<?php
/**
 * 광고 분석 페이지
 * 경로: /admin/advertisement/analytics.php
 */

require_once __DIR__ . '/../includes/admin-header.php';

// 페이지 제목 설정
$pageTitle = '광고 분석';

// 데이터베이스 연결
$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

// 날짜 범위 설정 (기본값: 최근 30일)
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$startDate = date('Y-m-d', strtotime("-{$days} days"));
$endDate = date('Y-m-d');

// 광고별 통계 조회
$adStats = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            ra.id as advertisement_id,
            ra.product_id,
            ra.seller_id,
            ra.product_type,
            ra.start_datetime,
            ra.end_datetime,
            ra.status,
            p.name as product_name,
            u.name as seller_name,
            COALESCE(SUM(aa.impression_count), 0) as total_impressions,
            COALESCE(SUM(aa.click_count), 0) as total_clicks,
            COALESCE(AVG(aa.ctr), 0) as avg_ctr,
            COALESCE(SUM(aa.unique_impressions), 0) as total_unique_impressions,
            COALESCE(SUM(aa.unique_clicks), 0) as total_unique_clicks
        FROM rotation_advertisements ra
        LEFT JOIN advertisement_analytics aa ON ra.id = aa.advertisement_id 
            AND aa.stat_date >= :start_date 
            AND aa.stat_date <= :end_date
        LEFT JOIN products p ON ra.product_id = p.id
        LEFT JOIN users u ON ra.seller_id = u.user_id
        WHERE ra.created_at >= :start_date
        GROUP BY ra.id, ra.product_id, ra.seller_id, ra.product_type, ra.start_datetime, ra.end_datetime, ra.status, p.name, u.name
        ORDER BY ra.created_at DESC
    ");
    $stmt->execute([
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ]);
    $adStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('광고 통계 조회 실패: ' . $e->getMessage());
}

// 전체 통계 요약
$summaryStats = [
    'total_ads' => count($adStats),
    'total_impressions' => 0,
    'total_clicks' => 0,
    'total_ctr' => 0,
    'total_unique_impressions' => 0,
    'total_unique_clicks' => 0
];

foreach ($adStats as $stat) {
    $summaryStats['total_impressions'] += (int)$stat['total_impressions'];
    $summaryStats['total_clicks'] += (int)$stat['total_clicks'];
    $summaryStats['total_unique_impressions'] += (int)$stat['total_unique_impressions'];
    $summaryStats['total_unique_clicks'] += (int)$stat['total_unique_clicks'];
}

if ($summaryStats['total_impressions'] > 0) {
    $summaryStats['total_ctr'] = ($summaryStats['total_clicks'] / $summaryStats['total_impressions']) * 100;
}

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - 관리자</title>
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e5e7eb;
        }
        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: #111827;
        }
        .date-filter {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .date-filter select {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .summary-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .summary-card-title {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .summary-card-value {
            font-size: 24px;
            font-weight: 600;
            color: #111827;
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stats-table th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        .stats-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .stats-table tr:hover {
            background: #f9fafb;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-expired {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-cancelled {
            background: #f3f4f6;
            color: #374151;
        }
        .ctr-value {
            font-weight: 600;
            color: #059669;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    
    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">📊 광고 분석</h1>
            <div class="date-filter">
                <label for="days">기간:</label>
                <select id="days" onchange="window.location.href='?days=' + this.value">
                    <option value="7" <?php echo $days === 7 ? 'selected' : ''; ?>>최근 7일</option>
                    <option value="30" <?php echo $days === 30 ? 'selected' : ''; ?>>최근 30일</option>
                    <option value="90" <?php echo $days === 90 ? 'selected' : ''; ?>>최근 90일</option>
                    <option value="365" <?php echo $days === 365 ? 'selected' : ''; ?>>최근 1년</option>
                </select>
            </div>
        </div>
        
        <!-- 요약 통계 -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-card-title">총 광고 수</div>
                <div class="summary-card-value"><?php echo number_format($summaryStats['total_ads']); ?>개</div>
            </div>
            <div class="summary-card">
                <div class="summary-card-title">총 노출 수</div>
                <div class="summary-card-value"><?php echo number_format($summaryStats['total_impressions']); ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-card-title">총 클릭 수</div>
                <div class="summary-card-value"><?php echo number_format($summaryStats['total_clicks']); ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-card-title">평균 CTR</div>
                <div class="summary-card-value"><?php echo number_format($summaryStats['total_ctr'], 2); ?>%</div>
            </div>
            <div class="summary-card">
                <div class="summary-card-title">고유 노출 수</div>
                <div class="summary-card-value"><?php echo number_format($summaryStats['total_unique_impressions']); ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-card-title">고유 클릭 수</div>
                <div class="summary-card-value"><?php echo number_format($summaryStats['total_unique_clicks']); ?></div>
            </div>
        </div>
        
        <!-- 광고별 상세 통계 -->
        <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2 style="margin-bottom: 16px; font-size: 18px; font-weight: 600;">광고별 상세 통계</h2>
            
            <?php if (empty($adStats)): ?>
                <div class="no-data">
                    <p>해당 기간에 광고 데이터가 없습니다.</p>
                    <p style="margin-top: 8px; font-size: 14px; color: #9ca3af;">
                        광고 노출/클릭 추적이 시작되면 통계가 표시됩니다.
                    </p>
                </div>
            <?php else: ?>
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>광고 ID</th>
                            <th>상품명</th>
                            <th>판매자</th>
                            <th>상품 타입</th>
                            <th>상태</th>
                            <th>노출 수</th>
                            <th>클릭 수</th>
                            <th>CTR</th>
                            <th>고유 노출</th>
                            <th>고유 클릭</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adStats as $stat): ?>
                            <?php
                            $ctr = $stat['total_impressions'] > 0 
                                ? ($stat['total_clicks'] / $stat['total_impressions']) * 100 
                                : 0;
                            $statusClass = 'badge-' . $stat['status'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stat['advertisement_id']); ?></td>
                                <td><?php echo htmlspecialchars($stat['product_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($stat['seller_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($stat['product_type']); ?></td>
                                <td>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php 
                                        $statusText = [
                                            'active' => '진행중',
                                            'expired' => '종료',
                                            'cancelled' => '취소'
                                        ];
                                        echo $statusText[$stat['status']] ?? $stat['status'];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($stat['total_impressions']); ?></td>
                                <td><?php echo number_format($stat['total_clicks']); ?></td>
                                <td class="ctr-value"><?php echo number_format($ctr, 2); ?>%</td>
                                <td><?php echo number_format($stat['total_unique_impressions']); ?></td>
                                <td><?php echo number_format($stat['total_unique_clicks']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 24px; padding: 16px; background: #f3f4f6; border-radius: 8px; font-size: 14px; color: #6b7280;">
            <strong>💡 참고사항:</strong>
            <ul style="margin-top: 8px; padding-left: 20px;">
                <li>통계는 <code>advertisement_analytics</code> 테이블의 집계 데이터를 기반으로 합니다.</li>
                <li>실시간 통계가 필요하면 <a href="<?php echo getAssetPath('/admin/cron/aggregate-ad-analytics.php'); ?>" style="color: #2563eb;">통계 집계 스크립트</a>를 실행하세요.</li>
                <li>CTR (Click Through Rate) = 클릭 수 / 노출 수 × 100</li>
            </ul>
        </div>
    </div>
</body>
</html>
