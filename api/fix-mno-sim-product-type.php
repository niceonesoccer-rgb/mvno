<?php
/**
 * 통신사유심 신청의 product_type 수정
 * 빈 문자열로 저장된 경우 'mno-sim'으로 업데이트
 */

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json; charset=utf-8');

// 관리자 또는 판매자만 접근 가능
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser) {
    echo json_encode(['success' => false, 'message' => '로그인 정보를 확인할 수 없습니다.']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결 실패');
    }
    
    // 1. product_applications 테이블의 product_type ENUM에 'mno-sim' 추가
    try {
        $checkEnum = $pdo->query("SHOW COLUMNS FROM product_applications WHERE Field = 'product_type'");
        $enumInfo = $checkEnum->fetch(PDO::FETCH_ASSOC);
        if ($enumInfo && isset($enumInfo['Type'])) {
            $enumType = $enumInfo['Type'];
            if (strpos($enumType, 'mno-sim') === false) {
                // ENUM에 'mno-sim' 추가
                $pdo->exec("ALTER TABLE product_applications MODIFY COLUMN product_type ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입'");
                echo json_encode(['success' => true, 'message' => 'product_type ENUM에 mno-sim 추가 완료']);
            } else {
                echo json_encode(['success' => true, 'message' => 'product_type ENUM에 이미 mno-sim이 포함되어 있습니다.']);
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'ENUM 수정 실패: ' . $e->getMessage()]);
        exit;
    }
    
    // 2. product_id로 mno-sim 상품인지 확인하고 product_type 업데이트
    // product_mno_sim_details 테이블에 있는 상품의 신청은 mno-sim으로 업데이트
    $stmt = $pdo->prepare("
        SELECT DISTINCT a.id, a.product_id, a.product_type, p.product_type as product_table_type
        FROM product_applications a
        LEFT JOIN products p ON a.product_id = p.id
        LEFT JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
        WHERE (a.product_type = '' OR a.product_type IS NULL)
        AND mno_sim.id IS NOT NULL
        ORDER BY a.created_at DESC
        LIMIT 100
    ");
    $stmt->execute();
    $emptyTypeApps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $fixed = 0;
    $errors = [];
    
    foreach ($emptyTypeApps as $app) {
        $appId = $app['id'];
        $productId = $app['product_id'];
        $productTableType = $app['product_table_type'];
        
        // product_mno_sim_details에 있으면 mno-sim으로 업데이트
        if ($productTableType === 'mno-sim' || $productId) {
            // product_id로 product_mno_sim_details 확인
            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) as cnt 
                FROM product_mno_sim_details 
                WHERE product_id = :product_id
            ");
            $checkStmt->execute([':product_id' => $productId]);
            $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($checkResult['cnt'] > 0) {
                // mno-sim 상품이면 업데이트
                try {
                    $updateStmt = $pdo->prepare("
                        UPDATE product_applications 
                        SET product_type = 'mno-sim' 
                        WHERE id = :id
                    ");
                    $updateStmt->execute([':id' => $appId]);
                    $fixed++;
                } catch (PDOException $e) {
                    $errors[] = "ID {$appId} 업데이트 실패: " . $e->getMessage();
                }
            } else if ($productTableType && $productTableType !== 'mno-sim') {
                // products 테이블에 product_type이 있으면 그것으로 업데이트
                try {
                    $updateStmt = $pdo->prepare("
                        UPDATE product_applications 
                        SET product_type = :product_type 
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        ':id' => $appId,
                        ':product_type' => $productTableType
                    ]);
                    $fixed++;
                } catch (PDOException $e) {
                    $errors[] = "ID {$appId} 업데이트 실패: " . $e->getMessage();
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "수정 완료: {$fixed}개 신청의 product_type이 업데이트되었습니다.",
        'fixed_count' => $fixed,
        'errors' => $errors,
        'checked_applications' => $emptyTypeApps
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

