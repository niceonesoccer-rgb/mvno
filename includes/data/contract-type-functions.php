<?php
/**
 * 가입형태 표시 관련 공통 함수
 * 
 * 저장값은 영문 코드(new, mnp, change)로 통일
 * 표시는 역할에 따라 다르게:
 * - 고객: "신규가입", "번호이동", "기기변경"
 * - 판매자/관리자: "신규", "번이", "기변"
 */

/**
 * 가입형태 값을 정규화 (영문 코드로 변환)
 * @param string $value 저장된 가입형태 값
 * @return string 정규화된 영문 코드 (new, mnp, change)
 */
function normalizeContractType($value) {
    if (empty($value)) {
        return '';
    }
    
    $value = trim($value);
    
    // 이미 영문 코드인 경우
    if (in_array($value, ['new', 'port', 'mnp', 'change'])) {
        // port는 mnp로 통일 (번호이동은 mnp가 표준)
        return $value === 'port' ? 'mnp' : $value;
    }
    
    // 한글값을 영문 코드로 변환
    if (in_array($value, ['신규', '신규가입'])) {
        return 'new';
    }
    if (in_array($value, ['번호이동', '번이', 'MNP', 'PORT'])) {
        return 'mnp';
    }
    if (in_array($value, ['기기변경', '기변'])) {
        return 'change';
    }
    
    // 알 수 없는 값은 그대로 반환
    return $value;
}

/**
 * 고객용 가입형태 표시 (신규가입, 번호이동, 기기변경)
 * @param array|string $app application 데이터 또는 additional_info 문자열 또는 subscription_type 값
 * @return string 표시할 가입형태
 */
function getContractTypeForCustomer($app) {
    $contractType = '';
    
    // 배열인 경우 (application 데이터)
    if (is_array($app)) {
        $additionalInfo = $app['additional_info'] ?? '';
        if ($additionalInfo) {
            if (is_string($additionalInfo)) {
                $info = json_decode($additionalInfo, true);
            } else {
                $info = $additionalInfo;
            }
            if (is_array($info)) {
                $contractType = $info['subscription_type'] ?? $info['contract_type'] ?? '';
            }
        }
    } 
    // 문자열인 경우 (subscription_type 값 직접 전달)
    else {
        $contractType = $app;
    }
    
    // 정규화
    $normalized = normalizeContractType($contractType);
    
    // 고객용 표시
    $customerLabels = [
        'new' => '신규가입',
        'mnp' => '번호이동',
        'port' => '번호이동', // 하위 호환성
        'change' => '기기변경'
    ];
    
    return $customerLabels[$normalized] ?? ($contractType ?: '-');
}

/**
 * 판매자/관리자용 가입형태 표시 (신규, 번이, 기변)
 * @param array|string $app application 데이터 또는 additional_info 문자열 또는 subscription_type 값
 * @return string 표시할 가입형태
 */
function getContractTypeForAdmin($app) {
    $contractType = '';
    
    // 배열인 경우 (application 데이터)
    if (is_array($app)) {
        $additionalInfo = $app['additional_info'] ?? '';
        if ($additionalInfo) {
            if (is_string($additionalInfo)) {
                $info = json_decode($additionalInfo, true);
            } else {
                $info = $additionalInfo;
            }
            if (is_array($info)) {
                $contractType = $info['subscription_type'] ?? $info['contract_type'] ?? '';
            }
        }
    } 
    // 문자열인 경우 (subscription_type 값 직접 전달)
    else {
        $contractType = $app;
    }
    
    // 정규화
    $normalized = normalizeContractType($contractType);
    
    // 판매자/관리자용 표시
    $adminLabels = [
        'new' => '신규',
        'mnp' => '번이',
        'port' => '번이', // 하위 호환성
        'change' => '기변'
    ];
    
    return $adminLabels[$normalized] ?? ($contractType ?: '-');
}






