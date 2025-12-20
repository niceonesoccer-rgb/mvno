<?php
// 현재 페이지 설정
$current_page = 'internets';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true; // 상세 페이지에서도 하단 메뉴바 표시

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';

// 인터넷 상품 ID 가져오기
$internet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($internet_id <= 0) {
    http_response_code(404);
    die('상품을 찾을 수 없습니다.');
}

// 헤더 포함
include '../includes/header.php';

// 조회수 업데이트
require_once '../includes/data/product-functions.php';
incrementProductView($internet_id);

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
    die('상품을 찾을 수 없습니다.');
}

// 일반 사용자가 판매종료 상품에 접근하는 경우 차단
if (!$isAdmin && isset($internet['status']) && $internet['status'] === 'inactive') {
    http_response_code(404);
    die('판매종료된 상품입니다.');
}

// 리뷰 목록 가져오기 (같은 판매자의 같은 타입의 모든 상품 리뷰 통합)
$reviews = getProductReviews($internet_id, 'internet', 20);
$averageRating = getProductAverageRating($internet_id, 'internet');
$reviewCount = getProductReviewCount($internet_id, 'internet');

// 가입처별 아이콘 경로 매핑
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
                        <div class="css-174t92n e82z5mt7">
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
                            <div class="css-12zfa6z e82z5mt8">
                                <img src="/MVNO/assets/images/icons/cash.svg" alt="현금" class="css-xj5cz0 e82z5mt9">
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
                            <div class="css-12zfa6z e82z5mt8">
                                <img src="/MVNO/assets/images/icons/gift-card.svg" alt="상품권" class="css-xj5cz0 e82z5mt9">
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
                            <div class="css-12zfa6z e82z5mt8">
                                <img src="/MVNO/assets/images/icons/equipment.svg" alt="장비" class="css-xj5cz0 e82z5mt9">
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
                            <div class="css-12zfa6z e82z5mt8">
                                <img src="/MVNO/assets/images/icons/installation.svg" alt="설치" class="css-xj5cz0 e82z5mt9">
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
                        <div data-testid="full-price-information" class="css-rkh09p e82z5mt2">
                            <p class="css-16qot29 e82z5mt6">월 <?php echo htmlspecialchars($internet['monthly_fee']); ?></p>
                        </div>
                        
                        <!-- 신청하기 버튼 (리스트 페이지와 동일한 레이아웃) -->
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                            <button class="internet-card-apply-btn" id="internetCardApplyBtn" data-apply-type="internet" data-internet-id="<?php echo $internet_id; ?>" style="width: 100%; padding: 0.875rem 1rem; font-size: 1rem; font-weight: 600; color: #ffffff; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 0.5rem; cursor: pointer; transition: all 0.3s ease;">
                                신청하기
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

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

@media (max-width: 767px) {
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
    max-width: 500px;
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
    justify-content: center;
    text-decoration: none;
    padding: 0.6rem;
    min-width: 2.88rem;
    min-height: 2.88rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s;
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

.css-2l6pil.e1ebrc9o0 {
    max-width: 100%;
}

/* Product card wrapper */
.css-2l6pil.e1ebrc9o0 > div {
    width: 100%;
    display: flex;
    flex-direction: column;
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
                <div class="internet-form">
                    <div class="internet-form-group">
                        <label for="internetName" class="internet-form-label">이름</label>
                        <div class="internet-form-input-wrapper">
                            <input id="internetName" type="text" inputmode="text" name="name" class="internet-form-input">
                        </div>
                    </div>
                    <div class="internet-form-group">
                        <label for="internetPhone" class="internet-form-label">휴대폰 번호</label>
                        <div class="internet-form-input-wrapper">
                            <input id="internetPhone" type="tel" inputmode="tel" name="phoneNumber" class="internet-form-input" data-phone-format="true">
                        </div>
                    </div>
                    <div class="internet-form-group">
                        <label for="internetEmail" class="internet-form-label">이메일 주소</label>
                        <div class="internet-form-input-wrapper">
                            <input id="internetEmail" type="email" inputmode="email" name="email" class="internet-form-input">
                        </div>
                    </div>
                    
                    <!-- 체크박스 -->
                    <div class="internet-checkbox-group">
                        <label class="internet-checkbox-all">
                            <input type="checkbox" id="agreeAll" class="internet-checkbox-input" onchange="toggleAllAgreements(this.checked)">
                            <span class="internet-checkbox-label">전체 동의</span>
                        </label>
                        <div class="internet-checkbox-list">
                            <div class="internet-checkbox-item-wrapper">
                                <div class="internet-checkbox-item">
                                    <label class="internet-checkbox-label-item">
                                        <input type="checkbox" id="agreePurpose" name="agreementPurpose" class="internet-checkbox-input-item" onchange="checkAllAgreements();" required>
                                        <span class="internet-checkbox-text" style="font-size: 1.0625rem !important;">개인정보 수집 및 이용목적에 동의합니까?</span>
                                    </label>
                                    <a href="#" class="internet-checkbox-link" id="purposeArrowLink" onclick="event.preventDefault(); openInternetPrivacyModal('purpose'); return false;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                            <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="internet-checkbox-item-wrapper">
                                <div class="internet-checkbox-item">
                                    <label class="internet-checkbox-label-item">
                                        <input type="checkbox" id="agreeItems" name="agreementItems" class="internet-checkbox-input-item" onchange="checkAllAgreements();" required>
                                        <span class="internet-checkbox-text" style="font-size: 1.0625rem !important;">개인정보 수집하는 항목에 동의합니까?</span>
                                    </label>
                                    <a href="#" class="internet-checkbox-link" id="itemsArrowLink" onclick="event.preventDefault(); openInternetPrivacyModal('items'); return false;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                            <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="internet-checkbox-item-wrapper">
                                <div class="internet-checkbox-item">
                                    <label class="internet-checkbox-label-item">
                                        <input type="checkbox" id="agreePeriod" name="agreementPeriod" class="internet-checkbox-input-item" onchange="checkAllAgreements();" required>
                                        <span class="internet-checkbox-text" style="font-size: 1.0625rem !important;">개인정보 보유 및 이용기간에 동의합니까?</span>
                                    </label>
                                    <a href="#" class="internet-checkbox-link" id="periodArrowLink" onclick="event.preventDefault(); openInternetPrivacyModal('period'); return false;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                            <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="internet-checkbox-item-wrapper">
                                <div class="internet-checkbox-item">
                                    <label class="internet-checkbox-label-item">
                                        <input type="checkbox" id="agreeThirdParty" name="agreementThirdParty" class="internet-checkbox-input-item" onchange="checkAllAgreements();" required>
                                        <span class="internet-checkbox-text" style="font-size: 1.0625rem !important;">개인정보 제3자 제공에 동의합니까?</span>
                                    </label>
                                    <a href="#" class="internet-checkbox-link" id="thirdPartyArrowLink" onclick="event.preventDefault(); openInternetPrivacyModal('thirdParty'); return false;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                            <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 상담 신청 버튼 -->
                    <button id="submitBtn" class="internet-submit-btn" disabled onclick="submitInternetForm()">상담 신청</button>
                </div>
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

function toggleAllAgreements(checked) {
    const agreePurpose = document.getElementById('agreePurpose');
    const agreeItems = document.getElementById('agreeItems');
    const agreePeriod = document.getElementById('agreePeriod');
    const agreeThirdParty = document.getElementById('agreeThirdParty');

    if (agreePurpose && agreeItems && agreePeriod && agreeThirdParty) {
        agreePurpose.checked = checked;
        agreeItems.checked = checked;
        agreePeriod.checked = checked;
        agreeThirdParty.checked = checked;
        
        // 버튼 상태 확인 (약간의 지연을 두어 체크박스 상태가 완전히 반영되도록)
        setTimeout(() => {
            if (typeof checkAllAgreements === 'function') {
                // 약간의 지연을 두어 체크박스 상태가 완전히 반영되도록
        setTimeout(() => {
            if (typeof checkAllAgreements === 'function') {
                checkAllAgreements();
            }
        }, 10);
            }
        }, 10);
    }
}

// 인터넷 개인정보 내용 정의
<?php
require_once __DIR__ . '/../includes/data/privacy-functions.php';
$privacySettings = getPrivacySettings();
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
    const agreePurpose = document.getElementById('agreePurpose');
    const agreeItems = document.getElementById('agreeItems');
    const agreePeriod = document.getElementById('agreePeriod');
    const agreeThirdParty = document.getElementById('agreeThirdParty');
    const submitBtn = document.getElementById('submitBtn');
    const nameInput = document.getElementById('internetName');
    const phoneInput = document.getElementById('internetPhone');
    const emailInput = document.getElementById('internetEmail');

    if (agreeAll && agreePurpose && agreeItems && agreePeriod && agreeThirdParty && submitBtn) {
        // 전체 동의 체크박스 상태 업데이트
        agreeAll.checked = agreePurpose.checked && agreeItems.checked && agreePeriod.checked && agreeThirdParty.checked;
        
        // 입력 필드 검증
        const name = nameInput ? nameInput.value.trim() : '';
        const phoneRaw = phoneInput ? phoneInput.value : '';
        const phone = phoneRaw.replace(/[^\d]/g, ''); // 숫자만 추출
        const email = emailInput ? emailInput.value.trim() : '';
        
        const isNameValid = name.length > 0;
        // 전화번호 검증: 010으로 시작하는 11자리 숫자
        const isPhoneValid = phone.length === 11 && phone.startsWith('010');
        // 이메일 검증: @가 포함되어 있으면 기본적으로 유효 (선택적 필드이므로 완화)
        const isEmailValid = email.length === 0 || (email.includes('@') && email.indexOf('@') > 0 && email.indexOf('@') < email.length - 1);
        const isAgreementsChecked = agreePurpose.checked && agreeItems.checked && agreePeriod.checked && agreeThirdParty.checked;
        
        // 디버깅: 콘솔에 상태 출력 (전화번호 전체 값 포함)
        console.log('checkAllAgreements - Validation:', {
            isNameValid,
            isPhoneValid,
            isEmailValid,
            isAgreementsChecked,
            name: name || 'empty',
            phoneRaw: phoneRaw || 'empty', // 원본 전화번호 (하이픈 포함)
            phone: phone || 'empty', // 숫자만 추출한 전화번호
            phoneLength: phone.length,
            phoneStartsWith010: phone.startsWith('010'),
            email: email || 'empty',
            agreePurpose: agreePurpose.checked,
            agreeItems: agreeItems.checked,
            agreePeriod: agreePeriod.checked,
            agreeThirdParty: agreeThirdParty.checked
        });
        
        // 모든 정보가 입력되고 동의가 체크되면 버튼 활성화
        if (isNameValid && isPhoneValid && isEmailValid && isAgreementsChecked) {
            submitBtn.disabled = false;
            console.log('checkAllAgreements - Button ENABLED');
        } else {
            submitBtn.disabled = true;
            console.log('checkAllAgreements - Button DISABLED - Reasons:', {
                name: !isNameValid ? 'invalid (empty)' : 'ok',
                phone: !isPhoneValid ? `invalid (length: ${phone.length}, startsWith010: ${phone.startsWith('010')}, value: ${phoneRaw})` : 'ok',
                email: !isEmailValid ? 'invalid' : 'ok',
                agreements: !isAgreementsChecked ? 'not checked' : 'checked'
            });
        }
    }
}

function submitInternetForm() {
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
                openLoginModal(false); // 로그인 모달 열기 (false = 로그인 모드)
            } else {
                setTimeout(() => {
                    if (typeof openLoginModal === 'function') {
                        openLoginModal(false); // 로그인 모달 열기 (false = 로그인 모드)
                    }
                }, 100);
            }
        });
        return;
    }
    
    const name = document.getElementById('internetName').value;
    const phone = document.getElementById('internetPhone').value;
    const email = document.getElementById('internetEmail').value;
    
    if (!selectedData.product_id) {
        showInternetToast('error', '상품 정보 오류', '상품 정보를 찾을 수 없습니다. 다시 시도해주세요.');
        return;
    }
    
    const formData = new FormData();
    formData.append('product_id', selectedData.product_id);
    formData.append('name', name);
    formData.append('phone', phone);
    formData.append('email', email);
    
    if (selectedData.currentCompany) {
        formData.append('currentCompany', selectedData.currentCompany);
    }
    
    // 디버깅: 전송할 데이터 로깅
    console.log('Internet Application Debug - Submitting form data:');
    console.log('  product_id:', selectedData.product_id);
    console.log('  name:', name);
    console.log('  phone:', phone);
    console.log('  email:', email);
    console.log('  currentCompany:', selectedData.currentCompany || 'none');
    
    fetch('/MVNO/api/submit-internet-application.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Internet Application Debug - Response status:', response.status, response.statusText);
        if (!response.ok) {
            console.error('Internet Application Debug - Response not OK:', response.status, response.statusText);
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Internet Application Debug - Response data:', data);
        if (data.success) {
            console.log('Internet Application Debug - Success! Application ID:', data.application_id);
            closeInternetModal();
            showInternetToast('success', '인터넷 상담을 신청했어요', '입력한 번호로 상담 전화를 드릴예정이에요');
        } else {
            console.error('Internet Application Debug - Failed:', data.message);
            if (data.debug) {
                console.error('Internet Application Debug - Debug info:', data.debug);
            }
            showInternetToast('error', '신청 실패', data.message || '신청정보 저장에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Internet Application Debug - Error caught:', error);
        console.error('Internet Application Debug - Error stack:', error.stack);
        showInternetToast('error', '신청 실패', '신청 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
    });
}

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

// 전화번호 입력 모듈
(function() {
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
    
    // 전화번호 입력 필드에 포맷 적용
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('internetPhone');
        if (phoneInput && phoneInput.hasAttribute('data-phone-format')) {
            applyPhoneFormat(phoneInput);
        }
        
        // 이름 입력 필드에 이벤트 리스너 추가
        const nameInput = document.getElementById('internetName');
        if (nameInput) {
            nameInput.addEventListener('input', function() {
                if (typeof checkAllAgreements === 'function') {
                    // 약간의 지연을 두어 체크박스 상태가 완전히 반영되도록
        setTimeout(() => {
            if (typeof checkAllAgreements === 'function') {
                checkAllAgreements();
            }
        }, 10);
                }
            });
            nameInput.addEventListener('blur', function() {
                if (typeof checkAllAgreements === 'function') {
                    // 약간의 지연을 두어 체크박스 상태가 완전히 반영되도록
        setTimeout(() => {
            if (typeof checkAllAgreements === 'function') {
                checkAllAgreements();
            }
        }, 10);
                }
            });
        }
        
        // 이메일 입력 필드에 이벤트 리스너 추가
        const emailInput = document.getElementById('internetEmail');
        if (emailInput) {
            emailInput.addEventListener('input', function() {
                if (typeof checkAllAgreements === 'function') {
                    // 약간의 지연을 두어 체크박스 상태가 완전히 반영되도록
        setTimeout(() => {
            if (typeof checkAllAgreements === 'function') {
                checkAllAgreements();
            }
        }, 10);
                }
            });
            emailInput.addEventListener('blur', function() {
                if (typeof checkAllAgreements === 'function') {
                    // 약간의 지연을 두어 체크박스 상태가 완전히 반영되도록
        setTimeout(() => {
            if (typeof checkAllAgreements === 'function') {
                checkAllAgreements();
            }
        }, 10);
                }
            });
        }
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
        }).catch(error => {
            // 에러 무시하고 계속 진행
            console.error('Redirect URL 저장 실패:', error);
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

// 신청하기 버튼 클릭 이벤트 (카드 내부 버튼)
(function() {
    const cardApplyBtn = document.getElementById('internetCardApplyBtn');
    
    if (cardApplyBtn) {
        cardApplyBtn.addEventListener('click', handleInternetApplyClick);
    }
})();

// 신청하기 버튼 클릭 이벤트 (하단 고정 버튼)
(function() {
    const applyBtn = document.getElementById('internetApplyBtn');
    
    if (applyBtn) {
        applyBtn.addEventListener('click', handleInternetApplyClick);
    }
})();
</script>

<?php include '../includes/footer.php'; ?>



