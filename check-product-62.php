<?php
/**
 * 상품 ID 62의 DB 데이터 확인 스크립트
 */

require_once __DIR__ . '/includes/data/db-config.php';

$productId = 62;

echo "<h2>상품 ID {$productId} 데이터 확인</h2>";

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("데이터베이스 연결 실패");
    }
    
    echo "<h3>1. products 테이블 확인</h3>";
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :product_id");
    $stmt->execute([':product_id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "<pre style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
        echo "✅ products 테이블에 데이터가 있습니다:\n\n";
        print_r($product);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>❌ products 테이블에 데이터가 없습니다.</p>";
    }
    
    echo "<h3>2. product_mno_sim_details 테이블 확인</h3>";
    $stmt = $pdo->prepare("SELECT * FROM product_mno_sim_details WHERE product_id = :product_id");
    $stmt->execute([':product_id' => $productId]);
    $productDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($productDetail) {
        echo "<pre style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
        echo "✅ product_mno_sim_details 테이블에 데이터가 있습니다:\n\n";
        print_r($productDetail);
        echo "</pre>";
        
        // 주요 필드 확인
        echo "<h4>주요 필드 값:</h4>";
        echo "<ul>";
        echo "<li>discount_period: " . ($productDetail['discount_period'] ?? 'NULL') . "</li>";
        echo "<li>call_type: " . ($productDetail['call_type'] ?? 'NULL') . "</li>";
        echo "<li>sms_type: " . ($productDetail['sms_type'] ?? 'NULL') . "</li>";
        echo "<li>data_amount: " . ($productDetail['data_amount'] ?? 'NULL') . "</li>";
        echo "<li>data_additional: " . ($productDetail['data_additional'] ?? 'NULL') . "</li>";
        echo "<li>data_exhausted: " . ($productDetail['data_exhausted'] ?? 'NULL') . "</li>";
        echo "<li>additional_call_type: " . ($productDetail['additional_call_type'] ?? 'NULL') . "</li>";
        echo "<li>mobile_hotspot: " . ($productDetail['mobile_hotspot'] ?? 'NULL') . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ product_mno_sim_details 테이블에 데이터가 없습니다.</p>";
        
        // 테이블 구조 확인
        echo "<h4>테이블 구조 확인:</h4>";
        $stmt = $pdo->query("SHOW TABLES LIKE 'product_mno_sim_details'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ 테이블은 존재합니다.</p>";
            
            // 컬럼 정보 확인
            $stmt = $pdo->query("DESCRIBE product_mno_sim_details");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
            print_r($columns);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>❌ 테이블이 존재하지 않습니다.</p>";
        }
    }
    
    // 전체 상품 개수 확인
    echo "<h3>3. 전체 통계</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE product_type = 'mno-sim'");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>전체 mno-sim 상품 수: {$total['total']}</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM product_mno_sim_details");
    $totalDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>전체 product_mno_sim_details 레코드 수: {$totalDetail['total']}</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>에러: " . $e->getMessage() . "</p>";
}
?>




