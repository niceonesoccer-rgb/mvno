<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';

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
                    <!-- 선물 상자 아이콘 -->
                    <div style="flex-shrink: 0; width: 40px; height: 40px; background-color: #e0e7ff; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 7H4C3.44772 7 3 7.44772 3 8V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V8C21 7.44772 20.5523 7 20 7Z" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 21V11C16 10.4477 15.5523 10 15 10H9C8.44772 10 8 10.4477 8 11V21" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 7V3C12 2.44772 12.4477 2 13 2H15C15.5523 2 16 2.44772 16 3V7" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8 7V3C8 2.44772 8.44772 2 9 2H11C11.5523 2 12 2.44772 12 3V7" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 14V17" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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

