<?php
/**
 * 예치금 조정 페이지 (관리자)
 * 경로: /admin/deposit/adjust.php
 * 
 * 기능: 관리자가 판매자의 예치금을 직접 조정할 수 있습니다.
 * - 환불: 예치금 차감 (-)
 * - 차감: 예치금 차감 (-)
 * - 서비스: 예치금 충전 (+)
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/path-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

$currentUser = getCurrentUser();
$adminId = $currentUser['user_id'] ?? 'system';

$error = '';
$success = '';

// 예치금 조정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'adjust') {
        try {
            $pdo->beginTransaction();
            
            $sellerId = trim($_POST['seller_id'] ?? '');
            $amount = floatval($_POST['amount'] ?? 0);
            $adjustmentType = trim($_POST['adjustment_type'] ?? ''); // 'refund', 'deduct', 'service'
            $reason = trim($_POST['reason'] ?? '');
            
            // 유효성 검사
            if (empty($sellerId)) {
                throw new Exception('판매자 아이디를 입력해주세요.');
            }
            
            if ($amount <= 0) {
                throw new Exception('금액은 0보다 커야 합니다.');
            }
            
            if (!in_array($adjustmentType, ['refund', 'deduct', 'service'])) {
                throw new Exception('올바른 조정 유형을 선택해주세요.');
            }
            
            if (empty($reason)) {
                throw new Exception('사유를 입력해주세요.');
            }
            
            // 판매자 존재 확인
            $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE user_id = :seller_id AND role = 'seller'");
            $stmt->execute([':seller_id' => $sellerId]);
            $seller = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$seller) {
                throw new Exception('판매자를 찾을 수 없습니다.');
            }
            
            // 예치금 계좌 확인 및 생성
            $pdo->prepare("
                INSERT IGNORE INTO seller_deposit_accounts (seller_id, balance, created_at)
                VALUES (:seller_id, 0, NOW())
            ")->execute([':seller_id' => $sellerId]);
            
            // 현재 잔액 조회 (잠금)
            $stmt = $pdo->prepare("SELECT balance FROM seller_deposit_accounts WHERE seller_id = :seller_id FOR UPDATE");
            $stmt->execute([':seller_id' => $sellerId]);
            $balanceData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$balanceData) {
                throw new Exception('예치금 계좌를 찾을 수 없습니다.');
            }
            
            $currentBalance = floatval($balanceData['balance'] ?? 0);
            
            // 조정 금액 계산
            // 환불, 차감: 음수 (-), 서비스: 양수 (+)
            $adjustmentAmount = ($adjustmentType === 'service') ? $amount : -$amount;
            $newBalance = $currentBalance + $adjustmentAmount;
            
            // 차감/환불 시 잔액 부족 확인
            if ($adjustmentType !== 'service' && $newBalance < 0) {
                throw new Exception('예치금 잔액이 부족합니다. (현재 잔액: ' . number_format($currentBalance) . '원)');
            }
            
            // 예치금 업데이트
            $stmt = $pdo->prepare("
                UPDATE seller_deposit_accounts 
                SET balance = :balance, updated_at = NOW()
                WHERE seller_id = :seller_id
            ");
            $stmt->execute([
                ':balance' => $newBalance,
                ':seller_id' => $sellerId
            ]);
            
            // 거래 내역 기록
            $typeLabels = [
                'refund' => '환불',
                'deduct' => '차감',
                'service' => '서비스'
            ];
            $description = '관리자 조정: ' . $typeLabels[$adjustmentType] . ' (' . $reason . ')';
            
            // transaction_type 결정: refund -> 'refund', deduct -> 'withdraw', service -> 'deposit'
            $transactionType = 'withdraw';
            if ($adjustmentType === 'refund') {
                $transactionType = 'refund';
            } elseif ($adjustmentType === 'service') {
                $transactionType = 'deposit';
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO seller_deposit_ledger (
                    seller_id, transaction_type, amount, balance_before, balance_after,
                    description, created_at
                ) VALUES (
                    :seller_id, :transaction_type, :amount, :balance_before, :balance_after,
                    :description, NOW()
                )
            ");
            $stmt->execute([
                ':seller_id' => $sellerId,
                ':transaction_type' => $transactionType,
                ':amount' => $adjustmentAmount,
                ':balance_before' => $currentBalance,
                ':balance_after' => $newBalance,
                ':description' => $description
            ]);
            
            $pdo->commit();
            
            $typeLabel = $typeLabels[$adjustmentType];
            $sign = ($adjustmentType === 'service') ? '+' : '-';
            $success = '예치금 조정이 완료되었습니다. (' . $typeLabel . ': ' . $sign . number_format($amount) . '원, 잔액: ' . number_format($newBalance) . '원)';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Deposit adjust error: ' . $e->getMessage());
            $error = $e->getMessage();
        }
    }
}

// 판매자 목록 조회 (자동완성용)
$sellerList = [];
if (isset($_GET['seller_search'])) {
    $searchTerm = trim($_GET['seller_search'] ?? '');
    if (!empty($searchTerm)) {
        $stmt = $pdo->prepare("
            SELECT user_id, name 
            FROM users 
            WHERE role = 'seller' 
            AND (user_id LIKE :search OR name LIKE :search)
            ORDER BY user_id ASC
            LIMIT 20
        ");
        $stmt->execute([':search' => '%' . $searchTerm . '%']);
        $sellerList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<div class="admin-content-wrapper">
    <div class="admin-content">
        <div class="page-header">
            <h1>예치금 조정</h1>
            <p>판매자의 예치금을 직접 조정할 수 있습니다.</p>
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
                
                <form method="POST" id="adjustForm" style="max-width: 600px;">
                    <input type="hidden" name="action" value="adjust">
                    
                    <!-- 판매자 선택 -->
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            판매자 아이디 <span style="color: #ef4444;">*</span>
                        </label>
                        <div style="position: relative;">
                            <input type="text" 
                                   id="seller_id" 
                                   name="seller_id" 
                                   required
                                   autocomplete="off"
                                   placeholder="판매자 아이디 입력"
                                   style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;"
                                   oninput="searchSeller(this.value)"
                                   onblur="validateSellerId(this.value)">
                            <div id="sellerSearchResults" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #d1d5db; border-radius: 8px; margin-top: 4px; max-height: 200px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                            </div>
                        </div>
                        <div id="sellerIdValidation" style="font-size: 13px; margin-top: 6px;"></div>
                        <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">
                            판매자 아이디를 입력하거나 검색해주세요.
                        </div>
                    </div>
                    
                    <!-- 조정 유형 선택 -->
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            조정 유형 <span style="color: #ef4444;">*</span>
                        </label>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <label style="flex: 1; min-width: 150px; padding: 16px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.3s; background: #fff;" 
                                   onmouseover="this.style.borderColor='#ef4444'; this.style.background='#fef2f2';" 
                                   onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='#fff';">
                                <input type="radio" name="adjustment_type" value="refund" required style="margin-right: 8px;">
                                <span style="font-weight: 600; color: #ef4444;">환불</span>
                                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">예치금 차감 (-)</div>
                            </label>
                            <label style="flex: 1; min-width: 150px; padding: 16px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.3s; background: #fff;" 
                                   onmouseover="this.style.borderColor='#ef4444'; this.style.background='#fef2f2';" 
                                   onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='#fff';">
                                <input type="radio" name="adjustment_type" value="deduct" required style="margin-right: 8px;">
                                <span style="font-weight: 600; color: #ef4444;">차감</span>
                                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">예치금 차감 (-)</div>
                            </label>
                            <label style="flex: 1; min-width: 150px; padding: 16px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.3s; background: #fff;" 
                                   onmouseover="this.style.borderColor='#10b981'; this.style.background='#f0fdf4';" 
                                   onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='#fff';">
                                <input type="radio" name="adjustment_type" value="service" required style="margin-right: 8px;">
                                <span style="font-weight: 600; color: #10b981;">서비스</span>
                                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">예치금 충전 (+)</div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- 금액 입력 -->
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            금액 <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" 
                               name="amount" 
                               required 
                               step="0.01" 
                               min="0.01"
                               placeholder="금액 입력"
                               style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;">
                        <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">
                            조정할 금액을 입력해주세요.
                        </div>
                    </div>
                    
                    <!-- 사유 입력 -->
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            사유 <span style="color: #ef4444;">*</span>
                        </label>
                        <textarea name="reason" 
                                  required 
                                  rows="4"
                                  placeholder="조정 사유를 입력해주세요."
                                  style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box; resize: vertical;"></textarea>
                    </div>
                    
                    <!-- 현재 잔액 표시 (판매자 선택 시) -->
                    <div id="currentBalanceInfo" style="display: none; margin-bottom: 24px; padding: 16px; background: #f8fafc; border-radius: 8px;">
                        <div style="font-size: 14px; color: #64748b; margin-bottom: 4px;">현재 예치금 잔액</div>
                        <div style="font-size: 20px; font-weight: 600; color: #6366f1;" id="currentBalanceAmount">-</div>
                    </div>
                    
                    <!-- 조정 후 예상 잔액 표시 -->
                    <div id="previewInfo" style="display: none; margin-bottom: 24px; padding: 16px; background: #fef3c7; border-radius: 8px;">
                        <div style="font-size: 14px; color: #92400e; margin-bottom: 4px;">조정 후 예상 잔액</div>
                        <div style="font-size: 18px; font-weight: 600; color: #92400e;" id="previewBalanceAmount">-</div>
                    </div>
                    
                    <!-- 제출 버튼 -->
                    <div style="display: flex; gap: 12px;">
                        <button type="submit" 
                                style="flex: 1; padding: 12px 24px; background: #6366f1; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                            조정 처리
                        </button>
                        <button type="reset" 
                                onclick="resetForm()"
                                style="flex: 1; padding: 12px 24px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                            초기화
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let currentBalance = 0;
let searchTimeout = null;

// 판매자 검색
function searchSeller(searchTerm) {
    clearTimeout(searchTimeout);
    
    if (searchTerm.length < 1) {
        document.getElementById('sellerSearchResults').style.display = 'none';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch(`?seller_search=${encodeURIComponent(searchTerm)}`)
            .then(response => response.text())
            .then(html => {
                // HTML에서 판매자 목록 추출
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const results = doc.getElementById('sellerSearchResults');
                
                if (results && results.innerHTML.trim()) {
                    document.getElementById('sellerSearchResults').innerHTML = results.innerHTML;
                    document.getElementById('sellerSearchResults').style.display = 'block';
                } else {
                    document.getElementById('sellerSearchResults').style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Search error:', error);
            });
    }, 300);
}

// 판매자 아이디 유효성 검사
function validateSellerId(sellerId) {
    const validationDiv = document.getElementById('sellerIdValidation');
    
    if (!sellerId || sellerId.trim() === '') {
        validationDiv.innerHTML = '';
        document.getElementById('currentBalanceInfo').style.display = 'none';
        document.getElementById('previewInfo').style.display = 'none';
        currentBalance = 0;
        return;
    }
    
    validationDiv.innerHTML = '<span style="color: #64748b;">확인 중...</span>';
    
    fetch(`<?= getApiPath('/api/get-seller-balance.php') ?>?seller_id=${encodeURIComponent(sellerId.trim())}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 판매자 정보 표시
                const sellerInfo = data.seller || {};
                const sellerName = sellerInfo.name || sellerInfo.company_name || sellerId;
                validationDiv.innerHTML = `<span style="color: #10b981;">✓ 판매자 확인됨 (${sellerName})</span>`;
                
                // 현재 잔액 표시
                currentBalance = parseFloat(data.balance) || 0;
                document.getElementById('currentBalanceAmount').textContent = new Intl.NumberFormat('ko-KR').format(Math.round(currentBalance)) + '원';
                document.getElementById('currentBalanceInfo').style.display = 'block';
                updatePreview();
            } else {
                validationDiv.innerHTML = `<span style="color: #ef4444;">✗ ${data.error || '판매자를 찾을 수 없습니다.'}</span>`;
                document.getElementById('currentBalanceInfo').style.display = 'none';
                document.getElementById('previewInfo').style.display = 'none';
                currentBalance = 0;
            }
        })
        .catch(error => {
            console.error('Seller validation error:', error);
            validationDiv.innerHTML = '<span style="color: #ef4444;">✗ 확인 중 오류가 발생했습니다.</span>';
            document.getElementById('currentBalanceInfo').style.display = 'none';
            document.getElementById('previewInfo').style.display = 'none';
            currentBalance = 0;
        });
}

// 판매자 선택
function selectSeller(sellerId, sellerName) {
    document.getElementById('seller_id').value = sellerId;
    document.getElementById('sellerSearchResults').style.display = 'none';
    
    // 판매자 아이디 유효성 검사 실행
    validateSellerId(sellerId);
}

// 조정 후 예상 잔액 업데이트
function updatePreview() {
    const amount = parseFloat(document.querySelector('input[name="amount"]').value) || 0;
    const adjustmentType = document.querySelector('input[name="adjustment_type"]:checked')?.value;
    
    if (!adjustmentType || amount <= 0) {
        document.getElementById('previewInfo').style.display = 'none';
        return;
    }
    
    const adjustmentAmount = (adjustmentType === 'service') ? amount : -amount;
    const previewBalance = currentBalance + adjustmentAmount;
    
    document.getElementById('previewBalanceAmount').textContent = new Intl.NumberFormat('ko-KR').format(Math.round(previewBalance)) + '원';
    
    if (adjustmentType !== 'service' && previewBalance < 0) {
        document.getElementById('previewBalanceAmount').style.color = '#ef4444';
        document.getElementById('previewInfo').style.background = '#fee2e2';
    } else {
        document.getElementById('previewBalanceAmount').style.color = '#92400e';
        document.getElementById('previewInfo').style.background = '#fef3c7';
    }
    
    document.getElementById('previewInfo').style.display = 'block';
}

// 이벤트 리스너
document.querySelector('input[name="amount"]')?.addEventListener('input', updatePreview);
document.querySelectorAll('input[name="adjustment_type"]').forEach(radio => {
    radio.addEventListener('change', updatePreview);
});

// 폼 제출 전 확인
document.getElementById('adjustForm')?.addEventListener('submit', function(e) {
    const amount = parseFloat(document.querySelector('input[name="amount"]').value) || 0;
    const adjustmentType = document.querySelector('input[name="adjustment_type"]:checked')?.value;
    const sellerId = document.getElementById('seller_id').value.trim();
    const validationDiv = document.getElementById('sellerIdValidation');
    
    if (!sellerId) {
        e.preventDefault();
        alert('판매자 아이디를 입력해주세요.');
        document.getElementById('seller_id').focus();
        return false;
    }
    
    // 판매자 아이디 유효성 검사 확인
    const validationText = validationDiv.textContent || '';
    if (!validationText.includes('✓ 판매자 확인됨')) {
        e.preventDefault();
        alert('판매자 아이디를 확인해주세요. 올바른 판매자 아이디를 입력했는지 확인하세요.');
        document.getElementById('seller_id').focus();
        return false;
    }
    
    if (!adjustmentType) {
        e.preventDefault();
        alert('조정 유형을 선택해주세요.');
        return false;
    }
    
    if (amount <= 0) {
        e.preventDefault();
        alert('금액을 입력해주세요.');
        return false;
    }
    
    const typeLabels = {
        'refund': '환불',
        'deduct': '차감',
        'service': '서비스'
    };
    const sign = (adjustmentType === 'service') ? '+' : '-';
    const previewBalance = currentBalance + ((adjustmentType === 'service') ? amount : -amount);
    
    if (adjustmentType !== 'service' && previewBalance < 0) {
        e.preventDefault();
        alert('예치금 잔액이 부족합니다. (현재 잔액: ' + new Intl.NumberFormat('ko-KR').format(Math.round(currentBalance)) + '원)');
        return false;
    }
    
    if (!confirm(`정말 예치금을 조정하시겠습니까?\n\n판매자: ${sellerId}\n유형: ${typeLabels[adjustmentType]}\n금액: ${sign}${new Intl.NumberFormat('ko-KR').format(Math.round(amount))}원\n조정 후 잔액: ${new Intl.NumberFormat('ko-KR').format(Math.round(previewBalance))}원`)) {
        e.preventDefault();
        return false;
    }
});

// 초기화
function resetForm() {
    document.getElementById('adjustForm').reset();
    document.getElementById('currentBalanceInfo').style.display = 'none';
    document.getElementById('previewInfo').style.display = 'none';
    currentBalance = 0;
}

// 외부 클릭 시 검색 결과 닫기
document.addEventListener('click', function(e) {
    if (!e.target.closest('#seller_id') && !e.target.closest('#sellerSearchResults')) {
        document.getElementById('sellerSearchResults').style.display = 'none';
    }
});
</script>

<?php if (isset($_GET['seller_search'])): ?>
    <div id="sellerSearchResults" style="display: none;">
        <?php if (empty($sellerList)): ?>
            <div style="padding: 12px; color: #64748b; text-align: center;">검색 결과가 없습니다.</div>
        <?php else: ?>
            <?php foreach ($sellerList as $seller): ?>
                <div onclick="selectSeller('<?= htmlspecialchars($seller['user_id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($seller['name'], ENT_QUOTES) ?>')" 
                     style="padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #e5e7eb; transition: background 0.2s;"
                     onmouseover="this.style.background='#f8fafc';"
                     onmouseout="this.style.background='white';">
                    <div style="font-weight: 600; color: #374151;"><?= htmlspecialchars($seller['user_id']) ?></div>
                    <div style="font-size: 13px; color: #64748b;"><?= htmlspecialchars($seller['name']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
