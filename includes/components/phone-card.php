<?php
/**
 * 통신사폰 카드 전체 컴포넌트
 * mno.php, wishlist.php에서 사용 (기존 레이아웃)
 * 
 * @param array $phone 통신사폰 데이터
 * @param string $layout_type 'list' 또는 'detail'
 * @param string $card_wrapper_class 추가 클래스명
 */
if (!isset($phone)) {
    $phone = [];
}
$layout_type = $layout_type ?? 'list';
$card_wrapper_class = $card_wrapper_class ?? '';
$phone_id = $phone['id'] ?? 0;
$is_link = ($layout_type === 'list' && $phone_id > 0);

// path-config.php가 로드되지 않았으면 로드
if (!function_exists('getAssetPath')) {
    require_once __DIR__ . '/../data/path-config.php';
}

// 상세 페이지 링크 설정
if (isset($phone['link_url']) && !empty($phone['link_url'])) {
    $link_url = $phone['link_url'];
    // 전체 URL이 아니면 getAssetPath 적용
    if (!preg_match('/^https?:\/\//', $link_url)) {
        // /MVNO/로 시작하는 경우 제거 후 getAssetPath 적용
        if (strpos($link_url, '/MVNO/') === 0) {
            $link_url = getAssetPath(str_replace('/MVNO', '', $link_url));
        } elseif (strpos($link_url, 'MVNO/') === 0) {
            $link_url = getAssetPath(str_replace('MVNO/', '', $link_url));
        } elseif (strpos($link_url, '/') === 0) {
            // 이미 /로 시작하는 경로면 getAssetPath 적용
            $link_url = getAssetPath($link_url);
        } else {
            // 상대 경로인 경우
            $link_url = getAssetPath('/' . $link_url);
        }
    }
} else {
    // link_url이 없으면 기본 상세 페이지 링크 생성
    $link_url = getAssetPath('/mno/mno-phone-detail.php?id=' . urlencode($phone_id));
}

// 푸터에서 공유 링크로 사용할 수 있도록 link_url을 phone 배열에 추가
$phone['link_url'] = $link_url;

// phone-card-header.php에서 사용할 변수는 이미 $phone에 있음
?>

<article class="basic-plan-card <?php echo htmlspecialchars($card_wrapper_class); ?>">
    <?php if ($is_link): ?>
    <a href="<?php echo htmlspecialchars($link_url); ?>" class="plan-card-link">
    <?php else: ?>
    <div class="plan-card-link">
    <?php endif; ?>
        <div class="plan-card-main-content">
            <div class="plan-card-header-body-frame">
                <?php include __DIR__ . '/phone-card-header.php'; ?>
                <?php include __DIR__ . '/phone-card-body.php'; ?>
            </div>
        </div>
    <?php if ($is_link): ?>
    </a>
    <?php else: ?>
    </div>
    <?php endif; ?>
    
    <?php include __DIR__ . '/phone-card-footer.php'; ?>
</article>
