<?php
/**
 * 알뜰폰 주문내역 카드 컴포넌트
 * 
 * @param array $app 주문내역 데이터
 * @param int $user_id 사용자 ID
 */
if (!isset($app)) {
    $app = [];
}
if (!isset($user_id)) {
    $user_id = 0;
}

// getUserMvnoApplications는 'id' 키에 product_id를 저장하고 'product_id' 키도 추가함
$productId = $app['product_id'] ?? ($app['id'] ?? 0);
$applicationId = $app['application_id'] ?? '';

// 판매자 정보 가져오기 (모든 상태에서)
// 1. additional_info에서 seller_snapshot 확인 (주문 시점 정보)
$sellerSnapshot = null;
$sellerId = isset($app['seller_id']) && $app['seller_id'] > 0 ? (int)$app['seller_id'] : null;
$seller = null;
$sellerPhone = '';

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
    $seller = getSellerById($sellerId);
} else {
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
            }
        }
    } catch (Exception $e) {
        error_log("Error getting seller info: " . $e->getMessage());
    }
}

// 3. 판매자 정보 우선순위: 실시간 정보 > 스냅샷 정보
$finalSeller = $seller ?: $sellerSnapshot;

$sellerPhone = '';
if ($finalSeller) {
    $sellerPhone = $finalSeller['phone'] ?? $finalSeller['mobile'] ?? '';
}
$sellerChatUrl = $finalSeller ? ($finalSeller['chat_consultation_url'] ?? '') : '';

// 판매자명 가져오기 (seller_name > company_name > name 우선순위)
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

// 판매자명과 전화번호 결합 (두 칸 띄고)
$sellerPhoneDisplay = $sellerPhone;

// 리뷰 설정 파일 포함
require_once __DIR__ . '/../../includes/data/review-settings.php';

// 리뷰 작성 권한 확인 (관리자 설정 기반)
$appStatus = $app['application_status'] ?? '';
$canWrite = canWriteReview($appStatus);

$hasReview = false;
$buttonText = '리뷰 작성';
$buttonClass = 'mvno-review-write-btn';
$buttonBgColor = '#EF4444';
$buttonHoverColor = '#dc2626';
$buttonDataReviewId = '';

if ($canWrite) {
    $latestReviewId = null;
    if ($applicationId && $productId) {
        try {
            $pdo = getDBConnection();
            if ($pdo) {
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
    
    $buttonText = $hasReview ? '리뷰 수정' : '리뷰 작성';
    $buttonClass = $hasReview ? 'mvno-review-edit-btn' : 'mvno-review-write-btn';
    $buttonBgColor = $hasReview ? '#6b7280' : '#EF4444';
    $buttonHoverColor = $hasReview ? '#4b5563' : '#dc2626';
    
    if ($latestReviewId) {
        $buttonDataReviewId = ' data-review-id="' . htmlspecialchars($latestReviewId) . '"';
    }
}

// 주문일자 포맷팅
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

// 상태별 배지 색상 설정
$appStatus = $app['application_status'] ?? '';
$statusBg = '#eef2ff';
$statusColor = '#6366f1';

if (!empty($appStatus)) {
    switch ($appStatus) {
        case 'pending':
            $statusBg = '#fef3c7';
            $statusColor = '#92400e';
            break;
        case 'processing':
            $statusBg = '#e0e7ff';
            $statusColor = '#6366f1';
            break;
        case 'completed':
            $statusBg = '#d1fae5';
            $statusColor = '#065f46';
            break;
        case 'cancelled':
        case 'rejected':
            $statusBg = '#fee2e2';
            $statusColor = '#991b1b';
            break;
        case 'closed':
            $statusBg = '#f3f4f6';
            $statusColor = '#6b7280';
            break;
    }
}

// 버튼 개수 계산
$buttonCount = 0;
if ($sellerPhone) $buttonCount++;
if ($sellerChatUrl) $buttonCount++;
if ($canWrite) $buttonCount++;

// 그리드 컬럼 수 결정
$gridCols = '1fr';
if ($buttonCount === 2) {
    $gridCols = '1fr 1fr';
} elseif ($buttonCount === 3) {
    $gridCols = '1fr 1fr 1fr';
}
?>

<div class="order-item-wrapper">
    <div class="plan-item application-card" 
         data-application-id="<?php echo htmlspecialchars($applicationId); ?>"
         style="padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"
         onmouseover="this.style.borderColor='#6366f1'; this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.15)'"
         onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'">
        
        <!-- 상단: 요금제 정보 -->
        <div style="position: relative; margin-bottom: 16px;">
            <!-- 주문일자 (상단 오른쪽) -->
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
            <?php if (!empty($app['status'])): ?>
                <div style="display: inline-flex; align-items: center; padding: 6px 12px; background: <?php echo $statusBg; ?>; border-radius: 6px;">
                    <span style="font-size: 13px; color: <?php echo $statusColor; ?>; font-weight: 600;"><?php echo htmlspecialchars($app['status']); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 판매자 정보 및 리뷰 작성 영역 -->
        <?php if ($sellerPhone || $sellerChatUrl || $canWrite): ?>
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                <div class="review-section-layout" style="display: grid; grid-template-columns: <?php echo $gridCols; ?>; gap: 12px;">
                    <!-- 전화번호 버튼 -->
                    <?php if ($sellerPhone): 
                        $phoneNumberOnly = preg_replace('/[^0-9]/', '', $sellerPhone);
                    ?>
                        <div style="display: flex; align-items: center; justify-content: center;" onclick="event.stopPropagation();">
                            <button class="phone-inquiry-pc" 
                                    disabled
                                    style="width: 100%; padding: 12px 16px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: not-allowed;">
                                전화문의
                            </button>
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
                    
                    <!-- 채팅상담 버튼 -->
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
                    
                    <!-- 리뷰 작성 버튼 -->
                    <?php if ($canWrite): ?>
                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;" onclick="event.stopPropagation();">
                            <button 
                                class="<?php echo $buttonClass; ?>" 
                                data-application-id="<?php echo htmlspecialchars($applicationId); ?>"
                                data-product-id="<?php echo htmlspecialchars($productId); ?>"
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
</div>

