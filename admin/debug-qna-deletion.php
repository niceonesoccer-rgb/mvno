<?php
/**
 * Q&A 삭제 문제 진단 도구
 */
require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/qna-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdmin()) {
    die('관리자 권한이 필요합니다.');
}

$pdo = getDBConnection();
if (!$pdo) {
    die('DB 연결 실패');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Q&A 삭제 문제 진단</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .error { color: red; }
        .success { color: green; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Q&A 삭제 문제 진단</h1>
    
    <?php
    // 1. 삭제된 Q&A 확인
    echo '<div class="section">';
    echo '<h2>1. 삭제된 Q&A 목록</h2>';
    $deletedQna = getDeletedQnaForAdmin();
    if (empty($deletedQna)) {
        echo '<p class="info">삭제된 Q&A가 없습니다.</p>';
    } else {
        echo '<table>';
        echo '<tr><th>ID</th><th>제목</th><th>작성자</th><th>삭제일시</th><th>답변 여부</th><th>상태</th></tr>';
        foreach ($deletedQna as $qna) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($qna['id']) . '</td>';
            echo '<td>' . htmlspecialchars($qna['title'] ?? 'NULL') . '</td>';
            echo '<td>' . htmlspecialchars($qna['user_id'] ?? 'NULL') . '</td>';
            echo '<td>' . htmlspecialchars($qna['deleted_at'] ?? 'NULL') . '</td>';
            echo '<td>' . (!empty($qna['answer']) ? '있음' : '없음') . '</td>';
            echo '<td>' . htmlspecialchars($qna['status'] ?? 'NULL') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    echo '</div>';
    
    // 2. 데이터베이스 트리거 확인
    echo '<div class="section">';
    echo '<h2>2. qna 테이블 관련 트리거 확인</h2>';
    try {
        $stmt = $pdo->query("
            SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_TIMING, ACTION_STATEMENT
            FROM information_schema.TRIGGERS
            WHERE TRIGGER_SCHEMA = DATABASE()
            AND EVENT_OBJECT_TABLE = 'qna'
        ");
        $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($triggers)) {
            echo '<p class="success">✓ qna 테이블에 트리거가 없습니다.</p>';
        } else {
            echo '<p class="error">⚠ qna 테이블에 트리거가 있습니다:</p>';
            echo '<table>';
            echo '<tr><th>트리거명</th><th>이벤트</th><th>타이밍</th><th>구문</th></tr>';
            foreach ($triggers as $trigger) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($trigger['TRIGGER_NAME']) . '</td>';
                echo '<td>' . htmlspecialchars($trigger['EVENT_MANIPULATION']) . '</td>';
                echo '<td>' . htmlspecialchars($trigger['ACTION_TIMING']) . '</td>';
                echo '<td>' . htmlspecialchars(substr($trigger['ACTION_STATEMENT'], 0, 200)) . '...</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } catch (Exception $e) {
        echo '<p class="error">트리거 확인 실패: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    
    // 3. 최근 삭제된 Q&A의 상세 정보
    echo '<div class="section">';
    echo '<h2>3. 최근 삭제된 Q&A 상세 정보</h2>';
    if (!empty($deletedQna)) {
        $latest = $deletedQna[0];
        echo '<p><strong>ID:</strong> ' . htmlspecialchars($latest['id']) . '</p>';
        echo '<p><strong>제목:</strong> ' . htmlspecialchars($latest['title'] ?? 'NULL') . '</p>';
        echo '<p><strong>내용:</strong> ' . htmlspecialchars(mb_substr($latest['content'] ?? 'NULL', 0, 100)) . '...</p>';
        echo '<p><strong>답변:</strong> ' . (!empty($latest['answer']) ? htmlspecialchars(mb_substr($latest['answer'], 0, 100)) . '...' : '없음') . '</p>';
        echo '<p><strong>상태:</strong> ' . htmlspecialchars($latest['status'] ?? 'NULL') . '</p>';
        echo '<p><strong>작성일:</strong> ' . htmlspecialchars($latest['created_at'] ?? 'NULL') . '</p>';
        echo '<p><strong>삭제일:</strong> ' . htmlspecialchars($latest['deleted_at'] ?? 'NULL') . '</p>';
        echo '<p><strong>답변일:</strong> ' . htmlspecialchars($latest['answered_at'] ?? 'NULL') . '</p>';
    } else {
        echo '<p class="info">삭제된 Q&A가 없습니다.</p>';
    }
    echo '</div>';
    
    // 4. PHP 에러 로그 확인 (최근 QnA 관련)
    echo '<div class="section">';
    echo '<h2>4. 최근 에러 로그 (QnA 관련)</h2>';
    $errorLogPath = ini_get('error_log');
    if ($errorLogPath && file_exists($errorLogPath)) {
        $lines = file($errorLogPath);
        $qnaLines = array_filter($lines, function($line) {
            return stripos($line, 'qna') !== false || stripos($line, 'QnA') !== false;
        });
        $qnaLines = array_slice($qnaLines, -20); // 최근 20줄
        if (empty($qnaLines)) {
            echo '<p class="info">QnA 관련 에러 로그가 없습니다.</p>';
        } else {
            echo '<pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;">';
            foreach ($qnaLines as $line) {
                echo htmlspecialchars($line);
            }
            echo '</pre>';
        }
    } else {
        echo '<p class="info">에러 로그 파일을 찾을 수 없습니다: ' . ($errorLogPath ?: '설정되지 않음') . '</p>';
    }
    echo '</div>';
    ?>
    
    <div class="section">
        <h2>5. 복구</h2>
        <?php if (!empty($deletedQna)): ?>
            <p>삭제된 Q&A를 복구하려면 관리자 페이지에서 복구 버튼을 클릭하세요.</p>
            <a href="/MVNO/admin/content/qna-manage.php?show_deleted=1">삭제된 항목 보기</a>
        <?php else: ?>
            <p class="info">복구할 항목이 없습니다.</p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <a href="/MVNO/admin/content/qna-manage.php">관리자 페이지로</a>
    </div>
</body>
</html>




