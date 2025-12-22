<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'internets';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 로그인 체크를 위한 auth-functions 포함
require_once '../includes/data/auth-functions.php';
require_once '../includes/data/db-config.php';
require_once '../includes/data/product-functions.php';
require_once '../includes/data/privacy-functions.php';

// 개인정보 설정 로드
$privacySettings = getPrivacySettings();

// 헤더에 CSS 추가를 위한 플래그 설정
$add_inline_css = true;
// 헤더 포함
include '../includes/header.php';

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

// 특정 상품 ID 파라미터 확인 (관리자가 특정 상품을 볼 때)
$productId = isset($_GET['id']) ? intval($_GET['id']) : null;

// 데이터베이스에서 인터넷 상품 목록 가져오기
$internetProducts = [];
try {
    $pdo = getDBConnection();
    if ($pdo) {
        // 관리자는 inactive 상태도 볼 수 있음
        $statusCondition = $isAdmin ? "AND p.status != 'deleted'" : "AND p.status = 'active'";
        
        // 특정 상품 ID가 있으면 해당 상품만 조회
        $whereClause = "WHERE p.product_type = 'internet' {$statusCondition}";
        if ($productId) {
            $whereClause .= " AND p.id = :product_id";
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.seller_id,
                p.status,
                p.view_count,
                p.favorite_count,
                p.application_count,
                inet.registration_place,
                inet.service_type,
                inet.speed_option,
                inet.monthly_fee,
                inet.cash_payment_names,
                inet.cash_payment_prices,
                inet.gift_card_names,
                inet.gift_card_prices,
                inet.equipment_names,
                inet.equipment_prices,
                inet.installation_names,
                inet.installation_prices,
                inet.promotion_title,
                inet.promotions
            FROM products p
            INNER JOIN product_internet_details inet ON p.id = inet.product_id
            {$whereClause}
            ORDER BY p.created_at DESC
        ");
        
        if ($productId) {
            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $internetProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // JSON 필드 디코딩
        foreach ($internetProducts as &$product) {
            $jsonFields = [
                'cash_payment_names', 'cash_payment_prices',
                'gift_card_names', 'gift_card_prices',
                'equipment_names', 'equipment_prices',
                'installation_names', 'installation_prices'
            ];
            
            foreach ($jsonFields as $field) {
                if (!empty($product[$field])) {
                    $decoded = json_decode($product[$field], true);
                    $product[$field] = is_array($decoded) ? $decoded : [];
                } else {
                    $product[$field] = [];
                }
            }
            
            // 프로모션 필드 디코딩
            if (!empty($product['promotions'])) {
                $product['promotions'] = json_decode($product['promotions'], true) ?: [];
            } else {
                $product['promotions'] = [];
            }
        }
        unset($product);
    }
} catch (PDOException $e) {
    error_log("Error fetching Internet products: " . $e->getMessage());
}

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
?>

<main class="main-content">
    <!-- 필터 버튼을 main-content 바로 아래로 이동하여 sticky 적용 -->
    <div class="plans-filter-section">
        <div class="plans-filter-inner">
            <div class="plans-filter-group">
                <div class="plans-filter-row">
                    <button class="plans-filter-btn">
                        <span class="plans-filter-text">#100MB</span>
                    </button>
                    <button class="plans-filter-btn">
                        <span class="plans-filter-text">#500MB</span>
                    </button>
                    <button class="plans-filter-btn">
                        <span class="plans-filter-text">#1G</span>
                    </button>
                    <button class="plans-filter-btn">
                        <span class="plans-filter-text">#TV 결합</span>
                    </button>
                </div>
            </div>
        </div>
        <hr class="plans-filter-divider">
    </div>
    
    <div class="PlanDetail_content_wrapper__0YNeJ">
        <div class="tw-w-full">
            <div class="css-2l6pil e1ebrc9o0">
                <?php if (empty($internetProducts)): ?>
                    <div style="text-align: center; padding: 60px 20px; color: #6b7280;">
                        <p>등록된 인터넷 상품이 없습니다.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($internetProducts as $product): ?>
                        <?php
                        $iconPath = getInternetIconPath($product['registration_place']);
                        $speedOption = htmlspecialchars($product['speed_option'] ?? '');
                        $applicationCount = number_format($product['application_count'] ?? 0);
                        // 월 요금 처리: DB에 저장된 값이 "17000원" 형식이면 그대로 표시
                        $monthlyFeeRaw = $product['monthly_fee'] ?? '';
                        if (!empty($monthlyFeeRaw)) {
                            // DECIMAL 타입에서 가져온 경우 소수점 제거
                            if (is_numeric($monthlyFeeRaw)) {
                                // 숫자만 있는 경우 (DECIMAL에서 가져온 경우)
                                $numericValue = (int)floatval($monthlyFeeRaw); // 소수점 제거하고 정수로 변환
                                $monthlyFee = number_format($numericValue, 0, '', ',') . '원';
                            } elseif (preg_match('/^(\d+)(.+)$/', $monthlyFeeRaw, $matches)) {
                                // "17000원" 형식인 경우
                                $numericValue = (int)$matches[1]; // 정수로 변환
                                $monthlyFee = number_format($numericValue, 0, '', ',') . $matches[2];
                            } else {
                                // 그 외의 경우
                                $numericValue = (int)floatval($monthlyFeeRaw);
                                $monthlyFee = number_format($numericValue, 0, '', ',') . '원';
                            }
                        } else {
                            $monthlyFee = '0원';
                        }
                        
                        // 혜택 정보 파싱
                        $cashNames = $product['cash_payment_names'] ?? [];
                        $cashPrices = $product['cash_payment_prices'] ?? [];
                        $giftNames = $product['gift_card_names'] ?? [];
                        $giftPrices = $product['gift_card_prices'] ?? [];
                        $equipNames = $product['equipment_names'] ?? [];
                        $equipPrices = $product['equipment_prices'] ?? [];
                        $installNames = $product['installation_names'] ?? [];
                        $installPrices = $product['installation_prices'] ?? [];
                        ?>
                        <div>
                            <div class="css-58gch7 e82z5mt0" data-product-id="<?php echo $product['id']; ?>">
                                <div class="css-1kjyj6z e82z5mt1">
                                    <?php if ($iconPath): ?>
                                        <img data-testid="internet-company-logo" src="<?php echo htmlspecialchars($iconPath); ?>" 
                                             alt="<?php echo htmlspecialchars($product['registration_place']); ?>" 
                                             class="css-1pg8bi e82z5mt15"
                                             style="<?php echo ($product['registration_place'] === 'KT') ? 'height: 24px;' : (($product['registration_place'] === 'DLIVE') ? 'height: 35px; object-fit: cover;' : 'max-height: 40px; object-fit: contain;'); ?>">
                                    <?php else: ?>
                                        <span><?php echo htmlspecialchars($product['registration_place']); ?></span>
                                    <?php endif; ?>
                                    <?php 
                                    // 서비스 타입 표시
                                    $serviceType = $product['service_type'] ?? '인터넷';
                                    $serviceTypeDisplay = $serviceType;
                                    if ($serviceType === '인터넷+TV') {
                                        $serviceTypeDisplay = '인터넷 + TV 결합';
                                    } elseif ($serviceType === '인터넷+TV+핸드폰') {
                                        $serviceTypeDisplay = '인터넷 + TV + 핸드폰 결합';
                                    }
                                    ?>
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
                                            <?php echo $speedOption; ?>
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
                                            <?php echo $applicationCount; ?>개 신청
                                        </div>
                                    </div>
                                </div>
                                <div class="css-174t92n e82z5mt7">
                                    <?php
                                    // 현금 지급 혜택 표시
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
                                    <p class="css-16qot29 e82z5mt6">월 <?php echo htmlspecialchars($monthlyFee); ?></p>
                                </div>
                                
                                <!-- 프로모션 아코디언 -->
                                <?php
                                $promotionTitle = $product['promotion_title'] ?? '';
                                $promotions = $product['promotions'] ?? [];
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
                                <div class="plan-accordion-box" style="margin-top: 12px; padding: 12px 0;" onclick="event.stopPropagation();">
                                    <div class="plan-accordion">
                                        <button type="button" class="plan-accordion-trigger" aria-expanded="false" style="padding: 12px 16px;" onclick="event.stopPropagation();">
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
                                        <div class="plan-accordion-content" style="display: none;" onclick="event.stopPropagation();">
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
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

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
        <div class="apply-modal-body" id="applyModalBody">
            <!-- 2단계: 기존 인터넷 회선 선택 -->
            <div class="apply-modal-step" id="step2" style="display: none;">
                <div class="plan-order-section">
                    <div class="plan-order-checkbox-group" id="internetCompanyButtons">
                        <div class="plan-order-checkbox-item">
                            <input type="radio" id="internetCompany_none" name="internetCompany" value="none" class="plan-order-checkbox-input">
                            <label for="internetCompany_none" class="plan-order-checkbox-label">
                                <div class="plan-order-checkbox-content">
                                    <div class="plan-order-checkbox-title">인터넷이 없어요</div>
                                    <div class="plan-order-checkbox-description">새로운 인터넷 회선을 신청할래요</div>
                                </div>
                            </label>
                        </div>
                        <div class="plan-order-checkbox-item">
                            <input type="radio" id="internetCompany_ktskylife" name="internetCompany" value="KT SkyLife" class="plan-order-checkbox-input">
                            <label for="internetCompany_ktskylife" class="plan-order-checkbox-label">
                                <div class="plan-order-checkbox-content">
                                    <div class="plan-order-checkbox-title">KT SkyLife</div>
                                    <div class="plan-order-checkbox-description">KT SkyLife 회선을 사용 중이에요</div>
                                </div>
                            </label>
                        </div>
                        <div class="plan-order-checkbox-item">
                            <input type="radio" id="internetCompany_hellovision" name="internetCompany" value="HelloVision" class="plan-order-checkbox-input">
                            <label for="internetCompany_hellovision" class="plan-order-checkbox-label">
                                <div class="plan-order-checkbox-content">
                                    <div class="plan-order-checkbox-title">HelloVision</div>
                                    <div class="plan-order-checkbox-description">HelloVision 회선을 사용 중이에요</div>
                                </div>
                            </label>
                        </div>
                        <div class="plan-order-checkbox-item">
                            <input type="radio" id="internetCompany_btv" name="internetCompany" value="BTV" class="plan-order-checkbox-input">
                            <label for="internetCompany_btv" class="plan-order-checkbox-label">
                                <div class="plan-order-checkbox-content">
                                    <div class="plan-order-checkbox-title">BTV</div>
                                    <div class="plan-order-checkbox-description">BTV 회선을 사용 중이에요</div>
                                </div>
                            </label>
                        </div>
                        <div class="plan-order-checkbox-item">
                            <input type="radio" id="internetCompany_dlive" name="internetCompany" value="DLive" class="plan-order-checkbox-input">
                            <label for="internetCompany_dlive" class="plan-order-checkbox-label">
                                <div class="plan-order-checkbox-content">
                                    <div class="plan-order-checkbox-title">DLive</div>
                                    <div class="plan-order-checkbox-description">DLive 회선을 사용 중이에요</div>
                                </div>
                            </label>
                        </div>
                        <div class="plan-order-checkbox-item">
                            <input type="radio" id="internetCompany_lgu" name="internetCompany" value="LG U+" class="plan-order-checkbox-input">
                            <label for="internetCompany_lgu" class="plan-order-checkbox-label">
                                <div class="plan-order-checkbox-content">
                                    <div class="plan-order-checkbox-title">LG U+</div>
                                    <div class="plan-order-checkbox-description">LG U+ 회선을 사용 중이에요</div>
                                </div>
                            </label>
                        </div>
                        <div class="plan-order-checkbox-item">
                            <input type="radio" id="internetCompany_kt" name="internetCompany" value="KT" class="plan-order-checkbox-input">
                            <label for="internetCompany_kt" class="plan-order-checkbox-label">
                                <div class="plan-order-checkbox-content">
                                    <div class="plan-order-checkbox-title">KT</div>
                                    <div class="plan-order-checkbox-description">KT 회선을 사용 중이에요</div>
                                </div>
                            </label>
                        </div>
                        <div class="plan-order-checkbox-item">
                            <input type="radio" id="internetCompany_broadband" name="internetCompany" value="Broadband" class="plan-order-checkbox-input">
                            <label for="internetCompany_broadband" class="plan-order-checkbox-label">
                                <div class="plan-order-checkbox-content">
                                    <div class="plan-order-checkbox-title">Broadband</div>
                                    <div class="plan-order-checkbox-description">Broadband 회선을 사용 중이에요</div>
                                </div>
                            </label>
                        </div>
                        <div class="plan-order-checkbox-item">
                            <input type="radio" id="internetCompany_other" name="internetCompany" value="기타" class="plan-order-checkbox-input">
                            <label for="internetCompany_other" class="plan-order-checkbox-label">
                                <div class="plan-order-checkbox-content">
                                    <div class="plan-order-checkbox-title">기타</div>
                                    <div class="plan-order-checkbox-description">다른 회선을 사용 중이에요</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 3단계: 개인정보 동의 및 신청 -->
            <div class="apply-modal-step" id="step3" style="display: none;">
                <!-- 인터넷 회선 정보 표시 -->
                <div class="internet-info-section" style="margin-bottom: 1.5rem;">
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
                    <div class="internet-info-arrow" style="display: none;">
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
                
                <form id="internetApplicationForm" class="consultation-form">
                    <input type="hidden" name="product_id" id="internetProductId" value="">
                    <input type="hidden" name="current_company" id="internetCurrentCompany" value="">
                    
                    <div class="consultation-form-group">
                        <label for="internetName" class="consultation-form-label">이름</label>
                        <input type="text" id="internetName" name="name" class="consultation-form-input" required>
                    </div>
                    
                    <div class="consultation-form-group">
                        <label for="internetPhone" class="consultation-form-label">휴대폰번호</label>
                        <input type="tel" id="internetPhone" name="phone" class="consultation-form-input" placeholder="010-1234-5678" required>
                        <span id="internetPhoneError" class="form-error-message" style="display: none; color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem;"></span>
                    </div>
                    
                    <div class="consultation-form-group">
                        <label for="internetEmail" class="consultation-form-label">이메일</label>
                        <input type="email" id="internetEmail" name="email" class="consultation-form-input" placeholder="example@email.com" required>
                        <span id="internetEmailError" class="form-error-message" style="display: none; color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem;"></span>
                    </div>
                    
                    <!-- 체크박스 -->
                    <div class="internet-checkbox-group">
                        <label class="internet-checkbox-all">
                            <input type="checkbox" id="agreeAll" class="internet-checkbox-input">
                            <span class="internet-checkbox-label">전체 동의</span>
                        </label>
                        <div class="internet-checkbox-list">
                            <?php
                            // 동의 항목 정의 (순서대로)
                            $agreementItems = [
                                'purpose' => ['id' => 'agreePurpose', 'name' => 'agreementPurpose', 'modal' => 'openInternetPrivacyModal'],
                                'items' => ['id' => 'agreeItems', 'name' => 'agreementItems', 'modal' => 'openInternetPrivacyModal'],
                                'period' => ['id' => 'agreePeriod', 'name' => 'agreementPeriod', 'modal' => 'openInternetPrivacyModal'],
                                'thirdParty' => ['id' => 'agreeThirdParty', 'name' => 'agreementThirdParty', 'modal' => 'openInternetPrivacyModal'],
                                'serviceNotice' => ['id' => 'agreeServiceNotice', 'name' => 'service_notice_opt_in', 'accordion' => 'internetServiceNoticeContent', 'accordionFunc' => 'toggleInternetAccordion'],
                                'marketing' => ['id' => 'agreeMarketing', 'name' => 'marketing_opt_in', 'accordion' => 'internetMarketingContent', 'accordionFunc' => 'toggleInternetAccordion']
                            ];
                            
                            foreach ($agreementItems as $key => $item):
                                $setting = $privacySettings[$key] ?? [];
                                $title = htmlspecialchars($setting['title'] ?? '');
                                $isRequired = $setting['isRequired'] ?? ($key !== 'marketing');
                                $requiredText = $isRequired ? '(필수)' : '(선택)';
                                $requiredColor = $isRequired ? '#4f46e5' : '#6b7280';
                                $requiredAttr = $isRequired ? 'required' : '';
                            ?>
                            <div class="internet-checkbox-item-wrapper">
                                <div class="internet-checkbox-item">
                                    <label class="internet-checkbox-label-item">
                                        <input type="checkbox" id="<?php echo $item['id']; ?>" name="<?php echo $item['name']; ?>" class="internet-checkbox-input-item" <?php echo $requiredAttr; ?>>
                                        <span class="internet-checkbox-text" style="font-size: 1.0625rem !important;"><?php echo $title; ?> <span style="color: <?php echo $requiredColor; ?>; font-weight: 600;"><?php echo $requiredText; ?></span></span>
                                    </label>
                                    <?php if (isset($item['modal'])): ?>
                                    <a href="#" class="internet-checkbox-link" id="<?php echo $key; ?>ArrowLink" onclick="event.preventDefault(); <?php echo $item['modal']; ?>('<?php echo $key; ?>'); return false;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                            <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                        </svg>
                                    </a>
                                    <?php elseif (isset($item['accordion'])): ?>
                                    <a href="#" class="internet-checkbox-link" onclick="event.preventDefault(); <?php echo $item['accordionFunc']; ?>('<?php echo $item['accordion']; ?>', this); return false;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                            <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php if ($key === 'serviceNotice'): ?>
                                <div class="internet-accordion-content" id="internetServiceNoticeContent">
                                    <div class="internet-accordion-inner">
                                        <div class="internet-accordion-section">
                                            <div style="font-size: 0.875rem; color: #6b7280; line-height: 1.65;">
                                                <?php echo $setting['content'] ?? ''; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php elseif ($key === 'marketing'): ?>
                                <div class="internet-accordion-content" id="internetMarketingContent">
                                    <div class="internet-accordion-inner">
                                        <div class="internet-accordion-section">
                                            <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 0.75rem 0;">광고성 정보를 받으시려면 아래 항목을 선택해주세요</p>
                                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                    <input type="checkbox" id="internetMarketingEmail" name="marketing_email_opt_in" class="internet-marketing-channel" style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;">
                                                    <span style="font-size: 0.875rem; color: #374151;">이메일 수신동의</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                    <input type="checkbox" id="internetMarketingSmsSns" name="marketing_sms_sns_opt_in" class="internet-marketing-channel" style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;">
                                                    <span style="font-size: 0.875rem; color: #374151;">SMS, SNS 수신동의</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                    <input type="checkbox" id="internetMarketingPush" name="marketing_push_opt_in" class="internet-marketing-channel" style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;">
                                                    <span style="font-size: 0.875rem; color: #374151;">앱 푸시 수신동의</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- 상담 신청 버튼 -->
                    <button type="submit" class="consultation-submit-btn" id="submitBtn" disabled>신청하기</button>
                </form>
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

<!-- 토스트 메시지 모달 -->
<div id="internetToastModal" class="internet-toast-modal">
    <div class="internet-toast-overlay"></div>
    <div class="internet-toast-content">
        <div class="internet-toast-icon" id="internetToastIcon"></div>
        <div class="internet-toast-title" id="internetToastTitle">인터넷 상담을 신청했어요</div>
        <div class="internet-toast-message" id="internetToastMessage">입력한 번호로 상담 전화를 드릴예정이에요</div>
        <button class="internet-toast-button" onclick="closeInternetToast()">확인</button>
    </div>
</div>

<style>
/* Main content background */
.main-content {
    background-color: #F8F9FA;
    min-height: 100vh;
}

/* Tailwind-like utilities */
.tw-w-full {
    width: 100%;
}

.tw-text-indigo-600 {
    color: #4f46e5;
}


/* Filter section - plans.php와 동일한 스타일 사용 (assets/css/style.css에 정의됨) */
/* 필터 버튼 내 이미지 아이콘 스타일 추가 */
.plans-filter-btn img {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.plans-filter-btn.active img,
.plans-filter-btn.selected img {
    opacity: 1;
}

/* Product cards container */
.PlanDetail_content_wrapper__0YNeJ {
    width: 100%;
    max-width: 100%;
}

.css-2l6pil.e1ebrc9o0 {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    padding: 1rem 1.5rem;
    /* 필터 아래에 위치하도록 기본 여백 추가 */
    padding-top: 100px;
    transition: padding-top 0.3s ease-in-out;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

/* PC에서 메뉴보다 작게 제한 */
@media (min-width: 1024px) {
    .PlanDetail_content_wrapper__0YNeJ {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .css-2l6pil.e1ebrc9o0 {
        max-width: 100%;
    }
}

/* 필터가 sticky일 때 추가 여백 */
.css-2l6pil.e1ebrc9o0.filter-active {
    padding-top: 50px;
}

/* Product card wrapper */
.css-2l6pil.e1ebrc9o0 > div {
    width: 100%;
    display: flex;
    flex-direction: column;
}

/* Product card */
.css-58gch7.e82z5mt0 {
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    background-color: #ffffff;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease, transform 0.3s ease, border-color 0.3s ease;
    cursor: pointer;
    width: 100%;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
}

.css-58gch7.e82z5mt0:hover {
    box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
    border-color: #d1d5db;
}

.css-1kjyj6z.e82z5mt1 {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
    width: 100%;
    flex-wrap: wrap;
}

.css-1kjyj6z.e82z5mt1 > *:first-child,
.css-1kjyj6z.e82z5mt1 > span {
    flex-shrink: 0;
}

.css-1pg8bi.e82z5mt15 {
    width: 120px;
    height: auto;
    object-fit: contain;
}

.css-huskxe.e82z5mt13 {
    display: flex;
    gap: 0.75rem;
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
    font-size: 1.2rem;
    color: #6b7280;
    white-space: nowrap;
    flex-shrink: 0;
}

.css-1fd5u73.e82z5mt14 img {
    width: 16px;
    height: 16px;
}

/* Benefits section */
.css-174t92n.e82z5mt7 {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin: 1rem 0;
    padding: 1rem;
    background-color: #f9fafb;
    border-radius: 0.5rem;
    width: 100%;
    box-sizing: border-box;
}

.css-12zfa6z.e82z5mt8 {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
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
    font-size: 1.3125rem;
    font-weight: 500;
    color: #4b5563;
    margin: 0;
    line-height: 1.5;
}

.css-2ht76o.e82z5mt12.item-name-text {
    color: #6b7280 !important;
    font-weight: 400 !important;
    font-size: 1.0584rem !important; /* 0.882rem의 120% (20% 증가) */
}

.item-price-text {
    color: #4b5563;
    font-weight: 600;
}

.css-1j35abw.e82z5mt11 {
    font-size: 1.125rem;
    color: #6b7280;
    margin: 0;
    font-weight: 400;
}

/* Price section */
.css-rkh09p.e82z5mt2 {
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
    width: 100%;
}

.css-16qot29.e82z5mt6 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #4b5563;
    margin: 0;
    text-align: right;
}

@media (max-width: 767px) {
    .css-2l6pil.e1ebrc9o0 {
        padding: 1rem;
        gap: 1rem;
    }
    
    .css-58gch7.e82z5mt0 {
        padding: 1rem;
        width: 100%;
    }
    
    .css-1kjyj6z.e82z5mt1 {
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        justify-content: space-between;
    }
    
    .css-huskxe.e82z5mt13 {
        gap: 0.5rem;
        flex-shrink: 0;
        flex-wrap: nowrap;
        justify-content: flex-end;
        margin-left: auto;
        width: auto;
    }
    
    .css-1fd5u73.e82z5mt14 {
        white-space: nowrap;
        font-size: 1rem;
    }
    
    @media (min-width: 480px) {
        .css-huskxe.e82z5mt13 {
            width: auto;
        }
    }
    
    .css-1fd5u73.e82z5mt14 {
        font-size: 1rem;
        white-space: normal;
    }
    
    .css-1pg8bi.e82z5mt15 {
        width: 80px;
        max-width: 80px;
        flex-shrink: 1;
    }
}

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
    /* 스크롤바 숨기기 */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

/* Step 2가 활성화될 때 모달 높이 증가 */
.internet-modal-content:has(#step2.active) {
    max-height: 95vh;
}

.internet-modal-content::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
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


.apply-modal-step {
    display: none;
}

.apply-modal-step.active {
    display: block;
}

/* Step 2: 기존 인터넷 회선 선택 - 높이 증가 */
#step2 {
    min-height: 400px;
}

/* 인터넷 정보 섹션 */
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

/* 기존 인터넷 회선 카드 배경색 */
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

/* 기존 인터넷 회선이 없을 때 화살표 숨기기 (JavaScript로 처리) */
.internet-info-arrow.hidden {
    display: none;
}

/* 모바일 반응형 */
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

/* 알림 메시지 (Callout) */
.internet-callout {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background-color: #f9fafb;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
}

.internet-callout-content {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}

.internet-callout-icon {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    margin-top: 2px;
}

.internet-callout-icon svg {
    width: 100%;
    height: 100%;
}

.internet-callout-text {
    flex: 1;
    font-size: 0.875rem;
    color: #374151;
    font-weight: 500;
    line-height: 1.5;
}


/* 체크박스 스타일 */
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

/* 전체동의 원형 체크박스 */
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
    font-size: 1.0625rem !important; /* 17px - 플랜 카드의 "통화 기본제공 | 문자 무제한 | KT알뜰폰 | 5G" 텍스트와 동일한 크기 */
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
    padding: 0.6rem; /* 클릭 영역 확대 */
    min-width: 2.88rem; /* 최소 너비 설정 */
    min-height: 2.88rem; /* 최소 높이 설정 (기존 높이에서 20% 증가) */
    border-radius: 0.25rem;
    transition: background-color 0.2s;
}

.internet-checkbox-link svg {
    width: 18px; /* 아이콘 크기 증가 */
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
.internet-checkbox-item-wrapper {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.internet-accordion-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    margin-top: 0;
    margin-left: 2rem;
}

.internet-accordion-content.active {
    max-height: 90px;
    overflow-y: auto;
    overflow-x: hidden;
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

/* 제출 버튼 */

/* 토스트 메시지 모달 */
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
    font-size: 1.125rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.75rem;
    text-align: center;
}

.internet-toast-message {
    font-size: 0.9375rem;
    color: #4b5563;
    margin-bottom: 1.5rem;
    text-align: center;
    line-height: 1.5;
    word-break: keep-all;
    word-spacing: -0.02em;
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
    transition: all 0.2s ease;
}

.internet-toast-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.4);
}

.internet-toast-button:active {
    transform: translateY(0);
}

@media (max-width: 767px) {
    .internet-callout {
        padding: 0.875rem;
    }
    
    .internet-callout-text {
        font-size: 0.8125rem;
    }
    
    
    .internet-toast-content {
        max-width: 95%;
        width: calc(100% - 1.5rem);
        padding: 1.75rem 1.5rem;
    }
    
    .internet-toast-message {
        font-size: 0.9375rem;
        white-space: nowrap;
    }
    
    .internet-checkbox-list {
        margin-left: 1.5rem;
    }
    
    #step2 {
        min-height: 450px;
    }
}

/* 개인정보 내용보기 모달 스타일은 assets/css/style.css에 정의되어 있음 */
</style>

<script>
// 필터가 화면에서 사라질 때 상단에 고정 (plans.php와 동일)
(function() {
    const filterSection = document.querySelector('.plans-filter-section');
    
    if (!filterSection) return;
    
    let lastScrollTop = 0;
    let isFilterSticky = false;
    let isFilterFixed = false;
    let filterOriginalTop = 0;
    
    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const filterRect = filterSection.getBoundingClientRect();
        const filterTop = filterRect.top;
        
        // 필터의 원래 위치 저장 (처음 로드 시)
        if (filterOriginalTop === 0 && scrollTop === 0) {
            filterOriginalTop = filterRect.top + scrollTop;
        }
        
        // 스크롤이 시작되면 sticky 모드로 전환
        if (scrollTop > 10 && !isFilterSticky) {
            filterSection.classList.add('filter-sticky');
            isFilterSticky = true;
        }
        
        // 필터가 화면 상단 밖으로 나갔는지 확인 (위로 스크롤해서 사라짐)
        if (filterTop < 0 && isFilterSticky && !isFilterFixed) {
            // 필터가 사라졌으므로 상단에 고정
            filterSection.classList.remove('filter-sticky');
            filterSection.classList.add('filter-fixed');
            isFilterFixed = true;
        } 
        // 스크롤이 다시 위로 올라가서 필터의 원래 위치 근처에 도달했는지 확인
        else if (scrollTop < filterOriginalTop - 50 && isFilterFixed) {
            // 필터를 sticky 모드로 복원
            filterSection.classList.remove('filter-fixed');
            filterSection.classList.add('filter-sticky');
            isFilterFixed = false;
        }
        // 스크롤이 맨 위로 돌아갔을 때
        else if (scrollTop <= 10 && isFilterSticky) {
            // 필터를 원래 위치로 복원
            filterSection.classList.remove('filter-sticky');
            filterSection.classList.remove('filter-fixed');
            isFilterSticky = false;
            isFilterFixed = false;
        }
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }
    
    // 스크롤 이벤트 리스너
    window.addEventListener('scroll', handleScroll, { passive: true });
    
    // 초기 실행
    handleScroll();
})();

// 필터 버튼 클릭 이벤트 핸들러 (plans.php와 동일)
(function() {
    const filterButtons = document.querySelectorAll('.plans-filter-btn');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // 클릭된 버튼의 active 상태 토글
            if (this.classList.contains('active')) {
                this.classList.remove('active');
            } else {
                this.classList.add('active');
            }
        });
    });
})();

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
] : null, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

// 인터넷 카드 클릭 이벤트 핸들러
document.addEventListener('DOMContentLoaded', function() {
    const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
    
    // 인터넷 모달을 열어야 하는지 여부를 저장하는 플래그
    window.shouldOpenInternetModal = false;
    
    // URL 파라미터에서 인터넷 모달을 열어야 하는지 확인 (SNS 로그인 후 리다이렉트)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('openInternetModal') === 'true') {
        window.shouldOpenInternetModal = true;
        // URL에서 파라미터 제거
        const newUrl = window.location.pathname;
        window.history.replaceState({}, '', newUrl);
        // 인터넷 모달 열기
        setTimeout(() => {
            openInternetModal();
        }, 300);
    }
    
    // 이벤트 위임을 사용하여 카드 클릭 처리
    const internetCardsContainer = document.querySelector('.internets-container') || document.querySelector('main') || document.body;
    
    if (internetCardsContainer) {
        internetCardsContainer.addEventListener('click', function(e) {
            // 클릭된 요소가 카드인지 확인
            const card = e.target.closest('.css-58gch7.e82z5mt0');
            
            if (!card) {
                return; // 카드가 아니면 무시
            }
            
            // 모달 내부 클릭은 무시
            if (e.target.closest('.internet-modal')) {
                return;
            }
            
            // 아코디언 영역 클릭은 무시 (카드 클릭 방지)
            if (e.target.closest('.plan-accordion-box') || e.target.closest('.plan-accordion') || e.target.closest('.plan-accordion-trigger')) {
                return;
            }
            
            // 버튼이나 링크 클릭은 무시
            if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A' || e.target.closest('button') || e.target.closest('a')) {
                return;
            }
            
            // 카드에서 product_id 추출
            const productId = card.getAttribute('data-product-id');
            
            if (productId) {
                // 상세페이지로 이동
                e.preventDefault();
                e.stopPropagation();
                window.location.href = '/MVNO/internets/internet-detail.php?id=' + productId;
            }
        });
    }
    
    // 카드에 커서 포인터 스타일 적용
    const internetCards = document.querySelectorAll('.css-58gch7.e82z5mt0');
    internetCards.forEach(card => {
        card.style.cursor = 'pointer';
    });
    
    // 로그인 모달의 로그인 폼 제출 처리 오버라이드 (인터넷 페이지에서만)
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        // 기존 이벤트 리스너 제거를 위해 클론 후 재등록
        const newLoginForm = loginForm.cloneNode(true);
        loginForm.parentNode.replaceChild(newLoginForm, loginForm);
        
        newLoginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/MVNO/api/direct-login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 로그인 모달 닫기
                    if (typeof closeLoginModal === 'function') {
                        closeLoginModal();
                    }
                    
                    // 인터넷 모달을 열어야 하는 경우
                    if (window.shouldOpenInternetModal) {
                        window.shouldOpenInternetModal = false;
                        // 페이지 리로드 대신 인터넷 모달 열기
                        setTimeout(() => {
                            openInternetModal();
                        }, 100);
                    } else {
                        // 일반적인 경우 페이지 리로드
                        window.location.href = data.redirect || '/MVNO/';
                    }
                } else {
                    alert(data.message || '로그인에 실패했습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('로그인 중 오류가 발생했습니다.');
            });
        });
    }
});

// 모달 제어 함수들
let currentStep = 2;
let selectedData = {};
let scrollbarWidth = 0;

function getScrollbarWidth() {
    // 스크롤바 너비 계산
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
        
        // 상태 초기화 (신청 인터넷 회선 정보와 product_id는 유지)
        const newCompanyData = {
            newCompany: selectedData.newCompany,
            newCompanyIcon: selectedData.newCompanyIcon,
            newCompanyLogo: selectedData.newCompanyLogo,
            product_id: selectedData.product_id // product_id 보존
        };
        selectedData = newCompanyData;
        resetSteps();
        // 두 번째 단계 활성화 (기존 인터넷 회선 선택)
        showStep(2);
        // 신청 인터넷 회선 정보 표시
        showNewCompanyInfo();
        // 폼 검증 이벤트 리스너 설정
        if (window.setupFormValidation) {
            window.setupFormValidation();
        }
        
        // 로그인한 사용자 정보 자동 입력 및 검증
        const nameInput = document.getElementById('internetName');
        const phoneInput = document.getElementById('internetPhone');
        const emailInput = document.getElementById('internetEmail');
        
        if (currentUser) {
            if (nameInput && currentUser.name) {
                nameInput.value = currentUser.name;
            }
            if (phoneInput && currentUser.phone) {
                phoneInput.value = currentUser.phone;
            }
            if (emailInput && currentUser.email) {
                emailInput.value = currentUser.email;
            }
        }
        
        // 사용자 정보 로드 후 검증 수행 (즉시 검증)
        setTimeout(function() {
            validatePhoneOnModal();
            validateEmailOnModal();
            checkAllAgreements();
        }, 50);
    }
}

function closeInternetModal() {
    const modal = document.getElementById('internetModal');
    const modalContent = modal ? modal.querySelector('.internet-modal-content') : null;
    
    if (modal && modalContent) {
        // 모바일에서 닫기 애니메이션 적용
        if (window.innerWidth <= 767) {
            modalContent.classList.add('closing');
            setTimeout(() => {
                modal.classList.remove('active');
                modalContent.classList.remove('closing');
                // body 스크롤 복원
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                // 상태 초기화
                currentStep = 2;
                selectedData = {};
                resetSteps();
            }, 300);
        } else {
            modal.classList.remove('active');
            // body 스크롤 복원
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            // 상태 초기화
            currentStep = 2;
            selectedData = {};
            resetSteps();
        }
    }
}

function showStep(step) {
    // 모든 단계 숨기기
    const steps = document.querySelectorAll('.apply-modal-step');
    steps.forEach(s => {
        s.classList.remove('active');
        s.style.display = 'none';
    });
    
    // 현재 단계 표시
    const currentStepEl = document.getElementById('step' + step);
    if (currentStepEl) {
        currentStepEl.classList.add('active');
        currentStepEl.style.display = 'block';
        currentStep = step;
        
        // step 3로 이동할 때 로그인한 사용자 정보 자동 입력 및 검증
        if (step === 3) {
            const nameInput = document.getElementById('internetName');
            const phoneInput = document.getElementById('internetPhone');
            const emailInput = document.getElementById('internetEmail');
            
            if (currentUser) {
                if (nameInput && currentUser.name && !nameInput.value) {
                    nameInput.value = currentUser.name;
                }
                if (phoneInput && currentUser.phone && !phoneInput.value) {
                    phoneInput.value = currentUser.phone;
                }
                if (emailInput && currentUser.email && !emailInput.value) {
                    emailInput.value = currentUser.email;
                }
            }
            
            // step3로 이동할 때 전화번호와 이메일 즉시 검증
            // DOM 업데이트를 기다리지 않고 즉시 검증
            validatePhoneOnModal();
            validateEmailOnModal();
            setTimeout(function() {
                validatePhoneOnModal();
                validateEmailOnModal();
                checkAllAgreements();
            }, 50); // DOM 업데이트 후 재검증
        }
    }
}

function resetSteps() {
    const steps = document.querySelectorAll('.apply-modal-step');
    steps.forEach(s => {
        s.classList.remove('active');
        s.style.display = 'none';
    });
    // 라디오 버튼 초기화
    const radioButtons = document.querySelectorAll('input[name="internetCompany"]');
    radioButtons.forEach(radio => {
        radio.checked = false;
    });
    // 선택된 항목의 체크 스타일 제거
    document.querySelectorAll('.plan-order-checkbox-item').forEach(item => {
        item.classList.remove('plan-order-checkbox-checked');
    });
    // 모달 제목 초기화
    updateModalTitle('기존 인터넷 회선 선택');
    // 현재 설치회사 정보 숨기기
    hideCurrentCompanyInfo();
    // 선택한 인터넷 정보 표시
    showNewCompanyInfo();
    // 체크박스 초기화
    const agreeAll = document.getElementById('agreeAll');
    const agreeThirdParty = document.getElementById('agreeThirdParty');
    const submitBtn = document.getElementById('submitBtn');
    if (agreeAll) agreeAll.checked = false;
    if (agreeThirdParty) agreeThirdParty.checked = false;
    if (submitBtn) submitBtn.disabled = true; // 기본적으로 비활성화
    
    // 초기화 후 버튼 상태 확인
    setTimeout(() => {
        if (typeof checkAllAgreements === 'function') {
            checkAllAgreements();
        }
    }, 100);
    // 입력 필드 초기화
    const nameInput = document.getElementById('internetName');
    const phoneInput = document.getElementById('internetPhone');
    const emailInput = document.getElementById('internetEmail');
    if (nameInput) nameInput.value = '';
    if (phoneInput) phoneInput.value = '';
    if (emailInput) emailInput.value = '';
}

// 라디오 버튼 이벤트 핸들러 (기존 인터넷 회선 선택)
document.addEventListener('DOMContentLoaded', function() {
    const companyRadioButtons = document.querySelectorAll('input[name="internetCompany"]');
    
    companyRadioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                const company = this.value;
                const companyIcons = {
                    'none': null,
                    'KT SkyLife': 'ktskylife',
                    'HelloVision': 'hellovision',
                    'BTV': 'btv',
                    'DLive': 'dlive',
                    'LG U+': 'lgu',
                    'KT': 'kt',
                    'Broadband': 'broadband',
                    '기타': 'other'
                };
                
                selectedData.currentCompany = company === 'none' ? null : company;
                selectedData.currentCompanyIcon = companyIcons[company] || null;
                
                // 모든 항목의 체크 스타일 제거
                document.querySelectorAll('.plan-order-checkbox-item').forEach(item => {
                    item.classList.remove('plan-order-checkbox-checked');
                });
                
                // 선택된 항목에 체크 스타일 적용
                this.closest('.plan-order-checkbox-item').classList.add('plan-order-checkbox-checked');
                
                // 선택 즉시 step3로 이동
                setTimeout(() => {
                    if (company === 'none') {
                        updateModalTitle('인터넷 회선 신청');
                        hideCurrentCompanyInfo();
                    } else {
                        updateModalTitle('인터넷 신청');
                        showCurrentCompanyInfo(company, companyIcons[company]);
                    }
                    showStep(3);
                }, 200);
            }
        });
        
        // 라벨 클릭 시 라디오 버튼 선택
        const label = radio.nextElementSibling;
        if (label && label.classList.contains('plan-order-checkbox-label')) {
            label.addEventListener('click', function(e) {
                if (radio.checked === false) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            });
        }
    });
});

function updateModalTitle(title) {
    const titleEl = document.getElementById('modalTitle');
    if (titleEl) {
        // 물음표 제거
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
        // 회사 이름과 아이콘 매핑
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
            // 텍스트 먼저 숨기기
            if (nameEl) nameEl.style.display = 'none';
            
            // 로고 설정
            logoEl.src = logoPath;
            logoEl.alt = company;
            logoEl.style.display = 'block';
            
            // 로고 로드 실패 시 텍스트 표시
            logoEl.onerror = function() {
                this.style.display = 'none';
                if (nameEl) {
                    nameEl.textContent = company;
                    nameEl.style.display = 'inline';
                }
            };
            
            // 로고 로드 성공 시 확인
            logoEl.onload = function() {
                this.style.display = 'block';
                if (nameEl) nameEl.style.display = 'none';
            };
        } else {
            // 로고가 없으면 텍스트 표시
            if (logoEl) logoEl.style.display = 'none';
            if (nameEl) {
                nameEl.textContent = company;
                nameEl.style.display = 'inline';
            }
        }
        
        // 카드 표시
        infoEl.style.display = 'flex';
        
        // 화살표 표시
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
            // 텍스트 먼저 숨기기
            if (nameEl) nameEl.style.display = 'none';
            
            // 로고 설정
            logoEl.src = logoPath;
            logoEl.alt = selectedData.newCompany;
            logoEl.style.display = 'block';
            
            // 로고 로드 실패 시 텍스트 표시
            logoEl.onerror = function() {
                this.style.display = 'none';
                if (nameEl) {
                    nameEl.textContent = selectedData.newCompany;
                    nameEl.style.display = 'inline';
                }
            };
            
            // 로고 로드 성공 시 확인
            logoEl.onload = function() {
                this.style.display = 'block';
                if (nameEl) nameEl.style.display = 'none';
            };
        } else {
            // 로고가 없으면 텍스트 표시
            if (logoEl) logoEl.style.display = 'none';
            if (nameEl) {
                nameEl.textContent = selectedData.newCompany;
                nameEl.style.display = 'inline';
            }
        }
        
        // 카드 표시
        infoEl.style.display = 'flex';
    } else {
        if (infoEl) infoEl.style.display = 'none';
    }
    
    // 기존 인터넷 회선이 없으면 화살표 숨기기
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
    
    // 화살표 숨기기
    if (arrowEl) {
        arrowEl.classList.add('hidden');
    }
}

function toggleAllAgreements(checked) {
    const agreePurpose = document.getElementById('agreePurpose');
    const agreeItems = document.getElementById('agreeItems');
    const agreePeriod = document.getElementById('agreePeriod');
    const agreeThirdParty = document.getElementById('agreeThirdParty');
    const agreeServiceNotice = document.getElementById('agreeServiceNotice');
    const agreeMarketing = document.getElementById('agreeMarketing');

    if (agreePurpose && agreeItems && agreePeriod && agreeThirdParty && agreeServiceNotice) {
        agreePurpose.checked = checked;
        agreeItems.checked = checked;
        agreePeriod.checked = checked;
        agreeThirdParty.checked = checked;
        agreeServiceNotice.checked = checked;
        if (agreeMarketing) {
            agreeMarketing.checked = checked;
            if (checked) {
                toggleInternetMarketingChannels();
            }
        }
        checkAllAgreements();
    }
}

// 인터넷 개인정보 내용 정의 (설정 파일에서 로드)
<?php
require_once __DIR__ . '/../includes/data/privacy-functions.php';
$privacySettings = getPrivacySettings();
echo "const internetPrivacyContents = " . json_encode($privacySettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
?>

// 페이지 로드 시 서비스 이용 및 혜택 안내 알림, 광고성 정보 수신동의 내용 설정
document.addEventListener('DOMContentLoaded', function() {
    // 인터넷 신청 폼 제출 이벤트
    const internetForm = document.getElementById('internetApplicationForm');
    if (internetForm) {
        internetForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitInternetForm();
        });
    }
    
});

// 인터넷 개인정보 내용보기 모달 열기 (전역으로 노출)
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

// 인터넷 개인정보 내용보기 모달 닫기
function closeInternetPrivacyModal() {
    const modal = document.getElementById('internetPrivacyContentModal');
    if (!modal) return;
    
    modal.classList.remove('privacy-content-modal-active');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// 인터넷 개인정보 모달 닫기 이벤트
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
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && internetPrivacyModal && internetPrivacyModal.classList.contains('privacy-content-modal-active')) {
            closeInternetPrivacyModal();
        }
    });
});

function checkAllAgreements() {
    const agreeAll = document.getElementById('agreeAll');
    const submitBtn = document.getElementById('submitBtn');
    const nameInput = document.getElementById('internetName');
    const phoneInput = document.getElementById('internetPhone');
    const emailInput = document.getElementById('internetEmail');

    if (!agreeAll || !submitBtn) return;

    // internetPrivacyContents에서 필수 항목 확인
    const requiredItems = [];
    const agreementMap = {
        'purpose': 'agreePurpose',
        'items': 'agreeItems',
        'period': 'agreePeriod',
        'thirdParty': 'agreeThirdParty',
        'serviceNotice': 'agreeServiceNotice',
        'marketing': 'agreeMarketing'
    };

    if (typeof internetPrivacyContents !== 'undefined') {
        for (const [key, id] of Object.entries(agreementMap)) {
            if (internetPrivacyContents[key] && internetPrivacyContents[key].isRequired) {
                requiredItems.push(id);
            }
        }
    } else {
        // 기본값: marketing 제외 모두 필수
        requiredItems.push('agreePurpose', 'agreeItems', 'agreePeriod', 'agreeThirdParty', 'agreeServiceNotice');
    }

    // 전체 동의 체크박스 상태 업데이트 (필수 항목만 포함)
    let allRequiredChecked = true;
    for (const itemId of requiredItems) {
        const checkbox = document.getElementById(itemId);
        if (checkbox && !checkbox.checked) {
            allRequiredChecked = false;
            break;
        }
    }
    agreeAll.checked = allRequiredChecked;

    // 이름, 휴대폰 번호, 이메일 확인
    const name = nameInput ? nameInput.value.trim() : '';
    const phone = phoneInput ? phoneInput.value.replace(/[^\d]/g, '') : '';
    const email = emailInput ? emailInput.value.trim() : '';

    // 제출 버튼 활성화/비활성화 (모든 필드가 입력되어야 활성화)
    const isNameValid = name.length > 0;
    const isPhoneValid = phone.length === 11 && phone.startsWith('010');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isEmailValid = email.length > 0 && emailRegex.test(email);
    
    // 필수 동의 항목 모두 체크되었는지 확인
    let isAgreementsChecked = true;
    for (const itemId of requiredItems) {
        const checkbox = document.getElementById(itemId);
        if (checkbox && !checkbox.checked) {
            isAgreementsChecked = false;
            break;
        }
    }

    if (isNameValid && isPhoneValid && isEmailValid && isAgreementsChecked) {
        submitBtn.disabled = false;
    } else {
        submitBtn.disabled = true;
    }
}

// 전화번호 검증 함수
function validatePhoneOnModal() {
    const phoneInput = document.getElementById('internetPhone');
    const phoneErrorElement = document.getElementById('internetPhoneError');
    
    if (phoneInput && phoneErrorElement) {
        const value = phoneInput.value.trim();
        const phoneNumbers = value.replace(/[^\d]/g, '');
        
        if (value && phoneNumbers.length > 0) {
            if (phoneNumbers.length === 11 && phoneNumbers.startsWith('010')) {
                phoneInput.classList.remove('input-error');
                phoneErrorElement.style.display = 'none';
                phoneErrorElement.textContent = '';
                return true;
            } else {
                phoneInput.classList.add('input-error');
                phoneErrorElement.style.display = 'block';
                phoneErrorElement.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                return false;
            }
        } else if (value) {
            phoneInput.classList.add('input-error');
            phoneErrorElement.style.display = 'block';
            phoneErrorElement.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
            return false;
        } else {
            phoneInput.classList.remove('input-error');
            phoneErrorElement.style.display = 'none';
            phoneErrorElement.textContent = '';
            return true; // 빈 값은 유효 (필수 필드 검증은 별도)
        }
    }
    return true;
}

// 이메일 검증 함수
function validateEmailOnModal() {
    const emailInput = document.getElementById('internetEmail');
    const emailErrorElement = document.getElementById('internetEmailError');
    
    if (emailInput && emailErrorElement) {
        const value = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (value.length > 0) {
            if (emailRegex.test(value)) {
                emailInput.classList.remove('input-error');
                emailErrorElement.style.display = 'none';
                emailErrorElement.textContent = '';
                return true;
            } else {
                emailInput.classList.add('input-error');
                emailErrorElement.style.display = 'block';
                emailErrorElement.textContent = '이메일 형식에 맞게 입력해주세요. (example@email.com 형식)';
                return false;
            }
        } else {
            emailInput.classList.remove('input-error');
            emailErrorElement.style.display = 'none';
            emailErrorElement.textContent = '';
            return true; // 빈 값은 유효 (필수 필드 검증은 별도)
        }
    }
    return true;
}

// 마케팅 채널 활성화/비활성화 토글 함수
function toggleInternetMarketingChannels() {
    const agreeMarketing = document.getElementById('agreeMarketing');
    const marketingChannels = document.querySelectorAll('.internet-marketing-channel');
    
    if (agreeMarketing && marketingChannels.length > 0) {
        const isEnabled = agreeMarketing.checked;
        marketingChannels.forEach(channel => {
            channel.disabled = !isEnabled;
            if (!isEnabled) {
                channel.checked = false;
            }
        });
    }
}

// 마케팅 채널 변경 시 상위 체크박스 업데이트
document.addEventListener('DOMContentLoaded', function() {
    const marketingChannels = document.querySelectorAll('.internet-marketing-channel');
    const agreeMarketing = document.getElementById('agreeMarketing');
    
    marketingChannels.forEach(channel => {
        channel.addEventListener('change', function() {
            if (agreeMarketing) {
                const anyChecked = Array.from(marketingChannels).some(ch => ch.checked);
                if (anyChecked && !agreeMarketing.checked) {
                    agreeMarketing.checked = true;
                    toggleInternetMarketingChannels();
                }
            }
        });
    });
    
    // 초기 상태 설정
    toggleInternetMarketingChannels();
});

// 아코디언 토글 함수
function toggleInternetAccordion(accordionId, arrowLink) {
    const accordion = document.getElementById(accordionId);
    if (!accordion || !arrowLink) return;
    
    const isOpen = accordion.classList.contains('active');
    if (isOpen) {
        accordion.classList.remove('active');
        arrowLink.classList.remove('arrow-up');
    } else {
        accordion.classList.add('active');
        arrowLink.classList.add('arrow-up');
    }
}

function submitInternetForm() {
    // 로그인 체크
    const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
    if (!isLoggedIn) {
        // 인터넷 모달 닫기
        closeInternetModal();
        
        // 현재 URL을 세션에 저장 (로그인 후 돌아올 주소)
        const currentUrl = window.location.href;
        fetch('/MVNO/api/save-redirect-url.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ redirect_url: currentUrl })
        }).then(() => {
            // 로그인 모달 열기 (false = 로그인 모드)
            if (typeof openLoginModal === 'function') {
                openLoginModal(false);
            } else {
                setTimeout(() => {
                    if (typeof openLoginModal === 'function') {
                        openLoginModal(false);
                    }
                }, 100);
            }
        });
        return;
    }
    
    const nameInput = document.getElementById('internetName');
    const phoneInput = document.getElementById('internetPhone');
    const emailInput = document.getElementById('internetEmail');
    
    const name = nameInput ? nameInput.value.trim() : '';
    const phone = phoneInput ? phoneInput.value.trim() : '';
    const email = emailInput ? emailInput.value.trim() : '';
    
    // 이름 검증
    if (!name) {
        if (nameInput) nameInput.focus();
        return;
    }
    
    // 전화번호 검증
    const phoneErrorElement = document.getElementById('internetPhoneError');
    const phoneNumbers = phone.replace(/[^\d]/g, '');
    if (!phone) {
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
    
    if (phoneNumbers.length !== 11 || !phoneNumbers.startsWith('010')) {
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
    const emailErrorElement = document.getElementById('internetEmailError');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email) {
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
    
    if (!emailRegex.test(email)) {
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
    
    // product_id 확인
    if (!selectedData.product_id) {
        showInternetToast('error', '상품 정보 오류', '상품 정보를 찾을 수 없습니다. 다시 시도해주세요.');
        return;
    }
    
    // hidden input 값 설정
    const productIdInput = document.getElementById('internetProductId');
    const currentCompanyInput = document.getElementById('internetCurrentCompany');
    if (productIdInput) {
        productIdInput.value = selectedData.product_id;
    }
    if (currentCompanyInput) {
        currentCompanyInput.value = selectedData.currentCompany || '';
    }
    
    // 폼 데이터 수집
    const formData = new FormData();
    formData.append('product_id', selectedData.product_id);
    formData.append('name', name);
    formData.append('phone', phone);
    formData.append('email', email);
    
    // 기존 인터넷 회선 정보 추가
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
    
    // 실제 제출 로직
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
            // 인터넷 모달 닫기
            closeInternetModal();
            
            // 토스트 메시지 표시
            showInternetToast('success', '인터넷 상담을 신청했어요', '입력한 번호로 상담 전화를 드릴예정이에요');
        } else {
            console.error('Internet Application Debug - Failed:', data.message);
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
    }
});

// 전화번호 입력 모듈
(function() {
    /**
     * 전화번호 형식으로 변환 (하이픈 자동 추가)
     * @param {string} value - 입력된 값
     * @returns {string} - 포맷된 전화번호
     */
    function formatPhoneNumber(value) {
        // 숫자만 추출
        const numbers = value.replace(/[^\d]/g, '');
        
        // 숫자가 없으면 빈 문자열 반환
        if (!numbers) return '';
        
        // 휴대폰 번호 검증: 반드시 0으로 시작해야 함
        if (numbers.length > 0 && numbers[0] !== '0') {
            // 0으로 시작하지 않으면 이전 값 유지 (입력 차단)
            return '';
        }
        
        // 휴대폰 번호 검증: 010으로 시작해야 함 (일반적인 휴대폰 번호)
        if (numbers.length >= 3 && !numbers.startsWith('010')) {
            // 010으로 시작하지 않으면 이전 값 유지 (입력 차단)
            // 단, 02(서울 지역번호)는 허용
            if (!numbers.startsWith('02')) {
                return '';
            }
        }
        
        // 전화번호 길이에 따라 형식 적용
        if (numbers.length <= 3) {
            // 3자리 이하일 때는 010으로 시작하는지 확인
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
            // 010-123 또는 02-1234
            if (numbers.startsWith('02')) {
                // 서울 지역번호 (02)
                return numbers.slice(0, 2) + '-' + numbers.slice(2);
            } else if (numbers.startsWith('010')) {
                // 휴대폰 (010)
                return numbers.slice(0, 3) + '-' + numbers.slice(3);
            } else {
                return '';
            }
        } else if (numbers.length <= 10) {
            // 010-1234-567 또는 02-1234-5678
            if (numbers.startsWith('02')) {
                // 서울 지역번호 (02-1234-5678)
                return numbers.slice(0, 2) + '-' + numbers.slice(2, 6) + '-' + numbers.slice(6);
            } else if (numbers.startsWith('010')) {
                // 휴대폰 (010-1234-567)
                return numbers.slice(0, 3) + '-' + numbers.slice(3, 7) + '-' + numbers.slice(7);
            } else {
                return '';
            }
        } else {
            // 010-1234-5678 또는 02-1234-5678
            if (numbers.startsWith('02')) {
                // 서울 지역번호 (02-1234-5678)
                return numbers.slice(0, 2) + '-' + numbers.slice(2, 6) + '-' + numbers.slice(6, 10);
            } else if (numbers.startsWith('010')) {
                // 휴대폰 (010-1234-5678)
                return numbers.slice(0, 3) + '-' + numbers.slice(3, 7) + '-' + numbers.slice(7, 11);
            } else {
                return '';
            }
        }
    }
    
    /**
     * 전화번호 입력 필드에 포맷 적용
     * @param {HTMLInputElement} input - 입력 필드 요소
     */
    function applyPhoneFormat(input) {
        // 이전 유효한 값 저장
        let lastValidValue = '';
        
        // 입력 이벤트
        input.addEventListener('input', function(e) {
            const cursorPosition = e.target.selectionStart;
            const oldValue = e.target.value;
            const newValue = formatPhoneNumber(e.target.value);
            
            // 검증 실패 시 (빈 문자열 반환) 이전 유효한 값으로 복원
            if (newValue === '' && oldValue !== '' && oldValue.replace(/[^\d]/g, '').length > 0) {
                // 잘못된 입력이므로 이전 유효한 값으로 복원
                e.target.value = lastValidValue;
                e.target.setSelectionRange(cursorPosition - 1, cursorPosition - 1);
                return;
            }
            
            // 유효한 값이면 저장
            if (newValue !== '') {
                lastValidValue = newValue;
            }
            
            e.target.value = newValue;
            
            // 커서 위치 조정 (하이픈 추가로 인한 위치 변경 보정)
            let newCursorPosition = cursorPosition;
            const oldLength = oldValue.length;
            const newLength = newValue.length;
            
            if (newLength > oldLength) {
                // 하이픈이 추가된 경우 커서 위치 조정
                const addedHyphens = (newValue.match(/-/g) || []).length - (oldValue.match(/-/g) || []).length;
                newCursorPosition = cursorPosition + addedHyphens;
            }
            
            e.target.setSelectionRange(newCursorPosition, newCursorPosition);
            
            // 버튼 활성화 상태 확인
            if (typeof checkAllAgreements === 'function') {
                checkAllAgreements();
            }
        });
        
        // 붙여넣기 이벤트
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const numbers = pastedText.replace(/[^\d]/g, '');
            
            // 붙여넣기 검증: 010으로 시작하는지 확인
            if (numbers.length > 0 && !numbers.startsWith('010')) {
                // 010으로 시작하지 않으면 이전 값 유지
                return;
            }
            
            const formatted = formatPhoneNumber(pastedText);
            if (formatted !== '') {
                input.value = formatted;
                lastValidValue = formatted;
                
                // 버튼 활성화 상태 확인
                if (typeof checkAllAgreements === 'function') {
                    checkAllAgreements();
                }
            }
        });
        
        // 키 입력 제한 (숫자와 백스페이스, 삭제, 화살표 키만 허용)
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
            
            // 숫자가 아닌 키는 기본 검증만 수행
            if (!isNumber && !isAllowedKey && !isCtrlA && !isCtrlC && !isCtrlV && !isCtrlX) {
                e.preventDefault();
                return;
            }
            
            // 숫자 입력 시 검증
            if (isNumber) {
                const currentValue = e.target.value.replace(/[^\d]/g, '');
                const cursorPosition = e.target.selectionStart;
                const textBeforeCursor = e.target.value.substring(0, cursorPosition).replace(/[^\d]/g, '');
                const textAfterCursor = e.target.value.substring(cursorPosition).replace(/[^\d]/g, '');
                const newValue = textBeforeCursor + e.key + textAfterCursor;
                
                // 첫 번째 숫자는 반드시 0이어야 함
                if (currentValue.length === 0 && e.key !== '0') {
                    e.preventDefault();
                    return;
                }
                
                // 두 번째 숫자는 반드시 1이어야 함 (010으로 시작)
                if (currentValue.length === 1 && e.key !== '1') {
                    e.preventDefault();
                    return;
                }
                
                // 세 번째 숫자는 반드시 0이어야 함 (010으로 시작)
                if (currentValue.length === 2 && e.key !== '0') {
                    e.preventDefault();
                    return;
                }
                
                // 최대 11자리까지만 입력 가능 (010-1234-5678)
                if (newValue.length > 11) {
                    e.preventDefault();
                    return;
                }
            }
        });
    }
    
    /**
     * 전화번호 형식으로 변환 함수 (전역으로 노출)
     * @param {string} value - 입력된 값
     * @returns {string} - 포맷된 전화번호
     */
    window.formatPhoneNumber = formatPhoneNumber;
    
    /**
     * 전화번호 양식 적용 함수 (외부에서 호출 가능)
     * @param {string|HTMLInputElement} selector - CSS 선택자 또는 입력 필드 요소
     */
    window.applyPhoneNumberFormat = function(selector) {
        let inputs;
        
        if (typeof selector === 'string') {
            // CSS 선택자로 찾기
            inputs = document.querySelectorAll(selector);
        } else if (selector instanceof HTMLInputElement) {
            // 직접 요소 전달
            inputs = [selector];
        } else {
            // data-phone-format 속성이 있는 모든 입력 필드 찾기
            inputs = document.querySelectorAll('input[data-phone-format="true"]');
        }
        
        inputs.forEach(function(input) {
            if (input.type === 'tel' || input.hasAttribute('data-phone-format')) {
                applyPhoneFormat(input);
            }
        });
    };
    
    // 페이지 로드 시 자동 적용
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.applyPhoneNumberFormat();
            setupFormValidation();
        });
    } else {
        window.applyPhoneNumberFormat();
        setupFormValidation();
    }
    
    // 폼 검증 이벤트 리스너 설정
    function setupFormValidation() {
        const nameInput = document.getElementById('internetName');
        const phoneInput = document.getElementById('internetPhone');
        const emailInput = document.getElementById('internetEmail');
        
        // 인터넷 개인정보 동의 체크박스 처리
        const agreeAll = document.getElementById('agreeAll');
        const agreeItemCheckboxes = document.querySelectorAll('#internetApplicationForm .internet-checkbox-input-item');
        
        // 전체 동의 체크박스 변경 이벤트
        if (agreeAll) {
            agreeAll.addEventListener('change', function() {
                const isChecked = this.checked;
                agreeItemCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
                // 마케팅 체크박스가 체크되면 채널 활성화
                if (isChecked) {
                    const agreeMarketing = document.getElementById('agreeMarketing');
                    if (agreeMarketing && agreeMarketing.checked) {
                        toggleInternetMarketingChannels();
                    }
                }
                checkAllAgreements();
            });
        }
        
        // 개별 체크박스 변경 이벤트 (전체 동의 상태 업데이트)
        agreeItemCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                checkAllAgreements();
                // 마케팅 체크박스인 경우 채널 토글
                if (this.id === 'agreeMarketing') {
                    toggleInternetMarketingChannels();
                }
            });
        });
        
        // 이름 입력 시 검증
        if (nameInput) {
            nameInput.addEventListener('input', checkAllAgreements);
            nameInput.addEventListener('blur', checkAllAgreements);
        }
        
        // 전화번호 실시간 검증
        if (phoneInput) {
            const phoneErrorElement = document.getElementById('internetPhoneError');
            
            // 실시간 포맷팅 및 검증
            phoneInput.addEventListener('input', function() {
                const value = this.value;
                if (typeof window.formatPhoneNumber === 'function') {
                    const formatted = window.formatPhoneNumber(value);
                    if (formatted !== value) {
                        this.value = formatted;
                    }
                }
                
                // 실시간 검증
                const phoneNumbers = this.value.replace(/[^\d]/g, '');
                if (phoneNumbers.length > 0) {
                    if (phoneNumbers.length === 11 && phoneNumbers.startsWith('010')) {
                        this.classList.remove('input-error');
                        if (phoneErrorElement) {
                            phoneErrorElement.style.display = 'none';
                            phoneErrorElement.textContent = '';
                        }
                    } else if (phoneNumbers.length >= 3) {
                        this.classList.add('input-error');
                        if (phoneErrorElement) {
                            phoneErrorElement.style.display = 'block';
                            phoneErrorElement.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                        }
                    }
                } else {
                    this.classList.remove('input-error');
                    if (phoneErrorElement) {
                        phoneErrorElement.style.display = 'none';
                        phoneErrorElement.textContent = '';
                    }
                }
                
                checkAllAgreements();
            });
            
            // 포커스 아웃 시 검증
            phoneInput.addEventListener('blur', function() {
                validatePhoneOnModal();
                checkAllAgreements();
            });
            
            phoneInput.addEventListener('focus', function() {
                this.classList.remove('input-error');
                if (phoneErrorElement) {
                    phoneErrorElement.style.display = 'none';
                    phoneErrorElement.textContent = '';
                }
            });
        }
        
        // 실시간 이메일 검증
        if (emailInput) {
            const emailErrorElement = document.getElementById('internetEmailError');
            
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
            
            emailInput.addEventListener('blur', function() {
                // 포커스 아웃 시에도 소문자로 변환
                this.value = this.value.toLowerCase();
                validateEmailOnModal();
                checkAllAgreements();
            });
            
            emailInput.addEventListener('focus', function() {
                this.classList.remove('input-error');
                if (emailErrorElement) {
                    emailErrorElement.style.display = 'none';
                    emailErrorElement.textContent = '';
                }
            });
        }
    }
    
    // 전역으로 노출
    window.setupFormValidation = setupFormValidation;
})();
</script>


<?php
// 푸터 포함
?>
<!-- 아코디언 스크립트 -->
<script src="/MVNO/assets/js/plan-accordion.js"></script>

<?php include '../includes/footer.php'; ?>
