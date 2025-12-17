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

// 리뷰 설정 파일 포함
require_once __DIR__ . '/../../includes/data/review-settings.php';

$order_date = $internet['order_date'] ?? '';
$installation_date = $internet['installation_date'] ?? '';
$internet_id = $internet['id'] ?? 0;
$has_review = $internet['has_review'] ?? false; // 리뷰 작성 여부
$application_status = $internet['application_status'] ?? ''; // 진행상황
$consultation_url = $internet['consultation_url'] ?? ''; // 상담 URL

// 리뷰 작성 가능 여부 확인 (진행상황 기반)
$can_write_review = canWriteReview($application_status);
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
        
        <?php if (!empty($installation_date)): ?>
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
        
        <?php if ($can_write_review && !$has_review): ?>
        <!-- 구분선 -->
        <div class="internet-order-action-divider" style="width: 100%; height: 1px; background: #e5e7eb; margin: 0;"></div>
        
        <!-- 리뷰쓰기 버튼 -->
        <div class="internet-order-action-item" style="padding: 8px 0;">
            <button type="button" class="internet-order-review-btn" data-internet-id="<?php echo $internet_id; ?>" style="width: 100%; padding: 10px 16px; background: #6366f1; border-radius: 8px; border: none; color: white; font-size: 14px; font-weight: 500; cursor: pointer;">
                리뷰쓰기
            </button>
        </div>
        <?php elseif (!$can_write_review && !empty($consultation_url)): ?>
        <!-- 구분선 -->
        <div class="internet-order-action-divider" style="width: 100%; height: 1px; background: #e5e7eb; margin: 0;"></div>
        
        <!-- 설치 문의하기 또는 서비스 문의하기 버튼 -->
        <div class="internet-order-action-item" style="padding: 8px 0;">
            <?php 
            $buttonText = !empty($installation_date) ? '서비스 문의' : '설치 문의';
            $buttonBgColor = !empty($installation_date) ? '#6b7280' : '#ec4899';
            ?>
            <a href="<?php echo htmlspecialchars($consultation_url); ?>" target="_blank" style="display: block; width: 100%; padding: 10px 16px; background: <?php echo $buttonBgColor; ?>; border-radius: 8px; text-align: center; text-decoration: none; color: white; font-size: 14px; font-weight: 500;">
                <?php echo $buttonText; ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>





