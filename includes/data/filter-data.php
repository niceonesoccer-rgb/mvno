<?php
/**
 * 필터 데이터 관리
 * 알뜰폰(MVNO)과 통신사폰(MNO)의 필터를 별도로 관리
 * DB-only: app_settings(namespace='filters')
 */

require_once __DIR__ . '/app-settings.php';

function getDefaultFilters(): array {
    return [
        'mno' => [
            '#갤럭시',
            '#아이폰',
            '#공짜',
            '#256GB',
            '#512GB'
        ],
        'mvno' => [
            '#베스트 요금제',
            '#만원 미만',
            '#장기 할인',
            '#100원'
        ],
    ];
}

/**
 * 통신사폰(MNO) 필터 목록 가져오기
 * 관리자 페이지에서 설정한 필터를 반환
 * @return array 필터 목록 배열
 */
function getMnoFilters() {
    $defaults = getDefaultFilters();
    $settings = getAppSettings('filters', $defaults);
    $mno = $settings['mno'] ?? $defaults['mno'];
    return is_array($mno) ? array_values($mno) : $defaults['mno'];
}

/**
 * 알뜰폰(MVNO) 필터 목록 가져오기
 * 관리자 페이지에서 설정한 필터를 반환
 * @return array 필터 목록 배열
 */
function getMvnoFilters() {
    $defaults = getDefaultFilters();
    $settings = getAppSettings('filters', $defaults);
    $mvno = $settings['mvno'] ?? $defaults['mvno'];
    return is_array($mvno) ? array_values($mvno) : $defaults['mvno'];
}

/**
 * 통신사폰(MNO) 필터 저장하기
 * 관리자 페이지에서 호출
 * @param array $filters 저장할 필터 목록
 * @return bool 성공 여부
 */
function saveMnoFilters($filters) {
    if (!is_array($filters)) {
        return false;
    }
    
    // 각 필터에 해시태그가 없으면 추가
    $filters = array_map(function($filter) {
        $filter = trim($filter);
        if ($filter && substr($filter, 0, 1) !== '#') {
            return '#' . $filter;
        }
        return $filter;
    }, $filters);
    
    // 빈 값 제거
    $filters = array_filter($filters, function($filter) {
        return !empty(trim($filter));
    });

    $defaults = getDefaultFilters();
    $settings = getAppSettings('filters', $defaults);
    $settings['mno'] = array_values($filters);
    $updatedBy = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
    return saveAppSettings('filters', $settings, $updatedBy);
}

/**
 * 알뜰폰(MVNO) 필터 저장하기
 * 관리자 페이지에서 호출
 * @param array $filters 저장할 필터 목록
 * @return bool 성공 여부
 */
function saveMvnoFilters($filters) {
    if (!is_array($filters)) {
        return false;
    }
    
    // 각 필터에 해시태그가 없으면 추가
    $filters = array_map(function($filter) {
        $filter = trim($filter);
        if ($filter && substr($filter, 0, 1) !== '#') {
            return '#' . $filter;
        }
        return $filter;
    }, $filters);
    
    // 빈 값 제거
    $filters = array_filter($filters, function($filter) {
        return !empty(trim($filter));
    });

    $defaults = getDefaultFilters();
    $settings = getAppSettings('filters', $defaults);
    $settings['mvno'] = array_values($filters);
    $updatedBy = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
    return saveAppSettings('filters', $settings, $updatedBy);
}

