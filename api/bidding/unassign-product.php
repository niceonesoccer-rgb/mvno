<?php
/**
 * 게시물 선택 해제 API
 * DELETE /api/bidding/unassign-product.php?bidding_participation_id=123
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: application/json; charset=utf-8');

// DELETE 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['_method']) || $_POST['_method'] !== 'DELETE')) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'DELETE 메서드만 허용됩니다.']);
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

    // 트랜잭션 시작
    $pdo->beginTransaction();

    // 입찰 참여 정보 조회 및 권한 검증
    $stmt = $pdo->prepare("
        SELECT 
            bp.id,
            bp.bidding_round_id,
            bp.seller_id,
            bp.status,
            br.display_start_at
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
        echo json_encode(['success' => false, 'message' => '본인의 입찰만 해제할 수 있습니다.']);
        exit;
    }

    // 게시 기간 시작 전인지 확인
    $now = date('Y-m-d H:i:s');
    if ($participation['display_start_at'] <= $now) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '게시 기간이 시작되어 게시물 선택을 해제할 수 없습니다.']);
        exit;
    }

    // 배정 정보 조회
    $stmt = $pdo->prepare("
        SELECT id
        FROM bidding_product_assignments
        WHERE bidding_participation_id = :participation_id
    ");
    $stmt->execute([':participation_id' => $biddingParticipationId]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assignment) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '선택된 게시물이 없습니다.']);
        exit;
    }

    // 배정 삭제
    $stmt = $pdo->prepare("
        DELETE FROM bidding_product_assignments
        WHERE id = :assignment_id
    ");
    $stmt->execute([':assignment_id' => $assignment['id']]);

    // 트랜잭션 커밋
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => '게시물 선택이 해제되었습니다.'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in unassign-product.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '게시물 선택 해제 중 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}


