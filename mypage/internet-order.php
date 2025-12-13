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
                                                            <?php 
                                    // 인터넷 카드 컴포넌트 사용
                                    include '../includes/components/internet-order-internet-card.php';
                                    ?>
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

<?php
// 공통 리뷰 모달 포함 (스크립트 전에 포함)
$prefix = 'internet';
$speedLabel = '설치 빨라요';
$formId = 'internetReviewForm';
$modalId = 'internetReviewModal';
$textareaId = 'internetReviewText';
include '../includes/components/order-review-modal.php';

// 공통 리뷰 삭제 모달 포함
$prefix = 'internet';
$modalId = 'internetReviewDeleteModal';
include '../includes/components/order-review-delete-modal.php';
?>

<script src="../assets/js/plan-accordion.js" defer></script>

<script>
// order-review.js가 로드된 후 실행되도록 확인
function initInternetOrderReview() {
    if (typeof OrderReviewManager === 'undefined') {
        console.log('OrderReviewManager 아직 로드되지 않음, 재시도...');
        setTimeout(initInternetOrderReview, 100);
        return;
    }
    
    document.addEventListener('DOMContentLoaded', function() {
    // 더보기 기능
    const moreBtn = document.getElementById('moreInternetsBtn');
    const internetItems = document.querySelectorAll('.internet-item');
    let visibleCount = 10;
    const totalInternets = internetItems.length;
    const loadCount = 10;

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
    }

    if (visibleCount >= totalInternets) {
        const moreButtonContainer = document.getElementById('moreButtonContainer');
        if (moreButtonContainer) {
            moreButtonContainer.style.display = 'none';
        }
    }

    // 리뷰 관리 공통 모듈 초기화
    console.log('OrderReviewManager 초기화 시작 - internet');
    const reviewButtons = document.querySelectorAll('.internet-order-review-btn');
    console.log('리뷰쓰기 버튼 개수:', reviewButtons.length);
    reviewButtons.forEach((btn, idx) => {
        console.log(`버튼 ${idx}:`, btn.className, btn.getAttribute('data-internet-id'));
    });
    
    // 모달 존재 확인
            const modal = document.getElementById('internetReviewModal');
    console.log('모달 존재 여부:', modal ? '있음' : '없음', modal);
    
    const reviewManager = new OrderReviewManager({
        prefix: 'internet',
        itemIdAttr: 'data-internet-id',
        speedLabel: '설치 빨라요',
        textareaId: 'internetReviewText',
        onReviewSubmit: function(internetId, reviewData) {
            // TODO: 서버로 리뷰 데이터 전송
            console.log('리뷰 작성 - Internet ID:', internetId, 'Review Data:', reviewData);
        },
        onReviewDelete: function(internetId) {
            // TODO: 서버로 삭제 요청
            console.log('리뷰 삭제 - Internet ID:', internetId);
        },
        onReviewUpdate: function(internetId) {
            // 리뷰 작성 완료 후 리뷰 쓰기 버튼 제거하고 점 3개 메뉴 표시
            const reviewBtn = document.querySelector(`.internet-order-review-btn[data-internet-id="${internetId}"]`);
            if (reviewBtn) {
                const actionItem = reviewBtn.closest('.internet-order-action-item');
                if (actionItem) {
                    const prevDivider = actionItem.previousElementSibling;
                    if (prevDivider && prevDivider.classList.contains('internet-order-action-divider')) {
                        prevDivider.remove();
                    }
                    actionItem.remove();
                } else {
                    reviewBtn.remove();
                }
            }
            
            // 헤더에 점 3개 메뉴 추가
            const card = document.querySelector(`.css-58gch7.e82z5mt0[data-internet-id="${internetId}"]`);
            if (card) {
                const cardHeader = card.querySelector('.internet-order-card-top-header');
                if (cardHeader) {
                    const menuGroup = cardHeader.querySelector('.internet-order-menu-group');
                    if (!menuGroup) {
                        const newMenuGroup = document.createElement('div');
                        newMenuGroup.className = 'internet-order-menu-group';
                        newMenuGroup.style.cssText = 'position: relative;';
                        
                        const menuBtn = document.createElement('button');
                        menuBtn.type = 'button';
                        menuBtn.className = 'internet-order-menu-btn';
                        menuBtn.setAttribute('data-internet-id', internetId);
                        menuBtn.setAttribute('aria-label', '메뉴');
                        menuBtn.style.cssText = 'background: none; border: none; padding: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center;';
                        menuBtn.innerHTML = `
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="6" r="1.5" fill="#868E96"/>
                                <circle cx="12" cy="12" r="1.5" fill="#868E96"/>
                                <circle cx="12" cy="18" r="1.5" fill="#868E96"/>
                            </svg>
                        `;
                        
                        const dropdown = document.createElement('div');
                        dropdown.className = 'internet-order-menu-dropdown';
                        dropdown.id = `internet-order-menu-${internetId}`;
                        dropdown.style.cssText = 'display: none; position: absolute; top: 100%; right: 0; margin-top: 4px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); z-index: 1000; min-width: 120px; overflow: hidden; flex-direction: column;';
                        
                        const editBtn = document.createElement('button');
                        editBtn.type = 'button';
                        editBtn.className = 'internet-order-menu-item internet-order-review-edit-btn';
                        editBtn.setAttribute('data-internet-id', internetId);
                        editBtn.textContent = '수정';
                        editBtn.style.cssText = 'width: 100%; padding: 12px 16px; background: none; border: none; text-align: left; font-size: 14px; color: #374151; cursor: pointer; transition: background-color 0.2s;';
                        
                        const deleteBtn = document.createElement('button');
                        deleteBtn.type = 'button';
                        deleteBtn.className = 'internet-order-menu-item internet-order-review-delete-btn';
                        deleteBtn.setAttribute('data-internet-id', internetId);
                        deleteBtn.textContent = '삭제';
                        deleteBtn.style.cssText = 'width: 100%; padding: 12px 16px; background: none; border: none; border-top: 1px solid #e5e7eb; text-align: left; font-size: 14px; color: #ef4444; cursor: pointer; transition: background-color 0.2s;';
                        
                        dropdown.appendChild(editBtn);
                        dropdown.appendChild(deleteBtn);
                        newMenuGroup.appendChild(menuBtn);
                        newMenuGroup.appendChild(dropdown);
                        cardHeader.appendChild(newMenuGroup);
                    }
                }
            }
        },
        onReviewDeleteUpdate: function(internetId) {
            // 점 3개 메뉴 제거하고 리뷰 쓰기 버튼 복원
            const card = document.querySelector(`.css-58gch7.e82z5mt0[data-internet-id="${internetId}"]`);
            if (card) {
                const cardHeader = card.querySelector('.internet-order-card-top-header');
                if (cardHeader) {
                    const menuGroup = cardHeader.querySelector('.internet-order-menu-group');
                    if (menuGroup) {
                        menuGroup.remove();
                    }
                }
            }
            
            // 리뷰 쓰기 버튼 추가
            const actionsContent = card ? card.querySelector('.internet-order-card-actions-content') : null;
            if (actionsContent) {
                const actionItem = document.createElement('div');
                actionItem.className = 'internet-order-action-item';
                actionItem.style.cssText = 'padding: 8px 0;';
                
                const divider = document.createElement('div');
                divider.className = 'internet-order-action-divider';
                divider.style.cssText = 'width: 100%; height: 1px; background: #e5e7eb; margin: 0;';
                
                const reviewBtn = document.createElement('button');
                reviewBtn.type = 'button';
                reviewBtn.className = 'internet-order-review-btn';
                reviewBtn.setAttribute('data-internet-id', internetId);
                reviewBtn.textContent = '리뷰 쓰기';
                reviewBtn.style.cssText = 'width: 100%; padding: 10px 16px; background: #6366f1; border-radius: 8px; border: none; color: white; font-size: 14px; font-weight: 500; cursor: pointer;';
                
                actionsContent.appendChild(divider);
                actionItem.appendChild(reviewBtn);
                actionsContent.appendChild(actionItem);
            }
        }
    });
}

// order-review.js 스크립트 동적 로드
const orderReviewScript = document.createElement('script');
orderReviewScript.src = '../assets/js/order-review.js';
orderReviewScript.onload = function() {
    console.log('order-review.js 로드 완료');
    initInternetOrderReview();
};
document.head.appendChild(orderReviewScript);
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

