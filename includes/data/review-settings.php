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
    'allowed_statuses' => ['received', 'activating', 'activation_completed', 'installation_completed', 'closed'], // 접수, 개통중, 개통완료, 설치완료, 종료 상태에서 리뷰 작성 가능
];

/**
 * 특정 진행상황에서 리뷰 작성이 가능한지 확인
 * @param string $application_status 진행상황 (DB에는 영어로 저장됨: received, activating, on_hold, cancelled, activation_completed, installation_completed, closed)
 * @return bool 리뷰 작성 가능 여부
 */
function canWriteReview($application_status) {
    global $review_settings;
    
    if (empty($application_status)) {
        return false;
    }
    
    // 상태값 정규화 (소문자로 변환하여 비교)
    $normalizedStatus = strtolower(trim($application_status));
    
    // pending -> received 변환
    if ($normalizedStatus === 'pending') {
        $normalizedStatus = 'received';
    }
    
    // 허용된 상태 목록 가져오기
    $allowedStatuses = $review_settings['allowed_statuses'] ?? ['activation_completed', 'installation_completed', 'closed'];
    
    // 소문자로 변환된 배열 생성 (비교용)
    $allowedStatusesLower = array_map('strtolower', $allowedStatuses);
    
    // 소문자 변환된 상태값과 비교
    return in_array($normalizedStatus, $allowedStatusesLower);
}




















