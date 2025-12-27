<?php
/**
 * isVisible 디버깅 스크립트
 * DB에 저장된 실제 값과 PHP에서 읽은 값을 비교
 */

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/privacy-functions.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>isVisible 디버깅</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    pre { background: #f9fafb; padding: 15px; border-radius: 4px; overflow-x: auto; border: 1px solid #e5e7eb; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 12px; text-align: left; border: 1px solid #e5e7eb; }
    th { background: #6366f1; color: white; }
    tr:nth-child(even) { background: #f9fafb; }
    .error { color: #ef4444; font-weight: bold; }
    .success { color: #10b981; font-weight: bold; }
    .warning { color: #f59e0b; font-weight: bold; }
    .code { background: #1f2937; color: #f9fafb; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>";

$pdo = getDBConnection();
if (!$pdo) {
    echo "<div class='section'><p class='error'>데이터베이스 연결 실패</p></div>";
    exit;
}

// 1. DB에서 직접 조회
echo "<div class='section'>";
echo "<h2>1. DB에서 직접 조회 (원본 JSON)</h2>";
$stmt = $pdo->prepare("SELECT json_value FROM app_settings WHERE namespace = 'privacy' LIMIT 1");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row && isset($row['json_value'])) {
    $jsonValue = $row['json_value'];
    $decoded = is_string($jsonValue) ? json_decode($jsonValue, true) : $jsonValue;
    
    if ($decoded && is_array($decoded)) {
        echo "<h3>원본 JSON 데이터:</h3>";
        echo "<pre>" . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "</pre>";
        
        echo "<h3>각 항목의 isVisible 상세 분석:</h3>";
        echo "<table>";
        echo "<tr><th>항목</th><th>원본 값</th><th>타입</th><th>=== false</th><th>== false</th><th>!== false</th><th>!= false</th><th>array_key_exists</th></tr>";
        
        foreach (['purpose', 'items', 'period', 'thirdParty', 'serviceNotice', 'marketing'] as $key) {
            $item = $decoded[$key] ?? [];
            $rawValue = $item['isVisible'] ?? 'NOT SET';
            $rawType = isset($item['isVisible']) ? gettype($item['isVisible']) : 'NOT SET';
            
            $strictFalse = ($rawValue === false) ? 'true' : 'false';
            $looseFalse = ($rawValue == false) ? 'true' : 'false';
            $strictNotFalse = ($rawValue !== false) ? 'true' : 'false';
            $looseNotFalse = ($rawValue != false) ? 'true' : 'false';
            $hasKey = array_key_exists('isVisible', $item) ? 'true' : 'false';
            
            $color = ($rawValue === false) ? 'error' : (($rawValue === true) ? 'success' : 'warning');
            
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
            echo "<td class='$color'>" . var_export($rawValue, true) . "</td>";
            echo "<td>" . htmlspecialchars($rawType) . "</td>";
            echo "<td>" . htmlspecialchars($strictFalse) . "</td>";
            echo "<td>" . htmlspecialchars($looseFalse) . "</td>";
            echo "<td>" . htmlspecialchars($strictNotFalse) . "</td>";
            echo "<td>" . htmlspecialchars($looseNotFalse) . "</td>";
            echo "<td>" . htmlspecialchars($hasKey) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p class='warning'>DB에 privacy 설정이 없습니다.</p>";
}
echo "</div>";

// 2. getPrivacySettings() 함수로 조회
echo "<div class='section'>";
echo "<h2>2. getPrivacySettings() 함수로 조회</h2>";
$settings = getPrivacySettings();

echo "<h3>반환된 설정:</h3>";
echo "<pre>" . htmlspecialchars(json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "</pre>";

echo "<h3>각 항목의 isVisible 값 (함수 반환값):</h3>";
echo "<table>";
echo "<tr><th>항목</th><th>값</th><th>타입</th><th>=== false</th><th>!== false</th><th>array_key_exists</th><th>렌더링 여부</th></tr>";

foreach (['purpose', 'items', 'period', 'thirdParty', 'serviceNotice', 'marketing'] as $key) {
    $item = $settings[$key] ?? [];
    $value = $item['isVisible'] ?? 'NOT SET';
    $type = isset($item['isVisible']) ? gettype($item['isVisible']) : 'NOT SET';
    
    $strictFalse = ($value === false) ? 'true' : 'false';
    $strictNotFalse = ($value !== false) ? 'true' : 'false';
    $hasKey = array_key_exists('isVisible', $item) ? 'true' : 'false';
    
    // internets.php의 로직 시뮬레이션
    if (array_key_exists('isVisible', $item)) {
        $isVisible = (bool)$item['isVisible'];
    } else {
        $isVisible = true;
    }
    
    $willRender = $isVisible ? '✅ 표시됨' : '❌ 숨김';
    $color = ($value === false) ? 'error' : (($value === true) ? 'success' : 'warning');
    
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
    echo "<td class='$color'>" . var_export($value, true) . "</td>";
    echo "<td>" . htmlspecialchars($type) . "</td>";
    echo "<td>" . htmlspecialchars($strictFalse) . "</td>";
    echo "<td>" . htmlspecialchars($strictNotFalse) . "</td>";
    echo "<td>" . htmlspecialchars($hasKey) . "</td>";
    echo "<td><strong>" . htmlspecialchars($willRender) . "</strong></td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// 3. internets.php 렌더링 로직 시뮬레이션
echo "<div class='section'>";
echo "<h2>3. internets.php 렌더링 로직 시뮬레이션</h2>";
echo "<p>다음 항목들이 화면에 표시됩니다:</p>";
echo "<ul style='line-height: 2;'>";

foreach (['purpose', 'items', 'period', 'thirdParty', 'serviceNotice', 'marketing'] as $key) {
    $setting = $settings[$key] ?? [];
    
    // internets.php의 정확한 로직
    if (array_key_exists('isVisible', $setting)) {
        $isVisible = (bool)$setting['isVisible'];
    } else {
        $isVisible = true;
    }
    
    if ($isVisible) {
        $title = htmlspecialchars($setting['title'] ?? $key);
        $isRequired = $setting['isRequired'] ?? ($key !== 'marketing');
        $requiredText = $isRequired ? '(필수)' : '(선택)';
        echo "<li><strong>" . htmlspecialchars($title) . "</strong> " . htmlspecialchars($requiredText) . " <span class='success'>✅ 표시됨</span></li>";
    } else {
        echo "<li><strong>" . htmlspecialchars($key) . "</strong> <span class='error'>❌ 숨김 (비노출)</span></li>";
    }
}
echo "</ul>";
echo "</div>";

// 4. 문제 진단
echo "<div class='section'>";
echo "<h2>4. 문제 진단</h2>";

$purposeSetting = $settings['purpose'] ?? [];
$purposeIsVisible = null;
$purposeHasKey = array_key_exists('isVisible', $purposeSetting);

if ($purposeHasKey) {
    $purposeIsVisible = $purposeSetting['isVisible'];
}

echo "<h3>purpose 항목 상세 분석:</h3>";
echo "<ul>";
echo "<li><strong>array_key_exists('isVisible', \$setting):</strong> " . ($purposeHasKey ? '<span class="success">true</span>' : '<span class="error">false</span>') . "</li>";
echo "<li><strong>\$setting['isVisible'] 원본 값:</strong> " . var_export($purposeIsVisible, true) . "</li>";
echo "<li><strong>타입:</strong> " . ($purposeIsVisible !== null ? gettype($purposeIsVisible) : 'null') . "</li>";
echo "<li><strong>(bool)\$setting['isVisible']:</strong> " . var_export($purposeIsVisible !== null ? (bool)$purposeIsVisible : null, true) . "</li>";

if ($purposeHasKey) {
    $calculatedVisible = (bool)$purposeSetting['isVisible'];
    echo "<li><strong>계산된 \$isVisible:</strong> " . var_export($calculatedVisible, true) . "</li>";
    echo "<li><strong>렌더링 여부:</strong> " . ($calculatedVisible ? '<span class="error">❌ 표시됨 (문제!)</span>' : '<span class="success">✅ 숨김 (정상)</span>') . "</li>";
    
    if ($calculatedVisible && $purposeSetting['isVisible'] === false) {
        echo "<li class='error'><strong>⚠️ 문제 발견:</strong> DB에 isVisible = false로 저장되어 있지만, (bool)false = false이므로 숨겨져야 하는데 표시되고 있습니다. 로직을 확인하세요.</li>";
    }
} else {
    echo "<li class='warning'><strong>⚠️ 문제 발견:</strong> isVisible 키가 없어서 기본값 true로 설정됩니다.</li>";
}
echo "</ul>";
echo "</div>";

// 5. 해결 방법 제시
echo "<div class='section'>";
echo "<h2>5. 해결 방법</h2>";
echo "<p>현재 코드:</p>";
echo "<pre class='code'>if (array_key_exists('isVisible', \$setting)) {
    \$isVisible = (bool)\$setting['isVisible'];
} else {
    \$isVisible = true;
}

if (!\$isVisible) {
    continue;
}</pre>";

echo "<p>만약 DB에 <span class='code'>isVisible: false</span>로 저장되어 있다면:</p>";
echo "<ul>";
echo "<li><span class='code'>array_key_exists('isVisible', \$setting)</span> = true</li>";
echo "<li><span class='code'>\$setting['isVisible']</span> = false</li>";
echo "<li><span class='code'>(bool)false</span> = false</li>";
echo "<li><span class='code'>!\$isVisible</span> = true</li>";
echo "<li><span class='code'>continue</span> 실행 → 숨김 ✅</li>";
echo "</ul>";

echo "<p class='warning'>만약 여전히 표시된다면, 다음을 확인하세요:</p>";
echo "<ol>";
echo "<li>DB에 실제로 <span class='code'>isVisible: false</span>로 저장되어 있는지 확인</li>";
echo "<li>캐시 문제인지 확인 (브라우저 새로고침)</li>";
echo "<li>다른 PHP 파일에서 값을 덮어쓰는지 확인</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><a href='/MVNO/admin/settings/privacy-settings.php'>관리자 페이지로 이동</a> | ";
echo "<a href='/MVNO/internets/internets.php'>가입 신청 페이지로 이동</a> | ";
echo "<a href='/MVNO/debug-privacy-settings.php'>전체 설정 확인</a></p>";
?>






