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
        <button class="mno-order-share-btn-inline" aria-label="공유하기" data-share-url="<?php echo htmlspecialchars($share_url); ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 8C19.6569 8 21 6.65685 21 5C21 3.34315 19.6569 2 18 2C16.3431 2 15 3.34315 15 5C15 5.12549 15.0077 5.24896 15.0227 5.36986L8.08261 9.79866C7.54305 9.29209 6.80891 9 6 9C4.34315 9 3 10.3431 3 12C3 13.6569 4.34315 15 6 15C6.80891 15 7.54305 14.7079 8.08261 14.2013L15.0227 18.6301C15.0077 18.751 15 18.8745 15 19C15 20.6569 16.3431 22 18 22C19.6569 22 21 20.6569 21 19C21 17.3431 19.6569 16 18 16C17.1911 16 16.457 16.2921 15.9174 16.7987L8.97727 12.3699C8.99227 12.249 9 12.1255 9 12C9 11.8745 8.99227 11.751 8.97727 11.6301L15.9174 7.20134C16.457 7.70791 17.1911 8 18 8Z" fill="#868E96"/>
            </svg>
        </button>
    </div>
    <?php endif; ?>
</div>

