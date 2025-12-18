<?php
/**
 * 사이트 설정 관리자 페이지
 * 경로: /MVNO/admin/settings/site-settings.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/site-settings.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdmin()) {
    header('Location: /MVNO/admin/');
    exit;
}

$error = '';
$success = '';

$settings = getSiteSettings();

// 저장
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $settings['site']['name_ko'] = trim($_POST['site_name_ko'] ?? '');
    $settings['site']['name_en'] = trim($_POST['site_name_en'] ?? '');
    $settings['site']['tagline'] = trim($_POST['site_tagline'] ?? '');

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

    // 최소 필드 검증
    if (empty($settings['site']['name_ko'])) {
        $error = '사이트명(한글)을 입력해주세요.';
    } elseif (empty($settings['site']['name_en'])) {
        $error = '사이트명(영문)을 입력해주세요.';
    } else {
        if (saveSiteSettings($settings)) {
            $success = '사이트 설정이 저장되었습니다.';
        } else {
            $error = '설정 저장에 실패했습니다.';
        }
    }
}

$currentPage = 'site-settings.php';
include '../includes/admin-header.php';
?>

<style>
    .admin-content { padding: 32px; }
    .page-header { margin-bottom: 32px; }
    .page-header h1 { font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
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
</style>

<div class="admin-content">
    <div class="page-header">
        <h1>사이트설정</h1>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="save_settings" value="1">

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
                <label for="site_tagline">태그라인</label>
                <input type="text" id="site_tagline" name="site_tagline" value="<?php echo htmlspecialchars($settings['site']['tagline'] ?? ''); ?>">
            </div>
        </div>

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
        </div>

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
        </div>

        <div style="margin-top: 32px;">
            <button type="submit" class="btn btn-primary">설정 저장</button>
        </div>
    </form>
</div>

<?php include '../includes/admin-footer.php'; ?>



