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

// 표시 개수 선택 (기본값: 50)
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
$perPageOptions = [10, 50, 100, 500];
if (!in_array($perPage, $perPageOptions)) {
    $perPage = 50;
}

// 현재 페이지 (기본값: 1)
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

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

// 최신순 정렬 (created_at 기준 내림차순)
usort($regularUsers, function($a, $b) {
    $dateA = $a['created_at'] ?? '1970-01-01 00:00:00';
    $dateB = $b['created_at'] ?? '1970-01-01 00:00:00';
    return strcmp($dateB, $dateA);
});

usort($sellerUsers, function($a, $b) {
    // 최근 업데이트일 기준 (updated_at 우선, 없으면 approved_at, 없으면 created_at)
    $dateA = $a['updated_at'] ?? $a['approved_at'] ?? $a['created_at'] ?? '1970-01-01 00:00:00';
    $dateB = $b['updated_at'] ?? $b['approved_at'] ?? $b['created_at'] ?? '1970-01-01 00:00:00';
    return strcmp($dateB, $dateA);
});

usort($adminUsers, function($a, $b) {
    $dateA = $a['created_at'] ?? '1970-01-01 00:00:00';
    $dateB = $b['created_at'] ?? '1970-01-01 00:00:00';
    return strcmp($dateB, $dateA);
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
        justify-content: space-between;
    }
    
    .filter-left {
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .filter-right {
        display: flex;
        align-items: center;
        gap: 8px;
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
            <div class="filter-left">
                <div class="filter-group">
                    <label>검색:</label>
                    <input type="text" name="search" placeholder="이름, 이메일, 아이디" value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-sm btn-primary">검색</button>
                    <a href="/MVNO/admin/users/member-list.php?tab=<?php echo htmlspecialchars($activeTab); ?>" class="btn-sm btn-primary">초기화</a>
                </div>
                <div class="filter-group">
                    <label>표시 개수:</label>
                    <select name="per_page" onchange="this.form.submit()">
                        <?php foreach ($perPageOptions as $option): ?>
                            <option value="<?php echo $option; ?>" <?php echo $perPage == $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php if ($activeTab === 'admins'): ?>
                <div class="filter-right">
                    <button type="button" class="btn-sm btn-primary" onclick="showAddSubAdminModal()">관리자 추가</button>
                </div>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- 일반 회원 탭 -->
    <div class="tab-content <?php echo $activeTab === 'users' ? 'active' : ''; ?>">
        <div class="member-table">
            <?php 
            // 일반 회원용 필터링
            $filteredRegularUsers = array_values($regularUsers);
            if (!empty($searchQuery)) {
                $filteredRegularUsers = array_filter($filteredRegularUsers, function($user) use ($searchQuery) {
                    $name = $user['name'] ?? '';
                    $email = $user['email'] ?? '';
                    $userId = $user['user_id'] ?? '';
                    return stripos($name, $searchQuery) !== false || 
                           stripos($email, $searchQuery) !== false ||
                           stripos($userId, $searchQuery) !== false;
                });
                $filteredRegularUsers = array_values($filteredRegularUsers);
            }
            
            // 페이지네이션 계산
            $regularCount = count($filteredRegularUsers);
            $regularPages = ceil($regularCount / $perPage);
            $regularCurrentPage = min($currentPage, max(1, $regularPages));
            $regularOffset = ($regularCurrentPage - 1) * $perPage;
            $regularPaginated = array_slice($filteredRegularUsers, $regularOffset, $perPage);
            ?>
            <?php if (empty($regularPaginated)): ?>
                <div class="no-results">
                    검색 결과가 없습니다.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>번호</th>
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
                        <?php foreach ($regularPaginated as $index => $user): ?>
                            <?php $rowNumber = $regularCount - ($regularOffset + $index); ?>
                            <tr>
                                <td><?php echo $rowNumber; ?></td>
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
                
                <!-- 페이지네이션 -->
                <?php if ($regularPages > 1): ?>
                    <div style="margin-top: 20px; display: flex; justify-content: center; align-items: center; gap: 8px;">
                        <?php if ($regularCurrentPage > 1): ?>
                            <a href="?tab=users&page=<?php echo $regularCurrentPage - 1; ?>&per_page=<?php echo $perPage; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn-sm btn-primary">이전</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $regularPages; $i++): ?>
                            <?php if ($i == 1 || $i == $regularPages || ($i >= $regularCurrentPage - 2 && $i <= $regularCurrentPage + 2)): ?>
                                <a href="?tab=users&page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn-sm btn-primary <?php echo $i == $regularCurrentPage ? 'active' : ''; ?>" style="<?php echo $i == $regularCurrentPage ? 'background: #1e40af;' : ''; ?>"><?php echo $i; ?></a>
                            <?php elseif ($i == $regularCurrentPage - 3 || $i == $regularCurrentPage + 3): ?>
                                <span>...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($regularCurrentPage < $regularPages): ?>
                            <a href="?tab=users&page=<?php echo $regularCurrentPage + 1; ?>&per_page=<?php echo $perPage; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn-sm btn-primary">다음</a>
                        <?php endif; ?>
                    </div>
                    <div style="text-align: center; margin-top: 10px; color: #6b7280; font-size: 14px;">
                        전체 <?php echo number_format($regularCount); ?>개 중 <?php echo number_format($regularOffset + 1); ?>-<?php echo number_format(min($regularOffset + $perPage, $regularCount)); ?>개 표시
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 판매자 탭 -->
    <div class="tab-content <?php echo $activeTab === 'sellers' ? 'active' : ''; ?>">
        <div class="member-table">
            <?php 
            // 판매자용 필터링
            $filteredSellers = array_values($sellerUsers);
            if (!empty($searchQuery)) {
                $filteredSellers = array_filter($filteredSellers, function($user) use ($searchQuery) {
                    $name = $user['name'] ?? '';
                    $email = $user['email'] ?? '';
                    $userId = $user['user_id'] ?? '';
                    return stripos($name, $searchQuery) !== false || 
                           stripos($email, $searchQuery) !== false ||
                           stripos($userId, $searchQuery) !== false;
                });
                $filteredSellers = array_values($filteredSellers);
            }
            
            // 페이지네이션 계산
            $sellerCount = count($filteredSellers);
            $sellerPages = ceil($sellerCount / $perPage);
            $sellerCurrentPage = min($currentPage, max(1, $sellerPages));
            $sellerOffset = ($sellerCurrentPage - 1) * $perPage;
            $sellerPaginated = array_slice($filteredSellers, $sellerOffset, $perPage);
            ?>
            <?php if (empty($sellerPaginated)): ?>
                <div class="no-results">
                    검색 결과가 없습니다.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>번호</th>
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
                        <?php foreach ($sellerPaginated as $index => $user): ?>
                            <?php $rowNumber = $sellerCount - ($sellerOffset + $index); ?>
                            <tr>
                                <td><?php echo $rowNumber; ?></td>
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
                
                <!-- 페이지네이션 -->
                <?php if ($sellerPages > 1): ?>
                    <div style="margin-top: 20px; display: flex; justify-content: center; align-items: center; gap: 8px;">
                        <?php if ($sellerCurrentPage > 1): ?>
                            <a href="?tab=sellers&page=<?php echo $sellerCurrentPage - 1; ?>&per_page=<?php echo $perPage; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn-sm btn-primary">이전</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $sellerPages; $i++): ?>
                            <?php if ($i == 1 || $i == $sellerPages || ($i >= $sellerCurrentPage - 2 && $i <= $sellerCurrentPage + 2)): ?>
                                <a href="?tab=sellers&page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn-sm btn-primary <?php echo $i == $sellerCurrentPage ? 'active' : ''; ?>" style="<?php echo $i == $sellerCurrentPage ? 'background: #1e40af;' : ''; ?>"><?php echo $i; ?></a>
                            <?php elseif ($i == $sellerCurrentPage - 3 || $i == $sellerCurrentPage + 3): ?>
                                <span>...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($sellerCurrentPage < $sellerPages): ?>
                            <a href="?tab=sellers&page=<?php echo $sellerCurrentPage + 1; ?>&per_page=<?php echo $perPage; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn-sm btn-primary">다음</a>
                        <?php endif; ?>
                    </div>
                    <div style="text-align: center; margin-top: 10px; color: #6b7280; font-size: 14px;">
                        전체 <?php echo number_format($sellerCount); ?>개 중 <?php echo number_format($sellerOffset + 1); ?>-<?php echo number_format(min($sellerOffset + $perPage, $sellerCount)); ?>개 표시
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 관리자/부관리자 탭 -->
    <div class="tab-content <?php echo $activeTab === 'admins' ? 'active' : ''; ?>">
        <div class="member-table">
            <?php 
            // 관리자용 필터링
            $filteredAdmins = array_values($adminUsers);
            if (!empty($searchQuery)) {
                $filteredAdmins = array_filter($filteredAdmins, function($user) use ($searchQuery) {
                    $name = $user['name'] ?? '';
                    $email = $user['email'] ?? '';
                    $userId = $user['user_id'] ?? '';
                    return stripos($name, $searchQuery) !== false || 
                           stripos($email, $searchQuery) !== false ||
                           stripos($userId, $searchQuery) !== false;
                });
                $filteredAdmins = array_values($filteredAdmins);
            }
            
            // 페이지네이션 계산
            $adminCount = count($filteredAdmins);
            $adminPages = ceil($adminCount / $perPage);
            $adminCurrentPage = min($currentPage, max(1, $adminPages));
            $adminOffset = ($adminCurrentPage - 1) * $perPage;
            $adminPaginated = array_slice($filteredAdmins, $adminOffset, $perPage);
            ?>
            <?php if (empty($adminPaginated)): ?>
                <div class="no-results">
                    검색 결과가 없습니다.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>번호</th>
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
                        <?php foreach ($adminPaginated as $index => $user): ?>
                            <?php $rowNumber = $adminCount - ($adminOffset + $index); ?>
                            <tr>
                                <td><?php echo $rowNumber; ?></td>
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
                
                <!-- 페이지네이션 -->
                <?php if ($adminPages > 1): ?>
                    <div style="margin-top: 20px; display: flex; justify-content: center; align-items: center; gap: 8px;">
                        <?php if ($adminCurrentPage > 1): ?>
                            <a href="?tab=admins&page=<?php echo $adminCurrentPage - 1; ?>&per_page=<?php echo $perPage; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn-sm btn-primary">이전</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $adminPages; $i++): ?>
                            <?php if ($i == 1 || $i == $adminPages || ($i >= $adminCurrentPage - 2 && $i <= $adminCurrentPage + 2)): ?>
                                <a href="?tab=admins&page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn-sm btn-primary <?php echo $i == $adminCurrentPage ? 'active' : ''; ?>" style="<?php echo $i == $adminCurrentPage ? 'background: #1e40af;' : ''; ?>"><?php echo $i; ?></a>
                            <?php elseif ($i == $adminCurrentPage - 3 || $i == $adminCurrentPage + 3): ?>
                                <span>...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($adminCurrentPage < $adminPages): ?>
                            <a href="?tab=admins&page=<?php echo $adminCurrentPage + 1; ?>&per_page=<?php echo $perPage; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn-sm btn-primary">다음</a>
                        <?php endif; ?>
                    </div>
                    <div style="text-align: center; margin-top: 10px; color: #6b7280; font-size: 14px;">
                        전체 <?php echo number_format($adminCount); ?>개 중 <?php echo number_format($adminOffset + 1); ?>-<?php echo number_format(min($adminOffset + $perPage, $adminCount)); ?>개 표시
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

