<?php
/**
 * mypage 폴더 복구 웹 인터페이스
 */

$result = '';
$success = false;
$error = '';
$output = '';

// 복구 실행
if (isset($_POST['restore'])) {
    $target_date = $_POST['date'] ?? '';
    $target_time = $_POST['time'] ?? '10:00:00';
    
    if (empty($target_date)) {
        $error = '날짜를 선택해주세요.';
    } else {
        // 날짜와 시간 결합
        $datetime = $target_date . ' ' . $target_time;
        
        // 현재 작업 디렉토리 변경
        $original_dir = getcwd();
        chdir(__DIR__);
        
        // Git 명령어 실행
        $commands = [];
        
        // 방법 1: 특정 날짜 이전의 커밋 찾기
        $find_commit_cmd = sprintf('git log --all --format="%%H|%%ai|%%s" --date=iso --until="%s" -- mypage/ -1', escapeshellarg($datetime));
        $commit_output = [];
        $commit_return = 0;
        exec($find_commit_cmd . ' 2>&1', $commit_output, $commit_return);
        
        if (!empty($commit_output)) {
            $commit_line = $commit_output[0];
            if (strpos($commit_line, '|') !== false) {
                $parts = explode('|', $commit_line, 3);
                $commit_hash = $parts[0] ?? '';
                
                if (!empty($commit_hash)) {
                    // mypage 폴더 복구
                    $restore_cmd = sprintf('git checkout %s -- mypage/', escapeshellarg($commit_hash));
                    $restore_output = [];
                    $restore_return = 0;
                    exec($restore_cmd . ' 2>&1', $restore_output, $restore_return);
                    
                    if ($restore_return === 0) {
                        $success = true;
                        $result = sprintf('mypage 폴더가 %s 이전 버전으로 복구되었습니다!', $datetime);
                        $output = implode("\n", $restore_output);
                        if (empty($output)) {
                            $output = "복구 완료: 커밋 " . substr($commit_hash, 0, 8);
                        }
                    } else {
                        $error = '복구 명령어 실행에 실패했습니다.';
                        $output = implode("\n", $restore_output);
                    }
                } else {
                    $error = '해당 날짜 이전의 커밋을 찾을 수 없습니다.';
                    $output = implode("\n", $commit_output);
                }
            } else {
                $error = '해당 날짜 이전의 커밋을 찾을 수 없습니다.';
                $output = implode("\n", $commit_output);
            }
        } else {
            $error = '해당 날짜 이전의 커밋을 찾을 수 없습니다.';
            $output = 'Git 저장소에 해당 날짜 이전의 mypage 관련 커밋이 없습니다.';
        }
        
        chdir($original_dir);
    }
}

// 오늘 날짜 (기본값)
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mypage 폴더 복구</title>
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
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .warning-box strong {
            color: #856404;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input[type="date"],
        .form-group input[type="time"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        
        .form-group input[type="date"]:focus,
        .form-group input[type="time"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 15px 30px;
            font-size: 1.1em;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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
        
        .quick-date-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        .quick-date-btn {
            padding: 8px 15px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s;
        }
        
        .quick-date-btn:hover {
            background: #e9ecef;
            border-color: #667eea;
        }
    </style>
    <script>
        function setQuickDate(daysAgo, time) {
            const date = new Date();
            date.setDate(date.getDate() - daysAgo);
            const dateStr = date.toISOString().split('T')[0];
            document.getElementById('date').value = dateStr;
            if (time) {
                document.getElementById('time').value = time;
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 mypage 폴더 복구</h1>
            <p>특정 날짜 이전 버전으로 복구합니다</p>
        </div>
        
        <div class="content">
            <div class="warning-box">
                <strong>⚠️ 주의:</strong> 이 작업은 mypage 폴더의 모든 파일을 선택한 날짜 이전 버전으로 되돌립니다. 
                현재 변경사항이 있다면 먼저 백업하세요.
            </div>
            
            <div class="info-box">
                <strong>💡 안내:</strong> 
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>복구할 날짜와 시간을 선택하세요</li>
                    <li>해당 날짜/시간 이전의 가장 최근 커밋으로 복구됩니다</li>
                    <li>복구 후 <code>git status</code>로 변경사항을 확인할 수 있습니다</li>
                    <li>되돌리려면 <code>git restore mypage/*</code>를 실행하세요</li>
                </ul>
            </div>
            
            <form method="POST" action="" onsubmit="return confirm('정말로 mypage 폴더를 복구하시겠습니까?');">
                <div class="form-group">
                    <label for="date">📅 날짜 선택:</label>
                    <input type="date" id="date" name="date" value="<?php echo $yesterday; ?>" required>
                    
                    <div class="quick-date-buttons">
                        <button type="button" class="quick-date-btn" onclick="setQuickDate(1, '10:00:00')">어제 10시</button>
                        <button type="button" class="quick-date-btn" onclick="setQuickDate(2, '10:00:00')">2일 전 10시</button>
                        <button type="button" class="quick-date-btn" onclick="setQuickDate(7, '00:00:00')">일주일 전</button>
                        <button type="button" class="quick-date-btn" onclick="setQuickDate(30, '00:00:00')">한 달 전</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="time">🕐 시간 선택:</label>
                    <input type="time" id="time" name="time" value="10:00:00" required>
                </div>
                
                <button type="submit" name="restore" class="btn btn-primary">
                    🔄 mypage 폴더 복구하기
                </button>
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
                <strong>📝 복구 후 확인 방법:</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>터미널에서 <code>git status</code> 실행하여 변경된 파일 확인</li>
                    <li>변경사항을 되돌리려면: <code>git restore mypage/*</code></li>
                    <li>변경사항을 커밋하려면: <code>git add mypage/</code> 후 <code>git commit</code></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>









