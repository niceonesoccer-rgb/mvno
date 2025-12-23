<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

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
    // 세션 정리 후 로그인 페이지로 리다이렉트
    if (isset($_SESSION['logged_in'])) {
        unset($_SESSION['logged_in']);
    }
    if (isset($_SESSION['user_id'])) {
        unset($_SESSION['user_id']);
    }
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: /MVNO/?show_login=1');
    exit;
}

$user_id = $currentUser['user_id'];

// 필요한 함수 포함
require_once '../includes/data/product-functions.php';
require_once '../includes/data/db-config.php';
require_once '../includes/data/contract-type-functions.php';
require_once '../includes/data/plan-data.php';

// DB에서 실제 신청 내역 가져오기
$applications = getUserMvnoApplications($user_id);

// 헤더 포함
include '../includes/header.php';
// 리뷰 모달 포함
include '../includes/components/mvno-review-modal.php';
?>

<main class="main-content">
    <div class="content-layout">
        <div class="plans-main-layout">
            <div class="plans-left-section">
                <!-- 페이지 헤더 -->
                <div style="margin-bottom: 24px; padding: 20px 0;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <a href="/MVNO/mypage/mypage.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 알뜰폰</h2>
                    </div>
                    <p style="font-size: 14px; color: #6b7280; margin: 0; margin-left: 36px;">카드를 클릭하면 신청 정보를 확인할 수 있습니다.</p>
                </div>
                
                <!-- 신청한 알뜰폰 목록 -->
                <div style="margin-bottom: 32px;">
                    <?php if (empty($applications)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: #6b7280;">
                            신청한 알뜰폰이 없습니다.
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;" id="applicationsContainer">
                            <?php foreach ($applications as $index => $app): ?>
                                <div class="plan-item application-card" 
                                     data-index="<?php echo $index; ?>"
                                     data-application-id="<?php echo htmlspecialchars($app['application_id'] ?? ''); ?>"
                                     style="<?php echo $index >= 10 ? 'display: none;' : ''; ?> padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"
                                     onmouseover="this.style.borderColor='#6366f1'; this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.15)'"
                                     onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'">
                                    
                                    <!-- 상단: 요금제 정보 -->
                                    <div style="position: relative; margin-bottom: 16px;">
                                        <!-- 주문일자 (상단 오른쪽) -->
                                        <?php
                                        $orderDate = $app['order_date'] ?? '';
                                        if ($orderDate) {
                                            try {
                                                $dateObj = new DateTime($orderDate);
                                                $formattedDate = $dateObj->format('Y.m.d');
                                            } catch (Exception $e) {
                                                $formattedDate = $orderDate;
                                            }
                                        } else {
                                            $formattedDate = '';
                                        }
                                        ?>
                                        <?php if ($formattedDate): ?>
                                            <div style="position: absolute; top: 0; right: 0; font-size: 13px; color: #6b7280;">
                                                <?php echo htmlspecialchars($formattedDate); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                            <div style="flex: 1;">
                                                <div style="font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 6px; line-height: 1.3;">
                                                    <?php echo htmlspecialchars($app['provider'] ?? '알 수 없음'); ?> <?php echo htmlspecialchars($app['title'] ?? '요금제 정보 없음'); ?>
                                                </div>
                                                <?php if (!empty($app['data_main'])): ?>
                                                    <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;">
                                                        <?php echo htmlspecialchars($app['data_main']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div style="display: flex; align-items: baseline; gap: 12px; flex-wrap: wrap;">
                                                    <?php if (!empty($app['price_main'])): ?>
                                                        <div style="font-size: 18px; color: #1f2937; font-weight: 700;">
                                                            <?php echo htmlspecialchars($app['price_main']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($app['price_after'])): ?>
                                                        <div style="font-size: 14px; color: #6b7280;">
                                                            <?php echo htmlspecialchars($app['price_after']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 중간: 약정기간 및 프로모션 기간 -->
                                    <div style="display: flex; gap: 16px; flex-wrap: wrap; padding: 12px 0; margin-bottom: 12px;">
                                        <?php if (!empty($app['contract_period'])): ?>
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #6366f1; flex-shrink: 0;">
                                                    <path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span style="font-size: 13px; color: #6b7280;">약정기간:</span>
                                                <span style="font-size: 13px; color: #374151; font-weight: 600;"><?php echo htmlspecialchars($app['contract_period']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($app['discount_period'])): ?>
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #10b981; flex-shrink: 0;">
                                                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span style="font-size: 13px; color: #6b7280;">프로모션:</span>
                                                <span style="font-size: 13px; color: #10b981; font-weight: 600;"><?php echo htmlspecialchars($app['discount_period']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- 하단: 주문번호 및 진행상황 -->
                                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
                                        <div style="display: flex; gap: 16px; flex-wrap: wrap; font-size: 13px;">
                                            <?php if (!empty($app['order_number'])): ?>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <span style="color: #6b7280;">주문번호</span>
                                                    <span style="color: #374151; font-weight: 600;"><?php echo htmlspecialchars($app['order_number']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php 
                                        // 상태별 배지 색상 설정
                                        $appStatus = $app['application_status'] ?? '';
                                        $statusBg = '#eef2ff'; // 기본 파란색
                                        $statusColor = '#6366f1';
                                        
                                        if (!empty($appStatus)) {
                                            switch ($appStatus) {
                                                case 'pending':
                                                    $statusBg = '#fef3c7'; // 노란색
                                                    $statusColor = '#92400e';
                                                    break;
                                                case 'processing':
                                                    $statusBg = '#e0e7ff'; // 보라색
                                                    $statusColor = '#6366f1';
                                                    break;
                                                case 'completed':
                                                    $statusBg = '#d1fae5'; // 초록색
                                                    $statusColor = '#065f46';
                                                    break;
                                                case 'cancelled':
                                                case 'rejected':
                                                    $statusBg = '#fee2e2'; // 빨간색
                                                    $statusColor = '#991b1b';
                                                    break;
                                                case 'closed':
                                                    $statusBg = '#f3f4f6'; // 회색
                                                    $statusColor = '#6b7280';
                                                    break;
                                            }
                                        }
                                        
                                        if (!empty($app['status'])): ?>
                                            <div style="display: inline-flex; align-items: center; padding: 6px 12px; background: <?php echo $statusBg; ?>; border-radius: 6px;">
                                                <span style="font-size: 13px; color: <?php echo $statusColor; ?>; font-weight: 600;"><?php echo htmlspecialchars($app['status']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- 판매자 정보 및 리뷰 작성 영역 -->
                                    <?php
                                    // getUserMvnoApplications는 'id' 키에 product_id를 저장하고 'product_id' 키도 추가함
                                    $productId = $app['product_id'] ?? ($app['id'] ?? 0);
                                    $applicationId = $app['application_id'] ?? '';
                                    
                                    // 판매자 정보 가져오기 (모든 상태에서)
                                    // getUserMvnoApplications에서 seller_id를 반환 배열에 포함시킴
                                    $sellerId = isset($app['seller_id']) && $app['seller_id'] > 0 ? (int)$app['seller_id'] : null;
                                    $seller = null;
                                    $sellerPhone = '';
                                    
                                    // 디버깅: 초기값 확인
                                    error_log("MVNO Order Debug - application_id: {$applicationId}, product_id: {$productId}, seller_id from app: " . ($sellerId ?: 'NULL'));
                                    
                                    if ($sellerId) {
                                        // 판매자 정보 가져오기
                                        $seller = getSellerById($sellerId);
                                        
                                        // 디버깅: 판매자 정보 조회 결과
                                        if ($seller) {
                                            error_log("MVNO Order Debug - seller found for seller_id {$sellerId}: name=" . ($seller['seller_name'] ?? '') . ", phone=" . ($seller['phone'] ?? '') . ", mobile=" . ($seller['mobile'] ?? ''));
                                        } else {
                                            error_log("MVNO Order Debug - seller NOT found for seller_id: {$sellerId}");
                                        }
                                    } else {
                                        error_log("MVNO Order Debug - No seller_id in application data for product_id: {$productId}");
                                        // seller_id가 없으면 product_id로 다시 시도
                                        try {
                                            $pdo = getDBConnection();
                                            if ($pdo && $productId > 0) {
                                                $sellerStmt = $pdo->prepare("SELECT seller_id FROM products WHERE id = :product_id LIMIT 1");
                                                $sellerStmt->execute([':product_id' => $productId]);
                                                $product = $sellerStmt->fetch(PDO::FETCH_ASSOC);
                                                if ($product && !empty($product['seller_id'])) {
                                                    $sellerId = $product['seller_id'];
                                                    $seller = getSellerById($sellerId);
                                                    error_log("MVNO Order Debug - seller_id found via products table: {$sellerId}");
                                                }
                                            }
                                        } catch (Exception $e) {
                                            error_log("MVNO Order Debug - Exception: " . $e->getMessage());
                                        }
                                    }
                                    
                                    $sellerPhone = $seller ? ($seller['phone'] ?? ($seller['mobile'] ?? '')) : '';
                                    error_log("MVNO Order Debug - Final sellerPhone for application {$applicationId}: " . ($sellerPhone ?: 'EMPTY'));
                                    
                                    // 판매자명 가져오기 (seller_name > company_name > name 우선순위)
                                    $sellerName = '';
                                    if ($seller) {
                                        if (!empty($seller['seller_name'])) {
                                            $sellerName = $seller['seller_name'];
                                        } elseif (!empty($seller['company_name'])) {
                                            $sellerName = $seller['company_name'];
                                        } elseif (!empty($seller['name'])) {
                                            $sellerName = $seller['name'];
                                        }
                                    }
                                    
                                    // 판매자명과 전화번호 결합 (두 칸 띄고)
                                    $sellerPhoneDisplay = $sellerPhone;
                                    if ($sellerName && $sellerPhone) {
                                        $sellerPhoneDisplay = $sellerName . '  ' . $sellerPhone;
                                    }
                                    
                                    // 개통완료 또는 종료 상태일 때만 리뷰 버튼 표시 (인터넷과 동일하게)
                                    $canWrite = in_array($appStatus, ['activation_completed', 'completed', 'closed', 'terminated']);
                                    
                                    // 디버깅: 화면에 임시로 정보 표시 (나중에 제거) - URL에 ?debug=1 추가하면 표시됨
                                    $debugInfo = [
                                        'product_id' => $productId,
                                        'application_id' => $applicationId,
                                        'seller_id_from_app' => $app['seller_id'] ?? 'NULL',
                                        'seller_id_used' => $sellerId ?: 'NULL',
                                        'seller_found' => $seller ? 'YES' : 'NO',
                                        'seller_phone' => $sellerPhone ?: 'EMPTY',
                                        'seller_name' => $sellerName ?: 'EMPTY',
                                        'canWrite' => $canWrite ? 'YES' : 'NO',
                                        'appStatus' => $appStatus ?: 'EMPTY'
                                    ];
                                    $hasReview = false;
                                    $buttonText = '리뷰 작성';
                                    $buttonClass = 'mvno-review-write-btn';
                                    $buttonBgColor = '#EF4444';
                                    $buttonHoverColor = '#dc2626';
                                    
                                    if ($canWrite) {
                                        // 주문건별로 여러 리뷰 작성 가능하므로 항상 "리뷰 작성" 버튼 표시
                                        // 최근 작성한 리뷰가 있으면 수정 가능하도록 확인
                                        $latestReviewId = null;
                                        if ($applicationId && $productId) {
                                            try {
                                                $pdo = getDBConnection();
                                                if ($pdo) {
                                                    // 같은 주문건의 최근 리뷰 확인 (수정용)
                                                    $reviewStmt = $pdo->prepare("
                                                        SELECT id 
                                                        FROM product_reviews 
                                                        WHERE application_id = :application_id 
                                                        AND product_id = :product_id 
                                                        AND user_id = :user_id 
                                                        AND product_type = 'mvno'
                                                        AND status != 'deleted'
                                                        ORDER BY created_at DESC
                                                        LIMIT 1
                                                    ");
                                                    $reviewStmt->execute([
                                                        ':application_id' => $applicationId,
                                                        ':product_id' => $productId,
                                                        ':user_id' => $user_id
                                                    ]);
                                                    $reviewResult = $reviewStmt->fetch(PDO::FETCH_ASSOC);
                                                    if ($reviewResult) {
                                                        $latestReviewId = $reviewResult['id'];
                                                        $hasReview = true;
                                                    }
                                                }
                                            } catch (Exception $e) {
                                                error_log("Error checking review: " . $e->getMessage());
                                            }
                                        }
                                        
                                        // 최근 리뷰가 있으면 "리뷰 수정", 없으면 "리뷰 작성"
                                        $buttonText = $hasReview ? '리뷰 수정' : '리뷰 작성';
                                        $buttonClass = $hasReview ? 'mvno-review-edit-btn' : 'mvno-review-write-btn';
                                        $buttonBgColor = $hasReview ? '#6b7280' : '#EF4444';
                                        $buttonHoverColor = $hasReview ? '#4b5563' : '#dc2626';
                                        
                                        // 최근 리뷰 ID를 data 속성에 저장 (수정 시 사용)
                                        if ($latestReviewId) {
                                            $buttonDataReviewId = ' data-review-id="' . htmlspecialchars($latestReviewId) . '"';
                                        } else {
                                            $buttonDataReviewId = '';
                                        }
                                    }
                                    
                                    // 디버깅: 조건 확인
                                    error_log("MVNO Order Debug - Condition check: sellerPhone=" . ($sellerPhone ?: 'EMPTY') . ", canWrite=" . ($canWrite ? 'true' : 'false') . ", will show section=" . (($sellerPhone || $canWrite) ? 'YES' : 'NO'));
                                    
                                    // 판매자 전화번호가 있거나 리뷰 버튼이 필요한 경우에만 섹션 표시
                                    if ($sellerPhone || $canWrite):
                                    ?>
                                        <!-- 디버깅 정보 (임시) -->
                                        <?php if (isset($_GET['debug']) && $_GET['debug'] === '1'): ?>
                                        <div style="margin-top: 8px; padding: 8px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 4px; font-size: 12px; color: #92400e;">
                                            <strong>디버그 정보:</strong><br>
                                            product_id: <?php echo htmlspecialchars($debugInfo['product_id']); ?><br>
                                            seller_id (from app): <?php echo htmlspecialchars($debugInfo['seller_id_from_app']); ?><br>
                                            seller_id (used): <?php echo htmlspecialchars($debugInfo['seller_id_used']); ?><br>
                                            seller found: <?php echo htmlspecialchars($debugInfo['seller_found']); ?><br>
                                            seller_phone: <?php echo htmlspecialchars($debugInfo['seller_phone']); ?><br>
                                            canWrite: <?php echo htmlspecialchars($debugInfo['canWrite']); ?><br>
                                            condition: sellerPhone=<?php echo $sellerPhone ? 'YES' : 'NO'; ?> || canWrite=<?php echo $canWrite ? 'YES' : 'NO'; ?> = <?php echo ($sellerPhone || $canWrite) ? 'SHOW' : 'HIDE'; ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                                            <div class="review-section-layout" style="display: grid; grid-template-columns: <?php echo ($sellerPhone && $canWrite) ? '1fr 1fr' : '1fr'; ?>; gap: 16px;">
                                                <!-- 왼쪽: 전화번호 (모든 상태에서 표시) -->
                                                <?php if ($sellerPhone): 
                                                    $phoneNumberOnly = preg_replace('/[^0-9]/', '', $sellerPhone);
                                                ?>
                                                    <div style="display: flex; align-items: center; justify-content: center;" onclick="event.stopPropagation();">
                                                        <!-- PC 버전: 전화번호 버튼 (클릭 불가) -->
                                                        <button class="phone-inquiry-pc" 
                                                                disabled
                                                                style="width: 100%; padding: 12px 16px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: not-allowed;">
                                                            <?php echo htmlspecialchars($sellerPhoneDisplay); ?>
                                                        </button>
                                                        <!-- 모바일 버전: 전화번호 버튼 (클릭 시 전화 연결) -->
                                                        <a href="tel:<?php echo htmlspecialchars($phoneNumberOnly); ?>" 
                                                           class="phone-inquiry-mobile"
                                                           onclick="event.stopPropagation();"
                                                           style="display: none; width: 100%; align-items: center; justify-content: center; padding: 12px 16px; background: #EF4444; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; text-decoration: none; cursor: pointer; transition: background 0.2s;"
                                                           onmouseover="this.style.background='#dc2626'"
                                                           onmouseout="this.style.background='#EF4444'">
                                                            <?php echo htmlspecialchars($sellerPhoneDisplay); ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- 오른쪽: 리뷰 작성 버튼 (개통완료/종료 상태일 때만 표시) -->
                                                <?php if ($canWrite): ?>
                                                <div style="display: flex; align-items: center; justify-content: center; gap: 8px;" onclick="event.stopPropagation();">
                                                    <button 
                                                        class="<?php echo $buttonClass; ?>" 
                                                        data-application-id="<?php echo htmlspecialchars($applicationId); ?>"
                                                        data-product-id="<?php echo htmlspecialchars($productId); ?>"
                                                        data-has-review="<?php echo $hasReview ? '1' : '0'; ?>"
                                                        <?php if (isset($buttonDataReviewId)) echo $buttonDataReviewId; ?>
                                                        style="flex: 1; padding: 12px 16px; background: <?php echo $buttonBgColor; ?>; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.2s;"
                                                        onmouseover="this.style.background='<?php echo $buttonHoverColor; ?>'"
                                                        onmouseout="this.style.background='<?php echo $buttonBgColor; ?>'">
                                                        <?php echo htmlspecialchars($buttonText); ?>
                                                    </button>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- 더보기 버튼 -->
                        <?php if (count($applications) > 10): ?>
                        <div style="margin-top: 32px; margin-bottom: 32px;" id="moreButtonContainer">
                            <button class="plan-review-more-btn" id="moreApplicationsBtn" style="width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                                더보기 (<?php 
                                $remaining = count($applications) - 10;
                                echo $remaining > 10 ? 10 : $remaining;
                                ?>개)
                            </button>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- 신청 상세 정보 모달 -->
<div id="applicationDetailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 10000; overflow-y: auto; padding: 20px;">
    <div style="max-width: 800px; margin: 40px auto; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); position: relative;">
        <!-- 모달 헤더 -->
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 24px; font-weight: bold; margin: 0; color: #1f2937;">등록정보</h2>
            <button id="closeModalBtn" style="background: none; border: none; cursor: pointer; padding: 8px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
        <!-- 모달 내용 -->
        <div id="modalContent" style="padding: 24px; max-height: calc(100vh - 200px); overflow-y: auto;">
            <div style="text-align: center; padding: 40px; color: #6b7280;">
                <div class="spinner" style="border: 3px solid #f3f4f6; border-top: 3px solid #6366f1; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
                <p>정보를 불러오는 중...</p>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<!-- 전화번호 반응형 스타일 및 리뷰 삭제 버튼 스타일 -->
<style>
@media (max-width: 768px) {
    .phone-inquiry-pc {
        display: none !important;
    }
    .phone-inquiry-mobile {
        display: flex !important;
    }
}
@media (min-width: 769px) {
    .phone-inquiry-pc {
        display: block !important;
    }
    .phone-inquiry-mobile {
        display: none !important;
    }
}

/* MVNO 리뷰 삭제 버튼 스타일 */
.mvno-review-btn-delete {
    background: #fee2e2;
    color: #dc2626;
    padding: 16px 20px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
}

.mvno-review-btn-delete:hover {
    background: #fecaca;
    color: #b91c1c;
    transform: translateY(-1px);
}

.mvno-review-btn-delete:active {
    transform: translateY(0);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // MVNO 리뷰 작성/수정 기능
    const reviewWriteButtons = document.querySelectorAll('.mvno-review-write-btn, .mvno-review-edit-btn');
    const reviewModal = document.getElementById('mvnoReviewModal');
    const reviewForm = document.getElementById('mvnoReviewForm');
    const reviewModalClose = reviewModal ? reviewModal.querySelector('.mvno-review-modal-close') : null;
    const reviewModalOverlay = reviewModal ? reviewModal.querySelector('.mvno-review-modal-overlay') : null;
    const reviewCancelBtn = reviewForm ? reviewForm.querySelector('.mvno-review-btn-cancel') : null;
    
    let currentReviewApplicationId = null;
    let currentReviewProductId = null;
    let currentReviewId = null;
    let isEditMode = false;
    
    // 리뷰 작성/수정 버튼 클릭 이벤트
    reviewWriteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); // 카드 클릭 이벤트 방지
            currentReviewApplicationId = this.getAttribute('data-application-id');
            currentReviewProductId = this.getAttribute('data-product-id');
            const hasReview = this.getAttribute('data-has-review') === '1';
            const reviewIdAttr = this.getAttribute('data-review-id');
            isEditMode = hasReview && reviewIdAttr !== null;
            currentReviewId = reviewIdAttr ? parseInt(reviewIdAttr) : null;
            
            if (reviewModal) {
                // 먼저 모달 제목과 버튼 텍스트를 설정
                const modalTitle = reviewModal.querySelector('.mvno-review-modal-title');
                if (modalTitle) {
                    modalTitle.textContent = isEditMode ? '리뷰 수정' : '리뷰 작성';
                }
                
                                // 제출 버튼 텍스트 변경
                const submitBtn = reviewForm ? reviewForm.querySelector('.mvno-review-btn-submit') : null;
                if (submitBtn) {
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                }
                
                // 삭제 버튼 표시/숨김
                const deleteBtn = document.getElementById('mvnoReviewDeleteBtn');
                if (deleteBtn) {
                    deleteBtn.style.display = isEditMode ? 'flex' : 'none';
                }
                
                // 현재 스크롤 위치 저장
                const scrollY = window.scrollY;
                document.body.style.position = 'fixed';
                document.body.style.top = `-${scrollY}px`;
                document.body.style.width = '100%';
                document.body.style.overflow = 'hidden';
                
                // 폼 초기화
                if (reviewForm) {
                    reviewForm.reset();
                    // 별점 초기화
                    const starLabels = reviewForm.querySelectorAll('.star-label');
                    starLabels.forEach(label => {
                        label.classList.remove('active');
                        label.classList.remove('hover-active');
                    });
                }
                
                // 수정 모드일 경우 기존 리뷰 데이터 로드
                if (isEditMode && currentReviewApplicationId && currentReviewProductId) {
                    fetch(`/MVNO/api/get-review-by-application.php?application_id=${currentReviewApplicationId}&product_id=${currentReviewProductId}&product_type=mvno`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.review) {
                                currentReviewId = data.review.id;
                                // 별점 설정
                                if (data.review.kindness_rating) {
                                    const kindnessInput = reviewForm.querySelector(`input[name="kindness_rating"][value="${data.review.kindness_rating}"]`);
                                    if (kindnessInput) {
                                        kindnessInput.checked = true;
                                        const rating = parseInt(data.review.kindness_rating);
                                        const kindnessLabels = reviewForm.querySelectorAll('.mvno-star-rating[data-rating-type="kindness"] .star-label');
                                        kindnessLabels.forEach((label, index) => {
                                            if (index < rating) {
                                                label.classList.add('active');
                                            } else {
                                                label.classList.remove('active');
                                            }
                                        });
                                    }
                                }
                                if (data.review.speed_rating) {
                                    const speedInput = reviewForm.querySelector(`input[name="speed_rating"][value="${data.review.speed_rating}"]`);
                                    if (speedInput) {
                                        speedInput.checked = true;
                                        const rating = parseInt(data.review.speed_rating);
                                        const speedLabels = reviewForm.querySelectorAll('.mvno-star-rating[data-rating-type="speed"] .star-label');
                                        speedLabels.forEach((label, index) => {
                                            if (index < rating) {
                                                label.classList.add('active');
                                            } else {
                                                label.classList.remove('active');
                                            }
                                        });
                                    }
                                }
                                // 리뷰 내용 설정
                                const reviewTextarea = reviewForm.querySelector('#mvnoReviewText');
                                if (reviewTextarea && data.review.content) {
                                    reviewTextarea.value = data.review.content;
                                    // 텍스트 카운터 업데이트
                                    const counter = document.getElementById('mvnoReviewTextCounter');
                                    if (counter) {
                                        counter.textContent = data.review.content.length;
                                    }
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error loading review:', error);
                        });
                }
                
                // 모달 표시
                reviewModal.style.display = 'block';
            }
        });
    });
    
    // 모달 닫기 함수
    function closeReviewModal() {
        if (reviewModal) {
            const scrollY = document.body.style.top;
            reviewModal.style.display = 'none';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.body.style.overflow = '';
            if (scrollY) {
                window.scrollTo(0, parseInt(scrollY || '0') * -1);
            }
        }
    }
    
    // 모달 닫기 이벤트
    if (reviewModalClose) {
        reviewModalClose.addEventListener('click', closeReviewModal);
    }
    
    if (reviewModalOverlay) {
        reviewModalOverlay.addEventListener('click', closeReviewModal);
    }
    
    if (reviewCancelBtn) {
        reviewCancelBtn.addEventListener('click', closeReviewModal);
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && reviewModal && reviewModal.style.display === 'block') {
            closeReviewModal();
        }
    });
    
    // 리뷰 폼 제출
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const kindnessRatingInput = reviewForm.querySelector('input[name="kindness_rating"]:checked');
            const speedRatingInput = reviewForm.querySelector('input[name="speed_rating"]:checked');
            const reviewText = reviewForm.querySelector('#mvnoReviewText').value.trim();
            
            if (!kindnessRatingInput) {
                showAlert('친절해요 별점을 선택해주세요.', '알림');
                return;
            }
            
            if (!speedRatingInput) {
                showAlert('개통 빨라요 별점을 선택해주세요.', '알림');
                return;
            }
            
            if (!reviewText) {
                showAlert('리뷰 내용을 입력해주세요.', '알림');
                return;
            }
            
            const formData = new FormData();
            formData.append('product_id', currentReviewProductId);
            formData.append('product_type', 'mvno');
            formData.append('kindness_rating', kindnessRatingInput.value);
            formData.append('speed_rating', speedRatingInput.value);
            formData.append('content', reviewText);
            formData.append('application_id', currentReviewApplicationId);
            
            if (isEditMode && currentReviewId) {
                formData.append('review_id', currentReviewId);
            }
            
            // 제출 버튼 비활성화
            const submitBtn = reviewForm.querySelector('.mvno-review-btn-submit');
            const submitBtnSpan = submitBtn ? submitBtn.querySelector('span') : null;
            if (submitBtn) {
                submitBtn.disabled = true;
                if (submitBtnSpan) {
                    submitBtnSpan.textContent = '처리 중...';
                } else {
                    submitBtn.textContent = '처리 중...';
                }
            }
            
            fetch('/MVNO/api/submit-review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('리뷰가 ' + (isEditMode ? '수정' : '작성') + '되었습니다.', '알림').then(() => {
                        closeReviewModal();
                        location.reload(); // 페이지 새로고침하여 리뷰 버튼 상태 업데이트
                    });
                } else {
                    showAlert(data.message || '리뷰 작성에 실패했습니다.', '오류').then(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            if (submitBtnSpan) {
                                submitBtnSpan.textContent = isEditMode ? '저장하기' : '작성하기';
                            } else {
                                submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('리뷰 작성 중 오류가 발생했습니다.', '오류').then(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        if (submitBtnSpan) {
                            submitBtnSpan.textContent = isEditMode ? '저장하기' : '작성하기';
                        } else {
                            submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                        }
                    }
                });
            });
        });
    }
    
    // MVNO 리뷰 삭제 버튼 클릭 이벤트
    const deleteReviewBtn = document.getElementById('mvnoReviewDeleteBtn');
    if (deleteReviewBtn) {
        deleteReviewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const reviewId = this.getAttribute('data-review-id') || currentReviewId;
            if (!reviewId) {
                showAlert('리뷰 정보를 찾을 수 없습니다.', '오류');
                return;
            }
            
            showConfirm('정말로 리뷰를 삭제하시겠습니까?\n삭제된 리뷰는 복구할 수 없습니다.', '리뷰 삭제').then(confirmed => {
                if (!confirmed) return;
                
                // 삭제 버튼 비활성화
                this.disabled = true;
                const originalText = this.querySelector('span').textContent;
                this.querySelector('span').textContent = '삭제 중...';
                
                const formData = new FormData();
                formData.append('review_id', reviewId);
                formData.append('product_type', 'mvno');
                
                fetch('/MVNO/api/delete-review.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('리뷰가 삭제되었습니다.', '알림').then(() => {
                            closeReviewModal();
                            location.reload();
                        });
                    } else {
                        showAlert(data.message || '리뷰 삭제에 실패했습니다.', '오류').then(() => {
                            this.disabled = false;
                            this.querySelector('span').textContent = originalText;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('리뷰 삭제 중 오류가 발생했습니다.', '오류').then(() => {
                        this.disabled = false;
                        this.querySelector('span').textContent = originalText;
                    });
                });
            });
        });
    }
    
    // 기존 코드
    const modal = document.getElementById('applicationDetailModal');
    const modalContent = document.getElementById('modalContent');
    const closeBtn = document.getElementById('closeModalBtn');
    const applicationCards = document.querySelectorAll('.application-card');
    
    // 카드 클릭 이벤트
    applicationCards.forEach(card => {
        card.addEventListener('click', function(e) {
            const applicationId = this.getAttribute('data-application-id');
            if (applicationId) {
                openModal(applicationId);
            }
        });
    });
    
    // 모달 닫기 버튼
    closeBtn.addEventListener('click', closeModal);
    
    // 배경 클릭 시 모달 닫기
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
        }
    });
    
    function openModal(applicationId) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // 배경 스크롤 방지
        
        // 로딩 표시
        modalContent.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #6b7280;">
                <div class="spinner" style="border: 3px solid #f3f4f6; border-top: 3px solid #6366f1; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
                <p>정보를 불러오는 중...</p>
            </div>
        `;
        
        // API 호출
        fetch(`/MVNO/api/get-application-details.php?application_id=${applicationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayApplicationDetails(data.data);
                } else {
                    modalContent.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #dc2626;">
                            <p>정보를 불러오는 중 오류가 발생했습니다.</p>
                            <p style="font-size: 14px; margin-top: 8px;">${data.message || '알 수 없는 오류'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalContent.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc2626;">
                        <p>정보를 불러오는 중 오류가 발생했습니다.</p>
                        <p style="font-size: 14px; margin-top: 8px;">네트워크 오류가 발생했습니다.</p>
                    </div>
                `;
            });
    }
    
    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = ''; // 배경 스크롤 복원
    }
    
    function displayApplicationDetails(data) {
        const customer = data.customer || {};
        const additionalInfo = data.additional_info || {};
        const productSnapshot = additionalInfo.product_snapshot || {};
        
        let html = '<div style="display: flex; flex-direction: column; gap: 24px;">';
        
        // 주문 정보 섹션 (맨 위로 이동)
        html += '<div>';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">주문 정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        if (data.order_number) {
            html += `<div style="color: #6b7280; font-weight: 500;">주문번호:</div>`;
            html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(data.order_number)}</div>`;
        }
        
        if (data.status) {
            html += `<div style="color: #6b7280; font-weight: 500;">진행상황:</div>`;
            html += `<div style="color: #6366f1; font-weight: 600;">${escapeHtml(data.status)}</div>`;
        }
        
        if (data.status_changed_at) {
            html += `<div style="color: #6b7280; font-weight: 500;">상태 변경일시:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(data.status_changed_at)}</div>`;
        }
        
        html += '</div></div>';
        
        // 고객 정보 섹션
        html += '<div>';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">고객 정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        if (customer.name) {
            html += `<div style="color: #6b7280; font-weight: 500;">이름:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(customer.name)}</div>`;
        }
        
        if (customer.phone) {
            html += `<div style="color: #6b7280; font-weight: 500;">전화번호:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(customer.phone)}</div>`;
        }
        
        if (customer.email) {
            html += `<div style="color: #6b7280; font-weight: 500;">이메일:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(customer.email)}</div>`;
        }
        
        if (customer.address) {
            html += `<div style="color: #6b7280; font-weight: 500;">주소:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(customer.address)}${customer.address_detail ? ' ' + escapeHtml(customer.address_detail) : ''}</div>`;
        }
        
        if (customer.birth_date) {
            html += `<div style="color: #6b7280; font-weight: 500;">생년월일:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(customer.birth_date)}</div>`;
        }
        
        if (customer.gender) {
            html += `<div style="color: #6b7280; font-weight: 500;">성별:</div>`;
            const genderText = customer.gender === 'male' ? '남성' : customer.gender === 'female' ? '여성' : '기타';
            html += `<div style="color: #1f2937;">${genderText}</div>`;
        }
        
        html += '</div></div>';
        
        // 상품 정보 섹션 (신청 시점)
        html += '<div>';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">상품정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        // 가입 형태를 상품 정보 섹션 첫 번째 항목으로 추가
        if (additionalInfo.subscription_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">가입 형태:</div>`;
            // 가입 형태 한글 변환
            let subscriptionTypeText = additionalInfo.subscription_type;
            const subscriptionTypeMap = {
                'new': '신규가입',
                'mnp': '번호이동',
                'port': '번호이동', // 하위 호환성
                'change': '기기변경'
            };
            if (subscriptionTypeMap[subscriptionTypeText]) {
                subscriptionTypeText = subscriptionTypeMap[subscriptionTypeText];
            }
            html += `<div style="color: #1f2937;">${escapeHtml(subscriptionTypeText)}</div>`;
        }
        
        if (productSnapshot.provider) {
            html += `<div style="color: #6b7280; font-weight: 500;">통신사:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.provider)}</div>`;
        }
        
        if (productSnapshot.plan_name) {
            html += `<div style="color: #6b7280; font-weight: 500;">요금제명:</div>`;
            html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(productSnapshot.plan_name)}</div>`;
        }
        
        if (productSnapshot.service_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터 속도:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.service_type)}</div>`;
        }
        
        if (productSnapshot.contract_period) {
            html += `<div style="color: #6b7280; font-weight: 500;">약정 기간:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.contract_period)}</div>`;
        }
        
        // 데이터 정보
        if (productSnapshot.data_amount) {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터:</div>`;
            let dataText = productSnapshot.data_amount;
            if (productSnapshot.data_amount === '직접입력' && productSnapshot.data_amount_value) {
                // data_amount_value에 이미 단위가 포함되어 있는지 확인
                const dataValueStr = String(productSnapshot.data_amount_value);
                const unit = productSnapshot.data_unit || 'GB';
                // 끝에 단위가 이미 포함되어 있으면 추가하지 않음
                if (dataValueStr.endsWith('GB') || dataValueStr.endsWith('MB') || dataValueStr.endsWith('TB') || 
                    dataValueStr.endsWith('Mbps') || dataValueStr.endsWith('Gbps') || dataValueStr.endsWith('Kbps')) {
                    dataText = dataValueStr;
                } else {
                    dataText = dataValueStr + unit;
                }
            }
            if (productSnapshot.data_additional && productSnapshot.data_additional !== '없음') {
                if (productSnapshot.data_additional === '직접입력' && productSnapshot.data_additional_value) {
                    dataText += ' + ' + productSnapshot.data_additional_value;
                } else {
                    dataText += ' + ' + productSnapshot.data_additional;
                }
            }
            if (productSnapshot.data_exhausted && productSnapshot.data_exhausted !== '직접입력') {
                dataText += ' + ' + productSnapshot.data_exhausted;
            } else if (productSnapshot.data_exhausted === '직접입력' && productSnapshot.data_exhausted_value) {
                dataText += ' + ' + productSnapshot.data_exhausted_value;
            }
            html += `<div style="color: #1f2937;">${escapeHtml(dataText)}</div>`;
        }
        
        // 통화 정보
        if (productSnapshot.call_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">통화:</div>`;
            let callText = productSnapshot.call_type;
            if (productSnapshot.call_type === '직접입력' && productSnapshot.call_amount) {
                // call_amount에 이미 단위가 포함되어 있는지 확인
                const callAmountStr = String(productSnapshot.call_amount);
                const unit = productSnapshot.call_amount_unit || '분';
                // 끝에 단위가 이미 포함되어 있으면 추가하지 않음
                if (callAmountStr.endsWith('분') || callAmountStr.endsWith('초') || callAmountStr.endsWith('건')) {
                    callText = callAmountStr;
                } else {
                    callText = callAmountStr + unit;
                }
            }
            html += `<div style="color: #1f2937;">${escapeHtml(callText)}</div>`;
        }
        
        // 문자 정보
        if (productSnapshot.sms_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">문자:</div>`;
            let smsText = productSnapshot.sms_type;
            if (productSnapshot.sms_type === '직접입력' && productSnapshot.sms_amount) {
                // sms_amount에 이미 단위가 포함되어 있는지 확인
                const smsAmountStr = String(productSnapshot.sms_amount);
                const unit = productSnapshot.sms_amount_unit || '건';
                // 끝에 단위가 이미 포함되어 있으면 추가하지 않음
                if (smsAmountStr.endsWith('분') || smsAmountStr.endsWith('초') || smsAmountStr.endsWith('건')) {
                    smsText = smsAmountStr;
                } else {
                    smsText = smsAmountStr + unit;
                }
            }
            html += `<div style="color: #1f2937;">${escapeHtml(smsText)}</div>`;
        }
        
        // 가격 정보
        if (productSnapshot.price_main) {
            html += `<div style="color: #6b7280; font-weight: 500;">기본 요금:</div>`;
            html += `<div style="color: #1f2937; font-weight: 600;">월 ${formatNumber(productSnapshot.price_main)}원</div>`;
        }
        
        if (productSnapshot.price_after) {
            html += `<div style="color: #6b7280; font-weight: 500;">할인 후 요금:</div>`;
            html += `<div style="color: #6366f1; font-weight: 600;">월 ${formatNumber(productSnapshot.price_after)}원</div>`;
        }
        
        if (productSnapshot.discount_period) {
            html += `<div style="color: #6b7280; font-weight: 500;">할인 기간:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.discount_period)}</div>`;
        }
        
        // 프로모션 정보
        if (productSnapshot.promotions) {
            let promotions = [];
            try {
                if (typeof productSnapshot.promotions === 'string') {
                    promotions = JSON.parse(productSnapshot.promotions);
                } else if (Array.isArray(productSnapshot.promotions)) {
                    promotions = productSnapshot.promotions;
                }
            } catch(e) {
                // JSON 파싱 실패 시 무시
            }
            
            if (promotions.length > 0) {
                html += `<div style="color: #6b7280; font-weight: 500;">프로모션:</div>`;
                html += `<div style="color: #1f2937;">${promotions.map(p => escapeHtml(p)).join(', ')}</div>`;
            }
        }
        
        html += '</div></div>';
        
        // 혜택 및 유의사항 섹션
        if (productSnapshot.benefits) {
            html += '<div>';
            html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">혜택 및 유의사항</h3>';
            html += '<div style="font-size: 14px; color: #374151; line-height: 1.8;">';
            
            let benefits = null;
            try {
                // benefits가 문자열인 경우 JSON 파싱 시도
                if (typeof productSnapshot.benefits === 'string') {
                    const parsed = JSON.parse(productSnapshot.benefits);
                    if (Array.isArray(parsed)) {
                        benefits = parsed;
                    } else {
                        benefits = [productSnapshot.benefits];
                    }
                } else if (Array.isArray(productSnapshot.benefits)) {
                    benefits = productSnapshot.benefits;
                } else {
                    benefits = [String(productSnapshot.benefits)];
                }
            } catch(e) {
                // JSON 파싱 실패 시 문자열로 처리
                benefits = [String(productSnapshot.benefits)];
            }
            
            if (benefits && benefits.length > 0) {
                html += '<ul style="margin: 0; padding-left: 20px; list-style-type: disc;">';
                benefits.forEach(function(benefit) {
                    const benefitText = String(benefit).trim();
                    if (benefitText) {
                        // 줄바꿈 문자(\n)를 <br> 태그로 변환
                        const formattedText = escapeHtml(benefitText).replace(/\n/g, '<br>');
                        html += `<li style="margin-bottom: 8px;">${formattedText}</li>`;
                    }
                });
                html += '</ul>';
            } else {
                html += '<div style="color: #9ca3af;">추가 정보 없음</div>';
            }
            
            html += '</div></div>';
        }
        
        html += '</div>';
        
        modalContent.innerHTML = html;
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatNumber(num) {
        if (!num) return '0';
        return parseInt(num).toLocaleString('ko-KR');
    }
    
    // 더보기 기능
    const moreBtn = document.getElementById('moreApplicationsBtn');
    const applicationItems = document.querySelectorAll('.application-card');
    let visibleCount = 10;
    const totalApplications = applicationItems.length;
    const loadCount = 10;

    function updateButtonText() {
        if (!moreBtn) return;
        const remaining = totalApplications - visibleCount;
        if (remaining > 0) {
            const showCount = remaining > loadCount ? loadCount : remaining;
            moreBtn.textContent = `더보기 (${showCount}개)`;
        }
    }

    if (moreBtn && totalApplications > 10) {
        updateButtonText();
        
        moreBtn.addEventListener('click', function() {
            const endCount = Math.min(visibleCount + loadCount, totalApplications);
            for (let i = visibleCount; i < endCount; i++) {
                if (applicationItems[i]) {
                    applicationItems[i].style.display = 'block';
                }
            }
            
            visibleCount = endCount;
            
            if (visibleCount >= totalApplications) {
                const moreButtonContainer = document.getElementById('moreButtonContainer');
                if (moreButtonContainer) {
                    moreButtonContainer.style.display = 'none';
                }
            } else {
                updateButtonText();
            }
        });
    } else if (moreBtn && totalApplications <= 10) {
        const moreButtonContainer = document.getElementById('moreButtonContainer');
        if (moreButtonContainer) {
            moreButtonContainer.style.display = 'none';
        }
    }
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>













