<?php
/**
 * QnA 테이블 데이터 확인 스크립트
 * q2222222 사용자의 질문과 'default' user_id로 저장된 질문 확인
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QnA 데이터 확인</title>
    <style>
        body {
            font-family: 'Malgun Gothic', sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
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
        h2 {
            color: #6366f1;
            margin-top: 30px;
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
        .user-id-default {
            background: #fee2e2;
            color: #dc2626;
            font-weight: bold;
        }
        .user-id-q2222222 {
            background: #dbeafe;
            color: #1e40af;
            font-weight: bold;
        }
        .status-pending {
            color: #d97706;
        }
        .status-answered {
            color: #059669;
        }
        .content-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .stats {
            background: #f0f9ff;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .stats-item {
            display: inline-block;
            margin-right: 30px;
            font-size: 16px;
        }
        .stats-item strong {
            color: #6366f1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 QnA 테이블 데이터 확인</h1>
        
        <?php
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                echo '<p style="color: red;">❌ DB 연결 실패</p>';
                exit;
            }
            
            // 전체 QnA 개수
            $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM qna");
            $total = $totalStmt->fetch()['total'];
            
            // q2222222 사용자의 질문
            $q2222222Stmt = $pdo->prepare("SELECT COUNT(*) as total FROM qna WHERE user_id = 'q2222222'");
            $q2222222Stmt->execute();
            $q2222222Count = $q2222222Stmt->fetch()['total'];
            
            // 'default' user_id로 저장된 질문
            $defaultStmt = $pdo->prepare("SELECT COUNT(*) as total FROM qna WHERE user_id = 'default'");
            $defaultStmt->execute();
            $defaultCount = $defaultStmt->fetch()['total'];
            
            // null user_id로 저장된 질문
            $nullStmt = $pdo->query("SELECT COUNT(*) as total FROM qna WHERE user_id IS NULL");
            $nullCount = $nullStmt->fetch()['total'];
            
            // 답변 완료된 질문
            $answeredStmt = $pdo->query("SELECT COUNT(*) as total FROM qna WHERE status = 'answered'");
            $answeredCount = $answeredStmt->fetch()['total'];
            
            // 답변 대기 중인 질문
            $pendingStmt = $pdo->query("SELECT COUNT(*) as total FROM qna WHERE status = 'pending' OR status IS NULL");
            $pendingCount = $pendingStmt->fetch()['total'];
            
            echo '<div class="stats">';
            echo '<div class="stats-item"><strong>전체 질문:</strong> ' . number_format($total) . '개</div>';
            echo '<div class="stats-item"><strong>q2222222 질문:</strong> ' . number_format($q2222222Count) . '개</div>';
            echo '<div class="stats-item"><strong>default user_id:</strong> ' . number_format($defaultCount) . '개</div>';
            echo '<div class="stats-item"><strong>null user_id:</strong> ' . number_format($nullCount) . '개</div>';
            echo '<div class="stats-item"><strong>답변 완료:</strong> ' . number_format($answeredCount) . '개</div>';
            echo '<div class="stats-item"><strong>답변 대기:</strong> ' . number_format($pendingCount) . '개</div>';
            echo '</div>';
            
            // q2222222 사용자의 질문 목록
            if ($q2222222Count > 0) {
                echo '<h2>q2222222 사용자의 질문 (' . $q2222222Count . '개)</h2>';
                $q2222222ListStmt = $pdo->prepare("SELECT * FROM qna WHERE user_id = 'q2222222' ORDER BY created_at DESC");
                $q2222222ListStmt->execute();
                $q2222222List = $q2222222ListStmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<table>';
                echo '<tr><th>ID</th><th>제목</th><th>내용 미리보기</th><th>상태</th><th>답변</th><th>작성일</th><th>답변일</th></tr>';
                foreach ($q2222222List as $qna) {
                    $statusClass = ($qna['status'] === 'answered') ? 'status-answered' : 'status-pending';
                    $statusText = ($qna['status'] === 'answered') ? '답변완료' : '답변대기';
                    $hasAnswer = !empty($qna['answer']);
                    $answerPreview = $hasAnswer ? mb_substr($qna['answer'], 0, 50) . '...' : '-';
                    
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($qna['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($qna['title']) . '</td>';
                    echo '<td class="content-preview" title="' . htmlspecialchars($qna['content']) . '">' . htmlspecialchars(mb_substr($qna['content'], 0, 50)) . '...</td>';
                    echo '<td class="' . $statusClass . '">' . $statusText . '</td>';
                    echo '<td>' . ($hasAnswer ? htmlspecialchars($answerPreview) : '-') . '</td>';
                    echo '<td>' . htmlspecialchars($qna['created_at']) . '</td>';
                    echo '<td>' . (!empty($qna['answered_at']) ? htmlspecialchars($qna['answered_at']) : '-') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            
            // 'default' user_id로 저장된 질문 목록
            if ($defaultCount > 0) {
                echo '<h2>⚠️ "default" user_id로 저장된 질문 (' . $defaultCount . '개) - 로그인 문제로 인한 잘못된 데이터</h2>';
                $defaultListStmt = $pdo->prepare("SELECT * FROM qna WHERE user_id = 'default' ORDER BY created_at DESC");
                $defaultListStmt->execute();
                $defaultList = $defaultListStmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<table>';
                echo '<tr><th>ID</th><th>제목</th><th>내용 미리보기</th><th>상태</th><th>답변</th><th>작성일</th><th>답변일</th></tr>';
                foreach ($defaultList as $qna) {
                    $statusClass = ($qna['status'] === 'answered') ? 'status-answered' : 'status-pending';
                    $statusText = ($qna['status'] === 'answered') ? '답변완료' : '답변대기';
                    $hasAnswer = !empty($qna['answer']);
                    $answerPreview = $hasAnswer ? mb_substr($qna['answer'], 0, 50) . '...' : '-';
                    
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($qna['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($qna['title']) . '</td>';
                    echo '<td class="content-preview" title="' . htmlspecialchars($qna['content']) . '">' . htmlspecialchars(mb_substr($qna['content'], 0, 50)) . '...</td>';
                    echo '<td class="' . $statusClass . '">' . $statusText . '</td>';
                    echo '<td>' . ($hasAnswer ? htmlspecialchars($answerPreview) : '-') . '</td>';
                    echo '<td>' . htmlspecialchars($qna['created_at']) . '</td>';
                    echo '<td>' . (!empty($qna['answered_at']) ? htmlspecialchars($qna['answered_at']) : '-') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            
            // null user_id로 저장된 질문 목록
            if ($nullCount > 0) {
                echo '<h2>⚠️ NULL user_id로 저장된 질문 (' . $nullCount . '개) - 로그인 문제로 인한 잘못된 데이터</h2>';
                $nullListStmt = $pdo->query("SELECT * FROM qna WHERE user_id IS NULL ORDER BY created_at DESC");
                $nullList = $nullListStmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<table>';
                echo '<tr><th>ID</th><th>제목</th><th>내용 미리보기</th><th>상태</th><th>답변</th><th>작성일</th><th>답변일</th></tr>';
                foreach ($nullList as $qna) {
                    $statusClass = ($qna['status'] === 'answered') ? 'status-answered' : 'status-pending';
                    $statusText = ($qna['status'] === 'answered') ? '답변완료' : '답변대기';
                    $hasAnswer = !empty($qna['answer']);
                    $answerPreview = $hasAnswer ? mb_substr($qna['answer'], 0, 50) . '...' : '-';
                    
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($qna['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($qna['title']) . '</td>';
                    echo '<td class="content-preview" title="' . htmlspecialchars($qna['content']) . '">' . htmlspecialchars(mb_substr($qna['content'], 0, 50)) . '...</td>';
                    echo '<td class="' . $statusClass . '">' . $statusText . '</td>';
                    echo '<td>' . ($hasAnswer ? htmlspecialchars($answerPreview) : '-') . '</td>';
                    echo '<td>' . htmlspecialchars($qna['created_at']) . '</td>';
                    echo '<td>' . (!empty($qna['answered_at']) ? htmlspecialchars($qna['answered_at']) : '-') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            
            // 전체 질문 목록 (최근 20개)
            echo '<h2>전체 질문 목록 (최근 20개)</h2>';
            $allStmt = $pdo->query("SELECT * FROM qna ORDER BY created_at DESC LIMIT 20");
            $allList = $allStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<table>';
            echo '<tr><th>ID</th><th>User ID</th><th>제목</th><th>내용 미리보기</th><th>상태</th><th>작성일</th></tr>';
            foreach ($allList as $qna) {
                $userId = $qna['user_id'] ?? 'NULL';
                $userIdClass = '';
                if ($userId === 'default') {
                    $userIdClass = 'user-id-default';
                } elseif ($userId === 'q2222222') {
                    $userIdClass = 'user-id-q2222222';
                } elseif ($userId === 'NULL' || $userId === null) {
                    $userIdClass = 'user-id-default';
                }
                
                $statusClass = ($qna['status'] === 'answered') ? 'status-answered' : 'status-pending';
                $statusText = ($qna['status'] === 'answered') ? '답변완료' : '답변대기';
                
                echo '<tr>';
                echo '<td>' . htmlspecialchars($qna['id']) . '</td>';
                echo '<td class="' . $userIdClass . '">' . htmlspecialchars($userId) . '</td>';
                echo '<td>' . htmlspecialchars($qna['title']) . '</td>';
                echo '<td class="content-preview" title="' . htmlspecialchars($qna['content']) . '">' . htmlspecialchars(mb_substr($qna['content'], 0, 50)) . '...</td>';
                echo '<td class="' . $statusClass . '">' . $statusText . '</td>';
                echo '<td>' . htmlspecialchars($qna['created_at']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
            // user_id별 통계
            echo '<h2>User ID별 질문 통계</h2>';
            $userStatsStmt = $pdo->query("SELECT user_id, COUNT(*) as count FROM qna GROUP BY user_id ORDER BY count DESC");
            $userStats = $userStatsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<table>';
            echo '<tr><th>User ID</th><th>질문 수</th></tr>';
            foreach ($userStats as $stat) {
                $userId = $stat['user_id'] ?? 'NULL';
                $userIdClass = '';
                if ($userId === 'default') {
                    $userIdClass = 'user-id-default';
                } elseif ($userId === 'q2222222') {
                    $userIdClass = 'user-id-q2222222';
                } elseif ($userId === 'NULL' || $userId === null) {
                    $userIdClass = 'user-id-default';
                }
                
                echo '<tr>';
                echo '<td class="' . $userIdClass . '">' . htmlspecialchars($userId) . '</td>';
                echo '<td>' . number_format($stat['count']) . '개</td>';
                echo '</tr>';
            }
            echo '</table>';
            
        } catch (Exception $e) {
            echo '<p style="color: red;">❌ 오류 발생: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
</body>
</html>

