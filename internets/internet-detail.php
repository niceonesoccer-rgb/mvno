<?php
// 현재 페이지 설정
$current_page = 'internets';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true; // 상세 페이지에서도 하단 메뉴바 표시

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';
require_once '../includes/data/privacy-functions.php';

// 개인정보 설정 로드
$privacySettings = getPrivacySettings();

// 인터넷 상품 ID 가져오기
$internet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 헤더에 CSS 추가를 위한 플래그 설정
$add_inline_css = true;
// 헤더 포함
include '../includes/header.php';

if ($internet_id <= 0) {
    http_response_code(404);
    ?>
    <main class="main-content">
        <div class="content-layout" style="max-width: 600px; margin: 80px auto; padding: 40px 20px;">
            <div style="text-align: center;">
                <div style="width: 120px; height: 120px; margin: 0 auto 24px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1f2937; margin: 0 0 12px 0;">상품을 찾을 수 없습니다</h1>
                <p style="font-size: 16px; color: #6b7280; margin: 0 0 32px 0; line-height: 1.6;">요청하신 상품이 존재하지 않거나 삭제되었습니다.</p>
                <a href="/MVNO/internets/internets.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #3b82f6; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; transition: background 0.2s;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    인터넷 상품 목록으로
                </a>
            </div>
        </div>
    </main>
    <?php include '../includes/footer.php'; ?>
    <?php exit; ?>
    <?php
}

// 인터넷 상품 데이터 가져오기
require_once '../includes/data/plan-data.php';
$internet = getInternetDetailData($internet_id);
$rawData = $internet['_raw_data'] ?? []; // 원본 DB 데이터 (null 대신 빈 배열로 초기화)

// 관리자 여부 확인
$isAdmin = false;
try {
    if (function_exists('isAdmin') && function_exists('getCurrentUser')) {
        $currentUser = getCurrentUser();
        if ($currentUser) {
            $isAdmin = isAdmin($currentUser['user_id']);
        }
    }
} catch (Exception $e) {
    // 관리자 체크 실패 시 일반 사용자로 처리
}

// 상품이 없거나, 일반 사용자가 판매종료 상품에 접근하는 경우
if (!$internet) {
    http_response_code(404);
    ?>
    <main class="main-content">
        <div class="content-layout" style="max-width: 600px; margin: 80px auto; padding: 40px 20px;">
            <div style="text-align: center;">
                <div style="width: 120px; height: 120px; margin: 0 auto 24px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1f2937; margin: 0 0 12px 0;">상품을 찾을 수 없습니다</h1>
                <p style="font-size: 16px; color: #6b7280; margin: 0 0 32px 0; line-height: 1.6;">요청하신 상품이 존재하지 않거나 삭제되었습니다.</p>
                <a href="/MVNO/internets/internets.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #3b82f6; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; transition: background 0.2s;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    인터넷 상품 목록으로
                </a>
            </div>
        </div>
    </main>
    <?php include '../includes/footer.php'; ?>
    <?php exit; ?>
    <?php
}

// 일반 사용자가 판매종료 상품에 접근하는 경우 차단
if (!$isAdmin && isset($internet['status']) && $internet['status'] === 'inactive') {
    http_response_code(404);
    ?>
    <main class="main-content">
        <div class="content-layout" style="max-width: 600px; margin: 80px auto; padding: 40px 20px;">
            <div style="text-align: center;">
                <div style="width: 120px; height: 120px; margin: 0 auto 24px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1f2937; margin: 0 0 12px 0;">판매가 종료된 상품입니다</h1>
                <p style="font-size: 16px; color: #6b7280; margin: 0 0 32px 0; line-height: 1.6;">죄송합니다. 요청하신 상품은 현재 판매가 종료되어 더 이상 제공되지 않습니다.<br>다른 상품을 찾아보시겠어요?</p>
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    <a href="/MVNO/internets/internets.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #3b82f6; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.2s; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);" onmouseover="this.style.background='#2563eb'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.background='#3b82f6'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(59, 130, 246, 0.3)'">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M3 7l9 6 9-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        인터넷 상품 둘러보기
                    </a>
                    <a href="/MVNO/" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #f3f4f6; color: #374151; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        홈으로 가기
                    </a>
                </div>
            </div>
        </div>
    </main>
    <?php include '../includes/footer.php'; ?>
    <?php exit; ?>
    <?php
}

// 조회수 업데이트 (판매종료가 아닌 경우에만)
require_once '../includes/data/product-functions.php';
incrementProductView($internet_id);

// 리뷰 목록 가져오기 (같은 판매자의 같은 타입의 모든 상품 리뷰 통합)
$reviews = getProductReviews($internet_id, 'internet', 20);
$averageRating = getProductAverageRating($internet_id, 'internet');
$reviewCount = getProductReviewCount($internet_id, 'internet');

// 가입처별 아이콘 경로 매핑
// 상대 시간 표시 함수
function getRelativeTime($datetime) {
    if (empty($datetime)) {
        return '';
    }
    
    try {
        $reviewTime = new DateTime($datetime);
        $now = new DateTime();
        $diff = $now->diff($reviewTime);
        
        // 오늘인지 확인
        if ($diff->days === 0) {
            if ($diff->h === 0 && $diff->i === 0) {
                return '방금 전';
            } elseif ($diff->h === 0) {
                return $diff->i . '분 전';
            } else {
                return $diff->h . '시간 전';
            }
        }
        
        // 어제인지 확인
        if ($diff->days === 1) {
            return '어제';
        }
        
        // 일주일 전까지
        if ($diff->days < 7) {
            return $diff->days . '일 전';
        }
        
        // 한달 전까지 (30일)
        if ($diff->days < 30) {
            $weeks = floor($diff->days / 7);
            return $weeks . '주 전';
        }
        
        // 일년 전까지 (365일)
        if ($diff->days < 365) {
            $months = floor($diff->days / 30);
            return $months . '개월 전';
        }
        
        // 일년 이상
        $years = floor($diff->days / 365);
        return $years . '년 전';
    } catch (Exception $e) {
        return '';
    }
}

function getInternetIconPath($registrationPlace) {
    $iconMap = [
        'KT' => '/MVNO/assets/images/internets/kt.svg',
        'SKT' => '/MVNO/assets/images/internets/broadband.svg',
        'LG U+' => '/MVNO/assets/images/internets/lgu.svg',
        'KT skylife' => '/MVNO/assets/images/internets/ktskylife.svg',
        'LG헬로비전' => '/MVNO/assets/images/internets/hellovision.svg',
        'BTV' => '/MVNO/assets/images/internets/btv.svg',
        'DLIVE' => '/MVNO/assets/images/internets/dlive.svg',
    ];
    return $iconMap[$registrationPlace] ?? '';
}

$iconPath = getInternetIconPath($internet['registration_place']);
$serviceType = $internet['service_type'] ?? '인터넷';
$serviceTypeDisplay = $serviceType;
if ($serviceType === '인터넷+TV') {
    $serviceTypeDisplay = '인터넷 + TV 결합';
} elseif ($serviceType === '인터넷+TV+핸드폰') {
    $serviceTypeDisplay = '인터넷 + TV + 핸드폰 결합';
}
?>

<main class="main-content">
    <!-- 인터넷 상품 정보 카드 -->
    <div class="PlanDetail_content_wrapper__0YNeJ">
        <div class="tw-w-full">
            <div class="css-2l6pil e1ebrc9o0">
                <div>
                    <div class="css-58gch7 e82z5mt0" data-product-id="<?php echo $internet_id; ?>">
                        <div class="css-1kjyj6z e82z5mt1">
                            <?php if ($iconPath): ?>
                                <img data-testid="internet-company-logo" src="<?php echo htmlspecialchars($iconPath); ?>" 
                                     alt="<?php echo htmlspecialchars($internet['registration_place']); ?>" 
                                     class="css-1pg8bi e82z5mt15"
                                     style="<?php echo ($internet['registration_place'] === 'KT') ? 'height: 24px;' : (($internet['registration_place'] === 'DLIVE') ? 'height: 35px; object-fit: cover;' : 'max-height: 40px; object-fit: contain;'); ?>">
                            <?php else: ?>
                                <span><?php echo htmlspecialchars($internet['registration_place']); ?></span>
                            <?php endif; ?>
                            <span style="margin-left: 0.5em; margin-right: 0.5em; font-size: 1.0584rem; color: #9ca3af;">|</span>
                            <span style="font-size: 1.0584rem; color: #6b7280; text-align: left; display: inline-block; white-space: nowrap;"><?php echo htmlspecialchars($serviceTypeDisplay); ?></span>
                            <div class="css-huskxe e82z5mt13" style="margin-left: auto;">
                                <div class="css-1fd5u73 e82z5mt14">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="2" y="3" width="20" height="14" rx="2" fill="#E9D5FF" stroke="#A855F7" stroke-width="1.5"/>
                                        <rect x="4" y="5" width="16" height="10" rx="1" fill="white"/>
                                        <rect x="2" y="17" width="20" height="4" rx="1" fill="#C084FC" stroke="#A855F7" stroke-width="1"/>
                                        <g transform="translate(17, -2) scale(1.5)">
                                            <path d="M0 0L-2 5H0L-1 10L2 5H0L0 0Z" fill="#6366F1" stroke="#4F46E5" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
                                        </g>
                                    </svg>
                                    <?php echo htmlspecialchars($internet['speed_option']); ?>
                                </div>
                                <div class="css-1fd5u73 e82z5mt14">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="userGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                                                <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:1" />
                                            </linearGradient>
                                        </defs>
                                        <circle cx="12" cy="8" r="4" fill="url(#userGradient)"/>
                                        <path d="M6 21c0-3.314 2.686-6 6-6s6 2.686 6 6" stroke="url(#userGradient)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?php echo number_format($internet['application_count'] ?? 0); ?>개 신청
                                </div>
                            </div>
                        </div>
                        <div class="css-174t92n e82z5mt7" style="display: flex; flex-direction: column; gap: 0.75rem; margin: 0.75rem 0 0.5rem 0; padding: 0.75rem; background-color: #f9fafb; border-radius: 0.5rem; width: 100%; box-sizing: border-box;">
                            <?php
                            // 현금 지급 혜택 표시
                            $cashNames = $internet['cash_payment_names'] ?? [];
                            $cashPrices = $internet['cash_payment_prices'] ?? [];
                            for ($i = 0; $i < count($cashNames); $i++):
                                if (!empty($cashNames[$i])):
                                    $priceRaw = isset($cashPrices[$i]) ? $cashPrices[$i] : '';
                                    // DB에 저장된 값이 "50000원" 형식이면 그대로 표시 (정수로 처리)
                                    if (!empty($priceRaw) && preg_match('/^(\d+)(.+)$/', $priceRaw, $matches)) {
                                        $priceDisplay = number_format((int)$matches[1]) . $matches[2];
                                        $hasPrice = true;
                                    } elseif (!empty($priceRaw) && is_numeric($priceRaw)) {
                                        $priceDisplay = number_format((int)$priceRaw) . '원';
                                        $hasPrice = true;
                                    } else {
                                        $hasPrice = false;
                                    }
                            ?>
                            <div class="css-12zfa6z e82z5mt8" style="display: flex; align-items: flex-start; gap: 0.5rem; width: 100%; box-sizing: border-box;">
                                <img src="/MVNO/assets/images/icons/cash.svg" alt="현금" class="css-xj5cz0 e82z5mt9" style="max-width: 4rem; max-height: 4rem; object-fit: contain; flex-shrink: 0; width: auto;">
                                <div class="css-0 e82z5mt10">
                                    <p class="css-2ht76o e82z5mt12 item-name-text">
                                        <?php echo htmlspecialchars($cashNames[$i]); ?>
                                    </p>
                                    <?php if ($hasPrice): ?>
                                        <p class="css-2ht76o e82z5mt12 item-price-text" style="margin-top: 1.28px;">
                                            <?php echo htmlspecialchars($priceDisplay); ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="css-2ht76o e82z5mt12 item-price-text" style="margin-top: 1.28px;">무료</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                                endif;
                            endfor;
                            
                            // 상품권 지급 혜택 표시
                            $giftNames = $internet['gift_card_names'] ?? [];
                            $giftPrices = $internet['gift_card_prices'] ?? [];
                            for ($i = 0; $i < count($giftNames); $i++):
                                if (!empty($giftNames[$i])):
                                    $priceRaw = isset($giftPrices[$i]) ? $giftPrices[$i] : '';
                                    // DB에 저장된 값이 "170000원" 형식이면 그대로 표시 (정수로 처리)
                                    if (!empty($priceRaw) && preg_match('/^(\d+)(.+)$/', $priceRaw, $matches)) {
                                        $priceDisplay = number_format((int)$matches[1]) . $matches[2];
                                        $hasPrice = true;
                                    } elseif (!empty($priceRaw) && is_numeric($priceRaw)) {
                                        $priceDisplay = number_format((int)$priceRaw) . '원';
                                        $hasPrice = true;
                                    } else {
                                        $hasPrice = false;
                                    }
                            ?>
                            <div class="css-12zfa6z e82z5mt8" style="display: flex; align-items: flex-start; gap: 0.5rem; width: 100%; box-sizing: border-box;">
                                <img src="/MVNO/assets/images/icons/gift-card.svg" alt="상품권" class="css-xj5cz0 e82z5mt9" style="max-width: 4rem; max-height: 4rem; object-fit: contain; flex-shrink: 0; width: auto;">
                                <div class="css-0 e82z5mt10">
                                    <p class="css-2ht76o e82z5mt12 item-name-text">
                                        <?php echo htmlspecialchars($giftNames[$i]); ?>
                                    </p>
                                    <?php if ($hasPrice): ?>
                                        <p class="css-2ht76o e82z5mt12 item-price-text" style="margin-top: 1.28px;">
                                            <?php echo htmlspecialchars($priceDisplay); ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="css-2ht76o e82z5mt12 item-price-text" style="margin-top: 1.28px;">무료</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                                endif;
                            endfor;
                            
                            // 장비 제공 혜택 표시
                            $equipNames = $internet['equipment_names'] ?? [];
                            $equipPrices = $internet['equipment_prices'] ?? [];
                            for ($i = 0; $i < count($equipNames); $i++):
                                if (!empty($equipNames[$i])):
                                    $priceText = isset($equipPrices[$i]) && !empty($equipPrices[$i]) ? $equipPrices[$i] : '';
                            ?>
                            <div class="css-12zfa6z e82z5mt8" style="display: flex; align-items: flex-start; gap: 0.5rem; width: 100%; box-sizing: border-box;">
                                <img src="/MVNO/assets/images/icons/equipment.svg" alt="장비" class="css-xj5cz0 e82z5mt9" style="max-width: 4rem; max-height: 4rem; object-fit: contain; flex-shrink: 0; width: auto;">
                                <div class="css-0 e82z5mt10">
                                    <p class="css-2ht76o e82z5mt12 item-name-text">
                                        <?php echo htmlspecialchars($equipNames[$i]); ?>
                                    </p>
                                    <?php if (!empty($priceText)): ?>
                                        <p class="css-2ht76o e82z5mt12" style="margin-top: 1.28px;">
                                            <?php echo htmlspecialchars($priceText); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                                endif;
                            endfor;
                            
                            // 설치 및 기타 서비스 혜택 표시
                            $installNames = $internet['installation_names'] ?? [];
                            $installPrices = $internet['installation_prices'] ?? [];
                            for ($i = 0; $i < count($installNames); $i++):
                                if (!empty($installNames[$i])):
                                    $priceText = isset($installPrices[$i]) && !empty($installPrices[$i]) ? $installPrices[$i] : '';
                            ?>
                            <div class="css-12zfa6z e82z5mt8" style="display: flex; align-items: flex-start; gap: 0.5rem; width: 100%; box-sizing: border-box;">
                                <img src="/MVNO/assets/images/icons/installation.svg" alt="설치" class="css-xj5cz0 e82z5mt9" style="max-width: 4rem; max-height: 4rem; object-fit: contain; flex-shrink: 0; width: auto;">
                                <div class="css-0 e82z5mt10">
                                    <p class="css-2ht76o e82z5mt12 item-name-text">
                                        <?php echo htmlspecialchars($installNames[$i]); ?>
                                    </p>
                                    <?php if (!empty($priceText)): ?>
                                        <p class="css-2ht76o e82z5mt12" style="margin-top: 1.28px;">
                                            <?php echo htmlspecialchars($priceText); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                                endif;
                            endfor;
                            ?>
                        </div>
                        
                        <!-- 프로모션 아코디언 -->
                        <?php
                        $promotionTitle = $internet['promotion_title'] ?? '';
                        $promotions = $internet['promotions'] ?? [];
                        $promotionCount = count(array_filter($promotions, function($p) { return !empty(trim($p)); }));
                        
                        if ($promotionCount > 0 || !empty($promotionTitle)):
                            // 아코디언 제목: 프로모션 제목이 있으면 사용, 없으면 기본 텍스트
                            $accordionTitle = '';
                            if (!empty($promotionTitle)) {
                                $accordionTitle = $promotionTitle;
                            } elseif ($promotionCount > 0) {
                                $accordionTitle = '프로모션 최대 ' . $promotionCount . '개';
                            }
                            
                            // 색상 배열 (무지개 순서: 빨강, 노랑, 초록, 파랑, 보라)
                            $giftColors = ['#EF4444', '#EAB308', '#10B981', '#3B82F6', '#8B5CF6'];
                            $giftTextColor = '#FFFFFF';
                        ?>
                        <div class="plan-accordion-box" style="margin-top: 0.5rem; padding: 0.75rem 0;">
                            <div class="plan-accordion">
                                <button type="button" class="plan-accordion-trigger" aria-expanded="false" style="padding: 12px 16px;">
                                    <div class="plan-gifts-accordion-content">
                                        <!-- 각 항목의 첫 글자를 원 안에 표시 -->
                                        <?php if ($promotionCount > 0): ?>
                                        <div class="plan-gifts-indicator-dots">
                                            <?php 
                                            $filteredPromotions = array_filter($promotions, function($p) { return !empty(trim($p)); });
                                            $index = 0;
                                            foreach ($filteredPromotions as $promotion): 
                                                $firstChar = mb_substr(trim($promotion), 0, 1, 'UTF-8'); // 첫 글자 추출
                                                // 색상 배열에서 순환하여 사용 (5개 이상일 경우 반복)
                                                $colorIndex = $index % count($giftColors);
                                                $bgColor = $giftColors[$colorIndex] ?? '#6366F1';
                                                $index++;
                                            ?>
                                                <span class="plan-gift-indicator-dot" style="background-color: <?php echo htmlspecialchars($bgColor); ?>;">
                                                    <span class="plan-gift-indicator-text" style="color: <?php echo htmlspecialchars($giftTextColor); ?>;"><?php echo htmlspecialchars($firstChar); ?></span>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                        <span class="plan-gifts-text-accordion"><?php echo htmlspecialchars($accordionTitle); ?></span>
                                    </div>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                        <path d="M6 9L12 15L18 9" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <div class="plan-accordion-content" style="display: none;">
                                    <div class="plan-gifts-detail-list">
                                        <?php if ($promotionCount > 0): ?>
                                            <?php foreach ($filteredPromotions as $promotion): ?>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text"><?php echo htmlspecialchars(trim($promotion)); ?></span>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="plan-gift-detail-item">
                                                <span class="plan-gift-detail-text">프로모션 정보 없음</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 신청하기 섹션 (모바일 하단 고정) -->
    <section class="plan-detail-apply-section">
        <div class="content-layout">
            <div class="plan-apply-content">
                <div class="plan-price-info">
                    <div class="plan-price-main">
                        <span class="plan-price-amount">월 <?php echo htmlspecialchars($internet['monthly_fee']); ?></span>
                    </div>
                </div>
                <button class="plan-apply-btn" id="internetMobileApplyBtn" data-apply-type="internet" data-internet-id="<?php echo $internet_id; ?>">신청하기</button>
            </div>
        </div>
    </section>
    
    <!-- 인터넷 리뷰 섹션 -->
    <?php
    // 정렬 방식 가져오기 (기본값: 최신순)
    $sort = $_GET['review_sort'] ?? 'created_desc';
    if (!in_array($sort, ['rating_desc', 'rating_asc', 'created_desc'])) {
        $sort = 'created_desc';
    }
    
    // 리뷰 목록 가져오기 (같은 판매자의 같은 타입의 모든 상품 리뷰 통합)
    // 모달에서 모든 리뷰를 표시하기 위해 충분히 많은 수를 가져옴
    $allReviews = getProductReviews($internet_id, 'internet', 1000, $sort);
    $reviews = array_slice($allReviews, 0, 5); // 페이지에는 처음 5개만 표시
    $averageRating = getProductAverageRating($internet_id, 'internet');
    $reviewCount = getProductReviewCount($internet_id, 'internet');
    $categoryAverages = getInternetReviewCategoryAverages($internet_id, 'internet');
    $hasReviews = $reviewCount > 0;
    $remainingCount = max(0, $reviewCount - 5); // 남은 리뷰 개수
    ?>
    <?php if ($hasReviews): ?>
    <section class="plan-review-section" id="internetReviewSection" style="margin-top: 2rem; padding: 2rem 0; background: #f9fafb;">
        <div class="PlanDetail_content_wrapper__0YNeJ">
            <div class="plan-review-header">
                <span class="plan-review-logo-text"><?php echo htmlspecialchars($internet['company_name'] ?? $internet['registration_place'] ?? '인터넷'); ?></span>
                <h2 class="section-title">리뷰</h2>
            </div>
            
            <?php if ($hasReviews): ?>
            <div class="plan-review-summary">
                <div class="plan-review-left">
                    <div class="plan-review-total-rating">
                        <div class="plan-review-total-rating-content">
                            <!-- 총 별점 앞에 별 하나만 표시 -->
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px;">
                                <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#EF4444"></path>
                            </svg>
                            <span class="plan-review-rating-score"><?php echo htmlspecialchars($averageRating > 0 ? number_format($averageRating, 1) : '0.0'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="plan-review-right">
                    <div class="plan-review-categories">
                        <div class="plan-review-category">
                            <span class="plan-review-category-label">친절해요</span>
                            <span class="plan-review-category-score"><?php echo htmlspecialchars($categoryAverages['kindness'] > 0 ? number_format($categoryAverages['kindness'], 1) : '0.0'); ?></span>
                            <div class="plan-review-stars">
                                <?php echo getPartialStarsFromRating($categoryAverages['kindness']); ?>
                            </div>
                        </div>
                        <div class="plan-review-category">
                            <span class="plan-review-category-label">설치 빨라요</span>
                            <span class="plan-review-category-score"><?php echo htmlspecialchars($categoryAverages['speed'] > 0 ? number_format($categoryAverages['speed'], 1) : '0.0'); ?></span>
                            <div class="plan-review-stars">
                                <?php echo getPartialStarsFromRating($categoryAverages['speed']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="plan-review-count-section">
                <div class="plan-review-count-sort-wrapper">
                    <span class="plan-review-count">총 <?php echo number_format($reviewCount); ?>개</span>
                    <div class="plan-review-sort-select-wrapper">
                        <select class="plan-review-sort-select" id="internetReviewSortSelect" aria-label="리뷰 정렬 방식 선택" onchange="window.location.href='?id=<?php echo $internet_id; ?>&review_sort=' + this.value">
                            <option value="rating_desc" <?php echo $sort === 'rating_desc' ? 'selected' : ''; ?>>높은 평점순</option>
                            <option value="rating_asc" <?php echo $sort === 'rating_asc' ? 'selected' : ''; ?>>낮은 평점순</option>
                            <option value="created_desc" <?php echo $sort === 'created_desc' ? 'selected' : ''; ?>>최신순</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="plan-review-list" id="internetReviewList">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="plan-review-item">
                            <div class="plan-review-item-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <?php 
                                    $authorName = htmlspecialchars($review['author_name'] ?? '익명');
                                    $provider = isset($review['provider']) && $review['provider'] ? htmlspecialchars($review['provider']) : '';
                                    $providerText = $provider ? ' | ' . $provider : '';
                                    ?>
                                    <span class="plan-review-author"><?php echo $authorName . $providerText; ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="plan-review-stars">
                                        <span><?php echo htmlspecialchars($review['stars'] ?? '★★★★★'); ?></span>
                                    </div>
                                    <?php if (!empty($review['created_at'])): ?>
                                        <span class="plan-review-time" style="font-size: 0.875rem; color: #6b7280;">
                                            <?php echo htmlspecialchars(getRelativeTime($review['created_at'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="plan-review-content"><?php 
                                $content = $review['content'] ?? '';
                                // 줄바꿈 문자들을 공백 하나로 변환 (기존 공백은 유지)
                                // \r\n을 먼저 공백으로 변환, 그 다음 \r, \n을 각각 공백으로 변환
                                $content = str_replace(["\r\n", "\r", "\n"], ' ', $content);
                                echo htmlspecialchars($content);
                            ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="plan-review-item">
                        <p class="plan-review-content" style="text-align: center; color: #9ca3af; padding: 40px 0;">등록된 리뷰가 없습니다.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($remainingCount > 0): ?>
                <button class="plan-review-more-btn" id="internetReviewMoreBtn" data-total-reviews="<?php echo $reviewCount; ?>" data-sort="<?php echo htmlspecialchars($sort); ?>">
                    리뷰 더보기 (<?php echo number_format($remainingCount); ?>개)
                </button>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<!-- 인터넷 리뷰 모달 포함 -->
<?php include '../includes/components/internet-review-modal.php'; ?>

<!-- 인터넷 리뷰 더보기 모달 -->
<div class="review-modal" id="internetReviewModal">
    <div class="review-modal-overlay" id="internetReviewModalOverlay"></div>
    <div class="review-modal-content">
        <div class="review-modal-header">
            <h3 class="review-modal-title">리뷰</h3>
            <button class="review-modal-close" aria-label="닫기" id="internetReviewModalClose">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="review-modal-body">
            <div class="review-modal-sort">
                <div class="review-modal-sort-wrapper">
                    <span class="review-modal-total">
                        총 <?php echo number_format($reviewCount); ?>개
                    </span>
                    <div class="review-modal-sort-select-wrapper">
                        <select class="review-modal-sort-select" id="internetReviewModalSortSelect" aria-label="리뷰 정렬 방식 선택">
                            <option value="rating_desc" <?php echo $sort === 'rating_desc' ? 'selected' : ''; ?>>높은 평점순</option>
                            <option value="rating_asc" <?php echo $sort === 'rating_asc' ? 'selected' : ''; ?>>낮은 평점순</option>
                            <option value="created_desc" <?php echo $sort === 'created_desc' ? 'selected' : ''; ?>>최신순</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="review-modal-list" id="internetReviewModalList">
                <?php if (!empty($allReviews)): ?>
                    <?php foreach ($allReviews as $review): ?>
                        <div class="review-modal-item">
                            <div class="review-modal-item-header">
                                <?php 
                                $authorName = htmlspecialchars($review['author_name'] ?? '익명');
                                $provider = isset($review['provider']) && $review['provider'] ? htmlspecialchars($review['provider']) : '';
                                $providerText = $provider ? ' | ' . $provider : '';
                                ?>
                                <span class="review-modal-author"><?php echo $authorName . $providerText; ?></span>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="review-modal-stars">
                                        <span><?php echo htmlspecialchars($review['stars'] ?? '★★★★★'); ?></span>
                                    </div>
                                    <?php if (!empty($review['created_at'])): ?>
                                        <span class="review-modal-time" style="font-size: 0.875rem; color: #6b7280;">
                                            <?php echo htmlspecialchars(getRelativeTime($review['created_at'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="review-modal-item-content"><?php 
                                $content = $review['content'] ?? '';
                                // 줄바꿈 문자들을 공백 하나로 변환 (기존 공백은 유지)
                                // \r\n을 먼저 공백으로 변환, 그 다음 \r, \n을 각각 공백으로 변환
                                $content = str_replace(["\r\n", "\r", "\n"], ' ', $content);
                                echo htmlspecialchars($content);
                            ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="review-modal-item">
                        <p class="review-modal-item-content" style="text-align: center; color: #868e96; padding: 40px 0;">등록된 리뷰가 없습니다.</p>
                    </div>
                <?php endif; ?>
            </div>
            <button class="review-modal-more-btn" id="internetReviewModalMoreBtn" style="display: none;">리뷰 더보기</button>
        </div>
    </div>
</div>

<style>
/* 인터넷 모달 스타일 */
.internet-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    overflow-y: auto;
}

.internet-modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* 데스크톱 전용 요소 */
.desktop-only {
    display: block;
}

@media (max-width: 767px) {
    .desktop-only {
        display: none !important;
    }
    
    /* 모바일 하단 고정 바를 위한 패딩 추가 */
    .main-content {
        padding-bottom: 120px;
    }
    
    .internet-modal.active {
        align-items: flex-end;
        justify-content: flex-start;
    }
}

.internet-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.internet-modal-content {
    position: relative;
    background-color: #ffffff;
    border-radius: 1rem;
    width: 90%;
    max-width: 640px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    z-index: 10000;
    margin: 2rem auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.internet-modal-content:has(#step2.active) {
    max-height: 95vh;
}

.internet-modal-content::-webkit-scrollbar {
    display: none;
}

@media (max-width: 767px) {
    .internet-modal-content {
        width: 100%;
        max-width: 100%;
        margin: 0;
        border-radius: 1.5rem 1.5rem 0 0;
        max-height: 85vh;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
        animation: slideUp 0.3s ease-out;
    }
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes slideDown {
    from {
        transform: translateY(0);
        opacity: 1;
    }
    to {
        transform: translateY(100%);
        opacity: 0;
    }
}

@media (max-width: 767px) {
    .internet-modal-content.closing {
        animation: slideDown 0.3s ease-in;
    }
}

.internet-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.internet-modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0;
    flex: 1;
    text-align: center;
}

.internet-modal-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    transition: background-color 0.2s, color 0.2s;
}

.internet-modal-close:hover {
    background-color: #f3f4f6;
    color: #1a1a1a;
}

.internet-modal-body {
    padding: 1.5rem;
}

.internet-modal-step {
    display: none;
}

.internet-modal-step.active {
    display: block;
}

#step2 {
    min-height: 400px;
}

.internet-option-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.internet-company-scroll-wrapper {
    width: 100%;
    padding: 0.5rem 0;
}

.internet-company-scroll {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    padding: 0.5rem 0;
}

.internet-company-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 1rem 0.75rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1.5px solid #e2e8f0;
    border-radius: 0.875rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    min-height: 100px;
}

.internet-company-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 0;
}

.internet-company-btn:hover {
    border-color: #667eea;
    background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.25), 
                0 8px 10px -6px rgba(102, 126, 234, 0.15);
}

.internet-company-btn:hover::before {
    left: 0;
    opacity: 0.05;
}

.internet-company-btn:active {
    transform: translateY(0);
    box-shadow: 0 4px 12px -2px rgba(102, 126, 234, 0.2);
}

.internet-company-btn.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.4), 
                0 8px 10px -6px rgba(102, 126, 234, 0.25);
}

.internet-company-btn.selected::before {
    opacity: 0;
}

.internet-company-logo {
    width: 70px;
    height: 70px;
    object-fit: contain;
    position: relative;
    z-index: 1;
    transition: transform 0.3s ease;
}

.internet-company-btn:hover .internet-company-logo {
    transform: scale(1.05);
}

.internet-company-btn.selected .internet-company-logo {
    filter: brightness(0) invert(1);
}

.internet-company-logo[src*="kt.svg"] {
    width: 50px;
    height: 70px;
}

.internet-company-icon-other {
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 1;
    transition: transform 0.3s ease;
}

.internet-company-icon-other svg {
    width: 50px;
    height: 50px;
}

.internet-company-btn:hover .internet-company-icon-other {
    transform: scale(1.05);
}

.internet-company-btn.selected .internet-company-icon-other svg {
    stroke: #ffffff;
}

.internet-company-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
    position: relative;
    z-index: 1;
    transition: color 0.3s ease;
    text-align: center;
    word-break: keep-all;
}

.internet-company-btn:hover .internet-company-name {
    color: #667eea;
}

.internet-company-btn.selected .internet-company-name {
    color: #ffffff;
}

.internet-option-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 1.125rem 1.5rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1.5px solid #e2e8f0;
    border-radius: 0.875rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-align: left;
    position: relative;
    overflow: hidden;
}

.internet-option-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 0;
}

.internet-option-btn:hover {
    border-color: #667eea;
    background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.25), 
                0 8px 10px -6px rgba(102, 126, 234, 0.15);
}

.internet-option-btn:hover::before {
    left: 0;
    opacity: 0.05;
}

.internet-option-btn:active {
    transform: translateY(0);
    box-shadow: 0 4px 12px -2px rgba(102, 126, 234, 0.2);
}

.internet-option-text {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    position: relative;
    z-index: 1;
    transition: color 0.3s ease;
}

.internet-option-btn:hover .internet-option-text {
    color: #667eea;
}

.internet-option-btn svg {
    color: #94a3b8;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
    transition: all 0.3s ease;
}

.internet-option-btn:hover svg {
    color: #667eea;
    transform: translateX(4px);
}

.internet-option-btn.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.4), 
                0 8px 10px -6px rgba(102, 126, 234, 0.25);
}

.internet-option-btn.selected::before {
    opacity: 0;
}

.internet-option-btn.selected .internet-option-text {
    color: #ffffff;
}

.internet-option-btn.selected svg {
    color: #ffffff;
    transform: translateX(4px);
}

@media (max-width: 767px) {
    .internet-modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .internet-modal-body {
        padding: 1.25rem 1.5rem;
        padding-bottom: calc(1.25rem + env(safe-area-inset-bottom));
    }
    
    .internet-option-btn {
        padding: 1rem 1.25rem;
    }
    
    .internet-option-text {
        font-size: 0.9375rem;
    }
}

.internet-info-section {
    margin-bottom: 1.5rem;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.internet-info-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    min-width: 140px;
    flex: 1;
    max-width: 200px;
    transition: all 0.2s ease;
    overflow: hidden;
}

.internet-info-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

#currentCompanyInfo.internet-info-card {
    background: #f8fafc;
}

.internet-info-label-section {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.8rem 1.5rem;
    width: 100%;
}

.internet-info-divider {
    width: 100%;
    height: 1px;
    background: #e2e8f0;
    margin: 0;
}

.internet-info-logo-section {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem 1.5rem;
    width: 100%;
}

.internet-info-label {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    text-align: center;
    width: 100%;
}

.internet-info-logo-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    min-height: 50px;
}

.internet-info-logo {
    height: 50px;
    width: auto;
    max-width: 120px;
    object-fit: contain;
}

.internet-info-name {
    color: #1e293b;
    font-weight: 600;
    font-size: 0.9375rem;
    text-align: center;
    width: 100%;
}

.internet-info-arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    flex-shrink: 0;
    padding: 0 0.5rem;
}

.internet-info-arrow svg {
    width: 24px;
    height: 24px;
}

.internet-info-arrow.hidden {
    display: none;
}

@media (max-width: 640px) {
    .internet-info-section {
        flex-direction: row;
        gap: 0.5rem;
    }
    
    .internet-info-card {
        flex: 1;
        min-width: 0;
        max-width: none;
    }
    
    .internet-info-arrow {
        padding: 0 0.25rem;
    }
    
    .internet-info-arrow svg {
        width: 20px;
        height: 20px;
    }
    
    .internet-info-logo {
        height: 40px;
    }
    
    .internet-info-label-section {
        padding: 0.6rem 0.75rem;
    }
    
    .internet-info-logo-section {
        padding: 0.75rem;
    }
    
    .internet-info-label {
        font-size: 0.8125rem;
    }
    
    .internet-info-name {
        font-size: 0.8125rem;
    }
}

.internet-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.internet-form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.internet-form-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
}

.internet-form-input-wrapper {
    position: relative;
}

.internet-form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background-color: #ffffff;
    color: #1e293b;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.internet-form-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.internet-form-input.input-error {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.internet-form-input.input-error:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.internet-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.internet-checkbox-all {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    gap: 0.5rem;
}

.internet-checkbox-all .internet-checkbox-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    flex: 1;
}

.internet-checkbox-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-left: 2rem;
}

.internet-checkbox-item-wrapper {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.internet-checkbox-item {
    display: flex;
    align-items: center;
    width: 100%;
}

.internet-checkbox-label-item {
    display: flex;
    align-items: center;
    cursor: pointer;
    flex: 1;
}

.internet-checkbox-input-item {
    width: 18px;
    height: 18px;
    margin: 0;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    background-color: #f3f4f6;
    position: relative;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.internet-checkbox-input-item:hover {
    border-color: #9ca3af;
    background-color: #e5e7eb;
}

.internet-checkbox-input-item:checked {
    background-color: #6366f1;
    border-color: #6366f1;
    box-shadow: 0 1px 3px rgba(99, 102, 241, 0.3);
}

.internet-checkbox-input-item:checked::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -55%) rotate(45deg);
    width: 5px;
    height: 9px;
    border: solid white;
    border-width: 0 2px 2px 0;
    border-radius: 1px;
}

.internet-checkbox-all .internet-checkbox-input {
    width: 20px;
    height: 20px;
    margin: 0;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    background-color: #f3f4f6;
    position: relative;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.internet-checkbox-all .internet-checkbox-input:hover {
    border-color: #9ca3af;
    background-color: #e5e7eb;
}

.internet-checkbox-all .internet-checkbox-input:checked {
    background-color: #6366f1;
    border-color: #6366f1;
    box-shadow: 0 1px 3px rgba(99, 102, 241, 0.3);
}

.internet-checkbox-all .internet-checkbox-input:checked::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -55%) rotate(45deg);
    width: 5px;
    height: 9px;
    border: solid white;
    border-width: 0 2px 2px 0;
    border-radius: 1px;
}

.internet-checkbox-group .internet-checkbox-text,
.internet-checkbox-label-item .internet-checkbox-text,
span.internet-checkbox-text {
    font-size: 1.0625rem !important;
    font-weight: 500 !important;
}

.internet-checkbox-text {
    color: #6b7280;
    margin-left: 0.5rem;
}

.internet-checkbox-link {
    margin-left: auto;
    color: #6b7280;
    display: flex;
    align-items: center;
    text-decoration: none;
}

.internet-checkbox-link svg {
    width: 18px;
    height: 18px;
    transition: transform 0.3s ease;
}

.internet-checkbox-link svg.arrow-down {
    transform: rotate(0deg);
}

.internet-checkbox-link:hover {
    color: #374151;
    background-color: #f3f4f6;
}

.internet-checkbox-link.arrow-up svg {
    transform: rotate(180deg);
}

/* 아코디언 스타일 */
.internet-accordion-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    margin-top: 0;
    margin-left: 2rem;
}

.internet-accordion-content.active {
    max-height: none;
    overflow: visible;
    transition: max-height 0.4s ease-in;
    margin-top: 0.75rem;
}

.internet-accordion-inner {
    background-color: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
}

.internet-accordion-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.internet-accordion-section {
    margin-bottom: 0.75rem;
}

.internet-accordion-section:last-child {
    margin-bottom: 0;
}

.internet-accordion-section-title {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 0.5rem;
}

.internet-accordion-section-content {
    font-size: 0.8125rem;
    color: #6b7280;
    line-height: 1.6;
    padding-left: 0.5rem;
}

.internet-submit-btn {
    width: 100%;
    padding: 1rem;
    font-size: 1rem;
    font-weight: 600;
    color: #ffffff;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 0.5rem;
}

.internet-submit-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.4);
}

.internet-submit-btn:active:not(:disabled) {
    transform: translateY(0);
}

.internet-submit-btn:disabled {
    background: #e5e7eb;
    color: #9ca3af;
    cursor: not-allowed;
}

.internet-card-apply-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.4);
}

.internet-card-apply-btn:active {
    transform: translateY(0);
}

.internet-toast-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.internet-toast-modal.active {
    display: flex;
}

.internet-toast-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.internet-toast-content {
    position: relative;
    background-color: #ffffff;
    border-radius: 1rem;
    padding: 2rem;
    max-width: 500px;
    width: calc(100% - 2rem);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    z-index: 10001;
    animation: toastFadeIn 0.3s ease-out;
}

@keyframes toastFadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.internet-toast-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.internet-toast-icon.success {
    background-color: #d1fae5;
    color: #10b981;
}

.internet-toast-icon.error {
    background-color: #fee2e2;
    color: #ef4444;
}

.internet-toast-icon svg {
    width: 32px;
    height: 32px;
}

.internet-toast-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 0.5rem;
    text-align: center;
}

.internet-toast-message {
    font-size: 1rem;
    color: #6b7280;
    margin-bottom: 1.5rem;
    text-align: center;
    line-height: 1.5;
}

.internet-toast-button {
    width: 100%;
    padding: 0.875rem;
    font-size: 1rem;
    font-weight: 600;
    color: #ffffff;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.internet-toast-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.4);
}

.privacy-content-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10001;
    align-items: center;
    justify-content: center;
}

.privacy-content-modal.privacy-content-modal-active {
    display: flex;
}

.privacy-content-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.privacy-content-modal-content {
    position: relative;
    background-color: #ffffff;
    border-radius: 1rem;
    padding: 0;
    max-width: 600px;
    width: calc(100% - 2rem);
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    z-index: 10002;
}

.privacy-content-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.privacy-content-modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0;
    flex: 1;
    text-align: center;
}

.privacy-content-modal-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    transition: background-color 0.2s, color 0.2s;
}

.privacy-content-modal-close:hover {
    background-color: #f3f4f6;
    color: #1a1a1a;
}

.privacy-content-modal-body {
    padding: 1.5rem;
    font-size: 0.875rem;
    color: #374151;
    line-height: 1.6;
}

/* 카드 크기 제한 */
.PlanDetail_content_wrapper__0YNeJ {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* 데스크톱: 카드와 하단 고정 바 사이 간격 및 전체 너비 사용 */
@media (min-width: 768px) {
    .plan-detail-apply-section {
        margin-top: 0.75rem;
    }
    
    /* PC에서는 전체 너비 사용 */
    .plan-detail-apply-section .PlanDetail_content_wrapper__0YNeJ {
        max-width: 100%;
        padding: 0;
    }
    
    .plan-detail-apply-section .css-2l6pil.e1ebrc9o0 {
        max-width: 100%;
        padding: 0;
    }
    
    /* plan-apply-content는 content-layout과 동일한 너비로 제한 */
    .plan-detail-apply-section .plan-apply-content {
        max-width: 800px;
        margin: 0 auto;
        padding: 0 1rem;
    }
}

@media (max-width: 767px) {
    .PlanDetail_content_wrapper__0YNeJ {
        padding: 0 1rem;
    }
}

.css-2l6pil.e1ebrc9o0 {
    max-width: 100%;
    gap: 0.75rem;
}

/* Product card wrapper */
.css-2l6pil.e1ebrc9o0 > div {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Product card - 크기 축소 */
.css-58gch7.e82z5mt0 {
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1rem;
    background-color: #ffffff;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease, transform 0.3s ease, border-color 0.3s ease;
    width: 100%;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.css-1kjyj6z.e82z5mt1 {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    width: 100%;
    flex-wrap: wrap;
}

.css-1pg8bi.e82z5mt15 {
    width: 100px;
    height: auto;
    object-fit: contain;
}

.css-huskxe.e82z5mt13 {
    display: flex;
    gap: 0.5rem;
    flex-wrap: nowrap;
    flex-shrink: 0;
    align-items: center;
    justify-content: flex-end;
    margin-left: auto;
}

.css-1fd5u73.e82z5mt14 {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: #6b7280;
    white-space: nowrap;
    flex-shrink: 0;
}

/* Benefits section - 패딩 축소 */
.css-174t92n.e82z5mt7 {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin: 0.75rem 0;
    padding: 0.75rem;
    background-color: #f9fafb;
    border-radius: 0.5rem;
    width: 100%;
    box-sizing: border-box;
}

/* 인터넷 카드 혜택 아이콘 스타일 (리스트 페이지와 동일) */
.css-12zfa6z.e82z5mt8 {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    width: 100%;
}

.css-xj5cz0.e82z5mt9 {
    width: auto;
    height: calc(1.0584rem * 1.5 + 1.3125rem * 1.5 + 0.08rem);
    max-width: 4rem;
    max-height: 4rem;
    flex-shrink: 0;
    object-fit: contain;
}

.css-0.e82z5mt10 {
    flex: 1;
}

.css-2ht76o.e82z5mt12 {
    font-size: 1rem;
    font-weight: 500;
    color: #4b5563;
    margin: 0;
    line-height: 1.5;
}

.css-2ht76o.e82z5mt12.item-name-text {
    color: #6b7280 !important;
    font-weight: 400 !important;
    font-size: 0.875rem !important;
}

.item-price-text {
    color: #4b5563;
    font-weight: 600;
    font-size: 0.875rem;
}

/* Price section */
.css-rkh09p.e82z5mt2 {
    margin-top: auto;
    padding-top: 0.75rem;
    border-top: 1px solid #e5e7eb;
    width: 100%;
}

.css-16qot29.e82z5mt6 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #4b5563;
    margin: 0;
    text-align: right;
}

@media (max-width: 767px) {
    .PlanDetail_content_wrapper__0YNeJ {
        padding: 0 0.75rem;
    }
    
    .css-58gch7.e82z5mt0 {
        padding: 0.875rem;
    }
    
    .css-174t92n.e82z5mt7 {
        padding: 0.625rem;
        gap: 0.5rem;
    }
}

/* 인터넷 리뷰 섹션 스타일 */
.plan-review-section .PlanDetail_content_wrapper__0YNeJ {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 1rem;
}

.plan-review-header {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    width: 100%;
}

.plan-review-header .section-title {
    margin: 0;
    display: flex;
    align-items: center;
}

.plan-review-logo-text {
    font-size: 16px;
    font-weight: 700;
    color: #374151;
    line-height: 1;
    display: flex;
    align-items: center;
}

@media (min-width: 992px) {
    .plan-review-logo-text {
        font-size: 20px;
    }
}

.plan-review-summary {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    gap: 48px;
    padding: 16px 0;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

.plan-review-left {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 0 0 auto;
}

.plan-review-seller-name {
    font-size: 16px;
    font-weight: 700;
    color: #374151;
    line-height: 1.5;
}

.plan-review-left .plan-review-rating {
    display: flex;
    align-items: center;
    gap: 4px;
}

.plan-review-left .plan-review-rating svg {
    width: 20px;
    height: 20px;
}

.plan-review-left .plan-review-rating-score {
    font-size: 28px;
    font-weight: 700;
    color: #000000;
}

.plan-review-total-rating {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex-shrink: 0;
}

.plan-review-total-rating-content {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 8px;
}

.plan-review-total-rating svg {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.plan-review-total-rating .plan-review-rating-score {
    font-size: 32px;
    font-weight: 700;
    color: #000000;
}

.plan-review-total-rating .plan-review-rating-count {
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
}

.plan-review-total-stars {
    font-size: 20px;
}

.plan-review-right {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 24px;
    flex: 0 0 auto;
}

.plan-review-categories {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.plan-review-category {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.plan-review-category-label {
    width: 80px;
    white-space: nowrap;
    font-size: 14px;
    font-weight: 700;
    color: #6b7280;
}

.plan-review-category-score {
    font-size: 14px;
    font-weight: 700;
    color: #4b5563;
    min-width: 35px;
    text-align: right;
}

.plan-review-stars {
    display: flex;
    align-items: center;
    gap: 2px;
    font-size: 18px;
    color: #EF4444;
    line-height: 1;
}

/* 부분 별점 스타일 */
.plan-review-stars .star-full {
    color: #EF4444;
}

.plan-review-stars .star-empty {
    color: #d1d5db;
}

.plan-review-stars .star-partial {
    position: relative;
    display: inline-block;
    width: 1em;
    height: 1em;
    line-height: 1;
    vertical-align: middle;
}

.plan-review-stars .star-partial-empty {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    color: #d1d5db;
    z-index: 0;
}

.plan-review-stars .star-partial-filled {
    position: absolute;
    top: 0;
    left: 0;
    width: var(--fill-percent);
    height: 100%;
    overflow: hidden;
    color: #EF4444;
    white-space: nowrap;
    z-index: 1;
}

.plan-review-count-section {
    width: 100%;
    margin-top: 16px;
    margin-bottom: 16px;
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.plan-review-count-sort-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.plan-review-count {
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
}

.plan-review-sort-select-wrapper {
    position: relative;
    box-shadow: rgba(36, 41, 46, 0.04) 0px 2px 8px 0px;
}

.plan-review-sort-select {
    padding: 6.5px 10px 6.5px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background-color: #ffffff;
    font-size: 14px;
    color: #374151;
    cursor: pointer;
    transition: border-color 0.2s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L6 6L11 1' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 32px;
}

.plan-review-sort-select:hover {
    border-color: #9ca3af;
}

.plan-review-sort-select:focus {
    outline: none;
    border-color: #667eea;
}

.plan-review-list {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 0;
}

.plan-review-item {
    width: 100%;
    max-width: 100%;
    padding: 16px;
    background-color: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

@media (min-width: 992px) {
    .plan-review-item {
        padding: 24px;
    }
}

.plan-review-item-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.plan-review-author {
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
}

.plan-review-date {
    font-size: 12px;
    color: #9ca3af;
}

.plan-review-content {
    font-size: 14px;
    line-height: 1.6;
    color: #374151;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.review-modal-item-content {
    font-size: 14px;
    line-height: 1.6;
    color: #374151;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

@media (max-width: 767px) {
    .plan-review-section .PlanDetail_content_wrapper__0YNeJ {
        padding: 0 0.75rem;
    }
    
    .plan-review-summary {
        flex-direction: column;
        align-items: center;
        gap: 24px;
        padding: 16px 1rem;
    }
    
    .plan-review-right {
        flex-direction: column;
        align-items: center;
        gap: 16px;
        width: 100%;
    }
    
    .plan-review-total-rating {
        align-items: center;
    }
}

@media (min-width: 768px) and (max-width: 991px) {
    .plan-review-summary {
        gap: 32px;
        padding: 16px 2rem;
    }
    
    .plan-review-right {
        gap: 16px;
    }
}

@media (min-width: 992px) {
    .plan-review-summary {
        padding: 16px 4rem;
    }
}
</style>

<!-- 인터넷 회선 신청 모달 -->
<div id="internetModal" class="internet-modal">
    <div class="internet-modal-overlay"></div>
    <div class="internet-modal-content">
        <div class="internet-modal-header">
            <h2 class="internet-modal-title" id="modalTitle">인터넷 설치여부</h2>
            <button class="internet-modal-close" onclick="closeInternetModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="internet-modal-body">
            <!-- Step 1: 인터넷 설치여부 선택 -->
            <div class="internet-modal-step" id="step1">
                <div class="internet-option-list">
                    <button class="internet-option-btn" onclick="selectInternetOption('none')">
                        <span class="internet-option-text">인터넷이 없어요</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <button class="internet-option-btn" onclick="selectInternetOption('installed')">
                        <span class="internet-option-text">인터넷이 설치되어 있어요</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Step 2: 기존 인터넷 회선 선택 -->
            <div class="internet-modal-step" id="step2">
                <div class="internet-company-scroll-wrapper">
                    <div class="internet-company-scroll">
                        <button class="internet-company-btn" onclick="selectInternetCompany('KT SkyLife', 'ktskylife')">
                            <img src="/MVNO/assets/images/internets/ktskylife.svg" alt="KT SkyLife" class="internet-company-logo">
                            <span class="internet-company-name">KT SkyLife</span>
                        </button>
                        <button class="internet-company-btn" onclick="selectInternetCompany('HelloVision', 'hellovision')">
                            <img src="/MVNO/assets/images/internets/hellovision.svg" alt="HelloVision" class="internet-company-logo">
                            <span class="internet-company-name">HelloVision</span>
                        </button>
                        <button class="internet-company-btn" onclick="selectInternetCompany('BTV', 'btv')">
                            <img src="/MVNO/assets/images/internets/btv.svg" alt="BTV" class="internet-company-logo">
                            <span class="internet-company-name">BTV</span>
                        </button>
                        <button class="internet-company-btn" onclick="selectInternetCompany('DLive', 'dlive')">
                            <img src="/MVNO/assets/images/internets/dlive.svg" alt="DLive" class="internet-company-logo">
                            <span class="internet-company-name">DLive</span>
                        </button>
                        <button class="internet-company-btn" onclick="selectInternetCompany('LG U+', 'lgu')">
                            <img src="/MVNO/assets/images/internets/lgu.svg" alt="LG U+" class="internet-company-logo">
                            <span class="internet-company-name">LG U+</span>
                        </button>
                        <button class="internet-company-btn" onclick="selectInternetCompany('KT', 'kt')">
                            <img src="/MVNO/assets/images/internets/kt.svg" alt="KT" class="internet-company-logo">
                            <span class="internet-company-name">KT</span>
                        </button>
                        <button class="internet-company-btn" onclick="selectInternetCompany('Broadband', 'broadband')">
                            <img src="/MVNO/assets/images/internets/broadband.svg" alt="Broadband" class="internet-company-logo">
                            <span class="internet-company-name">Broadband</span>
                        </button>
                        <button class="internet-company-btn" onclick="selectInternetCompany('기타', 'other')">
                            <div class="internet-company-icon-other">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="10" stroke="#667eea" stroke-width="2" fill="none"/>
                                    <path d="M9.09 9C9.3251 8.33167 9.78915 7.76811 10.4 7.40913C11.0108 7.05016 11.7289 6.91894 12.4272 7.03871C13.1255 7.15849 13.7588 7.52152 14.2151 8.06353C14.6713 8.60553 14.9211 9.29152 14.92 10C14.92 12 11.92 13 11.92 15" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 17H12.01" stroke="#667eea" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <span class="internet-company-name">기타</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: 폼 입력 -->
            <div class="internet-modal-step" id="step3">
                <!-- 인터넷 정보 표시 -->
                <div class="internet-info-section">
                    <!-- 기존 인터넷 회선 카드 -->
                    <div id="currentCompanyInfo" class="internet-info-card" style="display: none;">
                        <div class="internet-info-label-section">
                            <span class="internet-info-label">기존 인터넷 회선</span>
                        </div>
                        <div class="internet-info-divider"></div>
                        <div class="internet-info-logo-section">
                            <div class="internet-info-logo-wrapper">
                                <img id="currentCompanyLogo" src="" alt="" class="internet-info-logo" style="display: none;">
                                <span class="internet-info-name" id="currentCompanyName"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 화살표 -->
                    <div class="internet-info-arrow">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    
                    <!-- 신청 인터넷 회선 카드 -->
                    <div id="newCompanyInfo" class="internet-info-card">
                        <div class="internet-info-label-section">
                            <span class="internet-info-label">신청 인터넷 회선</span>
                        </div>
                        <div class="internet-info-divider"></div>
                        <div class="internet-info-logo-section">
                            <div class="internet-info-logo-wrapper">
                                <img id="newCompanyLogo" src="" alt="" class="internet-info-logo" style="display: none;">
                                <span class="internet-info-name" id="newCompanyName"></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                
                <!-- 입력 폼 -->
                <form id="internetApplicationForm" class="internet-form">
                    <div class="internet-form-group">
                        <label for="internetName" class="internet-form-label">이름</label>
                        <div class="internet-form-input-wrapper">
                            <input id="internetName" type="text" inputmode="text" name="name" class="internet-form-input">
                        </div>
                    </div>
                    <div class="internet-form-group">
                        <label for="internetPhone" class="internet-form-label">휴대폰 번호</label>
                        <div class="internet-form-input-wrapper">
                            <input id="internetPhone" type="tel" inputmode="tel" name="phoneNumber" class="internet-form-input" data-phone-format="true" required>
                            <span id="internetPhoneError" class="form-error-message" style="display: none; color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem;"></span>
                        </div>
                    </div>
                    <div class="internet-form-group">
                        <label for="internetEmail" class="internet-form-label">이메일 주소</label>
                        <div class="internet-form-input-wrapper">
                            <input id="internetEmail" type="email" inputmode="email" name="email" class="internet-form-input" required>
                            <span id="internetEmailError" class="form-error-message" style="display: none; color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem;"></span>
                        </div>
                    </div>
                    
                    <!-- 체크박스 -->
                    <div class="internet-checkbox-group">
                        <?php
                        // 동의 항목 정의 (순서대로)
                        $agreementItems = [
                            'purpose' => ['id' => 'agreePurpose', 'name' => 'agreementPurpose', 'modal' => 'openInternetPrivacyModal'],
                            'items' => ['id' => 'agreeItems', 'name' => 'agreementItems', 'modal' => 'openInternetPrivacyModal'],
                            'period' => ['id' => 'agreePeriod', 'name' => 'agreementPeriod', 'modal' => 'openInternetPrivacyModal'],
                            'thirdParty' => ['id' => 'agreeThirdParty', 'name' => 'agreementThirdParty', 'modal' => 'openInternetPrivacyModal'],
                            'serviceNotice' => ['id' => 'internetAgreementServiceNotice', 'name' => 'service_notice_opt_in', 'modal' => 'openInternetPrivacyModal'],
                            'marketing' => ['id' => 'internetAgreementMarketing', 'name' => 'marketing_opt_in', 'modal' => 'openInternetPrivacyModal']
                        ];
                        
                        // 노출되는 항목이 있는지 확인
                        $hasVisibleItems = false;
                        foreach ($agreementItems as $key => $item) {
                            $setting = $privacySettings[$key] ?? [];
                            if (array_key_exists('isVisible', $setting)) {
                                $isVisible = (bool)$setting['isVisible'];
                            } else {
                                $isVisible = true;
                            }
                            if ($isVisible) {
                                $hasVisibleItems = true;
                                break;
                            }
                        }
                        
                        // 노출되는 항목이 있을 때만 "전체 동의" 표시
                        if ($hasVisibleItems):
                        ?>
                        <label class="internet-checkbox-all">
                            <input type="checkbox" id="agreeAll" class="internet-checkbox-input" onchange="toggleAllAgreements(this.checked)">
                            <span class="internet-checkbox-label">전체 동의</span>
                        </label>
                        <?php endif; ?>
                        <div class="internet-checkbox-list">
                            <?php
                            // 관리자 페이지 설정에 따라 동의 항목 동적 렌더링
                            foreach ($agreementItems as $key => $item):
                                $setting = $privacySettings[$key] ?? [];
                                
                                // 노출 여부 확인 (isVisible = false인 항목은 렌더링하지 않음)
                                if (array_key_exists('isVisible', $setting)) {
                                    $isVisible = (bool)$setting['isVisible'];
                                } else {
                                    $isVisible = true;
                                }
                                
                                if (!$isVisible) {
                                    continue;
                                }
                                
                                // 제목 및 필수/선택 설정 (관리자 페이지에서 설정한 제목 사용)
                                $title = htmlspecialchars($setting['title'] ?? '');
                                // 제목이 비어있으면 기본값 사용
                                if (empty($title)) {
                                    $defaultTitles = [
                                        'purpose' => '개인정보 수집 및 이용목적',
                                        'items' => '개인정보 수집하는 항목',
                                        'period' => '개인정보 보유 및 이용기간',
                                        'thirdParty' => '개인정보 제3자 제공',
                                        'serviceNotice' => '서비스 이용 및 혜택 안내 알림',
                                        'marketing' => '광고성 정보수신'
                                    ];
                                    $title = $defaultTitles[$key] ?? '';
                                }
                                $isRequired = $setting['isRequired'] ?? ($key !== 'marketing');
                                $requiredText = $isRequired ? '(필수)' : '(선택)';
                                $requiredColor = $isRequired ? '#4f46e5' : '#6b7280';
                                $requiredAttr = $isRequired ? 'required' : '';
                                
                                // 모달 타입 확인
                                $hasModal = isset($item['modal']);
                            ?>
                            <div class="internet-checkbox-item-wrapper">
                                <div class="internet-checkbox-item">
                                    <label class="internet-checkbox-label-item">
                                        <input type="checkbox" id="<?php echo htmlspecialchars($item['id']); ?>" name="<?php echo htmlspecialchars($item['name']); ?>" class="internet-checkbox-input-item" onchange="checkAllAgreements();<?php echo ($key === 'serviceNotice' || $key === 'marketing') ? ' updateInternetNotificationSettings(\'' . $key . '\');' : ''; ?>" <?php echo $requiredAttr; ?>>
                                        <span class="internet-checkbox-text" style="font-size: 1.0625rem !important;"><?php echo $title; ?> <span style="color: <?php echo $requiredColor; ?>; font-weight: 600;"><?php echo $requiredText; ?></span></span>
                                    </label>
                                    <?php if ($hasModal): ?>
                                        <a href="#" class="internet-checkbox-link" id="<?php echo htmlspecialchars($key); ?>ArrowLink" onclick="event.preventDefault(); <?php echo htmlspecialchars($item['modal']); ?>('<?php echo $key; ?>'); return false;">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                                <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- 상담 신청 버튼 -->
                    <button type="submit" id="submitBtn" class="internet-submit-btn" disabled>상담 신청</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 개인정보 내용보기 모달 (인터넷 신청용) -->
<div class="privacy-content-modal" id="internetPrivacyContentModal">
    <div class="privacy-content-modal-overlay" id="internetPrivacyContentModalOverlay"></div>
    <div class="privacy-content-modal-content">
        <div class="privacy-content-modal-header">
            <h3 class="privacy-content-modal-title" id="internetPrivacyContentModalTitle">개인정보 수집 및 이용목적</h3>
            <button class="privacy-content-modal-close" aria-label="닫기" id="internetPrivacyContentModalClose">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="privacy-content-modal-body" id="internetPrivacyContentModalBody">
            <!-- 내용이 JavaScript로 동적으로 채워짐 -->
        </div>
    </div>
</div>

<!-- 토스트 메시지 모달 (성공/실패 모두 지원) -->
<div id="internetToastModal" class="internet-toast-modal">
    <div class="internet-toast-overlay"></div>
    <div class="internet-toast-content">
        <div class="internet-toast-icon" id="internetToastIcon"></div>
        <div class="internet-toast-title" id="internetToastTitle">인터넷 상담을 신청했어요</div>
        <div class="internet-toast-message" id="internetToastMessage">입력한 번호로 상담 전화를 드릴예정이에요</div>
        <button class="internet-toast-button" onclick="closeInternetToast()">확인</button>
    </div>
</div>

<script>
// 로그인한 사용자 정보 가져오기 (전역 변수)
<?php
$currentUser = null;
if (isLoggedIn()) {
    $currentUser = getCurrentUser();
}
?>
const currentUser = <?php echo json_encode($currentUser ? [
    'name' => $currentUser['name'] ?? '',
    'phone' => $currentUser['phone'] ?? '',
    'email' => $currentUser['email'] ?? ''
] : null); ?>;

// 모달 제어 함수들
let currentStep = 1;
let selectedData = {};
let scrollbarWidth = 0;

function getScrollbarWidth() {
    const outer = document.createElement('div');
    outer.style.visibility = 'hidden';
    outer.style.overflow = 'scroll';
    outer.style.msOverflowStyle = 'scrollbar';
    document.body.appendChild(outer);
    
    const inner = document.createElement('div');
    outer.appendChild(inner);
    
    const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
    
    outer.parentNode.removeChild(outer);
    
    return scrollbarWidth;
}

function openInternetModal() {
    const modal = document.getElementById('internetModal');
    const modalContent = modal ? modal.querySelector('.internet-modal-content') : null;
    
    if (modal && modalContent) {
        // 스크롤바 너비 계산 및 저장
        scrollbarWidth = getScrollbarWidth();
        
        // closing 클래스 제거 (이전에 닫힌 경우)
        modalContent.classList.remove('closing');
        modal.classList.add('active');
        
        // body 스크롤 숨기기 (스크롤바 공간 보정)
        document.body.style.overflow = 'hidden';
        if (scrollbarWidth > 0) {
            document.body.style.paddingRight = scrollbarWidth + 'px';
        }
        
        // 상품 정보 설정 (인터넷 상세페이지에서 사용)
        const productId = window.currentInternetProductId || <?php echo $internet_id; ?>;
        selectedData.product_id = productId;
        
        // 신청 인터넷 회선 정보 설정
        const registrationPlace = '<?php echo htmlspecialchars($internet['registration_place'], ENT_QUOTES); ?>';
        const iconPath = '<?php echo htmlspecialchars($iconPath, ENT_QUOTES); ?>';
        
        // 회사명 매핑
        const companyMap = {
            'KT': { name: 'KT', icon: 'kt' },
            'SKT': { name: 'Broadband', icon: 'broadband' },
            'LG U+': { name: 'LG U+', icon: 'lgu' },
            'KT skylife': { name: 'KT SkyLife', icon: 'ktskylife' },
            'LG헬로비전': { name: 'HelloVision', icon: 'hellovision' },
            'BTV': { name: 'BTV', icon: 'btv' },
            'DLIVE': { name: 'DLive', icon: 'dlive' }
        };
        
        const companyInfo = companyMap[registrationPlace] || { name: registrationPlace, icon: '' };
        selectedData.newCompany = companyInfo.name;
        selectedData.newCompanyIcon = companyInfo.icon;
        selectedData.newCompanyLogo = iconPath;
        
        // 상태 초기화
        resetSteps();
        // 첫 번째 단계 활성화
        showStep(1);
        // 신청 인터넷 회선 정보 표시
        showNewCompanyInfo();
        
        // 로그인한 사용자 정보 자동 입력
        if (currentUser) {
            const nameInput = document.getElementById('internetName');
            const phoneInput = document.getElementById('internetPhone');
            const emailInput = document.getElementById('internetEmail');
            
            if (nameInput && currentUser.name) {
                nameInput.value = currentUser.name;
            }
            if (phoneInput && currentUser.phone) {
                phoneInput.value = currentUser.phone;
            }
            if (emailInput && currentUser.email) {
                emailInput.value = currentUser.email.toLowerCase();
            }
            
            // 정보 입력 후 검증 및 버튼 상태 확인 (약간의 지연을 두어 DOM이 완전히 렌더링되도록)
            setTimeout(() => {
                // 전화번호 검증
                const phoneNumbers = phoneInput ? phoneInput.value.replace(/[^\d]/g, '') : '';
                const phoneErrorEl = document.getElementById('internetPhoneError');
                if (phoneInput && phoneInput.value.trim()) {
                    if (phoneNumbers.length === 11 && phoneNumbers.startsWith('010')) {
                        phoneInput.classList.remove('input-error');
                        if (phoneErrorEl) {
                            phoneErrorEl.style.display = 'none';
                            phoneErrorEl.textContent = '';
                        }
                    } else {
                        phoneInput.classList.add('input-error');
                        if (phoneErrorEl) {
                            phoneErrorEl.style.display = 'block';
                            phoneErrorEl.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                        }
                    }
                }
                
                // 이메일 검증
                const emailErrorEl = document.getElementById('internetEmailError');
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailInput && emailInput.value.trim()) {
                    const emailValue = emailInput.value.trim().toLowerCase();
                    emailInput.value = emailValue; // 소문자로 변환
                    if (emailRegex.test(emailValue)) {
                        emailInput.classList.remove('input-error');
                        if (emailErrorEl) {
                            emailErrorEl.style.display = 'none';
                            emailErrorEl.textContent = '';
                        }
                    } else {
                        emailInput.classList.add('input-error');
                        if (emailErrorEl) {
                            emailErrorEl.style.display = 'block';
                            emailErrorEl.textContent = '이메일 형식에 맞게 입력해주세요. (example@email.com 형식)';
                        }
                    }
                } else if (emailInput) {
                    emailInput.classList.add('input-error');
                    if (emailErrorEl) {
                        emailErrorEl.style.display = 'block';
                        emailErrorEl.textContent = '이메일을 입력해주세요.';
                    }
                }
                
                if (typeof checkAllAgreements === 'function') {
                    checkAllAgreements();
                }
            }, 200);
        } else {
            // 비회원인 경우 모달 로딩 시 검증 실행
            setTimeout(() => {
                const phoneInput = document.getElementById('internetPhone');
                const emailInput = document.getElementById('internetEmail');
                const emailErrorEl = document.getElementById('internetEmailError');
                
                // 이메일은 필수이므로 빈 값일 때 에러 표시
                if (emailInput && !emailInput.value.trim()) {
                    emailInput.classList.add('input-error');
                    if (emailErrorEl) {
                        emailErrorEl.style.display = 'block';
                        emailErrorEl.textContent = '이메일을 입력해주세요.';
                    }
                }
                
                if (typeof checkAllAgreements === 'function') {
                    checkAllAgreements();
                }
            }, 200);
        }
    }
}

function closeInternetModal() {
    const modal = document.getElementById('internetModal');
    const modalContent = modal ? modal.querySelector('.internet-modal-content') : null;
    
    if (modal && modalContent) {
        if (window.innerWidth <= 767) {
            modalContent.classList.add('closing');
            setTimeout(() => {
                modal.classList.remove('active');
                modalContent.classList.remove('closing');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                currentStep = 1;
                selectedData = {};
                resetSteps();
            }, 300);
        } else {
            modal.classList.remove('active');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            currentStep = 1;
            selectedData = {};
            resetSteps();
        }
    }
}

function showStep(step) {
    const steps = document.querySelectorAll('.internet-modal-step');
    steps.forEach(s => s.classList.remove('active'));
    
    const currentStepEl = document.getElementById('step' + step);
    if (currentStepEl) {
        currentStepEl.classList.add('active');
        currentStep = step;
        
        if (step === 3 && currentUser) {
            const nameInput = document.getElementById('internetName');
            const phoneInput = document.getElementById('internetPhone');
            const emailInput = document.getElementById('internetEmail');
            
            if (nameInput && currentUser.name && !nameInput.value) {
                nameInput.value = currentUser.name;
            }
            if (phoneInput && currentUser.phone && !phoneInput.value) {
                phoneInput.value = currentUser.phone;
            }
            if (emailInput && currentUser.email && !emailInput.value) {
                emailInput.value = currentUser.email;
            }
            
            // 정보 입력 후 버튼 상태 확인 (약간의 지연을 두어 DOM이 완전히 렌더링되도록)
            setTimeout(() => {
                if (typeof checkAllAgreements === 'function') {
                    checkAllAgreements();
                }
            }, 200);
        }
    }
}

function resetSteps() {
    const steps = document.querySelectorAll('.internet-modal-step');
    steps.forEach(s => s.classList.remove('active'));
    if (steps.length > 0) {
        steps[0].classList.add('active');
    }
    const optionButtons = document.querySelectorAll('.internet-option-btn');
    optionButtons.forEach(btn => btn.classList.remove('selected'));
    const companyButtons = document.querySelectorAll('.internet-company-btn');
    companyButtons.forEach(btn => btn.classList.remove('selected'));
    updateModalTitle('인터넷 설치여부');
    hideCurrentCompanyInfo();
    showNewCompanyInfo();
    const agreeAll = document.getElementById('agreeAll');
    const agreePurpose = document.getElementById('agreePurpose');
    const agreeItems = document.getElementById('agreeItems');
    const agreePeriod = document.getElementById('agreePeriod');
    const agreeThirdParty = document.getElementById('agreeThirdParty');
    const submitBtn = document.getElementById('submitBtn');
    if (agreeAll) agreeAll.checked = false;
    if (agreePurpose) agreePurpose.checked = false;
    if (agreeItems) agreeItems.checked = false;
    if (agreePeriod) agreePeriod.checked = false;
    if (agreeThirdParty) agreeThirdParty.checked = false;
    if (submitBtn) submitBtn.disabled = true; // 기본적으로 비활성화
    
    // 초기화 후 버튼 상태 확인
    setTimeout(() => {
        if (typeof checkAllAgreements === 'function') {
            // 약간의 지연을 두어 체크박스 상태가 완전히 반영되도록
        setTimeout(() => {
            if (typeof checkAllAgreements === 'function') {
                checkAllAgreements();
            }
        }, 10);
        }
    }, 100);
    const nameInput = document.getElementById('internetName');
    const phoneInput = document.getElementById('internetPhone');
    const emailInput = document.getElementById('internetEmail');
    if (nameInput) nameInput.value = '';
    if (phoneInput) phoneInput.value = '';
    if (emailInput) emailInput.value = '';
}

function selectInternetOption(option) {
    selectedData.installationStatus = option;
    
    const buttons = document.querySelectorAll('#step1 .internet-option-btn');
    buttons.forEach(btn => btn.classList.remove('selected'));
    
    const clickedButton = event.target.closest('.internet-option-btn');
    if (clickedButton) {
        clickedButton.classList.add('selected');
    }
    
    setTimeout(() => {
        if (option === 'none') {
            showStep(3);
            updateModalTitle('인터넷 회선 신청');
            hideCurrentCompanyInfo();
        } else if (option === 'installed') {
            showStep(2);
            updateModalTitle('기존 인터넷 회선 선택');
        }
    }, 300);
}

function selectInternetCompany(company, icon) {
    selectedData.currentCompany = company;
    selectedData.currentCompanyIcon = icon;
    
    const buttons = document.querySelectorAll('#step2 .internet-company-btn');
    buttons.forEach(btn => btn.classList.remove('selected'));
    
    const clickedButton = event.target.closest('.internet-company-btn');
    if (clickedButton) {
        clickedButton.classList.add('selected');
    }
    
    setTimeout(() => {
        showStep(3);
        updateModalTitle('인터넷 신청');
        showCurrentCompanyInfo(company, icon);
    }, 300);
}

function updateModalTitle(title) {
    const titleEl = document.getElementById('modalTitle');
    if (titleEl) {
        const cleanTitle = title.replace(/\?/g, '');
        titleEl.textContent = cleanTitle;
    }
}

function showCurrentCompanyInfo(company, icon) {
    const infoEl = document.getElementById('currentCompanyInfo');
    const nameEl = document.getElementById('currentCompanyName');
    const logoEl = document.getElementById('currentCompanyLogo');
    const arrowEl = document.querySelector('.internet-info-arrow');
    
    if (infoEl) {
        const companyLogos = {
            'KT SkyLife': '/MVNO/assets/images/internets/ktskylife.svg',
            'HelloVision': '/MVNO/assets/images/internets/hellovision.svg',
            'BTV': '/MVNO/assets/images/internets/btv.svg',
            'DLive': '/MVNO/assets/images/internets/dlive.svg',
            'LG U+': '/MVNO/assets/images/internets/lgu.svg',
            'KT': '/MVNO/assets/images/internets/kt.svg',
            'Broadband': '/MVNO/assets/images/internets/broadband.svg',
            '기타': null
        };
        
        const logoPath = companyLogos[company] || (icon ? `/MVNO/assets/images/internets/${icon}.svg` : null);
        
        if (logoPath && logoEl) {
            if (nameEl) nameEl.style.display = 'none';
            logoEl.src = logoPath;
            logoEl.alt = company;
            logoEl.style.display = 'block';
            logoEl.onerror = function() {
                this.style.display = 'none';
                if (nameEl) {
                    nameEl.textContent = company;
                    nameEl.style.display = 'inline';
                }
            };
            logoEl.onload = function() {
                this.style.display = 'block';
                if (nameEl) nameEl.style.display = 'none';
            };
        } else {
            if (logoEl) logoEl.style.display = 'none';
            if (nameEl) {
                nameEl.textContent = company;
                nameEl.style.display = 'inline';
            }
        }
        
        infoEl.style.display = 'flex';
        
        if (arrowEl) {
            arrowEl.classList.remove('hidden');
        }
    }
}

function showNewCompanyInfo() {
    const infoEl = document.getElementById('newCompanyInfo');
    const nameEl = document.getElementById('newCompanyName');
    const logoEl = document.getElementById('newCompanyLogo');
    
    if (infoEl && selectedData.newCompany) {
        const logoPath = selectedData.newCompanyLogo || 
            (selectedData.newCompanyIcon ? `/MVNO/assets/images/internets/${selectedData.newCompanyIcon}.svg` : null);
        
        if (logoPath && logoEl) {
            if (nameEl) nameEl.style.display = 'none';
            logoEl.src = logoPath;
            logoEl.alt = selectedData.newCompany;
            logoEl.style.display = 'block';
            logoEl.onerror = function() {
                this.style.display = 'none';
                if (nameEl) {
                    nameEl.textContent = selectedData.newCompany;
                    nameEl.style.display = 'inline';
                }
            };
            logoEl.onload = function() {
                this.style.display = 'block';
                if (nameEl) nameEl.style.display = 'none';
            };
        } else {
            if (logoEl) logoEl.style.display = 'none';
            if (nameEl) {
                nameEl.textContent = selectedData.newCompany;
                nameEl.style.display = 'inline';
            }
        }
        
        infoEl.style.display = 'flex';
    } else {
        if (infoEl) infoEl.style.display = 'none';
    }
    
    const currentInfoEl = document.getElementById('currentCompanyInfo');
    const arrowEl = document.querySelector('.internet-info-arrow');
    
    if (arrowEl) {
        if (currentInfoEl && currentInfoEl.style.display !== 'none') {
            arrowEl.classList.remove('hidden');
        } else {
            arrowEl.classList.add('hidden');
        }
    }
}

function hideCurrentCompanyInfo() {
    const infoEl = document.getElementById('currentCompanyInfo');
    const arrowEl = document.querySelector('.internet-info-arrow');
    
    if (infoEl) {
        infoEl.style.display = 'none';
    }
    
    if (arrowEl) {
        arrowEl.classList.add('hidden');
    }
}

// 전체 동의 및 개별 체크박스 이벤트 리스너 설정
document.addEventListener('DOMContentLoaded', function() {
    // 전체 동의 체크박스
    const agreementAll = document.getElementById('agreeAll');
    const agreementItemCheckboxes = document.querySelectorAll('.internet-checkbox-input-item');
    
    // 전체 동의 체크박스 변경 이벤트
    if (agreementAll) {
        agreementAll.addEventListener('change', function() {
            const isChecked = this.checked;
            agreementItemCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            checkAllAgreements();
        });
    }
    
    // 개별 체크박스 변경 이벤트 (전체 동의 상태 업데이트)
    agreementItemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            checkAllAgreements();
        });
    });
});

// 알림설정 업데이트 함수 (체크박스 변경 시 마이페이지 알림설정에 반영)
function updateInternetNotificationSettings(type) {
    const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
    if (!isLoggedIn) return; // 비회원은 업데이트하지 않음
    
    let checkbox;
    let settings = {};
    
    if (type === 'serviceNotice') {
        checkbox = document.getElementById('internetAgreementServiceNotice');
        if (checkbox) {
            settings = {
                service_notice_opt_in: checkbox.checked ? 1 : 0
            };
        }
    } else if (type === 'marketing') {
        checkbox = document.getElementById('internetAgreementMarketing');
        if (checkbox) {
            settings = {
                marketing_opt_in: checkbox.checked ? 1 : 0
            };
        }
    }
    
    if (checkbox && Object.keys(settings).length > 0) {
        // API 호출하여 알림설정 업데이트
        fetch('/MVNO/api/update-alarm-settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(settings)
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('알림설정 업데이트 실패:', data.message);
            }
        })
        .catch(error => {
            console.error('알림설정 업데이트 오류:', error);
        });
    }
}

function toggleAllAgreements(checked) {
    const agreeAll = document.getElementById('agreeAll');
    if (!agreeAll) return;
    
    // 모든 체크박스 찾기
    const checkboxes = document.querySelectorAll('.internet-checkbox-input-item');
    checkboxes.forEach(checkbox => {
        checkbox.checked = checked;
    });
    
    if (checked) {
        const agreementServiceNotice = document.getElementById('internetAgreementServiceNotice');
        if (agreementServiceNotice && agreementServiceNotice.checked) {
            if (typeof toggleInternetServiceNoticeChannels === 'function') {
                toggleInternetServiceNoticeChannels();
            }
        }
        const agreementMarketing = document.getElementById('internetAgreementMarketing');
        if (agreementMarketing && agreementMarketing.checked) {
            if (typeof toggleInternetMarketingChannels === 'function') {
                toggleInternetMarketingChannels();
            }
        }
    }
    
    checkAllAgreements();
}

// 인터넷 개인정보 내용 정의
<?php
// 이미 위에서 로드했으므로 재사용 (일관성 유지)
// $privacySettings는 파일 상단에서 이미 로드됨
echo "const internetPrivacyContents = " . json_encode($privacySettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
?>

function openInternetPrivacyModal(type) {
    const modal = document.getElementById('internetPrivacyContentModal');
    const modalTitle = document.getElementById('internetPrivacyContentModalTitle');
    const modalBody = document.getElementById('internetPrivacyContentModalBody');
    
    if (!modal || !modalTitle || !modalBody || !internetPrivacyContents[type]) return;
    
    modalTitle.textContent = internetPrivacyContents[type].title;
    modalBody.innerHTML = internetPrivacyContents[type].content;
    
    modal.style.display = 'flex';
    modal.classList.add('privacy-content-modal-active');
    document.body.style.overflow = 'hidden';
}

function closeInternetPrivacyModal() {
    const modal = document.getElementById('internetPrivacyContentModal');
    if (!modal) return;
    
    modal.classList.remove('privacy-content-modal-active');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

function checkAllAgreements() {
    const agreeAll = document.getElementById('agreeAll');
    const submitBtn = document.getElementById('submitBtn');
    const nameInput = document.getElementById('internetName');
    const phoneInput = document.getElementById('internetPhone');
    const emailInput = document.getElementById('internetEmail');

    if (!agreeAll || !submitBtn) return;

    // 필수 항목 목록 생성 (노출된 필수 항목만 포함)
    const requiredItems = [];
    const agreementMap = {
        'purpose': 'agreePurpose',
        'items': 'agreeItems',
        'period': 'agreePeriod',
        'thirdParty': 'agreeThirdParty',
        'serviceNotice': 'internetAgreementServiceNotice',
        'marketing': 'internetAgreementMarketing'
    };

    if (typeof internetPrivacyContents !== 'undefined') {
        for (const [key, id] of Object.entries(agreementMap)) {
            const setting = internetPrivacyContents[key];
            if (!setting) continue;
            
            const isVisible = setting.isVisible !== false;
            if (setting.isRequired === true && isVisible) {
                requiredItems.push(id);
            }
        }
    } else {
        // 기본값: marketing 제외 모두 필수
        requiredItems.push('agreePurpose', 'agreeItems', 'agreePeriod', 'agreeThirdParty');
    }

    // 전체 동의 체크박스 상태 업데이트
    let allRequiredChecked = true;
    for (const itemId of requiredItems) {
        const checkbox = document.getElementById(itemId);
        if (checkbox && !checkbox.checked) {
            allRequiredChecked = false;
            break;
        }
    }
    agreeAll.checked = allRequiredChecked;

    // 개인정보 입력 검증
    const name = nameInput ? nameInput.value.trim() : '';
    const phone = phoneInput ? phoneInput.value.replace(/[^\d]/g, '') : '';
    const email = emailInput ? emailInput.value.trim() : '';

    const isNameValid = name.length > 0;
    const isPhoneValid = phone.length === 11 && phone.startsWith('010');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isEmailValid = email.length > 0 && emailRegex.test(email);
    
    // 필수 동의 항목 체크 여부 확인
    let isAgreementsChecked = true;
    for (const itemId of requiredItems) {
        const checkbox = document.getElementById(itemId);
        if (checkbox && !checkbox.checked) {
            isAgreementsChecked = false;
            break;
        }
    }

    // 버튼 활성화 조건: 필수 항목 모두 체크 + 개인정보 입력 완료
    submitBtn.disabled = !(isNameValid && isPhoneValid && isEmailValid && isAgreementsChecked);
}

// 인터넷 신청 폼 제출 이벤트 (알뜰폰과 동일한 방식)
document.addEventListener('DOMContentLoaded', function() {
    const internetForm = document.getElementById('internetApplicationForm');
    if (internetForm) {
        internetForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // 로그인 체크
            const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
            if (!isLoggedIn) {
                closeInternetModal();
                const currentUrl = window.location.href;
                fetch('/MVNO/api/save-redirect-url.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ redirect_url: currentUrl })
                }).then(() => {
                    if (typeof openLoginModal === 'function') {
                        openLoginModal(false);
                    } else {
                        setTimeout(() => {
                            if (typeof openLoginModal === 'function') {
                                openLoginModal(false);
                            }
                        }, 100);
                    }
                }).catch(() => {
                    if (typeof openLoginModal === 'function') {
                        openLoginModal(false);
                    }
                });
                return;
            }
            
            // 입력값 검증
            const nameInput = document.getElementById('internetName');
            const phoneInput = document.getElementById('internetPhone');
            const emailInput = document.getElementById('internetEmail');
            const nameErrorElement = document.getElementById('internetNameError');
            const phoneErrorElement = document.getElementById('internetPhoneError');
            const emailErrorElement = document.getElementById('internetEmailError');
            
            // 이름 검증
            if (!nameInput || !nameInput.value.trim()) {
                if (nameInput) {
                    nameInput.classList.add('input-error');
                    if (nameErrorElement) {
                        nameErrorElement.style.display = 'block';
                        nameErrorElement.textContent = '이름을 입력해주세요.';
                    }
                    nameInput.focus();
                }
                return;
            }
            
            // 전화번호 검증
            if (!phoneInput || !phoneInput.value.trim()) {
                if (phoneInput) {
                    phoneInput.classList.add('input-error');
                    if (phoneErrorElement) {
                        phoneErrorElement.style.display = 'block';
                        phoneErrorElement.textContent = '휴대폰 번호를 입력해주세요.';
                    }
                    phoneInput.focus();
                }
                return;
            }
            
            const phoneNumbers = phoneInput.value.replace(/[^\d]/g, '');
            if (!validatePhoneNumber(phoneNumbers)) {
                if (phoneInput) {
                    phoneInput.classList.add('input-error');
                    if (phoneErrorElement) {
                        phoneErrorElement.style.display = 'block';
                        phoneErrorElement.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                    }
                    phoneInput.focus();
                }
                return;
            }
            
            // 이메일 검증
            if (!emailInput || !emailInput.value.trim()) {
                if (emailInput) {
                    emailInput.classList.add('input-error');
                    if (emailErrorElement) {
                        emailErrorElement.style.display = 'block';
                        emailErrorElement.textContent = '이메일을 입력해주세요.';
                    }
                    emailInput.focus();
                }
                return;
            }
            
            if (!validateEmail(emailInput.value)) {
                if (emailInput) {
                    emailInput.classList.add('input-error');
                    if (emailErrorElement) {
                        emailErrorElement.style.display = 'block';
                        emailErrorElement.textContent = '이메일 형식에 맞게 입력해주세요. (example@email.com 형식)';
                    }
                    emailInput.focus();
                }
                return;
            }
            
            // 필수 동의 항목 확인
            const requiredItems = [];
            const agreementMap = {
                'purpose': 'agreePurpose',
                'items': 'agreeItems',
                'period': 'agreePeriod',
                'thirdParty': 'agreeThirdParty',
                'serviceNotice': 'internetAgreementServiceNotice',
                'marketing': 'internetAgreementMarketing'
            };
            
            if (typeof internetPrivacyContents !== 'undefined') {
                for (const [key, id] of Object.entries(agreementMap)) {
                    const setting = internetPrivacyContents[key];
                    if (!setting) continue;
                    
                    const isVisible = setting.isVisible !== false;
                    if (setting.isRequired === true && isVisible) {
                        requiredItems.push(id);
                    }
                }
            } else {
                // 기본값: marketing 제외 모두 필수
                requiredItems.push('agreePurpose', 'agreeItems', 'agreePeriod', 'agreeThirdParty', 'internetAgreementServiceNotice');
            }
            
            let allRequiredChecked = true;
            for (const itemId of requiredItems) {
                const checkbox = document.getElementById(itemId);
                if (checkbox && !checkbox.checked) {
                    allRequiredChecked = false;
                    break;
                }
            }
            
            if (!allRequiredChecked) {
                alert('모든 필수 동의 항목에 동의해주세요.');
                return;
            }
            
            if (!selectedData.product_id) {
                showInternetToast('error', '상품 정보 오류', '상품 정보를 찾을 수 없습니다. 다시 시도해주세요.');
                return;
            }
            
            // 폼 데이터 수집 (FormData(this)로 자동으로 모든 체크박스 값 포함)
            const formData = new FormData(this);
            formData.append('product_id', selectedData.product_id);
            
            // phoneNumber 필드명을 phone으로 변경 (API와 일치)
            const phoneValue = formData.get('phoneNumber');
            if (phoneValue) {
                formData.delete('phoneNumber');
                formData.append('phone', phoneValue);
            }
            
            if (selectedData.currentCompany) {
                formData.append('currentCompany', selectedData.currentCompany);
            }
            
            // 제출 버튼 비활성화
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '처리 중...';
            }
            
            // 서버로 데이터 전송
            fetch('/MVNO/api/submit-internet-application.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    closeInternetModal();
                    showInternetToast('success', '인터넷 상담을 신청했어요', '입력한 번호로 상담 전화를 드릴예정이에요');
                } else {
                    showInternetToast('error', '신청 실패', data.message || '신청정보 저장에 실패했습니다.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = '상담 신청';
                    }
                }
            })
            .catch(error => {
                console.error('신청 처리 오류:', error);
                showInternetToast('error', '신청 실패', '신청 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = '상담 신청';
                }
            });
        });
    }
});

function showInternetToast(type = 'success', title = '인터넷 상담을 신청했어요', message = '입력한 번호로 상담 전화를 드릴예정이에요') {
    const toastModal = document.getElementById('internetToastModal');
    const toastIcon = document.getElementById('internetToastIcon');
    const toastTitle = document.getElementById('internetToastTitle');
    const toastMessage = document.getElementById('internetToastMessage');
    
    if (!toastModal) return;
    
    // 아이콘 설정
    if (toastIcon) {
        toastIcon.className = 'internet-toast-icon ' + type;
        if (type === 'success') {
            toastIcon.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            `;
        } else {
            toastIcon.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            `;
        }
    }
    
    // 제목과 메시지 설정
    if (toastTitle) {
        toastTitle.textContent = title;
    }
    if (toastMessage) {
        toastMessage.textContent = message;
    }
    
    // 모달 표시
    toastModal.classList.add('active');
}

function closeInternetToast() {
    const toastModal = document.getElementById('internetToastModal');
    if (toastModal) {
        toastModal.classList.remove('active');
    }
}

// 토스트 모달 외부 클릭 시 닫기
(function() {
    const toastModal = document.getElementById('internetToastModal');
    if (toastModal) {
        const overlay = toastModal.querySelector('.internet-toast-overlay');
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closeInternetToast();
                }
            });
        }
    }
})();

// 모달 외부 클릭 시 닫기
(function() {
    const modal = document.getElementById('internetModal');
    if (modal) {
        const overlay = modal.querySelector('.internet-modal-overlay');
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closeInternetModal();
                }
            });
        }
    }
})();

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('internetModal');
        if (modal && modal.classList.contains('active')) {
            closeInternetModal();
        }
        const privacyModal = document.getElementById('internetPrivacyContentModal');
        if (privacyModal && privacyModal.classList.contains('privacy-content-modal-active')) {
            closeInternetPrivacyModal();
        }
    }
});

// 개인정보 모달 닫기 이벤트
document.addEventListener('DOMContentLoaded', function() {
    const internetPrivacyModal = document.getElementById('internetPrivacyContentModal');
    const internetPrivacyModalOverlay = document.getElementById('internetPrivacyContentModalOverlay');
    const internetPrivacyModalClose = document.getElementById('internetPrivacyContentModalClose');
    
    if (internetPrivacyModalOverlay) {
        internetPrivacyModalOverlay.addEventListener('click', closeInternetPrivacyModal);
    }
    
    if (internetPrivacyModalClose) {
        internetPrivacyModalClose.addEventListener('click', closeInternetPrivacyModal);
    }
});

// 전화번호 및 이메일 검증 모듈
(function() {
    // 휴대폰번호 검증 함수 (전역으로 노출)
    window.validatePhoneNumber = function(phone) {
        // 숫자만 추출
        const phoneNumbers = phone.replace(/[^\d]/g, '');
        // 010으로 시작하는 11자리 숫자 확인
        return /^010\d{8}$/.test(phoneNumbers);
    };
    
    // 이메일 검증 함수 (전역으로 노출)
    window.validateEmail = function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email.trim());
    };
    
    function formatPhoneNumber(value) {
        const numbers = value.replace(/[^\d]/g, '');
        
        if (!numbers) return '';
        
        if (numbers.length > 0 && numbers[0] !== '0') {
            return '';
        }
        
        if (numbers.length >= 3 && !numbers.startsWith('010')) {
            if (!numbers.startsWith('02')) {
                return '';
            }
        }
        
        if (numbers.length <= 3) {
            if (numbers.length === 1 && numbers !== '0') {
                return '';
            }
            if (numbers.length === 2 && !numbers.startsWith('01')) {
                return '';
            }
            if (numbers.length === 3 && !numbers.startsWith('010')) {
                return '';
            }
            return numbers;
        } else if (numbers.length <= 7) {
            if (numbers.startsWith('02')) {
                return numbers.slice(0, 2) + '-' + numbers.slice(2);
            } else if (numbers.startsWith('010')) {
                return numbers.slice(0, 3) + '-' + numbers.slice(3);
            } else {
                return '';
            }
        } else if (numbers.length <= 10) {
            if (numbers.startsWith('02')) {
                return numbers.slice(0, 2) + '-' + numbers.slice(2, 6) + '-' + numbers.slice(6);
            } else if (numbers.startsWith('010')) {
                return numbers.slice(0, 3) + '-' + numbers.slice(3, 7) + '-' + numbers.slice(7);
            } else {
                return '';
            }
        } else {
            if (numbers.startsWith('02')) {
                return numbers.slice(0, 2) + '-' + numbers.slice(2, 6) + '-' + numbers.slice(6, 10);
            } else if (numbers.startsWith('010')) {
                return numbers.slice(0, 3) + '-' + numbers.slice(3, 7) + '-' + numbers.slice(7, 11);
            } else {
                return '';
            }
        }
    }
    
    function applyPhoneFormat(input) {
        let lastValidValue = '';
        
        input.addEventListener('input', function(e) {
            const cursorPosition = e.target.selectionStart;
            const oldValue = e.target.value;
            const newValue = formatPhoneNumber(e.target.value);
            
            if (newValue === '' && oldValue !== '' && oldValue.replace(/[^\d]/g, '').length > 0) {
                e.target.value = lastValidValue;
                e.target.setSelectionRange(cursorPosition - 1, cursorPosition - 1);
                return;
            }
            
            if (newValue !== '') {
                lastValidValue = newValue;
            }
            
            e.target.value = newValue;
            
            let newCursorPosition = cursorPosition;
            const oldLength = oldValue.length;
            const newLength = newValue.length;
            
            if (newLength > oldLength) {
                const addedHyphens = (newValue.match(/-/g) || []).length - (oldValue.match(/-/g) || []).length;
                newCursorPosition = cursorPosition + addedHyphens;
            }
            
            e.target.setSelectionRange(newCursorPosition, newCursorPosition);
            
            if (typeof checkAllAgreements === 'function') {
                // 약간의 지연을 두어 체크박스 상태가 완전히 반영되도록
        setTimeout(() => {
            if (typeof checkAllAgreements === 'function') {
                checkAllAgreements();
            }
        }, 10);
            }
        });
        
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const numbers = pastedText.replace(/[^\d]/g, '');
            
            if (numbers.length > 0 && !numbers.startsWith('010')) {
                return;
            }
            
            const formatted = formatPhoneNumber(pastedText);
            if (formatted !== '') {
                input.value = formatted;
                lastValidValue = formatted;
                
                if (typeof checkAllAgreements === 'function') {
                    // 약간의 지연을 두어 체크박스 상태가 완전히 반영되도록
        setTimeout(() => {
            if (typeof checkAllAgreements === 'function') {
                checkAllAgreements();
            }
        }, 10);
                }
            }
        });
        
        input.addEventListener('keydown', function(e) {
            const allowedKeys = [
                'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
                'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                'Home', 'End'
            ];
            
            const isNumber = (e.key >= '0' && e.key <= '9');
            const isAllowedKey = allowedKeys.includes(e.key);
            const isCtrlA = e.ctrlKey && e.key === 'a';
            const isCtrlC = e.ctrlKey && e.key === 'c';
            const isCtrlV = e.ctrlKey && e.key === 'v';
            const isCtrlX = e.ctrlKey && e.key === 'x';
            
            if (!isNumber && !isAllowedKey && !isCtrlA && !isCtrlC && !isCtrlV && !isCtrlX) {
                e.preventDefault();
                return;
            }
            
            if (isNumber) {
                const currentValue = e.target.value.replace(/[^\d]/g, '');
                
                if (currentValue.length === 0 && e.key !== '0') {
                    e.preventDefault();
                    return;
                }
                
                if (currentValue.length === 1 && e.key !== '1') {
                    e.preventDefault();
                    return;
                }
                
                if (currentValue.length === 2 && e.key !== '0') {
                    e.preventDefault();
                    return;
                }
            }
        });
    }
    
    // 전화번호 입력 필드에 포맷 적용 및 실시간 검증
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('internetPhone');
        const emailInput = document.getElementById('internetEmail');
        const phoneErrorElement = document.getElementById('internetPhoneError');
        const emailErrorElement = document.getElementById('internetEmailError');
        
        // 전화번호 입력 필드 처리
        if (phoneInput && phoneInput.hasAttribute('data-phone-format')) {
            const phoneErrorEl = document.getElementById('internetPhoneError');
            
            // 포맷 적용
            applyPhoneFormat(phoneInput);
            
            // 실시간 검증 (applyPhoneFormat의 input 이벤트 이후 실행)
            phoneInput.addEventListener('input', function() {
                const phoneNumbers = this.value.replace(/[^\d]/g, '');
                if (phoneNumbers.length > 0) {
                    if (phoneNumbers.length === 11 && phoneNumbers.startsWith('010')) {
                        this.classList.remove('input-error');
                        if (phoneErrorEl) {
                            phoneErrorEl.style.display = 'none';
                            phoneErrorEl.textContent = '';
                        }
                    } else {
                        this.classList.add('input-error');
                        if (phoneErrorEl) {
                            phoneErrorEl.style.display = 'block';
                            phoneErrorEl.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                        }
                    }
                } else {
                    this.classList.remove('input-error');
                    if (phoneErrorEl) {
                        phoneErrorEl.style.display = 'none';
                        phoneErrorEl.textContent = '';
                    }
                }
                checkAllAgreements();
            }, true); // capture phase에서 실행하여 applyPhoneFormat보다 먼저 실행
            
            // 포커스 아웃 시 검증
            phoneInput.addEventListener('blur', function() {
                const value = this.value.trim();
                const phoneNumbers = value.replace(/[^\d]/g, '');
                
                if (value && phoneNumbers.length > 0) {
                    if (phoneNumbers.length === 11 && phoneNumbers.startsWith('010')) {
                        this.classList.remove('input-error');
                        if (phoneErrorEl) {
                            phoneErrorEl.style.display = 'none';
                            phoneErrorEl.textContent = '';
                        }
                    } else {
                        this.classList.add('input-error');
                        if (phoneErrorEl) {
                            phoneErrorEl.style.display = 'block';
                            phoneErrorEl.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                        }
                    }
                } else if (value) {
                    this.classList.add('input-error');
                    if (phoneErrorEl) {
                        phoneErrorEl.style.display = 'block';
                        phoneErrorEl.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                    }
                } else {
                    this.classList.remove('input-error');
                    if (phoneErrorEl) {
                        phoneErrorEl.style.display = 'none';
                        phoneErrorEl.textContent = '';
                    }
                }
                
                checkAllAgreements();
            });
            
            // 입력 시작 시 에러 제거
            phoneInput.addEventListener('focus', function() {
                this.classList.remove('input-error');
                if (phoneErrorEl) {
                    phoneErrorEl.style.display = 'none';
                    phoneErrorEl.textContent = '';
                }
            });
        }
        
        // 이메일 입력 필드 처리
        if (emailInput) {
            // 실시간 검증 및 소문자 변환
            emailInput.addEventListener('input', function(e) {
                // 대문자를 소문자로 자동 변환
                const cursorPosition = this.selectionStart;
                const originalValue = this.value;
                const lowerValue = originalValue.toLowerCase();
                
                if (originalValue !== lowerValue) {
                    this.value = lowerValue;
                    const newCursorPosition = Math.min(cursorPosition, lowerValue.length);
                    this.setSelectionRange(newCursorPosition, newCursorPosition);
                }
                
                const value = this.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (value.length > 0) {
                    if (emailRegex.test(value)) {
                        this.classList.remove('input-error');
                        if (emailErrorElement) {
                            emailErrorElement.style.display = 'none';
                            emailErrorElement.textContent = '';
                        }
                    } else {
                        this.classList.add('input-error');
                        if (emailErrorElement) {
                            emailErrorElement.style.display = 'block';
                            emailErrorElement.textContent = '이메일 형식에 맞게 입력해주세요. (example@email.com 형식)';
                        }
                    }
                } else {
                    this.classList.remove('input-error');
                    if (emailErrorElement) {
                        emailErrorElement.style.display = 'none';
                        emailErrorElement.textContent = '';
                    }
                }
                
                checkAllAgreements();
            });
            
            // 포커스 아웃 시 검증
            emailInput.addEventListener('blur', function() {
                // 포커스 아웃 시에도 소문자로 변환
                this.value = this.value.toLowerCase();
                
                const value = this.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (value.length > 0) {
                    if (emailRegex.test(value)) {
                        this.classList.remove('input-error');
                        if (emailErrorElement) {
                            emailErrorElement.style.display = 'none';
                            emailErrorElement.textContent = '';
                        }
                    } else {
                        this.classList.add('input-error');
                        if (emailErrorElement) {
                            emailErrorElement.style.display = 'block';
                            emailErrorElement.textContent = '이메일 형식에 맞게 입력해주세요. (example@email.com 형식)';
                        }
                    }
                } else {
                    this.classList.add('input-error');
                    if (emailErrorElement) {
                        emailErrorElement.style.display = 'block';
                        emailErrorElement.textContent = '이메일을 입력해주세요.';
                    }
                }
                
                checkAllAgreements();
            });
            
            // 입력 시작 시 에러 제거
            emailInput.addEventListener('focus', function() {
                this.classList.remove('input-error');
                if (emailErrorElement) {
                    emailErrorElement.style.display = 'none';
                    emailErrorElement.textContent = '';
                }
            });
        }
        
        // 이름 입력 필드에 이벤트 리스너 추가
        const nameInput = document.getElementById('internetName');
        if (nameInput) {
            nameInput.addEventListener('input', function() {
                checkAllAgreements();
            });
            nameInput.addEventListener('blur', function() {
                checkAllAgreements();
            });
        }
        
        // 모달 로딩 시 검증 실행
        setTimeout(function() {
            if (phoneInput) {
                const phoneValue = phoneInput.value.trim();
                const phoneNumbers = phoneValue.replace(/[^\d]/g, '');
                
                if (phoneValue && phoneNumbers.length > 0) {
                    if (phoneNumbers.length === 11 && phoneNumbers.startsWith('010')) {
                        phoneInput.classList.remove('input-error');
                        if (phoneErrorElement) {
                            phoneErrorElement.style.display = 'none';
                        }
                    } else {
                        phoneInput.classList.add('input-error');
                        if (phoneErrorElement) {
                            phoneErrorElement.style.display = 'block';
                            phoneErrorElement.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                        }
                    }
                }
            }
            
            if (emailInput) {
                const emailValue = emailInput.value.trim().toLowerCase();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (emailValue.length > 0) {
                    if (emailRegex.test(emailValue)) {
                        emailInput.classList.remove('input-error');
                        if (emailErrorElement) {
                            emailErrorElement.style.display = 'none';
                        }
                    } else {
                        emailInput.classList.add('input-error');
                        if (emailErrorElement) {
                            emailErrorElement.style.display = 'block';
                            emailErrorElement.textContent = '이메일 형식에 맞게 입력해주세요. (example@email.com 형식)';
                        }
                    }
                } else {
                    emailInput.classList.add('input-error');
                    if (emailErrorElement) {
                        emailErrorElement.style.display = 'block';
                        emailErrorElement.textContent = '이메일을 입력해주세요.';
                    }
                }
            }
            
            checkAllAgreements();
        }, 100);
    });
})();

// 신청하기 버튼 클릭 이벤트 처리 함수 (리스트 페이지와 동일한 방식)
function handleInternetApplyClick(e) {
    e.preventDefault();
    e.stopPropagation();
    
    // 로그인 체크
    const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
    if (!isLoggedIn) {
        // 비회원: 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
        const currentUrl = window.location.href;
        fetch('/MVNO/api/save-redirect-url.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ redirect_url: currentUrl })
        }).catch(() => {
            // 에러 무시하고 계속 진행
        });
        
        // 인터넷 모달을 열어야 한다는 플래그 설정
        window.shouldOpenInternetModal = true;
        
        // 로그인 모달 열기 (false = 로그인 모드)
        function tryOpenLoginModal() {
            // 방법 1: 전역 함수 사용 (false = 로그인 모드)
            if (typeof openLoginModal === 'function') {
                openLoginModal(false);
                return true;
            }
            
            // 방법 2: 직접 모달 요소 찾기
            const loginModal = document.getElementById('loginModal');
            if (loginModal) {
                loginModal.classList.add('active');
                document.body.style.overflow = 'hidden';
                // 로그인 탭으로 전환
                const loginTab = document.querySelector('.login-tab[data-tab="login"]');
                if (loginTab) {
                    loginTab.click();
                }
                return true;
            }
            
            // 방법 3: 로그인 모달 오버레이 찾기
            const loginModalOverlay = document.querySelector('.login-modal-overlay');
            const loginModalContent = document.querySelector('.login-modal-content');
            if (loginModalOverlay && loginModalContent) {
                const loginModalWrapper = loginModalOverlay.closest('.login-modal');
                if (loginModalWrapper) {
                    loginModalWrapper.classList.add('active');
                    document.body.style.overflow = 'hidden';
                    // 로그인 탭으로 전환
                    const loginTab = document.querySelector('.login-tab[data-tab="login"]');
                    if (loginTab) {
                        loginTab.click();
                    }
                    return true;
                }
            }
            
            return false;
        }
        
        // 즉시 시도
        if (!tryOpenLoginModal()) {
            // 실패 시 재시도
            let retryCount = 0;
            const maxRetries = 10;
            const retryInterval = setInterval(() => {
                retryCount++;
                if (tryOpenLoginModal() || retryCount >= maxRetries) {
                    clearInterval(retryInterval);
                }
            }, 100);
        }
        
        return;
    }
    
    // 회원: 인터넷 설치 여부 모달 열기
    openInternetModal();
}

// 신청하기 버튼 클릭 이벤트 (하단 고정 버튼)
(function() {
    const mobileApplyBtn = document.getElementById('internetMobileApplyBtn');
    
    if (mobileApplyBtn) {
        mobileApplyBtn.addEventListener('click', handleInternetApplyClick);
    }
})();

    // 인터넷 리뷰 더보기 모달 기능
    const internetReviewMoreBtn = document.getElementById('internetReviewMoreBtn');
    const internetReviewModal = document.getElementById('internetReviewModal');
    const internetReviewModalOverlay = document.getElementById('internetReviewModalOverlay');
    const internetReviewModalClose = document.getElementById('internetReviewModalClose');
    const internetReviewModalList = document.getElementById('internetReviewModalList');
    const internetReviewModalMoreBtn = document.getElementById('internetReviewModalMoreBtn');
    
    // 모달 열기 함수
    function openInternetReviewModal() {
        if (internetReviewModal) {
            internetReviewModal.classList.add('review-modal-active');
            document.body.classList.add('review-modal-open');
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
        }
    }
    
    // 모달 닫기 함수
    function closeInternetReviewModal() {
        if (internetReviewModal) {
            internetReviewModal.classList.remove('review-modal-active');
            document.body.classList.remove('review-modal-open');
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        }
    }
    
    // 더보기 버튼 클릭 시 모달 열기
    if (internetReviewMoreBtn) {
        internetReviewMoreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openInternetReviewModal();
        });
    }
    
    // 모달 닫기 이벤트
    if (internetReviewModalOverlay) {
        internetReviewModalOverlay.addEventListener('click', closeInternetReviewModal);
    }
    
    if (internetReviewModalClose) {
        internetReviewModalClose.addEventListener('click', closeInternetReviewModal);
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && internetReviewModal && internetReviewModal.classList.contains('review-modal-active')) {
            closeInternetReviewModal();
        }
    });
    
    // 모달 내부 더보기 기능: 처음 10개, 이후 10개씩 표시
    if (internetReviewModalList && internetReviewModalMoreBtn) {
        const modalReviewItems = internetReviewModalList.querySelectorAll('.review-modal-item');
        const totalModalReviews = modalReviewItems.length;
        let visibleModalCount = 10; // 처음 10개만 표시
        
        // 초기 설정: 10개 이후 리뷰 숨기기
        function initializeInternetModalReviews() {
            visibleModalCount = 10; // 모달 열 때마다 10개로 초기화
            modalReviewItems.forEach((item, index) => {
                if (index >= visibleModalCount) {
                    item.style.display = 'none';
                } else {
                    item.style.display = 'block';
                }
            });
            
            // 모든 리뷰가 이미 표시되어 있으면 버튼 숨기기
            if (totalModalReviews <= visibleModalCount) {
                internetReviewModalMoreBtn.style.display = 'none';
            } else {
                const remaining = totalModalReviews - visibleModalCount;
                internetReviewModalMoreBtn.textContent = `리뷰 더보기 (${remaining}개)`;
                internetReviewModalMoreBtn.style.display = 'block';
            }
        }
        
        // 초기 설정 실행
        initializeInternetModalReviews();
        
        // 모달이 열릴 때마다 초기화
        if (internetReviewModal) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (internetReviewModal.classList.contains('review-modal-active')) {
                            initializeInternetModalReviews(); // 모달 열 때마다 10개로 초기화
                        }
                    }
                });
            });
            observer.observe(internetReviewModal, { attributes: true });
        }
        
        // 모달 내부 더보기 버튼 클릭 이벤트
        internetReviewModalMoreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            visibleModalCount += 10; // 10개씩 추가
            
            // 리뷰 표시
            modalReviewItems.forEach((item, index) => {
                if (index < visibleModalCount) {
                    item.style.display = 'block';
                }
            });
            
            // 남은 리뷰 개수 계산 및 버튼 텍스트 업데이트
            const remaining = totalModalReviews - visibleModalCount;
            if (remaining <= 0) {
                internetReviewModalMoreBtn.style.display = 'none';
            } else {
                internetReviewModalMoreBtn.textContent = `리뷰 더보기 (${remaining}개)`;
            }
        });
    }
    
    // 리뷰 정렬 선택 기능 (모달)
    const internetReviewModalSortSelect = document.getElementById('internetReviewModalSortSelect');
    if (internetReviewModalSortSelect) {
        internetReviewModalSortSelect.addEventListener('change', function() {
            const sort = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('review_sort', sort);
            window.location.href = url.toString();
        });
    }

</script>

<!-- 아코디언 스크립트 -->
<script src="/MVNO/assets/js/plan-accordion.js"></script>

<?php include '../includes/footer.php'; ?>






