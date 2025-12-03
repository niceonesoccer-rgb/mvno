<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = false;

// 통신사폰 데이터 배열
$phones = [
    ['id' => 1, 'provider' => 'SKT', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy Z Fold7', 'device_storage' => '256GB', 'plan_name' => 'SKT 프리미어 슈퍼', 'price' => '월 109,000원', 'maintenance_period' => '185일', 'order_date' => '2024.11.15', 'order_time' => '14:30', 'activation_date' => '2024.11.16', 'activation_time' => '10:00', 'has_review' => false],
    ['id' => 2, 'provider' => 'KT', 'company_name' => '이야기모바일', 'device_name' => 'iPhone 16 Pro', 'device_storage' => '512GB', 'plan_name' => 'KT 슈퍼플랜', 'price' => '월 125,000원', 'maintenance_period' => '180일', 'order_date' => '2024.11.12', 'order_time' => '09:15', 'activation_date' => '2024.11.13', 'activation_time' => '14:30', 'has_review' => true],
    ['id' => 3, 'provider' => 'LG U+', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy S25', 'device_storage' => '256GB', 'plan_name' => 'LG U+ 5G 슈퍼플랜', 'price' => '월 95,000원', 'maintenance_period' => '200일', 'order_date' => '2024.11.10', 'order_time' => '16:45', 'activation_date' => '2024.11.11', 'activation_time' => '09:00', 'has_review' => false],
    ['id' => 4, 'provider' => 'SKT', 'company_name' => '이야기모바일', 'device_name' => 'iPhone 16', 'device_storage' => '128GB', 'plan_name' => 'SKT 스탠다드', 'price' => '월 85,000원', 'maintenance_period' => '150일', 'order_date' => '2024.11.08', 'order_time' => '11:20', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://example.com/consultation'],
    ['id' => 5, 'provider' => 'KT', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy S24 Ultra', 'device_storage' => '512GB', 'plan_name' => 'KT 프리미엄', 'price' => '월 115,000원', 'maintenance_period' => '190일', 'order_date' => '2024.11.05', 'order_time' => '13:50', 'activation_date' => '2024.11.06', 'activation_time' => '16:20', 'has_review' => false],
    ['id' => 6, 'provider' => 'LG U+', 'company_name' => '이야기모바일', 'device_name' => 'iPhone 15 Pro Max', 'device_storage' => '256GB', 'plan_name' => 'LG U+ 5G 플랜', 'price' => '월 105,000원', 'maintenance_period' => '175일', 'order_date' => '2024.11.03', 'order_time' => '10:05', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=6'],
    ['id' => 7, 'provider' => 'SKT', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy Z Flip6', 'device_storage' => '256GB', 'plan_name' => 'SKT 베이직', 'price' => '월 75,000원', 'maintenance_period' => '140일', 'order_date' => '2024.11.01', 'order_time' => '15:30', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=7'],
    ['id' => 8, 'provider' => 'KT', 'company_name' => '이야기모바일', 'device_name' => 'iPhone 15', 'device_storage' => '128GB', 'plan_name' => 'KT 스탠다드', 'price' => '월 80,000원', 'maintenance_period' => '160일', 'order_date' => '2024.10.28', 'order_time' => '12:15', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=8'],
    ['id' => 9, 'provider' => 'LG U+', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy S23', 'device_storage' => '256GB', 'plan_name' => 'LG U+ 5G 베이직', 'price' => '월 70,000원', 'maintenance_period' => '130일', 'order_date' => '2024.10.25', 'order_time' => '14:00', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=9'],
    ['id' => 10, 'provider' => 'SKT', 'company_name' => '이야기모바일', 'device_name' => 'iPhone 14 Pro', 'device_storage' => '256GB', 'plan_name' => 'SKT 프리미엄', 'price' => '월 100,000원', 'maintenance_period' => '170일', 'order_date' => '2024.10.22', 'order_time' => '09:40', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=10'],
    ['id' => 11, 'provider' => 'KT', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy A54', 'device_storage' => '128GB', 'plan_name' => 'KT 베이직', 'price' => '월 65,000원', 'maintenance_period' => '120일', 'order_date' => '2024.10.20', 'order_time' => '16:20', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=11'],
    ['id' => 12, 'provider' => 'LG U+', 'company_name' => '이야기모바일', 'device_name' => 'iPhone 13', 'device_storage' => '128GB', 'plan_name' => 'LG U+ 5G 스탠다드', 'price' => '월 75,000원', 'maintenance_period' => '145일', 'order_date' => '2024.10.18', 'order_time' => '11:55', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=12'],
    ['id' => 13, 'provider' => 'SKT', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy Note20', 'device_storage' => '256GB', 'plan_name' => 'SKT 스탠다드', 'price' => '월 90,000원', 'maintenance_period' => '165일', 'order_date' => '2024.10.15', 'order_time' => '13:25', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=13'],
    ['id' => 14, 'provider' => 'KT', 'company_name' => '이야기모바일', 'device_name' => 'iPhone 12', 'device_storage' => '128GB', 'plan_name' => 'KT 베이직', 'price' => '월 70,000원', 'maintenance_period' => '135일', 'order_date' => '2024.10.12', 'order_time' => '10:30', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=14'],
    ['id' => 15, 'provider' => 'LG U+', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy S22', 'device_storage' => '256GB', 'plan_name' => 'LG U+ 5G 프리미엄', 'price' => '월 95,000원', 'maintenance_period' => '180일', 'order_date' => '2024.10.10', 'order_time' => '15:10', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=15'],
];

// 헤더 포함
include '../includes/header.php';
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
                        <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 통신사폰</h2>
                    </div>
                </div>

                <!-- 신청한 통신사폰 목록 -->
                <div style="margin-bottom: 32px;" id="phonesContainer">
                    <div class="mno-order-list-container">
                <?php foreach ($phones as $index => $phone): ?>
                    <div class="phone-item" data-index="<?php echo $index; ?>" style="<?php echo $index >= 10 ? 'display: none;' : ''; ?> position: relative;">
                        <?php
                        // 통신사폰 데이터 준비
                        $phone_data = [
                            'id' => $phone['id'],
                            'device_name' => $phone['device_name'],
                            'device_storage' => $phone['device_storage'],
                            'release_price' => $phone['release_price'] ?? '2,387,000',
                            'provider' => $phone['provider'],
                            'plan_name' => $phone['plan_name'],
                            'price' => $phone['price'],
                            'maintenance_period' => $phone['maintenance_period'],
                            'company_name' => $phone['company_name'] ?? '이야기모바일',
                            'rating' => '4.5',
                            'order_date' => $phone['order_date'] ?? '',
                            'order_time' => $phone['order_time'] ?? '',
                            'activation_date' => $phone['activation_date'] ?? '',
                            'activation_time' => $phone['activation_time'] ?? '',
                            'has_review' => $phone['has_review'] ?? false,
                            'is_sold_out' => $phone['is_sold_out'] ?? false,
                            'consultation_url' => $phone['consultation_url'] ?? '',
                            'common_support' => $phone['common_support'] ?? [
                                'number_port' => -198,
                                'device_change' => 191.6
                            ],
                            'contract_support' => $phone['contract_support'] ?? [
                                'number_port' => 198,
                                'device_change' => -150
                            ],
                            'additional_supports' => $phone['additional_supports'] ?? ['추가 지원금', '부가 서비스 1', '부가 서비스 2'],
                            'link_url' => '/MVNO/mno/mno-phone-detail.php?id=' . $phone['id']
                        ];
                        // 통신사폰 데이터 설정
                        $phone = $phone_data;
                        $layout_type = 'list';
                        $card_wrapper_class = '';
                        include '../includes/components/mno-order-phone-card.php';
                        ?>
                    </div>
                    <!-- 카드 구분선 (모바일용) -->
                    <hr class="mno-order-card-divider">
                <?php endforeach; ?>
                    </div>
                </div>

                <!-- 더보기 버튼 -->
                <div style="margin-top: 32px; margin-bottom: 32px;" id="moreButtonContainer">
            <button class="plan-review-more-btn" id="morePhonesBtn">
                더보기 (<?php 
                $remaining = count($phones) - 10;
                echo $remaining > 10 ? 10 : $remaining;
                ?>개)
            </button>
        </div>
            </div>
        </div>
    </div>
</main>

<script src="../assets/js/plan-accordion.js" defer></script>
<script src="../assets/js/favorite-heart.js" defer></script>
<script src="../assets/js/share.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const moreBtn = document.getElementById('morePhonesBtn');
    const phoneItems = document.querySelectorAll('.phone-item');
    let visibleCount = 10;
    const totalPhones = phoneItems.length;
    const loadCount = 10; // 한 번에 보여줄 개수

    function updateButtonText() {
        const remaining = totalPhones - visibleCount;
        if (remaining > 0) {
            const showCount = remaining > loadCount ? loadCount : remaining;
            moreBtn.textContent = `더보기 (${showCount}개)`;
        }
    }

    if (moreBtn) {
        updateButtonText();
        
        moreBtn.addEventListener('click', function() {
            // 다음 10개씩 표시
            const endCount = Math.min(visibleCount + loadCount, totalPhones);
            for (let i = visibleCount; i < endCount; i++) {
                if (phoneItems[i]) {
                    phoneItems[i].style.display = 'block';
                }
            }
            
            visibleCount = endCount;
            
            // 모든 항목이 보이면 더보기 버튼 숨기기
            if (visibleCount >= totalPhones) {
                const moreButtonContainer = document.getElementById('moreButtonContainer');
                if (moreButtonContainer) {
                    moreButtonContainer.style.display = 'none';
                }
            } else {
                updateButtonText();
            }
        });
    }

    // 모든 통신사폰이 보이면 더보기 버튼 숨기기
    if (visibleCount >= totalPhones) {
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
    function openReviewModal(phoneId) {
        const modal = document.getElementById('mnoReviewModal');
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
            // 모달에 phoneId 저장
            modal.setAttribute('data-phone-id', phoneId);
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
        const modal = document.getElementById('mnoReviewModal');
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
            const form = document.getElementById('mnoReviewForm');
            if (form) {
                form.reset();
            }
        }
    }

    // 수정 버튼 클릭 이벤트
    const editButtons = document.querySelectorAll('.mno-order-review-edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const phoneId = this.getAttribute('data-phone-id');
            if (phoneId) {
                openReviewModal(phoneId);
                // TODO: 기존 리뷰 데이터를 모달에 로드
            }
        });
    });

    // 삭제 모달 열기 함수
    function openDeleteModal(phoneId, buttonContainer, parentItem) {
        const deleteModal = document.getElementById('mnoReviewDeleteModal');
        if (deleteModal) {
            // 스크롤바 너비 계산
            const scrollbarWidth = getScrollbarWidth();
            
            // body 스크롤 방지
            document.body.style.overflow = 'hidden';
            document.body.style.position = 'fixed';
            document.body.style.top = `-${window.pageYOffset || document.documentElement.scrollTop}px`;
            document.body.style.width = '100%';
            document.body.style.paddingRight = `${scrollbarWidth}px`;
            document.documentElement.style.overflow = 'hidden';
            
            deleteModal.style.display = 'flex';
            deleteModal.setAttribute('data-phone-id', phoneId);
        }
    }

    // 삭제 모달 닫기 함수
    function closeDeleteModal() {
        const deleteModal = document.getElementById('mnoReviewDeleteModal');
        if (deleteModal) {
            deleteModal.style.display = 'none';
            
            // body 스크롤 복원
            const scrollTop = parseInt(document.body.style.top || '0') * -1;
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.body.style.paddingRight = '';
            document.documentElement.style.overflow = '';
            window.scrollTo(0, scrollTop);
        }
    }

    // 삭제 확인 함수
    function confirmDeleteReview(phoneId) {
        // TODO: 서버로 삭제 요청
        console.log('리뷰 삭제 - Phone ID:', phoneId);
        showReviewToast('리뷰가 삭제되었습니다.');
        closeDeleteModal();
        
        // 버튼을 리뷰 쓰기 버튼으로 복원
        const deleteBtn = document.querySelector(`.mno-order-review-delete-btn[data-phone-id="${phoneId}"]`);
        
        if (deleteBtn) {
            const parentItem = deleteBtn.closest('.mno-order-action-item');
            const buttonContainer = deleteBtn.parentElement;
            
            buttonContainer.remove();
            
            const newReviewBtn = document.createElement('button');
            newReviewBtn.type = 'button';
            newReviewBtn.className = 'mno-order-review-btn';
            newReviewBtn.setAttribute('data-phone-id', phoneId);
            newReviewBtn.textContent = '리뷰쓰기';
            parentItem.appendChild(newReviewBtn);
            
            newReviewBtn.addEventListener('click', function() {
                openReviewModal(phoneId);
            });
        }
    }

    // 삭제 버튼 클릭 이벤트
    const deleteButtons = document.querySelectorAll('.mno-order-review-delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const phoneId = this.getAttribute('data-phone-id');
            if (phoneId) {
                const parentItem = this.closest('.mno-order-action-item');
                const buttonContainer = this.parentElement;
                openDeleteModal(phoneId, buttonContainer, parentItem);
            }
        });
    });

    // 삭제 모달 이벤트
    const deleteModal = document.getElementById('mnoReviewDeleteModal');
    if (deleteModal) {
        const closeBtn = deleteModal.querySelector('.mno-review-delete-modal-close');
        const cancelBtn = deleteModal.querySelector('.mno-review-delete-btn-cancel');
        const confirmBtn = deleteModal.querySelector('.mno-review-delete-btn-confirm');
        const overlay = deleteModal.querySelector('.mno-review-delete-modal-overlay');

        if (closeBtn) {
            closeBtn.addEventListener('click', closeDeleteModal);
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeDeleteModal);
        }
        if (overlay) {
            overlay.addEventListener('click', closeDeleteModal);
        }
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                const phoneId = deleteModal.getAttribute('data-phone-id');
                if (phoneId) {
                    confirmDeleteReview(phoneId);
                }
            });
        }

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && deleteModal.style.display === 'flex') {
                closeDeleteModal();
            }
        });
    }

    // 리뷰쓰기 버튼 클릭 이벤트
    const reviewButtons = document.querySelectorAll('.mno-order-review-btn');
    
    reviewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const phoneId = this.getAttribute('data-phone-id');
            if (phoneId && !this.disabled) {
                openReviewModal(phoneId);
            }
        });
    });

    // 모달 닫기 이벤트
    const reviewModal = document.getElementById('mnoReviewModal');
    if (reviewModal) {
        const closeBtn = reviewModal.querySelector('.mno-review-modal-close');
        const cancelBtn = reviewModal.querySelector('.mno-review-btn-cancel');
        const overlay = reviewModal.querySelector('.mno-review-modal-overlay');

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
    const reviewForm = document.getElementById('mnoReviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const modal = document.getElementById('mnoReviewModal');
            const phoneId = modal ? modal.getAttribute('data-phone-id') : null;
            const reviewText = document.getElementById('reviewText').value.trim();
            const kindnessRatingInput = document.querySelector('#mnoReviewForm input[name="kindness_rating"]:checked');
            const speedRatingInput = document.querySelector('#mnoReviewForm input[name="speed_rating"]:checked');
            const kindnessRating = kindnessRatingInput ? parseInt(kindnessRatingInput.value) : null;
            const speedRating = speedRatingInput ? parseInt(speedRatingInput.value) : null;

            if (!kindnessRating) {
                showReviewToast('친절해요 별점을 선택해주세요.');
                return;
            }

            if (!speedRating) {
                showReviewToast('개통 빨라요 별점을 선택해주세요.');
                return;
            }

            if (!reviewText) {
                showReviewToast('리뷰 내용을 입력해주세요.');
                return;
            }

            if (!phoneId) {
                showReviewToast('오류가 발생했습니다. 다시 시도해주세요.');
                return;
            }

            // TODO: 서버로 리뷰 데이터 전송
            console.log('리뷰 작성 - Phone ID:', phoneId, 'Kindness Rating:', kindnessRating, 'Speed Rating:', speedRating, 'Review:', reviewText);
            
            // 예시: AJAX로 서버에 전송
            // fetch('/MVNO/api/review.php', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify({ phone_id: phoneId, review: reviewText })
            // })
            // .then(response => response.json())
            // .then(data => {
            //     if (data.success) {
            //         alert('리뷰가 작성되었습니다.');
            //         closeReviewModal();
            //         // 페이지 새로고침 또는 리뷰 상태 업데이트
            //     } else {
            //         alert('리뷰 작성에 실패했습니다: ' + (data.message || ''));
            //     }
            // })
            // .catch(error => {
            //     console.error('Error:', error);
            //     alert('리뷰 작성 중 오류가 발생했습니다.');
            // });

            // 임시: 성공 메시지 표시 (토스트 메시지)
            showReviewToast('리뷰가 작성되었습니다.');
            closeReviewModal();
            
            // 리뷰 작성 완료 후 버튼을 수정/삭제 버튼으로 변경
            const reviewBtn = document.querySelector(`.mno-order-review-btn[data-phone-id="${phoneId}"]`);
            if (reviewBtn) {
                const parentItem = reviewBtn.closest('.mno-order-action-item');
                if (parentItem) {
                    // 기존 리뷰 쓰기 버튼 제거
                    reviewBtn.remove();
                    
                    // 수정/삭제 버튼 컨테이너 생성
                    const buttonContainer = document.createElement('div');
                    buttonContainer.style.cssText = 'display: flex; gap: 8px; width: 100%;';
                    
                    // 수정 버튼 생성
                    const editBtn = document.createElement('button');
                    editBtn.type = 'button';
                    editBtn.className = 'mno-order-review-edit-btn';
                    editBtn.setAttribute('data-phone-id', phoneId);
                    editBtn.textContent = '수정';
                    editBtn.style.cssText = 'flex: 1; padding: 10px 16px; background: #6366f1; border-radius: 8px; border: none; color: white; font-size: 14px; font-weight: 500; cursor: pointer;';
                    
                    // 삭제 버튼 생성
                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'mno-order-review-delete-btn';
                    deleteBtn.setAttribute('data-phone-id', phoneId);
                    deleteBtn.textContent = '삭제';
                    deleteBtn.style.cssText = 'flex: 1; padding: 10px 16px; background: #ef4444; border-radius: 8px; border: none; color: white; font-size: 14px; font-weight: 500; cursor: pointer;';
                    
                    buttonContainer.appendChild(editBtn);
                    buttonContainer.appendChild(deleteBtn);
                    parentItem.appendChild(buttonContainer);
                    
                    // 수정 버튼 이벤트 추가
                    editBtn.addEventListener('click', function() {
                        openReviewModal(phoneId);
                    });
                    
                    // 삭제 버튼 이벤트 추가
                    deleteBtn.addEventListener('click', function() {
                        openDeleteModal(phoneId, buttonContainer, parentItem);
                    });
                }
            }
        });
    }

    // 리뷰 작성 완료 토스트 메시지 표시 함수 (화면 중앙)
    function showReviewToast(message) {
        // 기존 토스트가 있으면 제거
        const existingToast = document.querySelector('.mno-review-toast');
        if (existingToast) {
            existingToast.remove();
        }

        // 토스트 메시지 생성
        const toast = document.createElement('div');
        toast.className = 'mno-review-toast';
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
            toast.classList.add('mno-review-toast-visible');
        }, 10);

        // 0.7초 후 자동 제거
        setTimeout(() => {
            toast.classList.remove('mno-review-toast-visible');
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
        const existingToast = document.querySelector('.mno-order-toast');
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
        toast.className = 'mno-order-toast';
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
            toast.classList.add('mno-order-toast-visible');
        }, 10);

        // 0.7초 후 자동 제거
        setTimeout(() => {
            toast.classList.remove('mno-order-toast-visible');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300); // 애니메이션 시간
        }, 700);
    }

    // 공유 버튼 클릭 이벤트 (URL 복사)
    const shareButtons = document.querySelectorAll('.mno-order-share-btn-inline');
    
    shareButtons.forEach(button => {
        button.addEventListener('click', function(e) {
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
include '../includes/components/mno-review-modal.php';
// 리뷰 삭제 확인 모달 포함
include '../includes/components/mno-review-delete-modal.php';
?>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

