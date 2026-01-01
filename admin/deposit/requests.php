<?php
/**
 * 입금 신청 목록 페이지 (관리자)
 * 경로: /admin/deposit/requests.php
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

// 입금 확인/미입금 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = intval($_POST['request_id'] ?? 0);
    
    if ($action === 'confirm') {
        // 입금 확인 처리
        try {
            $pdo->beginTransaction();
            
            // 입금 신청 정보 가져오기
            $stmt = $pdo->prepare("SELECT * FROM deposit_requests WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                throw new Exception('입금 신청을 찾을 수 없습니다.');
            }
            
            if ($request['status'] !== 'pending') {
                throw new Exception('이미 처리된 입금 신청입니다.');
            }
            
            // 입금 날짜 받기
            $depositDate = $_POST['deposit_date'] ?? '';
            if (empty($depositDate)) {
                throw new Exception('입금 날짜를 입력해주세요.');
            }
            
            // 날짜 유효성 검사
            $dateParts = explode('-', $depositDate);
            if (count($dateParts) !== 3 || !checkdate($dateParts[1], $dateParts[2], $dateParts[0])) {
                throw new Exception('올바른 날짜 형식이 아닙니다.');
            }
            
            // 입금 확인 처리 (입금 날짜 + 현재 시간으로 저장)
            $confirmedDateTime = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("
                UPDATE deposit_requests 
                SET status = 'confirmed',
                    admin_id = :admin_id,
                    confirmed_at = :confirmed_at
                WHERE id = :id
            ");
            $stmt->execute([
                ':admin_id' => $adminId,
                ':confirmed_at' => $confirmedDateTime,
                ':id' => $requestId
            ]);
            
            // 판매자 예치금 계좌 확인 및 생성
            $pdo->prepare("
                INSERT IGNORE INTO seller_deposit_accounts (seller_id, balance, created_at)
                VALUES (:seller_id, 0, NOW())
            ")->execute([':seller_id' => $request['seller_id']]);
            
            // 잔액 조회 및 업데이트
            $stmt = $pdo->prepare("SELECT balance FROM seller_deposit_accounts WHERE seller_id = :seller_id FOR UPDATE");
            $stmt->execute([':seller_id' => $request['seller_id']]);
            $currentBalance = floatval($stmt->fetchColumn() ?? 0);
            $newBalance = $currentBalance + floatval($request['amount']); // 부가세 포함 총액 충전
            
            $pdo->prepare("
                UPDATE seller_deposit_accounts 
                SET balance = :balance, updated_at = NOW()
                WHERE seller_id = :seller_id
            ")->execute([
                ':balance' => $newBalance,
                ':seller_id' => $request['seller_id']
            ]);
            
            // 예치금 내역 기록
            $pdo->prepare("
                INSERT INTO seller_deposit_ledger (
                    seller_id, transaction_type, amount, balance_before, balance_after,
                    deposit_request_id, description, created_at
                ) VALUES (
                    :seller_id, 'deposit', :amount, :balance_before, :balance_after,
                    :deposit_request_id, :description, NOW()
                )
            ")->execute([
                ':seller_id' => $request['seller_id'],
                ':amount' => $request['amount'],
                ':balance_before' => $currentBalance,
                ':balance_after' => $newBalance,
                ':deposit_request_id' => $requestId,
                ':description' => '예치금 충전 (무통장 입금)'
            ]);
            
            $pdo->commit();
            $success = '입금이 확인되었고 예치금이 충전되었습니다.';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Deposit confirm error: ' . $e->getMessage());
            $error = $e->getMessage();
        }
    } elseif ($action === 'unpaid') {
        // 미입금 처리
        try {
            $stmt = $pdo->prepare("
                UPDATE deposit_requests 
                SET status = 'unpaid',
                    admin_id = :admin_id
                WHERE id = :id AND status = 'pending'
            ");
            $stmt->execute([
                ':admin_id' => $adminId,
                ':id' => $requestId
            ]);
            
            if ($stmt->rowCount() > 0) {
                $success = '미입금으로 처리되었습니다.';
            } else {
                $error = '이미 처리된 입금 신청입니다.';
            }
        } catch (PDOException $e) {
            error_log('Deposit unpaid error: ' . $e->getMessage());
            $error = '미입금 처리 중 오류가 발생했습니다.';
        }
    }
}

// 필터 처리
$statusFilter = $_GET['status'] ?? '';
$sellerIdFilter = $_GET['seller_id'] ?? ''; // 판매자 아이디 필터
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 입금 신청 목록 조회
$whereConditions = [];
$params = [];

if ($statusFilter && in_array($statusFilter, ['pending', 'confirmed', 'unpaid'])) {
    $whereConditions[] = "dr.status = :status";
    $params[':status'] = $statusFilter;
}

if ($sellerIdFilter && trim($sellerIdFilter) !== '') {
    $whereConditions[] = "dr.seller_id LIKE :seller_id";
    $params[':seller_id'] = '%' . trim($sellerIdFilter) . '%';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// 전체 개수 조회
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM deposit_requests dr
    $whereClause
");
$countStmt->execute($params);
$totalCount = $countStmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// 페이지별 데이터 조회
$stmt = $pdo->prepare("
    SELECT 
        dr.*,
        ba.bank_name,
        ba.account_number,
        ba.account_holder
    FROM deposit_requests dr
    LEFT JOIN bank_accounts ba ON dr.bank_account_id = ba.id
    $whereClause
    ORDER BY dr.created_at DESC
    LIMIT :limit OFFSET :offset
");

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-content-wrapper">
    <div class="admin-content">
        <div class="page-header">
            <h1>입금 신청 목록</h1>
            <p>판매자의 예치금 충전 신청을 확인하고 처리합니다.</p>
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
                
                <!-- 필터 -->
                <div style="margin-bottom: 24px;">
                    <form method="GET" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                        <input type="hidden" name="page" value="1">
                        <select name="status" style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #fff; width: 200px;">
                            <option value="">전체 상태</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>대기중</option>
                            <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>입금</option>
                            <option value="unpaid" <?= $statusFilter === 'unpaid' ? 'selected' : '' ?>>미입금</option>
                        </select>
                        
                        <input type="text" name="seller_id" value="<?= htmlspecialchars($sellerIdFilter) ?>" 
                               placeholder="판매자 아이디 검색"
                               style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #fff; width: 200px;">
                        
                        <button type="submit" style="padding: 10px 24px; background: #6366f1; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px;">
                            조회
                        </button>
                    </form>
                </div>
                
                <!-- 입금 신청 목록 -->
                <?php if (empty($deposits)): ?>
                    <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">💰</div>
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #374151;">입금 신청 내역이 없습니다</div>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden;">
                            <thead>
                                <tr style="background: #f1f5f9;">
                                    <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">순서</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">신청일시</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">판매자</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">입금자명</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">입금계좌</th>
                                    <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">공급가액</th>
                                    <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">부가세</th>
                                    <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">입금금액</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">상태</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // 역순 번호 계산 (최신 항목이 큰 번호)
                                $orderNumber = $totalCount - ($page - 1) * $perPage;
                                foreach ($deposits as $deposit): ?>
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td style="padding: 12px; text-align: center;">
                                            <?= $orderNumber-- ?>
                                        </td>
                                        <td style="padding: 12px;">
                                            <?= date('Y-m-d H:i', strtotime($deposit['created_at'])) ?>
                                        </td>
                                        <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($deposit['seller_id']) ?></td>
                                        <td style="padding: 12px;"><?= htmlspecialchars($deposit['depositor_name']) ?></td>
                                        <td style="padding: 12px; font-size: 13px; color: #64748b;">
                                            <?= htmlspecialchars($deposit['bank_name'] ?? '-') ?><br>
                                            <?= htmlspecialchars($deposit['account_number'] ?? '-') ?>
                                        </td>
                                        <td style="padding: 12px; text-align: right;"><?= number_format(floatval($deposit['supply_amount'] ?? 0), 0) ?>원</td>
                                        <td style="padding: 12px; text-align: right;"><?= number_format(floatval($deposit['tax_amount'] ?? 0), 0) ?>원</td>
                                        <td style="padding: 12px; text-align: right; font-weight: 600;"><?= number_format(floatval($deposit['amount'] ?? 0), 0) ?>원</td>
                                        <td style="padding: 12px; text-align: center;">
                                            <?php
                                            $statusLabels = [
                                                'pending' => ['label' => '대기중', 'color' => '#f59e0b'],
                                                'confirmed' => ['label' => '입금', 'color' => '#10b981'],
                                                'unpaid' => ['label' => '미입금', 'color' => '#6b7280']
                                            ];
                                            $statusInfo = $statusLabels[$deposit['status']] ?? ['label' => $deposit['status'], 'color' => '#64748b'];
                                            ?>
                                            <span style="padding: 4px 12px; background: <?= $statusInfo['color'] ?>20; color: <?= $statusInfo['color'] ?>; border-radius: 4px; font-size: 14px; font-weight: 500;">
                                                <?= $statusInfo['label'] ?>
                                            </span>
                                            <?php if ($deposit['confirmed_at']): ?>
                                                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                                                    <?= date('Y-m-d', strtotime($deposit['confirmed_at'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <?php if ($deposit['status'] === 'pending'): ?>
                                                <div style="display: flex; gap: 8px; justify-content: center;">
                                                    <button type="button" onclick="openConfirmModal(<?= $deposit['id'] ?>, '<?= htmlspecialchars($deposit['seller_id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($deposit['depositor_name'], ENT_QUOTES) ?>', <?= floatval($deposit['amount'] ?? 0) ?>, <?= floatval($deposit['supply_amount'] ?? 0) ?>, <?= floatval($deposit['tax_amount'] ?? 0) ?>)" 
                                                            style="padding: 6px 12px; background: #10b981; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">
                                                        입금확인
                                                    </button>
                                                    <button type="button" onclick="openUnpaidModal(<?= $deposit['id'] ?>, '<?= htmlspecialchars($deposit['seller_id'], ENT_QUOTES) ?>')" 
                                                            style="padding: 6px 12px; background: #ef4444; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">
                                                        미입금
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #64748b; font-size: 13px;">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- 페이지네이션 -->
                    <?php if ($totalPages > 1): ?>
                        <div style="margin-top: 24px; display: flex; justify-content: center; align-items: center; gap: 8px;">
                            <?php
                            // 페이지네이션 URL 파라미터 구성
                            $paginationParams = [];
                            if ($statusFilter) $paginationParams['status'] = $statusFilter;
                            if ($sellerIdFilter) $paginationParams['seller_id'] = $sellerIdFilter;
                            $paginationBaseUrl = !empty($paginationParams) ? '?' . http_build_query($paginationParams) : '?';
                            ?>
                            <?php if ($page > 1): ?>
                                <a href="<?= $paginationBaseUrl ?>&page=<?= $page - 1 ?>" 
                                   style="padding: 8px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; color: #374151; text-decoration: none; font-weight: 500;">
                                    이전
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <a href="<?= $paginationBaseUrl ?>&page=<?= $i ?>" 
                                   style="padding: 8px 16px; background: <?= $i === $page ? '#6366f1' : '#fff' ?>; border: 1px solid #e2e8f0; border-radius: 6px; color: <?= $i === $page ? '#fff' : '#374151' ?>; text-decoration: none; font-weight: <?= $i === $page ? '600' : '500' ?>;">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="<?= $paginationBaseUrl ?>&page=<?= $page + 1 ?>" 
                                   style="padding: 8px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; color: #374151; text-decoration: none; font-weight: 500;">
                                    다음
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 입금 확인 모달 -->
<div id="confirmModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 32px; width: 90%; max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 600;">입금 확인</h2>
            <button type="button" onclick="closeConfirmModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        
        <div style="margin-bottom: 24px;">
            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 16px;">
                <div style="font-size: 14px; color: #64748b; margin-bottom: 4px;">판매자</div>
                <div style="font-size: 16px; font-weight: 600;" id="confirmSellerId"></div>
            </div>
            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 16px;">
                <div style="font-size: 14px; color: #64748b; margin-bottom: 4px;">입금자명</div>
                <div style="font-size: 16px; font-weight: 600;" id="confirmDepositorName"></div>
            </div>
            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 16px;">
                <div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">입금 정보</div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>공급가액:</span>
                    <span style="font-weight: 600;" id="confirmSupplyAmount"></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>부가세 (10%):</span>
                    <span style="font-weight: 600;" id="confirmTaxAmount"></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px solid #e2e8f0;">
                    <span style="font-weight: 600;">입금금액:</span>
                    <span style="font-weight: 700; font-size: 18px; color: #6366f1;" id="confirmTotalAmount"></span>
                </div>
            </div>
            <div style="padding: 12px; background: #d1fae5; border-radius: 6px; color: #065f46; font-size: 14px;">
                ✓ 입금 확인 시 예치금이 충전됩니다.
            </div>
        </div>
        
        <form method="POST" id="confirmForm">
            <input type="hidden" name="action" value="confirm">
            <input type="hidden" name="request_id" id="confirmRequestId">
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 14px; color: #374151; margin-bottom: 8px; font-weight: 500;">
                    입금 날짜 <span style="color: #ef4444;">*</span>
                </label>
                <input type="date" name="deposit_date" id="depositDate" required
                       style="width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; background: #fff;"
                       max="<?= date('Y-m-d') ?>">
                <div style="font-size: 12px; color: #64748b; margin-top: 6px;">
                    실제 입금된 날짜를 입력해주세요.
                </div>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="submit" style="flex: 1; padding: 12px 24px; background: #10b981; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                    확인
                </button>
                <button type="button" onclick="closeConfirmModal()" style="flex: 1; padding: 12px 24px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                    취소
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 미입금 모달 -->
<div id="unpaidModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 32px; width: 90%; max-width: 400px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 600;">미입금 처리</h2>
            <button type="button" onclick="closeUnpaidModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        
        <div style="margin-bottom: 24px;">
            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 16px;">
                <div style="font-size: 14px; color: #64748b; margin-bottom: 4px;">판매자</div>
                <div style="font-size: 16px; font-weight: 600;" id="unpaidSellerId"></div>
            </div>
            <div style="padding: 12px; background: #fee2e2; border-radius: 6px; color: #991b1b; font-size: 14px;">
                ⚠ 미입금으로 처리하시겠습니까?
            </div>
        </div>
        
        <form method="POST" id="unpaidForm">
            <input type="hidden" name="action" value="unpaid">
            <input type="hidden" name="request_id" id="unpaidRequestId">
            
            <div style="display: flex; gap: 12px;">
                <button type="submit" style="flex: 1; padding: 12px 24px; background: #ef4444; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                    확인
                </button>
                <button type="button" onclick="closeUnpaidModal()" style="flex: 1; padding: 12px 24px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                    취소
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openConfirmModal(requestId, sellerId, depositorName, totalAmount, supplyAmount, taxAmount) {
    document.getElementById('confirmRequestId').value = requestId;
    document.getElementById('confirmSellerId').textContent = sellerId;
    document.getElementById('confirmDepositorName').textContent = depositorName;
    document.getElementById('confirmSupplyAmount').textContent = new Intl.NumberFormat('ko-KR').format(Math.round(supplyAmount)) + '원';
    document.getElementById('confirmTaxAmount').textContent = new Intl.NumberFormat('ko-KR').format(Math.round(taxAmount)) + '원';
    document.getElementById('confirmTotalAmount').textContent = new Intl.NumberFormat('ko-KR').format(Math.round(totalAmount)) + '원';
    
    // 입금 날짜 필드를 오늘 날짜로 기본 설정
    var today = new Date().toISOString().split('T')[0];
    document.getElementById('depositDate').value = today;
    
    document.getElementById('confirmModal').style.display = 'flex';
}


function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

function openUnpaidModal(requestId, sellerId) {
    document.getElementById('unpaidRequestId').value = requestId;
    document.getElementById('unpaidSellerId').textContent = sellerId;
    document.getElementById('unpaidModal').style.display = 'flex';
}

function closeUnpaidModal() {
    document.getElementById('unpaidModal').style.display = 'none';
}

// 모달 배경 클릭 시 닫기
document.getElementById('confirmModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeConfirmModal();
    }
});

document.getElementById('unpaidModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeUnpaidModal();
    }
});
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
