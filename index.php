<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'home';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 헤더 포함
include 'includes/header.php';

// 메인 페이지 데이터 함수 포함
require_once 'includes/data/home-functions.php';
require_once 'includes/data/plan-data.php';
require_once 'includes/data/phone-data.php';

// 메인 페이지 설정 가져오기
$home_settings = getHomeSettings();

// 메인 배너 이벤트 가져오기 (3개)
$main_banner_events = [];
if (!empty($home_settings['main_banners']) && is_array($home_settings['main_banners'])) {
    foreach ($home_settings['main_banners'] as $event_id) {
        $event = getEventById($event_id);
        if ($event) {
            $main_banner_events[] = $event;
        }
    }
}

// 알뜰폰 요금제 가져오기
$mvno_plans = [];
if (!empty($home_settings['mvno_plans']) && is_array($home_settings['mvno_plans'])) {
    $all_plans = getPlansData(100);
    foreach ($home_settings['mvno_plans'] as $plan_id) {
        foreach ($all_plans as $plan) {
            if (isset($plan['id']) && $plan['id'] == $plan_id) {
                $mvno_plans[] = $plan;
                break;
            }
        }
    }
}

// 통신사폰 가져오기
$mno_phones = [];
if (!empty($home_settings['mno_phones']) && is_array($home_settings['mno_phones'])) {
    $all_phones = getPhonesData(100);
    foreach ($home_settings['mno_phones'] as $phone_id) {
        foreach ($all_phones as $phone) {
            if (isset($phone['id']) && $phone['id'] == $phone_id) {
                $mno_phones[] = $phone;
                break;
            }
        }
    }
}
?>

<main class="main-content">
    <!-- 첫 번째 섹션: 메인 배너 레이아웃 (왼쪽 큰 배너 1개 + 오른쪽 작은 배너 2개) -->
    <div class="content-layout">
        <section class="main-banner-layout-section" style="margin-bottom: 2rem;">
            <?php if (!empty($main_banner_events)): ?>
                <div class="main-banner-grid">
                    <!-- 왼쪽: 큰 배너 1개 (16:9) -->
                    <div class="main-banner-left">
                        <?php if (isset($main_banner_events[0])): ?>
                            <a href="<?php echo htmlspecialchars($main_banner_events[0]['link']); ?>" class="main-banner-card large">
                                <img src="<?php echo htmlspecialchars($main_banner_events[0]['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($main_banner_events[0]['title']); ?>" 
                                     class="main-banner-image">
                            </a>
                        <?php else: ?>
                            <div class="main-banner-placeholder large">
                                <div class="placeholder-content">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.3;">
                                        <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 오른쪽: 작은 배너 2개 (16:9, 세로 배열) -->
                    <div class="main-banner-right">
                        <?php if (isset($main_banner_events[1])): ?>
                            <a href="<?php echo htmlspecialchars($main_banner_events[1]['link']); ?>" class="main-banner-card small">
                                <img src="<?php echo htmlspecialchars($main_banner_events[1]['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($main_banner_events[1]['title']); ?>" 
                                     class="main-banner-image">
                            </a>
                        <?php else: ?>
                            <div class="main-banner-placeholder small">
                                <div class="placeholder-content">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.3;">
                                        <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($main_banner_events[2])): ?>
                            <a href="<?php echo htmlspecialchars($main_banner_events[2]['link']); ?>" class="main-banner-card small">
                                <img src="<?php echo htmlspecialchars($main_banner_events[2]['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($main_banner_events[2]['title']); ?>" 
                                     class="main-banner-image">
                            </a>
                        <?php else: ?>
                            <div class="main-banner-placeholder small">
                                <div class="placeholder-content">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.3;">
                                        <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- 데이터가 없을 때 플레이스홀더 -->
                <div class="main-banner-grid">
                    <div class="main-banner-left">
                        <div class="main-banner-placeholder large">
                            <div class="placeholder-content">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.3; margin-bottom: 16px;">
                                    <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <p style="color: #9ca3af; font-size: 12px; margin: 0;">큰 배너</p>
                            </div>
                        </div>
                    </div>
                    <div class="main-banner-right">
                        <div class="main-banner-placeholder small">
                            <div class="placeholder-content">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.3; margin-bottom: 16px;">
                                    <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <p style="color: #9ca3af; font-size: 12px; margin: 0;">작은 배너 1</p>
                            </div>
                        </div>
                        <div class="main-banner-placeholder small">
                            <div class="placeholder-content">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.3; margin-bottom: 16px;">
                                    <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <p style="color: #9ca3af; font-size: 12px; margin: 0;">작은 배너 2</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- 두 번째 섹션: 알뜰폰 요금제 -->
    <div class="home-section bg-white">
        <div class="content-layout">
            <section class="home-product-section">
                <div class="home-section-header">
                    <h2 class="home-section-title">추천 알뜰폰 요금제</h2>
                    <a href="/MVNO/mvno/mvno.php" class="home-section-more">더보기 &gt;</a>
                </div>
                <?php if (!empty($mvno_plans)): ?>
                    <div class="home-product-grid">
                        <?php foreach (array_slice($mvno_plans, 0, 6) as $plan): ?>
                            <?php include 'includes/components/plan-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="home-empty-state">
                        <p style="color: #9ca3af; text-align: center; padding: 3rem 1rem;">
                            관리자 페이지에서 알뜰폰 요금제를 선택해주세요
                        </p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <!-- 세 번째 섹션: 통신사폰 -->
    <div class="home-section bg-gray-100">
        <div class="content-layout">
            <section class="home-product-section">
                <div class="home-section-header">
                    <h2 class="home-section-title">인기 통신사폰</h2>
                    <a href="/MVNO/mno/mno.php" class="home-section-more">더보기 &gt;</a>
                </div>
                <?php if (!empty($mno_phones)): ?>
                    <div class="home-product-grid">
                        <?php foreach (array_slice($mno_phones, 0, 6) as $phone): ?>
                            <?php include 'includes/components/phone-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="home-empty-state">
                        <p style="color: #9ca3af; text-align: center; padding: 3rem 1rem;">
                            관리자 페이지에서 통신사폰을 선택해주세요
                        </p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <!-- 네 번째 섹션: 인터넷 상품 -->
    <div class="home-section bg-gray-200">
        <div class="content-layout">
            <section class="home-internet-section">
                <div class="home-section-header">
                    <h2 class="home-section-title">최대할인 인터넷 상품</h2>
                    <p class="home-section-subtitle">현금성 상품받고, 최대혜택 누리기</p>
                </div>
                <?php if (!empty($home_settings['internet_products'])): ?>
                    <div class="home-internet-carousel">
                        <div class="home-internet-swiper" id="homeInternetSwiper">
                            <?php foreach ($home_settings['internet_products'] as $product): ?>
                                <div class="home-internet-slide">
                                    <a href="/plans/<?php echo htmlspecialchars($product); ?>" class="plan-card">
                                        <div class="plan-card-content internet-card">
                                            <div class="internet-card-header">
                                                <div class="internet-company">
                                                    <img src="/MVNO/assets/images/internets/ktskylife.svg" alt="KT skylife" class="internet-company-logo">
                                                </div>
                                                <div class="internet-specs">
                                                    <div class="internet-spec">
                                                        <img src="/MVNO/assets/images/icons/cash.svg" alt="인터넷 속도" class="internet-spec-icon">
                                                        <span>500MB</span>
                                                    </div>
                                                    <div class="internet-spec">
                                                        <img src="/MVNO/assets/images/icons/installation.svg" alt="TV 채널" class="internet-spec-icon">
                                                        <span>194개</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="internet-benefits">
                                                <div class="internet-benefit">
                                                    <img src="/MVNO/assets/images/icons/gift-card.svg" alt="혜택" class="internet-benefit-icon">
                                                    <div class="internet-benefit-text">
                                                        <p class="internet-benefit-title">인터넷,TV 설치비 무료</p>
                                                        <p class="internet-benefit-sub">무료(36,300원 상당)</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="internet-price">
                                                <p class="internet-price-text">월 26,400원</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="home-section-footer">
                        <a href="/MVNO/internets/internets.php" class="home-section-more-btn">
                            <span>인터넷 상품 더보기</span>
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.15146 20.8485C7.68283 20.3799 7.68283 19.6201 8.15146 19.1515L15.3029 12L8.15146 4.84852C7.68283 4.37989 7.68283 3.6201 8.15146 3.15147C8.62009 2.68284 9.37989 2.68284 9.84852 3.15147L17.8485 11.1515C18.3171 11.6201 18.3171 12.3799 17.8485 12.8485L9.84852 20.8485C9.37989 21.3172 8.62009 21.3172 8.15146 20.8485Z" fill="#868E96"></path>
                            </svg>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="home-empty-state">
                        <p style="color: #9ca3af; text-align: center; padding: 3rem 1rem;">
                            관리자 페이지에서 인터넷 상품을 설정해주세요
                        </p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

</main>

<style>
/* 메인 배너 레이아웃 */
.main-banner-layout-section {
    margin-top: 1.5rem;
}

.main-banner-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    width: 100%;
    align-items: stretch;
}

.main-banner-left {
    display: flex;
    flex-direction: column;
}

.main-banner-right {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.main-banner-card {
    display: block;
    width: 100%;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
}

.main-banner-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.main-banner-card.large {
    aspect-ratio: 16 / 9;
    width: 100%;
}

.main-banner-card.small {
    flex: 1;
    min-height: 0;
    width: 100%;
}

/* 작은 배너 2개가 큰 배너 높이에 맞추기 */
.main-banner-right {
    height: 100%;
}

.main-banner-right .main-banner-card.small {
    height: calc((100% - 1.5rem) / 2);
    flex: 0 0 auto;
    object-fit: cover;
}

.main-banner-right .main-banner-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.main-banner-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* 배너 플레이스홀더 */
.main-banner-placeholder {
    border-radius: 12px;
    border: 2px dashed #e5e7eb;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
}

.main-banner-placeholder.large {
    aspect-ratio: 16 / 9;
    width: 100%;
}

.main-banner-placeholder.small {
    flex: 1;
    min-height: 0;
    width: 100%;
}

.main-banner-right .main-banner-placeholder.small {
    height: calc((100% - 1.5rem) / 2);
    flex: 0 0 auto;
}

.placeholder-content {
    text-align: center;
    color: #9ca3af;
    padding: 1rem;
}

/* 홈 섹션 공통 스타일 */
.home-section {
    padding: 3rem 0;
    margin-top: 2rem;
}

.bg-white {
    background-color: #ffffff;
}

.bg-gray-100 {
    background-color: #f9fafb;
}

.bg-gray-200 {
    background-color: #f3f4f6;
}

.home-section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding: 0 1rem;
}

.home-section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
}

.home-section-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0.5rem 0 0 0;
    width: 100%;
}

.home-section-more {
    font-size: 0.875rem;
    color: #6366f1;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
    white-space: nowrap;
    margin-left: 1rem;
}

.home-section-more:hover {
    color: #4f46e5;
}

/* 홈 제품 그리드 */
.home-product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    padding: 0 1rem;
}

/* 빈 상태 스타일 */
.home-empty-state {
    padding: 3rem 1rem;
    text-align: center;
    color: #9ca3af;
}

/* 인터넷 섹션 */
.home-internet-section {
    width: 100%;
}

.home-internet-carousel {
    position: relative;
    padding: 0 1rem;
}

.home-internet-swiper {
    display: flex;
    gap: 1.5rem;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding-bottom: 1rem;
}

.home-internet-swiper::-webkit-scrollbar {
    display: none;
}

.home-internet-slide {
    flex: 0 0 320px;
    scroll-snap-align: start;
}

.home-section-footer {
    text-align: center;
    margin-top: 2rem;
    padding: 0 1rem;
}

.home-section-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    color: #374151;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.home-section-more-btn:hover {
    border-color: #6366f1;
    color: #6366f1;
}

.home-section-more-btn svg {
    width: 16px;
    height: 16px;
}

/* 모바일 반응형 */
@media (max-width: 767px) {
    .home-section {
        padding: 2rem 0;
    }
    
    .home-section-header {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .home-section-more {
        margin-left: 0;
        align-self: flex-end;
    }
    
    .home-section-title {
        font-size: 1.25rem;
    }
    
    .home-product-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .home-internet-slide {
        flex: 0 0 280px;
    }
    
    .main-banner-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .main-banner-card.large,
    .main-banner-card.small,
    .main-banner-placeholder.large,
    .main-banner-placeholder.small {
        aspect-ratio: 16 / 9;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    .home-product-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .home-product-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>


<?php
// 푸터 포함
include 'includes/footer.php';
?>

<?php
// show_login 파라미터가 있으면 로그인 모달 자동 열기
if (isset($_GET['show_login']) && $_GET['show_login'] == '1'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof openLoginModal === 'function') {
        openLoginModal(true);
    } else {
        setTimeout(() => {
            if (typeof openLoginModal === 'function') {
                openLoginModal(true);
            }
        }, 100);
    }
});
</script>
<?php endif; ?>
