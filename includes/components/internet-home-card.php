<?php
/**
 * 인터넷 상품 홈 카드 컴포넌트 (메인페이지 전용)
 * 
 * @param array $product 인터넷 상품 데이터
 * @param bool $is_link 링크 활성화 여부 (기본값: true)
 */

if (!isset($product)) {
    $product = [];
}

$is_link = $is_link ?? true;
$product_id = $product['id'] ?? 0;
$link_url = getAssetPath('/internets/internet-detail.php?id=' . $product_id);

// 데이터 추출
$provider = $product['registration_place'] ?? ''; // 통신사
$serviceType = $product['service_type'] ?? '인터넷'; // 결합여부
$speed = $product['speed_option'] ?? ''; // 속도
$monthlyFee = $product['monthly_fee'] ?? 0; // 금액

// 결합여부 표시
$combinedDisplay = '';
if ($serviceType === '인터넷+TV') {
    $combinedDisplay = '인터넷 + TV 결합';
} elseif ($serviceType === '인터넷+TV+핸드폰') {
    $combinedDisplay = '인터넷 + TV + 핸드폰 결합';
} elseif ($serviceType === '인터넷') {
    $combinedDisplay = '인터넷 단독';
} else {
    $combinedDisplay = $serviceType;
}

// 현금지급 파싱
$cashPayments = [];
$cashPaymentNames = $product['cash_payment_names'] ?? '';
$cashPaymentPrices = $product['cash_payment_prices'] ?? '';
if (!empty($cashPaymentNames) && !empty($cashPaymentPrices)) {
    $names = is_string($cashPaymentNames) ? json_decode($cashPaymentNames, true) : $cashPaymentNames;
    $prices = is_string($cashPaymentPrices) ? json_decode($cashPaymentPrices, true) : $cashPaymentPrices;
    if (is_array($names) && is_array($prices) && count($names) === count($prices)) {
        for ($i = 0; $i < count($names); $i++) {
            if (!empty($names[$i]) && !empty($prices[$i])) {
                $cashPayments[] = [
                    'name' => $names[$i],
                    'price' => $prices[$i]
                ];
            }
        }
    }
}

// 상품권지급 파싱
$giftCards = [];
$giftCardNames = $product['gift_card_names'] ?? '';
$giftCardPrices = $product['gift_card_prices'] ?? '';
if (!empty($giftCardNames) && !empty($giftCardPrices)) {
    $names = is_string($giftCardNames) ? json_decode($giftCardNames, true) : $giftCardNames;
    $prices = is_string($giftCardPrices) ? json_decode($giftCardPrices, true) : $giftCardPrices;
    if (is_array($names) && is_array($prices) && count($names) === count($prices)) {
        for ($i = 0; $i < count($names); $i++) {
            if (!empty($names[$i]) && !empty($prices[$i])) {
                $giftCards[] = [
                    'name' => $names[$i],
                    'price' => $prices[$i]
                ];
            }
        }
    }
}

// 장비 및 기타서비스 파싱
$equipmentServices = [];
$equipmentNames = $product['equipment_names'] ?? '';
$equipmentPrices = $product['equipment_prices'] ?? '';
$installationNames = $product['installation_names'] ?? '';
$installationPrices = $product['installation_prices'] ?? '';

// 장비
if (!empty($equipmentNames) && !empty($equipmentPrices)) {
    $names = is_string($equipmentNames) ? json_decode($equipmentNames, true) : $equipmentNames;
    $prices = is_string($equipmentPrices) ? json_decode($equipmentPrices, true) : $equipmentPrices;
    if (is_array($names) && is_array($prices) && count($names) === count($prices)) {
        for ($i = 0; $i < count($names); $i++) {
            if (!empty($names[$i]) && !empty($prices[$i])) {
                $equipmentServices[] = [
                    'name' => $names[$i],
                    'price' => $prices[$i]
                ];
            }
        }
    }
}

// 설치 및 기타서비스
if (!empty($installationNames) && !empty($installationPrices)) {
    $names = is_string($installationNames) ? json_decode($installationNames, true) : $installationNames;
    $prices = is_string($installationPrices) ? json_decode($installationPrices, true) : $installationPrices;
    if (is_array($names) && is_array($prices) && count($names) === count($prices)) {
        for ($i = 0; $i < count($names); $i++) {
            if (!empty($names[$i]) && !empty($prices[$i])) {
                $equipmentServices[] = [
                    'name' => $names[$i],
                    'price' => $prices[$i]
                ];
            }
        }
    }
}

// 금액 포맷팅 함수 (숫자 추출 후 천 단위 구분 기호 추가)
if (!function_exists('formatPriceWithComma')) {
    function formatPriceWithComma($priceStr) {
        if (empty($priceStr)) {
            return '';
        }
        
        // 숫자만 추출 (연속된 숫자)
        preg_match_all('/\d+/', $priceStr, $matches);
        if (empty($matches[0])) {
            return $priceStr;
        }
        
        // 첫 번째 숫자 시퀀스를 찾아서 포맷팅
        $numbers = $matches[0];
        $formattedNumber = number_format((float)$numbers[0]);
        
        // 원본 문자열에서 첫 번째 숫자를 포맷된 숫자로 교체
        $result = preg_replace('/\d+/', $formattedNumber, $priceStr, 1);
        
        return $result;
    }
}

// 금액 포맷팅
$priceDisplay = '';
if (!empty($monthlyFee)) {
    if (is_numeric($monthlyFee)) {
        $priceDisplay = '월 ' . number_format((float)$monthlyFee) . '원';
    } else {
        $priceDisplay = formatPriceWithComma($monthlyFee);
    }
}

// 현금지급, 상품권지급, 장비 및 기타서비스 금액 포맷팅
foreach ($cashPayments as &$item) {
    $item['price'] = formatPriceWithComma($item['price']);
}
unset($item);

foreach ($giftCards as &$item) {
    $item['price'] = formatPriceWithComma($item['price']);
}
unset($item);

foreach ($equipmentServices as &$item) {
    $item['price'] = formatPriceWithComma($item['price']);
}
unset($item);
?>

<article class="internet-home-card" data-product-id="<?php echo $product_id; ?>">
    <?php if ($is_link): ?>
    <a href="<?php echo htmlspecialchars($link_url); ?>" class="internet-home-card-link">
    <?php else: ?>
    <div class="internet-home-card-link">
    <?php endif; ?>
        <div class="internet-home-card-content">
            <!-- 1. 통신사 + 속도 (한 줄) -->
            <?php if (!empty($provider) || !empty($speed)): ?>
            <div class="internet-home-provider-speed">
                <?php if (!empty($provider)): ?>
                <span class="internet-home-provider"><?php echo htmlspecialchars($provider); ?></span>
                <?php endif; ?>
                <?php if (!empty($speed)): ?>
                <span class="internet-home-speed"><?php echo htmlspecialchars($speed); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- 2. 결합여부 -->
            <?php if (!empty($combinedDisplay)): ?>
            <div class="internet-home-combined">
                <?php echo htmlspecialchars($combinedDisplay); ?>
            </div>
            <?php endif; ?>
            
            <!-- 3. 현금지급 -->
            <?php if (!empty($cashPayments)): ?>
            <div class="internet-home-cash-payment">
                <div class="internet-home-section-items">
                    <?php foreach ($cashPayments as $item): ?>
                    <div class="internet-home-item">
                        <span class="internet-home-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="internet-home-item-price"><?php echo htmlspecialchars($item['price']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- 4. 상품권지급 -->
            <?php if (!empty($giftCards)): ?>
            <div class="internet-home-gift-card">
                <div class="internet-home-section-items">
                    <?php foreach ($giftCards as $item): ?>
                    <div class="internet-home-item">
                        <span class="internet-home-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="internet-home-item-price"><?php echo htmlspecialchars($item['price']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- 5. 장비 및 기타서비스 -->
            <?php if (!empty($equipmentServices)): ?>
            <div class="internet-home-equipment">
                <div class="internet-home-section-items">
                    <?php foreach ($equipmentServices as $item): ?>
                    <div class="internet-home-item">
                        <span class="internet-home-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="internet-home-item-price"><?php echo htmlspecialchars($item['price']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- 7. 금액 -->
            <?php if (!empty($priceDisplay)): ?>
            <div class="internet-home-price">
                <?php echo htmlspecialchars($priceDisplay); ?>
            </div>
            <?php endif; ?>
        </div>
    <?php if ($is_link): ?>
    </a>
    <?php else: ?>
    </div>
    <?php endif; ?>
</article>
