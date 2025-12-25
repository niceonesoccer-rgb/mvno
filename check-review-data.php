<?php
/**
 * 리뷰 데이터 확인 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/check-review-data.php?order_number=25122008-0016
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$orderNumber = isset($_GET['order_number']) ? trim($_GET['order_number']) : '25122008-0016';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>리뷰 데이터 확인</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 30px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; }
    </style>
</head>
<body>
<div class='container'>";

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        echo "<p class='error'>✗ 데이터베이스 연결 실패</p>";
        exit;
    }
    
    echo "<h1>리뷰 데이터 확인</h1>";
    echo "<p class='success'>✓ 데이터베이스 연결 성공</p>";
    echo "<p class='info'>주문번호: <strong>{$orderNumber}</strong></p>";
    
    // 1. 주문번호로 application_id 찾기
    echo "<h2>1. 주문번호로 신청 정보 찾기</h2>";
    
    // order_number 컬럼 존재 여부 확인
    $hasOrderNumber = false;
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM product_applications LIKE 'order_number'");
        $hasOrderNumber = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        // 컬럼이 없을 수 있음
    }
    
    $applications = [];
    if ($hasOrderNumber) {
        $stmt = $pdo->prepare("
            SELECT id, order_number, product_id, user_id, application_status, created_at
            FROM product_applications
            WHERE order_number = :order_number
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute([':order_number' => $orderNumber]);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // order_number 컬럼이 없으면 id로 직접 조회 시도
        $applicationId = is_numeric($orderNumber) ? intval($orderNumber) : null;
        if ($applicationId) {
            $stmt = $pdo->prepare("
                SELECT id, product_id, user_id, application_status, created_at
                FROM product_applications
                WHERE id = :application_id
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $stmt->execute([':application_id' => $applicationId]);
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            echo "<p class='warning'>order_number 컬럼이 없고, 주문번호가 숫자가 아닙니다. application_id를 직접 입력해주세요.</p>";
        }
    }
    
    if (empty($applications)) {
        echo "<p class='warning'>주문번호로 신청 정보를 찾을 수 없습니다.</p>";
    } else {
        echo "<table>";
        echo "<tr>
            <th>ID</th>";
        if ($hasOrderNumber) {
            echo "<th>주문번호</th>";
        }
        echo "<th>상품 ID</th>
            <th>사용자 ID</th>
            <th>상태</th>
            <th>생성일시</th>
        </tr>";
        foreach ($applications as $app) {
            echo "<tr>";
            echo "<td>{$app['id']}</td>";
            if ($hasOrderNumber) {
                echo "<td><strong>" . ($app['order_number'] ?? '-') . "</strong></td>";
            }
            echo "<td>{$app['product_id']}</td>";
            echo "<td>{$app['user_id']}</td>";
            echo "<td>" . ($app['application_status'] ?? '-') . "</td>";
            echo "<td>{$app['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 2. 각 application_id에 대한 리뷰 찾기
        foreach ($applications as $app) {
            $applicationId = $app['id'];
            $productId = $app['product_id'];
            
            echo "<h2>2. Application ID {$applicationId}의 리뷰</h2>";
            
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    product_id,
                    user_id,
                    product_type,
                    rating,
                    kindness_rating,
                    speed_rating,
                    title,
                    content,
                    status,
                    application_id,
                    created_at,
                    updated_at
                FROM product_reviews
                WHERE application_id = :application_id
                OR (product_id = :product_id AND user_id = :user_id)
                ORDER BY created_at DESC
            ");
            $stmt->execute([
                ':application_id' => $applicationId,
                ':product_id' => $productId,
                ':user_id' => $app['user_id']
            ]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($reviews)) {
                echo "<p class='warning'>리뷰를 찾을 수 없습니다.</p>";
            } else {
                echo "<table>";
                echo "<tr>
                    <th>ID</th>
                    <th>상품 ID</th>
                    <th>사용자 ID</th>
                    <th>별점</th>
                    <th>친절해요</th>
                    <th>설치빨라요</th>
                    <th>상태</th>
                    <th>신청 ID</th>
                    <th>작성일시</th>
                    <th>수정일시</th>
                </tr>";
                foreach ($reviews as $review) {
                    $kindnessClass = '';
                    $speedClass = '';
                    if ($review['kindness_rating'] == 5 && $review['speed_rating'] == 3) {
                        $kindnessClass = 'error';
                        $speedClass = 'success';
                    }
                    
                    echo "<tr>";
                    echo "<td>{$review['id']}</td>";
                    echo "<td>{$review['product_id']}</td>";
                    echo "<td>{$review['user_id']}</td>";
                    echo "<td>{$review['rating']}</td>";
                    echo "<td class='{$kindnessClass}'><strong>{$review['kindness_rating']}</strong></td>";
                    echo "<td class='{$speedClass}'><strong>{$review['speed_rating']}</strong></td>";
                    echo "<td>{$review['status']}</td>";
                    echo "<td>" . ($review['application_id'] ?? '-') . "</td>";
                    echo "<td>{$review['created_at']}</td>";
                    echo "<td>{$review['updated_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
    }
    
    // 3. 최근 리뷰 10개 확인
    echo "<h2>3. 최근 리뷰 10개 (모든 주문번호)</h2>";
    
    $selectOrderNumber = $hasOrderNumber ? 'a.order_number' : 'NULL as order_number';
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.product_id,
            r.user_id,
            r.rating,
            r.kindness_rating,
            r.speed_rating,
            r.status,
            r.created_at,
            $selectOrderNumber
        FROM product_reviews r
        LEFT JOIN product_applications a ON r.application_id = a.id
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $recentReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($recentReviews)) {
        echo "<table>";
        echo "<tr>
            <th>ID</th>
            <th>주문번호</th>
            <th>상품 ID</th>
            <th>사용자 ID</th>
            <th>별점</th>
            <th>친절해요</th>
            <th>설치빨라요</th>
            <th>상태</th>
            <th>작성일시</th>
        </tr>";
        foreach ($recentReviews as $review) {
            echo "<tr>";
            echo "<td>{$review['id']}</td>";
            echo "<td>" . ($review['order_number'] ?? '-') . "</td>";
            echo "<td>{$review['product_id']}</td>";
            echo "<td>{$review['user_id']}</td>";
            echo "<td>{$review['rating']}</td>";
            echo "<td><strong>{$review['kindness_rating']}</strong></td>";
            echo "<td><strong>{$review['speed_rating']}</strong></td>";
            echo "<td>{$review['status']}</td>";
            echo "<td>{$review['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>






