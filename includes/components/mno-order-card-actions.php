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

// 리뷰 설정 파일 포함
require_once __DIR__ . '/../../includes/data/review-settings.php';

$order_date = $phone['order_date'] ?? '';
$order_time = $phone['order_time'] ?? '';
$activation_date = $phone['activation_date'] ?? '';
$activation_time = $phone['activation_time'] ?? '';
$phone_id = $phone['id'] ?? 0;
$has_review = $phone['has_review'] ?? false; // 리뷰 작성 여부
$application_status = $phone['application_status'] ?? ''; // 진행상황
$consultation_url = $phone['consultation_url'] ?? ''; // 상담 URL

// 리뷰 작성 가능 여부 확인 (진행상황 기반)
$can_write_review = canWriteReview($application_status);
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
        
        <?php if (!empty($activation_date)): ?>
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
        
        <?php 
        // 포인트 사용 내역 표시
        $point_used = $phone['point_used'] ?? 0;
        $point_used_date = $phone['point_used_date'] ?? '';
        if ($point_used > 0): 
        ?>
        <!-- 구분선 -->
        <div class="mno-order-action-divider"></div>
        
        <!-- 포인트 사용 내역 -->
        <div class="mno-order-action-item">
            <span class="mno-order-action-label">포인트 사용</span>
            <span class="mno-order-action-value" style="color: #6366f1; font-weight: 600;">
                -<?php echo number_format($point_used); ?>원
            </span>
        </div>
        <?php endif; ?>
        
        <?php if ($can_write_review && !$has_review): ?>
        <!-- 구분선 -->
        <div class="mno-order-action-divider"></div>
        
        <!-- 리뷰쓰기 버튼 -->
        <div class="mno-order-action-item">
            <button type="button" class="mno-order-review-btn" data-phone-id="<?php echo $phone_id; ?>">
                리뷰쓰기
            </button>
        </div>
        <?php elseif (!$can_write_review && !empty($consultation_url)): ?>
        <!-- 구분선 -->
        <div class="mno-order-action-divider"></div>
        
        <!-- 개통 문의 버튼 -->
        <div class="mno-order-action-item">
            <a href="<?php echo htmlspecialchars($consultation_url); ?>" target="_blank" class="mno-order-inquiry-btn">
                개통 문의
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

