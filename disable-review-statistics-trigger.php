<?php
/**
 * 리뷰 통계 트리거 비활성화 스크립트
 * 트리거와 PHP 함수의 중복 업데이트 문제 해결
 * PHP 함수만 사용하여 통계를 관리하도록 변경
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

echo "<h1>리뷰 통계 트리거 비활성화</h1>";

try {
    // 1. 현재 트리거 확인
    echo "<h2>1. 현재 트리거 확인</h2>";
    $stmt = $pdo->query("
        SELECT 
            TRIGGER_NAME,
            EVENT_MANIPULATION
        FROM information_schema.TRIGGERS
        WHERE EVENT_OBJECT_TABLE = 'product_reviews'
        AND TRIGGER_SCHEMA = DATABASE()
    ");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($triggers as $trigger) {
        echo "<li>" . htmlspecialchars($trigger['TRIGGER_NAME']) . " - " . htmlspecialchars($trigger['EVENT_MANIPULATION']) . "</li>";
    }
    echo "</ul>";
    
    // 2. 통계 업데이트 트리거 제거
    echo "<h2>2. 통계 업데이트 트리거 제거</h2>";
    $pdo->exec("DROP TRIGGER IF EXISTS `trg_update_review_statistics_on_insert`");
    echo "<p style='color: green;'>✓ 트리거 제거 완료</p>";
    echo "<p><strong>이유:</strong> PHP 함수(`addProductReview`)에서 이미 통계를 관리하고 있으며, 트리거와의 중복 업데이트로 인한 문제를 방지하기 위함입니다.</p>";
    echo "<p><strong>장점:</strong></p>";
    echo "<ul>";
    echo "<li>삭제된 리뷰 체크 로직이 정확하게 작동합니다.</li>";
    echo "<li>주문건별(application_id) 리뷰 통계가 정확하게 반영됩니다.</li>";
    echo "<li>트리거와 PHP 함수의 중복 업데이트 문제가 해결됩니다.</li>";
    echo "</ul>";
    
    // 3. 최종 트리거 확인
    echo "<h2>3. 최종 트리거 확인</h2>";
    $stmt = $pdo->query("
        SELECT 
            TRIGGER_NAME,
            EVENT_MANIPULATION
        FROM information_schema.TRIGGERS
        WHERE EVENT_OBJECT_TABLE = 'product_reviews'
        AND TRIGGER_SCHEMA = DATABASE()
    ");
    $finalTriggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($finalTriggers)) {
        echo "<p style='color: green;'>✓ 통계 업데이트 트리거가 제거되었습니다.</p>";
    } else {
        echo "<p>남아있는 트리거:</p>";
        echo "<ul>";
        foreach ($finalTriggers as $trigger) {
            echo "<li>" . htmlspecialchars($trigger['TRIGGER_NAME']) . " - " . htmlspecialchars($trigger['EVENT_MANIPULATION']) . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<p style='color: green;'><strong>✓ 트리거 비활성화 완료!</strong></p>";
    echo "<p><strong>다음 단계:</strong></p>";
    echo "<ol>";
    echo "<li>기존 통계를 수정하려면 <a href='fix-review-statistics.php'>fix-review-statistics.php</a>를 실행하세요.</li>";
    echo "<li>새로운 리뷰를 작성하면 PHP 함수만 사용하여 통계가 업데이트됩니다.</li>";
    echo "<li>다른 주문건의 리뷰도 정상적으로 통계에 반영됩니다.</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}




