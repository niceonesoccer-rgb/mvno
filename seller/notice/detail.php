<?php
/**
 * 판매자 전용 공지사항 상세 페이지
 * 경로: /MVNO/seller/notice/detail.php
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

// 공지사항 ID 확인
$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: /MVNO/seller/notice/');
    exit;
}

// 공지사항 가져오기
$notice = getNoticeById($id);
if (!$notice) {
    header('Location: /MVNO/seller/notice/');
    exit;
}

// 판매자 전용 공지사항 권한 체크
$targetAudience = $notice['target_audience'] ?? 'all';
if ($targetAudience !== 'seller') {
    // 판매자 전용 공지사항이 아니면 목록으로 리다이렉트
    header('Location: /MVNO/seller/notice/');
    exit;
}

// 조회수 증가
incrementNoticeViews($id);
$notice = getNoticeById($id); // 업데이트된 조회수 가져오기

// 현재 페이지 설정
$current_page = 'seller';
$is_main_page = false;

// 페이지별 스타일
$pageStyles = '
    .seller-notice-detail-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 24px;
    }
    
    .notice-detail-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 24px;
        color: #6366f1;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: opacity 0.2s;
    }
    
    .notice-detail-back:hover {
        opacity: 0.8;
    }
    
    .notice-detail-article {
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 32px;
    }
    
    .notice-detail-header {
        margin-bottom: 24px;
        padding-bottom: 24px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .notice-detail-title {
        font-size: 28px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 16px;
        line-height: 1.4;
    }
    
    .notice-detail-meta {
        display: flex;
        align-items: center;
        gap: 16px;
        font-size: 14px;
        color: #6b7280;
    }
    
    .notice-detail-image {
        margin-bottom: 24px;
    }
    
    .notice-detail-image img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
    }
    
    .notice-detail-content {
        font-size: 16px;
        line-height: 1.8;
        color: #374151;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    
    .notice-detail-footer {
        margin-top: 32px;
        text-align: center;
    }
    
    .notice-detail-footer a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 24px;
        background: #6366f1;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: background-color 0.2s;
    }
    
    .notice-detail-footer a:hover {
        background: #4f46e5;
    }
';

include '../includes/seller-header.php';
?>

<div class="seller-notice-detail-container">
    <a href="/MVNO/seller/notice/" class="notice-detail-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 18L9 12L15 6"/>
        </svg>
        목록으로
    </a>
    
    <article class="notice-detail-article">
        <header class="notice-detail-header">
            <h1 class="notice-detail-title"><?= htmlspecialchars($notice['title']) ?></h1>
            <div class="notice-detail-meta">
                <span>작성일: <?= date('Y년 m월 d일', strtotime($notice['created_at'])) ?></span>
                <?php if (isset($notice['views'])): ?>
                    <span>조회수: <?= number_format($notice['views']) ?></span>
                <?php endif; ?>
            </div>
        </header>
        
        <?php if (!empty($notice['image_url'])): ?>
            <div class="notice-detail-image">
                <?php if (!empty($notice['link_url'])): ?>
                    <a href="<?= htmlspecialchars($notice['link_url']) ?>" target="_blank">
                        <img src="<?= htmlspecialchars($notice['image_url']) ?>" 
                             alt="<?= htmlspecialchars($notice['title']) ?>">
                    </a>
                <?php else: ?>
                    <img src="<?= htmlspecialchars($notice['image_url']) ?>" 
                         alt="<?= htmlspecialchars($notice['title']) ?>">
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($notice['content'])): ?>
            <div class="notice-detail-content">
                <?= nl2br(htmlspecialchars($notice['content'])) ?>
            </div>
        <?php endif; ?>
    </article>
    
    <div class="notice-detail-footer">
        <a href="/MVNO/seller/notice/">목록으로 돌아가기</a>
    </div>
</div>

<?php include '../includes/seller-footer.php'; ?>

