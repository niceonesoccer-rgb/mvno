<?php
/**
 * 사이트 설정 관리자 페이지
 * 경로: /MVNO/admin/settings/site-settings.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/site-settings.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/terms-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 코드보기 AJAX 요청 처리 (관리자 체크 전에 처리)
if (isset($_GET['action']) && $_GET['action'] === 'get_code' && isset($_GET['id']) && isset($_GET['type'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 관리자 권한 체크
    if (!function_exists('isAdmin') || !isAdmin()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => '권한이 없습니다.'
        ]);
        exit;
    }
    
    $id = intval($_GET['id']);
    $type = $_GET['type'] === 'terms' ? 'terms_of_service' : 'privacy_policy';
    
    $version = getTermsVersionById($id);
    
    if ($version && $version['type'] === $type) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'content' => $version['content']
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => '버전을 찾을 수 없습니다.'
        ]);
        exit;
    }
}

if (!isAdmin()) {
    header('Location: /MVNO/admin/');
    exit;
}

$error = '';
$success = '';

// 성공 메시지는 모달로 표시되므로 PHP 변수는 사용하지 않음
// JavaScript에서 URL 파라미터를 확인하여 모달 표시

$settings = getSiteSettings();
$currentUser = getCurrentUser();
$createdBy = $currentUser['user_id'] ?? 'admin';

// 개인정보처리방침 버전 관리 처리
$privacyVersionError = '';
$privacyVersionSuccess = '';
// 성공 메시지 처리
if (isset($_GET['privacy_success'])) {
    switch ($_GET['privacy_success']) {
        case 'added':
            $privacyVersionSuccess = '버전이 추가되었습니다.';
            break;
        case 'deleted':
            $privacyVersionSuccess = '버전이 삭제되었습니다.';
            break;
        case 'activated':
            $privacyVersionSuccess = '활성 버전으로 설정되었습니다.';
            break;
    }
}

// 이용약관 버전 관리 처리
$termsVersionError = '';
$termsVersionSuccess = '';
// 성공 메시지 처리
if (isset($_GET['terms_success'])) {
    switch ($_GET['terms_success']) {
        case 'added':
            $termsVersionSuccess = '버전이 추가되었습니다.';
            break;
        case 'deleted':
            $termsVersionSuccess = '버전이 삭제되었습니다.';
            break;
        case 'activated':
            $termsVersionSuccess = '활성 버전으로 설정되었습니다.';
            break;
    }
}


// 버전 목록 가져오기 (테이블이 없을 경우를 대비해 try-catch)
$privacyVersionList = [];
$activePrivacyVersion = null;
try {
    $allPrivacyVersionList = getTermsVersionList('privacy_policy', true);
    $activePrivacyVersion = getActiveTermsVersion('privacy_policy');
    
    // 페이지네이션 설정
    $privacyPerPage = 10;
    $privacyPage = isset($_GET['privacy_page']) ? max(1, intval($_GET['privacy_page'])) : 1;
    $totalPrivacyVersions = count($allPrivacyVersionList);
    $totalPrivacyPages = ceil($totalPrivacyVersions / $privacyPerPage);
    $privacyPage = min($privacyPage, max(1, $totalPrivacyPages));
    $privacyOffset = ($privacyPage - 1) * $privacyPerPage;
    $privacyVersionList = array_slice($allPrivacyVersionList, $privacyOffset, $privacyPerPage);
} catch (PDOException $e) {
    // 테이블이 없으면 빈 배열로 처리
    $privacyVersionList = [];
    $activePrivacyVersion = null;
    $totalPrivacyPages = 0;
    $privacyPage = 1;
}


// 이용약관 버전 목록 가져오기
$termsVersionList = [];
$activeTermsVersion = null;
try {
    $allTermsVersionList = getTermsVersionList('terms_of_service', true);
    $activeTermsVersion = getActiveTermsVersion('terms_of_service');
    
    // 페이지네이션 설정
    $termsPerPage = 10;
    $termsPage = isset($_GET['terms_page']) ? max(1, intval($_GET['terms_page'])) : 1;
    $totalTermsVersions = count($allTermsVersionList);
    $totalTermsPages = ceil($totalTermsVersions / $termsPerPage);
    $termsPage = min($termsPage, max(1, $totalTermsPages));
    $termsOffset = ($termsPage - 1) * $termsPerPage;
    $termsVersionList = array_slice($allTermsVersionList, $termsOffset, $termsPerPage);
} catch (PDOException $e) {
    // 테이블이 없으면 빈 배열로 처리
    $termsVersionList = [];
    $activeTermsVersion = null;
    $totalTermsPages = 0;
    $termsPage = 1;
}

// 버전 추가/수정/삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['privacy_version_action'])) {
    $action = $_POST['privacy_version_action'];
    
    if ($action === 'save_version') {
        $version = trim($_POST['privacy_version'] ?? '');
        $effectiveDate = trim($_POST['privacy_effective_date'] ?? '');
        $announcementDate = trim($_POST['privacy_announcement_date'] ?? '') ?: null;
        $title = trim($_POST['privacy_title'] ?? '개인정보처리방침');
        $content = trim($_POST['privacy_content'] ?? '');
        $setAsActive = isset($_POST['privacy_is_active']) && $_POST['privacy_is_active'] === '1';
        
        if (empty($version) || empty($effectiveDate) || empty($content)) {
            $privacyVersionError = '모든 필수 항목을 입력해주세요.';
        } else {
            // 추가
            $result = saveTermsVersion(
                    'privacy_policy',
                    $version,
                    $effectiveDate,
                    $title,
                    $content,
                    $announcementDate,
                    $setAsActive,
                    $createdBy
                );
                if ($result) {
                    // POST 처리 후 GET 파라미터를 다시 로드하기 위해 리다이렉트
                    header('Location: ?tab=privacy&privacy_success=added');
                    exit;
                } else {
                    // 중복 체크: 더 구체적인 오류 메시지
                    $pdo = getDBConnection();
                    if ($pdo) {
                        try {
                            $stmt = $pdo->prepare("SELECT id, version FROM terms_versions WHERE type = :type AND version = :version LIMIT 1");
                            $stmt->execute([':type' => 'privacy_policy', ':version' => $version]);
                            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($existing) {
                                $privacyVersionError = "버전 '{$version}'은(는) 이미 존재합니다. 다른 버전 번호를 사용하시거나 기존 버전을 수정해주세요.";
                            } else {
                                $privacyVersionError = '버전 추가에 실패했습니다. 다시 시도해주세요. (테이블이 생성되지 않았을 수 있습니다. database/create_terms_versions_table_now.php를 실행하세요)';
                            }
                        } catch (PDOException $e) {
                            $privacyVersionError = '버전 추가에 실패했습니다. terms_versions 테이블이 없습니다. <a href="/MVNO/database/create_terms_versions_table_now.php" target="_blank">테이블 생성 스크립트 실행</a>';
                        }
                    } else {
                        $privacyVersionError = '버전 추가에 실패했습니다. 데이터베이스 연결 오류가 발생했습니다.';
                    }
                }
        }
    } elseif ($action === 'delete_version' && isset($_POST['privacy_version_id'])) {
        $id = intval($_POST['privacy_version_id']);
        if (deleteTermsVersion($id)) {
            header('Location: ?tab=privacy&privacy_success=deleted');
            exit;
        } else {
            $privacyVersionError = '버전 삭제에 실패했습니다. (활성 버전은 삭제할 수 없습니다)';
        }
    } elseif ($action === 'set_active_version' && isset($_POST['privacy_version_id'])) {
        $id = intval($_POST['privacy_version_id']);
        if (updateTermsVersion($id, ['is_active' => 1])) {
            header('Location: ?tab=privacy&privacy_success=activated');
            exit;
        } else {
            $privacyVersionError = '활성 버전 설정에 실패했습니다.';
        }
    }
}

// 이용약관 버전 추가/수정/삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['terms_version_action'])) {
    $action = $_POST['terms_version_action'];
    
    if ($action === 'save_version') {
        $version = trim($_POST['terms_version'] ?? '');
        $effectiveDate = trim($_POST['terms_effective_date'] ?? '');
        $announcementDate = trim($_POST['terms_announcement_date'] ?? '') ?: null;
        $title = trim($_POST['terms_title'] ?? '이용약관');
        $content = trim($_POST['terms_content'] ?? '');
        $setAsActive = isset($_POST['terms_is_active']) && $_POST['terms_is_active'] === '1';
        
        if (empty($version) || empty($effectiveDate) || empty($content)) {
            $termsVersionError = '모든 필수 항목을 입력해주세요.';
        } else {
            // 추가
            $result = saveTermsVersion(
                    'terms_of_service',
                    $version,
                    $effectiveDate,
                    $title,
                    $content,
                    $announcementDate,
                    $setAsActive,
                    $createdBy
                );
                if ($result) {
                    header('Location: ?tab=terms&terms_success=added');
                    exit;
                } else {
                    // 중복 체크: 더 구체적인 오류 메시지
                    $pdo = getDBConnection();
                    if ($pdo) {
                        try {
                            $stmt = $pdo->prepare("SELECT id, version FROM terms_versions WHERE type = :type AND version = :version LIMIT 1");
                            $stmt->execute([':type' => 'terms_of_service', ':version' => $version]);
                            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($existing) {
                                $termsVersionError = "버전 '{$version}'은(는) 이미 존재합니다. 다른 버전 번호를 사용하시거나 기존 버전을 수정해주세요.";
                            } else {
                                $termsVersionError = '버전 추가에 실패했습니다. 다시 시도해주세요. (테이블이 생성되지 않았을 수 있습니다. database/create_terms_versions_table_now.php를 실행하세요)';
                            }
                        } catch (PDOException $e) {
                            $termsVersionError = '버전 추가에 실패했습니다. terms_versions 테이블이 없습니다. <a href="/MVNO/database/create_terms_versions_table_now.php" target="_blank">테이블 생성 스크립트 실행</a>';
                        }
                    } else {
                        $termsVersionError = '버전 추가에 실패했습니다. 데이터베이스 연결 오류가 발생했습니다.';
                    }
                }
        }
    } elseif ($action === 'delete_version' && isset($_POST['terms_version_id'])) {
        $id = intval($_POST['terms_version_id']);
        if (deleteTermsVersion($id)) {
            header('Location: ?tab=terms&terms_success=deleted');
            exit;
        } else {
            $termsVersionError = '버전 삭제에 실패했습니다. (활성 버전은 삭제할 수 없습니다)';
        }
    } elseif ($action === 'set_active_version' && isset($_POST['terms_version_id'])) {
        $id = intval($_POST['terms_version_id']);
        if (updateTermsVersion($id, ['is_active' => 1])) {
            header('Location: ?tab=terms&terms_success=activated');
            exit;
        } else {
            $termsVersionError = '활성 버전 설정에 실패했습니다.';
        }
    }
}

// 저장
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $settings['site']['name_ko'] = trim($_POST['site_name_ko'] ?? '');
    $settings['site']['name_en'] = trim($_POST['site_name_en'] ?? '');
    // tagline 필드 제거됨 (카테고리별 태그라인으로 이동)

    $settings['footer']['company_name'] = trim($_POST['footer_company_name'] ?? '');
    $settings['footer']['business_number'] = trim($_POST['footer_business_number'] ?? '');
    $settings['footer']['mail_order_number'] = trim($_POST['footer_mail_order_number'] ?? '');
    $settings['footer']['address'] = trim($_POST['footer_address'] ?? '');
    $settings['footer']['email'] = trim($_POST['footer_email'] ?? '');
    $settings['footer']['phone'] = trim($_POST['footer_phone'] ?? '');
    $settings['footer']['kakao'] = trim($_POST['footer_kakao'] ?? '');
    $settings['footer']['cs_notice'] = trim($_POST['footer_cs_notice'] ?? '');

    $settings['footer']['hours']['weekday'] = trim($_POST['footer_hours_weekday'] ?? '');
    $settings['footer']['hours']['hours'] = trim($_POST['footer_hours_hours'] ?? '');
    $settings['footer']['hours']['lunch'] = trim($_POST['footer_hours_lunch'] ?? '');
    
    // 약관 링크 설정
    $settings['footer']['terms']['terms_of_service']['text'] = trim($_POST['footer_terms_of_service_text'] ?? '') ?: '이용약관';
    $settings['footer']['terms']['terms_of_service']['url'] = trim($_POST['footer_terms_of_service_url'] ?? '') ?: '/MVNO/terms/view.php?type=terms_of_service';
    // content는 버전 관리 시스템으로 관리되므로 저장하지 않음
    // $settings['footer']['terms']['terms_of_service']['content'] = trim($_POST['footer_terms_of_service_content'] ?? '');
    
    $settings['footer']['terms']['privacy_policy']['text'] = trim($_POST['footer_privacy_policy_text'] ?? '') ?: '개인정보처리방침';
    $settings['footer']['terms']['privacy_policy']['url'] = trim($_POST['footer_privacy_policy_url'] ?? '') ?: '/MVNO/terms/view.php?type=privacy_policy';
    // content는 버전 관리 시스템으로 관리되므로 저장하지 않음
    // $settings['footer']['terms']['privacy_policy']['content'] = trim($_POST['footer_privacy_policy_content'] ?? '');

    // 최소 필드 검증
    if (empty($settings['site']['name_ko'])) {
        $error = '사이트명(한글)을 입력해주세요.';
    } elseif (empty($settings['site']['name_en'])) {
        $error = '사이트명(영문)을 입력해주세요.';
    } else {
        if (saveSiteSettings($settings)) {
            // 저장 후 현재 탭으로 리다이렉트
            $currentTab = $_POST['current_tab'] ?? 'basic';
            if (!in_array($currentTab, ['basic', 'footer', 'hours', 'terms', 'privacy'])) {
                $currentTab = 'basic';
            }
            header('Location: ?tab=' . $currentTab . '&success=saved');
            exit;
        } else {
            $error = '설정 저장에 실패했습니다.';
        }
    }
}

// 초기 탭 설정 (URL 파라미터 또는 기본값)
$initialTab = $_GET['tab'] ?? 'basic';
if (!in_array($initialTab, ['basic', 'footer', 'hours', 'terms', 'privacy'])) {
    $initialTab = 'basic';
}

$currentPage = 'site-settings.php';
include '../includes/admin-header.php';
?>

<style>
    .admin-content { padding: 32px; }
    .page-header { margin-bottom: 32px; }
    .page-header h1 { font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
    
    .tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 24px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .tab {
        padding: 12px 24px;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        font-size: 15px;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
        bottom: -2px;
    }
    
    .tab:hover {
        color: #374151;
        background: #f9fafb;
    }
    
    .tab.active {
        color: #6366f1;
        border-bottom-color: #6366f1;
        background: #f9fafb;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; margin-bottom: 24px; }
    .card-title { font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; }
    .form-group input[type="text"], .form-group textarea {
        width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px;
        font-size: 15px; transition: border-color 0.2s; box-sizing: border-box; font-family: inherit;
    }
    .form-group textarea { min-height: 90px; resize: vertical; }
    .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
    .help { font-size: 13px; color: #6b7280; margin-top: 6px; }
    .btn { padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; text-decoration: none; display: inline-block; }
    .btn-primary { background: #6366f1; color: white; }
    .btn-primary:hover { background: #4f46e5; }
    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .btn-danger { background: #ef4444; color: white; padding: 8px 16px; font-size: 14px; }
    .btn-danger:hover { background: #dc2626; }
    .btn-sm { padding: 8px 16px; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; }
    table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    table th { background: #f9fafb; font-weight: 600; color: #374151; }
    table tr:hover { background: #f9fafb; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: 600; }
    .badge-active { background: #d1fae5; color: #065f46; }
    .badge-inactive { background: #e5e7eb; color: #6b7280; }
    
    .pagination { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 20px; flex-wrap: wrap; }
    .pagination-btn { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; color: #374151; text-decoration: none; font-size: 14px; transition: all 0.2s; }
    .pagination-btn:hover:not(.disabled):not(.active) { background: #f9fafb; border-color: #6366f1; color: #6366f1; }
    .pagination-btn.active { background: #6366f1; color: white; border-color: #6366f1; }
    .pagination-btn.disabled { color: #9ca3af; cursor: not-allowed; opacity: 0.5; }
    
    /* 성공 모달 스타일 */
    .success-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }
    
    .success-modal-overlay.show {
        display: flex;
    }
    
    .success-modal {
        background: white;
        border-radius: 12px;
        padding: 32px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        text-align: center;
    }
    
    .success-modal-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 20px;
        background: #d1fae5;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        color: #059669;
    }
    
    .success-modal-title {
        font-size: 20px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 12px;
    }
    
    .success-modal-message {
        font-size: 15px;
        color: #4b5563;
        margin-bottom: 24px;
        line-height: 1.6;
    }
    
    .success-modal-btn {
        padding: 12px 32px;
        background: #6366f1;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .success-modal-btn:hover {
        background: #4f46e5;
    }
</style>

<div class="admin-content">
    <div class="page-header">
        <h1>사이트설정</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="save_settings" value="1">

        <div class="tabs">
            <button type="button" class="tab <?php echo $initialTab === 'basic' ? 'active' : ''; ?>" onclick="switchTab('basic')">사이트 기본</button>
            <button type="button" class="tab <?php echo $initialTab === 'footer' ? 'active' : ''; ?>" onclick="switchTab('footer')">푸터 회사정보</button>
            <button type="button" class="tab <?php echo $initialTab === 'hours' ? 'active' : ''; ?>" onclick="switchTab('hours')">운영시간</button>
            <button type="button" class="tab <?php echo $initialTab === 'terms' ? 'active' : ''; ?>" onclick="switchTab('terms')">이용약관</button>
            <button type="button" class="tab <?php echo $initialTab === 'privacy' ? 'active' : ''; ?>" onclick="switchTab('privacy')">개인정보처리방침</button>
        </div>
        

        <!-- 사이트 기본 탭 -->
        <div id="tab-basic" class="tab-content <?php echo $initialTab === 'basic' ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-title">사이트 기본</div>
                <div class="form-group">
                    <label for="site_name_ko">사이트명(한글)</label>
                    <input type="text" id="site_name_ko" name="site_name_ko" value="<?php echo htmlspecialchars($settings['site']['name_ko'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="site_name_en">사이트명(영문)</label>
                    <input type="text" id="site_name_en" name="site_name_en" value="<?php echo htmlspecialchars($settings['site']['name_en'] ?? ''); ?>" required>
                    <div class="help">예: usimking</div>
                </div>
                <div class="form-group">
                    <div class="help" style="padding: 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; color: #1e40af;">
                        <strong>태그라인 관리:</strong> 카테고리별 태그라인은 <a href="/MVNO/admin/advertisement/tagline.php" style="color: #2563eb; text-decoration: underline;">광고 관리 > 태그라인</a>에서 설정할 수 있습니다.
                    </div>
                </div>
                <div style="margin-top: 24px;">
                    <input type="hidden" name="current_tab" value="basic">
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </div>
        </div>

        <!-- 푸터 회사정보 탭 -->
        <div id="tab-footer" class="tab-content <?php echo $initialTab === 'footer' ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-title">푸터 회사정보</div>
                <div class="form-group">
                    <label for="footer_company_name">상호/법인명</label>
                    <input type="text" id="footer_company_name" name="footer_company_name" value="<?php echo htmlspecialchars($settings['footer']['company_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="footer_business_number">사업자등록번호</label>
                    <input type="text" id="footer_business_number" name="footer_business_number" value="<?php echo htmlspecialchars($settings['footer']['business_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="footer_mail_order_number">통신판매업 신고번호</label>
                    <input type="text" id="footer_mail_order_number" name="footer_mail_order_number" value="<?php echo htmlspecialchars($settings['footer']['mail_order_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="footer_address">주소</label>
                    <input type="text" id="footer_address" name="footer_address" value="<?php echo htmlspecialchars($settings['footer']['address'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="footer_email">이메일</label>
                    <input type="text" id="footer_email" name="footer_email" value="<?php echo htmlspecialchars($settings['footer']['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="footer_phone">전화번호</label>
                    <input type="text" id="footer_phone" name="footer_phone" value="<?php echo htmlspecialchars($settings['footer']['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="footer_kakao">카카오톡</label>
                    <input type="text" id="footer_kakao" name="footer_kakao" value="<?php echo htmlspecialchars($settings['footer']['kakao'] ?? ''); ?>">
                    <div class="help">예: @유심킹</div>
                </div>
                <div class="form-group">
                    <label for="footer_cs_notice">고객센터 안내문(선택)</label>
                    <textarea id="footer_cs_notice" name="footer_cs_notice"><?php echo htmlspecialchars($settings['footer']['cs_notice'] ?? ''); ?></textarea>
                </div>
                <div style="margin-top: 24px;">
                    <input type="hidden" name="current_tab" value="footer">
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </div>
        </div>

        <!-- 운영시간 탭 -->
        <div id="tab-hours" class="tab-content <?php echo $initialTab === 'hours' ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-title">운영시간</div>
                <div class="form-group">
                    <label for="footer_hours_weekday">요일</label>
                    <input type="text" id="footer_hours_weekday" name="footer_hours_weekday" value="<?php echo htmlspecialchars($settings['footer']['hours']['weekday'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="footer_hours_hours">시간</label>
                    <input type="text" id="footer_hours_hours" name="footer_hours_hours" value="<?php echo htmlspecialchars($settings['footer']['hours']['hours'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="footer_hours_lunch">점심시간</label>
                    <input type="text" id="footer_hours_lunch" name="footer_hours_lunch" value="<?php echo htmlspecialchars($settings['footer']['hours']['lunch'] ?? ''); ?>">
                </div>
                <div style="margin-top: 24px;">
                    <input type="hidden" name="current_tab" value="hours">
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </div>
        </div>
    </form>

    <!-- 이용약관 탭 -->
    <div id="tab-terms" class="tab-content <?php echo $initialTab === 'terms' ? 'active' : ''; ?>">
            <?php 
            // 테이블 존재 확인
            $tableExists = false;
            try {
                $pdo = getDBConnection();
                if ($pdo) {
                    $stmt = $pdo->query("SHOW TABLES LIKE 'terms_versions'");
                    $tableExists = $stmt->rowCount() > 0;
                }
            } catch (PDOException $e) {
                $tableExists = false;
            }
            
            if (!$tableExists): 
            ?>
                <div class="alert alert-error">
                    <strong>⚠️ 테이블이 생성되지 않았습니다.</strong><br>
                    버전 관리 기능을 사용하려면 먼저 <code>terms_versions</code> 테이블을 생성해야 합니다.<br>
                    <a href="/MVNO/database/create_terms_versions_table_now.php" target="_blank" style="color: #3b82f6; text-decoration: underline;">테이블 생성 스크립트 실행</a>
                </div>
            <?php endif; ?>
            
            <?php if ($termsVersionError): ?>
                <div class="alert alert-error"><?php echo $termsVersionError; ?></div>
            <?php endif; ?>
            
            <!-- 버전 추가 폼 -->
            <div class="card">
                <div class="card-title">새 버전 추가</div>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="terms_version_action" value="save_version">
                    
                    <div class="form-group">
                        <label for="terms_version">버전 번호 <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="terms_version" name="terms_version" value="" required placeholder="예: v1.0">
                        <div class="help">버전 번호를 입력하세요 (예: v1.0, v2.0)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="terms_effective_date">시행일자 <span style="color: #ef4444;">*</span></label>
                        <input type="date" id="terms_effective_date" name="terms_effective_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="terms_announcement_date">공고일자</label>
                        <input type="date" id="terms_announcement_date" name="terms_announcement_date" value="">
                        <div class="help">공고일자가 있는 경우 입력하세요</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="terms_title">제목 <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="terms_title" name="terms_title" value="이용약관" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="terms_content">내용 (HTML) <span style="color: #ef4444;">*</span></label>
                        <textarea id="terms_content" name="terms_content" rows="20" style="font-family: monospace; font-size: 13px;" required></textarea>
                        <div class="help">HTML 형식으로 내용을 입력하세요.</div>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="terms_is_active" value="1" <?php echo !$activeTermsVersion ? 'checked' : ''; ?>>
                            <span>현재 활성 버전으로 설정</span>
                        </label>
                        <div class="help">체크하면 이 버전이 현재 활성 버전으로 설정됩니다. 기존 활성 버전은 자동으로 비활성화됩니다.</div>
                    </div>
                    
                    <div style="margin-top: 24px;">
                        <button type="submit" class="btn btn-primary">추가</button>
                    </div>
                </form>
            </div>

            <!-- 버전 목록 -->
            <div class="card">
                <div class="card-title">버전 목록</div>
                <?php if (empty($termsVersionList)): ?>
                    <p style="color: #6b7280; padding: 20px 0;">등록된 버전이 없습니다.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 100px;">버전</th>
                                    <th style="width: 120px;">시행일자</th>
                                    <th style="width: 120px;">공고일자</th>
                                    <th>제목</th>
                                    <th style="width: 100px;">상태</th>
                                    <th style="width: 280px;">작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($termsVersionList as $ver): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($ver['version']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($ver['effective_date']); ?></td>
                                        <td><?php echo $ver['announcement_date'] ? htmlspecialchars($ver['announcement_date']) : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($ver['title']); ?></td>
                                        <td>
                                            <?php if ($ver['is_active'] == 1): ?>
                                                <span class="badge badge-active">활성</span>
                                            <?php else: ?>
                                                <span class="badge badge-inactive">비활성</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                <button type="button" class="btn btn-secondary btn-sm" onclick="copyCodeModal('<?php echo htmlspecialchars($ver['version'], ENT_QUOTES); ?>', <?php echo $ver['id']; ?>, 'terms')">코드복사하기</button>
                                                <?php if ($ver['is_active'] != 1): ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('활성 버전으로 설정하시겠습니까?');">
                                                        <input type="hidden" name="terms_version_action" value="set_active_version">
                                                        <input type="hidden" name="terms_version_id" value="<?php echo $ver['id']; ?>">
                                                        <button type="submit" class="btn btn-primary btn-sm">활성화</button>
                                                    </form>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                                        <input type="hidden" name="terms_version_action" value="delete_version">
                                                        <input type="hidden" name="terms_version_id" value="<?php echo $ver['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">삭제</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- 페이지네이션 -->
                    <?php if (isset($totalTermsPages) && $totalTermsPages > 1): ?>
                        <?php
                        // 페이지네이션 링크에는 성공 메시지 파라미터를 포함하지 않음
                        $queryParams = ['tab' => 'terms'];
                        $queryString = http_build_query($queryParams);
                        
                        // 페이지 그룹 계산 (10개씩 그룹화)
                        $pageGroupSize = 10;
                        $currentGroup = ceil($termsPage / $pageGroupSize);
                        $startPage = ($currentGroup - 1) * $pageGroupSize + 1;
                        $endPage = min($currentGroup * $pageGroupSize, $totalTermsPages);
                        $prevGroupLastPage = ($currentGroup - 1) * $pageGroupSize;
                        $nextGroupFirstPage = $currentGroup * $pageGroupSize + 1;
                        ?>
                        <div class="pagination" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 20px; flex-wrap: wrap;">
                            <?php if ($currentGroup > 1): ?>
                                <a href="?<?php echo $queryString; ?>&terms_page=<?php echo $prevGroupLastPage; ?>" 
                                   class="pagination-btn">이전</a>
                            <?php else: ?>
                                <span class="pagination-btn disabled">이전</span>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?<?php echo $queryString; ?>&terms_page=<?php echo $i; ?>" 
                                   class="pagination-btn <?php echo $i === $termsPage ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if ($nextGroupFirstPage <= $totalTermsPages): ?>
                                <a href="?<?php echo $queryString; ?>&terms_page=<?php echo $nextGroupFirstPage; ?>" 
                                   class="pagination-btn">다음</a>
                            <?php else: ?>
                                <span class="pagination-btn disabled">다음</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 개인정보처리방침 탭 -->
        <div id="tab-privacy" class="tab-content <?php echo $initialTab === 'privacy' ? 'active' : ''; ?>">
            <?php 
            // 테이블 존재 확인
            $tableExists = false;
            try {
                $pdo = getDBConnection();
                if ($pdo) {
                    $stmt = $pdo->query("SHOW TABLES LIKE 'terms_versions'");
                    $tableExists = $stmt->rowCount() > 0;
                }
            } catch (PDOException $e) {
                $tableExists = false;
            }
            
            if (!$tableExists): 
            ?>
                <div class="alert alert-error">
                    <strong>⚠️ 테이블이 생성되지 않았습니다.</strong><br>
                    버전 관리 기능을 사용하려면 먼저 <code>terms_versions</code> 테이블을 생성해야 합니다.<br>
                    <a href="/MVNO/database/create_terms_versions_table_now.php" target="_blank" style="color: #3b82f6; text-decoration: underline;">테이블 생성 스크립트 실행</a>
                </div>
            <?php endif; ?>
            
            <?php if ($privacyVersionError): ?>
                <div class="alert alert-error"><?php echo $privacyVersionError; ?></div>
            <?php endif; ?>
            
            <!-- 버전 추가 폼 -->
            <div class="card">
                <div class="card-title">새 버전 추가</div>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="privacy_version_action" value="save_version">
                    
                    <div class="form-group">
                        <label for="privacy_version">버전 번호 <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="privacy_version" name="privacy_version" value="" required placeholder="예: v3.8">
                        <div class="help">버전 번호를 입력하세요 (예: v3.8, v1.0)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="privacy_effective_date">시행일자 <span style="color: #ef4444;">*</span></label>
                        <input type="date" id="privacy_effective_date" name="privacy_effective_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="privacy_announcement_date">공고일자</label>
                        <input type="date" id="privacy_announcement_date" name="privacy_announcement_date" value="">
                        <div class="help">공고일자가 있는 경우 입력하세요</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="privacy_title">제목 <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="privacy_title" name="privacy_title" value="개인정보처리방침" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="privacy_content">내용 (HTML) <span style="color: #ef4444;">*</span></label>
                        <textarea id="privacy_content" name="privacy_content" rows="20" style="font-family: monospace; font-size: 13px;" required></textarea>
                        <div class="help">HTML 형식으로 내용을 입력하세요.</div>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="privacy_is_active" value="1" <?php echo !$activePrivacyVersion ? 'checked' : ''; ?>>
                            <span>현재 활성 버전으로 설정</span>
                        </label>
                        <div class="help">체크하면 이 버전이 현재 활성 버전으로 설정됩니다. 기존 활성 버전은 자동으로 비활성화됩니다.</div>
                    </div>
                    
                    <div style="margin-top: 24px;">
                        <button type="submit" class="btn btn-primary">추가</button>
                    </div>
                </form>
            </div>

            <!-- 버전 목록 -->
            <div class="card">
                <div class="card-title">버전 목록</div>
                <?php if (empty($privacyVersionList)): ?>
                    <p style="color: #6b7280; padding: 20px 0;">등록된 버전이 없습니다.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 100px;">버전</th>
                                    <th style="width: 120px;">시행일자</th>
                                    <th style="width: 120px;">공고일자</th>
                                    <th>제목</th>
                                    <th style="width: 100px;">상태</th>
                                    <th style="width: 280px;">작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($privacyVersionList as $ver): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($ver['version']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($ver['effective_date']); ?></td>
                                        <td><?php echo $ver['announcement_date'] ? htmlspecialchars($ver['announcement_date']) : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($ver['title']); ?></td>
                                        <td>
                                            <?php if ($ver['is_active'] == 1): ?>
                                                <span class="badge badge-active">활성</span>
                                            <?php else: ?>
                                                <span class="badge badge-inactive">비활성</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                <button type="button" class="btn btn-secondary btn-sm" onclick="copyCodeModal('<?php echo htmlspecialchars($ver['version'], ENT_QUOTES); ?>', <?php echo $ver['id']; ?>, 'privacy')">코드복사하기</button>
                                                <?php if ($ver['is_active'] != 1): ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('활성 버전으로 설정하시겠습니까?');">
                                                        <input type="hidden" name="privacy_version_action" value="set_active_version">
                                                        <input type="hidden" name="privacy_version_id" value="<?php echo $ver['id']; ?>">
                                                        <button type="submit" class="btn btn-primary btn-sm">활성화</button>
                                                    </form>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                                        <input type="hidden" name="privacy_version_action" value="delete_version">
                                                        <input type="hidden" name="privacy_version_id" value="<?php echo $ver['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">삭제</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- 페이지네이션 -->
                    <?php if (isset($totalPrivacyPages) && $totalPrivacyPages > 1): ?>
                        <?php
                        // 페이지네이션 링크에는 성공 메시지 파라미터를 포함하지 않음
                        $queryParams = ['tab' => 'privacy'];
                        $queryString = http_build_query($queryParams);
                        
                        // 페이지 그룹 계산 (10개씩 그룹화)
                        $pageGroupSize = 10;
                        $currentGroup = ceil($privacyPage / $pageGroupSize);
                        $startPage = ($currentGroup - 1) * $pageGroupSize + 1;
                        $endPage = min($currentGroup * $pageGroupSize, $totalPrivacyPages);
                        $prevGroupLastPage = ($currentGroup - 1) * $pageGroupSize;
                        $nextGroupFirstPage = $currentGroup * $pageGroupSize + 1;
                        ?>
                        <div class="pagination" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 20px; flex-wrap: wrap;">
                            <?php if ($currentGroup > 1): ?>
                                <a href="?<?php echo $queryString; ?>&privacy_page=<?php echo $prevGroupLastPage; ?>" 
                                   class="pagination-btn">이전</a>
                            <?php else: ?>
                                <span class="pagination-btn disabled">이전</span>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?<?php echo $queryString; ?>&privacy_page=<?php echo $i; ?>" 
                                   class="pagination-btn <?php echo $i === $privacyPage ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if ($nextGroupFirstPage <= $totalPrivacyPages): ?>
                                <a href="?<?php echo $queryString; ?>&privacy_page=<?php echo $nextGroupFirstPage; ?>" 
                                   class="pagination-btn">다음</a>
                            <?php else: ?>
                                <span class="pagination-btn disabled">다음</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

<script>
function switchTab(tabName) {
    // 모든 탭 버튼에서 active 클래스 제거
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // 모든 탭 컨텐츠에서 active 클래스 제거
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // 해당 탭 버튼 찾기 (onclick 속성으로 찾기)
    const tabButtons = document.querySelectorAll('.tab');
    tabButtons.forEach(tab => {
        if (tab.getAttribute('onclick') && tab.getAttribute('onclick').includes(`switchTab('${tabName}')`)) {
            tab.classList.add('active');
        }
    });
    
    // 해당 탭 컨텐츠에 active 클래스 추가
    const tabContent = document.getElementById('tab-' + tabName);
    if (tabContent) {
        tabContent.classList.add('active');
    } else {
        console.error('탭 컨텐츠를 찾을 수 없습니다: tab-' + tabName);
    }
    
    // URL 업데이트 (새로고침 시 탭 유지)
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tabName);
    window.history.replaceState(null, null, url.toString());
}

function getTabLabel(tabName) {
    const labels = {
        'basic': '사이트 기본',
        'footer': '푸터 회사정보',
        'hours': '운영시간',
        'terms': '이용약관',
        'privacy': '개인정보처리방침'
    };
    return labels[tabName] || '';
}

// 성공 모달 표시
function showSuccessModal(message) {
    const modal = document.getElementById('successModal');
    const messageEl = document.getElementById('successModalMessage');
    
    if (messageEl) {
        messageEl.textContent = message;
    }
    
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

// 성공 모달 닫기
function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        
        // URL에서 success 파라미터 제거
        const url = new URL(window.location.href);
        url.searchParams.delete('privacy_success');
        url.searchParams.delete('terms_success');
        url.searchParams.delete('success');
        window.history.replaceState({}, '', url);
    }
}

// URL 파라미터로 탭 활성화 및 성공 메시지 확인
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // 탭 활성화 (먼저 실행하여 기본 탭이 보이도록 함)
    // 약간의 지연을 두어 DOM이 완전히 로드된 후 실행
    setTimeout(function() {
        const tabFromUrl = urlParams.get('tab');
        if (tabFromUrl && document.getElementById(`tab-${tabFromUrl}`)) {
            switchTab(tabFromUrl);
        } else {
            // URL에 tab 파라미터가 없으면 기본 탭 활성화
            switchTab('basic');
        }
    }, 10);
    
    // 일반 설정 저장 성공 메시지
    const success = urlParams.get('success');
    if (success === 'saved') {
        showSuccessModal('사이트 설정이 저장되었습니다.');
    }
    
    // 개인정보처리방침 성공 메시지
    const privacySuccess = urlParams.get('privacy_success');
    if (privacySuccess) {
        let message = '';
        switch (privacySuccess) {
            case 'added':
                message = '개인정보처리방침 버전이 추가되었습니다.';
                break;
            case 'deleted':
                message = '개인정보처리방침 버전이 삭제되었습니다.';
                break;
            case 'activated':
                message = '개인정보처리방침 활성 버전으로 설정되었습니다.';
                break;
        }
        if (message) {
            showSuccessModal(message);
        }
    }
    
    // 이용약관 성공 메시지
    const termsSuccess = urlParams.get('terms_success');
    if (termsSuccess) {
        let message = '';
        switch (termsSuccess) {
            case 'added':
                message = '이용약관 버전이 추가되었습니다.';
                break;
            case 'deleted':
                message = '이용약관 버전이 삭제되었습니다.';
                break;
            case 'activated':
                message = '이용약관 활성 버전으로 설정되었습니다.';
                break;
        }
        if (message) {
            showSuccessModal(message);
        }
    }
    
    // 모달 배경 클릭 시 닫기
    const successModal = document.getElementById('successModal');
    if (successModal) {
        successModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeSuccessModal();
            }
        });
    }
    
});

// 코드복사하기
function copyCodeModal(version, id, type) {
    // AJAX로 버전 내용 가져오기
    const url = window.location.pathname + '?action=get_code&id=' + id + '&type=' + type;
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // 클립보드에 복사
                navigator.clipboard.writeText(data.content).then(() => {
                    showSuccessModal((type === 'terms' ? '이용약관' : '개인정보처리방침') + ' 버전 ' + version + ' 코드가 복사되었습니다.');
                }).catch(err => {
                    console.error('복사 실패:', err);
                    // fallback: 텍스트 영역을 사용한 복사
                    const textarea = document.createElement('textarea');
                    textarea.value = data.content;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.select();
                    try {
                        document.execCommand('copy');
                        showSuccessModal((type === 'terms' ? '이용약관' : '개인정보처리방침') + ' 버전 ' + version + ' 코드가 복사되었습니다.');
                    } catch (e) {
                        alert('코드 복사에 실패했습니다. 브라우저 설정을 확인해주세요.');
                    }
                    document.body.removeChild(textarea);
                });
            } else {
                alert(data.message || '코드를 불러올 수 없습니다.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('코드를 불러오는 중 오류가 발생했습니다: ' + error.message);
        });
}

</script>

<!-- 성공 모달 -->
<div id="successModal" class="success-modal-overlay">
    <div class="success-modal">
        <div class="success-modal-icon">✓</div>
        <div class="success-modal-title">알림</div>
        <div class="success-modal-message" id="successModalMessage"></div>
        <button type="button" class="success-modal-btn" onclick="closeSuccessModal()">확인</button>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>




















