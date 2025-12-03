<?php
/**
 * 통신사폰 주문 카드 헤더 컴포넌트 (독립 레이아웃)
 * 로고, 평점, 공유 버튼
 * 
 * @param array $phone 통신사폰 데이터
 */
if (!isset($phone)) {
    $phone = [];
}
$provider = $phone['company_name'] ?? '이야기모바일';
$rating = $phone['rating'] ?? '4.5';
$phone_id = $phone['id'] ?? 0;
$is_sold_out = $phone['is_sold_out'] ?? false; // 판매종료 여부
$share_url = '/MVNO/mno/mno-phone-detail.php?id=' . $phone_id;
?>

<!-- 헤더: 로고, 평점, 공유 -->
<div class="mno-order-card-top-header">
    <div class="mno-order-provider-rating-group">
        <span class="mno-order-provider-logo-text"><?php echo htmlspecialchars($provider); ?></span>
        <div class="mno-order-rating-group">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#FCC419" stroke="#FCC419" stroke-width="0.5"/>
            </svg>
            <span class="mno-order-rating-text"><?php echo htmlspecialchars($rating); ?></span>
        </div>
    </div>
    <?php if (!$is_sold_out): ?>
    <div class="mno-order-badge-favorite-group">
        <?php
        $button_class = 'mno-order-share-btn-inline';
        include __DIR__ . '/share-button.php';
        ?>
    </div>
    <?php endif; ?>
</div>

