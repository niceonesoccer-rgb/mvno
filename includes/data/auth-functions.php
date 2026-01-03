<?php
/**
 * 사용자 인증 관련 함수
 * 
 * 주요 기능:
 * - 세션 관리
 * - 사용자 조회 (DB-only)
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
    
    // 세션 쿠키 설정 (세션 유지 개선)
    if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
        session_set_cookie_params([
            'lifetime' => 0, // 브라우저 종료 시까지 유지
            'path' => '/',
            'domain' => '', // 현재 도메인
            'secure' => false, // HTTPS가 아니면 false
            'httponly' => true, // JavaScript 접근 방지
            'samesite' => 'Lax' // CSRF 방지
        ]);
    } else {
        session_set_cookie_params(0, '/', '', false, true);
    }
    
    session_start();
    
    // 세션 재생성 방지 (세션 ID 고정) - session_start() 이후에 실행
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(false); // false = 세션 데이터 유지
        $_SESSION['initiated'] = true;
    }
}

require_once __DIR__ . '/db-config.php';

// ============================================================================
// DB-only 모드
// - 레거시 JSON(users/admins/sellers) 경로/헬퍼 제거
// ============================================================================

// ============================================================================
// 판매자 입력값 검증(공용)
// - seller/register.php 의 "4단계" 검증 규칙을 공용 함수로 제공
// - 가입 화면 코드는 그대로 두고, 다른 화면에서 재사용한다.
// ============================================================================

/**
 * 휴대폰 번호 검증 + 포맷팅 (010 + 11자리)
 *
 * @param mixed $mobileRaw 입력값(하이픈 포함 가능)
 * @param string|null $errorMessage 실패 시 메시지
 * @return string|null 성공 시 010-XXXX-XXXX, 실패 시 null
 */
function validateAndFormatSellerMobile($mobileRaw, &$errorMessage = null) {
    $mobileRaw = trim((string)$mobileRaw);
    $digits = preg_replace('/[^\d]/', '', $mobileRaw);

    if (!preg_match('/^010\d{8}$/', $digits)) {
        $errorMessage = '휴대폰 번호는 010으로 시작하는 11자리 숫자여야 합니다.';
        return null;
    }

    return '010-' . substr($digits, 3, 4) . '-' . substr($digits, 7, 4);
}

/**
 * 전화번호 1개를 검증하고 하이픈 포맷팅하여 반환
 * - 휴대폰 / 대표번호 / 지역번호 / 070 / 080 등 포함
 *
 * @param string $digits 숫자만 남긴 문자열
 * @param string|null $errorMessage 실패 시 메시지
 * @return string|null 성공 시 하이픈 포함 포맷, 실패 시 null
 */
function validateAndFormatSingleKoreanPhoneDigits($digits, &$errorMessage = null) {
    $digits = preg_replace('/[^\d]/', '', (string)$digits);
    $len = strlen($digits);

    // 대표번호: 1XXX-XXXX (8자리) ex) 1588-1234, 1688-6547, 1544-0000, 1577-9999
    // 입력 중/붙여넣기로 8자리 초과가 들어오는 경우가 있어 8자리까지만 사용(UX 일치)
    if ($len >= 8 && preg_match('/^1\d{3}\d{4}$/', substr($digits, 0, 8))) {
        $digits8 = substr($digits, 0, 8);
        return substr($digits8, 0, 4) . '-' . substr($digits8, 4, 4);
    }

    // 휴대폰: 01X-XXXX-XXXX (11자리)
    if ($len === 11 && preg_match('/^01[0-9]\d{8}$/', $digits)) {
        return substr($digits, 0, 3) . '-' . substr($digits, 3, 4) . '-' . substr($digits, 7, 4);
    }

    // 서울(02): 02-XXX-XXXX (9자리) 또는 02-XXXX-XXXX (10자리)
    if ($len === 9 && preg_match('/^02\d{7}$/', $digits)) {
        return '02-' . substr($digits, 2, 3) . '-' . substr($digits, 5, 4);
    }
    if ($len === 10 && preg_match('/^02\d{8}$/', $digits)) {
        return '02-' . substr($digits, 2, 4) . '-' . substr($digits, 6, 4);
    }

    // 인터넷/수신자부담: 070/080 - 10자리(3-3-4) 또는 11자리(3-4-4)
    if ($len === 10 && preg_match('/^0[78]0\d{7}$/', $digits)) {
        return substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6, 4);
    }
    if ($len === 11 && preg_match('/^0[78]0\d{8}$/', $digits)) {
        return substr($digits, 0, 3) . '-' . substr($digits, 3, 4) . '-' . substr($digits, 7, 4);
    }

    // 지역번호(3자리): 0XX-XXX-XXXX (10자리) 또는 0XX-XXXX-XXXX (11자리)
    if ($len === 10 && preg_match('/^0[3-6]\d{8}$/', $digits)) {
        return substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6, 4);
    }
    if ($len === 11 && preg_match('/^0[3-6]\d{9}$/', $digits)) {
        return substr($digits, 0, 3) . '-' . substr($digits, 3, 4) . '-' . substr($digits, 7, 4);
    }

    $errorMessage = '전화번호 형식이 올바르지 않습니다. (예: 02-1234-5678, 031-123-4567, 010-1234-5678, 1588-1234, 070-1234-5678, 080-1234-5678)';
    return null;
}

/**
 * 전화번호 검증 + 자동 하이픈 포맷(쉼표로 구분된 여러 번호 지원)
 *
 * @param mixed $phoneRaw 입력값
 * @param string|null $errorMessage 실패 시 메시지
 * @param string|null $formatted 성공 시 표준 포맷(쉼표 구분)
 * @return bool
 */
function validateSellerPhoneList($phoneRaw, &$errorMessage = null, &$formatted = null) {
    $phoneRaw = trim((string)$phoneRaw);
    if ($phoneRaw === '') return true; // 선택 입력

    $phoneList = array_map('trim', explode(',', $phoneRaw));
    $phoneList = array_filter($phoneList, static fn($v) => $v !== '');

    $formattedList = [];
    foreach ($phoneList as $phoneItem) {
        $digits = preg_replace('/[^\d]/', '', $phoneItem);
        $formattedOne = validateAndFormatSingleKoreanPhoneDigits($digits, $errorMessage);
        if ($formattedOne === null) {
            return false;
        }
        $formattedList[] = $formattedOne;
    }

    // 표준 포맷으로 정규화(저장 시 하이픈 포함, 쉼표+공백으로 구분)
    $formatted = implode(', ', $formattedList);
    return true;
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
    if (isset($user['alarm_settings']) && is_string($user['alarm_settings'])) {
        $user['alarm_settings'] = json_decode($user['alarm_settings'], true) ?: [];
    }
    if (isset($user['seller_approved'])) {
        $user['seller_approved'] = (bool)$user['seller_approved'];
    }
    // 탈퇴 관련 필드도 boolean으로 변환
    if (isset($user['withdrawal_requested'])) {
        $user['withdrawal_requested'] = (bool)$user['withdrawal_requested'];
    }
    if (isset($user['withdrawal_completed'])) {
        $user['withdrawal_completed'] = (bool)$user['withdrawal_completed'];
    }
    if (isset($user['info_updated'])) {
        $user['info_updated'] = (bool)$user['info_updated'];
    }
    if (isset($user['info_checked_by_admin'])) {
        $user['info_checked_by_admin'] = (bool)$user['info_checked_by_admin'];
    }
    return $user;
}

// ============================================================================
// 사용자 조회 함수
// ============================================================================

/**
 * 사용자 ID로 사용자 찾기 (DB-only)
 */
function getUserById($userId) {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            // info_updated 관련 컬럼 존재 여부 확인
            $checkInfoUpdated = $pdo->query("
                SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'seller_profiles' 
                AND COLUMN_NAME = 'info_updated'
            ");
            $infoUpdatedExists = $checkInfoUpdated->fetchColumn() > 0;
            
            // 컬럼이 있으면 포함, 없으면 제외
            $infoUpdatedFields = $infoUpdatedExists 
                ? "sp.info_updated AS info_updated,
                   sp.info_updated_at AS info_updated_at,
                   sp.info_checked_by_admin AS info_checked_by_admin,
                   sp.info_checked_at AS info_checked_at,"
                : "NULL AS info_updated,
                   NULL AS info_updated_at,
                   NULL AS info_checked_by_admin,
                   NULL AS info_checked_at,";
            
            // A안: users(공통) + 역할별 프로필(seller_profiles/admin_profiles)
            // 기존 코드 호환을 위해 seller 관련 필드는 COALESCE로 내려준다.
            $stmt = $pdo->prepare("
                SELECT
                    u.*,
                    COALESCE(sp.seller_approved, u.seller_approved) AS seller_approved,
                    COALESCE(sp.approval_status, u.approval_status) AS approval_status,
                    COALESCE(sp.approved_at, u.approved_at) AS approved_at,
                    COALESCE(sp.held_at, u.held_at) AS held_at,
                    COALESCE(sp.withdrawal_requested, u.withdrawal_requested) AS withdrawal_requested,
                    COALESCE(sp.withdrawal_requested_at, u.withdrawal_requested_at) AS withdrawal_requested_at,
                    COALESCE(sp.withdrawal_reason, u.withdrawal_reason) AS withdrawal_reason,
                    COALESCE(sp.withdrawal_completed, u.withdrawal_completed) AS withdrawal_completed,
                    COALESCE(sp.withdrawal_completed_at, u.withdrawal_completed_at) AS withdrawal_completed_at,
                    COALESCE(sp.scheduled_delete_date, u.scheduled_delete_date) AS scheduled_delete_date,
                    COALESCE(sp.scheduled_delete_processed, u.scheduled_delete_processed) AS scheduled_delete_processed,
                    COALESCE(sp.scheduled_delete_processed_at, u.scheduled_delete_processed_at) AS scheduled_delete_processed_at,
                    COALESCE(sp.postal_code, u.postal_code) AS postal_code,
                    COALESCE(sp.address, u.address) AS address,
                    COALESCE(sp.address_detail, u.address_detail) AS address_detail,
                    COALESCE(sp.business_number, u.business_number) AS business_number,
                    COALESCE(sp.company_name, u.company_name) AS company_name,
                    COALESCE(sp.company_representative, u.company_representative) AS company_representative,
                    COALESCE(sp.business_type, u.business_type) AS business_type,
                    COALESCE(sp.business_item, u.business_item) AS business_item,
                    COALESCE(sp.business_license_image, u.business_license_image) AS business_license_image,
                    COALESCE(sp.permissions, u.permissions) AS permissions,
                    COALESCE(sp.permissions_updated_at, u.permissions_updated_at) AS permissions_updated_at,
                    " . $infoUpdatedFields . "
                    ap.created_by AS admin_created_by,
                    ap.memo AS admin_memo
                FROM users u
                LEFT JOIN seller_profiles sp ON sp.user_id = u.user_id
                LEFT JOIN admin_profiles ap ON ap.user_id = u.user_id
                WHERE u.user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                return normalizeUserData($user);
            }
        } catch (PDOException $e) {
            error_log("getUserById error: " . $e->getMessage());
        }
    }
    
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
    
    return null;
}

/**
 * 모든 사용자 데이터 읽기 (일반회원, 관리자, 판매자 통합)
 * 주의: 통합 검색이 필요한 경우에만 사용
 */
function getUsersData() {
    $allUsers = [];
    
    // A안: users 테이블에서 모든 역할 조회 (seller/admin/sub_admin 포함)
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            // info_updated 관련 컬럼 존재 여부 확인
            $checkInfoUpdated = $pdo->query("
                SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'seller_profiles' 
                AND COLUMN_NAME = 'info_updated'
            ");
            $infoUpdatedExists = $checkInfoUpdated->fetchColumn() > 0;
            
            // 컬럼이 있으면 포함, 없으면 제외
            $infoUpdatedFields = $infoUpdatedExists 
                ? "sp.info_updated AS info_updated,
                   sp.info_updated_at AS info_updated_at,
                   sp.info_checked_by_admin AS info_checked_by_admin,
                   sp.info_checked_at AS info_checked_at,"
                : "NULL AS info_updated,
                   NULL AS info_updated_at,
                   NULL AS info_checked_by_admin,
                   NULL AS info_checked_at,";
            
            $stmt = $pdo->query("
                SELECT
                    u.*,
                    COALESCE(sp.seller_approved, u.seller_approved) AS seller_approved,
                    COALESCE(sp.approval_status, u.approval_status) AS approval_status,
                    COALESCE(sp.approved_at, u.approved_at) AS approved_at,
                    COALESCE(sp.held_at, u.held_at) AS held_at,
                    COALESCE(sp.withdrawal_requested, u.withdrawal_requested) AS withdrawal_requested,
                    COALESCE(sp.withdrawal_requested_at, u.withdrawal_requested_at) AS withdrawal_requested_at,
                    COALESCE(sp.withdrawal_reason, u.withdrawal_reason) AS withdrawal_reason,
                    COALESCE(sp.withdrawal_completed, u.withdrawal_completed) AS withdrawal_completed,
                    COALESCE(sp.withdrawal_completed_at, u.withdrawal_completed_at) AS withdrawal_completed_at,
                    COALESCE(sp.scheduled_delete_date, u.scheduled_delete_date) AS scheduled_delete_date,
                    COALESCE(sp.scheduled_delete_processed, u.scheduled_delete_processed) AS scheduled_delete_processed,
                    COALESCE(sp.scheduled_delete_processed_at, u.scheduled_delete_processed_at) AS scheduled_delete_processed_at,
                    COALESCE(sp.postal_code, u.postal_code) AS postal_code,
                    COALESCE(sp.address, u.address) AS address,
                    COALESCE(sp.address_detail, u.address_detail) AS address_detail,
                    COALESCE(sp.business_number, u.business_number) AS business_number,
                    COALESCE(sp.company_name, u.company_name) AS company_name,
                    COALESCE(sp.company_representative, u.company_representative) AS company_representative,
                    COALESCE(sp.business_type, u.business_type) AS business_type,
                    COALESCE(sp.business_item, u.business_item) AS business_item,
                    COALESCE(sp.business_license_image, u.business_license_image) AS business_license_image,
                    COALESCE(sp.permissions, u.permissions) AS permissions,
                    COALESCE(sp.permissions_updated_at, u.permissions_updated_at) AS permissions_updated_at,
                    " . $infoUpdatedFields . "
                    ap.created_by AS admin_created_by,
                    ap.memo AS admin_memo
                FROM users u
                LEFT JOIN seller_profiles sp ON sp.user_id = u.user_id
                LEFT JOIN admin_profiles ap ON ap.user_id = u.user_id
            ");
            $dbUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($dbUsers as $user) {
                $allUsers[] = normalizeUserData($user);
            }
        } catch (PDOException $e) {
            error_log("getUsersData DB error: " . $e->getMessage());
        }
    }
    
    return ['users' => $allUsers];
}

// saveUsersData() 제거 (DB-only)

// ============================================================================
// 인증 관련 함수
// ============================================================================

/**
 * 사용자 로그인
 */
function loginUser($userId) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['logged_in'] = true;
    
    // last_login 업데이트 (DB에 컬럼이 있는 경우)
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            // last_login 컬럼 존재 여부 확인
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'");
            if ($stmt->rowCount() > 0) {
                $pdo->prepare("
                    UPDATE users
                    SET last_login = NOW()
                    WHERE user_id = :user_id
                ")->execute([':user_id' => $userId]);
            }
        } catch (PDOException $e) {
            // 컬럼이 없거나 오류가 발생해도 로그인은 정상 진행
            error_log("loginUser: last_login 업데이트 실패 - " . $e->getMessage());
        }
    }
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
    // 세션이 시작되지 않았으면 시작
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        $currentSessionName = session_name();
        if (empty($currentSessionName) || $currentSessionName === 'PHPSESSID') {
            session_name('MVNO_SESSION');
        }
        session_start();
    }
    
    // 로그인 상태 확인
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        return null;
    }
    
    // user_id 확인
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return null;
    }
    
    // DB에서 사용자 정보 가져오기
    $user = getUserById($_SESSION['user_id']);
    
    // 사용자 정보를 찾을 수 없으면 세션 정리
    if (!$user) {
        // 세션에 user_id가 있지만 DB에서 찾을 수 없는 경우 세션 정리
        unset($_SESSION['logged_in']);
        unset($_SESSION['user_id']);
        return null;
    }
    
    return $user;
}

/**
 * 현재 사용자 ID 가져오기
 */
if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        return $_SESSION['user_id'] ?? null;
    }
}

/**
 * 로그인 여부 확인
 */
function isLoggedIn() {
    // 세션이 시작되지 않았으면 시작
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        $currentSessionName = session_name();
        if (empty($currentSessionName) || $currentSessionName === 'PHPSESSID') {
            session_name('MVNO_SESSION');
        }
        session_start();
    }
    
    // 로그인 상태 확인
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    // user_id가 없으면 로그인 상태가 아님
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        // 세션 정리
        unset($_SESSION['logged_in']);
        return false;
    }
    
    return true;
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

    // DB-only: DB 연결 실패 시 가입 실패 처리
    return null;
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
    
    // A안: 모든 역할은 users(DB)에 저장 + 역할별 프로필 테이블에 분리 저장
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'DB 연결에 실패했습니다.'];
    }

    try {
        $pdo->beginTransaction();

        // users(공통) 저장
        $stmt = $pdo->prepare("
            INSERT INTO users (
                user_id, email, name, password, phone, mobile, seller_name, role,
                seller_approved, approval_status, chat_consultation_url,
                created_at
            ) VALUES (
                :user_id, :email, :name, :password, :phone, :mobile, :seller_name, :role,
                :seller_approved, :approval_status, :chat_consultation_url,
                NOW()
            )
        ");

        $stmt->execute([
            ':user_id' => $newUser['user_id'],
            ':email' => $newUser['email'] ?? null,
            ':name' => $newUser['name'],
            ':password' => $newUser['password'],
            ':phone' => $newUser['phone'] ?? null,
            ':mobile' => $newUser['mobile'] ?? null,
            ':seller_name' => $newUser['seller_name'] ?? null,
            ':role' => $role,
            ':seller_approved' => ($role === 'seller') ? 0 : 1,
            ':approval_status' => ($role === 'seller') ? 'pending' : null,
            ':chat_consultation_url' => $newUser['chat_consultation_url'] ?? null
        ]);

        // seller_profiles 저장 (판매자 전용)
        if ($role === 'seller') {
            $sp = $pdo->prepare("
                INSERT INTO seller_profiles (
                    user_id,
                    seller_approved, approval_status,
                    postal_code, address, address_detail,
                    business_number, company_name, company_representative, business_type, business_item,
                    business_license_image,
                    permissions, permissions_updated_at,
                    created_at
                ) VALUES (
                    :user_id,
                    0, 'pending',
                    :postal_code, :address, :address_detail,
                    :business_number, :company_name, :company_representative, :business_type, :business_item,
                    :business_license_image,
                    :permissions, NULL,
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    postal_code = VALUES(postal_code),
                    address = VALUES(address),
                    address_detail = VALUES(address_detail),
                    business_number = VALUES(business_number),
                    company_name = VALUES(company_name),
                    company_representative = VALUES(company_representative),
                    business_type = VALUES(business_type),
                    business_item = VALUES(business_item),
                    business_license_image = VALUES(business_license_image),
                    permissions = COALESCE(VALUES(permissions), permissions),
                    updated_at = NOW()
            ");

            $permissionsJson = null;
            if (!empty($newUser['permissions']) && is_array($newUser['permissions'])) {
                $permissionsJson = json_encode($newUser['permissions'], JSON_UNESCAPED_UNICODE);
            }

            $sp->execute([
                ':user_id' => $newUser['user_id'],
                ':postal_code' => $newUser['postal_code'] ?? null,
                ':address' => $newUser['address'] ?? null,
                ':address_detail' => $newUser['address_detail'] ?? null,
                ':business_number' => $newUser['business_number'] ?? null,
                ':company_name' => $newUser['company_name'] ?? null,
                ':company_representative' => $newUser['company_representative'] ?? null,
                ':business_type' => $newUser['business_type'] ?? null,
                ':business_item' => $newUser['business_item'] ?? null,
                ':business_license_image' => $newUser['business_license_image'] ?? null,
                ':permissions' => $permissionsJson
            ]);
        }

        // admin_profiles 저장 (관리자 전용)
        if ($role === 'admin' || $role === 'sub_admin') {
            $ap = $pdo->prepare("
                INSERT INTO admin_profiles (user_id, created_by, memo, created_at)
                VALUES (:user_id, :created_by, :memo, NOW())
                ON DUPLICATE KEY UPDATE
                    memo = COALESCE(VALUES(memo), memo),
                    updated_at = NOW()
            ");
            $ap->execute([
                ':user_id' => $newUser['user_id'],
                ':created_by' => $newUser['created_by'] ?? null,
                ':memo' => $newUser['memo'] ?? null
            ]);
        }

        $pdo->commit();

        $savedUser = getUserById($userId);
        return ['success' => true, 'user' => $savedUser];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("registerDirectUser DB error: " . $e->getMessage());
        return ['success' => false, 'message' => '회원가입 처리 중 오류가 발생했습니다.'];
    }
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

// saveSellersData/loadAndUpdateSeller 제거 (DB-only)

/**
 * 판매자 승인
 */
function approveSeller($userId) {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("
                UPDATE users
                SET seller_approved = 1,
                    approval_status = 'approved',
                    approved_at = NOW()
                WHERE user_id = :user_id AND role = 'seller'
            ")->execute([':user_id' => $userId]);

            $pdo->prepare("
                UPDATE seller_profiles
                SET seller_approved = 1,
                    approval_status = 'approved',
                    approved_at = NOW(),
                    updated_at = NOW()
                WHERE user_id = :user_id
            ")->execute([':user_id' => $userId]);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("approveSeller DB error: " . $e->getMessage());
        }
    }

    return false;
}

/**
 * 판매자 승인보류
 */
function holdSeller($userId) {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("
                UPDATE users
                SET seller_approved = 0,
                    approval_status = 'on_hold',
                    held_at = NOW()
                WHERE user_id = :user_id AND role = 'seller'
            ")->execute([':user_id' => $userId]);

            $pdo->prepare("
                UPDATE seller_profiles
                SET seller_approved = 0,
                    approval_status = 'on_hold',
                    held_at = NOW(),
                    updated_at = NOW()
                WHERE user_id = :user_id
            ")->execute([':user_id' => $userId]);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("holdSeller DB error: " . $e->getMessage());
        }
    }

    return false;
}

/**
 * 판매자 신청 거부
 */
function rejectSeller($userId) {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }

    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("
                UPDATE users
                SET seller_approved = 0,
                    approval_status = 'rejected',
                    updated_at = NOW()
                WHERE user_id = :user_id AND role = 'seller'
            ")->execute([':user_id' => $userId]);

            $pdo->prepare("
                UPDATE seller_profiles
                SET seller_approved = 0,
                    approval_status = 'rejected',
                    updated_at = NOW()
                WHERE user_id = :user_id
            ")->execute([':user_id' => $userId]);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('rejectSeller DB error: ' . $e->getMessage());
        }
    }

    return false;
}

/**
 * 판매자 승인 취소 (승인 -> 대기로 변경)
 */
function cancelSellerApproval($userId) {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }

    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("
                UPDATE users
                SET seller_approved = 0,
                    approval_status = 'pending',
                    approved_at = NULL,
                    held_at = NULL,
                    updated_at = NOW()
                WHERE user_id = :user_id AND role = 'seller'
            ")->execute([':user_id' => $userId]);

            $pdo->prepare("
                UPDATE seller_profiles
                SET seller_approved = 0,
                    approval_status = 'pending',
                    approved_at = NULL,
                    held_at = NULL,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ")->execute([':user_id' => $userId]);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('cancelSellerApproval DB error: ' . $e->getMessage());
        }
    }

    return false;
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

    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("
                UPDATE users
                SET withdrawal_requested = 1,
                    withdrawal_requested_at = NOW(),
                    withdrawal_reason = :reason,
                    approval_status = 'withdrawal_requested',
                    seller_approved = 0,
                    updated_at = NOW()
                WHERE user_id = :user_id AND role = 'seller'
            ")->execute([':user_id' => $userId, ':reason' => $reason]);

            $pdo->prepare("
                UPDATE seller_profiles
                SET withdrawal_requested = 1,
                    withdrawal_requested_at = NOW(),
                    withdrawal_reason = :reason,
                    approval_status = 'withdrawal_requested',
                    seller_approved = 0,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ")->execute([':user_id' => $userId, ':reason' => $reason]);

            // 탈퇴 요청 시 해당 판매자의 모든 상품을 판매종료 처리
            $pdo->prepare("
                UPDATE products
                SET status = 'inactive',
                    updated_at = NOW()
                WHERE seller_id = :seller_id 
                AND status != 'deleted'
            ")->execute([':seller_id' => $userId]);

            // 탈퇴 요청 시 주문 진행상황 처리
            // 접수(received/pending), 개통중(activating/processing), 보류(on_hold/rejected) 건만 취소 처리
            // 나머지(종료, 개통완료, 취소, 설치완료 등)는 진행상태 그대로 유지
            $pdo->prepare("
                UPDATE product_applications
                SET application_status = 'cancelled',
                    status_changed_at = NOW(),
                    updated_at = NOW()
                WHERE seller_id = :seller_id
                AND application_status IN ('pending', 'received', 'processing', 'activating', 'rejected', 'on_hold')
            ")->execute([':seller_id' => $userId]);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('requestSellerWithdrawal DB error: ' . $e->getMessage());
        }
    }

    return false;
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
    
    $pdo = getDBConnection();
    if (!$pdo) return false;

    try {
        $pdo->beginTransaction();

        // deleteDate가 없으면 자동으로 5년 후 날짜 설정 (개인정보보호법 준수)
        if (empty($deleteDate)) {
            $deleteDate = date('Y-m-d', strtotime('+5 years'));
        }

        // seller_profiles 업데이트 (탈퇴 완료 + 스케줄)
        $pdo->prepare("
            UPDATE seller_profiles
            SET withdrawal_completed = 1,
                withdrawal_completed_at = NOW(),
                approval_status = 'withdrawn',
                seller_approved = 0,
                scheduled_delete_date = :scheduled_delete_date,
                updated_at = NOW()
            WHERE user_id = :user_id
        ")->execute([
            ':user_id' => $userId,
            ':scheduled_delete_date' => $deleteDate
        ]);

        // 판매자의 모든 상품을 판매종료 처리
        $productStmt = $pdo->prepare("
            UPDATE products
            SET status = 'inactive',
                updated_at = NOW()
            WHERE seller_id = :user_id
            AND status = 'active'
        ");
        $productStmt->execute([':user_id' => $userId]);
        $deactivatedProducts = $productStmt->rowCount();
        
        if ($deactivatedProducts > 0) {
            error_log("completeSellerWithdrawal: 판매자 {$userId}의 {$deactivatedProducts}개 상품이 판매종료 처리되었습니다.");
        }

        // users 업데이트 (탈퇴 완료 처리, 5년 후 삭제 예정)
        // 개인정보는 5년 후 삭제 예정이므로 당장은 보존
        $pdo->prepare("
            UPDATE users
            SET seller_approved = 0,
                approval_status = 'withdrawn',
                withdrawal_completed = 1,
                withdrawal_completed_at = NOW(),
                scheduled_delete_date = :scheduled_delete_date,
                updated_at = NOW()
            WHERE user_id = :user_id AND role = 'seller'
        ")->execute([
            ':user_id' => $userId,
            ':scheduled_delete_date' => $deleteDate
        ]);

        $pdo->commit();
        
        error_log("completeSellerWithdrawal: 판매자 {$userId} 탈퇴 완료 처리. 개인정보 삭제 예정일: {$deleteDate}");

        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('completeSellerWithdrawal DB error: ' . $e->getMessage());
        return false;
    }
}

// processScheduledDeletions 제거 (DB-only)

/**
 * 판매자 탈퇴 요청 취소 (판매자가 취소하거나 관리자가 거부)
 */
function cancelSellerWithdrawal($userId) {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }
    
    $pdo = getDBConnection();
    if (!$pdo) return false;

    try {
        $pdo->beginTransaction();
        $pdo->prepare("
            UPDATE seller_profiles
            SET withdrawal_requested = 0,
                withdrawal_requested_at = NULL,
                withdrawal_reason = NULL,
                approval_status = 'approved',
                seller_approved = 1,
                updated_at = NOW()
            WHERE user_id = :user_id
        ")->execute([':user_id' => $userId]);

        $pdo->prepare("
            UPDATE users
            SET withdrawal_requested = 0,
                withdrawal_requested_at = NULL,
                withdrawal_reason = NULL,
                approval_status = 'approved',
                seller_approved = 1,
                updated_at = NOW()
            WHERE user_id = :user_id AND role = 'seller'
        ")->execute([':user_id' => $userId]);

        // 탈퇴 요청 반려 시 상품 상태는 그대로 유지 (inactive 상태 유지)
        // 탈퇴 신청 시 이미 판매종료된 상품은 반려 후에도 판매종료 상태로 유지

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('cancelSellerWithdrawal DB error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 탈퇴 요청 반려 (관리자가 탈퇴 요청을 거부하고 계정 복구)
 * cancelSellerWithdrawal와 동일하지만 의미적으로 구분
 */
function rejectWithdrawalRequest($userId) {
    return cancelSellerWithdrawal($userId);
}

/**
 * 5년 경과한 탈퇴자의 개인정보 삭제 처리
 * 개인정보보호법에 따라 탈퇴 후 5년 경과 시 개인정보 삭제
 * 
 * @return array 처리 결과 ['processed' => 처리된 건수, 'errors' => 오류 건수]
 */
function processScheduledDeletions() {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['processed' => 0, 'errors' => 1];
    }

    try {
        $pdo->beginTransaction();
        
        // 5년 경과한 탈퇴자 조회 (scheduled_delete_date가 오늘 이전인 경우)
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE role = 'seller'
            AND withdrawal_completed = 1
            AND scheduled_delete_date IS NOT NULL
            AND scheduled_delete_date <= :today
            AND scheduled_delete_processed = 0
        ");
        $stmt->execute([':today' => $today]);
        $usersToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $processedCount = 0;
        foreach ($usersToDelete as $user) {
            $userId = $user['user_id'];
            
            // 개인정보 삭제 (비식별화)
            $pdo->prepare("
                UPDATE users
                SET email = :email,
                    phone = NULL,
                    mobile = NULL,
                    postal_code = NULL,
                    address = NULL,
                    address_detail = NULL,
                    business_number = NULL,
                    company_name = NULL,
                    company_representative = NULL,
                    business_type = NULL,
                    business_item = NULL,
                    business_license_image = NULL,
                    scheduled_delete_processed = 1,
                    scheduled_delete_processed_at = NOW(),
                    updated_at = NOW()
                WHERE user_id = :user_id AND role = 'seller'
            ")->execute([
                ':user_id' => $userId,
                ':email' => 'deleted_' . $userId . '@deleted'
            ]);
            
            // seller_profiles 개인정보 삭제
            $pdo->prepare("
                UPDATE seller_profiles
                SET postal_code = NULL,
                    address = NULL,
                    address_detail = NULL,
                    business_number = NULL,
                    company_name = NULL,
                    company_representative = NULL,
                    business_type = NULL,
                    business_item = NULL,
                    business_license_image = NULL,
                    scheduled_delete_processed = 1,
                    scheduled_delete_processed_at = NOW(),
                    updated_at = NOW()
                WHERE user_id = :user_id
            ")->execute([':user_id' => $userId]);
            
            // 사업자등록증 이미지 파일 삭제
            $seller = getUserById($userId);
            $imageRelPath = $seller['business_license_image'] ?? null;
            if (!empty($imageRelPath)) {
                $imagePath = $_SERVER['DOCUMENT_ROOT'] . $imageRelPath;
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            
            $processedCount++;
        }
        
        $pdo->commit();
        
        if ($processedCount > 0) {
            error_log("processScheduledDeletions: {$processedCount}명의 탈퇴자 개인정보가 삭제 처리되었습니다.");
        }
        
        return ['processed' => $processedCount, 'errors' => 0];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('processScheduledDeletions DB error: ' . $e->getMessage());
        return ['processed' => 0, 'errors' => 1];
    }
}

/**
 * 판매자 권한 설정
 */
function setSellerPermissions($userId, $permissions) {
    $user = getUserById($userId);
    if (!$user || $user['role'] !== 'seller') {
        return false;
    }

    $allowedPermissions = ['mvno', 'mno', 'internet', 'mno-sim'];
    $validPermissions = [];
    foreach ((array)$permissions as $perm) {
        if (in_array($perm, $allowedPermissions, true)) {
            $validPermissions[] = $perm;
        }
    }

    $pdo = getDBConnection();
    if (!$pdo) return false;

    try {
        $json = json_encode(array_values(array_unique($validPermissions)), JSON_UNESCAPED_UNICODE);
        $pdo->beginTransaction();

        $pdo->prepare("
            INSERT INTO seller_profiles (user_id, permissions, permissions_updated_at, created_at)
            VALUES (:user_id, :permissions_insert, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                permissions = :permissions_update,
                permissions_updated_at = NOW(),
                updated_at = NOW()
        ")->execute([
            ':user_id' => $userId,
            ':permissions_insert' => $json,
            ':permissions_update' => $json
        ]);

        // users 테이블에도 동기화(기존 화면 호환)
        $pdo->prepare("
            UPDATE users
            SET permissions = :permissions,
                permissions_updated_at = NOW(),
                updated_at = NOW()
            WHERE user_id = :user_id AND role = 'seller'
        ")->execute([':user_id' => $userId, ':permissions' => $json]);

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('setSellerPermissions DB error: ' . $e->getMessage());
        return false;
    }
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
    
    return in_array($permission, $user['permissions'], true);
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







