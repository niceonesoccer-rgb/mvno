<?php
/**
 * application_customers 테이블 구조 확인 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/api/check-application-customers-table.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>application_customers 테이블 구조 확인</h1>";

$pdo = getDBConnection();
if (!$pdo) {
    echo "<p style='color: red;'>데이터베이스 연결 실패</p>";
    exit;
}

echo "<h2>1. 테이블 존재 확인</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'application_customers'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ application_customers 테이블 존재</p>";
    } else {
        echo "<p style='color: red;'>✗ application_customers 테이블 없음</p>";
        exit;
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>테이블 확인 실패: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>2. 테이블 구조 (SHOW COLUMNS)</h2>";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM application_customers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>컬럼 목록 (배열):</h3>";
    $columnNames = array_column($columns, 'Field');
    echo "<pre>" . implode(', ', $columnNames) . "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>컬럼 조회 실패: " . $e->getMessage() . "</p>";
}

echo "<h2>3. 예상되는 컬럼과 실제 컬럼 비교</h2>";
$expectedColumns = [
    'id',
    'application_id',
    'user_id',
    'name',
    'phone',
    'email',
    'address',
    'address_detail',
    'birth_date',
    'gender',
    'additional_info',
    'created_at',
    'updated_at'
];

$actualColumns = isset($columnNames) ? $columnNames : [];

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>예상 컬럼</th><th>존재 여부</th></tr>";
foreach ($expectedColumns as $expected) {
    $exists = in_array($expected, $actualColumns);
    $color = $exists ? 'green' : 'red';
    $mark = $exists ? '✓' : '✗';
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($expected) . "</strong></td>";
    echo "<td style='color: {$color};'>{$mark} " . ($exists ? '존재' : '없음') . "</td>";
    echo "</tr>";
}
echo "</table>";

$missingColumns = array_diff($expectedColumns, $actualColumns);
if (!empty($missingColumns)) {
    echo "<h3 style='color: red;'>⚠️ 누락된 컬럼:</h3>";
    echo "<ul>";
    foreach ($missingColumns as $missing) {
        echo "<li style='color: red;'><strong>" . htmlspecialchars($missing) . "</strong></li>";
    }
    echo "</ul>";
    
    echo "<h3>해결 방법:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    foreach ($missingColumns as $missing) {
        if ($missing === 'user_id') {
            echo "ALTER TABLE application_customers ADD COLUMN user_id VARCHAR(50) DEFAULT NULL COMMENT '회원 user_id (비회원 신청 가능)' AFTER application_id;\n";
        } else {
            echo "-- {$missing} 컬럼 추가 필요 (스키마 확인)\n";
        }
    }
    echo "</pre>";
}

echo "<h2>4. 테이블 CREATE 문 확인</h2>";
try {
    $stmt = $pdo->query("SHOW CREATE TABLE application_customers");
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









