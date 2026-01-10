<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';

// 경로 설정 파일 먼저 로드
require_once '../includes/data/path-config.php';

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';

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

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div style="width: 460px; margin: 0 auto; padding: 20px;" class="withdraw-container">
        <!-- 뒤로가기 버튼 및 제목 -->
        <div style="margin-bottom: 24px; padding: 20px 0;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <a href="<?php echo getAssetPath('/mypage/account-management.php'); ?>" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <h1 style="font-size: 20px; font-weight: bold; margin: 0; color: #212529;">탈퇴 전 아래 내용을 꼭 확인해주세요</h1>
            </div>
        </div>

        <!-- 탈퇴 안내 내용 -->
        <div style="background-color: #ffffff; border-radius: 8px; padding: 24px; margin-bottom: 24px;">
            <ol style="list-style: decimal; padding-left: 20px; margin: 0; line-height: 1.8;">
                <li style="margin-bottom: 16px; font-size: 15px; color: #212529;">
                    신청 중 또는 개통 대기 중인 요금제에 대해 유심킹으로부터 더이상 알림을 받으실 수 없게돼요
                </li>
                <li style="margin-bottom: 16px; font-size: 15px; color: #212529;">
                    지급 예정된 이벤트 혜택이 있었다면 탈퇴 즉시 모두 취소돼요
                </li>
                <li style="margin-bottom: 16px; font-size: 15px; color: #212529;">
                    유심킹 포인트는 액수와 상관없이 자동 소멸되며, 적립된 포인트는 반환할 수 없어요.
                </li>
                <li style="margin-bottom: 16px; font-size: 15px; color: #212529;">
                    재가입을 하더라도 한번 삭제된 기존 정보(신청 내역, 개통 정보 등) 가 복구되지는 않아요
                </li>
                <li style="margin-bottom: 16px; font-size: 15px; color: #212529;">
                    탈퇴하시면 유심킹 개통 내역은 개통완료 시 60일 후 파기, 개통 전 철회(취소) 시 30일 후 파기, 신청 후 미개통 시 60일 후 파기, (타인 납부 시) 개통완료 후 90일 후 파기 돼요.
                </li>
                <li style="margin-bottom: 16px; font-size: 15px; color: #212529;">
                    리뷰와 커뮤니티 글, 댓글은 삭제되지 않아요
                </li>
            </ol>
        </div>

        <!-- 확인 체크박스 -->
        <div style="margin-bottom: 24px; padding: 16px; background-color: #f9fafb; border-radius: 8px;">
            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                <input type="checkbox" id="withdrawConfirm" style="width: 20px; height: 20px; cursor: pointer; accent-color: #6366f1;">
                <span style="font-size: 15px; color: #212529;">위 내용을 모두 읽고 이해했어요</span>
            </label>
        </div>

        <!-- 탈퇴하기 버튼 -->
        <div>
            <button type="button" id="withdrawBtn" disabled style="width: 100%; padding: 16px; background-color: #e5e7eb; border: none; border-radius: 8px; font-size: 16px; color: #9ca3af; cursor: not-allowed; font-weight: 500; transition: all 0.3s ease;" onclick="handleFinalWithdraw()">
                탈퇴하기
            </button>
        </div>
    </div>
</main>

<script>
    // BASE_PATH와 API_PATH를 JavaScript에서 사용할 수 있도록 설정
    window.BASE_PATH = window.BASE_PATH || '<?php echo getBasePath(); ?>';
    window.API_PATH = window.API_PATH || (window.BASE_PATH + '/api');

// 체크박스 상태에 따라 버튼 활성화/비활성화
document.getElementById('withdrawConfirm').addEventListener('change', function() {
    const btn = document.getElementById('withdrawBtn');
    if (this.checked) {
        btn.disabled = false;
        btn.style.backgroundColor = '#6366f1';
        btn.style.color = '#ffffff';
        btn.style.cursor = 'pointer';
    } else {
        btn.disabled = true;
        btn.style.backgroundColor = '#e5e7eb';
        btn.style.color = '#9ca3af';
        btn.style.cursor = 'not-allowed';
    }
});

async function handleFinalWithdraw() {
    const confirmed = document.getElementById('withdrawConfirm').checked;
    if (!confirmed) {
        await showAlert('위 내용을 확인하고 체크박스를 선택해주세요.');
        return;
    }
    
    const result = await showConfirm('정말 회원 탈퇴를 진행하시겠습니까?\n이 작업은 되돌릴 수 없습니다.', '회원 탈퇴 확인');
    if (!result) {
        return;
    }
    
    // 탈퇴 버튼 비활성화
    const btn = document.getElementById('withdrawBtn');
    btn.disabled = true;
    btn.textContent = '처리 중...';
    
    try {
        // 회원 탈퇴 API 호출
        const apiPath = window.API_PATH || (window.BASE_PATH || '') + '/api';
        const response = await fetch(apiPath + '/withdraw-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                confirm: true
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            await showAlert('회원 탈퇴가 완료되었습니다.');
            // 홈페이지로 리다이렉트
            window.location.href = (window.BASE_PATH || '') + '/';
        } else {
            await showAlert(data.message || '회원 탈퇴 처리 중 오류가 발생했습니다.');
            btn.disabled = false;
            btn.textContent = '탈퇴하기';
        }
    } catch (error) {
        console.error('탈퇴 처리 오류:', error);
        await showAlert('회원 탈퇴 처리 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
        btn.disabled = false;
        btn.textContent = '탈퇴하기';
    }
}
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

