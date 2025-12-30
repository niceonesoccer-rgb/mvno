<?php
/**
 * 판매자 공지사항 필터링 디버깅 스크립트
 * 판매자 페이지에서 1번 공지사항이 안 보이는 원인 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';

date_default_timezone_set('Asia/Seoul');

$pdo = getDBConnection();
if (!$pdo) {
    die('DB 연결 실패');
}

echo "<h2>판매자 공지사항 필터링 디버깅</h2>";

// 현재 날짜
$currentDate = date('Y-m-d');
$currentDateTime = date('Y-m-d H:i:s');
echo "<p><strong>현재 날짜:</strong> {$currentDate}</p>";
echo "<p><strong>현재 일시:</strong> {$currentDateTime}</p>";
echo "<hr>";

// 1. 관리자용 쿼리 (모든 판매자 공지사항)
echo "<h3>1. 관리자용 쿼리 (모든 판매자 공지사항)</h3>";
$adminSql = "SELECT id, title, start_at, end_at, target_audience, created_at FROM notices WHERE target_audience = 'seller' ORDER BY id DESC";
$stmt = $pdo->query($adminSql);
$adminNotices = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>제목</th><th>시작일</th><th>종료일</th><th>생성일</th></tr>";
foreach ($adminNotices as $notice) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($notice['id'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($notice['title'] ?? '') . "</td>";
    echo "<td>" . ($notice['start_at'] ?? 'NULL') . "</td>";
    echo "<td>" . ($notice['end_at'] ?? 'NULL') . "</td>";
    echo "<td>" . ($notice['created_at'] ?? '') . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "<p><strong>관리자 페이지에서 보이는 공지사항 수:</strong> " . count($adminNotices) . "</p>";
echo "<hr>";

// 2. 판매자용 쿼리 (기간 필터링 적용)
echo "<h3>2. 판매자용 쿼리 (기간 필터링 적용)</h3>";
echo "<p><strong>SQL 조건:</strong></p>";
echo "<pre>";
echo "WHERE target_audience = 'seller'\n";
echo "AND (start_at IS NULL OR start_at <= CURDATE())\n";
echo "AND (end_at IS NULL OR end_at >= CURDATE())\n";
echo "</pre>";

$sellerSql = "SELECT id, title, start_at, end_at, target_audience, created_at 
              FROM notices 
              WHERE target_audience = 'seller'
              AND (start_at IS NULL OR start_at <= CURDATE())
              AND (end_at IS NULL OR end_at >= CURDATE())
              ORDER BY id DESC";
$stmt = $pdo->query($sellerSql);
$sellerNotices = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>제목</th><th>시작일</th><th>종료일</th><th>생성일</th></tr>";
foreach ($sellerNotices as $notice) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($notice['id'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($notice['title'] ?? '') . "</td>";
    echo "<td>" . ($notice['start_at'] ?? 'NULL') . "</td>";
    echo "<td>" . ($notice['end_at'] ?? 'NULL') . "</td>";
    echo "<td>" . ($notice['created_at'] ?? '') . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "<p><strong>판매자 페이지에서 보이는 공지사항 수:</strong> " . count($sellerNotices) . "</p>";
echo "<hr>";

// 3. 각 공지사항별 상세 분석
echo "<h3>3. 각 공지사항별 상세 분석</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>제목</th><th>start_at</th><th>end_at</th><th>start_at 조건</th><th>end_at 조건</th><th>결과</th></tr>";

foreach ($adminNotices as $notice) {
    $id = $notice['id'] ?? '';
    $title = $notice['title'] ?? '';
    $startAt = $notice['start_at'];
    $endAt = $notice['end_at'];
    
    // start_at 조건 체크
    $startAtOk = false;
    if ($startAt === null || $startAt === '') {
        $startAtOk = true;
        $startAtCondition = "NULL 또는 빈값 → OK";
    } else {
        $startDate = new DateTime($startAt);
        $currentDateObj = new DateTime($currentDate);
        if ($startDate <= $currentDateObj) {
            $startAtOk = true;
            $startAtCondition = "{$startAt} <= {$currentDate} → OK";
        } else {
            $startAtCondition = "{$startAt} > {$currentDate} → FAIL";
        }
    }
    
    // end_at 조건 체크
    $endAtOk = false;
    if ($endAt === null || $endAt === '') {
        $endAtOk = true;
        $endAtCondition = "NULL 또는 빈값 → OK";
    } else {
        $endDate = new DateTime($endAt);
        $currentDateObj = new DateTime($currentDate);
        if ($endDate >= $currentDateObj) {
            $endAtOk = true;
            $endAtCondition = "{$endAt} >= {$currentDate} → OK";
        } else {
            $endAtCondition = "{$endAt} < {$currentDate} → FAIL";
        }
    }
    
    $result = ($startAtOk && $endAtOk) ? "✓ 표시됨" : "✗ 표시 안됨";
    $rowColor = ($startAtOk && $endAtOk) ? '' : 'background-color: #ffcccc;';
    
    echo "<tr style='{$rowColor}'>";
    echo "<td>" . htmlspecialchars($id) . "</td>";
    echo "<td>" . htmlspecialchars($title) . "</td>";
    echo "<td>" . ($startAt ?? 'NULL') . "</td>";
    echo "<td>" . ($endAt ?? 'NULL') . "</td>";
    echo "<td>{$startAtCondition}</td>";
    echo "<td>{$endAtCondition}</td>";
    echo "<td><strong>{$result}</strong></td>";
    echo "</tr>";
}

echo "</table>";
echo "<hr>";

// 4. SQL CURDATE() 값 확인
echo "<h3>4. SQL CURDATE() 값 확인</h3>";
$curdateStmt = $pdo->query("SELECT CURDATE() as curdate");
$curdateResult = $curdateStmt->fetch(PDO::FETCH_ASSOC);
echo "<p><strong>SQL CURDATE():</strong> " . ($curdateResult['curdate'] ?? 'N/A') . "</p>";
echo "<p><strong>PHP date('Y-m-d'):</strong> {$currentDate}</p>";

// 5. 1번 공지사항 상세 확인
echo "<h3>5. 1번 공지사항 상세 확인</h3>";
$firstNotice = null;
foreach ($adminNotices as $notice) {
    if (strpos($notice['title'] ?? '', '첫번째') !== false || $notice['title'] === '첫번째') {
        $firstNotice = $notice;
        break;
    }
}

if ($firstNotice) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    foreach ($firstNotice as $key => $value) {
        echo "<tr><th>{$key}</th><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";
    
    // 날짜 비교
    $startAt = $firstNotice['start_at'];
    $endAt = $firstNotice['end_at'];
    
    echo "<h4>날짜 비교 결과:</h4>";
    echo "<ul>";
    echo "<li>start_at: " . ($startAt ?? 'NULL');
    if ($startAt) {
        $startDate = new DateTime($startAt);
        $currentDateObj = new DateTime($currentDate);
        echo " → " . ($startDate <= $currentDateObj ? "✓ OK (시작일 이전 또는 같음)" : "✗ FAIL (시작일 이전)");
    }
    echo "</li>";
    
    echo "<li>end_at: " . ($endAt ?? 'NULL');
    if ($endAt) {
        $endDate = new DateTime($endAt);
        $currentDateObj = new DateTime($currentDate);
        echo " → " . ($endDate >= $currentDateObj ? "✓ OK (종료일 이후 또는 같음)" : "✗ FAIL (종료일 지남)");
    }
    echo "</li>";
    echo "</ul>";
} else {
    echo "<p>1번 공지사항을 찾을 수 없습니다.</p>";
}

?>
