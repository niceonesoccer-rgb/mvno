<?php
/**
 * SEO 설정 관리 페이지 (탭 방식)
 * - 카테고리별 SEO 관리
 * - 검색엔진 검증 코드 관리
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

// 초기 탭 설정 (URL 파라미터 또는 기본값)
$initialTab = $_GET['tab'] ?? 'home';
$validTabs = ['home', 'mno-sim', 'mvno', 'mno', 'internets', 'verification'];
if (!in_array($initialTab, $validTabs)) {
    $initialTab = 'home';
}

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_seo'])) {
        // 카테고리별 SEO 저장
        $categorySEO = [];
        
        $categories = ['home', 'mno-sim', 'mvno', 'mno', 'internets'];
        foreach ($categories as $category) {
            $categorySEO[$category] = [
                'title' => trim($_POST[$category . '_title'] ?? ''),
                'description' => trim($_POST[$category . '_description'] ?? ''),
                'keywords' => trim($_POST[$category . '_keywords'] ?? ''),
                'og_title' => trim($_POST[$category . '_og_title'] ?? ''),
                'og_description' => trim($_POST[$category . '_og_description'] ?? ''),
                'og_image' => trim($_POST[$category . '_og_image'] ?? ''),
                'canonical' => trim($_POST[$category . '_canonical'] ?? ''),
            ];
        }
        
        if (saveCategorySEO($categorySEO)) {
            $success = 'SEO 설정이 저장되었습니다.';
            header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=' . urlencode($initialTab) . '&success=saved');
            exit;
        } else {
            $error = 'SEO 설정 저장에 실패했습니다.';
        }
    } elseif (isset($_POST['save_verification'])) {
        // 검증 코드 저장
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
            header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=verification&success=saved');
            exit;
        } else {
            $error = '검증 코드 저장에 실패했습니다.';
        }
    }
}

// 성공 메시지 처리
if (isset($_GET['success']) && $_GET['success'] === 'saved') {
    $success = '설정이 저장되었습니다.';
}

// 카테고리별 SEO 설정 가져오기
$categorySEO = getCategorySEO();

// 검증 코드 가져오기
$verificationCodes = getSearchEngineVerification();

// 카테고리 이름 매핑
$categoryNames = [
    'home' => '홈',
    'mno-sim' => '통신사단독유심',
    'mvno' => '알뜰폰',
    'mno' => '통신사폰',
    'internets' => '인터넷',
];

$currentPage = 'seo-settings.php';
include __DIR__ . '/../includes/admin-header.php';
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
        flex-wrap: wrap;
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
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
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
        min-height: 90px;
        resize: vertical;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .help {
        font-size: 13px;
        color: #6b7280;
        margin-top: 6px;
        line-height: 1.5;
    }
    
    .alert {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
    }
    
    .alert-error {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }
    
    .alert-success {
        background: #d1fae5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }
    
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: #6366f1;
        color: white;
    }
    
    .btn-primary:hover {
        background: #4f46e5;
    }
    
    code {
        background: #f3f4f6;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 13px;
        font-family: 'Courier New', monospace;
        color: #1f2937;
    }
</style>

<div class="admin-content">
    <div class="page-header">
        <h1>SEO 설정</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" id="seoSettingsForm">
        <input type="hidden" name="current_tab" id="current_tab_input" value="<?php echo htmlspecialchars($initialTab); ?>">
        
        <div class="tabs">
            <button type="button" class="tab <?php echo $initialTab === 'home' ? 'active' : ''; ?>" onclick="switchTab('home')">홈</button>
            <button type="button" class="tab <?php echo $initialTab === 'mno-sim' ? 'active' : ''; ?>" onclick="switchTab('mno-sim')">통신사단독유심</button>
            <button type="button" class="tab <?php echo $initialTab === 'mvno' ? 'active' : ''; ?>" onclick="switchTab('mvno')">알뜰폰</button>
            <button type="button" class="tab <?php echo $initialTab === 'mno' ? 'active' : ''; ?>" onclick="switchTab('mno')">통신사폰</button>
            <button type="button" class="tab <?php echo $initialTab === 'internets' ? 'active' : ''; ?>" onclick="switchTab('internets')">인터넷</button>
            <button type="button" class="tab <?php echo $initialTab === 'verification' ? 'active' : ''; ?>" onclick="switchTab('verification')">검증 코드</button>
        </div>

        <?php foreach ($categoryNames as $category => $categoryName): ?>
            <?php $seo = $categorySEO[$category] ?? []; ?>
            <div id="tab-<?php echo $category; ?>" class="tab-content <?php echo $initialTab === $category ? 'active' : ''; ?>">
                <div class="card">
                    <div class="card-title"><?php echo htmlspecialchars($categoryName); ?> 카테고리 SEO</div>
                    
                    <div class="form-group">
                        <label for="<?php echo $category; ?>_title">페이지 제목 (Title)</label>
                        <input type="text" id="<?php echo $category; ?>_title" name="<?php echo $category; ?>_title" value="<?php echo htmlspecialchars($seo['title'] ?? ''); ?>" placeholder="예: 알뜰폰 요금제 비교 | 유심킹">
                        <div class="help">검색엔진에 표시될 페이지 제목입니다. 60자 이내 권장.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="<?php echo $category; ?>_description">메타 설명 (Description)</label>
                        <textarea id="<?php echo $category; ?>_description" name="<?php echo $category; ?>_description" rows="3" placeholder="예: 알뜰폰 요금제를 비교하고 가장 저렴한 요금제를 찾아 신청하세요. SKT, KT, LG U+ 요금제 비교 제공."><?php echo htmlspecialchars($seo['description'] ?? ''); ?></textarea>
                        <div class="help">검색 결과에 표시될 설명입니다. 160자 이내 권장.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="<?php echo $category; ?>_keywords">키워드 (Keywords)</label>
                        <input type="text" id="<?php echo $category; ?>_keywords" name="<?php echo $category; ?>_keywords" value="<?php echo htmlspecialchars($seo['keywords'] ?? ''); ?>" placeholder="예: 알뜰폰, 요금제, 비교, 신청, SKT, KT, LG U+">
                        <div class="help">쉼표(,)로 구분하여 키워드를 입력하세요.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="<?php echo $category; ?>_og_title">OG 제목 (Open Graph Title)</label>
                        <input type="text" id="<?php echo $category; ?>_og_title" name="<?php echo $category; ?>_og_title" value="<?php echo htmlspecialchars($seo['og_title'] ?? ''); ?>" placeholder="SNS 공유 시 표시될 제목 (선택사항)">
                        <div class="help">SNS 공유 시 표시될 제목입니다. 비워두면 페이지 제목이 사용됩니다.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="<?php echo $category; ?>_og_description">OG 설명 (Open Graph Description)</label>
                        <textarea id="<?php echo $category; ?>_og_description" name="<?php echo $category; ?>_og_description" rows="2" placeholder="SNS 공유 시 표시될 설명 (선택사항)"><?php echo htmlspecialchars($seo['og_description'] ?? ''); ?></textarea>
                        <div class="help">SNS 공유 시 표시될 설명입니다. 비워두면 메타 설명이 사용됩니다.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="<?php echo $category; ?>_og_image">OG 이미지 (Open Graph Image)</label>
                        <input type="text" id="<?php echo $category; ?>_og_image" name="<?php echo $category; ?>_og_image" value="<?php echo htmlspecialchars($seo['og_image'] ?? ''); ?>" placeholder="예: https://example.com/image.jpg 또는 /images/og-image.jpg">
                        <div class="help">SNS 공유 시 표시될 이미지 URL입니다. 권장 크기: 1200x630px</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="<?php echo $category; ?>_canonical">Canonical URL</label>
                        <input type="text" id="<?php echo $category; ?>_canonical" name="<?php echo $category; ?>_canonical" value="<?php echo htmlspecialchars($seo['canonical'] ?? ''); ?>" placeholder="예: https://example.com/category/">
                        <div class="help">정규화된 URL입니다. 비워두면 현재 URL이 사용됩니다.</div>
                    </div>
                    
                    <div style="margin-top: 24px;">
                        <button type="button" onclick="submitCategorySEO()" class="btn btn-primary">저장</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- 검증 코드 탭 -->
        <div id="tab-verification" class="tab-content <?php echo $initialTab === 'verification' ? 'active' : ''; ?>">
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
            
            <div class="card">
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
                
                <div style="margin-top: 24px;">
                    <button type="button" onclick="submitVerification()" class="btn btn-primary">저장</button>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Hidden form for category SEO -->
    <form method="POST" id="categorySEOForm" style="display: none;">
        <input type="hidden" name="save_seo" value="1">
        <?php foreach ($categoryNames as $category => $categoryName): ?>
            <input type="hidden" name="<?php echo $category; ?>_title" id="hidden_<?php echo $category; ?>_title">
            <input type="hidden" name="<?php echo $category; ?>_description" id="hidden_<?php echo $category; ?>_description">
            <input type="hidden" name="<?php echo $category; ?>_keywords" id="hidden_<?php echo $category; ?>_keywords">
            <input type="hidden" name="<?php echo $category; ?>_og_title" id="hidden_<?php echo $category; ?>_og_title">
            <input type="hidden" name="<?php echo $category; ?>_og_description" id="hidden_<?php echo $category; ?>_og_description">
            <input type="hidden" name="<?php echo $category; ?>_og_image" id="hidden_<?php echo $category; ?>_og_image">
            <input type="hidden" name="<?php echo $category; ?>_canonical" id="hidden_<?php echo $category; ?>_canonical">
        <?php endforeach; ?>
    </form>
    
    <!-- Hidden form for verification -->
    <form method="POST" id="verificationForm" style="display: none;">
        <input type="hidden" name="save_verification" value="1">
        <input type="hidden" name="google_code" id="hidden_google_code">
        <input type="hidden" name="naver_code" id="hidden_naver_code">
        <input type="hidden" name="bing_code" id="hidden_bing_code">
        <input type="hidden" name="yandex_code" id="hidden_yandex_code">
        <input type="hidden" name="head_codes" id="hidden_head_codes">
        <input type="hidden" name="body_codes" id="hidden_body_codes">
        <input type="hidden" name="footer_codes" id="hidden_footer_codes">
    </form>
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
    }
    
    // current_tab hidden input 업데이트
    const currentTabInput = document.getElementById('current_tab_input');
    if (currentTabInput) {
        currentTabInput.value = tabName;
    }
    
    // URL 업데이트 (새로고침 시 탭 유지)
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tabName);
    window.history.replaceState(null, null, url.toString());
}

function submitCategorySEO() {
    // 모든 카테고리의 입력값을 hidden form으로 복사
    const categories = ['home', 'mno-sim', 'mvno', 'mno', 'internets'];
    categories.forEach(category => {
        const fields = ['title', 'description', 'keywords', 'og_title', 'og_description', 'og_image', 'canonical'];
        fields.forEach(field => {
            const input = document.getElementById(category + '_' + field);
            const hiddenInput = document.getElementById('hidden_' + category + '_' + field);
            if (input && hiddenInput) {
                hiddenInput.value = input.value;
            }
        });
    });
    
    // 현재 탭 정보 추가
    const currentTabInput = document.getElementById('current_tab_input');
    const url = new URL(window.location.href);
    if (currentTabInput) {
        url.searchParams.set('tab', currentTabInput.value);
    }
    
    // hidden form 제출
    const form = document.getElementById('categorySEOForm');
    form.action = url.toString();
    form.submit();
}

function submitVerification() {
    // 검증 코드 입력값을 hidden form으로 복사
    const fields = ['google_code', 'naver_code', 'bing_code', 'yandex_code', 'head_codes', 'body_codes', 'footer_codes'];
    fields.forEach(field => {
        const input = document.getElementById(field);
        const hiddenInput = document.getElementById('hidden_' + field);
        if (input && hiddenInput) {
            hiddenInput.value = input.value;
        }
    });
    
    // 현재 탭 정보 추가
    const url = new URL(window.location.href);
    url.searchParams.set('tab', 'verification');
    
    // hidden form 제출
    const form = document.getElementById('verificationForm');
    form.action = url.toString();
    form.submit();
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
