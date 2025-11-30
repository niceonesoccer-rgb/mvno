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

    <!-- 요금제 상세 정보 섹션 (통합) -->
    <section class="plan-detail-info-section">
        <div class="content-layout">
            <h2 class="section-title">요금제 상세 정보</h2>
            
            <!-- 기본 정보 카드 -->
            <div class="plan-info-card">
                <h3 class="plan-info-card-title">기본 정보</h3>
                <div class="plan-info-card-content">
                    <div class="plan-detail-grid">
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">요금제 이름</div>
                            <div class="plan-detail-value">
                                [모요핫딜] 11월한정 LTE 100GB+밀리+Data쿠폰60GB
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통신사 약정</div>
                            <div class="plan-detail-value">없음</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통신망</div>
                            <div class="plan-detail-value">KT망</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통신 기술</div>
                            <div class="plan-detail-value">LTE</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 데이터 정보 카드 -->
            <div class="plan-info-card">
                <h3 class="plan-info-card-title">데이터 정보</h3>
                <div class="plan-info-card-content">
                    <div class="plan-detail-grid">
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통화</div>
                            <div class="plan-detail-value">무제한</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">문자</div>
                            <div class="plan-detail-value">무제한</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">데이터 제공량</div>
                            <div class="plan-detail-value">월 100GB</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">데이터 소진시</div>
                            <div class="plan-detail-value">
                                5mbps 속도로 무제한
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
                            <div class="plan-detail-label">모바일 핫스팟</div>
                            <div class="plan-detail-value">데이터 제공량 내</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">데이터 쉐어링</div>
                            <div class="plan-detail-value">데이터 제공량 내</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 유심 정보 카드 -->
            <div class="plan-info-card">
                <h3 class="plan-info-card-title">유심 정보</h3>
                <div class="plan-info-card-content">
                    <div class="plan-detail-grid">
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
            </div>

        </div>
    </section>

    <!-- 초과 요금 섹션 -->
    <section class="plan-exceed-rate-section">
        <div class="content-layout">
            <h2 class="section-title">기본 제공 초과 시</h2>
            <div class="plan-info-card plan-info-card-no-bg">
                <div class="plan-info-card-content">
                    <div class="plan-detail-grid">
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">데이터</div>
                            <div class="plan-detail-value">
                                22.53원/MB
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">음성 통화</div>
                            <div class="plan-detail-value">1.98원/초</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">영상 통화</div>
                            <div class="plan-detail-value">3.3원/초</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">짧은 문자</div>
                            <div class="plan-detail-value">22원/개</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">긴 문자</div>
                            <div class="plan-detail-value">44원/개</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">엄청 긴 문자</div>
                            <div class="plan-detail-value">44원/개</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">사진 포함 문자</div>
                            <div class="plan-detail-value">220원/개</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">영상 포함 문자</div>
                            <div class="plan-detail-value">440원/개</div>
                        </div>
                    </div>
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

