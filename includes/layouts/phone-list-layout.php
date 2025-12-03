<?php
/**
 * 통신사폰 목록 레이아웃
 * mno.php, mno-order.php에서 사용
 * 
 * @param array $phones 통신사폰 배열
 * @param string $section_title 섹션 제목 (선택사항)
 */
if (!isset($phones)) {
    $phones = [];
}
$section_title = $section_title ?? '';
$layout_type = 'list';
$is_wishlist = $is_wishlist ?? false;
?>

<?php if (!empty($section_title)): ?>
<div class="plans-results-count">
    <span><?php echo htmlspecialchars($section_title); ?></span>
</div>
<?php endif; ?>

<div class="plans-list-container">
    <?php foreach ($phones as $phone): ?>
        <?php
        // 각 카드에 필요한 변수 설정
        $card_wrapper_class = '';
        include __DIR__ . '/../components/phone-card.php';
        ?>
        
        <!-- 카드 구분선 (모바일용) -->
        <hr class="plan-card-divider">
    <?php endforeach; ?>
</div>

