<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mno';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 모니터링 시스템 (선택사항 - 주석 해제하여 사용)
// require_once 'includes/monitor.php';
// $monitor = new ConnectionMonitor();
// $monitor->logConnection();

// 경로 설정 파일 먼저 로드
require_once '../includes/data/path-config.php';

// 헤더 포함
include '../includes/header.php';

// 통신사폰 데이터 가져오기
require_once '../includes/data/phone-data.php';
require_once '../includes/data/filter-data.php';
require_once __DIR__ . '/mno-advertisement-helper.php';

// 필터 파라미터 (스폰서 상품 필터링 전에 먼저 설정)
// 기기 타입: 전체/갤럭시/아이폰 중 하나만 선택 가능
$filterDeviceType = $_GET['device_type'] ?? ''; // 갤럭시, 아이폰 (단일 값)

// 용량: 256GB/512GB/1TB 중 하나만 선택 가능
$filterStorage = $_GET['storage'] ?? ''; // 256GB, 512GB, 1TB (단일 값)

$filterFree = isset($_GET['free']) && $_GET['free'] === '1'; // 공짜

// 광고 상품 ID 조회
list($advertisementProductIds, $rotationDuration) = getMnoAdvertisementProductIds();

// 필터 적용: 스폰서 상품도 필터 조건에 맞는 것만 표시
if (!empty($filterDeviceType) || !empty($filterStorage) || $filterFree) {
    require_once __DIR__ . '/../includes/data/db-config.php';
    $pdo = getDBConnection();
    
    if ($pdo && !empty($advertisementProductIds)) {
        $placeholders = implode(',', array_fill(0, count($advertisementProductIds), '?'));
        $adFilterStmt = $pdo->prepare("
            SELECT 
                p.id,
                mno.device_name,
                mno.device_capacity,
                mno.common_discount_new,
                mno.common_discount_port,
                mno.common_discount_change,
                mno.contract_discount_new,
                mno.contract_discount_port,
                mno.contract_discount_change
            FROM products p
            INNER JOIN product_mno_details mno ON p.id = mno.product_id
            WHERE p.id IN ($placeholders)
            AND p.status = 'active'
        ");
        $adFilterStmt->execute($advertisementProductIds);
        $adProductsData = $adFilterStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filteredAdvertisementProductIds = [];
        foreach ($adProductsData as $adProduct) {
            $matches = true;
            
            // device_type 필터
            if ($matches && !empty($filterDeviceType)) {
                $deviceName = $adProduct['device_name'] ?? '';
                if ($filterDeviceType === '갤럭시' || $filterDeviceType === 'galaxy') {
                    if (stripos($deviceName, '갤럭시') === false && stripos($deviceName, 'Galaxy') === false && stripos($deviceName, 'galaxy') === false) {
                        $matches = false;
                    }
                } elseif ($filterDeviceType === '아이폰' || $filterDeviceType === 'iphone') {
                    if (stripos($deviceName, '아이폰') === false && stripos($deviceName, 'iPhone') === false && stripos($deviceName, 'iphone') === false) {
                        $matches = false;
                    }
                }
            }
            
            // storage 필터
            if ($matches && !empty($filterStorage)) {
                $deviceCapacity = $adProduct['device_capacity'] ?? '';
                if ($deviceCapacity !== $filterStorage) {
                    $matches = false;
                }
            }
            
            // free 필터
            if ($matches && $filterFree) {
                $hasFree = false;
                $discountFields = [
                    $adProduct['common_discount_new'] ?? '',
                    $adProduct['common_discount_port'] ?? '',
                    $adProduct['common_discount_change'] ?? '',
                    $adProduct['contract_discount_new'] ?? '',
                    $adProduct['contract_discount_port'] ?? '',
                    $adProduct['contract_discount_change'] ?? ''
                ];
                foreach ($discountFields as $discountField) {
                    if (strpos($discountField, '-') !== false || strpos($discountField, '택배') !== false) {
                        $hasFree = true;
                        break;
                    }
                }
                if (!$hasFree) {
                    $matches = false;
                }
            }
            
            if ($matches) {
                $filteredAdvertisementProductIds[] = (int)$adProduct['id'];
            }
        }
        
        // 로테이션 순서 유지: 원본 ID 목록 순서대로 필터링된 ID만 남기기
        $filteredIdsSet = array_flip($filteredAdvertisementProductIds);
        $advertisementProductIds = array_values(array_filter($advertisementProductIds, function($id) use ($filteredIdsSet) {
            return isset($filteredIdsSet[$id]);
        }));
        
        error_log("[MNO 페이지] 필터 적용 후 스폰서 상품 ID 개수: " . count($advertisementProductIds) . ", ID 목록: " . implode(', ', $advertisementProductIds));
    }
}

// 페이지 번호 가져오기
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// 원본 DB 데이터를 직접 가져와서 필터링 (변환 전에 필터링)
require_once __DIR__ . '/../includes/data/db-config.php';
$pdo = getDBConnection();

$allProducts = [];
$totalCount = 0;

if ($pdo) {
    // WHERE 조건 구성
    $whereConditions = ["p.product_type = 'mno'", "p.status = 'active'"];
    $params = [];
    
    // 광고 상품은 제외하지 않음 (나중에 PHP에서 분리)
    
    // 기기 타입 필터 (갤럭시, 아이폰) - 하나만 선택 가능
    if (!empty($filterDeviceType)) {
        if ($filterDeviceType === '갤럭시' || $filterDeviceType === 'galaxy') {
            $whereConditions[] = "(mno.device_name LIKE '%갤럭시%' OR mno.device_name LIKE '%Galaxy%' OR mno.device_name LIKE '%galaxy%')";
        } elseif ($filterDeviceType === '아이폰' || $filterDeviceType === 'iphone') {
            $whereConditions[] = "(mno.device_name LIKE '%아이폰%' OR mno.device_name LIKE '%iPhone%' OR mno.device_name LIKE '%iphone%')";
        }
    }
    
    // 용량 필터 - 하나만 선택 가능
    if (!empty($filterStorage)) {
        $whereConditions[] = "mno.device_capacity = ?";
        $params[] = $filterStorage;
    }
    
    // 공짜 필터 (정책에 - 값이 있는 것, 예: -10, 택배)
    if ($filterFree) {
        // JSON 필드에서 음수 값이나 "택배" 같은 텍스트 확인
        $whereConditions[] = "(
            (mno.common_discount_new LIKE '%-%' OR mno.common_discount_new LIKE '%택배%')
            OR (mno.common_discount_port LIKE '%-%' OR mno.common_discount_port LIKE '%택배%')
            OR (mno.common_discount_change LIKE '%-%' OR mno.common_discount_change LIKE '%택배%')
            OR (mno.contract_discount_new LIKE '%-%' OR mno.contract_discount_new LIKE '%택배%')
            OR (mno.contract_discount_port LIKE '%-%' OR mno.contract_discount_port LIKE '%택배%')
            OR (mno.contract_discount_change LIKE '%-%' OR mno.contract_discount_change LIKE '%택배%')
        )";
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // 전체 개수 조회
    $countSql = "
        SELECT COUNT(*) as total
        FROM products p
        INNER JOIN product_mno_details mno ON p.id = mno.product_id
        {$whereClause}
    ";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $index => $value) {
        $countStmt->bindValue($index + 1, $value);
    }
    $countStmt->execute();
    $totalCount = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 디버깅: DB에서 가져온 전체 개수
    error_log("[MNO 페이지] ========== 디버깅 시작 ==========");
    error_log("[MNO 페이지] DB 쿼리 결과 - 전체 개수: {$totalCount}");
    error_log("[MNO 페이지] WHERE 조건: {$whereClause}");
    error_log("[MNO 페이지] 광고 상품 ID 개수: " . count($advertisementProductIds));
    if (!empty($advertisementProductIds)) {
        error_log("[MNO 페이지] 광고 상품 ID 목록: " . implode(', ', $advertisementProductIds));
    }
    
    // 추가 디버깅: products 테이블의 전체 active 상품 개수 확인
    $debugStmt = $pdo->query("SELECT COUNT(*) as cnt FROM products WHERE product_type = 'mno' AND status = 'active'");
    $debugResult = $debugStmt->fetch();
    $allActiveCount = $debugResult['cnt'] ?? 0;
    error_log("[MNO 페이지] products 테이블의 전체 active 상품 개수: {$allActiveCount}");
    
    // product_mno_details가 없는 상품 확인
    $missingStmt = $pdo->query("
        SELECT p.id 
        FROM products p
        LEFT JOIN product_mno_details mno ON p.id = mno.product_id
        WHERE p.product_type = 'mno' 
        AND p.status = 'active'
        AND mno.product_id IS NULL
    ");
    $missingProducts = $missingStmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($missingProducts)) {
        error_log("[MNO 페이지] ⚠ product_mno_details가 없는 상품 ID: " . implode(', ', $missingProducts));
    } else {
        error_log("[MNO 페이지] ✓ 모든 active 상품에 product_mno_details가 있습니다.");
    }
    
    // 상품 목록 조회 (원본 DB 데이터) - 전체 가져오기 (페이지네이션은 PHP에서 처리)
    $sql = "
        SELECT 
            p.id,
            p.seller_id,
            p.application_count,
            p.favorite_count,
            p.view_count,
            mno.device_name,
            mno.device_price,
            mno.device_capacity,
            mno.common_provider,
            mno.common_discount_new,
            mno.common_discount_port,
            mno.common_discount_change,
            mno.contract_provider,
            mno.contract_discount_new,
            mno.contract_discount_port,
            mno.contract_discount_change,
            mno.price_main,
            mno.contract_period_value,
            mno.promotion_title,
            mno.promotions,
            mno.delivery_method,
            mno.visit_region
        FROM products p
        INNER JOIN product_mno_details mno ON p.id = mno.product_id
        {$whereClause}
        ORDER BY p.id DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $index => $value) {
        $stmt->bindValue($index + 1, $value);
    }
    $stmt->execute();
    $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 디버깅: 가져온 상품 개수
    error_log("[MNO 페이지] DB에서 가져온 상품 개수: " . count($allProducts));
    if (count($allProducts) > 0) {
        $productIds = array_column($allProducts, 'id');
        error_log("[MNO 페이지] 상품 ID 범위: " . min($productIds) . " ~ " . max($productIds));
    }
    
    // 원본 데이터를 phone 형식으로 변환 (getPhonesData와 동일한 로직)
    // 판매자 정보 배치 조회
    $sellersData = [];
    $sellerIds = [];
    foreach ($allProducts as $p) {
        $sid = (string)($p['seller_id'] ?? '');
        if ($sid !== '') $sellerIds[$sid] = true;
    }
    if (!empty($sellerIds)) {
        $idList = array_keys($sellerIds);
        $placeholders = implode(',', array_fill(0, count($idList), '?'));
        $sellerStmt = $pdo->prepare("
            SELECT
                user_id,
                NULLIF(company_name,'') AS company_name,
                NULLIF(seller_name,'') AS seller_name,
                NULLIF(name,'') AS name
            FROM users
            WHERE role = 'seller'
              AND user_id IN ($placeholders)
        ");
        $sellerStmt->execute($idList);
        foreach ($sellerStmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $sellersData[(string)$s['user_id']] = $s;
        }
    }
    
    // 찜 상태 조회
    $favoriteProductIds = [];
    $currentUserId = null;
    try {
        if (function_exists('isLoggedIn') && function_exists('getCurrentUser')) {
            if (isLoggedIn()) {
                $currentUser = getCurrentUser();
                $currentUserId = $currentUser['user_id'] ?? null;
            }
        }
    } catch (Exception $e) {
        // 무시
    }
    
    if ($currentUserId && !empty($allProducts)) {
        try {
            $productIds = array_column($allProducts, 'id');
            if (!empty($productIds)) {
                $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                $favStmt = $pdo->prepare("
                    SELECT product_id 
                    FROM product_favorites 
                    WHERE user_id = ? 
                    AND product_id IN ($placeholders)
                    AND product_type = 'mno'
                ");
                $favStmt->execute(array_merge([$currentUserId], $productIds));
                $favoriteProductIdsRaw = $favStmt->fetchAll(PDO::FETCH_COLUMN);
                $favoriteProductIds = array_map('intval', $favoriteProductIdsRaw);
            }
        } catch (Exception $e) {
            // 무시
        }
    }
    
    // 변환 로직 (getPhonesData와 동일)
    $allPhones = [];
    foreach ($allProducts as $product) {
        // 판매자 정보
        $sellerId = (string)($product['seller_id'] ?? '');
        $seller = $sellersData[$sellerId] ?? null;
        $companyName = $seller['seller_name'] ?? $seller['company_name'] ?? $seller['name'] ?? '알뜰폰';
        
        // 통신사 정보 추출
        $provider = '-';
        $commonProviders = [];
        $contractProviders = [];
        
        if (!empty($product['common_provider'])) {
            $commonProviders = json_decode($product['common_provider'], true) ?: [];
            if (!empty($commonProviders)) {
                $provider = $commonProviders[0];
            }
        }
        if ($provider === '-' && !empty($product['contract_provider'])) {
            $contractProviders = json_decode($product['contract_provider'], true) ?: [];
            if (!empty($contractProviders)) {
                $provider = $contractProviders[0];
            }
        }
        
        // 공통지원할인 데이터 변환
        $commonSupport = [];
        if (!empty($commonProviders) && is_array($commonProviders)) {
            $commonDiscountNew = [];
            $commonDiscountPort = [];
            $commonDiscountChange = [];
            
            if (!empty($product['common_discount_new'])) {
                $decoded = json_decode($product['common_discount_new'], true);
                if (is_array($decoded)) {
                    $commonDiscountNew = $decoded;
                }
            }
            if (!empty($product['common_discount_port'])) {
                $decoded = json_decode($product['common_discount_port'], true);
                if (is_array($decoded)) {
                    $commonDiscountPort = $decoded;
                }
            }
            if (!empty($product['common_discount_change'])) {
                $decoded = json_decode($product['common_discount_change'], true);
                if (is_array($decoded)) {
                    $commonDiscountChange = $decoded;
                }
            }
            
            foreach ($commonProviders as $index => $prov) {
                $newVal = isset($commonDiscountNew[$index]) ? trim($commonDiscountNew[$index]) : '9999';
                $portVal = isset($commonDiscountPort[$index]) ? trim($commonDiscountPort[$index]) : '9999';
                $changeVal = isset($commonDiscountChange[$index]) ? trim($commonDiscountChange[$index]) : '9999';
                
                if ($newVal === '') $newVal = '9999';
                if ($portVal === '') $portVal = '9999';
                if ($changeVal === '') $changeVal = '9999';
                
                $commonSupport[] = [
                    'provider' => $prov,
                    'plan_name' => '',
                    'new_subscription' => ($newVal === '9999' || $newVal === 9999) ? 9999 : (int)$newVal,
                    'number_port' => ($portVal === '9999' || $portVal === 9999) ? 9999 : (float)$portVal,
                    'device_change' => ($changeVal === '9999' || $changeVal === 9999) ? 9999 : (float)$changeVal
                ];
            }
        }
        
        // 선택약정할인 데이터 변환
        $contractSupport = [];
        $contractDiscountNew = [];
        $contractDiscountPort = [];
        $contractDiscountChange = [];
        
        if (!empty($product['contract_discount_new'])) {
            $decoded = json_decode($product['contract_discount_new'], true);
            if (is_array($decoded)) {
                $contractDiscountNew = $decoded;
            }
        }
        if (!empty($product['contract_discount_port'])) {
            $decoded = json_decode($product['contract_discount_port'], true);
            if (is_array($decoded)) {
                $contractDiscountPort = $decoded;
            }
        }
        if (!empty($product['contract_discount_change'])) {
            $decoded = json_decode($product['contract_discount_change'], true);
            if (is_array($decoded)) {
                $contractDiscountChange = $decoded;
            }
        }
        
        if (!empty($contractProviders) && is_array($contractProviders)) {
            foreach ($contractProviders as $index => $prov) {
                $newVal = isset($contractDiscountNew[$index]) ? trim($contractDiscountNew[$index]) : '9999';
                $portVal = isset($contractDiscountPort[$index]) ? trim($contractDiscountPort[$index]) : '9999';
                $changeVal = isset($contractDiscountChange[$index]) ? trim($contractDiscountChange[$index]) : '9999';
                
                if ($newVal === '') $newVal = '9999';
                if ($portVal === '') $portVal = '9999';
                if ($changeVal === '') $changeVal = '9999';
                
                $contractSupport[] = [
                    'provider' => $prov,
                    'plan_name' => '',
                    'new_subscription' => ($newVal === '9999' || $newVal === 9999) ? 9999 : (int)$newVal,
                    'number_port' => ($portVal === '9999' || $portVal === 9999) ? 9999 : (float)$portVal,
                    'device_change' => ($changeVal === '9999' || $changeVal === 9999) ? 9999 : (float)$changeVal
                ];
            }
        } else {
            $useProviders = !empty($commonProviders) && is_array($commonProviders) ? $commonProviders : ['SKT', 'KT', 'LGU+'];
            $maxLength = max(
                count($contractDiscountNew),
                count($contractDiscountPort),
                count($contractDiscountChange),
                count($useProviders)
            );
            
            for ($index = 0; $index < $maxLength; $index++) {
                $prov = isset($useProviders[$index]) ? $useProviders[$index] : $useProviders[0];
                $newVal = isset($contractDiscountNew[$index]) ? trim($contractDiscountNew[$index]) : '9999';
                $portVal = isset($contractDiscountPort[$index]) ? trim($contractDiscountPort[$index]) : '9999';
                $changeVal = isset($contractDiscountChange[$index]) ? trim($contractDiscountChange[$index]) : '9999';
                
                if ($newVal === '') $newVal = '9999';
                if ($portVal === '') $portVal = '9999';
                if ($changeVal === '') $changeVal = '9999';
                
                if ($newVal != '9999' || $portVal != '9999' || $changeVal != '9999') {
                    $contractSupport[] = [
                        'provider' => $prov,
                        'plan_name' => '',
                        'new_subscription' => ($newVal === '9999' || $newVal === 9999) ? 9999 : (int)$newVal,
                        'number_port' => ($portVal === '9999' || $portVal === 9999) ? 9999 : (float)$portVal,
                        'device_change' => ($changeVal === '9999' || $changeVal === 9999) ? 9999 : (float)$changeVal
                    ];
                }
            }
        }
        
        // 부가서비스 변환
        $additionalSupports = [];
        $deliveryMethod = $product['delivery_method'] ?? 'delivery';
        $visitRegion = $product['visit_region'] ?? '';
        
        if (!empty($product['promotions'])) {
            $promotions = json_decode($product['promotions'], true) ?: [];
            foreach ($promotions as $promo) {
                if (!empty($promo)) {
                    $additionalSupports[] = $promo;
                }
            }
        }
        
        $promotionTitle = $product['promotion_title'] ?? '부가서비스 없음';
        if ($deliveryMethod === 'visit' && !empty($visitRegion)) {
            if (empty($additionalSupports)) {
                $additionalSupports[] = $visitRegion . ' | ' . $promotionTitle;
            } else {
                $additionalSupports[0] = $visitRegion . ' | ' . $additionalSupports[0];
            }
        } else if (!empty($additionalSupports)) {
            $additionalSupports[0] = '택배 | ' . $additionalSupports[0];
        }
        
        // 가격 포맷팅
        $monthlyPrice = '';
        if (!empty($product['price_main'])) {
            $monthlyPrice = number_format($product['price_main']) . '원';
        }
        
        $releasePrice = '';
        if (!empty($product['device_price'])) {
            $releasePrice = number_format($product['device_price']);
        }
        
        $maintenancePeriod = '';
        if (!empty($product['contract_period_value'])) {
            $maintenancePeriod = $product['contract_period_value'] . '일';
        }
        
        $applicationCount = (int)($product['application_count'] ?? 0);
        $selectionCount = number_format($applicationCount) . '명이 선택';
        
        $isFavorited = false;
        if ($currentUserId && !empty($favoriteProductIds)) {
            $productIdInt = (int)$product['id'];
            $isFavorited = in_array($productIdInt, $favoriteProductIds, true);
        }
        
        require_once __DIR__ . '/../includes/data/plan-data.php';
        $productId = (int)$product['id'];
        $averageRating = getProductAverageRating($productId, 'mno');
        $displayRating = $averageRating > 0 ? number_format($averageRating, 1) : '';
        
        $allPhones[] = [
            'id' => (int)$product['id'],
            'provider' => $provider,
            'company_name' => $companyName,
            'seller_name' => $seller['seller_name'] ?? null,
            'rating' => $displayRating,
            'device_name' => $product['device_name'] ?? '',
            'device_storage' => $product['device_capacity'] ?? '',
            'release_price' => $releasePrice,
            'plan_name' => $provider . ' 요금제',
            'monthly_price' => $monthlyPrice,
            'maintenance_period' => $maintenancePeriod,
            'selection_count' => $selectionCount,
            'application_count' => $applicationCount,
            'common_support' => $commonSupport,
            'contract_support' => $contractSupport,
            'additional_supports' => $additionalSupports,
            'delivery_method' => $deliveryMethod,
            'visit_region' => $visitRegion,
            'promotion_title' => $product['promotion_title'] ?? '부가서비스 없음',
            'is_favorited' => $isFavorited
        ];
    }
} else {
    // DB 연결 실패 시 기존 방식 사용
    $allPhones = getPhonesData(10000);
    $totalCount = count($allPhones);
}

// 광고 상품과 일반 상품 분리
$advertisementPhones = [];
$regularPhones = [];

// 광고 상품 ID 목록을 인덱스로 변환하여 순서 보존
$adProductIdIndex = [];
foreach ($advertisementProductIds as $index => $productId) {
    $adProductIdIndex[$productId] = $index;
}

// 일반 상품 먼저 분리
foreach ($allPhones as $phone) {
    $phoneId = (int)($phone['id'] ?? 0);
    if (isset($adProductIdIndex[$phoneId])) {
        // 광고 상품은 나중에 순서대로 처리
        continue;
    } else {
        $regularPhones[] = $phone;
    }
}

// 광고 상품을 로테이션 순서대로 추가
$adPhonesById = [];
foreach ($allPhones as $phone) {
    $phoneId = (int)($phone['id'] ?? 0);
    if (isset($adProductIdIndex[$phoneId])) {
        $phone['is_advertising'] = true;
        $adPhonesById[$phoneId] = $phone;
    }
}

// 로테이션 순서대로 광고 상품 배열 구성
foreach ($advertisementProductIds as $productId) {
    if (isset($adPhonesById[$productId])) {
        $advertisementPhones[] = $adPhonesById[$productId];
    }
}

// 광고 상품을 앞에 배치하고 일반 상품과 합치기
$allPhones = array_merge($advertisementPhones, $regularPhones);

// 전체 개수는 일반 상품 개수만 카운트 (광고는 별도)
// 하지만 DB에서 가져온 전체 개수에서 광고 상품을 제외한 개수를 사용
$totalCount = count($regularPhones); // 일반 상품 개수만 카운트 (광고는 별도)

// 디버깅: 최종 개수 확인
error_log("[MNO 페이지] 변환 후 전체 상품 개수: " . count($allPhones));
error_log("[MNO 페이지] 광고 상품 개수: " . count($advertisementPhones));
error_log("[MNO 페이지] 일반 상품 개수: " . count($regularPhones));
error_log("[MNO 페이지] 최종 표시 개수 (totalCount): {$totalCount}");
if (count($advertisementPhones) > 0) {
    $adProductIds = array_column($advertisementPhones, 'id');
    error_log("[MNO 페이지] 광고 상품 ID 목록: " . implode(', ', $adProductIds));
}

// 페이지네이션 적용
$phones = array_slice($allPhones, $offset, $limit);
$mno_filters = getMnoFilters();
?>

<main class="main-content">
    <div class="plans-filter-section">
        <div class="plans-filter-inner">
            <div class="plans-filter-group">
                <div class="plans-filter-row">
                    <?php 
                    // 필터가 없을 때 "전체" 버튼 활성화
                    $isAllFilterActive = empty($filterDeviceType) && empty($filterStorage) && !$filterFree;
                    ?>
                    <button class="plans-filter-btn <?php echo $isAllFilterActive ? 'active' : ''; ?>" 
                            data-filter-group="device_type" data-filter-value="" onclick="toggleFilter(this)">
                        <span class="plans-filter-text">전체</span>
                    </button>
                    
                    <button class="plans-filter-btn <?php echo ($filterDeviceType === '갤럭시' || $filterDeviceType === 'galaxy') ? 'active' : ''; ?>" 
                            data-filter-group="device_type" data-filter-value="갤럭시" onclick="toggleFilter(this)">
                        <span class="plans-filter-text">갤럭시</span>
                    </button>
                    
                    <button class="plans-filter-btn <?php echo ($filterDeviceType === '아이폰' || $filterDeviceType === 'iphone') ? 'active' : ''; ?>" 
                            data-filter-group="device_type" data-filter-value="아이폰" onclick="toggleFilter(this)">
                        <span class="plans-filter-text">아이폰</span>
                    </button>
                    
                    <button class="plans-filter-btn <?php echo $filterFree ? 'active' : ''; ?>" 
                            data-filter-group="free" data-filter-value="1" onclick="toggleFilter(this)">
                        <span class="plans-filter-text">공짜</span>
                    </button>
                    
                    <button class="plans-filter-btn <?php echo ($filterStorage === '256GB') ? 'active' : ''; ?>" 
                            data-filter-group="storage" data-filter-value="256GB" onclick="toggleFilter(this)">
                        <span class="plans-filter-text">256GB</span>
                    </button>
                    
                    <button class="plans-filter-btn <?php echo ($filterStorage === '512GB') ? 'active' : ''; ?>" 
                            data-filter-group="storage" data-filter-value="512GB" onclick="toggleFilter(this)">
                        <span class="plans-filter-text">512GB</span>
                    </button>
                    
                    <button class="plans-filter-btn <?php echo ($filterStorage === '1TB') ? 'active' : ''; ?>" 
                            data-filter-group="storage" data-filter-value="1TB" onclick="toggleFilter(this)">
                        <span class="plans-filter-text">1TB</span>
                    </button>
                </div>
            </div>
        </div>
        <hr class="plans-filter-divider">
    </div>

    <!-- 통신사폰 목록 섹션 -->
    <div class="content-layout">
        <div class="plans-main-layout">
            <!-- 왼쪽 섹션: 통신사폰 목록 -->
            <div class="plans-left-section">
                <!-- 테마별 통신사폰 섹션 -->
                <section class="theme-plans-list-section">
                    <!-- 통신사폰 목록 레이아웃 -->
                    <?php
                    $section_title = number_format($totalCount) . '개 검색';
                    include '../includes/layouts/phone-list-layout.php';
                    ?>
                    <?php 
                    $currentCount = count($phones);
                    $remainingCount = max(0, $totalCount - ($offset + $currentCount));
                    $nextPage = $page + 1;
                    $hasMore = ($offset + $currentCount) < $totalCount;
                    ?>
                    <?php if ($hasMore && $totalCount > 0): ?>
                    <div class="load-more-container" id="load-more-anchor">
                        <button id="load-more-mno-btn" class="load-more-btn" data-type="mno" data-page="2" data-total="<?php echo $totalCount; ?>">
                            더보기 (<span id="remaining-count"><?php echo number_format($remainingCount); ?></span>개 남음)
                        </button>
                    </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</main>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

<script src="<?php echo getAssetPath('/assets/js/plan-accordion.js'); ?>" defer></script>
<script src="<?php echo getAssetPath('/assets/js/favorite-heart.js'); ?>" defer></script>
<script src="<?php echo getAssetPath('/assets/js/share.js'); ?>" defer></script>
<!-- 더보기 기능 스크립트 -->
<script src="<?php echo getAssetPath('/assets/js/load-more-products.js'); ?>?v=2"></script>

<style>
/* 더보기 버튼 컨테이너 - 카드와 같은 너비 */
.load-more-container {
    width: 100%;
    max-width: 100%;
    padding: 30px 0;
    box-sizing: border-box;
    margin: 0;
}

/* 더보기 버튼 스타일 - 카드와 같은 너비로 설정 */
.load-more-btn {
    display: block;
    width: 100%;
    max-width: 100%;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white !important;
    text-decoration: none;
    text-align: center;
    border: none;
    padding: 16px 32px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
    box-sizing: border-box;
}

.load-more-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
}

.load-more-btn:active:not(:disabled) {
    transform: translateY(0);
}

.load-more-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<style>
/* 스폰서 배지 스타일 */
.sponsor-text {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-sm); /* .plan-provider-rating-group과 동일한 간격 */
}

.sponsor-badge {
    display: inline-block;
    background: #3b82f6;
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    letter-spacing: 0.5px;
    flex-shrink: 0;
}

.provider-name-text {
    display: inline-block;
}
</style>

<script>
// 필터 스크롤 고정 기능
(function() {
    const filterSection = document.querySelector('.plans-filter-section');
    const resultsCount = document.querySelector('.plans-results-count');
    const themeSection = document.querySelector('.theme-plans-list-section');
    
    if (!filterSection) return;
    
    let isFilterSticky = false;
    let isFilterFixed = false;
    let filterOriginalTop = 0;
    
    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const filterRect = filterSection.getBoundingClientRect();
        const filterTop = filterRect.top;
        
        if (filterOriginalTop === 0 && scrollTop === 0) {
            filterOriginalTop = filterRect.top + scrollTop;
        }
        
        if (scrollTop > 10 && !isFilterSticky) {
            filterSection.classList.add('filter-sticky');
            if (resultsCount) resultsCount.classList.add('filter-active');
            if (themeSection) themeSection.classList.add('filter-active');
            isFilterSticky = true;
        }
        
        if (filterTop < 0 && isFilterSticky && !isFilterFixed) {
            filterSection.classList.remove('filter-sticky');
            filterSection.classList.add('filter-fixed');
            isFilterFixed = true;
        } else if (scrollTop < filterOriginalTop - 50 && isFilterFixed) {
            filterSection.classList.remove('filter-fixed');
            filterSection.classList.add('filter-sticky');
            isFilterFixed = false;
        } else if (scrollTop <= 10 && isFilterSticky) {
            filterSection.classList.remove('filter-sticky', 'filter-fixed');
            if (resultsCount) resultsCount.classList.remove('filter-active');
            if (themeSection) themeSection.classList.remove('filter-active');
            isFilterSticky = false;
            isFilterFixed = false;
        }
    }
    
    let ticking = false;
    function onScroll() {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                handleScroll();
                ticking = false;
            });
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', onScroll, { passive: true });
    handleScroll();
})();

// 필터 함수들 (조합 필터링 지원)
function clearFilters() {
    window.location.href = window.location.pathname;
}

function toggleFilter(button) {
    const filterGroup = button.getAttribute('data-filter-group');
    const filterValue = button.getAttribute('data-filter-value');
    const url = new URL(window.location.href);
    
    if (filterGroup === 'free') {
        // 공짜 필터는 토글
        const currentFree = url.searchParams.get('free');
        if (currentFree === '1') {
            url.searchParams.delete('free');
        } else {
            url.searchParams.set('free', '1');
        }
    } else {
        // device_type과 storage는 그룹 내에서 하나만 선택 가능
        const currentValue = url.searchParams.get(filterGroup);
        const isActive = button.classList.contains('active');
        
        if (isActive) {
            // 같은 버튼을 다시 클릭하면 필터 제거
            url.searchParams.delete(filterGroup);
        } else {
            // 다른 버튼을 클릭하면 해당 그룹의 다른 필터 제거 후 새 필터 설정
            url.searchParams.delete(filterGroup);
            if (filterValue !== '') {
                url.searchParams.set(filterGroup, filterValue);
            }
        }
    }
    
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}
</script>
