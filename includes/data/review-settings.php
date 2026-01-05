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
    'allowed_statuses' => ['activation_completed', 'installation_completed', 'closed'], // 개통중, 설치완료, 종료 상태에서 리뷰 작성 가능
];

/**
 * 특정 진행상황에서 리뷰 작성이 가능한지 확인
 * @param string $application_status 진행상황
 * @return bool 리뷰 작성 가능 여부
 */
function canWriteReview($application_status) {
    global $review_settings;
    
    // 상태 정규화
    $normalizedStatus = strtolower(trim($application_status ?? ''));
    if (empty($normalizedStatus) || $normalizedStatus === 'pending') {
        $normalizedStatus = 'received';
    }
    
    // 허용된 상태 목록 확인
    // 기본값: 개통중, 설치완료, 종료 상태
    $allowedStatuses = $review_settings['allowed_statuses'] ?? ['activating', 'processing', 'installation_completed', 'completed', 'closed', 'terminated'];
    
    return in_array($normalizedStatus, $allowedStatuses);
}




















