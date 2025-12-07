<?php
/**
 * 회원 상세 정보 페이지
 */

// 필요한 함수 파일 먼저 포함
require_once __DIR__ . '/../../includes/data/auth-functions.php';

// POST 요청 처리 (헤더 출력 전에 처리)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 승인 처리
    if (isset($_POST['approve_seller'])) {
        $editUserId = $_POST['user_id'] ?? '';
        if ($editUserId && approveSeller($editUserId)) {
            header('Location: /MVNO/admin/users/member-detail.php?user_id=' . urlencode($editUserId) . '&success=approve');
            exit;
        } else {
            header('Location: /MVNO/admin/users/member-detail.php?user_id=' . urlencode($editUserId) . '&error=approve');
            exit;
        }
    }
    
    // 승인보류 처리
    if (isset($_POST['hold_seller'])) {
        $editUserId = $_POST['user_id'] ?? '';
        if ($editUserId && holdSeller($editUserId)) {
            header('Location: /MVNO/admin/users/member-detail.php?user_id=' . urlencode($editUserId) . '&success=hold');
            exit;
        } else {
            header('Location: /MVNO/admin/users/member-detail.php?user_id=' . urlencode($editUserId) . '&error=hold');
            exit;
        }
    }
    
    // 권한 설정 처리
    if (isset($_POST['save_permissions'])) {
        $editUserId = $_POST['user_id'] ?? '';
        $permissions = $_POST['permissions'] ?? [];
        
        if ($editUserId && setSellerPermissions($editUserId, $permissions)) {
            header('Location: /MVNO/admin/users/member-detail.php?user_id=' . urlencode($editUserId) . '&success=permissions');
            exit;
        } else {
            header('Location: /MVNO/admin/users/member-detail.php?user_id=' . urlencode($editUserId) . '&error=permissions');
            exit;
        }
    }
}

// 헤더 포함 (POST 처리가 완료된 후)
require_once __DIR__ . '/../includes/admin-header.php';

// 관리자 권한 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin()) {
    header('Location: /MVNO/auth/login.php');
    exit;
}

// 사용자 ID 가져오기
$userId = $_GET['user_id'] ?? '';

if (empty($userId)) {
    header('Location: /MVNO/admin/users/member-list.php');
    exit;
}

// 성공/에러 메시지 변수 초기화
$success_message = null;
$error_message = null;

// 사용자 정보 가져오기
$user = getUserById($userId);

if (!$user) {
    header('Location: /MVNO/admin/users/member-list.php');
    exit;
}

// 판매자인지 확인
$isSeller = isset($user['role']) && $user['role'] === 'seller';

// 관리자/부관리자인지 확인
$isAdmin = isset($user['role']) && ($user['role'] === 'admin' || $user['role'] === 'sub_admin');

// 판매자가 아니고 관리자/부관리자가 아닌 경우에만 일반 회원 정보 가져오기
if (!$isSeller && !$isAdmin) {
    // 포인트 정보 가져오기
    $pointsData = [];
    $pointsFile = __DIR__ . '/../../includes/data/user-points.json';
    if (file_exists($pointsFile)) {
        $pointsContent = file_get_contents($pointsFile);
        $pointsData = json_decode($pointsContent, true) ?: [];
    }
    $userPoints = $pointsData[$userId] ?? null;

    // 찜한 요금제 정보 가져오기
    $wishlistData = [];
    $wishlistFile = __DIR__ . '/../../includes/data/user-wishlist.json';
    if (file_exists($wishlistFile)) {
        $wishlistContent = file_get_contents($wishlistFile);
        $wishlistData = json_decode($wishlistContent, true) ?: [];
    }
    $userWishlist = $wishlistData[$userId] ?? null;

    // 판매 종료된 상품 필터링 함수
    require_once __DIR__ . '/../../includes/data/plan-data.php';
    require_once __DIR__ . '/../../includes/data/phone-data.php';

    function filterActiveWishlistItems($wishlistItems, $type) {
        if (empty($wishlistItems)) {
            return [];
        }
        
        $activeItems = [];
        foreach ($wishlistItems as $item) {
            $productId = $item['product_id'] ?? $item['id'] ?? null;
            if (!$productId) {
                continue;
            }
            
            // 상품 존재 여부 확인
            $productExists = false;
            if ($type === 'mvno') {
                $plan = getPlanDetailData($productId);
                if ($plan && (!isset($plan['status']) || $plan['status'] !== 'discontinued')) {
                    $productExists = true;
                }
            } else if ($type === 'mno') {
                $phone = getPhoneDetailData($productId);
                if ($phone && (!isset($phone['status']) || $phone['status'] !== 'discontinued')) {
                    $productExists = true;
                }
            }
            
            // 판매 중인 상품만 추가
            if ($productExists) {
                $activeItems[] = $item;
            }
        }
        
        return $activeItems;
    }

    // 판매 종료된 상품 필터링
    if ($userWishlist) {
        if (!empty($userWishlist['mvno'])) {
            $userWishlist['mvno'] = filterActiveWishlistItems($userWishlist['mvno'], 'mvno');
        }
        if (!empty($userWishlist['mno'])) {
            $userWishlist['mno'] = filterActiveWishlistItems($userWishlist['mno'], 'mno');
        }
    }

    // 주문 내역 정보 가져오기
    $orderData = [];
    $orderFile = __DIR__ . '/../../includes/data/user-orders.json';
    if (file_exists($orderFile)) {
        $orderContent = file_get_contents($orderFile);
        $orderData = json_decode($orderContent, true) ?: [];
    }
    $userOrders = $orderData[$userId] ?? null;

    // 알림 설정 정보 가져오기
    $notificationData = [];
    $notificationFile = __DIR__ . '/../../includes/data/user-notifications.json';
    if (file_exists($notificationFile)) {
        $notificationContent = file_get_contents($notificationFile);
        $notificationData = json_decode($notificationContent, true) ?: [];
    }
    $userNotifications = $notificationData[$userId] ?? null;

    // 계정 설정 정보 가져오기
    $accountData = [];
    $accountFile = __DIR__ . '/../../includes/data/user-accounts.json';
    if (file_exists($accountFile)) {
        $accountContent = file_get_contents($accountFile);
        $accountData = json_decode($accountContent, true) ?: [];
    }
    $userAccount = $accountData[$userId] ?? null;
} else {
    // 판매자인 경우 변수 초기화
    $userPoints = null;
    $userWishlist = null;
    $userOrders = null;
    $userNotifications = null;
    $userAccount = null;
}
?>

<style>
    .member-detail-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    
    .detail-header h1 {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .back-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #f3f4f6;
        color: #374151;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .back-button:hover {
        background: #e5e7eb;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }
    
    @media (max-width: 768px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .detail-card {
        background: white;
        border-radius: 8px;
        padding: 24px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .detail-card-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .detail-item {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .detail-item:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        width: 150px;
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        flex-shrink: 0;
    }
    
    .detail-value {
        flex: 1;
        font-size: 14px;
        color: #1f2937;
        word-break: break-word;
    }
    
    .detail-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-user {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .badge-seller {
        background: #fef3c7;
        color: #92400e;
    }
    
    .badge-admin {
        background: #fce7f3;
        color: #9f1239;
    }
    
    .badge-sub-admin {
        background: #e0e7ff;
        color: #3730a3;
    }
    
    .badge-approved {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-pending {
        background: #fef3c7;
        color: #92400e;
    }
    
    .badge-on-hold {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .badge-rejected {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .badge-withdrawal {
        background: #fef3c7;
        color: #92400e;
    }
    
    .permission-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        margin-right: 6px;
        margin-bottom: 6px;
        background: #e0e7ff;
        color: #3730a3;
    }
    
    .no-permission {
        color: #9ca3af;
        font-style: italic;
    }
    
    .license-image:hover {
        transform: scale(1.02);
    }
    
    /* 버튼 스타일 */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 500;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    
    .btn-success {
        background: #10b981 !important;
        color: white !important;
    }
    
    .btn-success:hover {
        background: #059669 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
    }
    
    .btn-warning {
        background: #f59e0b !important;
        color: white !important;
    }
    
    .btn-warning:hover {
        background: #d97706 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(245, 158, 11, 0.3);
    }
    
    .btn-primary {
        background: #6366f1 !important;
        color: white !important;
    }
    
    .btn-primary:hover {
        background: #4f46e5 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(99, 102, 241, 0.3);
    }
    
    /* 모달 버튼 스타일 */
    .modal-btn-confirm {
        background: #22d3a3 !important;
        color: white !important;
        box-shadow: 0 2px 8px rgba(34, 211, 163, 0.4) !important;
    }
    
    .modal-btn-confirm:hover {
        background: #10b981 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(34, 211, 163, 0.5) !important;
    }
    
    .sns-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 500;
        background: #f3f4f6;
        color: #6b7280;
    }
    
    .points-section {
        margin-top: 24px;
    }
    
    .points-balance {
        font-size: 32px;
        font-weight: 700;
        color: #3b82f6;
        margin-bottom: 16px;
    }
    
    .points-history {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .points-history-item {
        padding: 12px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .points-history-item:last-child {
        border-bottom: none;
    }
    
    .points-history-info {
        flex: 1;
    }
    
    .points-history-date {
        font-size: 12px;
        color: #9ca3af;
        margin-bottom: 4px;
    }
    
    .points-history-desc {
        font-size: 14px;
        color: #1f2937;
        font-weight: 500;
    }
    
    .points-history-amount {
        font-size: 16px;
        font-weight: 700;
    }
    
    .points-history-amount.positive {
        color: #10b981;
    }
    
    .points-history-amount.negative {
        color: #ef4444;
    }
    
    .points-history-balance {
        font-size: 12px;
        color: #6b7280;
        margin-top: 4px;
    }
    
    .no-data {
        text-align: center;
        padding: 40px;
        color: #9ca3af;
    }
    
    .mypage-section {
        margin-bottom: 24px;
    }
    
    .mypage-item-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .mypage-item {
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .mypage-item:last-child {
        border-bottom: none;
    }
    
    .mypage-item-label {
        font-size: 14px;
        color: #1f2937;
        font-weight: 500;
    }
    
    .mypage-item-value {
        font-size: 14px;
        color: #6b7280;
    }
    
    .order-item {
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 12px;
    }
    
    .order-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    
    .order-item-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
    }
    
    .order-item-date {
        font-size: 12px;
        color: #9ca3af;
    }
    
    .order-item-info {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        font-size: 14px;
    }
    
    .order-info-label {
        color: #6b7280;
        font-weight: 500;
    }
    
    .order-info-value {
        color: #1f2937;
    }
    
    .wishlist-item {
        padding: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .wishlist-item-name {
        font-size: 14px;
        font-weight: 500;
        color: #1f2937;
    }
    
    .wishlist-item-date {
        font-size: 12px;
        color: #9ca3af;
    }
    
    /* 버튼 스타일 */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 500;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    
    .btn-success {
        background: #10b981 !important;
        color: white !important;
    }
    
    .btn-success:hover {
        background: #059669 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
    }
    
    .btn-warning {
        background: #f59e0b !important;
        color: white !important;
    }
    
    .btn-warning:hover {
        background: #d97706 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(245, 158, 11, 0.3);
    }
    
    .btn-primary {
        background: #6366f1 !important;
        color: white !important;
    }
    
    .btn-primary:hover {
        background: #4f46e5 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(99, 102, 241, 0.3);
    }
</style>

<div class="admin-content">
    <div class="member-detail-container">
        <div class="detail-header">
            <h1><?php echo $isSeller ? '판매자 상세 정보' : '회원 상세 정보'; ?></h1>
            <div style="display: flex; gap: 12px; align-items: center;">
                <a href="<?php echo $isSeller ? '/MVNO/admin/seller-approval.php?tab=approved' : '/MVNO/admin/users/member-list.php'; ?>" class="back-button">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    목록으로
                </a>
                <?php if ($isSeller): ?>
                    <a href="/MVNO/admin/users/seller-edit.php?user_id=<?php echo urlencode($userId); ?>" class="back-button" style="background: #6366f1; color: white;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        회원정보 수정
                    </a>
                <?php elseif ($isAdmin): ?>
                    <a href="/MVNO/admin/settings/admin-manage.php?edit=<?php echo urlencode($userId); ?>" class="back-button" style="background: #6366f1; color: white;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        정보 수정
                    </a>
                <?php elseif (!$isSeller && !$isAdmin): ?>
                    <a href="/MVNO/admin/users/user-edit.php?user_id=<?php echo urlencode($userId); ?>" class="back-button" style="background: #6366f1; color: white;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        회원정보 수정
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="detail-grid">
            <!-- 기본 정보 -->
            <div class="detail-card">
                <h2 class="detail-card-title">기본 정보</h2>
                <div class="detail-item">
                    <div class="detail-label">아이디</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['user_id'] ?? ''); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">이름</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['name'] ?? ''); ?></div>
                </div>
                <?php if (!$isSeller && !$isAdmin): ?>
                    <div class="detail-item">
                        <div class="detail-label">이메일</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">전화번호</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></div>
                    </div>
                <?php elseif ($isAdmin): ?>
                    <div class="detail-item">
                        <div class="detail-label">전화번호</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></div>
                    </div>
                <?php endif; ?>
                <div class="detail-item">
                    <div class="detail-label">역할</div>
                    <div class="detail-value">
                        <?php 
                        $userRole = $user['role'] ?? 'user';
                        $roleNames = [
                            'user' => '일반 회원',
                            'seller' => '판매자',
                            'admin' => '관리자',
                            'sub_admin' => '부관리자'
                        ];
                        $roleClass = [
                            'user' => 'badge-user',
                            'seller' => 'badge-seller',
                            'admin' => 'badge-admin',
                            'sub_admin' => 'badge-sub-admin'
                        ];
                        ?>
                        <span class="detail-badge <?php echo $roleClass[$userRole] ?? ''; ?>">
                            <?php echo $roleNames[$userRole] ?? $userRole; ?>
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">가입일</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['created_at'] ?? ''); ?></div>
                </div>
            </div>
            
            <!-- 가입 정보 -->
            <div class="detail-card">
                <h2 class="detail-card-title">가입 정보</h2>
                <?php if (isset($user['sns_provider'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">가입 방식</div>
                        <div class="detail-value">
                            <span class="sns-badge">SNS 가입 (<?php echo strtoupper($user['sns_provider']); ?>)</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">SNS 제공자</div>
                        <div class="detail-value"><?php echo htmlspecialchars(strtoupper($user['sns_provider'] ?? '')); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">SNS ID</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user['sns_id'] ?? ''); ?></div>
                    </div>
                <?php else: ?>
                    <div class="detail-item">
                        <div class="detail-label">가입 방식</div>
                        <div class="detail-value">직접 가입</div>
                    </div>
                <?php endif; ?>
                
                <?php if ($isSeller): ?>
                    <div class="detail-item">
                        <div class="detail-label">승인 상태</div>
                        <div class="detail-value" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <?php 
                            $approvalStatus = $user['approval_status'] ?? 'pending';
                            $isApproved = isset($user['seller_approved']) && $user['seller_approved'] === true;
                            
                            if ($isApproved || $approvalStatus === 'approved') {
                                echo '<span class="detail-badge badge-approved">승인됨</span>';
                            } elseif ($approvalStatus === 'on_hold') {
                                echo '<span class="detail-badge badge-on-hold">승인 보류</span>';
                            } elseif ($approvalStatus === 'rejected') {
                                echo '<span class="detail-badge badge-rejected">승인불가</span>';
                            } elseif ($approvalStatus === 'withdrawal_requested') {
                                echo '<span class="detail-badge badge-withdrawal">탈퇴 요청</span>';
                            } else {
                                echo '<span class="detail-badge badge-pending">대기중</span>';
                            }
                            ?>
                            <?php if (isset($user['approved_at']) && ($isApproved || $approvalStatus === 'approved')): ?>
                                <span style="font-size: 13px; color: #6b7280;"><?php echo htmlspecialchars($user['approved_at']); ?></span>
                            <?php endif; ?>
                            <?php if (isset($user['held_at']) && $approvalStatus === 'on_hold'): ?>
                                <span style="font-size: 13px; color: #6b7280;"><?php echo htmlspecialchars($user['held_at']); ?></span>
                            <?php endif; ?>
                            <div style="margin-left: auto;">
                                <?php if (!$isApproved && $approvalStatus !== 'on_hold'): ?>
                                    <!-- 승인 대기 회원: 승인 버튼 -->
                                    <button type="button" onclick="showApproveConfirmModal('<?php echo htmlspecialchars($user['user_id']); ?>', '<?php echo htmlspecialchars($user['name'] ?? $user['user_id']); ?>')" class="btn btn-success" style="padding: 8px 16px; font-size: 13px; height: 36px; line-height: 1;">승인</button>
                                <?php elseif ($approvalStatus === 'on_hold'): ?>
                                    <!-- 승인보류 회원: 승인 버튼 -->
                                    <button type="button" onclick="showApproveConfirmModal('<?php echo htmlspecialchars($user['user_id']); ?>', '<?php echo htmlspecialchars($user['name'] ?? $user['user_id']); ?>')" class="btn btn-success" style="padding: 8px 16px; font-size: 13px; height: 36px; line-height: 1;">승인</button>
                                <?php elseif ($isApproved || $approvalStatus === 'approved'): ?>
                                    <!-- 승인된 회원: 승인보류 버튼 -->
                                    <button type="button" onclick="showHoldConfirmModal('<?php echo htmlspecialchars($user['user_id']); ?>', '<?php echo htmlspecialchars($user['name'] ?? $user['user_id']); ?>')" class="btn btn-warning" style="padding: 8px 16px; font-size: 13px; height: 36px; line-height: 1;">승인보류</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 판매자 권한 정보 (가입 정보 카드 안에 포함) -->
                    <div class="detail-item">
                        <div class="detail-label">권한</div>
                        <div class="detail-value" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <?php 
                            $permissions = $user['permissions'] ?? [];
                            $permNames = [
                                'mvno' => '알뜰폰',
                                'mno' => '통신사폰',
                                'internet' => '인터넷'
                            ];
                            if (empty($permissions)) {
                                echo '<span class="no-permission">권한 없음</span>';
                            } else {
                                foreach ($permissions as $perm) {
                                    $permName = $permNames[$perm] ?? $perm;
                                    echo '<span class="permission-badge">' . htmlspecialchars($permName) . '</span>';
                                }
                            }
                            ?>
                            <?php if (isset($user['permissions_updated_at'])): ?>
                                <span style="font-size: 13px; color: #6b7280;"><?php echo htmlspecialchars($user['permissions_updated_at']); ?></span>
                            <?php endif; ?>
                            <button type="button" class="btn btn-primary" onclick="openPermissionModal('<?php echo htmlspecialchars($user['user_id']); ?>')" style="margin-left: auto; padding: 8px 16px; font-size: 13px; height: 36px; line-height: 1;">
                                권한 설정
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php 
        // 성공/에러 메시지 처리
        if (isset($_GET['success'])) {
            switch ($_GET['success']) {
                case 'approve':
                    $success_message = '판매자가 승인되었습니다.';
                    break;
                case 'hold':
                    $success_message = '판매자 승인이 보류되었습니다.';
                    break;
                case 'permissions':
                    $success_message = '권한이 성공적으로 저장되었습니다.';
                    break;
            }
        }
        if (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'approve':
                    $error_message = '판매자 승인에 실패했습니다.';
                    break;
                case 'hold':
                    $error_message = '판매자 승인보류에 실패했습니다.';
                    break;
                case 'permissions':
                    $error_message = '권한 저장에 실패했습니다.';
                    break;
            }
        }
        ?>
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" style="margin-bottom: 20px; padding: 12px 16px; border-radius: 8px; background: #d1fae5; color: #065f46; border: 1px solid #10b981; max-width: 1200px; margin: 0 auto 24px;">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error" style="margin-bottom: 20px; padding: 12px 16px; border-radius: 8px; background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; max-width: 1200px; margin: 0 auto 24px;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- 사업자 정보 -->
        <?php if ($isSeller): ?>
            <?php if (isset($user['business_number']) || isset($user['company_name']) || isset($user['phone']) || isset($user['mobile'])): ?>
                <div class="detail-card" style="margin-bottom: 24px;">
                    <h2 class="detail-card-title">사업자 정보</h2>
                    
                    <?php if (isset($user['business_number'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">사업자등록번호</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user['business_number']); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($user['company_name'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">회사명</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user['company_name']); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($user['company_representative'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">대표자명</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user['company_representative']); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($user['business_type'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">업태</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user['business_type']); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($user['business_item'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">종목</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user['business_item']); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($user['phone'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">전화번호</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user['phone']); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($user['mobile'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">휴대폰</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user['mobile']); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($user['postal_code']) || isset($user['address'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">주소</div>
                            <div class="detail-value">
                                <?php if (isset($user['postal_code'])): ?>
                                    <?php echo htmlspecialchars($user['postal_code']); ?><br>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($user['address'] ?? ''); ?>
                                <?php if (isset($user['address_detail'])): ?>
                                    <?php echo htmlspecialchars($user['address_detail']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($user['business_license_image']) && !empty($user['business_license_image'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">사업자등록증</div>
                            <div class="detail-value">
                                <img src="<?php echo htmlspecialchars($user['business_license_image']); ?>" alt="사업자등록증" class="license-image" onclick="showImageZoom(this.src)" style="max-width: 600px; max-height: 400px; border: 1px solid #e5e7eb; border-radius: 8px; margin-top: 12px; cursor: pointer; transition: transform 0.2s;">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- 포인트 정보 (일반 회원만) -->
        <?php if (!$isSeller && !$isAdmin): ?>
        <div class="detail-card mypage-section">
            <h2 class="detail-card-title">포인트 정보</h2>
            <?php if ($userPoints): ?>
                <div class="points-section">
                    <div class="points-balance">
                        현재 포인트: <?php echo number_format($userPoints['balance'] ?? 0); ?>P
                    </div>
                    
                    <?php if (!empty($userPoints['history'])): ?>
                        <div class="points-history">
                            <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: #374151;">포인트 내역</h3>
                            <?php 
                            $history = array_reverse($userPoints['history']); // 최신순
                            foreach ($history as $item): 
                            ?>
                                <div class="points-history-item">
                                    <div class="points-history-info">
                                        <div class="points-history-date"><?php echo htmlspecialchars($item['date'] ?? ''); ?></div>
                                        <div class="points-history-desc"><?php echo htmlspecialchars($item['description'] ?? ''); ?></div>
                                        <?php if (isset($item['balance_after'])): ?>
                                            <div class="points-history-balance">잔액: <?php echo number_format($item['balance_after']); ?>P</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="points-history-amount <?php echo ($item['amount'] ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo ($item['amount'] ?? 0) >= 0 ? '+' : ''; ?><?php echo number_format($item['amount'] ?? 0); ?>P
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">포인트 내역이 없습니다.</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-data">포인트 정보가 없습니다.</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- 찜한 요금제 (일반 회원만) -->
        <?php if (!$isSeller && !$isAdmin): ?>
        <div class="detail-card mypage-section">
            <h2 class="detail-card-title">찜한 요금제</h2>
            <?php if ($userWishlist): ?>
                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #374151;">알뜰폰 요금제</h3>
                    <?php if (!empty($userWishlist['mvno'])): ?>
                        <?php foreach ($userWishlist['mvno'] as $item): ?>
                            <div class="wishlist-item">
                                <div>
                                    <div class="wishlist-item-name"><?php echo htmlspecialchars($item['name'] ?? '알뜰폰 요금제'); ?></div>
                                    <div class="wishlist-item-date"><?php echo htmlspecialchars($item['added_at'] ?? ''); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data" style="padding: 20px;">찜한 알뜰폰 요금제가 없습니다.</div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #374151;">통신사폰 요금제</h3>
                    <?php if (!empty($userWishlist['mno'])): ?>
                        <?php foreach ($userWishlist['mno'] as $item): ?>
                            <div class="wishlist-item">
                                <div>
                                    <div class="wishlist-item-name"><?php echo htmlspecialchars($item['name'] ?? '통신사폰 요금제'); ?></div>
                                    <div class="wishlist-item-date"><?php echo htmlspecialchars($item['added_at'] ?? ''); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data" style="padding: 20px;">찜한 통신사폰 요금제가 없습니다.</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-data">찜한 요금제가 없습니다.</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- 신청 내역 (일반 회원만) -->
        <?php if (!$isSeller && !$isAdmin): ?>
        <div class="detail-card mypage-section">
            <h2 class="detail-card-title">신청 내역</h2>
            <?php if ($userOrders): ?>
                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #374151;">알뜰폰 신청내역</h3>
                    <?php if (!empty($userOrders['mvno'])): ?>
                        <?php foreach ($userOrders['mvno'] as $order): ?>
                            <div class="order-item">
                                <div class="order-item-header">
                                    <div class="order-item-title"><?php echo htmlspecialchars($order['plan_name'] ?? '알뜰폰 요금제'); ?></div>
                                    <div class="order-item-date"><?php echo htmlspecialchars($order['order_date'] ?? ''); ?></div>
                                </div>
                                <div class="order-item-info">
                                    <div>
                                        <span class="order-info-label">상태: </span>
                                        <span class="order-info-value"><?php echo htmlspecialchars($order['status'] ?? '-'); ?></span>
                                    </div>
                                    <div>
                                        <span class="order-info-label">주문번호: </span>
                                        <span class="order-info-value"><?php echo htmlspecialchars($order['order_id'] ?? '-'); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data" style="padding: 20px;">알뜰폰 신청내역이 없습니다.</div>
                    <?php endif; ?>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #374151;">통신사폰 신청내역</h3>
                    <?php if (!empty($userOrders['mno'])): ?>
                        <?php foreach ($userOrders['mno'] as $order): ?>
                            <div class="order-item">
                                <div class="order-item-header">
                                    <div class="order-item-title"><?php echo htmlspecialchars($order['phone_name'] ?? '통신사폰'); ?></div>
                                    <div class="order-item-date"><?php echo htmlspecialchars($order['order_date'] ?? ''); ?></div>
                                </div>
                                <div class="order-item-info">
                                    <div>
                                        <span class="order-info-label">상태: </span>
                                        <span class="order-info-value"><?php echo htmlspecialchars($order['status'] ?? '-'); ?></span>
                                    </div>
                                    <div>
                                        <span class="order-info-label">주문번호: </span>
                                        <span class="order-info-value"><?php echo htmlspecialchars($order['order_id'] ?? '-'); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data" style="padding: 20px;">통신사폰 신청내역이 없습니다.</div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #374151;">인터넷 신청내역</h3>
                    <?php if (!empty($userOrders['internet'])): ?>
                        <?php foreach ($userOrders['internet'] as $order): ?>
                            <div class="order-item">
                                <div class="order-item-header">
                                    <div class="order-item-title"><?php echo htmlspecialchars($order['product_name'] ?? '인터넷 상품'); ?></div>
                                    <div class="order-item-date"><?php echo htmlspecialchars($order['order_date'] ?? ''); ?></div>
                                </div>
                                <div class="order-item-info">
                                    <div>
                                        <span class="order-info-label">상태: </span>
                                        <span class="order-info-value"><?php echo htmlspecialchars($order['status'] ?? '-'); ?></span>
                                    </div>
                                    <div>
                                        <span class="order-info-label">주문번호: </span>
                                        <span class="order-info-value"><?php echo htmlspecialchars($order['order_id'] ?? '-'); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data" style="padding: 20px;">인터넷 신청내역이 없습니다.</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-data">신청 내역이 없습니다.</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- 알림 설정 (일반 회원만) -->
        <?php if (!$isSeller && !$isAdmin): ?>
        <div class="detail-card mypage-section">
            <h2 class="detail-card-title">알림 설정</h2>
            <?php if ($userNotifications): ?>
                <ul class="mypage-item-list">
                    <li class="mypage-item">
                        <span class="mypage-item-label">이벤트 알림</span>
                        <span class="mypage-item-value"><?php echo isset($userNotifications['event']) && $userNotifications['event'] ? '활성화' : '비활성화'; ?></span>
                    </li>
                    <li class="mypage-item">
                        <span class="mypage-item-label">공지사항 알림</span>
                        <span class="mypage-item-value"><?php echo isset($userNotifications['notice']) && $userNotifications['notice'] ? '활성화' : '비활성화'; ?></span>
                    </li>
                    <li class="mypage-item">
                        <span class="mypage-item-label">주문 상태 알림</span>
                        <span class="mypage-item-value"><?php echo isset($userNotifications['order']) && $userNotifications['order'] ? '활성화' : '비활성화'; ?></span>
                    </li>
                    <li class="mypage-item">
                        <span class="mypage-item-label">마케팅 알림</span>
                        <span class="mypage-item-value"><?php echo isset($userNotifications['marketing']) && $userNotifications['marketing'] ? '활성화' : '비활성화'; ?></span>
                    </li>
                </ul>
            <?php else: ?>
                <div class="no-data">알림 설정 정보가 없습니다.</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- 계정 설정 (일반 회원만) -->
        <?php if (!$isSeller && !$isAdmin): ?>
        <div class="detail-card mypage-section">
            <h2 class="detail-card-title">계정 설정</h2>
            <?php if ($userAccount): ?>
                <ul class="mypage-item-list">
                    <?php if (isset($userAccount['phone'])): ?>
                        <li class="mypage-item">
                            <span class="mypage-item-label">전화번호</span>
                            <span class="mypage-item-value"><?php echo htmlspecialchars($userAccount['phone']); ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (isset($userAccount['address'])): ?>
                        <li class="mypage-item">
                            <span class="mypage-item-label">주소</span>
                            <span class="mypage-item-value"><?php echo htmlspecialchars($userAccount['address']); ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (isset($userAccount['birth_date'])): ?>
                        <li class="mypage-item">
                            <span class="mypage-item-label">생년월일</span>
                            <span class="mypage-item-value"><?php echo htmlspecialchars($userAccount['birth_date']); ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (isset($userAccount['gender'])): ?>
                        <li class="mypage-item">
                            <span class="mypage-item-label">성별</span>
                            <span class="mypage-item-value"><?php echo htmlspecialchars($userAccount['gender']); ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (isset($userAccount['updated_at'])): ?>
                        <li class="mypage-item">
                            <span class="mypage-item-label">최종 수정일</span>
                            <span class="mypage-item-value"><?php echo htmlspecialchars($userAccount['updated_at']); ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php else: ?>
                <div class="no-data">계정 설정 정보가 없습니다.</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- 전체 데이터 (JSON) -->
        <div class="detail-card">
            <h2 class="detail-card-title">전체 데이터 (JSON)</h2>
            <pre style="background: #f9fafb; padding: 16px; border-radius: 8px; overflow-x: auto; font-size: 12px; line-height: 1.6;"><?php echo htmlspecialchars(json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
        </div>
    </div>
</div>

<!-- 이미지 확대 오버레이 (판매자 사업자등록증용) -->
<?php if ($isSeller): ?>
<div class="image-zoom-overlay" id="imageZoomOverlay" onclick="closeImageZoom()" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.9); z-index: 2000; align-items: center; justify-content: center; cursor: pointer;">
    <img src="" alt="확대 이미지" class="image-zoom-content" onclick="event.stopPropagation()" style="max-width: 90%; max-height: 90%; border-radius: 8px;">
</div>

<script>
    function showImageZoom(imageSrc) {
        const overlay = document.getElementById('imageZoomOverlay');
        const img = overlay.querySelector('img');
        img.src = imageSrc;
        overlay.style.display = 'flex';
    }
    
    function closeImageZoom() {
        document.getElementById('imageZoomOverlay').style.display = 'none';
    }
</script>
<?php endif; ?>

<?php if ($isSeller): ?>
<!-- 권한 설정 모달 -->
<div class="modal-overlay" id="permissionModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal" style="background: white; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);">
        <div class="modal-title" style="font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 16px;">판매자 권한 설정</div>
        
        <form method="POST" action="/MVNO/admin/users/member-detail.php?user_id=<?php echo urlencode($user['user_id']); ?>" class="permissions-form" id="permissionsForm_<?php echo htmlspecialchars($user['user_id']); ?>" data-user-id="<?php echo htmlspecialchars($user['user_id']); ?>" data-initial-permissions="<?php echo htmlspecialchars(json_encode($user['permissions'] ?? [])); ?>">
            <input type="hidden" name="save_permissions" value="1">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
            
            <div style="margin-bottom: 20px;">
                <div style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 12px;">게시판 등록 권한</div>
                <div class="permissions-checkboxes" style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="permission-item" style="display: flex; align-items: center; gap: 8px;">
                        <input 
                            type="checkbox" 
                            id="modal_mvno_<?php echo htmlspecialchars($user['user_id']); ?>" 
                            name="permissions[]" 
                            value="mvno"
                            class="permission-checkbox"
                            style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;"
                            <?php echo (isset($user['permissions']) && in_array('mvno', $user['permissions'])) ? 'checked' : ''; ?>
                        >
                        <label for="modal_mvno_<?php echo htmlspecialchars($user['user_id']); ?>" style="font-size: 14px; color: #374151; cursor: pointer; user-select: none;">
                            알뜰폰
                        </label>
                    </div>
                    
                    <div class="permission-item" style="display: flex; align-items: center; gap: 8px;">
                        <input 
                            type="checkbox" 
                            id="modal_mno_<?php echo htmlspecialchars($user['user_id']); ?>" 
                            name="permissions[]" 
                            value="mno"
                            class="permission-checkbox"
                            style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;"
                            <?php echo (isset($user['permissions']) && in_array('mno', $user['permissions'])) ? 'checked' : ''; ?>
                        >
                        <label for="modal_mno_<?php echo htmlspecialchars($user['user_id']); ?>" style="font-size: 14px; color: #374151; cursor: pointer; user-select: none;">
                            통신사폰
                        </label>
                    </div>
                    
                    <div class="permission-item" style="display: flex; align-items: center; gap: 8px;">
                        <input 
                            type="checkbox" 
                            id="modal_internet_<?php echo htmlspecialchars($user['user_id']); ?>" 
                            name="permissions[]" 
                            value="internet"
                            class="permission-checkbox"
                            style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;"
                            <?php echo (isset($user['permissions']) && in_array('internet', $user['permissions'])) ? 'checked' : ''; ?>
                        >
                        <label for="modal_internet_<?php echo htmlspecialchars($user['user_id']); ?>" style="font-size: 14px; color: #374151; cursor: pointer; user-select: none;">
                            인터넷
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="modal-actions" style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closePermissionModal()" style="padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; background: #f3f4f6; color: #374151;">취소</button>
                <button type="button" class="modal-btn modal-btn-confirm" onclick="checkAndSavePermissions('<?php echo htmlspecialchars($user['user_id']); ?>')" style="padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; background: #22d3a3; color: white; box-shadow: 0 2px 8px rgba(34, 211, 163, 0.4);">저장</button>
            </div>
        </form>
    </div>
</div>

<!-- 승인 확인 모달 -->
<div class="modal-overlay" id="approveConfirmModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 1002; align-items: center; justify-content: center;">
    <div class="modal" style="background: white; border-radius: 12px; padding: 24px; max-width: 400px; width: 90%; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);">
        <div class="modal-title" style="font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 16px;">승인 상태 변경 확인</div>
        <div class="modal-message" id="approveConfirmMessage" style="font-size: 14px; color: #6b7280; margin-bottom: 24px; line-height: 1.6;">
            <strong id="approveConfirmUserName"></strong> 판매자의 승인 상태를 <strong style="color: #10b981;">승인</strong>으로 변경하시겠습니까?
        </div>
        <div class="modal-actions" style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeApproveConfirmModal()" style="padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; background: #f3f4f6; color: #374151;">취소</button>
            <form method="POST" action="/MVNO/admin/users/member-detail.php?user_id=<?php echo urlencode($user['user_id']); ?>" id="approveConfirmForm" style="display: inline;">
                <input type="hidden" name="user_id" id="approveConfirmUserId">
                <button type="submit" name="approve_seller" class="modal-btn modal-btn-confirm" style="padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; background: #10b981; color: white;">승인</button>
            </form>
        </div>
    </div>
</div>

<!-- 승인보류 확인 모달 -->
<div class="modal-overlay" id="holdConfirmModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 1002; align-items: center; justify-content: center;">
    <div class="modal" style="background: white; border-radius: 12px; padding: 24px; max-width: 400px; width: 90%; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);">
        <div class="modal-title" style="font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 16px;">승인 상태 변경 확인</div>
        <div class="modal-message" id="holdConfirmMessage" style="font-size: 14px; color: #6b7280; margin-bottom: 24px; line-height: 1.6;">
            <strong id="holdConfirmUserName"></strong> 판매자의 승인 상태를 <strong style="color: #f59e0b;">승인보류</strong>로 변경하시겠습니까?
        </div>
        <div class="modal-actions" style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeHoldConfirmModal()" style="padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; background: #f3f4f6; color: #374151;">취소</button>
            <form method="POST" action="/MVNO/admin/users/member-detail.php?user_id=<?php echo urlencode($user['user_id']); ?>" id="holdConfirmForm" style="display: inline;">
                <input type="hidden" name="user_id" id="holdConfirmUserId">
                <button type="submit" name="hold_seller" class="modal-btn modal-btn-hold" style="padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; background: #f59e0b; color: white;">승인보류</button>
            </form>
        </div>
    </div>
</div>

<!-- 저장 확인 모달 -->
<div class="modal-overlay" id="saveModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 1001; align-items: center; justify-content: center;">
    <div class="modal" style="background: white; border-radius: 12px; padding: 24px; max-width: 400px; width: 90%; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);">
        <div class="modal-title" style="font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 16px;">권한 저장 확인</div>
        <div class="modal-message" id="saveModalMessage" style="font-size: 14px; color: #6b7280; margin-bottom: 24px; line-height: 1.6;">
            판매자 권한을 저장하시겠습니까?
        </div>
        <div class="modal-actions" style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeSaveModal()" style="padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; background: #f3f4f6; color: #374151;">취소</button>
            <button type="button" class="modal-btn modal-btn-confirm" onclick="confirmSave()" style="padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; background: #22d3a3; color: white; box-shadow: 0 2px 8px rgba(34, 211, 163, 0.4);">저장</button>
        </div>
    </div>
</div>

<script>
    // 권한 설정 모달 열기
    function openPermissionModal(userId) {
        const modal = document.getElementById('permissionModal');
        modal.style.display = 'flex';
    }
    
    // 권한 설정 모달 닫기
    function closePermissionModal() {
        document.getElementById('permissionModal').style.display = 'none';
    }
    
    // 권한 변경 감지 및 저장 처리
    function checkAndSavePermissions(userId) {
        console.log('checkAndSavePermissions called with userId:', userId);
        
        const form = document.getElementById('permissionsForm_' + userId);
        if (!form) {
            console.error('Form not found for userId:', userId);
            alert('폼을 찾을 수 없습니다.');
            return;
        }
        
        const initialPermissions = JSON.parse(form.getAttribute('data-initial-permissions') || '[]');
        
        // 현재 선택된 권한 가져오기
        const checkboxes = form.querySelectorAll('.permission-checkbox:checked');
        const currentPermissions = Array.from(checkboxes).map(cb => cb.value);
        
        // 권한이 변경되었는지 확인
        const initialSorted = [...initialPermissions].sort();
        const currentSorted = [...currentPermissions].sort();
        const hasChanged = JSON.stringify(initialSorted) !== JSON.stringify(currentSorted);
        
        console.log('Initial permissions:', initialPermissions);
        console.log('Current permissions:', currentPermissions);
        console.log('Has changed:', hasChanged);
        
        if (!hasChanged) {
            showNoChangeModal();
            return;
        }
        
        // 변경사항이 있으면 저장 확인 모달 표시
        console.log('Showing save modal');
        showSaveModal();
        window.pendingFormId = userId;
    }
    
    // 저장 확인 모달 표시
    function showSaveModal() {
        console.log('showSaveModal called');
        const modal = document.getElementById('saveModal');
        if (!modal) {
            console.error('Save modal not found');
            alert('저장 확인 모달을 찾을 수 없습니다.');
            return;
        }
        
        console.log('Modal found, setting up content');
        const modalTitle = modal.querySelector('.modal-title');
        const modalMessage = modal.querySelector('.modal-message');
        const modalActions = modal.querySelector('.modal-actions');
        
        if (modalTitle) modalTitle.textContent = '권한 저장 확인';
        if (modalMessage) modalMessage.textContent = '판매자 권한을 저장하시겠습니까?';
        
        if (modalActions) {
            modalActions.innerHTML = `
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeSaveModal()" style="padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; background: #f3f4f6; color: #374151;">취소</button>
                <button type="button" class="modal-btn modal-btn-confirm" onclick="confirmSave()" style="padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; background: #22d3a3; color: white; box-shadow: 0 2px 8px rgba(34, 211, 163, 0.4);">저장</button>
            `;
        }
        
        console.log('Displaying modal');
        modal.style.display = 'flex';
        console.log('Modal display set to flex');
    }
    
    // 변경 사항 없음 모달 표시
    function showNoChangeModal() {
        const modal = document.getElementById('saveModal');
        if (!modal) {
            console.error('Save modal not found');
            return;
        }
        
        const modalTitle = modal.querySelector('.modal-title');
        const modalMessage = modal.querySelector('.modal-message');
        const modalActions = modal.querySelector('.modal-actions');
        
        if (modalTitle) modalTitle.textContent = '알림';
        if (modalMessage) modalMessage.textContent = '변경된 권한이 없습니다.';
        
        if (modalActions) {
            modalActions.innerHTML = `
                <button type="button" class="modal-btn modal-btn-confirm" onclick="closeSaveModal()" style="width: 100%; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; background: #22d3a3; color: white; box-shadow: 0 2px 8px rgba(34, 211, 163, 0.4);">확인</button>
            `;
        }
        
        modal.style.display = 'flex';
    }
    
    // 모달 닫기
    function closeSaveModal() {
        const modal = document.getElementById('saveModal');
        if (modal) {
            modal.style.display = 'none';
        }
        window.pendingFormId = null;
    }
    
    // 모달에서 확인 클릭 시 저장 실행
    function confirmSave() {
        console.log('confirmSave called');
        const userId = window.pendingFormId;
        if (!userId) {
            console.error('No pending form ID');
            alert('저장할 폼을 찾을 수 없습니다.');
            return;
        }
        
        console.log('Submitting form for userId:', userId);
        const form = document.getElementById('permissionsForm_' + userId);
        if (!form) {
            console.error('Form not found for userId:', userId);
            alert('폼을 찾을 수 없습니다.');
            return;
        }
        
        // 모달 닫기
        closeSaveModal();
        closePermissionModal();
        
        // 폼 제출
        console.log('Submitting form...');
        try {
            form.submit();
            console.log('Form submitted successfully');
        } catch (error) {
            console.error('Form submit error:', error);
            alert('저장 중 오류가 발생했습니다: ' + error.message);
        }
    }
    
    // 승인 확인 모달 표시
    function showApproveConfirmModal(userId, userName) {
        document.getElementById('approveConfirmUserId').value = userId;
        document.getElementById('approveConfirmUserName').textContent = userName;
        document.getElementById('approveConfirmModal').style.display = 'flex';
    }
    
    // 승인 확인 모달 닫기
    function closeApproveConfirmModal() {
        document.getElementById('approveConfirmModal').style.display = 'none';
    }
    
    // 승인보류 확인 모달 표시
    function showHoldConfirmModal(userId, userName) {
        document.getElementById('holdConfirmUserId').value = userId;
        document.getElementById('holdConfirmUserName').textContent = userName;
        document.getElementById('holdConfirmModal').style.display = 'flex';
    }
    
    // 승인보류 확인 모달 닫기
    function closeHoldConfirmModal() {
        document.getElementById('holdConfirmModal').style.display = 'none';
    }
    
    // 모달 외부 클릭 시 닫기
    document.addEventListener('DOMContentLoaded', function() {
        const saveModal = document.getElementById('saveModal');
        if (saveModal) {
            saveModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeSaveModal();
                }
            });
        }
        
        const permissionModal = document.getElementById('permissionModal');
        if (permissionModal) {
            permissionModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closePermissionModal();
                }
            });
        }
        
        const approveConfirmModal = document.getElementById('approveConfirmModal');
        if (approveConfirmModal) {
            approveConfirmModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeApproveConfirmModal();
                }
            });
        }
        
        const holdConfirmModal = document.getElementById('holdConfirmModal');
        if (holdConfirmModal) {
            holdConfirmModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeHoldConfirmModal();
                }
            });
        }
    });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

