<?php
/**
 * 찜하기 버튼 컴포넌트
 * 재사용 가능한 찜하기 버튼 모듈
 * 
 * @param string $button_class 버튼 CSS 클래스명 (기본값: 'plan-favorite-btn-inline')
 * @param string $button_id 버튼 ID (선택사항)
 * @param string $aria_label 접근성 라벨 (기본값: '찜하기')
 * @param bool $show_condition 표시 조건 (기본값: true)
 * @param int $item_id 아이템 ID (phone_id 또는 plan_id)
 * @param string $item_type 아이템 타입 ('phone' 또는 'plan')
 * @param bool $is_favorited 찜 상태 (기본값: false)
 */
if (!isset($button_class)) {
    $button_class = 'plan-favorite-btn-inline';
}
if (!isset($button_id)) {
    $button_id = '';
}
if (!isset($aria_label)) {
    $aria_label = '찜하기';
}
if (!isset($show_condition)) {
    $show_condition = true;
}
if (!isset($item_id)) {
    $item_id = 0;
}
if (!isset($item_type)) {
    $item_type = 'phone'; // 기본값: phone
}
if (!isset($is_favorited)) {
    $is_favorited = false;
}

if (!$show_condition) {
    return;
}
?>
<button class="<?php echo htmlspecialchars($button_class); ?> <?php echo $is_favorited ? 'favorited' : ''; ?>" 
        aria-label="<?php echo htmlspecialchars($aria_label); ?>"
        data-item-id="<?php echo $item_id; ?>"
        data-item-type="<?php echo htmlspecialchars($item_type); ?>"
        <?php if (!empty($button_id)): ?>id="<?php echo htmlspecialchars($button_id); ?>"<?php endif; ?>
        type="button">
    <svg width="28.8" height="28.8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="favorite-icon-svg">
        <path d="M17.9623 11.5427C18.5031 11.0065 18.8 10.2886 18.8 9.54803C18.8 8.80746 18.5032 8.08961 17.9623 7.5534C17.4166 7.01196 16.6657 6.7 15.8748 6.7C15.0838 6.7 14.3335 7.01145 13.7879 7.55284L13.549 7.7898C12.6914 8.64035 11.3084 8.64041 10.4508 7.78993L10.2121 7.55325C9.06574 6.41633 7.18394 6.41618 6.03759 7.55311C4.92082 8.66071 4.92079 10.4353 6.03758 11.543L12.0178 17.474C13.2794 16.2826 14.4839 15.0586 15.7184 13.804C16.4497 13.0609 17.1918 12.3068 17.9623 11.5427ZM11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C18.7821 13.8281 18.0602 14.5615 17.3378 15.2955C15.8837 16.7728 14.4273 18.2525 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166Z" fill="#868E96" class="favorite-icon-path" fill-rule="evenodd" clip-rule="evenodd"/>
    </svg>
</button>

