<?php
/**
 * 사용자 인증 관련 함수
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

// 세션 시작 (헤더가 전송되지 않은 경우에만)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

/**
 * 사용자 데이터 파일 경로
 */
function getUsersFilePath() {
    return __DIR__ . '/users.json';
}

function getAdminsFilePath() {
    return __DIR__ . '/admins.json';
}

function getSellersFilePath() {
    return __DIR__ . '/sellers.json';
}

/**
 * 모든 사용자 데이터 읽기 (일반회원, 관리자, 판매자 통합)
 * 주의: 통합 검색이 필요한 경우에만 사용하세요.
 * 일반적으로는 getUserById(), getUserByEmail() 등 역할별 분리 검색 함수를 사용하세요.
 */
function getUsersData() {
    $allUsers = [];
    
    // 일반 회원 (users.json)
    $usersFile = getUsersFilePath();
    if (file_exists($usersFile)) {
        clearstatcache(true, $usersFile); // 파일 캐시 클리어
        $content = file_get_contents($usersFile);
        $data = json_decode($content, true) ?: ['users' => []];
        $allUsers = array_merge($allUsers, $data['users'] ?? []);
    }
    
    // 관리자 (admins.json)
    $adminsFile = getAdminsFilePath();
    if (file_exists($adminsFile)) {
        clearstatcache(true, $adminsFile); // 파일 캐시 클리어
        $content = file_get_contents($adminsFile);
        $data = json_decode($content, true) ?: ['admins' => []];
        $allUsers = array_merge($allUsers, $data['admins'] ?? []);
    }
    
    // 판매자 (sellers.json)
    $sellersFile = getSellersFilePath();
    if (file_exists($sellersFile)) {
        clearstatcache(true, $sellersFile); // 파일 캐시 클리어
        $content = file_get_contents($sellersFile);
        $data = json_decode($content, true) ?: ['sellers' => []];
        $allUsers = array_merge($allUsers, $data['sellers'] ?? []);
    }
    
    return ['users' => $allUsers];
}

/**
 * 사용자 데이터 저장 (역할에 따라 적절한 파일에 저장)
 */
function saveUsersData($data) {
    $users = $data['users'] ?? [];
    $usersData = ['users' => []];
    $adminsData = ['admins' => []];
    $sellersData = ['sellers' => []];
    
    foreach ($users as $user) {
        $role = $user['role'] ?? 'user';
        if ($role === 'admin' || $role === 'sub_admin') {
            $adminsData['admins'][] = $user;
        } elseif ($role === 'seller') {
            $sellersData['sellers'][] = $user;
        } else {
            $usersData['users'][] = $user;
        }
    }
    
    // 각 파일에 저장
    file_put_contents(getUsersFilePath(), json_encode($usersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    file_put_contents(getAdminsFilePath(), json_encode($adminsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    file_put_contents(getSellersFilePath(), json_encode($sellersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * SNS 아이디 생성 (형식: nvr_3211205, kko_3211205, gol_3211205)
 */
function generateSnsUserId($provider, $snsId) {
    $prefixes = [
        'naver' => 'nvr',
        'kakao' => 'kko',
        'google' => 'gol'
    ];
    
    $prefix = $prefixes[$provider] ?? 'sns';
    
    // SNS ID를 해시하여 고유한 숫자 생성
    $hash = md5($snsId . $provider);
    // 해시에서 숫자만 추출하여 7자리 숫자 생성
    $numbers = preg_replace('/[^0-9]/', '', $hash);
    // 7자리 숫자 추출 (부족하면 반복)
    $idPart = substr(str_repeat($numbers, 3), 0, 7);
    
    return $prefix . '_' . $idPart;
}

/**
 * 사용자 ID로 사용자 찾기 (역할별 DB 분리 검색)
 */
function getUserById($userId) {
    // 1. 일반 회원 검색 (users.json)
    $usersFile = getUsersFilePath();
    if (file_exists($usersFile)) {
        $content = file_get_contents($usersFile);
        $data = json_decode($content, true) ?: ['users' => []];
        foreach ($data['users'] ?? [] as $user) {
            if (isset($user['user_id']) && $user['user_id'] === $userId) {
                return $user;
            }
        }
    }
    
    // 2. 관리자/부관리자 검색 (admins.json)
    $adminsFile = getAdminsFilePath();
    if (file_exists($adminsFile)) {
        $content = file_get_contents($adminsFile);
        $data = json_decode($content, true) ?: ['admins' => []];
        foreach ($data['admins'] ?? [] as $admin) {
            if (isset($admin['user_id']) && $admin['user_id'] === $userId) {
                return $admin;
            }
        }
    }
    
    // 3. 판매자 검색 (sellers.json)
    $sellersFile = getSellersFilePath();
    if (file_exists($sellersFile)) {
        $content = file_get_contents($sellersFile);
        $data = json_decode($content, true) ?: ['sellers' => []];
        foreach ($data['sellers'] ?? [] as $seller) {
            if (isset($seller['user_id']) && $seller['user_id'] === $userId) {
                return $seller;
            }
        }
    }
    
    return null;
}

/**
 * 이메일로 사용자 찾기 (역할별 DB 분리 검색)
 */
function getUserByEmail($email) {
    // 1. 일반 회원 검색 (users.json)
    $usersFile = getUsersFilePath();
    if (file_exists($usersFile)) {
        $content = file_get_contents($usersFile);
        $data = json_decode($content, true) ?: ['users' => []];
        foreach ($data['users'] ?? [] as $user) {
            if (isset($user['email']) && $user['email'] === $email) {
                return $user;
            }
        }
    }
    
    // 2. 관리자/부관리자 검색 (admins.json) - 관리자는 이메일이 없을 수 있음
    $adminsFile = getAdminsFilePath();
    if (file_exists($adminsFile)) {
        $content = file_get_contents($adminsFile);
        $data = json_decode($content, true) ?: ['admins' => []];
        foreach ($data['admins'] ?? [] as $admin) {
            if (isset($admin['email']) && $admin['email'] === $email) {
                return $admin;
            }
        }
    }
    
    // 3. 판매자 검색 (sellers.json)
    $sellersFile = getSellersFilePath();
    if (file_exists($sellersFile)) {
        $content = file_get_contents($sellersFile);
        $data = json_decode($content, true) ?: ['sellers' => []];
        foreach ($data['sellers'] ?? [] as $seller) {
            if (isset($seller['email']) && $seller['email'] === $email) {
                return $seller;
            }
        }
    }
    
    return null;
}

/**
 * SNS ID로 사용자 찾기 (역할별 DB 분리 검색)
 */
function getUserBySnsId($provider, $snsId) {
    // 1. 일반 회원 검색 (users.json) - SNS 로그인은 주로 일반 회원
    $usersFile = getUsersFilePath();
    if (file_exists($usersFile)) {
        $content = file_get_contents($usersFile);
        $data = json_decode($content, true) ?: ['users' => []];
        foreach ($data['users'] ?? [] as $user) {
            if (isset($user['sns_provider']) && $user['sns_provider'] === $provider && 
                isset($user['sns_id']) && $user['sns_id'] === $snsId) {
                return $user;
            }
        }
    }
    
    // 2. 관리자/부관리자 검색 (admins.json) - 일반적으로 SNS 로그인 없음
    $adminsFile = getAdminsFilePath();
    if (file_exists($adminsFile)) {
        $content = file_get_contents($adminsFile);
        $data = json_decode($content, true) ?: ['admins' => []];
        foreach ($data['admins'] ?? [] as $admin) {
            if (isset($admin['sns_provider']) && $admin['sns_provider'] === $provider && 
                isset($admin['sns_id']) && $admin['sns_id'] === $snsId) {
                return $admin;
            }
        }
    }
    
    // 3. 판매자 검색 (sellers.json) - 일반적으로 SNS 로그인 없음
    $sellersFile = getSellersFilePath();
    if (file_exists($sellersFile)) {
        $content = file_get_contents($sellersFile);
        $data = json_decode($content, true) ?: ['sellers' => []];
        foreach ($data['sellers'] ?? [] as $seller) {
            if (isset($seller['sns_provider']) && $seller['sns_provider'] === $provider && 
                isset($seller['sns_id']) && $seller['sns_id'] === $snsId) {
                return $seller;
            }
        }
    }
    
    return null;
}

/**
 * 사용자 로그인
 */
function loginUser($userId) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['logged_in'] = true;
}

/**
 * 사용자 로그아웃
 */
function logoutUser() {
    unset($_SESSION['user_id']);
    unset($_SESSION['logged_in']);
    session_destroy();
}

/**
 * 현재 로그인한 사용자 정보 가져오기
 */
function getCurrentUser() {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        return null;
    }
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return getUserById($_SESSION['user_id']);
}

/**
 * 로그인 여부 확인
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * 사용자 역할 확인
 */
function getUserRole($userId = null) {
    if ($userId === null) {
        $user = getCurrentUser();
        if (!$user) {
            return null;
        }
        return $user['role'] ?? 'user';
    }
    
    $user = getUserById($userId);
    return $user ? ($user['role'] ?? 'user') : null;
}

/**
 * 관리자 여부 확인
 */
function isAdmin($userId = null) {
    $role = getUserRole($userId);
    return $role === 'admin' || $role === 'sub_admin';
}

/**
 * 판매자 여부 확인
 */
function isSeller($userId = null) {
    $role = getUserRole($userId);
    return $role === 'seller';
}

/**
 * 판매자 승인 여부 확인
 */
function isSellerApproved($userId = null) {
    $user = $userId ? getUserById($userId) : getCurrentUser();
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }
    return isset($user['seller_approved']) && $user['seller_approved'] === true;
}

/**
 * 일반 회원 가입 (SNS)
 */
function registerSnsUser($provider, $snsId, $email, $name) {
    // 이미 가입된 사용자 확인
    $existingUser = getUserBySnsId($provider, $snsId);
    if ($existingUser) {
        return $existingUser;
    }
    
    $userId = generateSnsUserId($provider, $snsId);
    
    // 중복 확인 및 재생성
    while (getUserById($userId)) {
        $userId = generateSnsUserId($provider, $snsId . rand(1000, 9999));
    }
    
    $newUser = [
        'user_id' => $userId,
        'email' => $email,
        'name' => $name,
        'role' => 'user',
        'sns_provider' => $provider,
        'sns_id' => $snsId,
        'created_at' => date('Y-m-d H:i:s'),
        'seller_approved' => false
    ];
    
    // users.json에만 저장 (일반 회원)
    $file = getUsersFilePath();
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['users' => []];
    $data['users'][] = $newUser;
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    return $newUser;
}

/**
 * 직접 회원가입 (관리자, 서브관리자, 판매자)
 */
function registerDirectUser($userId, $password, $email, $name, $role, $additionalData = []) {
    // 역할 검증
    $allowedRoles = ['admin', 'sub_admin', 'seller'];
    if (!in_array($role, $allowedRoles)) {
        return ['success' => false, 'message' => '허용되지 않은 역할입니다.'];
    }
    
    // 이미 존재하는 사용자 확인
    if (getUserById($userId)) {
        return ['success' => false, 'message' => '이미 존재하는 아이디입니다.'];
    }
    
    if (getUserByEmail($email)) {
        return ['success' => false, 'message' => '이미 사용 중인 이메일입니다.'];
    }
    
    $newUser = [
        'user_id' => $userId,
        'email' => $email,
        'name' => $name,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'created_at' => date('Y-m-d H:i:s'),
        'seller_approved' => $role === 'seller' ? false : true, // 판매자는 승인 필요
        'permissions' => [] // 판매자 권한 (초기값: 빈 배열)
    ];
    
    // 판매자 가입 시 approval_status를 pending으로 설정
    if ($role === 'seller') {
        $newUser['approval_status'] = 'pending';
    }
    
    // 추가 데이터 병합 (판매자 정보 등)
    if (!empty($additionalData)) {
        $newUser = array_merge($newUser, $additionalData);
    }
    
    // 역할에 따라 적절한 파일에 저장
    if ($role === 'admin' || $role === 'sub_admin') {
        $file = getAdminsFilePath();
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['admins' => []];
        $data['admins'][] = $newUser;
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } elseif ($role === 'seller') {
        $file = getSellersFilePath();
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['sellers' => []];
        $data['sellers'][] = $newUser;
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    return ['success' => true, 'user' => $newUser];
}

/**
 * 직접 로그인 (아이디/비밀번호)
 */
function loginDirectUser($userId, $password) {
    $user = getUserById($userId);
    if (!$user) {
        return ['success' => false, 'message' => '아이디 또는 비밀번호가 올바르지 않습니다.'];
    }
    
    // SNS 가입 사용자는 직접 로그인 불가
    if (isset($user['sns_provider'])) {
        return ['success' => false, 'message' => 'SNS 로그인을 사용해주세요.'];
    }
    
    // 비밀번호 확인
    if (!isset($user['password']) || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => '아이디 또는 비밀번호가 올바르지 않습니다.'];
    }
    
    loginUser($userId);
    return ['success' => true, 'user' => $user];
}

/**
 * 판매자 데이터 파일 저장 (공통 함수)
 * 
 * @param string $file 파일 경로
 * @param array $data 저장할 데이터
 * @return bool 성공 여부
 */
function saveSellersData($file, $data) {
    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    $fp = fopen($file, 'c+');
    if (!$fp) {
        return false;
    }
    
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    
    ftruncate($fp, 0);
    rewind($fp);
    
    $written = fwrite($fp, $jsonData);
    if ($written === false || $written === 0) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return false;
    }
    
    fflush($fp);
    
    if (function_exists('fsync')) {
        fsync($fp);
    }
    
    flock($fp, LOCK_UN);
    fclose($fp);
    
    clearstatcache(true, $file);
    
    return true;
}

/**
 * 판매자 승인
 * 
 * 날짜 필드 정리:
 * - created_at (가입일): 최초 1번만 설정, 변경 안됨
 * - approved_at (승인일): 승인할 때마다 현재 시간으로 업데이트
 * - held_at (승인보류일): 승인보류할 때마다 현재 시간으로 업데이트
 * - updated_at (정보수정일): 판매자 정보 수정 시 업데이트 (별도 관리)
 * 
 * @param string $userId 판매자 아이디
 * @return bool 성공 여부
 */
function approveSeller($userId) {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }
    
    $file = getSellersFilePath();
    if (!file_exists($file)) {
        return false;
    }
    
    // 파일 캐시 클리어 후 읽기
    clearstatcache(true, $file);
    
    // 파일 내용 읽기
    $content = @file_get_contents($file);
    if ($content === false) {
        return false;
    }
    
    $data = @json_decode($content, true);
    if (!is_array($data) || !isset($data['sellers'])) {
        return false;
    }
    
    $currentTime = date('Y-m-d H:i:s');
    $foundUser = false;
    
    // 데이터 업데이트
    foreach ($data['sellers'] as &$u) {
        if (isset($u['user_id']) && $u['user_id'] === $userId) {
            $foundUser = true;
            $u['approved_at'] = $currentTime;  // 승인일 업데이트
            $u['seller_approved'] = true;
            $u['approval_status'] = 'approved';
            
            if (!isset($u['permissions'])) {
                $u['permissions'] = [];
            }
            break;
        }
    }
    
    if (!$foundUser) {
        return false;
    }
    
    // 파일 저장
    return saveSellersData($file, $data);
}

/**
 * 판매자 승인보류
 * 
 * 날짜 필드 정리:
 * - held_at (승인보류일): 승인보류할 때마다 현재 시간으로 업데이트
 * - approved_at (승인일): 승인보류 시에도 유지 (히스토리 보존)
 * - updated_at (정보수정일): 변경 안됨 (별도 관리)
 * 
 * @param string $userId 판매자 아이디
 * @return bool 성공 여부
 */
function holdSeller($userId) {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }
    
    $file = getSellersFilePath();
    if (!file_exists($file)) {
        return false;
    }
    
    // 파일 캐시 클리어 후 읽기
    clearstatcache(true, $file);
    
    // 파일 내용 읽기
    $content = @file_get_contents($file);
    if ($content === false) {
        return false;
    }
    
    $data = @json_decode($content, true);
    if (!is_array($data) || !isset($data['sellers'])) {
        return false;
    }
    
    $currentTime = date('Y-m-d H:i:s');
    $foundUser = false;
    
    // 데이터 업데이트
    foreach ($data['sellers'] as &$u) {
        if (isset($u['user_id']) && $u['user_id'] === $userId) {
            $foundUser = true;
            $u['held_at'] = $currentTime;  // 승인보류일 업데이트
            $u['seller_approved'] = false;
            $u['approval_status'] = 'on_hold';
            // approved_at은 유지 (히스토리 보존)
            break;
        }
    }
    
    if (!$foundUser) {
        return false;
    }
    
    // 파일 저장
    return saveSellersData($file, $data);
}

/**
 * 판매자 신청 취소 (거부)
 */
function rejectSeller($userId) {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }
    
    $file = getSellersFilePath();
    if (!file_exists($file)) {
        return false;
    }
    
    $data = json_decode(file_get_contents($file), true) ?: ['sellers' => []];
    foreach ($data['sellers'] as &$u) {
        if ($u['user_id'] === $userId) {
            $u['seller_approved'] = false;
            $u['approval_status'] = 'rejected';
            // rejected_at 필드 제거 (용어 정리: 승인일, 승인보류일만 유지)
            break;
        }
    }
    
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

/**
 * 판매자 승인 취소 (승인 -> 대기로 변경)
 * 
 * 날짜 필드 정리:
 * - approved_at (승인일): 승인 취소 시에도 유지 (히스토리 보존)
 * - held_at (승인보류일): 승인 취소 시 유지 (히스토리 보존)
 * - updated_at (정보수정일): 변경 안됨 (별도 관리)
 * 
 * @param string $userId 판매자 아이디
 * @return bool 성공 여부
 */
function cancelSellerApproval($userId) {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }
    
    $file = getSellersFilePath();
    if (!file_exists($file)) {
        return false;
    }
    
    $data = json_decode(file_get_contents($file), true) ?: ['sellers' => []];
    foreach ($data['sellers'] as &$u) {
        if ($u['user_id'] === $userId) {
            $u['seller_approved'] = false;
            $u['approval_status'] = 'pending';
            // 승인일(approved_at)과 승인보류일(held_at)은 유지 (히스토리 보존)
            break;
        }
    }
    
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

/**
 * 판매자 탈퇴 요청 (Soft Delete - 계정 비활성화)
 * 
 * 중요: 계정만 비활성화되며, 다음은 모두 보존됩니다:
 * - 등록한 상품 정보 (모든 상품 데이터)
 * - 고객의 구매 기록 (신청내역, 주문 내역)
 * - 리뷰 및 평가 정보
 * - 주문 및 거래 정보
 */
function requestSellerWithdrawal($userId, $reason = '') {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }
    
    $file = getSellersFilePath();
    if (!file_exists($file)) {
        return false;
    }
    
    $data = json_decode(file_get_contents($file), true) ?: ['sellers' => []];
    foreach ($data['sellers'] as &$u) {
        if ($u['user_id'] === $userId) {
            $u['withdrawal_requested'] = true;
            $u['withdrawal_requested_at'] = date('Y-m-d H:i:s');
            $u['withdrawal_reason'] = $reason;
            $u['approval_status'] = 'withdrawal_requested';
            // 계정 비활성화 (로그인 불가)
            $u['seller_approved'] = false;
            // 등록한 상품 정보, 고객 구매 기록, 리뷰, 주문 등은 모두 보존 (데이터 보존 정책)
            break;
        }
    }
    
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

/**
 * 판매자 탈퇴 완료 처리 (관리자 승인 후)
 * 
 * 중요: 다음 데이터는 모두 보존됩니다.
 * 1. 등록한 상품 정보 (상품명, 가격, 설명, 이미지 등 모든 상품 데이터)
 * 2. 고객의 구매 기록 (신청내역, 주문 내역 등)
 * 3. 리뷰 및 평가 정보
 * 4. 주문 및 거래 정보
 * 
 * 삭제/비식별화되는 것:
 * - 개인정보만 삭제 (이름, 이메일, 연락처, 주소)
 * - 계정 비활성화 (로그인 불가)
 * 
 * 보존 이유:
 * - 법적 보존 의무 (거래 기록, 상품 정보)
 * - 고객의 구매 이력 확인 필요
 * - 상품 정보 참고용 (고객이 구매한 상품 정보 확인)
 */
function completeSellerWithdrawal($userId, $deleteDate = null) {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }
    
    $file = getSellersFilePath();
    if (!file_exists($file)) {
        return false;
    }
    
    $data = json_decode(file_get_contents($file), true) ?: ['sellers' => []];
    foreach ($data['sellers'] as &$u) {
        if ($u['user_id'] === $userId) {
            $u['withdrawal_completed'] = true;
            $u['withdrawal_completed_at'] = date('Y-m-d H:i:s');
            $u['approval_status'] = 'withdrawn';
            $u['seller_approved'] = false;
            // 탈퇴 완료 처리 시 탈퇴 요청 플래그는 유지하되, 탈퇴 요청 탭에서는 제외됨 (위의 필터에서 처리)
            
            // 삭제 예정일 설정 (해당 날짜에 삭제)
            if (!empty($deleteDate)) {
                $u['scheduled_delete_date'] = $deleteDate;
            } else {
                // 날짜 미지정 시 즉시 삭제 처리
                $u['scheduled_delete_date'] = null;
                $u['scheduled_delete_time'] = null;
                // 즉시 삭제 처리
                $u['email'] = 'withdrawn_' . $userId . '@withdrawn';
                if (isset($u['phone'])) unset($u['phone']);
                if (isset($u['mobile'])) unset($u['mobile']);
                if (isset($u['address'])) unset($u['address']);
                if (isset($u['address_detail'])) unset($u['address_detail']);
                if (isset($u['postal_code'])) unset($u['postal_code']);
            }
            
            // 이름은 그대로 유지, 상태만 'withdrawn'으로 변경
            // 중요: 등록한 상품 정보와 고객의 구매 기록은 그대로 보존됨
            // 이름은 유지하므로 $u['name'] 변경하지 않음
            
            // 사업자 정보는 보존 (거래 이력과 연결되어 있음)
            // 다만 개인 연락처 정보는 삭제 예정일이 지나면 삭제
            
            // 등록한 상품 정보는 별도 파일에 저장되어 있으므로
            // 판매자 정보 삭제와 무관하게 보존됨
            // 고객의 주문/신청 내역도 별도 파일에 저장되어 있으므로 보존됨
            
            break;
        }
    }
    
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // 삭제 예정일이 오늘 이전이거나 같으면 즉시 삭제 처리
    if (!empty($deleteDate) && $deleteDate <= date('Y-m-d')) {
        processScheduledDeletions();
    }
    
    return true;
}

/**
 * 예정된 삭제 처리 (삭제 예정일이 지난 판매자들 처리)
 */
function processScheduledDeletions() {
    $file = getSellersFilePath();
    if (!file_exists($file)) {
        return false;
    }
    
    $data = json_decode(file_get_contents($file), true) ?: ['sellers' => []];
    $currentDate = date('Y-m-d');
    $currentDateTime = date('Y-m-d H:i:s');
    $updated = false;
    
    foreach ($data['sellers'] as &$u) {
        // 삭제 예정일이 설정되어 있고, 아직 처리되지 않은 경우
        if (isset($u['scheduled_delete_date']) && !empty($u['scheduled_delete_date']) 
            && (!isset($u['scheduled_delete_processed']) || $u['scheduled_delete_processed'] !== true)) {
            $scheduledDate = $u['scheduled_delete_date'];
            
            // 삭제 예정일이 현재 날짜보다 이전이거나 같은 경우 (날짜만 비교)
            if ($scheduledDate <= $currentDate) {
                // 개인정보 삭제 처리
                $u['email'] = 'withdrawn_' . $u['user_id'] . '@withdrawn';
                if (isset($u['phone'])) unset($u['phone']);
                if (isset($u['mobile'])) unset($u['mobile']);
                if (isset($u['address'])) unset($u['address']);
                if (isset($u['address_detail'])) unset($u['address_detail']);
                if (isset($u['postal_code'])) unset($u['postal_code']);
                
                // 삭제 처리 완료 표시
                $u['scheduled_delete_processed'] = true;
                $u['scheduled_delete_processed_at'] = $currentDateTime;
                
                $updated = true;
            }
        }
    }
    
    if ($updated) {
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    return $updated;
}

/**
 * 판매자 탈퇴 요청 취소 (판매자가 취소하거나 관리자가 거부)
 */
function cancelSellerWithdrawal($userId) {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }
    
    $file = getSellersFilePath();
    if (!file_exists($file)) {
        return false;
    }
    
    $data = json_decode(file_get_contents($file), true) ?: ['sellers' => []];
    foreach ($data['sellers'] as &$u) {
        if ($u['user_id'] === $userId) {
            $u['withdrawal_requested'] = false;
            $u['approval_status'] = 'approved';
            $u['seller_approved'] = true;
            if (isset($u['withdrawal_reason'])) unset($u['withdrawal_reason']);
            break;
        }
    }
    
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

/**
 * 판매자 권한 설정
 */
function setSellerPermissions($userId, $permissions) {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }
    
    // 권한 검증
    $allowedPermissions = ['mvno', 'mno', 'internet'];
    $validPermissions = [];
    foreach ($permissions as $perm) {
        if (in_array($perm, $allowedPermissions)) {
            $validPermissions[] = $perm;
        }
    }
    
    $file = getSellersFilePath();
    if (!file_exists($file)) {
        return false;
    }
    
    $data = json_decode(file_get_contents($file), true) ?: ['sellers' => []];
    foreach ($data['sellers'] as &$u) {
        if ($u['user_id'] === $userId) {
            $u['permissions'] = $validPermissions;
            $u['permissions_updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

/**
 * 판매자 권한 확인
 */
function hasSellerPermission($userId, $permission) {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }
    
    // 승인되지 않은 판매자는 권한 없음
    if (!isset($user['seller_approved']) || $user['seller_approved'] !== true) {
        return false;
    }
    
    // 권한이 없으면 false
    if (!isset($user['permissions']) || !is_array($user['permissions'])) {
        return false;
    }
    
    return in_array($permission, $user['permissions']);
}

/**
 * 현재 판매자의 권한 확인
 */
function canSellerPost($permission) {
    $user = getCurrentUser();
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }
    
    return hasSellerPermission($user['user_id'], $permission);
}

