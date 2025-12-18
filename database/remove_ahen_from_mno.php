<?php
/**
 * MNO 상품 데이터에서 ahen 항목 제거 스크립트
 * 
 * 이 스크립트는 product_mno_details 테이블의 모든 레코드에서
 * 할인 정보 JSON 배열의 4번째 요소(ahen, 인덱스 3)를 제거합니다.
 * 
 * 실행 방법:
 * 1. 브라우저에서 http://localhost/MVNO/database/remove_ahen_from_mno.php 접속
 * 2. 또는 명령줄에서: php remove_ahen_from_mno.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ahen 항목 제거 스크립트</title>
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #bee5eb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #ffeaa7;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        button:hover {
            background: #45a049;
        }
        button.danger {
            background: #f44336;
        }
        button.danger:hover {
            background: #da190b;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>MNO 상품 데이터에서 ahen 항목 제거</h1>
        
        <?php
        $pdo = getDBConnection();
        
        if (!$pdo) {
            echo '<div class="error">❌ 데이터베이스 연결에 실패했습니다.</div>';
            exit;
        }
        
        // 실행 확인
        if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
            // 먼저 영향받을 레코드 수 확인
            $checkStmt = $pdo->query("
                SELECT COUNT(*) as total,
                       SUM(CASE WHEN common_provider IS NOT NULL AND JSON_LENGTH(common_provider) > 3 THEN 1 ELSE 0 END) as has_common_ahen,
                       SUM(CASE WHEN contract_provider IS NOT NULL AND JSON_LENGTH(contract_provider) > 3 THEN 1 ELSE 0 END) as has_contract_ahen
                FROM product_mno_details
            ");
            $stats = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            echo '<div class="info">';
            echo '<h2>현재 상태</h2>';
            echo '<ul>';
            echo '<li>전체 레코드 수: ' . $stats['total'] . '개</li>';
            echo '<li>공통지원할인에 ahen이 있는 레코드: ' . $stats['has_common_ahen'] . '개</li>';
            echo '<li>선택약정할인에 ahen이 있는 레코드: ' . $stats['has_contract_ahen'] . '개</li>';
            echo '</ul>';
            echo '</div>';
            
            echo '<div class="warning">';
            echo '<h2>⚠️ 주의사항</h2>';
            echo '<p>이 스크립트는 다음 작업을 수행합니다:</p>';
            echo '<ul>';
            echo '<li>공통지원할인 JSON 배열에서 4번째 요소(ahen) 제거</li>';
            echo '<li>선택약정할인 JSON 배열에서 4번째 요소(ahen) 제거</li>';
            echo '<li>할인 값 배열(common_discount_new, common_discount_port, common_discount_change, contract_discount_new, contract_discount_port, contract_discount_change)에서도 4번째 요소 제거</li>';
            echo '</ul>';
            echo '<p><strong>이 작업은 되돌릴 수 없습니다. 실행하기 전에 데이터베이스 백업을 권장합니다.</strong></p>';
            echo '</div>';
            
            echo '<div style="text-align: center; margin-top: 30px;">';
            echo '<a href="?confirm=yes"><button class="danger">실행하기</button></a>';
            echo '<a href="javascript:history.back()"><button>취소</button></a>';
            echo '</div>';
            exit;
        }
        
        // 실제 실행
        echo '<div class="info">작업을 시작합니다...</div>';
        
        try {
            $pdo->beginTransaction();
            
            // 모든 레코드 가져오기
            $stmt = $pdo->query("SELECT id, common_provider, common_discount_new, common_discount_port, common_discount_change, contract_provider, contract_discount_new, contract_discount_port, contract_discount_change FROM product_mno_details");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updated = 0;
            $skipped = 0;
            
            foreach ($records as $record) {
                $updatedFields = [];
                $params = [];
                
                // 공통지원할인 처리
                if (!empty($record['common_provider'])) {
                    $commonProvider = json_decode($record['common_provider'], true);
                    if (is_array($commonProvider) && count($commonProvider) > 3) {
                        // 인덱스 3(ahen) 제거
                        array_splice($commonProvider, 3, 1);
                        $updatedFields[] = "common_provider = ?";
                        $params[] = json_encode($commonProvider, JSON_UNESCAPED_UNICODE);
                    }
                }
                
                // 공통지원할인 할인 값들 처리
                $commonFields = ['common_discount_new', 'common_discount_port', 'common_discount_change'];
                foreach ($commonFields as $field) {
                    if (!empty($record[$field])) {
                        $values = json_decode($record[$field], true);
                        if (is_array($values) && count($values) > 3) {
                            array_splice($values, 3, 1);
                            $updatedFields[] = "$field = ?";
                            $params[] = json_encode($values, JSON_UNESCAPED_UNICODE);
                        }
                    }
                }
                
                // 선택약정할인 처리
                if (!empty($record['contract_provider'])) {
                    $contractProvider = json_decode($record['contract_provider'], true);
                    if (is_array($contractProvider) && count($contractProvider) > 3) {
                        // 인덱스 3(ahen) 제거
                        array_splice($contractProvider, 3, 1);
                        $updatedFields[] = "contract_provider = ?";
                        $params[] = json_encode($contractProvider, JSON_UNESCAPED_UNICODE);
                    }
                }
                
                // 선택약정할인 할인 값들 처리
                $contractFields = ['contract_discount_new', 'contract_discount_port', 'contract_discount_change'];
                foreach ($contractFields as $field) {
                    if (!empty($record[$field])) {
                        $values = json_decode($record[$field], true);
                        if (is_array($values) && count($values) > 3) {
                            array_splice($values, 3, 1);
                            $updatedFields[] = "$field = ?";
                            $params[] = json_encode($values, JSON_UNESCAPED_UNICODE);
                        }
                    }
                }
                
                // 업데이트할 필드가 있으면 실행
                if (!empty($updatedFields)) {
                    $params[] = $record['id'];
                    $updateSql = "UPDATE product_mno_details SET " . implode(", ", $updatedFields) . " WHERE id = ?";
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute($params);
                    $updated++;
                } else {
                    $skipped++;
                }
            }
            
            $pdo->commit();
            
            echo '<div class="success">';
            echo '<h2>✅ 작업 완료</h2>';
            echo '<ul>';
            echo '<li>처리된 레코드: ' . $updated . '개</li>';
            echo '<li>변경사항 없는 레코드: ' . $skipped . '개</li>';
            echo '<li>전체 레코드: ' . count($records) . '개</li>';
            echo '</ul>';
            echo '<p>모든 ahen 항목이 성공적으로 제거되었습니다.</p>';
            echo '</div>';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo '<div class="error">';
            echo '<h2>❌ 오류 발생</h2>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="remove_ahen_from_mno.php"><button>다시 확인</button></a>
        </div>
    </div>
</body>
</html>



















