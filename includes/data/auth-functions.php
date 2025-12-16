<?php
/**
 * 사용자 인증 관련 함수
 * 
 * 주요 기능:
 * - 세션 관리
 * - 사용자 조회 (DB 및 JSON 파일)
 * - 로그인/로그아웃
 * - 회원가입 (SNS, 직접 가입)
 * - 판매자 관리 (승인, 권한 등)
 */

// 한국 시간대 설정
date_default_timezone_set('Asia/Seoul');

// 세션 설정 (전체 사이트에서 공유)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $currentSessionName = session_name();
    if (empty($currentSessionName) || $currentSessionName === 'PHPSESSID') {
        session_name('MVNO_SESSION');
    }
    
    // 세션 쿠키 설정
    if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params(0, '/', '', false, true);
    }
    
    session_start();
}

require_once __DIR__ . '/db-config.php';

// ============================================================================
// 파일 경로 함수
// ============================================================================

function getUsersFilePath() {
    return __DIR__ . '/users.json';
}

function getAdminsFilePath() {
    return __DIR__ . '/admins.json';
}

function getSellersFilePath() {
    return __DIR__ . '/sellers.json';
}

// ============================================================================
// 공통 헬퍼 함수
// ============================================================================

/**
 * JSON 파일에서 사용자 검색 (공통 로직)
 * 
 * @param string $filePath 파일 경로
 * @param string $key 검색할 키 (user_id, email, sns_provider 등)
 * @param mixed $value 검색할 값
 * @param string $dataKey JSON 데이터 키 (users, admins, sellers)
 * @return array|null 사용자 데이터 또는 null
 */
function searchUserInJsonFile($filePath, $key, $value, $dataKey) {
    if (!file_exists($filePath)) {
        return null;
    }
    
    clearstatcache(true, $filePath);
    $content = file_get_contents($filePath);
    $data = json_decode($content, true) ?: [$dataKey => []];
    
    foreach ($data[$dataKey] ?? [] as $user) {
        if (isset($user[$key]) && $user[$key] === $value) {
            return $user;
        }
    }
    
    return null;
}

/**
 * 사용자 데이터 정규화 (JSON 필드 처리)
 * 
 * @param array $user 사용자 데이터
 * @return array 정규화된 사용자 데이터
 */
function normalizeUserData($user) {
    if (isset($user['permissions']) && is_string($user['permissions'])) {
        $user['permissions'] = json_decode($user['permissions'], true) ?: [];
    }
    if (isset($user['seller_approved'])) {
        $user['seller_approved'] = (bool)$user['seller_approved'];
    }
    return $user;
}

// ============================================================================
// 사용자 조회 함수
// ============================================================================

/**
 * 사용자 ID로 사용자 찾기 (DB 우선, 실패 시 JSON)
 */
function getUserById($userId) {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id LIMIT 1");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                return normalizeUserData($user);
            }
        } catch (PDOException $e) {
            error_log("getUserById error: " . $e->getMessage());
        }
    }
    
    // JSON 파일에서 검색 (하위 호환성)
    $user = searchUserInJsonFile(getUsersFilePath(), 'user_id', $userId, 'users');
    if ($user) return $user;
    
    $user = searchUserInJsonFile(getAdminsFilePath(), 'user_id', $userId, 'admins');
    if ($user) return $user;
    
    $user = searchUserInJsonFile(getSellersFilePath(), 'user_id', $userId, 'sellers');
    if ($user) return $user;
    
    return null;
}

/**
 * 이메일로 사용자 찾기 (DB 우선, 실패 시 JSON)
 */
function getUserByEmail($email) {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                return normalizeUserData($user);
            }
        } catch (PDOException $e) {
            error_log("getUserByEmail error: " . $e->getMessage());
        }
    }
    
    // JSON 파일에서 검색 (하위 호환성)
    $user = searchUserInJsonFile(getUsersFilePath(), 'email', $email, 'users');
    if ($user) return $user;
    
    $user = searchUserInJsonFile(getAdminsFilePath(), 'email', $email, 'admins');
    if ($user) return $user;
    
    $user = searchUserInJsonFile(getSellersFilePath(), 'email', $email, 'sellers');
    if ($user) return $user;
    
    return null;
}

/**
 * SNS ID로 사용자 찾기 (DB 우선, 실패 시 JSON)
 */
function getUserBySnsId($provider, $snsId) {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE sns_provider = :provider AND sns_id = :sns_id LIMIT 1");
            $stmt->execute([
                ':provider' => $provider,
                ':sns_id' => $snsId
            ]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                return normalizeUserData($user);
            }
        } catch (PDOException $e) {
            error_log("getUserBySnsId error: " . $e->getMessage());
        }
    }
    
    // JSON 파일에서 검색 (하위 호환성)
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
    
    return null;
}

/**
 * 모든 사용자 데이터 읽기 (일반회원, 관리자, 판매자 통합)
 * 주의: 통합 검색이 필요한 경우에만 사용
 */
function getUsersData() {
    $allUsers = [];
    
    // 일반 회원 (DB에서 조회)
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM users WHERE role = 'user'");
            $dbUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($dbUsers as $user) {
                $allUsers[] = normalizeUserData($user);
            }
        } catch (PDOException $e) {
            error_log("getUsersData DB error: " . $e->getMessage());
        }
    }
    
    // DB 연결 실패 시 JSON 파일에서 읽기 (하위 호환성)
    if (empty($allUsers) || !$pdo) {
        $usersFile = getUsersFilePath();
        if (file_exists($usersFile)) {
            clearstatcache(true, $usersFile);
            $content = file_get_contents($usersFile);
            $data = json_decode($content, true) ?: ['users' => []];
            $allUsers = array_merge($allUsers, $data['users'] ?? []);
        }
    }
    
    // 관리자 (admins.json)
    $adminsFile = getAdminsFilePath();
    if (file_exists($adminsFile)) {
        clearstatcache(true, $adminsFile);
        $content = file_get_contents($adminsFile);
        $data = json_decode($content, true) ?: ['admins' => []];
        $allUsers = array_merge($allUsers, $data['admins'] ?? []);
    }
    
    // 판매자 (sellers.json)
    $sellersFile = getSellersFilePath();
    if (file_exists($sellersFile)) {
        clearstatcache(true, $sellersFile);
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
    
    file_put_contents(getUsersFilePath(), json_encode($usersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    file_put_contents(getAdminsFilePath(), json_encode($adminsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    file_put_contents(getSellersFilePath(), json_encode($sellersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ============================================================================
// 인증 관련 함수
// ============================================================================

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
 * 현재 사용자 ID 가져오기
 */
function getCurrentUserId() {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    return $_SESSION['user_id'] ?? null;
}

/**
 * 로그인 여부 확인
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        $currentSessionName = session_name();
        if (empty($currentSessionName) || $currentSessionName === 'PHPSESSID') {
            session_name('MVNO_SESSION');
        }
        session_start();
    }
    
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

// ============================================================================
// 회원가입 관련 함수
// ============================================================================

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
    $hash = md5($snsId . $provider);
    $numbers = preg_replace('/[^0-9]/', '', $hash);
    $idPart = substr(str_repeat($numbers, 3), 0, 7);
    
    return $prefix . '_' . $idPart;
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
    
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (user_id, email, name, role, sns_provider, sns_id, seller_approved, created_at)
                VALUES (:user_id, :email, :name, 'user', :sns_provider, :sns_id, 0, NOW())
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':email' => $email,
                ':name' => $name,
                ':sns_provider' => $provider,
                ':sns_id' => $snsId
            ]);
            
            return getUserById($userId);
        } catch (PDOException $e) {
            error_log("registerSnsUser DB error: " . $e->getMessage());
        }
    }
    
    // DB 연결 실패 또는 오류 시 JSON 파일에 저장 (하위 호환성)
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
    
    $file = getUsersFilePath();
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['users' => []];
    $data['users'][] = $newUser;
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    return $newUser;
}

/**
 * 직접 회원가입 (일반 회원, 관리자, 서브관리자, 판매자)
 */
function registerDirectUser($userId, $password, $email, $name, $role, $additionalData = []) {
    $allowedRoles = ['user', 'admin', 'sub_admin', 'seller'];
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
        'seller_approved' => $role === 'seller' ? false : true,
        'permissions' => []
    ];
    
    if ($role === 'seller') {
        $newUser['approval_status'] = 'pending';
    }
    
    if (!empty($additionalData)) {
        $newUser = array_merge($newUser, $additionalData);
    }
    
    // 일반 회원(user)만 DB에 저장, 나머지는 JSON 파일에 저장
    if ($role === 'user') {
        $pdo = getDBConnection();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO users (user_id, email, name, password, phone, role, seller_approved, created_at)
                    VALUES (:user_id, :email, :name, :password, :phone, 'user', 1, NOW())
                ");
                
                $stmt->execute([
                    ':user_id' => $newUser['user_id'],
                    ':email' => $newUser['email'],
                    ':name' => $newUser['name'],
                    ':password' => $newUser['password'],
                    ':phone' => $newUser['phone'] ?? null
                ]);
                
                $savedUser = getUserById($userId);
                return ['success' => true, 'user' => $savedUser];
            } catch (PDOException $e) {
                error_log("registerDirectUser DB error: " . $e->getMessage());
            }
        }
        
        // DB 연결 실패 시 JSON 파일에 저장 (하위 호환성)
        $file = getUsersFilePath();
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['users' => []];
        $data['users'][] = $newUser;
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } elseif ($role === 'admin' || $role === 'sub_admin') {
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

// ============================================================================
// 판매자 관리 함수
// ============================================================================

/**
 * 판매자 데이터 파일 저장 (공통 함수)
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
 * 판매자 데이터 로드 및 업데이트 헬퍼
 */
function loadAndUpdateSeller($userId, $callback) {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }
    
    $file = getSellersFilePath();
    if (!file_exists($file)) {
        return false;
    }
    
    clearstatcache(true, $file);
    $content = @file_get_contents($file);
    if ($content === false) {
        return false;
    }
    
    $data = @json_decode($content, true);
    if (!is_array($data) || !isset($data['sellers'])) {
        return false;
    }
    
    $foundUser = false;
    foreach ($data['sellers'] as &$u) {
        if (isset($u['user_id']) && $u['user_id'] === $userId) {
            $foundUser = true;
            $callback($u);
            break;
        }
    }
    
    if (!$foundUser) {
        return false;
    }
    
    return saveSellersData($file, $data);
}

/**
 * 판매자 승인
 */
function approveSeller($userId) {
    return loadAndUpdateSeller($userId, function(&$u) use ($userId) {
        $u['approved_at'] = date('Y-m-d H:i:s');
        $u['seller_approved'] = true;
        $u['approval_status'] = 'approved';
        if (!isset($u['permissions'])) {
            $u['permissions'] = [];
        }
    });
}

/**
 * 판매자 승인보류
 */
function holdSeller($userId) {
    return loadAndUpdateSeller($userId, function(&$u) {
        $u['held_at'] = date('Y-m-d H:i:s');
        $u['seller_approved'] = false;
        $u['approval_status'] = 'on_hold';
    });
}

/**
 * 판매자 신청 거부
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
            break;
        }
    }
    
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

/**
 * 판매자 승인 취소 (승인 -> 대기로 변경)
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
            $u['seller_approved'] = false;
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
            
            if (!empty($deleteDate)) {
                $u['scheduled_delete_date'] = $deleteDate;
            } else {
                $u['scheduled_delete_date'] = null;
                $u['scheduled_delete_time'] = null;
                $u['email'] = 'withdrawn_' . $userId . '@withdrawn';
                if (isset($u['phone'])) unset($u['phone']);
                if (isset($u['mobile'])) unset($u['mobile']);
                if (isset($u['address'])) unset($u['address']);
                if (isset($u['address_detail'])) unset($u['address_detail']);
                if (isset($u['postal_code'])) unset($u['postal_code']);
                
                // 업로드된 파일 삭제 (사업자등록증 등)
                if (isset($u['business_license_image']) && !empty($u['business_license_image'])) {
                    $imagePath = $_SERVER['DOCUMENT_ROOT'] . $u['business_license_image'];
                    if (file_exists($imagePath)) {
                        @unlink($imagePath);
                    }
                }
                
                // 기타 첨부파일 삭제
                if (isset($u['other_documents']) && is_array($u['other_documents'])) {
                    foreach ($u['other_documents'] as $doc) {
                        if (isset($doc['url']) && !empty($doc['url'])) {
                            $docPath = $_SERVER['DOCUMENT_ROOT'] . $doc['url'];
                            if (file_exists($docPath)) {
                                @unlink($docPath);
                            }
                        }
                    }
                }
            }
            break;
        }
    }
    
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
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
        if (isset($u['scheduled_delete_date']) && !empty($u['scheduled_delete_date']) 
            && (!isset($u['scheduled_delete_processed']) || $u['scheduled_delete_processed'] !== true)) {
            $scheduledDate = $u['scheduled_delete_date'];
            
            if ($scheduledDate <= $currentDate) {
                $u['email'] = 'withdrawn_' . $u['user_id'] . '@withdrawn';
                if (isset($u['phone'])) unset($u['phone']);
                if (isset($u['mobile'])) unset($u['mobile']);
                if (isset($u['address'])) unset($u['address']);
                if (isset($u['address_detail'])) unset($u['address_detail']);
                if (isset($u['postal_code'])) unset($u['postal_code']);
                
                // 업로드된 파일 삭제 (사업자등록증 등)
                if (isset($u['business_license_image']) && !empty($u['business_license_image'])) {
                    $imagePath = $_SERVER['DOCUMENT_ROOT'] . $u['business_license_image'];
                    if (file_exists($imagePath)) {
                        @unlink($imagePath);
                    }
                }
                
                // 기타 첨부파일 삭제
                if (isset($u['other_documents']) && is_array($u['other_documents'])) {
                    foreach ($u['other_documents'] as $doc) {
                        if (isset($doc['url']) && !empty($doc['url'])) {
                            $docPath = $_SERVER['DOCUMENT_ROOT'] . $doc['url'];
                            if (file_exists($docPath)) {
                                @unlink($docPath);
                            }
                        }
                    }
                }
                
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
    
    if (!isset($user['seller_approved']) || $user['seller_approved'] !== true) {
        return false;
    }
    
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



