<?php
/**
 * 주문번호 중복 확인 스크립트
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>주문번호 중복 확인</title>
    <style>
        body {
            font-family: 'Malgun Gothic', sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #dc3545;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        h2 {
            color: #555;
            margin-top: 40px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }
        th {
            background-color: #dc3545;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .duplicate {
            background-color: #fff3cd;
            font-weight: bold;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .alert {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 주문번호 중복 확인</h1>

        <?php
        $pdo = getDBConnection();
        
        if (!$pdo) {
            echo '<div class="alert">❌ 데이터베이스 연결 실패</div>';
            exit;
        }

        try {
            // 1. 주문번호가 NULL인 주문 확인
            $stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM product_applications 
                WHERE order_number IS NULL OR order_number = ''
            ");
            $nullCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // 2. 중복된 주문번호 확인
            $stmt = $pdo->query("
                SELECT order_number, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
                FROM product_applications 
                WHERE order_number IS NOT NULL AND order_number != ''
                GROUP BY order_number
                HAVING COUNT(*) > 1
                ORDER BY order_number
            ");
            $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 3. 전체 주문 통계
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM product_applications");
            $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $pdo->query("
                SELECT COUNT(DISTINCT order_number) as unique_count 
                FROM product_applications 
                WHERE order_number IS NOT NULL AND order_number != ''
            ");
            $uniqueCount = $stmt->fetch(PDO::FETCH_ASSOC)['unique_count'];
            
            // 결과 표시
            echo '<div class="success">';
            echo '<h3>📊 통계</h3>';
            echo '<p><strong>전체 주문:</strong> ' . number_format($totalOrders) . '건</p>';
            echo '<p><strong>주문번호가 있는 주문:</strong> ' . number_format($totalOrders - $nullCount) . '건</p>';
            echo '<p><strong>고유한 주문번호:</strong> ' . number_format($uniqueCount) . '개</p>';
            echo '<p><strong>주문번호가 없는 주문:</strong> ' . number_format($nullCount) . '건</p>';
            echo '</div>';
            
            if ($nullCount > 0) {
                echo '<div class="alert">';
                echo '<h3>⚠️ 주문번호가 없는 주문이 있습니다</h3>';
                echo '<p>주문번호가 없는 주문 ' . number_format($nullCount) . '건이 발견되었습니다.</p>';
                echo '</div>';
            }
            
            if (!empty($duplicates)) {
                echo '<div class="alert">';
                echo '<h3>❌ 중복된 주문번호 발견!</h3>';
                echo '<p>중복된 주문번호가 ' . count($duplicates) . '개 발견되었습니다.</p>';
                echo '</div>';
                
                echo '<h2>중복된 주문번호 목록</h2>';
                echo '<table>';
                echo '<thead>';
                echo '<tr>';
                echo '<th>주문번호</th>';
                echo '<th>중복 횟수</th>';
                echo '<th>주문 ID 목록</th>';
                echo '<th>상세 정보</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                foreach ($duplicates as $dup) {
                    $ids = explode(',', $dup['ids']);
                    echo '<tr class="duplicate">';
                    echo '<td><strong>' . htmlspecialchars($dup['order_number']) . '</strong></td>';
                    echo '<td>' . htmlspecialchars($dup['count']) . '건</td>';
                    echo '<td>' . htmlspecialchars($dup['ids']) . '</td>';
                    echo '<td><a href="?detail=' . htmlspecialchars($dup['order_number']) . '">상세보기</a></td>';
                    echo '</tr>';
                    
                    // 상세 정보 표시
                    if (isset($_GET['detail']) && $_GET['detail'] === $dup['order_number']) {
                        $detailStmt = $pdo->prepare("
                            SELECT 
                                pa.id,
                                pa.order_number,
                                pa.product_id,
                                pa.created_at,
                                ac.name,
                                ac.phone
                            FROM product_applications pa
                            LEFT JOIN application_customers ac ON pa.id = ac.application_id
                            WHERE pa.order_number = :order_number
                            ORDER BY pa.id
                        ");
                        $detailStmt->execute([':order_number' => $dup['order_number']]);
                        $details = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo '<tr>';
                        echo '<td colspan="4">';
                        echo '<table style="margin: 10px 0; background: #f8f9fa;">';
                        echo '<tr><th>주문ID</th><th>상품ID</th><th>고객명</th><th>전화번호</th><th>생성일시</th></tr>';
                        foreach ($details as $detail) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($detail['id']) . '</td>';
                            echo '<td>' . htmlspecialchars($detail['product_id']) . '</td>';
                            echo '<td>' . htmlspecialchars($detail['name'] ?? '-') . '</td>';
                            echo '<td>' . htmlspecialchars($detail['phone'] ?? '-') . '</td>';
                            echo '<td>' . htmlspecialchars($detail['created_at']) . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                        echo '</td>';
                        echo '</tr>';
                    }
                }
                
                echo '</tbody>';
                echo '</table>';
            } else {
                echo '<div class="success">';
                echo '<h3>✅ 중복된 주문번호가 없습니다</h3>';
                echo '</div>';
            }
            
            // 최근 주문 20개 확인
            echo '<h2>최근 주문 20개 (주문번호 확인)</h2>';
            $stmt = $pdo->query("
                SELECT 
                    pa.id,
                    pa.order_number,
                    pa.product_id,
                    pa.created_at,
                    ac.name,
                    ac.phone
                FROM product_applications pa
                LEFT JOIN application_customers ac ON pa.id = ac.application_id
                ORDER BY pa.id DESC
                LIMIT 20
            ");
            $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($recentOrders)) {
                echo '<table>';
                echo '<thead>';
                echo '<tr>';
                echo '<th>주문ID</th>';
                echo '<th>주문번호</th>';
                echo '<th>상품ID</th>';
                echo '<th>고객명</th>';
                echo '<th>전화번호</th>';
                echo '<th>생성일시</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                foreach ($recentOrders as $order) {
                    $isNull = empty($order['order_number']);
                    echo '<tr' . ($isNull ? ' style="background-color: #fff3cd;"' : '') . '>';
                    echo '<td>' . htmlspecialchars($order['id']) . '</td>';
                    echo '<td>' . ($isNull ? '<span style="color: red;">NULL</span>' : htmlspecialchars($order['order_number'])) . '</td>';
                    echo '<td>' . htmlspecialchars($order['product_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($order['name'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($order['phone'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($order['created_at']) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="alert">❌ 오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</body>
</html>
















