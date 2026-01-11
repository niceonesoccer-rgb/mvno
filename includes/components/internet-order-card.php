<?php
/**
 * 인터넷 주문내역 카드 컴포넌트
 * 
 * @param array $internet 주문내역 데이터
 * @param int $user_id 사용자 ID
 */

if (!isset($internet)) {
    echo '<div style="color: red;">주문내역 데이터가 없습니다.</div>';
    return;
}
if (!isset($user_id)) {
    $user_id = 0;
}

// 변수 초기화 및 기본값 설정
$applicationId = htmlspecialchars($internet['application_id'] ?? '');
$orderDate = $internet['order_date'] ?? '';
$formattedDate = '';
if ($orderDate) {
    try {
        $dateObj = new DateTime($orderDate);
        $formattedDate = $dateObj->format('Y.m.d');
    } catch (Exception $e) {
        $formattedDate = $orderDate;
    }
}
$provider = htmlspecialchars($internet['provider'] ?? '');
$speed = htmlspecialchars($internet['speed'] ?? '');
$planName = htmlspecialchars($internet['plan_name'] ?? '');
$price = htmlspecialchars($internet['price'] ?? '');
$orderNumber = htmlspecialchars($internet['order_number'] ?? '');
$appStatus = $internet['application_status'] ?? '';
$statusDisplay = htmlspecialchars($internet['status'] ?? '');

// 경로 설정 파일 포함 (getAssetPath 함수 사용)
require_once __DIR__ . '/../data/path-config.php';

// 통신사 로고 경로 설정
$logoUrl = '';
if (stripos($provider, 'KT skylife') !== false || stripos($provider, 'KTskylife') !== false) {
    $logoUrl = getAssetPath('/assets/images/internets/ktskylife.svg');
} elseif (stripos($provider, 'SKT') !== false || stripos($provider, 'SK broadband') !== false || stripos($provider, 'SK') !== false) {
    $logoUrl = getAssetPath('/assets/images/internets/broadband.svg');
} elseif (stripos($provider, 'KT') !== false) {
    $logoUrl = getAssetPath('/assets/images/internets/kt.svg');
} elseif (stripos($provider, 'LG') !== false || stripos($provider, 'LGU') !== false) {
    $logoUrl = getAssetPath('/assets/images/internets/lgu.svg');
}

$displayProvider = $provider;
if (stripos($provider, 'SKT') !== false && stripos($provider, 'broadband') === false) {
    $displayProvider = 'SKT broadband';
}

// 상태별 배지 색상 설정
$statusBg = '#eef2ff'; // 기본 파란색
$statusColor = '#6366f1';

if (!empty($appStatus)) {
    switch ($appStatus) {
        case 'received':
        case 'pending':
            $statusBg = '#fef3c7'; // 노란색
            $statusColor = '#92400e';
            break;
        case 'processing':
        case 'activating':
            $statusBg = '#e0e7ff'; // 보라색
            $statusColor = '#6366f1';
            break;
        case 'installation_completed':
        case 'activation_completed':
        case 'completed':
            $statusBg = '#d1fae5'; // 초록색
            $statusColor = '#065f46';
            break;
        case 'on_hold':
            $statusBg = '#fef3c7'; // 노란색
            $statusColor = '#92400e';
            break;
        case 'cancelled':
        case 'rejected':
            $statusBg = '#fee2e2'; // 빨간색
            $statusColor = '#991b1b';
            break;
        case 'closed':
        case 'terminated':
            $statusBg = '#f3f4f6'; // 회색
            $statusColor = '#6b7280';
            break;
    }
}

// 판매자 정보 및 리뷰 작성 영역
$productId = $internet['id'] ?? ($internet['product_id'] ?? 0);
$sellerId = null;
$seller = null;
$sellerPhone = '';
$sellerChatUrl = '';
$sellerName = '';

try {
    require_once __DIR__ . '/../data/db-config.php';
    $pdo = getDBConnection();
    if ($pdo && $productId > 0) {
        $sellerStmt = $pdo->prepare("SELECT seller_id FROM products WHERE id = :product_id LIMIT 1");
        $sellerStmt->execute([':product_id' => $productId]);
        $product = $sellerStmt->fetch(PDO::FETCH_ASSOC);
        if ($product && !empty($product['seller_id'])) {
            $sellerId = $product['seller_id'];
            require_once __DIR__ . '/../data/plan-data.php'; // getSellerById 함수 포함
            $seller = getSellerById($sellerId);
            if ($seller) {
                $sellerPhone = $seller['phone'] ?? ($seller['mobile'] ?? '');
                $sellerChatUrl = $seller['chat_consultation_url'] ?? '';
                if (!empty($seller['seller_name'])) {
                    $sellerName = $seller['seller_name'];
                } elseif (!empty($seller['company_name'])) {
                    $sellerName = $seller['company_name'];
                } elseif (!empty($seller['name'])) {
                    $sellerName = $seller['name'];
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Internet Order Card - Error getting seller info: " . $e->getMessage());
}

$sellerPhoneDisplay = $sellerPhone;

// 리뷰 설정 파일 포함
require_once __DIR__ . '/../../includes/data/review-settings.php';

// 리뷰 작성 권한 확인 (관리자 설정 기반)
$canWrite = canWriteReview($appStatus);
$hasReview = false;
$buttonText = '리뷰 작성';
$buttonClass = 'internet-review-write-btn';
$buttonBgColor = '#EF4444';
$buttonHoverColor = '#dc2626';
$buttonDataReviewId = '';

if ($canWrite && $applicationId && $productId) {
    try {
        require_once __DIR__ . '/../data/db-config.php';
        $pdo = getDBConnection();
        if ($pdo) {
            $reviewStmt = $pdo->prepare("
                SELECT id 
                FROM product_reviews 
                WHERE application_id = :application_id 
                AND product_id = :product_id 
                AND user_id = :user_id 
                AND product_type = 'internet'
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
$buttonClass = $hasReview ? 'internet-review-edit-btn' : 'internet-review-write-btn';
$buttonBgColor = $hasReview ? '#6b7280' : '#EF4444';
$buttonHoverColor = $hasReview ? '#4b5563' : '#dc2626';
?>

<div class="internet-item application-card" 
    data-application-id="<?php echo $applicationId; ?>"
    style="padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"
    onmouseover="this.style.borderColor='#6366f1'; this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.15)'"
    onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'">
    
    <!-- 상단: 통신사 로고 및 정보 -->
    <div style="margin-bottom: 16px; position: relative;">
        <!-- 주문일자 (상단 오른쪽) -->
        <?php if ($formattedDate): ?>
            <div style="position: absolute; top: 0; right: 0; font-size: 13px; color: #6b7280;">
                <?php echo $formattedDate; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($logoUrl): ?>
            <div style="margin-bottom: 8px;">
                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($displayProvider); ?>" style="height: 32px; object-fit: contain;">
            </div>
        <?php endif; ?>
        <div style="font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 12px; <?php echo $logoUrl ? 'margin-top: 4px;' : ''; ?>">
            <?php echo htmlspecialchars($displayProvider ?: '인터넷'); ?>
        </div>
        
        <div style="font-size: 16px; color: #374151; margin-bottom: 8px;">
            <?php echo $speed; ?> <?php echo $planName; ?>
        </div>
        
        <div style="font-size: 16px; color: #1f2937; font-weight: 600;">
            <?php 
            $displayPrice = $price;
            if (empty($displayPrice) || $displayPrice === '월 0원') {
                $displayPrice = '월 요금 정보 없음';
            }
            echo htmlspecialchars($displayPrice); 
            ?>
        </div>
    </div>
    
    <!-- 하단: 주문번호 및 진행상황 -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
            <?php if (!empty($orderNumber)): ?>
                <span style="color: #6b7280;">주문번호</span>
                <span style="color: #374151; font-weight: 600;"><?php echo $orderNumber; ?></span>
            <?php elseif (!empty($applicationId)): ?>
                <span style="color: #6b7280;">신청번호</span>
                <span style="color: #374151; font-weight: 600;">#<?php echo $applicationId; ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($statusDisplay)): ?>
            <div style="display: inline-flex; align-items: center; padding: 6px 12px; background: <?php echo $statusBg; ?>; border-radius: 6px;">
                <span style="font-size: 13px; color: <?php echo $statusColor; ?>; font-weight: 600;"><?php echo $statusDisplay; ?></span>
            </div>
        <?php else: ?>
            <div style="display: inline-flex; align-items: center; padding: 6px 12px; background: #f3f4f6; border-radius: 6px;">
                <span style="font-size: 13px; color: #6b7280; font-weight: 600;">상태 없음</span>
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
                
                <!-- 리뷰 작성 버튼 (설치완료/종료 상태일 때만 표시) -->
                <?php if ($canWrite): ?>
                <div style="display: flex; align-items: center; justify-content: center;" onclick="event.stopPropagation();">
                    <button 
                        class="<?php echo $buttonClass; ?>" 
                        data-application-id="<?php echo $applicationId; ?>"
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

