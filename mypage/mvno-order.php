<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = false;

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';

// 로그인 체크 - 로그인하지 않은 경우 회원가입 모달로 리다이렉트
if (!isLoggedIn()) {
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    // 로그인 모달이 있는 홈으로 리다이렉트 (모달 자동 열기)
    header('Location: /MVNO/?show_login=1');
    exit;
}

// 현재 사용자 정보 가져오기
$currentUser = getCurrentUser();
if (!$currentUser) {
    // 사용자 정보를 가져올 수 없으면 로그아웃 처리
    header('Location: /MVNO/?show_login=1');
    exit;
}

$user_id = $currentUser['user_id'];

// 필요한 함수 포함
require_once '../includes/data/product-functions.php';
require_once '../includes/data/db-config.php';

// DB에서 실제 신청 내역 가져오기
$applications = getUserMvnoApplications($user_id);

// 디버깅: 실제 데이터 확인 및 DB 직접 조회
$debugInfo = [];
$pdo = getDBConnection();
if ($pdo) {
    // DB에서 직접 조회해서 비교
    $stmt = $pdo->prepare("
        SELECT 
            a.id as application_id,
            a.order_number,
            a.product_id,
            a.application_status,
            a.created_at as order_date,
            c.additional_info,
            c.user_id
        FROM product_applications a
        INNER JOIN application_customers c ON a.id = c.application_id
        WHERE c.user_id = :user_id 
        AND a.product_type = 'mvno'
        ORDER BY a.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([':user_id' => $user_id]);
    $rawApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debugInfo['raw_count'] = count($rawApplications);
    $debugInfo['formatted_count'] = count($applications);
    
    if (!empty($rawApplications)) {
        $firstRaw = $rawApplications[0];
        $debugInfo['first_application_id'] = $firstRaw['application_id'];
        $debugInfo['first_product_id'] = $firstRaw['product_id'];
        $debugInfo['first_order_number'] = $firstRaw['order_number'];
        
        // additional_info 파싱
        if (!empty($firstRaw['additional_info'])) {
            $decoded = json_decode($firstRaw['additional_info'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $debugInfo['additional_info_parsed'] = true;
                $debugInfo['additional_info_keys'] = implode(', ', array_keys($decoded));
                $debugInfo['has_product_snapshot'] = isset($decoded['product_snapshot']) ? 'YES' : 'NO';
                if (isset($decoded['product_snapshot'])) {
                    $snapshot = $decoded['product_snapshot'];
                    $debugInfo['snapshot_plan_name'] = $snapshot['plan_name'] ?? 'NULL';
                    $debugInfo['snapshot_provider'] = $snapshot['provider'] ?? 'NULL';
                    $debugInfo['snapshot_keys_count'] = count($snapshot);
                }
            } else {
                $debugInfo['additional_info_parsed'] = false;
                $debugInfo['json_error'] = json_last_error_msg();
            }
        } else {
            $debugInfo['additional_info_parsed'] = false;
            $debugInfo['additional_info_empty'] = true;
        }
        
        // 함수가 반환한 첫 번째 데이터 확인
        if (!empty($applications)) {
            $firstApp = $applications[0];
            $debugInfo['formatted_provider'] = $firstApp['provider'] ?? 'NULL';
            $debugInfo['formatted_title'] = $firstApp['title'] ?? 'NULL';
            $debugInfo['formatted_data_main'] = $firstApp['data_main'] ?? 'NULL';
            $debugInfo['formatted_price_main'] = $firstApp['price_main'] ?? 'NULL';
            
            // 추가: 함수가 반환한 모든 응용 프로그램 ID 확인
            $debugInfo['formatted_application_ids'] = array_column($applications, 'application_id');
            $debugInfo['formatted_count_detail'] = count($applications);
            
            // 첫 번째 application_id로 직접 확인
            if (!empty($debugInfo['first_application_id'])) {
                $checkStmt = $pdo->prepare("
                    SELECT additional_info 
                    FROM application_customers 
                    WHERE application_id = :application_id 
                    LIMIT 1
                ");
                $checkStmt->execute([':application_id' => $debugInfo['first_application_id']]);
                $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if ($checkResult && !empty($checkResult['additional_info'])) {
                    $checkDecoded = json_decode($checkResult['additional_info'], true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($checkDecoded['product_snapshot'])) {
                        $checkSnapshot = $checkDecoded['product_snapshot'];
                        $debugInfo['direct_check_plan_name'] = $checkSnapshot['plan_name'] ?? 'NULL';
                        $debugInfo['direct_check_provider'] = $checkSnapshot['provider'] ?? 'NULL';
                    }
                }
            }
            
            // 에러 로그에서 확인할 수 있도록 로그 출력
            error_log("DEBUG PAGE: First application from function - provider: " . ($firstApp['provider'] ?? 'NULL') . ", title: " . ($firstApp['title'] ?? 'NULL'));
        }
    }
}

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div class="content-layout">
        <div class="plans-main-layout">
            <div class="plans-left-section">
                <!-- 페이지 헤더 -->
                <div style="margin-bottom: 24px; padding: 20px 0;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <a href="/MVNO/mypage/mypage.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 알뜰폰</h2>
                    </div>
                </div>

                <!-- 디버깅 정보 (상단 표시) -->
                <?php if (!empty($debugInfo)): ?>
                    <div style="padding: 20px; background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; margin-bottom: 24px; font-size: 13px; color: #92400e;">
                        <h3 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 700;">디버깅 정보</h3>
                        <div style="display: grid; grid-template-columns: auto 1fr; gap: 8px 16px;">
                            <strong>User ID:</strong>
                            <span><?php echo htmlspecialchars($user_id); ?></span>
                            
                            <strong>DB에서 직접 조회한 건수:</strong>
                            <span><?php echo htmlspecialchars($debugInfo['raw_count'] ?? 0); ?></span>
                            
                            <strong>함수에서 반환된 건수:</strong>
                            <span><?php echo htmlspecialchars($debugInfo['formatted_count'] ?? 0); ?></span>
                            
                            <?php if (!empty($debugInfo['first_application_id'])): ?>
                                <strong>첫 번째 신청 ID:</strong>
                                <span><?php echo htmlspecialchars($debugInfo['first_application_id']); ?></span>
                                
                                <strong>Product ID:</strong>
                                <span><?php echo htmlspecialchars($debugInfo['first_product_id'] ?? 'NULL'); ?></span>
                                
                                <strong>Order Number:</strong>
                                <span><?php echo htmlspecialchars($debugInfo['first_order_number'] ?? 'NULL'); ?></span>
                            <?php endif; ?>
                            
                            <?php if (isset($debugInfo['additional_info_parsed'])): ?>
                                <strong>additional_info 파싱:</strong>
                                <span><?php echo $debugInfo['additional_info_parsed'] ? '성공' : '실패'; ?></span>
                                
                                <?php if (!empty($debugInfo['additional_info_keys'])): ?>
                                    <strong>additional_info 키:</strong>
                                    <span><?php echo htmlspecialchars($debugInfo['additional_info_keys']); ?></span>
                                <?php endif; ?>
                                
                                <strong>product_snapshot 존재:</strong>
                                <span><?php echo htmlspecialchars($debugInfo['has_product_snapshot'] ?? 'UNKNOWN'); ?></span>
                                
                                <?php if (!empty($debugInfo['snapshot_plan_name'])): ?>
                                    <strong>Snapshot plan_name:</strong>
                                    <span><?php echo htmlspecialchars($debugInfo['snapshot_plan_name']); ?></span>
                                <?php endif; ?>
                                
                                <?php if (!empty($debugInfo['snapshot_provider'])): ?>
                                    <strong>Snapshot provider:</strong>
                                    <span><?php echo htmlspecialchars($debugInfo['snapshot_provider']); ?></span>
                                <?php endif; ?>
                                
                                <?php if (isset($debugInfo['snapshot_keys_count'])): ?>
                                    <strong>Snapshot 키 개수:</strong>
                                    <span><?php echo htmlspecialchars($debugInfo['snapshot_keys_count']); ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <strong style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #fbbf24;">함수 반환값:</strong>
                            <span style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #fbbf24;"></span>
                            
                            <strong>반환된 건수:</strong>
                            <span><?php echo htmlspecialchars($debugInfo['formatted_count'] ?? 0); ?></span>
                            
                            <?php if (!empty($debugInfo['formatted_application_ids'])): ?>
                                <strong>반환된 Application IDs:</strong>
                                <span><?php echo htmlspecialchars(implode(', ', $debugInfo['formatted_application_ids'])); ?></span>
                            <?php endif; ?>
                            
                            <?php if (!empty($debugInfo['formatted_provider'])): ?>
                                <strong>첫 번째 provider:</strong>
                                <span><?php echo htmlspecialchars($debugInfo['formatted_provider']); ?></span>
                                
                                <strong>첫 번째 title:</strong>
                                <span><?php echo htmlspecialchars($debugInfo['formatted_title'] ?? 'NULL'); ?></span>
                                
                                <strong>첫 번째 data_main:</strong>
                                <span><?php echo htmlspecialchars($debugInfo['formatted_data_main'] ?? 'NULL'); ?></span>
                                
                                <strong>첫 번째 price_main:</strong>
                                <span><?php echo htmlspecialchars($debugInfo['formatted_price_main'] ?? 'NULL'); ?></span>
                            <?php else: ?>
                                <strong style="color: #dc2626;">함수 반환값 없음:</strong>
                                <span style="color: #dc2626;">applications 배열이 비어있거나 첫 번째 항목이 없습니다.</span>
                            <?php endif; ?>
                            
                            <?php if (!empty($debugInfo['json_error'])): ?>
                                <strong style="color: #dc2626;">JSON 에러:</strong>
                                <span style="color: #dc2626;"><?php echo htmlspecialchars($debugInfo['json_error']); ?></span>
                            <?php endif; ?>
                            
                            <?php if (!empty($debugInfo['direct_check_plan_name'])): ?>
                                <strong style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #fbbf24; color: #059669;">직접 조회한 값:</strong>
                                <span style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #fbbf24;"></span>
                                
                                <strong style="color: #059669;">직접 조회 plan_name:</strong>
                                <span style="color: #059669;"><?php echo htmlspecialchars($debugInfo['direct_check_plan_name']); ?></span>
                                
                                <strong style="color: #059669;">직접 조회 provider:</strong>
                                <span style="color: #059669;"><?php echo htmlspecialchars($debugInfo['direct_check_provider'] ?? 'NULL'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- 신청한 알뜰폰 목록 -->
                <div style="margin-bottom: 32px;">
                    <?php if (empty($applications)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: #6b7280;">
                            신청한 알뜰폰이 없습니다.
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($applications as $index => $app): ?>
                                <div class="plan-item" style="padding: 20px; border: 1px solid #e5e7eb; border-radius: 12px; background: white;">
                                    <div style="display: flex; flex-direction: column; gap: 12px;">
                                        <!-- 헤더: 통신사 및 요금제명 -->
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                            <div style="flex: 1;">
                                                <div style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 8px;">
                                                    <?php echo htmlspecialchars($app['provider'] ?? '알 수 없음'); ?> <?php echo htmlspecialchars($app['title'] ?? '요금제 정보 없음'); ?>
                                                </div>
                                                <?php if (!empty($app['data_main'])): ?>
                                                    <div style="font-size: 14px; color: #6b7280; margin-bottom: 6px;">
                                                        <?php echo htmlspecialchars($app['data_main']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($app['price_main'])): ?>
                                                    <div style="font-size: 16px; color: #374151; font-weight: 600;">
                                                        <?php echo htmlspecialchars($app['price_main']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 12px; color: #9ca3af; text-align: right;">
                                                <?php echo htmlspecialchars($app['order_date'] ?? ''); ?>
                                            </div>
                                        </div>
                                        
                                        <!-- 정보: 주문번호 및 상태 -->
                                        <div style="display: flex; gap: 16px; flex-wrap: wrap; padding-top: 12px; border-top: 1px solid #f3f4f6; font-size: 13px;">
                                            <?php if (!empty($app['order_number'])): ?>
                                                <div>
                                                    <span style="color: #6b7280;">주문번호:</span>
                                                    <span style="color: #374151; font-weight: 500; margin-left: 4px;"><?php echo htmlspecialchars($app['order_number']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($app['status'])): ?>
                                                <div>
                                                    <span style="color: #6b7280;">진행상황:</span>
                                                    <span style="color: #6366f1; font-weight: 600; margin-left: 4px;"><?php echo htmlspecialchars($app['status']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// 푸터 포함
include '../includes/footer.php';
?>
