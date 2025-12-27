<?php
/**
 * 중복 리뷰 확인 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/check-duplicate-reviews.php
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>중복 리뷰 확인</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 30px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        tr:hover { background-color: #e8f5e9; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; }
        .duplicate { background-color: #ffebee !important; }
        .action-btn { 
            display: inline-block; 
            padding: 8px 16px; 
            margin: 5px; 
            background: #f44336; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            cursor: pointer;
        }
        .action-btn:hover { background: #d32f2f; }
        .action-btn.delete { background: #f44336; }
        .action-btn.delete:hover { background: #d32f2f; }
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
    
    echo "<h1>중복 리뷰 확인</h1>";
    echo "<p class='success'>✓ 데이터베이스 연결 성공</p>";
    
    // 1. product_id=9, user_id='q2222222'의 모든 리뷰 확인
    echo "<h2>1. q2222222 사용자의 product_id=9 리뷰 목록</h2>";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            product_id,
            product_type,
            rating,
            kindness_rating,
            speed_rating,
            title,
            content,
            status,
            created_at,
            updated_at
        FROM product_reviews 
        WHERE product_id = :product_id 
        AND user_id = :user_id
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([
        ':product_id' => 9,
        ':user_id' => 'q2222222'
    ]);
    
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reviews)) {
        echo "<p class='warning'>리뷰를 찾을 수 없습니다.</p>";
    } else {
        echo "<p class='info'>총 <strong>" . count($reviews) . "개</strong>의 리뷰가 있습니다.</p>";
        
        echo "<table>";
        echo "<tr>
            <th>ID</th>
            <th>상태</th>
            <th>별점</th>
            <th>친절해요</th>
            <th>설치빨라요</th>
            <th>제목</th>
            <th>내용 (일부)</th>
            <th>작성일시</th>
            <th>수정일시</th>
            <th>작업</th>
        </tr>";
        
        foreach ($reviews as $review) {
            $statusClass = '';
            if ($review['status'] === 'deleted') {
                $statusClass = 'error';
            } elseif ($review['status'] === 'approved') {
                $statusClass = 'success';
            } else {
                $statusClass = 'warning';
            }
            
            $contentPreview = mb_substr($review['content'], 0, 50);
            if (mb_strlen($review['content']) > 50) {
                $contentPreview .= '...';
            }
            
            echo "<tr>";
            echo "<td>{$review['id']}</td>";
            echo "<td class='{$statusClass}'>{$review['status']}</td>";
            echo "<td>{$review['rating']}</td>";
            echo "<td>" . ($review['kindness_rating'] ?? '-') . "</td>";
            echo "<td>" . ($review['speed_rating'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($review['title'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($contentPreview) . "</td>";
            echo "<td>{$review['created_at']}</td>";
            echo "<td>{$review['updated_at']}</td>";
            echo "<td>";
            if ($review['status'] !== 'deleted') {
                echo "<a href='?delete_id={$review['id']}' class='action-btn delete' onclick='return confirm(\"정말 삭제하시겠습니까?\");'>삭제</a>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 2. product_id=9의 모든 리뷰 확인 (모든 사용자)
    echo "<h2>2. product_id=9의 모든 리뷰 (모든 사용자)</h2>";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            product_id,
            product_type,
            rating,
            kindness_rating,
            speed_rating,
            status,
            created_at
        FROM product_reviews 
        WHERE product_id = :product_id
        AND status != 'deleted'
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([':product_id' => 9]);
    $allReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'>총 <strong>" . count($allReviews) . "개</strong>의 활성 리뷰가 있습니다.</p>";
    
    echo "<table>";
    echo "<tr>
        <th>ID</th>
        <th>사용자 ID</th>
        <th>별점</th>
        <th>친절해요</th>
        <th>설치빨라요</th>
        <th>상태</th>
        <th>작성일시</th>
    </tr>";
    
    foreach ($allReviews as $review) {
        echo "<tr>";
        echo "<td>{$review['id']}</td>";
        echo "<td><strong>{$review['user_id']}</strong></td>";
        echo "<td>{$review['rating']}</td>";
        echo "<td>" . ($review['kindness_rating'] ?? '-') . "</td>";
        echo "<td>" . ($review['speed_rating'] ?? '-') . "</td>";
        echo "<td>{$review['status']}</td>";
        echo "<td>{$review['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. 중복 리뷰 확인 (같은 사용자가 같은 상품에 여러 리뷰 작성)
    echo "<h2>3. 중복 리뷰 확인 (같은 사용자 + 같은 상품)</h2>";
    
    $stmt = $pdo->query("
        SELECT 
            product_id,
            user_id,
            COUNT(*) as review_count,
            GROUP_CONCAT(id ORDER BY created_at DESC) as review_ids,
            GROUP_CONCAT(status ORDER BY created_at DESC) as statuses
        FROM product_reviews
        WHERE status != 'deleted'
        GROUP BY product_id, user_id
        HAVING review_count > 1
        ORDER BY review_count DESC
    ");
    
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        echo "<p class='success'>✓ 중복 리뷰가 없습니다.</p>";
    } else {
        echo "<p class='warning'>⚠ 총 <strong>" . count($duplicates) . "개</strong>의 중복 리뷰 그룹이 있습니다.</p>";
        
        echo "<table>";
        echo "<tr>
            <th>상품 ID</th>
            <th>사용자 ID</th>
            <th>리뷰 개수</th>
            <th>리뷰 ID 목록</th>
            <th>상태 목록</th>
        </tr>";
        
        foreach ($duplicates as $dup) {
            $isHighlight = ($dup['product_id'] == 9 && $dup['user_id'] == 'q2222222');
            $rowClass = $isHighlight ? 'duplicate' : '';
            
            echo "<tr class='{$rowClass}'>";
            echo "<td>{$dup['product_id']}</td>";
            echo "<td><strong>{$dup['user_id']}</strong></td>";
            echo "<td class='error'>{$dup['review_count']}</td>";
            echo "<td>{$dup['review_ids']}</td>";
            echo "<td>{$dup['statuses']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. 삭제 처리
    if (isset($_GET['delete_id'])) {
        $deleteId = intval($_GET['delete_id']);
        
        $stmt = $pdo->prepare("
            UPDATE product_reviews 
            SET status = 'deleted', updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        
        if ($stmt->execute([':id' => $deleteId])) {
            echo "<p class='success'>✓ 리뷰 ID {$deleteId}가 삭제되었습니다.</p>";
            echo "<script>setTimeout(function(){ window.location.href = window.location.pathname; }, 1000);</script>";
        } else {
            echo "<p class='error'>✗ 리뷰 삭제 실패</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>








