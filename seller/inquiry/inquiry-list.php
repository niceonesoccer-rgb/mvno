<?php
/**
 * 판매자 1:1 문의 목록 페이지
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

// 페이지네이션
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 문의 목록 가져오기
$inquiries = getSellerInquiriesBySeller($sellerId, $perPage, $offset);

// 상태별 통계 (DB에서 직접 COUNT)
$pdo = getDBConnection();
$stats = [
    'pending' => 0,
    'answered' => 0
];

if ($pdo) {
    // pending 개수
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM seller_inquiries WHERE seller_id = ? AND status = 'pending'");
    $stmt->execute([$sellerId]);
    $stats['pending'] = $stmt->fetchColumn();
    
    // answered 개수 (closed도 answered로 카운트)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM seller_inquiries WHERE seller_id = ? AND (status = 'answered' OR status = 'closed')");
    $stmt->execute([$sellerId]);
    $stats['answered'] = $stmt->fetchColumn();
    
    // 전체 개수
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM seller_inquiries WHERE seller_id = ?");
    $stmt->execute([$sellerId]);
    $totalInquiries = $stmt->fetchColumn();
} else {
    // DB 연결 실패 시 기존 방식 사용
    $allInquiries = getSellerInquiriesBySeller($sellerId);
    $totalInquiries = count($allInquiries);
    foreach ($allInquiries as $inq) {
        $status = $inq['status'];
        if ($status === 'closed') {
            $status = 'answered'; // closed는 answered로 카운트
        }
        if (isset($stats[$status])) {
            $stats[$status] = $stats[$status] + 1;
        }
    }
}

$totalPages = ceil($totalInquiries / $perPage);

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
    
    .inquiry-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .inquiry-item {
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .inquiry-item:hover {
        background: #f9fafb;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
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
    
    .inquiry-number {
        color: #6366f1;
        font-weight: 700;
        font-size: 14px;
        min-width: 40px;
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
        align-items: center;
        gap: 8px;
        margin-top: 32px;
        padding: 20px 0;
    }
    
    .pagination a, .pagination span {
        padding: 10px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        text-decoration: none;
        color: #374151;
        background: white;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
        min-width: 40px;
        text-align: center;
    }
    
    .pagination a:hover {
        background: #f3f4f6;
        border-color: #6366f1;
        color: #6366f1;
    }
    
    .pagination .active {
        background: #6366f1;
        color: white;
        border-color: #6366f1;
        font-weight: 600;
    }
    
    .pagination span:not(.active) {
        color: #9ca3af;
        cursor: default;
    }
</style>

<div class="inquiry-container">
    <div class="page-header">
        <h1>1:1 문의</h1>
        <a href="<?php echo getAssetPath('/seller/inquiry/inquiry-write.php'); ?>" class="btn-primary">+ 문의하기</a>
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
    </div>
    
    <!-- 문의 목록 -->
    <div class="inquiry-list">
        <?php if (empty($inquiries)): ?>
            <div class="empty-state">
                <p style="font-size: 18px; margin-bottom: 8px;">등록된 문의가 없습니다.</p>
                <p style="font-size: 14px; color: #9ca3af;">관리자에게 문의하고 싶은 내용을 작성해주세요.</p>
            </div>
        <?php else: ?>
            <?php 
            $index = 0;
            foreach ($inquiries as $inquiry): 
                $index++;
                // 역순 번호: 전체 개수에서 현재 위치를 빼서 계산
                $inquiryNumber = $totalInquiries - (($page - 1) * $perPage + $index - 1);
            ?>
                <a href="<?php echo getAssetPath('/seller/inquiry/inquiry-detail.php'); ?>?id=<?php echo $inquiry['id']; ?>" style="text-decoration: none; color: inherit; display: block;">
                    <div class="inquiry-item">
                        <div class="inquiry-info">
                            <div class="inquiry-title">
                                <span class="inquiry-number"><?php echo $inquiryNumber; ?></span>
                                <span class="status-badge <?php echo $inquiry['status']; ?>">
                                    <?php
                                    $statusText = [
                                        'pending' => '답변 대기',
                                        'answered' => '답변 완료',
                                        'closed' => '답변 완료' // 기존 데이터 호환성
                                    ];
                                    echo isset($statusText[$inquiry['status']]) ? $statusText[$inquiry['status']] : '답변 대기';
                                    ?>
                                </span>
                                <?php echo htmlspecialchars($inquiry['title']); ?>
                                <?php if ($inquiry['attachment_count'] > 0): ?>
                                    <span class="attachment-icon">📎 <?php echo $inquiry['attachment_count']; ?>개</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="color: #6b7280; font-size: 14px;"><?php echo date('Y-m-d', strtotime($inquiry['created_at'])); ?></div>
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

