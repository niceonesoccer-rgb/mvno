<?php
/**
 * 개인정보 설정 관리자 페이지
 * 경로: /MVNO/admin/settings/privacy-settings.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';

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

// 개인정보 설정 파일 경로
$privacySettingsFile = __DIR__ . '/../../includes/data/privacy-settings.json';

// POST 요청 처리 (설정 저장)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $privacySettings = [
        'purpose' => [
            'title' => $_POST['purpose_title'] ?? '',
            'content' => $_POST['purpose_content'] ?? ''
        ],
        'items' => [
            'title' => $_POST['items_title'] ?? '',
            'content' => $_POST['items_content'] ?? ''
        ],
        'period' => [
            'title' => $_POST['period_title'] ?? '',
            'content' => $_POST['period_content'] ?? ''
        ],
        'thirdParty' => [
            'title' => $_POST['thirdParty_title'] ?? '',
            'content' => $_POST['thirdParty_content'] ?? ''
        ]
    ];
    
    // 모든 필드 검증
    $isValid = true;
    foreach ($privacySettings as $key => $value) {
        if (empty($value['title']) || empty($value['content'])) {
            $isValid = false;
            break;
        }
    }
    
    if ($isValid) {
        // JSON 파일로 저장
        $jsonData = json_encode($privacySettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($privacySettingsFile, $jsonData)) {
            $success = '개인정보 설정이 저장되었습니다.';
        } else {
            $error = '설정 저장에 실패했습니다.';
        }
    } else {
        $error = '모든 필드를 입력해주세요.';
    }
}

// 현재 설정 읽기
$privacySettings = [];
if (file_exists($privacySettingsFile)) {
    $jsonContent = file_get_contents($privacySettingsFile);
    $privacySettings = json_decode($jsonContent, true) ?: [];
} else {
    // 기본값 설정
    $privacySettings = [
        'purpose' => [
            'title' => '개인정보 수집 및 이용목적',
            'content' => '<div class="privacy-content-text"><p><strong>1. 개인정보의 수집 및 이용목적</strong></p><p>&lt;엔씨/빵삼텔레콤&gt;(\'http://www.dtmall.net\' 이하 \'회사\') 은(는) 다음의 목적을 위하여 개인정보를 처리하고 있으며, 다음의 목적 이외의 용도로는 이용하지 않습니다.</p></div>'
        ],
        'items' => [
            'title' => '개인정보 수집하는 항목',
            'content' => '<div class="privacy-content-text"><p><strong>2. 개인정보 수집항목 및 수집방법</strong></p></div>'
        ],
        'period' => [
            'title' => '개인정보 보유 및 이용기간',
            'content' => '<div class="privacy-content-text"><p><strong>3. 개인정보의 보유 및 이용기간</strong></p></div>'
        ],
        'thirdParty' => [
            'title' => '개인정보 제3자 제공',
            'content' => '<div class="privacy-content-text"><p><strong>모요 개인정보 제3자 제공에 동의</strong></p></div>'
        ]
    ];
}

// 현재 페이지 설정
$currentPage = 'privacy-settings.php';

// 헤더 포함
include '../includes/admin-header.php';
?>

<style>
    .admin-content {
        padding: 32px;
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
        
        <!-- 개인정보 수집 및 이용목적 -->
        <div class="card privacy-section">
            <div class="card-title">개인정보 수집 및 이용목적에 동의합니까?</div>
            <div class="form-group">
                <label for="purpose_title">제목 <span class="required">*</span></label>
                <input type="text" id="purpose_title" name="purpose_title" required value="<?php echo htmlspecialchars($privacySettings['purpose']['title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="purpose_content">내용 <span class="required">*</span></label>
                <textarea id="purpose_content" name="purpose_content" required><?php echo htmlspecialchars($privacySettings['purpose']['content'] ?? ''); ?></textarea>
                <div class="form-help">HTML 태그를 사용할 수 있습니다.</div>
            </div>
        </div>
        
        <!-- 개인정보 수집하는 항목 -->
        <div class="card privacy-section">
            <div class="card-title">개인정보 수집하는 항목에 동의합니까?</div>
            <div class="form-group">
                <label for="items_title">제목 <span class="required">*</span></label>
                <input type="text" id="items_title" name="items_title" required value="<?php echo htmlspecialchars($privacySettings['items']['title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="items_content">내용 <span class="required">*</span></label>
                <textarea id="items_content" name="items_content" required><?php echo htmlspecialchars($privacySettings['items']['content'] ?? ''); ?></textarea>
                <div class="form-help">HTML 태그를 사용할 수 있습니다.</div>
            </div>
        </div>
        
        <!-- 개인정보 보유 및 이용기간 -->
        <div class="card privacy-section">
            <div class="card-title">개인정보 보유 및 이용기간에 동의합니까?</div>
            <div class="form-group">
                <label for="period_title">제목 <span class="required">*</span></label>
                <input type="text" id="period_title" name="period_title" required value="<?php echo htmlspecialchars($privacySettings['period']['title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="period_content">내용 <span class="required">*</span></label>
                <textarea id="period_content" name="period_content" required><?php echo htmlspecialchars($privacySettings['period']['content'] ?? ''); ?></textarea>
                <div class="form-help">HTML 태그를 사용할 수 있습니다.</div>
            </div>
        </div>
        
        <!-- 개인정보 제3자 제공 -->
        <div class="card privacy-section">
            <div class="card-title">개인정보 제3자 제공에 동의합니까?</div>
            <div class="form-group">
                <label for="thirdParty_title">제목 <span class="required">*</span></label>
                <input type="text" id="thirdParty_title" name="thirdParty_title" required value="<?php echo htmlspecialchars($privacySettings['thirdParty']['title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="thirdParty_content">내용 <span class="required">*</span></label>
                <textarea id="thirdParty_content" name="thirdParty_content" required><?php echo htmlspecialchars($privacySettings['thirdParty']['content'] ?? ''); ?></textarea>
                <div class="form-help">HTML 태그를 사용할 수 있습니다.</div>
            </div>
        </div>
        
        <div style="margin-top: 32px;">
            <button type="submit" class="btn btn-primary">설정 저장</button>
        </div>
    </form>
</div>

<?php include '../includes/admin-footer.php'; ?>

