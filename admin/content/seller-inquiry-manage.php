<?php
/**
 * 판매자 1:1 문의 관리 페이지
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/seller-inquiry-functions.php';
require_once __DIR__ . '/../../includes/data/path-config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdmin()) {
    header('Location: ' . getAssetPath('/admin/'));
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
                    
                    // DB 경로를 실제 파일 시스템 경로로 변환
                    // DB 경로: /uploads/... -> 실제 경로: __DIR__/../../uploads/...
                    $oldDbPath = $attachment['file_path'];
                    $newDbPath = $newPath;
                    // 하드코딩된 /MVNO/ 제거
                    $oldActualPath = preg_replace('#^/MVNO/#', '/', $oldDbPath);
                    $oldActualPath = preg_replace('#^/MVNO#', '', $oldActualPath);
                    $newActualPath = preg_replace('#^/MVNO/#', '/', $newDbPath);
                    $newActualPath = preg_replace('#^/MVNO#', '', $newActualPath);
                    // __DIR__은 admin/content이므로 ../../로 루트로 이동
                    $oldPath = __DIR__ . '/../..' . $oldActualPath;
                    $newFullPath = __DIR__ . '/../..' . $newActualPath;
                    
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
            
            // 답변 등록 성공 시 모달 닫고 성공 메시지 표시
            header('Location: ?detail=' . $inquiryId . '&reply_success=1');
            exit;
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

// 전체 개수 조회 (효율적으로)
$pdo = getDBConnection();
$countQuery = "SELECT COUNT(*) FROM seller_inquiries WHERE 1=1";
$countParams = [];
if ($status) {
    $countQuery .= " AND status = ?";
    $countParams[] = $status;
}
if ($sellerId) {
    $countQuery .= " AND seller_id = ?";
    $countParams[] = $sellerId;
}
$stmt = $pdo->prepare($countQuery);
$stmt->execute($countParams);
$totalInquiries = $stmt->fetchColumn();
$totalPages = ceil($totalInquiries / $perPage);

// 상태별 통계
$stats = [
    'pending' => 0,
    'answered' => 0
];
$allStats = getAllSellerInquiries();
foreach ($allStats as $inq) {
    if (isset($stats[$inq['status']])) {
        $stats[$inq['status']] = $stats[$inq['status']] + 1;
    }
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
    /* closed 상태 제거됨 */
    .filter-section { display: flex; gap: 12px; margin-bottom: 24px; align-items: center; }
    .filter-section select, .filter-section input { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
    .inquiry-list { margin-top: 24px; }
    .inquiry-item { padding: 16px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
    .inquiry-item:last-child { border-bottom: none; }
    .inquiry-item:hover { background: #f9fafb; }
    .inquiry-info { flex: 1; }
    .inquiry-title { font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
    .inquiry-number { color: #6366f1; font-weight: 700; font-size: 14px; min-width: 40px; }
    .inquiry-meta { font-size: 13px; color: #6b7280; }
    .status-badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .status-badge.pending { background: #fef3c7; color: #92400e; }
    .status-badge.answered { background: #ddd6fe; color: #5b21b6; }
    /* closed 상태 제거됨 */
    .btn { padding: 8px 16px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-block; }
    .btn-primary { background: #6366f1; color: white; }
    .btn-primary:hover { background: #4f46e5; }
    
    /* 페이지네이션 스타일 */
    .pagination-container {
        margin-top: 32px;
        padding: 24px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
    }
    
    .pagination-btn {
        min-width: 40px;
        height: 40px;
        padding: 0 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: white;
        color: #374151;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .pagination-btn:hover:not(.disabled):not(.active) {
        background: #f3f4f6;
        border-color: #6366f1;
        color: #6366f1;
        transform: translateY(-1px);
    }
    
    .pagination-btn.active {
        background: #6366f1;
        border-color: #6366f1;
        color: white;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
    }
    
    .pagination-btn.disabled {
        background: #f9fafb;
        color: #d1d5db;
        cursor: not-allowed;
        border-color: #e5e7eb;
    }
    
    .pagination-ellipsis {
        padding: 0 8px;
        color: #6b7280;
        font-weight: 600;
    }
    
    .pagination-info {
        text-align: center;
        margin-top: 16px;
        font-size: 14px;
        color: #6b7280;
    }
    
    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    
    /* 파일 업로드 영역 스타일 */
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
        cursor: pointer;
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
    
    /* 첨부파일 표시 영역 */
    .attachment-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 12px;
        margin-top: 12px;
    }
    
    .attachment-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px;
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
    }
    
    .attachment-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-color: #6366f1;
    }
    
    .attachment-preview {
        width: 100%;
        height: 100px;
        object-fit: cover;
        border-radius: 6px;
        margin-bottom: 8px;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        color: #9ca3af;
        cursor: pointer;
    }
    
    .attachment-name {
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-bottom: 4px;
    }
    
    .attachment-size {
        font-size: 11px;
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
    </div>

    <!-- 필터 -->
    <div class="card">
        <div class="filter-section">
            <label>상태:</label>
            <select onchange="applyFilter()" id="statusFilter">
                <option value="">전체</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>답변 대기</option>
                <option value="answered" <?php echo $status === 'answered' ? 'selected' : ''; ?>>답변 완료</option>
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
            <?php 
            $index = 0;
            foreach ($inquiries as $inquiry): 
                $index++;
                // 역순 번호: 전체 개수에서 현재 위치를 빼서 계산
                $inquiryNumber = $totalInquiries - (($page - 1) * $perPage + $index - 1);
            ?>
                <div class="inquiry-item">
                    <div class="inquiry-info">
                        <div class="inquiry-title">
                            <span class="inquiry-number"><?php echo $inquiryNumber; ?></span>
                            <span class="status-badge <?php echo $inquiry['status']; ?>">
                                <?php
                                $statusText = [
                                    'pending' => '답변 대기',
                                    'answered' => '답변 완료',
                                    'closed' => '답변 완료' // 이전 데이터 호환성
                                ];
                                echo $statusText[$inquiry['status']] ?? '답변 완료';
                                ?>
                            </span>
                            <?php echo htmlspecialchars($inquiry['title']); ?>
                            <?php if ($inquiry['attachment_count'] > 0): ?>
                                <span style="color: #6366f1; font-size: 12px;">📎 <?php echo $inquiry['attachment_count']; ?>개</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="display: flex; align-items: center; gap: 8px; color: #6b7280; font-size: 14px;">
                            <a href="<?php echo getAssetPath('/admin/users/seller-detail.php?user_id=' . urlencode($inquiry['seller_id'])); ?>" 
                               style="color: #6366f1; text-decoration: none; font-weight: 500;"
                               onmouseover="this.style.textDecoration='underline'"
                               onmouseout="this.style.textDecoration='none'"
                               title="판매자 정보 보기">
                                <?php echo htmlspecialchars($inquiry['seller_name'] ?? $inquiry['seller_id']); ?>
                            </a>
                            <span>|</span>
                            <span><?php echo date('Y-m-d', strtotime($inquiry['created_at'])); ?></span>
                        </div>
                        <a href="?detail=<?php echo $inquiry['id']; ?>" class="btn btn-primary">상세보기</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- 페이지네이션 -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination-container">
            <?php
            $queryParams = [];
            if ($status) $queryParams['status'] = $status;
            if ($sellerId) $queryParams['seller_id'] = $sellerId;
            $queryParams['per_page'] = $perPage;
            $queryString = http_build_query($queryParams);
            
            // 페이지 번호 범위 계산
            $showPages = 5; // 양쪽에 표시할 페이지 수
            $startPage = max(1, $page - $showPages);
            $endPage = min($totalPages, $page + $showPages);
            ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo $queryString; ?>&page=1" class="pagination-btn" title="첫 페이지">«</a>
                    <a href="?<?php echo $queryString; ?>&page=<?php echo $page - 1; ?>" class="pagination-btn" title="이전">‹</a>
                <?php else: ?>
                    <span class="pagination-btn disabled">«</span>
                    <span class="pagination-btn disabled">‹</span>
                <?php endif; ?>
                
                <?php if ($startPage > 1): ?>
                    <a href="?<?php echo $queryString; ?>&page=1" class="pagination-btn">1</a>
                    <?php if ($startPage > 2): ?>
                        <span class="pagination-ellipsis">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="pagination-btn active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?php echo $queryString; ?>&page=<?php echo $i; ?>" class="pagination-btn"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <span class="pagination-ellipsis">...</span>
                    <?php endif; ?>
                    <a href="?<?php echo $queryString; ?>&page=<?php echo $totalPages; ?>" class="pagination-btn"><?php echo $totalPages; ?></a>
                <?php endif; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo $queryString; ?>&page=<?php echo $page + 1; ?>" class="pagination-btn" title="다음">›</a>
                    <a href="?<?php echo $queryString; ?>&page=<?php echo $totalPages; ?>" class="pagination-btn" title="마지막 페이지">»</a>
                <?php else: ?>
                    <span class="pagination-btn disabled">›</span>
                    <span class="pagination-btn disabled">»</span>
                <?php endif; ?>
            </div>
            <div class="pagination-info">
                총 <?php echo number_format($totalInquiries); ?>개 중 <?php echo number_format(($page - 1) * $perPage + 1); ?>-<?php echo number_format(min($page * $perPage, $totalInquiries)); ?>개 표시
            </div>
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
                    <?php echo date('Y-m-d', strtotime($detailInquiry['created_at'])); ?>
                </div>
                <div style="font-size: 15px; line-height: 1.8; color: #374151; white-space: pre-wrap;">
                    <?php echo nl2br(htmlspecialchars($detailInquiry['content'])); ?>
                </div>
                
                <?php if (!empty($detailAttachments)): ?>
                    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                        <div style="font-weight: 600; margin-bottom: 12px; color: #1f2937; font-size: 14px;">첨부파일 (<?php echo count($detailAttachments); ?>개)</div>
                        <div class="attachment-grid">
                            <?php foreach ($detailAttachments as $attachment): ?>
                                <?php
                                // DB 경로를 실제 파일 시스템 경로로 변환
                                $dbPath = $attachment['file_path'];
                                // 하드코딩된 /MVNO/ 제거
                                $actualPath = preg_replace('#^/MVNO/#', '/', $dbPath);
                                $actualPath = preg_replace('#^/MVNO#', '', $actualPath);
                                $filePath = __DIR__ . '/../..' . $actualPath;
                                $fileExists = file_exists($filePath);
                                $isImage = strpos($attachment['file_type'], 'image/') === 0;
                                $fileUrl = getAssetPath('/admin/content/seller-inquiry-download.php?file_id=' . $attachment['id']);
                                ?>
                                <div class="attachment-card">
                                    <a href="<?php echo $fileUrl; ?>" target="_blank" class="attachment-link">
                                        <?php if ($isImage && $fileExists): ?>
                                            <img src="<?php echo $fileUrl; ?>" alt="<?php echo htmlspecialchars($attachment['file_name']); ?>" class="attachment-preview" onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'attachment-preview\'>🖼️</div><div class=\'attachment-name\'><?php echo htmlspecialchars($attachment['file_name']); ?></div><div class=\'attachment-size\'><?php echo number_format($attachment['file_size'] / 1024, 1); ?> KB</div>';">
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
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
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
                                관리자
                                <span style="font-size: 13px; font-weight: 400; color: #6b7280; margin-left: 8px;">
                                    <?php echo date('Y-m-d', strtotime($reply['created_at'])); ?>
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
                                    <div style="font-weight: 600; margin-bottom: 8px; color: #1f2937; font-size: 13px;">첨부파일 (<?php echo count($replyAttachments); ?>개)</div>
                                    <div class="attachment-grid">
                                        <?php foreach ($replyAttachments as $attachment): ?>
                                            <?php
                                            $isImage = strpos($attachment['file_type'], 'image/') === 0;
                                            $fileUrl = getAssetPath('/admin/content/seller-inquiry-download.php?file_id=' . $attachment['id']);
                                            ?>
                                            <div class="attachment-card">
                                                <a href="<?php echo $fileUrl; ?>" target="_blank" class="attachment-link">
                                                    <?php if ($isImage): ?>
                                                        <img src="<?php echo $fileUrl; ?>" alt="<?php echo htmlspecialchars($attachment['file_name']); ?>" class="attachment-preview" onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'attachment-preview\'>🖼️</div><div class=\'attachment-name\'><?php echo htmlspecialchars($attachment['file_name']); ?></div><div class=\'attachment-size\'><?php echo number_format($attachment['file_size'] / 1024, 1); ?> KB</div>';">
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
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- 답변 작성 폼 -->
            <form method="POST" enctype="multipart/form-data" id="replyForm">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="inquiry_id" value="<?php echo $detailId; ?>">
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #374151;">답변 작성</label>
                    <textarea name="content" required style="width: 100%; min-height: 150px; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; font-family: inherit;" placeholder="답변 내용을 입력하세요"></textarea>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #374151;">첨부파일</label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <input type="file" id="attachments" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.hwp" style="display: none;">
                        <span class="file-upload-icon">📁</span>
                        <div class="file-upload-text">파일을 드래그하거나 클릭하여 업로드</div>
                        <div class="file-upload-hint">이미지, PDF, 문서 파일 (최대 5개, 총 20MB)</div>
                    </div>
                    <div class="file-list" id="fileList"></div>
                    <div style="font-size: 13px; color: #6b7280; margin-top: 8px;">
                        • 지원 형식: JPG, PNG, GIF, WEBP, PDF, DOC, DOCX, XLS, XLSX, HWP<br>
                        • 최대 5개 파일, 총 20MB까지 업로드 가능
                    </div>
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
    params.delete('reply_success');
    window.location.href = '?' + params.toString();
}

// 답변 등록 성공 처리
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('reply_success') === '1') {
        // 성공 메시지 모달 표시
        showSuccessModal();
    }
});

function showSuccessModal() {
    // 기존 모달이 있으면 제거
    const existingModal = document.getElementById('successModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // 성공 모달 생성
    const modal = document.createElement('div');
    modal.id = 'successModal';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 3000; display: flex; align-items: center; justify-content: center;';
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; padding: 32px; max-width: 400px; text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,0.2);">
            <div style="font-size: 48px; margin-bottom: 16px; color: #10b981;">✓</div>
            <h3 style="font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">답변이 등록되었습니다</h3>
            <p style="color: #6b7280; margin-bottom: 24px;">답변이 성공적으로 등록되었습니다.</p>
            <button onclick="closeSuccessModal()" style="padding: 12px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; width: 100%;">
                확인
            </button>
        </div>
    `;
    document.body.appendChild(modal);
    
    // 2초 후 자동으로 닫기
    setTimeout(function() {
        closeSuccessModal();
    }, 2000);
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.remove();
    }
    
    // 문의 상세 모달도 닫기
    closeModal();
}

// 파일 업로드 기능
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('attachments');
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileList = document.getElementById('fileList');
    
    if (!fileInput || !fileUploadArea || !fileList) return;
    
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
    }
    
    function updateFileList() {
        fileList.innerHTML = '';
        
        if (selectedFiles.length > 0) {
            fileUploadArea.classList.add('has-files');
        } else {
            fileUploadArea.classList.remove('has-files');
        }
        
        selectedFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
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
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
    }
    
    window.removeFile = function(index) {
        selectedFiles.splice(index, 1);
        updateFileList();
    };
    
    function getFileIcon(mimeType) {
        if (mimeType.startsWith('image/')) return '🖼️';
        if (mimeType === 'application/pdf') return '📄';
        if (mimeType.includes('word') || mimeType.includes('document')) return '📝';
        if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return '📊';
        if (mimeType.includes('hwp')) return '📋';
        return '📎';
    }
    
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
});
</script>

<?php include '../includes/admin-footer.php'; ?>

