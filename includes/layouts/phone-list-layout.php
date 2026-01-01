<?php
/**
 * 통신사폰 목록 레이아웃
 * mno.php, mno-order.php에서 사용
 * 
 * @param array $phones 통신사폰 배열
 * @param string $section_title 섹션 제목 (선택사항)
 */
if (!isset($phones)) {
    $phones = [];
}
$section_title = $section_title ?? '';
$layout_type = 'list';
$is_wishlist = $is_wishlist ?? false;
?>

<?php if (!empty($section_title)): ?>
<div class="plans-results-count">
    <span><?php echo htmlspecialchars($section_title); ?></span>
</div>
<?php endif; ?>

<div class="plans-list-container" id="mno-products-container">
    <!-- 최상단 광고 로테이션 섹션 - 모든 광고 상품 표시 -->
    <?php 
    // 광고 상품과 일반 상품 분리
    $advertisementPhonesForDisplay = [];
    $regularPhonesForDisplay = [];
    
    foreach ($phones as $phone) {
        if (isset($phone['is_advertising']) && $phone['is_advertising']) {
            $advertisementPhonesForDisplay[] = $phone;
        } else {
            $regularPhonesForDisplay[] = $phone;
        }
    }
    ?>
    
    <?php if (!empty($advertisementPhonesForDisplay)): ?>
        <?php foreach ($advertisementPhonesForDisplay as $index => $phone): ?>
            <div class="advertisement-card-item" data-ad-index="<?php echo $index; ?>">
                <?php
                // 각 카드에 필요한 변수 설정
                $card_wrapper_class = '';
                include __DIR__ . '/../components/phone-card.php';
                ?>
                
                <!-- 카드 구분선 (모바일용) -->
                <hr class="plan-card-divider">
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- 일반 상품 목록 -->
    <?php foreach ($regularPhonesForDisplay as $phone): ?>
        <?php
        // 각 카드에 필요한 변수 설정
        $card_wrapper_class = '';
        include __DIR__ . '/../components/phone-card.php';
        ?>
        
        <!-- 카드 구분선 (모바일용) -->
        <hr class="plan-card-divider">
    <?php endforeach; ?>
</div>

