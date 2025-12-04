<?php
/**
 * 관리자 로그인 페이지 / 대시보드
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 기본 admin 계정이 없으면 생성
$adminUser = getUserById('admin');
if (!$adminUser) {
    registerDirectUser('admin', 'admin', 'admin@moyo.com', '관리자', 'admin');
}

// POST 요청으로 로그인 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['password'])) {
    $userId = $_POST['user_id'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($userId) && !empty($password)) {
        $result = loginDirectUser($userId, $password);
        if ($result['success']) {
            // 관리자/판매자만 로그인 가능하도록 확인
            $user = getCurrentUser();
            if ($user && (isAdmin() || isSeller())) {
                header('Location: /MVNO/admin/');
                exit;
            } else {
                // 일반 사용자는 로그아웃하고 메인 페이지로
                logoutUser();
                $loginError = '관리자 또는 판매자만 접근 가능합니다.';
            }
        } else {
            $loginError = $result['message'] ?? '로그인에 실패했습니다.';
        }
    } else {
        $loginError = '아이디와 비밀번호를 입력해주세요.';
    }
}

// 로그인 상태 확인
$currentUser = getCurrentUser();
$isLoggedIn = isLoggedIn() && $currentUser;
$isAdminOrSeller = $isLoggedIn && (isAdmin() || isSeller());

// 로그인되지 않았거나 일반 사용자인 경우 로그인 페이지 표시
if (!$isAdminOrSeller) {
    $loginError = $loginError ?? '';
    ?>
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>관리자 로그인 - 모요</title>
        <link rel="stylesheet" href="/MVNO/assets/css/style.css">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .login-container {
                background: white;
                border-radius: 16px;
                padding: 48px;
                max-width: 400px;
                width: 100%;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }
            
            .login-header {
                text-align: center;
                margin-bottom: 32px;
            }
            
            .login-header h1 {
                font-size: 28px;
                font-weight: 700;
                color: #1f2937;
                margin-bottom: 8px;
            }
            
            .login-header p {
                font-size: 14px;
                color: #6b7280;
            }
            
            .error-message {
                padding: 12px 16px;
                background: #fee2e2;
                color: #991b1b;
                border-radius: 8px;
                margin-bottom: 24px;
                font-size: 14px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-group label {
                display: block;
                font-size: 14px;
                font-weight: 600;
                color: #374151;
                margin-bottom: 8px;
            }
            
            .form-group input {
                width: 100%;
                padding: 12px 16px;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                font-size: 16px;
                transition: border-color 0.2s;
                box-sizing: border-box;
            }
            
            .form-group input:focus {
                outline: none;
                border-color: #6366f1;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            }
            
            .login-button {
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            
            .login-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
            }
            
            .login-button:active {
                transform: translateY(0);
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <h1>관리자 로그인</h1>
                <p>관리자 또는 판매자 계정으로 로그인하세요</p>
            </div>
            
            <?php if ($loginError): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($loginError); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="user_id">아이디</label>
                    <input type="text" id="user_id" name="user_id" value="admin" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" value="admin" required>
                </div>
                <button type="submit" class="login-button">로그인</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 로그인된 관리자/판매자인 경우 대시보드 표시
$pageTitle = '대시보드';
include __DIR__ . '/includes/admin-header.php';

require_once __DIR__ . '/../includes/data/analytics-functions.php';
require_once __DIR__ . '/../includes/data/phone-data.php';
require_once __DIR__ . '/../includes/data/plan-data.php';

// 사용자 데이터 가져오기
$usersData = getUsersData();
$allUsers = $usersData['users'] ?? [];

// 통신사폰 데이터
$mnoPhones = getPhonesData(100);
$mnoPhonesCount = count($mnoPhones);

// 알뜰폰 데이터
$mvnoPlans = getPlansData(100);
$mvnoPlansCount = count($mvnoPlans);

// 운영 관련 통계
$sellers = array_filter($allUsers, function($user) {
    return isset($user['role']) && $user['role'] === 'seller';
});
$pendingSellers = array_filter($sellers, function($seller) {
    return !isset($seller['seller_approved']) || $seller['seller_approved'] === false;
});

// 회원 통계
$totalUsers = count($allUsers);
$sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
$recentUsers = array_filter($allUsers, function($user) use ($sevenDaysAgo) {
    return isset($user['created_at']) && $user['created_at'] >= $sevenDaysAgo;
});
$recentUsersCount = count($recentUsers);

// 통계 데이터 가져오기
$todayStats = getTodayStats();
$yesterdayStats = getPeriodStats(date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day')));

// 전일 대비 증가율 계산
$pageviewsChange = 0;
if (isset($yesterdayStats['pageviews']) && $yesterdayStats['pageviews'] > 0) {
    $pageviewsChange = round((($todayStats['pageviews'] - $yesterdayStats['pageviews']) / $yesterdayStats['pageviews']) * 100, 1);
}

$visitorsChange = 0;
if (isset($yesterdayStats['visitors']) && $yesterdayStats['visitors'] > 0) {
    $visitorsChange = round((($todayStats['visitors'] - $yesterdayStats['visitors']) / $yesterdayStats['visitors']) * 100, 1);
}
?>

<style>
    .dashboard-container {
        width: 100%;
        padding: 0;
    }
    
    .dashboard-row {
        display: grid;
        gap: 20px;
        margin-bottom: 20px;
        padding: 0 32px;
    }
    
    .dashboard-row-full {
        grid-template-columns: 1fr;
    }
    
    .dashboard-row-split {
        grid-template-columns: 1fr 1fr;
    }
    
    .dashboard-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        border: 1px solid #e2e8f0;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .dashboard-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    }
    
    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .card-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .card-title-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .card-title-icon svg {
        width: 20px;
        height: 20px;
        stroke: white;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
        fill: none;
    }
    
    .card-title-icon.mno {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .card-title-icon.mvno {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }
    
    .card-title-icon.event {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }
    
    .card-title-icon.operations {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .card-title-icon.stats {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    
    .card-link {
        font-size: 14px;
        color: #6366f6;
        text-decoration: none;
        font-weight: 500;
    }
    
    .card-link:hover {
        text-decoration: underline;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }
    
    .stat-item {
        background: #f8fafc;
        border-radius: 8px;
        padding: 16px;
        border: 1px solid #e2e8f0;
    }
    
    .stat-item-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-item-value {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .stat-item-change {
        font-size: 11px;
        margin-top: 4px;
        color: #64748b;
    }
    
    .stat-item-change.positive {
        color: #10b981;
    }
    
    .stat-item-change.negative {
        color: #ef4444;
    }
    
    .product-list {
        list-style: none;
        max-height: 300px;
        overflow-y: auto;
    }
    
    .product-item {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .product-item:last-child {
        border-bottom: none;
    }
    
    .product-name {
        font-size: 14px;
        color: #475569;
        font-weight: 500;
    }
    
    .product-meta {
        font-size: 12px;
        color: #94a3b8;
    }
    
    .operations-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .operation-card {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 8px;
        padding: 16px;
        border: 1px solid #e2e8f0;
        text-align: center;
        transition: all 0.2s;
    }
    
    .operation-card:hover {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        transform: translateY(-2px);
    }
    
    .operation-card-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    }
    
    .operation-card-icon svg {
        width: 24px;
        height: 24px;
        stroke: #475569;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
        fill: none;
    }
    
    .operation-card:hover .operation-card-icon {
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        transform: scale(1.05);
    }
    
    .operation-card-icon.pending {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    }
    
    .operation-card-icon.pending svg {
        stroke: #d97706;
    }
    
    .operation-card-icon.point-earn {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    }
    
    .operation-card-icon.point-earn svg {
        stroke: #2563eb;
    }
    
    .operation-card-icon.point-use {
        background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
    }
    
    .operation-card-icon.point-use svg {
        stroke: #db2777;
    }
    
    .operation-card-icon.user-add {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    }
    
    .operation-card-icon.user-add svg {
        stroke: #059669;
    }
    
    .operation-card-icon.user-remove {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    }
    
    .operation-card-icon.user-remove svg {
        stroke: #dc2626;
    }
    
    .operation-card-icon.users {
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    }
    
    .operation-card-icon.users svg {
        stroke: #6366f1;
    }
    
    .operation-card-label {
        font-size: 13px;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 4px;
    }
    
    .operation-card-value {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .event-list {
        list-style: none;
        max-height: 300px;
        overflow-y: auto;
    }
    
    .event-item {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .event-item:last-child {
        border-bottom: none;
    }
    
    .event-name {
        font-size: 14px;
        color: #475569;
        font-weight: 500;
    }
    
    .event-date {
        font-size: 12px;
        color: #94a3b8;
    }
    
    .stats-section {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        border: 1px solid #e2e8f0;
    }
    
    .stats-grid-large {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .stat-box-large {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 12px;
        padding: 24px;
        border: 1px solid #cbd5e1;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .stat-box-label {
        font-size: 13px;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-box-value {
        font-size: 32px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .stat-box-change {
        font-size: 12px;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .stat-box-change.positive {
        color: #10b981;
    }
    
    .stat-box-change.negative {
        color: #ef4444;
    }
    
    .stat-box-change.neutral {
        color: #64748b;
    }
</style>

<div class="dashboard-container">
    <!-- 첫 줄: 통신사폰 관련 내용 -->
    <div class="dashboard-row dashboard-row-full" style="padding-top: 32px;">
        <div class="dashboard-card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-title-icon mno">
                        <svg viewBox="0 0 24 24">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </div>
                    <span>통신사폰 현황</span>
                </div>
                <a href="/MVNO/admin/products/mno-list.php" class="card-link">전체보기 →</a>
            </div>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-item-label">등록된 상품</div>
                    <div class="stat-item-value"><?php echo number_format($mnoPhonesCount); ?></div>
                    <div class="stat-item-change">통신사폰 상품 수</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 둘째 줄: 알뜰폰 관련 내용 -->
    <div class="dashboard-row dashboard-row-full">
        <div class="dashboard-card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-title-icon mvno">
                        <svg viewBox="0 0 24 24">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                    </div>
                    <span>알뜰폰 현황</span>
                </div>
                <a href="/MVNO/admin/products/mvno-list.php" class="card-link">전체보기 →</a>
            </div>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-item-label">등록된 상품</div>
                    <div class="stat-item-value"><?php echo number_format($mvnoPlansCount); ?></div>
                    <div class="stat-item-change">알뜰폰 요금제 수</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 셋째 줄: 왼쪽 이벤트, 오른쪽 운영 현황 -->
    <div class="dashboard-row dashboard-row-split">
        <!-- 왼쪽: 이벤트 -->
        <div class="dashboard-card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-title-icon event">
                        <svg viewBox="0 0 24 24">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                    </div>
                    <span>이벤트</span>
                </div>
                <a href="/MVNO/admin/content/event-manage.php" class="card-link">전체보기 →</a>
            </div>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-item-label">진행 중인 이벤트</div>
                    <div class="stat-item-value">-</div>
                    <div class="stat-item-change">현재 진행 중</div>
                </div>
                <div class="stat-item">
                    <div class="stat-item-label">예정된 이벤트</div>
                    <div class="stat-item-value">-</div>
                    <div class="stat-item-change">예정된 이벤트</div>
                </div>
            </div>
        </div>
        
        <!-- 오른쪽: 운영 현황 -->
        <div class="dashboard-card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-title-icon operations">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3m15.364 6.364l-4.243-4.243m-4.242 0L5.636 17.364m12.728 0l-4.243-4.243m-4.242 0L5.636 6.636"/>
                        </svg>
                    </div>
                    <span>운영 현황</span>
                </div>
            </div>
            <div class="operations-grid">
                <div class="operation-card">
                    <div class="operation-card-icon pending">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="operation-card-label">판매승인 요청</div>
                    <div class="operation-card-value"><?php echo count($pendingSellers); ?></div>
                </div>
                <div class="operation-card">
                    <div class="operation-card-icon point-earn">
                        <svg viewBox="0 0 24 24">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="operation-card-label">포인트 적립</div>
                    <div class="operation-card-value">-</div>
                </div>
                <div class="operation-card">
                    <div class="operation-card-icon point-use">
                        <svg viewBox="0 0 24 24">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="operation-card-label">포인트 사용</div>
                    <div class="operation-card-value">-</div>
                </div>
                <div class="operation-card">
                    <div class="operation-card-icon user-add">
                        <svg viewBox="0 0 24 24">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <line x1="20" y1="8" x2="20" y2="14"/>
                            <line x1="23" y1="11" x2="17" y2="11"/>
                        </svg>
                    </div>
                    <div class="operation-card-label">회원가입</div>
                    <div class="operation-card-value"><?php echo $recentUsersCount; ?></div>
                </div>
                <div class="operation-card">
                    <div class="operation-card-icon user-remove">
                        <svg viewBox="0 0 24 24">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <line x1="18" y1="8" x2="23" y2="13"/>
                            <line x1="23" y1="8" x2="18" y2="13"/>
                        </svg>
                    </div>
                    <div class="operation-card-label">회원탈퇴</div>
                    <div class="operation-card-value">-</div>
                </div>
                <div class="operation-card">
                    <div class="operation-card-icon users">
                        <svg viewBox="0 0 24 24">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div class="operation-card-label">총 회원</div>
                    <div class="operation-card-value"><?php echo number_format($totalUsers); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 네째 줄: 통계 -->
    <div class="dashboard-row dashboard-row-full">
        <div class="stats-section">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-title-icon stats">
                        <svg viewBox="0 0 24 24">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                    </div>
                    <span>사이트 통계</span>
                </div>
            </div>
            <div class="stats-grid-large">
                <div class="stat-box-large">
                    <div class="stat-box-label">오늘 페이지뷰</div>
                    <div class="stat-box-value"><?php echo number_format($todayStats['pageviews'] ?? 0); ?></div>
                    <?php if ($pageviewsChange != 0): ?>
                        <div class="stat-box-change <?php echo $pageviewsChange > 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $pageviewsChange > 0 ? '↑' : '↓'; ?> <?php echo abs($pageviewsChange); ?>% 전일 대비
                        </div>
                    <?php else: ?>
                        <div class="stat-box-change neutral">전일 대비 변화 없음</div>
                    <?php endif; ?>
                </div>
                
                <div class="stat-box-large">
                    <div class="stat-box-label">오늘 방문자</div>
                    <div class="stat-box-value"><?php echo number_format($todayStats['visitors'] ?? 0); ?></div>
                    <?php if ($visitorsChange != 0): ?>
                        <div class="stat-box-change <?php echo $visitorsChange > 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $visitorsChange > 0 ? '↑' : '↓'; ?> <?php echo abs($visitorsChange); ?>% 전일 대비
                        </div>
                    <?php else: ?>
                        <div class="stat-box-change neutral">전일 대비 변화 없음</div>
                    <?php endif; ?>
                </div>
                
                <div class="stat-box-large">
                    <div class="stat-box-label">오늘 이벤트</div>
                    <div class="stat-box-value"><?php echo number_format($todayStats['events'] ?? 0); ?></div>
                    <div class="stat-box-change neutral">클릭 및 상호작용</div>
                </div>
                
                <div class="stat-box-large">
                    <div class="stat-box-label">평균 세션 시간</div>
                    <div class="stat-box-value"><?php 
                        $avgSessionTime = getAverageSessionTime(30);
                        echo $avgSessionTime > 0 ? formatSessionTime($avgSessionTime) : '-';
                    ?></div>
                    <div class="stat-box-change neutral">최근 30일 평균</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
