<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';

// 헤더 포함
include 'includes/header.php';
?>

<main class="main-content">
    <div class="content-layout">
        <!-- 페이지 제목 -->
        <div style="margin-bottom: 24px; padding-top: 24px; display: flex; align-items: center; gap: 12px;">
            <a href="/MVNO/mypage.php" style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; transition: background-color 0.2s; text-decoration: none; color: inherit;" onmouseover="this.style.backgroundColor='#f3f4f6'" onmouseout="this.style.backgroundColor='transparent'">
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
                <a href="/MVNO/plans/32627" class="plan-card-link">
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
                <a href="/MVNO/plans/32632" class="plan-card-link">
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
                <a href="/MVNO/plans/29290" class="plan-card-link">
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
        </div>
        
        <!-- 하단 BACK 버튼 -->
        <div style="margin-top: 48px; margin-bottom: 32px; display: flex; justify-content: center;">
            <a href="/MVNO/mypage.php" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; background-color: var(--color-gray-100); color: var(--color-gray-700); border-radius: 8px; font-size: 16px; font-weight: 600; text-decoration: none; transition: all 0.2s; border: 1px solid var(--color-gray-200);" onmouseover="this.style.backgroundColor='#e5e7eb'; this.style.borderColor='#d1d5db'" onmouseout="this.style.backgroundColor='#f3f4f6'; this.style.borderColor='#e5e7eb'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="transform: rotate(180deg);">
                    <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>BACK</span>
            </a>
        </div>
    </div>
</main>

<script src="assets/js/plan-accordion.js" defer></script>

<?php
// 푸터 포함
include 'includes/footer.php';
?>

