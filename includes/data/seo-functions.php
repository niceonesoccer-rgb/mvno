<?php
/**
 * SEO 관련 함수
 * - 검색엔진 검증 코드 관리
 * - 카테고리별 SEO 설정 관리
 * - 상품 SEO 메타 태그 자동 생성
 */

require_once __DIR__ . '/app-settings.php';
require_once __DIR__ . '/path-config.php';
require_once __DIR__ . '/site-settings.php';

/**
 * 검색엔진 검증 코드 가져오기
 * @return array
 */
function getSearchEngineVerification() {
    $defaults = [
        'google' => '',
        'naver' => '',
        'bing' => '',
        'yandex' => '',
        'head_codes' => '', // <head> 영역에 추가할 임의 코드
        'body_codes' => '', // <body> 시작 직후 추가할 임의 코드
        'footer_codes' => '', // </body> 앞에 추가할 임의 코드
    ];
    return getAppSettings('search_engine_verification', $defaults);
}

/**
 * 검색엔진 검증 코드 저장
 * @param array $codes
 * @return bool
 */
function saveSearchEngineVerification($codes) {
    $updatedBy = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
    return saveAppSettings('search_engine_verification', $codes, $updatedBy);
}

/**
 * 카테고리별 SEO 설정 가져오기
 * @return array
 */
function getCategorySEO() {
    $defaults = [
        'home' => [
            'title' => '',
            'description' => '',
            'keywords' => '',
            'og_title' => '',
            'og_description' => '',
            'og_image' => '',
            'canonical' => '',
        ],
        'mno-sim' => [
            'title' => '',
            'description' => '',
            'keywords' => '',
            'og_title' => '',
            'og_description' => '',
            'og_image' => '',
            'canonical' => '',
        ],
        'mvno' => [
            'title' => '',
            'description' => '',
            'keywords' => '',
            'og_title' => '',
            'og_description' => '',
            'og_image' => '',
            'canonical' => '',
        ],
        'mno' => [
            'title' => '',
            'description' => '',
            'keywords' => '',
            'og_title' => '',
            'og_description' => '',
            'og_image' => '',
            'canonical' => '',
        ],
        'internets' => [
            'title' => '',
            'description' => '',
            'keywords' => '',
            'og_title' => '',
            'og_description' => '',
            'og_image' => '',
            'canonical' => '',
        ],
    ];
    return getAppSettings('category_seo', $defaults);
}

/**
 * 카테고리별 SEO 설정 저장
 * @param array $seoSettings
 * @return bool
 */
function saveCategorySEO($seoSettings) {
    $updatedBy = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
    return saveAppSettings('category_seo', $seoSettings, $updatedBy);
}

/**
 * 현재 페이지의 카테고리 SEO 가져오기
 * @param string $currentPage 현재 페이지 식별자
 * @return array
 */
function getCurrentCategorySEO($currentPage = '') {
    $categorySEO = getCategorySEO();
    
    // 페이지 식별자에 따른 카테고리 매핑
    $categoryMap = [
        'home' => 'home',
        'mno-sim' => 'mno-sim',
        'mvno' => 'mvno',
        'plans' => 'mvno',
        'mno' => 'mno',
        'internets' => 'internets',
        'internet' => 'internets',
    ];
    
    $category = $categoryMap[$currentPage] ?? 'home';
    return $categorySEO[$category] ?? [
        'title' => '',
        'description' => '',
        'keywords' => '',
        'og_title' => '',
        'og_description' => '',
        'og_image' => '',
        'canonical' => '',
    ];
}

/**
 * 상품 SEO 메타 태그 자동 생성 (템플릿 기반)
 * 
 * 개인정보 보호: 이 함수는 개인정보(이메일, 전화번호, 주소 등)를 절대 포함하지 않습니다.
 * 판매자명, 상품명, 가격 등 공개 가능한 상품 정보만 사용합니다.
 * 
 * @param array $product 상품 데이터
 * @param string $productType 상품 타입 ('mvno', 'mno', 'internet', 'mno-sim')
 * @return array
 */
function generateProductSEO($product, $productType = 'mvno') {
    $siteSettings = getSiteSettings();
    $siteName = $siteSettings['site']['name_ko'] ?? '유심킹';
    $baseUrl = getBasePath();
    $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    // 상품 타입별 기본 정보 추출
    $title = '';
    $description = '';
    $keywords = [];
    $imageUrl = '';
    
    switch ($productType) {
        case 'mvno':
            $planName = $product['plan_name'] ?? '';
            $provider = $product['provider'] ?? '';
            $dataAmount = $product['data_amount'] ?? '';
            $price = $product['price_main'] ?? '';
            $priceAfter = $product['price_after'] ?? '';
            
            $title = $planName ? "{$planName} - {$provider} 요금제 | {$siteName}" : "{$provider} 요금제 | {$siteName}";
            $description = $planName ? "{$planName} {$provider} 요금제" : "{$provider} 요금제";
            if ($dataAmount) {
                $description .= " 데이터 {$dataAmount}";
            }
            if ($price) {
                $description .= " 월 {$price}원";
            }
            if ($priceAfter && $priceAfter !== $price) {
                $description .= " (할인 후 {$priceAfter}원)";
            }
            $description .= " | {$siteName}에서 신청하세요";
            
            $keywords = array_filter([$planName, $provider, '알뜰폰', '요금제', $dataAmount, $priceAfter ? "{$priceAfter}원" : null]);
            break;
            
        case 'mno':
            $deviceName = $product['device_name'] ?? '';
            $provider = $product['common_provider'] ?? '';
            $devicePrice = $product['device_price'] ?? '';
            $capacity = $product['device_capacity'] ?? '';
            
            $title = $deviceName ? "{$deviceName} - {$provider} 통신사폰 | {$siteName}" : "{$provider} 통신사폰 | {$siteName}";
            $description = $deviceName ? "{$deviceName} {$provider} 통신사폰" : "{$provider} 통신사폰";
            if ($capacity) {
                $description .= " {$capacity}";
            }
            if ($devicePrice) {
                $description .= " 출고가 {$devicePrice}원";
            }
            $description .= " | {$siteName}에서 신청하세요";
            
            $keywords = array_filter([$deviceName, $provider, '통신사폰', '스마트폰', $capacity, $devicePrice ? "{$devicePrice}원" : null]);
            break;
            
        case 'mno-sim':
            $planName = $product['plan_name'] ?? '';
            $provider = $product['provider'] ?? '';
            $dataAmount = $product['data_amount'] ?? '';
            $price = $product['price_main'] ?? '';
            
            $title = $planName ? "{$planName} - {$provider} 단독유심 | {$siteName}" : "{$provider} 단독유심 | {$siteName}";
            $description = $planName ? "{$planName} {$provider} 단독유심" : "{$provider} 단독유심";
            if ($dataAmount) {
                $description .= " 데이터 {$dataAmount}";
            }
            if ($price) {
                $description .= " 월 {$price}원";
            }
            $description .= " | {$siteName}에서 신청하세요";
            
            $keywords = array_filter([$planName, $provider, '단독유심', '유심', '요금제', $dataAmount, $price ? "{$price}원" : null]);
            break;
            
        case 'internet':
        case 'internets':
            $registrationPlace = $product['registration_place'] ?? '';
            $serviceType = $product['service_type'] ?? '인터넷';
            $speedOption = $product['speed_option'] ?? '';
            $monthlyFee = $product['monthly_fee'] ?? '';
            
            $title = $registrationPlace ? "{$registrationPlace} - {$serviceType} 요금제 | {$siteName}" : "{$serviceType} 요금제 | {$siteName}";
            $description = $registrationPlace ? "{$registrationPlace} {$serviceType} 요금제" : "{$serviceType} 요금제";
            if ($speedOption) {
                $description .= " 속도 {$speedOption}";
            }
            if ($monthlyFee) {
                $description .= " 월 {$monthlyFee}원";
            }
            $description .= " | {$siteName}에서 신청하세요";
            
            $keywords = array_filter([$registrationPlace, $serviceType, '인터넷', '요금제', $speedOption, $monthlyFee]);
            break;
    }
    
    // 기본값 설정
    if (empty($title)) {
        $title = "{$siteName} - 알뜰폰 요금제";
    }
    if (empty($description)) {
        $description = "{$siteName}에서 알뜰폰 요금제를 비교하고 신청하세요";
    }
    if (empty($keywords)) {
        $keywords = [$siteName, '알뜰폰', '요금제'];
    }
    
    // OG 이미지 (로고 또는 기본 이미지)
    $logo = $siteSettings['site']['logo'] ?? '';
    if ($logo) {
        // 로고 경로 정규화
        if (strpos($logo, '/') === 0) {
            $imageUrl = getBasePath() . $logo;
        } elseif (!preg_match('/^https?:\/\//', $logo)) {
            $imageUrl = getBasePath() . '/' . $logo;
        } else {
            $imageUrl = $logo;
        }
    }
    
    // Canonical URL
    $canonicalUrl = $currentUrl;
    
    return [
        'title' => $title,
        'description' => $description,
        'keywords' => implode(', ', $keywords),
        'og_title' => $title,
        'og_description' => $description,
        'og_image' => $imageUrl,
        'og_url' => $canonicalUrl,
        'og_type' => 'website',
        'canonical' => $canonicalUrl,
    ];
}

/**
 * SEO 메타 태그 HTML 생성
 * @param array $seo SEO 데이터
 * @return string
 */
function generateSEOMetaTags($seo) {
    $html = '';
    
    // Title
    if (!empty($seo['title'])) {
        $html .= '<title>' . htmlspecialchars($seo['title']) . '</title>' . "\n    ";
    }
    
    // Meta Description
    if (!empty($seo['description'])) {
        $html .= '<meta name="description" content="' . htmlspecialchars($seo['description']) . '">' . "\n    ";
    }
    
    // Meta Keywords
    if (!empty($seo['keywords'])) {
        $html .= '<meta name="keywords" content="' . htmlspecialchars($seo['keywords']) . '">' . "\n    ";
    }
    
    // Open Graph
    if (!empty($seo['og_title'])) {
        $html .= '<meta property="og:title" content="' . htmlspecialchars($seo['og_title']) . '">' . "\n    ";
    }
    if (!empty($seo['og_description'])) {
        $html .= '<meta property="og:description" content="' . htmlspecialchars($seo['og_description']) . '">' . "\n    ";
    }
    if (!empty($seo['og_image'])) {
        $html .= '<meta property="og:image" content="' . htmlspecialchars($seo['og_image']) . '">' . "\n    ";
    }
    if (!empty($seo['og_url'])) {
        $html .= '<meta property="og:url" content="' . htmlspecialchars($seo['og_url']) . '">' . "\n    ";
    }
    if (!empty($seo['og_type'])) {
        $html .= '<meta property="og:type" content="' . htmlspecialchars($seo['og_type']) . '">' . "\n    ";
    }
    
    // Canonical
    if (!empty($seo['canonical'])) {
        $html .= '<link rel="canonical" href="' . htmlspecialchars($seo['canonical']) . '">' . "\n    ";
    }
    
    return trim($html);
}
