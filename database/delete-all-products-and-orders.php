<?php
/**
 * 모든 주문 상품 및 등록 상품 삭제 스크립트
 * 
 * 주의: 이 스크립트는 다음 테이블의 모든 데이터를 삭제합니다:
 * - product_applications (주문/신청 내역)
 * - application_customers (고객 정보)
 * - products (등록된 상품)
 * - product_mvno_details (MVNO 상품 상세)
 * - product_mno_details (MNO 상품 상세)
 * - product_internet_details (인터넷 상품 상세)
 * - product_reviews (리뷰)
 * - product_favorites (찜)
 * - product_shares (공유)
 * 
 * 실행 전에 반드시 백업을 권장합니다.
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
        <title>모든 상품 및 주문 데이터 삭제</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 700px;
                margin: 50px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .warning-box {
                background: #fff3cd;
                border: 3px solid #ffc107;
                border-radius: 8px;
                padding: 25px;
                margin-bottom: 20px;
            }
            .warning-box h2 {
                color: #856404;
                margin-top: 0;
                font-size: 24px;
            }
            .warning-box ul {
                margin: 15px 0;
                padding-left: 25px;
            }
            .warning-box li {
                margin: 8px 0;
                font-size: 14px;
            }
            .btn {
                display: inline-block;
                padding: 14px 28px;
                margin: 10px 5px;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
                cursor: pointer;
                border: none;
                font-size: 16px;
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
        </style>
    </head>
    <body>
        <div class="warning-box">
            <h2>⚠️ 매우 중요한 경고</h2>
            <p>이 작업은 다음 테이블의 <strong>모든 데이터를 영구적으로 삭제</strong>합니다:</p>
            <ul>
                <li><code>product_applications</code> - 모든 주문/신청 내역</li>
                <li><code>application_customers</code> - 모든 고객 정보</li>
                <li><code>products</code> - 모든 등록된 상품</li>
                <li><code>product_mvno_details</code> - 모든 MVNO 상품 상세 정보</li>
                <li><code>product_mno_details</code> - 모든 MNO 상품 상세 정보</li>
                <li><code>product_internet_details</code> - 모든 인터넷 상품 상세 정보</li>
                <li><code>product_reviews</code> - 모든 리뷰</li>
                <li><code>product_favorites</code> - 모든 찜 목록</li>
                <li><code>product_shares</code> - 모든 공유 기록</li>
            </ul>
            <p style="color: #dc3545; font-weight: bold; font-size: 18px;">
                ⚠️ 이 작업은 되돌릴 수 없습니다! ⚠️
            </p>
            <p>계속하시려면 아래 버튼을 클릭하세요.</p>
        </div>
        <div style="text-align: center;">
            <a href="?confirm=yes" class="btn btn-danger" onclick="return confirm('정말로 모든 상품과 주문 데이터를 삭제하시겠습니까?\n\n이 작업은 되돌릴 수 없으며, 모든 등록된 상품과 주문 내역이 영구적으로 삭제됩니다!');">모든 데이터 삭제 실행</a>
            <a href="../" class="btn btn-secondary">취소</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 데이터 삭제 실행
$pdo = null;

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 외래 키 체크 비활성화 (삭제 순서 문제 해결)
    // TRUNCATE는 DDL이므로 트랜잭션을 사용하지 않음
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // 삭제 전 개수 확인
    $counts = [];
    $tables = [
        'product_applications',
        'application_customers',
        'product_reviews',
        'product_favorites',
        'product_shares',
        'product_mvno_details',
        'product_mno_details',
        'product_internet_details',
        'products'
    ];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $counts[$table] = $result['count'] ?? 0;
        } catch (PDOException $e) {
            $counts[$table] = 0; // 테이블이 없을 수 있음
        }
    }
    
    // 테이블 존재 여부 확인 함수
    $tableExists = function($tableName) use ($pdo) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    };
    
    // TRUNCATE 실행 함수 (테이블 존재 여부 확인 후)
    $truncateTable = function($tableName) use ($pdo, $tableExists) {
        if ($tableExists($tableName)) {
            try {
                $pdo->exec("TRUNCATE TABLE `{$tableName}`");
                return true;
            } catch (PDOException $e) {
                error_log("Failed to truncate table {$tableName}: " . $e->getMessage());
                return false;
            }
        }
        return false;
    };
    
    // 삭제 순서: 자식 테이블부터 부모 테이블 순서로
    // 1. 주문 관련 데이터
    $truncateTable('application_customers');
    $truncateTable('product_applications');
    
    // 2. 상품 관련 데이터 (리뷰, 찜, 공유)
    $truncateTable('product_reviews');
    $truncateTable('product_favorites');
    $truncateTable('product_shares');
    
    // 3. 상품 상세 정보
    $truncateTable('product_mvno_details');
    $truncateTable('product_mno_details');
    $truncateTable('product_internet_details');
    
    // 4. 상품 기본 정보 (마지막)
    $truncateTable('products');
    
    // 외래 키 체크 재활성화
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>삭제 완료</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 700px;
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
            .info table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            .info table th,
            .info table td {
                padding: 8px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            .info table th {
                background: #f0f0f0;
                font-weight: bold;
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
                <h2>✅ 삭제 완료</h2>
                <p>모든 상품 및 주문 데이터가 성공적으로 삭제되었습니다.</p>
            </div>
            
            <div class="info">
                <h3>삭제된 데이터 통계:</h3>
                <table>
                    <tr>
                        <th>테이블명</th>
                        <th>삭제된 레코드 수</th>
                    </tr>
                    <?php foreach ($counts as $table => $count): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($table); ?></code></td>
                        <td><?php echo number_format($count); ?>개</td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <p style="margin-top: 15px;"><strong>총 삭제된 레코드:</strong> <?php echo number_format(array_sum($counts)); ?>개</p>
            </div>
            
            <div style="text-align: center;">
                <a href="../" class="btn">홈으로 이동</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    // 외래키 체크 재활성화 (오류 발생 시에도)
    if (isset($pdo)) {
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (Exception $ex) {
            // 무시
        }
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



















