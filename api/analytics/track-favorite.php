<?php
/**
 * 찜 추적 API
 * 실제 DB에 찜 정보 저장 및 favorite_count 업데이트
 */

// 에러 출력 방지 (JSON만 반환)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 출력 버퍼링 시작 (에러 메시지가 출력되지 않도록)
ob_start();

// JSON 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// 응답 함수 (출력 버퍼 정리 후 JSON 반환)
function sendJsonResponse($data, $statusCode = 200) {
    // 출력 버퍼 정리
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 전체를 try-catch로 감싸서 모든 에러를 잡음
try {
    // 파일 로드
    $analyticsPath = __DIR__ . '/../../includes/data/analytics-functions.php';
    $authPath = __DIR__ . '/../../includes/data/auth-functions.php';
    $productPath = __DIR__ . '/../../includes/data/product-functions.php';
    
    if (!file_exists($analyticsPath)) {
        throw new Exception("analytics-functions.php not found: $analyticsPath");
    }
    if (!file_exists($authPath)) {
        throw new Exception("auth-functions.php not found: $authPath");
    }
    if (!file_exists($productPath)) {
        throw new Exception("product-functions.php not found: $productPath");
    }
    
    // require_once는 에러 발생 시 즉시 중단되므로 try-catch로 감쌀 수 없음
    // 대신 출력 버퍼를 확인하여 에러 메시지가 있는지 체크
    require_once $analyticsPath;
    require_once $authPath;
    require_once $productPath;
    
    // 출력 버퍼 확인 (에러 메시지가 있으면 제거)
    $buffer = ob_get_contents();
    if (!empty($buffer)) {
        // PHP 에러나 경고가 출력 버퍼에 있는 경우
        error_log("track-favorite.php: Output buffer contains: " . substr($buffer, 0, 500));
        ob_clean(); // 버퍼만 비우고 계속 진행
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(['success' => false, 'message' => '잘못된 요청입니다.'], 400);
    }

    // 로그인 체크
    if (!function_exists('isLoggedIn')) {
        throw new Exception('isLoggedIn function not found');
    }
    
    if (!isLoggedIn()) {
        sendJsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
    }

    $productType = $_POST['product_type'] ?? '';
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $action = $_POST['action'] ?? 'add'; // 'add' or 'remove'
    $sellerId = $_POST['seller_id'] ?? null;

    // 타입 매핑: JavaScript에서 사용하는 타입을 DB 타입으로 변환
    // 'phone' -> 'mno', 'plan' -> 'mvno'
    $typeMapping = [
        'phone' => 'mno',
        'plan' => 'mvno',
        'mvno' => 'mvno',
        'mno' => 'mno',
        'internet' => 'internet'
    ];

    if (!empty($productType) && isset($typeMapping[$productType])) {
        $productType = $typeMapping[$productType];
    }

    // 인터넷 상품은 찜 불가
    if ($productType === 'internet') {
        sendJsonResponse(['success' => false, 'message' => '인터넷 상품은 찜할 수 없습니다.'], 400);
    }

    if (empty($productType) || empty($productId)) {
        sendJsonResponse(['success' => false, 'message' => '필수 파라미터가 없습니다.'], 400);
    }

    // 사용자 정보 가져오기
    if (!function_exists('getCurrentUser')) {
        throw new Exception('getCurrentUser function not found');
    }
    
    try {
        $currentUser = getCurrentUser();
        $userId = $currentUser['user_id'] ?? null;
    } catch (Exception $e) {
        error_log("track-favorite.php: getCurrentUser error - " . $e->getMessage());
        throw new Exception('사용자 정보를 가져오는 중 오류가 발생했습니다.');
    } catch (Error $e) {
        error_log("track-favorite.php: getCurrentUser fatal error - " . $e->getMessage());
        throw new Exception('사용자 정보를 가져오는 중 오류가 발생했습니다.');
    }

    if (!$userId) {
        sendJsonResponse(['success' => false, 'message' => '로그인 정보를 확인할 수 없습니다.'], 401);
    }

    // 실제 DB에 찜 정보 저장/삭제
    if (!function_exists('toggleProductFavorite')) {
        throw new Exception('toggleProductFavorite function not found');
    }
    
    $isFavorite = ($action === 'add');
    
    try {
        $result = toggleProductFavorite($productId, $userId, $productType, $isFavorite);
    } catch (Exception $e) {
        error_log("track-favorite.php: toggleProductFavorite exception - " . $e->getMessage());
        throw new Exception('찜 처리 중 오류가 발생했습니다: ' . $e->getMessage());
    } catch (Error $e) {
        error_log("track-favorite.php: toggleProductFavorite fatal error - " . $e->getMessage());
        throw new Exception('찜 처리 중 오류가 발생했습니다: ' . $e->getMessage());
    }

    if ($result) {
        // 분석 추적도 함께 수행 (에러 발생해도 계속 진행)
        try {
            if (function_exists('trackFavorite')) {
                trackFavorite($productType, $productId, $sellerId, $action);
            }
        } catch (Exception $e) {
            error_log("track-favorite.php: trackFavorite error - " . $e->getMessage());
            // 분석 추적 실패해도 찜 처리는 성공으로 간주
        } catch (Error $e) {
            error_log("track-favorite.php: trackFavorite fatal error - " . $e->getMessage());
            // 분석 추적 실패해도 찜 처리는 성공으로 간주
        }
        
        // 업데이트된 favorite_count 가져오기
        $favoriteCount = 0;
        try {
            if (function_exists('getDBConnection')) {
                $pdo = getDBConnection();
                if ($pdo) {
                    $countStmt = $pdo->prepare("SELECT favorite_count FROM products WHERE id = :product_id");
                    $countStmt->execute([':product_id' => $productId]);
                    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
                    $favoriteCount = (int)($countResult['favorite_count'] ?? 0);
                }
            }
        } catch (Exception $e) {
            error_log("track-favorite.php: Get favorite_count error - " . $e->getMessage());
            // favorite_count 조회 실패해도 찜 처리는 성공으로 간주
        } catch (Error $e) {
            error_log("track-favorite.php: Get favorite_count fatal error - " . $e->getMessage());
            // favorite_count 조회 실패해도 찜 처리는 성공으로 간주
        }
        
        sendJsonResponse([
            'success' => true,
            'favorite_count' => $favoriteCount,
            'action' => $action
        ]);
    } else {
        error_log("Failed to toggle favorite: product_id=$productId, user_id=$userId, product_type=$productType, action=$action");
        sendJsonResponse([
            'success' => false,
            'message' => '찜 처리에 실패했습니다.'
        ], 500);
    }
    
} catch (Exception $e) {
    // 출력 버퍼 정리 후 에러 응답
    error_log("track-favorite.php: Exception - " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Trace: " . $e->getTraceAsString());
    sendJsonResponse([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.',
        'error' => 'Exception occurred'
    ], 500);
} catch (Error $e) {
    // 출력 버퍼 정리 후 에러 응답
    error_log("track-favorite.php: Fatal Error - " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Trace: " . $e->getTraceAsString());
    sendJsonResponse([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.',
        'error' => 'Fatal error occurred'
    ], 500);
}


















