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
                            <a href="/calculator/plans-by-data/recommended-one?data=1&from=home" class="data-plan-card">
                                <div class="data-plan-header">
                                    <span class="data-amount">1GB</span>
                                    <div class="data-plan-info">
                                        <p class="data-price">5,000원</p>
                                        <span class="data-desc">기본 데이터만 필요한 경우</span>
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
                            <a href="/calculator/plans-by-data/recommended-one?data=7&from=home" class="data-plan-card">
                                <div class="data-plan-header">
                                    <span class="data-amount">7GB</span>
                                    <div class="data-plan-info">
                                        <p class="data-price">100원</p>
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
                                        <p class="data-price">5,000원</p>
                                        <span class="data-desc">무약정</span>
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
                                        <span class="data-desc">약정93일</span>
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
                                        <span class="data-desc">핫스팟전용</span>
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
                        <div class="phone-deal-title-row">
                            <h2>자급제 성지 특가</h2>
                        </div>
                    </div>
                </div>
                <div class="phone-deal-swiper-wrapper">
                    <button type="button" class="swiper-nav-btn prev" aria-label="이전 목록">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M15.8485 20.8485C16.3172 20.3799 16.3172 19.6201 15.8485 19.1515L8.69712 12L15.8485 4.84852C16.3172 4.37989 16.3172 3.6201 15.8485 3.15147C15.3799 2.68284 14.6201 2.68284 14.1515 3.15147L6.15147 11.1515C5.68284 11.6201 5.68284 12.3799 6.15147 12.8485L14.1515 20.8485C14.6201 21.3172 15.3799 21.3172 15.8485 20.8485Z" fill="#868E96"></path>
                        </svg>
                    </button>
                    <div class="phone-deal-swiper" id="phoneDealSwiper">
                        <div class="phone-deal-slide">
                            <div class="phone-deal-card">
                                <div class="phone-deal-card-header">
                                    <span class="phone-name">갤럭시 S25</span>
                                    <span class="discount-badge">105만원 할인</span>
                                </div>
                                <div class="phone-image">
                                    <img src="https://assets.moyoplan.com/image/phone/model/galaxy_s_25.png" alt="갤럭시 S25" onerror="this.style.display='none'; this.parentElement.style.minHeight='200px';">
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
                        <div class="phone-deal-slide">
                            <div class="phone-deal-card">
                                <div class="phone-deal-card-header">
                                    <span class="phone-name">아이폰16 프로</span>
                                    <span class="discount-badge">110만원 할인</span>
                                </div>
                                <div class="phone-image">
                                    <img src="https://assets.moyoplan.com/image/phone/model/galaxy_s_25.png" alt="아이폰16 프로" onerror="this.style.display='none'; this.parentElement.style.minHeight='200px';">
                                </div>
                                <div class="phone-price-info">
                                    <span class="phone-price">891,000원부터</span>
                                    <div class="price-comparison">
                                        <span class="original-price">1,991,000원</span>
                                        <span class="comparison-text">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3.85923 6.31899C3.4339 5.89367 2.74432 5.89367 2.31899 6.31899C1.89367 6.74432 1.89367 7.4339 2.31899 7.85923L8.3946 13.9348C8.81992 14.3602 9.50951 14.3602 9.93483 13.9348L13.2151 10.6545L18.3943 15.8338H16.0831C15.4849 15.8338 15 16.3187 15 16.9169C15 17.5151 15.4849 18 16.0831 18H20.9169C21.5151 18 22 17.5151 22 16.9169V12.0831C22 11.4849 21.5151 11 20.9169 11C20.3187 11 19.8338 11.4849 19.8338 12.0831V14.1927L13.9852 8.3442C13.5599 7.91887 12.8703 7.91887 12.445 8.3442L9.16472 11.6245L3.85923 6.31899Z" fill="#40C057"></path>
                                            </svg>
                                            쿠팡보다 130만원 저렴해요
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="phone-deal-slide">
                            <div class="phone-deal-card">
                                <div class="phone-deal-card-header">
                                    <span class="phone-name">갤럭시 Z 폴드7</span>
                                    <span class="discount-badge">100만원 할인</span>
                                </div>
                                <div class="phone-image">
                                    <img src="https://assets.moyoplan.com/image/phone/model/galaxy_s_25.png" alt="갤럭시 Z 폴드7" onerror="this.style.display='none'; this.parentElement.style.minHeight='200px';">
                                </div>
                                <div class="phone-price-info">
                                    <span class="phone-price">1,379,300원부터</span>
                                    <div class="price-comparison">
                                        <span class="original-price">2,379,300원</span>
                                        <span class="comparison-text">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3.85923 6.31899C3.4339 5.89367 2.74432 5.89367 2.31899 6.31899C1.89367 6.74432 1.89367 7.4339 2.31899 7.85923L8.3946 13.9348C8.81992 14.3602 9.50951 14.3602 9.93483 13.9348L13.2151 10.6545L18.3943 15.8338H16.0831C15.4849 15.8338 15 16.3187 15 16.9169C15 17.5151 15.4849 18 16.0831 18H20.9169C21.5151 18 22 17.5151 22 16.9169V12.0831C22 11.4849 21.5151 11 20.9169 11C20.3187 11 19.8338 11.4849 19.8338 12.0831V14.1927L13.9852 8.3442C13.5599 7.91887 12.8703 7.91887 12.445 8.3442L9.16472 11.6245L3.85923 6.31899Z" fill="#40C057"></path>
                                            </svg>
                                            쿠팡보다 80만원 저렴해요
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="phone-deal-slide">
                            <div class="phone-deal-card">
                                <div class="phone-deal-card-header">
                                    <span class="phone-name">아이폰</span>
                                    <span class="discount-badge">할인</span>
                                </div>
                                <div class="phone-image">
                                    <img src="https://assets.moyoplan.com/image/phone/model/galaxy_s_25.png" alt="아이폰" onerror="this.style.display='none'; this.parentElement.style.minHeight='200px';">
                                </div>
                                <div class="phone-price-info">
                                    <span class="phone-price">237,000원부터</span>
                                    <div class="price-comparison">
                                        <span class="original-price">1,287,000원</span>
                                        <span class="comparison-text">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3.85923 6.31899C3.4339 5.89367 2.74432 5.89367 2.31899 6.31899C1.89367 6.74432 1.89367 7.4339 2.31899 7.85923L8.3946 13.9348C8.81992 14.3602 9.50951 14.3602 9.93483 13.9348L13.2151 10.6545L18.3943 15.8338H16.0831C15.4849 15.8338 15 16.3187 15 16.9169C15 17.5151 15.4849 18 16.0831 18H20.9169C21.5151 18 22 17.5151 22 16.9169V12.0831C22 11.4849 21.5151 11 20.9169 11C20.3187 11 19.8338 11.4849 19.8338 12.0831V14.1927L13.9852 8.3442C13.5599 7.91887 12.8703 7.91887 12.445 8.3442L9.16472 11.6245L3.85923 6.31899Z" fill="#40C057"></path>
                                            </svg>
                                            쿠팡보다 저렴해요
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="phone-deal-slide">
                            <div class="phone-deal-card">
                                <div class="phone-deal-card-header">
                                    <span class="phone-name">갤럭시 S24</span>
                                    <span class="discount-badge">90만원 할인</span>
                                </div>
                                <div class="phone-image">
                                    <img src="https://assets.moyoplan.com/image/phone/model/galaxy_s_25.png" alt="갤럭시 S24" onerror="this.style.display='none'; this.parentElement.style.minHeight='200px';">
                                </div>
                                <div class="phone-price-info">
                                    <span class="phone-price">95,000원부터</span>
                                    <div class="price-comparison">
                                        <span class="original-price">1,045,000원</span>
                                        <span class="comparison-text">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3.85923 6.31899C3.4339 5.89367 2.74432 5.89367 2.31899 6.31899C1.89367 6.74432 1.89367 7.4339 2.31899 7.85923L8.3946 13.9348C8.81992 14.3602 9.50951 14.3602 9.93483 13.9348L13.2151 10.6545L18.3943 15.8338H16.0831C15.4849 15.8338 15 16.3187 15 16.9169C15 17.5151 15.4849 18 16.0831 18H20.9169C21.5151 18 22 17.5151 22 16.9169V12.0831C22 11.4849 21.5151 11 20.9169 11C20.3187 11 19.8338 11.4849 19.8338 12.0831V14.1927L13.9852 8.3442C13.5599 7.91887 12.8703 7.91887 12.445 8.3442L9.16472 11.6245L3.85923 6.31899Z" fill="#40C057"></path>
                                            </svg>
                                            쿠팡보다 85만원 저렴해요
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="phone-deal-slide">
                            <div class="phone-deal-card">
                                <div class="phone-deal-card-header">
                                    <span class="phone-name">아이폰15 프로</span>
                                    <span class="discount-badge">100만원 할인</span>
                                </div>
                                <div class="phone-image">
                                    <img src="https://assets.moyoplan.com/image/phone/model/galaxy_s_25.png" alt="아이폰15 프로" onerror="this.style.display='none'; this.parentElement.style.minHeight='200px';">
                                </div>
                                <div class="phone-price-info">
                                    <span class="phone-price">791,000원부터</span>
                                    <div class="price-comparison">
                                        <span class="original-price">1,791,000원</span>
                                        <span class="comparison-text">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3.85923 6.31899C3.4339 5.89367 2.74432 5.89367 2.31899 6.31899C1.89367 6.74432 1.89367 7.4339 2.31899 7.85923L8.3946 13.9348C8.81992 14.3602 9.50951 14.3602 9.93483 13.9348L13.2151 10.6545L18.3943 15.8338H16.0831C15.4849 15.8338 15 16.3187 15 16.9169C15 17.5151 15.4849 18 16.0831 18H20.9169C21.5151 18 22 17.5151 22 16.9169V12.0831C22 11.4849 21.5151 11 20.9169 11C20.3187 11 19.8338 11.4849 19.8338 12.0831V14.1927L13.9852 8.3442C13.5599 7.91887 12.8703 7.91887 12.445 8.3442L9.16472 11.6245L3.85923 6.31899Z" fill="#40C057"></path>
                                            </svg>
                                            쿠팡보다 120만원 저렴해요
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="phone-deal-slide">
                            <div class="phone-deal-card">
                                <div class="phone-deal-card-header">
                                    <span class="phone-name">갤럭시 Z 플립7</span>
                                    <span class="discount-badge">95만원 할인</span>
                                </div>
                                <div class="phone-image">
                                    <img src="https://assets.moyoplan.com/image/phone/model/galaxy_s_25.png" alt="갤럭시 Z 플립7" onerror="this.style.display='none'; this.parentElement.style.minHeight='200px';">
                                </div>
                                <div class="phone-price-info">
                                    <span class="phone-price">1,179,300원부터</span>
                                    <div class="price-comparison">
                                        <span class="original-price">2,179,300원</span>
                                        <span class="comparison-text">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3.85923 6.31899C3.4339 5.89367 2.74432 5.89367 2.31899 6.31899C1.89367 6.74432 1.89367 7.4339 2.31899 7.85923L8.3946 13.9348C8.81992 14.3602 9.50951 14.3602 9.93483 13.9348L13.2151 10.6545L18.3943 15.8338H16.0831C15.4849 15.8338 15 16.3187 15 16.9169C15 17.5151 15.4849 18 16.0831 18H20.9169C21.5151 18 22 17.5151 22 16.9169V12.0831C22 11.4849 21.5151 11 20.9169 11C20.3187 11 19.8338 11.4849 19.8338 12.0831V14.1927L13.9852 8.3442C13.5599 7.91887 12.8703 7.91887 12.445 8.3442L9.16472 11.6245L3.85923 6.31899Z" fill="#40C057"></path>
                                            </svg>
                                            쿠팡보다 75만원 저렴해요
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="phone-deal-slide">
                            <div class="phone-deal-card">
                                <div class="phone-deal-card-header">
                                    <span class="phone-name">아이폰15</span>
                                    <span class="discount-badge">85만원 할인</span>
                                </div>
                                <div class="phone-image">
                                    <img src="https://assets.moyoplan.com/image/phone/model/galaxy_s_25.png" alt="아이폰15" onerror="this.style.display='none'; this.parentElement.style.minHeight='200px';">
                                </div>
                                <div class="phone-price-info">
                                    <span class="phone-price">637,000원부터</span>
                                    <div class="price-comparison">
                                        <span class="original-price">1,437,000원</span>
                                        <span class="comparison-text">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3.85923 6.31899C3.4339 5.89367 2.74432 5.89367 2.31899 6.31899C1.89367 6.74432 1.89367 7.4339 2.31899 7.85923L8.3946 13.9348C8.81992 14.3602 9.50951 14.3602 9.93483 13.9348L13.2151 10.6545L18.3943 15.8338H16.0831C15.4849 15.8338 15 16.3187 15 16.9169C15 17.5151 15.4849 18 16.0831 18H20.9169C21.5151 18 22 17.5151 22 16.9169V12.0831C22 11.4849 21.5151 11 20.9169 11C20.3187 11 19.8338 11.4849 19.8338 12.0831V14.1927L13.9852 8.3442C13.5599 7.91887 12.8703 7.91887 12.445 8.3442L9.16472 11.6245L3.85923 6.31899Z" fill="#40C057"></path>
                                            </svg>
                                            쿠팡보다 110만원 저렴해요
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="phone-deal-slide">
                            <div class="phone-deal-card">
                                <div class="phone-deal-card-header">
                                    <span class="phone-name">갤럭시 노트</span>
                                    <span class="discount-badge">80만원 할인</span>
                                </div>
                                <div class="phone-image">
                                    <img src="https://assets.moyoplan.com/image/phone/model/galaxy_s_25.png" alt="갤럭시 노트" onerror="this.style.display='none'; this.parentElement.style.minHeight='200px';">
                                </div>
                                <div class="phone-price-info">
                                    <span class="phone-price">1,079,300원부터</span>
                                    <div class="price-comparison">
                                        <span class="original-price">1,879,300원</span>
                                        <span class="comparison-text">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3.85923 6.31899C3.4339 5.89367 2.74432 5.89367 2.31899 6.31899C1.89367 6.74432 1.89367 7.4339 2.31899 7.85923L8.3946 13.9348C8.81992 14.3602 9.50951 14.3602 9.93483 13.9348L13.2151 10.6545L18.3943 15.8338H16.0831C15.4849 15.8338 15 16.3187 15 16.9169C15 17.5151 15.4849 18 16.0831 18H20.9169C21.5151 18 22 17.5151 22 16.9169V12.0831C22 11.4849 21.5151 11 20.9169 11C20.3187 11 19.8338 11.4849 19.8338 12.0831V14.1927L13.9852 8.3442C13.5599 7.91887 12.8703 7.91887 12.445 8.3442L9.16472 11.6245L3.85923 6.31899Z" fill="#40C057"></path>
                                            </svg>
                                            쿠팡보다 70만원 저렴해요
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="swiper-nav-btn next" aria-label="다음 목록">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.15146 20.8485C7.68283 20.3799 7.68283 19.6201 8.15146 19.1515L15.3029 12L8.15146 4.84852C7.68283 4.37989 7.68283 3.6201 8.15146 3.15147C8.62009 2.68284 9.37989 2.68284 9.84852 3.15147L17.8485 11.1515C18.3171 11.6201 18.3171 12.3799 17.8485 12.8485L9.84852 20.8485C9.37989 21.3172 8.62009 21.3172 8.15146 20.8485Z" fill="#868E96"></path>
                        </svg>
                    </button>
                    <div class="swiper-gradient prev-gradient"></div>
                    <div class="swiper-gradient next-gradient"></div>
                </div>
                <div class="phone-deal-footer">
                    <a href="/phones?from=home" class="more-btn">
                        <span>자급제폰 더보기 &gt;</span>
                    </a>
                </div>
            </section>
        </div>
    </div>

    <!-- 세 번째 섹션: 최대할인 인터넷 상품 -->
    <div class="exclusive-section bg-gray-200">
        <div class="content-layout">
            <section class="exclusive-plan-section">
                <div class="exclusive-header">
                    <header class="section-header">
                        <h2>최대할인 인터넷 상품</h2>
                        <p>현금성 상품받고, 최대혜택 누리기</p>
                    </header>
                </div>
                <div class="exclusive-swiper-wrapper">
                    <div class="exclusive-swiper" id="exclusiveSwiper">
                        <div class="exclusive-slide">
                            <a href="/plans/26257" class="plan-card">
                                <div class="plan-card-content internet-card">
                                    <!-- 상단: 통신사 로고 + 인터넷/TV 정보 -->
                                    <div class="internet-card-header">
                                        <div class="internet-company">
                                            <img src="https://assets-legacy.moyoplan.com/internets/assets/ktskylife.svg" alt="KT skylife" class="internet-company-logo">
                                        </div>
                                        <div class="internet-specs">
                                            <div class="internet-spec">
                                                <img src="https://assets-legacy.moyoplan.com/internets/assets/thunder_s.svg" alt="인터넷 속도" class="internet-spec-icon">
                                                <span>500MB</span>
                                            </div>
                                            <div class="internet-spec">
                                                <img src="https://assets-legacy.moyoplan.com/internets/assets/tv_s.svg" alt="TV 채널" class="internet-spec-icon">
                                                <span>194개</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 중간: 혜택 리스트 -->
                                    <div class="internet-benefits">
                                        <div class="internet-benefit">
                                            <img src="https://assets-legacy.moyoplan.com/internets/assets/gift.svg" alt="혜택" class="internet-benefit-icon">
                                            <div class="internet-benefit-text">
                                                <p class="internet-benefit-title">인터넷,TV 설치비 무료</p>
                                                <p class="internet-benefit-sub">무료(36,300원 상당)</p>
                                            </div>
                                        </div>
                                        <div class="internet-benefit">
                                            <img src="https://assets-legacy.moyoplan.com/internets/assets/gift.svg" alt="혜택" class="internet-benefit-icon">
                                            <div class="internet-benefit-text">
                                                <p class="internet-benefit-title">셋톱박스 임대료 무료</p>
                                                <p class="internet-benefit-sub">무료(월 3,300원 상당)</p>
                                            </div>
                                        </div>
                                        <div class="internet-benefit">
                                            <img src="https://assets-legacy.moyoplan.com/internets/assets/gift.svg" alt="혜택" class="internet-benefit-icon">
                                            <div class="internet-benefit-text">
                                                <p class="internet-benefit-title">와이파이 공유기</p>
                                                <p class="internet-benefit-sub">무료(월 1,100원 상당)</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 하단: 월 요금 -->
                                    <div class="internet-price">
                                        <p class="internet-price-text">월 26,400원</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <!-- 두 번째 인터넷 상품 카드 (동일 상품) -->
                        <div class="exclusive-slide">
                            <a href="/plans/26257" class="plan-card">
                                <div class="plan-card-content internet-card">
                                    <!-- 상단: 통신사 로고 + 인터넷/TV 정보 -->
                                    <div class="internet-card-header">
                                        <div class="internet-company">
                                            <img src="https://assets-legacy.moyoplan.com/internets/assets/ktskylife.svg" alt="KT skylife" class="internet-company-logo">
                                        </div>
                                        <div class="internet-specs">
                                            <div class="internet-spec">
                                                <img src="https://assets-legacy.moyoplan.com/internets/assets/thunder_s.svg" alt="인터넷 속도" class="internet-spec-icon">
                                                <span>500MB</span>
                                            </div>
                                            <div class="internet-spec">
                                                <img src="https://assets-legacy.moyoplan.com/internets/assets/tv_s.svg" alt="TV 채널" class="internet-spec-icon">
                                                <span>194개</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 중간: 혜택 리스트 -->
                                    <div class="internet-benefits">
                                        <div class="internet-benefit">
                                            <img src="https://assets-legacy.moyoplan.com/internets/assets/gift.svg" alt="혜택" class="internet-benefit-icon">
                                            <div class="internet-benefit-text">
                                                <p class="internet-benefit-title">인터넷,TV 설치비 무료</p>
                                                <p class="internet-benefit-sub">무료(36,300원 상당)</p>
                                            </div>
                                        </div>
                                        <div class="internet-benefit">
                                            <img src="https://assets-legacy.moyoplan.com/internets/assets/gift.svg" alt="혜택" class="internet-benefit-icon">
                                            <div class="internet-benefit-text">
                                                <p class="internet-benefit-title">셋톱박스 임대료 무료</p>
                                                <p class="internet-benefit-sub">무료(월 3,300원 상당)</p>
                                            </div>
                                        </div>
                                        <div class="internet-benefit">
                                            <img src="https://assets-legacy.moyoplan.com/internets/assets/gift.svg" alt="혜택" class="internet-benefit-icon">
                                            <div class="internet-benefit-text">
                                                <p class="internet-benefit-title">와이파이 공유기</p>
                                                <p class="internet-benefit-sub">무료(월 1,100원 상당)</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 하단: 월 요금 -->
                                    <div class="internet-price">
                                        <p class="internet-price-text">월 26,400원</p>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- 세 번째 인터넷 상품 카드 (동일 상품) -->
                        <div class="exclusive-slide">
                            <a href="/plans/26257" class="plan-card">
                                <div class="plan-card-content internet-card">
                                    <!-- 상단: 통신사 로고 + 인터넷/TV 정보 -->
                                    <div class="internet-card-header">
                                        <div class="internet-company">
                                            <img src="https://assets-legacy.moyoplan.com/internets/assets/ktskylife.svg" alt="KT skylife" class="internet-company-logo">
                                        </div>
                                        <div class="internet-specs">
                                            <div class="internet-spec">
                                                <img src="https://assets-legacy.moyoplan.com/internets/assets/thunder_s.svg" alt="인터넷 속도" class="internet-spec-icon">
                                                <span>500MB</span>
                                            </div>
                                            <div class="internet-spec">
                                                <img src="https://assets-legacy.moyoplan.com/internets/assets/tv_s.svg" alt="TV 채널" class="internet-spec-icon">
                                                <span>194개</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 중간: 혜택 리스트 -->
                                    <div class="internet-benefits">
                                        <div class="internet-benefit">
                                            <img src="https://assets-legacy.moyoplan.com/internets/assets/gift.svg" alt="혜택" class="internet-benefit-icon">
                                            <div class="internet-benefit-text">
                                                <p class="internet-benefit-title">인터넷,TV 설치비 무료</p>
                                                <p class="internet-benefit-sub">무료(36,300원 상당)</p>
                                            </div>
                                        </div>
                                        <div class="internet-benefit">
                                            <img src="https://assets-legacy.moyoplan.com/internets/assets/gift.svg" alt="혜택" class="internet-benefit-icon">
                                            <div class="internet-benefit-text">
                                                <p class="internet-benefit-title">셋톱박스 임대료 무료</p>
                                                <p class="internet-benefit-sub">무료(월 3,300원 상당)</p>
                                            </div>
                                        </div>
                                        <div class="internet-benefit">
                                            <img src="https://assets-legacy.moyoplan.com/internets/assets/gift.svg" alt="혜택" class="internet-benefit-icon">
                                            <div class="internet-benefit-text">
                                                <p class="internet-benefit-title">와이파이 공유기</p>
                                                <p class="internet-benefit-sub">무료(월 1,100원 상당)</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 하단: 월 요금 -->
                                    <div class="internet-price">
                                        <p class="internet-price-text">월 26,400원</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="section-footer">
                    <a href="plans.php?mobilePlanOperatorIdList=14&sorting=recommend_v2&page=0&from=home" class="more-btn">
                        <span>인터넷 상품 더보기</span>
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.15146 20.8485C7.68283 20.3799 7.68283 19.6201 8.15146 19.1515L15.3029 12L8.15146 4.84852C7.68283 4.37989 7.68283 3.6201 8.15146 3.15147C8.62009 2.68284 9.37989 2.68284 9.84852 3.15147L17.8485 11.1515C18.3171 11.6201 18.3171 12.3799 17.8485 12.8485L9.84852 20.8485C9.37989 21.3172 8.62009 21.3172 8.15146 20.8485Z" fill="#868E96"></path>
                        </svg>
                    </a>
                </div>
            </section>
        </div>
    </div>
</main>

<?php
// 푸터 포함
include 'includes/footer.php';
?>

