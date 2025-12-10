<?php
/**
 * 등록된 알뜰폰 상품 리스트 확인 페이지
 * 테스트용 - 실제 사용 시 삭제하거나 보안 처리 필요
 */

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/plan-data.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>등록된 알뜰폰 상품 리스트</title>
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
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .stat-item {
            flex: 1;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #10b981;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f9f9f9;
            font-weight: 600;
            color: #333;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .status-active {
            color: #10b981;
            font-weight: 600;
        }
        .status-inactive {
            color: #ef4444;
            font-weight: 600;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .product-link {
            color: #10b981;
            text-decoration: none;
        }
        .product-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>등록된 알뜰폰 상품 리스트</h1>
        
        <?php
        // 모든 상품 가져오기
        $allProducts = getAllMvnoProductsList(null);
        $activeProducts = getAllMvnoProductsList('active');
        $inactiveProducts = getAllMvnoProductsList('inactive');
        
        // 카드 형식으로 변환
        $cardProducts = [];
        foreach ($activeProducts as $product) {
            $cardProducts[] = convertMvnoProductToPlanCard($product);
        }
        ?>
        
        <div class="stats">
            <div class="stat-item">
                <div class="stat-label">전체 상품</div>
                <div class="stat-value"><?php echo count($allProducts); ?>개</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">활성 상품</div>
                <div class="stat-value"><?php echo count($activeProducts); ?>개</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">비활성 상품</div>
                <div class="stat-value"><?php echo count($inactiveProducts); ?>개</div>
            </div>
        </div>
        
        <?php if (empty($allProducts)): ?>
            <div class="no-data">
                <p>등록된 상품이 없습니다.</p>
                <p><a href="/MVNO/seller/products/mvno.php">상품 등록하기</a></p>
            </div>
        <?php else: ?>
            <h2>전체 상품 목록 (<?php echo count($allProducts); ?>개)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>상태</th>
                        <th>통신사</th>
                        <th>요금제명</th>
                        <th>월 요금</th>
                        <th>할인 후 요금</th>
                        <th>데이터</th>
                        <th>통화/문자</th>
                        <th>신청 수</th>
                        <th>등록일</th>
                        <th>카드 미리보기</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allProducts as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['id']); ?></td>
                        <td>
                            <span class="status-<?php echo $product['status']; ?>">
                                <?php 
                                $statusText = [
                                    'active' => '활성',
                                    'inactive' => '비활성',
                                    'deleted' => '삭제됨'
                                ];
                                echo $statusText[$product['status']] ?? $product['status'];
                                ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($product['provider']); ?></td>
                        <td><?php echo htmlspecialchars($product['plan_name']); ?></td>
                        <td><?php echo number_format($product['price_main']); ?>원</td>
                        <td>
                            <?php 
                            if ($product['price_after'] === null || $product['price_after'] === '0') {
                                echo '공짜';
                            } else {
                                echo number_format($product['price_after']) . '원';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if ($product['data_amount'] === '무제한') {
                                echo '무제한';
                            } elseif ($product['data_amount'] === '직접입력' && !empty($product['data_amount_value'])) {
                                echo $product['data_amount_value'] . $product['data_unit'];
                            } else {
                                echo htmlspecialchars($product['data_amount'] ?? '');
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            echo htmlspecialchars($product['call_type'] ?? '') . ' / ' . htmlspecialchars($product['sms_type'] ?? '');
                            ?>
                        </td>
                        <td><?php echo number_format($product['application_count']); ?>명</td>
                        <td><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                        <td>
                            <?php if ($product['status'] === 'active'): ?>
                                <a href="/MVNO/mvno/mvno.php" class="product-link" target="_blank">보기</a>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (!empty($cardProducts)): ?>
            <h2 style="margin-top: 40px;">카드 형식 변환 결과 (활성 상품만, <?php echo count($cardProducts); ?>개)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>통신사</th>
                        <th>제목</th>
                        <th>데이터 정보</th>
                        <th>기능</th>
                        <th>월 요금</th>
                        <th>할인 후 요금</th>
                        <th>선택 수</th>
                        <th>사은품</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cardProducts as $plan): ?>
                    <tr>
                        <td><?php echo $plan['id']; ?></td>
                        <td><?php echo htmlspecialchars($plan['provider']); ?></td>
                        <td><?php echo htmlspecialchars($plan['title']); ?></td>
                        <td><?php echo htmlspecialchars($plan['data_main']); ?></td>
                        <td><?php echo implode(', ', $plan['features']); ?></td>
                        <td><?php echo htmlspecialchars($plan['price_main']); ?></td>
                        <td><?php echo htmlspecialchars($plan['price_after']); ?></td>
                        <td><?php echo htmlspecialchars($plan['selection_count']); ?></td>
                        <td>
                            <?php 
                            if (!empty($plan['gifts'])) {
                                echo count($plan['gifts']) . '개: ' . implode(', ', array_slice($plan['gifts'], 0, 3));
                                if (count($plan['gifts']) > 3) {
                                    echo '...';
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>

