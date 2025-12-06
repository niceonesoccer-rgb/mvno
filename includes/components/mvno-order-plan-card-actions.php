<?php
/**
 * 요금제 주문 카드 액션 영역 컴포넌트
 * 신청일, 개통일, 리뷰쓰기 버튼
 * 
 * @param array $plan 요금제 데이터
 */
if (!isset($plan)) {
    $plan = [];
}
$order_date = $plan['order_date'] ?? '';
$activation_date = $plan['activation_date'] ?? '';
$plan_id = $plan['id'] ?? 0;
$has_review = $plan['has_review'] ?? false; // 리뷰 작성 여부
$is_activated = !empty($activation_date); // 개통 여부
$consultation_url = $plan['consultation_url'] ?? ''; // 상담 URL
?>

<!-- 액션 영역: 신청일 / 개통일 / 리뷰쓰기 또는 업체 문의하기 -->
<div class="mvno-order-card-actions">
    <div class="mvno-order-card-actions-content">
        <!-- 신청일 -->
        <div class="mvno-order-action-item">
            <span class="mvno-order-action-label">신청일</span>
            <span class="mvno-order-action-value">
                <?php echo htmlspecialchars($order_date ?: '-'); ?>
            </span>
        </div>
        
        <?php if ($is_activated): ?>
        <!-- 구분선 -->
        <div class="mvno-order-action-divider"></div>
        
        <!-- 개통일 -->
        <div class="mvno-order-action-item">
            <span class="mvno-order-action-label">개통일</span>
            <span class="mvno-order-action-value">
                <?php echo htmlspecialchars($activation_date); ?>
            </span>
        </div>
        <?php endif; ?>
        
        <?php 
        // 포인트 사용 내역 표시
        $point_used = $plan['point_used'] ?? 0;
        $point_used_date = $plan['point_used_date'] ?? '';
        if ($point_used > 0): 
        ?>
        <!-- 구분선 -->
        <div class="mvno-order-action-divider"></div>
        
        <!-- 포인트 사용 내역 -->
        <div class="mvno-order-action-item">
            <span class="mvno-order-action-label">포인트 사용</span>
            <span class="mvno-order-action-value" style="color: #6366f1; font-weight: 600;">
                -<?php echo number_format($point_used); ?>원
            </span>
        </div>
        <?php endif; ?>
        
        <?php if (!$is_activated || !$has_review): ?>
        <!-- 구분선 -->
        <div class="mvno-order-action-divider"></div>
        
        <!-- 리뷰쓰기 버튼 또는 업체 문의하기 버튼 -->
        <div class="mvno-order-action-item">
            <?php if ($is_activated): ?>
                <!-- 개통된 경우: 리뷰쓰기 버튼 (리뷰 작성 후에는 헤더의 점 3개 메뉴 사용) -->
                <?php if (!$has_review): ?>
                    <button type="button" class="mvno-order-review-btn" data-plan-id="<?php echo $plan_id; ?>">
                        리뷰쓰기
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <!-- 개통 안된 경우: 개통 문의 버튼 -->
                <?php if ($consultation_url): ?>
                    <a href="<?php echo htmlspecialchars($consultation_url); ?>" target="_blank" class="mvno-order-inquiry-btn">
                        개통 문의
                    </a>
                <?php else: ?>
                    <button type="button" class="mvno-order-inquiry-btn" disabled>
                        개통 문의
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

