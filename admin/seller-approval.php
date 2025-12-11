<?php
/**
 * 판매자 승인 관리 페이지
 */

// POST 처리는 출력 전에 수행 (헤더 리다이렉트를 위해)
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 승인 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_seller'])) {
    $userId = $_POST['user_id'] ?? '';
    $currentTab = $_GET['tab'] ?? 'all';
    $perPage = isset($_GET['per_page']) ? '&per_page=' . (int)$_GET['per_page'] : '';
    if ($userId && approveSeller($userId)) {
        header('Location: /MVNO/admin/seller-approval.php?tab=' . $currentTab . '&success=approve' . $perPage);
        exit;
    } else {
        header('Location: /MVNO/admin/seller-approval.php?tab=' . $currentTab . '&error=approve' . $perPage);
        exit;
    }
}

// 승인보류 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hold_seller'])) {
    $userId = $_POST['user_id'] ?? '';
    $currentTab = $_GET['tab'] ?? 'all';
    $perPage = isset($_GET['per_page']) ? '&per_page=' . (int)$_GET['per_page'] : '';
    if ($userId && holdSeller($userId)) {
        header('Location: /MVNO/admin/seller-approval.php?tab=' . $currentTab . '&success=hold' . $perPage);
        exit;
    } else {
        header('Location: /MVNO/admin/seller-approval.php?tab=' . $currentTab . '&error=hold' . $perPage);
        exit;
    }
}

// 신청 취소(거부) 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_seller'])) {
    $userId = $_POST['user_id'] ?? '';
    $currentTab = $_GET['tab'] ?? 'all';
    $perPage = isset($_GET['per_page']) ? '&per_page=' . (int)$_GET['per_page'] : '';
    if ($userId && rejectSeller($userId)) {
        header('Location: /MVNO/admin/seller-approval.php?tab=' . $currentTab . '&success=reject' . $perPage);
        exit;
    } else {
        header('Location: /MVNO/admin/seller-approval.php?tab=' . $currentTab . '&error=reject' . $perPage);
        exit;
    }
}

// 승인 취소 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_approval'])) {
    $userId = $_POST['user_id'] ?? '';
    $currentTab = $_GET['tab'] ?? 'all';
    $perPage = isset($_GET['per_page']) ? '&per_page=' . (int)$_GET['per_page'] : '';
    if ($userId && cancelSellerApproval($userId)) {
        header('Location: /MVNO/admin/seller-approval.php?tab=' . $currentTab . '&success=cancel_approval' . $perPage);
        exit;
    } else {
        header('Location: /MVNO/admin/seller-approval.php?tab=' . $currentTab . '&error=cancel_approval' . $perPage);
        exit;
    }
}

// 탈퇴 완료 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_withdrawal'])) {
    $userId = $_POST['user_id'] ?? '';
    $deleteDate = $_POST['delete_date'] ?? ''; // 삭제 예정일
    $perPage = isset($_GET['per_page']) ? '&per_page=' . (int)$_GET['per_page'] : '';
    if ($userId && completeSellerWithdrawal($userId, $deleteDate)) {
        header('Location: /MVNO/admin/seller-approval.php?tab=withdrawal&success=complete_withdrawal' . $perPage);
        exit;
    } else {
        header('Location: /MVNO/admin/seller-approval.php?tab=withdrawal&error=complete_withdrawal' . $perPage);
        exit;
    }
}

// 탈퇴 요청 취소 처리 (관리자 권한)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_withdrawal'])) {
    $userId = $_POST['user_id'] ?? '';
    $perPage = isset($_GET['per_page']) ? '&per_page=' . (int)$_GET['per_page'] : '';
    if ($userId && cancelSellerWithdrawal($userId)) {
        header('Location: /MVNO/admin/seller-approval.php?tab=withdrawal&success=cancel_withdrawal' . $perPage);
        exit;
    } else {
        header('Location: /MVNO/admin/seller-approval.php?tab=withdrawal&error=cancel_withdrawal' . $perPage);
        exit;
    }
}

// 가입탈퇴 처리 (관리자 권한)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_withdrawal'])) {
    require_once __DIR__ . '/../includes/data/auth-functions.php';
    $userId = $_POST['user_id'] ?? '';
    $reason = $_POST['reason'] ?? '관리자에 의한 가입탈퇴 처리';
    $perPage = isset($_GET['per_page']) ? '&per_page=' . (int)$_GET['per_page'] : '';
    if ($userId && requestSellerWithdrawal($userId, $reason)) {
        header('Location: /MVNO/admin/seller-approval.php?tab=withdrawal&success=request_withdrawal' . $perPage);
        exit;
    } else {
        $currentTab = $_GET['tab'] ?? 'all';
        header('Location: /MVNO/admin/seller-approval.php?tab=' . $currentTab . '&error=request_withdrawal' . $perPage);
        exit;
    }
}

// 정보 업데이트 확인 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_info_update'])) {
    $userId = $_POST['user_id'] ?? '';
    $currentTab = $_GET['tab'] ?? 'updated';
    $currentPage = isset($_POST['page']) ? max(1, (int)$_POST['page']) : (isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1);
    $perPage = isset($_POST['per_page']) ? (int)$_POST['per_page'] : (isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10);
    
    if ($userId) {
        $file = getSellersFilePath();
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?: ['sellers' => []];
            $updated = false;
            
            foreach ($data['sellers'] as &$u) {
                if ($u['user_id'] === $userId) {
                    $u['info_checked_by_admin'] = true;
                    $u['info_checked_at'] = date('Y-m-d H:i:s');
                    $updated = true;
                    break;
                }
            }
            
            if ($updated) {
                file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                header('Location: /MVNO/admin/seller-approval.php?tab=' . $currentTab . '&page=' . $currentPage . '&per_page=' . $perPage . '&success=check_info_update');
                exit;
            }
        }
    }
    header('Location: /MVNO/admin/seller-approval.php?tab=' . $currentTab . '&page=' . $currentPage . '&per_page=' . $perPage . '&error=check_info_update');
    exit;
}

// 헤더 포함 (출력 시작)
// 주의: processScheduledDeletions()는 admin-header.php에서 호출됨
require_once __DIR__ . '/includes/admin-header.php';

// 성공/에러 메시지 처리
$success_message = '';
$error_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'approve':
            $success_message = '판매자가 승인되었습니다.';
            break;
        case 'hold':
            $success_message = '판매자 승인이 보류되었습니다.';
            break;
        case 'reject':
            $success_message = '판매자 신청이 취소(거부)되었습니다.';
            break;
        case 'cancel_approval':
            $success_message = '판매자 승인이 취소되었습니다.';
            break;
        case 'complete_withdrawal':
            $success_message = '판매자 탈퇴가 완료되었습니다. (개인정보 삭제, 상품/리뷰/주문 데이터는 보존)';
            break;
        case 'cancel_withdrawal':
            $success_message = '탈퇴 요청이 취소되었습니다.';
            break;
        case 'request_withdrawal':
            $success_message = '가입탈퇴 처리가 완료되었습니다.';
            break;
        case 'check_info_update':
            $success_message = '정보 업데이트 확인이 완료되었습니다.';
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
        case 'reject':
            $error_message = '판매자 신청 취소에 실패했습니다.';
            break;
        case 'cancel_approval':
            $error_message = '판매자 승인 취소에 실패했습니다.';
            break;
        case 'complete_withdrawal':
            $error_message = '판매자 탈퇴 처리에 실패했습니다.';
            break;
        case 'cancel_withdrawal':
            $error_message = '탈퇴 요청 취소에 실패했습니다.';
            break;
        case 'request_withdrawal':
            $error_message = '가입탈퇴 처리에 실패했습니다.';
            break;
        case 'check_info_update':
            $error_message = '정보 업데이트 확인에 실패했습니다.';
            break;
    }
}

// 사용자 데이터 읽기
$data = getUsersData();
$sellers = [];
foreach ($data['users'] as $user) {
    if (isset($user['role']) && $user['role'] === 'seller') {
        $sellers[] = $user;
    }
}

// 신청자 (pending 상태 또는 approval_status가 없는 경우 - on_hold 제외)
$pendingSellers = array_filter($sellers, function($seller) {
    $approvalStatus = $seller['approval_status'] ?? null;
    $isApproved = isset($seller['seller_approved']) && $seller['seller_approved'] === true;
    // 승인되지 않았고, approval_status가 없거나 pending인 경우 (on_hold 제외, 탈퇴 요청 제외)
    return !$isApproved && ($approvalStatus === null || $approvalStatus === 'pending') && !isset($seller['withdrawal_requested']);
});

// 승인된 판매자 (on_hold가 아닌 승인된 판매자만, 탈퇴 요청 제외)
$approvedSellers = array_filter($sellers, function($seller) {
    $approvalStatus = $seller['approval_status'] ?? null;
    $isApproved = isset($seller['seller_approved']) && $seller['seller_approved'] === true;
    $hasWithdrawalRequest = isset($seller['withdrawal_requested']) && $seller['withdrawal_requested'] === true;
    // 승인되었고 on_hold가 아니며 탈퇴 요청이 없는 경우만
    return $isApproved && $approvalStatus !== 'on_hold' && !$hasWithdrawalRequest;
});

// 승인불가(거부) 판매자
$rejectedSellers = array_filter($sellers, function($seller) {
    $approvalStatus = $seller['approval_status'] ?? null;
    return $approvalStatus === 'rejected';
});

// 탈퇴 요청한 판매자 (탈퇴 완료 처리되지 않은 경우만)
$withdrawalRequestedSellers = array_filter($sellers, function($seller) {
    $hasWithdrawalRequest = isset($seller['withdrawal_requested']) && $seller['withdrawal_requested'] === true;
    $isCompleted = isset($seller['withdrawal_completed']) && $seller['withdrawal_completed'] === true;
    // 탈퇴 요청이 있고, 아직 완료 처리되지 않은 경우만
    return $hasWithdrawalRequest && !$isCompleted;
});

// 정보 업데이트된 판매자 (관리자 확인 전만 표시)
$updatedSellers = array_filter($sellers, function($seller) {
    return isset($seller['info_updated']) && $seller['info_updated'] === true 
        && (!isset($seller['info_checked_by_admin']) || $seller['info_checked_by_admin'] !== true);
});

// 활성 탭 확인 (기본값: 대시보드)
$activeTab = $_GET['tab'] ?? 'all';

// 검색어 가져오기
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// 표시 개수 선택 (기본값: 10)
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$perPageOptions = [10, 50, 100, 500];
if (!in_array($perPage, $perPageOptions)) {
    $perPage = 10;
}

// 현재 페이지 (기본값: 1)
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// 현재 탭에 따른 데이터 선택
$currentSellers = [];
switch ($activeTab) {
    case 'pending':
        $currentSellers = array_values($pendingSellers);
        break;
    case 'approved':
        $currentSellers = array_values($approvedSellers);
        break;
    case 'withdrawal':
        $currentSellers = array_values($withdrawalRequestedSellers);
        break;
    case 'updated':
        $currentSellers = array_values($updatedSellers);
        break;
    default:
        $currentSellers = array_values($sellers);
        break;
}

// 검색 필터링
if (!empty($searchQuery)) {
    $currentSellers = array_filter($currentSellers, function($seller) use ($searchQuery) {
        $searchLower = mb_strtolower($searchQuery, 'UTF-8');
        
        // 아이디 검색
        if (mb_strpos(mb_strtolower($seller['user_id'] ?? '', 'UTF-8'), $searchLower) !== false) {
            return true;
        }
        
        // 회사명 검색
        if (isset($seller['company_name']) && mb_strpos(mb_strtolower($seller['company_name'], 'UTF-8'), $searchLower) !== false) {
            return true;
        }
        
        // 이메일 검색
        if (isset($seller['email']) && mb_strpos(mb_strtolower($seller['email'], 'UTF-8'), $searchLower) !== false) {
            return true;
        }
        
        // 전화번호 검색 (phone, mobile)
        if (isset($seller['phone']) && mb_strpos(mb_strtolower($seller['phone'], 'UTF-8'), $searchLower) !== false) {
            return true;
        }
        if (isset($seller['mobile']) && mb_strpos(mb_strtolower($seller['mobile'], 'UTF-8'), $searchLower) !== false) {
            return true;
        }
        
        // 대표자명 검색
        if (isset($seller['company_representative']) && mb_strpos(mb_strtolower($seller['company_representative'], 'UTF-8'), $searchLower) !== false) {
            return true;
        }
        
        return false;
    });
    $currentSellers = array_values($currentSellers);
}

// 최신순 정렬 (created_at 기준 내림차순)
usort($currentSellers, function($a, $b) {
    $dateA = $a['created_at'] ?? '1970-01-01 00:00:00';
    $dateB = $b['created_at'] ?? '1970-01-01 00:00:00';
    return strcmp($dateB, $dateA); // 내림차순 (최신이 위로)
});

// 페이지네이션 계산
$totalCount = count($currentSellers);
$totalPages = ceil($totalCount / $perPage);
$currentPage = min($currentPage, max(1, $totalPages)); // 범위 제한
$offset = ($currentPage - 1) * $perPage;
$paginatedSellers = array_slice($currentSellers, $offset, $perPage);
?>

<style>
        h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 24px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        .sellers-section {
            margin-bottom: 32px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        td {
            font-size: 14px;
            color: #1f2937;
        }
        
        .btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .btn-approve {
            background: #10b981;
            color: white;
        }
        
        .btn-approve:hover {
            background: #059669;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-on-hold {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-hold {
            background: #f59e0b;
            color: white;
        }
        
        .btn-hold:hover {
            background: #d97706;
        }
        
        .btn-reject {
            background: #ef4444;
            color: white;
        }
        
        .btn-reject:hover {
            background: #dc2626;
        }
        
        .btn-cancel-approval {
            background: #ef4444;
            color: white;
        }
        
        .btn-cancel-approval:hover {
            background: #dc2626;
        }
        
        /* 탭 스타일 */
        .tabs {
            display: flex;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 24px;
            gap: 0;
        }
        
        .tab {
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            bottom: -2px;
        }
        
        .tab:hover {
            color: #374151;
            background: #f9fafb;
        }
        
        .tab.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .tab-badge {
            display: inline-block;
            margin-left: 8px;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            background: #e5e7eb;
            color: #6b7280;
        }
        
        .tab.active .tab-badge {
            background: #dbeafe;
            color: #3b82f6;
        }
        
        /* 모달 스타일 */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }
        
        .modal-message {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .modal-btn-cancel {
            background: #f3f4f6;
            color: #374151;
        }
        
        .modal-btn-cancel:hover {
            background: #e5e7eb;
        }
        
        .modal-btn-confirm {
            background: #10b981;
            color: white;
        }
        
        .modal-btn-confirm:hover {
            background: #059669;
        }
        
        .modal-btn-hold {
            background: #f59e0b;
            color: white;
        }
        
        .modal-btn-hold:hover {
            background: #d97706;
        }
        
        .detail-info {
            margin-bottom: 16px;
        }
        
        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            width: 120px;
            font-weight: 600;
            color: #6b7280;
            flex-shrink: 0;
        }
        
        .detail-value {
            flex: 1;
            color: #1f2937;
        }
        
        .modal-large {
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .detail-image {
            margin-top: 16px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .detail-image-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .detail-image-preview {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .detail-image-preview:hover {
            transform: scale(1.02);
        }
        
        .image-zoom-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .image-zoom-overlay.active {
            display: flex;
        }
        
        .image-zoom-content {
            max-width: 90%;
            max-height: 90%;
            border-radius: 8px;
        }
        
        /* 표시 개수 선택 및 페이지네이션 */
        .table-controls {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 16px;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
            min-width: auto;
        }
        
        .search-box input {
            flex: 0 0 72%;
            padding: 8px 16px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            color: #1f2937;
            max-width: 576px;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .search-box button {
            padding: 8px 20px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
        }
        
        .search-box button:hover {
            background: #4f46e5;
        }
        
        .per-page-selector {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .per-page-selector label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .per-page-selector select {
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            color: #1f2937;
            cursor: pointer;
        }
        
        .per-page-selector select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .pagination {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .pagination-info {
            font-size: 13px;
            color: #6b7280;
            margin-right: 16px;
            white-space: nowrap;
            flex-shrink: 0;
            min-width: 0;
        }
        
        @media (max-width: 768px) {
            .pagination {
                flex-direction: column;
                gap: 12px;
            }
            
            .pagination-info {
                margin-right: 0;
                margin-bottom: 8px;
            }
        }
        
        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            color: #374151;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            min-height: 36px;
            min-width: 44px;
            line-height: 1.5;
            box-sizing: border-box;
        }
        
        .pagination-btn:hover:not(.disabled) {
            background: #f9fafb;
            border-color: #9ca3af;
        }
        
        .pagination-btn.active {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }
        
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
</style>

<script>
        function switchTab(tabName, reload = true) {
            if (reload) {
                // URL 업데이트 및 페이지 리로드 (페이지네이션을 위해)
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                url.searchParams.set('page', '1'); // 탭 변경 시 페이지를 1로 리셋
                const perPage = url.searchParams.get('per_page') || '50';
                url.searchParams.set('per_page', perPage);
                window.location.href = url.toString();
            } else {
                // 탭만 활성화 (리로드 없이)
                document.querySelectorAll('.tab').forEach(tab => {
                    tab.classList.remove('active');
                });
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                document.getElementById('tab-' + tabName).classList.add('active');
                document.getElementById('content-' + tabName).classList.add('active');
            }
        }
        
        // 표시 개수 변경 함수
        function changePerPage(value) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', value);
            url.searchParams.set('page', '1'); // 페이지를 1로 리셋
            const search = url.searchParams.get('search');
            if (search) {
                url.searchParams.set('search', search);
            }
            window.location.href = url.toString();
        }
        
        // 페이지 로드 시 활성 탭 설정 (리로드 없이)
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'all';
            switchTab(tab, false); // 리로드 없이 탭만 활성화
        });
</script>

<div class="admin-content">
    <h1>판매자 관리</h1>
    
    <!-- 대시보드 버튼 탭 -->
    <div class="dashboard-tabs" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px;">
        <!-- 전체 - 흰색 -->
        <a href="/MVNO/admin/seller-approval.php?tab=all&page=1&per_page=<?php echo $perPage; ?>" class="dashboard-tab-card" style="background: white; border: 2px solid #e5e7eb; border-radius: 12px; padding: 20px; text-decoration: none; display: block; transition: all 0.3s; cursor: pointer;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <div style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.5px;">전체</div>
                <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="9" y1="3" x2="9" y2="21"/>
                        <line x1="15" y1="3" x2="15" y2="21"/>
                        <line x1="3" y1="9" x2="21" y2="9"/>
                        <line x1="3" y1="15" x2="21" y2="15"/>
                    </svg>
                </div>
            </div>
            <div style="font-size: 32px; font-weight: 700; color: #1f2937; margin-bottom: 4px;"><?php echo count($sellers); ?></div>
            <div style="font-size: 12px; color: #6b7280;">전체 판매자</div>
        </a>
        
        <!-- 신청자 -->
        <?php 
        $pendingCount = count($pendingSellers);
        $pendingBg = $pendingCount > 0 ? 'linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%)' : 'white';
        $pendingBorder = $pendingCount > 0 ? '#ec4899' : '#e5e7eb';
        $pendingTextColor = $pendingCount > 0 ? '#9f1239' : '#374151';
        $pendingNumberColor = $pendingCount > 0 ? '#831843' : '#1f2937';
        $pendingIconBg = $pendingCount > 0 ? 'rgba(236, 72, 153, 0.2)' : '#f3f4f6';
        $pendingIconStroke = $pendingCount > 0 ? '#ec4899' : '#6b7280';
        ?>
        <a href="/MVNO/admin/seller-approval.php?tab=pending&page=1&per_page=<?php echo $perPage; ?>" class="dashboard-tab-card" style="background: <?php echo $pendingBg; ?>; border: 2px solid <?php echo $pendingBorder; ?>; border-radius: 12px; padding: 20px; text-decoration: none; display: block; transition: all 0.3s; cursor: pointer;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <div style="font-size: 14px; font-weight: 600; color: <?php echo $pendingTextColor; ?>; text-transform: uppercase; letter-spacing: 0.5px;">신청자</div>
                <div style="width: 40px; height: 40px; background: <?php echo $pendingIconBg; ?>; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="<?php echo $pendingIconStroke; ?>" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
            </div>
            <div style="font-size: 32px; font-weight: 700; color: <?php echo $pendingNumberColor; ?>; margin-bottom: 4px;"><?php echo $pendingCount; ?></div>
            <div style="font-size: 12px; color: #6b7280;">승인 대기 중</div>
        </a>
        
        <!-- 승인판매자 - 흰색 -->
        <a href="/MVNO/admin/seller-approval.php?tab=approved&page=1&per_page=<?php echo $perPage; ?>" class="dashboard-tab-card" style="background: white; border: 2px solid #e5e7eb; border-radius: 12px; padding: 20px; text-decoration: none; display: block; transition: all 0.3s; cursor: pointer;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <div style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.5px;">승인판매자</div>
                <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
            </div>
            <div style="font-size: 32px; font-weight: 700; color: #1f2937; margin-bottom: 4px;"><?php echo count($approvedSellers); ?></div>
            <div style="font-size: 12px; color: #6b7280;">승인 완료</div>
        </a>
        
        <!-- 업데이트 -->
        <?php 
        $updatedCount = count($updatedSellers);
        $updatedBg = $updatedCount > 0 ? 'linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%)' : 'white';
        $updatedBorder = $updatedCount > 0 ? '#ec4899' : '#e5e7eb';
        $updatedTextColor = $updatedCount > 0 ? '#9f1239' : '#374151';
        $updatedNumberColor = $updatedCount > 0 ? '#831843' : '#1f2937';
        $updatedIconBg = $updatedCount > 0 ? 'rgba(236, 72, 153, 0.2)' : '#f3f4f6';
        $updatedIconStroke = $updatedCount > 0 ? '#ec4899' : '#6b7280';
        ?>
        <a href="/MVNO/admin/seller-approval.php?tab=updated&page=1&per_page=<?php echo $perPage; ?>" class="dashboard-tab-card" style="background: <?php echo $updatedBg; ?>; border: 2px solid <?php echo $updatedBorder; ?>; border-radius: 12px; padding: 20px; text-decoration: none; display: block; transition: all 0.3s; cursor: pointer;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <div style="font-size: 14px; font-weight: 600; color: <?php echo $updatedTextColor; ?>; text-transform: uppercase; letter-spacing: 0.5px;">업데이트</div>
                <div style="width: 40px; height: 40px; background: <?php echo $updatedIconBg; ?>; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="<?php echo $updatedIconStroke; ?>" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </div>
            </div>
            <div style="font-size: 32px; font-weight: 700; color: <?php echo $updatedNumberColor; ?>; margin-bottom: 4px;"><?php echo $updatedCount; ?></div>
            <div style="font-size: 12px; color: #6b7280;">정보 수정 대기</div>
        </a>
        
        <!-- 탈퇴요청 -->
        <?php 
        $withdrawalCount = count($withdrawalRequestedSellers);
        $withdrawalBg = $withdrawalCount > 0 ? 'linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%)' : 'white';
        $withdrawalBorder = $withdrawalCount > 0 ? '#ec4899' : '#e5e7eb';
        $withdrawalTextColor = $withdrawalCount > 0 ? '#9f1239' : '#374151';
        $withdrawalNumberColor = $withdrawalCount > 0 ? '#831843' : '#1f2937';
        $withdrawalIconBg = $withdrawalCount > 0 ? 'rgba(236, 72, 153, 0.2)' : '#f3f4f6';
        $withdrawalIconStroke = $withdrawalCount > 0 ? '#ec4899' : '#6b7280';
        ?>
        <a href="/MVNO/admin/seller-approval.php?tab=withdrawal&page=1&per_page=<?php echo $perPage; ?>" class="dashboard-tab-card" style="background: <?php echo $withdrawalBg; ?>; border: 2px solid <?php echo $withdrawalBorder; ?>; border-radius: 12px; padding: 20px; text-decoration: none; display: block; transition: all 0.3s; cursor: pointer;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <div style="font-size: 14px; font-weight: 600; color: <?php echo $withdrawalTextColor; ?>; text-transform: uppercase; letter-spacing: 0.5px;">탈퇴요청</div>
                <div style="width: 40px; height: 40px; background: <?php echo $withdrawalIconBg; ?>; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="<?php echo $withdrawalIconStroke; ?>" stroke-width="2">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </div>
            </div>
            <div style="font-size: 32px; font-weight: 700; color: <?php echo $withdrawalNumberColor; ?>; margin-bottom: 4px;"><?php echo $withdrawalCount; ?></div>
            <div style="font-size: 12px; color: #6b7280;">탈퇴 처리 대기</div>
        </a>
    </div>
    
    <style>
        .dashboard-tab-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
        
        @media (max-width: 1200px) {
            .dashboard-tabs {
                grid-template-columns: repeat(3, 1fr) !important;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-tabs {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
        
        <?php if (isset($success_message) && !empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message) && !empty($error_message)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- 검색 및 표시 개수 선택 -->
        <div class="table-controls" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; gap: 16px; flex-wrap: wrap;">
            <form method="GET" class="search-box" style="margin: 0; flex: 1; min-width: 300px;">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($activeTab); ?>">
                <input type="hidden" name="per_page" value="<?php echo $perPage; ?>">
                <input type="text" name="search" placeholder="아이디, 회사명, 이메일, 전화번호, 대표자명으로 검색..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">검색</button>
                <?php if (!empty($searchQuery)): ?>
                    <a href="?tab=<?php echo htmlspecialchars($activeTab); ?>&per_page=<?php echo $perPage; ?>" style="padding: 8px 16px; background: #ef4444; color: white; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500;">초기화</a>
                <?php endif; ?>
            </form>
            <div class="per-page-selector" style="margin-left: auto;">
                <label for="per-page">표시 개수:</label>
                <select id="per-page" onchange="changePerPage(this.value)">
                    <?php foreach ($perPageOptions as $option): ?>
                        <option value="<?php echo $option; ?>" <?php echo $perPage === $option ? 'selected' : ''; ?>>
                            <?php echo $option; ?>명
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- 탭 메뉴 -->
        <div class="tabs">
            <button class="tab" id="tab-all" onclick="switchTab('all')">
                대시보드
                <span class="tab-badge"><?php echo count($sellers); ?></span>
            </button>
            <button class="tab" id="tab-pending" onclick="switchTab('pending')">
                신청자
                <span class="tab-badge"><?php echo count($pendingSellers); ?></span>
            </button>
            <button class="tab" id="tab-approved" onclick="switchTab('approved')">
                승인판매자
                <span class="tab-badge"><?php echo count($approvedSellers); ?></span>
            </button>
            <button class="tab" id="tab-updated" onclick="switchTab('updated')">
                업데이트
                <span class="tab-badge"><?php echo count($updatedSellers); ?></span>
            </button>
            <button class="tab" id="tab-withdrawal" onclick="switchTab('withdrawal')">
                탈퇴요청
                <span class="tab-badge"><?php echo count($withdrawalRequestedSellers); ?></span>
            </button>
        </div>
        
        <!-- 대시보드 탭 -->
        <div class="tab-content" id="content-all">
            <div class="sellers-section">
                <?php if ($totalCount > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>번호</th>
                                <th>아이디</th>
                                <th>판매자명</th>
                                <th>회사명</th>
                                <th>대표자명</th>
                                <th>연락처</th>
                                <th>상태</th>
                                <th>권한</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paginatedSellers as $index => $seller): ?>
                                <?php $rowNumber = $totalCount - ($offset + $index); ?>
                                <?php
                                $approvalStatus = $seller['approval_status'] ?? null;
                                $isApproved = isset($seller['seller_approved']) && $seller['seller_approved'] === true;
                                $isWithdrawalRequested = isset($seller['withdrawal_requested']) && $seller['withdrawal_requested'] === true;
                                
                                // 상태 결정 (승인, 승인보류만 표시)
                                $statusBadge = '';
                                $statusText = '';
                                if ($isWithdrawalRequested) {
                                    $statusBadge = 'badge-on-hold';
                                    $statusText = '탈퇴 요청';
                                } elseif ($isApproved && $approvalStatus !== 'on_hold') {
                                    $statusBadge = 'badge-approved';
                                    $statusText = '승인';
                                } elseif ($approvalStatus === 'on_hold') {
                                    // 승인보류 상태
                                    $statusBadge = 'badge-on-hold';
                                    $statusText = '승인보류';
                                } else {
                                    // pending 상태 (신청자)
                                    $statusBadge = 'badge-pending';
                                    $statusText = '신청자';
                                }
                                
                                // 권한 정보 표시
                                $permissions = $seller['permissions'] ?? [];
                                $permissionLabels = [];
                                if (in_array('mvno', $permissions)) $permissionLabels[] = '알뜰폰';
                                if (in_array('mno', $permissions)) $permissionLabels[] = '통신사폰';
                                if (in_array('internet', $permissions)) $permissionLabels[] = '인터넷';
                                
                                // 판매자명
                                $sellerName = $seller['seller_name'] ?? $seller['company_name'] ?? $seller['name'] ?? '-';
                                
                                // 연락처 (휴대폰 우선, 없으면 전화번호)
                                $contact = '';
                                if (!empty($seller['mobile'])) {
                                    $contact = $seller['mobile'];
                                } elseif (!empty($seller['phone'])) {
                                    $contact = $seller['phone'];
                                } else {
                                    $contact = '-';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $rowNumber; ?></td>
                                    <td><?php echo htmlspecialchars($seller['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($sellerName); ?></td>
                                    <td><?php echo htmlspecialchars($seller['company_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['company_representative'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($contact); ?></td>
                                    <td>
                                        <span class="badge <?php echo $statusBadge; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($permissionLabels)): ?>
                                            <span style="font-size: 12px; color: #1f2937;"><?php echo implode(', ', $permissionLabels); ?></span>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: #9ca3af;">권한 없음</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>" class="btn" style="background: #6366f1; color: white; text-decoration: none; display: inline-block;">상세보기</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #6b7280; font-size: 14px; padding: 40px; text-align: center;">
                        <?php if (!empty($searchQuery)): ?>
                            검색 결과가 없습니다. 다른 검색어를 입력해주세요.
                        <?php else: ?>
                            등록된 판매자가 없습니다.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                
                <!-- 페이지네이션 -->
                <?php if ($totalCount > 0 && $totalPages > 1): ?>
                    <div class="pagination">
                        <span class="pagination-info">
                            전체 <?php echo $totalCount; ?>명 중 <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalCount); ?>명 표시
                        </span>
                        <?php 
                        $searchParam = !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '';
                        ?>
                        <?php if ($currentPage > 1): ?>
                            <a href="?tab=<?php echo $activeTab; ?>&page=<?php echo $currentPage - 1; ?>&per_page=<?php echo $perPage; ?><?php echo $searchParam; ?>" class="pagination-btn">이전</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">이전</span>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        if ($startPage > 1): ?>
                            <a href="?tab=<?php echo $activeTab; ?>&page=1&per_page=<?php echo $perPage; ?><?php echo $searchParam; ?>" class="pagination-btn">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="pagination-btn disabled">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?tab=<?php echo $activeTab; ?>&page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?><?php echo $searchParam; ?>" class="pagination-btn <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <span class="pagination-btn disabled">...</span>
                            <?php endif; ?>
                            <a href="?tab=<?php echo $activeTab; ?>&page=<?php echo $totalPages; ?>&per_page=<?php echo $perPage; ?><?php echo $searchParam; ?>" class="pagination-btn"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?tab=<?php echo $activeTab; ?>&page=<?php echo $currentPage + 1; ?>&per_page=<?php echo $perPage; ?><?php echo $searchParam; ?>" class="pagination-btn">다음</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">다음</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 신청자 탭 -->
        <div class="tab-content" id="content-pending">
            <div class="sellers-section">
                <?php 
                // 신청자 탭용 검색 필터링
                $pendingFiltered = array_values($pendingSellers);
                if (!empty($searchQuery)) {
                    $pendingFiltered = array_filter($pendingFiltered, function($seller) use ($searchQuery) {
                        $searchLower = mb_strtolower($searchQuery, 'UTF-8');
                        if (mb_strpos(mb_strtolower($seller['user_id'] ?? '', 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['company_name']) && mb_strpos(mb_strtolower($seller['company_name'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['email']) && mb_strpos(mb_strtolower($seller['email'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['phone']) && mb_strpos(mb_strtolower($seller['phone'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['mobile']) && mb_strpos(mb_strtolower($seller['mobile'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['company_representative']) && mb_strpos(mb_strtolower($seller['company_representative'], 'UTF-8'), $searchLower) !== false) return true;
                        return false;
                    });
                    $pendingFiltered = array_values($pendingFiltered);
                }
                // 최근 업데이트일 기준 정렬 (updated_at 우선, 없으면 created_at)
                usort($pendingFiltered, function($a, $b) {
                    $dateA = $a['updated_at'] ?? $a['created_at'] ?? '1970-01-01 00:00:00';
                    $dateB = $b['updated_at'] ?? $b['created_at'] ?? '1970-01-01 00:00:00';
                    return strcmp($dateB, $dateA);
                });
                
                // 신청자 탭용 페이지네이션 재계산
                $pendingCount = count($pendingFiltered);
                $pendingPages = ceil($pendingCount / $perPage);
                $pendingCurrentPage = min($currentPage, max(1, $pendingPages));
                $pendingOffset = ($pendingCurrentPage - 1) * $perPage;
                $pendingPaginated = array_slice($pendingFiltered, $pendingOffset, $perPage);
                ?>
                <?php if ($pendingCount > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>번호</th>
                                <th>아이디</th>
                                <th>판매자명</th>
                                <th>회사명</th>
                                <th>대표자명</th>
                                <th>연락처</th>
                                <th>상태</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingPaginated as $index => $seller): ?>
                                <?php $rowNumber = $pendingCount - ($pendingOffset + $index); ?>
                                <?php
                                // 판매자명
                                $sellerName = $seller['seller_name'] ?? $seller['company_name'] ?? $seller['name'] ?? '-';
                                
                                // 연락처 (휴대폰 우선, 없으면 전화번호)
                                $contact = '';
                                if (!empty($seller['mobile'])) {
                                    $contact = $seller['mobile'];
                                } elseif (!empty($seller['phone'])) {
                                    $contact = $seller['phone'];
                                } else {
                                    $contact = '-';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $rowNumber; ?></td>
                                    <td><?php echo htmlspecialchars($seller['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($sellerName); ?></td>
                                    <td><?php echo htmlspecialchars($seller['company_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['company_representative'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($contact); ?></td>
                                    <td>
                                        <span class="badge badge-pending">신청자</span>
                                    </td>
                                    <td>
                                        <a href="/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>" class="btn" style="background: #6366f1; color: white; text-decoration: none; display: inline-block;">상세보기</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #6b7280; font-size: 14px; padding: 40px; text-align: center;">
                        <?php if (!empty($searchQuery)): ?>
                            검색 결과가 없습니다. 다른 검색어를 입력해주세요.
                        <?php else: ?>
                            승인 대기 중인 판매자가 없습니다.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                
                <!-- 페이지네이션 -->
                <?php if ($pendingCount > 0 && $pendingPages > 1): ?>
                    <div class="pagination">
                        <span class="pagination-info">
                            전체 <?php echo $pendingCount; ?>명 중 <?php echo $pendingOffset + 1; ?>-<?php echo min($pendingOffset + $perPage, $pendingCount); ?>명 표시
                        </span>
                        <?php 
                        $pendingSearchParam = !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '';
                        ?>
                        <?php if ($pendingCurrentPage > 1): ?>
                            <a href="?tab=pending&page=<?php echo $pendingCurrentPage - 1; ?>&per_page=<?php echo $perPage; ?><?php echo $pendingSearchParam; ?>" class="pagination-btn">이전</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">이전</span>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $pendingCurrentPage - 2);
                        $endPage = min($pendingPages, $pendingCurrentPage + 2);
                        
                        if ($startPage > 1): ?>
                            <a href="?tab=pending&page=1&per_page=<?php echo $perPage; ?><?php echo $pendingSearchParam; ?>" class="pagination-btn">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="pagination-btn disabled">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?tab=pending&page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?><?php echo $pendingSearchParam; ?>" class="pagination-btn <?php echo $i === $pendingCurrentPage ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $pendingPages): ?>
                            <?php if ($endPage < $pendingPages - 1): ?>
                                <span class="pagination-btn disabled">...</span>
                            <?php endif; ?>
                            <a href="?tab=pending&page=<?php echo $pendingPages; ?>&per_page=<?php echo $perPage; ?><?php echo $pendingSearchParam; ?>" class="pagination-btn"><?php echo $pendingPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($pendingCurrentPage < $pendingPages): ?>
                            <a href="?tab=pending&page=<?php echo $pendingCurrentPage + 1; ?>&per_page=<?php echo $perPage; ?><?php echo $pendingSearchParam; ?>" class="pagination-btn">다음</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">다음</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 승인판매자 탭 -->
        <div class="tab-content" id="content-approved">
            <div class="sellers-section">
                <?php 
                // 승인판매자 탭용 검색 필터링
                $approvedFiltered = array_values($approvedSellers);
                if (!empty($searchQuery)) {
                    $approvedFiltered = array_filter($approvedFiltered, function($seller) use ($searchQuery) {
                        $searchLower = mb_strtolower($searchQuery, 'UTF-8');
                        if (mb_strpos(mb_strtolower($seller['user_id'] ?? '', 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['company_name']) && mb_strpos(mb_strtolower($seller['company_name'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['email']) && mb_strpos(mb_strtolower($seller['email'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['phone']) && mb_strpos(mb_strtolower($seller['phone'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['mobile']) && mb_strpos(mb_strtolower($seller['mobile'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['company_representative']) && mb_strpos(mb_strtolower($seller['company_representative'], 'UTF-8'), $searchLower) !== false) return true;
                        return false;
                    });
                    $approvedFiltered = array_values($approvedFiltered);
                }
                // 최근 승인일 기준 정렬 (최근 업데이트 순)
                usort($approvedFiltered, function($a, $b) {
                    $dateA = $a['approved_at'] ?? '1970-01-01 00:00:00';
                    $dateB = $b['approved_at'] ?? '1970-01-01 00:00:00';
                    return strcmp($dateB, $dateA);
                });
                
                // 승인판매자 탭용 페이지네이션 재계산
                $approvedCount = count($approvedFiltered);
                $approvedPages = ceil($approvedCount / $perPage);
                $approvedCurrentPage = min($currentPage, max(1, $approvedPages));
                $approvedOffset = ($approvedCurrentPage - 1) * $perPage;
                $approvedPaginated = array_slice($approvedFiltered, $approvedOffset, $perPage);
                ?>
                <?php if ($approvedCount > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>번호</th>
                                <th>아이디</th>
                                <th>판매자명</th>
                                <th>회사명</th>
                                <th>대표자명</th>
                                <th>연락처</th>
                                <th>권한</th>
                                <th>상태</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($approvedPaginated as $index => $seller): ?>
                                <?php $rowNumber = $approvedCount - ($approvedOffset + $index); ?>
                                <?php
                                // 판매자명
                                $sellerName = $seller['seller_name'] ?? $seller['company_name'] ?? $seller['name'] ?? '-';
                                
                                // 연락처 (휴대폰 우선, 없으면 전화번호)
                                $contact = '';
                                if (!empty($seller['mobile'])) {
                                    $contact = $seller['mobile'];
                                } elseif (!empty($seller['phone'])) {
                                    $contact = $seller['phone'];
                                } else {
                                    $contact = '-';
                                }
                                
                                $permissions = $seller['permissions'] ?? [];
                                $permissionLabels = [];
                                if (in_array('mvno', $permissions)) $permissionLabels[] = '알뜰폰';
                                if (in_array('mno', $permissions)) $permissionLabels[] = '통신사폰';
                                if (in_array('internet', $permissions)) $permissionLabels[] = '인터넷';
                                ?>
                                <tr>
                                    <td><?php echo $rowNumber; ?></td>
                                    <td><?php echo htmlspecialchars($seller['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($sellerName); ?></td>
                                    <td><?php echo htmlspecialchars($seller['company_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['company_representative'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($contact); ?></td>
                                    <td>
                                        <?php echo !empty($permissionLabels) ? implode(', ', $permissionLabels) : '<span style="color: #9ca3af;">권한 없음</span>'; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-approved">승인</span>
                                    </td>
                                    <td>
                                        <a href="/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>" class="btn" style="background: #6366f1; color: white; text-decoration: none; display: inline-block;">상세보기</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #6b7280; font-size: 14px; padding: 40px; text-align: center;">
                        <?php if (!empty($searchQuery)): ?>
                            검색 결과가 없습니다. 다른 검색어를 입력해주세요.
                        <?php else: ?>
                            승인된 판매자가 없습니다.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                
                <!-- 페이지네이션 -->
                <?php if ($approvedCount > 0 && $approvedPages > 1): ?>
                    <div class="pagination">
                        <span class="pagination-info">
                            전체 <?php echo $approvedCount; ?>명 중 <?php echo $approvedOffset + 1; ?>-<?php echo min($approvedOffset + $perPage, $approvedCount); ?>명 표시
                        </span>
                        <?php 
                        $approvedSearchParam = !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '';
                        ?>
                        <?php if ($approvedCurrentPage > 1): ?>
                            <a href="?tab=approved&page=<?php echo $approvedCurrentPage - 1; ?>&per_page=<?php echo $perPage; ?><?php echo $approvedSearchParam; ?>" class="pagination-btn">이전</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">이전</span>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $approvedCurrentPage - 2);
                        $endPage = min($approvedPages, $approvedCurrentPage + 2);
                        
                        if ($startPage > 1): ?>
                            <a href="?tab=approved&page=1&per_page=<?php echo $perPage; ?><?php echo $approvedSearchParam; ?>" class="pagination-btn">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="pagination-btn disabled">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?tab=approved&page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?><?php echo $approvedSearchParam; ?>" class="pagination-btn <?php echo $i === $approvedCurrentPage ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $approvedPages): ?>
                            <?php if ($endPage < $approvedPages - 1): ?>
                                <span class="pagination-btn disabled">...</span>
                            <?php endif; ?>
                            <a href="?tab=approved&page=<?php echo $approvedPages; ?>&per_page=<?php echo $perPage; ?><?php echo $approvedSearchParam; ?>" class="pagination-btn"><?php echo $approvedPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($approvedCurrentPage < $approvedPages): ?>
                            <a href="?tab=approved&page=<?php echo $approvedCurrentPage + 1; ?>&per_page=<?php echo $perPage; ?><?php echo $approvedSearchParam; ?>" class="pagination-btn">다음</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">다음</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 업데이트 탭 -->
        <div class="tab-content" id="content-updated">
            <div class="sellers-section">
                <?php 
                // 업데이트 탭용 검색 필터링
                $updatedFiltered = array_values($updatedSellers);
                if (!empty($searchQuery)) {
                    $updatedFiltered = array_filter($updatedFiltered, function($seller) use ($searchQuery) {
                        $searchLower = mb_strtolower($searchQuery, 'UTF-8');
                        if (mb_strpos(mb_strtolower($seller['user_id'] ?? '', 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['company_name']) && mb_strpos(mb_strtolower($seller['company_name'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['email']) && mb_strpos(mb_strtolower($seller['email'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['phone']) && mb_strpos(mb_strtolower($seller['phone'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['mobile']) && mb_strpos(mb_strtolower($seller['mobile'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['company_representative']) && mb_strpos(mb_strtolower($seller['company_representative'], 'UTF-8'), $searchLower) !== false) return true;
                        return false;
                    });
                    $updatedFiltered = array_values($updatedFiltered);
                }
                // 최신 업데이트순 정렬
                usort($updatedFiltered, function($a, $b) {
                    $dateA = $a['info_updated_at'] ?? '1970-01-01 00:00:00';
                    $dateB = $b['info_updated_at'] ?? '1970-01-01 00:00:00';
                    return strcmp($dateB, $dateA);
                });
                
                // 업데이트 탭용 페이지네이션 재계산
                $updatedCount = count($updatedFiltered);
                $updatedPages = ceil($updatedCount / $perPage);
                $updatedCurrentPage = min($currentPage, max(1, $updatedPages));
                $updatedOffset = ($updatedCurrentPage - 1) * $perPage;
                $updatedPaginated = array_slice($updatedFiltered, $updatedOffset, $perPage);
                ?>
                <?php if ($updatedCount > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>번호</th>
                                <th>아이디</th>
                                <th>판매자명</th>
                                <th>회사명</th>
                                <th>대표자명</th>
                                <th>연락처</th>
                                <th>업데이트일</th>
                                <th>상태</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($updatedPaginated as $index => $seller): ?>
                                <?php $rowNumber = $updatedCount - ($updatedOffset + $index); ?>
                                <?php
                                // 판매자명
                                $sellerName = $seller['seller_name'] ?? $seller['company_name'] ?? $seller['name'] ?? '-';
                                
                                // 연락처 (휴대폰 우선, 없으면 전화번호)
                                $contact = '';
                                if (!empty($seller['mobile'])) {
                                    $contact = $seller['mobile'];
                                } elseif (!empty($seller['phone'])) {
                                    $contact = $seller['phone'];
                                } else {
                                    $contact = '-';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $rowNumber; ?></td>
                                    <td><?php echo htmlspecialchars($seller['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($sellerName); ?></td>
                                    <td><?php echo htmlspecialchars($seller['company_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['company_representative'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($contact); ?></td>
                                    <td><?php echo htmlspecialchars($seller['info_updated_at'] ?? '-'); ?></td>
                                    <td>
                                        <?php if (isset($seller['info_checked_by_admin']) && $seller['info_checked_by_admin'] === true): ?>
                                            <span class="badge badge-approved">확인 완료</span>
                                        <?php else: ?>
                                            <span class="badge badge-pending">정보 업데이트</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px; align-items: center;">
                                            <a href="/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>" class="btn" style="background: #6366f1; color: white; text-decoration: none; display: inline-block; padding: 6px 12px; font-size: 13px;">상세보기</a>
                                            <?php if (!isset($seller['info_checked_by_admin']) || $seller['info_checked_by_admin'] !== true): ?>
                                                <button type="button" onclick="showCheckInfoUpdateModal('<?php echo htmlspecialchars($seller['user_id']); ?>', '<?php echo htmlspecialchars($seller['seller_name'] ?? $seller['name'] ?? $seller['user_id']); ?>')" class="btn" style="background: #10b981; color: white; border: none; padding: 6px 12px; font-size: 13px; cursor: pointer;">확인</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #6b7280; font-size: 14px; padding: 40px; text-align: center;">
                        <?php if (!empty($searchQuery)): ?>
                            검색 결과가 없습니다. 다른 검색어를 입력해주세요.
                        <?php else: ?>
                            정보가 업데이트된 판매자가 없습니다.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                
                <!-- 페이지네이션 -->
                <?php if ($updatedCount > 0 && $updatedPages > 1): ?>
                    <div class="pagination">
                        <span class="pagination-info">
                            전체 <?php echo $updatedCount; ?>명 중 <?php echo $updatedOffset + 1; ?>-<?php echo min($updatedOffset + $perPage, $updatedCount); ?>명 표시
                        </span>
                        <?php 
                        $updatedSearchParam = !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '';
                        ?>
                        <?php if ($updatedCurrentPage > 1): ?>
                            <a href="?tab=updated&page=<?php echo $updatedCurrentPage - 1; ?>&per_page=<?php echo $perPage; ?><?php echo $updatedSearchParam; ?>" class="pagination-btn">이전</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">이전</span>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $updatedCurrentPage - 2);
                        $endPage = min($updatedPages, $updatedCurrentPage + 2);
                        
                        if ($startPage > 1): ?>
                            <a href="?tab=updated&page=1&per_page=<?php echo $perPage; ?><?php echo $updatedSearchParam; ?>" class="pagination-btn">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="pagination-btn disabled">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?tab=updated&page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?><?php echo $updatedSearchParam; ?>" class="pagination-btn <?php echo $i === $updatedCurrentPage ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $updatedPages): ?>
                            <?php if ($endPage < $updatedPages - 1): ?>
                                <span class="pagination-btn disabled">...</span>
                            <?php endif; ?>
                            <a href="?tab=updated&page=<?php echo $updatedPages; ?>&per_page=<?php echo $perPage; ?><?php echo $updatedSearchParam; ?>" class="pagination-btn"><?php echo $updatedPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($updatedCurrentPage < $updatedPages): ?>
                            <a href="?tab=updated&page=<?php echo $updatedCurrentPage + 1; ?>&per_page=<?php echo $perPage; ?><?php echo $updatedSearchParam; ?>" class="pagination-btn">다음</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">다음</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 탈퇴진행 탭 -->
        <div class="tab-content" id="content-withdrawal">
            <div class="sellers-section">
                <?php 
                // 탈퇴진행 탭용 검색 필터링
                $withdrawalFiltered = array_values($withdrawalRequestedSellers);
                if (!empty($searchQuery)) {
                    $withdrawalFiltered = array_filter($withdrawalFiltered, function($seller) use ($searchQuery) {
                        $searchLower = mb_strtolower($searchQuery, 'UTF-8');
                        if (mb_strpos(mb_strtolower($seller['user_id'] ?? '', 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['company_name']) && mb_strpos(mb_strtolower($seller['company_name'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['email']) && mb_strpos(mb_strtolower($seller['email'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['phone']) && mb_strpos(mb_strtolower($seller['phone'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['mobile']) && mb_strpos(mb_strtolower($seller['mobile'], 'UTF-8'), $searchLower) !== false) return true;
                        if (isset($seller['company_representative']) && mb_strpos(mb_strtolower($seller['company_representative'], 'UTF-8'), $searchLower) !== false) return true;
                        return false;
                    });
                    $withdrawalFiltered = array_values($withdrawalFiltered);
                }
                // 최근 탈퇴 요청일 기준 정렬 (withdrawal_requested_at 우선, 없으면 updated_at)
                usort($withdrawalFiltered, function($a, $b) {
                    $dateA = $a['withdrawal_requested_at'] ?? $a['updated_at'] ?? '1970-01-01 00:00:00';
                    $dateB = $b['withdrawal_requested_at'] ?? $b['updated_at'] ?? '1970-01-01 00:00:00';
                    return strcmp($dateB, $dateA);
                });
                
                // 탈퇴진행 탭용 페이지네이션 재계산
                $withdrawalCount = count($withdrawalFiltered);
                $withdrawalPages = ceil($withdrawalCount / $perPage);
                $withdrawalCurrentPage = min($currentPage, max(1, $withdrawalPages));
                $withdrawalOffset = ($withdrawalCurrentPage - 1) * $perPage;
                $withdrawalPaginated = array_slice($withdrawalFiltered, $withdrawalOffset, $perPage);
                ?>
                <?php if ($withdrawalCount > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>번호</th>
                                <th>아이디</th>
                                <th>판매자명</th>
                                <th>회사명</th>
                                <th>대표자명</th>
                                <th>연락처</th>
                                <th>탈퇴 요청일</th>
                                <th>탈퇴 사유</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawalPaginated as $index => $seller): ?>
                                <?php $rowNumber = $withdrawalCount - ($withdrawalOffset + $index); ?>
                                <?php
                                // 판매자명
                                $sellerName = $seller['seller_name'] ?? $seller['company_name'] ?? $seller['name'] ?? '-';
                                
                                // 연락처 (휴대폰 우선, 없으면 전화번호)
                                $contact = '';
                                if (!empty($seller['mobile'])) {
                                    $contact = $seller['mobile'];
                                } elseif (!empty($seller['phone'])) {
                                    $contact = $seller['phone'];
                                } else {
                                    $contact = '-';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $rowNumber; ?></td>
                                    <td><?php echo htmlspecialchars($seller['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($sellerName); ?></td>
                                    <td><?php echo htmlspecialchars($seller['company_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['company_representative'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($contact); ?></td>
                                    <td><?php echo htmlspecialchars($seller['withdrawal_requested_at'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['withdrawal_reason'] ?? '사유 없음'); ?></td>
                                    <td>
                                        <a href="/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>" class="btn" style="background: #6366f1; color: white; text-decoration: none; display: inline-block;">상세보기</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #6b7280; font-size: 14px; padding: 40px; text-align: center;">
                        <?php if (!empty($searchQuery)): ?>
                            검색 결과가 없습니다. 다른 검색어를 입력해주세요.
                        <?php else: ?>
                            탈퇴 요청이 없습니다.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                
                <!-- 페이지네이션 -->
                <?php if ($withdrawalCount > 0 && $withdrawalPages > 1): ?>
                    <div class="pagination">
                        <span class="pagination-info">
                            전체 <?php echo $withdrawalCount; ?>명 중 <?php echo $withdrawalOffset + 1; ?>-<?php echo min($withdrawalOffset + $perPage, $withdrawalCount); ?>명 표시
                        </span>
                        <?php 
                        $withdrawalSearchParam = !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '';
                        ?>
                        <?php if ($withdrawalCurrentPage > 1): ?>
                            <a href="?tab=withdrawal&page=<?php echo $withdrawalCurrentPage - 1; ?>&per_page=<?php echo $perPage; ?><?php echo $withdrawalSearchParam; ?>" class="pagination-btn">이전</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">이전</span>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $withdrawalCurrentPage - 2);
                        $endPage = min($withdrawalPages, $withdrawalCurrentPage + 2);
                        
                        if ($startPage > 1): ?>
                            <a href="?tab=withdrawal&page=1&per_page=<?php echo $perPage; ?><?php echo $withdrawalSearchParam; ?>" class="pagination-btn">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="pagination-btn disabled">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?tab=withdrawal&page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?><?php echo $withdrawalSearchParam; ?>" class="pagination-btn <?php echo $i === $withdrawalCurrentPage ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $withdrawalPages): ?>
                            <?php if ($endPage < $withdrawalPages - 1): ?>
                                <span class="pagination-btn disabled">...</span>
                            <?php endif; ?>
                            <a href="?tab=withdrawal&page=<?php echo $withdrawalPages; ?>&per_page=<?php echo $perPage; ?><?php echo $withdrawalSearchParam; ?>" class="pagination-btn"><?php echo $withdrawalPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($withdrawalCurrentPage < $withdrawalPages): ?>
                            <a href="?tab=withdrawal&page=<?php echo $withdrawalCurrentPage + 1; ?>&per_page=<?php echo $perPage; ?><?php echo $withdrawalSearchParam; ?>" class="pagination-btn">다음</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">다음</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
</div>

<!-- 상세정보 모달 -->
<div class="modal-overlay" id="detailModal">
    <div class="modal modal-large">
        <div class="modal-title">판매자 가입정보</div>
        <div class="modal-message" id="detailContent">
            <!-- 동적으로 로드됨 -->
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeDetailModal()">닫기</button>
            <button type="button" id="detailApproveBtn" class="modal-btn modal-btn-confirm" style="display: none;">승인</button>
            <button type="button" id="detailHoldBtn" class="modal-btn modal-btn-hold" style="display: none;">승인보류</button>
        </div>
    </div>
</div>

<!-- 승인 확인 모달 -->
<div class="modal-overlay" id="approveModal">
    <div class="modal">
        <div class="modal-title">판매자 승인 확인</div>
        <div class="modal-message">
            <strong id="approveUserName"></strong> 판매자를 승인하시겠습니까?
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeApproveModal()">취소</button>
            <form method="POST" id="approveForm" style="display: inline;">
                <input type="hidden" name="user_id" id="approveUserId">
                <button type="submit" name="approve_seller" class="modal-btn modal-btn-confirm">승인</button>
            </form>
        </div>
    </div>
</div>

<!-- 보류 확인 모달 -->
<div class="modal-overlay" id="holdModal">
    <div class="modal">
        <div class="modal-title">판매자 승인보류 확인</div>
        <div class="modal-message">
            <strong id="holdUserName"></strong> 판매자 승인을 보류하시겠습니까?
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeHoldModal()">취소</button>
            <form method="POST" id="holdForm" style="display: inline;">
                <input type="hidden" name="user_id" id="holdUserId">
                <button type="submit" name="hold_seller" class="modal-btn modal-btn-hold">보류</button>
            </form>
        </div>
    </div>
</div>

<!-- 신청 취소(거부) 확인 모달 -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal">
        <div class="modal-title">판매자 신청 취소(거부) 확인</div>
        <div class="modal-message">
            <strong id="rejectUserName"></strong> 판매자 신청을 취소(거부)하시겠습니까?<br>
            <small style="color: #ef4444; margin-top: 8px; display: block;">이 작업은 되돌릴 수 없으며, 판매자가 재신청할 수 있습니다.</small>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeRejectModal()">취소</button>
            <form method="POST" id="rejectForm" style="display: inline;">
                <input type="hidden" name="user_id" id="rejectUserId">
                <button type="submit" name="reject_seller" class="modal-btn modal-btn-hold" style="background: #ef4444;">취소(거부)</button>
            </form>
        </div>
    </div>
</div>

<!-- 가입탈퇴 확인 모달 -->
<div class="modal-overlay" id="withdrawalModal">
    <div class="modal">
        <div class="modal-title">가입탈퇴 처리 확인</div>
        <div class="modal-message">
            <strong id="withdrawalUserName"></strong> 판매자의 가입탈퇴를 처리하시겠습니까?<br>
            <small style="color: #f59e0b; margin-top: 8px; display: block;">가입탈퇴 처리 후 탈퇴진행 탭으로 이동합니다.</small>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeWithdrawalModal()">취소</button>
            <form method="POST" id="withdrawalForm" style="display: inline;">
                <input type="hidden" name="user_id" id="withdrawalUserId">
                <input type="hidden" name="reason" value="관리자에 의한 가입탈퇴 처리">
                <button type="submit" name="request_withdrawal" class="modal-btn modal-btn-hold" style="background: #f59e0b;">가입탈퇴</button>
            </form>
        </div>
    </div>
</div>

<!-- 승인 취소 확인 모달 -->
<div class="modal-overlay" id="cancelApprovalModal">
    <div class="modal">
        <div class="modal-title">판매자 승인 취소 확인</div>
        <div class="modal-message">
            <strong id="cancelApprovalUserName"></strong> 판매자 승인을 취소하시겠습니까?<br>
            <small style="color: #f59e0b; margin-top: 8px; display: block;">승인이 취소되면 판매자는 승인 대기 상태로 변경됩니다.</small>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeCancelApprovalModal()">취소</button>
            <form method="POST" id="cancelApprovalForm" style="display: inline;">
                <input type="hidden" name="user_id" id="cancelApprovalUserId">
                <button type="submit" name="cancel_approval" class="modal-btn modal-btn-hold" style="background: #ef4444;">승인 취소</button>
            </form>
        </div>
    </div>
</div>

<!-- 탈퇴 완료 처리 모달 -->
<div class="modal-overlay" id="completeWithdrawalModal">
    <div class="modal">
        <div class="modal-title">판매자 탈퇴 완료 처리 확인</div>
        <div class="modal-message">
            <strong id="completeWithdrawalUserName"></strong> 판매자의 탈퇴를 완료 처리하시겠습니까?<br><br>
            <strong style="color: #ef4444;">⚠️ 다음 사항이 적용됩니다:</strong><br>
            <small style="color: #6b7280; margin-top: 8px; display: block; line-height: 1.6;">
                • 개인정보(이름, 이메일, 연락처, 주소 등)가 삭제됩니다.<br>
                • 계정이 영구적으로 비활성화됩니다.<br>
                • <strong style="color: #10b981;">등록하신 상품 정보는 모두 보존됩니다.</strong> (상품명, 가격, 설명 등)<br>
                • <strong style="color: #10b981;">고객의 구매 기록(신청내역, 주문 내역 등)은 모두 보존됩니다.</strong><br>
                • <strong>상품 정보, 리뷰, 주문 내역은 법적 보존 의무에 따라 보존됩니다.</strong><br>
                • 등록 상품은 "판매 종료" 상태로 변경될 수 있으나, 상품 정보는 보존됩니다.<br>
                • 고객은 탈퇴 후에도 자신의 구매 이력 및 구매한 상품 정보를 확인할 수 있습니다.<br>
                • 판매자명은 그대로 유지됩니다.
            </small>
        </div>
        <form method="POST" id="completeWithdrawalForm">
            <input type="hidden" name="user_id" id="completeWithdrawalUserId">
            <div class="form-group" style="margin: 20px 0;">
                <label class="form-label" for="deleteDate" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">삭제 예정일 (선택사항)</label>
                <input type="date" id="deleteDate" name="delete_date" class="form-input" min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">지정한 날짜에 자동으로 삭제 처리됩니다. 미지정 시 즉시 처리됩니다.</small>
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeCompleteWithdrawalModal()">취소</button>
                <button type="submit" name="complete_withdrawal" class="modal-btn modal-btn-hold" style="background: #ef4444;">탈퇴 완료 처리</button>
            </div>
        </form>
    </div>
</div>

<!-- 정보 업데이트 확인 모달 -->
<div class="modal-overlay" id="checkInfoUpdateModal" onclick="if(event.target === this) closeCheckInfoUpdateModal();">
    <div class="modal" onclick="event.stopPropagation();">
        <div class="modal-title">정보 업데이트 확인</div>
        <div class="modal-message">
            <strong id="checkInfoUpdateUserName"></strong> 판매자의 정보 업데이트 확인을 완료하시겠습니까?
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeCheckInfoUpdateModal()">취소</button>
            <form method="POST" action="/MVNO/admin/seller-approval.php?tab=updated" id="checkInfoUpdateForm" style="display: inline;">
                <input type="hidden" name="user_id" id="checkInfoUpdateUserId">
                <input type="hidden" name="page" id="checkInfoUpdatePage" value="<?php echo $updatedCurrentPage; ?>">
                <input type="hidden" name="per_page" id="checkInfoUpdatePerPage" value="<?php echo $perPage; ?>">
                <button type="submit" name="check_info_update" class="modal-btn modal-btn-confirm">확인</button>
            </form>
        </div>
    </div>
</div>

<!-- 탈퇴 요청 취소 모달 (관리자) -->
<div class="modal-overlay" id="cancelWithdrawalAdminModal">
    <div class="modal">
        <div class="modal-title">탈퇴 요청 취소 확인</div>
        <div class="modal-message">
            <strong id="cancelWithdrawalAdminUserName"></strong> 판매자의 탈퇴 요청을 취소하시겠습니까?<br>
            <small style="color: #10b981; margin-top: 8px; display: block;">취소 시 판매자 계정이 정상적으로 활성화됩니다.</small>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeCancelWithdrawalAdminModal()">취소</button>
            <form method="POST" id="cancelWithdrawalAdminForm" style="display: inline;">
                <input type="hidden" name="user_id" id="cancelWithdrawalAdminUserId">
                <button type="submit" name="cancel_withdrawal" class="modal-btn modal-btn-confirm">탈퇴 요청 취소</button>
            </form>
        </div>
    </div>
</div>

<script>
    // 판매자 데이터를 JavaScript에서 사용할 수 있도록 전달
    const sellersData = <?php echo json_encode($sellers, JSON_UNESCAPED_UNICODE); ?>;
    
    function showDetailModal(userId) {
        const seller = sellersData.find(s => s.user_id === userId);
        if (!seller) return;
        
        const detailContent = document.getElementById('detailContent');
        const approveBtn = document.getElementById('detailApproveBtn');
        const holdBtn = document.getElementById('detailHoldBtn');
        
        let html = '<div class="detail-info">';
        
        // 기본 정보
        html += '<div class="detail-row"><div class="detail-label">아이디</div><div class="detail-value">' + escapeHtml(seller.user_id || '-') + '</div></div>';
        html += '<div class="detail-row"><div class="detail-label">이름</div><div class="detail-value">' + escapeHtml(seller.name || '-') + '</div></div>';
        html += '<div class="detail-row"><div class="detail-label">이메일</div><div class="detail-value">' + escapeHtml(seller.email || '-') + '</div></div>';
        html += '<div class="detail-row"><div class="detail-label">가입일</div><div class="detail-value">' + escapeHtml(seller.created_at || '-') + '</div></div>';
        
        // 연락처 정보
        if (seller.phone) {
            html += '<div class="detail-row"><div class="detail-label">전화번호</div><div class="detail-value">' + escapeHtml(seller.phone) + '</div></div>';
        }
        if (seller.mobile) {
            html += '<div class="detail-row"><div class="detail-label">휴대폰</div><div class="detail-value">' + escapeHtml(seller.mobile) + '</div></div>';
        }
        
        // 주소 정보
        if (seller.address) {
            html += '<div class="detail-row"><div class="detail-label">주소</div><div class="detail-value">' + escapeHtml(seller.address) + '</div></div>';
        }
        if (seller.address_detail) {
            html += '<div class="detail-row"><div class="detail-label">상세주소</div><div class="detail-value">' + escapeHtml(seller.address_detail) + '</div></div>';
        }
        if (seller.postal_code) {
            html += '<div class="detail-row"><div class="detail-label">우편번호</div><div class="detail-value">' + escapeHtml(seller.postal_code) + '</div></div>';
        }
        
        // 사업자 정보
        if (seller.business_number) {
            html += '<div class="detail-row"><div class="detail-label">사업자등록번호</div><div class="detail-value">' + escapeHtml(seller.business_number) + '</div></div>';
        }
        if (seller.company_name) {
            html += '<div class="detail-row"><div class="detail-label">회사명</div><div class="detail-value">' + escapeHtml(seller.company_name) + '</div></div>';
        }
        if (seller.company_representative) {
            html += '<div class="detail-row"><div class="detail-label">대표자명</div><div class="detail-value">' + escapeHtml(seller.company_representative) + '</div></div>';
        }
        if (seller.business_type) {
            html += '<div class="detail-row"><div class="detail-label">업종</div><div class="detail-value">' + escapeHtml(seller.business_type) + '</div></div>';
        }
        if (seller.business_item) {
            html += '<div class="detail-row"><div class="detail-label">업태</div><div class="detail-value">' + escapeHtml(seller.business_item) + '</div></div>';
        }
        
        // 사업자등록증 이미지
        if (seller.business_license_image) {
            html += '<div class="detail-image">';
            html += '<div class="detail-image-label">사업자등록증</div>';
            html += '<img src="' + escapeHtml(seller.business_license_image) + '" alt="사업자등록증" class="detail-image-preview" onclick="showImageZoom(this.src)">';
            html += '</div>';
        }
        
        // 기타 첨부파일
        if (seller.other_documents && seller.other_documents.length > 0) {
            html += '<div class="detail-image" style="margin-top: 16px;">';
            html += '<div class="detail-image-label">기타 첨부파일</div>';
            seller.other_documents.forEach(function(doc, index) {
                if (doc.url) {
                    html += '<div style="margin-bottom: 12px;">';
                    html += '<img src="' + escapeHtml(doc.url) + '" alt="첨부파일 ' + (index + 1) + '" class="detail-image-preview" onclick="showImageZoom(this.src)" style="margin-bottom: 8px;">';
                    if (doc.name) {
                        html += '<div style="font-size: 12px; color: #6b7280;">' + escapeHtml(doc.name) + '</div>';
                    }
                    html += '</div>';
                }
            });
            html += '</div>';
        }
        
        // 승인 상태 정보
        const approvalStatus = seller.approval_status || (seller.seller_approved ? 'approved' : 'pending');
        if (approvalStatus === 'on_hold') {
            html += '<div class="detail-row"><div class="detail-label">보류일</div><div class="detail-value">' + escapeHtml(seller.held_at || '-') + '</div></div>';
        } else if (seller.seller_approved) {
            html += '<div class="detail-row"><div class="detail-label">승인일</div><div class="detail-value">' + escapeHtml(seller.approved_at || '-') + '</div></div>';
        }
        
        html += '</div>';
        detailContent.innerHTML = html;
        
        // 승인/보류 버튼 표시 (승인되지 않은 경우만)
        if (!seller.seller_approved && approvalStatus !== 'on_hold') {
            approveBtn.style.display = 'inline-block';
            holdBtn.style.display = 'inline-block';
            approveBtn.onclick = function() {
                closeDetailModal();
                showApproveModal(userId, seller.name || seller.user_id);
            };
            holdBtn.onclick = function() {
                closeDetailModal();
                showHoldModal(userId, seller.name || seller.user_id);
            };
        } else if (approvalStatus === 'on_hold') {
            approveBtn.style.display = 'inline-block';
            holdBtn.style.display = 'none';
            approveBtn.onclick = function() {
                closeDetailModal();
                showApproveModal(userId, seller.name || seller.user_id);
            };
        } else {
            approveBtn.style.display = 'none';
            holdBtn.style.display = 'none';
        }
        
        document.getElementById('detailModal').classList.add('active');
    }
    
    function closeDetailModal() {
        document.getElementById('detailModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    function escapeHtml(text) {
        if (!text) return '-';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function showApproveModal(userId, userName) {
        document.getElementById('approveUserId').value = userId;
        document.getElementById('approveUserName').textContent = userName;
        document.getElementById('approveModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeApproveModal() {
        document.getElementById('approveModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    function showHoldModal(userId, userName) {
        document.getElementById('holdUserId').value = userId;
        document.getElementById('holdUserName').textContent = userName;
        document.getElementById('holdModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeHoldModal() {
        document.getElementById('holdModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    function showCheckInfoUpdateModal(userId, userName) {
        const currentPage = <?php echo isset($updatedCurrentPage) ? $updatedCurrentPage : 1; ?>;
        const perPage = <?php echo $perPage; ?>;
        document.getElementById('checkInfoUpdateUserId').value = userId;
        document.getElementById('checkInfoUpdateUserName').textContent = userName;
        if (document.getElementById('checkInfoUpdatePage')) {
            document.getElementById('checkInfoUpdatePage').value = currentPage;
        }
        if (document.getElementById('checkInfoUpdatePerPage')) {
            document.getElementById('checkInfoUpdatePerPage').value = perPage;
        }
        document.getElementById('checkInfoUpdateModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeCheckInfoUpdateModal() {
        document.getElementById('checkInfoUpdateModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    function showRejectModal(userId, userName) {
        document.getElementById('rejectUserId').value = userId;
        document.getElementById('rejectUserName').textContent = userName;
        document.getElementById('rejectModal').classList.add('active');
    }
    
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.remove('active');
    }
    
    function showWithdrawalModal(userId, userName) {
        document.getElementById('withdrawalUserId').value = userId;
        document.getElementById('withdrawalUserName').textContent = userName;
        document.getElementById('withdrawalModal').classList.add('active');
    }
    
    function closeWithdrawalModal() {
        document.getElementById('withdrawalModal').classList.remove('active');
    }
    
    function showCancelApprovalModal(userId, userName) {
        document.getElementById('cancelApprovalUserId').value = userId;
        document.getElementById('cancelApprovalUserName').textContent = userName;
        document.getElementById('cancelApprovalModal').classList.add('active');
    }
    
    function closeCancelApprovalModal() {
        document.getElementById('cancelApprovalModal').classList.remove('active');
    }
    
    function showCompleteWithdrawalModal(userId, userName) {
        document.getElementById('completeWithdrawalUserId').value = userId;
        document.getElementById('completeWithdrawalUserName').textContent = userName;
        document.getElementById('completeWithdrawalModal').classList.add('active');
    }
    
    function closeCompleteWithdrawalModal() {
        document.getElementById('completeWithdrawalModal').classList.remove('active');
    }
    
    function showCancelWithdrawalAdminModal(userId, userName) {
        document.getElementById('cancelWithdrawalAdminUserId').value = userId;
        document.getElementById('cancelWithdrawalAdminUserName').textContent = userName;
        document.getElementById('cancelWithdrawalAdminModal').classList.add('active');
    }
    
    function closeCancelWithdrawalAdminModal() {
        document.getElementById('cancelWithdrawalAdminModal').classList.remove('active');
    }
    
    // 모달 외부 클릭 시 닫기
    document.getElementById('approveModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeApproveModal();
        }
    });
    
    document.getElementById('holdModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeHoldModal();
        }
    });
    
    document.getElementById('rejectModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeRejectModal();
        }
    });
    
    document.getElementById('withdrawalModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeWithdrawalModal();
        }
    });
    
    document.getElementById('cancelApprovalModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCancelApprovalModal();
        }
    });
    
    document.getElementById('detailModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDetailModal();
        }
    });
    
    document.getElementById('completeWithdrawalModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCompleteWithdrawalModal();
        }
    });
    
    document.getElementById('cancelWithdrawalAdminModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCancelWithdrawalAdminModal();
        }
    });
    
    // 이미지 확대 기능
    function showImageZoom(imageSrc) {
        const overlay = document.getElementById('imageZoomOverlay');
        const img = overlay.querySelector('img');
        img.src = imageSrc;
        overlay.classList.add('active');
    }
    
    function closeImageZoom() {
        document.getElementById('imageZoomOverlay').classList.remove('active');
    }
</script>

<!-- 이미지 확대 오버레이 -->
<div class="image-zoom-overlay" id="imageZoomOverlay" onclick="closeImageZoom()">
    <img src="" alt="확대 이미지" class="image-zoom-content" onclick="event.stopPropagation()">
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

