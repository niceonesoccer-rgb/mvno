<?php
/**
 * 기존 광고 가격 설정 테이블 삭제 스크립트
 * 
 * 실행 방법: 브라우저에서 http://localhost/MVNO/database/drop_old_advertisement_price_tables.php 접속
 * 
 * 삭제 대상:
 * - product_advertisement_prices (기존 광고 가격 설정 테이블)
 * - product_advertisements (기존 광고 신청 테이블 - 관련 테이블이 있다면)
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결 실패');
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>기존 광고 가격 설정 테이블 삭제</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1e293b;
            margin-bottom: 10px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .info {
            background: #dbeafe;
            color: #1e40af;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .warning {
            background: #fef3c7;
            color: #92400e;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            margin-right: 10px;
        }
        .btn:hover {
            background: #4f46e5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #1e293b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>기존 광고 가격 설정 테이블 삭제</h1>
        
        <?php
        $tablesToDrop = [
            'product_advertisement_prices' => '기존 광고 가격 설정 테이블',
            'product_advertisements' => '기존 광고 신청 테이블'
        ];
        
        $droppedTables = [];
        $notFoundTables = [];
        $errorTables = [];
        
        foreach ($tablesToDrop as $tableName => $description) {
            try {
                // 테이블 존재 확인
                $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
                if ($stmt->rowCount() > 0) {
                    // 외래키 제약조건 확인 및 삭제
                    $stmt = $pdo->query("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = '$tableName' 
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ");
                    $foreignKeys = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // 외래키 제약조건 삭제 (참조하는 테이블의 제약조건도 삭제)
                    foreach ($foreignKeys as $fkName) {
                        try {
                            $pdo->exec("ALTER TABLE `$tableName` DROP FOREIGN KEY `$fkName`");
                        } catch (PDOException $e) {
                            // 외래키가 없으면 무시
                        }
                    }
                    
                    // 다른 테이블에서 이 테이블을 참조하는 외래키 찾기
                    $stmt = $pdo->query("
                        SELECT TABLE_NAME, CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND REFERENCED_TABLE_NAME = '$tableName'
                    ");
                    $referencingKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // 참조하는 외래키 삭제
                    foreach ($referencingKeys as $ref) {
                        try {
                            $pdo->exec("ALTER TABLE `{$ref['TABLE_NAME']}` DROP FOREIGN KEY `{$ref['CONSTRAINT_NAME']}`");
                        } catch (PDOException $e) {
                            // 외래키가 없으면 무시
                        }
                    }
                    
                    // 테이블 삭제
                    $pdo->exec("DROP TABLE IF EXISTS `$tableName`");
                    $droppedTables[] = ['name' => $tableName, 'description' => $description];
                    echo "<div class='success'>✓ $description ($tableName) 테이블이 삭제되었습니다.</div>";
                } else {
                    $notFoundTables[] = ['name' => $tableName, 'description' => $description];
                    echo "<div class='info'>ℹ️ $description ($tableName) 테이블이 존재하지 않습니다.</div>";
                }
            } catch (Exception $e) {
                $errorTables[] = ['name' => $tableName, 'description' => $description, 'error' => $e->getMessage()];
                echo "<div class='error'><strong>❌ 오류 발생 ($tableName):</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
        
        echo "<h2>작업 요약</h2>";
        
        if (!empty($droppedTables)) {
            echo "<div class='success'><strong>✅ 삭제된 테이블:</strong></div>";
            echo "<table>";
            echo "<tr><th>테이블명</th><th>설명</th></tr>";
            foreach ($droppedTables as $table) {
                echo "<tr><td><strong>{$table['name']}</strong></td><td>{$table['description']}</td></tr>";
            }
            echo "</table>";
        }
        
        if (!empty($notFoundTables)) {
            echo "<div class='info'><strong>ℹ️ 존재하지 않는 테이블:</strong></div>";
            echo "<table>";
            echo "<tr><th>테이블명</th><th>설명</th></tr>";
            foreach ($notFoundTables as $table) {
                echo "<tr><td><strong>{$table['name']}</strong></td><td>{$table['description']}</td></tr>";
            }
            echo "</table>";
        }
        
        if (!empty($errorTables)) {
            echo "<div class='error'><strong>❌ 오류가 발생한 테이블:</strong></div>";
            echo "<table>";
            echo "<tr><th>테이블명</th><th>설명</th><th>오류 메시지</th></tr>";
            foreach ($errorTables as $table) {
                echo "<tr><td><strong>{$table['name']}</strong></td><td>{$table['description']}</td><td>{$table['error']}</td></tr>";
            }
            echo "</table>";
        }
        
        if (empty($droppedTables) && empty($errorTables)) {
            echo "<div class='info'><strong>✅ 삭제할 기존 테이블이 없습니다. 모든 작업이 완료되었습니다!</strong></div>";
        } elseif (empty($errorTables)) {
            echo "<div class='success'><strong>✅ 모든 작업이 완료되었습니다!</strong></div>";
        }
        ?>
        
        <a href="../admin/advertisement/prices.php" class="btn">광고 가격 설정 페이지로 이동</a>
        <a href="../admin/advertisement/duration-settings.php" class="btn">로테이션 시간 설정 페이지로 이동</a>
    </div>
</body>
</html>
