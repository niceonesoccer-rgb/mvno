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

// 포인트 설정 및 함수 포함
require_once '../includes/data/point-settings.php';
require_once '../includes/data/product-functions.php';
require_once '../includes/data/db-config.php';
require_once '../includes/data/plan-data.php';

// DB에서 실제 신청 내역 가져오기
$phones = getUserMnoApplications($user_id);
error_log("mno-order.php: Received " . count($phones) . " phones from getUserMnoApplications");
if (count($phones) > 0) {
    error_log("mno-order.php: First phone keys: " . implode(', ', array_keys($phones[0])));
}

// 디버깅: 데이터 확인
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
if ($debug_mode) {
    $pdo = getDBConnection();
    if ($pdo) {
        // 1. user_id 확인
        $debug_info = [
            'user_id' => $user_id,
            'current_user' => $currentUser,
            'phones_count' => count($phones),
            'phones_data' => $phones
        ];
        
        // 2. 데이터베이스에서 직접 확인
        try {
            // application_customers에서 user_id로 검색
            $stmt1 = $pdo->prepare("SELECT COUNT(*) as cnt FROM application_customers WHERE user_id = ?");
            $stmt1->execute([$user_id]);
            $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
            $debug_info['application_customers_count'] = $result1['cnt'] ?? 0;
            
            // product_applications에서 mno 타입 검색
            $stmt2 = $pdo->prepare("
                SELECT COUNT(*) as cnt 
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                WHERE c.user_id = ? AND a.product_type = 'mno'
            ");
            $stmt2->execute([$user_id]);
            $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
            $debug_info['mno_applications_count'] = $result2['cnt'] ?? 0;
            
            // 전체 신청 내역 확인 (user_id NULL 포함)
            $stmt3 = $pdo->prepare("
                SELECT a.id, a.product_id, a.product_type, a.application_status, a.created_at, c.user_id, c.name, c.phone
                FROM product_applications a
                LEFT JOIN application_customers c ON a.id = c.application_id
                WHERE a.product_type = 'mno'
                ORDER BY a.created_at DESC
                LIMIT 10
            ");
            $stmt3->execute();
            $debug_info['all_mno_applications'] = $stmt3->fetchAll(PDO::FETCH_ASSOC);
            
            // 실제 쿼리 실행 테스트
            $stmt4 = $pdo->prepare("
                SELECT 
                    a.id as application_id,
                    a.product_id,
                    a.application_status,
                    a.created_at as order_date,
                    c.name,
                    c.phone,
                    c.email,
                    c.user_id,
                    c.additional_info,
                    p.status as product_status
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                INNER JOIN products p ON a.product_id = p.id
                WHERE c.user_id = ? 
                AND a.product_type = 'mno'
                ORDER BY a.created_at DESC
            ");
            $stmt4->execute([$user_id]);
            $debug_info['direct_query_result'] = $stmt4->fetchAll(PDO::FETCH_ASSOC);
            $debug_info['direct_query_count'] = count($debug_info['direct_query_result']);
            
        } catch (PDOException $e) {
            $debug_info['error'] = $e->getMessage();
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
                        <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 통신사폰</h2>
                    </div>
                    <p style="font-size: 14px; color: #6b7280; margin: 0; margin-left: 36px;">카드를 클릭하면 상품 상세 정보를 확인할 수 있습니다.</p>
                </div>

                <!-- 디버깅 정보 (debug=1 파라미터가 있을 때만 표시) -->
                <?php if ($debug_mode && isset($debug_info)): ?>
                    <div style="padding: 20px; background: #f3f4f6; border-radius: 8px; margin-bottom: 20px; font-family: monospace; font-size: 12px;">
                        <h3 style="margin-top: 0; color: #6366f1;">디버깅 정보</h3>
                        <pre style="background: white; padding: 15px; border-radius: 4px; overflow-x: auto;"><?php echo htmlspecialchars(json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                    </div>
                <?php endif; ?>

                <!-- 신청한 통신사폰 목록 -->
                <div style="margin-bottom: 32px;" id="phonesContainer">
                    <?php if (empty($phones)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: #6b7280;">
                            신청한 통신사폰이 없습니다.
                            <?php if (!$debug_mode): ?>
                                <br><br>
                                <a href="?debug=1" style="color: #6366f1; text-decoration: underline; font-size: 12px;">디버깅 정보 보기</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($phones as $index => $phone): ?>
                                <div class="phone-item application-card" 
                                     data-index="<?php echo $index; ?>" 
                                     data-phone-id="<?php echo $phone['id']; ?>" 
                                     data-application-id="<?php echo htmlspecialchars($phone['application_id'] ?? ''); ?>"
                                     style="<?php echo $index >= 10 ? 'display: none;' : ''; ?> padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"
                                     onmouseover="this.style.borderColor='#6366f1'; this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.15)'"
                                     onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'"
                                     onclick="openOrderModal(<?php echo htmlspecialchars($phone['application_id'] ?? ''); ?>)">
                                    
                                    <!-- 상단: 단말기명 | 신청일 -->
                                    <div style="position: relative; margin-bottom: 12px;">
                                        <!-- 주문일자 (상단 오른쪽) -->
                                        <?php
                                        $orderDate = $phone['order_date'] ?? '';
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
                                        <?php 
                                        // 상태별 배지 색상 설정
                                        $appStatus = $phone['application_status'] ?? '';
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
                                        
                                        if (!empty($phone['status'])): ?>
                                            <div style="display: inline-flex; align-items: center; padding: 6px 12px; background: <?php echo $statusBg; ?>; border-radius: 6px;">
                                                <span style="font-size: 13px; color: <?php echo $statusColor; ?>; font-weight: 600;"><?php echo htmlspecialchars($phone['status']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- 판매자 정보 및 리뷰 작성 영역 -->
                                    <?php
                                    $productId = $phone['product_id'] ?? 0;
                                    $applicationId = $phone['application_id'] ?? '';
                                    
                                    // 판매자 정보 가져오기 (모든 상태에서)
                                    $sellerId = null;
                                    $seller = null;
                                    try {
                                        $pdo = getDBConnection();
                                        if ($pdo && $productId > 0) {
                                            // product_id로 seller_id 가져오기
                                            $sellerStmt = $pdo->prepare("SELECT seller_id FROM products WHERE id = :product_id LIMIT 1");
                                            $sellerStmt->execute([':product_id' => $productId]);
                                            $product = $sellerStmt->fetch(PDO::FETCH_ASSOC);
                                            if ($product && !empty($product['seller_id'])) {
                                                $sellerId = $product['seller_id'];
                                                // 판매자 정보 가져오기
                                                $seller = getSellerById($sellerId);
                                            }
                                        }
                                    } catch (Exception $e) {
                                        error_log("Error fetching seller info: " . $e->getMessage());
                                    }
                                    
                                    $sellerPhone = $seller ? ($seller['phone'] ?? ($seller['mobile'] ?? '')) : '';
                                    
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
                                    
                                    // 개통완료 또는 종료 상태일 때만 리뷰 버튼 표시
                                    $canWrite = in_array($appStatus, ['completed', 'closed']);
                                    
                                    // 리뷰 존재 여부 확인
                                    $hasReview = false;
                                    if ($canWrite && $applicationId && $productId) {
                                        try {
                                            $pdo = getDBConnection();
                                            if ($pdo) {
                                                $reviewStmt = $pdo->prepare("
                                                    SELECT COUNT(*) as cnt 
                                                    FROM product_reviews 
                                                    WHERE application_id = :application_id 
                                                    AND product_id = :product_id 
                                                    AND user_id = :user_id 
                                                    AND product_type = 'mno'
                                                    AND status != 'deleted'
                                                ");
                                                $reviewStmt->execute([
                                                    ':application_id' => $applicationId,
                                                    ':product_id' => $productId,
                                                    ':user_id' => $user_id
                                                ]);
                                                $reviewResult = $reviewStmt->fetch(PDO::FETCH_ASSOC);
                                                $hasReview = ($reviewResult['cnt'] ?? 0) > 0;
                                            }
                                        } catch (Exception $e) {
                                            error_log("Error checking review: " . $e->getMessage());
                                        }
                                    }
                                    
                                    $buttonText = $hasReview ? '리뷰 수정' : '리뷰 작성';
                                    $buttonClass = $hasReview ? 'mno-review-edit-btn' : 'mno-review-write-btn';
                                    $buttonBgColor = $hasReview ? '#6b7280' : '#EF4444';
                                    $buttonHoverColor = $hasReview ? '#4b5563' : '#dc2626';
                                    
                                    // 판매자 전화번호가 있거나 리뷰 버튼이 필요한 경우에만 섹션 표시
                                    if ($sellerPhone || $canWrite):
                                    ?>
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
                                                <div style="display: flex; align-items: center; justify-content: center;" onclick="event.stopPropagation();">
                                                    <button 
                                                        class="<?php echo $buttonClass; ?>" 
                                                        data-application-id="<?php echo htmlspecialchars($applicationId); ?>"
                                                        data-product-id="<?php echo htmlspecialchars($productId); ?>"
                                                        data-has-review="<?php echo $hasReview ? '1' : '0'; ?>"
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
                    <?php endif; ?>
                </div>

                <!-- 더보기 버튼 -->
                <?php if (count($phones) > 10): ?>
                <div style="margin-top: 32px; margin-bottom: 32px;" id="moreButtonContainer">
                    <button class="plan-review-more-btn" id="morePhonesBtn" style="width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                        더보기 (<?php 
                        $remaining = count($phones) - 10;
                        echo $remaining > 10 ? 10 : $remaining;
                        ?>개)
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- 주문 상세 정보 모달 -->
<div id="orderDetailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 10000; overflow-y: auto; padding: 20px;">
    <div style="max-width: 800px; margin: 40px auto; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); position: relative;">
        <!-- 모달 헤더 -->
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 24px; font-weight: bold; margin: 0; color: #1f2937;">주문 정보</h2>
            <button id="closeOrderModalBtn" style="background: none; border: none; cursor: pointer; padding: 8px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
        <!-- 모달 내용 -->
        <div id="orderModalContent" style="padding: 24px; max-height: calc(100vh - 200px); overflow-y: auto;">
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
function openOrderModal(applicationId) {
    const modal = document.getElementById('orderDetailModal');
    const modalContent = document.getElementById('orderModalContent');
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
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
                displayOrderDetails(data.data);
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
                </div>
            `;
        });
}

function closeOrderModal() {
    const modal = document.getElementById('orderDetailModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNumber(num) {
    if (!num) return '0';
    return parseFloat(num).toLocaleString('ko-KR');
}

function displayOrderDetails(data) {
    const modalContent = document.getElementById('orderModalContent');
    const customer = data.customer || {};
    const additionalInfo = data.additional_info || {};
    const productSnapshot = additionalInfo.product_snapshot || {};
    
    let html = '<div style="display: flex; flex-direction: column; gap: 24px;">';
    
    // 신청 정보 섹션 (위로 이동)
    html += '<div>';
    html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">신청 정보</h3>';
    html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
    
    // 주문번호 (필수 표시)
    const orderNumber = data.order_number || (data.application_id ? '#' + data.application_id : '');
    if (orderNumber) {
        html += `<div style="color: #6b7280; font-weight: 500;">주문번호:</div>`;
        html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(orderNumber)}</div>`;
    }
    
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
    
    html += '</div></div>';
    
    // 주문 정보 섹션
    html += '<div>';
    html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">주문 정보</h3>';
    html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
    
    if (productSnapshot.device_name) {
        html += `<div style="color: #6b7280; font-weight: 500;">단말기명:</div>`;
        html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(productSnapshot.device_name)}</div>`;
    }
    
    if (productSnapshot.device_price) {
        html += `<div style="color: #6b7280; font-weight: 500;">단말기 출고가:</div>`;
        html += `<div style="color: #1f2937; font-weight: 600;">${formatNumber(productSnapshot.device_price)}원</div>`;
    }
    
    if (productSnapshot.device_capacity) {
        html += `<div style="color: #6b7280; font-weight: 500;">용량:</div>`;
        html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.device_capacity)}</div>`;
    }
    
    if (additionalInfo.device_colors && additionalInfo.device_colors.length > 0) {
        html += `<div style="color: #6b7280; font-weight: 500;">선택한 색상:</div>`;
        html += `<div style="color: #1f2937;">${escapeHtml(additionalInfo.device_colors[0])}</div>`;
    }
    
    if (additionalInfo.carrier) {
        html += `<div style="color: #6b7280; font-weight: 500;">통신사:</div>`;
        html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(additionalInfo.carrier)}</div>`;
    }
    
    if (additionalInfo.subscription_type) {
        html += `<div style="color: #6b7280; font-weight: 500;">가입형태:</div>`;
        // 고객용 표시: 신규가입, 번호이동, 기기변경
        let subscriptionTypeText = additionalInfo.subscription_type;
        const subscriptionTypeMap = {
            'new': '신규가입',
            'port': '번호이동',
            'mnp': '번호이동',
            'change': '기기변경'
        };
        if (subscriptionTypeMap[subscriptionTypeText]) {
            subscriptionTypeText = subscriptionTypeMap[subscriptionTypeText];
        }
        html += `<div style="color: #1f2937;">${escapeHtml(subscriptionTypeText)}</div>`;
    }
    
    if (additionalInfo.discount_type) {
        html += `<div style="color: #6b7280; font-weight: 500;">할인방법:</div>`;
        html += `<div style="color: #1f2937;">${escapeHtml(additionalInfo.discount_type)}</div>`;
    }
    
    if (additionalInfo.price !== undefined && additionalInfo.price !== null && additionalInfo.price !== '') {
        html += `<div style="color: #6b7280; font-weight: 500;">가격:</div>`;
        html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(additionalInfo.price)}</div>`;
    }
    
    // 프로모션 정보 (가격 밑에 표시)
    if (productSnapshot.promotion_title || productSnapshot.promotions) {
        html += `<div style="color: #6b7280; font-weight: 500;">프로모션:</div>`;
        html += `<div style="color: #1f2937;">`;
        
        let promotionText = '';
        
        // 제목
        if (productSnapshot.promotion_title) {
            promotionText = escapeHtml(productSnapshot.promotion_title);
        }
        
        // 항목들
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
        
        // 빈 문자열 제거
        promotions = promotions.filter(p => p && p.trim() !== '');
        
        if (promotions.length > 0) {
            const promotionItems = promotions.map(p => escapeHtml(p)).join(', ');
            if (promotionText) {
                promotionText += `(${promotionItems})`;
            } else {
                promotionText = `(${promotionItems})`;
            }
        }
        
        if (promotionText) {
            html += `<div>${promotionText}</div>`;
        }
        
        html += `</div>`;
    }
    
    if (productSnapshot.delivery_method) {
        let deliveryText = productSnapshot.delivery_method === 'visit' ? '내방' : '배송';
        if (productSnapshot.visit_region) {
            deliveryText += `(${productSnapshot.visit_region})`;
        }
        html += `<div style="color: #6b7280; font-weight: 500;">단말기 수령방법:</div>`;
        html += `<div style="color: #1f2937;">${escapeHtml(deliveryText)}</div>`;
    }
    
    html += '</div></div>';
    
    // 판매자 상담 정보 섹션 추가
    if (data.seller_consultation) {
        const consultation = data.seller_consultation;
        const hasConsultation = consultation.phone || consultation.mobile;
        
        if (hasConsultation) {
            html += '<div style="border-top: 2px solid #e5e7eb; padding-top: 24px; margin-top: 24px;">';
            html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">상담하기</h3>';
            html += '<div style="display: flex; flex-direction: column; gap: 12px;">';
            
            const telNumber = consultation.mobile ? consultation.mobile.replace(/[^0-9]/g, '') : (consultation.phone ? consultation.phone.replace(/[^0-9]/g, '') : '');
            const telDisplay = consultation.mobile || consultation.phone;
            if (telNumber) {
                html += `<a href="tel:${telNumber}" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; background: #10b981; color: white; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    전화 상담: ${escapeHtml(telDisplay)}
                </a>`;
            }
            
            html += '</div></div>';
        }
    }
    
    html += '</div>';
    
    modalContent.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('orderDetailModal');
    const closeBtn = document.getElementById('closeOrderModalBtn');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeOrderModal);
    }
    
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeOrderModal();
            }
        });
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'block') {
            closeOrderModal();
        }
    });
    
    // 더보기 기능
    const moreBtn = document.getElementById('morePhonesBtn');
    const phoneItems = document.querySelectorAll('.phone-item');
    let visibleCount = 10;
    const totalPhones = phoneItems.length;
    const loadCount = 10;

    function updateButtonText() {
        if (!moreBtn) return;
        const remaining = totalPhones - visibleCount;
        if (remaining > 0) {
            const showCount = remaining > loadCount ? loadCount : remaining;
            moreBtn.textContent = `더보기 (${showCount}개)`;
        }
    }

    if (moreBtn && totalPhones > 10) {
        updateButtonText();
        
        moreBtn.addEventListener('click', function() {
            const endCount = Math.min(visibleCount + loadCount, totalPhones);
            for (let i = visibleCount; i < endCount; i++) {
                if (phoneItems[i]) {
                    phoneItems[i].style.display = 'block';
                }
            }
            
            visibleCount = endCount;
            
            if (visibleCount >= totalPhones) {
                const moreButtonContainer = document.getElementById('moreButtonContainer');
                if (moreButtonContainer) {
                    moreButtonContainer.style.display = 'none';
                }
            } else {
                updateButtonText();
            }
        });
    } else if (moreBtn && totalPhones <= 10) {
        const moreButtonContainer = document.getElementById('moreButtonContainer');
        if (moreButtonContainer) {
            moreButtonContainer.style.display = 'none';
        }
    }
    
    // MNO 리뷰 작성/수정 기능
    const reviewWriteButtons = document.querySelectorAll('.mno-review-write-btn, .mno-review-edit-btn');
    const reviewModal = document.getElementById('mnoReviewModal');
    const reviewForm = document.getElementById('mnoReviewForm');
    const reviewModalClose = reviewModal ? reviewModal.querySelector('.mno-review-modal-close') : null;
    const reviewModalOverlay = reviewModal ? reviewModal.querySelector('.mno-review-modal-overlay') : null;
    const reviewCancelBtn = reviewForm ? reviewForm.querySelector('.mno-review-btn-cancel') : null;
    
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
            isEditMode = hasReview;
            currentReviewId = null;
            
            if (reviewModal) {
                // 먼저 모달 제목과 버튼 텍스트를 설정
                const modalTitle = reviewModal.querySelector('.mno-review-modal-title');
                if (modalTitle) {
                    modalTitle.textContent = isEditMode ? '리뷰 수정' : '리뷰 작성';
                }
                
                // 제출 버튼 텍스트 변경
                const submitBtn = reviewForm ? reviewForm.querySelector('.mno-review-btn-submit') : null;
                if (submitBtn) {
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
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
                    fetch(`/MVNO/api/get-review-by-application.php?application_id=${currentReviewApplicationId}&product_id=${currentReviewProductId}&product_type=mno`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.review) {
                                currentReviewId = data.review.id;
                                
                                // 삭제 버튼에 리뷰 ID 저장 및 표시
                                const deleteBtn = document.getElementById('mnoReviewDeleteBtn');
                                if (deleteBtn) {
                                    deleteBtn.setAttribute('data-review-id', data.review.id);
                                    deleteBtn.style.display = 'flex';
                                }
                                
                                // 별점 설정
                                if (data.review.kindness_rating) {
                                    const kindnessInput = reviewForm.querySelector(`input[name="kindness_rating"][value="${data.review.kindness_rating}"]`);
                                    if (kindnessInput) {
                                        kindnessInput.checked = true;
                                        const rating = parseInt(data.review.kindness_rating);
                                        const kindnessLabels = reviewForm.querySelectorAll('.mno-star-rating[data-rating-type="kindness"] .star-label');
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
                                        const speedLabels = reviewForm.querySelectorAll('.mno-star-rating[data-rating-type="speed"] .star-label');
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
                                const reviewTextarea = reviewForm.querySelector('#reviewText');
                                if (reviewTextarea && data.review.content) {
                                    reviewTextarea.value = data.review.content;
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
            const reviewText = reviewForm.querySelector('#reviewText').value.trim();
            
            if (!kindnessRatingInput) {
                alert('친절해요 별점을 선택해주세요.');
                return;
            }
            
            if (!speedRatingInput) {
                alert('개통 빨라요 별점을 선택해주세요.');
                return;
            }
            
            if (!reviewText) {
                alert('리뷰 내용을 입력해주세요.');
                return;
            }
            
            const formData = new FormData();
            formData.append('product_id', currentReviewProductId);
            formData.append('product_type', 'mno');
            formData.append('kindness_rating', kindnessRatingInput.value);
            formData.append('speed_rating', speedRatingInput.value);
            formData.append('content', reviewText);
            formData.append('application_id', currentReviewApplicationId);
            
            if (isEditMode && currentReviewId) {
                formData.append('review_id', currentReviewId);
            }
            
            // 제출 버튼 비활성화
            const submitBtn = reviewForm.querySelector('.mno-review-btn-submit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '처리 중...';
            }
            
            fetch('/MVNO/api/submit-review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('리뷰가 ' + (isEditMode ? '수정' : '작성') + '되었습니다.');
                    closeReviewModal();
                    location.reload(); // 페이지 새로고침하여 리뷰 버튼 상태 업데이트
                } else {
                    alert(data.message || '리뷰 작성에 실패했습니다.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('리뷰 작성 중 오류가 발생했습니다.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                }
            });
        });
    }
    
    // MNO 리뷰 삭제 버튼 클릭 이벤트
    const deleteReviewBtn = document.getElementById('mnoReviewDeleteBtn');
    if (deleteReviewBtn) {
        deleteReviewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const reviewId = this.getAttribute('data-review-id') || currentReviewId;
            if (!reviewId) {
                alert('리뷰 정보를 찾을 수 없습니다.');
                return;
            }
            
            if (!confirm('정말로 리뷰를 삭제하시겠습니까?\n삭제된 리뷰는 복구할 수 없습니다.')) {
                return;
            }
            
            // 삭제 버튼 비활성화
            this.disabled = true;
            const originalText = this.querySelector('span').textContent;
            this.querySelector('span').textContent = '삭제 중...';
            
            const formData = new FormData();
            formData.append('review_id', reviewId);
            formData.append('product_type', 'mno');
            
            fetch('/MVNO/api/delete-review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('리뷰가 삭제되었습니다.');
                    closeReviewModal();
                    location.reload();
                } else {
                    alert(data.message || '리뷰 삭제에 실패했습니다.');
                    this.disabled = false;
                    this.querySelector('span').textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('리뷰 삭제 중 오류가 발생했습니다.');
                this.disabled = false;
                this.querySelector('span').textContent = originalText;
            });
        });
    }
});
</script>

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

/* MNO 리뷰 삭제 버튼 스타일 */
.mno-review-btn-delete {
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

.mno-review-btn-delete:hover {
    background: #fecaca;
    color: #b91c1c;
    transform: translateY(-1px);
}

.mno-review-btn-delete:active {
    transform: translateY(0);
}
</style>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

