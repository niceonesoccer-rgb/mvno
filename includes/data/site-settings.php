<?php
/**
 * 사이트 설정 로드/저장 유틸
 * - 관리자 > 설정 > 사이트설정에서 편집
 * - 공개 영역(footer/header 등)에서 표시
 */

/**
 * 사이트 설정 JSON 파일 경로
 * @return string
 */
function getSiteSettingsFilePath() {
    return __DIR__ . '/site-settings.json';
}

/**
 * 기본 사이트 설정
 * @return array
 */
function getDefaultSiteSettings() {
    return [
        'site' => [
            'name_ko' => '유심킹',
            'name_en' => 'usimking',
            'tagline' => '알뜰요금의 리더',
        ],
        'footer' => [
            'company_name' => '(주)유심킹',
            'business_number' => '',
            'mail_order_number' => '',
            'address' => '',
            'email' => '',
            'phone' => '',
            'kakao' => '@유심킹',
            'cs_notice' => '',
            'hours' => [
                'weekday' => '월~금',
                'hours' => '10:00 ~ 18:00',
                'lunch' => '점심시간 : 12:00 ~ 13:30 (주말, 공휴일 제외)',
            ],
        ],
    ];
}

/**
 * 사이트 설정 로드
 * @return array
 */
function getSiteSettings() {
    $path = getSiteSettingsFilePath();
    $defaults = getDefaultSiteSettings();

    if (!file_exists($path)) {
        return $defaults;
    }

    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return $defaults;
    }

    // 얕은 merge (필수 키 누락 대비)
    return array_replace_recursive($defaults, $data);
}

/**
 * 사이트 설정 저장
 * @param array $settings
 * @return bool
 */
function saveSiteSettings($settings) {
    $path = getSiteSettingsFilePath();
    $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }
    return file_put_contents($path, $json) !== false;
}

