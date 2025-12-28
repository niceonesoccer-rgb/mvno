<?php
/**
 * 판매자 1:1 문의 작성 페이지
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
$error = '';
$success = '';

// 문의 등록 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if (empty($title)) {
        $error = '제목을 입력해주세요.';
    } elseif (empty($content)) {
        $error = '내용을 입력해주세요.';
    } else {
        // 먼저 문의 등록 (파일 없이)
        $inquiryId = createSellerInquiry($sellerId, $title, $content, []);
        
        if ($inquiryId) {
            error_log("inquiry-write.php: inquiry created with ID - $inquiryId");
            
            // $_FILES 디버깅
            error_log("inquiry-write.php: _FILES dump - " . json_encode($_FILES));
            error_log("inquiry-write.php: isset(_FILES['attachments']) - " . (isset($_FILES['attachments']) ? 'yes' : 'no'));
            if (isset($_FILES['attachments'])) {
                error_log("inquiry-write.php: _FILES['attachments'] structure - " . json_encode([
                    'name' => $_FILES['attachments']['name'] ?? 'not set',
                    'error' => $_FILES['attachments']['error'] ?? 'not set',
                    'size' => $_FILES['attachments']['size'] ?? 'not set'
                ]));
            }
            
            // 첨부파일 처리 (실제 inquiry ID로)
            $attachments = [];
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'])) {
                $fileNames = is_array($_FILES['attachments']['name']) ? $_FILES['attachments']['name'] : [$_FILES['attachments']['name']];
                error_log("inquiry-write.php: files detected - " . count($fileNames));
                
                // 파일 배열 정규화 (단일 파일인 경우 배열로 변환)
                $fileCount = is_array($_FILES['attachments']['name']) ? count($_FILES['attachments']['name']) : 1;
                $totalSize = 0;
                
                // 파일 크기 검증
                for ($i = 0; $i < $fileCount; $i++) {
                    $fileError = is_array($_FILES['attachments']['error']) ? $_FILES['attachments']['error'][$i] : $_FILES['attachments']['error'];
                    if ($fileError === UPLOAD_ERR_OK) {
                        $fileSize = is_array($_FILES['attachments']['size']) ? $_FILES['attachments']['size'][$i] : $_FILES['attachments']['size'];
                        $totalSize += $fileSize;
                        if ($totalSize > 20 * 1024 * 1024) { // 20MB 제한
                            $error = '첨부파일 총 크기는 20MB를 초과할 수 없습니다.';
                            error_log("inquiry-write.php: total size exceeded - $totalSize");
                            break;
                        }
                    } else {
                        error_log("inquiry-write.php: file error[$i] - $fileError");
                    }
                }
                
                if (empty($error)) {
                    // 파일 업로드
                    for ($i = 0; $i < $fileCount; $i++) {
                        $fileError = is_array($_FILES['attachments']['error']) ? $_FILES['attachments']['error'][$i] : $_FILES['attachments']['error'];
                        
                        if ($fileError === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => is_array($_FILES['attachments']['name']) ? $_FILES['attachments']['name'][$i] : $_FILES['attachments']['name'],
                                'type' => is_array($_FILES['attachments']['type']) ? $_FILES['attachments']['type'][$i] : $_FILES['attachments']['type'],
                                'tmp_name' => is_array($_FILES['attachments']['tmp_name']) ? $_FILES['attachments']['tmp_name'][$i] : $_FILES['attachments']['tmp_name'],
                                'size' => is_array($_FILES['attachments']['size']) ? $_FILES['attachments']['size'][$i] : $_FILES['attachments']['size'],
                                'error' => $fileError
                            ];
                            
                            error_log("inquiry-write.php: uploading file[$i] - " . $file['name'] . ", tmp_name: " . $file['tmp_name']);
                            
                            $attachment = uploadSellerInquiryAttachment($file, $inquiryId, $sellerId);
                            if ($attachment) {
                                error_log("inquiry-write.php: file uploaded successfully - " . json_encode($attachment));
                                $attachments[] = $attachment;
                                
                                // DB에 첨부파일 정보 저장
                                $pdo = getDBConnection();
                                if ($pdo) {
                                    try {
                                        ensureSellerInquiryTables();
                                        $stmt = $pdo->prepare("
                                            INSERT INTO seller_inquiry_attachments 
                                            (inquiry_id, file_name, file_path, file_size, file_type, uploaded_by, created_at)
                                            VALUES (:inquiry_id, :file_name, :file_path, :file_size, :file_type, :uploaded_by, NOW())
                                        ");
                                        $result = $stmt->execute([
                                            ':inquiry_id' => $inquiryId,
                                            ':file_name' => $attachment['file_name'],
                                            ':file_path' => $attachment['file_path'],
                                            ':file_size' => $attachment['file_size'],
                                            ':file_type' => $attachment['file_type'],
                                            ':uploaded_by' => $sellerId
                                        ]);
                                        
                                        if ($result) {
                                            $insertId = $pdo->lastInsertId();
                                            error_log("inquiry-write.php: attachment saved to DB - ID: $insertId");
                                            
                                            // DB에서 다시 조회해서 확인
                                            $checkStmt = $pdo->prepare("SELECT * FROM seller_inquiry_attachments WHERE id = :id");
                                            $checkStmt->execute([':id' => $insertId]);
                                            $saved = $checkStmt->fetch(PDO::FETCH_ASSOC);
                                            error_log("inquiry-write.php: DB verification - " . json_encode($saved));
                                        } else {
                                            error_log("inquiry-write.php: DB insert failed - no rows affected");
                                        }
                                    } catch (PDOException $e) {
                                        error_log("inquiry-write.php: DB error - " . $e->getMessage());
                                        error_log("inquiry-write.php: DB error trace - " . $e->getTraceAsString());
                                        $error = '첨부파일 정보 저장에 실패했습니다: ' . $e->getMessage();
                                    }
                                } else {
                                    error_log("inquiry-write.php: DB connection failed");
                                    $error = '데이터베이스 연결에 실패했습니다.';
                                }
                            } else {
                                error_log("inquiry-write.php: file upload failed for file[$i]");
                                $error = '파일 업로드에 실패했습니다: ' . $file['name'];
                            }
                        }
                    }
                }
            } else {
                error_log("inquiry-write.php: no files uploaded");
            }
            
            if (empty($error)) {
                header('Location: /MVNO/seller/inquiry/inquiry-detail.php?id=' . $inquiryId . '&success=created');
                exit;
            } else {
                // 에러 발생 시 문의 삭제
                deleteSellerInquiry($inquiryId, $sellerId);
            }
        } else {
            $error = '문의 등록에 실패했습니다.';
        }
    }
}

$currentPage = 'inquiry-write.php';
include '../includes/seller-header.php';
?>

<style>
    .inquiry-write-container {
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

<div class="inquiry-write-container">
    <div class="page-header">
        <h1>1:1 문의 작성</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="form-card">
        <form method="POST" enctype="multipart/form-data" id="inquiryForm">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="title">제목 <span style="color: #ef4444;">*</span></label>
                <input type="text" id="title" name="title" required placeholder="문의 제목을 입력하세요" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="content">내용 <span style="color: #ef4444;">*</span></label>
                <textarea id="content" name="content" required placeholder="문의 내용을 입력하세요"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="attachments">첨부파일</label>
                <div class="file-upload-area" id="fileUploadArea">
                    <input type="file" id="attachments" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.hwp" style="display: none;">
                    <p style="margin: 0; color: #6b7280;">파일을 드래그하거나 클릭하여 업로드</p>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #9ca3af;">이미지, PDF, 문서 파일 (최대 5개, 총 20MB)</p>
                </div>
                <div class="file-list" id="fileList"></div>
                <div class="help-text">
                    • 지원 형식: JPG, PNG, GIF, WEBP, PDF, DOC, DOCX, XLS, XLSX, HWP<br>
                    • 최대 5개 파일, 총 20MB까지 업로드 가능
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">등록하기</button>
                <a href="/MVNO/seller/inquiry/inquiry-list.php" class="btn btn-secondary">취소</a>
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
        // input 초기화하지 않음 (파일이 전송되도록 유지)
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

