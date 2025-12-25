<?php
/**
 * 데이터베이스 필드 확인 스크립트
 * product_favorites 테이블의 product_type 필드에 mno-sim이 포함되어 있는지 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>DB 필드 확인</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;} .success{color:green;} .error{color:red;} table{border-collapse:collapse;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#f2f2f2;}</style>";
echo "</head><body>";

echo "<h1>데이터베이스 필드 확인</h1>";

$pdo = getDBConnection();
if (!$pdo) {
    echo "<p class='error'>데이터베이스 연결 실패</p>";
    exit;
}

// 1. product_favorites 테이블의 product_type 컬럼 확인
echo "<h2>1. product_favorites 테이블의 product_type 필드 확인</h2>";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM product_favorites WHERE Field = 'product_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "<p><strong>필드명:</strong> " . htmlspecialchars($column['Field']) . "</p>";
        echo "<p><strong>타입:</strong> " . htmlspecialchars($column['Type']) . "</p>";
        echo "<p><strong>Null:</strong> " . htmlspecialchars($column['Null']) . "</p>";
        echo "<p><strong>기본값:</strong> " . ($column['Default'] ?? 'NULL') . "</p>";
        
        // mno-sim이 포함되어 있는지 확인
        $typeStr = $column['Type'];
        if (stripos($typeStr, 'mno-sim') !== false) {
            echo "<p class='success'><strong>✓ mno-sim 타입이 지원됩니다!</strong></p>";
        } else {
            echo "<p class='error'><strong>✗ mno-sim 타입이 지원되지 않습니다.</strong></p>";
            echo "<p>다음 SQL을 실행하세요:</p>";
            echo "<pre style='background:#f5f5f5;padding:10px;border:1px solid #ddd;'>";
            echo "USE `mvno_db`;\n\n";
            echo "ALTER TABLE `product_favorites` \n";
            echo "MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입';";
            echo "</pre>";
        }
    } else {
        echo "<p class='error'>product_type 컬럼을 찾을 수 없습니다.</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 2. ENUM 값 직접 확인
echo "<h2>2. ENUM 값 상세 확인</h2>";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM product_favorites WHERE Field = 'product_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column && preg_match("/ENUM\s*\((.*)\)/i", $column['Type'], $matches)) {
        $enumValues = $matches[1];
        echo "<p><strong>ENUM 값:</strong> " . htmlspecialchars($enumValues) . "</p>";
        
        // 각 값 추출
        preg_match_all("/'([^']+)'/", $enumValues, $valueMatches);
        $values = $valueMatches[1];
        
        echo "<p><strong>지원되는 타입:</strong></p>";
        echo "<ul>";
        foreach ($values as $value) {
            $isMnoSim = ($value === 'mno-sim');
            $class = $isMnoSim ? 'success' : '';
            $mark = $isMnoSim ? ' ✓' : '';
            echo "<li class='{$class}'><strong>" . htmlspecialchars($value) . "</strong>{$mark}</li>";
        }
        echo "</ul>";
        
        if (!in_array('mno-sim', $values)) {
            echo "<p class='error'><strong>mno-sim이 ENUM 값에 없습니다. 마이그레이션이 필요합니다!</strong></p>";
        }
    }
} catch (PDOException $e) {
    echo "<p class='error'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 3. 실제 데이터 확인
echo "<h2>3. 실제 저장된 mno-sim 찜 데이터 확인</h2>";
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM product_favorites 
        WHERE product_type = 'mno-sim'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = (int)($result['count'] ?? 0);
    
    echo "<p><strong>찜한 통신사유심 상품 개수:</strong> " . $count . "개</p>";
    
    if ($count > 0) {
        $stmt = $pdo->query("
            SELECT 
                pf.id,
                pf.product_id,
                pf.user_id,
                pf.product_type,
                pf.created_at,
                p.status as product_status
            FROM product_favorites pf
            LEFT JOIN products p ON pf.product_id = p.id
            WHERE pf.product_type = 'mno-sim'
            ORDER BY pf.created_at DESC
            LIMIT 10
        ");
        $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>ID</th><th>상품 ID</th><th>사용자 ID</th><th>타입</th><th>상품 상태</th><th>찜한 일시</th></tr>";
        foreach ($favorites as $fav) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fav['id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_type']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_status'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($fav['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
    if (stripos($e->getMessage(), 'mno-sim') !== false || stripos($e->getMessage(), 'ENUM') !== false) {
        echo "<p class='error'><strong>데이터베이스 마이그레이션이 필요합니다!</strong></p>";
        echo "<p>에러 메시지가 'mno-sim' 또는 'ENUM'을 언급하고 있습니다.</p>";
    }
}

echo "<hr>";
echo "<p><a href='/MVNO/mypage/wishlist.php?type=mno-sim'>찜한 통신사유심 내역 페이지로 이동</a></p>";
echo "<p><a href='/MVNO/database/add-mno-sim-to-favorites.sql'>마이그레이션 SQL 파일 보기</a></p>";
echo "</body></html>";
?>

