<?php
/**
 * 필터 설정 관리자 페이지
 * 관리자가 알뜰폰(MVNO)과 통신사폰(MNO)의 필터를 별도로 관리할 수 있는 페이지
 */

// 관리자 인증 체크 (실제로는 세션 확인 등 필요)
// $is_admin = checkAdminAuth();
// if (!$is_admin) {
//     header('Location: /admin/login.php');
//     exit;
// }

require_once __DIR__ . '/../includes/data/filter-data.php';

$success_message = '';
$error_message = '';

// POST 요청 처리 (필터 저장)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_filters'])) {
    // 통신사폰 필터 저장
    if (isset($_POST['mno_filters'])) {
        $mno_filters = array_filter(array_map('trim', explode("\n", $_POST['mno_filters'])));
        $mno_filters = array_values($mno_filters); // 인덱스 재정렬
        if (saveMnoFilters($mno_filters)) {
            $success_message = '통신사폰 필터가 저장되었습니다.';
        } else {
            $error_message = '통신사폰 필터 저장에 실패했습니다.';
        }
    }
    
    // 알뜰폰 필터 저장
    if (isset($_POST['mvno_filters'])) {
        $mvno_filters = array_filter(array_map('trim', explode("\n", $_POST['mvno_filters'])));
        $mvno_filters = array_values($mvno_filters); // 인덱스 재정렬
        if (saveMvnoFilters($mvno_filters)) {
            $success_message = ($success_message ? $success_message . ' ' : '') . '알뜰폰 필터가 저장되었습니다.';
        } else {
            $error_message = ($error_message ? $error_message . ' ' : '') . '알뜰폰 필터 저장에 실패했습니다.';
        }
    }
    
    if ($success_message && !$error_message) {
        $error_message = ''; // 성공 메시지만 표시
    }
}

// 현재 필터 읽기
$current_mno_filters = getMnoFilters();
$current_mvno_filters = getMvnoFilters();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>필터 설정 관리 - 관리자</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        h1 {
            font-size: 24px;
            margin-bottom: 30px;
            color: #1a1a1a;
            border-bottom: 2px solid #6366F1;
            padding-bottom: 10px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .section-description {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-left: 3px solid #6366F1;
            border-radius: 4px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }
        
        textarea {
            width: 100%;
            min-height: 150px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
        }
        
        textarea:focus {
            outline: none;
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: #6366F1;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #4F46E5;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .filter-preview {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .filter-preview-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .filter-preview-item {
            display: inline-block;
            padding: 4px 8px;
            margin: 2px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>필터 설정 관리</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <!-- 통신사폰 필터 설정 -->
            <div class="section">
                <h2 class="section-title">통신사폰(MNO) 필터 설정</h2>
                <div class="section-description">
                    통신사폰 페이지(mno.php)에서 사용할 필터를 설정합니다.<br>
                    각 필터는 한 줄에 하나씩 입력하세요. 해시태그(#)는 자동으로 추가됩니다.
                </div>
                
                <div class="form-group">
                    <label for="mno_filters">통신사폰 필터 목록</label>
                    <textarea 
                        id="mno_filters" 
                        name="mno_filters" 
                        placeholder="#갤럭시&#10;#아이폰&#10;#공짜&#10;#256GB&#10;#512GB"
                    ><?php echo htmlspecialchars(implode("\n", $current_mno_filters)); ?></textarea>
                    <div class="help-text">
                        예시: 갤럭시, 아이폰, 공짜, 256GB, 512GB 등<br>
                        각 필터는 한 줄에 하나씩 입력하세요.
                    </div>
                    <div class="filter-preview">
                        <div class="filter-preview-title">현재 설정된 필터:</div>
                        <?php foreach ($current_mno_filters as $filter): ?>
                            <span class="filter-preview-item"><?php echo htmlspecialchars($filter); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- 알뜰폰 필터 설정 -->
            <div class="section">
                <h2 class="section-title">알뜰폰(MVNO) 필터 설정</h2>
                <div class="section-description">
                    알뜰폰 페이지(mvno.php)에서 사용할 필터를 설정합니다.<br>
                    각 필터는 한 줄에 하나씩 입력하세요. 해시태그(#)는 자동으로 추가됩니다.
                </div>
                
                <div class="form-group">
                    <label for="mvno_filters">알뜰폰 필터 목록</label>
                    <textarea 
                        id="mvno_filters" 
                        name="mvno_filters" 
                        placeholder="#베스트 요금제&#10;#만원 미만&#10;#장기 할인&#10;#100원"
                    ><?php echo htmlspecialchars(implode("\n", $current_mvno_filters)); ?></textarea>
                    <div class="help-text">
                        예시: 베스트 요금제, 만원 미만, 장기 할인, 100원 등<br>
                        각 필터는 한 줄에 하나씩 입력하세요.
                    </div>
                    <div class="filter-preview">
                        <div class="filter-preview-title">현재 설정된 필터:</div>
                        <?php foreach ($current_mvno_filters as $filter): ?>
                            <span class="filter-preview-item"><?php echo htmlspecialchars($filter); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" name="save_filters" class="btn btn-primary">설정 저장</button>
                <button type="button" class="btn btn-secondary" onclick="location.reload()">취소</button>
            </div>
        </form>
    </div>
    
    <script>
        // 텍스트 영역에서 해시태그 자동 추가 (선택사항)
        document.getElementById('mno_filters').addEventListener('blur', function() {
            let lines = this.value.split('\n');
            lines = lines.map(line => {
                line = line.trim();
                if (line && !line.startsWith('#')) {
                    return '#' + line;
                }
                return line;
            });
            this.value = lines.join('\n');
        });
        
        document.getElementById('mvno_filters').addEventListener('blur', function() {
            let lines = this.value.split('\n');
            lines = lines.map(line => {
                line = line.trim();
                if (line && !line.startsWith('#')) {
                    return '#' + line;
                }
                return line;
            });
            this.value = lines.join('\n');
        });
    </script>
</body>
</html>





