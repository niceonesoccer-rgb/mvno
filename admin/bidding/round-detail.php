<?php
/**
 * 입찰 라운드 상세/수정 페이지 (관리자)
 * 경로: /admin/bidding/round-detail.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    header('Location: /MVNO/admin/login.php');
    exit;
}

$pdo = getDBConnection();
$error = '';
$success = '';
$roundId = intval($_GET['id'] ?? 0);
$round = null;
$participations = [];

// 라운드 정보 조회
if ($roundId > 0) {
    try {
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM bidding_rounds WHERE id = :id");
            $stmt->execute([':id' => $roundId]);
            $round = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($round) {
                // 참여 내역 조회
                $partStmt = $pdo->prepare("
                    SELECT 
                        bp.*,
                        u.company_name as seller_name,
                        u.user_id as seller_id
                    FROM bidding_participations bp
                    LEFT JOIN users u ON bp.seller_id = u.user_id
                    WHERE bp.bidding_round_id = :round_id
                    ORDER BY bp.bid_amount DESC, bp.bid_at ASC
                ");
                $partStmt->execute([':round_id' => $roundId]);
                $participations = $partStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (PDOException $e) {
        $error = "정보를 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
        error_log("Bidding round detail load error: " . $e->getMessage());
    }
}

// POST 처리: 라운드 수정
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_round') {
    if (!$round) {
        $error = '입찰 라운드를 찾을 수 없습니다.';
    } else {
        $category = $_POST['category'] ?? '';
        $biddingStartAt = $_POST['bidding_start_at'] ?? '';
        $biddingEndAt = $_POST['bidding_end_at'] ?? '';
        $displayStartAt = $_POST['display_start_at'] ?? '';
        $displayEndAt = $_POST['display_end_at'] ?? '';
        $maxDisplayCount = intval($_POST['max_display_count'] ?? 20);
        $minBidAmount = floatval($_POST['min_bid_amount'] ?? 0);
        $maxBidAmount = floatval($_POST['max_bid_amount'] ?? 100000);
        $rotationType = $_POST['rotation_type'] ?? 'fixed';
        $rotationIntervalMinutes = $rotationType === 'rotating' ? intval($_POST['rotation_interval_minutes'] ?? 60) : null;
        
        // 유효성 검사
        if (empty($category) || !in_array($category, ['mno', 'mvno', 'mno_sim'])) {
            $error = '카테고리를 선택해주세요.';
        } elseif (empty($biddingStartAt) || empty($biddingEndAt)) {
            $error = '입찰 기간을 입력해주세요.';
        } elseif (empty($displayStartAt) || empty($displayEndAt)) {
            $error = '게시 기간을 입력해주세요.';
        } elseif (strtotime($biddingEndAt) <= strtotime($biddingStartAt)) {
            $error = '입찰 종료일시는 시작일시 이후여야 합니다.';
        } elseif (strtotime($displayEndAt) <= strtotime($displayStartAt)) {
            $error = '게시 종료일시는 시작일시 이후여야 합니다.';
        } elseif ($minBidAmount < 0) {
            $error = '최소 입찰금액은 0원 이상이어야 합니다.';
        } elseif ($maxBidAmount <= $minBidAmount) {
            $error = '최대 입찰금액은 최소 입찰금액보다 커야 합니다.';
        } elseif ($maxDisplayCount <= 0 || $maxDisplayCount > 100) {
            $error = '최대 노출 개수는 1~100 사이여야 합니다.';
        } elseif ($rotationType === 'rotating' && (!$rotationIntervalMinutes || $rotationIntervalMinutes < 1)) {
            $error = '순환 간격을 입력해주세요.';
        } else {
            try {
                if (!$pdo) {
                    throw new Exception('DB 연결에 실패했습니다.');
                }
                
                $stmt = $pdo->prepare("
                    UPDATE bidding_rounds SET
                        category = :category,
                        bidding_start_at = :bidding_start_at,
                        bidding_end_at = :bidding_end_at,
                        display_start_at = :display_start_at,
                        display_end_at = :display_end_at,
                        max_display_count = :max_display_count,
                        min_bid_amount = :min_bid_amount,
                        max_bid_amount = :max_bid_amount,
                        rotation_type = :rotation_type,
                        rotation_interval_minutes = :rotation_interval_minutes
                    WHERE id = :id
                ");
                
                $stmt->execute([
                    ':category' => $category,
                    ':bidding_start_at' => $biddingStartAt,
                    ':bidding_end_at' => $biddingEndAt,
                    ':display_start_at' => $displayStartAt,
                    ':display_end_at' => $displayEndAt,
                    ':max_display_count' => $maxDisplayCount,
                    ':min_bid_amount' => $minBidAmount,
                    ':max_bid_amount' => $maxBidAmount,
                    ':rotation_type' => $rotationType,
                    ':rotation_interval_minutes' => $rotationIntervalMinutes,
                    ':id' => $roundId
                ]);
                
                $success = '입찰 라운드가 수정되었습니다.';
                
                // 정보 다시 조회
                $stmt = $pdo->prepare("SELECT * FROM bidding_rounds WHERE id = :id");
                $stmt->execute([':id' => $roundId]);
                $round = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $error = '입찰 라운드 수정 중 오류가 발생했습니다: ' . $e->getMessage();
                error_log("Bidding round update error: " . $e->getMessage());
            }
        }
    }
}

require_once __DIR__ . '/../includes/admin-header.php';

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

// 입찰 상태 라벨
$participationStatusLabels = [
    'pending' => '대기',
    'won' => '낙찰',
    'lost' => '낙찰실패',
    'cancelled' => '취소'
];

if (!$round) {
    $error = '입찰 라운드를 찾을 수 없습니다.';
}

// 날짜 포맷 변환 (datetime-local input용)
function formatDateTimeForInput($datetime) {
    if (!$datetime) return '';
    return date('Y-m-d\TH:i', strtotime($datetime));
}

function formatDateForInput($date) {
    if (!$date) return '';
    return date('Y-m-d', strtotime($date));
}
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
    }
    
    .page-title {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-secondary:hover {
        background: #e5e7eb;
    }
    
    .content-section {
        background: white;
        border-radius: 12px;
        padding: 32px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin-bottom: 24px;
    }
    
    .section-title {
        font-size: 20px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
    }
    
    .form-input,
    .form-select {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    
    .form-help {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        color: #6b7280;
    }
    
    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-weight: 600;
    }
    
    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        width: 150px;
        font-weight: 600;
        color: #64748b;
    }
    
    .info-value {
        flex: 1;
        color: #1e293b;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-upcoming { background: #dbeafe; color: #1e40af; }
    .badge-bidding { background: #fef3c7; color: #92400e; }
    .badge-closed { background: #e5e7eb; color: #374151; }
    .badge-displaying { background: #d1fae5; color: #065f46; }
    .badge-finished { background: #f3f4f6; color: #6b7280; }
    
    .badge-won { background: #d1fae5; color: #065f46; }
    .badge-lost { background: #fee2e2; color: #991b1b; }
    .badge-pending { background: #dbeafe; color: #1e40af; }
    .badge-cancelled { background: #f3f4f6; color: #6b7280; }
    
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
        border-bottom: 2px solid #e2e8f0;
    }
    
    .table td {
        padding: 16px;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .error-message {
        background: #fee2e2;
        color: #991b1b;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    
    .success-message {
        background: #d1fae5;
        color: #065f46;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    
    .rotation-settings {
        background: #f8fafc;
        padding: 16px;
        border-radius: 8px;
        margin-top: 12px;
        display: none;
    }
    
    .rotation-settings.active {
        display: block;
    }
</style>

<div class="page-header">
    <h1 class="page-title">입찰 라운드 상세</h1>
    <a href="/MVNO/admin/bidding/rounds.php" class="btn-secondary">목록으로</a>
</div>

<?php if ($error): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success-message">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if ($round): ?>
    <div class="content-section">
        <h2 class="section-title">기본 정보</h2>
        <div class="info-row">
            <div class="info-label">ID</div>
            <div class="info-value"><?php echo $round['id']; ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">상태</div>
            <div class="info-value">
                <span class="badge badge-<?php echo $round['status']; ?>">
                    <?php echo htmlspecialchars($statusLabels[$round['status']] ?? $round['status']); ?>
                </span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">생성일시</div>
            <div class="info-value"><?php echo date('Y-m-d H:i:s', strtotime($round['created_at'])); ?></div>
        </div>
    </div>
    
    <div class="content-section">
        <h2 class="section-title">라운드 정보 수정</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_round">
            
            <div class="form-group">
                <label class="form-label">카테고리</label>
                <select name="category" class="form-select" required>
                    <option value="mno" <?php echo $round['category'] === 'mno' ? 'selected' : ''; ?>>통신사폰</option>
                    <option value="mvno" <?php echo $round['category'] === 'mvno' ? 'selected' : ''; ?>>알뜰폰</option>
                    <option value="mno_sim" <?php echo $round['category'] === 'mno_sim' ? 'selected' : ''; ?>>통신사단독유심</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">입찰 기간</label>
                <div class="form-row">
                    <div>
                        <input type="datetime-local" name="bidding_start_at" class="form-input" value="<?php echo formatDateTimeForInput($round['bidding_start_at']); ?>" required>
                    </div>
                    <div>
                        <input type="datetime-local" name="bidding_end_at" class="form-input" value="<?php echo formatDateTimeForInput($round['bidding_end_at']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">게시 기간</label>
                <div class="form-row">
                    <div>
                        <input type="date" name="display_start_at" class="form-input" value="<?php echo formatDateForInput($round['display_start_at']); ?>" required>
                    </div>
                    <div>
                        <input type="date" name="display_end_at" class="form-input" value="<?php echo formatDateForInput($round['display_end_at']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">최대 노출 개수</label>
                <input type="number" name="max_display_count" class="form-input" value="<?php echo $round['max_display_count']; ?>" min="1" max="100" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">입찰 금액 범위</label>
                <div class="form-row">
                    <div>
                        <input type="number" name="min_bid_amount" class="form-input" value="<?php echo $round['min_bid_amount']; ?>" min="0" step="1000" required>
                    </div>
                    <div>
                        <input type="number" name="max_bid_amount" class="form-input" value="<?php echo $round['max_bid_amount']; ?>" min="0" step="1000" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">운용 방식</label>
                <select name="rotation_type" class="form-select" id="rotationType" required>
                    <option value="fixed" <?php echo $round['rotation_type'] === 'fixed' ? 'selected' : ''; ?>>고정</option>
                    <option value="rotating" <?php echo $round['rotation_type'] === 'rotating' ? 'selected' : ''; ?>>순환</option>
                </select>
                
                <div class="rotation-settings" id="rotationSettings">
                    <label class="form-label" style="margin-top: 16px;">순환 간격 (분)</label>
                    <input type="number" name="rotation_interval_minutes" class="form-input" value="<?php echo $round['rotation_interval_minutes'] ?? 60; ?>" min="1">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">수정</button>
            </div>
        </form>
    </div>
    
    <div class="content-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 class="section-title" style="margin: 0;">입찰 참여 내역 (<?php echo count($participations); ?>건)</h2>
            <?php if ($round['status'] === 'closed'): ?>
                <a href="/MVNO/admin/bidding/award.php?round_id=<?php echo $roundId; ?>" class="btn-primary" style="text-decoration: none; display: inline-block;">
                    낙찰 처리
                </a>
            <?php endif; ?>
        </div>
        <?php if (empty($participations)): ?>
            <p style="color: #94a3b8; text-align: center; padding: 40px;">입찰 참여 내역이 없습니다.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>순위</th>
                        <th>판매자</th>
                        <th>입찰금액</th>
                        <th>상태</th>
                        <th>입찰일시</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participations as $participation): ?>
                        <tr>
                            <td><?php echo $participation['rank'] ?? '-'; ?></td>
                            <td><?php echo htmlspecialchars($participation['seller_name'] ?? $participation['seller_id']); ?></td>
                            <td><strong><?php echo number_format($participation['bid_amount']); ?>원</strong></td>
                            <td>
                                <span class="badge badge-<?php echo $participation['status']; ?>">
                                    <?php echo htmlspecialchars($participationStatusLabels[$participation['status']] ?? $participation['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($participation['bid_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="content-section">
        <p style="color: #991b1b;">입찰 라운드를 찾을 수 없습니다.</p>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rotationType = document.getElementById('rotationType');
    const rotationSettings = document.getElementById('rotationSettings');
    
    function updateRotationSettings() {
        if (rotationType.value === 'rotating') {
            rotationSettings.classList.add('active');
        } else {
            rotationSettings.classList.remove('active');
        }
    }
    
    rotationType.addEventListener('change', updateRotationSettings);
    updateRotationSettings();
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

