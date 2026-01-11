<?php
/**
 * 상품 상세 페이지 SEO 메타 태그 생성 컴포넌트
 * 
 * 사용법:
 * include __DIR__ . '/product-seo.php';
 * 
 * 또는:
 * $productSEO = generateProductSEO($productData, $productType);
 * 
 * 이 파일을 상품 상세 페이지에서 header.php include 전에 호출하여
 * $productSEO 변수를 설정하면 header.php에서 자동으로 SEO 메타 태그가 추가됩니다.
 */

if (!isset($productSEO) && (isset($plan) || isset($product) || isset($phone) || isset($internet))) {
    require_once __DIR__ . '/../data/seo-functions.php';
    
    $productData = [];
    $productType = 'mvno';
    
    // 상품 데이터 및 타입 추출
    if (isset($plan)) {
        $productData = $plan;
        $productType = $current_page ?? 'mvno';
        if ($productType === 'plans') {
            $productType = 'mvno';
        }
    } elseif (isset($phone)) {
        $productData = $phone;
        $productType = 'mno';
    } elseif (isset($internet)) {
        $productData = $internet;
        $productType = 'internet';
    } elseif (isset($product)) {
        $productData = $product;
        $productType = $product['product_type'] ?? 'mvno';
    }
    
    // SEO 메타 태그 생성
    if (!empty($productData)) {
        $productSEO = generateProductSEO($productData, $productType);
    }
}
