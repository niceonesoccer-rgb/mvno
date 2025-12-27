<?php
/**
 * 간단한 QnA User ID 수정 스크립트
 * 'default' → 'q2222222' 자동 수정
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>QnA User ID 수정</title>
    <style>
        body { font-family: 'Malgun Gothic', sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .success { background: #d1fae5; padding: 15px; border-radius: 6px; margin: 20px 0; color: #059669; }
        .error { background: #fee2e2; padding: 15px; border-radius: 6px; margin: 20px 0; color: #dc2626; }
        .btn { display: inline-block; padding: 12px 24px; background: #6366f1; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 QnA User ID 수정</h1>
        
        <?php
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                echo '<div class="error">❌ DB 연결 실패</div>';
                exit;
            }
            
            // 수정 전 개수 확인
            $beforeStmt = $pdo->prepare("SELECT COUNT(*) as count FROM qna WHERE user_id = 'default'");
            $beforeStmt->execute();
            $beforeCount = $beforeStmt->fetch()['count'];
            
            if ($beforeCount == 0) {
                echo '<div class="success">✅ 수정할 데이터가 없습니다.</div>';
            } else {
                // 트랜잭션 시작
                $pdo->beginTransaction();
                
                try {
                    // 'default' → 'q2222222' 수정
                    $updateStmt = $pdo->prepare("UPDATE qna SET user_id = 'q2222222', updated_at = NOW() WHERE user_id = 'default'");
                    $updateStmt->execute();
                    $affectedRows = $updateStmt->rowCount();
                    
                    // 커밋
                    $pdo->commit();
                    
                    // 수정 후 확인
                    $afterStmt = $pdo->prepare("SELECT COUNT(*) as count FROM qna WHERE user_id = 'q2222222'");
                    $afterStmt->execute();
                    $afterCount = $afterStmt->fetch()['count'];
                    
                    echo '<div class="success">';
                    echo '✅ <strong>수정 완료!</strong><br><br>';
                    echo "• 수정된 질문: " . number_format($affectedRows) . "개<br>";
                    echo "• q2222222 질문: " . number_format($afterCount) . "개<br>";
                    echo "• default 질문: 0개";
                    echo '</div>';
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="check_qna_data.php" class="btn">데이터 확인</a>
        </div>
    </div>
</body>
</html>

