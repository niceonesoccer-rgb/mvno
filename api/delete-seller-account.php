<?php
/**
 * 판매자 계정 삭제 API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '인증이 필요합니다.']);
    exit;
}

// 승인불가 상태인 경우에만 삭제 가능
$approvalStatus = $currentUser['approval_status'] ?? 'pending';
if ($approvalStatus !== 'rejected') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '승인불가 상태인 경우에만 계정을 삭제할 수 있습니다.']);
    exit;
}

$userId = $currentUser['user_id'];

// 판매자 데이터 파일에서 삭제
$file = getSellersFilePath();
if (!file_exists($file)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '데이터 파일을 찾을 수 없습니다.']);
    exit;
}

$data = json_decode(file_get_contents($file), true) ?: ['sellers' => []];
$sellerFound = false;

foreach ($data['sellers'] as $key => $seller) {
    if ($seller['user_id'] === $userId) {
        $sellerFound = true;
        
        // 업로드된 파일 삭제 (사업자등록증 등)
        if (isset($seller['business_license_image']) && !empty($seller['business_license_image'])) {
            $imagePath = $_SERVER['DOCUMENT_ROOT'] . $seller['business_license_image'];
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }
        
        // 기타 첨부파일 삭제
        if (isset($seller['other_documents']) && is_array($seller['other_documents'])) {
            foreach ($seller['other_documents'] as $doc) {
                if (isset($doc['url']) && !empty($doc['url'])) {
                    $docPath = $_SERVER['DOCUMENT_ROOT'] . $doc['url'];
                    if (file_exists($docPath)) {
                        @unlink($docPath);
                    }
                }
            }
        }
        
        // 판매자 정보 삭제
        unset($data['sellers'][$key]);
        break;
    }
}

if (!$sellerFound) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => '판매자 정보를 찾을 수 없습니다.']);
    exit;
}

// 배열 인덱스 재정렬
$data['sellers'] = array_values($data['sellers']);

// 파일에 저장
if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '데이터 저장에 실패했습니다.']);
    exit;
}

// 세션 삭제
logoutUser();

echo json_encode(['success' => true, 'message' => '계정이 성공적으로 삭제되었습니다.']);
