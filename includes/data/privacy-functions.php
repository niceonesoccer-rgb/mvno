<?php
/**
 * 개인정보 설정 관련 함수
 */

/**
 * 개인정보 설정을 가져옵니다.
 * @return array 개인정보 설정 배열
 */
function getPrivacySettings() {
    $privacySettingsFile = __DIR__ . '/privacy-settings.json';
    
    if (file_exists($privacySettingsFile)) {
        $jsonContent = file_get_contents($privacySettingsFile);
        $settings = json_decode($jsonContent, true);
        if ($settings) {
            return $settings;
        }
    }
    
    // 기본값 반환
    return [
        'purpose' => [
            'title' => '개인정보 수집 및 이용목적',
            'content' => '<div class="privacy-content-text"><p><strong>1. 개인정보의 수집 및 이용목적</strong></p><p>&lt;유심킹&gt;(\'http://www.dtmall.net\' 이하 \'회사\') 은(는) 다음의 목적을 위하여 개인정보를 처리하고 있으며, 다음의 목적 이외의 용도로는 이용하지 않습니다.</p></div>'
        ],
        'items' => [
            'title' => '개인정보 수집하는 항목',
            'content' => '<div class="privacy-content-text"><p><strong>2. 개인정보 수집항목 및 수집방법</strong></p></div>'
        ],
        'period' => [
            'title' => '개인정보 보유 및 이용기간',
            'content' => '<div class="privacy-content-text"><p><strong>3. 개인정보의 보유 및 이용기간</strong></p></div>'
        ],
        'thirdParty' => [
            'title' => '개인정보 제3자 제공',
            'content' => '<div class="privacy-content-text"><p><strong>유심킹 개인정보 제3자 제공에 동의</strong></p></div>'
        ]
    ];
}

/**
 * 개인정보 설정을 JavaScript 변수로 출력합니다.
 * @param string $varName JavaScript 변수명
 */
function outputPrivacySettingsAsJS($varName = 'privacyContents') {
    $settings = getPrivacySettings();
    echo '<script>';
    echo "const {$varName} = " . json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
    echo '</script>';
}

