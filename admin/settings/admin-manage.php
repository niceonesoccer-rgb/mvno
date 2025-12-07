<?php
/**
 * 관리자 관리 페이지
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
                    $success = '관리자 정보가 수정되었습니다.';
                    // 수정 후 목록으로 리다이렉트
                    header('Location: /MVNO/admin/settings/admin-manage.php?success=update');
                    exit;
                }
            }
        }
    }
}

// 관리자 추가 처리 (사이드바 모달에서 호출)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_admin') {
    $userId = strtolower(trim($_POST['user_id'] ?? '')); // 소문자로 변환
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $role = $_POST['role'] ?? 'sub_admin';
    
    if (empty($userId) || empty($password) || empty($phone) || empty($name)) {
        $error = '모든 필드를 입력해주세요.';
    } elseif (!preg_match('/^[a-z0-9]{4,20}$/', $userId)) {
        $error = '아이디는 소문자 영문자와 숫자 조합 4-20자로 입력해주세요.';
    } elseif (strlen($password) < 8) {
        $error = '비밀번호는 최소 8자 이상이어야 합니다.';
    } else {
        // 기존 관리자 확인 (admins.json에서만)
        $adminsFile = getAdminsFilePath();
        $admins = [];
        
        if (file_exists($adminsFile)) {
            $data = json_decode(file_get_contents($adminsFile), true) ?: ['admins' => []];
            $admins = $data['admins'] ?? [];
        }
        
        // 아이디 중복 확인
        $isDuplicate = false;
        foreach ($admins as $admin) {
            if (isset($admin['user_id']) && $admin['user_id'] === $userId) {
                $isDuplicate = true;
                $error = '이미 사용 중인 아이디입니다.';
                break;
            }
        }
        
        if (!$isDuplicate) {
            // 관리자 추가
            $newAdmin = [
                'user_id' => $userId,
                'phone' => $phone,
                'name' => $name,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $currentUser['user_id'] ?? 'system'
            ];
            
            $admins[] = $newAdmin;
            $data = ['admins' => $admins];
            
            if (file_put_contents($adminsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                $success = '부관리자가 성공적으로 추가되었습니다.';
                // 추가 후 목록으로 리다이렉트
                header('Location: /MVNO/admin/settings/admin-manage.php?success=add');
                exit;
            } else {
                $error = '관리자 추가 중 오류가 발생했습니다.';
            }
        }
    }
}

// 관리자 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_admin') {
    $deleteUserId = $_POST['user_id'] ?? '';
    
    if ($deleteUserId === $currentUser['user_id']) {
        $error = '자기 자신은 삭제할 수 없습니다.';
    } elseif (!empty($deleteUserId)) {
        $adminsFile = getAdminsFilePath();
        if (file_exists($adminsFile)) {
            $data = json_decode(file_get_contents($adminsFile), true) ?: ['admins' => []];
            $admins = $data['admins'] ?? [];
            
            $updatedAdmins = array_filter($admins, function($admin) use ($deleteUserId) {
                return ($admin['user_id'] ?? '') !== $deleteUserId;
            });
            
            $data = ['admins' => array_values($updatedAdmins)];
            
            if (file_put_contents($adminsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                $success = '관리자가 삭제되었습니다.';
            } else {
                $error = '관리자 삭제 중 오류가 발생했습니다.';
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

// 관리자 목록 가져오기
$adminsFile = getAdminsFilePath();
$admins = [];

if (file_exists($adminsFile)) {
    $data = json_decode(file_get_contents($adminsFile), true) ?: ['admins' => []];
    $admins = $data['admins'] ?? [];
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
    
    .page-header p {
        font-size: 16px;
        color: #6b7280;
    }
    
    .admin-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        margin-bottom: 32px;
    }
    
    @media (max-width: 1024px) {
        .admin-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .card-title {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
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
    
    .btn-danger {
        background: #ef4444;
        color: white;
        padding: 6px 12px;
        font-size: 13px;
    }
    
    .btn-danger:hover {
        background: #dc2626;
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
    
    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .admin-table thead {
        background: #f9fafb;
    }
    
    .admin-table th {
        padding: 12px;
        text-align: left;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .admin-table td {
        padding: 12px;
        font-size: 14px;
        color: #6b7280;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .admin-table tr:hover {
        background: #f9fafb;
    }
    
    .role-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .role-badge.admin {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .role-badge.sub_admin {
        background: #fef3c7;
        color: #92400e;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #9ca3af;
    }
</style>

<div class="admin-content">
    <div class="page-header">
        <h1>관리자 관리</h1>
        <p>시스템 관리자 및 부관리자를 추가하고 관리할 수 있습니다.</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success']) && $_GET['success'] === 'update'): ?>
        <div class="alert alert-success">
            관리자 정보가 수정되었습니다.
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success']) && $_GET['success'] === 'add'): ?>
        <div class="alert alert-success">
            부관리자가 성공적으로 추가되었습니다.
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($editAdmin): ?>
        <!-- 관리자 정보 수정 폼 -->
        <div class="card" style="max-width: 600px; margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="card-title">관리자 정보 수정</h2>
                <a href="/MVNO/admin/settings/admin-manage.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px; text-decoration: none; display: inline-block;">목록으로</a>
            </div>
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
                    <a href="/MVNO/admin/settings/admin-manage.php" class="btn" style="background: #f3f4f6; color: #374151; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">취소</a>
                </div>
            </form>
        </div>
    <?php else: ?>
    <div class="admin-grid">
        <!-- 관리자 목록 -->
        <div class="card" style="grid-column: 1 / -1;">
            <h2 class="card-title">관리자 목록</h2>
            
            <?php if (empty($admins)): ?>
                <div class="empty-state">
                    등록된 관리자가 없습니다.
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>아이디</th>
                            <th>이름</th>
                            <th>전화번호</th>
                            <th>역할</th>
                            <th>가입일</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($admin['user_id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($admin['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($admin['phone'] ?? ''); ?></td>
                                <td>
                                    <span class="role-badge <?php echo ($admin['role'] ?? '') === 'admin' ? 'admin' : 'sub_admin'; ?>">
                                        <?php echo ($admin['role'] ?? '') === 'admin' ? '관리자' : '부관리자'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($admin['created_at'] ?? ''); ?></td>
                                <td>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <a href="/MVNO/admin/settings/admin-manage.php?edit=<?php echo urlencode($admin['user_id'] ?? ''); ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 13px; text-decoration: none; display: inline-block;">수정</a>
                                        <?php if (($admin['user_id'] ?? '') !== $currentUser['user_id']): ?>
                                            <form method="POST" style="display: inline;" onsubmit="event.preventDefault(); showConfirm('정말 이 관리자를 삭제하시겠습니까?', '관리자 삭제').then(result => { if(result) this.submit(); }); return false;">
                                                <input type="hidden" name="action" value="delete_admin">
                                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($admin['user_id'] ?? ''); ?>">
                                                <button type="submit" class="btn btn-danger">삭제</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #9ca3af; font-size: 12px;">본인</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// 푸터 포함
include '../includes/admin-footer.php';
?>

