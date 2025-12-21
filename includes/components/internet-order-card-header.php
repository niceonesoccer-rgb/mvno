<?php
/**
 * 인터넷 주문 카드 헤더 컴포넌트 (독립 레이아웃)
 * 로고, 평점, 점 3개 메뉴
 * 
 * @param array $internet 인터넷 데이터
 */
if (!isset($internet)) {
    $internet = [];
}
$provider = $internet['provider'] ?? '';
$internet_id = $internet['id'] ?? 0;
$has_review = $internet['has_review'] ?? false; // 리뷰 작성 여부
$is_installed = !empty($internet['installation_date'] ?? ''); // 설치 여부

// 로고 경로 결정
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

<!-- 헤더: 로고, 평점, 점 3개 메뉴 -->
<div class="internet-order-card-top-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
    <div class="internet-order-provider-rating-group" style="display: flex; align-items: center; gap: 12px;">
        <?php if ($logoUrl): ?>
            <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($displayProvider); ?>" style="width: 40px; height: 40px; object-fit: contain;">
        <?php elseif (isset($companyLogos[$provider])): ?>
            <img src="<?php echo htmlspecialchars($companyLogos[$provider]); ?>" alt="<?php echo htmlspecialchars($displayProvider); ?>" style="width: 40px; height: 40px; object-fit: contain;">
        <?php else: ?>
            <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 17L12 22L22 17" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 12L12 17L22 12" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        <?php endif; ?>
        <span style="font-size: 16px; font-weight: 600; color: #374151;"><?php echo htmlspecialchars($displayProvider); ?></span>
    </div>
    <div class="internet-order-menu-group" style="position: relative;">
        <?php if ($is_installed && $has_review): ?>
        <!-- 리뷰 작성 후: 점 3개 메뉴 버튼 -->
        <button type="button" class="internet-order-menu-btn" data-internet-id="<?php echo $internet_id; ?>" aria-label="메뉴" style="background: none; border: none; padding: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="6" r="1.5" fill="#868E96"/>
                <circle cx="12" cy="12" r="1.5" fill="#868E96"/>
                <circle cx="12" cy="18" r="1.5" fill="#868E96"/>
            </svg>
        </button>
        <!-- 드롭다운 메뉴 -->
        <div class="internet-order-menu-dropdown" id="internet-order-menu-<?php echo $internet_id; ?>" style="display: none;">
            <button type="button" class="internet-order-menu-item internet-order-review-edit-btn" data-internet-id="<?php echo $internet_id; ?>">
                수정
            </button>
            <button type="button" class="internet-order-menu-item internet-order-review-delete-btn" data-internet-id="<?php echo $internet_id; ?>">
                삭제
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>






















