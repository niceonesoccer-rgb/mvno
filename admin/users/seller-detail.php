<?php
/**
 * 관리자용 판매자 상세 정보 페이지
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';

// 관리자 권한 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin()) {
    header('Location: /MVNO/auth/login.php');
    exit;
}

// 판매자 ID 가져오기
$sellerId = $_GET['user_id'] ?? '';

if (empty($sellerId)) {
    header('Location: /MVNO/admin/seller-approval.php');
    exit;
}

// 권한 설정 처리 (헤더 출력 전에 처리)
$success_message = null;
$error_message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    $userId = $_POST['user_id'] ?? '';
    $permissions = $_POST['permissions'] ?? [];
    
    if ($userId && setSellerPermissions($userId, $permissions)) {
        $success_message = '권한이 성공적으로 저장되었습니다.';
    } else {
        $error_message = '권한 저장에 실패했습니다.';
    }
}

// 승인 처리 (헤더 출력 전에 처리)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_seller'])) {
    $userId = $_POST['user_id'] ?? '';
    if ($userId && approveSeller($userId)) {
        header('Location: /MVNO/admin/users/seller-detail.php?user_id=' . urlencode($userId) . '&success=approve');
        exit;
    } else {
        header('Location: /MVNO/admin/users/seller-detail.php?user_id=' . urlencode($userId) . '&error=approve');
        exit;
    }
}

// 승인보류 처리 (헤더 출력 전에 처리)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hold_seller'])) {
    $userId = $_POST['user_id'] ?? '';
    if ($userId && holdSeller($userId)) {
        header('Location: /MVNO/admin/users/seller-detail.php?user_id=' . urlencode($userId) . '&success=hold');
        exit;
    } else {
        header('Location: /MVNO/admin/users/seller-detail.php?user_id=' . urlencode($userId) . '&error=hold');
        exit;
    }
}

require_once __DIR__ . '/../includes/admin-header.php';

// 판매자 정보 가져오기 (POST 요청 후 최신 정보를 가져오기 위해 여기서 로드)
// 파일 캐시 클리어하여 최신 데이터 가져오기
$sellersFile = __DIR__ . '/../../includes/data/sellers.json';
if (file_exists($sellersFile)) {
    clearstatcache(true, $sellersFile);
}
$seller = getUserById($sellerId);

if (!$seller || $seller['role'] !== 'seller') {
    header('Location: /MVNO/admin/seller-approval.php');
    exit;
}

?>
<style>
    .seller-detail-container {
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
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
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
    
    .business-info-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        margin-bottom: 24px;
    }
    
    .license-image {
        max-width: 600px;
        max-height: 400px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-top: 12px;
        cursor: pointer;
        transition: transform 0.2s;
    }
    
    .license-image:hover {
        transform: scale(1.02);
    }
    
    .action-buttons {
        display: flex;
        gap: 12px;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    
    .btn-primary {
        background: #6366f1;
        color: white;
    }
    
    .btn-primary:hover {
        background: #4f46e5;
    }
    
    .btn-success {
        background: #10b981;
        color: white;
    }
    
    .btn-success:hover {
        background: #059669;
    }
    
    .btn-warning {
        background: #f59e0b;
        color: white;
    }
    
    .btn-warning:hover {
        background: #d97706;
    }
    
    .full-width {
        grid-column: 1 / -1;
    }
    
    .permissions-form {
        margin-top: 0;
    }
    
    .permissions-checkboxes {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
    }
    
    .permission-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .modal-overlay.active {
        display: flex !important;
    }
    
    .modal-btn:hover {
        opacity: 0.9;
    }
    
    .modal-btn-cancel:hover {
        background: #e5e7eb !important;
    }
    
    .modal-btn-confirm:hover {
        background: #059669 !important;
    }
    
    .alert {
        max-width: 1200px;
        margin: 0 auto 24px;
    }
</style>

<div class="admin-content">
    <div class="seller-detail-container">
        <div class="detail-header">
            <h1>판매자 상세 정보</h1>
            <a href="/MVNO/admin/seller-approval.php?tab=approved" class="back-button">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                목록으로
            </a>
        </div>
        
        <div class="detail-grid">
            <!-- 기본 정보 -->
            <div class="detail-card">
                <h2 class="detail-card-title">기본 정보</h2>
                <div class="detail-item">
                    <div class="detail-label">아이디</div>
                    <div class="detail-value"><?php echo htmlspecialchars($seller['user_id'] ?? ''); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">이름</div>
                    <div class="detail-value"><?php echo htmlspecialchars($seller['name'] ?? ''); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">이메일</div>
                    <div class="detail-value"><?php echo htmlspecialchars($seller['email'] ?? ''); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">가입일</div>
                    <div class="detail-value"><?php echo htmlspecialchars($seller['created_at'] ?? ''); ?></div>
                </div>
            </div>
            
            <!-- 판매자 상태 -->
            <div class="detail-card">
                <h2 class="detail-card-title">판매자 상태</h2>
                <div class="detail-item">
                    <div class="detail-label">승인 상태</div>
                    <div class="detail-value" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <?php 
                        $approvalStatus = $seller['approval_status'] ?? 'pending';
                        $isApproved = isset($seller['seller_approved']) && $seller['seller_approved'] === true;
                        
                        if ($isApproved || $approvalStatus === 'approved') {
                            echo '<span class="detail-badge badge-approved">승인됨</span>';
                        } elseif ($approvalStatus === 'on_hold') {
                            echo '<span class="detail-badge badge-on-hold">승인 보류</span>';
                        } else {
                            echo '<span class="detail-badge badge-pending">승인 대기</span>';
                        }
                        ?>
                        <?php if (isset($seller['approved_at']) && ($isApproved || $approvalStatus === 'approved')): ?>
                            <span style="font-size: 13px; color: #6b7280;">승인일: <?php echo htmlspecialchars($seller['approved_at']); ?></span>
                        <?php endif; ?>
                        <?php if (isset($seller['held_at']) && $approvalStatus === 'on_hold'): ?>
                            <span style="font-size: 13px; color: #6b7280;">보류일: <?php echo htmlspecialchars($seller['held_at']); ?></span>
                        <?php endif; ?>
                        <div style="margin-left: auto;">
                            <?php if (!$isApproved && $approvalStatus !== 'on_hold'): ?>
                                <!-- 승인 대기 회원: 승인 버튼 -->
                                <button type="button" onclick="showApproveConfirmModal('<?php echo htmlspecialchars($seller['user_id']); ?>', '<?php echo htmlspecialchars($seller['name'] ?? $seller['user_id']); ?>')" class="btn btn-success" style="padding: 8px 16px; font-size: 13px; height: 36px; line-height: 1;">승인</button>
                            <?php elseif ($approvalStatus === 'on_hold'): ?>
                                <!-- 승인보류 회원: 승인 버튼 -->
                                <button type="button" onclick="showApproveConfirmModal('<?php echo htmlspecialchars($seller['user_id']); ?>', '<?php echo htmlspecialchars($seller['name'] ?? $seller['user_id']); ?>')" class="btn btn-success" style="padding: 8px 16px; font-size: 13px; height: 36px; line-height: 1;">승인</button>
                            <?php elseif ($isApproved || $approvalStatus === 'approved'): ?>
                                <!-- 승인된 회원: 승인보류 버튼 -->
                                <button type="button" onclick="showHoldConfirmModal('<?php echo htmlspecialchars($seller['user_id']); ?>', '<?php echo htmlspecialchars($seller['name'] ?? $seller['user_id']); ?>')" class="btn btn-warning" style="padding: 8px 16px; font-size: 13px; height: 36px; line-height: 1;">승인보류</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">권한</div>
                    <div class="detail-value" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <?php 
                        $permissions = $seller['permissions'] ?? [];
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
                        <button type="button" class="btn btn-primary" onclick="openPermissionModal('<?php echo htmlspecialchars($seller['user_id']); ?>')" style="margin-left: auto; padding: 8px 16px; font-size: 13px; height: 36px; line-height: 1;">
                            권한 설정
                        </button>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"></div>
                    <div class="detail-value" style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; flex-wrap: wrap;">
                        <a href="/MVNO/admin/users/seller-edit.php?user_id=<?php echo urlencode($seller['user_id']); ?>" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px; height: 36px; line-height: 1; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
                            정보 수정
                        </a>
                    </div>
                </div>
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
        <?php if (isset($seller['business_number']) || isset($seller['company_name']) || isset($seller['phone']) || isset($seller['mobile'])): ?>
            <div class="business-info-card">
                <h2 class="detail-card-title">사업자 정보</h2>
                
                <?php if (isset($seller['business_number'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">사업자등록번호</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['business_number']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($seller['company_name'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">회사명</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['company_name']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($seller['company_representative'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">대표자명</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['company_representative']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($seller['business_type'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">업태</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['business_type']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($seller['business_item'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">종목</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['business_item']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($seller['phone'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">전화번호</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['phone']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($seller['mobile'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">휴대폰</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['mobile']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($seller['postal_code']) || isset($seller['address'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">주소</div>
                        <div class="detail-value">
                            <?php 
                            $postalCode = isset($seller['postal_code']) ? htmlspecialchars($seller['postal_code']) : '';
                            $address = htmlspecialchars($seller['address'] ?? '');
                            $addressDetail = isset($seller['address_detail']) ? htmlspecialchars($seller['address_detail']) : '';
                            
                            $addressParts = [];
                            if (!empty($postalCode)) {
                                $addressParts[] = $postalCode;
                            }
                            if (!empty($address)) {
                                $addressParts[] = $address;
                            }
                            if (!empty($addressDetail)) {
                                $addressParts[] = $addressDetail;
                            }
                            
                            echo implode(', ', $addressParts);
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($seller['business_license_image']) && !empty($seller['business_license_image'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">사업자등록증</div>
                        <div class="detail-value">
                            <img src="<?php echo htmlspecialchars($seller['business_license_image']); ?>" alt="사업자등록증" class="license-image" onclick="showImageZoom(this.src)">
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- 전체 데이터 (JSON) -->
        <div class="detail-card">
            <h2 class="detail-card-title">전체 데이터 (JSON)</h2>
            <pre style="background: #f9fafb; padding: 16px; border-radius: 8px; overflow-x: auto; font-size: 12px; line-height: 1.6; max-height: 400px; overflow-y: auto;"><?php echo htmlspecialchars(json_encode($seller, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
        </div>
    </div>
</div>

<!-- 이미지 확대 오버레이 -->
<div class="image-zoom-overlay" id="imageZoomOverlay" onclick="closeImageZoom()" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.9); z-index: 2000; align-items: center; justify-content: center; cursor: pointer;">
    <img src="" alt="확대 이미지" class="image-zoom-content" onclick="event.stopPropagation()" style="max-width: 90%; max-height: 90%; border-radius: 8px;">
</div>

<!-- 권한 설정 모달 -->
<div class="modal-overlay" id="permissionModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal" style="background: white; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);">
        <div class="modal-title" style="font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 16px;">판매자 권한 설정</div>
        
        <form method="POST" class="permissions-form" id="permissionsForm_<?php echo htmlspecialchars($seller['user_id']); ?>" data-user-id="<?php echo htmlspecialchars($seller['user_id']); ?>" data-initial-permissions="<?php echo htmlspecialchars(json_encode($seller['permissions'] ?? [])); ?>">
            <input type="hidden" name="save_permissions" value="1">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($seller['user_id']); ?>">
            
            <div style="margin-bottom: 20px;">
                <div style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 12px;">게시판 등록 권한</div>
                <div class="permissions-checkboxes" style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="permission-item" style="display: flex; align-items: center; gap: 8px;">
                        <input 
                            type="checkbox" 
                            id="modal_mvno_<?php echo htmlspecialchars($seller['user_id']); ?>" 
                            name="permissions[]" 
                            value="mvno"
                            class="permission-checkbox"
                            style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;"
                            <?php echo (isset($seller['permissions']) && in_array('mvno', $seller['permissions'])) ? 'checked' : ''; ?>
                        >
                        <label for="modal_mvno_<?php echo htmlspecialchars($seller['user_id']); ?>" style="font-size: 14px; color: #374151; cursor: pointer; user-select: none;">
                            알뜰폰
                        </label>
                    </div>
                    
                    <div class="permission-item" style="display: flex; align-items: center; gap: 8px;">
                        <input 
                            type="checkbox" 
                            id="modal_mno_<?php echo htmlspecialchars($seller['user_id']); ?>" 
                            name="permissions[]" 
                            value="mno"
                            class="permission-checkbox"
                            style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;"
                            <?php echo (isset($seller['permissions']) && in_array('mno', $seller['permissions'])) ? 'checked' : ''; ?>
                        >
                        <label for="modal_mno_<?php echo htmlspecialchars($seller['user_id']); ?>" style="font-size: 14px; color: #374151; cursor: pointer; user-select: none;">
                            통신사폰
                        </label>
                    </div>
                    
                    <div class="permission-item" style="display: flex; align-items: center; gap: 8px;">
                        <input 
                            type="checkbox" 
                            id="modal_internet_<?php echo htmlspecialchars($seller['user_id']); ?>" 
                            name="permissions[]" 
                            value="internet"
                            class="permission-checkbox"
                            style="width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1;"
                            <?php echo (isset($seller['permissions']) && in_array('internet', $seller['permissions'])) ? 'checked' : ''; ?>
                        >
                        <label for="modal_internet_<?php echo htmlspecialchars($seller['user_id']); ?>" style="font-size: 14px; color: #374151; cursor: pointer; user-select: none;">
                            인터넷
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="modal-actions" style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closePermissionModal()" style="padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; background: #f3f4f6; color: #374151;">취소</button>
                <button type="button" class="modal-btn modal-btn-confirm" onclick="checkAndSavePermissions('<?php echo htmlspecialchars($seller['user_id']); ?>')" style="padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; background: #6366f1; color: white;">저장</button>
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
            <form method="POST" action="/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>" id="approveConfirmForm" style="display: inline;">
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
            <form method="POST" action="/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>" id="holdConfirmForm" style="display: inline;">
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
            <button type="button" class="modal-btn modal-btn-confirm" onclick="confirmSave()" style="padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; background: #10b981; color: white;">저장</button>
        </div>
    </div>
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
    
    // 권한 설정 모달 열기
    function openPermissionModal(userId) {
        // 권한 설정 모달 표시 (승인 여부와 관계없이)
        const modal = document.getElementById('permissionModal');
        modal.style.display = 'flex';
    }
    
    // 권한 설정 모달 닫기
    function closePermissionModal() {
        document.getElementById('permissionModal').style.display = 'none';
    }
    
    // 권한 변경 감지 및 저장 처리
    function checkAndSavePermissions(userId) {
        const form = document.getElementById('permissionsForm_' + userId);
        if (!form) {
            console.error('Form not found for userId:', userId);
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
        
        if (!hasChanged) {
            // 변경 사항 없음 모달 표시
            showNoChangeModal();
            return;
        }
        
        // 모달 표시
        showSaveModal();
        
        // 저장할 폼 ID 저장
        window.pendingFormId = userId;
    }
    
    // 저장 확인 모달 표시
    function showSaveModal() {
        const modal = document.getElementById('saveModal');
        const modalTitle = modal.querySelector('.modal-title');
        const modalMessage = modal.querySelector('.modal-message');
        const modalActions = modal.querySelector('.modal-actions');
        
        modalTitle.textContent = '권한 저장 확인';
        modalMessage.textContent = '판매자 권한을 저장하시겠습니까?';
        
        // 버튼을 저장 모드로 변경
        modalActions.innerHTML = `
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeSaveModal()">취소</button>
            <button type="button" class="modal-btn modal-btn-confirm" onclick="confirmSave()">저장</button>
        `;
        
        modal.classList.add('active');
    }
    
    // 변경 사항 없음 모달 표시
    function showNoChangeModal() {
        const modal = document.getElementById('saveModal');
        const modalTitle = modal.querySelector('.modal-title');
        const modalMessage = modal.querySelector('.modal-message');
        const modalActions = modal.querySelector('.modal-actions');
        
        modalTitle.textContent = '알림';
        modalMessage.textContent = '변경된 권한이 없습니다.';
        
        // 버튼을 확인만 표시
        modalActions.innerHTML = `
            <button type="button" class="modal-btn modal-btn-confirm" onclick="closeSaveModal()" style="width: 100%;">확인</button>
        `;
        
        modal.classList.add('active');
    }
    
    // 모달 닫기
    function closeSaveModal() {
        document.getElementById('saveModal').classList.remove('active');
        window.pendingFormId = null;
    }
    
    // 모달에서 확인 클릭 시 저장 실행
    function confirmSave() {
        const userId = window.pendingFormId;
        if (!userId) {
            console.error('No pending form ID');
            return;
        }
        
        const form = document.getElementById('permissionsForm_' + userId);
        if (!form) {
            console.error('Form not found for userId:', userId);
            return;
        }
        
        // 저장 확인 모달 닫기
        closeSaveModal();
        
        // 권한 설정 모달 닫기
        closePermissionModal();
        
        // 폼 제출
        form.submit();
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

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

