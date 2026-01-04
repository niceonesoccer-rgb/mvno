<?php
/**
 * 약관/개인정보처리방침 버전 관리 페이지
 * 경로: /MVNO/admin/settings/terms-versions.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/terms-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdmin()) {
    header('Location: /MVNO/admin/');
    exit;
}

$error = '';
$success = '';
$currentUser = getCurrentUser();
$createdBy = $currentUser['user_id'] ?? 'admin';

// 타입 (이용약관 또는 개인정보처리방침)
$type = $_GET['type'] ?? 'terms_of_service';
if (!in_array($type, ['terms_of_service', 'privacy_policy'])) {
    $type = 'terms_of_service';
}

// 편집할 버전 ID
$editId = $_GET['edit'] ?? null;
$editVersion = null;
if ($editId) {
    $editVersion = getTermsVersionById(intval($editId));
    if ($editVersion && $editVersion['type'] !== $type) {
        $editVersion = null;
        $editId = null;
    }
}

// 버전 목록 가져오기
$versionList = getTermsVersionList($type, true);
$activeVersion = getActiveTermsVersion($type);

// 버전 추가/수정
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'save') {
        $version = trim($_POST['version'] ?? '');
        $effectiveDate = trim($_POST['effective_date'] ?? '');
        $announcementDate = trim($_POST['announcement_date'] ?? '') ?: null;
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $setAsActive = isset($_POST['is_active']) && $_POST['is_active'] === '1';
        
        if (empty($version) || empty($effectiveDate) || empty($title) || empty($content)) {
            $error = '모든 필수 항목을 입력해주세요.';
        } else {
            if ($editId) {
                // 수정
                $result = updateTermsVersion(intval($editId), [
                    'version' => $version,
                    'effective_date' => $effectiveDate,
                    'announcement_date' => $announcementDate,
                    'title' => $title,
                    'content' => $content,
                    'is_active' => $setAsActive ? 1 : 0
                ]);
                if ($result) {
                    $success = '버전이 수정되었습니다.';
                    header('Location: ?type=' . $type);
                    exit;
                } else {
                    $error = '버전 수정에 실패했습니다.';
                }
            } else {
                // 추가
                $result = saveTermsVersion(
                    $type,
                    $version,
                    $effectiveDate,
                    $title,
                    $content,
                    $announcementDate,
                    $setAsActive,
                    $createdBy
                );
                if ($result) {
                    $success = '버전이 추가되었습니다.';
                    header('Location: ?type=' . $type);
                    exit;
                } else {
                    $error = '버전 추가에 실패했습니다. (버전 번호가 중복될 수 있습니다)';
                }
            }
        }
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        if (deleteTermsVersion($id)) {
            $success = '버전이 삭제되었습니다.';
            header('Location: ?type=' . $type);
            exit;
        } else {
            $error = '버전 삭제에 실패했습니다. (활성 버전은 삭제할 수 없습니다)';
        }
    } elseif ($action === 'set_active' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        if (updateTermsVersion($id, ['is_active' => 1])) {
            $success = '활성 버전으로 설정되었습니다.';
            header('Location: ?type=' . $type);
            exit;
        } else {
            $error = '활성 버전 설정에 실패했습니다.';
        }
    }
    
    // POST 후 새로고침하여 GET으로 리다이렉트
    if ($success) {
        header('Location: ?type=' . $type);
        exit;
    }
}

// 목록 다시 가져오기 (POST 후)
$versionList = getTermsVersionList($type, true);
$activeVersion = getActiveTermsVersion($type);

$currentPage = 'terms-versions.php';
include '../includes/admin-header.php';

$typeLabel = $type === 'terms_of_service' ? '이용약관' : '개인정보처리방침';
?>

<style>
    .admin-content { padding: 32px; }
    .page-header { margin-bottom: 32px; }
    .page-header h1 { font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
    
    .tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 24px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .tab {
        padding: 12px 24px;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        font-size: 15px;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
        bottom: -2px;
        text-decoration: none;
        display: inline-block;
    }
    
    .tab:hover {
        color: #374151;
        background: #f9fafb;
    }
    
    .tab.active {
        color: #6366f1;
        border-bottom-color: #6366f1;
        background: #f9fafb;
    }
    
    .card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; margin-bottom: 24px; }
    .card-title { font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; }
    .form-group input[type="text"], .form-group input[type="date"], .form-group textarea {
        width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px;
        font-size: 15px; transition: border-color 0.2s; box-sizing: border-box; font-family: inherit;
    }
    .form-group textarea { min-height: 200px; resize: vertical; }
    .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
    .help { font-size: 13px; color: #6b7280; margin-top: 6px; }
    .btn { padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; text-decoration: none; display: inline-block; }
    .btn-primary { background: #6366f1; color: white; }
    .btn-primary:hover { background: #4f46e5; }
    .btn-secondary { background: #6b7280; color: white; }
    .btn-secondary:hover { background: #4b5563; }
    .btn-danger { background: #ef4444; color: white; padding: 8px 16px; font-size: 14px; }
    .btn-danger:hover { background: #dc2626; }
    .btn-sm { padding: 8px 16px; font-size: 14px; }
    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    
    table { width: 100%; border-collapse: collapse; }
    table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    table th { background: #f9fafb; font-weight: 600; color: #374151; }
    table tr:hover { background: #f9fafb; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: 600; }
    .badge-active { background: #d1fae5; color: #065f46; }
    .badge-inactive { background: #e5e7eb; color: #6b7280; }
</style>

<div class="admin-content">
    <div class="page-header">
        <h1>약관 버전 관리</h1>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="tabs">
        <a href="?type=terms_of_service" class="tab <?php echo $type === 'terms_of_service' ? 'active' : ''; ?>">이용약관</a>
        <a href="?type=privacy_policy" class="tab <?php echo $type === 'privacy_policy' ? 'active' : ''; ?>">개인정보처리방침</a>
    </div>

    <!-- 버전 추가/수정 폼 -->
    <div class="card">
        <div class="card-title"><?php echo $editId ? '버전 수정' : '새 버전 추가'; ?> - <?php echo $typeLabel; ?></div>
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <?php if ($editId): ?>
                <input type="hidden" name="id" value="<?php echo $editId; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="version">버전 번호 <span style="color: #ef4444;">*</span></label>
                <input type="text" id="version" name="version" value="<?php echo htmlspecialchars($editVersion['version'] ?? ''); ?>" required placeholder="예: v3.8">
                <div class="help">버전 번호를 입력하세요 (예: v3.8, v1.0)</div>
            </div>
            
            <div class="form-group">
                <label for="effective_date">시행일자 <span style="color: #ef4444;">*</span></label>
                <input type="date" id="effective_date" name="effective_date" value="<?php echo $editVersion['effective_date'] ?? date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="announcement_date">공고일자</label>
                <input type="date" id="announcement_date" name="announcement_date" value="<?php echo $editVersion['announcement_date'] ?? ''; ?>">
                <div class="help">공고일자가 있는 경우 입력하세요</div>
            </div>
            
            <div class="form-group">
                <label for="title">제목 <span style="color: #ef4444;">*</span></label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($editVersion['title'] ?? $typeLabel); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="content">내용 (HTML) <span style="color: #ef4444;">*</span></label>
                <textarea id="content" name="content" rows="20" style="font-family: monospace; font-size: 13px;" required><?php echo htmlspecialchars($editVersion['content'] ?? ''); ?></textarea>
                <div class="help">HTML 형식으로 내용을 입력하세요.</div>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" <?php echo ($editVersion && $editVersion['is_active'] == 1) || (!$editId && !$activeVersion) ? 'checked' : ''; ?>>
                    <span>현재 활성 버전으로 설정</span>
                </label>
                <div class="help">체크하면 이 버전이 현재 활성 버전으로 설정됩니다. 기존 활성 버전은 자동으로 비활성화됩니다.</div>
            </div>
            
            <div style="margin-top: 24px; display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary"><?php echo $editId ? '수정' : '추가'; ?></button>
                <?php if ($editId): ?>
                    <a href="?type=<?php echo $type; ?>" class="btn btn-secondary">취소</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- 버전 목록 -->
    <div class="card">
        <div class="card-title">버전 목록 - <?php echo $typeLabel; ?></div>
        <?php if (empty($versionList)): ?>
            <p style="color: #6b7280; padding: 20px 0;">등록된 버전이 없습니다.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 100px;">버전</th>
                            <th style="width: 120px;">시행일자</th>
                            <th style="width: 120px;">공고일자</th>
                            <th>제목</th>
                            <th style="width: 100px;">상태</th>
                            <th style="width: 200px;">작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($versionList as $ver): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($ver['version']); ?></strong></td>
                                <td><?php echo htmlspecialchars($ver['effective_date']); ?></td>
                                <td><?php echo $ver['announcement_date'] ? htmlspecialchars($ver['announcement_date']) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($ver['title']); ?></td>
                                <td>
                                    <?php if ($ver['is_active'] == 1): ?>
                                        <span class="badge badge-active">활성</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">비활성</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="?type=<?php echo $type; ?>&edit=<?php echo $ver['id']; ?>" class="btn btn-secondary btn-sm">수정</a>
                                        <?php if ($ver['is_active'] != 1): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('활성 버전으로 설정하시겠습니까?');">
                                                <input type="hidden" name="action" value="set_active">
                                                <input type="hidden" name="id" value="<?php echo $ver['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">활성화</button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $ver['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">삭제</button>
                                            </form>
                                        <?php endif; ?>
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

<?php include '../includes/admin-footer.php'; ?>
