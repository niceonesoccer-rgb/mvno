<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'plans';

// 헤더 포함
include 'includes/header.php';
?>

<main class="main-content">
    <!-- 필터 섹션 (스크롤 시 상단 고정) -->
    <div class="plans-filter-section">
        <div class="plans-filter-inner">
            <!-- 데스크톱 검색 바 -->
            <div class="plans-search-wrapper-desktop">
                <form class="plans-search-form" action="plans.php" method="get">
                    <input type="text" name="search" class="plans-search-input" placeholder="요금제 이름 또는 통신사 등" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="plans-search-btn" aria-label="검색">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 21L16.65 16.65" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>
            </div>
            
            <!-- 데이터 필터 버튼 -->
            <button class="plans-data-filter-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 16 16" fill="none">
                    <path d="M11.9333 2C11.5652 2 11.2667 2.29848 11.2667 2.66667V13.3333C11.2667 13.7015 11.5652 14 11.9333 14H12.3333C12.7015 14 13 13.7015 13 13.3333V2.66667C13 2.29848 12.7015 2 12.3333 2H11.9333Z" fill="#7086FB"></path>
                    <path d="M7.1333 6.66667C7.1333 6.29848 7.43178 6 7.79997 6H8.19997C8.56816 6 8.86663 6.29848 8.86663 6.66667V13.3333C8.86663 13.7015 8.56816 14 8.19997 14H7.79997C7.43178 14 7.1333 13.7015 7.1333 13.3333V6.66667Z" fill="#7086FB"></path>
                    <path d="M3 9.33333C3 8.96514 3.29848 8.66667 3.66667 8.66667H4.06667C4.43486 8.66667 4.73333 8.96514 4.73333 9.33333V13.3333C4.73333 13.7015 4.43486 14 4.06667 14H3.66667C3.29848 14 3 13.7015 3 13.3333V9.33333Z" fill="#7086FB"></path>
                </svg>
                <span class="plans-filter-label">월 데이터</span>
                <div class="plans-filter-value">모든 용량</div>
                <div class="plans-filter-arrow">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"></path>
                    </svg>
                </div>
            </button>

            <!-- 필터 및 정렬 버튼 그룹 -->
            <div class="plans-filter-group">
                <!-- 필터 버튼 -->
                <button class="plans-filter-btn">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 11.5C16.0487 11.5 17.3625 10.4941 17.8237 9.09999H19.9C20.5075 9.09999 21 8.60751 21 7.99999C21 7.39248 20.5075 6.89999 19.9 6.89999H17.8236C17.3625 5.50589 16.0487 4.5 14.5 4.5C12.9513 4.5 11.6375 5.50589 11.1764 6.89999H4.1C3.49249 6.89999 3 7.39248 3 7.99999C3 8.60751 3.49249 9.09999 4.1 9.09999H11.1763C11.6375 10.4941 12.9513 11.5 14.5 11.5ZM14.5 9.5C15.3284 9.5 16 8.82843 16 8C16 7.17157 15.3284 6.5 14.5 6.5C13.6716 6.5 13 7.17157 13 8C13 8.82843 13.6716 9.5 14.5 9.5Z" fill="#3F4750"></path>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3 16C3 15.3925 3.49249 14.9 4.1 14.9H6.17635C6.6375 13.5059 7.95128 12.5 9.5 12.5C11.0487 12.5 12.3625 13.5059 12.8236 14.9H19.9C20.5075 14.9 21 15.3925 21 16C21 16.6075 20.5075 17.1 19.9 17.1H12.8237C12.3625 18.4941 11.0487 19.5 9.5 19.5C7.95128 19.5 6.6375 18.4941 6.17635 17.1H4.1C3.49249 17.1 3 16.6075 3 16ZM11 16C11 16.8284 10.3284 17.5 9.5 17.5C8.67157 17.5 8 16.8284 8 16C8 15.1716 8.67157 14.5 9.5 14.5C10.3284 14.5 11 15.1716 11 16Z" fill="#3F4750"></path>
                    </svg>
                    <span class="plans-filter-text">필터</span>
                </button>

                <!-- 정렬 버튼 -->
                <div class="plans-sort-wrapper">
                    <button class="plans-filter-btn">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.3485 2.35147C11.8799 1.88284 11.1201 1.88284 10.6515 2.35147L5.65146 7.35147C5.18283 7.8201 5.18283 8.5799 5.65146 9.04853C6.12009 9.51716 6.87989 9.51716 7.34852 9.04853L11.5 4.89706L15.6515 9.04853C16.1201 9.51716 16.8799 9.51716 17.3485 9.04853C17.8171 8.5799 17.8171 7.8201 17.3485 7.35147L12.3485 2.35147Z" fill="#3F4750"></path>
                            <path d="M7.34852 14.9514C6.87989 14.4828 6.12009 14.4828 5.65146 14.9514C5.18283 15.4201 5.18283 16.1799 5.65146 16.6485L10.6515 21.6485C11.1201 22.1171 11.8799 22.1171 12.3485 21.6485L17.3485 16.6485C17.8171 16.1799 17.8171 15.4201 17.3485 14.9514C16.8799 14.4828 16.1201 14.4828 15.6515 14.9514L11.5 19.1029L7.34852 14.9514Z" fill="#3F4750"></path>
                        </svg>
                        <span class="plans-filter-text">추천순</span>
                    </button>
                </div>
            </div>
        </div>
        <hr class="plans-filter-divider">
    </div>

    <!-- 요금제 목록 섹션 -->
    <div class="content-layout">
        <div class="plans-main-layout">
            <!-- 왼쪽 섹션: 요금제 목록 -->
            <div class="plans-left-section">
                <!-- 테마별 요금제 섹션 -->
                <section class="theme-plans-list-section">
                    <!-- 모바일 검색 바 -->
                    <div class="plans-search-wrapper-mobile">
                        <form class="plans-search-form" action="plans.php" method="get">
                            <input type="text" name="search" class="plans-search-input" placeholder="요금제 이름 또는 통신사 등" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button type="submit" class="plans-search-btn" aria-label="검색">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M21 21L16.65 16.65" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </form>
                    </div>

                    <!-- 검색 결과 개수 -->
                    <div class="plans-results-count">
                        <span>2,415개의 결과</span>
                    </div>

                    <!-- 요금제 카드 -->
                    <div class="plans-list-container">
                        <article class="basic-plan-card">
                            <a href="/plans/32627" class="plan-card-link">
                                <div class="plan-card-main-content">
                                    <div class="plan-card-header-body-frame">
                                        <!-- 헤더: 로고, 평점, 배지, 찜 -->
                                        <div class="plan-card-top-header">
                                            <div class="plan-provider-rating-group">
                                                <span class="plan-provider-logo-text">쉐이크모바일</span>
                                                <div class="plan-rating-group">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                                    </svg>
                                                    <span class="plan-rating-text">4.3</span>
                                                </div>
                                            </div>
                                            <div class="plan-badge-favorite-group">
                                                <button class="plan-favorite-btn-inline" aria-label="찜하기">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C17.3144 15.2837 15.1837 17.5556 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803Z" fill="#FA5252"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- 제목과 찜 버튼 -->
                                        <div class="plan-title-row">
                                            <span class="plan-title-text">[모요핫딜] 11월한정 LTE 100GB+밀리+Data쿠폰60GB</span>
                                        </div>

                                        <!-- 데이터 정보와 기능 -->
                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 100GB + 5Mbps</span>
                                                <button class="plan-info-icon-btn" aria-label="정보">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="plan-features-row">
                                                <span class="plan-feature-item">통화 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">문자 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">KT망</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">LTE</span>
                                            </div>
                                        </div>

                                        <!-- 가격 정보 -->
                                        <div class="plan-price-row">
                                            <div class="plan-price-left">
                                                <div class="plan-price-main-row">
                                                    <span class="plan-price-main">월 17,000원</span>
                                                    <button class="plan-info-icon-btn" aria-label="정보">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <span class="plan-price-after">7개월 이후 42,900원</span>
                                            </div>
                                            <div class="plan-price-right">
                                                <span class="plan-selection-count">29,448명이 선택</span>
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
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg" alt="이마트 상품권" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="데이터쿠폰 20GB" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/millie.svg" alt="밀리의 서재" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/subscription.svg" alt="SOLO결합(+20GB)" width="24" height="24" class="plan-gift-icon-overlap">
                                            </div>
                                            <span class="plan-gifts-text-accordion">사은품 최대 5개</span>
                                        </div>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                                        </svg>
                                    </button>
                                    <div class="plan-accordion-content" style="display: none;">
                                        <div class="plan-gifts-detail-list">
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">SOLO결합(+20GB)</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">밀리의서재 평생 무료 구독권</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">데이터쿠폰 20GB</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">[11월 한정]네이버페이 10,000원</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">3대 마트 상품권 2만원</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <!-- 두 번째 요금제 카드 -->
                        <article class="basic-plan-card">
                            <a href="/plans/32632" class="plan-card-link">
                                <div class="plan-card-main-content">
                                    <div class="plan-card-header-body-frame">
                                        <!-- 헤더: 로고, 평점, 배지, 찜 -->
                                        <div class="plan-card-top-header">
                                            <div class="plan-provider-rating-group">
                                                <span class="plan-provider-logo-text">고고모바일</span>
                                                <div class="plan-rating-group">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                                    </svg>
                                                    <span class="plan-rating-text">4.2</span>
                                                </div>
                                            </div>
                                            <div class="plan-badge-favorite-group">
                                                <button class="plan-favorite-btn-inline" aria-label="찜하기">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.9623 11.5427C18.5031 11.0065 18.8 10.2886 18.8 9.54803C18.8 8.80746 18.5032 8.08961 17.9623 7.5534C17.4166 7.01196 16.6657 6.7 15.8748 6.7C15.0838 6.7 14.3335 7.01145 13.7879 7.55284L13.549 7.7898C12.6914 8.64035 11.3084 8.64041 10.4508 7.78993L10.2121 7.55325C9.06574 6.41633 7.18394 6.41618 6.03759 7.55311C4.92082 8.66071 4.92079 10.4353 6.03758 11.543L12.0178 17.474C13.2794 16.2826 14.4839 15.0586 15.7184 13.804C16.4497 13.0609 17.1918 12.3068 17.9623 11.5427ZM11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C18.7821 13.8281 18.0602 14.5615 17.3378 15.2955C15.8837 16.7728 14.4273 18.2525 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166Z" fill="#868E96"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- 제목과 찜 버튼 -->
                                        <div class="plan-title-row">
                                            <span class="plan-title-text">[모요핫딜] LTE무제한 100GB+5M(CU20%할인)_11월</span>
                                        </div>

                                        <!-- 데이터 정보와 기능 -->
                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 100GB + 5Mbps</span>
                                                <button class="plan-info-icon-btn" aria-label="정보">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="plan-features-row">
                                                <span class="plan-feature-item">통화 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">문자 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">KT망</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">LTE</span>
                                            </div>
                                        </div>

                                        <!-- 가격 정보 -->
                                        <div class="plan-price-row">
                                            <div class="plan-price-left">
                                                <div class="plan-price-main-row">
                                                    <span class="plan-price-main">월 17,000원</span>
                                                    <button class="plan-info-icon-btn" aria-label="정보">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <span class="plan-price-after">7개월 이후 42,900원</span>
                                            </div>
                                            <div class="plan-price-right">
                                                <span class="plan-selection-count">12,353명이 선택</span>
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
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg" alt="KT유심&배송비 무료" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg" alt="데이터쿠폰 20GB x 3회" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg" alt="추가데이터 20GB 제공" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg" alt="이마트 상품권" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/cu.svg" alt="CU 상품권" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                            </div>
                                            <span class="plan-gifts-text-accordion">사은품 최대 6개</span>
                                        </div>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                                        </svg>
                                    </button>
                                    <div class="plan-accordion-content" style="display: none;">
                                        <div class="plan-gifts-detail-list">
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">KT유심&배송비 무료</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">데이터쿠폰 20GB x 3회</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">추가데이터 20GB 제공</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">이마트 상품권</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">CU 상품권</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">네이버페이</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <!-- 카드 구분선 (모바일용) -->
                        <hr class="plan-card-divider">

                        <!-- 세 번째 요금제 카드 -->
                        <article class="basic-plan-card">
                            <a href="/plans/29290" class="plan-card-link">
                                <div class="plan-card-main-content">
                                    <div class="plan-card-header-body-frame">
                                        <!-- 헤더: 로고, 평점, 배지, 찜 -->
                                        <div class="plan-card-top-header">
                                            <div class="plan-provider-rating-group">
                                                <span class="plan-provider-logo-text">이야기모바일</span>
                                                <div class="plan-rating-group">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                                    </svg>
                                                    <span class="plan-rating-text">4.5</span>
                                                </div>
                                            </div>
                                            <div class="plan-badge-favorite-group">
                                                <button class="plan-favorite-btn-inline" aria-label="찜하기">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.9623 11.5427C18.5031 11.0065 18.8 10.2886 18.8 9.54803C18.8 8.80746 18.5032 8.08961 17.9623 7.5534C17.4166 7.01196 16.6657 6.7 15.8748 6.7C15.0838 6.7 14.3335 7.01145 13.7879 7.55284L13.549 7.7898C12.6914 8.64035 11.3084 8.64041 10.4508 7.78993L10.2121 7.55325C9.06574 6.41633 7.18394 6.41618 6.03759 7.55311C4.92082 8.66071 4.92079 10.4353 6.03758 11.543L12.0178 17.474C13.2794 16.2826 14.4839 15.0586 15.7184 13.804C16.4497 13.0609 17.1918 12.3068 17.9623 11.5427ZM11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C18.7821 13.8281 18.0602 14.5615 17.3378 15.2955C15.8837 16.7728 14.4273 18.2525 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166Z" fill="#868E96"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- 제목과 찜 버튼 -->
                                        <div class="plan-title-row">
                                            <span class="plan-title-text">[모요핫딜] 이야기 완전 무제한 100GB+</span>
                                        </div>

                                        <!-- 데이터 정보와 기능 -->
                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 100GB + 5Mbps</span>
                                                <button class="plan-info-icon-btn" aria-label="정보">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="plan-features-row">
                                                <span class="plan-feature-item">통화 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">문자 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">SKT망</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">LTE</span>
                                            </div>
                                        </div>

                                        <!-- 가격 정보 -->
                                        <div class="plan-price-row">
                                            <div class="plan-price-left">
                                                <div class="plan-price-main-row">
                                                    <span class="plan-price-main">월 17,000원</span>
                                                    <button class="plan-info-icon-btn" aria-label="정보">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <span class="plan-price-after">7개월 이후 49,500원</span>
                                            </div>
                                            <div class="plan-price-right">
                                                <span class="plan-selection-count">17,816명이 선택</span>
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
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                            </div>
                                            <span class="plan-gifts-text-accordion">사은품 최대 2개</span>
                                        </div>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                                        </svg>
                                    </button>
                                    <div class="plan-accordion-content" style="display: none;">
                                        <div class="plan-gifts-detail-list">
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">네이버페이</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">네이버페이</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <!-- 카드 구분선 (모바일용) -->
                        <hr class="plan-card-divider">

                        <!-- 네 번째 요금제 카드: 핀다이렉트 -->
                        <article class="basic-plan-card">
                            <a href="/plans/32628" class="plan-card-link">
                                <div class="plan-card-main-content">
                                    <div class="plan-card-header-body-frame">
                                        <div class="plan-card-top-header">
                                            <div class="plan-provider-rating-group">
                                                <span class="plan-provider-logo-text">핀다이렉트</span>
                                                <div class="plan-rating-group">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                                    </svg>
                                                    <span class="plan-rating-text">4.2</span>
                                                </div>
                                            </div>
                                            <div class="plan-badge-favorite-group">
                                                <button class="plan-favorite-btn-inline" aria-label="찜하기">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.9623 11.5427C18.5031 11.0065 18.8 10.2886 18.8 9.54803C18.8 8.80746 18.5032 8.08961 17.9623 7.5534C17.4166 7.01196 16.6657 6.7 15.8748 6.7C15.0838 6.7 14.3335 7.01145 13.7879 7.55284L13.549 7.7898C12.6914 8.64035 11.3084 8.64041 10.4508 7.78993L10.2121 7.55325C9.06574 6.41633 7.18394 6.41618 6.03759 7.55311C4.92082 8.66071 4.92079 10.4353 6.03758 11.543L12.0178 17.474C13.2794 16.2826 14.4839 15.0586 15.7184 13.804C16.4497 13.0609 17.1918 12.3068 17.9623 11.5427ZM11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C18.7821 13.8281 18.0602 14.5615 17.3378 15.2955C15.8837 16.7728 14.4273 18.2525 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166Z" fill="#868E96"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="plan-title-row">
                                            <span class="plan-title-text">[모요핫딜] [S] 핀다이렉트Z _2511</span>
                                        </div>

                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 11GB + 매일 2GB + 3Mbps</span>
                                                <button class="plan-info-icon-btn" aria-label="정보">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="plan-features-row">
                                                <span class="plan-feature-item">통화 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">문자 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">SKT망</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">LTE</span>
                                            </div>
                                        </div>

                                        <div class="plan-price-row">
                                            <div class="plan-price-left">
                                                <div class="plan-price-main-row">
                                                    <span class="plan-price-main">월 14,960원</span>
                                                    <button class="plan-info-icon-btn" aria-label="정보">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <span class="plan-price-after">7개월 이후 39,600원</span>
                                            </div>
                                            <div class="plan-price-right">
                                                <span class="plan-selection-count">4,420명이 선택</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            
                            <div class="plan-accordion-box">
                                <div class="plan-accordion">
                                    <button type="button" class="plan-accordion-trigger" aria-expanded="false">
                                        <div class="plan-gifts-accordion-content">
                                            <div class="plan-gift-icons-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="추가 데이터" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                            </div>
                                            <span class="plan-gifts-text-accordion">사은품 최대 2개</span>
                                        </div>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                                        </svg>
                                    </button>
                                    <div class="plan-accordion-content" style="display: none;">
                                        <div class="plan-gifts-detail-list">
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">매월 20GB 추가 데이터</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">네이버페이</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <!-- 카드 구분선 (모바일용) -->
                        <hr class="plan-card-divider">

                        <!-- 다섯 번째 요금제 카드: 고고모바일 -->
                        <article class="basic-plan-card">
                            <a href="/plans/32629" class="plan-card-link">
                                <div class="plan-card-main-content">
                                    <div class="plan-card-header-body-frame">
                                        <div class="plan-card-top-header">
                                            <div class="plan-provider-rating-group">
                                                <span class="plan-provider-logo-text">고고모바일</span>
                                                <div class="plan-rating-group">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                                    </svg>
                                                    <span class="plan-rating-text">4.2</span>
                                                </div>
                                            </div>
                                            <div class="plan-badge-favorite-group">
                                                <button class="plan-favorite-btn-inline" aria-label="찜하기">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.9623 11.5427C18.5031 11.0065 18.8 10.2886 18.8 9.54803C18.8 8.80746 18.5032 8.08961 17.9623 7.5534C17.4166 7.01196 16.6657 6.7 15.8748 6.7C15.0838 6.7 14.3335 7.01145 13.7879 7.55284L13.549 7.7898C12.6914 8.64035 11.3084 8.64041 10.4508 7.78993L10.2121 7.55325C9.06574 6.41633 7.18394 6.41618 6.03759 7.55311C4.92082 8.66071 4.92079 10.4353 6.03758 11.543L12.0178 17.474C13.2794 16.2826 14.4839 15.0586 15.7184 13.804C16.4497 13.0609 17.1918 12.3068 17.9623 11.5427ZM11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C18.7821 13.8281 18.0602 14.5615 17.3378 15.2955C15.8837 16.7728 14.4273 18.2525 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166Z" fill="#868E96"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="plan-title-row">
                                            <span class="plan-title-text">[모요핫딜] 무제한 11GB+3M(밀리의서재 Free)_11월</span>
                                        </div>

                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 11GB + 매일 2GB + 3Mbps</span>
                                                <button class="plan-info-icon-btn" aria-label="정보">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="plan-features-row">
                                                <span class="plan-feature-item">통화 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">문자 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">KT망</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">LTE</span>
                                            </div>
                                        </div>

                                        <div class="plan-price-row">
                                            <div class="plan-price-left">
                                                <div class="plan-price-main-row">
                                                    <span class="plan-price-main">월 15,000원</span>
                                                    <button class="plan-info-icon-btn" aria-label="정보">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <span class="plan-price-after">7개월 이후 36,300원</span>
                                            </div>
                                            <div class="plan-price-right">
                                                <span class="plan-selection-count">6,970명이 선택</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            
                            <div class="plan-accordion-box">
                                <div class="plan-accordion">
                                    <button type="button" class="plan-accordion-trigger" aria-expanded="false">
                                        <div class="plan-gifts-accordion-content">
                                            <div class="plan-gift-icons-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg" alt="KT유심&배송비 무료" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="데이터쿠폰" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="추가데이터" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg" alt="이마트 상품권" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/millie.svg" alt="밀리의 서재" width="24" height="24" class="plan-gift-icon-overlap">
                                            </div>
                                            <span class="plan-gifts-text-accordion">사은품 최대 6개</span>
                                        </div>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                                        </svg>
                                    </button>
                                    <div class="plan-accordion-content" style="display: none;">
                                        <div class="plan-gifts-detail-list">
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">KT유심&배송비 무료</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">데이터쿠폰 20GB x 3회</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">추가데이터 20GB 제공</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">이마트 상품권</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">네이버페이</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">밀리의 서재</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <!-- 카드 구분선 (모바일용) -->
                        <hr class="plan-card-divider">

                        <!-- 여섯 번째 요금제 카드: 찬스모바일 -->
                        <article class="basic-plan-card">
                            <a href="/plans/32630" class="plan-card-link">
                                <div class="plan-card-main-content">
                                    <div class="plan-card-header-body-frame">
                                        <div class="plan-card-top-header">
                                            <div class="plan-provider-rating-group">
                                                <span class="plan-provider-logo-text">찬스모바일</span>
                                                <div class="plan-rating-group">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                                    </svg>
                                                    <span class="plan-rating-text">4.5</span>
                                                </div>
                                            </div>
                                            <div class="plan-badge-favorite-group">
                                                <button class="plan-favorite-btn-inline" aria-label="찜하기">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.9623 11.5427C18.5031 11.0065 18.8 10.2886 18.8 9.54803C18.8 8.80746 18.5032 8.08961 17.9623 7.5534C17.4166 7.01196 16.6657 6.7 15.8748 6.7C15.0838 6.7 14.3335 7.01145 13.7879 7.55284L13.549 7.7898C12.6914 8.64035 11.3084 8.64041 10.4508 7.78993L10.2121 7.55325C9.06574 6.41633 7.18394 6.41618 6.03759 7.55311C4.92082 8.66071 4.92079 10.4353 6.03758 11.543L12.0178 17.474C13.2794 16.2826 14.4839 15.0586 15.7184 13.804C16.4497 13.0609 17.1918 12.3068 17.9623 11.5427ZM11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C18.7821 13.8281 18.0602 14.5615 17.3378 15.2955C15.8837 16.7728 14.4273 18.2525 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166Z" fill="#868E96"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="plan-title-row">
                                            <span class="plan-title-text">[모요핫딜]음성기본 11GB+일 2GB+</span>
                                        </div>

                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 11GB + 매일 2GB + 3Mbps</span>
                                                <button class="plan-info-icon-btn" aria-label="정보">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="plan-features-row">
                                                <span class="plan-feature-item">통화 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">문자 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">LG U+망</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">LTE</span>
                                            </div>
                                        </div>

                                        <div class="plan-price-row">
                                            <div class="plan-price-left">
                                                <div class="plan-price-main-row">
                                                    <span class="plan-price-main">월 15,000원</span>
                                                    <button class="plan-info-icon-btn" aria-label="정보">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <span class="plan-price-after">7개월 이후 38,500원</span>
                                            </div>
                                            <div class="plan-price-right">
                                                <span class="plan-selection-count">31,315명이 선택</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            
                            <div class="plan-accordion-box">
                                <div class="plan-accordion">
                                    <button type="button" class="plan-accordion-trigger" aria-expanded="false">
                                        <div class="plan-gifts-accordion-content">
                                            <div class="plan-gift-icons-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg" alt="유심/배송비 무료" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                            </div>
                                            <span class="plan-gifts-text-accordion">사은품 최대 2개</span>
                                        </div>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                                        </svg>
                                    </button>
                                    <div class="plan-accordion-content" style="display: none;">
                                        <div class="plan-gifts-detail-list">
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">유심/배송비 무료</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">네이버페이</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <!-- 카드 구분선 (모바일용) -->
                        <hr class="plan-card-divider">

                        <!-- 일곱 번째 요금제 카드: 이야기모바일 -->
                        <article class="basic-plan-card">
                            <a href="/plans/32631" class="plan-card-link">
                                <div class="plan-card-main-content">
                                    <div class="plan-card-header-body-frame">
                                        <div class="plan-card-top-header">
                                            <div class="plan-provider-rating-group">
                                                <span class="plan-provider-logo-text">이야기모바일</span>
                                                <div class="plan-rating-group">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                                    </svg>
                                                    <span class="plan-rating-text">4.5</span>
                                                </div>
                                            </div>
                                            <div class="plan-badge-favorite-group">
                                                <button class="plan-favorite-btn-inline" aria-label="찜하기">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.9623 11.5427C18.5031 11.0065 18.8 10.2886 18.8 9.54803C18.8 8.80746 18.5032 8.08961 17.9623 7.5534C17.4166 7.01196 16.6657 6.7 15.8748 6.7C15.0838 6.7 14.3335 7.01145 13.7879 7.55284L13.549 7.7898C12.6914 8.64035 11.3084 8.64041 10.4508 7.78993L10.2121 7.55325C9.06574 6.41633 7.18394 6.41618 6.03759 7.55311C4.92082 8.66071 4.92079 10.4353 6.03758 11.543L12.0178 17.474C13.2794 16.2826 14.4839 15.0586 15.7184 13.804C16.4497 13.0609 17.1918 12.3068 17.9623 11.5427ZM11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C18.7821 13.8281 18.0602 14.5615 17.3378 15.2955C15.8837 16.7728 14.4273 18.2525 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166Z" fill="#868E96"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="plan-title-row">
                                            <span class="plan-title-text">[모요핫딜] 이야기 완전 무제한 11GB+</span>
                                        </div>

                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 11GB + 매일 2GB + 3Mbps</span>
                                                <button class="plan-info-icon-btn" aria-label="정보">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="plan-features-row">
                                                <span class="plan-feature-item">통화 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">문자 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">SKT망</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">LTE</span>
                                            </div>
                                        </div>

                                        <div class="plan-price-row">
                                            <div class="plan-price-left">
                                                <div class="plan-price-main-row">
                                                    <span class="plan-price-main">월 15,000원</span>
                                                    <button class="plan-info-icon-btn" aria-label="정보">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <span class="plan-price-after">7개월 이후 39,600원</span>
                                            </div>
                                            <div class="plan-price-right">
                                                <span class="plan-selection-count">13,651명이 선택</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            
                            <div class="plan-accordion-box">
                                <div class="plan-accordion">
                                    <button type="button" class="plan-accordion-trigger" aria-expanded="false">
                                        <div class="plan-gifts-accordion-content">
                                            <div class="plan-gift-icons-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                            </div>
                                            <span class="plan-gifts-text-accordion">사은품 최대 2개</span>
                                        </div>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                                        </svg>
                                    </button>
                                    <div class="plan-accordion-content" style="display: none;">
                                        <div class="plan-gifts-detail-list">
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">네이버페이</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">네이버페이</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <!-- 카드 구분선 (모바일용) -->
                        <hr class="plan-card-divider">

                        <!-- 여덟 번째 요금제 카드: 핀다이렉트 -->
                        <article class="basic-plan-card">
                            <a href="/plans/32632" class="plan-card-link">
                                <div class="plan-card-main-content">
                                    <div class="plan-card-header-body-frame">
                                        <div class="plan-card-top-header">
                                            <div class="plan-provider-rating-group">
                                                <span class="plan-provider-logo-text">핀다이렉트</span>
                                                <div class="plan-rating-group">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                                    </svg>
                                                    <span class="plan-rating-text">4.2</span>
                                                </div>
                                            </div>
                                            <div class="plan-badge-favorite-group">
                                                <button class="plan-favorite-btn-inline" aria-label="찜하기">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.9623 11.5427C18.5031 11.0065 18.8 10.2886 18.8 9.54803C18.8 8.80746 18.5032 8.08961 17.9623 7.5534C17.4166 7.01196 16.6657 6.7 15.8748 6.7C15.0838 6.7 14.3335 7.01145 13.7879 7.55284L13.549 7.7898C12.6914 8.64035 11.3084 8.64041 10.4508 7.78993L10.2121 7.55325C9.06574 6.41633 7.18394 6.41618 6.03759 7.55311C4.92082 8.66071 4.92079 10.4353 6.03758 11.543L12.0178 17.474C13.2794 16.2826 14.4839 15.0586 15.7184 13.804C16.4497 13.0609 17.1918 12.3068 17.9623 11.5427ZM11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C18.7821 13.8281 18.0602 14.5615 17.3378 15.2955C15.8837 16.7728 14.4273 18.2525 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166Z" fill="#868E96"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="plan-title-row">
                                            <span class="plan-title-text">[모요핫딜] [K] 핀다이렉트Z 7GB+(네이버페이) _2511</span>
                                        </div>

                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 7GB + 1Mbps</span>
                                                <button class="plan-info-icon-btn" aria-label="정보">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="plan-features-row">
                                                <span class="plan-feature-item">통화 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">문자 무제한</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">KT망</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">LTE</span>
                                            </div>
                                        </div>

                                        <div class="plan-price-row">
                                            <div class="plan-price-left">
                                                <div class="plan-price-main-row">
                                                    <span class="plan-price-main">월 8,000원</span>
                                                    <button class="plan-info-icon-btn" aria-label="정보">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <span class="plan-price-after">7개월 이후 26,400원</span>
                                            </div>
                                            <div class="plan-price-right">
                                                <span class="plan-selection-count">4,407명이 선택</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            
                            <div class="plan-accordion-box">
                                <div class="plan-accordion">
                                    <button type="button" class="plan-accordion-trigger" aria-expanded="false">
                                        <div class="plan-gifts-accordion-content">
                                            <div class="plan-gift-icons-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="추가 데이터" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="추가 데이터" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg" alt="이마트 상품권" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                            </div>
                                            <span class="plan-gifts-text-accordion">사은품 최대 5개</span>
                                        </div>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                                        </svg>
                                    </button>
                                    <div class="plan-accordion-content" style="display: none;">
                                        <div class="plan-gifts-detail-list">
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">추가 데이터</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">매월 5GB 추가 데이터</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">이마트 상품권</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">네이버페이</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">네이버페이</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <!-- 카드 구분선 (모바일용) -->
                        <hr class="plan-card-divider">

                        <!-- 아홉 번째 요금제 카드: 찬스모바일 -->
                        <article class="basic-plan-card">
                            <a href="/plans/32633" class="plan-card-link">
                                <div class="plan-card-main-content">
                                    <div class="plan-card-header-body-frame">
                                        <div class="plan-card-top-header">
                                            <div class="plan-provider-rating-group">
                                                <span class="plan-provider-logo-text">찬스모바일</span>
                                                <div class="plan-rating-group">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                                    </svg>
                                                    <span class="plan-rating-text">4.5</span>
                                                </div>
                                            </div>
                                            <div class="plan-badge-favorite-group">
                                                <button class="plan-favorite-btn-inline" aria-label="찜하기">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.9623 11.5427C18.5031 11.0065 18.8 10.2886 18.8 9.54803C18.8 8.80746 18.5032 8.08961 17.9623 7.5534C17.4166 7.01196 16.6657 6.7 15.8748 6.7C15.0838 6.7 14.3335 7.01145 13.7879 7.55284L13.549 7.7898C12.6914 8.64035 11.3084 8.64041 10.4508 7.78993L10.2121 7.55325C9.06574 6.41633 7.18394 6.41618 6.03759 7.55311C4.92082 8.66071 4.92079 10.4353 6.03758 11.543L12.0178 17.474C13.2794 16.2826 14.4839 15.0586 15.7184 13.804C16.4497 13.0609 17.1918 12.3068 17.9623 11.5427ZM11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C18.7821 13.8281 18.0602 14.5615 17.3378 15.2955C15.8837 16.7728 14.4273 18.2525 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166Z" fill="#868E96"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="plan-title-row">
                                            <span class="plan-title-text">[모요핫딜]100분 15GB+</span>
                                        </div>

                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 15GB + 3Mbps</span>
                                                <button class="plan-info-icon-btn" aria-label="정보">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="plan-features-row">
                                                <span class="plan-feature-item">통화 100분</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">문자 100건</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">LG U+망</span>
                                                <div class="plan-feature-divider"></div>
                                                <span class="plan-feature-item">LTE</span>
                                            </div>
                                        </div>

                                        <div class="plan-price-row">
                                            <div class="plan-price-left">
                                                <div class="plan-price-main-row">
                                                    <span class="plan-price-main">월 14,000원</span>
                                                    <button class="plan-info-icon-btn" aria-label="정보">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <span class="plan-price-after">7개월 이후 30,580원</span>
                                            </div>
                                            <div class="plan-price-right">
                                                <span class="plan-selection-count">7,977명이 선택</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            
                            <div class="plan-accordion-box">
                                <div class="plan-accordion">
                                    <button type="button" class="plan-accordion-trigger" aria-expanded="false">
                                        <div class="plan-gifts-accordion-content">
                                            <div class="plan-gift-icons-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg" alt="유심/배송비 무료" width="24" height="24" class="plan-gift-icon-overlap">
                                                <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                            </div>
                                            <span class="plan-gifts-text-accordion">사은품 최대 2개</span>
                                        </div>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                                        </svg>
                                    </button>
                                    <div class="plan-accordion-content" style="display: none;">
                                        <div class="plan-gifts-detail-list">
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">유심/배송비 무료</span>
                                            </div>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">네이버페이</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>
            </div>

            <!-- 오른쪽 사이드바: 나에게 맞는 요금제 -->
            <aside class="plans-sidebar">
                <div class="sidebar-content">
                    <span class="sidebar-title">나에게 맞는 요금제</span>
                    
                    <div class="recent-plans-list">
                        <a href="/plans/29238" class="recent-plan-card">
                            <span class="recent-plan-name">알찬 음성기본 7GB+</span>
                            <span class="recent-plan-price">월 8,000원</span>
                        </a>
                        <a href="/plans/29237" class="recent-plan-card">
                            <span class="recent-plan-name">알찬 100분 15GB+3Mbps</span>
                            <span class="recent-plan-price">월 14,000원</span>
                        </a>
                        <a href="/plans/31480" class="recent-plan-card">
                            <span class="recent-plan-name">[모요only] 음성기본7GB+</span>
                            <span class="recent-plan-price">월 8,000원</span>
                        </a>
                    </div>

                    <div class="sidebar-recommendation">
                        <p>위 요금제와 비슷한 요금제를 추천해드려요</p>
                    </div>

                    <a href="/mypage/recently-plan-view" class="sidebar-more-btn">
                        <span>요금제 더보기</span>
                    </a>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php
// 푸터 포함
include 'includes/footer.php';
?>

<script src="assets/js/plan-accordion.js" defer></script>

