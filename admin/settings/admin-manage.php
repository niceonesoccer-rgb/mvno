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

// 관리자 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_admin') {
    $deleteUserId = $_POST['user_id'] ?? '';
    
    if (empty($deleteUserId)) {
        $error = '삭제할 관리자 정보가 없습니다.';
    } else if ($deleteUserId === 'admin') {
        $error = 'admin 계정은 삭제할 수 없습니다.';
    } else {
        $pdo = getDBConnection();
        if (!$pdo) {
            $error = 'DB 연결에 실패했습니다.';
        } else {
            try {
                // 삭제할 관리자 확인
                $checkStmt = $pdo->prepare("SELECT role FROM users WHERE user_id = :user_id AND role IN ('admin','sub_admin') LIMIT 1");
                $checkStmt->execute([':user_id' => $deleteUserId]);
                $targetAdmin = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$targetAdmin) {
                    $error = '관리자 정보를 찾을 수 없습니다.';
                } else if ($targetAdmin['role'] === 'admin') {
                    $error = 'admin 계정은 삭제할 수 없습니다.';
                } else {
                    $pdo->beginTransaction();
                    
                    // admin_profiles 삭제
                    $pdo->prepare("DELETE FROM admin_profiles WHERE user_id = :user_id")
                        ->execute([':user_id' => $deleteUserId]);
                    
                    // users 테이블에서 삭제
                    $deleteStmt = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id AND role = 'sub_admin' LIMIT 1");
                    $deleteStmt->execute([':user_id' => $deleteUserId]);
                    
                    if ($deleteStmt->rowCount() < 1) {
                        $pdo->rollBack();
                        $error = '관리자 삭제에 실패했습니다.';
                    } else {
                        $pdo->commit();
                        header('Location: /MVNO/admin/users/member-list.php?tab=admins&success=delete');
                        exit;
                    }
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('admin-manage delete DB error: ' . $e->getMessage());
                $error = '관리자 삭제에 실패했습니다.';
            }
        }
    }
}

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
        // DB-only: users 테이블 업데이트 (admin/sub_admin)
        $pdo = getDBConnection();
        if (!$pdo) {
            $error = 'DB 연결에 실패했습니다.';
        } else {
            // admin 아이디가 아닌 경우 관리자 역할로 변경 불가
            $finalRole = ($editUserId === 'admin') ? 'admin' : 'sub_admin';

            if (!empty($password) && strlen($password) < 8) {
                $error = '비밀번호는 최소 8자 이상이어야 합니다.';
            } else {
                try {
                    $pdo->beginTransaction();

                    if (!empty($password)) {
                        $stmt = $pdo->prepare("
                            UPDATE users
                            SET phone = :phone,
                                name = :name,
                                role = :role,
                                password = :password,
                                updated_at = NOW()
                            WHERE user_id = :user_id
                              AND role IN ('admin','sub_admin')
                            LIMIT 1
                        ");
                        $stmt->execute([
                            ':phone' => $phone,
                            ':name' => $name,
                            ':role' => $finalRole,
                            ':password' => password_hash($password, PASSWORD_DEFAULT),
                            ':user_id' => $editUserId
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE users
                            SET phone = :phone,
                                name = :name,
                                role = :role,
                                updated_at = NOW()
                            WHERE user_id = :user_id
                              AND role IN ('admin','sub_admin')
                            LIMIT 1
                        ");
                        $stmt->execute([
                            ':phone' => $phone,
                            ':name' => $name,
                            ':role' => $finalRole,
                            ':user_id' => $editUserId
                        ]);
                    }

                    if ($stmt->rowCount() < 1) {
                        $pdo->rollBack();
                        $error = '관리자 정보를 찾을 수 없습니다.';
                    } else {
                        // admin_profiles에도 updated_at 반영 (존재하는 경우)
                        $pdo->prepare("UPDATE admin_profiles SET updated_at = NOW() WHERE user_id = :user_id")
                            ->execute([':user_id' => $editUserId]);

                        $pdo->commit();
                        header('Location: /MVNO/admin/users/member-list.php?tab=admins&success=update');
                        exit;
                    }
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    error_log('admin-manage update DB error: ' . $e->getMessage());
                    $error = '관리자 정보 저장에 실패했습니다.';
                }
            }
        }
    }
}


// 수정할 관리자 정보 가져오기
$editAdmin = null;
$editUserId = $_GET['edit'] ?? $_GET['user_id'] ?? '';
if (!empty($editUserId)) {
    $editAdmin = getUserById($editUserId);
    if (!$editAdmin || !in_array(($editAdmin['role'] ?? ''), ['admin', 'sub_admin'], true)) {
        $editAdmin = null;
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

<script>
function showDeleteModal() {
    document.getElementById('deleteModal').style.display = 'flex';
}

function hideDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// 모달 외부 클릭 시 닫기
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hideDeleteModal();
            }
        });
    }
});

function copyPageLink() {
    const pageLinkInput = document.getElementById('pageLink');
    if (pageLinkInput) {
        pageLinkInput.select();
        pageLinkInput.setSelectionRange(0, 99999); // 모바일 지원
        try {
            document.execCommand('copy');
            alert('페이지 링크가 클립보드에 복사되었습니다.');
        } catch (err) {
            // 클립보드 API 사용 (최신 브라우저)
            if (navigator.clipboard) {
                navigator.clipboard.writeText(pageLinkInput.value).then(function() {
                    alert('페이지 링크가 클립보드에 복사되었습니다.');
                });
            } else {
                alert('링크 복사에 실패했습니다. 수동으로 복사해주세요.');
            }
        }
    }
}
</script>

<div class="admin-content">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
        <div style="flex: 1;">
            <h1>관리자 정보 수정</h1>
            <?php if ($editAdmin): ?>
                <div style="margin-top: 8px; font-size: 13px; color: #6b7280;">
                    <span style="font-weight: 500;">페이지 링크:</span>
                    <input type="text" id="pageLink" readonly value="<?= htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" 
                           style="margin-left: 8px; padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px; width: 400px; background: #f9fafb; color: #374151;">
                    <button type="button" onclick="copyPageLink()" style="margin-left: 4px; padding: 4px 12px; background: #6366f1; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">복사</button>
                </div>
            <?php endif; ?>
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
                
                <div style="display: flex; gap: 12px; justify-content: space-between;">
                    <div style="display: flex; gap: 12px;">
                        <button type="submit" class="btn btn-primary">수정 완료</button>
                        <a href="/MVNO/admin/users/member-list.php?tab=admins" class="btn" style="background: #f3f4f6; color: #374151; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">취소</a>
                    </div>
                    <?php if (($editAdmin['user_id'] ?? '') !== 'admin'): ?>
                        <button type="button" onclick="showDeleteModal()" class="btn" style="background: #ef4444; color: white;">
                            삭제
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- 삭제 확인 모달 -->
        <?php if (($editAdmin['user_id'] ?? '') !== 'admin'): ?>
        <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                <h2 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 600; color: #1f2937;">관리자 삭제</h2>
                <p style="margin: 0 0 20px 0; color: #6b7280; line-height: 1.6;">
                    정말로 <strong><?= htmlspecialchars($editAdmin['name'] ?? $editAdmin['user_id'] ?? '') ?></strong> 관리자를 삭제하시겠습니까?<br>
                    이 작업은 되돌릴 수 없습니다.
                </p>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="delete_admin">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($editAdmin['user_id'] ?? '') ?>">
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="button" onclick="hideDeleteModal()" class="btn" style="background: #f3f4f6; color: #374151;">취소</button>
                        <button type="submit" class="btn" style="background: #ef4444; color: white;">삭제</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
// 푸터 포함
include '../includes/admin-footer.php';
?>

