<?php
/**
 * 판매자 전용 공지사항 관리 페이지
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/notice-functions.php';
require_once __DIR__ . '/../../includes/data/path-config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdmin()) {
    header('Location: ' . getAssetPath('/admin/'));
    exit;
}

// 페이지네이션 설정 (10개씩 고정)
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 공지사항 목록 가져오기
$notices = getSellerNoticesForAdmin($perPage, $offset);
$totalCount = getSellerNoticesCount();
$totalPages = ceil($totalCount / $perPage);

require_once __DIR__ . '/../includes/admin-header.php';
?>

<style>
    .admin-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    
    .page-title {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }
    
    .btn-primary {
        padding: 12px 24px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-primary:hover {
        background: #2563eb;
    }
    
    .notice-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .notice-table th {
        background: #f9fafb;
        padding: 16px;
        text-align: left;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .notice-table td {
        padding: 16px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
        color: #6b7280;
    }
    
    .notice-table tr:hover {
        background: #f9fafb;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-main {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .badge-text {
        background: #f3f4f6;
        color: #374151;
    }
    
    .badge-image {
        background: #fef3c7;
        color: #92400e;
    }
    
    .badge-both {
        background: #d1fae5;
        color: #065f46;
    }
    
    .btn-action {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        margin-right: 4px;
    }
    
    .btn-edit {
        background: #f3f4f6;
        color: #374151;
    }
    
    .btn-edit:hover {
        background: #e5e7eb;
    }
    
    .btn-delete {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .btn-delete:hover {
        background: #fecaca;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 24px;
    }
    
    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 14px;
        color: #374151;
        background: white;
        border: 1px solid #e5e7eb;
    }
    
    .pagination a:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }
    
    .pagination .active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    
    .pagination .disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    /* 모달 스타일 */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .modal {
        background: white;
        border-radius: 12px;
        padding: 0;
        max-width: 800px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
    }
    
    .modal-header {
        padding: 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-title {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }
    
    .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        color: #6b7280;
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }
    
    .close-btn:hover {
        background: #f3f4f6;
        color: #374151;
    }
    
    .modal-body {
        padding: 24px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-label .required {
        color: #ef4444;
    }
    
    .form-input,
    .form-textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        color: #1f2937;
        box-sizing: border-box;
    }
    
    .form-textarea {
        min-height: 120px;
        resize: vertical;
    }
    
    .form-input:focus,
    .form-textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .form-radio-group {
        display: flex;
        gap: 24px;
        margin-top: 8px;
    }
    
    .form-radio {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .form-radio input[type="radio"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .form-radio label {
        font-size: 14px;
        color: #374151;
        cursor: pointer;
    }
    
    .image-upload-area {
        margin-top: 12px;
    }
    
    .drop-zone {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: #f9fafb;
    }
    
    .drop-zone:hover {
        border-color: #3b82f6;
        background: #eff6ff;
    }
    
    .drop-zone.dragover {
        border-color: #3b82f6;
        background: #dbeafe;
    }
    
    .drop-zone svg {
        width: 48px;
        height: 48px;
        color: #9ca3af;
        margin-bottom: 12px;
    }
    
    .drop-zone p {
        font-size: 14px;
        color: #6b7280;
        margin: 8px 0;
    }
    
    .drop-zone button {
        margin-top: 12px;
        padding: 8px 16px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
    }
    
    .image-preview {
        margin-top: 16px;
        position: relative;
        display: inline-block;
    }
    
    .image-preview img {
        max-width: 100%;
        max-height: 300px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }
    
    .image-preview .delete-image-btn {
        position: absolute;
        top: 8px;
        right: 8px;
        padding: 8px 12px;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        font-weight: 600;
    }
    
    .image-preview .delete-image-btn:hover {
        background: rgba(220, 38, 38, 1);
    }
    
    .form-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .form-checkbox input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .form-checkbox label {
        font-size: 14px;
        color: #374151;
        cursor: pointer;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    
    .modal-footer {
        padding: 24px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
    
    .btn-cancel {
        padding: 10px 20px;
        background: #f3f4f6;
        color: #374151;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .btn-cancel:hover {
        background: #e5e7eb;
    }
    
    .btn-save {
        padding: 10px 20px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .btn-save:hover {
        background: #2563eb;
    }
    
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #10b981;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }
    
    /* 삭제 확인 모달 스타일 */
    .delete-modal {
        background: white;
        border-radius: 12px;
        padding: 24px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
    }
    
    .delete-modal h3 {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 16px 0;
    }
    
    .delete-modal p {
        font-size: 14px;
        color: #6b7280;
        margin: 0 0 24px 0;
        line-height: 1.6;
    }
    
    .delete-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
    
    .btn-cancel-delete {
        padding: 10px 20px;
        background: #f3f4f6;
        color: #374151;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .btn-cancel-delete:hover {
        background: #e5e7eb;
    }
    
    .btn-confirm-delete {
        padding: 10px 20px;
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .btn-confirm-delete:hover {
        background: #dc2626;
    }
</style>

<div class="admin-container">
    <div class="page-header">
        <h1 class="page-title">판매자 공지사항 관리</h1>
        <button class="btn-primary" onclick="openModal()">새 공지사항 작성</button>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php
            if ($_GET['success'] === 'created') {
                echo '공지사항이 생성되었습니다.';
            } elseif ($_GET['success'] === 'updated') {
                echo '공지사항이 수정되었습니다.';
            } elseif ($_GET['success'] === 'deleted') {
                echo '공지사항이 삭제되었습니다.';
            }
            ?>
        </div>
    <?php endif; ?>
    
    <table class="notice-table">
        <thead>
            <tr>
                <th>번호</th>
                <th>제목</th>
                <th>배너 타입</th>
                <th>메인공지</th>
                <th>메인공지 배너 기간</th>
                <th>작성일</th>
                <th>관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($notices)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #9ca3af;">
                        등록된 공지사항이 없습니다.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($notices as $index => $notice): ?>
                    <tr>
                        <td><?= $totalCount - $offset - $index ?></td>
                        <td><?= htmlspecialchars($notice['title']) ?></td>
                        <td>
                            <?php
                            $bannerType = $notice['banner_type'] ?? 'text';
                            $badgeClass = 'badge-' . $bannerType;
                            $badgeText = [
                                'text' => '텍스트만',
                                'image' => '이미지만',
                                'both' => '텍스트+이미지'
                            ][$bannerType] ?? '텍스트만';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                        </td>
                        <td>
                            <?php if ($notice['show_on_main'] ?? 0): ?>
                                <span class="badge badge-main">메인공지</span>
                            <?php else: ?>
                                <span style="color: #9ca3af;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $startAt = $notice['start_at'] ?? null;
                            $endAt = $notice['end_at'] ?? null;
                            if ($startAt || $endAt) {
                                echo ($startAt ?: '시작일 없음') . ' ~ ' . ($endAt ?: '종료일 없음');
                            } else {
                                echo '<span style="color: #9ca3af;">기간 제한 없음</span>';
                            }
                            ?>
                        </td>
                        <td><?= date('Y-m-d H:i', strtotime($notice['created_at'])) ?></td>
                        <td>
                            <button class="btn-action btn-edit" onclick="editNotice('<?= htmlspecialchars($notice['id'], ENT_QUOTES) ?>')">수정</button>
                            <button class="btn-action btn-delete" onclick="deleteNotice('<?= htmlspecialchars($notice['id'], ENT_QUOTES) ?>')">삭제</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">이전</a>
            <?php else: ?>
                <span class="disabled">이전</span>
            <?php endif; ?>
            
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            if ($startPage > 1): ?>
                <a href="?page=1">1</a>
                <?php if ($startPage > 2): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                    <span>...</span>
                <?php endif; ?>
                <a href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>">다음</a>
            <?php else: ?>
                <span class="disabled">다음</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal-overlay" id="deleteModal">
    <div class="delete-modal">
        <h3>공지사항 삭제</h3>
        <p>정말 삭제하시겠습니까?<br>삭제된 공지사항은 복구할 수 없습니다.</p>
        <div class="delete-modal-actions">
            <button type="button" class="btn-cancel-delete" onclick="closeDeleteModal()">취소</button>
            <button type="button" class="btn-confirm-delete" id="confirmDeleteBtn">삭제</button>
        </div>
    </div>
</div>

<!-- 작성/수정 모달 -->
<div class="modal-overlay" id="noticeModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">새 공지사항 작성</h2>
            <button class="close-btn" onclick="closeModal()">×</button>
        </div>
        <form id="noticeForm" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" id="noticeId" name="id">
                <input type="hidden" name="action" id="formAction" value="create">
                
                <div class="form-group">
                    <label class="form-label">제목 <span class="required">*</span></label>
                    <input type="text" class="form-input" id="noticeTitle" name="title" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">내용</label>
                    <textarea class="form-textarea" id="noticeContent" name="content"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">배너 타입 <span class="required">*</span></label>
                    <div class="form-radio-group">
                        <div class="form-radio">
                            <input type="radio" id="bannerTypeText" name="banner_type" value="text" checked>
                            <label for="bannerTypeText">텍스트만</label>
                        </div>
                        <div class="form-radio">
                            <input type="radio" id="bannerTypeImage" name="banner_type" value="image">
                            <label for="bannerTypeImage">이미지만</label>
                        </div>
                        <div class="form-radio">
                            <input type="radio" id="bannerTypeBoth" name="banner_type" value="both">
                            <label for="bannerTypeBoth">텍스트+이미지</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group" id="imageUploadGroup">
                    <label class="form-label">이미지 업로드</label>
                    <div class="image-upload-area">
                        <input type="file" id="imageInput" name="image" accept="image/*" style="display: none;" onchange="handleImageSelect(event)">
                        <div class="drop-zone" id="dropZone" onclick="document.getElementById('imageInput').click()">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p>이미지를 드래그하거나 클릭하여 선택</p>
                            <button type="button">이미지 선택</button>
                        </div>
                        <div class="image-preview" id="imagePreview" style="display: none;">
                            <img id="previewImage" src="" alt="미리보기">
                            <button type="button" class="delete-image-btn" onclick="deleteImage()">🗑️ 삭제</button>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">링크 URL (선택사항)</label>
                    <input type="url" class="form-input" id="noticeLinkUrl" name="link_url" placeholder="https://example.com">
                </div>
                
                <div class="form-group">
                    <div class="form-checkbox">
                        <input type="checkbox" id="showOnMain" name="show_on_main" value="1">
                        <label for="showOnMain">메인공지</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">메인공지 배너 기간</label>
                    <div class="form-row">
                        <div>
                            <label class="form-label" style="font-size: 12px; font-weight: 400;">시작일</label>
                            <input type="date" class="form-input" id="startAt" name="start_at">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 12px; font-weight: 400;">종료일</label>
                            <input type="date" class="form-input" id="endAt" name="end_at">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">취소</button>
                <button type="submit" class="btn-save">저장</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentImageUrl = null;
let isEditMode = false;

// 배너 타입에 따른 필드 표시/숨김
document.querySelectorAll('input[name="banner_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        updateImageUploadVisibility();
    });
});

function updateImageUploadVisibility() {
    const bannerType = document.querySelector('input[name="banner_type"]:checked').value;
    const imageUploadGroup = document.getElementById('imageUploadGroup');
    const contentGroup = document.querySelector('#noticeContent').closest('.form-group');
    
    if (bannerType === 'text') {
        imageUploadGroup.style.display = 'none';
        contentGroup.style.display = 'block';
    } else if (bannerType === 'image') {
        imageUploadGroup.style.display = 'block';
        contentGroup.style.display = 'none';
    } else { // both
        imageUploadGroup.style.display = 'block';
        contentGroup.style.display = 'block';
    }
}

// 드래그 앤 드롭
const dropZone = document.getElementById('dropZone');
const imageInput = document.getElementById('imageInput');

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0 && files[0].type.startsWith('image/')) {
        imageInput.files = files;
        handleImageSelect({ target: imageInput });
    }
});

function handleImageSelect(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    if (!file.type.startsWith('image/')) {
        alert('이미지 파일만 업로드 가능합니다.');
        return;
    }
    
    if (file.size > 10 * 1024 * 1024) {
        alert('이미지 크기는 10MB 이하여야 합니다.');
        return;
    }
    
    // 기존 이미지가 있으면 삭제
    if (currentImageUrl && isEditMode) {
        deleteImageFile(currentImageUrl);
    }
    
    const reader = new FileReader();
    reader.onload = (e) => {
        document.getElementById('previewImage').src = e.target.result;
        document.getElementById('imagePreview').style.display = 'block';
        dropZone.style.display = 'none';
        currentImageUrl = null; // 새 이미지 선택 시 기존 URL 초기화
    };
    reader.readAsDataURL(file);
}

function deleteImage() {
    if (confirm('이미지를 삭제하시겠습니까?')) {
        // 기존 이미지가 있으면 서버에서 삭제
        if (currentImageUrl && isEditMode) {
            deleteImageFile(currentImageUrl);
        }
        
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('previewImage').src = '';
        imageInput.value = '';
        dropZone.style.display = 'block';
        currentImageUrl = null;
    }
}

function deleteImageFile(imageUrl) {
    fetch('<?php echo getApiPath('/admin/api/seller-notice-api.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'delete_image',
            id: document.getElementById('noticeId').value,
            image_url: imageUrl
        })
    });
}

function openModal(noticeId = null) {
    isEditMode = noticeId !== null;
    const modal = document.getElementById('noticeModal');
    const form = document.getElementById('noticeForm');
    
    // 폼 초기화
    form.reset();
    document.getElementById('imagePreview').style.display = 'none';
    dropZone.style.display = 'block';
    currentImageUrl = null;
    document.getElementById('formAction').value = 'create';
    document.getElementById('modalTitle').textContent = '새 공지사항 작성';
    
    if (isEditMode) {
        // 수정 모드: 공지사항 데이터 로드
        fetch('<?php echo getApiPath('/admin/api/seller-notice-api.php'); ?>?action=get&id=' + noticeId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const notice = data.data;
                    document.getElementById('noticeId').value = notice.id;
                    document.getElementById('noticeTitle').value = notice.title || '';
                    document.getElementById('noticeContent').value = notice.content || '';
                    document.getElementById('noticeLinkUrl').value = notice.link_url || '';
                    document.getElementById('showOnMain').checked = notice.show_on_main == 1;
                    document.getElementById('startAt').value = notice.start_at || '';
                    document.getElementById('endAt').value = notice.end_at || '';
                    
                    // 배너 타입 설정
                    const bannerType = notice.banner_type || 'text';
                    document.querySelector(`input[name="banner_type"][value="${bannerType}"]`).checked = true;
                    updateImageUploadVisibility();
                    
                    // 이미지 표시 (경로 정규화 필요시)
                    if (notice.image_url) {
                        currentImageUrl = notice.image_url;
                        document.getElementById('previewImage').src = notice.image_url;
                        document.getElementById('imagePreview').style.display = 'block';
                        dropZone.style.display = 'none';
                    }
                    
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('modalTitle').textContent = '공지사항 수정';
                }
            });
    }
    
    modal.classList.add('active');
    updateImageUploadVisibility();
}

function closeModal() {
    document.getElementById('noticeModal').classList.remove('active');
}

function editNotice(noticeId) {
    openModal(noticeId);
}

let deleteNoticeId = null;

function deleteNotice(noticeId) {
    deleteNoticeId = noticeId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    deleteNoticeId = null;
}

function confirmDelete() {
    if (!deleteNoticeId) return;
    
    fetch('<?php echo getApiPath('/admin/api/seller-notice-api.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'delete',
            id: deleteNoticeId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.href = '<?php echo getAssetPath('/admin/content/seller-notice-manage.php?success=deleted&page=' . $page); ?>';
        } else {
            alert('삭제에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
            closeDeleteModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('삭제 중 오류가 발생했습니다.');
        closeDeleteModal();
    });
}

// 삭제 확인 버튼 클릭 이벤트
document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDelete);

// 삭제 모달 외부 클릭 시 닫기
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

// 폼 제출
document.getElementById('noticeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const action = formData.get('action');
    
    fetch('<?php echo getApiPath('/admin/api/seller-notice-api.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.href = '<?php echo getAssetPath('/admin/content/seller-notice-manage.php?success='); ?>' + (action === 'create' ? 'created' : 'updated') + '&page=<?= $page ?>';
        } else {
            alert('저장에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('저장 중 오류가 발생했습니다.');
    });
});

// 모달 외부 클릭 시 닫기
document.getElementById('noticeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

