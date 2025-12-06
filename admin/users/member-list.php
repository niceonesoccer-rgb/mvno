<?php
/**
 * 회원 관리 페이지
 */

require_once __DIR__ . '/../includes/admin-header.php';

// 사용자 데이터 가져오기
$usersData = getUsersData();
$users = $usersData['users'] ?? [];

// 탭 선택 (일반회원, 판매자, 관리자)
$activeTab = $_GET['tab'] ?? 'users'; // 'users', 'sellers', 'admins'
$searchQuery = $_GET['search'] ?? '';

// 탭별로 사용자 분리
$regularUsers = array_filter($users, function($user) {
    return ($user['role'] ?? 'user') === 'user';
});

$sellerUsers = array_filter($users, function($user) {
    return ($user['role'] ?? 'user') === 'seller';
});

$adminUsers = array_filter($users, function($user) {
    $role = $user['role'] ?? 'user';
    return $role === 'admin' || $role === 'sub_admin';
});

// 통계
$totalUsers = count($users);
$userCount = count(array_filter($users, fn($u) => ($u['role'] ?? 'user') === 'user'));
$sellerCount = count(array_filter($users, fn($u) => ($u['role'] ?? 'user') === 'seller'));
$adminCount = count(array_filter($users, fn($u) => ($u['role'] ?? 'user') === 'admin' || ($u['role'] ?? 'user') === 'sub_admin'));
?>

<style>
    .member-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .stat-label {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 8px;
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .member-filters {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .filter-row {
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .filter-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .filter-group label {
        font-size: 14px;
        color: #374151;
        font-weight: 500;
    }
    
    .filter-group select,
    .filter-group input {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
    }
    
    .filter-group input {
        min-width: 200px;
    }
    
    .member-table {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .member-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .member-table th {
        background: #f9fafb;
        padding: 12px 16px;
        text-align: left;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .member-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
        color: #1f2937;
    }
    
    .member-table tr:hover {
        background: #f9fafb;
    }
    
    .role-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .role-badge.user {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .role-badge.seller {
        background: #fef3c7;
        color: #92400e;
    }
    
    .role-badge.admin {
        background: #fce7f3;
        color: #9f1239;
    }
    
    .role-badge.sub_admin {
        background: #e0e7ff;
        color: #3730a3;
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
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-sm {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        border: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-primary {
        background: #3b82f6;
        color: white;
    }
    
    .btn-primary:hover {
        background: #2563eb;
    }
    
    .btn-danger {
        background: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background: #dc2626;
    }
    
    .no-results {
        text-align: center;
        padding: 40px;
        color: #6b7280;
    }
    
    .member-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 24px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .member-tab {
        padding: 12px 24px;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        font-size: 15px;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
        bottom: -2px;
    }
    
    .member-tab:hover {
        color: #374151;
        background: #f9fafb;
    }
    
    .member-tab.active {
        color: #3b82f6;
        border-bottom-color: #3b82f6;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
</style>

<div class="admin-content">
    <h1 class="admin-page-title">회원 관리</h1>
    
    <!-- 통계 -->
    <div class="member-stats">
        <div class="stat-card">
            <div class="stat-label">전체 회원</div>
            <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">일반 회원</div>
            <div class="stat-value"><?php echo number_format($userCount); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">판매자</div>
            <div class="stat-value"><?php echo number_format($sellerCount); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">관리자</div>
            <div class="stat-value"><?php echo number_format($adminCount); ?></div>
        </div>
    </div>
    
    <!-- 탭 메뉴 -->
    <div class="member-tabs">
        <a href="/MVNO/admin/users/member-list.php?tab=users" class="member-tab <?php echo $activeTab === 'users' ? 'active' : ''; ?>">
            일반 회원 (<?php echo number_format(count($regularUsers)); ?>)
        </a>
        <a href="/MVNO/admin/users/member-list.php?tab=sellers" class="member-tab <?php echo $activeTab === 'sellers' ? 'active' : ''; ?>">
            판매자 (<?php echo number_format(count($sellerUsers)); ?>)
        </a>
        <a href="/MVNO/admin/users/member-list.php?tab=admins" class="member-tab <?php echo $activeTab === 'admins' ? 'active' : ''; ?>">
            관리자/부관리자 (<?php echo number_format(count($adminUsers)); ?>)
        </a>
    </div>
    
    <!-- 필터 -->
    <div class="member-filters">
        <form method="GET" class="filter-row">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($activeTab); ?>">
            <div class="filter-group">
                <label>검색:</label>
                <input type="text" name="search" placeholder="이름, 이메일, 아이디" value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn-sm btn-primary">검색</button>
                <a href="/MVNO/admin/users/member-list.php?tab=<?php echo htmlspecialchars($activeTab); ?>" class="btn-sm btn-primary">초기화</a>
            </div>
        </form>
    </div>
    
    <!-- 일반 회원 탭 -->
    <div class="tab-content <?php echo $activeTab === 'users' ? 'active' : ''; ?>">
        <div class="member-table">
            <?php 
            // 일반 회원용 필터링
            $filteredRegularUsers = $regularUsers;
            if (!empty($searchQuery)) {
                $filteredRegularUsers = array_filter($filteredRegularUsers, function($user) use ($searchQuery) {
                    $name = $user['name'] ?? '';
                    $email = $user['email'] ?? '';
                    $userId = $user['user_id'] ?? '';
                    return stripos($name, $searchQuery) !== false || 
                           stripos($email, $searchQuery) !== false ||
                           stripos($userId, $searchQuery) !== false;
                });
            }
            ?>
            <?php if (empty($filteredRegularUsers)): ?>
                <div class="no-results">
                    검색 결과가 없습니다.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>아이디</th>
                            <th>이름</th>
                            <th>이메일</th>
                            <th>역할</th>
                            <th>SNS 제공자</th>
                            <th>SNS ID</th>
                            <th>가입일</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredRegularUsers as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['user_id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                <td>
                                    <span class="role-badge user">일반 회원</span>
                                </td>
                                <td>
                                    <?php if (isset($user['sns_provider'])): ?>
                                        <span class="sns-badge"><?php echo strtoupper($user['sns_provider']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user['sns_id'] ?? '-'); ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['created_at'] ?? ''); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="/MVNO/admin/users/member-detail.php?user_id=<?php echo urlencode($user['user_id']); ?>" class="btn-sm btn-primary">상세</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 판매자 탭 -->
    <div class="tab-content <?php echo $activeTab === 'sellers' ? 'active' : ''; ?>">
        <div class="member-table">
            <?php 
            // 판매자용 필터링
            $filteredSellers = $sellerUsers;
            if (!empty($searchQuery)) {
                $filteredSellers = array_filter($filteredSellers, function($user) use ($searchQuery) {
                    $name = $user['name'] ?? '';
                    $email = $user['email'] ?? '';
                    $userId = $user['user_id'] ?? '';
                    return stripos($name, $searchQuery) !== false || 
                           stripos($email, $searchQuery) !== false ||
                           stripos($userId, $searchQuery) !== false;
                });
            }
            ?>
            <?php if (empty($filteredSellers)): ?>
                <div class="no-results">
                    검색 결과가 없습니다.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>아이디</th>
                            <th>이름</th>
                            <th>이메일</th>
                            <th>역할</th>
                            <th>가입일</th>
                            <th>승인 상태</th>
                            <th>승인일</th>
                            <th>권한</th>
                            <th>권한 수정일</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredSellers as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['user_id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                <td>
                                    <span class="role-badge seller">판매자</span>
                                </td>
                                <td><?php echo htmlspecialchars($user['created_at'] ?? ''); ?></td>
                                <td>
                                    <?php if (isset($user['seller_approved']) && $user['seller_approved']): ?>
                                        <span style="color: #10b981;">승인됨</span>
                                    <?php else: ?>
                                        <span style="color: #f59e0b;">대기중</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user['approved_at'] ?? '-'); ?>
                                </td>
                                <td>
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
                                        $permLabels = array_map(function($p) use ($permNames) {
                                            return $permNames[$p] ?? $p;
                                        }, $permissions);
                                        echo htmlspecialchars(implode(', ', $permLabels));
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user['permissions_updated_at'] ?? '-'); ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if (!isset($user['seller_approved']) || !$user['seller_approved']): ?>
                                            <a href="/MVNO/admin/users/seller-approval.php?user_id=<?php echo urlencode($user['user_id']); ?>" class="btn-sm btn-primary">승인</a>
                                        <?php endif; ?>
                                        <a href="/MVNO/admin/users/seller-permissions.php?user_id=<?php echo urlencode($user['user_id']); ?>" class="btn-sm btn-primary">권한</a>
                                        <a href="/MVNO/admin/users/member-detail.php?user_id=<?php echo urlencode($user['user_id']); ?>" class="btn-sm btn-primary">상세</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 관리자/부관리자 탭 -->
    <div class="tab-content <?php echo $activeTab === 'admins' ? 'active' : ''; ?>">
        <div class="member-table">
            <?php 
            // 관리자용 필터링
            $filteredAdmins = $adminUsers;
            if (!empty($searchQuery)) {
                $filteredAdmins = array_filter($filteredAdmins, function($user) use ($searchQuery) {
                    $name = $user['name'] ?? '';
                    $email = $user['email'] ?? '';
                    $userId = $user['user_id'] ?? '';
                    return stripos($name, $searchQuery) !== false || 
                           stripos($email, $searchQuery) !== false ||
                           stripos($userId, $searchQuery) !== false;
                });
            }
            ?>
            <?php if (empty($filteredAdmins)): ?>
                <div class="no-results">
                    검색 결과가 없습니다.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>아이디</th>
                            <th>이름</th>
                            <th>이메일</th>
                            <th>역할</th>
                            <th>가입일</th>
                            <th>승인 상태</th>
                            <th>권한</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredAdmins as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['user_id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                <td>
                                    <?php 
                                    $role = $user['role'] ?? 'user';
                                    $roleNames = [
                                        'admin' => '관리자',
                                        'sub_admin' => '부관리자'
                                    ];
                                    ?>
                                    <span class="role-badge <?php echo $role; ?>">
                                        <?php echo $roleNames[$role] ?? $role; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($user['created_at'] ?? ''); ?></td>
                                <td>
                                    <?php if (isset($user['seller_approved'])): ?>
                                        <?php echo $user['seller_approved'] ? '<span style="color: #10b981;">승인됨</span>' : '<span style="color: #f59e0b;">미승인</span>'; ?>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $permissions = $user['permissions'] ?? [];
                                    if (empty($permissions)) {
                                        echo '<span style="color: #9ca3af;">-</span>';
                                    } else {
                                        echo htmlspecialchars(implode(', ', $permissions));
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="/MVNO/admin/users/member-detail.php?user_id=<?php echo urlencode($user['user_id']); ?>" class="btn-sm btn-primary">상세</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

