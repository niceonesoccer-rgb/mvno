<?php
/**
 * 개인정보 설정 관리자 페이지
 * 경로: /MVNO/admin/settings/privacy-settings.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/app-settings.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 체크
if (!isAdmin()) {
    header('Location: /MVNO/admin/');
    exit;
}

$currentUser = getCurrentUser();
$error = '';
$success = '';

// POST 요청 처리 (설정 저장)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $privacySettings = [
        'purpose' => [
            'title' => $_POST['purpose_title'] ?? '',
            'content' => $_POST['purpose_content'] ?? '',
            'isRequired' => isset($_POST['purpose_isRequired']) && $_POST['purpose_isRequired'] === '1',
            'isVisible' => isset($_POST['purpose_isVisible']) && $_POST['purpose_isVisible'] === '1'
        ],
        'items' => [
            'title' => $_POST['items_title'] ?? '',
            'content' => $_POST['items_content'] ?? '',
            'isRequired' => isset($_POST['items_isRequired']) && $_POST['items_isRequired'] === '1',
            'isVisible' => isset($_POST['items_isVisible']) && $_POST['items_isVisible'] === '1'
        ],
        'period' => [
            'title' => $_POST['period_title'] ?? '',
            'content' => $_POST['period_content'] ?? '',
            'isRequired' => isset($_POST['period_isRequired']) && $_POST['period_isRequired'] === '1',
            'isVisible' => isset($_POST['period_isVisible']) && $_POST['period_isVisible'] === '1'
        ],
        'thirdParty' => [
            'title' => $_POST['thirdParty_title'] ?? '',
            'content' => $_POST['thirdParty_content'] ?? '',
            'isRequired' => isset($_POST['thirdParty_isRequired']) && $_POST['thirdParty_isRequired'] === '1',
            'isVisible' => isset($_POST['thirdParty_isVisible']) && $_POST['thirdParty_isVisible'] === '1'
        ],
        'serviceNotice' => [
            'title' => $_POST['serviceNotice_title'] ?? '',
            'content' => $_POST['serviceNotice_content'] ?? '',
            'isRequired' => isset($_POST['serviceNotice_isRequired']) && $_POST['serviceNotice_isRequired'] === '1',
            'isVisible' => isset($_POST['serviceNotice_isVisible']) && $_POST['serviceNotice_isVisible'] === '1'
        ],
        'marketing' => [
            'title' => $_POST['marketing_title'] ?? '',
            'content' => $_POST['marketing_content'] ?? '',
            'isRequired' => isset($_POST['marketing_isRequired']) && $_POST['marketing_isRequired'] === '1',
            'isVisible' => isset($_POST['marketing_isVisible']) && $_POST['marketing_isVisible'] === '1'
        ]
    ];
    
    // 모든 필드 검증 (serviceNotice와 marketing도 포함)
    $isValid = true;
    foreach ($privacySettings as $key => $value) {
        if (empty($value['title']) || empty($value['content'])) {
            $isValid = false;
            break;
        }
    }
    
    if ($isValid) {
        // 디버깅: 저장할 데이터 확인
        error_log('Privacy Settings Save - Data to save: ' . json_encode($privacySettings, JSON_UNESCAPED_UNICODE));
        
        if (saveAppSettings('privacy', $privacySettings, $currentUser['user_id'] ?? null)) {
            // 저장 후 확인
            $savedSettings = getAppSettings('privacy', []);
            error_log('Privacy Settings Save - Saved data: ' . json_encode($savedSettings, JSON_UNESCAPED_UNICODE));
            
            $success = '개인정보 설정이 저장되었습니다.';
        } else {
            $error = '설정 저장에 실패했습니다.';
        }
    } else {
        $error = '모든 필드를 입력해주세요.';
    }
}

// 현재 설정 읽기
$privacySettings = getAppSettings('privacy', [
    'purpose' => [
        'title' => '개인정보 수집 및 이용목적',
        'content' => '<div class="privacy-content-text"><p><strong>1. 개인정보의 수집 및 이용목적</strong></p><p>&lt;유심킹&gt;(\'http://www.dtmall.net\' 이하 \'회사\') 은(는) 다음의 목적을 위하여 개인정보를 처리하고 있으며, 다음의 목적 이외의 용도로는 이용하지 않습니다.</p></div>',
        'isRequired' => true
    ],
    'items' => [
        'title' => '개인정보 수집하는 항목',
        'content' => '<div class="privacy-content-text"><p><strong>2. 개인정보 수집항목 및 수집방법</strong></p></div>',
        'isRequired' => true
    ],
    'period' => [
        'title' => '개인정보 보유 및 이용기간',
        'content' => '<div class="privacy-content-text"><p><strong>3. 개인정보의 보유 및 이용기간</strong></p></div>',
        'isRequired' => true
    ],
    'thirdParty' => [
        'title' => '개인정보 제3자 제공',
        'content' => '<div class="privacy-content-text"><p><strong>유심킹 개인정보 제3자 제공에 동의</strong></p></div>',
        'isRequired' => true
    ],
    'serviceNotice' => [
        'title' => '서비스 이용 및 혜택 안내 알림',
        'content' => '<div class="privacy-content-text"><p>서비스 이용에 필요한 필수 알림입니다. 알림톡으로 발송됩니다.</p><ul><li>요금제 유지기간 만료 및 변경 안내</li><li>부가서비스 종료 및 이용 조건 변경 안내</li><li>가입 고객 대상 혜택·이벤트 안내</li></ul></div>',
        'isRequired' => true
    ],
    'marketing' => [
        'title' => '광고성 정보수신',
        'content' => '<div class="privacy-content-text"><p>광고성 정보를 받으시려면 아래 항목을 선택해주세요</p><ul><li>이메일 수신동의</li><li>SMS, SNS 수신동의</li><li>앱 푸시 수신동의</li></ul></div>',
        'isRequired' => false
    ]
]);

// isRequired 값이 없는 경우 기본값 설정
foreach ($privacySettings as $key => $value) {
    if (!isset($value['isRequired'])) {
        $privacySettings[$key]['isRequired'] = ($key !== 'marketing');
    }
    // isVisible 값이 없는 경우 기본값 설정 (기본적으로 모두 노출)
    if (!isset($value['isVisible'])) {
        $privacySettings[$key]['isVisible'] = true;
    }
}

// 현재 페이지 설정
$currentPage = 'privacy-settings.php';

// 헤더 포함
include '../includes/admin-header.php';
?>

<style>
    .admin-content {
        padding: 32px;
        max-width: 70%;
        margin: 0 auto;
    }
    
    .page-header {
        margin-bottom: 32px;
    }
    
    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        margin-bottom: 24px;
    }
    
    .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-group label .required {
        color: #ef4444;
        margin-left: 4px;
    }
    
    .form-group input[type="text"],
    .form-group textarea {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.2s;
        box-sizing: border-box;
        font-family: inherit;
    }
    
    .form-group textarea {
        min-height: 200px;
        resize: vertical;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .form-help {
        font-size: 13px;
        color: #6b7280;
        margin-top: 6px;
    }
    
    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-primary {
        background: #6366f1;
        color: white;
    }
    
    .btn-primary:hover {
        background: #4f46e5;
    }
    
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #6ee7b7;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    
    .privacy-section {
        margin-bottom: 32px;
    }
    
    .radio-group {
        display: flex;
        gap: 24px;
        margin-top: 8px;
    }
    
    .radio-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .radio-item input[type="radio"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #6366f1;
    }
    
    .radio-item label {
        font-weight: 500;
        cursor: pointer;
        margin: 0;
    }
    
    .checkbox-group {
        display: flex;
        gap: 24px;
        margin-top: 8px;
    }
    
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #6366f1;
    }
    
    .checkbox-item label {
        font-weight: 500;
        cursor: pointer;
        margin: 0;
    }
</style>

<div class="admin-content">
    <div class="page-header">
        <h1>개인정보 설정</h1>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="save_settings" value="1">
        
        <!-- 개인정보 동의 항목 섹션 -->
        <div style="margin-bottom: 32px;">
            <h2 style="font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">개인정보 동의 항목</h2>
            <p style="font-size: 14px; color: #6b7280; margin-bottom: 24px;">가입 신청 시 표시되는 개인정보 수집 및 이용 동의 항목입니다.</p>
        </div>
        
        <!-- 개인정보 수집 및 이용목적 -->
        <div class="card privacy-section">
            <div class="form-group">
                <label for="purpose_title">제목 <span class="required">*</span></label>
                <input type="text" id="purpose_title" name="purpose_title" required value="<?php echo htmlspecialchars($privacySettings['purpose']['title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <div style="display: flex; gap: 48px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label>노출 여부 <span class="required">*</span></label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="purpose_isVisible" name="purpose_isVisible" value="1" <?php echo (($privacySettings['purpose']['isVisible'] ?? true) ? 'checked' : ''); ?>>
                                <label for="purpose_isVisible">노출</label>
                            </div>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label>선택 / 필수 <span class="required">*</span></label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="purpose_required" name="purpose_isRequired" value="1" <?php echo (($privacySettings['purpose']['isRequired'] ?? true) ? 'checked' : ''); ?>>
                                <label for="purpose_required">필수</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="purpose_optional" name="purpose_isRequired" value="0" <?php echo (!($privacySettings['purpose']['isRequired'] ?? true) ? 'checked' : ''); ?>>
                                <label for="purpose_optional">선택</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="purpose_content">내용 <span class="required">*</span></label>
                <textarea id="purpose_content" name="purpose_content" required><?php echo htmlspecialchars($privacySettings['purpose']['content'] ?? ''); ?></textarea>
                <div class="form-help">HTML 태그를 사용할 수 있습니다.</div>
            </div>
        </div>
        
        <!-- 개인정보 수집하는 항목 -->
        <div class="card privacy-section">
            <div class="form-group">
                <label for="items_title">제목 <span class="required">*</span></label>
                <input type="text" id="items_title" name="items_title" required value="<?php echo htmlspecialchars($privacySettings['items']['title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <div style="display: flex; gap: 48px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label>노출 여부 <span class="required">*</span></label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="items_isVisible" name="items_isVisible" value="1" <?php echo (($privacySettings['items']['isVisible'] ?? true) ? 'checked' : ''); ?>>
                                <label for="items_isVisible">노출</label>
                            </div>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label>선택 / 필수 <span class="required">*</span></label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="items_required" name="items_isRequired" value="1" <?php echo (($privacySettings['items']['isRequired'] ?? true) ? 'checked' : ''); ?>>
                                <label for="items_required">필수</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="items_optional" name="items_isRequired" value="0" <?php echo (!($privacySettings['items']['isRequired'] ?? true) ? 'checked' : ''); ?>>
                                <label for="items_optional">선택</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="items_content">본문 내용 <span class="required">*</span></label>
                <textarea id="items_content" name="items_content" required><?php echo htmlspecialchars($privacySettings['items']['content'] ?? ''); ?></textarea>
                <div class="form-help">HTML 태그를 사용할 수 있습니다.</div>
            </div>
        </div>
        
        <!-- 개인정보 보유 및 이용기간 -->
        <div class="card privacy-section">
            <div class="form-group">
                <label for="period_title">제목 <span class="required">*</span></label>
                <input type="text" id="period_title" name="period_title" required value="<?php echo htmlspecialchars($privacySettings['period']['title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <div style="display: flex; gap: 48px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label>노출 여부 <span class="required">*</span></label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="period_isVisible" name="period_isVisible" value="1" <?php echo (($privacySettings['period']['isVisible'] ?? true) ? 'checked' : ''); ?>>
                                <label for="period_isVisible">노출</label>
                            </div>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label>선택 / 필수 <span class="required">*</span></label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="period_required" name="period_isRequired" value="1" <?php echo (($privacySettings['period']['isRequired'] ?? true) ? 'checked' : ''); ?>>
                                <label for="period_required">필수</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="period_optional" name="period_isRequired" value="0" <?php echo (!($privacySettings['period']['isRequired'] ?? true) ? 'checked' : ''); ?>>
                                <label for="period_optional">선택</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="period_content">본문 내용 <span class="required">*</span></label>
                <textarea id="period_content" name="period_content" required><?php echo htmlspecialchars($privacySettings['period']['content'] ?? ''); ?></textarea>
                <div class="form-help">HTML 태그를 사용할 수 있습니다.</div>
            </div>
        </div>
        
        <!-- 개인정보 제3자 제공 -->
        <div class="card privacy-section">
            <div class="form-group">
                <label for="thirdParty_title">제목 <span class="required">*</span></label>
                <input type="text" id="thirdParty_title" name="thirdParty_title" required value="<?php echo htmlspecialchars($privacySettings['thirdParty']['title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <div style="display: flex; gap: 48px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label>노출 여부 <span class="required">*</span></label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="thirdParty_isVisible" name="thirdParty_isVisible" value="1" <?php echo (($privacySettings['thirdParty']['isVisible'] ?? true) ? 'checked' : ''); ?>>
                                <label for="thirdParty_isVisible">노출</label>
                            </div>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label>선택 / 필수 <span class="required">*</span></label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="thirdParty_required" name="thirdParty_isRequired" value="1" <?php echo (($privacySettings['thirdParty']['isRequired'] ?? true) ? 'checked' : ''); ?>>
                                <label for="thirdParty_required">필수</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="thirdParty_optional" name="thirdParty_isRequired" value="0" <?php echo (!($privacySettings['thirdParty']['isRequired'] ?? true) ? 'checked' : ''); ?>>
                                <label for="thirdParty_optional">선택</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="thirdParty_content">본문 내용 <span class="required">*</span></label>
                <textarea id="thirdParty_content" name="thirdParty_content" required><?php echo htmlspecialchars($privacySettings['thirdParty']['content'] ?? ''); ?></textarea>
                <div class="form-help">HTML 태그를 사용할 수 있습니다.</div>
            </div>
        </div>
        
        <!-- 알림 설정 연동 항목 섹션 -->
        <div style="margin-top: 48px; margin-bottom: 32px;">
            <h2 style="font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 8px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">알림 설정 연동 항목</h2>
            <p style="font-size: 14px; color: #6b7280; margin-bottom: 24px;">
                <span style="color: #6366f1; font-weight: 600;">※ 마이페이지 알림 설정과 연동됩니다.</span><br>
                아래 두 항목의 설정이 마이페이지 > 알림 설정 페이지에 표시되며, 사용자가 직접 알림 수신 여부를 변경할 수 있습니다.
            </p>
        </div>
        
        <!-- 서비스 이용 및 혜택 안내 알림(필수) -->
        <div class="card privacy-section" style="border-left: 4px solid #6366f1;">
            <div class="card-title" style="color: #6366f1; font-weight: 700;">
                🔔 서비스 이용 및 혜택 안내 알림 (알림 설정 연동)
            </div>
            <div class="form-group">
                <label for="serviceNotice_title">제목 <span class="required">*</span></label>
                <input type="text" id="serviceNotice_title" name="serviceNotice_title" required value="<?php echo htmlspecialchars($privacySettings['serviceNotice']['title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <div style="display: flex; gap: 48px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label>노출 여부 <span class="required">*</span></label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="serviceNotice_isVisible" name="serviceNotice_isVisible" value="1" <?php echo (($privacySettings['serviceNotice']['isVisible'] ?? true) ? 'checked' : ''); ?>>
                                <label for="serviceNotice_isVisible">노출</label>
                            </div>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label>선택 / 필수 <span class="required">*</span></label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="serviceNotice_required" name="serviceNotice_isRequired" value="1" <?php echo (($privacySettings['serviceNotice']['isRequired'] ?? true) ? 'checked' : ''); ?>>
                                <label for="serviceNotice_required">필수</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="serviceNotice_optional" name="serviceNotice_isRequired" value="0" <?php echo (!($privacySettings['serviceNotice']['isRequired'] ?? true) ? 'checked' : ''); ?>>
                                <label for="serviceNotice_optional">선택</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="serviceNotice_content">본문 내용 <span class="required">*</span></label>
                <textarea id="serviceNotice_content" name="serviceNotice_content" required><?php echo htmlspecialchars($privacySettings['serviceNotice']['content'] ?? ''); ?></textarea>
                <div class="form-help">HTML 태그를 사용할 수 있습니다.</div>
            </div>
        </div>
        
        <!-- 광고성 정보수신(선택) -->
        <div class="card privacy-section" style="border-left: 4px solid #6366f1;">
            <div class="card-title" style="color: #6366f1; font-weight: 700;">
                🔔 광고성 정보수신 (알림 설정 연동)
            </div>
            <div class="form-group">
                <label for="marketing_title">제목 <span class="required">*</span></label>
                <input type="text" id="marketing_title" name="marketing_title" required value="<?php echo htmlspecialchars($privacySettings['marketing']['title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <div style="display: flex; gap: 48px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label>노출 여부 <span class="required">*</span></label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="marketing_isVisible" name="marketing_isVisible" value="1" <?php echo (($privacySettings['marketing']['isVisible'] ?? true) ? 'checked' : ''); ?>>
                                <label for="marketing_isVisible">노출</label>
                            </div>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label>선택 / 필수 <span class="required">*</span></label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="marketing_required" name="marketing_isRequired" value="1" <?php echo (($privacySettings['marketing']['isRequired'] ?? false) ? 'checked' : ''); ?>>
                                <label for="marketing_required">필수</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="marketing_optional" name="marketing_isRequired" value="0" <?php echo (!($privacySettings['marketing']['isRequired'] ?? false) ? 'checked' : ''); ?>>
                                <label for="marketing_optional">선택</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="marketing_content">본문 내용 <span class="required">*</span></label>
                <textarea id="marketing_content" name="marketing_content" required><?php echo htmlspecialchars($privacySettings['marketing']['content'] ?? ''); ?></textarea>
                <div class="form-help">HTML 태그를 사용할 수 있습니다.</div>
            </div>
        </div>
        
        <div style="margin-top: 32px;">
            <button type="submit" class="btn btn-primary">설정 저장</button>
        </div>
    </form>
</div>

<?php include '../includes/admin-footer.php'; ?>

