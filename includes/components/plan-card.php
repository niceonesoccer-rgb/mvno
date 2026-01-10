<?php
/**
 * 요금제 카드 전체 컴포넌트
 * 
 * @param array $plan 요금제 데이터
 * @param string $layout_type 'list' 또는 'detail'
 * @param string $card_wrapper_class 추가 클래스명
 */
if (!isset($plan)) {
    $plan = [];
}
$layout_type = $layout_type ?? 'list';
$card_wrapper_class = $card_wrapper_class ?? '';
$plan_id = $plan['id'] ?? 0;
$is_link = ($layout_type === 'list' && $plan_id > 0);

// path-config.php가 로드되지 않았으면 로드
if (!function_exists('getAssetPath')) {
    require_once __DIR__ . '/../data/path-config.php';
}

// 상품 타입에 따라 올바른 상세 페이지 링크 설정
if (isset($plan['link_url']) && !empty($plan['link_url'])) {
    $link_url = $plan['link_url'];
    // 이미 전체 URL이 아니고 /MVNO/로 시작하면 getAssetPath 적용
    if (!preg_match('/^https?:\/\//', $link_url) && strpos($link_url, '/MVNO/') === 0) {
        $link_url = getAssetPath(str_replace('/MVNO', '', $link_url));
    } elseif (!preg_match('/^https?:\/\//', $link_url) && strpos($link_url, '/') === 0) {
        $link_url = getAssetPath($link_url);
    }
} else {
    // item_type 또는 plan_name으로 통신사단독유심 여부 확인
    $item_type = $plan['item_type'] ?? '';
    $plan_name = $plan['plan_name'] ?? '';
    $is_mno_sim = ($item_type === 'mno-sim' || !empty($plan_name));
    
    if ($is_mno_sim) {
        $link_url = getAssetPath('/mno-sim/mno-sim-detail.php?id=' . urlencode($plan_id));
    } else {
        $link_url = getAssetPath('/mvno/mvno-plan-detail.php?id=' . urlencode($plan_id));
    }
}

// 푸터에서 공유 링크로 사용할 수 있도록 link_url을 plan 배열에 추가
$plan['link_url'] = $link_url;
?>

<article class="basic-plan-card <?php echo htmlspecialchars($card_wrapper_class); ?>" data-plan-id="<?php echo $plan_id; ?>">
    <?php if ($is_link): ?>
    <a href="<?php echo htmlspecialchars($link_url); ?>" class="plan-card-link">
    <?php else: ?>
    <div class="plan-card-link">
    <?php endif; ?>
        <div class="plan-card-main-content">
            <div class="plan-card-header-body-frame">
                <?php include __DIR__ . '/plan-card-header.php'; ?>
                <?php include __DIR__ . '/plan-card-body.php'; ?>
            </div>
        </div>
    <?php if ($is_link): ?>
    </a>
    <?php else: ?>
    </div>
    <?php endif; ?>
    
    <?php include __DIR__ . '/plan-card-footer.php'; ?>
</article>

