<?php
/**
 * 공지사항 관리 페이지
 * 경로: /MVNO/admin/content/notice-manage.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/notice-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdmin()) {
    header('Location: /MVNO/admin/');
    exit;
}

$error = '';
$success = '';
$editNotice = null;
$editId = $_GET['edit'] ?? '';

// 페이지네이션 설정 (초기 설정)
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 10);
if (!in_array($perPage, [10, 20, 50, 100])) {
    $perPage = 10;
}

// 공지사항 등록
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $show_on_main = isset($_POST['show_on_main']) ? (bool)$_POST['show_on_main'] : false;
    $link_url = trim($_POST['link_url'] ?? '');
    $start_at = !empty($_POST['start_at']) ? $_POST['start_at'] : null;
    $end_at = !empty($_POST['end_at']) ? $_POST['end_at'] : null;
    
    // 이미지 업로드 처리
    $image_url = null;
    if (isset($_FILES['notice_image']) && $_FILES['notice_image']['error'] === UPLOAD_ERR_OK) {
        $image_url = uploadNoticeImage($_FILES['notice_image']);
        if (!$image_url) {
            $error = '이미지 업로드에 실패했습니다. 지원 형식: JPG, PNG, GIF, WEBP';
        }
    }
    
    // 기간 유효성 검사
    if ($start_at && $end_at && strtotime($start_at) > strtotime($end_at)) {
        $error = '메인공지 시작일은 종료일보다 이전이어야 합니다.';
    }
    
    if (!$error && empty($title)) {
        $error = '제목을 입력해주세요.';
    }
    
    if (!$error) {
        $result = createNotice($title, $content, $show_on_main, $image_url, $link_url, $start_at, $end_at);
        if ($result) {
            header('Location: /MVNO/admin/content/notice-manage.php?success=created&page=' . $page . '&per_page=' . $perPage);
            exit;
        } else {
            $error = '공지사항 등록에 실패했습니다.';
        }
    }
}

// 공지사항 수정
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = trim($_POST['id'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $show_on_main = isset($_POST['show_on_main']) ? (bool)$_POST['show_on_main'] : false;
    $link_url = trim($_POST['link_url'] ?? '');
    $start_at = !empty($_POST['start_at']) ? $_POST['start_at'] : null;
    $end_at = !empty($_POST['end_at']) ? $_POST['end_at'] : null;
    
    // 기존 공지사항 정보 가져오기
    $existingNotice = getNoticeById($id);
    $old_image_url = $existingNotice['image_url'] ?? null;
    $image_url = $old_image_url;
    
    // 새 이미지 업로드 처리
    if (isset($_FILES['notice_image']) && $_FILES['notice_image']['error'] === UPLOAD_ERR_OK) {
        $new_image_url = uploadNoticeImage($_FILES['notice_image']);
        if ($new_image_url) {
            // 기존 이미지 파일 삭제 (새 이미지와 다를 경우에만)
            if ($old_image_url && $old_image_url !== $new_image_url && file_exists(__DIR__ . '/../..' . $old_image_url)) {
                @unlink(__DIR__ . '/../..' . $old_image_url);
            }
            $image_url = $new_image_url;
        } else {
            $error = '이미지 업로드에 실패했습니다. 지원 형식: JPG, PNG, GIF, WEBP';
        }
    }
    
    // 기간 유효성 검사
    if (!$error && $start_at && $end_at && strtotime($start_at) > strtotime($end_at)) {
        $error = '메인공지 시작일은 종료일보다 이전이어야 합니다.';
    }
    
    if (!$error && empty($id)) {
        $error = '공지사항 ID가 없습니다.';
    } elseif (!$error && empty($title)) {
        $error = '제목을 입력해주세요.';
    }
    
    if (!$error) {
        // DB 업데이트 전에 삭제할 이미지 URL 저장
        $image_to_delete = ($old_image_url && $old_image_url !== $image_url) ? $old_image_url : null;
        
        if (updateNotice($id, $title, $content, $show_on_main, $image_url, $link_url, $start_at, $end_at)) {
            // DB 업데이트 성공 후 기존 이미지 파일 삭제
            if ($image_to_delete && file_exists(__DIR__ . '/../..' . $image_to_delete)) {
                @unlink(__DIR__ . '/../..' . $image_to_delete);
            }
            header('Location: /MVNO/admin/content/notice-manage.php?success=updated&page=' . $page . '&per_page=' . $perPage);
            exit;
        } else {
            $error = '공지사항 수정에 실패했습니다.';
        }
    }
}

// 공지사항 삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = trim($_POST['id'] ?? '');
    if (!empty($id) && deleteNotice($id)) {
        header('Location: /MVNO/admin/content/notice-manage.php?success=deleted');
        exit;
    } elseif (!empty($id)) {
        $error = '공지사항 삭제에 실패했습니다.';
    }
}

// 성공 메시지 처리
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'created') {
        $success = '공지사항이 등록되었습니다.';
    } elseif ($_GET['success'] === 'updated') {
        $success = '공지사항이 수정되었습니다.';
    } elseif ($_GET['success'] === 'deleted') {
        $success = '공지사항이 삭제되었습니다.';
    }
}

// 수정할 공지사항 가져오기
if (!empty($editId)) {
    $editNotice = getNoticeById($editId);
    if (!$editNotice) {
        $error = '공지사항을 찾을 수 없습니다.';
        $editId = '';
    }
}

// 공지사항 목록 가져오기 (페이지네이션 적용)
$allNotices = getAllNoticesForAdmin();
$totalNotices = count($allNotices);
$totalPages = ceil($totalNotices / $perPage);
$offset = ($page - 1) * $perPage;
$notices = array_slice($allNotices, $offset, $perPage);

$currentPage = 'notice-manage.php';
include '../includes/admin-header.php';
?>

<style>
    .admin-content { 
        padding: 32px; 
        max-width: 65%;
        margin: 0 auto;
    }
    .page-header { margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; }
    .page-header h1 { font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
    .card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; margin-bottom: 24px; }
    .card-title { font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; }
    .form-group input[type="text"], 
    .form-group input[type="url"],
    .form-group input[type="file"],
    .form-group textarea {
        width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px;
        font-size: 15px; transition: border-color 0.2s; box-sizing: border-box; font-family: inherit;
    }
    .form-group textarea { min-height: 200px; resize: vertical; }
    .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
    .form-group input[type="file"] { padding: 8px; }
    .form-group .checkbox-group { display: flex; align-items: center; gap: 8px; }
    .form-group input[type="checkbox"] { width: auto; margin: 0; }
    .help { font-size: 13px; color: #6b7280; margin-top: 6px; }
    .btn { padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; text-decoration: none; display: inline-block; }
    .btn-primary { background: #6366f1; color: white; }
    .btn-primary:hover { background: #4f46e5; }
    .btn-secondary { background: #6b7280; color: white; }
    .btn-secondary:hover { background: #4b5563; }
    .btn-danger { background: #ef4444; color: white; }
    .btn-danger:hover { background: #dc2626; }
    .btn-sm { padding: 8px 16px; font-size: 14px; }
    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .notice-list { margin-top: 24px; }
    .notice-item { padding: 16px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
    .notice-item:last-child { border-bottom: none; }
    .notice-item:hover { background: #f9fafb; }
    .notice-info { flex: 1; }
    .notice-title { font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 4px; }
    .notice-title .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-left: 8px; }
    .badge-important { background: #fee2e2; color: #991b1b; }
    .badge-draft { background: #f3f4f6; color: #6b7280; }
    .notice-meta { font-size: 13px; color: #6b7280; }
    .notice-actions { display: flex; gap: 8px; }
</style>

<div class="admin-content">
    <div class="page-header">
        <div>
            <h1>공지사항 관리</h1>
        </div>
        <?php if (empty($editId)): ?>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('noticeForm').scrollIntoView({ behavior: 'smooth' });">
                + 공지사항 등록
            </button>
        <?php else: ?>
            <a href="/MVNO/admin/content/notice-manage.php" class="btn btn-secondary">목록으로</a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- 공지사항 등록/수정 폼 -->
    <div class="card">
        <div class="card-title">
            <?php echo $editNotice ? '공지사항 수정' : '공지사항 등록'; ?>
        </div>
        <form id="noticeForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $editNotice ? 'update' : 'create'; ?>">
            <?php if ($editNotice): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($editNotice['id']); ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="title">제목 <span style="color: #ef4444;">*</span></label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    value="<?php echo htmlspecialchars($editNotice['title'] ?? ''); ?>" 
                    placeholder="공지사항 제목을 입력하세요"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="notice_image">이미지 (선택사항)</label>
                
                <!-- 드래그 앤 드롭 영역 -->
                <div id="imageUploadArea" style="border: 2px dashed #d1d5db; border-radius: 12px; padding: 40px; text-align: center; background: #f9fafb; cursor: pointer; transition: all 0.3s; position: relative; min-height: 200px;">
                    <input 
                        type="file" 
                        id="notice_image" 
                        name="notice_image" 
                        accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                        style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; top: 0; left: 0; z-index: 10;"
                    >
                    
                    <!-- 현재 이미지 표시 -->
                    <div id="currentImageContainer" style="width: 100%; margin-bottom: 16px; position: relative; z-index: 1;">
                        <?php if ($editNotice && !empty($editNotice['image_url'])): ?>
                            <div style="position: relative; display: inline-block; pointer-events: none;">
                                <img src="<?php echo htmlspecialchars($editNotice['image_url']); ?>" 
                                     alt="현재 이미지" 
                                     id="currentImage"
                                     style="max-width: 100%; max-height: 300px; border: 2px solid #e5e7eb; border-radius: 8px; padding: 8px; background: white; display: block; transition: opacity 0.3s;">
                                <div id="currentImageOverlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); border-radius: 8px; display: none; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 16px; pointer-events: none;">
                                    클릭하여 이미지 변경
                                </div>
                            </div>
                        <?php else: ?>
                            <div id="uploadPlaceholder" style="display: flex; flex-direction: column; align-items: center; gap: 16px; pointer-events: none;">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #9ca3af;">
                                    <path d="M21 15V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <div>
                                    <p style="font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 4px 0;">이미지를 드래그하거나 클릭하여 업로드</p>
                                    <p style="font-size: 13px; color: #6b7280; margin: 0;">JPG, PNG, GIF, WEBP (최대 5MB)</p>
                                    <p style="font-size: 13px; color: #6366f1; margin: 4px 0 0 0; font-weight: 600;">권장 비율: 3:4</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 새 이미지 미리보기 -->
                    <div id="imagePreview" style="display: none; width: 100%; margin-top: 16px; position: relative; z-index: 1;">
                        <p style="font-size: 13px; color: #6366f1; margin-bottom: 8px; font-weight: 600;">새 이미지 미리보기:</p>
                        <img id="previewImg" src="" alt="미리보기" style="max-width: 100%; max-height: 300px; border: 2px solid #6366f1; border-radius: 8px; padding: 8px; background: white; display: block; margin: 0 auto;">
                    </div>
                    
                    <!-- 업로드 안내 (현재 이미지가 있을 때만 표시) -->
                    <?php if ($editNotice && !empty($editNotice['image_url'])): ?>
                        <p style="font-size: 13px; color: #6366f1; margin-top: 12px; font-weight: 500; position: relative; z-index: 1; pointer-events: none;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            이미지를 드래그하거나 클릭하여 변경
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="help" style="margin-top: 8px;">지원 형식: JPG, PNG, GIF, WEBP (최대 5MB) | 권장 비율: 3:4</div>
            </div>
            
            <div class="form-group">
                <label for="link_url">링크 주소</label>
                <input 
                    type="url" 
                    id="link_url" 
                    name="link_url" 
                    value="<?php echo htmlspecialchars($editNotice['link_url'] ?? ''); ?>" 
                    placeholder="https://example.com (선택사항)"
                >
                <div class="help">이미지 클릭 시 이동할 링크 주소를 입력하세요.</div>
            </div>
            
            <div class="form-group">
                <label for="content">내용 (선택사항)</label>
                <textarea 
                    id="content" 
                    name="content" 
                    placeholder="공지사항 내용을 입력하세요 (선택사항)"
                ><?php echo htmlspecialchars($editNotice['content'] ?? ''); ?></textarea>
                <div class="help">HTML 태그 사용 가능. 이미지만 사용할 경우 비워두셔도 됩니다.</div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input 
                        type="checkbox" 
                        id="show_on_main" 
                        name="show_on_main" 
                        value="1"
                        <?php echo ($editNotice['show_on_main'] ?? false) ? 'checked' : ''; ?>
                        onchange="toggleMainNoticeDates(this.checked)"
                    >
                    <label for="show_on_main" style="margin: 0; font-weight: 500;">메인공지</label>
                </div>
                <div class="help">체크 시 메인페이지 접속 시 이 공지사항이 새창으로 자동 표시됩니다.</div>
            </div>
            
            <div id="mainNoticeDates" style="display: <?php echo ($editNotice['show_on_main'] ?? false) ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label>메인공지 기간 (선택사항)</label>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="position: relative; flex: 1; max-width: 200px;">
                            <input 
                                type="text" 
                                id="start_at" 
                                name="start_at" 
                                value="<?php echo !empty($editNotice['start_at']) ? date('Y-m-d', strtotime($editNotice['start_at'])) : ''; ?>"
                                placeholder="YYYY-MM-DD"
                                readonly
                                onclick="openDatePicker('start_at')"
                                style="width: 100%; padding: 8px 12px; padding-right: 32px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; box-sizing: border-box; cursor: pointer; background: white; position: relative; z-index: 1;"
                            >
                            <button type="button" onclick="openDatePicker('start_at')" style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 2px; display: flex; align-items: center; justify-content: center; z-index: 20;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #6b7280;">
                                    <path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <input type="date" id="start_at_hidden" style="position: absolute; opacity: 0; width: 100%; height: 100%; top: 0; left: 0; cursor: pointer; z-index: 5; pointer-events: none;">
                        </div>
                        <span style="font-size: 18px; font-weight: 600; color: #6b7280;">~</span>
                        <div style="position: relative; flex: 1; max-width: 200px;">
                            <input 
                                type="text" 
                                id="end_at" 
                                name="end_at" 
                                value="<?php echo !empty($editNotice['end_at']) ? date('Y-m-d', strtotime($editNotice['end_at'])) : ''; ?>"
                                placeholder="YYYY-MM-DD"
                                readonly
                                onclick="openDatePicker('end_at')"
                                style="width: 100%; padding: 8px 12px; padding-right: 32px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; box-sizing: border-box; cursor: pointer; background: white; position: relative; z-index: 1;"
                            >
                            <button type="button" onclick="openDatePicker('end_at')" style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 2px; display: flex; align-items: center; justify-content: center; z-index: 20;">
                                <svg width="16" height: 16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #6b7280;">
                                    <path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <input type="date" id="end_at_hidden" style="position: absolute; opacity: 0; width: 100%; height: 100%; top: 0; left: 0; cursor: pointer; z-index: 5; pointer-events: none;">
                        </div>
                    </div>
                    <div class="help">시작일을 지정하지 않으면 즉시 표시됩니다. 종료일을 지정하지 않으면 계속 표시됩니다.</div>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <?php echo $editNotice ? '수정하기' : '등록하기'; ?>
                </button>
                <?php if ($editNotice): ?>
                    <a href="/MVNO/admin/content/notice-manage.php?page=<?php echo $page; ?>&per_page=<?php echo $perPage; ?>" class="btn btn-secondary">취소</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- 공지사항 목록 -->
    <div class="card notice-list">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div class="card-title" style="margin: 0;">공지사항 목록</div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="color: #6b7280; font-size: 14px;">총 <?php echo number_format($totalNotices); ?>개</span>
                <select id="per_page_select" onchange="changePerPage()" style="padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10개씩 보기</option>
                    <option value="20" <?php echo $perPage == 20 ? 'selected' : ''; ?>>20개씩 보기</option>
                    <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50개씩 보기</option>
                    <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100개씩 보기</option>
                </select>
            </div>
        </div>
        <?php if (empty($notices)): ?>
            <div style="padding: 40px; text-align: center; color: #6b7280;">
                등록된 공지사항이 없습니다.
            </div>
        <?php else: ?>
            <?php foreach ($notices as $notice): ?>
                <div class="notice-item">
                    <div class="notice-info">
                        <div class="notice-title">
                            <?php echo htmlspecialchars($notice['title']); ?>
                            <?php if (!empty($notice['show_on_main'])): ?>
                                <span class="badge" style="background: #dbeafe; color: #1e40af;">메인공지</span>
                            <?php endif; ?>
                        </div>
                        <div class="notice-meta">
                            작성일: <?php echo date('Y-m-d H:i', strtotime($notice['created_at'])); ?> | 
                            조회수: <?php echo number_format($notice['views'] ?? 0); ?>
                            <?php if ($notice['updated_at'] !== $notice['created_at']): ?>
                                | 수정일: <?php echo date('Y-m-d H:i', strtotime($notice['updated_at'])); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="notice-actions">
                        <a href="/MVNO/admin/content/notice-manage.php?edit=<?php echo htmlspecialchars($notice['id']); ?>&page=<?php echo $page; ?>&per_page=<?php echo $perPage; ?>" class="btn btn-secondary btn-sm">수정</a>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($notice['id']); ?>">
                            <button type="submit" class="btn btn-danger btn-sm">삭제</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1): ?>
                <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb; display: flex; justify-content: center; align-items: center; gap: 8px;">
                    <!-- 이전 버튼 -->
                    <a href="?page=<?php echo max(1, $page - 1); ?>&per_page=<?php echo $perPage; ?>" 
                       class="pagination-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>"
                       style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; background: white; <?php echo $page <= 1 ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : ''; ?>">
                        이전
                    </a>
                    
                    <!-- 페이지 번호 -->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    if ($startPage > 1) {
                        echo '<a href="?page=1&per_page=' . $perPage . '" class="pagination-btn" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; background: white;">1</a>';
                        if ($startPage > 2) {
                            echo '<span style="padding: 8px 4px; color: #6b7280;">...</span>';
                        }
                    }
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?>" 
                           class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"
                           style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; background: <?php echo $i === $page ? '#6366f1' : 'white'; ?>; color: <?php echo $i === $page ? 'white' : '#374151'; ?>; font-weight: <?php echo $i === $page ? '600' : '400'; ?>;">
                            <?php echo $i; ?>
                        </a>
                    <?php
                    endfor;
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span style="padding: 8px 4px; color: #6b7280;">...</span>';
                        }
                        echo '<a href="?page=' . $totalPages . '&per_page=' . $perPage . '" class="pagination-btn" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; background: white;">' . $totalPages . '</a>';
                    }
                    ?>
                    
                    <!-- 다음 버튼 -->
                    <a href="?page=<?php echo min($totalPages, $page + 1); ?>&per_page=<?php echo $perPage; ?>" 
                       class="pagination-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>"
                       style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; background: white; <?php echo $page >= $totalPages ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : ''; ?>">
                        다음
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleMainNoticeDates(checked) {
    const datesDiv = document.getElementById('mainNoticeDates');
    if (datesDiv) {
        datesDiv.style.display = checked ? 'block' : 'none';
        
        // 날짜 입력 필드가 표시될 때 숨겨진 date input 초기화
        if (checked) {
            setTimeout(function() {
                const startHidden = document.getElementById('start_at_hidden');
                const endHidden = document.getElementById('end_at_hidden');
                const startVisible = document.getElementById('start_at');
                const endVisible = document.getElementById('end_at');
                
                if (startHidden && startVisible) {
                    if (startVisible.value) {
                        startHidden.value = startVisible.value;
                    }
                    // 이벤트 리스너 설정
                    startHidden.removeEventListener('change', function() {});
                    startHidden.addEventListener('change', function() {
                        if (startHidden.value) {
                            startVisible.value = startHidden.value;
                        }
                    });
                }
                
                if (endHidden && endVisible) {
                    if (endVisible.value) {
                        endHidden.value = endVisible.value;
                    }
                    // 이벤트 리스너 설정
                    endHidden.removeEventListener('change', function() {});
                    endHidden.addEventListener('change', function() {
                        if (endHidden.value) {
                            endVisible.value = endHidden.value;
                        }
                    });
                }
            }, 50);
        }
    }
}

function openDatePicker(fieldId) {
    const hiddenInput = document.getElementById(fieldId + '_hidden');
    const visibleInput = document.getElementById(fieldId);
    
    if (!hiddenInput || !visibleInput) {
        console.error('Date picker elements not found:', fieldId);
        return;
    }
    
    // 현재 값 설정
    if (visibleInput.value) {
        hiddenInput.value = visibleInput.value;
    }
    
    // 날짜 변경 시 visible input 업데이트
    function updateVisibleInput() {
        if (hiddenInput.value) {
            visibleInput.value = hiddenInput.value;
        }
    }
    
    // 기존 이벤트 리스너 제거 후 새로 등록
    hiddenInput.removeEventListener('change', updateVisibleInput);
    hiddenInput.addEventListener('change', updateVisibleInput);
    
    // 숨겨진 date input에 포커스를 주고 달력 표시
    hiddenInput.focus();
    
    // showPicker() API 사용 (지원되는 경우)
    if (hiddenInput.showPicker) {
        try {
            hiddenInput.showPicker();
            return;
        } catch (e) {
            console.log('showPicker() failed, trying click()');
        }
    }
    
    // showPicker()를 지원하지 않거나 실패한 경우 클릭 사용
    // 약간의 지연을 두어 포커스가 먼저 적용되도록 함
    setTimeout(function() {
        hiddenInput.click();
    }, 10);
}

function changePerPage() {
    const perPage = document.getElementById('per_page_select').value;
    const params = new URLSearchParams(window.location.search);
    
    // edit 파라미터 제거 (페이지네이션 시 편집 모드 해제)
    params.delete('edit');
    params.set('per_page', perPage);
    params.set('page', '1'); // 첫 페이지로 이동
    
    window.location.href = '?' + params.toString();
}

// 날짜 입력 필드 및 이미지 업로드 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 날짜 입력 필드는 이미 onclick 속성이 있으므로 중복 이벤트 리스너 불필요
    
    // 이미지 업로드 처리
    const imageInput = document.getElementById('notice_image');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const currentImage = document.getElementById('currentImage');
    const currentImageContainer = document.getElementById('currentImageContainer');
    const imageUploadArea = document.getElementById('imageUploadArea');
    const currentImageOverlay = document.getElementById('currentImageOverlay');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    
    function handleFile(file) {
        if (!file) return;
        
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        // 파일 크기 및 타입 검증
        if (file.size > maxSize) {
            alert('파일 크기가 5MB를 초과합니다. 더 작은 이미지를 선택해주세요.');
            imageInput.value = '';
            imagePreview.style.display = 'none';
            return;
        }
        
        if (!allowedTypes.includes(file.type)) {
            alert('지원하지 않는 파일 형식입니다. JPG, PNG, GIF, WEBP만 업로드 가능합니다.');
            imageInput.value = '';
            imagePreview.style.display = 'none';
            return;
        }
        
        // 미리보기 표시
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            imagePreview.style.display = 'block';
            
            if (currentImage) currentImage.style.opacity = '0.3';
            if (uploadPlaceholder) uploadPlaceholder.style.display = 'none';
            if (imageUploadArea) {
                imageUploadArea.style.borderColor = '#6366f1';
                imageUploadArea.style.background = '#eef2ff';
            }
        };
        reader.readAsDataURL(file);
    }
    
    if (!imageInput || !imagePreview || !previewImg) return;
    
    // 파일 선택 이벤트
    imageInput.addEventListener('change', function(e) {
        handleFile(e.target.files[0]);
    });
    
    // 드래그 앤 드롭 처리
    if (imageUploadArea) {
        imageUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.style.borderColor = '#6366f1';
            this.style.background = '#eef2ff';
        });
        
        imageUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (!imagePreview.style.display || imagePreview.style.display === 'none') {
                this.style.borderColor = '#d1d5db';
                this.style.background = '#f9fafb';
            }
        });
        
        imageUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const file = e.dataTransfer.files[0];
            if (file) {
                imageInput.files = e.dataTransfer.files;
                handleFile(file);
            }
        });
        
        // 호버 효과 (현재 이미지가 있을 때)
        if (currentImage) {
            imageUploadArea.addEventListener('mouseenter', function() {
                if (currentImageOverlay) currentImageOverlay.style.display = 'flex';
                if (currentImage) currentImage.style.opacity = '0.7';
            });
            
            imageUploadArea.addEventListener('mouseleave', function() {
                if (currentImageOverlay) currentImageOverlay.style.display = 'none';
                if (currentImage && (!imagePreview.style.display || imagePreview.style.display === 'none')) {
                    currentImage.style.opacity = '1';
                }
            });
        }
    }
});
</script>

<?php include '../includes/admin-footer.php'; ?>




