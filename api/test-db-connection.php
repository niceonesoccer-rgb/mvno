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
    $tables = ['products', 'product_mvno_details', 'product_mno_details', 'product_internet_details'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>✓ 테이블 '{$table}' 존재</p>";
            } else {
                echo "<p style='color: red;'>✗ 테이블 '{$table}' 없음</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ 테이블 '{$table}' 확인 실패: " . $e->getMessage() . "</p>";
        }
    }
    
    // 3. 데이터베이스 정보
    echo "<h2>3. 데이터베이스 정보</h2>";
    try {
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $dbInfo = $stmt->fetch();
        echo "<p>데이터베이스명: " . htmlspecialchars($dbInfo['db_name']) . "</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>데이터베이스 정보 조회 실패: " . $e->getMessage() . "</p>";
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


