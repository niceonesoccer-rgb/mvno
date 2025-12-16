<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'internets';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 로그인 체크를 위한 auth-functions 포함
require_once '../includes/data/auth-functions.php';
require_once '../includes/data/db-config.php';
require_once '../includes/data/product-functions.php';

// 헤더 포함
include '../includes/header.php';

// 데이터베이스에서 인터넷 상품 목록 가져오기
$internetProducts = [];
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
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
                inet.installation_prices
            FROM products p
            INNER JOIN product_internet_details inet ON p.id = inet.product_id
            WHERE p.product_type = 'internet' 
            AND p.status = 'active'
            ORDER BY p.created_at DESC
        ");
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
                                    <span style="margin-left: 0.5em; margin-right: 0.5em; font-size: 1.2rem; color: #9ca3af;">|</span>
                                    <span style="font-size: 1.2rem; color: #374151; text-align: left; display: inline-block;"><?php echo htmlspecialchars($serviceTypeDisplay); ?></span>
                                    <div class="css-huskxe e82z5mt13">
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
                                                    <linearGradient id="checkGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                        <stop offset="0%" style="stop-color:#10B981;stop-opacity:1" />
                                                        <stop offset="100%" style="stop-color:#059669;stop-opacity:1" />
                                                    </linearGradient>
                                                </defs>
                                                <circle cx="12" cy="12" r="10" fill="url(#checkGradient)" stroke="#047857" stroke-width="1"/>
                                                <path d="M8 12L10.5 14.5L16 9" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
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
                            <img src="https://assets-legacy.moyoplan.com/internets/assets/ktskylife.svg" alt="KT SkyLife" class="internet-company-logo">
                            <span class="internet-company-name">KT SkyLife</span>
                        </button>
                        <button class="internet-company-btn" onclick="selectInternetCompany('HelloVision', 'hellovision')">
                            <img src="https://assets-legacy.moyoplan.com/internets/assets/hellovision.svg" alt="HelloVision" class="internet-company-logo">
                            <span class="internet-company-name">HelloVision</span>
                        </button>
                        <button class="internet-company-btn" onclick="selectInternetCompany('BTV', 'btv')">
                            <img src="https://assets-legacy.moyoplan.com/internets/assets/btv.svg" alt="BTV" class="internet-company-logo">
                            <span class="internet-company-name">BTV</span>
                        </button>
                        <button class="internet-company-btn" onclick="selectInternetCompany('DLive', 'dlive')">
                            <img src="https://assets-legacy.moyoplan.com/internets/assets/dlive.svg" alt="DLive" class="internet-company-logo">
                            <span class="internet-company-name">DLive</span>
                        </button>
                        <button class="internet-company-btn" onclick="selectInternetCompany('LG U+', 'lgu')">
                            <img src="https://assets-legacy.moyoplan.com/internets/assets/lgu.svg" alt="LG U+" class="internet-company-logo">
                            <span class="internet-company-name">LG U+</span>
                        </button>
                        <button class="internet-company-btn" onclick="selectInternetCompany('KT', 'kt')">
                            <img src="https://assets-legacy.moyoplan.com/internets/assets/kt.svg" alt="KT" class="internet-company-logo">
                            <span class="internet-company-name">KT</span>
                        </button>
                        <button class="internet-company-btn" onclick="selectInternetCompany('Broadband', 'broadband')">
                            <img src="https://assets-legacy.moyoplan.com/internets/assets/broadband.svg" alt="Broadband" class="internet-company-logo">
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

<!-- 토스트 메시지 모달 -->
<div id="internetToastModal" class="internet-toast-modal">
    <div class="internet-toast-overlay"></div>
    <div class="internet-toast-content">
        <div class="internet-toast-title">인터넷 상담을 신청했어요</div>
        <div class="internet-toast-message">입력한 번호로 상담 전화를 드릴예정이에요</div>
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

/* Product card */
.css-58gch7.e82z5mt0 {
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    background-color: #ffffff;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease, transform 0.3s ease, border-color 0.3s ease;
    cursor: pointer;
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
}

.css-1fd5u73.e82z5mt14 {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 1.2rem;
    color: #374151;
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
}

.css-12zfa6z.e82z5mt8 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
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
    font-size: 1.3125rem;
    font-weight: 500;
    color: #1a1a1a;
    margin: 0;
    line-height: 1.5;
}

.css-2ht76o.e82z5mt12.item-name-text {
    color: #6b7280 !important;
    font-weight: 400 !important;
    font-size: 1.0584rem !important; /* 0.882rem의 120% (20% 증가) */
}

.item-price-text {
    color: #1a1a1a;
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
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.css-16qot29.e82z5mt6 {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0;
    text-align: right;
}

@media (max-width: 767px) {
    .css-58gch7.e82z5mt0 {
        padding: 1rem;
    }
    
    .css-1kjyj6z.e82z5mt1 {
        flex-wrap: nowrap;
        gap: 0.5rem;
    }
    
    .css-huskxe.e82z5mt13 {
        gap: 0.5rem;
        flex-shrink: 1;
    }
    
    .css-1fd5u73.e82z5mt14 {
        font-size: 1.2rem;
        white-space: nowrap;
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

.internet-modal-body {
    padding: 1.5rem;
}

.internet-modal-step {
    display: none;
}

.internet-modal-step.active {
    display: block;
}

/* Step 2: 기존 인터넷 회선 선택 - 높이 증가 */
#step2 {
    min-height: 400px;
}

.internet-option-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* 회사 선택 그리드 영역 */
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

/* KT 로고 폭 조정 (dlive처럼 작게) */
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

/* 폼 스타일 */
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
    
    .internet-form-group {
        gap: 0.5rem;
    }
    
    .internet-form-input {
        padding: 0.875rem 1rem;
        font-size: 0.9375rem;
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
    
    .internet-company-scroll {
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }
    
    #step2 {
        min-height: 450px;
    }
    
    .internet-company-btn {
        padding: 0.75rem;
    }
    
    .internet-company-logo {
        width: 60px;
        height: 60px;
    }
    
    /* KT 로고 폭 조정 (모바일) */
    .internet-company-logo[src*="kt.svg"] {
        width: 45px;
        height: 60px;
    }
    
    .internet-company-icon-other {
        width: 60px;
        height: 60px;
    }
    
    .internet-company-icon-other svg {
        width: 45px;
        height: 45px;
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
] : null); ?>;

// 인터넷 카드 클릭 이벤트 핸들러
document.addEventListener('DOMContentLoaded', function() {
    const internetCards = document.querySelectorAll('.css-58gch7.e82z5mt0');
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
    
    if (internetCards.length === 0) {
        console.warn('인터넷 카드를 찾을 수 없습니다.');
        return;
    }
    
    internetCards.forEach(card => {
        card.style.cursor = 'pointer'; // 클릭 가능한 커서 표시
        
        card.addEventListener('click', function(e) {
            // 모달 내부 클릭이나 특정 요소 클릭은 무시
            if (e.target.closest('.internet-modal')) {
                return;
            }
            
            // 로그인 체크 - 로그인하지 않은 경우 로그인 모달 표시
            if (!isLoggedIn) {
                e.preventDefault();
                e.stopPropagation();
                
                // 카드에서 회사 정보 추출 (로그인 후 사용하기 위해 저장)
                const logoImg = card.querySelector('img[data-testid="internet-company-logo"]');
                if (logoImg) {
                    const logoSrc = logoImg.src;
                    const logoAlt = logoImg.alt || '';
                    
                    // URL에서 회사명 추출
                    const companyMap = {
                        'ktskylife': { name: 'KT SkyLife', icon: 'ktskylife' },
                        'hellovision': { name: 'HelloVision', icon: 'hellovision' },
                        'btv': { name: 'BTV', icon: 'btv' },
                        'dlive': { name: 'DLive', icon: 'dlive' },
                        'lgu': { name: 'LG U+', icon: 'lgu' },
                        'kt': { name: 'KT', icon: 'kt' },
                        'broadband': { name: 'Broadband', icon: 'broadband' }
                    };
                    
                    let companyInfo = null;
                    for (const [key, value] of Object.entries(companyMap)) {
                        if (logoSrc.includes(key)) {
                            companyInfo = value;
                            break;
                        }
                    }
                    
                    // alt 텍스트에서도 확인
                    if (!companyInfo && logoAlt) {
                        for (const [key, value] of Object.entries(companyMap)) {
                            if (logoAlt.toLowerCase().includes(key.toLowerCase())) {
                                companyInfo = value;
                                break;
                            }
                        }
                    }
                    
                    if (companyInfo) {
                        selectedData.newCompany = companyInfo.name;
                        selectedData.newCompanyIcon = companyInfo.icon;
                        selectedData.newCompanyLogo = logoSrc;
                    }
                }
                
                // 인터넷 모달을 열어야 한다는 플래그 설정
                window.shouldOpenInternetModal = true;
                
                // 현재 URL을 세션에 저장 (로그인 후 돌아올 주소)
                const currentUrl = window.location.href;
                fetch('/MVNO/api/save-redirect-url.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ redirect_url: currentUrl })
                }).catch(error => {
                    // 에러 무시하고 계속 진행
                });
                
                // 로그인 모달 열기 (여러 방법 시도)
                function tryOpenLoginModal() {
                    // 방법 1: 전역 함수 사용
                    if (typeof openLoginModal === 'function') {
                        openLoginModal(false);
                        return true;
                    }
                    
                    // 방법 2: 직접 모달 요소 찾기
                    const loginModal = document.getElementById('loginModal');
                    if (loginModal) {
                        loginModal.classList.add('active');
                        document.body.style.overflow = 'hidden';
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
            
            // 카드에서 product_id 추출
            const productId = card.getAttribute('data-product-id');
            if (productId) {
                selectedData.product_id = productId;
                
                // 조회수 증가 API 호출
                fetch('/MVNO/api/increment-product-view.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ product_id: productId })
                }).catch(error => {
                    // 에러는 무시 (조회수 증가 실패해도 계속 진행)
                    console.error('조회수 증가 실패:', error);
                });
            }
            
            // 카드에서 회사 정보 추출
            const logoImg = card.querySelector('img[data-testid="internet-company-logo"]');
            if (logoImg) {
                const logoSrc = logoImg.src;
                const logoAlt = logoImg.alt || '';
                
                // URL에서 회사명 추출
                const companyMap = {
                    'ktskylife': { name: 'KT SkyLife', icon: 'ktskylife' },
                    'hellovision': { name: 'HelloVision', icon: 'hellovision' },
                    'btv': { name: 'BTV', icon: 'btv' },
                    'dlive': { name: 'DLive', icon: 'dlive' },
                    'lgu': { name: 'LG U+', icon: 'lgu' },
                    'kt': { name: 'KT', icon: 'kt' },
                    'broadband': { name: 'Broadband', icon: 'broadband' }
                };
                
                let companyInfo = null;
                for (const [key, value] of Object.entries(companyMap)) {
                    if (logoSrc.includes(key)) {
                        companyInfo = value;
                        break;
                    }
                }
                
                // alt 텍스트에서도 확인
                if (!companyInfo && logoAlt) {
                    for (const [key, value] of Object.entries(companyMap)) {
                        if (logoAlt.toLowerCase().includes(key.toLowerCase())) {
                            companyInfo = value;
                            break;
                        }
                    }
                }
                
                if (companyInfo) {
                    selectedData.newCompany = companyInfo.name;
                    selectedData.newCompanyIcon = companyInfo.icon;
                    selectedData.newCompanyLogo = logoSrc;
                }
            }
            
            // 모달 열기
            openInternetModal();
        });
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
let currentStep = 1;
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
        // 첫 번째 단계 활성화
        showStep(1);
        // 신청 인터넷 회선 정보 표시
        showNewCompanyInfo();
        // 폼 검증 이벤트 리스너 설정
        if (window.setupFormValidation) {
            window.setupFormValidation();
        }
        
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
        }
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
                currentStep = 1;
                selectedData = {};
                resetSteps();
            }, 300);
        } else {
            modal.classList.remove('active');
            // body 스크롤 복원
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            // 상태 초기화
            currentStep = 1;
            selectedData = {};
            resetSteps();
        }
    }
}

function showStep(step) {
    // 모든 단계 숨기기
    const steps = document.querySelectorAll('.internet-modal-step');
    steps.forEach(s => s.classList.remove('active'));
    
    // 현재 단계 표시
    const currentStepEl = document.getElementById('step' + step);
    if (currentStepEl) {
        currentStepEl.classList.add('active');
        currentStep = step;
        
        // step 3로 이동할 때 로그인한 사용자 정보 자동 입력
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
        }
    }
}

function resetSteps() {
    const steps = document.querySelectorAll('.internet-modal-step');
    steps.forEach(s => s.classList.remove('active'));
    if (steps.length > 0) {
        steps[0].classList.add('active');
    }
    // 선택된 버튼 상태 초기화
    const optionButtons = document.querySelectorAll('.internet-option-btn');
    optionButtons.forEach(btn => btn.classList.remove('selected'));
    const companyButtons = document.querySelectorAll('.internet-company-btn');
    companyButtons.forEach(btn => btn.classList.remove('selected'));
    // 모달 제목 초기화
    updateModalTitle('인터넷 설치여부');
    // 현재 설치회사 정보 숨기기
    hideCurrentCompanyInfo();
    // 선택한 인터넷 정보 표시
    showNewCompanyInfo();
    // 체크박스 초기화
    const agreeAll = document.getElementById('agreeAll');
    const agreePrivacy = document.getElementById('agreePrivacy');
    const agreeThirdParty = document.getElementById('agreeThirdParty');
    const submitBtn = document.getElementById('submitBtn');
    if (agreeAll) agreeAll.checked = false;
    if (agreePrivacy) agreePrivacy.checked = false;
    if (agreeThirdParty) agreeThirdParty.checked = false;
    if (submitBtn) submitBtn.disabled = true;
    // 입력 필드 초기화
    const nameInput = document.getElementById('internetName');
    const phoneInput = document.getElementById('internetPhone');
    const emailInput = document.getElementById('internetEmail');
    if (nameInput) nameInput.value = '';
    if (phoneInput) phoneInput.value = '';
    if (emailInput) emailInput.value = '';
}

function selectInternetOption(option) {
    selectedData.installationStatus = option;
    
    // 모든 버튼에서 selected 클래스 제거
    const buttons = document.querySelectorAll('#step1 .internet-option-btn');
    buttons.forEach(btn => btn.classList.remove('selected'));
    
    // 클릭된 버튼에 selected 클래스 추가
    const clickedButton = event.target.closest('.internet-option-btn');
    if (clickedButton) {
        clickedButton.classList.add('selected');
    }
    
    // 옵션에 따라 다음 단계로 이동
    setTimeout(() => {
        if (option === 'none') {
            // 인터넷이 없어요 -> 바로 폼으로 이동
            showStep(3);
            updateModalTitle('인터넷 회선 신청');
            hideCurrentCompanyInfo();
        } else if (option === 'installed') {
            // 인터넷이 설치되어 있어요 -> 설치회사 선택 단계로 이동
            showStep(2);
            updateModalTitle('기존 인터넷 회선 선택');
        }
    }, 300);
}

function selectInternetCompany(company, icon) {
    selectedData.currentCompany = company;
    selectedData.currentCompanyIcon = icon;
    
    // 모든 버튼에서 selected 클래스 제거
    const buttons = document.querySelectorAll('#step2 .internet-company-btn');
    buttons.forEach(btn => btn.classList.remove('selected'));
    
    // 클릭된 버튼에 selected 클래스 추가
    const clickedButton = event.target.closest('.internet-company-btn');
    if (clickedButton) {
        clickedButton.classList.add('selected');
    }
    
    // 폼 단계로 이동
    setTimeout(() => {
        showStep(3);
        updateModalTitle('인터넷 신청');
        showCurrentCompanyInfo(company, icon);
    }, 300);
}

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
            'KT SkyLife': 'https://assets-legacy.moyoplan.com/internets/assets/ktskylife.svg',
            'HelloVision': 'https://assets-legacy.moyoplan.com/internets/assets/hellovision.svg',
            'BTV': 'https://assets-legacy.moyoplan.com/internets/assets/btv.svg',
            'DLive': 'https://assets-legacy.moyoplan.com/internets/assets/dlive.svg',
            'LG U+': 'https://assets-legacy.moyoplan.com/internets/assets/lgu.svg',
            'KT': 'https://assets-legacy.moyoplan.com/internets/assets/kt.svg',
            'Broadband': 'https://assets-legacy.moyoplan.com/internets/assets/broadband.svg',
            '기타': null
        };
        
        const logoPath = companyLogos[company] || (icon ? `https://assets-legacy.moyoplan.com/internets/assets/${icon}.svg` : null);
        
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
            (selectedData.newCompanyIcon ? `https://assets-legacy.moyoplan.com/internets/assets/${selectedData.newCompanyIcon}.svg` : null);
        
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

    if (agreePurpose && agreeItems && agreePeriod && agreeThirdParty) {
        agreePurpose.checked = checked;
        agreeItems.checked = checked;
        agreePeriod.checked = checked;
        agreeThirdParty.checked = checked;
        checkAllAgreements();
    }
}

// 인터넷 개인정보 내용 정의 (설정 파일에서 로드)
<?php
require_once __DIR__ . '/../includes/data/privacy-functions.php';
$privacySettings = getPrivacySettings();
echo "const internetPrivacyContents = " . json_encode($privacySettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
?>

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
    const agreePurpose = document.getElementById('agreePurpose');
    const agreeItems = document.getElementById('agreeItems');
    const agreePeriod = document.getElementById('agreePeriod');
    const agreeThirdParty = document.getElementById('agreeThirdParty');
    const submitBtn = document.getElementById('submitBtn');
    const nameInput = document.getElementById('internetName');
    const phoneInput = document.getElementById('internetPhone');

    if (agreeAll && agreePurpose && agreeItems && agreePeriod && agreeThirdParty && submitBtn) {
        // 전체 동의 체크박스 상태 업데이트
        agreeAll.checked = agreePurpose.checked && agreeItems.checked && agreePeriod.checked && agreeThirdParty.checked;
        
        // 이름과 휴대폰 번호 확인
        const name = nameInput ? nameInput.value.trim() : '';
        const phone = phoneInput ? phoneInput.value.replace(/[^\d]/g, '') : '';
        
        // 제출 버튼 활성화/비활성화 (모든 필드가 입력되어야 활성화)
        const isNameValid = name.length > 0;
        const isPhoneValid = phone.length === 11 && phone.startsWith('010');
        const isAgreementsChecked = agreePurpose.checked && agreeItems.checked && agreePeriod.checked && agreeThirdParty.checked;
        
        if (isNameValid && isPhoneValid && isAgreementsChecked) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    }
}

function submitInternetForm() {
    // 로그인 체크
    const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
    if (!isLoggedIn) {
        // 인터넷 모달 닫기
        closeInternetModal();
        
        // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
        const currentUrl = window.location.href;
        fetch('/MVNO/api/save-redirect-url.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ redirect_url: currentUrl })
        }).then(() => {
            // 회원가입 모달 열기
            if (typeof openLoginModal === 'function') {
                openLoginModal(true);
            } else {
                setTimeout(() => {
                    if (typeof openLoginModal === 'function') {
                        openLoginModal(true);
                    }
                }, 100);
            }
        });
        return;
    }
    
    const name = document.getElementById('internetName').value;
    const phone = document.getElementById('internetPhone').value;
    const email = document.getElementById('internetEmail').value;
    
    // product_id 확인
    if (!selectedData.product_id) {
        alert('상품 정보를 찾을 수 없습니다. 다시 시도해주세요.');
        return;
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
    
    // 실제 제출 로직
    fetch('/MVNO/api/submit-internet-application.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 인터넷 모달 닫기
            closeInternetModal();
            
            // 토스트 메시지 표시
            showInternetToast();
        } else {
            alert(data.message || '신청 중 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('신청 중 오류가 발생했습니다.');
    });
}

function showInternetToast() {
    const toastModal = document.getElementById('internetToastModal');
    if (toastModal) {
        toastModal.classList.add('active');
    }
}

function closeInternetToast() {
    const toastModal = document.getElementById('internetToastModal');
    if (toastModal) {
        toastModal.classList.remove('active');
    }
}

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
        
        if (nameInput) {
            nameInput.addEventListener('input', checkAllAgreements);
            nameInput.addEventListener('blur', checkAllAgreements);
        }
        
        if (phoneInput) {
            phoneInput.addEventListener('input', checkAllAgreements);
            phoneInput.addEventListener('blur', checkAllAgreements);
        }
    }
    
    // 전역으로 노출
    window.setupFormValidation = setupFormValidation;
})();
</script>


<?php
// 푸터 포함
include '../includes/footer.php';
?>
