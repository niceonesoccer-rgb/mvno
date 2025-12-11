<?php
// 현재 페이지 설정
$current_page = 'mno';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true; // 상세 페이지에서도 푸터 표시

// 통신사폰 ID 가져오기
$phone_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// 헤더 포함
include '../includes/header.php';

// 통신사폰 데이터 가져오기
require_once '../includes/data/phone-data.php';
require_once '../includes/data/plan-data.php';
$phone = getPhoneDetailData($phone_id);
if (!$phone) {
    // 데이터가 없으면 기본값 사용
    $phone = [
        'id' => $phone_id,
        'provider' => 'SKT',
        'device_name' => 'Galaxy Z Fold7',
        'device_image' => 'https://assets.moyoplan.com/image/phone/model/galaxy_z_fold7.png',
        'device_storage' => '256GB',
        'device_price' => '출고가 2,387,000원',
        'plan_name' => 'SKT 프리미어 슈퍼',
        'common_number_port' => '191.6',
        'common_device_change' => '191.6',
        'contract_number_port' => '191.6',
        'contract_device_change' => '191.6',
        'monthly_price' => '109,000원',
        'maintenance_period' => '185일',
        'gifts' => [
            '추가 지원금',
            '부가 서비스 1',
            '부가 서비스 2'
        ]
    ];
}
?>

<main class="main-content plan-detail-page">
    <!-- 통신사폰 상세 레이아웃 (모듈 사용) -->
    <?php include '../includes/layouts/phone-detail-layout.php'; ?>

    <!-- 통신사폰 리뷰 섹션 -->
    <section class="phone-review-section" id="phoneReviewSection">
        <div class="content-layout">
            <div class="plan-review-header">
                <?php 
                $company_name_raw = $phone['company_name'] ?? '쉐이크모바일';
                // "스마트모바일" → "스마트"로 변환
                $company_name = $company_name_raw;
                if (strpos($company_name_raw, '스마트모바일') !== false) {
                    $company_name = '스마트';
                } elseif (strpos($company_name_raw, '모바일') !== false) {
                    // "XX모바일" 형식에서 "XX"만 추출
                    $company_name = str_replace('모바일', '', $company_name_raw);
                }
                ?>
                <span class="plan-review-logo-text"><?php echo htmlspecialchars($company_name); ?></span>
                <h2 class="section-title">리뷰</h2>
            </div>
            
            <?php
            // 정렬 방식 가져오기 (기본값: 높은 평점순)
            $sort = $_GET['review_sort'] ?? 'rating_desc';
            if (!in_array($sort, ['rating_desc', 'rating_asc', 'created_desc'])) {
                $sort = 'rating_desc';
            }
            
            // 실제 리뷰 데이터 가져오기 (같은 판매자의 같은 타입의 모든 상품 리뷰 통합)
            $reviews = getProductReviews($phone_id, 'mno', 20, $sort);
            $averageRating = getProductAverageRating($phone_id, 'mno');
            $reviewCount = getProductReviewCount($phone_id, 'mno');
            $hasReviews = $reviewCount > 0;
            ?>
            <?php if ($hasReviews): ?>
            <div class="plan-review-summary">
                <div class="plan-review-rating">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#EF4444"/>
                    </svg>
                    <span class="plan-review-rating-score"><?php echo htmlspecialchars($averageRating > 0 ? number_format($averageRating, 1) : '0.0'); ?></span>
                    <span class="plan-review-rating-count"><?php echo number_format($reviewCount); ?>개</span>
                </div>
                <div class="plan-review-categories">
                    <div class="plan-review-category">
                        <span class="plan-review-category-label">고객센터</span>
                        <span class="plan-review-category-score"><?php echo htmlspecialchars($averageRating > 0 ? number_format($averageRating - 0.1, 1) : '0.0'); ?></span>
                        <div class="plan-review-stars">
                            <span><?php echo getStarsFromRating(round($averageRating)); ?></span>
                        </div>
                    </div>
                    <div class="plan-review-category">
                        <span class="plan-review-category-label">개통 과정</span>
                        <span class="plan-review-category-score"><?php echo htmlspecialchars($averageRating > 0 ? number_format($averageRating + 0.2, 1) : '0.0'); ?></span>
                        <div class="plan-review-stars">
                            <span><?php echo getStarsFromRating(round($averageRating)); ?></span>
                        </div>
                    </div>
                    <div class="plan-review-category">
                        <span class="plan-review-category-label">개통 후 만족도</span>
                        <span class="plan-review-category-score"><?php echo htmlspecialchars($averageRating > 0 ? number_format($averageRating - 0.1, 1) : '0.0'); ?></span>
                        <div class="plan-review-stars">
                            <span><?php echo getStarsFromRating(round($averageRating)); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="plan-review-count-section">
                <div class="plan-review-count-sort-wrapper">
                    <span class="plan-review-count">총 <?php echo number_format($reviewCount); ?>개</span>
                    <div class="plan-review-sort-select-wrapper">
                        <select class="plan-review-sort-select" id="phoneReviewSortSelect" aria-label="리뷰 정렬 방식 선택">
                            <option value="rating_desc" <?php echo $sort === 'rating_desc' ? 'selected' : ''; ?>>높은 평점순</option>
                            <option value="rating_asc" <?php echo $sort === 'rating_asc' ? 'selected' : ''; ?>>낮은 평점순</option>
                            <option value="created_desc" <?php echo $sort === 'created_desc' ? 'selected' : ''; ?>>최신순</option>
                        </select>
                    </div>
                </div>
                <?php
                // 로그인한 사용자에게만 리뷰 작성 버튼 표시
                require_once '../includes/data/auth-functions.php';
                $currentUserId = getCurrentUserId();
                if ($currentUserId): ?>
                    <button class="plan-review-write-btn" id="phoneReviewWriteBtn">리뷰 작성</button>
                <?php endif; ?>
            </div>

            <div class="plan-review-list" id="phoneReviewList">
                <?php if (!empty($reviews)): ?>
                    <?php foreach (array_slice($reviews, 0, 5) as $review): ?>
                        <div class="plan-review-item">
                            <div class="plan-review-item-header">
                                <span class="plan-review-author"><?php echo htmlspecialchars($review['author_name'] ?? '익명'); ?></span>
                                <div class="plan-review-stars">
                                    <span><?php echo htmlspecialchars($review['stars'] ?? '★★★★☆'); ?></span>
                                </div>
                                <span class="plan-review-date"><?php echo htmlspecialchars($review['date_ago'] ?? '오늘'); ?></span>
                            </div>
                            <p class="plan-review-content"><?php echo htmlspecialchars($review['content'] ?? ''); ?></p>
                            <?php if (!empty($phone['device_name'])): ?>
                                <div class="plan-review-tags">
                                    <span class="plan-review-tag"><?php echo htmlspecialchars($phone['device_name']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($reviews) > 5): ?>
                        <?php foreach (array_slice($reviews, 5) as $review): ?>
                            <div class="plan-review-item" style="display: none;">
                                <div class="plan-review-item-header">
                                    <span class="plan-review-author"><?php echo htmlspecialchars($review['author_name'] ?? '익명'); ?></span>
                                    <div class="plan-review-stars">
                                        <span><?php echo htmlspecialchars($review['stars'] ?? '★★★★☆'); ?></span>
                                    </div>
                                    <span class="plan-review-date"><?php echo htmlspecialchars($review['date_ago'] ?? '오늘'); ?></span>
                                </div>
                                <p class="plan-review-content"><?php echo htmlspecialchars($review['content'] ?? ''); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="plan-review-item">
                        <p class="plan-review-content" style="text-align: center; color: #9ca3af; padding: 20px;">아직 리뷰가 없습니다.</p>
                    </div>
                <?php endif; ?>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">최*수</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">18일 전</span>
                    </div>
                    <p class="plan-review-content">기기 변경으로 가입했는데 할인 혜택이 정말 좋아요. 기존보다 훨씬 저렴하게 구매할 수 있어서 만족합니다.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">정*민</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">24일 전</span>
                    </div>
                    <p class="plan-review-content">통신사폰 처음 사용해봤는데 생각보다 괜찮아요. 통신 품질도 좋고 가격도 합리적입니다. 주변 사람들한테도 추천했어요.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">강*희</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="plan-review-date">31일 전</span>
                    </div>
                    <p class="plan-review-content">선택약정으로 가입했는데 추가 할인이 있어서 좋았어요. 약정 기간 동안 안정적으로 사용할 수 있어서 만족합니다.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">윤*진</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">38일 전</span>
                    </div>
                    <p class="plan-review-content">공통지원할인 받아서 정말 저렴하게 구매했어요. 기기 품질도 좋고 통신도 안정적입니다. 강력 추천합니다!</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">장*우</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">45일 전</span>
                    </div>
                    <p class="plan-review-content">통신사폰 구매 과정이 생각보다 간단했어요. 온라인으로 신청하고 바로 개통되어서 편리했습니다. 고객센터도 친절해요.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">임*성</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="plan-review-date">52일 전</span>
                    </div>
                    <p class="plan-review-content">기기 할인과 요금제 할인을 동시에 받아서 정말 좋았어요. 월 요금도 부담 없고 통신 품질도 만족스럽습니다.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">한*지</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">59일 전</span>
                    </div>
                    <p class="plan-review-content">번호이동 수수료 없이 진행할 수 있어서 좋았어요. 개통도 빠르고 통신 품질도 기존과 동일해서 만족합니다.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">송*현</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">66일 전</span>
                    </div>
                    <p class="plan-review-content">통신사폰으로 갤럭시 구매했는데 할인 혜택이 정말 좋아요. 기존보다 훨씬 저렴하게 구매할 수 있어서 만족합니다.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">조*혁</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="plan-review-date">73일 전</span>
                    </div>
                    <p class="plan-review-content">신규 가입으로 진행했는데 번호도 마음에 들고 개통도 빠르게 되었어요. 지원금도 많이 받아서 좋았습니다.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">배*수</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">80일 전</span>
                    </div>
                    <p class="plan-review-content">통신사폰 처음 사용해봤는데 생각보다 괜찮아요. 통신 품질도 좋고 가격도 합리적입니다. 주변 사람들한테도 추천했어요.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">신*아</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">87일 전</span>
                    </div>
                    <p class="plan-review-content">기기 변경으로 가입했는데 할인 혜택이 정말 좋아요. 기존보다 훨씬 저렴하게 구매할 수 있어서 만족합니다.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">오*성</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="plan-review-date">94일 전</span>
                    </div>
                    <p class="plan-review-content">선택약정으로 가입했는데 추가 할인이 있어서 좋았어요. 약정 기간 동안 안정적으로 사용할 수 있어서 만족합니다.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">류*호</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">101일 전</span>
                    </div>
                    <p class="plan-review-content">공통지원할인 받아서 정말 저렴하게 구매했어요. 기기 품질도 좋고 통신도 안정적입니다. 강력 추천합니다!</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">문*희</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">108일 전</span>
                    </div>
                    <p class="plan-review-content">통신사폰 구매 과정이 생각보다 간단했어요. 온라인으로 신청하고 바로 개통되어서 편리했습니다. 고객센터도 친절해요.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">양*준</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="plan-review-date">115일 전</span>
                    </div>
                    <p class="plan-review-content">기기 할인과 요금제 할인을 동시에 받아서 정말 좋았어요. 월 요금도 부담 없고 통신 품질도 만족스럽습니다.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">홍*영</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">122일 전</span>
                    </div>
                    <p class="plan-review-content">번호이동 수수료 없이 진행할 수 있어서 좋았어요. 개통도 빠르고 통신 품질도 기존과 동일해서 만족합니다.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">서*우</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">129일 전</span>
                    </div>
                    <p class="plan-review-content">통신사폰으로 아이폰 구매했는데 할인 혜택이 정말 좋아요. 기존보다 훨씬 저렴하게 구매할 수 있어서 만족합니다.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">노*진</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="plan-review-date">136일 전</span>
                    </div>
                    <p class="plan-review-content">신규 가입으로 진행했는데 번호도 마음에 들고 개통도 빠르게 되었어요. 지원금도 많이 받아서 좋았습니다.</p>
                </div>
                <div class="plan-review-item" style="display: none;">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">김*수</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">143일 전</span>
                    </div>
                    <p class="plan-review-content">통신사폰 처음 사용해봤는데 생각보다 괜찮아요. 통신 품질도 좋고 가격도 합리적입니다. 주변 사람들한테도 추천했어요.</p>
                </div>
            </div>
            <button class="plan-review-more-btn" id="phoneReviewMoreBtn">리뷰 더보기</button>
        </div>
    </section>

</main>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

<script src="/MVNO/assets/js/plan-accordion.js" defer></script>
<script src="/MVNO/assets/js/favorite-heart.js" defer></script>

<?php
// 리뷰 작성 모달 포함
$prefix = 'phone';
$speedLabel = '개통 빨라요';
$formId = 'phoneReviewForm';
$modalId = 'phoneReviewModal';
$textareaId = 'phoneReviewText';
include '../includes/components/order-review-modal.php';
?>

<script>
// 리뷰 작성 기능
document.addEventListener('DOMContentLoaded', function() {
    const reviewWriteBtn = document.getElementById('phoneReviewWriteBtn');
    const reviewModal = document.getElementById('phoneReviewModal');
    const reviewForm = document.getElementById('phoneReviewForm');
    const reviewModalOverlay = reviewModal ? reviewModal.querySelector('.phone-review-modal-overlay') : null;
    const reviewModalClose = reviewModal ? reviewModal.querySelector('.phone-review-modal-close') : null;
    
    if (!reviewWriteBtn || !reviewModal || !reviewForm) {
        return;
    }
    
    // 리뷰 작성 버튼 클릭
    reviewWriteBtn.addEventListener('click', function() {
        reviewModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });
    
    // 모달 닫기
    function closeReviewModal() {
        reviewModal.style.display = 'none';
        document.body.style.overflow = '';
        reviewForm.reset();
        // 별점 초기화
        const starInputs = reviewForm.querySelectorAll('input[type="radio"]');
        starInputs.forEach(input => {
            input.checked = false;
        });
        const starLabels = reviewForm.querySelectorAll('.phone-star-label');
        starLabels.forEach(label => {
            label.classList.remove('active');
        });
    }
    
    if (reviewModalOverlay) {
        reviewModalOverlay.addEventListener('click', closeReviewModal);
    }
    
    if (reviewModalClose) {
        reviewModalClose.addEventListener('click', closeReviewModal);
    }
    
    // 별점 클릭 이벤트
    const starLabels = reviewForm.querySelectorAll('.phone-star-label');
    starLabels.forEach(label => {
        label.addEventListener('click', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            const ratingType = this.closest('.phone-star-rating').getAttribute('data-rating-type');
            const radioInput = this.previousElementSibling;
            
            if (radioInput) {
                radioInput.checked = true;
            }
            
            // 같은 타입의 별점 업데이트
            const sameTypeLabels = reviewForm.querySelectorAll('.phone-star-rating[data-rating-type="' + ratingType + '"] .phone-star-label');
            sameTypeLabels.forEach((l, index) => {
                if (index < rating) {
                    l.classList.add('active');
                } else {
                    l.classList.remove('active');
                }
            });
        });
    });
    
    // 폼 제출
    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const kindnessRatingInput = reviewForm.querySelector('input[name="kindness_rating"]:checked');
        const speedRatingInput = reviewForm.querySelector('input[name="speed_rating"]:checked');
        const reviewText = document.getElementById('phoneReviewText').value.trim();
        
        if (!kindnessRatingInput) {
            alert('친절해요 별점을 선택해주세요.');
            return;
        }
        
        if (!speedRatingInput) {
            alert('개통 빨라요 별점을 선택해주세요.');
            return;
        }
        
        if (!reviewText) {
            alert('리뷰 내용을 입력해주세요.');
            return;
        }
        
        // 평균 별점 계산
        const kindnessRating = parseInt(kindnessRatingInput.value);
        const speedRating = parseInt(speedRatingInput.value);
        const averageRating = Math.round((kindnessRating + speedRating) / 2);
        
        // API 호출
        const formData = new FormData();
        formData.append('product_id', <?php echo $phone_id; ?>);
        formData.append('product_type', 'mno');
        formData.append('rating', averageRating);
        formData.append('content', reviewText);
        
        fetch('/MVNO/api/submit-review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('리뷰가 작성되었습니다.');
                closeReviewModal();
                // 페이지 새로고침하여 리뷰 반영
                location.reload();
            } else {
                alert(data.message || '리뷰 작성에 실패했습니다.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('리뷰 작성 중 오류가 발생했습니다.');
        });
    });
});
</script>

<?php
// 사용자 정보 가져오기 (세션에서)
$user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? '';
$user_phone = $_SESSION['user_phone'] ?? $_SESSION['phone'] ?? '';
$user_email = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';
?>

<!-- 상담신청 모달 -->
<div class="consultation-modal" id="consultationModal">
    <div class="consultation-modal-overlay" id="consultationModalOverlay"></div>
    <div class="consultation-modal-content">
        <div class="consultation-modal-header">
            <h3 class="consultation-modal-title">가입상담 신청</h3>
            <button class="consultation-modal-close" aria-label="닫기" id="consultationModalClose">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="consultation-modal-body">
            <form id="consultationForm" class="consultation-form">
                <div class="consultation-form-group">
                    <label for="consultationName" class="consultation-form-label">이름</label>
                    <input type="text" id="consultationName" name="name" class="consultation-form-input" value="<?php echo htmlspecialchars($user_name); ?>" required>
                </div>
                
                <div class="consultation-form-group">
                    <label for="consultationPhone" class="consultation-form-label">휴대폰번호</label>
                    <input type="tel" id="consultationPhone" name="phone" class="consultation-form-input" value="<?php echo htmlspecialchars($user_phone); ?>" placeholder="010-1234-5678" required>
                </div>
                
                <div class="consultation-form-group">
                    <label for="consultationEmail" class="consultation-form-label">이메일</label>
                    <input type="email" id="consultationEmail" name="email" class="consultation-form-input" value="<?php echo htmlspecialchars($user_email); ?>" placeholder="example@email.com" required>
                </div>
                
                <div class="consultation-notice-section">
                    <div class="consultation-notice-intro">
                        모유에서 다음 정보가 알림톡으로 발송됩니다:<br>
                        <span class="consultation-notice-intro-sub">알림 정보 설정은 마이페이지에서 수정가능하세요.</span>
                    </div>
                    <div class="consultation-notice-list">
                        <div class="consultation-notice-item consultation-notice-item-empty"></div>
                        <div class="consultation-notice-item consultation-notice-item-center">
                            • 신청정보<br>
                            • 약정기간 종료 안내<br>
                            • 프로모션 종료 안내<br>
                            • 기타 상품관련 안내
                        </div>
                        <div class="consultation-notice-item consultation-notice-item-empty"></div>
                    </div>
                    <div class="consultation-notice-text">
                        <?php 
                        $company_name = $phone['company_name'] ?? '쉐이크모바일';
                        echo htmlspecialchars($company_name); 
                        ?>에서 고객님께 가입상담을 진행합니다
                    </div>
                    <div class="consultation-notice-agreement-text">
                        동의 하시면 신청하기를 진행해주세요
                    </div>
                </div>
                
                <div class="consultation-agreement-section">
                    <div class="consultation-agreement-all">
                        <div class="consultation-agreement-checkbox-wrapper">
                            <input type="checkbox" id="agreementAll" class="consultation-agreement-checkbox consultation-agreement-all-checkbox">
                            <label for="agreementAll" class="consultation-agreement-label consultation-agreement-all-label">
                                전체 동의
                            </label>
                        </div>
                    </div>
                    
                    <div class="consultation-agreement-divider"></div>
                    
                    <div class="consultation-agreement-item">
                        <div class="consultation-agreement-checkbox-wrapper">
                            <input type="checkbox" id="agreementPurpose" name="agreementPurpose" class="consultation-agreement-checkbox consultation-agreement-item-checkbox" required>
                            <label for="agreementPurpose" class="consultation-agreement-label">
                                개인정보 수집 및 이용목적에 동의합니까?
                            </label>
                            <button type="button" class="consultation-agreement-view-btn" data-agreement="purpose">내용보기</button>
                        </div>
                    </div>
                    
                    <div class="consultation-agreement-item">
                        <div class="consultation-agreement-checkbox-wrapper">
                            <input type="checkbox" id="agreementItems" name="agreementItems" class="consultation-agreement-checkbox consultation-agreement-item-checkbox" required>
                            <label for="agreementItems" class="consultation-agreement-label">
                                개인정보 수집하는 항목에 동의합니까?
                            </label>
                            <button type="button" class="consultation-agreement-view-btn" data-agreement="items">내용보기</button>
                        </div>
                    </div>
                    
                    <div class="consultation-agreement-item">
                        <div class="consultation-agreement-checkbox-wrapper">
                            <input type="checkbox" id="agreementPeriod" name="agreementPeriod" class="consultation-agreement-checkbox consultation-agreement-item-checkbox" required>
                            <label for="agreementPeriod" class="consultation-agreement-label">
                                개인정보 보유 및 이용기간에 동의합니까?
                            </label>
                            <button type="button" class="consultation-agreement-view-btn" data-agreement="period">내용보기</button>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="consultation-submit-btn" id="consultationSubmitBtn">신청하기</button>
            </form>
        </div>
    </div>
</div>

<!-- 개인정보 내용보기 모달 -->
<div class="privacy-content-modal" id="privacyContentModal">
    <div class="privacy-content-modal-overlay" id="privacyContentModalOverlay"></div>
    <div class="privacy-content-modal-content">
        <div class="privacy-content-modal-header">
            <h3 class="privacy-content-modal-title" id="privacyContentModalTitle">개인정보 수집 및 이용목적</h3>
            <button class="privacy-content-modal-close" aria-label="닫기" id="privacyContentModalClose">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="privacy-content-modal-body" id="privacyContentModalBody">
            <!-- 내용이 JavaScript로 동적으로 채워짐 -->
        </div>
    </div>
</div>

<!-- 통신사폰 리뷰 모달 -->
<div class="review-modal" id="phoneReviewModal">
    <div class="review-modal-overlay" id="phoneReviewModalOverlay"></div>
    <div class="review-modal-content">
        <div class="review-modal-header">
            <?php 
            if (!isset($company_name)) {
                $company_name_raw = $phone['company_name'] ?? '쉐이크모바일';
                // "스마트모바일" → "스마트"로 변환
                $company_name = $company_name_raw;
                if (strpos($company_name_raw, '스마트모바일') !== false) {
                    $company_name = '스마트';
                } elseif (strpos($company_name_raw, '모바일') !== false) {
                    // "XX모바일" 형식에서 "XX"만 추출
                    $company_name = str_replace('모바일', '', $company_name_raw);
                }
            }
            if (!isset($provider)) {
                $provider = $phone['provider'] ?? 'SKT';
            }
            ?>
            <h3 class="review-modal-title"><?php echo htmlspecialchars($company_name); ?> (<?php echo htmlspecialchars($provider); ?>)</h3>
            <button class="review-modal-close" aria-label="닫기" id="phoneReviewModalClose">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="review-modal-body">
            <div class="review-modal-summary">
                <div class="review-modal-rating-main">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#EF4444"/>
                    </svg>
                    <span class="review-modal-rating-score"><?php echo htmlspecialchars($averageRating > 0 ? number_format($averageRating, 1) : '0.0'); ?></span>
                    <span class="review-modal-rating-count"><?php echo number_format($reviewCount); ?>개</span>
                </div>
                <div class="review-modal-categories">
                    <div class="review-modal-category">
                        <span class="review-modal-category-label">고객센터</span>
                        <span class="review-modal-category-score"><?php echo htmlspecialchars($averageRating > 0 ? number_format($averageRating - 0.1, 1) : '0.0'); ?></span>
                        <div class="review-modal-stars">
                            <span><?php echo getStarsFromRating(round($averageRating)); ?></span>
                        </div>
                    </div>
                    <div class="review-modal-category">
                        <span class="review-modal-category-label">개통 과정</span>
                        <span class="review-modal-category-score"><?php echo htmlspecialchars($averageRating > 0 ? number_format($averageRating + 0.2, 1) : '0.0'); ?></span>
                        <div class="review-modal-stars">
                            <span><?php echo getStarsFromRating(round($averageRating)); ?></span>
                        </div>
                    </div>
                    <div class="review-modal-category">
                        <span class="review-modal-category-label">개통 후 만족도</span>
                        <span class="review-modal-category-score"><?php echo htmlspecialchars($averageRating > 0 ? number_format($averageRating - 0.1, 1) : '0.0'); ?></span>
                        <div class="review-modal-stars">
                            <span><?php echo getStarsFromRating(round($averageRating)); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="review-modal-sort">
                <div class="review-modal-sort-wrapper">
                    <span class="review-modal-total">총 <?php echo number_format($reviewCount); ?>개</span>
                    <div class="review-modal-sort-select-wrapper">
                        <select class="review-modal-sort-select" id="phoneReviewModalSortSelect" aria-label="리뷰 정렬 방식 선택">
                            <option value="rating_desc" <?php echo $sort === 'rating_desc' ? 'selected' : ''; ?>>높은 평점순</option>
                            <option value="rating_asc" <?php echo $sort === 'rating_asc' ? 'selected' : ''; ?>>낮은 평점순</option>
                            <option value="created_desc" <?php echo $sort === 'created_desc' ? 'selected' : ''; ?>>최신순</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="review-modal-list" id="phoneReviewModalList">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-modal-item">
                            <div class="review-modal-item-header">
                                <span class="review-modal-author"><?php echo htmlspecialchars($review['author_name'] ?? '익명'); ?></span>
                                <div class="review-modal-stars">
                                    <span><?php echo htmlspecialchars($review['stars'] ?? '★★★★☆'); ?></span>
                                </div>
                                <span class="review-modal-date"><?php echo htmlspecialchars($review['date_ago'] ?? '오늘'); ?></span>
                            </div>
                            <p class="review-modal-item-content"><?php echo nl2br(htmlspecialchars($review['content'] ?? '')); ?></p>
                            <?php if (!empty($phone['device_name'])): ?>
                                <div class="review-modal-tags">
                                    <span class="review-modal-tag"><?php echo htmlspecialchars($phone['device_name']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="review-modal-item">
                        <p class="review-modal-item-content" style="text-align: center; color: #868e96; padding: 40px 0;">등록된 리뷰가 없습니다.</p>
                    </div>
                <?php endif; ?>
            </div>
            <button class="review-modal-more-btn" id="phoneReviewModalMoreBtn">리뷰 더보기</button>
        </div>
    </div>
</div>

<script>
// 상담신청 모달 기능
document.addEventListener('DOMContentLoaded', function() {
    const applyBtn = document.getElementById('phoneApplyBtn');
    const consultationModal = document.getElementById('consultationModal');
    const consultationModalOverlay = document.getElementById('consultationModalOverlay');
    const consultationModalClose = document.getElementById('consultationModalClose');
    const consultationForm = document.getElementById('consultationForm');
    
    // 개인정보 내용보기 모달
    const privacyModal = document.getElementById('privacyContentModal');
    const privacyModalOverlay = document.getElementById('privacyContentModalOverlay');
    const privacyModalClose = document.getElementById('privacyContentModalClose');
    const privacyModalTitle = document.getElementById('privacyContentModalTitle');
    const privacyModalBody = document.getElementById('privacyContentModalBody');
    
    // 개인정보 내용 정의
    const privacyContents = {
        purpose: {
            title: '개인정보 수집 및 이용목적',
            content: `<div class="privacy-content-text">
                <p><strong>1. 개인정보의 수집 및 이용목적</strong></p>
                <p>모유('http://www.dtmall.net' 이하 '회사') 은(는) 다음의 목적을 위하여 개인정보를 처리하고 있으며, 다음의 목적 이외의 용도로는 이용하지 않습니다.</p>
                
                <p><strong>가. 서비스 제공에 관한 계약 이행 및 서비스 제공에 따른 요금정산</strong></p>
                <p>컨텐츠 제공, 특정 맞춤 서비스 제공, 물품배송 또는 청구서 등 발송, 본인인증, 구매 및 요금 결제</p>
                
                <p><strong>나. 회원관리</strong></p>
                <p>회원제 서비스 이용 및 제한적 본인 확인제에 따른 고객 가입의사 확인, 고객에 대한 서비스 제공에 따른 본인 식별.인증, 불량회원의 부정 이용방지와 비인가 사용방지, 가입 및 가입횟수 제한, 분쟁 조정을 위한 기록보존, 불만처리 등 민원처리, 고지사항 전달, 회원자격 유지.관리, 회원 포인트 유지.관리 등</p>
                
                <p><strong>다. 신규 서비스 개발 및 마케팅</strong></p>
                <p>신규 서비스 개발 및 맞춤 서비스 제공, 통계학적 특성에 따른 서비스 제공 및 광고 게재, 서비스의 유효성 확인, 이벤트 및 광고성 정보 제공 및 참여기회 제공, 접속빈도 파악, 회원의 서비스이용에 대한 통계</p>
            </div>`
        },
        items: {
            title: '개인정보 수집하는 항목',
            content: `<div class="privacy-content-text">
                <p><strong>2. 개인정보 수집항목 및 수집방법</strong></p>
                <p>모유('http://www.dtmall.net' 이하 '회사') 은(는) 다음의 개인정보 항목을 처리하고 있습니다.</p>
                
                <p><strong>가. 수집하는 개인정보의 항목</strong></p>
                <p>첫째, 회사는 휴대폰 개통 및 원활한 고객상담을 위해 주문시 아래와 같은 개인정보를 수집하고 있습니다.</p>
                <p>- 필수항목 : 성명, 핸드폰번호, 긴급연락처</p>
                
                <p>둘째, 서비스 이용과정이나 사업처리 과정에서 아래와 같은 정보들이 자동으로 생성되어 수집될 수 있습니다.</p>
                <p>- IP Address, 쿠키, 방문 일시, 서비스 이용 기록, 불량 이용 기록</p>
                
                <p><strong>나. 개인정보 수집방법</strong></p>
                <p>회사는 다음과 같은 방법으로 개인정보를 수집합니다.</p>
                <p>- 홈페이지, 서면양식, 팩스, 전화, 상담 게시판, 이메일, 이벤트 응모, 배송요청</p>
                <p>- 협력회사로부터의 제공</p>
                <p>- 생성정보 수집 툴을 통한 수집</p>
            </div>`
        },
        period: {
            title: '개인정보 보유 및 이용기간',
            content: `<div class="privacy-content-text">
                <p><strong>3. 개인정보의 보유 및 이용기간</strong></p>
                <p>모유('http://www.dtmall.net' 이하 '회사') 은(는) 이용자의 개인정보는 원칙적으로 개인정보의 수집 및 이용목적이 달성되면 지체 없이 파기합니다. 단, 다음의 정보에 대해서는 아래의 이유로 명시한 기간 동안 보존합니다.</p>
                
                <p><strong>가. 내부 방침에 의한 정보보유 사유</strong></p>
                <p>- 부정이용기록</p>
                <p>보존 이유 : 부정 이용 방지</p>
                <p>보존 기간 : 1년</p>
                
                <p><strong>나. 관련법령에 의한 정보보유 사유</strong></p>
                <p>상법, 전자상거래 등에서의 소비자보호에 관한 법률 등 관계법령의 규정에 의하여 보존할 필요가 있는 경우 회사는 관계법령에서 정한 일정한 기간 동안 회원정보를 보관합니다. 이 경우 회사는 보관하는 정보를 그 보관의 목적으로만 이용하며 보존기간은 아래와 같습니다.</p>
                
                <p>- 계약 또는 청약철회 등에 관한 기록</p>
                <p>보존 이유 : 전자상거래 등에서의 소비자보호에 관한 법률</p>
                <p>보존 기간 : 5년</p>
                
                <p>- 대금결제 및 재화 등의 공급에 관한 기록</p>
                <p>보존 이유 : 전자상거래 등에서의 소비자보호에 관한 법률</p>
                <p>보존 기간 : 5년</p>
                
                <p>- 소비자의 불만 또는 분쟁처리에 관한 기록</p>
                <p>보존 이유 : 전자상거래 등에서의 소비자보호에 관한 법률</p>
                <p>보존 기간 : 3년</p>
                
                <p>- 본인확인에 관한 기록</p>
                <p>보존 이유 : 정보통신 이용촉진 및 정보보호 등에 관한 법률</p>
                <p>보존 기간 : 6개월</p>
                
                <p>- 방문에 관한 기록</p>
                <p>보존 이유 : 통신비밀보호법</p>
                <p>보존 기간 : 3개월</p>
            </div>`
        }
    };
    
    // 스크롤 위치 저장 변수
    let scrollPosition = 0;
    
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
    
    // 상담신청 모달 열기
    function openConsultationModal() {
        if (!consultationModal) return;
        
        scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        const scrollbarWidth = getScrollbarWidth();
        
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollPosition}px`;
        document.body.style.width = '100%';
        document.body.style.paddingRight = `${scrollbarWidth}px`;
        document.documentElement.style.overflow = 'hidden';
        
        consultationModal.style.display = 'flex';
        consultationModal.classList.add('consultation-modal-active');
    }
    
    // 상담신청 모달 닫기
    function closeConsultationModal() {
        if (!consultationModal) return;
        
        consultationModal.classList.remove('consultation-modal-active');
        
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        document.body.style.paddingRight = '';
        document.documentElement.style.overflow = '';
        
        window.scrollTo(0, scrollPosition);
    }
    
    // 개인정보 내용보기 모달 열기
    function openPrivacyModal(type) {
        if (!privacyModal || !privacyContents[type]) return;
        
        privacyModalTitle.textContent = privacyContents[type].title;
        privacyModalBody.innerHTML = privacyContents[type].content;
        
        privacyModal.style.display = 'flex';
        privacyModal.classList.add('privacy-content-modal-active');
    }
    
    // 개인정보 내용보기 모달 닫기
    function closePrivacyModal() {
        if (!privacyModal) return;
        
        privacyModal.classList.remove('privacy-content-modal-active');
    }
    
    // 신청하기 버튼 클릭 이벤트
    if (applyBtn) {
        applyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openConsultationModal();
        });
    }
    
    // 상담신청 모달 닫기 이벤트
    if (consultationModalOverlay) {
        consultationModalOverlay.addEventListener('click', closeConsultationModal);
    }
    
    if (consultationModalClose) {
        consultationModalClose.addEventListener('click', closeConsultationModal);
    }
    
    // 전체 동의 체크박스
    const agreementAll = document.getElementById('agreementAll');
    const agreementItemCheckboxes = document.querySelectorAll('.consultation-agreement-item-checkbox');
    
    // 전체 동의 체크박스 변경 이벤트
    if (agreementAll) {
        agreementAll.addEventListener('change', function() {
            const isChecked = this.checked;
            agreementItemCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        });
    }
    
    // 개별 체크박스 변경 이벤트 (전체 동의 상태 업데이트)
    agreementItemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(agreementItemCheckboxes).every(cb => cb.checked);
            if (agreementAll) {
                agreementAll.checked = allChecked;
            }
        });
    });
    
    // 개인정보 내용보기 버튼 클릭 이벤트
    const viewBtns = document.querySelectorAll('.consultation-agreement-view-btn');
    viewBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const type = this.getAttribute('data-agreement');
            openPrivacyModal(type);
        });
    });
    
    // 개인정보 내용보기 모달 닫기 이벤트
    if (privacyModalOverlay) {
        privacyModalOverlay.addEventListener('click', closePrivacyModal);
    }
    
    if (privacyModalClose) {
        privacyModalClose.addEventListener('click', closePrivacyModal);
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (privacyModal && privacyModal.classList.contains('privacy-content-modal-active')) {
                closePrivacyModal();
            } else if (consultationModal && consultationModal.classList.contains('consultation-modal-active')) {
                closeConsultationModal();
            }
        }
    });
    
    // 폼 제출 이벤트
    if (consultationForm) {
        consultationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // 모든 동의 체크박스 확인
            const agreementPurpose = document.getElementById('agreementPurpose');
            const agreementItems = document.getElementById('agreementItems');
            const agreementPeriod = document.getElementById('agreementPeriod');
            
            if (!agreementPurpose.checked || !agreementItems.checked || !agreementPeriod.checked) {
                alert('모든 개인정보 동의 항목에 동의해주세요.');
                return;
            }
            
            // 폼 데이터 수집
            const formData = new FormData(this);
            formData.append('product_id', <?php echo $phone_id; ?>);
            
            // 제출 버튼 비활성화
            const submitBtn = document.getElementById('consultationSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '처리 중...';
            }
            
            // 서버로 데이터 전송
            fetch('/MVNO/api/submit-mno-application.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 신청정보가 판매자에게 저장됨
                    
                    // redirect_url이 있으면 해당 URL로 이동
                    if (data.redirect_url && data.redirect_url.trim() !== '') {
                        window.location.href = data.redirect_url;
                    } else {
                        // redirect_url이 없으면 창 닫기
                        alert('상담신청이 완료되었습니다.');
                        closeConsultationModal();
                    }
                } else {
                    alert(data.message || '신청 처리 중 오류가 발생했습니다.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = '신청하기';
                    }
                }
            })
            .catch(error => {
                console.error('신청 처리 오류:', error);
                alert('신청 처리 중 오류가 발생했습니다.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = '신청하기';
                }
            });
        });
    }
});

// 리뷰 정렬 기능
document.addEventListener('DOMContentLoaded', function() {
    const reviewSortSelect = document.getElementById('phoneReviewSortSelect');
    if (reviewSortSelect) {
        reviewSortSelect.addEventListener('change', function() {
            const sort = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('review_sort', sort);
            window.location.href = url.toString();
        });
    }
});

// 통신사폰 리뷰 모달 기능
document.addEventListener('DOMContentLoaded', function() {
    const reviewList = document.getElementById('phoneReviewList');
    const reviewMoreBtn = document.getElementById('phoneReviewMoreBtn');
    const reviewModal = document.getElementById('phoneReviewModal');
    const reviewModalOverlay = document.getElementById('phoneReviewModalOverlay');
    const reviewModalClose = document.getElementById('phoneReviewModalClose');
    const reviewModalList = document.getElementById('phoneReviewModalList');
    const reviewModalMoreBtn = document.getElementById('phoneReviewModalMoreBtn');
    
    // 페이지 리뷰: 처음 5개만 표시
    if (reviewList) {
        const reviewItems = reviewList.querySelectorAll('.plan-review-item');
        const totalReviews = reviewItems.length;
        const visibleCount = 5;
        
        reviewItems.forEach((item, index) => {
            if (index >= visibleCount) {
                item.style.display = 'none';
            }
        });
        
        // 리뷰 더보기 버튼에 남은 리뷰 개수 표시
        if (reviewMoreBtn && totalReviews > visibleCount) {
            const remainingCount = totalReviews - visibleCount;
            reviewMoreBtn.textContent = `리뷰 더보기 (${remainingCount}개)`;
        } else if (reviewMoreBtn) {
            reviewMoreBtn.style.display = 'none';
        }
    }
    
    // 모달 열기 함수
    function openReviewModal() {
        if (reviewModal) {
            // 리뷰 섹션으로 스크롤 이동
            const reviewSection = document.getElementById('phoneReviewSection');
            if (reviewSection) {
                const sectionTop = reviewSection.getBoundingClientRect().top + window.pageYOffset;
                window.scrollTo({
                    top: sectionTop,
                    behavior: 'smooth'
                });
            }
            
            // 모달 열기 (약간의 딜레이를 주어 스크롤 후 모달이 열리도록)
            setTimeout(() => {
                reviewModal.classList.add('review-modal-active');
                document.body.classList.add('review-modal-open');
                document.body.style.overflow = 'hidden';
                document.documentElement.style.overflow = 'hidden';
            }, 300);
        }
    }
    
    // 모달 닫기 함수
    function closeReviewModal() {
        if (reviewModal) {
            reviewModal.classList.remove('review-modal-active');
            document.body.classList.remove('review-modal-open');
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        }
    }
    
    // 리뷰 아이템 클릭 시 모달 열기
    if (reviewList) {
        const reviewItems = reviewList.querySelectorAll('.plan-review-item');
        reviewItems.forEach(item => {
            item.style.cursor = 'pointer';
            item.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openReviewModal();
            });
        });
    }
    
    // 더보기 버튼 클릭 시 모달 열기
    if (reviewMoreBtn) {
        reviewMoreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openReviewModal();
        });
    }
    
    // 모달 닫기 이벤트
    if (reviewModalOverlay) {
        reviewModalOverlay.addEventListener('click', closeReviewModal);
    }
    
    if (reviewModalClose) {
        reviewModalClose.addEventListener('click', closeReviewModal);
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && reviewModal && reviewModal.classList.contains('review-modal-active')) {
            closeReviewModal();
        }
    });
    
    // 모달 내부 더보기 기능: 처음 5개, 이후 10개씩 표시
    if (reviewModalList && reviewModalMoreBtn) {
        const modalReviewItems = reviewModalList.querySelectorAll('.review-modal-item');
        const totalModalReviews = modalReviewItems.length;
        let visibleModalCount = 5; // 처음 5개만 표시
        
        // 초기 설정: 5개 이후 리뷰 숨기기
        function initializeModalReviews() {
            visibleModalCount = 5; // 모달 열 때마다 5개로 초기화
            modalReviewItems.forEach((item, index) => {
                if (index >= visibleModalCount) {
                    item.style.display = 'none';
                } else {
                    item.style.display = 'block';
                }
            });
            
            // 모든 리뷰가 이미 표시되어 있으면 버튼 숨기기
            if (totalModalReviews <= visibleModalCount) {
                reviewModalMoreBtn.style.display = 'none';
            } else {
                reviewModalMoreBtn.style.display = 'block';
            }
        }
        
        // 초기 설정 실행
        initializeModalReviews();
        
        // 모달이 열릴 때마다 초기화
        if (reviewModal) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (reviewModal.classList.contains('review-modal-active')) {
                            initializeModalReviews(); // 모달 열 때마다 5개로 초기화
                        }
                    }
                });
            });
            observer.observe(reviewModal, { attributes: true });
        }
        
        // 모달 내부 더보기 버튼 클릭 이벤트
        reviewModalMoreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            visibleModalCount += 10; // 10개씩 추가
            
            // 리뷰 표시
            modalReviewItems.forEach((item, index) => {
                if (index < visibleModalCount) {
                    item.style.display = 'block';
                }
            });
            
            // 모든 리뷰가 표시되면 버튼 숨기기
            if (visibleModalCount >= totalModalReviews) {
                reviewModalMoreBtn.style.display = 'none';
            }
        });
    }
    
    // 리뷰 정렬 선택 기능 (페이지)
    const reviewSortSelect = document.getElementById('phoneReviewSortSelect');
    if (reviewSortSelect) {
        reviewSortSelect.addEventListener('change', function() {
            const sort = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('review_sort', sort);
            window.location.href = url.toString();
        });
    }
    
    // 리뷰 정렬 선택 기능 (모달)
    const reviewModalSortSelect = document.getElementById('phoneReviewModalSortSelect');
    if (reviewModalSortSelect) {
        reviewModalSortSelect.addEventListener('change', function() {
            const sort = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('review_sort', sort);
            window.location.href = url.toString();
        });
    }
});
</script>


