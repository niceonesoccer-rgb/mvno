<?php
/**
 * device_id로 단말기 정보 가져오기 API
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: application/json; charset=utf-8');

$deviceId = $_GET['device_id'] ?? '';

if (empty($deviceId)) {
    echo json_encode(['success' => false, 'message' => '단말기 ID가 필요합니다.']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 연결에 실패했습니다.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT d.*, m.id as manufacturer_id, m.name as manufacturer_name
        FROM devices d
        LEFT JOIN device_manufacturers m ON d.manufacturer_id = m.id
        WHERE d.id = ? AND d.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$deviceId]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($device) {
        // 색상 정보 파싱
        $colors = [];
        if (!empty($device['color_values'])) {
            $colorValues = json_decode($device['color_values'], true);
            if (is_array($colorValues)) {
                $colors = $colorValues;
            }
        } elseif (!empty($device['color'])) {
            // color 필드가 쉼표로 구분된 문자열인 경우
            $colorNames = array_map('trim', explode(',', $device['color']));
            $colors = array_map(function($name) {
                return ['name' => $name, 'value' => ''];
            }, $colorNames);
        }
        
        echo json_encode([
            'success' => true,
            'device_id' => $device['id'],
            'manufacturer_id' => $device['manufacturer_id'],
            'manufacturer_name' => $device['manufacturer_name'],
            'device_name' => $device['name'],
            'device_storage' => $device['storage'],
            'device_price' => $device['release_price'],
            'colors' => $colors
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '단말기를 찾을 수 없습니다.'
        ]);
    }
} catch (PDOException $e) {
    error_log("Error fetching device info: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '단말기 정보를 가져오는 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}

