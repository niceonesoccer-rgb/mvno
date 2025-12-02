<?php
/**
 * 통신사폰 카드 본문 컴포넌트
 * 기기명, 용량, 요금제, 지원금, 가격 정보
 * 
 * @param array $phone 통신사폰 데이터
 * @param string $layout_type 'list' 또는 'detail'
 */
if (!isset($phone)) {
    $phone = [];
}
$layout_type = $layout_type ?? 'list';
$device_name = $phone['device_name'] ?? 'Galaxy Z Fold7';
$device_storage = $phone['device_storage'] ?? '256GB';
$device_price = $phone['device_price'] ?? '출고가 2,387,000원';
$plan_name = $phone['plan_name'] ?? 'SKT 프리미어 슈퍼';
$provider = $phone['provider'] ?? 'SKT';
$monthly_price = $phone['monthly_price'] ?? '109,000원';
$maintenance_period = $phone['maintenance_period'] ?? '185일';
$common_number_port = $phone['common_number_port'] ?? '191.6';
$common_device_change = $phone['common_device_change'] ?? '191.6';
$contract_number_port = $phone['contract_number_port'] ?? '191.6';
$contract_device_change = $phone['contract_device_change'] ?? '191.6';

// 통신사와 요금제명 분리 함수
if (!function_exists('parsePlanName')) {
    function parsePlanName($plan_name, $provider) {
        // plan_name에서 통신사명 제거
        $plan_name_clean = trim(str_replace($provider, '', $plan_name));
        return [
            'provider' => $provider,
            'plan_name' => $plan_name_clean
        ];
    }
}
$plan_info = parsePlanName($plan_name, $provider);

// 숫자 값을 파싱하여 음수/양수 판단 및 포맷팅 함수
if (!function_exists('formatSupportValue')) {
    function formatSupportValue($value) {
        // 문자열에서 숫자 추출
        $numeric_value = floatval(str_replace(',', '', $value));
        
        // 음수/양수 판단
        $is_negative = $numeric_value < 0;
        $abs_value = abs($numeric_value);
        
        // 소수점 첫째 자리가 0이면 정수만 표시, 아니면 소수점 첫째 자리까지 표시
        if ($abs_value == floor($abs_value)) {
            // 정수인 경우
            $formatted = ($is_negative ? '-' : '') . number_format($abs_value, 0);
        } else {
            // 소수점이 있는 경우
            $formatted = ($is_negative ? '-' : '') . number_format($abs_value, 1);
        }
        
        return [
            'value' => $formatted,
            'is_negative' => $is_negative
        ];
    }
}
?>

<!-- 제목: 기기명 | 용량 | 출고가 -->
<div class="phone-title-row">
    <span class="phone-title-text"><?php echo htmlspecialchars($device_name); ?> <span class="phone-title-separator">|</span> <?php echo htmlspecialchars($device_storage); ?> <span class="phone-title-separator">|</span> <?php echo htmlspecialchars($device_price); ?></span>
</div>

<!-- 요금제 정보 -->
<div class="phone-info-section">
    <div class="phone-data-row">
        <span class="phone-data-main">
            <span class="phone-provider-name"><?php echo htmlspecialchars($plan_info['provider']); ?></span>
            <span class="phone-name-text"><?php echo htmlspecialchars($plan_info['plan_name']); ?></span>
        </span>
    </div>
    <?php if ($layout_type === 'list'): ?>
    <div class="plan-features-row">
        <div class="mno-support-amount-section">
            <div class="mno-support-card">
                <div class="mno-support-card-title">공통지원할인</div>
                <div class="mno-support-card-content">
                    <div class="mno-support-item">
                        <span class="mno-support-label">번호이동</span>
                        <?php 
                        $common_port = formatSupportValue($common_number_port);
                        $value_class = $common_port['is_negative'] ? 'mno-support-value-negative' : 'mno-support-value-positive';
                        ?>
                        <span class="mno-support-value <?php echo $value_class; ?>"><?php echo htmlspecialchars($common_port['value']); ?></span>
                    </div>
                    <div class="mno-support-item">
                        <span class="mno-support-label">기기변경</span>
                        <?php 
                        $common_change = formatSupportValue($common_device_change);
                        $value_class = $common_change['is_negative'] ? 'mno-support-value-negative' : 'mno-support-value-positive';
                        ?>
                        <span class="mno-support-value <?php echo $value_class; ?>"><?php echo htmlspecialchars($common_change['value']); ?></span>
                    </div>
                </div>
            </div>
            <div class="mno-support-card">
                <div class="mno-support-card-title">선택약정할인</div>
                <div class="mno-support-card-content">
                    <div class="mno-support-item">
                        <span class="mno-support-label">번호이동</span>
                        <?php 
                        $contract_port = formatSupportValue($contract_number_port);
                        $value_class = $contract_port['is_negative'] ? 'mno-support-value-negative' : 'mno-support-value-positive';
                        ?>
                        <span class="mno-support-value <?php echo $value_class; ?>"><?php echo htmlspecialchars($contract_port['value']); ?></span>
                    </div>
                    <div class="mno-support-item">
                        <span class="mno-support-label">기기변경</span>
                        <?php 
                        $contract_change = formatSupportValue($contract_device_change);
                        $value_class = $contract_change['is_negative'] ? 'mno-support-value-negative' : 'mno-support-value-positive';
                        ?>
                        <span class="mno-support-value <?php echo $value_class; ?>"><?php echo htmlspecialchars($contract_change['value']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- 가격 정보 -->
<?php if ($layout_type === 'list'): ?>
<div class="plan-price-row">
    <div class="plan-price-left">
        <div class="plan-price-main-row">
            <span class="plan-price-main">월 <?php echo htmlspecialchars($monthly_price); ?></span>
        </div>
        <span class="plan-price-after">유지기간 <?php echo htmlspecialchars($maintenance_period); ?></span>
    </div>
</div>
<?php endif; ?>

