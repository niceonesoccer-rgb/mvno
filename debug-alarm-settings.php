<?php
/**
 * 알림 설정 API 디버깅 페이지
 * 웹 환경에서 API가 제대로 작동하는지 확인
 */

// 경로 설정 파일 먼저 로드
require_once __DIR__ . '/includes/data/path-config.php';
require_once __DIR__ . '/includes/data/auth-functions.php';

// 현재 사용자 정보
$currentUser = getCurrentUser();
$isLoggedIn = isLoggedIn();

// API 파일 존재 여부 확인
$apiFile = __DIR__ . '/api/update-alarm-settings.php';
$apiFileExists = file_exists($apiFile);
$apiFileReadable = $apiFileExists ? is_readable($apiFile) : false;

// API URL 생성
$apiUrl = getApiPath('/api/update-alarm-settings.php');
$basePath = getBasePath();

// 세션 정보
$sessionInfo = [
    'session_id' => session_id(),
    'logged_in' => $_SESSION['logged_in'] ?? false,
    'user_id' => $_SESSION['user_id'] ?? null,
];

// 서버 정보
$serverInfo = [
    'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? '',
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? '',
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? '',
];

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>알림 설정 API 디버깅</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            border-bottom: 3px solid #6366f1;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        .section {
            background: #f9fafb;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #6366f1;
        }
        .info-item {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            min-width: 200px;
        }
        .info-value {
            color: #333;
            flex: 1;
            word-break: break-all;
        }
        .status-ok {
            color: #10b981;
            font-weight: 600;
        }
        .status-error {
            color: #ef4444;
            font-weight: 600;
        }
        .status-warning {
            color: #f59e0b;
            font-weight: 600;
        }
        .test-section {
            background: #fff;
            border: 2px solid #e5e7eb;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .test-button {
            background: #6366f1;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
            transition: background 0.2s;
        }
        .test-button:hover {
            background: #4f46e5;
        }
        .test-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        .result-box {
            margin-top: 15px;
            padding: 15px;
            border-radius: 6px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 400px;
            overflow-y: auto;
        }
        .result-success {
            background: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }
        .result-error {
            background: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 알림 설정 API 디버깅</h1>

        <!-- 경로 설정 -->
        <div class="section">
            <h2>경로 설정</h2>
            <div class="info-item">
                <span class="info-label">BASE_PATH:</span>
                <span class="info-value"><code><?php echo htmlspecialchars($basePath); ?></code></span>
            </div>
            <div class="info-item">
                <span class="info-label">API URL:</span>
                <span class="info-value"><code><?php echo htmlspecialchars($apiUrl); ?></code></span>
            </div>
            <div class="info-item">
                <span class="info-label">API 파일 경로:</span>
                <span class="info-value"><code><?php echo htmlspecialchars($apiFile); ?></code></span>
            </div>
            <div class="info-item">
                <span class="info-label">파일 존재:</span>
                <span class="info-value">
                    <?php if ($apiFileExists): ?>
                        <span class="status-ok">✓ 존재함</span>
                    <?php else: ?>
                        <span class="status-error">✗ 존재하지 않음</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">파일 읽기 가능:</span>
                <span class="info-value">
                    <?php if ($apiFileReadable): ?>
                        <span class="status-ok">✓ 읽기 가능</span>
                    <?php else: ?>
                        <span class="status-error">✗ 읽기 불가</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <!-- 서버 정보 -->
        <div class="section">
            <h2>서버 정보</h2>
            <?php foreach ($serverInfo as $key => $value): ?>
                <div class="info-item">
                    <span class="info-label"><?php echo htmlspecialchars($key); ?>:</span>
                    <span class="info-value"><code><?php echo htmlspecialchars($value); ?></code></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 세션 정보 -->
        <div class="section">
            <h2>세션 정보</h2>
            <div class="info-item">
                <span class="info-label">로그인 상태:</span>
                <span class="info-value">
                    <?php if ($isLoggedIn): ?>
                        <span class="status-ok">✓ 로그인됨</span>
                    <?php else: ?>
                        <span class="status-warning">⚠ 로그인 필요</span>
                    <?php endif; ?>
                </span>
            </div>
            <?php foreach ($sessionInfo as $key => $value): ?>
                <div class="info-item">
                    <span class="info-label"><?php echo htmlspecialchars($key); ?>:</span>
                    <span class="info-value">
                        <?php 
                        if (is_bool($value)) {
                            echo $value ? 'true' : 'false';
                        } else {
                            echo htmlspecialchars($value ?? 'null');
                        }
                        ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 사용자 정보 -->
        <div class="section">
            <h2>사용자 정보</h2>
            <?php if ($currentUser): ?>
                <div class="info-item">
                    <span class="info-label">사용자 ID:</span>
                    <span class="info-value"><code><?php echo htmlspecialchars($currentUser['user_id'] ?? 'N/A'); ?></code></span>
                </div>
                <div class="info-item">
                    <span class="info-label">이름:</span>
                    <span class="info-value"><?php echo htmlspecialchars($currentUser['name'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">역할:</span>
                    <span class="info-value">
                        <?php 
                        $role = $currentUser['role'] ?? '';
                        $allowedRoles = ['member', 'user', ''];
                        if (in_array($role, $allowedRoles)) {
                            echo '<span class="status-ok">' . htmlspecialchars($role ?: '일반 사용자') . ' (허용됨)</span>';
                        } else {
                            echo '<span class="status-error">' . htmlspecialchars($role ?: 'N/A') . ' (거부됨)</span>';
                        }
                        ?>
                    </span>
                </div>
            <?php else: ?>
                <div class="info-item">
                    <span class="info-label">사용자 정보:</span>
                    <span class="info-value"><span class="status-warning">⚠ 사용자 정보 없음</span></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- API 테스트 -->
        <div class="test-section">
            <h2>API 테스트</h2>
            <p>아래 버튼들을 클릭하여 다양한 방식으로 API를 테스트할 수 있습니다.</p>
            
            <div id="testResult" class="result-box" style="display: none;"></div>
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
                <button class="test-button" id="testApiJsonBtn" <?php echo !$isLoggedIn ? 'disabled' : ''; ?>>
                    JSON 요청 테스트
                </button>
                <button class="test-button" id="testApiFormBtn" <?php echo !$isLoggedIn ? 'disabled' : ''; ?>>
                    FormData 요청 테스트
                </button>
                <button class="test-button" id="testApiDirectBtn">
                    직접 URL 접근 테스트
                </button>
            </div>
            
            <?php if (!$isLoggedIn): ?>
                <p style="color: #f59e0b; margin-top: 10px;">⚠ 로그인이 필요합니다. (직접 URL 접근 테스트는 제외)</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const apiUrl = '<?php echo $apiUrl; ?>';
        const testResult = document.getElementById('testResult');

        function showResult(text, isSuccess) {
            testResult.style.display = 'block';
            testResult.className = isSuccess ? 'result-box result-success' : 'result-box result-error';
            testResult.textContent = text;
        }

        // JSON 요청 테스트
        document.getElementById('testApiJsonBtn').addEventListener('click', async function() {
            const btn = this;
            btn.disabled = true;
            btn.textContent = '테스트 중...';
            showResult('API 호출 중...', false);

            const testData = {
                service_notice_opt_in: true,
                marketing_opt_in: false,
                marketing_email_opt_in: false,
                marketing_sms_sns_opt_in: false,
                marketing_push_opt_in: false
            };

            try {
                const startTime = performance.now();
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(testData)
                });
                const endTime = performance.now();
                const responseTime = (endTime - startTime).toFixed(2);

                const contentType = response.headers.get('content-type') || '';
                const isJson = contentType.includes('application/json');

                let responseText = await response.text();
                let responseData = null;

                if (isJson) {
                    try {
                        responseData = JSON.parse(responseText);
                    } catch (e) {
                        // JSON 파싱 실패
                    }
                }

                let resultText = `=== JSON 요청 테스트 결과 ===\n\n`;
                resultText += `URL: ${apiUrl}\n`;
                resultText += `Method: POST\n`;
                resultText += `Content-Type: application/json\n`;
                resultText += `상태 코드: ${response.status} ${response.statusText}\n`;
                resultText += `응답 시간: ${responseTime}ms\n`;
                resultText += `응답 Content-Type: ${contentType || 'N/A'}\n`;
                resultText += `\n=== 요청 데이터 ===\n`;
                resultText += JSON.stringify(testData, null, 2);
                resultText += `\n\n=== 응답 내용 ===\n`;

                if (responseData) {
                    resultText += JSON.stringify(responseData, null, 2);
                } else {
                    resultText += responseText.substring(0, 2000);
                    if (responseText.length > 2000) {
                        resultText += '\n\n... (응답이 너무 길어 일부만 표시)';
                    }
                }

                showResult(resultText, response.ok && responseData && responseData.success);

            } catch (error) {
                showResult(`=== JSON 요청 테스트 오류 ===\n\n` +
                    `URL: ${apiUrl}\n` +
                    `오류: ${error.message}\n` +
                    `스택: ${error.stack || 'N/A'}`, false);
            } finally {
                btn.disabled = false;
                btn.textContent = 'JSON 요청 테스트';
            }
        });

        // FormData 요청 테스트
        document.getElementById('testApiFormBtn').addEventListener('click', async function() {
            const btn = this;
            btn.disabled = true;
            btn.textContent = '테스트 중...';
            showResult('API 호출 중...', false);

            const formData = new FormData();
            formData.append('service_notice_opt_in', '1');
            formData.append('marketing_opt_in', '0');
            formData.append('marketing_email_opt_in', '0');
            formData.append('marketing_sms_sns_opt_in', '0');
            formData.append('marketing_push_opt_in', '0');

            try {
                const startTime = performance.now();
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                const endTime = performance.now();
                const responseTime = (endTime - startTime).toFixed(2);

                const contentType = response.headers.get('content-type') || '';
                const isJson = contentType.includes('application/json');

                let responseText = await response.text();
                let responseData = null;

                if (isJson) {
                    try {
                        responseData = JSON.parse(responseText);
                    } catch (e) {
                        // JSON 파싱 실패
                    }
                }

                let resultText = `=== FormData 요청 테스트 결과 ===\n\n`;
                resultText += `URL: ${apiUrl}\n`;
                resultText += `Method: POST\n`;
                resultText += `Content-Type: multipart/form-data (FormData)\n`;
                resultText += `상태 코드: ${response.status} ${response.statusText}\n`;
                resultText += `응답 시간: ${responseTime}ms\n`;
                resultText += `응답 Content-Type: ${contentType || 'N/A'}\n`;
                resultText += `\n=== 응답 내용 ===\n`;

                if (responseData) {
                    resultText += JSON.stringify(responseData, null, 2);
                } else {
                    resultText += responseText.substring(0, 2000);
                    if (responseText.length > 2000) {
                        resultText += '\n\n... (응답이 너무 길어 일부만 표시)';
                    }
                }

                showResult(resultText, response.ok && responseData && responseData.success);

            } catch (error) {
                showResult(`=== FormData 요청 테스트 오류 ===\n\n` +
                    `URL: ${apiUrl}\n` +
                    `오류: ${error.message}\n` +
                    `스택: ${error.stack || 'N/A'}`, false);
            } finally {
                btn.disabled = false;
                btn.textContent = 'FormData 요청 테스트';
            }
        });

        // 직접 URL 접근 테스트 (GET 요청)
        document.getElementById('testApiDirectBtn').addEventListener('click', async function() {
            const btn = this;
            btn.disabled = true;
            btn.textContent = '테스트 중...';
            showResult('API 호출 중...', false);

            try {
                const startTime = performance.now();
                const response = await fetch(apiUrl, {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                const endTime = performance.now();
                const responseTime = (endTime - startTime).toFixed(2);

                const contentType = response.headers.get('content-type') || '';
                let responseText = await response.text();

                let resultText = `=== 직접 URL 접근 테스트 (GET) ===\n\n`;
                resultText += `URL: ${apiUrl}\n`;
                resultText += `Method: GET\n`;
                resultText += `상태 코드: ${response.status} ${response.statusText}\n`;
                resultText += `응답 시간: ${responseTime}ms\n`;
                resultText += `응답 Content-Type: ${contentType || 'N/A'}\n`;
                resultText += `\n=== 응답 내용 ===\n`;
                resultText += responseText.substring(0, 2000);
                if (responseText.length > 2000) {
                    resultText += '\n\n... (응답이 너무 길어 일부만 표시)';
                }

                showResult(resultText, false);

            } catch (error) {
                showResult(`=== 직접 URL 접근 테스트 오류 ===\n\n` +
                    `URL: ${apiUrl}\n` +
                    `오류: ${error.message}\n` +
                    `스택: ${error.stack || 'N/A'}`, false);
            } finally {
                btn.disabled = false;
                btn.textContent = '직접 URL 접근 테스트';
            }
        });
    </script>
</body>
</html>
