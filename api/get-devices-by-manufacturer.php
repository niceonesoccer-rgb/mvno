<?php
/**
 * 제조사별 단말기 목록 가져오기 API
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: application/json; charset=utf-8');

$manufacturerId = $_GET['manufacturer_id'] ?? '';

if (empty($manufacturerId)) {
    echo json_encode(['success' => false, 'message' => '제조사 ID가 필요합니다.']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 연결에 실패했습니다.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT d.id, d.name, d.storage, d.release_price 
        FROM devices d 
        WHERE d.manufacturer_id = ? AND d.status = 'active' 
        ORDER BY d.name ASC, d.storage ASC
    ");
    $stmt->execute([$manufacturerId]);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 단말기명별로 그룹화
    $groupedDevices = [];
    foreach ($devices as $device) {
        $deviceName = $device['name'];
        if (!isset($groupedDevices[$deviceName])) {
            $groupedDevices[$deviceName] = [];
        }
        $groupedDevices[$deviceName][] = $device;
    }
    
    echo json_encode([
        'success' => true,
        'devices' => $devices,
        'grouped' => $groupedDevices
    ]);
} catch (PDOException $e) {
    error_log("Error fetching devices: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '단말기 목록을 가져오는 중 오류가 발생했습니다.'
    ]);
}

