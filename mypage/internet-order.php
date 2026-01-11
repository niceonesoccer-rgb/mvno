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

// 포인트 설정 및 함수 포함
require_once '../includes/data/point-settings.php';
require_once '../includes/data/product-functions.php';
require_once '../includes/data/db-config.php';
require_once '../includes/data/review-settings.php';
require_once '../includes/data/plan-data.php';

// 페이지 번호 및 제한 설정
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20; // 초기 로드 개수
$offset = ($page - 1) * $limit;

// DB에서 실제 신청 내역 가져오기 (페이징 적용)
$internets = getUserInternetApplications($user_id, $limit, $offset);
$totalCount = count(getUserInternetApplications($user_id)); // 전체 개수

$currentCount = count($internets);
$remainingCount = max(0, $totalCount - ($offset + $currentCount));
$hasMore = ($offset + $currentCount) < $totalCount;

// 헤더 포함
include '../includes/header.php';
// 인터넷 리뷰 모달 포함
include '../includes/components/internet-review-modal.php';
// 인터넷 리뷰 삭제 모달 포함
include '../includes/components/internet-review-delete-modal.php';
?>

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
                        <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 인터넷</h2>
                    </div>
                    <p style="font-size: 14px; color: #6b7280; margin: 0; margin-left: 36px;">카드를 클릭하면 신청 정보를 확인할 수 있습니다.</p>
                </div>

                <!-- 전체 개수 표시 -->
                <?php if (!empty($internets)): ?>
                <div class="plans-results-count">
                    <span><?php echo number_format($totalCount); ?>개의 결과</span>
                </div>
                <?php endif; ?>
                
                <!-- 신청한 인터넷 목록 -->
                <div style="margin-bottom: 32px;">
                    <?php if (empty($internets)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: #6b7280;">
                            신청한 인터넷이 없습니다.
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;" id="internet-orders-container">
                            <?php foreach ($internets as $index => $internet): ?>
                                <div class="order-item-wrapper">
                                    <?php include __DIR__ . '/../includes/components/internet-order-card.php'; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- 더보기 버튼 -->
                        <?php if ($hasMore && $totalCount > 0): ?>
                        <div class="load-more-container" id="load-more-anchor" style="margin-top: 32px; margin-bottom: 32px;">
                            <button id="load-more-internet-order-btn" class="load-more-btn" 
                                    data-type="internet" 
                                    data-page="2" 
                                    data-total="<?php echo $totalCount; ?>"
                                    data-order="true"
                                    style="width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                                더보기 (<span id="remaining-count"><?php echo number_format($remainingCount); ?></span>개 남음)
                            </button>
                        </div>
                        <?php endif; ?>
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
            <h2 style="font-size: 24px; font-weight: bold; margin: 0; color: #1f2937;">상품 정보</h2>
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

.product-modal-body {
    padding: 24px;
}

.product-info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.product-info-table th {
    background: #f9fafb;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border: 1px solid #e5e7eb;
    width: 30%;
}

.product-info-table td {
    padding: 12px;
    border: 1px solid #e5e7eb;
    color: #1f2937;
}

.product-info-table tr:nth-child(even) {
    background: #f9fafb;
}

/* 리뷰 작성 영역 반응형 스타일 */
.review-section-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* 전화번호 반응형 스타일 */
.phone-inquiry-pc {
    display: block;
}

.phone-inquiry-mobile {
    display: none !important;
}

@media (max-width: 768px) {
    .review-section-layout {
        grid-template-columns: 1fr;
    }
    
    .phone-inquiry-pc {
        display: none;
    }
    
    .phone-inquiry-mobile {
        display: flex !important;
    }
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
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    // 배경 클릭 시 모달 닫기
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'block') {
            closeModal();
        }
    });
    
    function openModal(applicationId) {
        if (!modal || !modalContent) return;
        
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
        fetch((window.API_PATH || (window.BASE_PATH || '') + '/api') + `/get-application-details.php?application_id=${applicationId}`)
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
                modalContent.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc2626;">
                        <p>정보를 불러오는 중 오류가 발생했습니다.</p>
                        <p style="font-size: 14px; margin-top: 8px;">네트워크 오류가 발생했습니다.</p>
                    </div>
                `;
            });
    }
    
    function closeModal() {
        if (!modal) return;
        modal.style.display = 'none';
        // 배경 페이지 스크롤 복원
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        document.documentElement.style.overflow = '';
    }
    
    function displayApplicationDetails(data) {
        if (!modalContent) return;
        
        const customer = data.customer || {};
        const additionalInfo = data.additional_info || {};
        const productSnapshot = additionalInfo.product_snapshot || {};
        
        
        let html = '<div class="product-modal-body">';
        
        // 통신사 로고 표시 (신청 시점 정보 사용)
        const registrationPlace = productSnapshot.registration_place || '';
        let logoUrl = '';
        let displayProvider = registrationPlace;
        
        // 정확한 매칭: 더 구체적인 값 우선 확인
        // 순서 중요: "SKT"를 "KT"보다 먼저 확인해야 함 (SKT에 KT가 포함되어 있음)
        if (registrationPlace.toLowerCase().indexOf('kt skylife') !== -1 || registrationPlace.toLowerCase().indexOf('ktskylife') !== -1) {
            // "KT skylife" -> ktskylife.svg
            logoUrl = (window.BASE_PATH || '') + '/assets/images/internets/ktskylife.svg';
        } else if (registrationPlace.indexOf('SKT') !== -1 || registrationPlace.toLowerCase().indexOf('sk broadband') !== -1 || registrationPlace.indexOf('SK') !== -1) {
            // "SKT", "SK broadband", "SK" -> broadband.svg (SKT broadband)
            // "SKT"를 "KT"보다 먼저 확인 (SKT에 KT가 포함되어 있으므로)
            logoUrl = (window.BASE_PATH || '') + '/assets/images/internets/broadband.svg';
        } else if (registrationPlace.indexOf('KT') !== -1) {
            // "KT" (skylife, SKT 제외) -> kt.svg
            logoUrl = (window.BASE_PATH || '') + '/assets/images/internets/kt.svg';
        } else if (registrationPlace.indexOf('LG') !== -1 || registrationPlace.indexOf('LGU') !== -1) {
            logoUrl = (window.BASE_PATH || '') + '/assets/images/internets/lgu.svg';
        }
        
        // provider 텍스트 변환: "SKT" -> "SKT broadband"
        if (registrationPlace.indexOf('SKT') !== -1 && registrationPlace.toLowerCase().indexOf('broadband') === -1) {
            displayProvider = 'SKT broadband';
        }
        
        if (logoUrl) {
            html += `<div style="margin-bottom: 12px;"><img src="${escapeHtml(logoUrl)}" alt="${escapeHtml(displayProvider)}" style="height: 40px; object-fit: contain;"></div>`;
        }
        // provider 텍스트도 명확히 표시
        if (displayProvider) {
            html += `<div style="font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 16px;">${escapeHtml(displayProvider)}</div>`;
        }
        
        // 기본 정보 테이블
        let serviceTypeDisplay = productSnapshot.service_type || '';
        if (serviceTypeDisplay === '인터넷+TV') {
            serviceTypeDisplay = '인터넷 + TV 결합';
        } else if (serviceTypeDisplay === '인터넷+TV+핸드폰') {
            serviceTypeDisplay = '인터넷 + TV + 핸드폰 결합';
        }
        
        html += '<table class="product-info-table">';
        html += '<tbody>';
        
        if (productSnapshot.registration_place) {
            html += `<tr><th>신청 인터넷 회선</th><td>${escapeHtml(productSnapshot.registration_place)}</td></tr>`;
        }
        
        if (serviceTypeDisplay) {
            html += `<tr><th>결합여부</th><td>${escapeHtml(serviceTypeDisplay)}</td></tr>`;
        }
        
        if (productSnapshot.speed_option) {
            // 속도 단위 변환 (DB 값 -> 표시용)
            const speedMap = {
                '100M': '100MB',
                '500M': '500MB',
                '1G': '1GB',
                '2.5G': '2.5GB',
                '5G': '5GB',
                '10G': '10GB'
            };
            const speedDisplay = speedMap[productSnapshot.speed_option] || productSnapshot.speed_option;
            html += `<tr><th>가입 속도</th><td>${escapeHtml(speedDisplay)}</td></tr>`;
        }
        
        if (productSnapshot.monthly_fee !== undefined && productSnapshot.monthly_fee !== null) {
            html += `<tr><th>월 요금제</th><td>${formatNumber(productSnapshot.monthly_fee)}원</td></tr>`;
        }
        
        // 기존 인터넷 통신사 정보 (기본 정보 테이블에 포함)
        const existingCompany = additionalInfo.currentCompany || additionalInfo.existing_company || additionalInfo.existingCompany || '';
        if (existingCompany) {
            html += `<tr><th>기존 인터넷 통신사</th><td>${escapeHtml(existingCompany)}</td></tr>`;
        }
        
        html += '</tbody></table>';
        
        // 프로모션 정보 표시 (값으로만)
        let promotionTitle = productSnapshot.promotion_title || '';
        let promotions = [];
        
        if (productSnapshot.promotions) {
            if (typeof productSnapshot.promotions === 'string') {
                try {
                    promotions = JSON.parse(productSnapshot.promotions) || [];
                } catch(e) {
                    promotions = [productSnapshot.promotions];
                }
            } else if (Array.isArray(productSnapshot.promotions)) {
                promotions = productSnapshot.promotions;
            }
        }
        
        const filteredPromotions = promotions.filter(p => p && p.trim());
        const promotionCount = filteredPromotions.length;
        
        if (promotionCount > 0 || promotionTitle) {
            html += '<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">프로모션 이벤트</h3>';
            html += '<table class="product-info-table"><tbody>';
            
            if (promotionTitle) {
                html += `<tr><th>프로모션 제목</th><td>${escapeHtml(promotionTitle)}</td></tr>`;
            }
            
            if (promotionCount > 0) {
                const promotionList = filteredPromotions.map(p => escapeHtml(p.trim())).join(', ');
                html += `<tr><th>프로모션 항목</th><td>${promotionList}</td></tr>`;
            }
            
            html += '</tbody></table>';
        }
        
        // 현금지급 섹션
        if (productSnapshot.cash_payment_names) {
            let cashNames = [];
            let cashPrices = [];
            
            // JSON 문자열 파싱
            if (typeof productSnapshot.cash_payment_names === 'string') {
                try {
                    cashNames = JSON.parse(productSnapshot.cash_payment_names) || [];
                } catch(e) {
                    cashNames = [productSnapshot.cash_payment_names];
                }
            } else if (Array.isArray(productSnapshot.cash_payment_names)) {
                cashNames = productSnapshot.cash_payment_names;
            }
            
            if (productSnapshot.cash_payment_prices) {
                if (typeof productSnapshot.cash_payment_prices === 'string') {
                    try {
                        cashPrices = JSON.parse(productSnapshot.cash_payment_prices) || [];
                    } catch(e) {
                        cashPrices = [productSnapshot.cash_payment_prices];
                    }
                } else if (Array.isArray(productSnapshot.cash_payment_prices)) {
                    cashPrices = productSnapshot.cash_payment_prices;
                }
            }
            
            if (cashNames.length > 0) {
                html += '<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">현금지급</h3>';
                html += '<table class="product-info-table"><tbody>';
                for (let i = 0; i < cashNames.length; i++) {
                    const price = cashPrices[i] || '';
                    html += `<tr><th>${escapeHtml(cashNames[i])}</th><td>${escapeHtml(price)}</td></tr>`;
                }
                html += '</tbody></table>';
            }
        }
        
        // 상품권 지급 섹션
        if (productSnapshot.gift_card_names) {
            let giftNames = [];
            let giftPrices = [];
            
            // JSON 문자열 파싱
            if (typeof productSnapshot.gift_card_names === 'string') {
                try {
                    giftNames = JSON.parse(productSnapshot.gift_card_names) || [];
                } catch(e) {
                    giftNames = [productSnapshot.gift_card_names];
                }
            } else if (Array.isArray(productSnapshot.gift_card_names)) {
                giftNames = productSnapshot.gift_card_names;
            }
            
            if (productSnapshot.gift_card_prices) {
                if (typeof productSnapshot.gift_card_prices === 'string') {
                    try {
                        giftPrices = JSON.parse(productSnapshot.gift_card_prices) || [];
                    } catch(e) {
                        giftPrices = [productSnapshot.gift_card_prices];
                    }
                } else if (Array.isArray(productSnapshot.gift_card_prices)) {
                    giftPrices = productSnapshot.gift_card_prices;
                }
            }
            
            if (giftNames.length > 0) {
                html += '<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">상품권 지급</h3>';
                html += '<table class="product-info-table"><tbody>';
                for (let i = 0; i < giftNames.length; i++) {
                    const price = giftPrices[i] || '';
                    html += `<tr><th>${escapeHtml(giftNames[i])}</th><td>${escapeHtml(price)}</td></tr>`;
                }
                html += '</tbody></table>';
            }
        }
        
        // 장비 제공 섹션
        if (productSnapshot.equipment_names) {
            let equipmentNames = [];
            let equipmentPrices = [];
            
            // JSON 문자열 파싱
            if (typeof productSnapshot.equipment_names === 'string') {
                try {
                    equipmentNames = JSON.parse(productSnapshot.equipment_names) || [];
                } catch(e) {
                    equipmentNames = [productSnapshot.equipment_names];
                }
            } else if (Array.isArray(productSnapshot.equipment_names)) {
                equipmentNames = productSnapshot.equipment_names;
            }
            
            if (productSnapshot.equipment_prices) {
                if (typeof productSnapshot.equipment_prices === 'string') {
                    try {
                        equipmentPrices = JSON.parse(productSnapshot.equipment_prices) || [];
                    } catch(e) {
                        equipmentPrices = [productSnapshot.equipment_prices];
                    }
                } else if (Array.isArray(productSnapshot.equipment_prices)) {
                    equipmentPrices = productSnapshot.equipment_prices;
                }
            }
            
            if (equipmentNames.length > 0) {
                html += '<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">장비 제공</h3>';
                html += '<table class="product-info-table"><tbody>';
                for (let i = 0; i < equipmentNames.length; i++) {
                    const price = equipmentPrices[i] || '';
                    html += `<tr><th>${escapeHtml(equipmentNames[i])}</th><td>${escapeHtml(price)}</td></tr>`;
                }
                html += '</tbody></table>';
            }
        }
        
        // 설치 및 기타 서비스 섹션
        if (productSnapshot.installation_names) {
            let installationNames = [];
            let installationPrices = [];
            
            // JSON 문자열 파싱
            if (typeof productSnapshot.installation_names === 'string') {
                try {
                    installationNames = JSON.parse(productSnapshot.installation_names) || [];
                } catch(e) {
                    installationNames = [productSnapshot.installation_names];
                }
            } else if (Array.isArray(productSnapshot.installation_names)) {
                installationNames = productSnapshot.installation_names;
            }
            
            if (productSnapshot.installation_prices) {
                if (typeof productSnapshot.installation_prices === 'string') {
                    try {
                        installationPrices = JSON.parse(productSnapshot.installation_prices) || [];
                    } catch(e) {
                        installationPrices = [productSnapshot.installation_prices];
                    }
                } else if (Array.isArray(productSnapshot.installation_prices)) {
                    installationPrices = productSnapshot.installation_prices;
                }
            }
            
            if (installationNames.length > 0) {
                html += '<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">설치 및 기타 서비스</h3>';
                html += '<table class="product-info-table"><tbody>';
                for (let i = 0; i < installationNames.length; i++) {
                    const price = installationPrices[i] || '';
                    html += `<tr><th>${escapeHtml(installationNames[i])}</th><td>${escapeHtml(price)}</td></tr>`;
                }
                html += '</tbody></table>';
            }
        }
        
        html += '</div>';
        
        // 판매자 상담 정보 섹션 추가
        if (data.seller_consultation) {
            const consultation = data.seller_consultation;
            const hasConsultation = consultation.phone || consultation.mobile;
            
            if (hasConsultation) {
                html += '<div style="border-top: 2px solid #e5e7eb; padding-top: 24px; margin-top: 24px;">';
                html += '<h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #6366f1;">상담하기</h3>';
                html += '<div style="display: flex; flex-direction: column; gap: 12px;">';
                
                const telNumber = consultation.mobile ? consultation.mobile.replace(/[^0-9]/g, '') : (consultation.phone ? consultation.phone.replace(/[^0-9]/g, '') : '');
                const telDisplay = consultation.mobile || consultation.phone;
                if (telNumber) {
                    html += `<a href="tel:${telNumber}" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; background: #10b981; color: white; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        전화 상담: ${escapeHtml(telDisplay)}
                    </a>`;
                }
                
                html += '</div></div>';
            }
        }

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
    
    // 더보기 기능은 load-more-products.js에서 처리
    
    // 인터넷 리뷰 작성/수정 기능
    window.currentReviewApplicationId = null;
    window.currentReviewProductId = null;
    window.currentReviewId = null;
    window.isEditMode = false;
    
    // 리뷰 작성/수정 버튼 클릭 이벤트 (전역 함수로 정의)
    window.initReviewButtonEvents = function() {
        // 필요한 변수들 다시 가져오기
        const reviewModal = document.getElementById('internetReviewModal');
        const reviewForm = document.getElementById('internetReviewForm');
        
        const buttons = document.querySelectorAll('.internet-review-write-btn, .internet-review-edit-btn');
        buttons.forEach(btn => {
            // 이미 이벤트가 바인딩된 버튼은 스킵
            if (btn.dataset.reviewEventAdded) return;
            btn.dataset.reviewEventAdded = 'true';
            
            btn.addEventListener('click', function(e) {
                e.stopPropagation(); // 카드 클릭 이벤트 방지
                e.preventDefault();
                
                const applicationIdAttr = this.getAttribute('data-application-id');
                const productIdAttr = this.getAttribute('data-product-id');
                const hasReview = this.getAttribute('data-has-review') === '1';
                const reviewIdAttr = this.getAttribute('data-review-id');
                
                if (!productIdAttr || productIdAttr === 'null' || productIdAttr === '') {
                    console.error('리뷰 버튼 클릭 오류: data-product-id 속성이 없거나 올바르지 않습니다.', this);
                    if (typeof showMessageModal === 'function') {
                        showMessageModal('상품 정보를 찾을 수 없습니다.', 'error');
                    } else if (typeof showAlert === 'function') {
                        showAlert('상품 정보를 찾을 수 없습니다.', '오류');
                    } else {
                        alert('상품 정보를 찾을 수 없습니다.');
                    }
                    return;
                }
                
                window.currentReviewApplicationId = applicationIdAttr;
                window.currentReviewProductId = productIdAttr;
                window.isEditMode = hasReview && reviewIdAttr !== null;
                window.currentReviewId = reviewIdAttr ? parseInt(reviewIdAttr) : null;
                
                if (reviewModal) {
                    // 변수들 가져오기
                    const isEditMode = window.isEditMode;
                    const currentReviewApplicationId = window.currentReviewApplicationId;
                    const currentReviewProductId = window.currentReviewProductId;
                    
                    // 먼저 모달 제목과 버튼 텍스트를 설정 (모달이 보이기 전에)
                    const modalTitle = reviewModal.querySelector('.internet-review-modal-title');
                    if (modalTitle) {
                        modalTitle.textContent = isEditMode ? '리뷰 수정' : '리뷰 작성';
                    }
                    
                    // 제출 버튼 텍스트 변경
                    const submitBtn = reviewForm ? reviewForm.querySelector('.internet-review-btn-submit span') : null;
                    if (submitBtn) {
                        submitBtn.textContent = isEditMode ? '저장하기' : '작성하기';
                    }
                    
                    // 삭제 버튼 표시/숨김
                    const deleteBtn = document.getElementById('internetReviewDeleteBtn');
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
                        // 텍스트 카운터 초기화
                        const reviewTextCounter = document.getElementById('reviewTextCounter');
                        if (reviewTextCounter) {
                            reviewTextCounter.textContent = '0';
                        }
                    }
                    
                    // 모달 표시
                    reviewModal.style.display = 'flex';
                    setTimeout(() => {
                        reviewModal.classList.add('show');
                    }, 10);
                    
                    // 수정 모드인 경우 기존 리뷰 데이터 로드
                    if (isEditMode && currentReviewProductId) {
                        loadExistingReview(currentReviewProductId);
                    }
                }
            });
        });
    };
    
    // 초기 리뷰 버튼 이벤트 바인딩
    window.initReviewButtonEvents();
    
    // 모달 닫기 함수 및 이벤트
    const reviewModalClose = document.getElementById('internetReviewModal') ? document.getElementById('internetReviewModal').querySelector('.internet-review-modal-close') : null;
    const reviewModalOverlay = document.getElementById('internetReviewModal') ? document.getElementById('internetReviewModal').querySelector('.internet-review-modal-overlay') : null;
    const reviewCancelBtn = document.getElementById('internetReviewForm') ? document.getElementById('internetReviewForm').querySelector('.internet-review-btn-cancel') : null;
    
    // 기존 리뷰 데이터 로드
    function loadExistingReview(productId) {
        // application_id가 있으면 application_id 기반으로 조회, 없으면 product_id로 조회
        let url = (window.API_PATH || (window.BASE_PATH || '') + '/api') + '/get-review.php?product_id=' + productId + '&product_type=internet';
        if (window.currentReviewApplicationId) {
            url += '&application_id=' + window.currentReviewApplicationId;
        }
        
        const reviewModal = document.getElementById('internetReviewModal');
        const reviewForm = document.getElementById('internetReviewForm');
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.review) {
                    const review = data.review;
                    window.currentReviewId = review.id;
                    
                    // 삭제 버튼에 리뷰 ID 저장 및 표시
                    const deleteBtn = document.getElementById('internetReviewDeleteBtn');
                    if (deleteBtn) {
                        deleteBtn.setAttribute('data-review-id', review.id);
                        deleteBtn.style.display = 'flex';
                    }
                    
                    const kindnessRating = parseInt(review.kindness_rating || review.rating || 0);
                    const speedRating = parseInt(review.speed_rating || review.rating || 0);
                    
                    // 친절해요 별점 설정
                    const kindnessInput = reviewForm.querySelector(`input[name="kindness_rating"][value="${kindnessRating}"]`);
                    if (kindnessInput) {
                        kindnessInput.checked = true;
                        const kindnessLabels = Array.from(reviewForm.querySelectorAll('.internet-star-rating[data-rating-type="kindness"] .star-label'));
                        kindnessLabels.forEach((label, index) => {
                            if (index < kindnessRating) {
                                label.classList.add('active');
                            } else {
                                label.classList.remove('active');
                            }
                        });
                    }
                    
                    // 설치 빨라요 별점 설정
                    const speedInput = reviewForm.querySelector(`input[name="speed_rating"][value="${speedRating}"]`);
                    if (speedInput) {
                        speedInput.checked = true;
                        const speedLabels = Array.from(reviewForm.querySelectorAll('.internet-star-rating[data-rating-type="speed"] .star-label'));
                        speedLabels.forEach((label, index) => {
                            if (index < speedRating) {
                                label.classList.add('active');
                            } else {
                                label.classList.remove('active');
                            }
                        });
                    }
                    
                    // 리뷰 내용 설정
                    const reviewTextarea = document.getElementById('internetReviewText');
                    if (reviewTextarea && review.content) {
                        reviewTextarea.value = review.content;
                        // 텍스트 카운터 업데이트
                        const reviewTextCounter = document.getElementById('reviewTextCounter');
                        if (reviewTextCounter) {
                            const length = review.content.length;
                            reviewTextCounter.textContent = length;
                            if (length > 1000) {
                                reviewTextCounter.style.color = '#ef4444';
                            } else {
                                reviewTextCounter.style.color = '#6366f1';
                            }
                        }
                    }
                } else {
                    if (typeof showMessageModal === 'function') {
                        showMessageModal('리뷰를 불러올 수 없습니다.', 'warning');
                    } else if (typeof showAlert === 'function') {
                        showAlert('리뷰를 불러올 수 없습니다.', '알림');
                    } else {
                        alert('리뷰를 불러올 수 없습니다.');
                    }
                    // 작성 모드로 전환
                    window.isEditMode = false;
                    const modalTitle = reviewModal ? reviewModal.querySelector('.internet-review-modal-title') : null;
                    if (modalTitle) {
                        modalTitle.textContent = '리뷰 작성';
                    }
                    const submitBtn = reviewForm ? reviewForm.querySelector('.internet-review-btn-submit span') : null;
                    if (submitBtn) {
                        submitBtn.textContent = '작성하기';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading review:', error);
                if (typeof showMessageModal === 'function') {
                    showMessageModal('리뷰를 불러오는 중 오류가 발생했습니다.', 'error');
                } else if (typeof showAlert === 'function') {
                    showAlert('리뷰를 불러오는 중 오류가 발생했습니다.', '오류');
                } else {
                    alert('리뷰를 불러오는 중 오류가 발생했습니다.');
                }
                window.isEditMode = false;
            });
    }
    
    // 리뷰 모달 닫기
    function closeReviewModal() {
        const reviewModal = document.getElementById('internetReviewModal');
        const reviewForm = document.getElementById('internetReviewForm');
        if (reviewModal) {
            reviewModal.classList.remove('show');
            setTimeout(() => {
                reviewModal.style.display = 'none';
                
                // 스크롤 위치 복원
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
                // 텍스트 카운터 초기화
                const reviewTextCounter = document.getElementById('reviewTextCounter');
                if (reviewTextCounter) {
                    reviewTextCounter.textContent = '0';
                }
                // 모달 상태 초기화
                window.currentReviewApplicationId = null;
                window.currentReviewProductId = null;
                window.currentReviewId = null;
                window.isEditMode = false;
                
                // 모달 제목 및 버튼 텍스트 초기화
                const modalTitle = reviewModal.querySelector('.internet-review-modal-title');
                if (modalTitle) {
                    modalTitle.textContent = '리뷰 작성';
                }
                const submitBtn = reviewForm ? reviewForm.querySelector('.internet-review-btn-submit span') : null;
                if (submitBtn) {
                    submitBtn.textContent = '작성하기';
                }
            }, 300);
        }
    }
    
    // 모달 닫기 이벤트
    if (reviewModalClose) {
        reviewModalClose.addEventListener('click', closeReviewModal);
    }
    
    if (reviewModalOverlay) {
        reviewModalOverlay.addEventListener('click', closeReviewModal);
    }
    
    if (reviewCancelBtn) {
        reviewCancelBtn.addEventListener('click', closeReviewModal);
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const reviewModal = document.getElementById('internetReviewModal');
            if (reviewModal && reviewModal.classList.contains('show')) {
                closeReviewModal();
            }
        }
    });
    
    // 별점 이벤트는 internet-review-modal.php 컴포넌트에서 처리됨
    
    // 리뷰 폼 제출
    const reviewForm = document.getElementById('internetReviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const kindnessRatingInput = reviewForm.querySelector('input[name="kindness_rating"]:checked');
            const speedRatingInput = reviewForm.querySelector('input[name="speed_rating"]:checked');
            const reviewText = document.getElementById('internetReviewText') ? document.getElementById('internetReviewText').value.trim() : '';
            
            if (!kindnessRatingInput) {
                showMessageModal('친절해요 별점을 선택해주세요.', 'warning');
                return;
            }
            
            if (!speedRatingInput) {
                showMessageModal('설치 빨라요 별점을 선택해주세요.', 'warning');
                return;
            }
            
            if (!reviewText) {
                showMessageModal('리뷰 내용을 입력해주세요.', 'warning');
                return;
            }
            
            // 전역 변수 확인
            if (!window.currentReviewProductId || window.currentReviewProductId <= 0) {
                if (typeof showMessageModal === 'function') {
                    showMessageModal('상품 정보를 찾을 수 없습니다.', 'error');
                } else if (typeof showAlert === 'function') {
                    showAlert('상품 정보를 찾을 수 없습니다.', '오류');
                } else {
                    alert('상품 정보를 찾을 수 없습니다.');
                }
                return;
            }
            
            const kindnessRating = parseInt(kindnessRatingInput.value);
            const speedRating = parseInt(speedRatingInput.value);
            const averageRating = Math.round((kindnessRating + speedRating) / 2);
            
            if (kindnessRating < 1 || kindnessRating > 5 || speedRating < 1 || speedRating > 5) {
                if (typeof showMessageModal === 'function') {
                    showMessageModal('별점은 1~5 사이의 값이어야 합니다.', 'error');
                } else if (typeof showAlert === 'function') {
                    showAlert('별점은 1~5 사이의 값이어야 합니다.', '오류');
                } else {
                    alert('별점은 1~5 사이의 값이어야 합니다.');
                }
                return;
            }
            
            
            // 제출 버튼 비활성화 및 로딩 상태
            const submitBtn = reviewForm.querySelector('.internet-review-btn-submit');
            const originalBtnContent = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>' + (window.isEditMode ? '저장 중...' : '작성 중...') + '</span>';
            
            // 리뷰 제출 (수정 모드인 경우 review_id 포함)
            const formData = new FormData();
            formData.append('product_id', window.currentReviewProductId);
            formData.append('product_type', 'internet');
            formData.append('rating', averageRating);
            formData.append('kindness_rating', kindnessRating); // 친절해요 별점
            formData.append('speed_rating', speedRating); // 설치 빨라요 별점
            formData.append('content', reviewText);
            formData.append('title', ''); // 인터넷 리뷰는 제목 없음
            
            // application_id 추가 (각 신청별로 별도 리뷰 작성)
            if (window.currentReviewApplicationId) {
                formData.append('application_id', window.currentReviewApplicationId);
            }
            
            // 수정 모드인 경우 review_id 추가
            if (window.isEditMode && window.currentReviewId) {
                formData.append('review_id', window.currentReviewId);
            }
            
            fetch((window.API_PATH || (window.BASE_PATH || '') + '/api') + '/submit-review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status, response.statusText);
                console.log('Response headers:', response.headers);
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('HTTP Error Response:', text);
                        throw new Error(`HTTP ${response.status}: ${text.substring(0, 200)}`);
                    });
                }
                return response.text().then(text => {
                    console.log('Raw response text:', text);
                    try {
                        const data = JSON.parse(text);
                        return data;
                    } catch (e) {
                        console.error('JSON 파싱 실패:', e);
                        console.error('Response text:', text);
                        throw new Error('서버 응답을 파싱할 수 없습니다: ' + text.substring(0, 200));
                    }
                });
            })
            .then(data => {
                console.log('Response data:', JSON.stringify(data, null, 2));
                if (data.success) {
                    // 성공 모달 표시
                    const successMessage = window.isEditMode ? '리뷰가 수정되었습니다.' : '리뷰가 작성되었습니다.';
                    if (typeof showMessageModal === 'function') {
                        showMessageModal(successMessage, 'success', function() {
                            closeReviewModal();
                            // 페이지 새로고침 (리뷰 작성/수정 버튼 상태 업데이트를 위해)
                            location.reload();
                        });
                    } else if (typeof showAlert === 'function') {
                        showAlert(successMessage, '알림').then(() => {
                            closeReviewModal();
                            location.reload();
                        });
                    } else {
                        alert(successMessage);
                        closeReviewModal();
                        location.reload();
                    }
                } else {
                    // 에러 모달 표시
                    const errorMessage = data.message || (window.isEditMode ? '리뷰 수정에 실패했습니다.' : '리뷰 작성에 실패했습니다.');
                    console.error('리뷰 제출 실패:', JSON.stringify(data, null, 2));
                    console.error('Error message:', data.message);
                    console.error('Error details:', data.error);
                    if (typeof showMessageModal === 'function') {
                        showMessageModal(errorMessage, 'error');
                    } else if (typeof showAlert === 'function') {
                        showAlert(errorMessage, '오류');
                    } else {
                        alert(errorMessage);
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnContent;
                }
            })
            .catch(error => {
                console.error('리뷰 제출 중 오류:', error);
                console.error('Error stack:', error.stack);
                const errorMessage = window.isEditMode ? '리뷰 수정 중 오류가 발생했습니다.' : '리뷰 작성 중 오류가 발생했습니다.';
                if (typeof showMessageModal === 'function') {
                    showMessageModal(errorMessage, 'error');
                } else if (typeof showAlert === 'function') {
                    showAlert(errorMessage, '오류');
                } else {
                    alert(errorMessage);
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
            });
        });
    }
    
    // 메시지 모달 표시 함수
    function showMessageModal(message, type, callback) {
        type = type || 'info'; // success, error, warning, info
        
        // 기존 모달이 있으면 제거
        const existingModal = document.getElementById('reviewMessageModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // 모달 생성
        const modal = document.createElement('div');
        modal.id = 'reviewMessageModal';
        modal.className = 'review-message-modal';
        
        // 아이콘 및 색상 설정
        let icon = '';
        let bgColor = '';
        let iconBg = '';
        
        switch(type) {
            case 'success':
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                bgColor = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                iconBg = '#10b981';
                break;
            case 'error':
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                bgColor = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
                iconBg = '#ef4444';
                break;
            case 'warning':
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                bgColor = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
                iconBg = '#f59e0b';
                break;
            default:
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 16H12V12H11M12 8H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                bgColor = 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)';
                iconBg = '#6366f1';
        }
        
        modal.innerHTML = `
            <div class="review-message-modal-overlay"></div>
            <div class="review-message-modal-content">
                <div class="review-message-modal-icon" style="background: ${iconBg};">
                    ${icon}
                </div>
                <div class="review-message-modal-body">
                    <h3 class="review-message-modal-title">${type === 'success' ? '작성 완료' : type === 'error' ? '오류 발생' : type === 'warning' ? '알림' : '알림'}</h3>
                    <p class="review-message-modal-text">${message}</p>
                </div>
                <div class="review-message-modal-footer">
                    <button class="review-message-modal-btn" style="background: ${bgColor};">
                        확인
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // 애니메이션
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
        
        // 닫기 이벤트
        const closeBtn = modal.querySelector('.review-message-modal-btn');
        const overlay = modal.querySelector('.review-message-modal-overlay');
        
        function closeMessageModal() {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.remove();
                if (callback && typeof callback === 'function') {
                    callback();
                }
            }, 300);
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', closeMessageModal);
        }
        
        if (overlay) {
            overlay.addEventListener('click', closeMessageModal);
        }
        
        // ESC 키로 닫기
        const escHandler = function(e) {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                closeMessageModal();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    }
    
    // 인터넷 리뷰 삭제 버튼 클릭 이벤트
    const deleteReviewBtn = document.getElementById('internetReviewDeleteBtn');
    const deleteModal = document.getElementById('internetReviewDeleteModal');
    
    // 삭제 모달 열기 함수
    function openDeleteModal(reviewId) {
        if (!deleteModal) {
            showMessageModal('삭제 모달을 찾을 수 없습니다.', 'error');
            return;
        }
        
        if (!reviewId) {
            showMessageModal('리뷰 정보를 찾을 수 없습니다.', 'error');
            return;
        }
        
        // 모달에 리뷰 ID 저장
        deleteModal.setAttribute('data-review-id', reviewId);
        
        // 스크롤 위치 저장
        const scrollY = window.scrollY;
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollY}px`;
        document.body.style.width = '100%';
        document.body.style.overflow = 'hidden';
        
        // 모달 표시
        deleteModal.style.display = 'flex';
        setTimeout(() => {
            deleteModal.classList.add('show');
        }, 10);
    }
    
    // 삭제 모달 닫기 함수
    function closeDeleteModal() {
        if (!deleteModal) return;
        
        deleteModal.classList.remove('show');
        setTimeout(() => {
            deleteModal.style.display = 'none';
            
            // 스크롤 위치 복원
            const scrollY = document.body.style.top;
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.body.style.overflow = '';
            if (scrollY) {
                window.scrollTo(0, parseInt(scrollY || '0') * -1);
            }
        }, 300);
    }
    
    // 삭제 버튼 클릭 이벤트
    if (deleteReviewBtn) {
        deleteReviewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const reviewId = this.getAttribute('data-review-id') || window.currentReviewId;
            openDeleteModal(reviewId);
        });
    }
    
    // 삭제 모달 이벤트 리스너
    if (deleteModal) {
        const deleteModalClose = deleteModal.querySelector('.internet-review-delete-modal-close');
        const deleteModalCancel = deleteModal.querySelector('.internet-review-delete-btn-cancel');
        const deleteModalConfirm = deleteModal.querySelector('.internet-review-delete-btn-confirm');
        const deleteModalOverlay = deleteModal.querySelector('.internet-review-delete-modal-overlay');
        
        // 닫기 버튼
        if (deleteModalClose) {
            deleteModalClose.addEventListener('click', closeDeleteModal);
        }
        
        // 취소 버튼
        if (deleteModalCancel) {
            deleteModalCancel.addEventListener('click', closeDeleteModal);
        }
        
        // 오버레이 클릭
        if (deleteModalOverlay) {
            deleteModalOverlay.addEventListener('click', closeDeleteModal);
        }
        
        // 확인 버튼 (삭제 실행)
        if (deleteModalConfirm) {
            deleteModalConfirm.addEventListener('click', function() {
                const reviewId = deleteModal.getAttribute('data-review-id');
                
                if (!reviewId) {
                    showMessageModal('리뷰 정보를 찾을 수 없습니다.', 'error');
                    return;
                }
                
                // 삭제 버튼 비활성화
                this.disabled = true;
                const originalText = this.textContent;
                this.textContent = '삭제 중...';
                
                const formData = new FormData();
                formData.append('review_id', reviewId);
                formData.append('product_type', 'internet');
                
                fetch((window.API_PATH || (window.BASE_PATH || '') + '/api') + '/delete-review.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeDeleteModal();
                        showMessageModal('리뷰가 삭제되었습니다.', 'success', function() {
                            closeReviewModal();
                            location.reload();
                        });
                    } else {
                        showMessageModal(data.message || '리뷰 삭제에 실패했습니다.', 'error');
                        this.disabled = false;
                        this.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessageModal('리뷰 삭제 중 오류가 발생했습니다.', 'error');
                    this.disabled = false;
                    this.textContent = originalText;
                });
            });
        }
        
        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && deleteModal.style.display === 'flex') {
                closeDeleteModal();
            }
        });
    }
});
</script>

<style>
/* 메시지 모달 스타일 */
.review-message-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10002;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.3s;
    padding: 20px;
}

.review-message-modal.show {
    opacity: 1;
    visibility: visible;
}

.review-message-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 10002;
}

.review-message-modal-content {
    position: relative;
    background: #ffffff;
    border-radius: 20px;
    width: 100%;
    max-width: 400px;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    transform: scale(0.9) translateY(20px);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    z-index: 10003;
}

.review-message-modal.show .review-message-modal-content {
    transform: scale(1) translateY(0);
}

.review-message-modal-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 32px auto 24px;
    color: white;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.review-message-modal-body {
    padding: 0 32px 24px;
    text-align: center;
}

.review-message-modal-title {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 12px 0;
    letter-spacing: -0.02em;
}

.review-message-modal-text {
    font-size: 16px;
    color: #64748b;
    line-height: 1.6;
    margin: 0;
}

.review-message-modal-footer {
    width: 100%;
    padding: 0 32px 32px;
}

.review-message-modal-btn {
    width: 100%;
    padding: 16px 24px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    color: #ffffff;
    border: none;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.review-message-modal-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.review-message-modal-btn:active {
    transform: translateY(0);
}

@media (max-width: 640px) {
    .review-message-modal {
        padding: 0;
    }
    
    .review-message-modal-content {
        max-width: 100%;
        border-radius: 20px 20px 0 0;
        margin-top: auto;
    }
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
}

document.addEventListener('DOMContentLoaded', function() {
    // 초기 페이지 로드 시 이벤트 바인딩
    initApplicationCardClickEvents();
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

