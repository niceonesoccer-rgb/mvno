<?php
/**
 * 로그인 모달 컴포넌트
 */
require_once __DIR__ . '/../../includes/data/auth-functions.php';

// 관리자/판매자 페이지 확인
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$isAdminPage = strpos($currentPath, '/admin/') !== false;
$isSellerPage = strpos($currentPath, '/seller/') !== false;
$hideSnsLogin = $isAdminPage || $isSellerPage;

$error = $_GET['error'] ?? '';
$errorMessages = [
    'invalid_request' => '잘못된 요청입니다.',
    'invalid_state' => '보안 검증에 실패했습니다.',
    'token_failed' => '인증 토큰을 받는데 실패했습니다.',
    'user_info_failed' => '사용자 정보를 가져오는데 실패했습니다.',
    'invalid_user' => '유효하지 않은 사용자입니다.',
    'invalid_provider' => '지원하지 않는 로그인 방식입니다.'
];
$errorMessage = $errorMessages[$error] ?? '';
$isRegisterMode = isset($_GET['register']) && $_GET['register'] === 'true';
?>
<style>
.login-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s ease-out;
}

.login-modal.login-modal-active {
    display: flex;
}

.login-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.login-modal-content {
    position: relative;
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 420px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    animation: slideUp 0.3s ease-out;
    z-index: 10001;
}

.login-modal-header {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 16px 20px 0;
    position: sticky;
    top: 0;
    background: white;
    z-index: 1;
}

.login-modal-header:has(.login-modal-title) {
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.login-modal-title {
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    margin: 0;
    letter-spacing: -0.02em;
}

.login-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: #9ca3af;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s;
}

.login-modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.login-modal-body {
    padding: 40px 24px 28px;
    max-width: 420px;
    margin: 0 auto;
}

.login-benefits-section {
    margin-bottom: 0;
}

.login-benefits-title {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    text-align: center;
    margin-bottom: 28px;
    line-height: 1.4;
    letter-spacing: -0.02em;
}

.login-benefits-list {
    display: flex;
    flex-direction: column;
    gap: 24px;
    margin-bottom: 36px;
}

.login-benefit-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.login-benefit-icon {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: transform 0.2s;
}

.login-benefit-icon:hover {
    transform: scale(1.05);
}

.login-benefit-icon.shield {
    background: #d1fae5;
    color: #10b981;
}

.login-benefit-icon.search {
    background: #fef3c7;
    color: #f59e0b;
}

.login-benefit-icon.phone {
    background: #dbeafe;
    color: #3b82f6;
}

.login-benefit-text {
    flex: 1;
    font-size: 15px;
    color: #374151;
    line-height: 1.6;
    padding-top: 2px;
    font-weight: 400;
}

.login-primary-button {
    width: 100%;
    padding: 16px 20px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    margin-bottom: 16px;
}

.login-primary-button img {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
}

.login-primary-button.kakao {
    background: #fee500;
    color: #000000;
}

.login-primary-button.kakao:hover {
    background: #fdd835;
}

.login-primary-button.naver {
    background: #03A94D;
    color: white;
}

.login-primary-button.naver:hover {
    background: #028a3f;
}

.login-primary-button.google {
    background: white;
    color: #1f2937;
    border: 1px solid #e5e7eb;
}

.login-primary-button.google:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.login-browse-link {
    text-align: center;
    font-size: 14px;
    color: #6b7280;
    cursor: pointer;
    text-decoration: none;
    display: block;
    margin-top: 8px;
    transition: color 0.2s;
}

.login-browse-link:hover {
    color: #111827;
    text-decoration: underline;
}

.login-error-message {
    padding: 12px 16px;
    background: #fee2e2;
    color: #991b1b;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 14px;
}

.login-sns-section {
    margin-bottom: 0;
}

.login-sns-title {
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 20px;
    text-align: center;
    letter-spacing: -0.01em;
}

.login-sns-buttons {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.login-sns-buttons .login-primary-button {
    margin-bottom: 0;
}

.login-sns-button-svg {
    display: block;
    width: 100%;
    height: auto;
    object-fit: contain;
}

.login-primary-button-svg {
    display: block;
    width: 100%;
    height: auto;
    object-fit: contain;
}

.login-sns-button-img {
    display: block;
    width: 100%;
    height: auto;
    object-fit: contain;
    transition: all 0.2s ease;
    border-radius: 12px;
}

.login-sns-button-img:hover {
    opacity: 0.85;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.login-sns-button-img.google {
    border: 1px solid #e5e7eb;
    box-sizing: border-box;
}

.login-sns-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: white;
    font-size: 15px;
    font-weight: 500;
    color: #1f2937;
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
}

.login-sns-button img {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
}

.login-sns-button:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.login-sns-button.naver {
    background: #03A94D;
    color: white;
    border-color: #03A94D;
}

.login-sns-button.naver:hover {
    background: #028a3f;
}

.login-sns-button.kakao {
    background: #fee500;
    color: #000000;
    border-color: #fee500;
    border-radius: 12px;
}

.login-sns-button.kakao:hover {
    background: #fdd835;
}

.login-sns-button.google {
    background: white;
    color: #1f2937;
}

.login-register-link {
    text-align: center;
    margin-top: 24px;
    font-size: 14px;
    color: #6b7280;
}

.login-register-link a {
    color: #6366f1;
    text-decoration: none;
    font-weight: 500;
}

.login-register-link a:hover {
    text-decoration: underline;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@media (max-width: 640px) {
    .login-modal-content {
        width: 100%;
        max-width: 100%;
        max-height: 100vh;
        height: 100vh;
        margin: 0;
        border-radius: 0;
        box-shadow: none;
    }
    
    .login-modal-header {
        padding: 16px 20px;
    }
    
    .login-modal-body {
        padding: 32px 20px 24px;
        max-width: 100%;
    }
    
    .login-benefits-title {
        font-size: 18px;
        margin-bottom: 20px;
    }
    
    .login-sns-title {
        font-size: 16px;
        margin-bottom: 16px;
    }
    
    .login-benefits-list {
        gap: 20px;
        margin-bottom: 28px;
    }
    
    .login-benefit-icon {
        width: 40px;
        height: 40px;
    }
    
    .login-benefit-text {
        font-size: 14px;
    }
    
    .login-sns-buttons {
        gap: 12px;
    }
}
</style>

<div id="loginModal" class="login-modal">
    <div class="login-modal-overlay"></div>
    <div class="login-modal-content">
        <div class="login-modal-header">
            <?php if (!$isRegisterMode): ?>
                <h3 class="login-modal-title" id="loginModalTitle"><?php echo $isRegisterMode ? '회원가입' : '로그인'; ?></h3>
            <?php endif; ?>
            <button class="login-modal-close" id="loginModalClose">&times;</button>
        </div>
        <div class="login-modal-body">
            <?php if ($errorMessage): ?>
                <div class="login-error-message">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($hideSnsLogin): ?>
                <!-- 관리자/판매자 페이지: SNS 로그인 숨김 -->
                <div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                    <p style="font-size: 16px; margin-bottom: 8px;">관리자 및 판매자는</p>
                    <p style="font-size: 14px;">아이디와 비밀번호로 로그인해주세요.</p>
                </div>
            <?php elseif ($isRegisterMode): ?>
                <!-- 회원가입 모드: 혜택 소개 -->
                <div class="login-benefits-section">
                    <h3 class="login-benefits-title">SNS로 로그인</h3>
                    <p style="text-align: center; font-size: 15px; color: #6b7280; margin-bottom: 32px; line-height: 1.5;">회원가입으로 더 많은 할인 혜택을 누려보세요~</p>
                    
                    <div class="login-benefits-list">
                        <div class="login-benefit-item">
                            <div class="login-benefit-icon shield">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    <path d="M9 12l2 2 4-4"/>
                                </svg>
                            </div>
                            <div class="login-benefit-text">
                                복잡한 요금제 가입 절차없이 모요에서 쉽게 개통해요
                            </div>
                        </div>
                        
                        <div class="login-benefit-item">
                            <div class="login-benefit-icon search">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"/>
                                    <path d="m21 21-4.35-4.35"/>
                                    <path d="M8 11h6M11 8v6"/>
                                </svg>
                            </div>
                            <div class="login-benefit-text">
                                34개 통신사의 모든 요금제를 한 번에 검색하고 비교해요
                            </div>
                        </div>
                        
                        <div class="login-benefit-item">
                            <div class="login-benefit-icon phone">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                    <path d="M12 18h.01"/>
                                    <circle cx="12" cy="7" r="1"/>
                                </svg>
                            </div>
                            <div class="login-benefit-text">
                                지금 쓰고 있는 사용량에 딱 맞는 맞춤 요금제를 추천드려요
                            </div>
                        </div>
                    </div>
                    
                    <div class="login-sns-buttons" style="margin-bottom: 16px;">
                        <button type="button" class="login-primary-button kakao" onclick="snsLoginModal('kakao')">
                            <img src="/MVNO/assets/images/logo/kakao-talk.svg" alt="카카오">
                            카카오로 시작하기
                        </button>
                        <button type="button" class="login-primary-button naver" onclick="snsLoginModal('naver')">
                            <img src="/MVNO/assets/images/logo/naver-n.svg" alt="네이버">
                            네이버로 시작하기
                        </button>
                        <button type="button" class="login-primary-button google" onclick="snsLoginModal('google')">
                            <img src="/MVNO/assets/images/logo/google-g.svg" alt="구글">
                            구글로 시작하기
                        </button>
                    </div>
                    
                    <a href="#" class="login-browse-link" onclick="closeLoginModal(); return false;">일단 둘러볼게요</a>
                </div>
            <?php else: ?>
                <!-- 로그인 모드: 기존 SNS 로그인 -->
                <div class="login-sns-section">
                    <div class="login-sns-title">SNS로 로그인</div>
                    <div class="login-sns-buttons">
                        <img src="/MVNO/assets/images/logo/button-kakao-login.png" alt="카카오톡 로그인" class="login-sns-button-img" onclick="snsLoginModal('kakao')" style="cursor: pointer; width: 100%; height: auto; border-radius: 12px;">
                        <img src="/MVNO/assets/images/logo/button-naver-login.png" alt="네이버로 로그인" class="login-sns-button-img" onclick="snsLoginModal('naver')" style="cursor: pointer; width: 100%; height: auto; border-radius: 12px;">
                        <img src="/MVNO/assets/images/logo/button-google-login.png" alt="구글 로그인" class="login-sns-button-img google" onclick="snsLoginModal('google')" style="cursor: pointer; width: 100%; height: auto; border-radius: 12px;">
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// 로그인 모달 열기/닫기
function openLoginModal(isRegister = false) {
    const modal = document.getElementById('loginModal');
    const title = document.getElementById('loginModalTitle');
    
    // 모달 내용을 동적으로 변경하기 위해 페이지 리로드 대신 PHP 변수 사용
    // 실제로는 서버 사이드에서 처리되므로 여기서는 모달만 열기
    if (isRegister) {
        title.textContent = '회원가입';
    } else {
        title.textContent = '로그인';
    }
    
    modal.classList.add('login-modal-active');
    // 배경 고정
    const scrollY = window.scrollY;
    document.body.style.overflow = 'hidden';
    document.body.style.position = 'fixed';
    document.body.style.top = `-${scrollY}px`;
    document.body.style.width = '100%';
    document.body.style.left = '0';
    document.body.style.right = '0';
    document.documentElement.style.overflow = 'hidden';
    window.scrollPosition = scrollY;
}

function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    modal.classList.remove('login-modal-active');
    
    // 배경 고정 해제
    const scrollY = window.scrollPosition || parseInt(document.body.style.top || '0') * -1;
    document.body.style.overflow = '';
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.width = '';
    document.body.style.left = '';
    document.body.style.right = '';
    document.documentElement.style.overflow = '';
    if (scrollY) {
        window.scrollTo(0, scrollY);
    }
}

// SNS 로그인
function snsLoginModal(provider) {
    fetch(`/MVNO/api/sns-login.php?action=${provider}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // redirect가 있으면 바로 이동 (테스트용 자동 로그인)
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.auth_url) {
                    // auth_url이 있으면 OAuth 인증 페이지로 이동
                    window.location.href = data.auth_url;
                }
            } else {
                alert(data.message || '로그인에 실패했습니다.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('로그인 중 오류가 발생했습니다.');
        });
}

// 모달 초기화
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('loginModal');
    const closeBtn = document.getElementById('loginModalClose');
    const overlay = modal.querySelector('.login-modal-overlay');
    
    // 닫기 버튼
    if (closeBtn) {
        closeBtn.addEventListener('click', closeLoginModal);
    }
    
    // 오버레이 클릭 시 닫기
    if (overlay) {
        overlay.addEventListener('click', closeLoginModal);
    }
    
    // ESC 키로 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('login-modal-active')) {
            closeLoginModal();
        }
    });
    
});

// 전역 함수로 등록
window.openLoginModal = openLoginModal;
window.closeLoginModal = closeLoginModal;
</script>
