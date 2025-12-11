<?php
// 현재 페이지 설정
$current_page = 'mvno';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true; // 상세 페이지에서도 하단 메뉴바 표시

// 요금제 ID 가져오기
$plan_id = isset($_GET['id']) ? intval($_GET['id']) : 32627;

// 헤더 포함
include '../includes/header.php';

// 요금제 데이터 가져오기
require_once '../includes/data/plan-data.php';
$plan = getPlanDetailData($plan_id);
$rawData = $plan['_raw_data'] ?? []; // 원본 DB 데이터 (null 대신 빈 배열로 초기화)

// 리뷰 목록 가져오기 (같은 판매자의 같은 타입의 모든 상품 리뷰 통합)
$reviews = getProductReviews($plan_id, 'mvno', 20);
$averageRating = getProductAverageRating($plan_id, 'mvno');
$reviewCount = getProductReviewCount($plan_id, 'mvno');
if (!$plan) {
    // 데이터가 없으면 기본값 사용
    $plan = [
        'id' => $plan_id,
        'provider' => '쉐이크모바일',
        'rating' => '4.3',
        'title' => '11월한정 LTE 100GB+밀리+Data쿠폰60GB',
        'data_main' => '월 100GB + 5Mbps',
        'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'],
        'price_main' => '월 17,000원',
        'price_after' => '7개월 이후 42,900원',
        'selection_count' => '29,448명이 신청',
        'gifts' => [
            'SOLO결합(+20GB)',
            '밀리의서재 평생 무료 구독권',
            '데이터쿠폰 20GB',
            '[11월 한정]네이버페이 10,000원',
            '3대 마트 상품권 2만원'
        ],
        'gift_icons' => [
            ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg', 'alt' => '이마트 상품권'],
            ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이'],
            ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '데이터쿠폰 20GB'],
            ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/millie.svg', 'alt' => '밀리의 서재'],
            ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/subscription.svg', 'alt' => 'SOLO결합(+20GB)']
        ]
    ];
    $rawData = []; // 기본값 사용 시에도 빈 배열로 초기화
}
?>

<main class="main-content plan-detail-page">
    <!-- 요금제 상세 레이아웃 (모듈 사용) -->
    <?php include '../includes/layouts/plan-detail-layout.php'; ?>

    <!-- 요금제 상세 정보 섹션 (통합) -->
    <section class="plan-detail-info-section">
        <div class="content-layout">
            <h2 class="section-title">상세정보</h2>
            
            <!-- 기본 정보 카드 -->
            <div class="plan-info-card">
                <h3 class="plan-info-card-title">기본 정보</h3>
                <div class="plan-info-card-content">
                    <div class="plan-detail-grid">
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">요금제 이름</div>
                            <div class="plan-detail-value">
                                <?php echo htmlspecialchars($plan['title'] ?? ($rawData['plan_name'] ?? '요금제명 없음')); ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통신사 약정</div>
                            <div class="plan-detail-value">
                                <?php 
                                $contractPeriod = $rawData['contract_period'] ?? '';
                                echo htmlspecialchars($contractPeriod ?: '없음'); 
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통신망</div>
                            <div class="plan-detail-value">
                                <?php 
                                $provider = $rawData['provider'] ?? '';
                                // provider 값 그대로 표시 (예: "KT알뜰폰", "SK알뜰폰", "LG알뜰폰")
                                echo htmlspecialchars($provider ?: '통신망 정보 없음'); 
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통신 기술</div>
                            <div class="plan-detail-value">
                                <?php echo htmlspecialchars($rawData['service_type'] ?? 'LTE'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 데이터 정보 카드 -->
            <div class="plan-info-card">
                <h3 class="plan-info-card-title">데이터 정보</h3>
                <div class="plan-info-card-content">
                    <div class="plan-detail-grid">
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통화</div>
                            <div class="plan-detail-value">
                                <?php
                                $callType = $rawData['call_type'] ?? '';
                                $callAmount = $rawData['call_amount'] ?? '';
                                if ($callType === '무제한') {
                                    echo '무제한';
                                } elseif ($callType === '기본제공') {
                                    echo '기본제공';
                                } elseif ($callType === '직접입력' && !empty($callAmount)) {
                                    echo number_format((float)$callAmount) . '분';
                                } else {
                                    echo htmlspecialchars($callType ?: '정보 없음');
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">문자</div>
                            <div class="plan-detail-value">
                                <?php
                                $smsType = $rawData['sms_type'] ?? '';
                                $smsAmount = $rawData['sms_amount'] ?? '';
                                if ($smsType === '무제한') {
                                    echo '무제한';
                                } elseif ($smsType === '기본제공') {
                                    echo '기본제공';
                                } elseif ($smsType === '직접입력' && !empty($smsAmount)) {
                                    echo number_format((float)$smsAmount) . '건';
                                } else {
                                    echo htmlspecialchars($smsType ?: '정보 없음');
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">데이터 제공량</div>
                            <div class="plan-detail-value">
                                <?php
                                $dataAmount = $rawData['data_amount'] ?? '';
                                $dataAmountValue = $rawData['data_amount_value'] ?? '';
                                $dataUnit = $rawData['data_unit'] ?? '';
                                if ($dataAmount === '무제한') {
                                    echo '무제한';
                                } elseif ($dataAmount === '직접입력' && !empty($dataAmountValue)) {
                                    // 직접입력인 경우: 값 + 단위 표시
                                    $displayValue = '월 ' . number_format((float)$dataAmountValue);
                                    if (!empty($dataUnit)) {
                                        $displayValue .= $dataUnit;
                                    }
                                    echo $displayValue;
                                } else {
                                    // 선택 옵션인 경우에도 단위 표시
                                    $displayValue = htmlspecialchars($dataAmount ?: '정보 없음');
                                    if (!empty($dataUnit) && $dataAmount !== '무제한' && $dataAmount !== '') {
                                        $displayValue .= ' ' . htmlspecialchars($dataUnit);
                                    }
                                    echo $displayValue;
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">데이터 소진시</div>
                            <div class="plan-detail-value">
                                <?php
                                $dataExhausted = $rawData['data_exhausted'] ?? '';
                                $dataExhaustedValue = $rawData['data_exhausted_value'] ?? '';
                                if ($dataExhausted === '직접입력' && !empty($dataExhaustedValue)) {
                                    echo htmlspecialchars($dataExhaustedValue);
                                } elseif (!empty($dataExhausted)) {
                                    echo htmlspecialchars($dataExhausted);
                                } else {
                                    echo '정보 없음';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">부가통화</div>
                            <div class="plan-detail-value">
                                <?php
                                $additionalCallType = $rawData['additional_call_type'] ?? '';
                                $additionalCall = $rawData['additional_call'] ?? '';
                                if (!empty($additionalCallType) && !empty($additionalCall)) {
                                    echo number_format((float)$additionalCall) . '분';
                                } else {
                                    echo '없음';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">테더링(핫스팟)</div>
                            <div class="plan-detail-value">
                                <?php
                                $mobileHotspot = $rawData['mobile_hotspot'] ?? '';
                                $mobileHotspotValue = $rawData['mobile_hotspot_value'] ?? '';
                                if ($mobileHotspot === '직접선택' && !empty($mobileHotspotValue)) {
                                    echo htmlspecialchars($mobileHotspotValue);
                                } elseif (!empty($mobileHotspot)) {
                                    echo htmlspecialchars($mobileHotspot);
                                } else {
                                    echo '기본 제공량 내에서 사용';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 유심 정보 카드 -->
            <div class="plan-info-card">
                <h3 class="plan-info-card-title">유심 정보</h3>
                <div class="plan-info-card-content">
                    <div class="plan-detail-grid">
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">일반 유심</div>
                            <div class="plan-detail-value">
                                <?php
                                $regularSimAvailable = $rawData['regular_sim_available'] ?? '';
                                $regularSimPrice = $rawData['regular_sim_price'] ?? '';
                                if ($regularSimAvailable === '배송가능' && !empty($regularSimPrice)) {
                                    echo '배송가능 (' . number_format((float)$regularSimPrice) . '원)';
                                } elseif ($regularSimAvailable === '배송가능') {
                                    echo '배송가능';
                                } else {
                                    echo '배송불가';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">NFC 유심</div>
                            <div class="plan-detail-value">
                                <?php
                                $nfcSimAvailable = $rawData['nfc_sim_available'] ?? '';
                                $nfcSimPrice = $rawData['nfc_sim_price'] ?? '';
                                if ($nfcSimAvailable === '배송가능' && !empty($nfcSimPrice)) {
                                    echo '배송가능 (' . number_format((float)$nfcSimPrice) . '원)';
                                } elseif ($nfcSimAvailable === '배송가능') {
                                    echo '배송가능';
                                } else {
                                    echo '배송불가';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">eSIM</div>
                            <div class="plan-detail-value">
                                <?php
                                $esimAvailable = $rawData['esim_available'] ?? '';
                                $esimPrice = $rawData['esim_price'] ?? '';
                                if ($esimAvailable === '개통가능' && !empty($esimPrice)) {
                                    echo '개통가능 (' . number_format((float)$esimPrice) . '원)';
                                } elseif ($esimAvailable === '개통가능') {
                                    echo '개통가능';
                                } else {
                                    echo '개통불가';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- 초과 요금 섹션 -->
    <section class="plan-exceed-rate-section">
        <div class="content-layout">
            <div class="plan-info-card">
                <h3 class="plan-info-card-title">기본 제공 초과 시</h3>
                <div class="plan-info-card-content">
                    <div class="plan-detail-grid">
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">데이터</div>
                            <div class="plan-detail-value">
                                <?php
                                $overDataPrice = $rawData['over_data_price'] ?? '';
                                if (!empty($overDataPrice)) {
                                    // 숫자 값만 추출 (단위 제거)
                                    preg_match('/[\d.]+/', $overDataPrice, $matches);
                                    $value = $matches[0] ?? $overDataPrice;
                                    echo htmlspecialchars($value) . '원/MB';
                                } else {
                                    echo '정보 없음';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">음성</div>
                            <div class="plan-detail-value">
                                <?php
                                $overVoicePrice = $rawData['over_voice_price'] ?? '';
                                if (!empty($overVoicePrice)) {
                                    // 숫자 값만 추출 (단위 제거)
                                    preg_match('/[\d.]+/', $overVoicePrice, $matches);
                                    $value = $matches[0] ?? $overVoicePrice;
                                    echo htmlspecialchars($value) . '원/초';
                                } else {
                                    echo '정보 없음';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">영상통화</div>
                            <div class="plan-detail-value">
                                <?php
                                $overVideoPrice = $rawData['over_video_price'] ?? '';
                                if (!empty($overVideoPrice)) {
                                    // 숫자 값만 추출 (단위 제거)
                                    preg_match('/[\d.]+/', $overVideoPrice, $matches);
                                    $value = $matches[0] ?? $overVideoPrice;
                                    echo htmlspecialchars($value) . '원/초';
                                } else {
                                    echo '정보 없음';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">단문메시지(SMS)</div>
                            <div class="plan-detail-value">
                                <?php
                                $overSmsPrice = $rawData['over_sms_price'] ?? '';
                                if (!empty($overSmsPrice)) {
                                    // 숫자 값만 추출 (단위 제거)
                                    preg_match('/[\d.]+/', $overSmsPrice, $matches);
                                    $value = $matches[0] ?? $overSmsPrice;
                                    echo htmlspecialchars($value) . '원/건';
                                } else {
                                    echo '정보 없음';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">텍스트형(LMS,MMS)</div>
                            <div class="plan-detail-value">
                                <?php
                                $overLmsPrice = $rawData['over_lms_price'] ?? '';
                                // 디버깅: 값 확인
                                // echo "<!-- Debug: over_lms_price = " . var_export($overLmsPrice, true) . " -->";
                                if (!empty($overLmsPrice) && trim($overLmsPrice) !== '') {
                                    // 숫자 값만 추출 (단위 제거)
                                    preg_match('/[\d.]+/', $overLmsPrice, $matches);
                                    $value = $matches[0] ?? $overLmsPrice;
                                    if (!empty($value)) {
                                        echo htmlspecialchars($value) . '원/건';
                                    } else {
                                        echo '정보 없음';
                                    }
                                } else {
                                    echo '정보 없음';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">멀티미디어형(MMS)</div>
                            <div class="plan-detail-value">
                                <?php
                                $overMmsPrice = $rawData['over_mms_price'] ?? '';
                                if (!empty($overMmsPrice)) {
                                    // 숫자 값만 추출 (단위 제거)
                                    preg_match('/[\d.]+/', $overMmsPrice, $matches);
                                    $value = $matches[0] ?? $overMmsPrice;
                                    echo htmlspecialchars($value) . '원/건';
                                } else {
                                    echo '정보 없음';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="plan-exceed-rate-notice">
                문자메시지 기본제공 혜택을 약관에 정한 기준보다 많이 사용하거나 스팸, 광고 목적으로 이용한 것이 확인되면 추가 요금을 내야 하거나 서비스 이용이 정지될 수 있어요.
            </div>
        </div>
    </section>

    <!-- 판매자 추가 정보 섹션 -->
    <section class="plan-seller-info-section">
        <div class="content-layout">
            <div class="plan-info-card">
                <h3 class="plan-info-card-title">혜택 및 유의사항</h3>
                <div class="plan-info-card-content">
                    <div class="plan-seller-additional-text">
                        <?php
                        $benefits = $rawData['benefits'] ?? '';
                        if (!empty($benefits)) {
                            $benefitsArray = json_decode($benefits, true);
                            if (is_array($benefitsArray)) {
                                echo '<ul>';
                                foreach ($benefitsArray as $benefit) {
                                    if (!empty(trim($benefit))) {
                                        echo '<li>' . htmlspecialchars($benefit) . '</li>';
                                    }
                                }
                                echo '</ul>';
                            } else {
                                echo htmlspecialchars($benefits);
                            }
                        } else {
                            echo '추가 정보 없음';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 통신사 리뷰 섹션 -->
    <section class="plan-review-section" id="planReviewSection">
        <div class="content-layout">
            <div class="plan-review-header">
                <a href="/mvnos/<?php echo urlencode($plan['provider'] ?? '쉐이크모바일'); ?>?from=요금제상세" class="plan-review-mvno-link">
                    <span class="plan-review-logo-text"><?php echo htmlspecialchars($plan['provider'] ?? '쉐이크모바일'); ?></span>
                </a>
                <h2 class="section-title">리뷰</h2>
            </div>
            
            <?php
            // 정렬 방식 가져오기 (기본값: 높은 평점순)
            $sort = $_GET['review_sort'] ?? 'rating_desc';
            if (!in_array($sort, ['rating_desc', 'rating_asc', 'created_desc'])) {
                $sort = 'rating_desc';
            }
            
            // 리뷰 목록 가져오기 (같은 판매자의 같은 타입의 모든 상품 리뷰 통합)
            $reviews = getProductReviews($plan_id, 'mvno', 20, $sort);
            $averageRating = getProductAverageRating($plan_id, 'mvno');
            $reviewCount = getProductReviewCount($plan_id, 'mvno');
            $hasReviews = $reviewCount > 0;
            ?>
            <?php if ($hasReviews): ?>
            <div class="plan-review-summary">
                <div class="plan-review-rating">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#EF4444"/>
                    </svg>
                    <span class="plan-review-rating-score"><?php echo htmlspecialchars($averageRating > 0 ? number_format($averageRating, 1) : ($plan['rating'] ?? '0.0')); ?></span>
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
                        <select class="plan-review-sort-select" id="planReviewSortSelect" aria-label="리뷰 정렬 방식 선택">
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
                    <button class="plan-review-write-btn" id="planReviewWriteBtn">리뷰 작성</button>
                <?php endif; ?>
            </div>

            <div class="plan-review-list">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="plan-review-item">
                            <div class="plan-review-item-header">
                                <span class="plan-review-author"><?php echo htmlspecialchars($review['author_name'] ?? '익명'); ?></span>
                                <div class="plan-review-stars">
                                    <span><?php echo htmlspecialchars($review['stars'] ?? '★★★★★'); ?></span>
                                </div>
                                <span class="plan-review-date"><?php echo htmlspecialchars($review['date_ago'] ?? ''); ?></span>
                            </div>
                            <p class="plan-review-content"><?php echo nl2br(htmlspecialchars($review['content'] ?? '')); ?></p>
                            <?php if (!empty($plan['title'])): ?>
                                <div class="plan-review-tags">
                                    <span class="plan-review-tag"><?php echo htmlspecialchars($plan['title']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="plan-review-item">
                        <p class="plan-review-content" style="text-align: center; color: #868e96; padding: 40px 0;">등록된 리뷰가 없습니다.</p>
                    </div>
                <?php endif; ?>
            </div>
            <button class="plan-review-more-btn" id="planReviewMoreBtn">리뷰 더보기</button>
        </div>
    </section>
</main>

<!-- 리뷰 모달 -->
<div class="review-modal" id="reviewModal">
    <div class="review-modal-overlay" id="reviewModalOverlay"></div>
    <div class="review-modal-content">
        <div class="review-modal-header">
            <h3 class="review-modal-title"><?php echo htmlspecialchars($plan['provider'] ?? '쉐이크모바일'); ?></h3>
            <button class="review-modal-close" aria-label="닫기" id="reviewModalClose">
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
                    <span class="review-modal-rating-score"><?php echo htmlspecialchars($averageRating > 0 ? number_format($averageRating, 1) : ($plan['rating'] ?? '0.0')); ?></span>
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
                    <span class="review-modal-total">
                        총 <?php echo number_format($reviewCount); ?>개
                    </span>
                    <div class="review-modal-sort-select-wrapper">
                        <select class="review-modal-sort-select" id="planReviewModalSortSelect" aria-label="리뷰 정렬 방식 선택">
                            <option value="rating_desc" <?php echo $sort === 'rating_desc' ? 'selected' : ''; ?>>높은 평점순</option>
                            <option value="rating_asc" <?php echo $sort === 'rating_asc' ? 'selected' : ''; ?>>낮은 평점순</option>
                            <option value="created_desc" <?php echo $sort === 'created_desc' ? 'selected' : ''; ?>>최신순</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="review-modal-list" id="reviewModalList">
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
                            <?php if (!empty($plan['title'])): ?>
                                <div class="review-modal-tags">
                                    <span class="review-modal-tag"><?php echo htmlspecialchars($plan['title']); ?></span>
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
            <button class="review-modal-more-btn">리뷰 더보기</button>
        </div>
    </div>
</div>

<!-- 공유 모달 -->
<div class="share-modal" id="shareModal">
    <div class="share-modal-overlay" id="shareModalOverlay"></div>
    <div class="share-modal-content">
        <div class="share-modal-header">
            <h3 class="share-modal-title">공유하기</h3>
            <button class="share-modal-close" aria-label="닫기" id="shareModalClose">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="share-modal-grid">
            <a href="#" class="share-modal-item" data-platform="kakao" target="_blank" rel="noopener noreferrer">
                <div class="share-modal-icon share-icon-kakao">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 4C9.37258 4 4 8.37258 4 14C4 17.5 6.1 20.6 9.3 22.3L8.5 27.5C8.4 27.8 8.6 28.1 8.9 28L13.2 25.7C13.8 25.8 14.4 25.9 15 25.9C21.6274 25.9 26.9 21.5274 26.9 15.9C26.9 9.27258 22.5274 4 16 4Z" fill="#3C1E1E"/>
                    </svg>
                </div>
                <span class="share-modal-label">카카오톡</span>
            </a>
            <a href="#" class="share-modal-item" data-platform="facebook" target="_blank" rel="noopener noreferrer">
                <div class="share-modal-icon share-icon-facebook">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 4C9.37258 4 4 9.37258 4 16C4 22.6274 9.37258 28 16 28C22.6274 28 28 22.6274 28 16C28 9.37258 22.6274 4 16 4ZM18.5 12.5H17C16.4 12.5 16 12.9 16 13.5V15.5H18.5C18.8 15.5 19 15.7 19 16V18C19 18.3 18.8 18.5 18.5 18.5H16V24H13.5V18.5H11.5C11.2 18.5 11 18.3 11 18V16C11 15.7 11.2 15.5 11.5 15.5H13.5V13.5C13.5 11.3 15.3 9.5 17.5 9.5H18.5C18.8 9.5 19 9.7 19 10V11.5C19 11.8 18.8 12 18.5 12H17.5C16.9 12 16.5 12.4 16.5 13V15.5H18.5C18.8 15.5 19 15.7 19 16V18C19 18.3 18.8 18.5 18.5 18.5H16.5V24H13.5V18.5H11.5C11.2 18.5 11 18.3 11 18V16C11 15.7 11.2 15.5 11.5 15.5H13.5V13.5C13.5 11.3 15.3 9.5 17.5 9.5H18.5C18.8 9.5 19 9.7 19 10V11.5C19 11.8 18.8 12 18.5 12Z" fill="#FFFFFF"/>
                    </svg>
                </div>
                <span class="share-modal-label">페이스북</span>
            </a>
            <a href="#" class="share-modal-item" data-platform="twitter" target="_blank" rel="noopener noreferrer">
                <div class="share-modal-icon share-icon-twitter">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z" fill="#FFFFFF"/>
                    </svg>
                </div>
                <span class="share-modal-label">트위터</span>
            </a>
            <button class="share-modal-item" data-platform="link" id="shareLinkBtn">
                <div class="share-modal-icon share-icon-link">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13 11C13 10.4477 13.4477 10 14 10H18C18.5523 10 19 10.4477 19 11C19 11.5523 18.5523 12 18 12H15V20H18C18.5523 20 19 20.4477 19 21C19 21.5523 18.5523 22 18 22H14C13.4477 22 13 21.5523 13 21V11Z" fill="#868E96"/>
                        <path d="M16 4C9.37258 4 4 9.37258 4 16C4 22.6274 9.37258 28 16 28C22.6274 28 28 22.6274 28 16C28 9.37258 22.6274 4 16 4Z" fill="none" stroke="#868E96" stroke-width="2"/>
                    </svg>
                </div>
                <span class="share-modal-label">링크 복사</span>
            </button>
        </div>
    </div>
</div>

<!-- 신청하기 모달 -->
<div class="apply-modal" id="applyModal">
    <div class="apply-modal-overlay" id="applyModalOverlay"></div>
    <div class="apply-modal-content">
        <div class="apply-modal-header">
            <button class="apply-modal-back" aria-label="뒤로 가기" id="applyModalBack" style="display: none;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 18L9 12L15 6" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <h3 class="apply-modal-title">가입유형</h3>
            <button class="apply-modal-close" aria-label="닫기" id="applyModalClose">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="apply-modal-body" id="applyModalBody">
            <!-- 2단계: 가입 방법 선택 -->
            <div class="apply-modal-step" id="step2">
                <div class="plan-order-section">
                    <div class="plan-order-checkbox-group">
                        <div class="plan-order-checkbox-item">
                            <input type="checkbox" id="numberPort" name="joinMethod" value="port" class="plan-order-checkbox-input">
                            <label for="numberPort" class="plan-order-checkbox-label">
                                <div class="plan-order-checkbox-content">
                                    <div class="plan-order-checkbox-title">번호 이동</div>
                                    <div class="plan-order-checkbox-description">지금 쓰는 번호 그대로 사용할래요</div>
                                </div>
                            </label>
                        </div>
                        <div class="plan-order-checkbox-item">
                            <input type="checkbox" id="newJoin" name="joinMethod" value="new" class="plan-order-checkbox-input">
                            <label for="newJoin" class="plan-order-checkbox-label">
                                <div class="plan-order-checkbox-content">
                                    <div class="plan-order-checkbox-title">신규 가입</div>
                                    <div class="plan-order-checkbox-description">새로운 번호로 가입할래요</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 3단계: 신청 안내 -->
            <div class="apply-modal-step" id="step3" style="display: none;">
                <div class="plan-apply-confirm-section">
                    <div class="plan-apply-confirm-description">
                        <div class="plan-apply-confirm-intro">
                            모유에서 다음 정보가 알림톡으로 발송됩니다:<br>
                            <span class="plan-apply-confirm-intro-sub">알림 정보 설정은 마이페이지에서 수정가능하세요.</span>
                        </div>
                        <div class="plan-apply-confirm-list">
                            <div class="plan-apply-confirm-item plan-apply-confirm-item-empty"></div>
                            <div class="plan-apply-confirm-item plan-apply-confirm-item-center">
                                • 신청정보<br>
                                • 약정기간 종료 안내<br>
                                • 프로모션 종료 안내<br>
                                • 기타 상품관련 안내
                            </div>
                            <div class="plan-apply-confirm-item plan-apply-confirm-item-empty"></div>
                        </div>
                        <div class="plan-apply-confirm-notice">쉐이크모바일 연계 통신사로 가입을 진행합니다</div>
                    </div>
                    <button class="plan-apply-confirm-btn" id="planApplyConfirmBtn">신청하기</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 아코디언 기능
document.addEventListener('DOMContentLoaded', function() {
    const accordionTriggers = document.querySelectorAll('.plan-accordion-trigger');
    
    accordionTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            // 모든 아코디언 닫기
            accordionTriggers.forEach(t => {
                t.setAttribute('aria-expanded', 'false');
                t.nextElementSibling.style.display = 'none';
            });
            
            // 클릭한 아코디언만 토글
            if (!isExpanded) {
                this.setAttribute('aria-expanded', 'true');
                content.style.display = 'block';
            }
        });
    });

    // 공유 모달 기능
    const shareBtn = document.getElementById('planShareBtn');
    const shareModal = document.getElementById('shareModal');
    const shareModalOverlay = document.getElementById('shareModalOverlay');
    const shareModalClose = document.getElementById('shareModalClose');
    const shareLinkBtn = document.getElementById('shareLinkBtn');
    const shareItems = document.querySelectorAll('.share-modal-item');

    // 현재 페이지 URL과 제목 가져오기
    const currentUrl = window.location.href;
    const planTitle = document.querySelector('.plan-title-text')?.textContent || '요금제 상세';
    const shareText = `${planTitle} - 모요`;

    // 링크 복사 함수
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text).then(function() {
                return true;
            }).catch(function() {
                return fallbackCopyTextToClipboard(text);
            });
        } else {
            return fallbackCopyTextToClipboard(text);
        }
    }
    
    // 토스트 메시지 표시 함수
    function showToastMessage(message) {
        // 기존 토스트가 있으면 제거
        const existingToast = document.querySelector('.toast-message');
        if (existingToast) {
            existingToast.remove();
        }
        
        // 공유하기 버튼 위치 가져오기
        const shareButton = document.getElementById('planShareBtn');
        let topPosition = '50%';
        // 좌우 중앙으로 고정
        const leftPosition = '50%';
        
        if (shareButton) {
            const rect = shareButton.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            // 공유하기 버튼의 중간 높이 위치
            topPosition = (rect.top + scrollTop + rect.height / 2) + 'px';
        }
        
        // 토스트 메시지 생성
        const toast = document.createElement('div');
        toast.className = 'toast-message';
        toast.textContent = message;
        toast.style.top = topPosition;
        toast.style.left = leftPosition;
        document.body.appendChild(toast);
        
        // 강제로 리플로우 발생시켜 초기 상태 적용
        void toast.offsetHeight;
        
        // 애니메이션을 위해 약간의 지연 후 visible 클래스 추가
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                toast.classList.add('toast-message-visible');
            });
        });
        
        // 1초 후 자동으로 사라지게
        setTimeout(function() {
            toast.classList.remove('toast-message-visible');
            setTimeout(function() {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        }, 1000);
    }
    
    // 공유 버튼 클릭 시 바로 링크 복사
    if (shareBtn) {
        shareBtn.addEventListener('click', function(e) {
            e.preventDefault();
            copyToClipboard(currentUrl).then(function(success) {
                if (success) {
                    showToastMessage('공유링크를 복사했어요');
                } else {
                    showToastMessage('링크 복사에 실패했습니다');
                }
            });
        });
    }

    // 모달 닫기
    function closeModal() {
        shareModal.classList.remove('share-modal-active');
        document.body.style.overflow = '';
    }

    if (shareModalOverlay) {
        shareModalOverlay.addEventListener('click', closeModal);
    }

    if (shareModalClose) {
        shareModalClose.addEventListener('click', closeModal);
    }

    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && shareModal.classList.contains('share-modal-active')) {
            closeModal();
        }
    });

    // 소셜 공유 링크 설정
    shareItems.forEach(item => {
        const platform = item.getAttribute('data-platform');
        
        if (platform === 'kakao') {
            // 카카오톡 공유 (카카오 SDK 필요 시 사용, 여기서는 링크 공유)
            item.href = `https://story.kakao.com/share?url=${encodeURIComponent(currentUrl)}`;
        } else if (platform === 'facebook') {
            // 페이스북 공유
            item.href = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}`;
        } else if (platform === 'twitter') {
            // 트위터 공유
            item.href = `https://twitter.com/intent/tweet?url=${encodeURIComponent(currentUrl)}&text=${encodeURIComponent(shareText)}`;
        } else if (platform === 'link') {
            // 링크 복사
            item.addEventListener('click', function(e) {
                e.preventDefault();
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(currentUrl).then(function() {
                        showAlert('링크가 복사되었습니다.').then(() => {
                            closeModal();
                        });
                    }).catch(function() {
                        // 클립보드 API 실패 시 fallback
                        fallbackCopyTextToClipboard(currentUrl);
                    });
                } else {
                    // 클립보드 API 미지원 시 fallback
                    fallbackCopyTextToClipboard(currentUrl);
                }
            });
        }
    });

    // 클립보드 복사 fallback 함수
    function fallbackCopyTextToClipboard(text) {
        return new Promise(function(resolve) {
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
                    resolve(true);
                } else {
                    resolve(false);
                }
            } catch (err) {
                resolve(false);
            }
            
            document.body.removeChild(textArea);
        });
    }

    // 신청하기 모달 기능
    const applyBtn = document.getElementById('planApplyBtn');
    const applyModal = document.getElementById('applyModal');
    const applyModalOverlay = document.getElementById('applyModalOverlay');
    const applyModalClose = document.getElementById('applyModalClose');
    const applyModalBody = document.getElementById('applyModalBody');

    // 스크롤 위치 저장 변수
    let scrollPosition = 0;

    // 모달 열기 함수
    function openApplyModal() {
        if (!applyModal) {
            console.error('모달을 찾을 수 없습니다.');
            return;
        }
        
        // 현재 스크롤 위치 저장
        scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        
        // body 스크롤 방지
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollPosition}px`;
        document.body.style.width = '100%';
        
        // html 요소도 스크롤 방지 (일부 브라우저용)
        document.documentElement.style.overflow = 'hidden';
        
        // 모달 열기
        applyModal.classList.add('apply-modal-active');
        
        // 신청 안내 모달(3단계) 바로 표시 (가입유형 선택 건너뛰기)
        showStep(3);
    }
    
    // 모달 단계 관리
    let currentStep = 3;
    
    // 단계 표시 함수
    const applyModalBack = document.getElementById('applyModalBack');
    
    function showStep(stepNumber, selectedMethod) {
        const step2 = document.getElementById('step2');
        const step3 = document.getElementById('step3');
        const modalTitle = document.querySelector('.apply-modal-title');
        const confirmMethod = document.getElementById('planApplyConfirmMethod');
        
        if (stepNumber === 2) {
            if (step2) step2.style.display = 'block';
            if (step3) step3.style.display = 'none';
            if (modalTitle) modalTitle.textContent = '가입유형';
            if (applyModalBack) applyModalBack.style.display = 'flex';
            currentStep = 2;
        } else if (stepNumber === 3) {
            if (step2) step2.style.display = 'none';
            if (step3) step3.style.display = 'block';
            // 모달 제목 기본값: 통신사 가입신청
            if (modalTitle) {
                modalTitle.textContent = '통신사 가입신청';
            }
            // 뒤로 가기 버튼 숨김 (첫 번째 모달이므로)
            if (applyModalBack) applyModalBack.style.display = 'none';
            // 버튼 텍스트 기본값: 신청하기
            const confirmBtn = document.getElementById('planApplyConfirmBtn');
            if (confirmBtn) {
                confirmBtn.textContent = '신청하기';
            }
            currentStep = 3;
        }
    }
    
    // 뒤로 가기 버튼 이벤트
    if (applyModalBack) {
        applyModalBack.addEventListener('click', function() {
            // step3에서 뒤로 가기 시 모달 닫기
            if (currentStep === 3) {
                closeApplyModal();
            }
        });
    }
    
    // 가입 방법 선택 이벤트 (라디오 버튼처럼 동작)
    const joinMethodInputs = document.querySelectorAll('input[name="joinMethod"]');
    joinMethodInputs.forEach(input => {
        input.addEventListener('change', function() {
            // 다른 체크박스 해제 (라디오 버튼처럼 동작)
            joinMethodInputs.forEach(inp => {
                if (inp !== this) {
                    inp.checked = false;
                    inp.closest('.plan-order-checkbox-item').classList.remove('plan-order-checkbox-checked');
                }
            });
            
            // 선택된 항목에 체크 스타일 적용
            if (this.checked) {
                this.closest('.plan-order-checkbox-item').classList.add('plan-order-checkbox-checked');
                console.log('선택된 가입 방법:', this.value);
                
                // 선택된 가입 방법 텍스트 가져오기
                const selectedMethod = this.value === 'port' ? '번호 이동' : '신규 가입';
                
                // 다음 단계로 진행
                setTimeout(() => {
                    showStep(3, selectedMethod);
                }, 300);
            } else {
                this.closest('.plan-order-checkbox-item').classList.remove('plan-order-checkbox-checked');
            }
        });
    });

    // 신청하기 버튼 클릭 이벤트
    if (applyBtn) {
        console.log('신청하기 버튼 찾음:', applyBtn);
        
        // onclick 속성으로 직접 할당
        applyBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            console.log('신청하기 버튼 클릭됨 (onclick)');
            openApplyModal();
            return false;
        };
        
        // addEventListener도 추가
        applyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            console.log('신청하기 버튼 클릭됨 (addEventListener)');
            openApplyModal();
            return false;
        }, true);
        
        // 테스트: 버튼이 클릭 가능한지 확인
        console.log('버튼 스타일:', window.getComputedStyle(applyBtn));
        console.log('버튼 pointer-events:', window.getComputedStyle(applyBtn).pointerEvents);
    } else {
        console.error('신청하기 버튼을 찾을 수 없습니다.');
        // 대체 방법: 클래스로 찾기
        const applyBtnByClass = document.querySelector('.plan-apply-btn');
        if (applyBtnByClass) {
            console.log('클래스로 버튼 찾음:', applyBtnByClass);
            applyBtnByClass.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('신청하기 버튼 클릭됨 (클래스로 찾은 버튼)');
                openApplyModal();
                return false;
            };
        }
    }

    // 모달 닫기
    function closeApplyModal() {
        applyModal.classList.remove('apply-modal-active');
        
        // body 스크롤 복원
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        
        // html 요소 스크롤 복원
        document.documentElement.style.overflow = '';
        
        // 저장된 스크롤 위치로 복원
        window.scrollTo(0, scrollPosition);
        
        // 모달 상태 초기화
        showStep(3);
    }
    
    // step3 신청하기 버튼 이벤트
    const planApplyConfirmBtn = document.getElementById('planApplyConfirmBtn');
    if (planApplyConfirmBtn) {
        planApplyConfirmBtn.addEventListener('click', function() {
            // 모달 즉시 닫기
            applyModal.classList.remove('apply-modal-active');
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.documentElement.style.overflow = '';
            window.scrollTo(0, scrollPosition);
            showStep(3);
            
            // 상품 등록 시 설정된 URL로 새 창에서 이동 (현재는 naver)
            window.open('https://www.naver.com', '_blank');
        });
    }

    if (applyModalOverlay) {
        applyModalOverlay.addEventListener('click', closeApplyModal);
        // 터치 스크롤 방지
        applyModalOverlay.addEventListener('touchmove', function(e) {
            e.preventDefault();
        }, { passive: false });
    }
    
    // 모달이 열려있을 때 배경 스크롤 방지
    if (applyModal) {
        applyModal.addEventListener('touchmove', function(e) {
            // 모달 콘텐츠 내부가 아닌 경우에만 preventDefault
            if (e.target === applyModal || e.target === applyModalOverlay) {
                e.preventDefault();
            }
        }, { passive: false });
    }

    if (applyModalClose) {
        applyModalClose.addEventListener('click', closeApplyModal);
    }

    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && applyModal.classList.contains('apply-modal-active')) {
            closeApplyModal();
        }
    });

    // 메인 페이지 리뷰: 처음 5개만 표시
    const planReviewList = document.querySelector('.plan-review-list');
    if (planReviewList) {
        const reviewItems = planReviewList.querySelectorAll('.plan-review-item');
        const totalReviews = reviewItems.length;
        
        // 초기 설정: 5개 이후 리뷰 숨기기
        reviewItems.forEach((item, index) => {
            if (index >= 5) {
                item.style.display = 'none';
            }
        });
    }

    // 리뷰 모달 기능
    const reviewModal = document.getElementById('reviewModal');
    const reviewModalOverlay = document.getElementById('reviewModalOverlay');
    const reviewModalClose = document.getElementById('reviewModalClose');
    const reviewModalMoreBtn = document.querySelector('.review-modal-more-btn');
    const reviewModalList = document.querySelector('.review-modal-list');
    const planReviewMoreBtn = document.getElementById('planReviewMoreBtn');
    
    // 모달 열기 함수
    function openReviewModal() {
        if (reviewModal) {
            reviewModal.classList.add('review-modal-active');
            document.body.classList.add('review-modal-open');
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
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
    if (planReviewList) {
        const reviewItems = planReviewList.querySelectorAll('.plan-review-item');
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
    if (planReviewMoreBtn) {
        planReviewMoreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openReviewModal();
        });
    }
    
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
        
        // 모달이 열릴 때마다 초기화 (MutationObserver 또는 이벤트 리스너 사용)
        if (reviewModal) {
            // 모달이 열릴 때를 감지하기 위해 클래스 변경 감지
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
    
    // 리뷰 정렬 선택 기능 (페이지 리뷰)
    const planReviewSortSelect = document.getElementById('planReviewSortSelect');
    if (planReviewSortSelect) {
        planReviewSortSelect.addEventListener('change', function() {
            const sort = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('review_sort', sort);
            window.location.href = url.toString();
        });
    }
    
    // 리뷰 정렬 선택 기능 (모달)
    const reviewSortSelect = document.getElementById('reviewSortSelect');
    if (reviewSortSelect) {
        reviewSortSelect.addEventListener('change', function() {
            const sort = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('review_sort', sort);
            window.location.href = url.toString();
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
});
</script>

<?php
// 포인트 사용 모달 포함
$type = 'mvno';
$item_id = $plan_id;
$item_name = $plan['title'] ?? '알뜰폰 요금제';
include '../includes/components/point-usage-modal.php';
?>

<?php include '../includes/footer.php'; ?>
<script src="/MVNO/assets/js/favorite-heart.js" defer></script>
<script src="/MVNO/assets/js/point-usage-integration.js" defer></script>

<?php
// 리뷰 작성 모달 포함
$prefix = 'plan';
$speedLabel = '개통 빨라요';
$formId = 'planReviewForm';
$modalId = 'planReviewModal';
$textareaId = 'planReviewText';
include '../includes/components/order-review-modal.php';
?>

<script>
// 리뷰 작성 기능
document.addEventListener('DOMContentLoaded', function() {
    const reviewWriteBtn = document.getElementById('planReviewWriteBtn');
    const reviewModal = document.getElementById('planReviewModal');
    const reviewForm = document.getElementById('planReviewForm');
    const reviewModalOverlay = reviewModal ? reviewModal.querySelector('.plan-review-modal-overlay') : null;
    const reviewModalClose = reviewModal ? reviewModal.querySelector('.plan-review-modal-close') : null;
    
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
        const starLabels = reviewForm.querySelectorAll('.plan-star-label');
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
    const starLabels = reviewForm.querySelectorAll('.plan-star-label');
    starLabels.forEach(label => {
        label.addEventListener('click', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            const ratingType = this.closest('.plan-star-rating').getAttribute('data-rating-type');
            const radioInput = this.previousElementSibling;
            
            if (radioInput) {
                radioInput.checked = true;
            }
            
            // 같은 타입의 별점 업데이트
            const sameTypeLabels = reviewForm.querySelectorAll('.plan-star-rating[data-rating-type="' + ratingType + '"] .plan-star-label');
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
        const reviewText = document.getElementById('planReviewText').value.trim();
        
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
        formData.append('product_id', <?php echo $plan_id; ?>);
        formData.append('product_type', 'mvno');
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

<script>
// 신청하기 버튼에 포인트 모달 연동
document.addEventListener('DOMContentLoaded', function() {
    const applyBtn = document.getElementById('planApplyBtn');
    if (applyBtn) {
        applyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // 포인트 모달 열기
            const modalId = 'pointUsageModal_mvno_<?php echo $plan_id; ?>';
            const modal = document.getElementById(modalId);
            if (modal && typeof openPointUsageModal === 'function') {
                openPointUsageModal('mvno', <?php echo $plan_id; ?>);
            } else if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        });
    }
    
    // 포인트 사용 확인 후 기존 신청 모달 열기 (여기에 기존 신청 모달 열기 코드 추가)
    document.addEventListener('pointUsageConfirmed', function(e) {
        const { type, itemId, usedPoint } = e.detail;
        if (type === 'mvno') {
            // 기존 신청 모달 열기 로직
            // 예: openApplicationModal(itemId, usedPoint);
            console.log('포인트 사용 확인됨:', e.detail);
            // TODO: 기존 신청 모달 열기
        }
    });
});
</script>

