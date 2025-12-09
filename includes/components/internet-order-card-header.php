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
?>

<!-- 헤더: 로고, 평점, 점 3개 메뉴 -->
<div class="internet-order-card-top-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
    <div class="internet-order-provider-rating-group" style="display: flex; align-items: center; gap: 12px;">
        <?php if (isset($companyLogos[$provider])): ?>
            <img src="<?php echo htmlspecialchars($companyLogos[$provider]); ?>" alt="<?php echo htmlspecialchars($provider); ?>" style="width: 40px; height: 40px; object-fit: contain;">
        <?php else: ?>
            <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 17L12 22L22 17" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 12L12 17L22 12" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        <?php endif; ?>
        <span style="font-size: 16px; font-weight: 600; color: #374151;"><?php echo htmlspecialchars($provider); ?></span>
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





















