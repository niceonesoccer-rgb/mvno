<?php
/**
 * 통신사폰 주문 카드 전체 컴포넌트 (mno-order.php 전용)
 * mno-order.php만 사용하는 독립적인 레이아웃
 * 
 * @param array $phone 통신사폰 데이터
 * @param string $layout_type 'list' 또는 'detail'
 * @param string $card_wrapper_class 추가 클래스명
 */
if (!isset($phone)) {
    $phone = [];
}
$layout_type = $layout_type ?? 'list';
$card_wrapper_class = $card_wrapper_class ?? '';
?>

<article class="mno-order-card <?php echo htmlspecialchars($card_wrapper_class); ?>">
    <div class="mno-order-card-link">
        <div class="mno-order-card-main-content">
            <div class="mno-order-card-header-body-frame">
                <?php include __DIR__ . '/mno-order-card-header.php'; ?>
                <?php include __DIR__ . '/phone-card-body.php'; ?>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/mno-order-card-actions.php'; ?>
    
    <?php include __DIR__ . '/phone-card-footer.php'; ?>
</article>

