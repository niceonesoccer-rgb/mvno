<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'event';

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div class="event-container">
        <h2 class="event-main-title">진행 중 이벤트</h2>
        
        <!-- 탭 메뉴 및 전체 이벤트 -->
        <section class="event-section event-tab-section">
            <div class="c-tabmenu-wrap">
                <div class="c-tabmenu-link-wrap">
                    <div class="c-tabmenu-list">
                        <ul class="tab-list">
                            <li class="tab-item is-active">
                                <button role="tab" aria-selected="true" tabindex="0" class="tab-button">전체</button>
                            </li>
                            <li class="tab-item">
                                <button role="tab" aria-selected="false" tabindex="-1" class="tab-button">요금제</button>
                            </li>
                            <li class="tab-item">
                                <button role="tab" aria-selected="false" tabindex="-1" class="tab-button">프로모션</button>
                            </li>
                            <li class="tab-item">
                                <button role="tab" aria-selected="false" tabindex="-1" class="tab-button">제휴카드</button>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="c-tabcontent-box">
                    <ul class="event-grid all-events">
                        <li class="event-card">
                            <a href="#none" class="event-link">
                                <span class="option-flag">
                                    <small class="c-flag new-flag">최신</small>
                                </span>
                                <div class="img-area gradient">
                                    <img src="https://www.lguplus.com/static-evet/pc-contents/images/event/eh/20251128-050334-247-wV0lgtCf.png" alt="12월 온라인스토어 연말 감사제" loading="lazy" class="event-image">
                                </div>
                                <div class="text-area">
                                    <p class="tit">12월 온라인스토어 연말 감사제</p>
                                    <p class="date">2025-11-28 ~ 2025-12-30</p>
                                </div>
                            </a>
                        </li>
                        
                        <li class="event-card">
                            <a href="#none" class="event-link">
                                <span class="option-flag">
                                    <small class="c-flag new-flag">최신</small>
                                </span>
                                <div class="img-area gradient">
                                    <img src="https://www.lguplus.com/static-evet/pc-contents/images/evet/eh/20251128-110152-523-16CPrJH5.png" alt="소상공인 한정 최대 474만원 혜택" loading="lazy" class="event-image">
                                </div>
                                <div class="text-area">
                                    <p class="tit">소상공인 한정 최대 474만원 혜택</p>
                                    <p class="date">2025-11-27 ~ 2026-02-26</p>
                                </div>
                            </a>
                        </li>
                        
                        <li class="event-card">
                            <a href="#none" class="event-link">
                                <span class="option-flag">
                                    <small class="c-flag new-flag">최신</small>
                                </span>
                                <div class="img-area gradient">
                                    <img src="https://www.lguplus.com/static-evet/pc-contents/images/event/eh/20251124-034110-926-lh1WGxbP.png" alt="원하는 기간만큼 사용하는 선불인터넷" loading="lazy" class="event-image">
                                </div>
                                <div class="text-area">
                                    <p class="tit">원하는 기간만큼 사용하는 선불인터넷</p>
                                    <p class="date">2025-11-25 ~ 2025-12-31</p>
                                </div>
                            </a>
                        </li>
                        
                        <li class="event-card">
                            <a href="#none" class="event-link">
                                <span class="option-flag">
                                    <small class="c-flag dDay-flag">마감임박</small>
                                </span>
                                <div class="img-area gradient">
                                    <img src="https://www.lguplus.com/static-evet/pc-contents/images/evet/eh/20251121-051824-734-JzdBa5S5.jpg" alt="U+모바일tv 누구나 무료 영화" loading="lazy" class="event-image">
                                </div>
                                <div class="text-area">
                                    <p class="tit">U+모바일tv 누구나 무료 영화</p>
                                    <p class="date">2025-11-24 ~ 2025-11-30</p>
                                </div>
                            </a>
                        </li>
                        
                        <li class="event-card">
                            <a href="#none" class="event-link">
                                <span class="option-flag"></span>
                                <div class="img-area gradient">
                                    <img src="https://www.lguplus.com/static-evet/pc-contents/images/evet/eh/20251119-010451-458-o1jaqW7H.jpg" alt="U+tv모아에서 드리는 선물 받으세요." loading="lazy" class="event-image">
                                </div>
                                <div class="text-area">
                                    <p class="tit">U+tv모아에서 드리는 선물 받으세요.</p>
                                    <p class="date">2025-11-20 ~ 2025-12-21</p>
                                </div>
                            </a>
                        </li>
                        
                        <li class="event-card">
                            <a href="#none" class="event-link">
                                <span class="option-flag">
                                    <small class="c-flag dDay-flag">마감임박</small>
                                </span>
                                <div class="img-area gradient">
                                    <img src="https://www.lguplus.com/static-evet/pc-contents/images/evet/eh/20251119-025033-718-rifwOua9.png" alt="U+one [혜택]에 놀러오세요" loading="lazy" class="event-image">
                                </div>
                                <div class="text-area">
                                    <p class="tit">U+one [혜택]에 놀러오세요</p>
                                    <p class="date">2025-11-20 ~ 2025-11-30</p>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
</main>

<style>
/* 이벤트 페이지 컨테이너 */
.event-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

/* 메인 제목 */
.event-main-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 2rem;
    text-align: center;
}

/* 섹션 제목 */
.event-section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 1.5rem;
}

.event-section {
    margin-bottom: 3rem;
}

.event-tab-section {
    padding-top: 0;
}

/* 이벤트 그리드 */
.event-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
}

/* 이벤트 카드 */
.event-card {
    position: relative;
    background: #ffffff;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.event-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
}

.event-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

/* 옵션 플래그 */
.option-flag {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    z-index: 10;
}

.c-flag {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1.2;
}

.dDay-flag {
    background-color: #ef4444;
    color: #ffffff;
}

.new-flag {
    background-color: #6366f1;
    color: #ffffff;
}

/* 이미지 영역 */
.img-area {
    position: relative;
    width: 100%;
    padding-top: 56.25%; /* 16:9 비율 */
    overflow: hidden;
    background: #f3f4f6;
}

.img-area.gradient::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 40%;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.3), transparent);
    pointer-events: none;
}

.event-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.event-card:hover .event-image {
    transform: scale(1.05);
}

/* 텍스트 영역 */
.text-area {
    padding: 1rem;
}

.flag-area {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.blue-flag {
    background-color: #dbeafe;
    color: #1e40af;
}

.gray-flag {
    background-color: #f3f4f6;
    color: #6b7280;
}

.tit {
    font-size: 1rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0.5rem 0;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.flag-n-tag {
    margin: 0.5rem 0;
}

.flag-n-tag em {
    font-size: 0.875rem;
    color: #6b7280;
    font-style: normal;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.date {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0.5rem 0 0 0;
}

/* 탭 메뉴 */
.c-tabmenu-wrap {
    margin-bottom: 2rem;
}

.c-tabmenu-link-wrap {
    margin-bottom: 2rem;
}

.tab-list {
    display: flex;
    gap: 0.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
    flex-wrap: wrap;
}

.tab-item {
    margin: 0;
}

.tab-button {
    padding: 0.5rem 1.25rem;
    background: transparent;
    border: 1px solid #e5e7eb;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    outline: none;
}

.tab-button:focus {
    outline: none;
    box-shadow: none;
}

.tab-button:active {
    outline: none;
    box-shadow: none;
}

.tab-button:hover {
    background-color: #f3f4f6;
    color: #374151;
}

.tab-item.is-active .tab-button {
    color: #ec4899;
    font-weight: 600;
    border: 1px solid #ec4899;
}

/* 모바일 반응형 */
@media (max-width: 767px) {
    .event-container {
        padding: 1rem 0.75rem;
    }
    
    .event-main-title {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .event-section-title {
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }
    
    .event-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .tab-list {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    
    .tab-list::-webkit-scrollbar {
        display: none;
    }
    
    .tab-item {
        flex-shrink: 0;
    }
    
    .tab-button {
        padding: 0.625rem 1rem;
        font-size: 0.8125rem;
    }
    
    .text-area {
        padding: 0.875rem;
    }
    
    .tit {
        font-size: 0.9375rem;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    .event-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .event-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabItems = document.querySelectorAll('.tab-item');
    
    tabButtons.forEach((button, index) => {
        button.addEventListener('click', function() {
            // 모든 탭에서 is-active 클래스 제거
            tabItems.forEach(item => {
                item.classList.remove('is-active');
            });
            
            // 클릭한 탭에 is-active 클래스 추가
            tabItems[index].classList.add('is-active');
            
            // aria-selected 속성 업데이트
            tabButtons.forEach((btn, i) => {
                btn.setAttribute('aria-selected', i === index ? 'true' : 'false');
                btn.setAttribute('tabindex', i === index ? '0' : '-1');
            });
        });
    });
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

