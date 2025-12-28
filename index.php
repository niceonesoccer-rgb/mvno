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
require_once 'includes/data/notice-functions.php';

// 이미지 경로 정규화 함수
function normalizeImagePathForDisplay($path) {
    if (empty($path)) {
        return '';
    }
    
    $imagePath = trim($path);
    
    // 이미 /MVNO/로 시작하면 그대로 사용
    if (strpos($imagePath, '/MVNO/') === 0) {
        return $imagePath;
    }
    // /uploads/events/ 또는 /uploads/events/로 시작하는 경우
    elseif (preg_match('#^/uploads/events/#', $imagePath)) {
        return '/MVNO' . $imagePath;
    }
    // /uploads/ 또는 /images/로 시작하면 /MVNO/ 추가
    elseif (strpos($imagePath, '/uploads/') === 0 || strpos($imagePath, '/images/') === 0) {
        return '/MVNO' . $imagePath;
    }
    // 파일명만 있는 경우 (확장자가 있고 슬래시가 없음)
    elseif (strpos($imagePath, '/') === false && preg_match('/\.(webp|jpg|jpeg|png|gif)$/i', $imagePath)) {
        return '/MVNO/uploads/events/' . $imagePath;
    }
    // 상대 경로인데 파일명이 아닌 경우
    elseif (strpos($imagePath, '/') !== 0) {
        return '/MVNO/' . $imagePath;
    }
    
    return $imagePath;
}

// 메인 페이지 설정 가져오기
$home_settings = getHomeSettings();

// 메인 배너 이벤트 가져오기 (3개)
$main_banner_events = [];
if (!empty($home_settings['main_banners']) && is_array($home_settings['main_banners'])) {
    foreach ($home_settings['main_banners'] as $event_id) {
        $event = getEventById($event_id);
        if ($event) {
            // 이미지 경로 정규화
            if (!empty($event['image'])) {
                $event['image'] = normalizeImagePathForDisplay($event['image']);
            }
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

// 사이트 전체 섹션 배너 가져오기
$site_large_banners = [];
if (!empty($home_settings['site_large_banners']) && is_array($home_settings['site_large_banners'])) {
    foreach ($home_settings['site_large_banners'] as $event_id) {
        $event = getEventById($event_id);
        if ($event) {
            // 이미지 경로 정규화
            if (!empty($event['image'])) {
                $event['image'] = normalizeImagePathForDisplay($event['image']);
            }
            $site_large_banners[] = $event;
        }
    }
}

$site_small_banners = [];
if (!empty($home_settings['site_small_banners']) && is_array($home_settings['site_small_banners'])) {
    foreach ($home_settings['site_small_banners'] as $event_id) {
        $event = getEventById($event_id);
        if ($event) {
            // 이미지 경로 정규화
            if (!empty($event['image'])) {
                $event['image'] = normalizeImagePathForDisplay($event['image']);
            }
            $site_small_banners[] = $event;
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
            <?php if (!empty($site_large_banners) || !empty($site_small_banners)): ?>
                <div class="main-banner-grid">
                    <!-- 왼쪽: 큰 배너 (롤링) -->
                    <div class="main-banner-left">
                        <?php if (!empty($site_large_banners)): ?>
                            <div class="main-banner-carousel" id="main-banner-carousel">
                                <?php foreach ($site_large_banners as $index => $banner): 
                                    $banner_image = $banner['image'] ?? '';
                                    $banner_title = $banner['title'] ?? '';
                                    $banner_id = $banner['id'] ?? '';
                                    
                                    // 이벤트 상세 페이지 링크 생성
                                    if (!empty($banner_id)) {
                                        $banner_link = '/MVNO/event/event-detail.php?id=' . urlencode($banner_id);
                                    } else {
                                        $banner_link = $banner['link'] ?? '#';
                                    }
                                    
                                    if (empty($banner_image)) continue;
                                ?>
                                    <div class="main-banner-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <a href="<?php echo htmlspecialchars($banner_link); ?>" class="main-banner-card large">
                                            <img src="<?php echo htmlspecialchars($banner_image); ?>" 
                                                 alt="<?php echo htmlspecialchars($banner_title); ?>" 
                                                 class="main-banner-image">
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (count($site_large_banners) > 1): ?>
                                    <div class="main-banner-controls">
                                        <button class="main-banner-prev" onclick="changeMainBanner(-1)">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14 20L10 12L14 4" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                        <button class="main-banner-next" onclick="changeMainBanner(1)">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M10 20L14 12L10 4" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="main-banner-indicators">
                                        <?php foreach ($site_large_banners as $index => $banner): ?>
                                            <span class="main-banner-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                  onclick="goToMainBanner(<?php echo $index; ?>)"></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
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
                        <?php if (isset($site_small_banners[0])): 
                            $banner = $site_small_banners[0];
                            $banner_image = $banner['image'] ?? '';
                            $banner_title = $banner['title'] ?? '';
                            $banner_id = $banner['id'] ?? '';
                            
                            // 이벤트 상세 페이지 링크 생성
                            if (!empty($banner_id)) {
                                $banner_link = '/MVNO/event/event-detail.php?id=' . urlencode($banner_id);
                            } else {
                                $banner_link = $banner['link'] ?? '#';
                            }
                        ?>
                            <a href="<?php echo htmlspecialchars($banner_link); ?>" class="main-banner-card small">
                                <img src="<?php echo htmlspecialchars($banner_image); ?>" 
                                     alt="<?php echo htmlspecialchars($banner_title); ?>" 
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
                        
                        <?php if (isset($site_small_banners[1])): 
                            $banner = $site_small_banners[1];
                            $banner_image = $banner['image'] ?? '';
                            $banner_title = $banner['title'] ?? '';
                            $banner_id = $banner['id'] ?? '';
                            
                            // 이벤트 상세 페이지 링크 생성
                            if (!empty($banner_id)) {
                                $banner_link = '/MVNO/event/event-detail.php?id=' . urlencode($banner_id);
                            } else {
                                $banner_link = $banner['link'] ?? '#';
                            }
                        ?>
                            <a href="<?php echo htmlspecialchars($banner_link); ?>" class="main-banner-card small">
                                <img src="<?php echo htmlspecialchars($banner_image); ?>" 
                                     alt="<?php echo htmlspecialchars($banner_title); ?>" 
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
                
                
                <!-- 상품 목록 -->
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

/* 메인 배너 캐러셀 */
.main-banner-carousel {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    border-radius: 12px;
    overflow: hidden;
}

.main-banner-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
}

.main-banner-slide.active {
    opacity: 1;
    z-index: 1;
}

.main-banner-controls {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 100%;
    display: flex;
    justify-content: space-between;
    padding: 0;
    z-index: 2;
    pointer-events: none;
}

.main-banner-prev {
    margin-left: 1rem;
}

.main-banner-next {
    margin-right: 1rem;
}

.main-banner-prev,
.main-banner-next {
    background: rgba(255, 255, 255, 0.5);
    color: #1a1a1a;
    border: none;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: all;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.main-banner-prev svg,
.main-banner-next svg {
    width: 28px;
    height: 28px;
    transition: transform 0.3s ease;
}

.main-banner-prev:hover,
.main-banner-next:hover {
    background: rgba(255, 255, 255, 0.7);
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.main-banner-prev:hover svg,
.main-banner-next:hover svg {
    transform: scale(1.15);
}

.main-banner-prev:active,
.main-banner-next:active {
    transform: scale(0.95);
}

.main-banner-indicators {
    position: absolute;
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 0.5rem;
    z-index: 2;
}

.main-banner-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: background 0.2s;
}

.main-banner-dot.active {
    background: white;
}

.main-banner-card.small {
    aspect-ratio: 16 / 9;
    width: 100%;
    flex: 0 0 auto;
}

/* 작은 배너 2개가 큰 배너 높이에 맞추기 */
.main-banner-right {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    height: 100%;
}

.main-banner-right .main-banner-card.small {
    aspect-ratio: 16 / 9;
    width: 100%;
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
    aspect-ratio: 16 / 9;
    width: 100%;
    flex: 0 0 auto;
}

.main-banner-right .main-banner-placeholder.small {
    aspect-ratio: 16 / 9;
    width: 100%;
    height: auto;
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

<script>
// 배너 높이 자동 조정: 작은 배너 2개를 큰 배너 높이에 맞추고 gap 조절
document.addEventListener('DOMContentLoaded', function() {
    function adjustBannerHeights() {
        const bannerGrid = document.querySelector('.main-banner-grid');
        if (!bannerGrid) return;
        
        // 큰 배너 (캐러셀 또는 단일 배너)
        const largeBannerCarousel = bannerGrid.querySelector('.main-banner-left .main-banner-carousel');
        const largeBannerPlaceholder = bannerGrid.querySelector('.main-banner-left .main-banner-placeholder.large');
        const largeBanner = largeBannerCarousel || largeBannerPlaceholder;
        const rightContainer = bannerGrid.querySelector('.main-banner-right');
        const smallBanners = rightContainer ? rightContainer.querySelectorAll('.main-banner-card.small, .main-banner-placeholder.small') : [];
        
        if (!largeBanner || !rightContainer || smallBanners.length !== 2) return;
        
        // 큰 배너의 실제 높이 측정
        const largeBannerHeight = largeBanner.offsetHeight;
        
        // 작은 배너의 폭 (오른쪽 영역의 폭)
        const smallBannerWidth = rightContainer.offsetWidth;
        
        // 작은 배너가 16:9 비율을 유지할 때의 높이 계산
        const smallBannerHeight16to9 = smallBannerWidth / (16 / 9);
        
        // 작은 배너 2개의 총 높이 (16:9 비율 기준)
        const totalSmallBannerHeight = smallBannerHeight16to9 * 2;
        
        // 필요한 gap 계산 (큰 배너 높이에서 작은 배너 2개 높이를 뺀 값)
        const requiredGap = largeBannerHeight - totalSmallBannerHeight;
        
        // gap 설정 (최소 8px 이상)
        const finalGap = Math.max(requiredGap, 8);
        rightContainer.style.gap = finalGap + 'px';
        
        // 작은 배너는 16:9 비율 유지 (aspect-ratio 사용)
        smallBanners.forEach(banner => {
            banner.style.aspectRatio = '16 / 9';
            banner.style.height = 'auto';
        });
    }
    
    // 초기 실행 (약간의 지연을 두어 DOM 렌더링 완료 후 실행)
    setTimeout(adjustBannerHeights, 50);
    
    // 리사이즈 시 재조정
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(adjustBannerHeights, 100);
    });
    
    // 이미지 로드 후 재조정
    const bannerImages = document.querySelectorAll('.main-banner-image');
    bannerImages.forEach(img => {
        if (img.complete) {
            setTimeout(adjustBannerHeights, 50);
        } else {
            img.addEventListener('load', function() {
                setTimeout(adjustBannerHeights, 50);
            }, { once: true });
        }
    });
});

// 메인 배너 캐러셀
let mainBannerCurrentIndex = 0;
let mainBannerInterval = null;

function initMainBannerCarousel() {
    const carousel = document.getElementById('main-banner-carousel');
    if (!carousel) return;
    
    const slides = carousel.querySelectorAll('.main-banner-slide');
    if (slides.length <= 1) return;
    
    // 자동 롤링 시작
    mainBannerInterval = setInterval(() => {
        changeMainBanner(1);
    }, 2000); // 2초마다 변경
}

function changeMainBanner(direction) {
    const carousel = document.getElementById('main-banner-carousel');
    if (!carousel) return;
    
    const slides = carousel.querySelectorAll('.main-banner-slide');
    const dots = carousel.querySelectorAll('.main-banner-dot');
    
    if (slides.length === 0) return;
    
    // 현재 슬라이드 숨기기
    slides[mainBannerCurrentIndex].classList.remove('active');
    if (dots[mainBannerCurrentIndex]) {
        dots[mainBannerCurrentIndex].classList.remove('active');
    }
    
    // 다음 인덱스 계산
    mainBannerCurrentIndex += direction;
    if (mainBannerCurrentIndex >= slides.length) {
        mainBannerCurrentIndex = 0;
    } else if (mainBannerCurrentIndex < 0) {
        mainBannerCurrentIndex = slides.length - 1;
    }
    
    // 다음 슬라이드 표시
    slides[mainBannerCurrentIndex].classList.add('active');
    if (dots[mainBannerCurrentIndex]) {
        dots[mainBannerCurrentIndex].classList.add('active');
    }
    
    // 자동 롤링 재시작
    if (mainBannerInterval) {
        clearInterval(mainBannerInterval);
    }
    mainBannerInterval = setInterval(() => {
        changeMainBanner(1);
    }, 2000);
}

function goToMainBanner(index) {
    const carousel = document.getElementById('main-banner-carousel');
    if (!carousel) return;
    
    const slides = carousel.querySelectorAll('.main-banner-slide');
    const dots = carousel.querySelectorAll('.main-banner-dot');
    
    if (index < 0 || index >= slides.length) return;
    
    // 현재 슬라이드 숨기기
    slides[mainBannerCurrentIndex].classList.remove('active');
    if (dots[mainBannerCurrentIndex]) {
        dots[mainBannerCurrentIndex].classList.remove('active');
    }
    
    // 선택한 슬라이드 표시
    mainBannerCurrentIndex = index;
    slides[mainBannerCurrentIndex].classList.add('active');
    if (dots[mainBannerCurrentIndex]) {
        dots[mainBannerCurrentIndex].classList.add('active');
    }
    
    // 자동 롤링 재시작
    if (mainBannerInterval) {
        clearInterval(mainBannerInterval);
    }
    mainBannerInterval = setInterval(() => {
        changeMainBanner(1);
    }, 2000);
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    initMainBannerCarousel();
});
</script>

<?php
// 푸터 포함
include 'includes/footer.php';
?>

<?php
// 메인페이지 공지사항 새창 표시
$mainNotice = getMainPageNotice();
// 디버깅: 메인공지 정보 확인 (개발 환경에서만)
if (isset($_GET['debug_notice']) && $_GET['debug_notice'] == '1') {
    echo "<!-- 메인공지 디버깅 정보:\n";
    echo "getMainPageNotice() 반환값: " . ($mainNotice ? "있음" : "없음") . "\n";
    if ($mainNotice) {
        echo "ID: " . htmlspecialchars($mainNotice['id'] ?? 'N/A') . "\n";
        echo "제목: " . htmlspecialchars($mainNotice['title'] ?? 'N/A') . "\n";
        echo "show_on_main: " . ($mainNotice['show_on_main'] ?? 'N/A') . "\n";
        echo "image_url: " . htmlspecialchars($mainNotice['image_url'] ?? '없음') . "\n";
        echo "start_at: " . htmlspecialchars($mainNotice['start_at'] ?? 'NULL') . "\n";
        echo "end_at: " . htmlspecialchars($mainNotice['end_at'] ?? 'NULL') . "\n";
        echo "쿠키 확인: " . (isset($_COOKIE['notice_viewed_' . $mainNotice['id']]) ? "설정됨" : "없음") . "\n";
    }
    echo "-->";
}
if ($mainNotice && !empty($mainNotice['image_url']) && !isset($_COOKIE['notice_viewed_' . $mainNotice['id']])): ?>
<div id="mainNoticeModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.4); z-index: 10000; align-items: center; justify-content: center;">
    <div style="position: relative; width: 90%; max-width: 800px; display: flex; flex-direction: column; align-items: center; border-radius: 12px; overflow: hidden;">
        <!-- 이미지 영역 (클릭 시 링크 이동) -->
        <?php if (!empty($mainNotice['link_url'])): ?>
            <div id="mainNoticeImage" style="display: block; width: 100%; cursor: pointer; position: relative;">
                <img src="<?php echo htmlspecialchars($mainNotice['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($mainNotice['title']); ?>" 
                     style="width: 100%; height: auto; display: block; border-radius: 12px 12px 0 0;">
            </div>
        <?php else: ?>
            <img src="<?php echo htmlspecialchars($mainNotice['image_url']); ?>" 
                 alt="<?php echo htmlspecialchars($mainNotice['title']); ?>" 
                 style="width: 100%; height: auto; display: block; border-radius: 12px 12px 0 0;">
        <?php endif; ?>
        
        <!-- 하단 버튼 영역 -->
        <div style="width: 100%; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.5); border-radius: 0 0 12px 12px;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: white; cursor: pointer;">
                <input type="checkbox" id="dontShowAgain" style="width: auto; margin: 0;">
                <span>오늘 그만보기</span>
            </label>
            <button type="button" id="closeMainNoticeBtn" style="padding: 8px 20px; background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 6px; font-weight: 500; font-size: 14px; cursor: pointer; transition: background 0.2s;">
                창닫기
            </button>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('mainNoticeModal');
    const closeBtn = document.getElementById('closeMainNoticeBtn');
    const dontShowAgain = document.getElementById('dontShowAgain');
    const noticeImage = document.getElementById('mainNoticeImage');
    const noticeId = '<?php echo htmlspecialchars($mainNotice['id']); ?>';
    const linkUrl = '<?php echo !empty($mainNotice['link_url']) ? htmlspecialchars($mainNotice['link_url'], ENT_QUOTES) : ''; ?>';
    
    // 현재 스크롤 위치 저장
    let scrollPosition = 0;
    
    // 모달 표시
    if (modal) {
        // 현재 스크롤 위치 저장
        scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        
        modal.style.display = 'flex';
        // body 스크롤 고정
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = '-' + scrollPosition + 'px';
        document.body.style.width = '100%';
    }
    
    function closeModal(saveCookie, redirectUrl) {
        if (modal) {
            modal.style.display = 'none';
            // body 스크롤 복원
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            // 스크롤 위치 복원
            window.scrollTo(0, scrollPosition);
        }
        
        // 쿠키 설정 (체크박스가 체크되었거나 saveCookie가 true일 때)
        if (saveCookie || (dontShowAgain && dontShowAgain.checked)) {
            const expires = new Date();
            expires.setHours(23, 59, 59, 999); // 오늘 자정까지
            document.cookie = 'notice_viewed_' + noticeId + '=1; expires=' + expires.toUTCString() + '; path=/';
        }
        
        // 리다이렉트 URL이 있으면 이동
        if (redirectUrl) {
            window.location.href = redirectUrl;
        }
    }
    
    // 이미지 클릭 시 링크로 이동
    if (noticeImage && linkUrl) {
        noticeImage.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal(true, linkUrl); // 모달 닫고 쿠키 설정 후 리다이렉트
        });
    }
    
    // 오늘 그만보기 체크박스 클릭 시 즉시 모달 닫기
    if (dontShowAgain) {
        dontShowAgain.addEventListener('change', function() {
            if (this.checked) {
                // 체크박스가 선택되면 즉시 모달 닫기 및 쿠키 설정
                closeModal(true);
            }
        });
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            closeModal(false); // 닫기 버튼은 쿠키 설정 안 함
        });
        // 호버 효과
        closeBtn.addEventListener('mouseenter', function() {
            this.style.background = 'rgba(255, 255, 255, 0.3)';
        });
        closeBtn.addEventListener('mouseleave', function() {
            this.style.background = 'rgba(255, 255, 255, 0.2)';
        });
    }
    
    // 배경 클릭 시 닫기
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(false); // 배경 클릭은 쿠키 설정 안 함
            }
        });
    }
    
    // ESC 키로 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
            closeModal(false); // ESC 키는 쿠키 설정 안 함
        }
    });
});
</script>
<?php endif; ?>

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
