<?php
/**
 * product_reviews 테이블에 mno-sim 타입 추가 및 필요한 컬럼 추가
 * 실행 방법: 브라우저에서 http://localhost/MVNO/database/update_product_reviews_for_mno_sim.php 접속
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>product_reviews 테이블 업데이트</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 10px;
        }
        .success {
            color: #10b981;
            background: #d1fae5;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            color: #ef4444;
            background: #fee2e2;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .info {
            color: #6366f1;
            background: #e0e7ff;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        pre {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>product_reviews 테이블 업데이트</h1>
        
        <?php
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                throw new Exception("데이터베이스 연결 실패");
            }
            
            echo "<div class='info'>데이터베이스 연결 성공</div>";
            
            // 1. product_type ENUM에 'mno-sim' 추가
            echo "<h2>1. product_type ENUM에 'mno-sim' 추가</h2>";
            try {
                // 현재 ENUM 값 확인
                $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews WHERE Field = 'product_type'");
                $columnInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);
                $enumValues = $columnInfo['Type'];
                
                echo "<div class='info'>현재 product_type: $enumValues</div>";
                
                // 'mno-sim'이 이미 포함되어 있는지 확인
                if (strpos($enumValues, 'mno-sim') === false) {
                    // ENUM에 'mno-sim' 추가
                    $pdo->exec("ALTER TABLE product_reviews MODIFY COLUMN product_type ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입'");
                    echo "<div class='success'>✓ product_type ENUM에 'mno-sim' 추가 완료</div>";
                } else {
                    echo "<div class='info'>✓ product_type ENUM에 'mno-sim'이 이미 포함되어 있습니다.</div>";
                }
            } catch (PDOException $e) {
                echo "<div class='error'>✗ product_type ENUM 수정 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            
            // 2. application_id 컬럼 확인 및 추가
            echo "<h2>2. application_id 컬럼 확인</h2>";
            try {
                $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'application_id'");
                if ($checkStmt->rowCount() > 0) {
                    echo "<div class='info'>✓ application_id 컬럼이 이미 존재합니다.</div>";
                } else {
                    $pdo->exec("ALTER TABLE product_reviews ADD COLUMN application_id INT(11) UNSIGNED NULL DEFAULT NULL COMMENT '신청 ID (주문별 리뷰 구분용)' AFTER product_id");
                    $pdo->exec("ALTER TABLE product_reviews ADD INDEX idx_application_id (application_id)");
                    echo "<div class='success'>✓ application_id 컬럼 추가 완료</div>";
                }
            } catch (PDOException $e) {
                echo "<div class='error'>✗ application_id 컬럼 확인/추가 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            
            // 3. kindness_rating 컬럼 확인 및 추가
            echo "<h2>3. kindness_rating 컬럼 확인</h2>";
            try {
                $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'kindness_rating'");
                if ($checkStmt->rowCount() > 0) {
                    echo "<div class='info'>✓ kindness_rating 컬럼이 이미 존재합니다.</div>";
                } else {
                    $pdo->exec("ALTER TABLE product_reviews ADD COLUMN kindness_rating TINYINT(1) UNSIGNED NULL DEFAULT NULL COMMENT '친절해요 평점 (1-5)' AFTER rating");
                    echo "<div class='success'>✓ kindness_rating 컬럼 추가 완료</div>";
                }
            } catch (PDOException $e) {
                echo "<div class='error'>✗ kindness_rating 컬럼 확인/추가 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            
            // 4. speed_rating 컬럼 확인 및 추가
            echo "<h2>4. speed_rating 컬럼 확인</h2>";
            try {
                $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'speed_rating'");
                if ($checkStmt->rowCount() > 0) {
                    echo "<div class='info'>✓ speed_rating 컬럼이 이미 존재합니다.</div>";
                } else {
                    $pdo->exec("ALTER TABLE product_reviews ADD COLUMN speed_rating TINYINT(1) UNSIGNED NULL DEFAULT NULL COMMENT '개통/설치 빨라요 평점 (1-5)' AFTER kindness_rating");
                    echo "<div class='success'>✓ speed_rating 컬럼 추가 완료</div>";
                }
            } catch (PDOException $e) {
                echo "<div class='error'>✗ speed_rating 컬럼 확인/추가 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            
            // 5. 최종 테이블 구조 확인
            echo "<h2>5. 최종 테이블 구조 확인</h2>";
            $columnsStmt = $pdo->query("SHOW COLUMNS FROM product_reviews");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<pre>";
            echo "product_reviews 테이블 컬럼 목록:\n\n";
            foreach ($columns as $col) {
                echo sprintf("%-20s %-30s %s\n", $col['Field'], $col['Type'], $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL');
            }
            echo "</pre>";
            
            // 6. mno-sim 리뷰 개수 확인
            echo "<h2>6. mno-sim 리뷰 개수 확인</h2>";
            try {
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM product_reviews WHERE product_type = 'mno-sim'");
                $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "<div class='info'>현재 mno-sim 리뷰 개수: <strong>$count</strong>개</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>리뷰 개수 확인 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            
            echo "<div class='success' style='margin-top: 30px;'><strong>✓ 모든 업데이트가 완료되었습니다!</strong></div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
    </div>
</body>
</html>


