<?php
/**
 * 리뷰 작성 권한 설정 관리자 페이지
 * 관리자가 진행상황에 따라 리뷰 작성 권한을 설정할 수 있는 페이지
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/path-config.php';

// 관리자 인증 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    header('Location: ' . getAssetPath('/admin/login.php'));
    exit;
}

// 리뷰 설정 파일 경로
$settings_file = __DIR__ . '/../includes/data/review-settings.php';

// POST 요청 처리 (설정 저장)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $allowedStatuses = $_POST['allowed_statuses'] ?? [];
    
    // 배열을 PHP 코드로 변환 (이스케이프 처리)
    $escapedStatuses = array_map('addslashes', $allowedStatuses);
    $statusesString = "['" . implode("', '", $escapedStatuses) . "']";
    
    // 상태 라벨 맵
    $statusLabels = [
        'received' => '접수',
        'activating' => '개통중',
        'on_hold' => '보류',
        'cancelled' => '취소',
        'activation_completed' => '개통완료',
        'installation_completed' => '설치완료',
        'closed' => '종료'
    ];
    
    // 주석 문자열 생성
    $labelStrings = array_map(function($s) use ($statusLabels) {
        return $statusLabels[$s] ?? $s;
    }, $allowedStatuses);
    $commentText = implode(", ", $labelStrings) . " 상태에서 리뷰 작성 가능";
    
    // 설정 파일을 직접 재생성 (더 안정적)
    $newFileContent = <<<PHP
<?php
/**
 * 리뷰 작성 권한 설정 파일
 * 관리자가 설정할 수 있는 리뷰 작성 권한 관련 설정
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

// 리뷰 작성 권한 설정
// 리뷰를 작성할 수 있는 진행상황 목록
\$review_settings = [
    // 리뷰 작성 가능한 진행상황 목록
    // 가능한 값: 'received', 'activating', 'on_hold', 'cancelled', 'activation_completed', 'installation_completed', 'closed'
    // 인터넷의 경우: 'activating'(개통중), 'installation_completed'/'completed'(설치완료), 'closed'/'terminated'(종료)
    'allowed_statuses' => {$statusesString}, // {$commentText}
];

/**
 * 특정 진행상황에서 리뷰 작성이 가능한지 확인
 * @param string \$application_status 진행상황 (DB에는 영어로 저장됨: received, activating, on_hold, cancelled, activation_completed, installation_completed, closed)
 * @return bool 리뷰 작성 가능 여부
 */
function canWriteReview(\$application_status) {
    global \$review_settings;
    
    if (empty(\$application_status)) {
        return false;
    }
    
    // 상태값 정규화 (소문자로 변환하여 비교)
    \$normalizedStatus = strtolower(trim(\$application_status));
    
    // pending -> received 변환
    if (\$normalizedStatus === 'pending') {
        \$normalizedStatus = 'received';
    }
    
    // 허용된 상태 목록 가져오기
    \$allowedStatuses = \$review_settings['allowed_statuses'] ?? ['activation_completed', 'installation_completed', 'closed'];
    
    // 소문자로 변환된 배열 생성 (비교용)
    \$allowedStatusesLower = array_map('strtolower', \$allowedStatuses);
    
    // 소문자 변환된 상태값과 비교
    return in_array(\$normalizedStatus, \$allowedStatusesLower);
}
PHP;

    // 파일 저장
    $result = file_put_contents($settings_file, $newFileContent, LOCK_EX);
    if ($result !== false) {
        $success_message = '설정이 저장되었습니다.';
        // 설정 파일 다시 로드하여 반영 확인
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($settings_file, true);
        }
        require_once $settings_file;
    } else {
        $message = '설정 저장에 실패했습니다. 파일 쓰기 권한을 확인해주세요. (경로: ' . htmlspecialchars($settings_file) . ')';
        $messageType = 'error';
    }
}

// 현재 설정 읽기
require_once $settings_file;

// 진행상황 옵션
$statusOptions = [
    'received' => '접수',
    'activating' => '개통중',
    'on_hold' => '보류',
    'cancelled' => '취소',
    'activation_completed' => '개통완료',
    'installation_completed' => '설치완료',
    'closed' => '종료'
];

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-container" style="margin-top: 80px; max-width: 700px; margin-left: auto; margin-right: auto;">
    <h1 style="text-align: center; margin-bottom: 32px;">리뷰 작성 권한 설정</h1>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; background: #d1fae5; color: #065f46; border: 1px solid #10b981;">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <!-- 리뷰 작성 권한 설정 -->
        <div class="settings-section" style="margin-bottom: 32px; padding-bottom: 32px; border-bottom: 1px solid #e5e7eb;">
            <h2 class="settings-section-title" style="font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 16px; text-align: center;">리뷰 작성 가능한 진행상황</h2>
            
            <div class="form-group" style="margin-bottom: 24px;">
                <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 12px; text-align: center;">
                    다음 진행상황에서 리뷰를 작성할 수 있습니다:
                </label>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 20px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; max-width: 500px; margin: 0 auto;">
                    <?php 
                    $currentAllowedStatuses = $review_settings['allowed_statuses'] ?? ['activation_completed'];
                    foreach ($statusOptions as $statusValue => $statusLabel): 
                    ?>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px; border-radius: 6px; transition: background 0.2s; <?php echo in_array($statusValue, $currentAllowedStatuses) ? 'background: #eef2ff;' : ''; ?>">
                            <input 
                                type="checkbox" 
                                name="allowed_statuses[]" 
                                value="<?php echo htmlspecialchars($statusValue); ?>"
                                <?php echo in_array($statusValue, $currentAllowedStatuses) ? 'checked' : ''; ?>
                                style="width: 18px; height: 18px; cursor: pointer; flex-shrink: 0;"
                            >
                            <span style="font-size: 14px; color: #374151;">
                                <?php echo htmlspecialchars($statusLabel); ?>
                                <span style="color: #6b7280; font-size: 12px;">(<?php echo htmlspecialchars($statusValue); ?>)</span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-help" style="font-size: 13px; color: #6b7280; margin-top: 16px; text-align: center;">
                    선택한 진행상황의 주문에 대해서만 마이페이지에서 리뷰 작성 버튼이 표시됩니다.
                </div>
            </div>
        </div>
        
        <!-- 저장 버튼 -->
        <div style="display: flex; gap: 12px; justify-content: center; margin-bottom: 32px;">
            <button type="submit" name="save_settings" class="btn btn-primary" style="padding: 12px 32px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; background: #6366f1; color: white;">
                설정 저장
            </button>
        </div>
    </form>
    
    <!-- 안내 섹션 -->
    <div class="settings-section" style="margin-top: 32px;">
        <h2 class="settings-section-title" style="font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 16px; text-align: center;">설정 안내</h2>
        <div style="padding: 20px; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #3b82f6; max-width: 600px; margin: 0 auto;">
            <p style="color: #1e40af; font-size: 14px; margin-bottom: 12px; text-align: center;">
                <strong>리뷰 작성 권한 설정 방법:</strong>
            </p>
            <ul style="color: #1e40af; font-size: 14px; margin-left: 20px; line-height: 1.8;">
                <li>위의 체크박스에서 리뷰 작성이 가능한 진행상황을 선택합니다.</li>
                <li>여러 진행상황을 선택할 수 있습니다.</li>
                <li>선택한 진행상황의 주문에 대해서만 마이페이지에서 "리뷰쓰기" 버튼이 표시됩니다.</li>
                <li>기본값은 "개통완료" 상태입니다.</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>




















