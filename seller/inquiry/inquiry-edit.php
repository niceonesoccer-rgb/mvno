<?php
/**
 * 판매자 1:1 문의 수정 페이지
 */

require_once __DIR__ . '/../../includes/data/path-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/seller-inquiry-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: ' . getAssetPath('/seller/login.php'));
    exit;
}

// 판매자 승인 체크
$isApproved = isset($currentUser['seller_approved']) && $currentUser['seller_approved'] === true;
if (!$isApproved) {
    header('Location: ' . getAssetPath('/seller/waiting.php'));
    exit;
}

$sellerId = $currentUser['user_id'];
$inquiryId = intval($_GET['id'] ?? 0);

if (!$inquiryId) {
    header('Location: ' . getAssetPath('/seller/inquiry/inquiry-list.php'));
    exit;
}

// 문의 조회
$inquiry = getSellerInquiryById($inquiryId);

if (!$inquiry || $inquiry['seller_id'] !== $sellerId) {
    header('Location: ' . getAssetPath('/seller/inquiry/inquiry-list.php'));
    exit;
}

// 수정 가능 여부 확인 (답변 전이고 관리자가 확인하지 않은 경우만 수정 가능)
$canEdit = ($inquiry['status'] === 'pending' && empty($inquiry['admin_viewed_at']));

if (!$canEdit) {
    header('Location: ' . getAssetPath('/seller/inquiry/inquiry-detail.php') . '?id=' . $inquiryId . '&error=cannot_edit');
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
        // 삭제할 파일 ID 처리
        $deleteFileIds = [];
        if (!empty($_POST['delete_files']) && is_array($_POST['delete_files'])) {
            $deleteFileIds = array_map('intval', $_POST['delete_files']);
        }
        
        // 유지할 파일 ID 처리
        // 새 파일을 업로드하지 않았을 때는 모든 기존 파일을 유지
        $keepFileIds = [];
        
        // 파일 업로드 확인 (더 정확한 체크)
        $hasNewFiles = !empty($_FILES['attachments']['name'][0]) && 
                       is_array($_FILES['attachments']['name']) && 
                       !empty($_FILES['attachments']['name'][0]);
        
        error_log("inquiry-edit.php: hasNewFiles: " . ($hasNewFiles ? 'yes' : 'no'));
        error_log("inquiry-edit.php: _FILES: " . json_encode($_FILES));
        
        if (!$hasNewFiles) {
            // 새 파일이 없으면 모든 기존 파일 ID를 가져옴
            $pdo = getDBConnection();
            if ($pdo) {
                $stmt = $pdo->prepare("
                    SELECT id FROM seller_inquiry_attachments 
                    WHERE inquiry_id = ? AND reply_id IS NULL
                ");
                $stmt->execute([$inquiryId]);
                $existingFileIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $keepFileIds = $existingFileIds;
                error_log("inquiry-edit.php: No new files, keeping existing files: " . json_encode($keepFileIds));
            }
        } else {
            // 새 파일이 있으면 POST로 전달된 keep_files 사용
            if (!empty($_POST['keep_files']) && is_array($_POST['keep_files'])) {
                $keepFileIds = array_map('intval', $_POST['keep_files']);
                error_log("inquiry-edit.php: New files uploaded, keeping files from POST: " . json_encode($keepFileIds));
            } else {
                error_log("inquiry-edit.php: New files uploaded but no keep_files in POST");
            }
        }
        
        // 삭제할 파일 삭제 (새 파일이 업로드된 경우에만 실행)
        // 새 파일이 없으면 기존 파일 삭제를 막기 위해 나중에 처리
        $pendingDeleteFileIds = $deleteFileIds;
        
        // 새 첨부파일 처리
        $attachments = [];
        $uploadErrors = [];
        if (!empty($_FILES['attachments']['name'][0])) {
            $fileCount = count($_FILES['attachments']['name']);
            $totalSize = 0;
            
            // 기존 파일 크기도 포함하여 계산
            if (!empty($keepFileIds)) {
                $pdo = getDBConnection();
                if ($pdo) {
                    $placeholders = implode(',', array_fill(0, count($keepFileIds), '?'));
                    $stmt = $pdo->prepare("
                        SELECT file_size FROM seller_inquiry_attachments 
                        WHERE id IN ($placeholders) AND inquiry_id = ?
                    ");
                    $stmt->execute(array_merge($keepFileIds, [$inquiryId]));
                    $existingFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($existingFiles as $existing) {
                        $totalSize += $existing['file_size'];
                    }
                }
            }
            
            for ($i = 0; $i < $fileCount; $i++) {
                $fileName = $_FILES['attachments']['name'][$i];
                $fileError = $_FILES['attachments']['error'][$i];
                
                error_log("inquiry-edit.php: Processing file $i - name: $fileName, error: $fileError");
                
                if ($fileError === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $fileName,
                        'type' => $_FILES['attachments']['type'][$i],
                        'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                        'size' => $_FILES['attachments']['size'][$i],
                        'error' => $fileError
                    ];
                    
                    $totalSize += $file['size'];
                    if ($totalSize > 20 * 1024 * 1024) { // 20MB 제한
                        $error = '첨부파일 총 크기는 20MB를 초과할 수 없습니다.';
                        break;
                    }
                    
                    error_log("inquiry-edit.php: Calling uploadSellerInquiryAttachment for file: $fileName");
                    $attachment = uploadSellerInquiryAttachment($file, $inquiryId, $sellerId);
                    if ($attachment) {
                        error_log("inquiry-edit.php: File uploaded successfully: " . json_encode($attachment));
                        $attachments[] = $attachment;
                    } else {
                        error_log("inquiry-edit.php: File upload failed for: $fileName");
                        $uploadErrors[] = $fileName;
                    }
                } else {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => '파일 크기가 서버 최대 크기를 초과했습니다.',
                        UPLOAD_ERR_FORM_SIZE => '파일 크기가 폼 최대 크기를 초과했습니다.',
                        UPLOAD_ERR_PARTIAL => '파일이 일부만 업로드되었습니다.',
                        UPLOAD_ERR_NO_FILE => '파일이 업로드되지 않았습니다.',
                        UPLOAD_ERR_NO_TMP_DIR => '임시 폴더가 없습니다.',
                        UPLOAD_ERR_CANT_WRITE => '파일 쓰기에 실패했습니다.',
                        UPLOAD_ERR_EXTENSION => '파일 업로드가 확장에 의해 중지되었습니다.'
                    ];
                    $errorMsg = $errorMessages[$fileError] ?? "알 수 없는 오류 (코드: $fileError)";
                    error_log("inquiry-edit.php: Upload error for $fileName: $errorMsg");
                    $uploadErrors[] = "$fileName: $errorMsg";
                }
            }
            
            if (!empty($uploadErrors) && empty($attachments)) {
                $error = '파일 업로드에 실패했습니다: ' . implode(', ', $uploadErrors);
            } elseif (!empty($uploadErrors)) {
                $error = '일부 파일 업로드에 실패했습니다: ' . implode(', ', $uploadErrors);
            }
        }
        
        error_log("inquiry-edit.php: Total attachments to save: " . count($attachments));
        error_log("inquiry-edit.php: Pending delete file IDs: " . json_encode($pendingDeleteFileIds));
        error_log("inquiry-edit.php: Keep file IDs: " . json_encode($keepFileIds));
        
        // 새 파일이 업로드되지 않았고 삭제할 파일만 있는 경우 처리
        if (empty($attachments) && !empty($pendingDeleteFileIds)) {
            // 새 파일 없이 기존 파일만 삭제하는 경우
            error_log("inquiry-edit.php: No new files, but files to delete. Processing deletion only.");
            $pdo = getDBConnection();
            if ($pdo) {
                $placeholders = implode(',', array_fill(0, count($pendingDeleteFileIds), '?'));
                $stmt = $pdo->prepare("
                    SELECT file_path FROM seller_inquiry_attachments 
                    WHERE id IN ($placeholders) AND inquiry_id = ? AND reply_id IS NULL
                ");
                $params = array_merge($pendingDeleteFileIds, [$inquiryId]);
                $stmt->execute($params);
                $filesToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($filesToDelete as $file) {
                    $dbPath = $file['file_path'];
                    $actualPath = str_replace('/MVNO', '', $dbPath);
                    $filePath = __DIR__ . '/../..' . $actualPath;
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                        error_log("inquiry-edit.php: Deleted file: $filePath");
                    }
                }
                
                $stmt = $pdo->prepare("
                    DELETE FROM seller_inquiry_attachments 
                    WHERE id IN ($placeholders) AND inquiry_id = ? AND reply_id IS NULL
                ");
                $stmt->execute($params);
                error_log("inquiry-edit.php: Deleted " . count($pendingDeleteFileIds) . " files from DB");
            }
        }
        
        // 파일 개수 확인 (유지할 파일 + 새 파일)
        $totalFileCount = count($keepFileIds) + count($attachments);
        if ($totalFileCount > 5) {
            $error = '최대 5개까지 업로드할 수 있습니다.';
        }
        
        if (empty($error)) {
            error_log("inquiry-edit.php: Calling updateSellerInquiry with " . count($attachments) . " attachments");
            // 문의 수정 (새 파일이 있을 때만 기존 파일 삭제)
            // 새 파일이 없으면 keepFileIds에 모든 기존 파일이 포함되어 있어서 기존 파일이 유지됨
            $updateResult = updateSellerInquiry($inquiryId, $sellerId, $title, $content, $attachments, $keepFileIds);
            error_log("inquiry-edit.php: updateSellerInquiry result: " . ($updateResult ? 'success' : 'failed'));
            
            if ($updateResult) {
                header('Location: ' . getAssetPath('/seller/inquiry/inquiry-detail.php') . '?id=' . $inquiryId . '&success=updated');
                exit;
            } else {
                // DB 저장 실패 시 업로드된 파일 정리
                if (!empty($attachments)) {
                    error_log("inquiry-edit.php: Cleaning up uploaded files due to DB save failure");
                    foreach ($attachments as $attachment) {
                        $dbPath = $attachment['file_path'];
                        $actualPath = str_replace('/MVNO', '', $dbPath);
                        $filePath = __DIR__ . '/../..' . $actualPath;
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                            error_log("inquiry-edit.php: Deleted file: $filePath");
                        }
                    }
                }
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
        border: 3px dashed #d1d5db;
        border-radius: 12px;
        padding: 40px 24px;
        text-align: center;
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .file-upload-area::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.1), transparent);
        transition: left 0.5s;
    }
    
    .file-upload-area:hover::before {
        left: 100%;
    }
    
    .file-upload-area:hover {
        border-color: #6366f1;
        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.15);
    }
    
    .file-upload-area.drag-over {
        border-color: #6366f1;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        transform: scale(1.02);
        box-shadow: 0 12px 32px rgba(99, 102, 241, 0.25);
    }
    
    .file-upload-area.has-files {
        border-color: #10b981;
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    }
    
    .file-upload-icon {
        font-size: 48px;
        margin-bottom: 16px;
        display: block;
        transition: transform 0.3s;
    }
    
    .file-upload-area:hover .file-upload-icon {
        transform: scale(1.1) rotate(5deg);
    }
    
    .file-upload-text {
        font-size: 16px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .file-upload-hint {
        font-size: 13px;
        color: #6b7280;
        margin: 0;
    }
    
    .file-list {
        margin-top: 24px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
    }
    
    .file-item {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
    }
    
    .file-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        border-color: #6366f1;
    }
    
    .file-item-preview {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 12px;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        color: #9ca3af;
    }
    
    .file-item-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    
    .file-item-name {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .file-item-size {
        font-size: 12px;
        color: #6b7280;
    }
    
    .file-item-remove {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 28px;
        height: 28px;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: bold;
        transition: all 0.2s;
        opacity: 0;
    }
    
    .file-item:hover .file-item-remove {
        opacity: 1;
    }
    
    .file-item-remove:hover {
        background: #dc2626;
        transform: scale(1.1);
    }
    
    .file-type-icon {
        font-size: 32px;
        margin-bottom: 8px;
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
                <label for="attachments">첨부파일</label>
                <div class="file-upload-area" id="fileUploadArea">
                    <input type="file" id="attachments" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.hwp" style="display: none;">
                    <span class="file-upload-icon">📁</span>
                    <div class="file-upload-text">파일을 드래그하거나 클릭하여 업로드</div>
                    <div class="file-upload-hint">이미지, PDF, 문서 파일 (최대 5개, 총 20MB)</div>
                </div>
                <div class="file-list" id="fileList">
                    <?php if (!empty($existingAttachments)): ?>
                        <?php foreach ($existingAttachments as $attachment): ?>
                            <?php
                            $isImage = strpos($attachment['file_type'], 'image/') === 0;
                            $fileUrl = getAssetPath('/seller/inquiry/inquiry-download.php') . '?file_id=' . $attachment['id'];
                            ?>
                            <div class="file-item existing-file" data-file-id="<?php echo $attachment['id']; ?>">
                                <?php if ($isImage): ?>
                                    <div class="file-item-preview">
                                        <img src="<?php echo $fileUrl; ?>" alt="<?php echo htmlspecialchars($attachment['file_name']); ?>" onerror="this.parentElement.innerHTML='🖼️';">
                                    </div>
                                <?php else: ?>
                                    <div class="file-item-preview"><?php
                                        if (strpos($attachment['file_type'], 'pdf') !== false) echo '📄';
                                        elseif (strpos($attachment['file_type'], 'word') !== false || strpos($attachment['file_type'], 'document') !== false) echo '📝';
                                        elseif (strpos($attachment['file_type'], 'excel') !== false || strpos($attachment['file_type'], 'spreadsheet') !== false) echo '📊';
                                        elseif (strpos($attachment['file_type'], 'hwp') !== false) echo '📋';
                                        else echo '📎';
                                    ?></div>
                                <?php endif; ?>
                                <div class="file-item-info">
                                    <div class="file-item-name" title="<?php echo htmlspecialchars($attachment['file_name']); ?>">
                                        <?php echo htmlspecialchars($attachment['file_name']); ?>
                                    </div>
                                    <div class="file-item-size">
                                        <?php echo number_format($attachment['file_size'] / 1024, 1); ?> KB
                                    </div>
                                </div>
                                <button type="button" class="file-item-remove" onclick="removeExistingFile(<?php echo $attachment['id']; ?>)" title="삭제">×</button>
                                <input type="hidden" name="keep_files[]" value="<?php echo $attachment['id']; ?>" id="keep_file_<?php echo $attachment['id']; ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="help-text">
                    • 지원 형식: JPG, PNG, GIF, WEBP, PDF, DOC, DOCX, XLS, XLSX, HWP<br>
                    • 최대 5개 파일, 총 20MB까지 업로드 가능
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">수정하기</button>
                <a href="<?php echo getAssetPath('/seller/inquiry/inquiry-detail.php'); ?>?id=<?php echo $inquiryId; ?>" class="btn btn-secondary">취소</a>
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
        
        // 기존 파일 개수 확인
        const existingFiles = fileList.querySelectorAll('.existing-file');
        const existingCount = existingFiles.length;
        
        // 파일 개수 확인 (기존 파일 + 새 파일)
        if (existingCount + selectedFiles.length + files.length > maxFiles) {
            alert(`최대 ${maxFiles}개까지 업로드할 수 있습니다. (현재 기존 파일 ${existingCount}개)`);
            return;
        }
        
        // 총 크기 확인 (기존 파일 크기도 포함)
        let totalSize = selectedFiles.reduce((sum, f) => sum + f.size, 0);
        for (let file of files) {
            totalSize += file.size;
        }
        
        // 기존 파일 크기 추가
        existingFiles.forEach(fileItem => {
            const sizeText = fileItem.querySelector('.file-item-size').textContent;
            const sizeMatch = sizeText.match(/([\d.]+)\s*(KB|MB)/);
            if (sizeMatch) {
                const size = parseFloat(sizeMatch[1]);
                const unit = sizeMatch[2];
                if (unit === 'MB') {
                    totalSize += size * 1024 * 1024;
                } else {
                    totalSize += size * 1024;
                }
            }
        });
        
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
        // 기존 파일은 유지하고 새 파일만 추가
        const existingFiles = fileList.querySelectorAll('.existing-file');
        const existingCount = existingFiles.length;
        const newFilesCount = selectedFiles.length;
        const totalCount = existingCount + newFilesCount;
        
        // 기존 파일 제거 (새로 렌더링하기 위해)
        const newFileItems = fileList.querySelectorAll('.file-item:not(.existing-file)');
        newFileItems.forEach(item => item.remove());
        
        if (totalCount > 0) {
            fileUploadArea.classList.add('has-files');
        } else {
            fileUploadArea.classList.remove('has-files');
        }
        
        // 새 파일 추가
        selectedFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item new-file';
            
            const isImage = file.type.startsWith('image/');
            const preview = isImage 
                ? `<img src="${URL.createObjectURL(file)}" alt="${file.name}" class="file-item-preview">`
                : `<div class="file-item-preview">${getFileIcon(file.type)}</div>`;
            
            fileItem.innerHTML = `
                ${preview}
                <div class="file-item-info">
                    <div class="file-item-name" title="${file.name}">${file.name}</div>
                    <div class="file-item-size">${formatFileSize(file.size)}</div>
                </div>
                <button type="button" class="file-item-remove" onclick="removeFile(${index})" title="삭제">×</button>
            `;
            fileList.appendChild(fileItem);
        });
        
        // 실제 파일 input 업데이트
        // DataTransfer를 사용하면 일부 브라우저에서 form submit 시 파일이 전달되지 않을 수 있음
        // 대신 hidden input을 사용하거나, form submit 전에 파일을 다시 설정
        try {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => {
                try {
                    dataTransfer.items.add(file);
                } catch (e) {
                    console.error('Error adding file to DataTransfer:', e);
                }
            });
            fileInput.files = dataTransfer.files;
            console.log('File input updated, file count:', fileInput.files.length);
        } catch (e) {
            console.error('Error updating file input:', e);
        }
    }
    
    function getFileIcon(mimeType) {
        if (mimeType.startsWith('image/')) return '🖼️';
        if (mimeType === 'application/pdf') return '📄';
        if (mimeType.includes('word') || mimeType.includes('document')) return '📝';
        if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return '📊';
        if (mimeType.includes('hwp')) return '📋';
        return '📎';
    }
    
    window.removeFile = function(index) {
        selectedFiles.splice(index, 1);
        updateFileList();
    };
    
    // 기존 파일 삭제 함수
    window.removeExistingFile = function(fileId) {
        if (confirm('이 파일을 삭제하시겠습니까?')) {
            const fileItem = document.querySelector(`.file-item[data-file-id="${fileId}"]`);
            const keepInput = document.getElementById(`keep_file_${fileId}`);
            
            if (fileItem) {
                fileItem.remove();
            }
            if (keepInput) {
                keepInput.remove();
            }
            
            // 삭제할 파일 ID를 hidden input으로 추가
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_files[]';
            deleteInput.value = fileId;
            document.getElementById('inquiryForm').appendChild(deleteInput);
            
            // 파일 개수 업데이트
            const existingFiles = fileList.querySelectorAll('.existing-file');
            const newFiles = fileList.querySelectorAll('.new-file');
            const totalCount = existingFiles.length + newFiles.length;
            
            if (totalCount === 0) {
                fileUploadArea.classList.remove('has-files');
            }
        }
    };
    
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
    
    // Form submit 전에 파일 확인
    const form = document.getElementById('inquiryForm');
    form.addEventListener('submit', function(e) {
        console.log('Form submit - selectedFiles count:', selectedFiles.length);
        console.log('Form submit - fileInput.files count:', fileInput.files.length);
        
        // 파일이 선택되었는데 input에 없으면 다시 설정
        if (selectedFiles.length > 0 && fileInput.files.length === 0) {
            console.log('Files selected but not in input, updating...');
            try {
                const dataTransfer = new DataTransfer();
                selectedFiles.forEach(file => {
                    try {
                        dataTransfer.items.add(file);
                    } catch (err) {
                        console.error('Error adding file:', err);
                    }
                });
                fileInput.files = dataTransfer.files;
                console.log('File input updated on submit, file count:', fileInput.files.length);
            } catch (err) {
                console.error('Error updating file input on submit:', err);
                alert('파일 업로드에 문제가 발생했습니다. 다시 시도해주세요.');
                e.preventDefault();
                return false;
            }
        }
        
        // 파일 개수 확인
        const existingFiles = fileList.querySelectorAll('.existing-file');
        const newFiles = fileList.querySelectorAll('.new-file');
        const totalCount = existingFiles.length + newFiles.length;
        
        if (totalCount > 5) {
            alert('최대 5개까지 업로드할 수 있습니다.');
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});
</script>

<?php include '../includes/seller-footer.php'; ?>

