<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';

// 로그인 체크 - 로그인하지 않은 경우 회원가입 모달로 리다이렉트
if (!isLoggedIn()) {
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    // 로그인 모달이 있는 홈으로 리다이렉트 (모달 자동 열기)
    header('Location: /MVNO/?show_login=1');
    exit;
}

// 현재 사용자 정보 가져오기
$currentUser = getCurrentUser();
if (!$currentUser) {
    // 사용자 정보를 가져올 수 없으면 로그아웃 처리
    header('Location: /MVNO/?show_login=1');
    exit;
}

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div style="width: 460px; margin: 0 auto; padding: 20px;" class="alarm-setting-container">
        <!-- 뒤로가기 버튼 및 제목 -->
        <div style="margin-bottom: 24px; padding: 20px 0;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <a href="/MVNO/mypage/mypage.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <h1 style="font-size: 20px; font-weight: bold; margin: 0; color: #212529;">알림 설정</h1>
            </div>
        </div>

        <!-- 혜택·이벤트 알림 섹션 -->
        <div style="background-color: #ffffff; border-radius: 8px; padding: 20px; margin-bottom: 16px;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;">
                <div style="display: flex; align-items: flex-start; gap: 16px; flex: 1;">
                    <!-- 알림 아이콘 -->
                    <div style="flex-shrink: 0; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    
                    <!-- 텍스트 영역 -->
                    <div style="flex: 1;">
                        <h3 style="font-size: 16px; font-weight: bold; margin: 0 0 8px 0; color: #212529;">혜택·이벤트 알림</h3>
                        <p style="font-size: 14px; color: #6b7280; margin: 0; line-height: 1.5;">각종 혜택 알림, 요금제 유지기간 만료 안내, 부가서비스 종료, 프로모션종료 안내등 꼭 필요한 정보를 알려드려요</p>
                    </div>
                </div>
                
                <!-- 토글 스위치 -->
                <label class="toggle-switch" style="position: relative; display: inline-block; width: 48px; height: 28px; flex-shrink: 0; cursor: pointer;">
                    <input type="checkbox" id="benefitNotification" class="toggle-input" onchange="handleToggle(this)">
                    <span class="toggle-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #d1d5db; transition: 0.3s; border-radius: 28px;">
                        <span class="toggle-knob" style="position: absolute; content: ''; height: 22px; width: 22px; left: 3px; bottom: 3px; background-color: white; transition: 0.3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></span>
                    </span>
                </label>
            </div>
        </div>
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
function handleToggle(checkbox) {
    const slider = checkbox.nextElementSibling;
    const knob = slider.querySelector('.toggle-knob');
    
    if (checkbox.checked) {
        slider.style.backgroundColor = '#6366f1';
        knob.style.transform = 'translateX(20px)';
    } else {
        slider.style.backgroundColor = '#d1d5db';
        knob.style.transform = 'translateX(0)';
    }
    
    // 여기에 실제 알림 설정 저장 로직을 추가할 수 있습니다
    // 예: fetch('/api/notification-settings', { method: 'POST', body: JSON.stringify({ enabled: checkbox.checked }) })
}
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

