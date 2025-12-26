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
require_once '../includes/data/plan-data.php';

// DB에서 실제 신청 내역 가져오기
$applications = getUserMnoSimApplications($user_id);

// 헤더 포함
include '../includes/header.php';
// 리뷰 모달 포함
include '../includes/components/mvno-review-modal.php';
?>

<style>
/* 전화번호 버튼 반응형 스타일 */
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
</style>

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
                        <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 통신사단독유심</h2>
                    </div>
                    <p style="font-size: 14px; color: #6b7280; margin: 0; margin-left: 36px;">카드를 클릭하면 신청 정보를 확인할 수 있습니다.</p>
                </div>
                
                <!-- 신청한 통신사단독유심 목록 -->
                <div style="margin-bottom: 32px;">
                    <?php if (empty($applications)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: #6b7280;">
                            신청한 통신사단독유심이 없습니다.
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;" id="applicationsContainer">
                            <?php foreach ($applications as $index => $app): ?>
                                <div class="plan-item application-card" 
                                     data-index="<?php echo $index; ?>"
                                     data-application-id="<?php echo htmlspecialchars($app['application_id'] ?? ''); ?>"
                                     style="<?php echo $index >= 10 ? 'display: none;' : ''; ?> padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"
                                     onmouseover="this.style.borderColor='#6366f1'; this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.15)'"
                                     onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'"
                                     onclick="openModal(<?php echo htmlspecialchars($app['application_id'] ?? ''); ?>)">
                                    
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
                                                    <?php echo htmlspecialchars($app['title'] ?? ($app['plan_name'] ?? '요금제 정보 없음')); ?>
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
                                    
                                    <!-- 중간: 요금제 유지기간 및 유심기변 불가기간 -->
                                    <div style="display: flex; gap: 16px; flex-wrap: wrap; padding: 12px 0; margin-bottom: 12px;">
                                        <?php if (!empty($app['plan_maintenance_period'])): ?>
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #6366f1; flex-shrink: 0;">
                                                    <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span style="font-size: 13px; color: #6b7280;">요금제 유지기간:</span>
                                                <span style="font-size: 13px; color: #374151; font-weight: 600;"><?php echo htmlspecialchars($app['plan_maintenance_period']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($app['sim_change_restriction_period'])): ?>
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #8b5cf6; flex-shrink: 0;">
                                                    <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span style="font-size: 13px; color: #6b7280;">유심기변 불가기간:</span>
                                                <span style="font-size: 13px; color: #8b5cf6; font-weight: 600;"><?php echo htmlspecialchars($app['sim_change_restriction_period']); ?></span>
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
                                    $productId = $app['product_id'] ?? ($app['id'] ?? 0);
                                    $applicationId = $app['application_id'] ?? '';
                                    
                                    // 판매자 정보 가져오기
                                    $sellerId = isset($app['seller_id']) && $app['seller_id'] > 0 ? (int)$app['seller_id'] : null;
                                    $seller = null;
                                    $sellerPhone = '';
                                    
                                    if ($sellerId) {
                                        // 판매자 정보 가져오기
                                        $seller = getUserById($sellerId);
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
                                                    $seller = getUserById($sellerId);
                                                }
                                            }
                                        } catch (Exception $e) {
                                            error_log("Error getting seller info: " . $e->getMessage());
                                        }
                                    }
                                    
                                    $sellerPhone = $seller ? ($seller['phone'] ?? ($seller['mobile'] ?? '')) : '';
                                    $sellerChatUrl = $seller ? ($seller['chat_consultation_url'] ?? '') : '';
                                    
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
                                    
                                    $sellerPhoneDisplay = $sellerPhone;
                                    if ($sellerName && $sellerPhone) {
                                        $sellerPhoneDisplay = $sellerName . '  ' . $sellerPhone;
                                    }
                                    
                                    // 개통완료 또는 종료 상태일 때만 리뷰 버튼 표시
                                    $appStatus = $app['application_status'] ?? '';
                                    $canWrite = in_array($appStatus, ['activation_completed', 'completed', 'closed', 'terminated']);
                                    
                                    $hasReview = false;
                                    $buttonText = '리뷰 작성';
                                    $buttonClass = 'mno-sim-review-write-btn';
                                    $buttonBgColor = '#EF4444';
                                    $buttonHoverColor = '#dc2626';
                                    $buttonDataReviewId = '';
                                    
                                    if ($canWrite && $applicationId && $productId) {
                                        // 리뷰 작성 여부 확인
                                        try {
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
                                                        // 'mno-sim'이 ENUM에 없으면 'mno'로 조회
                                                        $productTypeForQuery = 'mno';
                                                        error_log("Warning: product_type ENUM에 'mno-sim'이 없어 'mno'로 조회합니다. DB 업데이트가 필요합니다.");
                                                    }
                                                } catch (PDOException $e) {
                                                    error_log("Error checking product_type: " . $e->getMessage());
                                                    $productTypeForQuery = 'mno'; // 안전을 위해 'mno'로 대체
                                                }
                                                
                                                if ($hasApplicationId && !empty($applicationId)) {
                                                    $reviewStmt = $pdo->prepare("
                                                        SELECT id 
                                                        FROM product_reviews 
                                                        WHERE application_id = :application_id 
                                                        AND product_id = :product_id 
                                                        AND user_id = :user_id 
                                                        AND product_type = :product_type
                                                        AND status = 'approved'
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
                                                        AND status = 'approved'
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
                                            error_log("Error checking review: " . $e->getMessage());
                                        }
                                        
                                        // 최근 리뷰가 있으면 "리뷰 수정", 없으면 "리뷰 작성"
                                        $buttonText = $hasReview ? '리뷰 수정' : '리뷰 작성';
                                        $buttonClass = $hasReview ? 'mno-sim-review-edit-btn' : 'mno-sim-review-write-btn';
                                        $buttonBgColor = $hasReview ? '#6b7280' : '#EF4444';
                                        $buttonHoverColor = $hasReview ? '#4b5563' : '#dc2626';
                                    }
                                    
                                    // 판매자 전화번호, 채팅상담, 리뷰 버튼이 필요한 경우에만 섹션 표시
                                    if ($sellerPhone || $sellerChatUrl || $canWrite):
                                    ?>
                                        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                                            <?php
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
                                                        data-application-id="<?php echo htmlspecialchars($applicationId); ?>"
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
<div id="applicationDetailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 10000; overflow: hidden; padding: 20px;">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
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
        // 배경 페이지 스크롤 완전 차단 (스크롤바도 숨김)
        document.body.style.overflow = 'hidden';
        document.body.style.paddingRight = '0px';
        // html 요소도 스크롤 차단
        document.documentElement.style.overflow = 'hidden';
        
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
        // 배경 페이지 스크롤 복원
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        document.documentElement.style.overflow = '';
    }
    
    function displayApplicationDetails(data) {
        const customer = data.customer || {};
        const additionalInfo = data.additional_info || {};
        const productSnapshot = additionalInfo.product_snapshot || {};
        
        let html = '<div style="display: flex; flex-direction: column; gap: 24px;">';
        
        // 주문 정보 섹션
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
        
        // 기본 요금
        if (productSnapshot.price_main) {
            html += `<div style="color: #6b7280; font-weight: 500;">기본 요금:</div>`;
            const priceMainUnit = productSnapshot.price_main_unit || '원';
            html += `<div style="color: #1f2937; font-weight: 600;">월 ${formatNumber(productSnapshot.price_main)}${escapeHtml(priceMainUnit)}</div>`;
        }
        
        // 할인 후 요금
        if (productSnapshot.price_after_type && productSnapshot.price_after_type !== 'none') {
            html += `<div style="color: #6b7280; font-weight: 500;">할인 후 요금:</div>`;
            if (productSnapshot.price_after_type === 'free' || productSnapshot.price_after == 0) {
                html += `<div style="color: #6366f1; font-weight: 600;">무료</div>`;
            } else if (productSnapshot.price_after) {
                const priceAfterUnit = productSnapshot.price_after_unit || '원';
                html += `<div style="color: #6366f1; font-weight: 600;">월 ${formatNumber(productSnapshot.price_after)}${escapeHtml(priceAfterUnit)}</div>`;
            }
        }
        
        // 할인기간
        if (productSnapshot.discount_period) {
            html += `<div style="color: #6b7280; font-weight: 500;">할인기간:</div>`;
            let discountPeriodText = '';
            const discountPeriod = productSnapshot.discount_period;
            if (discountPeriod === '프로모션 없음') {
                discountPeriodText = '프로모션 없음';
            } else if (discountPeriod === '직접입력' && productSnapshot.discount_period_value && productSnapshot.discount_period_unit) {
                discountPeriodText = String(productSnapshot.discount_period_value) + escapeHtml(productSnapshot.discount_period_unit);
            } else if (discountPeriod) {
                discountPeriodText = discountPeriod;
            } else {
                discountPeriodText = '정보 없음';
            }
            html += `<div style="color: #1f2937;">${escapeHtml(discountPeriodText)}</div>`;
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
        
        // 기본 정보 섹션
        html += '<div style="margin-bottom: 24px;">';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">기본 정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        // 요금제 이름
        if (productSnapshot.plan_name) {
            html += `<div style="color: #6b7280; font-weight: 500;">요금제 이름</div>`;
            html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(productSnapshot.plan_name)}</div>`;
        }
        
        // 통신사 약정
        if (productSnapshot.contract_period) {
            html += `<div style="color: #6b7280; font-weight: 500;">통신사 약정</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.contract_period)}`;
            if (productSnapshot.contract_period_discount_value && productSnapshot.contract_period_discount_unit) {
                html += ` ${productSnapshot.contract_period_discount_value}${escapeHtml(productSnapshot.contract_period_discount_unit)}`;
            }
            html += `</div>`;
        }
        
        // 통신망
        if (productSnapshot.provider) {
            html += `<div style="color: #6b7280; font-weight: 500;">통신망</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.provider)}</div>`;
        }
        
        // 통신 기술
        if (productSnapshot.service_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">통신 기술</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.service_type)}</div>`;
        }
        
        // 가입 형태 (사용자가 선택한 정보)
        if (additionalInfo.subscription_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">가입 형태</div>`;
            // 가입 형태 한글 변환 (고객용 표시)
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
        
        // 요금제 유지기간
        if (productSnapshot.plan_maintenance_period_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">요금제 유지기간</div>`;
            const planMaintenanceType = productSnapshot.plan_maintenance_period_type;
            if (planMaintenanceType === '무약정') {
                html += `<div style="color: #1f2937;">무약정</div>`;
            } else if (planMaintenanceType === '직접입력') {
                const prefix = productSnapshot.plan_maintenance_period_prefix || '';
                const value = productSnapshot.plan_maintenance_period_value || null;
                const unit = productSnapshot.plan_maintenance_period_unit || '';
                if (value && unit) {
                    html += `<div style="color: #1f2937;">${escapeHtml(prefix + '+' + value + unit)}</div>`;
                } else {
                    html += `<div style="color: #1f2937;">정보 없음</div>`;
                }
            }
        }
        
        // 유심기변 불가기간
        if (productSnapshot.sim_change_restriction_period_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">유심기변 불가기간</div>`;
            const simChangeRestrictionType = productSnapshot.sim_change_restriction_period_type;
            if (simChangeRestrictionType === '무약정') {
                html += `<div style="color: #1f2937;">무약정</div>`;
            } else if (simChangeRestrictionType === '직접입력') {
                const prefix = productSnapshot.sim_change_restriction_period_prefix || '';
                const value = productSnapshot.sim_change_restriction_period_value || null;
                const unit = productSnapshot.sim_change_restriction_period_unit || '';
                if (value && unit) {
                    html += `<div style="color: #1f2937;">${escapeHtml(prefix + '+' + value + unit)}</div>`;
                } else {
                    html += `<div style="color: #1f2937;">정보 없음</div>`;
                }
            }
        }
        
        html += '</div></div>';
        
        // 데이터 정보 섹션
        html += '<div style="margin-bottom: 24px;">';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">데이터 정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        // 통화 정보
        if (productSnapshot.call_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">통화</div>`;
            let callText = productSnapshot.call_type;
            if (productSnapshot.call_type === '직접입력' && productSnapshot.call_amount) {
                const callAmountStr = String(productSnapshot.call_amount);
                const unit = productSnapshot.call_amount_unit || '분';
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
            html += `<div style="color: #6b7280; font-weight: 500;">문자</div>`;
            let smsText = productSnapshot.sms_type;
            if (productSnapshot.sms_type === '직접입력' && productSnapshot.sms_amount) {
                const smsAmountStr = String(productSnapshot.sms_amount);
                const unit = productSnapshot.sms_amount_unit || '건';
                if (smsAmountStr.endsWith('분') || smsAmountStr.endsWith('초') || smsAmountStr.endsWith('건')) {
                    smsText = smsAmountStr;
                } else {
                    smsText = smsAmountStr + unit;
                }
            }
            html += `<div style="color: #1f2937;">${escapeHtml(smsText)}</div>`;
        }
        
        // 데이터 제공량
        if (productSnapshot.data_amount) {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터 제공량</div>`;
            let dataText = '';
            if (productSnapshot.data_amount === '무제한') {
                dataText = '무제한';
            } else if (productSnapshot.data_amount === '직접입력' && productSnapshot.data_amount_value) {
                const dataValueStr = String(productSnapshot.data_amount_value);
                const unit = productSnapshot.data_unit || 'GB';
                if (dataValueStr.endsWith('GB') || dataValueStr.endsWith('MB') || dataValueStr.endsWith('TB') || 
                    dataValueStr.endsWith('Mbps') || dataValueStr.endsWith('Gbps') || dataValueStr.endsWith('Kbps')) {
                    dataText = dataValueStr;
                } else {
                    dataText = dataValueStr + unit;
                }
            } else {
                dataText = productSnapshot.data_amount;
            }
            html += `<div style="color: #1f2937;">${escapeHtml(dataText)}</div>`;
        }
        
        // 데이터 추가제공
        if (productSnapshot.data_additional && productSnapshot.data_additional !== '없음') {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터 추가제공</div>`;
            let additionalText = '';
            if (productSnapshot.data_additional === '직접입력' && productSnapshot.data_additional_value) {
                additionalText = productSnapshot.data_additional_value;
            } else {
                additionalText = productSnapshot.data_additional;
            }
            html += `<div style="color: #1f2937;">${escapeHtml(additionalText)}</div>`;
        }
        
        // 데이터 소진시
        if (productSnapshot.data_exhausted && productSnapshot.data_exhausted !== '직접입력') {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터 소진시</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.data_exhausted)}</div>`;
        } else if (productSnapshot.data_exhausted === '직접입력' && productSnapshot.data_exhausted_value) {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터 소진시</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.data_exhausted_value)}</div>`;
        }
        
        // 부가·영상통화 정보
        if (productSnapshot.additional_call_type && productSnapshot.additional_call) {
            html += `<div style="color: #6b7280; font-weight: 500;">부가·영상통화</div>`;
            let additionalCallText = '';
            const additionalCallStr = String(productSnapshot.additional_call);
            const additionalCallUnit = productSnapshot.additional_call_unit || '분';
            if (additionalCallStr.endsWith('분') || additionalCallStr.endsWith('초') || additionalCallStr.endsWith('건')) {
                additionalCallText = additionalCallStr;
            } else {
                additionalCallText = additionalCallStr + additionalCallUnit;
            }
            html += `<div style="color: #1f2937;">${escapeHtml(additionalCallText)}</div>`;
        }
        
        // 테더링(핫스팟) 정보
        if (productSnapshot.mobile_hotspot) {
            html += `<div style="color: #6b7280; font-weight: 500;">테더링(핫스팟)</div>`;
            let hotspotText = '';
            const mobileHotspot = productSnapshot.mobile_hotspot;
            if (mobileHotspot === '기본 제공량 내에서 사용') {
                hotspotText = '기본 제공량 내에서 사용';
            } else if (productSnapshot.mobile_hotspot_value && productSnapshot.mobile_hotspot_unit) {
                const hotspotValue = parseFloat(productSnapshot.mobile_hotspot_value);
                if (Math.floor(hotspotValue) === hotspotValue) {
                    hotspotText = formatNumber(hotspotValue) + escapeHtml(productSnapshot.mobile_hotspot_unit);
                } else {
                    const formatted = hotspotValue.toFixed(2).replace(/\.?0+$/, '');
                    hotspotText = formatted + escapeHtml(productSnapshot.mobile_hotspot_unit);
                }
            } else if (mobileHotspot) {
                hotspotText = mobileHotspot;
            } else {
                hotspotText = '정보 없음';
            }
            html += `<div style="color: #1f2937;">${escapeHtml(hotspotText)}</div>`;
        }
        
        html += '</div></div>';
        
        // 유심 정보 섹션
        html += '<div style="margin-bottom: 24px;">';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">유심 정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        // 일반 유심 정보
        if (productSnapshot.regular_sim_available) {
            html += `<div style="color: #6b7280; font-weight: 500;">일반 유심</div>`;
            let regularSimText = productSnapshot.regular_sim_available;
            if (regularSimText === '유심비 유료' || regularSimText === '배송가능') {
                if (productSnapshot.regular_sim_price && productSnapshot.regular_sim_price > 0) {
                    const regularSimUnit = productSnapshot.regular_sim_price_unit || '원';
                    regularSimText = `유심비 유료 (${formatNumber(productSnapshot.regular_sim_price)}${escapeHtml(regularSimUnit)})`;
                } else {
                    regularSimText = '유심비 유료';
                }
            } else if (regularSimText === '유심비 무료' || regularSimText === '유심 무료' || regularSimText === '무료제공') {
                regularSimText = '유심비 무료';
            } else if (regularSimText === '유심·배송비 무료' || regularSimText === '무료제공(배송비무료)') {
                regularSimText = '유심·배송비 무료';
            }
            html += `<div style="color: #1f2937;">${escapeHtml(regularSimText)}</div>`;
        }
        
        // NFC 유심 정보
        if (productSnapshot.nfc_sim_available) {
            html += `<div style="color: #6b7280; font-weight: 500;">NFC 유심</div>`;
            let nfcSimText = productSnapshot.nfc_sim_available;
            if (nfcSimText === '유심비 유료' || nfcSimText === '배송가능') {
                if (productSnapshot.nfc_sim_price && productSnapshot.nfc_sim_price > 0) {
                    const nfcSimUnit = productSnapshot.nfc_sim_price_unit || '원';
                    nfcSimText = `유심비 유료 (${formatNumber(productSnapshot.nfc_sim_price)}${escapeHtml(nfcSimUnit)})`;
                } else {
                    nfcSimText = '유심비 유료';
                }
            } else if (nfcSimText === '유심비 무료' || nfcSimText === '유심 무료' || nfcSimText === '무료제공') {
                nfcSimText = '유심비 무료';
            } else if (nfcSimText === '유심·배송비 무료' || nfcSimText === '무료제공(배송비무료)') {
                nfcSimText = '유심·배송비 무료';
            }
            html += `<div style="color: #1f2937;">${escapeHtml(nfcSimText)}</div>`;
        }
        
        // eSIM 정보
        if (productSnapshot.esim_available) {
            html += `<div style="color: #6b7280; font-weight: 500;">eSIM</div>`;
            let esimText = productSnapshot.esim_available;
            if (esimText === '개통불가') {
                esimText = '개통불가';
            } else if (esimText === 'eSIM 유료' || esimText === '유심비 유료' || esimText === '개통가능') {
                if (productSnapshot.esim_price && productSnapshot.esim_price > 0) {
                    const esimUnit = productSnapshot.esim_price_unit || '원';
                    esimText = `개통가능 (${formatNumber(productSnapshot.esim_price)}${escapeHtml(esimUnit)})`;
                } else {
                    esimText = '개통가능';
                }
            } else if (esimText === 'eSIM 무료' || esimText === '유심비 무료' || esimText === '유심 무료' || esimText === '무료제공') {
                esimText = 'eSIM 무료';
            }
            html += `<div style="color: #1f2937;">${escapeHtml(esimText)}</div>`;
        }
        
        html += '</div></div>';
        
        // 기본 제공 초과 시 섹션
        let hasOverData = false;
        let overDataPrice = productSnapshot.over_data_price;
        let overVoicePrice = productSnapshot.over_voice_price;
        let overVideoPrice = productSnapshot.over_video_price;
        let overSmsPrice = productSnapshot.over_sms_price;
        let overLmsPrice = productSnapshot.over_lms_price;
        let overMmsPrice = productSnapshot.over_mms_price;
        
        if (overDataPrice !== null && overDataPrice !== undefined && overDataPrice !== '') hasOverData = true;
        if (overVoicePrice !== null && overVoicePrice !== undefined && overVoicePrice !== '') hasOverData = true;
        if (overVideoPrice !== null && overVideoPrice !== undefined && overVideoPrice !== '') hasOverData = true;
        if (overSmsPrice !== null && overSmsPrice !== undefined && overSmsPrice !== '') hasOverData = true;
        if (overLmsPrice !== null && overLmsPrice !== undefined && overLmsPrice !== '') hasOverData = true;
        if (overMmsPrice !== null && overMmsPrice !== undefined && overMmsPrice !== '') hasOverData = true;
        
        if (hasOverData) {
            html += '<div>';
            html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">기본 제공 초과 시</h3>';
            html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
            
            // 데이터
            if (overDataPrice !== null && overDataPrice !== undefined && overDataPrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">데이터</div>`;
                const overDataPriceUnit = productSnapshot.over_data_price_unit || '원/MB';
                const overDataPriceFloat = parseFloat(overDataPrice);
                let overDataFormatted = '';
                if (isNaN(overDataPriceFloat)) {
                    overDataFormatted = String(overDataPrice);
                } else if (Math.floor(overDataPriceFloat) === overDataPriceFloat) {
                    overDataFormatted = formatNumber(overDataPriceFloat);
                } else {
                    overDataFormatted = parseFloat(overDataPriceFloat.toFixed(2)).toString();
                }
                html += `<div style="color: #1f2937;">${escapeHtml(overDataFormatted)} ${escapeHtml(overDataPriceUnit)}</div>`;
            }
            
            // 음성
            if (overVoicePrice !== null && overVoicePrice !== undefined && overVoicePrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">음성</div>`;
                const overVoicePriceUnit = productSnapshot.over_voice_price_unit || '원/초';
                const overVoicePriceFloat = parseFloat(overVoicePrice);
                let overVoiceFormatted = '';
                if (isNaN(overVoicePriceFloat)) {
                    overVoiceFormatted = String(overVoicePrice);
                } else if (Math.floor(overVoicePriceFloat) === overVoicePriceFloat) {
                    overVoiceFormatted = formatNumber(overVoicePriceFloat);
                } else {
                    overVoiceFormatted = parseFloat(overVoicePriceFloat.toFixed(2)).toString();
                }
                html += `<div style="color: #1f2937;">${escapeHtml(overVoiceFormatted)} ${escapeHtml(overVoicePriceUnit)}</div>`;
            }
            
            // 영상통화
            if (overVideoPrice !== null && overVideoPrice !== undefined && overVideoPrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">영상통화</div>`;
                const overVideoPriceUnit = productSnapshot.over_video_price_unit || '원/초';
                const overVideoPriceFloat = parseFloat(overVideoPrice);
                let overVideoFormatted = '';
                if (isNaN(overVideoPriceFloat)) {
                    overVideoFormatted = String(overVideoPrice);
                } else if (Math.floor(overVideoPriceFloat) === overVideoPriceFloat) {
                    overVideoFormatted = formatNumber(overVideoPriceFloat);
                } else {
                    overVideoFormatted = parseFloat(overVideoPriceFloat.toFixed(2)).toString();
                }
                html += `<div style="color: #1f2937;">${escapeHtml(overVideoFormatted)} ${escapeHtml(overVideoPriceUnit)}</div>`;
            }
            
            // 단문메시지(SMS)
            if (overSmsPrice !== null && overSmsPrice !== undefined && overSmsPrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">단문메시지(SMS)</div>`;
                const overSmsPriceUnit = productSnapshot.over_sms_price_unit || '원/건';
                html += `<div style="color: #1f2937;">${formatNumber(overSmsPrice)} ${escapeHtml(overSmsPriceUnit)}</div>`;
            }
            
            // 텍스트형(LMS)
            if (overLmsPrice !== null && overLmsPrice !== undefined && overLmsPrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">텍스트형(LMS)</div>`;
                const overLmsPriceUnit = productSnapshot.over_lms_price_unit || '원/건';
                html += `<div style="color: #1f2937;">${formatNumber(overLmsPrice)} ${escapeHtml(overLmsPriceUnit)}</div>`;
            }
            
            // 멀티미디어형(MMS)
            if (overMmsPrice !== null && overMmsPrice !== undefined && overMmsPrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">멀티미디어형(MMS)</div>`;
                const overMmsPriceUnit = productSnapshot.over_mms_price_unit || '원/건';
                html += `<div style="color: #1f2937;">${formatNumber(overMmsPrice)} ${escapeHtml(overMmsPriceUnit)}</div>`;
            }
            
            html += '</div></div>';
            
            // 문자메시지 주의사항
            html += '<div style="margin-top: 16px; padding: 12px; background: #f9fafb; border-radius: 8px; font-size: 13px; color: #6b7280; line-height: 1.6;">';
            html += '문자메시지 기본제공 혜택을 약관에 정한 기준보다 많이 사용하거나 스팸, 광고 목적으로 이용한 것이 확인되면 추가 요금을 내야 하거나 서비스 이용이 정지될 수 있어요.';
            html += '</div>';
        }
        
        // 혜택 및 유의사항 섹션
        if (productSnapshot.benefits || productSnapshot.promotion_title || productSnapshot.promotions) {
            html += '<div style="margin-bottom: 24px;">';
            html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">혜택 및 유의사항</h3>';
            html += '<div style="font-size: 14px; color: #374151; line-height: 1.8;">';
            
            // 혜택 정보 (프로모션 제목 + 항목들)
            if (productSnapshot.promotion_title || productSnapshot.promotions) {
                html += '<div style="margin-bottom: 16px;">';
                html += '<div style="font-weight: 600; color: #1f2937; margin-bottom: 8px;">혜택</div>';
                let benefitText = '';
                
                // 제목이 있으면 표시
                if (productSnapshot.promotion_title) {
                    benefitText = escapeHtml(productSnapshot.promotion_title);
                }
                
                // 항목들 가져오기
                let promotions = [];
                try {
                    if (typeof productSnapshot.promotions === 'string') {
                        promotions = JSON.parse(productSnapshot.promotions);
                    } else if (Array.isArray(productSnapshot.promotions)) {
                        promotions = productSnapshot.promotions;
                    }
                } catch(e) {
                    promotions = [];
                }
                
                // 항목이 있으면 제목 ( 항목, 항목, ... ) 형식으로 표시
                if (promotions.length > 0) {
                    const promotionList = promotions.filter(p => p && p.trim()).map(p => escapeHtml(p.trim())).join(', ');
                    if (benefitText) {
                        benefitText = `${benefitText} (${promotionList})`;
                    } else {
                        benefitText = promotionList;
                    }
                }
                
                if (benefitText) {
                    html += `<div style="color: #374151;">${benefitText}</div>`;
                }
                html += '</div>';
            }
            
            // 유의사항 (benefits)
            let benefits = null;
            try {
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
                benefits = [String(productSnapshot.benefits)];
            }
            
            if (benefits && benefits.length > 0) {
                html += '<div style="margin-top: 16px;">';
                html += '<div style="font-weight: 600; color: #1f2937; margin-bottom: 8px;">유의사항</div>';
                html += '<ul style="margin: 0; padding-left: 20px; list-style-type: disc;">';
                benefits.forEach(function(benefit) {
                    const benefitText = String(benefit).trim();
                    if (benefitText) {
                        const formattedText = escapeHtml(benefitText).replace(/\n/g, '<br>');
                        html += `<li style="margin-bottom: 8px;">${formattedText}</li>`;
                    }
                });
                html += '</ul>';
                html += '</div>';
            }
            
            html += '</div></div>';
        }
        
        html += '</div>';
        
        modalContent.innerHTML = html;
    }
    
    function formatNumber(num) {
        if (!num) return '0';
        return parseFloat(num).toLocaleString('ko-KR');
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // 리뷰 작성/수정 기능
    const reviewWriteButtons = document.querySelectorAll('.mno-sim-review-write-btn, .mno-sim-review-edit-btn');
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
            const productType = this.getAttribute('data-product-type') || 'mno-sim';
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
                    fetch(`/MVNO/api/get-review-by-application.php?application_id=${currentReviewApplicationId}&product_id=${currentReviewProductId}&product_type=${productType}`)
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
                                }
                            }
                            // 모달 표시
                            reviewModal.style.display = 'flex';
                            setTimeout(() => {
                                reviewModal.classList.add('show');
                            }, 10);
                        })
                        .catch(error => {
                            console.error('Error loading review:', error);
                            // 에러가 발생해도 모달 표시
                            reviewModal.style.display = 'flex';
                            setTimeout(() => {
                                reviewModal.classList.add('show');
                            }, 10);
                        });
                } else {
                    // 새 리뷰 작성 모드
                    reviewModal.style.display = 'flex';
                    setTimeout(() => {
                        reviewModal.classList.add('show');
                    }, 10);
                }
            }
        });
    });
    
    // 리뷰 모달 닫기
    function closeReviewModal() {
        if (reviewModal) {
            reviewModal.classList.remove('show');
            setTimeout(() => {
                reviewModal.style.display = 'none';
                // 스크롤 복원
                const scrollY = document.body.style.top;
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.width = '';
                document.body.style.overflow = '';
                if (scrollY) {
                    window.scrollTo(0, parseInt(scrollY || '0') * -1);
                }
                
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
                const reviewTextCounter = document.getElementById('mvnoReviewTextCounter');
                if (reviewTextCounter) {
                    reviewTextCounter.textContent = '0';
                }
            }, 300);
        }
        currentReviewApplicationId = null;
        currentReviewProductId = null;
        currentReviewId = null;
        isEditMode = false;
    }
    
    // 모달 닫기 버튼
    if (reviewModalClose) {
        reviewModalClose.addEventListener('click', closeReviewModal);
    }
    
    // 오버레이 클릭 시 닫기
    if (reviewModalOverlay) {
        reviewModalOverlay.addEventListener('click', closeReviewModal);
    }
    
    // 취소 버튼
    if (reviewCancelBtn) {
        reviewCancelBtn.addEventListener('click', closeReviewModal);
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && reviewModal && (reviewModal.style.display === 'flex' || reviewModal.classList.contains('show'))) {
            closeReviewModal();
        }
    });
    
    // 리뷰 폼 제출
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('.mvno-review-btn-submit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '처리 중...';
            }
            
            // 필드 값 가져오기
            const kindnessRatingInput = reviewForm.querySelector('input[name="kindness_rating"]:checked');
            const speedRatingInput = reviewForm.querySelector('input[name="speed_rating"]:checked');
            const reviewTextarea = reviewForm.querySelector('#mvnoReviewText');
            const reviewText = reviewTextarea ? reviewTextarea.value.trim() : '';
            
            // 클라이언트 사이드 validation
            if (!kindnessRatingInput) {
                showAlert('친절해요 별점을 선택해주세요.', '알림');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                }
                return;
            }
            
            if (!speedRatingInput) {
                showAlert('개통 빨라요 별점을 선택해주세요.', '알림');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                }
                return;
            }
            
            if (!reviewText) {
                showAlert('리뷰 내용을 입력해주세요.', '알림');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                }
                return;
            }
            
            // FormData 생성
            const formData = new FormData();
            formData.append('product_id', currentReviewProductId);
            formData.append('product_type', 'mno-sim');
            formData.append('kindness_rating', kindnessRatingInput.value);
            formData.append('speed_rating', speedRatingInput.value);
            formData.append('content', reviewText);
            formData.append('application_id', currentReviewApplicationId);
            
            if (isEditMode && currentReviewId) {
                formData.append('review_id', currentReviewId);
            }
            
            fetch('/MVNO/api/submit-review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(isEditMode ? '리뷰가 수정되었습니다.' : '리뷰가 작성되었습니다.', '알림').then(() => {
                        closeReviewModal();
                        location.reload();
                    });
                } else {
                    showAlert(data.message || '리뷰 작성에 실패했습니다.', '오류');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('리뷰 작성 중 오류가 발생했습니다.', '오류');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                }
            });
        });
    }
    
    // 리뷰 삭제 버튼
    const reviewDeleteBtn = document.getElementById('mvnoReviewDeleteBtn');
    if (reviewDeleteBtn) {
        reviewDeleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!currentReviewId) return;
            
            if (!confirm('정말 이 리뷰를 삭제하시겠습니까?')) {
                return;
            }
            
            this.disabled = true;
            const originalText = this.querySelector('span').textContent;
            this.querySelector('span').textContent = '삭제 중...';
            
            const formData = new FormData();
            formData.append('review_id', currentReviewId);
            formData.append('product_type', 'mno-sim');
            
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
    }
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

