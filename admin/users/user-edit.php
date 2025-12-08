<?php
/**
 * 일반 회원 정보 수정 페이지
 * 경로: /MVNO/admin/users/user-edit.php
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

// 일반 회원 정보 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $editUserId = $_POST['user_id'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($editUserId) || empty($phone) || empty($name) || empty($email)) {
        $error = '모든 필수 항목을 입력해주세요.';
    } else {
        // 전화번호 형식 검증
        $phoneNumbers = preg_replace('/[^\d]/', '', $phone);
        if (!preg_match('/^010\d{8}$/', $phoneNumbers)) {
            $error = '휴대폰 번호는 010으로 시작하는 11자리 숫자여야 합니다.';
        } else {
            // 전화번호 포맷팅
            $formattedPhone = '010-' . substr($phoneNumbers, 3, 4) . '-' . substr($phoneNumbers, 7, 4);
            
            // 이메일 형식 검증
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = '올바른 이메일 형식이 아닙니다.';
            } else {
                $usersFile = getUsersFilePath();
                if (file_exists($usersFile)) {
                    $data = json_decode(file_get_contents($usersFile), true) ?: ['users' => []];
                    $users = $data['users'] ?? [];
                    
                    $updated = false;
                    foreach ($users as &$user) {
                        if (isset($user['user_id']) && $user['user_id'] === $editUserId) {
                            $user['phone'] = $formattedPhone;
                            $user['name'] = $name;
                            $user['email'] = $email;
                            $updated = true;
                            break;
                        }
                    }
                    
                    if ($updated && empty($error)) {
                        $data = ['users' => $users];
                        if (file_put_contents($usersFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                            // 수정 후 회원 상세 페이지로 리다이렉트
                            header('Location: /MVNO/admin/users/member-detail.php?user_id=' . urlencode($editUserId) . '&success=update');
                            exit;
                        } else {
                            $error = '회원정보 저장에 실패했습니다.';
                        }
                    } elseif (!$updated) {
                        $error = '회원을 찾을 수 없습니다.';
                    }
                } else {
                    $error = '사용자 데이터 파일을 찾을 수 없습니다.';
                }
            }
        }
    }
}

// 수정할 회원 정보 가져오기
$editUser = null;
$editUserId = $_GET['user_id'] ?? '';
if (!empty($editUserId)) {
    $editUser = getUserById($editUserId);
    if (!$editUser || $editUser['role'] !== 'user') {
        header('Location: /MVNO/admin/users/member-list.php?tab=users&error=not_found');
        exit;
    }
} else {
    header('Location: /MVNO/admin/users/member-list.php?tab=users');
    exit;
}

// 현재 페이지 설정
$currentPage = 'user-edit.php';

// 헤더 포함
include '../includes/admin-header.php';
?>

<style>
    .admin-content {
        padding: 32px;
    }
    
    .page-header {
        margin-bottom: 32px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0;
    }
    
    .card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        max-width: 800px;
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
    
    .form-group input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: #6366f1;
    }
    
    .form-group input.error {
        border-color: #ef4444;
    }
    
    .form-help {
        font-size: 13px;
        color: #6b7280;
        margin-top: 4px;
    }
    
    .error-message {
        padding: 12px 16px;
        background: #fee2e2;
        color: #991b1b;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
    }
    
    .success-message {
        padding: 12px 16px;
        background: #d1fae5;
        color: #065f46;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
    }
    
    .button-group {
        display: flex;
        gap: 12px;
        margin-top: 32px;
        justify-content: flex-end;
    }
    
    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
    }
    
    .btn-primary {
        background: #6366f1;
        color: white;
    }
    
    .btn-primary:hover {
        background: #4f46e5;
    }
    
    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    
    .btn-secondary:hover {
        background: #e5e7eb;
    }
    
    .readonly-field {
        background: #f9fafb;
        color: #6b7280;
        cursor: not-allowed;
    }
</style>

<div class="admin-content">
    <div class="page-header">
        <h1>일반 회원 정보 수정</h1>
        <a href="/MVNO/admin/users/member-detail.php?user_id=<?php echo urlencode($editUserId); ?>" class="btn btn-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            이전으로
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success']) && $_GET['success'] === 'update'): ?>
        <div class="success-message">
            회원정보가 성공적으로 수정되었습니다.
        </div>
    <?php endif; ?>
    
    <div class="card">
        <form method="POST" id="userEditForm">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($editUser['user_id'] ?? ''); ?>">
            
            <div class="form-group">
                <label for="user_id">아이디</label>
                <input 
                    type="text" 
                    id="user_id" 
                    name="user_id_display" 
                    value="<?php echo htmlspecialchars($editUser['user_id'] ?? ''); ?>"
                    class="readonly-field"
                    readonly
                >
                <div class="form-help">아이디는 변경할 수 없습니다.</div>
            </div>
            
            <div class="form-group">
                <label for="name">
                    이름
                    <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="<?php echo htmlspecialchars($editUser['name'] ?? ''); ?>"
                    required
                    maxlength="50"
                >
                <div class="form-help">실명을 입력해주세요.</div>
            </div>
            
            <div class="form-group">
                <label for="email">
                    이메일
                    <span class="required">*</span>
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?php echo htmlspecialchars($editUser['email'] ?? ''); ?>"
                    required
                >
                <div class="form-help">정확한 이메일 주소를 입력해주세요.</div>
            </div>
            
            <div class="form-group">
                <label for="phone">
                    휴대폰 번호
                    <span class="required">*</span>
                </label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    value="<?php echo htmlspecialchars($editUser['phone'] ?? ''); ?>"
                    placeholder="010-1234-5678"
                    pattern="010-\d{4}-\d{4}"
                    maxlength="13"
                    required
                >
                <div class="form-help">010으로 시작하는 휴대폰 번호를 입력해주세요 (예: 010-1234-5678)</div>
            </div>
            
            <?php if (isset($editUser['sns_provider'])): ?>
                <div class="form-group">
                    <label for="sns_provider">가입 방식</label>
                    <input 
                        type="text" 
                        id="sns_provider" 
                        value="SNS 가입 (<?php echo strtoupper($editUser['sns_provider'] ?? ''); ?>)"
                        class="readonly-field"
                        readonly
                    >
                    <div class="form-help">SNS 가입 회원입니다.</div>
                </div>
            <?php endif; ?>
            
            <div class="button-group">
                <a href="/MVNO/admin/users/member-detail.php?user_id=<?php echo urlencode($editUserId); ?>" class="btn btn-secondary">
                    취소
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    수정 완료
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const phoneInput = document.getElementById('phone');
    
    // 전화번호 자동 포맷팅
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^\d]/g, '');
        
        // 010으로 시작하도록 강제
        if (value.length > 0 && !value.startsWith('010')) {
            value = '010' + value.replace(/^010/, '');
        }
        
        // 11자리 제한
        if (value.length > 11) {
            value = value.substring(0, 11);
        }
        
        // 하이픈 추가
        if (value.length > 3) {
            value = value.substring(0, 3) + '-' + value.substring(3);
        }
        if (value.length > 8) {
            value = value.substring(0, 8) + '-' + value.substring(8, 12);
        }
        
        e.target.value = value;
        
        // 형식 검증
        const phoneNumbers = value.replace(/[^\d]/g, '');
        if (phoneNumbers.length === 11 && /^010\d{8}$/.test(phoneNumbers)) {
            e.target.classList.remove('error');
        } else if (phoneNumbers.length > 0) {
            e.target.classList.add('error');
        } else {
            e.target.classList.remove('error');
        }
    });
    
    // 포커스 시 자동 입력
    phoneInput.addEventListener('focus', function(e) {
        if (!e.target.value || !e.target.value.startsWith('010')) {
            e.target.value = '010-';
        }
    });
    
    // 블러 시 형식 검증
    phoneInput.addEventListener('blur', function(e) {
        const phoneNumbers = e.target.value.replace(/[^\d]/g, '');
        if (phoneNumbers.length > 0 && (!/^010\d{8}$/.test(phoneNumbers))) {
            e.target.classList.add('error');
        }
    });
    
    // 폼 제출 검증
    document.getElementById('userEditForm').addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = phoneInput.value.trim();
        const phoneNumbers = phone.replace(/[^\d]/g, '');
        
        if (!name) {
            e.preventDefault();
            alert('이름을 입력해주세요.');
            document.getElementById('name').focus();
            return false;
        }
        
        if (!email) {
            e.preventDefault();
            alert('이메일을 입력해주세요.');
            document.getElementById('email').focus();
            return false;
        }
        
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            e.preventDefault();
            alert('올바른 이메일 형식이 아닙니다.');
            document.getElementById('email').focus();
            return false;
        }
        
        if (!/^010\d{8}$/.test(phoneNumbers)) {
            e.preventDefault();
            alert('휴대폰 번호는 010으로 시작하는 11자리 숫자여야 합니다.');
            phoneInput.classList.add('error');
            phoneInput.focus();
            return false;
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>







