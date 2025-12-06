<?php
/**
 * 요금제 주문 카드 전체 컴포넌트 (mvno-order.php 전용)
 * mvno-order.php만 사용하는 독립적인 레이아웃
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
$is_link = false; // 카드 클릭 링크 제거
?>

<article class="mvno-order-card <?php echo htmlspecialchars($card_wrapper_class); ?>" data-plan-id="<?php echo $plan_id; ?>">
    <div class="mvno-order-card-link">
        <div class="mvno-order-card-main-content">
            <div class="mvno-order-card-header-body-frame">
                <?php include __DIR__ . '/mvno-order-plan-card-header.php'; ?>
                <?php include __DIR__ . '/plan-card-body.php'; ?>
            </div>
        </div>
    </div>
    
    <?php 
    $plan = $plan_data;
    include __DIR__ . '/mvno-order-plan-card-actions.php'; 
    include __DIR__ . '/plan-card-footer.php'; 
    ?>
</article>

