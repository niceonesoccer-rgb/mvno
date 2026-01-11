<?php
/**
 * 리뷰 작성 권한 설정 관리자 페이지
 * 관리자가 진행상황에 따라 리뷰 작성 권한을 설정할 수 있는 페이지
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/path-config.php';
require_once __DIR__ . '/../includes/data/db-config.php';

// 관리자 인증 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    header('Location: ' . getAssetPath('/admin/login.php'));
    exit;
}

// DB 연결
$pdo = getDBConnection();
if (!$pdo) {
    $message = '데이터베이스 연결에 실패했습니다.';
    $messageType = 'error';
}

// POST 요청 처리 (설정 저장)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings']) && $pdo) {
    $allowedStatuses = $_POST['allowed_statuses'] ?? [];
    
    // 빈 배열 체크 - 하나도 선택하지 않으면 오류
    if (empty($allowedStatuses)) {
        $message = '최소 하나 이상의 진행상황을 선택해야 합니다.';
        $messageType = 'error';
        $allowedStatuses = []; // 저장하지 않음
    }
    
    // 디버깅: 저장할 값 확인
    error_log("리뷰 설정 저장 - POST 데이터: " . print_r($_POST['allowed_statuses'] ?? [], true));
    error_log("리뷰 설정 저장 - 저장할 값: " . print_r($allowedStatuses, true));
    
    // 빈 배열이 아닌 경우에만 저장
    if (!empty($allowedStatuses)) {
        // 배열을 JSON으로 변환
        $jsonValue = json_encode($allowedStatuses, JSON_UNESCAPED_UNICODE);
        
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
        
            $success_message = '설정이 저장되었습니다.';
        } catch (PDOException $e) {
            error_log("리뷰 설정 저장 오류: " . $e->getMessage());
            $message = '설정 저장에 실패했습니다: ' . htmlspecialchars($e->getMessage());
            $messageType = 'error';
        }
    }
}

// 현재 설정 읽기 (DB에서만 - 하드코딩 제거)
$allowedStatuses = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'review_allowed_statuses' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['setting_value'])) {
            $decoded = json_decode($row['setting_value'], true);
            if (is_array($decoded) && !empty($decoded)) {
                // 빈 배열이 아닌 경우에만 사용
                $allowedStatuses = $decoded;
            } else {
                // JSON 파싱 실패 또는 빈 배열
                error_log("리뷰 설정 읽기: JSON 파싱 실패 또는 빈 배열 - " . $row['setting_value']);
            }
        } else {
            // DB에 설정이 없음
            error_log("리뷰 설정 읽기: DB에 설정이 없음");
        }
    } catch (PDOException $e) {
        error_log("리뷰 설정 읽기 오류: " . $e->getMessage());
    }
}

// 진행상황 옵션
$statusOptions = [
    'received' => '접수',
    'activating' => '개통중',
    'on_hold' => '보류',
    'cancelled' => '취소',
    'activation_completed' => '개통완료',
    'installation_completed' => '설치완료',
    'closed' => '종료'
];

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-container" style="margin-top: 80px; max-width: 700px; margin-left: auto; margin-right: auto;">
    <h1 style="text-align: center; margin-bottom: 32px;">리뷰 작성 권한 설정</h1>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; background: #d1fae5; color: #065f46; border: 1px solid #10b981;">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($message) && isset($messageType)): ?>
        <div class="alert alert-<?php echo $messageType === 'error' ? 'error' : 'warning'; ?>" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; background: <?php echo $messageType === 'error' ? '#fee2e2' : '#fef3c7'; ?>; color: <?php echo $messageType === 'error' ? '#991b1b' : '#92400e'; ?>; border: 1px solid <?php echo $messageType === 'error' ? '#f87171' : '#fbbf24'; ?>;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($allowedStatuses)): ?>
        <div class="alert alert-warning" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; background: #fef3c7; color: #92400e; border: 1px solid #fbbf24;">
            <strong>주의:</strong> 현재 DB에 설정이 없습니다. 아래에서 진행상황을 선택하고 저장해주세요.
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <!-- 리뷰 작성 권한 설정 -->
        <div class="settings-section" style="margin-bottom: 32px; padding-bottom: 32px; border-bottom: 1px solid #e5e7eb;">
            <h2 class="settings-section-title" style="font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 16px; text-align: center;">리뷰 작성 가능한 진행상황</h2>
            
            <div class="form-group" style="margin-bottom: 24px;">
                <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 12px; text-align: center;">
                    다음 진행상황에서 리뷰를 작성할 수 있습니다:
                </label>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 20px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; max-width: 500px; margin: 0 auto;">
                    <?php 
                    $currentAllowedStatuses = $allowedStatuses;
                    foreach ($statusOptions as $statusValue => $statusLabel): 
                    ?>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px; border-radius: 6px; transition: background 0.2s; <?php echo in_array($statusValue, $currentAllowedStatuses) ? 'background: #eef2ff;' : ''; ?>">
                            <input 
                                type="checkbox" 
                                name="allowed_statuses[]" 
                                value="<?php echo htmlspecialchars($statusValue); ?>"
                                <?php echo in_array($statusValue, $currentAllowedStatuses) ? 'checked' : ''; ?>
                                style="width: 18px; height: 18px; cursor: pointer; flex-shrink: 0;"
                            >
                            <span style="font-size: 14px; color: #374151;">
                                <?php echo htmlspecialchars($statusLabel); ?>
                                <span style="color: #6b7280; font-size: 12px;">(<?php echo htmlspecialchars($statusValue); ?>)</span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-help" style="font-size: 13px; color: #6b7280; margin-top: 16px; text-align: center;">
                    선택한 진행상황의 주문에 대해서만 마이페이지에서 리뷰 작성 버튼이 표시됩니다.
                </div>
            </div>
        </div>
        
        <!-- 저장 버튼 -->
        <div style="display: flex; gap: 12px; justify-content: center; margin-bottom: 32px;">
            <button type="submit" name="save_settings" class="btn btn-primary" style="padding: 12px 32px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; background: #6366f1; color: white;">
                설정 저장
            </button>
        </div>
    </form>
    
    <!-- 안내 섹션 -->
    <div class="settings-section" style="margin-top: 32px;">
        <h2 class="settings-section-title" style="font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 16px; text-align: center;">설정 안내</h2>
        <div style="padding: 20px; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #3b82f6; max-width: 600px; margin: 0 auto;">
            <p style="color: #1e40af; font-size: 14px; margin-bottom: 12px; text-align: center;">
                <strong>리뷰 작성 권한 설정 방법:</strong>
            </p>
            <ul style="color: #1e40af; font-size: 14px; margin-left: 20px; line-height: 1.8;">
                <li>위의 체크박스에서 리뷰 작성이 가능한 진행상황을 선택합니다.</li>
                <li>여러 진행상황을 선택할 수 있습니다.</li>
                <li>선택한 진행상황의 주문에 대해서만 마이페이지에서 "리뷰쓰기" 버튼이 표시됩니다.</li>
                <li>기본값은 "개통완료" 상태입니다.</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>




















