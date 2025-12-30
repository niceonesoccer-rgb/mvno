<?php
/**
 * 입찰 현황 페이지 (판매자)
 * 경로: /seller/bidding/list.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

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

// 판매자 승인 체크
$approvalStatus = $currentUser['approval_status'] ?? 'pending';
if ($approvalStatus !== 'approved') {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

// 탈퇴 요청 상태 확인
if (isset($currentUser['withdrawal_requested']) && $currentUser['withdrawal_requested'] === true) {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

require_once __DIR__ . '/../includes/seller-header.php';

$pdo = getDBConnection();
$participations = [];
$availableRounds = [];
$error = null;
$sellerId = (string)$currentUser['user_id'];
$activeTab = $_GET['tab'] ?? 'my';

try {
    if ($pdo) {
        // 내 입찰 내역
        $stmt = $pdo->prepare("
            SELECT 
                bp.*,
                br.category,
                br.bidding_start_at,
                br.bidding_end_at,
                br.display_start_at,
                br.display_end_at,
                br.min_bid_amount,
                br.max_bid_amount,
                br.rotation_type
            FROM bidding_participations bp
            INNER JOIN bidding_rounds br ON bp.bidding_round_id = br.id
            WHERE bp.seller_id = :seller_id
            ORDER BY bp.bid_at DESC
        ");
        $stmt->execute([':seller_id' => $sellerId]);
        $participations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 입찰 참여 가능한 라운드 (입찰 중인 라운드 중 아직 참여하지 않은 것)
        $now = date('Y-m-d H:i:s');
        $roundsStmt = $pdo->prepare("
            SELECT br.*
            FROM bidding_rounds br
            WHERE br.status = 'bidding'
              AND br.bidding_start_at <= :now
              AND br.bidding_end_at >= :now
              AND NOT EXISTS (
                  SELECT 1 FROM bidding_participations bp
                  WHERE bp.bidding_round_id = br.id
                    AND bp.seller_id = :seller_id
                    AND bp.status != 'cancelled'
              )
            ORDER BY br.bidding_end_at ASC
        ");
        $roundsStmt->execute([':seller_id' => $sellerId, ':now' => $now]);
        $availableRounds = $roundsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "입찰 현황을 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
    error_log("Seller bidding list error: " . $e->getMessage());
}

// 카테고리 라벨
$categoryLabels = [
    'mno' => '통신사폰',
    'mvno' => '알뜰폰',
    'mno_sim' => '통신사단독유심'
];

// 상태 라벨
$statusLabels = [
    'pending' => '대기',
    'won' => '낙찰',
    'lost' => '낙찰실패',
    'cancelled' => '취소'
];
?>

<style>
    .page-header {
        margin-bottom: 32px;
    }
    
    .page-title {
        font-size: 32px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }
    
    .page-description {
        color: #64748b;
        font-size: 15px;
    }
    
    .content-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        margin-bottom: 24px;
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table th {
        background: #f8fafc;
        padding: 16px;
        text-align: left;
        font-weight: 600;
        color: #475569;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .table td {
        padding: 20px 16px;
        border-bottom: 1px solid #e2e8f0;
        color: #1e293b;
    }
    
    .table tr:hover {
        background: #f8fafc;
    }
    
    .badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-won {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-lost {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .badge-pending {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .badge-cancelled {
        background: #f3f4f6;
        color: #6b7280;
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: #94a3b8;
    }
    
    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 24px;
    }
    
    .empty-state h3 {
        font-size: 20px;
        color: #64748b;
        margin-bottom: 12px;
    }
    
    .empty-state p {
        font-size: 15px;
        color: #94a3b8;
    }
    
    .error-message {
        background: #fee2e2;
        color: #991b1b;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        border-left: 4px solid #dc2626;
    }
    
    .action-link {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 500;
    }
    
    .action-link:hover {
        text-decoration: underline;
    }
</style>

<div class="page-header">
    <h1 class="page-title">입찰 현황</h1>
    <p class="page-description">나의 입찰 참여 내역을 확인하고 새로운 입찰에 참여할 수 있습니다.</p>
</div>

<div style="display: flex; gap: 12px; margin-bottom: 24px; border-bottom: 2px solid #e2e8f0;">
    <a href="?tab=my" style="padding: 12px 24px; text-decoration: none; color: <?php echo $activeTab === 'my' ? '#3b82f6' : '#64748b'; ?>; font-weight: <?php echo $activeTab === 'my' ? '600' : '400'; ?>; border-bottom: 2px solid <?php echo $activeTab === 'my' ? '#3b82f6' : 'transparent'; ?>; margin-bottom: -2px;">
        내 입찰 내역
    </a>
    <a href="?tab=available" style="padding: 12px 24px; text-decoration: none; color: <?php echo $activeTab === 'available' ? '#3b82f6' : '#64748b'; ?>; font-weight: <?php echo $activeTab === 'available' ? '600' : '400'; ?>; border-bottom: 2px solid <?php echo $activeTab === 'available' ? '#3b82f6' : 'transparent'; ?>; margin-bottom: -2px;">
        참여 가능한 입찰
    </a>
</div>

<?php if ($error): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($activeTab === 'my'): ?>
    <div class="content-card">
        <?php if (empty($participations)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📊</div>
                <h3>입찰 참여 내역이 없습니다</h3>
                <p>아직 입찰에 참여한 내역이 없습니다.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>카테고리</th>
                        <th>입찰금액</th>
                        <th>순위</th>
                        <th>상태</th>
                        <th>입찰일시</th>
                        <th>관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participations as $participation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($categoryLabels[$participation['category']] ?? $participation['category']); ?></td>
                            <td><strong><?php echo number_format($participation['bid_amount']); ?>원</strong></td>
                            <td><?php echo $participation['rank'] ?? '-'; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $participation['status']; ?>">
                                    <?php echo htmlspecialchars($statusLabels[$participation['status']] ?? $participation['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($participation['bid_at'])); ?></td>
                            <td>
                                <a href="/MVNO/seller/bidding/detail.php?id=<?php echo $participation['id']; ?>" class="action-link">
                                    상세보기
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="content-card">
        <?php if (empty($availableRounds)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🔍</div>
                <h3>참여 가능한 입찰이 없습니다</h3>
                <p>현재 입찰 중인 라운드가 없거나 이미 모두 참여하셨습니다.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>카테고리</th>
                        <th>입찰 기간</th>
                        <th>게시 기간</th>
                        <th>입찰 금액 범위</th>
                        <th>운용 방식</th>
                        <th>관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($availableRounds as $round): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($categoryLabels[$round['category']] ?? $round['category']); ?></td>
                            <td>
                                <?php echo date('Y-m-d H:i', strtotime($round['bidding_start_at'])); ?><br>
                                <small style="color: #94a3b8;">
                                    ~ <?php echo date('Y-m-d H:i', strtotime($round['bidding_end_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <?php echo date('Y-m-d', strtotime($round['display_start_at'])); ?><br>
                                <small style="color: #94a3b8;">
                                    ~ <?php echo date('Y-m-d', strtotime($round['display_end_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <?php echo number_format($round['min_bid_amount']); ?>원 ~<br>
                                <?php echo number_format($round['max_bid_amount']); ?>원
                            </td>
                            <td>
                                <?php echo $round['rotation_type'] === 'fixed' ? '고정' : '순환 (' . $round['rotation_interval_minutes'] . '분)'; ?>
                            </td>
                            <td>
                                <a href="/MVNO/seller/bidding/participate.php?round_id=<?php echo $round['id']; ?>" class="action-link" style="background: #3b82f6; color: white; padding: 8px 16px; border-radius: 6px; font-weight: 600;">
                                    입찰 참여
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/seller-footer.php'; ?>

