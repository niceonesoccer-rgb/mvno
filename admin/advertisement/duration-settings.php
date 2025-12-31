<?php
/**
 * 로테이션 시간 설정 페이지 (관리자)
 * 경로: /admin/advertisement/duration-settings.php
 * 
 * 로테이션 시간은 1가지만 설정 가능 (system_settings 테이블 사용)
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

$error = '';
$success = '';

// 로테이션 시간 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $durationSeconds = intval($_POST['duration_seconds'] ?? 0);
    
    if ($durationSeconds <= 0) {
        $error = '로테이션 시간을 올바르게 입력해주세요.';
    } else {
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
            }
            
            // system_settings에 로테이션 시간 저장
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
                VALUES ('advertisement_rotation_duration', :value, 'number', '광고 로테이션 시간(초)')
                ON DUPLICATE KEY UPDATE
                    setting_value = :value,
                    updated_at = NOW()
            ");
            $stmt->execute([':value' => strval($durationSeconds)]);
            $success = '로테이션 시간이 저장되었습니다.';
            
        } catch (PDOException $e) {
            error_log('Duration save error: ' . $e->getMessage());
            $error = '로테이션 시간 저장 중 오류가 발생했습니다.';
        }
    }
}

// 현재 로테이션 시간 조회
$durationSeconds = '';
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'advertisement_rotation_duration'");
    $stmt->execute();
    $durationSeconds = $stmt->fetchColumn();
} catch (PDOException $e) {
    // 테이블이 없으면 무시 (나중에 저장 시 생성됨)
}

// 기본값이 없으면 30초 표시
if (empty($durationSeconds)) {
    $durationSeconds = '30';
}
?>

<div class="admin-content-wrapper">
    <div class="admin-content">
        <div class="page-header">
            <h1>로테이션 시간 설정</h1>
            <p>광고 로테이션 시간을 설정합니다.</p>
        </div>
        
        <div class="content-box">
            <div style="padding: 24px;">
                <?php if ($error): ?>
                    <div style="padding: 12px; background: #fee2e2; color: #991b1b; border-radius: 6px; margin-bottom: 20px;">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div style="padding: 12px; background: #d1fae5; color: #065f46; border-radius: 6px; margin-bottom: 20px;">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" style="max-width: 600px;">
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px;">
                        <div style="margin-bottom: 24px;">
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 15px;">
                                로테이션 시간 (초) <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="number" name="duration_seconds" value="<?= htmlspecialchars($durationSeconds) ?>" required min="1" step="1"
                                   style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;"
                                   placeholder="예: 30 (30초), 60 (1분), 300 (5분)">
                            <div style="font-size: 13px; color: #6b7280; margin-top: 8px;">
                                초 단위로 입력하세요. (예: 30초 = 30, 1분 = 60, 5분 = 300)
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 12px;">
                            <button type="submit" style="flex: 1; padding: 12px 24px; background: #6366f1; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                                저장
                            </button>
                        </div>
                    </div>
                </form>
                
                <?php if (!empty($durationSeconds) && $durationSeconds != '30'): ?>
                    <div style="margin-top: 24px; padding: 16px; background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 4px;">
                        <div style="font-weight: 600; color: #1e40af; margin-bottom: 4px;">현재 설정</div>
                        <div style="color: #1e3a8a; font-size: 14px;">
                            로테이션 시간: <strong><?= htmlspecialchars($durationSeconds) ?>초</strong>
                            <?php
                            $seconds = intval($durationSeconds);
                            if ($seconds >= 60) {
                                echo ' (' . ($seconds / 60) . '분)';
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
