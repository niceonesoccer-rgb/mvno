<?php
/**
 * 판매자 센터 메인 페이지
 * 경로: /MVNO/seller/
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/path-config.php';
require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/notice-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: ' . getAssetPath('/seller/login.php'));
    exit;
}

// 판매자 승인 체크 - 승인되지 않은 경우 waiting.php로 리다이렉트
$isApproved = isset($currentUser['seller_approved']) && $currentUser['seller_approved'] === true;
if (!$isApproved) {
    header('Location: ' . getAssetPath('/seller/waiting.php'));
    exit;
}

// 판매자 메인공지 가져오기 (승인된 판매자만)
$sellerBanner = null;
try {
    $sellerBanner = getSellerMainBanner();
    
    // 디버깅: 메인공지 조회 결과 로그
    if (isset($_GET['debug_banner'])) {
        error_log('=== 판매자 메인공지 디버깅 ===');
        error_log('조회된 메인공지: ' . ($sellerBanner ? '있음' : '없음'));
        if ($sellerBanner) {
            error_log('ID: ' . ($sellerBanner['id'] ?? 'N/A'));
            error_log('제목: ' . ($sellerBanner['title'] ?? 'N/A'));
            error_log('target_audience: ' . ($sellerBanner['target_audience'] ?? 'N/A'));
            error_log('show_on_main: ' . ($sellerBanner['show_on_main'] ?? 'N/A'));
            error_log('start_at: ' . ($sellerBanner['start_at'] ?? 'NULL'));
            error_log('end_at: ' . ($sellerBanner['end_at'] ?? 'NULL'));
            error_log('banner_type: ' . ($sellerBanner['banner_type'] ?? 'N/A'));
            error_log('image_url: ' . ($sellerBanner['image_url'] ?? 'NULL'));
            error_log('link_url: ' . ($sellerBanner['link_url'] ?? 'NULL'));
        } else {
            // 메인공지가 없는 경우 원인 파악
            $pdo = getDBConnection();
            if ($pdo) {
                $currentDate = date('Y-m-d');
                $allSellerNotices = $pdo->query("SELECT id, title, target_audience, show_on_main, start_at, end_at FROM notices WHERE target_audience = 'seller' ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                error_log('판매자 공지사항 총 개수: ' . count($allSellerNotices));
                foreach ($allSellerNotices as $notice) {
                    $showOnMain = $notice['show_on_main'] ?? 0;
                    $startAt = $notice['start_at'] ?? null;
                    $endAt = $notice['end_at'] ?? null;
                    $dateOk = true;
                    if ($startAt && $startAt > $currentDate) $dateOk = false;
                    if ($endAt && $endAt < $currentDate) $dateOk = false;
                    error_log('  - ' . ($notice['title'] ?? 'N/A') . ' | show_on_main=' . $showOnMain . ' | 날짜조건=' . ($dateOk ? 'OK' : 'FAIL'));
                }
            }
        }
        error_log('===========================');
    }
} catch (Exception $e) {
    error_log("Error fetching seller banner: " . $e->getMessage());
    if (isset($_GET['debug_banner'])) {
        error_log('Exception details: ' . $e->getTraceAsString());
    }
}

// 통계 데이터 가져오기
$stats = [
    'total_products' => 0,
    'active_products' => 0,
    'inactive_products' => 0,
    'total_orders' => 0,
    'pending_orders' => 0,
    'completed_orders' => 0
];

// 주문 카테고리별 진행상황 통계 (해야할 일들만 - 종료/개통완료/설치완료 제외)
$orderStats = [
    'mvno' => [
        'received' => 0,
        'activating' => 0,
        'processing' => 0,
        'on_hold' => 0,
        'cancelled' => 0
    ],
    'mno' => [
        'received' => 0,
        'activating' => 0,
        'processing' => 0,
        'on_hold' => 0,
        'cancelled' => 0
    ],
    'internet' => [
        'received' => 0,
        'activating' => 0,
        'processing' => 0,
        'on_hold' => 0,
        'cancelled' => 0
    ],
    'mno-sim' => [
        'received' => 0,
        'activating' => 0,
        'processing' => 0,
        'on_hold' => 0,
        'cancelled' => 0
    ]
];

// 광고관리 데이터 (광고종료 5일 이내)
$advertisementStats = [
    'mno-sim' => 0,
    'mvno' => 0,
    'mno' => 0,
    'internet' => 0
];

// 예치금 데이터
$depositData = [
    'balance' => 0,
    'recent_history' => []
];

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $sellerId = (string)$currentUser['user_id'];
        
        // 디버깅: seller_id 확인
        error_log("Dashboard Stats - seller_id: " . $sellerId . " (type: " . gettype($sellerId) . ")");
        
        // 전체 상품 수
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = :seller_id AND status != 'deleted'");
        $stmt->execute([':seller_id' => $sellerId]);
        $stats['total_products'] = $stmt->fetch()['total'] ?? 0;
        
        // 판매 중인 상품 수
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = :seller_id AND status = 'active'");
        $stmt->execute([':seller_id' => $sellerId]);
        $stats['active_products'] = $stmt->fetch()['total'] ?? 0;
        
        // 판매 종료된 상품 수
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = :seller_id AND status = 'inactive'");
        $stmt->execute([':seller_id' => $sellerId]);
        $stats['inactive_products'] = $stmt->fetch()['total'] ?? 0;
        
        // 전체 주문 수
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM product_applications a
            WHERE a.seller_id = :seller_id
        ");
        $stmt->execute([':seller_id' => $sellerId]);
        $stats['total_orders'] = $stmt->fetch()['total'] ?? 0;
        
        // 대기 중인 주문 수
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM product_applications a
            WHERE a.seller_id = :seller_id 
            AND (a.application_status = 'received' OR a.application_status = '' OR a.application_status IS NULL OR LOWER(TRIM(a.application_status)) = 'pending')
        ");
        $stmt->execute([':seller_id' => $sellerId]);
        $stats['pending_orders'] = $stmt->fetch()['total'] ?? 0;
        
        // 완료된 주문 수
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM product_applications a
            WHERE a.seller_id = :seller_id 
            AND a.application_status = 'completed'
        ");
        $stmt->execute([':seller_id' => $sellerId]);
        $stats['completed_orders'] = $stmt->fetch()['total'] ?? 0;
        
        // 주문 카테고리별 진행상황 통계 (해야할 일들만 - 종료/개통완료/설치완료 제외, 취소는 포함)
        // 포함 카테고리: 알뜰폰(mvno), 통신사폰(mno), 인터넷(internet), 통신사단독유심(mno-sim)
        // 대시보드는 전체 기간 조회 (놓치는 주문이 없도록)
        $productTypes = ['mvno', 'mno', 'internet', 'mno-sim'];
        $activeStatuses = ['received', 'activating', 'processing', 'on_hold', 'cancelled'];
        // 제외할 상태: closed(종료), activation_completed(개통완료), installation_completed(설치완료), completed(완료)
        // 취소 상태는 포함하여 표시
        
        foreach ($productTypes as $type) {
            foreach ($activeStatuses as $status) {
                if ($status === 'received') {
                    // received는 빈 문자열, null, 'pending'도 포함, 종료/완료 상태 제외
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as total 
                        FROM product_applications a
                        WHERE a.seller_id = :seller_id 
                        AND a.product_type = :product_type
                        AND (
                            a.application_status = 'received'
                            OR a.application_status = '' 
                            OR a.application_status IS NULL 
                            OR LOWER(TRIM(a.application_status)) = 'pending'
                        )
                        AND (a.application_status IS NULL OR a.application_status NOT IN ('closed', 'activation_completed', 'installation_completed', 'completed'))
                    ");
                } elseif ($status === 'on_hold') {
                    // on_hold는 'on_hold' 또는 'rejected' 상태 포함
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as total 
                        FROM product_applications a
                        WHERE a.seller_id = :seller_id 
                        AND a.product_type = :product_type
                        AND (a.application_status = 'on_hold' OR a.application_status = 'rejected')
                        AND a.application_status NOT IN ('closed', 'activation_completed', 'installation_completed', 'completed')
                    ");
                } elseif ($status === 'cancelled') {
                    // cancelled 상태만 조회
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as total 
                        FROM product_applications a
                        WHERE a.seller_id = :seller_id 
                        AND a.product_type = :product_type
                        AND a.application_status = 'cancelled'
                    ");
                } else {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as total 
                        FROM product_applications a
                        WHERE a.seller_id = :seller_id 
                        AND a.product_type = :product_type
                        AND a.application_status = :status
                        AND a.application_status NOT IN ('closed', 'activation_completed', 'installation_completed', 'completed')
                    ");
                }
                if ($status === 'received') {
                    $stmt->execute([
                        ':seller_id' => $sellerId,
                        ':product_type' => $type
                    ]);
                } elseif ($status === 'on_hold') {
                    $stmt->execute([
                        ':seller_id' => $sellerId,
                        ':product_type' => $type
                    ]);
                } elseif ($status === 'cancelled') {
                    $stmt->execute([
                        ':seller_id' => $sellerId,
                        ':product_type' => $type
                    ]);
                } else {
                    $stmt->execute([
                        ':seller_id' => $sellerId,
                        ':product_type' => $type,
                        ':status' => $status
                    ]);
                }
                $orderStats[$type][$status] = $stmt->fetch()['total'] ?? 0;
            }
        }
        
        // 광고종료 5일 이내 상품 수 (카테고리별)
        $fiveDaysLater = date('Y-m-d H:i:s', strtotime('+5 days'));
        foreach ($productTypes as $type) {
            // product_type 변환 (mno-sim -> mno_sim)
            $dbType = ($type === 'mno-sim') ? 'mno_sim' : $type;
            
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT ra.product_id) as total
                FROM rotation_advertisements ra
                INNER JOIN products p ON ra.product_id = p.id
                WHERE ra.seller_id = :seller_id
                AND ra.product_type = :product_type
                AND ra.status = 'active'
                AND ra.end_datetime <= :end_datetime
                AND ra.end_datetime > NOW()
                AND p.status = 'active'
            ");
            $stmt->execute([
                ':seller_id' => $sellerId,
                ':product_type' => $dbType,
                ':end_datetime' => $fiveDaysLater
            ]);
            $advertisementStats[$type] = $stmt->fetch()['total'] ?? 0;
        }
        
        // 예치금 잔액 조회
        $stmt = $pdo->prepare("SELECT balance FROM seller_deposit_accounts WHERE seller_id = :seller_id");
        $stmt->execute([':seller_id' => $sellerId]);
        $balanceResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $depositData['balance'] = floatval($balanceResult['balance'] ?? 0);
        
        // 최근 예치금 사용내역 조회 (최근 5건)
        $stmt = $pdo->prepare("
            SELECT * FROM seller_deposit_ledger 
            WHERE seller_id = :seller_id
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute([':seller_id' => $sellerId]);
        $depositData['recent_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
}

// 판매자 메인배너 가져오기
$sellerBanner = getSellerMainBanner();

// 현재 페이지 설정
$current_page = 'seller';
$is_main_page = false;

// 페이지별 스타일
$pageStyles = '
        .seller-center-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 24px;
        }
        
        .seller-header {
            margin-bottom: 40px;
        }
        
        .seller-header h1 {
            font-size: 36px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }
        
        .seller-header p {
            font-size: 18px;
            color: #64748b;
            font-weight: 500;
        }
        
        .pending-notice {
            padding: 20px;
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 12px;
            margin-bottom: 32px;
        }
        
        .pending-notice-title {
            font-size: 18px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 8px;
        }
        
        .pending-notice-text {
            font-size: 14px;
            color: #78350f;
        }
        
        .dashboard-section {
            margin-bottom: 48px;
        }
        
        .dashboard-section-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .dashboard-section-title::before {
            content: "";
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 2px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .dashboard-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
        }
        
        .dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        
        .dashboard-card.primary::before {
            background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
        }
        
        .dashboard-card.success::before {
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        }
        
        .dashboard-card.warning::before {
            background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
        }
        
        .dashboard-card.info::before {
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
        }
        
        .dashboard-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        
        .dashboard-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .dashboard-card.primary .dashboard-card-icon {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }
        
        .dashboard-card.success .dashboard-card-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .dashboard-card.warning .dashboard-card-icon {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .dashboard-card.info .dashboard-card-icon {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
        
        .dashboard-card-title {
            font-size: 15px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .dashboard-card-value {
            font-size: 42px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
            line-height: 1;
        }
        
        .dashboard-card-description {
            font-size: 14px;
            color: #94a3b8;
            font-weight: 500;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }
        
        .action-button {
            padding: 18px 28px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }
        
        .action-button.secondary {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #475569;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .action-button.secondary:hover {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* 판매자 배너 모달 스타일 */
        .seller-banner-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .seller-banner-modal.active {
            display: flex;
        }
        
        .seller-banner-content {
            background: white;
            border-radius: 16px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .seller-banner-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .seller-banner-close:hover {
            background: rgba(0, 0, 0, 0.8);
            transform: scale(1.1);
        }
        
        .seller-banner-body {
            position: relative;
        }
        
        .seller-banner-body a {
            display: block;
            text-decoration: none;
            cursor: pointer;
        }
        
        .seller-banner-image {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .seller-banner-text {
            padding: 40px;
            text-align: center;
        }
        
        .seller-banner-text h2 {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }
        
        .seller-banner-text p {
            font-size: 16px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        
        .seller-banner-link-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            margin-top: 8px;
        }
        
        .seller-banner-link-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
        
        .seller-banner-link-btn:active {
            transform: translateY(0);
        }
        
        .seller-banner-link-btn svg {
            width: 18px;
            height: 18px;
        }
        
        .seller-banner-both {
            position: relative;
        }
        
        .seller-banner-both img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .seller-banner-text-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            padding: 40px;
            color: white;
        }
        
        .seller-banner-text-overlay h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            color: white;
        }
        
        .seller-banner-text-overlay p {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
        }
        
        /* 판매자 필수 확인사항 안내 카드 */
        .seller-notice-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 32px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
            position: relative;
        }
        
        .seller-notice-card.hidden {
            display: none;
        }
        
        .seller-notice-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        
        .seller-notice-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: #92400e;
        }
        
        .seller-notice-title-icon {
            font-size: 24px;
        }
        
        .seller-notice-toggle {
            background: none;
            border: none;
            color: #92400e;
            font-size: 14px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        .seller-notice-toggle:hover {
            background: rgba(146, 64, 14, 0.1);
        }
        
        .seller-notice-content {
            font-size: 15px;
            color: #78350f;
            line-height: 1.8;
        }
        
        .seller-notice-list {
            list-style: none;
            padding: 0;
            margin: 12px 0;
        }
        
        .seller-notice-list li {
            padding: 8px 0;
            padding-left: 24px;
            position: relative;
        }
        
        .seller-notice-list li::before {
            content: "•";
            position: absolute;
            left: 8px;
            color: #f59e0b;
            font-weight: bold;
            font-size: 18px;
        }
        
        .seller-notice-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid rgba(146, 64, 14, 0.2);
        }
        
        .seller-notice-link {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: color 0.2s;
        }
        
        .seller-notice-link:hover {
            color: #4f46e5;
            text-decoration: underline;
        }
        
        .seller-notice-dismiss {
            background: none;
            border: none;
            color: #dc2626;
            font-size: 13px;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .seller-notice-dismiss:hover {
            background: rgba(220, 38, 38, 0.1);
            color: #b91c1c;
        }
        
        .seller-notice-content.collapsed {
            display: none;
        }
';

include 'includes/seller-header.php';
?>

<div class="seller-center-container">
            <?php if (isset($_GET['register']) && $_GET['register'] === 'success'): ?>
                <div style="padding: 16px; background: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 24px; border: 1px solid #10b981;">
                    판매자 가입이 완료되었습니다. 관리자 승인 후 상품 등록이 가능합니다.
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['approved']) && $_GET['approved'] === 'true'): ?>
                <div style="padding: 16px; background: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 24px; border: 1px solid #10b981;">
                    판매자 승인이 완료되었습니다. 이제 상품 등록 및 판매가 가능합니다.
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <?php if ($_GET['error'] === 'no_permission_mvno'): ?>
                    <div style="padding: 16px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; margin-bottom: 24px;">
                        <div style="font-size: 16px; font-weight: 600; color: #92400e; margin-bottom: 8px;">권한이 없습니다</div>
                        <div style="font-size: 14px; color: #78350f;">알뜰폰 게시판에 상품을 등록할 권한이 없습니다. 관리자에게 권한을 요청하세요.</div>
                    </div>
                <?php elseif ($_GET['error'] === 'no_permission_mno'): ?>
                    <div style="padding: 16px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; margin-bottom: 24px;">
                        <div style="font-size: 16px; font-weight: 600; color: #92400e; margin-bottom: 8px;">권한이 없습니다</div>
                        <div style="font-size: 14px; color: #78350f;">통신사폰 게시판에 상품을 등록할 권한이 없습니다. 관리자에게 권한을 요청하세요.</div>
                    </div>
                <?php elseif ($_GET['error'] === 'no_permission_internet'): ?>
                    <div style="padding: 16px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; margin-bottom: 24px;">
                        <div style="font-size: 16px; font-weight: 600; color: #92400e; margin-bottom: 8px;">권한이 없습니다</div>
                        <div style="font-size: 14px; color: #78350f;">인터넷 게시판에 상품을 등록할 권한이 없습니다. 관리자에게 권한을 요청하세요.</div>
                    </div>
                <?php elseif ($_GET['error'] === 'no_permission_mno_sim'): ?>
                    <div style="padding: 16px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; margin-bottom: 24px;">
                        <div style="font-size: 16px; font-weight: 600; color: #92400e; margin-bottom: 8px;">권한이 없습니다</div>
                        <div style="font-size: 14px; color: #78350f;">통신사단독유심 게시판에 상품을 등록할 권한이 없습니다. 관리자에게 권한을 요청하세요.</div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- 판매자 필수 확인사항 안내 카드 -->
            <div class="seller-notice-card" id="sellerNoticeCard">
                <div class="seller-notice-header">
                    <div class="seller-notice-title">
                        <span class="seller-notice-title-icon">⚠️</span>
                        <span>판매자 필수 확인사항</span>
                    </div>
                    <button type="button" class="seller-notice-toggle" onclick="toggleNoticeContent()" id="noticeToggleBtn">
                        접기
                    </button>
                </div>
                <div class="seller-notice-content" id="noticeContent">
                    <ul class="seller-notice-list">
                        <li>계정 탈퇴 시 모든 상품 판매종료 처리</li>
                        <li>3일 이상 미접속 시 모든 상품 판매종료 처리</li>
                        <li>주문, 개통완료, 취소, 설치완료, 종료가 아닌 것은 15일 후 모두 종료처리함</li>
                    </ul>
                    <div class="seller-notice-actions">
                        <button type="button" class="seller-notice-dismiss" onclick="dismissNotice()">더 이상 보지 않기</button>
                    </div>
                </div>
            </div>
            
            <!-- 등록 상품 섹션 -->
            <div class="dashboard-section">
                <h2 class="dashboard-section-title">등록 상품</h2>
                <div class="dashboard-grid">
                    <div class="dashboard-card primary">
                        <div class="dashboard-card-header">
                            <div class="dashboard-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 24px; height: 24px;">
                                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                        </div>
                        <div class="dashboard-card-title">전체 상품</div>
                        <div class="dashboard-card-value"><?php echo number_format($stats['total_products']); ?></div>
                        <div class="dashboard-card-description">등록된 전체 상품 수</div>
                    </div>
                    
                    <div class="dashboard-card success">
                        <div class="dashboard-card-header">
                            <div class="dashboard-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 24px; height: 24px;">
                                    <path d="M9 11l3 3L22 4m-1 8v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                                </svg>
                            </div>
                        </div>
                        <div class="dashboard-card-title">판매 중</div>
                        <div class="dashboard-card-value"><?php echo number_format($stats['active_products']); ?></div>
                        <div class="dashboard-card-description">현재 판매 중인 상품</div>
                    </div>
                    
                    <div class="dashboard-card warning">
                        <div class="dashboard-card-header">
                            <div class="dashboard-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 24px; height: 24px;">
                                    <path d="M18 6L6 18M6 6l12 12"/>
                                </svg>
                            </div>
                        </div>
                        <div class="dashboard-card-title">판매 종료</div>
                        <div class="dashboard-card-value"><?php echo number_format($stats['inactive_products']); ?></div>
                        <div class="dashboard-card-description">판매 종료된 상품</div>
                    </div>
                </div>
            </div>
            
            <!-- 주문 관리 섹션 -->
            <div class="dashboard-section">
                <h2 class="dashboard-section-title">주문 관리</h2>
                
                <?php
                $orderCategories = [
                    'mno-sim' => ['name' => '통신사단독유심', 'url' => getAssetPath('/seller/orders/mno-sim.php')],
                    'mvno' => ['name' => '알뜰폰', 'url' => getAssetPath('/seller/orders/mvno.php')],
                    'mno' => ['name' => '통신사폰', 'url' => getAssetPath('/seller/orders/mno.php')],
                    'internet' => ['name' => '인터넷', 'url' => getAssetPath('/seller/orders/internet.php')]
                ];
                $statusLabels = [
                    'received' => '접수',
                    'activating' => '개통중',
                    'processing' => '처리중',
                    'on_hold' => '보류',
                    'cancelled' => '취소'
                ];
                ?>
                
                <?php foreach ($orderCategories as $type => $category): ?>
                    <?php
                    // 접수, 보류, 개통중 중 하나라도 건수가 있으면 표시
                    $displayStatuses = ['received' => '접수', 'on_hold' => '보류', 'activating' => '개통중'];
                    $hasAnyOrders = false;
                    foreach ($displayStatuses as $status => $label) {
                        $count = $orderStats[$type][$status] ?? 0;
                        if ($count > 0) {
                            $hasAnyOrders = true;
                            break;
                        }
                    }
                    if (!$hasAnyOrders) continue;
                    ?>
                    <div class="content-box" style="background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #0f172a;">
                                <?= htmlspecialchars($category['name']) ?>
                            </h3>
                            <a href="<?= htmlspecialchars($category['url']) ?>" style="color: #6366f1; text-decoration: none; font-size: 14px; font-weight: 600;">
                                전체보기 →
                            </a>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
                            <?php 
                            // 접수, 보류, 개통중만 표시 (건수가 0보다 큰 것만 표시)
                            foreach ($displayStatuses as $status => $label): 
                                $count = $orderStats[$type][$status] ?? 0;
                                // 건수가 0이면 표시하지 않음
                                if ($count <= 0) continue;
                            ?>
                                <a href="<?= htmlspecialchars($category['url']) ?>?status=<?= $status === 'received' ? 'received' : $status ?>" style="text-decoration: none; display: block;">
                                    <div style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-radius: 8px; padding: 16px; border: 1px solid #ef4444; transition: all 0.2s;">
                                        <div style="font-size: 13px; color: #64748b; font-weight: 500; margin-bottom: 8px;">
                                            <?= htmlspecialchars($label) ?>
                                        </div>
                                        <div style="font-size: 28px; font-weight: 800; color: #991b1b; margin-bottom: 4px;">
                                            <?= number_format($count) ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
            </div>
            
            <!-- 광고관리 섹션 -->
            <div class="dashboard-section">
                <h2 class="dashboard-section-title">광고관리</h2>
                
                <?php
                $adCategories = [
                    'mno-sim' => ['name' => '통신사단독유심', 'url' => getAssetPath('/seller/products/mno-sim-list.php')],
                    'mvno' => ['name' => '알뜰폰', 'url' => getAssetPath('/seller/products/mvno-list.php')],
                    'mno' => ['name' => '통신사폰', 'url' => getAssetPath('/seller/products/mno-list.php')],
                    'internet' => ['name' => '인터넷', 'url' => getAssetPath('/seller/products/internet-list.php')]
                ];
                ?>
                
                <?php
                $hasExpiringAds = false;
                foreach ($adCategories as $type => $category) {
                    if ($advertisementStats[$type] > 0) {
                        $hasExpiringAds = true;
                        break;
                    }
                }
                ?>
                
                <?php if ($hasExpiringAds): ?>
                    <div class="content-box" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px solid #f59e0b; border-radius: 12px; padding: 24px; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2); margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                            <span style="font-size: 24px;">⚠️</span>
                            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #92400e;">
                                광고종료 5일 이내 마감 상품
                            </h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <?php foreach ($adCategories as $type => $category): ?>
                                <?php if ($advertisementStats[$type] > 0): ?>
                                    <a href="<?= htmlspecialchars($category['url']) ?>" style="text-decoration: none; display: block;">
                                        <div style="background: white; border-radius: 8px; padding: 20px; border: 2px solid #f59e0b; transition: all 0.2s; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.15);">
                                            <div style="font-size: 14px; color: #78350f; font-weight: 600; margin-bottom: 8px;">
                                                <?= htmlspecialchars($category['name']) ?>
                                            </div>
                                            <div style="font-size: 32px; font-weight: 800; color: #92400e; margin-bottom: 4px;">
                                                <?= number_format($advertisementStats[$type]) ?>
                                            </div>
                                            <div style="font-size: 12px; color: #a16207;">
                                                개 상품
                                            </div>
                                        </div>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="content-box" style="background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
                        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">📢</div>
                        <div style="font-size: 16px; color: #64748b; margin-bottom: 20px;">광고종료 예정인 상품이 없습니다</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 예치금 관리 섹션 -->
            <div class="dashboard-section">
                <h2 class="dashboard-section-title">예치금 관리</h2>
                <div class="dashboard-grid">
                    <div class="dashboard-card primary">
                        <div class="dashboard-card-header">
                            <div class="dashboard-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 24px; height: 24px;">
                                    <path d="M21 12c0 1.66-1 3-2.5 3S16 13.66 16 12s1-3 2.5-3 2.5 1.34 2.5 3z"/>
                                    <path d="M12 21c-1.66 0-3-1-3-2.5s1.34-2.5 3-2.5 3 1 3 2.5-1.34 2.5-3 2.5z"/>
                                    <path d="M12 3c1.66 0 3 1 3 2.5S13.66 8 12 8 9 7 9 5.5 10.34 3 12 3z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="dashboard-card-title">예치금 잔액</div>
                        <div class="dashboard-card-value"><?php echo number_format($depositData['balance'], 0); ?>원</div>
                        <div class="dashboard-card-description">부가세 포함</div>
                    </div>
                </div>
                
                <!-- 최근 예치금 사용내역 -->
                <?php if (!empty($depositData['recent_history'])): ?>
                <div class="content-box" style="background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 24px;">
                    <div style="margin-bottom: 20px;">
                        <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #0f172a;">최근 사용내역</h3>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                    <th style="padding: 12px; text-align: left; font-weight: 600; font-size: 13px; color: #64748b;">일시</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; font-size: 13px; color: #64748b;">구분</th>
                                    <th style="padding: 12px; text-align: right; font-weight: 600; font-size: 13px; color: #64748b;">금액</th>
                                    <th style="padding: 12px; text-align: right; font-weight: 600; font-size: 13px; color: #64748b;">잔액</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; font-size: 13px; color: #64748b;">내용</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $typeLabels = [
                                    'deposit' => ['label' => '충전', 'color' => '#10b981'],
                                    'withdraw' => ['label' => '차감', 'color' => '#ef4444'],
                                    'refund' => ['label' => '환불', 'color' => '#3b82f6']
                                ];
                                foreach ($depositData['recent_history'] as $item): 
                                    $typeInfo = $typeLabels[$item['transaction_type']] ?? ['label' => $item['transaction_type'], 'color' => '#64748b'];
                                ?>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 12px; font-size: 14px; color: #374151;">
                                        <?= date('Y-m-d H:i', strtotime($item['created_at'])) ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span style="padding: 4px 12px; background: <?= $typeInfo['color'] ?>20; color: <?= $typeInfo['color'] ?>; border-radius: 4px; font-size: 13px; font-weight: 500;">
                                            <?= $typeInfo['label'] ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: right; font-size: 14px; font-weight: 600; color: <?= $item['transaction_type'] === 'deposit' || $item['transaction_type'] === 'refund' ? $typeInfo['color'] : '#374151' ?>;">
                                        <?php 
                                        $amount = floatval($item['amount']);
                                        if ($item['transaction_type'] === 'deposit' || $item['transaction_type'] === 'refund') {
                                            echo '+' . number_format(abs($amount), 0) . '원';
                                        } else {
                                            // 차감일 때는 절댓값을 사용하여 중복 마이너스 방지
                                            echo '-' . number_format(abs($amount), 0) . '원';
                                        }
                                        ?>
                                    </td>
                                    <td style="padding: 12px; text-align: right; font-size: 14px; color: #64748b;">
                                        <?= number_format($item['balance_after'], 0) ?>원
                                    </td>
                                    <td style="padding: 12px; font-size: 14px; color: #64748b;">
                                        <?= htmlspecialchars($item['description'] ?? '-') ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 20px; text-align: center;">
                        <a href="<?php echo getAssetPath('/seller/deposit/history.php'); ?>" class="action-button secondary" style="display: inline-flex;">
                            전체 사용내역 보기
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="content-box" style="background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 24px; text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">💳</div>
                    <div style="font-size: 16px; color: #64748b; margin-bottom: 20px;">사용내역이 없습니다</div>
                    <a href="<?php echo getAssetPath('/seller/deposit/charge.php'); ?>" class="action-button" style="display: inline-flex;">
                        예치금 충전하기
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 빠른 작업 -->
            <div class="dashboard-section">
                <h2 class="dashboard-section-title">빠른 작업</h2>
                <div class="quick-actions">
                    <a href="<?php echo getAssetPath('/seller/products/mvno.php'); ?>" class="action-button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                        알뜰폰 상품 등록
                    </a>
                    <a href="<?php echo getAssetPath('/seller/products/mno.php'); ?>" class="action-button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                        통신사폰 상품 등록
                    </a>
                    <a href="<?php echo getAssetPath('/seller/products/internet.php'); ?>" class="action-button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                        </svg>
                        인터넷 상품 등록
                    </a>
                    <a href="<?php echo getAssetPath('/seller/products/mno-sim.php'); ?>" class="action-button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                        통신사단독유심 등록
                    </a>
                </div>
            </div>
</div>

<!-- 디버깅 정보 (debug_banner 파라미터가 있을 때만 표시) -->
<?php if (isset($_GET['debug_banner'])): ?>
<div style="position: fixed; bottom: 20px; right: 20px; background: rgba(0, 0, 0, 0.8); color: white; padding: 20px; border-radius: 8px; z-index: 99999; max-width: 400px; font-size: 12px; font-family: monospace;">
    <h3 style="margin: 0 0 10px 0; color: #60a5fa;">메인공지 디버깅</h3>
    <div style="line-height: 1.6;">
        <div><strong>메인공지 존재:</strong> <?= $sellerBanner ? '✅ 있음' : '❌ 없음' ?></div>
        <?php if ($sellerBanner): ?>
            <div><strong>ID:</strong> <?= htmlspecialchars($sellerBanner['id'] ?? 'N/A') ?></div>
            <div><strong>제목:</strong> <?= htmlspecialchars($sellerBanner['title'] ?? 'N/A') ?></div>
            <div><strong>target_audience:</strong> <?= htmlspecialchars($sellerBanner['target_audience'] ?? 'N/A') ?></div>
            <div><strong>show_on_main:</strong> <?= $sellerBanner['show_on_main'] ?? 'N/A' ?></div>
            <div><strong>start_at:</strong> <?= $sellerBanner['start_at'] ?? 'NULL' ?></div>
            <div><strong>end_at:</strong> <?= $sellerBanner['end_at'] ?? 'NULL' ?></div>
            <div><strong>banner_type:</strong> <?= htmlspecialchars($sellerBanner['banner_type'] ?? 'N/A') ?></div>
            <div><strong>image_url:</strong> <?= !empty($sellerBanner['image_url']) ? '있음' : '없음' ?></div>
            <div><strong>link_url:</strong> <?= !empty($sellerBanner['link_url']) ? htmlspecialchars($sellerBanner['link_url']) : '없음' ?></div>
        <?php else: ?>
            <div style="margin-top: 10px; color: #fbbf24;">
                <strong>원인 확인:</strong><br>
                - target_audience가 'seller'인지 확인<br>
                - show_on_main이 1인지 확인<br>
                - 표시 기간이 현재 날짜 범위 내인지 확인
            </div>
        <?php endif; ?>
    </div>
    <button onclick="this.parentElement.remove()" style="margin-top: 10px; padding: 5px 10px; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer;">닫기</button>
</div>
<?php endif; ?>

<!-- 판매자 메인공지 모달 -->
<?php 
// 디버깅: 메인공지 상태 확인
if (isset($_GET['debug_banner'])) {
    echo '<!-- 디버깅: sellerBanner = ' . ($sellerBanner ? '있음' : '없음') . ' -->';
    if ($sellerBanner) {
        echo '<!-- 디버깅: ID = ' . htmlspecialchars($sellerBanner['id'] ?? 'N/A') . ' -->';
        echo '<!-- 디버깅: 제목 = ' . htmlspecialchars($sellerBanner['title'] ?? 'N/A') . ' -->';
    }
}
?>
<?php if ($sellerBanner): ?>
<div class="seller-banner-modal" id="sellerBannerModal">
    <div class="seller-banner-content">
        <button class="seller-banner-close" onclick="closeSellerBanner()">×</button>
        <div class="seller-banner-body">
            <?php
            $bannerType = $sellerBanner['banner_type'] ?? 'text';
            $hasLink = !empty($sellerBanner['link_url']);
            $linkUrl = $sellerBanner['link_url'] ?? '#';
            $linkTarget = $hasLink ? '_blank' : '_self';
            
            if ($hasLink) {
                echo '<a href="' . htmlspecialchars($linkUrl) . '" target="' . $linkTarget . '">';
            }
            
            if ($bannerType === 'text' || $bannerType === 'both') {
                echo '<div class="seller-banner-text' . ($bannerType === 'both' ? ' seller-banner-text-overlay' : '') . '">';
                if (!empty($sellerBanner['content'])) {
                    echo '<p>' . nl2br(htmlspecialchars($sellerBanner['content'])) . '</p>';
                }
                
                // 텍스트만 있는 경우 링크가 있으면 바로가기 버튼 표시
                if ($bannerType === 'text' && $hasLink) {
                    echo '<a href="' . htmlspecialchars($linkUrl) . '" target="' . $linkTarget . '" class="seller-banner-link-btn">';
                    echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
                    echo '<path d="M5 12h14M12 5l7 7-7 7"/>';
                    echo '</svg>';
                    echo '바로가기';
                    echo '</a>';
                }
                
                echo '</div>';
            }
            
            if ($bannerType === 'image' || $bannerType === 'both') {
                if (!empty($sellerBanner['image_url'])) {
                    if ($bannerType === 'both') {
                        echo '<div class="seller-banner-both">';
                        echo '<img src="' . htmlspecialchars($sellerBanner['image_url']) . '" alt="' . htmlspecialchars($sellerBanner['title'] ?? '') . '" class="seller-banner-image">';
                        echo '</div>';
                    } else {
                        // 이미지만 있는 경우 이미지에 링크 연결
                        if ($hasLink) {
                            echo '<a href="' . htmlspecialchars($linkUrl) . '" target="' . $linkTarget . '">';
                        }
                        echo '<img src="' . htmlspecialchars($sellerBanner['image_url']) . '" alt="' . htmlspecialchars($sellerBanner['title'] ?? '') . '" class="seller-banner-image">';
                        if ($hasLink) {
                            echo '</a>';
                        }
                    }
                } elseif ($bannerType === 'image') {
                    // 이미지 배너인데 이미지가 없으면 텍스트로 대체 표시
                    echo '<div class="seller-banner-text">';
                    if (!empty($sellerBanner['content'])) {
                        echo '<p>' . nl2br(htmlspecialchars($sellerBanner['content'])) . '</p>';
                    }
                    
                    // 링크가 있으면 바로가기 버튼 표시
                    if ($hasLink) {
                        echo '<a href="' . htmlspecialchars($linkUrl) . '" target="' . $linkTarget . '" class="seller-banner-link-btn">';
                        echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
                        echo '<path d="M5 12h14M12 5l7 7-7 7"/>';
                        echo '</svg>';
                        echo '바로가기';
                        echo '</a>';
                    }
                    
                    echo '</div>';
                }
            }
            
            // 텍스트+이미지인 경우 이미지에 링크가 연결되어 있으므로 여기서 닫지 않음
            if ($hasLink && $bannerType === 'both') {
                echo '</a>';
            }
            ?>
        </div>
    </div>
</div>

<script>
// 디버깅 모드
const DEBUG_BANNER = <?= isset($_GET['debug_banner']) ? 'true' : 'false' ?>;

// 항상 콘솔에 기본 정보 출력
console.log('=== 판매자 메인공지 체크 ===');
console.log('메인공지 존재 여부:', <?= $sellerBanner ? 'true' : 'false' ?>);
<?php if ($sellerBanner): ?>
console.log('메인공지 ID:', '<?= htmlspecialchars($sellerBanner['id'] ?? 'N/A', ENT_QUOTES) ?>');
console.log('메인공지 제목:', '<?= htmlspecialchars($sellerBanner['title'] ?? 'N/A', ENT_QUOTES) ?>');
console.log('target_audience:', '<?= htmlspecialchars($sellerBanner['target_audience'] ?? 'N/A', ENT_QUOTES) ?>');
console.log('show_on_main:', <?= $sellerBanner['show_on_main'] ?? 0 ?>);
<?php else: ?>
console.log('⚠️ 메인공지가 없습니다. 다음을 확인하세요:');
console.log('  1. target_audience가 "seller"인지');
console.log('  2. show_on_main이 1인지');
console.log('  3. 표시 기간이 현재 날짜 범위 내인지');
<?php endif; ?>

// 페이지 로드 시 메인공지 모달 표시
(function() {
    function showMainNoticeModal() {
        const modal = document.getElementById('sellerBannerModal');
        
        console.log('=== 메인공지 모달 표시 시도 ===');
        console.log('모달 요소 존재:', !!modal);
        
        if (modal) {
            console.log('모달 찾음, 표시 준비 중...');
            // 약간의 지연을 두어 페이지 로드 후 표시
            setTimeout(function() {
                console.log('모달 표시 실행...');
                modal.classList.add('active');
                // body 스크롤 방지
                document.body.style.overflow = 'hidden';
                
                const computedStyle = window.getComputedStyle(modal);
                console.log('모달 display:', computedStyle.display);
                console.log('모달 visibility:', computedStyle.visibility);
                console.log('모달 z-index:', computedStyle.zIndex);
                console.log('✅ 메인공지 모달 표시 완료');
            }, 500);
        } else {
            console.error('❌ sellerBannerModal 요소를 찾을 수 없습니다!');
            console.log('페이지 소스에서 "sellerBannerModal"을 검색해보세요.');
        }
    }
    
    // DOMContentLoaded 이벤트
    if (document.readyState === 'loading') {
        console.log('문서 로딩 중, DOMContentLoaded 대기...');
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOMContentLoaded 이벤트 발생');
            showMainNoticeModal();
        });
    } else {
        console.log('문서 이미 로드됨, 즉시 실행');
        // 이미 로드된 경우 즉시 실행
        showMainNoticeModal();
    }
})();

function closeSellerBanner() {
    const modal = document.getElementById('sellerBannerModal');
    if (modal) {
        modal.classList.remove('active');
        // body 스크롤 복원
        document.body.style.overflow = '';
        console.log('메인공지 모달 닫힘');
    }
}

// 모달 외부 클릭 시 닫기
document.addEventListener('DOMContentLoaded', function() {
    const bannerModal = document.getElementById('sellerBannerModal');
    if (bannerModal) {
        bannerModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeSellerBanner();
            }
        });
        console.log('모달 외부 클릭 이벤트 리스너 등록');
    } else {
        console.warn('모달 요소가 없어 이벤트 리스너를 등록할 수 없습니다.');
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSellerBanner();
        }
    });
    
    console.log('ESC 키 이벤트 리스너 등록 완료');
});
</script>
<?php endif; ?>

<!-- 판매자 필수 확인사항 안내 카드 스크립트 -->
<script>
(function() {
    // localStorage에서 "더 이상 보지 않기" 상태 확인
    const noticeDismissed = localStorage.getItem('seller_notice_dismissed');
    if (noticeDismissed === 'true') {
        const noticeCard = document.getElementById('sellerNoticeCard');
        if (noticeCard) {
            noticeCard.classList.add('hidden');
        }
        return;
    }
    
    // 접기/펼치기 상태 관리
    let isExpanded = true;
    
    // 접기/펼치기 함수
    window.toggleNoticeContent = function() {
        const content = document.getElementById('noticeContent');
        const toggleBtn = document.getElementById('noticeToggleBtn');
        
        if (!content || !toggleBtn) return;
        
        isExpanded = !isExpanded;
        if (isExpanded) {
            content.classList.remove('collapsed');
            toggleBtn.textContent = '접기';
        } else {
            content.classList.add('collapsed');
            toggleBtn.textContent = '펼치기';
        }
    };
    
    // 더 이상 보지 않기 함수
    window.dismissNotice = function() {
        if (confirm('이 안내를 더 이상 표시하지 않으시겠습니까?')) {
            localStorage.setItem('seller_notice_dismissed', 'true');
            const noticeCard = document.getElementById('sellerNoticeCard');
            if (noticeCard) {
                noticeCard.style.transition = 'opacity 0.3s ease';
                noticeCard.style.opacity = '0';
                setTimeout(function() {
                    noticeCard.classList.add('hidden');
                }, 300);
            }
        }
    };
})();
</script>

<?php include 'includes/seller-footer.php'; ?>

