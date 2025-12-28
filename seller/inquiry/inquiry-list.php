<?php
/**
 * 판매자 1:1 문의 목록 페이지
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

// 페이지네이션
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 문의 목록 가져오기
$inquiries = getSellerInquiriesBySeller($sellerId, $perPage, $offset);
$totalInquiries = count(getSellerInquiriesBySeller($sellerId));
$totalPages = ceil($totalInquiries / $perPage);

// 상태별 통계
$stats = [
    'pending' => 0,
    'answered' => 0,
    'closed' => 0
];
$allInquiries = getSellerInquiriesBySeller($sellerId);
foreach ($allInquiries as $inq) {
    $stats[$inq['status']] = $stats[$inq['status']] + 1;
}

$currentPage = 'inquiry-list.php';
include '../includes/seller-header.php';
?>

<style>
    .inquiry-container {
        max-width: 1200px;
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
    
    .btn-primary {
        padding: 12px 24px;
        background: #6366f1;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        display: inline-block;
        transition: all 0.2s;
    }
    
    .btn-primary:hover {
        background: #4f46e5;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 32px;
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .stat-label {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 8px;
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #1f2937;
    }
    
    .stat-card.pending .stat-value {
        color: #f59e0b;
    }
    
    .stat-card.answered .stat-value {
        color: #6366f1;
    }
    
    .stat-card.closed .stat-value {
        color: #10b981;
    }
    
    .inquiry-list {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }
    
    .inquiry-item {
        padding: 20px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.2s;
    }
    
    .inquiry-item:last-child {
        border-bottom: none;
    }
    
    .inquiry-item:hover {
        background: #f9fafb;
    }
    
    .inquiry-info {
        flex: 1;
    }
    
    .inquiry-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .inquiry-meta {
        font-size: 14px;
        color: #6b7280;
        display: flex;
        gap: 16px;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
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
    
    .attachment-icon {
        color: #6366f1;
        font-size: 14px;
    }
    
    .empty-state {
        padding: 60px 20px;
        text-align: center;
        color: #6b7280;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 32px;
    }
    
    .pagination a, .pagination span {
        padding: 8px 16px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        text-decoration: none;
        color: #374151;
    }
    
    .pagination a:hover {
        background: #f3f4f6;
    }
    
    .pagination .active {
        background: #6366f1;
        color: white;
        border-color: #6366f1;
    }
</style>

<div class="inquiry-container">
    <div class="page-header">
        <h1>1:1 문의</h1>
        <a href="/MVNO/seller/inquiry/inquiry-write.php" class="btn-primary">+ 문의하기</a>
    </div>
    
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
    
    <!-- 문의 목록 -->
    <div class="inquiry-list">
        <?php if (empty($inquiries)): ?>
            <div class="empty-state">
                <p style="font-size: 18px; margin-bottom: 8px;">등록된 문의가 없습니다.</p>
                <p style="font-size: 14px; color: #9ca3af;">관리자에게 문의하고 싶은 내용을 작성해주세요.</p>
            </div>
        <?php else: ?>
            <?php foreach ($inquiries as $inquiry): ?>
                <a href="/MVNO/seller/inquiry/inquiry-detail.php?id=<?php echo $inquiry['id']; ?>" style="text-decoration: none; color: inherit;">
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
                                    <span class="attachment-icon">📎 <?php echo $inquiry['attachment_count']; ?>개</span>
                                <?php endif; ?>
                            </div>
                            <div class="inquiry-meta">
                                <span>작성일: <?php echo date('Y-m-d H:i', strtotime($inquiry['created_at'])); ?></span>
                                <?php if ($inquiry['reply_count'] > 0): ?>
                                    <span>답변: <?php echo $inquiry['reply_count']; ?>개</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="color: #9ca3af;">→</div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- 페이지네이션 -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">이전</a>
            <?php endif; ?>
            
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            if ($startPage > 1):
            ?>
                <a href="?page=1">1</a>
                <?php if ($startPage > 2): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                    <span>...</span>
                <?php endif; ?>
                <a href="?page=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>">다음</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/seller-footer.php'; ?>

