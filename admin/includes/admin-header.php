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

// 예정된 삭제 처리 (모든 관리자 페이지 접속 시마다 확인)
processScheduledDeletions();

$currentPage = basename($_SERVER['PHP_SELF']);

// 관리자/부관리자 수 계산
$adminsFile = getAdminsFilePath();
$adminCount = 0;
if (file_exists($adminsFile)) {
    $data = json_decode(file_get_contents($adminsFile), true) ?: ['admins' => []];
    $admins = $data['admins'] ?? [];
    $adminCount = count($admins);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 페이지 - 모요</title>
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
            <a href="/MVNO/admin/" class="admin-top-header-logo" style="text-decoration: none; color: inherit;">모요관리자</a>
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
            </div>
            
            <!-- 상품 관리 -->
            <div class="menu-section">
                <div class="menu-section-title">상품 관리</div>
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
                <a href="/MVNO/admin/content/notice-manage.php" class="menu-item <?php echo $currentPage === 'notice-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                    </span>
                    공지사항 관리
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
                <button type="button" class="menu-add-button" onclick="showAddSubAdminModal()">
                    <span>+ 부관리자 추가</span>
                </button>
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
                <a href="/MVNO/admin/settings/point-settings.php" class="menu-item <?php echo $currentPage === 'point-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </span>
                    포인트 설정
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
                <a href="/MVNO/admin/settings/filter-settings.php" class="menu-item <?php echo $currentPage === 'filter-settings.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="M21 21l-4.35-4.35"/>
                        </svg>
                    </span>
                    필터 설정
                </a>
                <a href="/MVNO/admin/settings/home-manage.php" class="menu-item <?php echo $currentPage === 'home-manage.php' ? 'active' : ''; ?>">
                    <span class="menu-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </span>
                    홈 관리
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
    
    <!-- 부관리자 추가 모달 -->
    <div id="addSubAdminModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 2000; align-items: center; justify-content: center; pointer-events: auto;">
        <div id="addSubAdminModalContent" style="background: white; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; pointer-events: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="font-size: 20px; font-weight: 700; color: #1f2937; margin: 0;">부관리자 추가</h2>
                <button type="button" onclick="closeAddSubAdminModal()" style="background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">&times;</button>
            </div>
            <form id="addSubAdminForm" method="POST" action="/MVNO/api/add-admin.php">
                <input type="hidden" name="action" value="add_admin">
                <input type="hidden" name="role" value="sub_admin">
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">아이디 <span style="color: #ef4444;">*</span></label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="admin_user_id" name="user_id" required pattern="[a-z0-9]{4,20}" style="flex: 1; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;" placeholder="소문자 영문자와 숫자 조합 4-20자">
                        <button type="button" id="checkAdminIdBtn" onclick="checkAdminDuplicate()" style="padding: 12px 20px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; white-space: nowrap;">중복확인</button>
                    </div>
                    <div id="adminIdCheckResult" style="font-size: 13px; margin-top: 6px;"></div>
                    <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">소문자 영문자와 숫자 조합 4-20자</div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">이름 <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="name" required style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;" placeholder="이름">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">휴대폰 <span style="color: #ef4444;">*</span></label>
                    <input type="tel" id="admin_phone" name="phone" required pattern="010-\d{4}-\d{4}" style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;" placeholder="010-1234-5678" maxlength="13">
                    <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">휴대폰 번호를 입력해주세요. (예: 010-1234-5678)</div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">비밀번호 <span style="color: #ef4444;">*</span></label>
                    <input type="password" id="admin_password" name="password" required minlength="8" style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;" placeholder="최소 8자 이상">
                    <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">최소 8자 이상 입력해주세요.</div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">비밀번호 확인 <span style="color: #ef4444;">*</span></label>
                    <input type="password" id="admin_password_confirm" name="password_confirm" required minlength="8" style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;" placeholder="비밀번호를 다시 입력해주세요">
                    <div id="passwordMatchResult" style="font-size: 13px; margin-top: 6px;"></div>
                </div>
                
                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="submit" style="flex: 1; padding: 12px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s;">추가</button>
                    <button type="button" onclick="closeAddSubAdminModal()" style="flex: 1; padding: 12px 24px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s;">취소</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showAddSubAdminModal() {
            const modal = document.getElementById('addSubAdminModal');
            modal.style.display = 'flex';
            // 모달이 열릴 때 body 스크롤 막기
            document.body.style.overflow = 'hidden';
            // 모달 열릴 때 모든 필드 초기화 및 활성화
            setTimeout(function() {
                // 아이디 입력 필드
                const adminUserIdInput = document.getElementById('admin_user_id');
                if (adminUserIdInput) {
                    adminUserIdInput.value = '';
                    adminUserIdInput.removeAttribute('disabled');
                    adminUserIdInput.removeAttribute('readonly');
                    adminUserIdInput.disabled = false;
                    adminUserIdInput.readOnly = false;
                    adminUserIdInput.style.borderColor = '#d1d5db';
                    adminUserIdInput.focus();
                }
                
                // 비밀번호 필드 초기화
                const adminPasswordInput = document.getElementById('admin_password');
                const adminPasswordConfirmInput = document.getElementById('admin_password_confirm');
                if (adminPasswordInput) {
                    adminPasswordInput.value = '';
                    adminPasswordInput.style.borderColor = '#d1d5db';
                }
                if (adminPasswordConfirmInput) {
                    adminPasswordConfirmInput.value = '';
                    adminPasswordConfirmInput.style.borderColor = '#d1d5db';
                }
                if (passwordMatchResult) {
                    passwordMatchResult.innerHTML = '';
                }
                
                // 중복확인 상태 초기화
                document.getElementById('adminIdCheckResult').innerHTML = '';
                document.getElementById('adminIdCheckResult').className = '';
                adminIdChecked = false;
                adminIdValid = false;
            }, 100);
        }
        
        function closeAddSubAdminModal() {
            const modal = document.getElementById('addSubAdminModal');
            modal.style.display = 'none';
            // 모달이 닫힐 때 body 스크롤 복원
            document.body.style.overflow = '';
            document.getElementById('addSubAdminForm').reset();
            // 모든 상태 초기화
            const adminUserIdInput = document.getElementById('admin_user_id');
            if (adminUserIdInput) {
                adminUserIdInput.removeAttribute('disabled');
                adminUserIdInput.removeAttribute('readonly');
                adminUserIdInput.disabled = false;
                adminUserIdInput.readOnly = false;
                adminUserIdInput.style.borderColor = '#d1d5db';
            }
            document.getElementById('adminIdCheckResult').innerHTML = '';
            document.getElementById('adminIdCheckResult').className = '';
            
            // 비밀번호 필드 초기화
            const adminPasswordInput = document.getElementById('admin_password');
            const adminPasswordConfirmInput = document.getElementById('admin_password_confirm');
            const passwordMatchResultDiv = document.getElementById('passwordMatchResult');
            if (adminPasswordInput) {
                adminPasswordInput.style.borderColor = '#d1d5db';
            }
            if (adminPasswordConfirmInput) {
                adminPasswordConfirmInput.style.borderColor = '#d1d5db';
            }
            if (passwordMatchResultDiv) {
                passwordMatchResultDiv.innerHTML = '';
            }
            
            adminIdChecked = false;
            adminIdValid = false;
        }
        
        // 모달 외부 클릭 방지 (모달이 열려있을 때 뒷배경 클릭 막기)
        const addSubAdminModal = document.getElementById('addSubAdminModal');
        const addSubAdminModalContent = document.getElementById('addSubAdminModalContent');
        
        if (addSubAdminModal && addSubAdminModalContent) {
            // 모달 배경(뒷배경) 클릭 시 아무 동작도 하지 않음
            addSubAdminModal.addEventListener('click', function(e) {
                // 뒷배경을 직접 클릭한 경우에만 동작 방지
                if (e.target === this) {
                    e.preventDefault();
                    e.stopPropagation();
                    // 모달을 닫지 않음
                    return false;
                }
            });
            
            // 모달 내부 컨텐츠 영역 클릭 시 이벤트 전파 방지 (뒷배경으로 전파되지 않도록)
            addSubAdminModalContent.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        // 아이디 입력 시 소문자로 자동 변환
        const adminUserIdInput = document.getElementById('admin_user_id');
        if (adminUserIdInput) {
            adminUserIdInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^a-z0-9]/gi, '').toLowerCase();
                // 중복확인 상태 초기화
                document.getElementById('adminIdCheckResult').innerHTML = '';
                document.getElementById('adminIdCheckResult').className = '';
            });
        }
        
        // 휴대폰 번호 자동 포맷팅 (010-XXXX-XXXX, 010으로 시작 강제)
        const adminPhoneInput = document.getElementById('admin_phone');
        if (adminPhoneInput) {
            adminPhoneInput.addEventListener('input', function(e) {
                let value = this.value.replace(/[^0-9]/g, ''); // 숫자만 추출
                
                // 010으로 시작하지 않으면 처음 3자리를 010으로 강제
                if (value.length > 0) {
                    if (value.length === 1 && value !== '0') {
                        value = '0';
                    } else if (value.length === 2 && !value.startsWith('01')) {
                        value = '01';
                    } else if (value.length >= 3 && !value.startsWith('010')) {
                        value = '010' + value.substring(3);
                    }
                }
                
                // 최대 11자리까지만 허용
                if (value.length > 11) {
                    value = value.substring(0, 11);
                }
                
                // 자동 하이픈 추가 (010-XXXX-XXXX)
                if (value.length === 0) {
                    this.value = '';
                } else if (value.length <= 3) {
                    this.value = value;
                } else if (value.length > 3 && value.length <= 7) {
                    this.value = value.substring(0, 3) + '-' + value.substring(3);
                } else if (value.length > 7) {
                    this.value = value.substring(0, 3) + '-' + value.substring(3, 7) + '-' + value.substring(7);
                }
            });
            
            // 포커스 인 시 010으로 시작 확인
            adminPhoneInput.addEventListener('focus', function(e) {
                if (!this.value || this.value.trim() === '') {
                    this.value = '010-';
                    this.setSelectionRange(4, 4); // 커서를 하이픈 뒤로 이동
                } else if (!this.value.startsWith('010')) {
                    let value = this.value.replace(/[^0-9]/g, '');
                    if (value && !value.startsWith('010')) {
                        this.value = '010-' + (value.length > 3 ? value.substring(3) : '');
                    }
                }
            });
            
            // 포커스 아웃 시 형식 검증
            adminPhoneInput.addEventListener('blur', function(e) {
                const phonePattern = /^010-\d{4}-\d{4}$/;
                if (this.value && !phonePattern.test(this.value)) {
                    this.style.borderColor = '#ef4444';
                } else if (phonePattern.test(this.value)) {
                    this.style.borderColor = '#d1d5db';
                }
            });
        }
        
        // 비밀번호 확인 검증
        const adminPasswordInput = document.getElementById('admin_password');
        const adminPasswordConfirmInput = document.getElementById('admin_password_confirm');
        const passwordMatchResult = document.getElementById('passwordMatchResult');
        
        function checkPasswordMatch() {
            const password = adminPasswordInput ? adminPasswordInput.value : '';
            const passwordConfirm = adminPasswordConfirmInput ? adminPasswordConfirmInput.value : '';
            
            if (!passwordConfirm) {
                passwordMatchResult.innerHTML = '';
                if (adminPasswordConfirmInput) {
                    adminPasswordConfirmInput.style.borderColor = '#d1d5db';
                }
                return;
            }
            
            if (password && passwordConfirm) {
                if (password === passwordConfirm) {
                    passwordMatchResult.innerHTML = '<span style="color: #10b981;">✓ 비밀번호가 일치합니다.</span>';
                    if (adminPasswordConfirmInput) {
                        adminPasswordConfirmInput.style.borderColor = '#10b981';
                    }
                } else {
                    passwordMatchResult.innerHTML = '<span style="color: #ef4444;">✗ 비밀번호가 일치하지 않습니다.</span>';
                    if (adminPasswordConfirmInput) {
                        adminPasswordConfirmInput.style.borderColor = '#ef4444';
                    }
                }
            }
        }
        
        if (adminPasswordInput && adminPasswordConfirmInput) {
            adminPasswordInput.addEventListener('input', checkPasswordMatch);
            adminPasswordConfirmInput.addEventListener('input', checkPasswordMatch);
            
            adminPasswordConfirmInput.addEventListener('blur', function() {
                checkPasswordMatch();
            });
        }
        
        let adminIdChecked = false;
        let adminIdValid = false;
        
        function checkAdminDuplicate() {
            const userId = adminUserIdInput.value.trim();
            const checkBtn = document.getElementById('checkAdminIdBtn');
            const resultDiv = document.getElementById('adminIdCheckResult');
            
            if (!userId) {
                resultDiv.innerHTML = '<span style="color: #ef4444;">아이디를 입력해주세요.</span>';
                resultDiv.className = 'error';
                adminUserIdInput.focus();
                return;
            }
            
            // 아이디 형식 검증
            if (!/^[a-z0-9]{4,20}$/.test(userId)) {
                resultDiv.innerHTML = '<span style="color: #ef4444;">소문자 영문자와 숫자 조합 4-20자로 입력해주세요.</span>';
                resultDiv.className = 'error';
                adminIdChecked = true;
                adminIdValid = false;
                return;
            }
            
            // 중복확인 중
            checkBtn.disabled = true;
            checkBtn.textContent = '확인 중...';
            resultDiv.innerHTML = '<span style="color: #6b7280;">확인 중...</span>';
            resultDiv.className = 'checking';
            
            fetch(`/MVNO/api/check-admin-duplicate.php?type=user_id&value=${encodeURIComponent(userId)}`)
                .then(response => response.json())
                .then(data => {
                    checkBtn.disabled = false;
                    checkBtn.textContent = '중복확인';
                    
                    if (data.success && !data.duplicate) {
                        resultDiv.innerHTML = '<span style="color: #10b981;">✓ ' + data.message + '</span>';
                        resultDiv.className = 'success';
                        adminUserIdInput.style.borderColor = '#10b981';
                        adminIdChecked = true;
                        adminIdValid = true;
                    } else {
                        resultDiv.innerHTML = '<span style="color: #ef4444;">✗ ' + data.message + '</span>';
                        resultDiv.className = 'error';
                        adminUserIdInput.style.borderColor = '#ef4444';
                        adminIdChecked = true;
                        adminIdValid = false;
                    }
                })
                .catch(error => {
                    checkBtn.disabled = false;
                    checkBtn.textContent = '중복확인';
                    resultDiv.innerHTML = '<span style="color: #ef4444;">확인 중 오류가 발생했습니다.</span>';
                    resultDiv.className = 'error';
                    console.error('Error:', error);
                });
        }
        
        // 폼 제출 시 중복확인 체크 및 휴대폰 번호 검증
        document.getElementById('addSubAdminForm').addEventListener('submit', function(e) {
            if (!adminIdChecked) {
                e.preventDefault();
                alert('아이디 중복확인을 해주세요.');
                adminUserIdInput.focus();
                return false;
            }
            if (!adminIdValid) {
                e.preventDefault();
                alert('사용 가능한 아이디를 입력해주세요.');
                adminUserIdInput.focus();
                return false;
            }
            
            // 휴대폰 번호 형식 검증
            const phonePattern = /^010-\d{4}-\d{4}$/;
            const phoneValue = adminPhoneInput ? adminPhoneInput.value.trim() : '';
            if (!phoneValue || !phonePattern.test(phoneValue)) {
                e.preventDefault();
                alert('휴대폰 번호를 올바르게 입력해주세요. (예: 010-1234-5678)');
                if (adminPhoneInput) {
                    adminPhoneInput.focus();
                    adminPhoneInput.style.borderColor = '#ef4444';
                }
                return false;
            }
            
            // 비밀번호 확인 검증
            const password = adminPasswordInput ? adminPasswordInput.value : '';
            const passwordConfirm = adminPasswordConfirmInput ? adminPasswordConfirmInput.value : '';
            if (!password || password.length < 8) {
                e.preventDefault();
                alert('비밀번호는 최소 8자 이상 입력해주세요.');
                if (adminPasswordInput) {
                    adminPasswordInput.focus();
                }
                return false;
            }
            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('비밀번호가 일치하지 않습니다.');
                if (adminPasswordConfirmInput) {
                    adminPasswordConfirmInput.focus();
                    adminPasswordConfirmInput.style.borderColor = '#ef4444';
                }
                return false;
            }
        });
    </script>
    
    <!-- 메인 콘텐츠 -->
    <main class="admin-main">
        <div class="admin-content">

