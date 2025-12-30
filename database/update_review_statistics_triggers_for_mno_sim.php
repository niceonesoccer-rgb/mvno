<?php
/**
 * 리뷰 통계 트리거에 mno-sim 타입 추가
 * 실행 방법: 브라우저에서 http://localhost/MVNO/database/update_review_statistics_triggers_for_mno_sim.php 접속
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>리뷰 통계 트리거 업데이트</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 10px;
        }
        .success {
            color: #10b981;
            background: #d1fae5;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            color: #ef4444;
            background: #fee2e2;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .info {
            color: #6366f1;
            background: #e0e7ff;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        pre {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>리뷰 통계 트리거 업데이트 (mno-sim 추가)</h1>
        
        <?php
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                throw new Exception("데이터베이스 연결 실패");
            }
            
            echo "<div class='info'>데이터베이스 연결 성공</div>";
            
            // 트리거 SQL 파일 읽기
            $triggerFile = __DIR__ . '/add_review_statistics_update_delete_triggers.sql';
            if (!file_exists($triggerFile)) {
                throw new Exception("트리거 SQL 파일을 찾을 수 없습니다: $triggerFile");
            }
            
            $sql = file_get_contents($triggerFile);
            
            // mno-sim이 이미 포함되어 있는지 확인
            if (strpos($sql, "'mno-sim'") === false) {
                echo "<div class='error'>SQL 파일에 mno-sim이 포함되어 있지 않습니다. 파일을 먼저 수정해주세요.</div>";
            } else {
                echo "<div class='info'>SQL 파일에 mno-sim이 포함되어 있습니다.</div>";
                
                // 트리거 삭제 및 재생성
                echo "<h2>트리거 업데이트</h2>";
                
                // SQL 실행 (DELIMITER 처리)
                $statements = explode('DELIMITER', $sql);
                $currentDelimiter = ';';
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (empty($statement)) continue;
                    
                    if (strpos($statement, '$$') !== false) {
                        $currentDelimiter = '$$';
                        $statement = str_replace('$$', '', $statement);
                    }
                    
                    // 각 SQL 문장 실행
                    $lines = explode("\n", $statement);
                    $sqlStatement = '';
                    
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line) || strpos($line, '--') === 0) continue;
                        
                        $sqlStatement .= $line . "\n";
                        
                        // DELIMITER로 끝나는지 확인
                        if (substr(rtrim($line), -strlen($currentDelimiter)) === $currentDelimiter) {
                            $sqlStatement = rtrim($sqlStatement, $currentDelimiter . "\n");
                            if (!empty($sqlStatement)) {
                                try {
                                    $pdo->exec($sqlStatement);
                                    echo "<div class='success'>✓ SQL 실행 완료</div>";
                                } catch (PDOException $e) {
                                    // 트리거가 이미 존재하는 경우는 무시
                                    if (strpos($e->getMessage(), 'already exists') === false) {
                                        echo "<div class='error'>✗ SQL 실행 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
                                    } else {
                                        echo "<div class='info'>ℹ 트리거가 이미 존재합니다. (무시됨)</div>";
                                    }
                                }
                            }
                            $sqlStatement = '';
                        }
                    }
                }
                
                // 트리거 확인
                echo "<h2>트리거 확인</h2>";
                try {
                    $checkStmt = $pdo->query("
                        SELECT 
                            TRIGGER_NAME,
                            EVENT_MANIPULATION,
                            EVENT_OBJECT_TABLE,
                            ACTION_TIMING
                        FROM information_schema.TRIGGERS
                        WHERE TRIGGER_SCHEMA = DATABASE()
                        AND TRIGGER_NAME LIKE 'trg_update_review_statistics%'
                        ORDER BY TRIGGER_NAME
                    ");
                    $triggers = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($triggers)) {
                        echo "<div class='success'>등록된 트리거:</div>";
                        echo "<pre>";
                        foreach ($triggers as $trigger) {
                            echo sprintf("%-40s %-10s %-20s %s\n", 
                                $trigger['TRIGGER_NAME'], 
                                $trigger['EVENT_MANIPULATION'],
                                $trigger['EVENT_OBJECT_TABLE'],
                                $trigger['ACTION_TIMING']
                            );
                        }
                        echo "</pre>";
                    } else {
                        echo "<div class='error'>트리거가 등록되지 않았습니다.</div>";
                    }
                } catch (PDOException $e) {
                    echo "<div class='error'>트리거 확인 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
            
            echo "<div class='success' style='margin-top: 30px;'><strong>✓ 업데이트가 완료되었습니다!</strong></div>";
            echo "<div class='info' style='margin-top: 20px;'>참고: 트리거는 리뷰 수정/삭제 시 자동으로 통계 테이블을 업데이트합니다.</div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
    </div>
</body>
</html>





