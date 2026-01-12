<?php
/**
 * 관리자용 상품 일괄 상태 변경 API
 * POST /api/admin-product-bulk-update.php
 */

// 에러 출력 방지 (JSON 응답을 위해)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 출력 버퍼링 시작 (에러 방지)
ob_start();

// CORS 헤더 설정 (필요한 경우)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

// OPTIONS 요청 처리 (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// 출력 버퍼 비우기 (이전 출력 제거)
ob_clean();

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'POST 메서드만 허용됩니다.'
    ]);
    exit;
}

// 관리자 인증 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '관리자 권한이 필요합니다.'
    ]);
    exit;
}

// JSON 또는 FormData 데이터 읽기 (웹서버 호환성)
$data = null;
$input = file_get_contents('php://input');

// FormData 요청 확인 (웹서버 호환성)
// $_POST가 비어있지 않으면 FormData로 간주
if (!empty($_POST)) {
    $data = $_POST;
} elseif (!empty($input)) {
    // JSON 요청 처리
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // JSON 파싱 실패 시, multipart/form-data로 시도
        // Content-Type이 multipart/form-data인 경우 php://input이 비어있을 수 있음
        parse_str($input, $parsed);
        if (!empty($parsed)) {
            $data = $parsed;
        } else {
            $data = null;
        }
    }
}

// 디버깅 로그 (항상 기록)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
error_log("[Admin Bulk Update] Content-Type: " . $contentType);
error_log("[Admin Bulk Update] POST data: " . print_r($_POST, true));
error_log("[Admin Bulk Update] Input data: " . substr($input, 0, 500));
error_log("[Admin Bulk Update] Parsed data: " . print_r($data, true));

if (!$data || (!isset($data['product_ids']) && !isset($data['product_id'])) || !isset($data['status'])) {
    ob_clean();
    http_response_code(400);
    $errorMsg = '잘못된 요청입니다. (데이터 파싱 실패)';
    if (!$data) {
        $errorMsg .= ' - 데이터가 없습니다.';
    } elseif (!isset($data['product_ids']) && !isset($data['product_id'])) {
        $errorMsg .= ' - product_ids 필드가 없습니다.';
    } elseif (!isset($data['status'])) {
        $errorMsg .= ' - status 필드가 없습니다.';
    }
    echo json_encode([
        'success' => false,
        'message' => $errorMsg,
        'debug' => [
            'has_post' => !empty($_POST),
            'has_input' => !empty($input),
            'content_type' => $contentType,
            'post_keys' => !empty($_POST) ? array_keys($_POST) : [],
            'data_keys' => $data ? array_keys($data) : []
        ]
    ]);
    exit;
}

// product_ids 처리 (FormData에서 JSON 문자열 또는 쉼표로 구분된 문자열로 올 수 있음)
if (isset($data['product_ids'])) {
    // product_ids가 문자열인 경우 배열로 변환
    if (is_string($data['product_ids'])) {
        // 먼저 JSON으로 파싱 시도
        $decoded = json_decode($data['product_ids'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $data['product_ids'] = $decoded;
        } else {
            // JSON이 아니면 쉼표로 구분된 문자열로 처리
            $data['product_ids'] = array_filter(array_map('trim', explode(',', $data['product_ids'])));
        }
    }
    // 배열이 아닌 경우 배열로 변환
    if (!is_array($data['product_ids'])) {
        $data['product_ids'] = [$data['product_ids']];
    }
}

if (!isset($data['product_ids'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '상품 ID가 필요합니다. (product_ids 필드 없음)'
    ]);
    exit;
}

if (!isset($data['status'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '상태 값이 필요합니다. (status 필드 없음)'
    ]);
    exit;
}

$productIds = $data['product_ids'];
$status = trim($data['status'] ?? '');

// 상태 값 검증 (대소문자 구분 없이)
$validStatuses = ['active', 'inactive'];
$statusLower = strtolower($status);
if (!in_array($statusLower, $validStatuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '유효하지 않은 상태 값입니다. (받은 값: ' . htmlspecialchars($status) . ')'
    ]);
    exit;
}

// 소문자로 정규화
$status = $statusLower;

// 상품 ID 배열 검증
if (!is_array($productIds) || empty($productIds)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '상품 ID가 필요합니다.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '데이터베이스 연결에 실패했습니다.'
        ]);
        exit;
    }
    
    // 상품 ID를 정수로 변환 및 검증
    $validProductIds = [];
    foreach ($productIds as $id) {
        $id = intval($id);
        if ($id > 0) {
            $validProductIds[] = $id;
        }
    }
    
    if (empty($validProductIds)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '유효한 상품 ID가 없습니다.'
        ]);
        exit;
    }
    
    // 플레이스홀더 생성
    $placeholders = implode(',', array_fill(0, count($validProductIds), '?'));
    
    // 판매중으로 변경하려는 경우, 탈퇴 신청/완료 회원의 상품인지 확인
    if ($status === 'active') {
        // 탈퇴 신청 또는 탈퇴 완료한 판매자의 상품 조회
        $checkStmt = $pdo->prepare("
            SELECT p.id, p.seller_id, u.user_id
            FROM products p
            LEFT JOIN users u ON p.seller_id = u.user_id
            LEFT JOIN seller_profiles sp ON u.user_id = sp.user_id
            WHERE p.id IN ($placeholders)
            AND p.status != 'deleted'
            AND (
                -- 탈퇴 신청
                COALESCE(sp.withdrawal_requested, u.withdrawal_requested, 0) = 1
                OR u.approval_status = 'withdrawal_requested'
                -- 탈퇴 완료
                OR COALESCE(sp.withdrawal_completed, u.withdrawal_completed, 0) = 1
                OR u.approval_status = 'withdrawn'
            )
        ");
        $checkStmt->execute($validProductIds);
        $withdrawalProducts = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($withdrawalProducts)) {
            $withdrawalCount = count($withdrawalProducts);
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "탈퇴 신청/완료 회원의 상품 {$withdrawalCount}개는 판매중으로 변경할 수 없습니다."
            ]);
            exit;
        }
    }
    
    // 관리자는 모든 상품의 상태를 변경할 수 있음 (seller_id 체크 없음)
    $stmt = $pdo->prepare("
        UPDATE products 
        SET status = ?, updated_at = NOW()
        WHERE id IN ($placeholders) 
        AND status != 'deleted'
    ");
    
    $params = array_merge([$status], $validProductIds);
    $stmt->execute($params);
    
    $affectedRows = $stmt->rowCount();
    
    if ($affectedRows > 0) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => $affectedRows . '개의 상품이 업데이트되었습니다.',
            'affected_count' => $affectedRows
        ]);
    } else {
        ob_clean();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '업데이트할 상품을 찾을 수 없습니다.'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error updating products: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '상품 상태 변경 중 오류가 발생했습니다.'
    ]);
} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '예기치 않은 오류가 발생했습니다.'
    ]);
}



































