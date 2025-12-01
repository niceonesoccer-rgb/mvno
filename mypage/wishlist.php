<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div class="content-layout wishlist-page">
        <!-- 페이지 제목 -->
        <div style="margin-bottom: 24px; padding-top: 24px; display: flex; align-items: center; gap: 12px;">
            <a href="/MVNO/mypage/mypage.php" style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; transition: background-color 0.2s; text-decoration: none; color: inherit;" onmouseover="this.style.backgroundColor='#f3f4f6'" onmouseout="this.style.backgroundColor='transparent'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="transform: rotate(180deg);">
                    <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <h1 style="font-size: 24px; font-weight: 700; margin: 0; flex: 1;">내가 찜한 요금제</h1>
        </div>

        <!-- 검색 결과 개수 -->
        <div class="plans-results-count">
            <span>2,415개의 결과</span>
        </div>

        <!-- 요금제 카드 목록 -->
        <div class="plans-list-container">
            <!-- 첫 번째 요금제: 쉐이크모바일 -->
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

                            <!-- 제목 -->
                            <div class="plan-title-row">
                                <span class="plan-title-text">11월한정 LTE 100GB+밀리+Data쿠폰60GB</span>
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
                                    <span class="plan-selection-count">29,448명이 신청</span>
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
                                    <span class="plan-gift-detail-text">이마트 상품권</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">네이버페이</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">데이터쿠폰 20GB</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">밀리의 서재</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">SOLO결합(+20GB)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- 두 번째 요금제: 고고모바일 -->
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
                                            <path d="M21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C17.3144 15.2837 15.1837 17.5556 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803Z" fill="#FA5252"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- 제목 -->
                            <div class="plan-title-row">
                                <span class="plan-title-text">LTE무제한 100GB+5M(CU20%할인)_11월</span>
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
                                    <span class="plan-selection-count">12,353명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="데이터쿠폰 20GB x 3회" width="24" height="24" class="plan-gift-icon-overlap">
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

            <!-- 세 번째 요금제: 이야기모바일 -->
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
                                            <path d="M21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C17.3144 15.2837 15.1837 17.5556 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803Z" fill="#FA5252"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- 제목 -->
                            <div class="plan-title-row">
                                <span class="plan-title-text">이야기 완전 무제한 100GB+</span>
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
                                    <span class="plan-selection-count">17,816명이 신청</span>
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

            <!-- 네 번째 요금제: 프리티 -->
            <article class="basic-plan-card wishlist-item">
                <a href="/MVNO/plans/plan-detail.php?id=32633" class="plan-card-link">
                    <div class="plan-card-main-content">
                        <div class="plan-card-header-body-frame">
                            <div class="plan-card-top-header">
                                <div class="plan-provider-rating-group">
                                    <span class="plan-provider-logo-text">프리티</span>
                                    <div class="plan-rating-group">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                        </svg>
                                        <span class="plan-rating-text">4.1</span>
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
                            <div class="plan-title-row">
                                <span class="plan-title-text">5G 무제한 150GB+10Mbps</span>
                            </div>
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 150GB + 10Mbps</span>
                                </div>
                                <div class="plan-features-row">
                                    <span class="plan-feature-item">통화 무제한</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">문자 무제한</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">LG U+망</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">5G</span>
                                </div>
                            </div>
                            <div class="plan-price-row">
                                <div class="plan-price-left">
                                    <div class="plan-price-main-row">
                                        <span class="plan-price-main">월 19,000원</span>
                                    </div>
                                    <span class="plan-price-after">7개월 이후 45,000원</span>
                                </div>
                                <div class="plan-price-right">
                                    <span class="plan-selection-count">8,234명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="데이터쿠폰 30GB" width="24" height="24" class="plan-gift-icon-overlap">
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
                                    <span class="plan-gift-detail-text">데이터쿠폰 30GB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- 다섯 번째 요금제: 알뜰폰 -->
            <article class="basic-plan-card wishlist-item">
                <a href="/MVNO/plans/plan-detail.php?id=32634" class="plan-card-link">
                    <div class="plan-card-main-content">
                        <div class="plan-card-header-body-frame">
                            <div class="plan-card-top-header">
                                <div class="plan-provider-rating-group">
                                    <span class="plan-provider-logo-text">알뜰폰</span>
                                    <div class="plan-rating-group">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                </svg>
                                        <span class="plan-rating-text">4.0</span>
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
                            <div class="plan-title-row">
                                <span class="plan-title-text">LTE 50GB+3Mbps 기본 요금제</span>
                            </div>
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 50GB + 3Mbps</span>
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
                                        <span class="plan-price-main">월 12,000원</span>
                                    </div>
                                    <span class="plan-price-after">7개월 이후 35,000원</span>
                                </div>
                                <div class="plan-price-right">
                                    <span class="plan-selection-count">15,678명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg" alt="이마트 상품권" width="24" height="24" class="plan-gift-icon-overlap">
                                </div>
                                <span class="plan-gifts-text-accordion">사은품 최대 1개</span>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                            </svg>
                        </button>
                        <div class="plan-accordion-content" style="display: none;">
                            <div class="plan-gifts-detail-list">
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">이마트 상품권</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- 여섯 번째 요금제: 헬로모바일 -->
            <article class="basic-plan-card wishlist-item">
                <a href="/MVNO/plans/plan-detail.php?id=32635" class="plan-card-link">
                    <div class="plan-card-main-content">
                        <div class="plan-card-header-body-frame">
                            <div class="plan-card-top-header">
                                <div class="plan-provider-rating-group">
                                    <span class="plan-provider-logo-text">헬로모바일</span>
                                    <div class="plan-rating-group">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                        </svg>
                                        <span class="plan-rating-text">4.4</span>
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
                            <div class="plan-title-row">
                                <span class="plan-title-text">5G 슈퍼플랜 200GB+15Mbps</span>
                            </div>
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 200GB + 15Mbps</span>
                                </div>
                                <div class="plan-features-row">
                                    <span class="plan-feature-item">통화 무제한</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">문자 무제한</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">KT망</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">5G</span>
                                </div>
                            </div>
                            <div class="plan-price-row">
                                <div class="plan-price-left">
                                    <div class="plan-price-main-row">
                                        <span class="plan-price-main">월 22,000원</span>
                                    </div>
                                    <span class="plan-price-after">7개월 이후 55,000원</span>
                                </div>
                                <div class="plan-price-right">
                                    <span class="plan-selection-count">6,543명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="데이터쿠폰 50GB" width="24" height="24" class="plan-gift-icon-overlap">
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg" alt="이마트 상품권" width="24" height="24" class="plan-gift-icon-overlap">
                                </div>
                                <span class="plan-gifts-text-accordion">사은품 최대 3개</span>
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
                                    <span class="plan-gift-detail-text">데이터쿠폰 50GB</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">이마트 상품권</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- 일곱 번째 요금제: 유니크모바일 -->
            <article class="basic-plan-card wishlist-item">
                <a href="/MVNO/plans/plan-detail.php?id=32636" class="plan-card-link">
                    <div class="plan-card-main-content">
                        <div class="plan-card-header-body-frame">
                            <div class="plan-card-top-header">
                                <div class="plan-provider-rating-group">
                                    <span class="plan-provider-logo-text">유니크모바일</span>
                                    <div class="plan-rating-group">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                        </svg>
                                        <span class="plan-rating-text">4.6</span>
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
                            <div class="plan-title-row">
                                <span class="plan-title-text">LTE 프리미엄 80GB+8Mbps</span>
                            </div>
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 80GB + 8Mbps</span>
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
                                    <span class="plan-price-after">7개월 이후 38,000원</span>
                                </div>
                                <div class="plan-price-right">
                                    <span class="plan-selection-count">11,234명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/cu.svg" alt="CU 상품권" width="24" height="24" class="plan-gift-icon-overlap">
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="데이터쿠폰 25GB" width="24" height="24" class="plan-gift-icon-overlap">
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
                                    <span class="plan-gift-detail-text">CU 상품권</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">데이터쿠폰 25GB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- 여덟 번째 요금제: 모빙 -->
            <article class="basic-plan-card wishlist-item">
                <a href="/MVNO/plans/plan-detail.php?id=32637" class="plan-card-link">
                    <div class="plan-card-main-content">
                        <div class="plan-card-header-body-frame">
                            <div class="plan-card-top-header">
                                <div class="plan-provider-rating-group">
                                    <span class="plan-provider-logo-text">모빙</span>
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
                            <div class="plan-title-row">
                                <span class="plan-title-text">5G 스탠다드 120GB+12Mbps</span>
                            </div>
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 120GB + 12Mbps</span>
                                </div>
                                <div class="plan-features-row">
                                    <span class="plan-feature-item">통화 무제한</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">문자 무제한</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">SKT망</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">5G</span>
                                </div>
                            </div>
                            <div class="plan-price-row">
                                <div class="plan-price-left">
                                    <div class="plan-price-main-row">
                                        <span class="plan-price-main">월 18,000원</span>
                                    </div>
                                    <span class="plan-price-after">7개월 이후 48,000원</span>
                                </div>
                                <div class="plan-price-right">
                                    <span class="plan-selection-count">9,876명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/millie.svg" alt="밀리의 서재" width="24" height="24" class="plan-gift-icon-overlap">
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
                                    <span class="plan-gift-detail-text">밀리의 서재</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- 아홉 번째 요금제: 엠티모바일 -->
            <article class="basic-plan-card wishlist-item">
                <a href="/MVNO/plans/plan-detail.php?id=32638" class="plan-card-link">
                    <div class="plan-card-main-content">
                        <div class="plan-card-header-body-frame">
                            <div class="plan-card-top-header">
                                <div class="plan-provider-rating-group">
                                    <span class="plan-provider-logo-text">엠티모바일</span>
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
                                            <path d="M21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C17.3144 15.2837 15.1837 17.5556 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803Z" fill="#FA5252"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="plan-title-row">
                                <span class="plan-title-text">LTE 베이직 30GB+2Mbps</span>
                            </div>
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 30GB + 2Mbps</span>
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
                                        <span class="plan-price-main">월 10,000원</span>
                                    </div>
                                    <span class="plan-price-after">7개월 이후 32,000원</span>
                                </div>
                                <div class="plan-price-right">
                                    <span class="plan-selection-count">20,123명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="데이터쿠폰 10GB" width="24" height="24" class="plan-gift-icon-overlap">
                                </div>
                                <span class="plan-gifts-text-accordion">사은품 최대 1개</span>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                            </svg>
                        </button>
                        <div class="plan-accordion-content" style="display: none;">
                            <div class="plan-gifts-detail-list">
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">데이터쿠폰 10GB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- 열 번째 요금제: 스마텔 -->
            <article class="basic-plan-card wishlist-item">
                <a href="/MVNO/plans/plan-detail.php?id=32639" class="plan-card-link">
                    <div class="plan-card-main-content">
                        <div class="plan-card-header-body-frame">
                            <div class="plan-card-top-header">
                                <div class="plan-provider-rating-group">
                                    <span class="plan-provider-logo-text">스마텔</span>
                                    <div class="plan-rating-group">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                        </svg>
                                        <span class="plan-rating-text">4.7</span>
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
                            <div class="plan-title-row">
                                <span class="plan-title-text">5G 울트라 250GB+20Mbps 프리미엄</span>
                            </div>
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 250GB + 20Mbps</span>
                                </div>
                                <div class="plan-features-row">
                                    <span class="plan-feature-item">통화 무제한</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">문자 무제한</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">SKT망</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">5G</span>
                                </div>
                            </div>
                            <div class="plan-price-row">
                                <div class="plan-price-left">
                                    <div class="plan-price-main-row">
                                        <span class="plan-price-main">월 25,000원</span>
                                    </div>
                                    <span class="plan-price-after">7개월 이후 60,000원</span>
                                </div>
                                <div class="plan-price-right">
                                    <span class="plan-selection-count">4,567명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg" alt="이마트 상품권" width="24" height="24" class="plan-gift-icon-overlap">
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="데이터쿠폰 60GB" width="24" height="24" class="plan-gift-icon-overlap">
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/millie.svg" alt="밀리의 서재" width="24" height="24" class="plan-gift-icon-overlap">
                                </div>
                                <span class="plan-gifts-text-accordion">사은품 최대 4개</span>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                            </svg>
                        </button>
                        <div class="plan-accordion-content" style="display: none;">
                            <div class="plan-gifts-detail-list">
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">이마트 상품권</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">네이버페이</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">데이터쿠폰 60GB</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">밀리의 서재</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- 열한 번째 요금제: 티플러스 -->
            <article class="basic-plan-card wishlist-item" style="display: none;">
                <a href="/MVNO/plans/plan-detail.php?id=32641" class="plan-card-link">
                    <div class="plan-card-main-content">
                        <div class="plan-card-header-body-frame">
                            <div class="plan-card-top-header">
                                <div class="plan-provider-rating-group">
                                    <span class="plan-provider-logo-text">티플러스</span>
                                    <div class="plan-rating-group">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                        </svg>
                                        <span class="plan-rating-text">4.4</span>
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
                            <div class="plan-title-row">
                                <span class="plan-title-text">LTE 스탠다드 60GB+6Mbps</span>
                            </div>
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 60GB + 6Mbps</span>
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
                                        <span class="plan-price-main">월 13,000원</span>
                                    </div>
                                    <span class="plan-price-after">7개월 이후 36,000원</span>
                                </div>
                                <div class="plan-price-right">
                                    <span class="plan-selection-count">7,890명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="데이터쿠폰 15GB" width="24" height="24" class="plan-gift-icon-overlap">
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
                                    <span class="plan-gift-detail-text">데이터쿠폰 15GB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- 열두 번째 요금제: 알뜰통신 -->
            <article class="basic-plan-card wishlist-item" style="display: none;">
                <a href="/MVNO/plans/plan-detail.php?id=32642" class="plan-card-link">
                    <div class="plan-card-main-content">
                        <div class="plan-card-header-body-frame">
                            <div class="plan-card-top-header">
                                <div class="plan-provider-rating-group">
                                    <span class="plan-provider-logo-text">알뜰통신</span>
                                    <div class="plan-rating-group">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                        </svg>
                                        <span class="plan-rating-text">4.1</span>
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
                            <div class="plan-title-row">
                                <span class="plan-title-text">5G 라이트 90GB+9Mbps</span>
                            </div>
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 90GB + 9Mbps</span>
                                </div>
                                <div class="plan-features-row">
                                    <span class="plan-feature-item">통화 무제한</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">문자 무제한</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">LG U+망</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">5G</span>
                                </div>
                            </div>
                            <div class="plan-price-row">
                                <div class="plan-price-left">
                                    <div class="plan-price-main-row">
                                        <span class="plan-price-main">월 16,000원</span>
                                    </div>
                                    <span class="plan-price-after">7개월 이후 40,000원</span>
                                </div>
                                <div class="plan-price-right">
                                    <span class="plan-selection-count">5,432명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/cu.svg" alt="CU 상품권" width="24" height="24" class="plan-gift-icon-overlap">
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

            <!-- 열세 번째 요금제: 모바일알뜰 -->
            <article class="basic-plan-card wishlist-item" style="display: none;">
                <a href="/MVNO/plans/plan-detail.php?id=32643" class="plan-card-link">
                    <div class="plan-card-main-content">
                        <div class="plan-card-header-body-frame">
                            <div class="plan-card-top-header">
                                <div class="plan-provider-rating-group">
                                    <span class="plan-provider-logo-text">모바일알뜰</span>
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
                            <div class="plan-title-row">
                                <span class="plan-title-text">LTE 프리미엄 70GB+7Mbps</span>
                            </div>
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 70GB + 7Mbps</span>
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
                                        <span class="plan-price-main">월 14,000원</span>
                                    </div>
                                    <span class="plan-price-after">7개월 이후 37,000원</span>
                                </div>
                                <div class="plan-price-right">
                                    <span class="plan-selection-count">8,765명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg" alt="이마트 상품권" width="24" height="24" class="plan-gift-icon-overlap">
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="데이터쿠폰 20GB" width="24" height="24" class="plan-gift-icon-overlap">
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
                                    <span class="plan-gift-detail-text">이마트 상품권</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">데이터쿠폰 20GB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- 열네 번째 요금제: 프리티모바일 -->
            <article class="basic-plan-card wishlist-item" style="display: none;">
                <a href="/MVNO/plans/plan-detail.php?id=32644" class="plan-card-link">
                    <div class="plan-card-main-content">
                        <div class="plan-card-header-body-frame">
                            <div class="plan-card-top-header">
                                <div class="plan-provider-rating-group">
                                    <span class="plan-provider-logo-text">프리티모바일</span>
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
                                            <path d="M21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C17.3144 15.2837 15.1837 17.5556 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803Z" fill="#FA5252"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="plan-title-row">
                                <span class="plan-title-text">5G 베이직 110GB+11Mbps</span>
                            </div>
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 110GB + 11Mbps</span>
                                </div>
                                <div class="plan-features-row">
                                    <span class="plan-feature-item">통화 무제한</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">문자 무제한</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">KT망</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">5G</span>
                                </div>
                            </div>
                            <div class="plan-price-row">
                                <div class="plan-price-left">
                                    <div class="plan-price-main-row">
                                        <span class="plan-price-main">월 20,000원</span>
                                    </div>
                                    <span class="plan-price-after">7개월 이후 50,000원</span>
                                </div>
                                <div class="plan-price-right">
                                    <span class="plan-selection-count">6,234명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/millie.svg" alt="밀리의 서재" width="24" height="24" class="plan-gift-icon-overlap">
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="데이터쿠폰 25GB" width="24" height="24" class="plan-gift-icon-overlap">
                                </div>
                                <span class="plan-gifts-text-accordion">사은품 최대 3개</span>
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
                                    <span class="plan-gift-detail-text">밀리의 서재</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">데이터쿠폰 25GB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- 열다섯 번째 요금제: 모바일알뜰 -->
            <article class="basic-plan-card wishlist-item" style="display: none;">
                <a href="/MVNO/plans/plan-detail.php?id=32645" class="plan-card-link">
                    <div class="plan-card-main-content">
                        <div class="plan-card-header-body-frame">
                            <div class="plan-card-top-header">
                                <div class="plan-provider-rating-group">
                                    <span class="plan-provider-logo-text">모바일알뜰</span>
                                    <div class="plan-rating-group">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                        </svg>
                                        <span class="plan-rating-text">4.0</span>
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
                            <div class="plan-title-row">
                                <span class="plan-title-text">LTE 미니 20GB+2Mbps</span>
                            </div>
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 20GB + 2Mbps</span>
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
                                        <span class="plan-price-main">월 8,000원</span>
                                    </div>
                                    <span class="plan-price-after">7개월 이후 28,000원</span>
                                </div>
                                <div class="plan-price-right">
                                    <span class="plan-selection-count">12,345명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="데이터쿠폰 5GB" width="24" height="24" class="plan-gift-icon-overlap">
                                </div>
                                <span class="plan-gifts-text-accordion">사은품 최대 1개</span>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                            </svg>
                        </button>
                        <div class="plan-accordion-content" style="display: none;">
                            <div class="plan-gifts-detail-list">
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">데이터쿠폰 5GB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- 열여섯 번째 요금제: 알뜰모바일 -->
            <article class="basic-plan-card wishlist-item" style="display: none;">
                <a href="/MVNO/plans/plan-detail.php?id=32646" class="plan-card-link">
                    <div class="plan-card-main-content">
                        <div class="plan-card-header-body-frame">
                            <div class="plan-card-top-header">
                                <div class="plan-provider-rating-group">
                                    <span class="plan-provider-logo-text">알뜰모바일</span>
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
                                            <path d="M21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C17.3144 15.2837 15.1837 17.5556 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803Z" fill="#FA5252"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="plan-title-row">
                                <span class="plan-title-text">5G 맥스 180GB+18Mbps</span>
                            </div>
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 180GB + 18Mbps</span>
                                </div>
                                <div class="plan-features-row">
                                    <span class="plan-feature-item">통화 무제한</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">문자 무제한</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">SKT망</span>
                                    <div class="plan-feature-divider"></div>
                                    <span class="plan-feature-item">5G</span>
                                </div>
                            </div>
                            <div class="plan-price-row">
                                <div class="plan-price-left">
                                    <div class="plan-price-main-row">
                                        <span class="plan-price-main">월 23,000원</span>
                                    </div>
                                    <span class="plan-price-after">7개월 이후 58,000원</span>
                                </div>
                                <div class="plan-price-right">
                                    <span class="plan-selection-count">3,456명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg" alt="이마트 상품권" width="24" height="24" class="plan-gift-icon-overlap">
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="데이터쿠폰 40GB" width="24" height="24" class="plan-gift-icon-overlap">
                                </div>
                                <span class="plan-gifts-text-accordion">사은품 최대 3개</span>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                            </svg>
                        </button>
                        <div class="plan-accordion-content" style="display: none;">
                            <div class="plan-gifts-detail-list">
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">이마트 상품권</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">네이버페이</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">데이터쿠폰 40GB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- 열일곱 번째 요금제: 스마트알뜰 -->
            <article class="basic-plan-card wishlist-item" style="display: none;">
                <a href="/MVNO/plans/plan-detail.php?id=32647" class="plan-card-link">
                    <div class="plan-card-main-content">
                        <div class="plan-card-header-body-frame">
                            <div class="plan-card-top-header">
                                <div class="plan-provider-rating-group">
                                    <span class="plan-provider-logo-text">스마트알뜰</span>
                                    <div class="plan-rating-group">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"/>
                                        </svg>
                                        <span class="plan-rating-text">4.6</span>
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
                            <div class="plan-title-row">
                                <span class="plan-title-text">LTE 플러스 130GB+13Mbps</span>
                            </div>
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 130GB + 13Mbps</span>
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
                                        <span class="plan-price-main">월 21,000원</span>
                                    </div>
                                    <span class="plan-price-after">7개월 이후 53,000원</span>
                                </div>
                                <div class="plan-price-right">
                                    <span class="plan-selection-count">5,678명이 신청</span>
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
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/cu.svg" alt="CU 상품권" width="24" height="24" class="plan-gift-icon-overlap">
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="24" height="24" class="plan-gift-icon-overlap">
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg" alt="데이터쿠폰 35GB" width="24" height="24" class="plan-gift-icon-overlap">
                                    <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/millie.svg" alt="밀리의 서재" width="24" height="24" class="plan-gift-icon-overlap">
                                </div>
                                <span class="plan-gifts-text-accordion">사은품 최대 4개</span>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"/>
                            </svg>
                        </button>
                        <div class="plan-accordion-content" style="display: none;">
                            <div class="plan-gifts-detail-list">
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">CU 상품권</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">네이버페이</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">데이터쿠폰 35GB</span>
                                </div>
                                <div class="plan-gift-detail-item">
                                    <span class="plan-gift-detail-text">밀리의 서재</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </div>
        
        <!-- 더보기 버튼 -->
        <div style="margin-top: 32px; margin-bottom: 32px;" id="moreButtonContainer">
            <button class="plan-review-more-btn" id="loadMoreBtn">
                더보기 (<?php 
                $hiddenItems = 7; // 숨겨진 상품 개수 (11번째부터)
                echo $hiddenItems > 10 ? 10 : $hiddenItems; 
                ?>개)
            </button>
        </div>
    </div>
</main>

<script src="../assets/js/plan-accordion.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const allItems = Array.from(document.querySelectorAll('.wishlist-item'));
    // 처음 10개는 이미 표시되어 있으므로, 11번째부터가 숨겨진 항목
    const hiddenItems = allItems.filter(item => item.style.display === 'none');
    let visibleCount = 10; // 처음 10개는 이미 표시됨
    const loadCount = 10; // 한 번에 보여줄 개수
    
    if (hiddenItems.length === 0) {
        const moreButtonContainer = document.getElementById('moreButtonContainer');
        if (moreButtonContainer) {
            moreButtonContainer.style.display = 'none';
        }
        return;
    }
    
    function updateButtonText() {
        const remaining = allItems.length - visibleCount;
        if (remaining > 0) {
            const showCount = remaining > loadCount ? loadCount : remaining;
            loadMoreBtn.textContent = `더보기 (${showCount}개)`;
        }
    }
    
    updateButtonText();
    
    loadMoreBtn.addEventListener('click', function() {
        // 다음 10개씩 표시
        const endCount = Math.min(visibleCount + loadCount, allItems.length);
        for (let i = visibleCount; i < endCount; i++) {
            if (allItems[i]) {
                allItems[i].style.display = 'block';
            }
        }
        
        visibleCount = endCount;
        
        // 모든 항목이 보이면 더보기 버튼 숨기기
        if (visibleCount >= allItems.length) {
            const moreButtonContainer = document.getElementById('moreButtonContainer');
            if (moreButtonContainer) {
                moreButtonContainer.style.display = 'none';
            }
        } else {
            updateButtonText();
        }
    });
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

