<?php
/**
 * 통신사단독유심 주문내역 카드 컴포넌트
 * 
 * @param array $app 주문내역 데이터
 * @param int $user_id 사용자 ID
 */

if (!isset($app)) {
    echo '<div style="color: red;">주문내역 데이터가 없습니다.</div>';
    return;
}
if (!isset($user_id)) {
    $user_id = 0;
}

// 변수 초기화 및 기본값 설정
$applicationId = htmlspecialchars($app['application_id'] ?? '');
$orderDate = $app['order_date'] ?? '';
$formattedDate = '';
if ($orderDate) {
    try {
        $dateObj = new DateTime($orderDate);
        $formattedDate = $dateObj->format('Y.m.d');
    } catch (Exception $e) {
        $formattedDate = $orderDate;
    }
}
$title = htmlspecialchars($app['title'] ?? ($app['plan_name'] ?? '요금제 정보 없음'));
$dataMain = htmlspecialchars($app['data_main'] ?? '');
$priceMain = htmlspecialchars($app['price_main'] ?? '');
$priceAfter = htmlspecialchars($app['price_after'] ?? '');
$planMaintenancePeriod = htmlspecialchars($app['plan_maintenance_period'] ?? '');
$simChangeRestrictionPeriod = htmlspecialchars($app['sim_change_restriction_period'] ?? '');
$orderNumber = htmlspecialchars($app['order_number'] ?? '');
$appStatus = $app['application_status'] ?? '';
$statusDisplay = htmlspecialchars($app['status'] ?? '');

// 상태별 배지 색상 설정
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

// 판매자 정보 및 리뷰 작성 영역
$productId = $app['product_id'] ?? ($app['id'] ?? 0);
// 1. additional_info에서 seller_snapshot 확인 (주문 시점 정보)
$sellerSnapshot = null;
$sellerId = isset($app['seller_id']) && $app['seller_id'] > 0 ? (int)$app['seller_id'] : null;
$seller = null;
$sellerPhone = '';
$sellerChatUrl = '';
$sellerName = '';

if (!empty($app['additional_info'])) {
    try {
        $additionalInfo = json_decode($app['additional_info'], true);
        if (is_array($additionalInfo) && isset($additionalInfo['seller_snapshot'])) {
            $sellerSnapshot = $additionalInfo['seller_snapshot'];
        }
    } catch (Exception $e) {
        error_log("Error parsing additional_info: " . $e->getMessage());
    }
}

// 2. 실시간 판매자 정보 조회 시도
if ($sellerId) {
    require_once __DIR__ . '/../data/product-functions.php';
    require_once __DIR__ . '/../data/plan-data.php';
    $seller = getSellerById($sellerId);
} else {
    // seller_id가 없으면 product_id로 다시 시도
    try {
        require_once __DIR__ . '/../data/db-config.php';
        $pdo = getDBConnection();
        if ($pdo && $productId > 0) {
            $sellerStmt = $pdo->prepare("SELECT seller_id FROM products WHERE id = :product_id LIMIT 1");
            $sellerStmt->execute([':product_id' => $productId]);
            $product = $sellerStmt->fetch(PDO::FETCH_ASSOC);
            if ($product && !empty($product['seller_id'])) {
                $sellerId = $product['seller_id'];
                require_once __DIR__ . '/../data/product-functions.php';
                require_once __DIR__ . '/../data/plan-data.php';
                $seller = getSellerById($sellerId);
            }
        }
    } catch (Exception $e) {
        error_log("MNO SIM Order Card - Error getting seller info: " . $e->getMessage());
    }
}

// 3. 판매자 정보 우선순위: 실시간 정보 > 스냅샷 정보
$finalSeller = $seller ?: $sellerSnapshot;

$sellerPhone = '';
if ($finalSeller) {
    $sellerPhone = $finalSeller['phone'] ?? $finalSeller['mobile'] ?? '';
}
$sellerChatUrl = $finalSeller ? ($finalSeller['chat_consultation_url'] ?? '') : '';

// 판매자명 가져오기
$sellerName = '';
if ($finalSeller) {
    if (!empty($finalSeller['seller_name'])) {
        $sellerName = $finalSeller['seller_name'];
    } elseif (!empty($finalSeller['company_name'])) {
        $sellerName = $finalSeller['company_name'];
    } elseif (!empty($finalSeller['name'])) {
        $sellerName = $finalSeller['name'];
    }
}

// 탈퇴한 판매자 표시 (스냅샷만 있고 실시간 정보가 없는 경우)
$isSellerWithdrawn = ($sellerSnapshot && !$seller);

$sellerPhoneDisplay = $sellerPhone;
if ($sellerName && $sellerPhone) {
    $sellerPhoneDisplay = $sellerName . '  ' . $sellerPhone;
}

// 리뷰 설정 파일 포함
require_once __DIR__ . '/../../includes/data/review-settings.php';

// 리뷰 작성 권한 확인 (관리자 설정 기반)
$canWrite = canWriteReview($appStatus);
$hasReview = false;
$buttonText = '리뷰 작성';
$buttonClass = 'mno-sim-review-write-btn';
$buttonBgColor = '#EF4444';
$buttonHoverColor = '#dc2626';
$buttonDataReviewId = '';

if ($canWrite && $applicationId && $productId && $user_id) {
    try {
        require_once __DIR__ . '/../data/db-config.php';
        $pdo = getDBConnection();
        if ($pdo) {
            // application_id 컬럼 존재 여부 확인
            $hasApplicationId = false;
            try {
                $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'application_id'");
                $hasApplicationId = $checkStmt->rowCount() > 0;
            } catch (PDOException $e) {
                error_log("Error checking application_id column: " . $e->getMessage());
            }
            
            // product_type에 'mno-sim'이 있는지 확인 (없으면 'mno'로 대체)
            $productTypeForQuery = 'mno-sim';
            try {
                $typeCheckStmt = $pdo->query("SHOW COLUMNS FROM product_reviews WHERE Field = 'product_type'");
                $typeColumn = $typeCheckStmt->fetch(PDO::FETCH_ASSOC);
                if ($typeColumn && strpos($typeColumn['Type'], 'mno-sim') === false) {
                    $productTypeForQuery = 'mno';
                    error_log("Warning: product_type ENUM에 'mno-sim'이 없어 'mno'로 조회합니다.");
                }
            } catch (PDOException $e) {
                error_log("Error checking product_type: " . $e->getMessage());
                $productTypeForQuery = 'mno';
            }
            
            if ($hasApplicationId && !empty($applicationId)) {
                $reviewStmt = $pdo->prepare("
                    SELECT id 
                    FROM product_reviews 
                    WHERE application_id = :application_id 
                    AND product_id = :product_id 
                    AND user_id = :user_id 
                    AND product_type = :product_type
                    AND status != 'deleted'
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $reviewStmt->execute([
                    ':application_id' => $applicationId,
                    ':product_id' => $productId,
                    ':user_id' => $user_id,
                    ':product_type' => $productTypeForQuery
                ]);
            } else {
                $reviewStmt = $pdo->prepare("
                    SELECT id 
                    FROM product_reviews 
                    WHERE product_id = :product_id 
                    AND user_id = :user_id 
                    AND product_type = :product_type
                    AND status != 'deleted'
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $reviewStmt->execute([
                    ':product_id' => $productId,
                    ':user_id' => $user_id,
                    ':product_type' => $productTypeForQuery
                ]);
            }
            
            $reviewResult = $reviewStmt->fetch(PDO::FETCH_ASSOC);
            if ($reviewResult) {
                $latestReviewId = $reviewResult['id'];
                $hasReview = true;
                $buttonDataReviewId = ' data-review-id="' . htmlspecialchars($latestReviewId) . '"';
            }
        }
    } catch (Exception $e) {
        error_log("MNO SIM Order Card - Error checking review: " . $e->getMessage());
    }
    $buttonText = $hasReview ? '리뷰 수정' : '리뷰 작성';
    $buttonClass = $hasReview ? 'mno-sim-review-edit-btn' : 'mno-sim-review-write-btn';
    $buttonBgColor = $hasReview ? '#6b7280' : '#EF4444';
    $buttonHoverColor = $hasReview ? '#4b5563' : '#dc2626';
}
?>

<div class="plan-item application-card" 
    data-application-id="<?php echo $applicationId; ?>"
    style="padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"
    onmouseover="this.style.borderColor='#6366f1'; this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.15)'"
    onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'"
    onclick="openModal(<?php echo htmlspecialchars($applicationId); ?>)">
    
    <!-- 상단: 요금제 정보 -->
    <div style="position: relative; margin-bottom: 16px;">
        <!-- 주문일자 (상단 오른쪽) -->
        <?php if ($formattedDate): ?>
            <div style="position: absolute; top: 0; right: 0; font-size: 13px; color: #6b7280;">
                <?php echo $formattedDate; ?>
            </div>
        <?php endif; ?>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="flex: 1;">
                <div style="font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 6px; line-height: 1.3;">
                    <?php echo $title; ?>
                </div>
                <?php if (!empty($dataMain)): ?>
                    <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;">
                        <?php echo $dataMain; ?>
                    </div>
                <?php endif; ?>
                <div style="display: flex; align-items: baseline; gap: 12px; flex-wrap: wrap;">
                    <?php if (!empty($priceMain)): ?>
                        <div style="font-size: 18px; color: #1f2937; font-weight: 700;">
                            <?php echo $priceMain; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($priceAfter)): ?>
                        <div style="font-size: 14px; color: #6b7280;">
                            <?php echo $priceAfter; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 중간: 요금제 유지기간 및 유심기변 불가기간 -->
    <div style="display: flex; gap: 16px; flex-wrap: wrap; padding: 12px 0; margin-bottom: 12px;">
        <?php if (!empty($planMaintenancePeriod)): ?>
            <div style="display: flex; align-items: center; gap: 6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #6366f1; flex-shrink: 0;">
                    <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span style="font-size: 13px; color: #6b7280;">요금제 유지기간:</span>
                <span style="font-size: 13px; color: #374151; font-weight: 600;"><?php echo $planMaintenancePeriod; ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($simChangeRestrictionPeriod)): ?>
            <div style="display: flex; align-items: center; gap: 6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #8b5cf6; flex-shrink: 0;">
                    <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span style="font-size: 13px; color: #6b7280;">유심기변 불가기간:</span>
                <span style="font-size: 13px; color: #8b5cf6; font-weight: 600;"><?php echo $simChangeRestrictionPeriod; ?></span>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 하단: 주문번호 및 진행상황 -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
        <div style="display: flex; gap: 16px; flex-wrap: wrap; font-size: 13px;">
            <?php if (!empty($orderNumber)): ?>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="color: #6b7280;">주문번호</span>
                    <span style="color: #374151; font-weight: 600;"><?php echo $orderNumber; ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($statusDisplay)): ?>
            <div style="display: inline-flex; align-items: center; padding: 6px 12px; background: <?php echo $statusBg; ?>; border-radius: 6px;">
                <span style="font-size: 13px; color: <?php echo $statusColor; ?>; font-weight: 600;"><?php echo $statusDisplay; ?></span>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 판매자 정보 및 리뷰 작성 영역 -->
    <?php if ($sellerPhone || $sellerChatUrl || $canWrite): ?>
        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
            <?php
            $buttonCount = 0;
            if ($sellerPhone) $buttonCount++;
            if ($sellerChatUrl) $buttonCount++;
            if ($canWrite) $buttonCount++;
            
            $gridCols = '1fr';
            if ($buttonCount === 2) {
                $gridCols = '1fr 1fr';
            } elseif ($buttonCount === 3) {
                $gridCols = '1fr 1fr 1fr';
            }
            ?>
            <div class="review-section-layout" style="display: grid; grid-template-columns: <?php echo $gridCols; ?>; gap: 12px;">
                <!-- 전화번호 버튼 (모든 상태에서 표시) -->
                <?php if ($sellerPhone): 
                    $phoneNumberOnly = preg_replace('/[^0-9]/', '', $sellerPhone);
                ?>
                    <div style="display: flex; align-items: center; justify-content: center;" onclick="event.stopPropagation();">
                        <!-- PC 버전: 전화번호 버튼 (클릭 불가) -->
                        <button class="phone-inquiry-pc" 
                                disabled
                                style="width: 100%; padding: 12px 16px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: not-allowed;">
                            전화: <?php echo htmlspecialchars($sellerPhoneDisplay); ?>
                        </button>
                        <!-- 모바일 버전: 전화번호 버튼 (클릭 시 전화 연결) -->
                        <a href="tel:<?php echo htmlspecialchars($phoneNumberOnly); ?>" 
                           class="phone-inquiry-mobile"
                           onclick="event.stopPropagation();"
                           style="display: none; width: 100%; align-items: center; justify-content: center; padding: 12px 16px; background: #10b981; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; text-decoration: none; cursor: pointer; transition: background 0.2s;"
                           onmouseover="this.style.background='#059669'"
                           onmouseout="this.style.background='#10b981'">
                            전화: <?php echo htmlspecialchars($sellerPhoneDisplay); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- 채팅상담 버튼 (채팅상담 URL이 있을 때 표시) -->
                <?php if ($sellerChatUrl): ?>
                <div style="display: flex; align-items: center; justify-content: center;" onclick="event.stopPropagation();">
                    <a href="<?php echo htmlspecialchars($sellerChatUrl); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       style="width: 100%; display: flex; align-items: center; justify-content: center; padding: 12px 16px; background: #FEE500; color: #000000; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; text-decoration: none; cursor: pointer; transition: background 0.2s;"
                       onmouseover="this.style.background='#FDD835'"
                       onmouseout="this.style.background='#FEE500'">
                        채팅상담
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- 리뷰 작성 버튼 (개통완료/종료 상태일 때만 표시) -->
                <?php if ($canWrite): ?>
                <div style="display: flex; align-items: center; justify-content: center; gap: 8px;" onclick="event.stopPropagation();">
                    <button 
                        class="<?php echo $buttonClass; ?>" 
                        data-application-id="<?php echo $applicationId; ?>"
                        data-product-id="<?php echo htmlspecialchars($productId); ?>"
                        data-product-type="mno-sim"
                        data-has-review="<?php echo $hasReview ? '1' : '0'; ?>"
                        <?php echo $buttonDataReviewId; ?>
                        style="width: 100%; padding: 12px 16px; background: <?php echo $buttonBgColor; ?>; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.2s;"
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

