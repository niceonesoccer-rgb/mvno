<?php
/**
 * 통신사폰 주문내역 카드 컴포넌트
 * 
 * @param array $phone 주문내역 데이터
 * @param int $user_id 사용자 ID
 */
if (!isset($phone)) {
    $phone = [];
}
if (!isset($user_id)) {
    $user_id = 0;
}

$productId = $phone['product_id'] ?? 0;
$applicationId = $phone['application_id'] ?? '';

// 판매자 정보 가져오기
// 1. additional_info에서 seller_snapshot 확인 (주문 시점 정보)
$sellerSnapshot = null;
$sellerId = isset($phone['seller_id']) && $phone['seller_id'] > 0 ? (int)$phone['seller_id'] : null;
$seller = null;
$sellerPhone = '';

if (!empty($phone['additional_info'])) {
    try {
        $additionalInfo = json_decode($phone['additional_info'], true);
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
$appStatus = $phone['application_status'] ?? '';
$canWrite = canWriteReview($appStatus);

$hasReview = false;
$buttonText = '리뷰 작성';
$buttonClass = 'mno-review-write-btn';
$buttonBgColor = '#EF4444';
$buttonHoverColor = '#dc2626';
$buttonDataReviewId = '';

if ($canWrite && $applicationId && $productId) {
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $reviewStmt = $pdo->prepare("
                SELECT id 
                FROM product_reviews 
                WHERE application_id = :application_id 
                AND product_id = :product_id 
                AND user_id = :user_id 
                AND product_type = 'mno'
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
                $hasReview = true;
                $latestReviewId = $reviewResult['id'];
                $buttonDataReviewId = ' data-review-id="' . htmlspecialchars($latestReviewId) . '"';
            }
        }
    } catch (Exception $e) {
        error_log("Error checking review: " . $e->getMessage());
    }
}

$buttonText = $hasReview ? '리뷰 수정' : '리뷰 작성';
$buttonClass = $hasReview ? 'mno-review-edit-btn' : 'mno-review-write-btn';
$buttonBgColor = $hasReview ? '#6b7280' : '#EF4444';
$buttonHoverColor = $hasReview ? '#4b5563' : '#dc2626';

// 주문일자 포맷팅
$orderDate = $phone['order_date'] ?? '';
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
$appStatus = $phone['application_status'] ?? '';
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

$gridCols = '1fr';
if ($buttonCount === 2) {
    $gridCols = '1fr 1fr';
} elseif ($buttonCount === 3) {
    $gridCols = '1fr 1fr 1fr';
}
?>

<div class="order-item-wrapper">
    <div class="phone-item application-card" 
         data-phone-id="<?php echo $phone['id']; ?>" 
         data-application-id="<?php echo htmlspecialchars($applicationId); ?>"
         style="padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"
         onmouseover="this.style.borderColor='#6366f1'; this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.15)'"
         onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'"
         onclick="openOrderModal(<?php echo htmlspecialchars($applicationId); ?>)">
        
        <!-- 상단: 단말기명 | 신청일 -->
        <div style="position: relative; margin-bottom: 12px;">
            <?php if ($formattedDate): ?>
                <div style="position: absolute; top: 0; right: 0; font-size: 13px; color: #6b7280;">
                    <?php echo htmlspecialchars($formattedDate); ?>
                </div>
            <?php endif; ?>
            
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 18px; font-weight: 600; color: #1f2937;">
                    <?php echo htmlspecialchars($phone['device_name'] ?? '단말기'); ?>
                </div>
            </div>
        </div>
        
        <!-- 중간: 출고가, 용량, 색상 -->
        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; font-size: 14px; color: #6b7280;">
            <?php if (!empty($phone['device_price'])): ?>
                <span><?php echo htmlspecialchars($phone['device_price']); ?></span>
            <?php endif; ?>
            <?php if (!empty($phone['device_storage'])): ?>
                <?php if (!empty($phone['device_price'])): ?><span>,</span><?php endif; ?>
                <span><?php echo htmlspecialchars($phone['device_storage']); ?></span>
            <?php endif; ?>
            <?php if (!empty($phone['device_color'])): ?>
                <?php if (!empty($phone['device_price']) || !empty($phone['device_storage'])): ?><span>,</span><?php endif; ?>
                <span><?php echo htmlspecialchars($phone['device_color']); ?></span>
            <?php endif; ?>
        </div>
        
        <!-- 중간: 할인 정보 -->
        <?php if (!empty($phone['discount_info'])): ?>
            <div style="font-size: 14px; color: #374151; margin-bottom: 12px;">
                <?php echo htmlspecialchars($phone['discount_info']); ?>
            </div>
        <?php endif; ?>
        
        <!-- 하단: 주문번호 및 진행상황 -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
            <div style="font-size: 13px;">
                <?php if (!empty($phone['order_number'])): ?>
                    <span style="color: #6b7280;">주문번호 </span>
                    <span style="color: #374151; font-weight: 600;"><?php echo htmlspecialchars($phone['order_number']); ?></span>
                <?php elseif (!empty($phone['application_id'])): ?>
                    <span style="color: #6b7280;">주문번호 </span>
                    <span style="color: #374151; font-weight: 600;">#<?php echo htmlspecialchars($phone['application_id']); ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($phone['status'])): ?>
                <div style="display: inline-flex; align-items: center; padding: 6px 12px; background: <?php echo $statusBg; ?>; border-radius: 6px;">
                    <span style="font-size: 13px; color: <?php echo $statusColor; ?>; font-weight: 600;"><?php echo htmlspecialchars($phone['status']); ?></span>
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
                        <div style="display: flex; align-items: center; justify-content: center;" onclick="event.stopPropagation();">
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

