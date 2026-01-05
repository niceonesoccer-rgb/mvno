<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

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
    // 세션 정리 후 로그인 페이지로 리다이렉트
    if (isset($_SESSION['logged_in'])) {
        unset($_SESSION['logged_in']);
    }
    if (isset($_SESSION['user_id'])) {
        unset($_SESSION['user_id']);
    }
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: /MVNO/?show_login=1');
    exit;
}

$user_id = $currentUser['user_id'];

// 포인트 설정 및 함수 포함
require_once '../includes/data/point-settings.php';
require_once '../includes/data/product-functions.php';
require_once '../includes/data/db-config.php';
require_once '../includes/data/plan-data.php';

// 페이지 번호 및 제한 설정
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20; // 초기 로드 개수
$offset = ($page - 1) * $limit;

// DB에서 실제 신청 내역 가져오기 (페이징 적용)
$phones = getUserMnoApplications($user_id, $limit, $offset);
$totalPhonesCount = count(getUserMnoApplications($user_id)); // 전체 개수

$currentCount = count($phones);
$remainingCount = max(0, $totalPhonesCount - ($offset + $currentCount));
$hasMore = ($offset + $currentCount) < $totalPhonesCount;

// 헤더 포함
include '../includes/header.php';
// 리뷰 모달 포함
include '../includes/components/mno-review-modal.php';
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
                    <p style="font-size: 14px; color: #6b7280; margin: 0; margin-left: 36px;">카드를 클릭하면 상품 상세 정보를 확인할 수 있습니다.</p>
                </div>

                <!-- 전체 개수 표시 -->
                <?php if (!empty($phones)): ?>
                <div class="plans-results-count">
                    <span><?php echo number_format($totalPhonesCount); ?>개의 결과</span>
                </div>
                <?php endif; ?>

                <!-- 신청한 통신사폰 목록 -->
                <div style="margin-bottom: 32px;">
                    <?php if (empty($phones)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: #6b7280;">
                            신청한 통신사폰이 없습니다.
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;" id="mno-orders-container">
                            <?php foreach ($phones as $index => $phone): ?>
                                <?php 
                                // 컴포넌트에 필요한 변수 설정
                                $phone = $phone;
                                $user_id = $user_id;
                                include __DIR__ . '/../includes/components/mno-order-card.php'; 
                                ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 더보기 버튼 -->
                <?php if ($hasMore && $totalPhonesCount > 0): ?>
                <div class="load-more-container" id="load-more-anchor">
                    <button id="load-more-mno-order-btn" class="load-more-btn" 
                            data-type="mno" 
                            data-page="2" 
                            data-total="<?php echo $totalPhonesCount; ?>"
                            data-order="true">
                        더보기 (<span id="remaining-count"><?php echo number_format($remainingCount); ?></span>개 남음)
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- 주문 상세 정보 모달 -->
<div id="orderDetailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 10000; overflow: hidden; padding: 20px;">
    <div style="max-width: 800px; margin: 40px auto; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); position: relative;">
        <!-- 모달 헤더 -->
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 24px; font-weight: bold; margin: 0; color: #1f2937;">주문 정보</h2>
            <button id="closeOrderModalBtn" style="background: none; border: none; cursor: pointer; padding: 8px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
        <!-- 모달 내용 -->
        <div id="orderModalContent" style="padding: 24px; max-height: calc(100vh - 200px); overflow-y: auto;">
            <div style="text-align: center; padding: 40px; color: #6b7280;">
                <div class="spinner" style="border: 3px solid #f3f4f6; border-top: 3px solid #6366f1; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
                <p>정보를 불러오는 중...</p>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
function openOrderModal(applicationId) {
    const modal = document.getElementById('orderDetailModal');
    const modalContent = document.getElementById('orderModalContent');
    
    modal.style.display = 'block';
    // 배경 페이지 스크롤 완전 차단 (스크롤바도 숨김)
    document.body.style.overflow = 'hidden';
    document.body.style.paddingRight = '0px';
    // html 요소도 스크롤 차단
    document.documentElement.style.overflow = 'hidden';
    
    // 로딩 표시
    modalContent.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #6b7280;">
            <div class="spinner" style="border: 3px solid #f3f4f6; border-top: 3px solid #6366f1; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
            <p>정보를 불러오는 중...</p>
        </div>
    `;
    
    // API 호출
    fetch(`/MVNO/api/get-application-details.php?application_id=${applicationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOrderDetails(data.data);
            } else {
                modalContent.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc2626;">
                        <p>정보를 불러오는 중 오류가 발생했습니다.</p>
                        <p style="font-size: 14px; margin-top: 8px;">${data.message || '알 수 없는 오류'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalContent.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #dc2626;">
                    <p>정보를 불러오는 중 오류가 발생했습니다.</p>
                </div>
            `;
        });
}

function closeOrderModal() {
    const modal = document.getElementById('orderDetailModal');
    modal.style.display = 'none';
    // 배경 페이지 스크롤 복원
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    document.documentElement.style.overflow = '';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNumber(num) {
    if (!num) return '0';
    return parseFloat(num).toLocaleString('ko-KR');
}

function displayOrderDetails(data) {
    const modalContent = document.getElementById('orderModalContent');
    const customer = data.customer || {};
    const additionalInfo = data.additional_info || {};
    const productSnapshot = additionalInfo.product_snapshot || {};
    
    let html = '<div style="display: flex; flex-direction: column; gap: 24px;">';
    
    // 신청 정보 섹션 (위로 이동)
    html += '<div>';
    html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">신청 정보</h3>';
    html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
    
    // 주문번호 (필수 표시)
    const orderNumber = data.order_number || (data.application_id ? '#' + data.application_id : '');
    if (orderNumber) {
        html += `<div style="color: #6b7280; font-weight: 500;">주문번호:</div>`;
        html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(orderNumber)}</div>`;
    }
    
    if (customer.name) {
        html += `<div style="color: #6b7280; font-weight: 500;">이름:</div>`;
        html += `<div style="color: #1f2937;">${escapeHtml(customer.name)}</div>`;
    }
    
    if (customer.phone) {
        html += `<div style="color: #6b7280; font-weight: 500;">전화번호:</div>`;
        html += `<div style="color: #1f2937;">${escapeHtml(customer.phone)}</div>`;
    }
    
    if (customer.email) {
        html += `<div style="color: #6b7280; font-weight: 500;">이메일:</div>`;
        html += `<div style="color: #1f2937;">${escapeHtml(customer.email)}</div>`;
    }
    
    html += '</div></div>';
    
    // 주문 정보 섹션
    html += '<div>';
    html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">주문 정보</h3>';
    html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
    
    if (productSnapshot.device_name) {
        html += `<div style="color: #6b7280; font-weight: 500;">단말기명:</div>`;
        html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(productSnapshot.device_name)}</div>`;
    }
    
    if (productSnapshot.device_price) {
        html += `<div style="color: #6b7280; font-weight: 500;">단말기 출고가:</div>`;
        html += `<div style="color: #1f2937; font-weight: 600;">${formatNumber(productSnapshot.device_price)}원</div>`;
    }
    
    if (productSnapshot.device_capacity) {
        html += `<div style="color: #6b7280; font-weight: 500;">용량:</div>`;
        html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.device_capacity)}</div>`;
    }
    
    if (additionalInfo.device_colors && additionalInfo.device_colors.length > 0) {
        html += `<div style="color: #6b7280; font-weight: 500;">선택한 색상:</div>`;
        html += `<div style="color: #1f2937;">${escapeHtml(additionalInfo.device_colors[0])}</div>`;
    }
    
    if (additionalInfo.carrier) {
        html += `<div style="color: #6b7280; font-weight: 500;">통신사:</div>`;
        html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(additionalInfo.carrier)}</div>`;
    }
    
    if (additionalInfo.subscription_type) {
        html += `<div style="color: #6b7280; font-weight: 500;">가입형태:</div>`;
        // 고객용 표시: 신규가입, 번호이동, 기기변경
        let subscriptionTypeText = additionalInfo.subscription_type;
        const subscriptionTypeMap = {
            'new': '신규가입',
            'mnp': '번호이동',
            'port': '번호이동', // 하위 호환성
            'change': '기기변경'
        };
        if (subscriptionTypeMap[subscriptionTypeText]) {
            subscriptionTypeText = subscriptionTypeMap[subscriptionTypeText];
        }
        html += `<div style="color: #1f2937;">${escapeHtml(subscriptionTypeText)}</div>`;
    }
    
    if (additionalInfo.discount_type) {
        html += `<div style="color: #6b7280; font-weight: 500;">할인방법:</div>`;
        html += `<div style="color: #1f2937;">${escapeHtml(additionalInfo.discount_type)}</div>`;
    }
    
    if (additionalInfo.price !== undefined && additionalInfo.price !== null && additionalInfo.price !== '') {
        html += `<div style="color: #6b7280; font-weight: 500;">가격:</div>`;
        html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(additionalInfo.price)}</div>`;
    }
    
    // 프로모션 정보 (가격 밑에 표시)
    if (productSnapshot.promotion_title || productSnapshot.promotions) {
        html += `<div style="color: #6b7280; font-weight: 500;">프로모션:</div>`;
        html += `<div style="color: #1f2937;">`;
        
        let promotionText = '';
        
        // 제목
        if (productSnapshot.promotion_title) {
            promotionText = escapeHtml(productSnapshot.promotion_title);
        }
        
        // 항목들
        let promotions = [];
        try {
            if (typeof productSnapshot.promotions === 'string') {
                promotions = JSON.parse(productSnapshot.promotions);
            } else if (Array.isArray(productSnapshot.promotions)) {
                promotions = productSnapshot.promotions;
            }
        } catch(e) {
            // JSON 파싱 실패 시 무시
        }
        
        // 빈 문자열 제거
        promotions = promotions.filter(p => p && p.trim() !== '');
        
        if (promotions.length > 0) {
            const promotionItems = promotions.map(p => escapeHtml(p)).join(', ');
            if (promotionText) {
                promotionText += `(${promotionItems})`;
            } else {
                promotionText = `(${promotionItems})`;
            }
        }
        
        if (promotionText) {
            html += `<div>${promotionText}</div>`;
        }
        
        html += `</div>`;
    }
    
    if (productSnapshot.delivery_method) {
        let deliveryText = productSnapshot.delivery_method === 'visit' ? '내방' : '배송';
        if (productSnapshot.visit_region) {
            deliveryText += `(${productSnapshot.visit_region})`;
        }
        html += `<div style="color: #6b7280; font-weight: 500;">단말기 수령방법:</div>`;
        html += `<div style="color: #1f2937;">${escapeHtml(deliveryText)}</div>`;
    }
    
    html += '</div></div>';
    
    // 판매자 상담 정보 섹션 추가
    if (data.seller_consultation) {
        const consultation = data.seller_consultation;
        const hasConsultation = consultation.phone || consultation.mobile;
        
        if (hasConsultation) {
            html += '<div style="border-top: 2px solid #e5e7eb; padding-top: 24px; margin-top: 24px;">';
            html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">상담하기</h3>';
            html += '<div style="display: flex; flex-direction: column; gap: 12px;">';
            
            const telNumber = consultation.mobile ? consultation.mobile.replace(/[^0-9]/g, '') : (consultation.phone ? consultation.phone.replace(/[^0-9]/g, '') : '');
            const telDisplay = consultation.mobile || consultation.phone;
            if (telNumber) {
                html += `<a href="tel:${telNumber}" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; background: #10b981; color: white; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    전화 상담: ${escapeHtml(telDisplay)}
                </a>`;
            }
            
            html += '</div></div>';
        }
    }
    
    html += '</div>';
    
    modalContent.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('orderDetailModal');
    const closeBtn = document.getElementById('closeOrderModalBtn');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeOrderModal);
    }
    
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeOrderModal();
            }
        });
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'block') {
            closeOrderModal();
        }
    });
    
    // 새로 로드된 카드에 대한 클릭 이벤트를 다시 바인딩하는 함수
    function initApplicationCardClickEvents() {
        const applicationCards = document.querySelectorAll('.application-card');
        applicationCards.forEach(card => {
            // 기존 이벤트 리스너가 중복 등록되지 않도록 확인
            if (!card.dataset.eventListenerAdded) {
                card.addEventListener('click', function(e) {
                    const applicationId = this.getAttribute('data-application-id');
                    if (applicationId) {
                        openOrderModal(applicationId);
                    }
                });
                card.dataset.eventListenerAdded = 'true'; // 플래그 설정
            }
        });
    }
    
    // MNO 리뷰 작성/수정 기능
    const reviewWriteButtons = document.querySelectorAll('.mno-review-write-btn, .mno-review-edit-btn');
    const reviewModal = document.getElementById('mnoReviewModal');
    const reviewForm = document.getElementById('mnoReviewForm');
    const reviewModalClose = reviewModal ? reviewModal.querySelector('.mno-review-modal-close') : null;
    const reviewModalOverlay = reviewModal ? reviewModal.querySelector('.mno-review-modal-overlay') : null;
    const reviewCancelBtn = reviewForm ? reviewForm.querySelector('.mno-review-btn-cancel') : null;
    
    let currentReviewApplicationId = null;
    let currentReviewProductId = null;
    let currentReviewId = null;
    let isEditMode = false;
    
    // 리뷰 작성/수정 버튼 클릭 이벤트
    reviewWriteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); // 카드 클릭 이벤트 방지
            currentReviewApplicationId = this.getAttribute('data-application-id');
            currentReviewProductId = this.getAttribute('data-product-id');
            const hasReview = this.getAttribute('data-has-review') === '1';
            isEditMode = hasReview;
            currentReviewId = null;
            
            if (reviewModal) {
                // 먼저 모달 제목과 버튼 텍스트를 설정
                const modalTitle = reviewModal.querySelector('.mno-review-modal-title');
                if (modalTitle) {
                    modalTitle.textContent = isEditMode ? '리뷰 수정' : '리뷰 작성';
                }
                
                                // 제출 버튼 텍스트 변경
                const submitBtn = reviewForm ? reviewForm.querySelector('.mno-review-btn-submit') : null;
                if (submitBtn) {
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                }
                
                // 삭제 버튼 표시/숨김
                const deleteBtn = document.getElementById('mnoReviewDeleteBtn');
                if (deleteBtn) {
                    deleteBtn.style.display = isEditMode ? 'flex' : 'none';
                }
                
                // 현재 스크롤 위치 저장
                const scrollY = window.scrollY;
                document.body.style.position = 'fixed';
                document.body.style.top = `-${scrollY}px`;
                document.body.style.width = '100%';
                document.body.style.overflow = 'hidden';
                
                // 폼 초기화
                if (reviewForm) {
                    reviewForm.reset();
                    // 별점 초기화
                    const starLabels = reviewForm.querySelectorAll('.star-label');
                    starLabels.forEach(label => {
                        label.classList.remove('active');
                        label.classList.remove('hover-active');
                    });
                }
                
                // 수정 모드일 경우 기존 리뷰 데이터 로드
                if (isEditMode && currentReviewApplicationId && currentReviewProductId) {
                    fetch(`/MVNO/api/get-review-by-application.php?application_id=${currentReviewApplicationId}&product_id=${currentReviewProductId}&product_type=mno`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.review) {
                                currentReviewId = data.review.id;
                                
                                // 별점 설정
                                if (data.review.kindness_rating) {
                                    const kindnessInput = reviewForm.querySelector(`input[name="kindness_rating"][value="${data.review.kindness_rating}"]`);
                                    if (kindnessInput) {
                                        kindnessInput.checked = true;
                                        const rating = parseInt(data.review.kindness_rating);
                                        const kindnessLabels = reviewForm.querySelectorAll('.mno-star-rating[data-rating-type="kindness"] .star-label');
                                        kindnessLabels.forEach((label, index) => {
                                            if (index < rating) {
                                                label.classList.add('active');
                                            } else {
                                                label.classList.remove('active');
                                            }
                                        });
                                    }
                                }
                                if (data.review.speed_rating) {
                                    const speedInput = reviewForm.querySelector(`input[name="speed_rating"][value="${data.review.speed_rating}"]`);
                                    if (speedInput) {
                                        speedInput.checked = true;
                                        const rating = parseInt(data.review.speed_rating);
                                        const speedLabels = reviewForm.querySelectorAll('.mno-star-rating[data-rating-type="speed"] .star-label');
                                        speedLabels.forEach((label, index) => {
                                            if (index < rating) {
                                                label.classList.add('active');
                                            } else {
                                                label.classList.remove('active');
                                            }
                                        });
                                    }
                                }
                                // 리뷰 내용 설정
                                const reviewTextarea = reviewForm.querySelector('#mnoReviewText');
                                if (reviewTextarea && data.review.content) {
                                    reviewTextarea.value = data.review.content;
                                    // 텍스트 카운터 업데이트
                                    const counter = document.getElementById('mnoReviewTextCounter');
                                    if (counter) {
                                        counter.textContent = data.review.content.length;
                                    }
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error loading review:', error);
                        });
                }
                
                // 모달 표시 (애니메이션)
                reviewModal.style.display = 'flex';
                setTimeout(() => {
                    reviewModal.classList.add('show');
                }, 10);
            }
        });
    });
    
    // 모달 닫기 함수
    function closeReviewModal() {
        if (reviewModal) {
            reviewModal.classList.remove('show');
            setTimeout(() => {
                const scrollY = document.body.style.top;
                reviewModal.style.display = 'none';
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.width = '';
                document.body.style.overflow = '';
                if (scrollY) {
                    window.scrollTo(0, parseInt(scrollY || '0') * -1);
                }
                
                // 폼 초기화
                if (reviewForm) {
                    reviewForm.reset();
                    // 별점 초기화
                    const starLabels = reviewForm.querySelectorAll('.star-label');
                    starLabels.forEach(label => {
                        label.classList.remove('active');
                        label.classList.remove('hover-active');
                    });
                }
                const reviewTextCounter = document.getElementById('mnoReviewTextCounter');
                if (reviewTextCounter) {
                    reviewTextCounter.textContent = '0';
                }
            }, 300);
        }
    }
    
    // 모달 닫기 이벤트
    if (reviewModalClose) {
        reviewModalClose.addEventListener('click', closeReviewModal);
    }
    
    if (reviewModalOverlay) {
        reviewModalOverlay.addEventListener('click', closeReviewModal);
    }
    
    if (reviewCancelBtn) {
        reviewCancelBtn.addEventListener('click', closeReviewModal);
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && reviewModal && reviewModal.classList.contains('show')) {
            closeReviewModal();
        }
    });
    
    // 리뷰 폼 제출
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const kindnessRatingInput = reviewForm.querySelector('input[name="kindness_rating"]:checked');
            const speedRatingInput = reviewForm.querySelector('input[name="speed_rating"]:checked');
            const reviewText = reviewForm.querySelector('#mnoReviewText').value.trim();
            
            if (!kindnessRatingInput) {
                showAlert('친절해요 별점을 선택해주세요.', '알림');
                return;
            }
            
            if (!speedRatingInput) {
                showAlert('개통 빨라요 별점을 선택해주세요.', '알림');
                return;
            }
            
            if (!reviewText) {
                showAlert('리뷰 내용을 입력해주세요.', '알림');
                return;
            }
            
            const formData = new FormData();
            formData.append('product_id', currentReviewProductId);
            formData.append('product_type', 'mno');
            formData.append('kindness_rating', kindnessRatingInput.value);
            formData.append('speed_rating', speedRatingInput.value);
            formData.append('content', reviewText);
            formData.append('application_id', currentReviewApplicationId);
            
            if (isEditMode && currentReviewId) {
                formData.append('review_id', currentReviewId);
            }
            
            // 제출 버튼 비활성화
            const submitBtn = reviewForm.querySelector('.mno-review-btn-submit');
            const submitBtnSpan = submitBtn ? submitBtn.querySelector('span') : null;
            if (submitBtn) {
                submitBtn.disabled = true;
                if (submitBtnSpan) {
                    submitBtnSpan.textContent = '처리 중...';
                } else {
                    submitBtn.textContent = '처리 중...';
                }
            }
            
            fetch('/MVNO/api/submit-review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('리뷰가 ' + (isEditMode ? '수정' : '작성') + '되었습니다.', '알림').then(() => {
                        closeReviewModal();
                        location.reload(); // 페이지 새로고침하여 리뷰 버튼 상태 업데이트
                    });
                } else {
                    showAlert(data.message || '리뷰 작성에 실패했습니다.', '오류').then(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            if (submitBtnSpan) {
                                submitBtnSpan.textContent = isEditMode ? '저장하기' : '작성하기';
                            } else {
                                submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('리뷰 작성 중 오류가 발생했습니다.', '오류').then(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        if (submitBtnSpan) {
                            submitBtnSpan.textContent = isEditMode ? '저장하기' : '작성하기';
                        } else {
                            submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                        }
                    }
                });
            });
        });
    }
    
    // MNO 리뷰 삭제 버튼 클릭 이벤트
    const deleteReviewBtn = document.getElementById('mnoReviewDeleteBtn');
    if (deleteReviewBtn) {
        deleteReviewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const reviewId = this.getAttribute('data-review-id') || currentReviewId;
            if (!reviewId) {
                alert('리뷰 정보를 찾을 수 없습니다.');
                return;
            }
            
            if (!confirm('정말로 리뷰를 삭제하시겠습니까?\n삭제된 리뷰는 복구할 수 없습니다.')) {
                return;
            }
            
            // 삭제 버튼 비활성화
            this.disabled = true;
            const originalText = this.querySelector('span').textContent;
            this.querySelector('span').textContent = '삭제 중...';
            
            const formData = new FormData();
            formData.append('review_id', reviewId);
            formData.append('product_type', 'mno');
            
            fetch('/MVNO/api/delete-review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('리뷰가 삭제되었습니다.');
                    closeReviewModal();
                    location.reload();
                } else {
                    alert(data.message || '리뷰 삭제에 실패했습니다.');
                    this.disabled = false;
                    this.querySelector('span').textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('리뷰 삭제 중 오류가 발생했습니다.');
                this.disabled = false;
                this.querySelector('span').textContent = originalText;
            });
        });
    }
});
</script>

<!-- 전화번호 반응형 스타일 및 리뷰 삭제 버튼 스타일 -->
<style>
@media (max-width: 768px) {
    .phone-inquiry-pc {
        display: none !important;
    }
    .phone-inquiry-mobile {
        display: flex !important;
    }
}
@media (min-width: 769px) {
    .phone-inquiry-pc {
        display: block !important;
    }
    .phone-inquiry-mobile {
        display: none !important;
    }
}

/* MNO 리뷰 삭제 버튼 스타일 */
.mno-review-btn-delete {
    background: #fee2e2;
    color: #dc2626;
    padding: 16px 20px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
}

.mno-review-btn-delete:hover {
    background: #fecaca;
    color: #b91c1c;
    transform: translateY(-1px);
}

.mno-review-btn-delete:active {
    transform: translateY(0);
}

/* 더보기 버튼 스타일 */
.load-more-container {
    margin-top: 24px;
    margin-bottom: 32px;
    width: 100%;
}

.load-more-btn {
    width: 100%;
    padding: 14px 24px;
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
}

.load-more-btn:hover:not(:disabled) {
    background: #4f46e5;
    box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
    transform: translateY(-1px);
}

.load-more-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    opacity: 0.7;
}
</style>

<script src="/MVNO/assets/js/load-more-products.js?v=2"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 초기 페이지 로드 시 이벤트 바인딩
    initApplicationCardClickEvents();
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

