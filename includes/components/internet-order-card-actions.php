<?php
/**
 * 인터넷 주문 카드 액션 영역 컴포넌트
 * 신청일, 설치일, 리뷰쓰기 버튼
 * 
 * @param array $internet 인터넷 데이터
 */
if (!isset($internet)) {
    $internet = [];
}
$order_date = $internet['order_date'] ?? '';
$installation_date = $internet['installation_date'] ?? '';
$internet_id = $internet['id'] ?? 0;
$has_review = $internet['has_review'] ?? false; // 리뷰 작성 여부
$is_installed = !empty($installation_date); // 설치 여부
$consultation_url = $internet['consultation_url'] ?? ''; // 상담 URL
?>

<!-- 액션 영역: 신청일 / 설치일 / 리뷰쓰기 또는 설치 문의하기 -->
<div class="internet-order-card-actions" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
    <div class="internet-order-card-actions-content" style="display: flex; flex-direction: column; gap: 0;">
        <!-- 신청일 -->
        <div class="internet-order-action-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
            <span class="internet-order-action-label" style="font-size: 13px; color: #9ca3af;">신청일</span>
            <span class="internet-order-action-value" style="font-size: 13px; color: #374151; font-weight: 500;">
                <?php echo htmlspecialchars($order_date ?: '-'); ?>
            </span>
        </div>
        
        <?php if ($is_installed): ?>
        <!-- 구분선 -->
        <div class="internet-order-action-divider" style="width: 100%; height: 1px; background: #e5e7eb; margin: 0;"></div>
        
        <!-- 설치일 -->
        <div class="internet-order-action-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
            <span class="internet-order-action-label" style="font-size: 13px; color: #9ca3af;">설치일</span>
            <span class="internet-order-action-value" style="font-size: 13px; color: #374151; font-weight: 500;">
                <?php echo htmlspecialchars($installation_date); ?>
            </span>
        </div>
        <?php endif; ?>
        
        <!-- 구분선 -->
        <div class="internet-order-action-divider" style="width: 100%; height: 1px; background: #e5e7eb; margin: 0;"></div>
        
        <!-- 설치 문의하기 또는 서비스 문의하기 버튼 -->
        <div class="internet-order-action-item" style="padding: 8px 0;">
            <?php 
            $buttonText = $is_installed ? '서비스 문의' : '설치 문의';
            $buttonBgColor = $is_installed ? '#6b7280' : '#ec4899';
            ?>
            <?php if ($consultation_url): ?>
                <a href="<?php echo htmlspecialchars($consultation_url); ?>" target="_blank" style="display: block; width: 100%; padding: 10px 16px; background: <?php echo $buttonBgColor; ?>; border-radius: 8px; text-align: center; text-decoration: none; color: white; font-size: 14px; font-weight: 500;">
                    <?php echo $buttonText; ?>
                </a>
            <?php else: ?>
                <button type="button" disabled style="width: 100%; padding: 10px 16px; background: <?php echo $buttonBgColor; ?>; border-radius: 8px; border: none; color: white; font-size: 14px; font-weight: 500; cursor: not-allowed; opacity: 0.6;">
                    <?php echo $buttonText; ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>





