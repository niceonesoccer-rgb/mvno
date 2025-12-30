<?php
/**
 * 입찰 라운드 관리 페이지 (관리자)
 * 경로: /admin/bidding/rounds.php
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

$pdo = getDBConnection();
$rounds = [];
$error = null;

try {
    if ($pdo) {
        $stmt = $pdo->query("
            SELECT 
                br.*,
                u.user_id as created_by_user_id,
                u.company_name as created_by_name
            FROM bidding_rounds br
            LEFT JOIN users u ON br.created_by = u.user_id
            ORDER BY br.bidding_start_at DESC
        ");
        $rounds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "입찰 라운드 목록을 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
    error_log("Bidding rounds list error: " . $e->getMessage());
}

// 카테고리 라벨
$categoryLabels = [
    'mno' => '통신사폰',
    'mvno' => '알뜰폰',
    'mno_sim' => '통신사단독유심'
];

// 상태 라벨
$statusLabels = [
    'upcoming' => '예정',
    'bidding' => '입찰중',
    'closed' => '마감',
    'displaying' => '게시중',
    'finished' => '종료'
];
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    
    .page-title {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .table-container {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table th {
        background: #f8fafc;
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        color: #475569;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .table td {
        padding: 16px;
        border-bottom: 1px solid #e2e8f0;
        color: #1e293b;
    }
    
    .table tr:hover {
        background: #f8fafc;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-upcoming {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .badge-bidding {
        background: #fef3c7;
        color: #92400e;
    }
    
    .badge-closed {
        background: #e5e7eb;
        color: #374151;
    }
    
    .badge-displaying {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-finished {
        background: #f3f4f6;
        color: #6b7280;
    }
    
    .text-center {
        text-align: center;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }
    
    .empty-state-icon {
        font-size: 48px;
        margin-bottom: 16px;
    }
    
    .error-message {
        background: #fee2e2;
        color: #991b1b;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
</style>

<div class="page-header">
    <h1 class="page-title">입찰 라운드 관리</h1>
    <a href="/MVNO/admin/bidding/round-create.php" class="btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        라운드 생성
    </a>
</div>

<?php if ($error): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="table-container">
    <?php if (empty($rounds)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <h3>등록된 입찰 라운드가 없습니다</h3>
            <p>새로운 입찰 라운드를 생성해주세요.</p>
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>카테고리</th>
                    <th>입찰 기간</th>
                    <th>게시 기간</th>
                    <th>최소 입찰금액</th>
                    <th>최대 입찰금액</th>
                    <th>표시 방식</th>
                    <th>상태</th>
                    <th>생성자</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rounds as $round): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($round['id']); ?></td>
                        <td><?php echo htmlspecialchars($categoryLabels[$round['category']] ?? $round['category']); ?></td>
                        <td>
                            <?php 
                            echo date('Y-m-d H:i', strtotime($round['bidding_start_at'])); 
                            ?><br>
                            <small style="color: #94a3b8;">
                                ~ <?php echo date('Y-m-d H:i', strtotime($round['bidding_end_at'])); ?>
                            </small>
                        </td>
                        <td>
                            <?php 
                            echo date('Y-m-d', strtotime($round['display_start_at'])); 
                            ?><br>
                            <small style="color: #94a3b8;">
                                ~ <?php echo date('Y-m-d', strtotime($round['display_end_at'])); ?>
                            </small>
                        </td>
                        <td><?php echo number_format($round['min_bid_amount']); ?>원</td>
                        <td><?php echo number_format($round['max_bid_amount']); ?>원</td>
                        <td>
                            <?php 
                            if ($round['rotation_type'] === 'fixed') {
                                echo '고정';
                            } else {
                                echo '로테이션 (' . $round['rotation_interval_minutes'] . '분)';
                            }
                            ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $round['status']; ?>">
                                <?php echo htmlspecialchars($statusLabels[$round['status']] ?? $round['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($round['created_by_name'] ?? $round['created_by'] ?? '-'); ?></td>
                        <td>
                            <a href="/MVNO/admin/bidding/round-detail.php?id=<?php echo $round['id']; ?>" style="color: #3b82f6; text-decoration: none; font-weight: 500;">
                                상세보기
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>


