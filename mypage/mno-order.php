<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = false;

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

$user_id = $currentUser['user_id'];

// 포인트 설정 및 함수 포함
require_once '../includes/data/point-settings.php';
require_once '../includes/data/product-functions.php';
require_once '../includes/data/db-config.php';

// DB에서 실제 신청 내역 가져오기
$phones = getUserMnoApplications($user_id);

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div class="content-layout">
        <div class="plans-main-layout">
            <div class="plans-left-section">
                <!-- 페이지 헤더 -->
                <div style="margin-bottom: 24px; padding: 20px 0;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <a href="/MVNO/mypage/mypage.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 통신사폰</h2>
                    </div>
                </div>

                <!-- 신청한 통신사폰 목록 -->
                <div style="margin-bottom: 32px;" id="phonesContainer">
                    <?php if (empty($phones)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: #6b7280;">
                            신청한 통신사폰이 없습니다.
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($phones as $index => $phone): ?>
                                <div class="phone-item" data-index="<?php echo $index; ?>" data-phone-id="<?php echo $phone['id']; ?>" style="<?php echo $index >= 10 ? 'display: none;' : ''; ?> padding: 16px; border-bottom: 1px solid #e5e7eb;">
                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                            <div style="flex: 1;">
                                                <div style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 4px;">
                                                    <?php echo htmlspecialchars($phone['provider'] ?? ''); ?> <?php echo htmlspecialchars($phone['device_name'] ?? ''); ?>
                                                </div>
                                                <?php if (!empty($phone['device_storage'])): ?>
                                                    <div style="font-size: 14px; color: #6b7280; margin-bottom: 4px;">
                                                        용량: <?php echo htmlspecialchars($phone['device_storage']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div style="font-size: 14px; color: #6b7280; margin-bottom: 4px;">
                                                    <?php echo htmlspecialchars($phone['plan_name'] ?? ''); ?>
                                                </div>
                                                <div style="font-size: 14px; color: #374151; font-weight: 500;">
                                                    <?php echo htmlspecialchars($phone['price'] ?? ''); ?>
                                                </div>
                                            </div>
                                            <div style="font-size: 12px; color: #9ca3af;">
                                                <?php echo htmlspecialchars($phone['order_date'] ?? ''); ?>
                                                <?php if (!empty($phone['order_time'])): ?>
                                                    <?php echo htmlspecialchars($phone['order_time']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($phone['status'])): ?>
                                            <div style="font-size: 12px; color: #6366f1;">
                                                상태: <?php echo htmlspecialchars($phone['status']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 더보기 버튼 -->
                <?php if (count($phones) > 10): ?>
                <div style="margin-top: 32px; margin-bottom: 32px;" id="moreButtonContainer">
                    <button class="plan-review-more-btn" id="morePhonesBtn" style="width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                        더보기 (<?php 
                        $remaining = count($phones) - 10;
                        echo $remaining > 10 ? 10 : $remaining;
                        ?>개)
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 더보기 기능
    const moreBtn = document.getElementById('morePhonesBtn');
    const phoneItems = document.querySelectorAll('.phone-item');
    let visibleCount = 10;
    const totalPhones = phoneItems.length;
    const loadCount = 10;

    function updateButtonText() {
        if (!moreBtn) return;
        const remaining = totalPhones - visibleCount;
        if (remaining > 0) {
            const showCount = remaining > loadCount ? loadCount : remaining;
            moreBtn.textContent = `더보기 (${showCount}개)`;
        }
    }

    if (moreBtn && totalPhones > 10) {
        updateButtonText();
        
        moreBtn.addEventListener('click', function() {
            const endCount = Math.min(visibleCount + loadCount, totalPhones);
            for (let i = visibleCount; i < endCount; i++) {
                if (phoneItems[i]) {
                    phoneItems[i].style.display = 'block';
                }
            }
            
            visibleCount = endCount;
            
            if (visibleCount >= totalPhones) {
                const moreButtonContainer = document.getElementById('moreButtonContainer');
                if (moreButtonContainer) {
                    moreButtonContainer.style.display = 'none';
                }
            } else {
                updateButtonText();
            }
        });
    } else if (moreBtn && totalPhones <= 10) {
        const moreButtonContainer = document.getElementById('moreButtonContainer');
        if (moreButtonContainer) {
            moreButtonContainer.style.display = 'none';
        }
    }
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

