<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 경로 설정 파일 먼저 로드
require_once '../includes/data/path-config.php';

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';

// 로그인 체크 - 로그인하지 않은 경우 회원가입 모달로 리다이렉트
if (!isLoggedIn()) {
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    // 로그인 모달이 있는 홈으로 리다이렉트 (모달 자동 열기)
    header('Location: ' . getAssetPath('/?show_login=1'));
    exit;
}

// 현재 사용자 정보 가져오기
$currentUser = getCurrentUser();
if (!$currentUser) {
    // 세션 정리 후 로그인 페이지로 리다이렉트
    if (isset($_SESSION['logged_in'])) {
        unset($_SESSION['logged_in']);
    }
    if (isset($_SESSION['user_id'])) {
        unset($_SESSION['user_id']);
    }
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . getAssetPath('/?show_login=1'));
    exit;
}

$user_id = $currentUser['user_id'];

// 필요한 함수 포함
require_once '../includes/data/product-functions.php';
require_once '../includes/data/db-config.php';
require_once '../includes/data/plan-data.php';

// 페이지 번호 및 제한 설정
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20; // 초기 로드 개수
$offset = ($page - 1) * $limit;

// DB에서 실제 신청 내역 가져오기 (페이징 적용)
$applications = getUserMnoSimApplications($user_id, $limit, $offset);
$totalCount = count(getUserMnoSimApplications($user_id)); // 전체 개수

$currentCount = count($applications);
$remainingCount = max(0, $totalCount - ($offset + $currentCount));
$hasMore = ($offset + $currentCount) < $totalCount;

// 헤더 포함
include '../includes/header.php';
// 리뷰 모달 포함
include '../includes/components/mvno-review-modal.php';
?>

<style>
/* 전화번호 버튼 반응형 스타일 */
@media (max-width: 768px) {
    .phone-inquiry-pc {
        display: none !important;
    }
    .phone-inquiry-mobile {
        display: flex !important;
    }
}
@media (min-width: 769px) {
    .phone-inquiry-pc {
        display: block !important;
    }
    .phone-inquiry-mobile {
        display: none !important;
    }
}
</style>

<main class="main-content">
    <div class="content-layout">
        <div class="plans-main-layout">
            <div class="plans-left-section">
                <!-- 페이지 헤더 -->
                <div style="margin-bottom: 24px; padding: 20px 0;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <a href="<?php echo getAssetPath('/mypage/mypage.php'); ?>" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 통신사단독유심</h2>
                    </div>
                    <p style="font-size: 14px; color: #6b7280; margin: 0; margin-left: 36px;">카드를 클릭하면 신청 정보를 확인할 수 있습니다.</p>
                </div>
                
                <!-- 전체 개수 표시 -->
                <?php if (!empty($applications)): ?>
                <div class="plans-results-count">
                    <span><?php echo number_format($totalCount); ?>개의 결과</span>
                </div>
                <?php endif; ?>
                
                <!-- 신청한 통신사단독유심 목록 -->
                <div style="margin-bottom: 32px;">
                    <?php if (empty($applications)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: #6b7280;">
                            신청한 통신사단독유심이 없습니다.
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;" id="mno-sim-orders-container">
                            <?php foreach ($applications as $index => $app): ?>
                                <div class="order-item-wrapper">
                                    <?php include __DIR__ . '/../includes/components/mno-sim-order-card.php'; ?>
                                </div>
                            <?php endforeach; ?>
                        
                        <!-- 더보기 버튼 -->
                        <?php if ($hasMore && $totalCount > 0): ?>
                        <div class="load-more-container" id="load-more-anchor">
                            <button id="load-more-mno-sim-order-btn" class="load-more-btn" 
                                    data-type="mno-sim" 
                                    data-page="2" 
                                    data-total="<?php echo $totalCount; ?>"
                                    data-order="true">
                                더보기 (<span id="remaining-count"><?php echo number_format($remainingCount); ?></span>개 남음)
                            </button>
                        </div>
                        <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- 신청 상세 정보 모달 -->
<div id="applicationDetailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 10000; overflow: hidden; padding: 20px;">
    <div style="max-width: 800px; margin: 40px auto; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); position: relative;">
        <!-- 모달 헤더 -->
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 24px; font-weight: bold; margin: 0; color: #1f2937;">등록정보</h2>
            <button id="closeModalBtn" style="background: none; border: none; cursor: pointer; padding: 8px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
        <!-- 모달 내용 -->
        <div id="modalContent" style="padding: 24px; max-height: calc(100vh - 200px); overflow-y: auto;">
            <div style="text-align: center; padding: 40px; color: #6b7280;">
                <div class="spinner" style="border: 3px solid #f3f4f6; border-top: 3px solid #6366f1; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
                <p>정보를 불러오는 중...</p>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
    // BASE_PATH와 API_PATH를 JavaScript에서 사용할 수 있도록 설정
    window.BASE_PATH = window.BASE_PATH || '<?php echo getBasePath(); ?>';
    window.API_PATH = window.API_PATH || (window.BASE_PATH + '/api');
    
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('applicationDetailModal');
    const modalContent = document.getElementById('modalContent');
    const closeBtn = document.getElementById('closeModalBtn');
    const applicationCards = document.querySelectorAll('.application-card');
    
    // 카드 클릭 이벤트
    applicationCards.forEach(card => {
        card.addEventListener('click', function(e) {
            const applicationId = this.getAttribute('data-application-id');
            if (applicationId) {
                openModal(applicationId);
            }
        });
    });
    
    // 모달 닫기 버튼
    closeBtn.addEventListener('click', closeModal);
    
    // 배경 클릭 시 모달 닫기
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
        }
    });
    
    function openModal(applicationId) {
        modal.style.display = 'block';
        // 배경 페이지 스크롤 완전 차단 (스크롤바도 숨김)
        document.body.style.overflow = 'hidden';
        document.body.style.paddingRight = '0px';
        // html 요소도 스크롤 차단
        document.documentElement.style.overflow = 'hidden';
        
        // 로딩 표시
        modalContent.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #6b7280;">
                <div class="spinner" style="border: 3px solid #f3f4f6; border-top: 3px solid #6366f1; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
                <p>정보를 불러오는 중...</p>
            </div>
        `;
        
        // API 호출
        fetch(`${window.API_PATH || (window.BASE_PATH || '') + '/api'}/get-application-details.php?application_id=${applicationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayApplicationDetails(data.data);
                } else {
                    modalContent.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #dc2626;">
                            <p>정보를 불러오는 중 오류가 발생했습니다.</p>
                            <p style="font-size: 14px; margin-top: 8px;">${data.message || '알 수 없는 오류'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalContent.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc2626;">
                        <p>정보를 불러오는 중 오류가 발생했습니다.</p>
                        <p style="font-size: 14px; margin-top: 8px;">네트워크 오류가 발생했습니다.</p>
                    </div>
                `;
            });
    }
    
    function closeModal() {
        modal.style.display = 'none';
        // 배경 페이지 스크롤 복원
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        document.documentElement.style.overflow = '';
    }
    
    function displayApplicationDetails(data) {
        const customer = data.customer || {};
        const additionalInfo = data.additional_info || {};
        const productSnapshot = additionalInfo.product_snapshot || {};
        
        let html = '<div style="display: flex; flex-direction: column; gap: 24px;">';
        
        // 주문 정보 섹션
        html += '<div>';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">주문 정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        if (data.order_number) {
            html += `<div style="color: #6b7280; font-weight: 500;">주문번호:</div>`;
            html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(data.order_number)}</div>`;
        }
        
        if (data.status) {
            html += `<div style="color: #6b7280; font-weight: 500;">진행상황:</div>`;
            html += `<div style="color: #6366f1; font-weight: 600;">${escapeHtml(data.status)}</div>`;
        }
        
        if (data.status_changed_at) {
            html += `<div style="color: #6b7280; font-weight: 500;">상태 변경일시:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(data.status_changed_at)}</div>`;
        }
        
        // 기본 요금
        if (productSnapshot.price_main) {
            html += `<div style="color: #6b7280; font-weight: 500;">기본 요금:</div>`;
            const priceMainUnit = productSnapshot.price_main_unit || '원';
            html += `<div style="color: #1f2937; font-weight: 600;">월 ${formatNumber(productSnapshot.price_main)}${escapeHtml(priceMainUnit)}</div>`;
        }
        
        // 할인 후 요금
        if (productSnapshot.price_after_type && productSnapshot.price_after_type !== 'none') {
            html += `<div style="color: #6b7280; font-weight: 500;">할인 후 요금:</div>`;
            if (productSnapshot.price_after_type === 'free' || productSnapshot.price_after == 0) {
                html += `<div style="color: #6366f1; font-weight: 600;">무료</div>`;
            } else if (productSnapshot.price_after) {
                const priceAfterUnit = productSnapshot.price_after_unit || '원';
                html += `<div style="color: #6366f1; font-weight: 600;">월 ${formatNumber(productSnapshot.price_after)}${escapeHtml(priceAfterUnit)}</div>`;
            }
        }
        
        // 할인기간
        if (productSnapshot.discount_period) {
            html += `<div style="color: #6b7280; font-weight: 500;">할인기간:</div>`;
            let discountPeriodText = '';
            const discountPeriod = productSnapshot.discount_period;
            if (discountPeriod === '프로모션 없음') {
                discountPeriodText = '프로모션 없음';
            } else if (discountPeriod === '직접입력' && productSnapshot.discount_period_value && productSnapshot.discount_period_unit) {
                discountPeriodText = String(productSnapshot.discount_period_value) + escapeHtml(productSnapshot.discount_period_unit);
            } else if (discountPeriod) {
                discountPeriodText = discountPeriod;
            } else {
                discountPeriodText = '정보 없음';
            }
            html += `<div style="color: #1f2937;">${escapeHtml(discountPeriodText)}</div>`;
        }
        
        html += '</div></div>';
        
        // 고객 정보 섹션
        html += '<div>';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">고객 정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        if (customer.name) {
            html += `<div style="color: #6b7280; font-weight: 500;">이름:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(customer.name)}</div>`;
        }
        
        if (customer.phone) {
            html += `<div style="color: #6b7280; font-weight: 500;">전화번호:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(customer.phone)}</div>`;
        }
        
        if (customer.email) {
            html += `<div style="color: #6b7280; font-weight: 500;">이메일:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(customer.email)}</div>`;
        }
        
        if (customer.address) {
            html += `<div style="color: #6b7280; font-weight: 500;">주소:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(customer.address)}${customer.address_detail ? ' ' + escapeHtml(customer.address_detail) : ''}</div>`;
        }
        
        if (customer.birth_date) {
            html += `<div style="color: #6b7280; font-weight: 500;">생년월일:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(customer.birth_date)}</div>`;
        }
        
        if (customer.gender) {
            html += `<div style="color: #6b7280; font-weight: 500;">성별:</div>`;
            const genderText = customer.gender === 'male' ? '남성' : customer.gender === 'female' ? '여성' : '기타';
            html += `<div style="color: #1f2937;">${genderText}</div>`;
        }
        
        html += '</div></div>';
        
        // 기본 정보 섹션
        html += '<div style="margin-bottom: 24px;">';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">기본 정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        // 요금제 이름
        if (productSnapshot.plan_name) {
            html += `<div style="color: #6b7280; font-weight: 500;">요금제 이름</div>`;
            html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(productSnapshot.plan_name)}</div>`;
        }
        
        // 통신사 약정
        if (productSnapshot.contract_period) {
            html += `<div style="color: #6b7280; font-weight: 500;">통신사 약정</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.contract_period)}`;
            if (productSnapshot.contract_period_discount_value && productSnapshot.contract_period_discount_unit) {
                html += ` ${productSnapshot.contract_period_discount_value}${escapeHtml(productSnapshot.contract_period_discount_unit)}`;
            }
            html += `</div>`;
        }
        
        // 통신망
        if (productSnapshot.provider) {
            html += `<div style="color: #6b7280; font-weight: 500;">통신망</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.provider)}</div>`;
        }
        
        // 통신 기술
        if (productSnapshot.service_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">통신 기술</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.service_type)}</div>`;
        }
        
        // 가입 형태 (사용자가 선택한 정보)
        if (additionalInfo.subscription_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">가입 형태</div>`;
            // 가입 형태 한글 변환 (고객용 표시)
            let subscriptionTypeText = additionalInfo.subscription_type;
            const subscriptionTypeMap = {
                'new': '신규가입',
                'mnp': '번호이동',
                'port': '번호이동', // 하위 호환성
                'change': '기기변경'
            };
            if (subscriptionTypeMap[subscriptionTypeText]) {
                subscriptionTypeText = subscriptionTypeMap[subscriptionTypeText];
            }
            html += `<div style="color: #1f2937;">${escapeHtml(subscriptionTypeText)}</div>`;
        }
        
        // 요금제 유지기간
        if (productSnapshot.plan_maintenance_period_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">요금제 유지기간</div>`;
            const planMaintenanceType = productSnapshot.plan_maintenance_period_type;
            if (planMaintenanceType === '무약정') {
                html += `<div style="color: #1f2937;">무약정</div>`;
            } else if (planMaintenanceType === '직접입력') {
                const prefix = productSnapshot.plan_maintenance_period_prefix || '';
                const value = productSnapshot.plan_maintenance_period_value || null;
                const unit = productSnapshot.plan_maintenance_period_unit || '';
                if (value && unit) {
                    html += `<div style="color: #1f2937;">${escapeHtml(prefix + '+' + value + unit)}</div>`;
                } else {
                    html += `<div style="color: #1f2937;">정보 없음</div>`;
                }
            }
        }
        
        // 유심기변 불가기간
        if (productSnapshot.sim_change_restriction_period_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">유심기변 불가기간</div>`;
            const simChangeRestrictionType = productSnapshot.sim_change_restriction_period_type;
            if (simChangeRestrictionType === '무약정') {
                html += `<div style="color: #1f2937;">무약정</div>`;
            } else if (simChangeRestrictionType === '직접입력') {
                const prefix = productSnapshot.sim_change_restriction_period_prefix || '';
                const value = productSnapshot.sim_change_restriction_period_value || null;
                const unit = productSnapshot.sim_change_restriction_period_unit || '';
                if (value && unit) {
                    html += `<div style="color: #1f2937;">${escapeHtml(prefix + '+' + value + unit)}</div>`;
                } else {
                    html += `<div style="color: #1f2937;">정보 없음</div>`;
                }
            }
        }
        
        html += '</div></div>';
        
        // 데이터 정보 섹션
        html += '<div style="margin-bottom: 24px;">';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">데이터 정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        // 통화 정보
        if (productSnapshot.call_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">통화</div>`;
            let callText = productSnapshot.call_type;
            if (productSnapshot.call_type === '직접입력' && productSnapshot.call_amount) {
                const callAmountStr = String(productSnapshot.call_amount);
                const unit = productSnapshot.call_amount_unit || '분';
                if (callAmountStr.endsWith('분') || callAmountStr.endsWith('초') || callAmountStr.endsWith('건')) {
                    callText = callAmountStr;
                } else {
                    callText = callAmountStr + unit;
                }
            }
            html += `<div style="color: #1f2937;">${escapeHtml(callText)}</div>`;
        }
        
        // 문자 정보
        if (productSnapshot.sms_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">문자</div>`;
            let smsText = productSnapshot.sms_type;
            if (productSnapshot.sms_type === '직접입력' && productSnapshot.sms_amount) {
                const smsAmountStr = String(productSnapshot.sms_amount);
                const unit = productSnapshot.sms_amount_unit || '건';
                if (smsAmountStr.endsWith('분') || smsAmountStr.endsWith('초') || smsAmountStr.endsWith('건')) {
                    smsText = smsAmountStr;
                } else {
                    smsText = smsAmountStr + unit;
                }
            }
            html += `<div style="color: #1f2937;">${escapeHtml(smsText)}</div>`;
        }
        
        // 데이터 제공량
        if (productSnapshot.data_amount) {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터 제공량</div>`;
            let dataText = '';
            if (productSnapshot.data_amount === '무제한') {
                dataText = '무제한';
            } else if (productSnapshot.data_amount === '직접입력' && productSnapshot.data_amount_value) {
                const dataValueStr = String(productSnapshot.data_amount_value);
                const unit = productSnapshot.data_unit || 'GB';
                if (dataValueStr.endsWith('GB') || dataValueStr.endsWith('MB') || dataValueStr.endsWith('TB') || 
                    dataValueStr.endsWith('Mbps') || dataValueStr.endsWith('Gbps') || dataValueStr.endsWith('Kbps')) {
                    dataText = dataValueStr;
                } else {
                    dataText = dataValueStr + unit;
                }
            } else {
                dataText = productSnapshot.data_amount;
            }
            html += `<div style="color: #1f2937;">${escapeHtml(dataText)}</div>`;
        }
        
        // 데이터 추가제공
        if (productSnapshot.data_additional && productSnapshot.data_additional !== '없음') {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터 추가제공</div>`;
            let additionalText = '';
            if (productSnapshot.data_additional === '직접입력' && productSnapshot.data_additional_value) {
                additionalText = productSnapshot.data_additional_value;
            } else {
                additionalText = productSnapshot.data_additional;
            }
            html += `<div style="color: #1f2937;">${escapeHtml(additionalText)}</div>`;
        }
        
        // 데이터 소진시
        if (productSnapshot.data_exhausted && productSnapshot.data_exhausted !== '직접입력') {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터 소진시</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.data_exhausted)}</div>`;
        } else if (productSnapshot.data_exhausted === '직접입력' && productSnapshot.data_exhausted_value) {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터 소진시</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.data_exhausted_value)}</div>`;
        }
        
        // 부가·영상통화 정보
        if (productSnapshot.additional_call_type && productSnapshot.additional_call) {
            html += `<div style="color: #6b7280; font-weight: 500;">부가·영상통화</div>`;
            let additionalCallText = '';
            const additionalCallStr = String(productSnapshot.additional_call);
            const additionalCallUnit = productSnapshot.additional_call_unit || '분';
            if (additionalCallStr.endsWith('분') || additionalCallStr.endsWith('초') || additionalCallStr.endsWith('건')) {
                additionalCallText = additionalCallStr;
            } else {
                additionalCallText = additionalCallStr + additionalCallUnit;
            }
            html += `<div style="color: #1f2937;">${escapeHtml(additionalCallText)}</div>`;
        }
        
        // 테더링(핫스팟) 정보
        if (productSnapshot.mobile_hotspot) {
            html += `<div style="color: #6b7280; font-weight: 500;">테더링(핫스팟)</div>`;
            let hotspotText = '';
            const mobileHotspot = productSnapshot.mobile_hotspot;
            if (mobileHotspot === '기본 제공량 내에서 사용') {
                hotspotText = '기본 제공량 내에서 사용';
            } else if (productSnapshot.mobile_hotspot_value && productSnapshot.mobile_hotspot_unit) {
                const hotspotValue = parseFloat(productSnapshot.mobile_hotspot_value);
                if (Math.floor(hotspotValue) === hotspotValue) {
                    hotspotText = formatNumber(hotspotValue) + escapeHtml(productSnapshot.mobile_hotspot_unit);
                } else {
                    const formatted = hotspotValue.toFixed(2).replace(/\.?0+$/, '');
                    hotspotText = formatted + escapeHtml(productSnapshot.mobile_hotspot_unit);
                }
            } else if (mobileHotspot) {
                hotspotText = mobileHotspot;
            } else {
                hotspotText = '정보 없음';
            }
            html += `<div style="color: #1f2937;">${escapeHtml(hotspotText)}</div>`;
        }
        
        html += '</div></div>';
        
        // 유심 정보 섹션
        html += '<div style="margin-bottom: 24px;">';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">유심 정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        // 일반 유심 정보
        if (productSnapshot.regular_sim_available) {
            html += `<div style="color: #6b7280; font-weight: 500;">일반 유심</div>`;
            let regularSimText = productSnapshot.regular_sim_available;
            if (regularSimText === '유심비 유료' || regularSimText === '배송가능') {
                if (productSnapshot.regular_sim_price && productSnapshot.regular_sim_price > 0) {
                    const regularSimUnit = productSnapshot.regular_sim_price_unit || '원';
                    regularSimText = `유심비 유료 (${formatNumber(productSnapshot.regular_sim_price)}${escapeHtml(regularSimUnit)})`;
                } else {
                    regularSimText = '유심비 유료';
                }
            } else if (regularSimText === '유심비 무료' || regularSimText === '유심 무료' || regularSimText === '무료제공') {
                regularSimText = '유심비 무료';
            } else if (regularSimText === '유심·배송비 무료' || regularSimText === '무료제공(배송비무료)') {
                regularSimText = '유심·배송비 무료';
            }
            html += `<div style="color: #1f2937;">${escapeHtml(regularSimText)}</div>`;
        }
        
        // NFC 유심 정보
        if (productSnapshot.nfc_sim_available) {
            html += `<div style="color: #6b7280; font-weight: 500;">NFC 유심</div>`;
            let nfcSimText = productSnapshot.nfc_sim_available;
            if (nfcSimText === '유심비 유료' || nfcSimText === '배송가능') {
                if (productSnapshot.nfc_sim_price && productSnapshot.nfc_sim_price > 0) {
                    const nfcSimUnit = productSnapshot.nfc_sim_price_unit || '원';
                    nfcSimText = `유심비 유료 (${formatNumber(productSnapshot.nfc_sim_price)}${escapeHtml(nfcSimUnit)})`;
                } else {
                    nfcSimText = '유심비 유료';
                }
            } else if (nfcSimText === '유심비 무료' || nfcSimText === '유심 무료' || nfcSimText === '무료제공') {
                nfcSimText = '유심비 무료';
            } else if (nfcSimText === '유심·배송비 무료' || nfcSimText === '무료제공(배송비무료)') {
                nfcSimText = '유심·배송비 무료';
            }
            html += `<div style="color: #1f2937;">${escapeHtml(nfcSimText)}</div>`;
        }
        
        // eSIM 정보
        if (productSnapshot.esim_available) {
            html += `<div style="color: #6b7280; font-weight: 500;">eSIM</div>`;
            let esimText = productSnapshot.esim_available;
            if (esimText === '개통불가') {
                esimText = '개통불가';
            } else if (esimText === 'eSIM 유료' || esimText === '유심비 유료' || esimText === '개통가능') {
                if (productSnapshot.esim_price && productSnapshot.esim_price > 0) {
                    const esimUnit = productSnapshot.esim_price_unit || '원';
                    esimText = `개통가능 (${formatNumber(productSnapshot.esim_price)}${escapeHtml(esimUnit)})`;
                } else {
                    esimText = '개통가능';
                }
            } else if (esimText === 'eSIM 무료' || esimText === '유심비 무료' || esimText === '유심 무료' || esimText === '무료제공') {
                esimText = 'eSIM 무료';
            }
            html += `<div style="color: #1f2937;">${escapeHtml(esimText)}</div>`;
        }
        
        html += '</div></div>';
        
        // 기본 제공 초과 시 섹션
        let hasOverData = false;
        let overDataPrice = productSnapshot.over_data_price;
        let overVoicePrice = productSnapshot.over_voice_price;
        let overVideoPrice = productSnapshot.over_video_price;
        let overSmsPrice = productSnapshot.over_sms_price;
        let overLmsPrice = productSnapshot.over_lms_price;
        let overMmsPrice = productSnapshot.over_mms_price;
        
        if (overDataPrice !== null && overDataPrice !== undefined && overDataPrice !== '') hasOverData = true;
        if (overVoicePrice !== null && overVoicePrice !== undefined && overVoicePrice !== '') hasOverData = true;
        if (overVideoPrice !== null && overVideoPrice !== undefined && overVideoPrice !== '') hasOverData = true;
        if (overSmsPrice !== null && overSmsPrice !== undefined && overSmsPrice !== '') hasOverData = true;
        if (overLmsPrice !== null && overLmsPrice !== undefined && overLmsPrice !== '') hasOverData = true;
        if (overMmsPrice !== null && overMmsPrice !== undefined && overMmsPrice !== '') hasOverData = true;
        
        if (hasOverData) {
            html += '<div>';
            html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">기본 제공 초과 시</h3>';
            html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
            
            // 데이터
            if (overDataPrice !== null && overDataPrice !== undefined && overDataPrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">데이터</div>`;
                const overDataPriceUnit = productSnapshot.over_data_price_unit || '원/MB';
                const overDataPriceFloat = parseFloat(overDataPrice);
                let overDataFormatted = '';
                if (isNaN(overDataPriceFloat)) {
                    overDataFormatted = String(overDataPrice);
                } else if (Math.floor(overDataPriceFloat) === overDataPriceFloat) {
                    overDataFormatted = formatNumber(overDataPriceFloat);
                } else {
                    overDataFormatted = parseFloat(overDataPriceFloat.toFixed(2)).toString();
                }
                html += `<div style="color: #1f2937;">${escapeHtml(overDataFormatted)} ${escapeHtml(overDataPriceUnit)}</div>`;
            }
            
            // 음성
            if (overVoicePrice !== null && overVoicePrice !== undefined && overVoicePrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">음성</div>`;
                const overVoicePriceUnit = productSnapshot.over_voice_price_unit || '원/초';
                const overVoicePriceFloat = parseFloat(overVoicePrice);
                let overVoiceFormatted = '';
                if (isNaN(overVoicePriceFloat)) {
                    overVoiceFormatted = String(overVoicePrice);
                } else if (Math.floor(overVoicePriceFloat) === overVoicePriceFloat) {
                    overVoiceFormatted = formatNumber(overVoicePriceFloat);
                } else {
                    overVoiceFormatted = parseFloat(overVoicePriceFloat.toFixed(2)).toString();
                }
                html += `<div style="color: #1f2937;">${escapeHtml(overVoiceFormatted)} ${escapeHtml(overVoicePriceUnit)}</div>`;
            }
            
            // 영상통화
            if (overVideoPrice !== null && overVideoPrice !== undefined && overVideoPrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">영상통화</div>`;
                const overVideoPriceUnit = productSnapshot.over_video_price_unit || '원/초';
                const overVideoPriceFloat = parseFloat(overVideoPrice);
                let overVideoFormatted = '';
                if (isNaN(overVideoPriceFloat)) {
                    overVideoFormatted = String(overVideoPrice);
                } else if (Math.floor(overVideoPriceFloat) === overVideoPriceFloat) {
                    overVideoFormatted = formatNumber(overVideoPriceFloat);
                } else {
                    overVideoFormatted = parseFloat(overVideoPriceFloat.toFixed(2)).toString();
                }
                html += `<div style="color: #1f2937;">${escapeHtml(overVideoFormatted)} ${escapeHtml(overVideoPriceUnit)}</div>`;
            }
            
            // 단문메시지(SMS)
            if (overSmsPrice !== null && overSmsPrice !== undefined && overSmsPrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">단문메시지(SMS)</div>`;
                const overSmsPriceUnit = productSnapshot.over_sms_price_unit || '원/건';
                html += `<div style="color: #1f2937;">${formatNumber(overSmsPrice)} ${escapeHtml(overSmsPriceUnit)}</div>`;
            }
            
            // 텍스트형(LMS)
            if (overLmsPrice !== null && overLmsPrice !== undefined && overLmsPrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">텍스트형(LMS)</div>`;
                const overLmsPriceUnit = productSnapshot.over_lms_price_unit || '원/건';
                html += `<div style="color: #1f2937;">${formatNumber(overLmsPrice)} ${escapeHtml(overLmsPriceUnit)}</div>`;
            }
            
            // 멀티미디어형(MMS)
            if (overMmsPrice !== null && overMmsPrice !== undefined && overMmsPrice !== '') {
                html += `<div style="color: #6b7280; font-weight: 500;">멀티미디어형(MMS)</div>`;
                const overMmsPriceUnit = productSnapshot.over_mms_price_unit || '원/건';
                html += `<div style="color: #1f2937;">${formatNumber(overMmsPrice)} ${escapeHtml(overMmsPriceUnit)}</div>`;
            }
            
            html += '</div></div>';
            
            // 문자메시지 주의사항
            html += '<div style="margin-top: 16px; padding: 12px; background: #f9fafb; border-radius: 8px; font-size: 13px; color: #6b7280; line-height: 1.6;">';
            html += '문자메시지 기본제공 혜택을 약관에 정한 기준보다 많이 사용하거나 스팸, 광고 목적으로 이용한 것이 확인되면 추가 요금을 내야 하거나 서비스 이용이 정지될 수 있어요.';
            html += '</div>';
        }
        
        // 혜택 및 유의사항 섹션
        if (productSnapshot.benefits || productSnapshot.promotion_title || productSnapshot.promotions) {
            html += '<div style="margin-bottom: 24px;">';
            html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">혜택 및 유의사항</h3>';
            html += '<div style="font-size: 14px; color: #374151; line-height: 1.8;">';
            
            // 혜택 정보 (프로모션 제목 + 항목들)
            if (productSnapshot.promotion_title || productSnapshot.promotions) {
                html += '<div style="margin-bottom: 16px;">';
                html += '<div style="font-weight: 600; color: #1f2937; margin-bottom: 8px;">혜택</div>';
                let benefitText = '';
                
                // 제목이 있으면 표시
                if (productSnapshot.promotion_title) {
                    benefitText = escapeHtml(productSnapshot.promotion_title);
                }
                
                // 항목들 가져오기
                let promotions = [];
                try {
                    if (typeof productSnapshot.promotions === 'string') {
                        promotions = JSON.parse(productSnapshot.promotions);
                    } else if (Array.isArray(productSnapshot.promotions)) {
                        promotions = productSnapshot.promotions;
                    }
                } catch(e) {
                    promotions = [];
                }
                
                // 항목이 있으면 제목 ( 항목, 항목, ... ) 형식으로 표시
                if (promotions.length > 0) {
                    const promotionList = promotions.filter(p => p && p.trim()).map(p => escapeHtml(p.trim())).join(', ');
                    if (benefitText) {
                        benefitText = `${benefitText} (${promotionList})`;
                    } else {
                        benefitText = promotionList;
                    }
                }
                
                if (benefitText) {
                    html += `<div style="color: #374151;">${benefitText}</div>`;
                }
                html += '</div>';
            }
            
            // 유의사항 (benefits)
            let benefits = null;
            try {
                if (typeof productSnapshot.benefits === 'string') {
                    const parsed = JSON.parse(productSnapshot.benefits);
                    if (Array.isArray(parsed)) {
                        benefits = parsed;
                    } else {
                        benefits = [productSnapshot.benefits];
                    }
                } else if (Array.isArray(productSnapshot.benefits)) {
                    benefits = productSnapshot.benefits;
                } else {
                    benefits = [String(productSnapshot.benefits)];
                }
            } catch(e) {
                benefits = [String(productSnapshot.benefits)];
            }
            
            if (benefits && benefits.length > 0) {
                html += '<div style="margin-top: 16px;">';
                html += '<div style="font-weight: 600; color: #1f2937; margin-bottom: 8px;">유의사항</div>';
                html += '<ul style="margin: 0; padding-left: 20px; list-style-type: disc;">';
                benefits.forEach(function(benefit) {
                    const benefitText = String(benefit).trim();
                    if (benefitText) {
                        const formattedText = escapeHtml(benefitText).replace(/\n/g, '<br>');
                        html += `<li style="margin-bottom: 8px;">${formattedText}</li>`;
                    }
                });
                html += '</ul>';
                html += '</div>';
            }
            
            html += '</div></div>';
        }
        
        html += '</div>';
        
        modalContent.innerHTML = html;
    }
    
    function formatNumber(num) {
        if (!num) return '0';
        return parseFloat(num).toLocaleString('ko-KR');
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // 리뷰 작성/수정 기능 변수 (전역 스코프)
    const reviewModal = document.getElementById('mvnoReviewModal');
    const reviewForm = document.getElementById('mvnoReviewForm');
    const reviewModalClose = reviewModal ? reviewModal.querySelector('.mvno-review-modal-close') : null;
    const reviewModalOverlay = reviewModal ? reviewModal.querySelector('.mvno-review-modal-overlay') : null;
    const reviewCancelBtn = reviewForm ? reviewForm.querySelector('.mvno-review-btn-cancel') : null;
    
    let currentReviewApplicationId = null;
    let currentReviewProductId = null;
    let currentReviewId = null;
    let isEditMode = false;
    
    // 리뷰 작성/수정 버튼 클릭 이벤트 (전역 함수로 정의)
    window.initReviewButtonEvents = function() {
        // 필요한 변수들 다시 가져오기
        const reviewModal = document.getElementById('mvnoReviewModal');
        const reviewForm = document.getElementById('mvnoReviewForm');
        
        const buttons = document.querySelectorAll('.mno-sim-review-write-btn, .mno-sim-review-edit-btn');
        buttons.forEach(btn => {
            // 이미 이벤트가 바인딩된 버튼은 스킵
            if (btn.dataset.reviewEventAdded) return;
            btn.dataset.reviewEventAdded = 'true';
            
            btn.addEventListener('click', function(e) {
                e.stopPropagation(); // 카드 클릭 이벤트 방지
                e.preventDefault();
                
                window.currentReviewApplicationId = this.getAttribute('data-application-id');
                window.currentReviewProductId = this.getAttribute('data-product-id');
                const productType = this.getAttribute('data-product-type') || 'mno-sim';
                const hasReview = this.getAttribute('data-has-review') === '1';
                const reviewIdAttr = this.getAttribute('data-review-id');
                window.isEditMode = hasReview && reviewIdAttr !== null;
                window.currentReviewId = reviewIdAttr ? parseInt(reviewIdAttr) : null;
                
                // 디버깅: application_id 확인
                console.log('리뷰 버튼 클릭 - application_id:', window.currentReviewApplicationId, 'product_id:', window.currentReviewProductId);
                
                // product_id 유효성 검사
                if (!window.currentReviewProductId || window.currentReviewProductId === 'null' || window.currentReviewProductId === 'undefined' || window.currentReviewProductId === '0') {
                    console.error('product_id가 없습니다:', {
                        'data-product-id': this.getAttribute('data-product-id'),
                        'currentReviewProductId': window.currentReviewProductId,
                        button: this
                    });
                    showAlert('상품 정보를 찾을 수 없습니다. 페이지를 새로고침 후 다시 시도해주세요.', '오류');
                    return;
                }
                
                if (reviewModal) {
                    // 변수들 가져오기
                    const isEditMode = window.isEditMode;
                    const currentReviewApplicationId = window.currentReviewApplicationId;
                    const currentReviewProductId = window.currentReviewProductId;
                    
                    // 먼저 모달 제목과 버튼 텍스트를 설정
                    const modalTitle = reviewModal.querySelector('.mvno-review-modal-title');
                    if (modalTitle) {
                        modalTitle.textContent = isEditMode ? '리뷰 수정' : '리뷰 작성';
                    }
                    
                    // 제출 버튼 텍스트 변경
                    const submitBtn = reviewForm ? reviewForm.querySelector('.mvno-review-btn-submit') : null;
                    if (submitBtn) {
                        submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                    }
                    
                    // 삭제 버튼 표시/숨김
                    const deleteBtn = document.getElementById('mvnoReviewDeleteBtn');
                    if (deleteBtn) {
                        deleteBtn.style.display = isEditMode ? 'flex' : 'none';
                    }
                    
                    // 현재 스크롤 위치 저장
                    const scrollY = window.scrollY;
                    document.body.style.position = 'fixed';
                    document.body.style.top = `-${scrollY}px`;
                    document.body.style.width = '100%';
                    document.body.style.overflow = 'hidden';
                    
                    // 폼 초기화
                    if (reviewForm) {
                        reviewForm.reset();
                        // 별점 초기화
                        const starLabels = reviewForm.querySelectorAll('.star-label');
                        starLabels.forEach(label => {
                            label.classList.remove('active');
                            label.classList.remove('hover-active');
                        });
                    }
                    
                    // 수정 모드일 경우 기존 리뷰 데이터 로드
                    if (window.isEditMode && window.currentReviewApplicationId && window.currentReviewProductId) {
                        const apiPath = window.API_PATH || (window.BASE_PATH || '') + '/api';
                        fetch(`${apiPath}/get-review-by-application.php?application_id=${window.currentReviewApplicationId}&product_id=${window.currentReviewProductId}&product_type=${productType}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.review) {
                                    window.currentReviewId = data.review.id;
                                    console.log('MNO-SIM 리뷰 데이터 로드 성공 - review_id:', window.currentReviewId);
                                    
                                    // 삭제 버튼에 리뷰 ID 저장 및 표시
                                    const deleteBtn = document.getElementById('mvnoReviewDeleteBtn');
                                    if (deleteBtn) {
                                        deleteBtn.setAttribute('data-review-id', data.review.id);
                                        deleteBtn.style.display = 'flex';
                                        console.log('MNO-SIM 삭제 버튼에 data-review-id 설정:', data.review.id);
                                    }
                                    
                                    // 별점 설정
                                    if (data.review.kindness_rating) {
                                        const kindnessInput = reviewForm.querySelector(`input[name="kindness_rating"][value="${data.review.kindness_rating}"]`);
                                        if (kindnessInput) {
                                            kindnessInput.checked = true;
                                            const rating = parseInt(data.review.kindness_rating);
                                            const kindnessLabels = reviewForm.querySelectorAll('.mvno-star-rating[data-rating-type="kindness"] .star-label');
                                            kindnessLabels.forEach((label, index) => {
                                                if (index < rating) {
                                                    label.classList.add('active');
                                                } else {
                                                    label.classList.remove('active');
                                                }
                                            });
                                        }
                                    }
                                    if (data.review.speed_rating) {
                                        const speedInput = reviewForm.querySelector(`input[name="speed_rating"][value="${data.review.speed_rating}"]`);
                                        if (speedInput) {
                                            speedInput.checked = true;
                                            const rating = parseInt(data.review.speed_rating);
                                            const speedLabels = reviewForm.querySelectorAll('.mvno-star-rating[data-rating-type="speed"] .star-label');
                                            speedLabels.forEach((label, index) => {
                                                if (index < rating) {
                                                    label.classList.add('active');
                                                } else {
                                                    label.classList.remove('active');
                                                }
                                            });
                                        }
                                    }
                                    // 리뷰 내용 설정
                                    const reviewTextarea = reviewForm.querySelector('#mvnoReviewText');
                                    if (reviewTextarea && data.review.content) {
                                        reviewTextarea.value = data.review.content;
                                    }
                                }
                                // 모달 표시
                                reviewModal.style.display = 'flex';
                                setTimeout(() => {
                                    reviewModal.classList.add('show');
                                }, 10);
                            })
                            .catch(error => {
                                console.error('Error loading review:', error);
                                // 에러가 발생해도 모달 표시
                                reviewModal.style.display = 'flex';
                                setTimeout(() => {
                                    reviewModal.classList.add('show');
                                }, 10);
                            });
                    } else {
                        // 새 리뷰 작성 모드
                        reviewModal.style.display = 'flex';
                        setTimeout(() => {
                            reviewModal.classList.add('show');
                        }, 10);
                    }
                }
            });
        });
    }
    
    // 초기 리뷰 버튼 이벤트 바인딩
    window.initReviewButtonEvents();
    
    // 리뷰 모달 닫기
    function closeReviewModal() {
        if (reviewModal) {
            reviewModal.classList.remove('show');
            setTimeout(() => {
                reviewModal.style.display = 'none';
                // 스크롤 복원
                const scrollY = document.body.style.top;
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.width = '';
                document.body.style.overflow = '';
                if (scrollY) {
                    window.scrollTo(0, parseInt(scrollY || '0') * -1);
                }
                
                // 폼 초기화
                if (reviewForm) {
                    reviewForm.reset();
                    // 별점 초기화
                    const starLabels = reviewForm.querySelectorAll('.star-label');
                    starLabels.forEach(label => {
                        label.classList.remove('active');
                        label.classList.remove('hover-active');
                    });
                }
                const reviewTextCounter = document.getElementById('mvnoReviewTextCounter');
                if (reviewTextCounter) {
                    reviewTextCounter.textContent = '0';
                }
            }, 300);
        }
        currentReviewApplicationId = null;
        currentReviewProductId = null;
        currentReviewId = null;
        isEditMode = false;
    }
    
    // 모달 닫기 버튼
    if (reviewModalClose) {
        reviewModalClose.addEventListener('click', closeReviewModal);
    }
    
    // 오버레이 클릭 시 닫기
    if (reviewModalOverlay) {
        reviewModalOverlay.addEventListener('click', closeReviewModal);
    }
    
    // 취소 버튼
    if (reviewCancelBtn) {
        reviewCancelBtn.addEventListener('click', closeReviewModal);
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && reviewModal && (reviewModal.style.display === 'flex' || reviewModal.classList.contains('show'))) {
            closeReviewModal();
        }
    });
    
    // 리뷰 폼 제출
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('.mvno-review-btn-submit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '처리 중...';
            }
            
            // 필드 값 가져오기
            const kindnessRatingInput = reviewForm.querySelector('input[name="kindness_rating"]:checked');
            const speedRatingInput = reviewForm.querySelector('input[name="speed_rating"]:checked');
            const reviewTextarea = reviewForm.querySelector('#mvnoReviewText');
            const reviewText = reviewTextarea ? reviewTextarea.value.trim() : '';
            
            // 클라이언트 사이드 validation
            if (!kindnessRatingInput) {
                showAlert('친절해요 별점을 선택해주세요.', '알림');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                }
                return;
            }
            
            if (!speedRatingInput) {
                showAlert('개통 빨라요 별점을 선택해주세요.', '알림');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                }
                return;
            }
            
            if (!reviewText) {
                showAlert('리뷰 내용을 입력해주세요.', '알림');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                }
                return;
            }
            
            // product_id 유효성 검사 (window에서 가져오기)
            const productId = window.currentReviewProductId;
            if (!productId || productId === 'null' || productId === 'undefined' || productId === '0' || parseInt(productId) <= 0) {
                console.error('product_id 검증 실패:', {
                    currentReviewProductId: currentReviewProductId,
                    window_currentReviewProductId: window.currentReviewProductId,
                    productId: productId
                });
                showAlert('상품 ID가 올바르지 않습니다. 페이지를 새로고침 후 다시 시도해주세요.', '오류');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                }
                return;
            }
            
            // FormData 생성 (window에서 가져온 product_id 사용)
            const formData = new FormData();
            const productIdToSubmit = window.currentReviewProductId || currentReviewProductId;
            formData.append('product_id', productIdToSubmit);
            formData.append('product_type', 'mno-sim');
            formData.append('kindness_rating', kindnessRatingInput.value);
            formData.append('speed_rating', speedRatingInput.value);
            formData.append('content', reviewText);
            // application_id 디버깅 및 검증
            const appId = window.currentReviewApplicationId || currentReviewApplicationId;
            console.log('리뷰 작성 - application_id:', appId, 'type:', typeof appId);
            if (appId && appId !== 'null' && appId !== 'undefined' && appId !== '' && appId !== '0') {
                formData.append('application_id', appId);
            } else {
                console.error('application_id가 유효하지 않습니다:', appId);
                showAlert('주문 정보를 찾을 수 없습니다. 페이지를 새로고침 후 다시 시도해주세요.', '오류');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                }
                return;
            }
            
            if (isEditMode && currentReviewId) {
                formData.append('review_id', currentReviewId);
            }
            
            fetch((window.API_PATH || (window.BASE_PATH || '') + '/api') + '/submit-review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(isEditMode ? '리뷰가 수정되었습니다.' : '리뷰가 작성되었습니다.', '알림').then(() => {
                        closeReviewModal();
                        location.reload();
                    });
                } else {
                    showAlert(data.message || '리뷰 작성에 실패했습니다.', '오류');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('리뷰 작성 중 오류가 발생했습니다.', '오류');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                }
            });
        });
    }
    
    // 리뷰 삭제 버튼
    const reviewDeleteBtn = document.getElementById('mvnoReviewDeleteBtn');
    if (reviewDeleteBtn) {
        reviewDeleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // MVNO처럼 data-review-id 속성 또는 window.currentReviewId 사용
            const reviewId = this.getAttribute('data-review-id') || window.currentReviewId;
            console.log('MNO-SIM 리뷰 삭제 버튼 클릭 - reviewId:', reviewId, 'data-review-id:', this.getAttribute('data-review-id'), 'window.currentReviewId:', window.currentReviewId);
            
            if (!reviewId) {
                console.error('MNO-SIM 리뷰 삭제 실패: reviewId가 없습니다.');
                showAlert('리뷰 정보를 찾을 수 없습니다.', '오류');
                return;
            }
            
            showConfirm('정말로 리뷰를 삭제하시겠습니까?\n삭제된 리뷰는 복구할 수 없습니다.', '리뷰 삭제').then(confirmed => {
                if (!confirmed) return;
                
                // 삭제 버튼 비활성화
                this.disabled = true;
                const originalText = this.querySelector('span').textContent;
                this.querySelector('span').textContent = '삭제 중...';
                
                const formData = new FormData();
                formData.append('review_id', reviewId);
                formData.append('product_type', 'mno-sim');
                
                fetch((window.API_PATH || (window.BASE_PATH || '') + '/api') + '/delete-review.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('리뷰가 삭제되었습니다.', '알림').then(() => {
                            closeReviewModal();
                            location.reload();
                        });
                    } else {
                        showAlert(data.message || '리뷰 삭제에 실패했습니다.', '오류').then(() => {
                            this.disabled = false;
                            this.querySelector('span').textContent = originalText;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('리뷰 삭제 중 오류가 발생했습니다.', '오류').then(() => {
                        this.disabled = false;
                        this.querySelector('span').textContent = originalText;
                    });
                });
            });
        });
    }
    
    // 더보기 기능은 load-more-products.js에서 처리
});
</script>

<style>
/* 더보기 버튼 스타일 */
.load-more-container {
    margin-top: 24px;
    margin-bottom: 32px;
    width: 100%;
}

.load-more-btn {
    width: 100%;
    padding: 14px 24px;
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
}

.load-more-btn:hover:not(:disabled) {
    background: #4f46e5;
    box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
    transform: translateY(-1px);
}

.load-more-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    opacity: 0.7;
}
</style>

<script src="<?php echo getAssetPath('/assets/js/load-more-products.js'); ?>?v=2"></script>
<script>
// 새로 로드된 카드에 대한 클릭 이벤트를 다시 바인딩하는 함수
function initApplicationCardClickEvents() {
    const applicationCards = document.querySelectorAll('.application-card');
    applicationCards.forEach(card => {
        // 기존 이벤트 리스너가 중복 등록되지 않도록 확인
        if (!card.dataset.eventListenerAdded) {
            card.addEventListener('click', function(e) {
                const applicationId = this.getAttribute('data-application-id');
                if (applicationId) {
                    openModal(applicationId);
                }
            });
            card.dataset.eventListenerAdded = 'true'; // 플래그 설정
        }
    });
    
    // 리뷰 버튼 이벤트도 재바인딩
    if (typeof window.initReviewButtonEvents === 'function') {
        window.initReviewButtonEvents();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // 초기 페이지 로드 시 이벤트 바인딩
    initApplicationCardClickEvents();
});

// initReviewButtonEvents는 이미 전역으로 노출됨
</script>

<!-- 계속신청하기 모달 -->
<div id="continueApplicationModal" class="continue-application-modal" style="display: none;">
    <div class="continue-application-overlay"></div>
    <div class="continue-application-content">
        <div class="continue-application-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2 17L12 22L22 17" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2 12L12 17L22 12" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="continue-application-title"><span id="continueApplicationSellerName">판매자</span> 대리점으로 이동합니다.</div>
        <div class="continue-application-message">계속 가입신청을 진행해주셔야<br>가입 신청이 완료됩니다.</div>
        <div class="continue-application-submessage">최저가격으로 가입해보세요~</div>
        <button type="button" id="continueApplicationBtn" class="continue-application-button">계속신청하기</button>
    </div>
</div>

<style>
.continue-application-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.continue-application-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.continue-application-content {
    position: relative;
    background: white;
    border-radius: 16px;
    padding: 32px 24px;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    text-align: center;
}

.continue-application-icon {
    margin-bottom: 20px;
    display: flex;
    justify-content: center;
}

.continue-application-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 12px;
    line-height: 1.4;
}

.continue-application-message {
    font-size: 16px;
    color: #4b5563;
    margin-bottom: 8px;
    line-height: 1.5;
}

.continue-application-submessage {
    font-size: 14px;
    color: #6366f1;
    font-weight: 600;
    margin-bottom: 24px;
}

.continue-application-button {
    width: 100%;
    padding: 14px 24px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.continue-application-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
}

.continue-application-button:active {
    transform: translateY(0);
}
</style>

<script>
// 계속신청하기 모달 처리
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('continueApplicationModal');
    const continueBtn = document.getElementById('continueApplicationBtn');
    const overlay = modal ? modal.querySelector('.continue-application-overlay') : null;
    
    // sessionStorage에서 redirect_url 확인
    const pendingRedirectUrl = sessionStorage.getItem('pendingRedirectUrl');
    const pendingSellerName = sessionStorage.getItem('pendingSellerName');
    
    if (pendingRedirectUrl && modal) {
        // 판매자 이름 업데이트
        const sellerNameElement = document.getElementById('continueApplicationSellerName');
        if (sellerNameElement && pendingSellerName) {
            sellerNameElement.textContent = pendingSellerName;
        }
        
        // 모달 표시
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // 계속신청하기 버튼 클릭 시
        if (continueBtn) {
            continueBtn.addEventListener('click', function() {
                // 새 창으로 외부 링크 열기
                window.open(pendingRedirectUrl, '_blank');
                // sessionStorage에서 제거
                sessionStorage.removeItem('pendingRedirectUrl');
                sessionStorage.removeItem('pendingSellerName');
                // 모달 닫기
                if (modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
        }
        
        // 오버레이 클릭 시 모달 닫기
        if (overlay) {
            overlay.addEventListener('click', function() {
                sessionStorage.removeItem('pendingRedirectUrl');
                if (modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
        }
    }
});
</script>

<!-- 계속신청하기 모달 -->
<div id="continueApplicationModal" class="continue-application-modal" style="display: none;">
    <div class="continue-application-overlay"></div>
    <div class="continue-application-content">
        <div class="continue-application-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2 17L12 22L22 17" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2 12L12 17L22 12" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="continue-application-title"><span id="continueApplicationSellerName">판매자</span> 대리점으로 이동합니다.</div>
        <div class="continue-application-message">계속 가입신청을 진행해주셔야<br>가입 신청이 완료됩니다.</div>
        <div class="continue-application-submessage">최저가격으로 가입해보세요~</div>
        <button type="button" id="continueApplicationBtn" class="continue-application-button">계속신청하기</button>
    </div>
</div>

<style>
.continue-application-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.continue-application-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.continue-application-content {
    position: relative;
    background: white;
    border-radius: 16px;
    padding: 32px 24px;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    text-align: center;
}

.continue-application-icon {
    margin-bottom: 20px;
    display: flex;
    justify-content: center;
}

.continue-application-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 12px;
    line-height: 1.4;
}

.continue-application-message {
    font-size: 16px;
    color: #4b5563;
    margin-bottom: 8px;
    line-height: 1.5;
}

.continue-application-submessage {
    font-size: 14px;
    color: #6366f1;
    font-weight: 600;
    margin-bottom: 24px;
}

.continue-application-button {
    width: 100%;
    padding: 14px 24px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.continue-application-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
}

.continue-application-button:active {
    transform: translateY(0);
}
</style>

<script>
// 계속신청하기 모달 처리
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('continueApplicationModal');
    const continueBtn = document.getElementById('continueApplicationBtn');
    const overlay = modal ? modal.querySelector('.continue-application-overlay') : null;
    
    // sessionStorage에서 redirect_url 확인
    const pendingRedirectUrl = sessionStorage.getItem('pendingRedirectUrl');
    const pendingSellerName = sessionStorage.getItem('pendingSellerName');
    
    if (pendingRedirectUrl && modal) {
        // 판매자 이름 업데이트
        const sellerNameElement = document.getElementById('continueApplicationSellerName');
        if (sellerNameElement && pendingSellerName) {
            sellerNameElement.textContent = pendingSellerName;
        }
        
        // 모달 표시
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // 계속신청하기 버튼 클릭 시 새창으로 외부 링크 열기
        if (continueBtn) {
            continueBtn.addEventListener('click', function() {
                window.open(pendingRedirectUrl, '_blank');
                // sessionStorage에서 제거
                sessionStorage.removeItem('pendingRedirectUrl');
                sessionStorage.removeItem('pendingSellerName');
                // 모달 닫기
                modal.style.display = 'none';
                document.body.style.overflow = '';
            });
        }
        
        // 모달 배경 클릭 시 닫기
        if (overlay) {
            overlay.addEventListener('click', function() {
                sessionStorage.removeItem('pendingRedirectUrl');
                modal.style.display = 'none';
                document.body.style.overflow = '';
            });
        }
    }
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

