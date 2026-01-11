<?php
/**
 * MNO-SIM 리뷰 작성 로그 확인 스크립트
 * 리뷰 작성 시 발생하는 모든 로그를 확인합니다.
 */

require_once __DIR__ . '/includes/data/db-config.php';

// 로그 파일 경로
$apacheLogPath = 'C:/xampp/apache/logs/error.log';
$phpLogPath = 'C:/xampp/php/logs/php_error_log';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>MNO-SIM 리뷰 작성 로그 확인</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; }
        .log-section { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .log-entry { background: white; padding: 10px; margin: 5px 0; border-left: 3px solid #4CAF50; font-family: monospace; font-size: 12px; }
        .log-entry.error { border-left-color: #f44336; }
        .log-entry.warning { border-left-color: #ff9800; }
        .log-entry.info { border-left-color: #2196F3; }
        .no-logs { color: #999; font-style: italic; }
        .stats { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .stats-item { display: inline-block; margin: 0 20px 10px 0; }
        .filter-form { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .filter-form input, .filter-form select { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-form button { padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filter-form button:hover { background: #45a049; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        table th { background: #4CAF50; color: white; }
        table tr:nth-child(even) { background: #f9f9f9; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔍 MNO-SIM 리뷰 작성 로그 확인</h1>";

// 필터 파라미터
$filterApplicationId = isset($_GET['application_id']) ? intval($_GET['application_id']) : null;
$filterProductId = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
$filterUserId = isset($_GET['user_id']) ? trim($_GET['user_id']) : null;
$filterLimit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

// 필터 폼
echo "<div class='filter-form'>
    <form method='GET'>
        <label>Application ID: <input type='number' name='application_id' value='" . htmlspecialchars($filterApplicationId ?? '') . "'></label>
        <label>Product ID: <input type='number' name='product_id' value='" . htmlspecialchars($filterProductId ?? '') . "'></label>
        <label>User ID: <input type='text' name='user_id' value='" . htmlspecialchars($filterUserId ?? '') . "'></label>
        <label>최대 로그 수: <input type='number' name='limit' value='$filterLimit' min='10' max='500'></label>
        <button type='submit'>조회</button>
        <a href='?' style='margin-left: 10px; color: #666;'>초기화</a>
    </form>
</div>";

// 1. Apache Error Log 확인
echo "<h2>1. Apache Error Log (최근 리뷰 관련 로그)</h2>";
echo "<div class='log-section'>";

if (file_exists($apacheLogPath)) {
    $apacheLogs = file($apacheLogPath);
    $apacheLogs = array_reverse($apacheLogs); // 최신순
    
    $reviewLogs = [];
    $keywords = ['mno-sim', 'MNO-SIM', 'submit-review', 'addProductReview', '리뷰', 'review'];
    
    foreach ($apacheLogs as $line) {
        $lineLower = strtolower($line);
        $isReviewLog = false;
        
        foreach ($keywords as $keyword) {
            if (stripos($line, $keyword) !== false) {
                $isReviewLog = true;
                break;
            }
        }
        
        if ($isReviewLog) {
            // 필터 적용
            if ($filterApplicationId && strpos($line, (string)$filterApplicationId) === false) continue;
            if ($filterProductId && strpos($line, (string)$filterProductId) === false) continue;
            if ($filterUserId && strpos($line, $filterUserId) === false) continue;
            
            $reviewLogs[] = $line;
            if (count($reviewLogs) >= $filterLimit) break;
        }
    }
    
    if (!empty($reviewLogs)) {
        echo "<div class='stats'>
            <strong>총 " . count($reviewLogs) . "개의 리뷰 관련 로그를 찾았습니다.</strong>
        </div>";
        
        foreach ($reviewLogs as $log) {
            $logClass = 'info';
            if (stripos($log, 'error') !== false || stripos($log, 'fail') !== false) {
                $logClass = 'error';
            } elseif (stripos($log, 'warning') !== false) {
                $logClass = 'warning';
            }
            echo "<div class='log-entry $logClass'>" . htmlspecialchars($log) . "</div>";
        }
    } else {
        echo "<div class='no-logs'>리뷰 관련 로그를 찾을 수 없습니다.</div>";
    }
} else {
    echo "<div class='no-logs'>Apache 로그 파일을 찾을 수 없습니다: $apacheLogPath</div>";
}

echo "</div>";

// 2. PHP Error Log 확인
echo "<h2>2. PHP Error Log</h2>";
echo "<div class='log-section'>";

if (file_exists($phpLogPath)) {
    $phpLogs = file($phpLogPath);
    $phpLogs = array_reverse($phpLogs);
    
    $reviewLogs = [];
    foreach ($phpLogs as $line) {
        $lineLower = strtolower($line);
        if (stripos($line, 'mno-sim') !== false || stripos($line, 'review') !== false || stripos($line, '리뷰') !== false) {
            if ($filterApplicationId && strpos($line, (string)$filterApplicationId) === false) continue;
            if ($filterProductId && strpos($line, (string)$filterProductId) === false) continue;
            if ($filterUserId && strpos($line, $filterUserId) === false) continue;
            
            $reviewLogs[] = $line;
            if (count($reviewLogs) >= $filterLimit) break;
        }
    }
    
    if (!empty($reviewLogs)) {
        echo "<div class='stats'>총 " . count($reviewLogs) . "개의 리뷰 관련 로그를 찾았습니다.</div>";
        foreach ($reviewLogs as $log) {
            echo "<div class='log-entry'>" . htmlspecialchars($log) . "</div>";
        }
    } else {
        echo "<div class='no-logs'>리뷰 관련 로그를 찾을 수 없습니다.</div>";
    }
} else {
    echo "<div class='no-logs'>PHP 로그 파일을 찾을 수 없습니다: $phpLogPath</div>";
}

echo "</div>";

// 3. DB에서 실제 리뷰 확인
echo "<h2>3. DB에서 실제 리뷰 확인</h2>";
echo "<div class='log-section'>";

$pdo = getDBConnection();
if ($pdo) {
    try {
        $whereConditions = ["product_type = 'mno-sim'"];
        $params = [];
        
        if ($filterApplicationId) {
            $whereConditions[] = "application_id = :application_id";
            $params[':application_id'] = $filterApplicationId;
        }
        if ($filterProductId) {
            $whereConditions[] = "product_id = :product_id";
            $params[':product_id'] = $filterProductId;
        }
        if ($filterUserId) {
            $whereConditions[] = "user_id = :user_id";
            $params[':user_id'] = $filterUserId;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $stmt = $pdo->prepare("
            SELECT id, application_id, product_id, user_id, product_type, 
                   kindness_rating, speed_rating, rating, status, created_at, updated_at
            FROM product_reviews 
            WHERE $whereClause
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $filterLimit, PDO::PARAM_INT);
        $stmt->execute();
        
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($reviews)) {
            echo "<div class='stats'>
                <strong>총 " . count($reviews) . "개의 리뷰를 찾았습니다.</strong>
            </div>";
            
            echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Application ID</th>
                    <th>Product ID</th>
                    <th>User ID</th>
                    <th>Kindness</th>
                    <th>Speed</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>";
            
            foreach ($reviews as $review) {
                echo "<tr>
                    <td>{$review['id']}</td>
                    <td>" . ($review['application_id'] ?? 'NULL') . "</td>
                    <td>{$review['product_id']}</td>
                    <td>{$review['user_id']}</td>
                    <td>" . ($review['kindness_rating'] ?? '-') . "</td>
                    <td>" . ($review['speed_rating'] ?? '-') . "</td>
                    <td>{$review['rating']}</td>
                    <td>{$review['status']}</td>
                    <td>{$review['created_at']}</td>
                </tr>";
            }
            
            echo "</table>";
        } else {
            echo "<div class='no-logs'>DB에서 리뷰를 찾을 수 없습니다.</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='log-entry error'>DB 조회 오류: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<div class='no-logs'>DB 연결 실패</div>";
}

echo "</div>";

// 4. 최근 리뷰 작성 시도 확인 (로그 기반)
echo "<h2>4. 최근 리뷰 작성 시도 확인</h2>";
echo "<div class='log-section'>";

if ($pdo) {
    try {
        // 최근 24시간 내 리뷰 작성 시도 로그 패턴 확인
        $stmt = $pdo->prepare("
            SELECT id, application_id, product_id, user_id, product_type, status, created_at
            FROM product_reviews 
            WHERE product_type = 'mno-sim'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute();
        $recentReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($recentReviews)) {
            echo "<div class='stats'>최근 24시간 내 작성된 리뷰: " . count($recentReviews) . "개</div>";
            echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Application ID</th>
                    <th>Product ID</th>
                    <th>User ID</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>";
            
            foreach ($recentReviews as $review) {
                echo "<tr>
                    <td>{$review['id']}</td>
                    <td>" . ($review['application_id'] ?? 'NULL') . "</td>
                    <td>{$review['product_id']}</td>
                    <td>{$review['user_id']}</td>
                    <td>{$review['status']}</td>
                    <td>{$review['created_at']}</td>
                </tr>";
            }
            
            echo "</table>";
        } else {
            echo "<div class='no-logs'>최근 24시간 내 작성된 리뷰가 없습니다.</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='log-entry error'>DB 조회 오류: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

echo "</div>";

echo "</div>
</body>
</html>";
?>
