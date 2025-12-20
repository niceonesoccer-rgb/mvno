<?php
/**
 * 신청서 작성 시 DB 저장 데이터 확인 스크립트
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>신청서 DB 저장 데이터 확인</title>
    <style>
        body {
            font-family: 'Malgun Gothic', sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1600px;
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
        h2 {
            color: #555;
            margin-top: 40px;
            margin-bottom: 20px;
            padding-left: 10px;
            border-left: 4px solid #4CAF50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 13px;
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
            word-break: break-word;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .has-unit {
            background-color: #fff3cd;
        }
        .json-data {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 400px;
            overflow-y: auto;
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
        <h1>📋 신청서 작성 시 DB 저장 데이터 확인</h1>

        <?php
        $pdo = getDBConnection();
        
        if (!$pdo) {
            echo '<div style="color: red;">❌ 데이터베이스 연결 실패</div>';
            exit;
        }

        try {
            // 최근 주문 5개 확인
            $stmt = $pdo->query("
                SELECT 
                    pa.id,
                    pa.order_number,
                    pa.product_id,
                    pa.created_at,
                    ac.name,
                    ac.phone,
                    ac.email,
                    ac.additional_info
                FROM product_applications pa
                LEFT JOIN application_customers ac ON pa.id = ac.application_id
                ORDER BY pa.id DESC
                LIMIT 5
            ");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<div class="info">';
            echo '<h3>📊 신청서 작성 시 저장되는 데이터 구조</h3>';
            echo '<p><strong>1. product_applications 테이블:</strong></p>';
            echo '<ul>';
            echo '<li>id: 주문 ID</li>';
            echo '<li>order_number: 주문번호 (YYMMDDHH-0001 형식)</li>';
            echo '<li>product_id: 상품 ID</li>';
            echo '<li>seller_id: 판매자 ID</li>';
            echo '<li>product_type: 상품 타입 (mvno, mno, internet)</li>';
            echo '<li>application_status: 신청 상태</li>';
            echo '<li>created_at: 신청일시</li>';
            echo '</ul>';
            
            echo '<p><strong>2. application_customers 테이블:</strong></p>';
            echo '<ul>';
            echo '<li>name: 고객명</li>';
            echo '<li>phone: 전화번호</li>';
            echo '<li>email: 이메일</li>';
            echo '<li><strong>additional_info (JSON):</strong> 추가 정보 (중요!)</li>';
            echo '</ul>';
            
            echo '<p><strong>3. additional_info 구조:</strong></p>';
            echo '<ul>';
            echo '<li>subscription_type: 가입 형태 (new, port, change)</li>';
            echo '<li><strong>product_snapshot:</strong> 신청 당시 상품 정보 전체 (단위 포함 가능)</li>';
            echo '</ul>';
            echo '</div>';
            
            if (!empty($orders)) {
                echo '<h2>최근 주문 5개 - 저장된 데이터 확인</h2>';
                
                foreach ($orders as $index => $order) {
                    echo '<div style="margin-bottom: 40px; border: 1px solid #ddd; padding: 20px; border-radius: 5px;">';
                    echo '<h3 style="margin-top: 0;">주문 #' . ($index + 1) . ' (주문ID: ' . htmlspecialchars($order['id']) . ')</h3>';
                    
                    echo '<table>';
                    echo '<tr><th style="width: 150px;">항목</th><th>값</th></tr>';
                    echo '<tr><td>주문번호</td><td>' . htmlspecialchars($order['order_number'] ?? 'NULL') . '</td></tr>';
                    echo '<tr><td>상품ID</td><td>' . htmlspecialchars($order['product_id']) . '</td></tr>';
                    echo '<tr><td>고객명</td><td>' . htmlspecialchars($order['name'] ?? '-') . '</td></tr>';
                    echo '<tr><td>전화번호</td><td>' . htmlspecialchars($order['phone'] ?? '-') . '</td></tr>';
                    echo '<tr><td>이메일</td><td>' . htmlspecialchars($order['email'] ?? '-') . '</td></tr>';
                    echo '<tr><td>신청일시</td><td>' . htmlspecialchars($order['created_at']) . '</td></tr>';
                    echo '</table>';
                    
                    // additional_info 파싱
                    if (!empty($order['additional_info'])) {
                        $additionalInfo = json_decode($order['additional_info'], true);
                        
                        if ($additionalInfo) {
                            echo '<h4 style="margin-top: 20px;">additional_info 내용:</h4>';
                            
                            echo '<table>';
                            echo '<tr><th style="width: 200px;">키</th><th>값</th></tr>';
                            
                            // subscription_type
                            if (isset($additionalInfo['subscription_type'])) {
                                echo '<tr>';
                                echo '<td>subscription_type</td>';
                                echo '<td>' . htmlspecialchars($additionalInfo['subscription_type']) . '</td>';
                                echo '</tr>';
                            }
                            
                            // product_snapshot 확인
                            if (isset($additionalInfo['product_snapshot']) && is_array($additionalInfo['product_snapshot'])) {
                                $snapshot = $additionalInfo['product_snapshot'];
                                
                                echo '<tr>';
                                echo '<td colspan="2" style="background-color: #f8f9fa; font-weight: bold;">product_snapshot (신청 당시 상품 정보)</td>';
                                echo '</tr>';
                                
                                // 단위가 포함될 수 있는 필드들 확인
                                $unitFields = [
                                    'call_amount' => '통화량',
                                    'sms_amount' => '문자량',
                                    'additional_call' => '부가통화',
                                    'data_amount_value' => '데이터 제공량',
                                    'data_additional_value' => '데이터 추가제공',
                                    'data_exhausted_value' => '데이터 소진시',
                                    'mobile_hotspot_value' => '테더링',
                                    'over_data_price' => '데이터 초과 시',
                                    'over_voice_price' => '음성 초과 시',
                                    'over_video_price' => '영상통화 초과 시',
                                    'over_sms_price' => 'SMS 초과 시',
                                    'over_lms_price' => 'LMS/MMS 초과 시',
                                    'over_mms_price' => 'MMS 초과 시'
                                ];
                                
                                foreach ($unitFields as $field => $label) {
                                    if (isset($snapshot[$field])) {
                                        $value = $snapshot[$field];
                                        $hasUnit = false;
                                        
                                        // 단위 포함 여부 확인
                                        if ($value && (strpos($value, '원') !== false || 
                                            strpos($value, 'MB') !== false || 
                                            strpos($value, 'GB') !== false || 
                                            strpos($value, 'gb') !== false ||
                                            strpos($value, 'mb') !== false ||
                                            strpos($value, '분') !== false ||
                                            strpos($value, '건') !== false ||
                                            strpos($value, 'Mbps') !== false)) {
                                            $hasUnit = true;
                                        }
                                        
                                        echo '<tr' . ($hasUnit ? ' class="has-unit"' : '') . '>';
                                        echo '<td>' . htmlspecialchars($label) . '<br><small>(' . $field . ')</small></td>';
                                        echo '<td>' . htmlspecialchars($value ?? '-') . ($hasUnit ? ' <span style="color: orange;">⚠️ 단위 포함</span>' : '') . '</td>';
                                        echo '</tr>';
                                    }
                                }
                                
                                // 전체 product_snapshot JSON 표시
                                echo '<tr>';
                                echo '<td colspan="2">';
                                echo '<details style="margin-top: 10px;">';
                                echo '<summary style="cursor: pointer; font-weight: bold; color: #4CAF50;">전체 product_snapshot 보기 (클릭)</summary>';
                                echo '<div class="json-data">' . htmlspecialchars(json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</div>';
                                echo '</details>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</table>';
                        } else {
                            echo '<p style="color: #999;">additional_info가 비어있거나 파싱할 수 없습니다.</p>';
                        }
                    } else {
                        echo '<p style="color: #999;">additional_info가 없습니다.</p>';
                    }
                    
                    echo '</div>';
                }
            } else {
                echo '<p>등록된 주문이 없습니다.</p>';
            }
            
        } catch (PDOException $e) {
            echo '<div style="color: red;">❌ 오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</body>
</html>













