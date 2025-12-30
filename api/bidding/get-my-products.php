<?php
/**
 * 낙찰자가 선택할 수 있는 상품 목록 조회 API
 * GET /api/bidding/get-my-products.php?bidding_participation_id=123
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: application/json; charset=utf-8');

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

// 파라미터 검증
$biddingParticipationId = isset($_GET['bidding_participation_id']) ? intval($_GET['bidding_participation_id']) : 0;

if ($biddingParticipationId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '입찰 참여 ID가 올바르지 않습니다.']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }

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
            br.status as round_status
        FROM bidding_participations bp
        INNER JOIN bidding_rounds br ON bp.bidding_round_id = br.id
        WHERE bp.id = :participation_id
    ");
    $stmt->execute([':participation_id' => $biddingParticipationId]);
    $participation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$participation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '입찰 참여 정보를 찾을 수 없습니다.']);
        exit;
    }

    // 본인 입찰인지 확인
    if ($participation['seller_id'] !== $currentUser['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '본인의 입찰만 조회할 수 있습니다.']);
        exit;
    }

    // 낙찰 상태 확인
    if ($participation['status'] !== 'won') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '낙찰된 입찰만 게시물을 선택할 수 있습니다.']);
        exit;
    }

    // 게시 기간 시작 전인지 확인
    $now = date('Y-m-d H:i:s');
    if ($participation['display_start_at'] <= $now) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '게시 기간이 시작되어 게시물을 선택할 수 없습니다.']);
        exit;
    }

    // 이미 선택된 게시물 확인
    $stmt = $pdo->prepare("
        SELECT product_id
        FROM bidding_product_assignments
        WHERE bidding_participation_id = :participation_id
    ");
    $stmt->execute([':participation_id' => $biddingParticipationId]);
    $existingAssignment = $stmt->fetch(PDO::FETCH_ASSOC);
    $alreadySelectedProductId = $existingAssignment ? $existingAssignment['product_id'] : null;

    // 카테고리 매핑 (bidding_rounds.category -> products.product_type)
    $categoryMapping = [
        'mvno' => 'mvno',
        'mno' => 'mno',
        'mno_sim' => 'mno_sim'
    ];
    $productType = $categoryMapping[$participation['category']] ?? null;

    if (!$productType) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '올바르지 않은 카테고리입니다.']);
        exit;
    }

    // 해당 판매자의 활성화된 상품 목록 조회
    // 주의: products.seller_id는 INT이지만, MySQL이 자동 타입 변환을 수행하므로 문자열로 전달해도 작동합니다
    $sellerId = (string)$currentUser['user_id'];
    
    // 기본 쿼리 (카테고리별 상세 정보 포함)
    $sql = "
        SELECT 
            p.id,
            p.seller_id,
            p.product_type,
            p.status,
            p.created_at,
            CASE p.product_type
                WHEN 'mvno' THEN mvno.plan_name
                WHEN 'mno' THEN mno.device_name
                WHEN 'internet' THEN CONCAT(inet.registration_place, ' ', inet.speed_option)
                ELSE '상품명 없음'
            END AS product_name,
            CASE p.product_type
                WHEN 'mvno' THEN mvno.provider
                WHEN 'mno' THEN 'SKT/KT/LG U+'
                WHEN 'internet' THEN inet.registration_place
                ELSE ''
            END AS provider,
            CASE p.product_type
                WHEN 'mvno' THEN mvno.price_after
                WHEN 'mno' THEN mno.price_main
                WHEN 'internet' THEN inet.monthly_fee
                ELSE NULL
            END AS price
        FROM products p
        LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND p.product_type = 'mvno'
        LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
        LEFT JOIN product_internet_details inet ON p.id = inet.product_id AND p.product_type = 'internet'
        WHERE p.seller_id = :seller_id
          AND p.product_type = :product_type
          AND p.status = 'active'
        ORDER BY p.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':seller_id' => $sellerId,
        ':product_type' => $productType
    ]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 이미 선택된 상품 표시를 위해 플래그 추가
    foreach ($products as &$product) {
        $product['is_selected'] = ($product['id'] == $alreadySelectedProductId);
    }

    echo json_encode([
        'success' => true,
        'participation' => [
            'id' => $participation['id'],
            'category' => $participation['category'],
            'bid_amount' => $participation['bid_amount'],
            'rank' => $participation['rank'],
            'display_start_at' => $participation['display_start_at'],
            'selected_product_id' => $alreadySelectedProductId
        ],
        'products' => $products
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error in get-my-products.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '상품 목록을 조회하는 중 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}

