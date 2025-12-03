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
$company_name = $phone['company_name'] ?? '쉐이크모바일';
$rating = $phone['rating'] ?? '4.3';
$phone_id = $phone['id'] ?? 0;
$share_url = $phone['link_url'] ?? '/MVNO/mno/mno-phone-detail.php?id=' . $phone_id;
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
        <button class="plan-favorite-btn-inline" aria-label="찜하기" <?php echo ($layout_type === 'detail') ? 'id="phoneFavoriteBtn"' : ''; ?>>
            <svg width="28.8" height="28.8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="favorite-icon-svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M17.9623 11.5427C18.5031 11.0065 18.8 10.2886 18.8 9.54803C18.8 8.80746 18.5032 8.08961 17.9623 7.5534C17.4166 7.01196 16.6657 6.7 15.8748 6.7C15.0838 6.7 14.3335 7.01145 13.7879 7.55284L13.549 7.7898C12.6914 8.64035 11.3084 8.64041 10.4508 7.78993L10.2121 7.55325C9.06574 6.41633 7.18394 6.41618 6.03759 7.55311C4.92082 8.66071 4.92079 10.4353 6.03758 11.543L12.0178 17.474C13.2794 16.2826 14.4839 15.0586 15.7184 13.804C16.4497 13.0609 17.1918 12.3068 17.9623 11.5427ZM11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C18.7821 13.8281 18.0602 14.5615 17.3378 15.2955C15.8837 16.7728 14.4273 18.2525 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166Z" fill="#868E96" class="favorite-icon-path"/>
            </svg>
        </button>
        <?php if ($phone_id > 0): ?>
        <button class="plan-share-btn-inline" aria-label="공유하기" data-share-url="<?php echo htmlspecialchars($share_url); ?>" <?php echo ($layout_type === 'detail') ? 'id="phoneShareBtn"' : ''; ?>>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11.2028 3.30311C11.6574 2.89896 12.3426 2.89896 12.7972 3.30311L17.2972 7.30311C17.7926 7.74341 17.8372 8.5019 17.3969 8.99724C16.9566 9.49258 16.1981 9.53719 15.7028 9.09689L13.2 6.8722V13.8C13.2 14.4627 12.6627 15 12 15C11.3372 15 10.8 14.4627 10.8 13.8V6.87222L8.29724 9.09689C7.8019 9.53719 7.04341 9.49258 6.60311 8.99724C6.16281 8.5019 6.20742 7.74341 6.70276 7.30311L11.2028 3.30311Z" fill="#868E96"/>
                <path d="M4.2 13C4.86274 13 5.4 13.5373 5.4 14.2V18.1083C5.4 18.184 5.43249 18.2896 5.5575 18.3981C5.68495 18.5087 5.89077 18.6 6.15 18.6H17.85C18.1093 18.6 18.3151 18.5087 18.4425 18.3981C18.5675 18.2897 18.6 18.184 18.6 18.1083V14.2C18.6 13.5373 19.1373 13 19.8 13C20.4627 13 21 13.5373 21 14.2V18.1083C21 19.8598 19.4239 21 17.85 21H6.15C4.5761 21 3 19.8598 3 18.1083V14.2C3 13.5373 3.53726 13 4.2 13Z" fill="#868E96"/>
            </svg>
        </button>
        <?php endif; ?>
    </div>
</div>

