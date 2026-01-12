<?php
/**
 * 판매자 페이지 공통 헤더
 * 사이드바 네비게이션 포함
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/path-config.php';

// 판매자 인증 체크 (출력 전에 체크)
// seller-edit.php에서 이미 체크한 경우 스킵
// 전역 변수를 먼저 확인 (seller-edit.php에서 설정한 경우)
// $GLOBALS를 먼저 확인하고, 없으면 로컬 변수 확인
$skipAuthCheck = false;
// $GLOBALS를 먼저 확인 (seller-edit.php에서 설정한 경우)
if (isset($GLOBALS['skipAuthCheck']) && $GLOBALS['skipAuthCheck'] === true) {
    $skipAuthCheck = true;
}
// 로컬 변수도 확인 (seller-edit.php에서 include 전에 설정한 경우)
// 주의: $skipAuthCheck를 false로 초기화했으므로, 로컬 변수는 이미 false입니다.
// 따라서 로컬 변수 확인은 의미가 없으므로 제거합니다.

if ($skipAuthCheck === true) {
    // seller-edit.php에서 이미 체크했으므로 전달된 $currentUser 사용
    // $GLOBALS에서 먼저 확인
    if (isset($GLOBALS['sellerEditCurrentUser']) && is_array($GLOBALS['sellerEditCurrentUser']) && !empty($GLOBALS['sellerEditCurrentUser'])) {
        $currentUser = $GLOBALS['sellerEditCurrentUser'];
    } elseif (isset($currentUser) && is_array($currentUser) && !empty($currentUser)) {
        // 이미 설정된 경우 사용 (변경 없음)
    } else {
        // seller-edit.php에서 전달되지 않은 경우 getCurrentUser() 호출
        $currentUser = getCurrentUser();
    }
    // seller-edit.php에서 이미 승인 체크를 했으므로 추가 체크 불필요
    // $currentUser가 없으면 에러 방지를 위해 기본값 설정
    if (!isset($currentUser) || !is_array($currentUser) || empty($currentUser)) {
        // seller-edit.php에서 체크했는데 $currentUser가 없으면 문제가 있음
        // seller-edit.php에서 이미 체크했으므로 여기서는 리다이렉트하지 않고 null로 설정
        $currentUser = null;
    }
    // seller-edit.php에서 이미 인증 및 승인 체크를 완료했으므로 추가 체크 불필요
    // 판매자명 체크 (seller-edit.php에서 체크한 경우에도 판매자명 확인)
$sellerName = $currentUser['company_name'] ?? ($currentUser['seller_name'] ?? '');
    $hasSellerName = !empty(trim($sellerName));
    $showSellerNameModal = !$hasSellerName;
} else {
    // 일반적인 경우: 인증 체크 수행
    $currentUser = getCurrentUser();
    if (!$currentUser || $currentUser['role'] !== 'seller') {
        header('Location: ' . getAssetPath('/seller/login.php'));
        exit;
    }
    
    // 판매자 승인 상태 확인 (승인불가 상태도 waiting.php로 이동)
    $approvalStatus = $currentUser['approval_status'] ?? 'pending';
    if ($approvalStatus !== 'approved') {
        // 승인 대기 중이거나 보류, 거부 상태인 경우
        header('Location: ' . getAssetPath('/seller/waiting.php'));
        exit;
    }

    // 탈퇴 요청 상태 확인 (탈퇴 요청 시 로그인 불가)
    if (isset($currentUser['withdrawal_requested']) && $currentUser['withdrawal_requested'] === true) {
        header('Location: ' . getAssetPath('/seller/waiting.php'));
        exit;
    }
    
    // 판매자명 체크 (승인된 판매자만)
    $sellerName = $currentUser['company_name'] ?? ($currentUser['seller_name'] ?? '');
    $hasSellerName = !empty(trim($sellerName));
    $showSellerNameModal = !$hasSellerName;
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" id="viewportMeta" content="width=1400, initial-scale=0.5, minimum-scale=0.1, maximum-scale=5.0, user-scalable=yes">
    <title>판매자 센터 - 유심킹</title>
    <link rel="icon" type="image/png" href="<?php echo getAssetPath('/images/site/favicon.png'); ?>">
    <!-- 나눔스퀘어어라운드 웹폰트 (Regular & Bold) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/moonspam/NanumSquareRound@latest/nanumsquareround.min.css">
    <script>
    // 모바일에서 viewport 동적 조정
    (function() {
        function adjustViewport() {
            const viewport = document.getElementById('viewportMeta');
            if (!viewport) return;
            
            const isMobile = window.innerWidth <= 768;
            const deviceWidth = window.innerWidth || 375; // 기본값 375px
            
            if (isMobile) {
                // 모바일: 실제 컨텐츠 너비(1400px) 기준으로 설정
                // initial-scale 계산: 화면 너비 / 컨텐츠 너비 (최소 0.2 ~ 최대 1.0)
                const calculatedScale = Math.max(0.2, Math.min(1.0, deviceWidth / 1400));
                viewport.setAttribute('content', 'width=1400, initial-scale=' + calculatedScale + ', minimum-scale=0.1, maximum-scale=5.0, user-scalable=yes');
            } else {
                // PC: 일반 설정
                viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes');
            }
        }
        
        // 초기 로드 및 리사이즈 시 조정
        adjustViewport();
        window.addEventListener('resize', adjustViewport);
        window.addEventListener('orientationchange', function() {
            setTimeout(adjustViewport, 100);
        });
    })();
    </script>
    <link rel="stylesheet" href="<?php echo getAssetPath('/assets/css/style.css'); ?>">
    <script src="<?php echo getAssetPath('/assets/js/modal.js'); ?>" defer></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            overflow-x: auto;
            overflow-y: scroll;
            min-width: 1400px; /* 모바일에서도 최소 너비 유지 */
        }
        
        body {
            font-family: 'NanumSquareRound', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch; /* iOS에서 부드러운 스크롤 */
            min-width: 1400px; /* 모바일에서도 최소 너비 유지 */
        }
        
        /* 헤더 (네비게이션 포함 1단 구조) */
        .seller-top-header {
            width: 100%;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0;
            display: flex;
<<<<<<< HEAD
            align-items: flex-start; /* 서브메뉴가 나올 수 있도록 */
=======
            align-items: center; /* 헤더 내용 세로 가운데 정렬 */
>>>>>>> 955e643 (판매자 완료)
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000; /* 헤더는 항상 위에 */
            height: 64px;
            min-height: 64px; /* 최소 높이 */
<<<<<<< HEAD
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
=======
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
>>>>>>> 955e643 (판매자 완료)
            min-width: 1400px;
            overflow: visible !important; /* 서브메뉴가 헤더 밖으로 나올 수 있도록 강제 */
        }
        
        /* 헤더 왼쪽 영역 (로고, 햄버거 메뉴) */
        .seller-top-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 0 24px;
            flex-shrink: 0;
        }
        
        /* 헤더 오른쪽 영역 (사용자 정보, 링크) */
        .seller-top-header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 24px;
            flex-shrink: 0;
            margin-left: auto;
        }
        
        /* 상단 네비게이션 바 (헤더 내부) */
        .seller-nav-bar {
            flex: 1;
            display: flex;
            align-items: center; /* 세로 가운데 정렬 */
            justify-content: center; /* 가로 가운데 정렬 */
            height: 100%;
            overflow: visible !important; /* 서브메뉴가 나올 수 있도록 강제 */
            background: transparent;
        }
        
        .seller-nav-container {
            display: flex;
            align-items: center; /* 세로 가운데 정렬 */
            justify-content: center; /* 가로 가운데 정렬 */
            padding: 0 12px;
            max-width: 100%;
            overflow: visible !important; /* 서브메뉴가 나올 수 있도록 강제 */
            height: 100%;
            /* 가로 스크롤은 필요시 JavaScript로 처리 */
        }
        
        /* 가로 스크롤이 필요한 경우를 위한 스타일 (필요시 사용) */
        .seller-nav-container-wrapper {
            overflow-x: auto;
            overflow-y: visible !important;
        }
        
        .seller-nav-container-wrapper::-webkit-scrollbar {
            height: 4px;
        }
        
        .seller-nav-container-wrapper::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .seller-nav-container-wrapper::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
        }
        
        .nav-category {
            position: relative; /* absolute의 기준점 */
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center; /* 가운데 정렬 */
            overflow: visible !important; /* 서브메뉴가 나올 수 있도록 강제 */
        }
        
        /* 호버 시 z-index 증가하여 서브메뉴가 헤더 위에 표시 */
        .nav-category:hover {
            z-index: 1001;
        }
        
        .nav-category-btn {
            display: flex;
            align-items: center;
            justify-content: center; /* 가운데 정렬 */
            padding: 0 20px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 16px; /* 글자 크기 증가 (14px -> 16px) */
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.3s ease;
            border: none;
            background: none;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            height: 100%;
            text-align: center; /* 텍스트 가운데 정렬 */
        }
        
        .nav-category-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-bottom-color: rgba(255, 255, 255, 0.6);
        }
        
        .nav-category-btn.active {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border-bottom-color: #ffffff;
        }
        
        .nav-category-btn.has-submenu::after {
            content: '';
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid rgba(255, 255, 255, 0.6);
            margin-left: 8px;
            transition: transform 0.3s ease;
        }
        
        .nav-category:hover .nav-category-btn.has-submenu::after,
        .nav-category-btn.active.has-submenu::after {
            transform: rotate(180deg);
        }
        
        .nav-submenu {
            position: absolute; /* 부모(.nav-category) 기준으로 배치 */
            top: 100%; /* 카테고리 버튼 바로 아래 */
            left: 0; /* 부모의 왼쪽 끝부터 */
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            min-width: 200px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            border-top: 2px solid rgba(255, 255, 255, 0.3);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
                        transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                        visibility 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                        max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1001; /* 헤더(1000)보다 높게, 본문(1)보다 높게 - 본문 위에 떠있음 */
            max-height: 0;
            overflow: hidden;
            pointer-events: none;
            padding-top: 0;
            margin-top: 0;
        }
        
        /* 호버 시 서브메뉴 표시 */
        .nav-category:hover .nav-submenu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            max-height: 500px;
            pointer-events: auto;
        }
        
        /* active 클래스로 열린 서브메뉴도 표시 (호버가 아닐 때도) - 단, 호버 중이 아닐 때만 */
        .nav-category.active:not(.closing):not(:hover) .nav-submenu {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        
        /* active이면서 호버 중일 때만 표시 */
        .nav-category.active:not(.closing):hover .nav-submenu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            max-height: 500px;
            pointer-events: auto;
        }
        
        /* closing 클래스가 있으면 서브메뉴 숨김 (우선순위 높음) */
        .nav-category.closing .nav-submenu,
        .nav-category.closing:hover .nav-submenu {
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }
        
        /* 호버 시 카테고리 버튼 스타일 */
        .nav-category:hover .nav-category-btn {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border-bottom-color: rgba(255, 255, 255, 0.8);
        }
        
        .nav-category:hover .nav-category-btn.has-submenu::after,
        .nav-category.active .nav-category-btn.has-submenu::after {
            transform: rotate(180deg);
        }
        
        /* active 상태일 때도 버튼 스타일 적용 */
        .nav-category.active .nav-category-btn {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border-bottom-color: rgba(255, 255, 255, 0.8);
        }
        
        .nav-submenu-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-submenu-item:hover {
            background: rgba(99, 102, 241, 0.15);
            color: #ffffff;
            border-left-color: #6366f1;
            padding-left: 24px;
        }
        
        .nav-submenu-item.active {
            background: rgba(99, 102, 241, 0.25);
            color: #ffffff;
            border-left-color: #6366f1;
            font-weight: 600;
        }
        
        .nav-submenu-item-icon {
            width: 18px;
            height: 18px;
            margin-right: 10px;
            flex-shrink: 0;
        }
        
        .nav-submenu-item-icon svg {
            width: 100%;
            height: 100%;
            stroke: currentColor;
            stroke-width: 2;
        }
        
        @media (max-width: 768px) {
            .seller-top-header-left {
                gap: 12px;
            }
        }
        
        /* 햄버거 메뉴 버튼 - 데스크톱에서도 표시 (모바일 메뉴용) */
        .mobile-menu-toggle {
            display: block;
            background: none;
            border: none;
            color: #ffffff;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: background 0.2s;
            flex-shrink: 0;
        }
        
        .mobile-menu-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .mobile-menu-toggle:active {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .seller-top-header-logo {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            text-decoration: none;
            letter-spacing: -0.5px;
            transition: opacity 0.2s;
            white-space: nowrap;
        }
        
        @media (max-width: 768px) {
            .seller-top-header-logo {
                font-size: 18px;
            }
        }
        
        .seller-top-header-logo:hover {
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .seller-top-header-right {
                display: none; /* 모바일에서 숨김 */
            }
            
            .seller-nav-bar {
                display: none; /* 모바일에서 네비게이션 바 숨김 */
            }
        }
        
        .seller-info {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.95);
            font-weight: 600;
            padding: 8px 16px;
            border-right: 1px solid rgba(255, 255, 255, 0.3);
            margin-right: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 8px;
            cursor: pointer;
            white-space: nowrap;
        }
        
        @media (max-width: 768px) {
            .seller-info {
                font-size: 13px;
                padding: 6px 12px;
                margin-right: 4px;
                border-right: none;
            }
        }
        
        .seller-info:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            transform: translateY(-1px);
        }
        
        .seller-top-header-link {
            font-size: 15px;
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        @media (max-width: 768px) {
            .seller-top-header-link {
                font-size: 13px;
                padding: 6px 12px;
            }
        }
        
        .seller-top-header-link:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }
        
        /* 모바일 네비게이션 - 데스크톱에서도 사용 가능 */
        .mobile-nav-menu {
            position: fixed;
            top: 64px;
<<<<<<< HEAD
            right: 0;
=======
            left: 0;
>>>>>>> 955e643 (판매자 완료)
            width: 320px;
            max-width: 85vw;
            height: calc(100vh - 64px);
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
<<<<<<< HEAD
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 998;
            overflow-y: auto;
            box-shadow: -4px 0 16px rgba(0, 0, 0, 0.3);
=======
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 998;
            overflow-y: auto;
            box-shadow: 4px 0 16px rgba(0, 0, 0, 0.3);
>>>>>>> 955e643 (판매자 완료)
        }
        
        .mobile-nav-menu.open {
            transform: translateX(0);
        }
        
        .mobile-nav-menu::-webkit-scrollbar {
            width: 6px;
        }
        
        .mobile-nav-menu::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .mobile-nav-menu::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .mobile-nav-menu::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
            
            .mobile-nav-category {
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .mobile-nav-category-btn {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                padding: 16px 20px;
                color: rgba(255, 255, 255, 0.85);
                text-decoration: none;
                font-size: 15px;
                font-weight: 600;
                background: none;
                border: none;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .mobile-nav-category-btn:hover {
                background: rgba(255, 255, 255, 0.1);
                color: #ffffff;
            }
            
            .mobile-nav-category-btn::after {
                content: '';
                width: 0;
                height: 0;
                border-left: 5px solid transparent;
                border-right: 5px solid transparent;
                border-top: 5px solid rgba(255, 255, 255, 0.6);
                transition: transform 0.3s ease;
            }
            
            .mobile-nav-category.active .mobile-nav-category-btn::after {
                transform: rotate(180deg);
            }
            
            .mobile-nav-submenu {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
            }
            
            .mobile-nav-category.active .mobile-nav-submenu {
                max-height: 1000px;
            }
            
            .mobile-nav-submenu-item {
                display: flex;
                align-items: center;
                padding: 12px 20px 12px 40px;
                color: rgba(255, 255, 255, 0.75);
                text-decoration: none;
                font-size: 14px;
                font-weight: 500;
                transition: all 0.2s ease;
            }
            
            .mobile-nav-submenu-item:hover {
                background: rgba(99, 102, 241, 0.15);
                color: #ffffff;
            }
            
            .mobile-nav-submenu-item.active {
                background: rgba(99, 102, 241, 0.2);
                color: #ffffff;
                font-weight: 600;
            }
        }
        
        /* 기존 사이드바 스타일 제거됨 - 상단 네비게이션으로 대체 */
        
        /* 메인 컨텐츠 */
        .seller-content-wrapper {
            margin-top: 64px !important; /* 헤더 높이만큼만 */
            padding-top: 0 !important;
            min-height: calc(100vh - 64px);
            padding: 0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            /* 스크롤바 숨김 */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
            min-width: 1400px; /* 모바일에서도 최소 너비 유지 */
            position: relative;
            z-index: 1; /* 낮은 z-index - 서브메뉴(1001)가 위에 떠있음 */
        }
        
        /* 헤더와 본문 겹침 방지 */
        body {
            padding-top: 0 !important;
        }
        
        .seller-content {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
        
        /* 스크롤바 숨김 (Webkit 브라우저) */
        .seller-content-wrapper::-webkit-scrollbar {
            display: none;
        }
        
        @media (max-width: 768px) {
            .seller-content-wrapper {
                margin-top: 64px !important; /* 모바일에서도 헤더 높이만큼만 */
                width: 100%;
                min-width: 1400px; /* 모바일에서도 전체 너비 유지 */
                /* 모바일에서 확대/축소와 패닝 허용 */
                touch-action: pan-x pan-y pinch-zoom;
            }
        }
        
        .seller-content {
            padding: 32px;
            min-width: 1000px;
        }
        
        @media (max-width: 768px) {
            .seller-content {
                padding: 16px;
                /* 모바일에서 터치 제스처 허용 */
                touch-action: pan-x pan-y pinch-zoom;
            }
        }
        
        /* 판매자명 입력 모달 */
        .seller-name-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .seller-name-modal {
            background: white;
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .seller-name-modal-header {
            padding: 24px 24px 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .seller-name-modal-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }
        
        .seller-name-modal-body {
            padding: 24px;
        }
        
        .seller-name-modal-description {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .seller-name-modal-description strong {
            color: #1f2937;
            font-weight: 600;
        }
        
        .seller-name-form-group {
            margin-bottom: 0;
        }
        
        .seller-name-form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .seller-name-form-label .required {
            color: #ef4444;
            margin-left: 4px;
        }
        
        .seller-name-form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .seller-name-form-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .seller-name-form-input.checked-valid {
            border-color: #10b981;
        }
        
        .seller-name-form-input.checked-invalid {
            border-color: #ef4444;
        }
        
        .seller-name-form-help {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: #9ca3af;
        }
        
        .seller-name-modal-footer {
            padding: 16px 24px 24px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
        }
        
        .seller-name-modal-btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .seller-name-modal-btn-primary {
            background: #6366f1;
            color: white;
        }
        
        .seller-name-modal-btn-primary:hover {
            background: #4f46e5;
        }
        
        .seller-name-modal-btn-primary:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        
        .check-result {
            font-size: 13px;
            font-weight: 500;
            min-height: 20px;
        }
        
        .check-result.success {
            color: #10b981;
        }
        
        .check-result.error {
            color: #ef4444;
        }
        
        .check-result.checking {
            color: #6b7280;
        }
        
        /* 테이블 컨테이너 가로 스크롤 지원 */
        .table-container,
        table {
            min-width: 1000px;
        }
        
        @media (max-width: 768px) {
            .table-container,
            table {
                /* 모바일에서 터치 제스처 허용 */
                touch-action: pan-x pan-y pinch-zoom;
            }
        }
        
        /* 그리드 레이아웃 가로 스크롤 지원 */
        .dashboard-grid,
        .grid-container {
            min-width: 1000px;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid,
            .grid-container {
                /* 모바일에서 터치 제스처 허용 */
                touch-action: pan-x pan-y pinch-zoom;
            }
        }
        
        /* body와 html에서도 모바일 확대/축소 허용 */
        @media (max-width: 768px) {
            html, body {
                /* 모바일에서 확대/축소와 패닝 허용 */
                touch-action: pan-x pan-y pinch-zoom;
                /* iOS에서 확대/축소 최적화 */
                -webkit-text-size-adjust: 100%;
                /* 모바일에서도 가로 스크롤 가능 */
                overflow-x: auto;
                overflow-y: auto;
                /* 스크롤바 숨김 (스크롤은 가능) */
                scrollbar-width: none; /* Firefox */
                -ms-overflow-style: none; /* IE/Edge */
            }
            
            html::-webkit-scrollbar, body::-webkit-scrollbar {
                display: none; /* Chrome, Safari, Opera */
            }
        }
    </style>
    <?php if (isset($pageStyles) && !empty($pageStyles)): ?>
    <style>
        <?php echo $pageStyles; ?>
    </style>
    <?php endif; ?>
</head>
<body>
    <!-- 상단 헤더 (1단 구조: 로고 + 네비게이션 + 사용자정보) -->
    <header class="seller-top-header">
        <div class="seller-top-header-left">
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="메뉴 열기">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <a href="<?php echo getAssetPath('/seller/'); ?>" class="seller-top-header-logo">판매자 센터</a>
        </div>
        
        <!-- 상단 네비게이션 바 (헤더 내부) -->
        <nav class="seller-nav-bar" id="sellerNavBar">
        <div class="seller-nav-container">
            <!-- 대시보드 -->
            <div class="nav-category">
                <a href="<?php echo getAssetPath('/seller/'); ?>" class="nav-category-btn <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                    대시보드
                </a>
            </div>
            
            <!-- 광고 관리 -->
            <div class="nav-category">
                <button class="nav-category-btn has-submenu <?php echo ($currentPage === 'list.php' && strpos($_SERVER['REQUEST_URI'], '/advertisement/') !== false) ? 'active' : ''; ?>" data-category="advertisement">
                    광고 관리
                </button>
                <div class="nav-submenu">
                    <a href="<?php echo getAssetPath('/seller/advertisement/list.php'); ?>" class="nav-submenu-item <?php echo ($currentPage === 'list.php' && strpos($_SERVER['REQUEST_URI'], '/advertisement/') !== false) ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </span>
                        광고 내역
                    </a>
                </div>
            </div>
            
            <!-- 예치금 관리 -->
            <div class="nav-category">
                <button class="nav-category-btn has-submenu <?php echo (strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>" data-category="deposit">
                    예치금 관리
                </button>
                <div class="nav-submenu">
                    <a href="<?php echo getAssetPath('/seller/deposit/charge.php'); ?>" class="nav-submenu-item <?php echo ($currentPage === 'charge.php' && strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="2" x2="12" y2="22"/>
                                <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                            </svg>
                        </span>
                        예치금 충전
                    </a>
                    <a href="<?php echo getAssetPath('/seller/deposit/history.php'); ?>" class="nav-submenu-item <?php echo ($currentPage === 'history.php' && strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </span>
                        예치금 내역
                    </a>
                </div>
            </div>
            
            <!-- 주문 관리 -->
            <div class="nav-category">
                <button class="nav-category-btn has-submenu <?php echo (strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>" data-category="orders">
                    주문 관리
                </button>
                <div class="nav-submenu">
                    <a href="<?php echo getAssetPath('/seller/orders/mno-sim.php'); ?>" class="nav-submenu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'mno-sim.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                <line x1="12" y1="18" x2="12.01" y2="18"/>
                            </svg>
                        </span>
                        통신사단독유심
                    </a>
                    <a href="<?php echo getAssetPath('/seller/orders/mvno.php'); ?>" class="nav-submenu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'mvno.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                <line x1="12" y1="18" x2="12.01" y2="18"/>
                            </svg>
                        </span>
                        알뜰폰
                    </a>
                    <a href="<?php echo getAssetPath('/seller/orders/mno.php'); ?>" class="nav-submenu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'mno.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                <line x1="12" y1="18" x2="12.01" y2="18"/>
                            </svg>
                        </span>
                        통신사폰
                    </a>
                    <a href="<?php echo getAssetPath('/seller/orders/internet.php'); ?>" class="nav-submenu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'internet.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="2" y1="12" x2="22" y2="12"/>
                                <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                            </svg>
                        </span>
                        인터넷
                    </a>
                </div>
            </div>
            
            <!-- 상품 관리 -->
            <div class="nav-category">
                <button class="nav-category-btn has-submenu <?php echo (strpos($_SERVER['REQUEST_URI'], '/products/') !== false && strpos($_SERVER['REQUEST_URI'], '/orders/') === false && (strpos($currentPage, 'list.php') !== false || strpos($currentPage, '-list.php') !== false)) ? 'active' : ''; ?>" data-category="products">
                    상품 관리
                </button>
                <div class="nav-submenu">
                    <a href="<?php echo getAssetPath('/seller/products/mno-sim-list.php'); ?>" class="nav-submenu-item <?php echo $currentPage === 'mno-sim-list.php' ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                <line x1="12" y1="18" x2="12.01" y2="18"/>
                            </svg>
                        </span>
                        통신사단독유심
                    </a>
                    <a href="<?php echo getAssetPath('/seller/products/mvno-list.php'); ?>" class="nav-submenu-item <?php echo $currentPage === 'mvno-list.php' ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                <line x1="12" y1="18" x2="12.01" y2="18"/>
                            </svg>
                        </span>
                        알뜰폰
                    </a>
                    <a href="<?php echo getAssetPath('/seller/products/mno-list.php'); ?>" class="nav-submenu-item <?php echo $currentPage === 'mno-list.php' ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                <line x1="12" y1="18" x2="12.01" y2="18"/>
                            </svg>
                        </span>
                        통신사폰
                    </a>
                    <a href="<?php echo getAssetPath('/seller/products/internet-list.php'); ?>" class="nav-submenu-item <?php echo $currentPage === 'internet-list.php' ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="2" y1="12" x2="22" y2="12"/>
                                <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                            </svg>
                        </span>
                        인터넷
                    </a>
                </div>
            </div>
            
            <!-- 상품 등록 -->
            <div class="nav-category">
                <button class="nav-category-btn has-submenu <?php echo (strpos($_SERVER['REQUEST_URI'], '/products/') !== false && strpos($_SERVER['REQUEST_URI'], '/orders/') === false && strpos($currentPage, 'list.php') === false && strpos($currentPage, '-list.php') === false && in_array($currentPage, ['mno-sim.php', 'mvno.php', 'mno.php', 'internet.php'])) ? 'active' : ''; ?>" data-category="register">
                    상품 등록
                </button>
                <div class="nav-submenu">
                    <a href="<?php echo getAssetPath('/seller/products/mno-sim.php'); ?>" class="nav-submenu-item <?php echo ($currentPage === 'mno-sim.php' && strpos($_SERVER['REQUEST_URI'], '/products/') !== false && strpos($_SERVER['REQUEST_URI'], '/orders/') === false) ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                <line x1="12" y1="18" x2="12.01" y2="18"/>
                            </svg>
                        </span>
                        통신사단독유심
                    </a>
                    <a href="<?php echo getAssetPath('/seller/products/mvno.php'); ?>" class="nav-submenu-item <?php echo ($currentPage === 'mvno.php' && strpos($_SERVER['REQUEST_URI'], '/products/') !== false && strpos($_SERVER['REQUEST_URI'], '/orders/') === false) ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                <line x1="12" y1="18" x2="12.01" y2="18"/>
                            </svg>
                        </span>
                        알뜰폰
                    </a>
                    <a href="<?php echo getAssetPath('/seller/products/mno.php'); ?>" class="nav-submenu-item <?php echo ($currentPage === 'mno.php' && strpos($_SERVER['REQUEST_URI'], '/products/') !== false && strpos($_SERVER['REQUEST_URI'], '/orders/') === false) ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                <line x1="12" y1="18" x2="12.01" y2="18"/>
                            </svg>
                        </span>
                        통신사폰
                    </a>
                    <a href="<?php echo getAssetPath('/seller/products/internet.php'); ?>" class="nav-submenu-item <?php echo ($currentPage === 'internet.php' && strpos($_SERVER['REQUEST_URI'], '/products/') !== false && strpos($_SERVER['REQUEST_URI'], '/orders/') === false) ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="2" y1="12" x2="22" y2="12"/>
                                <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                            </svg>
                        </span>
                        인터넷
                    </a>
                </div>
            </div>
            
            <!-- 고객 지원 -->
            <div class="nav-category">
                <button class="nav-category-btn has-submenu <?php echo (strpos($_SERVER['REQUEST_URI'], '/inquiry/') !== false || strpos($_SERVER['REQUEST_URI'], '/notice/') !== false) ? 'active' : ''; ?>" data-category="support">
                    고객 지원
                </button>
                <div class="nav-submenu">
                    <a href="<?php echo getAssetPath('/seller/inquiry/inquiry-list.php'); ?>" class="nav-submenu-item <?php echo (strpos($currentPage, 'inquiry') !== false) ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                <path d="M13 8H7"/>
                                <path d="M17 12H7"/>
                            </svg>
                        </span>
                        1:1 문의
                    </a>
                    <a href="<?php echo getAssetPath('/seller/notice/'); ?>" class="nav-submenu-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/seller/notice/') !== false) ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                            </svg>
                        </span>
                        공지사항
                    </a>
                </div>
            </div>
            
            <!-- 통계 -->
            <div class="nav-category">
                <a href="<?php echo getAssetPath('/seller/statistics/'); ?>" class="nav-category-btn <?php echo ($currentPage === 'statistics' || strpos($_SERVER['REQUEST_URI'] ?? '', '/seller/statistics/') !== false) ? 'active' : ''; ?>">
                    통계
                </a>
            </div>
            
            <!-- 계정 -->
            <div class="nav-category">
                <button class="nav-category-btn has-submenu <?php echo ($currentPage === 'profile.php') ? 'active' : ''; ?>" data-category="account">
                    계정
                </button>
                <div class="nav-submenu">
                    <a href="<?php echo getAssetPath('/seller/profile.php'); ?>" class="nav-submenu-item <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                        <span class="nav-submenu-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        내정보
                    </a>
                </div>
            </div>
        </div>
        </nav>
        
        <div class="seller-top-header-right">
            <?php if ($currentUser && isset($currentUser['user_id']) && isset($currentUser['company_name'])): ?>
                <a href="<?php echo getAssetPath('/seller/profile.php'); ?>" class="seller-info">
                    <?php echo htmlspecialchars($currentUser['company_name']); ?> (<?php echo htmlspecialchars($currentUser['user_id']); ?>)
                </a>
            <?php endif; ?>
            <a href="<?php echo getAssetPath('/'); ?>" class="seller-top-header-link">사이트보기</a>
            <a href="<?php echo getAssetPath('/seller/logout.php'); ?>" class="seller-top-header-link">로그아웃</a>
        </div>
    </header>
    
    <!-- 모바일 네비게이션 메뉴 -->
    <div class="mobile-nav-menu" id="mobileNavMenu">
        <!-- 대시보드 -->
        <div class="mobile-nav-category">
            <a href="<?php echo getAssetPath('/seller/'); ?>" class="mobile-nav-category-btn">
                대시보드
            </a>
        </div>
        
        <!-- 광고 관리 -->
        <div class="mobile-nav-category">
            <button class="mobile-nav-category-btn" data-category="advertisement">
                광고 관리
            </button>
            <div class="mobile-nav-submenu">
                <a href="<?php echo getAssetPath('/seller/advertisement/list.php'); ?>" class="mobile-nav-submenu-item <?php echo ($currentPage === 'list.php' && strpos($_SERVER['REQUEST_URI'], '/advertisement/') !== false) ? 'active' : ''; ?>">
                    광고 내역
                </a>
            </div>
        </div>
        
        <!-- 예치금 관리 -->
        <div class="mobile-nav-category">
            <button class="mobile-nav-category-btn" data-category="deposit">
                예치금 관리
            </button>
            <div class="mobile-nav-submenu">
                <a href="<?php echo getAssetPath('/seller/deposit/charge.php'); ?>" class="mobile-nav-submenu-item <?php echo ($currentPage === 'charge.php' && strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>">
                    예치금 충전
                </a>
                <a href="<?php echo getAssetPath('/seller/deposit/history.php'); ?>" class="mobile-nav-submenu-item <?php echo ($currentPage === 'history.php' && strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>">
                    예치금 내역
                </a>
            </div>
        </div>
        
        <!-- 주문 관리 -->
        <div class="mobile-nav-category">
            <button class="mobile-nav-category-btn" data-category="orders">
                주문 관리
            </button>
            <div class="mobile-nav-submenu">
                <a href="<?php echo getAssetPath('/seller/orders/mno-sim.php'); ?>" class="mobile-nav-submenu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'mno-sim.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                    통신사단독유심
                </a>
                <a href="<?php echo getAssetPath('/seller/orders/mvno.php'); ?>" class="mobile-nav-submenu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'mvno.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                    알뜰폰
                </a>
                <a href="<?php echo getAssetPath('/seller/orders/mno.php'); ?>" class="mobile-nav-submenu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'mno.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                    통신사폰
                </a>
                <a href="<?php echo getAssetPath('/seller/orders/internet.php'); ?>" class="mobile-nav-submenu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'internet.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                    인터넷
                </a>
            </div>
        </div>
        
        <!-- 상품 관리 -->
        <div class="mobile-nav-category">
            <button class="mobile-nav-category-btn" data-category="products">
                상품 관리
            </button>
            <div class="mobile-nav-submenu">
                <a href="<?php echo getAssetPath('/seller/products/mno-sim-list.php'); ?>" class="mobile-nav-submenu-item <?php echo $currentPage === 'mno-sim-list.php' ? 'active' : ''; ?>">
                    통신사단독유심
                </a>
                <a href="<?php echo getAssetPath('/seller/products/mvno-list.php'); ?>" class="mobile-nav-submenu-item <?php echo $currentPage === 'mvno-list.php' ? 'active' : ''; ?>">
                    알뜰폰
                </a>
                <a href="<?php echo getAssetPath('/seller/products/mno-list.php'); ?>" class="mobile-nav-submenu-item <?php echo $currentPage === 'mno-list.php' ? 'active' : ''; ?>">
                    통신사폰
                </a>
                <a href="<?php echo getAssetPath('/seller/products/internet-list.php'); ?>" class="mobile-nav-submenu-item <?php echo $currentPage === 'internet-list.php' ? 'active' : ''; ?>">
                    인터넷
                </a>
            </div>
        </div>
        
        <!-- 상품 등록 -->
        <div class="mobile-nav-category">
            <button class="mobile-nav-category-btn" data-category="register">
                상품 등록
            </button>
            <div class="mobile-nav-submenu">
                <a href="<?php echo getAssetPath('/seller/products/mno-sim.php'); ?>" class="mobile-nav-submenu-item <?php echo ($currentPage === 'mno-sim.php' && strpos($_SERVER['REQUEST_URI'], '/products/') !== false && strpos($_SERVER['REQUEST_URI'], '/orders/') === false) ? 'active' : ''; ?>">
                    통신사단독유심
                </a>
                <a href="<?php echo getAssetPath('/seller/products/mvno.php'); ?>" class="mobile-nav-submenu-item <?php echo ($currentPage === 'mvno.php' && strpos($_SERVER['REQUEST_URI'], '/products/') !== false && strpos($_SERVER['REQUEST_URI'], '/orders/') === false) ? 'active' : ''; ?>">
                    알뜰폰
                </a>
                <a href="<?php echo getAssetPath('/seller/products/mno.php'); ?>" class="mobile-nav-submenu-item <?php echo ($currentPage === 'mno.php' && strpos($_SERVER['REQUEST_URI'], '/products/') !== false && strpos($_SERVER['REQUEST_URI'], '/orders/') === false) ? 'active' : ''; ?>">
                    통신사폰
                </a>
                <a href="<?php echo getAssetPath('/seller/products/internet.php'); ?>" class="mobile-nav-submenu-item <?php echo ($currentPage === 'internet.php' && strpos($_SERVER['REQUEST_URI'], '/products/') !== false && strpos($_SERVER['REQUEST_URI'], '/orders/') === false) ? 'active' : ''; ?>">
                    인터넷
                </a>
            </div>
        </div>
        
        <!-- 고객 지원 -->
        <div class="mobile-nav-category">
            <button class="mobile-nav-category-btn" data-category="support">
                고객 지원
            </button>
            <div class="mobile-nav-submenu">
                <a href="<?php echo getAssetPath('/seller/inquiry/inquiry-list.php'); ?>" class="mobile-nav-submenu-item <?php echo (strpos($currentPage, 'inquiry') !== false) ? 'active' : ''; ?>">
                    1:1 문의
                </a>
                <a href="<?php echo getAssetPath('/seller/notice/'); ?>" class="mobile-nav-submenu-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/seller/notice/') !== false) ? 'active' : ''; ?>">
                    공지사항
                </a>
            </div>
        </div>
        
        <!-- 통계 -->
        <div class="mobile-nav-category">
            <a href="<?php echo getAssetPath('/seller/statistics/'); ?>" class="mobile-nav-category-btn">
                통계
            </a>
        </div>
        
        <!-- 계정 -->
        <div class="mobile-nav-category">
            <button class="mobile-nav-category-btn" data-category="account">
                계정
            </button>
            <div class="mobile-nav-submenu">
                <a href="<?php echo getAssetPath('/seller/profile.php'); ?>" class="mobile-nav-submenu-item <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                    내정보
                </a>
            </div>
        </div>
    </div>
    
    <!-- 모바일 메뉴 오버레이 -->
    <div class="sidebar-overlay" id="mobileNavOverlay"></div>
    
    <style>
    /* 사이드바 완전히 숨기기 */
    .seller-sidebar {
        display: none !important;
    }
    
    /* 오버레이 스타일 */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 997;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .sidebar-overlay.active {
        display: block;
        opacity: 1;
    }
    </style>
    
    <!-- 판매자명 입력 모달 -->
    <?php if (isset($showSellerNameModal) && $showSellerNameModal): ?>
    <div class="seller-name-modal-overlay" id="sellerNameModal" style="display: flex;">
        <div class="seller-name-modal">
            <div class="seller-name-modal-header">
                <h2 class="seller-name-modal-title">판매자명 설정 필요</h2>
            </div>
            <div class="seller-name-modal-body">
                <p class="seller-name-modal-description">
                    사이트에서 판매 시 사용할 <strong>판매자명(닉네임)</strong>을 설정해주세요.<br>
                    판매자명은 고객에게 표시되며, 회원정보 수정 페이지에서 언제든지 변경할 수 있습니다.
                </p>
                <div class="seller-name-form-group">
                    <label for="modalSellerName" class="seller-name-form-label">판매자명 (닉네임) <span class="required">*</span></label>
                    <input type="text" id="modalSellerName" class="seller-name-form-input" placeholder="사이트에서 사용할 판매자명을 입력하세요" maxlength="50" autocomplete="off">
                    <div id="modalSellerNameCheckResult" class="check-result" style="margin-top: 8px;"></div>
                    <small class="seller-name-form-help">최소 2자 이상, 최대 50자까지 입력 가능합니다.</small>
                </div>
            </div>
            <div class="seller-name-modal-footer">
                <button type="button" class="seller-name-modal-btn seller-name-modal-btn-primary" id="saveSellerNameBtn" onclick="saveSellerName()">저장</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- 메인 컨텐츠 영역 -->
    <div class="seller-content-wrapper" style="margin-top: 64px !important; padding-top: 0 !important;">
        <div class="seller-content" style="margin-top: 0 !important; padding-top: 0 !important;">
            
<script>
// 네비게이션 메뉴 기능
document.addEventListener('DOMContentLoaded', function() {
    // 헤더와 본문 겹침 방지 - 강제 적용
    const contentWrapper = document.querySelector('.seller-content-wrapper');
    if (contentWrapper) {
        contentWrapper.style.marginTop = '64px';
        contentWrapper.style.paddingTop = '0';
        contentWrapper.style.setProperty('margin-top', '64px', 'important');
    }
    const content = document.querySelector('.seller-content');
    if (content) {
        content.style.marginTop = '0';
        content.style.paddingTop = '0';
    }
    // 데스크톱 네비게이션
    const navCategories = document.querySelectorAll('.nav-category');
    const navCategoryBtns = document.querySelectorAll('.nav-category-btn.has-submenu');
    
    // 모바일 네비게이션
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileNavMenu = document.getElementById('mobileNavMenu');
    const mobileNavOverlay = document.getElementById('mobileNavOverlay');
    const mobileNavCategories = document.querySelectorAll('.mobile-nav-category');
    const mobileNavCategoryBtns = document.querySelectorAll('.mobile-nav-category-btn[data-category]');
    
    // 데스크톱: 호버로 메뉴 표시 (position: absolute 사용으로 위치 계산 불필요)
    const debugSubmenu = <?php echo isset($_GET['debug_menu']) ? 'true' : 'false'; ?>;
    
    // 모든 카테고리에 호버 및 클릭 이벤트 추가
    navCategories.forEach((category, index) => {
        const btn = category.querySelector('.nav-category-btn');
        const submenu = category.querySelector('.nav-submenu');
        const categoryName = btn ? btn.textContent.trim() : '알 수 없음';
        
        if (submenu) {
            // 클릭 시 active 토글
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const isActive = category.classList.contains('active');
                
                // 모든 카테고리에서 active 및 closing 제거
                navCategories.forEach(cat => {
                    cat.classList.remove('active', 'closing');
                });
                
                // 클릭한 카테고리만 active 토글
                if (!isActive) {
                    category.classList.add('active');
                    category.classList.remove('closing');
                }
            });
            
            // 마우스 진입 시: 다른 카테고리의 active 제거 (호버로만 열리도록)
            category.addEventListener('mouseenter', function() {
                // 다른 모든 카테고리에서 active 제거 (현재 호버 중인 것 제외)
                navCategories.forEach(cat => {
                    if (cat !== category) {
                        cat.classList.remove('active', 'closing');
                    }
                });
                
                if (debugSubmenu) {
                    console.log(`[호버] ${categoryName} - 마우스 진입`);
                    setTimeout(() => {
                        const style = window.getComputedStyle(submenu);
                        console.log(`[호버] ${categoryName} - 서브메뉴 상태:`, {
                            visibility: style.visibility,
                            opacity: style.opacity,
                            zIndex: style.zIndex,
                            top: style.top,
                            left: style.left,
                            position: style.position
                        });
                    }, 100);
                }
            });
            
            // 서브메뉴 항목 클릭 시 메뉴 즉시 닫기
            const submenuItems = submenu.querySelectorAll('.nav-submenu-item');
            submenuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    // 즉시 모든 카테고리에서 active 제거하고 closing 클래스 추가
                    navCategories.forEach(cat => {
                        cat.classList.remove('active');
                        cat.classList.add('closing');
                        // 서브메뉴 즉시 숨김
                        const catSubmenu = cat.querySelector('.nav-submenu');
                        if (catSubmenu) {
                            catSubmenu.style.opacity = '0';
                            catSubmenu.style.visibility = 'hidden';
                            catSubmenu.style.pointerEvents = 'none';
                        }
                    });
                    // closing 클래스는 잠시 후 제거 (CSS transition 완료 후)
                    setTimeout(function() {
                        navCategories.forEach(cat => {
                            cat.classList.remove('closing');
                        });
                    }, 300);
                    // 링크 이동은 정상적으로 진행되도록 함
                }, true); // capture phase에서 실행하여 다른 이벤트보다 먼저 처리
            });
            
            // 마우스가 카테고리와 서브메뉴를 모두 벗어나면 active 제거
            let leaveTimer;
            category.addEventListener('mouseleave', function(e) {
                // 서브메뉴로 이동 중이면 타이머 취소
                if (submenu.contains(e.relatedTarget)) {
                    clearTimeout(leaveTimer);
                    return;
                }
                
                // 약간의 지연을 두어 서브메뉴로 이동할 시간 제공
                leaveTimer = setTimeout(function() {
                    // 호버가 끝나면 active 제거 (호버로만 열리도록)
                    category.classList.remove('active');
                    if (debugSubmenu) {
                        console.log(`[호버] ${categoryName} - 마우스 나감, active 제거`);
                    }
                }, 100);
            });
            
            // 서브메뉴에서 나갈 때도 처리
            submenu.addEventListener('mouseleave', function(e) {
                if (!category.contains(e.relatedTarget)) {
                    // 카테고리나 서브메뉴 어디에도 없으면 active 제거
                    setTimeout(function() {
                        if (!category.matches(':hover') && !submenu.matches(':hover')) {
                            category.classList.remove('active');
                        }
                    }, 100);
                }
            });
        }
    });
    
    // 외부 클릭 시 모든 active 제거
    document.addEventListener('click', function(e) {
        // 서브메뉴 항목 클릭은 제외 (이미 처리됨)
        if (e.target.closest('.nav-submenu-item')) {
            return;
        }
        
        // 카테고리나 서브메뉴 외부 클릭 시 모든 active 제거
        if (!e.target.closest('.nav-category') && !e.target.closest('.nav-submenu')) {
            navCategories.forEach(cat => {
                cat.classList.remove('active');
            });
        }
    });
    
    // 화면 크기 변경 시 (position: absolute 사용으로 재계산 불필요)
    
    // 디버깅 모드
    if (debugSubmenu) {
        console.log('=== 서브메뉴 디버깅 모드 활성화 ===');
        console.log('발견된 카테고리 개수:', navCategories.length);
        
        navCategories.forEach((category, index) => {
            const btn = category.querySelector('.nav-category-btn');
            const submenu = category.querySelector('.nav-submenu');
            const categoryName = btn ? btn.textContent.trim() : '알 수 없음';
            
            console.log(`카테고리 ${index + 1}: ${categoryName}`);
            console.log('  - 버튼 존재:', !!btn);
            console.log('  - 서브메뉴 존재:', !!submenu);
            
            if (submenu) {
                const computedStyle = window.getComputedStyle(submenu);
                console.log('  - position:', computedStyle.position);
                console.log('  - top:', computedStyle.top);
                console.log('  - z-index:', computedStyle.zIndex);
                console.log('  - visibility:', computedStyle.visibility);
                console.log('  - opacity:', computedStyle.opacity);
                console.log('  - transform:', computedStyle.transform);
            }
        });
    }
    
    // 모바일: 햄버거 메뉴 토글
    function openMobileMenu() {
        console.log('햄버거 메뉴 클릭됨');
        if (mobileNavMenu) {
            console.log('모바일 메뉴 열기');
            mobileNavMenu.classList.add('open');
        } else {
            console.error('mobileNavMenu 요소를 찾을 수 없습니다');
        }
        if (mobileNavOverlay) {
            console.log('오버레이 표시');
            mobileNavOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            console.error('mobileNavOverlay 요소를 찾을 수 없습니다');
        }
    }
    
    function closeMobileMenu() {
        if (mobileNavMenu) {
            mobileNavMenu.classList.remove('open');
        }
        if (mobileNavOverlay) {
            mobileNavOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        // 모든 모바일 카테고리 닫기
        mobileNavCategories.forEach(cat => {
            cat.classList.remove('active');
        });
    }
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', openMobileMenu);
    }
    
    if (mobileNavOverlay) {
        mobileNavOverlay.addEventListener('click', closeMobileMenu);
    }
    
    // 모바일: 카테고리 클릭 시 서브메뉴 토글
    mobileNavCategoryBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const category = this.closest('.mobile-nav-category');
            const isActive = category.classList.contains('active');
            
            // 다른 카테고리는 닫기
            mobileNavCategories.forEach(cat => {
                if (cat !== category) {
                    cat.classList.remove('active');
                }
            });
            
            // 클릭한 카테고리 토글
            category.classList.toggle('active', !isActive);
        });
    });
    
    // 모바일 메뉴 링크 클릭 시 메뉴 닫기 (모든 화면 크기에서)
    if (mobileNavMenu) {
        const menuLinks = mobileNavMenu.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                closeMobileMenu();
            });
        });
    }
    
    // ESC 키로 모바일 메뉴 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileNavMenu && mobileNavMenu.classList.contains('open')) {
            closeMobileMenu();
        }
    });
    
    // 화면 크기 변경 시 처리 (모바일 메뉴는 유지)
    let navResizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(navResizeTimer);
        navResizeTimer = setTimeout(function() {
            // 데스크톱 네비게이션의 카테고리만 닫기 (모바일 메뉴는 유지)
            if (window.innerWidth > 768) {
                navCategories.forEach(cat => {
                    cat.classList.remove('active');
                });
            }
        }, 250);
    });
    
    // 현재 페이지에 맞는 카테고리 활성화 (서브메뉴 자동 표시는 하지 않음 - 호버 시에만)
    // active 클래스는 버튼 스타일링용으로만 사용, 서브메뉴는 호버 시에만 표시
    const currentUrl = window.location.pathname;
    navCategories.forEach(category => {
        const submenuItems = category.querySelectorAll('.nav-submenu-item');
        submenuItems.forEach(item => {
            if (item.href && currentUrl.includes(new URL(item.href).pathname)) {
                // active 클래스는 추가하되, 서브메뉴는 호버 시에만 표시되도록 CSS에서 처리
                category.classList.add('active');
            }
        });
    });
    
    mobileNavCategories.forEach(category => {
        const submenuItems = category.querySelectorAll('.mobile-nav-submenu-item');
        submenuItems.forEach(item => {
            if (item.href && currentUrl.includes(new URL(item.href).pathname)) {
                category.classList.add('active');
            }
        });
    });
});
</script>

