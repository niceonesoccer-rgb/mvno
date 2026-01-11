<?php
// 현재 페이지 설정
$current_page = 'mvno';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true; // 상세 페이지에서도 하단 메뉴바 표시

// 경로 설정 파일 먼저 로드
require_once '../includes/data/path-config.php';

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';
require_once '../includes/data/privacy-functions.php';

// 개인정보 설정 로드
$privacySettings = getPrivacySettings();

// 요금제 ID 가져오기
$plan_id = isset($_GET['id']) ? intval($_GET['id']) : 32627;

// 요금제 데이터 가져오기 (SEO 메타 태그 생성을 위해 헤더 전에 로드)
require_once '../includes/data/plan-data.php';
$plan = getPlanDetailData($plan_id);

// 상품 SEO 메타 태그 생성 (header.php에서 사용)
require_once '../includes/data/seo-functions.php';
if ($plan) {
    $productSEO = generateProductSEO($plan, 'mvno');
}

// 헤더 포함
include '../includes/header.php';

// 조회수 업데이트
require_once '../includes/data/product-functions.php';
incrementProductView($plan_id);

// 요금제 데이터 가져오기
require_once '../includes/data/plan-data.php';
$plan = getPlanDetailData($plan_id);
$rawData = $plan['_raw_data'] ?? []; // 원본 DB 데이터 (null 대신 빈 배열로 초기화)


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
if (!$plan) {
    http_response_code(404);
    die('상품을 찾을 수 없습니다.');
}

// 일반 사용자가 판매종료 상품에 접근하는 경우 차단
if (!$isAdmin && isset($plan['status']) && $plan['status'] === 'inactive') {
    http_response_code(404);
    die('판매종료된 상품입니다.');
}

// 리뷰 목록 가져오기 (탭 네비게이션용)
// 정렬 방식 가져오기 (기본값: 최신순)
$sort = $_GET['review_sort'] ?? 'created_desc';
if (!in_array($sort, ['rating_desc', 'rating_asc', 'created_desc'])) {
    $sort = 'created_desc';
}

// 리뷰 목록 가져오기 (같은 판매자의 같은 타입의 모든 상품 리뷰 통합)
$allReviews = getProductReviews($plan_id, 'mvno', 1000, $sort);
$reviews = array_slice($allReviews, 0, 5); // 페이지에는 처음 5개만 표시
$reviewCount = count($allReviews);
$hasReviews = !empty($allReviews) && $reviewCount > 0;
$remainingCount = max(0, $reviewCount - 5);

// 카테고리별 평균 별점 가져오기
$categoryAverages = getInternetReviewCategoryAverages($plan_id, 'mvno');

// 총별점: kindness와 speed의 평균으로 계산 (통신사단독유심과 동일한 방식)
if ($categoryAverages['kindness'] > 0 && $categoryAverages['speed'] > 0) {
    $averageRating = round(($categoryAverages['kindness'] + $categoryAverages['speed']) / 2, 1);
} else {
    // 폴백: 기존 방식 사용
    $averageRating = getProductAverageRating($plan_id, 'mvno');
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
?>

<main class="main-content plan-detail-page">
    <!-- 요금제 상세 레이아웃 (모듈 사용) -->
    <?php include '../includes/layouts/plan-detail-layout.php'; ?>

    <!-- 탭 네비게이션 -->
    <section class="plan-detail-tabs-section">
        <div class="content-layout">
            <div class="plan-detail-tabs">
                <button class="plan-detail-tab active" data-tab="info">상세정보</button>
                <?php if ($hasReviews): ?>
                <button class="plan-detail-tab" data-tab="review">리뷰(<?php echo number_format($reviewCount); ?>개)</button>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 탭 컨텐츠: 상세정보 -->
    <section class="plan-detail-tab-content active" id="tab-info">
        <!-- 요금제 상세 정보 섹션 (통합) -->
        <section class="plan-detail-info-section">
            <div class="content-layout">
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
                            <div class="plan-detail-label">텍스트형(LMS)</div>
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
    </section>

    <!-- 포인트 할인 혜택 설정 섹션 -->
    <?php if (isset($plan['point_setting']) && $plan['point_setting'] > 0): ?>
    <section class="plan-point-benefit-section">
        <div class="content-layout">
            <div class="plan-info-card">
                <h3 class="plan-info-card-title">포인트 할인 혜택 설정</h3>
                <div class="plan-info-card-content">
                    <table class="product-info-table" style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; width: 200px;">포인트설정금액</th>
                            <td style="padding: 12px; color: #1f2937; border-bottom: 1px solid #e5e7eb;">
                                <?php echo number_format($plan['point_setting']); ?>P
                            </td>
                        </tr>
                        <?php if (!empty($plan['point_benefit_description'])): ?>
                        <tr>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; vertical-align: top;">할인혜택내용</th>
                            <td style="padding: 12px; color: #1f2937; border-bottom: 1px solid #e5e7eb; line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($plan['point_benefit_description'])); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- 탭 컨텐츠: 리뷰 -->
    <?php if ($hasReviews): ?>
    <section class="plan-detail-tab-content" id="tab-review">
        <!-- 통신사 리뷰 섹션 -->
    <?php
    // 정렬 방식 가져오기 (기본값: 최신순)
    $sort = $_GET['review_sort'] ?? 'created_desc';
    if (!in_array($sort, ['rating_desc', 'rating_asc', 'created_desc'])) {
        $sort = 'created_desc';
    }
    
    // 리뷰 목록 가져오기 (정렬 변경 시 다시 가져오기)
    $allReviews = getProductReviews($plan_id, 'mvno', 1000, $sort);
    $reviews = array_slice($allReviews, 0, 3); // 페이지에는 처음 3개만 표시
    $reviewCount = count($allReviews);
    $remainingCount = max(0, $reviewCount - 3);
    
    // 카테고리별 평균 별점 가져오기
    $categoryAverages = getInternetReviewCategoryAverages($plan_id, 'mvno');
    
    // 총별점: kindness와 speed의 평균으로 계산 (통신사단독유심과 동일한 방식)
    if ($categoryAverages['kindness'] > 0 && $categoryAverages['speed'] > 0) {
        $averageRating = round(($categoryAverages['kindness'] + $categoryAverages['speed']) / 2, 1);
    } else {
        // 폴백: 기존 방식 사용
        $averageRating = getProductAverageRating($plan_id, 'mvno');
    }
    
    // 판매자명 가져오기
    $sellerName = '';
    try {
        require_once '../includes/data/product-functions.php';
        $sellerId = $rawData['seller_id'] ?? null;
        if ($sellerId) {
            $seller = getSellerById($sellerId);
            if ($seller) {
                $sellerName = getSellerDisplayName($seller);
            }
        }
    } catch (Exception $e) {
        error_log("MVNO Plan Detail - Error getting seller name: " . $e->getMessage());
    }
    
    // pending 상태의 리뷰를 자동으로 approved로 변경 (기존 pending 리뷰 처리용)
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            // 해당 상품의 pending 상태 리뷰를 approved로 변경
            $updateStmt = $pdo->prepare("UPDATE product_reviews SET status = 'approved' WHERE product_id = :product_id AND product_type = 'mvno' AND status = 'pending'");
            $updateStmt->execute([':product_id' => $plan_id]);
            $updatedCount = $updateStmt->rowCount();
            if ($updatedCount > 0) {
                error_log("MVNO Plan Detail - Auto-approved {$updatedCount} pending review(s) for product_id: {$plan_id}");
                // 리뷰 목록 다시 가져오기 (새로고침 효과)
                $allReviews = getProductReviews($plan_id, 'mvno', 1000, $sort);
                $reviews = array_slice($allReviews, 0, 3);
                $reviewCount = count($allReviews);
                $remainingCount = max(0, $reviewCount - 3);
            }
        }
    } catch (Exception $e) {
        error_log("MVNO Plan Detail - Exception while auto-approving reviews: " . $e->getMessage());
    }
    ?>
    
    <section class="plan-review-section" id="planReviewSection" style="padding: 2rem 0; background: #f9fafb;">
        <div class="content-layout">
            <div class="plan-review-header">
                <span class="plan-review-logo-text" style="color: #6366f1;"><?php echo htmlspecialchars($sellerName ?: ($plan['provider'] ?? '알뜰폰')); ?></span>
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
                        <select class="plan-review-sort-select" id="planReviewSortSelect" aria-label="리뷰 정렬 방식 선택" onchange="window.location.href='?id=<?php echo $plan_id; ?>&review_sort=' + this.value">
                            <option value="rating_desc" <?php echo $sort === 'rating_desc' ? 'selected' : ''; ?>>높은 평점순</option>
                            <option value="rating_asc" <?php echo $sort === 'rating_asc' ? 'selected' : ''; ?>>낮은 평점순</option>
                            <option value="created_desc" <?php echo $sort === 'created_desc' ? 'selected' : ''; ?>>최신순</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="plan-review-list" id="planReviewList">
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
            </div>
            
            <?php if ($remainingCount > 0): ?>
                <button class="plan-review-more-btn" id="planReviewMoreBtn" data-total-reviews="<?php echo $reviewCount; ?>" data-sort="<?php echo htmlspecialchars($sort); ?>">
                    리뷰 <?php echo number_format($remainingCount); ?>개 더보기
                </button>
            <?php endif; ?>
        </div>
    </section>
    </section>
    <?php endif; ?>
</main>

<style>
.plan-detail-tabs-section {
    background: #fff;
    border-bottom: none;
    position: sticky;
    top: 0;
    z-index: 10;
    margin-top: 0;
    margin-bottom: 0;
    padding-top: 0;
    padding-bottom: 0;
}

.plan-detail-tabs-section .content-layout {
    padding-top: 0;
    padding-bottom: 0;
}

.plan-detail-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid transparent;
}

.plan-detail-tab {
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    font-size: 1rem;
    font-weight: 500;
    color: var(--color-gray-700);
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    outline: none;
}

.plan-detail-tab:hover {
    color: var(--color-gray-700);
    background: #f9fafb;
}

.plan-detail-tab:focus,
.plan-detail-tab:active,
.plan-detail-tab:focus-visible {
    outline: none;
    border: none;
    border-bottom: 2px solid transparent;
}

.plan-detail-tab.active {
    color: var(--color-gray-700);
    border-bottom-color: #EF4444;
    font-weight: 600;
}

.plan-detail-tab.active:focus,
.plan-detail-tab.active:active,
.plan-detail-tab.active:focus-visible {
    outline: none;
    border: none;
    border-bottom: 2px solid #EF4444;
    color: var(--color-gray-700);
}

.plan-detail-tab-content {
    display: none;
}

.plan-detail-tab-content.active {
    display: block;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.plan-detail-tab');
    const tabContents = document.querySelectorAll('.plan-detail-tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // 모든 탭 비활성화
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // 선택한 탭 활성화
            this.classList.add('active');
            const targetContent = document.getElementById('tab-' + targetTab);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
    
    // 더보기 버튼 클릭 시 모달 열기
    const reviewMoreBtn = document.getElementById('planReviewMoreBtn');
    if (reviewMoreBtn) {
        reviewMoreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof window.openReviewListModal === 'function') {
                window.openReviewListModal();
            }
        });
    }
});
</script>

<!-- 리뷰 모달 -->
<div class="review-modal" id="reviewModal">
    <div class="review-modal-overlay" id="reviewModalOverlay"></div>
    <div class="review-modal-content">
        <div class="review-modal-header">
            <h3 class="review-modal-title"><?php echo htmlspecialchars($sellerName ?: ($plan['provider'] ?? '쉐이크모바일')); ?></h3>
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
            <button class="review-modal-more-btn" id="reviewModalMoreBtn" style="display: none;">리뷰 더보기</button>
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
                    <img src="<?php echo getAssetPath('/assets/images/icons/share-link.svg'); ?>" alt="링크 복사" width="32" height="32">
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
                        <span id="mvnoApplicationPhoneError" class="form-error-message" style="display: none; color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem;"></span>
                    </div>
                    
                    <div class="consultation-form-group">
                        <label for="mvnoApplicationEmail" class="consultation-form-label">이메일</label>
                        <input type="email" id="mvnoApplicationEmail" name="email" class="consultation-form-input" placeholder="example@email.com" required>
                        <span id="mvnoApplicationEmailError" class="form-error-message" style="display: none; color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem;"></span>
                    </div>
                    
                    <!-- 체크박스 -->
                    <div class="internet-checkbox-group">
                        <?php
                        // 동의 항목 정의 (순서대로)
                        $agreementItems = [
                            'purpose' => ['id' => 'mvnoAgreementPurpose', 'name' => 'agreementPurpose', 'modal' => 'openMvnoPrivacyModal'],
                            'items' => ['id' => 'mvnoAgreementItems', 'name' => 'agreementItems', 'modal' => 'openMvnoPrivacyModal'],
                            'period' => ['id' => 'mvnoAgreementPeriod', 'name' => 'agreementPeriod', 'modal' => 'openMvnoPrivacyModal'],
                            'thirdParty' => ['id' => 'mvnoAgreementThirdParty', 'name' => 'agreementThirdParty', 'modal' => 'openMvnoPrivacyModal'],
                            'serviceNotice' => ['id' => 'mvnoAgreementServiceNotice', 'name' => 'service_notice_opt_in', 'accordion' => 'mvnoServiceNoticeContent', 'accordionFunc' => 'toggleMvnoAccordion'],
                            'marketing' => ['id' => 'mvnoAgreementMarketing', 'name' => 'marketing_opt_in', 'accordion' => 'mvnoMarketingContent', 'accordionFunc' => 'toggleMvnoAccordion']
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
                            <input type="checkbox" id="mvnoAgreementAll" class="internet-checkbox-input">
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
                                    <a href="#" class="internet-checkbox-link" id="mvno<?php echo ucfirst($key); ?>ArrowLink" onclick="event.preventDefault(); <?php echo $item['modal']; ?>('<?php echo $key; ?>'); return false;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                            <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                        </svg>
                                    </a>
                                    <?php elseif (isset($item['accordion'])): ?>
                                    <a href="#" class="internet-checkbox-link" onclick="event.preventDefault(); openMvnoPrivacyModal('<?php echo $key; ?>'); return false;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                            <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php if ($key === 'serviceNotice'): ?>
                                <div class="internet-accordion-content" id="mvnoServiceNoticeContent">
                                    <div class="internet-accordion-inner">
                                        <div class="internet-accordion-section">
                                            <div style="font-size: 0.875rem; color: #6b7280; line-height: 1.65;">
                                                <?php echo $setting['content'] ?? ''; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php elseif ($key === 'marketing'): ?>
                                <div class="internet-accordion-content" id="mvnoMarketingContent">
                                    <div class="internet-accordion-inner">
                                        <div class="internet-accordion-section">
                                            <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 0.75rem 0;">광고성 정보를 받으시려면 아래 항목을 선택해주세요</p>
                                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                    <input type="checkbox" id="mvnoMarketingEmail" name="marketing_email_opt_in" class="mvno-marketing-channel" style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;">
                                                    <span style="font-size: 0.875rem; color: #374151;">이메일 수신동의</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                    <input type="checkbox" id="mvnoMarketingSmsSns" name="marketing_sms_sns_opt_in" class="mvno-marketing-channel" style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;">
                                                    <span style="font-size: 0.875rem; color: #374151;">SMS, SNS 수신동의</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                    <input type="checkbox" id="mvnoMarketingPush" name="marketing_push_opt_in" class="mvno-marketing-channel" style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;">
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
                    
                    <button type="submit" class="consultation-submit-btn" id="mvnoApplicationSubmitBtn">신청하기</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.internet-accordion-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    margin-top: 0;
    margin-left: 2rem;
}

.internet-accordion-content.active {
    max-height: 500px;
    overflow-y: auto;
    overflow-x: hidden;
    transition: max-height 0.4s ease-in;
    margin-top: 0.75rem;
}

.internet-accordion-inner {
    background-color: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
}

.arrow-up {
    transform: rotate(180deg);
    transition: transform 0.3s ease;
}

/* 리뷰 목록 모달 스타일 */
.review-list-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.3s;
    overflow-y: auto;
    padding: 20px;
}

.review-list-modal[style*="display: block"],
.review-list-modal.show {
    opacity: 1;
    visibility: visible;
}

.review-list-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 10000;
}

.review-list-modal-content {
    position: relative;
    background: #ffffff;
    border-radius: 24px;
    width: 100%;
    max-width: 900px;
    max-height: calc(100vh - 40px);
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    transform: scale(0.95) translateY(20px);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    z-index: 10001;
    margin: auto;
}

.review-list-modal.show .review-list-modal-content {
    transform: scale(1) translateY(0);
}

.review-list-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 28px 32px 24px;
    border-bottom: 1px solid #f1f5f9;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
}

.review-list-modal-title {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    letter-spacing: -0.02em;
}

.review-list-modal-close {
    background: #f1f5f9;
    border: none;
    cursor: pointer;
    padding: 10px;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    width: 40px;
    height: 40px;
}

.review-list-modal-close:hover {
    background: #e2e8f0;
    color: #1e293b;
    transform: rotate(90deg);
}

.review-list-modal-body {
    padding: 32px;
    overflow-y: auto;
    flex: 1;
}

.review-list-modal-sort-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.review-list-modal-count {
    font-size: 16px;
    font-weight: 600;
    color: #374151;
}

.review-list-modal-sort-select-wrapper {
    position: relative;
}

.review-list-modal-sort-select {
    padding: 8px 32px 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    background: #ffffff;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L6 6L11 1' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    transition: all 0.2s;
}

.review-list-modal-sort-select:hover {
    border-color: #9ca3af;
}

.review-list-modal-sort-select:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.review-list-modal-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.review-list-modal-item {
    padding: 20px;
    background: #f9fafb;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.review-list-modal-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.review-list-modal-item-author {
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
}

.review-list-modal-item-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.review-list-modal-item-stars {
    font-size: 16px;
    color: #EF4444;
}

.review-list-modal-item-time {
    font-size: 14px;
    color: #6b7280;
}

.review-list-modal-item-content {
    font-size: 15px;
    line-height: 1.6;
    color: #374151;
    white-space: pre-wrap;
    word-break: break-word;
}

.review-list-modal-more-wrapper {
    margin-top: 24px;
    text-align: center;
}

.review-list-modal-more-btn {
    padding: 14px 32px;
    background: #ffffff;
    color: #374151;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    width: 100%;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    outline: none;
}

.review-list-modal-more-btn:focus {
    outline: none;
    border-color: #d1d5db;
}

.review-list-modal-more-btn:hover {
    background: #f9fafb;
    border-color: #9ca3af;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.review-list-modal-more-btn:active {
    transform: translateY(0);
    background: #f3f4f6;
}

@media (max-width: 640px) {
    .review-list-modal {
        padding: 0;
    }
    
    .review-list-modal-content {
        width: 100%;
        max-width: 100%;
        border-radius: 0;
        max-height: 100vh;
    }
    
    .review-list-modal-header {
        padding: 24px 20px 20px;
    }
    
    .review-list-modal-body {
        padding: 24px 20px;
    }
    
    .review-list-modal-title {
        font-size: 20px;
    }
    
    .review-list-modal-sort-wrapper {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .review-list-modal-sort-select-wrapper {
        width: 100%;
    }
    
    .review-list-modal-sort-select {
        width: 100%;
    }
}
</style>

<!-- 리뷰 목록 모달 -->
<div class="review-list-modal" id="reviewListModal" style="display: none;">
    <div class="review-list-modal-overlay" id="reviewListModalOverlay"></div>
    <div class="review-list-modal-content">
        <div class="review-list-modal-header">
            <h3 class="review-list-modal-title">리뷰 전체보기</h3>
            <button class="review-list-modal-close" id="reviewListModalClose" aria-label="닫기">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="review-list-modal-body">
            <div class="review-list-modal-sort-wrapper">
                <span class="review-list-modal-count" id="reviewListModalCount">총 0개</span>
                <div class="review-list-modal-sort-select-wrapper">
                    <select class="review-list-modal-sort-select" id="reviewListModalSortSelect" aria-label="리뷰 정렬 방식 선택">
                        <option value="rating_desc">높은 평점순</option>
                        <option value="rating_asc">낮은 평점순</option>
                        <option value="created_desc" selected>최신순</option>
                    </select>
                </div>
            </div>
            <div class="review-list-modal-list" id="reviewListModalList">
                <!-- 리뷰 목록이 JavaScript로 동적으로 채워짐 -->
            </div>
            <div class="review-list-modal-more-wrapper" id="reviewListModalMoreWrapper" style="display: none; margin-top: 24px; text-align: center;">
                <button class="review-list-modal-more-btn" id="reviewListModalMoreBtn">리뷰 더보기</button>
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
// 관리자 페이지 설정 로드 (DB의 app_settings 테이블)
<?php
// 이미 위에서 로드했으므로 재사용 (일관성 유지)
// $privacySettings는 12줄에서 이미 로드됨
echo "const mvnoPrivacyContents = " . json_encode($privacySettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";

// 모든 리뷰 데이터를 JavaScript로 전달 (모달용)
if ($hasReviews && function_exists('getProductReviews')) {
    $sortForModal = $_GET['review_sort'] ?? 'created_desc';
    if (!in_array($sortForModal, ['rating_desc', 'rating_asc', 'created_desc'])) {
        $sortForModal = 'created_desc';
    }
    $allReviewsForModal = getProductReviews($plan_id, 'mvno', 1000, $sortForModal);
    
    // 리뷰 데이터 준비 (created_at을 포함하여 상대 시간 계산 가능하도록)
    $reviewsForJS = [];
    foreach ($allReviewsForModal as $review) {
        $reviewsForJS[] = [
            'author_name' => $review['author_name'] ?? '익명',
            'provider' => $review['provider'] ?? '',
            'stars' => $review['stars'] ?? '★★★★★',
            'created_at' => $review['created_at'] ?? '',
            'content' => $review['content'] ?? '',
            'rating' => $review['rating'] ?? 0
        ];
    }
    echo "const allReviewsData = " . json_encode($reviewsForJS, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
    echo "const reviewCount = " . count($allReviewsForModal) . ";\n";
} else {
    echo "const allReviewsData = [];\n";
    echo "const reviewCount = 0;\n";
}
?>

// 리뷰 모달 관련 공통 함수들 (전역으로 정의)
window.getReviewRelativeTime = function(datetime) {
    if (!datetime) return '';
    try {
        const reviewTime = new Date(datetime);
        const now = new Date();
        const diffMs = now - reviewTime;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffDays === 0) {
            if (diffMins === 0) return '방금 전';
            if (diffHours === 0) return diffMins + '분 전';
            return diffHours + '시간 전';
        }
        if (diffDays === 1) return '어제';
        if (diffDays < 7) return diffDays + '일 전';
        if (diffDays < 30) return Math.floor(diffDays / 7) + '주 전';
        if (diffDays < 365) return Math.floor(diffDays / 30) + '개월 전';
        return Math.floor(diffDays / 365) + '년 전';
    } catch (e) {
        return '';
    }
};

window.createReviewItemHTML = function(review) {
    const authorName = (review.author_name || '익명').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const provider = review.provider ? review.provider.replace(/</g, '&lt;').replace(/>/g, '&gt;') : '';
    const stars = (review.stars || '★★★★★').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const content = (review.content || '').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, ' ');
    const timeAgo = window.getReviewRelativeTime(review.created_at);
    return `
        <div class="review-list-modal-item">
            <div class="review-list-modal-item-header">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span class="review-list-modal-item-author">${authorName}</span>
                    ${provider ? `<span class="plan-review-provider-badge">${provider}</span>` : ''}
                </div>
                <div class="review-list-modal-item-right">
                    <div class="review-list-modal-item-stars">${stars}</div>
                    ${timeAgo ? `<span class="review-list-modal-item-time">${timeAgo}</span>` : ''}
                </div>
            </div>
            <p class="review-list-modal-item-content">${content}</p>
        </div>
    `;
};

window.renderReviewItems = function(reviews, startIndex, count) {
    if (!reviews || reviews.length === 0) {
        return '<div class="review-list-modal-item"><p class="review-list-modal-item-content" style="text-align: center; color: #9ca3af; padding: 40px 0;">등록된 리뷰가 없습니다.</p></div>';
    }
    const endIndex = Math.min(startIndex + count, reviews.length);
    return reviews.slice(startIndex, endIndex).map(review => window.createReviewItemHTML(review)).join('');
};

// 리뷰 목록 모달 열기 함수 (전역으로 먼저 정의)
window.openReviewListModal = function() {
    const modal = document.getElementById('reviewListModal');
    const modalList = document.getElementById('reviewListModalList');
    const modalCount = document.getElementById('reviewListModalCount');
    const modalSortSelect = document.getElementById('reviewListModalSortSelect');
    
    if (!modal) {
        console.error('Review modal element not found');
        return;
    }
    
    if (typeof allReviewsData === 'undefined') {
        console.error('allReviewsData is undefined');
        alert('리뷰 데이터를 불러올 수 없습니다.');
        return;
    }
    
    // 리뷰 정렬 함수
    function sortReviews(reviews, sortType) {
        const sorted = [...reviews];
        switch(sortType) {
            case 'rating_desc':
                return sorted.sort((a, b) => (b.rating || 0) - (a.rating || 0));
            case 'rating_asc':
                return sorted.sort((a, b) => (a.rating || 0) - (b.rating || 0));
            case 'created_desc':
            default:
                return sorted.sort((a, b) => {
                    const dateA = new Date(a.created_at || 0);
                    const dateB = new Date(b.created_at || 0);
                    return dateB - dateA;
                });
        }
    }
    
    // 현재 정렬 방식 가져오기 (기본값: 최신순)
    const currentSort = modalSortSelect ? modalSortSelect.value : 'created_desc';
    const sortedReviews = sortReviews(allReviewsData, currentSort);
    
    // 전역 변수에 정렬된 리뷰 저장
    window.reviewModalSortedReviews = sortedReviews;
    window.reviewModalDisplayedCount = 0;
    
    // 처음 10개만 표시
    modalList.innerHTML = window.renderReviewItems(sortedReviews, 0, 10);
    window.reviewModalDisplayedCount = Math.min(10, sortedReviews.length);
    
    // 더보기 버튼 표시/숨김 및 텍스트 업데이트 처리
    const modalMoreBtn = document.getElementById('reviewListModalMoreBtn');
    const modalMoreWrapper = document.getElementById('reviewListModalMoreWrapper');
    if (modalMoreWrapper) {
        if (window.reviewModalDisplayedCount < sortedReviews.length) {
            modalMoreWrapper.style.display = 'block';
            // 남은 개수 표시
            const remainingCount = sortedReviews.length - window.reviewModalDisplayedCount;
            if (modalMoreBtn) {
                modalMoreBtn.textContent = `리뷰 더보기 (${remainingCount.toLocaleString()}개)`;
            }
        } else {
            modalMoreWrapper.style.display = 'none';
        }
    }
    
    // 리뷰 개수 업데이트
    if (modalCount) {
        const totalCount = typeof reviewCount !== 'undefined' ? reviewCount : (allReviewsData ? allReviewsData.length : 0);
        modalCount.textContent = `총 ${totalCount.toLocaleString()}개`;
    }
    
    // 정렬 선택 값 설정
    if (modalSortSelect) {
        modalSortSelect.value = currentSort;
    }
    
    // 스크롤 위치 저장 (전역 변수에 저장하여 닫을 때 사용)
    window.reviewModalScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
    const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
    
    // 모달 열기
    document.body.style.overflow = 'hidden';
    document.body.style.position = 'fixed';
    document.body.style.top = `-${window.reviewModalScrollPosition}px`;
    document.body.style.width = '100%';
    document.body.style.paddingRight = `${scrollbarWidth}px`;
    
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
};

// 리뷰 목록 모달 닫기 및 이벤트 처리
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('reviewListModal');
    const modalOverlay = document.getElementById('reviewListModalOverlay');
    const modalClose = document.getElementById('reviewListModalClose');
    const modalSortSelect = document.getElementById('reviewListModalSortSelect');
    const modalList = document.getElementById('reviewListModalList');
    
    // 모달 닫기 함수
    function closeReviewListModal() {
        if (!modal) return;
        
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            
            // 스크롤 위치 복원
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.body.style.paddingRight = '';
            
            if (window.reviewModalScrollPosition !== undefined) {
                window.scrollTo(0, window.reviewModalScrollPosition);
            }
        }, 300);
    }
    
    // 모달 닫기 이벤트
    if (modalOverlay) {
        modalOverlay.addEventListener('click', closeReviewListModal);
    }
    
    if (modalClose) {
        modalClose.addEventListener('click', closeReviewListModal);
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('show')) {
            closeReviewListModal();
        }
    });
    
    // 정렬 함수
    function sortReviews(reviews, sortType) {
        const sorted = [...reviews];
        switch(sortType) {
            case 'rating_desc':
                return sorted.sort((a, b) => (b.rating || 0) - (a.rating || 0));
            case 'rating_asc':
                return sorted.sort((a, b) => (a.rating || 0) - (b.rating || 0));
            case 'created_desc':
            default:
                return sorted.sort((a, b) => {
                    const dateA = new Date(a.created_at || 0);
                    const dateB = new Date(b.created_at || 0);
                    return dateB - dateA;
                });
        }
    }
    
    // 정렬 변경 이벤트
    if (modalSortSelect && modalList) {
        modalSortSelect.addEventListener('change', function() {
            if (typeof allReviewsData === 'undefined') return;
            
            const sortedReviews = sortReviews(allReviewsData, this.value);
            window.reviewModalSortedReviews = sortedReviews;
            window.reviewModalDisplayedCount = 0;
            
            // 처음 10개만 표시
            modalList.innerHTML = window.renderReviewItems(sortedReviews, 0, 10);
            window.reviewModalDisplayedCount = Math.min(10, sortedReviews.length);
            
            // 더보기 버튼 표시/숨김 및 텍스트 업데이트 처리
            const modalMoreWrapper = document.getElementById('reviewListModalMoreWrapper');
            const modalMoreBtn = document.getElementById('reviewListModalMoreBtn');
            if (modalMoreWrapper) {
                if (window.reviewModalDisplayedCount < sortedReviews.length) {
                    modalMoreWrapper.style.display = 'block';
                    // 남은 개수 표시
                    const remainingCount = sortedReviews.length - window.reviewModalDisplayedCount;
                    if (modalMoreBtn) {
                        modalMoreBtn.textContent = `리뷰 더보기 (${remainingCount.toLocaleString()}개)`;
                    }
                } else {
                    modalMoreWrapper.style.display = 'none';
                }
            }
            
            // 스크롤을 맨 위로
            if (modalList) {
                modalList.scrollTop = 0;
            }
        });
    }
    
    // 더보기 버튼 클릭 이벤트
    const modalMoreBtn = document.getElementById('reviewListModalMoreBtn');
    const modalMoreWrapper = document.getElementById('reviewListModalMoreWrapper');
    if (modalMoreBtn && modalList) {
        modalMoreBtn.addEventListener('click', function() {
            if (typeof window.reviewModalSortedReviews === 'undefined') return;
            
            const sortedReviews = window.reviewModalSortedReviews;
            const currentCount = window.reviewModalDisplayedCount || 0;
            
            // 다음 10개 가져오기
            const nextReviews = window.renderReviewItems(sortedReviews, currentCount, 10);
            modalList.insertAdjacentHTML('beforeend', nextReviews);
            
            // 표시된 개수 업데이트
            window.reviewModalDisplayedCount = Math.min(currentCount + 10, sortedReviews.length);
            
            // 더보기 버튼 표시/숨김 및 텍스트 업데이트 처리
            if (modalMoreWrapper) {
                if (window.reviewModalDisplayedCount < sortedReviews.length) {
                    modalMoreWrapper.style.display = 'block';
                    // 남은 개수 표시
                    const remainingCount = sortedReviews.length - window.reviewModalDisplayedCount;
                    if (modalMoreBtn) {
                        modalMoreBtn.textContent = `리뷰 더보기 (${remainingCount.toLocaleString()}개)`;
                    }
                } else {
                    modalMoreWrapper.style.display = 'none';
                }
            }
        });
    }
});

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

// 전체 동의 상태 확인 함수 (전역 스코프 - 인라인 핸들러에서 호출됨)
function checkAllMvnoAgreements() {
    const mvnoAgreementAll = document.getElementById('mvnoAgreementAll');
    const submitBtn = document.getElementById('mvnoApplicationSubmitBtn');
    const nameInput = document.getElementById('mvnoApplicationName');
    const phoneInput = document.getElementById('mvnoApplicationPhone');
    const emailInput = document.getElementById('mvnoApplicationEmail');

    if (!mvnoAgreementAll || !submitBtn) return;

    // mvnoPrivacyContents에서 필수 항목 확인
    const requiredItems = [];
    const agreementMap = {
        'purpose': 'mvnoAgreementPurpose',
        'items': 'mvnoAgreementItems',
        'period': 'mvnoAgreementPeriod',
        'thirdParty': 'mvnoAgreementThirdParty',
        'serviceNotice': 'mvnoAgreementServiceNotice',
        'marketing': 'mvnoAgreementMarketing'
    };

    if (typeof mvnoPrivacyContents !== 'undefined') {
        // DB에서 로드한 설정 기반으로 필수 항목 결정
        for (const [key, id] of Object.entries(agreementMap)) {
            const setting = mvnoPrivacyContents[key];
            if (!setting) continue;
            
            // 노출 여부 확인: isVisible이 false이면 화면에 없으므로 필수 항목에서 제외
            const isVisible = setting.isVisible !== false;
            
            // 필수 여부 확인: isRequired가 true이고 노출된 항목만 필수 항목에 추가
            if (setting.isRequired === true && isVisible) {
                requiredItems.push(id);
            }
        }
    } else {
        // 기본값: marketing 제외 모두 필수
        requiredItems.push('mvnoAgreementPurpose', 'mvnoAgreementItems', 'mvnoAgreementPeriod', 'mvnoAgreementThirdParty', 'mvnoAgreementServiceNotice');
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
    if (mvnoAgreementAll) {
        mvnoAgreementAll.checked = allChecked;
    }

    // 이름, 휴대폰 번호, 이메일 확인
    const name = nameInput ? nameInput.value.trim() : '';
    const phone = phoneInput ? phoneInput.value.replace(/[^\d]/g, '') : '';
    const email = emailInput ? emailInput.value.trim() : '';

    // 제출 버튼 활성화/비활성화 (모든 필드가 입력되어야 활성화)
    const isNameValid = name.length > 0;
    const isPhoneValid = phone.length === 11 && phone.startsWith('010');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isEmailValid = email.length > 0 && emailRegex.test(email);
    
    // 필수 동의 항목 모두 체크되었는지 확인
    let isAgreementsChecked = true;
    for (const itemId of requiredItems) {
        const checkbox = document.getElementById(itemId);
        if (checkbox && !checkbox.checked) {
            isAgreementsChecked = false;
            break;
        }
    }

    if (isNameValid && isPhoneValid && isEmailValid && isAgreementsChecked) {
        submitBtn.disabled = false;
    } else {
        submitBtn.disabled = true;
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
        // 가입형태 매핑: "신규" -> "new", "번이" -> "mnp", "기변" -> "change"
        $mappedTypes = [];
        foreach ($registrationTypes as $type) {
            if ($type === '신규') {
                $mappedTypes[] = 'new';
            } elseif ($type === '번이') {
                $mappedTypes[] = 'mnp';
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
            { type: 'mnp', label: '번호이동', description: '지금 쓰는 번호 그대로 사용할래요' },
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
        const apiPath = window.API_PATH || (window.BASE_PATH || '') + '/api';
        fetch(apiPath + '/get-current-user-info.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (nameInput && data.name) {
                        nameInput.value = data.name;
                    }
                    if (phoneInput && data.phone) {
                        phoneInput.value = formatPhoneNumber(data.phone);
                        // 값 설정 후 즉시 검증
                        setTimeout(() => {
                            validatePhoneOnModal();
                        }, 10);
                    }
                    
                    // 전화번호 실시간 검증 (데이터 로드 여부와 관계없이 항상 설정)
                    if (phoneInput) {
                        const phoneErrorElement = document.getElementById('mvnoApplicationPhoneError');
                        
                        // 실시간 포맷팅 및 검증
                        phoneInput.addEventListener('input', function() {
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
                            
                            checkAllMvnoAgreements();
                        });
                        
                        // 포커스 아웃 시 검증
                        phoneInput.addEventListener('blur', function() {
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
                        // 값 설정 후 즉시 검증
                        setTimeout(() => {
                            validateEmailOnModal();
                        }, 10);
                    }
                    
                    // 사용자 정보 로드 후 검증 수행 (즉시 검증)
                    setTimeout(function() {
                        validatePhoneOnModal();
                        validateEmailOnModal();
                        checkAllMvnoAgreements();
                    }, 50);
                } else {
                    // 사용자 정보가 없어도 기존 값이 있으면 검증
                    setTimeout(function() {
                        validatePhoneOnModal();
                        validateEmailOnModal();
                        checkAllMvnoAgreements();
                    }, 50);
                }
            })
            .catch(error => {
                // 오류가 나도 계속 진행
                // 에러 발생 시에도 검증 수행
                setTimeout(function() {
                    validatePhoneOnModal();
                    validateEmailOnModal();
                    checkAllMvnoAgreements();
                }, 50);
            });
        
        // 모달이 열릴 때마다 즉시 검증 (사용자 정보 로드와 관계없이)
        setTimeout(function() {
            validatePhoneOnModal();
            validateEmailOnModal();
            checkAllMvnoAgreements();
        }, 100);
        
        // 실시간 이메일 검증
        if (emailInput) {
            const emailErrorElement = document.getElementById('mvnoApplicationEmailError');
            
            emailInput.addEventListener('input', function(e) {
                // 대문자를 소문자로 자동 변환
                const cursorPosition = this.selectionStart;
                const originalValue = this.value;
                const lowerValue = originalValue.toLowerCase();
                
                // 소문자로 변환된 값이 다르면 업데이트
                if (originalValue !== lowerValue) {
                    this.value = lowerValue;
                    // 커서 위치 복원
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
                
                checkAllMvnoAgreements();
            });
            
            emailInput.addEventListener('blur', function() {
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
                
                checkAllMvnoAgreements();
            });
        }
    }
    
    // 단계 표시 함수
    const applyModalBack = document.getElementById('applyModalBack');
    
    // 전화번호 검증 함수
    function validatePhoneOnModal() {
        const phoneInput = document.getElementById('mvnoApplicationPhone');
        const phoneErrorElement = document.getElementById('mvnoApplicationPhoneError');
        
        if (phoneInput && phoneErrorElement) {
            const value = phoneInput.value.trim();
            const phoneNumbers = value.replace(/[^\d]/g, '');
            
            if (value && phoneNumbers.length > 0) {
                if (phoneNumbers.length === 11 && phoneNumbers.startsWith('010')) {
                    phoneInput.classList.remove('input-error');
                    phoneErrorElement.style.display = 'none';
                    phoneErrorElement.textContent = '';
                    return true;
                } else {
                    phoneInput.classList.add('input-error');
                    phoneErrorElement.style.display = 'block';
                    phoneErrorElement.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                    return false;
                }
            } else if (value) {
                phoneInput.classList.add('input-error');
                phoneErrorElement.style.display = 'block';
                phoneErrorElement.textContent = '전화번호 형식에 맞게 입력해주세요. (010-1234-5678 형식)';
                return false;
            } else {
                phoneInput.classList.remove('input-error');
                phoneErrorElement.style.display = 'none';
                phoneErrorElement.textContent = '';
                return true; // 빈 값은 유효 (필수 필드 검증은 별도)
            }
        }
        return true;
    }
    
    // 이메일 검증 함수
    function validateEmailOnModal() {
        const emailInput = document.getElementById('mvnoApplicationEmail');
        const emailErrorElement = document.getElementById('mvnoApplicationEmailError');
        
        if (emailInput && emailErrorElement) {
            const value = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (value.length > 0) {
                if (emailRegex.test(value)) {
                    emailInput.classList.remove('input-error');
                    emailErrorElement.style.display = 'none';
                    emailErrorElement.textContent = '';
                    return true;
                } else {
                    emailInput.classList.add('input-error');
                    emailErrorElement.style.display = 'block';
                    emailErrorElement.textContent = '이메일 형식에 맞게 입력해주세요. (example@email.com 형식)';
                    return false;
                }
            } else {
                emailInput.classList.remove('input-error');
                emailErrorElement.style.display = 'none';
                emailErrorElement.textContent = '';
                return true; // 빈 값은 유효 (필수 필드 검증은 별도)
            }
        }
        return true;
    }
    
    // 아코디언 토글 함수
    function toggleMvnoAccordion(accordionId, arrowLink) {
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
    
    // 개인정보 내용보기 모달 열기 함수
    function openMvnoPrivacyModal(type) {
        const modal = document.getElementById('mvnoPrivacyContentModal');
        const modalTitle = document.getElementById('mvnoPrivacyContentModalTitle');
        const modalBody = document.getElementById('mvnoPrivacyContentModalBody');
        
        if (!modal || !modalTitle || !modalBody) return;
        
        if (typeof mvnoPrivacyContents !== 'undefined' && mvnoPrivacyContents[type]) {
            modalTitle.textContent = mvnoPrivacyContents[type].title || '';
            modalBody.innerHTML = mvnoPrivacyContents[type].content || '';
        } else {
            return; // 데이터가 없으면 모달을 열지 않음
        }
        
        modal.style.display = 'flex';
        modal.classList.add('privacy-content-modal-active');
        document.body.style.overflow = 'hidden';
    }
    
    // 개인정보 내용보기 모달 닫기 함수
    function closeMvnoPrivacyModal() {
        const modal = document.getElementById('mvnoPrivacyContentModal');
        if (!modal) return;
        
        modal.classList.remove('privacy-content-modal-active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    
    // 모달 닫기 이벤트 리스너
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('mvnoPrivacyContentModal');
        const modalOverlay = document.getElementById('mvnoPrivacyContentModalOverlay');
        const modalClose = document.getElementById('mvnoPrivacyContentModalClose');
        
        if (modalOverlay) {
            modalOverlay.addEventListener('click', closeMvnoPrivacyModal);
        }
        
        if (modalClose) {
            modalClose.addEventListener('click', closeMvnoPrivacyModal);
        }
        
        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal && modal.classList.contains('privacy-content-modal-active')) {
                closeMvnoPrivacyModal();
            }
        });
    });
    
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
            
            // step3로 이동할 때 전화번호와 이메일 즉시 검증
            // DOM 업데이트를 기다리지 않고 즉시 검증
            validatePhoneOnModal();
            validateEmailOnModal();
            setTimeout(function() {
                validatePhoneOnModal();
                validateEmailOnModal();
                checkAllMvnoAgreements();
            }, 50); // DOM 업데이트 후 재검증
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
    

    // 중복 제출 방지 플래그
    let isSubmitting = false;
    
    // 신청하기 버튼 클릭 이벤트
    if (applyBtn) {
        applyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // 로그인 체크
            const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
            if (!isLoggedIn) {
                // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
                const currentUrl = window.location.href;
                const apiPath = window.API_PATH || (window.BASE_PATH || '') + '/api';
                fetch(apiPath + '/save-redirect-url.php', {
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
            
            // 포인트 설정 확인
            const planId = <?php echo $plan_id; ?>;
            checkAndOpenPointModal('mvno', planId, openApplyModal);
            return false;
        });
    }
    
    // 포인트 설정 확인 및 모달 열기 함수
    function checkAndOpenPointModal(type, itemId, callback) {
        // 포인트 설정 조회
        const apiPath = window.API_PATH || (window.BASE_PATH || '') + '/api';
        fetch(`${apiPath}/get-product-point-setting.php?type=${type}&id=${itemId}`)
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
            
            // 필수 동의 항목 확인 (동적으로 계산)
            const requiredItems = [];
            const agreementMap = {
                'purpose': 'mvnoAgreementPurpose',
                'items': 'mvnoAgreementItems',
                'period': 'mvnoAgreementPeriod',
                'thirdParty': 'mvnoAgreementThirdParty',
                'serviceNotice': 'mvnoAgreementServiceNotice',
                'marketing': 'mvnoAgreementMarketing'
            };
            
            if (typeof mvnoPrivacyContents !== 'undefined') {
                for (const [key, id] of Object.entries(agreementMap)) {
                    const setting = mvnoPrivacyContents[key];
                    if (!setting) continue;
                    
                    const isVisible = setting.isVisible !== false;
                    if (setting.isRequired === true && isVisible) {
                        requiredItems.push(id);
                    }
                }
            } else {
                // 기본값: marketing 제외 모두 필수
                requiredItems.push('mvnoAgreementPurpose', 'mvnoAgreementItems', 'mvnoAgreementPeriod', 'mvnoAgreementThirdParty', 'mvnoAgreementServiceNotice');
            }
            
            // 필수 항목 모두 체크되었는지 확인
            let allRequiredChecked = true;
            for (const itemId of requiredItems) {
                const checkbox = document.getElementById(itemId);
                if (checkbox && !checkbox.checked) {
                    allRequiredChecked = false;
                    break;
                }
            }
            
            if (!allRequiredChecked) {
                alert('모든 필수 개인정보 동의 항목에 동의해주세요.');
                return;
            }
            
            // 중복 제출 방지
            if (isSubmitting) {
                console.log('이미 제출 중입니다. 중복 제출을 방지합니다.');
                return;
            }
            
            // 제출 버튼 비활성화
            const submitBtn = document.getElementById('mvnoApplicationSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '처리 중...';
            }
            
            // 제출 플래그 설정
            isSubmitting = true;
            
            // 폼 데이터 준비
            const formData = new FormData(this);
            
            // 포인트 사용 정보 추가 (포인트 모달에서 확인한 포인트)
            if (window.pointUsageData && window.pointUsageData.usedPoint > 0) {
                formData.append('used_point', window.pointUsageData.usedPoint);
            }
            
            // 서버로 데이터 전송
            const apiPath = window.API_PATH || (window.BASE_PATH || '') + '/api';
            fetch(apiPath + '/submit-mvno-application.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 신청정보가 DB에 저장됨
                    
                    // 무조건 마이페이지 알뜰폰 주문내역으로 이동
                    const mypageUrl = (window.BASE_PATH || '') + '/mypage/mvno-order.php';
                    
                    // redirect_url이 있으면 새 창으로 열기
                    if (data.redirect_url && data.redirect_url.trim() !== '') {
                        let redirectUrl = data.redirect_url.replace(/\s+/g, '').trim();
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
                    // 실패 시 플래그 리셋
                    isSubmitting = false;
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = '신청하기';
                    }
                    
                    // 실패 시 모달로 표시
                    let errorMessage = data.message || '신청정보 저장에 실패했습니다.';
                    
                    // 디버그 정보가 있으면 콘솔에 출력
                    if (data.debug) {
                        console.error('신청 실패 - 디버그 정보:', data.debug);
                        console.error('신청 실패 - 디버그 정보 (JSON):', JSON.stringify(data.debug, null, 2));
                        
                        // 로컬 환경에서는 에러 메시지에 추가 정보 포함
                        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                            if (data.debug.last_db_error) {
                                errorMessage += '\n\n데이터베이스 오류: ' + data.debug.last_db_error;
                            }
                            if (data.debug.last_db_connection_error) {
                                errorMessage += '\n\n연결 오류: ' + data.debug.last_db_connection_error;
                            }
                            if (data.debug.error_message && data.debug.error_message !== errorMessage) {
                                errorMessage += '\n\n상세: ' + data.debug.error_message;
                            }
                            if (data.debug.product_id) {
                                console.error('상품 ID:', data.debug.product_id);
                            }
                            if (data.debug.user_id) {
                                console.error('사용자 ID:', data.debug.user_id);
                            }
                            if (data.debug.seller_id) {
                                console.error('판매자 ID:', data.debug.seller_id);
                            }
                        }
                    }
                    
                    // 전체 응답 데이터도 로깅
                    console.error('신청 실패 - 전체 응답:', data);
                    console.error('신청 실패 - 전체 응답 (JSON):', JSON.stringify(data, null, 2));
                    
                    // missing_fields가 있으면 표시
                    if (data.missing_fields && data.missing_fields.length > 0) {
                        errorMessage += '\n누락된 필드: ' + data.missing_fields.join(', ');
                    }
                    
                    if (typeof showAlert === 'function') {
                        showAlert(errorMessage, '신청 실패');
                    } else {
                        alert(errorMessage);
                    }
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = '신청하기';
                    }
                }
            })
            .catch(error => {
                // 에러 발생 시 플래그 리셋
                isSubmitting = false;
                
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
            // 마케팅 체크박스인 경우 채널 토글
            if (this.id === 'mvnoAgreementMarketing') {
                toggleMvnoMarketingChannels();
            }
        });
    });
    
    // 전체 동의 토글 함수
    function toggleAllMvnoAgreements(checked) {
        const mvnoAgreementPurpose = document.getElementById('mvnoAgreementPurpose');
        const mvnoAgreementItems = document.getElementById('mvnoAgreementItems');
        const mvnoAgreementPeriod = document.getElementById('mvnoAgreementPeriod');
        const mvnoAgreementThirdParty = document.getElementById('mvnoAgreementThirdParty');
        const mvnoAgreementServiceNotice = document.getElementById('mvnoAgreementServiceNotice');
        const mvnoAgreementMarketing = document.getElementById('mvnoAgreementMarketing');

        if (mvnoAgreementPurpose && mvnoAgreementItems && mvnoAgreementPeriod && mvnoAgreementThirdParty && mvnoAgreementServiceNotice) {
            mvnoAgreementPurpose.checked = checked;
            mvnoAgreementItems.checked = checked;
            mvnoAgreementPeriod.checked = checked;
            mvnoAgreementThirdParty.checked = checked;
            mvnoAgreementServiceNotice.checked = checked;
            if (mvnoAgreementMarketing) {
                mvnoAgreementMarketing.checked = checked;
                if (checked) {
                    toggleMvnoMarketingChannels();
                }
            }
            checkAllMvnoAgreements();
        }
    }
    
    // 마케팅 채널 활성화/비활성화 토글 함수
    function toggleMvnoMarketingChannels() {
        const mvnoAgreementMarketing = document.getElementById('mvnoAgreementMarketing');
        const marketingChannels = document.querySelectorAll('.mvno-marketing-channel');
        
        if (mvnoAgreementMarketing && marketingChannels.length > 0) {
            const isEnabled = mvnoAgreementMarketing.checked;
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
        const marketingChannels = document.querySelectorAll('.mvno-marketing-channel');
        const mvnoAgreementMarketing = document.getElementById('mvnoAgreementMarketing');
        
        marketingChannels.forEach(channel => {
            channel.addEventListener('change', function() {
                if (mvnoAgreementMarketing) {
                    const anyChecked = Array.from(marketingChannels).some(ch => ch.checked);
                    if (anyChecked && !mvnoAgreementMarketing.checked) {
                        // 상위 토글 체크
                        mvnoAgreementMarketing.checked = true;
                        // 모든 하위 체크박스 자동 체크
                        toggleMvnoMarketingChannels();
                    } else if (!anyChecked && mvnoAgreementMarketing.checked) {
                        // 모든 하위 체크박스가 해제되면 상위 토글도 해제
                        mvnoAgreementMarketing.checked = false;
                        toggleMvnoMarketingChannels();
                    }
                }
            });
        });
        
        // 초기 상태 설정
        toggleMvnoMarketingChannels();
    });
    
    // 아코디언 토글 함수
    function toggleMvnoAccordion(accordionId, arrowLink) {
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

    // checkAllMvnoAgreements 함수는 전역 스코프에 정의되어 있음 (인라인 핸들러에서 호출됨)
    
    
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
        
        // 모달 내 배지 제거 (필수/선택 표시 제거)
        if (type === 'serviceNotice' || type === 'marketing') {
            // serviceNotice의 경우
            if (type === 'serviceNotice') {
                const header = mvnoPrivacyModalBody.querySelector('.privacy-service-notice-header');
                if (header) {
                    const badge = header.querySelector('.required-badge, .optional-badge');
                    if (badge) {
                        badge.remove();
                    }
                }
            }
            
            // marketing의 경우
            if (type === 'marketing') {
                const header = mvnoPrivacyModalBody.querySelector('.privacy-marketing-header');
                if (header) {
                    const badge = header.querySelector('.required-badge, .optional-badge');
                    if (badge) {
                        badge.remove();
                    }
                }
            }
        }
        
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

    // 메인 페이지 리뷰는 이미 PHP에서 5개만 표시됨

    // 리뷰 모달 기능
    const reviewModal = document.getElementById('reviewModal');
    const reviewModalOverlay = document.getElementById('reviewModalOverlay');
    const reviewModalClose = document.getElementById('reviewModalClose');
    const reviewModalMoreBtn = document.querySelector('.review-modal-more-btn');
    const reviewModalList = document.querySelector('.review-modal-list');
    const planReviewMoreBtn = document.getElementById('planReviewMoreBtn');
    const planReviewList = document.getElementById('planReviewList');
    
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
        
        // 모달이 열릴 때마다 초기화 (MutationObserver 또는 이벤트 리스너 사용)
        if (reviewModal) {
            // 모달이 열릴 때를 감지하기 위해 클래스 변경 감지
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

<style>
/* MVNO 리뷰 섹션 스타일 (인터넷과 동일) */
.plan-review-section .content-layout {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 1rem;
}

.plan-review-header {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    width: 100%;
}

.plan-review-header .section-title {
    margin: 0;
    display: flex;
    align-items: center;
}

.plan-review-logo-text {
    font-size: 16px;
    font-weight: 700;
    color: #374151;
    line-height: 1;
    display: flex;
    align-items: center;
}

@media (min-width: 992px) {
    .plan-review-logo-text {
        font-size: 20px;
    }
}

.plan-review-summary {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    gap: 48px;
    padding: 16px 0;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

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

.plan-review-count-section {
    width: 100%;
    margin-top: 16px;
    margin-bottom: 16px;
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.plan-review-count-sort-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.plan-review-count {
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
}

.plan-review-sort-select-wrapper {
    position: relative;
    box-shadow: rgba(36, 41, 46, 0.04) 0px 2px 8px 0px;
}

.plan-review-sort-select {
    padding: 6.5px 10px 6.5px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background-color: #ffffff;
    font-size: 14px;
    color: #374151;
    cursor: pointer;
    transition: border-color 0.2s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L6 6L11 1' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 32px;
}

.plan-review-sort-select:hover {
    border-color: #9ca3af;
}

.plan-review-sort-select:focus {
    outline: none;
    border-color: #667eea;
}

.plan-review-list {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 0;
}

.plan-review-item {
    width: 100%;
    max-width: 100%;
    padding: 16px;
    background-color: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

@media (min-width: 992px) {
    .plan-review-item {
        padding: 24px;
    }
}

.plan-review-item-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.plan-review-author {
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
}

.plan-review-provider-badge {
    display: inline-block;
    padding: 4px 10px;
    background-color: #f3f4f6;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    color: #374151;
    line-height: 1.4;
}

.plan-review-content {
    font-size: 14px;
    line-height: 1.6;
    color: #374151;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
    background-color: #ffffff;
    padding: 12px;
    border-radius: 8px;
}

.review-modal-item-content {
    font-size: 14px;
    line-height: 1.6;
    color: #374151;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.plan-review-more-btn {
    width: 100%;
    padding: 14px;
    margin-top: 16px;
    background-color: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
}

.plan-review-more-btn:hover {
    background-color: #f9fafb;
    border-color: #9ca3af;
}

@media (max-width: 767px) {
    .plan-review-section .content-layout {
        padding: 0 0.75rem;
    }
    
    .plan-review-summary {
        flex-direction: column;
        align-items: center;
        gap: 24px;
        padding: 16px 1rem;
    }
    
    .plan-review-right {
        flex-direction: column;
        align-items: center;
        gap: 16px;
        width: 100%;
    }
    
    .plan-review-total-rating {
        align-items: center;
    }
}

@media (min-width: 768px) and (max-width: 991px) {
    .plan-review-summary {
        gap: 32px;
        padding: 16px 2rem;
    }
    
    .plan-review-right {
        gap: 16px;
    }
}

@media (min-width: 992px) {
    .plan-review-summary {
        padding: 16px 4rem;
    }
}
</style>

<script>
</script>

<script>
    // BASE_PATH와 API_PATH를 JavaScript에서 사용할 수 있도록 설정
    window.BASE_PATH = window.BASE_PATH || '<?php echo getBasePath(); ?>';
    window.API_PATH = window.API_PATH || (window.BASE_PATH + '/api');
</script>
<script src="<?php echo getAssetPath('/assets/js/favorite-heart.js'); ?>" defer></script>
<script src="<?php echo getAssetPath('/assets/js/point-usage-integration.js'); ?>" defer></script>

<?php 
// 포인트 사용 모달 포함
$type = 'mvno';
$item_id = $plan_id;
$item_name = $plan['plan_name'] ?? '';
include '../includes/components/point-usage-modal.php';
?>

<?php include '../includes/footer.php'; ?>