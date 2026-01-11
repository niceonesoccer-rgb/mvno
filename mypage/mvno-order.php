<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 경로 설정 파일 먼저 로드
require_once '../includes/data/path-config.php';

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';

// 로그인 체크 - 로그인하지 않은 경우 회원가입 모달로 리다이렉트
if (!isLoggedIn()) {
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    // 로그인 모달이 있는 홈으로 리다이렉트 (모달 자동 열기)
    header('Location: ' . getAssetPath('/?show_login=1'));
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
    header('Location: ' . getAssetPath('/?show_login=1'));
    exit;
}

$user_id = $currentUser['user_id'];

// 필요한 함수 포함
require_once '../includes/data/product-functions.php';
require_once '../includes/data/db-config.php';
require_once '../includes/data/contract-type-functions.php';
require_once '../includes/data/plan-data.php';

// 페이지 번호 및 제한 설정
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20; // 초기 로드 개수
$offset = ($page - 1) * $limit;

// DB에서 실제 신청 내역 가져오기 (페이징 적용)
$applications = getUserMvnoApplications($user_id, $limit, $offset);
$totalCount = count(getUserMvnoApplications($user_id)); // 전체 개수

$currentCount = count($applications);
$remainingCount = max(0, $totalCount - ($offset + $currentCount));
$hasMore = ($offset + $currentCount) < $totalCount;

// 헤더 포함
include '../includes/header.php';
// 리뷰 모달 포함
include '../includes/components/mvno-review-modal.php';
?>

<main class="main-content">
    <div class="content-layout">
        <div class="plans-main-layout">
            <div class="plans-left-section">
                <!-- 페이지 헤더 -->
                <div style="margin-bottom: 24px; padding: 20px 0;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <a href="<?php echo getAssetPath('/mypage/mypage.php'); ?>" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 알뜰폰</h2>
                    </div>
                    <p style="font-size: 14px; color: #6b7280; margin: 0; margin-left: 36px;">카드를 클릭하면 신청 정보를 확인할 수 있습니다.</p>
                </div>
                
                <!-- 전체 개수 표시 -->
                <?php if (!empty($applications)): ?>
                <div class="plans-results-count">
                    <span><?php echo number_format($totalCount); ?>개의 결과</span>
                </div>
                <?php endif; ?>
                
                <!-- 신청한 알뜰폰 목록 -->
                <div style="margin-bottom: 32px;">
                    <?php if (empty($applications)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: #6b7280;">
                            신청한 알뜰폰이 없습니다.
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;" id="mvno-orders-container">
                            <?php foreach ($applications as $index => $app): ?>
                                <?php include __DIR__ . '/../includes/components/mvno-order-card.php'; ?>
                            <?php endforeach; ?>
                        
                        <!-- 더보기 버튼 -->
                            <?php if ($hasMore && $totalCount > 0): ?>
                            <div class="load-more-container" id="load-more-anchor">
                                <button id="load-more-mvno-order-btn" class="load-more-btn" 
                                        data-type="mvno" 
                                        data-page="2" 
                                        data-total="<?php echo $totalCount; ?>"
                                        data-order="true">
                                    더보기 (<span id="remaining-count"><?php echo number_format($remainingCount); ?></span>개 남음)
                            </button>
                        </div>
                        <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- 신청 상세 정보 모달 -->
<div id="applicationDetailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 10000; overflow: hidden; padding: 20px;">
    <div style="max-width: 800px; margin: 40px auto; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); position: relative;">
        <!-- 모달 헤더 -->
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 24px; font-weight: bold; margin: 0; color: #1f2937;">등록정보</h2>
            <button id="closeModalBtn" style="background: none; border: none; cursor: pointer; padding: 8px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
        <!-- 모달 내용 -->
        <div id="modalContent" style="padding: 24px; max-height: calc(100vh - 200px); overflow-y: auto;">
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

/* MVNO 리뷰 삭제 버튼 스타일 */
.mvno-review-btn-delete {
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

.mvno-review-btn-delete:hover {
    background: #fecaca;
    color: #b91c1c;
    transform: translateY(-1px);
}

.mvno-review-btn-delete:active {
    transform: translateY(0);
}
</style>

<script>
    // BASE_PATH와 API_PATH를 JavaScript에서 사용할 수 있도록 설정
    window.BASE_PATH = window.BASE_PATH || '<?php echo getBasePath(); ?>';
    window.API_PATH = window.API_PATH || (window.BASE_PATH + '/api');
    
document.addEventListener('DOMContentLoaded', function() {
    // MVNO 리뷰 작성/수정 기능
    const reviewWriteButtons = document.querySelectorAll('.mvno-review-write-btn, .mvno-review-edit-btn');
    const reviewModal = document.getElementById('mvnoReviewModal');
    const reviewForm = document.getElementById('mvnoReviewForm');
    const reviewModalClose = reviewModal ? reviewModal.querySelector('.mvno-review-modal-close') : null;
    const reviewModalOverlay = reviewModal ? reviewModal.querySelector('.mvno-review-modal-overlay') : null;
    const reviewCancelBtn = reviewForm ? reviewForm.querySelector('.mvno-review-btn-cancel') : null;
    
    let currentReviewApplicationId = null;
    let currentReviewProductId = null;
    let currentReviewId = null;
    let isEditMode = false;
    
    // 리뷰 작성/수정 버튼 클릭 이벤트 (전역 함수로 정의)
    window.initReviewButtonEvents = function() {
        // 필요한 변수들 다시 가져오기
        const reviewModal = document.getElementById('mvnoReviewModal');
        const reviewForm = document.getElementById('mvnoReviewForm');
        
        const buttons = document.querySelectorAll('.mvno-review-write-btn, .mvno-review-edit-btn');
        buttons.forEach(btn => {
            // 이미 이벤트가 바인딩된 버튼은 스킵
            if (btn.dataset.reviewEventAdded) return;
            btn.dataset.reviewEventAdded = 'true';
            
            btn.addEventListener('click', function(e) {
                e.stopPropagation(); // 카드 클릭 이벤트 방지
                e.preventDefault();
                
                const applicationIdAttr = this.getAttribute('data-application-id');
                const productIdAttr = this.getAttribute('data-product-id');
                const hasReview = this.getAttribute('data-has-review') === '1';
                const reviewIdAttr = this.getAttribute('data-review-id');
                
                if (!productIdAttr || productIdAttr === 'null' || productIdAttr === '') {
                    console.error('리뷰 버튼 클릭 오류: data-product-id 속성이 없거나 올바르지 않습니다.', this);
                    if (typeof showAlert === 'function') {
                        showAlert('상품 정보를 찾을 수 없습니다.', '오류');
                    } else {
                        alert('상품 정보를 찾을 수 없습니다.');
                    }
                    return;
                }
                
                window.currentReviewApplicationId = applicationIdAttr;
                window.currentReviewProductId = productIdAttr;
                window.isEditMode = hasReview && reviewIdAttr !== null;
                window.currentReviewId = reviewIdAttr ? parseInt(reviewIdAttr) : null;
                
                if (reviewModal) {
                    // 변수들 가져오기
                    const isEditMode = window.isEditMode;
                    const currentReviewApplicationId = window.currentReviewApplicationId;
                    const currentReviewProductId = window.currentReviewProductId;
                    
                    // 먼저 모달 제목과 버튼 텍스트를 설정
                    const modalTitle = reviewModal.querySelector('.mvno-review-modal-title');
                    if (modalTitle) {
                        modalTitle.textContent = isEditMode ? '리뷰 수정' : '리뷰 작성';
                    }
                    
                    // 제출 버튼 텍스트 변경
                    const submitBtn = reviewForm ? reviewForm.querySelector('.mvno-review-btn-submit') : null;
                    if (submitBtn) {
                        submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                    }
                    
                    // 삭제 버튼 표시/숨김
                    const deleteBtn = document.getElementById('mvnoReviewDeleteBtn');
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
                        fetch(`${window.API_PATH || (window.BASE_PATH || '') + '/api'}/get-review-by-application.php?application_id=${currentReviewApplicationId}&product_id=${currentReviewProductId}&product_type=mvno`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.review) {
                                    window.currentReviewId = data.review.id;
                                    // 별점 설정
                                    if (data.review.kindness_rating) {
                                        const kindnessInput = reviewForm.querySelector(`input[name="kindness_rating"][value="${data.review.kindness_rating}"]`);
                                        if (kindnessInput) {
                                            kindnessInput.checked = true;
                                            const rating = parseInt(data.review.kindness_rating);
                                            const kindnessLabels = reviewForm.querySelectorAll('.mvno-star-rating[data-rating-type="kindness"] .star-label');
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
                                            const speedLabels = reviewForm.querySelectorAll('.mvno-star-rating[data-rating-type="speed"] .star-label');
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
                                    const reviewTextarea = reviewForm.querySelector('#mvnoReviewText');
                                    if (reviewTextarea && data.review.content) {
                                        reviewTextarea.value = data.review.content;
                                        // 텍스트 카운터 업데이트
                                        const counter = document.getElementById('mvnoReviewTextCounter');
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
                    
                    // 모달 표시
                    reviewModal.style.display = 'block';
                }
            });
        });
    };
    
    // 초기 리뷰 버튼 이벤트 바인딩
    window.initReviewButtonEvents();
    
    // 모달 닫기 함수
    function closeReviewModal() {
        if (reviewModal) {
            const scrollY = document.body.style.top;
            reviewModal.style.display = 'none';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.body.style.overflow = '';
            if (scrollY) {
                window.scrollTo(0, parseInt(scrollY || '0') * -1);
            }
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
        if (e.key === 'Escape' && reviewModal && reviewModal.style.display === 'block') {
            closeReviewModal();
        }
    });
    
    // 리뷰 폼 제출
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const kindnessRatingInput = reviewForm.querySelector('input[name="kindness_rating"]:checked');
            const speedRatingInput = reviewForm.querySelector('input[name="speed_rating"]:checked');
            const reviewText = reviewForm.querySelector('#mvnoReviewText').value.trim();
            
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
            
            // 전역 변수 확인
            if (!window.currentReviewProductId) {
                showAlert('상품 정보를 찾을 수 없습니다.', '오류');
                return;
            }
            
            const formData = new FormData();
            formData.append('product_id', window.currentReviewProductId);
            formData.append('product_type', 'mvno');
            formData.append('kindness_rating', kindnessRatingInput.value);
            formData.append('speed_rating', speedRatingInput.value);
            formData.append('content', reviewText);
            formData.append('application_id', window.currentReviewApplicationId);
            
            if (window.isEditMode && window.currentReviewId) {
                formData.append('review_id', window.currentReviewId);
            }
            
            // 제출 버튼 비활성화
            const submitBtn = reviewForm.querySelector('.mvno-review-btn-submit');
            const submitBtnSpan = submitBtn ? submitBtn.querySelector('span') : null;
            if (submitBtn) {
                submitBtn.disabled = true;
                if (submitBtnSpan) {
                    submitBtnSpan.textContent = '처리 중...';
                } else {
                    submitBtn.textContent = '처리 중...';
                }
            }
            
            fetch((window.API_PATH || (window.BASE_PATH || '') + '/api') + '/submit-review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('리뷰가 ' + (window.isEditMode ? '수정' : '작성') + '되었습니다.', '알림').then(() => {
                        closeReviewModal();
                        location.reload(); // 페이지 새로고침하여 리뷰 버튼 상태 업데이트
                    });
                } else {
                    showAlert(data.message || '리뷰 작성에 실패했습니다.', '오류').then(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            if (submitBtnSpan) {
                                submitBtnSpan.textContent = window.isEditMode ? '저장하기' : '작성하기';
                            } else {
                                submitBtn.textContent = window.isEditMode ? '저장하기' : '작성하기';
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
                            submitBtnSpan.textContent = window.isEditMode ? '저장하기' : '작성하기';
                        } else {
                            submitBtn.textContent = window.isEditMode ? '저장하기' : '작성하기';
                        }
                    }
                });
            });
        });
    }
    
    // MVNO 리뷰 삭제 버튼 클릭 이벤트
    const deleteReviewBtn = document.getElementById('mvnoReviewDeleteBtn');
    if (deleteReviewBtn) {
        deleteReviewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const reviewId = this.getAttribute('data-review-id') || window.currentReviewId;
            if (!reviewId) {
                showAlert('리뷰 정보를 찾을 수 없습니다.', '오류');
                return;
            }
            
            showConfirm('정말로 리뷰를 삭제하시겠습니까?\n삭제된 리뷰는 복구할 수 없습니다.', '리뷰 삭제').then(confirmed => {
                if (!confirmed) return;
                
                // 삭제 버튼 비활성화
                this.disabled = true;
                const originalText = this.querySelector('span').textContent;
                this.querySelector('span').textContent = '삭제 중...';
                
                const formData = new FormData();
                formData.append('review_id', reviewId);
                formData.append('product_type', 'mvno');
                
                fetch((window.API_PATH || (window.BASE_PATH || '') + '/api') + '/delete-review.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('리뷰가 삭제되었습니다.', '알림').then(() => {
                            closeReviewModal();
                            location.reload();
                        });
                    } else {
                        showAlert(data.message || '리뷰 삭제에 실패했습니다.', '오류').then(() => {
                            this.disabled = false;
                            this.querySelector('span').textContent = originalText;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('리뷰 삭제 중 오류가 발생했습니다.', '오류').then(() => {
                        this.disabled = false;
                        this.querySelector('span').textContent = originalText;
                    });
                });
            });
        });
    }
    
    // 기존 코드
    const modal = document.getElementById('applicationDetailModal');
    const modalContent = document.getElementById('modalContent');
    const closeBtn = document.getElementById('closeModalBtn');
    const applicationCards = document.querySelectorAll('.application-card');
    
    // 카드 클릭 이벤트
    applicationCards.forEach(card => {
        card.addEventListener('click', function(e) {
            const applicationId = this.getAttribute('data-application-id');
            if (applicationId) {
                openModal(applicationId);
            }
        });
    });
    
    // 모달 닫기 버튼
    closeBtn.addEventListener('click', closeModal);
    
    // 배경 클릭 시 모달 닫기
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
        }
    });
    
    function openModal(applicationId) {
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
        fetch(`${window.API_PATH || (window.BASE_PATH || '') + '/api'}/get-application-details.php?application_id=${applicationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayApplicationDetails(data.data);
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
                        <p style="font-size: 14px; margin-top: 8px;">네트워크 오류가 발생했습니다.</p>
                    </div>
                `;
            });
    }
    
    function closeModal() {
        modal.style.display = 'none';
        // 배경 페이지 스크롤 복원
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        document.documentElement.style.overflow = '';
    }
    
    function displayApplicationDetails(data) {
        const customer = data.customer || {};
        const additionalInfo = data.additional_info || {};
        const productSnapshot = additionalInfo.product_snapshot || {};
        
        let html = '<div style="display: flex; flex-direction: column; gap: 24px;">';
        
        // 주문 정보 섹션 (맨 위로 이동)
        html += '<div>';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">주문 정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        if (data.order_number) {
            html += `<div style="color: #6b7280; font-weight: 500;">주문번호:</div>`;
            html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(data.order_number)}</div>`;
        }
        
        if (data.status) {
            html += `<div style="color: #6b7280; font-weight: 500;">진행상황:</div>`;
            html += `<div style="color: #6366f1; font-weight: 600;">${escapeHtml(data.status)}</div>`;
        }
        
        if (data.status_changed_at) {
            html += `<div style="color: #6b7280; font-weight: 500;">상태 변경일시:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(data.status_changed_at)}</div>`;
        }
        
        // 가격 정보 (주문 정보 섹션에 추가)
        if (productSnapshot.price_main) {
            html += `<div style="color: #6b7280; font-weight: 500;">월 요금:</div>`;
            html += `<div style="color: #1f2937; font-weight: 600;">${formatNumber(productSnapshot.price_main)}원</div>`;
        }
        
        if (productSnapshot.discount_period) {
            html += `<div style="color: #6b7280; font-weight: 500;">할인기간(프로모션기간):</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.discount_period)}</div>`;
        }
        
        // 할인기간요금(프로모션기간요금) - discount_period가 있고 프로모션이 없지 않을 때만 표시
        if (productSnapshot.discount_period && 
            productSnapshot.discount_period !== '프로모션 없음' && 
            productSnapshot.discount_period !== '') {
            if (productSnapshot.price_after !== null && productSnapshot.price_after !== undefined) {
                // 0이면 공짜로 표시, 그 외는 금액 표시
                if (productSnapshot.price_after === 0 || productSnapshot.price_after === '0') {
                    html += `<div style="color: #6b7280; font-weight: 500;">할인기간요금(프로모션기간요금):</div>`;
                    html += `<div style="color: #1f2937;">공짜</div>`;
                } else {
                    html += `<div style="color: #6b7280; font-weight: 500;">할인기간요금(프로모션기간요금):</div>`;
                    html += `<div style="color: #6366f1; font-weight: 600;">${formatNumber(productSnapshot.price_after)}원</div>`;
                }
            } else {
                // price_after가 null이면 프로모션 없음
                html += `<div style="color: #6b7280; font-weight: 500;">할인기간요금(프로모션기간요금):</div>`;
                html += `<div style="color: #1f2937;">-</div>`;
            }
        } else if (productSnapshot.discount_period === '프로모션 없음' || 
                   (!productSnapshot.discount_period || productSnapshot.discount_period === '')) {
            // 프로모션이 없을 때도 할인기간요금 필드 표시 (빈 값)
            html += `<div style="color: #6b7280; font-weight: 500;">할인기간요금(프로모션기간요금):</div>`;
            html += `<div style="color: #1f2937;">-</div>`;
        }
        
        html += '</div></div>';
        
        // 고객 정보 섹션
        html += '<div>';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">고객 정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
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
        
        if (customer.address) {
            html += `<div style="color: #6b7280; font-weight: 500;">주소:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(customer.address)}${customer.address_detail ? ' ' + escapeHtml(customer.address_detail) : ''}</div>`;
        }
        
        if (customer.birth_date) {
            html += `<div style="color: #6b7280; font-weight: 500;">생년월일:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(customer.birth_date)}</div>`;
        }
        
        if (customer.gender) {
            html += `<div style="color: #6b7280; font-weight: 500;">성별:</div>`;
            const genderText = customer.gender === 'male' ? '남성' : customer.gender === 'female' ? '여성' : '기타';
            html += `<div style="color: #1f2937;">${genderText}</div>`;
        }
        
        html += '</div></div>';
        
        // 상품 정보 섹션 (신청 시점)
        html += '<div>';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">상품정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        // 가입 형태를 상품 정보 섹션 첫 번째 항목으로 추가
        if (additionalInfo.subscription_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">가입 형태:</div>`;
            // 가입 형태 한글 변환
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
        
        if (productSnapshot.provider) {
            html += `<div style="color: #6b7280; font-weight: 500;">통신사:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.provider)}</div>`;
        }
        
        if (productSnapshot.plan_name) {
            html += `<div style="color: #6b7280; font-weight: 500;">요금제명:</div>`;
            html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(productSnapshot.plan_name)}</div>`;
        }
        
        if (productSnapshot.service_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터 속도:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.service_type)}</div>`;
        }
        
        if (productSnapshot.contract_period) {
            html += `<div style="color: #6b7280; font-weight: 500;">약정기간:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.contract_period)}</div>`;
        }
        
        // 데이터 제공량
        if (productSnapshot.data_amount) {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터 제공량:</div>`;
            let dataText = '';
            if (productSnapshot.data_amount === '직접입력' && productSnapshot.data_amount_value) {
                // data_amount_value에 이미 단위가 포함되어 있는지 확인
                const dataValueStr = String(productSnapshot.data_amount_value);
                const unit = productSnapshot.data_unit || 'GB';
                // 끝에 단위가 이미 포함되어 있으면 추가하지 않음
                if (dataValueStr.endsWith('GB') || dataValueStr.endsWith('MB') || dataValueStr.endsWith('TB') || 
                    dataValueStr.endsWith('Mbps') || dataValueStr.endsWith('Gbps') || dataValueStr.endsWith('Kbps')) {
                    dataText = dataValueStr;
                } else {
                    dataText = dataValueStr + unit;
                }
            } else {
                dataText = productSnapshot.data_amount;
            }
            html += `<div style="color: #1f2937;">${escapeHtml(dataText)}</div>`;
        }
        
        // 데이터 추가제공
        if (productSnapshot.data_additional && productSnapshot.data_additional !== '없음') {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터 추가제공:</div>`;
            let dataAdditionalText = '';
            if (productSnapshot.data_additional === '직접입력' && productSnapshot.data_additional_value) {
                dataAdditionalText = productSnapshot.data_additional_value;
            } else {
                dataAdditionalText = productSnapshot.data_additional;
            }
            html += `<div style="color: #1f2937;">${escapeHtml(dataAdditionalText)}</div>`;
        }
        
        // 데이터 소진시
        if (productSnapshot.data_exhausted) {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터 소진시:</div>`;
            let dataExhaustedText = '';
            if (productSnapshot.data_exhausted === '직접입력' && productSnapshot.data_exhausted_value) {
                dataExhaustedText = productSnapshot.data_exhausted_value;
            } else {
                dataExhaustedText = productSnapshot.data_exhausted;
            }
            html += `<div style="color: #1f2937;">${escapeHtml(dataExhaustedText)}</div>`;
        }
        
        // 통화 정보
        if (productSnapshot.call_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">통화:</div>`;
            let callText = productSnapshot.call_type;
            if (productSnapshot.call_type === '직접입력' && productSnapshot.call_amount) {
                // call_amount에 이미 단위가 포함되어 있는지 확인
                const callAmountStr = String(productSnapshot.call_amount);
                const unit = productSnapshot.call_amount_unit || '분';
                // 끝에 단위가 이미 포함되어 있으면 추가하지 않음
                if (callAmountStr.endsWith('분') || callAmountStr.endsWith('초') || callAmountStr.endsWith('건')) {
                    callText = callAmountStr;
                } else {
                    callText = callAmountStr + unit;
                }
            }
            html += `<div style="color: #1f2937;">${escapeHtml(callText)}</div>`;
        }
        
        // 문자 정보
        if (productSnapshot.sms_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">문자:</div>`;
            let smsText = productSnapshot.sms_type;
            if (productSnapshot.sms_type === '직접입력' && productSnapshot.sms_amount) {
                // sms_amount에 이미 단위가 포함되어 있는지 확인
                const smsAmountStr = String(productSnapshot.sms_amount);
                const unit = productSnapshot.sms_amount_unit || '건';
                // 끝에 단위가 이미 포함되어 있으면 추가하지 않음
                if (smsAmountStr.endsWith('분') || smsAmountStr.endsWith('초') || smsAmountStr.endsWith('건')) {
                    smsText = smsAmountStr;
                } else {
                    smsText = smsAmountStr + unit;
                }
            }
            html += `<div style="color: #1f2937;">${escapeHtml(smsText)}</div>`;
        }
        
        // 부가·영상통화
        if (productSnapshot.additional_call_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">부가·영상통화:</div>`;
            let additionalCallText = productSnapshot.additional_call_type;
            if (productSnapshot.additional_call_type === '직접입력' && productSnapshot.additional_call) {
                // additional_call에 이미 단위가 포함되어 있는지 확인
                const additionalCallStr = String(productSnapshot.additional_call);
                const unit = productSnapshot.additional_call_unit || '분';
                // 끝에 단위가 이미 포함되어 있으면 추가하지 않음
                if (additionalCallStr.endsWith('분') || additionalCallStr.endsWith('초') || additionalCallStr.endsWith('건')) {
                    additionalCallText = additionalCallStr;
                } else {
                    additionalCallText = additionalCallStr + unit;
                }
            }
            html += `<div style="color: #1f2937;">${escapeHtml(additionalCallText)}</div>`;
        }
        
        // 테더링(핫스팟)
        html += `<div style="color: #6b7280; font-weight: 500;">테더링(핫스팟):</div>`;
        let hotspotText = '기본 제공량 내에서 사용';
        if (productSnapshot.mobile_hotspot) {
            if (productSnapshot.mobile_hotspot === '직접선택' && productSnapshot.mobile_hotspot_value) {
                hotspotText = productSnapshot.mobile_hotspot_value;
            } else {
                hotspotText = productSnapshot.mobile_hotspot;
            }
        }
        html += `<div style="color: #1f2937;">${escapeHtml(hotspotText)}</div>`;
        
        html += '</div></div>';
        
        // 유심 정보 섹션
        html += '<div>';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">유심 정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        // 일반 유심
        html += `<div style="color: #6b7280; font-weight: 500;">일반 유심:</div>`;
        let regularSimText = '배송불가';
        if (productSnapshot.regular_sim_available === '배송가능') {
            if (productSnapshot.regular_sim_price) {
                // 가격에 이미 단위가 포함되어 있는지 확인
                const priceStr = String(productSnapshot.regular_sim_price);
                if (priceStr.match(/^(\d+)(.+)$/)) {
                    const matches = priceStr.match(/^(\d+)(.+)$/);
                    regularSimText = `배송가능 (${parseInt(matches[1]).toLocaleString('ko-KR')}${matches[2]})`;
                } else {
                    regularSimText = `배송가능 (${parseInt(productSnapshot.regular_sim_price).toLocaleString('ko-KR')}원)`;
                }
            } else {
                regularSimText = '배송가능';
            }
        }
        html += `<div style="color: #1f2937;">${escapeHtml(regularSimText)}</div>`;
        
        // NFC 유심
        html += `<div style="color: #6b7280; font-weight: 500;">NFC 유심:</div>`;
        let nfcSimText = '배송불가';
        if (productSnapshot.nfc_sim_available === '배송가능') {
            if (productSnapshot.nfc_sim_price) {
                // 가격에 이미 단위가 포함되어 있는지 확인
                const priceStr = String(productSnapshot.nfc_sim_price);
                if (priceStr.match(/^(\d+)(.+)$/)) {
                    const matches = priceStr.match(/^(\d+)(.+)$/);
                    nfcSimText = `배송가능 (${parseInt(matches[1]).toLocaleString('ko-KR')}${matches[2]})`;
                } else {
                    nfcSimText = `배송가능 (${parseInt(productSnapshot.nfc_sim_price).toLocaleString('ko-KR')}원)`;
                }
            } else {
                nfcSimText = '배송가능';
            }
        }
        html += `<div style="color: #1f2937;">${escapeHtml(nfcSimText)}</div>`;
        
        // eSIM
        html += `<div style="color: #6b7280; font-weight: 500;">eSIM:</div>`;
        let esimText = '개통불가';
        if (productSnapshot.esim_available === '개통가능') {
            if (productSnapshot.esim_price) {
                // 가격에 이미 단위가 포함되어 있는지 확인
                const priceStr = String(productSnapshot.esim_price);
                if (priceStr.match(/^(\d+)(.+)$/)) {
                    const matches = priceStr.match(/^(\d+)(.+)$/);
                    esimText = `개통가능 (${parseInt(matches[1]).toLocaleString('ko-KR')}${matches[2]})`;
                } else {
                    esimText = `개통가능 (${parseInt(productSnapshot.esim_price).toLocaleString('ko-KR')}원)`;
                }
            } else {
                esimText = '개통가능';
            }
        }
        html += `<div style="color: #1f2937;">${escapeHtml(esimText)}</div>`;
        
        html += '</div></div>';
        
        // 프로모션 이벤트 섹션
        if (productSnapshot.promotion_title || productSnapshot.promotions) {
            let promotions = [];
            try {
                if (typeof productSnapshot.promotions === 'string') {
                    promotions = JSON.parse(productSnapshot.promotions);
                } else if (Array.isArray(productSnapshot.promotions)) {
                    promotions = productSnapshot.promotions;
                }
            } catch(e) {
                promotions = [];
            }
            
            const giftCount = promotions.filter(p => p && p.trim()).length;
            const promotionTitle = productSnapshot.promotion_title || '';
            
            if (giftCount > 0 || promotionTitle) {
                html += '<div>';
                html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">프로모션 이벤트</h3>';
                html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
                
                // 프로모션 제목 (항목1, 항목2, 항목3...) 형식
                let promotionText = '';
                if (promotionTitle) {
                    promotionText = promotionTitle;
                }
                
                if (giftCount > 0) {
                    const promotionList = promotions.filter(p => p && p.trim()).map(p => escapeHtml(p.trim())).join(', ');
                    if (promotionText) {
                        promotionText = `${promotionText} (${promotionList})`;
                    } else {
                        promotionText = promotionList;
                    }
                }
                
                if (promotionText) {
                    html += `<div style="color: #6b7280; font-weight: 500;">프로모션 이벤트:</div>`;
                    html += `<div style="color: #1f2937;">${promotionText}</div>`;
                }
                
                html += '</div></div>';
            }
        }
        
        // 기본 제공 초과 시 섹션
        let hasOverData = false;
        let overDataPrice = productSnapshot.over_data_price;
        let overVoicePrice = productSnapshot.over_voice_price;
        let overVideoPrice = productSnapshot.over_video_price;
        let overSmsPrice = productSnapshot.over_sms_price;
        let overLmsPrice = productSnapshot.over_lms_price;
        let overMmsPrice = productSnapshot.over_mms_price;
        
        if (overDataPrice !== null && overDataPrice !== undefined && overDataPrice !== '') hasOverData = true;
        if (overVoicePrice !== null && overVoicePrice !== undefined && overVoicePrice !== '') hasOverData = true;
        if (overVideoPrice !== null && overVideoPrice !== undefined && overVideoPrice !== '') hasOverData = true;
        if (overSmsPrice !== null && overSmsPrice !== undefined && overSmsPrice !== '') hasOverData = true;
        if (overLmsPrice !== null && overLmsPrice !== undefined && overLmsPrice !== '' && String(overLmsPrice).trim() !== '') hasOverData = true;
        if (overMmsPrice !== null && overMmsPrice !== undefined && overMmsPrice !== '' && String(overMmsPrice).trim() !== '') hasOverData = true;
        
        if (hasOverData) {
            html += '<div>';
            html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">기본 제공 초과 시</h3>';
            html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
            
            // 데이터
            if (overDataPrice !== null && overDataPrice !== undefined && overDataPrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">데이터</div>`;
                html += `<div style="color: #1f2937;">${escapeHtml(String(overDataPrice))}</div>`;
            }
            
            // 음성
            if (overVoicePrice !== null && overVoicePrice !== undefined && overVoicePrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">음성</div>`;
                html += `<div style="color: #1f2937;">${escapeHtml(String(overVoicePrice))}</div>`;
            }
            
            // 영상통화
            if (overVideoPrice !== null && overVideoPrice !== undefined && overVideoPrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">영상통화</div>`;
                html += `<div style="color: #1f2937;">${escapeHtml(String(overVideoPrice))}</div>`;
            }
            
            // 단문메시지(SMS)
            if (overSmsPrice !== null && overSmsPrice !== undefined && overSmsPrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">단문메시지(SMS)</div>`;
                html += `<div style="color: #1f2937;">${escapeHtml(String(overSmsPrice))}</div>`;
            }
            
            // 텍스트형(LMS)
            if (overLmsPrice !== null && overLmsPrice !== undefined && overLmsPrice !== '' && String(overLmsPrice).trim() !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">텍스트형(LMS)</div>`;
                html += `<div style="color: #1f2937;">${escapeHtml(String(overLmsPrice))}</div>`;
            }
            
            // 멀티미디어형(MMS)
            if (overMmsPrice !== null && overMmsPrice !== undefined && overMmsPrice !== '' && String(overMmsPrice).trim() !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">멀티미디어형(MMS)</div>`;
                html += `<div style="color: #1f2937;">${escapeHtml(String(overMmsPrice))}</div>`;
            }
            
            html += '</div></div>';
        }
        
        // 혜택 및 유의사항 섹션
        if (productSnapshot.benefits) {
            html += '<div>';
            html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">혜택 및 유의사항</h3>';
            html += '<div style="font-size: 14px; color: #374151; line-height: 1.8;">';
            
            let benefits = null;
            try {
                // benefits가 문자열인 경우 JSON 파싱 시도
                if (typeof productSnapshot.benefits === 'string') {
                    const parsed = JSON.parse(productSnapshot.benefits);
                    if (Array.isArray(parsed)) {
                        benefits = parsed;
                    } else {
                        benefits = [productSnapshot.benefits];
                    }
                } else if (Array.isArray(productSnapshot.benefits)) {
                    benefits = productSnapshot.benefits;
                } else {
                    benefits = [String(productSnapshot.benefits)];
                }
            } catch(e) {
                // JSON 파싱 실패 시 문자열로 처리
                benefits = [String(productSnapshot.benefits)];
            }
            
            if (benefits && benefits.length > 0) {
                html += '<ul style="margin: 0; padding-left: 20px; list-style-type: disc;">';
                benefits.forEach(function(benefit) {
                    const benefitText = String(benefit).trim();
                    if (benefitText) {
                        // 줄바꿈 문자(\n)를 <br> 태그로 변환
                        const formattedText = escapeHtml(benefitText).replace(/\n/g, '<br>');
                        html += `<li style="margin-bottom: 8px;">${formattedText}</li>`;
                    }
                });
                html += '</ul>';
            } else {
                html += '<div style="color: #9ca3af;">추가 정보 없음</div>';
            }
            
            html += '</div></div>';
        }
        
        html += '</div>';
        
        modalContent.innerHTML = html;
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatNumber(num) {
        if (!num) return '0';
        return parseInt(num).toLocaleString('ko-KR');
    }
    
    // 아코디언 토글 기능 (동적으로 추가된 아코디언도 작동하도록)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.plan-accordion-trigger')) {
            const trigger = e.target.closest('.plan-accordion-trigger');
            const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
            const accordion = trigger.closest('.plan-accordion');
            const content = accordion ? accordion.querySelector('.plan-accordion-content') : null;
            const arrow = trigger.querySelector('.plan-accordion-arrow');
            
            if (!content) return;
            
            // aria-expanded 상태 토글
            trigger.setAttribute('aria-expanded', !isExpanded);
            
            // 콘텐츠 표시/숨김
            if (isExpanded) {
                content.style.display = 'none';
                if (arrow) {
                    arrow.style.transform = 'rotate(0deg)';
                }
            } else {
                content.style.display = 'block';
                if (arrow) {
                    arrow.style.transform = 'rotate(180deg)';
                }
            }
        }
    });
    
    // 더보기 기능은 load-more-products.js에서 처리
});
</script>

<style>
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

<script src="<?php echo getAssetPath('/assets/js/load-more-products.js'); ?>?v=2"></script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>













