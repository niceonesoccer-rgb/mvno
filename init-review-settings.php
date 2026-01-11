<?php
/**
 * 리뷰 작성 권한 설정 초기화 스크립트
 * DB에 기본값을 저장합니다
 */
require_once __DIR__ . '/includes/data/db-config.php';

$pdo = getDBConnection();
if (!$pdo) {
    die('DB 연결 실패');
}

// 기본값 설정 (접수 포함)
$defaultStatuses = ['received', 'activating', 'on_hold', 'cancelled', 'activation_completed', 'installation_completed', 'closed'];
// 또는 기본값만
// $defaultStatuses = ['activation_completed', 'installation_completed', 'closed'];

$jsonValue = json_encode($defaultStatuses, JSON_UNESCAPED_UNICODE);

try {
    // system_settings 테이블 확인 및 생성 (없는 경우)
    $stmt = $pdo->query("SHOW TABLES LIKE 'system_settings'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `system_settings` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `setting_key` VARCHAR(100) NOT NULL COMMENT '설정 키',
                `setting_value` TEXT NOT NULL COMMENT '설정 값',
                `setting_type` ENUM('string', 'number', 'boolean', 'json') NOT NULL DEFAULT 'string',
                `description` VARCHAR(255) DEFAULT NULL COMMENT '설명',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_setting_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 설정'
        ");
        echo "<p>system_settings 테이블이 생성되었습니다.</p>";
    }
    
    // DB에 저장
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
        VALUES ('review_allowed_statuses', :value, 'json', '리뷰 작성 가능한 진행상황 목록')
        ON DUPLICATE KEY UPDATE
            setting_value = :value2,
            updated_at = NOW()
    ");
    $stmt->execute([
        ':value' => $jsonValue,
        ':value2' => $jsonValue
    ]);
    
    echo "<h2>리뷰 작성 권한 설정 초기화 완료</h2>";
    echo "<p>저장된 값:</p>";
    echo "<pre>";
    print_r($defaultStatuses);
    echo "</pre>";
    echo "<p>JSON 값: " . htmlspecialchars($jsonValue) . "</p>";
    
    // 확인
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'review_allowed_statuses' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $decoded = json_decode($row['setting_value'], true);
        echo "<p><strong>확인:</strong> DB에 저장된 값</p>";
        echo "<pre>";
        print_r($decoded);
        echo "</pre>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
