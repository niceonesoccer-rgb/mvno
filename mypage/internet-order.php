<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = false;

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';

// 로그인 체크 - 로그인하지 않은 경우 회원가입 모달로 리다이렉트
if (!isLoggedIn()) {
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    // 로그인 모달이 있는 홈으로 리다이렉트 (모달 자동 열기)
    header('Location: /MVNO/?show_login=1');
    exit;
}

// 현재 사용자 정보 가져오기
$currentUser = getCurrentUser();
if (!$currentUser) {
    // 사용자 정보를 가져올 수 없으면 로그아웃 처리
    header('Location: /MVNO/?show_login=1');
    exit;
}

$user_id = $currentUser['user_id'];

// 포인트 설정 및 함수 포함
require_once '../includes/data/point-settings.php';
require_once '../includes/data/product-functions.php';
require_once '../includes/data/db-config.php';

// DB에서 실제 신청 내역 가져오기
$internets = getUserInternetApplications($user_id);

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
                    <?php if (empty($internets)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: #6b7280;">
                            신청한 인터넷이 없습니다.
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($internets as $index => $internet): ?>
                                <div class="internet-item" data-index="<?php echo $index; ?>" data-internet-id="<?php echo $internet['id']; ?>" style="<?php echo $index >= 10 ? 'display: none;' : ''; ?> padding: 16px; border-bottom: 1px solid #e5e7eb;">
                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                            <div style="flex: 1;">
                                                <div style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 4px;">
                                                    <?php echo htmlspecialchars($internet['provider'] ?? ''); ?> <?php echo htmlspecialchars($internet['plan_name'] ?? ''); ?>
                                                </div>
                                                <?php if (!empty($internet['speed'])): ?>
                                                    <div style="font-size: 14px; color: #6b7280; margin-bottom: 4px;">
                                                        속도: <?php echo htmlspecialchars($internet['speed']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div style="font-size: 14px; color: #374151; font-weight: 500;">
                                                    <?php echo htmlspecialchars($internet['price'] ?? ''); ?>
                                                </div>
                                            </div>
                                            <div style="font-size: 12px; color: #9ca3af;">
                                                <?php echo htmlspecialchars($internet['order_date'] ?? ''); ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($internet['status'])): ?>
                                            <div style="font-size: 12px; color: #6366f1;">
                                                상태: <?php echo htmlspecialchars($internet['status']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 더보기 버튼 -->
                <?php if (count($internets) > 10): ?>
                <div style="margin-top: 32px; margin-bottom: 32px;" id="moreButtonContainer">
                    <button class="plan-review-more-btn" id="moreInternetsBtn" style="width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                        더보기 (<?php 
                        $remaining = count($internets) - 10;
                        echo $remaining > 10 ? 10 : $remaining;
                        ?>개)
                    </button>
                </div>
                <?php endif; ?>
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

/* 주문 페이지 리뷰 모달 별점 스타일 */
[class*="-review-modal"] [class*="-rating-group-row"] {
    display: flex;
    flex-direction: column;
    gap: 0;
}

[class*="-review-modal"] [class*="-rating-item"] {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 0;
}

[class*="-review-modal"] [class*="-rating-item-spaced"] {
    margin-top: 32px;
}

[class*="-review-modal"] [class*="-review-form-label"] {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0;
}

[class*="-review-modal"] [class*="-star-rating"] {
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
}

[class*="-review-modal"] [class*="-star-rating"] input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    margin: 0;
    padding: 0;
    pointer-events: none;
}

[class*="-review-modal"] [class*="-star-label"] {
    display: inline-block;
    cursor: pointer;
    color: #d1d5db;
    transition: color 0.2s ease, transform 0.1s ease;
    padding: 4px;
    margin: 0;
    line-height: 0;
}

[class*="-review-modal"] [class*="-star-label"]:hover {
    transform: scale(1.1);
}

[class*="-review-modal"] [class*="-star-label"] svg {
    display: block;
    width: 32px;
    height: 32px;
}

/* 기본 별 색상은 회색, JavaScript로 동적으로 변경됨 */
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 더보기 기능
    const moreBtn = document.getElementById('moreInternetsBtn');
    const internetItems = document.querySelectorAll('.internet-item');
    let visibleCount = 10;
    const totalInternets = internetItems.length;
    const loadCount = 10;

    function updateButtonText() {
        if (!moreBtn) return;
        const remaining = totalInternets - visibleCount;
        if (remaining > 0) {
            const showCount = remaining > loadCount ? loadCount : remaining;
            moreBtn.textContent = `더보기 (${showCount}개)`;
        }
    }

    if (moreBtn && totalInternets > 10) {
        updateButtonText();
        
        moreBtn.addEventListener('click', function() {
            const endCount = Math.min(visibleCount + loadCount, totalInternets);
            for (let i = visibleCount; i < endCount; i++) {
                if (internetItems[i]) {
                    internetItems[i].style.display = 'block';
                }
            }
            
            visibleCount = endCount;
            
            if (visibleCount >= totalInternets) {
                const moreButtonContainer = document.getElementById('moreButtonContainer');
                if (moreButtonContainer) {
                    moreButtonContainer.style.display = 'none';
                }
            } else {
                updateButtonText();
            }
        });
    } else if (moreBtn && totalInternets <= 10) {
        const moreButtonContainer = document.getElementById('moreButtonContainer');
        if (moreButtonContainer) {
            moreButtonContainer.style.display = 'none';
        }
    }
});
</script>
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

