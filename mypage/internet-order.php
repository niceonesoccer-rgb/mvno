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

// 포인트 설정 및 함수 포함
require_once '../includes/data/point-settings.php';
require_once '../includes/data/product-functions.php';
require_once '../includes/data/db-config.php';
require_once '../includes/data/review-settings.php';
require_once '../includes/data/plan-data.php';

// DB에서 실제 신청 내역 가져오기
$internets = getUserInternetApplications($user_id);

// 디버깅: 데이터 확인
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
if ($debug_mode) {
    $pdo = getDBConnection();
    if ($pdo) {
        // 1. user_id 확인
        $debug_info = [
            'user_id' => $user_id,
            'current_user' => $currentUser,
            'internets_count' => count($internets),
            'internets_data' => $internets
        ];
        
        // 2. 데이터베이스에서 직접 확인
        try {
            // application_customers에서 user_id로 검색
            $stmt1 = $pdo->prepare("SELECT COUNT(*) as cnt FROM application_customers WHERE user_id = ?");
            $stmt1->execute([$user_id]);
            $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
            $debug_info['application_customers_count'] = $result1['cnt'] ?? 0;
            
            // product_applications에서 internet 타입 검색
            $stmt2 = $pdo->prepare("
                SELECT COUNT(*) as cnt 
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                WHERE c.user_id = ? AND a.product_type = 'internet'
            ");
            $stmt2->execute([$user_id]);
            $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
            $debug_info['internet_applications_count'] = $result2['cnt'] ?? 0;
            
            // 전체 신청 내역 확인 (user_id NULL 포함)
            $stmt3 = $pdo->prepare("
                SELECT a.id, a.product_id, a.product_type, a.application_status, a.created_at, c.user_id, c.name, c.phone
                FROM product_applications a
                LEFT JOIN application_customers c ON a.id = c.application_id
                WHERE a.product_type = 'internet'
                ORDER BY a.created_at DESC
                LIMIT 10
            ");
            $stmt3->execute();
            $debug_info['all_internet_applications'] = $stmt3->fetchAll(PDO::FETCH_ASSOC);
            
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
                AND a.product_type = 'internet'
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
// 인터넷 리뷰 모달 포함
include '../includes/components/internet-review-modal.php';
// 인터넷 리뷰 삭제 모달 포함
include '../includes/components/internet-review-delete-modal.php';
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
                        <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 인터넷</h2>
                    </div>
                    <p style="font-size: 14px; color: #6b7280; margin: 0; margin-left: 36px;">카드를 클릭하면 신청 정보를 확인할 수 있습니다.</p>
                </div>

                <!-- 디버깅 정보 (debug=1 파라미터가 있을 때만 표시) -->
                <?php if ($debug_mode && isset($debug_info)): ?>
                    <div style="padding: 20px; background: #f3f4f6; border-radius: 8px; margin-bottom: 20px; font-family: monospace; font-size: 12px;">
                        <h3 style="margin-top: 0; color: #6366f1;">디버깅 정보</h3>
                        <pre style="background: white; padding: 15px; border-radius: 4px; overflow-x: auto;"><?php echo htmlspecialchars(json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                    </div>
                <?php endif; ?>
                
                <!-- 신청한 인터넷 목록 -->
                <div style="margin-bottom: 32px;">
                    <?php if (empty($internets)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: #6b7280;">
                            신청한 인터넷이 없습니다.
                            <?php if (!$debug_mode): ?>
                                <br><br>
                                <a href="?debug=1" style="color: #6366f1; text-decoration: underline; font-size: 12px;">디버깅 정보 보기</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($internets as $index => $internet): ?>
                                <div class="internet-item application-card" 
                                     data-index="<?php echo $index; ?>" 
                                     data-internet-id="<?php echo $internet['id']; ?>" 
                                     data-application-id="<?php echo htmlspecialchars($internet['application_id'] ?? ''); ?>"
                                     style="<?php echo $index >= 10 ? 'display: none;' : ''; ?> padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"
                                     onmouseover="this.style.borderColor='#6366f1'; this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.15)'"
                                     onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'">
                                    
                                    <!-- 상단: 통신사 로고 및 정보 -->
                                    <div style="margin-bottom: 16px; position: relative;">
                                        <!-- 주문일자 (상단 오른쪽) -->
                                        <?php
                                        $orderDate = $internet['order_date'] ?? '';
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
                                        
                                        <?php
                                        // 통신사 로고 경로 설정
                                        $provider = $internet['provider'] ?? '';
                                        
                                        // 디버깅: provider 값 확인
                                        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                                            error_log("mypage/internet-order.php - application_id: " . ($internet['application_id'] ?? 'unknown') . ", provider: '" . $provider . "'");
                                        }
                                        
                                        $logoUrl = '';
                                        // 정확한 매칭: 더 구체적인 값 우선 확인
                                        // 순서 중요: "SKT"를 "KT"보다 먼저 확인해야 함 (SKT에 KT가 포함되어 있음)
                                        if (stripos($provider, 'KT skylife') !== false || stripos($provider, 'KTskylife') !== false) {
                                            // "KT skylife" -> ktskylife.svg
                                            $logoUrl = '/MVNO/assets/images/internets/ktskylife.svg';
                                        } elseif (stripos($provider, 'SKT') !== false || stripos($provider, 'SK broadband') !== false || stripos($provider, 'SK') !== false) {
                                            // "SKT", "SK broadband", "SK" -> broadband.svg (SKT broadband)
                                            // "SKT"를 "KT"보다 먼저 확인 (SKT에 KT가 포함되어 있으므로)
                                            $logoUrl = '/MVNO/assets/images/internets/broadband.svg';
                                        } elseif (stripos($provider, 'KT') !== false) {
                                            // "KT" (skylife, SKT 제외) -> kt.svg
                                            $logoUrl = '/MVNO/assets/images/internets/kt.svg';
                                        } elseif (stripos($provider, 'LG') !== false || stripos($provider, 'LGU') !== false) {
                                            $logoUrl = '/MVNO/assets/images/internets/lgu.svg';
                                        }
                                        
                                        // provider 텍스트 변환: "SKT" -> "SKT broadband"
                                        $displayProvider = $provider;
                                        if (stripos($provider, 'SKT') !== false && stripos($provider, 'broadband') === false) {
                                            $displayProvider = 'SKT broadband';
                                        }
                                        ?>
                                        <?php if ($logoUrl): ?>
                                            <div style="margin-bottom: 8px;">
                                                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($displayProvider); ?>" style="height: 32px; object-fit: contain;">
                                                <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                                                    <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">[디버그: provider="<?php echo htmlspecialchars($provider); ?>"]</div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <!-- provider 텍스트는 항상 표시 (신청 시점 정보 명확히 표시) -->
                                        <div style="font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 12px; <?php echo $logoUrl ? 'margin-top: 4px;' : ''; ?>">
                                            <?php echo htmlspecialchars($displayProvider ?: '인터넷'); ?>
                                            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                                                <div style="font-size: 11px; color: #6b7280; margin-top: 4px; font-weight: normal;">[디버그: provider="<?php echo htmlspecialchars($provider); ?>"]</div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div style="font-size: 16px; color: #374151; margin-bottom: 8px;">
                                            <?php echo htmlspecialchars($internet['speed'] ?? ''); ?> <?php echo htmlspecialchars($internet['plan_name'] ?? ''); ?>
                                        </div>
                                        
                                        <div style="font-size: 16px; color: #1f2937; font-weight: 600;">
                                            <?php 
                                            $displayPrice = $internet['price'] ?? '';
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
                                            <?php if (!empty($internet['order_number'])): ?>
                                                <span style="color: #6b7280;">주문번호</span>
                                                <span style="color: #374151; font-weight: 600;"><?php echo htmlspecialchars($internet['order_number']); ?></span>
                                            <?php elseif (!empty($internet['application_id'])): ?>
                                                <span style="color: #6b7280;">신청번호</span>
                                                <span style="color: #374151; font-weight: 600;">#<?php echo htmlspecialchars($internet['application_id']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                        <?php 
                                        // 상태별 배지 색상 설정 (함수에서 반환하는 application_status 사용)
                                        $statusBg = '#eef2ff'; // 기본 파란색
                                        $statusColor = '#6366f1';
                                        
                                        // 상태별 배지 색상 설정 (internet.php의 옵션과 동일한 상태값 사용)
                                        $appStatus = $internet['application_status'] ?? '';
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
                                        
                                        if (!empty($internet['status'])): ?>
                                            <div style="display: inline-flex; align-items: center; padding: 6px 12px; background: <?php echo $statusBg; ?>; border-radius: 6px;">
                                                <span style="font-size: 13px; color: <?php echo $statusColor; ?>; font-weight: 600;"><?php echo htmlspecialchars($internet['status']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div style="display: inline-flex; align-items: center; padding: 6px 12px; background: #f3f4f6; border-radius: 6px;">
                                                <span style="font-size: 13px; color: #6b7280; font-weight: 600;">상태 없음</span>
                                            </div>
                                        <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- 판매자 정보 및 리뷰 작성 영역 -->
                                    <?php
                                    $appStatus = $internet['application_status'] ?? '';
                                    // getUserInternetApplications에서 반환하는 배열의 'id' 키에 product_id 값이 들어있음
                                    $productId = $internet['id'] ?? ($internet['product_id'] ?? 0);
                                    $applicationId = $internet['application_id'] ?? '';
                                    
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
                                                // 판매자 정보 가져오기 (plan-data.php는 이미 상단에서 포함됨)
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
                                    
                                    // 설치완료 또는 종료 상태일 때만 리뷰 버튼 표시
                                    $canWrite = in_array($appStatus, ['installation_completed', 'activation_completed', 'completed', 'closed', 'terminated']);
                                    $hasReview = false;
                                    $buttonText = '리뷰 작성';
                                    $buttonClass = 'internet-review-write-btn';
                                    $buttonBgColor = '#EF4444';
                                    $buttonHoverColor = '#dc2626';
                                    
                                    if ($canWrite) {
                                        $hasReview = $internet['has_review'] ?? false;
                                        $buttonText = $hasReview ? '리뷰 수정' : '리뷰 작성';
                                        $buttonClass = $hasReview ? 'internet-review-edit-btn' : 'internet-review-write-btn';
                                        $buttonBgColor = $hasReview ? '#6b7280' : '#EF4444';
                                        $buttonHoverColor = $hasReview ? '#4b5563' : '#dc2626';
                                    }
                                    
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
                                                
                                                <!-- 오른쪽: 리뷰 작성 버튼 (설치완료/종료 상태일 때만 표시) -->
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
                <?php if (count($internets) > 10): ?>
                <div style="margin-top: 32px; margin-bottom: 32px;" id="moreButtonContainer">
                    <button class="plan-review-more-btn" id="moreInternetsBtn" style="width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                        더보기 (<?php 
                        $remaining = count($internets) - 10;
                        echo $remaining > 10 ? 10 : $remaining;
                        ?>개)
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- 신청 상세 정보 모달 -->
<div id="applicationDetailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 10000; overflow-y: auto; padding: 20px;">
    <div style="max-width: 800px; margin: 40px auto; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); position: relative;">
        <!-- 모달 헤더 -->
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 24px; font-weight: bold; margin: 0; color: #1f2937;">상품 정보</h2>
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

.product-modal-body {
    padding: 24px;
}

.product-info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.product-info-table th {
    background: #f9fafb;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border: 1px solid #e5e7eb;
    width: 30%;
}

.product-info-table td {
    padding: 12px;
    border: 1px solid #e5e7eb;
    color: #1f2937;
}

.product-info-table tr:nth-child(even) {
    background: #f9fafb;
}

/* 리뷰 작성 영역 반응형 스타일 */
.review-section-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* 전화번호 반응형 스타일 */
.phone-inquiry-pc {
    display: block;
}

.phone-inquiry-mobile {
    display: none !important;
}

@media (max-width: 768px) {
    .review-section-layout {
        grid-template-columns: 1fr;
    }
    
    .phone-inquiry-pc {
        display: none;
    }
    
    .phone-inquiry-mobile {
        display: flex !important;
    }
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
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    // 배경 클릭 시 모달 닫기
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'block') {
            closeModal();
        }
    });
    
    function openModal(applicationId) {
        if (!modal || !modalContent) return;
        
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
                modalContent.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc2626;">
                        <p>정보를 불러오는 중 오류가 발생했습니다.</p>
                        <p style="font-size: 14px; margin-top: 8px;">네트워크 오류가 발생했습니다.</p>
                    </div>
                `;
            });
    }
    
    function closeModal() {
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = ''; // 배경 스크롤 복원
    }
    
    function displayApplicationDetails(data) {
        if (!modalContent) return;
        
        const customer = data.customer || {};
        const additionalInfo = data.additional_info || {};
        const productSnapshot = additionalInfo.product_snapshot || {};
        
        let html = '<div class="product-modal-body">';
        
        // 통신사 로고 표시 (신청 시점 정보 사용)
        const registrationPlace = productSnapshot.registration_place || '';
        let logoUrl = '';
        let displayProvider = registrationPlace;
        
        // 정확한 매칭: 더 구체적인 값 우선 확인
        // 순서 중요: "SKT"를 "KT"보다 먼저 확인해야 함 (SKT에 KT가 포함되어 있음)
        if (registrationPlace.toLowerCase().indexOf('kt skylife') !== -1 || registrationPlace.toLowerCase().indexOf('ktskylife') !== -1) {
            // "KT skylife" -> ktskylife.svg
            logoUrl = '/MVNO/assets/images/internets/ktskylife.svg';
        } else if (registrationPlace.indexOf('SKT') !== -1 || registrationPlace.toLowerCase().indexOf('sk broadband') !== -1 || registrationPlace.indexOf('SK') !== -1) {
            // "SKT", "SK broadband", "SK" -> broadband.svg (SKT broadband)
            // "SKT"를 "KT"보다 먼저 확인 (SKT에 KT가 포함되어 있으므로)
            logoUrl = '/MVNO/assets/images/internets/broadband.svg';
        } else if (registrationPlace.indexOf('KT') !== -1) {
            // "KT" (skylife, SKT 제외) -> kt.svg
            logoUrl = '/MVNO/assets/images/internets/kt.svg';
        } else if (registrationPlace.indexOf('LG') !== -1 || registrationPlace.indexOf('LGU') !== -1) {
            logoUrl = '/MVNO/assets/images/internets/lgu.svg';
        }
        
        // provider 텍스트 변환: "SKT" -> "SKT broadband"
        if (registrationPlace.indexOf('SKT') !== -1 && registrationPlace.toLowerCase().indexOf('broadband') === -1) {
            displayProvider = 'SKT broadband';
        }
        
        if (logoUrl) {
            html += `<div style="margin-bottom: 12px;"><img src="${escapeHtml(logoUrl)}" alt="${escapeHtml(displayProvider)}" style="height: 40px; object-fit: contain;"></div>`;
        }
        // provider 텍스트도 명확히 표시
        if (displayProvider) {
            html += `<div style="font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 16px;">${escapeHtml(displayProvider)}</div>`;
        }
        
        // 기본 정보 테이블
        let serviceTypeDisplay = productSnapshot.service_type || '';
        if (serviceTypeDisplay === '인터넷+TV') {
            serviceTypeDisplay = '인터넷 + TV 결합';
        } else if (serviceTypeDisplay === '인터넷+TV+핸드폰') {
            serviceTypeDisplay = '인터넷 + TV + 핸드폰 결합';
        }
        
        html += '<table class="product-info-table">';
        html += '<tbody>';
        
        if (productSnapshot.registration_place) {
            html += `<tr><th>신청 인터넷 회선</th><td>${escapeHtml(productSnapshot.registration_place)}</td></tr>`;
        }
        
        if (serviceTypeDisplay) {
            html += `<tr><th>결합여부</th><td>${escapeHtml(serviceTypeDisplay)}</td></tr>`;
        }
        
        if (productSnapshot.speed_option) {
            html += `<tr><th>가입 속도</th><td>${escapeHtml(productSnapshot.speed_option)}</td></tr>`;
        }
        
        if (productSnapshot.monthly_fee !== undefined && productSnapshot.monthly_fee !== null) {
            html += `<tr><th>월 요금제</th><td>${formatNumber(productSnapshot.monthly_fee)}원</td></tr>`;
        }
        
        // 기존 인터넷 통신사 정보 (기본 정보 테이블에 포함)
        const existingCompany = additionalInfo.currentCompany || additionalInfo.existing_company || additionalInfo.existingCompany || '';
        if (existingCompany) {
            html += `<tr><th>기존 인터넷 통신사</th><td>${escapeHtml(existingCompany)}</td></tr>`;
        }
        
        html += '</tbody></table>';
        
        // 현금지급 섹션
        if (productSnapshot.cash_payment_names) {
            let cashNames = [];
            let cashPrices = [];
            
            // JSON 문자열 파싱
            if (typeof productSnapshot.cash_payment_names === 'string') {
                try {
                    cashNames = JSON.parse(productSnapshot.cash_payment_names) || [];
                } catch(e) {
                    cashNames = [productSnapshot.cash_payment_names];
                }
            } else if (Array.isArray(productSnapshot.cash_payment_names)) {
                cashNames = productSnapshot.cash_payment_names;
            }
            
            if (productSnapshot.cash_payment_prices) {
                if (typeof productSnapshot.cash_payment_prices === 'string') {
                    try {
                        cashPrices = JSON.parse(productSnapshot.cash_payment_prices) || [];
                    } catch(e) {
                        cashPrices = [productSnapshot.cash_payment_prices];
                    }
                } else if (Array.isArray(productSnapshot.cash_payment_prices)) {
                    cashPrices = productSnapshot.cash_payment_prices;
                }
            }
            
            if (cashNames.length > 0) {
                html += '<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">현금지급</h3>';
                html += '<table class="product-info-table"><tbody>';
                for (let i = 0; i < cashNames.length; i++) {
                    const price = cashPrices[i] || '';
                    html += `<tr><th>${escapeHtml(cashNames[i])}</th><td>${escapeHtml(price)}</td></tr>`;
                }
                html += '</tbody></table>';
            }
        }
        
        // 상품권 지급 섹션
        if (productSnapshot.gift_card_names) {
            let giftNames = [];
            let giftPrices = [];
            
            // JSON 문자열 파싱
            if (typeof productSnapshot.gift_card_names === 'string') {
                try {
                    giftNames = JSON.parse(productSnapshot.gift_card_names) || [];
                } catch(e) {
                    giftNames = [productSnapshot.gift_card_names];
                }
            } else if (Array.isArray(productSnapshot.gift_card_names)) {
                giftNames = productSnapshot.gift_card_names;
            }
            
            if (productSnapshot.gift_card_prices) {
                if (typeof productSnapshot.gift_card_prices === 'string') {
                    try {
                        giftPrices = JSON.parse(productSnapshot.gift_card_prices) || [];
                    } catch(e) {
                        giftPrices = [productSnapshot.gift_card_prices];
                    }
                } else if (Array.isArray(productSnapshot.gift_card_prices)) {
                    giftPrices = productSnapshot.gift_card_prices;
                }
            }
            
            if (giftNames.length > 0) {
                html += '<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">상품권 지급</h3>';
                html += '<table class="product-info-table"><tbody>';
                for (let i = 0; i < giftNames.length; i++) {
                    const price = giftPrices[i] || '';
                    html += `<tr><th>${escapeHtml(giftNames[i])}</th><td>${escapeHtml(price)}</td></tr>`;
                }
                html += '</tbody></table>';
            }
        }
        
        // 장비 제공 섹션
        if (productSnapshot.equipment_names) {
            let equipmentNames = [];
            let equipmentPrices = [];
            
            // JSON 문자열 파싱
            if (typeof productSnapshot.equipment_names === 'string') {
                try {
                    equipmentNames = JSON.parse(productSnapshot.equipment_names) || [];
                } catch(e) {
                    equipmentNames = [productSnapshot.equipment_names];
                }
            } else if (Array.isArray(productSnapshot.equipment_names)) {
                equipmentNames = productSnapshot.equipment_names;
            }
            
            if (productSnapshot.equipment_prices) {
                if (typeof productSnapshot.equipment_prices === 'string') {
                    try {
                        equipmentPrices = JSON.parse(productSnapshot.equipment_prices) || [];
                    } catch(e) {
                        equipmentPrices = [productSnapshot.equipment_prices];
                    }
                } else if (Array.isArray(productSnapshot.equipment_prices)) {
                    equipmentPrices = productSnapshot.equipment_prices;
                }
            }
            
            if (equipmentNames.length > 0) {
                html += '<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">장비 제공</h3>';
                html += '<table class="product-info-table"><tbody>';
                for (let i = 0; i < equipmentNames.length; i++) {
                    const price = equipmentPrices[i] || '';
                    html += `<tr><th>${escapeHtml(equipmentNames[i])}</th><td>${escapeHtml(price)}</td></tr>`;
                }
                html += '</tbody></table>';
            }
        }
        
        // 설치 및 기타 서비스 섹션
        if (productSnapshot.installation_names) {
            let installationNames = [];
            let installationPrices = [];
            
            // JSON 문자열 파싱
            if (typeof productSnapshot.installation_names === 'string') {
                try {
                    installationNames = JSON.parse(productSnapshot.installation_names) || [];
                } catch(e) {
                    installationNames = [productSnapshot.installation_names];
                }
            } else if (Array.isArray(productSnapshot.installation_names)) {
                installationNames = productSnapshot.installation_names;
            }
            
            if (productSnapshot.installation_prices) {
                if (typeof productSnapshot.installation_prices === 'string') {
                    try {
                        installationPrices = JSON.parse(productSnapshot.installation_prices) || [];
                    } catch(e) {
                        installationPrices = [productSnapshot.installation_prices];
                    }
                } else if (Array.isArray(productSnapshot.installation_prices)) {
                    installationPrices = productSnapshot.installation_prices;
                }
            }
            
            if (installationNames.length > 0) {
                html += '<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">설치 및 기타 서비스</h3>';
                html += '<table class="product-info-table"><tbody>';
                for (let i = 0; i < installationNames.length; i++) {
                    const price = installationPrices[i] || '';
                    html += `<tr><th>${escapeHtml(installationNames[i])}</th><td>${escapeHtml(price)}</td></tr>`;
                }
                html += '</tbody></table>';
            }
        }
        
        html += '</div>';
        
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
    const moreBtn = document.getElementById('moreInternetsBtn');
    const internetItems = document.querySelectorAll('.internet-item');
    let visibleCount = 10;
    const totalInternets = internetItems.length;
    const loadCount = 10;

    function updateButtonText() {
        if (!moreBtn) return;
        const remaining = totalInternets - visibleCount;
        if (remaining > 0) {
            const showCount = remaining > loadCount ? loadCount : remaining;
            moreBtn.textContent = `더보기 (${showCount}개)`;
        }
    }

    if (moreBtn && totalInternets > 10) {
        updateButtonText();
        
        moreBtn.addEventListener('click', function() {
            const endCount = Math.min(visibleCount + loadCount, totalInternets);
            for (let i = visibleCount; i < endCount; i++) {
                if (internetItems[i]) {
                    internetItems[i].style.display = 'block';
                }
            }
            
            visibleCount = endCount;
            
            if (visibleCount >= totalInternets) {
                const moreButtonContainer = document.getElementById('moreButtonContainer');
                if (moreButtonContainer) {
                    moreButtonContainer.style.display = 'none';
                }
            } else {
                updateButtonText();
            }
        });
    } else if (moreBtn && totalInternets <= 10) {
        const moreButtonContainer = document.getElementById('moreButtonContainer');
        if (moreButtonContainer) {
            moreButtonContainer.style.display = 'none';
        }
    }
    
    // 인터넷 리뷰 작성/수정 기능
    const reviewWriteButtons = document.querySelectorAll('.internet-review-write-btn, .internet-review-edit-btn');
    const reviewModal = document.getElementById('internetReviewModal');
    const reviewForm = document.getElementById('internetReviewForm');
    const reviewModalClose = reviewModal ? reviewModal.querySelector('.internet-review-modal-close') : null;
    const reviewModalOverlay = reviewModal ? reviewModal.querySelector('.internet-review-modal-overlay') : null;
    const reviewCancelBtn = reviewForm ? reviewForm.querySelector('.internet-review-btn-cancel') : null;
    
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
                // 먼저 모달 제목과 버튼 텍스트를 설정 (모달이 보이기 전에)
                const modalTitle = reviewModal.querySelector('.internet-review-modal-title');
                if (modalTitle) {
                    modalTitle.textContent = isEditMode ? '리뷰 수정' : '리뷰 작성';
                }
                
                // 제출 버튼 텍스트 변경
                const submitBtn = reviewForm ? reviewForm.querySelector('.internet-review-btn-submit span') : null;
                if (submitBtn) {
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                }
                
                // 삭제 버튼 표시/숨김
                const deleteBtn = document.getElementById('internetReviewDeleteBtn');
                if (deleteBtn) {
                    deleteBtn.style.display = isEditMode ? 'flex' : 'none';
                }
                
                // 삭제 버튼에 리뷰 ID 저장
                if (deleteBtn && isEditMode && currentReviewId) {
                    deleteBtn.setAttribute('data-review-id', currentReviewId);
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
                    // 텍스트 카운터 초기화
                    const reviewTextCounter = document.getElementById('reviewTextCounter');
                    if (reviewTextCounter) {
                        reviewTextCounter.textContent = '0';
                    }
                }
                
                // 모달 표시
                reviewModal.style.display = 'flex';
                setTimeout(() => {
                    reviewModal.classList.add('show');
                }, 10);
                
                // 수정 모드인 경우 기존 리뷰 데이터 로드
                if (isEditMode && currentReviewProductId) {
                    loadExistingReview(currentReviewProductId);
                }
            }
        });
    });
    
    // 기존 리뷰 데이터 로드
    function loadExistingReview(productId) {
        // application_id가 있으면 application_id 기반으로 조회, 없으면 product_id로 조회
        let url = '/MVNO/api/get-review.php?product_id=' + productId + '&product_type=internet';
        if (currentReviewApplicationId) {
            url += '&application_id=' + currentReviewApplicationId;
            // 또는 get-review-by-application.php 사용 (더 명확함)
            // url = '/MVNO/api/get-review-by-application.php?application_id=' + currentReviewApplicationId + '&product_id=' + productId + '&product_type=internet';
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.review) {
                    const review = data.review;
                    currentReviewId = review.id;
                    
                    // 삭제 버튼에 리뷰 ID 저장 및 표시
                    const deleteBtn = document.getElementById('internetReviewDeleteBtn');
                    if (deleteBtn) {
                        deleteBtn.setAttribute('data-review-id', review.id);
                        deleteBtn.style.display = 'flex';
                    }
                    
                    const kindnessRating = parseInt(review.kindness_rating || review.rating || 0);
                    const speedRating = parseInt(review.speed_rating || review.rating || 0);
                    
                    // 친절해요 별점 설정
                    const kindnessInput = reviewForm.querySelector(`input[name="kindness_rating"][value="${kindnessRating}"]`);
                    if (kindnessInput) {
                        kindnessInput.checked = true;
                        const kindnessLabels = Array.from(reviewForm.querySelectorAll('.internet-star-rating[data-rating-type="kindness"] .star-label'));
                        kindnessLabels.forEach((label, index) => {
                            if (index < kindnessRating) {
                                label.classList.add('active');
                            } else {
                                label.classList.remove('active');
                            }
                        });
                    }
                    
                    // 설치 빨라요 별점 설정
                    const speedInput = reviewForm.querySelector(`input[name="speed_rating"][value="${speedRating}"]`);
                    if (speedInput) {
                        speedInput.checked = true;
                        const speedLabels = Array.from(reviewForm.querySelectorAll('.internet-star-rating[data-rating-type="speed"] .star-label'));
                        speedLabels.forEach((label, index) => {
                            if (index < speedRating) {
                                label.classList.add('active');
                            } else {
                                label.classList.remove('active');
                            }
                        });
                    }
                    
                    // 리뷰 내용 설정
                    const reviewTextarea = document.getElementById('internetReviewText');
                    if (reviewTextarea && review.content) {
                        reviewTextarea.value = review.content;
                        // 텍스트 카운터 업데이트
                        const reviewTextCounter = document.getElementById('reviewTextCounter');
                        if (reviewTextCounter) {
                            const length = review.content.length;
                            reviewTextCounter.textContent = length;
                            if (length > 1000) {
                                reviewTextCounter.style.color = '#ef4444';
                            } else {
                                reviewTextCounter.style.color = '#6366f1';
                            }
                        }
                    }
                } else {
                    showMessageModal('리뷰를 불러올 수 없습니다.', 'warning');
                    // 작성 모드로 전환
                    isEditMode = false;
                    const modalTitle = reviewModal.querySelector('.internet-review-modal-title');
                    if (modalTitle) {
                        modalTitle.textContent = '리뷰 작성';
                    }
                    const submitBtn = reviewForm ? reviewForm.querySelector('.internet-review-btn-submit span') : null;
                    if (submitBtn) {
                        submitBtn.textContent = '작성하기';
                    }
                }
            })
            .catch(error => {
                showMessageModal('리뷰를 불러오는 중 오류가 발생했습니다.', 'error');
                isEditMode = false;
            });
    }
    
    // 리뷰 모달 닫기
    function closeReviewModal() {
        if (reviewModal) {
            reviewModal.classList.remove('show');
            setTimeout(() => {
                reviewModal.style.display = 'none';
                
                // 스크롤 위치 복원
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
                // 텍스트 카운터 초기화
                const reviewTextCounter = document.getElementById('reviewTextCounter');
                if (reviewTextCounter) {
                    reviewTextCounter.textContent = '0';
                }
                // 모달 상태 초기화
                currentReviewApplicationId = null;
                currentReviewProductId = null;
                currentReviewId = null;
                isEditMode = false;
                
                // 모달 제목 및 버튼 텍스트 초기화
                const modalTitle = reviewModal.querySelector('.internet-review-modal-title');
                if (modalTitle) {
                    modalTitle.textContent = '리뷰 작성';
                }
                const submitBtn = reviewForm ? reviewForm.querySelector('.internet-review-btn-submit span') : null;
                if (submitBtn) {
                    submitBtn.textContent = '작성하기';
                }
            }, 300);
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
        if (e.key === 'Escape' && reviewModal && reviewModal.classList.contains('show')) {
            closeReviewModal();
        }
    });
    
    // 별점 이벤트는 internet-review-modal.php 컴포넌트에서 처리됨
    
    // 리뷰 폼 제출
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const kindnessRatingInput = reviewForm.querySelector('input[name="kindness_rating"]:checked');
            const speedRatingInput = reviewForm.querySelector('input[name="speed_rating"]:checked');
            const reviewText = document.getElementById('internetReviewText') ? document.getElementById('internetReviewText').value.trim() : '';
            
            if (!kindnessRatingInput) {
                showMessageModal('친절해요 별점을 선택해주세요.', 'warning');
                return;
            }
            
            if (!speedRatingInput) {
                showMessageModal('설치 빨라요 별점을 선택해주세요.', 'warning');
                return;
            }
            
            if (!reviewText) {
                showMessageModal('리뷰 내용을 입력해주세요.', 'warning');
                return;
            }
            
            if (!currentReviewProductId || currentReviewProductId <= 0) {
                showMessageModal('상품 정보를 찾을 수 없습니다.', 'error');
                return;
            }
            
            const kindnessRating = parseInt(kindnessRatingInput.value);
            const speedRating = parseInt(speedRatingInput.value);
            const averageRating = Math.round((kindnessRating + speedRating) / 2);
            
            if (kindnessRating < 1 || kindnessRating > 5 || speedRating < 1 || speedRating > 5) {
                showMessageModal('별점은 1~5 사이의 값이어야 합니다.', 'error');
                return;
            }
            
            
            // 제출 버튼 비활성화 및 로딩 상태
            const submitBtn = reviewForm.querySelector('.internet-review-btn-submit');
            const originalBtnContent = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>' + (isEditMode ? '저장 중...' : '작성 중...') + '</span>';
            
            // 리뷰 제출 (수정 모드인 경우 review_id 포함)
            const formData = new FormData();
            formData.append('product_id', currentReviewProductId);
            formData.append('product_type', 'internet');
            formData.append('rating', averageRating);
            formData.append('kindness_rating', kindnessRating); // 친절해요 별점
            formData.append('speed_rating', speedRating); // 설치 빨라요 별점
            formData.append('content', reviewText);
            formData.append('title', ''); // 인터넷 리뷰는 제목 없음
            
            // application_id 추가 (각 신청별로 별도 리뷰 작성)
            if (currentReviewApplicationId) {
                formData.append('application_id', currentReviewApplicationId);
            }
            
            // 수정 모드인 경우 review_id 추가
            if (isEditMode && currentReviewId) {
                formData.append('review_id', currentReviewId);
            }
            
            fetch('/MVNO/api/submit-review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status, response.statusText);
                console.log('Response headers:', response.headers);
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('HTTP Error Response:', text);
                        throw new Error(`HTTP ${response.status}: ${text.substring(0, 200)}`);
                    });
                }
                return response.text().then(text => {
                    console.log('Raw response text:', text);
                    try {
                        const data = JSON.parse(text);
                        return data;
                    } catch (e) {
                        console.error('JSON 파싱 실패:', e);
                        console.error('Response text:', text);
                        throw new Error('서버 응답을 파싱할 수 없습니다: ' + text.substring(0, 200));
                    }
                });
            })
            .then(data => {
                console.log('Response data:', JSON.stringify(data, null, 2));
                if (data.success) {
                    // 성공 모달 표시
                    const successMessage = isEditMode ? '리뷰가 수정되었습니다.' : '리뷰가 작성되었습니다.';
                    showMessageModal(successMessage, 'success', function() {
                        closeReviewModal();
                        // 페이지 새로고침 (리뷰 작성/수정 버튼 상태 업데이트를 위해)
                        location.reload();
                    });
                } else {
                    // 에러 모달 표시
                    const errorMessage = data.message || (isEditMode ? '리뷰 수정에 실패했습니다.' : '리뷰 작성에 실패했습니다.');
                    console.error('리뷰 제출 실패:', JSON.stringify(data, null, 2));
                    console.error('Error message:', data.message);
                    console.error('Error details:', data.error);
                    showMessageModal(errorMessage, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnContent;
                }
            })
            .catch(error => {
                console.error('리뷰 제출 중 오류:', error);
                console.error('Error stack:', error.stack);
                const errorMessage = isEditMode ? '리뷰 수정 중 오류가 발생했습니다.' : '리뷰 작성 중 오류가 발생했습니다.';
                showMessageModal(errorMessage, 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
            });
        });
    }
    
    // 메시지 모달 표시 함수
    function showMessageModal(message, type, callback) {
        type = type || 'info'; // success, error, warning, info
        
        // 기존 모달이 있으면 제거
        const existingModal = document.getElementById('reviewMessageModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // 모달 생성
        const modal = document.createElement('div');
        modal.id = 'reviewMessageModal';
        modal.className = 'review-message-modal';
        
        // 아이콘 및 색상 설정
        let icon = '';
        let bgColor = '';
        let iconBg = '';
        
        switch(type) {
            case 'success':
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                bgColor = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                iconBg = '#10b981';
                break;
            case 'error':
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                bgColor = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
                iconBg = '#ef4444';
                break;
            case 'warning':
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                bgColor = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
                iconBg = '#f59e0b';
                break;
            default:
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 16H12V12H11M12 8H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                bgColor = 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)';
                iconBg = '#6366f1';
        }
        
        modal.innerHTML = `
            <div class="review-message-modal-overlay"></div>
            <div class="review-message-modal-content">
                <div class="review-message-modal-icon" style="background: ${iconBg};">
                    ${icon}
                </div>
                <div class="review-message-modal-body">
                    <h3 class="review-message-modal-title">${type === 'success' ? '작성 완료' : type === 'error' ? '오류 발생' : type === 'warning' ? '알림' : '알림'}</h3>
                    <p class="review-message-modal-text">${message}</p>
                </div>
                <div class="review-message-modal-footer">
                    <button class="review-message-modal-btn" style="background: ${bgColor};">
                        확인
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // 애니메이션
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
        
        // 닫기 이벤트
        const closeBtn = modal.querySelector('.review-message-modal-btn');
        const overlay = modal.querySelector('.review-message-modal-overlay');
        
        function closeMessageModal() {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.remove();
                if (callback && typeof callback === 'function') {
                    callback();
                }
            }, 300);
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', closeMessageModal);
        }
        
        if (overlay) {
            overlay.addEventListener('click', closeMessageModal);
        }
        
        // ESC 키로 닫기
        const escHandler = function(e) {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                closeMessageModal();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    }
    
    // 인터넷 리뷰 삭제 버튼 클릭 이벤트
    const deleteReviewBtn = document.getElementById('internetReviewDeleteBtn');
    const deleteModal = document.getElementById('internetReviewDeleteModal');
    
    // 삭제 모달 열기 함수
    function openDeleteModal(reviewId) {
        if (!deleteModal) {
            showMessageModal('삭제 모달을 찾을 수 없습니다.', 'error');
            return;
        }
        
        if (!reviewId) {
            showMessageModal('리뷰 정보를 찾을 수 없습니다.', 'error');
            return;
        }
        
        // 모달에 리뷰 ID 저장
        deleteModal.setAttribute('data-review-id', reviewId);
        
        // 스크롤 위치 저장
        const scrollY = window.scrollY;
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollY}px`;
        document.body.style.width = '100%';
        document.body.style.overflow = 'hidden';
        
        // 모달 표시
        deleteModal.style.display = 'flex';
        setTimeout(() => {
            deleteModal.classList.add('show');
        }, 10);
    }
    
    // 삭제 모달 닫기 함수
    function closeDeleteModal() {
        if (!deleteModal) return;
        
        deleteModal.classList.remove('show');
        setTimeout(() => {
            deleteModal.style.display = 'none';
            
            // 스크롤 위치 복원
            const scrollY = document.body.style.top;
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.body.style.overflow = '';
            if (scrollY) {
                window.scrollTo(0, parseInt(scrollY || '0') * -1);
            }
        }, 300);
    }
    
    // 삭제 버튼 클릭 이벤트
    if (deleteReviewBtn) {
        deleteReviewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const reviewId = this.getAttribute('data-review-id') || currentReviewId;
            openDeleteModal(reviewId);
        });
    }
    
    // 삭제 모달 이벤트 리스너
    if (deleteModal) {
        const deleteModalClose = deleteModal.querySelector('.internet-review-delete-modal-close');
        const deleteModalCancel = deleteModal.querySelector('.internet-review-delete-btn-cancel');
        const deleteModalConfirm = deleteModal.querySelector('.internet-review-delete-btn-confirm');
        const deleteModalOverlay = deleteModal.querySelector('.internet-review-delete-modal-overlay');
        
        // 닫기 버튼
        if (deleteModalClose) {
            deleteModalClose.addEventListener('click', closeDeleteModal);
        }
        
        // 취소 버튼
        if (deleteModalCancel) {
            deleteModalCancel.addEventListener('click', closeDeleteModal);
        }
        
        // 오버레이 클릭
        if (deleteModalOverlay) {
            deleteModalOverlay.addEventListener('click', closeDeleteModal);
        }
        
        // 확인 버튼 (삭제 실행)
        if (deleteModalConfirm) {
            deleteModalConfirm.addEventListener('click', function() {
                const reviewId = deleteModal.getAttribute('data-review-id');
                
                if (!reviewId) {
                    showMessageModal('리뷰 정보를 찾을 수 없습니다.', 'error');
                    return;
                }
                
                // 삭제 버튼 비활성화
                this.disabled = true;
                const originalText = this.textContent;
                this.textContent = '삭제 중...';
                
                const formData = new FormData();
                formData.append('review_id', reviewId);
                formData.append('product_type', 'internet');
                
                fetch('/MVNO/api/delete-review.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeDeleteModal();
                        showMessageModal('리뷰가 삭제되었습니다.', 'success', function() {
                            closeReviewModal();
                            location.reload();
                        });
                    } else {
                        showMessageModal(data.message || '리뷰 삭제에 실패했습니다.', 'error');
                        this.disabled = false;
                        this.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessageModal('리뷰 삭제 중 오류가 발생했습니다.', 'error');
                    this.disabled = false;
                    this.textContent = originalText;
                });
            });
        }
        
        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && deleteModal.style.display === 'flex') {
                closeDeleteModal();
            }
        });
    }
});
</script>

<style>
/* 메시지 모달 스타일 */
.review-message-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10002;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.3s;
    padding: 20px;
}

.review-message-modal.show {
    opacity: 1;
    visibility: visible;
}

.review-message-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 10002;
}

.review-message-modal-content {
    position: relative;
    background: #ffffff;
    border-radius: 20px;
    width: 100%;
    max-width: 400px;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    transform: scale(0.9) translateY(20px);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    z-index: 10003;
}

.review-message-modal.show .review-message-modal-content {
    transform: scale(1) translateY(0);
}

.review-message-modal-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 32px auto 24px;
    color: white;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.review-message-modal-body {
    padding: 0 32px 24px;
    text-align: center;
}

.review-message-modal-title {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 12px 0;
    letter-spacing: -0.02em;
}

.review-message-modal-text {
    font-size: 16px;
    color: #64748b;
    line-height: 1.6;
    margin: 0;
}

.review-message-modal-footer {
    width: 100%;
    padding: 0 32px 32px;
}

.review-message-modal-btn {
    width: 100%;
    padding: 16px 24px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    color: #ffffff;
    border: none;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.review-message-modal-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.review-message-modal-btn:active {
    transform: translateY(0);
}

@media (max-width: 640px) {
    .review-message-modal {
        padding: 0;
    }
    
    .review-message-modal-content {
        max-width: 100%;
        border-radius: 20px 20px 0 0;
        margin-top: auto;
    }
}
</style>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

