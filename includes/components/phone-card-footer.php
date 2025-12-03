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
?>

<!-- 아코디언: 추가지원 및 부가서비스 -->
<?php if ($support_count > 0): ?>
<div class="plan-accordion-box">
    <div class="plan-accordion">
        <button type="button" class="plan-accordion-trigger" aria-expanded="false">
            <div class="plan-gifts-accordion-content">
                <!-- 각 항목의 첫 글자를 원 안에 표시 -->
                <div class="plan-gifts-indicator-dots">
                    <?php 
                    // 각 항목의 첫 글자를 원 안에 표시
                    $display_count = min($support_count, 3);
                    for ($i = 0; $i < $display_count; $i++): 
                        $first_char = mb_substr($additional_supports[$i], 0, 1, 'UTF-8'); // 첫 글자 추출
                        $bg_color = $support_colors[$i] ?? $support_colors[0] ?? '#6366F1'; // 색상 배열에서 가져오거나 기본값 사용
                    ?>
                        <span class="plan-gift-indicator-dot" style="background-color: <?php echo htmlspecialchars($bg_color); ?>;">
                            <span class="plan-gift-indicator-text" style="color: <?php echo htmlspecialchars($support_text_color); ?>;"><?php echo htmlspecialchars($first_char); ?></span>
                        </span>
                    <?php endfor; ?>
                </div>
                <span class="plan-gifts-text-accordion">추가지원 및 부가서비스</span>
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
