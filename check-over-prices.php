<?php
/**
 * 기본 제공 초과 시 가격 확인 스크립트
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>기본 제공 초과 시 가격 확인</title>
    <style>
        body {
            font-family: 'Malgun Gothic', sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }
        th {
            background-color: #4CAF50;
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
        .has-unit {
            background-color: #fff3cd;
        }
        .info {
            background-color: #d1ecf1;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💰 기본 제공 초과 시 가격 확인</h1>

        <?php
        $pdo = getDBConnection();
        
        if (!$pdo) {
            echo '<div style="color: red;">❌ 데이터베이스 연결 실패</div>';
            exit;
        }

        try {
            // MVNO 상품의 기본 제공 초과 시 가격 확인
            $stmt = $pdo->query("
                SELECT 
                    p.id as product_id,
                    mvno.plan_name,
                    mvno.over_data_price,
                    mvno.over_voice_price,
                    mvno.over_video_price,
                    mvno.over_sms_price,
                    mvno.over_lms_price,
                    mvno.over_mms_price
                FROM products p
                INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
                WHERE p.product_type = 'mvno' AND p.status = 'active'
                ORDER BY p.id DESC
                LIMIT 20
            ");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<div class="info">';
            echo '<h3>📋 저장 형식 확인</h3>';
            echo '<p><strong>현재 DB 저장 방식:</strong> 숫자만 저장 (예: "57", "57.57", "575")</p>';
            echo '<p><strong>표시 방식:</strong> 숫자 + 단위 (예: "57원/MB", "58원", "575원")</p>';
            echo '<p><strong>단위 정보:</strong></p>';
            echo '<ul>';
            echo '<li>데이터: 원/MB</li>';
            echo '<li>음성: 원</li>';
            echo '<li>영상통화: 원</li>';
            echo '<li>단문메시지(SMS): 원</li>';
            echo '<li>텍스트형(LMS,MMS): 원</li>';
            echo '<li>멀티미디어형(MMS): 원</li>';
            echo '</ul>';
            echo '</div>';
            
            if (!empty($products)) {
                echo '<h2>최근 상품 20개 - 기본 제공 초과 시 가격</h2>';
                echo '<table>';
                echo '<thead>';
                echo '<tr>';
                echo '<th>상품ID</th>';
                echo '<th>요금제명</th>';
                echo '<th>데이터<br>(원/MB)</th>';
                echo '<th>음성<br>(원)</th>';
                echo '<th>영상통화<br>(원)</th>';
                echo '<th>SMS<br>(원)</th>';
                echo '<th>LMS/MMS<br>(원)</th>';
                echo '<th>MMS<br>(원)</th>';
                echo '<th>표시 예시</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                foreach ($products as $product) {
                    // 단위가 포함되어 있는지 확인
                    $hasUnit = false;
                    $overDataPrice = $product['over_data_price'];
                    $overVoicePrice = $product['over_voice_price'];
                    $overVideoPrice = $product['over_video_price'];
                    $overSmsPrice = $product['over_sms_price'];
                    $overLmsPrice = $product['over_lms_price'];
                    $overMmsPrice = $product['over_mms_price'];
                    
                    // 단위 포함 여부 확인
                    if ($overDataPrice && (strpos($overDataPrice, '원') !== false || strpos($overDataPrice, 'MB') !== false)) {
                        $hasUnit = true;
                    }
                    
                    // 표시 예시 생성
                    $displayExample = [];
                    if ($overDataPrice) {
                        $num = preg_replace('/[^0-9.]/g', '', $overDataPrice);
                        $displayExample[] = '데이터: ' . ($num ? number_format(floatval($num), 0) : $overDataPrice) . '원/MB';
                    }
                    if ($overVoicePrice) {
                        $num = preg_replace('/[^0-9.]/g', '', $overVoicePrice);
                        $displayExample[] = '음성: ' . ($num ? number_format(floatval($num), 0) : $overVoicePrice) . '원';
                    }
                    
                    echo '<tr' . ($hasUnit ? ' class="has-unit"' : '') . '>';
                    echo '<td>' . htmlspecialchars($product['product_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($product['plan_name']) . '</td>';
                    echo '<td>' . ($overDataPrice ? htmlspecialchars($overDataPrice) : '-') . '</td>';
                    echo '<td>' . ($overVoicePrice ? htmlspecialchars($overVoicePrice) : '-') . '</td>';
                    echo '<td>' . ($overVideoPrice ? htmlspecialchars($overVideoPrice) : '-') . '</td>';
                    echo '<td>' . ($overSmsPrice ? htmlspecialchars($overSmsPrice) : '-') . '</td>';
                    echo '<td>' . ($overLmsPrice ? htmlspecialchars($overLmsPrice) : '-') . '</td>';
                    echo '<td>' . ($overMmsPrice ? htmlspecialchars($overMmsPrice) : '-') . '</td>';
                    echo '<td style="font-size: 12px;">' . (!empty($displayExample) ? implode('<br>', $displayExample) : '-') . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
                
                if ($hasUnit) {
                    echo '<div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;">';
                    echo '<p><strong>⚠️ 주의:</strong> 일부 상품에 단위가 포함되어 저장되어 있습니다.</p>';
                    echo '<p>표시할 때는 숫자만 추출하여 단위를 추가하도록 수정되었습니다.</p>';
                    echo '</div>';
                }
            } else {
                echo '<p>등록된 상품이 없습니다.</p>';
            }
            
        } catch (PDOException $e) {
            echo '<div style="color: red;">❌ 오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</body>
</html>










