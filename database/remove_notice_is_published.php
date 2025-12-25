<?php
/**
 * notices 테이블에서 is_published 필드 제거 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/database/remove_notice_is_published.php
 * 
 * 이 필드는 항상 1로 설정되었고, 실제 발행 여부는 publish_start_at과 publish_end_at으로 관리됩니다.
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('<h2 style="color: red;">데이터베이스 연결에 실패했습니다.</h2>');
}

try {
    echo '<h2>notices 테이블에서 is_published 필드 제거</h2>';
    echo '<style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .warning { color: orange; font-weight: bold; }
    </style>';
    
    // 컬럼 존재 여부 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM notices LIKE 'is_published'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        // is_published 컬럼 제거
        $pdo->exec("ALTER TABLE notices DROP COLUMN is_published");
        echo '<h3>작업 결과:</h3>';
        echo '<ul>';
        echo '<li><span class="success">✓ is_published 필드가 성공적으로 제거되었습니다.</span></li>';
        echo '</ul>';
        echo '<p class="info">이제 공지사항의 발행 여부는 publish_start_at과 publish_end_at 필드로만 관리됩니다.</p>';
    } else {
        echo '<h3>작업 결과:</h3>';
        echo '<ul>';
        echo '<li><span class="info">- is_published 필드가 이미 존재하지 않습니다.</span></li>';
        echo '</ul>';
    }
    
    echo '<p><a href="/MVNO/admin/content/notice-manage.php" style="display: inline-block; padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px;">공지사항 관리 페이지로 이동</a></p>';
    
} catch (PDOException $e) {
    echo '<h2 class="error">오류 발생: ' . htmlspecialchars($e->getMessage()) . '</h2>';
    echo '<p class="warning">주의: 다른 테이블이나 외래키 제약조건이 있을 수 있습니다.</p>';
}
?>




