<?php
/**
 * 주문번호 25122008-0017 상품 정보 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>주문 25122008-0017 상품 정보</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: green; font-weight: bold; padding: 10px; background: #d1fae5; border-radius: 5px; margin: 10px 0; }
        .error { color: red; font-weight: bold; padding: 10px; background: #fee2e2; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #dbeafe; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; padding: 10px; background: #fef3c7; border-radius: 5px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #10b981; color: white; }
        .raw-value { font-family: monospace; background: #f3f4f6; padding: 4px 8px; border-radius: 3px; font-size: 14px; }
        .section { margin: 30px 0; padding: 20px; background: #f9fafb; border-radius: 8px; }
        h2 { color: #1f2937; border-bottom: 2px solid #10b981; padding-bottom: 10px; }
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
    
    echo "<h1>주문번호 25122008-0017 상품 정보 확인</h1>";
    
    // 주문 정보 조회
    $stmt = $pdo->prepare("
        SELECT 
            a.id as application_id,
            a.order_number,
            a.product_id,
            a.application_status,
            a.created_at,
            a.status_changed_at,
            c.name as customer_name,
            c.phone as customer_phone,
            c.email as customer_email,
            c.additional_info,
            p.product_type,
            internet.registration_place,
            internet.service_type,
            internet.speed_option,
            internet.monthly_fee,
            internet.cash_payment_names,
            internet.cash_payment_prices,
            internet.gift_card_names,
            internet.gift_card_prices,
            internet.equipment_names,
            internet.equipment_prices,
            internet.installation_names,
            internet.installation_prices
        FROM product_applications a
        INNER JOIN application_customers c ON a.id = c.application_id
        INNER JOIN products p ON a.product_id = p.id
        LEFT JOIN product_internet_details internet ON p.id = internet.product_id
        WHERE a.order_number = :order_number
    ");
    $stmt->execute([':order_number' => '25122008-0017']);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo "<p class='error'>주문을 찾을 수 없습니다.</p>";
        exit;
    }
    
    // 기본 주문 정보
    echo "<div class='section'>";
    echo "<h2>1. 기본 주문 정보</h2>";
    echo "<table>";
    echo "<tr><th>항목</th><th>값</th></tr>";
    echo "<tr><td>주문번호</td><td><strong>" . htmlspecialchars($order['order_number']) . "</strong></td></tr>";
    echo "<tr><td>신청 ID</td><td>" . htmlspecialchars($order['application_id']) . "</td></tr>";
    echo "<tr><td>상품 ID</td><td>" . htmlspecialchars($order['product_id']) . "</td></tr>";
    echo "<tr><td>상품 타입</td><td>" . htmlspecialchars($order['product_type']) . "</td></tr>";
    echo "<tr><td>고객명</td><td>" . htmlspecialchars($order['customer_name']) . "</td></tr>";
    echo "<tr><td>전화번호</td><td>" . htmlspecialchars($order['customer_phone']) . "</td></tr>";
    echo "<tr><td>이메일</td><td>" . htmlspecialchars($order['customer_email'] ?? '-') . "</td></tr>";
    echo "<tr><td>진행상황</td><td>" . htmlspecialchars($order['application_status']) . "</td></tr>";
    echo "<tr><td>주문일시</td><td>" . htmlspecialchars($order['created_at']) . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    // 월 요금제 상세 분석
    echo "<div class='section'>";
    echo "<h2>2. 월 요금제 (monthly_fee) 상세 분석</h2>";
    
    $monthlyFee = $order['monthly_fee'];
    $monthlyFeeType = gettype($monthlyFee);
    $monthlyFeeLength = strlen($monthlyFee);
    
    echo "<table>";
    echo "<tr><th>항목</th><th>값</th></tr>";
    echo "<tr><td>원본 값 (RAW)</td><td><span class='raw-value'>" . htmlspecialchars($monthlyFee) . "</span></td></tr>";
    echo "<tr><td>PHP 타입</td><td><strong>{$monthlyFeeType}</strong></td></tr>";
    echo "<tr><td>문자열 길이</td><td>{$monthlyFeeLength} bytes</td></tr>";
    
    // 숫자 추출 테스트 (JavaScript와 동일한 로직)
    $numericValue = preg_replace('/[^0-9]/', '', $monthlyFee);
    echo "<tr><td>숫자만 추출 (JS 로직)</td><td><span class='raw-value'>{$numericValue}</span></td></tr>";
    
    if ($numericValue !== '') {
        $intValue = intval($numericValue);
        echo "<tr><td>정수 변환</td><td><strong>" . number_format($intValue) . "</strong></td></tr>";
        
        if ($intValue >= 1000000) {
            $suggestedValue = floor($intValue / 100);
            echo "<tr><td>100만원 이상 감지</td><td class='error'>⚠ 오류 의심 (예상값: " . number_format($suggestedValue) . "원)</td></tr>";
        } elseif ($intValue > 200000) {
            echo "<tr><td>20만원 초과</td><td class='warning'>⚠ 비정상적으로 높은 값</td></tr>";
        } else {
            echo "<tr><td>정상 범위</td><td class='success'>✓ 정상</td></tr>";
        }
    }
    
    // 소수점 포함 여부 확인
    if (strpos($monthlyFee, '.') !== false) {
        echo "<tr><td>소수점 포함</td><td class='error'>⚠ 소수점이 포함되어 있습니다!</td></tr>";
        echo "<tr><td>문제 원인</td><td class='error'>소수점(.)이 JavaScript에서 숫자가 아닌 문자로 제거되면서 '30000.00' → '3000000'으로 변환됨</td></tr>";
    }
    
    echo "</table>";
    echo "</div>";
    
    // 상품 상세 정보
    echo "<div class='section'>";
    echo "<h2>3. 상품 상세 정보</h2>";
    echo "<table>";
    echo "<tr><th>항목</th><th>값</th></tr>";
    echo "<tr><td>인터넷 가입처</td><td>" . htmlspecialchars($order['registration_place'] ?? '-') . "</td></tr>";
    echo "<tr><td>결합여부</td><td>" . htmlspecialchars($order['service_type'] ?? '-') . "</td></tr>";
    echo "<tr><td>가입 속도</td><td>" . htmlspecialchars($order['speed_option'] ?? '-') . "</td></tr>";
    echo "<tr><td>월 요금제 (DB)</td><td><span class='raw-value'>" . htmlspecialchars($order['monthly_fee'] ?? '-') . "</span></td></tr>";
    echo "</table>";
    echo "</div>";
    
    // additional_info의 product_snapshot 확인
    echo "<div class='section'>";
    echo "<h2>4. additional_info (product_snapshot) 확인</h2>";
    
    $additionalInfo = json_decode($order['additional_info'] ?? '{}', true);
    $snapshot = $additionalInfo['product_snapshot'] ?? [];
    
    if (!empty($snapshot)) {
        echo "<table>";
        echo "<tr><th>항목</th><th>값</th></tr>";
        foreach ($snapshot as $key => $value) {
            if ($key === 'monthly_fee') {
                echo "<tr><td><strong>{$key}</strong></td><td><span class='raw-value'>" . htmlspecialchars($value) . "</span></td></tr>";
            } else {
                echo "<tr><td>{$key}</td><td>" . htmlspecialchars(is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value) . "</td></tr>";
            }
        }
        echo "</table>";
        
        if (isset($snapshot['monthly_fee'])) {
            $snapshotFee = $snapshot['monthly_fee'];
            echo "<p class='info'>스냅샷의 monthly_fee: <span class='raw-value'>{$snapshotFee}</span></p>";
            
            // 스냅샷 값도 분석
            $snapshotNumeric = preg_replace('/[^0-9]/', '', $snapshotFee);
            if ($snapshotNumeric !== '') {
                $snapshotInt = intval($snapshotNumeric);
                echo "<p class='info'>스냅샷 숫자 추출: <strong>" . number_format($snapshotInt) . "</strong></p>";
            }
        }
    } else {
        echo "<p class='info'>product_snapshot이 없습니다.</p>";
    }
    echo "</div>";
    
    // JavaScript 시뮬레이션
    echo "<div class='section'>";
    echo "<h2>5. JavaScript 처리 시뮬레이션</h2>";
    
    $jsCode = "
// JavaScript에서의 처리 과정
const fee = '{$monthlyFee}';
console.log('원본 값:', fee);

// validateMonthlyFee 함수의 로직
const numericValue = parseInt(String(fee).replace(/[^0-9]/g, ''));
console.log('숫자만 추출:', numericValue);

if (numericValue > 200000) {
    if (numericValue >= 1000000) {
        const suggestedValue = Math.floor(numericValue / 100);
        console.log('오류 감지! 예상값:', suggestedValue);
    }
}
";
    
    echo "<pre style='background: #f3f4f6; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
    echo htmlspecialchars($jsCode);
    echo "</pre>";
    
    // 실제 JavaScript 실행 결과
    $jsNumericValue = intval(preg_replace('/[^0-9]/', '', $monthlyFee));
    echo "<table>";
    echo "<tr><th>단계</th><th>결과</th></tr>";
    echo "<tr><td>원본 값</td><td><span class='raw-value'>{$monthlyFee}</span></td></tr>";
    echo "<tr><td>String(fee)</td><td><span class='raw-value'>" . htmlspecialchars((string)$monthlyFee) . "</span></td></tr>";
    echo "<tr><td>.replace(/[^0-9]/g, '')</td><td><span class='raw-value'>{$jsNumericValue}</span></td></tr>";
    echo "<tr><td>parseInt()</td><td><strong>" . number_format($jsNumericValue) . "</strong></td></tr>";
    
    if ($jsNumericValue >= 1000000) {
        $suggested = floor($jsNumericValue / 100);
        echo "<tr><td>결과</td><td class='error'>⚠ 3,000,000원 (예상값: " . number_format($suggested) . "원)</td></tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // 해결 방법
    echo "<div class='section'>";
    echo "<h2>6. 해결 방법</h2>";
    echo "<ol>";
    echo "<li><strong>데이터베이스 수정:</strong> monthly_fee 값을 '30000원' 형식으로 변경</li>";
    echo "<li><strong>컬럼 타입 확인:</strong> VARCHAR(50)로 되어 있는지 확인</li>";
    echo "<li><strong>JavaScript 수정:</strong> 소수점을 고려한 파싱 로직 추가</li>";
    echo "</ol>";
    
    // 즉시 수정 SQL
    if (strpos($monthlyFee, '.') !== false || preg_match('/^[0-9]+$/', $monthlyFee)) {
        $newValue = preg_replace('/[^0-9]/', '', $monthlyFee) . '원';
        echo "<p class='warning'><strong>수정 SQL:</strong></p>";
        echo "<pre style='background: #f3f4f6; padding: 15px; border-radius: 5px;'>";
        echo "UPDATE product_internet_details \n";
        echo "SET monthly_fee = '{$newValue}' \n";
        echo "WHERE product_id = {$order['product_id']};";
        echo "</pre>";
    }
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p class='error'>데이터베이스 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>

