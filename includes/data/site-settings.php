<?php
/**
 * 사이트 설정 로드/저장 유틸
 * - 관리자 > 설정 > 사이트설정에서 편집
 * - 공개 영역(footer/header 등)에서 표시
 */

/**
 * 이용약관 템플릿 내용 가져오기
 * @return string
 */
function getTermsOfServiceContent() {
    $templatePath = __DIR__ . '/../../terms/terms-of-service-template.html';
    if (file_exists($templatePath)) {
        return file_get_contents($templatePath);
    }
    return '<h1>이용약관</h1><p>이용약관 내용을 입력하세요.</p>';
}

/**
 * 개인정보처리방침 템플릿 내용 가져오기
 * @return string
 */
function getPrivacyPolicyContent() {
    $templatePath = __DIR__ . '/../../terms/privacy-policy-template.html';
    if (file_exists($templatePath)) {
        return file_get_contents($templatePath);
    }
    return '<h1>개인정보처리방침</h1><p>개인정보처리방침 내용을 입력하세요.</p>';
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
            'terms' => [
                'terms_of_service' => [
                    'text' => '이용약관',
                    'url' => '/MVNO/terms/view.php?type=terms_of_service',
                    'content' => getTermsOfServiceContent(),
                ],
                'privacy_policy' => [
                    'text' => '개인정보처리방침',
                    'url' => '/MVNO/terms/view.php?type=privacy_policy',
                    'content' => getPrivacyPolicyContent(),
                ],
                'information_security' => [
                    'text' => '정보보호현황',
                    'url' => '/MVNO/terms/view.php?type=information_security',
                    'content' => '<h1>정보보호현황</h1><p>정보보호현황 내용을 입력하세요.</p>',
                ],
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




















