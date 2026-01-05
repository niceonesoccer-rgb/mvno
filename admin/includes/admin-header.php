<?php
/**
 * 관리자 페이지 공통 헤더
 * 사이드바 네비게이션 포함
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';

// 관리자 인증 체크 (출력 전에 체크)
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    // 관리자가 아니면 관리자 로그인 페이지로 리다이렉트
    header('Location: /MVNO/admin/login.php');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 페이지 - 유심킹</title>
    <link rel="stylesheet" href="/MVNO/assets/css/style.css">
    <script src="/MVNO/assets/js/modal.js" defer></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
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
            right: 0;
            z-index: 1000;
            height: 60px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .admin-top-header-left {
            display: flex;
            align-items: center;
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
        }
        
        .admin-top-header-logo:hover {
            opacity: 0.8;
        }
        
        .admin-top-header-right {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .admin-top-header-link {
            font-size: 14px;
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 6px;
            transition: background 0.2s;
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
        }
        
        .admin-content {
            padding: 24px;
            background: transparent;
        }
        
        
        
        /* 반응형 */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .admin-sidebar.open {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .admin-top-header {
                padding: 12px 16px;
            }
            
            .admin-top-header-logo {
                font-size: 16px;
            }
            
            .admin-top-header-link {
                font-size: 13px;
            }
            
            .admin-top-header-right {
                gap: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- 상단 헤더 -->
    <header class="admin-top-header">
        <div class="admin-top-header-left">
            <a href="/MVNO/admin/" class="admin-top-header-logo" style="text-decoration: none; color: inherit;">유심킹 관리자</a>
        </div>
        <div class="admin-top-header-right">
            <a href="/MVNO/" target="_blank" class="admin-top-header-link">사이트보기</a>
            <a href="/MVNO/api/logout.php" class="admin-top-header-link">로그아웃</a>
        </div>
    </header>
    
    <!-- 사이드바 -->
    <aside class="admin-sidebar" id="adminSidebar">
        <nav class="sidebar-menu">
            <!-- 대시보드 -->
            <div class="menu-section">
                <a href="/MVNO/admin/" class="menu-item <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
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
                <a href="/MVNO/admin/seller-approval.php?tab=pending" class="menu-item <?php echo ($currentPage === 'seller-approval.php' || $currentPage === 'seller-permissions.php') ? 'active' : ''; ?>">
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
                <a href="/MVNO/admin/users/member-list.php" class="menu-item <?php echo $currentPage === 'member-list.php' ? 'active' : ''; ?>">
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
                <a href="/MVNO/admin/users/sub-admin-manage.php" class="menu-item <?php echo $currentPage === 'sub-admin-manage.php' ? 'active' : ''; ?>">
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
            </div>
            
            <!-- 주문 관리 -->
            <div class="menu-section">
                <div class="menu-section-title">주문 관리</div>
                <a href="/MVNO/admin/orders/mno-sim-list.php" class="menu-item <?php echo ($currentPage === 'mno-sim-list.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                    </span>
                    통신사단독유심
                </a>
                <a href="/MVNO/admin/orders/mvno-list.php" class="menu-item <?php echo ($currentPage === 'mvno-list.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                    </span>
                    알뜰폰
                </a>
                <a href="/MVNO/admin/orders/mno-list.php" class="menu-item <?php echo ($currentPage === 'mno-list.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </span>
                    통신사폰
                </a>
                <a href="/MVNO/admin/orders/internet-list.php" class="menu-item <?php echo ($currentPage === 'internet-list.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
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
                <a href="/MVNO/admin/products/mno-sim-list.php" class="menu-item <?php echo ($currentPage === 'mno-sim-list.php' && strpos($_SERVER['REQUEST_URI'], '/products/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                    </span>
                    통신사단독유심 관리
                </a>
                <a href="/MVNO/admin/products/mvno-list.php" class="menu-item">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                    </span>
                    알뜰폰 관리
                </a>
                <a href="/MVNO/admin/products/mno-list.php" class="menu-item">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </span>
                    통신사폰 관리
                </a>
                <a href="/MVNO/admin/products/internet-list.php" class="menu-item">
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
                <a href="/MVNO/admin/advertisement/prices.php" class="menu-item <?php echo ($currentPage === 'prices.php' && strpos($_SERVER['REQUEST_URI'], '/advertisement/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="2" x2="12" y2="22"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </span>
                    광고 가격 설정
                </a>
                <a href="/MVNO/admin/advertisement/list.php" class="menu-item <?php echo ($currentPage === 'list.php' && strpos($_SERVER['REQUEST_URI'], '/advertisement/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </span>
                    광고 목록
                </a>
                <a href="/MVNO/admin/advertisement/tagline.php" class="menu-item <?php echo ($currentPage === 'tagline.php' && strpos($_SERVER['REQUEST_URI'], '/advertisement/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            <path d="M13 8H7"/>
                            <path d="M17 12H7"/>
                        </svg>
                    </span>
                    태그라인
                </a>
                <a href="/MVNO/admin/advertisement/analytics.php" class="menu-item <?php echo ($currentPage === 'analytics.php' && strpos($_SERVER['REQUEST_URI'], '/advertisement/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                    </span>
                    광고 분석
                </a>
                <a href="/MVNO/admin/deposit/requests.php" class="menu-item <?php echo ($currentPage === 'requests.php' && strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                    </span>
                    입금 신청 목록
                </a>
                <a href="/MVNO/admin/deposit/bank-accounts.php" class="menu-item <?php echo ($currentPage === 'bank-accounts.php' && strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                            <path d="M6 16h.01M10 16h.01M14 16h.01M18 16h.01"/>
                        </svg>
                    </span>
                    무통장 계좌 관리
                </a>
                <a href="/MVNO/admin/deposit/adjust.php" class="menu-item <?php echo ($currentPage === 'adjust.php' && strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="2" x2="12" y2="22"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </span>
                    예치금 조정
                </a>
                <a href="/MVNO/admin/tax-invoice/issue.php" class="menu-item <?php echo ($currentPage === 'issue.php' && strpos($_SERVER['REQUEST_URI'], '/tax-invoice/') !== false) ? 'active' : ''; ?>">
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
                <a href="/MVNO/admin/content/event-manage.php" class="menu-item <?php echo $currentPage === 'event-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                    </span>
                    이벤트 관리
                </a>
                <a href="/MVNO/admin/content/banner-manage.php" class="menu-item <?php echo $currentPage === 'banner-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="3" x2="9" y2="21"/>
                        </svg>
                    </span>
                    배너 관리
                </a>
                <a href="/MVNO/admin/content/home-product-select.php" class="menu-item <?php echo $currentPage === 'home-product-select.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </span>
                    메인상품선택
                </a>
                <a href="/MVNO/admin/content/notice-manage.php" class="menu-item <?php echo $currentPage === 'notice-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                    </span>
                    공지사항 관리
                </a>
                <a href="/MVNO/admin/content/seller-notice-manage.php" class="menu-item <?php echo $currentPage === 'seller-notice-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                    </span>
                    판매자 공지사항 관리
                </a>
                <a href="/MVNO/admin/content/qna-manage.php" class="menu-item <?php echo $currentPage === 'qna-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </span>
                    Q&A 관리
                </a>
                <a href="/MVNO/admin/content/seller-inquiry-manage.php" class="menu-item <?php echo $currentPage === 'seller-inquiry-manage.php' ? 'active' : ''; ?>">
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
                <a href="/MVNO/admin/settings/admin-manage.php" class="menu-item <?php echo $currentPage === 'admin-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </span>
                    관리자 관리
                </a>
                <a href="/MVNO/admin/settings/site-settings.php" class="menu-item <?php echo $currentPage === 'site-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 5 15.4a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.13.33.2.68.2 1.03s-.07.7-.2 1.03"/>
                        </svg>
                    </span>
                    사이트설정
                </a>
                <a href="/MVNO/admin/settings/forbidden-ids-manage.php" class="menu-item <?php echo $currentPage === 'forbidden-ids-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <line x1="9" y1="12" x2="15" y2="12"/>
                        </svg>
                    </span>
                    가입 금지어 관리
                </a>
                <a href="/MVNO/admin/settings/api-settings.php" class="menu-item <?php echo $currentPage === 'api-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    API 설정
                </a>
                <a href="/MVNO/admin/settings/email-settings.php" class="menu-item <?php echo $currentPage === 'email-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </span>
                    이메일 설정
                </a>
                <a href="/MVNO/admin/settings/point-settings.php" class="menu-item <?php echo $currentPage === 'point-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </span>
                    포인트 설정
                </a>
                <a href="/MVNO/admin/settings/customer-point-history.php" class="menu-item <?php echo $currentPage === 'customer-point-history.php' ? 'active' : ''; ?>">
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
                <a href="/MVNO/admin/settings/privacy-settings.php" class="menu-item <?php echo $currentPage === 'privacy-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <path d="M9 12l2 2 4-4"/>
                        </svg>
                    </span>
                    개인정보 설정
                </a>
                <a href="/MVNO/admin/settings/device-settings.php" class="menu-item <?php echo $currentPage === 'device-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                    </span>
                    단말기 설정
                </a>
                <a href="/MVNO/admin/settings/internet-service-types.php" class="menu-item <?php echo $currentPage === 'internet-service-types.php' ? 'active' : ''; ?>">
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
                <a href="/MVNO/admin/review-settings.php" class="menu-item <?php echo $currentPage === 'review-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            <path d="M13 8H7"/>
                            <path d="M17 12H7"/>
                        </svg>
                    </span>
                    리뷰 작성 권한
                </a>
                <a href="/MVNO/admin/settings/data-delete.php" class="menu-item <?php echo $currentPage === 'data-delete.php' ? 'active' : ''; ?>">
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
            </div>
            
            <!-- 통계 분석 -->
            <div class="menu-section">
                <div class="menu-section-title">통계 분석</div>
                <a href="/MVNO/admin/analytics/dashboard.php" class="menu-item <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                    </span>
                    통계 대시보드
                </a>
                <a href="/MVNO/admin/analytics/realtime.php" class="menu-item <?php echo $currentPage === 'realtime.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </span>
                    실시간 통계
                </a>
                <a href="/MVNO/admin/analytics/advanced.php" class="menu-item <?php echo $currentPage === 'advanced.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="M21 21l-4.35-4.35"/>
                        </svg>
                    </span>
                    고급 분석
                </a>
                <a href="/MVNO/admin/analytics/seller-stats.php" class="menu-item <?php echo $currentPage === 'seller-stats.php' ? 'active' : ''; ?>">
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
                <a href="/MVNO/admin/analytics/product-stats.php" class="menu-item <?php echo $currentPage === 'product-stats.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                    </span>
                    게시물별 통계
                </a>
                <a href="/MVNO/admin/analytics/cleanup.php" class="menu-item <?php echo $currentPage === 'cleanup.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </span>
                    데이터 정리
                </a>
                <a href="/MVNO/admin/analytics/settings.php" class="menu-item <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
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
                <a href="/MVNO/admin/monitor.php" class="menu-item <?php echo $currentPage === 'monitor.php' ? 'active' : ''; ?>">
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
                <a href="/MVNO/admin/utils/image-selector.php" class="menu-item <?php echo $currentPage === 'image-selector.php' ? 'active' : ''; ?>">
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

