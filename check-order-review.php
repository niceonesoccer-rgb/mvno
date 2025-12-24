<?php
/**
 * 주문번호별 리뷰 데이터 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';

$orderNumber = isset($_GET['order_number']) ? trim($_GET['order_number']) : '25122011-0002';

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

echo "<h2>주문번호: $orderNumber</h2>";

// 1. application_id 찾기
$stmt = $pdo->prepare("
    SELECT id, product_id, user_id, order_number, application_status
    FROM product_applications
    WHERE order_number = :order_number
");
$stmt->execute([':order_number' => $orderNumber]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    die("주문번호 '$orderNumber'에 해당하는 신청 정보를 찾을 수 없습니다.");
}

$applicationId = $application['id'];
echo "<h3>신청 정보 (Application ID: $applicationId)</h3>";
echo "<pre>";
print_r($application);
echo "</pre>";

// 2. 해당 application_id의 리뷰 찾기
$stmt = $pdo->prepare("
    SELECT 
        id,
        product_id,
        user_id,
        application_id,
        rating,
        kindness_rating,
        speed_rating,
        title,
        content,
        status,
        created_at
    FROM product_reviews
    WHERE application_id = :application_id
    AND status != 'deleted'
");
$stmt->execute([':application_id' => $applicationId]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>리뷰 데이터</h3>";
if (empty($reviews)) {
    echo "<p>리뷰가 없습니다.</p>";
} else {
    foreach ($reviews as $review) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<strong>리뷰 ID:</strong> " . $review['id'] . "<br>";
        echo "<strong>Product ID:</strong> " . $review['product_id'] . "<br>";
        echo "<strong>User ID:</strong> " . $review['user_id'] . "<br>";
        echo "<strong>Application ID:</strong> " . $review['application_id'] . "<br>";
        echo "<strong>Rating (총점):</strong> " . $review['rating'] . "<br>";
        echo "<strong>Kindness Rating (친절해요):</strong> " . ($review['kindness_rating'] ?? 'NULL') . "<br>";
        echo "<strong>Speed Rating (설치 빨라요):</strong> " . ($review['speed_rating'] ?? 'NULL') . "<br>";
        
        // 평균 계산
        if ($review['kindness_rating'] !== null && $review['speed_rating'] !== null) {
            $calculatedAverage = ($review['kindness_rating'] + $review['speed_rating']) / 2;
            echo "<strong>계산된 평균:</strong> " . number_format($calculatedAverage, 2) . " (친절: {$review['kindness_rating']}, 속도: {$review['speed_rating']})<br>";
            echo "<strong>저장된 rating과 일치:</strong> " . ($review['rating'] == round($calculatedAverage) ? 'YES' : 'NO') . "<br>";
        }
        
        echo "<strong>Title:</strong> " . htmlspecialchars($review['title'] ?? '') . "<br>";
        echo "<strong>Content:</strong> " . htmlspecialchars($review['content']) . "<br>";
        echo "<strong>Status:</strong> " . $review['status'] . "<br>";
        echo "<strong>Created At:</strong> " . $review['created_at'] . "<br>";
        echo "</div>";
    }
    
    // 주문별 평균 계산
    if (count($reviews) > 0) {
        $totalKindness = 0;
        $totalSpeed = 0;
        $count = 0;
        
        foreach ($reviews as $review) {
            if ($review['kindness_rating'] !== null && $review['speed_rating'] !== null) {
                $totalKindness += $review['kindness_rating'];
                $totalSpeed += $review['speed_rating'];
                $count++;
            }
        }
        
        if ($count > 0) {
            $avgKindness = $totalKindness / $count;
            $avgSpeed = $totalSpeed / $count;
            $avgOverall = ($avgKindness + $avgSpeed) / 2;
            
            // 소수 둘째자리에서 올림하여 소수 첫째자리까지 표시
            $avgKindness = ceil($avgKindness * 10) / 10;
            $avgSpeed = ceil($avgSpeed * 10) / 10;
            $avgOverall = ceil($avgOverall * 10) / 10;
            
            echo "<h3>주문별 평균 별점 (소수 첫째자리까지, 둘째자리에서 올림)</h3>";
            echo "<p><strong>친절해요 평균:</strong> " . number_format($avgKindness, 1) . "</p>";
            echo "<p><strong>설치 빨라요 평균:</strong> " . number_format($avgSpeed, 1) . "</p>";
            echo "<p><strong>전체 평균:</strong> " . number_format($avgOverall, 1) . "</p>";
            echo "<p><strong>별 표시 예시:</strong></p>";
            echo "<ul>";
            echo "<li>2.5점 → 별 2개 + 반개 (50% 채워진 별 1개)</li>";
            echo "<li>2.4점 → 별 2개 + 반개 (40% 채워진 별 1개)</li>";
            echo "<li>2.1점 → 별 2개 + 반개 (10% 채워진 별 1개)</li>";
            echo "</ul>";
        }
    }
}

// 3. 통계 테이블 확인
$stmt = $pdo->prepare("
    SELECT 
        product_id,
        total_rating_sum,
        total_review_count,
        kindness_rating_sum,
        kindness_review_count,
        speed_rating_sum,
        speed_review_count
    FROM product_review_statistics
    WHERE product_id = :product_id
");
$stmt->execute([':product_id' => $application['product_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>통계 테이블 데이터 (Product ID: {$application['product_id']})</h3>";
if ($stats) {
    echo "<pre>";
    print_r($stats);
    echo "</pre>";
    
    if ($stats['total_review_count'] > 0) {
        $productAvg = $stats['total_rating_sum'] / $stats['total_review_count'];
        $productAvg = ceil($productAvg * 10) / 10; // 소수 둘째자리에서 올림
        echo "<p><strong>상품 평균 별점:</strong> " . number_format($productAvg, 1) . "</p>";
    }
    
    if ($stats['kindness_review_count'] > 0) {
        $kindnessAvg = $stats['kindness_rating_sum'] / $stats['kindness_review_count'];
        $kindnessAvg = ceil($kindnessAvg * 10) / 10; // 소수 둘째자리에서 올림
        echo "<p><strong>친절해요 평균:</strong> " . number_format($kindnessAvg, 1) . "</p>";
    }
    
    if ($stats['speed_review_count'] > 0) {
        $speedAvg = $stats['speed_rating_sum'] / $stats['speed_review_count'];
        $speedAvg = ceil($speedAvg * 10) / 10; // 소수 둘째자리에서 올림
        echo "<p><strong>설치 빨라요 평균:</strong> " . number_format($speedAvg, 1) . "</p>";
    }
} else {
    echo "<p>통계 데이터가 없습니다.</p>";
}




