<?php
/**
 * 판매자 1:1 문의 수정 페이지
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/seller-inquiry-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: /MVNO/seller/login.php');
    exit;
}

// 판매자 승인 체크
$isApproved = isset($currentUser['seller_approved']) && $currentUser['seller_approved'] === true;
if (!$isApproved) {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

$sellerId = $currentUser['user_id'];
$inquiryId = intval($_GET['id'] ?? 0);

if (!$inquiryId) {
    header('Location: /MVNO/seller/inquiry/inquiry-list.php');
    exit;
}

// 문의 조회
$inquiry = getSellerInquiryById($inquiryId);

if (!$inquiry || $inquiry['seller_id'] !== $sellerId) {
    header('Location: /MVNO/seller/inquiry/inquiry-list.php');
    exit;
}

// 수정 가능 여부 확인
$canEdit = ($inquiry['status'] === 'pending' && empty($inquiry['admin_viewed_at']));

if (!$canEdit) {
    header('Location: /MVNO/seller/inquiry/inquiry-detail.php?id=' . $inquiryId . '&error=cannot_edit');
    exit;
}

$error = '';
$success = '';

// 문의 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if (empty($title)) {
        $error = '제목을 입력해주세요.';
    } elseif (empty($content)) {
        $error = '내용을 입력해주세요.';
    } else {
        // 첨부파일 처리
        $attachments = [];
        if (!empty($_FILES['attachments']['name'][0])) {
            $fileCount = count($_FILES['attachments']['name']);
            $totalSize = 0;
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['attachments']['name'][$i],
                        'type' => $_FILES['attachments']['type'][$i],
                        'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                        'size' => $_FILES['attachments']['size'][$i],
                        'error' => $_FILES['attachments']['error'][$i]
                    ];
                    
                    $totalSize += $file['size'];
                    if ($totalSize > 20 * 1024 * 1024) { // 20MB 제한
                        $error = '첨부파일 총 크기는 20MB를 초과할 수 없습니다.';
                        break;
                    }
                    
                    $attachment = uploadSellerInquiryAttachment($file, $inquiryId, $sellerId);
                    if ($attachment) {
                        $attachments[] = $attachment;
                    }
                }
            }
        }
        
        if (empty($error)) {
            // 문의 수정
            if (updateSellerInquiry($inquiryId, $sellerId, $title, $content, $attachments)) {
                header('Location: /MVNO/seller/inquiry/inquiry-detail.php?id=' . $inquiryId . '&success=updated');
                exit;
            } else {
                $error = '문의 수정에 실패했습니다. 관리자가 확인했거나 답변이 완료된 문의는 수정할 수 없습니다.';
            }
        }
    }
}

// 기존 첨부파일 조회
$existingAttachments = getSellerInquiryAttachments($inquiryId);

$currentPage = 'inquiry-edit.php';
include '../includes/seller-header.php';
?>

<style>
    .inquiry-edit-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 32px 24px;
    }
    
    .page-header {
        margin-bottom: 32px;
    }
    
    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .form-card {
        background: white;
        border-radius: 12px;
        padding: 32px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-group input[type="text"],
    .form-group textarea {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 15px;
        font-family: inherit;
        box-sizing: border-box;
    }
    
    .form-group textarea {
        min-height: 200px;
        resize: vertical;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .file-upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 24px;
        text-align: center;
        background: #f9fafb;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .file-upload-area:hover {
        border-color: #6366f1;
        background: #f3f4f6;
    }
    
    .file-upload-area.drag-over {
        border-color: #6366f1;
        background: #eef2ff;
    }
    
    .file-list {
        margin-top: 16px;
    }
    
    .file-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        background: #f3f4f6;
        border-radius: 6px;
        margin-bottom: 8px;
    }
    
    .file-item-name {
        flex: 1;
        font-size: 14px;
        color: #374151;
    }
    
    .file-item-remove {
        color: #ef4444;
        cursor: pointer;
        font-weight: 600;
    }
    
    .btn-group {
        display: flex;
        gap: 12px;
        margin-top: 32px;
    }
    
    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s;
    }
    
    .btn-primary {
        background: #6366f1;
        color: white;
    }
    
    .btn-primary:hover {
        background: #4f46e5;
    }
    
    .btn-secondary {
        background: #6b7280;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #4b5563;
    }
    
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    
    .help-text {
        font-size: 13px;
        color: #6b7280;
        margin-top: 6px;
    }
</style>

<div class="inquiry-edit-container">
    <div class="page-header">
        <h1>1:1 문의 수정</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="form-card">
        <form method="POST" enctype="multipart/form-data" id="inquiryForm">
            <input type="hidden" name="action" value="update">
            
            <div class="form-group">
                <label for="title">제목 <span style="color: #ef4444;">*</span></label>
                <input type="text" id="title" name="title" required placeholder="문의 제목을 입력하세요" value="<?php echo htmlspecialchars($inquiry['title']); ?>">
            </div>
            
            <div class="form-group">
                <label for="content">내용 <span style="color: #ef4444;">*</span></label>
                <textarea id="content" name="content" required placeholder="문의 내용을 입력하세요"><?php echo htmlspecialchars($inquiry['content']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>기존 첨부파일</label>
                <?php if (!empty($existingAttachments)): ?>
                    <div class="file-list">
                        <?php foreach ($existingAttachments as $attachment): ?>
                            <div class="file-item">
                                <span class="file-item-name">📎 <?php echo htmlspecialchars($attachment['file_name']); ?></span>
                                <span style="color: #6b7280; font-size: 13px;">(기존 파일은 수정 시 삭제됩니다)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #9ca3af; font-size: 14px;">등록된 첨부파일이 없습니다.</p>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="attachments">새 첨부파일</label>
                <div class="file-upload-area" id="fileUploadArea">
                    <input type="file" id="attachments" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.hwp" style="display: none;">
                    <p style="margin: 0; color: #6b7280;">파일을 드래그하거나 클릭하여 업로드</p>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #9ca3af;">이미지, PDF, 문서 파일 (최대 5개, 총 20MB)</p>
                </div>
                <div class="file-list" id="fileList"></div>
                <div class="help-text">
                    • 지원 형식: JPG, PNG, GIF, WEBP, PDF, DOC, DOCX, XLS, XLSX, HWP<br>
                    • 최대 5개 파일, 총 20MB까지 업로드 가능<br>
                    • 기존 첨부파일은 새 파일로 교체됩니다.
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">수정하기</button>
                <a href="/MVNO/seller/inquiry/inquiry-detail.php?id=<?php echo $inquiryId; ?>" class="btn btn-secondary">취소</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('attachments');
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileList = document.getElementById('fileList');
    const selectedFiles = [];
    
    // 파일 선택
    fileUploadArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    fileInput.addEventListener('change', function(e) {
        handleFiles(Array.from(e.target.files));
    });
    
    // 드래그 앤 드롭
    fileUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        fileUploadArea.classList.add('drag-over');
    });
    
    fileUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        fileUploadArea.classList.remove('drag-over');
    });
    
    fileUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        fileUploadArea.classList.remove('drag-over');
        handleFiles(Array.from(e.dataTransfer.files));
    });
    
    function handleFiles(files) {
        const maxFiles = 5;
        const maxTotalSize = 20 * 1024 * 1024; // 20MB
        
        // 파일 개수 확인
        if (selectedFiles.length + files.length > maxFiles) {
            alert(`최대 ${maxFiles}개까지 업로드할 수 있습니다.`);
            return;
        }
        
        // 총 크기 확인
        let totalSize = selectedFiles.reduce((sum, f) => sum + f.size, 0);
        for (let file of files) {
            totalSize += file.size;
        }
        
        if (totalSize > maxTotalSize) {
            alert('총 파일 크기는 20MB를 초과할 수 없습니다.');
            return;
        }
        
        // 파일 추가
        for (let file of files) {
            selectedFiles.push(file);
        }
        
        updateFileList();
        fileInput.value = ''; // input 초기화
    }
    
    function updateFileList() {
        fileList.innerHTML = '';
        
        selectedFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.innerHTML = `
                <span class="file-item-name">${file.name} (${formatFileSize(file.size)})</span>
                <span class="file-item-remove" onclick="removeFile(${index})">삭제</span>
            `;
            fileList.appendChild(fileItem);
        });
        
        // 실제 파일 input 업데이트
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
    }
    
    window.removeFile = function(index) {
        selectedFiles.splice(index, 1);
        updateFileList();
    };
    
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
});
</script>

<?php include '../includes/seller-footer.php'; ?>

