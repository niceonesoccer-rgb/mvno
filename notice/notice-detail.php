<?php
/**
 * 공지사항 상세 페이지
 */

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';

// 로그인 체크 - 로그인하지 않은 경우 회원가입 모달로 리다이렉트
// (마이페이지에서 접근하는 경우 로그인 필수)
if (!isLoggedIn()) {
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    // 로그인 모달이 있는 홈으로 리다이렉트 (모달 자동 열기)
    header('Location: /MVNO/?show_login=1');
    exit;
}

// 현재 사용자 정보 가져오기
$currentUser = getCurrentUser();
if (!$currentUser) {
    // 세션 정리 후 로그인 페이지로 리다이렉트
    if (isset($_SESSION['logged_in'])) {
        unset($_SESSION['logged_in']);
    }
    if (isset($_SESSION['user_id'])) {
        unset($_SESSION['user_id']);
    }
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: /MVNO/?show_login=1');
    exit;
}

// 현재 페이지 설정
$current_page = 'mypage';
$is_main_page = false;

// 헤더 포함
include '../includes/header.php';

// 공지사항 함수 포함
require_once '../includes/data/notice-functions.php';

// 관리자 여부 확인
$isAdmin = isAdmin();

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
                <h1 style="font-size: 24px; font-weight: bold; margin: 0 0 16px 0; color: #111827; line-height: 1.4;">
                    <?php echo htmlspecialchars($notice['title']); ?>
                </h1>
                <div style="display: flex; align-items: center; gap: 16px; font-size: 14px; color: #6b7280;">
                    <span>작성일: <?php echo date('Y년 m월 d일', strtotime($notice['created_at'])); ?></span>
                    <?php if ($isAdmin && isset($notice['views'])): ?>
                        <span style="color: #9ca3af;">조회수: <?php echo number_format($notice['views']); ?></span>
                    <?php endif; ?>
                </div>
            </header>

            <!-- 본문 -->
            <?php if (!empty($notice['image_url'])): ?>
                <div style="margin-bottom: 24px;">
                    <?php if (!empty($notice['link_url'])): ?>
                        <a href="<?php echo htmlspecialchars($notice['link_url']); ?>" target="_blank" style="display: block;">
                            <img src="<?php echo htmlspecialchars($notice['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($notice['title']); ?>" 
                                 style="max-width: 100%; height: auto; border-radius: 8px; cursor: pointer;">
                        </a>
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars($notice['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($notice['title']); ?>" 
                             style="max-width: 100%; height: auto; border-radius: 8px;">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($notice['content'])): ?>
                <div style="font-size: 16px; line-height: 1.8; color: #374151; white-space: pre-wrap; word-wrap: break-word;">
                    <?php echo nl2br(htmlspecialchars($notice['content'])); ?>
                </div>
            <?php endif; ?>
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


















