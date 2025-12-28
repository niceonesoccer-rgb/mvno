<?php
/**
 * 판매자 1:1 문의 상세 페이지
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
        $filePath = __DIR__ . '/../..' . $att['file_path'];
        error_log("inquiry-detail.php: file path - $filePath, exists - " . (file_exists($filePath) ? 'yes' : 'no'));
    }
}

foreach ($attachments as $idx => $att) {
    error_log("inquiry-detail.php: attachment[$idx] - " . json_encode($att));
    $filePath = __DIR__ . '/../..' . $att['file_path'];
    error_log("inquiry-detail.php: file path - $filePath, exists - " . (file_exists($filePath) ? 'yes' : 'no'));
}

// 수정 가능 여부 확인
$canEdit = ($inquiry['status'] === 'pending' && empty($inquiry['admin_viewed_at']));
$canDelete = ($inquiry['status'] === 'pending' && empty($inquiry['admin_viewed_at']));

// 확인 완료 처리
if (isset($_GET['mark_closed']) && $inquiry['status'] === 'answered') {
    if (markSellerInquiryAsClosed($inquiryId, $sellerId)) {
        header('Location: /MVNO/seller/inquiry/inquiry-detail.php?id=' . $inquiryId . '&success=closed');
        exit;
    }
}

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
    
    .status-badge.closed {
        background: #d1fae5;
        color: #065f46;
    }
    
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
    
    .attachment-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: #f9fafb;
        border-radius: 8px;
        margin-bottom: 8px;
    }
    
    .attachment-item a {
        color: #6366f1;
        text-decoration: none;
        font-weight: 500;
    }
    
    .attachment-item a:hover {
        text-decoration: underline;
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
                <a href="/MVNO/seller/inquiry/inquiry-edit.php?id=<?php echo $inquiryId; ?>" class="btn btn-primary">수정</a>
            <?php endif; ?>
            <?php if ($canDelete): ?>
                <a href="/MVNO/seller/inquiry/inquiry-delete.php?id=<?php echo $inquiryId; ?>" class="btn btn-danger" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
            <?php endif; ?>
            <a href="/MVNO/seller/inquiry/inquiry-list.php" class="btn btn-secondary">목록</a>
        </div>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <?php if ($_GET['success'] === 'created'): ?>
            <div class="alert alert-success">문의가 등록되었습니다.</div>
        <?php elseif ($_GET['success'] === 'closed'): ?>
            <div class="alert alert-success">확인 완료 처리되었습니다.</div>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php if (!$canEdit && !empty($inquiry['admin_viewed_at'])): ?>
        <div class="notice-box">
            <p>⚠️ 관리자가 이 문의를 확인했습니다. 수정할 수 없습니다.</p>
        </div>
    <?php elseif (!$canEdit && $inquiry['status'] !== 'pending'): ?>
        <div class="notice-box">
            <p>⚠️ 답변이 완료된 문의는 수정할 수 없습니다.</p>
        </div>
    <?php endif; ?>
    
    <!-- 문의 내용 -->
    <div class="inquiry-card">
        <div class="inquiry-header">
            <div>
                <div class="inquiry-title">
                    <span class="status-badge <?php echo $inquiry['status']; ?>">
                        <?php
                        $statusText = [
                            'pending' => '답변 대기',
                            'answered' => '답변 완료',
                            'closed' => '확인 완료'
                        ];
                        echo $statusText[$inquiry['status']];
                        ?>
                    </span>
                    <?php echo htmlspecialchars($inquiry['title']); ?>
                </div>
                <div class="inquiry-meta">
                    <span>작성일: <?php echo date('Y-m-d H:i', strtotime($inquiry['created_at'])); ?></span>
                    <?php if (!empty($inquiry['admin_viewed_at'])): ?>
                        <span>관리자 확인: <?php echo date('Y-m-d H:i', strtotime($inquiry['admin_viewed_at'])); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="inquiry-content"><?php echo nl2br(htmlspecialchars($inquiry['content'])); ?></div>
        
        <?php if (!empty($attachments)): ?>
            <div class="attachment-section">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #1f2937;">첨부파일</h3>
                <?php foreach ($attachments as $attachment): ?>
                    <?php
                    $filePath = __DIR__ . '/../..' . $attachment['file_path'];
                    $fileExists = file_exists($filePath);
                    ?>
                    <div class="attachment-item">
                        <span>📎</span>
                        <?php if ($fileExists): ?>
                            <a href="/MVNO/seller/inquiry/inquiry-download.php?file_id=<?php echo $attachment['id']; ?>" target="_blank">
                                <?php echo htmlspecialchars($attachment['file_name']); ?>
                            </a>
                        <?php else: ?>
                            <span style="color: #ef4444;">
                                <?php echo htmlspecialchars($attachment['file_name']); ?> (파일 없음)
                            </span>
                            <a href="/MVNO/seller/inquiry/inquiry-debug.php?id=<?php echo $inquiryId; ?>" style="margin-left: 8px; font-size: 12px; color: #6366f1;">디버깅</a>
                            <a href="/MVNO/seller/inquiry/inquiry-check-db.php?id=<?php echo $inquiryId; ?>" style="margin-left: 8px; font-size: 12px; color: #6366f1;">DB확인</a>
                        <?php endif; ?>
                        <span style="color: #9ca3af; font-size: 13px;">
                            (<?php echo number_format($attachment['file_size'] / 1024, 1); ?> KB)
                        </span>
                    </div>
                <?php endforeach; ?>
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
                                <?php echo htmlspecialchars($reply['author_name'] ?? '알 수 없음'); ?>
                                <?php if ($reply['reply_type'] === 'admin'): ?>
                                    <span style="color: #6366f1; font-size: 12px; margin-left: 8px;">(관리자)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="reply-date">
                            <?php echo date('Y-m-d H:i', strtotime($reply['created_at'])); ?>
                        </div>
                    </div>
                    <div class="reply-content"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></div>
                    
                    <?php
                    $replyAttachments = getSellerInquiryAttachments($inquiryId, $reply['id']);
                    if (!empty($replyAttachments)):
                    ?>
                        <div class="attachment-section">
                            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #1f2937;">첨부파일</h4>
                            <?php foreach ($replyAttachments as $attachment): ?>
                                <div class="attachment-item">
                                    <span>📎</span>
                                    <a href="/MVNO/seller/inquiry/inquiry-download.php?file_id=<?php echo $attachment['id']; ?>" target="_blank">
                                        <?php echo htmlspecialchars($attachment['file_name']); ?>
                                    </a>
                                    <span style="color: #9ca3af; font-size: 13px;">
                                        (<?php echo number_format($attachment['file_size'] / 1024, 1); ?> KB)
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- 확인 완료 버튼 -->
    <?php if ($inquiry['status'] === 'answered'): ?>
        <div style="text-align: center; margin-top: 32px;">
            <a href="?id=<?php echo $inquiryId; ?>&mark_closed=1" class="btn btn-primary">확인 완료</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/seller-footer.php'; ?>

