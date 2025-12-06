<?php
/**
 * 인터넷 주문 카드 전체 컴포넌트 (internet-order.php 전용)
 * internet-order.php만 사용하는 독립적인 레이아웃
 * 
 * @param array $internet 인터넷 데이터
 * @param array $companyLogos 회사 로고 매핑 배열
 * @param int $index 인덱스 (더보기 기능용, 기본값: 0)
 */
if (!isset($internet)) {
    $internet = [];
}
if (!isset($companyLogos)) {
    $companyLogos = [];
}
$index = $index ?? 0;
$internet_id = $internet['id'] ?? 0;
$provider = $internet['provider'] ?? '';
$speed = $internet['speed'] ?? '';
$tv_combined = $internet['tv_combined'] ?? false;
$price = $internet['price'] ?? '';
$order_date = $internet['order_date'] ?? '';
$installation_date = $internet['installation_date'] ?? '';
$has_review = $internet['has_review'] ?? false;
$review_count = $internet['review_count'] ?? 0;
$consultation_url = $internet['consultation_url'] ?? '';
?>

<div class="internet-item" data-index="<?php echo $index; ?>" style="<?php echo $index >= 10 ? 'display: none;' : ''; ?> margin-bottom: 1rem;">
    <div class="css-58gch7 e82z5mt0" data-internet-id="<?php echo $internet_id; ?>">
        <?php
        // 헤더 컴포넌트 포함
        include __DIR__ . '/internet-order-card-header.php';
        ?>
        
        <!-- 상품 정보 영역 -->
        <div style="margin-bottom: 1rem;">
            <div class="css-huskxe e82z5mt13" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <div class="css-1fd5u73 e82z5mt14" style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.875rem; color: #374151;">
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
                    </span><?php echo htmlspecialchars($speed); ?>
                </div>
            </div>
        </div>
        <div class="css-174t92n e82z5mt7">
            <?php if ($tv_combined): ?>
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
            <p class="css-16qot29 e82z5mt6"><?php echo htmlspecialchars($price); ?></p>
        </div>
        
        <?php
        // 액션 영역 컴포넌트 포함
        include __DIR__ . '/internet-order-card-actions.php';
        ?>
    </div>
</div>

