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

// path-config.php가 로드되지 않았으면 로드
if (!function_exists('getAssetPath')) {
    require_once __DIR__ . '/../data/path-config.php';
}

// 공유 링크는 카드 클릭 시 이동하는 신청 페이지 URL 사용
if (isset($phone['link_url']) && !empty($phone['link_url'])) {
    $share_url = $phone['link_url'];
} else {
    $share_url = getAssetPath('/mno/mno-phone-detail.php?id=' . urlencode($phone_id));
}

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
                $display_delivery_text = '택배';
                
                if ($delivery_method === 'visit' && !empty($visit_region)) {
                    // 내방인 경우: "내방(양천 강서)" 형식
                    $display_delivery_text = '내방(' . $visit_region . ')';
                } else {
                    // 택배인 경우
                    $display_delivery_text = '택배';
                }
                ?>
                <span class="plan-gifts-text-accordion" style="display: flex; align-items: center; gap: 4px;">
                    <span style="min-width: 13ch; display: inline-block; text-align: center; padding-right: 4px; border-right: 1px solid #e5e7eb;"><?php echo htmlspecialchars($display_delivery_text); ?></span>
                    <span><?php echo htmlspecialchars($phone['promotion_title'] ?? '부가서비스 없음'); ?></span>
                </span>
            </div>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                <path d="M6 9L12 15L18 9" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="plan-accordion-content" style="display: none;">
            <div class="plan-gifts-detail-list">
                <?php 
                // promotion_title이 "부가서비스 없음"이거나 additional_supports가 비어있으면 기본 배지 표시
                $promotion_title = $phone['promotion_title'] ?? '부가서비스 없음';
                $is_no_service = (empty($additional_supports) || (count($additional_supports) === 1 && strpos($additional_supports[0], '부가서비스 없음') !== false)) && $promotion_title === '부가서비스 없음';
                
                if ($is_no_service) {
                    // 기본 항목들 (애, 1, 2, 3, 4) - 가로로 나란히 배치
                    $default_items = ['애', '1', '2', '3', '4'];
                    $default_colors = ['#ef4444', '#FCC419', '#51cf66', '#3b82f6', '#9c88ff']; // 빨강, 노랑, 초록, 파랑, 보라
                ?>
                <div class="plan-gift-detail-item" style="display: flex; align-items: center; gap: 4px;">
                    <?php foreach ($default_items as $index => $item): 
                        $bg_color = $default_colors[$index] ?? '#6366F1';
                    ?>
                    <div class="plan-gift-indicator-dot" style="background-color: <?php echo htmlspecialchars($bg_color); ?>;">
                        <span class="plan-gift-indicator-text"><?php echo htmlspecialchars($item); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php
                } else {
                    // 기존 additional_supports 항목 표시
                    foreach ($additional_supports as $support): 
                ?>
                <div class="plan-gift-detail-item">
                    <?php 
                    // "택배 | ..." 또는 "양천 강서 | ..." 형태에서 항목만 추출
                    $support_text = $support;
                    if (strpos($support, ' | ') !== false) {
                        $parts = explode(' | ', $support, 2);
                        $support_text = $parts[1] ?? $support; // " | " 뒤의 항목명만 사용
                    }
                    ?>
                    <span class="plan-gift-detail-text"><?php echo htmlspecialchars($support_text); ?></span>
                </div>
                <?php 
                    endforeach;
                }
                ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
