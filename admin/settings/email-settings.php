<?php
/**
 * 이메일 설정 관리자 페이지
 * 경로: /MVNO/admin/settings/email-settings.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/app-settings.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdmin()) {
    header('Location: /MVNO/admin/');
    exit;
}

$error = '';
$success = '';

// 기본 설정값
$defaultSettings = [
    'mail_method' => 'auto',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_from_email' => 'noreply@mvno.com',
    'smtp_from_name' => 'MVNO 서비스',
    'mail_reply_to' => 'support@mvno.com',
    'mail_site_name' => 'MVNO',
    'mail_site_url' => 'https://mvno.com',
    'mail_support_email' => 'support@mvno.com'
];

// 현재 설정 읽기
$settings = getAppSettings('email', $defaultSettings);

// 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $newSettings = [
        'mail_method' => $_POST['mail_method'] ?? 'auto',
        'smtp_host' => trim($_POST['smtp_host'] ?? ''),
        'smtp_port' => intval($_POST['smtp_port'] ?? 587),
        'smtp_secure' => $_POST['smtp_secure'] ?? 'tls',
        'smtp_username' => trim($_POST['smtp_username'] ?? ''),
        'smtp_password' => trim($_POST['smtp_password'] ?? ''),
        'smtp_from_email' => trim($_POST['smtp_from_email'] ?? ''),
        'smtp_from_name' => trim($_POST['smtp_from_name'] ?? ''),
        'mail_reply_to' => trim($_POST['mail_reply_to'] ?? ''),
        'mail_site_name' => trim($_POST['mail_site_name'] ?? ''),
        'mail_site_url' => trim($_POST['mail_site_url'] ?? ''),
        'mail_support_email' => trim($_POST['mail_support_email'] ?? '')
    ];
    
    // 유효성 검사
    if (!in_array($newSettings['mail_method'], ['auto', 'mail', 'smtp'])) {
        $error = '이메일 발송 방식을 올바르게 선택해주세요.';
    } elseif ($newSettings['mail_method'] === 'smtp') {
        if (empty($newSettings['smtp_host'])) {
            $error = 'SMTP 서버 주소를 입력해주세요.';
        } elseif (empty($newSettings['smtp_port']) || $newSettings['smtp_port'] < 1 || $newSettings['smtp_port'] > 65535) {
            $error = 'SMTP 포트를 올바르게 입력해주세요. (1-65535)';
        } elseif (empty($newSettings['smtp_username'])) {
            $error = 'SMTP 사용자명을 입력해주세요.';
        } elseif (empty($newSettings['smtp_password'])) {
            // 비밀번호가 비어있으면 기존 값 유지 (변경하지 않음)
            if (isset($settings['smtp_password']) && !empty($settings['smtp_password'])) {
                $newSettings['smtp_password'] = $settings['smtp_password'];
            } else {
                // 첫 번째 저장 시에는 비밀번호가 필요
                $error = 'SMTP 비밀번호를 입력해주세요.';
            }
        }
    }
    
    if (empty($error)) {
        // 이메일 형식 검증
        if (!empty($newSettings['smtp_from_email']) && !filter_var($newSettings['smtp_from_email'], FILTER_VALIDATE_EMAIL)) {
            $error = '발신자 이메일 주소 형식이 올바르지 않습니다.';
        } elseif (!empty($newSettings['mail_reply_to']) && !filter_var($newSettings['mail_reply_to'], FILTER_VALIDATE_EMAIL)) {
            $error = '회신 주소 형식이 올바르지 않습니다.';
        } elseif (!empty($newSettings['mail_support_email']) && !filter_var($newSettings['mail_support_email'], FILTER_VALIDATE_EMAIL)) {
            $error = '고객 지원 이메일 주소 형식이 올바르지 않습니다.';
        } else {
            if (saveAppSettings('email', $newSettings, 'admin')) {
                $success = '이메일 설정이 저장되었습니다.';
                $settings = $newSettings;
            } else {
                $error = '설정 저장에 실패했습니다.';
            }
        }
    }
}

$currentPage = 'email-settings.php';
include '../includes/admin-header.php';
?>

<style>
    .admin-content { padding: 32px; }
    .page-header { margin-bottom: 32px; }
    .page-header h1 { font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
    .card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; margin-bottom: 24px; }
    .card-title { font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; }
    .form-group input[type="text"], 
    .form-group input[type="email"], 
    .form-group input[type="number"],
    .form-group input[type="password"],
    .form-group select,
    .form-group textarea {
        width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px;
        font-size: 15px; transition: border-color 0.2s; box-sizing: border-box; font-family: inherit;
    }
    .form-group textarea { min-height: 90px; resize: vertical; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { 
        outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); 
    }
    .help { font-size: 13px; color: #6b7280; margin-top: 6px; }
    .btn { padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; text-decoration: none; display: inline-block; }
    .btn-primary { background: #6366f1; color: white; }
    .btn-primary:hover { background: #4f46e5; }
    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .info-box { background: #eff6ff; border-left: 4px solid #3b82f6; padding: 16px; margin: 16px 0; border-radius: 4px; }
    .info-box p { margin: 4px 0; font-size: 14px; color: #1e40af; }
    .smtp-settings { display: none; }
    .smtp-settings.show { display: block; }
</style>

<div class="admin-content">
    <div class="page-header">
        <h1>이메일 설정</h1>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" id="emailSettingsForm">
        <input type="hidden" name="save_settings" value="1">

        <!-- 발송 방식 설정 -->
        <div class="card">
            <div class="card-title">이메일 발송 방식</div>
            <div class="form-group">
                <label for="mail_method">발송 방식 선택</label>
                <select id="mail_method" name="mail_method" required>
                    <option value="auto" <?php echo ($settings['mail_method'] ?? 'auto') === 'auto' ? 'selected' : ''; ?>>자동 선택 (로컬: SMTP 시도, 호스팅: mail 사용)</option>
                    <option value="mail" <?php echo ($settings['mail_method'] ?? '') === 'mail' ? 'selected' : ''; ?>>기본 mail() 함수 사용</option>
                    <option value="smtp" <?php echo ($settings['mail_method'] ?? '') === 'smtp' ? 'selected' : ''; ?>>SMTP 사용 (PHPMailer 필요)</option>
                </select>
                <div class="help">
                    • <strong>자동 선택</strong>: 환경에 따라 자동으로 선택됩니다. (로컬: SMTP 시도, 호스팅: mail 사용)<br>
                    • <strong>mail() 함수</strong>: 호스팅 환경에서 대부분 작동합니다.<br>
                    • <strong>SMTP</strong>: PHPMailer가 설치되어 있어야 하며, SMTP 서버 정보가 필요합니다.
                </div>
            </div>
        </div>

        <!-- SMTP 설정 -->
        <div class="card smtp-settings <?php echo ($settings['mail_method'] ?? 'auto') === 'smtp' ? 'show' : ''; ?>" id="smtpSettings">
            <div class="card-title">SMTP 설정</div>
            
            <div class="info-box">
                <p><strong>📌 SMTP 설정 안내</strong></p>
                <p>• Gmail 사용 시: Google 계정 설정 → 보안 → 2단계 인증 활성화 후 앱 비밀번호 생성 필요</p>
                <p>• 네이버 메일: SMTP 서버 주소는 'smtp.naver.com', 포트는 587, 보안은 TLS</p>
                <p>• 호스팅 업체: 호스팅 업체에서 제공하는 SMTP 정보를 입력하세요</p>
            </div>

            <div class="form-group">
                <label for="smtp_host">SMTP 서버 주소</label>
                <input type="text" id="smtp_host" name="smtp_host" 
                       value="<?php echo htmlspecialchars($settings['smtp_host'] ?? 'smtp.gmail.com'); ?>" 
                       placeholder="예: smtp.gmail.com">
                <div class="help">Gmail: smtp.gmail.com, 네이버: smtp.naver.com</div>
            </div>

            <div class="form-group">
                <label for="smtp_port">SMTP 포트</label>
                <input type="number" id="smtp_port" name="smtp_port" 
                       value="<?php echo htmlspecialchars($settings['smtp_port'] ?? 587); ?>" 
                       min="1" max="65535" required>
                <div class="help">일반적으로 587 (TLS) 또는 465 (SSL) 사용</div>
            </div>

            <div class="form-group">
                <label for="smtp_secure">보안 방식</label>
                <select id="smtp_secure" name="smtp_secure" required>
                    <option value="tls" <?php echo ($settings['smtp_secure'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (포트 587)</option>
                    <option value="ssl" <?php echo ($settings['smtp_secure'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL (포트 465)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="smtp_username">SMTP 사용자명 (이메일 주소)</label>
                <input type="email" id="smtp_username" name="smtp_username" 
                       value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>" 
                       placeholder="your-email@gmail.com">
            </div>

            <div class="form-group">
                <label for="smtp_password">SMTP 비밀번호 (앱 비밀번호)</label>
                <input type="password" id="smtp_password" name="smtp_password" 
                       value="" 
                       placeholder="비밀번호 변경 시에만 입력하세요">
                <div class="help">비밀번호를 변경하지 않으려면 비워두세요. (기존 비밀번호 유지)</div>
            </div>
        </div>

        <!-- 발신자 정보 설정 -->
        <div class="card">
            <div class="card-title">발신자 정보</div>
            
            <div class="form-group">
                <label for="smtp_from_email">발신 전용 이메일 주소</label>
                <input type="email" id="smtp_from_email" name="smtp_from_email" 
                       value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? 'noreply@mvno.com'); ?>" 
                       required>
                <div class="help">이메일 수신함에 표시되는 발신자 주소입니다. (수신 불가)</div>
            </div>

            <div class="form-group">
                <label for="smtp_from_name">발신자 이름</label>
                <input type="text" id="smtp_from_name" name="smtp_from_name" 
                       value="<?php echo htmlspecialchars($settings['smtp_from_name'] ?? 'MVNO 서비스'); ?>" 
                       required>
                <div class="help">이메일 수신함에 표시되는 발신자 이름입니다.</div>
            </div>

            <div class="form-group">
                <label for="mail_reply_to">회신 주소</label>
                <input type="email" id="mail_reply_to" name="mail_reply_to" 
                       value="<?php echo htmlspecialchars($settings['mail_reply_to'] ?? 'support@mvno.com'); ?>" 
                       required>
                <div class="help">고객이 이메일에 회신할 때 사용되는 주소입니다.</div>
            </div>
        </div>

        <!-- 사이트 정보 설정 -->
        <div class="card">
            <div class="card-title">사이트 정보</div>
            
            <div class="form-group">
                <label for="mail_site_name">사이트 이름</label>
                <input type="text" id="mail_site_name" name="mail_site_name" 
                       value="<?php echo htmlspecialchars($settings['mail_site_name'] ?? 'MVNO'); ?>" 
                       required>
                <div class="help">이메일 내용에 표시되는 사이트 이름입니다.</div>
            </div>

            <div class="form-group">
                <label for="mail_site_url">사이트 URL</label>
                <input type="text" id="mail_site_url" name="mail_site_url" 
                       value="<?php echo htmlspecialchars($settings['mail_site_url'] ?? 'https://mvno.com'); ?>" 
                       required>
                <div class="help">호스팅 업로드 시 실제 도메인으로 변경하세요. (예: https://yourdomain.com)</div>
            </div>

            <div class="form-group">
                <label for="mail_support_email">고객 지원 이메일</label>
                <input type="email" id="mail_support_email" name="mail_support_email" 
                       value="<?php echo htmlspecialchars($settings['mail_support_email'] ?? 'support@mvno.com'); ?>" 
                       required>
                <div class="help">이메일 내용에 표시되는 고객 지원 이메일 주소입니다.</div>
            </div>
        </div>

        <div style="margin-top: 32px;">
            <button type="submit" class="btn btn-primary">설정 저장</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mailMethodSelect = document.getElementById('mail_method');
    const smtpSettings = document.getElementById('smtpSettings');
    
    function toggleSmtpSettings() {
        if (mailMethodSelect.value === 'smtp') {
            smtpSettings.classList.add('show');
        } else {
            smtpSettings.classList.remove('show');
        }
    }
    
    mailMethodSelect.addEventListener('change', toggleSmtpSettings);
    toggleSmtpSettings(); // 초기 실행
});
</script>

<?php include '../includes/admin-footer.php'; ?>
