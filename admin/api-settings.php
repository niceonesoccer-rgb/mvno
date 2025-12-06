<?php
/**
 * API 설정 관리자 페이지
 * 관리자가 SNS 로그인 API 키를 설정할 수 있는 페이지
 */

// 관리자 인증 체크 (실제로는 세션 확인 등 필요)
// require_once __DIR__ . '/../includes/data/auth-functions.php';
// if (!isAdmin()) {
//     header('Location: /MVNO/auth/login.php');
//     exit;
// }

// API 설정 파일 경로
$settings_file = __DIR__ . '/../includes/data/api-settings.json';

// POST 요청 처리 (설정 저장)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $new_settings = [
        'naver' => [
            'client_id' => trim($_POST['naver_client_id'] ?? ''),
            'client_secret' => trim($_POST['naver_client_secret'] ?? ''),
            'redirect_uri' => trim($_POST['naver_redirect_uri'] ?? '')
        ],
        'kakao' => [
            'client_id' => trim($_POST['kakao_client_id'] ?? ''),
            'rest_api_key' => trim($_POST['kakao_rest_api_key'] ?? ''),
            'redirect_uri' => trim($_POST['kakao_redirect_uri'] ?? '')
        ],
        'google' => [
            'client_id' => trim($_POST['google_client_id'] ?? ''),
            'client_secret' => trim($_POST['google_client_secret'] ?? ''),
            'redirect_uri' => trim($_POST['google_redirect_uri'] ?? '')
        ]
    ];
    
    file_put_contents($settings_file, json_encode($new_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    $success_message = 'API 설정이 저장되었습니다.';
}

// 현재 설정 읽기
$settings = [];
if (file_exists($settings_file)) {
    $content = file_get_contents($settings_file);
    $settings = json_decode($content, true) ?: [];
}

$naver_settings = $settings['naver'] ?? ['client_id' => '', 'client_secret' => '', 'redirect_uri' => ''];
$kakao_settings = $settings['kakao'] ?? ['client_id' => '', 'rest_api_key' => '', 'redirect_uri' => ''];
$google_settings = $settings['google'] ?? ['client_id' => '', 'client_secret' => '', 'redirect_uri' => ''];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API 설정 관리</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f9fafb;
            padding: 20px;
        }
        
        .admin-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 24px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .settings-section {
            margin-bottom: 32px;
            padding-bottom: 32px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .settings-section:last-child {
            border-bottom: none;
        }
        
        .settings-section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        input[type="text"],
        input[type="url"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        input[type="text"]:focus,
        input[type="url"]:focus {
            outline: none;
            border-color: #6366f1;
        }
        
        .form-help {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #6366f1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4f46e5;
        }
        
        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        
        .info-box h3 {
            font-size: 14px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
        }
        
        .info-box p {
            font-size: 13px;
            color: #1e3a8a;
            margin-bottom: 4px;
        }
        
        .info-box ul {
            font-size: 13px;
            color: #1e3a8a;
            margin-left: 20px;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>API 설정 관리</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>설정 안내</h3>
            <p>각 SNS 개발자 센터에서 API 키를 발급받아 설정해주세요.</p>
            <ul>
                <li><strong>네이버:</strong> <a href="https://developers.naver.com/apps/" target="_blank">네이버 개발자 센터</a></li>
                <li><strong>카카오:</strong> <a href="https://developers.kakao.com/" target="_blank">카카오 개발자 센터</a></li>
                <li><strong>구글:</strong> <a href="https://console.cloud.google.com/" target="_blank">구글 클라우드 콘솔</a></li>
            </ul>
            <p style="margin-top: 12px;"><strong>리다이렉트 URI:</strong> <code>http://localhost/MVNO/api/sns-callback.php?provider={provider}</code></p>
        </div>
        
        <form method="POST">
            <!-- 네이버 설정 -->
            <div class="settings-section">
                <h2 class="settings-section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#03c75a">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28H12l-1.9-9.28h2.103l1.4 6.848 1.97-6.848h2.321z"/>
                    </svg>
                    네이버 API 설정
                </h2>
                
                <div class="form-group">
                    <label for="naver_client_id">Client ID</label>
                    <input 
                        type="text" 
                        id="naver_client_id" 
                        name="naver_client_id" 
                        value="<?php echo htmlspecialchars($naver_settings['client_id']); ?>"
                        placeholder="네이버 Client ID 입력"
                    >
                </div>
                
                <div class="form-group">
                    <label for="naver_client_secret">Client Secret</label>
                    <input 
                        type="text" 
                        id="naver_client_secret" 
                        name="naver_client_secret" 
                        value="<?php echo htmlspecialchars($naver_settings['client_secret']); ?>"
                        placeholder="네이버 Client Secret 입력"
                    >
                </div>
                
                <div class="form-group">
                    <label for="naver_redirect_uri">Redirect URI</label>
                    <input 
                        type="url" 
                        id="naver_redirect_uri" 
                        name="naver_redirect_uri" 
                        value="<?php echo htmlspecialchars($naver_settings['redirect_uri']); ?>"
                        placeholder="http://localhost/MVNO/api/sns-callback.php?provider=naver"
                    >
                    <div class="form-help">네이버 개발자 센터에 등록한 리다이렉트 URI와 동일해야 합니다.</div>
                </div>
            </div>
            
            <!-- 카카오 설정 -->
            <div class="settings-section">
                <h2 class="settings-section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#fee500">
                        <path d="M12 3c5.799 0 10.5 3.664 10.5 8.185 0 4.52-4.701 8.184-10.5 8.184a13.5 13.5 0 0 1-1.727-.11l-4.408 2.767c.501.762 1.45 1.01 2.248.405l3.773-2.33A12.984 12.984 0 0 1 12 19.37c-5.799 0-10.5-3.663-10.5-8.185C1.5 6.664 6.201 3 12 3z"/>
                    </svg>
                    카카오 API 설정
                </h2>
                
                <div class="form-group">
                    <label for="kakao_client_id">Client ID (REST API Key)</label>
                    <input 
                        type="text" 
                        id="kakao_client_id" 
                        name="kakao_client_id" 
                        value="<?php echo htmlspecialchars($kakao_settings['client_id']); ?>"
                        placeholder="카카오 Client ID 입력"
                    >
                </div>
                
                <div class="form-group">
                    <label for="kakao_rest_api_key">REST API Key</label>
                    <input 
                        type="text" 
                        id="kakao_rest_api_key" 
                        name="kakao_rest_api_key" 
                        value="<?php echo htmlspecialchars($kakao_settings['rest_api_key']); ?>"
                        placeholder="카카오 REST API Key 입력"
                    >
                    <div class="form-help">카카오 개발자 센터의 REST API 키를 입력하세요.</div>
                </div>
                
                <div class="form-group">
                    <label for="kakao_redirect_uri">Redirect URI</label>
                    <input 
                        type="url" 
                        id="kakao_redirect_uri" 
                        name="kakao_redirect_uri" 
                        value="<?php echo htmlspecialchars($kakao_settings['redirect_uri']); ?>"
                        placeholder="http://localhost/MVNO/api/sns-callback.php?provider=kakao"
                    >
                    <div class="form-help">카카오 개발자 센터에 등록한 리다이렉트 URI와 동일해야 합니다.</div>
                </div>
            </div>
            
            <!-- 구글 설정 -->
            <div class="settings-section">
                <h2 class="settings-section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    구글 API 설정
                </h2>
                
                <div class="form-group">
                    <label for="google_client_id">Client ID</label>
                    <input 
                        type="text" 
                        id="google_client_id" 
                        name="google_client_id" 
                        value="<?php echo htmlspecialchars($google_settings['client_id']); ?>"
                        placeholder="구글 Client ID 입력"
                    >
                </div>
                
                <div class="form-group">
                    <label for="google_client_secret">Client Secret</label>
                    <input 
                        type="text" 
                        id="google_client_secret" 
                        name="google_client_secret" 
                        value="<?php echo htmlspecialchars($google_settings['client_secret']); ?>"
                        placeholder="구글 Client Secret 입력"
                    >
                </div>
                
                <div class="form-group">
                    <label for="google_redirect_uri">Redirect URI</label>
                    <input 
                        type="url" 
                        id="google_redirect_uri" 
                        name="google_redirect_uri" 
                        value="<?php echo htmlspecialchars($google_settings['redirect_uri']); ?>"
                        placeholder="http://localhost/MVNO/api/sns-callback.php?provider=google"
                    >
                    <div class="form-help">구글 클라우드 콘솔에 등록한 리다이렉트 URI와 동일해야 합니다.</div>
                </div>
            </div>
            
            <!-- 저장 버튼 -->
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="submit" name="save_settings" class="btn btn-primary">
                    설정 저장
                </button>
            </div>
        </form>
    </div>
</body>
</html>





