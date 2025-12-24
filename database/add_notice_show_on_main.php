<?php
/**
 * notices 테이블에 show_on_main 필드 추가 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/database/add_notice_show_on_main.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('<h2 style="color: red;">데이터베이스 연결에 실패했습니다.</h2>');
}

try {
    // show_on_main 컬럼이 이미 있는지 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM notices LIKE 'show_on_main'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo '<h2 style="color: green;">show_on_main 필드가 이미 존재합니다.</h2>';
    } else {
        // 컬럼 추가
        $pdo->exec("ALTER TABLE notices ADD COLUMN show_on_main TINYINT(1) NOT NULL DEFAULT 0 COMMENT '메인페이지 새창 표시 여부' AFTER is_published");
        echo '<h2 style="color: green;">show_on_main 필드가 성공적으로 추가되었습니다.</h2>';
    }
    
    echo '<p><a href="/MVNO/admin/content/notice-manage.php">공지사항 관리 페이지로 이동</a></p>';
    
} catch (PDOException $e) {
    echo '<h2 style="color: red;">오류 발생: ' . htmlspecialchars($e->getMessage()) . '</h2>';
}
?>

