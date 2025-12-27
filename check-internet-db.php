<?php
/**
 * 인터넷 주문내역 product_snapshot DB 확인 페이지
 * 브라우저에서 접근: http://localhost/MVNO/check-internet-db.php
 */

require_once 'includes/data/db-config.php';

$pdo = getDBConnection();
if (!$pdo) {
    die("DB 연결 실패");
}

// 인터넷 신청 내역 조회
$stmt = $pdo->query("
    SELECT 
        a.id as application_id,
        a.product_id,
        a.created_at,
        c.additional_info,
        c.user_id
    FROM product_applications a
    INNER JOIN application_customers c ON a.id = c.application_id
    WHERE a.product_type = 'internet'
    ORDER BY a.created_at DESC
    LIMIT 10
");

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>인터넷 주문내역 DB 확인</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 10px;
        }
        .item {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
        }
        .item-header {
            font-weight: bold;
            color: #6366f1;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status-yes {
            background: #d1fae5;
            color: #065f46;
        }
        .status-no {
            background: #fee2e2;
            color: #991b1b;
        }
        .field-list {
            margin-top: 10px;
            padding: 10px;
            background: white;
            border-radius: 4px;
        }
        .field-item {
            padding: 5px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .field-item:last-child {
            border-bottom: none;
        }
        .field-name {
            font-weight: bold;
            color: #374151;
            display: inline-block;
            width: 200px;
        }
        .field-value {
            color: #6b7280;
            word-break: break-all;
        }
        pre {
            background: #1f2937;
            color: #10b981;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>인터넷 주문내역 product_snapshot 확인</h1>
        <p>총 <?php echo count($results); ?>개의 인터넷 신청 내역을 확인했습니다.</p>
        
        <?php foreach ($results as $row): ?>
            <div class="item">
                <div class="item-header">
                    Application ID: <?php echo htmlspecialchars($row['application_id']); ?>
                    <span class="status <?php 
                        $additional_info = json_decode($row['additional_info'], true);
                        $has_snapshot = isset($additional_info['product_snapshot']) && !empty($additional_info['product_snapshot']);
                        echo $has_snapshot ? 'status-yes' : 'status-no';
                    ?>">
                        <?php echo $has_snapshot ? '✅ Snapshot 있음' : '❌ Snapshot 없음'; ?>
                    </span>
                </div>
                
                <div class="field-list">
                    <div class="field-item">
                        <span class="field-name">Product ID:</span>
                        <span class="field-value"><?php echo htmlspecialchars($row['product_id']); ?></span>
                    </div>
                    <div class="field-item">
                        <span class="field-name">User ID:</span>
                        <span class="field-value"><?php echo htmlspecialchars($row['user_id']); ?></span>
                    </div>
                    <div class="field-item">
                        <span class="field-name">Created At:</span>
                        <span class="field-value"><?php echo htmlspecialchars($row['created_at']); ?></span>
                    </div>
                </div>
                
                <?php
                $additional_info = json_decode($row['additional_info'], true);
                $has_snapshot = isset($additional_info['product_snapshot']) && !empty($additional_info['product_snapshot']);
                
                if ($has_snapshot):
                    $snapshot = $additional_info['product_snapshot'];
                ?>
                    <h3 style="margin-top: 15px; color: #10b981;">✅ product_snapshot 데이터:</h3>
                    <div class="field-list">
                        <?php
                        $important_fields = [
                            'registration_place' => '신청 인터넷 회선',
                            'service_type' => '결합여부',
                            'speed_option' => '가입 속도',
                            'monthly_fee' => '월 요금제',
                            'cash_payment_names' => '현금지급 항목',
                            'gift_card_names' => '상품권 지급 항목',
                            'equipment_names' => '장비 제공 항목',
                            'installation_names' => '설치 및 기타 서비스 항목'
                        ];
                        
                        foreach ($important_fields as $key => $label):
                            if (isset($snapshot[$key])):
                                $value = $snapshot[$key];
                                if (is_array($value)) {
                                    $display = count($value) > 0 ? '[' . implode(', ', array_slice($value, 0, 3)) . (count($value) > 3 ? '...' : '') . ']' : '빈 배열';
                                } else {
                                    $display = strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value;
                                }
                        ?>
                            <div class="field-item">
                                <span class="field-name"><?php echo htmlspecialchars($label); ?>:</span>
                                <span class="field-value"><?php echo htmlspecialchars($display); ?></span>
                            </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                    
                    <details style="margin-top: 15px;">
                        <summary style="cursor: pointer; color: #6366f1; font-weight: bold;">전체 product_snapshot JSON 보기</summary>
                        <pre><?php echo htmlspecialchars(json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                    </details>
                <?php else: ?>
                    <h3 style="margin-top: 15px; color: #ef4444;">❌ product_snapshot이 없습니다!</h3>
                    <details style="margin-top: 15px;">
                        <summary style="cursor: pointer; color: #6366f1; font-weight: bold;">additional_info 전체 보기</summary>
                        <pre><?php echo htmlspecialchars(json_encode($additional_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                    </details>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>

