<?php
/**
 * 데이터베이스 설정 관리자 페이지
 * 관리자가 DB 연결 정보를 변경할 수 있는 페이지
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';

// admin 계정만 접근 가능 (부관리자 제외)
$currentUser = getCurrentUser();
if (!$currentUser || getUserRole($currentUser['user_id']) !== 'admin') {
    header('Location: /MVNO/admin/');
    exit;
}

// DB 연결은 나중에 필요할 때만 수행 (설정 저장 시)

$error = '';
$success = '';

// DB 설정 파일 경로
$dbConfigFile = __DIR__ . '/../../includes/data/db-config.php';
$dbConfigLocalFile = __DIR__ . '/../../includes/data/db-config-local.php';

// 현재 설정 읽기
$currentConfig = [
    'host' => 'localhost',
    'name' => 'mvno_db',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4'
];

// 로컬 설정 파일이 있으면 읽기
if (file_exists($dbConfigLocalFile)) {
    // 파일에서 설정 추출
    $content = file_get_contents($dbConfigLocalFile);
    if (preg_match("/define\('DB_HOST',\s*'([^']+)'\)/", $content, $matches)) {
        $currentConfig['host'] = $matches[1];
    }
    if (preg_match("/define\('DB_NAME',\s*'([^']+)'\)/", $content, $matches)) {
        $currentConfig['name'] = $matches[1];
    }
    if (preg_match("/define\('DB_USER',\s*'([^']+)'\)/", $content, $matches)) {
        $currentConfig['user'] = $matches[1];
    }
    if (preg_match("/define\('DB_PASS',\s*'([^']*)'\)/", $content, $matches)) {
        $currentConfig['pass'] = $matches[1];
    }
    if (preg_match("/define\('DB_CHARSET',\s*'([^']+)'\)/", $content, $matches)) {
        $currentConfig['charset'] = $matches[1];
    }
} else {
    // 기본 설정 파일에서 읽기
    if (file_exists($dbConfigFile)) {
        $content = file_get_contents($dbConfigFile);
        if (preg_match("/define\('DB_HOST',\s*'([^']+)'\)/", $content, $matches)) {
            $currentConfig['host'] = $matches[1];
        }
        if (preg_match("/define\('DB_NAME',\s*'([^']+)'\)/", $content, $matches)) {
            $currentConfig['name'] = $matches[1];
        }
        if (preg_match("/define\('DB_USER',\s*'([^']+)'\)/", $content, $matches)) {
            $currentConfig['user'] = $matches[1];
        }
        if (preg_match("/define\('DB_PASS',\s*'([^']*)'\)/", $content, $matches)) {
            $currentConfig['pass'] = $matches[1];
        }
        if (preg_match("/define\('DB_CHARSET',\s*'([^']+)'\)/", $content, $matches)) {
            $currentConfig['charset'] = $matches[1];
        }
    }
}

// POST 요청 처리 (설정 저장)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_db_settings'])) {
    $host = trim($_POST['db_host'] ?? '');
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = $_POST['db_pass'] ?? '';
    $charset = trim($_POST['db_charset'] ?? 'utf8mb4');
    
    // 유효성 검사
    if (empty($host)) {
        $error = 'DB 호스트를 입력해주세요.';
    } elseif (empty($name)) {
        $error = 'DB 이름을 입력해주세요.';
    } elseif (empty($user)) {
        $error = 'DB 사용자명을 입력해주세요.';
    } else {
            // 연결 테스트 (기존 DB 연결 없이 직접 테스트)
        try {
            $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
            $testPdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            
            // 간단한 쿼리로 연결 확인
            $testPdo->query("SELECT 1");
            
            // 연결 성공 시 설정 파일 저장
            $configContent = "<?php
/**
 * 데이터베이스 연결 설정 (로컬 설정)
 * 이 파일은 관리자 페이지에서 자동 생성됩니다.
 * 서버 환경에 맞게 수정된 설정이 저장됩니다.
 * 
 * 주의: 이 파일은 보안상 중요한 정보를 포함하므로 절대 공개되지 않도록 주의하세요.
 */

// 데이터베이스 설정
define('DB_HOST', '" . addslashes($host) . "');
define('DB_NAME', '" . addslashes($name) . "');
define('DB_USER', '" . addslashes($user) . "');
define('DB_PASS', '" . addslashes($pass) . "');
define('DB_CHARSET', '" . addslashes($charset) . "');
";
            
            // 파일 저장
            if (file_put_contents($dbConfigLocalFile, $configContent) !== false) {
                // 파일 권한 설정 (소유자만 읽기/쓰기)
                @chmod($dbConfigLocalFile, 0600);
                
                $success = 'DB 설정이 저장되었습니다. 새로고침 후 적용됩니다.';
                $currentConfig = [
                    'host' => $host,
                    'name' => $name,
                    'user' => $user,
                    'pass' => $pass,
                    'charset' => $charset
                ];
            } else {
                $error = '설정 파일 저장에 실패했습니다. 파일 권한을 확인해주세요.';
            }
        } catch (PDOException $e) {
            $error = 'DB 연결 테스트 실패: ' . $e->getMessage();
        }
    }
}

// 현재 페이지 설정
$currentPage = 'db-settings.php';

// 헤더 포함
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="page-header" style="margin-bottom: 32px;">
        <h1 style="font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">데이터베이스 설정</h1>
        <p style="font-size: 16px; color: #6b7280;">서버 환경에 맞게 데이터베이스 연결 정보를 설정할 수 있습니다.</p>
    </div>
    
    <?php if ($error): ?>
        <div style="padding: 16px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 24px; border: 1px solid #ef4444;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div style="padding: 16px; background: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 24px; border: 1px solid #10b981;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
        <div style="display: flex; align-items: start; gap: 12px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <div style="font-weight: 600; color: #92400e; margin-bottom: 4px;">보안 주의사항</div>
                <div style="font-size: 14px; color: #78350f;">
                    DB 비밀번호는 민감한 정보입니다. 설정 저장 후에는 브라우저를 닫거나 로그아웃하여 보안을 유지하세요.
                    <br>설정은 <code style="background: rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 4px;">includes/data/db-config-local.php</code> 파일에 저장됩니다.
                </div>
            </div>
        </div>
    </div>
    
    <form method="POST" style="background: white; border-radius: 12px; padding: 32px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;">
        <div style="margin-bottom: 32px;">
            <h2 style="font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 24px;">데이터베이스 연결 정보</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label for="db_host" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        DB 호스트 <span style="color: #ef4444;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="db_host" 
                        name="db_host" 
                        value="<?= htmlspecialchars($currentConfig['host']) ?>"
                        required
                        placeholder="localhost"
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px;"
                    >
                    <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">
                        데이터베이스 서버 주소
                    </div>
                </div>
                
                <div>
                    <label for="db_name" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        DB 이름 <span style="color: #ef4444;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="db_name" 
                        name="db_name" 
                        value="<?= htmlspecialchars($currentConfig['name']) ?>"
                        required
                        placeholder="mvno_db"
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px;"
                    >
                    <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">
                        데이터베이스 이름
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label for="db_user" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        DB 사용자명 <span style="color: #ef4444;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="db_user" 
                        name="db_user" 
                        value="<?= htmlspecialchars($currentConfig['user']) ?>"
                        required
                        placeholder="root"
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px;"
                    >
                    <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">
                        데이터베이스 사용자 계정
                    </div>
                </div>
                
                <div>
                    <label for="db_pass" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        DB 비밀번호
                    </label>
                    <input 
                        type="password" 
                        id="db_pass" 
                        name="db_pass" 
                        value="<?= htmlspecialchars($currentConfig['pass']) ?>"
                        placeholder="비밀번호 입력"
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px;"
                    >
                    <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">
                        데이터베이스 비밀번호 (비어있을 수 있음)
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="db_charset" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    문자셋
                </label>
                <select 
                    id="db_charset" 
                    name="db_charset" 
                    style="width: 200px; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px;"
                >
                    <option value="utf8mb4" <?= $currentConfig['charset'] === 'utf8mb4' ? 'selected' : '' ?>>utf8mb4 (권장)</option>
                    <option value="utf8" <?= $currentConfig['charset'] === 'utf8' ? 'selected' : '' ?>>utf8</option>
                    <option value="latin1" <?= $currentConfig['charset'] === 'latin1' ? 'selected' : '' ?>>latin1</option>
                </select>
                <div style="font-size: 13px; color: #6b7280; margin-top: 6px;">
                    데이터베이스 문자 인코딩
                </div>
            </div>
        </div>
        
        <!-- 저장 버튼 -->
        <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 24px; border-top: 1px solid #e5e7eb;">
            <button type="submit" name="save_db_settings" 
                    style="padding: 12px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s;"
                    onmouseover="this.style.background='#4f46e5';"
                    onmouseout="this.style.background='#6366f1';">
                설정 저장
            </button>
        </div>
    </form>
</div>

<?php
// 푸터 포함
include __DIR__ . '/../includes/admin-footer.php';
?>
