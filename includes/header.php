<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>알뜰폰 요금제 모요(MOYO), 모두의 요금제</title>
    <link rel="stylesheet" href="/MVNO/assets/css/style.css">
    <script src="/MVNO/assets/js/header-scroll.js" defer></script>
    <script src="/MVNO/assets/js/phone-deal-scroll.js" defer></script>
    <script src="/MVNO/assets/js/nav-click-fix.js" defer></script>
</head>
<body>
    <header class="header sticky-nav" id="mainHeader">
        <div class="nav-wrapper">
            <!-- 모바일 로고 -->
            <div class="left-addon-mobile" id="mobileLogo">
                <a class="nav-logo" href="/MVNO/">
                    <span class="moyo-logo-full">
                        <img src="/MVNO/assets/images/logo/moyo-full.svg" alt="모요" width="130" height="30" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                        <span class="logo-text" style="display:none; font-weight: 700; font-size: 18px; color: #6366f1;">모요</span>
                    </span>
                    <div class="moyo-logo-icon-wrapper">
                        <span class="moyo-logo-icon">
                            <img src="/MVNO/assets/images/logo/moyo-icon.svg" alt="모요" width="40" height="40" onerror="this.style.display='none';">
                        </span>
                    </div>
                </a>
            </div>
            
            <!-- 데스크톱 로고 -->
            <div class="left-addon-desktop">
                <a class="nav-logo" href="/MVNO/">
                    <span class="moyo-logo-full">
                        <img src="/MVNO/assets/images/logo/moyo-full.svg" alt="모요" width="130" height="30" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                        <span class="logo-text" style="display:none; font-weight: 700; font-size: 18px; color: #6366f1;">모요</span>
                    </span>
                    <div class="moyo-logo-icon-wrapper">
                        <span class="moyo-logo-icon">
                            <img src="/MVNO/assets/images/logo/moyo-icon.svg" alt="모요" width="40" height="40" onerror="this.style.display='none';">
                        </span>
                    </div>
                </a>
            </div>
            
            <!-- 모바일 우측 추가 영역 -->
            <div class="right-addon-mobile"></div>
            
            <!-- 헤더 가운데 텍스트 -->
            <div class="header-center-text">
                <span>알뜰요금의 리더</span>
            </div>
            
            <!-- 네비게이션 메뉴 -->
            <nav class="nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'home') ? 'active' : ''; ?>" href="/MVNO/">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 12L5 10M5 10L12 3L19 10M5 10V20C5 20.5523 5.44772 21 6 21H9M19 10L21 12M19 10V20C19 20.5523 18.5523 21 18 21H15M9 21C9.55228 21 10 20.5523 10 20V16C10 15.4477 10.4477 15 11 15H13C13.5523 15 14 15.4477 14 16V20C14 20.5523 14.4477 21 15 21M9 21H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>홈</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'plans') ? 'active' : ''; ?>" href="/MVNO/plans/plans.php">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 12C2 8.22876 2 6.34315 3.17157 5.17157C4.34315 4 6.22876 4 10 4H14C17.7712 4 19.6569 4 20.8284 5.17157C22 6.34315 22 8.22876 22 12C22 15.7712 22 17.6569 20.8284 18.8284C19.6569 20 17.7712 20 14 20H10C6.22876 20 4.34315 20 3.17157 18.8284C2 17.6569 2 15.7712 2 12Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M7 8H17M7 12H17M7 16H12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>요금제</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'phones') ? 'active' : ''; ?>" href="/MVNO/phones/phones.php">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5 4C5 2.89543 5.89543 2 7 2H17C18.1046 2 19 2.89543 19 4V20C19 21.1046 18.1046 22 17 22H7C5.89543 22 5 21.1046 5 20V4Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M12 18H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>휴대폰</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'internets') ? 'active' : ''; ?>" href="/MVNO/internets/internets.php">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <!-- 지구 본체 -->
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                                <!-- 경도선 (세로 메리디안) 2개 -->
                                <path d="M3 12C3 8.5 5.5 6 9 6C12.5 6 15 8.5 15 12C15 15.5 12.5 18 9 18C5.5 18 3 15.5 3 12Z" stroke="currentColor" stroke-width="2" fill="none"/>
                                <path d="M21 12C21 8.5 18.5 6 15 6C11.5 6 9 8.5 9 12C9 15.5 11.5 18 15 18C18.5 18 21 15.5 21 12Z" stroke="currentColor" stroke-width="2" fill="none"/>
                                <!-- 위도선 (가로 패럴렐) 2개 -->
                                <ellipse cx="12" cy="8" rx="7" ry="2" stroke="currentColor" stroke-width="2" fill="none"/>
                                <ellipse cx="12" cy="16" rx="7" ry="2" stroke="currentColor" stroke-width="2" fill="none"/>
                            </svg>
                            <span>인터넷</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'esim') ? 'active' : ''; ?>" href="/MVNO/esim/esim.php">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <!-- 유심칩 본체 (왼쪽 상단 모서리 잘림) -->
                                <path d="M6 4H18C19.1046 4 20 4.89543 20 6V18C20 19.1046 19.1046 20 18 20H6C4.89543 20 4 19.1046 4 18V6C4 4.89543 4.89543 4 6 4Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M4 6V10H8V6C8 4.89543 7.10457 4 6 4C4.89543 4 4 4.89543 4 6Z" fill="currentColor"/>
                                <!-- 금속 접촉부 (3x3 그리드) -->
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
                            <span>해외eSIM</span>
                        </a>
                    </li>
                    <li class="nav-item nav-item-desktop-only">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'event') ? 'active' : ''; ?>" href="/MVNO/event/event.php">이벤트</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'mypage') ? 'active' : ''; ?>" href="/MVNO/mypage/mypage.php">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>마이페이지</span>
                        </a>
                    </li>
                    <li class="nav-item nav-item-mobile-only">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'wishlist') ? 'active' : ''; ?>" href="/MVNO/mypage/wishlist.php">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20.84 4.61C20.3292 4.099 19.7228 3.69364 19.0554 3.41708C18.3879 3.14052 17.6725 2.99817 16.95 2.99817C16.2275 2.99817 15.5121 3.14052 14.8446 3.41708C14.1772 3.69364 13.5708 4.099 13.06 4.61L12 5.67L10.94 4.61C9.9083 3.57831 8.50903 2.99871 7.05 2.99871C5.59096 2.99871 4.19169 3.57831 3.16 4.61C2.1283 5.64169 1.54871 7.04097 1.54871 8.5C1.54871 9.95903 2.1283 11.3583 3.16 12.39L4.22 13.45L12 21.23L19.78 13.45L20.84 12.39C21.351 11.8792 21.7564 11.2728 22.0329 10.6054C22.3095 9.93789 22.4518 9.22248 22.4518 8.5C22.4518 7.77752 22.3095 7.0621 22.0329 6.39464C21.7564 5.72718 21.351 5.12075 20.84 4.61Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>찜</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

