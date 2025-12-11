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
    // 특정 device_id가 있으면 해당 단말기도 포함 (수정 모드용)
    $includeDeviceId = $_GET['include_device_id'] ?? null;
    
    // devices 테이블에서 기본 단말기 목록 가져오기
    if ($includeDeviceId) {
        // 특정 device_id를 포함하여 모든 active 단말기와 해당 단말기 가져오기
        $stmt = $pdo->prepare("
            SELECT DISTINCT d.id, d.name, d.storage, d.release_price, d.status
            FROM devices d 
            WHERE d.manufacturer_id = ? AND (d.status = 'active' OR d.id = ?)
            ORDER BY d.name ASC, d.storage ASC
        ");
        $stmt->execute([$manufacturerId, $includeDeviceId]);
    } else {
        // 모든 active 단말기 가져오기
        $stmt = $pdo->prepare("
            SELECT DISTINCT d.id, d.name, d.storage, d.release_price, d.status
            FROM devices d 
            WHERE d.manufacturer_id = ? AND d.status = 'active' 
            ORDER BY d.name ASC, d.storage ASC
        ");
        $stmt->execute([$manufacturerId]);
    }
    
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // product_mno_details에서 이미 등록된 단말기 정보도 가져오기
    try {
        // device_id가 있는 경우: devices 테이블과 조인
        $stmtUsed = $pdo->prepare("
            SELECT DISTINCT d.id, d.name, d.storage, d.release_price, d.status
            FROM product_mno_details m
            INNER JOIN devices d ON m.device_id = d.id
            WHERE d.manufacturer_id = ? 
            AND m.device_id IS NOT NULL
        ");
        $stmtUsed->execute([$manufacturerId]);
        $usedDevices = $stmtUsed->fetchAll(PDO::FETCH_ASSOC);
        
        // 기존 devices 배열과 병합 (중복 제거)
        $existingDeviceIds = array_column($devices, 'id');
        foreach ($usedDevices as $used) {
            if (!empty($used['id']) && !in_array($used['id'], $existingDeviceIds)) {
                $devices[] = $used;
            }
        }
        
        // device_name만 있는 경우 (devices 테이블에 없을 수 있는 구버전 데이터)
        // 제조사 정보가 없으므로 device_name으로 devices 테이블과 매칭
        $stmtUsedByName = $pdo->prepare("
            SELECT DISTINCT m.device_name, m.device_capacity, m.device_price
            FROM product_mno_details m
            WHERE m.device_name IS NOT NULL AND m.device_name != ''
            AND EXISTS (
                SELECT 1 FROM devices d 
                WHERE d.name = m.device_name AND d.manufacturer_id = ?
            )
        ");
        $stmtUsedByName->execute([$manufacturerId]);
        $usedDevicesByName = $stmtUsedByName->fetchAll(PDO::FETCH_ASSOC);
        
        // devices 테이블에서 매칭되는 단말기 가져오기
        $existingDeviceNames = array_column($devices, 'name');
        foreach ($usedDevicesByName as $used) {
            if (!empty($used['device_name']) && !in_array($used['device_name'], $existingDeviceNames)) {
                $stmtMatch = $pdo->prepare("
                    SELECT id, name, storage, release_price, status
                    FROM devices
                    WHERE name = ? AND manufacturer_id = ?
                ");
                $stmtMatch->execute([$used['device_name'], $manufacturerId]);
                $matchedDevice = $stmtMatch->fetch(PDO::FETCH_ASSOC);
                if ($matchedDevice && !in_array($matchedDevice['id'], $existingDeviceIds)) {
                    $devices[] = $matchedDevice;
                }
            }
        }
    } catch (PDOException $e) {
        // product_mno_details 테이블이 없거나 오류가 있어도 계속 진행
        error_log("Warning: Could not fetch used devices from product_mno_details: " . $e->getMessage());
    }
    
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
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log("Error fetching devices: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '단말기 목록을 가져오는 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

