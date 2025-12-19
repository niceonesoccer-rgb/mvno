<?php
/**
 * 통신사폰 데이터 처리
 * 데이터베이스에서 MNO 상품 데이터를 가져옵니다.
 */

require_once __DIR__ . '/db-config.php';
// auth-functions.php는 조건부로 로드 (에러 발생 시 무시)
if (file_exists(__DIR__ . '/auth-functions.php')) {
    require_once __DIR__ . '/auth-functions.php';
}

/**
 * 통신사폰 목록 데이터 가져오기
 * @param int $limit 가져올 개수
 * @return array 통신사폰 배열
 */
function getPhonesData($limit = 10) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return [];
    }
    
    // 현재 로그인한 사용자 ID 가져오기 (에러 발생 시 무시)
    $currentUserId = null;
    try {
        if (function_exists('isLoggedIn') && function_exists('getCurrentUser')) {
            if (isLoggedIn()) {
                $currentUser = getCurrentUser();
                $currentUserId = $currentUser['user_id'] ?? null;
            }
        }
    } catch (Exception $e) {
        // 로그인 체크 실패해도 상품 목록은 표시
        error_log("Warning: Failed to get current user in getPhonesData: " . $e->getMessage());
    } catch (Error $e) {
        // PHP 7+ Fatal Error도 처리
        error_log("Warning: Fatal error getting current user in getPhonesData: " . $e->getMessage());
    }
    
    try {
        $stmt = $pdo->prepare("
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
            WHERE p.product_type = 'mno' 
            AND p.status = 'active'
            ORDER BY p.id DESC
            LIMIT :limit
        ");
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 사용자의 찜 상태 가져오기 (에러 발생 시 무시)
        $favoriteProductIds = [];
        if ($currentUserId && !empty($products)) {
            try {
                $productIds = array_column($products, 'id');
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
                    // 정수 배열로 변환하여 타입 일치 보장
                    $favoriteProductIds = array_map('intval', $favoriteProductIdsRaw);
                }
            } catch (Exception $e) {
                // 찜 상태 조회 실패해도 상품 목록은 표시
                error_log("Warning: Failed to get favorite status in getPhonesData: " . $e->getMessage());
            }
        }
        
        // 판매자 정보 로드 (DB-only)
        $sellersData = [];
        $sellerIds = [];
        foreach ($products as $p) {
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
        
        // 데이터 변환
        $phones = [];
        foreach ($products as $product) {
            // 판매자 정보
            $sellerId = (string)($product['seller_id'] ?? '');
            $seller = $sellersData[$sellerId] ?? null;
            // 카드/리스트에서는 "판매자명"이 보여야 하므로 seller_name 우선
            $companyName = $seller['seller_name'] ?? $seller['company_name'] ?? $seller['name'] ?? '알뜰폰';
            
            // 통신사 정보 추출
            $provider = '-';
            $commonProviders = [];
            $contractProviders = [];
            
            if (!empty($product['common_provider'])) {
                $commonProviders = json_decode($product['common_provider'], true) ?: [];
                if (!empty($commonProviders)) {
                    $provider = $commonProviders[0]; // 첫 번째 통신사
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
                    
                    // 빈 문자열이면 9999로 처리
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
        
        // 할인 값 배열 초기화
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
        
        // contract_provider가 없으면 common_provider를 사용하거나 기본 통신사 사용
        if (!empty($contractProviders) && is_array($contractProviders)) {
            // contract_provider가 있는 경우
            foreach ($contractProviders as $index => $prov) {
                $newVal = isset($contractDiscountNew[$index]) ? trim($contractDiscountNew[$index]) : '9999';
                $portVal = isset($contractDiscountPort[$index]) ? trim($contractDiscountPort[$index]) : '9999';
                $changeVal = isset($contractDiscountChange[$index]) ? trim($contractDiscountChange[$index]) : '9999';
                
                // 빈 문자열이면 9999로 처리
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
            // contract_provider가 없지만 할인 값이 있는 경우, common_provider 사용
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
                
                // 빈 문자열이면 9999로 처리
                if ($newVal === '') $newVal = '9999';
                if ($portVal === '') $portVal = '9999';
                if ($changeVal === '') $changeVal = '9999';
                
                // 모든 값이 9999가 아니면 추가
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
            
        // 택배/내방 정보 추가
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
            
            // 유지기간
            $maintenancePeriod = '';
            if (!empty($product['contract_period_value'])) {
                $maintenancePeriod = $product['contract_period_value'] . '일';
            }
            
            // 신청 수
            $applicationCount = (int)($product['application_count'] ?? 0);
            $selectionCount = number_format($applicationCount) . '명이 선택';
            
            // 찜 상태 확인 (엄격한 타입 비교)
            $isFavorited = false;
            if ($currentUserId && !empty($favoriteProductIds)) {
                $productIdInt = (int)$product['id'];
                $isFavorited = in_array($productIdInt, $favoriteProductIds, true); // strict mode
            }
            
            $phones[] = [
                'id' => (int)$product['id'],
                'provider' => $provider,
                // UI에서 phone['company_name']을 표시하는 곳이 많아 하위호환 유지:
                // - seller_name 우선값을 company_name 키에 넣어준다.
                'company_name' => $companyName,
                'seller_name' => $seller['seller_name'] ?? null,
                'rating' => '4.3', // 기본값, 나중에 리뷰 시스템 연동
                'device_name' => $product['device_name'] ?? '',
                'device_storage' => $product['device_capacity'] ?? '',
                'release_price' => $releasePrice,
                'plan_name' => $provider . ' 요금제', // 기본값
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
        
        // 디버깅: 데이터 확인
        if (empty($phones)) {
            error_log("getPhonesData: No products found in database");
        } else {
            error_log("getPhonesData: Found " . count($phones) . " products");
        }
        
        return $phones;
        
    } catch (PDOException $e) {
        error_log("Error fetching phones data: " . $e->getMessage());
        error_log("SQL Query: " . $stmt->queryString ?? 'N/A');
        return [];
    } catch (Exception $e) {
        error_log("Unexpected error in getPhonesData: " . $e->getMessage());
        return [];
    }
}

/**
 * 통신사폰 상세 데이터 가져오기
 * @param int $phone_id 통신사폰 ID
 * @return array|null 통신사폰 데이터 또는 null
 */
function getPhoneDetailData($phone_id) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return null;
    }
    
    // 현재 로그인한 사용자 ID 및 관리자 여부 확인 (에러 발생 시 무시)
    $currentUserId = null;
    $isAdmin = false;
    try {
        if (function_exists('isLoggedIn') && function_exists('getCurrentUser') && function_exists('isAdmin')) {
            if (isLoggedIn()) {
                $currentUser = getCurrentUser();
                $currentUserId = $currentUser['user_id'] ?? null;
                $isAdmin = isAdmin($currentUserId);
            }
        }
    } catch (Exception $e) {
        // 로그인 체크 실패해도 상품 정보는 반환
        error_log("Warning: Failed to get current user in getPhoneDetailData: " . $e->getMessage());
    } catch (Error $e) {
        // PHP 7+ Fatal Error도 처리
        error_log("Warning: Fatal error getting current user in getPhoneDetailData: " . $e->getMessage());
    }
    
    try {
        // 관리자는 inactive 상태도 볼 수 있음
        $statusCondition = $isAdmin ? "AND p.status != 'deleted'" : "AND p.status = 'active'";
        
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.seller_id,
                p.status,
                p.application_count,
                p.favorite_count,
                p.view_count,
                mno.device_name,
                mno.device_price,
                mno.device_capacity,
                mno.device_colors,
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
            WHERE p.id = :product_id 
            AND p.product_type = 'mno'
            {$statusCondition}
            LIMIT 1
        ");
        
        $stmt->bindValue(':product_id', $phone_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return null;
        }
        
        // 찜 상태 확인 (에러 발생 시 무시)
        $isFavorited = false;
        if ($currentUserId) {
            try {
                $favStmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM product_favorites 
                    WHERE user_id = :user_id 
                    AND product_id = :product_id
                    AND product_type = 'mno'
                ");
                $favStmt->execute([
                    ':user_id' => $currentUserId,
                    ':product_id' => $phone_id
                ]);
                $favCount = $favStmt->fetchColumn();
                $isFavorited = ($favCount > 0);
            } catch (Exception $e) {
                // 찜 상태 조회 실패해도 상품 정보는 반환
                error_log("Warning: Failed to get favorite status in getPhoneDetailData: " . $e->getMessage());
            }
        }
        
        // 판매자 정보 로드 (DB-only)
        $seller = null;
        $sellerId = (string)($product['seller_id'] ?? '');
        if (!empty($sellerId)) {
            try {
                $sellerStmt = $pdo->prepare("
                    SELECT
                        user_id,
                        NULLIF(company_name,'') AS company_name,
                        NULLIF(seller_name,'') AS seller_name,
                        NULLIF(name,'') AS name
                    FROM users
                    WHERE role = 'seller'
                      AND user_id = :user_id
                    LIMIT 1
                ");
                $sellerStmt->execute([':user_id' => $sellerId]);
                $seller = $sellerStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (Exception $e) {
                // ignore
            }
        }
        
        // 상세에서도 "판매자명"이 보여야 하므로 seller_name 우선
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
                
                // 빈 문자열이면 9999로 처리
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
        
        // 할인 값 배열 초기화
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
        
        // contract_provider가 없으면 common_provider를 사용하거나 기본 통신사 사용
        if (!empty($contractProviders) && is_array($contractProviders)) {
            // contract_provider가 있는 경우
            foreach ($contractProviders as $index => $prov) {
                $newVal = isset($contractDiscountNew[$index]) ? trim($contractDiscountNew[$index]) : '9999';
                $portVal = isset($contractDiscountPort[$index]) ? trim($contractDiscountPort[$index]) : '9999';
                $changeVal = isset($contractDiscountChange[$index]) ? trim($contractDiscountChange[$index]) : '9999';
                
                // 빈 문자열이면 9999로 처리
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
            // contract_provider가 없지만 할인 값이 있는 경우, common_provider 사용
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
                
                // 빈 문자열이면 9999로 처리
                if ($newVal === '') $newVal = '9999';
                if ($portVal === '') $portVal = '9999';
                if ($changeVal === '') $changeVal = '9999';
                
                // 모든 값이 9999가 아니면 추가
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
        
        // 택배/내방 정보 추가
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
        $devicePrice = '';
        if (!empty($product['device_price'])) {
            $devicePrice = $product['device_price'];
            $releasePrice = number_format($devicePrice);
        }
        
        // 유지기간
        $maintenancePeriod = '';
        if (!empty($product['contract_period_value'])) {
            $maintenancePeriod = $product['contract_period_value'] . '일';
        }
        
        // 신청 수
        $applicationCount = (int)($product['application_count'] ?? 0);
        $selectionCount = number_format($applicationCount) . '명이 선택';
        
        // 단말기 색상 정보 파싱
        $deviceColors = [];
        if (!empty($product['device_colors'])) {
            $colorsJson = json_decode($product['device_colors'], true);
            if (is_array($colorsJson)) {
                $deviceColors = $colorsJson;
            }
        }
        
        return [
            'id' => (int)$product['id'],
            'status' => $product['status'] ?? 'active', // 상품 상태 추가
            'provider' => $provider,
            'company_name' => $companyName,
            'seller_name' => $seller['seller_name'] ?? null,
            'rating' => '4.3', // 기본값
            'device_name' => $product['device_name'] ?? '',
            'device_storage' => $product['device_capacity'] ?? '',
            'device_price' => $devicePrice, // 원본 가격 값 추가
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
            'device_colors' => $deviceColors,
            'is_favorited' => $isFavorited
        ];
        
    } catch (PDOException $e) {
        error_log("Error fetching phone detail data: " . $e->getMessage());
        error_log("Product ID: " . $phone_id);
        return null;
    } catch (Exception $e) {
        error_log("Unexpected error in getPhoneDetailData: " . $e->getMessage());
        return null;
    }
}

