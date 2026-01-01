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
    // 판매자명 체크 (seller-edit.php에서 체크한 경우에도 판매자명 확인)
$sellerName = $currentUser['company_name'] ?? ($currentUser['seller_name'] ?? '');
    $hasSellerName = !empty(trim($sellerName));
    $showSellerNameModal = !$hasSellerName;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>판매자 센터 - 유심킹</title>
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
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 64px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }
        
        .seller-top-header-left {
            display: flex;
            align-items: center;
        }
        
        .seller-top-header-logo {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            text-decoration: none;
            letter-spacing: -0.5px;
            transition: opacity 0.2s;
        }
        
        .seller-top-header-logo:hover {
            opacity: 0.9;
        }
        
        .seller-top-header-right {
            display: flex;
            align-items: center;
            gap: 20px;
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
        }
        
        .seller-top-header-link:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }
        
        /* 사이드바 */
        .seller-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: #f1f5f9;
            position: fixed;
            height: calc(100vh - 64px);
            top: 64px;
            left: 0;
            overflow-y: auto;
            padding: 32px 0;
            box-shadow: 4px 0 16px rgba(0, 0, 0, 0.15);
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
            margin-bottom: 0;
            padding-bottom: 28px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 28px;
            position: relative;
        }
        
        .menu-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .menu-section::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 24px;
            right: 24px;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.1) 50%, transparent 100%);
        }
        
        .menu-section:last-child::after {
            display: none;
        }
        
        .menu-section-title {
            font-size: 12px;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 0 24px;
            margin-bottom: 12px;
            position: relative;
            display: flex;
            align-items: center;
        }
        
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 14px 24px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 15px;
            font-weight: 600;
            border-left: 4px solid transparent;
            position: relative;
            margin: 2px 0;
            border-radius: 0 8px 8px 0;
        }
        
        .menu-item::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 0;
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.2) 0%, rgba(139, 92, 246, 0.15) 100%);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 0 8px 8px 0;
        }
        
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%);
            color: #ffffff;
            padding-left: 28px;
            transform: translateX(4px);
        }
        
        .menu-item:hover::before {
            width: 4px;
            background: linear-gradient(180deg, #6366f1 0%, #8b5cf6 100%);
        }
        
        .menu-item-sub:hover {
            transform: translateX(2px);
        }
        
        .menu-item.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.25) 0%, rgba(139, 92, 246, 0.18) 100%);
            color: #ffffff;
            border-left-color: #6366f1;
            box-shadow: inset 0 0 20px rgba(99, 102, 241, 0.15), 0 2px 8px rgba(99, 102, 241, 0.1);
            font-weight: 700;
        }
        
        .menu-item.active::before {
            width: 100%;
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.25) 0%, rgba(139, 92, 246, 0.18) 100%);
        }
        
        .menu-item-sub {
            padding-left: 64px;
            font-size: 15px;
            font-weight: 500;
            position: relative;
        }
        
        .menu-item-sub .menu-item-icon {
            width: 18px;
            height: 18px;
            margin-right: 10px;
        }
        
        .menu-item-sub::after {
            content: "";
            position: absolute;
            left: 40px;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.35);
            transition: all 0.3s ease;
        }
        
        .menu-item-sub:hover::after {
            background: rgba(99, 102, 241, 0.8);
            width: 7px;
            height: 7px;
            box-shadow: 0 0 6px rgba(99, 102, 241, 0.4);
        }
        
        .menu-item-sub.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.2) 0%, rgba(139, 92, 246, 0.15) 100%);
            color: #ffffff;
            border-left-color: #6366f1;
            font-weight: 600;
        }
        
        .menu-item-sub.active::after {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            width: 8px;
            height: 8px;
            box-shadow: 0 0 8px rgba(99, 102, 241, 0.5);
        }
        
        .menu-item-sub.active::before {
            width: 100%;
        }
        
        .menu-sub-category {
            padding: 10px 24px;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.45);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 12px;
            margin-bottom: 8px;
            position: relative;
            padding-left: 40px;
        }
        
        .menu-sub-category::before {
            content: "";
            position: absolute;
            left: 24px;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.6);
            box-shadow: 0 0 4px rgba(99, 102, 241, 0.3);
        }
        
        .menu-item-icon {
            width: 24px;
            height: 24px;
            margin-right: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        
        .menu-item:hover .menu-item-icon {
            transform: scale(1.1);
        }
        
        .menu-item-icon svg {
            width: 100%;
            height: 100%;
            stroke: currentColor;
            stroke-width: 2.5;
        }
        
        .menu-item.active .menu-item-icon svg {
            filter: drop-shadow(0 0 4px rgba(99, 102, 241, 0.5));
        }
        
        /* 메인 컨텐츠 */
        .seller-content-wrapper {
            margin-left: 280px;
            margin-top: 64px;
            min-height: calc(100vh - 64px);
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
                <a href="/MVNO/seller/profile.php" class="seller-info">
                    <?php echo htmlspecialchars($currentUser['company_name']); ?> (<?php echo htmlspecialchars($currentUser['user_id']); ?>)
                </a>
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
        
        <!-- 광고 관리 -->
        <div class="menu-section">
            <div class="menu-section-title">광고 관리</div>
            <a href="/MVNO/seller/advertisement/list.php" class="menu-item <?php echo ($currentPage === 'list.php' && strpos($_SERVER['REQUEST_URI'], '/advertisement/') !== false) ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </span>
                광고 내역
            </a>
        </div>
        
        <!-- 예치금 관리 -->
        <div class="menu-section">
            <div class="menu-section-title">예치금 관리</div>
            <a href="/MVNO/seller/deposit/charge.php" class="menu-item <?php echo ($currentPage === 'charge.php' && strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="2" x2="12" y2="22"/>
                        <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                    </svg>
                </span>
                예치금 충전
            </a>
            <a href="/MVNO/seller/deposit/history.php" class="menu-item <?php echo ($currentPage === 'history.php' && strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </span>
                예치금 내역
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">주문 관리</div>
            <a href="/MVNO/seller/orders/mno-sim.php" class="menu-item menu-item-sub <?php echo (basename($_SERVER['PHP_SELF']) === 'mno-sim.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                </span>
                통신사단독유심
            </a>
            <a href="/MVNO/seller/orders/mvno.php" class="menu-item menu-item-sub <?php echo (basename($_SERVER['PHP_SELF']) === 'mvno.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                </span>
                알뜰폰
            </a>
            <a href="/MVNO/seller/orders/mno.php" class="menu-item menu-item-sub <?php echo (basename($_SERVER['PHP_SELF']) === 'mno.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                </span>
                통신사폰
            </a>
            <a href="/MVNO/seller/orders/internet.php" class="menu-item menu-item-sub <?php echo (basename($_SERVER['PHP_SELF']) === 'internet.php' && strpos($_SERVER['REQUEST_URI'], '/orders/') !== false) ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                    </svg>
                </span>
                인터넷
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">상품 관리</div>
            <a href="/MVNO/seller/products/mno-sim-list.php" class="menu-item menu-item-sub <?php echo $currentPage === 'mno-sim-list.php' ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                </span>
                통신사단독유심
            </a>
            <a href="/MVNO/seller/products/mvno-list.php" class="menu-item menu-item-sub <?php echo $currentPage === 'mvno-list.php' ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                </span>
                알뜰폰
            </a>
            <a href="/MVNO/seller/products/mno-list.php" class="menu-item menu-item-sub <?php echo $currentPage === 'mno-list.php' ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                </span>
                통신사폰
            </a>
            <a href="/MVNO/seller/products/internet-list.php" class="menu-item menu-item-sub <?php echo $currentPage === 'internet-list.php' ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                    </svg>
                </span>
                인터넷
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">고객 지원</div>
            <a href="/MVNO/seller/inquiry/inquiry-list.php" class="menu-item <?php echo (strpos($currentPage, 'inquiry') !== false) ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        <path d="M13 8H7"/>
                        <path d="M17 12H7"/>
                    </svg>
                </span>
                1:1 문의
            </a>
            <a href="/MVNO/seller/notice/" class="menu-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/seller/notice/') !== false) ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </span>
                공지사항
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">상품 등록</div>
            <a href="/MVNO/seller/products/mno-sim.php" class="menu-item menu-item-sub <?php echo ($currentPage === 'mno-sim.php' && strpos($_SERVER['REQUEST_URI'], '/products/') !== false && strpos($_SERVER['REQUEST_URI'], '/orders/') === false) ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                </span>
                통신사단독유심
            </a>
            <a href="/MVNO/seller/products/mvno.php" class="menu-item menu-item-sub <?php echo ($currentPage === 'mvno.php' && strpos($_SERVER['REQUEST_URI'], '/products/') !== false && strpos($_SERVER['REQUEST_URI'], '/orders/') === false) ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                </span>
                알뜰폰
            </a>
            <a href="/MVNO/seller/products/mno.php" class="menu-item menu-item-sub <?php echo ($currentPage === 'mno.php' && strpos($_SERVER['REQUEST_URI'], '/products/') !== false && strpos($_SERVER['REQUEST_URI'], '/orders/') === false) ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                </span>
                통신사폰
            </a>
            <a href="/MVNO/seller/products/internet.php" class="menu-item menu-item-sub <?php echo ($currentPage === 'internet.php' && strpos($_SERVER['REQUEST_URI'], '/products/') !== false && strpos($_SERVER['REQUEST_URI'], '/orders/') === false) ? 'active' : ''; ?>">
                <span class="menu-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                    </svg>
                </span>
                인터넷
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">통계</div>
            <a href="/MVNO/seller/statistics/" class="menu-item <?php echo ($currentPage === 'statistics' || strpos($_SERVER['REQUEST_URI'] ?? '', '/seller/statistics/') !== false) ? 'active' : ''; ?>">
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
    <div class="seller-content-wrapper">
        <div class="seller-content">

