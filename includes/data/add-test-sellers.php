<?php
/**
 * 테스트 판매자 계정 일괄 생성 스크립트
 * 아이디와 비밀번호가 동일한 숫자 형태로 120개 생성
 * 
 * 실행 방법:
 * 1. 브라우저에서: http://localhost/MVNO/includes/data/add-test-sellers.php
 * 2. 터미널에서: php includes/data/add-test-sellers.php
 */

// 브라우저에서 실행 시 HTML 출력
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<pre>';
}

require_once __DIR__ . '/auth-functions.php';

// 시작 번호 (77777777부터 시작)
$startNumber = 77777777;
$count = 120;

echo "판매자 계정 생성 시작...\n\n";

for ($i = 0; $i < $count; $i++) {
    $userId = str_pad($startNumber + $i, 8, '0', STR_PAD_LEFT);
    $password = $userId; // 비밀번호도 아이디와 동일
    $email = $userId . '@test.com';
    $name = $userId;
    
    // 추가 정보
    $additionalData = [
        'phone' => '02-' . substr($userId, 0, 4) . '-' . substr($userId, 4, 4),
        'mobile' => '010-' . substr($userId, 0, 4) . '-' . substr($userId, 4, 4),
        'postal_code' => '',
        'address' => '서울 강남구 가로수길 ' . ($i + 1),
        'address_detail' => '',
        'business_number' => substr($userId, 0, 3) . '-' . substr($userId, 3, 2) . '-' . substr($userId, 5, 5),
        'company_name' => $userId,
        'company_representative' => $userId,
        'business_type' => $userId,
        'business_item' => $userId,
    ];
    
    // 이미 존재하는지 확인
    $existingUser = getUserById($userId);
    if ($existingUser) {
        echo "SKIP: {$userId} (이미 존재)\n";
        continue;
    }
    
    // 계정 생성
    $result = registerDirectUser($userId, $password, $email, $name, 'seller', $additionalData);
    
    if ($result['success']) {
        echo "SUCCESS: {$userId} / {$password} / {$email}\n";
    } else {
        echo "ERROR: {$userId} - {$result['message']}\n";
    }
}

echo "\n완료! 총 {$count}개 계정 생성 시도\n";

// 브라우저에서 실행 시 HTML 닫기
if (php_sapi_name() !== 'cli') {
    echo '</pre>';
}







