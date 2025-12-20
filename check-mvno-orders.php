<?php
/**
 * 알뜰폰 주문번호 조회 스크립트
 * 서버에 저장된 알뜰폰 주문의 주문번호를 표시합니다.
 */

require_once __DIR__ . '/includes/data/db-config.php';

// 한국 시간대 설정
date_default_timezone_set('Asia/Seoul');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>알뜰폰 주문번호 조회</title>
    <style>
        body {
            font-family: 'Malgun Gothic', sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1f2937;
            margin-bottom: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        tr:hover {
            background: #f9fafb;
        }
        .order-number {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #10b981;
        }
        .no-orders {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>알뜰폰 주문번호 조회</h1>
        
        <?php
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                echo '<p style="color: red;">데이터베이스 연결에 실패했습니다.</p>';
                exit;
            }
            
            // 알뜰폰 주문 조회
            $sql = "
                SELECT 
                    a.id,
                    a.created_at,
                    c.name as customer_name,
                    c.phone,
                    mvno.provider,
                    mvno.plan_name
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                INNER JOIN products p ON a.product_id = p.id
                LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
                WHERE a.product_type = 'mvno'
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT 100
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($orders)) {
                echo '<div class="no-orders">등록된 알뜰폰 주문이 없습니다.</div>';
            } else {
                echo '<table>';
                echo '<thead>';
                echo '<tr>';
                echo '<th>순번</th>';
                echo '<th>주문번호</th>';
                echo '<th>생성일시</th>';
                echo '<th>고객명</th>';
                echo '<th>전화번호</th>';
                echo '<th>통신사</th>';
                echo '<th>상품명</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                $index = 1;
                foreach ($orders as $order) {
                    // 주문번호 생성
                    $createdAt = new DateTime($order['created_at']);
                    // 앞 8자리: YY(년 2자리) + MM(월 2자리) + DD(일 2자리) + HH(시간 2자리)
                    $dateTimePart = $createdAt->format('ymdH');
                    // 뒤 8자리: MM(분 2자리) + 주문ID(6자리)
                    $minutePart = $createdAt->format('i');
                    $orderIdPadded = str_pad($order['id'], 6, '0', STR_PAD_LEFT);
                    $orderNumber = $dateTimePart . '-' . $minutePart . $orderIdPadded;
                    
                    echo '<tr>';
                    echo '<td>' . $index++ . '</td>';
                    echo '<td><span class="order-number">' . htmlspecialchars($orderNumber) . '</span></td>';
                    echo '<td>' . htmlspecialchars($order['created_at']) . '</td>';
                    echo '<td>' . htmlspecialchars($order['customer_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($order['phone']) . '</td>';
                    echo '<td>' . htmlspecialchars($order['provider'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($order['plan_name'] ?? '-') . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
                echo '<p style="margin-top: 20px; color: #6b7280;">총 ' . count($orders) . '개의 주문이 조회되었습니다. (최대 100개)</p>';
            }
        } catch (PDOException $e) {
            echo '<p style="color: red;">오류가 발생했습니다: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
</body>
</html>













