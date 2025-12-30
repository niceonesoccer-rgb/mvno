<?php
/**
 * 인터넷 상품 아이템 템플릿
 * API에서 HTML을 생성할 때 사용
 * 
 * 사용 전에 다음 변수들이 설정되어 있어야 함:
 * - $product: 상품 데이터 배열
 * - $iconPath: 아이콘 경로
 * - $speedOption: 속도 옵션
 * - $applicationCount: 신청 개수
 * - $monthlyFee: 월 요금
 * - $cashNames, $cashPrices: 현금 지급 정보
 * - $giftNames, $giftPrices: 상품권 정보
 * - $equipNames, $equipPrices: 장비 정보
 * - $installNames, $installPrices: 설치 정보
 * - $serviceTypeDisplay: 서비스 타입 표시명
 * - $promotionTitle: 프로모션 제목
 * - $promotions: 프로모션 배열
 */

// 변수 초기화 (없는 경우 기본값)
$iconPath = $iconPath ?? '';
$speedOption = $speedOption ?? '';
$applicationCount = $applicationCount ?? '0';
$monthlyFee = $monthlyFee ?? '0원';
$cashNames = $cashNames ?? [];
$cashPrices = $cashPrices ?? [];
$giftNames = $giftNames ?? [];
$giftPrices = $giftPrices ?? [];
$equipNames = $equipNames ?? [];
$equipPrices = $equipPrices ?? [];
$installNames = $installNames ?? [];
$installPrices = $installPrices ?? [];
$serviceTypeDisplay = $serviceTypeDisplay ?? '인터넷';
$promotionTitle = $promotionTitle ?? '';
$promotions = $promotions ?? [];
$promotionCount = count(array_filter($promotions, function($p) { return !empty(trim($p)); }));
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
                            <linearGradient id="userGradient<?php echo $product['id']; ?>" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <circle cx="12" cy="8" r="4" fill="url(#userGradient<?php echo $product['id']; ?>)"/>
                        <path d="M6 21c0-3.314 2.686-6 6-6s6 2.686 6 6" stroke="url(#userGradient<?php echo $product['id']; ?>)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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
        if ($promotionCount > 0 || !empty($promotionTitle)):
            $accordionTitle = '';
            if (!empty($promotionTitle)) {
                $accordionTitle = $promotionTitle;
            } elseif ($promotionCount > 0) {
                $accordionTitle = '프로모션 최대 ' . $promotionCount . '개';
            }
            
            $giftColors = ['#EF4444', '#EAB308', '#10B981', '#3B82F6', '#8B5CF6'];
            $giftTextColor = '#FFFFFF';
        ?>
        <div class="plan-accordion-box" style="margin-top: 12px; padding: 12px 0;" onclick="event.stopPropagation();">
            <div class="plan-accordion">
                <button type="button" class="plan-accordion-trigger" aria-expanded="false" style="padding: 12px 16px;" onclick="event.stopPropagation();">
                    <div class="plan-gifts-accordion-content">
                        <?php if ($promotionCount > 0): ?>
                        <div class="plan-gifts-indicator-dots">
                            <?php 
                            $filteredPromotions = array_filter($promotions, function($p) { return !empty(trim($p)); });
                            $index = 0;
                            foreach ($filteredPromotions as $promotion): 
                                $firstChar = mb_substr(trim($promotion), 0, 1, 'UTF-8');
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





