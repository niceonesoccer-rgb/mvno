<?php
/**
 * 사이트 설정 로드/저장 유틸
 * - 관리자 > 설정 > 사이트설정에서 편집
 * - 공개 영역(footer/header 등)에서 표시
 */

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
    $defaults = getDefaultSiteSettings();
    // 실서비스: DB(app_settings) 사용
    require_once __DIR__ . '/app-settings.php';
    return getAppSettings('site', $defaults);
}

/**
 * 사이트 설정 저장
 * @param array $settings
 * @return bool
 */
function saveSiteSettings($settings) {
    require_once __DIR__ . '/app-settings.php';
    $updatedBy = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
    return saveAppSettings('site', $settings, $updatedBy);
}












