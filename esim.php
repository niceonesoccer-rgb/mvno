<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'esim';

<<<<<<< HEAD
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

// 이미지 경로 헬퍼 함수
function getCountryImageUrl($filename, $externalUrl) {
    $localPath = __DIR__ . '/images/country/' . $filename;
    if (file_exists($localPath)) {
        return 'images/country/' . $filename;
    }
    return $externalUrl;
}

// 외부 이미지 URL 매핑
$external_images = [
    'japan.svg' => 'https://asset.usimsa.com/images/country/179d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'china.svg' => 'https://asset.usimsa.com/images/country/1b9d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'taiwan.svg' => 'https://asset.usimsa.com/images/country/189d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'philippines.svg' => 'https://asset.usimsa.com/images/country/1f9d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'thailand.svg' => 'https://asset.usimsa.com/images/country/199d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'vietnam.svg' => 'https://asset.usimsa.com/images/country/1a9d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'malaysia.svg' => 'https://asset.usimsa.com/images/country/1e9d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'singapore.svg' => 'https://asset.usimsa.com/images/country/1d9d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'usa.svg' => 'https://asset.usimsa.com/images/country/1c9d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'australia.svg' => 'https://asset.usimsa.com/images/country/2a9d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'indonesia.svg' => 'https://asset.usimsa.com/images/country/239d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'uae.svg' => 'https://asset.usimsa.com/images/country/289d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'hongkong.svg' => 'https://asset.usimsa.com/images/country/219d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'guam.svg' => 'https://asset.usimsa.com/images/country/209d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'canada.svg' => 'https://asset.usimsa.com/images/country/b3578299-836e-ee11-bbf0-28187860d6d3.svg',
    'cambodia.svg' => 'https://asset.usimsa.com/images/country/bd578299-836e-ee11-bbf0-28187860d6d3.svg',
    'italy.svg' => 'https://asset.usimsa.com/images/country/299d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'macau.svg' => 'https://asset.usimsa.com/images/country/229d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'france.svg' => 'https://asset.usimsa.com/images/country/269d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'spain.svg' => 'https://asset.usimsa.com/images/country/b4578299-836e-ee11-bbf0-28187860d6d3.svg',
    'turkey.svg' => 'https://asset.usimsa.com/images/country/d68aecfe-93db-ee11-85f9-002248f774ee.svg',
    'uk.svg' => 'https://asset.usimsa.com/images/country/279d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'germany.svg' => 'https://asset.usimsa.com/images/country/259d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'qatar.svg' => 'https://asset.usimsa.com/images/country/bb578299-836e-ee11-bbf0-28187860d6d3.svg',
    'portugal.svg' => 'https://asset.usimsa.com/images/country/b5578299-836e-ee11-bbf0-28187860d6d3.svg',
    'india.svg' => 'https://asset.usimsa.com/images/country/bf578299-836e-ee11-bbf0-28187860d6d3.svg',
    'mexico.svg' => 'https://asset.usimsa.com/images/country/be578299-836e-ee11-bbf0-28187860d6d3.svg',
    'laos.svg' => 'https://asset.usimsa.com/images/country/b83e155f-8bae-ee11-be9e-002248f7dbdd.svg',
    'southkorea.svg' => 'https://asset.usimsa.com/images/country/249d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'denmark.svg' => 'https://asset.usimsa.com/images/country/b8578299-836e-ee11-bbf0-28187860d6d3.svg',
    'maldives.svg' => 'https://asset.usimsa.com/images/country/1aa267d1-99aa-ee11-be9e-002248f7dbdd.svg',
    'sweden.svg' => 'https://asset.usimsa.com/images/country/b7578299-836e-ee11-bbf0-28187860d6d3.svg',
    'austria.svg' => 'https://asset.usimsa.com/images/country/b9578299-836e-ee11-bbf0-28187860d6d3.svg',
    'newzealand.svg' => 'https://asset.usimsa.com/images/country/a2472a10-7bdb-ee11-85f9-002248f774ee.svg',
    'ireland.svg' => 'https://asset.usimsa.com/images/country/b6578299-836e-ee11-bbf0-28187860d6d3.svg',
    'mongolia.svg' => 'https://asset.usimsa.com/images/country/b2578299-836e-ee11-bbf0-28187860d6d3.svg',
    'kazakhstan.svg' => 'https://asset.usimsa.com/images/country/D732E6AD-D4F4-EF11-90CB-6045BD4556A6.svg',
    'global-151.svg' => 'https://asset.usimsa.com/crm/country/ABBB2654-909A-F011-B3CD-002248F7DF6B-1758892918652.svg',
    'europe-42.svg' => 'https://asset.usimsa.com/images/country/b4c9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'europe-36.svg' => 'https://asset.usimsa.com/images/country/4086995C-1709-F011-AAA7-002248F7D829.svg',
    'europe-33.svg' => 'https://asset.usimsa.com/images/country/b3c9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'hongkong-macau.svg' => 'https://asset.usimsa.com/images/country/b1c9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'china-hongkong-macau.svg' => 'https://asset.usimsa.com/images/country/352ADA47-CA02-F011-AAA7-002248F7D829.svg',
    'southeast-asia-3.svg' => 'https://asset.usimsa.com/images/country/afc9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'usa-canada.svg' => 'https://asset.usimsa.com/images/country/b0c9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'australia-newzealand.svg' => 'https://asset.usimsa.com/images/country/b2c9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'asia-13.svg' => 'https://asset.usimsa.com/images/country/afc9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'guam-saipan.svg' => 'https://asset.usimsa.com/images/country/b6c9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'north-central-america-3.svg' => 'https://asset.usimsa.com/images/country/b0c9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'southeast-asia-8.svg' => 'https://asset.usimsa.com/images/country/afc9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'south-america-11.png' => 'https://asset.usimsa.com/images/country/9788CAF5-48C3-EF11-88CF-002248F770DC.png',
];

// 쿼리 파라미터 확인
$selected_country = isset($_GET['country']) ? $_GET['country'] : null;
$selected_region = isset($_GET['region']) ? $_GET['region'] : null;

=======
>>>>>>> a0361c0 (작업: 11300858)
// 헤더 포함
include 'includes/header.php';
?>

<main class="main-content">
<<<<<<< HEAD
    <div class="content-layout">
        <?php if ($selected_country && isset($countries[$selected_country])): ?>
            <!-- 국가 상세 페이지 -->
            <?php $country = $countries[$selected_country]; ?>
            <section class="esim-detail-section">
                <div class="esim-detail-header">
                    <a href="esim.php" class="back-link">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                            <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>목록으로</span>
                    </a>
                    <h1 class="esim-detail-title"><?php echo htmlspecialchars($country['name']); ?> eSIM</h1>
                </div>
                <div class="esim-detail-content">
                    <div class="country-flag-large">
                        <img src="<?php echo getCountryImageUrl($country['image'], isset($external_images[$country['image']]) ? $external_images[$country['image']] : ''); ?>" alt="<?php echo htmlspecialchars($country['name']); ?>" class="flag-large">
                        <?php if (isset($country['badge'])): ?>
                            <div class="badge-large"><?php echo htmlspecialchars($country['badge']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="country-info">
                        <h2><?php echo htmlspecialchars($country['name']); ?> 해외이심 상품</h2>
                        <p>다양한 요금제를 확인하고 선택해보세요.</p>
                    </div>
                </div>
            </section>
        <?php elseif ($selected_region && isset($regions[$selected_region])): ?>
            <!-- 지역 상세 페이지 -->
            <?php $region = $regions[$selected_region]; ?>
            <section class="esim-detail-section">
                <div class="esim-detail-header">
                    <a href="esim.php" class="back-link">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                            <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>목록으로</span>
                    </a>
                    <h1 class="esim-detail-title"><?php echo htmlspecialchars($region['name']); ?> eSIM</h1>
                </div>
                <div class="esim-detail-content">
                    <div class="country-flag-large">
                        <img src="<?php echo getCountryImageUrl($region['image'], isset($external_images[$region['image']]) ? $external_images[$region['image']] : ''); ?>" alt="<?php echo htmlspecialchars($region['name']); ?>" class="flag-large">
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
=======
    <div class="mx-auto max-w-6xl p-5 text-txt-01">
        <article class="mt-5 flex items-center justify-between">
            <h1 class="text-18 md:text-30">해외eSIM</h1>
        </article>
        
        <!-- 탭 메뉴 -->
        <div class="c-tabmenu-link-wrap mt-8">
            <div class="c-tabmenu-list">
                <ul class="tab-list">
                    <li class="tab-item is-active">
                        <button role="tab" aria-selected="true" tabindex="0" class="tab-button" data-tab="popular">인기국가</button>
                    </li>
                    <li class="tab-item">
                        <button role="tab" aria-selected="false" tabindex="-1" class="tab-button" data-tab="multi">다국가</button>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- 인기국가 목록 -->
        <section id="popular-tab" class="country-list-section mt-8 tab-content active">
            <div class="container">
                <div class="tab grid gap-12 flex-wrap flex-justify-center">
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/179d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="badge">무제한</div>
                        <div class="flex flex-justify-center">
                            <span class="product_name">일본</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/1b9d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="badge">무제한</div>
                        <div class="flex flex-justify-center">
                            <span class="product_name">중국</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/189d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="badge">무제한</div>
                        <div class="flex flex-justify-center">
                            <span class="product_name">대만</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/1f9d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="badge">무제한</div>
                        <div class="flex flex-justify-center">
                            <span class="product_name">필리핀</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/199d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="badge">무제한</div>
                        <div class="flex flex-justify-center">
                            <span class="product_name">태국</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/1a9d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="badge">무제한</div>
                        <div class="flex flex-justify-center">
                            <span class="product_name">베트남</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/1e9d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="badge">무제한</div>
                        <div class="flex flex-justify-center">
                            <span class="product_name">말레이시아</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/1d9d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="badge">무제한</div>
                        <div class="flex flex-justify-center">
                            <span class="product_name">싱가포르</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/1c9d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="badge">무제한</div>
                        <div class="flex flex-justify-center">
                            <span class="product_name">미국</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/2a9d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="badge">무제한</div>
                        <div class="flex flex-justify-center">
                            <span class="product_name">호주</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/239d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="badge">무제한</div>
                        <div class="flex flex-justify-center">
                            <span class="product_name">인도네시아</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/289d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">아랍에미리트</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/219d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">홍콩</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/209d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">괌</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b3578299-836e-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">캐나다</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/bd578299-836e-ee11-bbf0-28187860d6d3.svg">
                        <div class="badge">무제한</div>
                        <div class="flex flex-justify-center">
                            <span class="product_name">캄보디아</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/299d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">이탈리아</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/229d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">마카오</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/269d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">프랑스</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b4578299-836e-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">스페인</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/d68aecfe-93db-ee11-85f9-002248f774ee.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">튀르키예(터키)</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/279d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">영국</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/259d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">독일</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/bb578299-836e-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">카타르</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b5578299-836e-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">포르투갈</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/bf578299-836e-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">인도</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/be578299-836e-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">멕시코</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b83e155f-8bae-ee11-be9e-002248f7dbdd.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">라오스</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/249d30e5-6a69-ee11-bbf0-28187860d6d3.svg">
                        <div class="badge">무제한</div>
                        <div class="flex flex-justify-center">
                            <span class="product_name">대한민국</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b8578299-836e-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">덴마크</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/1aa267d1-99aa-ee11-be9e-002248f7dbdd.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">몰디브</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b7578299-836e-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">스웨덴</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b9578299-836e-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">오스트리아</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/a2472a10-7bdb-ee11-85f9-002248f774ee.svg">
                        <div class="badge">무제한</div>
                        <div class="flex flex-justify-center">
                            <span class="product_name">뉴질랜드</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b6578299-836e-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">아일랜드</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b2578299-836e-ee11-bbf0-28187860d6d3.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">몽골</span>
                        </div>
                    </div>
                </div>
                <div class="item flex flex-column flex-align-center point-cursor">
                    <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                        <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/D732E6AD-D4F4-EF11-90CB-6045BD4556A6.svg">
                        <div class="flex flex-justify-center">
                            <span class="product_name">카자흐스탄</span>
>>>>>>> a0361c0 (작업: 11300858)
                        </div>
                    </div>
                </div>
            </div>
<<<<<<< HEAD

            <!-- 컨테이너 -->
            <div class="esim-container">
                <!-- 인기국가 탭 -->
                <div id="tab-popular" class="esim-tab-pane active">
                    <div class="country-grid">
                        <a href="esim.php?country=japan" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('japan.svg', $external_images['japan.svg']); ?>" alt="일본" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">일본</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=china" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('china.svg', $external_images['china.svg']); ?>" alt="중국" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">중국</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=taiwan" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('taiwan.svg', $external_images['taiwan.svg']); ?>" alt="대만" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">대만</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=philippines" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('philippines.svg', $external_images['philippines.svg']); ?>" alt="필리핀" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">필리핀</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=thailand" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('thailand.svg', $external_images['thailand.svg']); ?>" alt="태국" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">태국</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=vietnam" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('vietnam.svg', $external_images['vietnam.svg']); ?>" alt="베트남" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">베트남</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=malaysia" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('malaysia.svg', $external_images['malaysia.svg']); ?>" alt="말레이시아" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">말레이시아</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=singapore" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('singapore.svg', $external_images['singapore.svg']); ?>" alt="싱가포르" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">싱가포르</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=usa" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('usa.svg', $external_images['usa.svg']); ?>" alt="미국" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">미국</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=australia" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('australia.svg', $external_images['australia.svg']); ?>" alt="호주" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">호주</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=indonesia" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('indonesia.svg', $external_images['indonesia.svg']); ?>" alt="인도네시아" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">인도네시아</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=uae" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('uae.svg', $external_images['uae.svg']); ?>" alt="아랍에미리트" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">아랍에미리트</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=hongkong" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('hongkong.svg', $external_images['hongkong.svg']); ?>" alt="홍콩" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">홍콩</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=guam" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('guam.svg', $external_images['guam.svg']); ?>" alt="괌" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">괌</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=canada" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('canada.svg', $external_images['canada.svg']); ?>" alt="캐나다" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">캐나다</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=cambodia" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('cambodia.svg', $external_images['cambodia.svg']); ?>" alt="캄보디아" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">캄보디아</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=italy" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('italy.svg', $external_images['italy.svg']); ?>" alt="이탈리아" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">이탈리아</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=macau" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('macau.svg', $external_images['macau.svg']); ?>" alt="마카오" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">마카오</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=france" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('france.svg', $external_images['france.svg']); ?>" alt="프랑스" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">프랑스</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=spain" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('spain.svg', $external_images['spain.svg']); ?>" alt="스페인" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">스페인</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=turkey" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('turkey.svg', $external_images['turkey.svg']); ?>" alt="튀르키예(터키)" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">튀르키예(터키)</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=uk" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('uk.svg', $external_images['uk.svg']); ?>" alt="영국" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">영국</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=germany" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('germany.svg', $external_images['germany.svg']); ?>" alt="독일" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">독일</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=qatar" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('qatar.svg', $external_images['qatar.svg']); ?>" alt="카타르" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">카타르</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=portugal" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('portugal.svg', $external_images['portugal.svg']); ?>" alt="포르투갈" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">포르투갈</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=india" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('india.svg', $external_images['india.svg']); ?>" alt="인도" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">인도</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=mexico" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('mexico.svg', $external_images['mexico.svg']); ?>" alt="멕시코" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">멕시코</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=laos" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('laos.svg', $external_images['laos.svg']); ?>" alt="라오스" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">라오스</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=southkorea" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('southkorea.svg', $external_images['southkorea.svg']); ?>" alt="대한민국" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">대한민국</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=denmark" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('denmark.svg', $external_images['denmark.svg']); ?>" alt="덴마크" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">덴마크</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=maldives" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('maldives.svg', $external_images['maldives.svg']); ?>" alt="몰디브" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">몰디브</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=sweden" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('sweden.svg', $external_images['sweden.svg']); ?>" alt="스웨덴" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">스웨덴</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=austria" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('austria.svg', $external_images['austria.svg']); ?>" alt="오스트리아" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">오스트리아</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=newzealand" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('newzealand.svg', $external_images['newzealand.svg']); ?>" alt="뉴질랜드" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">뉴질랜드</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=ireland" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('ireland.svg', $external_images['ireland.svg']); ?>" alt="아일랜드" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">아일랜드</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=mongolia" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('mongolia.svg', $external_images['mongolia.svg']); ?>" alt="몽골" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">몽골</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?country=kazakhstan" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('kazakhstan.svg', $external_images['kazakhstan.svg']); ?>" alt="카자흐스탄" class="flag" width="48" height="48">
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
                        <a href="esim.php?region=global-151" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('global-151.svg', $external_images['global-151.svg']); ?>" alt="글로벌 151개국" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">글로벌<br>151개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?region=europe-42" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('europe-42.svg', $external_images['europe-42.svg']); ?>" alt="유럽 42개국" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">유럽<br>42개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?region=europe-36" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('europe-36.svg', $external_images['europe-36.svg']); ?>" alt="유럽 36개국" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">유럽<br>36개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?region=europe-33" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('europe-33.svg', $external_images['europe-33.svg']); ?>" alt="유럽 33개국" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">유럽<br>33개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?region=hongkong-macau" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('hongkong-macau.svg', $external_images['hongkong-macau.svg']); ?>" alt="홍콩/마카오" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">홍콩/<br>마카오</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?region=china-hongkong-macau" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('china-hongkong-macau.svg', $external_images['china-hongkong-macau.svg']); ?>" alt="중국/홍콩/마카오" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">중국/<br>홍콩/<br>마카오</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?region=southeast-asia-3" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('southeast-asia-3.svg', $external_images['southeast-asia-3.svg']); ?>" alt="동남아 3개국" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">동남아<br>3개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?region=usa-canada" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('usa-canada.svg', $external_images['usa-canada.svg']); ?>" alt="미국/캐나다" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">미국/<br>캐나다</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?region=australia-newzealand" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('australia-newzealand.svg', $external_images['australia-newzealand.svg']); ?>" alt="호주/뉴질랜드" class="flag" width="48" height="48">
                                <div class="badge">무제한</div>
                                <div class="product-name-wrapper">
                                    <span class="product_name">호주/<br>뉴질랜드</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?region=asia-13" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('asia-13.svg', $external_images['asia-13.svg']); ?>" alt="아시아 13개국" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">아시아<br>13개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?region=guam-saipan" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('guam-saipan.svg', $external_images['guam-saipan.svg']); ?>" alt="괌/사이판" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">괌/사이판</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?region=north-central-america-3" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('north-central-america-3.svg', $external_images['north-central-america-3.svg']); ?>" alt="북중미 3개국" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">북중미<br>3개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?region=southeast-asia-8" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('southeast-asia-8.svg', $external_images['southeast-asia-8.svg']); ?>" alt="동남아 8개국" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">동남아<br>8개국</span>
                                </div>
                            </div>
                        </a>
                        <a href="esim.php?region=south-america-11" class="country-item">
                            <div class="product-flag-item">
                                <img src="<?php echo getCountryImageUrl('south-america-11.png', $external_images['south-america-11.png']); ?>" alt="남미 11개국" class="flag" width="48" height="48">
                                <div class="product-name-wrapper">
                                    <span class="product_name">남미<br>11개국</span>
                                </div>
                            </div>
                        </a>
=======
        </section>
        
        <!-- 다국가 목록 -->
        <section id="multi-tab" class="country-list-section mt-8 tab-content">
            <div class="container">
                <div class="tab grid gap-12 flex-wrap flex-justify-center">
                    <div class="item flex flex-column flex-align-center point-cursor">
                        <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                            <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/crm/country/ABBB2654-909A-F011-B3CD-002248F7DF6B-1758892918652.svg">
                            <div class="flex flex-justify-center">
                                <span class="product_name">글로벌<br>151개국</span>
                            </div>
                        </div>
                    </div>
                    <div class="item flex flex-column flex-align-center point-cursor">
                        <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                            <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b4c9012b-7f69-ee11-bbf0-28187860d6d3.svg">
                            <div class="badge">무제한</div>
                            <div class="flex flex-justify-center">
                                <span class="product_name">유럽<br>42개국</span>
                            </div>
                        </div>
                    </div>
                    <div class="item flex flex-column flex-align-center point-cursor">
                        <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                            <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/4086995C-1709-F011-AAA7-002248F7D829.svg">
                            <div class="badge">무제한</div>
                            <div class="flex flex-justify-center">
                                <span class="product_name">유럽<br>36개국</span>
                            </div>
                        </div>
                    </div>
                    <div class="item flex flex-column flex-align-center point-cursor">
                        <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                            <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b3c9012b-7f69-ee11-bbf0-28187860d6d3.svg">
                            <div class="badge">무제한</div>
                            <div class="flex flex-justify-center">
                                <span class="product_name">유럽<br>33개국</span>
                            </div>
                        </div>
                    </div>
                    <div class="item flex flex-column flex-align-center point-cursor">
                        <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                            <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b1c9012b-7f69-ee11-bbf0-28187860d6d3.svg">
                            <div class="badge">무제한</div>
                            <div class="flex flex-justify-center">
                                <span class="product_name">홍콩/<br>마카오</span>
                            </div>
                        </div>
                    </div>
                    <div class="item flex flex-column flex-align-center point-cursor">
                        <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                            <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/352ADA47-CA02-F011-AAA7-002248F7D829.svg">
                            <div class="badge">무제한</div>
                            <div class="flex flex-justify-center">
                                <span class="product_name">중국/<br>홍콩/<br>마카오</span>
                            </div>
                        </div>
                    </div>
                    <div class="item flex flex-column flex-align-center point-cursor">
                        <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                            <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/afc9012b-7f69-ee11-bbf0-28187860d6d3.svg">
                            <div class="flex flex-justify-center">
                                <span class="product_name">동남아<br>3개국</span>
                            </div>
                        </div>
                    </div>
                    <div class="item flex flex-column flex-align-center point-cursor">
                        <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                            <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b0c9012b-7f69-ee11-bbf0-28187860d6d3.svg">
                            <div class="flex flex-justify-center">
                                <span class="product_name">미국/<br>캐나다</span>
                            </div>
                        </div>
                    </div>
                    <div class="item flex flex-column flex-align-center point-cursor">
                        <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                            <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b2c9012b-7f69-ee11-bbf0-28187860d6d3.svg">
                            <div class="badge">무제한</div>
                            <div class="flex flex-justify-center">
                                <span class="product_name">호주/<br>뉴질랜드</span>
                            </div>
                        </div>
                    </div>
                    <div class="item flex flex-column flex-align-center point-cursor">
                        <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                            <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/afc9012b-7f69-ee11-bbf0-28187860d6d3.svg">
                            <div class="flex flex-justify-center">
                                <span class="product_name">아시아<br>13개국</span>
                            </div>
                        </div>
                    </div>
                    <div class="item flex flex-column flex-align-center point-cursor">
                        <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                            <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b6c9012b-7f69-ee11-bbf0-28187860d6d3.svg">
                            <div class="flex flex-justify-center">
                                <span class="product_name">괌/사이판</span>
                            </div>
                        </div>
                    </div>
                    <div class="item flex flex-column flex-align-center point-cursor">
                        <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                            <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/b0c9012b-7f69-ee11-bbf0-28187860d6d3.svg">
                            <div class="flex flex-justify-center">
                                <span class="product_name">북중미<br>3개국</span>
                            </div>
                        </div>
                    </div>
                    <div class="item flex flex-column flex-align-center point-cursor">
                        <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                            <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/afc9012b-7f69-ee11-bbf0-28187860d6d3.svg">
                            <div class="flex flex-justify-center">
                                <span class="product_name">동남아<br>8개국</span>
                            </div>
                        </div>
                    </div>
                    <div class="item flex flex-column flex-align-center point-cursor">
                        <div class="flex flex-column flex-align-center point-cursor product-flag-item">
                            <img width="48" height="48" alt="flag" class="flag" src="https://asset.usimsa.com/images/country/9788CAF5-48C3-EF11-88CF-002248F770DC.png">
                            <div class="flex flex-justify-center">
                                <span class="product_name">남미<br>11개국</span>
                            </div>
                        </div>
>>>>>>> a0361c0 (작업: 11300858)
                    </div>
                </div>
            </div>
        </section>
<<<<<<< HEAD
        <?php endif; ?>
=======
>>>>>>> a0361c0 (작업: 11300858)
    </div>
</main>

<style>
<<<<<<< HEAD
/* 해외이심 페이지 스타일 */
.esim-section {
    padding: var(--spacing-lg) var(--spacing-md);
    max-width: var(--max-width);
    margin: 0 auto;
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
}

.country-item:hover {
    transform: translateY(-4px);
    text-decoration: none;
}

.product-flag-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    gap: var(--spacing-xs);
=======
/* 기본 유틸리티 */
.mx-auto {
    margin-left: auto;
    margin-right: auto;
}

.max-w-6xl {
    max-width: 72rem;
}

.p-5 {
    padding: 1.25rem;
}

.text-txt-01 {
    color: #1a1a1a;
}

.mt-5 {
    margin-top: 1.25rem;
}

.mt-8 {
    margin-top: 2rem;
}

.flex {
    display: flex;
}

.flex-wrap {
    flex-wrap: wrap;
}

.flex-column {
    flex-direction: column;
}

.flex-align-center {
    align-items: center;
}

.flex-justify-center {
    justify-content: center;
}

.items-center {
    align-items: center;
}

.justify-between {
    justify-content: space-between;
}

.gap-12 {
    gap: 3rem;
}

.text-18 {
    font-size: 1.125rem;
    line-height: 1.75rem;
}

.grid {
    display: grid;
}

.point-cursor {
    cursor: pointer;
}

/* 탭 메뉴 스타일 */
.c-tabmenu-link-wrap {
    margin-bottom: 2rem;
}

.tab-list {
    display: flex;
    gap: 0.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
    flex-wrap: wrap;
}

.tab-item {
    margin: 0;
}

.tab-button {
    padding: 0.5rem 1.25rem;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    outline: none;
}

.tab-button:focus {
    outline: none;
    box-shadow: none;
}

.tab-button:active {
    outline: none;
    box-shadow: none;
}

.tab-button:hover {
    background-color: #f3f4f6;
    color: #374151;
}

.tab-item.is-active .tab-button {
    color: #ec4899;
    font-weight: 600;
    border: 1px solid #ec4899;
}

/* 탭 콘텐츠 */
.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* 국가 목록 스타일 */
.country-list-section {
    padding: 1rem 0;
}

.container {
    width: 100%;
}

.tab {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1.5rem;
    padding: 1rem 0;
}

.item {
    min-width: 0;
}

.product-flag-item {
    width: 100%;
    padding: 1rem;
    transition: all 0.2s ease;
}

.product-flag-item:hover {
    transform: translateY(-2px);
>>>>>>> a0361c0 (작업: 11300858)
}

.flag {
    width: 48px;
    height: 48px;
    object-fit: contain;
<<<<<<< HEAD
    border-radius: 4px;
}

.badge {
    background-color: var(--color-red-500);
    color: var(--color-white);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: var(--font-size-xs);
    font-weight: 700;
    margin-top: var(--spacing-xs);
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
=======
    margin-bottom: 0.5rem;
}

.badge {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6366f1;
    margin-bottom: 0.25rem;
}

.product_name {
    font-size: 0.875rem;
    color: #1a1a1a;
    text-align: center;
    line-height: 1.4;
    white-space: pre-line;
}

@media (min-width: 768px) {
    .md\:text-30 {
        font-size: 1.875rem;
        line-height: 2.25rem;
    }
    
    .tab {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 2rem;
    }
}

@media (min-width: 1024px) {
    .tab {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 3rem;
    }
}

@media (max-width: 767px) {
    .p-5 {
        padding: 1rem;
    }
    
    .tab-list {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    
    .tab-list::-webkit-scrollbar {
        display: none;
    }
    
    .tab-item {
        flex-shrink: 0;
    }
    
    .tab-button {
        padding: 0.625rem 1rem;
        font-size: 0.8125rem;
    }
    
    .tab {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 1rem;
    }
    
    .product-flag-item {
        padding: 0.75rem;
>>>>>>> a0361c0 (작업: 11300858)
    }
    
    .flag {
        width: 40px;
        height: 40px;
    }
<<<<<<< HEAD
    
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
=======
>>>>>>> a0361c0 (작업: 11300858)
}
</style>

<script>
<<<<<<< HEAD
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
=======
// 국가 상세 페이지 열기 함수
function openCountryDetail(countryName, flagImage) {
    const url = 'esim-detail.php?country=' + encodeURIComponent(countryName) + '&flag=' + encodeURIComponent(flagImage);
    window.open(url, '_blank', 'width=800,height=900,scrollbars=yes');
}

document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabItems = document.querySelectorAll('.tab-item');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // 탭 전환
    tabButtons.forEach((button, index) => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // 모든 탭에서 is-active 클래스 제거
            tabItems.forEach(item => {
                item.classList.remove('is-active');
            });
            
            // 클릭한 탭에 is-active 클래스 추가
            tabItems[index].classList.add('is-active');
            
            // aria-selected 속성 업데이트
            tabButtons.forEach((btn, i) => {
                btn.setAttribute('aria-selected', i === index ? 'true' : 'false');
                btn.setAttribute('tabindex', i === index ? '0' : '-1');
            });
            
            // 모든 탭 콘텐츠에서 active 클래스 제거
            tabContents.forEach(content => content.classList.remove('active'));
            
            // 해당하는 탭 콘텐츠에 active 클래스 추가
            const targetContent = document.getElementById(targetTab + '-tab');
            if (targetContent) {
                targetContent.classList.add('active');
>>>>>>> a0361c0 (작업: 11300858)
            }
        });
    });
    
<<<<<<< HEAD
    // 윈도우 리사이즈 시 인디케이터 위치 재계산
    window.addEventListener('resize', function() {
        const activeTab = document.querySelector('.esim-tab-item.active');
        if (activeTab) {
            updateIndicator(activeTab);
=======
    // 국가 항목 클릭 이벤트 (이벤트 위임)
    document.querySelectorAll('.product-flag-item').forEach(item => {
        const productNameEl = item.querySelector('.product_name');
        if (productNameEl) {
            const countryName = productNameEl.textContent.trim();
            const flagImg = item.querySelector('.flag');
            const flagSrc = flagImg ? flagImg.src : '';
            const flagId = flagSrc.match(/\/([^\/]+)\.svg$/);
            const flagImage = flagId ? flagId[1] : '';
            
            item.addEventListener('click', function(e) {
                e.preventDefault();
                openCountryDetail(countryName, flagImage);
            });
>>>>>>> a0361c0 (작업: 11300858)
        }
    });
});
</script>

<?php
// 푸터 포함
include 'includes/footer.php';
?>
