<?php
/**
 * 1:1 문의(Q&A) 관리 페이지
 * 경로: /MVNO/admin/content/qna-manage.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/qna-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdmin()) {
    header('Location: /MVNO/admin/');
    exit;
}

$error = '';
$success = '';
$viewQna = null;
$viewId = $_GET['view'] ?? '';
$answerId = $_GET['answer'] ?? '';

// Q&A 답변 작성
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'answer') {
    $id = trim($_POST['id'] ?? '');
    $answer = trim($_POST['answer'] ?? '');
    $currentUser = getCurrentUser();
    $answered_by = $currentUser['user_id'] ?? 'admin';
    
    if (empty($id)) {
        $error = 'Q&A ID가 없습니다.';
    } elseif (empty($answer)) {
        $error = '답변 내용을 입력해주세요.';
    } else {
        error_log("===== 답변 등록 시도 시작 =====");
        error_log("QnA ID: {$id}, Answer length: " . strlen($answer) . ", Answered by: {$answered_by}");
        error_log("호출 위치: " . __FILE__ . ":" . __LINE__);
        
        if (answerQna($id, $answer, $answered_by)) {
            error_log("답변 등록 성공 - 리다이렉트 전");
            $success = '답변이 등록되었습니다.';
            
            // 리다이렉트 전에 QnA 상태 확인
            $pdo = getDBConnection();
            if ($pdo) {
                $checkStmt = $pdo->prepare("SELECT id, deleted_at, status, answer FROM qna WHERE id = :id LIMIT 1");
                $checkStmt->execute([':id' => $id]);
                $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if ($checkResult) {
                    error_log("리다이렉트 전 QnA 상태 - deleted_at: " . ($checkResult['deleted_at'] ?? 'NULL') . ", status: " . ($checkResult['status'] ?? 'NULL') . ", answer: " . (!empty($checkResult['answer']) ? 'EXISTS' : 'NULL'));
                } else {
                    error_log("리다이렉트 전 QnA를 찾을 수 없음!");
                }
            }
            
            header('Location: /MVNO/admin/content/qna-manage.php?view=' . urlencode($id) . '&success=answered');
            exit;
        } else {
            error_log("답변 등록 실패");
            $error = '답변 등록에 실패했습니다.';
        }
    }
}

// Q&A 답변 수정
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_answer') {
    $id = trim($_POST['id'] ?? '');
    $answer = trim($_POST['answer'] ?? '');
    $currentUser = getCurrentUser();
    $answered_by = $currentUser['user_id'] ?? 'admin';
    
    // 필터 및 검색 파라미터 가져오기 (리다이렉트 시 유지)
    $filter = isset($_POST['filter']) ? $_POST['filter'] : (isset($_GET['filter']) ? $_GET['filter'] : 'all');
    $search = isset($_POST['search']) ? $_POST['search'] : (isset($_GET['search']) ? $_GET['search'] : '');
    
    if (empty($id)) {
        $error = 'Q&A ID가 없습니다.';
    } else {
        // 빈 답변도 허용 (답변 삭제용)
        if (answerQna($id, $answer, $answered_by)) {
            $success = empty($answer) ? '답변이 삭제되었습니다.' : '답변이 수정되었습니다.';
            
            // 리다이렉트 URL 구성 (필터 및 검색 파라미터 유지)
            $redirectUrl = '/MVNO/admin/content/qna-manage.php?view=' . urlencode($id) . '&success=updated';
            if ($filter !== 'all') {
                $redirectUrl .= '&filter=' . urlencode($filter);
            }
            if (!empty($search)) {
                $redirectUrl .= '&search=' . urlencode($search);
            }
            
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            $error = '답변 수정에 실패했습니다.';
        }
    }
}

// Q&A 복구
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore') {
    $id = trim($_POST['id'] ?? '');
    
    if (empty($id)) {
        $error = 'Q&A ID가 없습니다.';
    } else {
        if (restoreQna($id)) {
            $success = 'Q&A가 복구되었습니다.';
            header('Location: /MVNO/admin/content/qna-manage.php?success=restored');
            exit;
        } else {
            $error = 'Q&A 복구에 실패했습니다.';
        }
    }
}

// Q&A 영구 삭제 (완전 삭제)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'permanently_delete') {
    $id = trim($_POST['id'] ?? '');
    
    // 안전장치: 영구 삭제 확인 (이중 확인)
    $confirmPermanentDelete = isset($_POST['confirm_permanent_delete']) && $_POST['confirm_permanent_delete'] === 'yes';
    
    if (empty($id)) {
        $error = 'Q&A ID가 없습니다.';
    } elseif (!$confirmPermanentDelete) {
        $error = '영구 삭제 확인이 필요합니다.';
    } else {
        // 영구 삭제 전 로깅
        $qnaToDelete = getQnaById($id);
        if ($qnaToDelete) {
            error_log("QnA 영구 삭제 시도 - ID: " . $id . ", Title: " . ($qnaToDelete['title'] ?? 'NULL') . ", User: " . ($qnaToDelete['user_id'] ?? 'NULL'));
        }
        
        if (permanentlyDeleteQna($id)) {
            $success = 'Q&A가 영구 삭제되었습니다.';
            error_log("QnA 영구 삭제 성공 - ID: " . $id);
            
            // 삭제된 항목 목록으로 리다이렉트
            header('Location: /MVNO/admin/content/qna-manage.php?show_deleted=1&success=permanently_deleted');
            exit;
        } else {
            $error = 'Q&A 영구 삭제에 실패했습니다.';
            error_log("QnA 영구 삭제 실패 - ID: " . $id);
        }
    }
}

// Q&A 삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = trim($_POST['id'] ?? '');
    
    // 안전장치: 삭제 확인 (이중 확인)
    $confirmDelete = isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes';
    
    if (empty($id)) {
        $error = 'Q&A ID가 없습니다.';
    } elseif (!$confirmDelete) {
        // 첫 번째 확인: JavaScript 확인이 실패한 경우
        $error = '삭제 확인이 필요합니다.';
    } else {
        // 삭제 전 로깅
        $qnaToDelete = getQnaById($id);
        if ($qnaToDelete) {
            error_log("QnA 삭제 시도 - ID: " . $id . ", Title: " . ($qnaToDelete['title'] ?? 'NULL') . ", User: " . ($qnaToDelete['user_id'] ?? 'NULL'));
        }
        
        if (deleteQna($id)) {
            $success = 'Q&A가 삭제되었습니다.';
            error_log("QnA 삭제 성공 - ID: " . $id);
            
            // 필터 및 검색 파라미터 유지
            $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $redirectUrl = '/MVNO/admin/content/qna-manage.php?success=deleted';
            if ($filter !== 'all') {
                $redirectUrl .= '&filter=' . urlencode($filter);
            }
            if (!empty($search)) {
                $redirectUrl .= '&search=' . urlencode($search);
            }
            
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            $error = 'Q&A 삭제에 실패했습니다.';
            error_log("QnA 삭제 실패 - ID: " . $id);
        }
    }
}

// 성공 메시지 처리
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'answered') {
        $success = '답변이 등록되었습니다.';
    } elseif ($_GET['success'] === 'updated') {
        $success = '답변이 수정되었습니다.';
    } elseif ($_GET['success'] === 'deleted') {
        $success = 'Q&A가 삭제되었습니다. (복구 가능)';
    } elseif ($_GET['success'] === 'restored') {
        $success = 'Q&A가 복구되었습니다.';
    } elseif ($_GET['success'] === 'permanently_deleted') {
        $success = 'Q&A가 영구 삭제되었습니다.';
    }
}

// 필터 및 검색 파라미터 (상세 조회 전에 정의)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // all, pending, answered, deleted
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] === '1';

// 상세 보기할 Q&A 가져오기
if (!empty($viewId)) {
    error_log("===== QnA 상세 조회 시작 =====");
    error_log("View ID: {$viewId}, Show Deleted: " . ($showDeleted ? 'YES' : 'NO'));
    
    // 삭제된 항목도 볼 수 있도록 별도 함수 사용
    if ($showDeleted) {
        // 삭제된 Q&A 조회 (관리자용)
        $pdo = getDBConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM qna WHERE id = :id AND deleted_at IS NOT NULL AND deleted_at != '' LIMIT 1");
            $stmt->execute([':id' => $viewId]);
            $viewQna = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("삭제된 QnA 조회 결과: " . ($viewQna ? 'FOUND' : 'NOT FOUND'));
        } else {
            $viewQna = null;
        }
    } else {
        $viewQna = getQnaById($viewId);
        error_log("일반 QnA 조회 결과: " . ($viewQna ? 'FOUND' : 'NOT FOUND'));
        if ($viewQna) {
            error_log("QnA 상태 - deleted_at: " . ($viewQna['deleted_at'] ?? 'NULL') . ", status: " . ($viewQna['status'] ?? 'NULL'));
            
            // 관리자가 Q&A를 조회했음을 표시
            markQnaAsViewedByAdmin($viewId);
        }
    }
    
    if (!$viewQna) {
        error_log("QnA를 찾을 수 없음 - View ID: {$viewId}");
        
        // 삭제되었는지 확인
        $pdo = getDBConnection();
        if ($pdo) {
            $checkDeletedStmt = $pdo->prepare("SELECT id, deleted_at FROM qna WHERE id = :id LIMIT 1");
            $checkDeletedStmt->execute([':id' => $viewId]);
            $deletedCheck = $checkDeletedStmt->fetch(PDO::FETCH_ASSOC);
            if ($deletedCheck) {
                error_log("QnA가 삭제된 상태 - deleted_at: " . ($deletedCheck['deleted_at'] ?? 'NULL'));
            } else {
                error_log("QnA가 존재하지 않음");
            }
        }
        
        $error = 'Q&A를 찾을 수 없습니다.';
        $viewId = '';
    }
}

// 필터 및 검색 파라미터 (상세 조회 전에 정의)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // all, pending, answered, deleted
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] === '1';

// 페이지네이션 설정
$itemsPerPage = 10; // 한 페이지당 표시할 항목 수
$paginationPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // 현재 페이지 번호

// Q&A 목록 가져오기
if ($showDeleted) {
    // 삭제된 Q&A만 가져오기
    $allQnaList = getDeletedQnaForAdmin();
    $pendingCount = 0;
} else {
    // 일반 Q&A 가져오기
    $allQnaList = getAllQnaForAdmin();
    $pendingCount = getPendingQnaCount();
}

// 검색 필터 적용
if (!empty($search)) {
    $allQnaList = array_filter($allQnaList, function($qna) use ($search) {
        $searchLower = mb_strtolower($search);
        return (
            mb_strpos(mb_strtolower($qna['title'] ?? ''), $searchLower) !== false ||
            mb_strpos(mb_strtolower($qna['content'] ?? ''), $searchLower) !== false ||
            mb_strpos(mb_strtolower($qna['answer'] ?? ''), $searchLower) !== false ||
            mb_strpos(mb_strtolower($qna['user_id'] ?? ''), $searchLower) !== false
        );
    });
    $allQnaList = array_values($allQnaList);
}

// 상태 필터 적용
if ($filter === 'pending') {
    $qnaList = array_filter($allQnaList, function($qna) {
        $status = isset($qna['status']) ? trim($qna['status']) : '';
        return ($status !== 'answered');
    });
} elseif ($filter === 'answered') {
    $qnaList = array_filter($allQnaList, function($qna) {
        $status = isset($qna['status']) ? trim($qna['status']) : '';
        return ($status === 'answered');
    });
} else {
    $qnaList = $allQnaList;
}

// 배열 인덱스 재정렬
$qnaList = array_values($qnaList);

// 페이지네이션 계산
$totalItems = count($qnaList); // 필터링된 전체 항목 수
$totalPages = ceil($totalItems / $itemsPerPage); // 전체 페이지 수
$paginationPage = min($paginationPage, max(1, $totalPages)); // 유효한 페이지 범위로 제한

// 현재 페이지에 표시할 항목만 추출
$offset = ($paginationPage - 1) * $itemsPerPage;
$pagedQnaList = array_slice($qnaList, $offset, $itemsPerPage);

// 통계 계산
$totalCount = count($allQnaList);
$answeredCount = $totalCount - $pendingCount;

$adminCurrentPage = 'qna-manage.php';
include '../includes/admin-header.php';

// admin-header.php 포함 후에도 페이지네이션 변수 보호
if (!isset($paginationPage) || !is_numeric($paginationPage)) {
    $paginationPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
}
$paginationPage = (int)$paginationPage;
if ($paginationPage < 1) $paginationPage = 1;
if (isset($totalPages) && $paginationPage > $totalPages) {
    $paginationPage = max(1, $totalPages);
}
if (!isset($offset)) {
    $offset = ($paginationPage - 1) * $itemsPerPage;
}
?>

<style>
    .admin-content { padding: 32px; }
    .page-header { margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; }
    .page-header h1 { font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
    .page-header .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 14px; font-weight: 600; margin-left: 12px; background: #fee2e2; color: #991b1b; }
    .card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; margin-bottom: 24px; }
    .card-title { font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; }
    .form-group textarea {
        width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px;
        font-size: 15px; transition: border-color 0.2s; box-sizing: border-box; font-family: inherit;
    }
    .form-group textarea { min-height: 150px; resize: vertical; }
    .form-group textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
    .help { font-size: 13px; color: #6b7280; margin-top: 6px; }
    .btn { padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; text-decoration: none; display: inline-block; }
    .btn-primary { background: #6366f1; color: white; }
    .btn-primary:hover { background: #4f46e5; }
    .btn-secondary { background: #6b7280; color: white; }
    .btn-secondary:hover { background: #4b5563; }
    .btn-danger { background: #ef4444; color: white; }
    .btn-danger:hover { background: #dc2626; }
    .btn-sm { padding: 8px 16px; font-size: 14px; }
    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .qna-list { margin-top: 24px; }
    .qna-item { padding: 16px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
    .qna-item:last-child { border-bottom: none; }
    .qna-item:hover { background: #f9fafb; }
    .qna-item.pending { background: #fef3c7; }
    .qna-info { flex: 1; }
    .qna-title { font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 4px; }
    .qna-title .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-left: 8px; }
    .badge-pending { background: #fee2e2; color: #991b1b; }
    .badge-answered { background: #d1fae5; color: #065f46; }
    .qna-meta { font-size: 13px; color: #6b7280; }
    .qna-actions { display: flex; gap: 8px; }
    .qna-detail { margin-top: 24px; }
    .qna-question, .qna-answer { padding: 20px; border-radius: 8px; margin-bottom: 16px; }
    .qna-question { background: #f9fafb; border-left: 4px solid #6366f1; }
    .qna-answer { background: #eff6ff; border-left: 4px solid #10b981; }
    .qna-section-title { font-size: 14px; font-weight: 600; color: #6b7280; margin-bottom: 8px; }
    .qna-content { font-size: 15px; color: #1f2937; line-height: 1.6; white-space: pre-wrap; }
</style>

<div class="admin-content">
    <div class="page-header">
        <div>
            <h1>1:1 문의 관리</h1>
            <div style="display: flex; gap: 12px; margin-top: 8px; align-items: center; flex-wrap: wrap;">
                <?php if ($showDeleted): ?>
                    <span style="font-size: 14px; color: #dc2626;">삭제된 Q&A: <strong><?php echo number_format($totalCount); ?></strong>건</span>
                    <a href="/MVNO/admin/content/qna-manage.php" style="font-size: 14px; color: #6366f1; text-decoration: none;">← 일반 목록으로</a>
                <?php else: ?>
                    <span style="font-size: 14px; color: #6b7280;">전체: <strong><?php echo number_format($totalCount); ?></strong>건</span>
                    <span style="font-size: 14px; color: #d97706;">답변 대기: <strong><?php echo number_format($pendingCount); ?></strong>건</span>
                    <span style="font-size: 14px; color: #059669;">답변 완료: <strong><?php echo number_format($answeredCount); ?></strong>건</span>
                    <?php 
                    $deletedCount = count(getDeletedQnaForAdmin());
                    if ($deletedCount > 0): 
                    ?>
                        <a href="/MVNO/admin/content/qna-manage.php?show_deleted=1" style="font-size: 14px; color: #dc2626; text-decoration: none;">
                            삭제된 항목: <strong><?php echo number_format($deletedCount); ?></strong>건 (복구 가능)
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($viewId)): ?>
            <a href="/MVNO/admin/content/qna-manage.php<?php echo !empty($filter) && $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?><?php echo !empty($search) ? ($filter !== 'all' ? '&' : '?') . 'search=' . urlencode($search) : ''; ?>" class="btn btn-secondary">목록으로</a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($viewId) && $viewQna): ?>
        <!-- Q&A 상세 보기 -->
        <div class="card qna-detail">
            <div class="card-title">문의 상세</div>
            
            <div class="qna-question">
                <div class="qna-section-title">질문</div>
                <div style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">
                    <?php echo htmlspecialchars($viewQna['title']); ?>
                </div>
                <div class="qna-content"><?php echo nl2br(htmlspecialchars($viewQna['content'])); ?></div>
                <div style="margin-top: 12px; font-size: 13px; color: #6b7280;">
                    작성자: <?php echo htmlspecialchars($viewQna['user_id']); ?> | 
                    작성일: <?php echo date('Y-m-d H:i', strtotime($viewQna['created_at'])); ?>
                </div>
            </div>

            <?php if ($viewQna['status'] === 'answered' && !empty($viewQna['answer'])): ?>
                <div class="qna-answer">
                    <div class="qna-section-title">답변</div>
                    <div class="qna-content"><?php echo nl2br(htmlspecialchars($viewQna['answer'])); ?></div>
                    <div style="margin-top: 12px; font-size: 13px; color: #6b7280;">
                        답변자: <?php echo htmlspecialchars($viewQna['answered_by'] ?? '관리자'); ?> | 
                        답변일: <?php echo date('Y-m-d H:i', strtotime($viewQna['answered_at'])); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($viewQna['deleted_at']) && !empty($viewQna['deleted_at'])): ?>
                <!-- 삭제된 항목 알림 및 복구/영구 삭제 -->
                <div class="alert alert-error" style="margin-top: 24px;">
                    <strong>⚠ 이 Q&A는 삭제된 항목입니다.</strong><br>
                    삭제일시: <?php echo date('Y-m-d H:i', strtotime($viewQna['deleted_at'])); ?><br>
                    <div style="display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap;">
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($viewQna['id']); ?>">
                            <button type="submit" class="btn btn-primary">복구하기</button>
                        </form>
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('⚠️ 경고: 이 작업은 되돌릴 수 없습니다!\n\n정말로 이 Q&A를 영구적으로 삭제하시겠습니까?\n\n이 작업은 데이터베이스에서 완전히 제거되며 복구할 수 없습니다.');">
                            <input type="hidden" name="action" value="permanently_delete">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($viewQna['id']); ?>">
                            <input type="hidden" name="confirm_permanent_delete" value="yes">
                            <button type="submit" class="btn btn-danger">영구 삭제</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- 답변 작성/수정 폼 -->
                <div class="card" style="margin-top: 24px;">
                    <div class="card-title">
                        <?php echo $viewQna['status'] === 'answered' ? '답변 수정' : '답변 작성'; ?>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $viewQna['status'] === 'answered' ? 'update_answer' : 'answer'; ?>">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($viewQna['id']); ?>">
                        <?php if (isset($filter) && $filter !== 'all'): ?>
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <?php endif; ?>
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="answer">답변 내용 <?php if ($viewQna['status'] !== 'answered'): ?><span style="color: #ef4444;">*</span><?php endif; ?></label>
                            <textarea 
                                id="answer" 
                                name="answer" 
                                placeholder="답변 내용을 입력하세요 (비워두면 답변 삭제)"
                                <?php if ($viewQna['status'] !== 'answered'): ?>required<?php endif; ?>
                            ><?php echo htmlspecialchars($viewQna['answer'] ?? ''); ?></textarea>
                            <?php if ($viewQna['status'] === 'answered'): ?>
                                <p class="help" style="color: #6b7280; margin-top: 6px;">답변을 비워두면 답변이 삭제되고 상태가 '답변 대기'로 변경됩니다.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div style="display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap;">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $viewQna['status'] === 'answered' ? '답변 수정' : '답변 등록'; ?>
                            </button>
                            <a href="/MVNO/admin/content/qna-manage.php<?php echo !empty($filter) && $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?><?php echo !empty($search) ? ($filter !== 'all' ? '&' : '?') . 'search=' . urlencode($search) : ''; ?>" class="btn btn-secondary">목록으로</a>
                        </div>
                    </form>
                </div>
                
                <!-- 삭제 버튼을 별도 섹션으로 분리 (실수 방지) -->
                <div class="card" style="margin-top: 24px; border-left: 4px solid #ef4444;">
                    <div class="card-title" style="color: #dc2626;">위험한 작업</div>
                    <p style="color: #6b7280; margin-bottom: 16px;">이 Q&A를 삭제하시겠습니까? 삭제된 항목은 복구할 수 있습니다.</p>
                    <form method="POST" onsubmit="return confirm('정말 삭제하시겠습니까?\n\n삭제된 항목은 복구할 수 있습니다.');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($viewQna['id']); ?>">
                        <input type="hidden" name="confirm_delete" value="yes">
                        <?php if (isset($filter) && $filter !== 'all'): ?>
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <?php endif; ?>
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                        <button type="submit" class="btn btn-danger" style="width: auto;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 8px; vertical-align: middle;">
                                <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Q&A 삭제
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Q&A 목록 -->
        <div class="card qna-list">
            <div class="card-title">1:1 문의 목록</div>
            
            <!-- 검색 및 필터 -->
            <div style="margin-bottom: 24px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <!-- 검색 -->
                <form method="GET" style="display: flex; gap: 8px; flex: 1; min-width: 300px;">
                    <?php if (isset($filter) && $filter !== 'all'): ?>
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <?php endif; ?>
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="제목, 내용, 작성자로 검색..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                        style="flex: 1; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;"
                    >
                    <button type="submit" class="btn btn-secondary" style="padding: 10px 20px;">검색</button>
                </form>
            </div>
            
            <!-- 필터 탭 -->
            <div style="display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 2px solid #e5e7eb;">
                <a href="/MVNO/admin/content/qna-manage.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>" 
                   class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>"
                   style="padding: 12px 20px; text-decoration: none; color: #6b7280; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; <?php echo $filter === 'all' ? 'color: #6366f1; border-bottom-color: #6366f1; font-weight: 600;' : ''; ?>">
                    전체
                </a>
                <a href="/MVNO/admin/content/qna-manage.php?filter=pending<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                   class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>"
                   style="padding: 12px 20px; text-decoration: none; color: #6b7280; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; <?php echo $filter === 'pending' ? 'color: #6366f1; border-bottom-color: #6366f1; font-weight: 600;' : ''; ?>">
                    답변 대기
                </a>
                <a href="/MVNO/admin/content/qna-manage.php?filter=answered<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                   class="filter-tab <?php echo $filter === 'answered' ? 'active' : ''; ?>"
                   style="padding: 12px 20px; text-decoration: none; color: #6b7280; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; <?php echo $filter === 'answered' ? 'color: #6366f1; border-bottom-color: #6366f1; font-weight: 600;' : ''; ?>">
                    답변 완료
                </a>
            </div>
            
            <!-- 페이지네이션 정보 -->
            <?php if ($totalItems > 0): ?>
                <div style="margin-bottom: 16px; font-size: 14px; color: #6b7280;">
                    전체 <?php echo number_format($totalItems); ?>건 중 <?php echo number_format($offset + 1); ?>-<?php echo number_format(min($offset + $itemsPerPage, $totalItems)); ?>건 표시
                </div>
            <?php endif; ?>
            
            <?php if (empty($pagedQnaList)): ?>
                <div style="text-align: center; padding: 60px 20px; color: #6b7280;">
                    <p style="font-size: 16px; margin: 0;">등록된 문의가 없습니다.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                                <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">제목</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">작성자</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">상태</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">작성일</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagedQnaList as $qna): ?>
                                <tr style="border-bottom: 1px solid #e5e7eb; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor=''">
                                    <td style="padding: 12px;">
                                        <?php
                                        $viewUrl = '/MVNO/admin/content/qna-manage.php?view=' . urlencode($qna['id']);
                                        $viewParams = [];
                                        if ($filter !== 'all') $viewParams[] = 'filter=' . urlencode($filter);
                                        if (!empty($search)) $viewParams[] = 'search=' . urlencode($search);
                                        if ($paginationPage > 1) $viewParams[] = 'page=' . $paginationPage;
                                        if (!empty($viewParams)) $viewUrl .= '&' . implode('&', $viewParams);
                                        ?>
                                        <a href="<?php echo $viewUrl; ?>" 
                                           style="color: #6366f1; text-decoration: none; font-weight: 500;">
                                            <?php echo htmlspecialchars(mb_substr($qna['title'] ?? '(제목 없음)', 0, 50)); ?>
                                        </a>
                                    </td>
                                    <td style="padding: 12px; color: #6b7280;"><?php echo htmlspecialchars($qna['user_id'] ?? 'unknown'); ?></td>
                                    <td style="padding: 12px; text-align: center;">
                                        <?php if (trim($qna['status']) === 'answered'): ?>
                                            <span style="display: inline-block; padding: 4px 12px; background-color: #dbeafe; color: #1e40af; border-radius: 12px; font-size: 12px; font-weight: 500;">답변 완료</span>
                                        <?php else: ?>
                                            <span style="display: inline-block; padding: 4px 12px; background-color: #fef3c7; color: #92400e; border-radius: 12px; font-size: 12px; font-weight: 500;">답변 대기</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center; color: #6b7280; font-size: 13px;">
                                        <?php echo date('Y-m-d H:i', strtotime($qna['created_at'])); ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <div style="display: flex; gap: 6px; justify-content: center; align-items: center; flex-wrap: wrap;">
                                            <?php
                                            $detailUrl = '/MVNO/admin/content/qna-manage.php?view=' . urlencode($qna['id']);
                                            $detailParams = [];
                                            if ($filter !== 'all') $detailParams[] = 'filter=' . urlencode($filter);
                                            if (!empty($search)) $detailParams[] = 'search=' . urlencode($search);
                                            if ($paginationPage > 1) $detailParams[] = 'page=' . $paginationPage;
                                            if ($showDeleted) $detailParams[] = 'show_deleted=1';
                                            if (!empty($detailParams)) $detailUrl .= '&' . implode('&', $detailParams);
                                            ?>
                                            <a href="<?php echo $detailUrl; ?>" 
                                               class="btn btn-sm btn-primary" 
                                               style="padding: 6px 12px; font-size: 12px;">
                                                상세보기
                                            </a>
                                            <?php if ($showDeleted): ?>
                                                <form method="POST" style="display: inline-block;" onsubmit="return confirm('⚠️ 경고: 이 작업은 되돌릴 수 없습니다!\n\n정말로 이 Q&A를 영구적으로 삭제하시겠습니까?\n\n이 작업은 데이터베이스에서 완전히 제거되며 복구할 수 없습니다.');">
                                                    <input type="hidden" name="action" value="permanently_delete">
                                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($qna['id']); ?>">
                                                    <input type="hidden" name="confirm_permanent_delete" value="yes">
                                                    <button type="submit" class="btn btn-sm btn-danger" style="padding: 6px 12px; font-size: 12px;">
                                                        영구 삭제
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- 페이지네이션 -->
                <?php if ($totalPages > 1): ?>
                    <div style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 32px;">
                        <!-- 이전 페이지 -->
                        <?php if ($paginationPage > 1): ?>
                            <?php
                            $prevUrl = '/MVNO/admin/content/qna-manage.php?';
                            $params = [];
                            if ($filter !== 'all') $params[] = 'filter=' . urlencode($filter);
                            if (!empty($search)) $params[] = 'search=' . urlencode($search);
                            if ($showDeleted) $params[] = 'show_deleted=1';
                            $params[] = 'page=' . ($paginationPage - 1);
                            $prevUrl .= implode('&', $params);
                            ?>
                            <a href="<?php echo $prevUrl; ?>" 
                               style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s;"
                               onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1';"
                               onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#374151';">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        <?php else: ?>
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; color: #d1d5db; background: #f9fafb; cursor: not-allowed;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        <?php endif; ?>
                        
                        <!-- 페이지 번호 -->
                        <?php
                        // 페이지 번호 범위 계산 (현재 페이지 기준 앞뒤 2페이지씩 표시)
                        $startPage = max(1, $paginationPage - 2);
                        $endPage = min($totalPages, $paginationPage + 2);
                        
                        // URL 파라미터 구성 함수
                        if (!function_exists('buildQnaPageUrl')) {
                            function buildQnaPageUrl($page, $filter, $search, $showDeleted) {
                                $url = '/MVNO/admin/content/qna-manage.php?';
                                $params = [];
                                if ($filter !== 'all') $params[] = 'filter=' . urlencode($filter);
                                if (!empty($search)) $params[] = 'search=' . urlencode($search);
                                if ($showDeleted) $params[] = 'show_deleted=1';
                                $params[] = 'page=' . $page;
                                return $url . implode('&', $params);
                            }
                        }
                        
                        // 첫 페이지 표시
                        if ($startPage > 1): ?>
                            <a href="<?php echo buildPageUrl(1, $filter, $search, $showDeleted); ?>" 
                               style="display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s;"
                               onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1';"
                               onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#374151';">
                                1
                            </a>
                            <?php if ($startPage > 2): ?>
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; color: #9ca3af;">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <?php if ($i == $paginationPage): ?>
                                <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border: 1px solid #6366f1; border-radius: 8px; color: white; background: #6366f1; font-weight: 600;">
                                    <?php echo $i; ?>
                                </span>
                            <?php else: ?>
                                <a href="<?php echo buildQnaPageUrl($i, $filter, $search, $showDeleted); ?>" 
                                   style="display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s;"
                                   onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1';"
                                   onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#374151';">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <!-- 마지막 페이지 표시 -->
                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; color: #9ca3af;">...</span>
                            <?php endif; ?>
                            <a href="<?php echo buildQnaPageUrl($totalPages, $filter, $search, $showDeleted); ?>" 
                               style="display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s;"
                               onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1';"
                               onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#374151';">
                                <?php echo $totalPages; ?>
                            </a>
                        <?php endif; ?>
                        
                        <!-- 다음 페이지 -->
                        <?php if ($paginationPage < $totalPages): ?>
                            <?php
                            $nextUrl = '/MVNO/admin/content/qna-manage.php?';
                            $params = [];
                            if ($filter !== 'all') $params[] = 'filter=' . urlencode($filter);
                            if (!empty($search)) $params[] = 'search=' . urlencode($search);
                            if ($showDeleted) $params[] = 'show_deleted=1';
                            $params[] = 'page=' . ($paginationPage + 1);
                            $nextUrl .= implode('&', $params);
                            ?>
                            <a href="<?php echo $nextUrl; ?>" 
                               style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s;"
                               onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1';"
                               onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#374151';">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        <?php else: ?>
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; color: #d1d5db; background: #f9fafb; cursor: not-allowed;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/admin-footer.php'; ?>



