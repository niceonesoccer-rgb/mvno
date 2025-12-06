<?php
/**
 * 판매자 내정보 확인 페이지
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: /MVNO/seller/login.php');
    exit;
}

// 판매자 승인 체크 - 승인되지 않은 경우 waiting.php로 리다이렉트
if (!isset($currentUser['approval_status']) || $currentUser['approval_status'] !== 'approved') {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

// 페이지별 스타일
$pageStyles = '
    .profile-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .profile-header {
        margin-bottom: 32px;
    }
    
    .profile-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .profile-header p {
        font-size: 16px;
        color: #6b7280;
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
        max-width: 400px;
        max-height: 300px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-top: 12px;
    }
    
        .full-width {
            grid-column: 1 / -1;
        }
        
        .withdrawal-section {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 2px solid #fee2e2;
        }
        
        .withdrawal-warning {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }
        
        .withdrawal-warning-title {
            font-size: 14px;
            font-weight: 700;
            color: #991b1b;
            margin-bottom: 8px;
        }
        
        .withdrawal-warning-text {
            font-size: 13px;
            color: #7f1d1d;
            line-height: 1.6;
        }
        
        .withdrawal-warning-list {
            margin-top: 12px;
            padding-left: 20px;
        }
        
        .withdrawal-warning-list li {
            font-size: 12px;
            color: #7f1d1d;
            margin-bottom: 4px;
        }
        
        .btn-withdrawal {
            background: #ef4444;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-withdrawal:hover {
            background: #dc2626;
        }
        
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
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
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
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .form-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
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
        
        .modal-btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .modal-btn-danger:hover {
            background: #dc2626;
        }
';

include 'includes/seller-header.php';
?>

<div class="profile-container">
    <div class="profile-header">
        <h1>내정보</h1>
        <p>판매자 계정 정보를 확인할 수 있습니다.</p>
    </div>
    
    <div class="detail-grid">
        <!-- 기본 정보 -->
        <div class="detail-card">
            <h2 class="detail-card-title">기본 정보</h2>
            <div class="detail-item">
                <div class="detail-label">아이디</div>
                <div class="detail-value"><?php echo htmlspecialchars($currentUser['user_id'] ?? ''); ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">이름</div>
                <div class="detail-value"><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">이메일</div>
                <div class="detail-value"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">가입일</div>
                <div class="detail-value"><?php echo htmlspecialchars($currentUser['created_at'] ?? ''); ?></div>
            </div>
        </div>
        
        <!-- 판매자 상태 -->
        <div class="detail-card">
            <h2 class="detail-card-title">판매자 상태</h2>
            <div class="detail-item">
                <div class="detail-label">승인 상태</div>
                <div class="detail-value">
                    <?php 
                    $approvalStatus = $currentUser['approval_status'] ?? 'pending';
                    $isApproved = isset($currentUser['seller_approved']) && $currentUser['seller_approved'] === true;
                    
                    if ($isApproved || $approvalStatus === 'approved') {
                        echo '<span class="detail-badge badge-approved">승인됨</span>';
                    } elseif ($approvalStatus === 'on_hold') {
                        echo '<span class="detail-badge badge-on-hold">승인 보류</span>';
                    } else {
                        echo '<span class="detail-badge badge-pending">승인 대기</span>';
                    }
                    ?>
                </div>
            </div>
            <?php if (isset($currentUser['approved_at'])): ?>
                <div class="detail-item">
                    <div class="detail-label">승인일</div>
                    <div class="detail-value"><?php echo htmlspecialchars($currentUser['approved_at']); ?></div>
                </div>
            <?php endif; ?>
            <?php if (isset($currentUser['held_at'])): ?>
                <div class="detail-item">
                    <div class="detail-label">보류일</div>
                    <div class="detail-value"><?php echo htmlspecialchars($currentUser['held_at']); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 판매자 권한 정보 -->
    <div class="detail-card" style="margin-bottom: 24px;">
        <h2 class="detail-card-title">판매자 권한</h2>
        <div class="detail-item">
            <div class="detail-label">게시판 권한</div>
            <div class="detail-value">
                <?php 
                $permissions = $currentUser['permissions'] ?? [];
                if (empty($permissions)) {
                    echo '<span class="no-permission">권한이 없습니다. 관리자에게 문의하세요.</span>';
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
        <?php if (isset($currentUser['permissions_updated_at'])): ?>
            <div class="detail-item">
                <div class="detail-label">권한 수정일</div>
                <div class="detail-value"><?php echo htmlspecialchars($currentUser['permissions_updated_at']); ?></div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 사업자 정보 -->
    <?php if (isset($currentUser['business_number']) || isset($currentUser['company_name'])): ?>
        <div class="business-info-card">
            <h2 class="detail-card-title">사업자 정보</h2>
            
            <?php if (isset($currentUser['business_number'])): ?>
                <div class="detail-item">
                    <div class="detail-label">사업자등록번호</div>
                    <div class="detail-value"><?php echo htmlspecialchars($currentUser['business_number']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($currentUser['company_name'])): ?>
                <div class="detail-item">
                    <div class="detail-label">회사명</div>
                    <div class="detail-value"><?php echo htmlspecialchars($currentUser['company_name']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($currentUser['company_representative'])): ?>
                <div class="detail-item">
                    <div class="detail-label">대표자명</div>
                    <div class="detail-value"><?php echo htmlspecialchars($currentUser['company_representative']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($currentUser['business_type'])): ?>
                <div class="detail-item">
                    <div class="detail-label">업태</div>
                    <div class="detail-value"><?php echo htmlspecialchars($currentUser['business_type']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($currentUser['business_item'])): ?>
                <div class="detail-item">
                    <div class="detail-label">종목</div>
                    <div class="detail-value"><?php echo htmlspecialchars($currentUser['business_item']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($currentUser['phone'])): ?>
                <div class="detail-item">
                    <div class="detail-label">전화번호</div>
                    <div class="detail-value"><?php echo htmlspecialchars($currentUser['phone']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($currentUser['mobile'])): ?>
                <div class="detail-item">
                    <div class="detail-label">휴대폰</div>
                    <div class="detail-value"><?php echo htmlspecialchars($currentUser['mobile']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($currentUser['postal_code']) || isset($currentUser['address'])): ?>
                <div class="detail-item">
                    <div class="detail-label">주소</div>
                    <div class="detail-value">
                        <?php if (isset($currentUser['postal_code'])): ?>
                            <?php echo htmlspecialchars($currentUser['postal_code']); ?><br>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($currentUser['address'] ?? ''); ?>
                        <?php if (isset($currentUser['address_detail'])): ?>
                            <?php echo htmlspecialchars($currentUser['address_detail']); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($currentUser['business_license_image']) && !empty($currentUser['business_license_image'])): ?>
                <div class="detail-item">
                    <div class="detail-label">사업자등록증</div>
                    <div class="detail-value">
                        <img src="<?php echo htmlspecialchars($currentUser['business_license_image']); ?>" alt="사업자등록증" class="license-image">
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- 탈퇴 요청 섹션 -->
    <div class="detail-card withdrawal-section">
        <h2 class="detail-card-title" style="color: #dc2626;">계정 관리</h2>
        
        <div class="withdrawal-warning">
            <div class="withdrawal-warning-title">⚠️ 탈퇴 전 반드시 확인해주세요</div>
            <div class="withdrawal-warning-text">
                판매자 계정을 탈퇴하시면 다음 사항이 적용됩니다:
            </div>
            <ul class="withdrawal-warning-list">
                <li>판매자 계정이 비활성화되어 로그인이 불가능합니다.</li>
                <li><strong>등록하신 상품 정보는 모두 보존</strong>됩니다. (상품명, 가격, 설명 등 모든 정보)</li>
                <li><strong>고객의 구매 기록(신청내역, 주문 내역 등)은 모두 보존</strong>됩니다.</li>
                <li>등록하신 상품에 대한 리뷰, 주문 내역 등은 <strong>법적 보존 의무에 따라 보관</strong>됩니다.</li>
                <li>기존 등록 상품은 "판매 종료" 상태로 변경될 수 있으나, <strong>상품 정보와 고객 구매 기록은 그대로 보존</strong>됩니다.</li>
                <li>개인정보(이름, 이메일, 연락처, 주소 등)는 삭제되지만, <strong>등록 상품 정보와 고객의 주문/신청 기록은 그대로 유지</strong>됩니다.</li>
                <li>고객은 탈퇴 후에도 자신의 구매 이력 및 구매한 상품 정보를 확인할 수 있습니다.</li>
                <li>탈퇴 요청 후 관리자 승인을 거쳐 처리됩니다.</li>
                <li>탈퇴 요청은 취소할 수 있습니다.</li>
            </ul>
        </div>
        
        <?php 
        $withdrawalRequested = isset($currentUser['withdrawal_requested']) && $currentUser['withdrawal_requested'] === true;
        ?>
        
        <?php if ($withdrawalRequested): ?>
            <div style="padding: 16px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; margin-bottom: 16px;">
                <div style="font-weight: 600; color: #92400e; margin-bottom: 8px;">탈퇴 요청이 접수되었습니다</div>
                <div style="font-size: 13px; color: #78350f;">
                    요청일: <?php echo htmlspecialchars($currentUser['withdrawal_requested_at'] ?? ''); ?><br>
                    <?php if (isset($currentUser['withdrawal_reason']) && !empty($currentUser['withdrawal_reason'])): ?>
                        사유: <?php echo htmlspecialchars($currentUser['withdrawal_reason']); ?><br>
                    <?php endif; ?>
                    관리자 검토 후 처리됩니다.
                </div>
            </div>
            <button onclick="showCancelWithdrawalModal()" class="btn btn-secondary">탈퇴 요청 취소</button>
        <?php else: ?>
            <button onclick="showWithdrawalModal()" class="btn-withdrawal">판매자 계정 탈퇴 요청</button>
        <?php endif; ?>
    </div>
</div>

<!-- 탈퇴 요청 모달 -->
<div class="modal-overlay" id="withdrawalModal">
    <div class="modal">
        <div class="modal-title">판매자 계정 탈퇴 요청</div>
        <div class="modal-message">
            정말로 판매자 계정 탈퇴를 요청하시겠습니까?<br>
            <strong style="color: #ef4444;">탈퇴 요청 후 즉시 계정이 비활성화되며, 관리자 승인 후 완전히 처리됩니다.</strong>
        </div>
        <form id="withdrawalForm" onsubmit="requestWithdrawal(event)">
            <div class="form-group">
                <label class="form-label" for="withdrawalReason">탈퇴 사유 (선택사항)</label>
                <textarea 
                    id="withdrawalReason" 
                    name="reason" 
                    class="form-textarea" 
                    placeholder="탈퇴 사유를 입력해주세요. (선택사항)"
                ></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeWithdrawalModal()">취소</button>
                <button type="submit" class="modal-btn modal-btn-danger">탈퇴 요청</button>
            </div>
        </form>
    </div>
</div>

<!-- 탈퇴 요청 취소 모달 -->
<div class="modal-overlay" id="cancelWithdrawalModal">
    <div class="modal">
        <div class="modal-title">탈퇴 요청 취소</div>
        <div class="modal-message">
            탈퇴 요청을 취소하시겠습니까?<br>
            취소하시면 판매자 계정이 정상적으로 활성화됩니다.
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeCancelWithdrawalModal()">취소</button>
            <button type="button" class="modal-btn btn-primary" onclick="cancelWithdrawal()" style="background: #6366f1;">탈퇴 요청 취소</button>
        </div>
    </div>
</div>

<script>
    function showWithdrawalModal() {
        document.getElementById('withdrawalModal').classList.add('active');
    }
    
    function closeWithdrawalModal() {
        document.getElementById('withdrawalModal').classList.remove('active');
    }
    
    function showCancelWithdrawalModal() {
        document.getElementById('cancelWithdrawalModal').classList.add('active');
    }
    
    function closeCancelWithdrawalModal() {
        document.getElementById('cancelWithdrawalModal').classList.remove('active');
    }
    
    function requestWithdrawal(event) {
        event.preventDefault();
        
        if (!confirm('정말로 탈퇴를 요청하시겠습니까?\n요청 후 즉시 계정이 비활성화됩니다.')) {
            return;
        }
        
        const reason = document.getElementById('withdrawalReason').value;
        
        fetch('/MVNO/api/request-seller-withdrawal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('탈퇴 요청이 접수되었습니다.');
                window.location.reload();
            } else {
                alert('탈퇴 요청에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('탈퇴 요청 중 오류가 발생했습니다.');
        });
    }
    
    function cancelWithdrawal() {
        if (!confirm('탈퇴 요청을 취소하시겠습니까?')) {
            return;
        }
        
        fetch('/MVNO/api/cancel-seller-withdrawal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                confirm: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('탈퇴 요청이 취소되었습니다.');
                window.location.reload();
            } else {
                alert('취소에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('취소 중 오류가 발생했습니다.');
        });
    }
    
    // 모달 외부 클릭 시 닫기
    document.getElementById('withdrawalModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeWithdrawalModal();
        }
    });
    
    document.getElementById('cancelWithdrawalModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCancelWithdrawalModal();
        }
    });
</script>

<?php include 'includes/seller-footer.php'; ?>

