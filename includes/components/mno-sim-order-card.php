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
$applicationId = isset($app['application_id']) && $app['application_id'] !== null && $app['application_id'] !== '' ? (int)$app['application_id'] : '';
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
// product_id가 없으면 application_id를 사용하지 말고 0으로 처리 (데이터 오류)
$productId = isset($app['product_id']) && $app['product_id'] > 0 ? (int)$app['product_id'] : 0;
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

if ($canWrite) {
    $latestReviewId = null;
    if ($applicationId && $productId) {
        try {
            $pdo = getDBConnection();
            if ($pdo) {
                // application_id를 정수로 변환 (DB 비교 시 타입 일치를 위해)
                $applicationIdInt = is_numeric($applicationId) ? (int)$applicationId : 0;
                
                // 디버깅: 조회 조건 확인
                error_log("MNO-SIM 리뷰 조회 - application_id=" . var_export($applicationId, true) . " (type: " . gettype($applicationId) . "), applicationIdInt=$applicationIdInt, product_id=$productId (type: " . gettype($productId) . "), user_id=$user_id");
                
                // MVNO와 동일한 방식으로 단순화 (application_id 정수로 변환하여 비교)
                $reviewStmt = $pdo->prepare("
                    SELECT id 
                    FROM product_reviews 
                    WHERE application_id = :application_id 
                    AND product_id = :product_id 
                    AND user_id = :user_id 
                    AND product_type = 'mno-sim'
                    AND status != 'deleted'
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                // application_id를 명시적으로 정수로 변환하여 바인딩
                $reviewStmt->bindValue(':application_id', $applicationIdInt, PDO::PARAM_INT);
                $reviewStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
                $reviewStmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $reviewStmt->execute();
                $reviewResult = $reviewStmt->fetch(PDO::FETCH_ASSOC);
                
                // 디버깅: 조회 결과 확인
                if ($reviewResult) {
                    $latestReviewId = $reviewResult['id'];
                    $hasReview = true;
                    error_log("MNO-SIM 리뷰 조회 성공 - review_id=$latestReviewId");
                } else {
                    error_log("MNO-SIM 리뷰 조회 실패 - 리뷰를 찾을 수 없음");
                    
                    // 디버깅: 실제 DB에 존재하는 리뷰 확인 (조건 완화)
                    $debugStmt = $pdo->prepare("
                        SELECT id, application_id, product_id, user_id, product_type, status, created_at
                        FROM product_reviews 
                        WHERE (application_id = :application_id OR application_id = CAST(:application_id AS UNSIGNED))
                        AND product_id = :product_id 
                        AND user_id = :user_id 
                        AND product_type IN ('mno-sim', 'mno')
                        ORDER BY created_at DESC
                        LIMIT 5
                    ");
                    $debugStmt->execute([
                        ':application_id' => $applicationIdInt,
                        ':product_id' => $productId,
                        ':user_id' => $user_id
                    ]);
                    $debugResults = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("MNO-SIM 리뷰 디버깅 - 찾은 리뷰 개수: " . count($debugResults));
                    if (!empty($debugResults)) {
                        foreach ($debugResults as $idx => $debugRow) {
                            error_log("MNO-SIM 리뷰 디버깅 [$idx]: id={$debugRow['id']}, application_id={$debugRow['application_id']} (type: " . gettype($debugRow['application_id']) . "), product_id={$debugRow['product_id']}, product_type={$debugRow['product_type']}, status={$debugRow['status']}, created_at={$debugRow['created_at']}");
                        }
                    } else {
                        error_log("MNO-SIM 리뷰 디버깅 - 조건 완화해도 리뷰 없음");
                    }
                }
            }
        } catch (Exception $e) {
            error_log("MNO-SIM 리뷰 조회 오류: " . $e->getMessage());
        }
    } else {
        error_log("MNO-SIM 리뷰 조회 조건 실패 - applicationId=" . var_export($applicationId, true) . ", productId=$productId, canWrite=" . ($canWrite ? 'true' : 'false'));
    }
    
    $buttonText = $hasReview ? '리뷰 수정' : '리뷰 작성';
    $buttonClass = $hasReview ? 'mno-sim-review-edit-btn' : 'mno-sim-review-write-btn';
    $buttonBgColor = $hasReview ? '#6b7280' : '#EF4444';
    $buttonHoverColor = $hasReview ? '#4b5563' : '#dc2626';
    
    if ($latestReviewId) {
        $buttonDataReviewId = ' data-review-id="' . htmlspecialchars($latestReviewId) . '"';
    }
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
                            전화문의
                        </button>
                        <!-- 모바일 버전: 전화번호 버튼 (클릭 시 전화 연결) -->
                        <a href="tel:<?php echo htmlspecialchars($phoneNumberOnly); ?>" 
                           class="phone-inquiry-mobile"
                           onclick="event.stopPropagation();"
                           style="display: none; width: 100%; align-items: center; justify-content: center; padding: 12px 16px; background: #10b981; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; text-decoration: none; cursor: pointer; transition: background 0.2s;"
                           onmouseover="this.style.background='#059669'"
                           onmouseout="this.style.background='#10b981'">
                            전화문의
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

