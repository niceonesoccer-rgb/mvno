<?php
/**
 * 인터넷 상품 목록 페이지
 * 경로: /seller/products/internet-list.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/product-functions.php';

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

// 필터 파라미터
$status = $_GET['status'] ?? '';
if ($status === '') {
    $status = null;
}
$registrationPlace = $_GET['registration_place'] ?? '';
$speedOption = $_GET['speed_option'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = isset($_GET['per_page']) ? max(10, min(100, intval($_GET['per_page']))) : 10;

// DB에서 인터넷 상품 목록 가져오기
$products = [];
$totalProducts = 0;
$totalPages = 1;

try {
    $pdo = getDBConnection();
    if ($pdo) {
        // WHERE 조건 구성
        $sellerId = (string)$currentUser['user_id'];
        $whereConditions = ['p.seller_id = :seller_id', "p.product_type = 'internet'"];
        $params = [':seller_id' => $sellerId];
        
        // 상태 필터
        if ($status && $status !== '') {
            $whereConditions[] = 'p.status = :status';
            $params[':status'] = $status;
        } else {
            $whereConditions[] = "p.status != 'deleted'";
        }
        
        // 가입처 필터
        if ($registrationPlace && $registrationPlace !== '') {
            $whereConditions[] = 'inet.registration_place = :registration_place';
            $params[':registration_place'] = $registrationPlace;
        }
        
        // 인터넷속도 필터
        if ($speedOption && $speedOption !== '') {
            $whereConditions[] = 'inet.speed_option = :speed_option';
            $params[':speed_option'] = $speedOption;
        }
        
        // 등록일 구간 필터
        if ($dateFrom && $dateFrom !== '') {
            $whereConditions[] = 'DATE(p.created_at) >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo && $dateTo !== '') {
            $whereConditions[] = 'DATE(p.created_at) <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // 전체 개수 조회
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM products p
            INNER JOIN product_internet_details inet ON p.id = inet.product_id
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $totalProducts = $countStmt->fetch()['total'];
        $totalPages = ceil($totalProducts / $perPage);
        
        // 상품 목록 조회
        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                inet.registration_place AS provider,
                inet.speed_option AS speed_option,
                inet.monthly_fee AS monthly_fee
            FROM products p
            INNER JOIN product_internet_details inet ON p.id = inet.product_id
            WHERE {$whereClause}
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $products = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error fetching Internet products: " . $e->getMessage());
}

// 페이지별 스타일 (mvno-list.php와 동일)
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
        flex-wrap: wrap;
        gap: 16px;
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
        align-items: flex-start;
        flex-wrap: wrap;
    }
    
    .filter-row {
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
        width: 100%;
    }
    
    .filter-row:first-child {
        margin-bottom: 16px;
    }
    
    .date-input-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .date-input {
        padding: 8px 12px;
        font-size: 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: white;
    }
    
    .filter-bar-left {
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .filter-bar-right {
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .btn-reset {
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        border-radius: 6px;
        background: #6b7280;
        color: white;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-reset:hover {
        background: #4b5563;
        color: white;
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
    
    .btn-copy {
        background: #10b981;
        color: white;
    }
    
    .btn-copy:hover {
        background: #059669;
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
        align-items: center;
        gap: 8px;
        margin-top: 24px;
        padding: 20px;
        flex-wrap: nowrap;
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
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
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
    
    @media (max-width: 768px) {
        .product-table {
            font-size: 12px;
        }
        
        .product-table th,
        .product-table td {
            padding: 12px 8px;
        }
    }
';

include __DIR__ . '/../includes/seller-header.php';
?>

<div class="product-list-container">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success" style="margin-bottom: 24px; padding: 16px; border-radius: 8px; background: #d1fae5; color: #065f46; border: 1px solid #10b981;">
            상품이 성공적으로 처리되었습니다.
        </div>
    <?php endif; ?>
    
    <!-- 필터 바 -->
    <div class="filter-bar">
        <div style="flex: 1; min-width: 0;">
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">상태:</label>
                    <select class="filter-select" id="filter_status" onchange="applyFilters()">
                        <option value="">전체</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>판매중</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>판매종료</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">가입처:</label>
                    <select class="filter-select" id="filter_registration_place" onchange="applyFilters()">
                        <option value="">전체</option>
                        <option value="KT" <?php echo $registrationPlace === 'KT' ? 'selected' : ''; ?>>KT</option>
                        <option value="SKT" <?php echo $registrationPlace === 'SKT' ? 'selected' : ''; ?>>SKT</option>
                        <option value="LG U+" <?php echo $registrationPlace === 'LG U+' ? 'selected' : ''; ?>>LG U+</option>
                        <option value="KT skylife" <?php echo $registrationPlace === 'KT skylife' ? 'selected' : ''; ?>>KT skylife</option>
                        <option value="LG헬로비전" <?php echo $registrationPlace === 'LG헬로비전' ? 'selected' : ''; ?>>LG헬로비전</option>
                        <option value="BTV" <?php echo $registrationPlace === 'BTV' ? 'selected' : ''; ?>>BTV</option>
                        <option value="DLIVE" <?php echo $registrationPlace === 'DLIVE' ? 'selected' : ''; ?>>DLIVE</option>
                        <option value="기타" <?php echo $registrationPlace === '기타' ? 'selected' : ''; ?>>기타</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">인터넷속도:</label>
                    <select class="filter-select" id="filter_speed_option" onchange="applyFilters()">
                        <option value="">전체</option>
                        <option value="100M" <?php echo $speedOption === '100M' ? 'selected' : ''; ?>>100M</option>
                        <option value="500M" <?php echo $speedOption === '500M' ? 'selected' : ''; ?>>500M</option>
                        <option value="1G" <?php echo $speedOption === '1G' ? 'selected' : ''; ?>>1G</option>
                        <option value="2.5G" <?php echo $speedOption === '2.5G' ? 'selected' : ''; ?>>2.5G</option>
                        <option value="5G" <?php echo $speedOption === '5G' ? 'selected' : ''; ?>>5G</option>
                        <option value="10G" <?php echo $speedOption === '10G' ? 'selected' : ''; ?>>10G</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">등록일:</label>
                    <div class="date-input-group">
                        <input type="date" class="date-input" id="filter_date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" onchange="applyFilters()">
                        <span style="color: #6b7280;">~</span>
                        <input type="date" class="date-input" id="filter_date_to" value="<?php echo htmlspecialchars($dateTo); ?>" onchange="applyFilters()">
                    </div>
                </div>
                
                <div class="filter-group">
                    <button type="button" class="btn-reset" onclick="resetFilters()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                            <path d="M21 3v5h-5"/>
                            <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                            <path d="M3 21v-5h5"/>
                        </svg>
                        초기화
                    </button>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">페이지수:</label>
                    <select class="filter-select" id="filter_per_page" onchange="applyFilters()">
                        <option value="10" <?php echo ($perPage === 10 || !isset($_GET['per_page'])) ? 'selected' : ''; ?>>10개</option>
                        <option value="20" <?php echo $perPage === 20 ? 'selected' : ''; ?>>20개</option>
                        <option value="30" <?php echo $perPage === 30 ? 'selected' : ''; ?>>30개</option>
                        <option value="50" <?php echo $perPage === 50 ? 'selected' : ''; ?>>50개</option>
                        <option value="100" <?php echo $perPage === 100 ? 'selected' : ''; ?>>100개</option>
                    </select>
                </div>
            </div>
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
                <div class="empty-state-text">새로운 인터넷 상품을 등록해보세요</div>
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    <a href="/MVNO/seller/products/internet.php" class="btn btn-primary">인터넷 등록</a>
                </div>
            </div>
        <?php else: ?>
            <table class="product-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">
                            <div style="display: flex; flex-direction: column; gap: 8px; align-items: center;">
                                <button class="btn btn-sm" onclick="bulkInactive()" style="background: #ef4444; color: white; padding: 4px 8px; font-size: 12px; border-radius: 4px; border: none; cursor: pointer; width: 60px; white-space: nowrap;">판매종료</button>
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="cursor: pointer;">
                            </div>
                        </th>
                        <th>상품등록번호</th>
                        <th>가입처</th>
                        <th>인터넷속도</th>
                        <th>월 요금</th>
                        <th>조회수</th>
                        <th>찜</th>
                        <th>신청</th>
                        <th>상태</th>
                        <th>등록일</th>
                        <th>관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $index => $product): ?>
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" class="product-checkbox" value="<?php echo $product['id']; ?>" style="cursor: pointer;">
                            </td>
                            <td><?php 
                                $productNumber = getProductNumberByType($product['id'], 'internet');
                                echo $productNumber ? htmlspecialchars($productNumber) : htmlspecialchars($product['id'] ?? '-');
                            ?></td>
                            <td><?php echo htmlspecialchars($product['provider'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($product['speed_option'] ?? '-'); ?></td>
                            <td><?php echo number_format($product['monthly_fee'] ?? 0); ?>원</td>
                            <td><?php echo number_format($product['view_count'] ?? 0); ?></td>
                            <td><?php echo number_format($product['favorite_count'] ?? 0); ?></td>
                            <td><?php echo number_format($product['application_count'] ?? 0); ?></td>
                            <td>
                                <span class="badge <?php echo ($product['status'] ?? 'active') === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo ($product['status'] ?? 'active') === 'active' ? '판매중' : '판매종료'; ?>
                                </span>
                            </td>
                            <td><?php echo isset($product['created_at']) ? date('Y-m-d', strtotime($product['created_at'])) : '-'; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)">수정</button>
                                    <button class="btn btn-sm btn-copy" onclick="copyProduct(<?php echo $product['id']; ?>)">복사</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php
                    $paginationParams = [];
                    if ($status) $paginationParams['status'] = $status;
                    if ($registrationPlace) $paginationParams['registration_place'] = $registrationPlace;
                    if ($speedOption) $paginationParams['speed_option'] = $speedOption;
                    if ($dateFrom) $paginationParams['date_from'] = $dateFrom;
                    if ($dateTo) $paginationParams['date_to'] = $dateTo;
                    if ($perPage != 10) $paginationParams['per_page'] = $perPage;
                    $paginationQuery = http_build_query($paginationParams);
                    ?>
                    <a href="?<?php echo $paginationQuery; ?>&page=<?php echo max(1, $page - 1); ?>" 
                       class="pagination-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">이전</a>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?<?php echo $paginationQuery; ?>&page=<?php echo $i; ?>" 
                           class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <a href="?<?php echo $paginationQuery; ?>&page=<?php echo min($totalPages, $page + 1); ?>" 
                       class="pagination-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">다음</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function applyFilters() {
    const status = document.getElementById('filter_status').value;
    const registrationPlace = document.getElementById('filter_registration_place').value;
    const speedOption = document.getElementById('filter_speed_option').value;
    const dateFrom = document.getElementById('filter_date_from').value;
    const dateTo = document.getElementById('filter_date_to').value;
    const perPage = document.getElementById('filter_per_page').value;
    const params = new URLSearchParams();
    
    if (status && status !== '') {
        params.set('status', status);
    }
    if (registrationPlace && registrationPlace !== '') {
        params.set('registration_place', registrationPlace);
    }
    if (speedOption && speedOption !== '') {
        params.set('speed_option', speedOption);
    }
    if (dateFrom && dateFrom !== '') {
        params.set('date_from', dateFrom);
    }
    if (dateTo && dateTo !== '') {
        params.set('date_to', dateTo);
    }
    if (perPage && perPage !== '10') {
        params.set('per_page', perPage);
    }
    params.delete('page');
    
    window.location.href = '?' + params.toString();
}

function resetFilters() {
    // 모든 필터를 기본값으로 초기화하고 페이지 이동
    window.location.href = window.location.pathname;
}

function editProduct(productId) {
    window.location.href = '/MVNO/seller/products/internet.php?id=' + productId;
}

function copyProduct(productId) {
    const message = '이 상품을 복사하시겠습니까?\n\n※ 복사된 상품은 판매종료 상태로 설정됩니다.';
    if (typeof showConfirm === 'function') {
        showConfirm(message, '상품 복사').then(confirmed => {
            if (confirmed) {
                processCopyProduct(productId);
            }
        });
    } else if (confirm(message)) {
        processCopyProduct(productId);
    }
}

function processCopyProduct(productId) {
    fetch('/MVNO/api/product-copy.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            product_type: 'internet'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showAlert === 'function') {
                showAlert('상품이 복사되었습니다.\n복사된 상품은 판매종료 상태로 설정되었습니다.', '완료');
            } else {
                alert('상품이 복사되었습니다.\n복사된 상품은 판매종료 상태로 설정되었습니다.');
            }
            location.reload();
        } else {
            if (typeof showAlert === 'function') {
                showAlert(data.message || '상품 복사에 실패했습니다.', '오류', true);
            } else {
                alert(data.message || '상품 복사에 실패했습니다.');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showAlert === 'function') {
            showAlert('상품 복사 중 오류가 발생했습니다.', '오류', true);
        } else {
            alert('상품 복사 중 오류가 발생했습니다.');
        }
    });
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function setProductInactive(productId) {
    const message = '이 상품을 판매종료 처리하시겠습니까?';
    if (typeof showConfirm === 'function') {
        showConfirm(message, '판매종료 확인').then(confirmed => {
            if (confirmed) {
                processSetProductInactive(productId);
            }
        });
    } else if (confirm(message)) {
        processSetProductInactive(productId);
    }
}

function processSetProductInactive(productId) {
    fetch('/MVNO/api/product-bulk-update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_ids: [productId],
            status: 'inactive'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showAlert === 'function') {
                showAlert('상품이 판매종료 처리되었습니다.', '완료');
            } else {
                alert('상품이 판매종료 처리되었습니다.');
            }
            location.reload();
        } else {
            if (typeof showAlert === 'function') {
                showAlert(data.message || '상품 상태 변경에 실패했습니다.', '오류', true);
            } else {
                alert(data.message || '상품 상태 변경에 실패했습니다.');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showAlert === 'function') {
            showAlert('상품 상태 변경 중 오류가 발생했습니다.', '오류', true);
        } else {
            alert('상품 상태 변경 중 오류가 발생했습니다.');
        }
    });
}

function bulkInactive() {
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    if (checkboxes.length === 0) {
        if (typeof showAlert === 'function') {
            showAlert('선택된 상품이 없습니다.', '알림');
        } else {
            alert('선택된 상품이 없습니다.');
        }
        return;
    }
    
    const productCount = checkboxes.length;
    const message = '선택한 ' + productCount + '개의 상품을 판매종료 처리하시겠습니까?';
    
    if (typeof showConfirm === 'function') {
        showConfirm(message, '판매종료 확인').then(confirmed => {
            if (confirmed) {
                processBulkInactive(checkboxes);
            }
        });
    } else if (confirm(message)) {
        processBulkInactive(checkboxes);
    }
}

function processBulkInactive(checkboxes) {
    const productIds = Array.from(checkboxes).map(cb => cb.value);
    
    fetch('/MVNO/api/product-bulk-update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_ids: productIds,
            status: 'inactive'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showAlert === 'function') {
                showAlert('선택한 상품이 판매종료 처리되었습니다.', '완료');
            } else {
                alert('선택한 상품이 판매종료 처리되었습니다.');
            }
            location.reload();
        } else {
            if (typeof showAlert === 'function') {
                showAlert(data.message || '상품 상태 변경에 실패했습니다.', '오류', true);
            } else {
                alert(data.message || '상품 상태 변경에 실패했습니다.');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showAlert === 'function') {
            showAlert('상품 상태 변경 중 오류가 발생했습니다.', '오류', true);
        } else {
            alert('상품 상태 변경 중 오류가 발생했습니다.');
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>

