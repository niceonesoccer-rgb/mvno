<?php
/**
 * notices 테이블 구조 확인 및 필요한 컬럼 추가 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/database/check_notices_table.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('<h2 style="color: red;">데이터베이스 연결에 실패했습니다.</h2>');
}

try {
    echo '<h2>notices 테이블 구조 확인 및 수정</h2>';
    echo '<style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #6366f1; color: white; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
    </style>';
    
    // 현재 테이블 구조 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM notices");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<h3>현재 테이블 구조:</h3>';
    echo '<table>';
    echo '<tr><th>필드명</th><th>타입</th><th>NULL</th><th>기본값</th><th>설명</th></tr>';
    
    $existingColumns = [];
    foreach ($columns as $col) {
        $existingColumns[] = $col['Field'];
        echo '<tr>';
        echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
        echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
        echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
        echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
        echo '<td>' . htmlspecialchars($col['Comment'] ?? '') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    // 필요한 컬럼 목록
    $requiredColumns = [
        'show_on_main' => [
            'type' => 'TINYINT(1)',
            'null' => 'NOT NULL',
            'default' => '0',
            'comment' => '메인페이지 새창 표시 여부',
            'after' => 'is_published'
        ],
        'image_url' => [
            'type' => 'VARCHAR(500)',
            'null' => 'DEFAULT NULL',
            'default' => 'NULL',
            'comment' => '공지사항 이미지 URL',
            'after' => 'content'
        ],
        'link_url' => [
            'type' => 'VARCHAR(500)',
            'null' => 'DEFAULT NULL',
            'default' => 'NULL',
            'comment' => '공지사항 링크 URL',
            'after' => 'image_url'
        ]
    ];
    
    $messages = [];
    $addedColumns = [];
    
    // 각 컬럼 확인 및 추가
    foreach ($requiredColumns as $colName => $colDef) {
        if (!in_array($colName, $existingColumns)) {
            try {
                $sql = "ALTER TABLE notices ADD COLUMN `{$colName}` {$colDef['type']} {$colDef['null']} DEFAULT {$colDef['default']} COMMENT '{$colDef['comment']}'";
                if (isset($colDef['after'])) {
                    $sql .= " AFTER `{$colDef['after']}`";
                }
                
                $pdo->exec($sql);
                $messages[] = "<span class='success'>✓ {$colName} 컬럼이 성공적으로 추가되었습니다.</span>";
                $addedColumns[] = $colName;
            } catch (PDOException $e) {
                $messages[] = "<span class='error'>✗ {$colName} 컬럼 추가 실패: " . htmlspecialchars($e->getMessage()) . "</span>";
            }
        } else {
            $messages[] = "<span class='info'>- {$colName} 컬럼이 이미 존재합니다.</span>";
        }
    }
    
    echo '<h3>작업 결과:</h3>';
    echo '<ul>';
    foreach ($messages as $msg) {
        echo '<li>' . $msg . '</li>';
    }
    echo '</ul>';
    
    if (!empty($addedColumns)) {
        echo '<h3 class="success">다음 컬럼이 추가되었습니다: ' . implode(', ', $addedColumns) . '</h3>';
        
        // 업데이트된 테이블 구조 다시 확인
        $stmt = $pdo->query("SHOW COLUMNS FROM notices");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<h3>업데이트된 테이블 구조:</h3>';
        echo '<table>';
        echo '<tr><th>필드명</th><th>타입</th><th>NULL</th><th>기본값</th><th>설명</th></tr>';
        foreach ($columns as $col) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
            echo '<td>' . htmlspecialchars($col['Comment'] ?? '') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    echo '<p><a href="/MVNO/admin/content/notice-manage.php" style="display: inline-block; padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px;">공지사항 관리 페이지로 이동</a></p>';
    
} catch (PDOException $e) {
    echo '<h2 class="error">오류 발생: ' . htmlspecialchars($e->getMessage()) . '</h2>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}
?>








