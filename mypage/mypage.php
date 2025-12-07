<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 헤더 포함
include '../includes/header.php';

?>

<main class="main-content">
    <div style="width: 460px; margin: 0 auto; padding: 20px;" class="mypage-container">
        <!-- 사용자 인사말 헤더 -->
        <div style="margin-bottom: 24px; padding: 20px 0;">
            <h2 style="font-size: 24px; font-weight: bold; margin: 0;">YMB님 안녕하세요</h2>
        </div>


        <!-- 하단 메뉴 리스트 -->
        <div style="margin-bottom: 32px;">
            <ul style="list-style: none; padding: 0; margin: 0;">

            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 16px 0;">
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="/MVNO/notice/notice.php" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span style="font-size: 16px;">공지 사항</span>
                        </div>
                        <img alt=">" src="https://assets-legacy.moyoplan.com/img/icons/rightArrow.svg" style="width: 16px; height: 16px;">
                    </a>
                </li>
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="/MVNO/qna/qna.php" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                                <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span style="font-size: 16px;">질문과 답변</span>
                        </div>
                        <img alt=">" src="https://assets-legacy.moyoplan.com/img/icons/rightArrow.svg" style="width: 16px; height: 16px;">
                    </a>
                </li>
            </ul>

        </div>
    </div>
</main>

<script src="../assets/js/plan-accordion.js" defer></script>
<script src="../assets/js/favorite-heart.js" defer></script>
<script src="../assets/js/point-balance-update.js" defer></script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>
