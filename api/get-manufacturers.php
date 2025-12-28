<?php
/**
 * 제조사 목록 가져오기 API
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 연결에 실패했습니다.']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, name, name_en, display_order FROM device_manufacturers WHERE status = 'active' ORDER BY display_order ASC, name ASC");
    $manufacturers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'manufacturers' => $manufacturers
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log("Error fetching manufacturers: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '제조사 목록을 가져오는 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

