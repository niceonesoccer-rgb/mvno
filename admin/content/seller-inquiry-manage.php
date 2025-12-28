<?php
/**
 * 판매자 1:1 문의 관리 페이지
 * 경로: /MVNO/admin/content/seller-inquiry-manage.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/seller-inquiry-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdmin()) {
    header('Location: /MVNO/admin/');
    exit;
}

$currentUser = getCurrentUser();
$adminId = $currentUser['user_id'];

$error = '';
$success = '';

// 답변 작성 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    $inquiryId = intval($_POST['inquiry_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    
    if (empty($content)) {
        $error = '답변 내용을 입력해주세요.';
    } else {
        // 첨부파일 처리
        $attachments = [];
        if (!empty($_FILES['attachments']['name'][0])) {
            $fileCount = count($_FILES['attachments']['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['attachments']['name'][$i],
                        'type' => $_FILES['attachments']['type'][$i],
                        'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                        'size' => $_FILES['attachments']['size'][$i],
                        'error' => $_FILES['attachments']['error'][$i]
                    ];
                    
                    // 임시로 reply ID 생성
                    $tempReplyId = 999999;
                    $attachment = uploadSellerInquiryReplyAttachment($file, $inquiryId, $tempReplyId, $adminId);
                    if ($attachment) {
                        $attachments[] = $attachment;
                    }
                }
            }
        }
        
        // 답변 작성
        $replyId = createSellerInquiryReply($inquiryId, $adminId, $content, $attachments);
        
        if ($replyId) {
            // 실제 reply ID로 파일 경로 업데이트
            if (!empty($attachments)) {
                $pdo = getDBConnection();
                foreach ($attachments as $idx => $attachment) {
                    $newPath = str_replace('/' . $tempReplyId . '/', '/' . $replyId . '/', $attachment['file_path']);
                    $oldPath = __DIR__ . '/../..' . $attachment['file_path'];
                    $newFullPath = __DIR__ . '/../..' . $newPath;
                    
                    // 디렉토리 생성
                    $newDir = dirname($newFullPath);
                    if (!is_dir($newDir)) {
                        mkdir($newDir, 0755, true);
                    }
                    
                    // 파일 이동
                    if (file_exists($oldPath)) {
                        rename($oldPath, $newFullPath);
                    }
                    
                    // DB 업데이트
                    $stmt = $pdo->prepare("UPDATE seller_inquiry_attachments SET file_path = :new_path WHERE file_path = :old_path");
                    $stmt->execute([
                        ':new_path' => $newPath,
                        ':old_path' => $attachment['file_path']
                    ]);
                }
            }
            
            $success = '답변이 등록되었습니다.';
        } else {
            $error = '답변 등록에 실패했습니다.';
        }
    }
}

// 필터 및 페이지네이션
$status = $_GET['status'] ?? '';
$sellerId = $_GET['seller_id'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 10);
if (!in_array($perPage, [10, 20, 50, 100])) {
    $perPage = 10;
}

$offset = ($page - 1) * $perPage;

// 문의 목록 가져오기
$inquiries = getAllSellerInquiries($status ?: null, $sellerId ?: null, $perPage, $offset);
$allInquiries = getAllSellerInquiries($status ?: null, $sellerId ?: null);
$totalInquiries = count($allInquiries);
$totalPages = ceil($totalInquiries / $perPage);

// 상태별 통계
$stats = [
    'pending' => 0,
    'answered' => 0,
    'closed' => 0
];
$allStats = getAllSellerInquiries();
foreach ($allStats as $inq) {
    $stats[$inq['status']] = $stats[$inq['status']] + 1;
}

// 상세 조회 시 확인 처리
$detailId = intval($_GET['detail'] ?? 0);
if ($detailId) {
    markSellerInquiryAsViewedByAdmin($detailId, $adminId);
}

$currentPage = 'seller-inquiry-manage.php';
include '../includes/admin-header.php';
?>

<style>
    .admin-content { 
        padding: 32px; 
        max-width: 95%;
        margin: 0 auto;
    }
    .page-header { margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; }
    .page-header h1 { font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
    .card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; margin-bottom: 24px; }
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; }
    .stat-label { font-size: 14px; color: #6b7280; margin-bottom: 8px; }
    .stat-value { font-size: 32px; font-weight: 700; color: #1f2937; }
    .stat-card.pending .stat-value { color: #f59e0b; }
    .stat-card.answered .stat-value { color: #6366f1; }
    .stat-card.closed .stat-value { color: #10b981; }
    .filter-section { display: flex; gap: 12px; margin-bottom: 24px; align-items: center; }
    .filter-section select, .filter-section input { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
    .inquiry-list { margin-top: 24px; }
    .inquiry-item { padding: 16px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
    .inquiry-item:last-child { border-bottom: none; }
    .inquiry-item:hover { background: #f9fafb; }
    .inquiry-info { flex: 1; }
    .inquiry-title { font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 4px; }
    .inquiry-meta { font-size: 13px; color: #6b7280; }
    .status-badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .status-badge.pending { background: #fef3c7; color: #92400e; }
    .status-badge.answered { background: #ddd6fe; color: #5b21b6; }
    .status-badge.closed { background: #d1fae5; color: #065f46; }
    .btn { padding: 8px 16px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-block; }
    .btn-primary { background: #6366f1; color: white; }
    .btn-primary:hover { background: #4f46e5; }
    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
</style>

<div class="admin-content">
    <div class="page-header">
        <h1>판매자 1:1 문의 관리</h1>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- 통계 -->
    <div class="stats-grid">
        <div class="stat-card pending">
            <div class="stat-label">답변 대기</div>
            <div class="stat-value"><?php echo $stats['pending']; ?></div>
        </div>
        <div class="stat-card answered">
            <div class="stat-label">답변 완료</div>
            <div class="stat-value"><?php echo $stats['answered']; ?></div>
        </div>
        <div class="stat-card closed">
            <div class="stat-label">확인 완료</div>
            <div class="stat-value"><?php echo $stats['closed']; ?></div>
        </div>
    </div>

    <!-- 필터 -->
    <div class="card">
        <div class="filter-section">
            <label>상태:</label>
            <select onchange="applyFilter()" id="statusFilter">
                <option value="">전체</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>답변 대기</option>
                <option value="answered" <?php echo $status === 'answered' ? 'selected' : ''; ?>>답변 완료</option>
                <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>확인 완료</option>
            </select>
            <label>판매자 ID:</label>
            <input type="text" id="sellerFilter" placeholder="판매자 ID" value="<?php echo htmlspecialchars($sellerId); ?>" onkeypress="if(event.key==='Enter') applyFilter()">
            <button onclick="applyFilter()" class="btn btn-primary">필터 적용</button>
            <a href="?" class="btn btn-secondary">초기화</a>
        </div>
    </div>

    <!-- 문의 목록 -->
    <div class="card inquiry-list">
        <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 16px; color: #1f2937;">문의 목록 (총 <?php echo number_format($totalInquiries); ?>개)</h2>
        <?php if (empty($inquiries)): ?>
            <div style="padding: 40px; text-align: center; color: #6b7280;">
                등록된 문의가 없습니다.
            </div>
        <?php else: ?>
            <?php foreach ($inquiries as $inquiry): ?>
                <div class="inquiry-item">
                    <div class="inquiry-info">
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
                            <?php if ($inquiry['attachment_count'] > 0): ?>
                                <span style="color: #6366f1; font-size: 12px;">📎 <?php echo $inquiry['attachment_count']; ?>개</span>
                            <?php endif; ?>
                        </div>
                        <div class="inquiry-meta">
                            <span>판매자: <?php echo htmlspecialchars($inquiry['seller_name'] ?? $inquiry['seller_id']); ?></span>
                            <span>작성일: <?php echo date('Y-m-d H:i', strtotime($inquiry['created_at'])); ?></span>
                            <?php if (!empty($inquiry['admin_viewed_at'])): ?>
                                <span style="color: #f59e0b;">✓ 확인: <?php echo date('Y-m-d H:i', strtotime($inquiry['admin_viewed_at'])); ?></span>
                            <?php endif; ?>
                            <?php if ($inquiry['reply_count'] > 0): ?>
                                <span>답변: <?php echo $inquiry['reply_count']; ?>개</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="?detail=<?php echo $inquiry['id']; ?>" class="btn btn-primary">상세보기</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- 페이지네이션 -->
    <?php if ($totalPages > 1): ?>
        <div style="margin-top: 24px; text-align: center;">
            <?php
            $queryParams = [];
            if ($status) $queryParams['status'] = $status;
            if ($sellerId) $queryParams['seller_id'] = $sellerId;
            $queryParams['per_page'] = $perPage;
            $queryString = http_build_query($queryParams);
            ?>
            <?php if ($page > 1): ?>
                <a href="?<?php echo $queryString; ?>&page=<?php echo $page - 1; ?>" class="btn btn-secondary">이전</a>
            <?php endif; ?>
            <span style="margin: 0 16px;"><?php echo $page; ?> / <?php echo $totalPages; ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="?<?php echo $queryString; ?>&page=<?php echo $page + 1; ?>" class="btn btn-secondary">다음</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($detailId): ?>
    <?php
    $detailInquiry = getSellerInquiryById($detailId);
    $detailReplies = getSellerInquiryReplies($detailId);
    $detailAttachments = getSellerInquiryAttachments($detailId);
    ?>
    <!-- 상세 모달 -->
    <div id="detailModal" style="display: block; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000; overflow-y: auto;">
        <div style="max-width: 900px; margin: 40px auto; background: white; border-radius: 12px; padding: 32px; position: relative;">
            <button onclick="closeModal()" style="position: absolute; top: 16px; right: 16px; background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280;">×</button>
            
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 24px; color: #1f2937;">문의 상세</h2>
            
            <div style="margin-bottom: 24px; padding: 16px; background: #f9fafb; border-radius: 8px;">
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 12px; color: #1f2937;">
                    <?php echo htmlspecialchars($detailInquiry['title']); ?>
                </div>
                <div style="font-size: 14px; color: #6b7280; margin-bottom: 16px;">
                    판매자: <?php echo htmlspecialchars($detailInquiry['seller_name'] ?? $detailInquiry['seller_id']); ?> | 
                    작성일: <?php echo date('Y-m-d H:i', strtotime($detailInquiry['created_at'])); ?>
                </div>
                <div style="font-size: 15px; line-height: 1.8; color: #374151; white-space: pre-wrap;">
                    <?php echo nl2br(htmlspecialchars($detailInquiry['content'])); ?>
                </div>
                
                <?php if (!empty($detailAttachments)): ?>
                    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                        <div style="font-weight: 600; margin-bottom: 8px; color: #1f2937;">첨부파일:</div>
                        <?php foreach ($detailAttachments as $attachment): ?>
                            <div style="margin-bottom: 8px;">
                                <a href="/MVNO/admin/content/seller-inquiry-download.php?file_id=<?php echo $attachment['id']; ?>" target="_blank" style="color: #6366f1; text-decoration: none;">
                                    📎 <?php echo htmlspecialchars($attachment['file_name']); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 답변 목록 -->
            <?php if (!empty($detailReplies)): ?>
                <div style="margin-bottom: 24px;">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px; color: #1f2937;">답변</h3>
                    <?php foreach ($detailReplies as $reply): ?>
                        <div style="padding: 16px; background: #f9fafb; border-radius: 8px; margin-bottom: 12px;">
                            <div style="font-weight: 600; margin-bottom: 8px; color: #6366f1;">
                                <?php echo htmlspecialchars($reply['author_name'] ?? '알 수 없음'); ?> (관리자)
                                <span style="font-size: 13px; font-weight: 400; color: #6b7280; margin-left: 8px;">
                                    <?php echo date('Y-m-d H:i', strtotime($reply['created_at'])); ?>
                                </span>
                            </div>
                            <div style="font-size: 15px; line-height: 1.8; color: #374151; white-space: pre-wrap;">
                                <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                            </div>
                            <?php
                            $replyAttachments = getSellerInquiryAttachments($detailId, $reply['id']);
                            if (!empty($replyAttachments)):
                            ?>
                                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
                                    <?php foreach ($replyAttachments as $attachment): ?>
                                        <div style="margin-bottom: 8px;">
                                            <a href="/MVNO/admin/content/seller-inquiry-download.php?file_id=<?php echo $attachment['id']; ?>" target="_blank" style="color: #6366f1; text-decoration: none;">
                                                📎 <?php echo htmlspecialchars($attachment['file_name']); ?>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- 답변 작성 폼 -->
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="inquiry_id" value="<?php echo $detailId; ?>">
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #374151;">답변 작성</label>
                    <textarea name="content" required style="width: 100%; min-height: 150px; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; font-family: inherit;" placeholder="답변 내용을 입력하세요"></textarea>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #374151;">첨부파일</label>
                    <input type="file" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.hwp" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;">
                    <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">최대 5개, 총 20MB까지</div>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary">답변 등록</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">닫기</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
function applyFilter() {
    const status = document.getElementById('statusFilter').value;
    const sellerId = document.getElementById('sellerFilter').value;
    const params = new URLSearchParams();
    if (status) params.set('status', status);
    if (sellerId) params.set('seller_id', sellerId);
    window.location.href = '?' + params.toString();
}

function closeModal() {
    const params = new URLSearchParams(window.location.search);
    params.delete('detail');
    window.location.href = '?' + params.toString();
}
</script>

<?php include '../includes/admin-footer.php'; ?>

