<?php
/**
 * 단말기 관리 API
 * 제조사 및 단말기 CRUD 작업 처리
 */

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    header('Location: /MVNO/auth/login.php');
    exit;
}

// 데이터베이스 연결
$pdo = getDBConnection();
if (!$pdo) {
    header('Location: /MVNO/admin/settings/device-settings.php?error=' . urlencode('데이터베이스 연결에 실패했습니다.'));
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add_manufacturer':
            $name = trim($_POST['name'] ?? '');
            $nameEn = trim($_POST['name_en'] ?? '');
            $displayOrder = (int)($_POST['display_order'] ?? 0);
            $status = $_POST['status'] ?? 'active';
            
            if (empty($name)) {
                throw new Exception('제조사명을 입력해주세요.');
            }
            
            // 중복 체크
            $stmt = $pdo->prepare("SELECT id FROM device_manufacturers WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                throw new Exception('이미 존재하는 제조사명입니다.');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO device_manufacturers (name, name_en, display_order, status) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $nameEn ?: null, $displayOrder, $status]);
            
            header('Location: /MVNO/admin/settings/device-settings.php?success=manufacturer_added');
            exit;
            
        case 'update_manufacturer':
            $id = (int)($_POST['manufacturer_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $nameEn = trim($_POST['name_en'] ?? '');
            $displayOrder = (int)($_POST['display_order'] ?? 0);
            $status = $_POST['status'] ?? 'active';
            
            if (empty($id) || empty($name)) {
                throw new Exception('필수 정보가 누락되었습니다.');
            }
            
            // 중복 체크 (자기 자신 제외)
            $stmt = $pdo->prepare("SELECT id FROM device_manufacturers WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->fetch()) {
                throw new Exception('이미 존재하는 제조사명입니다.');
            }
            
            $stmt = $pdo->prepare("
                UPDATE device_manufacturers 
                SET name = ?, name_en = ?, display_order = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $nameEn ?: null, $displayOrder, $status, $id]);
            
            header('Location: /MVNO/admin/settings/device-settings.php?success=manufacturer_updated');
            exit;
            
        case 'update_manufacturer_order':
            $ordersJson = $_POST['orders'] ?? '[]';
            $orders = json_decode($ordersJson, true);
            
            if (!is_array($orders)) {
                throw new Exception('잘못된 순서 데이터입니다.');
            }
            
            try {
                $pdo->beginTransaction();
                
                foreach ($orders as $order) {
                    $id = (int)($order['id'] ?? 0);
                    $displayOrder = (int)($order['order'] ?? 0);
                    
                    if ($id > 0) {
                        $stmt = $pdo->prepare("UPDATE device_manufacturers SET display_order = ? WHERE id = ?");
                        $stmt->execute([$displayOrder, $id]);
                    }
                }
                
                $pdo->commit();
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => '순서가 업데이트되었습니다.']);
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
            
        case 'delete_manufacturer':
            $id = (int)($_POST['manufacturer_id'] ?? 0);
            
            if (empty($id)) {
                throw new Exception('제조사 ID가 없습니다.');
            }
            
            // 연결된 단말기 확인
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE manufacturer_id = ?");
            $stmt->execute([$id]);
            $deviceCount = $stmt->fetchColumn();
            
            if ($deviceCount > 0) {
                throw new Exception('연결된 단말기가 있어 삭제할 수 없습니다. 먼저 단말기를 삭제하거나 다른 제조사로 변경해주세요.');
            }
            
            $stmt = $pdo->prepare("DELETE FROM device_manufacturers WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Location: /MVNO/admin/settings/device-settings.php?success=manufacturer_deleted');
            exit;
            
        case 'add_device':
            $manufacturerId = (int)($_POST['manufacturer_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $storage = trim($_POST['storage'] ?? '');
            $releasePrice = !empty($_POST['release_price']) ? (float)$_POST['release_price'] : null;
            $colorValues = trim($_POST['color_values'] ?? '');
            $modelCode = trim($_POST['model_code'] ?? '');
            $releaseDate = !empty($_POST['release_date']) ? $_POST['release_date'] : null;
            $status = $_POST['status'] ?? 'active';
            
            if (empty($manufacturerId) || empty($name)) {
                throw new Exception('제조사와 단말기명을 입력해주세요.');
            }
            
            // 제조사 존재 확인
            $stmt = $pdo->prepare("SELECT id FROM device_manufacturers WHERE id = ?");
            $stmt->execute([$manufacturerId]);
            if (!$stmt->fetch()) {
                throw new Exception('존재하지 않는 제조사입니다.');
            }
            
            // color_values JSON 유효성 검사 및 color 필드 생성
            $colorValuesJson = null;
            $colorText = null;
            
            if (!empty($colorValues)) {
                $decoded = json_decode($colorValues, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $colorValuesJson = $colorValues;
                    // color 필드에 색상명만 쉼표로 구분하여 저장
                    $colorNames = array_map(function($item) {
                        return $item['name'] ?? '';
                    }, $decoded);
                    $colorText = implode(', ', array_filter($colorNames));
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO devices (manufacturer_id, name, storage, release_price, color, color_values, model_code, release_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $manufacturerId, 
                $name, 
                $storage ?: null, 
                $releasePrice, 
                $colorText,
                $colorValuesJson,
                $modelCode ?: null, 
                $releaseDate, 
                $status
            ]);
            
            header('Location: /MVNO/admin/settings/device-settings.php?success=device_added');
            exit;
            
        case 'update_device':
            $id = (int)($_POST['device_id'] ?? 0);
            $manufacturerId = (int)($_POST['manufacturer_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $storage = trim($_POST['storage'] ?? '');
            $releasePrice = !empty($_POST['release_price']) ? (float)$_POST['release_price'] : null;
            $colorValues = trim($_POST['color_values'] ?? '');
            $modelCode = trim($_POST['model_code'] ?? '');
            $releaseDate = !empty($_POST['release_date']) ? $_POST['release_date'] : null;
            $status = $_POST['status'] ?? 'active';
            
            if (empty($id) || empty($manufacturerId) || empty($name)) {
                throw new Exception('필수 정보가 누락되었습니다.');
            }
            
            // 제조사 존재 확인
            $stmt = $pdo->prepare("SELECT id FROM device_manufacturers WHERE id = ?");
            $stmt->execute([$manufacturerId]);
            if (!$stmt->fetch()) {
                throw new Exception('존재하지 않는 제조사입니다.');
            }
            
            // color_values JSON 유효성 검사 및 color 필드 생성
            $colorValuesJson = null;
            $colorText = null;
            
            if (!empty($colorValues)) {
                $decoded = json_decode($colorValues, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $colorValuesJson = $colorValues;
                    // color 필드에 색상명만 쉼표로 구분하여 저장
                    $colorNames = array_map(function($item) {
                        return $item['name'] ?? '';
                    }, $decoded);
                    $colorText = implode(', ', array_filter($colorNames));
                }
            }
            
            $stmt = $pdo->prepare("
                UPDATE devices 
                SET manufacturer_id = ?, name = ?, storage = ?, release_price = ?, color = ?, color_values = ?,
                    model_code = ?, release_date = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $manufacturerId, 
                $name, 
                $storage ?: null, 
                $releasePrice, 
                $colorText,
                $colorValuesJson,
                $modelCode ?: null, 
                $releaseDate, 
                $status,
                $id
            ]);
            
            header('Location: /MVNO/admin/settings/device-settings.php?success=device_updated');
            exit;
            
        case 'delete_device':
            $id = (int)($_POST['device_id'] ?? 0);
            
            if (empty($id)) {
                throw new Exception('단말기 ID가 없습니다.');
            }
            
            $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Location: /MVNO/admin/settings/device-settings.php?success=device_deleted');
            exit;
            
        default:
            throw new Exception('잘못된 요청입니다.');
    }
} catch (Exception $e) {
    header('Location: /MVNO/admin/settings/device-settings.php?error=' . urlencode($e->getMessage()));
    exit;
}

