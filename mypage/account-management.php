<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div style="width: 460px; margin: 0 auto; padding: 20px;" class="account-settings-container">
        <!-- 뒤로가기 버튼 및 제목 -->
        <div style="margin-bottom: 24px; padding: 20px 0;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <a href="/MVNO/mypage/mypage.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <h1 style="font-size: 20px; font-weight: bold; margin: 0; color: #212529;">계정 설정</h1>
            </div>
        </div>

        <!-- 계정 정보 섹션 -->
        <div style="background-color: #ffffff; border-radius: 8px; padding: 24px; margin-bottom: 24px;">
            <div style="margin-bottom: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
                    <span style="font-size: 16px; color: #6b7280; font-weight: 500;">이름</span>
                    <span style="font-size: 16px; color: #212529; font-weight: 500;">YMB</span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
                    <span style="font-size: 16px; color: #6b7280; font-weight: 500;">연락처</span>
                    <span style="font-size: 16px; color: #212529; font-weight: 500;">+82 10-2423-2324</span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0;">
                    <span style="font-size: 16px; color: #6b7280; font-weight: 500;">포인트</span>
                    <span style="font-size: 16px; color: #212529; font-weight: 500;">-</span>
                </div>
            </div>
        </div>

        <!-- 회원 탈퇴 버튼 -->
        <div style="margin-top: 32px;">
            <a href="/MVNO/mypage/withdraw.php" style="display: block; width: 100%; padding: 16px; background-color: transparent; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 16px; color: #212529; text-align: center; text-decoration: none; font-weight: 500;">
                회원 탈퇴
            </a>
        </div>
    </div>
</main>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

