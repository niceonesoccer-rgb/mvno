<?php
/**
 * 예치금 충전 페이지 (판매자)
 * 경로: /seller/deposit/charge.php
 */

require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

// 인증 체크 (header 호출 전에 처리)
$currentUser = getCurrentUser();
$sellerId = $currentUser['user_id'] ?? '';

if (empty($sellerId)) {
    header('Location: /MVNO/seller/login.php');
    exit;
}

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

$error = '';
$success = '';

// 예치금 충전 신청 처리 (header 호출 전에 처리)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bankAccountId = intval($_POST['bank_account_id'] ?? 0);
    $depositorName = trim($_POST['depositor_name'] ?? '');
    $supplyAmount = floatval($_POST['supply_amount'] ?? 0);
    
    if (empty($bankAccountId) || empty($depositorName) || $supplyAmount <= 0) {
        $error = '모든 필드를 올바르게 입력해주세요.';
    } else {
        try {
            // 부가세 계산 (공급가액의 10%)
            $taxAmount = $supplyAmount * 0.1;
            $totalAmount = $supplyAmount + $taxAmount; // 입금 금액 = 공급가액 + 부가세
            
            $stmt = $pdo->prepare("
                INSERT INTO deposit_requests 
                (seller_id, bank_account_id, depositor_name, amount, supply_amount, tax_amount, status)
                VALUES (:seller_id, :bank_account_id, :depositor_name, :amount, :supply_amount, :tax_amount, 'pending')
            ");
            $stmt->execute([
                ':seller_id' => $sellerId,
                ':bank_account_id' => $bankAccountId,
                ':depositor_name' => $depositorName,
                ':amount' => $totalAmount,
                ':supply_amount' => $supplyAmount,
                ':tax_amount' => $taxAmount
            ]);
            
            // 폼 초기화를 위해 GET으로 리다이렉트
            header('Location: ?success=1');
            exit;
        } catch (PDOException $e) {
            error_log('Deposit charge error: ' . $e->getMessage());
            $error = '예치금 충전 신청 중 오류가 발생했습니다.';
        }
    }
}

if (isset($_GET['success'])) {
    $success = '예치금 충전 신청이 완료되었습니다. 관리자가 입금을 확인하면 예치금이 충전됩니다.';
}

// 모든 처리 완료 후 헤더 include
require_once __DIR__ . '/../includes/seller-header.php';

// 활성화된 계좌 목록 조회
$stmt = $pdo->query("
    SELECT * FROM bank_accounts 
    WHERE is_active = 1 
    ORDER BY display_order ASC, id DESC
");
$bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 예치금 잔액 조회
$stmt = $pdo->prepare("SELECT balance FROM seller_deposit_accounts WHERE seller_id = :seller_id");
$stmt->execute([':seller_id' => $sellerId]);
$balanceResult = $stmt->fetch(PDO::FETCH_ASSOC);
$balance = floatval($balanceResult['balance'] ?? 0);
?>

<div class="seller-center-container">
    <div class="page-header" style="margin-bottom: 32px;">
        <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">예치금 충전</h1>
        <p style="font-size: 16px; color: #64748b;">무통장 입금을 통해 예치금을 충전할 수 있습니다.</p>
    </div>
    
    <div class="content-box" style="background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 60%; margin: 0 auto;">
        <?php if ($error): ?>
            <div style="padding: 12px; background: #fee2e2; color: #991b1b; border-radius: 6px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div style="padding: 12px; background: #d1fae5; color: #065f46; border-radius: 6px; margin-bottom: 20px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-bottom: 24px; padding: 20px; background: #f8fafc; border-radius: 8px;">
            <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">현재 예치금 잔액</div>
            <div style="font-size: 32px; font-weight: 700; color: #6366f1;"><?= number_format($balance, 0) ?>원</div>
        </div>
        
        <?php if (empty($bankAccounts)): ?>
            <div style="padding: 40px; text-align: center; color: #64748b;">
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">입금 계좌가 등록되지 않았습니다</div>
                <div>관리자에게 문의해주세요.</div>
            </div>
        <?php else: ?>
            <form method="POST">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        입금 계좌 <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="bank_account_id" required
                            style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;">
                        <option value="">계좌를 선택하세요</option>
                        <?php foreach ($bankAccounts as $account): ?>
                            <option value="<?= $account['id'] ?>">
                                <?= htmlspecialchars($account['bank_name']) ?> 
                                <?= htmlspecialchars($account['account_number']) ?> 
                                (<?= htmlspecialchars($account['account_holder']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        입금자명 <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="depositor_name" required
                           style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;"
                           placeholder="입금자명을 입력하세요">
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        충전 금액 (공급가액) <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="supply_amount" id="supply_amount" required
                            style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;">
                        <option value="">금액을 선택하세요</option>
                        <?php
                        // 10만원부터 90만원까지 10만원 단위
                        for ($i = 10; $i <= 90; $i += 10) {
                            $amount = $i * 10000;
                            echo '<option value="' . $amount . '">' . number_format($amount, 0) . '원</option>';
                        }
                        // 100만원부터 1000만원까지 100만원 단위
                        for ($i = 100; $i <= 1000; $i += 100) {
                            $amount = $i * 10000;
                            echo '<option value="' . $amount . '">' . number_format($amount, 0) . '원</option>';
                        }
                        ?>
                    </select>
                    <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">
                        부가세 10%가 자동으로 추가되어 입금 금액이 계산됩니다.
                    </div>
                    <div id="amountPreview" style="margin-top: 12px; padding: 12px; background: #f8fafc; border-radius: 6px; display: none;">
                        <div style="font-size: 14px; color: #64748b; margin-bottom: 4px;">입금 금액 (부가세 포함):</div>
                        <div style="font-size: 20px; font-weight: 600; color: #6366f1;" id="totalAmount">0원</div>
                    </div>
                </div>
                
                <button type="submit" 
                        style="width: 100%; padding: 14px 24px; background: #6366f1; color: #fff; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">
                    예치금 충전 신청
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('supply_amount')?.addEventListener('change', function() {
    const supplyAmount = parseFloat(this.value) || 0;
    const previewDiv = document.getElementById('amountPreview');
    
    if (supplyAmount > 0) {
        const taxAmount = supplyAmount * 0.1;
        const totalAmount = supplyAmount + taxAmount;
        document.getElementById('totalAmount').textContent = new Intl.NumberFormat('ko-KR').format(Math.round(totalAmount)) + '원';
        previewDiv.style.display = 'block';
    } else {
        previewDiv.style.display = 'none';
    }
});
</script>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>
