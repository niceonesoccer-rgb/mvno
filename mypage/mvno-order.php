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

// 필요한 함수 포함
require_once '../includes/data/product-functions.php';
require_once '../includes/data/db-config.php';

// DB에서 실제 신청 내역 가져오기
$applications = getUserMvnoApplications($user_id);

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div class="content-layout">
        <div class="plans-main-layout">
            <div class="plans-left-section">
                <!-- 페이지 헤더 -->
                <div style="margin-bottom: 24px; padding: 20px 0;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <a href="/MVNO/mypage/mypage.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 알뜰폰</h2>
                    </div>
                    <p style="font-size: 14px; color: #6b7280; margin: 0; margin-left: 36px;">카드를 클릭하면 신청 정보를 확인할 수 있습니다.</p>
                </div>
                
                <!-- 신청한 알뜰폰 목록 -->
                <div style="margin-bottom: 32px;">
                    <?php if (empty($applications)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: #6b7280;">
                            신청한 알뜰폰이 없습니다.
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($applications as $index => $app): ?>
                                <div class="plan-item application-card" 
                                     data-application-id="<?php echo htmlspecialchars($app['application_id'] ?? ''); ?>"
                                     style="padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"
                                     onmouseover="this.style.borderColor='#6366f1'; this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.15)'"
                                     onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'">
                                    
                                    <!-- 상단: 요금제 정보 -->
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                                        <div style="flex: 1;">
                                            <div style="font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 6px; line-height: 1.3;">
                                                <?php echo htmlspecialchars($app['provider'] ?? '알 수 없음'); ?> <?php echo htmlspecialchars($app['title'] ?? '요금제 정보 없음'); ?>
                                            </div>
                                            <?php if (!empty($app['data_main'])): ?>
                                                <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;">
                                                    <?php echo htmlspecialchars($app['data_main']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div style="display: flex; align-items: baseline; gap: 12px; flex-wrap: wrap;">
                                                <?php if (!empty($app['price_main'])): ?>
                                                    <div style="font-size: 18px; color: #1f2937; font-weight: 700;">
                                                        <?php echo htmlspecialchars($app['price_main']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($app['price_after'])): ?>
                                                    <div style="font-size: 14px; color: #6b7280;">
                                                        <?php echo htmlspecialchars($app['price_after']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div style="font-size: 12px; color: #9ca3af; text-align: right; white-space: nowrap; margin-left: 16px;">
                                            <?php echo htmlspecialchars($app['order_date'] ?? ''); ?>
                                        </div>
                                    </div>
                                    
                                    <!-- 중간: 약정기간 및 프로모션 기간 -->
                                    <div style="display: flex; gap: 16px; flex-wrap: wrap; padding: 12px 0; margin-bottom: 12px;">
                                        <?php if (!empty($app['contract_period'])): ?>
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #6366f1; flex-shrink: 0;">
                                                    <path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span style="font-size: 13px; color: #6b7280;">약정기간:</span>
                                                <span style="font-size: 13px; color: #374151; font-weight: 600;"><?php echo htmlspecialchars($app['contract_period']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($app['discount_period'])): ?>
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #10b981; flex-shrink: 0;">
                                                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span style="font-size: 13px; color: #6b7280;">프로모션:</span>
                                                <span style="font-size: 13px; color: #10b981; font-weight: 600;"><?php echo htmlspecialchars($app['discount_period']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- 하단: 주문번호 및 진행상황 -->
                                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                                        <div style="display: flex; gap: 16px; flex-wrap: wrap; font-size: 13px;">
                                            <?php if (!empty($app['order_number'])): ?>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <span style="color: #6b7280;">주문번호</span>
                                                    <span style="color: #374151; font-weight: 600;"><?php echo htmlspecialchars($app['order_number']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($app['status'])): ?>
                                            <div style="display: inline-flex; align-items: center; padding: 6px 12px; background: #eef2ff; border-radius: 6px;">
                                                <span style="font-size: 13px; color: #6366f1; font-weight: 600;"><?php echo htmlspecialchars($app['status']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- 신청 상세 정보 모달 -->
<div id="applicationDetailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 10000; overflow-y: auto; padding: 20px;">
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
        document.body.style.overflow = 'hidden'; // 배경 스크롤 방지
        
        // 로딩 표시
        modalContent.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #6b7280;">
                <div class="spinner" style="border: 3px solid #f3f4f6; border-top: 3px solid #6366f1; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
                <p>정보를 불러오는 중...</p>
            </div>
        `;
        
        // API 호출
        fetch(`/MVNO/api/get-application-details.php?application_id=${applicationId}`)
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
        document.body.style.overflow = ''; // 배경 스크롤 복원
    }
    
    function displayApplicationDetails(data) {
        const customer = data.customer || {};
        const additionalInfo = data.additional_info || {};
        const productSnapshot = additionalInfo.product_snapshot || {};
        
        let html = '<div style="display: flex; flex-direction: column; gap: 24px;">';
        
        // 주문 정보 섹션 (맨 위로 이동)
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
        
        // 상품 정보 섹션 (신청 시점)
        html += '<div>';
        html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">상품정보</h3>';
        html += '<div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px 16px; font-size: 14px;">';
        
        // 가입 형태를 상품 정보 섹션 첫 번째 항목으로 추가
        if (additionalInfo.subscription_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">가입 형태:</div>`;
            // 가입 형태 한글 변환
            let subscriptionTypeText = additionalInfo.subscription_type;
            const subscriptionTypeMap = {
                'new': '신규가입',
                'port': '번호이동',
                'change': '기기변경'
            };
            if (subscriptionTypeMap[subscriptionTypeText]) {
                subscriptionTypeText = subscriptionTypeMap[subscriptionTypeText];
            }
            html += `<div style="color: #1f2937;">${escapeHtml(subscriptionTypeText)}</div>`;
        }
        
        if (productSnapshot.provider) {
            html += `<div style="color: #6b7280; font-weight: 500;">통신사:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.provider)}</div>`;
        }
        
        if (productSnapshot.plan_name) {
            html += `<div style="color: #6b7280; font-weight: 500;">요금제명:</div>`;
            html += `<div style="color: #1f2937; font-weight: 600;">${escapeHtml(productSnapshot.plan_name)}</div>`;
        }
        
        if (productSnapshot.service_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터 속도:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.service_type)}</div>`;
        }
        
        if (productSnapshot.contract_period) {
            html += `<div style="color: #6b7280; font-weight: 500;">약정 기간:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.contract_period)}</div>`;
        }
        
        // 데이터 정보
        if (productSnapshot.data_amount) {
            html += `<div style="color: #6b7280; font-weight: 500;">데이터:</div>`;
            let dataText = productSnapshot.data_amount;
            if (productSnapshot.data_amount === '직접입력' && productSnapshot.data_amount_value) {
                // data_amount_value에 이미 단위가 포함되어 있는지 확인
                const dataValueStr = String(productSnapshot.data_amount_value);
                const unit = productSnapshot.data_unit || 'GB';
                // 끝에 단위가 이미 포함되어 있으면 추가하지 않음
                if (dataValueStr.endsWith('GB') || dataValueStr.endsWith('MB') || dataValueStr.endsWith('TB') || 
                    dataValueStr.endsWith('Mbps') || dataValueStr.endsWith('Gbps') || dataValueStr.endsWith('Kbps')) {
                    dataText = dataValueStr;
                } else {
                    dataText = dataValueStr + unit;
                }
            }
            if (productSnapshot.data_additional && productSnapshot.data_additional !== '없음') {
                if (productSnapshot.data_additional === '직접입력' && productSnapshot.data_additional_value) {
                    dataText += ' + ' + productSnapshot.data_additional_value;
                } else {
                    dataText += ' + ' + productSnapshot.data_additional;
                }
            }
            if (productSnapshot.data_exhausted && productSnapshot.data_exhausted !== '직접입력') {
                dataText += ' + ' + productSnapshot.data_exhausted;
            } else if (productSnapshot.data_exhausted === '직접입력' && productSnapshot.data_exhausted_value) {
                dataText += ' + ' + productSnapshot.data_exhausted_value;
            }
            html += `<div style="color: #1f2937;">${escapeHtml(dataText)}</div>`;
        }
        
        // 통화 정보
        if (productSnapshot.call_type) {
            html += `<div style="color: #6b7280; font-weight: 500;">통화:</div>`;
            let callText = productSnapshot.call_type;
            if (productSnapshot.call_type === '직접입력' && productSnapshot.call_amount) {
                // call_amount에 이미 단위가 포함되어 있는지 확인
                const callAmountStr = String(productSnapshot.call_amount);
                const unit = productSnapshot.call_amount_unit || '분';
                // 끝에 단위가 이미 포함되어 있으면 추가하지 않음
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
            html += `<div style="color: #6b7280; font-weight: 500;">문자:</div>`;
            let smsText = productSnapshot.sms_type;
            if (productSnapshot.sms_type === '직접입력' && productSnapshot.sms_amount) {
                // sms_amount에 이미 단위가 포함되어 있는지 확인
                const smsAmountStr = String(productSnapshot.sms_amount);
                const unit = productSnapshot.sms_amount_unit || '건';
                // 끝에 단위가 이미 포함되어 있으면 추가하지 않음
                if (smsAmountStr.endsWith('분') || smsAmountStr.endsWith('초') || smsAmountStr.endsWith('건')) {
                    smsText = smsAmountStr;
                } else {
                    smsText = smsAmountStr + unit;
                }
            }
            html += `<div style="color: #1f2937;">${escapeHtml(smsText)}</div>`;
        }
        
        // 가격 정보
        if (productSnapshot.price_main) {
            html += `<div style="color: #6b7280; font-weight: 500;">기본 요금:</div>`;
            html += `<div style="color: #1f2937; font-weight: 600;">월 ${formatNumber(productSnapshot.price_main)}원</div>`;
        }
        
        if (productSnapshot.price_after) {
            html += `<div style="color: #6b7280; font-weight: 500;">할인 후 요금:</div>`;
            html += `<div style="color: #6366f1; font-weight: 600;">월 ${formatNumber(productSnapshot.price_after)}원</div>`;
        }
        
        if (productSnapshot.discount_period) {
            html += `<div style="color: #6b7280; font-weight: 500;">할인 기간:</div>`;
            html += `<div style="color: #1f2937;">${escapeHtml(productSnapshot.discount_period)}</div>`;
        }
        
        // 프로모션 정보
        if (productSnapshot.promotions) {
            let promotions = [];
            try {
                if (typeof productSnapshot.promotions === 'string') {
                    promotions = JSON.parse(productSnapshot.promotions);
                } else if (Array.isArray(productSnapshot.promotions)) {
                    promotions = productSnapshot.promotions;
                }
            } catch(e) {
                // JSON 파싱 실패 시 무시
            }
            
            if (promotions.length > 0) {
                html += `<div style="color: #6b7280; font-weight: 500;">프로모션:</div>`;
                html += `<div style="color: #1f2937;">${promotions.map(p => escapeHtml(p)).join(', ')}</div>`;
            }
        }
        
        html += '</div></div>';
        
        html += '</div>';
        
        modalContent.innerHTML = html;
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatNumber(num) {
        if (!num) return '0';
        return parseInt(num).toLocaleString('ko-KR');
    }
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>


