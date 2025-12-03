<?php
/**
 * 요금제 주문 카드 헤더 컴포넌트 (독립 레이아웃)
 * 로고, 평점, 공유 버튼
 * 
 * @param array $plan 요금제 데이터
 */
if (!isset($plan)) {
    $plan = [];
}
$provider = $plan['provider'] ?? '쉐이크모바일';
$rating = $plan['rating'] ?? '4.3';
$plan_id = $plan['id'] ?? 0;
$is_sold_out = $plan['is_sold_out'] ?? false; // 판매종료 여부
$share_url = '/MVNO/mvno/mvno-plan-detail.php?id=' . $plan_id;
?>

<!-- 헤더: 로고, 평점, 공유 -->
<div class="mvno-order-card-top-header">
    <div class="mvno-order-provider-rating-group">
        <span class="mvno-order-provider-logo-text"><?php echo htmlspecialchars($provider); ?></span>
        <div class="mvno-order-rating-group">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#FCC419" stroke="#FCC419" stroke-width="0.5"/>
            </svg>
            <span class="mvno-order-rating-text"><?php echo htmlspecialchars($rating); ?></span>
        </div>
    </div>
    <div class="mvno-order-badge-share-group">
        <?php
        if (!$is_sold_out) {
            $button_class = 'mvno-order-share-btn-inline';
            include __DIR__ . '/share-button.php';
        }
        ?>
    </div>
</div>

