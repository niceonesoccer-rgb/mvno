<?php
/**
 * 통신사폰 주문 카드 액션 영역 컴포넌트
 * 신청일, 개통일, 리뷰쓰기 버튼
 * 
 * @param array $phone 통신사폰 데이터
 */
if (!isset($phone)) {
    $phone = [];
}
$order_date = $phone['order_date'] ?? '';
$order_time = $phone['order_time'] ?? '';
$activation_date = $phone['activation_date'] ?? '';
$activation_time = $phone['activation_time'] ?? '';
$phone_id = $phone['id'] ?? 0;
$has_review = $phone['has_review'] ?? false; // 리뷰 작성 여부
$is_activated = !empty($activation_date); // 개통 여부
$consultation_url = $phone['consultation_url'] ?? ''; // 상담 URL
?>

<!-- 액션 영역: 신청일 / 개통일 / 리뷰쓰기 또는 업체 문의하기 -->
<div class="mno-order-card-actions">
    <div class="mno-order-card-actions-content">
        <!-- 신청일 -->
        <div class="mno-order-action-item">
            <span class="mno-order-action-label">신청일</span>
            <span class="mno-order-action-value">
                <?php if ($order_date): ?>
                    <?php echo htmlspecialchars($order_date); ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </span>
        </div>
        
        <?php if ($is_activated): ?>
        <!-- 구분선 -->
        <div class="mno-order-action-divider"></div>
        
        <!-- 개통일 -->
        <div class="mno-order-action-item">
            <span class="mno-order-action-label">개통일</span>
            <span class="mno-order-action-value">
                <?php echo htmlspecialchars($activation_date); ?>
            </span>
        </div>
        <?php endif; ?>
        
        <!-- 구분선 -->
        <div class="mno-order-action-divider"></div>
        
        <!-- 리뷰쓰기 버튼 또는 업체 문의하기 버튼 -->
        <div class="mno-order-action-item">
            <?php if ($is_activated): ?>
                <!-- 개통된 경우: 리뷰쓰기 버튼 또는 수정/삭제 버튼 -->
                <?php if ($has_review): ?>
                    <div style="display: flex; gap: 8px; width: 100%;">
                        <button type="button" class="mno-order-review-edit-btn" data-phone-id="<?php echo $phone_id; ?>" style="flex: 1; padding: 10px 16px; background: #6366f1; border-radius: 8px; border: none; color: white; font-size: 14px; font-weight: 500; cursor: pointer;">
                            수정
                        </button>
                        <button type="button" class="mno-order-review-delete-btn" data-phone-id="<?php echo $phone_id; ?>" style="flex: 1; padding: 10px 16px; background: #ef4444; border-radius: 8px; border: none; color: white; font-size: 14px; font-weight: 500; cursor: pointer;">
                            삭제
                        </button>
                    </div>
                <?php else: ?>
                    <button type="button" class="mno-order-review-btn" data-phone-id="<?php echo $phone_id; ?>">
                        리뷰쓰기
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <!-- 개통 안된 경우: 개통 문의 버튼 -->
                <?php if ($consultation_url): ?>
                    <a href="<?php echo htmlspecialchars($consultation_url); ?>" target="_blank" class="mno-order-inquiry-btn">
                        개통 문의
                    </a>
                <?php else: ?>
                    <button type="button" class="mno-order-inquiry-btn" disabled>
                        개통 문의
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

