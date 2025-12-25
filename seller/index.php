<?php
/**
 * 판매자 센터 메인 페이지
 * 경로: /MVNO/seller/
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: /MVNO/seller/login.php');
    exit;
}

// 판매자 승인 체크 - 승인되지 않은 경우 waiting.php로 리다이렉트
$isApproved = isset($currentUser['seller_approved']) && $currentUser['seller_approved'] === true;
if (!$isApproved) {
    header('Location: /MVNO/seller/waiting.php');
    exit;
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

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $sellerId = (string)$currentUser['user_id'];
        
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
            FROM applications a
            WHERE a.seller_id = :seller_id
        ");
        $stmt->execute([':seller_id' => $sellerId]);
        $stats['total_orders'] = $stmt->fetch()['total'] ?? 0;
        
        // 대기 중인 주문 수
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM applications a
            WHERE a.seller_id = :seller_id 
            AND (a.application_status = 'received' OR a.application_status = '' OR a.application_status IS NULL OR LOWER(TRIM(a.application_status)) = 'pending')
        ");
        $stmt->execute([':seller_id' => $sellerId]);
        $stats['pending_orders'] = $stmt->fetch()['total'] ?? 0;
        
        // 완료된 주문 수
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM applications a
            WHERE a.seller_id = :seller_id 
            AND a.application_status = 'completed'
        ");
        $stmt->execute([':seller_id' => $sellerId]);
        $stats['completed_orders'] = $stmt->fetch()['total'] ?? 0;
    }
} catch (PDOException $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
}

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
';

include 'includes/seller-header.php';
?>

<div class="seller-center-container">
            <div class="seller-header">
                <h1>판매자 센터</h1>
                <p><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>님, 환영합니다</p>
            </div>
            
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
                <?php endif; ?>
            <?php endif; ?>
            
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
                <div class="dashboard-grid">
                    <div class="dashboard-card info">
                        <div class="dashboard-card-header">
                            <div class="dashboard-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 24px; height: 24px;">
                                    <path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/>
                                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                </svg>
                            </div>
                        </div>
                        <div class="dashboard-card-title">전체 주문</div>
                        <div class="dashboard-card-value"><?php echo number_format($stats['total_orders']); ?></div>
                        <div class="dashboard-card-description">전체 주문 건수</div>
                    </div>
                    
                    <div class="dashboard-card warning">
                        <div class="dashboard-card-header">
                            <div class="dashboard-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 24px; height: 24px;">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                        </div>
                        <div class="dashboard-card-title">대기 중</div>
                        <div class="dashboard-card-value"><?php echo number_format($stats['pending_orders']); ?></div>
                        <div class="dashboard-card-description">처리 대기 중인 주문</div>
                    </div>
                    
                    <div class="dashboard-card success">
                        <div class="dashboard-card-header">
                            <div class="dashboard-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 24px; height: 24px;">
                                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                            </div>
                        </div>
                        <div class="dashboard-card-title">완료</div>
                        <div class="dashboard-card-value"><?php echo number_format($stats['completed_orders']); ?></div>
                        <div class="dashboard-card-description">처리 완료된 주문</div>
                    </div>
                </div>
            </div>
            
            <!-- 빠른 작업 -->
            <div class="dashboard-section">
                <h2 class="dashboard-section-title">빠른 작업</h2>
                <div class="quick-actions">
                    <a href="/MVNO/seller/products/mvno.php" class="action-button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                        알뜰폰 상품 등록
                    </a>
                    <a href="/MVNO/seller/products/mno.php" class="action-button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                        통신사폰 상품 등록
                    </a>
                    <a href="/MVNO/seller/products/internet.php" class="action-button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                        </svg>
                        인터넷 상품 등록
                    </a>
                    <a href="/MVNO/seller/products/mno-sim.php" class="action-button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                        통신사유심 등록
                    </a>
                </div>
            </div>
</div>

<?php include 'includes/seller-footer.php'; ?>

