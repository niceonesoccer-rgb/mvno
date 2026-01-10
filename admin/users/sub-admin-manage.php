<?php
/**
 * 부관리자 관리 페이지
 * 경로: /admin/users/sub-admin-manage.php
 */

require_once __DIR__ . '/../../includes/data/path-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 체크 (admin 계정만 접근 가능)
if (!isAdmin()) {
    header('Location: ' . getAssetPath('/admin/index.php'));
    exit;
}

// 부관리자 관리 페이지는 admin 계정만 접근 가능
$currentUser = getCurrentUser();
if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
    header('Location: ' . getAssetPath('/admin/index.php'));
    exit;
}

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

// 탭 선택 (리스트, 추가)
$activeTab = $_GET['tab'] ?? 'list'; // 'list', 'add'

// 부관리자 목록 조회
$subAdmins = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.name,
            u.phone,
            u.role,
            u.created_at,
            u.updated_at,
            ap.created_by
        FROM users u
        LEFT JOIN admin_profiles ap ON u.user_id = ap.user_id
        WHERE u.role = 'sub_admin'
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $subAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Sub-admin list error: ' . $e->getMessage());
}

// 부관리자 추가 처리
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_sub_admin') {
    $userId = strtolower(trim($_POST['user_id'] ?? ''));
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $name = trim($_POST['name'] ?? '');
    
    // 유효성 검사
    if (empty($userId) || empty($password) || empty($phone) || empty($name)) {
        $error = '모든 필드를 입력해주세요.';
    } elseif ($password !== $passwordConfirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } elseif (!preg_match('/^[a-z0-9]{4,20}$/', $userId)) {
        $error = '아이디는 소문자 영문자와 숫자 조합 4-20자여야 합니다.';
    } elseif (strlen($password) < 8) {
        $error = '비밀번호는 최소 8자 이상이어야 합니다.';
    } else {
        // registerDirectUser 함수 사용
        $additionalData = [
            'phone' => $phone,
            'created_by' => $currentUser['user_id'] ?? 'system'
        ];
        
        $result = registerDirectUser($userId, $password, null, $name, 'sub_admin', $additionalData);
        
        if ($result['success']) {
            $success = '부관리자가 성공적으로 추가되었습니다.';
            $activeTab = 'list'; // 추가 후 리스트 탭으로 이동
            
            // 목록 새로고침
            $stmt = $pdo->prepare("
                SELECT 
                    u.user_id,
                    u.name,
                    u.phone,
                    u.role,
                    u.created_at,
                    u.updated_at,
                    ap.created_by
                FROM users u
                LEFT JOIN admin_profiles ap ON u.user_id = ap.user_id
                WHERE u.role = 'sub_admin'
                ORDER BY u.created_at DESC
            ");
            $stmt->execute();
            $subAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = $result['message'] ?? '부관리자 추가 중 오류가 발생했습니다.';
        }
    }
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1>부관리자 관리</h1>
        <p>부관리자 목록을 조회하고 새로운 부관리자를 추가할 수 있습니다.</p>
    </div>
    
    <!-- 탭 메뉴 -->
    <div class="tab-menu" style="margin-bottom: 24px; border-bottom: 2px solid #e5e7eb;">
        <button type="button" class="tab-button <?php echo $activeTab === 'list' ? 'active' : ''; ?>" 
                onclick="switchTab('list')" style="padding: 12px 24px; background: none; border: none; border-bottom: 2px solid <?php echo $activeTab === 'list' ? '#6366f1' : 'transparent'; ?>; color: <?php echo $activeTab === 'list' ? '#6366f1' : '#6b7280'; ?>; font-size: 15px; font-weight: 600; cursor: pointer; margin-right: 8px;">
            부관리자 리스트 (<?php echo count($subAdmins); ?>)
        </button>
        <button type="button" class="tab-button <?php echo $activeTab === 'add' ? 'active' : ''; ?>" 
                onclick="switchTab('add')" style="padding: 12px 24px; background: none; border: none; border-bottom: 2px solid <?php echo $activeTab === 'add' ? '#6366f1' : 'transparent'; ?>; color: <?php echo $activeTab === 'add' ? '#6366f1' : '#6b7280'; ?>; font-size: 15px; font-weight: 600; cursor: pointer;">
            부관리자 추가
        </button>
    </div>
    
    <!-- 메시지 표시 -->
    <?php if ($error): ?>
        <div style="padding: 12px 16px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div style="padding: 12px 16px; background: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <!-- 리스트 탭 -->
    <?php if ($activeTab === 'list'): ?>
        <div class="content-card">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">아이디</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">이름</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">휴대폰</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">생성일</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">생성자</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subAdmins)): ?>
                            <tr>
                                <td colspan="6" style="padding: 40px; text-align: center; color: #6b7280;">
                                    등록된 부관리자가 없습니다.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subAdmins as $admin): ?>
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 12px; color: #1f2937;"><?php echo htmlspecialchars($admin['user_id']); ?></td>
                                    <td style="padding: 12px; color: #1f2937;"><?php echo htmlspecialchars($admin['name']); ?></td>
                                    <td style="padding: 12px; color: #1f2937;"><?php echo htmlspecialchars($admin['phone']); ?></td>
                                    <td style="padding: 12px; color: #6b7280;"><?php echo date('Y-m-d H:i', strtotime($admin['created_at'])); ?></td>
                                    <td style="padding: 12px; color: #6b7280;"><?php echo htmlspecialchars($admin['created_by'] ?? '-'); ?></td>
                                    <td style="padding: 12px; text-align: center;">
                                        <a href="<?php echo getAssetPath('/admin/settings/admin-manage.php'); ?>?user_id=<?php echo urlencode($admin['user_id']); ?>" 
                                           style="padding: 6px 12px; background: #6366f1; color: white; text-decoration: none; border-radius: 6px; font-size: 14px; display: inline-block;">
                                            수정
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- 추가 탭 -->
    <?php if ($activeTab === 'add'): ?>
        <div class="content-card">
            <form method="POST" action="" id="addSubAdminForm">
                <input type="hidden" name="action" value="add_sub_admin">
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        아이디 <span style="color: #ef4444;">*</span>
                    </label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="user_id" name="user_id" required pattern="[a-z0-9]{4,20}" 
                               style="flex: 1; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;" 
                               placeholder="소문자 영문자와 숫자 조합 4-20자">
                        <button type="button" id="checkIdBtn" onclick="checkDuplicate()" 
                                style="padding: 12px 20px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; white-space: nowrap;">
                            중복확인
                        </button>
                    </div>
                    <div id="idCheckResult" style="font-size: 13px; margin-top: 6px;"></div>
                    <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">소문자 영문자와 숫자 조합 4-20자</div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        이름 <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="name" required 
                           style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;" 
                           placeholder="이름">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        휴대폰 <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="tel" id="phone" name="phone" required pattern="010-\d{4}-\d{4}" 
                           style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;" 
                           placeholder="010-1234-5678" maxlength="13">
                    <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">휴대폰 번호를 입력해주세요. (예: 010-1234-5678)</div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        비밀번호 <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="password" id="password" name="password" required minlength="8" 
                           style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;" 
                           placeholder="최소 8자 이상">
                    <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">최소 8자 이상 입력해주세요.</div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        비밀번호 확인 <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="8" 
                           style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;" 
                           placeholder="비밀번호를 다시 입력해주세요">
                    <div id="passwordMatchResult" style="font-size: 13px; margin-top: 6px;"></div>
                </div>
                
                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="submit" style="flex: 1; padding: 12px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                        추가
                    </button>
                    <button type="button" onclick="switchTab('list')" 
                            style="flex: 1; padding: 12px 24px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                        취소
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
.admin-content {
    padding: 24px;
    max-width: 1200px;
    margin: 0 auto;
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

.content-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tab-button.active {
    color: #6366f1 !important;
    border-bottom-color: #6366f1 !important;
}
</style>

<script>
// 전역 함수로 정의
window.switchTab = function(tab) {
    window.location.href = '<?php echo getAssetPath('/admin/users/sub-admin-manage.php'); ?>?tab=' + tab;
};

let idChecked = false;
let idValid = false;

// 아이디 입력 시 소문자로 자동 변환
const userIdInput = document.getElementById('user_id');
if (userIdInput) {
    userIdInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^a-z0-9]/gi, '').toLowerCase();
        document.getElementById('idCheckResult').innerHTML = '';
        idChecked = false;
        idValid = false;
    });
}

// 중복 확인
function checkDuplicate() {
    const userId = document.getElementById('user_id').value.trim().toLowerCase();
    const resultDiv = document.getElementById('idCheckResult');
    
    if (!userId) {
        resultDiv.innerHTML = '<span style="color: #ef4444;">아이디를 입력해주세요.</span>';
        return;
    }
    
    if (!/^[a-z0-9]{4,20}$/.test(userId)) {
        resultDiv.innerHTML = '<span style="color: #ef4444;">아이디는 소문자 영문자와 숫자 조합 4-20자여야 합니다.</span>';
        return;
    }
    
    fetch(`<?php echo getAssetPath('/api/check-admin-duplicate.php'); ?>?type=user_id&value=${encodeURIComponent(userId)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && !data.duplicate) {
                resultDiv.innerHTML = '<span style="color: #10b981;">사용 가능한 아이디입니다.</span>';
                idChecked = true;
                idValid = true;
                userIdInput.style.borderColor = '#10b981';
                userIdInput.setAttribute('readonly', 'readonly');
            } else {
                resultDiv.innerHTML = '<span style="color: #ef4444;">' + (data.message || '이미 사용 중인 아이디입니다.') + '</span>';
                idChecked = true;
                idValid = false;
                userIdInput.style.borderColor = '#ef4444';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<span style="color: #ef4444;">중복 확인 중 오류가 발생했습니다.</span>';
        });
}

// 휴대폰 번호 자동 포맷팅
const phoneInput = document.getElementById('phone');
if (phoneInput) {
    phoneInput.addEventListener('input', function(e) {
        let value = this.value.replace(/[^0-9]/g, '');
        
        if (value.length > 0 && !value.startsWith('010')) {
            if (value.length === 1 && value !== '0') {
                value = '0';
            } else if (value.length === 2 && !value.startsWith('01')) {
                value = '01';
            } else if (value.length >= 3) {
                value = '010' + value.substring(3);
            }
        }
        
        if (value.length > 11) {
            value = value.substring(0, 11);
        }
        
        if (value.length === 0) {
            this.value = '';
        } else if (value.length <= 3) {
            this.value = value;
        } else if (value.length > 3 && value.length <= 7) {
            this.value = value.substring(0, 3) + '-' + value.substring(3);
        } else if (value.length > 7) {
            this.value = value.substring(0, 3) + '-' + value.substring(3, 7) + '-' + value.substring(7);
        }
    });
}

// 비밀번호 확인 (admin-header.php와 변수명 충돌 방지)
const passwordInput = document.getElementById('password');
const passwordConfirmInput = document.getElementById('password_confirm');
const subAdminPasswordMatchResult = document.getElementById('passwordMatchResult');

if (passwordInput && passwordConfirmInput && subAdminPasswordMatchResult) {
    function checkPasswordMatch() {
        const password = passwordInput.value;
        const passwordConfirm = passwordConfirmInput.value;
        
        if (passwordConfirm.length === 0) {
            subAdminPasswordMatchResult.innerHTML = '';
            return;
        }
        
        if (password === passwordConfirm) {
            subAdminPasswordMatchResult.innerHTML = '<span style="color: #10b981;">비밀번호가 일치합니다.</span>';
            passwordConfirmInput.style.borderColor = '#10b981';
        } else {
            subAdminPasswordMatchResult.innerHTML = '<span style="color: #ef4444;">비밀번호가 일치하지 않습니다.</span>';
            passwordConfirmInput.style.borderColor = '#ef4444';
        }
    }
    
    passwordInput.addEventListener('input', checkPasswordMatch);
    passwordConfirmInput.addEventListener('input', checkPasswordMatch);
}

// 폼 제출 전 검증
document.getElementById('addSubAdminForm')?.addEventListener('submit', function(e) {
    if (!idChecked || !idValid) {
        e.preventDefault();
        alert('아이디 중복 확인을 해주세요.');
        return false;
    }
    
    const password = passwordInput.value;
    const passwordConfirm = passwordConfirmInput.value;
    
    if (password !== passwordConfirm) {
        e.preventDefault();
        alert('비밀번호가 일치하지 않습니다.');
        return false;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
