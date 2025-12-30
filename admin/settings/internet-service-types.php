<?php
/**
 * 인터넷 결합여부 설정 관리 페이지
 * 관리자가 인터넷 상품의 결합여부 옵션을 추가/삭제/수정할 수 있는 페이지
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/app-settings.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

// 관리자 권한 체크
if (!isAdmin()) {
    header('Location: /MVNO/admin/');
    exit;
}

$message = '';
$messageType = '';

// 기본 옵션 (처음 생성 시)
$defaultOptions = [
    ['value' => '인터넷', 'label' => '인터넷'],
    ['value' => '인터넷+TV', 'label' => '인터넷 + TV 결합'],
    ['value' => '인터넷+TV+핸드폰', 'label' => '인터넷 + TV + 핸드폰 결합']
];

// 옵션 추가
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $value = trim($_POST['value'] ?? '');
    $label = trim($_POST['label'] ?? '');
    
    if (empty($value) || empty($label)) {
        $message = '값과 라벨을 모두 입력해주세요.';
        $messageType = 'error';
    } else {
        $options = getAppSettings('internet_service_types', ['options' => $defaultOptions]);
        $options['options'][] = ['value' => $value, 'label' => $label];
        
        if (saveAppSettings('internet_service_types', $options, getCurrentUser()['user_id'] ?? 'admin')) {
            $message = '옵션이 추가되었습니다.';
            $messageType = 'success';
        } else {
            $message = '옵션 추가에 실패했습니다.';
            $messageType = 'error';
        }
    }
}

// 옵션 삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $index = intval($_POST['index'] ?? -1);
    
    if ($index >= 0) {
        $options = getAppSettings('internet_service_types', ['options' => $defaultOptions]);
        if (isset($options['options'][$index])) {
            array_splice($options['options'], $index, 1);
            
            if (saveAppSettings('internet_service_types', $options, getCurrentUser()['user_id'] ?? 'admin')) {
                $message = '옵션이 삭제되었습니다.';
                $messageType = 'success';
            } else {
                $message = '옵션 삭제에 실패했습니다.';
                $messageType = 'error';
            }
        }
    }
}

// 옵션 수정
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $index = intval($_POST['index'] ?? -1);
    $value = trim($_POST['value'] ?? '');
    $label = trim($_POST['label'] ?? '');
    
    if ($index >= 0 && !empty($value) && !empty($label)) {
        $options = getAppSettings('internet_service_types', ['options' => $defaultOptions]);
        if (isset($options['options'][$index])) {
            $options['options'][$index] = ['value' => $value, 'label' => $label];
            
            if (saveAppSettings('internet_service_types', $options, getCurrentUser()['user_id'] ?? 'admin')) {
                $message = '옵션이 수정되었습니다.';
                $messageType = 'success';
            } else {
                $message = '옵션 수정에 실패했습니다.';
                $messageType = 'error';
            }
        }
    }
}

// 현재 옵션 불러오기
$settings = getAppSettings('internet_service_types', ['options' => $defaultOptions]);
$serviceTypes = $settings['options'] ?? $defaultOptions;

// app_settings 테이블 자동 생성
$pdo = getDBConnection();
if ($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `app_settings` (
                `namespace` VARCHAR(100) NOT NULL,
                `json_value` TEXT NOT NULL,
                `updated_by` VARCHAR(50) DEFAULT NULL,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`namespace`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (PDOException $e) {
        error_log("Error creating app_settings table: " . $e->getMessage());
    }
}
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>인터넷 결합여부 설정</h1>
        <p>인터넷 상품 등록 시 사용할 결합여부 옵션을 관리합니다.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?>" style="margin-bottom: 24px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- 옵션 추가 폼 -->
    <div class="admin-card" style="margin-bottom: 24px;">
        <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">옵션 추가</h2>
        <form method="POST" style="display: flex; gap: 12px; align-items: flex-end;">
            <input type="hidden" name="action" value="add">
            <div style="flex: 1;">
                <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px;">값 (DB 저장값)</label>
                <input type="text" name="value" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" placeholder="예: 인터넷+TV+전화">
            </div>
            <div style="flex: 1;">
                <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px;">라벨 (표시명)</label>
                <input type="text" name="label" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" placeholder="예: 인터넷 + TV + 전화 결합">
            </div>
            <button type="submit" style="padding: 10px 24px; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">추가</button>
        </form>
    </div>

    <!-- 옵션 목록 -->
    <div class="admin-card">
        <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">현재 옵션 목록</h2>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 12px; text-align: left; font-weight: 600; width: 60px;">순서</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600;">값 및 라벨</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600;">현재 표시명</th>
                        <th style="padding: 12px; text-align: center; font-weight: 600; width: 150px;">작업</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($serviceTypes)): ?>
                        <tr>
                            <td colspan="4" style="padding: 24px; text-align: center; color: #6b7280;">등록된 옵션이 없습니다.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($serviceTypes as $index => $option): ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 12px;"><?php echo $index + 1; ?></td>
                                <td style="padding: 12px;">
                                    <form method="POST" id="edit-form-<?php echo $index; ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <input type="text" name="value" value="<?php echo htmlspecialchars($option['value']); ?>" required style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;" placeholder="값 (DB 저장값)">
                                            <input type="text" name="label" value="<?php echo htmlspecialchars($option['label']); ?>" required style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;" placeholder="라벨 (표시명)">
                                        </div>
                                    </form>
                                </td>
                                <td style="padding: 12px;">
                                    <span style="display: block; padding: 8px; color: #6b7280;"><?php echo htmlspecialchars($option['label']); ?></span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <button type="submit" form="edit-form-<?php echo $index; ?>" style="padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">수정</button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="index" value="<?php echo $index; ?>">
                                            <button type="submit" style="padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">삭제</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>








