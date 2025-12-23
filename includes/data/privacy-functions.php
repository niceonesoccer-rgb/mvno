<?php
/**
 * 개인정보 설정 관련 함수
 */

/**
 * 개인정보 설정을 가져옵니다.
 * @return array 개인정보 설정 배열
 */
function getPrivacySettings() {
    require_once __DIR__ . '/app-settings.php';
    require_once __DIR__ . '/db-config.php';
    $defaults = [
        'purpose' => [
            'title' => '개인정보 수집 및 이용목적',
            'content' => '<div class="privacy-content-text"><p><strong>1. 개인정보의 수집 및 이용목적</strong></p><p>&lt;유심킹&gt;(\'http://www.dtmall.net\' 이하 \'회사\') 은(는) 다음의 목적을 위하여 개인정보를 처리하고 있으며, 다음의 목적 이외의 용도로는 이용하지 않습니다.</p></div>',
            'isRequired' => true
        ],
        'items' => [
            'title' => '개인정보 수집하는 항목',
            'content' => '<div class="privacy-content-text"><p><strong>2. 개인정보 수집항목 및 수집방법</strong></p></div>',
            'isRequired' => true
        ],
        'period' => [
            'title' => '개인정보 보유 및 이용기간',
            'content' => '<div class="privacy-content-text"><p><strong>3. 개인정보의 보유 및 이용기간</strong></p></div>',
            'isRequired' => true
        ],
        'thirdParty' => [
            'title' => '개인정보 제3자 제공',
            'content' => '<div class="privacy-content-text"><p><strong>유심킹 개인정보 제3자 제공에 동의</strong></p></div>',
            'isRequired' => true
        ],
        'serviceNotice' => [
            'title' => '서비스 이용 및 혜택 안내 알림',
            'content' => '<div class="privacy-content-text"><p>서비스 이용에 필요한 필수 알림입니다. 알림톡으로 발송됩니다.</p><ul><li>요금제 유지기간 만료 및 변경 안내</li><li>부가서비스 종료 및 이용 조건 변경 안내</li><li>가입 고객 대상 혜택·이벤트 안내</li></ul></div>',
            'isRequired' => true
        ],
        'marketing' => [
            'title' => '광고성 정보수신',
            'content' => '<div class="privacy-content-text"><p>광고성 정보를 받으시려면 아래 항목을 선택해주세요</p><ul><li>이메일 수신동의</li><li>SMS, SNS 수신동의</li><li>앱 푸시 수신동의</li></ul></div>',
            'isRequired' => false
        ]
    ];
    
    $settings = getAppSettings('privacy', $defaults);
    
    // DB에서 직접 읽어서 isVisible 값 명시적으로 적용
    // array_replace_recursive는 기본값을 우선하므로, DB 값을 직접 확인
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT json_value FROM app_settings WHERE namespace = :ns LIMIT 1');
            $stmt->execute([':ns' => 'privacy']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['json_value'])) {
                $val = $row['json_value'];
                $decoded = is_string($val) ? json_decode($val, true) : $val;
                if (is_array($decoded)) {
                    foreach ($decoded as $key => $dbValue) {
                        if (is_array($dbValue) && array_key_exists('isVisible', $dbValue)) {
                            $settings[$key]['isVisible'] = (bool)$dbValue['isVisible'];
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            error_log('getPrivacySettings DB error: ' . $e->getMessage());
        }
    }
    
    // 기본값 설정 (content 필드는 DB에 저장된 그대로 사용)
    foreach ($settings as $key => &$value) {
        // isRequired 기본값 설정
        if (!isset($value['isRequired'])) {
            $value['isRequired'] = ($key !== 'marketing');
        }
        // isVisible 기본값 설정
        if (!array_key_exists('isVisible', $value)) {
            $value['isVisible'] = true;
        }
        // content 필드는 DB에 저장된 그대로 사용 (검증이나 정리 로직 없음)
        // 숫자, 줄바꿈, 공백 등 모든 내용이 그대로 유지되어야 함
    }
    unset($value); // 참조 해제
    
    return $settings;
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

