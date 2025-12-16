<?php
/**
 * 인터넷 등록상품 및 주문내역 삭제 스크립트
 * 
 * 주의: 이 스크립트는 모든 인터넷 상품과 주문 데이터를 삭제합니다.
 * 실행 전에 반드시 백업을 확인하세요.
 */

require_once __DIR__ . '/../includes/data/db-config.php';

// 안전을 위해 직접 실행 시에만 동작하도록 설정
$confirmDelete = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if (!$confirmDelete) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>인터넷 주문 데이터 삭제</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; }
            .warning { background: #fee2e2; border: 2px solid #ef4444; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .info { background: #dbeafe; border: 2px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .button { display: inline-block; padding: 12px 24px; background: #ef4444; color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; }
            .button:hover { background: #dc2626; }
            .button-secondary { background: #6b7280; }
            .button-secondary:hover { background: #4b5563; }
        </style>
    </head>
    <body>
        <h1>⚠️ 인터넷 주문 데이터 삭제</h1>
        <div class='warning'>
            <h2>경고</h2>
            <p>이 작업은 <strong>모든 인터넷 등록상품과 주문 데이터를 영구적으로 삭제</strong>합니다.</p>
            <p>삭제되는 데이터:</p>
            <ul>
                <li>products 테이블의 모든 인터넷 상품 (product_type = 'internet')</li>
                <li>product_internet_details 테이블의 모든 인터넷 상품 상세 정보</li>
                <li>product_applications 테이블의 모든 인터넷 주문 (product_type = 'internet')</li>
                <li>application_customers 테이블의 관련 고객 정보</li>
            </ul>
            <p><strong>이 작업은 되돌릴 수 없습니다!</strong></p>
        </div>
        <div class='info'>
            <p>삭제를 진행하려면 아래 버튼을 클릭하세요.</p>
            <a href='?confirm=yes' class='button' onclick='return confirm(\"정말로 모든 인터넷 등록상품과 주문 데이터를 삭제하시겠습니까?\\n\\n이 작업은 되돌릴 수 없습니다!\");'>삭제 실행</a>
            <a href='../' class='button button-secondary'>취소</a>
        </div>
    </body>
    </html>";
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    $pdo->beginTransaction();
    
    // 1. 인터넷 주문의 고객 정보 삭제
    $deleteCustomers = $pdo->prepare("
        DELETE c FROM application_customers c
        INNER JOIN product_applications a ON c.application_id = a.id
        WHERE a.product_type = 'internet'
    ");
    $deleteCustomers->execute();
    $deletedCustomers = $deleteCustomers->rowCount();
    
    // 2. 인터넷 주문 삭제
    $deleteApplications = $pdo->prepare("
        DELETE FROM product_applications 
        WHERE product_type = 'internet'
    ");
    $deleteApplications->execute();
    $deletedApplications = $deleteApplications->rowCount();
    
    // 3. 인터넷 상품 상세 정보 삭제 (CASCADE로 자동 삭제되지만 명시적으로 삭제)
    $deleteDetails = $pdo->prepare("
        DELETE FROM product_internet_details
    ");
    $deleteDetails->execute();
    $deletedDetails = $deleteDetails->rowCount();
    
    // 4. 인터넷 상품 삭제 (찜, 공유, 조회수 등 관련 데이터도 함께 삭제됨)
    $deleteProducts = $pdo->prepare("
        DELETE FROM products 
        WHERE product_type = 'internet'
    ");
    $deleteProducts->execute();
    $deletedProducts = $deleteProducts->rowCount();
    
    $pdo->commit();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>삭제 완료</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; }
            .success { background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .info { background: #dbeafe; border: 2px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .button { display: inline-block; padding: 12px 24px; background: #10b981; color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; }
        </style>
    </head>
    <body>
        <h1>✅ 삭제 완료</h1>
        <div class='success'>
            <h2>인터넷 등록상품과 주문 데이터가 성공적으로 삭제되었습니다.</h2>
            <p>삭제된 데이터:</p>
            <ul>
                <li>인터넷 상품: <strong>{$deletedProducts}개</strong></li>
                <li>인터넷 상품 상세 정보: <strong>{$deletedDetails}개</strong></li>
                <li>주문 건수: <strong>{$deletedApplications}건</strong></li>
                <li>고객 정보: <strong>{$deletedCustomers}건</strong></li>
            </ul>
        </div>
        <div class='info'>
            <p>이제 새로운 서비스 타입(인터넷/인터넷+TV)으로 상품을 등록하고 주문을 받을 수 있습니다.</p>
            <a href='../' class='button'>홈으로 돌아가기</a>
        </div>
    </body>
    </html>";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>삭제 오류</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; }
            .error { background: #fee2e2; border: 2px solid #ef4444; padding: 20px; border-radius: 8px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <h1>❌ 삭제 오류</h1>
        <div class='error'>
            <p>오류가 발생했습니다: " . htmlspecialchars($e->getMessage()) . "</p>
            <p>데이터베이스 트랜잭션이 롤백되었습니다.</p>
        </div>
    </body>
    </html>";
    
    error_log("Delete internet products and orders error: " . $e->getMessage());
}
