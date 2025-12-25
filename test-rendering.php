<?php
/**
 * 실제 렌더링 테스트 스크립트
 * internets.php와 동일한 로직으로 렌더링 테스트
 */

require_once __DIR__ . '/includes/data/privacy-functions.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>실제 렌더링 테스트</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .rendered-item { padding: 10px; margin: 5px 0; border: 1px solid #e5e7eb; border-radius: 4px; }
    .hidden { background: #fee; color: #c00; }
    .visible { background: #efe; color: #060; }
    pre { background: #f9fafb; padding: 15px; border-radius: 4px; overflow-x: auto; }
</style>";

// internets.php와 동일한 로직
$privacySettings = getPrivacySettings();

echo "<div class='section'>";
echo "<h2>1. getPrivacySettings() 반환값 확인</h2>";
echo "<pre>" . htmlspecialchars(json_encode($privacySettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "</pre>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>2. 실제 렌더링 로직 시뮬레이션</h2>";

$agreementItems = [
    'purpose' => ['id' => 'agreePurpose', 'name' => 'agreementPurpose'],
    'items' => ['id' => 'agreeItems', 'name' => 'agreementItems'],
    'period' => ['id' => 'agreePeriod', 'name' => 'agreementPeriod'],
    'thirdParty' => ['id' => 'agreeThirdParty', 'name' => 'agreementThirdParty'],
    'serviceNotice' => ['id' => 'agreeServiceNotice', 'name' => 'service_notice_opt_in'],
    'marketing' => ['id' => 'agreeMarketing', 'name' => 'marketing_opt_in']
];

echo "<h3>렌더링될 항목:</h3>";
echo "<div style='display: flex; flex-direction: column; gap: 10px;'>";

foreach ($agreementItems as $key => $item):
    $setting = $privacySettings[$key] ?? [];
    
    // internets.php와 동일한 로직
    if (array_key_exists('isVisible', $setting)) {
        $isVisible = (bool)$setting['isVisible'];
    } else {
        $isVisible = true;
    }
    
    // 디버깅 정보
    $debugInfo = [
        'key' => $key,
        'has_isVisible' => array_key_exists('isVisible', $setting),
        'raw_value' => $setting['isVisible'] ?? 'NOT SET',
        'raw_type' => isset($setting['isVisible']) ? gettype($setting['isVisible']) : 'NOT SET',
        'calculated_isVisible' => $isVisible,
        'will_render' => $isVisible
    ];
    
    if (!$isVisible) {
        echo "<div class='rendered-item hidden'>";
        echo "<strong>❌ 숨김:</strong> " . htmlspecialchars($key) . "<br>";
        echo "<small>디버깅: " . json_encode($debugInfo, JSON_UNESCAPED_UNICODE) . "</small>";
        echo "</div>";
        continue;
    }
    
    $title = htmlspecialchars($setting['title'] ?? $key);
    $isRequired = $setting['isRequired'] ?? ($key !== 'marketing');
    $requiredText = $isRequired ? '(필수)' : '(선택)';
    
    echo "<div class='rendered-item visible'>";
    echo "<input type='checkbox' id='" . htmlspecialchars($item['id']) . "'> ";
    echo "<label for='" . htmlspecialchars($item['id']) . "'>" . $title . " <span style='color: " . ($isRequired ? '#4f46e5' : '#6b7280') . ";'>" . $requiredText . "</span></label>";
    echo "<br><small>디버깅: " . json_encode($debugInfo, JSON_UNESCAPED_UNICODE) . "</small>";
    echo "</div>";
endforeach;

echo "</div>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>3. purpose 항목 상세 분석</h2>";
$purposeSetting = $privacySettings['purpose'] ?? [];
echo "<pre>";
echo "array_key_exists('isVisible', \$setting): " . (array_key_exists('isVisible', $purposeSetting) ? 'true' : 'false') . "\n";
echo "\$setting['isVisible'] 원본 값: " . var_export($purposeSetting['isVisible'] ?? 'NOT SET', true) . "\n";
echo "타입: " . (isset($purposeSetting['isVisible']) ? gettype($purposeSetting['isVisible']) : 'NOT SET') . "\n";

if (array_key_exists('isVisible', $purposeSetting)) {
    $calculated = (bool)$purposeSetting['isVisible'];
    echo "(bool)\$setting['isVisible']: " . var_export($calculated, true) . "\n";
    echo "계산된 \$isVisible: " . var_export($calculated, true) . "\n";
    echo "렌더링 여부: " . ($calculated ? '❌ 표시됨 (문제!)' : '✅ 숨김 (정상)') . "\n";
} else {
    echo "⚠️ isVisible 키가 없습니다!\n";
}
echo "</pre>";
echo "</div>";

echo "<hr>";
echo "<p><a href='/MVNO/internets/internets.php'>가입 신청 페이지로 이동</a></p>";
?>




