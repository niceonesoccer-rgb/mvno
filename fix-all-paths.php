<?php
/**
 * 모든 파일에서 /MVNO/ 경로를 동적 경로로 변경하는 스크립트
 * 
 * 사용법:
 * 1. 이 파일을 프로젝트 루트에 두고
 * 2. 브라우저에서 실행: http://localhost/MVNO/fix-all-paths.php
 * 3. 또는 명령줄에서 실행: php fix-all-paths.php
 * 
 * 주의: 백업을 먼저 받으세요!
 */

// 관리자 권한 체크
require_once __DIR__ . '/includes/data/auth-functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = function_exists('isAdmin') ? isAdmin() : false;
$isLocalhost = (
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false
);

// 로컬호스트가 아니거나 관리자가 아니면 실행 안 함
if (!$isLocalhost || !$isAdmin) {
    die('이 스크립트는 로컬호스트에서 관리자만 실행할 수 있습니다.');
}

// 변경할 파일 목록 (주요 파일들)
$filesToFix = [
    // Components
    'includes/components/point-usage-modal.php',
    'includes/components/login-modal.php',
    'includes/components/plan-card.php',
    'includes/components/phone-card.php',
    'includes/components/mvno-home-card.php',
    'includes/components/mno-sim-home-card.php',
    'includes/components/mno-home-card.php',
    'includes/components/login-button.php',
    'includes/components/internet-order-card.php',
    'includes/components/internet-home-card.php',
    'includes/components/mno-order-card-header.php',
    'includes/components/phone-card-footer.php',
    'includes/components/plan-card-footer.php',
    'includes/components/internet-order-card-header.php',
    'includes/components/plan-card-header.php',
    'includes/components/phone-card-header.php',
    'includes/components/seller-product-form.php',
    
    // Data functions
    'includes/data/notice-functions.php',
    'includes/data/seller-inquiry-functions.php',
    'includes/data/product-functions.php',
    'includes/data/plan-data.php',
    'includes/data/mail-helper.php',
    'includes/data/mail-config.php',
    
    // 기타
    'index.php',
];

$results = [];
$totalChanged = 0;

foreach ($filesToFix as $file) {
    $filePath = __DIR__ . '/' . $file;
    
    if (!file_exists($filePath)) {
        $results[] = [
            'file' => $file,
            'status' => 'not_found',
            'changes' => 0
        ];
        continue;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $changes = 0;
    
    // path-config.php 로드 여부 확인
    $hasPathConfig = strpos($content, 'path-config.php') !== false;
    
    // /MVNO/ 경로를 동적 경로로 변경
    // 패턴 1: href="/MVNO/...
    $content = preg_replace_callback(
        '/href=["\']\/MVNO\/([^"\']+)["\']/',
        function($matches) use (&$changes) {
            $changes++;
            return 'href="<?php echo getAssetPath(\'/' . $matches[1] . '\'); ?>"';
        },
        $content
    );
    
    // 패턴 2: src="/MVNO/...
    $content = preg_replace_callback(
        '/src=["\']\/MVNO\/([^"\']+)["\']/',
        function($matches) use (&$changes) {
            $changes++;
            return 'src="<?php echo getAssetPath(\'/' . $matches[1] . '\'); ?>"';
        },
        $content
    );
    
    // 패턴 3: fetch('/MVNO/...
    $content = preg_replace_callback(
        "/fetch\(['\"]\/MVNO\/([^'\"]+)['\"]\)/",
        function($matches) use (&$changes) {
            $changes++;
            return 'fetch(\'<?php echo getApiPath("/' . $matches[1] . '"); ?>\')';
        },
        $content
    );
    
    // 패턴 4: window.location.href = '/MVNO/...
    $content = preg_replace_callback(
        "/window\.location\.href\s*=\s*['\"]\/MVNO\/([^'\"]+)['\"]/",
        function($matches) use (&$changes) {
            $changes++;
            return 'window.location.href = \'<?php echo getAssetPath("/' . $matches[1] . '"); ?>\'';
        },
        $content
    );
    
    // 패턴 5: return '/MVNO/...
    $content = preg_replace_callback(
        "/return\s+['\"]\/MVNO\/([^'\"]+)['\"]/",
        function($matches) use (&$changes) {
            $changes++;
            return 'return getAssetPath(\'/' . $matches[1] . '\')';
        },
        $content
    );
    
    // path-config.php가 없으면 추가
    if (!$hasPathConfig && $changes > 0) {
        // PHP 시작 태그 다음에 추가
        if (preg_match('/^<\?php\s*\n/', $content)) {
            $content = preg_replace(
                '/^<\?php\s*\n/',
                "<?php\nrequire_once __DIR__ . '/data/path-config.php';\n",
                $content,
                1
            );
        }
    }
    
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        $totalChanged += $changes;
        $results[] = [
            'file' => $file,
            'status' => 'updated',
            'changes' => $changes
        ];
    } else {
        $results[] = [
            'file' => $file,
            'status' => 'no_changes',
            'changes' => 0
        ];
    }
}

// 결과 출력
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>경로 수정 결과</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>경로 수정 완료</h1>
    <p class="success">총 <?php echo $totalChanged; ?>개의 경로가 수정되었습니다.</p>
    
    <table>
        <tr>
            <th>파일</th>
            <th>상태</th>
            <th>변경 수</th>
        </tr>
        <?php foreach ($results as $result): ?>
        <tr>
            <td><?php echo htmlspecialchars($result['file']); ?></td>
            <td class="<?php echo $result['status'] === 'updated' ? 'success' : ($result['status'] === 'not_found' ? 'error' : 'info'); ?>">
                <?php 
                echo $result['status'] === 'updated' ? '수정됨' : 
                     ($result['status'] === 'not_found' ? '파일 없음' : '변경 없음'); 
                ?>
            </td>
            <td><?php echo $result['changes']; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <p style="margin-top: 20px;">
        <strong>다음 단계:</strong><br>
        1. 로컬에서 테스트하여 정상 동작 확인<br>
        2. 프로덕션 서버에 업로드<br>
        3. 이 스크립트는 삭제하거나 보안을 위해 접근 차단
    </p>
</body>
</html>
