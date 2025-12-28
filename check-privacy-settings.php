<?php
/**
 * 개인정보 설정 DB 저장 확인 스크립트
 * 모든 필드가 제대로 저장되었는지 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/app-settings.php';
require_once __DIR__ . '/includes/data/privacy-functions.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>개인정보 설정 DB 저장 확인</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #6366f1; color: white; }
    tr:nth-child(even) { background-color: #f9fafb; }
    .success { color: #10b981; font-weight: bold; }
    .error { color: #ef4444; font-weight: bold; }
    .info { color: #6b7280; }
</style>";

// 데이터베이스에서 직접 조회
$pdo = getDBConnection();
if (!$pdo) {
    echo "<p class='error'>데이터베이스 연결 실패</p>";
    exit;
}

echo "<h2>1. app_settings 테이블에서 직접 조회</h2>";
$stmt = $pdo->prepare("SELECT namespace, json_value, updated_by, updated_at FROM app_settings WHERE namespace = 'privacy'");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo "<p class='success'>✓ privacy 설정이 데이터베이스에 저장되어 있습니다.</p>";
    echo "<p><strong>업데이트 시간:</strong> " . htmlspecialchars($row['updated_at'] ?? 'N/A') . "</p>";
    echo "<p><strong>업데이트한 사용자:</strong> " . htmlspecialchars($row['updated_by'] ?? 'N/A') . "</p>";
    
    $jsonValue = $row['json_value'];
    if (is_string($jsonValue)) {
        $decoded = json_decode($jsonValue, true);
    } else {
        $decoded = $jsonValue;
    }
    
    if ($decoded && is_array($decoded)) {
        echo "<h2>2. 저장된 필드 확인</h2>";
        echo "<table>";
        echo "<tr><th>항목</th><th>제목</th><th>노출 여부</th><th>선택/필수</th><th>내용 길이</th></tr>";
        
        $allFieldsPresent = true;
        foreach (['purpose', 'items', 'period', 'thirdParty', 'serviceNotice', 'marketing'] as $key) {
            $item = $decoded[$key] ?? [];
            $hasTitle = isset($item['title']) && !empty($item['title']);
            $hasContent = isset($item['content']) && !empty($item['content']);
            $hasIsRequired = isset($item['isRequired']);
            $hasIsVisible = isset($item['isVisible']);
            
            if (!$hasTitle || !$hasContent || !$hasIsRequired || !$hasIsVisible) {
                $allFieldsPresent = false;
            }
            
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
            echo "<td>" . ($hasTitle ? "<span class='success'>✓</span> " . htmlspecialchars(substr($item['title'] ?? '', 0, 30)) : "<span class='error'>✗ 없음</span>") . "</td>";
            echo "<td>" . ($hasIsVisible ? "<span class='success'>✓ " . ($item['isVisible'] ? '노출' : '비노출') . "</span>" : "<span class='error'>✗ 없음</span>") . "</td>";
            echo "<td>" . ($hasIsRequired ? "<span class='success'>✓ " . ($item['isRequired'] ? '필수' : '선택') . "</span>" : "<span class='error'>✗ 없음</span>") . "</td>";
            echo "<td>" . ($hasContent ? "<span class='success'>✓ " . strlen($item['content'] ?? '') . "자</span>" : "<span class='error'>✗ 없음</span>") . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        if ($allFieldsPresent) {
            echo "<p class='success' style='margin-top: 20px; font-size: 18px;'>✓ 모든 필드가 제대로 저장되어 있습니다!</p>";
        } else {
            echo "<p class='error' style='margin-top: 20px; font-size: 18px;'>✗ 일부 필드가 누락되었습니다.</p>";
        }
        
        echo "<h2>3. 전체 JSON 데이터</h2>";
        echo "<pre style='background: #f3f4f6; padding: 15px; border-radius: 8px; overflow-x: auto;'>";
        echo htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        echo "</pre>";
    } else {
        echo "<p class='error'>JSON 데이터를 파싱할 수 없습니다.</p>";
    }
} else {
    echo "<p class='error'>✗ privacy 설정이 데이터베이스에 없습니다. 관리자 페이지에서 설정을 저장해주세요.</p>";
}

echo "<h2>4. getPrivacySettings() 함수로 조회</h2>";
$settings = getPrivacySettings();
echo "<table>";
echo "<tr><th>항목</th><th>제목</th><th>노출 여부</th><th>선택/필수</th><th>내용 길이</th></tr>";

foreach (['purpose', 'items', 'period', 'thirdParty', 'serviceNotice', 'marketing'] as $key) {
    $item = $settings[$key] ?? [];
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
    echo "<td>" . htmlspecialchars(substr($item['title'] ?? 'N/A', 0, 30)) . "</td>";
    echo "<td>" . (isset($item['isVisible']) ? ($item['isVisible'] ? '노출' : '비노출') : 'N/A') . "</td>";
    echo "<td>" . (isset($item['isRequired']) ? ($item['isRequired'] ? '필수' : '선택') : 'N/A') . "</td>";
    echo "<td>" . strlen($item['content'] ?? '') . "자</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<p><a href='/MVNO/admin/settings/privacy-settings.php'>관리자 페이지로 이동</a></p>";
?>







