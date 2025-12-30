<?php
/**
 * 게시물 선택/배정 API
 * POST /api/bidding/assign-product.php
 * 
 * 요청 데이터:
 * {
 *   "bidding_participation_id": 123,
 *   "product_id": 456
 * }
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: application/json; charset=utf-8');

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST 메서드만 허용됩니다.']);
    exit;
}

// 로그인 체크
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'seller') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '판매자만 접근할 수 있습니다.']);
    exit;
}

// 판매자 승인 체크
if (!isSellerApproved()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '판매자 승인이 필요합니다.']);
    exit;
}

// 요청 데이터 파싱
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$biddingParticipationId = isset($input['bidding_participation_id']) ? intval($input['bidding_participation_id']) : 0;
$productId = isset($input['product_id']) ? intval($input['product_id']) : 0;

if ($biddingParticipationId <= 0 || $productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '입찰 참여 ID와 상품 ID가 올바르지 않습니다.']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }

    // 트랜잭션 시작
    $pdo->beginTransaction();

    // 입찰 참여 정보 조회 및 권한 검증
    $stmt = $pdo->prepare("
        SELECT 
            bp.id,
            bp.bidding_round_id,
            bp.seller_id,
            bp.bid_amount,
            bp.status,
            bp.rank,
            br.category,
            br.display_start_at,
            br.status as round_status,
            br.rotation_type
        FROM bidding_participations bp
        INNER JOIN bidding_rounds br ON bp.bidding_round_id = br.id
        WHERE bp.id = :participation_id
        FOR UPDATE
    ");
    $stmt->execute([':participation_id' => $biddingParticipationId]);
    $participation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$participation) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '입찰 참여 정보를 찾을 수 없습니다.']);
        exit;
    }

    // 본인 입찰인지 확인
    if ($participation['seller_id'] !== $currentUser['user_id']) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '본인의 입찰만 선택할 수 있습니다.']);
        exit;
    }

    // 낙찰 상태 확인
    if ($participation['status'] !== 'won') {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '낙찰된 입찰만 게시물을 선택할 수 있습니다.']);
        exit;
    }

    // 게시 기간 시작 전인지 확인
    $now = date('Y-m-d H:i:s');
    if ($participation['display_start_at'] <= $now) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '게시 기간이 시작되어 게시물을 선택할 수 없습니다.']);
        exit;
    }

    // 상품 존재 및 권한 확인
    // 주의: products.seller_id는 INT이지만, MySQL이 자동 타입 변환을 수행하므로 문자열로 전달해도 작동합니다
    $sellerId = (string)$currentUser['user_id'];
    $stmt = $pdo->prepare("
        SELECT id, seller_id, product_type, status
        FROM products
        WHERE id = :product_id
          AND seller_id = :seller_id
          AND status = 'active'
        FOR UPDATE
    ");
    $stmt->execute([
        ':product_id' => $productId,
        ':seller_id' => $sellerId
    ]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '상품을 찾을 수 없거나 선택할 수 없는 상태입니다.']);
        exit;
    }

    // 카테고리 매핑 확인
    $categoryMapping = [
        'mvno' => 'mvno',
        'mno' => 'mno',
        'mno_sim' => 'mno_sim'
    ];
    $expectedProductType = $categoryMapping[$participation['category']] ?? null;

    if ($product['product_type'] !== $expectedProductType) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '해당 카테고리의 상품만 선택할 수 있습니다.']);
        exit;
    }

    // 이미 다른 입찰에서 선택된 상품인지 확인 (같은 라운드 내에서만 체크)
    $stmt = $pdo->prepare("
        SELECT bpa.id
        FROM bidding_product_assignments bpa
        INNER JOIN bidding_participations bp ON bpa.bidding_participation_id = bp.id
        WHERE bpa.product_id = :product_id
          AND bp.bidding_round_id = :round_id
          AND bpa.bidding_participation_id != :participation_id
    ");
    $stmt->execute([
        ':product_id' => $productId,
        ':round_id' => $participation['bidding_round_id'],
        ':participation_id' => $biddingParticipationId
    ]);

    if ($stmt->fetch()) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '이미 다른 낙찰자가 선택한 상품입니다.']);
        exit;
    }

    // 기존 배정 확인
    $stmt = $pdo->prepare("
        SELECT id, product_id
        FROM bidding_product_assignments
        WHERE bidding_participation_id = :participation_id
    ");
    $stmt->execute([':participation_id' => $biddingParticipationId]);
    $existingAssignment = $stmt->fetch(PDO::FETCH_ASSOC);

    // display_order 계산 (입찰 금액 순, 동점 시 입찰 시간 빠른 순)
    $stmt = $pdo->prepare("
        SELECT 
            bp.id,
            bp.bid_amount,
            bp.bid_at
        FROM bidding_participations bp
        WHERE bp.bidding_round_id = :round_id
          AND bp.status = 'won'
        ORDER BY bp.bid_amount DESC, bp.bid_at ASC
    ");
    $stmt->execute([':round_id' => $participation['bidding_round_id']]);
    $allWonParticipations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $displayOrder = null;
    foreach ($allWonParticipations as $index => $wonPart) {
        if ($wonPart['id'] == $biddingParticipationId) {
            $displayOrder = $index + 1;
            break;
        }
    }

    if ($displayOrder === null) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '순위를 계산할 수 없습니다.']);
        exit;
    }

    // 배정 생성 또는 업데이트
    if ($existingAssignment) {
        // 기존 배정 업데이트
        $stmt = $pdo->prepare("
            UPDATE bidding_product_assignments
            SET product_id = :product_id,
                display_order = :display_order,
                bid_amount = :bid_amount,
                last_rotated_at = NULL
            WHERE id = :assignment_id
        ");
        $stmt->execute([
            ':assignment_id' => $existingAssignment['id'],
            ':product_id' => $productId,
            ':display_order' => $displayOrder,
            ':bid_amount' => $participation['bid_amount']
        ]);
        $assignmentId = $existingAssignment['id'];
    } else {
        // 새 배정 생성
        $stmt = $pdo->prepare("
            INSERT INTO bidding_product_assignments (
                bidding_round_id,
                bidding_participation_id,
                product_id,
                display_order,
                bid_amount,
                assigned_at
            ) VALUES (
                :bidding_round_id,
                :bidding_participation_id,
                :product_id,
                :display_order,
                :bid_amount,
                NOW()
            )
        ");
        $stmt->execute([
            ':bidding_round_id' => $participation['bidding_round_id'],
            ':bidding_participation_id' => $biddingParticipationId,
            ':product_id' => $productId,
            ':display_order' => $displayOrder,
            ':bid_amount' => $participation['bid_amount']
        ]);
        $assignmentId = $pdo->lastInsertId();
    }

    // 트랜잭션 커밋
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => '게시물이 선택되었습니다.',
        'assignment' => [
            'id' => $assignmentId,
            'bidding_round_id' => $participation['bidding_round_id'],
            'bidding_participation_id' => $biddingParticipationId,
            'product_id' => $productId,
            'display_order' => $displayOrder,
            'bid_amount' => $participation['bid_amount']
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in assign-product.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '게시물 선택 중 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}

