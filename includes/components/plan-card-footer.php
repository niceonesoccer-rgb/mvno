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
$gift_icons = $plan['gift_icons'] ?? [];
$gift_count = count($gifts);
// 관리자 페이지에서 설정한 색상 (나중에 DB에서 가져올 예정)
$gift_colors = $plan['gift_colors'] ?? ['#6366F1', '#8B5CF6', '#EC4899']; // 기본값
$gift_text_color = $plan['gift_text_color'] ?? '#FFFFFF'; // 기본값: 흰색
?>

<!-- 아코디언: 사은품 -->
<?php if ($gift_count > 0): ?>
<div class="plan-accordion-box">
    <div class="plan-accordion">
        <button type="button" class="plan-accordion-trigger" aria-expanded="false">
            <div class="plan-gifts-accordion-content">
                <?php if (!empty($gift_icons)): ?>
                <div class="plan-gift-icons-overlap">
                    <?php foreach ($gift_icons as $icon): ?>
                        <img src="<?php echo htmlspecialchars($icon['src']); ?>" 
                             alt="<?php echo htmlspecialchars($icon['alt']); ?>" 
                             width="24" height="24" 
                             class="plan-gift-icon-overlap">
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <!-- 각 항목의 첫 글자를 원 안에 표시 -->
                <div class="plan-gifts-indicator-dots">
                    <?php 
                    // 각 항목의 첫 글자를 원 안에 표시
                    $display_count = min($gift_count, 3);
                    for ($i = 0; $i < $display_count; $i++): 
                        $first_char = mb_substr($gifts[$i], 0, 1, 'UTF-8'); // 첫 글자 추출
                        $bg_color = $gift_colors[$i] ?? $gift_colors[0] ?? '#6366F1'; // 색상 배열에서 가져오거나 기본값 사용
                    ?>
                        <span class="plan-gift-indicator-dot" style="background-color: <?php echo htmlspecialchars($bg_color); ?>;">
                            <span class="plan-gift-indicator-text" style="color: <?php echo htmlspecialchars($gift_text_color); ?>;"><?php echo htmlspecialchars($first_char); ?></span>
                        </span>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <span class="plan-gifts-text-accordion">사은품 최대 <?php echo $gift_count; ?>개</span>
            </div>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
            </svg>
        </button>
        <div class="plan-accordion-content" style="display: none;">
            <div class="plan-gifts-detail-list">
                <?php foreach ($gifts as $gift): ?>
                <div class="plan-gift-detail-item">
                    <span class="plan-gift-detail-text"><?php echo htmlspecialchars($gift); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

