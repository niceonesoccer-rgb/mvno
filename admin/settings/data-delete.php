<?php
/**
 * 데이터 삭제 관리 페이지
 * 경로: /MVNO/admin/settings/data-delete.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 체크
if (!isAdmin()) {
    header('Location: /MVNO/admin/');
    exit;
}

$currentUser = getCurrentUser();
$error = '';
$success = '';
$deleteResult = null;

// 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $confirmText = $_POST['confirm_text'] ?? '';
    
    // 확인 텍스트 검증
    if ($confirmText !== '삭제') {
        $error = '확인 텍스트가 올바르지 않습니다. "삭제"를 정확히 입력해주세요.';
    } else {
        try {
            switch ($action) {
                case 'delete_users':
                    // 일반회원 삭제
                    $pdo = getDBConnection();
                    if (!$pdo) throw new Exception('DB 연결에 실패했습니다.');

                    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
                    $beforeCount = (int)$stmt->fetchColumn();

                    $pdo->beginTransaction();
                    $pdo->prepare("DELETE FROM users WHERE role = 'user'")->execute();
                    $pdo->commit();

                    $deleteResult = ['type' => '일반회원', 'count' => $beforeCount];
                    $success = "일반회원 {$beforeCount}명이 삭제되었습니다.";
                    break;
                    
                case 'delete_sellers':
                    // 판매자 삭제
                    $pdo = getDBConnection();
                    if (!$pdo) throw new Exception('DB 연결에 실패했습니다.');

                    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'");
                    $beforeCount = (int)$stmt->fetchColumn();

                    $pdo->beginTransaction();
                    $pdo->prepare("DELETE FROM seller_profiles")->execute();
                    $pdo->prepare("DELETE FROM users WHERE role = 'seller'")->execute();
                    $pdo->commit();

                    $deleteResult = ['type' => '판매자', 'count' => $beforeCount];
                    $success = "판매자 {$beforeCount}명이 삭제되었습니다.";
                    break;
                    
                case 'delete_sub_admins':
                    // 부관리자 삭제 (admin 제외)
                    $pdo = getDBConnection();
                    if (!$pdo) throw new Exception('DB 연결에 실패했습니다.');

                    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'sub_admin'");
                    $subAdminCount = (int)$stmt->fetchColumn();

                    $pdo->beginTransaction();
                    $pdo->prepare("DELETE FROM admin_profiles WHERE user_id <> 'admin'")->execute();
                    $pdo->prepare("DELETE FROM users WHERE role = 'sub_admin'")->execute();
                    $pdo->commit();

                    $deleteResult = ['type' => '부관리자', 'count' => $subAdminCount];
                    $success = "부관리자 {$subAdminCount}명이 삭제되었습니다. (admin 계정은 보존되었습니다)";
                    break;
                    
                case 'delete_orders':
                    // 주문정보 삭제
                    $pdo = getDBConnection();
                    if ($pdo) {
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                        
                        // 삭제 전 개수 확인
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_applications");
                        $appCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                        
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM application_customers");
                        $customerCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                        
                        // 삭제 실행
                        $pdo->exec('TRUNCATE TABLE application_customers');
                        $pdo->exec('TRUNCATE TABLE product_applications');
                        
                        // products 테이블의 application_count 초기화
                        $pdo->exec('UPDATE products SET application_count = 0');
                        
                        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                        
                        $deleteResult = [
                            'type' => '주문정보',
                            'count' => $appCount + $customerCount,
                            'details' => [
                                'applications' => $appCount,
                                'customers' => $customerCount
                            ]
                        ];
                        $success = "주문정보가 삭제되었습니다. (신청: {$appCount}건, 고객정보: {$customerCount}건)";
                    }
                    break;
                    
                case 'delete_products':
                    // 등록상품 삭제
                    $pdo = getDBConnection();
                    if ($pdo) {
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                        
                        // 삭제 전 개수 확인
                        $counts = [];
                        $tables = [
                            'products',
                            'product_mvno_details',
                            'product_mno_details',
                            'product_internet_details',
                            'product_reviews',
                            'product_favorites',
                            'product_shares'
                        ];
                        
                        foreach ($tables as $table) {
                            try {
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $counts[$table] = $result['count'] ?? 0;
                            } catch (PDOException $e) {
                                $counts[$table] = 0;
                            }
                        }
                        
                        // 테이블 존재 여부 확인 함수
                        $tableExists = function($tableName) use ($pdo) {
                            try {
                                $stmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
                                return $stmt->rowCount() > 0;
                            } catch (PDOException $e) {
                                return false;
                            }
                        };
                        
                        // 삭제 실행
                        if ($tableExists('product_reviews')) {
                            $pdo->exec('TRUNCATE TABLE product_reviews');
                        }
                        if ($tableExists('product_favorites')) {
                            $pdo->exec('TRUNCATE TABLE product_favorites');
                        }
                        if ($tableExists('product_shares')) {
                            $pdo->exec('TRUNCATE TABLE product_shares');
                        }
                        if ($tableExists('product_mvno_details')) {
                            $pdo->exec('TRUNCATE TABLE product_mvno_details');
                        }
                        if ($tableExists('product_mno_details')) {
                            $pdo->exec('TRUNCATE TABLE product_mno_details');
                        }
                        if ($tableExists('product_internet_details')) {
                            $pdo->exec('TRUNCATE TABLE product_internet_details');
                        }
                        if ($tableExists('products')) {
                            $pdo->exec('TRUNCATE TABLE products');
                        }
                        
                        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                        
                        $totalCount = array_sum($counts);
                        $deleteResult = [
                            'type' => '등록상품',
                            'count' => $totalCount,
                            'details' => $counts
                        ];
                        $success = "등록상품이 삭제되었습니다. (총 {$totalCount}건)";
                    }
                    break;
            }
        } catch (Exception $e) {
            $error = '삭제 중 오류가 발생했습니다: ' . $e->getMessage();
        }
    }
}

// 현재 통계 가져오기
$stats = [
    'users' => 0,
    'sellers' => 0,
    'sub_admins' => 0,
    'orders' => 0,
    'products' => 0
];

// 사용자 통계 (DB-only)
$pdo = getDBConnection();
if ($pdo) {
    try {
        $stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
        $stats['sellers'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn();
        $stats['sub_admins'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'sub_admin'")->fetchColumn();
    } catch (PDOException $e) {
        // ignore
    }
}

// 주문 수
$pdo = getDBConnection();
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_applications");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['orders'] = $result['count'] ?? 0;
    } catch (PDOException $e) {
        $stats['orders'] = 0;
    }
    
    // 상품 수
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['products'] = $result['count'] ?? 0;
    } catch (PDOException $e) {
        $stats['products'] = 0;
    }
}

// 현재 페이지 설정
$currentPage = 'data-delete.php';

// 헤더 포함
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="content-header">
        <h1>데이터 삭제 관리</h1>
        <p class="content-description">회원정보, 주문정보, 등록상품을 삭제할 수 있습니다. 삭제된 데이터는 복구할 수 없으니 주의하세요.</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <strong>오류:</strong> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <strong>성공:</strong> <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>

    <div class="data-delete-container">
        <!-- 회원정보 삭제 -->
        <div class="delete-section">
            <h2>회원정보 삭제</h2>
            <div class="delete-cards">
                <!-- 일반회원 삭제 -->
                <div class="delete-card">
                    <div class="delete-card-header">
                        <h3>일반회원</h3>
                        <span class="count-badge"><?php echo number_format($stats['users']); ?>명</span>
                    </div>
                    <p class="delete-description">일반회원 정보를 모두 삭제합니다.</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_users', '일반회원', <?php echo $stats['users']; ?>)">
                        삭제하기
                    </button>
                </div>

                <!-- 판매자 삭제 -->
                <div class="delete-card">
                    <div class="delete-card-header">
                        <h3>판매자</h3>
                        <span class="count-badge"><?php echo number_format($stats['sellers']); ?>명</span>
                    </div>
                    <p class="delete-description">판매자 정보를 모두 삭제합니다.</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_sellers', '판매자', <?php echo $stats['sellers']; ?>)">
                        삭제하기
                    </button>
                </div>

                <!-- 부관리자 삭제 -->
                <div class="delete-card">
                    <div class="delete-card-header">
                        <h3>부관리자</h3>
                        <span class="count-badge"><?php echo number_format($stats['sub_admins']); ?>명</span>
                    </div>
                    <p class="delete-description">부관리자 정보를 모두 삭제합니다. (admin 계정은 제외)</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_sub_admins', '부관리자', <?php echo $stats['sub_admins']; ?>)">
                        삭제하기
                    </button>
                </div>
            </div>
        </div>

        <!-- 주문정보 삭제 -->
        <div class="delete-section">
            <h2>주문정보 삭제</h2>
            <div class="delete-card">
                <div class="delete-card-header">
                    <h3>주문/신청 내역</h3>
                    <span class="count-badge"><?php echo number_format($stats['orders']); ?>건</span>
                </div>
                <p class="delete-description">모든 주문 및 신청 내역과 고객 정보를 삭제합니다.</p>
                <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_orders', '주문정보', <?php echo $stats['orders']; ?>)">
                    삭제하기
                </button>
            </div>
        </div>

        <!-- 등록상품 삭제 -->
        <div class="delete-section">
            <h2>등록상품 삭제</h2>
            <div class="delete-card">
                <div class="delete-card-header">
                    <h3>등록된 상품</h3>
                    <span class="count-badge"><?php echo number_format($stats['products']); ?>개</span>
                </div>
                <p class="delete-description">모든 등록된 상품과 관련 정보(리뷰, 찜, 공유 등)를 삭제합니다.</p>
                <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_products', '등록상품', <?php echo $stats['products']; ?>)">
                    삭제하기
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>⚠️ 삭제 확인</h2>
            <button type="button" class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="warning-box">
                <p><strong id="deleteType"></strong>를(을) 삭제하시겠습니까?</p>
                <p>삭제될 데이터: <strong id="deleteCount"></strong></p>
                <p style="color: #dc3545; font-weight: bold; margin-top: 15px;">
                    ⚠️ 이 작업은 되돌릴 수 없습니다!
                </p>
            </div>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" id="deleteAction">
                <div class="form-group">
                    <label for="confirm_text">확인을 위해 <strong>"삭제"</strong>를 입력하세요:</label>
                    <input type="text" id="confirm_text" name="confirm_text" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">취소</button>
                    <button type="submit" class="btn btn-danger">삭제 실행</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.data-delete-container {
    max-width: 1200px;
    margin: 0 auto;
}

.delete-section {
    margin-bottom: 40px;
    padding: 24px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.delete-section h2 {
    margin: 0 0 20px 0;
    font-size: 20px;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 12px;
}

.delete-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.delete-card {
    padding: 20px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.delete-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.delete-card-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.count-badge {
    background: #dc3545;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
}

.delete-description {
    color: #666;
    font-size: 14px;
    margin-bottom: 16px;
    line-height: 1.5;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: #dc3545;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    color: #333;
}

.modal-body {
    padding: 20px;
}

.warning-box {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 6px;
    padding: 16px;
    margin-bottom: 20px;
}

.warning-box p {
    margin: 8px 0;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}
</style>

<script>
function showDeleteModal(action, type, count) {
    document.getElementById('deleteAction').value = action;
    document.getElementById('deleteType').textContent = type;
    document.getElementById('deleteCount').textContent = count + (type.includes('회원') || type.includes('관리자') ? '명' : type.includes('상품') ? '개' : '건');
    document.getElementById('confirm_text').value = '';
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.getElementById('deleteForm').reset();
}

// 모달 외부 클릭 시 닫기
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/admin-footer.php';
?>






















