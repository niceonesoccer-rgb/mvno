<?php
/**
 * 통신사폰 카드 본문 컴포넌트
 * 제목, 요금제 정보, 지원금 정보, 가격 정보
 * 
 * @param array $phone 통신사폰 데이터
 */
if (!isset($phone)) {
    $phone = [];
}
$device_name = $phone['device_name'] ?? '기기명';
$device_storage = $phone['device_storage'] ?? '';
$release_price = $phone['release_price'] ?? '0원';
$provider = $phone['provider'] ?? 'SKT';
$plan_name = $phone['plan_name'] ?? '요금제명';
$price_main = $phone['price'] ?? '월 0원';
$maintenance_period = $phone['maintenance_period'] ?? '0일';

// 요금제명에서 통신사명 제거 (이미 provider에 있음)
$plan_name_clean = str_replace([$provider . ' ', 'SKT ', 'KT ', 'LG U+ '], '', $plan_name);

// 지원금 정보 (예시 데이터 - 나중에 실제 데이터로 교체)
$common_support = $phone['common_support'] ?? [
    'number_port' => -198,
    'device_change' => 191.6
];
$contract_support = $phone['contract_support'] ?? [
    'number_port' => 198,
    'device_change' => -150
];
?>

<!-- 제목: 기기명 | 용량 | 출고가 -->
<div class="phone-title-row">
    <span class="phone-title-text">
        <?php echo htmlspecialchars($device_name); ?> 
        <span class="phone-title-separator">|</span> 
        <?php echo htmlspecialchars($device_storage); ?> 
        <span class="phone-title-separator">|</span> 
        출고가 <?php echo htmlspecialchars($release_price); ?>원
    </span>
</div>

<!-- 요금제 정보 -->
<div class="phone-info-section">
    <div class="phone-data-row">
        <span class="phone-data-main">
            <span class="phone-provider-name"><?php echo htmlspecialchars($provider); ?></span>
            <span class="phone-name-text"><?php echo htmlspecialchars($plan_name_clean); ?></span>
        </span>
    </div>
    <div class="plan-features-row">
        <div class="mno-support-amount-section">
            <div class="mno-support-card">
                <div class="mno-support-card-title">공통지원할인</div>
                <div class="mno-support-card-content">
                    <div class="mno-support-item">
                        <span class="mno-support-label">번호이동</span>
                        <span class="mno-support-value <?php echo $common_support['number_port'] < 0 ? 'mno-support-value-negative' : 'mno-support-value-positive'; ?>">
                            <?php echo $common_support['number_port'] < 0 ? '-' : '+'; ?><?php echo number_format(abs($common_support['number_port'])); ?>
                        </span>
                    </div>
                    <div class="mno-support-item">
                        <span class="mno-support-label">기기변경</span>
                        <span class="mno-support-value <?php echo $common_support['device_change'] < 0 ? 'mno-support-value-negative' : 'mno-support-value-positive'; ?>">
                            <?php echo $common_support['device_change'] < 0 ? '-' : '+'; ?><?php echo number_format(abs($common_support['device_change']), 1); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="mno-support-card">
                <div class="mno-support-card-title">선택약정할인</div>
                <div class="mno-support-card-content">
                    <div class="mno-support-item">
                        <span class="mno-support-label">번호이동</span>
                        <span class="mno-support-value <?php echo $contract_support['number_port'] < 0 ? 'mno-support-value-negative' : 'mno-support-value-positive'; ?>">
                            <?php echo $contract_support['number_port'] < 0 ? '-' : '+'; ?><?php echo number_format(abs($contract_support['number_port'])); ?>
                        </span>
                    </div>
                    <div class="mno-support-item">
                        <span class="mno-support-label">기기변경</span>
                        <span class="mno-support-value <?php echo $contract_support['device_change'] < 0 ? 'mno-support-value-negative' : 'mno-support-value-positive'; ?>">
                            <?php echo $contract_support['device_change'] < 0 ? '' : '+'; ?><?php echo number_format(abs($contract_support['device_change'])); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 가격 정보 -->
<div class="plan-price-row">
    <div class="plan-price-left">
        <div class="plan-price-main-row">
            <span class="plan-price-main"><?php echo htmlspecialchars($price_main); ?></span>
        </div>
        <span class="plan-price-after">유지기간 <?php echo htmlspecialchars($maintenance_period); ?></span>
    </div>
</div>
