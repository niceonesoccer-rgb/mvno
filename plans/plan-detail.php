<?php
// 현재 페이지 설정
$current_page = 'plans';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = false;

// 요금제 ID 가져오기
$plan_id = isset($_GET['id']) ? intval($_GET['id']) : 32627;

// 헤더 포함
include '../includes/header.php';

// 요금제 데이터 가져오기
require_once '../includes/data/plan-data.php';
$plan = getPlanDetailData($plan_id);
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
}
?>

<main class="main-content plan-detail-page">
    <!-- 요금제 상세 레이아웃 (모듈 사용) -->
    <?php include '../includes/layouts/plan-detail-layout.php'; ?>

    <!-- 요금제 상세 정보 섹션 (통합) -->
    <section class="plan-detail-info-section">
        <div class="content-layout">
            <h2 class="section-title">요금제 상세 정보</h2>
            
            <!-- 기본 정보 카드 -->
            <div class="plan-info-card">
                <h3 class="plan-info-card-title">기본 정보</h3>
                <div class="plan-info-card-content">
                    <div class="plan-detail-grid">
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">요금제 이름</div>
                            <div class="plan-detail-value">
                                11월한정 LTE 100GB+밀리+Data쿠폰60GB
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통신사 약정</div>
                            <div class="plan-detail-value">없음</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통신망</div>
                            <div class="plan-detail-value">KT망</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">통신 기술</div>
                            <div class="plan-detail-value">LTE</div>
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
                            <div class="plan-detail-value">무제한</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">문자</div>
                            <div class="plan-detail-value">무제한</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">데이터 제공량</div>
                            <div class="plan-detail-value">월 100GB</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">데이터 소진시</div>
                            <div class="plan-detail-value">
                                5mbps 속도로 무제한
                            </div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">부가통화</div>
                            <div class="plan-detail-value">300분</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">번호이동 수수료</div>
                            <div class="plan-detail-value">없음</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">모바일 핫스팟</div>
                            <div class="plan-detail-value">데이터 제공량 내</div>
                        </div>
                        <div class="plan-detail-item">
                            <div class="plan-detail-label">데이터 쉐어링</div>
                            <div class="plan-detail-value">데이터 제공량 내</div>
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

    <!-- 통신사 리뷰 섹션 -->
    <section class="plan-review-section" id="planReviewSection">
        <div class="content-layout">
            <div class="plan-review-header">
                <a href="/mvnos/쉐이크모바일?from=요금제상세" class="plan-review-mvno-link">
                    <span class="plan-review-logo-text">쉐이크모바일</span>
                </a>
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
                <span class="plan-review-count">11,533개</span>
            </div>

            <div class="plan-review-list">
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">전*한</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">24일 전</span>
                    </div>
                    <p class="plan-review-content">개통이 다른 회사 보다 빠르고 좋습니다. 요금제 너무 좋아서 계속 사용할 예정 입니다. 친구, 가족 들에게 소개해주고 같이 사용 하는 중입니다. 강력 추천 합니다.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">오*열</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">29일 전</span>
                    </div>
                    <p class="plan-review-content">번호 이동이나 이동 후 개통도 휴일임에도 신청서 작성하고 쓰고 있던 esim으로 안내 문자에 따라 바로 즉시 개통할 수 있어 편리했습니다.(KT알띁A → KT알띁B)</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">최*연</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">41일 전</span>
                    </div>
                    <p class="plan-review-content">고객센터 개통 전화없이 모요 통해서 개통신청하고 편의점 바로유심 사서 끼우면 바로 개통됨..타 알뜰폰 통신사보다 개통과정, 통신속도,데이터량 불편함없이 사용함..쉐이크모바일 강추</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">김*수</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">52일 전</span>
                    </div>
                    <p class="plan-review-content">데이터 속도도 빠르고 가격도 합리적이에요. 특히 100GB 제공량이 넉넉해서 매달 데이터 걱정 없이 사용하고 있습니다. 주변 사람들한테도 추천했어요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">이*민</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="plan-review-date">58일 전</span>
                    </div>
                    <p class="plan-review-content">번호이동 과정이 생각보다 간단했어요. 고객센터 상담도 친절하고 개통도 빠르게 진행되었습니다. 다만 초기 설정할 때 조금 헷갈렸지만 지금은 잘 사용 중입니다.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">박*준</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">65일 전</span>
                    </div>
                    <p class="plan-review-content">eSIM으로 개통했는데 정말 편리했어요. 유심 카드 교체 없이 바로 사용할 수 있어서 좋았습니다. 통화 품질도 깨끗하고 데이터 속도도 만족스러워요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">정*호</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">72일 전</span>
                    </div>
                    <p class="plan-review-content">기존 통신사보다 월 요금이 훨씬 저렴한데 데이터 제공량은 더 많아서 만족합니다. 사은품도 받고 가격도 좋고 일석이조네요. 강력 추천합니다!</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">강*영</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="plan-review-date">78일 전</span>
                    </div>
                    <p class="plan-review-content">처음 알뜰폰 사용인데 걱정했지만 생각보다 괜찮아요. 통신 품질도 나쁘지 않고 가격 대비 만족도가 높습니다. 다만 앱이 조금 불편한 점이 있어요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">윤*서</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">85일 전</span>
                    </div>
                    <p class="plan-review-content">밀리의 서재 무료 구독권 받아서 너무 좋아요! 요금제도 저렴하고 부가 서비스까지 받을 수 있어서 정말 만족합니다. 친구들한테도 자랑했어요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">장*우</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">91일 전</span>
                    </div>
                    <p class="plan-review-content">KT망이라서 통신 품질이 안정적이에요. 지하철이나 건물 안에서도 끊김 없이 잘 사용하고 있습니다. 데이터 소진 후에도 5Mbps로 계속 사용할 수 있어서 좋아요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">임*진</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="plan-review-date">98일 전</span>
                    </div>
                    <p class="plan-review-content">신규 가입으로 진행했는데 번호도 마음에 들고 개통도 빠르게 되었어요. 고객센터 응대도 친절하고 전체적으로 만족합니다. 다만 약정 기간이 있으면 더 좋을 것 같아요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">한*지</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">105일 전</span>
                    </div>
                    <p class="plan-review-content">데이터 쿠폰 60GB까지 받아서 총 160GB나 사용할 수 있어요! 유튜브, 넷플릭스 마음껏 보고 다니는데도 부족함이 없습니다. 정말 추천해요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">송*현</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">112일 전</span>
                    </div>
                    <p class="plan-review-content">모요 사이트에서 비교하고 신청했는데 정말 편리했어요. 여러 통신사 요금제를 한눈에 비교할 수 있어서 좋았습니다. 쉐이크모바일 선택한 거 후회 없어요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">조*혁</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="plan-review-date">119일 전</span>
                    </div>
                    <p class="plan-review-content">번호이동 수수료가 없어서 좋았어요. 다른 통신사는 수수료 받는데 여기는 없어서 부담이 적었습니다. 통화 품질도 깨끗하고 만족합니다.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">배*수</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">126일 전</span>
                    </div>
                    <p class="plan-review-content">휴일에도 개통이 가능해서 정말 편리했어요. 주말에 신청했는데 월요일 오전에 바로 개통되었습니다. 고객센터도 친절하게 안내해주셨어요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">신*아</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">133일 전</span>
                    </div>
                    <p class="plan-review-content">네이버페이 1만원 상품권 받아서 기분 좋았어요. 요금제도 저렴하고 사은품도 받고 일석이조입니다. 가족들도 모두 여기로 바꿨어요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">오*성</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="plan-review-date">140일 전</span>
                    </div>
                    <p class="plan-review-content">유심 배송도 빠르고 개통도 신속하게 진행되었어요. 처음 사용해보는 알뜰폰이라 걱정했는데 생각보다 괜찮습니다. 다만 앱 UI가 조금 아쉬워요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">류*호</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">147일 전</span>
                    </div>
                    <p class="plan-review-content">데이터 제공량이 넉넉해서 매달 걱정 없이 사용하고 있어요. 핫스팟도 데이터 제공량 내에서 사용 가능해서 노트북 연결해서도 잘 쓰고 있습니다.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">문*희</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">154일 전</span>
                    </div>
                    <p class="plan-review-content">SOLO 결합으로 추가 20GB 받아서 총 120GB 사용 중이에요! 데이터 걱정 전혀 없이 사용하고 있습니다. 가격 대비 정말 최고의 요금제인 것 같아요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">양*준</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="plan-review-date">161일 전</span>
                    </div>
                    <p class="plan-review-content">고객센터 상담이 친절하고 전문적이에요. 문의사항도 빠르게 해결해주시고 개통 과정도 원활하게 진행되었습니다. 전체적으로 만족합니다.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">홍*영</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">168일 전</span>
                    </div>
                    <p class="plan-review-content">이마트 상품권 2만원 받아서 기분 좋았어요! 요금제도 저렴하고 사은품도 다양하게 받을 수 있어서 정말 만족합니다. 주변 사람들한테도 추천했어요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">서*우</span>
                        <div class="plan-review-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="plan-review-date">175일 전</span>
                    </div>
                    <p class="plan-review-content">기존 통신사에서 번호이동 했는데 전혀 문제없이 잘 사용하고 있어요. 통신 품질도 동일하고 가격은 훨씬 저렴해서 만족합니다. 계속 사용할 예정이에요.</p>
                </div>
                <div class="plan-review-item">
                    <div class="plan-review-item-header">
                        <span class="plan-review-author">노*진</span>
                        <div class="plan-review-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="plan-review-date">182일 전</span>
                    </div>
                    <p class="plan-review-content">데이터 속도가 안정적이에요. 지하철이나 지하에서도 끊김 없이 잘 사용하고 있습니다. 가격 대비 품질이 정말 좋은 것 같아요. 추천합니다!</p>
                </div>
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
            <h3 class="review-modal-title">쉐이크모바일</h3>
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
                    <span class="review-modal-total">총 11,539개</span>
                    <div class="review-modal-sort-select-wrapper">
                        <select class="review-modal-sort-select" id="reviewSortSelect" aria-label="리뷰 정렬 방식 선택">
                            <option value="SCORE_DESC">높은 평점순</option>
                            <option value="SCORE_ASC">낮은 평점순</option>
                            <option value="CREATED_DESC">최신순</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="review-modal-list">
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">전*한</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">24일 전</span>
                    </div>
                    <p class="review-modal-item-content">개통이 다른 회사 보다 빠르고 좋습니다. 요금제 너무 좋아서 계속 사용할 예정 입니다. 친구, 가족 들에게 소개해주고 같이 사용 하는 중입니다. 강력 추천 합니다.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 미보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">오*열</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">29일 전</span>
                    </div>
                    <p class="review-modal-item-content">번호 이동이나 이동 후 개통도 휴일임에도 신청서 작성하고 쓰고 있던 esim으로 안내 문자에 따라 바로 즉시 개통할 수 있어 편리했습니다. (KT알뜰A → KT알뜰B)</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">유심 미보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">최*연</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">41일 전</span>
                    </div>
                    <p class="review-modal-item-content">고객센터 개통 전화없이 모요 통해서 개통신청하고 편의점 바로유심 사서 끼우면 바로 개통됨..타 알뜰폰 통신사보다 개통과정, 통신속도,데이터량 불편함없이 사용함..쉐이크모바일 강추</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">유심 보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">김*수</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">52일 전</span>
                    </div>
                    <p class="review-modal-item-content">데이터 속도도 빠르고 가격도 합리적이에요. 특히 100GB 제공량이 넉넉해서 매달 데이터 걱정 없이 사용하고 있습니다. 주변 사람들한테도 추천했어요.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">이*민</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="review-modal-date">58일 전</span>
                    </div>
                    <p class="review-modal-item-content">번호이동 과정이 생각보다 간단했어요. 고객센터 상담도 친절하고 개통도 빠르게 진행되었습니다. 다만 초기 설정할 때 조금 헷갈렸지만 지금은 잘 사용 중입니다.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 2일</span>
                        <span class="review-modal-tag">유심 미보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">박*준</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">65일 전</span>
                    </div>
                    <p class="review-modal-item-content">eSIM으로 개통했는데 정말 편리했어요. 유심 카드 교체 없이 바로 사용할 수 있어서 좋았습니다. 통화 품질도 깨끗하고 데이터 속도도 만족스러워요.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 미보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">정*호</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">72일 전</span>
                    </div>
                    <p class="review-modal-item-content">기존 통신사보다 월 요금이 훨씬 저렴한데 데이터 제공량은 더 많아서 만족합니다. 사은품도 받고 가격도 좋고 일석이조네요. 강력 추천합니다!</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">강*영</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="review-modal-date">78일 전</span>
                    </div>
                    <p class="review-modal-item-content">처음 알뜰폰 사용인데 걱정했지만 생각보다 괜찮아요. 통신 품질도 나쁘지 않고 가격 대비 만족도가 높습니다. 다만 앱이 조금 불편한 점이 있어요.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 2일</span>
                        <span class="review-modal-tag">유심 미보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">윤*서</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">85일 전</span>
                    </div>
                    <p class="review-modal-item-content">밀리의 서재 무료 구독권 받아서 너무 좋아요! 요금제도 저렴하고 부가 서비스까지 받을 수 있어서 정말 만족합니다. 친구들한테도 자랑했어요.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">장*우</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">91일 전</span>
                    </div>
                    <p class="review-modal-item-content">KT망이라서 통신 품질이 안정적이에요. 지하철이나 건물 안에서도 끊김 없이 잘 사용하고 있습니다. 데이터 소진 후에도 5Mbps로 계속 사용할 수 있어서 좋아요.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 미보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">임*진</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="review-modal-date">98일 전</span>
                    </div>
                    <p class="review-modal-item-content">신규 가입으로 진행했는데 번호도 마음에 들고 개통도 빠르게 되었어요. 고객센터 응대도 친절하고 전체적으로 만족합니다. 다만 약정 기간이 있으면 더 좋을 것 같아요.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 미보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">한*지</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">105일 전</span>
                    </div>
                    <p class="review-modal-item-content">데이터 쿠폰 60GB까지 받아서 총 160GB나 사용할 수 있어요! 유튜브, 넷플릭스 마음껏 보고 다니는데도 부족함이 없습니다. 정말 추천해요.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">송*현</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">112일 전</span>
                    </div>
                    <p class="review-modal-item-content">모요 사이트에서 비교하고 신청했는데 정말 편리했어요. 여러 통신사 요금제를 한눈에 비교할 수 있어서 좋았습니다. 쉐이크모바일 선택한 거 후회 없어요.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 미보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">조*혁</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="review-modal-date">119일 전</span>
                    </div>
                    <p class="review-modal-item-content">번호이동 수수료가 없어서 좋았어요. 다른 통신사는 수수료 받는데 여기는 없어서 부담이 적었습니다. 통화 품질도 깨끗하고 만족합니다.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 2일</span>
                        <span class="review-modal-tag">유심 보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">배*수</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">126일 전</span>
                    </div>
                    <p class="review-modal-item-content">휴일에도 개통이 가능해서 정말 편리했어요. 주말에 신청했는데 월요일 오전에 바로 개통되었습니다. 고객센터도 친절하게 안내해주셨어요.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 미보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">신*아</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">133일 전</span>
                    </div>
                    <p class="review-modal-item-content">네이버페이 1만원 상품권 받아서 기분 좋았어요. 요금제도 저렴하고 사은품도 받고 일석이조입니다. 가족들도 모두 여기로 바꿨어요.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">오*성</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="review-modal-date">140일 전</span>
                    </div>
                    <p class="review-modal-item-content">유심 배송도 빠르고 개통도 신속하게 진행되었어요. 처음 사용해보는 알뜰폰이라 걱정했는데 생각보다 괜찮습니다. 다만 앱 UI가 조금 아쉬워요.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 2일</span>
                        <span class="review-modal-tag">유심 미보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">류*호</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">147일 전</span>
                    </div>
                    <p class="review-modal-item-content">데이터 제공량이 넉넉해서 매달 걱정 없이 사용하고 있어요. 핫스팟도 데이터 제공량 내에서 사용 가능해서 노트북 연결해서도 잘 쓰고 있습니다.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">문*희</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">154일 전</span>
                    </div>
                    <p class="review-modal-item-content">SOLO 결합으로 추가 20GB 받아서 총 120GB 사용 중이에요! 데이터 걱정 전혀 없이 사용하고 있습니다. 가격 대비 정말 최고의 요금제인 것 같아요.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 미보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">양*준</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="review-modal-date">161일 전</span>
                    </div>
                    <p class="review-modal-item-content">고객센터 상담이 친절하고 전문적이에요. 문의사항도 빠르게 해결해주시고 개통 과정도 원활하게 진행되었습니다. 전체적으로 만족합니다.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">홍*영</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">168일 전</span>
                    </div>
                    <p class="review-modal-item-content">이마트 상품권 2만원 받아서 기분 좋았어요! 요금제도 저렴하고 사은품도 다양하게 받을 수 있어서 정말 만족합니다. 주변 사람들한테도 추천했어요.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 미보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">서*우</span>
                        <div class="review-modal-stars">
                            <span>★★★★★</span>
                        </div>
                        <span class="review-modal-date">175일 전</span>
                    </div>
                    <p class="review-modal-item-content">기존 통신사에서 번호이동 했는데 전혀 문제없이 잘 사용하고 있어요. 통신 품질도 동일하고 가격은 훨씬 저렴해서 만족합니다. 계속 사용할 예정이에요.</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 2일</span>
                        <span class="review-modal-tag">유심 보유</span>
                    </div>
                </div>
                <div class="review-modal-item">
                    <div class="review-modal-item-header">
                        <span class="review-modal-author">노*진</span>
                        <div class="review-modal-stars">
                            <span>★★★★☆</span>
                        </div>
                        <span class="review-modal-date">182일 전</span>
                    </div>
                    <p class="review-modal-item-content">데이터 속도가 안정적이에요. 지하철이나 지하에서도 끊김 없이 잘 사용하고 있습니다. 가격 대비 품질이 정말 좋은 것 같아요. 추천합니다!</p>
                    <div class="review-modal-tags">
                        <span class="review-modal-tag">KT망</span>
                        <span class="review-modal-tag">개통까지 1일</span>
                        <span class="review-modal-tag">유심 미보유</span>
                    </div>
                </div>
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
                        alert('링크가 복사되었습니다.');
                        closeModal();
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
    
    // 리뷰 정렬 선택 기능
    const reviewSortSelect = document.getElementById('reviewSortSelect');
    
    if (reviewSortSelect) {
        // 정렬 선택 변경 시
        reviewSortSelect.addEventListener('change', function(e) {
            // 여기에 정렬 로직 추가 가능
            // 예: 리뷰를 선택된 정렬 방식에 따라 정렬
            console.log('정렬 방식 변경:', this.value);
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

<?php include '../includes/footer.php'; ?>
<script src="/MVNO/assets/js/favorite-heart.js" defer></script>

