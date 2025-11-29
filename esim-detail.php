<?php
// 현재 페이지 설정
$current_page = 'esim';

// URL 파라미터에서 국가 정보 가져오기
$countryName = isset($_GET['country']) ? $_GET['country'] : '일본';
$flagImage = isset($_GET['flag']) ? $_GET['flag'] : '179d30e5-6a69-ee11-bbf0-28187860d6d3.svg';

// 헤더 포함
include 'includes/header.php';
?>

<main class="main-content">
    <div class="select-page-body top-54 p-16">
        <!-- 1단계: eSIM/USIM 선택 -->
        <section class="step-section" data-step="1">
            <div class="switch-wrapper w-full flex">
                <div class="switch-item selected" data-type="esim">eSIM</div>
                <div class="switch-item" data-type="usim">USIM</div>
            </div>
        </section>

        <!-- 2단계: 기간 선택 -->
        <section class="step-section" data-step="2">
            <h1 class="title mt-30 mb-20">
                <span class="country-name"><?php echo htmlspecialchars($countryName); ?></span>에서<br>
                <span class="main-color selected-days">4일</span> 동안 사용하시나요?
            </h1>
            <div class="flex gap-8">
                <button class="A-button stroke day-btn" data-days="3" style="min-width: 64px;">3일</button>
                <button class="A-button default day-btn" data-days="4" style="min-width: 64px;">4일</button>
                <button class="A-button stroke day-btn" data-days="5" style="min-width: 64px;">5일</button>
                <button class="A-button stroke day-btn" data-days="custom">기간 선택</button>
            </div>
        </section>

        <!-- 배너 -->
        <div class="banner brand mt-16">
            <div class="text-left">🎉<?php echo htmlspecialchars($countryName); ?> 여행 필수템! 로컬 eSIM, USIM 출시!</div>
        </div>

        <!-- 5단계: 추천 상품 (선택한 조건에 따라 표시) -->
        <section class="step-section recommend-section mt-24" data-step="5">
            <h2 class="recommend-title mb-12">유심사 추천 상품</h2>
            <div class="card">
                <div class="card-title p-16">
                    <div class="flex flex-align-center gap-12">
                        <img width="40" height="40" class="flag" src="https://asset.usimsa.com/images/country/<?php echo htmlspecialchars($flagImage); ?>.svg" alt="<?php echo htmlspecialchars($countryName); ?>">
                        <div class="product-name flex flex-column flex-justify-center">
                            <span><?php echo htmlspecialchars($countryName); ?></span>
                        </div>
                    </div>
                </div>
                <div class="card-content p-16">
                    <div class="flex flex-column gap-8">
                        <div class="flex flex-space-between">
                            <span>기간/망</span>
                            <span class="selected-period-network">4일 / 로밍망</span>
                        </div>
                        <div class="flex flex-space-between">
                            <span>데이터</span>
                            <span class="selected-data">완전 무제한</span>
                        </div>
                        <div class="price flex flex-space-between flex-align-center">
                            <span class="label">금액</span>
                            <span class="won selected-price">12,500원</span>
                        </div>
                    </div>
                    <div class="flex gap-8 mt-16">
                        <button class="cta-button tertiary lg flex-1">
                            <div class="flex flex-align-center flex-justify-center">
                                <span>상세보기</span>
                            </div>
                        </button>
                        <button class="cta-button primary lg flex-2">
                            <div class="flex flex-align-center flex-justify-center">
                                <span>구매하기</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3단계: 망 종류 선택 -->
        <section class="step-section options-section mt-16" data-step="3">
            <div class="el-tabs el-tabs--top">
                <div class="el-tabs__header is-top">
                    <div class="el-tabs__nav-wrap is-top">
                        <div class="el-tabs__nav-scroll">
                            <div class="el-tabs__nav is-top is-stretch" role="tablist">
                                <div class="el-tabs__active-bar is-top"></div>
                                <div class="el-tabs__item is-top is-active network-tab" id="tab-roaming" data-network="roaming" role="tab" aria-selected="true" tabindex="0">로밍망</div>
                                <div class="el-tabs__item is-top network-tab" id="tab-local" data-network="local" role="tab" aria-selected="false" tabindex="-1">로컬망</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="el-tabs__content">
                    <div id="pane-roaming" class="el-tab-pane" role="tabpanel" aria-hidden="false"></div>
                    <div id="pane-local" class="el-tab-pane" role="tabpanel" aria-hidden="true" style="display: none;"></div>
                </div>
            </div>

            <!-- 4단계: 옵션 선택 -->
            <div class="options mt-20" data-step="4">
                <div class="options-list">
                    <div class="flex flex-column gap-8">
                        <div class="option-box flex flex-space-between flex-align-center gap-8 isUnlimitedCapacity option-item" data-option="unlimited" data-price="12500">
                            <span class="name">완전 무제한</span>
                            <span class="price">12,500원</span>
                        </div>
                        <div class="option-box flex flex-space-between flex-align-center gap-8 option-item" data-option="500mb" data-price="2500">
                            <span class="name">매일 500MB 이후 저속 무제한</span>
                            <span class="price">2,500원</span>
                        </div>
                        <div class="option-box flex flex-space-between flex-align-center gap-8 option-item" data-option="1gb" data-price="3600">
                            <span class="name">매일 1GB 이후 저속 무제한</span>
                            <span class="price">3,600원</span>
                        </div>
                        <div class="option-box flex flex-space-between flex-align-center gap-8 option-item" data-option="2gb" data-price="6200">
                            <span class="name">매일 2GB 이후 저속 무제한</span>
                            <span class="price">6,200원</span>
                        </div>
                        <div class="option-box flex flex-space-between flex-align-center gap-8 option-item" data-option="3gb" data-price="8400">
                            <span class="name">매일 3GB 이후 저속 무제한</span>
                            <span class="price">8,400원</span>
                        </div>
                        <div class="option-box flex flex-space-between flex-align-center gap-8 option-item" data-option="4gb" data-price="8600">
                            <span class="name">매일 4GB 이후 저속 무제한</span>
                            <span class="price">8,600원</span>
                        </div>
                        <div class="option-box flex flex-space-between flex-align-center gap-8 option-item" data-option="5gb" data-price="8900">
                            <span class="name">매일 5GB 이후 저속 무제한</span>
                            <span class="price">8,900원</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<style>
/* 기본 스타일 */
.select-page-body {
    max-width: 600px;
    margin: 0 auto;
    padding: 1rem;
}

.top-54 {
    padding-top: 3.375rem;
}

.p-16 {
    padding: 1rem;
}

.mt-16 {
    margin-top: 1rem;
}

.mt-20 {
    margin-top: 1.25rem;
}

.mt-24 {
    margin-top: 1.5rem;
}

.mt-30 {
    margin-top: 1.875rem;
}

.mb-12 {
    margin-bottom: 0.75rem;
}

.mb-20 {
    margin-bottom: 1.25rem;
}

.gap-8 {
    gap: 0.5rem;
}

.gap-12 {
    gap: 0.75rem;
}

.w-full {
    width: 100%;
}

.flex {
    display: flex;
}

.flex-column {
    flex-direction: column;
}

.flex-align-center {
    align-items: center;
}

.flex-justify-center {
    justify-content: center;
}

.flex-space-between {
    justify-content: space-between;
}

/* 1단계: eSIM/USIM 스위치 */
.switch-wrapper {
    background-color: #f3f4f6;
    border-radius: 0.5rem;
    padding: 0.25rem;
    display: flex;
    gap: 0.25rem;
}

.switch-item {
    flex: 1;
    padding: 0.75rem 1rem;
    text-align: center;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #6b7280;
    background-color: transparent;
}

.switch-item.selected {
    background-color: #ffffff;
    color: #1a1a1a;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* 2단계: 기간 선택 */
.title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1.5;
}

.main-color {
    color: #ec4899;
}

.A-button {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid #e5e7eb;
    background-color: #ffffff;
    color: #374151;
}

.A-button.stroke {
    background-color: transparent;
}

.A-button.default {
    background-color: #ec4899;
    color: #ffffff;
    border-color: #ec4899;
}

.A-button:hover {
    opacity: 0.8;
}

/* 배너 */
.banner.brand {
    background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
    color: #ffffff;
    padding: 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.text-left {
    text-align: left;
}

/* 5단계: 추천 상품 카드 */
.recommend-section {
    background-color: #ffffff;
}

.recommend-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a1a1a;
}

.card {
    background-color: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-title {
    border-bottom: 1px solid #f3f4f6;
}

.flag {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.product-name {
    font-size: 1rem;
    font-weight: 600;
    color: #1a1a1a;
}

.card-content {
    background-color: #ffffff;
}

.price .label {
    font-size: 0.875rem;
    color: #6b7280;
}

.price .won {
    font-size: 1.25rem;
    font-weight: 700;
    color: #ec4899;
}

/* 버튼 */
.cta-button {
    padding: 0.875rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.cta-button.tertiary {
    background-color: #f3f4f6;
    color: #374151;
}

.cta-button.tertiary:hover {
    background-color: #e5e7eb;
}

.cta-button.primary {
    background-color: #ec4899;
    color: #ffffff;
}

.cta-button.primary:hover {
    background-color: #be185d;
}

.cta-button.lg {
    padding: 1rem 1.5rem;
}

.flex-1 {
    flex: 1;
}

.flex-2 {
    flex: 2;
}

/* 3단계: 망 종류 탭 */
.el-tabs {
    border-bottom: 1px solid #e5e7eb;
}

.el-tabs__nav {
    position: relative;
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
}

.el-tabs__item {
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    border-bottom: 2px solid transparent;
}

.el-tabs__item.is-active {
    color: #ec4899;
    font-weight: 600;
}

.el-tabs__active-bar {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 2px;
    background-color: #ec4899;
    transition: transform 0.3s ease, width 0.3s ease;
}

/* 4단계: 옵션 선택 */
.options {
    margin-top: 1.25rem;
}

.option-box {
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background-color: #ffffff;
    cursor: pointer;
    transition: all 0.2s ease;
}

.option-box:hover {
    border-color: #ec4899;
    background-color: #fdf2f8;
}

.option-box.selected {
    border-color: #ec4899;
    background-color: #fdf2f8;
    border-width: 2px;
}

.option-box .name {
    font-size: 0.875rem;
    color: #1a1a1a;
    font-weight: 500;
}

.option-box .price {
    font-size: 0.875rem;
    color: #ec4899;
    font-weight: 600;
}

@media (max-width: 767px) {
    .select-page-body {
        padding: 0.75rem;
    }
    
    .title {
        font-size: 1.25rem;
    }
    
    .A-button {
        padding: 0.625rem 0.75rem;
        font-size: 0.8125rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 선택 상태 관리
    const selection = {
        type: 'esim', // esim or usim
        days: 4,
        network: 'roaming', // roaming or local
        option: 'unlimited',
        price: 12500
    };

    // 1단계: eSIM/USIM 스위치
    const switchItems = document.querySelectorAll('.switch-item');
    switchItems.forEach(item => {
        item.addEventListener('click', function() {
            switchItems.forEach(s => s.classList.remove('selected'));
            this.classList.add('selected');
            selection.type = this.getAttribute('data-type');
            updateRecommendation();
        });
    });

    // 2단계: 기간 선택
    const dayButtons = document.querySelectorAll('.day-btn');
    const selectedDaysEl = document.querySelector('.selected-days');
    
    dayButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            dayButtons.forEach(b => {
                b.classList.remove('default');
                b.classList.add('stroke');
            });
            this.classList.remove('stroke');
            this.classList.add('default');
            
            const days = this.getAttribute('data-days');
            selection.days = days === 'custom' ? 'custom' : parseInt(days);
            selectedDaysEl.textContent = days === 'custom' ? '기간 선택' : days + '일';
            updateRecommendation();
        });
    });

    // 3단계: 망 종류 탭
    const networkTabs = document.querySelectorAll('.network-tab');
    const tabPanes = document.querySelectorAll('.el-tab-pane');
    const activeBar = document.querySelector('.el-tabs__active-bar');
    
    networkTabs.forEach((tab, index) => {
        tab.addEventListener('click', function() {
            // 탭 활성화
            networkTabs.forEach(t => {
                t.classList.remove('is-active');
                t.setAttribute('aria-selected', 'false');
                t.setAttribute('tabindex', '-1');
            });
            this.classList.add('is-active');
            this.setAttribute('aria-selected', 'true');
            this.setAttribute('tabindex', '0');

            // 패널 전환
            tabPanes.forEach(pane => {
                pane.style.display = 'none';
                pane.setAttribute('aria-hidden', 'true');
            });
            const targetPane = document.querySelector('#' + this.id.replace('tab-', 'pane-'));
            if (targetPane) {
                targetPane.style.display = 'block';
                targetPane.setAttribute('aria-hidden', 'false');
            }

            // 활성 바 이동
            const tabWidth = this.offsetWidth;
            const tabLeft = this.offsetLeft;
            activeBar.style.width = tabWidth + 'px';
            activeBar.style.transform = 'translateX(' + tabLeft + 'px)';

            selection.network = this.getAttribute('data-network');
            updateRecommendation();
        });
    });

    // 4단계: 옵션 선택
    const optionItems = document.querySelectorAll('.option-item');
    optionItems.forEach(item => {
        item.addEventListener('click', function() {
            optionItems.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            
            selection.option = this.getAttribute('data-option');
            selection.price = parseInt(this.getAttribute('data-price'));
            updateRecommendation();
        });
    });

    // 추천 상품 업데이트 함수
    function updateRecommendation() {
        // 기간/망 업데이트
        const periodNetworkEl = document.querySelector('.selected-period-network');
        const networkText = selection.network === 'roaming' ? '로밍망' : '로컬망';
        const daysText = selection.days === 'custom' ? '기간 선택' : selection.days + '일';
        periodNetworkEl.textContent = daysText + ' / ' + networkText;

        // 데이터 옵션 업데이트
        const selectedOption = document.querySelector('.option-item.selected');
        if (selectedOption) {
            const dataText = selectedOption.querySelector('.name').textContent;
            document.querySelector('.selected-data').textContent = dataText;
        }

        // 가격 업데이트
        document.querySelector('.selected-price').textContent = selection.price.toLocaleString() + '원';
    }

    // 초기 옵션 선택
    document.querySelector('.option-item').classList.add('selected');
    updateRecommendation();
});
</script>

<?php
// 푸터 포함
include 'includes/footer.php';
?>

