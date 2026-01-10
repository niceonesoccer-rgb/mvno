<?php
/**
 * 판매자 전용 공지사항 API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/notice-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/path-config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            handleCreate();
            break;
            
        case 'update':
            handleUpdate();
            break;
            
        case 'delete':
            handleDelete();
            break;
            
        case 'delete_image':
            handleDeleteImage();
            break;
            
        case 'get':
            handleGet();
            break;
            
        case 'list':
            handleList();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
            break;
    }
} catch (Exception $e) {
    error_log('seller-notice-api error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '서버 오류가 발생했습니다.']);
}

function handleCreate() {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $banner_type = $_POST['banner_type'] ?? 'text';
    $link_url = trim($_POST['link_url'] ?? '');
    $show_on_main = isset($_POST['show_on_main']) && $_POST['show_on_main'] == '1';
    $start_at = !empty($_POST['start_at']) ? $_POST['start_at'] : null;
    $end_at = !empty($_POST['end_at']) ? $_POST['end_at'] : null;
    
    // 유효성 검사
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => '제목을 입력해주세요.']);
        return;
    }
    
    if (!in_array($banner_type, ['text', 'image', 'both'])) {
        $banner_type = 'text';
    }
    
    // 이미지 업로드 처리
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_url = uploadSellerNoticeImage($_FILES['image']);
        if (!$image_url) {
            echo json_encode(['success' => false, 'message' => '이미지 업로드에 실패했습니다.']);
            return;
        }
    }
    
    // 배너 타입에 따른 필수 항목 체크
    if ($banner_type === 'image' && empty($image_url)) {
        echo json_encode(['success' => false, 'message' => '이미지 배너는 이미지가 필수입니다.']);
        return;
    }
    
    if ($banner_type === 'both' && empty($image_url)) {
        echo json_encode(['success' => false, 'message' => '텍스트+이미지 배너는 이미지가 필수입니다.']);
        return;
    }
    
    // 메인배너는 이미지 없이도 등록 가능 (이미지 필수 조건 제거)
    
    // 기간 유효성 검사
    if ($start_at && $end_at && strtotime($start_at) > strtotime($end_at)) {
        echo json_encode(['success' => false, 'message' => '시작일은 종료일보다 이전이어야 합니다.']);
        return;
    }
    
    $result = createSellerNotice($title, $content, $banner_type, $image_url, $link_url, $show_on_main, $start_at, $end_at);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => '공지사항이 생성되었습니다.',
            'data' => $result
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '공지사항 생성에 실패했습니다.']);
    }
}

function handleUpdate() {
    $id = trim($_POST['id'] ?? '');
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => '공지사항 ID가 필요합니다.']);
        return;
    }
    
    $notice = getNoticeById($id);
    if (!$notice) {
        echo json_encode(['success' => false, 'message' => '공지사항을 찾을 수 없습니다.']);
        return;
    }
    
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $banner_type = $_POST['banner_type'] ?? 'text';
    $link_url = trim($_POST['link_url'] ?? '');
    $show_on_main = isset($_POST['show_on_main']) && $_POST['show_on_main'] == '1';
    $start_at = !empty($_POST['start_at']) ? $_POST['start_at'] : null;
    $end_at = !empty($_POST['end_at']) ? $_POST['end_at'] : null;
    
    // 유효성 검사
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => '제목을 입력해주세요.']);
        return;
    }
    
    if (!in_array($banner_type, ['text', 'image', 'both'])) {
        $banner_type = 'text';
    }
    
    // 이미지 업로드 처리
    $image_url = $notice['image_url'] ?? null; // 기본값은 기존 이미지
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // 새 이미지 업로드
        $new_image_url = uploadSellerNoticeImage($_FILES['image']);
        if ($new_image_url) {
            // 기존 이미지 삭제
            if ($image_url) {
                deleteNoticeImageFile($image_url);
            }
            $image_url = $new_image_url;
        }
    }
    
    // 배너 타입에 따른 필수 항목 체크
    if ($banner_type === 'image' && empty($image_url)) {
        echo json_encode(['success' => false, 'message' => '이미지 배너는 이미지가 필수입니다.']);
        return;
    }
    
    if ($banner_type === 'both' && empty($image_url)) {
        echo json_encode(['success' => false, 'message' => '텍스트+이미지 배너는 이미지가 필수입니다.']);
        return;
    }
    
    // 메인배너는 이미지 없이도 등록 가능 (이미지 필수 조건 제거)
    
    // 기간 유효성 검사
    if ($start_at && $end_at && strtotime($start_at) > strtotime($end_at)) {
        echo json_encode(['success' => false, 'message' => '시작일은 종료일보다 이전이어야 합니다.']);
        return;
    }
    
    $result = updateSellerNotice($id, $title, $content, $banner_type, $image_url, $link_url, $show_on_main, $start_at, $end_at);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => '공지사항이 수정되었습니다.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '공지사항 수정에 실패했습니다.']);
    }
}

function handleDelete() {
    $id = trim($_POST['id'] ?? '');
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => '공지사항 ID가 필요합니다.']);
        return;
    }
    
    $notice = getNoticeById($id);
    if (!$notice) {
        echo json_encode(['success' => false, 'message' => '공지사항을 찾을 수 없습니다.']);
        return;
    }
    
    // 이미지 파일 삭제 (deleteNotice 함수 내부에서 처리됨)
    $result = deleteNotice($id);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => '공지사항이 삭제되었습니다.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '공지사항 삭제에 실패했습니다.']);
    }
}

function handleDeleteImage() {
    $id = trim($_POST['id'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    
    if (empty($id) || empty($image_url)) {
        echo json_encode(['success' => false, 'message' => '필수 파라미터가 없습니다.']);
        return;
    }
    
    $notice = getNoticeById($id);
    if (!$notice) {
        echo json_encode(['success' => false, 'message' => '공지사항을 찾을 수 없습니다.']);
        return;
    }
    
    // 이미지 파일 삭제
    if (deleteNoticeImageFile($image_url)) {
        // DB에서 이미지 URL 제거
        $pdo = getDBConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE notices SET image_url = NULL WHERE id = :id");
            $stmt->execute([':id' => $id]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => '이미지가 삭제되었습니다.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '이미지 삭제에 실패했습니다.']);
    }
}

function handleGet() {
    $id = $_GET['id'] ?? '';
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => '공지사항 ID가 필요합니다.']);
        return;
    }
    
    $notice = getNoticeById($id);
    
    if ($notice && ($notice['target_audience'] ?? 'all') === 'seller') {
        echo json_encode([
            'success' => true,
            'data' => $notice
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '공지사항을 찾을 수 없습니다.']);
    }
}

function handleList() {
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = intval($_GET['per_page'] ?? 20);
    $offset = ($page - 1) * $perPage;
    
    $notices = getSellerNoticesForAdmin($perPage, $offset);
    $totalCount = getSellerNoticesCount();
    
    echo json_encode([
        'success' => true,
        'data' => $notices,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $totalCount,
            'total_pages' => ceil($totalCount / $perPage)
        ]
    ]);
}

