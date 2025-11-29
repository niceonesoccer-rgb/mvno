<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>알뜰폰 요금제 모요(MOYO), 모두의 요금제</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header sticky-nav">
        <div class="nav-wrapper">
            <!-- 모바일 로고 -->
            <div class="left-addon-mobile">
                <a class="nav-logo" href="index.php">
                    <span class="moyo-logo-full">
                        <img src="assets/images/logo/moyo-full.svg" alt="모요" width="130" height="30" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                        <span class="logo-text" style="display:none; font-weight: 700; font-size: 18px; color: #6366f1;">모요</span>
                    </span>
                    <div class="moyo-logo-icon-wrapper">
                        <span class="moyo-logo-icon">
                            <img src="assets/images/logo/moyo-icon.svg" alt="모요" width="40" height="40" onerror="this.style.display='none';">
                        </span>
                    </div>
                </a>
            </div>
            
            <!-- 데스크톱 로고 -->
            <div class="left-addon-desktop">
                <a class="nav-logo" href="index.php">
                    <span class="moyo-logo-full">
                        <img src="assets/images/logo/moyo-full.svg" alt="모요" width="130" height="30" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                        <span class="logo-text" style="display:none; font-weight: 700; font-size: 18px; color: #6366f1;">모요</span>
                    </span>
                    <div class="moyo-logo-icon-wrapper">
                        <span class="moyo-logo-icon">
                            <img src="assets/images/logo/moyo-icon.svg" alt="모요" width="40" height="40" onerror="this.style.display='none';">
                        </span>
                    </div>
                </a>
            </div>
            
            <!-- 모바일 우측 추가 영역 -->
            <div class="right-addon-mobile"></div>
            
            <!-- 네비게이션 메뉴 -->
            <nav class="nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'home') ? 'active' : ''; ?>" href="index.php">홈</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'plans') ? 'active' : ''; ?>" href="/plans">요금제</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'phones') ? 'active' : ''; ?>" href="/phones">자급제</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'internets') ? 'active' : ''; ?>" href="/internets">인터넷</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'esim') ? 'active' : ''; ?>" href="/esim">해외eSIM</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'event') ? 'active' : ''; ?>" href="/event">이벤트</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'mypage') ? 'active' : ''; ?>" href="/mypage">마이페이지</a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

