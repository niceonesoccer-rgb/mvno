<?php
/**
 * 1:1 Q&A 상세 페이지
 */
session_start();

// Q&A 함수 포함 (헤더 포함 전에 처리)
require_once '../includes/data/qna-functions.php';

// 사용자 ID 가져오기
$user_id = getCurrentUserId();

// Q&A ID 확인
$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id) {
    header('Location: /MVNO/qna/qna.php');
    exit;
}

// Q&A 가져오기
$qna = getQnaById($id, $user_id);
if (!$qna) {
    // 에러 로깅
    error_log("QnA 상세 조회 실패 - ID: " . $id . ", User ID: " . $user_id);
    $_SESSION['qna_error'] = '문의글을 찾을 수 없습니다.';
    header('Location: /MVNO/qna/qna.php');
    exit;
}

// 데이터 검증: 필수 필드 확인 (NULL 체크와 빈 문자열 체크를 명확히 구분)
if (!isset($qna['title']) || $qna['title'] === null || trim($qna['title']) === '' || 
    !isset($qna['content']) || $qna['content'] === null || trim($qna['content']) === '') {
    error_log("QnA 데이터 불완전 - ID: " . $id . ", Title: " . ($qna['title'] ?? 'NULL') . ", Content: " . (isset($qna['content']) && $qna['content'] !== null ? (strlen($qna['content']) > 0 ? 'EXISTS(' . strlen($qna['content']) . ' chars)' : 'EMPTY') : 'NULL'));
    $_SESSION['qna_error'] = '문의글 데이터가 불완전합니다.';
    header('Location: /MVNO/qna/qna.php');
    exit;
}

// 삭제 처리 (헤더 포함 전에 처리하여 리다이렉트 가능하도록)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (deleteQna($id, $user_id)) {
        header('Location: /MVNO/qna/qna.php');
        exit;
    }
}

// 현재 페이지 설정
$current_page = 'mypage';
$is_main_page = false;

// 헤더 포함 (모든 리다이렉트 처리 후)
include '../includes/header.php';
?>

<main class="main-content">
    <div style="width: 100%; max-width: 980px; margin: 0 auto; padding: 20px;" class="qna-detail-container">
        <!-- 뒤로가기 버튼 -->
        <a href="/MVNO/qna/qna.php" 
           style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 24px; color: #6366f1; text-decoration: none; font-size: 14px; font-weight: 500;"
           onmouseover="this.style.opacity='0.8'" 
           onmouseout="this.style.opacity='1'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            목록으로
        </a>

        <!-- 질문 내용 -->
        <article style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 32px; margin-bottom: 24px;">
            <header style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #e5e7eb;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                    <?php if (isset($qna['status']) && $qna['status'] == 'answered'): ?>
                        <span style="display: inline-flex; align-items: center; padding: 6px 12px; background: #d1fae5; color: #059669; border-radius: 6px; font-size: 13px; font-weight: 600;">답변완료</span>
                    <?php elseif (isset($qna['admin_viewed_at']) && !empty($qna['admin_viewed_at'])): ?>
                        <span style="display: inline-flex; align-items: center; padding: 6px 12px; background: #dbeafe; color: #1e40af; border-radius: 6px; font-size: 13px; font-weight: 600;">답변대기중</span>
                    <?php else: ?>
                        <span style="display: inline-flex; align-items: center; padding: 6px 12px; background: #fef3c7; color: #d97706; border-radius: 6px; font-size: 13px; font-weight: 600;">답변대기</span>
                    <?php endif; ?>
                </div>
                <h1 style="font-size: 24px; font-weight: bold; margin: 0 0 16px 0; color: #111827; line-height: 1.4;">
                    <?php echo htmlspecialchars($qna['title']); ?>
                </h1>
                <div style="display: flex; align-items: center; gap: 16px; font-size: 14px; color: #6b7280;">
                    <span>작성일: <?php echo date('Y년 m월 d일 H:i', strtotime($qna['created_at'])); ?></span>
                </div>
            </header>

            <div style="font-size: 16px; line-height: 1.8; color: #374151; white-space: pre-wrap; word-wrap: break-word;">
                <?php echo nl2br(htmlspecialchars($qna['content'])); ?>
            </div>
        </article>

        <!-- 답변 내용 -->
        <?php if (isset($qna['answer']) && !empty($qna['answer'])): ?>
            <article style="background: #f0f9ff; border-radius: 12px; border: 1px solid #bae6fd; padding: 32px; margin-bottom: 24px;">
                <header style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #bae6fd;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #0369a1;">
                            <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h2 style="font-size: 18px; font-weight: bold; margin: 0; color: #0369a1;">관리자 답변</h2>
                    </div>
                    <div style="font-size: 14px; color: #0369a1;">
                        <?php if (isset($qna['answered_at'])): ?>
                            답변일: <?php echo date('Y년 m월 d일 H:i', strtotime($qna['answered_at'])); ?>
                        <?php endif; ?>
                    </div>
                </header>

                <div style="font-size: 16px; line-height: 1.8; color: #0c4a6e; white-space: pre-wrap; word-wrap: break-word;">
                    <?php echo nl2br(htmlspecialchars($qna['answer'])); ?>
                </div>
            </article>
        <?php else: ?>
            <div style="background: #fef3c7; border-radius: 12px; border: 1px solid #fde68a; padding: 24px; margin-bottom: 24px; text-align: center;">
                <p style="color: #d97706; font-size: 16px; margin: 0;">답변 대기 중입니다. 빠른 시일 내에 답변드리겠습니다.</p>
            </div>
        <?php endif; ?>

        <!-- 버튼 영역 -->
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <a href="/MVNO/qna/qna.php" 
               style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.2s;"
               onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1'" 
               onmouseout="this.style.borderColor='#d1d5db'; this.style.color='#374151'">
                목록으로
            </a>
            <?php 
            // 관리자가 조회한 경우 삭제 버튼 숨김
            $canDelete = (!isset($qna['answer']) || empty($qna['answer'])) && 
                         (!isset($qna['admin_viewed_at']) || empty($qna['admin_viewed_at']));
            ?>
            <?php if ($canDelete): ?>
                <form method="POST" action="" style="display: inline-block;" onsubmit="event.preventDefault(); showConfirm('정말 삭제하시겠습니까?', '삭제 확인').then(result => { if(result) this.submit(); }); return false;">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" 
                            style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; background: white; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s;"
                            onmouseover="this.style.borderColor='#dc2626'; this.style.background='#fee2e2'" 
                            onmouseout="this.style.borderColor='#fecaca'; this.style.background='white'">
                        삭제하기
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
// 푸터 포함
include '../includes/footer.php';
?>



