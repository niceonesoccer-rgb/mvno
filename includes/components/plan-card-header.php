<?php
/**
 * 요금제 카드 헤더 컴포넌트
 * 로고, 평점, 찜 버튼, 공유 버튼(상세 페이지용)
 * 
 * @param array $plan 요금제 데이터
 * @param string $layout_type 'list' 또는 'detail'
 */
if (!isset($plan)) {
    $plan = [];
}
$layout_type = $layout_type ?? 'list';
$provider = $plan['provider'] ?? '쉐이크모바일';
$rating = $plan['rating'] ?? '4.3';
$plan_id = $plan['id'] ?? 0;
$share_url = $plan['link_url'] ?? '/MVNO/mvno/mvno-plan-detail.php?id=' . $plan_id;
?>

<!-- 헤더: 로고, 평점, 배지, 찜 -->
<div class="plan-card-top-header">
    <div class="plan-provider-rating-group">
        <span class="plan-provider-logo-text"><?php echo htmlspecialchars($provider); ?></span>
        <div class="plan-rating-group">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#EF4444"/>
            </svg>
            <span class="plan-rating-text"><?php echo htmlspecialchars($rating); ?></span>
        </div>
    </div>
    <div class="plan-badge-favorite-group">
        <?php
        $button_id = ($layout_type === 'detail') ? 'planFavoriteBtn' : '';
        $item_id = $plan_id;
        $item_type = 'plan';
        $is_favorited = $plan['is_favorited'] ?? false;
        include __DIR__ . '/favorite-button.php';
        ?>
        <?php
        if ($plan_id > 0) {
            $button_id = ($layout_type === 'detail') ? 'planShareBtn' : '';
            include __DIR__ . '/share-button.php';
        }
        ?>
    </div>
</div>

