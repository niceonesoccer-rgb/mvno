<?php
// 현재 페이지 설정
$current_page = 'plans';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = false;

// 요금제 ID 가져오기
$plan_id = isset($_GET['id']) ? intval($_GET['id']) : 32627;

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content plan-detail-page">
    <!-- 기본 정보 섹션 -->
    <div class="content-layout plan-detail-header-section">
        <article class="basic-plan-card">
            <div class="plan-card-link">
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
                                <button class="plan-share-btn-inline" aria-label="공유하기" id="planShareBtn">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M11.2028 3.30311C11.6574 2.89896 12.3426 2.89896 12.7972 3.30311L17.2972 7.30311C17.7926 7.74341 17.8372 8.5019 17.3969 8.99724C16.9566 9.49258 16.1981 9.53719 15.7028 9.09689L13.2 6.8722V13.8C13.2 14.4627 12.6627 15 12 15C11.3372 15 10.8 14.4627 10.8 13.8V6.87222L8.29724 9.09689C7.8019 9.53719 7.04341 9.49258 6.60311 8.99724C6.16281 8.5019 6.20742 7.74341 6.70276 7.30311L11.2028 3.30311Z" fill="#868E96"/>
                                        <path d="M4.2 13C4.86274 13 5.4 13.5373 5.4 14.2V18.1083C5.4 18.184 5.43249 18.2896 5.5575 18.3981C5.68495 18.5087 5.89077 18.6 6.15 18.6H17.85C18.1093 18.6 18.3151 18.5087 18.4425 18.3981C18.5675 18.2897 18.6 18.184 18.6 18.1083V14.2C18.6 13.5373 19.1373 13 19.8 13C20.4627 13 21 13.5373 21 14.2V18.1083C21 19.8598 19.4239 21 17.85 21H6.15C4.5761 21 3 19.8598 3 18.1083V14.2C3 13.5373 3.53726 13 4.2 13Z" fill="#868E96"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- 제목과 찜 버튼 -->
                        <div class="plan-title-row">
                            <span class="plan-title-text">11월한정 LTE 100GB+밀리+Data쿠폰60GB</span>
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
                                <span class="plan-selection-count">29,448명이 선택</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
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
    </div>

    <!-- 신청하기 섹션 (하단 고정) -->
    <section class="plan-detail-apply-section">
        <div class="content-layout">
            <div class="plan-apply-content">
                <div class="plan-price-info">
                    <div class="plan-price-main">
                        <button class="plan-price-info-btn" aria-label="가격 정보">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#DEE2E6"/>
                            </svg>
                        </button>
                        <span class="plan-price-amount">월 17,000원</span>
                    </div>
                    <span class="plan-price-note">7개월 이후 42,900원</span>
                </div>
                <button class="plan-apply-btn">신청하기</button>
            </div>
        </div>
    </section>

    <!-- 요금제 상세 정보 섹션 -->
    <section class="plan-detail-info-section">
        <div class="content-layout">
            <h2 class="section-title">요금제 상세 정보</h2>
            
            <!-- 통화/문자/통신망/통신기술 요약 -->
            <div class="plan-summary-grid">
                <div class="plan-summary-item">
                    <div class="plan-summary-label">통화</div>
                    <div class="plan-summary-value">무제한</div>
                </div>
                <div class="plan-summary-item">
                    <div class="plan-summary-label">문자</div>
                    <div class="plan-summary-value">무제한</div>
                </div>
                <div class="plan-summary-item">
                    <div class="plan-summary-label">통신망</div>
                    <div class="plan-summary-value">KT망</div>
                </div>
                <div class="plan-summary-item">
                    <div class="plan-summary-label">통신 기술</div>
                    <div class="plan-summary-value">LTE</div>
                </div>
            </div>

            <!-- 상세 정보 그리드 -->
            <div class="plan-detail-grid">
                <div class="plan-detail-item">
                    <div class="plan-detail-label">요금제 이름</div>
                    <div class="plan-detail-value">
                        <a href="/mvnos/쉐이크모바일?from=요금제상세">쉐이크모바일</a> | [모요핫딜] 11월한정 LTE 100GB+밀리+Data쿠폰60GB
                    </div>
                </div>
                <div class="plan-detail-item">
                    <div class="plan-detail-label">통신사 약정</div>
                    <div class="plan-detail-value">없음</div>
                </div>
                <div class="plan-detail-item">
                    <div class="plan-detail-label">데이터 제공량</div>
                    <div class="plan-detail-value">월 100GB</div>
                </div>
                <div class="plan-detail-item">
                    <div class="plan-detail-label">데이터 소진시</div>
                    <div class="plan-detail-value">
                        5mbps 속도로 무제한
                        <button class="info-tooltip-btn" aria-label="정보">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#ADB5BD"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="plan-detail-item">
                    <div class="plan-detail-label">부가통화</div>
                    <div class="plan-detail-value">300분</div>
                </div>
                <div class="plan-detail-item">
                    <div class="plan-detail-label">번호이동 수수료</div>
                    <div class="plan-detail-value">없음</div>
                </div>
                <div class="plan-detail-item">
                    <div class="plan-detail-label">일반 유심</div>
                    <div class="plan-detail-value">배송가능 (6,600원)</div>
                </div>
                <div class="plan-detail-item">
                    <div class="plan-detail-label">NFC 유심</div>
                    <div class="plan-detail-value">배송불가</div>
                </div>
                <div class="plan-detail-item">
                    <div class="plan-detail-label">eSIM</div>
                    <div class="plan-detail-value">개통가능 (2,750원)</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 지원/미지원 기능 섹션 -->
    <section class="plan-supported-section">
        <div class="content-layout">
            <div class="plan-supported-grid">
                <div class="plan-supported-box">
                    <h3 class="plan-supported-title">지원</h3>
                    <div class="plan-supported-list">
                        <div class="plan-supported-item">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M1 12C1 5.92487 5.92487 1 12 1C18.0751 1 23 5.92487 23 12C23 18.0751 18.0751 23 12 23C5.92487 23 1 18.0751 1 12ZM8.80178 3.58482C7.04248 4.25383 5.54727 5.45868 4.51555 7H7.62598C7.91954 5.7997 8.31335 4.65175 8.80178 3.58482ZM11.3524 3.02294C10.6508 4.19229 10.0898 5.54003 9.69156 7H14.3085C13.9103 5.54003 13.3492 4.19229 12.6476 3.02294C12.4337 3.00773 12.2178 3 12 3C11.7822 3 11.5663 3.00773 11.3524 3.02294ZM7.2369 9H3.51212C3.18046 9.93834 3 10.9481 3 12C3 13.0519 3.18046 14.0617 3.51212 15H7.2369C7.09376 14.0225 7.01392 13.0225 7.00009 12.0137C7.00004 12.0099 7.00001 12.0061 7 12.0024C6.99999 11.997 7.00002 11.9916 7.00009 11.9863C7.01392 10.9775 7.09376 9.97751 7.2369 9ZM9.26031 15C9.10336 14.0273 9.01477 13.0218 9.0001 12C9.01477 10.9782 9.10336 9.97268 9.26031 9H14.7397C14.8967 9.97268 14.9853 10.9782 14.9999 12C14.9853 13.0218 14.8967 14.0273 14.7397 15H9.26031ZM7.62598 17H4.51555C5.54728 18.5413 7.04248 19.7462 8.80178 20.4152C8.31336 19.3483 7.91954 18.2003 7.62598 17ZM11.3524 20.9771C10.6508 19.8077 10.0898 18.46 9.69156 17H14.3085C13.9103 18.46 13.3492 19.8077 12.6476 20.9771C12.4337 20.9923 12.2178 21 12 21C11.7822 21 11.5663 20.9923 11.3524 20.9771ZM16.9999 12.0137C16.9861 13.0225 16.9063 14.0225 16.7631 15H20.4879C20.8195 14.0617 21 13.0519 21 12C21 10.9481 20.8195 9.93834 20.4879 9H16.7631C16.9063 9.97751 16.9861 10.9775 16.9999 11.9863C17.0001 11.9954 17.0001 12.0046 16.9999 12.0137ZM16.374 17C16.0805 18.2003 15.6867 19.3482 15.1982 20.4152C16.9575 19.7462 18.4527 18.5413 19.4845 17H16.374ZM15.1983 3.58484C15.6867 4.65175 16.0805 5.79971 16.374 7H19.4845C18.4527 5.45869 16.9575 4.25384 15.1983 3.58484Z" fill="#3F4750"/>
                            </svg>
                            <span>인터넷 결합</span>
                        </div>
                        <div class="plan-supported-item">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.34285 6.34701C6.73332 5.95643 6.73322 5.32327 6.34264 4.9328C5.95206 4.54234 5.31889 4.54243 4.92843 4.93301C3.05336 6.80865 2 9.35222 2 12.0044C2 14.6565 3.05336 17.2001 4.92843 19.0757C5.31889 19.4663 5.95206 19.4664 6.34264 19.0759C6.73322 18.6855 6.73332 18.0523 6.34285 17.6617C4.84273 16.1612 4 14.1262 4 12.0044C4 9.88255 4.84273 7.8476 6.34285 6.34701Z" fill="#3F4750"/>
                                <path d="M19.0716 4.93301C18.6811 4.54243 18.0479 4.54234 17.6574 4.9328C17.2668 5.32327 17.2667 5.95643 17.6571 6.34701C19.1573 7.8476 20 9.88255 20 12.0044C20 14.1262 19.1573 16.1612 17.6571 17.6617C17.2667 18.0523 17.2668 18.6855 17.6574 19.0759C18.0479 19.4664 18.6811 19.4663 19.0716 19.0757C20.9466 17.2001 22 14.6565 22 12.0044C22 9.35222 20.9466 6.80865 19.0716 4.93301Z" fill="#3F4750"/>
                                <path d="M8.8899 8.88606C9.28064 8.49575 9.28099 7.86259 8.89069 7.47184C8.50038 7.0811 7.86721 7.08075 7.47647 7.47106C6.88132 8.06555 6.40917 8.77153 6.08704 9.54861C5.76491 10.3257 5.5991 11.1587 5.5991 11.9999C5.5991 12.8411 5.76491 13.674 6.08704 14.4511C6.40917 15.2282 6.88132 15.9342 7.47647 16.5287C7.86721 16.919 8.50038 16.9186 8.89069 16.5279C9.28099 16.1372 9.28064 15.504 8.8899 15.1137C8.4807 14.7049 8.15607 14.2195 7.93459 13.6853C7.7131 13.151 7.5991 12.5783 7.5991 11.9999C7.5991 11.4215 7.7131 10.8488 7.93459 10.3145C8.15607 9.7802 8.4807 9.29481 8.8899 8.88606Z" fill="#3F4750"/>
                                <path d="M16.5235 7.48006C16.1328 7.08975 15.4996 7.0901 15.1093 7.48085C14.719 7.87159 14.7194 8.50475 15.1101 8.89506C15.5193 9.30381 15.8439 9.7892 16.0654 10.3235C16.2869 10.8578 16.4009 11.4305 16.4009 12.0089C16.4009 12.5873 16.2869 13.16 16.0654 13.6943C15.8439 14.2285 15.5193 14.7139 15.1101 15.1227C14.7194 15.513 14.719 16.1462 15.1093 16.5369C15.4996 16.9276 16.1328 16.928 16.5235 16.5377C17.1187 15.9432 17.5908 15.2372 17.913 14.4601C18.2351 13.683 18.4009 12.8501 18.4009 12.0089C18.4009 11.1677 18.2351 10.3347 17.913 9.55761C17.5908 8.78053 17.1187 8.07455 16.5235 7.48006Z" fill="#3F4750"/>
                                <path d="M14 12C14 13.1046 13.1046 14 12 14C10.8954 14 10 13.1046 10 12C10 10.8954 10.8954 10 12 10C13.1046 10 14 10.8954 14 12Z" fill="#3F4750"/>
                            </svg>
                            <span>모바일 핫스팟</span>
                            <span class="plan-supported-note">데이터 제공량 내 이용 가능</span>
                        </div>
                        <div class="plan-supported-item">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.7089 8.79154C8.59377 8.308 8.10845 8.00934 7.62491 8.12447C7.14137 8.2396 6.84272 8.72492 6.95785 9.20846L7.17012 10.1H7.00001C6.50295 10.1 6.10001 10.5029 6.10001 11C6.10001 11.4971 6.50295 11.9 7.00001 11.9H7.59869L8.528 15.8031C8.68111 16.4462 9.25568 16.9 9.91671 16.9C10.5777 16.9 11.1523 16.4462 11.3054 15.8031L12 12.8857L12.6947 15.8031C12.8478 16.4462 13.4223 16.9 14.0834 16.9C14.7444 16.9 15.319 16.4462 15.4721 15.8031L16.4014 11.9H17C17.4971 11.9 17.9 11.4971 17.9 11C17.9 10.5029 17.4971 10.1 17 10.1H16.83L17.0422 9.20846C17.1574 8.72492 16.8587 8.2396 16.3752 8.12447C15.8916 8.00934 15.4063 8.308 15.2912 8.79154L14.9796 10.1H13.1871L12.8756 8.79154C12.779 8.38612 12.4168 8.1 12 8.1C11.5833 8.1 11.221 8.38612 11.1245 8.79154L10.813 10.1H9.02043L8.7089 8.79154ZM13.6157 11.9L14.0834 13.8643L14.5511 11.9H13.6157ZM9.91671 13.8643L9.44901 11.9H10.3844L9.91671 13.8643Z" fill="#3F4750"/>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12ZM19.8 12C19.8 16.3078 16.3078 19.8 12 19.8C7.69218 19.8 4.2 16.3078 4.2 12C4.2 7.69218 7.69218 4.2 12 4.2C16.3078 4.2 19.8 7.69218 19.8 12Z" fill="#3F4750"/>
                            </svg>
                            <span>소액 결제</span>
                        </div>
                        <div class="plan-supported-item">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M7.21522 3.72321C6.25091 2.75891 4.68747 2.75895 3.72321 3.72321C2.75895 4.68747 2.75891 6.25091 3.72321 7.21522L6.79428 10.2863L4.91428 18.4328C4.79135 18.966 4.95161 19.5252 5.33869 19.9123L5.96134 20.5349C6.6917 21.2653 7.91404 21.1148 8.44547 20.2291L11.5548 15.0468L13.0987 16.5907L12.9241 19.2095C12.8171 20.8168 14.8962 21.5442 15.8137 20.2187L17.6158 17.6158L20.2188 15.8137C21.5443 14.8961 20.8165 12.8171 19.2094 12.9241L16.5907 13.0987L15.0468 11.5548L20.2291 8.44543C21.1149 7.91396 21.2652 6.69167 20.5349 5.96134L19.9123 5.3387C19.5251 4.95153 18.9656 4.79149 18.4326 4.91433L10.2863 6.79429L7.21522 3.72321ZM5.13743 5.13743C5.32066 4.95419 5.61777 4.95419 5.801 5.13743L9.03366 8.37009C9.42081 8.75723 9.98008 8.91748 10.5133 8.79445L18.6596 6.9145L18.7473 7.00215L13.565 10.1116C12.6792 10.643 12.5289 11.8653 13.2592 12.5957L15.3088 14.6452C15.6325 14.9689 16.0803 15.1372 16.5372 15.1067L17.853 15.019L16.4158 16.014C16.2588 16.1226 16.1226 16.2588 16.014 16.4158L15.019 17.853L15.1067 16.5372C15.1372 16.0803 14.9689 15.6325 14.6452 15.3088L12.5957 13.2592C11.8653 12.5289 10.643 12.6793 10.1115 13.5651L7.00216 18.7473L6.9145 18.6596L8.79447 10.5133C8.91749 9.98001 8.75722 9.4208 8.37009 9.03367L5.13743 5.801C4.95419 5.61777 4.95419 5.32066 5.13743 5.13743Z" fill="#3F4750"/>
                            </svg>
                            <span>해외 로밍 부가서비스</span>
                            <span class="plan-supported-note">신청은 통신사에 문의</span>
                        </div>
                        <div class="plan-supported-item">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M14 6.49999C14 4.56701 15.567 2.99999 17.5 2.99999C19.433 2.99999 21 4.56701 21 6.49999C21 8.43297 19.433 9.99999 17.5 9.99999C16.5593 9.99999 15.7053 9.62888 15.0764 9.02508L9.97037 11.5425C9.98992 11.6922 10 11.8449 10 12C10 12.0961 9.99613 12.1913 9.98852 12.2855L15.205 14.8574C15.8194 14.3233 16.6219 14 17.5 14C19.433 14 21 15.567 21 17.5C21 19.433 19.433 21 17.5 21C15.567 21 14 19.433 14 17.5C14 17.2884 14.0188 17.0811 14.0548 16.8798L9.04041 14.4076C8.40257 15.0804 7.50028 15.5 6.5 15.5C4.56702 15.5 3 13.933 3 12C3 10.0669 4.56702 8.49999 6.5 8.49999C7.43869 8.49999 8.29107 8.86952 8.91961 9.47104L14.0289 6.95198C14.0098 6.80401 14 6.65315 14 6.49999ZM17.5 5.27026C16.8208 5.27026 16.2703 5.82082 16.2703 6.49999C16.2703 7.17916 16.8208 7.72972 17.5 7.72972C18.1792 7.72972 18.7297 7.17916 18.7297 6.49999C18.7297 5.82082 18.1792 5.27026 17.5 5.27026ZM17.5 16.2703C16.8208 16.2703 16.2703 16.8208 16.2703 17.5C16.2703 18.1792 16.8208 18.7297 17.5 18.7297C18.1792 18.7297 18.7297 18.1792 18.7297 17.5C18.7297 16.8208 18.1792 16.2703 17.5 16.2703ZM5.27027 12C5.27027 11.3208 5.82083 10.7703 6.5 10.7703C7.17917 10.7703 7.72973 11.3208 7.72973 12C7.72973 12.6792 7.17917 13.2297 6.5 13.2297C5.82083 13.2297 5.27027 12.6792 5.27027 12Z" fill="#3F4750"/>
                            </svg>
                            <span>데이터 쉐어링</span>
                        </div>
                    </div>
                </div>
                <div class="plan-supported-box">
                    <h3 class="plan-supported-title">미지원</h3>
                    <div class="plan-supported-list">
                        <span class="plan-supported-empty">없음</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 초과 요금 섹션 -->
    <section class="plan-exceed-rate-section">
        <div class="content-layout">
            <h2 class="section-title">기본 제공 초과 시</h2>
            <div class="plan-exceed-table">
                <div class="plan-exceed-row">
                    <div class="plan-exceed-header">데이터</div>
                    <div class="plan-exceed-value">
                        <span>22.53원/MB</span>
                        <button class="info-tooltip-btn" aria-label="정보">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM11.9631 6.3C11.0676 6.3 10.232 6.51466 9.57985 6.96603C8.92366 7.42021 8.46483 8.10662 8.31938 9.02129C8.3164 9.03998 8.31437 9.05917 8.31312 9.07865C8.30448 9.13807 8.3 9.19884 8.3 9.26065C8.3 9.95296 8.86123 10.5142 9.55354 10.5142C10.1005 10.5142 10.5657 10.1638 10.7369 9.67526L10.7387 9.67011C10.7638 9.59745 10.7824 9.52176 10.7938 9.44373L10.8058 9.38928L10.8081 9.37877L10.8083 9.37769C10.8271 9.29121 10.841 9.22917 10.8574 9.18386C11.0275 8.71526 11.4653 8.45953 11.9483 8.45361C12.5783 8.46102 13.0472 8.87279 13.0411 9.48507L13.0411 9.48924C13.0473 10.0572 12.6402 10.4644 12.0041 10.8789C11.5992 11.1321 11.2517 11.4001 10.9961 11.7995C10.74 12.1996 10.5884 12.7121 10.5357 13.435C10.5347 13.4494 10.5345 13.4636 10.5352 13.4775C10.5343 13.496 10.5339 13.5146 10.5339 13.5333C10.5339 14.1879 11.0645 14.7186 11.7191 14.7186C12.2745 14.7186 12.7406 14.3366 12.8692 13.8211H12.8775L12.8898 13.7197C12.8941 13.6924 12.8975 13.6647 12.8999 13.6367C12.9441 13.2837 13.0501 13.0231 13.2199 12.8024C13.394 12.5762 13.6445 12.3796 13.997 12.1706L13.9983 12.1699C14.5009 11.8667 14.928 11.5082 15.229 11.0562C15.5318 10.6015 15.7 10.0628 15.7 9.41276C15.7 8.43645 15.308 7.64987 14.6337 7.1118C13.9643 6.57764 13.0321 6.3 11.9631 6.3ZM11.7579 18.0998C11.0263 18.0998 10.4347 17.516 10.4503 16.7921C10.4347 16.0761 11.0263 15.4923 11.7579 15.5001C12.4507 15.4923 13.05 16.0761 13.05 16.7921C13.05 17.516 12.4507 18.0998 11.7579 18.0998Z" fill="#ADB5BD"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="plan-exceed-row">
                    <div class="plan-exceed-header">음성 통화</div>
                    <div class="plan-exceed-value">1.98원/초</div>
                </div>
                <div class="plan-exceed-row">
                    <div class="plan-exceed-header">영상 통화</div>
                    <div class="plan-exceed-value">3.3원/초</div>
                </div>
                <div class="plan-exceed-row">
                    <div class="plan-exceed-header">짧은 문자</div>
                    <div class="plan-exceed-value">22원/개</div>
                </div>
                <div class="plan-exceed-row">
                    <div class="plan-exceed-header">긴 문자</div>
                    <div class="plan-exceed-value">44원/개</div>
                </div>
                <div class="plan-exceed-row">
                    <div class="plan-exceed-header">엄청 긴 문자</div>
                    <div class="plan-exceed-value">44원/개</div>
                </div>
                <div class="plan-exceed-row">
                    <div class="plan-exceed-header">사진 포함 문자</div>
                    <div class="plan-exceed-value">220원/개</div>
                </div>
                <div class="plan-exceed-row">
                    <div class="plan-exceed-header">영상 포함 문자</div>
                    <div class="plan-exceed-value">440원/개</div>
                </div>
            </div>
            <p class="plan-exceed-note">통화 또는 문자 제공량이 무제한이더라도 과도한 사용이 있을 경우 사용량 제한이 있을 수 있어요.</p>
        </div>
    </section>

    <!-- 통신사 리뷰 섹션 -->
    <section class="plan-review-section">
        <div class="content-layout">
            <div class="plan-review-header">
                <a href="/mvnos/쉐이크모바일?from=요금제상세" class="plan-review-mvno-link">
                    <img src="https://assets.moyoplan.com/logo/%E1%84%89%E1%85%B0%E1%84%8B%E1%85%B5%E1%84%8F%E1%85%B3%E1%84%86%E1%85%A9%E1%84%87%E1%85%A1%E1%84%8B%E1%85%B5%E1%86%AF.svg" alt="쉐이크모바일" class="plan-review-logo">
                </a>
                <h2 class="section-title">통신사 리뷰</h2>
            </div>
            
            <div class="plan-review-summary">
                <div class="plan-review-rating">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FAB005"/>
                    </svg>
                    <span class="plan-review-rating-score">4.3</span>
                    <span class="plan-review-count">11,533개</span>
                </div>
                <div class="plan-review-categories">
                    <div class="plan-review-category">
                        <span class="plan-review-category-label">고객센터</span>
                        <span class="plan-review-category-score">4.2</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                    </div>
                    <div class="plan-review-divider"></div>
                    <div class="plan-review-category">
                        <span class="plan-review-category-label">개통 과정</span>
                        <span class="plan-review-category-score">4.5</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                    </div>
                    <div class="plan-review-divider"></div>
                    <div class="plan-review-category">
                        <span class="plan-review-category-label">개통 후 만족도</span>
                        <span class="plan-review-category-score">4.1</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="plan-review-list">
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">전*한</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">24일 전</span>
                    </div>
                    <p class="plan-review-content">개통이 다른 회사 보다 빠르고 좋습니다. 요금제 너무 좋아서 계속 사용할 예정 입니다. 친구, 가족 들에게 소개해주고 같이 사용 하는 중입니다. 강력 추천 합니다.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">오*열</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">29일 전</span>
                    </div>
                    <p class="plan-review-content">번호 이동이나 이동 후 개통도 휴일임에도 신청서 작성하고 쓰고 있던 esim으로 안내 문자에 따라 바로 즉시 개통할 수 있어 편리했습니다.(KT알띁A → KT알띁B)</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">최*연</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">41일 전</span>
                    </div>
                    <p class="plan-review-content">고객센터 개통 전화없이 모요 통해서 개통신청하고 편의점 바로유심 사서 끼우면 바로 개통됨..타 알뜰폰 통신사보다 개통과정, 통신속도,데이터량 불편함없이 사용함..쉐이크모바일 강추</p>
                </div>
            </div>
            <button class="plan-review-more-btn">더보기</button>
        </div>
    </section>
</main>

<!-- 공유 모달 -->
<div class="share-modal" id="shareModal">
    <div class="share-modal-overlay" id="shareModalOverlay"></div>
    <div class="share-modal-content">
        <div class="share-modal-header">
            <h3 class="share-modal-title">공유하기</h3>
            <button class="share-modal-close" aria-label="닫기" id="shareModalClose">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="share-modal-grid">
            <a href="#" class="share-modal-item" data-platform="kakao" target="_blank" rel="noopener noreferrer">
                <div class="share-modal-icon share-icon-kakao">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 4C9.37258 4 4 8.37258 4 14C4 17.5 6.1 20.6 9.3 22.3L8.5 27.5C8.4 27.8 8.6 28.1 8.9 28L13.2 25.7C13.8 25.8 14.4 25.9 15 25.9C21.6274 25.9 26.9 21.5274 26.9 15.9C26.9 9.27258 22.5274 4 16 4Z" fill="#3C1E1E"/>
                    </svg>
                </div>
                <span class="share-modal-label">카카오톡</span>
            </a>
            <a href="#" class="share-modal-item" data-platform="facebook" target="_blank" rel="noopener noreferrer">
                <div class="share-modal-icon share-icon-facebook">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 4C9.37258 4 4 9.37258 4 16C4 22.6274 9.37258 28 16 28C22.6274 28 28 22.6274 28 16C28 9.37258 22.6274 4 16 4ZM18.5 12.5H17C16.4 12.5 16 12.9 16 13.5V15.5H18.5C18.8 15.5 19 15.7 19 16V18C19 18.3 18.8 18.5 18.5 18.5H16V24H13.5V18.5H11.5C11.2 18.5 11 18.3 11 18V16C11 15.7 11.2 15.5 11.5 15.5H13.5V13.5C13.5 11.3 15.3 9.5 17.5 9.5H18.5C18.8 9.5 19 9.7 19 10V11.5C19 11.8 18.8 12 18.5 12H17.5C16.9 12 16.5 12.4 16.5 13V15.5H18.5C18.8 15.5 19 15.7 19 16V18C19 18.3 18.8 18.5 18.5 18.5H16.5V24H13.5V18.5H11.5C11.2 18.5 11 18.3 11 18V16C11 15.7 11.2 15.5 11.5 15.5H13.5V13.5C13.5 11.3 15.3 9.5 17.5 9.5H18.5C18.8 9.5 19 9.7 19 10V11.5C19 11.8 18.8 12 18.5 12Z" fill="#FFFFFF"/>
                    </svg>
                </div>
                <span class="share-modal-label">페이스북</span>
            </a>
            <a href="#" class="share-modal-item" data-platform="twitter" target="_blank" rel="noopener noreferrer">
                <div class="share-modal-icon share-icon-twitter">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z" fill="#FFFFFF"/>
                    </svg>
                </div>
                <span class="share-modal-label">트위터</span>
            </a>
            <button class="share-modal-item" data-platform="link" id="shareLinkBtn">
                <div class="share-modal-icon share-icon-link">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13 11C13 10.4477 13.4477 10 14 10H18C18.5523 10 19 10.4477 19 11C19 11.5523 18.5523 12 18 12H15V20H18C18.5523 20 19 20.4477 19 21C19 21.5523 18.5523 22 18 22H14C13.4477 22 13 21.5523 13 21V11Z" fill="#868E96"/>
                        <path d="M16 4C9.37258 4 4 9.37258 4 16C4 22.6274 9.37258 28 16 28C22.6274 28 28 22.6274 28 16C28 9.37258 22.6274 4 16 4Z" fill="none" stroke="#868E96" stroke-width="2"/>
                    </svg>
                </div>
                <span class="share-modal-label">링크 복사</span>
            </button>
        </div>
    </div>
</div>

<script>
// 아코디언 기능
document.addEventListener('DOMContentLoaded', function() {
    const accordionTriggers = document.querySelectorAll('.plan-accordion-trigger');
    
    accordionTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            // 모든 아코디언 닫기
            accordionTriggers.forEach(t => {
                t.setAttribute('aria-expanded', 'false');
                t.nextElementSibling.style.display = 'none';
            });
            
            // 클릭한 아코디언만 토글
            if (!isExpanded) {
                this.setAttribute('aria-expanded', 'true');
                content.style.display = 'block';
            }
        });
    });

    // 공유 모달 기능
    const shareBtn = document.getElementById('planShareBtn');
    const shareModal = document.getElementById('shareModal');
    const shareModalOverlay = document.getElementById('shareModalOverlay');
    const shareModalClose = document.getElementById('shareModalClose');
    const shareLinkBtn = document.getElementById('shareLinkBtn');
    const shareItems = document.querySelectorAll('.share-modal-item');

    // 현재 페이지 URL과 제목 가져오기
    const currentUrl = window.location.href;
    const planTitle = document.querySelector('.plan-title-text')?.textContent || '요금제 상세';
    const shareText = `${planTitle} - 모요`;

    // 공유 버튼 클릭 시 모달 열기
    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            shareModal.classList.add('share-modal-active');
            document.body.style.overflow = 'hidden';
        });
    }

    // 모달 닫기
    function closeModal() {
        shareModal.classList.remove('share-modal-active');
        document.body.style.overflow = '';
    }

    if (shareModalOverlay) {
        shareModalOverlay.addEventListener('click', closeModal);
    }

    if (shareModalClose) {
        shareModalClose.addEventListener('click', closeModal);
    }

    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && shareModal.classList.contains('share-modal-active')) {
            closeModal();
        }
    });

    // 소셜 공유 링크 설정
    shareItems.forEach(item => {
        const platform = item.getAttribute('data-platform');
        
        if (platform === 'kakao') {
            // 카카오톡 공유 (카카오 SDK 필요 시 사용, 여기서는 링크 공유)
            item.href = `https://story.kakao.com/share?url=${encodeURIComponent(currentUrl)}`;
        } else if (platform === 'facebook') {
            // 페이스북 공유
            item.href = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}`;
        } else if (platform === 'twitter') {
            // 트위터 공유
            item.href = `https://twitter.com/intent/tweet?url=${encodeURIComponent(currentUrl)}&text=${encodeURIComponent(shareText)}`;
        } else if (platform === 'link') {
            // 링크 복사
            item.addEventListener('click', function(e) {
                e.preventDefault();
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(currentUrl).then(function() {
                        alert('링크가 복사되었습니다.');
                        closeModal();
                    }).catch(function() {
                        // 클립보드 API 실패 시 fallback
                        fallbackCopyTextToClipboard(currentUrl);
                    });
                } else {
                    // 클립보드 API 미지원 시 fallback
                    fallbackCopyTextToClipboard(currentUrl);
                }
            });
        }
    });

    // 클립보드 복사 fallback 함수
    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                alert('링크가 복사되었습니다.');
                closeModal();
            } else {
                alert('링크 복사에 실패했습니다. 수동으로 복사해주세요.');
            }
        } catch (err) {
            alert('링크 복사에 실패했습니다. 수동으로 복사해주세요.');
        }
        
        document.body.removeChild(textArea);
    }
});
</script>

<?php include '../includes/footer.php'; ?>

