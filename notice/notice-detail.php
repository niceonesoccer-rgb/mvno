<?php
/**
 * 공지사항 상세 페이지
 */
session_start();

// 현재 페이지 설정
$current_page = 'mypage';
$is_main_page = false;

// 헤더 포함
include '../includes/header.php';

// 공지사항 함수 포함
require_once '../includes/data/notice-functions.php';

// 공지사항 ID 확인
$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id) {
    header('Location: /MVNO/notice/notice.php');
    exit;
}

// 공지사항 가져오기
$notice = getNoticeById($id);
if (!$notice) {
    header('Location: /MVNO/notice/notice.php');
    exit;
}

// 조회수 증가
incrementNoticeViews($id);
$notice = getNoticeById($id); // 업데이트된 조회수 가져오기
?>

<main class="main-content">
    <div style="width: 100%; max-width: 980px; margin: 0 auto; padding: 20px;" class="notice-detail-container">
        <!-- 뒤로가기 버튼 -->
        <a href="/MVNO/notice/notice.php" 
           style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 24px; color: #6366f1; text-decoration: none; font-size: 14px; font-weight: 500;"
           onmouseover="this.style.opacity='0.8'" 
           onmouseout="this.style.opacity='1'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            목록으로
        </a>

        <!-- 공지사항 내용 -->
        <article style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 32px;">
            <!-- 헤더 -->
            <header style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #e5e7eb;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                    <?php if (isset($notice['is_important']) && $notice['is_important']): ?>
                        <span style="display: inline-flex; align-items: center; padding: 6px 12px; background: #fee2e2; color: #dc2626; border-radius: 6px; font-size: 13px; font-weight: 600;">중요</span>
                    <?php endif; ?>
                </div>
                <h1 style="font-size: 24px; font-weight: bold; margin: 0 0 16px 0; color: #111827; line-height: 1.4;">
                    <?php echo htmlspecialchars($notice['title']); ?>
                </h1>
                <div style="display: flex; align-items: center; gap: 16px; font-size: 14px; color: #6b7280;">
                    <span>작성일: <?php echo date('Y년 m월 d일 H:i', strtotime($notice['created_at'])); ?></span>
                    <?php if (isset($notice['views'])): ?>
                        <span>조회수: <?php echo number_format($notice['views']); ?></span>
                    <?php endif; ?>
                </div>
            </header>

            <!-- 본문 -->
            <div style="font-size: 16px; line-height: 1.8; color: #374151; white-space: pre-wrap; word-wrap: break-word;">
                <?php echo nl2br(htmlspecialchars($notice['content'])); ?>
            </div>
        </article>

        <!-- 목록으로 버튼 -->
        <div style="margin-top: 32px; text-align: center;">
            <a href="/MVNO/notice/notice.php" 
               style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; background: #6366f1; color: white; border-radius: 8px; text-decoration: none; font-weight: 500; transition: background-color 0.2s;"
               onmouseover="this.style.background='#4f46e5'" 
               onmouseout="this.style.background='#6366f1'">
                목록으로 돌아가기
            </a>
        </div>
    </div>
</main>

<?php
// 푸터 포함
include '../includes/footer.php';
?>





