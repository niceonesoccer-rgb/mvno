<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = false;

// 포인트 설정 및 함수 포함
require_once '../includes/data/point-settings.php';
$user_id = 'default'; // 실제로는 세션에서 가져옴

// 요금제 데이터 배열 (주문 내역용)
$plans = [
    ['id' => 32627, 'provider' => '쉐이크모바일', 'rating' => 4.3, 'title' => '[모요핫딜] 11월한정 LTE 100GB+밀리+Data쿠폰60GB', 'data_main' => '월 100GB + 5Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price_main' => '월 17,000원', 'price_after' => '7개월 이후 42,900원', 'selection_count' => '29,448명이 신청', 'gifts' => ['이마트 상품권', '네이버페이', '데이터쿠폰 20GB', '밀리의 서재', 'SOLO결합(+20GB)'], 'gift_count' => 5, 'order_date' => '2024.11.15', 'activation_date' => '2024.11.16', 'has_review' => false, 'is_sold_out' => false],
    ['id' => 32632, 'provider' => '고고모바일', 'rating' => 4.2, 'title' => 'LTE무제한 100GB+5M(CU20%할인)_11월', 'data_main' => '월 100GB + 5Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price_main' => '월 17,000원', 'price_after' => '7개월 이후 42,900원', 'selection_count' => '12,353명이 신청', 'gifts' => ['KT유심&배송비 무료', '데이터쿠폰 20GB x 3회', '추가데이터 20GB 제공', '이마트 상품권', 'CU 상품권', '네이버페이'], 'gift_count' => 6, 'order_date' => '2024.11.12', 'activation_date' => '2024.11.13', 'has_review' => false, 'is_sold_out' => false],
    ['id' => 29290, 'provider' => '이야기모바일', 'rating' => 4.5, 'title' => '이야기 완전 무제한 100GB+', 'data_main' => '월 100GB + 5Mbps', 'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'], 'price_main' => '월 17,000원', 'price_after' => '7개월 이후 49,500원', 'selection_count' => '17,816명이 신청', 'gifts' => ['네이버페이', '네이버페이'], 'gift_count' => 2, 'order_date' => '2024.11.10', 'activation_date' => '2024.11.11', 'has_review' => true, 'is_sold_out' => false],
    ['id' => 32628, 'provider' => '핀다이렉트', 'rating' => 4.2, 'title' => '[S] 핀다이렉트Z _2511', 'data_main' => '월 11GB + 매일 2GB + 3Mbps', 'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'], 'price_main' => '월 14,960원', 'price_after' => '7개월 이후 39,600원', 'selection_count' => '4,420명이 신청', 'gifts' => ['매월 20GB 추가 데이터', '네이버페이'], 'gift_count' => 2, 'order_date' => '2024.11.08', 'activation_date' => '', 'has_review' => false, 'is_sold_out' => false, 'consultation_url' => 'https://example.com/consultation'],
    ['id' => 32629, 'provider' => '고고모바일', 'rating' => 4.2, 'title' => '무제한 11GB+3M(밀리의서재 Free)_11월', 'data_main' => '월 11GB + 매일 2GB + 3Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price_main' => '월 15,000원', 'price_after' => '7개월 이후 36,300원', 'selection_count' => '6,970명이 신청', 'gifts' => ['KT유심&배송비 무료', '데이터쿠폰 20GB x 3회', '추가데이터 20GB 제공', '이마트 상품권', '네이버페이', '밀리의 서재'], 'gift_count' => 6, 'order_date' => '2024.11.05', 'activation_date' => '2024.11.06', 'has_review' => false, 'is_sold_out' => false],
    ['id' => 32630, 'provider' => '찬스모바일', 'rating' => 4.5, 'title' => '음성기본 11GB+일 2GB+', 'data_main' => '월 11GB + 매일 2GB + 3Mbps', 'features' => ['통화 무제한', '문자 무제한', 'LG U+망', 'LTE'], 'price_main' => '월 15,000원', 'price_after' => '7개월 이후 38,500원', 'selection_count' => '31,315명이 신청', 'gifts' => ['유심/배송비 무료', '네이버페이'], 'gift_count' => 2, 'order_date' => '2024.11.03', 'activation_date' => '2024.11.04', 'has_review' => false, 'is_sold_out' => false],
    ['id' => 32631, 'provider' => '이야기모바일', 'rating' => 4.5, 'title' => '이야기 완전 무제한 11GB+', 'data_main' => '월 11GB + 매일 2GB + 3Mbps', 'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'], 'price_main' => '월 15,000원', 'price_after' => '7개월 이후 39,600원', 'selection_count' => '13,651명이 신청', 'gifts' => ['네이버페이', '네이버페이'], 'gift_count' => 2, 'order_date' => '2024.11.01', 'activation_date' => '2024.11.02', 'has_review' => false, 'is_sold_out' => false],
    ['id' => 32633, 'provider' => '찬스모바일', 'rating' => 4.5, 'title' => '100분 15GB+', 'data_main' => '월 15GB + 3Mbps', 'features' => ['통화 100분', '문자 100건', 'LG U+망', 'LTE'], 'price_main' => '월 14,000원', 'price_after' => '7개월 이후 30,580원', 'selection_count' => '7,977명이 신청', 'gifts' => ['유심/배송비 무료', '네이버페이'], 'gift_count' => 2, 'order_date' => '2024.10.28', 'activation_date' => '2024.10.29', 'has_review' => false, 'is_sold_out' => false],
    ['id' => 32634, 'provider' => '쉐이크모바일', 'rating' => 4.3, 'title' => 'LTE 완전무제한 200GB+', 'data_main' => '월 200GB + 5Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price_main' => '월 25,000원', 'price_after' => '7개월 이후 52,900원', 'selection_count' => '8,234명이 신청', 'gifts' => ['이마트 상품권', '네이버페이', '데이터쿠폰 30GB', '밀리의 서재'], 'gift_count' => 4, 'order_date' => '2024.10.25', 'activation_date' => '2024.10.26', 'has_review' => false, 'is_sold_out' => false],
    ['id' => 32635, 'provider' => '고고모바일', 'rating' => 4.2, 'title' => '스마트 요금제 50GB+', 'data_main' => '월 50GB + 3Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price_main' => '월 12,000원', 'price_after' => '7개월 이후 35,900원', 'selection_count' => '15,678명이 신청', 'gifts' => ['CU 상품권', '네이버페이'], 'gift_count' => 2, 'order_date' => '2024.10.22', 'activation_date' => '2024.10.23', 'has_review' => false, 'is_sold_out' => false],
    ['id' => 32636, 'provider' => '핀다이렉트', 'rating' => 4.2, 'title' => '[K] 핀다이렉트Z 7GB+(네이버페이) _2511', 'data_main' => '월 7GB + 1Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price_main' => '월 8,000원', 'price_after' => '7개월 이후 26,400원', 'selection_count' => '4,407명이 신청', 'gifts' => ['추가 데이터', '매월 5GB 추가 데이터', '이마트 상품권', '네이버페이', '네이버페이'], 'gift_count' => 5, 'order_date' => '2024.10.20', 'activation_date' => '2024.10.21', 'has_review' => false, 'is_sold_out' => false],
    ['id' => 32637, 'provider' => '이야기모바일', 'rating' => 4.5, 'title' => '이야기 베이직 5GB+', 'data_main' => '월 5GB + 1Mbps', 'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'], 'price_main' => '월 6,000원', 'price_after' => '7개월 이후 22,000원', 'selection_count' => '9,123명이 신청', 'gifts' => ['네이버페이'], 'gift_count' => 1, 'order_date' => '2024.10.18', 'activation_date' => '2024.10.19', 'has_review' => false, 'is_sold_out' => false],
    ['id' => 32638, 'provider' => '찬스모바일', 'rating' => 4.5, 'title' => '찬스 프리미엄 150GB+', 'data_main' => '월 150GB + 5Mbps', 'features' => ['통화 무제한', '문자 무제한', 'LG U+망', 'LTE'], 'price_main' => '월 20,000원', 'price_after' => '7개월 이후 45,000원', 'selection_count' => '11,456명이 신청', 'gifts' => ['유심/배송비 무료', '네이버페이', '데이터쿠폰 25GB'], 'gift_count' => 3, 'order_date' => '2024.10.15', 'activation_date' => '2024.10.16', 'has_review' => false, 'is_sold_out' => false],
    ['id' => 32639, 'provider' => '쉐이크모바일', 'rating' => 4.3, 'title' => '쉐이크 스탠다드 30GB+', 'data_main' => '월 30GB + 3Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price_main' => '월 10,000원', 'price_after' => '7개월 이후 32,900원', 'selection_count' => '18,234명이 신청', 'gifts' => ['이마트 상품권', '네이버페이', '데이터쿠폰 15GB'], 'gift_count' => 3, 'order_date' => '2024.10.12', 'activation_date' => '2024.10.13', 'has_review' => false, 'is_sold_out' => false],
    ['id' => 32640, 'provider' => '고고모바일', 'rating' => 4.2, 'title' => '고고 울트라 80GB+', 'data_main' => '월 80GB + 5Mbps', 'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'], 'price_main' => '월 16,000원', 'price_after' => '7개월 이후 41,900원', 'selection_count' => '14,567명이 신청', 'gifts' => ['CU 상품권', '네이버페이', '데이터쿠폰 20GB', '이마트 상품권'], 'gift_count' => 4, 'order_date' => '2024.10.10', 'activation_date' => '2024.10.11', 'has_review' => false, 'is_sold_out' => false],
];

// 헤더 포함
include '../includes/header.php';

// 사은품 아이콘 매핑
$giftIcons = [
    '이마트 상품권' => 'emart',
    '네이버페이' => 'naverpay',
    '데이터쿠폰' => 'ticket',
    '밀리의 서재' => 'millie',
    'SOLO결합' => 'subscription',
    'CU 상품권' => 'cu',
    'KT유심&배송비 무료' => 'etc',
    '추가데이터' => 'ticket',
    '유심/배송비 무료' => 'etc',
    '매월' => 'ticket',
    '추가 데이터' => 'ticket',
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
                        <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 알뜰폰</h2>
                    </div>
                </div>

                <!-- 신청한 알뜰폰 목록 -->
                <div style="margin-bottom: 32px;" id="plansContainer">
                    <div class="mvno-order-list-container">
                <?php foreach ($plans as $index => $plan): ?>
                    <div class="plan-item" data-index="<?php echo $index; ?>" style="<?php echo $index >= 10 ? 'display: none;' : ''; ?> position: relative;">
                        <?php
                        // 요금제 데이터 준비
                        $plan_data = $plan;
                        
                        // 포인트 사용 내역 가져오기
                        $point_history = getPointHistoryByItem($user_id, 'mvno', $plan['id']);
                        $plan_data['point_used'] = $point_history ? $point_history['amount'] : 0;
                        $plan_data['point_used_date'] = $point_history ? $point_history['date'] : '';
                        
                        $plan = $plan_data; // 컴포넌트에서 사용할 변수
                        $layout_type = 'list';
                        $card_wrapper_class = '';
                        include '../includes/components/mvno-order-plan-card.php';
                        ?>
                    </div>
                    <!-- 카드 구분선 (모바일용) -->
                    <hr class="mvno-order-card-divider">
                <?php endforeach; ?>
                    </div>
                </div>

                <!-- 더보기 버튼 -->
                <div style="margin-top: 32px; margin-bottom: 32px;" id="moreButtonContainer">
                    <button class="plan-review-more-btn" id="morePlansBtn">
                        더보기 (<?php 
                        $remaining = count($plans) - 10;
                        echo $remaining > 10 ? 10 : $remaining;
                        ?>개)
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// 공통 리뷰 모달 포함 (스크립트 전에 포함)
$prefix = 'mvno';
$speedLabel = '개통 빨라요';
$formId = 'mvnoReviewForm';
$modalId = 'mvnoReviewModal';
$textareaId = 'reviewText';
include '../includes/components/order-review-modal.php';

// 공통 리뷰 삭제 모달 포함
$prefix = 'mvno';
$modalId = 'mvnoReviewDeleteModal';
include '../includes/components/order-review-delete-modal.php';

// 공통 리뷰 작성 완료 모달 포함
$prefix = 'mvno';
$modalId = 'mvnoReviewSuccessModal';
include '../includes/components/order-review-success-modal.php';
?>

<script src="../assets/js/plan-accordion.js" defer></script>
<script src="../assets/js/share.js" defer></script>

<script>
// order-review.js가 로드된 후 실행되도록 확인
function initMvnoOrderReview() {
    if (typeof OrderReviewManager === 'undefined') {
        console.log('OrderReviewManager 아직 로드되지 않음, 재시도...');
        setTimeout(initMvnoOrderReview, 100);
        return;
    }
    
    document.addEventListener('DOMContentLoaded', function() {
    // 더보기 기능
    const moreBtn = document.getElementById('morePlansBtn');
    const planItems = document.querySelectorAll('.plan-item');
    let visibleCount = 10;
    const totalPlans = planItems.length;
    const loadCount = 10;

    function updateButtonText() {
        const remaining = totalPlans - visibleCount;
        if (remaining > 0) {
            const showCount = remaining > loadCount ? loadCount : remaining;
            moreBtn.textContent = `더보기 (${showCount}개)`;
        }
    }

    if (moreBtn) {
        updateButtonText();
        
        moreBtn.addEventListener('click', function() {
            const endCount = Math.min(visibleCount + loadCount, totalPlans);
            for (let i = visibleCount; i < endCount; i++) {
                if (planItems[i]) {
                    planItems[i].style.display = 'block';
                }
            }
            
            visibleCount = endCount;
            
            if (visibleCount >= totalPlans) {
                const moreButtonContainer = document.getElementById('moreButtonContainer');
                if (moreButtonContainer) {
                    moreButtonContainer.style.display = 'none';
                }
            } else {
                updateButtonText();
            }
        });
    }

    if (visibleCount >= totalPlans) {
        const moreButtonContainer = document.getElementById('moreButtonContainer');
        if (moreButtonContainer) {
            moreButtonContainer.style.display = 'none';
        }
    }

    // 리뷰 관리 공통 모듈 초기화
    console.log('OrderReviewManager 초기화 시작 - mvno');
    const reviewButtons = document.querySelectorAll('.mvno-order-review-btn');
    console.log('리뷰쓰기 버튼 개수:', reviewButtons.length);
    reviewButtons.forEach((btn, idx) => {
        console.log(`버튼 ${idx}:`, btn.className, btn.getAttribute('data-plan-id'));
    });
    
    // 모달 존재 확인
    const modal = document.getElementById('mvnoReviewModal');
    console.log('모달 존재 여부:', modal ? '있음' : '없음', modal);
    
    const reviewManager = new OrderReviewManager({
        prefix: 'mvno',
        itemIdAttr: 'data-plan-id',
        speedLabel: '개통 빨라요',
        textareaId: 'reviewText',
        showSuccessModal: true,
        onReviewSubmit: function(planId, reviewData) {
            // TODO: 서버로 리뷰 데이터 전송
            console.log('리뷰 작성 - Plan ID:', planId, 'Review Data:', reviewData);
        },
        onReviewDelete: function(planId) {
            // TODO: 서버로 삭제 요청
            console.log('리뷰 삭제 - Plan ID:', planId);
        },
        onReviewUpdate: function(planId) {
            // 리뷰 작성 완료 후 헤더에 점 3개 메뉴 표시
            
            const reviewBtn = document.querySelector(`.mvno-order-review-btn[data-plan-id="${planId}"]`);
            if (reviewBtn) {
                const actionItem = reviewBtn.closest('.mvno-order-action-item');
                const actionsContent = actionItem ? actionItem.closest('.mvno-order-card-actions-content') : null;
                
                if (actionItem && actionsContent) {
                    const prevDivider = actionItem.previousElementSibling;
                    if (prevDivider && prevDivider.classList.contains('mvno-order-action-divider')) {
                        prevDivider.remove();
                    }
                    actionItem.remove();
                } else {
                    reviewBtn.remove();
                }
                
                const cardHeader = document.querySelector(`.mvno-order-card[data-plan-id="${planId}"] .mvno-order-card-top-header`);
                if (cardHeader) {
                    const menuGroup = cardHeader.querySelector('.mvno-order-menu-group');
                    if (!menuGroup) {
                        const newMenuGroup = document.createElement('div');
                        newMenuGroup.className = 'mvno-order-menu-group';
                        
                        const menuBtn = document.createElement('button');
                        menuBtn.type = 'button';
                        menuBtn.className = 'mvno-order-menu-btn';
                        menuBtn.setAttribute('data-plan-id', planId);
                        menuBtn.setAttribute('aria-label', '메뉴');
                        menuBtn.innerHTML = `
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="6" r="1.5" fill="#868E96"/>
                                <circle cx="12" cy="12" r="1.5" fill="#868E96"/>
                                <circle cx="12" cy="18" r="1.5" fill="#868E96"/>
                            </svg>
                        `;
                        
                        const dropdown = document.createElement('div');
                        dropdown.className = 'mvno-order-menu-dropdown';
                        dropdown.id = 'mvno-order-menu-' + planId;
                        
                        const editBtn = document.createElement('button');
                        editBtn.type = 'button';
                        editBtn.className = 'mvno-order-menu-item mvno-order-review-edit-btn';
                        editBtn.setAttribute('data-plan-id', planId);
                        editBtn.textContent = '수정';
                        
                        const deleteBtn = document.createElement('button');
                        deleteBtn.type = 'button';
                        deleteBtn.className = 'mvno-order-menu-item mvno-order-review-delete-btn';
                        deleteBtn.setAttribute('data-plan-id', planId);
                        deleteBtn.textContent = '삭제';
                        
                        dropdown.appendChild(editBtn);
                        dropdown.appendChild(deleteBtn);
                        newMenuGroup.appendChild(menuBtn);
                        newMenuGroup.appendChild(dropdown);
                        cardHeader.appendChild(newMenuGroup);
                        
                        menuBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const menuId = 'mvno-order-menu-' + planId;
                            
                            if (openMenuId && openMenuId !== menuId) {
                                const prevDropdown = document.getElementById(openMenuId);
                                if (prevDropdown) {
                                    prevDropdown.classList.remove('mvno-order-menu-open');
                                }
                            }
                            
                            if (dropdown.classList.contains('mvno-order-menu-open')) {
                                dropdown.classList.remove('mvno-order-menu-open');
                                openMenuId = null;
                            } else {
                                dropdown.classList.add('mvno-order-menu-open');
                                openMenuId = menuId;
                            }
                        });
                        
                        editBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            dropdown.classList.remove('mvno-order-menu-open');
                            openMenuId = null;
                            reviewManager.openReviewModal(planId);
                        });
                        
                        deleteBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            dropdown.classList.remove('mvno-order-menu-open');
                            openMenuId = null;
                            reviewManager.openDeleteModal(planId);
                        });
                    }
                }
            }
        },
        onReviewDeleteUpdate: function(planId) {
            const cardHeader = document.querySelector(`.mvno-order-card[data-plan-id="${planId}"] .mvno-order-card-top-header`);
            if (cardHeader) {
                const menuGroup = cardHeader.querySelector('.mvno-order-menu-group');
                if (menuGroup) {
                    menuGroup.remove();
                }
            }
            
            const actionItem = document.querySelector(`.mvno-order-card[data-plan-id="${planId}"] .mvno-order-action-item:last-child`);
            if (actionItem && !actionItem.querySelector('.mvno-order-review-btn')) {
                const newReviewBtn = document.createElement('button');
                newReviewBtn.type = 'button';
                newReviewBtn.className = 'mvno-order-review-btn';
                newReviewBtn.setAttribute('data-plan-id', planId);
                newReviewBtn.textContent = '리뷰쓰기';
                actionItem.appendChild(newReviewBtn);
            }
        }
    });

    // 점 3개 메뉴 관련 함수들 (기존 로직 유지)
    let openMenuId = null;
    
    function openDeleteModal(planId) {
        reviewManager.openDeleteModal(planId);
    }
    
    function showReviewToast(message) {
        reviewManager.showToast(message);
    }

    // 토스트 메시지 표시 함수 (공유 아이콘과 같은 행, 왼쪽)
    function showToast(message, buttonElement) {
        // 기존 토스트가 있으면 제거
        const existingToast = document.querySelector('.mvno-order-toast');
        if (existingToast) {
            existingToast.remove();
        }

        // 버튼의 위치 계산
        const buttonRect = buttonElement.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
        
        // 버튼과 같은 높이 (세로 중앙)
        const buttonCenterTop = scrollTop + buttonRect.top + (buttonRect.height / 2);

        // 토스트 메시지 생성
        const toast = document.createElement('div');
        toast.className = 'mvno-order-toast';
        toast.textContent = message;
        document.body.appendChild(toast);

        // 공유 아이콘 왼쪽, 같은 행에 위치 설정
        const toastTop = buttonCenterTop;
        const toastLeft = buttonRect.left + scrollLeft - 8; // 버튼 왼쪽 8px

        toast.style.top = toastTop + 'px';
        toast.style.left = toastLeft + 'px';
        toast.style.transform = 'translateX(-100%) translateY(-50%) translateY(10px)';

        // 애니메이션을 위해 약간의 지연 후 visible 클래스 추가
        setTimeout(() => {
            toast.classList.add('mvno-order-toast-visible');
        }, 10);

        // 0.7초 후 자동 제거
        setTimeout(() => {
            toast.classList.remove('mvno-order-toast-visible');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300); // 애니메이션 시간
        }, 700); // 0.7초
    }

    // 점 3개 메뉴 버튼 클릭 이벤트 (슬라이드 메뉴 토글)
    const menuButtons = document.querySelectorAll('.mvno-order-menu-btn');
    let openMenuId = null; // 현재 열린 메뉴 ID
    
    menuButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const planId = this.getAttribute('data-plan-id');
            const menuId = 'mvno-order-menu-' + planId;
            const dropdown = document.getElementById(menuId);
            
            if (!dropdown) return;
            
            // 다른 메뉴가 열려있으면 닫기
            if (openMenuId && openMenuId !== menuId) {
                const prevDropdown = document.getElementById(openMenuId);
                if (prevDropdown) {
                    prevDropdown.classList.remove('mvno-order-menu-open');
                }
            }
            
            // 현재 메뉴 토글
            if (dropdown.classList.contains('mvno-order-menu-open')) {
                dropdown.classList.remove('mvno-order-menu-open');
                openMenuId = null;
            } else {
                dropdown.classList.add('mvno-order-menu-open');
                openMenuId = menuId;
            }
        });
    });
    
    // 메뉴 외부 클릭 시 닫기
    document.addEventListener('click', function(e) {
        if (openMenuId) {
            const dropdown = document.getElementById(openMenuId);
            const menuBtn = document.querySelector(`.mvno-order-menu-btn[data-plan-id="${openMenuId.replace('mvno-order-menu-', '')}"]`);
            
            if (dropdown && menuBtn && 
                !dropdown.contains(e.target) && 
                !menuBtn.contains(e.target)) {
                dropdown.classList.remove('mvno-order-menu-open');
                openMenuId = null;
            }
        }
    });
    
    // 드롭다운 메뉴 내부의 수정/삭제 버튼 이벤트 (동적으로 추가된 버튼 포함)
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('mvno-order-menu-item')) {
            const planId = e.target.getAttribute('data-plan-id');
            const menuId = 'mvno-order-menu-' + planId;
            const dropdown = document.getElementById(menuId);
            if (dropdown) {
                dropdown.classList.remove('mvno-order-menu-open');
                openMenuId = null;
            }
            if (planId) {
                if (e.target.classList.contains('mvno-order-review-edit-btn')) {
                    reviewManager.openReviewModal(planId);
                } else if (e.target.classList.contains('mvno-order-review-delete-btn')) {
                    reviewManager.openDeleteModal(planId);
                }
            }
        }
    });
}

// order-review.js 스크립트 동적 로드
const orderReviewScript = document.createElement('script');
orderReviewScript.src = '../assets/js/order-review.js';
orderReviewScript.onload = function() {
    console.log('order-review.js 로드 완료');
    initMvnoOrderReview();
};
document.head.appendChild(orderReviewScript);
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>
