<?php
/**
 * 요금제 주문 카드 헤더 컴포넌트 (독립 레이아웃)
 * 로고, 평점, 점 3개 메뉴
 * 
 * @param array $plan 요금제 데이터
 */
if (!isset($plan)) {
    $plan = [];
}
$provider = $plan['provider'] ?? '쉐이크모바일';
$rating = $plan['rating'] ?? '4.3';
$plan_id = $plan['id'] ?? 0;
$has_review = $plan['has_review'] ?? false; // 리뷰 작성 여부
$is_activated = !empty($plan['activation_date'] ?? ''); // 개통 여부
?>

<!-- 헤더: 로고, 평점, 점 3개 메뉴 -->
<div class="mvno-order-card-top-header">
    <div class="mvno-order-provider-rating-group">
        <span class="mvno-order-provider-logo-text"><?php echo htmlspecialchars($provider); ?></span>
        <div class="mvno-order-rating-group">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#EF4444" stroke="#EF4444" stroke-width="0.5"/>
            </svg>
            <span class="mvno-order-rating-text"><?php echo htmlspecialchars($rating); ?></span>
        </div>
    </div>
    <div class="mvno-order-menu-group">
        <?php if ($is_activated && $has_review): ?>
        <!-- 리뷰 작성 후: 점 3개 메뉴 버튼 -->
        <button type="button" class="mvno-order-menu-btn" data-plan-id="<?php echo $plan_id; ?>" aria-label="메뉴">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="6" r="1.5" fill="#868E96"/>
                <circle cx="12" cy="12" r="1.5" fill="#868E96"/>
                <circle cx="12" cy="18" r="1.5" fill="#868E96"/>
            </svg>
        </button>
        <!-- 드롭다운 메뉴 -->
        <div class="mvno-order-menu-dropdown" id="mvno-order-menu-<?php echo $plan_id; ?>" style="display: none;">
            <button type="button" class="mvno-order-menu-item mvno-order-review-edit-btn" data-plan-id="<?php echo $plan_id; ?>">
                수정
            </button>
            <button type="button" class="mvno-order-menu-item mvno-order-review-delete-btn" data-plan-id="<?php echo $plan_id; ?>">
                삭제
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

