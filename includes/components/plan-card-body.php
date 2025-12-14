<?php
/**
 * 요금제 카드 본문 컴포넌트
 * 제목, 데이터 정보, 기능, 가격 정보
 * 
 * @param array $plan 요금제 데이터
 * @param string $layout_type 'list' 또는 'detail'
 */
if (!isset($plan)) {
    $plan = [];
}
$layout_type = $layout_type ?? 'list';
$title = $plan['title'] ?? '요금제 제목';
// 하이픈 앞뒤 모두 값이 있으면 하이픈 삭제, 값이 없으면 유지
// "데이터 소진시 - 5Mbps 무제한" → "데이터 소진시 5Mbps 무제한" (하이픈 제거)
// "통화 기본제공 -" → "통화 기본제공 -" (유지, 뒤에 값 없음)
// " - 문자 무제한" → " - 문자 무제한" (유지, 앞에 값 없음)
// 하이픈 앞뒤에 공백과 문자가 모두 있는 경우에만 하이픈 제거
$title = preg_replace('/([^\s-]+)\s*-\s*([^\s-]+)/u', '$1 $2', $title); // 앞뒤 모두 값이 있는 경우 하이픈 제거
$data_main = $plan['data_main'] ?? '월 100GB + 5Mbps';
$features = $plan['features'] ?? ['통화 무제한', '문자 무제한', 'KT망', 'LTE'];
$price_main = $plan['price_main'] ?? '월 17,000원';
$price_after = $plan['price_after'] ?? '7개월 이후 42,900원';
$selection_count = $plan['selection_count'] ?? '29,448명이 선택';
$show_features = ($layout_type === 'list');
?>

<!-- 제목 -->
<div class="plan-title-row">
    <span class="plan-title-text"><?php echo htmlspecialchars($title); ?></span>
</div>

<!-- 데이터 정보와 기능 -->
<div class="plan-info-section">
    <div class="plan-data-row">
        <span class="plan-data-main"><?php echo htmlspecialchars($data_main); ?></span>
        <?php if ($layout_type === 'detail'): ?>
            <span class="plan-selection-count"><?php echo htmlspecialchars($selection_count); ?></span>
        <?php endif; ?>
    </div>
    <?php if ($show_features && !empty($features)): ?>
    <div class="plan-features-row">
        <?php foreach ($features as $index => $feature): ?>
            <span class="plan-feature-item"><?php echo htmlspecialchars($feature); ?></span>
            <?php if ($index < count($features) - 1): ?>
                <div class="plan-feature-divider"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- 가격 정보 -->
<?php if ($layout_type === 'list'): ?>
<div class="plan-price-row">
    <div class="plan-price-left">
        <div class="plan-price-main-row">
            <span class="plan-price-main"><?php echo htmlspecialchars($price_main); ?></span>
        </div>
        <span class="plan-price-after"><?php echo htmlspecialchars($price_after); ?></span>
    </div>
    <div class="plan-price-right">
        <span class="plan-selection-count"><?php echo htmlspecialchars($selection_count); ?></span>
    </div>
</div>
<?php endif; ?>



























