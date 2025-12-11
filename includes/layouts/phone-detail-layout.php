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

<!-- 신청하기 섹션 (카드 아래 오른쪽) -->
<section class="plan-detail-apply-section">
    <div class="content-layout">
        <div class="plan-apply-content">
            <button class="plan-apply-btn" id="phoneApplyBtn">신청하기</button>
        </div>
    </div>
</section>



























