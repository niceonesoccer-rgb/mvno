<?php
/**
 * product_applications 테이블 구조 확인 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/api/check-product-applications-table.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>product_applications 테이블 구조 확인</h1>";

$pdo = getDBConnection();
if (!$pdo) {
    echo "<p style='color: red;'>데이터베이스 연결 실패</p>";
    exit;
}

echo "<h2>1. 테이블 존재 확인</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'product_applications'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ product_applications 테이블 존재</p>";
    } else {
        echo "<p style='color: red;'>✗ product_applications 테이블 없음</p>";
        exit;
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>테이블 확인 실패: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>2. 테이블 구조 (DESCRIBE)</h2>";
try {
    $stmt = $pdo->query("DESCRIBE product_applications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        $highlight = ($col['Field'] === 'user_id') ? 'background: yellow;' : '';
        echo "<tr style='{$highlight}'>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $hasUserId = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'user_id') {
            $hasUserId = true;
            break;
        }
    }
    
    if ($hasUserId) {
        echo "<p style='color: green;'>✓ user_id 컬럼이 존재합니다.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ user_id 컬럼이 없습니다!</p>";
        echo "<h3>해결 방법: 컬럼 추가</h3>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        echo "ALTER TABLE product_applications \n";
        echo "  ADD COLUMN user_id VARCHAR(50) DEFAULT NULL COMMENT '신청자 user_id (users.user_id)' AFTER product_type;\n\n";
        echo "ALTER TABLE product_applications \n";
        echo "  ADD INDEX idx_user_id (user_id);";
        echo "</pre>";
        echo "<p><strong>위 SQL을 phpMyAdmin에서 실행하거나 아래 버튼을 클릭하세요:</strong></p>";
        echo "<form method='POST' style='margin-top: 20px;'>";
        echo "<input type='hidden' name='add_column' value='1'>";
        echo "<button type='submit' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>user_id 컬럼 추가하기</button>";
        echo "</form>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>테이블 구조 확인 실패: " . $e->getMessage() . "</p>";
}

// 컬럼 추가 처리
if (isset($_POST['add_column']) && $_POST['add_column'] == '1') {
    echo "<h2>3. 컬럼 추가 실행</h2>";
    try {
        // user_id 컬럼 추가
        $pdo->exec("ALTER TABLE product_applications ADD COLUMN user_id VARCHAR(50) DEFAULT NULL COMMENT '신청자 user_id (users.user_id)' AFTER product_type");
        echo "<p style='color: green;'>✓ user_id 컬럼 추가 완료</p>";
        
        // 인덱스 추가
        try {
            $pdo->exec("ALTER TABLE product_applications ADD INDEX idx_user_id (user_id)");
            echo "<p style='color: green;'>✓ user_id 인덱스 추가 완료</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "<p style='color: orange;'>⚠ 인덱스가 이미 존재합니다.</p>";
            } else {
                echo "<p style='color: red;'>✗ 인덱스 추가 실패: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<p style='color: green; font-weight: bold;'>✅ 완료! 이제 페이지를 새로고침하여 확인하세요.</p>";
        echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: orange;'>⚠ user_id 컬럼이 이미 존재합니다.</p>";
        } else {
            echo "<p style='color: red;'>✗ 컬럼 추가 실패: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<h2>4. 테이블 CREATE 문 확인</h2>";
try {
    $stmt = $pdo->query("SHOW CREATE TABLE product_applications");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (isset($result['Create Table'])) {
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
        echo htmlspecialchars($result['Create Table']);
        echo "</pre>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>CREATE 문 조회 실패: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='/MVNO/api/test-db-connection.php'>데이터베이스 연결 테스트로 돌아가기</a></p>";



