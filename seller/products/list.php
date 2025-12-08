<?php
/**
 * 판매자 상품 목록 페이지
 * 경로: /seller/products/list.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';

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

// 판매자 승인 체크
$approvalStatus = $currentUser['approval_status'] ?? 'pending';
if ($approvalStatus !== 'approved') {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

// 탈퇴 요청 상태 확인
if (isset($currentUser['withdrawal_requested']) && $currentUser['withdrawal_requested'] === true) {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

// 페이지별 스타일
$pageStyles = '
    .product-list-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
    }
    
    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .page-header p {
        font-size: 16px;
        color: #6b7280;
    }
    
    .btn {
        padding: 12px 24px;
        font-size: 15px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: #10b981;
        color: white;
    }
    
    .btn-primary:hover {
        background: #059669;
    }
    
    .filter-bar {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
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
    
    .filter-label {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
    }
    
    .filter-select {
        padding: 8px 12px;
        font-size: 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: white;
        cursor: pointer;
    }
    
    .product-table-wrapper {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .product-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .product-table thead {
        background: #f9fafb;
    }
    
    .product-table th {
        padding: 16px;
        text-align: left;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .product-table td {
        padding: 16px;
        font-size: 14px;
        color: #1f2937;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .product-table tbody tr:hover {
        background: #f9fafb;
    }
    
    .product-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 12px;
    }
    
    .badge-mvno {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .badge-mno {
        background: #fce7f3;
        color: #9f1239;
    }
    
    .badge-internet {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-active {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-inactive {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 13px;
        border-radius: 6px;
    }
    
    .btn-edit {
        background: #3b82f6;
        color: white;
    }
    
    .btn-edit:hover {
        background: #2563eb;
    }
    
    .btn-delete {
        background: #ef4444;
        color: white;
    }
    
    .btn-delete:hover {
        background: #dc2626;
    }
    
    .empty-state {
        padding: 60px 20px;
        text-align: center;
        color: #6b7280;
    }
    
    .empty-state-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        opacity: 0.5;
    }
    
    .empty-state-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
    }
    
    .empty-state-text {
        font-size: 14px;
        margin-bottom: 24px;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 24px;
        padding: 20px;
    }
    
    .pagination-btn {
        padding: 8px 16px;
        font-size: 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: white;
        color: #374151;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .pagination-btn:hover {
        background: #f9fafb;
        border-color: #10b981;
    }
    
    .pagination-btn.active {
        background: #10b981;
        color: white;
        border-color: #10b981;
    }
    
    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
';

include __DIR__ . '/../includes/seller-header.php';

// 필터 파라미터
$boardType = $_GET['board_type'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// 샘플 데이터 (실제로는 DB에서 가져옴)
$products = [];
// TODO: 실제 상품 데이터 로드
// 예: $products = getSellerProducts($currentUser['user_id'], $boardType, $status, $page, $perPage);
$totalProducts = 0;
$totalPages = 1;
?>

<div class="product-list-container">
    <div class="page-header">
        <div>
            <h1>상품 목록</h1>
            <p>등록한 상품을 관리하세요</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="/MVNO/seller/products/mvno.php" class="btn btn-primary">알뜰폰 등록</a>
            <a href="/MVNO/seller/products/mno.php" class="btn btn-primary">통신사폰 등록</a>
            <a href="/MVNO/seller/products/internet.php" class="btn btn-primary">인터넷 등록</a>
        </div>
    </div>
    
    <!-- 필터 바 -->
    <div class="filter-bar">
        <div class="filter-group">
            <label class="filter-label">게시판:</label>
            <select class="filter-select" id="filter_board_type" onchange="applyFilters()">
                <option value="">전체</option>
                <option value="mvno" <?php echo $boardType === 'mvno' ? 'selected' : ''; ?>>알뜰폰</option>
                <option value="mno" <?php echo $boardType === 'mno' ? 'selected' : ''; ?>>통신사폰</option>
                <option value="internet" <?php echo $boardType === 'internet' ? 'selected' : ''; ?>>인터넷</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label class="filter-label">상태:</label>
            <select class="filter-select" id="filter_status" onchange="applyFilters()">
                <option value="">전체</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>판매중</option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>판매종료</option>
            </select>
        </div>
    </div>
    
    <!-- 상품 테이블 -->
    <div class="product-table-wrapper">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
                <div class="empty-state-title">등록된 상품이 없습니다</div>
                <div class="empty-state-text">새로운 상품을 등록해보세요</div>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <a href="/MVNO/seller/products/mvno.php" class="btn btn-primary">알뜰폰 등록</a>
                    <a href="/MVNO/seller/products/mno.php" class="btn btn-primary">통신사폰 등록</a>
                    <a href="/MVNO/seller/products/internet.php" class="btn btn-primary">인터넷 등록</a>
                </div>
            </div>
        <?php else: ?>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>번호</th>
                        <th>게시판</th>
                        <th>요금제명</th>
                        <th>통신사</th>
                        <th>월 요금</th>
                        <th>상태</th>
                        <th>등록일</th>
                        <th>관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $index => $product): ?>
                        <tr>
                            <td><?php echo ($page - 1) * $perPage + $index + 1; ?></td>
                            <td>
                                <?php
                                $boardTypeLabels = [
                                    'mvno' => '알뜰폰',
                                    'mno' => '통신사폰',
                                    'internet' => '인터넷'
                                ];
                                $boardTypeLabel = $boardTypeLabels[$product['board_type']] ?? $product['board_type'];
                                $boardTypeClass = 'badge-' . $product['board_type'];
                                ?>
                                <span class="badge <?php echo $boardTypeClass; ?>"><?php echo htmlspecialchars($boardTypeLabel); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($product['plan_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($product['provider'] ?? '-'); ?></td>
                            <td><?php echo number_format($product['monthly_fee'] ?? 0); ?>원</td>
                            <td>
                                <span class="badge <?php echo ($product['status'] ?? 'active') === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo ($product['status'] ?? 'active') === 'active' ? '판매중' : '판매종료'; ?>
                                </span>
                            </td>
                            <td><?php echo isset($product['created_at']) ? date('Y-m-d', strtotime($product['created_at'])) : '-'; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-edit" onclick="editProduct(<?php echo $product['id'] ?? 0; ?>)">수정</button>
                                    <button class="btn btn-sm btn-delete" onclick="deleteProduct(<?php echo $product['id'] ?? 0; ?>)">삭제</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page - 1); ?>&board_type=<?php echo htmlspecialchars($boardType); ?>&status=<?php echo htmlspecialchars($status); ?>" 
                       class="pagination-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">이전</a>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&board_type=<?php echo htmlspecialchars($boardType); ?>&status=<?php echo htmlspecialchars($status); ?>" 
                           class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <a href="?page=<?php echo min($totalPages, $page + 1); ?>&board_type=<?php echo htmlspecialchars($boardType); ?>&status=<?php echo htmlspecialchars($status); ?>" 
                       class="pagination-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">다음</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function applyFilters() {
    const boardType = document.getElementById('filter_board_type').value;
    const status = document.getElementById('filter_status').value;
    
    const params = new URLSearchParams();
    if (boardType) params.append('board_type', boardType);
    if (status) params.append('status', status);
    
    window.location.href = '?' + params.toString();
}

function editProduct(productId) {
    // TODO: 상품 수정 페이지로 이동
    alert('상품 수정 기능은 준비 중입니다. (상품 ID: ' + productId + ')');
}

function deleteProduct(productId) {
    if (confirm('정말로 이 상품을 삭제하시겠습니까?')) {
        // TODO: 상품 삭제 API 호출
        alert('상품 삭제 기능은 준비 중입니다. (상품 ID: ' + productId + ')');
    }
}
</script>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>






