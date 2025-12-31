<?php
/**
 * 예치금 잔액을 부가세 포함 금액으로 수정하는 스크립트
 * 
 * 기존에 공급가액 기준으로 저장된 잔액을 
 * 입금 신청 내역을 기반으로 부가세 포함 금액으로 재계산합니다.
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>예치금 잔액 부가세 포함으로 수정</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .info {
            background: #dbeafe;
            color: #1e40af;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 20px;
        }
        .button:hover {
            background: #4f46e5;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>예치금 잔액 부가세 포함으로 수정</h1>
        
        <?php
        try {
            $pdo->beginTransaction();
            
            // 모든 판매자 조회
            $sellersStmt = $pdo->query("SELECT DISTINCT seller_id FROM deposit_requests");
            $sellers = $sellersStmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<div class='info'>총 " . count($sellers) . "명의 판매자를 찾았습니다.</div>";
            
            $updatedCount = 0;
            $details = [];
            
            foreach ($sellers as $sellerId) {
                // 해당 판매자의 입금 신청 내역 조회 (입금 확인된 것만)
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM(CASE WHEN status = 'confirmed' THEN amount ELSE 0 END) as total_deposit_amount,
                        SUM(CASE WHEN status = 'confirmed' THEN supply_amount ELSE 0 END) as total_deposit_supply
                    FROM deposit_requests 
                    WHERE seller_id = :seller_id
                ");
                $stmt->execute([':seller_id' => $sellerId]);
                $depositData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $totalDepositAmount = floatval($depositData['total_deposit_amount'] ?? 0); // 부가세 포함
                $totalDepositSupply = floatval($depositData['total_deposit_supply'] ?? 0); // 공급가액만
                
                // 광고 신청 차감 내역 조회 (부가세 포함 기준으로 차감된 것)
                $stmt = $pdo->prepare("
                    SELECT SUM(ABS(amount)) as total_withdraw
                    FROM seller_deposit_ledger 
                    WHERE seller_id = :seller_id AND transaction_type = 'withdraw'
                ");
                $stmt->execute([':seller_id' => $sellerId]);
                $withdrawData = $stmt->fetch(PDO::FETCH_ASSOC);
                $totalWithdraw = floatval($withdrawData['total_withdraw'] ?? 0);
                
                // 현재 잔액 조회
                $stmt = $pdo->prepare("SELECT balance FROM seller_deposit_accounts WHERE seller_id = :seller_id");
                $stmt->execute([':seller_id' => $sellerId]);
                $balanceData = $stmt->fetch(PDO::FETCH_ASSOC);
                $currentBalance = floatval($balanceData['balance'] ?? 0);
                
                // 부가세 포함 총액 기준 잔액 계산
                $correctBalance = $totalDepositAmount - $totalWithdraw;
                
                // 잔액이 다른 경우에만 업데이트
                if (abs($currentBalance - $correctBalance) >= 0.01) {
                    // 잔액 업데이트
                    $updateStmt = $pdo->prepare("
                        UPDATE seller_deposit_accounts 
                        SET balance = :balance, updated_at = NOW()
                        WHERE seller_id = :seller_id
                    ");
                    $updateStmt->execute([
                        ':balance' => $correctBalance,
                        ':seller_id' => $sellerId
                    ]);
                    
                    $details[] = [
                        'seller_id' => $sellerId,
                        'old_balance' => $currentBalance,
                        'new_balance' => $correctBalance,
                        'difference' => $correctBalance - $currentBalance,
                        'total_deposit_amount' => $totalDepositAmount,
                        'total_deposit_supply' => $totalDepositSupply,
                        'total_withdraw' => $totalWithdraw
                    ];
                    $updatedCount++;
                }
            }
            
            $pdo->commit();
            
            echo "<div class='success'>✓ " . $updatedCount . "명의 판매자 잔액이 업데이트되었습니다.</div>";
            
            if (!empty($details)) {
                echo "<h2>업데이트 상세 내역</h2>";
                echo "<table>";
                echo "<thead>";
                echo "<tr>";
                echo "<th>판매자 ID</th>";
                echo "<th class='text-right'>입금 총액 (부가세 포함)</th>";
                echo "<th class='text-right'>입금 총액 (공급가액만)</th>";
                echo "<th class='text-right'>차감 총액</th>";
                echo "<th class='text-right'>기존 잔액</th>";
                echo "<th class='text-right'>새 잔액 (부가세 포함)</th>";
                echo "<th class='text-right'>차이</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
                foreach ($details as $detail) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($detail['seller_id']) . "</td>";
                    echo "<td class='text-right'>" . number_format($detail['total_deposit_amount'], 0) . "원</td>";
                    echo "<td class='text-right'>" . number_format($detail['total_deposit_supply'], 0) . "원</td>";
                    echo "<td class='text-right'>" . number_format($detail['total_withdraw'], 0) . "원</td>";
                    echo "<td class='text-right'>" . number_format($detail['old_balance'], 0) . "원</td>";
                    echo "<td class='text-right'><strong>" . number_format($detail['new_balance'], 0) . "원</strong></td>";
                    $diffColor = $detail['difference'] > 0 ? '#10b981' : '#ef4444';
                    echo "<td class='text-right' style='color: $diffColor; font-weight: 600;'>" . number_format($detail['difference'], 0) . "원</td>";
                    echo "</tr>";
                }
                echo "</tbody>";
                echo "</table>";
            } else {
                echo "<div class='info'>업데이트할 데이터가 없습니다. 모든 잔액이 이미 올바르게 설정되어 있습니다.</div>";
            }
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "<div class='error'>❌ 오류 발생: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div style='margin-top: 10px; padding: 10px; background: #f3f4f6; border-radius: 4px; font-family: monospace; font-size: 12px;'>" . htmlspecialchars($e->getTraceAsString()) . "</div>";
        }
        ?>
        
        <a href="/MVNO/seller/deposit/history.php" class="button">예치금 내역 페이지로 이동</a>
    </div>
</body>
</html>
