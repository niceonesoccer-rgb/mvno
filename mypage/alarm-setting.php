<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 경로 설정 파일 먼저 로드
require_once '../includes/data/path-config.php';

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';
require_once '../includes/data/privacy-functions.php';

// 로그인 체크 - 로그인하지 않은 경우 회원가입 모달로 리다이렉트
if (!isLoggedIn()) {
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    // 로그인 모달이 있는 홈으로 리다이렉트 (모달 자동 열기)
    header('Location: ' . getAssetPath('/?show_login=1'));
    exit;
}

// 현재 사용자 정보 가져오기
$currentUser = getCurrentUser();
if (!$currentUser) {
    // 세션 정리 후 로그인 페이지로 리다이렉트
    if (isset($_SESSION['logged_in'])) {
        unset($_SESSION['logged_in']);
    }
    if (isset($_SESSION['user_id'])) {
        unset($_SESSION['user_id']);
    }
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . getAssetPath('/?show_login=1'));
    exit;
}

// 관리자 페이지에서 설정한 개인정보 설정 가져오기
$privacySettings = getPrivacySettings();
$serviceNoticeSettings = $privacySettings['serviceNotice'] ?? [];
$marketingSettings = $privacySettings['marketing'] ?? [];

// 관리자 페이지에서 설정한 개인정보 설정 가져오기
$privacySettings = getPrivacySettings();
$serviceNoticeSettings = $privacySettings['serviceNotice'] ?? [];
$marketingSettings = $privacySettings['marketing'] ?? [];

// 관리자 설정에서 제목과 내용 가져오기 (없으면 기본값 사용)
// 노출 여부 확인
$serviceNoticeIsVisible = $serviceNoticeSettings['isVisible'] ?? true;
$marketingIsVisible = $marketingSettings['isVisible'] ?? true;

$serviceNoticeTitle = htmlspecialchars($serviceNoticeSettings['title'] ?? '서비스 이용 및 혜택 안내 알림');
$serviceNoticeContent = $serviceNoticeSettings['content'] ?? '';
$serviceNoticeIsRequired = $serviceNoticeSettings['isRequired'] ?? true;

$marketingTitle = htmlspecialchars($marketingSettings['title'] ?? '광고성 정보수신');
$marketingContent = $marketingSettings['content'] ?? '';
$marketingIsRequired = $marketingSettings['isRequired'] ?? false;

// 서비스 이용 및 혜택 안내 알림의 하위 항목 추출 (HTML에서 li 태그 내용 추출)
$serviceNoticeItems = [];
if (!empty($serviceNoticeContent)) {
    // HTML에서 <li> 태그 내용 추출
    preg_match_all('/<li[^>]*>(.*?)<\/li>/i', $serviceNoticeContent, $matches);
    if (!empty($matches[1])) {
        $serviceNoticeItems = array_map('strip_tags', $matches[1]);
    }
}
// 기본값이 없으면 기본 항목 사용
if (empty($serviceNoticeItems)) {
    $serviceNoticeItems = [
        '요금제 유지기간 만료 및 변경 안내',
        '부가서비스 종료 및 이용 조건 변경 안내',
        '가입 고객 대상 혜택·이벤트 안내'
    ];
}

// 사용자 알림 설정 가져오기
// - DB에 개별 컬럼이 있으면 그 값을 우선 사용
// - 없으면 users.alarm_settings(JSON) 사용 (하위 호환)
$alarmSettings = [
    // 신규 필드명(화면 문구 기준) 우선 사용
    'service_notice_opt_in' =>
        isset($currentUser['service_notice_opt_in']) ? (bool)$currentUser['service_notice_opt_in'] :
        // 하위 호환(구 필드/JSON)
        (isset($currentUser['benefit_notification']) ? (bool)$currentUser['benefit_notification'] : (bool)($currentUser['alarm_settings']['service_notice_opt_in'] ?? $currentUser['alarm_settings']['benefit_notification'] ?? true)),

    'marketing_email_opt_in' =>
        isset($currentUser['marketing_email_opt_in']) ? (bool)$currentUser['marketing_email_opt_in'] :
        (isset($currentUser['advertising_email']) ? (bool)$currentUser['advertising_email'] : (bool)($currentUser['alarm_settings']['marketing_email_opt_in'] ?? $currentUser['alarm_settings']['advertising_email'] ?? false)),

    'marketing_sms_sns_opt_in' =>
        isset($currentUser['marketing_sms_sns_opt_in']) ? (bool)$currentUser['marketing_sms_sns_opt_in'] :
        ((isset($currentUser['advertising_sms']) ? (bool)$currentUser['advertising_sms'] : false) ||
         (isset($currentUser['advertising_kakao']) ? (bool)$currentUser['advertising_kakao'] : false) ||
         (bool)($currentUser['alarm_settings']['marketing_sms_sns_opt_in'] ?? false)),

    'marketing_push_opt_in' =>
        isset($currentUser['marketing_push_opt_in']) ? (bool)$currentUser['marketing_push_opt_in'] :
        (isset($currentUser['advertising_push']) ? (bool)$currentUser['advertising_push'] : (bool)($currentUser['alarm_settings']['marketing_push_opt_in'] ?? $currentUser['alarm_settings']['advertising_push'] ?? false)),
];

// 광고성 정보 수신동의(전체) 토글 값
$marketingOptIn =
    isset($currentUser['marketing_opt_in']) ? (bool)$currentUser['marketing_opt_in'] :
    ($alarmSettings['marketing_email_opt_in'] || $alarmSettings['marketing_sms_sns_opt_in'] || $alarmSettings['marketing_push_opt_in']);

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div style="width: 460px; margin: 0 auto; padding: 20px;" class="alarm-setting-container">
        <!-- 뒤로가기 버튼 및 제목 -->
        <div style="margin-bottom: 24px; padding: 20px 0;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <a href="<?php echo getAssetPath('/mypage/mypage.php'); ?>" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <h1 style="font-size: 20px; font-weight: bold; margin: 0; color: #212529;">알림 설정</h1>
            </div>
        </div>

        <!-- 서비스 이용 및 혜택 안내 알림(필수) 섹션 -->
        <?php if ($serviceNoticeIsVisible): ?>
        <div style="background-color: #ffffff; border-radius: 8px; padding: 20px; margin-bottom: 16px;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;">
                <div style="display: flex; align-items: flex-start; gap: 16px; flex: 1;">
                    <!-- 아이콘 -->
                    <div style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 10px; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>

                    <!-- 텍스트 -->
                    <div style="flex: 1;">
                        <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 6px 0; color: #212529;">
                            <?php echo $serviceNoticeTitle; ?> 
                            <span style="font-size: 12px; font-weight: 600; color: <?php echo $serviceNoticeIsRequired ? '#4f46e5' : '#6b7280'; ?>; vertical-align: middle;">
                                (<?php echo $serviceNoticeIsRequired ? '필수' : '선택'; ?>)
                            </span>
                        </h3>
                        <?php if (!empty($serviceNoticeItems)): ?>
                        <ul style="margin: 0; padding-left: 18px; color: #6b7280; font-size: 14px; line-height: 1.65;">
                            <?php foreach ($serviceNoticeItems as $item): ?>
                            <li><?php echo htmlspecialchars($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 토글 스위치 -->
                <label class="toggle-switch" style="position: relative; display: inline-block; width: 48px; height: 28px; flex-shrink: 0; cursor: pointer; margin-top: 4px;">
                    <input type="checkbox" id="serviceNoticeOptIn" class="toggle-input" <?php echo $alarmSettings['service_notice_opt_in'] ? 'checked' : ''; ?> onchange="handleBenefitToggle(this)">
                    <span class="toggle-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: <?php echo $alarmSettings['service_notice_opt_in'] ? '#6366f1' : '#d1d5db'; ?>; transition: 0.3s; border-radius: 28px;">
                        <span class="toggle-knob" style="position: absolute; content: ''; height: 22px; width: 22px; left: 3px; bottom: 3px; background-color: white; transition: 0.3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transform: translateX(<?php echo $alarmSettings['service_notice_opt_in'] ? '20px' : '0'; ?>);"></span>
                    </span>
                </label>
            </div>
        </div>
        <?php endif; ?>

        <!-- 광고성 정보수신(선택) 섹션 -->
        <?php if ($marketingIsVisible): ?>
        <div style="background-color: #ffffff; border-radius: 8px; padding: 20px; margin-bottom: 16px;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;">
                <div style="display: flex; align-items: flex-start; gap: 16px; flex: 1;">
                    <!-- 아이콘 -->
                    <div style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 10px; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 8v4" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 16h.01" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>

                    <!-- 텍스트 -->
                    <div style="flex: 1;">
                        <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 6px 0; color: #212529;">
                            <?php echo $marketingTitle; ?> 
                            <span style="font-size: 12px; font-weight: 600; color: <?php echo $marketingIsRequired ? '#4f46e5' : '#6b7280'; ?>; vertical-align: middle;">
                                (<?php echo $marketingIsRequired ? '필수' : '선택'; ?>)
                            </span>
                        </h3>
                        <p style="font-size: 14px; color: #6b7280; line-height: 1.65; margin: 8px 0 0 0;">
                            이메일, SMS·SNS, 앱 푸시를 통해 서비스 및 제휴사의 이벤트·프로모션, 할인·쿠폰, 맞춤형 혜택, 중요 공지 및 서비스 업데이트, 정기 뉴스레터 등 유용한 정보를 받아보실 수 있습니다.
                        </p>
                    </div>
                </div>

                <!-- 마케팅 전체 토글 -->
                <label class="toggle-switch" style="position: relative; display: inline-block; width: 48px; height: 28px; flex-shrink: 0; cursor: pointer; margin-top: 4px;">
                    <input type="checkbox" id="marketingOptIn" class="toggle-input" <?php echo $marketingOptIn ? 'checked' : ''; ?> onchange="handleMarketingToggle(this)">
                    <span class="toggle-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: <?php echo $marketingOptIn ? '#6366f1' : '#d1d5db'; ?>; transition: 0.3s; border-radius: 28px;">
                        <span class="toggle-knob" style="position: absolute; content: ''; height: 22px; width: 22px; left: 3px; bottom: 3px; background-color: white; transition: 0.3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transform: translateX(<?php echo $marketingOptIn ? '20px' : '0'; ?>);"></span>
                    </span>
                </label>
            </div>

            <!-- 채널 체크박스는 제목/설명 텍스트 시작선(아이콘 오른쪽)에 정렬 -->
            <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 14px; margin-left: 56px;">
                <!-- 이메일 수신동의 -->
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 12px; min-height: 48px; box-sizing: border-box; border-radius: 8px; transition: background-color 0.2s;" 
                       onmouseover="this.style.backgroundColor='#f9fafb'" 
                       onmouseout="this.style.backgroundColor='transparent'">
                    <input type="checkbox" id="marketingEmailOptIn" class="advertising-checkbox" <?php echo $alarmSettings['marketing_email_opt_in'] ? 'checked' : ''; ?> onchange="handleAdvertisingChange()" style="width: 20px; height: 20px; cursor: pointer; accent-color: #6366f1;">
                    <span style="font-size: 15px; color: #212529; flex: 1;">이메일 수신동의</span>
                </label>

                <!-- SMS, SNS 수신동의 (SMS + 카카오 알림톡을 함께 제어) -->
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 12px; min-height: 48px; box-sizing: border-box; border-radius: 8px; transition: background-color 0.2s;" 
                       onmouseover="this.style.backgroundColor='#f9fafb'" 
                       onmouseout="this.style.backgroundColor='transparent'">
                    <input type="checkbox" id="marketingSmsSnsOptIn" class="advertising-checkbox" <?php echo $alarmSettings['marketing_sms_sns_opt_in'] ? 'checked' : ''; ?> onchange="handleAdvertisingChange()" style="width: 20px; height: 20px; cursor: pointer; accent-color: #6366f1;">
                    <span style="font-size: 15px; color: #212529; flex: 1;">SMS, SNS 수신동의</span>
                </label>

                <!-- 앱 푸시 수신동의 -->
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 12px; min-height: 48px; box-sizing: border-box; border-radius: 8px; transition: background-color 0.2s;" 
                       onmouseover="this.style.backgroundColor='#f9fafb'" 
                       onmouseout="this.style.backgroundColor='transparent'">
                    <input type="checkbox" id="marketingPushOptIn" class="advertising-checkbox" <?php echo $alarmSettings['marketing_push_opt_in'] ? 'checked' : ''; ?> onchange="handleAdvertisingChange()" style="width: 20px; height: 20px; cursor: pointer; accent-color: #6366f1;">
                    <span style="font-size: 15px; color: #212529; flex: 1;">앱 푸시 수신동의</span>
                </label>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<style>
.toggle-input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-input:checked + .toggle-slider {
    background-color: #6366f1;
}

.toggle-input:checked + .toggle-slider .toggle-knob {
    transform: translateX(20px);
}
</style>

<script>
    // BASE_PATH와 API_PATH는 header.php에서 이미 설정됨
    // header.php의 설정을 그대로 사용 (재설정하지 않음)

// 혜택·이벤트 알림 토글 처리
function handleBenefitToggle(checkbox) {
    const slider = checkbox.nextElementSibling;
    const knob = slider.querySelector('.toggle-knob');
    
    if (checkbox.checked) {
        slider.style.backgroundColor = '#6366f1';
        knob.style.transform = 'translateX(20px)';
    } else {
        slider.style.backgroundColor = '#d1d5db';
        knob.style.transform = 'translateX(0)';
    }
    
    // 알림 설정 저장
    saveAlarmSettings();
}

// 광고성 정보 수신동의(전체) 토글 처리
function handleMarketingToggle(checkbox) {
    const slider = checkbox.nextElementSibling;
    const knob = slider.querySelector('.toggle-knob');

    if (checkbox.checked) {
        slider.style.backgroundColor = '#6366f1';
        knob.style.transform = 'translateX(20px)';
        // ON일 때: 하위 체크박스 모두 자동 체크
        document.getElementById('marketingEmailOptIn').checked = true;
        document.getElementById('marketingSmsSnsOptIn').checked = true;
        document.getElementById('marketingPushOptIn').checked = true;
    } else {
        slider.style.backgroundColor = '#d1d5db';
        knob.style.transform = 'translateX(0)';
        // OFF면 하위 채널 전부 해제
        document.getElementById('marketingEmailOptIn').checked = false;
        document.getElementById('marketingSmsSnsOptIn').checked = false;
        document.getElementById('marketingPushOptIn').checked = false;
    }

    applyMarketingDisabledState();
    saveAlarmSettings();
}

function applyMarketingDisabledState() {
    const enabled = document.getElementById('marketingOptIn').checked;
    const checkboxes = document.querySelectorAll('.advertising-checkbox');
    checkboxes.forEach(cb => {
        cb.disabled = !enabled;
        cb.style.cursor = enabled ? 'pointer' : 'not-allowed';
        cb.parentElement.style.opacity = enabled ? '1' : '0.5';
    });
}

// 광고성 정보 수신 동의 체크박스 변경 처리
function handleAdvertisingChange() {
    const marketingOptIn = document.getElementById('marketingOptIn');
    
    // 채널 체크 상태 확인
    const anyChecked =
        document.getElementById('marketingEmailOptIn').checked ||
        document.getElementById('marketingSmsSnsOptIn').checked ||
        document.getElementById('marketingPushOptIn').checked;

    // 체크박스가 하나라도 체크되면 상위 토글 자동 ON
    if (anyChecked && !marketingOptIn.checked) {
        marketingOptIn.checked = true;
        handleMarketingToggle(marketingOptIn);
        return;
    }

    // 모든 체크박스가 해제되면 상위 토글도 OFF
    if (!anyChecked && marketingOptIn.checked) {
        marketingOptIn.checked = false;
        handleMarketingToggle(marketingOptIn);
        return;
    }

    // 알림 설정 저장
    saveAlarmSettings();
}

// 알림 설정 저장 함수
function saveAlarmSettings() {
    const marketingOptIn = document.getElementById('marketingOptIn').checked;
    const smsSns = document.getElementById('marketingSmsSnsOptIn').checked;

    // FormData 사용 (웹 서버가 JSON 요청을 거부하는 경우 대비)
    const formData = new FormData();
    formData.append('service_notice_opt_in', document.getElementById('serviceNoticeOptIn').checked ? '1' : '0');
    formData.append('marketing_opt_in', marketingOptIn ? '1' : '0');
    formData.append('marketing_email_opt_in', document.getElementById('marketingEmailOptIn').checked ? '1' : '0');
    formData.append('marketing_sms_sns_opt_in', (marketingOptIn ? smsSns : false) ? '1' : '0');
    formData.append('marketing_push_opt_in', document.getElementById('marketingPushOptIn').checked ? '1' : '0');
    
    // API 경로 설정 (window.API_PATH를 그대로 사용)
    const apiPath = window.API_PATH || (window.BASE_PATH || '') + '/api';
    fetch(apiPath + '/update-alarm-settings.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
    .then(response => {
        // Content-Type 확인
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(text => {
                throw new Error('서버가 JSON을 반환하지 않았습니다: ' + text.substring(0, 200));
            });
        }
    })
    .then(data => {
        if (data.success) {
            // 성공 메시지 (선택 사항)
            // console.log('알림 설정이 저장되었습니다.');
        } else {
            console.error('알림 설정 저장 실패:', data.message);
            alert('알림 설정 저장에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        console.error('알림 설정 저장 오류:', error);
        alert('알림 설정 저장 중 오류가 발생했습니다: ' + error.message);
    });
}

// 초기 상태 적용
document.addEventListener('DOMContentLoaded', function() {
    // 마케팅 채널 중 하나라도 체크되어 있으면 전체 토글도 자동으로 체크
    const marketingEmail = document.getElementById('marketingEmailOptIn');
    const marketingSmsSns = document.getElementById('marketingSmsSnsOptIn');
    const marketingPush = document.getElementById('marketingPushOptIn');
    const marketingOptIn = document.getElementById('marketingOptIn');
    
    if (marketingEmail && marketingSmsSns && marketingPush && marketingOptIn) {
        const anyChannelChecked = marketingEmail.checked || marketingSmsSns.checked || marketingPush.checked;
        if (anyChannelChecked && !marketingOptIn.checked) {
            marketingOptIn.checked = true;
            // 토글 UI 업데이트
            const slider = marketingOptIn.nextElementSibling;
            const knob = slider.querySelector('.toggle-knob');
            if (slider && knob) {
                slider.style.backgroundColor = '#6366f1';
                knob.style.transform = 'translateX(20px)';
            }
        }
    }
    
    applyMarketingDisabledState();
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

