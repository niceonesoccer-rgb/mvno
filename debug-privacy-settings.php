<?php
/**
 * 개인정보 설정 디버깅 스크립트
 * DB에 저장된 실제 값 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/privacy-functions.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>개인정보 설정 디버깅</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    pre { background: #f3f4f6; padding: 15px; border-radius: 8px; overflow-x: auto; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
    .error { color: #ef4444; }
    .success { color: #10b981; }
</style>";

// 1. DB에서 직접 조회
$pdo = getDBConnection();
if (!$pdo) {
    echo "<p class='error'>데이터베이스 연결 실패</p>";
    exit;
}

echo "<div class='section'>";
echo "<h2>1. DB에서 직접 조회 (app_settings 테이블)</h2>";
$stmt = $pdo->prepare("SELECT namespace, json_value, updated_at FROM app_settings WHERE namespace = 'privacy'");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo "<p class='success'>✓ privacy 설정이 DB에 있습니다.</p>";
    echo "<p><strong>업데이트 시간:</strong> " . htmlspecialchars($row['updated_at'] ?? 'N/A') . "</p>";
    
    $jsonValue = $row['json_value'];
    if (is_string($jsonValue)) {
        $decoded = json_decode($jsonValue, true);
    } else {
        $decoded = $jsonValue;
    }
    
    if ($decoded && is_array($decoded)) {
        echo "<h3>원본 JSON 데이터:</h3>";
        echo "<pre>" . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "</pre>";
        
        echo "<h3>각 항목의 isVisible 값:</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>항목</th><th>isVisible 값</th><th>타입</th><th>isset() 결과</th><th>array_key_exists() 결과</th></tr>";
        
        foreach (['purpose', 'items', 'period', 'thirdParty', 'serviceNotice', 'marketing'] as $key) {
            $item = $decoded[$key] ?? [];
            $isVisible = $item['isVisible'] ?? 'NOT SET';
            $isVisibleType = gettype($isVisible);
            $issetResult = isset($item['isVisible']) ? 'true' : 'false';
            $arrayKeyExistsResult = array_key_exists('isVisible', $item) ? 'true' : 'false';
            
            $color = ($isVisible === false) ? 'red' : (($isVisible === true) ? 'green' : 'gray');
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
            echo "<td style='color: $color;'><strong>" . var_export($isVisible, true) . "</strong></td>";
            echo "<td>" . htmlspecialchars($isVisibleType) . "</td>";
            echo "<td>" . htmlspecialchars($issetResult) . "</td>";
            echo "<td>" . htmlspecialchars($arrayKeyExistsResult) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p class='error'>✗ privacy 설정이 DB에 없습니다.</p>";
}
echo "</div>";

// 2. getPrivacySettings() 함수로 조회
echo "<div class='section'>";
echo "<h2>2. getPrivacySettings() 함수로 조회</h2>";
$settings = getPrivacySettings();

echo "<h3>반환된 설정:</h3>";
echo "<pre>" . htmlspecialchars(json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "</pre>";

echo "<h3>각 항목의 isVisible 값:</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>항목</th><th>isVisible 값</th><th>타입</th><th>화면 표시 여부</th></tr>";

foreach (['purpose', 'items', 'period', 'thirdParty', 'serviceNotice', 'marketing'] as $key) {
    $item = $settings[$key] ?? [];
    $isVisible = $item['isVisible'] ?? 'NOT SET';
    $isVisibleType = gettype($isVisible);
    
    // 화면 표시 여부 계산
    if (array_key_exists('isVisible', $item)) {
        $willDisplay = (bool)$item['isVisible'] ? '표시됨 ✅' : '숨김 ❌';
    } else {
        $willDisplay = '표시됨 ✅ (기본값)';
    }
    
    $color = ($isVisible === false) ? 'red' : (($isVisible === true) ? 'green' : 'gray');
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
    echo "<td style='color: $color;'><strong>" . var_export($isVisible, true) . "</strong></td>";
    echo "<td>" . htmlspecialchars($isVisibleType) . "</td>";
    echo "<td>" . htmlspecialchars($willDisplay) . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// 3. internets.php에서 사용하는 로직 시뮬레이션
echo "<div class='section'>";
echo "<h2>3. internets.php 렌더링 로직 시뮬레이션</h2>";
echo "<p>다음 항목들이 화면에 표시됩니다:</p>";
echo "<ul>";

foreach (['purpose', 'items', 'period', 'thirdParty', 'serviceNotice', 'marketing'] as $key) {
    $setting = $settings[$key] ?? [];
    
    // internets.php의 로직과 동일하게 체크
    if (array_key_exists('isVisible', $setting)) {
        $isVisible = (bool)$setting['isVisible'];
    } else {
        $isVisible = true; // 기본값: 노출
    }
    
    if ($isVisible) {
        $title = htmlspecialchars($setting['title'] ?? $key);
        $isRequired = $setting['isRequired'] ?? ($key !== 'marketing');
        $requiredText = $isRequired ? '(필수)' : '(선택)';
        echo "<li><strong>" . htmlspecialchars($title) . "</strong> " . htmlspecialchars($requiredText) . " ✅</li>";
    } else {
        echo "<li><strong>" . htmlspecialchars($key) . "</strong> ❌ (비노출로 설정됨)</li>";
    }
}
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><a href='/MVNO/admin/settings/privacy-settings.php'>관리자 페이지로 이동</a> | ";
echo "<a href='/MVNO/internets/internets.php'>가입 신청 페이지로 이동</a></p>";
?>









