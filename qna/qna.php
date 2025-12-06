<?php
/**
 * 1:1 Q&A 목록 페이지
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

// Q&A 목록 가져오기
$qnas = getQnaList($user_id);
?>

<main class="main-content">
    <div style="width: 100%; max-width: 980px; margin: 0 auto; padding: 20px;" class="qna-container">
        <!-- 페이지 헤더 -->
        <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-size: 28px; font-weight: bold; margin: 0 0 8px 0;">질문과 답변</h1>
                <p style="color: #6b7280; font-size: 14px; margin: 0;">궁금한 사항을 질문해주시면 관리자가 답변해드립니다.</p>
            </div>
            <a href="/MVNO/qna/qna-write.php" 
               style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #6366f1; color: white; border-radius: 8px; text-decoration: none; font-weight: 500; transition: background-color 0.2s;"
               onmouseover="this.style.background='#4f46e5'" 
               onmouseout="this.style.background='#6366f1'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                질문하기
            </a>
        </div>

        <!-- Q&A 목록 -->
        <?php if (empty($qnas)): ?>
            <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; border: 1px solid #e5e7eb;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin: 0 auto 16px; opacity: 0.5;">
                    <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                    <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p style="color: #9ca3af; font-size: 16px; margin-bottom: 24px;">등록된 질문이 없습니다.</p>
                <a href="/MVNO/qna/qna-write.php" 
                   style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #6366f1; color: white; border-radius: 8px; text-decoration: none; font-weight: 500;">
                    첫 질문 작성하기
                </a>
            </div>
        <?php else: ?>
            <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;">
                <?php foreach ($qnas as $qna): ?>
                    <a href="/MVNO/qna/qna-detail.php?id=<?php echo htmlspecialchars($qna['id']); ?>" 
                       style="display: block; padding: 20px; border-bottom: 1px solid #e5e7eb; text-decoration: none; color: inherit; transition: background-color 0.2s;"
                       onmouseover="this.style.backgroundColor='#f9fafb'" 
                       onmouseout="this.style.backgroundColor='white'">
                        <div style="display: flex; align-items: flex-start; gap: 16px;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                    <?php if (isset($qna['status']) && $qna['status'] == 'answered'): ?>
                                        <span style="display: inline-flex; align-items: center; padding: 4px 8px; background: #d1fae5; color: #059669; border-radius: 4px; font-size: 12px; font-weight: 600;">답변완료</span>
                                    <?php else: ?>
                                        <span style="display: inline-flex; align-items: center; padding: 4px 8px; background: #fef3c7; color: #d97706; border-radius: 4px; font-size: 12px; font-weight: 600;">답변대기</span>
                                    <?php endif; ?>
                                    <h3 style="font-size: 16px; font-weight: 600; margin: 0; color: #111827;">
                                        <?php echo htmlspecialchars($qna['title']); ?>
                                    </h3>
                                </div>
                                <p style="font-size: 14px; color: #6b7280; margin: 0 0 8px 0; line-height: 1.5;">
                                    <?php echo htmlspecialchars(mb_substr($qna['content'], 0, 100)); ?>
                                    <?php echo mb_strlen($qna['content']) > 100 ? '...' : ''; ?>
                                </p>
                                <div style="display: flex; align-items: center; gap: 16px; font-size: 13px; color: #9ca3af;">
                                    <span><?php echo date('Y.m.d', strtotime($qna['created_at'])); ?></span>
                                    <?php if (isset($qna['answered_at'])): ?>
                                        <span>답변일: <?php echo date('Y.m.d', strtotime($qna['answered_at'])); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0; color: #9ca3af;">
                                <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// 푸터 포함
include '../includes/footer.php';
?>





