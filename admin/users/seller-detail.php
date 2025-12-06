<?php
/**
 * 관리자용 판매자 상세 정보 페이지
 */

require_once __DIR__ . '/../includes/admin-header.php';

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

// 판매자 정보 가져오기
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
                    <div class="detail-value">
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
                    </div>
                </div>
                <?php if (isset($seller['approved_at'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">승인일</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['approved_at']); ?></div>
                    </div>
                <?php endif; ?>
                <?php if (isset($seller['held_at'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">보류일</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['held_at']); ?></div>
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
                    $permissions = $seller['permissions'] ?? [];
                    if (empty($permissions)) {
                        echo '<span class="no-permission">권한이 없습니다.</span>';
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
            <?php if (isset($seller['permissions_updated_at'])): ?>
                <div class="detail-item">
                    <div class="detail-label">권한 수정일</div>
                    <div class="detail-value"><?php echo htmlspecialchars($seller['permissions_updated_at']); ?></div>
                </div>
            <?php endif; ?>
            <div class="action-buttons">
                <a href="/MVNO/admin/seller-permissions.php?user_id=<?php echo urlencode($seller['user_id']); ?>" class="btn btn-primary">권한 설정</a>
            </div>
        </div>
        
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
                            <?php if (isset($seller['postal_code'])): ?>
                                <?php echo htmlspecialchars($seller['postal_code']); ?><br>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($seller['address'] ?? ''); ?>
                            <?php if (isset($seller['address_detail'])): ?>
                                <?php echo htmlspecialchars($seller['address_detail']); ?>
                            <?php endif; ?>
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
        
        <!-- 관리 작업 -->
        <div class="detail-card">
            <h2 class="detail-card-title">관리 작업</h2>
            <div class="action-buttons" style="border-top: none; padding-top: 0;">
                <?php if (!isset($seller['seller_approved']) || $seller['seller_approved'] !== true): ?>
                    <form method="POST" action="/MVNO/admin/seller-approval.php" style="display: inline;">
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($seller['user_id']); ?>">
                        <button type="submit" name="approve_seller" class="btn btn-success">승인</button>
                    </form>
                    <?php if (!isset($seller['approval_status']) || $seller['approval_status'] !== 'on_hold'): ?>
                        <form method="POST" action="/MVNO/admin/seller-approval.php" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($seller['user_id']); ?>">
                            <button type="submit" name="hold_seller" class="btn btn-warning">승인보류</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="/MVNO/admin/users/member-list.php?search=<?php echo urlencode($seller['user_id']); ?>" class="btn btn-primary">회원 목록에서 보기</a>
            </div>
        </div>
        
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

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
