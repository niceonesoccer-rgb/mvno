<?php
/**
 * 주문 정보 확인 스크립트
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>주문 정보 확인</title>
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
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
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
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-processing { background-color: #17a2b8; color: #fff; }
        .status-completed { background-color: #28a745; color: #fff; }
        .status-cancelled { background-color: #6c757d; color: #fff; }
        .status-rejected { background-color: #dc3545; color: #fff; }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .info-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-section p {
            margin: 5px 0;
        }
        .info-section ul {
            margin: 10px 0 10px 20px;
            padding: 0;
        }
        .info-section li {
            margin: 5px 0;
        }
        .info-section pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.5;
        }
        .info-section h3 {
            margin-top: 20px;
            margin-bottom: 10px;
            color: #333;
        }
        table {
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 주문 정보 확인</h1>

        <?php
        $pdo = getDBConnection();
        
        if (!$pdo) {
            echo '<div class="no-data">❌ 데이터베이스 연결 실패</div>';
            exit;
        }

        try {
            // 통계 정보
            echo '<div class="stats">';
            
            // 전체 주문 수
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM product_applications");
            $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // 상태별 주문 수 (상태 매핑 적용)
            $stmt = $pdo->query("
                SELECT application_status, COUNT(*) as count 
                FROM product_applications 
                GROUP BY application_status
            ");
            $statusCounts = [];
            $statusMapping = [
                'received' => 'pending',
                'activating' => 'processing',
                'on_hold' => 'rejected',
                'activation_completed' => 'completed',
                'installation_completed' => 'completed',
                'pending' => 'pending',
                'processing' => 'processing',
                'completed' => 'completed',
                'cancelled' => 'cancelled',
                'rejected' => 'rejected'
            ];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $mappedStatus = $statusMapping[$row['application_status']] ?? 'pending';
                $statusCounts[$mappedStatus] = ($statusCounts[$mappedStatus] ?? 0) + $row['count'];
            }
            
            // 상품 타입별 주문 수
            $stmt = $pdo->query("
                SELECT product_type, COUNT(*) as count 
                FROM product_applications 
                GROUP BY product_type
            ");
            $typeCounts = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $typeCounts[$row['product_type']] = $row['count'];
            }
            
            echo '<div class="stat-card">';
            echo '<h3>전체 주문</h3>';
            echo '<p class="number">' . number_format($totalOrders) . '</p>';
            echo '</div>';
            
            echo '<div class="stat-card">';
            echo '<h3>대기 중</h3>';
            echo '<p class="number">' . number_format($statusCounts['pending'] ?? 0) . '</p>';
            echo '</div>';
            
            echo '<div class="stat-card">';
            echo '<h3>처리 중</h3>';
            echo '<p class="number">' . number_format($statusCounts['processing'] ?? 0) . '</p>';
            echo '</div>';
            
            echo '<div class="stat-card">';
            echo '<h3>완료</h3>';
            echo '<p class="number">' . number_format($statusCounts['completed'] ?? 0) . '</p>';
            echo '</div>';
            
            echo '</div>';

            // 주문 목록 조회 (additional_info 포함)
            $stmt = $pdo->query("
                SELECT 
                    pa.id,
                    pa.order_number,
                    pa.product_id,
                    pa.seller_id,
                    pa.product_type,
                    pa.application_status,
                    pa.created_at,
                    pa.updated_at,
                    ac.name as customer_name,
                    ac.phone as customer_phone,
                    ac.email as customer_email,
                    ac.additional_info,
                    p.status as product_status,
                    mvno.plan_name as mvno_plan_name,
                    mvno.provider as mvno_provider,
                    mno.device_name as mno_device_name,
                    mno.common_provider as mno_provider,
                    internet.registration_place as internet_registration_place
                FROM product_applications pa
                LEFT JOIN application_customers ac ON pa.id = ac.application_id
                LEFT JOIN products p ON pa.product_id = p.id
                LEFT JOIN product_mvno_details mvno ON pa.product_id = mvno.product_id AND pa.product_type = 'mvno'
                LEFT JOIN product_mno_details mno ON pa.product_id = mno.product_id AND pa.product_type = 'mno'
                LEFT JOIN product_internet_details internet ON pa.product_id = internet.product_id AND pa.product_type = 'internet'
                ORDER BY pa.created_at DESC
                LIMIT 100
            ");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // additional_info 파싱 및 주문 정보 추출
            foreach ($orders as &$order) {
                // additional_info JSON 파싱
                if (!empty($order['additional_info'])) {
                    $order['additional_info'] = json_decode($order['additional_info'], true) ?: [];
                } else {
                    $order['additional_info'] = [];
                }
                
                // 주문번호 사용 (DB에 저장된 값, 없으면 생성)
                if (empty($order['order_number'])) {
                    // 기존 주문번호가 없는 경우 (하위 호환성)
                    $createdAt = new DateTime($order['created_at']);
                    $order['order_number'] = $createdAt->format('ymdH') . '-' . str_pad($order['id'], 4, '0', STR_PAD_LEFT);
                }
                
                // 가입형태 추출
                $order['subscription_type'] = $order['additional_info']['subscription_type'] ?? '';
                $subscriptionTypeLabels = [
                    'new' => '신규가입',
                    'port' => '번호이동',
                    'change' => '기기변경'
                ];
                $order['subscription_type_label'] = $subscriptionTypeLabels[$order['subscription_type']] ?? $order['subscription_type'] ?? '-';
                
                // 상품명 추출
                $order['product_name'] = '-';
                if ($order['product_type'] === 'mvno') {
                    $order['product_name'] = $order['mvno_plan_name'] ?? '-';
                } elseif ($order['product_type'] === 'mno') {
                    $order['product_name'] = $order['mno_device_name'] ?? '-';
                } elseif ($order['product_type'] === 'internet') {
                    $order['product_name'] = $order['internet_registration_place'] ?? '-';
                }
                
                // 통신사 추출
                $order['provider'] = '-';
                if ($order['product_type'] === 'mvno') {
                    $order['provider'] = $order['mvno_provider'] ?? '-';
                } elseif ($order['product_type'] === 'mno') {
                    $order['provider'] = $order['mno_provider'] ?? '-';
                }
                
                // product_snapshot에서 정보 가져오기 (신청 당시 정보)
                $productSnapshot = $order['additional_info']['product_snapshot'] ?? [];
                if (!empty($productSnapshot) && is_array($productSnapshot)) {
                    // product_snapshot의 정보로 덮어쓰기
                    if (isset($productSnapshot['plan_name']) && $order['product_type'] === 'mvno') {
                        $order['product_name'] = $productSnapshot['plan_name'];
                    }
                    if (isset($productSnapshot['provider'])) {
                        $order['provider'] = $productSnapshot['provider'];
                    }
                    if (isset($productSnapshot['device_name']) && $order['product_type'] === 'mno') {
                        $order['product_name'] = $productSnapshot['device_name'];
                    }
                }
            }
            unset($order);

            // 상품 타입별 주문 정보
            echo '<h2>📊 상품 타입별 주문 현황</h2>';
            echo '<div class="info-section">';
            echo '<p><strong>알뜰폰(MVNO):</strong> ' . number_format($typeCounts['mvno'] ?? 0) . '건</p>';
            echo '<p><strong>통신사폰(MNO):</strong> ' . number_format($typeCounts['mno'] ?? 0) . '건</p>';
            echo '<p><strong>인터넷(Internet):</strong> ' . number_format($typeCounts['internet'] ?? 0) . '건</p>';
            echo '</div>';

            // 주문 목록 테이블
            echo '<h2>📋 주문 목록 (최근 100건)</h2>';
            
            if (empty($orders)) {
                echo '<div class="no-data">주문 내역이 없습니다.</div>';
            } else {
                echo '<table>';
                echo '<thead>';
                echo '<tr>';
                echo '<th>주문번호</th>';
                echo '<th>주문ID</th>';
                echo '<th>상품타입</th>';
                echo '<th>통신사</th>';
                echo '<th>상품명</th>';
                echo '<th>가입형태</th>';
                echo '<th>고객명</th>';
                echo '<th>전화번호</th>';
                echo '<th>이메일</th>';
                echo '<th>상태</th>';
                echo '<th>신청일시</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                foreach ($orders as $order) {
                    // 상태 매핑 (판매자 페이지와 동일)
                    $statusMapping = [
                        'received' => '접수',
                        'activating' => '개통중',
                        'on_hold' => '보류',
                        'cancelled' => '취소',
                        'activation_completed' => '개통완료',
                        'installation_completed' => '설치완료',
                        // 기존 상태 호환성 유지
                        'pending' => '접수',
                        'processing' => '개통중',
                        'completed' => '설치완료',
                        'rejected' => '보류'
                    ];
                    
                    $statusText = $statusMapping[$order['application_status']] ?? $order['application_status'];
                    $statusClass = 'status-' . ($order['application_status'] === 'pending' || $order['application_status'] === 'received' ? 'pending' : 
                                      ($order['application_status'] === 'processing' || $order['application_status'] === 'activating' ? 'processing' :
                                      ($order['application_status'] === 'completed' || $order['application_status'] === 'activation_completed' || $order['application_status'] === 'installation_completed' ? 'completed' :
                                      ($order['application_status'] === 'cancelled' ? 'cancelled' : 'rejected'))));
                    
                    $typeText = [
                        'mvno' => '알뜰폰',
                        'mno' => '통신사폰',
                        'internet' => '인터넷'
                    ];
                    
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($order['order_number'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($order['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($typeText[$order['product_type']] ?? $order['product_type']) . '</td>';
                    echo '<td>' . htmlspecialchars($order['provider'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($order['product_name'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($order['subscription_type_label'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($order['customer_name'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($order['customer_phone'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($order['customer_email'] ?? '-') . '</td>';
                    echo '<td><span class="status ' . $statusClass . '">' . htmlspecialchars($statusText) . '</span></td>';
                    echo '<td>' . htmlspecialchars($order['created_at']) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
            }

            // additional_info 구조 예시
            if (!empty($orders)) {
                echo '<h2>📦 주문 저장 데이터 구조</h2>';
                echo '<div class="info-section">';
                echo '<h3>일반회원이 주문 시 저장되는 데이터:</h3>';
                echo '<p><strong>1. product_applications 테이블:</strong></p>';
                echo '<ul>';
                echo '<li>id: 주문 ID</li>';
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
                echo '<li><strong>additional_info (JSON):</strong> 추가 정보</li>';
                echo '</ul>';
                
                echo '<p><strong>3. additional_info 구조 (JSON):</strong></p>';
                $sampleOrder = $orders[0];
                if (!empty($sampleOrder['additional_info'])) {
                    echo '<pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;">';
                    echo htmlspecialchars(json_encode($sampleOrder['additional_info'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    echo '</pre>';
                } else {
                    echo '<p style="color: #999;">additional_info가 비어있습니다.</p>';
                }
                
                echo '<p><strong>주요 필드:</strong></p>';
                echo '<ul>';
                echo '<li><strong>subscription_type:</strong> 가입 형태 (new=신규가입, mnp=번호이동, change=기기변경)</li>';
                echo '<li><strong>product_snapshot:</strong> 신청 당시의 상품 정보 전체 (클레임 처리용)</li>';
                echo '</ul>';
                
                echo '<p><strong>주문번호 생성 규칙:</strong></p>';
                echo '<ul>';
                echo '<li>형식: YYMMDDHH-0001 (쇼핑몰 일반 형식)</li>';
                echo '<li>예시: 25121519-0001</li>';
                echo '<li>앞 8자리: 년월일시간 (YYMMDDHH)</li>';
                echo '<li>뒤 4자리: 순번 (0001 ~ 9999)</li>';
                echo '<li>같은 시간(시 단위)에 여러 주문이 있을 경우 순번이 증가합니다</li>';
                echo '<li>총 12자리 (하이픈 포함)</li>';
                echo '</ul>';
                echo '</div>';
            }

            // 테이블 구조 정보
            echo '<h2>📐 테이블 구조 정보</h2>';
            
            // product_applications 테이블 구조
            echo '<h3>product_applications (주문/신청 테이블)</h3>';
            $stmt = $pdo->query("DESCRIBE product_applications");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo '<table>';
            echo '<thead><tr><th>컬럼명</th><th>타입</th><th>NULL</th><th>키</th><th>기본값</th><th>추가</th></tr></thead>';
            echo '<tbody>';
            foreach ($columns as $col) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                echo '<td>' . htmlspecialchars($col['Extra'] ?? '') . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';

            // application_customers 테이블 구조
            echo '<h3>application_customers (고객 정보 테이블)</h3>';
            $stmt = $pdo->query("DESCRIBE application_customers");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo '<table>';
            echo '<thead><tr><th>컬럼명</th><th>타입</th><th>NULL</th><th>키</th><th>기본값</th><th>추가</th></tr></thead>';
            echo '<tbody>';
            foreach ($columns as $col) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                echo '<td>' . htmlspecialchars($col['Extra'] ?? '') . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';

        } catch (PDOException $e) {
            echo '<div class="no-data">❌ 오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</body>
</html>















