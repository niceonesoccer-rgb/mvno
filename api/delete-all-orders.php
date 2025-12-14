<?php
/**
 * 모든 주문 데이터 삭제 API
 * 주의: 이 스크립트는 모든 주문 정보를 영구적으로 삭제합니다.
 */

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json; charset=utf-8');

// 관리자 권한 체크
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

$currentUser = getCurrentUser();

// 관리자 또는 서브 관리자만 접근 가능
if (!$currentUser || !in_array($currentUser['role'], ['admin', 'sub_admin'])) {
    echo json_encode([
        'success' => false,
        'message' => '관리자 권한이 필요합니다.'
    ]);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'POST 요청만 허용됩니다.'
    ]);
    exit;
}

// 확인 코드 체크 (보안)
$confirmCode = isset($_POST['confirm']) ? trim($_POST['confirm']) : '';
if ($confirmCode !== 'DELETE_ALL_ORDERS') {
    echo json_encode([
        'success' => false,
        'message' => '확인 코드가 올바르지 않습니다.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    // 삭제 전 개수 확인
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_applications");
    $beforeCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // 외래키 제약조건 일시적으로 비활성화 (성능 최적화)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 모든 주문 데이터 삭제
    $stmt = $pdo->prepare("DELETE FROM product_applications");
    $stmt->execute();
    
    // products 테이블의 application_count 초기화
    $stmt = $pdo->prepare("UPDATE products SET application_count = 0");
    $stmt->execute();
    
    // 외래키 제약조건 다시 활성화
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // 트랜잭션 커밋
    $pdo->commit();
    
    // 삭제 후 개수 확인
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_applications");
    $afterCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM application_customers");
    $customerCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'message' => "모든 주문 데이터가 삭제되었습니다.",
        'deleted_count' => $beforeCount,
        'remaining_applications' => $afterCount,
        'remaining_customers' => $customerCount
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // 외래키 제약조건 다시 활성화 (오류 발생 시)
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $ex) {
        // 무시
    }
    
    error_log("Delete All Orders Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '주문 삭제 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
