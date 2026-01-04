<?php
/**
 * 메인 페이지 여부 자동 판단 (하단 메뉴 및 푸터 표시 제어용)
 * 
 * 사용법:
 * 1. 수동 설정: 각 페이지에서 $is_main_page = true/false 설정
 * 2. 자동 판단: 설정하지 않으면 파일명과 URL 기반으로 자동 판단
 * 3. 쿼리 파라미터 우선: 쿼리 파라미터가 있으면 무조건 서브페이지로 처리 (수동 설정보다 우선)
 */

// 세션 및 인증 함수를 가장 먼저 로드 (HTML 출력 전에 세션 시작)
require_once __DIR__ . '/data/auth-functions.php';
require_once __DIR__ . '/data/site-settings.php';

$siteSettings = getSiteSettings();
$site = $siteSettings['site'] ?? [];

// 쿼리 파라미터가 서브페이지인지 먼저 확인 (수동 설정보다 우선)
$query_string = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
$has_query_params = !empty($query_string);

// 서브페이지로 판단할 쿼리 파라미터 목록
$sub_page_params = [
    'id',          // plans.php?id=123
    'detail',      // plans.php?detail=123
    'view',        // plans.php?view=123
    'edit',        // plans.php?edit=123
    'category',    // plans.php?category=premium
    'type',        // plans.php?type=lte
    'provider',    // plans.php?provider=kt
];

$is_sub_page_by_query = false;
if ($has_query_params) {
    // 서브페이지 파라미터 목록에 있는지 확인
    foreach ($sub_page_params as $param) {
        if (isset($_GET[$param])) {
            $is_sub_page_by_query = true;
            break;
        }
    }
    
    // 서브페이지 파라미터가 아니지만 쿼리가 있는 경우도 체크
    if (!$is_sub_page_by_query && !empty($_GET)) {
        $excluded_params = ['page', 'sort', 'filter', 'search', 'tab'];
        foreach (array_keys($_GET) as $key) {
            if (!in_array($key, $excluded_params)) {
                $is_sub_page_by_query = true;
                break;
            }
        }
    }
}

// 쿼리 파라미터가 서브페이지면 무조건 서브페이지로 처리 (단, 명시적으로 설정된 경우는 제외)
if ($is_sub_page_by_query && !isset($is_main_page)) {
    $is_main_page = false;
}
// 수동 설정이 없으면 자동 판단
else if (!isset($is_main_page)) {
    // 현재 실행 중인 스크립트 파일 경로
    $current_script = $_SERVER['SCRIPT_NAME'];
    $current_file = basename($current_script);
    $current_path = dirname($current_script);
    
    // 메인 페이지 파일명 목록
    $main_page_files = [
        'index.php',
        'mvno.php',
        'mno.php',
        'internets.php',
        'event.php',
        'mypage.php'
    ];
    
    // 서브페이지 패턴 (파일명에 포함되면 서브페이지로 판단)
    $sub_page_patterns = [
        'detail',      // plan-detail.php, phone-detail.php 등
        '-detail',     // 하이픈 포함 detail
        '_detail',     // 언더스코어 포함 detail
        'view',        // view.php 등
        'edit',        // edit.php 등
        'create',      // create.php 등
        'form',        // form.php 등
    ];
    
    // 1. 파일명이 메인 페이지 목록에 있는지 확인
    if (in_array($current_file, $main_page_files)) {
        $is_main_page = true;
    }
    // 2. 파일명에 서브페이지 패턴이 포함되어 있는지 확인
    else {
        $is_sub_page = false;
        foreach ($sub_page_patterns as $pattern) {
            if (stripos($current_file, $pattern) !== false) {
                $is_sub_page = true;
                break;
            }
        }
        $is_main_page = !$is_sub_page;
    }
    
    // 3. URL 경로 기반 추가 판단 (서브 디렉토리 체크)
    $path_parts = explode('/', trim($current_path, '/'));
    if (count($path_parts) > 1) {
        // 서브 디렉토리가 있고, 파일명이 메인 페이지가 아니면 서브페이지로 간주
        if (!in_array($current_file, $main_page_files)) {
            $is_main_page = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(($site['name_ko'] ?? '유심킹') . ' - 알뜰폰 요금제'); ?></title>
    <link rel="stylesheet" href="/MVNO/assets/css/style.css">
    <?php if (isset($add_inline_css) && $add_inline_css): ?>
    <style>
        /* 이미지 및 컨테이너 스타일 - CSS 로드 전에도 적용되도록 head에 추가 */
        .css-174t92n.e82z5mt7 {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.75rem !important;
            margin: 1rem 0 !important;
            padding: 1rem !important;
            background-color: #f9fafb !important;
            border-radius: 0.5rem !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .css-12zfa6z.e82z5mt8 {
            display: flex !important;
            align-items: flex-start !important;
            gap: 0.5rem !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .css-xj5cz0.e82z5mt9 {
            max-width: 4rem !important;
            max-height: 4rem !important;
            object-fit: contain !important;
            flex-shrink: 0 !important;
            width: auto !important;
        }
    </style>
    <?php endif; ?>
    <script>
        // 메인 페이지 여부 (하단 메뉴 및 푸터 표시 제어용)
        window.IS_MAIN_PAGE = <?php echo (isset($is_main_page) && $is_main_page) ? 'true' : 'false'; ?>;
    </script>
    <script src="/MVNO/assets/js/modal.js" defer></script>
    <script src="/MVNO/assets/js/header-scroll.js" defer></script>
    <script src="/MVNO/assets/js/phone-deal-scroll.js" defer></script>
    <script src="/MVNO/assets/js/nav-click-fix.js" defer></script>
    <script src="/MVNO/assets/js/share.js" defer></script>
    <script src="/MVNO/assets/js/phone-consultation-modal.js" defer></script>
    <script src="/MVNO/assets/js/tagline-effects.js" defer></script>
</head>
<body>
    <header class="header sticky-nav" id="mainHeader">
        <div class="nav-wrapper">
            <!-- 모바일 로고 -->
            <div class="left-addon-mobile" id="mobileLogo">
                <a class="nav-logo" href="/MVNO/">
                    <span class="usimking-logo-full">
                        <span class="logo-text" style="display:inline-block; font-weight: 700; font-size: 18px; color: #6366f1;"><?php echo htmlspecialchars($site['name_ko'] ?? '유심킹'); ?></span>
                    </span>
                </a>
            </div>
            
            <!-- 데스크톱 로고 -->
            <div class="left-addon-desktop">
                <a class="nav-logo" href="/MVNO/">
                    <span class="usimking-logo-full">
                        <span class="logo-text" style="display:inline-block; font-weight: 700; font-size: 18px; color: #6366f1;"><?php echo htmlspecialchars($site['name_ko'] ?? '유심킹'); ?></span>
                    </span>
                </a>
            </div>
            
            <!-- 모바일 우측 추가 영역 -->
            <div class="right-addon-mobile">
            </div>
            
            <!-- 헤더 가운데 텍스트 -->
            <div class="header-center-text<?php 
                $currentPageTagline = getCurrentPageTagline($current_page ?? 'home');
                $taglineEffect = $currentPageTagline['effect'] ?? 'none';
                if ($taglineEffect && $taglineEffect !== 'none') {
                    echo ' tagline-effect-' . htmlspecialchars($taglineEffect);
                }
            ?>">
                <?php
                // 현재 페이지의 태그라인 가져오기
                $taglineText = $currentPageTagline['tagline'] ?? '';
                $taglineLink = $currentPageTagline['link'] ?? '';
                
                if (!empty($taglineText)):
                    if (!empty($taglineLink)):
                        // 링크가 있으면 링크로 표시
                        echo '<a href="' . htmlspecialchars($taglineLink) . '" style="text-decoration: none; color: inherit; display: inline-block;">';
                        echo htmlspecialchars($taglineText);
                        echo '</a>';
                    else:
                        // 링크가 없으면 텍스트만 표시
                        echo '<span>' . htmlspecialchars($taglineText) . '</span>';
                    endif;
                endif;
                ?>
            </div>
            
            <!-- 데스크톱 로그인 버튼 -->
            <div class="right-addon-desktop" style="display: flex; align-items: center;">
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
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'mno-sim') ? 'active' : ''; ?>" href="/MVNO/mno-sim/mno-sim.php">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5 4C5 2.89543 5.89543 2 7 2H17C18.1046 2 19 2.89543 19 4V20C19 21.1046 18.1046 22 17 22H7C5.89543 22 5 21.1046 5 20V4Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M9 8H15M9 12H15M9 16H12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <circle cx="12" cy="6" r="1.5" fill="currentColor"/>
                            </svg>
                            <span>통신사단독유심</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && ($current_page == 'plans' || $current_page == 'mvno')) ? 'active' : ''; ?>" href="/MVNO/mvno/mvno.php">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 12C2 8.22876 2 6.34315 3.17157 5.17157C4.34315 4 6.22876 4 10 4H14C17.7712 4 19.6569 4 20.8284 5.17157C22 6.34315 22 8.22876 22 12C22 15.7712 22 17.6569 20.8284 18.8284C19.6569 20 17.7712 20 14 20H10C6.22876 20 4.34315 20 3.17157 18.8284C2 17.6569 2 15.7712 2 12Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M7 8H17M7 12H17M7 16H12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>알뜰폰</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'mno') ? 'active' : ''; ?>" href="/MVNO/mno/mno.php">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5 4C5 2.89543 5.89543 2 7 2H17C18.1046 2 19 2.89543 19 4V20C19 21.1046 18.1046 22 17 22H7C5.89543 22 5 21.1046 5 20V4Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M12 18H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>통신사폰</span>
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
                    <li class="nav-item nav-item-desktop-only">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'event') ? 'active' : ''; ?>" href="/MVNO/event/event.php">이벤트</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'mypage') ? 'active' : ''; ?>" href="/MVNO/mypage/mypage.php" data-require-login="true">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>마이페이지</span>
                        </a>
                    </li>
                    <?php 
                    // auth-functions.php는 이미 파일 상단에서 로드됨
                    $isLoggedIn = isLoggedIn();
                    ?>
                    <li class="nav-item nav-item-desktop-only">
                        <?php if ($isLoggedIn): ?>
                            <a class="nav-link" href="/MVNO/api/logout.php">
                                <span>로그아웃</span>
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="#" onclick="if (typeof openLoginModal === 'function') { openLoginModal(false); } else { setTimeout(function() { if (typeof openLoginModal === 'function') { openLoginModal(false); } }, 100); } return false;">
                                <span>로그인</span>
                            </a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item nav-item-mobile-only">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page == 'wishlist') ? 'active' : ''; ?>" href="/MVNO/mypage/wishlist.php">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="#ef4444"/>
                            </svg>
                            <span>찜</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

<?php include __DIR__ . '/components/login-modal.php'; ?>

<script>
// 네비게이션 링크 로그인 체크
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-link[data-require-login="true"]');
    const isLoggedIn = <?php echo (isset($isLoggedIn) && $isLoggedIn) ? 'true' : 'false'; ?>;
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!isLoggedIn) {
                e.preventDefault();
                // 로그인 모달 열기 (로그인 모드) - openLoginModal 함수 내부에서 로그인 상태를 다시 체크함
                if (typeof openLoginModal === 'function') {
                    openLoginModal(false);
                } else {
                    // 모달이 아직 로드되지 않은 경우
                    setTimeout(() => {
                        if (typeof openLoginModal === 'function') {
                            openLoginModal(false);
                        } else {
                            window.location.href = '/MVNO/auth/login.php';
                        }
                    }, 100);
                }
            }
        });
    });
});

// 자동 로그인 함수
function autoLogin() {
    fetch('/MVNO/api/auto-login.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 로그인 성공 - 페이지 새로고침
                window.location.reload();
            } else {
                // 로그인 실패 시 로그인 모달 표시
                if (typeof openLoginModal === 'function') {
                    openLoginModal(false);
                } else {
                    alert('로그인에 실패했습니다.');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // 에러 발생 시 로그인 모달 표시
            if (typeof openLoginModal === 'function') {
                openLoginModal(false);
            } else {
                alert('로그인 중 오류가 발생했습니다.');
            }
        });
}
</script>

