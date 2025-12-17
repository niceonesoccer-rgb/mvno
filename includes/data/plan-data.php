<?php
/**
 * 요금제 데이터 처리
 */

require_once __DIR__ . '/db-config.php';
require_once __DIR__ . '/auth-functions.php';

/**
 * DB에서 MVNO 상품 데이터를 카드 형식으로 변환
 * @param array $product DB에서 가져온 상품 데이터
 * @return array 카드 형식의 요금제 데이터
 */
function convertMvnoProductToPlanCard($product) {
    // 통신사 provider 원본 값 저장 (통신사 망 정보 추출용)
    $originalProvider = $product['provider'] ?? '';
    
    // 데이터 제공량 포맷팅
    $dataMain = '';
    if (!empty($product['data_amount'])) {
        if ($product['data_amount'] === '무제한') {
            $dataMain = '무제한';
        } elseif ($product['data_amount'] === '직접입력' && !empty($product['data_amount_value'])) {
            // DB에 저장된 값이 "100GB" 형식이면 그대로 표시
            $dataAmountValue = $product['data_amount_value'];
            if (preg_match('/^(\d+)(.+)$/', $dataAmountValue, $matches)) {
                $dataMain = '월 ' . number_format((float)$matches[1]) . $matches[2];
            } else {
                $dataMain = '월 ' . htmlspecialchars($dataAmountValue);
            }
        } else {
            $dataMain = $product['data_amount'];
        }
        
        // 데이터 추가제공 추가
        if (!empty($product['data_additional']) && $product['data_additional'] !== '없음') {
            if ($product['data_additional'] === '직접입력' && !empty($product['data_additional_value'])) {
                $dataMain .= ' + ' . $product['data_additional_value'];
            } else {
                $dataMain .= ' + ' . $product['data_additional'];
            }
        }
        
        // 데이터 소진시 추가 (직접입력이 아닌 경우만)
        if (!empty($product['data_exhausted']) && $product['data_exhausted'] !== '직접입력') {
            $dataMain .= ' + ' . $product['data_exhausted'];
        } elseif (!empty($product['data_exhausted']) && $product['data_exhausted'] === '직접입력' && !empty($product['data_exhausted_value'])) {
            $dataMain .= ' + ' . $product['data_exhausted_value'];
        }
    }
    
    // 기능 배열 생성 (DB에서 가져온 데이터 사용)
    $features = [];
    
    // 통화 정보 (DB에서 가져온 call_type, call_amount 사용)
    if (!empty($product['call_type'])) {
        $callType = trim($product['call_type']);
        if ($callType === '무제한') {
            $features[] = '통화 무제한';
        } elseif ($callType === '기본제공') {
            $features[] = '통화 기본제공';
        } elseif ($callType === '직접입력' && !empty($product['call_amount'])) {
            $callAmount = trim($product['call_amount']);
            if ($callAmount !== '') {
                // DB에 저장된 값이 "300분" 형식이면 그대로 표시
                if (preg_match('/^(\d+)(.+)$/', $callAmount, $matches)) {
                    $features[] = '통화 ' . number_format((float)$matches[1]) . $matches[2];
                } else {
                    $features[] = '통화 ' . htmlspecialchars($callAmount);
                }
            }
        }
    }
    
    // 문자 정보 (DB에서 가져온 sms_type, sms_amount 사용)
    if (!empty($product['sms_type'])) {
        $smsType = trim($product['sms_type']);
        if ($smsType === '무제한') {
            $features[] = '문자 무제한';
        } elseif ($smsType === '기본제공') {
            $features[] = '문자 기본제공';
        } elseif ($smsType === '직접입력' && !empty($product['sms_amount'])) {
            $smsAmount = trim($product['sms_amount']);
            if ($smsAmount !== '') {
                // DB에 저장된 값이 "50건" 또는 "10원/건" 형식이면 그대로 표시
                if (preg_match('/^(\d+)(.+)$/', $smsAmount, $matches)) {
                    $features[] = '문자 ' . number_format((float)$matches[1]) . $matches[2];
                } else {
                    $features[] = '문자 ' . htmlspecialchars($smsAmount);
                }
            }
        }
    }
    
    // 부가·영상통화 정보 (DB에서 가져온 additional_call_type, additional_call 사용)
    if (!empty($product['additional_call_type'])) {
        $additionalCallType = trim($product['additional_call_type']);
        if ($additionalCallType === '직접입력' && !empty($product['additional_call'])) {
            $additionalCall = trim($product['additional_call']);
            if ($additionalCall !== '') {
                // DB에 저장된 값이 "100분" 형식이면 그대로 표시
                if (preg_match('/^(\d+)(.+)$/', $additionalCall, $matches)) {
                    $features[] = '부가·영상 ' . number_format((float)$matches[1]) . $matches[2];
                } else {
                    $features[] = '부가·영상 ' . htmlspecialchars($additionalCall);
                }
            }
        }
    }
    
    // 통신사 망 정보 (DB에서 가져온 provider 값 그대로 표시)
    // provider 값 예시: "KT알뜰폰", "SK알뜰폰", "LG알뜰폰"
    if (!empty($originalProvider)) {
        $providerTrimmed = trim($originalProvider);
        // DB에 저장된 값 그대로 표시 (변환하지 않음)
        $features[] = $providerTrimmed;
    }
    
    // 서비스 타입 (DB에서 가져온 service_type 사용, 예: LTE, 5G 등)
    if (!empty($product['service_type'])) {
        $serviceType = trim($product['service_type']);
        if ($serviceType !== '') {
            $features[] = $serviceType;
        }
    }
    
    // 가격 포맷팅 (변경된 로직)
    // price_main: 할인 후 요금(프로모션기간)에 기입된 내용
    // price_after: 할인기간(프로모션기간) + 원래 월요금
    
    $priceAfterValue = $product['price_after'] ?? null;
    $originalPriceMain = $product['price_main'] ?? 0;
    
    // price_main: 할인 후 요금 표시 (프로모션 기간 요금)
    if ($priceAfterValue === null || $priceAfterValue === '' || $priceAfterValue === '0') {
        // 할인 후 요금이 없으면(공짜) "공짜"로 표시
        $priceMain = '공짜';
    } elseif ($priceAfterValue !== null && $priceAfterValue !== '' && $priceAfterValue !== '0') {
        // 할인 후 요금이 있으면 그것을 price_main으로 표시
        $priceMain = '월 ' . number_format((float)$priceAfterValue) . '원';
    } else {
        // 기본값
        $priceMain = '월 ' . number_format((float)$originalPriceMain) . '원';
    }
    
    // price_after: 할인기간 + 원래 월요금
    $priceAfter = '';
    if (!empty($product['discount_period'])) {
        $priceAfter = $product['discount_period'] . ' 이후 월 ' . number_format((float)$originalPriceMain) . '원';
    } else {
        // 할인기간이 없으면 원래 월요금만 표시
        $priceAfter = '월 ' . number_format((float)$originalPriceMain) . '원';
    }
    
    // 선택 수 포맷팅 (실제 DB의 application_count 사용)
    $applicationCount = isset($product['application_count']) ? (int)$product['application_count'] : 0;
    $selectionCount = number_format($applicationCount) . '명이 선택';
    
    // 프로모션 목록 (gifts) - 항목만 포함 (제목 제외)
    $gifts = [];
    if (!empty($product['promotions'])) {
        $promotions = json_decode($product['promotions'], true);
        if (is_array($promotions)) {
            $gifts = array_filter($promotions, function($p) {
                return !empty(trim($p));
            });
        }
    }
    
    // 프로모션 제목은 별도로 저장 (아코디언 제목용)
    $promotionTitle = $product['promotion_title'] ?? '';
    
    // 판매자명 가져오기
    $sellerId = $product['seller_id'] ?? null;
    $sellerName = '';
    if ($sellerId) {
        $seller = getSellerById($sellerId);
        $sellerName = getSellerDisplayName($seller);
    }
    
    // provider 필드에 판매자명 사용 (통신사명 대신)
    $displayProvider = !empty($sellerName) ? $sellerName : ($product['provider'] ?? '판매자 정보 없음');
    
    // 평균 별점 가져오기 (DB에서)
    $productId = (int)$product['id'];
    $averageRating = getProductAverageRating($productId, 'mvno');
    
    // 별점이 0이면 빈 문자열 (리뷰가 없는 경우)
    $displayRating = $averageRating > 0 ? number_format($averageRating, 1) : '';
    
    return [
        'id' => $productId,
        'provider' => $displayProvider, // 판매자명으로 표시
        'rating' => $displayRating, // DB에서 가져온 평균 별점
        'title' => $product['plan_name'] ?? '',
        'data_main' => $dataMain ?: '데이터 정보 없음',
        'features' => $features,
        'price_main' => $priceMain,
        'price_after' => $priceAfter,
        'selection_count' => $selectionCount,
        'gifts' => $gifts, // 항목만 포함
        'promotion_title' => $promotionTitle, // 아코디언 제목용
        'gift_icons' => [], // 나중에 추가 가능
    ];
}

/**
 * 등록된 모든 MVNO 상품 목록 가져오기 (상세 정보 포함)
 * @param string $status 상품 상태 필터 (기본: 'active', null이면 모든 상태)
 * @return array 상품 배열
 */
function getAllMvnoProductsList($status = 'active') {
    $pdo = getDBConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $sql = "
            SELECT 
                p.id,
                p.seller_id,
                p.status,
                p.view_count,
                p.favorite_count,
                p.review_count,
                p.share_count,
                p.application_count,
                p.created_at,
                p.updated_at,
                mvno.provider,
                mvno.service_type,
                mvno.plan_name,
                mvno.contract_period,
                mvno.contract_period_days,
                mvno.discount_period,
                mvno.price_main,
                mvno.price_after,
                mvno.data_amount,
                mvno.data_amount_value,
                mvno.data_unit,
                mvno.data_additional,
                mvno.data_additional_value,
                mvno.data_exhausted,
                mvno.data_exhausted_value,
                mvno.call_type,
                mvno.call_amount,
                mvno.additional_call_type,
                mvno.additional_call,
                mvno.sms_type,
                mvno.sms_amount,
                mvno.promotion_title,
                mvno.promotions
            FROM products p
            INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
            WHERE p.product_type = 'mvno'
        ";
        
        $params = [];
        if ($status !== null) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $status;
        } else {
            $sql .= " AND p.status != 'deleted'";
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching all MVNO products: " . $e->getMessage());
        return [];
    }
}

/**
 * 요금제 목록 데이터 가져오기 (DB에서)
 * @param int $limit 가져올 개수
 * @param string $status 상품 상태 필터 (기본: 'active')
 * @return array 요금제 배열
 */
/**
 * 판매자 정보 가져오기 (seller_id로)
 * @param string $sellerId 판매자 ID
 * @return array|null 판매자 정보 또는 null
 */
function getSellerById($sellerId) {
    if (empty($sellerId)) {
        return null;
    }
    
    $sellersFile = __DIR__ . '/sellers.json';
    if (!file_exists($sellersFile)) {
        return null;
    }
    
    $data = json_decode(file_get_contents($sellersFile), true) ?: ['sellers' => []];
    $sellers = $data['sellers'] ?? [];
    
    foreach ($sellers as $seller) {
        if (isset($seller['user_id']) && (string)$seller['user_id'] === (string)$sellerId) {
            return $seller;
        }
    }
    
    return null;
}

/**
 * 판매자명 가져오기 (우선순위: seller_name > company_name > name)
 * @param array|null $seller 판매자 정보
 * @return string 판매자명
 */
function getSellerDisplayName($seller) {
    if (!$seller) {
        return '판매자 정보 없음';
    }
    
    if (!empty($seller['seller_name'])) {
        return $seller['seller_name'];
    }
    
    if (!empty($seller['company_name'])) {
        return $seller['company_name'];
    }
    
    if (!empty($seller['name'])) {
        return $seller['name'];
    }
    
    return '판매자 정보 없음';
}

/**
 * 상품의 평균 별점 가져오기
 * 같은 판매자의 같은 상품 타입의 모든 상품 리뷰를 통합하여 계산
 * @param int $productId 상품 ID
 * @param string $productType 상품 타입 ('mvno' 또는 'mno')
 * @return float 평균 별점 (0.0 ~ 5.0), 리뷰가 없으면 0.0
 */
function getProductAverageRating($productId, $productType = 'mvno') {
    $pdo = getDBConnection();
    if (!$pdo) {
        return 0.0;
    }
    
    try {
        // 먼저 상품의 seller_id 가져오기
        $productStmt = $pdo->prepare("
            SELECT seller_id 
            FROM products 
            WHERE id = :product_id 
            AND product_type = :product_type
            AND status = 'active'
            LIMIT 1
        ");
        $productStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $productStmt->bindValue(':product_type', $productType, PDO::PARAM_STR);
        $productStmt->execute();
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product || !isset($product['seller_id'])) {
            return 0.0;
        }
        
        $sellerId = $product['seller_id'];
        
        // 같은 판매자의 같은 타입의 모든 상품 ID 가져오기
        $productsStmt = $pdo->prepare("
            SELECT id 
            FROM products 
            WHERE seller_id = :seller_id 
            AND product_type = :product_type
            AND status = 'active'
        ");
        $productsStmt->bindValue(':seller_id', $sellerId, PDO::PARAM_INT);
        $productsStmt->bindValue(':product_type', $productType, PDO::PARAM_STR);
        $productsStmt->execute();
        $productIds = $productsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($productIds)) {
            return 0.0;
        }
        
        // 같은 판매자의 같은 타입의 모든 상품 리뷰의 평균 별점 계산
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $pdo->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
            FROM product_reviews
            WHERE product_id IN ($placeholders)
            AND product_type = ?
            AND status = 'approved'
        ");
        
        $params = array_merge($productIds, [$productType]);
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['review_count'] > 0 && $result['avg_rating'] !== null) {
            return round((float)$result['avg_rating'], 1);
        }
        
        return 0.0;
    } catch (PDOException $e) {
        error_log("Error fetching product average rating: " . $e->getMessage());
        return 0.0;
    }
}

/**
 * 상품 리뷰 목록 가져오기
 * 같은 판매자의 같은 상품 타입의 모든 상품 리뷰를 통합하여 가져옴
 * @param int $productId 상품 ID
 * @param string $productType 상품 타입 ('mvno' 또는 'mno')
 * @param int $limit 가져올 개수 (기본: 10)
 * @param string $sort 정렬 방식 ('rating_desc', 'rating_asc', 'created_desc') 기본: 'created_desc'
 * @return array 리뷰 배열
 */
function getProductReviews($productId, $productType = 'mvno', $limit = 10, $sort = 'rating_desc') {
    $pdo = getDBConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        // 먼저 상품의 seller_id 가져오기
        $productStmt = $pdo->prepare("
            SELECT seller_id 
            FROM products 
            WHERE id = :product_id 
            AND product_type = :product_type
            AND status = 'active'
            LIMIT 1
        ");
        $productStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $productStmt->bindValue(':product_type', $productType, PDO::PARAM_STR);
        $productStmt->execute();
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product || !isset($product['seller_id'])) {
            return [];
        }
        
        $sellerId = $product['seller_id'];
        
        // 같은 판매자의 같은 타입의 모든 상품 ID 가져오기
        $productsStmt = $pdo->prepare("
            SELECT id 
            FROM products 
            WHERE seller_id = :seller_id 
            AND product_type = :product_type
            AND status = 'active'
        ");
        $productsStmt->bindValue(':seller_id', $sellerId, PDO::PARAM_INT);
        $productsStmt->bindValue(':product_type', $productType, PDO::PARAM_STR);
        $productsStmt->execute();
        $productIds = $productsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($productIds)) {
            return [];
        }
        
        // 정렬 방식 설정 (기본값: 높은 평점순)
        $orderBy = 'r.rating DESC, r.created_at DESC'; // 기본값: 높은 평점순
        switch ($sort) {
            case 'rating_desc':
                $orderBy = 'r.rating DESC, r.created_at DESC';
                break;
            case 'rating_asc':
                $orderBy = 'r.rating ASC, r.created_at DESC';
                break;
            case 'created_desc':
                $orderBy = 'r.created_at DESC';
                break;
            default:
                $orderBy = 'r.rating DESC, r.created_at DESC';
                break;
        }
        
        // 같은 판매자의 같은 타입의 모든 상품 리뷰 가져오기
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $pdo->prepare("
            SELECT 
                r.id,
                r.user_id,
                r.rating,
                r.title,
                r.content,
                r.is_verified,
                r.helpful_count,
                r.created_at
            FROM product_reviews r
            WHERE r.product_id IN ($placeholders)
            AND r.product_type = ?
            AND r.status = 'approved'
            ORDER BY $orderBy
            LIMIT ?
        ");
        
        $params = array_merge($productIds, [$productType, $limit]);
        $stmt->execute($params);
        
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 사용자 이름 가져오기
        foreach ($reviews as &$review) {
            $user = getUserById($review['user_id']);
            if ($user) {
                // 이름 마스킹 (예: "홍길동" -> "홍*동")
                $name = $user['name'] ?? '익명';
                if (mb_strlen($name) > 1) {
                    $firstChar = mb_substr($name, 0, 1);
                    $lastChar = mb_substr($name, -1);
                    $review['author_name'] = $firstChar . '*' . $lastChar;
                } else {
                    $review['author_name'] = $name;
                }
            } else {
                $review['author_name'] = '익명';
            }
            
            // 날짜 포맷팅 (예: "24일 전")
            $review['date_ago'] = formatDateAgo($review['created_at']);
            
            // 별점을 별 아이콘으로 변환
            $review['stars'] = getStarsFromRating($review['rating']);
        }
        
        return $reviews;
    } catch (PDOException $e) {
        error_log("Error fetching product reviews: " . $e->getMessage());
        return [];
    }
}

/**
 * 상품의 리뷰 개수 가져오기
 * 같은 판매자의 같은 상품 타입의 모든 상품 리뷰를 통합하여 계산
 * @param int $productId 상품 ID
 * @param string $productType 상품 타입 ('mvno' 또는 'mno')
 * @return int 리뷰 개수
 */
function getProductReviewCount($productId, $productType = 'mvno') {
    $pdo = getDBConnection();
    if (!$pdo) {
        return 0;
    }
    
    try {
        // 먼저 상품의 seller_id 가져오기
        $productStmt = $pdo->prepare("
            SELECT seller_id 
            FROM products 
            WHERE id = :product_id 
            AND product_type = :product_type
            AND status = 'active'
            LIMIT 1
        ");
        $productStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $productStmt->bindValue(':product_type', $productType, PDO::PARAM_STR);
        $productStmt->execute();
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product || !isset($product['seller_id'])) {
            return 0;
        }
        
        $sellerId = $product['seller_id'];
        
        // 같은 판매자의 같은 타입의 모든 상품 ID 가져오기
        $productsStmt = $pdo->prepare("
            SELECT id 
            FROM products 
            WHERE seller_id = :seller_id 
            AND product_type = :product_type
            AND status = 'active'
        ");
        $productsStmt->bindValue(':seller_id', $sellerId, PDO::PARAM_INT);
        $productsStmt->bindValue(':product_type', $productType, PDO::PARAM_STR);
        $productsStmt->execute();
        $productIds = $productsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($productIds)) {
            return 0;
        }
        
        // 같은 판매자의 같은 타입의 모든 상품 리뷰 개수 계산
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as review_count
            FROM product_reviews
            WHERE product_id IN ($placeholders)
            AND product_type = ?
            AND status = 'approved'
        ");
        
        $params = array_merge($productIds, [$productType]);
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)($result['review_count'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error fetching product review count: " . $e->getMessage());
        return 0;
    }
}

/**
 * 날짜를 "N일 전" 형식으로 변환
 * @param string $date 날짜 문자열
 * @return string 포맷된 날짜
 */
function formatDateAgo($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    $days = floor($diff / (60 * 60 * 24));
    
    if ($days < 1) {
        return '오늘';
    } elseif ($days < 30) {
        return $days . '일 전';
    } elseif ($days < 365) {
        $months = floor($days / 30);
        return $months . '개월 전';
    } else {
        $years = floor($days / 365);
        return $years . '년 전';
    }
}

/**
 * 별점을 별 아이콘 문자열로 변환
 * @param int $rating 별점 (1-5)
 * @return string 별 아이콘 문자열 (예: "★★★★★")
 */
function getStarsFromRating($rating) {
    $rating = (int)$rating;
    if ($rating < 1) $rating = 1;
    if ($rating > 5) $rating = 5;
    
    $fullStars = $rating;
    $emptyStars = 5 - $rating;
    
    return str_repeat('★', $fullStars) . str_repeat('☆', $emptyStars);
}

function getPlansDataFromDB($limit = 10, $status = 'active') {
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
        error_log("Warning: Failed to get current user in getPlansDataFromDB: " . $e->getMessage());
    } catch (Error $e) {
        // PHP 7+ Fatal Error도 처리
        error_log("Warning: Fatal error getting current user in getPlansDataFromDB: " . $e->getMessage());
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.seller_id,
                p.status,
                p.view_count,
                p.favorite_count,
                p.review_count,
                p.share_count,
                p.application_count,
                mvno.provider,
                mvno.service_type,
                mvno.plan_name,
                mvno.contract_period,
                mvno.contract_period_days,
                mvno.discount_period,
                mvno.price_main,
                mvno.price_after,
                mvno.data_amount,
                mvno.data_amount_value,
                mvno.data_unit,
                mvno.data_additional,
                mvno.data_additional_value,
                mvno.data_exhausted,
                mvno.data_exhausted_value,
                mvno.call_type,
                mvno.call_amount,
                mvno.additional_call_type,
                mvno.additional_call,
                mvno.sms_type,
                mvno.sms_amount,
                mvno.promotion_title,
                mvno.promotions
            FROM products p
            INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
            WHERE p.product_type = 'mvno' 
            AND p.status = :status
            ORDER BY p.created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
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
                        AND product_type = 'mvno'
                    ");
                    $favStmt->execute(array_merge([$currentUserId], $productIds));
                    $favoriteProductIdsRaw = $favStmt->fetchAll(PDO::FETCH_COLUMN);
                    // 정수 배열로 변환하여 타입 일치 보장
                    $favoriteProductIds = array_map('intval', $favoriteProductIdsRaw);
                }
            } catch (Exception $e) {
                // 찜 상태 조회 실패해도 상품 목록은 표시
                error_log("Warning: Failed to get favorite status in getPlansDataFromDB: " . $e->getMessage());
            }
        }
        
        // 카드 형식으로 변환
        $plans = [];
        foreach ($products as $product) {
            try {
                $plan = convertMvnoProductToPlanCard($product);
                // 찜 상태 추가 (엄격한 타입 비교)
                $productIdInt = (int)$product['id'];
                $plan['is_favorited'] = ($currentUserId && !empty($favoriteProductIds) && in_array($productIdInt, $favoriteProductIds, true));
                $plans[] = $plan;
            } catch (Exception $e) {
                // 개별 상품 변환 실패해도 다른 상품은 표시
                error_log("Warning: Failed to convert product to plan card (id={$product['id']}): " . $e->getMessage());
            }
        }
        
        return $plans;
    } catch (PDOException $e) {
        error_log("Error fetching MVNO plans from DB: " . $e->getMessage());
        return [];
    }
}

/**
 * 요금제 목록 데이터 가져오기
 * @param int $limit 가져올 개수
 * @return array 요금제 배열
 */
function getPlansData($limit = 10) {
    // DB에서 데이터 가져오기 시도
    $plans = getPlansDataFromDB($limit, 'active');
    
    // DB에 데이터가 있으면 반환
    if (!empty($plans)) {
        return $plans;
    }
    
    // DB에 데이터가 없으면 하드코딩 데이터 사용 (임시)
    $allPlans = [
        [
            'id' => 32627,
            'provider' => '쉐이크모바일',
            'rating' => '4.3',
            'title' => '11월한정 LTE 100GB+밀리+Data쿠폰60GB',
            'data_main' => '월 100GB + 5Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'],
            'price_main' => '월 17,000원',
            'price_after' => '7개월 이후 42,900원',
            'selection_count' => '29,448명이 선택',
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
        ],
        [
            'id' => 32632,
            'provider' => '고고모바일',
            'rating' => '4.2',
            'title' => 'LTE무제한 100GB+5M(CU20%할인)_11월',
            'data_main' => '월 100GB + 5Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'],
            'price_main' => '월 17,000원',
            'price_after' => '7개월 이후 42,900원',
            'selection_count' => '12,353명이 선택',
            'gifts' => [
                'KT유심&배송비 무료',
                '데이터쿠폰 20GB x 3회',
                '추가데이터 20GB 제공',
                '이마트 상품권',
                'CU 상품권',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg', 'alt' => 'KT유심&배송비 무료'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '데이터쿠폰 20GB x 3회'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '추가데이터 20GB 제공'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg', 'alt' => '이마트 상품권'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/cu.svg', 'alt' => 'CU 상품권'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ],
        [
            'id' => 29290,
            'provider' => '이야기모바일',
            'rating' => '4.5',
            'title' => '이야기 완전 무제한 100GB+',
            'data_main' => '월 100GB + 5Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'],
            'price_main' => '월 17,000원',
            'price_after' => '7개월 이후 49,500원',
            'selection_count' => '17,816명이 선택',
            'gifts' => [
                '네이버페이',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ],
        [
            'id' => 32628,
            'provider' => '핀다이렉트',
            'rating' => '4.2',
            'title' => '[S] 핀다이렉트Z _2511',
            'data_main' => '월 11GB + 매일 2GB + 3Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'],
            'price_main' => '월 14,960원',
            'price_after' => '7개월 이후 39,600원',
            'selection_count' => '4,420명이 선택',
            'gifts' => [
                '매월 20GB 추가 데이터',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '추가 데이터'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ],
        [
            'id' => 32629,
            'provider' => '고고모바일',
            'rating' => '4.2',
            'title' => '무제한 11GB+3M(밀리의서재 Free)_11월',
            'data_main' => '월 11GB + 매일 2GB + 3Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'],
            'price_main' => '월 15,000원',
            'price_after' => '7개월 이후 36,300원',
            'selection_count' => '6,970명이 선택',
            'gifts' => [
                'KT유심&배송비 무료',
                '데이터쿠폰 20GB x 3회',
                '추가데이터 20GB 제공',
                '이마트 상품권',
                '네이버페이',
                '밀리의 서재'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg', 'alt' => 'KT유심&배송비 무료'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '데이터쿠폰'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '추가데이터'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg', 'alt' => '이마트 상품권'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/millie.svg', 'alt' => '밀리의 서재']
            ]
        ],
        [
            'id' => 32630,
            'provider' => '찬스모바일',
            'rating' => '4.5',
            'title' => '음성기본 11GB+일 2GB+',
            'data_main' => '월 11GB + 매일 2GB + 3Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'LG U+망', 'LTE'],
            'price_main' => '월 15,000원',
            'price_after' => '7개월 이후 38,500원',
            'selection_count' => '31,315명이 선택',
            'gifts' => [
                '유심/배송비 무료',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg', 'alt' => '유심/배송비 무료'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ],
        [
            'id' => 32631,
            'provider' => '이야기모바일',
            'rating' => '4.5',
            'title' => '이야기 완전 무제한 11GB+',
            'data_main' => '월 11GB + 매일 2GB + 3Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'],
            'price_main' => '월 15,000원',
            'price_after' => '7개월 이후 39,600원',
            'selection_count' => '13,651명이 선택',
            'gifts' => [
                '네이버페이',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ],
        [
            'id' => 32633,
            'provider' => '찬스모바일',
            'rating' => '4.5',
            'title' => '100분 15GB+',
            'data_main' => '월 15GB + 3Mbps',
            'features' => ['통화 100분', '문자 100건', 'LG U+망', 'LTE'],
            'price_main' => '월 14,000원',
            'price_after' => '7개월 이후 30,580원',
            'selection_count' => '7,977명이 선택',
            'gifts' => [
                '유심/배송비 무료',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg', 'alt' => '유심/배송비 무료'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ],
        [
            'id' => 32634,
            'provider' => '이야기모바일',
            'rating' => '4.5',
            'title' => '이야기 완전 무제한 11GB+',
            'data_main' => '월 11GB + 매일 2GB + 3Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'],
            'price_main' => '월 15,000원',
            'price_after' => '7개월 이후 39,600원',
            'selection_count' => '13,651명이 선택',
            'gifts' => [
                '네이버페이',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ],
        [
            'id' => 32635,
            'provider' => '핀다이렉트',
            'rating' => '4.2',
            'title' => '[K] 핀다이렉트Z 7GB+(네이버페이) _2511',
            'data_main' => '월 7GB + 1Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'],
            'price_main' => '월 8,000원',
            'price_after' => '7개월 이후 26,400원',
            'selection_count' => '4,407명이 선택',
            'gifts' => [
                '추가 데이터',
                '매월 5GB 추가 데이터',
                '이마트 상품권',
                '네이버페이',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '추가 데이터'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '추가 데이터'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg', 'alt' => '이마트 상품권'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ]
    ];
    
    // limit만큼만 반환
    return array_slice($allPlans, 0, $limit);
}

/**
 * 요금제 상세 데이터 가져오기
 * @param int $plan_id 요금제 ID
 * @return array|null 요금제 데이터 또는 null
 */
function getPlanDetailData($plan_id) {
    $pdo = getDBConnection();
    if (!$pdo) {
        // DB 연결 실패 시 하드코딩 데이터에서 찾기
    $plans = getPlansData();
    foreach ($plans as $plan) {
        if ($plan['id'] == $plan_id) {
            return $plan;
        }
    }
    return null;
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
        // 로그인 체크 실패해도 상품 정보는 반환
        error_log("Warning: Failed to get current user in getPlanDetailData: " . $e->getMessage());
    } catch (Error $e) {
        // PHP 7+ Fatal Error도 처리
        error_log("Warning: Fatal error getting current user in getPlanDetailData: " . $e->getMessage());
    }

    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.seller_id,
                p.status,
                p.view_count,
                p.favorite_count,
                p.review_count,
                p.share_count,
                p.application_count,
                mvno.provider,
                mvno.service_type,
                mvno.plan_name,
                mvno.contract_period,
                mvno.contract_period_days,
                mvno.discount_period,
                mvno.price_main,
                mvno.price_after,
                mvno.data_amount,
                mvno.data_amount_value,
                mvno.data_unit,
                mvno.data_additional,
                mvno.data_additional_value,
                mvno.data_exhausted,
                mvno.data_exhausted_value,
                mvno.call_type,
                mvno.call_amount,
                mvno.additional_call_type,
                mvno.additional_call,
                mvno.sms_type,
                mvno.sms_amount,
                mvno.mobile_hotspot,
                mvno.mobile_hotspot_value,
                mvno.regular_sim_available,
                mvno.regular_sim_price,
                mvno.nfc_sim_available,
                mvno.nfc_sim_price,
                mvno.esim_available,
                mvno.esim_price,
                mvno.over_data_price,
                mvno.over_voice_price,
                mvno.over_video_price,
                mvno.over_sms_price,
                mvno.over_lms_price,
                mvno.over_mms_price,
                mvno.promotion_title,
                mvno.promotions,
                mvno.benefits,
                mvno.registration_types,
                mvno.redirect_url
            FROM products p
            INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
            WHERE p.id = :plan_id 
            AND p.product_type = 'mvno'
            AND p.status != 'deleted'
        ");
        
        $stmt->bindValue(':plan_id', $plan_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $planCard = convertMvnoProductToPlanCard($product);
            
            // 찜 상태 확인 (에러 발생 시 무시)
            $isFavorited = false;
            if ($currentUserId) {
                try {
                    $favStmt = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM product_favorites 
                        WHERE user_id = :user_id 
                        AND product_id = :product_id
                        AND product_type = 'mvno'
                    ");
                    $favStmt->execute([
                        ':user_id' => $currentUserId,
                        ':product_id' => $plan_id
                    ]);
                    $favCount = $favStmt->fetchColumn();
                    $isFavorited = ($favCount > 0);
                } catch (Exception $e) {
                    // 찜 상태 조회 실패해도 상품 정보는 반환
                    error_log("Warning: Failed to get favorite status in getPlanDetailData: " . $e->getMessage());
                }
            }
            
            $planCard['is_favorited'] = $isFavorited;
            
            // 원본 상세 데이터도 함께 반환 (상세 페이지에서 사용)
            $planCard['_raw_data'] = $product;
            return $planCard;
        }
        
        // DB에서 찾지 못하면 하드코딩 데이터에서 찾기
        $plans = getPlansData();
        foreach ($plans as $plan) {
            if ($plan['id'] == $plan_id) {
                return $plan;
            }
        }
        
        return null;
    } catch (PDOException $e) {
        error_log("Error fetching plan detail from DB: " . $e->getMessage());
        // 에러 발생 시 하드코딩 데이터에서 찾기
        $plans = getPlansData();
        foreach ($plans as $plan) {
            if ($plan['id'] == $plan_id) {
                return $plan;
            }
        }
        return null;
    }
}
