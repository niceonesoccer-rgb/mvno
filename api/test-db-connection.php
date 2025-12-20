<?php
/**
 * 데이터베이스 연결 테스트 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/api/test-db-connection.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>데이터베이스 연결 테스트</h1>";

// 1. 데이터베이스 연결 테스트
echo "<h2>1. 데이터베이스 연결</h2>";
$pdo = getDBConnection();
if ($pdo) {
    echo "<p style='color: green;'>✓ 데이터베이스 연결 성공</p>";
    
    // 2. 테이블 존재 확인
    echo "<h2>2. 테이블 존재 확인</h2>";
    $tables = [
        'products' => '상품 기본 정보',
        'product_mvno_details' => 'MVNO 상품 상세',
        'product_mno_details' => 'MNO 상품 상세',
        'product_internet_details' => '인터넷 상품 상세',
        'product_applications' => '상품 신청 (필수)',
        'application_customers' => '신청 고객 정보 (필수)',
        'product_reviews' => '상품 리뷰',
        'product_favorites' => '상품 찜',
        'product_shares' => '상품 공유'
    ];
    
    $missingTables = [];
    foreach ($tables as $table => $description) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>✓ 테이블 '{$table}' ({$description}) 존재</p>";
            } else {
                echo "<p style='color: red;'>✗ 테이블 '{$table}' ({$description}) 없음</p>";
                $missingTables[] = $table;
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ 테이블 '{$table}' 확인 실패: " . $e->getMessage() . "</p>";
            $missingTables[] = $table;
        }
    }
    
    // 필수 테이블 확인
    if (in_array('product_applications', $missingTables) || in_array('application_customers', $missingTables)) {
        echo "<p style='color: red; font-weight: bold;'>⚠️ 경고: 신청 기능에 필요한 필수 테이블이 없습니다!</p>";
        echo "<p>다음 명령을 실행하세요:</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        echo "phpMyAdmin에서 database/products_schema.sql 파일을 import하세요.\n";
        echo "또는 명령줄에서:\n";
        echo "mysql -u root mvno_db < database/products_schema.sql";
        echo "</pre>";
    }
    
    // 3. 필수 컬럼 확인
    echo "<h2>3. 필수 컬럼 확인</h2>";
    
    // product_applications 테이블의 order_number 컬럼 확인
    if (!in_array('product_applications', $missingTables)) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM product_applications LIKE 'order_number'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>✓ product_applications.order_number 컬럼 존재</p>";
            } else {
                echo "<p style='color: orange;'>⚠ product_applications.order_number 컬럼 없음 (자동 생성됨)</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ 컬럼 확인 실패: " . $e->getMessage() . "</p>";
        }
    }
    
    // application_customers 테이블의 user_id 컬럼 확인
    if (!in_array('application_customers', $missingTables)) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_customers LIKE 'user_id'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>✓ application_customers.user_id 컬럼 존재</p>";
            } else {
                echo "<p style='color: orange;'>⚠ application_customers.user_id 컬럼 없음 (자동 생성됨)</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ 컬럼 확인 실패: " . $e->getMessage() . "</p>";
        }
    }
    
    // 4. 데이터베이스 정보
    echo "<h2>4. 데이터베이스 정보</h2>";
    try {
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $dbInfo = $stmt->fetch();
        echo "<p>데이터베이스명: " . htmlspecialchars($dbInfo['db_name']) . "</p>";
        
        // MySQL 버전 확인
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        echo "<p>MySQL 버전: " . htmlspecialchars($version['version']) . "</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>데이터베이스 정보 조회 실패: " . $e->getMessage() . "</p>";
    }
    
    // 5. 테이블 데이터 확인
    echo "<h2>5. 테이블 데이터 확인</h2>";
    if (!in_array('products', $missingTables)) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
            $count = $stmt->fetch();
            echo "<p>products 테이블 레코드 수: " . $count['count'] . "</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>데이터 확인 실패: " . $e->getMessage() . "</p>";
        }
    }
    
    if (!in_array('product_applications', $missingTables)) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_applications");
            $count = $stmt->fetch();
            echo "<p>product_applications 테이블 레코드 수: " . $count['count'] . "</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>데이터 확인 실패: " . $e->getMessage() . "</p>";
        }
    }
    
} else {
    echo "<p style='color: red;'>✗ 데이터베이스 연결 실패</p>";
    echo "<p>설정 확인:</p>";
    echo "<ul>";
    echo "<li>호스트: " . DB_HOST . "</li>";
    echo "<li>데이터베이스: " . DB_NAME . "</li>";
    echo "<li>사용자: " . DB_USER . "</li>";
    echo "</ul>";
    echo "<p><strong>해결 방법:</strong></p>";
    echo "<ol>";
    echo "<li>데이터베이스가 생성되었는지 확인: <code>CREATE DATABASE mvno_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code></li>";
    echo "<li>스키마 파일을 실행했는지 확인: <code>database/products_schema.sql</code></li>";
    echo "<li>데이터베이스 접속 정보가 올바른지 확인: <code>includes/data/db-config.php</code></li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><a href='/MVNO/seller/products/mvno.php'>상품 등록 페이지로 돌아가기</a></p>";



