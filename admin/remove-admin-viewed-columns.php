<?php
/**
 * 관리자 확인 컬럼 제거 스크립트
 * 이 스크립트는 seller_inquiries 테이블에서 admin_viewed_at, admin_viewed_by 컬럼을 제거합니다.
 * 
 * 사용법: 브라우저에서 /MVNO/admin/remove-admin-viewed-columns.php 접속
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 체크
if (!isAdmin()) {
    die('관리자 권한이 필요합니다.');
}

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

$errors = [];
$success = [];
$info = [];

// 컬럼 존재 여부 확인
$stmt = $pdo->query("SHOW COLUMNS FROM seller_inquiries");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
$existingColumns = array_flip($columns);

$hasAdminViewedAt = isset($existingColumns['admin_viewed_at']);
$hasAdminViewedBy = isset($existingColumns['admin_viewed_by']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    try {
        $pdo->beginTransaction();
        
        // admin_viewed_at 컬럼 삭제
        if ($hasAdminViewedAt) {
            try {
                $pdo->exec("ALTER TABLE `seller_inquiries` DROP COLUMN `admin_viewed_at`");
                $success[] = "admin_viewed_at 컬럼이 성공적으로 삭제되었습니다.";
            } catch (PDOException $e) {
                $errors[] = "admin_viewed_at 컬럼 삭제 실패: " . $e->getMessage();
            }
        } else {
            $info[] = "admin_viewed_at 컬럼이 존재하지 않습니다.";
        }
        
        // admin_viewed_by 컬럼 삭제
        if ($hasAdminViewedBy) {
            try {
                $pdo->exec("ALTER TABLE `seller_inquiries` DROP COLUMN `admin_viewed_by`");
                $success[] = "admin_viewed_by 컬럼이 성공적으로 삭제되었습니다.";
            } catch (PDOException $e) {
                $errors[] = "admin_viewed_by 컬럼 삭제 실패: " . $e->getMessage();
            }
        } else {
            $info[] = "admin_viewed_by 컬럼이 존재하지 않습니다.";
        }
        
        if (empty($errors)) {
            $pdo->commit();
        } else {
            $pdo->rollBack();
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = "트랜잭션 오류: " . $e->getMessage();
    }
    
    // 삭제 후 다시 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM seller_inquiries");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $existingColumns = array_flip($columns);
    $hasAdminViewedAt = isset($existingColumns['admin_viewed_at']);
    $hasAdminViewedBy = isset($existingColumns['admin_viewed_by']);
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 확인 컬럼 제거</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #1f2937;
            margin-bottom: 24px;
        }
        .status {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .status.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .status.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .status.info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
        .status.warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde047;
        }
        .column-status {
            margin: 16px 0;
            padding: 12px;
            background: #f9fafb;
            border-radius: 6px;
        }
        .column-status.exists {
            border-left: 4px solid #ef4444;
        }
        .column-status.not-exists {
            border-left: 4px solid #10b981;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
            margin-right: 12px;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        .warning-box {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
        }
        .warning-box h3 {
            color: #92400e;
            margin-top: 0;
        }
        .warning-box ul {
            margin: 12px 0;
            padding-left: 24px;
        }
        .warning-box li {
            margin: 8px 0;
            color: #78350f;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>관리자 확인 컬럼 제거</h1>
        
        <?php if (!empty($success)): ?>
            <?php foreach ($success as $msg): ?>
                <div class="status success">✓ <?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $msg): ?>
                <div class="status error">✗ <?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($info)): ?>
            <?php foreach ($info as $msg): ?>
                <div class="status info">ℹ <?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="column-status <?php echo $hasAdminViewedAt ? 'exists' : 'not-exists'; ?>">
            <strong>admin_viewed_at 컬럼:</strong> 
            <?php echo $hasAdminViewedAt ? '존재함' : '존재하지 않음'; ?>
        </div>
        
        <div class="column-status <?php echo $hasAdminViewedBy ? 'exists' : 'not-exists'; ?>">
            <strong>admin_viewed_by 컬럼:</strong> 
            <?php echo $hasAdminViewedBy ? '존재함' : '존재하지 않음'; ?>
        </div>
        
        <?php if ($hasAdminViewedAt || $hasAdminViewedBy): ?>
            <div class="warning-box">
                <h3>⚠️ 주의사항</h3>
                <ul>
                    <li>이 작업은 되돌릴 수 없습니다.</li>
                    <li>컬럼 삭제 전에 데이터베이스 백업을 권장합니다.</li>
                    <li>이 컬럼들은 더 이상 사용되지 않으므로 안전하게 삭제할 수 있습니다.</li>
                </ul>
            </div>
            
            <form method="POST" onsubmit="return confirm('정말로 컬럼을 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.');">
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="btn btn-danger">컬럼 삭제 실행</button>
                <a href="/MVNO/admin/" class="btn btn-secondary">취소</a>
            </form>
        <?php else: ?>
            <div class="status success">
                ✓ 모든 관리자 확인 관련 컬럼이 이미 제거되었습니다.
            </div>
            <a href="/MVNO/admin/" class="btn btn-secondary">관리자 페이지로 돌아가기</a>
        <?php endif; ?>
    </div>
</body>
</html>



