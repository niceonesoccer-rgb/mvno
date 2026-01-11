<?php
/**
 * 카테고리별 SEO 관리 페이지
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_seo'])) {
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
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=saved');
        exit;
    } else {
        $error = 'SEO 설정 저장에 실패했습니다.';
    }
}

// 성공 메시지 처리
if (isset($_GET['success']) && $_GET['success'] === 'saved') {
    $success = 'SEO 설정이 저장되었습니다.';
}

// 카테고리별 SEO 설정 가져오기
$categorySEO = getCategorySEO();

// 카테고리 이름 매핑
$categoryNames = [
    'home' => '홈',
    'mno-sim' => '통신사단독유심',
    'mvno' => '알뜰폰',
    'mno' => '통신사폰',
    'internets' => '인터넷',
];

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1>카테고리별 SEO 관리</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" id="categorySEOForm">
        <input type="hidden" name="save_seo" value="1">
        
        <?php foreach ($categoryNames as $category => $categoryName): ?>
            <?php $seo = $categorySEO[$category] ?? []; ?>
            <div class="card" style="margin-bottom: 24px;">
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
            </div>
        <?php endforeach; ?>
        
        <div style="margin-top: 24px;">
            <button type="submit" class="btn btn-primary">저장</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
