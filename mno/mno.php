<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mno';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 모니터링 시스템 (선택사항 - 주석 해제하여 사용)
// require_once 'includes/monitor.php';
// $monitor = new ConnectionMonitor();
// $monitor->logConnection();

// 헤더 포함
include '../includes/header.php';

// 통신사폰 데이터 가져오기
require_once '../includes/data/phone-data.php';
$phones = getPhonesData(1000);
?>

<main class="main-content">
    <!-- 통신사폰 목록 섹션 -->
    <div class="content-layout">
        <div class="plans-main-layout">
            <!-- 왼쪽 섹션: 통신사폰 목록 -->
            <div class="plans-left-section">
                <!-- 테마별 통신사폰 섹션 -->
                <section class="theme-plans-list-section">
                    <!-- 통신사폰 목록 레이아웃 -->
                    <?php
                    $section_title = '';
                    include '../includes/layouts/phone-list-layout.php';
                    ?>
                </section>
            </div>
        </div>
    </div>
</main>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

<script src="/MVNO/assets/js/plan-accordion.js" defer></script>
<script src="/MVNO/assets/js/favorite-heart.js" defer></script>
<script src="/MVNO/assets/js/share.js" defer></script>
