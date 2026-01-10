<?php
/**
 * 리뷰 설정 디버깅 페이지
 * 실제 저장된 값과 함수 작동 여부 확인
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/path-config.php';
require_once __DIR__ . '/../includes/data/review-settings.php';

// 관리자 인증 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    header('Location: ' . getAssetPath('/admin/login.php'));
    exit;
}

require_once __DIR__ . '/includes/admin-header.php';

// 테스트할 상태값들
$testStatuses = [
    'pending' => '대기중 (pending)',
    'received' => '접수',
    'activating' => '개통중',
    'on_hold' => '보류',
    'cancelled' => '취소',
    'activation_completed' => '개통완료',
    'installation_completed' => '설치완료',
    'closed' => '종료'
];

// 실제 DB에서 주문 상태 확인
$pdo = getDBConnection();
$actualStatuses = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT application_status FROM product_applications WHERE application_status IS NOT NULL AND application_status != '' ORDER BY application_status");
        $actualStatuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("Error getting actual statuses: " . $e->getMessage());
    }
}
?>

<div class="admin-container" style="margin-top: 80px; max-width: 1200px; margin-left: auto; margin-right: auto;">
    <h1 style="text-align: center; margin-bottom: 32px;">리뷰 설정 디버깅</h1>
    
    <!-- 현재 저장된 설정 -->
    <div class="admin-card" style="margin-bottom: 24px;">
        <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">현재 저장된 설정</h2>
        <div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
            <pre style="background: white; padding: 16px; border-radius: 6px; overflow-x: auto; font-size: 13px;"><?php 
                echo htmlspecialchars("allowed_statuses => " . var_export($review_settings['allowed_statuses'] ?? [], true)); 
            ?></pre>
        </div>
    </div>
    
    <!-- 함수 테스트 -->
    <div class="admin-card" style="margin-bottom: 24px;">
        <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">canWriteReview() 함수 테스트</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left; font-weight: 600;">상태값 (영문)</th>
                    <th style="padding: 12px; text-align: left; font-weight: 600;">상태값 (한글)</th>
                    <th style="padding: 12px; text-align: center; font-weight: 600;">정규화 후</th>
                    <th style="padding: 12px; text-align: center; font-weight: 600;">리뷰 작성 가능</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($testStatuses as $statusEn => $statusKo): ?>
                    <?php 
                    $normalized = strtolower(trim($statusEn ?? ''));
                    if (empty($normalized) || $normalized === 'pending') {
                        $normalized = 'received';
                    }
                    $canWrite = canWriteReview($statusEn);
                    ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px;"><?php echo htmlspecialchars($statusEn); ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($statusKo); ?></td>
                        <td style="padding: 12px; text-align: center; font-family: monospace;"><?php echo htmlspecialchars($normalized); ?></td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="padding: 4px 12px; border-radius: 4px; font-weight: 600; <?php echo $canWrite ? 'background: #d1fae5; color: #065f46;' : 'background: #fee2e2; color: #991b1b;'; ?>">
                                <?php echo $canWrite ? '가능' : '불가능'; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- 실제 DB 상태값 -->
    <div class="admin-card" style="margin-bottom: 24px;">
        <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">실제 DB에 저장된 상태값</h2>
        <div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
            <?php if (!empty($actualStatuses)): ?>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php foreach ($actualStatuses as $status): ?>
                        <?php 
                        $canWrite = canWriteReview($status);
                        $statusLabel = $testStatuses[$status] ?? $status;
                        
                        // 정규화 확인
                        $normalized = strtolower(trim($status ?? ''));
                        if ($normalized === 'pending') {
                            $normalized = 'received';
                        }
                        ?>
                        <div style="padding: 8px 12px; background: white; border-radius: 6px; border: 1px solid #e5e7eb; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <span style="font-weight: 600; color: #374151;"><?php echo htmlspecialchars($status); ?></span>
                                <span style="color: #6b7280;">(<?php echo htmlspecialchars($statusLabel); ?>)</span>
                                <span style="font-size: 11px; color: #9ca3af; font-family: monospace;">→ 정규화: <?php echo htmlspecialchars($normalized); ?></span>
                                <span style="margin-left: auto; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; <?php echo $canWrite ? 'background: #d1fae5; color: #065f46;' : 'background: #fee2e2; color: #991b1b;'; ?>">
                                    <?php echo $canWrite ? '리뷰 가능' : '리뷰 불가'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #6b7280;">DB에서 상태값을 찾을 수 없습니다.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 설정 파일 원본 확인 -->
    <div class="admin-card">
        <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">설정 파일 원본 내용</h2>
        <div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
            <pre style="background: white; padding: 16px; border-radius: 6px; overflow-x: auto; font-size: 12px; max-height: 400px; overflow-y: auto;"><?php 
                $settings_file = __DIR__ . '/../includes/data/review-settings.php';
                echo htmlspecialchars(file_get_contents($settings_file)); 
            ?></pre>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
