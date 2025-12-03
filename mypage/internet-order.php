<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = false;

// 인터넷 요금제 데이터 배열 (주문 내역용)
$internets = [
    ['id' => 1, 'provider' => 'KT SkyLife', 'plan_name' => '인터넷 500MB + TV', 'speed' => '500MB', 'tv_combined' => true, 'price' => '월 39,000원', 'installation_fee' => '무료', 'order_date' => '2024.11.15', 'installation_date' => '2024.11.18', 'has_review' => false, 'review_count' => 19],
    ['id' => 2, 'provider' => 'HelloVision', 'plan_name' => '인터넷 1G + TV', 'speed' => '1G', 'tv_combined' => true, 'price' => '월 45,000원', 'installation_fee' => '무료', 'order_date' => '2024.11.12', 'installation_date' => '2024.11.15', 'has_review' => true, 'review_count' => 21],
    ['id' => 3, 'provider' => 'BTV', 'plan_name' => '인터넷 100MB', 'speed' => '100MB', 'tv_combined' => false, 'price' => '월 25,000원', 'installation_fee' => '무료', 'order_date' => '2024.11.10', 'installation_date' => '2024.11.13', 'has_review' => false, 'review_count' => 18],
    ['id' => 4, 'provider' => 'DLive', 'plan_name' => '인터넷 500MB', 'speed' => '500MB', 'tv_combined' => false, 'price' => '월 32,000원', 'installation_fee' => '무료', 'order_date' => '2024.11.08', 'installation_date' => '', 'has_review' => false, 'review_count' => 0],
    ['id' => 5, 'provider' => 'LG U+', 'plan_name' => '인터넷 1G + TV', 'speed' => '1G', 'tv_combined' => true, 'price' => '월 48,000원', 'installation_fee' => '무료', 'order_date' => '2024.11.05', 'installation_date' => '2024.11.08', 'has_review' => false, 'review_count' => 15],
    ['id' => 6, 'provider' => 'KT', 'plan_name' => '인터넷 100MB + TV', 'speed' => '100MB', 'tv_combined' => true, 'price' => '월 35,000원', 'installation_fee' => '무료', 'order_date' => '2024.11.03', 'installation_date' => '', 'has_review' => false, 'review_count' => 0],
    ['id' => 7, 'provider' => 'Broadband', 'plan_name' => '인터넷 500MB + TV', 'speed' => '500MB', 'tv_combined' => true, 'price' => '월 38,000원', 'installation_fee' => '무료', 'order_date' => '2024.11.01', 'installation_date' => '', 'has_review' => false, 'review_count' => 0],
    ['id' => 8, 'provider' => 'KT SkyLife', 'plan_name' => '인터넷 1G', 'speed' => '1G', 'tv_combined' => false, 'price' => '월 42,000원', 'installation_fee' => '무료', 'order_date' => '2024.10.28', 'installation_date' => '2024.10.31', 'has_review' => false, 'review_count' => 12],
    ['id' => 9, 'provider' => 'HelloVision', 'plan_name' => '인터넷 100MB + TV', 'speed' => '100MB', 'tv_combined' => true, 'price' => '월 30,000원', 'installation_fee' => '무료', 'order_date' => '2024.10.25', 'installation_date' => '2024.10.28', 'has_review' => false, 'review_count' => 8],
    ['id' => 10, 'provider' => 'BTV', 'plan_name' => '인터넷 500MB + TV', 'speed' => '500MB', 'tv_combined' => true, 'price' => '월 40,000원', 'installation_fee' => '무료', 'order_date' => '2024.10.22', 'installation_date' => '2024.10.25', 'has_review' => false, 'review_count' => 25],
    ['id' => 11, 'provider' => 'DLive', 'plan_name' => '인터넷 1G + TV', 'speed' => '1G', 'tv_combined' => true, 'price' => '월 46,000원', 'installation_fee' => '무료', 'order_date' => '2024.10.20', 'installation_date' => '', 'has_review' => false, 'review_count' => 0],
    ['id' => 12, 'provider' => 'LG U+', 'plan_name' => '인터넷 100MB', 'speed' => '100MB', 'tv_combined' => false, 'price' => '월 24,000원', 'installation_fee' => '무료', 'order_date' => '2024.10.18', 'installation_date' => '2024.10.21', 'has_review' => false, 'review_count' => 7],
    ['id' => 13, 'provider' => 'KT', 'plan_name' => '인터넷 500MB', 'speed' => '500MB', 'tv_combined' => false, 'price' => '월 33,000원', 'installation_fee' => '무료', 'order_date' => '2024.10.15', 'installation_date' => '2024.10.18', 'has_review' => false, 'review_count' => 14],
    ['id' => 14, 'provider' => 'Broadband', 'plan_name' => '인터넷 1G', 'speed' => '1G', 'tv_combined' => false, 'price' => '월 44,000원', 'installation_fee' => '무료', 'order_date' => '2024.10.12', 'installation_date' => '2024.10.15', 'has_review' => false, 'review_count' => 9],
    ['id' => 15, 'provider' => 'KT SkyLife', 'plan_name' => '인터넷 100MB + TV', 'speed' => '100MB', 'tv_combined' => true, 'price' => '월 32,000원', 'installation_fee' => '무료', 'order_date' => '2024.10.10', 'installation_date' => '2024.10.13', 'has_review' => false, 'review_count' => 11],
];

// 헤더 포함
include '../includes/header.php';

// 회사 로고 매핑
$companyLogos = [
    'KT SkyLife' => 'https://assets-legacy.moyoplan.com/internets/assets/ktskylife.svg',
    'HelloVision' => 'https://assets-legacy.moyoplan.com/internets/assets/hellovision.svg',
    'BTV' => 'https://assets-legacy.moyoplan.com/internets/assets/btv.svg',
    'DLive' => 'https://assets-legacy.moyoplan.com/internets/assets/dlive.svg',
    'LG U+' => 'https://assets-legacy.moyoplan.com/internets/assets/lgu.svg',
    'KT' => 'https://assets-legacy.moyoplan.com/internets/assets/kt.svg',
    'Broadband' => 'https://assets-legacy.moyoplan.com/internets/assets/broadband.svg',
];
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
                        <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 인터넷</h2>
                    </div>
                </div>

                <!-- 신청한 인터넷 목록 -->
                <div style="margin-bottom: 32px;" id="internetsContainer">
                    <div class="PlanDetail_content_wrapper__0YNeJ">
                        <div class="tw-m-auto tw-w-full tw-max-w-[780px] min-w-640-legacy:tw-max-w-[480px]">
                            <div class="css-2l6pil e1ebrc9o0">
                                <?php foreach ($internets as $index => $internet): ?>
                                    <div class="internet-item" data-index="<?php echo $index; ?>" style="<?php echo $index >= 10 ? 'display: none;' : ''; ?> margin-bottom: 1rem;">
                                        <div class="css-58gch7 e82z5mt0">
                                            <div class="css-1kjyj6z e82z5mt1">
                                                <?php if (isset($companyLogos[$internet['provider']])): ?>
                                                    <img data-testid="internet-company-logo" src="<?php echo htmlspecialchars($companyLogos[$internet['provider']]); ?>" alt="<?php echo htmlspecialchars($internet['provider']); ?>" class="css-1pg8bi e82z5mt15">
                                                <?php else: ?>
                                                    <div style="width: 80px; height: 80px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <path d="M2 17L12 22L22 17" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <path d="M2 12L12 17L22 12" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="css-huskxe e82z5mt13">
                                                    <div class="css-1fd5u73 e82z5mt14">
                                                        <span style="box-sizing:border-box;display:inline-block;overflow:hidden;width:initial;height:initial;background:none;opacity:1;border:0;margin:0;padding:0;position:relative;max-width:100%">
                                                            <span style="box-sizing:border-box;display:block;width:initial;height:initial;background:none;opacity:1;border:0;margin:0;padding:0;max-width:100%">
                                                                <img style="display:block;max-width:100%;width:initial;height:initial;background:none;opacity:1;border:0;margin:0;padding:0" alt="" aria-hidden="true" src="data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20version=%271.1%27%20width=%2720%27%20height=%2720%27/%3e">
                                                            </span>
                                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="position:absolute;top:0;left:0;bottom:0;right:0;box-sizing:border-box;padding:0;border:none;margin:auto;display:block;width:100%;height:100%">
                                                                <rect x="2" y="3" width="20" height="14" rx="2" fill="#E9D5FF" stroke="#A855F7" stroke-width="1.5"/>
                                                                <rect x="4" y="5" width="16" height="10" rx="1" fill="white"/>
                                                                <rect x="2" y="17" width="20" height="4" rx="1" fill="#C084FC" stroke="#A855F7" stroke-width="1"/>
                                                                <g transform="translate(17, -2) scale(1.5)">
                                                                    <path d="M0 0L-2 5H0L-1 10L2 5H0L0 0Z" fill="#6366F1" stroke="#4F46E5" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
                                                                </g>
                                                            </svg>
                                                        </span><?php echo htmlspecialchars($internet['speed']); ?>
                                                    </div>
                                                    <div class="css-1fd5u73 e82z5mt14">
                                                        <span style="box-sizing:border-box;display:inline-block;overflow:hidden;width:initial;height:initial;background:none;opacity:1;border:0;margin:0;padding:0;position:relative;max-width:100%">
                                                            <span style="box-sizing:border-box;display:block;width:initial;height:initial;background:none;opacity:1;border:0;margin:0;padding:0;max-width:100%">
                                                                <img style="display:block;max-width:100%;width:initial;height:initial;background:none;opacity:1;border:0;margin:0;padding:0" alt="" aria-hidden="true" src="data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20version=%271.1%27%20width=%2720%27%20height=%2720%27/%3e">
                                                            </span>
                                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="position:absolute;top:0;left:0;bottom:0;right:0;box-sizing:border-box;padding:0;border:none;margin:auto;display:block;width:100%;height:100%">
                                                                <defs>
                                                                    <linearGradient id="checkGradient<?php echo $internet['id']; ?>" x1="0%" y1="0%" x2="100%" y2="100%">
                                                                        <stop offset="0%" style="stop-color:#10B981;stop-opacity:1" />
                                                                        <stop offset="100%" style="stop-color:#059669;stop-opacity:1" />
                                                                    </linearGradient>
                                                                </defs>
                                                                <circle cx="12" cy="12" r="10" fill="url(#checkGradient<?php echo $internet['id']; ?>)" stroke="#047857" stroke-width="1"/>
                                                                <path d="M8 12L10.5 14.5L16 9" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                            </svg>
                                                        </span>
                                                        <?php if (!empty($internet['installation_date'])): ?>
                                                            <?php 
                                                            $reviewCount = isset($internet['review_count']) ? $internet['review_count'] : 0;
                                                            if ($reviewCount > 0): 
                                                            ?>
                                                                <?php echo $reviewCount; ?>개 리뷰
                                                            <?php else: ?>
                                                                설치 완료
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            설치 대기 중
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="css-174t92n e82z5mt7">
                                                <?php if ($internet['tv_combined']): ?>
                                                    <div class="css-12zfa6z e82z5mt8">
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="css-xj5cz0 e82z5mt9">
                                                            <path d="M20 7H4C2.89543 7 2 7.89543 2 9V19C2 20.1046 2.89543 21 4 21H20C21.1046 21 22 20.1046 22 19V9C22 7.89543 21.1046 7 20 7Z" stroke="#6366F1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                            <path d="M12 7V21M12 7L8 3M12 7L16 3" stroke="#6366F1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        <div class="css-0 e82z5mt10">
                                                            <p class="css-2ht76o e82z5mt12">인터넷,TV 설치비 무료</p>
                                                            <p class="css-1j35abw e82z5mt11">무료(36,300원 상당)</p>
                                                        </div>
                                                    </div>
                                                    <div class="css-12zfa6z e82z5mt8">
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="css-xj5cz0 e82z5mt9">
                                                            <path d="M20 7H4C2.89543 7 2 7.89543 2 9V19C2 20.1046 2.89543 21 4 21H20C21.1046 21 22 20.1046 22 19V9C22 7.89543 21.1046 7 20 7Z" stroke="#6366F1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                            <path d="M12 7V21M12 7L8 3M12 7L16 3" stroke="#6366F1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        <div class="css-0 e82z5mt10">
                                                            <p class="css-2ht76o e82z5mt12">셋톱박스 임대료 무료</p>
                                                            <p class="css-1j35abw e82z5mt11">무료(월 3,300원 상당)</p>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="css-12zfa6z e82z5mt8">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="css-xj5cz0 e82z5mt9">
                                                        <path d="M20 7H4C2.89543 7 2 7.89543 2 9V19C2 20.1046 2.89543 21 4 21H20C21.1046 21 22 20.1046 22 19V9C22 7.89543 21.1046 7 20 7Z" stroke="#6366F1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                        <path d="M12 7V21M12 7L8 3M12 7L16 3" stroke="#6366F1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    <div class="css-0 e82z5mt10">
                                                        <p class="css-2ht76o e82z5mt12">와이파이 공유기</p>
                                                        <p class="css-1j35abw e82z5mt11">무료(월 1,100원 상당)</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div data-testid="full-price-information" class="css-rkh09p e82z5mt2">
                                                <p class="css-16qot29 e82z5mt6"><?php echo htmlspecialchars($internet['price']); ?></p>
                                            </div>
                                            
                                            <!-- 주문 정보 및 액션 버튼 -->
                                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                                                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 1rem;">
                                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                                        <span style="font-size: 13px; color: #9ca3af;">신청일</span>
                                                        <span style="font-size: 13px; color: #374151; font-weight: 500;"><?php echo htmlspecialchars($internet['order_date']); ?></span>
                                                    </div>
                                                    <?php if (!empty($internet['installation_date'])): ?>
                                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                                            <span style="font-size: 13px; color: #9ca3af;">설치일</span>
                                                            <span style="font-size: 13px; color: #374151; font-weight: 500;"><?php echo htmlspecialchars($internet['installation_date']); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div style="display: flex; gap: 8px;">
                                                    <?php if (empty($internet['installation_date'])): ?>
                                                        <!-- 설치 전: 설치 문의 버튼 -->
                                                        <?php if (!empty($internet['consultation_url'])): ?>
                                                            <a href="<?php echo htmlspecialchars($internet['consultation_url']); ?>" style="flex: 1; padding: 10px 16px; background: #ec4899; border-radius: 8px; text-align: center; text-decoration: none; color: white; font-size: 14px; font-weight: 500;">
                                                                설치 문의
                                                            </a>
                                                        <?php else: ?>
                                                            <button disabled style="flex: 1; padding: 10px 16px; background: #ec4899; border-radius: 8px; border: none; color: white; font-size: 14px; font-weight: 500; cursor: not-allowed; opacity: 0.6;">
                                                                설치 문의
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <!-- 설치 완료: 리뷰 관련 버튼 -->
                                                        <?php if ($internet['has_review']): ?>
                                                            <button class="internet-order-review-edit-btn" data-internet-id="<?php echo $internet['id']; ?>" style="flex: 1; padding: 10px 16px; background: #6366f1; border-radius: 8px; border: none; color: white; font-size: 14px; font-weight: 500; cursor: pointer;">
                                                                수정
                                                            </button>
                                                            <button class="internet-order-review-delete-btn" data-internet-id="<?php echo $internet['id']; ?>" style="flex: 1; padding: 10px 16px; background: #ef4444; border-radius: 8px; border: none; color: white; font-size: 14px; font-weight: 500; cursor: pointer;">
                                                                삭제
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="internet-order-review-btn" data-internet-id="<?php echo $internet['id']; ?>" style="flex: 1; padding: 10px 16px; background: #6366f1; border-radius: 8px; border: none; color: white; font-size: 14px; font-weight: 500; cursor: pointer;">
                                                                리뷰 쓰기
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 더보기 버튼 -->
                <div style="margin-top: 32px; margin-bottom: 32px;" id="moreButtonContainer">
                    <button class="plan-review-more-btn" id="moreInternetsBtn">
                        더보기 (<?php 
                        $remaining = count($internets) - 10;
                        echo $remaining > 10 ? 10 : $remaining;
                        ?>개)
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
/* Internet order page styles - matching internets.php */
.PlanDetail_content_wrapper__0YNeJ {
    width: 100%;
}

.tw-m-auto {
    margin: 0 auto;
}

.tw-w-full {
    width: 100%;
}

.tw-max-w-\[780px\] {
    max-width: 780px;
}

.min-w-640-legacy\:tw-max-w-\[480px\] {
    max-width: 480px;
}

@media (min-width: 640px) {
    .min-w-640-legacy\:tw-max-w-\[480px\] {
        max-width: 480px;
    }
}

.css-2l6pil.e1ebrc9o0 {
    display: flex;
    flex-direction: column;
    gap: 1rem;
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
    gap: 1rem;
    margin-bottom: 1rem;
}

.css-1pg8bi.e82z5mt15 {
    width: 80px;
    height: auto;
    object-fit: contain;
}

.css-huskxe.e82z5mt13 {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.css-1fd5u73.e82z5mt14 {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
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
    align-items: flex-start;
    gap: 0.75rem;
}

.css-xj5cz0.e82z5mt9 {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.css-0.e82z5mt10 {
    flex: 1;
}

.css-2ht76o.e82z5mt12 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 0.25rem 0;
}

.css-1j35abw.e82z5mt11 {
    font-size: 0.8125rem;
    color: #6b7280;
    margin: 0;
}

/* Price section */
.css-rkh09p.e82z5mt2 {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.css-16qot29.e82z5mt6 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0;
}

.internet-order-review-btn:hover:not(:disabled) {
    background: #4f46e5 !important;
}

@media (max-width: 640px) {
    .css-58gch7.e82z5mt0 {
        padding: 1rem;
    }
}
</style>

<script src="../assets/js/plan-accordion.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const moreBtn = document.getElementById('moreInternetsBtn');
    const internetItems = document.querySelectorAll('.internet-item');
    let visibleCount = 10;
    const totalInternets = internetItems.length;
    const loadCount = 10; // 한 번에 보여줄 개수

    function updateButtonText() {
        const remaining = totalInternets - visibleCount;
        if (remaining > 0) {
            const showCount = remaining > loadCount ? loadCount : remaining;
            moreBtn.textContent = `더보기 (${showCount}개)`;
        }
    }

    if (moreBtn) {
        updateButtonText();
        
        moreBtn.addEventListener('click', function() {
            // 다음 10개씩 표시
            const endCount = Math.min(visibleCount + loadCount, totalInternets);
            for (let i = visibleCount; i < endCount; i++) {
                if (internetItems[i]) {
                    internetItems[i].style.display = 'block';
                }
            }
            
            visibleCount = endCount;
            
            // 모든 항목이 보이면 더보기 버튼 숨기기
            if (visibleCount >= totalInternets) {
                const moreButtonContainer = document.getElementById('moreButtonContainer');
                if (moreButtonContainer) {
                    moreButtonContainer.style.display = 'none';
                }
            } else {
                updateButtonText();
            }
        });
    }

    // 모든 인터넷이 보이면 더보기 버튼 숨기기
    if (visibleCount >= totalInternets) {
        const moreButtonContainer = document.getElementById('moreButtonContainer');
        if (moreButtonContainer) {
            moreButtonContainer.style.display = 'none';
        }
    }

    // 스크롤 위치 저장 변수
    let reviewModalScrollPosition = 0;

    // 스크롤바 너비 계산 함수
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

    // 리뷰 작성 모달 열기
    function openReviewModal(internetId) {
        const modal = document.getElementById('internetReviewModal');
        if (modal) {
            // 현재 스크롤 위치 저장
            reviewModalScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            
            // 스크롤바 너비 계산
            const scrollbarWidth = getScrollbarWidth();
            
            // body 스크롤 방지 (스크롤바 너비만큼 padding-right 추가하여 레이아웃 이동 방지)
            document.body.style.overflow = 'hidden';
            document.body.style.position = 'fixed';
            document.body.style.top = `-${reviewModalScrollPosition}px`;
            document.body.style.width = '100%';
            document.body.style.paddingRight = `${scrollbarWidth}px`;
            
            // html 요소도 스크롤 방지 (일부 브라우저용)
            document.documentElement.style.overflow = 'hidden';
            
            modal.style.display = 'flex';
            // 모달에 internetId 저장
            modal.setAttribute('data-internet-id', internetId);
            // 텍스트 영역 포커스
            setTimeout(() => {
                const textarea = document.getElementById('internetReviewText');
                if (textarea) {
                    textarea.focus();
                }
            }, 100);
        }
    }

    // 리뷰 작성 모달 닫기
    function closeReviewModal() {
        const modal = document.getElementById('internetReviewModal');
        if (modal) {
            modal.style.display = 'none';
            
            // body 스크롤 복원
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.body.style.paddingRight = '';
            document.documentElement.style.overflow = '';
            
            // 저장된 스크롤 위치로 복원
            window.scrollTo(0, reviewModalScrollPosition);
            
            // 폼 초기화
            const form = document.getElementById('internetReviewForm');
            if (form) {
                form.reset();
            }
        }
    }


    // 리뷰쓰기 버튼 클릭 이벤트
    const reviewButtons = document.querySelectorAll('.internet-order-review-btn:not(:disabled)');
    
    reviewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const internetId = this.getAttribute('data-internet-id');
            if (internetId && !this.disabled) {
                openReviewModal(internetId);
            }
        });
    });

    // 수정 버튼 클릭 이벤트
    const editButtons = document.querySelectorAll('.internet-order-review-edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const internetId = this.getAttribute('data-internet-id');
            if (internetId) {
                openReviewModal(internetId);
                // TODO: 기존 리뷰 데이터를 모달에 로드
            }
        });
    });

    // 삭제 모달 열기 함수
    function openDeleteModal(internetId, editBtn, deleteBtn, consultationLink, parentDiv) {
        const deleteModal = document.getElementById('internetReviewDeleteModal');
        if (deleteModal) {
            // 스크롤바 너비 계산
            const scrollbarWidth = getScrollbarWidth();
            
            // body 스크롤 방지
            document.body.style.overflow = 'hidden';
            document.body.style.position = 'fixed';
            document.body.style.top = `-${window.pageYOffset || document.documentElement.scrollTop}px`;
            document.body.style.width = '100%';
            document.body.style.paddingRight = `${scrollbarWidth}px`;
            document.documentElement.style.overflow = 'hidden';
            
            deleteModal.style.display = 'flex';
            deleteModal.setAttribute('data-internet-id', internetId);
        }
    }

    // 삭제 모달 닫기 함수
    function closeDeleteModal() {
        const deleteModal = document.getElementById('internetReviewDeleteModal');
        if (deleteModal) {
            deleteModal.style.display = 'none';
            
            // body 스크롤 복원
            const scrollTop = parseInt(document.body.style.top || '0') * -1;
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.body.style.paddingRight = '';
            document.documentElement.style.overflow = '';
            window.scrollTo(0, scrollTop);
        }
    }

    // 삭제 확인 함수
    function confirmDeleteReview(internetId) {
        // TODO: 서버로 삭제 요청
        console.log('리뷰 삭제 - Internet ID:', internetId);
        showReviewToast('리뷰가 삭제되었습니다.');
        closeDeleteModal();
        
        // 버튼을 리뷰 쓰기 버튼으로 복원
        const deleteBtn = document.querySelector(`.internet-order-review-delete-btn[data-internet-id="${internetId}"]`);
        const editBtn = document.querySelector(`.internet-order-review-edit-btn[data-internet-id="${internetId}"]`);
        
        if (deleteBtn && editBtn) {
            const parentDiv = deleteBtn.parentElement;
            const consultationLink = parentDiv.querySelector('a[href*="consultation"]');
            
            editBtn.remove();
            deleteBtn.remove();
            
            const newReviewBtn = document.createElement('button');
            newReviewBtn.className = 'internet-order-review-btn';
            newReviewBtn.setAttribute('data-internet-id', internetId);
            newReviewBtn.textContent = '리뷰 쓰기';
            newReviewBtn.style.cssText = 'flex: 1; padding: 10px 16px; background: #6366f1; border-radius: 8px; border: none; color: white; font-size: 14px; font-weight: 500; cursor: pointer;';
            
            if (consultationLink) {
                parentDiv.insertBefore(newReviewBtn, consultationLink.nextSibling);
            } else {
                parentDiv.appendChild(newReviewBtn);
            }
            
            newReviewBtn.addEventListener('click', function() {
                openReviewModal(internetId);
            });
        }
    }

    // 삭제 버튼 클릭 이벤트
    const deleteButtons = document.querySelectorAll('.internet-order-review-delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const internetId = this.getAttribute('data-internet-id');
            if (internetId) {
                const parentDiv = this.parentElement;
                const editBtn = parentDiv.querySelector('.internet-order-review-edit-btn');
                const consultationLink = parentDiv.querySelector('a[href*="consultation"]');
                openDeleteModal(internetId, editBtn, this, consultationLink, parentDiv);
            }
        });
    });

    // 삭제 모달 이벤트
    const deleteModal = document.getElementById('internetReviewDeleteModal');
    if (deleteModal) {
        const closeBtn = deleteModal.querySelector('.internet-review-delete-modal-close');
        const cancelBtn = deleteModal.querySelector('.internet-review-delete-btn-cancel');
        const confirmBtn = deleteModal.querySelector('.internet-review-delete-btn-confirm');
        const overlay = deleteModal.querySelector('.internet-review-delete-modal-overlay');

        if (closeBtn) {
            closeBtn.addEventListener('click', closeDeleteModal);
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeDeleteModal);
        }
        if (overlay) {
            overlay.addEventListener('click', closeDeleteModal);
        }
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                const internetId = deleteModal.getAttribute('data-internet-id');
                if (internetId) {
                    confirmDeleteReview(internetId);
                }
            });
        }

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && deleteModal.style.display === 'flex') {
                closeDeleteModal();
            }
        });
    }

    // 모달 닫기 이벤트
    const reviewModal = document.getElementById('internetReviewModal');
    if (reviewModal) {
        const closeBtn = reviewModal.querySelector('.internet-review-modal-close');
        const cancelBtn = reviewModal.querySelector('.internet-review-btn-cancel');
        const overlay = reviewModal.querySelector('.internet-review-modal-overlay');

        if (closeBtn) {
            closeBtn.addEventListener('click', closeReviewModal);
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeReviewModal);
        }
        if (overlay) {
            overlay.addEventListener('click', closeReviewModal);
        }

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && reviewModal.style.display === 'flex') {
                closeReviewModal();
            }
        });
    }

    // 리뷰 작성 폼 제출
    const reviewForm = document.getElementById('internetReviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const modal = document.getElementById('internetReviewModal');
            const internetId = modal ? modal.getAttribute('data-internet-id') : null;
            const reviewText = document.getElementById('internetReviewText').value.trim();
            const kindnessRatingInput = document.querySelector('#internetReviewForm input[name="kindness_rating"]:checked');
            const speedRatingInput = document.querySelector('#internetReviewForm input[name="speed_rating"]:checked');
            const kindnessRating = kindnessRatingInput ? parseInt(kindnessRatingInput.value) : null;
            const speedRating = speedRatingInput ? parseInt(speedRatingInput.value) : null;

            if (!kindnessRating) {
                showReviewToast('친절해요 별점을 선택해주세요.');
                return;
            }

            if (!speedRating) {
                showReviewToast('설치 빨라요 별점을 선택해주세요.');
                return;
            }

            if (!reviewText) {
                showReviewToast('리뷰 내용을 입력해주세요.');
                return;
            }

            if (!internetId) {
                showReviewToast('오류가 발생했습니다. 다시 시도해주세요.');
                return;
            }

            // TODO: 서버로 리뷰 데이터 전송
            console.log('리뷰 작성 - Internet ID:', internetId, 'Kindness Rating:', kindnessRating, 'Speed Rating:', speedRating, 'Review:', reviewText);
            
            // 임시: 성공 메시지 표시 (토스트 메시지)
            showReviewToast('리뷰가 작성되었습니다.');
            closeReviewModal();
            
            // 리뷰 작성 완료 후 버튼을 수정/삭제 버튼으로 변경
            const reviewBtn = document.querySelector(`.internet-order-review-btn[data-internet-id="${internetId}"]`);
            if (reviewBtn) {
                const parentDiv = reviewBtn.parentElement;
                const consultationLink = parentDiv.querySelector('a[href*="consultation"]');
                
                // 기존 리뷰 쓰기 버튼 제거
                reviewBtn.remove();
                
                // 수정 버튼 생성
                const editBtn = document.createElement('button');
                editBtn.className = 'internet-order-review-edit-btn';
                editBtn.setAttribute('data-internet-id', internetId);
                editBtn.textContent = '수정';
                editBtn.style.cssText = 'flex: 1; padding: 10px 16px; background: #6366f1; border-radius: 8px; border: none; color: white; font-size: 14px; font-weight: 500; cursor: pointer;';
                
                // 삭제 버튼 생성
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'internet-order-review-delete-btn';
                deleteBtn.setAttribute('data-internet-id', internetId);
                deleteBtn.textContent = '삭제';
                deleteBtn.style.cssText = 'flex: 1; padding: 10px 16px; background: #ef4444; border-radius: 8px; border: none; color: white; font-size: 14px; font-weight: 500; cursor: pointer;';
                
                // 버튼 추가
                if (consultationLink) {
                    parentDiv.insertBefore(editBtn, consultationLink.nextSibling);
                    parentDiv.insertBefore(deleteBtn, editBtn.nextSibling);
                } else {
                    parentDiv.appendChild(editBtn);
                    parentDiv.appendChild(deleteBtn);
                }
                
                // 수정 버튼 이벤트 추가
                editBtn.addEventListener('click', function() {
                    openReviewModal(internetId);
                });
                
                // 삭제 버튼 이벤트 추가
                deleteBtn.addEventListener('click', function() {
                    openDeleteModal(internetId, editBtn, deleteBtn, consultationLink, parentDiv);
                });
            }
        });
    }

    // 리뷰 작성 완료 토스트 메시지 표시 함수 (화면 중앙)
    function showReviewToast(message) {
        // 기존 토스트가 있으면 제거
        const existingToast = document.querySelector('.internet-review-toast');
        if (existingToast) {
            existingToast.remove();
        }

        // 토스트 메시지 생성
        const toast = document.createElement('div');
        toast.className = 'internet-review-toast';
        toast.textContent = message;
        document.body.appendChild(toast);

        // 화면 정중앙에 위치 설정
        const toastTop = window.innerHeight / 2; // 화면 세로 중앙
        const toastLeft = window.innerWidth / 2; // 화면 가로 중앙

        toast.style.top = toastTop + 'px';
        toast.style.left = toastLeft + 'px';
        toast.style.transform = 'translateX(-50%) translateY(-50%) translateY(10px)';

        // 애니메이션을 위해 약간의 지연 후 visible 클래스 추가
        setTimeout(() => {
            toast.classList.add('internet-review-toast-visible');
        }, 10);

        // 0.7초 후 자동 제거
        setTimeout(() => {
            toast.classList.remove('internet-review-toast-visible');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300); // 애니메이션 시간
        }, 700); // 0.7초
    }
});
</script>

<?php
// 리뷰 작성 모달 포함
include '../includes/components/internet-review-modal.php';
// 리뷰 삭제 확인 모달 포함
include '../includes/components/internet-review-delete-modal.php';
?>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

