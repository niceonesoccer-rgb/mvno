<?php
/**
 * Q&A 삭제 이력 확인 스크립트
 * 최근 삭제된 Q&A를 확인합니다.
 */

require_once __DIR__ . '/../includes/data/db-config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDBConnection();
if (!$pdo) {
    die('DB 연결 실패');
}

echo "<h1>Q&A 삭제 이력 확인</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .info { padding: 15px; background: #e0f2fe; border-left: 4px solid #0284c7; margin: 20px 0; }
    .warning { padding: 15px; background: #fef3c7; border-left: 4px solid #f59e0b; margin: 20px 0; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #6366f1; color: white; }
</style>";

// 현재 Q&A 개수 확인
$stmt = $pdo->query("SELECT COUNT(*) FROM qna");
$currentCount = $stmt->fetchColumn();

echo "<div class='warning'>";
echo "<h2>현재 상태</h2>";
echo "<p><strong>현재 Q&A 개수: " . $currentCount . "건</strong></p>";
if ($currentCount == 0) {
    echo "<p style='color: red; font-weight: bold;'>⚠ 경고: Q&A 데이터가 모두 삭제되었습니다!</p>";
}
echo "</div>";

// MySQL 바이너리 로그 확인 (활성화되어 있는 경우)
echo "<div class='info'>";
echo "<h2>복구 방법</h2>";
echo "<h3>1. DB 백업 확인</h3>";
echo "<p>XAMPP의 경우 보통 다음 위치에 백업이 있을 수 있습니다:</p>";
echo "<ul>";
echo "<li>C:\\xampp\\mysql\\backup\\</li>";
echo "<li>C:\\xampp\\mysql\\data\\mvno_db\\</li>";
echo "</ul>";

echo "<h3>2. MySQL 바이너리 로그 확인</h3>";
try {
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'log_bin'");
    $logBin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($logBin && $logBin['Value'] === 'ON') {
        echo "<p style='color: green;'>✓ 바이너리 로그가 활성화되어 있습니다.</p>";
        echo "<p>다음 명령어로 최근 삭제 쿼리를 확인할 수 있습니다:</p>";
        echo "<pre style='background: #f3f4f6; padding: 10px; border-radius: 4px;'>";
        echo "mysqlbinlog C:\\xampp\\mysql\\data\\mysql-bin.XXXXXX | grep -i \"DELETE.*qna\"";
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠ 바이너리 로그가 비활성화되어 있습니다.</p>";
    }
} catch (Exception $e) {
    echo "<p>바이너리 로그 확인 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h3>3. 테이블 구조 확인</h3>";
try {
    $stmt = $pdo->query("DESCRIBE qna");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>✓ qna 테이블은 존재합니다.</p>";
    echo "<p>컬럼 개수: " . count($columns) . "개</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ qna 테이블이 존재하지 않습니다: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// 최근 실행된 쿼리 확인 (일반 로그가 활성화된 경우)
echo "<h2>최근 활동 확인</h2>";
try {
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'general_log'");
    $generalLog = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($generalLog && $generalLog['Value'] === 'ON') {
        echo "<p style='color: green;'>✓ 일반 로그가 활성화되어 있습니다.</p>";
        $stmt = $pdo->query("SHOW VARIABLES LIKE 'general_log_file'");
        $logFile = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($logFile) {
            echo "<p>로그 파일 위치: " . htmlspecialchars($logFile['Value']) . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ 일반 로그가 비활성화되어 있습니다.</p>";
    }
} catch (Exception $e) {
    echo "<p>로그 확인 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// PHP 에러 로그 확인 안내
echo "<div class='info'>";
echo "<h2>PHP 에러 로그 확인</h2>";
echo "<p>다음 위치에서 최근 에러 로그를 확인할 수 있습니다:</p>";
echo "<ul>";
echo "<li>C:\\xampp\\apache\\logs\\error.log</li>";
echo "<li>C:\\xampp\\php\\logs\\php_error_log</li>";
echo "</ul>";
echo "<p>다음 명령어로 Q&A 관련 로그를 확인:</p>";
echo "<pre style='background: #f3f4f6; padding: 10px; border-radius: 4px;'>";
echo "findstr /i \"QnA qna\" C:\\xampp\\apache\\logs\\error.log";
echo "</pre>";
echo "</div>";

// 안전장치 제안
echo "<div class='warning'>";
echo "<h2>안전장치 제안</h2>";
echo "<p>앞으로 이런 문제를 방지하기 위해:</p>";
echo "<ul>";
echo "<li>✅ DELETE 쿼리 실행 전 확인 대화상자 추가</li>";
echo "<li>✅ 소프트 삭제(soft delete) 구현 고려</li>";
echo "<li>✅ 정기적인 DB 백업 설정</li>";
echo "<li>✅ 트랜잭션 로그 활성화</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><a href='/MVNO/admin/test-qna-status.php?force=true'>상태 확인 페이지로</a> | ";
echo "<a href='/MVNO/admin/content/qna-manage.php'>관리자 페이지로</a></p>";
?>

