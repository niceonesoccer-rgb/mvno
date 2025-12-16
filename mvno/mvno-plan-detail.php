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

// 조회수 업데이트
require_once '../includes/data/product-functions.php';
incrementProductView($plan_id);

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
                                $contractPeriodDays = isset($rawData['contract_period_days']) ? intval($rawData['contract_period_days']) : 0;
                                
                                if ($contractPeriod === '무약정' || $contractPeriod === 'none') {
                                    echo '무약정';
                                } elseif (!empty($contractPeriod) && $contractPeriod !== '직접입력') {
                                    // DB에 저장된 값이 "181일" 또는 "2개월" 형식이면 그대로 표시
                                    echo htmlspecialchars($contractPeriod);
                                } elseif ($contractPeriodDays > 0) {
                                    // 하위 호환성: contract_period_days만 있는 경우
                                    echo htmlspecialchars($contractPeriodDays . '일');
                                } else {
                                    echo '없음';
                                }
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
                                if ($callType === '직접입력' && !empty($callAmount)) {
                                    // DB에 저장된 값이 "300분" 형식이면 그대로 표시, 아니면 숫자만 추출해서 표시
                                    if (preg_match('/^(\d+)(.+)$/', $callAmount, $matches)) {
                                        echo number_format((float)$matches[1]) . $matches[2];
                                    } else {
                                        echo htmlspecialchars($callAmount);
                                    }
                                } elseif (!empty($callType)) {
                                    echo htmlspecialchars($callType);
                                } else {
                                    echo '정보 없음';
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
                                if ($smsType === '직접입력' && !empty($smsAmount)) {
                                    // DB에 저장된 값이 "50건" 또는 "10원/건" 형식이면 그대로 표시
                                    if (preg_match('/^(\d+)(.+)$/', $smsAmount, $matches)) {
                                        echo number_format((float)$matches[1]) . $matches[2];
                                    } else {
                                        echo htmlspecialchars($smsAmount);
                                    }
                                } elseif (!empty($smsType)) {
                                    echo htmlspecialchars($smsType);
                                } else {
                                    echo '정보 없음';
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
                                if ($dataAmount === '직접입력' && !empty($dataAmountValue)) {
                                    // DB에 저장된 값이 "100GB" 형식이면 그대로 표시
                                    if (preg_match('/^(\d+)(.+)$/', $dataAmountValue, $matches)) {
                                        echo '월 ' . number_format((float)$matches[1]) . $matches[2];
                                    } else {
                                        echo '월 ' . htmlspecialchars($dataAmountValue);
                                    }
                                } elseif (!empty($dataAmount)) {
                                    // 그 외는 type 값 그대로 표시 (무제한 등)
                                    echo htmlspecialchars($dataAmount);
                                } else {
                                    echo '정보 없음';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">데이터 추가제공</div>
                            <div class="plan-detail-value">
                                <?php
                                $dataAdditional = $rawData['data_additional'] ?? '';
                                $dataAdditionalValue = $rawData['data_additional_value'] ?? '';
                                if ($dataAdditional === '직접입력' && !empty($dataAdditionalValue)) {
                                    echo htmlspecialchars($dataAdditionalValue);
                                } elseif (!empty($dataAdditional)) {
                                    echo htmlspecialchars($dataAdditional);
                                } else {
                                    echo '없음';
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
                            <div class="plan-detail-label">부가·영상통화</div>
                            <div class="plan-detail-value">
                                <?php
                                $additionalCallType = $rawData['additional_call_type'] ?? '';
                                $additionalCall = $rawData['additional_call'] ?? '';
                                if ($additionalCallType === '직접입력' && !empty($additionalCall)) {
                                    // DB에 저장된 값이 "100분" 형식이면 그대로 표시
                                    if (preg_match('/^(\d+)(.+)$/', $additionalCall, $matches)) {
                                        echo number_format((float)$matches[1]) . $matches[2];
                                    } else {
                                        echo htmlspecialchars($additionalCall);
                                    }
                                } elseif (!empty($additionalCallType)) {
                                    echo htmlspecialchars($additionalCallType);
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
                                    // DB에 저장된 값이 "50GB" 형식이면 그대로 표시
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
                                    // DB에 저장된 값이 "7700원" 형식이면 그대로 표시
                                    if (preg_match('/^(\d+)(.+)$/', $regularSimPrice, $matches)) {
                                        echo '배송가능 (' . number_format((float)$matches[1]) . $matches[2] . ')';
                                    } else {
                                        echo '배송가능 (' . number_format((float)$regularSimPrice) . '원)';
                                    }
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
                                    // DB에 저장된 값이 "7700원" 형식이면 그대로 표시
                                    if (preg_match('/^(\d+)(.+)$/', $nfcSimPrice, $matches)) {
                                        echo '배송가능 (' . number_format((float)$matches[1]) . $matches[2] . ')';
                                    } else {
                                        echo '배송가능 (' . number_format((float)$nfcSimPrice) . '원)';
                                    }
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
                                    // DB에 저장된 값이 "7700원" 형식이면 그대로 표시
                                    if (preg_match('/^(\d+)(.+)$/', $esimPrice, $matches)) {
                                        echo '개통가능 (' . number_format((float)$matches[1]) . $matches[2] . ')';
                                    } else {
                                        echo '개통가능 (' . number_format((float)$esimPrice) . '원)';
                                    }
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
                                    // DB에 저장된 값이 "22.53원/MB" 형식이면 그대로 표시
                                    echo htmlspecialchars($overDataPrice);
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
                                    // DB에 저장된 값이 "1.98원/초" 형식이면 그대로 표시
                                    echo htmlspecialchars($overVoicePrice);
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
                                    // DB에 저장된 값이 "1.98원/초" 형식이면 그대로 표시
                                    echo htmlspecialchars($overVideoPrice);
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
                                    // DB에 저장된 값이 "22원/건" 형식이면 그대로 표시
                                    echo htmlspecialchars($overSmsPrice);
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
                                    // DB에 저장된 값이 "33원/건" 형식이면 그대로 표시
                                    echo htmlspecialchars($overLmsPrice);
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
                                    // DB에 저장된 값이 "110원/건" 형식이면 그대로 표시
                                    echo htmlspecialchars($overMmsPrice);
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
            <button class="share-modal-item" data-platform="link" id="shareLinkBtn">
                <div class="share-modal-icon share-icon-link">
                    <img src="/MVNO/assets/images/icons/share-link.svg" alt="링크 복사" width="32" height="32">
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
                    
                    <!-- 체크박스 -->
                    <div class="internet-checkbox-group">
                        <label class="internet-checkbox-all">
                            <input type="checkbox" id="mvnoAgreementAll" class="internet-checkbox-input" onchange="toggleAllMvnoAgreements(this.checked)">
                            <span class="internet-checkbox-label">전체 동의</span>
                        </label>
                        <div class="internet-checkbox-list">
                            <div class="internet-checkbox-item-wrapper">
                                <div class="internet-checkbox-item">
                                    <label class="internet-checkbox-label-item">
                                        <input type="checkbox" id="mvnoAgreementPurpose" name="agreementPurpose" class="internet-checkbox-input-item" onchange="checkAllMvnoAgreements();" required>
                                        <span class="internet-checkbox-text" style="font-size: 1.0625rem !important;">개인정보 수집 및 이용목적에 동의합니까?</span>
                                    </label>
                                    <a href="#" class="internet-checkbox-link" id="mvnoPurposeArrowLink" onclick="event.preventDefault(); openMvnoPrivacyModal('purpose'); return false;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                            <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="internet-checkbox-item-wrapper">
                                <div class="internet-checkbox-item">
                                    <label class="internet-checkbox-label-item">
                                        <input type="checkbox" id="mvnoAgreementItems" name="agreementItems" class="internet-checkbox-input-item" onchange="checkAllMvnoAgreements();" required>
                                        <span class="internet-checkbox-text" style="font-size: 1.0625rem !important;">개인정보 수집하는 항목에 동의합니까?</span>
                                    </label>
                                    <a href="#" class="internet-checkbox-link" id="mvnoItemsArrowLink" onclick="event.preventDefault(); openMvnoPrivacyModal('items'); return false;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                            <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="internet-checkbox-item-wrapper">
                                <div class="internet-checkbox-item">
                                    <label class="internet-checkbox-label-item">
                                        <input type="checkbox" id="mvnoAgreementPeriod" name="agreementPeriod" class="internet-checkbox-input-item" onchange="checkAllMvnoAgreements();" required>
                                        <span class="internet-checkbox-text" style="font-size: 1.0625rem !important;">개인정보 보유 및 이용기간에 동의합니까?</span>
                                    </label>
                                    <a href="#" class="internet-checkbox-link" id="mvnoPeriodArrowLink" onclick="event.preventDefault(); openMvnoPrivacyModal('period'); return false;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                            <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="internet-checkbox-item-wrapper">
                                <div class="internet-checkbox-item">
                                    <label class="internet-checkbox-label-item">
                                        <input type="checkbox" id="mvnoAgreementThirdParty" name="agreementThirdParty" class="internet-checkbox-input-item" onchange="checkAllMvnoAgreements();" required>
                                        <span class="internet-checkbox-text" style="font-size: 1.0625rem !important;">개인정보 제3자 제공에 동의합니까?</span>
                                    </label>
                                    <a href="#" class="internet-checkbox-link" id="mvnoThirdPartyArrowLink" onclick="event.preventDefault(); openMvnoPrivacyModal('thirdParty'); return false;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                            <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                        </svg>
                                    </a>
                                </div>
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
// 아코디언 토글 함수 (전역으로 노출)
function toggleMvnoAccordionByArrow(accordionId, arrowLinkId) {
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
    // 현재 페이지 URL 가져오기
    const currentUrl = window.location.href;

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

    // 링크 복사 기능
    const shareLinkBtn = document.getElementById('shareLinkBtn');
    if (shareLinkBtn) {
        shareLinkBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // URL 복사 함수
            function copyUrl() {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    return navigator.clipboard.writeText(currentUrl).then(function() {
                        return true;
                    }).catch(function() {
                        // 클립보드 API 실패 시 fallback
                        return fallbackCopyTextToClipboard(currentUrl);
                    });
                } else {
                    // 클립보드 API 미지원 시 fallback
                    return fallbackCopyTextToClipboard(currentUrl);
                }
            }
            
            // URL 복사 실행
            copyUrl().then(function(success) {
                if (success) {
                    // 모달로 복사 완료 알림
                    if (typeof showAlert === 'function') {
                        showAlert('복사되었습니다.', '링크 복사 완료').then(() => {
                            closeModal();
                        });
                    } else {
                        alert('복사되었습니다.');
                        closeModal();
                    }
                } else {
                    // 복사 실패 시
                    if (typeof showAlert === 'function') {
                        showAlert('링크 복사에 실패했습니다.', '오류');
                    } else {
                        alert('링크 복사에 실패했습니다.');
                    }
                }
            });
        });
    }

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
    
    // 상품의 가입형태 데이터 (PHP에서 전달)
    const productRegistrationTypes = <?php
        $registrationTypes = [];
        if (!empty($rawData['registration_types'])) {
            if (is_string($rawData['registration_types'])) {
                $registrationTypes = json_decode($rawData['registration_types'], true) ?: [];
            } else {
                $registrationTypes = $rawData['registration_types'];
            }
        }
        // 가입형태 매핑: "신규" -> "new", "번이" -> "port", "기변" -> "change"
        $mappedTypes = [];
        foreach ($registrationTypes as $type) {
            if ($type === '신규') {
                $mappedTypes[] = 'new';
            } elseif ($type === '번이') {
                $mappedTypes[] = 'port';
            } elseif ($type === '기변') {
                $mappedTypes[] = 'change';
            }
        }
        echo json_encode($mappedTypes, JSON_UNESCAPED_UNICODE);
    ?>;

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
        
        // 모든 가입형태 정의
        const allSubscriptionTypes = [
            { type: 'new', label: '신규가입', description: '새로운 번호로 가입할래요' },
            { type: 'port', label: '번호이동', description: '지금 쓰는 번호 그대로 사용할래요' },
            { type: 'change', label: '기기변경', description: '기기만 변경하고 번호는 유지할래요' }
        ];
        
        // 상품에 체크된 가입형태만 필터링
        let availableTypes = [];
        if (productRegistrationTypes && productRegistrationTypes.length > 0) {
            // 상품에 체크된 가입형태만 표시
            availableTypes = allSubscriptionTypes.filter(type => 
                productRegistrationTypes.includes(type.type)
            );
        } else {
            // 상품에 가입형태가 설정되지 않은 경우 모든 형태 표시 (기존 동작 유지)
            availableTypes = allSubscriptionTypes;
        }
        
        // 버튼 생성
        availableTypes.forEach(type => {
            createSubscriptionTypeButton(type, container);
        });
        
        // 이벤트 설정
        setupRadioButtonEvents(container);
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
                            checkAllMvnoAgreements();
                        });
                        
                        // 포커스 아웃 시 검증
                        phoneInput.addEventListener('blur', function() {
                            const value = this.value.trim();
                            if (value && !validatePhoneNumber(value)) {
                                this.classList.add('input-error');
                            } else {
                                this.classList.remove('input-error');
                            }
                            checkAllMvnoAgreements();
                        });
                    }
                    
                    // 이름 입력 시 검증
                    if (nameInput) {
                        nameInput.addEventListener('input', checkAllMvnoAgreements);
                        nameInput.addEventListener('blur', checkAllMvnoAgreements);
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
                        // 모든 공백 제거 (앞뒤 + 내부)
                        let redirectUrl = data.redirect_url.replace(/\s+/g, '').trim();
                        // URL이 프로토콜(http:// 또는 https://)을 포함하지 않으면 https:// 추가
                        if (!/^https?:\/\//i.test(redirectUrl)) {
                            redirectUrl = 'https://' + redirectUrl;
                        }
                        window.location.href = redirectUrl;
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
    const mvnoAgreementItemCheckboxes = document.querySelectorAll('#mvnoApplicationForm .internet-checkbox-input-item');
    
    // 전체 동의 체크박스 변경 이벤트
    if (mvnoAgreementAll) {
        mvnoAgreementAll.addEventListener('change', function() {
            const isChecked = this.checked;
            mvnoAgreementItemCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            checkAllMvnoAgreements();
        });
    }
    
    // 개별 체크박스 변경 이벤트 (전체 동의 상태 업데이트)
    mvnoAgreementItemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            checkAllMvnoAgreements();
        });
    });
    
    // 전체 동의 토글 함수
    function toggleAllMvnoAgreements(checked) {
        const mvnoAgreementPurpose = document.getElementById('mvnoAgreementPurpose');
        const mvnoAgreementItems = document.getElementById('mvnoAgreementItems');
        const mvnoAgreementPeriod = document.getElementById('mvnoAgreementPeriod');
        const mvnoAgreementThirdParty = document.getElementById('mvnoAgreementThirdParty');
        
        if (mvnoAgreementPurpose && mvnoAgreementItems && mvnoAgreementPeriod && mvnoAgreementThirdParty) {
            mvnoAgreementPurpose.checked = checked;
            mvnoAgreementItems.checked = checked;
            mvnoAgreementPeriod.checked = checked;
            mvnoAgreementThirdParty.checked = checked;
            checkAllMvnoAgreements();
        }
    }
    
    // 전체 동의 상태 확인 함수
    function checkAllMvnoAgreements() {
        const mvnoAgreementAll = document.getElementById('mvnoAgreementAll');
        const mvnoAgreementPurpose = document.getElementById('mvnoAgreementPurpose');
        const mvnoAgreementItems = document.getElementById('mvnoAgreementItems');
        const mvnoAgreementPeriod = document.getElementById('mvnoAgreementPeriod');
        const mvnoAgreementThirdParty = document.getElementById('mvnoAgreementThirdParty');
        const submitBtn = document.getElementById('mvnoApplicationSubmitBtn');
        const nameInput = document.getElementById('mvnoApplicationName');
        const phoneInput = document.getElementById('mvnoApplicationPhone');
        
        if (mvnoAgreementAll && mvnoAgreementPurpose && mvnoAgreementItems && mvnoAgreementPeriod && mvnoAgreementThirdParty && submitBtn) {
            // 전체 동의 체크박스 상태 업데이트
            mvnoAgreementAll.checked = mvnoAgreementPurpose.checked && mvnoAgreementItems.checked && mvnoAgreementPeriod.checked && mvnoAgreementThirdParty.checked;
            
            // 이름과 휴대폰 번호 확인
            const name = nameInput ? nameInput.value.trim() : '';
            const phone = phoneInput ? phoneInput.value.replace(/[^\d]/g, '') : '';
            
            // 제출 버튼 활성화/비활성화 (모든 필드가 입력되어야 활성화)
            const isNameValid = name.length > 0;
            const isPhoneValid = phone.length === 11 && phone.startsWith('010');
            const isAgreementsChecked = mvnoAgreementPurpose.checked && mvnoAgreementItems.checked && mvnoAgreementPeriod.checked && mvnoAgreementThirdParty.checked;
            
            if (isNameValid && isPhoneValid && isAgreementsChecked) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
    }
    
    
    // MVNO 개인정보 내용보기 모달
    const mvnoPrivacyModal = document.getElementById('mvnoPrivacyContentModal');
    const mvnoPrivacyModalOverlay = document.getElementById('mvnoPrivacyContentModalOverlay');
    const mvnoPrivacyModalClose = document.getElementById('mvnoPrivacyContentModalClose');
    const mvnoPrivacyModalTitle = document.getElementById('mvnoPrivacyContentModalTitle');
    const mvnoPrivacyModalBody = document.getElementById('mvnoPrivacyContentModalBody');
    
    // 개인정보 내용 정의 (설정 파일에서 로드)
    <?php
    require_once __DIR__ . '/../includes/data/privacy-functions.php';
    $privacySettings = getPrivacySettings();
    echo "const mvnoPrivacyContents = " . json_encode($privacySettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
    ?>
    
    // MVNO 개인정보 내용보기 모달 열기 (전역으로 노출)
    window.openMvnoPrivacyModal = function(type) {
        if (!mvnoPrivacyModal || !mvnoPrivacyContents[type]) return;
        
        mvnoPrivacyModalTitle.textContent = mvnoPrivacyContents[type].title;
        mvnoPrivacyModalBody.innerHTML = mvnoPrivacyContents[type].content;
        
        mvnoPrivacyModal.style.display = 'flex';
        mvnoPrivacyModal.classList.add('privacy-content-modal-active');
        document.body.style.overflow = 'hidden';
    };
    
    // MVNO 개인정보 내용보기 모달 닫기
    function closeMvnoPrivacyModal() {
        if (!mvnoPrivacyModal) return;
        
        mvnoPrivacyModal.classList.remove('privacy-content-modal-active');
        mvnoPrivacyModal.style.display = 'none';
        document.body.style.overflow = '';
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

<style>
/* 체크박스 스타일 (인터넷 신청과 동일) */
/* 아코디언 텍스트 크기를 플랜 카드 기능 텍스트와 동일하게 설정 */
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
    font-size: 1.0625rem !important; /* 17px - 플랜 카드의 "통화 기본제공 | 문자 무제한 | KT알뜰폰 | 5G" 텍스트와 동일한 크기 */
    font-weight: 500 !important;
    color: #6b7280;
    margin-left: 0.5rem;
}

/* 더 구체적인 선택자로 확실하게 적용 */
.internet-checkbox-label-item .internet-checkbox-text {
    font-size: 1.0625rem !important; /* 17px */
    font-weight: 500 !important;
}

.internet-checkbox-link {
    margin-left: auto;
    color: #6b7280;
    display: flex;
    align-items: center;
    text-decoration: none;
}

.internet-checkbox-link svg {
    width: 16px;
    height: 16px;
    transition: transform 0.3s ease;
}

.internet-checkbox-link svg.arrow-down {
    transform: rotate(0deg);
}

.internet-checkbox-link:hover {
    color: #374151;
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

<script src="/MVNO/assets/js/favorite-heart.js" defer></script>
<script src="/MVNO/assets/js/point-usage-integration.js" defer></script>

<?php include '../includes/footer.php'; ?>