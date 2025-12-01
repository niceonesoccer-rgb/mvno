<?php
/**
 * 요금제 목록 레이아웃
 * plans.php, mypage.php, wishlist.php, orders.php에서 사용
 * 
 * @param array $plans 요금제 배열
 * @param string $section_title 섹션 제목 (선택사항)
 */
if (!isset($plans)) {
    $plans = [];
}
$section_title = $section_title ?? '';
$layout_type = 'list';
?>

<?php if (!empty($section_title)): ?>
<div class="plans-results-count">
    <span><?php echo htmlspecialchars($section_title); ?></span>
</div>
<?php endif; ?>

<div class="plans-list-container">
    <?php foreach ($plans as $plan): ?>
        <?php
        // 각 카드에 필요한 변수 설정
        $card_wrapper_class = '';
        include __DIR__ . '/../components/plan-card.php';
        ?>
        
        <!-- 카드 구분선 (모바일용) -->
        <hr class="plan-card-divider">
    <?php endforeach; ?>
</div>

