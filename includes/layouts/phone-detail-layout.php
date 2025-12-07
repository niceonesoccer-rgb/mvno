<?php
/**
 * 통신사폰 상세 레이아웃
 * mno-phone-detail.php에서 사용
 * 
 * @param array $phone 통신사폰 데이터
 */
if (!isset($phone)) {
    $phone = [];
}
$layout_type = 'detail';
$card_wrapper_class = '';
?>

<div class="content-layout plan-detail-header-section">
    <?php include __DIR__ . '/../components/phone-card.php'; ?>
</div>

<!-- 신청하기 섹션 (하단 고정) -->
<section class="plan-detail-apply-section">
    <div class="content-layout">
        <div class="plan-apply-content">
            <div class="plan-price-info">
                <div class="plan-price-main">
                    <span class="plan-price-amount">월 <?php echo htmlspecialchars($phone['monthly_price'] ?? '109,000원'); ?></span>
                </div>
                <span class="plan-price-note">유지기간 <?php echo htmlspecialchars($phone['maintenance_period'] ?? '185일'); ?></span>
            </div>
            <button class="plan-apply-btn" id="phoneApplyBtn">신청하기</button>
        </div>
    </div>
</section>

















