<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'home';

// 헤더 포함
include 'includes/header.php';
?>

<main class="main-content">
    <!-- 첫 번째 섹션: 배너 캐러셀 + 데이터 요금제 + 랭킹 -->
    <div class="content-layout">
        <div class="main-grid">
            <!-- 왼쪽: 배너 캐러셀 + 랭킹 -->
            <div class="left-section">
                <!-- 메인 배너 -->
                <section class="banner-section">
                    <div class="banner-wrapper">
                        <a href="/event" class="banner-link">
                            <img src="images/upload/event/2025/10/20251031_192001398_teGIZDiB.png" alt="메인 배너" class="banner-image">
                        </a>
                    </div>
                </section>

                <!-- 모요 요금제 랭킹 -->
                <section class="ranking-section">
                    <div class="ranking-list">
                        <a href="/plans/themes/ranking/weekly-top-20" class="ranking-card">
                            <img src="images/upload/event/2025/10/20251030_140511806_lTyhMVjG.jpg" alt="배너" class="ranking-banner">
                        </a>
                        <a href="/plans/themes/ranking/under-10000-fee-twenty-plans" class="ranking-card">
                            <img src="images/upload/event/2025/10/20251031_122332004_kjuEtCoq.jpg" alt="배너" class="ranking-banner">
                        </a>
                    </div>
                </section>
            </div>

            <!-- 오른쪽: 데이터 요금제 리스트 -->
            <div class="right-section">
                <section class="data-plan-section">
                    <ul class="data-plan-list">
                        <li>
                            <a href="/calculator/plans-by-data/recommended-one?data=7&from=home" class="data-plan-card">
                                <div class="data-plan-header">
                                    <span class="data-amount">7GB</span>
                                    <div class="data-plan-info">
                                        <p class="data-price">6,000원</p>
                                        <span class="data-desc">웹 서핑과 카톡만 한다면</span>
                                    </div>
                                    <span class="arrow-icon">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.15146 20.8485C7.68283 20.3799 7.68283 19.6201 8.15146 19.1515L15.3029 12L8.15146 4.84852C7.68283 4.37989 7.68283 3.6201 8.15146 3.15147C8.62009 2.68284 9.37989 2.68284 9.84852 3.15147L17.8485 11.1515C18.3171 11.6201 18.3171 12.3799 17.8485 12.8485L9.84852 20.8485C9.37989 21.3172 8.62009 21.3172 8.15146 20.8485Z" fill="#868E96"></path>
                                        </svg>
                                    </span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/calculator/plans-by-data/recommended-one?data=15&from=home" class="data-plan-card">
                                <div class="data-plan-header">
                                    <span class="data-amount">15GB</span>
                                    <div class="data-plan-info">
                                        <p class="data-price">12,000원</p>
                                        <span class="data-desc">출퇴근길에 음악을 듣는다면</span>
                                    </div>
                                    <span class="arrow-icon">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.15146 20.8485C7.68283 20.3799 7.68283 19.6201 8.15146 19.1515L15.3029 12L8.15146 4.84852C7.68283 4.37989 7.68283 3.6201 8.15146 3.15147C8.62009 2.68284 9.37989 2.68284 9.84852 3.15147L17.8485 11.1515C18.3171 11.6201 18.3171 12.3799 17.8485 12.8485L9.84852 20.8485C9.37989 21.3172 8.62009 21.3172 8.15146 20.8485Z" fill="#868E96"></path>
                                        </svg>
                                    </span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/calculator/plans-by-data/recommended-one?data=71&from=home" class="data-plan-card">
                                <div class="data-plan-header">
                                    <span class="data-amount">71GB</span>
                                    <div class="data-plan-info">
                                        <p class="data-price">13,000원</p>
                                        <span class="data-desc">매일 1시간 이상 영상을 본다면</span>
                                    </div>
                                    <span class="arrow-icon">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.15146 20.8485C7.68283 20.3799 7.68283 19.6201 8.15146 19.1515L15.3029 12L8.15146 4.84852C7.68283 4.37989 7.68283 3.6201 8.15146 3.15147C8.62009 2.68284 9.37989 2.68284 9.84852 3.15147L17.8485 11.1515C18.3171 11.6201 18.3171 12.3799 17.8485 12.8485L9.84852 20.8485C9.37989 21.3172 8.62009 21.3172 8.15146 20.8485Z" fill="#868E96"></path>
                                        </svg>
                                    </span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/calculator/plans-by-data/recommended-one?data=100&from=home" class="data-plan-card">
                                <div class="data-plan-header">
                                    <span class="data-amount">100GB</span>
                                    <div class="data-plan-info">
                                        <p class="data-price">17,000원</p>
                                        <span class="data-desc">걱정 없이 맘껏 쓰고 싶다면</span>
                                    </div>
                                    <span class="arrow-icon">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.15146 20.8485C7.68283 20.3799 7.68283 19.6201 8.15146 19.1515L15.3029 12L8.15146 4.84852C7.68283 4.37989 7.68283 3.6201 8.15146 3.15147C8.62009 2.68284 9.37989 2.68284 9.84852 3.15147L17.8485 11.1515C18.3171 11.6201 18.3171 12.3799 17.8485 12.8485L9.84852 20.8485C9.37989 21.3172 8.62009 21.3172 8.15146 20.8485Z" fill="#868E96"></path>
                                        </svg>
                                    </span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/calculator/plans-by-data/recommended-one/direct-plan" class="data-plan-card">
                                <div class="data-plan-header">
                                    <span class="data-amount">무제한</span>
                                    <div class="data-plan-info">
                                        <p class="data-price">19,100원</p>
                                        <span class="data-desc">5G 요금제, 유심 무료 배송까지</span>
                                    </div>
                                    <span class="arrow-icon">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.15146 20.8485C7.68283 20.3799 7.68283 19.6201 8.15146 19.1515L15.3029 12L8.15146 4.84852C7.68283 4.37989 7.68283 3.6201 8.15146 3.15147C8.62009 2.68284 9.37989 2.68284 9.84852 3.15147L17.8485 11.1515C18.3171 11.6201 18.3171 12.3799 17.8485 12.8485L9.84852 20.8485C9.37989 21.3172 8.62009 21.3172 8.15146 20.8485Z" fill="#868E96"></path>
                                        </svg>
                                    </span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </div>

    <!-- 두 번째 섹션: 휴대폰 특가 -->
    <div class="phone-deal-section bg-gray-100">
        <div class="content-layout">
            <section class="phone-deal-carousel-section">
                <div class="phone-deal-header">
                    <div class="phone-deal-title-wrapper">
                        <span class="phone-deal-subtitle">자급제 + 알뜰폰보다 저렴한</span>
                        <div class="phone-deal-title-row">
                            <h2>휴대폰 특가</h2>
                            <div class="countdown-timer">
                                <span id="countdown">1일 15:33:28 남음</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="phone-deal-swiper-wrapper">
                    <div class="phone-deal-swiper" id="phoneDealSwiper">
                        <div class="phone-deal-slide">
                            <div class="phone-deal-card">
                                <div class="phone-deal-card-header">
                                    <span class="phone-name">갤럭시 S25</span>
                                    <span class="discount-badge">105만원 할인</span>
                                </div>
                                <div class="phone-image">
                                    <img src="https://assets.moyoplan.com/image/phone/model/galaxy_s_25.png" alt="갤럭시 S25">
                                </div>
                                <div class="phone-price-info">
                                    <span class="phone-price">105,000원부터</span>
                                    <div class="price-comparison">
                                        <span class="original-price">1,155,000원</span>
                                        <span class="comparison-text">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3.85923 6.31899C3.4339 5.89367 2.74432 5.89367 2.31899 6.31899C1.89367 6.74432 1.89367 7.4339 2.31899 7.85923L8.3946 13.9348C8.81992 14.3602 9.50951 14.3602 9.93483 13.9348L13.2151 10.6545L18.3943 15.8338H16.0831C15.4849 15.8338 15 16.3187 15 16.9169C15 17.5151 15.4849 18 16.0831 18H20.9169C21.5151 18 22 17.5151 22 16.9169V12.0831C22 11.4849 21.5151 11 20.9169 11C20.3187 11 19.8338 11.4849 19.8338 12.0831V14.1927L13.9852 8.3442C13.5599 7.91887 12.8703 7.91887 12.445 8.3442L9.16472 11.6245L3.85923 6.31899Z" fill="#40C057"></path>
                                            </svg>
                                            쿠팡보다 95만원 저렴해요
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- 추가 휴대폰 슬라이드들... -->
                    </div>
                    <button type="button" class="swiper-nav-btn next" aria-label="다음 목록">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.15146 20.8485C7.68283 20.3799 7.68283 19.6201 8.15146 19.1515L15.3029 12L8.15146 4.84852C7.68283 4.37989 7.68283 3.6201 8.15146 3.15147C8.62009 2.68284 9.37989 2.68284 9.84852 3.15147L17.8485 11.1515C18.3171 11.6201 18.3171 12.3799 17.8485 12.8485L9.84852 20.8485C9.37989 21.3172 8.62009 21.3172 8.15146 20.8485Z" fill="#868E96"></path>
                        </svg>
                    </button>
                    <div class="swiper-gradient"></div>
                </div>
                <div class="phone-deal-footer">
                    <a href="/phones" class="more-btn">
                        <span>특가 더보기</span>
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.15146 20.8485C7.68283 20.3799 7.68283 19.6201 8.15146 19.1515L15.3029 12L8.15146 4.84852C7.68283 4.37989 7.68283 3.6201 8.15146 3.15147C8.62009 2.68284 9.37989 2.68284 9.84852 3.15147L17.8485 11.1515C18.3171 11.6201 18.3171 12.3799 17.8485 12.8485L9.84852 20.8485C9.37989 21.3172 8.62009 21.3172 8.15146 20.8485Z" fill="#868E96"></path>
                        </svg>
                    </a>
                </div>
            </section>
        </div>
    </div>

    <!-- 세 번째 섹션: 110만명이 선택한 통신사 -->
    <div class="exclusive-section bg-gray-200">
        <div class="content-layout">
            <section class="exclusive-plan-section">
                <div class="exclusive-header">
                    <div class="exclusive-icon">
                        <img src="https://assets.moyoplan.com/logo/mpo/14.svg" alt="110만명이 선택한 통신사" width="40" height="40">
                    </div>
                    <header class="section-header">
                        <h2>110만명이 선택한 통신사</h2>
                        <p>통신비 줄이고, 혜택 챙기는 똑똑한 선택</p>
                    </header>
                </div>
                <div class="exclusive-swiper-wrapper">
                    <div class="exclusive-swiper" id="exclusiveSwiper">
                        <div class="exclusive-slide">
                            <a href="/plans/26257" class="plan-card">
                                <div class="plan-card-content">
                                    <header>
                                        <h4>[모요블프][평생할인] 7GB+/통화기본</h4>
                                        <div class="plan-data">
                                            <h3>월 7GB + 1Mbps</h3>
                                        </div>
                                        <div class="plan-features">
                                            <span>통화 무제한</span>
                                            <span>문자 무제한</span>
                                        </div>
                                    </header>
                                    <ul class="gift-list">
                                        <li>
                                            <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg" alt="네이버페이" width="16" height="16">
                                            <span>[모요블프] 매달 Npay 1만원(12만)</span>
                                        </li>
                                        <li>
                                            <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/emart24.svg" alt="이마트24" width="16" height="16">
                                            <span>12만원 혜택(매월 이마트24 5천원)</span>
                                        </li>
                                        <li>
                                            <img src="https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg" alt="기타" width="16" height="16">
                                            <span>우리결합시, 데이터 5GB 추가 증정</span>
                                        </li>
                                    </ul>
                                    <div class="plan-price">
                                        <span class="lifetime-badge">평생</span>
                                        <span class="price">15,900원</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <!-- 추가 요금제 슬라이드들... -->
                    </div>
                </div>
                <div class="section-footer">
                    <a href="/plans?mobilePlanOperatorIdList=14&sorting=recommend_v2&page=0&from=home" class="more-btn">
                        <span>요금제 더보기</span>
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.15146 20.8485C7.68283 20.3799 7.68283 19.6201 8.15146 19.1515L15.3029 12L8.15146 4.84852C7.68283 4.37989 7.68283 3.6201 8.15146 3.15147C8.62009 2.68284 9.37989 2.68284 9.84852 3.15147L17.8485 11.1515C18.3171 11.6201 18.3171 12.3799 17.8485 12.8485L9.84852 20.8485C9.37989 21.3172 8.62009 21.3172 8.15146 20.8485Z" fill="#868E96"></path>
                        </svg>
                    </a>
                </div>
            </section>
        </div>
    </div>

    <!-- 네 번째 섹션: 테마별 요금제 -->
    <div class="theme-section">
        <div class="content-layout">
            <section class="theme-plan-section">
                <header class="section-header">
                    <h2>테마별 요금제</h2>
                </header>
                <div class="theme-swiper-wrapper">
                    <div class="theme-swiper" id="themeSwiper">
                        <div class="theme-slide">
                            <a href="/plans/themes/life-time-discount" class="theme-card">
                                <img src="https://assets.moyoplan.com/image/home/cash.png" alt="평생 할인" width="48" height="48">
                                <div class="theme-content">
                                    <h3>평생 할인</h3>
                                    <p>평생 이 가격 그대로!</p>
                                </div>
                            </a>
                        </div>
                        <div class="theme-slide">
                            <a href="/plans/themes/twenty-four-discount" class="theme-card">
                                <img src="https://assets.moyoplan.com/image/home/discount-date.png" alt="24개월 할인" width="48" height="48">
                                <div class="theme-content">
                                    <h3>24개월 할인</h3>
                                    <p>2년 동안 저렴해요</p>
                                </div>
                            </a>
                        </div>
                        <div class="theme-slide">
                            <a href="/plans/themes/tethering" class="theme-card">
                                <img src="https://assets.moyoplan.com/image/home/wifi.png" alt="핫스팟 전용" width="48" height="48">
                                <div class="theme-content">
                                    <h3>핫스팟 전용</h3>
                                    <p>데이터 맘껏 공유해요</p>
                                </div>
                            </a>
                        </div>
                        <div class="theme-slide">
                            <a href="/plans/themes/high-quality-video" class="theme-card">
                                <img src="https://assets.moyoplan.com/image/home/youtube.png" alt="고화질 동영상" width="48" height="48">
                                <div class="theme-content">
                                    <h3>고화질 동영상</h3>
                                    <p>데이터 걱정 없어요</p>
                                </div>
                            </a>
                        </div>
                        <div class="theme-slide">
                            <a href="/plans/themes/5g" class="theme-card">
                                <img src="https://assets.moyoplan.com/image/home/5g.png" alt="5G 요금제" width="48" height="48">
                                <div class="theme-content">
                                    <h3>5G 요금제</h3>
                                    <p>5G를 반값으로!</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<?php
// 푸터 포함
include 'includes/footer.php';
?>

