<?php
/**
 * 이메일 인증 테이블 생성 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/database/create_email_verification_table.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>이메일 인증 테이블 생성</title>
    <style>
        body { font-family: 'Malgun Gothic', Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .info { background: #dbeafe; border: 1px solid #3b82f6; color: #1e40af; padding: 15px; border-radius: 8px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>이메일 인증 테이블 생성</h1>
    
<?php
try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        echo '<div class="error">데이터베이스 연결에 실패했습니다.</div>';
        exit;
    }
    
    // 테이블 생성 SQL
    $sql = "
    CREATE TABLE IF NOT EXISTS `email_verifications` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` VARCHAR(50) NOT NULL COMMENT '사용자 ID',
        `email` VARCHAR(100) NOT NULL COMMENT '인증할 이메일 주소',
        `verification_code` VARCHAR(10) NOT NULL COMMENT '인증번호 (6자리)',
        `verification_token` VARCHAR(64) NOT NULL COMMENT '인증 토큰 (링크용)',
        `type` ENUM('email_change', 'password_change') NOT NULL COMMENT '인증 타입',
        `status` ENUM('pending', 'verified', 'expired') NOT NULL DEFAULT 'pending' COMMENT '인증 상태',
        `expires_at` DATETIME NOT NULL COMMENT '만료 시간 (30분)',
        `verified_at` DATETIME DEFAULT NULL COMMENT '인증 완료 시간',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
        PRIMARY KEY (`id`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_email` (`email`),
        KEY `idx_verification_code` (`verification_code`),
        KEY `idx_verification_token` (`verification_token`),
        KEY `idx_status` (`status`),
        KEY `idx_expires_at` (`expires_at`),
        KEY `idx_type` (`type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='이메일 인증 정보';
    ";
    
    $pdo->exec($sql);
    
    // 테이블 생성 확인
    $stmt = $pdo->query("SHOW TABLES LIKE 'email_verifications'");
    if ($stmt->rowCount() > 0) {
        echo '<div class="success">✅ 이메일 인증 테이블이 성공적으로 생성되었습니다!</div>';
        echo '<div class="info">이제 마이페이지에서 이메일 인증번호 발송이 가능합니다.</div>';
        echo '<p><a href="/MVNO/mypage/account-management.php">계정 설정으로 돌아가기</a></p>';
    } else {
        echo '<div class="error">❌ 테이블 생성에 실패했습니다.</div>';
    }
    
} catch (PDOException $e) {
    echo '<div class="error">오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
</body>
</html>






