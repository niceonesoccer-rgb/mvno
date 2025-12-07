<?php
/**
 * 관리자 정보 수정 페이지
 * 경로: /MVNO/admin/settings/admin-manage.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 체크
if (!isAdmin()) {
    header('Location: /MVNO/admin/');
    exit;
}

$currentUser = getCurrentUser();
$error = '';
$success = '';

// 관리자 정보 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_admin') {
    $editUserId = $_POST['user_id'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $role = $_POST['role'] ?? 'sub_admin';
    $password = $_POST['password'] ?? '';
    
    if (empty($editUserId) || empty($phone) || empty($name)) {
        $error = '모든 필드를 입력해주세요.';
    } else {
        $adminsFile = getAdminsFilePath();
        if (file_exists($adminsFile)) {
            $data = json_decode(file_get_contents($adminsFile), true) ?: ['admins' => []];
            $admins = $data['admins'] ?? [];
            
            $updated = false;
            foreach ($admins as &$admin) {
                if (isset($admin['user_id']) && $admin['user_id'] === $editUserId) {
                    $admin['phone'] = $phone;
                    $admin['name'] = $name;
                    
                    // admin 아이디가 아닌 경우 관리자 역할로 변경 불가
                    if ($editUserId === 'admin') {
                        $admin['role'] = 'admin'; // admin은 항상 관리자
                    } else {
                        $admin['role'] = 'sub_admin'; // 그 외는 부관리자만 가능
                    }
                    
                    // 비밀번호가 입력된 경우에만 업데이트
                    if (!empty($password)) {
                        if (strlen($password) < 8) {
                            $error = '비밀번호는 최소 8자 이상이어야 합니다.';
                            break;
                        }
                        $admin['password'] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    
                    $updated = true;
                    break;
                }
            }
            
            if ($updated && empty($error)) {
                $data = ['admins' => $admins];
                if (file_put_contents($adminsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                    // 수정 후 관리자 목록으로 리다이렉트
                    header('Location: /MVNO/admin/users/member-list.php?tab=admins&success=update');
                    exit;
                }
            }
        }
    }
}


// 수정할 관리자 정보 가져오기
$editAdmin = null;
$editUserId = $_GET['edit'] ?? '';
if (!empty($editUserId)) {
    $adminsFile = getAdminsFilePath();
    if (file_exists($adminsFile)) {
        $data = json_decode(file_get_contents($adminsFile), true) ?: ['admins' => []];
        $admins = $data['admins'] ?? [];
        foreach ($admins as $admin) {
            if (isset($admin['user_id']) && $admin['user_id'] === $editUserId) {
                $editAdmin = $admin;
                break;
            }
        }
    }
}


// 현재 페이지 설정
$currentPage = 'admin-manage.php';

// 헤더 포함
include '../includes/admin-header.php';
?>

<style>
    .admin-content {
        padding: 32px;
    }
    
    .page-header {
        margin-bottom: 32px;
    }
    
    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-group label .required {
        color: #ef4444;
        margin-left: 4px;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .form-help {
        font-size: 13px;
        color: #6b7280;
        margin-top: 6px;
    }
    
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-primary {
        background: #6366f1;
        color: white;
    }
    
    .btn-primary:hover {
        background: #4f46e5;
    }
    
    .alert {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #10b981;
    }
    
</style>

<div class="admin-content">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
        <div style="flex: 1;">
            <h1>관리자 정보 수정</h1>
        </div>
        <div>
            <a href="/MVNO/admin/users/member-list.php?tab=admins" class="btn" style="background: #f3f4f6; color: #374151; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                목록으로
            </a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!$editAdmin): ?>
        <div class="alert alert-error">
            관리자 정보를 찾을 수 없습니다.
        </div>
        <div style="margin-top: 20px;">
            <a href="/MVNO/admin/users/member-list.php?tab=admins" class="btn btn-primary" style="text-decoration: none; display: inline-block;">목록으로 돌아가기</a>
        </div>
    <?php else: ?>
        <!-- 관리자 정보 수정 폼 -->
        <div class="card" style="max-width: 600px; margin-bottom: 24px;">
            <form method="POST">
                <input type="hidden" name="action" value="update_admin">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($editAdmin['user_id'] ?? ''); ?>">
                
                <div class="form-group">
                    <label for="edit_user_id">아이디</label>
                    <input type="text" id="edit_user_id" value="<?php echo htmlspecialchars($editAdmin['user_id'] ?? ''); ?>" disabled style="background: #f3f4f6; color: #6b7280;">
                    <div class="form-help">아이디는 변경할 수 없습니다.</div>
                </div>
                
                <div class="form-group">
                    <label for="edit_phone">전화번호 <span class="required">*</span></label>
                    <input type="tel" id="edit_phone" name="phone" required value="<?php echo htmlspecialchars($editAdmin['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="edit_name">이름 <span class="required">*</span></label>
                    <input type="text" id="edit_name" name="name" required value="<?php echo htmlspecialchars($editAdmin['name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="edit_password">비밀번호</label>
                    <input type="password" id="edit_password" name="password" minlength="8">
                    <div class="form-help">변경하지 않으려면 비워두세요. (최소 8자 이상)</div>
                </div>
                
                <div class="form-group">
                    <label for="edit_role">역할 <span class="required">*</span></label>
                    <?php if (($editAdmin['user_id'] ?? '') === 'admin'): ?>
                        <select id="edit_role" name="role" required>
                            <option value="admin" <?php echo (($editAdmin['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>관리자</option>
                        </select>
                        <div class="form-help">관리자(admin)는 역할을 변경할 수 없습니다.</div>
                    <?php else: ?>
                        <select id="edit_role" name="role" required>
                            <option value="sub_admin" <?php echo (($editAdmin['role'] ?? '') === 'sub_admin') ? 'selected' : ''; ?>>부관리자</option>
                        </select>
                        <div class="form-help">부관리자만 추가 및 수정 가능합니다.</div>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary">수정 완료</button>
                    <a href="/MVNO/admin/users/member-list.php?tab=admins" class="btn" style="background: #f3f4f6; color: #374151; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">취소</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php
// 푸터 포함
include '../includes/admin-footer.php';
?>

