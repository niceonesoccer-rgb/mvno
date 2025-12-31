<?php
/**
 * 무통장 계좌 관리 페이지 (관리자)
 * 경로: /admin/deposit/bank-accounts.php
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

$currentUser = getCurrentUser();
$adminId = $currentUser['user_id'] ?? 'system';

$error = '';
$success = '';

// 계좌 등록/수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $bankName = trim($_POST['bank_name'] ?? '');
        $accountNumber = trim($_POST['account_number'] ?? '');
        $accountHolder = trim($_POST['account_holder'] ?? '');
        $displayOrder = intval($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $memo = trim($_POST['memo'] ?? '');
        $accountId = $action === 'edit' ? intval($_POST['account_id'] ?? 0) : 0;
        
        if (empty($bankName) || empty($accountNumber) || empty($accountHolder)) {
            $error = '은행명, 계좌번호, 예금주는 필수 입력 항목입니다.';
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO bank_accounts (bank_name, account_number, account_holder, display_order, is_active, memo)
                        VALUES (:bank_name, :account_number, :account_holder, :display_order, :is_active, :memo)
                    ");
                    $stmt->execute([
                        ':bank_name' => $bankName,
                        ':account_number' => $accountNumber,
                        ':account_holder' => $accountHolder,
                        ':display_order' => $displayOrder,
                        ':is_active' => $isActive,
                        ':memo' => $memo
                    ]);
                    $success = '계좌가 등록되었습니다.';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE bank_accounts 
                        SET bank_name = :bank_name,
                            account_number = :account_number,
                            account_holder = :account_holder,
                            display_order = :display_order,
                            is_active = :is_active,
                            memo = :memo
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':bank_name' => $bankName,
                        ':account_number' => $accountNumber,
                        ':account_holder' => $accountHolder,
                        ':display_order' => $displayOrder,
                        ':is_active' => $isActive,
                        ':memo' => $memo,
                        ':id' => $accountId
                    ]);
                    $success = '계좌 정보가 수정되었습니다.';
                }
            } catch (PDOException $e) {
                error_log('Bank account save error: ' . $e->getMessage());
                $error = '계좌 저장 중 오류가 발생했습니다.';
            }
        }
    } elseif ($action === 'delete') {
        $accountId = intval($_POST['account_id'] ?? 0);
        
        if ($accountId > 0) {
            try {
                // 입금 신청에서 사용 중인지 확인
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM deposit_requests WHERE bank_account_id = :id");
                $stmt->execute([':id' => $accountId]);
                $usageCount = $stmt->fetchColumn();
                
                if ($usageCount > 0) {
                    $error = '이 계좌는 입금 신청에서 사용 중이어서 삭제할 수 없습니다. (사용 건수: ' . $usageCount . '건)';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM bank_accounts WHERE id = :id");
                    $stmt->execute([':id' => $accountId]);
                    $success = '계좌가 삭제되었습니다.';
                }
            } catch (PDOException $e) {
                error_log('Bank account delete error: ' . $e->getMessage());
                $error = '계좌 삭제 중 오류가 발생했습니다.';
            }
        }
    }
}

// 계좌 목록 조회
$stmt = $pdo->query("
    SELECT * FROM bank_accounts 
    ORDER BY display_order ASC, id DESC
");
$bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 수정할 계좌 정보 가져오기
$editAccount = null;
$editId = $_GET['edit'] ?? '';
if (!empty($editId)) {
    $stmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE id = :id");
    $stmt->execute([':id' => $editId]);
    $editAccount = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="admin-content-wrapper">
    <div class="admin-content">
        <div class="page-header">
            <h1>무통장 계좌 관리</h1>
            <p>예치금 입금용 무통장 계좌를 등록하고 관리합니다.</p>
        </div>
        
        <div class="content-box">
            <div style="padding: 24px;">
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
                
                <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0; font-size: 18px; font-weight: 600;">등록된 계좌 목록</h2>
                    <button type="button" id="btnAddAccount" style="padding: 10px 20px; background: #6366f1; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        + 계좌 등록
                    </button>
                </div>
                
                <?php if (empty($bankAccounts)): ?>
                    <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">🏦</div>
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #374151;">등록된 계좌가 없습니다</div>
                        <div style="font-size: 14px; margin-bottom: 24px;">위의 "계좌 등록" 버튼을 클릭하여 계좌를 추가하세요.</div>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden;">
                            <thead>
                                <tr style="background: #f1f5f9;">
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">순서</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">은행명</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">계좌번호</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">예금주</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">상태</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">메모</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bankAccounts as $account): ?>
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td style="padding: 12px;"><?= htmlspecialchars($account['display_order']) ?></td>
                                        <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($account['bank_name']) ?></td>
                                        <td style="padding: 12px;"><?= htmlspecialchars($account['account_number']) ?></td>
                                        <td style="padding: 12px;"><?= htmlspecialchars($account['account_holder']) ?></td>
                                        <td style="padding: 12px; text-align: center;">
                                            <?php if ($account['is_active']): ?>
                                                <span style="padding: 4px 12px; background: #d1fae5; color: #065f46; border-radius: 4px; font-size: 14px; font-weight: 500;">
                                                    활성
                                                </span>
                                            <?php else: ?>
                                                <span style="padding: 4px 12px; background: #fee2e2; color: #991b1b; border-radius: 4px; font-size: 14px; font-weight: 500;">
                                                    비활성
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px; color: #64748b;">
                                            <?= htmlspecialchars($account['memo'] ?? '-') ?>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <div style="display: flex; gap: 8px; justify-content: center;">
                                                <a href="?edit=<?= $account['id'] ?>" style="padding: 6px 12px; background: #3b82f6; color: #fff; border-radius: 4px; text-decoration: none; font-size: 13px;">
                                                    수정
                                                </a>
                                                <button type="button" onclick="deleteAccount(<?= $account['id'] ?>)" style="padding: 6px 12px; background: #ef4444; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">
                                                    삭제
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 계좌 등록/수정 모달 -->
<div id="accountModal" style="display: <?= $editAccount ? 'flex' : 'none' ?>; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 32px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 600;">
                <?= $editAccount ? '계좌 수정' : '계좌 등록' ?>
            </h2>
            <button type="button" onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        
        <form method="POST" id="accountForm">
            <input type="hidden" name="action" value="<?= $editAccount ? 'edit' : 'add' ?>">
            <?php if ($editAccount): ?>
                <input type="hidden" name="account_id" value="<?= $editAccount['id'] ?>">
            <?php endif; ?>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    은행명 <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="bank_name" value="<?= htmlspecialchars($editAccount['bank_name'] ?? '') ?>" required
                       style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;"
                       placeholder="예: 국민은행, 신한은행">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    계좌번호 <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="account_number" value="<?= htmlspecialchars($editAccount['account_number'] ?? '') ?>" required
                       style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;"
                       placeholder="계좌번호를 입력하세요">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    예금주 <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="account_holder" value="<?= htmlspecialchars($editAccount['account_holder'] ?? '') ?>" required
                       style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;"
                       placeholder="예금주명을 입력하세요">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    표시 순서
                </label>
                <input type="number" name="display_order" value="<?= htmlspecialchars($editAccount['display_order'] ?? '0') ?>"
                       style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;"
                       placeholder="숫자가 작을수록 앞에 표시됩니다">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" <?= ($editAccount['is_active'] ?? 1) ? 'checked' : '' ?> 
                           style="width: 18px; height: 18px;">
                    <span style="font-weight: 600; color: #374151;">활성화</span>
                </label>
                <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">
                    비활성화된 계좌는 판매자가 선택할 수 없습니다.
                </div>
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    메모 (관리자용)
                </label>
                <textarea name="memo" rows="3"
                          style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box; resize: vertical;"
                          placeholder="관리자용 메모를 입력하세요 (선택사항)"><?= htmlspecialchars($editAccount['memo'] ?? '') ?></textarea>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="submit" style="flex: 1; padding: 12px 24px; background: #6366f1; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                    저장
                </button>
                <button type="button" onclick="closeModal()" style="flex: 1; padding: 12px 24px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                    취소
                </button>
            </div>
        </form>
    </div>
</div>

<form method="POST" id="deleteForm" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="account_id" id="deleteAccountId">
</form>

<script>
function closeModal() {
    document.getElementById('accountModal').style.display = 'none';
    window.location.href = '<?= $_SERVER['PHP_SELF'] ?>';
}

document.getElementById('btnAddAccount')?.addEventListener('click', function() {
    window.location.href = '<?= $_SERVER['PHP_SELF'] ?>?add=1';
});

function deleteAccount(id) {
    if (confirm('정말 이 계좌를 삭제하시겠습니까?\n\n입금 신청에서 사용 중인 계좌는 삭제할 수 없습니다.')) {
        document.getElementById('deleteAccountId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// URL에 add 파라미터가 있으면 모달 열기
if (window.location.search.includes('add=1')) {
    document.getElementById('accountModal').style.display = 'flex';
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
