<?php
/**
 * 공유하기 버튼 컴포넌트
 * 재사용 가능한 공유하기 버튼 모듈
 * 
 * @param string $share_url 공유할 URL
 * @param string $button_class 버튼 CSS 클래스명 (기본값: 'plan-share-btn-inline')
 * @param string $button_id 버튼 ID (선택사항)
 * @param string $aria_label 접근성 라벨 (기본값: '공유하기')
 * @param bool $show_condition 표시 조건 (기본값: true)
 * @param string $product_type 상품 타입 ('mvno', 'mno', 'mno-sim', 'internet')
 * @param int $product_id 상품 ID
 * @param string $seller_id 판매자 ID (선택사항)
 */
if (!isset($share_url)) {
    $share_url = '';
}
if (!isset($button_class)) {
    $button_class = 'plan-share-btn-inline';
}
if (!isset($button_id)) {
    $button_id = '';
}
if (!isset($aria_label)) {
    $aria_label = '공유하기';
}
if (!isset($show_condition)) {
    $show_condition = true;
}
if (!isset($product_type)) {
    $product_type = '';
}
if (!isset($product_id)) {
    $product_id = 0;
}
if (!isset($seller_id)) {
    $seller_id = '';
}

if (!$show_condition || empty($share_url)) {
    return;
}
?>
<button type="button" class="<?php echo htmlspecialchars($button_class); ?>" 
        aria-label="<?php echo htmlspecialchars($aria_label); ?>" 
        data-share-url="<?php echo htmlspecialchars($share_url); ?>"
        <?php if (!empty($product_type)): ?>data-product-type="<?php echo htmlspecialchars($product_type); ?>"<?php endif; ?>
        <?php if (!empty($product_id)): ?>data-product-id="<?php echo htmlspecialchars($product_id); ?>"<?php endif; ?>
        <?php if (!empty($seller_id)): ?>data-seller-id="<?php echo htmlspecialchars($seller_id); ?>"<?php endif; ?>
        <?php if (!empty($button_id)): ?>id="<?php echo htmlspecialchars($button_id); ?>"<?php endif; ?>>
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M11.2028 3.30311C11.6574 2.89896 12.3426 2.89896 12.7972 3.30311L17.2972 7.30311C17.7926 7.74341 17.8372 8.5019 17.3969 8.99724C16.9566 9.49258 16.1981 9.53719 15.7028 9.09689L13.2 6.8722V13.8C13.2 14.4627 12.6627 15 12 15C11.3372 15 10.8 14.4627 10.8 13.8V6.87222L8.29724 9.09689C7.8019 9.53719 7.04341 9.49258 6.60311 8.99724C6.16281 8.5019 6.20742 7.74341 6.70276 7.30311L11.2028 3.30311Z" fill="#868E96"/>
        <path d="M4.2 13C4.86274 13 5.4 13.5373 5.4 14.2V18.1083C5.4 18.184 5.43249 18.2896 5.5575 18.3981C5.68495 18.5087 5.89077 18.6 6.15 18.6H17.85C18.1093 18.6 18.3151 18.5087 18.4425 18.3981C18.5675 18.2897 18.6 18.184 18.6 18.1083V14.2C18.6 13.5373 19.1373 13 19.8 13C20.4627 13 21 13.5373 21 14.2V18.1083C21 19.8598 19.4239 21 17.85 21H6.15C4.5761 21 3 19.8598 3 18.1083V14.2C3 13.5373 3.53726 13 4.2 13Z" fill="#868E96"/>
    </svg>
</button>

