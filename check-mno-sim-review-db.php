<?php
/**
 * 통신사단독유심 리뷰 DB 확인 스크립트
 * http://localhost/mvno/check-mno-sim-review-db.php?application_id=372&product_id=63
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('DB 연결 실패');
}

// GET 파라미터로 application_id와 product_id 받기
$applicationId = isset($_GET['application_id']) ? $_GET['application_id'] : '';
$productId = isset($_GET['product_id']) ? $_GET['product_id'] : '';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>통신사단독유심 리뷰 DB 확인</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #f0f0f0; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
        form { margin: 20px 0; padding: 15px; background: #fff; border-radius: 5px; }
        input[type='text'], input[type='number'] { padding: 8px; margin: 5px; width: 200px; }
        button { padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #4f46e5; }
    </style>
</head>
<body>
    <h1>통신사단독유심 리뷰 DB 확인</h1>
    
    <div class='section'>
        <h2>조회 조건 입력</h2>
        <form method='GET'>
            <label>Application ID: <input type='number' name='application_id' value='" . htmlspecialchars($applicationId) . "'></label><br>
            <label>Product ID: <input type='number' name='product_id' value='" . htmlspecialchars($productId) . "'></label><br>
            <button type='submit'>조회</button>
        </form>
    </div>";

if ($applicationId && $productId) {
    echo "<div class='section'>";
    echo "<h2>조회 결과</h2>";
    
    // application_id를 정수로 변환
    $applicationIdInt = is_numeric($applicationId) ? (int)$applicationId : 0;
    
    echo "<p><strong>조회 조건:</strong></p>";
    echo "<ul>";
    echo "<li>Application ID: " . htmlspecialchars($applicationId) . " (type: " . gettype($applicationId) . ", int: $applicationIdInt)</li>";
    echo "<li>Product ID: " . htmlspecialchars($productId) . "</li>";
    echo "</ul>";
    
    // 1. product_reviews 테이블에서 리뷰 조회
    try {
        $stmt = $pdo->prepare("
            SELECT id, application_id, product_id, user_id, product_type, status, rating, content, created_at
            FROM product_reviews 
            WHERE application_id = :application_id 
            AND product_id = :product_id 
            AND product_type = 'mno-sim'
            AND status != 'deleted'
            ORDER BY created_at DESC
        ");
        $stmt->execute([
            ':application_id' => $applicationIdInt,
            ':product_id' => (int)$productId
        ]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>1. product_reviews 테이블 조회 (application_id = $applicationIdInt)</h3>";
        if (!empty($reviews)) {
            echo "<p class='success'>✅ 리뷰 " . count($reviews) . "개 발견</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Application ID</th><th>Product ID</th><th>User ID</th><th>Product Type</th><th>Status</th><th>Rating</th><th>Created At</th></tr>";
            foreach ($reviews as $review) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($review['id']) . "</td>";
                echo "<td>" . htmlspecialchars($review['application_id']) . " (type: " . gettype($review['application_id']) . ")</td>";
                echo "<td>" . htmlspecialchars($review['product_id']) . "</td>";
                echo "<td>" . htmlspecialchars($review['user_id']) . "</td>";
                echo "<td>" . htmlspecialchars($review['product_type']) . "</td>";
                echo "<td>" . htmlspecialchars($review['status']) . "</td>";
                echo "<td>" . htmlspecialchars($review['rating']) . "</td>";
                echo "<td>" . htmlspecialchars($review['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ 리뷰를 찾을 수 없습니다.</p>";
            
            // 2. 조건을 완화하여 조회 (application_id 정수로 변환)
            echo "<h3>2. 조건 완화하여 조회 (application_id 정수로 변환)</h3>";
            $stmt2 = $pdo->prepare("
                SELECT id, application_id, product_id, user_id, product_type, status, rating, created_at
                FROM product_reviews 
                WHERE application_id = :application_id_int
                AND product_id = :product_id 
                AND product_type IN ('mno-sim', 'mno')
                ORDER BY created_at DESC
                LIMIT 10
            ");
            $stmt2->execute([
                ':application_id_int' => $applicationIdInt,
                ':product_id' => (int)$productId
            ]);
            $reviews2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($reviews2)) {
                echo "<p class='info'>🔍 조건 완화 시 리뷰 " . count($reviews2) . "개 발견</p>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Application ID</th><th>Product ID</th><th>User ID</th><th>Product Type</th><th>Status</th><th>Rating</th><th>Created At</th></tr>";
                foreach ($reviews2 as $review) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($review['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($review['application_id']) . " (type: " . gettype($review['application_id']) . ")</td>";
                    echo "<td>" . htmlspecialchars($review['product_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($review['user_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($review['product_type']) . "</td>";
                    echo "<td>" . htmlspecialchars($review['status']) . "</td>";
                    echo "<td>" . htmlspecialchars($review['rating']) . "</td>";
                    echo "<td>" . htmlspecialchars($review['created_at']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>❌ 조건 완화해도 리뷰를 찾을 수 없습니다.</p>";
            }
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ DB 조회 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // 3. product_applications 테이블에서 application_id 확인
    echo "<h3>3. product_applications 테이블에서 application_id 확인</h3>";
    try {
        $stmt3 = $pdo->prepare("
            SELECT id, product_id, product_type, application_status, created_at
            FROM product_applications 
            WHERE id = :application_id
            LIMIT 1
        ");
        $stmt3->execute([':application_id' => $applicationIdInt]);
        $app = $stmt3->fetch(PDO::FETCH_ASSOC);
        
        if ($app) {
            echo "<p class='success'>✅ Application 정보 발견</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Product ID</th><th>Product Type</th><th>Application Status</th><th>Created At</th></tr>";
            echo "<tr>";
            echo "<td>" . htmlspecialchars($app['id']) . "</td>";
            echo "<td>" . htmlspecialchars($app['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($app['product_type']) . "</td>";
            echo "<td>" . htmlspecialchars($app['application_status']) . "</td>";
            echo "<td>" . htmlspecialchars($app['created_at']) . "</td>";
            echo "</tr>";
            echo "</table>";
        } else {
            echo "<p class='error'>❌ Application 정보를 찾을 수 없습니다.</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ DB 조회 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // 4. product_id=63인 모든 mno-sim 리뷰 조회 (모든 status 포함)
    echo "<h3>4. product_id=" . htmlspecialchars($productId) . "인 모든 mno-sim 리뷰 조회 (모든 status 포함)</h3>";
    
    // 먼저 product_mno_sim_details 테이블에 해당 상품이 있는지 확인
    echo "<h4>4-0. product_mno_sim_details 테이블 확인</h4>";
    try {
        $checkStmt = $pdo->prepare("SELECT product_id FROM product_mno_sim_details WHERE product_id = :product_id LIMIT 1");
        $checkStmt->execute([':product_id' => (int)$productId]);
        $productExists = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($productExists) {
            echo "<p class='success'>✅ product_mno_sim_details 테이블에 product_id=" . htmlspecialchars($productId) . " 존재</p>";
        } else {
            echo "<p class='error'>❌ product_mno_sim_details 테이블에 product_id=" . htmlspecialchars($productId) . " 없음 (getProductReviews 함수에서 INNER JOIN 때문에 리뷰가 조회되지 않을 수 있음)</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ DB 조회 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    try {
        $stmt4 = $pdo->prepare("
            SELECT id, application_id, product_id, user_id, product_type, status, rating, created_at
            FROM product_reviews 
            WHERE product_id = :product_id
            AND product_type = 'mno-sim'
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt4->execute([':product_id' => (int)$productId]);
        $allReviews = $stmt4->fetchAll(PDO::FETCH_ASSOC);
        
        // getProductReviews 함수 사용해서도 확인
        echo "<h4>4-1. getProductReviews 함수로 조회 (status = 'approved'만, INNER JOIN 사용)</h4>";
        require_once __DIR__ . '/includes/data/plan-data.php';
        if (function_exists('getProductReviews')) {
            $functionReviews = getProductReviews((int)$productId, 'mno-sim', 50, 'created_desc');
            echo "<p class='info'>🔍 getProductReviews 함수 결과: " . count($functionReviews) . "개</p>";
            if (!empty($functionReviews)) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Application ID</th><th>User ID</th><th>Rating</th><th>Content (일부)</th><th>Created At</th></tr>";
                foreach (array_slice($functionReviews, 0, 10) as $review) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($review['id'] ?? 'N/A') . "</td>";
                    $appIdDisplay = isset($review['application_id']) && $review['application_id'] !== null ? htmlspecialchars($review['application_id']) : 'NULL';
                    echo "<td>$appIdDisplay</td>";
                    echo "<td>" . htmlspecialchars($review['user_id'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($review['rating'] ?? 'N/A') . "</td>";
                    $contentPreview = isset($review['content']) ? mb_substr($review['content'], 0, 30) : '';
                    echo "<td>" . htmlspecialchars($contentPreview) . "...</td>";
                    echo "<td>" . htmlspecialchars($review['created_at'] ?? 'N/A') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
        if (!empty($allReviews)) {
            echo "<p class='success'>✅ product_id=" . htmlspecialchars($productId) . "인 mno-sim 리뷰 " . count($allReviews) . "개 발견!</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Application ID</th><th>Product ID</th><th>User ID</th><th>Status</th><th>Rating</th><th>Created At</th></tr>";
            foreach ($allReviews as $review) {
                $highlight = ($review['application_id'] == $applicationIdInt) ? ' style="background: #fef3c7;"' : '';
                echo "<tr$highlight>";
                echo "<td>" . htmlspecialchars($review['id']) . "</td>";
                $appIdDisplay = $review['application_id'] === null ? 'NULL' : htmlspecialchars($review['application_id']);
                echo "<td>$appIdDisplay (type: " . gettype($review['application_id']) . ")</td>";
                echo "<td>" . htmlspecialchars($review['product_id']) . "</td>";
                echo "<td>" . htmlspecialchars($review['user_id']) . "</td>";
                echo "<td>" . htmlspecialchars($review['status']) . "</td>";
                echo "<td>" . htmlspecialchars($review['rating'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($review['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // application_id=410인 리뷰가 있는지 확인
            $found410 = false;
            foreach ($allReviews as $review) {
                if ($review['application_id'] == $applicationIdInt) {
                    $found410 = true;
                    break;
                }
            }
            if ($found410) {
                echo "<p class='success'>✅ application_id=" . htmlspecialchars($applicationId) . "인 리뷰가 있습니다!</p>";
            } else {
                echo "<p class='error'>❌ application_id=" . htmlspecialchars($applicationId) . "인 리뷰는 없습니다. 다른 application_id로 저장되어 있습니다.</p>";
            }
        } else {
            echo "<p class='error'>❌ product_id=" . htmlspecialchars($productId) . "인 mno-sim 리뷰가 DB에 없습니다.</p>";
            echo "<p class='info'>💡 상품 상세 페이지에는 리뷰가 표시되지만, DB에는 리뷰가 없습니다. 다른 DB를 사용하거나 캐시된 데이터일 수 있습니다.</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ DB 조회 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "</div>";
}

echo "</body></html>";
?>
