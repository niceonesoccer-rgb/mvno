<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = false;

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

<script src="../assets/js/plan-accordion.js" defer></script>
<script src="../assets/js/share.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const moreBtn = document.getElementById('morePlansBtn');
    const planItems = document.querySelectorAll('.plan-item');
    let visibleCount = 10;
    const totalPlans = planItems.length;
    const loadCount = 10; // 한 번에 보여줄 개수

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
            // 다음 10개씩 표시
            const endCount = Math.min(visibleCount + loadCount, totalPlans);
            for (let i = visibleCount; i < endCount; i++) {
                if (planItems[i]) {
                    planItems[i].style.display = 'block';
                }
            }
            
            visibleCount = endCount;
            
            // 모든 항목이 보이면 더보기 버튼 숨기기
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

    // 모든 요금제가 보이면 더보기 버튼 숨기기
    if (visibleCount >= totalPlans) {
        const moreButtonContainer = document.getElementById('moreButtonContainer');
        if (moreButtonContainer) {
            moreButtonContainer.style.display = 'none';
        }
    }

    // 스크롤 위치 저장 변수
    let reviewModalScrollPosition = 0;

    // 스크롤바 너비 계산 함수
    function getScrollbarWidth() {
        const outer = document.createElement('div');
        outer.style.visibility = 'hidden';
        outer.style.overflow = 'scroll';
        outer.style.msOverflowStyle = 'scrollbar';
        document.body.appendChild(outer);
        
        const inner = document.createElement('div');
        outer.appendChild(inner);
        
        const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
        
        outer.parentNode.removeChild(outer);
        
        return scrollbarWidth;
    }

    // 리뷰 작성 모달 열기
    function openReviewModal(planId) {
        const modal = document.getElementById('mvnoReviewModal');
        if (modal) {
            // 현재 스크롤 위치 저장
            reviewModalScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            
            // 스크롤바 너비 계산
            const scrollbarWidth = getScrollbarWidth();
            
            // body 스크롤 방지 (스크롤바 너비만큼 padding-right 추가하여 레이아웃 이동 방지)
            document.body.style.overflow = 'hidden';
            document.body.style.position = 'fixed';
            document.body.style.top = `-${reviewModalScrollPosition}px`;
            document.body.style.width = '100%';
            document.body.style.paddingRight = `${scrollbarWidth}px`;
            
            // html 요소도 스크롤 방지 (일부 브라우저용)
            document.documentElement.style.overflow = 'hidden';
            
            modal.style.display = 'flex';
            // 모달에 planId 저장
            modal.setAttribute('data-plan-id', planId);
            // 텍스트 영역 포커스
            setTimeout(() => {
                const textarea = document.getElementById('reviewText');
                if (textarea) {
                    textarea.focus();
                }
            }, 100);
        }
    }

    // 리뷰 작성 모달 닫기
    function closeReviewModal() {
        const modal = document.getElementById('mvnoReviewModal');
        if (modal) {
            modal.style.display = 'none';
            
            // body 스크롤 복원
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.body.style.paddingRight = '';
            document.documentElement.style.overflow = '';
            
            // 저장된 스크롤 위치로 복원
            window.scrollTo(0, reviewModalScrollPosition);
            
            // 폼 초기화
            const form = document.getElementById('mvnoReviewForm');
            if (form) {
                form.reset();
            }
        }
    }

    // 리뷰쓰기 버튼 클릭 이벤트
    const reviewButtons = document.querySelectorAll('.mvno-order-review-btn');
    
    reviewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const planId = this.getAttribute('data-plan-id');
            if (planId && !this.disabled) {
                openReviewModal(planId);
            }
        });
    });

    // 모달 닫기 이벤트
    const reviewModal = document.getElementById('mvnoReviewModal');
    if (reviewModal) {
        const closeBtn = reviewModal.querySelector('.mvno-review-modal-close');
        const cancelBtn = reviewModal.querySelector('.mvno-review-btn-cancel');
        const overlay = reviewModal.querySelector('.mvno-review-modal-overlay');

        if (closeBtn) {
            closeBtn.addEventListener('click', closeReviewModal);
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeReviewModal);
        }
        if (overlay) {
            overlay.addEventListener('click', closeReviewModal);
        }

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && reviewModal.style.display === 'flex') {
                closeReviewModal();
            }
        });
    }

    // 리뷰 작성 폼 제출
    const reviewForm = document.getElementById('mvnoReviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const modal = document.getElementById('mvnoReviewModal');
            const planId = modal ? modal.getAttribute('data-plan-id') : null;
            const reviewText = document.getElementById('reviewText').value.trim();

            if (!reviewText) {
                showReviewToast('리뷰 내용을 입력해주세요.');
                return;
            }

            if (!planId) {
                showReviewToast('오류가 발생했습니다. 다시 시도해주세요.');
                return;
            }

            // TODO: 서버로 리뷰 데이터 전송
            console.log('리뷰 작성 - Plan ID:', planId, 'Review:', reviewText);
            
            // 임시: 성공 메시지 표시 (토스트 메시지)
            showReviewToast('리뷰가 작성되었습니다.');
            closeReviewModal();
        });
    }

    // 리뷰 작성 완료 토스트 메시지 표시 함수 (화면 중앙)
    function showReviewToast(message) {
        // 기존 토스트가 있으면 제거
        const existingToast = document.querySelector('.mvno-review-toast');
        if (existingToast) {
            existingToast.remove();
        }

        // 토스트 메시지 생성
        const toast = document.createElement('div');
        toast.className = 'mvno-review-toast';
        toast.textContent = message;
        document.body.appendChild(toast);

        // 화면 정중앙에 위치 설정
        const toastTop = window.innerHeight / 2; // 화면 세로 중앙
        const toastLeft = window.innerWidth / 2; // 화면 가로 중앙

        toast.style.top = toastTop + 'px';
        toast.style.left = toastLeft + 'px';
        toast.style.transform = 'translateX(-50%) translateY(-50%) translateY(10px)';

        // 애니메이션을 위해 약간의 지연 후 visible 클래스 추가
        setTimeout(() => {
            toast.classList.add('mvno-review-toast-visible');
        }, 10);

        // 0.7초 후 자동 제거
        setTimeout(() => {
            toast.classList.remove('mvno-review-toast-visible');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300); // 애니메이션 시간
        }, 700); // 0.7초
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

    // 공유 버튼 클릭 이벤트 (URL 복사)
    const shareButtons = document.querySelectorAll('.mvno-order-share-btn-inline');
    
    shareButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const shareUrl = this.getAttribute('data-share-url');
            if (shareUrl) {
                // 전체 URL 생성
                const fullUrl = window.location.origin + shareUrl;
                
                // 클립보드에 복사
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(fullUrl).then(() => {
                        // 복사 성공 토스트 메시지 (버튼 위치 기준)
                        showToast('링크가 복사되었습니다.', this);
                    }).catch((err) => {
                        console.error('복사 실패:', err);
                        // 폴백: 텍스트 선택 방식
                        fallbackCopyTextToClipboard(fullUrl, this);
                    });
                } else {
                    // 폴백: 텍스트 선택 방식
                    fallbackCopyTextToClipboard(fullUrl, this);
                }
            }
        });
    });

    // 폴백: 클립보드 API를 지원하지 않는 경우
    function fallbackCopyTextToClipboard(text, buttonElement) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showToast('링크가 복사되었습니다.', buttonElement);
            } else {
                showToast('링크 복사에 실패했습니다.', buttonElement);
            }
        } catch (err) {
            console.error('복사 실패:', err);
            showToast('링크 복사에 실패했습니다.', buttonElement);
        }
        
        document.body.removeChild(textArea);
    }
});
</script>

<?php
// 리뷰 작성 모달 포함
include '../includes/components/mvno-review-modal.php';
?>

<?php
// 푸터 포함
include '../includes/footer.php';
?>
