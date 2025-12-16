<?php
/**
 * 주문 관리 데이터 초기화 스크립트
 * 
 * 주의: 이 스크립트는 product_applications와 application_customers 테이블의 
 * 모든 데이터를 삭제합니다. 실행 전에 백업을 권장합니다.
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

// 안전을 위해 확인 메시지
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if (!$confirmed) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>주문 데이터 초기화</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .warning-box {
                background: #fff3cd;
                border: 2px solid #ffc107;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .warning-box h2 {
                color: #856404;
                margin-top: 0;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                margin: 10px 5px;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
                cursor: pointer;
                border: none;
            }
            .btn-danger {
                background: #dc3545;
                color: white;
            }
            .btn-danger:hover {
                background: #c82333;
            }
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
            .btn-secondary:hover {
                background: #5a6268;
            }
            .result-box {
                background: white;
                border-radius: 8px;
                padding: 20px;
                margin-top: 20px;
            }
            .success {
                color: #155724;
                background: #d4edda;
                border: 1px solid #c3e6cb;
                padding: 15px;
                border-radius: 6px;
            }
            .error {
                color: #721c24;
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                padding: 15px;
                border-radius: 6px;
            }
        </style>
    </head>
    <body>
        <div class="warning-box">
            <h2>⚠️ 주의사항</h2>
            <p>이 작업은 다음 테이블의 <strong>모든 데이터를 삭제</strong>합니다:</p>
            <ul>
                <li><code>product_applications</code> (상품 신청)</li>
                <li><code>application_customers</code> (신청 고객 정보)</li>
            </ul>
            <p><strong>이 작업은 되돌릴 수 없습니다!</strong></p>
            <p>계속하시려면 아래 버튼을 클릭하세요.</p>
        </div>
        <div style="text-align: center;">
            <a href="?confirm=yes" class="btn btn-danger" onclick="return confirm('정말로 모든 주문 데이터를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다!');">데이터 초기화 실행</a>
            <a href="../" class="btn btn-secondary">취소</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 데이터 초기화 실행
try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();
    
    // 외래 키 체크 비활성화 (삭제 순서 문제 해결)
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // application_customers 테이블 초기화
    $stmt = $pdo->exec('TRUNCATE TABLE application_customers');
    $customersDeleted = $stmt;
    
    // product_applications 테이블 초기화
    $stmt = $pdo->exec('TRUNCATE TABLE product_applications');
    $applicationsDeleted = $stmt;
    
    // 외래 키 체크 재활성화
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    $pdo->commit();
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>초기화 완료</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .result-box {
                background: white;
                border-radius: 8px;
                padding: 30px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .success {
                color: #155724;
                background: #d4edda;
                border: 1px solid #c3e6cb;
                padding: 20px;
                border-radius: 6px;
                margin-bottom: 20px;
            }
            .info {
                background: #e7f3ff;
                border: 1px solid #b3d9ff;
                padding: 15px;
                border-radius: 6px;
                margin-top: 20px;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                margin-top: 20px;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
                background: #10b981;
                color: white;
            }
            .btn:hover {
                background: #059669;
            }
        </style>
    </head>
    <body>
        <div class="result-box">
            <div class="success">
                <h2>✅ 초기화 완료</h2>
                <p>주문 관리 데이터가 성공적으로 초기화되었습니다.</p>
            </div>
            
            <div class="info">
                <h3>초기화된 테이블:</h3>
                <ul>
                    <li><code>product_applications</code> - 모든 상품 신청 데이터 삭제됨</li>
                    <li><code>application_customers</code> - 모든 고객 정보 데이터 삭제됨</li>
                </ul>
                <p><strong>참고:</strong> 상품 정보(products)와 상품 상세 정보는 유지되었습니다.</p>
            </div>
            
            <div style="text-align: center;">
                <a href="../seller/orders/internet.php" class="btn">주문 관리 페이지로 이동</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>오류 발생</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .result-box {
                background: white;
                border-radius: 8px;
                padding: 30px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .error {
                color: #721c24;
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                padding: 20px;
                border-radius: 6px;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                margin-top: 20px;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
                background: #6c757d;
                color: white;
            }
        </style>
    </head>
    <body>
        <div class="result-box">
            <div class="error">
                <h2>❌ 오류 발생</h2>
                <p><strong>오류 메시지:</strong></p>
                <pre><?php echo htmlspecialchars($e->getMessage()); ?></pre>
            </div>
            <div style="text-align: center;">
                <a href="?" class="btn">다시 시도</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>




