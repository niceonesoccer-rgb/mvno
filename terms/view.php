<?php
/**
 * 약관 내용 표시 페이지
 * 관리자 페이지에서 설정한 약관 내용을 표시합니다.
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/site-settings.php';

// 현재 페이지 설정
$current_page = 'terms';
$is_main_page = true;

// 헤더 포함
include '../includes/header.php';

// 약관 타입 가져오기
$type = $_GET['type'] ?? '';
$validTypes = ['terms_of_service', 'privacy_policy', 'information_security'];

if (!in_array($type, $validTypes)) {
    http_response_code(404);
    ?>
    <main class="main-content">
        <div class="content-layout" style="max-width: 800px; margin: 80px auto; padding: 40px 20px;">
            <div style="text-align: center;">
                <h1 style="font-size: 28px; font-weight: 700; color: #1f2937; margin: 0 0 12px 0;">페이지를 찾을 수 없습니다</h1>
                <p style="font-size: 16px; color: #6b7280; margin: 0 0 32px 0;">요청하신 약관 페이지가 존재하지 않습니다.</p>
                <a href="/MVNO/" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #3b82f6; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                    홈으로 가기
                </a>
            </div>
        </div>
    </main>
    <?php include '../includes/footer.php'; ?>
    <?php exit; ?>
    <?php
}

// 사이트 설정 가져오기
$siteSettings = getSiteSettings();
$footer = $siteSettings['footer'] ?? [];
$terms = $footer['terms'] ?? [];

// 약관 정보 가져오기
$termInfo = $terms[$type] ?? null;

if (!$termInfo || empty($termInfo['content'])) {
    http_response_code(404);
    ?>
    <main class="main-content">
        <div class="content-layout" style="max-width: 800px; margin: 80px auto; padding: 40px 20px;">
            <div style="text-align: center;">
                <h1 style="font-size: 28px; font-weight: 700; color: #1f2937; margin: 0 0 12px 0;">내용이 없습니다</h1>
                <p style="font-size: 16px; color: #6b7280; margin: 0 0 32px 0;">관리자 페이지에서 약관 내용을 설정해주세요.</p>
                <a href="/MVNO/" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #3b82f6; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                    홈으로 가기
                </a>
            </div>
        </div>
    </main>
    <?php include '../includes/footer.php'; ?>
    <?php exit; ?>
    <?php
}

$termTitle = $termInfo['text'] ?? '약관';
$termContent = $termInfo['content'] ?? '';
?>

<main class="main-content">
    <div class="content-layout" style="max-width: 900px; margin: 40px auto; padding: 40px 20px;">
        <div style="background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
            <h1 style="font-size: 32px; font-weight: 700; color: #1f2937; margin: 0 0 32px 0; padding-bottom: 16px; border-bottom: 2px solid #e5e7eb;">
                <?php echo htmlspecialchars($termTitle); ?>
            </h1>
            
            <div class="terms-content" style="font-size: 15px; line-height: 1.8; color: #374151;">
                <?php echo $termContent; ?>
            </div>
        </div>
    </div>
</main>

<style>
.terms-content {
    word-wrap: break-word;
}

.terms-content h1,
.terms-content h2,
.terms-content h3 {
    color: #1f2937;
    margin-top: 24px;
    margin-bottom: 12px;
}

.terms-content h1 {
    font-size: 24px;
    font-weight: 700;
}

.terms-content h2 {
    font-size: 20px;
    font-weight: 600;
}

.terms-content h3 {
    font-size: 18px;
    font-weight: 600;
}

.terms-content p {
    margin-bottom: 12px;
}

.terms-content ul,
.terms-content ol {
    margin: 12px 0;
    padding-left: 24px;
}

.terms-content li {
    margin-bottom: 8px;
}

.terms-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 16px 0;
}

.terms-content table th,
.terms-content table td {
    border: 1px solid #e5e7eb;
    padding: 12px;
    text-align: left;
}

.terms-content table th {
    background-color: #f9fafb;
    font-weight: 600;
}

@media (max-width: 767px) {
    .content-layout {
        padding: 20px 16px !important;
    }
    
    .terms-content {
        font-size: 14px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
