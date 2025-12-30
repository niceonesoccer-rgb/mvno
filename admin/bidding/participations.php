<?php
/**
 * 입찰 현황 페이지 (관리자)
 * 경로: /admin/bidding/participations.php
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

$pdo = getDBConnection();
$participations = [];
$error = null;

// 필터 파라미터
$roundId = $_GET['round_id'] ?? null;
$status = $_GET['status'] ?? null;
$category = $_GET['category'] ?? null;

try {
    if ($pdo) {
        $whereConditions = [];
        $params = [];
        
        if ($roundId) {
            $whereConditions[] = 'bp.bidding_round_id = :round_id';
            $params[':round_id'] = $roundId;
        }
        
        if ($status) {
            $whereConditions[] = 'bp.status = :status';
            $params[':status'] = $status;
        }
        
        if ($category) {
            $whereConditions[] = 'br.category = :category';
            $params[':category'] = $category;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $stmt = $pdo->prepare("
            SELECT 
                bp.*,
                br.category,
                br.bidding_start_at,
                br.bidding_end_at,
                u.company_name as seller_name,
                u.user_id as seller_id
            FROM bidding_participations bp
            INNER JOIN bidding_rounds br ON bp.bidding_round_id = br.id
            LEFT JOIN users u ON bp.seller_id = u.user_id
            {$whereClause}
            ORDER BY bp.bid_at DESC
        ");
        $stmt->execute($params);
        $participations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "입찰 현황을 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
    error_log("Bidding participations list error: " . $e->getMessage());
}

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
    
    .filter-section {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    
    .filter-row {
        display: flex;
        gap: 16px;
        align-items: flex-end;
    }
    
    .filter-group {
        flex: 1;
    }
    
    .filter-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #475569;
        font-size: 14px;
    }
    
    .filter-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
    }
    
    .btn-filter {
        background: #3b82f6;
        color: white;
        padding: 10px 24px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-weight: 600;
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
        padding: 60px 20px;
        color: #94a3b8;
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
    <h1 class="page-title">입찰 현황</h1>
</div>

<?php if ($error): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="filter-section">
    <form method="GET" action="">
        <div class="filter-row">
            <div class="filter-group">
                <label class="filter-label">카테고리</label>
                <select name="category" class="filter-input">
                    <option value="">전체</option>
                    <option value="mno" <?php echo $category === 'mno' ? 'selected' : ''; ?>>통신사폰</option>
                    <option value="mvno" <?php echo $category === 'mvno' ? 'selected' : ''; ?>>알뜰폰</option>
                    <option value="mno_sim" <?php echo $category === 'mno_sim' ? 'selected' : ''; ?>>통신사단독유심</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">상태</label>
                <select name="status" class="filter-input">
                    <option value="">전체</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>대기</option>
                    <option value="won" <?php echo $status === 'won' ? 'selected' : ''; ?>>낙찰</option>
                    <option value="lost" <?php echo $status === 'lost' ? 'selected' : ''; ?>>낙찰실패</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>취소</option>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit" class="btn-filter">조회</button>
            </div>
        </div>
    </form>
</div>

<div class="table-container">
    <?php if (empty($participations)): ?>
        <div class="empty-state">
            <div>📊</div>
            <h3>입찰 참여 내역이 없습니다</h3>
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>판매자</th>
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
                        <td><?php echo htmlspecialchars($participation['id']); ?></td>
                        <td><?php echo htmlspecialchars($participation['seller_name'] ?? $participation['seller_id']); ?></td>
                        <td><?php echo htmlspecialchars($participation['category']); ?></td>
                        <td><?php echo number_format($participation['bid_amount']); ?>원</td>
                        <td><?php echo $participation['rank'] ?? '-'; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $participation['status']; ?>">
                                <?php echo htmlspecialchars($statusLabels[$participation['status']] ?? $participation['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($participation['bid_at'])); ?></td>
                        <td>
                            <a href="/MVNO/admin/bidding/participation-detail.php?id=<?php echo $participation['id']; ?>" style="color: #3b82f6; text-decoration: none; font-weight: 500;">
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


