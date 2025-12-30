<?php
/**
 * Q&A 답변 작성 테스트 스크립트
 * 답변 작성이 제대로 작동하는지 테스트합니다.
 */

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/qna-functions.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDBConnection();
if (!$pdo) {
    die('DB 연결 실패');
}

echo "<h1>Q&A 답변 작성 테스트</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
    .success { color: green; }
    .error { color: red; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #6366f1; color: white; }
</style>";

// 1. 답변 대기 중인 Q&A 찾기
echo "<div class='test-section'>";
echo "<h2>1. 답변 대기 중인 Q&A 찾기</h2>";
$stmt = $pdo->query("SELECT id, title, status, answer FROM qna WHERE status = 'pending' OR answer IS NULL OR answer = '' ORDER BY created_at DESC LIMIT 5");
$pendingQnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($pendingQnas)) {
    echo "<p class='error'>답변 대기 중인 Q&A가 없습니다.</p>";
} else {
    echo "<table>";
    echo "<tr><th>ID</th><th>제목</th><th>상태</th><th>답변 여부</th></tr>";
    foreach ($pendingQnas as $qna) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($qna['id']) . "</td>";
        echo "<td>" . htmlspecialchars(mb_substr($qna['title'], 0, 50)) . "</td>";
        echo "<td>" . htmlspecialchars($qna['status'] ?? 'NULL') . "</td>";
        echo "<td>" . (!empty($qna['answer']) ? '있음' : '없음') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 첫 번째 Q&A로 테스트 답변 작성
    if (!empty($pendingQnas)) {
        $testQnaId = $pendingQnas[0]['id'];
        $testAnswer = "테스트 답변입니다. 작성 시간: " . date('Y-m-d H:i:s');
        
        echo "<h3>테스트 답변 작성</h3>";
        echo "<p>Q&A ID: <strong>" . htmlspecialchars($testQnaId) . "</strong></p>";
        echo "<p>답변 내용: <strong>" . htmlspecialchars($testAnswer) . "</strong></p>";
        
        // 답변 작성 시도
        $result = answerQna($testQnaId, $testAnswer, 'test_admin');
        
        if ($result) {
            echo "<p class='success'>✓ 답변 작성 성공!</p>";
            
            // 저장 확인
            $verifyStmt = $pdo->prepare("SELECT id, title, status, answer, answered_at FROM qna WHERE id = :id LIMIT 1");
            $verifyStmt->execute([':id' => $testQnaId]);
            $verified = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($verified) {
                echo "<h4>저장 확인 결과:</h4>";
                echo "<table>";
                echo "<tr><th>필드</th><th>값</th></tr>";
                echo "<tr><td>ID</td><td>" . htmlspecialchars($verified['id']) . "</td></tr>";
                echo "<tr><td>제목</td><td>" . htmlspecialchars($verified['title']) . "</td></tr>";
                echo "<tr><td>상태</td><td><strong>" . htmlspecialchars($verified['status'] ?? 'NULL') . "</strong></td></tr>";
                echo "<tr><td>답변</td><td>" . htmlspecialchars(mb_substr($verified['answer'] ?? '', 0, 100)) . "</td></tr>";
                echo "<tr><td>답변일시</td><td>" . htmlspecialchars($verified['answered_at'] ?? 'NULL') . "</td></tr>";
                echo "</table>";
                
                if ($verified['status'] === 'answered') {
                    echo "<p class='success'>✓ Status가 'answered'로 정상 저장되었습니다!</p>";
                } else {
                    echo "<p class='error'>✗ Status가 'answered'가 아닙니다. 실제 값: " . htmlspecialchars($verified['status'] ?? 'NULL') . "</p>";
                }
            } else {
                echo "<p class='error'>✗ 저장 확인 실패: Q&A를 찾을 수 없습니다.</p>";
            }
        } else {
            echo "<p class='error'>✗ 답변 작성 실패!</p>";
            echo "<p>PHP 에러 로그를 확인해주세요.</p>";
        }
    }
}
echo "</div>";

// 2. 모든 Q&A 상태 확인
echo "<div class='test-section'>";
echo "<h2>2. 전체 Q&A 상태 확인</h2>";
$stmt = $pdo->query("SELECT id, title, status, answer, answered_at FROM qna ORDER BY created_at DESC");
$allQnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$answeredCount = 0;
$pendingCount = 0;

foreach ($allQnas as $qna) {
    if ($qna['status'] === 'answered') {
        $answeredCount++;
    } else {
        $pendingCount++;
    }
}

echo "<p>전체: " . count($allQnas) . "건 | ";
echo "답변대기: " . $pendingCount . "건 | ";
echo "답변완료: " . $answeredCount . "건</p>";

if ($answeredCount > 0) {
    echo "<p class='success'>✓ 답변 완료된 Q&A가 " . $answeredCount . "건 있습니다.</p>";
} else {
    echo "<p class='error'>✗ 답변 완료된 Q&A가 없습니다.</p>";
}
echo "</div>";

// 3. 데이터베이스 스키마 확인
echo "<div class='test-section'>";
echo "<h2>3. 데이터베이스 스키마 확인</h2>";
try {
    $stmt = $pdo->query("DESCRIBE qna");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>필드명</th><th>타입</th><th>NULL</th><th>기본값</th></tr>";
    foreach ($columns as $col) {
        $highlight = ($col['Field'] === 'status') ? " style='background-color: #fff3cd;'" : '';
        echo "<tr$highlight>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // status 컬럼의 ENUM 값 확인
    foreach ($columns as $col) {
        if ($col['Field'] === 'status') {
            echo "<p><strong>Status 컬럼 타입:</strong> " . htmlspecialchars($col['Type']) . "</p>";
            if (preg_match("/ENUM\('([^']+)','([^']+)'\)/", $col['Type'], $matches)) {
                echo "<p>ENUM 값: <strong>" . htmlspecialchars($matches[1]) . "</strong>, <strong>" . htmlspecialchars($matches[2]) . "</strong></p>";
            }
            break;
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>스키마 확인 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

echo "<hr>";
echo "<p><a href='/MVNO/admin/test-qna-status.php?force=true'>상태 확인 페이지로</a> | ";
echo "<a href='/MVNO/admin/content/qna-manage.php'>관리자 페이지로</a></p>";
?>








