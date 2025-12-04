<?php
/**
 * 이벤트 관리 페이지 (관리자용)
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

// 함수 포함
require_once '../includes/data/home-functions.php';

// 액션 처리
$message = '';
$message_type = '';
$editing_event = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'create') {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $image = isset($_POST['image']) ? trim($_POST['image']) : '';
        $link = isset($_POST['link']) ? trim($_POST['link']) : '';
        $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
        $category = isset($_POST['category']) ? trim($_POST['category']) : 'all';
        
        if (!empty($title) && !empty($image) && !empty($link)) {
            $event = createEvent($title, $image, $link, $start_date, $end_date, $category);
            if ($event) {
                $message = '이벤트가 등록되었습니다.';
                $message_type = 'success';
            } else {
                $message = '이벤트 등록에 실패했습니다.';
                $message_type = 'error';
            }
        } else {
            $message = '필수 항목을 모두 입력해주세요.';
            $message_type = 'error';
        }
    } elseif ($action === 'update') {
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $image = isset($_POST['image']) ? trim($_POST['image']) : '';
        $link = isset($_POST['link']) ? trim($_POST['link']) : '';
        $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
        $category = isset($_POST['category']) ? trim($_POST['category']) : 'all';
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '1';
        
        if (!empty($id) && !empty($title) && !empty($image) && !empty($link)) {
            if (updateEvent($id, $title, $image, $link, $start_date, $end_date, $category, $is_active)) {
                $message = '이벤트가 수정되었습니다.';
                $message_type = 'success';
            } else {
                $message = '이벤트 수정에 실패했습니다.';
                $message_type = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? $_POST['id'] : '';
        if ($id && deleteEvent($id)) {
            $message = '이벤트가 삭제되었습니다.';
            $message_type = 'success';
        } else {
            $message = '이벤트 삭제에 실패했습니다.';
            $message_type = 'error';
        }
    } elseif ($action === 'edit') {
        $id = isset($_GET['edit']) ? $_GET['edit'] : (isset($_POST['id']) ? $_POST['id'] : '');
        if ($id) {
            $editing_event = getEventById($id);
            if (!$editing_event) {
                $editing_event = getAllEvents();
                foreach ($editing_event as $evt) {
                    if (isset($evt['id']) && $evt['id'] == $id) {
                        $editing_event = $evt;
                        break;
                    }
                }
            }
        }
    }
}

// 이벤트 목록 가져오기
$events = getAllEvents();
?>

<main class="main-content">
    <div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 20px;" class="event-manage-container">
        <!-- 페이지 헤더 -->
        <div style="margin-bottom: 32px;">
            <h1 style="font-size: 28px; font-weight: bold; margin: 0 0 8px 0;">이벤트 관리</h1>
            <p style="color: #6b7280; font-size: 14px; margin: 0;">이벤트를 등록하고 관리할 수 있습니다.</p>
        </div>

        <!-- 메시지 -->
        <?php if ($message): ?>
            <div style="padding: 16px; background: <?php echo $message_type === 'success' ? '#d1fae5' : '#fee2e2'; ?>; border: 1px solid <?php echo $message_type === 'success' ? '#a7f3d0' : '#fecaca'; ?>; border-radius: 8px; margin-bottom: 24px; color: <?php echo $message_type === 'success' ? '#059669' : '#dc2626'; ?>;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- 이벤트 등록/수정 폼 -->
        <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 32px; margin-bottom: 32px;">
            <h2 style="font-size: 20px; font-weight: bold; margin: 0 0 24px 0;">
                <?php echo $editing_event ? '이벤트 수정' : '새 이벤트 등록'; ?>
            </h2>
            <?php if ($editing_event): ?>
                <a href="/MVNO/admin/event-manage.php" style="color: #6366f1; text-decoration: none; font-size: 14px; margin-bottom: 16px; display: inline-block;">
                    ← 새로 등록하기
                </a>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $editing_event ? 'update' : 'create'; ?>">
                <?php if ($editing_event): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($editing_event['id']); ?>">
                <?php endif; ?>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        제목 <span style="color: #dc2626;">*</span>
                    </label>
                    <input type="text" 
                           name="title" 
                           value="<?php echo $editing_event ? htmlspecialchars($editing_event['title']) : ''; ?>"
                           required
                           style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px;"
                           placeholder="이벤트 제목을 입력해주세요">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        이미지 URL <span style="color: #dc2626;">*</span>
                    </label>
                    <input type="text" 
                           name="image" 
                           value="<?php echo $editing_event ? htmlspecialchars($editing_event['image']) : ''; ?>"
                           required
                           style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px;"
                           placeholder="이미지 URL을 입력해주세요">
                    <?php if ($editing_event && !empty($editing_event['image'])): ?>
                        <div style="margin-top: 8px;">
                            <img src="<?php echo htmlspecialchars($editing_event['image']); ?>" 
                                 alt="미리보기" 
                                 style="max-width: 300px; max-height: 200px; border-radius: 8px; border: 1px solid #e5e7eb;">
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        링크 URL <span style="color: #dc2626;">*</span>
                    </label>
                    <input type="text" 
                           name="link" 
                           value="<?php echo $editing_event ? htmlspecialchars($editing_event['link']) : ''; ?>"
                           required
                           style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px;"
                           placeholder="링크 URL을 입력해주세요">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            시작일
                        </label>
                        <input type="date" 
                               name="start_date" 
                               value="<?php echo $editing_event ? htmlspecialchars($editing_event['start_date']) : ''; ?>"
                               style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            종료일
                        </label>
                        <input type="date" 
                               name="end_date" 
                               value="<?php echo $editing_event ? htmlspecialchars($editing_event['end_date']) : ''; ?>"
                               style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        카테고리
                    </label>
                    <select name="category" style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px;">
                        <option value="all" <?php echo ($editing_event && ($editing_event['category'] ?? 'all') == 'all') ? 'selected' : ''; ?>>전체</option>
                        <option value="plan" <?php echo ($editing_event && ($editing_event['category'] ?? '') == 'plan') ? 'selected' : ''; ?>>요금제</option>
                        <option value="promotion" <?php echo ($editing_event && ($editing_event['category'] ?? '') == 'promotion') ? 'selected' : ''; ?>>프로모션</option>
                        <option value="card" <?php echo ($editing_event && ($editing_event['category'] ?? '') == 'card') ? 'selected' : ''; ?>>제휴카드</option>
                    </select>
                </div>

                <?php if ($editing_event): ?>
                <div style="margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" 
                               name="is_active" 
                               value="1"
                               <?php echo ($editing_event['is_active'] ?? false) ? 'checked' : ''; ?>
                               style="width: 18px; height: 18px; cursor: pointer;">
                        <span style="font-size: 14px; color: #374151;">활성화</span>
                    </label>
                </div>
                <?php endif; ?>

                <div style="display: flex; gap: 12px;">
                    <?php if ($editing_event): ?>
                        <a href="/MVNO/admin/event-manage.php" 
                           style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; text-decoration: none; font-weight: 500;">
                            취소
                        </a>
                    <?php endif; ?>
                    <button type="submit" 
                            style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer;">
                        <?php echo $editing_event ? '수정하기' : '등록하기'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- 이벤트 목록 -->
        <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;">
            <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                <h2 style="font-size: 20px; font-weight: bold; margin: 0;">등록된 이벤트 (<?php echo count($events); ?>개)</h2>
            </div>

            <?php if (empty($events)): ?>
                <div style="padding: 60px 20px; text-align: center; color: #9ca3af;">
                    등록된 이벤트가 없습니다.
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                <th style="padding: 16px; text-align: left; font-size: 14px; font-weight: 600; color: #374151;">제목</th>
                                <th style="padding: 16px; text-align: center; font-size: 14px; font-weight: 600; color: #374151; width: 100px;">카테고리</th>
                                <th style="padding: 16px; text-align: center; font-size: 14px; font-weight: 600; color: #374151; width: 150px;">기간</th>
                                <th style="padding: 16px; text-align: center; font-size: 14px; font-weight: 600; color: #374151; width: 100px;">상태</th>
                                <th style="padding: 16px; text-align: center; font-size: 14px; font-weight: 600; color: #374151; width: 150px;">작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 16px;">
                                        <div style="font-weight: 500; color: #111827; margin-bottom: 4px;">
                                            <?php echo htmlspecialchars($event['title']); ?>
                                        </div>
                                        <?php if (!empty($event['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($event['image']); ?>" 
                                                 alt="미리보기" 
                                                 style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb;">
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 16px; text-align: center; color: #6b7280; font-size: 13px;">
                                        <?php 
                                        $categories = [
                                            'all' => '전체',
                                            'plan' => '요금제',
                                            'promotion' => '프로모션',
                                            'card' => '제휴카드'
                                        ];
                                        echo $categories[$event['category'] ?? 'all'] ?? '전체';
                                        ?>
                                    </td>
                                    <td style="padding: 16px; text-align: center; color: #6b7280; font-size: 13px;">
                                        <?php 
                                        if (!empty($event['start_date']) && !empty($event['end_date'])) {
                                            echo date('Y.m.d', strtotime($event['start_date'])) . ' ~ ' . date('Y.m.d', strtotime($event['end_date']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td style="padding: 16px; text-align: center;">
                                        <?php if (isset($event['is_active']) && $event['is_active']): ?>
                                            <span style="display: inline-flex; align-items: center; padding: 4px 8px; background: #d1fae5; color: #059669; border-radius: 4px; font-size: 12px; font-weight: 600;">활성</span>
                                        <?php else: ?>
                                            <span style="display: inline-flex; align-items: center; padding: 4px 8px; background: #f3f4f6; color: #6b7280; border-radius: 4px; font-size: 12px; font-weight: 600;">비활성</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 16px; text-align: center;">
                                        <div style="display: flex; gap: 8px; justify-content: center;">
                                            <a href="/MVNO/admin/event-manage.php?edit=<?php echo htmlspecialchars($event['id']); ?>" 
                                               style="display: inline-block; padding: 6px 12px; background: #6366f1; color: white; border-radius: 6px; font-size: 13px; text-decoration: none;">
                                                수정
                                            </a>
                                            <form method="POST" action="" style="display: inline-block;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($event['id']); ?>">
                                                <button type="submit" 
                                                        style="padding: 6px 12px; background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; font-size: 13px; cursor: pointer;">
                                                    삭제
                                                </button>
                                            </form>
                                        </div>
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

