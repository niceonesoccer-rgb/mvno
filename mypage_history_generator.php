<?php
/**
 * mypage 업데이트 이력 HTML 생성 웹 인터페이스
 */

// 실행 결과 변수
$result = '';
$success = false;
$output = '';
$error = '';

// 실행 버튼이 클릭되었을 때
if (isset($_POST['generate'])) {
    $script_path = __DIR__ . '/generate_mypage_history_html.py';
    $html_path = __DIR__ . '/mypage_history.html';
    
    // 현재 작업 디렉토리를 스크립트가 있는 디렉토리로 변경
    $original_dir = getcwd();
    chdir(__DIR__);
    
    // Python 스크립트 실행
    if (file_exists($script_path)) {
        // Python 경로 확인 (Windows)
        $commands = [
            'python generate_mypage_history_html.py',
            'python3 generate_mypage_history_html.py',
            'py generate_mypage_history_html.py',
            'C:\\Python\\python.exe generate_mypage_history_html.py',
            'C:\\Python39\\python.exe generate_mypage_history_html.py',
            'C:\\Python310\\python.exe generate_mypage_history_html.py'
        ];
        
        $executed = false;
        $output_text = '';
        
        foreach ($commands as $cmd) {
            $output = [];
            $return_var = 0;
            
            // 명령어 실행 (작업 디렉토리 지정)
            exec($cmd . ' 2>&1', $output, $return_var);
            $output_text = implode("\n", $output);
            
            // HTML 파일이 생성되었는지 확인
            if (file_exists($html_path) && filesize($html_path) > 1000) {
                $executed = true;
                break;
            }
        }
        
        // 원래 디렉토리로 복귀
        chdir($original_dir);
        
        if ($executed && file_exists($html_path)) {
            $success = true;
            $result = 'HTML 파일이 성공적으로 생성되었습니다!';
            $output = $output_text ?: '스크립트가 실행되었습니다.';
        } else {
            $error = '스크립트 실행에 실패했습니다.';
            $output = $output_text ?: 'Python을 찾을 수 없거나 스크립트 실행에 문제가 있습니다.';
            
            // 디버깅 정보 추가
            if (empty($output_text)) {
                $output = 'Python 명령어를 찾을 수 없습니다. 터미널에서 직접 실행해보세요: python generate_mypage_history_html.py';
            }
        }
    } else {
        chdir($original_dir);
        $error = 'Python 스크립트 파일을 찾을 수 없습니다: ' . $script_path;
    }
}

// HTML 파일 존재 여부 확인
$html_exists = file_exists(__DIR__ . '/mypage_history.html');
$html_url = 'mypage_history.html';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mypage 업데이트 이력 생성기</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 30px;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .info-box strong {
            color: #1976D2;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            flex: 1;
            min-width: 200px;
            padding: 15px 30px;
            font-size: 1.1em;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(17, 153, 142, 0.4);
        }
        
        .result-box {
            margin-top: 20px;
            padding: 20px;
            border-radius: 8px;
        }
        
        .result-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .result-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .output-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #333;
        }
        
        .instructions {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .instructions h3 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .instructions ol {
            margin-left: 20px;
            color: #856404;
        }
        
        .instructions li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 mypage 업데이트 이력 생성기</h1>
            <p>Git 이력을 기반으로 HTML 리포트를 생성합니다</p>
        </div>
        
        <div class="content">
            <div class="info-box">
                <strong>💡 안내:</strong> 이 페이지에서 버튼을 클릭하면 mypage 디렉토리의 Git 이력을 분석하여 
                웹페이지로 보기 좋은 HTML 리포트를 생성합니다.
            </div>
            
            <div class="info-box" style="background: #fff3cd; border-left-color: #ffc107;">
                <strong>🔗 빠른 링크:</strong><br>
                <a href="mypage_history_generator.php" style="color: #856404; text-decoration: underline; margin-right: 15px;">📝 생성기 페이지</a>
                <a href="mypage_history.html" target="_blank" style="color: #856404; text-decoration: underline;">📊 결과 보기</a>
            </div>
            
            <?php if ($html_exists): ?>
            <div class="result-box result-success">
                <strong>✓ HTML 파일이 존재합니다!</strong>
                <span class="status-badge badge-success">준비됨</span>
            </div>
            <?php else: ?>
            <div class="result-box result-error">
                <strong>⚠ HTML 파일이 없습니다.</strong>
                <span class="status-badge badge-warning">생성 필요</span>
            </div>
            <?php endif; ?>
            
            <div class="instructions">
                <h3>📋 사용 방법</h3>
                <ol>
                    <li>아래 "HTML 생성하기" 버튼을 클릭하세요</li>
                    <li>생성이 완료되면 "결과 보기" 버튼이 활성화됩니다</li>
                    <li>"결과 보기" 버튼을 클릭하여 생성된 리포트를 확인하세요</li>
                </ol>
            </div>
            
            <form method="POST" action="">
                <div class="button-group">
                    <button type="submit" name="generate" class="btn btn-primary">
                        🔄 HTML 생성하기
                    </button>
                    
                    <?php if ($html_exists): ?>
                    <a href="<?php echo htmlspecialchars($html_url); ?>" target="_blank" class="btn btn-success">
                        📊 결과 보기
                    </a>
                    <?php else: ?>
                    <span class="btn btn-success" style="opacity: 0.5; cursor: not-allowed;">
                        📊 결과 보기 (생성 필요)
                    </span>
                    <?php endif; ?>
                </div>
            </form>
            
            <?php if ($result || $error): ?>
            <div class="result-box <?php echo $success ? 'result-success' : 'result-error'; ?>">
                <?php if ($success): ?>
                    <strong>✓ 성공!</strong>
                    <p><?php echo htmlspecialchars($result); ?></p>
                <?php else: ?>
                    <strong>✗ 오류</strong>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                
                <?php if ($output): ?>
                <div class="output-box">
                    <strong>실행 결과:</strong><br>
                    <?php echo nl2br(htmlspecialchars($output)); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="info-box" style="margin-top: 30px;">
                <strong>📝 참고사항:</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>Python이 설치되어 있어야 합니다</li>
                    <li>Git 저장소에 mypage 관련 커밋이 있어야 데이터가 표시됩니다</li>
                    <li>생성된 HTML 파일은 <code>mypage_history.html</code>에 저장됩니다</li>
                    <li>새로운 커밋이 있으면 다시 생성하여 최신 정보를 반영할 수 있습니다</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>






