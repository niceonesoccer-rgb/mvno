<?php
/**
 * 통신사폰 카드 전체 컴포넌트
 * mno.php, wishlist.php에서 사용 (기존 레이아웃)
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
$phone_id = $phone['id'] ?? 0;
$is_link = ($layout_type === 'list' && $phone_id > 0);
$link_url = $phone['link_url'] ?? '/MVNO/mno/mno-phone-detail.php?id=' . $phone_id;

// plan-card-header.php에서 사용할 $plan 변수 설정
$plan = [
    'provider' => $phone['company_name'] ?? '이야기모바일',
    'rating' => $phone['rating'] ?? '4.5'
];
?>

<article class="basic-plan-card <?php echo htmlspecialchars($card_wrapper_class); ?>">
    <?php if ($is_link): ?>
    <a href="<?php echo htmlspecialchars($link_url); ?>" class="plan-card-link">
    <?php else: ?>
    <div class="plan-card-link">
    <?php endif; ?>
        <div class="plan-card-main-content">
            <div class="plan-card-header-body-frame">
                <?php include __DIR__ . '/plan-card-header.php'; ?>
                <?php include __DIR__ . '/phone-card-body.php'; ?>
            </div>
        </div>
    <?php if ($is_link): ?>
    </a>
    <?php else: ?>
    </div>
    <?php endif; ?>
    
    <?php include __DIR__ . '/phone-card-footer.php'; ?>
</article>
