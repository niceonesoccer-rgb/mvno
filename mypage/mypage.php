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
$user_name = $currentUser['name'] ?? '회원';

// 포인트 설정 로드
require_once '../includes/data/point-settings.php';
$user_point = getUserPoint($user_id);
$current_balance = $user_point['balance'] ?? 0;

// 찜한 개수 가져오기
require_once '../includes/data/db-config.php';
$pdo = getDBConnection();
$mno_sim_count = 0;
$mvno_count = 0;
$mno_count = 0;

if ($pdo) {
    try {
        // 찜한 통신사단독유심 개수 (활성화된 상품만)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM product_favorites pf
            INNER JOIN products p ON pf.product_id = p.id
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
            WHERE pf.user_id = :user_id 
            AND pf.product_type = 'mno-sim' 
            AND p.status = 'active'
        ");
        $stmt->execute([':user_id' => (string)$user_id]);
        $mno_sim_count = intval($stmt->fetch(PDO::FETCH_ASSOC)['count']);
        
        // 찜한 알뜰폰 개수 (활성화된 상품만)
        // 알뜰폰은 products 테이블에 직접 저장되므로 간단한 조인
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM product_favorites pf
            INNER JOIN products p ON pf.product_id = p.id
            WHERE pf.user_id = :user_id 
            AND pf.product_type = 'mvno' 
            AND p.status = 'active'
        ");
        $stmt->execute([':user_id' => (string)$user_id]);
        $mvno_count = intval($stmt->fetch(PDO::FETCH_ASSOC)['count']);
        
        // 찜한 통신사폰 개수 (활성화된 상품만)
        // 통신사폰도 products 테이블에 직접 저장되므로 간단한 조인
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM product_favorites pf
            INNER JOIN products p ON pf.product_id = p.id
            WHERE pf.user_id = :user_id 
            AND pf.product_type = 'mno' 
            AND p.status = 'active'
        ");
        $stmt->execute([':user_id' => (string)$user_id]);
        $mno_count = intval($stmt->fetch(PDO::FETCH_ASSOC)['count']);
    } catch (PDOException $e) {
        error_log("Error fetching favorite counts: " . $e->getMessage());
    }
}

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div style="width: 460px; margin: 0 auto; padding: 20px;" class="mypage-container">
        <!-- 사용자 인사말 헤더 -->
        <div style="margin-bottom: 24px; padding: 20px 0;">
            <h2 style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo htmlspecialchars($user_name); ?>님 안녕하세요</h2>
        </div>

        <!-- 포인트 카드 -->
        <div style="margin-bottom: 24px; padding: 20px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border-radius: 12px; color: white;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="flex: 1;">
                    <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">보유 포인트</div>
                    <div style="font-size: 32px; font-weight: 700;">
                        <?php echo number_format($current_balance); ?>원
                    </div>
                </div>
                <a href="<?php echo getAssetPath('/mypage/point-history.php'); ?>" style="display: inline-block; padding: 8px 16px; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 8px; color: white; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s; white-space: nowrap;" onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'; this.style.borderColor='rgba(255, 255, 255, 0.5)';" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.borderColor='rgba(255, 255, 255, 0.3)';">내역보기</a>
            </div>
        </div>

        <!-- 하단 메뉴 리스트 -->
        <div style="margin-bottom: 32px;">
            <ul style="list-style: none; padding: 0; margin: 0;">
                <!-- 찜한 통신사단독유심 내역 -->
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="<?php echo getAssetPath('/mypage/wishlist.php?type=mno-sim'); ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="#ef4444"/>
                            </svg>
                            <span style="font-size: 16px;">찜한 통신사단독유심 (<?php echo number_format($mno_sim_count); ?>개)</span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width: 16px; height: 16px;">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>

                <!-- 찜한 알뜰폰 내역 -->
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="<?php echo getAssetPath('/mypage/wishlist.php?type=mvno'); ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="#ef4444"/>
                            </svg>
                            <span style="font-size: 16px;">찜한 알뜰폰 (<?php echo number_format($mvno_count); ?>개)</span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width: 16px; height: 16px;">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>

                <!-- 찜한 통신사폰 내역 -->
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="<?php echo getAssetPath('/mypage/wishlist.php?type=mno'); ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="#ef4444"/>
                            </svg>
                            <span style="font-size: 16px;">찜한 통신사폰 (<?php echo number_format($mno_count); ?>개)</span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width: 16px; height: 16px;">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>

                <!-- 포인트 내역 -->
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="<?php echo getAssetPath('/mypage/point-history.php'); ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span style="font-size: 16px;">포인트 내역</span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width: 16px; height: 16px;">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>

                <!-- 통신사단독유심 주문 내역 -->
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="<?php echo getAssetPath('/mypage/mno-sim-order.php'); ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M9 11l3 3L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span style="font-size: 16px;">통신사단독유심 주문 내역</span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width: 16px; height: 16px;">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>

                <!-- 알뜰폰 주문 내역 -->
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="<?php echo getAssetPath('/mypage/mvno-order.php'); ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M9 11l3 3L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span style="font-size: 16px;">알뜰폰 주문 내역</span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width: 16px; height: 16px;">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>

                <!-- 통신사폰 주문 내역 -->
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="<?php echo getAssetPath('/mypage/mno-order.php'); ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M9 11l3 3L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span style="font-size: 16px;">통신사폰 주문 내역</span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width: 16px; height: 16px;">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>

                <!-- 인터넷 주문 내역 -->
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="<?php echo getAssetPath('/mypage/internet-order.php'); ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M9 11l3 3L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span style="font-size: 16px;">인터넷 주문 내역</span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width: 16px; height: 16px;">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>

                <!-- 계정 관리 -->
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="<?php echo getAssetPath('/mypage/account-management.php'); ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span style="font-size: 16px;">계정 관리</span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width: 16px; height: 16px;">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>

                <!-- 알림 설정 -->
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="<?php echo getAssetPath('/mypage/alarm-setting.php'); ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span style="font-size: 16px;">알림 설정</span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width: 16px; height: 16px;">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>

                <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 16px 0;">

                <!-- 공지 사항 -->
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="<?php echo getAssetPath('/notice/notice.php'); ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span style="font-size: 16px;">공지 사항</span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width: 16px; height: 16px;">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>

                <!-- 1:1 문의 -->
                <li style="border-bottom: 1px solid #e5e7eb;">
                    <a href="<?php echo getAssetPath('/qna/qna.php'); ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                                <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span style="font-size: 16px;">1:1 문의</span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width: 16px; height: 16px;">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</main>

<script src="../assets/js/plan-accordion.js" defer></script>
<script src="../assets/js/favorite-heart.js" defer></script>
<script src="../assets/js/point-balance-update.js" defer></script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>
