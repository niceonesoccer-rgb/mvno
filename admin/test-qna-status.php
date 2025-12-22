<?php
/**
 * Q&A 상태 확인 테스트 스크립트
 * DB에 저장된 Q&A의 상태를 확인합니다.
 */

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/qna-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 확인
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 디버깅: 세션 및 사용자 정보 확인
$currentUser = getCurrentUser();
$isAdminCheck = isAdmin();

echo "<h2>디버깅 정보</h2>";
echo "<ul>";
echo "<li>세션 ID: " . session_id() . "</li>";
echo "<li>현재 사용자: " . ($currentUser ? htmlspecialchars($currentUser['user_id'] ?? 'NULL') : 'NULL') . "</li>";
echo "<li>사용자 역할: " . ($currentUser ? htmlspecialchars($currentUser['role'] ?? 'NULL') : 'NULL') . "</li>";
echo "<li>isAdmin() 결과: " . ($isAdminCheck ? 'true' : 'false') . "</li>";
echo "</ul>";

// GET 파라미터로 강제 실행 허용 (개발용)
$forceRun = isset($_GET['force']) && $_GET['force'] === 'true';

if (!$isAdminCheck && !$forceRun) {
    echo "<div style='padding: 20px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #991b1b; margin-top: 0;'>관리자 권한이 필요합니다.</h3>";
    echo "<p>현재 사용자: " . ($currentUser ? htmlspecialchars($currentUser['user_id'] ?? 'NULL') . " (역할: " . htmlspecialchars($currentUser['role'] ?? 'NULL') . ")" : '로그인 안 됨') . "</p>";
    echo "<p><strong>개발/테스트 목적:</strong> <a href='?force=true' style='color: #6366f1;'>강제 실행</a></p>";
    echo "</div>";
    die();
}

$pdo = getDBConnection();
if (!$pdo) {
    die('DB 연결 실패');
}

echo "<h1>Q&A 상태 확인</h1>";
echo "<style>
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #6366f1; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    .status-pending { color: #d97706; font-weight: bold; }
    .status-answered { color: #059669; font-weight: bold; }
</style>";

// 모든 Q&A 조회
$stmt = $pdo->query("SELECT id, user_id, title, status, answer, answered_at, created_at FROM qna ORDER BY created_at DESC");
$allQnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>전체 Q&A 목록 (" . count($allQnas) . "건)</h2>";
echo "<table>";
echo "<tr><th>ID</th><th>사용자</th><th>제목</th><th>상태</th><th>답변 여부</th><th>답변일시</th><th>작성일시</th></tr>";

$pendingCount = 0;
$answeredCount = 0;

foreach ($allQnas as $qna) {
    $status = $qna['status'] ?? 'NULL';
    $hasAnswer = !empty($qna['answer']);
    $answeredAt = $qna['answered_at'] ?? '';
    
    if ($status === 'answered') {
        $answeredCount++;
    } else {
        $pendingCount++;
    }
    
    $statusClass = $status === 'answered' ? 'status-answered' : 'status-pending';
    $statusText = $status === 'answered' ? '답변완료' : ($status === 'pending' ? '답변대기' : $status);
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars(substr($qna['id'], 0, 20)) . "...</td>";
    echo "<td>" . htmlspecialchars($qna['user_id']) . "</td>";
    echo "<td>" . htmlspecialchars(mb_substr($qna['title'], 0, 30)) . "...</td>";
    echo "<td class='$statusClass'>" . htmlspecialchars($statusText) . " (" . htmlspecialchars($status) . ")</td>";
    echo "<td>" . ($hasAnswer ? '있음' : '없음') . "</td>";
    echo "<td>" . ($answeredAt ? htmlspecialchars($answeredAt) : '-') . "</td>";
    echo "<td>" . htmlspecialchars($qna['created_at']) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>통계</h2>";
echo "<ul>";
echo "<li>전체: " . count($allQnas) . "건</li>";
echo "<li>답변대기: " . $pendingCount . "건</li>";
echo "<li>답변완료: " . $answeredCount . "건</li>";
echo "</ul>";

// 답변이 있지만 status가 'answered'가 아닌 경우 확인
echo "<h2>데이터 불일치 확인</h2>";
$inconsistent = [];
foreach ($allQnas as $qna) {
    $hasAnswer = !empty($qna['answer']);
    $status = $qna['status'] ?? '';
    
    if ($hasAnswer && $status !== 'answered') {
        $inconsistent[] = $qna;
    }
}

if (empty($inconsistent)) {
    echo "<p style='color: green;'>✓ 모든 데이터가 일치합니다.</p>";
} else {
    echo "<p style='color: red;'>⚠ 데이터 불일치 발견: " . count($inconsistent) . "건</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>제목</th><th>상태</th><th>답변 여부</th></tr>";
    foreach ($inconsistent as $qna) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars(substr($qna['id'], 0, 20)) . "...</td>";
        echo "<td>" . htmlspecialchars(mb_substr($qna['title'], 0, 30)) . "...</td>";
        echo "<td>" . htmlspecialchars($qna['status'] ?? 'NULL') . "</td>";
        echo "<td>있음</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 최근 답변 작성 로그 확인
echo "<h2>최근 업데이트된 Q&A (답변이 있는 항목)</h2>";
$stmt = $pdo->query("SELECT id, title, status, answer, answered_at, updated_at FROM qna WHERE answer IS NOT NULL AND answer != '' ORDER BY updated_at DESC LIMIT 10");
$recentAnswered = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($recentAnswered)) {
    echo "<p>답변이 있는 Q&A가 없습니다.</p>";
} else {
    echo "<table>";
    echo "<tr><th>ID</th><th>제목</th><th>상태</th><th>답변일시</th><th>수정일시</th></tr>";
    foreach ($recentAnswered as $qna) {
        $statusClass = $qna['status'] === 'answered' ? 'status-answered' : 'status-pending';
        echo "<tr>";
        echo "<td>" . htmlspecialchars(substr($qna['id'], 0, 20)) . "...</td>";
        echo "<td>" . htmlspecialchars(mb_substr($qna['title'], 0, 30)) . "...</td>";
        echo "<td class='$statusClass'>" . htmlspecialchars($qna['status'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($qna['answered_at'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($qna['updated_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<p><a href='/MVNO/admin/content/qna-manage.php'>관리자 페이지로 돌아가기</a></p>";
?>
