<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div style="width: 460px; margin: 0 auto; padding: 20px;" class="mypage-container">
        <!-- 사용자 인사말 헤더 -->
        <div style="margin-bottom: 24px; padding: 20px 0;">
            <h2 style="font-size: 24px; font-weight: bold; margin: 0;">YMB님 안녕하세요</h2>
        </div>

        <!-- 내가 찜한 요금제 섹션 -->
        <div style="margin-bottom: 32px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                        <path d="M21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C17.3144 15.2837 15.1837 17.5556 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803Z" fill="#FA5252"/>
                    </svg>
                    <p style="font-size: 18px; font-weight: bold; margin: 0;">찜한 요금제</p>
                </div>
                <a href="/MVNO/mypage/wishlist.php" style="font-size: 14px; color: #6366f1; text-decoration: none;">3개 더보기</a>
            </div>
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
                                            <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FCC419"></path>
                                        </svg>
                                        <span class="plan-rating-text">4.3</span>
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
                                <span class="plan-title-text">[모요핫딜] 11월한정 LTE 100GB+밀리+Data쿠폰60GB</span>
                            </div>

                            <!-- 데이터 정보와 기능 -->
                            <div class="plan-info-section">
                                <div class="plan-data-row">
                                    <span class="plan-data-main">월 100GB + 5Mbps</span>
                                    <button class="plan-info-icon-btn" aria-label="정보">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"></path>
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
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"></path>
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
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.15146 8.15147C3.62009 7.68284 4.37989 7.68284 4.84852 8.15147L12 15.3029L19.1515 8.15147C19.6201 7.68284 20.3799 7.68284 20.8485 8.15147C21.3171 8.6201 21.3171 9.3799 20.8485 9.84853L12.8485 17.8485C12.3799 18.3172 11.6201 18.3172 11.1515 17.8485L3.15146 9.84853C2.68283 9.3799 2.68283 8.6201 3.15146 8.15147Z" fill="#868E96"></path>
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
        </div>

        <!-- 하단 메뉴 리스트 -->
        <div style="margin-bottom: 32px;">
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="/mypage/orders" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M9 12H15M9 16H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span style="font-size: 16px;">신청한 요금제</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 14px; color: #6b7280;">0개</span>
                            <img alt=">" src="https://assets-legacy.moyoplan.com/img/icons/rightArrow.svg" style="width: 16px; height: 16px;">
                        </div>
                    </a>
                </li>
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="/mypage/phone-applies" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M5 4C5 2.89543 5.89543 2 7 2H17C18.1046 2 19 2.89543 19 4V20C19 21.1046 18.1046 22 17 22H7C5.89543 22 5 21.1046 5 20V4Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M12 18H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span style="font-size: 16px;">휴대폰 신청 내역</span>
                        </div>
                        <img alt=">" src="https://assets-legacy.moyoplan.com/img/icons/rightArrow.svg" style="width: 16px; height: 16px;">
                    </a>
                </li>
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="/mypage/esim-applies" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M6 4H18C19.1046 4 20 4.89543 20 6V18C20 19.1046 19.1046 20 18 20H6C4.89543 20 4 19.1046 4 18V6C4 4.89543 4.89543 4 6 4Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M4 6V10H8V6C8 4.89543 7.10457 4 6 4C4.89543 4 4 4.89543 4 6Z" fill="currentColor"/>
                                <rect x="10" y="8" width="1.5" height="1.5" rx="0.25" fill="currentColor"/>
                                <rect x="12.5" y="8" width="1.5" height="1.5" rx="0.25" fill="currentColor"/>
                                <rect x="15" y="8" width="1.5" height="1.5" rx="0.25" fill="currentColor"/>
                                <rect x="10" y="10.5" width="1.5" height="1.5" rx="0.25" fill="currentColor"/>
                                <rect x="12.5" y="10.5" width="1.5" height="1.5" rx="0.25" fill="currentColor"/>
                                <rect x="15" y="10.5" width="1.5" height="1.5" rx="0.25" fill="currentColor"/>
                                <rect x="10" y="13" width="1.5" height="1.5" rx="0.25" fill="currentColor"/>
                                <rect x="12.5" y="13" width="1.5" height="1.5" rx="0.25" fill="currentColor"/>
                                <rect x="15" y="13" width="1.5" height="1.5" rx="0.25" fill="currentColor"/>
                            </svg>
                            <span style="font-size: 16px;">유심신청내역</span>
                        </div>
                        <img alt=">" src="https://assets-legacy.moyoplan.com/img/icons/rightArrow.svg" style="width: 16px; height: 16px;">
                    </a>
                </li>
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="/mypage/payment" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M3 10C3 6.22876 3 4.34315 4.17157 3.17157C5.34315 2.34315 7.22876 2.34315 11 2.34315H13C16.7712 2.34315 18.6569 2.34315 19.8284 3.17157C21 4.34315 21 6.22876 21 10V14C21 17.7712 21 19.6569 19.8284 20.8284C18.6569 22 16.7712 22 13 22H11C7.22876 22 5.34315 22 4.17157 20.8284C3 19.6569 3 17.7712 3 14V10Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M7 12H17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M7 16H12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span style="font-size: 16px;">내 결제내역</span>
                        </div>
                        <img alt=">" src="https://assets-legacy.moyoplan.com/img/icons/rightArrow.svg" style="width: 16px; height: 16px;">
                    </a>
                </li>
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="/MVNO/mypage/alarm-setting.php" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6982 21.5547 10.4458 21.3031 10.27 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span style="font-size: 16px;">알림 설정</span>
                        </div>
                        <img alt=">" src="https://assets-legacy.moyoplan.com/img/icons/rightArrow.svg" style="width: 16px; height: 16px;">
                    </a>
                </li>
            </ul>

            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 16px 0;">
            <div style="margin: 16px 0 8px 0;">
                <p style="font-size: 12px; color: #9ca3af; margin: 0;">기타</p>
            </div>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="/notice" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span style="font-size: 16px;">공지 사항</span>
                        </div>
                        <img alt=">" src="https://assets-legacy.moyoplan.com/img/icons/rightArrow.svg" style="width: 16px; height: 16px;">
                    </a>
                </li>
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="/community/questions" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                                <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span style="font-size: 16px;">질문과 답변</span>
                        </div>
                        <img alt=">" src="https://assets-legacy.moyoplan.com/img/icons/rightArrow.svg" style="width: 16px; height: 16px;">
                    </a>
                </li>
            </ul>

            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 16px 0;">
            <div style="margin: 16px 0 8px 0;">
                <p style="font-size: 12px; color: #9ca3af; margin: 0;">계정</p>
            </div>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li>
                    <a href="/MVNO/mypage/account-management.php" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span style="font-size: 16px;">계정 설정</span>
                        </div>
                        <img alt=">" src="https://assets-legacy.moyoplan.com/img/icons/rightArrow.svg" style="width: 16px; height: 16px;">
                    </a>
                </li>
            </ul>
        </div>
    </div>
</main>

<script src="../assets/js/plan-accordion.js" defer></script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>
