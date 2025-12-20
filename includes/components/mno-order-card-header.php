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
$company_name_raw = $phone['company_name'] ?? '이야기모바일';
// "스마트모바일" → "스마트"로 변환
$provider = $company_name_raw;
if (strpos($company_name_raw, '스마트모바일') !== false) {
    $provider = '스마트';
} elseif (strpos($company_name_raw, '모바일') !== false) {
    // "XX모바일" 형식에서 "XX"만 추출
    $provider = str_replace('모바일', '', $company_name_raw);
}
$rating = $phone['rating'] ?? '4.5';
$phone_id = $phone['id'] ?? 0;
$is_sold_out = $phone['is_sold_out'] ?? false; // 판매종료 여부
$share_url = '/MVNO/mno/mno-phone-detail.php?id=' . $phone_id;
?>

<?php
$has_review = $phone['has_review'] ?? false;
$is_activated = !empty($phone['activation_date'] ?? '');
?>
<!-- 헤더: 로고, 평점, 점 3개 메뉴 -->
<div class="mno-order-card-top-header">
    <div class="mno-order-provider-rating-group">
        <span class="mno-order-provider-logo-text"><?php echo htmlspecialchars($provider); ?></span>
        <div class="mno-order-rating-group">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#EF4444" stroke="#EF4444" stroke-width="0.5"/>
            </svg>
            <span class="mno-order-rating-text"><?php echo htmlspecialchars($rating); ?></span>
        </div>
    </div>
    <div class="mno-order-menu-group">
        <?php if ($is_activated && $has_review): ?>
        <!-- 리뷰 작성 후: 점 3개 메뉴 버튼 -->
        <button type="button" class="mno-order-menu-btn" data-phone-id="<?php echo $phone_id; ?>" aria-label="메뉴">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="6" r="1.5" fill="#868E96"/>
                <circle cx="12" cy="12" r="1.5" fill="#868E96"/>
                <circle cx="12" cy="18" r="1.5" fill="#868E96"/>
            </svg>
        </button>
        <!-- 드롭다운 메뉴 -->
        <div class="mno-order-menu-dropdown" id="mno-order-menu-<?php echo $phone_id; ?>" style="display: none;">
            <button type="button" class="mno-order-menu-item mno-order-review-edit-btn" data-phone-id="<?php echo $phone_id; ?>">
                수정
            </button>
            <button type="button" class="mno-order-menu-item mno-order-review-delete-btn" data-phone-id="<?php echo $phone_id; ?>">
                삭제
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

