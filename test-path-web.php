<?php
/**
 * 웹 환경 경로 설정 테스트 페이지
 * http://ganadamobile.co.kr/test-path-web.php
 */

require_once __DIR__ . '/includes/data/path-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>웹 환경 경로 설정 테스트</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-box {
            background: white;
            padding: 20px;
            margin: 10px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-box h3 {
            margin-top: 0;
            color: #333;
        }
        .test-item {
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
            border-left: 3px solid #6366f1;
        }
        .test-item strong {
            display: inline-block;
            width: 200px;
        }
        .success {
            color: #10b981;
            font-weight: bold;
        }
        .error {
            color: #ef4444;
            font-weight: bold;
        }
        .warning {
            color: #f59e0b;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>웹 환경 경로 설정 테스트</h1>
    
    <div class="test-box">
        <h3>서버 정보</h3>
        <div class="test-item">
            <strong>HTTP_HOST:</strong> <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'N/A'); ?>
        </div>
        <div class="test-item">
            <strong>SCRIPT_NAME:</strong> <?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'N/A'); ?>
        </div>
        <div class="test-item">
            <strong>REQUEST_URI:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A'); ?>
        </div>
        <div class="test-item">
            <strong>DOCUMENT_ROOT:</strong> <?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A'); ?>
        </div>
    </div>
    
    <div class="test-box">
        <h3>경로 설정 결과</h3>
        <div class="test-item">
            <strong>BASE_PATH:</strong> 
            <span class="<?php 
                $expected = '';
                $actual = defined('BASE_PATH') ? BASE_PATH : 'NOT DEFINED';
                echo ($actual === $expected) ? 'success' : 'error';
            ?>">
                '<?php echo htmlspecialchars($actual); ?>'
            </span>
            <?php if ($actual !== $expected): ?>
                <span class="error"> (예상: '<?php echo $expected; ?>')</span>
            <?php endif; ?>
        </div>
        <div class="test-item">
            <strong>getBasePath():</strong> 
            <span class="<?php 
                $expected = '';
                $actual = getBasePath();
                echo ($actual === $expected) ? 'success' : 'error';
            ?>">
                '<?php echo htmlspecialchars($actual); ?>'
            </span>
        </div>
        <div class="test-item">
            <strong>getAssetPath('/assets/css/style.css'):</strong> 
            <span><?php echo htmlspecialchars(getAssetPath('/assets/css/style.css')); ?></span>
        </div>
        <div class="test-item">
            <strong>getAssetPath('/mno-sim/mno-sim.php'):</strong> 
            <span><?php echo htmlspecialchars(getAssetPath('/mno-sim/mno-sim.php')); ?></span>
        </div>
    </div>
    
    <div class="test-box">
        <h3>예상 결과 (웹 환경)</h3>
        <div class="test-item">
            <strong>BASE_PATH:</strong> <code>''</code> (빈 문자열) - 루트에 설치됨
        </div>
        <div class="test-item">
            <strong>getAssetPath('/assets/css/style.css'):</strong> <code>/assets/css/style.css</code>
        </div>
        <div class="test-item">
            <strong>getAssetPath('/mno-sim/mno-sim.php'):</strong> <code>/mno-sim/mno-sim.php</code>
        </div>
    </div>
    
    <div class="test-box">
        <h3>로컬 환경과의 차이</h3>
        <div class="test-item">
            <strong>로컬 (localhost/MVNO/):</strong>
            <ul>
                <li>BASE_PATH: <code>/MVNO</code></li>
                <li>getAssetPath('/assets/css/style.css'): <code>/MVNO/assets/css/style.css</code></li>
            </ul>
        </div>
        <div class="test-item">
            <strong>웹 (ganadamobile.co.kr/):</strong>
            <ul>
                <li>BASE_PATH: <code>''</code> (빈 문자열)</li>
                <li>getAssetPath('/assets/css/style.css'): <code>/assets/css/style.css</code></li>
            </ul>
        </div>
    </div>
</body>
</html>
