<?php
/**
 * 요금제 카드 푸터 컴포넌트 (사은품 아코디언)
 * 
 * @param array $plan 요금제 데이터
 */
if (!isset($plan)) {
    $plan = [];
}
$gifts = $plan['gifts'] ?? [];
$gift_count = count($gifts);
$promotion_title = $plan['promotion_title'] ?? '';

// 아코디언 제목: 프로모션 제목이 있으면 사용, 없으면 기본 텍스트
$accordion_title = '';
if (!empty($promotion_title)) {
    $accordion_title = $promotion_title;
} elseif ($gift_count > 0) {
    $accordion_title = '사은품 최대 ' . $gift_count . '개';
}

// 관리자 페이지에서 설정한 색상 (나중에 DB에서 가져올 예정)
// 무지개 순서: 빨강, 노랑, 초록, 파랑, 보라
$gift_colors = $plan['gift_colors'] ?? ['#EF4444', '#EAB308', '#10B981', '#3B82F6', '#8B5CF6']; // 무지개 순서 (주황 제외)
$gift_text_color = $plan['gift_text_color'] ?? '#FFFFFF'; // 기본값: 흰색
$plan_id = $plan['id'] ?? 0;
// 공유 링크는 카드 클릭 시 이동하는 신청 페이지 URL 사용
$share_url = $plan['link_url'] ?? '/MVNO/mvno/mvno-plan-detail.php?id=' . $plan_id;
?>

<!-- 아코디언: 사은품 -->
<?php if ($gift_count > 0 || !empty($promotion_title)): ?>
<div class="plan-accordion-box">
    <div class="plan-accordion">
        <button type="button" class="plan-accordion-trigger" aria-expanded="false">
            <div class="plan-gifts-accordion-content">
                <!-- 각 항목의 첫 글자를 원 안에 표시 (통신사폰과 동일한 방식) -->
                <?php if ($gift_count > 0): ?>
                <div class="plan-gifts-indicator-dots">
                    <?php 
                    // 각 항목의 첫 글자를 원 안에 표시 (모든 항목 표시)
                    for ($i = 0; $i < $gift_count; $i++): 
                        $first_char = mb_substr($gifts[$i], 0, 1, 'UTF-8'); // 첫 글자 추출
                        // 색상 배열에서 순환하여 사용 (5개 이상일 경우 반복)
                        $color_index = $i % count($gift_colors);
                        $bg_color = $gift_colors[$color_index] ?? '#6366F1';
                    ?>
                        <span class="plan-gift-indicator-dot" style="background-color: <?php echo htmlspecialchars($bg_color); ?>;">
                            <span class="plan-gift-indicator-text" style="color: <?php echo htmlspecialchars($gift_text_color); ?>;"><?php echo htmlspecialchars($first_char); ?></span>
                        </span>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <span class="plan-gifts-text-accordion"><?php echo htmlspecialchars($accordion_title); ?></span>
            </div>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                <path d="M6 9L12 15L18 9" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="plan-accordion-content" style="display: none;">
            <div class="plan-gifts-detail-list">
                <?php if ($gift_count > 0): ?>
                    <?php foreach ($gifts as $gift): ?>
                    <div class="plan-gift-detail-item">
                        <span class="plan-gift-detail-text"><?php echo htmlspecialchars($gift); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="plan-gift-detail-item">
                        <span class="plan-gift-detail-text">사은품 정보 없음</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

