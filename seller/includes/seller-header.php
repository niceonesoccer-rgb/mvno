<?php
/**
 * 판매자 페이지 공통 헤더
 * 사이드바 네비게이션 포함
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';

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
} else {
    // 일반적인 경우: 인증 체크 수행
    $currentUser = getCurrentUser();
    if (!$currentUser || $currentUser['role'] !== 'seller') {
        header('Location: /MVNO/seller/login.php');
        exit;
    }
    
    // 판매자 승인 상태 확인 (승인불가 상태도 waiting.php로 이동)
    $approvalStatus = $currentUser['approval_status'] ?? 'pending';
    if ($approvalStatus !== 'approved') {
        // 승인 대기 중이거나 보류, 거부 상태인 경우
        header('Location: /MVNO/seller/waiting.php');
        exit;
    }

    // 탈퇴 요청 상태 확인 (탈퇴 요청 시 로그인 불가)
    if (isset($currentUser['withdrawal_requested']) && $currentUser['withdrawal_requested'] === true) {
        header('Location: /MVNO/seller/waiting.php');
        exit;
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>판매자 센터 - 모요</title>
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
        .seller-top-header {
            width: 100%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-bottom: 1px solid #047857;
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
        
        .seller-top-header-left {
            display: flex;
            align-items: center;
        }
        
        .seller-top-header-logo {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            text-decoration: none;
        }
        
        .seller-top-header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .seller-info {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.95);
            font-weight: 500;
            padding: 6px 12px;
            border-right: 1px solid rgba(255, 255, 255, 0.3);
            margin-right: 8px;
        }
        
        .seller-top-header-link {
            font-size: 14px;
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        
        .seller-top-header-link:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* 사이드바 */
        .seller-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #065f46 0%, #047857 50%, #059669 100%);
            color: #f1f5f9;
            position: fixed;
            height: calc(100vh - 60px);
            top: 60px;
            left: 0;
            overflow-y: auto;
            padding: 24px 0;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
        }
        
        .seller-sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .seller-sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .seller-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .seller-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        .menu-section {
            margin-bottom: 32px;
        }
        
        .menu-section-title {
            font-size: 12px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0 24px;
            margin-bottom: 12px;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.2s;
            font-size: 14px;
            border-left: 3px solid transparent;
        }
        
        .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
        
        .menu-item.active {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-left-color: #fbbf24;
        }
        
        .menu-item-sub {
            padding-left: 56px;
            font-size: 13px;
        }
        
        .menu-item-sub.active {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border-left-color: #fbbf24;
        }
        
        .menu-item-icon {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .menu-item-icon svg {
            width: 100%;
            height: 100%;
            stroke: currentColor;
        }
        
        /* 메인 컨텐츠 */
        .seller-content-wrapper {
            margin-left: 260px;
            margin-top: 60px;
            min-height: calc(100vh - 60px);
            padding: 0;
        }
        
        .seller-content {
            padding: 32px;
        }
        
        @media (max-width: 1024px) {
            .seller-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .seller-sidebar.open {
                transform: translateX(0);
            }
            
            .seller-content-wrapper {
                margin-left: 0;
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
    <!-- 상단 헤더 -->
    <header class="seller-top-header">
        <div class="seller-top-header-left">
            <a href="/MVNO/seller/" class="seller-top-header-logo">판매자 센터</a>
        </div>
        <div class="seller-top-header-right">
            <?php if ($currentUser && isset($currentUser['user_id']) && isset($currentUser['company_name'])): ?>
                <span class="seller-info">
                    <?php echo htmlspecialchars($currentUser['company_name']); ?> (<?php echo htmlspecialchars($currentUser['user_id']); ?>)
                </span>
            <?php endif; ?>
            <a href="/MVNO/" class="seller-top-header-link">사이트보기</a>
            <a href="/MVNO/seller/logout.php" class="seller-top-header-link">로그아웃</a>
        </div>
    </header>
    
    <!-- 사이드바 -->
    <aside class="seller-sidebar">
        <div class="menu-section">
            <div class="menu-section-title">대시보드</div>
            <a href="/MVNO/seller/" class="menu-item <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                </span>
                대시보드
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">상품 관리</div>
            <div style="margin-top: 8px;">
                <div style="padding: 8px 24px; font-size: 12px; color: rgba(255, 255, 255, 0.5); font-weight: 600;">
                    등록 상품
                </div>
                <a href="/MVNO/seller/products/mvno-list.php" class="menu-item menu-item-sub <?php echo $currentPage === 'mvno-list.php' ? 'active' : ''; ?>">
                    알뜰폰
                </a>
                <a href="/MVNO/seller/products/mno-list.php" class="menu-item menu-item-sub <?php echo $currentPage === 'mno-list.php' ? 'active' : ''; ?>">
                    통신사폰
                </a>
                <a href="/MVNO/seller/products/internet-list.php" class="menu-item menu-item-sub <?php echo $currentPage === 'internet-list.php' ? 'active' : ''; ?>">
                    인터넷
                </a>
            </div>
            <div style="margin-top: 8px;">
                <div style="padding: 8px 24px; font-size: 12px; color: rgba(255, 255, 255, 0.5); font-weight: 600;">
                    상품 등록
                </div>
                <a href="/MVNO/seller/products/mvno.php" class="menu-item menu-item-sub <?php echo $currentPage === 'mvno.php' ? 'active' : ''; ?>">
                    알뜰폰
                </a>
                <a href="/MVNO/seller/products/mno.php" class="menu-item menu-item-sub <?php echo $currentPage === 'mno.php' ? 'active' : ''; ?>">
                    통신사폰
                </a>
                <a href="/MVNO/seller/products/internet.php" class="menu-item menu-item-sub <?php echo $currentPage === 'internet.php' ? 'active' : ''; ?>">
                    인터넷
                </a>
            </div>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">주문 관리</div>
            <a href="/MVNO/seller/orders/" class="menu-item">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"/>
                        <circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                </span>
                주문 관리
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">통계</div>
            <a href="/MVNO/seller/statistics/" class="menu-item">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                </span>
                판매 통계
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">계정</div>
            <a href="/MVNO/seller/profile.php" class="menu-item <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </span>
                내정보
            </a>
        </div>
    </aside>
    
    <!-- 메인 컨텐츠 영역 -->
    <div class="seller-content-wrapper">
        <div class="seller-content">

