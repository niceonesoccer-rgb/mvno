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

// 통신사폰 데이터 배열
$phones = [
    ['id' => 1, 'provider' => 'SKT', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy Z Fold7', 'device_storage' => '256GB', 'plan_name' => 'SKT 프리미어 슈퍼', 'price' => '월 109,000원', 'maintenance_period' => '185일', 'order_date' => '2024.11.15', 'order_time' => '14:30', 'activation_date' => '2024.11.16', 'activation_time' => '10:00', 'has_review' => false],
    ['id' => 2, 'provider' => 'KT', 'company_name' => '이야기모바일', 'device_name' => 'iPhone 16 Pro', 'device_storage' => '512GB', 'plan_name' => 'KT 슈퍼플랜', 'price' => '월 125,000원', 'maintenance_period' => '180일', 'order_date' => '2024.11.12', 'order_time' => '09:15', 'activation_date' => '2024.11.13', 'activation_time' => '14:30', 'has_review' => true],
    ['id' => 3, 'provider' => 'LG U+', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy S25', 'device_storage' => '256GB', 'plan_name' => 'LG U+ 5G 슈퍼플랜', 'price' => '월 95,000원', 'maintenance_period' => '200일', 'order_date' => '2024.11.10', 'order_time' => '16:45', 'activation_date' => '2024.11.11', 'activation_time' => '09:00', 'has_review' => false],
    ['id' => 4, 'provider' => 'SKT', 'company_name' => '이야기모바일', 'device_name' => 'iPhone 16', 'device_storage' => '128GB', 'plan_name' => 'SKT 스탠다드', 'price' => '월 85,000원', 'maintenance_period' => '150일', 'order_date' => '2024.11.08', 'order_time' => '11:20', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://example.com/consultation'],
    ['id' => 5, 'provider' => 'KT', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy S24 Ultra', 'device_storage' => '512GB', 'plan_name' => 'KT 프리미엄', 'price' => '월 115,000원', 'maintenance_period' => '190일', 'order_date' => '2024.11.05', 'order_time' => '13:50', 'activation_date' => '2024.11.06', 'activation_time' => '16:20', 'has_review' => false],
    ['id' => 6, 'provider' => 'LG U+', 'company_name' => '이야기모바일', 'device_name' => 'iPhone 15 Pro Max', 'device_storage' => '256GB', 'plan_name' => 'LG U+ 5G 플랜', 'price' => '월 105,000원', 'maintenance_period' => '175일', 'order_date' => '2024.11.03', 'order_time' => '10:05', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=6'],
    ['id' => 7, 'provider' => 'SKT', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy Z Flip6', 'device_storage' => '256GB', 'plan_name' => 'SKT 베이직', 'price' => '월 75,000원', 'maintenance_period' => '140일', 'order_date' => '2024.11.01', 'order_time' => '15:30', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=7'],
    ['id' => 8, 'provider' => 'KT', 'company_name' => '이야기모바일', 'device_name' => 'iPhone 15', 'device_storage' => '128GB', 'plan_name' => 'KT 스탠다드', 'price' => '월 80,000원', 'maintenance_period' => '160일', 'order_date' => '2024.10.28', 'order_time' => '12:15', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=8'],
    ['id' => 9, 'provider' => 'LG U+', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy S23', 'device_storage' => '256GB', 'plan_name' => 'LG U+ 5G 베이직', 'price' => '월 70,000원', 'maintenance_period' => '130일', 'order_date' => '2024.10.25', 'order_time' => '14:00', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=9'],
    ['id' => 10, 'provider' => 'SKT', 'company_name' => '이야기모바일', 'device_name' => 'iPhone 14 Pro', 'device_storage' => '256GB', 'plan_name' => 'SKT 프리미엄', 'price' => '월 100,000원', 'maintenance_period' => '170일', 'order_date' => '2024.10.22', 'order_time' => '09:40', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=10'],
    ['id' => 11, 'provider' => 'KT', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy A54', 'device_storage' => '128GB', 'plan_name' => 'KT 베이직', 'price' => '월 65,000원', 'maintenance_period' => '120일', 'order_date' => '2024.10.20', 'order_time' => '16:20', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=11'],
    ['id' => 12, 'provider' => 'LG U+', 'company_name' => '이야기모바일', 'device_name' => 'iPhone 13', 'device_storage' => '128GB', 'plan_name' => 'LG U+ 5G 스탠다드', 'price' => '월 75,000원', 'maintenance_period' => '145일', 'order_date' => '2024.10.18', 'order_time' => '11:55', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=12'],
    ['id' => 13, 'provider' => 'SKT', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy Note20', 'device_storage' => '256GB', 'plan_name' => 'SKT 스탠다드', 'price' => '월 90,000원', 'maintenance_period' => '165일', 'order_date' => '2024.10.15', 'order_time' => '13:25', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=13'],
    ['id' => 14, 'provider' => 'KT', 'company_name' => '이야기모바일', 'device_name' => 'iPhone 12', 'device_storage' => '128GB', 'plan_name' => 'KT 베이직', 'price' => '월 70,000원', 'maintenance_period' => '135일', 'order_date' => '2024.10.12', 'order_time' => '10:30', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=14'],
    ['id' => 15, 'provider' => 'LG U+', 'company_name' => '이야기모바일', 'device_name' => 'Galaxy S22', 'device_storage' => '256GB', 'plan_name' => 'LG U+ 5G 프리미엄', 'price' => '월 95,000원', 'maintenance_period' => '180일', 'order_date' => '2024.10.10', 'order_time' => '15:10', 'activation_date' => '', 'activation_time' => '', 'has_review' => false, 'consultation_url' => 'https://consult.example.com/inquiry?id=15'],
];

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
                    <div class="mno-order-list-container">
                <?php foreach ($phones as $index => $phone): ?>
                    <div class="phone-item" data-index="<?php echo $index; ?>" data-phone-id="<?php echo $phone['id']; ?>" style="<?php echo $index >= 10 ? 'display: none;' : ''; ?> position: relative;">
                        <?php
                        // 포인트 사용 내역 가져오기
                        $point_history = getPointHistoryByItem($user_id, 'mno', $phone['id']);
                        
                        // 통신사폰 데이터 준비
                        $phone_data = [
                            'id' => $phone['id'],
                            'device_name' => $phone['device_name'],
                            'device_storage' => $phone['device_storage'],
                            'release_price' => $phone['release_price'] ?? '2,387,000',
                            'provider' => $phone['provider'],
                            'plan_name' => $phone['plan_name'],
                            'price' => $phone['price'],
                            'maintenance_period' => $phone['maintenance_period'],
                            'company_name' => $phone['company_name'] ?? '이야기모바일',
                            'rating' => '4.5',
                            'order_date' => $phone['order_date'] ?? '',
                            'order_time' => $phone['order_time'] ?? '',
                            'activation_date' => $phone['activation_date'] ?? '',
                            'activation_time' => $phone['activation_time'] ?? '',
                            'has_review' => $phone['has_review'] ?? false,
                            'is_sold_out' => $phone['is_sold_out'] ?? false,
                            'consultation_url' => $phone['consultation_url'] ?? '',
                            'common_support' => $phone['common_support'] ?? [
                                'number_port' => -198,
                                'device_change' => 191.6
                            ],
                            'contract_support' => $phone['contract_support'] ?? [
                                'number_port' => 198,
                                'device_change' => -150
                            ],
                            'additional_supports' => $phone['additional_supports'] ?? ['추가 지원금', '부가 서비스 1', '부가 서비스 2'],
                            'link_url' => '/MVNO/mno/mno-phone-detail.php?id=' . $phone['id'],
                            'point_used' => $point_history ? $point_history['amount'] : 0,
                            'point_used_date' => $point_history ? $point_history['date'] : ''
                        ];
                        // 통신사폰 데이터 설정
                        $phone = $phone_data;
                        $layout_type = 'list';
                        $card_wrapper_class = '';
                        include '../includes/components/mno-order-phone-card.php';
                        ?>
                    </div>
                    <!-- 카드 구분선 (모바일용) -->
                    <hr class="mno-order-card-divider">
                <?php endforeach; ?>
                    </div>
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
        </div>
    </div>
</main>

<script src="../assets/js/plan-accordion.js" defer></script>
<script src="../assets/js/favorite-heart.js" defer></script>
<script src="../assets/js/order-review.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 더보기 기능
    const moreBtn = document.getElementById('morePhonesBtn');
    const phoneItems = document.querySelectorAll('.phone-item');
    let visibleCount = 10;
    const totalPhones = phoneItems.length;
    const loadCount = 10;

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
    }

    if (visibleCount >= totalPhones) {
        const moreButtonContainer = document.getElementById('moreButtonContainer');
        if (moreButtonContainer) {
            moreButtonContainer.style.display = 'none';
        }
    }

    // 리뷰 관리 공통 모듈 초기화
    const reviewManager = new OrderReviewManager({
        prefix: 'mno',
        itemIdAttr: 'data-phone-id',
        speedLabel: '개통 빨라요',
        textareaId: 'reviewText',
        onReviewSubmit: function(phoneId, reviewData) {
            // TODO: 서버로 리뷰 데이터 전송
            console.log('리뷰 작성 - Phone ID:', phoneId, 'Review Data:', reviewData);
        },
        onReviewDelete: function(phoneId) {
            // TODO: 서버로 삭제 요청
            console.log('리뷰 삭제 - Phone ID:', phoneId);
        },
        onReviewUpdate: function(phoneId) {
            // 리뷰 작성 완료 후 리뷰 쓰기 버튼 제거하고 점 3개 메뉴 표시
            const reviewBtn = document.querySelector(`.mno-order-review-btn[data-phone-id="${phoneId}"]`);
            if (reviewBtn) {
                const parentItem = reviewBtn.closest('.mno-order-action-item');
                if (parentItem) {
                    const prevDivider = parentItem.previousElementSibling;
                    if (prevDivider && prevDivider.classList.contains('mno-order-action-divider')) {
                        prevDivider.remove();
                    }
                    parentItem.remove();
                } else {
                    reviewBtn.remove();
                }
            }
            
            // 헤더에 점 3개 메뉴 추가
            const phoneItem = document.querySelector(`.phone-item[data-phone-id="${phoneId}"]`);
            const cardHeader = phoneItem ? phoneItem.querySelector('.mno-order-card-top-header') : null;
            if (cardHeader) {
                const menuGroup = cardHeader.querySelector('.mno-order-menu-group');
                if (!menuGroup) {
                    const newMenuGroup = document.createElement('div');
                    newMenuGroup.className = 'mno-order-menu-group';
                    newMenuGroup.style.cssText = 'position: relative;';
                    
                    const menuBtn = document.createElement('button');
                    menuBtn.type = 'button';
                    menuBtn.className = 'mno-order-menu-btn';
                    menuBtn.setAttribute('data-phone-id', phoneId);
                    menuBtn.setAttribute('aria-label', '메뉴');
                    menuBtn.innerHTML = `
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="6" r="1.5" fill="#868E96"/>
                            <circle cx="12" cy="12" r="1.5" fill="#868E96"/>
                            <circle cx="12" cy="18" r="1.5" fill="#868E96"/>
                        </svg>
                    `;
                    
                    const dropdown = document.createElement('div');
                    dropdown.className = 'mno-order-menu-dropdown';
                    dropdown.id = `mno-order-menu-${phoneId}`;
                    dropdown.style.cssText = 'display: none; position: absolute; top: 100%; right: 0; margin-top: 4px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); z-index: 1000; min-width: 120px; overflow: hidden; flex-direction: column;';
                    
                    const editBtn = document.createElement('button');
                    editBtn.type = 'button';
                    editBtn.className = 'mno-order-menu-item mno-order-review-edit-btn';
                    editBtn.setAttribute('data-phone-id', phoneId);
                    editBtn.textContent = '수정';
                    editBtn.style.cssText = 'width: 100%; padding: 12px 16px; background: none; border: none; text-align: left; font-size: 14px; color: #374151; cursor: pointer; transition: background-color 0.2s;';
                    
                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'mno-order-menu-item mno-order-review-delete-btn';
                    deleteBtn.setAttribute('data-phone-id', phoneId);
                    deleteBtn.textContent = '삭제';
                    deleteBtn.style.cssText = 'width: 100%; padding: 12px 16px; background: none; border: none; border-top: 1px solid #e5e7eb; text-align: left; font-size: 14px; color: #ef4444; cursor: pointer; transition: background-color 0.2s;';
                    
                    dropdown.appendChild(editBtn);
                    dropdown.appendChild(deleteBtn);
                    newMenuGroup.appendChild(menuBtn);
                    newMenuGroup.appendChild(dropdown);
                    cardHeader.appendChild(newMenuGroup);
                }
            }
        },
        onReviewDeleteUpdate: function(phoneId) {
            console.log('리뷰 삭제 후 UI 업데이트:', phoneId);
            
            // 점 3개 메뉴 제거
            const phoneItem = document.querySelector(`.phone-item[data-phone-id="${phoneId}"]`);
            if (phoneItem) {
                const cardHeader = phoneItem.querySelector('.mno-order-card-top-header');
                if (cardHeader) {
                    const menuGroup = cardHeader.querySelector('.mno-order-menu-group');
                    if (menuGroup) {
                        console.log('점 3개 메뉴 제거');
                        menuGroup.remove();
                    }
                }
                
                // 리뷰 쓰기 버튼 추가
                const actionsContent = phoneItem.querySelector('.mno-order-card-actions-content');
                if (actionsContent) {
                    // 기존 리뷰 쓰기 버튼이 있는지 확인
                    const existingBtn = actionsContent.querySelector('.mno-order-review-btn');
                    if (!existingBtn) {
                        console.log('리뷰 쓰기 버튼 추가');
                        const actionItem = document.createElement('div');
                        actionItem.className = 'mno-order-action-item';
                        
                        const divider = document.createElement('div');
                        divider.className = 'mno-order-action-divider';
                        
                        const reviewBtn = document.createElement('button');
                        reviewBtn.type = 'button';
                        reviewBtn.className = 'mno-order-review-btn';
                        reviewBtn.setAttribute('data-phone-id', phoneId);
                        reviewBtn.textContent = '리뷰쓰기';
                        
                        actionsContent.appendChild(divider);
                        actionItem.appendChild(reviewBtn);
                        actionsContent.appendChild(actionItem);
                    }
                }
            } else {
                console.error('phone-item을 찾을 수 없습니다:', phoneId);
            }
        }
    });

});
</script>

<?php
// 공통 리뷰 모달 포함
$prefix = 'mno';
$speedLabel = '개통 빨라요';
$formId = 'mnoReviewForm';
$modalId = 'mnoReviewModal';
$textareaId = 'reviewText';
include '../includes/components/order-review-modal.php';

// 공통 리뷰 삭제 모달 포함
$prefix = 'mno';
$modalId = 'mnoReviewDeleteModal';
include '../includes/components/order-review-delete-modal.php';
?>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

