<?php
/**
 * 회원 상세 정보 페이지
 */

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
    
    .license-image:hover {
        transform: scale(1.02);
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
</style>

<div class="admin-content">
    <div class="member-detail-container">
        <div class="detail-header">
            <h1><?php echo $isSeller ? '판매자 상세 정보' : '회원 상세 정보'; ?></h1>
            <div style="display: flex; gap: 12px; align-items: center;">
                <?php if ($isAdmin): ?>
                    <a href="/MVNO/admin/settings/admin-manage.php?edit=<?php echo urlencode($userId); ?>" class="back-button" style="background: #6366f1; color: white;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        정보 수정
                    </a>
                <?php endif; ?>
                <a href="<?php echo $isSeller ? '/MVNO/admin/seller-approval.php?tab=approved' : '/MVNO/admin/users/member-list.php'; ?>" class="back-button">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    목록으로
                </a>
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
                <?php if ($isAdmin): ?>
                    <div class="detail-item">
                        <div class="detail-label">전화번호</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></div>
                    </div>
                <?php else: ?>
                    <div class="detail-item">
                        <div class="detail-label">이메일</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
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
                        <div class="detail-value">
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
                        </div>
                    </div>
                    <?php if (isset($user['approved_at'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">승인일</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user['approved_at']); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($user['held_at'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">보류일</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user['held_at']); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($user['withdrawal_requested_at'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">탈퇴 요청일</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user['withdrawal_requested_at']); ?></div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 판매자 권한 정보 -->
        <?php if ($isSeller): ?>
            <div class="detail-card" style="margin-bottom: 24px;">
                <h2 class="detail-card-title">판매자 권한</h2>
                <div class="detail-item">
                    <div class="detail-label">게시판 권한</div>
                    <div class="detail-value">
                        <?php 
                        $permissions = $user['permissions'] ?? [];
                        if (empty($permissions)) {
                            echo '<span style="color: #9ca3af;">권한 없음</span>';
                        } else {
                            $permNames = [
                                'mvno' => '알뜰폰',
                                'mno' => '통신사폰',
                                'internet' => '인터넷'
                            ];
                            foreach ($permissions as $perm) {
                                $permName = $permNames[$perm] ?? $perm;
                                echo '<span class="permission-badge">' . htmlspecialchars($permName) . '</span>';
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php if (isset($user['permissions_updated_at'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">권한 수정일</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user['permissions_updated_at']); ?></div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 사업자 정보 -->
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

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

