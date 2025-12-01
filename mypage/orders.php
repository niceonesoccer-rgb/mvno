<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = false;

// 요금제 데이터 배열
$plans = [
    ['id' => 32627, 'provider' => '쉐이크모바일', 'rating' => 4.3, 'title' => '[모요핫딜] 11월한정 LTE 100GB+밀리+Data쿠폰60GB', 'data' => '월 100GB + 5Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price' => '월 17,000원', 'price_after' => '7개월 이후 42,900원', 'count' => '29,448명이 신청', 'gifts' => ['이마트 상품권', '네이버페이', '데이터쿠폰 20GB', '밀리의 서재', 'SOLO결합(+20GB)'], 'gift_count' => 5, 'order_date' => '2024.11.15', 'order_time' => '14:30'],
    ['id' => 32632, 'provider' => '고고모바일', 'rating' => 4.2, 'title' => 'LTE무제한 100GB+5M(CU20%할인)_11월', 'data' => '월 100GB + 5Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price' => '월 17,000원', 'price_after' => '7개월 이후 42,900원', 'count' => '12,353명이 신청', 'gifts' => ['KT유심&배송비 무료', '데이터쿠폰 20GB x 3회', '추가데이터 20GB 제공', '이마트 상품권', 'CU 상품권', '네이버페이'], 'gift_count' => 6, 'order_date' => '2024.11.12', 'order_time' => '09:15'],
    ['id' => 29290, 'provider' => '이야기모바일', 'rating' => 4.5, 'title' => '이야기 완전 무제한 100GB+', 'data' => '월 100GB + 5Mbps', 'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'], 'price' => '월 17,000원', 'price_after' => '7개월 이후 49,500원', 'count' => '17,816명이 신청', 'gifts' => ['네이버페이', '네이버페이'], 'gift_count' => 2, 'order_date' => '2024.11.10', 'order_time' => '16:45'],
    ['id' => 32628, 'provider' => '핀다이렉트', 'rating' => 4.2, 'title' => '[S] 핀다이렉트Z _2511', 'data' => '월 11GB + 매일 2GB + 3Mbps', 'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'], 'price' => '월 14,960원', 'price_after' => '7개월 이후 39,600원', 'count' => '4,420명이 신청', 'gifts' => ['매월 20GB 추가 데이터', '네이버페이'], 'gift_count' => 2, 'order_date' => '2024.11.08', 'order_time' => '11:20'],
    ['id' => 32629, 'provider' => '고고모바일', 'rating' => 4.2, 'title' => '무제한 11GB+3M(밀리의서재 Free)_11월', 'data' => '월 11GB + 매일 2GB + 3Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price' => '월 15,000원', 'price_after' => '7개월 이후 36,300원', 'count' => '6,970명이 신청', 'gifts' => ['KT유심&배송비 무료', '데이터쿠폰 20GB x 3회', '추가데이터 20GB 제공', '이마트 상품권', '네이버페이', '밀리의 서재'], 'gift_count' => 6, 'order_date' => '2024.11.05', 'order_time' => '13:50'],
    ['id' => 32630, 'provider' => '찬스모바일', 'rating' => 4.5, 'title' => '음성기본 11GB+일 2GB+', 'data' => '월 11GB + 매일 2GB + 3Mbps', 'features' => ['통화 무제한', '문자 무제한', 'LG U+망', 'LTE'], 'price' => '월 15,000원', 'price_after' => '7개월 이후 38,500원', 'count' => '31,315명이 신청', 'gifts' => ['유심/배송비 무료', '네이버페이'], 'gift_count' => 2, 'order_date' => '2024.11.03', 'order_time' => '10:05'],
    ['id' => 32631, 'provider' => '이야기모바일', 'rating' => 4.5, 'title' => '이야기 완전 무제한 11GB+', 'data' => '월 11GB + 매일 2GB + 3Mbps', 'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'], 'price' => '월 15,000원', 'price_after' => '7개월 이후 39,600원', 'count' => '13,651명이 신청', 'gifts' => ['네이버페이', '네이버페이'], 'gift_count' => 2, 'order_date' => '2024.11.01', 'order_time' => '15:30'],
    ['id' => 32633, 'provider' => '찬스모바일', 'rating' => 4.5, 'title' => '100분 15GB+', 'data' => '월 15GB + 3Mbps', 'features' => ['통화 100분', '문자 100건', 'LG U+망', 'LTE'], 'price' => '월 14,000원', 'price_after' => '7개월 이후 30,580원', 'count' => '7,977명이 신청', 'gifts' => ['유심/배송비 무료', '네이버페이'], 'gift_count' => 2, 'order_date' => '2024.10.28', 'order_time' => '12:15'],
    ['id' => 32634, 'provider' => '쉐이크모바일', 'rating' => 4.3, 'title' => 'LTE 완전무제한 200GB+', 'data' => '월 200GB + 5Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price' => '월 25,000원', 'price_after' => '7개월 이후 52,900원', 'count' => '8,234명이 신청', 'gifts' => ['이마트 상품권', '네이버페이', '데이터쿠폰 30GB', '밀리의 서재'], 'gift_count' => 4, 'order_date' => '2024.10.25', 'order_time' => '14:00'],
    ['id' => 32635, 'provider' => '고고모바일', 'rating' => 4.2, 'title' => '스마트 요금제 50GB+', 'data' => '월 50GB + 3Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price' => '월 12,000원', 'price_after' => '7개월 이후 35,900원', 'count' => '15,678명이 신청', 'gifts' => ['CU 상품권', '네이버페이'], 'gift_count' => 2, 'order_date' => '2024.10.22', 'order_time' => '09:40'],
    ['id' => 32636, 'provider' => '핀다이렉트', 'rating' => 4.2, 'title' => '[K] 핀다이렉트Z 7GB+(네이버페이) _2511', 'data' => '월 7GB + 1Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price' => '월 8,000원', 'price_after' => '7개월 이후 26,400원', 'count' => '4,407명이 신청', 'gifts' => ['추가 데이터', '매월 5GB 추가 데이터', '이마트 상품권', '네이버페이', '네이버페이'], 'gift_count' => 5, 'order_date' => '2024.10.20', 'order_time' => '16:20'],
    ['id' => 32637, 'provider' => '이야기모바일', 'rating' => 4.5, 'title' => '이야기 베이직 5GB+', 'data' => '월 5GB + 1Mbps', 'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'], 'price' => '월 6,000원', 'price_after' => '7개월 이후 22,000원', 'count' => '9,123명이 신청', 'gifts' => ['네이버페이'], 'gift_count' => 1, 'order_date' => '2024.10.18', 'order_time' => '11:55'],
    ['id' => 32638, 'provider' => '찬스모바일', 'rating' => 4.5, 'title' => '찬스 프리미엄 150GB+', 'data' => '월 150GB + 5Mbps', 'features' => ['통화 무제한', '문자 무제한', 'LG U+망', 'LTE'], 'price' => '월 20,000원', 'price_after' => '7개월 이후 45,000원', 'count' => '11,456명이 신청', 'gifts' => ['유심/배송비 무료', '네이버페이', '데이터쿠폰 25GB'], 'gift_count' => 3, 'order_date' => '2024.10.15', 'order_time' => '13:25'],
    ['id' => 32639, 'provider' => '쉐이크모바일', 'rating' => 4.3, 'title' => '쉐이크 스탠다드 30GB+', 'data' => '월 30GB + 3Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price' => '월 10,000원', 'price_after' => '7개월 이후 32,900원', 'count' => '18,234명이 신청', 'gifts' => ['이마트 상품권', '네이버페이', '데이터쿠폰 15GB'], 'gift_count' => 3, 'order_date' => '2024.10.12', 'order_time' => '10:30'],
    ['id' => 32640, 'provider' => '고고모바일', 'rating' => 4.2, 'title' => '고고 울트라 80GB+', 'data' => '월 80GB + 5Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price' => '월 16,000원', 'price_after' => '7개월 이후 41,900원', 'count' => '14,567명이 신청', 'gifts' => ['CU 상품권', '네이버페이', '데이터쿠폰 20GB', '이마트 상품권'], 'gift_count' => 4, 'order_date' => '2024.10.10', 'order_time' => '15:10'],
];

// 헤더 포함
include '../includes/header.php';

// 사은품 아이콘 매핑
$giftIcons = [
    '이마트 상품권' => 'emart',
    '네이버페이' => 'naverpay',
    '데이터쿠폰' => 'ticket',
    '밀리의 서재' => 'millie',
    'SOLO결합' => 'subscription',
    'CU 상품권' => 'cu',
    'KT유심&배송비 무료' => 'etc',
    '추가데이터' => 'ticket',
    '유심/배송비 무료' => 'etc',
    '매월' => 'ticket',
    '추가 데이터' => 'ticket',
];
?>

<main class="main-content">
    <div style="width: 460px; margin: 0 auto; padding: 20px;" class="mypage-container">
        <!-- 페이지 헤더 -->
        <div style="margin-bottom: 24px; padding: 20px 0;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <a href="/MVNO/mypage/mypage.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 요금제</h2>
            </div>
        </div>

        <!-- 신청한 요금제 목록 -->
        <div style="margin-bottom: 32px;" id="plansContainer">
            <?php foreach ($plans as $index => $plan): ?>
                <div class="plan-item" data-index="<?php echo $index; ?>" style="<?php echo $index >= 10 ? 'display: none;' : ''; ?> position: relative; margin-bottom: 36px;">
                    <span style="position: absolute; top: -24px; right: 0; font-size: 12px; color: #868E96; white-space: nowrap; z-index: 1; background-color: #ffffff; padding: 2px 4px;">신청일: <?php echo htmlspecialchars($plan['order_date'] . ' ' . $plan['order_time']); ?></span>
                <article class="basic-plan-card" style="margin-bottom: 0;">
                    <a href="/MVNO/plans/plan-detail.php?id=<?php echo $plan['id']; ?>" class="plan-card-link">
                        <div class="plan-card-main-content">
                            <div class="plan-card-header-body-frame">
                                <!-- 헤더: 로고, 평점, 배지, 찜 -->
                                <div class="plan-card-top-header">
                                    <div class="plan-provider-rating-group">
                                        <span class="plan-provider-logo-text"><?php echo htmlspecialchars($plan['provider']); ?></span>
                                        <div class="plan-rating-group">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"></path>
                                            </svg>
                                            <span class="plan-rating-text"><?php echo $plan['rating']; ?></span>
                                        </div>
                                    </div>
                                    <div class="plan-badge-favorite-group">
                                        <button class="plan-favorite-btn-inline" aria-label="찜하기">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C17.3144 15.2837 15.1837 17.5556 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803Z" fill="#FA5252"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- 제목 -->
                                <div class="plan-title-row">
                                    <span class="plan-title-text"><?php echo htmlspecialchars($plan['title']); ?></span>
                                </div>

                                <!-- 데이터 정보와 기능 -->
                                <div class="plan-info-section">
                                    <div class="plan-data-row">
                                        <span class="plan-data-main"><?php echo htmlspecialchars($plan['data']); ?></span>
                                        <button class="plan-info-icon-btn" aria-label="정보">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="plan-features-row">
                                        <?php foreach ($plan['features'] as $fIndex => $feature): ?>
                                            <span class="plan-feature-item"><?php echo htmlspecialchars($feature); ?></span>
                                            <?php if ($fIndex < count($plan['features']) - 1): ?>
                                                <div class="plan-feature-divider"></div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- 가격 정보 -->
                                <div class="plan-price-row">
                                    <div class="plan-price-left">
                                        <div class="plan-price-main-row">
                                            <span class="plan-price-main"><?php echo htmlspecialchars($plan['price']); ?></span>
                                            <button class="plan-info-icon-btn" aria-label="정보">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <span class="plan-price-after"><?php echo htmlspecialchars($plan['price_after']); ?></span>
                                    </div>
                                    <div class="plan-price-right">
                                        <span class="plan-selection-count"><?php echo htmlspecialchars($plan['count']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                    
                    <!-- 아코디언: 사은품 -->
                    <div class="plan-accordion-box">
                        <div class="plan-accordion">
                            <button type="button" class="plan-accordion-trigger" aria-expanded="false">
                                <div class="plan-gifts-accordion-content">
                                    <div class="plan-gift-icons-overlap">
                                        <?php 
                                        $displayGifts = array_slice($plan['gifts'], 0, 5);
                                        foreach ($displayGifts as $gift):
                                            $iconKey = 'etc';
                                            foreach ($giftIcons as $key => $icon):
                                                if (strpos($gift, $key) !== false):
                                                    $iconKey = $icon;
                                                    break;
                                                endif;
                                            endforeach;
                                        ?>
                                            <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/<?php echo $iconKey; ?>.svg" alt="<?php echo htmlspecialchars($gift); ?>" width="24" height="24" class="plan-gift-icon-overlap">
                                        <?php endforeach; ?>
                                    </div>
                                    <span class="plan-gifts-text-accordion">사은품 최대 <?php echo $plan['gift_count']; ?>개</span>
                                </div>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"></path>
                                </svg>
                            </button>
                            <div class="plan-accordion-content" style="display: none;">
                                <div class="plan-gifts-detail-list">
                                    <?php foreach ($plan['gifts'] as $gift): ?>
                                        <div class="plan-gift-detail-item">
                                            <span class="plan-gift-detail-text"><?php echo htmlspecialchars($gift); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 더보기 버튼 -->
        <div style="margin-top: 32px; margin-bottom: 32px;" id="moreButtonContainer">
            <button class="plan-review-more-btn" id="morePlansBtn">
                더보기 (<?php 
                $remaining = count($plans) - 10;
                echo $remaining > 10 ? 10 : $remaining;
                ?>개)
            </button>
        </div>
    </div>
</main>

<script src="../assets/js/plan-accordion.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const moreBtn = document.getElementById('morePlansBtn');
    const planItems = document.querySelectorAll('.plan-item');
    let visibleCount = 10;
    const totalPlans = planItems.length;
    const loadCount = 10; // 한 번에 보여줄 개수

    function updateButtonText() {
        const remaining = totalPlans - visibleCount;
        if (remaining > 0) {
            const showCount = remaining > loadCount ? loadCount : remaining;
            moreBtn.textContent = `더보기 (${showCount}개)`;
        }
    }

    if (moreBtn) {
        updateButtonText();
        
        moreBtn.addEventListener('click', function() {
            // 다음 10개씩 표시
            const endCount = Math.min(visibleCount + loadCount, totalPlans);
            for (let i = visibleCount; i < endCount; i++) {
                if (planItems[i]) {
                    planItems[i].style.display = 'block';
                }
            }
            
            visibleCount = endCount;
            
            // 모든 항목이 보이면 더보기 버튼 숨기기
            if (visibleCount >= totalPlans) {
                const moreButtonContainer = document.getElementById('moreButtonContainer');
                if (moreButtonContainer) {
                    moreButtonContainer.style.display = 'none';
                }
            } else {
                updateButtonText();
            }
        });
    }

    // 모든 요금제가 보이면 더보기 버튼 숨기기
    if (visibleCount >= totalPlans) {
        const moreButtonContainer = document.getElementById('moreButtonContainer');
        if (moreButtonContainer) {
            moreButtonContainer.style.display = 'none';
        }
    }
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>
