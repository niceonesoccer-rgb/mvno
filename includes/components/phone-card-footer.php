<?php
/**
 * 통신사폰 카드 푸터 컴포넌트 (추가지원 및 부가서비스 아코디언)
 * 
 * @param array $phone 통신사폰 데이터
 */
if (!isset($phone)) {
    $phone = [];
}
$additional_supports = $phone['additional_supports'] ?? ['추가 지원금', '부가 서비스 1', '부가 서비스 2'];
$support_count = count($additional_supports);
// 관리자 페이지에서 설정한 색상 (나중에 DB에서 가져올 예정)
$support_colors = $phone['support_colors'] ?? ['#6366F1', '#8B5CF6', '#EC4899']; // 기본값
$support_text_color = $phone['support_text_color'] ?? '#FFFFFF'; // 기본값: 흰색
$phone_id = $phone['id'] ?? 0;
// 공유 링크는 카드 클릭 시 이동하는 신청 페이지 URL 사용
$share_url = $phone['link_url'] ?? '/MVNO/mno/mno-phone-detail.php?id=' . $phone_id;

// 택배/내방지역명 가져오기
$delivery_method = $phone['delivery_method'] ?? 'delivery';
$visit_region = $phone['visit_region'] ?? '';
$delivery_display = ($delivery_method === 'visit' && !empty($visit_region)) ? $visit_region : '택배';
?>

<!-- 아코디언: 추가지원 및 부가서비스 -->
<?php if ($support_count > 0): ?>
<div class="plan-accordion-box">
    <div class="plan-accordion">
        <button type="button" class="plan-accordion-trigger" aria-expanded="false">
            <div class="plan-gifts-accordion-content">
                <?php 
                // 택배 또는 내방지역명 표시
                $first_support = $additional_supports[0] ?? '';
                $display_delivery = '택배';
                
                // "택배 | ..." 또는 "서초 | ..." 형태에서 추출 시도
                if (strpos($first_support, ' | ') !== false) {
                    $parts = explode(' | ', $first_support, 2);
                    $display_delivery = $parts[0]; // "택배" 또는 "서초" 등
                } else {
                    // 데이터에 없으면 phone 데이터에서 직접 가져오기
                    $display_delivery = $delivery_display;
                }
                ?>
                <span class="plan-gifts-text-accordion" style="display: flex; align-items: center; gap: 12px;">
                    <span style="min-width: 80px; display: inline-block; text-align: left; padding-right: 12px; border-right: 1px solid #e5e7eb;"><?php echo htmlspecialchars($display_delivery); ?></span>
                    <span>추가지원 및 부가서비스</span>
                </span>
            </div>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                <path d="M6 9L12 15L18 9" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="plan-accordion-content" style="display: none;">
            <div class="plan-gifts-detail-list">
                <?php foreach ($additional_supports as $support): ?>
                <div class="plan-gift-detail-item">
                    <span class="plan-gift-detail-text"><?php echo htmlspecialchars($support); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
