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

// 기본 경로 자동 감지 (로컬/프로덕션 모두 동일하게 처리)
$basePath = '';

// 제외할 디렉토리 목록 (이 디렉토리들은 basePath가 될 수 없음)
// 애플리케이션 디렉토리들도 제외 (웹 환경에서 루트에 설치된 경우)
// 주의: 'mvno'는 소문자로만 추가 (대문자 MVNO는 basePath가 될 수 있음)
$excludedDirs = ['admin', 'api', 'assets', 'includes', 'uploads', 'event', 'internets', 'mypage', 'terms', 'auth', 'seller', 'vendor', 'database', 'logs', 'docs', 'home', 'qna', 'notice', 'esim', 'mvno', 'mno', 'mno-sim'];

// 파일 확장자 목록 (파일명은 basePath가 될 수 없음)
$fileExtensions = ['php', 'html', 'htm', 'js', 'css', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'pdf', 'txt', 'xml', 'json'];

// 1. SCRIPT_NAME에서 기본 경로 추출 (가장 정확)
// 예: /MVNO/index.php -> /MVNO (로컬)
// 예: /mno-sim/mno-sim.php -> '' (웹, mno-sim은 애플리케이션 디렉토리)
// 예: /test-path-web.php -> '' (웹, 파일명은 basePath가 될 수 없음)
if (!empty($scriptPath) && $scriptPath !== '/index.php' && $scriptPath !== '/') {
    // 디렉토리가 있는 경우만 처리 (슬래시가 2개 이상)
    if (preg_match('#^/([^/]+)/#', $scriptPath, $matches)) {
        $firstDir = $matches[1];
        $firstDirLower = strtolower($firstDir);
        
        // 대문자 MVNO는 basePath가 될 수 있음 (로컬 환경)
        if ($firstDir === 'MVNO') {
            $basePath = '/' . $firstDir;
        }
        // 다른 디렉토리는 제외 목록에 없으면 basePath로 설정
        elseif (!in_array($firstDirLower, $excludedDirs)) {
            $basePath = '/' . $firstDir;
        }
    }
    // 루트에 직접 있는 파일은 basePath를 설정하지 않음
}

// 2. SCRIPT_NAME에서 추출 실패 시 REQUEST_URI에서 추출
// 예: /MVNO/ -> /MVNO (로컬)
// 예: /mno-sim/mno-sim.php -> '' (웹)
// 예: /test-path-web.php -> '' (웹, 파일명은 basePath가 될 수 없음)
if (empty($basePath) && !empty($requestUri)) {
    // 쿼리 문자열 제거
    $uriPath = parse_url($requestUri, PHP_URL_PATH);
    if ($uriPath && $uriPath !== '/' && $uriPath !== '/index.php') {
        // 디렉토리가 있는 경우만 처리 (슬래시가 2개 이상)
        // 파일명만 있는 경우는 제외 (확장자가 있거나 슬래시가 하나만 있음)
        if (preg_match('#^/([^/]+)/#', $uriPath, $matches)) {
            $firstDir = $matches[1];
            $firstDirLower = strtolower($firstDir);
            
            // 파일 확장자가 있는지 확인 (파일명은 basePath가 될 수 없음)
            $hasExtension = preg_match('/\.(php|html|htm|js|css|jpg|jpeg|png|gif|svg|webp|pdf|txt|xml|json)$/i', $firstDir);
            
            if (!$hasExtension) {
                // 대문자 MVNO는 basePath가 될 수 있음 (로컬 환경)
                if ($firstDir === 'MVNO') {
                    $basePath = '/' . $firstDir;
                }
                // 다른 디렉토리는 제외 목록에 없으면 basePath로 설정
                elseif (!in_array($firstDirLower, $excludedDirs)) {
                    $basePath = '/' . $firstDir;
                }
            }
        }
        // 루트에 직접 있는 파일은 basePath를 설정하지 않음
    }
}

// 3. 둘 다 실패하면 루트 설치로 간주 (basePath = '')

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
    
    // 경로 정규화: 중복된 슬래시 제거 및 경로 정리
    $path = preg_replace('#/+#', '/', $path);
    
    // 전체 URL인 경우 HTTPS로 강제 변환 (Mixed Content 방지)
    if (preg_match('/^https?:\/\//', $path)) {
        // HTTP를 HTTPS로 변환
        if (preg_match('/^http:\/\//', $path)) {
            $path = str_replace('http://', 'https://', $path);
        }
        return $path;
    }
    
    // DB에 저장된 경로에서 하드코딩된 /MVNO/ 제거 (웹/로컬 호환성)
    // 여러 번 반복해서 모든 /MVNO/ 제거
    while (strpos($path, '/MVNO/') !== false) {
        $path = str_replace('/MVNO/', '/', $path);
    }
    // 경로 시작 부분의 /MVNO 제거
    if (strpos($path, '/MVNO') === 0 && strlen($path) > 5) {
        // /MVNO 다음이 슬래시가 아닌 경우에만 제거 (예: /MVNOuploads는 제거하지 않음)
        if ($path[5] === '/' || strlen($path) === 5) {
            $path = substr($path, 5); // '/MVNO' 제거
        }
    }
    // MVNO/로 시작하는 경우도 처리
    if (strpos($path, 'MVNO/') === 0) {
        $path = substr($path, 5); // 'MVNO/' 제거
    }
    
    // 경로 정규화: 앞뒤 공백 제거 및 중복 슬래시 정리
    $path = trim($path);
    $path = preg_replace('#/+#', '/', $path);
    
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
