<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'plans';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 모니터링 시스템 (선택사항 - 주석 해제하여 사용)
// require_once 'includes/monitor.php';
// $monitor = new ConnectionMonitor();
// $monitor->logConnection();

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <!-- 필터 섹션 (스크롤 시 상단 고정) -->
    <div class="plans-filter-section">
        <div class="plans-filter-inner">

            <!-- 필터 및 정렬 버튼 그룹 -->
            <div class="plans-filter-group">
                <!-- 첫 번째 행: 필터 + 인터넷 결합 + 해시태그 버튼들 -->
                <div class="plans-filter-row">
                    <button class="plans-filter-btn">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 11.5C16.0487 11.5 17.3625 10.4941 17.8237 9.09999H19.9C20.5075 9.09999 21 8.60751 21 7.99999C21 7.39248 20.5075 6.89999 19.9 6.89999H17.8236C17.3625 5.50589 16.0487 4.5 14.5 4.5C12.9513 4.5 11.6375 5.50589 11.1764 6.89999H4.1C3.49249 6.89999 3 7.39248 3 7.99999C3 8.60751 3.49249 9.09999 4.1 9.09999H11.1763C11.6375 10.4941 12.9513 11.5 14.5 11.5ZM14.5 9.5C15.3284 9.5 16 8.82843 16 8C16 7.17157 15.3284 6.5 14.5 6.5C13.6716 6.5 13 7.17157 13 8C13 8.82843 13.6716 9.5 14.5 9.5Z" fill="#3F4750"></path>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3 16C3 15.3925 3.49249 14.9 4.1 14.9H6.17635C6.6375 13.5059 7.95128 12.5 9.5 12.5C11.0487 12.5 12.3625 13.5059 12.8236 14.9H19.9C20.5075 14.9 21 15.3925 21 16C21 16.6075 20.5075 17.1 19.9 17.1H12.8237C12.3625 18.4941 11.0487 19.5 9.5 19.5C7.95128 19.5 6.6375 18.4941 6.17635 17.1H4.1C3.49249 17.1 3 16.6075 3 16ZM11 16C11 16.8284 10.3284 17.5 9.5 17.5C8.67157 17.5 8 16.8284 8 16C8 15.1716 8.67157 14.5 9.5 14.5C10.3284 14.5 11 15.1716 11 16Z" fill="#3F4750"></path>
                        </svg>
                        <span class="plans-filter-text">필터</span>
                    </button>
                    <button class="plans-filter-btn">
                        <span class="plans-filter-text">인터넷 결합</span>
                    </button>
                    <button class="plans-filter-btn">
                        <span class="plans-filter-text">#베스트 요금제</span>
                    </button>
                    <button class="plans-filter-btn">
                        <span class="plans-filter-text">#만원 미만</span>
                    </button>
                    <button class="plans-filter-btn">
                        <span class="plans-filter-text">#장기 할인</span>
                    </button>
                    <button class="plans-filter-btn">
                        <span class="plans-filter-text">#100원</span>
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

                    <!-- 검색 결과 개수 -->
                    <div class="plans-results-count">
                        <span>2,415개의 결과</span>
                    </div>

                    <!-- 요금제 카드 -->
                    <div class="plans-list-container">
                        <article class="basic-plan-card">
                            <a href="/MVNO/plans/plan-detail.php?id=32627" class="plan-card-link">
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
                                            <span class="plan-title-text"> 11월한정 LTE 100GB+밀리+Data쿠폰60GB</span>
                                        </div>

                                        <!-- 데이터 정보와 기능 -->
                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 100GB + 5Mbps</span>
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
                            <a href="/MVNO/plans/plan-detail.php?id=32632" class="plan-card-link">
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
                                            <span class="plan-title-text"> LTE무제한 100GB+5M(CU20%할인)_11월</span>
                                        </div>

                                        <!-- 데이터 정보와 기능 -->
                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 100GB + 5Mbps</span>
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
                            <a href="/MVNO/plans/plan-detail.php?id=29290" class="plan-card-link">
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
                                            <span class="plan-title-text"> 이야기 완전 무제한 100GB+</span>
                                        </div>

                                        <!-- 데이터 정보와 기능 -->
                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 100GB + 5Mbps</span>
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
                            <a href="/MVNO/plans/plan-detail.php?id=32628" class="plan-card-link">
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
                                            <span class="plan-title-text"> [S] 핀다이렉트Z _2511</span>
                                        </div>

                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 11GB + 매일 2GB + 3Mbps</span>
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
                            <a href="/MVNO/plans/plan-detail.php?id=32629" class="plan-card-link">
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
                                            <span class="plan-title-text"> 무제한 11GB+3M(밀리의서재 Free)_11월</span>
                                        </div>

                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 11GB + 매일 2GB + 3Mbps</span>
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
                            <a href="/MVNO/plans/plan-detail.php?id=32630" class="plan-card-link">
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
                                            <span class="plan-title-text">음성기본 11GB+일 2GB+</span>
                                        </div>

                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 11GB + 매일 2GB + 3Mbps</span>
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
                            <a href="/MVNO/plans/plan-detail.php?id=32631" class="plan-card-link">
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
                                            <span class="plan-title-text"> 이야기 완전 무제한 11GB+</span>
                                        </div>

                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 11GB + 매일 2GB + 3Mbps</span>
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
                            <a href="/MVNO/plans/plan-detail.php?id=32632" class="plan-card-link">
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
                                            <span class="plan-title-text"> [K] 핀다이렉트Z 7GB+(네이버페이) _2511</span>
                                        </div>

                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 7GB + 1Mbps</span>
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
                            <a href="/MVNO/plans/plan-detail.php?id=32633" class="plan-card-link">
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
                                            <span class="plan-title-text">100분 15GB+</span>
                                        </div>

                                        <div class="plan-info-section">
                                            <div class="plan-data-row">
                                                <span class="plan-data-main">월 15GB + 3Mbps</span>
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

                <!-- 페이지네이션 -->
                <div class="pagination-wrapper" data-sentry-component="LinkPagination" data-sentry-source-file="LinkPagination.tsx">
                    <ul class="pagination-list">
                        <li>
                            <a class="pagination-btn pagination-nav" href="plans.php?page=10" aria-label="이전 페이지">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" data-sentry-element="svg" data-sentry-component="ChevronLeftIcon" data-sentry-source-file="ChevronLeftIcon.tsx">
                                    <path d="M10.5303 3.53033C10.8232 3.23744 10.8232 2.76256 10.5303 2.46967C10.2374 2.17678 9.76256 2.17678 9.46967 2.46967L10.5303 3.53033ZM5 8L4.46967 7.46967C4.17678 7.76256 4.17678 8.23744 4.46967 8.53033L5 8ZM9.46967 13.5303C9.76256 13.8232 10.2374 13.8232 10.5303 13.5303C10.8232 13.2374 10.8232 12.7626 10.5303 12.4697L9.46967 13.5303ZM9.46967 2.46967L4.46967 7.46967L5.53033 8.53033L10.5303 3.53033L9.46967 2.46967ZM4.46967 8.53033L9.46967 13.5303L10.5303 12.4697L5.53033 7.46967L4.46967 8.53033Z" data-sentry-element="path" data-sentry-source-file="ChevronLeftIcon.tsx"></path>
                                </svg>
                            </a>
                        </li>
                        <li><a class="pagination-btn pagination-page active" href="plans.php?page=11">11</a></li>
                        <li><a class="pagination-btn pagination-page" href="plans.php?page=12">12</a></li>
                        <li><a class="pagination-btn pagination-page" href="plans.php?page=13">13</a></li>
                        <li><a class="pagination-btn pagination-page" href="plans.php?page=14">14</a></li>
                        <li><a class="pagination-btn pagination-page" href="plans.php?page=15">15</a></li>
                        <li><a class="pagination-btn pagination-page" href="plans.php?page=16">16</a></li>
                        <li><a class="pagination-btn pagination-page" href="plans.php?page=17">17</a></li>
                        <li><a class="pagination-btn pagination-page" href="plans.php?page=18">18</a></li>
                        <li><a class="pagination-btn pagination-page" href="plans.php?page=19">19</a></li>
                        <li><a class="pagination-btn pagination-page" href="plans.php?page=20">20</a></li>
                        <li>
                            <a class="pagination-btn pagination-nav" href="plans.php?page=21" aria-label="다음 페이지">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" data-sentry-element="svg" data-sentry-component="ChevronRightIcon" data-sentry-source-file="ChevronRightIcon.tsx">
                                    <path d="M5.46967 12.4697C5.17678 12.7626 5.17678 13.2374 5.46967 13.5303C5.76256 13.8232 6.23744 13.8232 6.53033 13.5303L5.46967 12.4697ZM11 8L11.5303 8.53033C11.8232 8.23744 11.8232 7.76256 11.5303 7.46967L11 8ZM6.53033 2.46967C6.23744 2.17678 5.76256 2.17678 5.46967 2.46967C5.17678 2.76256 5.17678 3.23744 5.46967 3.53033L6.53033 2.46967ZM6.53033 13.5303L11.5303 8.53033L10.4697 7.46967L5.46967 12.4697L6.53033 13.5303ZM11.5303 7.46967L6.53033 2.46967L5.46967 3.53033L10.4697 8.53033L11.5303 7.46967Z" data-sentry-element="path" data-sentry-source-file="ChevronRightIcon.tsx"></path>
                                </svg>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</main>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

<script src="/MVNO/assets/js/plan-accordion.js" defer></script>

<script>
// 필터가 화면에서 사라질 때 상단에 고정 (요금제 페이지)
(function() {
    const filterSection = document.querySelector('.plans-filter-section');
    const header = document.getElementById('mainHeader');
    const resultsCount = document.querySelector('.plans-results-count');
    const themeSection = document.querySelector('.theme-plans-list-section');
    
    if (!filterSection) return;
    
    let lastScrollTop = 0;
    let isFilterSticky = false;
    let isFilterFixed = false;
    let filterOriginalTop = 0;
    let filterHeight = 0;
    
    // 필터 높이 계산
    function calculateFilterHeight() {
        if (filterSection) {
            filterHeight = filterSection.offsetHeight;
        }
    }
    
    // 초기 필터 높이 계산
    calculateFilterHeight();
    
    // 리사이즈 시 필터 높이 재계산
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            calculateFilterHeight();
        }, 100);
    });
    
    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const filterRect = filterSection.getBoundingClientRect();
        const filterTop = filterRect.top;
        
        // 필터의 원래 위치 저장 (처음 로드 시)
        if (filterOriginalTop === 0 && scrollTop === 0) {
            filterOriginalTop = filterRect.top + scrollTop;
        }
        
        // 스크롤이 시작되면 sticky 모드로 전환
        if (scrollTop > 10 && !isFilterSticky) {
            calculateFilterHeight(); // 필터 높이 재계산
            filterSection.classList.add('filter-sticky');
            if (resultsCount) resultsCount.classList.add('filter-active');
            if (themeSection) themeSection.classList.add('filter-active');
            isFilterSticky = true;
        }
        
        // 필터가 화면 상단 밖으로 나갔는지 확인 (위로 스크롤해서 사라짐)
        if (filterTop < 0 && isFilterSticky && !isFilterFixed) {
            // 필터가 사라졌으므로 상단에 고정
            calculateFilterHeight(); // 필터 높이 재계산
            filterSection.classList.remove('filter-sticky');
            filterSection.classList.add('filter-fixed');
            isFilterFixed = true;
        } 
        // 스크롤이 다시 위로 올라가서 필터의 원래 위치 근처에 도달했는지 확인
        else if (scrollTop < filterOriginalTop - 50 && isFilterFixed) {
            // 필터를 sticky 모드로 복원
            calculateFilterHeight(); // 필터 높이 재계산
            filterSection.classList.remove('filter-fixed');
            filterSection.classList.add('filter-sticky');
            isFilterFixed = false;
        }
        // 스크롤이 맨 위로 돌아갔을 때
        else if (scrollTop <= 10 && isFilterSticky) {
            // 필터를 원래 위치로 복원
            filterSection.classList.remove('filter-sticky');
            filterSection.classList.remove('filter-fixed');
            if (resultsCount) resultsCount.classList.remove('filter-active');
            if (themeSection) themeSection.classList.remove('filter-active');
            isFilterSticky = false;
            isFilterFixed = false;
        }
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }
    
    // 스크롤 이벤트 최적화 (requestAnimationFrame 사용)
    let ticking = false;
    function onScroll() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                handleScroll();
                ticking = false;
            });
            ticking = true;
        }
    }
    
    // 스크롤 이벤트 리스너
    window.addEventListener('scroll', onScroll, { passive: true });
    
    // 초기 실행
    handleScroll();
})();

// 필터 버튼 클릭 이벤트 핸들러
(function() {
    const filterButtons = document.querySelectorAll('.plans-filter-btn');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // 클릭된 버튼의 active 상태 토글
            if (this.classList.contains('active')) {
                this.classList.remove('active');
            } else {
                this.classList.add('active');
            }
        });
    });
})();
</script>

