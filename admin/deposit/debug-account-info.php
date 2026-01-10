<?php
/**
 * 계좌 정보 디버깅 페이지
 * 계좌 수정 시 입금 신청 기록이 모두 변경되는 문제 디버깅
 */

require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/path-config.php';

// 관리자 권한 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    header('Location: ' . getAssetPath('/admin/login.php'));
    exit;
}

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

// 특정 계좌 ID 필터 (쿼리 파라미터로 받기)
$filterAccountId = isset($_GET['account_id']) ? intval($_GET['account_id']) : null;

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1400, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>계좌 정보 디버깅</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f7fa;
            min-width: 1400px;
        }
        .container {
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1f2937;
            margin-bottom: 30px;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 15px;
        }
        h2 {
            color: #374151;
            margin-top: 40px;
            margin-bottom: 20px;
            font-size: 20px;
        }
        .filter-box {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .filter-box label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        .filter-box select, .filter-box button {
            padding: 10px 15px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        .filter-box button {
            background: #6366f1;
            color: white;
            border: none;
            cursor: pointer;
            margin-left: 10px;
        }
        .filter-box button:hover {
            background: #4f46e5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        thead {
            background: #f1f5f9;
        }
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e2e8f0;
            font-size: 13px;
            text-transform: uppercase;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        tbody tr:hover {
            background: #f9fafb;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-stored {
            background: #d1fae5;
            color: #065f46;
        }
        .status-joined {
            background: #dbeafe;
            color: #1e40af;
        }
        .status-null {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-empty {
            background: #fef3c7;
            color: #92400e;
        }
        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-box h3 {
            margin-top: 0;
            color: #1e40af;
        }
        .info-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .info-box li {
            margin: 5px 0;
            color: #1e3a8a;
        }
        .warning-box {
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .warning-box h3 {
            margin-top: 0;
            color: #92400e;
        }
        .diff {
            background: #fee2e2;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }
        .same {
            color: #059669;
            font-weight: 600;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 계좌 정보 디버깅 도구</h1>
        
        <div class="info-box">
            <h3>📋 디버깅 목적</h3>
            <ul>
                <li>계좌 정보 수정 시 입금 신청 기록이 모두 변경되는 원인 파악</li>
                <li><code>deposit_requests</code> 테이블에 저장된 텍스트 값 확인</li>
                <li><code>bank_accounts</code> 테이블과 JOIN 결과 비교</li>
                <li>표시되는 값의 출처 확인 (텍스트 저장값 vs JOIN값)</li>
            </ul>
        </div>

        <?php
        // 1. 계좌 목록 조회 (필터용)
        $accountsStmt = $pdo->query("SELECT id, bank_name, account_number, account_holder FROM bank_accounts ORDER BY id");
        $allAccounts = $accountsStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="filter-box">
            <label>계좌 선택 (필터링):</label>
            <form method="GET" style="display: inline-block;">
                <select name="account_id" onchange="this.form.submit()">
                    <option value="">전체 계좌</option>
                    <?php foreach ($allAccounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>" <?= $filterAccountId == $acc['id'] ? 'selected' : '' ?>>
                            [ID: <?= $acc['id'] ?>] <?= htmlspecialchars($acc['bank_name']) ?> - <?= htmlspecialchars($acc['account_number']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">필터 적용</button>
                <?php if ($filterAccountId): ?>
                    <a href="?account_id=" style="margin-left: 10px; color: #6366f1; text-decoration: none;">필터 해제</a>
                <?php endif; ?>
            </form>
        </div>

        <?php
        // 2. 입금 신청 기록 조회 (실제 저장된 값 확인)
        $whereClause = $filterAccountId ? "WHERE dr.bank_account_id = :account_id" : "";
        $params = $filterAccountId ? [':account_id' => $filterAccountId] : [];
        
        $stmt = $pdo->prepare("
            SELECT 
                dr.id as request_id,
                dr.seller_id,
                dr.bank_account_id,
                dr.created_at,
                dr.status,
                -- deposit_requests에 저장된 텍스트 값 (원본)
                dr.bank_name as dr_bank_name,
                dr.account_number as dr_account_number,
                dr.account_holder as dr_account_holder,
                -- bank_accounts의 현재 값 (JOIN)
                ba.bank_name as ba_bank_name,
                ba.account_number as ba_account_number,
                ba.account_holder as ba_account_holder,
                -- COALESCE로 표시되는 값 (현재 requests.php에서 사용하는 로직)
                COALESCE(dr.bank_name, ba.bank_name) as displayed_bank_name,
                COALESCE(dr.account_number, ba.account_number) as displayed_account_number,
                COALESCE(dr.account_holder, ba.account_holder) as displayed_account_holder
            FROM deposit_requests dr
            LEFT JOIN bank_accounts ba ON dr.bank_account_id = ba.id
            $whereClause
            ORDER BY dr.created_at DESC
            LIMIT 50
        ");
        
        if ($filterAccountId) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        $deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <h2>📊 입금 신청 기록 분석 (최근 50건)</h2>
        
        <?php if (empty($deposits)): ?>
            <div class="warning-box">
                <h3>⚠️ 데이터 없음</h3>
                <p>조건에 맞는 입금 신청 기록이 없습니다.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>판매자</th>
                        <th>계좌ID</th>
                        <th>신청일</th>
                        <th>상태</th>
                        <th colspan="3" style="text-align: center; background: #dbeafe;">deposit_requests 테이블 저장값 (원본)</th>
                        <th colspan="3" style="text-align: center; background: #fef3c7;">bank_accounts 현재값 (JOIN)</th>
                        <th colspan="3" style="text-align: center; background: #d1fae5;">실제 표시되는 값 (COALESCE)</th>
                        <th>비고</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th style="background: #dbeafe;">은행명</th>
                        <th style="background: #dbeafe;">계좌번호</th>
                        <th style="background: #dbeafe;">예금주</th>
                        <th style="background: #fef3c7;">은행명</th>
                        <th style="background: #fef3c7;">계좌번호</th>
                        <th style="background: #fef3c7;">예금주</th>
                        <th style="background: #d1fae5;">은행명</th>
                        <th style="background: #d1fae5;">계좌번호</th>
                        <th style="background: #d1fae5;">예금주</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deposits as $deposit): 
                        $drBankName = $deposit['dr_bank_name'] ?? '';
                        $drAccountNumber = $deposit['dr_account_number'] ?? '';
                        $drAccountHolder = $deposit['dr_account_holder'] ?? '';
                        
                        $baBankName = $deposit['ba_bank_name'] ?? '';
                        $baAccountNumber = $deposit['ba_account_number'] ?? '';
                        $baAccountHolder = $deposit['ba_account_holder'] ?? '';
                        
                        $displayedBankName = $deposit['displayed_bank_name'] ?? '';
                        $displayedAccountNumber = $deposit['displayed_account_number'] ?? '';
                        $displayedAccountHolder = $deposit['displayed_account_holder'] ?? '';
                        
                        // 값 비교
                        $bankNameMatch = ($drBankName === $baBankName);
                        $accountNumberMatch = ($drAccountNumber === $baAccountNumber);
                        $accountHolderMatch = ($drAccountHolder === $baAccountHolder);
                        
                        // 표시값의 출처 확인
                        $bankNameSource = !empty($drBankName) ? 'stored' : (!empty($baBankName) ? 'joined' : 'null');
                        $accountNumberSource = !empty($drAccountNumber) ? 'stored' : (!empty($baAccountNumber) ? 'joined' : 'null');
                        $accountHolderSource = !empty($drAccountHolder) ? 'stored' : (!empty($baAccountHolder) ? 'joined' : 'null');
                    ?>
                        <tr>
                            <td><strong><?= $deposit['request_id'] ?></strong></td>
                            <td><?= htmlspecialchars($deposit['seller_id']) ?></td>
                            <td><?= $deposit['bank_account_id'] ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($deposit['created_at'])) ?></td>
                            <td>
                                <span class="status-badge" style="background: <?= $deposit['status'] === 'confirmed' ? '#d1fae5' : '#fee2e2' ?>; color: <?= $deposit['status'] === 'confirmed' ? '#065f46' : '#991b1b' ?>;">
                                    <?= $deposit['status'] ?>
                                </span>
                            </td>
                            <!-- deposit_requests 저장값 -->
                            <td style="background: #dbeafe;">
                                <?php if (empty($drBankName)): ?>
                                    <span class="status-null">NULL/빈값</span>
                                <?php else: ?>
                                    <code><?= htmlspecialchars($drBankName) ?></code>
                                <?php endif; ?>
                            </td>
                            <td style="background: #dbeafe;">
                                <?php if (empty($drAccountNumber)): ?>
                                    <span class="status-null">NULL/빈값</span>
                                <?php else: ?>
                                    <code><?= htmlspecialchars($drAccountNumber) ?></code>
                                <?php endif; ?>
                            </td>
                            <td style="background: #dbeafe;">
                                <?php if (empty($drAccountHolder)): ?>
                                    <span class="status-null">NULL/빈값</span>
                                <?php else: ?>
                                    <code><?= htmlspecialchars($drAccountHolder) ?></code>
                                <?php endif; ?>
                            </td>
                            <!-- bank_accounts 현재값 -->
                            <td style="background: #fef3c7;">
                                <?php if (empty($baBankName)): ?>
                                    <span class="status-null">계좌삭제됨</span>
                                <?php else: ?>
                                    <code><?= htmlspecialchars($baBankName) ?></code>
                                <?php endif; ?>
                            </td>
                            <td style="background: #fef3c7;">
                                <?php if (empty($baAccountNumber)): ?>
                                    <span class="status-null">계좌삭제됨</span>
                                <?php else: ?>
                                    <code><?= htmlspecialchars($baAccountNumber) ?></code>
                                <?php endif; ?>
                            </td>
                            <td style="background: #fef3c7;">
                                <?php if (empty($baAccountHolder)): ?>
                                    <span class="status-null">계좌삭제됨</span>
                                <?php else: ?>
                                    <code><?= htmlspecialchars($baAccountHolder) ?></code>
                                <?php endif; ?>
                            </td>
                            <!-- 실제 표시되는 값 -->
                            <td style="background: #d1fae5;">
                                <code><?= htmlspecialchars($displayedBankName) ?></code>
                                <br>
                                <span class="status-badge status-<?= $bankNameSource ?>">
                                    <?= $bankNameSource === 'stored' ? '저장값' : ($bankNameSource === 'joined' ? 'JOIN값' : '없음') ?>
                                </span>
                            </td>
                            <td style="background: #d1fae5;">
                                <code><?= htmlspecialchars($displayedAccountNumber) ?></code>
                                <br>
                                <span class="status-badge status-<?= $accountNumberSource ?>">
                                    <?= $accountNumberSource === 'stored' ? '저장값' : ($accountNumberSource === 'joined' ? 'JOIN값' : '없음') ?>
                                </span>
                            </td>
                            <td style="background: #d1fae5;">
                                <code><?= htmlspecialchars($displayedAccountHolder) ?></code>
                                <br>
                                <span class="status-badge status-<?= $accountHolderSource ?>">
                                    <?= $accountHolderSource === 'stored' ? '저장값' : ($accountHolderSource === 'joined' ? 'JOIN값' : '없음') ?>
                                </span>
                            </td>
                            <!-- 비고 -->
                            <td>
                                <?php if (!$bankNameMatch || !$accountNumberMatch || !$accountHolderMatch): ?>
                                    <span class="diff">⚠️ 불일치</span>
                                <?php else: ?>
                                    <span class="same">✓ 일치</span>
                                <?php endif; ?>
                                <?php if (empty($drBankName) || empty($drAccountNumber) || empty($drAccountHolder)): ?>
                                    <br><span class="status-empty">텍스트 미저장</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            // 통계 정보
            $statsStmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN dr.bank_name IS NOT NULL AND dr.bank_name != '' THEN 1 ELSE 0 END) as has_bank_name,
                    SUM(CASE WHEN dr.account_number IS NOT NULL AND dr.account_number != '' THEN 1 ELSE 0 END) as has_account_number,
                    SUM(CASE WHEN dr.account_holder IS NOT NULL AND dr.account_holder != '' THEN 1 ELSE 0 END) as has_account_holder,
                    SUM(CASE WHEN (dr.bank_name IS NULL OR dr.bank_name = '') AND ba.bank_name IS NOT NULL THEN 1 ELSE 0 END) as using_join_bank_name,
                    SUM(CASE WHEN (dr.account_number IS NULL OR dr.account_number = '') AND ba.account_number IS NOT NULL THEN 1 ELSE 0 END) as using_join_account_number,
                    SUM(CASE WHEN (dr.account_holder IS NULL OR dr.account_holder = '') AND ba.account_holder IS NOT NULL THEN 1 ELSE 0 END) as using_join_account_holder,
                    SUM(CASE WHEN dr.bank_name != ba.bank_name THEN 1 ELSE 0 END) as bank_name_mismatch,
                    SUM(CASE WHEN dr.account_number != ba.account_number THEN 1 ELSE 0 END) as account_number_mismatch,
                    SUM(CASE WHEN dr.account_holder != ba.account_holder THEN 1 ELSE 0 END) as account_holder_mismatch
                FROM deposit_requests dr
                LEFT JOIN bank_accounts ba ON dr.bank_account_id = ba.id
                $whereClause
            ");
            
            if ($filterAccountId) {
                $statsStmt->execute($params);
            } else {
                $statsStmt->execute();
            }
            
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            ?>

            <div class="info-box" style="margin-top: 30px;">
                <h3>📈 통계 정보</h3>
                <table style="margin-top: 10px;">
                    <tr>
                        <th style="width: 250px;">항목</th>
                        <th>값</th>
                        <th>비율</th>
                    </tr>
                    <tr>
                        <td>전체 입금 신청</td>
                        <td><strong><?= $stats['total'] ?></strong>건</td>
                        <td>100%</td>
                    </tr>
                    <tr>
                        <td>은행명 텍스트 저장됨</td>
                        <td><strong><?= $stats['has_bank_name'] ?></strong>건</td>
                        <td><?= $stats['total'] > 0 ? round($stats['has_bank_name'] / $stats['total'] * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr>
                        <td>계좌번호 텍스트 저장됨</td>
                        <td><strong><?= $stats['has_account_number'] ?></strong>건</td>
                        <td><?= $stats['total'] > 0 ? round($stats['has_account_number'] / $stats['total'] * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr>
                        <td>예금주 텍스트 저장됨</td>
                        <td><strong><?= $stats['has_account_holder'] ?></strong>건</td>
                        <td><?= $stats['total'] > 0 ? round($stats['has_account_holder'] / $stats['total'] * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr style="background: #fef3c7;">
                        <td>⚠️ 은행명 JOIN 사용 중</td>
                        <td><strong><?= $stats['using_join_bank_name'] ?></strong>건</td>
                        <td><?= $stats['total'] > 0 ? round($stats['using_join_bank_name'] / $stats['total'] * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr style="background: #fef3c7;">
                        <td>⚠️ 계좌번호 JOIN 사용 중</td>
                        <td><strong><?= $stats['using_join_account_number'] ?></strong>건</td>
                        <td><?= $stats['total'] > 0 ? round($stats['using_join_account_number'] / $stats['total'] * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr style="background: #fef3c7;">
                        <td>⚠️ 예금주 JOIN 사용 중</td>
                        <td><strong><?= $stats['using_join_account_holder'] ?></strong>건</td>
                        <td><?= $stats['total'] > 0 ? round($stats['using_join_account_holder'] / $stats['total'] * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr style="background: #fee2e2;">
                        <td>🚨 은행명 불일치</td>
                        <td><strong><?= $stats['bank_name_mismatch'] ?></strong>건</td>
                        <td><?= $stats['total'] > 0 ? round($stats['bank_name_mismatch'] / $stats['total'] * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr style="background: #fee2e2;">
                        <td>🚨 계좌번호 불일치</td>
                        <td><strong><?= $stats['account_number_mismatch'] ?></strong>건</td>
                        <td><?= $stats['total'] > 0 ? round($stats['account_number_mismatch'] / $stats['total'] * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr style="background: #fee2e2;">
                        <td>🚨 예금주 불일치</td>
                        <td><strong><?= $stats['account_holder_mismatch'] ?></strong>건</td>
                        <td><?= $stats['total'] > 0 ? round($stats['account_holder_mismatch'] / $stats['total'] * 100, 1) : 0 ?>%</td>
                    </tr>
                </table>
            </div>

            <?php if ($stats['using_join_bank_name'] > 0 || $stats['using_join_account_number'] > 0 || $stats['using_join_account_holder'] > 0): ?>
                <div class="warning-box">
                    <h3>⚠️ 문제 발견!</h3>
                    <ul>
                        <li><strong>JOIN 사용 중인 기록이 있습니다.</strong> 이는 <code>deposit_requests</code> 테이블에 계좌 정보가 텍스트로 저장되지 않았다는 의미입니다.</li>
                        <li>계좌 정보를 수정하면 이 기록들의 표시값도 함께 변경됩니다.</li>
                        <li><strong>해결 방법:</strong> 입금 신청 시 계좌 정보를 텍스트로 저장하도록 코드를 확인하세요.</li>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($stats['bank_name_mismatch'] > 0 || $stats['account_number_mismatch'] > 0 || $stats['account_holder_mismatch'] > 0): ?>
                <div class="warning-box">
                    <h3>⚠️ 값 불일치 발견!</h3>
                    <ul>
                        <li><strong>저장된 텍스트 값과 현재 계좌 정보가 다른 기록이 있습니다.</strong></li>
                        <li>이는 입금 신청 후 계좌 정보가 수정되었을 가능성이 있습니다.</li>
                        <li>이 경우 텍스트 저장값이 있으므로 계좌 수정 시 표시값이 변경되지 않습니다.</li>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div style="margin-top: 40px; padding: 20px; background: #f3f4f6; border-radius: 8px;">
            <h3>🔗 관련 페이지</h3>
            <ul>
                <li><a href="<?= getAssetPath('/admin/deposit/requests.php') ?>" target="_blank">입금 신청 관리</a></li>
                <li><a href="<?= getAssetPath('/admin/deposit/bank-accounts.php') ?>" target="_blank">계좌 관리</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
