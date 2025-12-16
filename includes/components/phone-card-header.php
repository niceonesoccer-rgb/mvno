<?php
/**
 * 통신사폰 카드 헤더 컴포넌트
 * 통신사, 찜 버튼, 공유 버튼(상세 페이지용)
 * 
 * @param array $phone 통신사폰 데이터
 * @param string $layout_type 'list' 또는 'detail'
 */
if (!isset($phone)) {
    $phone = [];
}
$layout_type = $layout_type ?? 'list';
$provider = $phone['provider'] ?? 'SKT';
$company_name_raw = $phone['company_name'] ?? '쉐이크모바일';
// "스마트모바일" → "스마트"로 변환
$company_name = $company_name_raw;
if (strpos($company_name_raw, '스마트모바일') !== false) {
    $company_name = '스마트';
} elseif (strpos($company_name_raw, '모바일') !== false) {
    // "XX모바일" 형식에서 "XX"만 추출
    $company_name = str_replace('모바일', '', $company_name_raw);
}
$rating = $phone['rating'] ?? '4.3';
$phone_id = $phone['id'] ?? 0;
$share_url = $phone['link_url'] ?? '/MVNO/mno/mno-phone-detail.php?id=' . $phone_id;
$selection_count = $phone['selection_count'] ?? '29,448명이 선택';
?>

<!-- 헤더: 통신사, 찜 -->
<div class="plan-card-top-header">
    <div class="plan-provider-rating-group">
        <span class="plan-provider-logo-text"><?php echo htmlspecialchars($company_name); ?></span>
        <div class="plan-rating-group">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
            </svg>
            <span class="plan-rating-text"><?php echo htmlspecialchars($rating); ?></span>
        </div>
    </div>
    <div class="plan-badge-favorite-group">
        <span class="plan-selection-count"><?php echo htmlspecialchars($selection_count); ?></span>
        <?php
        $button_id = ($layout_type === 'detail') ? 'phoneFavoriteBtn' : '';
        $item_id = $phone_id;
        $item_type = 'phone';
        $is_favorited = $phone['is_favorited'] ?? false;
        include __DIR__ . '/favorite-button.php';
        ?>
        <?php
        if ($phone_id > 0) {
            $button_id = ($layout_type === 'detail') ? 'phoneShareBtn' : '';
            include __DIR__ . '/share-button.php';
        }
        ?>
    </div>
</div>

