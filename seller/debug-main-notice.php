<?php
/**
 * 판매자 메인공지 디버깅 페이지
 * 경로: /MVNO/seller/debug-main-notice.php
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/notice-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    die('판매자만 접근할 수 있습니다.');
}

$pdo = getDBConnection();
$currentDate = date('Y-m-d');

// 모든 판매자 공지사항 조회
$allNotices = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM notices WHERE target_audience = 'seller' ORDER BY created_at DESC");
        $allNotices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

// 메인공지 조회
$mainNotice = getSellerMainBanner();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>메인공지 디버깅</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }
        .debug-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 10px;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            background: #f9fafb;
            border-radius: 6px;
        }
        .section h2 {
            color: #6366f1;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #6366f1;
            color: white;
        }
        .status-ok {
            color: #10b981;
            font-weight: bold;
        }
        .status-fail {
            color: #ef4444;
            font-weight: bold;
        }
        .main-notice {
            background: #dbeafe;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
        }
        .query-box {
            background: #1f2937;
            color: #10b981;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            overflow-x: auto;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <h1>판매자 메인공지 디버깅</h1>
        
        <div class="section">
            <h2>현재 날짜</h2>
            <p><?= $currentDate ?></p>
        </div>
        
        <div class="section">
            <h2>메인공지 조회 결과</h2>
            <?php if ($mainNotice): ?>
                <div class="main-notice">
                    <p><strong>✅ 메인공지가 조회되었습니다!</strong></p>
                    <table>
                        <tr><th>필드</th><th>값</th></tr>
                        <tr><td>ID</td><td><?= htmlspecialchars($mainNotice['id'] ?? 'N/A') ?></td></tr>
                        <tr><td>제목</td><td><?= htmlspecialchars($mainNotice['title'] ?? 'N/A') ?></td></tr>
                        <tr><td>target_audience</td><td><?= htmlspecialchars($mainNotice['target_audience'] ?? 'N/A') ?></td></tr>
                        <tr><td>show_on_main</td><td><?= $mainNotice['show_on_main'] ?? 'N/A' ?></td></tr>
                        <tr><td>start_at</td><td><?= $mainNotice['start_at'] ?? 'NULL' ?></td></tr>
                        <tr><td>end_at</td><td><?= $mainNotice['end_at'] ?? 'NULL' ?></td></tr>
                        <tr><td>banner_type</td><td><?= htmlspecialchars($mainNotice['banner_type'] ?? 'N/A') ?></td></tr>
                        <tr><td>image_url</td><td><?= !empty($mainNotice['image_url']) ? htmlspecialchars($mainNotice['image_url']) : 'NULL' ?></td></tr>
                        <tr><td>link_url</td><td><?= !empty($mainNotice['link_url']) ? htmlspecialchars($mainNotice['link_url']) : 'NULL' ?></td></tr>
                    </table>
                </div>
            <?php else: ?>
                <p class="status-fail">❌ 메인공지가 조회되지 않았습니다.</p>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>모든 판매자 공지사항 (최근 10개)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>제목</th>
                        <th>target_audience</th>
                        <th>show_on_main</th>
                        <th>start_at</th>
                        <th>end_at</th>
                        <th>날짜 조건</th>
                        <th>메인공지 조건</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($allNotices, 0, 10) as $notice): 
                        $showOnMain = $notice['show_on_main'] ?? 0;
                        $startAt = $notice['start_at'] ?? null;
                        $endAt = $notice['end_at'] ?? null;
                        $targetAudience = $notice['target_audience'] ?? 'all';
                        
                        $dateOk = true;
                        if ($startAt && $startAt > $currentDate) $dateOk = false;
                        if ($endAt && $endAt < $currentDate) $dateOk = false;
                        
                        $mainOk = ($targetAudience === 'seller') && ($showOnMain == 1) && $dateOk;
                    ?>
                        <tr style="<?= $mainOk ? 'background: #dbeafe;' : '' ?>">
                            <td><?= htmlspecialchars(substr($notice['id'] ?? 'N/A', 0, 20)) ?></td>
                            <td><?= htmlspecialchars($notice['title'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($targetAudience) ?></td>
                            <td><?= $showOnMain ?></td>
                            <td><?= $startAt ?: 'NULL' ?></td>
                            <td><?= $endAt ?: 'NULL' ?></td>
                            <td class="<?= $dateOk ? 'status-ok' : 'status-fail' ?>">
                                <?= $dateOk ? '✅ OK' : '❌ FAIL' ?>
                            </td>
                            <td class="<?= $mainOk ? 'status-ok' : 'status-fail' ?>">
                                <?= $mainOk ? '✅ 메인공지' : '❌ 아님' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>실행된 쿼리</h2>
            <div class="query-box">
SELECT * FROM notices 
WHERE target_audience = 'seller'
AND (show_on_main = 1 OR CAST(show_on_main AS UNSIGNED) = 1)
AND (start_at IS NULL OR start_at <= '<?= $currentDate ?>')
AND (end_at IS NULL OR end_at >= '<?= $currentDate ?>')
ORDER BY created_at DESC
LIMIT 1
            </div>
        </div>
        
        <div class="section">
            <h2>직접 쿼리 실행 결과 (파라미터 바인딩)</h2>
            <?php
            if ($pdo) {
                try {
                    $sql = "SELECT * FROM notices 
                            WHERE target_audience = 'seller'
                            AND (show_on_main = 1 OR CAST(show_on_main AS UNSIGNED) = 1)
                            AND (start_at IS NULL OR start_at <= :current_date)
                            AND (end_at IS NULL OR end_at >= :current_date)
                            ORDER BY created_at DESC
                            LIMIT 1";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':current_date', $currentDate, PDO::PARAM_STR);
                    $stmt->execute();
                    $directResult = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($directResult) {
                        echo '<p class="status-ok">✅ 직접 쿼리로 메인공지 조회 성공</p>';
                        echo '<table>';
                        echo '<tr><th>필드</th><th>값</th></tr>';
                        foreach ($directResult as $key => $value) {
                            echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value ?? 'NULL') . '</td></tr>';
                        }
                        echo '</table>';
                    } else {
                        echo '<p class="status-fail">❌ 직접 쿼리로도 메인공지 조회 실패</p>';
                        echo '<p>날짜 조건을 확인하세요. 현재 날짜: ' . $currentDate . '</p>';
                    }
                } catch (PDOException $e) {
                    echo '<p class="status-fail">쿼리 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
            }
            ?>
        </div>
        
        <div class="section">
            <h2>직접 쿼리 실행 결과 (문자열 치환)</h2>
            <?php
            if ($pdo) {
                try {
                    // 파라미터 바인딩 없이 직접 실행
                    $sql = "SELECT * FROM notices 
                            WHERE target_audience = 'seller'
                            AND (show_on_main = 1 OR CAST(show_on_main AS UNSIGNED) = 1)
                            AND (start_at IS NULL OR start_at <= '" . $pdo->quote($currentDate) . "')
                            AND (end_at IS NULL OR end_at >= '" . $pdo->quote($currentDate) . "')
                            ORDER BY created_at DESC
                            LIMIT 1";
                    $stmt = $pdo->query($sql);
                    $directResult2 = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($directResult2) {
                        echo '<p class="status-ok">✅ 문자열 치환 쿼리로 메인공지 조회 성공</p>';
                        echo '<table>';
                        echo '<tr><th>필드</th><th>값</th></tr>';
                        foreach ($directResult2 as $key => $value) {
                            echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value ?? 'NULL') . '</td></tr>';
                        }
                        echo '</table>';
                    } else {
                        echo '<p class="status-fail">❌ 문자열 치환 쿼리로도 메인공지 조회 실패</p>';
                    }
                } catch (PDOException $e) {
                    echo '<p class="status-fail">쿼리 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
            }
            ?>
        </div>
        
        <div class="section">
            <h2>해결 방법</h2>
            <ol>
                <li>위의 "모든 판매자 공지사항" 테이블에서 메인공지 조건이 ✅인 공지사항이 있는지 확인</li>
                <li>없다면:
                    <ul>
                        <li>target_audience가 'seller'인지 확인</li>
                        <li>show_on_main이 1인지 확인</li>
                        <li>표시 기간이 현재 날짜 범위 내인지 확인</li>
                    </ul>
                </li>
                <li>관리자 페이지에서 공지사항을 수정하여 위 조건들을 만족시키세요</li>
            </ol>
        </div>
    </div>
</body>
</html>

