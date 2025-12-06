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

    <!-- 통신사폰 상세 정보 섹션 -->
    <section class="plan-detail-info-section">
        <div class="content-layout">
            <h2 class="section-title">상세정보</h2>
            
            <!-- 기본 정보 카드 -->
            <div class="plan-info-card">
                <h3 class="plan-info-card-title">기본 정보</h3>
                <div class="plan-info-card-content">
                    <div class="plan-detail-grid">
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">기기명</div>
                            <div class="plan-detail-value">
                                <?php echo htmlspecialchars($phone['device_name']); ?>
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">용량</div>
                            <div class="plan-detail-value"><?php echo htmlspecialchars($phone['device_storage']); ?></div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">출고가</div>
                            <div class="plan-detail-value"><?php echo htmlspecialchars($phone['device_price']); ?></div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통신사</div>
                            <div class="plan-detail-value"><?php echo htmlspecialchars($phone['provider']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 요금제 정보 카드 -->
            <div class="plan-info-card">
                <h3 class="plan-info-card-title">요금제 정보</h3>
                <div class="plan-info-card-content">
                    <div class="plan-detail-grid">
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">요금제명</div>
                            <div class="plan-detail-value"><?php echo htmlspecialchars($phone['plan_name']); ?></div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">월 요금</div>
                            <div class="plan-detail-value"><?php echo htmlspecialchars($phone['monthly_price']); ?></div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">유지기간</div>
                            <div class="plan-detail-value"><?php echo htmlspecialchars($phone['maintenance_period']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 지원금 정보 카드 -->
            <div class="plan-info-card">
                <h3 class="plan-info-card-title">지원금 정보</h3>
                <div class="plan-info-card-content">
                    <div class="plan-detail-grid">
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">공통지원할인 번호이동</div>
                            <?php 
                            // formatSupportValue 함수가 정의되어 있는지 확인
                            if (!function_exists('formatSupportValue')) {
                                function formatSupportValue($value) {
                                    $numeric_value = floatval(str_replace(',', '', $value));
                                    $is_negative = $numeric_value < 0;
                                    $abs_value = abs($numeric_value);
                                    if ($abs_value == floor($abs_value)) {
                                        $formatted = ($is_negative ? '-' : '') . number_format($abs_value, 0);
                                    } else {
                                        $formatted = ($is_negative ? '-' : '') . number_format($abs_value, 1);
                                    }
                                    return [
                                        'value' => $formatted,
                                        'is_negative' => $is_negative
                                    ];
                                }
                            }
                            $common_port = formatSupportValue($phone['common_number_port']);
                            ?>
                            <div class="plan-detail-value"><?php echo htmlspecialchars($common_port['value']); ?>만원</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">공통지원할인 기기변경</div>
                            <?php 
                            $common_change = formatSupportValue($phone['common_device_change']);
                            ?>
                            <div class="plan-detail-value"><?php echo htmlspecialchars($common_change['value']); ?>만원</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">선택약정할인 번호이동</div>
                            <?php 
                            $contract_port = formatSupportValue($phone['contract_number_port']);
                            ?>
                            <div class="plan-detail-value"><?php echo htmlspecialchars($contract_port['value']); ?>만원</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">선택약정할인 기기변경</div>
                            <?php 
                            $contract_change = formatSupportValue($phone['contract_device_change']);
                            ?>
                            <div class="plan-detail-value"><?php echo htmlspecialchars($contract_change['value']); ?>만원</div>
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
                            <div class="plan-detail-value">배송가능 (6,600원)</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">NFC 유심</div>
                            <div class="plan-detail-value">배송불가</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">eSIM</div>
                            <div class="plan-detail-value">개통가능 (2,750원)</div>
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
                                22.53원/MB
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">음성 통화</div>
                            <div class="plan-detail-value">1.98원/초</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">부가/영상통화</div>
                            <div class="plan-detail-value">3.3원/초</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">단문메시지(SMS)</div>
                            <div class="plan-detail-value">22원/개</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">장문 텍스트형(MMS)</div>
                            <div class="plan-detail-value">44원/개</div>
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
                        여기 추가내용
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 통신사폰 리뷰 섹션 -->
    <section class="phone-review-section" id="phoneReviewSection">
        <div class="content-layout">
            <div class="plan-review-header">
                <?php 
                $company_name = $phone['company_name'] ?? '쉐이크모바일';
                ?>
                <span class="plan-review-logo-text"><?php echo htmlspecialchars($company_name); ?></span>
                <h2 class="section-title">리뷰</h2>
            </div>
            
            <div class="plan-review-summary">
                <div class="plan-review-rating">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FAB005"/>
                    </svg>
                    <span class="plan-review-rating-score">4.3</span>
                </div>
                <div class="plan-review-categories">
                    <div class="plan-review-category">
                        <span class="plan-review-category-label">친절해요</span>
                        <span class="plan-review-category-score">4.2</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                    </div>
                    <div class="plan-review-category">
                        <span class="plan-review-category-label">개통 빨라요</span>
                        <span class="plan-review-category-score">4.5</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="plan-review-count-section">
                <span class="plan-review-count">8,247개</span>
            </div>

            <div class="plan-review-list" id="phoneReviewList">
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">김*호</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">3일 전</span>
                    </div>
                    <p class="plan-review-content">통신사폰 구매했는데 정말 만족해요. 기기 할인도 많이 받고 요금제도 좋아서 추천합니다. 고객센터도 친절하게 상담해주셨어요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">이*영</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">7일 전</span>
                    </div>
                    <p class="plan-review-content">번호이동으로 가입했는데 개통이 빠르고 안정적이에요. 통신 품질도 기존 통신사와 동일해서 만족합니다. 할인 혜택도 좋아요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">박*준</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="plan-review-date">12일 전</span>
                    </div>
                    <p class="plan-review-content">신규 가입으로 진행했는데 번호도 마음에 들고 개통도 빠르게 되었어요. 지원금도 많이 받아서 좋았습니다.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">최*수</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">18일 전</span>
                    </div>
                    <p class="plan-review-content">기기 변경으로 가입했는데 할인 혜택이 정말 좋아요. 기존보다 훨씬 저렴하게 구매할 수 있어서 만족합니다.</p>
                </div>
                <div class="plan-review-item">
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

<!-- 통신사폰 신청하기 모달 -->
<div class="phone-apply-modal" id="phoneApplyModal">
    <div class="phone-apply-modal-overlay" id="phoneApplyModalOverlay"></div>
    <div class="phone-apply-modal-content">
        <div class="phone-apply-modal-header">
            <button class="phone-apply-modal-back" aria-label="뒤로 가기" id="phoneApplyModalBack" style="display: none;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 18L9 12L15 6" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <h3 class="phone-apply-modal-title">가입유형</h3>
            <button class="phone-apply-modal-close" aria-label="닫기" id="phoneApplyModalClose">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="phone-apply-modal-body" id="phoneApplyModalBody">
            <!-- 2단계: 가입 방법 선택 -->
            <div class="phone-apply-modal-step" id="phoneStep2">
                <div class="plan-order-section">
                    <div class="plan-order-checkbox-group">
                        <div class="plan-order-checkbox-item">
                            <input type="checkbox" id="phoneNumberPort" name="phoneJoinMethod" value="port" class="plan-order-checkbox-input">
                            <label for="phoneNumberPort" class="plan-order-checkbox-label">
                                <div class="plan-order-checkbox-content">
                                    <div class="plan-order-checkbox-title">번호 이동</div>
                                    <div class="plan-order-checkbox-description">지금 쓰는 번호 그대로 사용할래요</div>
                                </div>
                            </label>
                        </div>
                        <div class="plan-order-checkbox-item">
                            <input type="checkbox" id="phoneNewJoin" name="phoneJoinMethod" value="new" class="plan-order-checkbox-input">
                            <label for="phoneNewJoin" class="plan-order-checkbox-label">
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
            <div class="phone-apply-modal-step" id="phoneStep3" style="display: none;">
                <div class="plan-apply-confirm-section">
                    <div class="plan-apply-confirm-description">
                        <div class="plan-apply-confirm-intro">
                            모요에서 다음 정보가 알림톡으로 발송됩니다:<br>
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
                        <div class="plan-apply-confirm-notice"><?php echo htmlspecialchars($phone['provider']); ?> 통신사로 가입을 진행합니다</div>
                    </div>
                    <button class="plan-apply-confirm-btn" id="phoneApplyConfirmBtn" data-apply-url="<?php echo htmlspecialchars($phone['apply_url'] ?? 'https://www.daum.net'); ?>">신청하기</button>
                </div>
            </div>
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
                $company_name = $phone['company_name'] ?? '쉐이크모바일';
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
                        <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FAB005"/>
                    </svg>
                    <span class="review-modal-rating-score">4.3</span>
                </div>
                <div class="review-modal-categories">
                    <div class="review-modal-category">
                        <span class="review-modal-category-label">친절해요</span>
                        <span class="review-modal-category-score">4.2</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                    </div>
                    <div class="review-modal-category">
                        <span class="review-modal-category-label">개통 빨라요</span>
                        <span class="review-modal-category-score">4.5</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="review-modal-sort">
                <div class="review-modal-sort-wrapper">
                    <span class="review-modal-total">총 8,247개</span>
                    <div class="review-modal-sort-select-wrapper">
                        <select class="review-modal-sort-select" id="phoneReviewSortSelect" aria-label="리뷰 정렬 방식 선택">
                            <option value="SCORE_DESC">높은 평점순</option>
                            <option value="SCORE_ASC">낮은 평점순</option>
                            <option value="CREATED_DESC">최신순</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="review-modal-list" id="phoneReviewModalList">
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">김*호</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">3일 전</span>
                    </div>
                    <p class="review-modal-item-content">통신사폰 구매했는데 정말 만족해요. 기기 할인도 많이 받고 요금제도 좋아서 추천합니다. 고객센터도 친절하게 상담해주셨어요.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">이*영</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">7일 전</span>
                    </div>
                    <p class="review-modal-item-content">번호이동으로 가입했는데 개통이 빠르고 안정적이에요. 통신 품질도 기존 통신사와 동일해서 만족합니다. 할인 혜택도 좋아요.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">박*준</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="review-modal-date">12일 전</span>
                    </div>
                    <p class="review-modal-item-content">신규 가입으로 진행했는데 번호도 마음에 들고 개통도 빠르게 되었어요. 지원금도 많이 받아서 좋았습니다.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">최*수</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">18일 전</span>
                    </div>
                    <p class="review-modal-item-content">기기 변경으로 가입했는데 할인 혜택이 정말 좋아요. 기존보다 훨씬 저렴하게 구매할 수 있어서 만족합니다.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">정*민</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">24일 전</span>
                    </div>
                    <p class="review-modal-item-content">통신사폰 처음 사용해봤는데 생각보다 괜찮아요. 통신 품질도 좋고 가격도 합리적입니다. 주변 사람들한테도 추천했어요.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">강*희</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="review-modal-date">31일 전</span>
                    </div>
                    <p class="review-modal-item-content">선택약정으로 가입했는데 추가 할인이 있어서 좋았어요. 약정 기간 동안 안정적으로 사용할 수 있어서 만족합니다.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">윤*진</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">38일 전</span>
                    </div>
                    <p class="review-modal-item-content">공통지원할인 받아서 정말 저렴하게 구매했어요. 기기 품질도 좋고 통신도 안정적입니다. 강력 추천합니다!</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">장*우</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">45일 전</span>
                    </div>
                    <p class="review-modal-item-content">통신사폰 구매 과정이 생각보다 간단했어요. 온라인으로 신청하고 바로 개통되어서 편리했습니다. 고객센터도 친절해요.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">임*성</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="review-modal-date">52일 전</span>
                    </div>
                    <p class="review-modal-item-content">기기 할인과 요금제 할인을 동시에 받아서 정말 좋았어요. 월 요금도 부담 없고 통신 품질도 만족스럽습니다.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">한*지</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">59일 전</span>
                    </div>
                    <p class="review-modal-item-content">번호이동 수수료 없이 진행할 수 있어서 좋았어요. 개통도 빠르고 통신 품질도 기존과 동일해서 만족합니다.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">송*현</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">66일 전</span>
                    </div>
                    <p class="review-modal-item-content">통신사폰으로 갤럭시 구매했는데 할인 혜택이 정말 좋아요. 기존보다 훨씬 저렴하게 구매할 수 있어서 만족합니다.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">조*혁</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="review-modal-date">73일 전</span>
                    </div>
                    <p class="review-modal-item-content">신규 가입으로 진행했는데 번호도 마음에 들고 개통도 빠르게 되었어요. 지원금도 많이 받아서 좋았습니다.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">배*수</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">80일 전</span>
                    </div>
                    <p class="review-modal-item-content">통신사폰 처음 사용해봤는데 생각보다 괜찮아요. 통신 품질도 좋고 가격도 합리적입니다. 주변 사람들한테도 추천했어요.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">신*아</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">87일 전</span>
                    </div>
                    <p class="review-modal-item-content">기기 변경으로 가입했는데 할인 혜택이 정말 좋아요. 기존보다 훨씬 저렴하게 구매할 수 있어서 만족합니다.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">오*성</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="review-modal-date">94일 전</span>
                    </div>
                    <p class="review-modal-item-content">선택약정으로 가입했는데 추가 할인이 있어서 좋았어요. 약정 기간 동안 안정적으로 사용할 수 있어서 만족합니다.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">류*호</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">101일 전</span>
                    </div>
                    <p class="review-modal-item-content">공통지원할인 받아서 정말 저렴하게 구매했어요. 기기 품질도 좋고 통신도 안정적입니다. 강력 추천합니다!</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">문*희</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">108일 전</span>
                    </div>
                    <p class="review-modal-item-content">통신사폰 구매 과정이 생각보다 간단했어요. 온라인으로 신청하고 바로 개통되어서 편리했습니다. 고객센터도 친절해요.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">양*준</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="review-modal-date">115일 전</span>
                    </div>
                    <p class="review-modal-item-content">기기 할인과 요금제 할인을 동시에 받아서 정말 좋았어요. 월 요금도 부담 없고 통신 품질도 만족스럽습니다.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">홍*영</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">122일 전</span>
                    </div>
                    <p class="review-modal-item-content">번호이동 수수료 없이 진행할 수 있어서 좋았어요. 개통도 빠르고 통신 품질도 기존과 동일해서 만족합니다.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">서*우</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">129일 전</span>
                    </div>
                    <p class="review-modal-item-content">통신사폰으로 아이폰 구매했는데 할인 혜택이 정말 좋아요. 기존보다 훨씬 저렴하게 구매할 수 있어서 만족합니다.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">노*진</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="review-modal-date">136일 전</span>
                    </div>
                    <p class="review-modal-item-content">신규 가입으로 진행했는데 번호도 마음에 들고 개통도 빠르게 되었어요. 지원금도 많이 받아서 좋았습니다.</p>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">김*수</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">143일 전</span>
                    </div>
                    <p class="review-modal-item-content">통신사폰 처음 사용해봤는데 생각보다 괜찮아요. 통신 품질도 좋고 가격도 합리적입니다. 주변 사람들한테도 추천했어요.</p>
                </div>
            </div>
            <button class="review-modal-more-btn" id="phoneReviewModalMoreBtn">리뷰 더보기</button>
        </div>
    </div>
</div>

<script>
// 통신사폰 신청하기 모달 기능
document.addEventListener('DOMContentLoaded', function() {
    const applyBtn = document.getElementById('phoneApplyBtn');
    const applyModal = document.getElementById('phoneApplyModal');
    const applyModalOverlay = document.getElementById('phoneApplyModalOverlay');
    const applyModalClose = document.getElementById('phoneApplyModalClose');
    const applyModalBody = document.getElementById('phoneApplyModalBody');
    const applyModalBack = document.getElementById('phoneApplyModalBack');

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
    
    // 모달 열기 함수
    function openApplyModal() {
        console.log('openApplyModal 호출');
        if (!applyModal) {
            console.error('모달을 찾을 수 없습니다.');
            return;
        }
        
        console.log('모달 요소:', applyModal);
        
        // 현재 스크롤 위치 저장
        scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        
        // 스크롤바 너비 계산
        const scrollbarWidth = getScrollbarWidth();
        
        // body 스크롤 방지 (스크롤바 너비만큼 padding-right 추가하여 레이아웃 이동 방지)
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollPosition}px`;
        document.body.style.width = '100%';
        document.body.style.paddingRight = `${scrollbarWidth}px`;
        
        // html 요소도 스크롤 방지 (일부 브라우저용)
        document.documentElement.style.overflow = 'hidden';
        
        // 모달 열기
        applyModal.style.display = 'flex';
        applyModal.classList.add('phone-apply-modal-active');
        console.log('모달 display 설정:', applyModal.style.display);
        console.log('모달 클래스:', applyModal.className);
        
        // 신청 안내 모달(3단계) 바로 표시 (가입유형 선택 건너뛰기)
        showStep(3);
    }
    
    // 모달 닫기 함수
    function closeApplyModal() {
        if (!applyModal) return;
        
        // 모달 닫기
        applyModal.classList.remove('phone-apply-modal-active');
        
        // body 스크롤 복원
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        document.body.style.paddingRight = '';
        document.documentElement.style.overflow = '';
        
        // 저장된 스크롤 위치로 복원
        window.scrollTo(0, scrollPosition);
    }
    
    // 모달 단계 관리
    let currentStep = 3;
    
    // 단계 표시 함수
    function showStep(stepNumber, selectedMethod) {
        const step2 = document.getElementById('phoneStep2');
        const step3 = document.getElementById('phoneStep3');
        const modalTitle = document.querySelector('.phone-apply-modal-title');
        
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
            const confirmBtn = document.getElementById('phoneApplyConfirmBtn');
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
    const joinMethodInputs = document.querySelectorAll('input[name="phoneJoinMethod"]');
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
        applyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('신청하기 버튼 클릭됨');
            openApplyModal();
        });
    } else {
        console.error('신청하기 버튼을 찾을 수 없습니다: phoneApplyBtn');
    }

    // 모달 닫기 이벤트
    if (applyModalOverlay) {
        applyModalOverlay.addEventListener('click', closeApplyModal);
    }

    if (applyModalClose) {
        applyModalClose.addEventListener('click', closeApplyModal);
    }

    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && applyModal && applyModal.classList.contains('phone-apply-modal-active')) {
            closeApplyModal();
        }
    });

    // 최종 신청하기 버튼 클릭 이벤트
    const confirmBtn = document.getElementById('phoneApplyConfirmBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            // 상품 신청 URL 가져오기
            const applyUrl = this.getAttribute('data-apply-url') || 'https://www.daum.net';
            // 해당 URL로 이동
            window.location.href = applyUrl;
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
    
    // 리뷰 정렬 선택 기능
    const reviewSortSelect = document.getElementById('phoneReviewSortSelect');
    if (reviewSortSelect) {
        reviewSortSelect.addEventListener('change', function(e) {
            // 여기에 정렬 로직 추가 가능
            console.log('정렬 방식 변경:', this.value);
        });
    }
});
</script>

<?php
// 포인트 사용 모달 포함
$type = 'mno';
$item_id = $phone_id;
$item_name = $phone['device_name'] ?? '통신사폰';
include '../includes/components/point-usage-modal.php';
?>

<script src="/MVNO/assets/js/point-usage-integration.js" defer></script>
<script>
// 통신사폰 신청하기 버튼에 포인트 모달 연동
document.addEventListener('DOMContentLoaded', function() {
    const applyBtn = document.getElementById('phoneApplyBtn');
    if (applyBtn) {
        applyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // 포인트 모달 열기
            const modalId = 'pointUsageModal_mno_<?php echo $phone_id; ?>';
            const modal = document.getElementById(modalId);
            if (modal && typeof openPointUsageModal === 'function') {
                openPointUsageModal('mno', <?php echo $phone_id; ?>);
            } else if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        });
    }
    
    // 포인트 사용 확인 후 기존 신청 모달 열기
    document.addEventListener('pointUsageConfirmed', function(e) {
        const { type, itemId, usedPoint } = e.detail;
        if (type === 'mno') {
            console.log('포인트 사용 확인됨:', e.detail);
            // TODO: 기존 통신사폰 신청 모달 열기
        }
    });
});
</script>

