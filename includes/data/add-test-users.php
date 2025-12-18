<?php
/**
 * 테스트 회원 데이터 추가 스크립트
 * 일반회원 15명, 판매자 회원 15명 추가
 */

require_once __DIR__ . '/auth-functions.php';

// DB-only: 테스트 계정 생성은 DB에 직접 반영

// 일반회원 15명 추가 (SNS 가입)
$snsProviders = ['naver', 'kakao', 'google'];
$snsNames = [
    '김민수', '이지은', '박준호', '최수진', '정다은',
    '강민지', '윤서연', '장현우', '임태영', '한소희',
    '오지훈', '신예린', '류성민', '문혜진', '양동현'
];

for ($i = 0; $i < 15; $i++) {
    $provider = $snsProviders[$i % 3];
    $snsId = 'sns_' . str_pad($i + 1, 6, '0', STR_PAD_LEFT);
    $email = 'user' . ($i + 1) . '@test.com';
    $name = $snsNames[$i];
    
    // 이미 존재하는지 확인
    $existingUser = getUserBySnsId($provider, $snsId);
    if ($existingUser) {
        continue;
    }

    // DB에 SNS 유저 생성
    registerSnsUser($provider, $snsId, $email, $name);
}

// 판매자 회원 15명 추가
$sellerNames = [
    '판매자1', '판매자2', '판매자3', '판매자4', '판매자5',
    '판매자6', '판매자7', '판매자8', '판매자9', '판매자10',
    '판매자11', '판매자12', '판매자13', '판매자14', '판매자15'
];

for ($i = 0; $i < 15; $i++) {
    $userId = 'seller' . str_pad($i + 1, 2, '0', STR_PAD_LEFT);
    $email = 'seller' . ($i + 1) . '@test.com';
    $name = $sellerNames[$i];
    $password = '000000'; // 테스트용 비밀번호
    
    // 이미 존재하는지 확인
    if (getUserById($userId)) {
        continue;
    }

    $additional = [
        'phone' => '010-0000-0000',
        'mobile' => '010-0000-0000',
        'business_number' => '000-00-00000',
        'company_name' => '테스트회사' . ($i + 1),
        'business_license_image' => '/MVNO/uploads/sellers/test.png'
    ];
    registerDirectUser($userId, $password, $email, $name, 'seller', $additional);
}

echo "테스트 회원 데이터가 추가되었습니다.\n";
echo "- 일반회원: 15명\n";
echo "- 판매자 회원: 15명\n";
echo "\n판매자 로그인 정보:\n";
echo "아이디: seller01 ~ seller15\n";
echo "비밀번호: 000000\n";

