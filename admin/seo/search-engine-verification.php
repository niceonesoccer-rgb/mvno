<?php
/**
 * 검색엔진 검증 코드 관리 페이지
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/path-config.php';
require_once __DIR__ . '/../../includes/data/seo-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdmin()) {
    header('Location: ' . getAssetPath('/admin/login.php'));
    exit;
}

$error = '';
$success = '';

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_verification'])) {
    $codes = [
        'google' => trim($_POST['google_code'] ?? ''),
        'naver' => trim($_POST['naver_code'] ?? ''),
        'bing' => trim($_POST['bing_code'] ?? ''),
        'yandex' => trim($_POST['yandex_code'] ?? ''),
        'head_codes' => trim($_POST['head_codes'] ?? ''),
        'body_codes' => trim($_POST['body_codes'] ?? ''),
        'footer_codes' => trim($_POST['footer_codes'] ?? ''),
    ];
    
    if (saveSearchEngineVerification($codes)) {
        $success = '검증 코드가 저장되었습니다.';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=saved');
        exit;
    } else {
        $error = '검증 코드 저장에 실패했습니다.';
    }
}

// 성공 메시지 처리
if (isset($_GET['success']) && $_GET['success'] === 'saved') {
    $success = '검증 코드가 저장되었습니다.';
}

// 검증 코드 가져오기
$verificationCodes = getSearchEngineVerification();

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1>검색엔진 검증 코드 관리</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" id="verificationForm">
        <input type="hidden" name="save_verification" value="1">
        
        <div class="card">
            <div class="card-title">검색엔진 사이트 소유권 확인</div>
            
            <div class="form-group">
                <label for="google_code">Google Search Console 검증 코드</label>
                <input type="text" id="google_code" name="google_code" value="<?php echo htmlspecialchars($verificationCodes['google'] ?? ''); ?>" placeholder="content 속성 값만 입력하세요 (예: abc123xyz...)">
                <div class="help">
                    Google Search Console에서 제공하는 meta 태그의 content 속성 값만 입력하세요.<br>
                    예: <code>&lt;meta name="google-site-verification" content="abc123xyz..." /&gt;</code> → <code>abc123xyz...</code> 입력
                </div>
            </div>
            
            <div class="form-group">
                <label for="naver_code">Naver Search Advisor 검증 코드</label>
                <input type="text" id="naver_code" name="naver_code" value="<?php echo htmlspecialchars($verificationCodes['naver'] ?? ''); ?>" placeholder="content 속성 값만 입력하세요 (예: def456uvw...)">
                <div class="help">
                    Naver Search Advisor에서 제공하는 meta 태그의 content 속성 값만 입력하세요.<br>
                    예: <code>&lt;meta name="naver-site-verification" content="def456uvw..." /&gt;</code> → <code>def456uvw...</code> 입력
                </div>
            </div>
            
            <div class="form-group">
                <label for="bing_code">Bing Webmaster Tools 검증 코드</label>
                <input type="text" id="bing_code" name="bing_code" value="<?php echo htmlspecialchars($verificationCodes['bing'] ?? ''); ?>" placeholder="content 속성 값만 입력하세요 (예: ghi789rst...)">
                <div class="help">
                    Bing Webmaster Tools에서 제공하는 meta 태그의 content 속성 값만 입력하세요.<br>
                    예: <code>&lt;meta name="msvalidate.01" content="ghi789rst..." /&gt;</code> → <code>ghi789rst...</code> 입력
                </div>
            </div>
            
            <div class="form-group">
                <label for="yandex_code">Yandex Webmaster 검증 코드</label>
                <input type="text" id="yandex_code" name="yandex_code" value="<?php echo htmlspecialchars($verificationCodes['yandex'] ?? ''); ?>" placeholder="content 속성 값만 입력하세요 (선택사항)">
                <div class="help">Yandex Webmaster에서 제공하는 meta 태그의 content 속성 값입니다. (선택사항)</div>
            </div>
        </div>
        
        <div class="card" style="margin-top: 24px;">
            <div class="card-title">추가 코드 (Head, Body, Footer 영역)</div>
            
            <div class="form-group">
                <label for="head_codes">Head 영역 추가 코드</label>
                <textarea id="head_codes" name="head_codes" rows="5" placeholder="&lt;meta&gt; 태그, &lt;link&gt; 태그, &lt;script&gt; 태그 등을 입력하세요.&#10;예: &lt;meta name='robots' content='noindex'&gt;&#10;&lt;link rel='alternate' href='...'&gt;"><?php echo htmlspecialchars($verificationCodes['head_codes'] ?? ''); ?></textarea>
                <div class="help">&lt;head&gt; 영역에 추가할 HTML 코드를 입력하세요. (Google Analytics, Tag Manager 등)</div>
            </div>
            
            <div class="form-group">
                <label for="body_codes">Body 시작 부분 추가 코드</label>
                <textarea id="body_codes" name="body_codes" rows="5" placeholder="&lt;script&gt; 태그, &lt;noscript&gt; 태그 등을 입력하세요.&#10;예: &lt;noscript&gt;&lt;iframe src='...'&gt;&lt;/iframe&gt;&lt;/noscript&gt;"><?php echo htmlspecialchars($verificationCodes['body_codes'] ?? ''); ?></textarea>
                <div class="help">&lt;body&gt; 태그 직후에 추가할 HTML 코드를 입력하세요.</div>
            </div>
            
            <div class="form-group">
                <label for="footer_codes">Body 종료 부분 추가 코드</label>
                <textarea id="footer_codes" name="footer_codes" rows="5" placeholder="&lt;script&gt; 태그 등을 입력하세요.&#10;예: &lt;script&gt;...&lt;/script&gt;"><?php echo htmlspecialchars($verificationCodes['footer_codes'] ?? ''); ?></textarea>
                <div class="help">&lt;/body&gt; 태그 직전에 추가할 HTML 코드를 입력하세요. (Google Analytics, Tag Manager 등)</div>
            </div>
        </div>
        
        <div style="margin-top: 24px;">
            <button type="submit" class="btn btn-primary">저장</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
