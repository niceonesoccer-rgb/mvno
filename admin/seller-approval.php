<?php
/**
 * 판매자 승인 관리 페이지
 */

require_once __DIR__ . '/includes/admin-header.php';

// 승인 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_seller'])) {
    $userId = $_POST['user_id'] ?? '';
    if ($userId && approveSeller($userId)) {
        $success_message = '판매자가 승인되었습니다.';
    } else {
        $error_message = '판매자 승인에 실패했습니다.';
    }
}

// 승인보류 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hold_seller'])) {
    $userId = $_POST['user_id'] ?? '';
    if ($userId && holdSeller($userId)) {
        $success_message = '판매자 승인이 보류되었습니다.';
    } else {
        $error_message = '판매자 승인보류에 실패했습니다.';
    }
}

// 신청 취소(거부) 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_seller'])) {
    $userId = $_POST['user_id'] ?? '';
    if ($userId && rejectSeller($userId)) {
        $success_message = '판매자 신청이 취소(거부)되었습니다.';
    } else {
        $error_message = '판매자 신청 취소에 실패했습니다.';
    }
}

// 승인 취소 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_approval'])) {
    $userId = $_POST['user_id'] ?? '';
    if ($userId && cancelApproval($userId)) {
        $success_message = '판매자 승인이 취소되었습니다.';
    } else {
        $error_message = '판매자 승인 취소에 실패했습니다.';
    }
}

// 탈퇴 완료 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_withdrawal'])) {
    $userId = $_POST['user_id'] ?? '';
    if ($userId && completeSellerWithdrawal($userId)) {
        $success_message = '판매자 탈퇴가 완료되었습니다. (개인정보 삭제, 상품/리뷰/주문 데이터는 보존)';
    } else {
        $error_message = '판매자 탈퇴 처리에 실패했습니다.';
    }
}

// 탈퇴 요청 취소 처리 (관리자 권한)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_withdrawal'])) {
    $userId = $_POST['user_id'] ?? '';
    if ($userId && cancelSellerWithdrawal($userId)) {
        $success_message = '탈퇴 요청이 취소되었습니다.';
    } else {
        $error_message = '탈퇴 요청 취소에 실패했습니다.';
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

// 승인 대기 중인 판매자 (승인보류, 거부가 아닌 미승인 판매자)
$pendingSellers = array_filter($sellers, function($seller) {
    $approvalStatus = $seller['approval_status'] ?? null;
    $isApproved = isset($seller['seller_approved']) && $seller['seller_approved'] === true;
    return !$isApproved && $approvalStatus !== 'on_hold' && $approvalStatus !== 'rejected';
});

// 승인보류 판매자
$onHoldSellers = array_filter($sellers, function($seller) {
    $approvalStatus = $seller['approval_status'] ?? null;
    return $approvalStatus === 'on_hold';
});

// 승인된 판매자
$approvedSellers = array_filter($sellers, function($seller) {
    return isset($seller['seller_approved']) && $seller['seller_approved'] === true;
});

// 승인불가(거부) 판매자
$rejectedSellers = array_filter($sellers, function($seller) {
    $approvalStatus = $seller['approval_status'] ?? null;
    return $approvalStatus === 'rejected';
});

// 탈퇴 요청한 판매자
$withdrawalRequestedSellers = array_filter($sellers, function($seller) {
    return isset($seller['withdrawal_requested']) && $seller['withdrawal_requested'] === true;
});

// 활성 탭 확인 (기본값: 신청자)
$activeTab = $_GET['tab'] ?? 'pending';
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
</style>

<script>
        function switchTab(tabName) {
            // 모든 탭과 콘텐츠 숨기기
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // 선택한 탭과 콘텐츠 보이기
            document.getElementById('tab-' + tabName).classList.add('active');
            document.getElementById('content-' + tabName).classList.add('active');
            
            // URL 업데이트 (새로고침 없이)
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);
        }
        
        // 페이지 로드 시 활성 탭 설정
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'pending';
            switchTab(tab);
        });
</script>

<div class="admin-content">
    <h1>판매자 관리</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- 탭 메뉴 -->
        <div class="tabs">
            <button class="tab" id="tab-pending" onclick="switchTab('pending')">
                신청자
                <span class="tab-badge"><?php echo count($pendingSellers); ?></span>
            </button>
            <button class="tab" id="tab-onhold" onclick="switchTab('onhold')">
                보류자
                <span class="tab-badge"><?php echo count($onHoldSellers); ?></span>
            </button>
            <button class="tab" id="tab-approved" onclick="switchTab('approved')">
                승인판매자
                <span class="tab-badge"><?php echo count($approvedSellers); ?></span>
            </button>
            <button class="tab" id="tab-rejected" onclick="switchTab('rejected')">
                승인불가
                <span class="tab-badge"><?php echo count($rejectedSellers); ?></span>
            </button>
            <button class="tab" id="tab-withdrawal" onclick="switchTab('withdrawal')">
                탈퇴 요청
                <span class="tab-badge"><?php echo count($withdrawalRequestedSellers); ?></span>
            </button>
        </div>
        
        <!-- 신청자 탭 -->
        <div class="tab-content" id="content-pending">
            <div class="sellers-section">
                <?php if (count($pendingSellers) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>아이디</th>
                                <th>이름</th>
                                <th>이메일</th>
                                <th>가입일</th>
                                <th>상태</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingSellers as $seller): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($seller['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($seller['name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['email'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['created_at'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-pending">대기 중</span>
                                    </td>
                                    <td>
                                        <a href="/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>" class="btn" style="background: #6366f1; color: white; margin-right: 8px; text-decoration: none; display: inline-block;">상세보기</a>
                                        <button type="button" onclick="showApproveModal('<?php echo htmlspecialchars($seller['user_id']); ?>', '<?php echo htmlspecialchars($seller['name'] ?? $seller['user_id']); ?>')" class="btn btn-approve" style="margin-right: 8px;">승인</button>
                                        <button type="button" onclick="showHoldModal('<?php echo htmlspecialchars($seller['user_id']); ?>', '<?php echo htmlspecialchars($seller['name'] ?? $seller['user_id']); ?>')" class="btn btn-hold" style="margin-right: 8px;">승인보류</button>
                                        <button type="button" onclick="showRejectModal('<?php echo htmlspecialchars($seller['user_id']); ?>', '<?php echo htmlspecialchars($seller['name'] ?? $seller['user_id']); ?>')" class="btn btn-reject">신청취소</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #6b7280; font-size: 14px; padding: 40px; text-align: center;">승인 대기 중인 판매자가 없습니다.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 보류자 탭 -->
        <div class="tab-content" id="content-onhold">
            <div class="sellers-section">
                <?php if (count($onHoldSellers) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>아이디</th>
                                <th>이름</th>
                                <th>이메일</th>
                                <th>가입일</th>
                                <th>보류일</th>
                                <th>상태</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($onHoldSellers as $seller): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($seller['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($seller['name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['email'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['created_at'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['held_at'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-on-hold">승인보류</span>
                                    </td>
                                    <td>
                                        <a href="/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>" class="btn" style="background: #6366f1; color: white; margin-right: 8px; text-decoration: none; display: inline-block;">상세보기</a>
                                        <button type="button" onclick="showApproveModal('<?php echo htmlspecialchars($seller['user_id']); ?>', '<?php echo htmlspecialchars($seller['name'] ?? $seller['user_id']); ?>')" class="btn btn-approve" style="margin-right: 8px;">승인</button>
                                        <button type="button" onclick="showRejectModal('<?php echo htmlspecialchars($seller['user_id']); ?>', '<?php echo htmlspecialchars($seller['name'] ?? $seller['user_id']); ?>')" class="btn btn-reject">승인불가</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #6b7280; font-size: 14px; padding: 40px; text-align: center;">승인보류 판매자가 없습니다.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 승인판매자 탭 -->
        <div class="tab-content" id="content-approved">
            <div class="sellers-section">
                <?php if (count($approvedSellers) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>아이디</th>
                                <th>이름</th>
                                <th>이메일</th>
                                <th>가입일</th>
                                <th>승인일</th>
                                <th>권한</th>
                                <th>상태</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($approvedSellers as $seller): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($seller['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($seller['name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['email'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['created_at'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['approved_at'] ?? '-'); ?></td>
                                    <td>
                                        <?php 
                                        $permissions = $seller['permissions'] ?? [];
                                        $permissionLabels = [];
                                        if (in_array('mvno', $permissions)) $permissionLabels[] = '알뜰폰';
                                        if (in_array('mno', $permissions)) $permissionLabels[] = '통신사폰';
                                        if (in_array('internet', $permissions)) $permissionLabels[] = '인터넷';
                                        echo !empty($permissionLabels) ? implode(', ', $permissionLabels) : '<span style="color: #9ca3af;">권한 없음</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-approved">승인됨</span>
                                    </td>
                                    <td>
                                        <a href="/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>" class="btn" style="background: #6366f1; color: white; margin-right: 8px; text-decoration: none; display: inline-block;">상세보기</a>
                                        <a href="/MVNO/admin/seller-permissions.php?user_id=<?php echo urlencode($seller['user_id']); ?>" style="padding: 6px 12px; background: #10b981; color: white; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-block; margin-right: 8px;">권한 설정</a>
                                        <button type="button" onclick="showCancelApprovalModal('<?php echo htmlspecialchars($seller['user_id']); ?>', '<?php echo htmlspecialchars($seller['name'] ?? $seller['user_id']); ?>')" class="btn btn-cancel-approval">승인취소</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #6b7280; font-size: 14px; padding: 40px; text-align: center;">승인된 판매자가 없습니다.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 승인불가 탭 -->
        <div class="tab-content" id="content-rejected">
            <div class="sellers-section">
                <?php if (count($rejectedSellers) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>아이디</th>
                                <th>이름</th>
                                <th>이메일</th>
                                <th>가입일</th>
                                <th>거부일</th>
                                <th>상태</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rejectedSellers as $seller): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($seller['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($seller['name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['email'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['created_at'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['rejected_at'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-rejected">승인불가</span>
                                    </td>
                                    <td>
                                        <a href="/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>" class="btn" style="background: #6366f1; color: white; margin-right: 8px; text-decoration: none; display: inline-block;">상세보기</a>
                                        <button type="button" onclick="showApproveModal('<?php echo htmlspecialchars($seller['user_id']); ?>', '<?php echo htmlspecialchars($seller['name'] ?? $seller['user_id']); ?>')" class="btn btn-approve">재승인</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #6b7280; font-size: 14px; padding: 40px; text-align: center;">승인불가 판매자가 없습니다.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 탈퇴 요청 탭 -->
        <div class="tab-content" id="content-withdrawal">
            <div class="sellers-section">
                <?php if (count($withdrawalRequestedSellers) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>아이디</th>
                                <th>이름</th>
                                <th>이메일</th>
                                <th>탈퇴 요청일</th>
                                <th>탈퇴 사유</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawalRequestedSellers as $seller): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($seller['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($seller['name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['email'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['withdrawal_requested_at'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($seller['withdrawal_reason'] ?? '사유 없음'); ?></td>
                                    <td>
                                        <a href="/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>" class="btn" style="background: #6366f1; color: white; margin-right: 8px; text-decoration: none; display: inline-block;">상세보기</a>
                                        <button type="button" onclick="showCompleteWithdrawalModal('<?php echo htmlspecialchars($seller['user_id']); ?>', '<?php echo htmlspecialchars($seller['name'] ?? $seller['user_id']); ?>')" class="btn" style="background: #ef4444; color: white; margin-right: 8px;">탈퇴 완료 처리</button>
                                        <button type="button" onclick="showCancelWithdrawalAdminModal('<?php echo htmlspecialchars($seller['user_id']); ?>', '<?php echo htmlspecialchars($seller['name'] ?? $seller['user_id']); ?>')" class="btn btn-approve">탈퇴 요청 취소</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #6b7280; font-size: 14px; padding: 40px; text-align: center;">탈퇴 요청이 없습니다.</p>
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
                • 판매자명은 "탈퇴한 판매자"로 표시됩니다.
            </small>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeCompleteWithdrawalModal()">취소</button>
            <form method="POST" id="completeWithdrawalForm" style="display: inline;">
                <input type="hidden" name="user_id" id="completeWithdrawalUserId">
                <button type="submit" name="complete_withdrawal" class="modal-btn modal-btn-hold" style="background: #ef4444;">탈퇴 완료 처리</button>
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
    }
    
    function closeApproveModal() {
        document.getElementById('approveModal').classList.remove('active');
    }
    
    function showHoldModal(userId, userName) {
        document.getElementById('holdUserId').value = userId;
        document.getElementById('holdUserName').textContent = userName;
        document.getElementById('holdModal').classList.add('active');
    }
    
    function closeHoldModal() {
        document.getElementById('holdModal').classList.remove('active');
    }
    
    function showRejectModal(userId, userName) {
        document.getElementById('rejectUserId').value = userId;
        document.getElementById('rejectUserName').textContent = userName;
        document.getElementById('rejectModal').classList.add('active');
    }
    
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.remove('active');
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

