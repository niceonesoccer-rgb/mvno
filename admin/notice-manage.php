<?php
/**
 * 공지사항 관리 페이지 (관리자용)
 */
session_start();

// 관리자 인증 확인 (실제로는 세션 체크 필요)
// $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
// if (!$is_admin) {
//     header('Location: /MVNO/mypage/mypage.php');
//     exit;
// }

// 현재 페이지 설정
$current_page = 'mypage';
$is_main_page = false;

// 헤더 포함
include '../includes/header.php';

// 공지사항 함수 포함
require_once '../includes/data/notice-functions.php';

// 액션 처리
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'create') {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $is_important = isset($_POST['is_important']) && $_POST['is_important'] === '1';
        
        if (!empty($title) && !empty($content)) {
            $notice = createNotice($title, $content, $is_important);
            if ($notice) {
                $message = '공지사항이 등록되었습니다.';
                $message_type = 'success';
            } else {
                $message = '공지사항 등록에 실패했습니다.';
                $message_type = 'error';
            }
        } else {
            $message = '제목과 내용을 모두 입력해주세요.';
            $message_type = 'error';
        }
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? $_POST['id'] : '';
        if ($id && deleteNotice($id)) {
            $message = '공지사항이 삭제되었습니다.';
            $message_type = 'success';
        } else {
            $message = '공지사항 삭제에 실패했습니다.';
            $message_type = 'error';
        }
    }
}

// 공지사항 목록 가져오기
$notices = getAllNoticesForAdmin();
?>

<main class="main-content">
    <div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 20px;" class="notice-manage-container">
        <!-- 페이지 헤더 -->
        <div style="margin-bottom: 32px;">
            <h1 style="font-size: 28px; font-weight: bold; margin: 0 0 8px 0;">공지사항 관리</h1>
            <p style="color: #6b7280; font-size: 14px; margin: 0;">공지사항을 등록, 수정, 삭제할 수 있습니다.</p>
        </div>

        <!-- 메시지 -->
        <?php if ($message): ?>
            <div style="padding: 16px; background: <?php echo $message_type === 'success' ? '#d1fae5' : '#fee2e2'; ?>; border: 1px solid <?php echo $message_type === 'success' ? '#a7f3d0' : '#fecaca'; ?>; border-radius: 8px; margin-bottom: 24px; color: <?php echo $message_type === 'success' ? '#059669' : '#dc2626'; ?>;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- 공지사항 등록 폼 -->
        <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 32px; margin-bottom: 32px;">
            <h2 style="font-size: 20px; font-weight: bold; margin: 0 0 24px 0;">새 공지사항 등록</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        제목 <span style="color: #dc2626;">*</span>
                    </label>
                    <input type="text" 
                           name="title" 
                           required
                           style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px;"
                           placeholder="공지사항 제목을 입력해주세요">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        내용 <span style="color: #dc2626;">*</span>
                    </label>
                    <textarea name="content" 
                              required
                              rows="8"
                              style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; font-family: inherit; resize: vertical;"
                              placeholder="공지사항 내용을 입력해주세요"></textarea>
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" 
                               name="is_important" 
                               value="1"
                               style="width: 18px; height: 18px; cursor: pointer;">
                        <span style="font-size: 14px; color: #374151;">중요 공지사항으로 표시</span>
                    </label>
                </div>

                <button type="submit" 
                        style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: background-color 0.2s;"
                        onmouseover="this.style.background='#4f46e5'" 
                        onmouseout="this.style.background='#6366f1'">
                    등록하기
                </button>
            </form>
        </div>

        <!-- 공지사항 목록 -->
        <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;">
            <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                <h2 style="font-size: 20px; font-weight: bold; margin: 0;">등록된 공지사항 (<?php echo count($notices); ?>개)</h2>
            </div>

            <?php if (empty($notices)): ?>
                <div style="padding: 60px 20px; text-align: center; color: #9ca3af;">
                    등록된 공지사항이 없습니다.
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                <th style="padding: 16px; text-align: left; font-size: 14px; font-weight: 600; color: #374151;">제목</th>
                                <th style="padding: 16px; text-align: center; font-size: 14px; font-weight: 600; color: #374151; width: 100px;">중요</th>
                                <th style="padding: 16px; text-align: center; font-size: 14px; font-weight: 600; color: #374151; width: 120px;">조회수</th>
                                <th style="padding: 16px; text-align: center; font-size: 14px; font-weight: 600; color: #374151; width: 150px;">작성일</th>
                                <th style="padding: 16px; text-align: center; font-size: 14px; font-weight: 600; color: #374151; width: 100px;">상태</th>
                                <th style="padding: 16px; text-align: center; font-size: 14px; font-weight: 600; color: #374151; width: 120px;">작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notices as $notice): ?>
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 16px;">
                                        <a href="/MVNO/notice/notice-detail.php?id=<?php echo htmlspecialchars($notice['id']); ?>" 
                                           target="_blank"
                                           style="color: #6366f1; text-decoration: none; font-weight: 500;"
                                           onmouseover="this.style.textDecoration='underline'" 
                                           onmouseout="this.style.textDecoration='none'">
                                            <?php echo htmlspecialchars($notice['title']); ?>
                                        </a>
                                    </td>
                                    <td style="padding: 16px; text-align: center;">
                                        <?php if (isset($notice['is_important']) && $notice['is_important']): ?>
                                            <span style="display: inline-flex; align-items: center; padding: 4px 8px; background: #fee2e2; color: #dc2626; border-radius: 4px; font-size: 12px; font-weight: 600;">중요</span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 16px; text-align: center; color: #6b7280;">
                                        <?php echo number_format($notice['views'] ?? 0); ?>
                                    </td>
                                    <td style="padding: 16px; text-align: center; color: #6b7280; font-size: 13px;">
                                        <?php echo date('Y.m.d', strtotime($notice['created_at'])); ?>
                                    </td>
                                    <td style="padding: 16px; text-align: center;">
                                        <?php if (isset($notice['is_published']) && $notice['is_published']): ?>
                                            <span style="display: inline-flex; align-items: center; padding: 4px 8px; background: #d1fae5; color: #059669; border-radius: 4px; font-size: 12px; font-weight: 600;">공개</span>
                                        <?php else: ?>
                                            <span style="display: inline-flex; align-items: center; padding: 4px 8px; background: #f3f4f6; color: #6b7280; border-radius: 4px; font-size: 12px; font-weight: 600;">비공개</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 16px; text-align: center;">
                                        <form method="POST" action="" style="display: inline-block;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($notice['id']); ?>">
                                            <button type="submit" 
                                                    style="padding: 6px 12px; background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; font-size: 13px; cursor: pointer; transition: all 0.2s;"
                                                    onmouseover="this.style.background='#fecaca'" 
                                                    onmouseout="this.style.background='#fee2e2'">
                                                삭제
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

