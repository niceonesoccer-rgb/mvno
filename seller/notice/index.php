<?php
/**
 * 판매자 전용 공지사항 목록 페이지
 * 경로: /MVNO/seller/notice/
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/notice-functions.php';

// 세션 시작
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

// 현재 페이지 설정
$current_page = 'seller';
$is_main_page = false;

// 페이지별 스타일
$pageStyles = '
    .seller-notice-container {
        max-width: 720px;
        margin: 0 auto;
        padding: 40px 24px;
    }
    
    .notice-header {
        margin-bottom: 32px;
    }
    
    .notice-header h1 {
        font-size: 32px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .notice-header p {
        font-size: 16px;
        color: #6b7280;
    }
    
    .notice-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .notice-item {
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        transition: all 0.2s;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .notice-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .notice-item-image {
        width: 100%;
        height: auto;
        display: block;
        object-fit: contain;
    }
    
    .notice-item-content {
        padding: 20px;
    }
    
    .notice-item-title {
        font-size: 18px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 12px;
        line-height: 1.4;
    }
    
    .notice-item-text {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 16px;
        line-height: 1.6;
    }
    
    .notice-item-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 13px;
        color: #9ca3af;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 32px;
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
        background: #6366f1;
        color: white;
        border-color: #6366f1;
    }
    
    .pagination .disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
    }
    
    .empty-state svg {
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        opacity: 0.5;
        color: #9ca3af;
    }
    
    .empty-state p {
        color: #9ca3af;
        font-size: 16px;
    }
';

include '../includes/seller-header.php';

// 페이지네이션 (10개씩 고정)
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10; // 10개씩 고정
$offset = ($page - 1) * $per_page;

// 판매자 전용 공지사항 목록 가져오기 (DB 레벨 페이지네이션)
$total = getSellerNoticesCount();
$notices = getSellerNotices($per_page, $offset);
$total_pages = ceil($total / $per_page);
?>

<div class="seller-notice-container">
    <div class="notice-header">
        <h1>공지사항</h1>
        <p>판매자 전용 공지사항을 확인하세요.</p>
    </div>
    
    <?php if (empty($notices)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <p>등록된 공지사항이 없습니다.</p>
        </div>
    <?php else: ?>
        <div class="notice-list">
            <?php foreach ($notices as $notice): 
                $has_image = !empty($notice['image_url']);
                $has_content = !empty($notice['content']);
            ?>
                <a href="/MVNO/seller/notice/detail.php?id=<?= htmlspecialchars($notice['id']) ?>" class="notice-item">
                    <?php if ($has_image): ?>
                        <img src="<?= htmlspecialchars($notice['image_url']) ?>" 
                             alt="<?= htmlspecialchars($notice['title']) ?>" 
                             class="notice-item-image">
                    <?php endif; ?>
                    
                    <div class="notice-item-content">
                        <h3 class="notice-item-title"><?= htmlspecialchars($notice['title']) ?></h3>
                        
                        <?php if ($has_content): ?>
                            <p class="notice-item-text">
                                <?= htmlspecialchars(mb_substr(strip_tags($notice['content']), 0, 150)) ?>
                                <?= mb_strlen(strip_tags($notice['content'])) > 150 ? '...' : '' ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="notice-item-meta">
                            <span><?= date('Y년 m월 d일', strtotime($notice['created_at'])) ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>">이전</a>
                <?php else: ?>
                    <span class="disabled">이전</span>
                <?php endif; ?>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($total_pages, $page + 2);
                
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
                
                <?php if ($endPage < $total_pages): ?>
                    <?php if ($endPage < $total_pages - 1): ?>
                        <span>...</span>
                    <?php endif; ?>
                    <a href="?page=<?= $total_pages ?>"><?= $total_pages ?></a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>">다음</a>
                <?php else: ?>
                    <span class="disabled">다음</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/seller-footer.php'; ?>

