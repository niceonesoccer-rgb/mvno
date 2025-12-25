<?php
// 현재 페이지 설정
$current_page = 'mno-sim';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true; // 상세 페이지에서도 하단 메뉴바 표시

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';
require_once '../includes/data/privacy-functions.php';

// 개인정보 설정 로드
$privacySettings = getPrivacySettings();

// 요금제 ID 가져오기
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    http_response_code(404);
    die('상품을 찾을 수 없습니다.');
}

// 헤더 포함
include '../includes/header.php';

// 조회수 업데이트
require_once '../includes/data/product-functions.php';
incrementProductView($product_id);

// 요금제 데이터 가져오기
require_once '../includes/data/plan-data.php';
$plan = getMnoSimDetailData($product_id);
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

// 리뷰 목록 가져오기
$reviews = [];
$averageRating = 0;
$reviewCount = 0;
if (function_exists('getProductReviews')) {
    $reviews = getProductReviews($product_id, 'mno-sim', 20);
    $averageRating = getProductAverageRating($product_id, 'mno-sim');
    $reviewCount = getProductReviewCount($product_id, 'mno-sim');
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
                                <?php echo htmlspecialchars($plan['plan_name'] ?? ($rawData['plan_name'] ?? '요금제명 없음')); ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통신사 약정</div>
                            <div class="plan-detail-value">
                                <?php 
                                $contractPeriod = $rawData['contract_period'] ?? '';
                                $contractPeriodValue = $rawData['contract_period_discount_value'] ?? null;
                                $contractPeriodUnit = $rawData['contract_period_discount_unit'] ?? '';
                                
                                if (!empty($contractPeriod)) {
                                    echo htmlspecialchars($contractPeriod);
                                    if (!empty($contractPeriodValue) && !empty($contractPeriodUnit)) {
                                        echo ' ' . $contractPeriodValue . $contractPeriodUnit;
                                    }
                                } else {
                                    echo '없음';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통신망</div>
                            <div class="plan-detail-value">
                                <?php echo htmlspecialchars($rawData['provider'] ?? '통신망 정보 없음'); ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통신 기술</div>
                            <div class="plan-detail-value">
                                <?php echo htmlspecialchars($rawData['service_type'] ?? '정보 없음'); ?>
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
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">요금제 유지기간</div>
                            <div class="plan-detail-value">
                                <?php
                                $planMaintenanceType = $rawData['plan_maintenance_period_type'] ?? '';
                                if ($planMaintenanceType === '무약정') {
                                    echo '무약정';
                                } elseif ($planMaintenanceType === '직접입력') {
                                    $prefix = $rawData['plan_maintenance_period_prefix'] ?? '';
                                    $value = $rawData['plan_maintenance_period_value'] ?? null;
                                    $unit = $rawData['plan_maintenance_period_unit'] ?? '';
                                    if (!empty($value) && !empty($unit)) {
                                        $displayValue = $prefix . '+' . $value . $unit;
                                        echo htmlspecialchars($displayValue);
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
                            <div class="plan-detail-label">유심기변 불가기간</div>
                            <div class="plan-detail-value">
                                <?php
                                $simChangeRestrictionType = $rawData['sim_change_restriction_period_type'] ?? '';
                                if ($simChangeRestrictionType === '무약정') {
                                    echo '무약정';
                                } elseif ($simChangeRestrictionType === '직접입력') {
                                    $prefix = $rawData['sim_change_restriction_period_prefix'] ?? '';
                                    $value = $rawData['sim_change_restriction_period_value'] ?? null;
                                    $unit = $rawData['sim_change_restriction_period_unit'] ?? '';
                                    if (!empty($value) && !empty($unit)) {
                                        $displayValue = $prefix . '+' . $value . $unit;
                                        echo htmlspecialchars($displayValue);
                                    } else {
                                        echo '정보 없음';
                                    }
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
                                if ($callType === '무제한') {
                                    echo '무제한';
                                } elseif ($callType === '기본제공') {
                                    echo '기본제공';
                                } elseif (!empty($rawData['call_amount']) && !empty($rawData['call_amount_unit'])) {
                                    $callAmount = floatval($rawData['call_amount']);
                                    // 소수점이 있으면 소수점 표시, 없으면 정수로 표시
                                    if (floor($callAmount) == $callAmount) {
                                        echo number_format($callAmount, 0) . $rawData['call_amount_unit'];
                                    } else {
                                        $formatted = number_format($callAmount, 2, '.', '');
                                        echo rtrim(rtrim($formatted, '0'), '.') . $rawData['call_amount_unit'];
                                    }
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
                                if ($smsType === '무제한') {
                                    echo '무제한';
                                } elseif ($smsType === '기본제공') {
                                    echo '기본제공';
                                } elseif (!empty($rawData['sms_amount']) && !empty($rawData['sms_amount_unit'])) {
                                    echo number_format($rawData['sms_amount']) . $rawData['sms_amount_unit'];
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
                                if ($dataAmount === '무제한') {
                                    echo '무제한';
                                } elseif (!empty($rawData['data_amount_value']) && !empty($rawData['data_unit'])) {
                                    $dataAmountValue = floatval($rawData['data_amount_value']);
                                    // 소수점이 있으면 소수점 표시, 없으면 정수로 표시
                                    if (floor($dataAmountValue) == $dataAmountValue) {
                                        echo number_format($dataAmountValue, 0) . $rawData['data_unit'];
                                    } else {
                                        $formatted = number_format($dataAmountValue, 2, '.', '');
                                        echo rtrim(rtrim($formatted, '0'), '.') . $rawData['data_unit'];
                                    }
                                } elseif (!empty($dataAmount)) {
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
                                if (!empty($dataAdditionalValue)) {
                                    echo htmlspecialchars($dataAdditionalValue);
                                } elseif (!empty($dataAdditional) && $dataAdditional !== '없음') {
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
                                if (!empty($dataExhaustedValue)) {
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
                                if (!empty($rawData['additional_call']) && !empty($rawData['additional_call_unit'])) {
                                    $additionalCall = floatval($rawData['additional_call']);
                                    // 소수점이 있으면 소수점 표시, 없으면 정수로 표시
                                    if (floor($additionalCall) == $additionalCall) {
                                        echo number_format($additionalCall, 0) . $rawData['additional_call_unit'];
                                    } else {
                                        $formatted = number_format($additionalCall, 2, '.', '');
                                        echo rtrim(rtrim($formatted, '0'), '.') . $rawData['additional_call_unit'];
                                    }
                                } elseif (!empty($additionalCallType)) {
                                    echo htmlspecialchars($additionalCallType);
                                } else {
                                    echo '정보 없음';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">테더링(핫스팟)</div>
                            <div class="plan-detail-value">
                                <?php
                                $mobileHotspot = $rawData['mobile_hotspot'] ?? '';
                                if ($mobileHotspot === '기본 제공량 내에서 사용') {
                                    echo '기본 제공량 내에서 사용';
                                } elseif (!empty($rawData['mobile_hotspot_value']) && !empty($rawData['mobile_hotspot_unit'])) {
                                    $mobileHotspotValue = floatval($rawData['mobile_hotspot_value']);
                                    // 소수점이 있으면 소수점 표시, 없으면 정수로 표시
                                    if (floor($mobileHotspotValue) == $mobileHotspotValue) {
                                        echo number_format($mobileHotspotValue, 0) . $rawData['mobile_hotspot_unit'];
                                    } else {
                                        $formatted = number_format($mobileHotspotValue, 2, '.', '');
                                        echo rtrim(rtrim($formatted, '0'), '.') . $rawData['mobile_hotspot_unit'];
                                    }
                                } elseif (!empty($mobileHotspot)) {
                                    echo htmlspecialchars($mobileHotspot);
                                } else {
                                    echo '정보 없음';
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
                                $regularSim = $rawData['regular_sim_available'] ?? '';
                                if ($regularSim === '배송불가') {
                                    echo '배송불가';
                                } elseif ($regularSim === '유심비 유료' || $regularSim === '배송가능') {
                                    $price = $rawData['regular_sim_price'] ?? 0;
                                    $unit = $rawData['regular_sim_price_unit'] ?? '원';
                                    if ($price > 0) {
                                        echo '유심비 유료 (' . number_format((int)$price) . $unit . ')';
                                    } else {
                                        echo '유심비 유료';
                                    }
                                } elseif ($regularSim === '유심비 무료' || $regularSim === '유심 무료' || $regularSim === '무료제공') {
                                    echo '유심비 무료';
                                } elseif ($regularSim === '유심·배송비 무료' || $regularSim === '무료제공(배송비무료)') {
                                    echo '유심·배송비 무료';
                                } elseif (!empty($regularSim)) {
                                    echo htmlspecialchars($regularSim);
                                } else {
                                    echo '정보 없음';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">NFC 유심</div>
                            <div class="plan-detail-value">
                                <?php
                                $nfcSim = $rawData['nfc_sim_available'] ?? '';
                                if ($nfcSim === '배송불가') {
                                    echo '배송불가';
                                } elseif ($nfcSim === '유심비 유료' || $nfcSim === '배송가능') {
                                    $price = $rawData['nfc_sim_price'] ?? 0;
                                    $unit = $rawData['nfc_sim_price_unit'] ?? '원';
                                    if ($price > 0) {
                                        echo '유심비 유료 (' . number_format((int)$price) . $unit . ')';
                                    } else {
                                        echo '유심비 유료';
                                    }
                                } elseif ($nfcSim === '유심비 무료' || $nfcSim === '유심 무료' || $nfcSim === '무료제공') {
                                    echo '유심비 무료';
                                } elseif ($nfcSim === '유심·배송비 무료' || $nfcSim === '무료제공(배송비무료)') {
                                    echo '유심·배송비 무료';
                                } elseif (!empty($nfcSim)) {
                                    echo htmlspecialchars($nfcSim);
                                } else {
                                    echo '정보 없음';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">eSIM</div>
                            <div class="plan-detail-value">
                                <?php
                                $esim = $rawData['esim_available'] ?? '';
                                if ($esim === '개통불가') {
                                    echo '개통불가';
                                } elseif ($esim === 'eSIM 유료' || $esim === '유심비 유료' || $esim === '개통가능') {
                                    $price = $rawData['esim_price'] ?? 0;
                                    $unit = $rawData['esim_price_unit'] ?? '원';
                                    if ($price > 0) {
                                        echo '개통가능 (' . number_format((int)$price) . $unit . ')';
                                    } else {
                                        echo '개통가능';
                                    }
                                } elseif ($esim === 'eSIM 무료' || $esim === '유심비 무료' || $esim === '유심 무료' || $esim === '무료제공') {
                                    echo 'eSIM 무료';
                                } elseif (!empty($esim)) {
                                    echo htmlspecialchars($esim);
                                } else {
                                    echo '정보 없음';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 기본 제공 초과 시 카드 -->
            <div class="plan-info-card">
                <h3 class="plan-info-card-title">기본 제공 초과 시</h3>
                <div class="plan-info-card-content">
                    <div class="plan-detail-grid">
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">데이터</div>
                            <div class="plan-detail-value">
                                <?php
                                $overDataPrice = $rawData['over_data_price'] ?? null;
                                $overDataPriceUnit = $rawData['over_data_price_unit'] ?? '원/MB';
                                if ($overDataPrice !== null) {
                                    $overDataPriceFloat = floatval($overDataPrice);
                                    // 소수점이 있으면 소수점 표시, 없으면 정수로 표시
                                    if (floor($overDataPriceFloat) == $overDataPriceFloat) {
                                        echo number_format($overDataPriceFloat, 0) . $overDataPriceUnit;
                                    } else {
                                        $formatted = number_format($overDataPriceFloat, 2, '.', '');
                                        echo rtrim(rtrim($formatted, '0'), '.') . $overDataPriceUnit;
                                    }
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
                                $overVoicePrice = $rawData['over_voice_price'] ?? null;
                                $overVoicePriceUnit = $rawData['over_voice_price_unit'] ?? '원/초';
                                if ($overVoicePrice !== null) {
                                    $overVoicePriceFloat = floatval($overVoicePrice);
                                    // 소수점이 있으면 소수점 표시, 없으면 정수로 표시
                                    if (floor($overVoicePriceFloat) == $overVoicePriceFloat) {
                                        echo number_format($overVoicePriceFloat, 0) . $overVoicePriceUnit;
                                    } else {
                                        $formatted = number_format($overVoicePriceFloat, 2, '.', '');
                                        echo rtrim(rtrim($formatted, '0'), '.') . $overVoicePriceUnit;
                                    }
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
                                $overVideoPrice = $rawData['over_video_price'] ?? null;
                                $overVideoPriceUnit = $rawData['over_video_price_unit'] ?? '원/초';
                                if ($overVideoPrice !== null) {
                                    $overVideoPriceFloat = floatval($overVideoPrice);
                                    // 소수점이 있으면 소수점 표시, 없으면 정수로 표시
                                    if (floor($overVideoPriceFloat) == $overVideoPriceFloat) {
                                        echo number_format($overVideoPriceFloat, 0) . $overVideoPriceUnit;
                                    } else {
                                        $formatted = number_format($overVideoPriceFloat, 2, '.', '');
                                        echo rtrim(rtrim($formatted, '0'), '.') . $overVideoPriceUnit;
                                    }
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
                                $overSmsPrice = $rawData['over_sms_price'] ?? null;
                                $overSmsPriceUnit = $rawData['over_sms_price_unit'] ?? '원/건';
                                if ($overSmsPrice !== null) {
                                    echo number_format((int)$overSmsPrice) . $overSmsPriceUnit;
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
                                $overLmsPrice = $rawData['over_lms_price'] ?? null;
                                $overLmsPriceUnit = $rawData['over_lms_price_unit'] ?? '원/건';
                                if ($overLmsPrice !== null) {
                                    echo number_format((int)$overLmsPrice) . $overLmsPriceUnit;
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
                                $overMmsPrice = $rawData['over_mms_price'] ?? null;
                                $overMmsPriceUnit = $rawData['over_mms_price_unit'] ?? '원/건';
                                if ($overMmsPrice !== null) {
                                    echo number_format((int)$overMmsPrice) . $overMmsPriceUnit;
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
    <?php
    // 정렬 방식 가져오기 (기본값: 최신순)
    $sort = $_GET['review_sort'] ?? 'created_desc';
    if (!in_array($sort, ['rating_desc', 'rating_asc', 'created_desc'])) {
        $sort = 'created_desc';
    }
    
    // 리뷰 목록 가져오기
    $allReviews = getProductReviews($product_id, 'mno-sim', 1000, $sort);
    $reviews = array_slice($allReviews, 0, 5); // 페이지에는 처음 5개만 표시
    $averageRating = getProductAverageRating($product_id, 'mno-sim');
    $reviewCount = count($allReviews);
    $hasReviews = !empty($allReviews) && $reviewCount > 0;
    $remainingCount = max(0, $reviewCount - 5);
    
    // 카테고리별 평균 별점 가져오기
    $categoryAverages = getInternetReviewCategoryAverages($product_id, 'mno-sim');
    
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
        error_log("MNO-SIM Plan Detail - Error getting seller name: " . $e->getMessage());
    }
    ?>
    
    <?php if ($hasReviews): ?>
    <section class="plan-review-section" id="planReviewSection" style="margin-top: 2rem; padding: 2rem 0; background: #f9fafb;">
        <div class="content-layout">
            <div class="plan-review-header">
                <span class="plan-review-logo-text"><?php echo htmlspecialchars($sellerName ?: ($plan['provider'] ?? '통신사유심')); ?></span>
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
                        <select class="plan-review-sort-select" id="planReviewSortSelect" aria-label="리뷰 정렬 방식 선택" onchange="window.location.href='?id=<?php echo $product_id; ?>&review_sort=' + this.value">
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
                                    $providerText = $provider ? ' | ' . $provider : '';
                                    ?>
                                    <span class="plan-review-author"><?php echo $authorName . $providerText; ?></span>
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
    <?php endif; ?>
</main>

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
                <form id="mnoSimApplicationForm" class="consultation-form">
                    <input type="hidden" name="product_id" id="mnoSimProductId" value="<?php echo $product_id; ?>">
                    <input type="hidden" name="subscription_type" id="mnoSimSubscriptionType" value="">
                    
                    <div class="consultation-form-group">
                        <label for="mnoSimApplicationName" class="consultation-form-label">이름</label>
                        <input type="text" id="mnoSimApplicationName" name="name" class="consultation-form-input" required>
                    </div>
                    
                    <div class="consultation-form-group">
                        <label for="mnoSimApplicationPhone" class="consultation-form-label">휴대폰번호</label>
                        <input type="tel" id="mnoSimApplicationPhone" name="phone" class="consultation-form-input" placeholder="010-1234-5678" required>
                        <span id="mnoSimApplicationPhoneError" class="form-error-message" style="display: none; color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem;"></span>
                    </div>
                    
                    <div class="consultation-form-group">
                        <label for="mnoSimApplicationEmail" class="consultation-form-label">이메일</label>
                        <input type="email" id="mnoSimApplicationEmail" name="email" class="consultation-form-input" placeholder="example@email.com" required>
                        <span id="mnoSimApplicationEmailError" class="form-error-message" style="display: none; color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem;"></span>
                    </div>
                    
                    <!-- 체크박스 -->
                    <div class="internet-checkbox-group">
                        <?php
                        // 동의 항목 정의 (순서대로)
                        $agreementItems = [
                            'purpose' => ['id' => 'mnoSimAgreementPurpose', 'name' => 'agreementPurpose', 'modal' => 'openMnoSimPrivacyModal'],
                            'items' => ['id' => 'mnoSimAgreementItems', 'name' => 'agreementItems', 'modal' => 'openMnoSimPrivacyModal'],
                            'period' => ['id' => 'mnoSimAgreementPeriod', 'name' => 'agreementPeriod', 'modal' => 'openMnoSimPrivacyModal'],
                            'thirdParty' => ['id' => 'mnoSimAgreementThirdParty', 'name' => 'agreementThirdParty', 'modal' => 'openMnoSimPrivacyModal'],
                            'serviceNotice' => ['id' => 'mnoSimAgreementServiceNotice', 'name' => 'service_notice_opt_in', 'accordion' => 'mnoSimServiceNoticeContent', 'accordionFunc' => 'toggleMnoSimAccordion'],
                            'marketing' => ['id' => 'mnoSimAgreementMarketing', 'name' => 'marketing_opt_in', 'accordion' => 'mnoSimMarketingContent', 'accordionFunc' => 'toggleMnoSimAccordion']
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
                            <input type="checkbox" id="mnoSimAgreementAll" class="internet-checkbox-input">
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
                                    <a href="#" class="internet-checkbox-link" id="mnoSim<?php echo ucfirst($key); ?>ArrowLink" onclick="event.preventDefault(); <?php echo $item['modal']; ?>('<?php echo $key; ?>'); return false;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                            <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                        </svg>
                                    </a>
                                    <?php elseif (isset($item['accordion'])): ?>
                                    <a href="#" class="internet-checkbox-link" onclick="event.preventDefault(); openMnoSimPrivacyModal('<?php echo $key; ?>'); return false;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="arrow-down">
                                            <path d="M3.646 4.646a.5.5 0 0 1 .708 0L8 8.293l3.646-3.647a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 0-.708z"></path>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php if ($key === 'serviceNotice'): ?>
                                <div class="internet-accordion-content" id="mnoSimServiceNoticeContent">
                                    <div class="internet-accordion-inner">
                                        <div class="internet-accordion-section">
                                            <div style="font-size: 0.875rem; color: #6b7280; line-height: 1.65;">
                                                <?php echo $setting['content'] ?? ''; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php elseif ($key === 'marketing'): ?>
                                <div class="internet-accordion-content" id="mnoSimMarketingContent">
                                    <div class="internet-accordion-inner">
                                        <div class="internet-accordion-section">
                                            <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 0.75rem 0;">광고성 정보를 받으시려면 아래 항목을 선택해주세요</p>
                                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                    <input type="checkbox" id="mnoSimMarketingEmail" name="marketing_email_opt_in" class="mno-sim-marketing-channel" style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;">
                                                    <span style="font-size: 0.875rem; color: #374151;">이메일 수신동의</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                    <input type="checkbox" id="mnoSimMarketingSmsSns" name="marketing_sms_sns_opt_in" class="mno-sim-marketing-channel" style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;">
                                                    <span style="font-size: 0.875rem; color: #374151;">SMS, SNS 수신동의</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                    <input type="checkbox" id="mnoSimMarketingPush" name="marketing_push_opt_in" class="mno-sim-marketing-channel" style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;">
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
                    
                    <button type="submit" class="consultation-submit-btn" id="mnoSimApplicationSubmitBtn">신청하기</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 개인정보 내용보기 모달 (통신사유심용) -->
<div class="privacy-content-modal" id="mnoSimPrivacyContentModal">
    <div class="privacy-content-modal-overlay" id="mnoSimPrivacyContentModalOverlay"></div>
    <div class="privacy-content-modal-content">
        <div class="privacy-content-modal-header">
            <h3 class="privacy-content-modal-title" id="mnoSimPrivacyContentModalTitle">개인정보 수집 및 이용목적</h3>
            <button class="privacy-content-modal-close" aria-label="닫기" id="mnoSimPrivacyContentModalClose">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="privacy-content-modal-body" id="mnoSimPrivacyContentModalBody">
            <!-- 내용이 JavaScript로 동적으로 채워짐 -->
        </div>
    </div>
</div>

<style>
/* 체크박스 스타일 (알뜰폰과 동일) */
.internet-checkbox-group .internet-checkbox-text,
.internet-checkbox-label-item .internet-checkbox-text,
span.internet-checkbox-text {
    font-size: 1.0625rem !important;
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

.arrow-up {
    transform: rotate(180deg);
    transition: transform 0.3s ease;
}
</style>

<script>
// 관리자 페이지 설정 로드 (DB의 app_settings 테이블)
<?php
echo "const mnoSimPrivacyContents = " . json_encode($privacySettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
?>

// 아코디언 기능
// 아코디언 토글 함수 (전역으로 노출)
function toggleMnoSimAccordionByArrow(accordionId, arrowLinkId) {
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
    const nameInput = document.getElementById('mnoSimApplicationName');
    const phoneInput = document.getElementById('mnoSimApplicationPhone');
    const emailInput = document.getElementById('mnoSimApplicationEmail');
    const subscriptionTypeInput = document.getElementById('mnoSimSubscriptionType');
    
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
                    // 값 설정 후 즉시 검증
                    setTimeout(() => {
                        validatePhoneOnModal();
                    }, 10);
                }
                
                // 전화번호 실시간 검증 (데이터 로드 여부와 관계없이 항상 설정)
                if (phoneInput) {
                    const phoneErrorElement = document.getElementById('mnoSimApplicationPhoneError');
                    
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
                        
                        checkAllMnoSimAgreements();
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
                        
                        checkAllMnoSimAgreements();
                    });
                }
                
                // 이름 입력 시 검증
                if (nameInput) {
                    nameInput.addEventListener('input', checkAllMnoSimAgreements);
                    nameInput.addEventListener('blur', checkAllMnoSimAgreements);
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
                    checkAllMnoSimAgreements();
                }, 50);
            }
        })
        .catch(error => {
            // 오류가 나도 계속 진행
            // 에러 발생 시에도 검증 수행
            setTimeout(function() {
                validatePhoneOnModal();
                validateEmailOnModal();
                checkAllMnoSimAgreements();
            }, 50);
        });
    
    // 모달이 열릴 때마다 즉시 검증 (사용자 정보 로드와 관계없이)
    setTimeout(function() {
        validatePhoneOnModal();
        validateEmailOnModal();
        checkAllMnoSimAgreements();
    }, 100);
    
    // 실시간 이메일 검증
    if (emailInput) {
        const emailErrorElement = document.getElementById('mnoSimApplicationEmailError');
        
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
            
            checkAllMnoSimAgreements();
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
            
            checkAllMnoSimAgreements();
        });
    }
}

// 단계 표시 함수
const applyModalBack = document.getElementById('applyModalBack');

// 전화번호 검증 함수
function validatePhoneOnModal() {
    const phoneInput = document.getElementById('mnoSimApplicationPhone');
    const phoneErrorElement = document.getElementById('mnoSimApplicationPhoneError');
    
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
    const emailInput = document.getElementById('mnoSimApplicationEmail');
    const emailErrorElement = document.getElementById('mnoSimApplicationEmailError');
    
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
function toggleMnoSimAccordion(accordionId, arrowLink) {
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
function openMnoSimPrivacyModal(type) {
    const modal = document.getElementById('mnoSimPrivacyContentModal');
    const modalTitle = document.getElementById('mnoSimPrivacyContentModalTitle');
    const modalBody = document.getElementById('mnoSimPrivacyContentModalBody');
    
    if (!modal || !modalTitle || !modalBody) return;
    
    if (typeof mnoSimPrivacyContents !== 'undefined' && mnoSimPrivacyContents[type]) {
        modalTitle.textContent = mnoSimPrivacyContents[type].title || '';
        modalBody.innerHTML = mnoSimPrivacyContents[type].content || '';
    } else {
        return; // 데이터가 없으면 모달을 열지 않음
    }
    
    modal.style.display = 'flex';
    modal.classList.add('privacy-content-modal-active');
    document.body.style.overflow = 'hidden';
}

// 개인정보 내용보기 모달 닫기 함수
function closeMnoSimPrivacyModal() {
    const modal = document.getElementById('mnoSimPrivacyContentModal');
    if (!modal) return;
    
    modal.classList.remove('privacy-content-modal-active');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// 모달 닫기 이벤트 리스너
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('mnoSimPrivacyContentModal');
    const modalOverlay = document.getElementById('mnoSimPrivacyContentModalOverlay');
    const modalClose = document.getElementById('mnoSimPrivacyContentModalClose');
    
    if (modalOverlay) {
        modalOverlay.addEventListener('click', closeMnoSimPrivacyModal);
    }
    
    if (modalClose) {
        modalClose.addEventListener('click', closeMnoSimPrivacyModal);
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('privacy-content-modal-active')) {
            closeMnoSimPrivacyModal();
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
            checkAllMnoSimAgreements();
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
    const form = document.getElementById('mnoSimApplicationForm');
    if (form) {
        form.reset();
    }
}

// 통신사유심 신청 폼 제출 이벤트
const mnoSimApplicationForm = document.getElementById('mnoSimApplicationForm');
if (mnoSimApplicationForm) {
    mnoSimApplicationForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // 필수 필드 검증
        const nameInput = document.getElementById('mnoSimApplicationName');
        const phoneInput = document.getElementById('mnoSimApplicationPhone');
        const emailInput = document.getElementById('mnoSimApplicationEmail');
        
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
            'purpose': 'mnoSimAgreementPurpose',
            'items': 'mnoSimAgreementItems',
            'period': 'mnoSimAgreementPeriod',
            'thirdParty': 'mnoSimAgreementThirdParty',
            'serviceNotice': 'mnoSimAgreementServiceNotice',
            'marketing': 'mnoSimAgreementMarketing'
        };
        
        if (typeof mnoSimPrivacyContents !== 'undefined') {
            for (const [key, id] of Object.entries(agreementMap)) {
                const setting = mnoSimPrivacyContents[key];
                if (!setting) continue;
                
                const isVisible = setting.isVisible !== false;
                if (setting.isRequired === true && isVisible) {
                    requiredItems.push(id);
                }
            }
        } else {
            // 기본값: marketing 제외 모두 필수
            requiredItems.push('mnoSimAgreementPurpose', 'mnoSimAgreementItems', 'mnoSimAgreementPeriod', 'mnoSimAgreementThirdParty', 'mnoSimAgreementServiceNotice');
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
        
        // 제출 버튼 비활성화
        const submitBtn = document.getElementById('mnoSimApplicationSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = '처리 중...';
        }
        
        // 폼 데이터 준비
        const formData = new FormData(this);
        
        // 서버로 데이터 전송
        fetch('/MVNO/api/submit-mno-sim-application.php', {
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
                    // redirect_url이 없으면 마이페이지 통신사유심 주문내역으로 이동
                    window.location.href = '/MVNO/mypage/mno-sim-order.php';
                }
            } else {
                // 실패 시 모달로 표시
                let errorMessage = data.message || '신청정보 저장에 실패했습니다.';
                
                // 디버그 정보가 있으면 콘솔에 출력
                if (data.debug) {
                    console.error('신청 실패 - 디버그 정보:', data.debug);
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

// 통신사유심 개인정보 동의 체크박스 처리
const mnoSimAgreementAll = document.getElementById('mnoSimAgreementAll');
const mnoSimAgreementItemCheckboxes = document.querySelectorAll('#mnoSimApplicationForm .internet-checkbox-input-item');

// 전체 동의 체크박스 변경 이벤트
if (mnoSimAgreementAll) {
    mnoSimAgreementAll.addEventListener('change', function() {
        const isChecked = this.checked;
        mnoSimAgreementItemCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        checkAllMnoSimAgreements();
    });
}

// 개별 체크박스 변경 이벤트 (전체 동의 상태 업데이트)
mnoSimAgreementItemCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        checkAllMnoSimAgreements();
    });
});

// 전체 동의 토글 함수
function toggleAllMnoSimAgreements(checked) {
    const mnoSimAgreementPurpose = document.getElementById('mnoSimAgreementPurpose');
    const mnoSimAgreementItems = document.getElementById('mnoSimAgreementItems');
    const mnoSimAgreementPeriod = document.getElementById('mnoSimAgreementPeriod');
    const mnoSimAgreementThirdParty = document.getElementById('mnoSimAgreementThirdParty');
    const mnoSimAgreementServiceNotice = document.getElementById('mnoSimAgreementServiceNotice');
    const mnoSimAgreementMarketing = document.getElementById('mnoSimAgreementMarketing');

    if (mnoSimAgreementPurpose && mnoSimAgreementItems && mnoSimAgreementPeriod && mnoSimAgreementThirdParty && mnoSimAgreementServiceNotice) {
        mnoSimAgreementPurpose.checked = checked;
        mnoSimAgreementItems.checked = checked;
        mnoSimAgreementPeriod.checked = checked;
        mnoSimAgreementThirdParty.checked = checked;
        mnoSimAgreementServiceNotice.checked = checked;
        if (mnoSimAgreementMarketing) {
            mnoSimAgreementMarketing.checked = checked;
            if (checked) {
                toggleMnoSimMarketingChannels();
            }
        }
        checkAllMnoSimAgreements();
    }
}

// 마케팅 채널 활성화/비활성화 토글 함수
function toggleMnoSimMarketingChannels() {
    const mnoSimAgreementMarketing = document.getElementById('mnoSimAgreementMarketing');
    const marketingChannels = document.querySelectorAll('.mno-sim-marketing-channel');
    
    if (mnoSimAgreementMarketing && marketingChannels.length > 0) {
        const isEnabled = mnoSimAgreementMarketing.checked;
        marketingChannels.forEach(channel => {
            channel.disabled = !isEnabled;
            if (!isEnabled) {
                channel.checked = false;
            }
        });
    }
}

// 마케팅 채널 변경 시 상위 체크박스 업데이트
document.addEventListener('DOMContentLoaded', function() {
    const marketingChannels = document.querySelectorAll('.mno-sim-marketing-channel');
    const mnoSimAgreementMarketing = document.getElementById('mnoSimAgreementMarketing');
    
    marketingChannels.forEach(channel => {
        channel.addEventListener('change', function() {
            if (mnoSimAgreementMarketing) {
                const anyChecked = Array.from(marketingChannels).some(ch => ch.checked);
                if (anyChecked && !mnoSimAgreementMarketing.checked) {
                    mnoSimAgreementMarketing.checked = true;
                    toggleMnoSimMarketingChannels();
                }
            }
        });
    });
    
    // 초기 상태 설정
    toggleMnoSimMarketingChannels();
});

// 통신사유심 개인정보 내용보기 모달
const mnoSimPrivacyModal = document.getElementById('mnoSimPrivacyContentModal');
const mnoSimPrivacyModalOverlay = document.getElementById('mnoSimPrivacyContentModalOverlay');
const mnoSimPrivacyModalClose = document.getElementById('mnoSimPrivacyContentModalClose');
const mnoSimPrivacyModalTitle = document.getElementById('mnoSimPrivacyContentModalTitle');
const mnoSimPrivacyModalBody = document.getElementById('mnoSimPrivacyContentModalBody');

// 통신사유심 개인정보 내용보기 모달 열기 (전역으로 노출)
window.openMnoSimPrivacyModal = function(type) {
    if (!mnoSimPrivacyModal || !mnoSimPrivacyContents[type]) return;
    
    mnoSimPrivacyModalTitle.textContent = mnoSimPrivacyContents[type].title;
    mnoSimPrivacyModalBody.innerHTML = mnoSimPrivacyContents[type].content;
    
    mnoSimPrivacyModal.style.display = 'flex';
    mnoSimPrivacyModal.classList.add('privacy-content-modal-active');
    document.body.style.overflow = 'hidden';
};

// 통신사유심 개인정보 내용보기 모달 닫기 이벤트
if (mnoSimPrivacyModalOverlay) {
    mnoSimPrivacyModalOverlay.addEventListener('click', closeMnoSimPrivacyModal);
}

if (mnoSimPrivacyModalClose) {
    mnoSimPrivacyModalClose.addEventListener('click', closeMnoSimPrivacyModal);
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
        if (mnoSimPrivacyModal && mnoSimPrivacyModal.classList.contains('privacy-content-modal-active')) {
            closeMnoSimPrivacyModal();
        } else if (applyModal && applyModal.classList.contains('apply-modal-active')) {
            closeApplyModal();
        }
    }
});

// checkAllMnoSimAgreements 함수 - 신청 버튼 활성화/비활성화 제어
function checkAllMnoSimAgreements() {
    const nameInput = document.getElementById('mnoSimApplicationName');
    const phoneInput = document.getElementById('mnoSimApplicationPhone');
    const emailInput = document.getElementById('mnoSimApplicationEmail');
    const submitBtn = document.getElementById('mnoSimApplicationSubmitBtn');
    const agreeAll = document.getElementById('mnoSimAgreementAll');
    
    if (!submitBtn) return;
    
    // 필수 동의 항목 ID 목록 (동적으로 계산)
    const requiredItems = [];
    const agreementMap = {
        'purpose': 'mnoSimAgreementPurpose',
        'items': 'mnoSimAgreementItems',
        'period': 'mnoSimAgreementPeriod',
        'thirdParty': 'mnoSimAgreementThirdParty',
        'serviceNotice': 'mnoSimAgreementServiceNotice',
        'marketing': 'mnoSimAgreementMarketing'
    };
    
    if (typeof mnoSimPrivacyContents !== 'undefined') {
        for (const [key, id] of Object.entries(agreementMap)) {
            const setting = mnoSimPrivacyContents[key];
            if (!setting) continue;
            
            const isVisible = setting.isVisible !== false;
            if (setting.isRequired === true && isVisible) {
                requiredItems.push(id);
            }
        }
    } else {
        // 기본값: marketing 제외 모두 필수
        requiredItems.push('mnoSimAgreementPurpose', 'mnoSimAgreementItems', 'mnoSimAgreementPeriod', 'mnoSimAgreementThirdParty', 'mnoSimAgreementServiceNotice');
    }
    
    // 전체 동의 체크박스 상태 업데이트
    if (agreeAll) {
        let allRequiredChecked = true;
        for (const itemId of requiredItems) {
            const checkbox = document.getElementById(itemId);
            if (checkbox && !checkbox.checked) {
                allRequiredChecked = false;
                break;
            }
        }
        agreeAll.checked = allRequiredChecked;
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
</script>

<script src="/MVNO/assets/js/favorite-heart.js" defer></script>
<script src="/MVNO/assets/js/point-usage-integration.js" defer></script>

<?php include '../includes/footer.php'; ?>
