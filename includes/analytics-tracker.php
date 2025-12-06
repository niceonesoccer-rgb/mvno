<?php
/**
 * 통계 추적 스크립트
 * 각 페이지에 포함시켜 사용
 */

require_once __DIR__ . '/data/analytics-functions.php';

// 현재 페이지 경로
$currentPage = $_SERVER['REQUEST_URI'] ?? '/';

// 페이지뷰 기록
trackPageView($currentPage);

// 활성 세션 업데이트 (실시간 접속자 추적)
updateActiveSession($currentPage);

