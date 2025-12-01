<?php
/**
 * 요금제 카드 전체 컴포넌트
 * 
 * @param array $plan 요금제 데이터
 * @param string $layout_type 'list' 또는 'detail'
 * @param string $card_wrapper_class 추가 클래스명
 */
if (!isset($plan)) {
    $plan = [];
}
$layout_type = $layout_type ?? 'list';
$card_wrapper_class = $card_wrapper_class ?? '';
$plan_id = $plan['id'] ?? 0;
$is_link = ($layout_type === 'list' && $plan_id > 0);
?>

<article class="basic-plan-card <?php echo htmlspecialchars($card_wrapper_class); ?>">
    <?php if ($is_link): ?>
    <a href="/MVNO/plans/plan-detail.php?id=<?php echo $plan_id; ?>" class="plan-card-link">
    <?php else: ?>
    <div class="plan-card-link">
    <?php endif; ?>
        <div class="plan-card-main-content">
            <div class="plan-card-header-body-frame">
                <?php include __DIR__ . '/plan-card-header.php'; ?>
                <?php include __DIR__ . '/plan-card-body.php'; ?>
            </div>
        </div>
    <?php if ($is_link): ?>
    </a>
    <?php else: ?>
    </div>
    <?php endif; ?>
    
    <?php include __DIR__ . '/plan-card-footer.php'; ?>
</article>

