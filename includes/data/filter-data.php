<?php
/**
 * 필터 데이터 관리
 * 알뜰폰(MVNO)과 통신사폰(MNO)의 필터를 별도로 관리
 */

/**
 * 통신사폰(MNO) 필터 목록 가져오기
 * 관리자 페이지에서 설정한 필터를 반환
 * @return array 필터 목록 배열
 */
function getMnoFilters() {
    // TODO: 나중에 DB에서 관리자 설정값을 가져오도록 변경
    // 현재는 기본값 반환
    $default_filters = [
        '#갤럭시',
        '#아이폰',
        '#공짜',
        '#256GB',
        '#512GB'
    ];
    
    // 관리자 설정 파일에서 읽기 시도 (없으면 기본값 사용)
    $filter_file = __DIR__ . '/../../data/mno-filters.json';
    if (file_exists($filter_file)) {
        $saved_filters = json_decode(file_get_contents($filter_file), true);
        if (is_array($saved_filters) && !empty($saved_filters)) {
            return $saved_filters;
        }
    }
    
    return $default_filters;
}

/**
 * 알뜰폰(MVNO) 필터 목록 가져오기
 * 관리자 페이지에서 설정한 필터를 반환
 * @return array 필터 목록 배열
 */
function getMvnoFilters() {
    // TODO: 나중에 DB에서 관리자 설정값을 가져오도록 변경
    // 현재는 기본값 반환
    $default_filters = [
        '#베스트 요금제',
        '#만원 미만',
        '#장기 할인',
        '#100원'
    ];
    
    // 관리자 설정 파일에서 읽기 시도 (없으면 기본값 사용)
    $filter_file = __DIR__ . '/../../data/mvno-filters.json';
    if (file_exists($filter_file)) {
        $saved_filters = json_decode(file_get_contents($filter_file), true);
        if (is_array($saved_filters) && !empty($saved_filters)) {
            return $saved_filters;
        }
    }
    
    return $default_filters;
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
    
    $data_dir = __DIR__ . '/../../data';
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    
    $filter_file = $data_dir . '/mno-filters.json';
    $result = file_put_contents($filter_file, json_encode(array_values($filters), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    return $result !== false;
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
    
    $data_dir = __DIR__ . '/../../data';
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    
    $filter_file = $data_dir . '/mvno-filters.json';
    $result = file_put_contents($filter_file, json_encode(array_values($filters), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    return $result !== false;
}

