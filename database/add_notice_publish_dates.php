<?php
/**
 * notices 테이블에 publish_start_at과 publish_end_at 필드 추가 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/database/add_notice_publish_dates.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('<h2 style="color: red;">데이터베이스 연결에 실패했습니다.</h2>');
}

try {
    echo '<h2>notices 테이블에 발행 기간 필드 추가</h2>';
    echo '<style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
    </style>';
    
    // 컬럼 존재 여부 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM notices LIKE 'publish_start_at'");
    $startColumnExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM notices LIKE 'publish_end_at'");
    $endColumnExists = $stmt->rowCount() > 0;
    
    $messages = [];
    
    if (!$startColumnExists) {
        $pdo->exec("ALTER TABLE notices ADD COLUMN publish_start_at DATE DEFAULT NULL COMMENT '발행 시작일' AFTER is_published");
        $messages[] = '<span class="success">✓ publish_start_at 필드가 성공적으로 추가되었습니다.</span>';
    } else {
        $messages[] = '<span class="info">- publish_start_at 필드가 이미 존재합니다.</span>';
    }
    
    if (!$endColumnExists) {
        $pdo->exec("ALTER TABLE notices ADD COLUMN publish_end_at DATE DEFAULT NULL COMMENT '발행 종료일' AFTER publish_start_at");
        $messages[] = '<span class="success">✓ publish_end_at 필드가 성공적으로 추가되었습니다.</span>';
    } else {
        $messages[] = '<span class="info">- publish_end_at 필드가 이미 존재합니다.</span>';
    }
    
    echo '<h3>작업 결과:</h3>';
    echo '<ul>';
    foreach ($messages as $msg) {
        echo '<li>' . $msg . '</li>';
    }
    echo '</ul>';
    
    echo '<p><a href="/MVNO/admin/content/notice-manage.php" style="display: inline-block; padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px;">공지사항 관리 페이지로 이동</a></p>';
    
} catch (PDOException $e) {
    echo '<h2 class="error">오류 발생: ' . htmlspecialchars($e->getMessage()) . '</h2>';
}
?>
