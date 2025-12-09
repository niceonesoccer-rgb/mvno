<?php
/**
 * 포인트 설정 관리자 페이지
 * 관리자가 포인트 관련 설정을 변경할 수 있는 페이지
 */

// 관리자 인증 체크 (실제로는 세션 확인 등 필요)
// $is_admin = checkAdminAuth();
// if (!$is_admin) {
//     header('Location: /admin/login.php');
//     exit;
// }

// 포인트 설정 파일 경로
$settings_file = __DIR__ . '/../includes/data/point-settings.php';

// POST 요청 처리 (설정 저장)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $new_settings = [
        'max_usable_point' => intval($_POST['max_usable_point'] ?? 50000),
        'mvno_application_point' => intval($_POST['mvno_application_point'] ?? 1000),
        'mno_application_point' => intval($_POST['mno_application_point'] ?? 1000),
        'internet_application_point' => intval($_POST['internet_application_point'] ?? 1000),
        'usage_message' => $_POST['usage_message'] ?? '신청 시 포인트가 차감됩니다.',
    ];
    
    // 설정 파일 읽기
    $file_content = file_get_contents($settings_file);
    
    // 설정 값 업데이트
    foreach ($new_settings as $key => $value) {
        if (is_string($value)) {
            $value = "'" . addslashes($value) . "'";
        }
        $file_content = preg_replace(
            "/'{$key}'\s*=>\s*[^,]+/",
            "'{$key}' => {$value}",
            $file_content
        );
    }
    
    file_put_contents($settings_file, $file_content);
    
    $success_message = '설정이 저장되었습니다.';
}

// 현재 설정 읽기
require_once $settings_file;
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>포인트 설정 관리</title>
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
            max-width: 800px;
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
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        input[type="number"],
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        input[type="number"]:focus,
        textarea:focus {
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
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>포인트 설정 관리</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <!-- 기본 설정 -->
            <div class="settings-section">
                <h2 class="settings-section-title">기본 설정</h2>
                
                <div class="form-group">
                    <label for="max_usable_point">최대 사용 가능 포인트 (원)</label>
                    <input 
                        type="number" 
                        id="max_usable_point" 
                        name="max_usable_point" 
                        value="<?php echo htmlspecialchars($point_settings['max_usable_point']); ?>"
                        min="0"
                        required
                    >
                    <div class="form-help">신청 시 한 번에 사용할 수 있는 최대 포인트 금액입니다.</div>
                </div>
                
                <div class="form-group">
                    <label for="usage_message">포인트 사용 안내 메시지</label>
                    <textarea 
                        id="usage_message" 
                        name="usage_message" 
                        rows="2"
                        required
                    ><?php echo htmlspecialchars($point_settings['usage_message']); ?></textarea>
                    <div class="form-help">포인트 사용 모달에 표시될 안내 메시지입니다.</div>
                </div>
            </div>
            
            <!-- 신청별 기본 차감 포인트 -->
            <div class="settings-section">
                <h2 class="settings-section-title">신청별 기본 차감 포인트</h2>
                
                <div class="form-group">
                    <label for="mvno_application_point">알뜰폰 신청 기본 차감 포인트 (원)</label>
                    <input 
                        type="number" 
                        id="mvno_application_point" 
                        name="mvno_application_point" 
                        value="<?php echo htmlspecialchars($point_settings['mvno_application_point']); ?>"
                        min="0"
                        required
                    >
                    <div class="form-help">알뜰폰 신청 시 기본으로 차감되는 포인트입니다. (사용자가 변경 가능)</div>
                </div>
                
                <div class="form-group">
                    <label for="mno_application_point">통신사폰 신청 기본 차감 포인트 (원)</label>
                    <input 
                        type="number" 
                        id="mno_application_point" 
                        name="mno_application_point" 
                        value="<?php echo htmlspecialchars($point_settings['mno_application_point']); ?>"
                        min="0"
                        required
                    >
                    <div class="form-help">통신사폰 신청 시 기본으로 차감되는 포인트입니다. (사용자가 변경 가능)</div>
                </div>
                
                <div class="form-group">
                    <label for="internet_application_point">인터넷 신청 기본 차감 포인트 (원)</label>
                    <input 
                        type="number" 
                        id="internet_application_point" 
                        name="internet_application_point" 
                        value="<?php echo htmlspecialchars($point_settings['internet_application_point']); ?>"
                        min="0"
                        required
                    >
                    <div class="form-help">인터넷 신청 시 기본으로 차감되는 포인트입니다. (사용자가 변경 가능)</div>
                </div>
            </div>
            
            <!-- 저장 버튼 -->
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="submit" name="save_settings" class="btn btn-primary">
                    설정 저장
                </button>
            </div>
        </form>
        
        <!-- 통계 섹션 (나중에 구현) -->
        <div class="settings-section" style="margin-top: 32px;">
            <h2 class="settings-section-title">포인트 사용 통계</h2>
            <p style="color: #6b7280; font-size: 14px;">
                판매자별 소진 금액과 신청 건수 통계는 관리자 페이지에서 확인할 수 있습니다.
            </p>
        </div>
    </div>
</body>
</html>


















