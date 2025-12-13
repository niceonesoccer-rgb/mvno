<?php
// 현재 페이지 설정
$current_page = 'mvno';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true; // 상세 페이지에서도 하단 메뉴바 표시

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';

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
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">가입 형태</div>
                            <div class="plan-detail-value">
                                <?php
                                $registrationTypes = [];
                                if (!empty($rawData['registration_types'])) {
                                    if (is_string($rawData['registration_types'])) {
                                        $registrationTypes = json_decode($rawData['registration_types'], true) ?: [];
                                    } else {
                                        $registrationTypes = $rawData['registration_types'];
                                    }
                                }
                                if (!empty($registrationTypes) && is_array($registrationTypes)) {
                                    echo htmlspecialchars(implode(', ', $registrationTypes));
                                } else {
                                    echo '정보 없음';
                                }
                                ?>
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
                                if (!empty($overLmsPrice) && trim($overLmsPrice) !== '') {
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
                // 로그인한 사용자에게만 리뷰 작성 버튼 표시 (이미 위에서 auth-functions.php 포함됨)
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
            <div class="apply-modal-step" id="step2" style="display: none;">
                <div class="plan-order-section">
                    <div class="plan-order-checkbox-group" id="subscriptionTypeButtons">
                        <!-- JavaScript로 동적으로 생성됨 -->
                    </div>
                </div>
            </div>
            
            <!-- 3단계: 개인정보 동의 및 신청 -->
            <div class="apply-modal-step" id="step3" style="display: none;">
                <form id="mvnoApplicationForm" class="consultation-form">
                    <input type="hidden" name="product_id" id="mvnoProductId" value="<?php echo $plan_id; ?>">
                    <input type="hidden" name="subscription_type" id="mvnoSubscriptionType" value="">
                    
                    <div class="consultation-form-group">
                        <label for="mvnoApplicationName" class="consultation-form-label">이름</label>
                        <input type="text" id="mvnoApplicationName" name="name" class="consultation-form-input" required>
                    </div>
                    
                    <div class="consultation-form-group">
                        <label for="mvnoApplicationPhone" class="consultation-form-label">휴대폰번호</label>
                        <input type="tel" id="mvnoApplicationPhone" name="phone" class="consultation-form-input" placeholder="010-1234-5678" required>
                    </div>
                    
                    <div class="consultation-form-group">
                        <label for="mvnoApplicationEmail" class="consultation-form-label">이메일</label>
                        <input type="email" id="mvnoApplicationEmail" name="email" class="consultation-form-input" placeholder="example@email.com" required>
                    </div>
                    
                    <div class="consultation-agreement-section">
                        <div class="consultation-agreement-all">
                            <div class="consultation-agreement-checkbox-wrapper">
                                <input type="checkbox" id="mvnoAgreementAll" class="consultation-agreement-checkbox consultation-agreement-all-checkbox">
                                <label for="mvnoAgreementAll" class="consultation-agreement-label consultation-agreement-all-label">
                                    전체 동의
                                </label>
                            </div>
                        </div>
                        
                        <div class="consultation-agreement-divider"></div>
                        
                        <div class="consultation-agreement-item">
                            <div class="consultation-agreement-checkbox-wrapper">
                                <input type="checkbox" id="mvnoAgreementPurpose" name="agreementPurpose" class="consultation-agreement-checkbox consultation-agreement-item-checkbox" required>
                                <label for="mvnoAgreementPurpose" class="consultation-agreement-label">
                                    개인정보 수집 및 이용목적에 동의합니까?
                                </label>
                                <button type="button" class="consultation-agreement-view-btn" data-agreement="purpose">내용보기</button>
                            </div>
                        </div>
                        
                        <div class="consultation-agreement-item">
                            <div class="consultation-agreement-checkbox-wrapper">
                                <input type="checkbox" id="mvnoAgreementItems" name="agreementItems" class="consultation-agreement-checkbox consultation-agreement-item-checkbox" required>
                                <label for="mvnoAgreementItems" class="consultation-agreement-label">
                                    개인정보 수집하는 항목에 동의합니까?
                                </label>
                                <button type="button" class="consultation-agreement-view-btn" data-agreement="items">내용보기</button>
                            </div>
                        </div>
                        
                        <div class="consultation-agreement-item">
                            <div class="consultation-agreement-checkbox-wrapper">
                                <input type="checkbox" id="mvnoAgreementPeriod" name="agreementPeriod" class="consultation-agreement-checkbox consultation-agreement-item-checkbox" required>
                                <label for="mvnoAgreementPeriod" class="consultation-agreement-label">
                                    개인정보 보유 및 이용기간에 동의합니까?
                                </label>
                                <button type="button" class="consultation-agreement-view-btn" data-agreement="period">내용보기</button>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="consultation-submit-btn" id="mvnoApplicationSubmitBtn">신청하기</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 개인정보 내용보기 모달 (MVNO용) -->
<div class="privacy-content-modal" id="mvnoPrivacyContentModal">
    <div class="privacy-content-modal-overlay" id="mvnoPrivacyContentModalOverlay"></div>
    <div class="privacy-content-modal-content">
        <div class="privacy-content-modal-header">
            <h3 class="privacy-content-modal-title" id="mvnoPrivacyContentModalTitle">개인정보 수집 및 이용목적</h3>
            <button class="privacy-content-modal-close" aria-label="닫기" id="mvnoPrivacyContentModalClose">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="privacy-content-modal-body" id="mvnoPrivacyContentModalBody">
            <!-- 내용이 JavaScript로 동적으로 채워짐 -->
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
        // 로그인 체크
        const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
        if (!isLoggedIn) {
            // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
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
        
        if (!applyModal) {
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
        
        // 가입 형태 선택 모달(2단계) 먼저 표시
        loadSubscriptionTypes();
        showStep(2);
    }
    
    // 모달 단계 관리
    let currentStep = 2;
    let selectedSubscriptionType = null;
    
    // 라디오 버튼 이벤트 설정 함수
    function setupRadioButtonEvents(container) {
        const radioInputs = container.querySelectorAll('input[name="subscriptionType"]');
        radioInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.checked) {
                    selectedSubscriptionType = this.value;
                    
                    // 모든 항목의 체크 스타일 제거
                    container.querySelectorAll('.plan-order-checkbox-item').forEach(item => {
                        item.classList.remove('plan-order-checkbox-checked');
                    });
                    
                    // 선택된 항목에 체크 스타일 적용
                    this.closest('.plan-order-checkbox-item').classList.add('plan-order-checkbox-checked');
                    
                    // 선택 즉시 step3로 이동
                    setTimeout(() => {
                        handleSubscriptionTypeSelection(this.value);
                    }, 200);
                }
            });
            
            // 라벨 클릭 시 라디오 버튼 선택
            const label = input.nextElementSibling;
            if (label && label.classList.contains('plan-order-checkbox-label')) {
                label.addEventListener('click', function(e) {
                    if (input.checked === false) {
                        input.checked = true;
                        input.dispatchEvent(new Event('change'));
                    }
                });
            }
        });
    }
    
    // 가입 형태 버튼 생성 함수
    function createSubscriptionTypeButton(type, container) {
        const item = document.createElement('div');
        item.className = 'plan-order-checkbox-item';
        item.innerHTML = `
            <input type="radio" id="subType_${type.type}" name="subscriptionType" value="${type.type}" class="plan-order-checkbox-input">
            <label for="subType_${type.type}" class="plan-order-checkbox-label">
                <div class="plan-order-checkbox-content">
                    <div class="plan-order-checkbox-title">${type.label}</div>
                    <div class="plan-order-checkbox-description">${type.description}</div>
                </div>
            </label>
        `;
        container.appendChild(item);
    }
    
    // 가입 형태 목록 로드
    function loadSubscriptionTypes() {
        const container = document.getElementById('subscriptionTypeButtons');
        if (!container) return;
        
        container.innerHTML = '';
        
        fetch('/MVNO/api/get-user-subscription-types.php')
            .then(response => response.json())
            .then(data => {
                let types = [];
                
                if (data.success && data.subscription_types && data.subscription_types.length > 0) {
                    types = data.subscription_types;
                } else {
                    // 가입 형태가 없으면 모든 형태 표시
                    types = [
                        { type: 'new', label: '신규가입', description: '새로운 번호로 가입할래요' },
                        { type: 'port', label: '번호이동', description: '지금 쓰는 번호 그대로 사용할래요' },
                        { type: 'change', label: '기기변경', description: '기기만 변경하고 번호는 유지할래요' }
                    ];
                }
                
                // 버튼 생성
                types.forEach(type => {
                    createSubscriptionTypeButton(type, container);
                });
                
                // 이벤트 설정
                setupRadioButtonEvents(container);
            })
            .catch(error => {
                // 오류 발생 시 기본 형태 표시
                const container = document.getElementById('subscriptionTypeButtons');
                if (container) {
                    const defaultTypes = [
                        { type: 'new', label: '신규가입', description: '새로운 번호로 가입할래요' },
                        { type: 'port', label: '번호이동', description: '지금 쓰는 번호 그대로 사용할래요' },
                        { type: 'change', label: '기기변경', description: '기기만 변경하고 번호는 유지할래요' }
                    ];
                    
                    defaultTypes.forEach(type => {
                        createSubscriptionTypeButton(type, container);
                    });
                    
                    setupRadioButtonEvents(container);
                }
            });
    }
    
    // 가입 형태 선택 처리
    function handleSubscriptionTypeSelection(type) {
        selectedSubscriptionType = type;
        // step3로 이동 및 사용자 정보 로드
        loadUserInfo();
        showStep(3);
    }
    
    // 휴대폰번호 검증 함수
    function validatePhoneNumber(phone) {
        const phoneNumbers = phone.replace(/[^\d]/g, '');
        return /^010\d{8}$/.test(phoneNumbers);
    }
    
    // 휴대폰번호 포맷팅 함수
    function formatPhoneNumber(phone) {
        const phoneNumbers = phone.replace(/[^\d]/g, '');
        if (phoneNumbers.length === 11 && phoneNumbers.startsWith('010')) {
            return '010-' + phoneNumbers.substring(3, 7) + '-' + phoneNumbers.substring(7, 11);
        }
        return phone;
    }
    
    // 사용자 정보 로드
    function loadUserInfo() {
        const nameInput = document.getElementById('mvnoApplicationName');
        const phoneInput = document.getElementById('mvnoApplicationPhone');
        const emailInput = document.getElementById('mvnoApplicationEmail');
        const subscriptionTypeInput = document.getElementById('mvnoSubscriptionType');
        
        // 가입 형태 저장
        if (subscriptionTypeInput) {
            subscriptionTypeInput.value = selectedSubscriptionType || '';
        }
        
        // 현재 로그인한 사용자 정보 가져오기
        fetch('/MVNO/api/get-current-user-info.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (nameInput && data.name) {
                        nameInput.value = data.name;
                    }
                    if (phoneInput && data.phone) {
                        phoneInput.value = formatPhoneNumber(data.phone);
                        
                        // 실시간 포맷팅
                        phoneInput.addEventListener('input', function() {
                            const value = this.value;
                            const formatted = formatPhoneNumber(value);
                            if (formatted !== value) {
                                this.value = formatted;
                            }
                        });
                        
                        // 포커스 아웃 시 검증
                        phoneInput.addEventListener('blur', function() {
                            const value = this.value.trim();
                            if (value && !validatePhoneNumber(value)) {
                                this.classList.add('input-error');
                            } else {
                                this.classList.remove('input-error');
                            }
                        });
                    }
                    if (emailInput && data.email) {
                        emailInput.value = data.email;
                    }
                }
            })
            .catch(error => {
                // 오류가 나도 계속 진행
            });
        
        // 실시간 이메일 검증
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                const value = this.value.trim();
                if (value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        this.classList.add('input-error');
                    } else {
                        this.classList.remove('input-error');
                    }
                }
            });
        }
    }
    
    // 단계 표시 함수
    const applyModalBack = document.getElementById('applyModalBack');
    
    function showStep(stepNumber) {
        const step2 = document.getElementById('step2');
        const step3 = document.getElementById('step3');
        const modalTitle = document.querySelector('.apply-modal-title');
        
        if (stepNumber === 2) {
            if (step2) step2.style.display = 'block';
            if (step3) step3.style.display = 'none';
            if (modalTitle) modalTitle.textContent = '가입유형';
            if (applyModalBack) applyModalBack.style.display = 'none'; // step2에서는 뒤로가기 숨김
            currentStep = 2;
        } else if (stepNumber === 3) {
            if (step2) step2.style.display = 'none';
            if (step3) step3.style.display = 'block';
            // 모달 제목: 가입신청
            if (modalTitle) {
                modalTitle.textContent = '가입신청';
            }
            // 뒤로 가기 버튼 표시
            if (applyModalBack) applyModalBack.style.display = 'flex';
            currentStep = 3;
        }
    }
    
    // 뒤로 가기 버튼 이벤트
    if (applyModalBack) {
        applyModalBack.addEventListener('click', function() {
            // step3에서 뒤로 가기 시 step2로 이동
            if (currentStep === 3) {
                showStep(2);
            }
        });
    }
    

    // 신청하기 버튼 클릭 이벤트
    if (applyBtn) {
        applyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openApplyModal();
            return false;
        });
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
        showStep(2);
        selectedSubscriptionType = null;
        
        // 폼 초기화
        const form = document.getElementById('mvnoApplicationForm');
        if (form) {
            form.reset();
        }
    }
    
    // MVNO 신청 폼 제출 이벤트
    const mvnoApplicationForm = document.getElementById('mvnoApplicationForm');
    if (mvnoApplicationForm) {
        mvnoApplicationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // 필수 필드 검증
            const nameInput = document.getElementById('mvnoApplicationName');
            const phoneInput = document.getElementById('mvnoApplicationPhone');
            const emailInput = document.getElementById('mvnoApplicationEmail');
            
            if (!nameInput || !nameInput.value.trim()) {
                alert('이름을 입력해주세요.');
                if (nameInput) nameInput.focus();
                return;
            }
            
            if (!phoneInput || !phoneInput.value.trim()) {
                alert('휴대폰 번호를 입력해주세요.');
                if (phoneInput) phoneInput.focus();
                return;
            }
            
            // 휴대폰번호 검증 (010으로 시작하는 11자리)
            const phoneNumbers = phoneInput.value.replace(/[^\d]/g, '');
            if (!/^010\d{8}$/.test(phoneNumbers)) {
                alert('010으로 시작하는 11자리 휴대폰 번호를 입력해주세요.');
                if (phoneInput) phoneInput.focus();
                return;
            }
            
            if (!emailInput || !emailInput.value.trim()) {
                alert('이메일을 입력해주세요.');
                if (emailInput) emailInput.focus();
                return;
            }
            
            // 이메일 검증
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.value.trim())) {
                alert('올바른 이메일 주소를 입력해주세요.');
                if (emailInput) emailInput.focus();
                return;
            }
            
            // 모든 동의 체크박스 확인
            const agreementPurpose = document.getElementById('mvnoAgreementPurpose');
            const agreementItems = document.getElementById('mvnoAgreementItems');
            const agreementPeriod = document.getElementById('mvnoAgreementPeriod');
            
            if (!agreementPurpose.checked || !agreementItems.checked || !agreementPeriod.checked) {
                alert('모든 개인정보 동의 항목에 동의해주세요.');
                return;
            }
            
            // 제출 버튼 비활성화
            const submitBtn = document.getElementById('mvnoApplicationSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '처리 중...';
            }
            
            // 폼 데이터 준비
            const formData = new FormData(this);
            
            // 서버로 데이터 전송
            fetch('/MVNO/api/submit-mvno-application.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 신청정보가 DB에 저장됨
                    
                    // redirect_url이 있으면 해당 URL로 이동
                    if (data.redirect_url && data.redirect_url.trim() !== '') {
                        window.location.href = data.redirect_url;
                    } else {
                        // redirect_url이 없으면 모달 닫기
                        if (typeof showAlert === 'function') {
                            showAlert('신청이 완료되었습니다.', '신청 완료');
                        } else {
                            alert('신청이 완료되었습니다.');
                        }
                        closeApplyModal();
                    }
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
    
    // MVNO 개인정보 동의 체크박스 처리
    const mvnoAgreementAll = document.getElementById('mvnoAgreementAll');
    const mvnoAgreementItemCheckboxes = document.querySelectorAll('#mvnoApplicationForm .consultation-agreement-item-checkbox');
    
    // 전체 동의 체크박스 변경 이벤트
    if (mvnoAgreementAll) {
        mvnoAgreementAll.addEventListener('change', function() {
            const isChecked = this.checked;
            mvnoAgreementItemCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        });
    }
    
    // 개별 체크박스 변경 이벤트 (전체 동의 상태 업데이트)
    mvnoAgreementItemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(mvnoAgreementItemCheckboxes).every(cb => cb.checked);
            if (mvnoAgreementAll) {
                mvnoAgreementAll.checked = allChecked;
            }
        });
    });
    
    // MVNO 개인정보 내용보기 모달
    const mvnoPrivacyModal = document.getElementById('mvnoPrivacyContentModal');
    const mvnoPrivacyModalOverlay = document.getElementById('mvnoPrivacyContentModalOverlay');
    const mvnoPrivacyModalClose = document.getElementById('mvnoPrivacyContentModalClose');
    const mvnoPrivacyModalTitle = document.getElementById('mvnoPrivacyContentModalTitle');
    const mvnoPrivacyModalBody = document.getElementById('mvnoPrivacyContentModalBody');
    
    // 개인정보 내용 정의
    const mvnoPrivacyContents = {
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
    
    // MVNO 개인정보 내용보기 모달 열기
    function openMvnoPrivacyModal(type) {
        if (!mvnoPrivacyModal || !mvnoPrivacyContents[type]) return;
        
        mvnoPrivacyModalTitle.textContent = mvnoPrivacyContents[type].title;
        mvnoPrivacyModalBody.innerHTML = mvnoPrivacyContents[type].content;
        
        mvnoPrivacyModal.style.display = 'flex';
        mvnoPrivacyModal.classList.add('privacy-content-modal-active');
    }
    
    // MVNO 개인정보 내용보기 모달 닫기
    function closeMvnoPrivacyModal() {
        if (!mvnoPrivacyModal) return;
        
        mvnoPrivacyModal.classList.remove('privacy-content-modal-active');
    }
    
    // MVNO 개인정보 내용보기 버튼 클릭 이벤트
    const mvnoViewBtns = document.querySelectorAll('#mvnoApplicationForm .consultation-agreement-view-btn');
    mvnoViewBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const type = this.getAttribute('data-agreement');
            openMvnoPrivacyModal(type);
        });
    });
    
    // MVNO 개인정보 내용보기 모달 닫기 이벤트
    if (mvnoPrivacyModalOverlay) {
        mvnoPrivacyModalOverlay.addEventListener('click', closeMvnoPrivacyModal);
    }
    
    if (mvnoPrivacyModalClose) {
        mvnoPrivacyModalClose.addEventListener('click', closeMvnoPrivacyModal);
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
        if (e.key === 'Escape') {
            if (mvnoPrivacyModal && mvnoPrivacyModal.classList.contains('privacy-content-modal-active')) {
                closeMvnoPrivacyModal();
            } else if (applyModal && applyModal.classList.contains('apply-modal-active')) {
                closeApplyModal();
            } else if (reviewModal && reviewModal.classList.contains('review-modal-active')) {
                closeReviewModal();
            }
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
    
    // 포인트 사용 확인 후 기존 신청 모달 열기
    document.addEventListener('pointUsageConfirmed', function(e) {
        const { type, itemId } = e.detail;
        if (type === 'mvno' && itemId === <?php echo $plan_id; ?>) {
            openApplyModal();
        }
    });
});
</script>

