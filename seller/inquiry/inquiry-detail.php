<?php
/**
 * 판매자 1:1 문의 상세 페이지
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

// 답변 목록 조회
$replies = getSellerInquiryReplies($inquiryId);

// 첨부파일 조회
$attachments = getSellerInquiryAttachments($inquiryId);
error_log("inquiry-detail.php: inquiry ID - $inquiryId, attachments count - " . count($attachments));

// DB에서 직접 확인
$pdo = getDBConnection();
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM seller_inquiry_attachments WHERE inquiry_id = :id");
    $stmt->execute([':id' => $inquiryId]);
    $dbAttachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("inquiry-detail.php: DB direct query - " . count($dbAttachments) . " attachments found");
    foreach ($dbAttachments as $idx => $att) {
        error_log("inquiry-detail.php: DB attachment[$idx] - " . json_encode($att));
        // DB 경로를 실제 파일 시스템 경로로 변환
        $dbPath = $att['file_path'];
        $actualPath = str_replace('/MVNO', '', $dbPath);
        $filePath = __DIR__ . '/../..' . $actualPath;
        error_log("inquiry-detail.php: file path - $filePath, exists - " . (file_exists($filePath) ? 'yes' : 'no'));
    }
}

foreach ($attachments as $idx => $att) {
    error_log("inquiry-detail.php: attachment[$idx] - " . json_encode($att));
    // DB 경로를 실제 파일 시스템 경로로 변환
    $dbPath = $att['file_path'];
    $actualPath = str_replace('/MVNO', '', $dbPath);
    $filePath = __DIR__ . '/../..' . $actualPath;
    error_log("inquiry-detail.php: file path - $filePath, exists - " . (file_exists($filePath) ? 'yes' : 'no'));
}

// 수정 가능 여부 확인
$canEdit = ($inquiry['status'] === 'pending' && empty($inquiry['admin_viewed_at']));
$canDelete = ($inquiry['status'] === 'pending' && empty($inquiry['admin_viewed_at']));


$currentPage = 'inquiry-detail.php';
include '../includes/seller-header.php';
?>

<style>
    .inquiry-detail-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 32px 24px;
    }
    
    .page-header {
        margin-bottom: 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s;
        margin-left: 8px;
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
    
    .btn-danger {
        background: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background: #dc2626;
    }
    
    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .inquiry-card {
        background: white;
        border-radius: 12px;
        padding: 32px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        margin-bottom: 24px;
    }
    
    .inquiry-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
        padding-bottom: 24px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .inquiry-title {
        font-size: 24px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 12px;
    }
    
    .inquiry-meta {
        font-size: 14px;
        color: #6b7280;
        display: flex;
        gap: 16px;
    }
    
    .status-badge {
        padding: 6px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
    }
    
    .status-badge.pending {
        background: #fef3c7;
        color: #92400e;
    }
    
    .status-badge.answered {
        background: #ddd6fe;
        color: #5b21b6;
    }
    
    /* closed 상태 제거됨 */
    
    .inquiry-content {
        font-size: 15px;
        line-height: 1.8;
        color: #374151;
        white-space: pre-wrap;
        margin-bottom: 24px;
    }
    
    .attachment-section {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
    }
    
    .attachment-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 16px;
        margin-top: 12px;
    }
    
    .attachment-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px;
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }
    
    .attachment-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        border-color: #6366f1;
    }
    
    .attachment-preview {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 10px;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        color: #9ca3af;
        cursor: pointer;
    }
    
    .attachment-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .attachment-name {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-bottom: 4px;
    }
    
    .attachment-size {
        font-size: 12px;
        color: #6b7280;
    }
    
    .attachment-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    
    .attachment-link:hover {
        text-decoration: none;
    }
    
    /* 이미지 확대 모달 */
    .image-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        cursor: pointer;
    }
    
    .image-modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
    }
    
    .image-modal-close {
        position: absolute;
        top: 20px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
        z-index: 10001;
    }
    
    .image-modal-close:hover {
        color: #fff;
    }
    
    .reply-section {
        margin-top: 32px;
    }
    
    .reply-item {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        margin-bottom: 16px;
    }
    
    .reply-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .reply-author {
        font-weight: 600;
        color: #1f2937;
    }
    
    .reply-author.admin {
        color: #6366f1;
    }
    
    .reply-date {
        font-size: 13px;
        color: #6b7280;
    }
    
    .reply-content {
        font-size: 15px;
        line-height: 1.8;
        color: #374151;
        white-space: pre-wrap;
    }
    
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    
    .alert-warning {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fbbf24;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #6ee7b7;
    }
    
    .notice-box {
        background: #fef3c7;
        border: 1px solid #fbbf24;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 24px;
    }
    
    .notice-box p {
        margin: 0;
        color: #92400e;
        font-size: 14px;
    }
</style>

<div class="inquiry-detail-container">
    <div class="page-header">
        <h1>1:1 문의 상세</h1>
        <div>
            <?php if ($canEdit): ?>
                <a href="<?php echo getAssetPath('/seller/inquiry/inquiry-edit.php'); ?>?id=<?php echo $inquiryId; ?>" class="btn btn-primary">수정</a>
            <?php endif; ?>
            <?php if ($canDelete): ?>
                <a href="<?php echo getAssetPath('/seller/inquiry/inquiry-delete.php'); ?>?id=<?php echo $inquiryId; ?>" class="btn btn-danger" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
            <?php endif; ?>
            <a href="<?php echo getAssetPath('/seller/inquiry/inquiry-list.php'); ?>" class="btn btn-secondary">목록</a>
        </div>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <?php if ($_GET['success'] === 'created'): ?>
            <div class="alert alert-success">문의가 등록되었습니다.</div>
        <?php endif; ?>
    <?php endif; ?>
    
    
    <!-- 문의 내용 -->
    <div class="inquiry-card">
        <div class="inquiry-header">
            <div>
                <div class="inquiry-title">
                    <?php if ($inquiry['status'] === 'pending'): ?>
                        <span class="status-badge <?php echo $inquiry['status']; ?>">
                            답변 대기
                        </span>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($inquiry['title']); ?>
                </div>
                <div class="inquiry-meta">
                    <span><?php echo date('Y-m-d', strtotime($inquiry['created_at'])); ?></span>
                </div>
            </div>
        </div>
        
        <div class="inquiry-content"><?php echo nl2br(htmlspecialchars($inquiry['content'])); ?></div>
        
        <?php if (!empty($attachments)): ?>
            <div class="attachment-section">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #1f2937;">첨부파일 (<?php echo count($attachments); ?>개)</h3>
                <div class="attachment-grid">
                    <?php foreach ($attachments as $attachment): ?>
                        <?php
                        // DB 경로를 실제 파일 시스템 경로로 변환
                        $dbPath = $attachment['file_path'];
                        $actualPath = str_replace('/MVNO', '', $dbPath);
                        $filePath = __DIR__ . '/../..' . $actualPath;
                        $fileExists = file_exists($filePath);
                        $isImage = strpos($attachment['file_type'], 'image/') === 0;
                        $fileUrl = getAssetPath('/seller/inquiry/inquiry-download.php') . '?file_id=' . $attachment['id'];
                        ?>
                        <div class="attachment-card" onclick="<?php echo $isImage ? "openImageModal('$fileUrl', '" . htmlspecialchars($attachment['file_name'], ENT_QUOTES) . "', true)" : "window.open('$fileUrl', '_blank')"; ?>">
                            <?php if ($isImage && $fileExists): ?>
                                <div class="attachment-preview">
                                    <img src="<?php echo $fileUrl; ?>" alt="<?php echo htmlspecialchars($attachment['file_name']); ?>" onerror="this.parentElement.innerHTML='🖼️';">
                                </div>
                            <?php else: ?>
                                <div class="attachment-preview"><?php
                                    if (strpos($attachment['file_type'], 'pdf') !== false) echo '📄';
                                    elseif (strpos($attachment['file_type'], 'word') !== false || strpos($attachment['file_type'], 'document') !== false) echo '📝';
                                    elseif (strpos($attachment['file_type'], 'excel') !== false || strpos($attachment['file_type'], 'spreadsheet') !== false) echo '📊';
                                    elseif (strpos($attachment['file_type'], 'hwp') !== false) echo '📋';
                                    else echo '📎';
                                ?></div>
                            <?php endif; ?>
                            <div class="attachment-name" title="<?php echo htmlspecialchars($attachment['file_name']); ?>">
                                <?php echo htmlspecialchars($attachment['file_name']); ?>
                            </div>
                            <div class="attachment-size">
                                <?php echo number_format($attachment['file_size'] / 1024, 1); ?> KB
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 답변 목록 -->
    <?php if (!empty($replies)): ?>
        <div class="reply-section">
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 16px; color: #1f2937;">답변</h2>
            <?php foreach ($replies as $reply): ?>
                <div class="reply-item">
                    <div class="reply-header">
                        <div>
                            <span class="reply-author <?php echo $reply['reply_type'] === 'admin' ? 'admin' : ''; ?>">
                                <?php if ($reply['reply_type'] === 'admin'): ?>
                                    관리자
                                <?php else: ?>
                                    <?php echo htmlspecialchars($reply['author_name'] ?? '알 수 없음'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="reply-date">
                            <?php echo date('Y-m-d', strtotime($reply['created_at'])); ?>
                        </div>
                    </div>
                    <div class="reply-content"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></div>
                    
                    <?php
                    $replyAttachments = getSellerInquiryAttachments($inquiryId, $reply['id']);
                    if (!empty($replyAttachments)):
                    ?>
                        <div class="attachment-section">
                            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #1f2937;">첨부파일 (<?php echo count($replyAttachments); ?>개)</h4>
                            <div class="attachment-grid">
                                <?php foreach ($replyAttachments as $attachment): ?>
                                    <?php
                                    $isImage = strpos($attachment['file_type'], 'image/') === 0;
                                    $fileUrl = getAssetPath('/seller/inquiry/inquiry-download.php') . '?file_id=' . $attachment['id'];
                                    ?>
                                    <div class="attachment-card" onclick="<?php echo $isImage ? "openImageModal('$fileUrl', '" . htmlspecialchars($attachment['file_name'], ENT_QUOTES) . "', true)" : "window.open('$fileUrl', '_blank')"; ?>">
                                        <?php if ($isImage): ?>
                                            <div class="attachment-preview">
                                                <img src="<?php echo $fileUrl; ?>" alt="<?php echo htmlspecialchars($attachment['file_name']); ?>" onerror="this.parentElement.innerHTML='🖼️';">
                                            </div>
                                        <?php else: ?>
                                            <div class="attachment-preview"><?php
                                                if (strpos($attachment['file_type'], 'pdf') !== false) echo '📄';
                                                elseif (strpos($attachment['file_type'], 'word') !== false || strpos($attachment['file_type'], 'document') !== false) echo '📝';
                                                elseif (strpos($attachment['file_type'], 'excel') !== false || strpos($attachment['file_type'], 'spreadsheet') !== false) echo '📊';
                                                elseif (strpos($attachment['file_type'], 'hwp') !== false) echo '📋';
                                                else echo '📎';
                                            ?></div>
                                        <?php endif; ?>
                                        <div class="attachment-name" title="<?php echo htmlspecialchars($attachment['file_name']); ?>">
                                            <?php echo htmlspecialchars($attachment['file_name']); ?>
                                        </div>
                                        <div class="attachment-size">
                                            <?php echo number_format($attachment['file_size'] / 1024, 1); ?> KB
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
</div>

<!-- 이미지 확대 모달 -->
<div id="imageModal" class="image-modal" onclick="closeImageModal()">
    <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
    <img class="image-modal-content" id="modalImage" src="" alt="">
</div>

<script>
function openImageModal(imageUrl, fileName, isImage) {
    if (isImage) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        modal.style.display = 'block';
        modalImg.src = imageUrl;
        modalImg.alt = fileName;
    } else {
        // 이미지가 아닌 경우 다운로드
        window.open(imageUrl, '_blank');
    }
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.style.display = 'none';
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});
</script>

<?php include '../includes/seller-footer.php'; ?>

