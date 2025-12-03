<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'esim';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 국가/지역 정보 매핑
$countries = [
    'japan' => ['name' => '일본', 'image' => 'japan.svg', 'badge' => '무제한'],
    'china' => ['name' => '중국', 'image' => 'china.svg', 'badge' => '무제한'],
    'taiwan' => ['name' => '대만', 'image' => 'taiwan.svg', 'badge' => '무제한'],
    'philippines' => ['name' => '필리핀', 'image' => 'philippines.svg', 'badge' => '무제한'],
    'thailand' => ['name' => '태국', 'image' => 'thailand.svg', 'badge' => '무제한'],
    'vietnam' => ['name' => '베트남', 'image' => 'vietnam.svg', 'badge' => '무제한'],
    'malaysia' => ['name' => '말레이시아', 'image' => 'malaysia.svg', 'badge' => '무제한'],
    'singapore' => ['name' => '싱가포르', 'image' => 'singapore.svg', 'badge' => '무제한'],
    'usa' => ['name' => '미국', 'image' => 'usa.svg', 'badge' => '무제한'],
    'australia' => ['name' => '호주', 'image' => 'australia.svg', 'badge' => '무제한'],
    'indonesia' => ['name' => '인도네시아', 'image' => 'indonesia.svg', 'badge' => '무제한'],
    'uae' => ['name' => '아랍에미리트', 'image' => 'uae.svg'],
    'hongkong' => ['name' => '홍콩', 'image' => 'hongkong.svg'],
    'guam' => ['name' => '괌', 'image' => 'guam.svg'],
    'canada' => ['name' => '캐나다', 'image' => 'canada.svg'],
    'cambodia' => ['name' => '캄보디아', 'image' => 'cambodia.svg', 'badge' => '무제한'],
    'italy' => ['name' => '이탈리아', 'image' => 'italy.svg'],
    'macau' => ['name' => '마카오', 'image' => 'macau.svg'],
    'france' => ['name' => '프랑스', 'image' => 'france.svg'],
    'spain' => ['name' => '스페인', 'image' => 'spain.svg'],
    'turkey' => ['name' => '튀르키예(터키)', 'image' => 'turkey.svg'],
    'uk' => ['name' => '영국', 'image' => 'uk.svg'],
    'germany' => ['name' => '독일', 'image' => 'germany.svg'],
    'qatar' => ['name' => '카타르', 'image' => 'qatar.svg'],
    'portugal' => ['name' => '포르투갈', 'image' => 'portugal.svg'],
    'india' => ['name' => '인도', 'image' => 'india.svg'],
    'mexico' => ['name' => '멕시코', 'image' => 'mexico.svg'],
    'laos' => ['name' => '라오스', 'image' => 'laos.svg'],
    'southkorea' => ['name' => '대한민국', 'image' => 'southkorea.svg', 'badge' => '무제한'],
    'denmark' => ['name' => '덴마크', 'image' => 'denmark.svg'],
    'maldives' => ['name' => '몰디브', 'image' => 'maldives.svg'],
    'sweden' => ['name' => '스웨덴', 'image' => 'sweden.svg'],
    'austria' => ['name' => '오스트리아', 'image' => 'austria.svg'],
    'newzealand' => ['name' => '뉴질랜드', 'image' => 'newzealand.svg', 'badge' => '무제한'],
    'ireland' => ['name' => '아일랜드', 'image' => 'ireland.svg'],
    'mongolia' => ['name' => '몽골', 'image' => 'mongolia.svg'],
    'kazakhstan' => ['name' => '카자흐스탄', 'image' => 'kazakhstan.svg'],
];

$regions = [
    'global-151' => ['name' => '글로벌 151개국', 'image' => 'global-151.svg'],
    'europe-42' => ['name' => '유럽 42개국', 'image' => 'europe-42.svg', 'badge' => '무제한'],
    'europe-36' => ['name' => '유럽 36개국', 'image' => 'europe-36.svg', 'badge' => '무제한'],
    'europe-33' => ['name' => '유럽 33개국', 'image' => 'europe-33.svg', 'badge' => '무제한'],
    'hongkong-macau' => ['name' => '홍콩/마카오', 'image' => 'hongkong-macau.svg', 'badge' => '무제한'],
    'china-hongkong-macau' => ['name' => '중국/홍콩/마카오', 'image' => 'china-hongkong-macau.svg', 'badge' => '무제한'],
    'southeast-asia-3' => ['name' => '동남아 3개국', 'image' => 'southeast-asia-3.svg'],
    'usa-canada' => ['name' => '미국/캐나다', 'image' => 'usa-canada.svg'],
    'australia-newzealand' => ['name' => '호주/뉴질랜드', 'image' => 'australia-newzealand.svg', 'badge' => '무제한'],
    'asia-13' => ['name' => '아시아 13개국', 'image' => 'asia-13.svg'],
    'guam-saipan' => ['name' => '괌/사이판', 'image' => 'guam-saipan.svg'],
    'north-central-america-3' => ['name' => '북중미 3개국', 'image' => 'north-central-america-3.svg'],
    'southeast-asia-8' => ['name' => '동남아 8개국', 'image' => 'southeast-asia-8.svg'],
    'south-america-11' => ['name' => '남미 11개국', 'image' => 'south-america-11.png'],
];

// 이미지 경로 헬퍼 함수 (로컬 파일만 사용)
function getCountryImageUrl($filename) {
    return '../mvno/assets/images/' . $filename;
}

// 쿼리 파라미터 확인
$selected_country = isset($_GET['country']) ? $_GET['country'] : null;
$selected_region = isset($_GET['region']) ? $_GET['region'] : null;

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div class="content-layout">
        <?php if ($selected_country && isset($countries[$selected_country])): ?>
            <!-- 국가 상세 페이지 -->
            <?php $country = $countries[$selected_country]; ?>
            <section class="esim-detail-section">
                <!-- eSIM / USIM 탭 -->
                <div class="esim-type-tabs">
                    <div class="esim-type-tabs-container">
                        <button type="button" class="esim-type-tab active" data-type="esim">
                            <span>eSIM</span>
                        </button>
                        <button type="button" class="esim-type-tab" data-type="usim">
                            <span>USIM</span>
                        </button>
                    </div>
                </div>

                <!-- 국가 정보 및 기간 선택 -->
                <div class="esim-country-info-section">
                    <h1 class="esim-country-question">
                        <?php echo htmlspecialchars($country['name']); ?> 에서<br>
                        <span class="esim-duration-text">4일</span> 동안 사용하시나요?
                    </h1>
                    
                    <!-- 기간 선택 버튼 -->
                    <div class="esim-duration-selector">
                        <button type="button" class="duration-btn" data-days="3">3일</button>
                        <button type="button" class="duration-btn active" data-days="4">4일</button>
                        <button type="button" class="duration-btn" data-days="5">5일</button>
                        <button type="button" class="duration-btn" data-days="custom">기간 선택</button>
                    </div>
                </div>

                <!-- 프로모션 배너 -->
                <div class="esim-promo-banner">
                    <div class="esim-promo-content">
                        <svg class="esim-promo-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z" fill="currentColor"/>
                        </svg>
                        <span class="esim-promo-text"><?php echo htmlspecialchars($country['name']); ?> 여행 필수템! 로컬 eSIM, USIM 출시!</span>
                    </div>
                </div>

                <!-- Google Maps 데이터 무료 섹션 -->
                <div class="esim-google-maps-section">
                    <div class="esim-google-maps-card">
                        <div class="esim-google-maps-header">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#4285F4"/>
                            </svg>
                            <span class="esim-google-maps-title">Google Maps 데이터 무료</span>
                            <a href="#" class="esim-google-maps-desc">자세히 <span class="esim-arrow">></span></a>
                        </div>
                        <p class="esim-google-maps-note">데이터 차감 없이 앱을 사용해 보세요.(로밍망 선택 시)</p>
                    </div>
                </div>

                <!-- 추천 상품 섹션 -->
                <div class="esim-recommended-section">
                    <h2 class="esim-recommended-title">모유 추천상품</h2>
                    <div class="esim-products-grid">
                        <!-- 추천 상품 카드 -->
                        <div class="esim-product-card">
                            <div class="esim-product-header">
                                <div class="esim-product-country">
                                    <span class="esim-country-dot"></span>
                                    <span class="esim-country-name"><?php echo htmlspecialchars($country['name']); ?></span>
                                </div>
                            </div>
                            <div class="esim-product-details">
                                <div class="esim-product-detail-row">
                                    <span class="esim-detail-label">기간/망</span>
                                    <span class="esim-detail-value">4일/로밍망</span>
                                </div>
                                <div class="esim-product-detail-row">
                                    <span class="esim-detail-label">데이터</span>
                                    <span class="esim-detail-value">완전 무제한</span>
                                </div>
                                <div class="esim-product-detail-row">
                                    <span class="esim-detail-label">금액</span>
                                    <span class="esim-detail-value esim-price">12,500원</span>
                                </div>
                            </div>
                            <div class="esim-product-actions">
                                <button type="button" class="esim-btn-detail">상세보기</button>
                                <button type="button" class="esim-btn-purchase">구매하기</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 데이터 요금제 섹션 -->
                <div class="esim-plans-section">
                    <div class="esim-network-tabs">
                        <button type="button" class="esim-network-tab active" data-network="roaming">로밍망</button>
                        <button type="button" class="esim-network-tab" data-network="local">로컬망</button>
                    </div>
                    <div class="esim-plans-list">
                        <!-- 로밍망 요금제 -->
                        <div class="esim-plan-group" data-network="roaming">
                            <div class="esim-plan-item active">
                                <span class="esim-plan-name">완전 무제한</span>
                                <span class="esim-plan-price">12,500원</span>
                            </div>
                            <div class="esim-plan-item">
                                <span class="esim-plan-name">매일 500MB 이후 저속 무제한</span>
                                <span class="esim-plan-price">2,500원</span>
                            </div>
                            <div class="esim-plan-item">
                                <span class="esim-plan-name">매일 1GB 이후 저속 무제한</span>
                                <span class="esim-plan-price">3,600원</span>
                            </div>
                            <div class="esim-plan-item">
                                <span class="esim-plan-name">매일 2GB 이후 저속 무제한</span>
                                <span class="esim-plan-price">6,200원</span>
                            </div>
                            <div class="esim-plan-item">
                                <span class="esim-plan-name">매일 3GB 이후 저속 무제한</span>
                                <span class="esim-plan-price">8,400원</span>
                            </div>
                            <div class="esim-plan-item">
                                <span class="esim-plan-name">매일 4GB 이후 저속 무제한</span>
                                <span class="esim-plan-price">8,600원</span>
                            </div>
                            <div class="esim-plan-item">
                                <span class="esim-plan-name">매일 5GB 이후 저속 무제한</span>
                                <span class="esim-plan-price">8,900원</span>
                            </div>
                        </div>
                        <!-- 로컬망 요금제 -->
                        <div class="esim-plan-group" data-network="local" style="display: none;">
                            <div class="esim-plan-item active">
                                <span class="esim-plan-name">완전 무제한</span>
                                <span class="esim-plan-price">21,000원</span>
                            </div>
                            <div class="esim-plan-item">
                                <span class="esim-plan-name">매일 500MB 이후 저속 무제한</span>
                                <span class="esim-plan-price">3,300원</span>
                            </div>
                            <div class="esim-plan-item">
                                <span class="esim-plan-name">매일 1GB 이후 저속 무제한</span>
                                <span class="esim-plan-price">6,000원</span>
                            </div>
                            <div class="esim-plan-item">
                                <span class="esim-plan-name">매일 2GB 이후 저속 무제한</span>
                                <span class="esim-plan-price">8,800원</span>
                            </div>
                            <div class="esim-plan-item">
                                <span class="esim-plan-name">매일 3GB 이후 저속 무제한</span>
                                <span class="esim-plan-price">12,000원</span>
                            </div>
                            <div class="esim-plan-item">
                                <span class="esim-plan-name">매일 4GB 이후 저속 무제한</span>
                                <span class="esim-plan-price">14,500원</span>
                            </div>
                            <div class="esim-plan-item">
                                <span class="esim-plan-name">매일 5GB 이후 저속 무제한</span>
                                <span class="esim-plan-price">15,800원</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 기간 선택 모달 -->
                <div class="esim-duration-modal" id="durationModal">
                    <div class="esim-duration-modal-overlay"></div>
                    <div class="esim-duration-modal-content">
                        <div class="esim-duration-modal-header">
                            <h3 class="esim-duration-modal-title">며칠 동안 사용하세요?</h3>
                            <button type="button" class="esim-duration-modal-close" aria-label="닫기">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                        <div class="esim-duration-modal-body">
                            <?php for ($i = 1; $i <= 30; $i++): ?>
                                <button type="button" class="esim-duration-option" data-days="<?php echo $i; ?>">
                                    <?php echo $i; ?>일
                                </button>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php elseif ($selected_region && isset($regions[$selected_region])): ?>
            <!-- 지역 상세 페이지 -->
            <?php $region = $regions[$selected_region]; ?>
            <section class="esim-detail-section">
                <div class="esim-detail-header">
                    <a href="/MVNO/esim/esim.php" class="back-link">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                            <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>목록으로</span>
                    </a>
                    <h1 class="esim-detail-title"><?php echo htmlspecialchars($region['name']); ?> eSIM</h1>
                </div>
                <div class="esim-detail-content">
                    <div class="country-flag-large">
                        <img src="<?php echo getCountryImageUrl($region['image']); ?>" alt="<?php echo htmlspecialchars($region['name']); ?>" class="flag-large">
                        <?php if (isset($region['badge'])): ?>
                            <div class="badge-large"><?php echo htmlspecialchars($region['badge']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="country-info">
                        <h2><?php echo htmlspecialchars($region['name']); ?> 해외이심 상품</h2>
                        <p>다양한 요금제를 확인하고 선택해보세요.</p>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <!-- 기본 목록 페이지 -->
            <section class="esim-section">
                <!-- 헤더 영역 -->
                <div class="esim-header">
                    <h1 class="esim-title">어디로 떠나시나요?</h1>
                <div class="esim-controller">
                    <!-- 탭 메뉴 -->
                    <div class="esim-tabs-wrapper">
                        <div class="esim-tabs-container" role="tablist">
                            <div class="esim-tabs-indicator" aria-hidden="true"></div>
                            <button type="button" role="tab" aria-selected="true" class="esim-tab-item active" data-tab="popular">
                                <span class="esim-tab-text">인기국가</span>
                            </button>
                            <button type="button" role="tab" aria-selected="false" class="esim-tab-item" data-tab="continent">
                                <span class="esim-tab-text">다국가</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 컨테이너 -->
            <div class="esim-container">
                <!-- 인기국가 탭 -->
                <div id="tab-popular" class="esim-tab-pane active">
                    <div class="country-grid">
                        <a href="/MVNO/esim/esim.php?country=japan" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('japan.svg'); ?>" alt="일본" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">일본</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=china" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('china.svg'); ?>" alt="중국" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">중국</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=taiwan" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('taiwan.svg'); ?>" alt="대만" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">대만</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=philippines" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('philippines.svg'); ?>" alt="필리핀" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">필리핀</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=thailand" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('thailand.svg'); ?>" alt="태국" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">태국</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=vietnam" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('vietnam.svg'); ?>" alt="베트남" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">베트남</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=malaysia" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('malaysia.svg'); ?>" alt="말레이시아" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">말레이시아</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=singapore" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('singapore.svg'); ?>" alt="싱가포르" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">싱가포르</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=usa" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('usa.svg'); ?>" alt="미국" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">미국</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=australia" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('australia.svg'); ?>" alt="호주" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">호주</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=indonesia" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('indonesia.svg'); ?>" alt="인도네시아" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">인도네시아</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=uae" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('uae.svg'); ?>" alt="아랍에미리트" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">아랍에미리트</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=hongkong" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('hongkong.svg'); ?>" alt="홍콩" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">홍콩</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=guam" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('guam.svg'); ?>" alt="괌" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">괌</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=canada" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('canada.svg'); ?>" alt="캐나다" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">캐나다</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=cambodia" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('cambodia.svg'); ?>" alt="캄보디아" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">캄보디아</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=italy" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('italy.svg'); ?>" alt="이탈리아" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">이탈리아</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=macau" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('macau.svg'); ?>" alt="마카오" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">마카오</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=france" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('france.svg'); ?>" alt="프랑스" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">프랑스</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=spain" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('spain.svg'); ?>" alt="스페인" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">스페인</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=turkey" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('turkey.svg'); ?>" alt="튀르키예(터키)" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">튀르키예(터키)</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=uk" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('uk.svg'); ?>" alt="영국" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">영국</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=germany" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('germany.svg'); ?>" alt="독일" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">독일</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=qatar" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('qatar.svg'); ?>" alt="카타르" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">카타르</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=portugal" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('portugal.svg'); ?>" alt="포르투갈" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">포르투갈</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=india" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('india.svg'); ?>" alt="인도" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">인도</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=mexico" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('mexico.svg'); ?>" alt="멕시코" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">멕시코</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=laos" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('laos.svg'); ?>" alt="라오스" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">라오스</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=southkorea" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('southkorea.svg'); ?>" alt="대한민국" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">대한민국</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=denmark" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('denmark.svg'); ?>" alt="덴마크" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">덴마크</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=maldives" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('maldives.svg'); ?>" alt="몰디브" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">몰디브</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=sweden" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('sweden.svg'); ?>" alt="스웨덴" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">스웨덴</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=austria" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('austria.svg'); ?>" alt="오스트리아" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">오스트리아</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=newzealand" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('newzealand.svg'); ?>" alt="뉴질랜드" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">뉴질랜드</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=ireland" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('ireland.svg'); ?>" alt="아일랜드" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">아일랜드</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=mongolia" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('mongolia.svg'); ?>" alt="몽골" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">몽골</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?country=kazakhstan" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('kazakhstan.svg'); ?>" alt="카자흐스탄" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">카자흐스탄</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- 다국가 탭 -->
                <div id="tab-continent" class="esim-tab-pane">
                    <div class="country-grid">
                        <a href="/MVNO/esim/esim.php?region=global-151" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('global-151.svg'); ?>" alt="글로벌 151개국" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">글로벌<br>151개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?region=europe-42" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('europe-42.svg'); ?>" alt="유럽 42개국" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">유럽<br>42개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?region=europe-36" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('europe-36.svg'); ?>" alt="유럽 36개국" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">유럽<br>36개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?region=europe-33" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('europe-33.svg'); ?>" alt="유럽 33개국" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">유럽<br>33개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?region=hongkong-macau" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('hongkong-macau.svg'); ?>" alt="홍콩/마카오" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">홍콩/<br>마카오</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?region=china-hongkong-macau" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('china-hongkong-macau.svg'); ?>" alt="중국/홍콩/마카오" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">중국/<br>홍콩/<br>마카오</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?region=southeast-asia-3" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('southeast-asia-3.svg'); ?>" alt="동남아 3개국" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">동남아<br>3개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?region=usa-canada" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('usa-canada.svg'); ?>" alt="미국/캐나다" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">미국/<br>캐나다</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?region=australia-newzealand" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('australia-newzealand.svg'); ?>" alt="호주/뉴질랜드" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">호주/<br>뉴질랜드</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?region=asia-13" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('asia-13.svg'); ?>" alt="아시아 13개국" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">아시아<br>13개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?region=guam-saipan" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('guam-saipan.svg'); ?>" alt="괌/사이판" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">괌/사이판</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?region=north-central-america-3" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('north-central-america-3.svg'); ?>" alt="북중미 3개국" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">북중미<br>3개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?region=southeast-asia-8" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('southeast-asia-8.svg'); ?>" alt="동남아 8개국" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">동남아<br>8개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="/MVNO/esim/esim.php?region=south-america-11" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('south-america-11.png'); ?>" alt="남미 11개국" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">남미<br>11개국</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</main>

<style>
/* 해외이심 페이지 스타일 */
.esim-section {
    padding: var(--spacing-lg) var(--spacing-md);
    max-width: var(--max-width);
    margin: 0 auto;
}

/* 국가 상세 페이지 스타일 */
.esim-detail-section {
    padding: var(--spacing-md);
    max-width: var(--max-width);
    margin: 0 auto;
}

.esim-detail-header {
    display: flex;
    align-items: center;
    margin-bottom: var(--spacing-md);
}

.back-link {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    color: var(--color-gray-700);
    font-size: var(--font-size-base);
    text-decoration: none;
    transition: color 0.2s;
}

.back-link:hover {
    color: var(--color-primary);
}

.back-link svg {
    width: 24px;
    height: 24px;
}

/* 배너 섹션 */
.esim-banner-section {
    margin-bottom: var(--spacing-md);
}

.esim-banner-wrapper {
    position: relative;
    width: 100%;
    border-radius: 12px;
    overflow: hidden;
}

.esim-banner-image {
    width: 100%;
    height: auto;
    display: block;
    object-fit: cover;
}

.esim-banner-arrow {
    position: absolute;
    top: 50%;
    right: var(--spacing-md);
    transform: translateY(-50%);
    color: var(--color-white);
    background: rgba(0, 0, 0, 0.3);
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* 소셜 공유 섹션 */
.esim-social-section {
    display: flex;
    justify-content: flex-end;
    margin-bottom: var(--spacing-lg);
}

.esim-social-btn {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-xs) var(--spacing-sm);
    background: none;
    border: none;
    color: var(--color-gray-600);
    font-size: var(--font-size-sm);
    cursor: pointer;
    transition: color 0.2s;
}

.esim-social-btn:hover {
    color: var(--color-primary);
}

/* eSIM/USIM 탭 */
.esim-type-tabs {
    margin-bottom: var(--spacing-lg);
}

.esim-type-tabs-container {
    display: flex;
    background: var(--color-gray-200);
    border-radius: 12px;
    padding: 4px;
    gap: 0;
}

.esim-type-tab {
    flex: 1;
    padding: calc(var(--spacing-sm) * 2) var(--spacing-md);
    background: transparent;
    border: none;
    border-radius: 8px;
    color: var(--color-gray-600);
    font-size: var(--font-size-base);
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}

.esim-type-tab.active {
    background: var(--color-white);
    color: var(--color-black);
    font-weight: 900;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.esim-type-tab:hover:not(.active) {
    color: var(--color-gray-700);
}

/* 국가 정보 및 기간 선택 */
.esim-country-info-section {
    text-align: center;
    margin-bottom: var(--spacing-xl);
}

.esim-country-question {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-lg);
    line-height: 1.4;
}

.esim-duration-text {
    color: var(--color-primary);
}

.esim-duration-selector {
    display: flex;
    justify-content: center;
    gap: var(--spacing-sm);
}

.duration-btn {
    padding: var(--spacing-sm) var(--spacing-lg);
    background: var(--color-gray-100);
    border: 2px solid var(--color-gray-200);
    border-radius: 999px;
    color: var(--color-gray-600);
    font-size: var(--font-size-base);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.duration-btn:hover {
    background: var(--color-gray-200);
    border-color: var(--color-gray-300);
}

.duration-btn.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-white);
}

/* 기간 선택 모달 */
.esim-duration-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
}

.esim-duration-modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.esim-duration-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.esim-duration-modal-content {
    position: relative;
    background: var(--color-white);
    border-radius: 16px;
    width: 90%;
    max-width: 400px;
    max-height: 80vh;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    z-index: 1001;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.esim-duration-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--spacing-lg);
    border-bottom: 1px solid var(--color-gray-200);
}

.esim-duration-modal-title {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--color-gray-700);
    margin: 0;
}

.esim-duration-modal-close {
    background: none;
    border: none;
    padding: var(--spacing-xs);
    cursor: pointer;
    color: var(--color-gray-600);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s;
}

.esim-duration-modal-close:hover {
    color: var(--color-gray-700);
}

.esim-duration-modal-body {
    padding: var(--spacing-md);
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
    max-height: calc(80vh - 80px);
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

.esim-duration-modal-body::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

.esim-duration-option {
    width: 100%;
    padding: var(--spacing-md);
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: 8px;
    color: var(--color-gray-700);
    font-size: var(--font-size-base);
    font-weight: 500;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.esim-duration-option:hover {
    background: var(--color-gray-100);
    border-color: var(--color-primary);
}

.esim-duration-option:active {
    background: var(--color-primary);
    color: var(--color-white);
    border-color: var(--color-primary);
}

/* 프로모션 배너 */
.esim-promo-banner {
    background: #E3F2FD;
    border-radius: 12px;
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
    text-align: center;
}

.esim-promo-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-sm);
    color: var(--color-gray-700);
}

.esim-promo-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.esim-promo-text {
    font-size: var(--font-size-base);
    font-weight: 600;
}

/* Google Maps 섹션 */
.esim-google-maps-section {
    margin-bottom: var(--spacing-xl);
}

.esim-google-maps-card {
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: 12px;
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
}

.esim-google-maps-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-sm);
    justify-content: space-between;
}

.esim-google-maps-title {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--color-gray-700);
    flex: 1;
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.esim-google-maps-desc {
    font-size: var(--font-size-sm);
    color: var(--color-primary);
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 2px;
}

.esim-google-maps-desc:hover {
    text-decoration: underline;
}

.esim-arrow {
    display: inline-block;
}

.esim-google-maps-note {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    line-height: 1.5;
}

/* 추천 상품 섹션 */
.esim-recommended-section {
    margin-top: var(--spacing-2xl);
}

.esim-recommended-title {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-lg);
    text-align: center;
}

.esim-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--spacing-lg);
}

.esim-product-card {
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: 12px;
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
    transition: all 0.2s;
}

.esim-product-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.esim-product-header {
    margin-bottom: var(--spacing-md);
}

.esim-product-country {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.esim-country-dot {
    width: 8px;
    height: 8px;
    background-color: var(--color-red-500);
    border-radius: 50%;
    display: inline-block;
}

.esim-country-name {
    font-size: var(--font-size-base);
    font-weight: 600;
    color: var(--color-gray-700);
}

.esim-product-details {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-lg);
    padding-bottom: var(--spacing-md);
    border-bottom: 1px solid var(--color-gray-200);
}

.esim-product-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.esim-detail-label {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
}

.esim-detail-value {
    font-size: var(--font-size-sm);
    color: var(--color-gray-700);
    font-weight: 500;
}

.esim-price {
    color: var(--color-primary);
    font-weight: 700;
    font-size: var(--font-size-base);
}

.esim-product-actions {
    display: flex;
    gap: var(--spacing-sm);
}

.esim-btn-detail {
    flex: 1;
    padding: calc(var(--spacing-md) * 1.2) var(--spacing-md);
    background: var(--color-white);
    border: 1px solid var(--color-primary);
    border-radius: 8px;
    color: var(--color-primary);
    font-size: var(--font-size-base);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.esim-btn-detail:hover {
    background: var(--color-primary);
    color: var(--color-white);
}

.esim-btn-purchase {
    flex: 1;
    padding: calc(var(--spacing-md) * 1.2) var(--spacing-md);
    background: var(--color-primary);
    border: 1px solid var(--color-primary);
    border-radius: 8px;
    color: var(--color-white);
    font-size: var(--font-size-base);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.esim-btn-purchase:hover {
    background: var(--color-primary-dark);
    border-color: var(--color-primary-dark);
}

/* 데이터 요금제 섹션 */
.esim-plans-section {
    margin-top: var(--spacing-2xl);
}

.esim-network-tabs {
    display: flex;
    background: var(--color-gray-200);
    border-radius: 12px;
    padding: 4px;
    gap: 0;
    margin-bottom: var(--spacing-lg);
}

.esim-network-tab {
    flex: 1;
    padding: calc(var(--spacing-sm) * 1.5) var(--spacing-md);
    background: transparent;
    border: none;
    border-radius: 8px;
    color: var(--color-gray-600);
    font-size: var(--font-size-base);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.esim-network-tab.active {
    background: var(--color-primary);
    color: var(--color-white);
    font-weight: 700;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.esim-network-tab:hover:not(.active) {
    color: var(--color-gray-700);
}

.esim-plans-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.esim-plan-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-md);
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.esim-plan-item:hover {
    border-color: var(--color-primary);
    background: var(--color-gray-100);
}

.esim-plan-item.active {
    border-color: var(--color-primary);
    background: var(--color-primary);
}

.esim-plan-name {
    font-size: var(--font-size-base);
    color: var(--color-gray-700);
    font-weight: 500;
}

.esim-plan-item.active .esim-plan-name {
    color: var(--color-white);
    font-weight: 600;
}

.esim-plan-price {
    font-size: var(--font-size-base);
    color: var(--color-gray-700);
    font-weight: 600;
}

.esim-plan-item.active .esim-plan-price {
    color: var(--color-white);
    font-weight: 700;
}

/* 헤더 영역 */
.esim-header {
    margin-bottom: var(--spacing-xl);
}

.esim-title {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-lg);
    text-align: center;
}

.esim-controller {
    margin-bottom: var(--spacing-lg);
}

/* 탭 메뉴 */
.esim-tabs-wrapper {
    margin-bottom: var(--spacing-lg);
    display: inline-flex;
    width: 100%;
}

.esim-tabs-container {
    position: relative;
    display: inline-flex;
    border-radius: 10px;
    background-color: var(--color-gray-200);
    padding: 4px;
    width: 100%;
    max-width: 400px;
}

.esim-tabs-indicator {
    position: absolute;
    top: 4px;
    bottom: 4px;
    background-color: var(--color-white);
    border-radius: 6px;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease-in-out;
    width: calc(50% - 4px);
    left: 4px;
}

.esim-tab-item {
    position: relative;
    z-index: 10;
    flex: 1;
    height: 40px;
    background: none;
    border: none;
    cursor: pointer;
    transition: color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.esim-tab-item:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.esim-tab-text {
    font-size: var(--font-size-sm);
    font-weight: 500;
    color: var(--color-gray-500);
    transition: color 0.2s;
}

.esim-tab-item.active .esim-tab-text {
    color: var(--color-gray-700);
}

.esim-tab-item:not(.active) .esim-tab-text {
    color: var(--color-gray-500);
}

.esim-container {
    min-height: 400px;
}

.esim-tab-pane {
    display: none;
}

.esim-tab-pane.active {
    display: block;
}

/* 국가 그리드 */
.country-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: var(--spacing-md);
    justify-items: center;
    flex-wrap: wrap;
}

.country-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    transition: transform 0.2s;
    width: 100%;
    max-width: 120px;
    text-decoration: none;
    color: inherit;
    outline: none;
    border: none;
}

.country-item:hover {
    transform: translateY(-4px);
    text-decoration: none;
}

.country-item:focus,
.country-item:active {
    outline: none;
    border: none;
    box-shadow: none;
}

.product-flag-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    gap: var(--spacing-xs);
    position: relative;
}

.flag {
    width: 48px;
    height: 48px;
    object-fit: contain;
    border-radius: 50%;
}

.badge {
    position: absolute;
    top: -4px;
    right: 8px;
    background-color: #3b82f6;
    color: var(--color-white);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: var(--font-size-xs);
    font-weight: 700;
    z-index: 10;
    white-space: nowrap;
    line-height: 1.2;
}

.product-name-wrapper {
    display: flex;
    justify-content: center;
    margin-top: var(--spacing-xs);
}

.product_name {
    font-size: var(--font-size-sm);
    font-weight: 500;
    color: var(--color-gray-700);
    text-align: center;
    white-space: normal;
    line-height: 1.4;
}

/* 모바일 반응형 */
@media (max-width: 767px) {
    .esim-title {
        font-size: var(--font-size-xl);
        margin-bottom: var(--spacing-md);
    }
    
    .country-grid {
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: var(--spacing-sm);
    }
    
    .flag {
        width: 40px;
        height: 40px;
    }
    
    .product_name {
        font-size: var(--font-size-xs);
    }
    
    .esim-tabs-container {
        max-width: 100%;
    }
    
    .esim-tab-item {
        height: 36px;
    }
    
    .esim-tab-text {
        font-size: var(--font-size-xs);
    }

    /* 상세 페이지 모바일 스타일 */
    .esim-detail-section {
        padding: var(--spacing-sm);
    }

    .esim-country-question {
        font-size: var(--font-size-xl);
    }

    .duration-btn {
        padding: var(--spacing-xs) var(--spacing-md);
        font-size: var(--font-size-sm);
    }

    .esim-promo-text {
        font-size: var(--font-size-sm);
    }

    .esim-google-maps-card {
        padding: var(--spacing-md);
    }

    .esim-products-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-md);
    }

    .esim-type-tab {
        padding: var(--spacing-xs) var(--spacing-sm);
        font-size: var(--font-size-sm);
    }

    .esim-product-actions {
        flex-direction: column;
    }

    .esim-btn-detail,
    .esim-btn-purchase {
        width: 100%;
        padding: var(--spacing-md) var(--spacing-sm);
        min-height: 44px;
        font-size: var(--font-size-sm);
    }

    .esim-network-tabs {
        gap: var(--spacing-sm);
    }

    .esim-network-tab {
        padding: var(--spacing-xs) var(--spacing-sm);
        font-size: var(--font-size-sm);
    }

    .esim-plan-item {
        padding: var(--spacing-sm);
    }

    .esim-plan-name {
        font-size: var(--font-size-sm);
    }

    .esim-plan-price {
        font-size: var(--font-size-sm);
    }

    .esim-duration-modal-content {
        width: 95%;
        max-height: 85vh;
    }

    .esim-duration-modal-header {
        padding: var(--spacing-md);
    }

    .esim-duration-modal-title {
        font-size: var(--font-size-base);
    }

    .esim-duration-modal-body {
        padding: var(--spacing-sm);
        max-height: calc(85vh - 70px);
    }

    .esim-duration-option {
        padding: calc(var(--spacing-sm) * 1.5);
        font-size: var(--font-size-sm);
    }
}
</style>

<script>
// 탭 전환 기능
document.addEventListener('DOMContentLoaded', function() {
    const tabItems = document.querySelectorAll('.esim-tab-item');
    const tabPanes = document.querySelectorAll('.esim-tab-pane');
    const indicator = document.querySelector('.esim-tabs-indicator');
    const tabsContainer = document.querySelector('.esim-tabs-container');
    
    function updateIndicator(activeTab) {
        if (!indicator || !tabsContainer) return;
        
        const tabs = Array.from(tabItems);
        const activeIndex = tabs.indexOf(activeTab);
        const tabWidth = tabsContainer.offsetWidth / tabs.length;
        const indicatorWidth = tabWidth - 8; // padding 고려
        const leftPosition = activeIndex * tabWidth + 4; // padding 고려
        
        indicator.style.width = indicatorWidth + 'px';
        indicator.style.left = leftPosition + 'px';
    }
    
    // 초기 인디케이터 위치 설정
    const activeTab = document.querySelector('.esim-tab-item.active');
    if (activeTab) {
        updateIndicator(activeTab);
    }
    
    tabItems.forEach(item => {
        item.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // 모든 탭 비활성화
            tabItems.forEach(tab => {
                tab.classList.remove('active');
                tab.setAttribute('aria-selected', 'false');
            });
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // 선택한 탭 활성화
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            
            // 인디케이터 위치 업데이트
            updateIndicator(this);
            
            const targetPane = document.getElementById('tab-' + targetTab);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        });
    });
    
    // 윈도우 리사이즈 시 인디케이터 위치 재계산
    window.addEventListener('resize', function() {
        const activeTab = document.querySelector('.esim-tab-item.active');
        if (activeTab) {
            updateIndicator(activeTab);
        }
    });

    // 국가 상세 페이지 기능
    // 기간 선택 버튼
    const durationButtons = document.querySelectorAll('.duration-btn');
    const durationText = document.querySelector('.esim-duration-text');
    
    durationButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const days = this.getAttribute('data-days');
            
            // 기간 선택 버튼 클릭 시 모달 열기
            if (days === 'custom') {
                const modal = document.getElementById('durationModal');
                if (modal) {
                    modal.classList.add('show');
                    document.body.style.overflow = 'hidden'; // 스크롤 방지
                }
                return;
            }
            
            // 모든 버튼 비활성화
            durationButtons.forEach(b => b.classList.remove('active'));
            
            // 선택한 버튼 활성화
            this.classList.add('active');
            
            // 텍스트 업데이트
            if (durationText) {
                durationText.textContent = days + '일';
            }
        });
    });

    // 기간 선택 모달 기능
    const durationModal = document.getElementById('durationModal');
    if (durationModal) {
        const modalOverlay = durationModal.querySelector('.esim-duration-modal-overlay');
        const modalClose = durationModal.querySelector('.esim-duration-modal-close');
        const durationOptions = durationModal.querySelectorAll('.esim-duration-option');
        
        // 모달 닫기 함수
        function closeModal() {
            durationModal.classList.remove('show');
            document.body.style.overflow = ''; // 스크롤 복원
        }
        
        // 오버레이 클릭 시 닫기
        if (modalOverlay) {
            modalOverlay.addEventListener('click', closeModal);
        }
        
        // 닫기 버튼 클릭 시 닫기
        if (modalClose) {
            modalClose.addEventListener('click', closeModal);
        }
        
        // ESC 키로 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && durationModal.classList.contains('show')) {
                closeModal();
            }
        });
        
        // 기간 옵션 선택
        durationOptions.forEach(option => {
            option.addEventListener('click', function() {
                const selectedDays = this.getAttribute('data-days');
                
                // 모든 버튼 비활성화
                durationButtons.forEach(b => b.classList.remove('active'));
                
                // 기간 선택 버튼에 active 클래스 추가 (선택된 날짜 표시용)
                const customBtn = document.querySelector('.duration-btn[data-days="custom"]');
                if (customBtn) {
                    customBtn.classList.add('active');
                    customBtn.textContent = selectedDays + '일';
                }
                
                // 텍스트 업데이트
                if (durationText) {
                    durationText.textContent = selectedDays + '일';
                }
                
                // 모달 닫기
                closeModal();
            });
        });
    }

    // eSIM/USIM 탭 전환
    const esimTypeTabs = document.querySelectorAll('.esim-type-tab');
    
    esimTypeTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            
            // 모든 탭 비활성화
            esimTypeTabs.forEach(t => t.classList.remove('active'));
            
            // 선택한 탭 활성화
            this.classList.add('active');
            
            // 여기에 타입에 따른 상품 필터링 로직 추가 가능
            console.log('Selected type:', type);
        });
    });

    // 로밍망/로컬망 탭 전환
    const networkTabs = document.querySelectorAll('.esim-network-tab');
    const planGroups = document.querySelectorAll('.esim-plan-group');
    
    networkTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const network = this.getAttribute('data-network');
            
            // 모든 탭 비활성화
            networkTabs.forEach(t => t.classList.remove('active'));
            
            // 선택한 탭 활성화
            this.classList.add('active');
            
            // 해당 네트워크의 요금제 그룹만 표시
            planGroups.forEach(group => {
                if (group.getAttribute('data-network') === network) {
                    group.style.display = 'flex';
                    group.style.flexDirection = 'column';
                    group.style.gap = 'var(--spacing-sm)';
                } else {
                    group.style.display = 'none';
                }
            });
            
            // 첫 번째 요금제를 활성화
            const activeGroup = document.querySelector(`.esim-plan-group[data-network="${network}"]`);
            if (activeGroup) {
                const firstPlan = activeGroup.querySelector('.esim-plan-item');
                if (firstPlan) {
                    // 모든 요금제 비활성화
                    document.querySelectorAll('.esim-plan-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    // 첫 번째 요금제 활성화
                    firstPlan.classList.add('active');
                }
            }
        });
    });

    // 요금제 선택
    const planItems = document.querySelectorAll('.esim-plan-item');
    
    planItems.forEach(item => {
        item.addEventListener('click', function() {
            // 모든 요금제 비활성화
            planItems.forEach(p => p.classList.remove('active'));
            
            // 선택한 요금제 활성화
            this.classList.add('active');
            
            // 선택한 요금제 정보 가져오기
            const planName = this.querySelector('.esim-plan-name').textContent;
            const planPrice = this.querySelector('.esim-plan-price').textContent;
            
            // 추천 상품 카드 업데이트 (선택사항)
            const productCard = document.querySelector('.esim-product-card');
            if (productCard) {
                const priceElement = productCard.querySelector('.esim-price');
                if (priceElement) {
                    priceElement.textContent = planPrice;
                }
            }
            
            console.log('Selected plan:', planName, planPrice);
        });
    });

    // 소셜 공유 기능은 share.js에서 자동으로 처리됩니다 (data-share-url 속성 사용)
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>
