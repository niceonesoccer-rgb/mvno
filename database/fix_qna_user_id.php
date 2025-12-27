<?php
/**
 * QnA 테이블의 'default' user_id를 q2222222로 수정하는 스크립트
 * 로그인 문제로 인해 잘못 저장된 데이터 복구
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$targetUserId = 'q2222222'; // 수정할 user_id
$oldUserId = 'default'; // 수정할 기존 user_id

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QnA User ID 수정</title>
    <style>
        body {
            font-family: 'Malgun Gothic', sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 10px;
        }
        .info {
            background: #f0f9ff;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #6366f1;
        }
        .warning {
            background: #fef3c7;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #f59e0b;
        }
        .success {
            background: #d1fae5;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #059669;
        }
        .error {
            background: #fee2e2;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #dc2626;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #4f46e5;
        }
        .btn-danger {
            background: #dc2626;
        }
        .btn-danger:hover {
            background: #b91c1c;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #6366f1;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background: #f9fafb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 QnA User ID 수정</h1>
        
        <?php
        $action = $_GET['action'] ?? 'preview';
        
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                echo '<div class="error">❌ DB 연결 실패</div>';
                exit;
            }
            
            // 미리보기 모드
            if ($action === 'preview') {
                // 'default' user_id로 저장된 질문 개수 확인
                $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM qna WHERE user_id = :old_user");
                $countStmt->execute([':old_user' => $oldUserId]);
                $count = $countStmt->fetch()['count'];
                
                echo '<div class="info">';
                echo '<strong>수정 대상:</strong> "' . htmlspecialchars($oldUserId) . '" user_id로 저장된 질문<br>';
                echo '<strong>수정할 user_id:</strong> "' . htmlspecialchars($targetUserId) . '"<br>';
                echo '<strong>수정될 질문 수:</strong> ' . number_format($count) . '개';
                echo '</div>';
                
                if ($count > 0) {
                    echo '<div class="warning">';
                    echo '⚠️ <strong>주의:</strong> 이 작업은 되돌릴 수 없습니다. 수정 전에 데이터베이스를 백업하세요.';
                    echo '</div>';
                    
                    // 수정될 질문 목록 미리보기
                    $previewStmt = $pdo->prepare("SELECT id, title, created_at FROM qna WHERE user_id = :old_user ORDER BY created_at DESC LIMIT 10");
                    $previewStmt->execute([':old_user' => $oldUserId]);
                    $previewList = $previewStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo '<h2>수정될 질문 목록 (최근 10개)</h2>';
                    echo '<table>';
                    echo '<tr><th>ID</th><th>제목</th><th>작성일</th></tr>';
                    foreach ($previewList as $qna) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($qna['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($qna['title']) . '</td>';
                        echo '<td>' . htmlspecialchars($qna['created_at']) . '</td>';
                        echo '</tr>';
                    }
                    if ($count > 10) {
                        echo '<tr><td colspan="3" style="text-align: center; color: #6b7280;">... 외 ' . number_format($count - 10) . '개 더</td></tr>';
                    }
                    echo '</table>';
                    
                    echo '<div style="margin-top: 30px; text-align: center;">';
                    echo '<a href="?action=execute" class="btn btn-danger" onclick="return confirm(\'정말로 ' . $count . '개의 질문을 수정하시겠습니까?\\n\\n이 작업은 되돌릴 수 없습니다.\');">수정 실행</a>';
                    echo '<a href="check_qna_data.php" class="btn">취소 (데이터 확인으로 돌아가기)</a>';
                    echo '</div>';
                } else {
                    echo '<div class="success">';
                    echo '✅ 수정할 데이터가 없습니다. 모든 질문이 올바른 user_id로 저장되어 있습니다.';
                    echo '</div>';
                }
            }
            
            // 실행 모드
            elseif ($action === 'execute') {
                // 트랜잭션 시작
                $pdo->beginTransaction();
                
                try {
                    // 'default' user_id를 q2222222로 수정
                    $updateStmt = $pdo->prepare("UPDATE qna SET user_id = :new_user, updated_at = NOW() WHERE user_id = :old_user");
                    $updateStmt->execute([
                        ':new_user' => $targetUserId,
                        ':old_user' => $oldUserId
                    ]);
                    
                    $affectedRows = $updateStmt->rowCount();
                    
                    // 트랜잭션 커밋
                    $pdo->commit();
                    
                    echo '<div class="success">';
                    echo '✅ <strong>수정 완료!</strong><br>';
                    echo number_format($affectedRows) . '개의 질문이 "' . htmlspecialchars($targetUserId) . '" user_id로 수정되었습니다.';
                    echo '</div>';
                    
                    // 수정된 질문 목록 확인
                    $verifyStmt = $pdo->prepare("SELECT COUNT(*) as count FROM qna WHERE user_id = :new_user");
                    $verifyStmt->execute([':new_user' => $targetUserId]);
                    $newCount = $verifyStmt->fetch()['count'];
                    
                    $oldCountStmt = $pdo->prepare("SELECT COUNT(*) as count FROM qna WHERE user_id = :old_user");
                    $oldCountStmt->execute([':old_user' => $oldUserId]);
                    $oldCount = $oldCountStmt->fetch()['count'];
                    
                    echo '<div class="info" style="margin-top: 20px;">';
                    echo '<strong>수정 후 상태:</strong><br>';
                    echo '• "' . htmlspecialchars($targetUserId) . '" user_id 질문: ' . number_format($newCount) . '개<br>';
                    echo '• "' . htmlspecialchars($oldUserId) . '" user_id 질문: ' . number_format($oldCount) . '개';
                    echo '</div>';
                    
                    echo '<div style="margin-top: 30px; text-align: center;">';
                    echo '<a href="check_qna_data.php" class="btn">데이터 확인으로 돌아가기</a>';
                    echo '</div>';
                    
                } catch (Exception $e) {
                    // 트랜잭션 롤백
                    $pdo->rollBack();
                    throw $e;
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '❌ <strong>오류 발생:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
            
            echo '<div style="margin-top: 30px; text-align: center;">';
            echo '<a href="?action=preview" class="btn">다시 시도</a>';
            echo '<a href="check_qna_data.php" class="btn">데이터 확인으로 돌아가기</a>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>

