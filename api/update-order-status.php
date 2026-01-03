<?php
/**
 * 주문 상태 변경 API
 * 판매자와 관리자가 주문의 진행상황을 변경할 때 사용
 * - 판매자: 자신의 주문만 변경 가능
 * - 관리자: 모든 주문 변경 가능
 */

// Output buffering 시작 (에러 출력 방지)
ob_start();

// 에러 출력 비활성화 (JSON 응답 보호)
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 이전 출력 버퍼 정리
ob_clean();

header('Content-Type: application/json; charset=utf-8');

// 로그인 체크
if (!isLoggedIn()) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    ob_end_flush();
    exit;
}

$currentUser = getCurrentUser();

// 판매자 또는 관리자 로그인 체크
$isAdmin = $currentUser && isAdmin($currentUser['user_id']);
$isSeller = $currentUser && $currentUser['role'] === 'seller';

if (!$currentUser || (!$isAdmin && !$isSeller)) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '판매자 또는 관리자만 접근 가능합니다.'
    ]);
    ob_end_flush();
    exit;
}

// POST 데이터 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'POST 요청만 허용됩니다.'
    ]);
    ob_end_flush();
    exit;
}

// 필수 필드 확인
$applicationId = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
$newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';

// 운영 안전: PII/전체 POST 로깅 금지 (필요 시 최소 정보만)
error_log("Update Order Status called - application_id={$applicationId}, status={$newStatus}");

// 유효한 상태 값 체크
$validStatuses = ['received', 'activating', 'on_hold', 'cancelled', 'activation_completed', 'installation_completed', 'closed'];

if (empty($applicationId) || empty($newStatus)) {
    error_log("Missing required fields - applicationId: " . var_export($applicationId, true) . ", newStatus: " . var_export($newStatus, true));
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '필수 정보가 누락되었습니다.',
        'debug' => [
            'application_id' => $applicationId,
            'status' => $newStatus,
            'post_data' => $_POST
        ]
    ]);
    ob_end_flush();
    exit;
}

if (!in_array($newStatus, $validStatuses)) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '유효하지 않은 상태 값입니다.'
    ]);
    ob_end_flush();
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // ENUM 값 확인 및 검증
    $enumStmt = $pdo->query("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'product_applications' 
        AND COLUMN_NAME = 'application_status'
    ");
    $enumInfo = $enumStmt->fetch(PDO::FETCH_ASSOC);
    $enumType = $enumInfo['COLUMN_TYPE'] ?? '';
    error_log("Current ENUM values: " . $enumType);
    
    // ENUM에서 값 추출
    $enumValues = [];
    if (preg_match("/ENUM\('(.*)'\)/", $enumType, $matches)) {
        $enumValues = explode("','", $matches[1]);
    }
    
    // 요청한 상태 값이 ENUM에 있는지 확인
    if (!empty($enumValues) && !in_array($newStatus, $enumValues)) {
        error_log("Status '$newStatus' not in ENUM. Available values: " . implode(', ', $enumValues));
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => "상태 값 '$newStatus'이(가) 데이터베이스에 없습니다. ENUM 업데이트가 필요합니다.",
            'debug' => [
                'requested_status' => $newStatus,
                'available_enum_values' => $enumValues,
                'enum_type' => $enumType,
                'update_url' => '/MVNO/database/update_application_status_enum.php'
            ]
        ]);
        ob_end_flush();
        exit;
    }
    
    $userId = (string)$currentUser['user_id'];
    error_log("User ID: " . $userId . ", Application ID: " . $applicationId . ", Is Admin: " . ($isAdmin ? 'Yes' : 'No'));
    
    // 주문 정보 확인
    // 관리자는 모든 주문 접근 가능, 판매자는 자신의 주문만 접근 가능
    if ($isAdmin) {
        $stmt = $pdo->prepare("
            SELECT a.id, a.seller_id, a.application_status
            FROM product_applications a
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmt->execute([$applicationId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT a.id, a.seller_id, a.application_status
            FROM product_applications a
            WHERE a.id = ? AND a.seller_id = ?
            LIMIT 1
        ");
        $stmt->execute([$applicationId, $userId]);
    }
    
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Application query result: " . var_export($application, true));
    
    if (!$application) {
        throw new Exception('주문을 찾을 수 없거나 접근 권한이 없습니다. (ID: ' . $applicationId . ')');
    }
    
    $currentStatus = $application['application_status'];
    $statusChanged = ($currentStatus !== $newStatus);
    
    // 상태 업데이트 (상태가 실제로 변경될 때만 status_changed_at 업데이트)
    // 관리자는 모든 주문 수정 가능, 판매자는 자신의 주문만 수정 가능
    if ($statusChanged) {
        if ($isAdmin) {
            $stmt = $pdo->prepare("
                UPDATE product_applications
                SET application_status = :status, 
                    updated_at = NOW(),
                    status_changed_at = NOW()
                WHERE id = :application_id
            ");
            $updateParams = [
                ':status' => $newStatus,
                ':application_id' => $applicationId
            ];
        } else {
            $stmt = $pdo->prepare("
                UPDATE product_applications
                SET application_status = :status, 
                    updated_at = NOW(),
                    status_changed_at = NOW()
                WHERE id = :application_id AND seller_id = :seller_id
            ");
            $updateParams = [
                ':status' => $newStatus,
                ':application_id' => $applicationId,
                ':seller_id' => $userId
            ];
        }
    } else {
        if ($isAdmin) {
            $stmt = $pdo->prepare("
                UPDATE product_applications
                SET application_status = :status, 
                    updated_at = NOW()
                WHERE id = :application_id
            ");
            $updateParams = [
                ':status' => $newStatus,
                ':application_id' => $applicationId
            ];
        } else {
            $stmt = $pdo->prepare("
                UPDATE product_applications
                SET application_status = :status, 
                    updated_at = NOW()
                WHERE id = :application_id AND seller_id = :seller_id
            ");
            $updateParams = [
                ':status' => $newStatus,
                ':application_id' => $applicationId,
                ':seller_id' => $userId
            ];
        }
    }
    
    // PDO 에러 모드 설정 (에러 발생 시 예외 발생)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    try {
        $result = $stmt->execute($updateParams);
    } catch (PDOException $e) {
        error_log("PDO Execute Error: " . $e->getMessage());
        error_log("PDO Error Code: " . $e->getCode());
        error_log("PDO Error Info: " . var_export($stmt->errorInfo(), true));
        
        // ENUM 관련 오류인지 확인
        if (strpos($e->getMessage(), 'enum') !== false || strpos($e->getMessage(), 'ENUM') !== false || $e->getCode() == '1265') {
            throw new Exception('상태 값 "' . $newStatus . '"이(가) 데이터베이스 ENUM에 없습니다. ENUM 업데이트가 필요합니다.');
        }
        throw $e;
    }
    
    $affectedRows = $stmt->rowCount();
    error_log("Update executed - affected rows: " . $affectedRows);
    error_log("Update params - status: $newStatus, application_id: $applicationId, user_id: $userId, is_admin: " . ($isAdmin ? 'Yes' : 'No'));
    
    if ($affectedRows === 0) {
        error_log("No rows affected - applicationId: $applicationId, userId: $userId");
        // PDO 에러 정보 확인
        $errorInfo = $stmt->errorInfo();
        if ($errorInfo[0] !== '00000') {
            error_log("PDO Error Info: " . var_export($errorInfo, true));
            throw new Exception('상태 변경에 실패했습니다: ' . ($errorInfo[2] ?? '알 수 없는 오류'));
        }
        throw new Exception('상태 변경에 실패했습니다. 주문을 찾을 수 없거나 권한이 없습니다.');
    }
    
    // 업데이트 후 실제 저장된 값 확인
    if ($isAdmin) {
        $verifyStmt = $pdo->prepare("
            SELECT application_status 
            FROM product_applications 
            WHERE id = ?
            LIMIT 1
        ");
        $verifyStmt->execute([$applicationId]);
    } else {
        $verifyStmt = $pdo->prepare("
            SELECT application_status 
            FROM product_applications 
            WHERE id = ? AND seller_id = ?
            LIMIT 1
        ");
        $verifyStmt->execute([$applicationId, $userId]);
    }
    $verified = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Verified status after update: " . var_export($verified, true));
    
    if (!$verified || $verified['application_status'] !== $newStatus) {
        error_log("Status mismatch! Expected: $newStatus, Got: " . ($verified['application_status'] ?? 'NULL'));
        throw new Exception('상태 변경 후 검증에 실패했습니다. (예상: ' . $newStatus . ', 실제: ' . ($verified['application_status'] ?? 'NULL') . ')');
    }
    
    echo json_encode([
        'success' => true,
        'message' => '상태가 변경되었습니다.',
        'status' => $newStatus,
        'verified_status' => $verified['application_status'],
        'affected_rows' => $affectedRows
    ]);
    
    // Output buffer 종료 및 출력
    ob_end_flush();
    exit;
    
} catch (PDOException $e) {
    error_log("Update Order Status PDO Error: " . $e->getMessage());
    error_log("PDO Error Info: " . var_export(isset($pdo) ? $pdo->errorInfo() : [], true));
    
    // Output buffer 정리 후 JSON 출력
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '데이터베이스 오류: ' . $e->getMessage(),
        'debug' => isset($pdo) ? $pdo->errorInfo() : []
    ]);
    ob_end_flush();
    exit;
} catch (Exception $e) {
    error_log("Update Order Status Error: " . $e->getMessage());
    
    // Output buffer 정리 후 JSON 출력
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    ob_end_flush();
    exit;
}
