<?php
/**
 * 약관 내용 표시 페이지
 * 관리자 페이지에서 설정한 약관 내용을 표시합니다.
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/site-settings.php';
require_once __DIR__ . '/../includes/data/terms-functions.php';

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

// information_security는 기존 방식 사용
if ($type === 'information_security') {
    $siteSettings = getSiteSettings();
    $footer = $siteSettings['footer'] ?? [];
    $terms = $footer['terms'] ?? [];
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
    $versionList = [];
    $currentVersion = null;
} else {
    // terms_of_service, privacy_policy는 버전 관리 시스템 사용
    $version = $_GET['version'] ?? null;
    $date = $_GET['date'] ?? null;
    
    // 버전 목록 가져오기 (드롭다운용)
    $versionList = getTermsVersionList($type, true);
    
    // 표시할 버전 결정
    if ($version) {
        $currentVersion = getTermsVersionByVersion($type, $version);
    } elseif ($date) {
        $currentVersion = getTermsVersionByDate($type, $date);
    } else {
        $currentVersion = getActiveTermsVersion($type);
    }
    
    if (!$currentVersion) {
        // 버전 관리 시스템에 데이터가 없으면 기존 방식으로 폴백
        $siteSettings = getSiteSettings();
        $footer = $siteSettings['footer'] ?? [];
        $terms = $footer['terms'] ?? [];
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
        $versionList = [];
    } else {
        $termTitle = $currentVersion['title'];
        $termContent = $currentVersion['content'];
    }
}
?>

<main class="main-content">
    <div class="content-layout" style="max-width: 900px; margin: 40px auto; padding: 40px 20px;">
        <div style="background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; padding-bottom: 16px; border-bottom: 2px solid #e5e7eb; flex-wrap: wrap; gap: 16px;">
                <h1 style="font-size: 32px; font-weight: 700; color: #1f2937; margin: 0;">
                    <?php echo htmlspecialchars($termTitle); ?>
                </h1>
                
                <?php if (!empty($versionList) && isset($currentVersion)): ?>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <label for="version-select" style="font-size: 14px; font-weight: 600; color: #374151; white-space: nowrap;">시행일자:</label>
                        <select id="version-select" onchange="changeVersion(this.value)" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; cursor: pointer; min-width: 180px;">
                            <?php foreach ($versionList as $ver): ?>
                                <option value="<?php echo htmlspecialchars($ver['version']); ?>" 
                                    <?php echo (isset($currentVersion) && $currentVersion['id'] == $ver['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ver['effective_date']); ?> (<?php echo htmlspecialchars($ver['version']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (isset($currentVersion) && $currentVersion): ?>
                <div style="margin-bottom: 20px; padding: 12px 16px; background: #f9fafb; border-radius: 8px; font-size: 14px; color: #6b7280;">
                    <strong>시행일자:</strong> <?php echo htmlspecialchars($currentVersion['effective_date']); ?>
                    <?php if ($currentVersion['announcement_date']): ?>
                        | <strong>공고일자:</strong> <?php echo htmlspecialchars($currentVersion['announcement_date']); ?>
                    <?php endif; ?>
                    | <strong>버전:</strong> <?php echo htmlspecialchars($currentVersion['version']); ?>
                </div>
            <?php endif; ?>
            
            <div class="terms-content" style="font-size: 15px; line-height: 1.8; color: #374151;">
                <?php echo $termContent; ?>
            </div>
        </div>
    </div>
</main>

<?php if (!empty($versionList) && isset($currentVersion)): ?>
<script>
function changeVersion(version) {
    const url = new URL(window.location.href);
    url.searchParams.set('version', version);
    window.location.href = url.toString();
}
</script>
<?php endif; ?>

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
    margin: 24px 0;
    font-size: 14px;
    background-color: #ffffff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.terms-content table th,
.terms-content table td {
    border: 1px solid #e5e7eb;
    padding: 14px 16px;
    text-align: left;
    vertical-align: top;
    line-height: 1.6;
}

.terms-content table th {
    background-color: #f3f4f6;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
    border-bottom: 2px solid #e5e7eb;
}

.terms-content table tbody tr {
    transition: background-color 0.15s ease;
}

.terms-content table tbody tr:hover {
    background-color: #f9fafb;
}

.terms-content table tbody tr:last-child td {
    border-bottom: 1px solid #e5e7eb;
}

@media (max-width: 767px) {
    .content-layout {
        padding: 20px 16px !important;
    }
    
    .terms-content {
        font-size: 14px;
    }
    
    .terms-content table {
        font-size: 13px;
        margin: 20px 0;
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .terms-content table th,
    .terms-content table td {
        padding: 10px 12px;
        font-size: 13px;
    }
    
    .terms-content table th {
        font-size: 13px;
        white-space: nowrap;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
