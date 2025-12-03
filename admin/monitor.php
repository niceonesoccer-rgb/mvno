<?php
/**
 * 관리자 모니터링 페이지
 * 동시 접속 수 및 통계 확인
 */

require_once '../includes/monitor.php';

$monitor = new ConnectionMonitor();
$currentConnections = $monitor->getCurrentConnections();
$recentStats = $monitor->getRecentStats(5);
$limit = 30; // 호스팅 제한
$percentage = ($currentConnections / $limit) * 100;
$status = $currentConnections >= $limit ? 'danger' : ($currentConnections >= $limit * 0.8 ? 'warning' : 'success');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>접속 모니터링</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
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
            margin-bottom: 30px;
            color: #333;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin-top: 10px;
        }
        .progress-fill {
            height: 100%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .progress-success { background: #28a745; }
        .progress-warning { background: #ffc107; }
        .progress-danger { background: #dc3545; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .page-list {
            margin-top: 20px;
        }
        .page-item {
            padding: 10px;
            background: #f8f9fa;
            margin-bottom: 5px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
        }
        .refresh-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
        }
        .refresh-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 접속 모니터링</h1>
        
        <div class="stat-card">
            <div class="stat-label">현재 동시 접속 수</div>
            <div class="stat-value"><?php echo $currentConnections; ?> / <?php echo $limit; ?></div>
            <div class="progress-bar">
                <div class="progress-fill progress-<?php echo $status; ?>" 
                     style="width: <?php echo min($percentage, 100); ?>%">
                    <?php echo round($percentage, 1); ?>%
                </div>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">최근 5분 접속 수</div>
                <div class="stat-value"><?php echo $recentStats['total']; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">고유 IP 수</div>
                <div class="stat-value"><?php echo $recentStats['unique_ips']; ?></div>
            </div>
        </div>
        
        <?php if (!empty($recentStats['pages'])): ?>
        <div class="stat-card">
            <div class="stat-label">인기 페이지 (최근 5분)</div>
            <div class="page-list">
                <?php 
                $count = 0;
                foreach ($recentStats['pages'] as $page => $views): 
                    if ($count++ >= 10) break;
                ?>
                    <div class="page-item">
                        <span><?php echo htmlspecialchars($page); ?></span>
                        <span><strong><?php echo $views; ?></strong> 회</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <button class="refresh-btn" onclick="location.reload()">🔄 새로고침</button>
        
        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 4px; color: #856404;">
            <strong>⚠️ 주의:</strong> 
            <?php if ($currentConnections >= $limit): ?>
                <span style="color: #dc3545;">현재 접속 수가 제한에 도달했습니다!</span>
            <?php elseif ($currentConnections >= $limit * 0.8): ?>
                <span style="color: #856404;">접속 수가 80% 이상입니다. 주의하세요.</span>
            <?php else: ?>
                <span>현재 접속 수는 정상 범위입니다.</span>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // 30초마다 자동 새로고침
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>










