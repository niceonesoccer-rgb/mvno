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

// 사용자 포인트 데이터 (실제로는 데이터베이스에서 가져옴)
// 현재는 파일 기반으로 구현
function getUserPoint($user_id = 'default') {
    $point_file = __DIR__ . '/../data/user-points.json';
    
    if (!file_exists($point_file)) {
        // 기본 포인트 설정
        $default_points = [
            'default' => [
                'balance' => 100000, // 기본 잔액
                'history' => []
            ]
        ];
        file_put_contents($point_file, json_encode($default_points, JSON_PRETTY_PRINT));
        return $default_points[$user_id] ?? ['balance' => 0, 'history' => []];
    }
    
    $points_data = json_decode(file_get_contents($point_file), true);
    return $points_data[$user_id] ?? ['balance' => 0, 'history' => []];
}

// 포인트 차감
function deductPoint($user_id, $amount, $type, $item_id, $description = '') {
    $point_file = __DIR__ . '/../data/user-points.json';
    $points_data = [];
    
    if (file_exists($point_file)) {
        $points_data = json_decode(file_get_contents($point_file), true);
    }
    
    if (!isset($points_data[$user_id])) {
        $points_data[$user_id] = ['balance' => 0, 'history' => []];
    }
    
    $current_balance = $points_data[$user_id]['balance'] ?? 0;
    
    if ($current_balance < $amount) {
        return ['success' => false, 'message' => '포인트가 부족합니다.'];
    }
    
    $new_balance = $current_balance - $amount;
    $points_data[$user_id]['balance'] = $new_balance;
    
    // 내역 추가
    $history_item = [
        'id' => uniqid(),
        'date' => date('Y-m-d H:i:s'),
        'type' => $type, // 'mvno', 'mno', 'internet'
        'amount' => $amount,
        'item_id' => $item_id,
        'description' => $description ?: "{$type} 신청",
        'balance_after' => $new_balance
    ];
    
    $points_data[$user_id]['history'][] = $history_item;
    
    // 최근 100개만 유지
    if (count($points_data[$user_id]['history']) > 100) {
        $points_data[$user_id]['history'] = array_slice($points_data[$user_id]['history'], -100);
    }
    
    file_put_contents($point_file, json_encode($points_data, JSON_PRETTY_PRINT));
    
    return [
        'success' => true,
        'balance' => $new_balance,
        'history_item' => $history_item
    ];
}

// 포인트 추가 (관리자용)
function addPoint($user_id, $amount, $description = '') {
    $point_file = __DIR__ . '/../data/user-points.json';
    $points_data = [];
    
    if (file_exists($point_file)) {
        $points_data = json_decode(file_get_contents($point_file), true);
    }
    
    if (!isset($points_data[$user_id])) {
        $points_data[$user_id] = ['balance' => 0, 'history' => []];
    }
    
    $current_balance = $points_data[$user_id]['balance'] ?? 0;
    $new_balance = $current_balance + $amount;
    $points_data[$user_id]['balance'] = $new_balance;
    
    // 내역 추가
    $history_item = [
        'id' => uniqid(),
        'date' => date('Y-m-d H:i:s'),
        'type' => 'add',
        'amount' => $amount,
        'description' => $description ?: '포인트 충전',
        'balance_after' => $new_balance
    ];
    
    $points_data[$user_id]['history'][] = $history_item;
    
    file_put_contents($point_file, json_encode($points_data, JSON_PRETTY_PRINT));
    
    return [
        'success' => true,
        'balance' => $new_balance,
        'history_item' => $history_item
    ];
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

