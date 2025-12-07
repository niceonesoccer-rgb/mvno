<?php
/**
 * 1:1 Q&A 질문 작성 페이지
 */
session_start();

// 현재 페이지 설정
$current_page = 'mypage';
$is_main_page = false;

// 헤더 포함
include '../includes/header.php';

// Q&A 함수 포함
require_once '../includes/data/qna-functions.php';

// 사용자 ID 가져오기
$user_id = getCurrentUserId();

// 폼 제출 처리
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    
    if (empty($title)) {
        $error = '제목을 입력해주세요.';
    } elseif (empty($content)) {
        $error = '내용을 입력해주세요.';
    } else {
        $qna = createQna($user_id, $title, $content);
        if ($qna) {
            $success = true;
            header('Location: /MVNO/qna/qna-detail.php?id=' . $qna['id']);
            exit;
        } else {
            $error = '질문 등록에 실패했습니다. 다시 시도해주세요.';
        }
    }
}
?>

<main class="main-content">
    <div style="width: 100%; max-width: 980px; margin: 0 auto; padding: 20px;" class="qna-write-container">
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

        <!-- 페이지 헤더 -->
        <div style="margin-bottom: 32px;">
            <h1 style="font-size: 28px; font-weight: bold; margin: 0 0 8px 0;">질문 작성</h1>
            <p style="color: #6b7280; font-size: 14px; margin: 0;">궁금한 사항을 자세히 작성해주시면 빠른 답변을 받으실 수 있습니다.</p>
        </div>

        <!-- 에러 메시지 -->
        <?php if ($error): ?>
            <div style="padding: 16px; background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; margin-bottom: 24px; color: #dc2626;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- 질문 작성 폼 -->
        <form method="POST" action="" style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 32px;">
            <div style="margin-bottom: 24px;">
                <label for="title" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    제목 <span style="color: #dc2626;">*</span>
                </label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                       required
                       style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; transition: border-color 0.2s;"
                       onfocus="this.style.borderColor='#6366f1'" 
                       onblur="this.style.borderColor='#d1d5db'"
                       placeholder="질문 제목을 입력해주세요">
            </div>

            <div style="margin-bottom: 32px;">
                <label for="content" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    내용 <span style="color: #dc2626;">*</span>
                </label>
                <textarea id="content" 
                          name="content" 
                          required
                          rows="10"
                          style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; font-family: inherit; resize: vertical; transition: border-color 0.2s;"
                          onfocus="this.style.borderColor='#6366f1'" 
                          onblur="this.style.borderColor='#d1d5db'"
                          placeholder="질문 내용을 자세히 입력해주세요"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <a href="/MVNO/qna/qna.php" 
                   style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.2s;"
                   onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1'" 
                   onmouseout="this.style.borderColor='#d1d5db'; this.style.color='#374151'">
                    취소
                </a>
                <button type="submit" 
                        style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: background-color 0.2s;"
                        onmouseover="this.style.background='#4f46e5'" 
                        onmouseout="this.style.background='#6366f1'">
                    등록하기
                </button>
            </div>
        </form>
    </div>
</main>

<?php
// 푸터 포함
include '../includes/footer.php';
?>











