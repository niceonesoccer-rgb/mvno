<?php
/**
 * application_customers INSERT 진단 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/api/diagnose-application-insert.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>application_customers INSERT 진단</h1>";

$pdo = getDBConnection();
if (!$pdo) {
    echo "<p style='color: red;'>데이터베이스 연결 실패</p>";
    exit;
}

echo "<h2>1. 테이블 구조 확인</h2>";
try {
    $stmt = $pdo->query("DESCRIBE application_customers");
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
        echo "<h3>해결 방법 (A안): 컬럼 추가</h3>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        echo "ALTER TABLE application_customers \n";
        echo "  ADD COLUMN user_id VARCHAR(50) DEFAULT NULL COMMENT '회원 user_id' AFTER application_id;\n\n";
        echo "ALTER TABLE application_customers \n";
        echo "  ADD INDEX idx_user_id (user_id);";
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>테이블 구조 확인 실패: " . $e->getMessage() . "</p>";
}

echo "<h2>2. 현재 코드에서 사용하는 INSERT 쿼리</h2>";
echo "<h3>수정된 쿼리 (user_id 없음):</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
echo "INSERT INTO application_customers \n";
echo "  (application_id, name, phone, email, address, address_detail, birth_date, gender, additional_info) \n";
echo "VALUES \n";
echo "  (:application_id, :name, :phone, :email, :address, :address_detail, :birth_date, :gender, :additional_info)";
echo "</pre>";

echo "<h2>3. 테스트 INSERT 실행</h2>";
if ($hasUserId) {
    echo "<p style='color: orange;'>⚠️ user_id 컬럼이 존재하므로, user_id 포함 INSERT도 테스트합니다.</p>";
    
    // 테스트용 데이터
    $testApplicationId = 999999; // 존재하지 않는 ID (테스트용)
    
    echo "<h3>테스트 1: user_id 없이 INSERT (현재 코드 방식)</h3>";
    try {
        $sql1 = "INSERT INTO application_customers (application_id, name, phone, email) VALUES (:application_id, :name, :phone, :email)";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute([
            ':application_id' => $testApplicationId,
            ':name' => '테스트',
            ':phone' => '01012345678',
            ':email' => 'test@test.com'
        ]);
        echo "<p style='color: green;'>✓ user_id 없이 INSERT 성공</p>";
        
        // 롤백 (테스트 데이터 삭제)
        $pdo->exec("DELETE FROM application_customers WHERE application_id = {$testApplicationId}");
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ user_id 없이 INSERT 실패: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>테스트 2: user_id 포함 INSERT (에러 재현)</h3>";
    try {
        $sql2 = "INSERT INTO application_customers (application_id, user_id, name, phone, email) VALUES (:application_id, :user_id, :name, :phone, :email)";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([
            ':application_id' => $testApplicationId,
            ':user_id' => 'test_user',
            ':name' => '테스트',
            ':phone' => '01012345678',
            ':email' => 'test@test.com'
        ]);
        echo "<p style='color: green;'>✓ user_id 포함 INSERT 성공 (컬럼이 존재함)</p>";
        
        // 롤백 (테스트 데이터 삭제)
        $pdo->exec("DELETE FROM application_customers WHERE application_id = {$testApplicationId}");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'user_id') !== false) {
            echo "<p style='color: red; font-weight: bold;'>✗ user_id 포함 INSERT 실패: " . $e->getMessage() . "</p>";
            echo "<p style='color: red;'>이것이 현재 발생하는 에러입니다!</p>";
        } else {
            echo "<p style='color: orange;'>⚠ 다른 에러: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p style='color: red;'>user_id 컬럼이 없으므로 user_id 포함 INSERT는 실패할 것입니다.</p>";
    echo "<p>위의 '해결 방법 (A안)'을 실행하여 컬럼을 추가하세요.</p>";
}

echo "<h2>4. PHP 코드 확인</h2>";
echo "<p>현재 코드 위치: <code>includes/data/product-functions.php</code> (line 1560)</p>";
echo "<p>현재 SQL은 user_id 없이 실행되도록 수정되어 있습니다.</p>";

echo "<h2>5. 권장 해결 방법</h2>";
if ($hasUserId) {
    echo "<p style='color: green;'>✓ 테이블에 user_id 컬럼이 존재합니다.</p>";
    echo "<p>하지만 실제 INSERT 시 에러가 발생한다면:</p>";
    echo "<ol>";
    echo "<li><strong>PHP 캐시 문제</strong>: 웹서버를 재시작하세요 (Apache/XAMPP 재시작)</li>";
    echo "<li><strong>OPcache 문제</strong>: PHP OPcache를 비활성화하거나 클리어하세요</li>";
    echo "<li><strong>코드 경로 문제</strong>: 다른 파일에서 INSERT를 실행하고 있을 수 있습니다</li>";
    echo "</ol>";
} else {
    echo "<p style='color: red;'>✗ 테이블에 user_id 컬럼이 없습니다.</p>";
    echo "<p><strong>해결 방법: 위의 '해결 방법 (A안)' SQL을 실행하세요.</strong></p>";
}

echo "<hr>";
echo "<p><a href='/MVNO/api/test-db-connection.php'>데이터베이스 연결 테스트로 돌아가기</a></p>";

