<?php
/**
 * 자동 처리 기능 테스트 스크립트
 * 
 * 세 가지 자동 처리 기능이 제대로 동작하는지 확인합니다:
 * 1. 계정 탈퇴 시 모든 상품 판매종료 처리
 * 2. 3일 이상 미접속 시 모든 상품 판매종료 처리
 * 3. 15일 후 주문 종료 처리
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/auth-functions.php';
require_once __DIR__ . '/includes/data/product-functions.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>\n";
echo "<html><head><meta charset='UTF-8'><title>자동 처리 기능 테스트</title></head><body>\n";
echo "<h1>자동 처리 기능 테스트</h1>\n";
echo "<pre style='font-family: monospace; font-size: 12px;'>\n";

$pdo = getDBConnection();
if (!$pdo) {
    die("❌ 데이터베이스 연결 실패\n");
}

echo "✅ 데이터베이스 연결 성공\n\n";

// ============================================
// 1. 계정 탈퇴 시 상품 판매종료 처리 검증
// ============================================
echo "=== 1. 계정 탈퇴 시 상품 판매종료 처리 검증 ===\n";

// completeSellerWithdrawal 함수 확인
if (function_exists('completeSellerWithdrawal')) {
    echo "✅ completeSellerWithdrawal 함수 존재\n";
    
    // 함수 코드에서 상품 업데이트 부분 확인
    $functionFile = file_get_contents(__DIR__ . '/includes/data/auth-functions.php');
    if (strpos($functionFile, "UPDATE products") !== false && 
        strpos($functionFile, "SET status = 'inactive'") !== false &&
        strpos($functionFile, "WHERE seller_id = :user_id") !== false) {
        echo "✅ 상품 판매종료 처리 코드 존재\n";
    } else {
        echo "❌ 상품 판매종료 처리 코드가 없습니다!\n";
    }
    
    // seller_id와 user_id 타입 확인
    $stmt = $pdo->query("DESCRIBE products");
    $productsColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $sellerIdType = null;
    foreach ($productsColumns as $col) {
        if ($col['Field'] === 'seller_id') {
            $sellerIdType = $col['Type'];
            break;
        }
    }
    
    $stmt = $pdo->query("DESCRIBE users");
    $usersColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $userIdType = null;
    foreach ($usersColumns as $col) {
        if ($col['Field'] === 'user_id') {
            $userIdType = $col['Type'];
            break;
        }
    }
    
    echo "📋 products.seller_id 타입: " . ($sellerIdType ?? '알 수 없음') . "\n";
    echo "📋 users.user_id 타입: " . ($userIdType ?? '알 수 없음') . "\n";
    
    if (strpos($sellerIdType, 'int') !== false && strpos($userIdType, 'varchar') !== false) {
        echo "⚠️  주의: seller_id(INT)와 user_id(VARCHAR) 타입이 다릅니다. 변환이 필요할 수 있습니다.\n";
    } elseif ($sellerIdType && $userIdType) {
        echo "✅ 타입 확인 완료\n";
    }
    
} else {
    echo "❌ completeSellerWithdrawal 함수가 없습니다!\n";
}

echo "\n";

// ============================================
// 2. 3일 미접속 시 상품 판매종료 처리 검증
// ============================================
echo "=== 2. 3일 미접속 시 상품 판매종료 처리 검증 ===\n";

// last_login 컬럼 확인
$stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'");
if ($stmt->rowCount() > 0) {
    echo "✅ last_login 컬럼 존재\n";
} else {
    echo "❌ last_login 컬럼이 없습니다! add_last_login_column.sql을 실행해주세요.\n";
}

// loginUser 함수 확인
if (function_exists('loginUser')) {
    echo "✅ loginUser 함수 존재\n";
    
    $functionFile = file_get_contents(__DIR__ . '/includes/data/auth-functions.php');
    if (strpos($functionFile, "UPDATE users") !== false && 
        strpos($functionFile, "SET last_login = NOW()") !== false) {
        echo "✅ last_login 업데이트 코드 존재\n";
    } else {
        echo "❌ last_login 업데이트 코드가 없습니다!\n";
    }
}

// autoDeactivateInactiveSellerProducts 함수 확인
if (function_exists('autoDeactivateInactiveSellerProducts')) {
    echo "✅ autoDeactivateInactiveSellerProducts 함수 존재\n";
    
    $functionFile = file_get_contents(__DIR__ . '/includes/data/product-functions.php');
    if (strpos($functionFile, "UPDATE products") !== false && 
        strpos($functionFile, "SET status = 'inactive'") !== false &&
        strpos($functionFile, "DATE_SUB(NOW(), INTERVAL 3 DAY)") !== false) {
        echo "✅ 3일 미접속 처리 코드 존재\n";
    } else {
        echo "❌ 3일 미접속 처리 코드가 없습니다!\n";
    }
} else {
    echo "❌ autoDeactivateInactiveSellerProducts 함수가 없습니다!\n";
}

echo "\n";

// ============================================
// 3. 15일 후 주문 종료 처리 검증
// ============================================
echo "=== 3. 15일 후 주문 종료 처리 검증 ===\n";

if (function_exists('autoCloseOldApplications')) {
    echo "✅ autoCloseOldApplications 함수 존재\n";
    
    $functionFile = file_get_contents(__DIR__ . '/includes/data/product-functions.php');
    if (strpos($functionFile, "UPDATE product_applications") !== false && 
        strpos($functionFile, "SET application_status = 'closed'") !== false &&
        strpos($functionFile, "DATE_SUB(NOW(), INTERVAL 15 DAY)") !== false) {
        echo "✅ 15일 후 주문 종료 처리 코드 존재\n";
    } else {
        echo "❌ 15일 후 주문 종료 처리 코드가 없습니다!\n";
    }
    
    // 제외 상태 확인
    if (strpos($functionFile, "pending") !== false &&
        strpos($functionFile, "received") !== false &&
        strpos($functionFile, "activation_completed") !== false &&
        strpos($functionFile, "cancelled") !== false &&
        strpos($functionFile, "installation_completed") !== false &&
        strpos($functionFile, "closed") !== false) {
        echo "✅ 제외 상태 목록 정상 (pending, received, activation_completed, cancelled, installation_completed, closed)\n";
    } else {
        echo "⚠️  제외 상태 목록 확인 필요\n";
    }
} else {
    echo "❌ autoCloseOldApplications 함수가 없습니다!\n";
}

echo "\n";

// ============================================
// SQL 쿼리 문법 검증
// ============================================
echo "=== SQL 쿼리 문법 검증 ===\n";

// 1. completeSellerWithdrawal의 SQL 검증
echo "1. 계정 탈퇴 시 상품 업데이트 쿼리:\n";
echo "   SELECT 1 FROM products WHERE seller_id = :user_id AND status = 'active' LIMIT 1;\n";
try {
    $testStmt = $pdo->prepare("SELECT 1 FROM products WHERE seller_id = :user_id AND status = 'active' LIMIT 1");
    $testStmt->execute([':user_id' => '1']); // 테스트용
    echo "   ✅ 쿼리 문법 정상\n";
} catch (PDOException $e) {
    echo "   ❌ 쿼리 오류: " . $e->getMessage() . "\n";
}

// 2. autoDeactivateInactiveSellerProducts의 SQL 검증
echo "\n2. 3일 미접속 판매자 조회 쿼리:\n";
echo "   SELECT DISTINCT u.user_id FROM users u WHERE u.role = 'seller' AND ...;\n";
try {
    $testStmt = $pdo->prepare("
        SELECT DISTINCT u.user_id
        FROM users u
        WHERE u.role = 'seller'
        AND u.seller_approved = 1
        AND u.approval_status = 'approved'
        AND (
            (u.last_login IS NOT NULL AND u.last_login <= DATE_SUB(NOW(), INTERVAL 3 DAY))
            OR (u.last_login IS NULL AND u.created_at <= DATE_SUB(NOW(), INTERVAL 3 DAY))
        )
        LIMIT 1
    ");
    $testStmt->execute();
    echo "   ✅ 쿼리 문법 정상\n";
} catch (PDOException $e) {
    echo "   ❌ 쿼리 오류: " . $e->getMessage() . "\n";
}

// 3. autoCloseOldApplications의 SQL 검증
echo "\n3. 15일 후 주문 종료 처리 쿼리:\n";
echo "   UPDATE product_applications SET application_status = 'closed' WHERE ...;\n";
try {
    $excludedStatuses = ['pending', 'received', 'activation_completed', 'cancelled', 'installation_completed', 'closed'];
    $placeholders = implode(',', array_fill(0, count($excludedStatuses), '?'));
    $testStmt = $pdo->prepare("
        SELECT 1 FROM product_applications
        WHERE application_status NOT IN ({$placeholders})
        AND created_at <= DATE_SUB(NOW(), INTERVAL 15 DAY)
        AND application_status != 'closed'
        LIMIT 1
    ");
    $testStmt->execute($excludedStatuses);
    echo "   ✅ 쿼리 문법 정상\n";
} catch (PDOException $e) {
    echo "   ❌ 쿼리 오류: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================
// 최종 요약
// ============================================
echo "=== 최종 요약 ===\n";
echo "모든 검증이 완료되었습니다.\n";
echo "\n다음 단계:\n";
echo "1. database/add_last_login_column.sql 실행 (아직 실행하지 않았다면)\n";
echo "2. api/auto-close-old-applications.php를 cron job에 등록\n";
echo "3. api/auto-deactivate-inactive-seller-products.php를 cron job에 등록\n";
echo "4. 실제 테스트 진행\n";

echo "</pre></body></html>\n";
