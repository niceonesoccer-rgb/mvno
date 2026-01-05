<?php
/**
 * 관리자 대시보드
 */

require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/monitor.php';

$pdo = getDBConnection();

// 오늘, 일주일, 한달 날짜 계산
$today = date('Y-m-d 00:00:00');
$weekAgo = date('Y-m-d 00:00:00', strtotime('-7 days'));
$monthAgo = date('Y-m-d 00:00:00', strtotime('-30 days'));

// 카테고리별 주문수 통계
$orderStats = [
    'mno' => ['today' => 0, 'week' => 0, 'month' => 0],
    'mvno' => ['today' => 0, 'week' => 0, 'month' => 0],
    'internet' => ['today' => 0, 'week' => 0, 'month' => 0],
    'mno_sim' => ['today' => 0, 'week' => 0, 'month' => 0]
];

// 카테고리별 상품등록수 통계
$productStats = [
    'mno' => ['today' => 0, 'week' => 0, 'month' => 0],
    'mvno' => ['today' => 0, 'week' => 0, 'month' => 0],
    'internet' => ['today' => 0, 'week' => 0, 'month' => 0],
    'mno_sim' => ['today' => 0, 'week' => 0, 'month' => 0]
];

// 판매자 통계
$sellerStats = [
    'pending' => 0,      // 신청자
    'updated' => 0,      // 업데이트
    'withdrawal' => 0    // 탈퇴요청
];

// 광고 통계
$adStats = [
    'today_count' => 0,
    'today_amount' => 0,
    'week_amount' => 0,
    'month_amount' => 0,
    'deposit_pending' => 0
];

// 문의 통계
$inquiryStats = [
    'member_qna' => 0,      // 회원 1:1 문의 답변대기
    'seller_inquiry' => 0   // 판매자 1:1 문의 답변대기
];

// 고객 적립포인트 (전체 고객 포인트 합계)
$totalCustomerPoints = 0;

// 접속모니터링 통계
$connectionStats = [
    'current' => 0,          // 현재 동시 접속 수
    'recent_5min' => 0,      // 최근 5분 접속 수
    'recent_1hour' => 0,     // 최근 1시간 접속 수
    'today' => 0             // 오늘 총 접속 수
];

try {
    $monitor = new ConnectionMonitor();
    $connectionStats['current'] = $monitor->getCurrentConnections();
    $recent5min = $monitor->getRecentStats(5);
    $connectionStats['recent_5min'] = $recent5min['total'];
    $recent1hour = $monitor->getRecentStats(60);
    $connectionStats['recent_1hour'] = $recent1hour['total'];
    
    // 오늘 총 접속 수 계산
    $todayStart = strtotime('today');
    $logFile = __DIR__ . '/../logs/connections.log';
    if (file_exists($logFile)) {
        $lines = file($logFile);
        $todayCount = 0;
        foreach ($lines as $line) {
            $data = json_decode(trim($line), true);
            if ($data && isset($data['time']) && $data['time'] >= $todayStart) {
                $todayCount++;
            }
        }
        $connectionStats['today'] = $todayCount;
    }
} catch (Exception $e) {
    error_log('접속모니터링 통계 조회 오류: ' . $e->getMessage());
}

if ($pdo) {
    try {
        // 카테고리별 주문수 통계
        $categories = ['mno', 'mvno', 'internet', 'mno_sim'];
        foreach ($categories as $category) {
            // 오늘
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM product_applications a
                INNER JOIN products p ON a.product_id = p.id
                WHERE p.product_type = :type 
                AND DATE(a.created_at) = CURDATE()
            ");
            $stmt->execute([':type' => $category === 'mno_sim' ? 'mno-sim' : $category]);
            $orderStats[$category]['today'] = (int)$stmt->fetchColumn();
            
            // 일주일
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM product_applications a
                INNER JOIN products p ON a.product_id = p.id
                WHERE p.product_type = :type 
                AND a.created_at >= :week_ago
            ");
            $stmt->execute([':type' => $category === 'mno_sim' ? 'mno-sim' : $category, ':week_ago' => $weekAgo]);
            $orderStats[$category]['week'] = (int)$stmt->fetchColumn();
            
            // 한달
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM product_applications a
                INNER JOIN products p ON a.product_id = p.id
                WHERE p.product_type = :type 
                AND a.created_at >= :month_ago
            ");
            $stmt->execute([':type' => $category === 'mno_sim' ? 'mno-sim' : $category, ':month_ago' => $monthAgo]);
            $orderStats[$category]['month'] = (int)$stmt->fetchColumn();
        }
        
        // 카테고리별 상품등록수 통계
        foreach ($categories as $category) {
            // 오늘
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM products 
                WHERE product_type = :type 
                AND DATE(created_at) = CURDATE()
                AND status != 'deleted'
            ");
            $stmt->execute([':type' => $category === 'mno_sim' ? 'mno-sim' : $category]);
            $productStats[$category]['today'] = (int)$stmt->fetchColumn();
            
            // 일주일
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM products 
                WHERE product_type = :type 
                AND created_at >= :week_ago
                AND status != 'deleted'
            ");
            $stmt->execute([':type' => $category === 'mno_sim' ? 'mno-sim' : $category, ':week_ago' => $weekAgo]);
            $productStats[$category]['week'] = (int)$stmt->fetchColumn();
            
            // 한달
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM products 
                WHERE product_type = :type 
                AND created_at >= :month_ago
                AND status != 'deleted'
            ");
            $stmt->execute([':type' => $category === 'mno_sim' ? 'mno-sim' : $category, ':month_ago' => $monthAgo]);
            $productStats[$category]['month'] = (int)$stmt->fetchColumn();
        }
        
        // 판매자 통계 - 신청자 (pending)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM users u
            LEFT JOIN seller_profiles sp ON u.user_id = sp.user_id
            WHERE u.role = 'seller' 
            AND u.seller_approved = 0
            AND (u.approval_status IS NULL OR u.approval_status = 'pending')
            AND (u.withdrawal_requested IS NULL OR u.withdrawal_requested = 0)
        ");
        $sellerStats['pending'] = (int)$stmt->fetchColumn();
        
        // 판매자 통계 - 업데이트 (info_updated = 1 AND info_checked_by_admin != 1)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM seller_profiles 
            WHERE info_updated = 1 
            AND (info_checked_by_admin IS NULL OR info_checked_by_admin = 0)
        ");
        $sellerStats['updated'] = (int)$stmt->fetchColumn();
        
        // 판매자 통계 - 탈퇴요청
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM users 
            WHERE role = 'seller' 
            AND withdrawal_requested = 1 
            AND (withdrawal_completed IS NULL OR withdrawal_completed = 0)
        ");
        $sellerStats['withdrawal'] = (int)$stmt->fetchColumn();
        
        // 광고 통계 - 오늘 광고신청 수
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM rotation_advertisements 
            WHERE DATE(created_at) = CURDATE()
        ");
        $stmt->execute();
        $adStats['today_count'] = (int)$stmt->fetchColumn();
        
        // 광고 통계 - 오늘 총금액 (부가세 포함, price는 공급가액이므로 1.1 곱하기)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(price * 1.1), 0) 
            FROM rotation_advertisements 
            WHERE DATE(created_at) = CURDATE()
        ");
        $stmt->execute();
        $adStats['today_amount'] = (float)$stmt->fetchColumn();
        
        // 광고 통계 - 일주일간 총금액
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(price * 1.1), 0) 
            FROM rotation_advertisements 
            WHERE created_at >= :week_ago
        ");
        $stmt->execute([':week_ago' => $weekAgo]);
        $adStats['week_amount'] = (float)$stmt->fetchColumn();
        
        // 광고 통계 - 한달간 총금액
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(price * 1.1), 0) 
            FROM rotation_advertisements 
            WHERE created_at >= :month_ago
        ");
        $stmt->execute([':month_ago' => $monthAgo]);
        $adStats['month_amount'] = (float)$stmt->fetchColumn();
        
        // 광고 통계 - 입금신청자 (pending 상태)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM deposit_requests 
            WHERE status = 'pending'
        ");
        $adStats['deposit_pending'] = (int)$stmt->fetchColumn();
        
        // 회원 1:1 문의 답변대기
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM qna 
            WHERE status = 'pending' OR answer IS NULL OR answer = ''
        ");
        $inquiryStats['member_qna'] = (int)$stmt->fetchColumn();
        
        // 판매자 1:1 문의 답변대기
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM seller_inquiries 
            WHERE status = 'pending'
        ");
        $inquiryStats['seller_inquiry'] = (int)$stmt->fetchColumn();
        
        // 고객 적립포인트 (전체 포인트 잔액 합계)
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(balance), 0) 
            FROM user_point_accounts
        ");
        $totalCustomerPoints = (int)$stmt->fetchColumn();
        
    } catch (PDOException $e) {
        error_log('대시보드 통계 조회 오류: ' . $e->getMessage());
    }
}

// 카테고리 라벨
$categoryLabels = [
    'mno' => '통신사폰',
    'mvno' => '알뜰폰',
    'internet' => '인터넷',
    'mno_sim' => '통신사단독유심'
];
?>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }
    
    .dashboard-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border: 1px solid #e2e8f0;
        transition: all 0.3s;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 180px;
    }
    
    .dashboard-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        text-decoration: none;
        color: inherit;
    }
    
    .dashboard-card.has-new {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        border: 2px solid #ef4444;
    }
    
    .dashboard-card.has-new:hover {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        border-color: #dc2626;
    }
    
    .dashboard-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    
    .dashboard-card-title {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .dashboard-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .dashboard-card-icon svg {
        width: 24px;
        height: 24px;
        stroke: white;
        fill: none;
        stroke-width: 2.5;
    }
    
    .dashboard-card-value {
        font-size: 36px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
        line-height: 1;
    }
    
    .dashboard-card-description {
        font-size: 13px;
        color: #94a3b8;
        margin-bottom: 12px;
    }
    
    .dashboard-card-stats {
        display: flex;
        gap: 16px;
        padding-top: 16px;
        border-top: 1px solid #e2e8f0;
    }
    
    .dashboard-card-stat {
        flex: 1;
    }
    
    .dashboard-card-stat-label {
        font-size: 11px;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    
    .dashboard-card-stat-value {
        font-size: 18px;
        font-weight: 600;
        color: #475569;
    }
    
    .dashboard-section {
        margin-bottom: 48px;
    }
    
    .dashboard-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 24px;
        margin-bottom: 32px;
    }
    
    .dashboard-row-section {
        display: flex;
        flex-direction: column;
    }
    
    .dashboard-row-section h2 {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 16px;
    }
    
    .dashboard-row-grid {
        display: grid;
        gap: 16px;
        align-items: stretch;
    }
    
    .dashboard-row-section:first-child .dashboard-row-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .dashboard-row-section:last-child .dashboard-row-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .dashboard-row-center {
        display: flex;
        flex-direction: column;
    }
    
    .dashboard-row-center .dashboard-card-wrapper {
        display: flex;
        align-items: stretch;
    }
    
    .dashboard-row-center .dashboard-card {
        flex: 1;
    }
    
    .dashboard-row-center h2 {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 16px;
    }
    
    @media (max-width: 1400px) {
        .dashboard-row {
            grid-template-columns: 1fr;
        }
    }
    
    .dashboard-section-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 3px solid #6366f1;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }
    
    .stat-item {
        background: #f8fafc;
        border-radius: 8px;
        padding: 12px 16px;
        border-left: 4px solid #6366f1;
    }
    
    .stat-item-label {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 4px;
    }
    
    .stat-item-value {
        font-size: 20px;
        font-weight: 600;
        color: #1e293b;
    }
</style>

<div class="dashboard-section">
    <h1 class="dashboard-section-title">대시보드</h1>
    
    <!-- 판매자 관리 & 문의 관리 -->
    <div class="dashboard-row">
        <!-- 판매자 관리 -->
        <div class="dashboard-row-section">
            <h2>판매자 관리</h2>
            <div class="dashboard-row-grid">
                <a href="/MVNO/admin/seller-approval.php?tab=pending" class="dashboard-card <?php echo $sellerStats['pending'] > 0 ? 'has-new' : ''; ?>">
                    <div class="dashboard-card-header">
                        <span class="dashboard-card-title">신청자</span>
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <svg viewBox="0 0 24 24">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </div>
                    </div>
                    <div class="dashboard-card-value"><?php echo number_format($sellerStats['pending']); ?></div>
                    <div class="dashboard-card-description">승인 대기 중인 판매자</div>
                </a>
                
                <a href="/MVNO/admin/seller-approval.php?tab=updated" class="dashboard-card <?php echo $sellerStats['updated'] > 0 ? 'has-new' : ''; ?>">
                    <div class="dashboard-card-header">
                        <span class="dashboard-card-title">업데이트</span>
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <svg viewBox="0 0 24 24">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="dashboard-card-value"><?php echo number_format($sellerStats['updated']); ?></div>
                    <div class="dashboard-card-description">정보 업데이트 대기</div>
                </a>
                
                <a href="/MVNO/admin/seller-approval.php?tab=withdrawal" class="dashboard-card <?php echo $sellerStats['withdrawal'] > 0 ? 'has-new' : ''; ?>">
                    <div class="dashboard-card-header">
                        <span class="dashboard-card-title">탈퇴요청</span>
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                            <svg viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                        </div>
                    </div>
                    <div class="dashboard-card-value"><?php echo number_format($sellerStats['withdrawal']); ?></div>
                    <div class="dashboard-card-description">탈퇴 요청 대기</div>
                </a>
            </div>
        </div>
        
        <!-- 입금신청자 -->
        <div class="dashboard-row-center">
            <h2>입금신청자</h2>
            <div class="dashboard-card-wrapper">
                <a href="/MVNO/admin/deposit/requests.php" class="dashboard-card <?php echo $adStats['deposit_pending'] > 0 ? 'has-new' : ''; ?>">
                    <div class="dashboard-card-header">
                        <span class="dashboard-card-title">입금신청자</span>
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);">
                            <svg viewBox="0 0 24 24">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                <line x1="1" y1="10" x2="23" y2="10"/>
                            </svg>
                        </div>
                    </div>
                    <div class="dashboard-card-value"><?php echo number_format($adStats['deposit_pending']); ?></div>
                    <div class="dashboard-card-description">입금 확인 대기</div>
                </a>
            </div>
        </div>
        
        <!-- 문의 관리 -->
        <div class="dashboard-row-section">
            <h2>문의 관리</h2>
            <div class="dashboard-row-grid">
                <a href="/MVNO/admin/content/qna-manage.php?status=pending" class="dashboard-card <?php echo $inquiryStats['member_qna'] > 0 ? 'has-new' : ''; ?>">
                    <div class="dashboard-card-header">
                        <span class="dashboard-card-title">회원 1:1 문의</span>
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                            <svg viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                    </div>
                    <div class="dashboard-card-value"><?php echo number_format($inquiryStats['member_qna']); ?></div>
                    <div class="dashboard-card-description">답변 대기 중인 문의</div>
                </a>
                
                <a href="/MVNO/admin/content/seller-inquiry-manage.php?status=pending" class="dashboard-card <?php echo $inquiryStats['seller_inquiry'] > 0 ? 'has-new' : ''; ?>">
                    <div class="dashboard-card-header">
                        <span class="dashboard-card-title">판매자 1:1 문의</span>
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
                            <svg viewBox="0 0 24 24">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                <path d="M13 8H7"/>
                                <path d="M17 12H7"/>
                            </svg>
                        </div>
                    </div>
                    <div class="dashboard-card-value"><?php echo number_format($inquiryStats['seller_inquiry']); ?></div>
                    <div class="dashboard-card-description">답변 대기 중인 문의</div>
                </a>
            </div>
        </div>
    </div>
    
    <!-- 접속모니터링 -->
    <div class="dashboard-section">
        <h2 style="font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 16px;">접속모니터링</h2>
        <div class="dashboard-grid">
            <a href="/MVNO/admin/monitor.php" class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="dashboard-card-title">현재 동시 접속</span>
                    <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);">
                        <svg viewBox="0 0 24 24">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                </div>
                <div class="dashboard-card-value"><?php echo number_format($connectionStats['current']); ?></div>
                <div class="dashboard-card-description">현재 활성 접속자 수</div>
            </a>
            
            <a href="/MVNO/admin/monitor.php" class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="dashboard-card-title">최근 5분 접속</span>
                    <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                </div>
                <div class="dashboard-card-value"><?php echo number_format($connectionStats['recent_5min']); ?></div>
                <div class="dashboard-card-description">최근 5분간 접속 수</div>
            </a>
            
            <a href="/MVNO/admin/monitor.php" class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="dashboard-card-title">최근 1시간 접속</span>
                    <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                </div>
                <div class="dashboard-card-value"><?php echo number_format($connectionStats['recent_1hour']); ?></div>
                <div class="dashboard-card-description">최근 1시간간 접속 수</div>
            </a>
            
            <a href="/MVNO/admin/monitor.php" class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="dashboard-card-title">오늘 총 접속</span>
                    <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                </div>
                <div class="dashboard-card-value"><?php echo number_format($connectionStats['today']); ?></div>
                <div class="dashboard-card-description">오늘 총 접속 수</div>
            </a>
        </div>
    </div>
    
    <!-- 카테고리별 주문수 -->
    <div class="dashboard-section">
        <h2 style="font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 16px;">카테고리별 주문수</h2>
        <div class="dashboard-grid">
            <?php foreach ($categoryLabels as $category => $label): ?>
            <a href="/MVNO/admin/orders/<?php echo $category === 'mno_sim' ? 'mno-sim-list.php' : ($category . '-list.php'); ?>" class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="dashboard-card-title"><?php echo htmlspecialchars($label); ?></span>
                    <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                        <svg viewBox="0 0 24 24">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                    </div>
                </div>
                <div class="dashboard-card-stats">
                    <div class="dashboard-card-stat">
                        <div class="dashboard-card-stat-label">오늘</div>
                        <div class="dashboard-card-stat-value"><?php echo number_format($orderStats[$category]['today']); ?></div>
                    </div>
                    <div class="dashboard-card-stat">
                        <div class="dashboard-card-stat-label">일주일</div>
                        <div class="dashboard-card-stat-value"><?php echo number_format($orderStats[$category]['week']); ?></div>
                    </div>
                    <div class="dashboard-card-stat">
                        <div class="dashboard-card-stat-label">한달</div>
                        <div class="dashboard-card-stat-value"><?php echo number_format($orderStats[$category]['month']); ?></div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- 광고 관리 -->
    <div class="dashboard-section">
        <h2 style="font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 16px;">광고 관리</h2>
        <div class="dashboard-grid">
            <a href="/MVNO/admin/advertisement/list.php" class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="dashboard-card-title">오늘 광고신청</span>
                    <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                        <svg viewBox="0 0 24 24">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </div>
                </div>
                <div class="dashboard-card-value"><?php echo number_format($adStats['today_count']); ?></div>
                <div class="dashboard-card-description">오늘 신청한 광고 수</div>
            </a>
            
            <a href="/MVNO/admin/advertisement/list.php" class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="dashboard-card-title">오늘 총금액</span>
                    <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <svg viewBox="0 0 24 24">
                            <line x1="12" y1="2" x2="12" y2="22"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                </div>
                <div class="dashboard-card-value"><?php echo number_format($adStats['today_amount']); ?>원</div>
                <div class="dashboard-card-description">오늘 신청한 광고 총액</div>
            </a>
            
            <a href="/MVNO/admin/advertisement/list.php" class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="dashboard-card-title">일주일간 총금액</span>
                    <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <svg viewBox="0 0 24 24">
                            <line x1="12" y1="2" x2="12" y2="22"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                </div>
                <div class="dashboard-card-value"><?php echo number_format($adStats['week_amount']); ?>원</div>
                <div class="dashboard-card-description">지난 7일간 광고 총액</div>
            </a>
            
            <a href="/MVNO/admin/advertisement/list.php" class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="dashboard-card-title">한달간 총금액</span>
                    <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <svg viewBox="0 0 24 24">
                            <line x1="12" y1="2" x2="12" y2="22"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                </div>
                <div class="dashboard-card-value"><?php echo number_format($adStats['month_amount']); ?>원</div>
                <div class="dashboard-card-description">지난 30일간 광고 총액</div>
            </a>
        </div>
    </div>
    
    <!-- 고객 적립포인트 -->
    <div class="dashboard-section">
        <h2 style="font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 16px;">고객 적립포인트</h2>
        <div class="dashboard-grid">
            <a href="/MVNO/admin/settings/customer-point-history.php" class="dashboard-card">
                <div class="dashboard-card-header">
                    <span class="dashboard-card-title">총 적립포인트</span>
                    <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                </div>
                <div class="dashboard-card-value"><?php echo number_format($totalCustomerPoints); ?>P</div>
                <div class="dashboard-card-description">전체 고객 포인트 합계</div>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
