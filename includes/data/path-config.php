<?php
/**
 * 경로 설정 파일
 * 로컬/프로덕션 환경에 따라 자동으로 경로를 설정합니다.
 */

// 현재 스크립트의 실제 경로에서 기본 경로 계산
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// 환경 자동 감지
$isLocalhost = (
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', '::1') !== false
);

// 프로덕션 서버 감지
$isProduction = !$isLocalhost;

// 기본 경로 자동 감지
if ($isLocalhost) {
    // 로컬 환경: /MVNO/ 사용
    $basePath = '/MVNO';
} else {
    // 프로덕션 환경: 실제 경로 자동 감지
    // 스크립트 경로에서 기본 경로 추출
    if (preg_match('#^/([^/]+)/#', $scriptPath, $matches)) {
        $basePath = '/' . $matches[1];
    } elseif (preg_match('#^/([^/]+)/#', $requestUri, $matches)) {
        $basePath = '/' . $matches[1];
    } else {
        // 기본값: 루트 경로
        $basePath = '';
    }
    
    // ganadamobile.co.kr인 경우: 루트에 직접 설치
    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'ganadamobile.co.kr') !== false) {
        // 루트에 직접 설치된 경우 (mvno 폴더 없이)
        $basePath = '';
    }
}

// 상수 정의
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $basePath);
}

// 헬퍼 함수
function getBasePath() {
    return defined('BASE_PATH') ? BASE_PATH : '';
}

function getAssetPath($path) {
    if (empty($path)) {
        return '';
    }
    
    $base = getBasePath();
    
    // DB에 저장된 경로에서 /MVNO/ 제거 (프로덕션 호환성)
    // 여러 번 반복해서 모든 /MVNO/ 제거
    while (strpos($path, '/MVNO/') !== false) {
        $path = str_replace('/MVNO/', '/', $path);
    }
    // 경로 시작 부분의 /MVNO 제거
    if (strpos($path, '/MVNO') === 0) {
        $path = substr($path, 5); // '/MVNO' 제거
    }
    // MVNO/로 시작하는 경우도 처리
    if (strpos($path, 'MVNO/') === 0) {
        $path = substr($path, 5); // 'MVNO/' 제거
    }
    
    // 경로가 이미 /로 시작하면 그대로 사용
    if (strpos($path, '/') === 0) {
        return $base . $path;
    }
    return $base . '/' . ltrim($path, '/');
}

function getApiPath($path) {
    return getAssetPath($path);
}

function getUploadPath($path) {
    return getAssetPath($path);
}
