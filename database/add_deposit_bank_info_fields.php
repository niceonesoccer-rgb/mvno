<?php
/**
 * deposit_requests 테이블에 계좌 정보 텍스트 컬럼 추가
 * 입금 신청 시점의 계좌 정보를 텍스트로 저장하여 계좌 삭제 후에도 정보 확인 가능
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
    <title>입금 신청 테이블 - 계좌 정보 컬럼 추가</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #6366f1; padding-bottom: 10px; }
        .success { background: #d1fae5; color: #065f46; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .info { background: #dbeafe; color: #1e40af; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .warning { background: #fef3c7; color: #92400e; padding: 15px; border-radius: 6px; margin: 15px 0; }
        button { padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        button:hover { background: #4f46e5; }
        pre { background: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>입금 신청 테이블 - 계좌 정보 컬럼 추가</h1>
        
        <?php
        try {
            // 1. deposit_requests 테이블 존재 확인
            $stmt = $pdo->query("SHOW TABLES LIKE 'deposit_requests'");
            if ($stmt->rowCount() == 0) {
                echo '<div class="error">deposit_requests 테이블이 존재하지 않습니다.</div>';
                exit;
            }
            echo '<div class="success">✓ deposit_requests 테이블 존재 확인</div>';
            
            // 2. 현재 컬럼 확인
            $stmt = $pdo->query("SHOW COLUMNS FROM deposit_requests");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo '<div class="info"><strong>현재 컬럼:</strong> ' . implode(', ', $columns) . '</div>';
            
            // 3. 컬럼 추가 (없는 경우만)
            $columnsToAdd = [
                'bank_name' => "VARCHAR(50) DEFAULT NULL COMMENT '은행명 (입금 신청 시점의 정보)'",
                'account_number' => "VARCHAR(50) DEFAULT NULL COMMENT '계좌번호 (입금 신청 시점의 정보)'",
                'account_holder' => "VARCHAR(100) DEFAULT NULL COMMENT '예금주 (입금 신청 시점의 정보)'"
            ];
            
            $pdo->beginTransaction();
            
            foreach ($columnsToAdd as $columnName => $columnDef) {
                if (!in_array($columnName, $columns)) {
                    $sql = "ALTER TABLE deposit_requests ADD COLUMN {$columnName} {$columnDef}";
                    $pdo->exec($sql);
                    echo '<div class="success">✓ 컬럼 추가 완료: ' . $columnName . '</div>';
                } else {
                    echo '<div class="warning">컬럼이 이미 존재합니다: ' . $columnName . '</div>';
                }
            }
            
            // 4. 기존 데이터 업데이트 (bank_account_id로 계좌 정보 채우기)
            // bank_name, account_number, account_holder가 NULL이거나 빈 값인 경우만 업데이트
            $updateSql = "
                UPDATE deposit_requests dr
                INNER JOIN bank_accounts ba ON dr.bank_account_id = ba.id
                SET dr.bank_name = ba.bank_name,
                    dr.account_number = ba.account_number,
                    dr.account_holder = ba.account_holder
                WHERE (dr.bank_name IS NULL OR dr.bank_name = '')
                   OR (dr.account_number IS NULL OR dr.account_number = '')
                   OR (dr.account_holder IS NULL OR dr.account_holder = '')
            ";
            
            $affectedRows = $pdo->exec($updateSql);
            if ($affectedRows > 0) {
                echo '<div class="success">✓ 기존 ' . $affectedRows . '건의 입금 신청 기록에 계좌 정보를 업데이트했습니다.</div>';
            } else {
                echo '<div class="info">기존 데이터는 이미 계좌 정보가 모두 저장되어 있습니다.</div>';
            }
            
            // 5. 업데이트 후 상태 확인
            $checkStmt = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN bank_name IS NULL OR bank_name = '' THEN 1 ELSE 0 END) as missing_bank_name,
                    SUM(CASE WHEN account_number IS NULL OR account_number = '' THEN 1 ELSE 0 END) as missing_account_number,
                    SUM(CASE WHEN account_holder IS NULL OR account_holder = '' THEN 1 ELSE 0 END) as missing_account_holder
                FROM deposit_requests
            ");
            $stats = $checkStmt->fetch(PDO::FETCH_ASSOC);
            echo '<div class="info">';
            echo '<strong>데이터 상태:</strong><br>';
            echo '- 전체 입금 신청: ' . $stats['total'] . '건<br>';
            echo '- 은행명 누락: ' . $stats['missing_bank_name'] . '건<br>';
            echo '- 계좌번호 누락: ' . $stats['missing_account_number'] . '건<br>';
            echo '- 예금주 누락: ' . $stats['missing_account_holder'] . '건';
            echo '</div>';
            
            $pdo->commit();
            
            echo '<div class="success"><strong>완료!</strong> 모든 작업이 성공적으로 완료되었습니다.</div>';
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo '<div class="error"><strong>오류 발생:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        ?>
        
        <div style="margin-top: 30px;">
            <button onclick="window.location.reload()">다시 실행</button>
            <button onclick="window.location.href='../admin/deposit/bank-accounts.php'" style="background: #6b7280; margin-left: 10px;">계좌 관리로 이동</button>
        </div>
    </div>
</body>
</html>