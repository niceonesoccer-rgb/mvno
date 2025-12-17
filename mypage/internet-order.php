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
require_once '../includes/data/product-functions.php';
require_once '../includes/data/db-config.php';

// DB에서 실제 신청 내역 가져오기
$internets = getUserInternetApplications($user_id);
error_log("internet-order.php: Received " . count($internets) . " internets from getUserInternetApplications");
error_log("internet-order.php: user_id: " . $user_id);
if (count($internets) > 0) {
    error_log("internet-order.php: First internet keys: " . implode(', ', array_keys($internets[0])));
    error_log("internet-order.php: First internet data: " . json_encode($internets[0], JSON_UNESCAPED_UNICODE));
} else {
    error_log("internet-order.php: WARNING - getUserInternetApplications returned empty array!");
}

// 디버깅: 데이터 확인
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
if ($debug_mode) {
    $pdo = getDBConnection();
    if ($pdo) {
        // 1. user_id 확인
        $debug_info = [
            'user_id' => $user_id,
            'current_user' => $currentUser,
            'internets_count' => count($internets),
            'internets_data' => $internets
        ];
        
        // 2. 데이터베이스에서 직접 확인
        try {
            // application_customers에서 user_id로 검색
            $stmt1 = $pdo->prepare("SELECT COUNT(*) as cnt FROM application_customers WHERE user_id = ?");
            $stmt1->execute([$user_id]);
            $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
            $debug_info['application_customers_count'] = $result1['cnt'] ?? 0;
            
            // product_applications에서 internet 타입 검색
            $stmt2 = $pdo->prepare("
                SELECT COUNT(*) as cnt 
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                WHERE c.user_id = ? AND a.product_type = 'internet'
            ");
            $stmt2->execute([$user_id]);
            $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
            $debug_info['internet_applications_count'] = $result2['cnt'] ?? 0;
            
            // 전체 신청 내역 확인 (user_id NULL 포함)
            $stmt3 = $pdo->prepare("
                SELECT a.id, a.product_id, a.product_type, a.application_status, a.created_at, c.user_id, c.name, c.phone
                FROM product_applications a
                LEFT JOIN application_customers c ON a.id = c.application_id
                WHERE a.product_type = 'internet'
                ORDER BY a.created_at DESC
                LIMIT 10
            ");
            $stmt3->execute();
            $debug_info['all_internet_applications'] = $stmt3->fetchAll(PDO::FETCH_ASSOC);
            
            // 실제 쿼리 실행 테스트
            $stmt4 = $pdo->prepare("
                SELECT 
                    a.id as application_id,
                    a.product_id,
                    a.application_status,
                    a.created_at as order_date,
                    c.name,
                    c.phone,
                    c.email,
                    c.user_id,
                    c.additional_info,
                    p.status as product_status
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                INNER JOIN products p ON a.product_id = p.id
                WHERE c.user_id = ? 
                AND a.product_type = 'internet'
                ORDER BY a.created_at DESC
            ");
            $stmt4->execute([$user_id]);
            $debug_info['direct_query_result'] = $stmt4->fetchAll(PDO::FETCH_ASSOC);
            $debug_info['direct_query_count'] = count($debug_info['direct_query_result']);
            
        } catch (PDOException $e) {
            $debug_info['error'] = $e->getMessage();
        }
    }
}

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
                        <h2 style="font-size: 24px; font-weight: bold; margin: 0;">신청한 인터넷</h2>
                    </div>
                    <p style="font-size: 14px; color: #6b7280; margin: 0; margin-left: 36px;">카드를 클릭하면 신청 정보를 확인할 수 있습니다.</p>
                </div>

                <!-- 디버깅 정보 (debug=1 파라미터가 있을 때만 표시) -->
                <?php if ($debug_mode && isset($debug_info)): ?>
                    <div style="padding: 20px; background: #f3f4f6; border-radius: 8px; margin-bottom: 20px; font-family: monospace; font-size: 12px;">
                        <h3 style="margin-top: 0; color: #6366f1;">디버깅 정보</h3>
                        <pre style="background: white; padding: 15px; border-radius: 4px; overflow-x: auto;"><?php echo htmlspecialchars(json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                    </div>
                <?php endif; ?>
                
                <!-- 신청한 인터넷 목록 -->
                <div style="margin-bottom: 32px;">
                    <?php if (empty($internets)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: #6b7280;">
                            신청한 인터넷이 없습니다.
                            <?php if (!$debug_mode): ?>
                                <br><br>
                                <a href="?debug=1" style="color: #6366f1; text-decoration: underline; font-size: 12px;">디버깅 정보 보기</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($internets as $index => $internet): ?>
                                <div class="internet-item application-card" 
                                     data-index="<?php echo $index; ?>" 
                                     data-internet-id="<?php echo $internet['id']; ?>" 
                                     data-application-id="<?php echo htmlspecialchars($internet['application_id'] ?? ''); ?>"
                                     style="<?php echo $index >= 10 ? 'display: none;' : ''; ?> padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"
                                     onmouseover="this.style.borderColor='#6366f1'; this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.15)'"
                                     onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'">
                                    
                                    <!-- 상단: 통신사 로고 및 정보 -->
                                    <div style="margin-bottom: 16px;">
                                        <?php
                                        // 통신사 로고 경로 설정
                                        $provider = $internet['provider'] ?? '';
                                        $logoUrl = '';
                                        if (stripos($provider, 'SKT') !== false || stripos($provider, 'SK') !== false) {
                                            $logoUrl = 'https://assets-legacy.moyoplan.com/internets/assets/broadband.svg';
                                        } elseif (stripos($provider, 'KT') !== false) {
                                            $logoUrl = 'https://assets-legacy.moyoplan.com/internets/assets/kt.svg';
                                        } elseif (stripos($provider, 'LG') !== false || stripos($provider, 'LGU') !== false) {
                                            $logoUrl = 'https://assets-legacy.moyoplan.com/internets/assets/lgu.svg';
                                        }
                                        ?>
                                        <?php if ($logoUrl): ?>
                                            <div style="margin-bottom: 12px;">
                                                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($provider); ?>" style="height: 32px; object-fit: contain;">
                                            </div>
                                        <?php else: ?>
                                            <div style="font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 12px;">
                                                <?php echo htmlspecialchars($provider ?: '인터넷'); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div style="font-size: 16px; color: #374151; margin-bottom: 8px;">
                                            <?php echo htmlspecialchars($internet['speed'] ?? ''); ?> <?php echo htmlspecialchars($internet['plan_name'] ?? ''); ?>
                                        </div>
                                        
                                        <div style="font-size: 16px; color: #1f2937; font-weight: 600;">
                                            <?php echo htmlspecialchars($internet['price'] ?? ''); ?>
                                        </div>
                                    </div>
                                    
                                    <!-- 하단: 주문번호 및 진행상황 -->
                                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
                                        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
                                            <?php if (!empty($internet['order_number'])): ?>
                                                <span style="color: #6b7280;">주문번호</span>
                                                <span style="color: #374151; font-weight: 600;"><?php echo htmlspecialchars($internet['order_number']); ?></span>
                                            <?php elseif (!empty($internet['application_id'])): ?>
                                                <span style="color: #6b7280;">신청번호</span>
                                                <span style="color: #374151; font-weight: 600;">#<?php echo htmlspecialchars($internet['application_id']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                        <?php 
                                        // 상태별 배지 색상 설정 (함수에서 반환하는 application_status 사용)
                                        $statusBg = '#eef2ff'; // 기본 파란색
                                        $statusColor = '#6366f1';
                                        
                                        // 상태별 배지 색상 설정 (internet.php의 옵션과 동일한 상태값 사용)
                                        $appStatus = $internet['application_status'] ?? '';
                                        if (!empty($appStatus)) {
                                            switch ($appStatus) {
                                                case 'received':
                                                case 'pending':
                                                    $statusBg = '#fef3c7'; // 노란색
                                                    $statusColor = '#92400e';
                                                    break;
                                                case 'processing':
                                                case 'activating':
                                                    $statusBg = '#e0e7ff'; // 보라색
                                                    $statusColor = '#6366f1';
                                                    break;
                                                case 'installation_completed':
                                                case 'activation_completed':
                                                case 'completed':
                                                    $statusBg = '#d1fae5'; // 초록색
                                                    $statusColor = '#065f46';
                                                    break;
                                                case 'on_hold':
                                                    $statusBg = '#fef3c7'; // 노란색
                                                    $statusColor = '#92400e';
                                                    break;
                                                case 'cancelled':
                                                case 'rejected':
                                                    $statusBg = '#fee2e2'; // 빨간색
                                                    $statusColor = '#991b1b';
                                                    break;
                                                case 'closed':
                                                case 'terminated':
                                                    $statusBg = '#f3f4f6'; // 회색
                                                    $statusColor = '#6b7280';
                                                    break;
                                            }
                                        }
                                        
                                        if (!empty($internet['status'])): ?>
                                            <div style="display: inline-flex; align-items: center; padding: 6px 12px; background: <?php echo $statusBg; ?>; border-radius: 6px;">
                                                <span style="font-size: 13px; color: <?php echo $statusColor; ?>; font-weight: 600;"><?php echo htmlspecialchars($internet['status']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div style="display: inline-flex; align-items: center; padding: 6px 12px; background: #f3f4f6; border-radius: 6px;">
                                                <span style="font-size: 13px; color: #6b7280; font-weight: 600;">상태 없음</span>
                                            </div>
                                        <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 더보기 버튼 -->
                <?php if (count($internets) > 10): ?>
                <div style="margin-top: 32px; margin-bottom: 32px;" id="moreButtonContainer">
                    <button class="plan-review-more-btn" id="moreInternetsBtn" style="width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                        더보기 (<?php 
                        $remaining = count($internets) - 10;
                        echo $remaining > 10 ? 10 : $remaining;
                        ?>개)
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- 신청 상세 정보 모달 -->
<div id="applicationDetailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 10000; overflow-y: auto; padding: 20px;">
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
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = ''; // 배경 스크롤 복원
    }
    
    function displayApplicationDetails(data) {
        if (!modalContent) return;
        
        const customer = data.customer || {};
        const additionalInfo = data.additional_info || {};
        const productSnapshot = additionalInfo.product_snapshot || {};
        
        let html = '<div class="product-modal-body">';
        
        // 통신사 로고 표시
        const registrationPlace = productSnapshot.registration_place || '';
        let logoUrl = '';
        if (registrationPlace.indexOf('SKT') !== -1 || registrationPlace.indexOf('SK') !== -1) {
            logoUrl = 'https://assets-legacy.moyoplan.com/internets/assets/broadband.svg';
        } else if (registrationPlace.indexOf('KT') !== -1) {
            logoUrl = 'https://assets-legacy.moyoplan.com/internets/assets/kt.svg';
        } else if (registrationPlace.indexOf('LG') !== -1 || registrationPlace.indexOf('LGU') !== -1) {
            logoUrl = 'https://assets-legacy.moyoplan.com/internets/assets/lgu.svg';
        }
        
        if (logoUrl) {
            html += `<div style="margin-bottom: 16px;"><img src="${escapeHtml(logoUrl)}" alt="${escapeHtml(registrationPlace)}" style="height: 40px; object-fit: contain;"></div>`;
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
            html += `<tr><th>가입 속도</th><td>${escapeHtml(productSnapshot.speed_option)}</td></tr>`;
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
    
    // 더보기 기능
    const moreBtn = document.getElementById('moreInternetsBtn');
    const internetItems = document.querySelectorAll('.internet-item');
    let visibleCount = 10;
    const totalInternets = internetItems.length;
    const loadCount = 10;

    function updateButtonText() {
        if (!moreBtn) return;
        const remaining = totalInternets - visibleCount;
        if (remaining > 0) {
            const showCount = remaining > loadCount ? loadCount : remaining;
            moreBtn.textContent = `더보기 (${showCount}개)`;
        }
    }

    if (moreBtn && totalInternets > 10) {
        updateButtonText();
        
        moreBtn.addEventListener('click', function() {
            const endCount = Math.min(visibleCount + loadCount, totalInternets);
            for (let i = visibleCount; i < endCount; i++) {
                if (internetItems[i]) {
                    internetItems[i].style.display = 'block';
                }
            }
            
            visibleCount = endCount;
            
            if (visibleCount >= totalInternets) {
                const moreButtonContainer = document.getElementById('moreButtonContainer');
                if (moreButtonContainer) {
                    moreButtonContainer.style.display = 'none';
                }
            } else {
                updateButtonText();
            }
        });
    } else if (moreBtn && totalInternets <= 10) {
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

