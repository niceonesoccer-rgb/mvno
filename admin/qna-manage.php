<?php
/**
 * 1:1 Q&A 관리 페이지 (관리자용)
 */
session_start();

// 관리자 인증 확인 (실제로는 세션 체크 필요)
// $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
// if (!$is_admin) {
//     header('Location: /MVNO/mypage/mypage.php');
//     exit;
// }

// 관리자 헤더 포함
include __DIR__ . '/includes/admin-header.php';

// Q&A 함수 포함
require_once '../includes/data/qna-functions.php';

// 액션 처리
$message = '';
$message_type = '';
$editing_qna = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'answer') {
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';
        $answer = isset($_POST['answer']) ? trim($_POST['answer']) : '';
        
        if (!empty($id) && !empty($answer)) {
            if (answerQna($id, $answer, 'admin')) {
                $message = '답변이 등록되었습니다.';
                $message_type = 'success';
            } else {
                $message = '답변 등록에 실패했습니다.';
                $message_type = 'error';
            }
        } else {
            $message = '답변 내용을 입력해주세요.';
            $message_type = 'error';
        }
    } elseif ($action === 'edit_answer') {
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';
        $answer = isset($_POST['answer']) ? trim($_POST['answer']) : '';
        
        if (!empty($id) && !empty($answer)) {
            if (answerQna($id, $answer, 'admin')) {
                $message = '답변이 수정되었습니다.';
                $message_type = 'success';
            } else {
                $message = '답변 수정에 실패했습니다.';
                $message_type = 'error';
            }
        }
    }
}

// Q&A ID로 상세 보기
$view_id = isset($_GET['view']) ? $_GET['view'] : null;
if ($view_id) {
    $editing_qna = getQnaById($view_id);
}

// Q&A 목록 가져오기
$qnas = getAllQnaForAdmin();
$pending_count = getPendingQnaCount();
?>

<div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 20px;" class="qna-manage-container">
        <!-- 페이지 헤더 -->
        <div style="margin-bottom: 32px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h1 style="font-size: 28px; font-weight: bold; margin: 0;">1:1 Q&A 관리</h1>
                <?php if ($pending_count > 0): ?>
                    <span style="display: inline-flex; align-items: center; padding: 8px 16px; background: #fef3c7; color: #d97706; border-radius: 8px; font-size: 14px; font-weight: 600;">
                        답변 대기: <?php echo $pending_count; ?>건
                    </span>
                <?php endif; ?>
            </div>
            <p style="color: #6b7280; font-size: 14px; margin: 0;">사용자 질문에 답변할 수 있습니다.</p>
        </div>

        <!-- 메시지 -->
        <?php if ($message): ?>
            <div style="padding: 16px; background: <?php echo $message_type === 'success' ? '#d1fae5' : '#fee2e2'; ?>; border: 1px solid <?php echo $message_type === 'success' ? '#a7f3d0' : '#fecaca'; ?>; border-radius: 8px; margin-bottom: 24px; color: <?php echo $message_type === 'success' ? '#059669' : '#dc2626'; ?>;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- 답변 작성/수정 폼 -->
        <?php if ($editing_qna): ?>
            <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 32px; margin-bottom: 32px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h2 style="font-size: 20px; font-weight: bold; margin: 0;">
                        <?php echo isset($editing_qna['answer']) && !empty($editing_qna['answer']) ? '답변 수정' : '답변 작성'; ?>
                    </h2>
                    <a href="/MVNO/admin/qna-manage.php" 
                       style="color: #6b7280; text-decoration: none; font-size: 14px;"
                       onmouseover="this.style.color='#374151'" 
                       onmouseout="this.style.color='#6b7280'">
                        닫기
                    </a>
                </div>

                <!-- 질문 내용 -->
                <div style="background: #f9fafb; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">질문</div>
                    <h3 style="font-size: 18px; font-weight: 600; margin: 0 0 12px 0; color: #111827;">
                        <?php echo htmlspecialchars($editing_qna['title']); ?>
                    </h3>
                    <div style="font-size: 14px; color: #374151; line-height: 1.6; white-space: pre-wrap;">
                        <?php echo nl2br(htmlspecialchars($editing_qna['content'])); ?>
                    </div>
                    <div style="font-size: 12px; color: #9ca3af; margin-top: 12px;">
                        작성일: <?php echo date('Y년 m월 d일 H:i', strtotime($editing_qna['created_at'])); ?>
                    </div>
                </div>

                <!-- 답변 폼 -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="<?php echo isset($editing_qna['answer']) && !empty($editing_qna['answer']) ? 'edit_answer' : 'answer'; ?>">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($editing_qna['id']); ?>">
                    
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            답변 내용 <span style="color: #dc2626;">*</span>
                        </label>
                        <textarea name="answer" 
                                  required
                                  rows="8"
                                  style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; font-family: inherit; resize: vertical;"
                                  placeholder="답변 내용을 입력해주세요"><?php echo isset($editing_qna['answer']) ? htmlspecialchars($editing_qna['answer']) : ''; ?></textarea>
                    </div>

                    <div style="display: flex; gap: 12px;">
                        <a href="/MVNO/admin/qna-manage.php" 
                           style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; text-decoration: none; font-weight: 500;">
                            취소
                        </a>
                        <button type="submit" 
                                style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: background-color 0.2s;"
                                onmouseover="this.style.background='#4f46e5'" 
                                onmouseout="this.style.background='#6366f1'">
                            <?php echo isset($editing_qna['answer']) && !empty($editing_qna['answer']) ? '수정하기' : '답변 등록'; ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Q&A 목록 -->
        <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;">
            <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                <h2 style="font-size: 20px; font-weight: bold; margin: 0;">질문 목록 (<?php echo count($qnas); ?>개)</h2>
            </div>

            <?php if (empty($qnas)): ?>
                <div style="padding: 60px 20px; text-align: center; color: #9ca3af;">
                    등록된 질문이 없습니다.
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                <th style="padding: 16px; text-align: left; font-size: 14px; font-weight: 600; color: #374151;">제목</th>
                                <th style="padding: 16px; text-align: center; font-size: 14px; font-weight: 600; color: #374151; width: 120px;">상태</th>
                                <th style="padding: 16px; text-align: center; font-size: 14px; font-weight: 600; color: #374151; width: 150px;">작성일</th>
                                <th style="padding: 16px; text-align: center; font-size: 14px; font-weight: 600; color: #374151; width: 150px;">답변일</th>
                                <th style="padding: 16px; text-align: center; font-size: 14px; font-weight: 600; color: #374151; width: 120px;">작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($qnas as $qna): ?>
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 16px;">
                                        <div style="font-weight: 500; color: #111827; margin-bottom: 4px;">
                                            <?php echo htmlspecialchars($qna['title']); ?>
                                        </div>
                                        <div style="font-size: 13px; color: #6b7280;">
                                            <?php echo htmlspecialchars(mb_substr($qna['content'], 0, 50)); ?>
                                            <?php echo mb_strlen($qna['content']) > 50 ? '...' : ''; ?>
                                        </div>
                                    </td>
                                    <td style="padding: 16px; text-align: center;">
                                        <?php if (isset($qna['status']) && $qna['status'] == 'answered'): ?>
                                            <span style="display: inline-flex; align-items: center; padding: 4px 8px; background: #d1fae5; color: #059669; border-radius: 4px; font-size: 12px; font-weight: 600;">답변완료</span>
                                        <?php else: ?>
                                            <span style="display: inline-flex; align-items: center; padding: 4px 8px; background: #fef3c7; color: #d97706; border-radius: 4px; font-size: 12px; font-weight: 600;">답변대기</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 16px; text-align: center; color: #6b7280; font-size: 13px;">
                                        <?php echo date('Y.m.d H:i', strtotime($qna['created_at'])); ?>
                                    </td>
                                    <td style="padding: 16px; text-align: center; color: #6b7280; font-size: 13px;">
                                        <?php if (isset($qna['answered_at'])): ?>
                                            <?php echo date('Y.m.d H:i', strtotime($qna['answered_at'])); ?>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 16px; text-align: center;">
                                        <a href="/MVNO/admin/qna-manage.php?view=<?php echo htmlspecialchars($qna['id']); ?>" 
                                           style="display: inline-block; padding: 6px 12px; background: #6366f1; color: white; border-radius: 6px; font-size: 13px; text-decoration: none; transition: background-color 0.2s;"
                                           onmouseover="this.style.background='#4f46e5'" 
                                           onmouseout="this.style.background='#6366f1'">
                                            <?php echo isset($qna['answer']) && !empty($qna['answer']) ? '수정' : '답변'; ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>

