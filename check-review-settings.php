<?php
/**
 * 리뷰 설정 DB 값 확인 스크립트
 */
require_once __DIR__ . '/includes/data/db-config.php';

$pdo = getDBConnection();
if (!$pdo) {
    die('DB 연결 실패');
}

echo "<h2>리뷰 작성 권한 설정 DB 값 확인</h2>";

// DB에서 설정 읽기
$stmt = $pdo->prepare("SELECT setting_key, setting_value, setting_type, description, updated_at FROM system_settings WHERE setting_key = 'review_allowed_statuses' LIMIT 1");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo "<h3>DB에 저장된 값:</h3>";
    echo "<pre>";
    echo "setting_key: " . htmlspecialchars($row['setting_key']) . "\n";
    echo "setting_value: " . htmlspecialchars($row['setting_value']) . "\n";
    echo "setting_type: " . htmlspecialchars($row['setting_type']) . "\n";
    echo "description: " . htmlspecialchars($row['description'] ?? '') . "\n";
    echo "updated_at: " . htmlspecialchars($row['updated_at']) . "\n";
    echo "</pre>";
    
    $decoded = json_decode($row['setting_value'], true);
    if (is_array($decoded)) {
        echo "<h3>JSON 디코딩 결과:</h3>";
        echo "<pre>";
        print_r($decoded);
        echo "</pre>";
        
        echo "<h3>체크박스 상태:</h3>";
        $statusOptions = [
            'received' => '접수',
            'activating' => '개통중',
            'on_hold' => '보류',
            'cancelled' => '취소',
            'activation_completed' => '개통완료',
            'installation_completed' => '설치완료',
            'closed' => '종료'
        ];
        
        echo "<ul>";
        foreach ($statusOptions as $statusValue => $statusLabel) {
            $checked = in_array($statusValue, $decoded) ? '✓ 체크됨' : '✗ 체크 안됨';
            echo "<li>{$statusLabel} ({$statusValue}): <strong>{$checked}</strong></li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>JSON 파싱 실패!</p>";
    }
} else {
    echo "<p style='color: orange;'>DB에 설정이 없습니다. 기본값이 사용됩니다.</p>";
    echo "<h3>기본값 (하드코딩):</h3>";
    echo "<pre>";
    print_r(['activation_completed', 'installation_completed', 'closed']);
    echo "</pre>";
}

echo "<hr>";
echo "<h3>canWriteReview 함수의 기본값:</h3>";
echo "<pre>";
echo "['activation_completed', 'installation_completed', 'closed']\n";
echo "</pre>";
?>
