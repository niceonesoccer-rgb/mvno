<?php
/**
 * 포인트 설정 관리자 페이지
 * 관리자가 포인트 관련 설정을 변경할 수 있는 페이지
 */

require_once __DIR__ . '/../../includes/data/path-config.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

// 관리자 권한 체크
if (!isAdmin()) {
    header('Location: ' . getAssetPath('/admin/index.php'));
    exit;
}

$pdo = getDBConnection();
$error = '';
$success = '';

// 설정 가져오기 함수
function getPointSetting($key, $default = 0) {
    global $pdo;
    if (!$pdo) {
        return $default;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1");
        $stmt->execute([':key' => $key]);
        $result = $stmt->fetchColumn();
        $value = $result !== false ? intval($result) : $default;
        return $value;
    } catch (PDOException $e) {
        error_log("getPointSetting error: " . $e->getMessage() . " (key={$key})");
        return $default;
    }
}

// 설정 저장 함수
function savePointSetting($key, $value, $description = '') {
    global $pdo;
    if (!$pdo) {
        error_log("savePointSetting: DB 연결 실패 (key={$key}, value={$value})");
        return false;
    }
    
    try {
        // VALUES() 함수를 사용하여 ON DUPLICATE KEY UPDATE에서 파라미터 중복 문제 해결
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
            VALUES (:key, :value, 'number', :description)
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                description = VALUES(description),
                updated_at = NOW()
        ");
        $result = $stmt->execute([
            ':key' => $key,
            ':value' => (string)$value,
            ':description' => $description
        ]);
        
        if ($result) {
            error_log("savePointSetting 성공: key={$key}, value={$value}, description={$description}");
        } else {
            error_log("savePointSetting 실패: key={$key}, value={$value}");
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("savePointSetting error: " . $e->getMessage() . " (key={$key}, value={$value})");
        return false;
    }
}

// POST 요청 처리 (설정 저장)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    try {
        $pdo->beginTransaction();
        
        // 회원가입 축하 포인트
        $signupPoint = isset($_POST['signup_point']) ? intval($_POST['signup_point']) : 0;
        $signupPointEnabled = isset($_POST['signup_point_enabled']) && $_POST['signup_point_enabled'] == '1' ? 1 : 0;
        
        $result1 = savePointSetting('point_signup_amount', $signupPoint, '회원가입 시 지급되는 포인트');
        $result2 = savePointSetting('point_signup_enabled', $signupPointEnabled, '회원가입 포인트 지급 활성화 여부');
        
        // 상품 조회 포인트
        $viewPoint = isset($_POST['view_point']) ? intval($_POST['view_point']) : 0;
        $viewPointEnabled = isset($_POST['view_point_enabled']) && $_POST['view_point_enabled'] == '1' ? 1 : 0;
        
        $result3 = savePointSetting('point_view_amount', $viewPoint, '상품 조회 시 지급되는 포인트');
        $result4 = savePointSetting('point_view_enabled', $viewPointEnabled, '상품 조회 포인트 지급 활성화 여부');
        
        // 가입 신청 포인트 (알뜰폰)
        $mvnoApplicationPoint = isset($_POST['mvno_application_point']) ? intval($_POST['mvno_application_point']) : 0;
        $mvnoApplicationEnabled = isset($_POST['mvno_application_enabled']) && $_POST['mvno_application_enabled'] == '1' ? 1 : 0;
        $result5 = savePointSetting('point_application_mvno_amount', $mvnoApplicationPoint, '알뜰폰 신청 시 지급되는 포인트');
        $result6 = savePointSetting('point_application_mvno_enabled', $mvnoApplicationEnabled, '알뜰폰 신청 포인트 지급 활성화 여부');
        
        // 가입 신청 포인트 (통신사폰)
        $mnoApplicationPoint = isset($_POST['mno_application_point']) ? intval($_POST['mno_application_point']) : 0;
        $mnoApplicationEnabled = isset($_POST['mno_application_enabled']) && $_POST['mno_application_enabled'] == '1' ? 1 : 0;
        $result7 = savePointSetting('point_application_mno_amount', $mnoApplicationPoint, '통신사폰 신청 시 지급되는 포인트');
        $result8 = savePointSetting('point_application_mno_enabled', $mnoApplicationEnabled, '통신사폰 신청 포인트 지급 활성화 여부');
        
        // 가입 신청 포인트 (통신사단독유심)
        $mnoSimApplicationPoint = isset($_POST['mno_sim_application_point']) ? intval($_POST['mno_sim_application_point']) : 0;
        $mnoSimApplicationEnabled = isset($_POST['mno_sim_application_enabled']) && $_POST['mno_sim_application_enabled'] == '1' ? 1 : 0;
        $result9 = savePointSetting('point_application_mno_sim_amount', $mnoSimApplicationPoint, '통신사단독유심 신청 시 지급되는 포인트');
        $result10 = savePointSetting('point_application_mno_sim_enabled', $mnoSimApplicationEnabled, '통신사단독유심 신청 포인트 지급 활성화 여부');
        
        // 가입 신청 포인트 (인터넷)
        $internetApplicationPoint = isset($_POST['internet_application_point']) ? intval($_POST['internet_application_point']) : 0;
        $internetApplicationEnabled = isset($_POST['internet_application_enabled']) && $_POST['internet_application_enabled'] == '1' ? 1 : 0;
        $result11 = savePointSetting('point_application_internet_amount', $internetApplicationPoint, '인터넷 신청 시 지급되는 포인트');
        $result12 = savePointSetting('point_application_internet_enabled', $internetApplicationEnabled, '인터넷 신청 포인트 지급 활성화 여부');
        
        if ($result1 && $result2 && $result3 && $result4 && $result5 && $result6 && $result7 && $result8 && $result9 && $result10 && $result11 && $result12) {
            $pdo->commit();
            
            // POST-Redirect-GET 패턴으로 중복 제출 방지 및 값 확인
            header('Location: ' . $_SERVER['PHP_SELF'] . '?saved=1');
            exit;
        } else {
            $pdo->rollBack();
            $error = '설정 저장에 실패했습니다. (결과: ' . ($result1 ? '1' : '0') . ', ' . ($result2 ? '1' : '0') . ', ' . ($result3 ? '1' : '0') . ', ' . ($result4 ? '1' : '0') . ')';
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('포인트 설정 저장 오류: ' . $e->getMessage());
        $error = '설정 저장 중 오류가 발생했습니다: ' . $e->getMessage();
    }
}

// 현재 설정 읽기
$signupPoint = getPointSetting('point_signup_amount', 0);
$signupPointEnabled = getPointSetting('point_signup_enabled', 0);
$viewPoint = getPointSetting('point_view_amount', 0);
$viewPointEnabled = getPointSetting('point_view_enabled', 0);

// 가입 신청 포인트 설정
$mvnoApplicationPoint = getPointSetting('point_application_mvno_amount', 0);
$mvnoApplicationEnabled = getPointSetting('point_application_mvno_enabled', 0);
$mnoApplicationPoint = getPointSetting('point_application_mno_amount', 0);
$mnoApplicationEnabled = getPointSetting('point_application_mno_enabled', 0);
$mnoSimApplicationPoint = getPointSetting('point_application_mno_sim_amount', 0);
$mnoSimApplicationEnabled = getPointSetting('point_application_mno_sim_enabled', 0);
$internetApplicationPoint = getPointSetting('point_application_internet_amount', 0);
$internetApplicationEnabled = getPointSetting('point_application_internet_enabled', 0);


// 현재 페이지 설정
$currentPage = 'point-settings.php';

// 헤더 포함
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="page-header" style="margin-bottom: 32px;">
        <h1 style="font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">포인트 설정</h1>
        <p style="font-size: 16px; color: #6b7280;">일반회원 포인트 자동 지급 설정을 관리합니다.</p>
    </div>
    
    <?php if ($error): ?>
        <div style="padding: 16px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 24px; border: 1px solid #ef4444;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['saved'])): ?>
        <div style="padding: 16px; background: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 24px; border: 1px solid #10b981;">
            설정이 저장되었습니다.
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div style="padding: 16px; background: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 24px; border: 1px solid #10b981;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" style="background: white; border-radius: 12px; padding: 32px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;">
        <!-- 회원가입 축하 포인트 설정 -->
        <div style="margin-bottom: 40px; padding-bottom: 32px; border-bottom: 1px solid #e5e7eb;">
            <h2 style="font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 24px;">1. 회원가입 축하 포인트</h2>
            
            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 12px; font-size: 16px; font-weight: 600; color: #374151; cursor: pointer;">
                    <input type="checkbox" name="signup_point_enabled" value="1" <?= $signupPointEnabled ? 'checked' : '' ?> 
                           style="width: 20px; height: 20px; cursor: pointer;">
                    <span>회원가입 시 자동으로 포인트 지급</span>
                </label>
                <div style="font-size: 13px; color: #6b7280; margin-top: 8px; margin-left: 32px;">
                    체크하면 회원가입 시 자동으로 설정한 포인트가 지급됩니다.
                </div>
            </div>
            
            <div style="margin-left: 32px;">
                <label for="signup_point" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    지급 포인트 (원)
                </label>
                <input 
                    type="number" 
                    id="signup_point" 
                    name="signup_point" 
                    value="<?= htmlspecialchars($signupPoint) ?>"
                    min="0"
                    step="1"
                    required
                    style="width: 200px; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; transition: border-color 0.2s;"
                    onfocus="this.style.borderColor='#6366f1'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)';"
                    onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                >
                <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">
                    회원가입 시 지급할 포인트 금액을 입력하세요.
                </div>
            </div>
        </div>
        
        <!-- 상품 조회 포인트 설정 -->
        <div style="margin-bottom: 40px; padding-bottom: 32px; border-bottom: 1px solid #e5e7eb;">
            <h2 style="font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 24px;">2. 상품 조회 포인트</h2>
            
            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 12px; font-size: 16px; font-weight: 600; color: #374151; cursor: pointer;">
                    <input type="checkbox" name="view_point_enabled" value="1" <?= $viewPointEnabled ? 'checked' : '' ?> 
                           style="width: 20px; height: 20px; cursor: pointer;">
                    <span>상품 조회 시 자동으로 포인트 지급</span>
                </label>
                <div style="font-size: 13px; color: #6b7280; margin-top: 8px; margin-left: 32px;">
                    체크하면 상품 상세 페이지를 조회할 때마다 설정한 포인트가 지급됩니다.
                </div>
            </div>
            
            <div style="margin-left: 32px;">
                <label for="view_point" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    조회당 지급 포인트 (원)
                </label>
                <input 
                    type="number" 
                    id="view_point" 
                    name="view_point" 
                    value="<?= htmlspecialchars($viewPoint) ?>"
                    min="0"
                    step="1"
                    required
                    style="width: 200px; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; transition: border-color 0.2s;"
                    onfocus="this.style.borderColor='#6366f1'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)';"
                    onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                >
                <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">
                    상품을 조회할 때마다 지급할 포인트 금액을 입력하세요.
                </div>
            </div>
        </div>
        
        <!-- 가입 신청 포인트 설정 -->
        <div style="margin-bottom: 40px;">
            <h2 style="font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 24px;">3. 가입 신청 포인트</h2>
            <p style="font-size: 14px; color: #6b7280; margin-bottom: 24px;">회원이 각 상품 유형별로 가입 신청을 완료했을 때 지급되는 포인트를 설정합니다.</p>
            
            <!-- 알뜰폰 신청 포인트 -->
            <div style="margin-bottom: 32px; padding: 20px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
                <div style="margin-bottom: 16px;">
                    <label style="display: flex; align-items: center; gap: 12px; font-size: 16px; font-weight: 600; color: #374151; cursor: pointer;">
                        <input type="checkbox" name="mvno_application_enabled" value="1" <?= $mvnoApplicationEnabled ? 'checked' : '' ?> 
                               style="width: 20px; height: 20px; cursor: pointer;">
                        <span>알뜰폰 신청 시 자동으로 포인트 지급</span>
                    </label>
                </div>
                <div style="margin-left: 32px;">
                    <label for="mvno_application_point" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        지급 포인트 (원)
                    </label>
                    <input 
                        type="number" 
                        id="mvno_application_point" 
                        name="mvno_application_point" 
                        value="<?= htmlspecialchars($mvnoApplicationPoint) ?>"
                        min="0"
                        step="1"
                        required
                        style="width: 200px; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px;"
                    >
                </div>
            </div>
            
            <!-- 통신사폰 신청 포인트 -->
            <div style="margin-bottom: 32px; padding: 20px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
                <div style="margin-bottom: 16px;">
                    <label style="display: flex; align-items: center; gap: 12px; font-size: 16px; font-weight: 600; color: #374151; cursor: pointer;">
                        <input type="checkbox" name="mno_application_enabled" value="1" <?= $mnoApplicationEnabled ? 'checked' : '' ?> 
                               style="width: 20px; height: 20px; cursor: pointer;">
                        <span>통신사폰 신청 시 자동으로 포인트 지급</span>
                    </label>
                </div>
                <div style="margin-left: 32px;">
                    <label for="mno_application_point" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        지급 포인트 (원)
                    </label>
                    <input 
                        type="number" 
                        id="mno_application_point" 
                        name="mno_application_point" 
                        value="<?= htmlspecialchars($mnoApplicationPoint) ?>"
                        min="0"
                        step="1"
                        required
                        style="width: 200px; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px;"
                    >
                </div>
            </div>
            
            <!-- 통신사단독유심 신청 포인트 -->
            <div style="margin-bottom: 32px; padding: 20px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
                <div style="margin-bottom: 16px;">
                    <label style="display: flex; align-items: center; gap: 12px; font-size: 16px; font-weight: 600; color: #374151; cursor: pointer;">
                        <input type="checkbox" name="mno_sim_application_enabled" value="1" <?= $mnoSimApplicationEnabled ? 'checked' : '' ?> 
                               style="width: 20px; height: 20px; cursor: pointer;">
                        <span>통신사단독유심 신청 시 자동으로 포인트 지급</span>
                    </label>
                </div>
                <div style="margin-left: 32px;">
                    <label for="mno_sim_application_point" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        지급 포인트 (원)
                    </label>
                    <input 
                        type="number" 
                        id="mno_sim_application_point" 
                        name="mno_sim_application_point" 
                        value="<?= htmlspecialchars($mnoSimApplicationPoint) ?>"
                        min="0"
                        step="1"
                        required
                        style="width: 200px; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px;"
                    >
                </div>
            </div>
            
            <!-- 인터넷 신청 포인트 -->
            <div style="margin-bottom: 32px; padding: 20px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
                <div style="margin-bottom: 16px;">
                    <label style="display: flex; align-items: center; gap: 12px; font-size: 16px; font-weight: 600; color: #374151; cursor: pointer;">
                        <input type="checkbox" name="internet_application_enabled" value="1" <?= $internetApplicationEnabled ? 'checked' : '' ?> 
                               style="width: 20px; height: 20px; cursor: pointer;">
                        <span>인터넷 신청 시 자동으로 포인트 지급</span>
                    </label>
                </div>
                <div style="margin-left: 32px;">
                    <label for="internet_application_point" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        지급 포인트 (원)
                    </label>
                    <input 
                        type="number" 
                        id="internet_application_point" 
                        name="internet_application_point" 
                        value="<?= htmlspecialchars($internetApplicationPoint) ?>"
                        min="0"
                        step="1"
                        required
                        style="width: 200px; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px;"
                    >
                </div>
            </div>
        </div>
        
        <!-- 저장 버튼 -->
        <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 24px; border-top: 1px solid #e5e7eb;">
            <button type="submit" name="save_settings" 
                    style="padding: 12px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s;"
                    onmouseover="this.style.background='#4f46e5';"
                    onmouseout="this.style.background='#6366f1';">
                설정 저장
            </button>
        </div>
    </form>
</div>

<?php
// 푸터 포함
include __DIR__ . '/../includes/admin-footer.php';
?>
