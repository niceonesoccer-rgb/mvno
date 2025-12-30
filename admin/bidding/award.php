<?php
/**
 * 낙찰 처리 페이지 (관리자)
 * 경로: /admin/bidding/award.php
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
$roundId = intval($_GET['round_id'] ?? $_POST['round_id'] ?? 0);
$round = null;
$participations = [];
$winners = [];

// 라운드 정보 및 참여 내역 조회
if ($roundId > 0) {
    try {
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM bidding_rounds WHERE id = :id");
            $stmt->execute([':id' => $roundId]);
            $round = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($round) {
                // 모든 참여 내역 조회 (pending 상태만, 입찰 금액 내림차순, 입찰 시간 오름차순)
                $partStmt = $pdo->prepare("
                    SELECT 
                        bp.*,
                        u.company_name as seller_name,
                        u.user_id as seller_id
                    FROM bidding_participations bp
                    LEFT JOIN users u ON bp.seller_id = u.user_id
                    WHERE bp.bidding_round_id = :round_id
                      AND bp.status = 'pending'
                    ORDER BY bp.bid_amount DESC, bp.bid_at ASC
                ");
                $partStmt->execute([':round_id' => $roundId]);
                $participations = $partStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 상위 max_display_count개만 낙찰 후보
                $maxDisplayCount = $round['max_display_count'];
                $winners = array_slice($participations, 0, $maxDisplayCount);
            }
        }
    } catch (PDOException $e) {
        $error = "정보를 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
        error_log("Bidding award load error: " . $e->getMessage());
    }
}

// POST 처리: 낙찰 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_award') {
    if (!$round) {
        $error = '입찰 라운드를 찾을 수 없습니다.';
    } elseif ($round['status'] !== 'closed') {
        $error = '입찰이 종료되지 않았습니다. (상태: ' . $round['status'] . ')';
    } else {
        try {
            if (!$pdo) {
                throw new Exception('DB 연결에 실패했습니다.');
            }
            
            $pdo->beginTransaction();
            $now = date('Y-m-d H:i:s');
            $maxDisplayCount = $round['max_display_count'];
            
            // 낙찰 처리
            $rank = 1;
            foreach ($winners as $winner) {
                $updateStmt = $pdo->prepare("
                    UPDATE bidding_participations 
                    SET status = 'won', rank = :rank, won_at = :won_at
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    ':rank' => $rank,
                    ':won_at' => $now,
                    ':id' => $winner['id']
                ]);
                $rank++;
            }
            
            // 미낙찰 처리 (나머지)
            $losers = array_slice($participations, $maxDisplayCount);
            foreach ($losers as $loser) {
                // 예치금 환불
                $refundAmount = $loser['deposit_used'];
                $depStmt = $pdo->prepare("SELECT balance FROM seller_deposits WHERE seller_id = :seller_id FOR UPDATE");
                $depStmt->execute([':seller_id' => $loser['seller_id']]);
                $deposit = $depStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($deposit) {
                    $newBalance = $deposit['balance'] + $refundAmount;
                    $updateStmt = $pdo->prepare("UPDATE seller_deposits SET balance = :balance WHERE seller_id = :seller_id");
                    $updateStmt->execute([':balance' => $newBalance, ':seller_id' => $loser['seller_id']]);
                    
                    // 거래 내역 기록
                    $transStmt = $pdo->prepare("
                        INSERT INTO seller_deposit_transactions (
                            seller_id, transaction_type, amount, balance_before, balance_after,
                            reference_id, reference_type, description, processed_by
                        ) VALUES (
                            :seller_id, 'refund', :amount, :balance_before, :balance_after,
                            :reference_id, :reference_type, :description, :processed_by
                        )
                    ");
                    $transStmt->execute([
                        ':seller_id' => $loser['seller_id'],
                        ':amount' => $refundAmount,
                        ':balance_before' => $deposit['balance'],
                        ':balance_after' => $newBalance,
                        ':reference_id' => $loser['id'],
                        ':reference_type' => 'bidding_participation',
                        ':description' => '낙찰 실패 환불',
                        ':processed_by' => $currentUser['user_id']
                    ]);
                }
                
                // 상태 변경
                $loserStmt = $pdo->prepare("UPDATE bidding_participations SET status = 'lost', deposit_refunded = :deposit_refunded WHERE id = :id");
                $loserStmt->execute([
                    ':deposit_refunded' => $refundAmount,
                    ':id' => $loser['id']
                ]);
            }
            
            // 라운드 상태 변경 (displaying으로)
            if (strtotime($round['display_start_at']) <= strtotime($now)) {
                $roundStmt = $pdo->prepare("UPDATE bidding_rounds SET status = 'displaying' WHERE id = :id");
                $roundStmt->execute([':id' => $roundId]);
            }
            
            $pdo->commit();
            
            // 성공 시 상세 페이지로 리다이렉트
            header('Location: /MVNO/admin/bidding/round-detail.php?id=' . $roundId . '&success=awarded');
            exit;
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = '낙찰 처리 중 오류가 발생했습니다: ' . $e->getMessage();
            error_log("Bidding award error: " . $e->getMessage());
        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
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

if (!$round) {
    $error = '입찰 라운드를 찾을 수 없습니다.';
}
?>

<style>
    .page-header {
        margin-bottom: 32px;
    }
    
    .page-title {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
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
        margin-bottom: 24px;
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
    
    .info-box {
        background: #f8fafc;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    
    .info-row {
        display: flex;
        padding: 8px 0;
        color: #374151;
    }
    
    .info-label {
        width: 120px;
        font-weight: 600;
        color: #64748b;
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
        border-bottom: 2px solid #e2e8f0;
    }
    
    .table td {
        padding: 16px;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .table tr.winner {
        background: #f0fdf4;
    }
    
    .table tr.loser {
        background: #fef2f2;
    }
    
    .badge-won {
        background: #d1fae5;
        color: #065f46;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-lost {
        background: #fee2e2;
        color: #991b1b;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .btn-primary:disabled {
        background: #d1d5db;
        cursor: not-allowed;
        transform: none;
    }
    
    .error-message {
        background: #fee2e2;
        color: #991b1b;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    
    .warning-message {
        background: #fef3c7;
        color: #92400e;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    
    .action-section {
        margin-top: 32px;
        padding-top: 24px;
        border-top: 2px solid #e2e8f0;
    }
</style>

<div class="page-header">
    <h1 class="page-title">낙찰 처리</h1>
</div>

<a href="/MVNO/admin/bidding/round-detail.php?id=<?php echo $roundId; ?>" class="btn-secondary">← 라운드 상세로</a>

<?php if ($error): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($round): ?>
    <div class="content-section">
        <div class="info-box">
            <div class="info-row">
                <div class="info-label">카테고리:</div>
                <div><?php echo htmlspecialchars($categoryLabels[$round['category']] ?? $round['category']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">최대 노출 개수:</div>
                <div><strong><?php echo $round['max_display_count']; ?>개</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">참여 건수:</div>
                <div><?php echo count($participations); ?>건</div>
            </div>
        </div>
        
        <h2 class="section-title">낙찰 결과 미리보기</h2>
        
        <?php if (empty($participations)): ?>
            <p style="color: #94a3b8; text-align: center; padding: 40px;">입찰 참여 내역이 없습니다.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>순위</th>
                        <th>판매자</th>
                        <th>입찰금액</th>
                        <th>입찰일시</th>
                        <th>결과</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($participations as $participation): 
                        $isWinner = $rank <= $round['max_display_count'];
                    ?>
                        <tr class="<?php echo $isWinner ? 'winner' : 'loser'; ?>">
                            <td><?php echo $rank; ?></td>
                            <td><?php echo htmlspecialchars($participation['seller_name'] ?? $participation['seller_id']); ?></td>
                            <td><strong><?php echo number_format($participation['bid_amount']); ?>원</strong></td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($participation['bid_at'])); ?></td>
                            <td>
                                <?php if ($isWinner): ?>
                                    <span class="badge-won">낙찰 예정</span>
                                <?php else: ?>
                                    <span class="badge-lost">낙찰 실패 (환불 예정)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                        $rank++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
            
            <?php if ($round['status'] === 'closed'): ?>
                <div class="action-section">
                    <div class="warning-message">
                        <strong>주의:</strong> 낙찰 처리를 진행하면 입찰 결과가 확정되고, 미낙찰자의 예치금이 자동으로 환불됩니다.
                    </div>
                    <form method="POST" action="" onsubmit="return confirm('정말 낙찰 처리를 진행하시겠습니까?\\n낙찰: <?php echo count($winners); ?>명\\n미낙찰 환불: <?php echo count($participations) - count($winners); ?>명');">
                        <input type="hidden" name="action" value="process_award">
                        <input type="hidden" name="round_id" value="<?php echo $roundId; ?>">
                        <button type="submit" class="btn-primary">낙찰 처리 실행</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="warning-message">
                    입찰이 종료된 상태(closed)에서만 낙찰 처리가 가능합니다. 현재 상태: <?php echo $round['status']; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="content-section">
        <p style="color: #991b1b;">입찰 라운드를 찾을 수 없습니다.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

