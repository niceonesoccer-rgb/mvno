<?php
// 현재 페이지 설정
$current_page = 'mno';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true; // 상세 페이지에서도 푸터 표시

// 통신사폰 ID 가져오기
$phone_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// 로그인 체크를 위한 auth-functions 포함
require_once '../includes/data/auth-functions.php';
require_once '../includes/data/privacy-functions.php';

// 개인정보 설정 로드
$privacySettings = getPrivacySettings();

// 헤더 포함
include '../includes/header.php';

// 조회수 업데이트
require_once '../includes/data/product-functions.php';
incrementProductView($phone_id);

// 통신사폰 데이터 가져오기
require_once '../includes/data/phone-data.php';
require_once '../includes/data/plan-data.php';
$phone = getPhoneDetailData($phone_id);
$rawData = $phone['_raw_data'] ?? []; // 원본 DB 데이터 (null 대신 빈 배열로 초기화)

// 상품번호 가져오기
require_once '../includes/data/product-functions.php';
$productNumber = getProductNumberByType($phone_id, 'mno', $rawData['seller_id'] ?? null);
// phone-card-body.php에서 사용할 수 있도록 phone 배열에 추가
$phone['product_number'] = $productNumber;

// 관리자 여부 확인
$isAdmin = false;
try {
    if (function_exists('isAdmin') && function_exists('getCurrentUser')) {
        $currentUser = getCurrentUser();
        if ($currentUser) {
            $isAdmin = isAdmin($currentUser['user_id']);
        }
    }
} catch (Exception $e) {
    // 관리자 체크 실패 시 일반 사용자로 처리
}

// 상품이 없거나, 일반 사용자가 판매종료 상품에 접근하는 경우
if (!$phone) {
    http_response_code(404);
    die('상품을 찾을 수 없습니다.');
}

// 일반 사용자가 판매종료 상품에 접근하는 경우 차단
if (!$isAdmin && isset($phone['status']) && $phone['status'] === 'inactive') {
    http_response_code(404);
    die('판매종료된 상품입니다.');
}
?>

<main class="main-content plan-detail-page">
    <!-- 통신사폰 상세 레이아웃 (모듈 사용) -->
    <?php include '../includes/layouts/phone-detail-layout.php'; ?>

    <!-- 통신사폰 리뷰 섹션 -->
    <?php
    // 정렬 방식 가져오기 (기본값: 최신순)
    $sort = $_GET['review_sort'] ?? 'created_desc';
    if (!in_array($sort, ['rating_desc', 'rating_asc', 'created_desc'])) {
        $sort = 'created_desc';
    }
    
    // 상대 시간 표시 함수
    function getRelativeTime($datetime) {
        if (empty($datetime)) {
            return '';
        }
        
        try {
            $reviewTime = new DateTime($datetime);
            $now = new DateTime();
            $diff = $now->diff($reviewTime);
            
            // 오늘인지 확인
            if ($diff->days === 0) {
                if ($diff->h === 0 && $diff->i === 0) {
                    return '방금 전';
                } elseif ($diff->h === 0) {
                    return $diff->i . '분 전';
                } else {
                    return $diff->h . '시간 전';
                }
            }
            
            // 어제인지 확인
            if ($diff->days === 1) {
                return '어제';
            }
            
            // 일주일 전까지
            if ($diff->days < 7) {
                return $diff->days . '일 전';
            }
            
            // 한달 전까지 (30일)
            if ($diff->days < 30) {
                $weeks = floor($diff->days / 7);
                return $weeks . '주 전';
            }
            
            // 일년 전까지 (365일)
            if ($diff->days < 365) {
                $months = floor($diff->days / 30);
                return $months . '개월 전';
            }
            
            // 일년 이상
            $years = floor($diff->days / 365);
            return $years . '년 전';
        } catch (Exception $e) {
            return '';
        }
    }
    
    // 실제 리뷰 데이터 가져오기 (같은 판매자의 같은 타입의 모든 상품 리뷰 통합)
    // 모달에서 모든 리뷰를 표시하기 위해 충분히 많은 수를 가져옴
    $allReviews = getProductReviews($phone_id, 'mno', 1000, $sort);
    $reviews = array_slice($allReviews, 0, 3); // 페이지에는 처음 3개만 표시
    $averageRating = getProductAverageRating($phone_id, 'mno');
    // 실제 리뷰 배열의 개수를 사용 (통계 테이블 대신 실제 데이터 확인)
    $reviewCount = count($allReviews);
    $hasReviews = !empty($allReviews) && $reviewCount > 0;
    $remainingCount = max(0, $reviewCount - 5); // 남은 리뷰 개수
    
    // 카테고리별 평균 별점 가져오기 (MVNO와 동일한 함수 사용)
    $categoryAverages = getInternetReviewCategoryAverages($phone_id, 'mno');
    
    // 판매자명 가져오기
    $sellerName = '';
    try {
        require_once '../includes/data/product-functions.php';
        // DB에서 직접 seller_id 가져오기
        $pdo = getDBConnection();
        if ($pdo) {
            $sellerStmt = $pdo->prepare("SELECT seller_id FROM products WHERE id = :product_id AND product_type = 'mno' LIMIT 1");
            $sellerStmt->execute([':product_id' => $phone_id]);
            $sellerData = $sellerStmt->fetch(PDO::FETCH_ASSOC);
            $sellerId = $sellerData['seller_id'] ?? null;
            
            if ($sellerId) {
                $seller = getSellerById($sellerId);
                if ($seller) {
                    $sellerName = getSellerDisplayName($seller);
                }
            }
        }
    } catch (Exception $e) {
        error_log("MNO Phone Detail - Error getting seller name: " . $e->getMessage());
    }
    
    // pending 상태의 리뷰를 자동으로 approved로 변경 (기존 pending 리뷰 처리용)
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            // 해당 상품의 pending 상태 리뷰를 approved로 변경
            $updateStmt = $pdo->prepare("UPDATE product_reviews SET status = 'approved' WHERE product_id = :product_id AND product_type = 'mno' AND status = 'pending'");
            $updateStmt->execute([':product_id' => $phone_id]);
            $updatedCount = $updateStmt->rowCount();
            if ($updatedCount > 0) {
                error_log("MNO Phone Detail - Auto-approved {$updatedCount} pending review(s) for product_id: {$phone_id}");
                // 리뷰 목록 다시 가져오기 (새로고침 효과)
                $allReviews = getProductReviews($phone_id, 'mno', 1000, $sort);
                $reviews = array_slice($allReviews, 0, 3);
                $reviewCount = count($allReviews);
                $hasReviews = !empty($allReviews) && $reviewCount > 0;
                $remainingCount = max(0, $reviewCount - 3);
            }
        }
    } catch (Exception $e) {
        error_log("MNO Phone Detail - Exception while auto-approving reviews: " . $e->getMessage());
    }
    ?>
    <!-- 통신사폰 리뷰 섹션 - 리뷰가 있으면 항상 표시 -->
    <section class="phone-review-section" id="phoneReviewSection">
        <div class="content-layout">
            <div class="plan-review-header">
                <span class="plan-review-logo-text"><?php echo htmlspecialchars($sellerName ?: ($phone['company_name'] ?? '통신사폰')); ?></span>
                <h2 class="section-title">리뷰</h2>
            </div>
            
            <?php if ($hasReviews): ?>
            <div class="plan-review-summary">
                <div class="plan-review-left">
                    <div class="plan-review-total-rating">
                        <div class="plan-review-total-rating-content">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px;">
                                <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#EF4444"></path>
                            </svg>
                            <span class="plan-review-rating-score"><?php echo htmlspecialchars($averageRating > 0 ? number_format($averageRating, 1) : '0.0'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="plan-review-right">
                    <div class="plan-review-categories">
                        <div class="plan-review-category">
                            <span class="plan-review-category-label">친절해요</span>
                            <span class="plan-review-category-score"><?php echo htmlspecialchars($categoryAverages['kindness'] > 0 ? number_format($categoryAverages['kindness'], 1) : '0.0'); ?></span>
                            <div class="plan-review-stars">
                                <?php echo getPartialStarsFromRating($categoryAverages['kindness']); ?>
                            </div>
                        </div>
                        <div class="plan-review-category">
                            <span class="plan-review-category-label">개통 빨라요</span>
                            <span class="plan-review-category-score"><?php echo htmlspecialchars($categoryAverages['speed'] > 0 ? number_format($categoryAverages['speed'], 1) : '0.0'); ?></span>
                            <div class="plan-review-stars">
                                <?php echo getPartialStarsFromRating($categoryAverages['speed']); ?>
                            </div>
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
            </div>

            <div class="plan-review-list" id="phoneReviewList">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="plan-review-item">
                            <div class="plan-review-item-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <?php 
                                    $authorName = htmlspecialchars($review['author_name'] ?? '익명');
                                    $provider = isset($review['provider']) && $review['provider'] ? htmlspecialchars($review['provider']) : '';
                                    ?>
                                    <span class="plan-review-author"><?php echo $authorName; ?></span>
                                    <?php if ($provider): ?>
                                        <span class="plan-review-provider-badge"><?php echo $provider; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="plan-review-stars">
                                        <span><?php echo htmlspecialchars($review['stars'] ?? '★★★★★'); ?></span>
                                    </div>
                                    <?php if (!empty($review['created_at'])): ?>
                                        <span class="plan-review-time" style="font-size: 0.875rem; color: #6b7280;">
                                            <?php echo htmlspecialchars(getRelativeTime($review['created_at'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="plan-review-content"><?php 
                                $content = $review['content'] ?? '';
                                // 줄바꿈 문자들을 공백 하나로 변환 (기존 공백은 유지)
                                // \r\n을 먼저 공백으로 변환, 그 다음 \r, \n을 각각 공백으로 변환
                                $content = str_replace(["\r\n", "\r", "\n"], ' ', $content);
                                echo htmlspecialchars($content);
                            ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="plan-review-item">
                        <p class="plan-review-content" style="text-align: center; color: #9ca3af; padding: 40px 0;">등록된 리뷰가 없습니다.</p>
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
            
            <?php if ($remainingCount > 0): ?>
                <button class="plan-review-more-btn" id="phoneReviewMoreBtn" data-total-reviews="<?php echo $reviewCount; ?>" data-sort="<?php echo htmlspecialchars($sort); ?>">
                    리뷰 더보기 (<?php echo number_format($remainingCount); ?>개)
                </button>
            <?php endif; ?>
        </div>
    </section>

</main>

<?php 
// 포인트 사용 모달 포함
$type = 'mno';
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$item_name = '';
include '../includes/components/point-usage-modal.php';
?>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

<script src="/MVNO/assets/js/plan-accordion.js" defer></script>
<script src="/MVNO/assets/js/favorite-heart.js" defer></script>

<script>
// 포인트 설정 확인 및 모달 열기 함수
function checkAndOpenPointModal(type, itemId, callback) {
    // 포인트 설정 조회
    fetch(`/MVNO/api/get-product-point-setting.php?type=${type}&id=${itemId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                // 조회 실패 시 바로 신청 모달 열기
                if (callback) callback();
                return;
            }
            
            // 포인트 설정이 0이거나 할인 혜택이 없으면 바로 신청 모달 열기
            if (!data.can_use_point || data.point_setting <= 0 || !data.point_benefit_description) {
                if (callback) callback();
                return;
            }
            
            // 포인트 모달 열기
            if (typeof openPointUsageModal === 'function') {
                openPointUsageModal(type, itemId);
                
                // 포인트 모달 확인 이벤트 리스너 (한 번만 등록)
                const eventHandler = function(e) {
                    const { usedPoint } = e.detail;
                    
                    // 포인트 사용 정보만 저장 (실제 차감은 가입 신청 완료 시 처리)
                    window.pointUsageData = {
                        type: type,
                        itemId: itemId,
                        usedPoint: usedPoint,
                        discountAmount: usedPoint,
                        productPointSetting: data.point_setting,
                        benefitDescription: data.point_benefit_description
                    };
                    
                    // 기존 신청 모달 열기
                    if (callback) callback();
                    
                    // 이벤트 리스너 제거 (한 번만 실행)
                    document.removeEventListener('pointUsageConfirmed', eventHandler);
                };
                
                document.addEventListener('pointUsageConfirmed', eventHandler, { once: true });
            } else {
                // 포인트 모달 함수가 없으면 바로 신청 모달 열기
                if (callback) callback();
            }
        })
        .catch(error => {
            console.error('포인트 설정 조회 오류:', error);
            // 오류 발생 시에도 신청 모달 열기 (사용자 경험 유지)
            if (callback) callback();
        });
}
</script>

<?php
// 사용자 정보 가져오기 (현재 로그인한 사용자)
$currentUser = getCurrentUser();
$user_name = '';
$user_phone = '';
$user_email = '';

if ($currentUser) {
    $user_name = $currentUser['name'] ?? $currentUser['user_name'] ?? $_SESSION['user_name'] ?? $_SESSION['name'] ?? '';
    $user_phone = $currentUser['phone'] ?? $_SESSION['user_phone'] ?? $_SESSION['phone'] ?? '';
    $user_email = $currentUser['email'] ?? $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';
}

// 할인방법 데이터 준비 (JSON으로 전달)
$discountData = [
    'common_support' => $phone['common_support'] ?? [],
    'contract_support' => $phone['contract_support'] ?? []
];
?>

<!-- 할인방법 선택 모달 -->
<div class="discount-selection-modal" id="discountSelectionModal">
    <div class="discount-selection-modal-overlay" id="discountSelectionModalOverlay"></div>
    <div class="discount-selection-modal-content">
        <div class="discount-selection-modal-header">
            <h3 class="discount-selection-modal-title">할인방법 선택</h3>
            <button class="discount-selection-modal-close" aria-label="닫기" id="discountSelectionModalClose">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="discount-selection-modal-body">
            <div class="discount-selection-table-wrapper">
                <table class="discount-selection-table">
                    <thead>
                        <tr>
                            <th>통신사</th>
                            <th>할인종류</th>
                            <th>가입유형</th>
                            <th>가격</th>
                        </tr>
                    </thead>
                    <tbody id="discountSelectionTableBody">
                        <!-- JavaScript로 동적으로 채워짐 -->
                    </tbody>
                </table>
            </div>
            <?php if (empty(array_filter($phone['common_support'] ?? [])) && empty(array_filter($phone['contract_support'] ?? []))): ?>
            <div class="discount-selection-empty">
                판매 가능한 할인 방법이 없습니다.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 단말기 색상 선택 모달 -->
<div class="discount-selection-modal" id="deviceColorSelectionModal">
    <div class="discount-selection-modal-overlay" id="deviceColorSelectionModalOverlay"></div>
    <div class="discount-selection-modal-content">
        <div class="discount-selection-modal-header">
            <h3 class="discount-selection-modal-title">단말기 색상 선택</h3>
            <button class="discount-selection-modal-close" aria-label="닫기" id="deviceColorSelectionModalClose">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="discount-selection-modal-body">
            <div id="device-colors-selection-container" style="display: flex; flex-wrap: wrap; gap: 12px; padding: 20px; justify-content: center;">
                <div style="width: 100%; color: #6b7280; font-size: 14px; text-align: center;">색상을 불러오는 중...</div>
            </div>
            <div style="padding: 20px; text-align: center;">
                <button type="button" id="deviceColorConfirmBtn" class="discount-amount-button" style="padding: 12px 32px; font-size: 16px; font-weight: 600; background: #6366f1; color: white; border: none; border-radius: 8px; cursor: pointer; min-width: 200px;" disabled>선택 완료</button>
            </div>
        </div>
    </div>
</div>

<!-- 상담신청 모달 -->
<div class="consultation-modal" id="consultationModal">
    <div class="consultation-modal-overlay" id="consultationModalOverlay"></div>
    <div class="consultation-modal-content">
        <div class="consultation-modal-header">
            <h3 class="consultation-modal-title">가입신청</h3>
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
                    <span id="consultationPhoneError" class="form-error-message" style="display: none; color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem;"></span>
                </div>
                
                <div class="consultation-form-group">
                    <label for="consultationEmail" class="consultation-form-label">이메일</label>
                    <input type="email" id="consultationEmail" name="email" class="consultation-form-input" value="<?php echo htmlspecialchars($user_email); ?>" placeholder="example@email.com" required>
                    <span id="consultationEmailError" class="form-error-message" style="display: none; color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem;"></span>
                </div>
                
                <!-- 체크박스 -->
                <div class="internet-checkbox-group">
                    <?php
                    // 동의 항목 정의 (순서대로)
                    $agreementItems = [
                        'purpose' => ['id' => 'mnoAgreementPurpose', 'name' => 'agreementPurpose', 'modal' => 'openMnoPrivacyModal'],
                        'items' => ['id' => 'mnoAgreementItems', 'name' => 'agreementItems', 'modal' => 'openMnoPrivacyModal'],
                        'period' => ['id' => 'mnoAgreementPeriod', 'name' => 'agreementPeriod', 'modal' => 'openMnoPrivacyModal'],
                        'thirdParty' => ['id' => 'mnoAgreementThirdParty', 'name' => 'agreementThirdParty', 'modal' => 'openMnoPrivacyModal'],
                        'serviceNotice' => ['id' => 'mnoAgreementServiceNotice', 'name' => 'service_notice_opt_in', 'accordion' => 'mnoServiceNoticeContent', 'accordionFunc' => 'toggleMnoAccordion'],
                        'marketing' => ['id' => 'mnoAgreementMarketing', 'name' => 'marketing_opt_in', 'accordion' => 'mnoMarketingContent', 'accordionFunc' => 'toggleMnoAccordion']
                    ];
                    
                    // 노출되는 항목이 있는지 확인
                    $hasVisibleItems = false;
                    foreach ($agreementItems as $key => $item) {
                        $setting = $privacySettings[$key] ?? [];
                        if (array_key_exists('isVisible', $setting)) {
                            $isVisible = (bool)$setting['isVisible'];
                        } else {
                            $isVisible = true;
                        }
                        if ($isVisible) {
                            $hasVisibleItems = true;
                            break;
                        }
                    }
                    
                    // 노출되는 항목이 있을 때만 "전체 동의" 표시
                    if ($hasVisibleItems):
                    ?>
                    <label class="internet-checkbox-all">
                        <input type="checkbox" id="mnoAgreementAll" class="internet-checkbox-input">
                        <span class="internet-checkbox-label">전체 동의</span>
                    </label>
                    <?php endif; ?>
                    <div class="internet-checkbox-list">
                        <?php
                        // 관리자 페이지 설정에 따라 동의 항목 동적 렌더링
                        foreach ($agreementItems as $key => $item):
                            $setting = $privacySettings[$key] ?? [];
                            
                            // 노출 여부 확인 (isVisible = false인 항목은 렌더링하지 않음)
                            if (array_key_exists('isVisible', $setting)) {
                                $isVisible = (bool)$setting['isVisible'];
                            } else {
                                $isVisible = true;
                            }
                            
                            if (!$isVisible) {
                                continue;
                            }
                            
                            // 제목 및 필수/선택 설정 (관리자 페이지에서 설정한 제목 사용)
                            $title = htmlspecialchars($setting['title'] ?? '');
                            // 제목이 비어있으면 기본값 사용
                            if (empty($title)) {
                                $defaultTitles = [
                                    'purpose' => '개인정보 수집 및 이용목적',
                                    'items' => '개인정보 수집하는 항목',
                                    'period' => '개인정보 보유 및 이용기간',
                                    'thirdParty' => '개인정보 제3자 제공',
                                    'serviceNotice' => '서비스 이용 및 혜택 안내 알림',
                                    'marketing' => '광고성 정보수신'
                                ];
                                $title = $defaultTitles[$key] ?? '';
                            }
                            $isRequired = $setting['isRequired'] ?? ($key !== 'marketing');
                            $requiredText = $isRequired ? '(필수)' : '(선택)';
                            $requiredColor = $isRequired ? '#4f46e5' : '#6b7280';
                            $requiredAttr = $isRequired ? 'required' : '';
                        ?>
                        <div class="internet-checkbox-item-wrapper">
                            <div class="internet-checkbox-item">
                                <label class="internet-checkbox-label-item">
                                    <input type="checkbox" id="<?php echo $item['id']; ?>" name="<?php echo $item['name']; ?>" class="internet-checkbox-input-item" <?php echo $requiredAttr; ?>>
                                    <span class="internet-checkbox-text" style="font-size: 1.0625rem !important;"><?php echo $title; ?> <span style="color: <?php echo $requiredColor; ?>; font-weight: 600;"><?php echo $requiredText; ?></span></span>
                                </label>
                                <?php if (isset($item['modal'])): ?>
                                <a href="#" class="internet-checkbox-link" id="mno<?php echo ucfirst($key); ?>ArrowLink" onclick="event.preventDefault(); <?php echo $item['modal']; ?>('<?php echo $key; ?>'); return false;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                        <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                    </svg>
                                </a>
                                <?php elseif (isset($item['accordion'])): ?>
                                <a href="#" class="internet-checkbox-link" onclick="event.preventDefault(); openMnoPrivacyModal('<?php echo $key; ?>'); return false;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                        <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                    </svg>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php if ($key === 'serviceNotice'): ?>
                            <div class="internet-accordion-content" id="mnoServiceNoticeContent">
                                <div class="internet-accordion-inner">
                                    <div class="internet-accordion-section">
                                        <div style="font-size: 0.875rem; color: #6b7280; line-height: 1.65;">
                                            <?php echo $setting['content'] ?? ''; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php elseif ($key === 'marketing'): ?>
                            <div class="internet-accordion-content" id="mnoMarketingContent">
                                <div class="internet-accordion-inner">
                                    <div class="internet-accordion-section">
                                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 0.75rem 0;">광고성 정보를 받으시려면 아래 항목을 선택해주세요</p>
                                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                <input type="checkbox" id="mnoMarketingEmail" name="marketing_email_opt_in" class="mno-marketing-channel" style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;">
                                                <span style="font-size: 0.875rem; color: #374151;">이메일 수신동의</span>
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                <input type="checkbox" id="mnoMarketingSmsSns" name="marketing_sms_sns_opt_in" class="mno-marketing-channel" style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;">
                                                <span style="font-size: 0.875rem; color: #374151;">SMS, SNS 수신동의</span>
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                <input type="checkbox" id="mnoMarketingPush" name="marketing_push_opt_in" class="mno-marketing-channel" style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;">
                                                <span style="font-size: 0.875rem; color: #374151;">앱 푸시 수신동의</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
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
            <h3 class="review-modal-title"><?php echo htmlspecialchars($sellerName ?: ($phone['company_name'] ?? '통신사폰')); ?></h3>
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
                </div>
                <div class="review-modal-categories">
                    <div class="review-modal-category">
                        <span class="review-modal-category-label">친절해요</span>
                        <span class="review-modal-category-score"><?php echo htmlspecialchars($categoryAverages['kindness'] > 0 ? number_format($categoryAverages['kindness'], 1) : '0.0'); ?></span>
                        <div class="review-modal-stars">
                            <span><?php echo getPartialStarsFromRating($categoryAverages['kindness']); ?></span>
                        </div>
                    </div>
                    <div class="review-modal-category">
                        <span class="review-modal-category-label">개통 빨라요</span>
                        <span class="review-modal-category-score"><?php echo htmlspecialchars($categoryAverages['speed'] > 0 ? number_format($categoryAverages['speed'], 1) : '0.0'); ?></span>
                        <div class="review-modal-stars">
                            <span><?php echo getPartialStarsFromRating($categoryAverages['speed']); ?></span>
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
                <?php if (!empty($allReviews)): ?>
                    <?php foreach ($allReviews as $review): ?>
                        <div class="review-modal-item">
                            <div class="review-modal-item-header">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <?php 
                                    $authorName = htmlspecialchars($review['author_name'] ?? '익명');
                                    $provider = isset($review['provider']) && $review['provider'] ? htmlspecialchars($review['provider']) : '';
                                    ?>
                                    <span class="review-modal-author"><?php echo $authorName; ?></span>
                                    <?php if ($provider): ?>
                                        <span class="plan-review-provider-badge"><?php echo $provider; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="review-modal-stars">
                                        <span><?php echo htmlspecialchars($review['stars'] ?? '★★★★☆'); ?></span>
                                    </div>
                                    <?php if (!empty($review['created_at'])): ?>
                                        <span class="review-modal-time" style="font-size: 0.875rem; color: #6b7280;">
                                            <?php echo htmlspecialchars(getRelativeTime($review['created_at'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="review-modal-item-content"><?php 
                                $content = $review['content'] ?? '';
                                // 줄바꿈 문자들을 공백 하나로 변환 (기존 공백은 유지)
                                // \r\n을 먼저 공백으로 변환, 그 다음 \r, \n을 각각 공백으로 변환
                                $content = str_replace(["\r\n", "\r", "\n"], ' ', $content);
                                echo htmlspecialchars($content);
                            ?></p>
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
            <button class="review-modal-more-btn" id="phoneReviewModalMoreBtn" style="display: none;">리뷰 더보기</button>
        </div>
    </div>
</div>

<script>
// 관리자 페이지 설정 로드 (DB의 app_settings 테이블)
<?php
// 이미 위에서 로드했으므로 재사용 (일관성 유지)
// $privacySettings는 12줄에서 이미 로드됨
echo "const mnoPrivacyContents = " . json_encode($privacySettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
?>

// 아코디언 토글 함수 (전역으로 노출)
function toggleMnoAccordionByArrow(accordionId, arrowLinkId) {
    const accordion = document.getElementById(accordionId);
    const arrowLink = document.getElementById(arrowLinkId);
    
    if (!accordion || !arrowLink) return;
    
    // 현재 상태 확인
    const isOpen = accordion.classList.contains('active');
    
    // 상태 토글
    const newState = !isOpen;
    
    if (newState) {
        accordion.classList.add('active');
        arrowLink.classList.add('arrow-up');
    } else {
        accordion.classList.remove('active');
        arrowLink.classList.remove('arrow-up');
    }
}

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
    
    // 개인정보 내용 정의 (설정 파일에서 로드)
    <?php
    require_once __DIR__ . '/../includes/data/privacy-functions.php';
    $privacySettings = getPrivacySettings();
    echo "const privacyContents = " . json_encode($privacySettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
    ?>
    
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
    
    // 할인방법 선택 모달 관련
    const discountSelectionModal = document.getElementById('discountSelectionModal');
    const discountSelectionModalOverlay = document.getElementById('discountSelectionModalOverlay');
    const discountSelectionModalClose = document.getElementById('discountSelectionModalClose');
    const discountSelectionTableBody = document.getElementById('discountSelectionTableBody');
    
    // 할인방법 데이터
    const discountData = <?php echo json_encode($discountData, JSON_UNESCAPED_UNICODE); ?>;
    
    // 할인방법 선택 모달 열기
    function openDiscountSelectionModal() {
        if (!discountSelectionModal) return;
        
        // 테이블 초기화
        if (discountSelectionTableBody) {
            discountSelectionTableBody.innerHTML = '';
            
            // 할인 옵션 데이터 구성
            const discountOptions = [];
            
            // 공통지원할인 처리
            if (discountData.common_support && Array.isArray(discountData.common_support)) {
                discountData.common_support.forEach(item => {
                    const provider = item.provider || '';
                    
                    // 신규가입
                    if (item.new_subscription !== undefined && item.new_subscription !== 9999 && item.new_subscription !== '9999') {
                        discountOptions.push({
                            provider: provider,
                            discountType: '공통지원할인',
                            subscriptionType: '신규가입',
                            amount: item.new_subscription
                        });
                    }
                    
                    // 번호이동
                    if (item.number_port !== undefined && item.number_port !== 9999 && item.number_port !== '9999') {
                        discountOptions.push({
                            provider: provider,
                            discountType: '공통지원할인',
                            subscriptionType: '번호이동',
                            amount: item.number_port
                        });
                    }
                    
                    // 기기변경
                    if (item.device_change !== undefined && item.device_change !== 9999 && item.device_change !== '9999') {
                        discountOptions.push({
                            provider: provider,
                            discountType: '공통지원할인',
                            subscriptionType: '기기변경',
                            amount: item.device_change
                        });
                    }
                });
            }
            
            // 선택약정할인 처리
            if (discountData.contract_support && Array.isArray(discountData.contract_support)) {
                discountData.contract_support.forEach(item => {
                    const provider = item.provider || '';
                    
                    // 신규가입
                    if (item.new_subscription !== undefined && item.new_subscription !== 9999 && item.new_subscription !== '9999') {
                        discountOptions.push({
                            provider: provider,
                            discountType: '선택약정할인',
                            subscriptionType: '신규가입',
                            amount: item.new_subscription
                        });
                    }
                    
                    // 번호이동
                    if (item.number_port !== undefined && item.number_port !== 9999 && item.number_port !== '9999') {
                        discountOptions.push({
                            provider: provider,
                            discountType: '선택약정할인',
                            subscriptionType: '번호이동',
                            amount: item.number_port
                        });
                    }
                    
                    // 기기변경
                    if (item.device_change !== undefined && item.device_change !== 9999 && item.device_change !== '9999') {
                        discountOptions.push({
                            provider: provider,
                            discountType: '선택약정할인',
                            subscriptionType: '기기변경',
                            amount: item.device_change
                        });
                    }
                });
            }
            
            // 통신사별로 그룹화, 그리고 할인종류별로도 그룹화
            const groupedByProviderAndDiscount = {};
            discountOptions.forEach(option => {
                const key = `${option.provider}_${option.discountType}`;
                if (!groupedByProviderAndDiscount[key]) {
                    groupedByProviderAndDiscount[key] = {
                        provider: option.provider,
                        discountType: option.discountType,
                        options: []
                    };
                }
                groupedByProviderAndDiscount[key].options.push(option);
            });
            
            // 통신사별로 다시 그룹화 (같은 통신사의 항목들을 묶기 위해)
            const finalGrouped = {};
            Object.keys(groupedByProviderAndDiscount).forEach(key => {
                const item = groupedByProviderAndDiscount[key];
                if (!finalGrouped[item.provider]) {
                    finalGrouped[item.provider] = [];
                }
                finalGrouped[item.provider].push(item);
            });
            
            // 테이블 행 생성
            Object.keys(finalGrouped).forEach(provider => {
                const providerGroups = finalGrouped[provider];
                let providerRowSpan = 0;
                
                // 통신사별 총 행 개수 계산
                providerGroups.forEach(group => {
                    providerRowSpan += group.options.length;
                });
                
                providerGroups.forEach((group, groupIndex) => {
                    group.options.forEach((option, optionIndex) => {
                        const row = document.createElement('tr');
                        
                        // 통신사 셀 (첫 번째 그룹의 첫 번째 옵션에만 표시)
                        if (groupIndex === 0 && optionIndex === 0) {
                            const providerCell = document.createElement('td');
                            providerCell.textContent = provider;
                            providerCell.rowSpan = providerRowSpan;
                            providerCell.className = 'discount-provider-cell';
                            row.appendChild(providerCell);
                        }
                        
                        // 할인종류 셀 (각 그룹의 첫 번째 옵션에만 표시)
                        if (optionIndex === 0) {
                            const discountTypeCell = document.createElement('td');
                            discountTypeCell.textContent = group.discountType;
                            discountTypeCell.rowSpan = group.options.length;
                            discountTypeCell.className = 'discount-type-cell';
                            row.appendChild(discountTypeCell);
                        }
                        
                        // 가입유형
                        const subscriptionTypeCell = document.createElement('td');
                        subscriptionTypeCell.textContent = option.subscriptionType;
                        row.appendChild(subscriptionTypeCell);
                        
                        // 할인금액 버튼
                        const amountCell = document.createElement('td');
                        const amountButton = document.createElement('button');
                        amountButton.type = 'button';
                        amountButton.className = 'discount-amount-button';
                        
                        // 금액 포맷팅 (음수인 경우 포함, 소수점 처리, 원 제거)
                        const amount = parseFloat(option.amount);
                        let formattedAmount;
                        if (amount % 1 === 0) {
                            // 정수인 경우
                            formattedAmount = amount < 0 
                                ? `-${Math.abs(amount).toLocaleString('ko-KR')}`
                                : `${amount.toLocaleString('ko-KR')}`;
                        } else {
                            // 소수점이 있는 경우
                            formattedAmount = amount < 0 
                                ? `-${Math.abs(amount).toLocaleString('ko-KR', { minimumFractionDigits: 1, maximumFractionDigits: 2 })}`
                                : `${amount.toLocaleString('ko-KR', { minimumFractionDigits: 1, maximumFractionDigits: 2 })}`;
                        }
                        
                        amountButton.textContent = formattedAmount;
                        amountButton.setAttribute('data-provider', option.provider);
                        amountButton.setAttribute('data-discount-type', option.discountType);
                        amountButton.setAttribute('data-subscription-type', option.subscriptionType);
                        amountButton.setAttribute('data-amount', option.amount);
                        
                        // 버튼 클릭 이벤트
                        amountButton.addEventListener('click', function() {
                            // data-amount 값을 그대로 가져오기 (0도 포함)
                            const amountValue = this.getAttribute('data-amount');
                            handleDiscountSelection(
                                this.getAttribute('data-provider'),
                                this.getAttribute('data-discount-type'),
                                this.getAttribute('data-subscription-type'),
                                amountValue // 0인 경우도 그대로 전달
                            );
                        });
                        
                        amountCell.appendChild(amountButton);
                        row.appendChild(amountCell);
                        
                        discountSelectionTableBody.appendChild(row);
                    });
                });
            });
            
            // 할인 옵션이 없으면 메시지 표시
            if (discountOptions.length === 0 && discountSelectionTableBody.parentElement) {
                const emptyRow = document.createElement('tr');
                const emptyCell = document.createElement('td');
                emptyCell.colSpan = 4;
                emptyCell.className = 'discount-empty-cell';
                emptyCell.textContent = '판매 가능한 할인 방법이 없습니다.';
                emptyCell.style.textAlign = 'center';
                emptyCell.style.padding = '40px 20px';
                emptyCell.style.color = '#9ca3af';
                emptyRow.appendChild(emptyCell);
                discountSelectionTableBody.appendChild(emptyRow);
            }
        }
        
        scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        const scrollbarWidth = getScrollbarWidth();
        
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollPosition}px`;
        document.body.style.width = '100%';
        document.body.style.paddingRight = `${scrollbarWidth}px`;
        document.documentElement.style.overflow = 'hidden';
        
        discountSelectionModal.style.display = 'flex';
        discountSelectionModal.classList.add('discount-selection-modal-active');
    }
    
    // 할인방법 선택 모달 닫기
    function closeDiscountSelectionModal() {
        if (!discountSelectionModal) return;
        
        discountSelectionModal.classList.remove('discount-selection-modal-active');
        
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        document.body.style.paddingRight = '';
        document.documentElement.style.overflow = '';
        
        window.scrollTo(0, scrollPosition);
    }
    
    // 할인방법 선택 처리
    function handleDiscountSelection(provider, discountType, subscriptionType, amount) {
        // amount가 '0'인 경우도 정상적으로 처리
        console.log('handleDiscountSelection - amount:', amount, 'type:', typeof amount);
        // 선택한 할인 방법 정보 저장
        window.selectedDiscountInfo = {
            provider: provider,
            discountType: discountType,
            subscriptionType: subscriptionType,
            amount: amount
        };
        
        // 등록된 색상이 있으면 색상 선택 모달 열기, 없으면 바로 가입신청 모달 열기
        const deviceColors = <?php echo json_encode($phone['device_colors'] ?? [], JSON_UNESCAPED_UNICODE); ?>;
        if (Array.isArray(deviceColors) && deviceColors.length > 0) {
            // 연속적인 느낌을 위해 할인방법 모달을 닫지 않고 바로 색상 모달 열기
            // 할인방법 모달 닫기
            closeDiscountSelectionModal();
            // 바로 색상 선택 모달 열기 (딜레이 최소화)
            setTimeout(() => {
                openDeviceColorSelectionModal(deviceColors);
            }, 150);
        } else {
            // 색상이 없으면 할인방법 모달 닫고 가입신청 모달 열기
            closeDiscountSelectionModal();
            setTimeout(() => {
                openConsultationModal(provider, discountType, subscriptionType, amount);
            }, 150);
        }
    }
    
    // 할인방법 선택 모달 닫기 이벤트
    if (discountSelectionModalOverlay) {
        discountSelectionModalOverlay.addEventListener('click', closeDiscountSelectionModal);
    }
    
    if (discountSelectionModalClose) {
        discountSelectionModalClose.addEventListener('click', closeDiscountSelectionModal);
    }
    
    // 단말기 색상 선택 모달 관련
    const deviceColorSelectionModal = document.getElementById('deviceColorSelectionModal');
    const deviceColorSelectionModalOverlay = document.getElementById('deviceColorSelectionModalOverlay');
    const deviceColorSelectionModalClose = document.getElementById('deviceColorSelectionModalClose');
    const deviceColorConfirmBtn = document.getElementById('deviceColorConfirmBtn');
    let selectedColors = [];
    
    // 단말기 색상 선택 모달 열기
    function openDeviceColorSelectionModal(colors) {
        if (!deviceColorSelectionModal) return;
        
        selectedColors = [];
        const colorContainer = document.getElementById('device-colors-selection-container');
        const confirmBtn = deviceColorConfirmBtn;
        
        if (!colorContainer) return;
        
        // 색상 버튼 생성 (1개만 선택 가능)
        colorContainer.innerHTML = '';
        if (Array.isArray(colors) && colors.length > 0) {
            colors.forEach(colorName => {
                const colorButton = document.createElement('button');
                colorButton.type = 'button';
                colorButton.className = 'discount-amount-button';
                colorButton.textContent = colorName;
                colorButton.setAttribute('data-color', colorName);
                colorButton.style.cssText = 'padding: 12px 24px; font-size: 14px; font-weight: 600; border: 2px solid #e5e7eb; border-radius: 8px; background: white; color: #374151; cursor: pointer; transition: all 0.2s;';
                
                // 버튼 클릭 이벤트 (1개만 선택)
                colorButton.addEventListener('click', function() {
                    // 모든 버튼 초기화
                    const allButtons = colorContainer.querySelectorAll('.discount-amount-button');
                    allButtons.forEach(btn => {
                        btn.style.borderColor = '#e5e7eb';
                        btn.style.background = 'white';
                        btn.style.color = '#374151';
                    });
                    
                    // 선택한 버튼 활성화 (리뷰 작성 버튼과 동일한 색상)
                    this.style.borderColor = '#6366f1';
                    this.style.background = '#6366f1';
                    this.style.color = 'white';
                    
                    // 선택한 색상 저장 (1개만)
                    selectedColors = [this.getAttribute('data-color')];
                    
                    // 선택 완료 버튼 활성화 (리뷰 작성 버튼과 동일한 색상)
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = '선택 완료';
                        confirmBtn.style.background = '#6366f1';
                    }
                });
                
                colorContainer.appendChild(colorButton);
            });
        } else {
            colorContainer.innerHTML = '<div style="width: 100%; color: #6b7280; font-size: 14px; text-align: center;">등록된 색상이 없습니다.</div>';
        }
        
        // 선택 완료 버튼 초기화
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.textContent = '색상을 선택해주세요';
            confirmBtn.style.background = '#9ca3af';
        }
        
        // 모달 열기 (연속적인 느낌을 위해 딜레이 없이 바로 열기)
        scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        const scrollbarWidth = getScrollbarWidth();
        
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollPosition}px`;
        document.body.style.width = '100%';
        document.body.style.paddingRight = `${scrollbarWidth}px`;
        document.documentElement.style.overflow = 'hidden';
        
        deviceColorSelectionModal.style.display = 'flex';
        deviceColorSelectionModal.classList.add('discount-selection-modal-active');
    }
    
    // 단말기 색상 선택 모달 닫기
    function closeDeviceColorSelectionModal() {
        if (!deviceColorSelectionModal) return;
        
        deviceColorSelectionModal.classList.remove('discount-selection-modal-active');
        
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        document.body.style.paddingRight = '';
        document.documentElement.style.overflow = '';
        
        window.scrollTo(0, scrollPosition);
    }
    
    // 색상 선택 모달 이벤트
    if (deviceColorSelectionModalOverlay) {
        deviceColorSelectionModalOverlay.addEventListener('click', closeDeviceColorSelectionModal);
    }
    
    if (deviceColorSelectionModalClose) {
        deviceColorSelectionModalClose.addEventListener('click', closeDeviceColorSelectionModal);
    }
    
    // 선택 완료 버튼 클릭 이벤트
    if (deviceColorConfirmBtn) {
        deviceColorConfirmBtn.addEventListener('click', function() {
            if (selectedColors.length === 0) {
                alert('색상을 선택해주세요.');
                return;
            }
            
            // 선택한 할인 정보와 색상 정보로 가입신청 모달 열기
            const discountInfo = window.selectedDiscountInfo || {};
            
            // 연속적인 느낌을 위해 색상 모달을 닫지 않고 바로 가입신청 모달 열기
            closeDeviceColorSelectionModal();
            setTimeout(() => {
                openConsultationModal(
                    discountInfo.provider,
                    discountInfo.discountType,
                    discountInfo.subscriptionType,
                    discountInfo.amount,
                    selectedColors
                );
            }, 150);
        });
    }
    
    // 상담신청 모달 열기 (할인 정보 파라미터 추가)
    function openConsultationModal(selectedProvider, selectedDiscountType, selectedSubscriptionType, selectedAmount, selectedDeviceColors = []) {
        console.log('openConsultationModal - selectedAmount:', selectedAmount, 'type:', typeof selectedAmount);
        if (!consultationModal) return;
        
        // 버튼 상태 초기화
        const submitBtn = document.getElementById('consultationSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = '신청하기';
        }
        
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
        
        // 모달이 열릴 때 전체 동의 체크박스 이벤트 다시 등록
        setTimeout(() => {
            const mnoAgreementAll = document.getElementById('mnoAgreementAll');
            const mnoAgreementItemCheckboxes = document.querySelectorAll('.internet-checkbox-input-item');
            
            if (mnoAgreementAll) {
                // 기존 이벤트 리스너 제거 후 재등록 (중복 방지)
                const newAgreementAll = mnoAgreementAll.cloneNode(true);
                mnoAgreementAll.parentNode.replaceChild(newAgreementAll, mnoAgreementAll);
                
                newAgreementAll.addEventListener('change', function() {
                    const isChecked = this.checked;
                    const checkboxes = document.querySelectorAll('.internet-checkbox-input-item');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = isChecked;
                        // change 이벤트 트리거
                        const changeEvent = new Event('change', { bubbles: true });
                        checkbox.dispatchEvent(changeEvent);
                    });
                    checkAllMnoAgreements();
                });
            }
        }, 100);
        
        // 모달이 열릴 때 기존 값이 있으면 즉시 검증 (지연 없이)
        const phoneInput = document.getElementById('consultationPhone');
        const emailInput = document.getElementById('consultationEmail');
        const phoneErrorElement = document.getElementById('consultationPhoneError');
        const emailErrorElement = document.getElementById('consultationEmailError');
        
        // 전화번호 즉시 검증
        if (phoneInput && phoneErrorElement) {
            const phoneValue = phoneInput.value.trim();
            const phoneNumbers = phoneValue.replace(/[^\d]/g, '');
            
            if (phoneValue && phoneNumbers.length > 0) {
                if (phoneNumbers.length === 11 && phoneNumbers.startsWith('010')) {
                    phoneInput.classList.remove('input-error');
                    phoneErrorElement.style.display = 'none';
                    phoneErrorElement.textContent = '';
                } else {
                    phoneInput.classList.add('input-error');
                    phoneErrorElement.style.display = 'block';
                    phoneErrorElement.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                }
            } else if (phoneValue) {
                phoneInput.classList.add('input-error');
                phoneErrorElement.style.display = 'block';
                phoneErrorElement.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
            }
        }
        
        // 이메일 즉시 검증
        if (emailInput && emailErrorElement) {
            const emailValue = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (emailValue.length > 0) {
                if (emailRegex.test(emailValue)) {
                    emailInput.classList.remove('input-error');
                    emailErrorElement.style.display = 'none';
                    emailErrorElement.textContent = '';
                } else {
                    emailInput.classList.add('input-error');
                    emailErrorElement.style.display = 'block';
                    emailErrorElement.textContent = '이메일 형식에 맞게 입력해주세요. (example@email.com 형식)';
                }
            }
        }
        
        // DOM 업데이트 후 재검증
        setTimeout(function() {
            checkAllMnoAgreements();
        }, 50);
        
        // 선택한 할인 정보를 폼에 저장 (나중에 제출 시 함께 전송)
        if (selectedProvider) {
            const hiddenInput = consultationForm.querySelector('input[name="selected_provider"]');
            if (hiddenInput) {
                hiddenInput.value = selectedProvider;
            } else {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_provider';
                input.value = selectedProvider;
                consultationForm.appendChild(input);
            }
        }
        if (selectedDiscountType) {
            const hiddenInput = consultationForm.querySelector('input[name="selected_discount_type"]');
            if (hiddenInput) {
                hiddenInput.value = selectedDiscountType;
            } else {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_discount_type';
                input.value = selectedDiscountType;
                consultationForm.appendChild(input);
            }
        }
        if (selectedSubscriptionType) {
            const hiddenInput = consultationForm.querySelector('input[name="selected_subscription_type"]');
            if (hiddenInput) {
                hiddenInput.value = selectedSubscriptionType;
            } else {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_subscription_type';
                input.value = selectedSubscriptionType;
                consultationForm.appendChild(input);
            }
        }
        // selectedAmount 처리: undefined, null, 빈 문자열이 아닌 경우 모두 처리 (0도 포함)
        // option.amount가 0일 때 그대로 0을 사용하도록 함
        if (selectedAmount !== undefined && selectedAmount !== null && selectedAmount !== '') {
            const hiddenInput = consultationForm.querySelector('input[name="selected_amount"]');
            if (hiddenInput) {
                // 0인 경우도 그대로 저장 ('0' 또는 0 모두 허용)
                hiddenInput.value = String(selectedAmount);
            } else {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_amount';
                // 0인 경우도 그대로 저장 ('0' 또는 0 모두 허용)
                input.value = String(selectedAmount);
                consultationForm.appendChild(input);
            }
        } else if (selectedAmount === '0' || selectedAmount === 0) {
            // selectedAmount가 0인 경우 명시적으로 처리
            const hiddenInput = consultationForm.querySelector('input[name="selected_amount"]');
            if (hiddenInput) {
                hiddenInput.value = '0';
            } else {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_amount';
                input.value = '0';
                consultationForm.appendChild(input);
            }
        }
        
        // 선택한 단말기 색상 정보 저장 (1개만 선택 가능)
        if (selectedDeviceColors && Array.isArray(selectedDeviceColors) && selectedDeviceColors.length > 0) {
            // 기존 색상 입력 필드 제거
            const existingColorInputs = consultationForm.querySelectorAll('input[name="device_color[]"]');
            existingColorInputs.forEach(input => input.remove());
            
            // 선택한 색상을 hidden input으로 추가 (1개만)
            const selectedColor = selectedDeviceColors[0];
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'device_color[]';
            input.value = selectedColor;
            consultationForm.appendChild(input);
        }
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
    
    // 개인정보 내용보기 모달 열기 (전역으로 노출)
    window.openMnoPrivacyModal = function(type) {
        const modal = document.getElementById('privacyContentModal');
        const modalTitle = document.getElementById('privacyContentModalTitle');
        const modalBody = document.getElementById('privacyContentModalBody');
        
        if (!modal || !modalTitle || !modalBody) return;
        
        // mnoPrivacyContents 우선 사용, 없으면 privacyContents 사용
        const contents = (typeof mnoPrivacyContents !== 'undefined' && mnoPrivacyContents) ? mnoPrivacyContents : privacyContents;
        
        if (!contents || !contents[type]) return;
        
        modalTitle.textContent = contents[type].title || '';
        modalBody.innerHTML = contents[type].content || '';
        
        // 모달 내 배지 제거 (필수/선택 표시 제거)
        if (type === 'serviceNotice' || type === 'marketing') {
            // serviceNotice의 경우
            if (type === 'serviceNotice') {
                const header = modalBody.querySelector('.privacy-service-notice-header');
                if (header) {
                    const badge = header.querySelector('.required-badge, .optional-badge');
                    if (badge) {
                        badge.remove();
                    }
                }
            }
            
            // marketing의 경우
            if (type === 'marketing') {
                const header = modalBody.querySelector('.privacy-marketing-header');
                if (header) {
                    const badge = header.querySelector('.required-badge, .optional-badge');
                    if (badge) {
                        badge.remove();
                    }
                }
            }
        }
        
        modal.style.display = 'flex';
        modal.classList.add('privacy-content-modal-active');
        document.body.style.overflow = 'hidden';
    };
    
    // 개인정보 내용보기 모달 닫기
    function closePrivacyModal() {
        if (!privacyModal) return;
        
        privacyModal.classList.remove('privacy-content-modal-active');
        privacyModal.style.display = 'none';
        document.body.style.overflow = '';
    }
    
    // 신청하기 버튼 클릭 이벤트
    if (applyBtn) {
        applyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // 로그인 체크
            const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
            if (!isLoggedIn) {
                // 비회원: 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
                const currentUrl = window.location.href;
                fetch('/MVNO/api/save-redirect-url.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ redirect_url: currentUrl })
                }).then(() => {
                    // 로그인 모달 열기
                    if (typeof openLoginModal === 'function') {
                        openLoginModal(false);
                    } else {
                        setTimeout(() => {
                            if (typeof openLoginModal === 'function') {
                                openLoginModal(false);
                            }
                        }, 100);
                    }
                });
                return;
            }
            
            // 포인트 설정 확인 후 할인방법 선택 모달 열기
            const productId = <?php echo isset($_GET['id']) ? intval($_GET['id']) : 0; ?>;
            checkAndOpenPointModal('mno', productId, openDiscountSelectionModal);
        });
    }
    
    // 상담신청 모달 닫기 이벤트
    if (consultationModalOverlay) {
        consultationModalOverlay.addEventListener('click', closeConsultationModal);
    }
    
    if (consultationModalClose) {
        consultationModalClose.addEventListener('click', closeConsultationModal);
    }
    
    // 전체 동의 체크박스 및 개별 체크박스 이벤트 등록 (알뜰폰 모달과 동일한 방식)
    const mnoAgreementAll = document.getElementById('mnoAgreementAll');
    const mnoAgreementItemCheckboxes = document.querySelectorAll('.internet-checkbox-input-item');
    
    // 전체 동의 체크박스 변경 이벤트
    if (mnoAgreementAll) {
        mnoAgreementAll.addEventListener('change', function() {
            const isChecked = this.checked;
            mnoAgreementItemCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
                // change 이벤트 트리거
                const changeEvent = new Event('change', { bubbles: true });
                checkbox.dispatchEvent(changeEvent);
            });
            checkAllMnoAgreements();
        });
    }
    
    // 개별 체크박스 변경 이벤트 (전체 동의 상태 업데이트)
    mnoAgreementItemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            checkAllMnoAgreements();
            // 마케팅 체크박스인 경우 채널 토글
            if (this.id === 'mnoAgreementMarketing') {
                toggleMnoMarketingChannels();
            }
        });
    });
    
    // 전체 동의 토글 함수 (하위 호환성을 위해 유지)
    function toggleAllMnoAgreements(checked) {
        // 모든 개별 체크박스를 찾아서 체크/해제
        const agreementItemCheckboxes = document.querySelectorAll('.internet-checkbox-input-item');
        agreementItemCheckboxes.forEach(checkbox => {
            checkbox.checked = checked;
            // change 이벤트를 수동으로 트리거하여 다른 이벤트 리스너도 실행되도록
            const changeEvent = new Event('change', { bubbles: true });
            checkbox.dispatchEvent(changeEvent);
            // 마케팅 체크박스인 경우 채널 토글
            if (checkbox.id === 'mnoAgreementMarketing' && checked) {
                toggleMnoMarketingChannels();
            }
        });
        
        // 개별 체크박스 변경 이벤트를 트리거하여 버튼 상태 업데이트
        checkAllMnoAgreements();
    }
    
    // 전체 동의 상태 확인 함수
    function checkAllMnoAgreements() {
        const agreementAll = document.getElementById('mnoAgreementAll');
        const submitBtn = document.getElementById('consultationSubmitBtn');
        const nameInput = document.getElementById('consultationName');
        const phoneInput = document.getElementById('consultationPhone');
        const emailInput = document.getElementById('consultationEmail');

        if (!agreementAll || !submitBtn) return;

        // 필수 항목 목록 생성 (노출된 필수 항목만 포함)
        const requiredItems = [];
        const agreementMap = {
            'purpose': 'mnoAgreementPurpose',
            'items': 'mnoAgreementItems',
            'period': 'mnoAgreementPeriod',
            'thirdParty': 'mnoAgreementThirdParty',
            'serviceNotice': 'mnoAgreementServiceNotice',
            'marketing': 'mnoAgreementMarketing'
        };

        if (typeof mnoPrivacyContents !== 'undefined') {
            for (const [key, id] of Object.entries(agreementMap)) {
                const setting = mnoPrivacyContents[key];
                if (!setting) continue;
                
                const isVisible = setting.isVisible !== false;
                if (setting.isRequired === true && isVisible) {
                    requiredItems.push(id);
                }
            }
        } else {
            // 기본값: marketing 제외 모두 필수
            requiredItems.push('mnoAgreementPurpose', 'mnoAgreementItems', 'mnoAgreementPeriod', 'mnoAgreementThirdParty', 'mnoAgreementServiceNotice');
        }

        // 전체 동의 체크박스 상태 업데이트 (모든 체크박스 확인)
        const allCheckboxes = document.querySelectorAll('.internet-checkbox-input-item');
        let allChecked = true;
        if (allCheckboxes.length > 0) {
            allCheckboxes.forEach(checkbox => {
                if (!checkbox.checked) {
                    allChecked = false;
                }
            });
        } else {
            // 체크박스가 없으면 필수 항목만 확인
            allChecked = true;
            for (const itemId of requiredItems) {
                const checkbox = document.getElementById(itemId);
                if (checkbox && !checkbox.checked) {
                    allChecked = false;
                    break;
                }
            }
        }
        if (agreementAll) {
            agreementAll.checked = allChecked;
        }

        // 개인정보 입력 검증
        const name = nameInput ? nameInput.value.trim() : '';
        const phone = phoneInput ? phoneInput.value.replace(/[^\d]/g, '') : '';
        const email = emailInput ? emailInput.value.trim() : '';

        const isNameValid = name.length > 0;
        const isPhoneValid = phone.length === 11 && phone.startsWith('010');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isEmailValid = email.length > 0 && emailRegex.test(email);
        
        // 필수 동의 항목 체크 여부 확인
        let isAgreementsChecked = true;
        for (const itemId of requiredItems) {
            const checkbox = document.getElementById(itemId);
            if (checkbox && !checkbox.checked) {
                isAgreementsChecked = false;
                break;
            }
        }

        // 버튼 활성화 조건: 필수 항목 모두 체크 + 개인정보 입력 완료
        submitBtn.disabled = !(isNameValid && isPhoneValid && isEmailValid && isAgreementsChecked);
    }
    
    // 서비스 이용 및 혜택 안내 알림 채널 활성화/비활성화 토글 함수
    function toggleMnoServiceNoticeChannels() {
        const agreementServiceNotice = document.getElementById('mnoAgreementServiceNotice');
        const serviceNoticeChannels = document.querySelectorAll('.mno-service-notice-channel');
        
        if (agreementServiceNotice && serviceNoticeChannels.length > 0) {
            const isEnabled = agreementServiceNotice.checked;
            serviceNoticeChannels.forEach(channel => {
                channel.disabled = !isEnabled;
                if (!isEnabled) {
                    channel.checked = false;
                }
            });
        }
    }
    
    // 마케팅 채널 활성화/비활성화 토글 함수
    function toggleMnoMarketingChannels() {
        const agreementMarketing = document.getElementById('mnoAgreementMarketing');
        const marketingChannels = document.querySelectorAll('.mno-marketing-channel');
        
        if (agreementMarketing && marketingChannels.length > 0) {
            const isEnabled = agreementMarketing.checked;
            marketingChannels.forEach(channel => {
                channel.disabled = !isEnabled;
                if (isEnabled) {
                    // 활성화 시 모든 체크박스 자동 체크
                    channel.checked = true;
                } else {
                    // 비활성화 시 모든 체크박스 해제
                    channel.checked = false;
                }
            });
        }
    }
    
    // 마케팅 채널 변경 시 상위 체크박스 업데이트
    document.addEventListener('DOMContentLoaded', function() {
        const marketingChannels = document.querySelectorAll('.mno-marketing-channel');
        const agreementMarketing = document.getElementById('mnoAgreementMarketing');
        
        marketingChannels.forEach(channel => {
            channel.addEventListener('change', function() {
                if (agreementMarketing) {
                    const anyChecked = Array.from(marketingChannels).some(ch => ch.checked);
                    if (anyChecked && !agreementMarketing.checked) {
                        // 상위 토글 체크
                        agreementMarketing.checked = true;
                        // 모든 하위 체크박스 자동 체크
                        toggleMnoMarketingChannels();
                    } else if (!anyChecked && agreementMarketing.checked) {
                        // 모든 하위 체크박스가 해제되면 상위 토글도 해제
                        agreementMarketing.checked = false;
                        toggleMnoMarketingChannels();
                    }
                }
            });
        });
        
        // 초기 상태 설정
        toggleMnoMarketingChannels();
    });
    
    // 아코디언 토글 함수
    function toggleMnoAccordion(accordionId, arrowLink) {
        const accordion = document.getElementById(accordionId);
        if (!accordion || !arrowLink) return;
        
        const isOpen = accordion.classList.contains('active');
        if (isOpen) {
            accordion.classList.remove('active');
            arrowLink.classList.remove('arrow-up');
        } else {
            accordion.classList.add('active');
            arrowLink.classList.add('arrow-up');
        }
    }
    
    
    // 개인정보 내용보기 버튼 클릭 이벤트는 아코디언으로 대체됨 (인라인 아코디언 사용)
    
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
            } else if (discountSelectionModal && discountSelectionModal.classList.contains('discount-selection-modal-active')) {
                closeDiscountSelectionModal();
            } else if (consultationModal && consultationModal.classList.contains('consultation-modal-active')) {
                closeConsultationModal();
            }
        }
    });
    
    // 휴대폰번호 검증 함수
    function validatePhoneNumber(phone) {
        // 숫자만 추출
        const phoneNumbers = phone.replace(/[^\d]/g, '');
        // 010으로 시작하는 11자리 숫자 확인
        return /^010\d{8}$/.test(phoneNumbers);
    }
    
    // 이메일 검증 함수
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email.trim());
    }
    
    // 휴대폰번호 포맷팅 함수
    function formatPhoneNumber(phone) {
        const phoneNumbers = phone.replace(/[^\d]/g, '');
        if (phoneNumbers.length === 11 && phoneNumbers.startsWith('010')) {
            return '010-' + phoneNumbers.substring(3, 7) + '-' + phoneNumbers.substring(7, 11);
        }
        return phone;
    }
    
    // 실시간 검증 이벤트
    const consultationPhone = document.getElementById('consultationPhone');
    const consultationEmail = document.getElementById('consultationEmail');
    
    if (consultationPhone) {
        const phoneErrorElement = document.getElementById('consultationPhoneError');
        
        // 입력 중 포맷팅 및 실시간 검증
        consultationPhone.addEventListener('input', function() {
            const value = this.value;
            const formatted = formatPhoneNumber(value);
            if (formatted !== value) {
                this.value = formatted;
            }
            
            // 실시간 검증
            const phoneNumbers = this.value.replace(/[^\d]/g, '');
            if (phoneNumbers.length > 0) {
                if (phoneNumbers.length === 11 && phoneNumbers.startsWith('010')) {
                    this.classList.remove('input-error');
                    if (phoneErrorElement) {
                        phoneErrorElement.style.display = 'none';
                        phoneErrorElement.textContent = '';
                    }
                } else {
                    this.classList.add('input-error');
                    if (phoneErrorElement) {
                        phoneErrorElement.style.display = 'block';
                        phoneErrorElement.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                    }
                }
            } else {
                this.classList.remove('input-error');
                if (phoneErrorElement) {
                    phoneErrorElement.style.display = 'none';
                    phoneErrorElement.textContent = '';
                }
            }
            
            checkAllMnoAgreements();
        });
        
        // 포커스 아웃 시 검증
        consultationPhone.addEventListener('blur', function() {
            const value = this.value.trim();
            const phoneNumbers = value.replace(/[^\d]/g, '');
            
            if (value && phoneNumbers.length > 0) {
                if (phoneNumbers.length === 11 && phoneNumbers.startsWith('010')) {
                    this.classList.remove('input-error');
                    if (phoneErrorElement) {
                        phoneErrorElement.style.display = 'none';
                        phoneErrorElement.textContent = '';
                    }
                } else {
                    this.classList.add('input-error');
                    if (phoneErrorElement) {
                        phoneErrorElement.style.display = 'block';
                        phoneErrorElement.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                    }
                }
            } else if (value) {
                this.classList.add('input-error');
                if (phoneErrorElement) {
                    phoneErrorElement.style.display = 'block';
                    phoneErrorElement.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                }
            } else {
                this.classList.remove('input-error');
                if (phoneErrorElement) {
                    phoneErrorElement.style.display = 'none';
                    phoneErrorElement.textContent = '';
                }
            }
            
            checkAllMnoAgreements();
        });
        
        // 입력 시작 시 에러 제거
        consultationPhone.addEventListener('focus', function() {
            this.classList.remove('input-error');
            if (phoneErrorElement) {
                phoneErrorElement.style.display = 'none';
                phoneErrorElement.textContent = '';
            }
        });
    }
    
    if (consultationEmail) {
        const emailErrorElement = document.getElementById('consultationEmailError');
        
        // 실시간 검증 및 소문자 변환
        consultationEmail.addEventListener('input', function(e) {
            // 대문자를 소문자로 자동 변환
            const cursorPosition = this.selectionStart;
            const originalValue = this.value;
            const lowerValue = originalValue.toLowerCase();
            
            if (originalValue !== lowerValue) {
                this.value = lowerValue;
                const newCursorPosition = Math.min(cursorPosition, lowerValue.length);
                this.setSelectionRange(newCursorPosition, newCursorPosition);
            }
            
            const value = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (value.length > 0) {
                if (emailRegex.test(value)) {
                    this.classList.remove('input-error');
                    if (emailErrorElement) {
                        emailErrorElement.style.display = 'none';
                        emailErrorElement.textContent = '';
                    }
                } else {
                    this.classList.add('input-error');
                    if (emailErrorElement) {
                        emailErrorElement.style.display = 'block';
                        emailErrorElement.textContent = '이메일 형식에 맞게 입력해주세요. (example@email.com 형식)';
                    }
                }
            } else {
                this.classList.remove('input-error');
                if (emailErrorElement) {
                    emailErrorElement.style.display = 'none';
                    emailErrorElement.textContent = '';
                }
            }
            
            checkAllMnoAgreements();
        });
        
        // 포커스 아웃 시 검증
        consultationEmail.addEventListener('blur', function() {
            // 포커스 아웃 시에도 소문자로 변환
            this.value = this.value.toLowerCase();
            
            const value = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (value.length > 0) {
                if (emailRegex.test(value)) {
                    this.classList.remove('input-error');
                    if (emailErrorElement) {
                        emailErrorElement.style.display = 'none';
                        emailErrorElement.textContent = '';
                    }
                } else {
                    this.classList.add('input-error');
                    if (emailErrorElement) {
                        emailErrorElement.style.display = 'block';
                        emailErrorElement.textContent = '이메일 형식에 맞게 입력해주세요. (example@email.com 형식)';
                    }
                }
            } else {
                this.classList.remove('input-error');
                if (emailErrorElement) {
                    emailErrorElement.style.display = 'none';
                    emailErrorElement.textContent = '';
                }
            }
            
            checkAllMnoAgreements();
        });
        
        // 입력 시작 시 에러 제거
        consultationEmail.addEventListener('focus', function() {
            this.classList.remove('input-error');
            if (emailErrorElement) {
                emailErrorElement.style.display = 'none';
                emailErrorElement.textContent = '';
            }
        });
    }
    
    // 이름 입력 시 검증
    const consultationName = document.getElementById('consultationName');
    if (consultationName) {
        consultationName.addEventListener('input', checkAllMnoAgreements);
        consultationName.addEventListener('blur', checkAllMnoAgreements);
    }
    
    // 폼 제출 이벤트
    if (consultationForm) {
        consultationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // 로그인 체크
            const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
            if (!isLoggedIn) {
                // 모달 닫기
                if (consultationModal) {
                    consultationModal.classList.remove('consultation-modal-active');
                    document.body.style.overflow = '';
                    document.body.style.position = '';
                    document.body.style.top = '';
                    document.body.style.width = '';
                    document.documentElement.style.overflow = '';
                }
                
                // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
                const currentUrl = window.location.href;
                fetch('/MVNO/api/save-redirect-url.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ redirect_url: currentUrl })
                }).then(() => {
                    // 회원가입 모달 열기
                    if (typeof openLoginModal === 'function') {
                        openLoginModal(true);
                    } else {
                        setTimeout(() => {
                            if (typeof openLoginModal === 'function') {
                                openLoginModal(true);
                            }
                        }, 100);
                    }
                });
                return;
            }
            
            // 필수 필드 검증
            const consultationName = document.getElementById('consultationName');
            const consultationPhone = document.getElementById('consultationPhone');
            const consultationEmail = document.getElementById('consultationEmail');
            
            // 이름 검증
            if (!consultationName || !consultationName.value.trim()) {
                alert('이름을 입력해주세요.');
                if (consultationName) consultationName.focus();
                return;
            }
            
            // 휴대폰번호 검증
            const phoneErrorElement = document.getElementById('consultationPhoneError');
            if (!consultationPhone || !consultationPhone.value.trim()) {
                if (consultationPhone) {
                    consultationPhone.classList.add('input-error');
                    if (phoneErrorElement) {
                        phoneErrorElement.style.display = 'block';
                        phoneErrorElement.textContent = '휴대폰 번호를 입력해주세요.';
                    }
                    consultationPhone.focus();
                }
                return;
            }
            
            if (!validatePhoneNumber(consultationPhone.value)) {
                if (consultationPhone) {
                    consultationPhone.classList.add('input-error');
                    if (phoneErrorElement) {
                        phoneErrorElement.style.display = 'block';
                        phoneErrorElement.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                    }
                    consultationPhone.focus();
                }
                return;
            }
            
            // 이메일 검증
            const emailErrorElement = document.getElementById('consultationEmailError');
            if (!consultationEmail || !consultationEmail.value.trim()) {
                if (consultationEmail) {
                    consultationEmail.classList.add('input-error');
                    if (emailErrorElement) {
                        emailErrorElement.style.display = 'block';
                        emailErrorElement.textContent = '이메일을 입력해주세요.';
                    }
                    consultationEmail.focus();
                }
                return;
            }
            
            if (!validateEmail(consultationEmail.value)) {
                if (consultationEmail) {
                    consultationEmail.classList.add('input-error');
                    if (emailErrorElement) {
                        emailErrorElement.style.display = 'block';
                        emailErrorElement.textContent = '이메일 형식에 맞게 입력해주세요. (example@email.com 형식)';
                    }
                    consultationEmail.focus();
                }
                return;
            }
            
            // 모든 동의 체크박스 확인 (실제 ID 사용)
            const agreementPurpose = document.getElementById('mnoAgreementPurpose');
            const agreementItems = document.getElementById('mnoAgreementItems');
            const agreementPeriod = document.getElementById('mnoAgreementPeriod');
            
            if (!agreementPurpose || !agreementItems || !agreementPeriod) {
                alert('개인정보 동의 항목을 찾을 수 없습니다.');
                return;
            }
            
            if (!agreementPurpose.checked || !agreementItems.checked || !agreementPeriod.checked) {
                alert('모든 개인정보 동의 항목에 동의해주세요.');
                return;
            }
            
            // 폼 데이터 수집
            const formData = new FormData(this);
            formData.append('product_id', <?php echo $phone_id; ?>);
            
            // 포인트 사용 정보 추가
            if (window.pointUsageData && window.pointUsageData.usedPoint > 0) {
                formData.append('used_point', window.pointUsageData.usedPoint);
            }
            
            // selected_amount 값 확인
            const selectedAmountInput = this.querySelector('input[name="selected_amount"]');
            if (selectedAmountInput) {
                console.log('Form submit - selected_amount:', selectedAmountInput.value);
                formData.set('selected_amount', selectedAmountInput.value);
            } else {
                console.warn('Form submit - selected_amount input not found');
            }
            
            // 포인트 사용량 추가 (포인트 모달에서 확인한 포인트)
            if (window.pointUsageData && window.pointUsageData.usedPoint > 0) {
                formData.append('used_point', window.pointUsageData.usedPoint);
            }
            
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
                    
                    // 무조건 마이페이지 통신사폰 주문내역으로 이동
                    const mypageUrl = '/MVNO/mypage/mno-order.php';
                    
                    // redirect_url이 있으면 새 창으로 열기
                    if (data.redirect_url && data.redirect_url.trim() !== '') {
                        let redirectUrl = data.redirect_url.trim();
                        // URL이 프로토콜(http:// 또는 https://)을 포함하지 않으면 https:// 추가
                        if (!/^https?:\/\//i.test(redirectUrl)) {
                            redirectUrl = 'https://' + redirectUrl;
                        }
                        // 새 창으로 열기
                        window.open(redirectUrl, '_blank');
                    }
                    
                    // 마이페이지로 이동
                    window.location.href = mypageUrl;
                } else {
                    // 실패 시 모달로 표시
                    if (typeof showAlert === 'function') {
                        showAlert(data.message || '신청정보 저장에 실패했습니다.', '신청 실패');
                    } else {
                        alert(data.message || '신청정보 저장에 실패했습니다.');
                    }
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = '신청하기';
                    }
                }
            })
            .catch(error => {
                console.error('신청 처리 오류:', error);
                // 에러 발생 시 모달로 표시
                if (typeof showAlert === 'function') {
                    showAlert('신청 처리 중 오류가 발생했습니다.', '오류');
                } else {
                    alert('신청 처리 중 오류가 발생했습니다.');
                }
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
    
    // 페이지 리뷰는 이미 PHP에서 5개만 표시됨
    
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
    
    // 모달 내부 더보기 기능: 처음 10개, 이후 10개씩 표시
    if (reviewModalList && reviewModalMoreBtn) {
        const modalReviewItems = reviewModalList.querySelectorAll('.review-modal-item');
        const totalModalReviews = modalReviewItems.length;
        let visibleModalCount = 10; // 처음 10개만 표시
        
        // 초기 설정: 10개 이후 리뷰 숨기기
        function initializeModalReviews() {
            visibleModalCount = 10; // 모달 열 때마다 10개로 초기화
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
                const remaining = totalModalReviews - visibleModalCount;
                reviewModalMoreBtn.textContent = `리뷰 더보기 (${remaining}개)`;
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
                            initializeModalReviews(); // 모달 열 때마다 10개로 초기화
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
            
            // 남은 리뷰 개수 계산 및 버튼 텍스트 업데이트
            const remaining = totalModalReviews - visibleModalCount;
            if (remaining <= 0) {
                reviewModalMoreBtn.style.display = 'none';
            } else {
                reviewModalMoreBtn.textContent = `리뷰 더보기 (${remaining}개)`;
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

<style>
/* 체크박스 스타일 (인터넷 신청과 동일) */
.internet-checkbox-group .internet-checkbox-text,
.internet-checkbox-label-item .internet-checkbox-text,
span.internet-checkbox-text {
    font-size: 1.0625rem !important; /* 17px - 플랜 카드의 "통화 기본제공 | 문자 무제한 | KT알뜰폰 | 5G" 텍스트와 동일한 크기 */
    font-weight: 500 !important;
}

.internet-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.internet-checkbox-all {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    gap: 0.5rem;
}

.internet-checkbox-all .internet-checkbox-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    flex: 1;
}

.internet-checkbox-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-left: 2rem;
}

.internet-checkbox-item-wrapper {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.internet-checkbox-item {
    display: flex;
    align-items: center;
    width: 100%;
}

.internet-checkbox-label-item {
    display: flex;
    align-items: center;
    cursor: pointer;
    flex: 1;
}

.internet-checkbox-input-item {
    width: 18px;
    height: 18px;
    margin: 0;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    background-color: #f3f4f6;
    position: relative;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.internet-checkbox-input-item:hover {
    border-color: #9ca3af;
    background-color: #e5e7eb;
}

.internet-checkbox-input-item:checked {
    background-color: #6366f1;
    border-color: #6366f1;
    box-shadow: 0 1px 3px rgba(99, 102, 241, 0.3);
}

.internet-checkbox-input-item:checked::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -55%) rotate(45deg);
    width: 5px;
    height: 9px;
    border: solid white;
    border-width: 0 2px 2px 0;
    border-radius: 1px;
}

/* 전체동의 원형 체크박스 */
.internet-checkbox-all .internet-checkbox-input {
    width: 20px;
    height: 20px;
    margin: 0;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    background-color: #f3f4f6;
    position: relative;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.internet-checkbox-all .internet-checkbox-input:hover {
    border-color: #9ca3af;
    background-color: #e5e7eb;
}

.internet-checkbox-all .internet-checkbox-input:checked {
    background-color: #6366f1;
    border-color: #6366f1;
    box-shadow: 0 1px 3px rgba(99, 102, 241, 0.3);
}

.internet-checkbox-all .internet-checkbox-input:checked::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -55%) rotate(45deg);
    width: 5px;
    height: 9px;
    border: solid white;
    border-width: 0 2px 2px 0;
    border-radius: 1px;
}

.internet-checkbox-text {
    font-size: 1.0625rem !important;
    font-weight: 500 !important;
    color: #6b7280;
    margin-left: 0.5rem;
}

.internet-checkbox-link {
    margin-left: auto;
    color: #6b7280;
    display: flex;
    align-items: center;
    text-decoration: none;
}

.internet-checkbox-link svg {
    width: 18px; /* 아이콘 크기 증가 */
    height: 18px;
    transition: transform 0.3s ease;
}

.internet-checkbox-link svg.arrow-down {
    transform: rotate(0deg);
}

.internet-checkbox-link:hover {
    color: #374151;
    background-color: #f3f4f6;
}

.internet-checkbox-link.arrow-up svg {
    transform: rotate(180deg);
}

/* 아코디언 스타일 */
.internet-accordion-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    margin-top: 0;
    margin-left: 2rem;
}

.internet-accordion-content.active {
    max-height: none;
    overflow: visible;
    transition: max-height 0.4s ease-in;
    margin-top: 0.75rem;
}

.internet-accordion-inner {
    background-color: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
}

.internet-accordion-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.internet-accordion-section {
    margin-bottom: 0.75rem;
}

.internet-accordion-section:last-child {
    margin-bottom: 0;
}

.internet-accordion-section-title {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 0.5rem;
}

.internet-accordion-section-content {
    font-size: 0.8125rem;
    color: #6b7280;
    line-height: 1.6;
    padding-left: 0.5rem;
}

@media (max-width: 767px) {
    .internet-checkbox-list {
        margin-left: 1.5rem;
    }
}
</style>
</script>

<style>
/* 체크박스 스타일 (인터넷 신청과 동일) */
.internet-checkbox-group .internet-checkbox-text,
.internet-checkbox-label-item .internet-checkbox-text,
span.internet-checkbox-text {
    font-size: 1.0625rem !important; /* 17px - 플랜 카드의 "통화 기본제공 | 문자 무제한 | KT알뜰폰 | 5G" 텍스트와 동일한 크기 */
    font-weight: 500 !important;
}

.internet-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.internet-checkbox-all {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    gap: 0.5rem;
}

.internet-checkbox-all .internet-checkbox-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    flex: 1;
}

.internet-checkbox-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-left: 2rem;
}

.internet-checkbox-item-wrapper {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.internet-checkbox-item {
    display: flex;
    align-items: center;
    width: 100%;
}

.internet-checkbox-label-item {
    display: flex;
    align-items: center;
    cursor: pointer;
    flex: 1;
}

.internet-checkbox-input-item {
    width: 18px;
    height: 18px;
    margin: 0;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    background-color: #f3f4f6;
    position: relative;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.internet-checkbox-input-item:hover {
    border-color: #9ca3af;
    background-color: #e5e7eb;
}

.internet-checkbox-input-item:checked {
    background-color: #6366f1;
    border-color: #6366f1;
    box-shadow: 0 1px 3px rgba(99, 102, 241, 0.3);
}

.internet-checkbox-input-item:checked::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -55%) rotate(45deg);
    width: 5px;
    height: 9px;
    border: solid white;
    border-width: 0 2px 2px 0;
    border-radius: 1px;
}

/* 전체동의 원형 체크박스 */
.internet-checkbox-all .internet-checkbox-input {
    width: 20px;
    height: 20px;
    margin: 0;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    background-color: #f3f4f6;
    position: relative;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.internet-checkbox-all .internet-checkbox-input:hover {
    border-color: #9ca3af;
    background-color: #e5e7eb;
}

.internet-checkbox-all .internet-checkbox-input:checked {
    background-color: #6366f1;
    border-color: #6366f1;
    box-shadow: 0 1px 3px rgba(99, 102, 241, 0.3);
}

.internet-checkbox-all .internet-checkbox-input:checked::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -55%) rotate(45deg);
    width: 5px;
    height: 9px;
    border: solid white;
    border-width: 0 2px 2px 0;
    border-radius: 1px;
}

.internet-checkbox-text {
    font-size: 1.0625rem !important;
    font-weight: 500 !important;
    color: #6b7280;
    margin-left: 0.5rem;
}

.internet-checkbox-link {
    margin-left: auto;
    color: #6b7280;
    display: flex;
    align-items: center;
    text-decoration: none;
}

.internet-checkbox-link svg {
    width: 18px; /* 아이콘 크기 증가 */
    height: 18px;
    transition: transform 0.3s ease;
}

.internet-checkbox-link svg.arrow-down {
    transform: rotate(0deg);
}

.internet-checkbox-link:hover {
    color: #374151;
    background-color: #f3f4f6;
}

.internet-checkbox-link.arrow-up svg {
    transform: rotate(180deg);
}

/* 아코디언 스타일 */
.internet-accordion-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    margin-top: 0;
    margin-left: 2rem;
}

.internet-accordion-content.active {
    max-height: none;
    overflow: visible;
    transition: max-height 0.4s ease-in;
    margin-top: 0.75rem;
}

.internet-accordion-inner {
    background-color: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
}

.internet-accordion-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.internet-accordion-section {
    margin-bottom: 0.75rem;
}

.internet-accordion-section:last-child {
    margin-bottom: 0;
}

.internet-accordion-section-title {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 0.5rem;
}

.internet-accordion-section-content {
    font-size: 0.8125rem;
    color: #6b7280;
    line-height: 1.6;
    padding-left: 0.5rem;
}

@media (max-width: 767px) {
    .internet-checkbox-list {
        margin-left: 1.5rem;
    }
}

.plan-review-content {
    font-size: 14px;
    line-height: 1.6;
    color: #374151;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.review-modal-item-content {
    font-size: 14px;
    line-height: 1.6;
    color: #374151;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

/* 리뷰 섹션 스타일 (MVNO와 동일) */
.plan-review-left {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 0 0 auto;
}

.plan-review-total-rating {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex-shrink: 0;
}

.plan-review-total-rating-content {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 8px;
}

.plan-review-total-rating svg {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.plan-review-total-rating .plan-review-rating-score {
    font-size: 32px;
    font-weight: 700;
    color: #000000;
}

.plan-review-right {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 24px;
    flex: 0 0 auto;
}

.plan-review-categories {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.plan-review-category {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.plan-review-category-label {
    width: 80px;
    white-space: nowrap;
    font-size: 14px;
    font-weight: 700;
    color: #6b7280;
}

.plan-review-category-score {
    font-size: 14px;
    font-weight: 700;
    color: #4b5563;
    min-width: 35px;
    text-align: right;
}

.plan-review-stars {
    display: flex;
    align-items: center;
    gap: 2px;
    font-size: 18px;
    color: #EF4444;
    line-height: 1;
}

/* 부분 별점 스타일 */
.plan-review-stars .star-full {
    color: #EF4444;
}

.plan-review-stars .star-empty {
    color: #d1d5db;
}

.plan-review-stars .star-partial {
    position: relative;
    display: inline-block;
    width: 1em;
    height: 1em;
    line-height: 1;
    vertical-align: middle;
}

.plan-review-stars .star-partial-empty {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    color: #d1d5db;
    z-index: 0;
}

.plan-review-stars .star-partial-filled {
    position: absolute;
    top: 0;
    left: 0;
    width: var(--fill-percent);
    height: 100%;
    overflow: hidden;
    color: #EF4444;
    white-space: nowrap;
    z-index: 1;
}
</style>

