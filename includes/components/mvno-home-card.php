<?php
/**
 * 메인페이지 전용 알뜰폰 요금제 카드 컴포넌트
 * 
 * @param array $product 원본 상품 데이터
 */
if (!isset($product)) {
    $product = [];
}

$product_id = $product['id'] ?? 0;
$is_link = ($product_id > 0);
$link_url = '/MVNO/mvno/mvno-plan-detail.php?id=' . $product_id;

// 통신사
$provider = $product['provider'] ?? '';

// 요금제명
$planName = $product['plan_name'] ?? '';

// 데이터 + 추가데이터
$dataMain = '';
$dataAdditional = '';

if (!empty($product['data_amount'])) {
    if ($product['data_amount'] === '무제한') {
        $dataMain = '무제한';
    } elseif ($product['data_amount'] === '직접입력' && !empty($product['data_amount_value'])) {
        $dataAmountValue = $product['data_amount_value'];
        if (preg_match('/^(\d+)(.+)$/', $dataAmountValue, $matches)) {
            $dataMain = number_format((float)$matches[1]) . $matches[2];
        } else {
            $dataMain = $product['data_amount_value'];
        }
    } else {
        $dataMain = $product['data_amount'];
    }
    
    // 추가데이터
    if (!empty($product['data_additional']) && $product['data_additional'] !== '없음') {
        if ($product['data_additional'] === '직접입력' && !empty($product['data_additional_value'])) {
            $dataAdditional = $product['data_additional_value'];
        } else {
            $dataAdditional = $product['data_additional'];
        }
    }
}

// 통화 정보
$callInfo = '';
if (!empty($product['call_type'])) {
    $callType = trim($product['call_type']);
    if ($callType === '무제한') {
        $callInfo = '통화 무제한';
    } elseif ($callType === '기본제공') {
        $callInfo = '통화 기본제공';
    } elseif ($callType === '직접입력' && !empty($product['call_amount'])) {
        $callAmount = trim($product['call_amount']);
        if ($callAmount !== '') {
            if (preg_match('/^(\d+)(.+)$/', $callAmount, $matches)) {
                $callInfo = '통화 ' . number_format((float)$matches[1]) . $matches[2];
            } else {
                $callInfo = '통화 ' . htmlspecialchars($callAmount);
            }
        }
    }
}

// 문자 정보
$smsInfo = '';
if (!empty($product['sms_type'])) {
    $smsType = trim($product['sms_type']);
    if ($smsType === '무제한') {
        $smsInfo = '문자 무제한';
    } elseif ($smsType === '기본제공') {
        $smsInfo = '문자 기본제공';
    } elseif ($smsType === '직접입력' && !empty($product['sms_amount'])) {
        $smsAmount = trim($product['sms_amount']);
        if ($smsAmount !== '') {
            if (preg_match('/^(\d+)(.+)$/', $smsAmount, $matches)) {
                $smsInfo = '문자 ' . number_format((float)$matches[1]) . $matches[2];
            } else {
                $smsInfo = '문자 ' . htmlspecialchars($smsAmount);
            }
        }
    }
}

// 프로모션 기간
$promotionPeriod = '';
if (!empty($product['discount_period'])) {
    $promotionPeriod = $product['discount_period'];
}

// 프로모션간 금액 (price_after가 프로모션 기간 동안의 금액)
$promotionPrice = '';
$priceAfterValue = $product['price_after'] ?? null;
if ($priceAfterValue === null || $priceAfterValue === '' || $priceAfterValue === '0' || $priceAfterValue == 0) {
    $promotionPrice = '공짜';
} elseif ($priceAfterValue !== null && $priceAfterValue !== '' && $priceAfterValue !== '0' && $priceAfterValue != 0) {
    $promotionPrice = '월 ' . number_format((float)$priceAfterValue) . '원';
} else {
    $originalPriceMain = $product['price_main'] ?? 0;
    $promotionPrice = '월 ' . number_format((float)$originalPriceMain) . '원';
}
?>

<article class="mvno-home-card" data-plan-id="<?php echo $product_id; ?>">
    <?php if ($is_link): ?>
    <a href="<?php echo htmlspecialchars($link_url); ?>" class="mvno-home-card-link">
    <?php else: ?>
    <div class="mvno-home-card-link">
    <?php endif; ?>
        <div class="mvno-home-card-content">
            <!-- 1. 통신사 -->
            <?php if (!empty($provider)): ?>
            <div class="mvno-home-provider">
                <?php echo htmlspecialchars($provider); ?>
            </div>
            <?php endif; ?>
            
            <!-- 2. 요금제명 -->
            <?php if (!empty($planName)): ?>
            <div class="mvno-home-plan-name">
                <?php echo htmlspecialchars($planName); ?>
            </div>
            <?php endif; ?>
            
            <!-- 3. 데이터 + 추가데이터 -->
            <?php if (!empty($dataMain)): ?>
            <div class="mvno-home-data">
                <span class="mvno-home-data-label">데이터</span>
                <span class="mvno-home-data-value"><?php echo htmlspecialchars($dataMain); ?></span>
                <?php if (!empty($dataAdditional)): ?>
                <span class="mvno-home-data-additional">+ <?php echo htmlspecialchars($dataAdditional); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- 4. 통화 문자 -->
            <div class="mvno-home-call-sms">
                <?php if (!empty($callInfo)): ?>
                <span class="mvno-home-call"><?php echo htmlspecialchars($callInfo); ?></span>
                <?php endif; ?>
                <?php if (!empty($callInfo) && !empty($smsInfo)): ?>
                <span class="mvno-home-separator">|</span>
                <?php endif; ?>
                <?php if (!empty($smsInfo)): ?>
                <span class="mvno-home-sms"><?php echo htmlspecialchars($smsInfo); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- 5. 프로모션 기간 금액 (한 줄) -->
            <div class="mvno-home-promotion-price-row">
                <?php if (!empty($promotionPeriod)): ?>
                <span class="mvno-home-promotion-period"><?php echo htmlspecialchars($promotionPeriod); ?></span>
                <?php endif; ?>
                <?php if (!empty($promotionPrice)): ?>
                <span class="mvno-home-promotion-price"><?php echo htmlspecialchars($promotionPrice); ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php if ($is_link): ?>
    </a>
    <?php else: ?>
    </div>
    <?php endif; ?>
</article>
