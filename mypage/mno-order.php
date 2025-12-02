<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = false;

// 통신사폰 데이터 배열
$phones = [
    ['id' => 1, 'provider' => 'SKT', 'device_name' => 'Galaxy Z Fold7', 'device_storage' => '256GB', 'plan_name' => 'SKT 프리미어 슈퍼', 'price' => '월 109,000원', 'maintenance_period' => '185일', 'order_date' => '2024.11.15', 'order_time' => '14:30'],
    ['id' => 2, 'provider' => 'KT', 'device_name' => 'iPhone 16 Pro', 'device_storage' => '512GB', 'plan_name' => 'KT 슈퍼플랜', 'price' => '월 125,000원', 'maintenance_period' => '180일', 'order_date' => '2024.11.12', 'order_time' => '09:15'],
    ['id' => 3, 'provider' => 'LG U+', 'device_name' => 'Galaxy S25', 'device_storage' => '256GB', 'plan_name' => 'LG U+ 5G 슈퍼플랜', 'price' => '월 95,000원', 'maintenance_period' => '200일', 'order_date' => '2024.11.10', 'order_time' => '16:45'],
    ['id' => 4, 'provider' => 'SKT', 'device_name' => 'iPhone 16', 'device_storage' => '128GB', 'plan_name' => 'SKT 스탠다드', 'price' => '월 85,000원', 'maintenance_period' => '150일', 'order_date' => '2024.11.08', 'order_time' => '11:20'],
    ['id' => 5, 'provider' => 'KT', 'device_name' => 'Galaxy S24 Ultra', 'device_storage' => '512GB', 'plan_name' => 'KT 프리미엄', 'price' => '월 115,000원', 'maintenance_period' => '190일', 'order_date' => '2024.11.05', 'order_time' => '13:50'],
    ['id' => 6, 'provider' => 'LG U+', 'device_name' => 'iPhone 15 Pro Max', 'device_storage' => '256GB', 'plan_name' => 'LG U+ 5G 플랜', 'price' => '월 105,000원', 'maintenance_period' => '175일', 'order_date' => '2024.11.03', 'order_time' => '10:05'],
    ['id' => 7, 'provider' => 'SKT', 'device_name' => 'Galaxy Z Flip6', 'device_storage' => '256GB', 'plan_name' => 'SKT 베이직', 'price' => '월 75,000원', 'maintenance_period' => '140일', 'order_date' => '2024.11.01', 'order_time' => '15:30'],
    ['id' => 8, 'provider' => 'KT', 'device_name' => 'iPhone 15', 'device_storage' => '128GB', 'plan_name' => 'KT 스탠다드', 'price' => '월 80,000원', 'maintenance_period' => '160일', 'order_date' => '2024.10.28', 'order_time' => '12:15'],
    ['id' => 9, 'provider' => 'LG U+', 'device_name' => 'Galaxy S23', 'device_storage' => '256GB', 'plan_name' => 'LG U+ 5G 베이직', 'price' => '월 70,000원', 'maintenance_period' => '130일', 'order_date' => '2024.10.25', 'order_time' => '14:00'],
    ['id' => 10, 'provider' => 'SKT', 'device_name' => 'iPhone 14 Pro', 'device_storage' => '256GB', 'plan_name' => 'SKT 프리미엄', 'price' => '월 100,000원', 'maintenance_period' => '170일', 'order_date' => '2024.10.22', 'order_time' => '09:40'],
    ['id' => 11, 'provider' => 'KT', 'device_name' => 'Galaxy A54', 'device_storage' => '128GB', 'plan_name' => 'KT 베이직', 'price' => '월 65,000원', 'maintenance_period' => '120일', 'order_date' => '2024.10.20', 'order_time' => '16:20'],
    ['id' => 12, 'provider' => 'LG U+', 'device_name' => 'iPhone 13', 'device_storage' => '128GB', 'plan_name' => 'LG U+ 5G 스탠다드', 'price' => '월 75,000원', 'maintenance_period' => '145일', 'order_date' => '2024.10.18', 'order_time' => '11:55'],
    ['id' => 13, 'provider' => 'SKT', 'device_name' => 'Galaxy Note20', 'device_storage' => '256GB', 'plan_name' => 'SKT 스탠다드', 'price' => '월 90,000원', 'maintenance_period' => '165일', 'order_date' => '2024.10.15', 'order_time' => '13:25'],
    ['id' => 14, 'provider' => 'KT', 'device_name' => 'iPhone 12', 'device_storage' => '128GB', 'plan_name' => 'KT 베이직', 'price' => '월 70,000원', 'maintenance_period' => '135일', 'order_date' => '2024.10.12', 'order_time' => '10:30'],
    ['id' => 15, 'provider' => 'LG U+', 'device_name' => 'Galaxy S22', 'device_storage' => '256GB', 'plan_name' => 'LG U+ 5G 프리미엄', 'price' => '월 95,000원', 'maintenance_period' => '180일', 'order_date' => '2024.10.10', 'order_time' => '15:10'],
];

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div style="width: 460px; margin: 0 auto; padding: 20px;" class="mypage-container">
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
            <?php foreach ($phones as $index => $phone): ?>
                <div class="phone-item" data-index="<?php echo $index; ?>" style="<?php echo $index >= 10 ? 'display: none;' : ''; ?> position: relative; margin-bottom: 36px;">
                    <span style="position: absolute; top: -24px; right: 0; font-size: 12px; color: #868E96; white-space: nowrap; z-index: 1; background-color: #ffffff; padding: 2px 4px;">신청일: <?php echo htmlspecialchars($phone['order_date'] . ' ' . $phone['order_time']); ?></span>
                <article class="basic-plan-card" style="margin-bottom: 0;">
                    <a href="/MVNO/mno/mno.php?id=<?php echo $phone['id']; ?>" class="plan-card-link">
                        <div class="plan-card-main-content">
                            <div class="plan-card-header-body-frame">
                                <!-- 헤더: 통신사, 찜 -->
                                <div class="plan-card-top-header">
                                    <div class="plan-provider-rating-group">
                                        <span class="plan-provider-logo-text"><?php echo htmlspecialchars($phone['provider']); ?></span>
                                    </div>
                                    <div class="plan-badge-favorite-group">
                                        <button class="plan-favorite-btn-inline" aria-label="찜하기">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M17.9623 11.5427C18.5031 11.0065 18.8 10.2886 18.8 9.54803C18.8 8.80746 18.5032 8.08961 17.9623 7.5534C17.4166 7.01196 16.6657 6.7 15.8748 6.7C15.0838 6.7 14.3335 7.01145 13.7879 7.55284L13.549 7.7898C12.6914 8.64035 11.3084 8.64041 10.4508 7.78993L10.2121 7.55325C9.06574 6.41633 7.18394 6.41618 6.03759 7.55311C4.92082 8.66071 4.92079 10.4353 6.03758 11.543L12.0178 17.474C13.2794 16.2826 14.4839 15.0586 15.7184 13.804C16.4497 13.0609 17.1918 12.3068 17.9623 11.5427ZM11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C18.7821 13.8281 18.0602 14.5615 17.3378 15.2955C15.8837 16.7728 14.4273 18.2525 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166Z" fill="#868E96"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- 제목 -->
                                <div class="plan-title-row">
                                    <span class="plan-title-text"><?php echo htmlspecialchars($phone['device_name']); ?> | <?php echo htmlspecialchars($phone['device_storage']); ?></span>
                                </div>

                                <!-- 요금제 정보 -->
                                <div class="plan-info-section">
                                    <div class="plan-data-row">
                                        <span class="plan-data-main"><?php echo htmlspecialchars($phone['plan_name']); ?></span>
                                    </div>
                                </div>

                                <!-- 가격 정보 -->
                                <div class="plan-price-row">
                                    <div class="plan-price-left">
                                        <div class="plan-price-main-row">
                                            <span class="plan-price-main"><?php echo htmlspecialchars($phone['price']); ?></span>
                                        </div>
                                        <span class="plan-price-after">유지기간 <?php echo htmlspecialchars($phone['maintenance_period']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </article>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 더보기 버튼 -->
        <div style="margin-top: 32px; margin-bottom: 32px;" id="moreButtonContainer">
            <button class="plan-review-more-btn" id="morePhonesBtn">
                더보기 (<?php 
                $remaining = count($phones) - 10;
                echo $remaining > 10 ? 10 : $remaining;
                ?>개)
            </button>
        </div>
    </div>
</main>

<script src="../assets/js/plan-accordion.js" defer></script>
<script src="../assets/js/favorite-heart.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const moreBtn = document.getElementById('morePhonesBtn');
    const phoneItems = document.querySelectorAll('.phone-item');
    let visibleCount = 10;
    const totalPhones = phoneItems.length;
    const loadCount = 10; // 한 번에 보여줄 개수

    function updateButtonText() {
        const remaining = totalPhones - visibleCount;
        if (remaining > 0) {
            const showCount = remaining > loadCount ? loadCount : remaining;
            moreBtn.textContent = `더보기 (${showCount}개)`;
        }
    }

    if (moreBtn) {
        updateButtonText();
        
        moreBtn.addEventListener('click', function() {
            // 다음 10개씩 표시
            const endCount = Math.min(visibleCount + loadCount, totalPhones);
            for (let i = visibleCount; i < endCount; i++) {
                if (phoneItems[i]) {
                    phoneItems[i].style.display = 'block';
                }
            }
            
            visibleCount = endCount;
            
            // 모든 항목이 보이면 더보기 버튼 숨기기
            if (visibleCount >= totalPhones) {
                const moreButtonContainer = document.getElementById('moreButtonContainer');
                if (moreButtonContainer) {
                    moreButtonContainer.style.display = 'none';
                }
            } else {
                updateButtonText();
            }
        });
    }

    // 모든 통신사폰이 보이면 더보기 버튼 숨기기
    if (visibleCount >= totalPhones) {
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

