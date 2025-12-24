<?php
/**
 * notices 테이블에 image_url과 link_url 필드 추가 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/database/add_notice_image_link.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('<h2 style="color: red;">데이터베이스 연결에 실패했습니다.</h2>');
}

try {
    // 컬럼 존재 여부 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM notices LIKE 'image_url'");
    $imageColumnExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM notices LIKE 'link_url'");
    $linkColumnExists = $stmt->rowCount() > 0;
    
    $messages = [];
    
    if (!$imageColumnExists) {
        $pdo->exec("ALTER TABLE notices ADD COLUMN image_url VARCHAR(500) DEFAULT NULL COMMENT '공지사항 이미지 URL' AFTER content");
        $messages[] = 'image_url 필드가 성공적으로 추가되었습니다.';
    } else {
        $messages[] = 'image_url 필드가 이미 존재합니다.';
    }
    
    if (!$linkColumnExists) {
        $pdo->exec("ALTER TABLE notices ADD COLUMN link_url VARCHAR(500) DEFAULT NULL COMMENT '공지사항 링크 URL' AFTER image_url");
        $messages[] = 'link_url 필드가 성공적으로 추가되었습니다.';
    } else {
        $messages[] = 'link_url 필드가 이미 존재합니다.';
    }
    
    echo '<h2 style="color: green;">작업 완료</h2>';
    foreach ($messages as $msg) {
        echo '<p>' . htmlspecialchars($msg) . '</p>';
    }
    
    echo '<p><a href="/MVNO/admin/content/notice-manage.php">공지사항 관리 페이지로 이동</a></p>';
    
} catch (PDOException $e) {
    echo '<h2 style="color: red;">오류 발생: ' . htmlspecialchars($e->getMessage()) . '</h2>';
}
?>

