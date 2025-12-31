<?php
/**
 * 광고 가격 설정 페이지 (관리자)
 * 경로: /admin/advertisement/prices.php
 * 
 * 로테이션 시간별 광고일수에 대한 금액 설정
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
$currentTab = $_GET['tab'] ?? 'mno_sim';

// 로테이션 시간 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_rotation_duration'])) {
    $durationSeconds = intval($_POST['rotation_duration'] ?? 0);
    
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
                VALUES ('advertisement_rotation_duration', :value1, 'number', '광고 로테이션 시간(초)')
                ON DUPLICATE KEY UPDATE
                    setting_value = :value2,
                    updated_at = NOW()
            ");
            $stmt->execute([
                ':value1' => strval($durationSeconds),
                ':value2' => strval($durationSeconds)
            ]);
            $success = '로테이션 시간이 저장되었습니다.';
        } catch (PDOException $e) {
            error_log('Rotation duration save error: ' . $e->getMessage());
            $error = '로테이션 시간 저장 중 오류가 발생했습니다.';
        }
    }
}

// 가격 저장/수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $productType = $_POST['product_type'] ?? '';
        $advertisementDays = intval($_POST['advertisement_days'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $priceId = intval($_POST['price_id'] ?? 0);
        
        if (empty($productType) || $advertisementDays <= 0 || $price <= 0) {
            $error = '모든 필드를 올바르게 입력해주세요.';
        } else {
            try {
                if ($priceId > 0) {
                    // 수정
                    $stmt = $pdo->prepare("
                        UPDATE rotation_advertisement_prices 
                        SET price = :price, is_active = :is_active
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':price' => $price,
                        ':is_active' => $isActive,
                        ':id' => $priceId
                    ]);
                    $success = '가격이 수정되었습니다.';
                } else {
                    // 추가
                    $stmt = $pdo->prepare("
                        INSERT INTO rotation_advertisement_prices 
                        (product_type, advertisement_days, price, is_active)
                        VALUES (:product_type, :advertisement_days, :price, :is_active)
                        ON DUPLICATE KEY UPDATE
                            price = VALUES(price),
                            is_active = VALUES(is_active)
                    ");
                    $stmt->execute([
                        ':product_type' => $productType,
                        ':advertisement_days' => $advertisementDays,
                        ':price' => $price,
                        ':is_active' => $isActive
                    ]);
                    $success = '가격이 저장되었습니다.';
                }
                $currentTab = $productType;
            } catch (PDOException $e) {
                error_log('Price save error: ' . $e->getMessage());
                $error = '가격 저장 중 오류가 발생했습니다.';
            }
        }
    } elseif ($action === 'delete') {
        $priceId = intval($_POST['price_id'] ?? 0);
        
        if ($priceId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM rotation_advertisement_prices WHERE id = :id");
                $stmt->execute([':id' => $priceId]);
                $success = '가격이 삭제되었습니다.';
            } catch (PDOException $e) {
                error_log('Price delete error: ' . $e->getMessage());
                $error = '가격 삭제 중 오류가 발생했습니다.';
            }
        }
    }
}

// system_settings에서 로테이션 시간 조회 (단일 값)
$rotationDuration = 30; // 기본값
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'advertisement_rotation_duration'");
    $stmt->execute();
    $durationValue = $stmt->fetchColumn();
    if ($durationValue) {
        $rotationDuration = intval($durationValue);
    }
} catch (PDOException $e) {
    // 설정이 없으면 기본값 사용
    error_log('Rotation duration 조회 오류: ' . $e->getMessage());
}

$productTypes = [
    'mno_sim' => '통신사단독유심',
    'mvno' => '알뜰폰',
    'mno' => '통신사폰',
    'internet' => '인터넷'
];

$advertisementDaysOptions = [1, 2, 3, 5, 7, 10, 14, 30];

// 현재 탭의 가격 목록 조회
$stmt = $pdo->prepare("
    SELECT * FROM rotation_advertisement_prices 
    WHERE product_type = :product_type
    ORDER BY advertisement_days ASC
");
$stmt->execute([':product_type' => $currentTab]);
$prices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 가격을 배열로 재구성 (advertisement_days => price data)
$priceMap = [];
foreach ($prices as $price) {
    $priceMap[$price['advertisement_days']] = $price;
}
?>

<div class="admin-content-wrapper">
    <div class="admin-content">
        <div class="page-header">
            <h1>광고 가격 설정</h1>
            <p>카테고리별, 광고일수별 광고 가격을 설정합니다.</p>
        </div>
        
        <div class="content-box">
            <div style="padding: 24px;">
                <?php if ($error): ?>
                    <div style="padding: 12px; background: #fee2e2; color: #991b1b; border-radius: 6px; margin-bottom: 20px;">
                        <?= $error ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div style="padding: 12px; background: #d1fae5; color: #065f46; border-radius: 6px; margin-bottom: 20px;">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <!-- 로테이션 시간 설정 -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                    <form method="POST" style="display: flex; gap: 12px; align-items: flex-end;">
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 14px;">
                                로테이션 시간 (초)
                            </label>
                            <input type="number" name="rotation_duration" value="<?= htmlspecialchars($rotationDuration) ?>" required min="1" step="1"
                                   style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; box-sizing: border-box;"
                                   placeholder="예: 30 (30초), 60 (1분)">
                        </div>
                        <button type="submit" name="save_rotation_duration" value="1"
                                style="padding: 10px 24px; background: #6366f1; color: #fff; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; white-space: nowrap; height: 42px;">
                            로테이션 시간 저장
                        </button>
                    </form>
                    <div style="font-size: 13px; color: #6b7280; margin-top: 8px;">
                        현재 로테이션 시간: <strong><?= $rotationDuration ?>초</strong>
                        <?php if ($rotationDuration >= 60): ?>
                            (<?= ($rotationDuration / 60) ?>분)
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 탭 -->
                <div style="margin-bottom: 24px;">
                    <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                        <?php foreach ($productTypes as $type => $label): ?>
                            <a href="?tab=<?= $type ?>" 
                               class="tab-button <?= $currentTab === $type ? 'active' : '' ?>" 
                               style="padding: 10px 20px; border: 1px solid #e2e8f0; background: <?= $currentTab === $type ? '#6366f1' : '#fff' ?>; color: <?= $currentTab === $type ? '#fff' : '#1e293b' ?>; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; transition: all 0.2s;">
                                <?= $label ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 가격 설정 테이블 -->
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden;">
                        <thead>
                            <tr style="background: #f1f5f9;">
                                <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">
                                    로테이션 시간
                                </th>
                                <?php foreach ($advertisementDaysOptions as $days): ?>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;"><?= $days ?>일</th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 12px; text-align: center; font-weight: 500; background: #f8fafc;">
                                    <?php
                                    if ($rotationDuration < 60) {
                                        echo $rotationDuration . '초';
                                    } else {
                                        echo ($rotationDuration / 60) . '분';
                                    }
                                    ?>
                                </td>
                                <?php foreach ($advertisementDaysOptions as $days): ?>
                                    <td style="padding: 12px; text-align: center;">
                                        <?php
                                        $priceData = $priceMap[$days] ?? null;
                                        if ($priceData):
                                            $isActive = $priceData['is_active'];
                                        ?>
                                            <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                                                <div style="font-weight: 600; color: <?= $isActive ? '#1e293b' : '#94a3b8' ?>;">
                                                    <?= number_format($priceData['price'], 0) ?>원
                                                </div>
                                                <div style="display: flex; gap: 4px;">
                                                    <button type="button" onclick="editPrice(<?= $priceData['id'] ?>, '<?= $currentTab ?>', <?= $days ?>, <?= $priceData['price'] ?>, <?= $isActive ?>)" 
                                                            style="padding: 4px 8px; background: #3b82f6; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 11px;">
                                                        수정
                                                    </button>
                                                    <button type="button" onclick="deletePrice(<?= $priceData['id'] ?>)" 
                                                            style="padding: 4px 8px; background: #ef4444; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 11px;">
                                                        삭제
                                                    </button>
                                                </div>
                                                <?php if (!$isActive): ?>
                                                    <span style="font-size: 11px; color: #ef4444;">비활성</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <button type="button" onclick="addPrice('<?= $currentTab ?>', <?= $days ?>)" 
                                                    style="padding: 6px 12px; background: #f1f5f9; color: #64748b; border: 1px dashed #cbd5e1; border-radius: 4px; cursor: pointer; font-size: 12px;">
                                                추가
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 가격 추가/수정 모달 -->
<div id="priceModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 32px; width: 90%; max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 600;" id="modalTitle">가격 설정</h2>
            <button type="button" onclick="closePriceModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        
        <form method="POST" id="priceForm">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="price_id" id="priceId">
            <input type="hidden" name="product_type" id="productType">
            <input type="hidden" name="advertisement_days" id="advertisementDays">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    카테고리
                </label>
                <input type="text" id="productTypeLabel" readonly
                       style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box; background: #f9fafb;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    광고 기간 (일)
                </label>
                <input type="text" id="advertisementDaysLabel" readonly
                       style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box; background: #f9fafb;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    가격 (원) <span style="color: #ef4444;">*</span>
                </label>
                <input type="number" name="price" id="price" required min="0" step="100"
                       style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;"
                       placeholder="가격을 입력하세요">
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" id="isActive" checked 
                           style="width: 18px; height: 18px;">
                    <span style="font-weight: 600; color: #374151;">활성화</span>
                </label>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="submit" style="flex: 1; padding: 12px 24px; background: #6366f1; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                    저장
                </button>
                <button type="button" onclick="closePriceModal()" style="flex: 1; padding: 12px 24px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                    취소
                </button>
            </div>
        </form>
    </div>
</div>

<form method="POST" id="deleteForm" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="price_id" id="deletePriceId">
</form>

<script>
const productTypeLabels = {
    'mno_sim': '통신사단독유심',
    'mvno': '알뜰폰',
    'mno': '통신사폰',
    'internet': '인터넷'
};

function addPrice(productType, advertisementDays) {
    document.getElementById('priceId').value = '';
    document.getElementById('productType').value = productType;
    document.getElementById('advertisementDays').value = advertisementDays;
    document.getElementById('price').value = '';
    document.getElementById('isActive').checked = true;
    
    document.getElementById('productTypeLabel').value = productTypeLabels[productType];
    document.getElementById('advertisementDaysLabel').value = advertisementDays + '일';
    
    document.getElementById('modalTitle').textContent = '가격 추가';
    document.getElementById('priceModal').style.display = 'flex';
}

function editPrice(priceId, productType, advertisementDays, price, isActive) {
    document.getElementById('priceId').value = priceId;
    document.getElementById('productType').value = productType;
    document.getElementById('advertisementDays').value = advertisementDays;
    document.getElementById('price').value = price;
    document.getElementById('isActive').checked = isActive == 1;
    
    document.getElementById('productTypeLabel').value = productTypeLabels[productType];
    document.getElementById('advertisementDaysLabel').value = advertisementDays + '일';
    
    document.getElementById('modalTitle').textContent = '가격 수정';
    document.getElementById('priceModal').style.display = 'flex';
}

function closePriceModal() {
    document.getElementById('priceModal').style.display = 'none';
}

function deletePrice(priceId) {
    if (confirm('정말 이 가격 설정을 삭제하시겠습니까?')) {
        document.getElementById('deletePriceId').value = priceId;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
