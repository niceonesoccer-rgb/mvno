<?php
/**
 * 데이터베이스 연결 테스트 스크립트
 * 프로덕션 서버에서 DB 연결 상태를 확인합니다.
 */

// 경로 설정 로드
require_once __DIR__ . '/includes/data/path-config.php';
require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB 연결 테스트</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1f2937;
            margin-top: 0;
        }
        .section {
            margin-bottom: 24px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 6px;
            border-left: 4px solid #6366f1;
        }
        .success {
            color: #10b981;
            font-weight: 600;
        }
        .error {
            color: #ef4444;
            font-weight: 600;
        }
        .info {
            color: #3b82f6;
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f3f4f6;
            font-weight: 600;
            color: #374151;
        }
        .code {
            background: #1f2937;
            color: #f9fafb;
            padding: 12px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
            overflow-x: auto;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 데이터베이스 연결 테스트</h1>
        
        <?php
        // 1. 설정 파일 확인
        echo '<div class="section">';
        echo '<h2>📁 설정 파일 확인</h2>';
        
        $dbConfigLocalFile = __DIR__ . '/includes/data/db-config-local.php';
        $dbConfigFile = __DIR__ . '/includes/data/db-config.php';
        
        $localExists = file_exists($dbConfigLocalFile);
        $defaultExists = file_exists($dbConfigFile);
        
        echo '<p>db-config-local.php: <span class="' . ($localExists ? 'success' : 'error') . '">' . ($localExists ? '✅ 존재' : '❌ 없음') . '</span></p>';
        echo '<p>db-config.php: <span class="' . ($defaultExists ? 'success' : 'error') . '">' . ($defaultExists ? '✅ 존재' : '❌ 없음') . '</span></p>';
        
        // 설정 값 읽기
        $config = [
            'host' => 'N/A',
            'name' => 'N/A',
            'user' => 'N/A',
            'pass' => 'N/A (보안상 표시 안 함)',
            'charset' => 'N/A'
        ];
        
        if ($localExists) {
            $content = file_get_contents($dbConfigLocalFile);
            if (preg_match("/define\('DB_HOST',\s*'([^']+)'\)/", $content, $matches)) {
                $config['host'] = $matches[1];
            }
            if (preg_match("/define\('DB_NAME',\s*'([^']+)'\)/", $content, $matches)) {
                $config['name'] = $matches[1];
            }
            if (preg_match("/define\('DB_USER',\s*'([^']+)'\)/", $content, $matches)) {
                $config['user'] = $matches[1];
            }
            if (preg_match("/define\('DB_CHARSET',\s*'([^']+)'\)/", $content, $matches)) {
                $config['charset'] = $matches[1];
            }
        } elseif ($defaultExists) {
            $content = file_get_contents($dbConfigFile);
            if (preg_match("/define\('DB_HOST',\s*'([^']+)'\)/", $content, $matches)) {
                $config['host'] = $matches[1];
            }
            if (preg_match("/define\('DB_NAME',\s*'([^']+)'\)/", $content, $matches)) {
                $config['name'] = $matches[1];
            }
            if (preg_match("/define\('DB_USER',\s*'([^']+)'\)/", $content, $matches)) {
                $config['user'] = $matches[1];
            }
            if (preg_match("/define\('DB_CHARSET',\s*'([^']+)'\)/", $content, $matches)) {
                $config['charset'] = $matches[1];
            }
        }
        
        echo '<div class="code">';
        echo "DB_HOST: " . htmlspecialchars($config['host']) . "\n";
        echo "DB_NAME: " . htmlspecialchars($config['name']) . "\n";
        echo "DB_USER: " . htmlspecialchars($config['user']) . "\n";
        echo "DB_PASS: " . htmlspecialchars($config['pass']) . "\n";
        echo "DB_CHARSET: " . htmlspecialchars($config['charset']) . "\n";
        echo '</div>';
        echo '</div>';
        
        // 2. 데이터베이스 연결 테스트
        echo '<div class="section">';
        echo '<h2>🔗 데이터베이스 연결 테스트</h2>';
        
        try {
            $pdo = getDBConnection();
            if ($pdo) {
                echo '<p class="success">✅ 데이터베이스 연결 성공!</p>';
                
                // 3. 테이블 확인
                echo '<div class="section">';
                echo '<h2>📊 테이블 확인</h2>';
                
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                echo '<p class="info">총 ' . count($tables) . '개의 테이블이 있습니다.</p>';
                
                if (count($tables) > 0) {
                    echo '<table>';
                    echo '<tr><th>테이블명</th><th>레코드 수</th></tr>';
                    foreach ($tables as $table) {
                        try {
                            $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                            echo '<tr><td>' . htmlspecialchars($table) . '</td><td>' . number_format($count) . '</td></tr>';
                        } catch (PDOException $e) {
                            echo '<tr><td>' . htmlspecialchars($table) . '</td><td class="error">에러</td></tr>';
                        }
                    }
                    echo '</table>';
                } else {
                    echo '<p class="error">❌ 테이블이 없습니다. 데이터베이스를 초기화해야 합니다.</p>';
                }
                echo '</div>';
                
                // 4. 주요 테이블 데이터 확인
                echo '<div class="section">';
                echo '<h2>📦 주요 데이터 확인</h2>';
                
                $importantTables = [
                    'products' => '상품',
                    'app_settings' => '앱 설정',
                    'events' => '이벤트',
                    'users' => '사용자',
                    'product_applications' => '상품 신청'
                ];
                
                echo '<table>';
                echo '<tr><th>테이블</th><th>설명</th><th>레코드 수</th><th>상태</th></tr>';
                
                foreach ($importantTables as $table => $desc) {
                    $exists = in_array($table, $tables);
                    if ($exists) {
                        try {
                            $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                            $status = $count > 0 ? '<span class="success">✅ 데이터 있음</span>' : '<span class="error">❌ 데이터 없음</span>';
                            echo '<tr><td>' . htmlspecialchars($table) . '</td><td>' . htmlspecialchars($desc) . '</td><td>' . number_format($count) . '</td><td>' . $status . '</td></tr>';
                        } catch (PDOException $e) {
                            echo '<tr><td>' . htmlspecialchars($table) . '</td><td>' . htmlspecialchars($desc) . '</td><td>-</td><td class="error">에러</td></tr>';
                        }
                    } else {
                        echo '<tr><td>' . htmlspecialchars($table) . '</td><td>' . htmlspecialchars($desc) . '</td><td>-</td><td class="error">❌ 테이블 없음</td></tr>';
                    }
                }
                echo '</table>';
                echo '</div>';
                
                // 5. 상품 데이터 상세 확인
                if (in_array('products', $tables)) {
                    echo '<div class="section">';
                    echo '<h2>🛍️ 상품 데이터 상세</h2>';
                    
                    try {
                        $stmt = $pdo->query("
                            SELECT 
                                product_type,
                                status,
                                COUNT(*) as count
                            FROM products
                            GROUP BY product_type, status
                            ORDER BY product_type, status
                        ");
                        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($results) > 0) {
                            echo '<table>';
                            echo '<tr><th>상품 타입</th><th>상태</th><th>개수</th></tr>';
                            foreach ($results as $row) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['product_type'] ?? 'N/A') . '</td>';
                                echo '<td>' . htmlspecialchars($row['status'] ?? 'N/A') . '</td>';
                                echo '<td>' . number_format($row['count']) . '</td>';
                                echo '</tr>';
                            }
                            echo '</table>';
                        } else {
                            echo '<p class="error">❌ 상품 데이터가 없습니다.</p>';
                        }
                    } catch (PDOException $e) {
                        echo '<p class="error">❌ 쿼리 에러: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                    echo '</div>';
                }
                
                // 6. app_settings 확인
                if (in_array('app_settings', $tables)) {
                    echo '<div class="section">';
                    echo '<h2>⚙️ 앱 설정 확인</h2>';
                    
                    try {
                        $stmt = $pdo->query("SELECT namespace FROM app_settings");
                        $namespaces = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (count($namespaces) > 0) {
                            echo '<p class="info">설정된 네임스페이스: ' . implode(', ', array_map('htmlspecialchars', $namespaces)) . '</p>';
                            
                            // home 설정 확인
                            if (in_array('home', $namespaces)) {
                                $stmt = $pdo->query("SELECT json_value FROM app_settings WHERE namespace = 'home' LIMIT 1");
                                $homeSettings = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($homeSettings) {
                                    $data = json_decode($homeSettings['json_value'], true);
                                    echo '<p class="success">✅ home 설정이 있습니다.</p>';
                                    echo '<div class="code">';
                                    echo "통신사단독유심: " . count($data['mno_sim_plans'] ?? []) . "개\n";
                                    echo "알뜰폰: " . count($data['mvno_plans'] ?? []) . "개\n";
                                    echo "통신사폰: " . count($data['mno_phones'] ?? []) . "개\n";
                                    echo "인터넷: " . count($data['internet_products'] ?? []) . "개\n";
                                    echo "큰 배너: " . count($data['site_large_banners'] ?? []) . "개\n";
                                    echo "작은 배너: " . count($data['site_small_banners'] ?? []) . "개\n";
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="error">❌ home 설정이 없습니다.</p>';
                            }
                        } else {
                            echo '<p class="error">❌ app_settings에 데이터가 없습니다.</p>';
                        }
                    } catch (PDOException $e) {
                        echo '<p class="error">❌ 쿼리 에러: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                    echo '</div>';
                }
                
            } else {
                echo '<p class="error">❌ 데이터베이스 연결 실패!</p>';
                if (isset($GLOBALS['lastDbConnectionError'])) {
                    echo '<p class="error">에러 메시지: ' . htmlspecialchars($GLOBALS['lastDbConnectionError']) . '</p>';
                }
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ 예외 발생: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        echo '</div>';
        ?>
        
        <div class="section">
            <h2>💡 다음 단계</h2>
            <ul>
                <li>테이블이 없으면: 로컬 DB를 프로덕션 서버에 업로드해야 합니다.</li>
                <li>데이터가 없으면: 로컬 DB의 데이터를 프로덕션 서버에 업로드해야 합니다.</li>
                <li>연결이 안 되면: <code>includes/data/db-config-local.php</code> 파일의 설정을 확인하세요.</li>
            </ul>
        </div>
    </div>
</body>
</html>
