<?php
/**
 * 요금제 상세 레이아웃
 * plan-detail.php에서 사용
 * 
 * @param array $plan 요금제 데이터
 */
if (!isset($plan)) {
    $plan = [];
}
$layout_type = 'detail';
$card_wrapper_class = '';
?>

<div class="content-layout plan-detail-header-section">
    <?php include __DIR__ . '/../components/plan-card.php'; ?>
</div>

<!-- 신청하기 섹션 (하단 고정) -->
<section class="plan-detail-apply-section">
    <div class="content-layout">
        <div class="plan-apply-content">
            <div class="plan-price-info">
                <div class="plan-price-main">
                    <span class="plan-price-amount"><?php echo htmlspecialchars($plan['price_main'] ?? '월 17,000원'); ?></span>
                </div>
                <span class="plan-price-note"><?php echo htmlspecialchars($plan['price_after'] ?? '7개월 이후 42,900원'); ?></span>
            </div>
            <button class="plan-apply-btn" id="planApplyBtn">신청하기</button>
        </div>
    </div>
</section>






















