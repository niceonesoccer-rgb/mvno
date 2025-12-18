<?php
/**
 * 포인트 설정 파일
 * 관리자가 설정할 수 있는 포인트 관련 설정
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

// 포인트 설정 (나중에 관리자 페이지에서 수정 가능)
$point_settings = [
    // 최대 사용 가능 포인트 (원 단위)
    'max_usable_point' => 50000,
    
    // 알뜰폰 신청 시 기본 차감 포인트
    'mvno_application_point' => 1000,
    
    // 통신사폰 신청 시 기본 차감 포인트
    'mno_application_point' => 1000,
    
    // 인터넷 신청 시 기본 차감 포인트
    'internet_application_point' => 1000,
    
    // 포인트 사용 안내 메시지
    'usage_message' => '신청 시 포인트가 차감됩니다.',
];

require_once __DIR__ . '/db-config.php';

// 사용자 포인트 조회(DB)
function getUserPoint($user_id = 'default') {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['balance' => 0, 'history' => []];
    }

    // 계정이 없으면 0으로 생성
    $pdo->prepare("INSERT IGNORE INTO user_point_accounts (user_id, balance) VALUES (:user, 0)")
        ->execute([':user' => (string)$user_id]);

    $stmt = $pdo->prepare("SELECT balance FROM user_point_accounts WHERE user_id = :user LIMIT 1");
    $stmt->execute([':user' => (string)$user_id]);
    $balance = (int)($stmt->fetchColumn() ?? 0);

    $histStmt = $pdo->prepare("SELECT id, created_at as date, type, ABS(delta) as amount, item_id, description, balance_after FROM user_point_ledger WHERE user_id = :user ORDER BY created_at DESC LIMIT 100");
    $histStmt->execute([':user' => (string)$user_id]);
    $history = $histStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return ['balance' => $balance, 'history' => $history];
}

// 포인트 차감
function deductPoint($user_id, $amount, $type, $item_id, $description = '') {
    $pdo = getDBConnection();
    if (!$pdo) return ['success' => false, 'message' => 'DB 연결 실패'];

    try {
        $pdo->beginTransaction();

        $pdo->prepare("INSERT IGNORE INTO user_point_accounts (user_id, balance) VALUES (:user, 0)")
            ->execute([':user' => (string)$user_id]);

        // 행 잠금
        $stmt = $pdo->prepare("SELECT balance FROM user_point_accounts WHERE user_id = :user FOR UPDATE");
        $stmt->execute([':user' => (string)$user_id]);
        $current_balance = (int)($stmt->fetchColumn() ?? 0);

        if ($current_balance < $amount) {
            $pdo->rollBack();
            return ['success' => false, 'message' => '포인트가 부족합니다.'];
        }

        $new_balance = $current_balance - $amount;
        $pdo->prepare("UPDATE user_point_accounts SET balance = :bal WHERE user_id = :user")
            ->execute([':bal' => $new_balance, ':user' => (string)$user_id]);

        $history_item = [
            'date' => date('Y-m-d H:i:s'),
            'type' => $type,
            'amount' => $amount,
            'item_id' => $item_id,
            'description' => $description ?: "{$type} 신청",
            'balance_after' => $new_balance
        ];

        $pdo->prepare("
            INSERT INTO user_point_ledger (user_id, delta, type, item_id, description, balance_after, created_at)
            VALUES (:user, :delta, :type, :item, :desc, :bal_after, NOW())
        ")->execute([
            ':user' => (string)$user_id,
            ':delta' => -abs((int)$amount),
            ':type' => (string)$type,
            ':item' => (string)$item_id,
            ':desc' => (string)$history_item['description'],
            ':bal_after' => $new_balance
        ]);

        $pdo->commit();

        return ['success' => true, 'balance' => $new_balance, 'history_item' => $history_item];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('deductPoint DB error: ' . $e->getMessage());
        return ['success' => false, 'message' => '포인트 차감 처리 중 오류'];
    }
}

// 포인트 추가 (관리자용)
function addPoint($user_id, $amount, $description = '') {
    $pdo = getDBConnection();
    if (!$pdo) return ['success' => false, 'message' => 'DB 연결 실패'];

    try {
        $pdo->beginTransaction();

        $pdo->prepare("INSERT IGNORE INTO user_point_accounts (user_id, balance) VALUES (:user, 0)")
            ->execute([':user' => (string)$user_id]);

        $stmt = $pdo->prepare("SELECT balance FROM user_point_accounts WHERE user_id = :user FOR UPDATE");
        $stmt->execute([':user' => (string)$user_id]);
        $current_balance = (int)($stmt->fetchColumn() ?? 0);

        $new_balance = $current_balance + (int)$amount;
        $pdo->prepare("UPDATE user_point_accounts SET balance = :bal WHERE user_id = :user")
            ->execute([':bal' => $new_balance, ':user' => (string)$user_id]);

        $history_item = [
            'date' => date('Y-m-d H:i:s'),
            'type' => 'add',
            'amount' => (int)$amount,
            'description' => $description ?: '포인트 충전',
            'balance_after' => $new_balance
        ];

        $pdo->prepare("
            INSERT INTO user_point_ledger (user_id, delta, type, item_id, description, balance_after, created_at)
            VALUES (:user, :delta, 'add', NULL, :desc, :bal_after, NOW())
        ")->execute([
            ':user' => (string)$user_id,
            ':delta' => abs((int)$amount),
            ':desc' => (string)$history_item['description'],
            ':bal_after' => $new_balance
        ]);

        $pdo->commit();

        return ['success' => true, 'balance' => $new_balance, 'history_item' => $history_item];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('addPoint DB error: ' . $e->getMessage());
        return ['success' => false, 'message' => '포인트 적립 처리 중 오류'];
    }
}

// 특정 아이템의 포인트 사용 내역 가져오기
function getPointHistoryByItem($user_id, $type, $item_id) {
    $user_point = getUserPoint($user_id);
    $history = $user_point['history'] ?? [];
    
    // 해당 타입과 아이템 ID로 필터링
    foreach ($history as $item) {
        if (isset($item['type']) && $item['type'] === $type && 
            isset($item['item_id']) && $item['item_id'] == $item_id) {
            return $item;
        }
    }
    
    return null;
}

// 특정 타입의 포인트 사용 내역 가져오기
function getPointHistoryByType($user_id, $type) {
    $user_point = getUserPoint($user_id);
    $history = $user_point['history'] ?? [];
    
    // 해당 타입으로 필터링
    $filtered = [];
    foreach ($history as $item) {
        if (isset($item['type']) && $item['type'] === $type) {
            $filtered[] = $item;
        }
    }
    
    // 날짜순 정렬 (최신순)
    usort($filtered, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return $filtered;
}

