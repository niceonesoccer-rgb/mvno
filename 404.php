<?php
/**
 * 404 에러 페이지
 */

require_once __DIR__ . '/includes/data/path-config.php';
require_once __DIR__ . '/includes/data/site-settings.php';

// 사이트 설정 가져오기
$siteSettings = getSiteSettings();
$site = $siteSettings['site'] ?? [];

// 현재 페이지 설정
$current_page = '404';
$is_main_page = false;

// HTTP 404 상태 코드 설정
http_response_code(404);

// 헤더 포함
include __DIR__ . '/includes/header.php';
?>

<main class="main-content">
    <div class="content-layout" style="max-width: 800px; margin: 80px auto; padding: 40px 20px; text-align: center;">
        <div style="margin-bottom: 32px;">
            <h1 style="font-size: 72px; font-weight: 700; color: #6366f1; margin: 0 0 16px 0; line-height: 1;">404</h1>
            <h2 style="font-size: 28px; font-weight: 700; color: #1f2937; margin: 0 0 12px 0;">페이지를 찾을 수 없습니다</h2>
            <p style="font-size: 16px; color: #6b7280; margin: 0 0 32px 0; line-height: 1.6;">
                요청하신 페이지가 존재하지 않거나 이동되었을 수 있습니다.<br>
                URL을 다시 확인해주세요.
            </p>
        </div>
        
        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo getAssetPath('/'); ?>" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #6366f1; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; transition: background 0.2s;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                홈으로 가기
            </a>
            <button onclick="history.back()" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; text-decoration: none; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                이전 페이지
            </button>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
