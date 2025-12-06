<?php
/**
 * 판매자 권한 관리 페이지
 * 관리자가 판매자별로 알뜰폰/통신사폰/인터넷 게시판 권한을 부여하는 페이지
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 관리자 인증 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin()) {
    header('Location: /MVNO/auth/login.php');
    exit;
}

// 권한 설정 처리 (헤더 출력 전에 처리)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    $userId = $_POST['user_id'] ?? '';
    $permissions = $_POST['permissions'] ?? [];
    
    if ($userId && setSellerPermissions($userId, $permissions)) {
        // 저장 성공 시 판매자 관리 페이지로 리다이렉트
        header('Location: /MVNO/admin/seller-approval.php?success=permissions_saved');
        exit;
    } else {
        $error_message = '권한 저장에 실패했습니다.';
    }
}

require_once __DIR__ . '/includes/admin-header.php';

// 특정 판매자만 표시 (user_id 파라미터가 있는 경우)
$targetUserId = $_GET['user_id'] ?? null;

// 사용자 데이터 읽기
$data = getUsersData();
$sellers = [];
foreach ($data['users'] as $user) {
    if (isset($user['role']) && $user['role'] === 'seller') {
        $sellers[] = $user;
    }
}

// 승인된 판매자만 필터링
$approvedSellers = array_filter($sellers, function($seller) {
    return isset($seller['seller_approved']) && $seller['seller_approved'] === true;
});

// 특정 판매자만 표시 (user_id 파라미터가 있는 경우)
if ($targetUserId) {
    $approvedSellers = array_filter($approvedSellers, function($seller) use ($targetUserId) {
        return $seller['user_id'] === $targetUserId;
    });
}
?>
<style>
        .admin-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .page-description {
            font-size: 14px;
            color: #6b7280;
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
        
        .sellers-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .seller-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            background: white;
        }
        
        .seller-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .seller-info {
            flex: 1;
        }
        
        .seller-name {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .seller-details {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .permissions-form {
            margin-top: 16px;
        }
        
        .permissions-title {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
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
        
        .permission-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #6366f1;
        }
        
        .permission-item label {
            font-size: 14px;
            color: #374151;
            cursor: pointer;
            user-select: none;
        }
        
        .permission-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            margin-left: 8px;
        }
        
        .permission-badge.mvno {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .permission-badge.mno {
            background: #fce7f3;
            color: #9f1239;
        }
        
        .permission-badge.internet {
            background: #dcfce7;
            color: #166534;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .btn-save {
            background: #6366f1;
            color: white;
        }
        
        .btn-save:hover {
            background: #4f46e5;
        }
        
        .no-sellers {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .no-sellers-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .no-sellers-text {
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .no-sellers-subtext {
            font-size: 14px;
            color: #9ca3af;
        }
        
        .link-to-approval {
            display: inline-block;
            margin-top: 8px;
            color: #6366f1;
            text-decoration: none;
            font-size: 14px;
        }
        
        .link-to-approval:hover {
            text-decoration: underline;
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
        
        .btn-save:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
    </style>

<div class="admin-content">
    <div class="admin-container">
        <h1>판매자 권한 관리</h1>
        <p class="page-description">승인된 판매자에게 알뜰폰, 통신사폰, 인터넷 게시판 등록 권한을 부여할 수 있습니다.</p>
        
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
        
        <?php if (count($approvedSellers) > 0): ?>
            <div class="sellers-list">
                <?php foreach ($approvedSellers as $seller): ?>
                    <div class="seller-card">
                        <div class="seller-header">
                            <div class="seller-info">
                                <div class="seller-name">
                                    <?php echo htmlspecialchars($seller['name'] ?? $seller['user_id']); ?>
                                </div>
                                <div class="seller-details">
                                    <div>아이디: <?php echo htmlspecialchars($seller['user_id']); ?></div>
                                    <div>이메일: <?php echo htmlspecialchars($seller['email'] ?? '-'); ?></div>
                                    <?php if (isset($seller['permissions_updated_at'])): ?>
                                        <div style="margin-top: 4px; color: #9ca3af; font-size: 12px;">
                                            권한 수정일: <?php echo htmlspecialchars($seller['permissions_updated_at']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" class="permissions-form" id="permissionsForm_<?php echo htmlspecialchars($seller['user_id']); ?>" data-user-id="<?php echo htmlspecialchars($seller['user_id']); ?>" data-initial-permissions="<?php echo htmlspecialchars(json_encode($seller['permissions'] ?? [])); ?>">
                            <input type="hidden" name="save_permissions" value="1">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($seller['user_id']); ?>">
                            
                            <div class="permissions-title">게시판 등록 권한</div>
                            <div class="permissions-checkboxes">
                                <div class="permission-item">
                                    <input 
                                        type="checkbox" 
                                        id="mvno_<?php echo htmlspecialchars($seller['user_id']); ?>" 
                                        name="permissions[]" 
                                        value="mvno"
                                        class="permission-checkbox"
                                        <?php echo (isset($seller['permissions']) && in_array('mvno', $seller['permissions'])) ? 'checked' : ''; ?>
                                    >
                                    <label for="mvno_<?php echo htmlspecialchars($seller['user_id']); ?>">
                                        알뜰폰
                                        <?php if (isset($seller['permissions']) && in_array('mvno', $seller['permissions'])): ?>
                                            <span class="permission-badge mvno">권한 있음</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                                
                                <div class="permission-item">
                                    <input 
                                        type="checkbox" 
                                        id="mno_<?php echo htmlspecialchars($seller['user_id']); ?>" 
                                        name="permissions[]" 
                                        value="mno"
                                        class="permission-checkbox"
                                        <?php echo (isset($seller['permissions']) && in_array('mno', $seller['permissions'])) ? 'checked' : ''; ?>
                                    >
                                    <label for="mno_<?php echo htmlspecialchars($seller['user_id']); ?>">
                                        통신사폰
                                        <?php if (isset($seller['permissions']) && in_array('mno', $seller['permissions'])): ?>
                                            <span class="permission-badge mno">권한 있음</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                                
                                <div class="permission-item">
                                    <input 
                                        type="checkbox" 
                                        id="internet_<?php echo htmlspecialchars($seller['user_id']); ?>" 
                                        name="permissions[]" 
                                        value="internet"
                                        class="permission-checkbox"
                                        <?php echo (isset($seller['permissions']) && in_array('internet', $seller['permissions'])) ? 'checked' : ''; ?>
                                    >
                                    <label for="internet_<?php echo htmlspecialchars($seller['user_id']); ?>">
                                        인터넷
                                        <?php if (isset($seller['permissions']) && in_array('internet', $seller['permissions'])): ?>
                                            <span class="permission-badge internet">권한 있음</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            </div>
                            
                            <div style="margin-top: 16px; text-align: right;">
                                <button type="button" class="btn btn-save" onclick="checkAndSavePermissions('<?php echo htmlspecialchars($seller['user_id']); ?>')">
                                    <?php 
                                    $hasPermissions = isset($seller['permissions']) && is_array($seller['permissions']) && count($seller['permissions']) > 0;
                                    echo $hasPermissions ? '수정' : '권한 저장';
                                    ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-sellers">
                <div class="no-sellers-icon">📋</div>
                <div class="no-sellers-text">승인된 판매자가 없습니다</div>
                <div class="no-sellers-subtext">판매자를 먼저 승인해주세요</div>
                <a href="/MVNO/admin/seller-approval.php" class="link-to-approval">판매자 승인 페이지로 이동</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 저장 확인 모달 -->
    <div class="modal-overlay" id="saveModal">
        <div class="modal">
            <div class="modal-title">권한 저장 확인</div>
            <div class="modal-message" id="saveModalMessage">
                판매자 권한을 저장하시겠습니까?
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeSaveModal()">취소</button>
                <button type="button" class="modal-btn modal-btn-confirm" onclick="confirmSave()">저장</button>
            </div>
        </div>
    </div>
    
    <script>
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
            
            // 모달 메시지 변경
            document.getElementById('saveModalMessage').textContent = '판매자 권한이 저장되었습니다.';
            
            // 폼 제출
            form.submit();
        }
        
        // 모달 외부 클릭 시 닫기
        document.getElementById('saveModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSaveModal();
            }
        });
    </script>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>




