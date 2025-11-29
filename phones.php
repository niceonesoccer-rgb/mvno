<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'phones';

// 헤더 포함
include 'includes/header.php';
?>

<main class="main-content">
    <!-- 자급제 메뉴 섹션 -->
    <div class="self-pay-menu-section">
        <div class="content-layout">
            <div class="self-pay-menu-container">
                <!-- 오늘의 특가 -->
                <section class="today-deal-section">
                    <h2 class="section-title">오늘의 특가</h2>
                    <ul class="today-deal-list">
                        <li class="deal-item">
                            <div class="deal-item-content">
                                <span class="deal-rank">1</span>
                                <div class="deal-image-wrapper">
                                    <img src="https://assets.moyoplan.com/image/phone/model/galaxy_s_25.png" alt="갤럭시 S25" class="deal-image">
                                </div>
                                <div class="deal-info">
                                    <div class="deal-details">
                                        <span class="deal-name">갤럭시 S25</span>
                                        <span class="deal-price">105,000원</span>
                                    </div>
                                    <span class="deal-badge">91%</span>
                                </div>
                                <button class="deal-button">보러가기</button>
                            </div>
                        </li>
                        <li class="deal-item">
                            <div class="deal-item-content">
                                <span class="deal-rank">2</span>
                                <div class="deal-image-wrapper">
                                    <img src="https://assets.moyoplan.com/image/phone/model/iphone_16_pro.png" alt="아이폰16 프로" class="deal-image">
                                </div>
                                <div class="deal-info">
                                    <div class="deal-details">
                                        <span class="deal-name">아이폰16 프로</span>
                                        <span class="deal-price">891,000원</span>
                                    </div>
                                    <span class="deal-badge">55%</span>
                                </div>
                                <button class="deal-button">보러가기</button>
                            </div>
                        </li>
                        <li class="deal-item">
                            <div class="deal-item-content">
                                <span class="deal-rank">3</span>
                                <div class="deal-image-wrapper">
                                    <img src="https://assets.moyoplan.com/image/phone/model/galaxy_z_fold7.png" alt="갤럭시 Z 폴드7" class="deal-image">
                                </div>
                                <div class="deal-info">
                                    <div class="deal-details">
                                        <span class="deal-name">갤럭시 Z 폴드7</span>
                                        <span class="deal-price">1,379,300원</span>
                                    </div>
                                    <span class="deal-badge">42%</span>
                                </div>
                                <button class="deal-button">보러가기</button>
                            </div>
                        </li>
                    </ul>
                    <a href="/phones/moyo-deal" class="view-all-button">
                        <button class="view-all-btn">모두 보기</button>
                    </a>
                </section>

                <!-- 가격 비교 요약 -->
                <section class="price-compare-section">
                    <div class="compare-header">
                        <div class="compare-title-wrapper">
                            <button type="button" class="phone-select-button">
                                <span class="phone-name">갤럭시 S25</span>
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="dropdown-icon">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM8.77782 10.2222C8.34824 9.79261 7.65176 9.79261 7.22218 10.2222C6.79261 10.6518 6.79261 11.3482 7.22218 11.7778L11.2222 15.7778C11.6518 16.2074 12.3482 16.2074 12.7778 15.7778L16.7778 11.7778C17.2074 11.3482 17.2074 10.6518 16.7778 10.2222C16.3482 9.79261 15.6518 9.79261 15.2222 10.2222L12 13.4444L8.77782 10.2222Z" fill="#ADB5BD"></path>
                                </svg>
                            </button>
                            <span class="compare-subtitle">가격 한눈에 확인해요</span>
                        </div>
                        <div class="phone-image-preview">
                            <img src="https://assets.moyoplan.com/image/phone/model/galaxy_s_25.png" alt="갤럭시 S25" class="preview-image">
                        </div>
                    </div>
                    <div class="storage-tabs">
                        <div class="tab-indicator"></div>
                        <button type="button" class="storage-tab active" role="tab" aria-selected="true">256GB</button>
                        <button type="button" class="storage-tab" role="tab" aria-selected="false">512GB</button>
                    </div>
                    <ul class="compare-list">
                        <li class="compare-item">
                            <button type="button" class="compare-item-button">
                                <div class="compare-item-content">
                                    <div class="compare-avatar compare-avatar-indigo">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                            <path d="M17.5 14.75C19.0188 14.75 20.25 15.9812 20.25 17.5C20.25 19.0188 19.0188 20.25 17.5 20.25C15.9812 20.25 14.75 19.0188 14.75 17.5C14.75 15.9812 15.9812 14.75 17.5 14.75Z" stroke="#516AEC" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                            <path d="M6.5 3.75C8.01878 3.75 9.25 4.98122 9.25 6.5C9.25 8.01878 8.01878 9.25 6.5 9.25C4.98122 9.25 3.75 8.01878 3.75 6.5C3.75 4.98122 4.98122 3.75 6.5 3.75Z" stroke="#516AEC" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                            <path d="M19 5L5 19" stroke="#516AEC" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </svg>
                                    </div>
                                    <span class="compare-item-name">모요 특가</span>
                                    <span class="compare-item-subtitle">요금제 월 85,000원</span>
                                    <span class="compare-item-price">105,000원</span>
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="compare-arrow">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M8.15146 20.8485C7.68283 20.3799 7.68283 19.6201 8.15146 19.1515L15.3029 12L8.15146 4.84852C7.68283 4.37989 7.68283 3.6201 8.15146 3.15147C8.62009 2.68284 9.37989 2.68284 9.84852 3.15147L17.8485 11.1515C18.3171 11.6201 18.3171 12.3799 17.8485 12.8485L9.84852 20.8485C9.37989 21.3172 8.62009 21.3172 8.15146 20.8485Z" fill="#868E96"></path>
                                    </svg>
                                </div>
                            </button>
                        </li>
                        <hr class="compare-divider">
                        <li class="compare-item">
                            <a href="https://link.coupang.com/a/c0gQy7" target="_blank" class="compare-item-link">
                                <div class="compare-item-content">
                                    <div class="compare-avatar compare-avatar-violet">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                            <path d="M17 21H7C6.20435 21 5.44129 20.6839 4.87868 20.1213C4.31607 19.5587 4 18.7956 4 18V8C4 7.46957 4.21071 6.96086 4.58579 6.58579C4.96086 6.21071 5.46957 6 6 6H18C18.5304 6 19.0391 6.21071 19.4142 6.58579C19.7893 6.96086 20 7.46957 20 8V18C20 18.7956 19.6839 19.5587 19.1213 20.1213C18.5587 20.6839 17.7957 21 17 21Z" fill="#CEBAF5"></path>
                                            <path d="M15 10C14.7348 10 14.4804 9.89464 14.2929 9.70711C14.1054 9.51957 14 9.26522 14 9V5C14 4.46957 13.7893 3.96086 13.4142 3.58579C13.0391 3.21071 12.5304 3 12 3C11.4696 3 10.9609 3.21071 10.5858 3.58579C10.2107 3.96086 10 4.46957 10 5V9C10 9.26522 9.89464 9.51957 9.70711 9.70711C9.51957 9.89464 9.26522 10 9 10C8.73478 10 8.48043 9.89464 8.29289 9.70711C8.10536 9.51957 8 9.26522 8 9V5C8 3.93913 8.42143 2.92172 9.17157 2.17157C9.92172 1.42143 10.9391 1 12 1C13.0609 1 14.0783 1.42143 14.8284 2.17157C15.5786 2.92172 16 3.93913 16 5V9C16 9.26522 15.8946 9.51957 15.7071 9.70711C15.5196 9.89464 15.2652 10 15 10Z" fill="#8754EB"></path>
                                        </svg>
                                    </div>
                                    <span class="compare-item-name">쿠팡</span>
                                    <span class="compare-item-price">1,062,600원</span>
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="compare-arrow">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M8.15146 20.8485C7.68283 20.3799 7.68283 19.6201 8.15146 19.1515L15.3029 12L8.15146 4.84852C7.68283 4.37989 7.68283 3.6201 8.15146 3.15147C8.62009 2.68284 9.37989 2.68284 9.84852 3.15147L17.8485 11.1515C18.3171 11.6201 18.3171 12.3799 17.8485 12.8485L9.84852 20.8485C9.37989 21.3172 8.62009 21.3172 8.15146 20.8485Z" fill="#868E96"></path>
                                    </svg>
                                </div>
                            </a>
                        </li>
                        <hr class="compare-divider">
                        <li class="compare-item">
                            <a href="/holyland-compare?skuCode=GALAXY_S25&skuStorage=STORAGE_256G" class="compare-item-link">
                                <div class="compare-item-content">
                                    <div class="compare-avatar compare-avatar-red">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M11.3154 22.7007L11.3124 22.6987L11.306 22.6945L11.2849 22.6806C11.2671 22.6689 11.2423 22.6524 11.2107 22.6312C11.1476 22.5887 11.0576 22.5273 10.9447 22.4481C10.719 22.2897 10.4007 22.0594 10.0205 21.7652C9.26225 21.1786 8.24754 20.3301 7.22829 19.2848C5.24585 17.2517 3 14.2247 3 10.7801C3 8.43955 3.95729 6.2025 5.64895 4.5589C7.33938 2.91649 9.62456 2 12 2C14.3754 2 16.6606 2.91649 18.3511 4.5589C20.0427 6.2025 21 8.43955 21 10.7801C21 14.2247 18.7542 17.2517 16.7717 19.2848C15.7525 20.3301 14.7378 21.1786 13.9795 21.7652C13.5993 22.0594 13.281 22.2897 13.0553 22.4481C12.9424 22.5273 12.8524 22.5887 12.7893 22.6312L12.7431 22.6621L12.7151 22.6806L12.694 22.6945L12.6876 22.6987L12.6846 22.7007C12.2685 22.9702 11.7315 22.9702 11.3154 22.7007ZM15 11C15 12.6569 13.6569 14 12 14C10.3431 14 9 12.6569 9 11C9 9.34315 10.3431 8 12 8C13.6569 8 15 9.34315 15 11Z" fill="#FA5252"></path>
                                        </svg>
                                    </div>
                                    <span class="compare-item-name">성지</span>
                                    <span class="compare-item-subtitle">요금제 월 110,000원</span>
                                    <span class="compare-item-price">평균 -133,333원</span>
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="compare-arrow">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M8.15146 20.8485C7.68283 20.3799 7.68283 19.6201 8.15146 19.1515L15.3029 12L8.15146 4.84852C7.68283 4.37989 7.68283 3.6201 8.15146 3.15147C8.62009 2.68284 9.37989 2.68284 9.84852 3.15147L17.8485 11.1515C18.3171 11.6201 18.3171 12.3799 17.8485 12.8485L9.84852 20.8485C9.37989 21.3172 8.62009 21.3172 8.15146 20.8485Z" fill="#868E96"></path>
                                    </svg>
                                </div>
                            </a>
                        </li>
                    </ul>
                    <p class="compare-disclaimer">파트너스 활동을 통해 일정액의 수수료를 제공받을 수 있습니다</p>
                </section>

                <!-- 배너 네비게이션 -->
                <div class="banner-navigation">
                    <div class="banner-nav-card">
                        <a href="/phones/stock-alarm" class="banner-nav-link">
                            <div class="banner-nav-content">
                                <span class="banner-nav-icon">
                                    <img src="https://assets.moyoplan.com/icon/ico-phone-category-stock-alarm.svg" alt="재고알리미" class="banner-icon-img">
                                </span>
                                <div class="banner-nav-text">
                                    <span class="banner-nav-title">재고알리미</span>
                                    <span class="banner-nav-desc">아이폰 17 재고 실시간 알림</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="banner-nav-card">
                        <a href="/phones/contents" class="banner-nav-link">
                            <div class="banner-nav-content">
                                <span class="banner-nav-icon">
                                    <img src="https://assets.moyoplan.com/icon/ico-phone-category-contents.svg" alt="읽을거리" class="banner-icon-img">
                                </span>
                                <div class="banner-nav-text">
                                    <span class="banner-nav-title">읽을거리</span>
                                    <span class="banner-nav-desc">다양한 휴대폰 관련 소식이에요</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 검색 필터 섹션 -->
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
    </div>
</main>

<?php
// 푸터 포함
include 'includes/footer.php';
?>

