<?php
// 현재 페이지 설정
$current_page = 'plans';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = false;

// 요금제 ID 가져오기
$plan_id = isset($_GET['id']) ? intval($_GET['id']) : 32627;

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content plan-reviews-page">
    <div class="content-layout">
        <!-- 리뷰 헤더 -->
        <div class="plan-review-header">
            <a href="/mvnos/쉐이크모바일?from=요금제상세" class="plan-review-mvno-link">
                <span class="plan-review-logo-text">쉐이크모바일</span>
            </a>
            <h2 class="section-title">리뷰</h2>
        </div>
        
        <div class="plan-review-summary">
            <div class="plan-review-rating">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.1479 3.1366C12.7138 2.12977 11.2862 2.12977 10.8521 3.1366L8.75804 7.99389L3.48632 8.48228C2.3937 8.58351 1.9524 9.94276 2.77717 10.6665L6.75371 14.156L5.58995 19.3138C5.34855 20.3837 6.50365 21.2235 7.44697 20.664L12 17.9635L16.553 20.664C17.4963 21.2235 18.6514 20.3837 18.4101 19.3138L17.2463 14.156L21.2228 10.6665C22.0476 9.94276 21.6063 8.58351 20.5137 8.48228L15.242 7.99389L13.1479 3.1366Z" fill="#FAB005"/>
                </svg>
                <span class="plan-review-rating-score">4.3</span>
            </div>
            <div class="plan-review-categories">
                <div class="plan-review-category">
                    <span class="plan-review-category-label">친절해요</span>
                    <span class="plan-review-category-score">4.2</span>
                    <div class="plan-review-stars">
                        <span>★★★★☆</span>
                    </div>
                </div>
                <div class="plan-review-category">
                    <span class="plan-review-category-label">개통 빨라요</span>
                    <span class="plan-review-category-score">4.5</span>
                    <div class="plan-review-stars">
                        <span>★★★★☆</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="plan-review-count-section">
            <span class="plan-review-count">11,533개</span>
        </div>

        <div class="plan-review-list" id="reviewList">
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">전*한</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">24일 전</span>
                </div>
                <p class="plan-review-content">개통이 다른 회사 보다 빠르고 좋습니다. 요금제 너무 좋아서 계속 사용할 예정 입니다. 친구, 가족 들에게 소개해주고 같이 사용 하는 중입니다. 강력 추천 합니다.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">오*열</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">29일 전</span>
                </div>
                <p class="plan-review-content">번호 이동이나 이동 후 개통도 휴일임에도 신청서 작성하고 쓰고 있던 esim으로 안내 문자에 따라 바로 즉시 개통할 수 있어 편리했습니다.(KT알띁A → KT알띁B)</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">최*연</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">41일 전</span>
                </div>
                <p class="plan-review-content">고객센터 개통 전화없이 모요 통해서 개통신청하고 편의점 바로유심 사서 끼우면 바로 개통됨..타 알뜰폰 통신사보다 개통과정, 통신속도,데이터량 불편함없이 사용함..쉐이크모바일 강추</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">김*수</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">52일 전</span>
                </div>
                <p class="plan-review-content">데이터 속도도 빠르고 가격도 합리적이에요. 특히 100GB 제공량이 넉넉해서 매달 데이터 걱정 없이 사용하고 있습니다. 주변 사람들한테도 추천했어요.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">이*민</span>
                    <div class="plan-review-stars">
                        <span>★★★★☆</span>
                    </div>
                    <span class="plan-review-date">58일 전</span>
                </div>
                <p class="plan-review-content">번호이동 과정이 생각보다 간단했어요. 고객센터 상담도 친절하고 개통도 빠르게 진행되었습니다. 다만 초기 설정할 때 조금 헷갈렸지만 지금은 잘 사용 중입니다.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">박*준</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">65일 전</span>
                </div>
                <p class="plan-review-content">eSIM으로 개통했는데 정말 편리했어요. 유심 카드 교체 없이 바로 사용할 수 있어서 좋았습니다. 통화 품질도 깨끗하고 데이터 속도도 만족스러워요.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">정*호</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">72일 전</span>
                </div>
                <p class="plan-review-content">기존 통신사보다 월 요금이 훨씬 저렴한데 데이터 제공량은 더 많아서 만족합니다. 사은품도 받고 가격도 좋고 일석이조네요. 강력 추천합니다!</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">강*영</span>
                    <div class="plan-review-stars">
                        <span>★★★★☆</span>
                    </div>
                    <span class="plan-review-date">78일 전</span>
                </div>
                <p class="plan-review-content">처음 알뜰폰 사용인데 걱정했지만 생각보다 괜찮아요. 통신 품질도 나쁘지 않고 가격 대비 만족도가 높습니다. 다만 앱이 조금 불편한 점이 있어요.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">윤*서</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">85일 전</span>
                </div>
                <p class="plan-review-content">밀리의 서재 무료 구독권 받아서 너무 좋아요! 요금제도 저렴하고 부가 서비스까지 받을 수 있어서 정말 만족합니다. 친구들한테도 자랑했어요.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">장*우</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">91일 전</span>
                </div>
                <p class="plan-review-content">KT망이라서 통신 품질이 안정적이에요. 지하철이나 건물 안에서도 끊김 없이 잘 사용하고 있습니다. 데이터 소진 후에도 5Mbps로 계속 사용할 수 있어서 좋아요.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">임*진</span>
                    <div class="plan-review-stars">
                        <span>★★★★☆</span>
                    </div>
                    <span class="plan-review-date">98일 전</span>
                </div>
                <p class="plan-review-content">신규 가입으로 진행했는데 번호도 마음에 들고 개통도 빠르게 되었어요. 고객센터 응대도 친절하고 전체적으로 만족합니다. 다만 약정 기간이 있으면 더 좋을 것 같아요.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">한*지</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">105일 전</span>
                </div>
                <p class="plan-review-content">데이터 쿠폰 60GB까지 받아서 총 160GB나 사용할 수 있어요! 유튜브, 넷플릭스 마음껏 보고 다니는데도 부족함이 없습니다. 정말 추천해요.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">송*현</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">112일 전</span>
                </div>
                <p class="plan-review-content">모요 사이트에서 비교하고 신청했는데 정말 편리했어요. 여러 통신사 요금제를 한눈에 비교할 수 있어서 좋았습니다. 쉐이크모바일 선택한 거 후회 없어요.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">조*혁</span>
                    <div class="plan-review-stars">
                        <span>★★★★☆</span>
                    </div>
                    <span class="plan-review-date">119일 전</span>
                </div>
                <p class="plan-review-content">번호이동 수수료가 없어서 좋았어요. 다른 통신사는 수수료 받는데 여기는 없어서 부담이 적었습니다. 통화 품질도 깨끗하고 만족합니다.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">배*수</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">126일 전</span>
                </div>
                <p class="plan-review-content">휴일에도 개통이 가능해서 정말 편리했어요. 주말에 신청했는데 월요일 오전에 바로 개통되었습니다. 고객센터도 친절하게 안내해주셨어요.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">신*아</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">133일 전</span>
                </div>
                <p class="plan-review-content">네이버페이 1만원 상품권 받아서 기분 좋았어요. 요금제도 저렴하고 사은품도 받고 일석이조입니다. 가족들도 모두 여기로 바꿨어요.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">오*성</span>
                    <div class="plan-review-stars">
                        <span>★★★★☆</span>
                    </div>
                    <span class="plan-review-date">140일 전</span>
                </div>
                <p class="plan-review-content">유심 배송도 빠르고 개통도 신속하게 진행되었어요. 처음 사용해보는 알뜰폰이라 걱정했는데 생각보다 괜찮습니다. 다만 앱 UI가 조금 아쉬워요.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">류*호</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">147일 전</span>
                </div>
                <p class="plan-review-content">데이터 제공량이 넉넉해서 매달 걱정 없이 사용하고 있어요. 핫스팟도 데이터 제공량 내에서 사용 가능해서 노트북 연결해서도 잘 쓰고 있습니다.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">문*희</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">154일 전</span>
                </div>
                <p class="plan-review-content">SOLO 결합으로 추가 20GB 받아서 총 120GB 사용 중이에요! 데이터 걱정 전혀 없이 사용하고 있습니다. 가격 대비 정말 최고의 요금제인 것 같아요.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">양*준</span>
                    <div class="plan-review-stars">
                        <span>★★★★☆</span>
                    </div>
                    <span class="plan-review-date">161일 전</span>
                </div>
                <p class="plan-review-content">고객센터 상담이 친절하고 전문적이에요. 문의사항도 빠르게 해결해주시고 개통 과정도 원활하게 진행되었습니다. 전체적으로 만족합니다.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">홍*영</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">168일 전</span>
                </div>
                <p class="plan-review-content">이마트 상품권 2만원 받아서 기분 좋았어요! 요금제도 저렴하고 사은품도 다양하게 받을 수 있어서 정말 만족합니다. 주변 사람들한테도 추천했어요.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">서*우</span>
                    <div class="plan-review-stars">
                        <span>★★★★★</span>
                    </div>
                    <span class="plan-review-date">175일 전</span>
                </div>
                <p class="plan-review-content">기존 통신사에서 번호이동 했는데 전혀 문제없이 잘 사용하고 있어요. 통신 품질도 동일하고 가격은 훨씬 저렴해서 만족합니다. 계속 사용할 예정이에요.</p>
            </div>
            <div class="plan-review-item">
                <div class="plan-review-item-header">
                    <span class="plan-review-author">노*진</span>
                    <div class="plan-review-stars">
                        <span>★★★★☆</span>
                    </div>
                    <span class="plan-review-date">182일 전</span>
                </div>
                <p class="plan-review-content">데이터 속도가 안정적이에요. 지하철이나 지하에서도 끊김 없이 잘 사용하고 있습니다. 가격 대비 품질이 정말 좋은 것 같아요. 추천합니다!</p>
            </div>
        </div>
        <button class="plan-review-more-btn" id="reviewMoreBtn">리뷰 더보기</button>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reviewList = document.getElementById('reviewList');
    const reviewMoreBtn = document.getElementById('reviewMoreBtn');
    
    if (reviewList && reviewMoreBtn) {
        const reviewItems = reviewList.querySelectorAll('.plan-review-item');
        const totalReviews = reviewItems.length;
        let visibleCount = 10; // 처음 10개만 표시
        
        // 초기 설정: 10개 이후 리뷰 숨기기
        reviewItems.forEach((item, index) => {
            if (index >= visibleCount) {
                item.style.display = 'none';
            }
        });
        
        // 더보기 버튼 클릭 이벤트
        reviewMoreBtn.addEventListener('click', function() {
            visibleCount += 10; // 10개씩 추가
            
            // 리뷰 표시
            reviewItems.forEach((item, index) => {
                if (index < visibleCount) {
                    item.style.display = 'block';
                }
            });
            
            // 모든 리뷰가 표시되면 버튼 숨기기
            if (visibleCount >= totalReviews) {
                reviewMoreBtn.style.display = 'none';
            }
        });
        
        // 모든 리뷰가 이미 표시되어 있으면 버튼 숨기기
        if (totalReviews <= visibleCount) {
            reviewMoreBtn.style.display = 'none';
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>

