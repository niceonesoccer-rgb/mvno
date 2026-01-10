<?php
/**
 * 로그인 리다이렉트 경로 디버깅
 * 이 파일로 리다이렉트되는 경로를 확인할 수 있습니다.
 */

require_once __DIR__ . '/../includes/data/path-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 디버깅 정보 수집
$debugInfo = [
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'N/A',
    'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'N/A',
    'HTTPS' => $_SERVER['HTTPS'] ?? 'N/A',
    'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'N/A',
];

// 경로 계산
$currentDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/admin');
$adminPath = rtrim($currentDir, '/');
$redirectUrl = $adminPath . '/index.php';

// getAssetPath 결과 확인
$assetPathResult = getAssetPath('/admin/index.php');
$basePath = getBasePath();

// admin/index.php 파일 존재 확인
$indexFilePath = __DIR__ . '/index.php';
$indexFileExists = file_exists($indexFilePath);

// 절대 URL 생성
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$absoluteUrl = $protocol . '://' . $host . $redirectUrl;

// 현재 사용자 정보
$currentUser = getCurrentUser();
$isLoggedIn = $currentUser !== null;
$isAdminUser = false;
if ($currentUser) {
    $isAdminUser = isAdmin($currentUser['user_id']);
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 리다이렉트 디버깅</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #6366f1;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 6px;
            border-left: 4px solid #6366f1;
        }
        .section h2 {
            color: #6366f1;
            margin-top: 0;
            font-size: 18px;
        }
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
            width: 200px;
            flex-shrink: 0;
        }
        .info-value {
            color: #111827;
            word-break: break-all;
            flex: 1;
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
        .redirect-box {
            background: #eff6ff;
            border: 2px solid #3b82f6;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .redirect-box h3 {
            margin-top: 0;
            color: #1e40af;
        }
        .test-button {
            display: inline-block;
            padding: 12px 24px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
            font-weight: 600;
        }
        .test-button:hover {
            background: #4f46e5;
        }
        .test-button.secondary {
            background: #6b7280;
        }
        .test-button.secondary:hover {
            background: #4b5563;
        }
        pre {
            background: #1f2937;
            color: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 로그인 리다이렉트 경로 디버깅</h1>
        
        <!-- 서버 정보 -->
        <div class="section">
            <h2>서버 정보</h2>
            <div class="info-row">
                <div class="info-label">REQUEST_URI:</div>
                <div class="info-value"><?php echo htmlspecialchars($debugInfo['REQUEST_URI']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">SCRIPT_NAME:</div>
                <div class="info-value"><?php echo htmlspecialchars($debugInfo['SCRIPT_NAME']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">HTTP_HOST:</div>
                <div class="info-value"><?php echo htmlspecialchars($debugInfo['HTTP_HOST']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">HTTPS:</div>
                <div class="info-value"><?php echo htmlspecialchars($debugInfo['HTTPS']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">프로토콜:</div>
                <div class="info-value"><?php echo $protocol; ?></div>
            </div>
        </div>

        <!-- 경로 계산 -->
        <div class="section">
            <h2>경로 계산 결과</h2>
            <div class="info-row">
                <div class="info-label">현재 디렉토리 (dirname):</div>
                <div class="info-value"><?php echo htmlspecialchars($currentDir); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">admin 경로:</div>
                <div class="info-value"><?php echo htmlspecialchars($adminPath); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">리다이렉트 URL (상대):</div>
                <div class="info-value"><strong><?php echo htmlspecialchars($redirectUrl); ?></strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">리다이렉트 URL (절대):</div>
                <div class="info-value"><strong><?php echo htmlspecialchars($absoluteUrl); ?></strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">BASE_PATH:</div>
                <div class="info-value"><?php echo htmlspecialchars($basePath ?: '(빈 문자열)'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">getAssetPath('/admin/index.php'):</div>
                <div class="info-value"><?php echo htmlspecialchars($assetPathResult); ?></div>
            </div>
        </div>

        <!-- 파일 존재 확인 -->
        <div class="section">
            <h2>파일 존재 확인</h2>
            <div class="info-row">
                <div class="info-label">index.php 파일 경로:</div>
                <div class="info-value"><?php echo htmlspecialchars($indexFilePath); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">파일 존재:</div>
                <div class="info-value">
                    <?php if ($indexFileExists): ?>
                        <span class="status-ok">✓ 존재함</span>
                    <?php else: ?>
                        <span class="status-error">✗ 존재하지 않음</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 사용자 정보 -->
        <div class="section">
            <h2>현재 사용자 정보</h2>
            <div class="info-row">
                <div class="info-label">로그인 상태:</div>
                <div class="info-value">
                    <?php if ($isLoggedIn): ?>
                        <span class="status-ok">✓ 로그인됨</span>
                    <?php else: ?>
                        <span class="status-warning">⚠ 로그인 안됨</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($currentUser): ?>
            <div class="info-row">
                <div class="info-label">사용자 ID:</div>
                <div class="info-value"><?php echo htmlspecialchars($currentUser['user_id'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">사용자 이름:</div>
                <div class="info-value"><?php echo htmlspecialchars($currentUser['name'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">역할:</div>
                <div class="info-value"><?php echo htmlspecialchars($currentUser['role'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">관리자 여부:</div>
                <div class="info-value">
                    <?php if ($isAdminUser): ?>
                        <span class="status-ok">✓ 관리자</span>
                    <?php else: ?>
                        <span class="status-warning">⚠ 관리자 아님</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="info-row">
                <div class="info-value">사용자 정보 없음</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- 리다이렉트 테스트 -->
        <div class="redirect-box">
            <h3>리다이렉트 테스트</h3>
            <p>다음 버튼들을 클릭하여 리다이렉트가 제대로 작동하는지 확인하세요:</p>
            <a href="<?php echo htmlspecialchars($redirectUrl); ?>" class="test-button" target="_blank">
                상대 경로 테스트: <?php echo htmlspecialchars($redirectUrl); ?>
            </a>
            <a href="<?php echo htmlspecialchars($absoluteUrl); ?>" class="test-button" target="_blank">
                절대 경로 테스트: <?php echo htmlspecialchars($absoluteUrl); ?>
            </a>
            <a href="<?php echo htmlspecialchars($assetPathResult); ?>" class="test-button secondary" target="_blank">
                getAssetPath 결과 테스트: <?php echo htmlspecialchars($assetPathResult); ?>
            </a>
            <a href="/admin/index.php" class="test-button secondary" target="_blank">
                직접 경로 테스트: /admin/index.php
            </a>
        </div>

        <!-- 디버깅 코드 -->
        <div class="section">
            <h2>디버깅용 코드</h2>
            <p>로그인 후 리다이렉트할 때 사용할 코드:</p>
            <pre><?php
echo "// 방법 1: 상대 경로 (현재 사용 중)\n";
echo "\$currentDir = dirname(\$_SERVER['SCRIPT_NAME']);\n";
echo "\$adminPath = rtrim(\$currentDir, '/');\n";
echo "\$redirectUrl = \$adminPath . '/index.php';\n";
echo "header('Location: ' . \$redirectUrl);\n";
echo "// 결과: " . htmlspecialchars($redirectUrl) . "\n\n";

echo "// 방법 2: 절대 URL\n";
echo "\$protocol = (isset(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] === 'on') ? 'https' : 'http';\n";
echo "\$host = \$_SERVER['HTTP_HOST'] ?? 'localhost';\n";
echo "\$redirectUrl = \$protocol . '://' . \$host . '" . htmlspecialchars($redirectUrl) . "';\n";
echo "header('Location: ' . \$redirectUrl);\n";
echo "// 결과: " . htmlspecialchars($absoluteUrl) . "\n\n";

echo "// 방법 3: getAssetPath 사용\n";
echo "\$redirectUrl = getAssetPath('/admin/index.php');\n";
echo "header('Location: ' . \$redirectUrl);\n";
echo "// 결과: " . htmlspecialchars($assetPathResult) . "\n";
?></pre>
        </div>

        <!-- 로그인 페이지로 -->
        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
            <a href="login.php" class="test-button">로그인 페이지로 돌아가기</a>
        </div>
    </div>
</body>
</html>
