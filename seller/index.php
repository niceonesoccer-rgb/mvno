<?php
/**
 * 판매자 센터 메인 페이지
 * 경로: /MVNO/seller/
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

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
if (!isset($currentUser['approval_status']) || $currentUser['approval_status'] !== 'approved') {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

// 현재 페이지 설정
$current_page = 'seller';
$is_main_page = false;

// 페이지별 스타일
$pageStyles = '
        .seller-center-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 24px;
        }
        
        .seller-header {
            margin-bottom: 32px;
        }
        
        .seller-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .seller-header p {
            font-size: 16px;
            color: #6b7280;
        }
        
        .pending-notice {
            padding: 20px;
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            margin-bottom: 24px;
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
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }
        
        .dashboard-card-title {
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 12px;
        }
        
        .dashboard-card-value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .dashboard-card-description {
            font-size: 13px;
            color: #9ca3af;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 32px;
        }
        
        .action-button {
            padding: 16px 24px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: background 0.2s;
        }
        
        .action-button:hover {
            background: #4f46e5;
        }
        
        .action-button.secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .action-button.secondary:hover {
            background: #e5e7eb;
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
            
            <!-- 대시보드 카드 -->
                <!-- 대시보드 카드 -->
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="dashboard-card-title">등록된 상품</div>
                        <div class="dashboard-card-value">0</div>
                        <div class="dashboard-card-description">전체 상품 수</div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-title">판매 중인 상품</div>
                        <div class="dashboard-card-value">0</div>
                        <div class="dashboard-card-description">판매 중인 상품 수</div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-title">판매 종료</div>
                        <div class="dashboard-card-value">0</div>
                        <div class="dashboard-card-description">판매 종료된 상품 수</div>
                    </div>
                </div>
                
                <!-- 빠른 작업 -->
                <div class="quick-actions">
                    <a href="/MVNO/seller/products/mvno.php" class="action-button">알뜰폰 상품 관리</a>
                    <a href="/MVNO/seller/products/mno.php" class="action-button">통신사폰 상품 관리</a>
                    <a href="/MVNO/seller/products/internet.php" class="action-button">인터넷 상품 관리</a>
                    <a href="/MVNO/seller/statistics.php" class="action-button secondary">통계 보기</a>
                </div>
</div>

<?php include 'includes/seller-footer.php'; ?>

