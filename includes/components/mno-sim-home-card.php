<?php
/**
 * 메인페이지 전용 통신사단독유심 카드 컴포넌트
 * 
 * @param array $product 원본 상품 데이터
 */
if (!isset($product)) {
    $product = [];
}

$product_id = $product['id'] ?? 0;
$is_link = ($product_id > 0);
$link_url = getAssetPath('/mno-sim/mno-sim-detail.php?id=' . $product_id);

// 통신사
$provider = $product['provider'] ?? '';

// 요금할인방법 (계약기간 + 할인)
$contractPeriod = $product['contract_period'] ?? '';
$discountMethod = '';
if (!empty($contractPeriod)) {
    $discountMethod = $contractPeriod;
    if (!empty($product['contract_period_discount_value']) && !empty($product['contract_period_discount_unit'])) {
        $discountMethod .= ' ' . $product['contract_period_discount_value'] . $product['contract_period_discount_unit'];
    }
}

// 요금제명
$planName = $product['plan_name'] ?? '';

// 데이터 + 추가데이터
$dataMain = '';
if (!empty($product['data_amount'])) {
    if ($product['data_amount'] === '무제한') {
        $dataMain = '무제한';
    } elseif (!empty($product['data_amount_value']) && !empty($product['data_unit'])) {
        $dataMain = number_format($product['data_amount_value']) . $product['data_unit'];
    } else {
        $dataMain = $product['data_amount'];
    }
    
    // 추가데이터
    if (!empty($product['data_additional']) && $product['data_additional'] !== '없음') {
        if (!empty($product['data_additional_value'])) {
            $dataMain .= ' + ' . $product['data_additional_value'];
        } else {
            $dataMain .= ' + ' . $product['data_additional'];
        }
    }
}

// 요금
$priceMain = '';
$priceMainValue = (int)($product['price_main'] ?? 0);
$priceAfterValue = (int)($product['price_after'] ?? 0);
$priceAfterUnit = $product['price_after_unit'] ?? '원';

if ($priceAfterValue > 0) {
    // 프로모션 금액이 있는 경우
    $priceMain = '월 ' . number_format($priceAfterValue) . $priceAfterUnit;
} elseif ($priceMainValue > 0) {
    // 프로모션 금액이 없는 경우
    $priceMainUnit = $product['price_main_unit'] ?? '원';
    $priceMain = '월 ' . number_format($priceMainValue) . $priceMainUnit;
} else {
    $priceMain = '월 0원';
}

// 프로모션기간
$promotionPeriod = '';
if (!empty($product['discount_period']) && $product['discount_period'] !== '프로모션 없음') {
    if (!empty($product['discount_period_value']) && !empty($product['discount_period_unit'])) {
        $promotionPeriod = $product['discount_period_value'] . $product['discount_period_unit'];
    } else {
        $promotionPeriod = $product['discount_period'];
    }
}
?>

<article class="mno-sim-home-card" data-plan-id="<?php echo $product_id; ?>">
    <?php if ($is_link): ?>
    <a href="<?php echo htmlspecialchars($link_url); ?>" class="mno-sim-home-card-link">
    <?php else: ?>
    <div class="mno-sim-home-card-link">
    <?php endif; ?>
        <div class="mno-sim-home-card-content">
            <!-- 1. 통신사 -->
            <?php if (!empty($provider)): ?>
            <div class="mno-sim-home-provider">
                <?php echo htmlspecialchars($provider); ?>
            </div>
            <?php endif; ?>
            
            <!-- 2. 요금할인방법 -->
            <?php if (!empty($discountMethod)): ?>
            <div class="mno-sim-home-discount-method">
                <?php echo htmlspecialchars($discountMethod); ?>
            </div>
            <?php endif; ?>
            
            <!-- 3. 요금제명 -->
            <?php if (!empty($planName)): ?>
            <div class="mno-sim-home-plan-name">
                <?php echo htmlspecialchars($planName); ?>
            </div>
            <?php endif; ?>
            
            <!-- 4. 데이터 + 추가데이터 -->
            <?php if (!empty($dataMain)): ?>
            <div class="mno-sim-home-data">
                <span class="mno-sim-home-data-label">데이터</span>
                <span class="mno-sim-home-data-value"><?php echo htmlspecialchars($dataMain); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- 5. 프로모션기간 요금 (한 줄) -->
            <div class="mno-sim-home-promotion-price-row">
                <?php if (!empty($promotionPeriod)): ?>
                <span class="mno-sim-home-promotion-period"><?php echo htmlspecialchars($promotionPeriod); ?></span>
                <?php endif; ?>
                <?php if (!empty($priceMain)): ?>
                <span class="mno-sim-home-price"><?php echo htmlspecialchars($priceMain); ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php if ($is_link): ?>
    </a>
    <?php else: ?>
    </div>
    <?php endif; ?>
</article>
