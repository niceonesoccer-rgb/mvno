<?php
/**
 * 관리자 페이지 공통 헤더
 * 사이드바 네비게이션 포함
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/path-config.php';

// 세션 시작 (인증 체크 전에 명시적으로 시작)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 인증 체크 (출력 전에 체크)
try {
    $currentUser = getCurrentUser();
    if (!$currentUser || !isAdmin($currentUser['user_id'])) {
        // 관리자가 아니면 관리자 로그인 페이지로 리다이렉트
        // 현재 스크립트 위치 기준으로 상대 경로 계산
        $currentDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/admin');
        $adminPath = rtrim($currentDir, '/');
        $redirectUrl = $adminPath . '/login.php';
        header('Location: ' . $redirectUrl);
        exit;
    }
} catch (Exception $e) {
    // 인증 체크 중 오류 발생 시 로그인 페이지로 리다이렉트
    error_log('admin-header auth check error: ' . $e->getMessage());
    $currentDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/admin');
    $adminPath = rtrim($currentDir, '/');
    $redirectUrl = $adminPath . '/login.php';
    header('Location: ' . $redirectUrl);
    exit;
}

// DB-only: JSON 기반 scheduled deletion 처리 제거

$currentPage = basename($_SERVER['PHP_SELF']);

// 관리자/부관리자 수 계산 (DB)
$adminCount = 0;
$pdo = getDBConnection();
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin','sub_admin')");
        $adminCount = (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('admin-header adminCount DB error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" id="viewportMeta" content="width=1400, initial-scale=0.5, minimum-scale=0.1, maximum-scale=5.0, user-scalable=yes">
    <title>관리자 페이지 - 유심킹</title>
    <link rel="icon" type="image/png" href="<?php echo getAssetPath('/images/site/favicon.png'); ?>">
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch; /* iOS에서 부드러운 스크롤 */
            min-width: 1400px; /* 모바일에서도 최소 너비 유지 */
        }
        
        /* 헤더 */
        .admin-top-header {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: 1px solid #4c51bf;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            height: 60px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .admin-top-header {
            min-width: 1400px; /* 모바일에서도 최소 너비 유지 */
        }
        
        .admin-top-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        /* 햄버거 메뉴 버튼 */
        .mobile-menu-toggle {
            display: none;
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
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
        }
        
        .admin-top-header-logo {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            text-decoration: underline;
            text-decoration-color: #fbbf24;
            text-decoration-style: wavy;
            text-underline-offset: 4px;
            transition: opacity 0.2s;
            cursor: pointer;
            white-space: nowrap;
        }
        
        @media (max-width: 768px) {
            .admin-top-header-logo {
                font-size: 16px;
            }
        }
        
        .admin-top-header-logo:hover {
            opacity: 0.8;
        }
        
        .admin-top-header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .admin-top-header-right {
                display: none; /* 모바일에서 숨김 */
            }
        }
        
        .admin-top-header-link {
            font-size: 14px;
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 6px;
            transition: background 0.2s;
            white-space: nowrap;
        }
        
        @media (max-width: 768px) {
            .admin-top-header-link {
                font-size: 12px;
                padding: 6px 10px;
            }
        }
        
        .admin-top-header-link:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* 사이드바 */
        .admin-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1e293b 0%, #334155 50%, #475569 100%);
            color: #f1f5f9;
            position: fixed;
            height: calc(100vh - 60px);
            overflow-y: auto;
            z-index: 999;
            top: 60px;
            left: 0;
            border-right: 2px solid #64748b;
            box-shadow: 2px 0 16px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }
        
        /* 사이드바 닫기 버튼 */
        .sidebar-close-btn {
            display: none;
            position: absolute;
            top: 16px;
            right: 16px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #ffffff;
            font-size: 24px;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
            z-index: 1000;
        }
        
        .sidebar-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* 사이드바 오버레이 */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .sidebar-overlay.active {
            opacity: 1;
        }
        
        /* 모바일에서 사이드바 숨김 */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                width: 260px;
                max-width: 80vw;
            }
            
            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .sidebar-close-btn {
                display: flex;
            }
            
            .sidebar-overlay {
                display: block;
            }
        }
        
        .admin-sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .admin-sidebar::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.3);
        }
        
        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(59, 130, 246, 0.5);
            border-radius: 3px;
        }
        
        .admin-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(59, 130, 246, 0.7);
        }
        
        .sidebar-user {
            padding: 20px;
            border-bottom: 2px solid #475569;
            font-size: 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-user::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .sidebar-user-name {
            font-weight: 700;
            margin-bottom: 4px;
            color: #ffffff;
            font-size: 15px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .sidebar-user-role {
            color: #dbeafe;
            font-size: 12px;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }
        
        .sidebar-menu {
            padding: 8px 0;
        }
        
        .menu-section {
            margin-bottom: 8px;
        }
        
        .menu-section-title {
            padding: 12px 20px 8px;
            font-size: 11px;
            font-weight: 700;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.1) 100%);
            border-left: 4px solid #3b82f6;
            margin-top: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            position: relative;
            cursor: pointer;
        }
        
        .menu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: transparent;
            transition: all 0.3s;
            pointer-events: none;
        }
        
        .menu-item::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.1) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }
        
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.05) 100%);
            color: #ffffff;
            padding-left: 24px;
        }
        
        .menu-item:hover::before {
            background: linear-gradient(180deg, #60a5fa 0%, #3b82f6 100%);
        }
        
        .menu-item:hover::after {
            opacity: 1;
        }
        
        .menu-item.active {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.3) 0%, rgba(59, 130, 246, 0.15) 100%);
            color: #ffffff;
            border-left-color: #60a5fa;
            font-weight: 600;
            box-shadow: inset 0 0 20px rgba(59, 130, 246, 0.2);
        }
        
        .menu-item.active::before {
            background: linear-gradient(180deg, #60a5fa 0%, #3b82f6 100%);
            width: 4px;
            box-shadow: 0 0 10px rgba(96, 165, 250, 0.5);
        }
        
        .menu-item.active::after {
            opacity: 1;
        }
        
        .menu-item-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            margin-right: 12px;
            flex-shrink: 0;
            border-radius: 8px;
            background: rgba(59, 130, 246, 0.2);
            transition: all 0.3s;
            position: relative;
            z-index: 1;
            pointer-events: none;
        }
        
        .menu-item-icon svg {
            width: 16px;
            height: 16px;
            stroke: #93c5fd;
            fill: none;
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
            transition: all 0.3s;
        }
        
        .menu-item:hover .menu-item-icon {
            background: linear-gradient(135deg, rgba(96, 165, 250, 0.4) 0%, rgba(59, 130, 246, 0.3) 100%);
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        
        .menu-item:hover .menu-item-icon svg {
            stroke: #ffffff;
        }
        
        .menu-item.active .menu-item-icon {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.5);
            transform: scale(1.05);
        }
        
        .menu-item.active .menu-item-icon svg {
            stroke: #ffffff;
        }
        
        /* 메뉴 섹션별 색상 구분 */
        .menu-section:nth-child(2) .menu-item-icon {
            background: rgba(251, 191, 36, 0.2);
        }
        
        .menu-section:nth-child(2) .menu-item-icon svg {
            stroke: #fbbf24;
        }
        
        .menu-section:nth-child(2) .menu-item:hover .menu-item-icon {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.4) 0%, rgba(245, 158, 11, 0.3) 100%);
        }
        
        .menu-section:nth-child(2) .menu-item.active .menu-item-icon {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        }
        
        .menu-section:nth-child(3) .menu-item-icon {
            background: rgba(139, 92, 246, 0.2);
        }
        
        .menu-section:nth-child(3) .menu-item-icon svg {
            stroke: #a78bfa;
        }
        
        .menu-section:nth-child(3) .menu-item:hover .menu-item-icon {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.4) 0%, rgba(124, 58, 237, 0.3) 100%);
        }
        
        .menu-section:nth-child(3) .menu-item.active .menu-item-icon {
            background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
        }
        
        .menu-section:nth-child(4) .menu-item-icon {
            background: rgba(239, 68, 68, 0.2);
        }
        
        .menu-section:nth-child(4) .menu-item-icon svg {
            stroke: #f87171;
        }
        
        .menu-section:nth-child(4) .menu-item:hover .menu-item-icon {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.4) 0%, rgba(220, 38, 38, 0.3) 100%);
        }
        
        .menu-section:nth-child(4) .menu-item.active .menu-item-icon {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
        }
        
        .menu-section:nth-child(5) .menu-item-icon {
            background: rgba(236, 72, 153, 0.2);
        }
        
        .menu-section:nth-child(4) .menu-item-icon svg {
            stroke: #f472b6;
        }
        
        .menu-section:nth-child(4) .menu-item:hover .menu-item-icon {
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.4) 0%, rgba(219, 39, 119, 0.3) 100%);
        }
        
        .menu-section:nth-child(4) .menu-item.active .menu-item-icon {
            background: linear-gradient(135deg, #f472b6 0%, #ec4899 100%);
        }
        
        .menu-section:nth-child(5) .menu-item-icon {
            background: rgba(16, 185, 129, 0.2);
        }
        
        .menu-section:nth-child(5) .menu-item-icon svg {
            stroke: #34d399;
        }
        
        .menu-section:nth-child(5) .menu-item:hover .menu-item-icon {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.4) 0%, rgba(5, 150, 105, 0.3) 100%);
        }
        
        .menu-section:nth-child(5) .menu-item.active .menu-item-icon {
            background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
        }
        
        .menu-section:nth-child(6) .menu-item-icon {
            background: rgba(59, 130, 246, 0.2);
        }
        
        .menu-section:nth-child(6) .menu-item-icon svg {
            stroke: #93c5fd;
        }
        
        .menu-section:nth-child(6) .menu-item:hover .menu-item-icon {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.4) 0%, rgba(37, 99, 235, 0.3) 100%);
        }
        
        .menu-section:nth-child(6) .menu-item.active .menu-item-icon {
            background: linear-gradient(135deg, #93c5fd 0%, #60a5fa 100%);
        }
        
        .menu-section:nth-child(7) .menu-item-icon {
            background: rgba(249, 115, 22, 0.2);
        }
        
        .menu-section:nth-child(7) .menu-item-icon svg {
            stroke: #fb923c;
        }
        
        .menu-section:nth-child(7) .menu-item:hover .menu-item-icon {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.4) 0%, rgba(234, 88, 12, 0.3) 100%);
        }
        
        .menu-section:nth-child(7) .menu-item.active .menu-item-icon {
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
        }
        
        .menu-section:nth-child(8) .menu-item-icon {
            background: rgba(99, 102, 241, 0.2);
        }
        
        .menu-section:nth-child(8) .menu-item-icon svg {
            stroke: #a5b4fc;
        }
        
        .menu-section:nth-child(8) .menu-item:hover .menu-item-icon {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.4) 0%, rgba(79, 70, 229, 0.3) 100%);
        }
        
        .menu-section:nth-child(8) .menu-item.active .menu-item-icon {
            background: linear-gradient(135deg, #a5b4fc 0%, #818cf8 100%);
        }
        
        .menu-sub-item {
            padding-left: 52px;
            font-size: 13px;
        }
        
        .menu-add-button {
            padding: 8px 20px;
            font-size: 13px;
            color: #93c5fd;
            background: rgba(59, 130, 246, 0.15);
            margin: 4px 20px;
            border-radius: 6px;
            text-align: center;
            justify-content: center;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .menu-add-button:hover {
            background: rgba(59, 130, 246, 0.25);
            color: #ffffff;
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        
        /* 메인 콘텐츠 */
        .admin-main {
            margin-left: 260px;
            margin-top: 60px;
            min-height: calc(100vh - 60px);
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            /* 스크롤바 숨김 */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
        }
        
        /* 스크롤바 숨김 (Webkit 브라우저) */
        .admin-main::-webkit-scrollbar {
            display: none;
        }
        
        .admin-main {
            min-width: calc(1400px - 260px); /* 모바일에서도 최소 너비 유지 */
        }
        
        @media (max-width: 768px) {
            .admin-main {
                margin-left: 0;
                width: 100%;
                min-width: 1400px; /* 모바일에서도 전체 너비 유지 */
                /* 모바일에서 확대/축소와 패닝 허용 */
                touch-action: pan-x pan-y pinch-zoom;
            }
        }
        
        .admin-content {
            padding: 24px;
            background: transparent;
            min-width: 1000px;
        }
        
        @media (max-width: 768px) {
            .admin-content {
                padding: 16px;
                /* 모바일에서 터치 제스처 허용 */
                touch-action: pan-x pan-y pinch-zoom;
            }
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
</head>
<body>
    <!-- 사이드바 오버레이 -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- 상단 헤더 -->
    <header class="admin-top-header">
        <div class="admin-top-header-left">
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="메뉴 열기">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <a href="<?php echo getAssetPath('/admin/'); ?>" class="admin-top-header-logo" style="text-decoration: none; color: inherit;">관리자 센터</a>
        </div>
        <div class="admin-top-header-right">
            <a href="<?php echo getAssetPath('/'); ?>" target="_blank" class="admin-top-header-link">사이트보기</a>
            <a href="<?php echo getApiPath('/api/logout.php'); ?>" class="admin-top-header-link">로그아웃</a>
        </div>
    </header>
    
    <!-- 사이드바 -->
    <aside class="admin-sidebar" id="adminSidebar">
        <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="메뉴 닫기">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
        <nav class="sidebar-menu">
            <!-- 대시보드 -->
            <div class="menu-section">
                <a href="<?php echo getAssetPath('/admin/'); ?>" class="menu-item <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </span>
                    대시보드
                </a>
            </div>
            
            <!-- 사용자 관리 -->
            <div class="menu-section">
                <div class="menu-section-title">사용자 관리</div>
                <a href="<?php echo getAssetPath('/admin/seller-approval.php?tab=pending'); ?>" class="menu-item <?php echo ($currentPage === 'seller-approval.php' || $currentPage === 'seller-permissions.php') ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </span>
                    판매자 관리
                </a>
                <a href="<?php echo getAssetPath('/admin/users/member-list.php'); ?>" class="menu-item <?php echo $currentPage === 'member-list.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </span>
                    회원 관리
                </a>
                <?php if (getUserRole($currentUser['user_id']) === 'admin'): ?>
                <a href="<?php echo getAssetPath('/admin/users/sub-admin-manage.php'); ?>" class="menu-item <?php echo $currentPage === 'sub-admin-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </span>
                    부관리자 관리
                </a>
                <?php endif; ?>
            </div>
            
            <!-- 주문 관리 -->
            <div class="menu-section">
                <div class="menu-section-title">주문 관리</div>
                <a href="<?php echo getAssetPath('/admin/orders/mno-sim-list.php'); ?>" class="menu-item <?php echo ($currentPage === 'mno-sim-list.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                    </span>
                    통신사단독유심
                </a>
                <a href="<?php echo getAssetPath('/admin/orders/mvno-list.php'); ?>" class="menu-item <?php echo ($currentPage === 'mvno-list.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                    </span>
                    알뜰폰
                </a>
                <a href="<?php echo getAssetPath('/admin/orders/mno-list.php'); ?>" class="menu-item <?php echo ($currentPage === 'mno-list.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </span>
                    통신사폰
                </a>
                <a href="<?php echo getAssetPath('/admin/orders/internet-list.php'); ?>" class="menu-item <?php echo ($currentPage === 'internet-list.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        </svg>
                    </span>
                    인터넷
                </a>
            </div>
            
            <!-- 상품 관리 -->
            <div class="menu-section">
                <div class="menu-section-title">상품 관리</div>
                <a href="<?php echo getAssetPath('/admin/products/mno-sim-list.php'); ?>" class="menu-item <?php echo ($currentPage === 'mno-sim-list.php' && strpos($_SERVER['REQUEST_URI'], '/products/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                    </span>
                    통신사단독유심 관리
                </a>
                <a href="<?php echo getAssetPath('/admin/products/mvno-list.php'); ?>" class="menu-item">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                    </span>
                    알뜰폰 관리
                </a>
                <a href="<?php echo getAssetPath('/admin/products/mno-list.php'); ?>" class="menu-item">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </span>
                    통신사폰 관리
                </a>
                <a href="<?php echo getAssetPath('/admin/products/internet-list.php'); ?>" class="menu-item">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        </svg>
                    </span>
                    인터넷 관리
                </a>
            </div>
            
            <!-- 광고 관리 -->
            <div class="menu-section">
                <div class="menu-section-title">광고 관리</div>
                <a href="<?php echo getAssetPath('/admin/advertisement/prices.php'); ?>" class="menu-item <?php echo ($currentPage === 'prices.php' && strpos($_SERVER['REQUEST_URI'], '/advertisement/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="2" x2="12" y2="22"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </span>
                    광고 가격 설정
                </a>
                <a href="<?php echo getAssetPath('/admin/advertisement/list.php'); ?>" class="menu-item <?php echo ($currentPage === 'list.php' && strpos($_SERVER['REQUEST_URI'], '/advertisement/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </span>
                    광고 목록
                </a>
                <a href="<?php echo getAssetPath('/admin/advertisement/tagline.php'); ?>" class="menu-item <?php echo ($currentPage === 'tagline.php' && strpos($_SERVER['REQUEST_URI'], '/advertisement/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            <path d="M13 8H7"/>
                            <path d="M17 12H7"/>
                        </svg>
                    </span>
                    태그라인
                </a>
                <a href="<?php echo getAssetPath('/admin/advertisement/analytics.php'); ?>" class="menu-item <?php echo ($currentPage === 'analytics.php' && strpos($_SERVER['REQUEST_URI'], '/advertisement/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                    </span>
                    광고 분석
                </a>
                <a href="<?php echo getAssetPath('/admin/deposit/requests.php'); ?>" class="menu-item <?php echo ($currentPage === 'requests.php' && strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                    </span>
                    입금 신청 목록
                </a>
                <a href="<?php echo getAssetPath('/admin/deposit/bank-accounts.php'); ?>" class="menu-item <?php echo ($currentPage === 'bank-accounts.php' && strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                            <path d="M6 16h.01M10 16h.01M14 16h.01M18 16h.01"/>
                        </svg>
                    </span>
                    무통장 계좌 관리
                </a>
                <a href="<?php echo getAssetPath('/admin/deposit/adjust.php'); ?>" class="menu-item <?php echo ($currentPage === 'adjust.php' && strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="2" x2="12" y2="22"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </span>
                    예치금 조정
                </a>
                <a href="<?php echo getAssetPath('/admin/tax-invoice/issue.php'); ?>" class="menu-item <?php echo ($currentPage === 'issue.php' && strpos($_SERVER['REQUEST_URI'], '/tax-invoice/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </span>
                    세금계산서 발행
                </a>
            </div>
            
            <!-- 콘텐츠 관리 -->
            <div class="menu-section">
                <div class="menu-section-title">콘텐츠 관리</div>
                <a href="<?php echo getAssetPath('/admin/content/event-manage.php'); ?>" class="menu-item <?php echo $currentPage === 'event-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                    </span>
                    이벤트 관리
                </a>
                <a href="<?php echo getAssetPath('/admin/content/banner-manage.php'); ?>" class="menu-item <?php echo $currentPage === 'banner-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="3" x2="9" y2="21"/>
                        </svg>
                    </span>
                    배너 관리
                </a>
                <a href="<?php echo getAssetPath('/admin/content/home-product-select.php'); ?>" class="menu-item <?php echo $currentPage === 'home-product-select.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </span>
                    메인상품선택
                </a>
                <a href="<?php echo getAssetPath('/admin/content/notice-manage.php'); ?>" class="menu-item <?php echo $currentPage === 'notice-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                    </span>
                    공지사항 관리
                </a>
                <a href="<?php echo getAssetPath('/admin/content/seller-notice-manage.php'); ?>" class="menu-item <?php echo $currentPage === 'seller-notice-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                    </span>
                    판매자 공지사항 관리
                </a>
                <a href="<?php echo getAssetPath('/admin/content/qna-manage.php'); ?>" class="menu-item <?php echo $currentPage === 'qna-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </span>
                    Q&A 관리
                </a>
                <a href="<?php echo getAssetPath('/admin/content/seller-inquiry-manage.php'); ?>" class="menu-item <?php echo $currentPage === 'seller-inquiry-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            <path d="M13 8H7"/>
                            <path d="M17 12H7"/>
                        </svg>
                    </span>
                    판매자 1:1 문의 관리
                </a>
            </div>
            
            <!-- 설정 -->
            <div class="menu-section">
                <div class="menu-section-title">설정</div>
                <?php if (getUserRole($currentUser['user_id']) === 'admin' || getUserRole($currentUser['user_id']) === 'sub_admin'): ?>
                <a href="<?php echo getAssetPath('/admin/settings/admin-manage.php'); ?>" class="menu-item <?php echo $currentPage === 'admin-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    내 정보
                </a>
                <?php endif; ?>
                <a href="<?php echo getAssetPath('/admin/settings/site-settings.php'); ?>" class="menu-item <?php echo $currentPage === 'site-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 5 15.4a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.13.33.2.68.2 1.03s-.07.7-.2 1.03"/>
                        </svg>
                    </span>
                    사이트설정
                </a>
                <a href="<?php echo getAssetPath('/admin/settings/forbidden-ids-manage.php'); ?>" class="menu-item <?php echo $currentPage === 'forbidden-ids-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <line x1="9" y1="12" x2="15" y2="12"/>
                        </svg>
                    </span>
                    가입 금지어 관리
                </a>
                <a href="<?php echo getAssetPath('/admin/settings/email-settings.php'); ?>" class="menu-item <?php echo $currentPage === 'email-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </span>
                    이메일 설정
                </a>
                <a href="<?php echo getAssetPath('/admin/settings/point-settings.php'); ?>" class="menu-item <?php echo $currentPage === 'point-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </span>
                    포인트 설정
                </a>
                <a href="<?php echo getAssetPath('/admin/settings/customer-point-history.php'); ?>" class="menu-item <?php echo $currentPage === 'customer-point-history.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </span>
                    고객적립포인트
                </a>
                <a href="<?php echo getAssetPath('/admin/settings/privacy-settings.php'); ?>" class="menu-item <?php echo $currentPage === 'privacy-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <path d="M9 12l2 2 4-4"/>
                        </svg>
                    </span>
                    개인정보 설정
                </a>
                <a href="<?php echo getAssetPath('/admin/settings/device-settings.php'); ?>" class="menu-item <?php echo $currentPage === 'device-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                    </span>
                    단말기 설정
                </a>
                <a href="<?php echo getAssetPath('/admin/settings/internet-service-types.php'); ?>" class="menu-item <?php echo $currentPage === 'internet-service-types.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" stroke-linecap="round"/>
                            <rect x="4" y="5" width="16" height="10" rx="1" stroke-linecap="round"/>
                            <rect x="2" y="17" width="20" height="4" rx="1" stroke-linecap="round"/>
                            <path d="M17 19h-2M9 19H7" stroke-linecap="round"/>
                        </svg>
                    </span>
                    인터넷 결합여부 설정
                </a>
                <a href="<?php echo getAssetPath('/admin/review-settings.php'); ?>" class="menu-item <?php echo $currentPage === 'review-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            <path d="M13 8H7"/>
                            <path d="M17 12H7"/>
                        </svg>
                    </span>
                    리뷰 작성 권한
                </a>
                <?php if (getUserRole($currentUser['user_id']) === 'admin'): ?>
                <a href="<?php echo getAssetPath('/admin/settings/data-delete.php'); ?>" class="menu-item <?php echo $currentPage === 'data-delete.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            <line x1="10" y1="11" x2="10" y2="17"/>
                            <line x1="14" y1="11" x2="14" y2="17"/>
                        </svg>
                    </span>
                    데이터 삭제 관리
                </a>
                <a href="<?php echo getAssetPath('/admin/api-settings.php'); ?>" class="menu-item <?php echo $currentPage === 'api-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    API 설정
                </a>
                <a href="<?php echo getAssetPath('/admin/settings/db-settings.php'); ?>" class="menu-item <?php echo $currentPage === 'db-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <ellipse cx="12" cy="5" rx="9" ry="3"/>
                            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                        </svg>
                    </span>
                    DB 설정
                </a>
                <?php endif; ?>
            </div>
            
            <!-- 통계 분석 -->
            <div class="menu-section">
                <div class="menu-section-title">통계 분석</div>
                <a href="<?php echo getAssetPath('/admin/analytics/dashboard.php'); ?>" class="menu-item <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                    </span>
                    통계 대시보드
                </a>
                <a href="<?php echo getAssetPath('/admin/analytics/realtime.php'); ?>" class="menu-item <?php echo $currentPage === 'realtime.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </span>
                    실시간 통계
                </a>
                <a href="<?php echo getAssetPath('/admin/analytics/advanced.php'); ?>" class="menu-item <?php echo $currentPage === 'advanced.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="M21 21l-4.35-4.35"/>
                        </svg>
                    </span>
                    고급 분석
                </a>
                <a href="<?php echo getAssetPath('/admin/analytics/seller-stats.php'); ?>" class="menu-item <?php echo $currentPage === 'seller-stats.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </span>
                    판매자별 통계
                </a>
                <a href="<?php echo getAssetPath('/admin/analytics/product-stats.php'); ?>" class="menu-item <?php echo $currentPage === 'product-stats.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                    </span>
                    게시물별 통계
                </a>
                <a href="<?php echo getAssetPath('/admin/analytics/cleanup.php'); ?>" class="menu-item <?php echo $currentPage === 'cleanup.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </span>
                    데이터 정리
                </a>
                <a href="<?php echo getAssetPath('/admin/analytics/settings.php'); ?>" class="menu-item <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3"/>
                        </svg>
                    </span>
                    통계 설정
                </a>
            </div>
            
            <!-- 모니터링 -->
            <div class="menu-section">
                <a href="<?php echo getAssetPath('/admin/monitor.php'); ?>" class="menu-item <?php echo $currentPage === 'monitor.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                    </span>
                    모니터링
                </a>
            </div>
            
            <!-- 유틸리티 -->
            <div class="menu-section">
                <div class="menu-section-title">유틸리티</div>
                <a href="<?php echo getAssetPath('/admin/utils/image-selector.php'); ?>" class="menu-item <?php echo $currentPage === 'image-selector.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                    </span>
                    이미지 선택기
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- 메인 콘텐츠 -->
    <main class="admin-main">
        <div class="admin-content">
            
<script>
// 모바일 메뉴 토글 기능
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('adminSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
    
    function openSidebar() {
        if (sidebar) {
            sidebar.classList.add('mobile-open');
        }
        if (sidebarOverlay) {
            sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeSidebar() {
        if (sidebar) {
            sidebar.classList.remove('mobile-open');
        }
        if (sidebarOverlay) {
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', openSidebar);
    }
    
    if (sidebarCloseBtn) {
        sidebarCloseBtn.addEventListener('click', closeSidebar);
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    // 사이드바 메뉴 링크 클릭 시 모바일에서 사이드바 닫기
    if (sidebar) {
        const menuLinks = sidebar.querySelectorAll('a.menu-item');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                // 모바일 환경에서만 닫기
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });
    }
    
    // ESC 키로 사이드바 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('mobile-open')) {
            closeSidebar();
        }
    });
    
    // 화면 크기 변경 시 처리
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // 데스크톱 크기로 변경되면 사이드바 열기 상태 유지 (필요시)
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        }, 250);
    });
});
</script>

