<?php
/**
 * 리뷰 작성 권한 설정 파일
 * 관리자가 설정할 수 있는 리뷰 작성 권한 관련 설정
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

// 리뷰 작성 권한 설정
// 리뷰를 작성할 수 있는 진행상황 목록
$review_settings = [
    // 리뷰 작성 가능한 진행상황 목록
    // 가능한 값: 'received', 'activating', 'on_hold', 'cancelled', 'activation_completed', 'installation_completed', 'closed'
    // 인터넷의 경우: 'activating'(개통중), 'installation_completed'/'completed'(설치완료), 'closed'/'terminated'(종료)
    'allowed_statuses' => ['activation_completed', 'installation_completed', 'closed'], // 개통완료, 설치완료, 종료 상태에서 리뷰 작성 가능
];

/**
 * 특정 진행상황에서 리뷰 작성이 가능한지 확인
 * @param string $application_status 진행상황 (DB에는 영어로 저장됨: received, activating, on_hold, cancelled, activation_completed, installation_completed, closed)
 * @return bool 리뷰 작성 가능 여부
 */
function canWriteReview($application_status) {
    if (empty($application_status)) {
        return false;
    }
    
    // DB에서 설정 읽기 (하드코딩 제거 - DB에서만 읽기)
    require_once __DIR__ . '/db-config.php';
    $pdo = getDBConnection();
    
    if (!$pdo) {
        error_log("canWriteReview: DB 연결 실패 - 리뷰 작성 불가");
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'review_allowed_statuses' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row || empty($row['setting_value'])) {
            // DB에 설정이 없으면 리뷰 작성 불가
            error_log("canWriteReview: DB에 설정이 없음 - 리뷰 작성 불가");
            return false;
        }
        
        $decoded = json_decode($row['setting_value'], true);
        if (!is_array($decoded) || empty($decoded)) {
            // JSON 파싱 실패 또는 빈 배열이면 리뷰 작성 불가
            error_log("canWriteReview: JSON 파싱 실패 또는 빈 배열 - " . $row['setting_value']);
            return false;
        }
        
        $allowedStatuses = $decoded;
    } catch (PDOException $e) {
        error_log("canWriteReview DB 오류: " . $e->getMessage() . " - 리뷰 작성 불가");
        return false;
    }
    
    // 상태값 정규화 (소문자로 변환하여 비교)
    $normalizedStatus = strtolower(trim($application_status));
    
    // pending -> received 변환
    if ($normalizedStatus === 'pending') {
        $normalizedStatus = 'received';
    }
    
    // 소문자로 변환된 배열 생성 (비교용)
    $allowedStatusesLower = array_map('strtolower', $allowedStatuses);
    
    // 소문자 변환된 상태값과 비교
    $result = in_array($normalizedStatus, $allowedStatusesLower);
    
    // 디버깅 로그 (필요시 주석 해제)
    // error_log("canWriteReview: status=$application_status, normalized=$normalizedStatus, allowed=" . json_encode($allowedStatusesLower) . ", result=" . ($result ? 'true' : 'false'));
    
    return $result;
}