<?php
/**
 * 메인상품선택 관리 페이지
 * 경로: /MVNO/admin/content/home-product-select.php
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/home-functions.php';
require_once __DIR__ . '/../../includes/data/product-functions.php';

$error = '';
$success = '';
$pdo = getDBConnection();

// 현재 설정 가져오기
$home_settings = getHomeSettings();
$selected_mvno_plans = $home_settings['mvno_plans'] ?? [];
$selected_mno_phones = $home_settings['mno_phones'] ?? [];
$selected_mno_sim_plans = $home_settings['mno_sim_plans'] ?? [];
$selected_internet_products = $home_settings['internet_products'] ?? [];

// 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_products') {
    try {
        $home_settings = getHomeSettings();
        
        // 현재 탭의 상품만 업데이트하고, 다른 카테고리는 기존 설정 유지
        $currentTab = $_POST['current_tab'] ?? $activeTab;
        
        if ($currentTab === 'mvno') {
            // 알뜰폰만 업데이트
            if (isset($_POST['mvno_plans']) && is_array($_POST['mvno_plans'])) {
                $selectedIds = array_map('intval', $_POST['mvno_plans']);
                if (!empty($selectedIds) && $pdo) {
                    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                    $stmt = $pdo->prepare("SELECT id FROM products WHERE id IN ($placeholders) AND status = 'active'");
                    $stmt->execute($selectedIds);
                    $activeIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $home_settings['mvno_plans'] = $activeIds;
                } else {
                    $home_settings['mvno_plans'] = [];
                }
            } else {
                $home_settings['mvno_plans'] = [];
            }
        } elseif ($currentTab === 'mno') {
            // 통신사폰만 업데이트
            if (isset($_POST['mno_phones']) && is_array($_POST['mno_phones'])) {
                $selectedIds = array_map('intval', $_POST['mno_phones']);
                if (!empty($selectedIds) && $pdo) {
                    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                    $stmt = $pdo->prepare("SELECT id FROM products WHERE id IN ($placeholders) AND status = 'active'");
                    $stmt->execute($selectedIds);
                    $activeIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $home_settings['mno_phones'] = $activeIds;
                } else {
                    $home_settings['mno_phones'] = [];
                }
            } else {
                $home_settings['mno_phones'] = [];
            }
        } elseif ($currentTab === 'mno-sim') {
            // 통신사단독유심만 업데이트
            if (isset($_POST['mno_sim_plans']) && is_array($_POST['mno_sim_plans'])) {
                $selectedIds = array_map('intval', $_POST['mno_sim_plans']);
                if (!empty($selectedIds) && $pdo) {
                    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                    $stmt = $pdo->prepare("SELECT id FROM products WHERE id IN ($placeholders) AND status = 'active'");
                    $stmt->execute($selectedIds);
                    $activeIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $home_settings['mno_sim_plans'] = $activeIds;
                } else {
                    $home_settings['mno_sim_plans'] = [];
                }
            } else {
                $home_settings['mno_sim_plans'] = [];
            }
        } elseif ($currentTab === 'internet') {
            // 인터넷만 업데이트
            if (isset($_POST['internet_products']) && is_array($_POST['internet_products'])) {
                $selectedIds = array_map('intval', $_POST['internet_products']);
                if (!empty($selectedIds) && $pdo) {
                    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                    $stmt = $pdo->prepare("SELECT id FROM products WHERE id IN ($placeholders) AND status = 'active'");
                    $stmt->execute($selectedIds);
                    $activeIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $home_settings['internet_products'] = $activeIds;
                } else {
                    $home_settings['internet_products'] = [];
                }
            } else {
                $home_settings['internet_products'] = [];
            }
        }
        
        $saveResult = saveHomeSettings($home_settings);
        
        if ($saveResult) {
            $success = '메인 상품 선택이 저장되었습니다.';
            // 현재 설정 다시 가져오기
            $home_settings = getHomeSettings();
            $selected_mvno_plans = $home_settings['mvno_plans'] ?? [];
            $selected_mno_phones = $home_settings['mno_phones'] ?? [];
            $selected_mno_sim_plans = $home_settings['mno_sim_plans'] ?? [];
            $selected_internet_products = $home_settings['internet_products'] ?? [];
        } else {
            $error = '저장에 실패했습니다.';
        }
    } catch (Exception $e) {
        $error = '오류가 발생했습니다: ' . $e->getMessage();
        error_log('Error saving home products: ' . $e->getMessage());
    }
}

// 탭 파라미터
$activeTab = $_GET['tab'] ?? 'mno-sim';
$validTabs = ['mno-sim', 'mvno', 'mno', 'internet'];
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'mno-sim';
}

// 페이지네이션
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 20);
if (!in_array($perPage, [10, 20, 50, 100])) {
    $perPage = 20;
}
$offset = ($page - 1) * $perPage;

// 검색 필터
$search_query = $_GET['search_query'] ?? '';
$status = $_GET['status'] ?? '';
$main_added = $_GET['main_added'] ?? '';
$main_added = $_GET['main_added'] ?? '';

// 각 상품 타입별 목록 가져오기
$products = [];
$totalProducts = 0;
$totalPages = 1;

if ($pdo) {
    try {
        $whereConditions = [];
        $params = [];
        
        // 상품 타입별 WHERE 조건
        if ($activeTab === 'mvno') {
            $whereConditions[] = "p.product_type = 'mvno'";
        } elseif ($activeTab === 'mno') {
            $whereConditions[] = "p.product_type = 'mno'";
        } elseif ($activeTab === 'mno-sim') {
            $whereConditions[] = "p.product_type = 'mno-sim'";
        } elseif ($activeTab === 'internet') {
            $whereConditions[] = "p.product_type = 'internet'";
        }
        
        $whereConditions[] = "p.status != 'deleted'";
        
        // 상태 필터
        if ($status && $status !== '') {
            $whereConditions[] = 'p.status = :status';
            $params[':status'] = $status;
        }
        
        // 검색 필터
        if ($search_query && $search_query !== '') {
            $searchParam = '%' . $search_query . '%';
            if ($activeTab === 'mvno') {
                $whereConditions[] = "(mvno.plan_name LIKE :search_query1 OR p.id LIKE :search_query2 OR p.seller_id LIKE :search_query3)";
                $params[':search_query1'] = $searchParam;
                $params[':search_query2'] = $searchParam;
                $params[':search_query3'] = $searchParam;
            } elseif ($activeTab === 'mno') {
                $whereConditions[] = "(mno.device_name LIKE :search_query1 OR p.id LIKE :search_query2 OR p.seller_id LIKE :search_query3)";
                $params[':search_query1'] = $searchParam;
                $params[':search_query2'] = $searchParam;
                $params[':search_query3'] = $searchParam;
            } elseif ($activeTab === 'mno-sim') {
                $whereConditions[] = "(mno_sim.plan_name LIKE :search_query1 OR p.id LIKE :search_query2 OR p.seller_id LIKE :search_query3)";
                $params[':search_query1'] = $searchParam;
                $params[':search_query2'] = $searchParam;
                $params[':search_query3'] = $searchParam;
            } elseif ($activeTab === 'internet') {
                $whereConditions[] = "(inet.registration_place LIKE :search_query1 OR inet.speed_option LIKE :search_query2 OR p.id LIKE :search_query3 OR p.seller_id LIKE :search_query4)";
                $params[':search_query1'] = $searchParam;
                $params[':search_query2'] = $searchParam;
                $params[':search_query3'] = $searchParam;
                $params[':search_query4'] = $searchParam;
            }
        }
        
        // 메인 추가 필터
        if ($main_added === 'yes') {
            // 메인에 추가된 상품만
            $selectedIds = [];
            if ($activeTab === 'mvno') {
                $selectedIds = $selected_mvno_plans;
            } elseif ($activeTab === 'mno') {
                $selectedIds = $selected_mno_phones;
            } elseif ($activeTab === 'mno-sim') {
                $selectedIds = $selected_mno_sim_plans;
            } elseif ($activeTab === 'internet') {
                $selectedIds = $selected_internet_products;
            }
            
            if (!empty($selectedIds)) {
                $whereConditions[] = "p.id IN (" . implode(',', array_map('intval', $selectedIds)) . ")";
            } else {
                // 추가된 상품이 없으면 결과 없음
                $whereConditions[] = "1 = 0";
            }
        } elseif ($main_added === 'no') {
            // 메인에 추가되지 않은 상품만
            $selectedIds = [];
            if ($activeTab === 'mvno') {
                $selectedIds = $selected_mvno_plans;
            } elseif ($activeTab === 'mno') {
                $selectedIds = $selected_mno_phones;
            } elseif ($activeTab === 'mno-sim') {
                $selectedIds = $selected_mno_sim_plans;
            } elseif ($activeTab === 'internet') {
                $selectedIds = $selected_internet_products;
            }
            
            if (!empty($selectedIds)) {
                $whereConditions[] = "p.id NOT IN (" . implode(',', array_map('intval', $selectedIds)) . ")";
            }
            // 빈 배열이면 모든 상품 표시 (조건 추가 안 함)
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // 전체 개수 조회
        if ($activeTab === 'mvno') {
            $countSql = "
                SELECT COUNT(*) 
                FROM products p
                INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
                WHERE $whereClause
            ";
        } elseif ($activeTab === 'mno') {
            $countSql = "
                SELECT COUNT(*) 
                FROM products p
                INNER JOIN product_mno_details mno ON p.id = mno.product_id
                WHERE $whereClause
            ";
        } elseif ($activeTab === 'mno-sim') {
            $countSql = "
                SELECT COUNT(*) 
                FROM products p
                INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
                WHERE $whereClause
            ";
        } elseif ($activeTab === 'internet') {
            $countSql = "
                SELECT COUNT(*) 
                FROM products p
                INNER JOIN product_internet_details inet ON p.id = inet.product_id
                WHERE $whereClause
            ";
        }
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalProducts = $countStmt->fetchColumn();
        $totalPages = ceil($totalProducts / $perPage);
        
        // 상품 목록 조회
        if ($activeTab === 'mvno') {
            $sql = "
                SELECT 
                    p.id,
                    p.seller_id,
                    p.status,
                    p.created_at,
                    p.application_count,
                    mvno.plan_name as product_name,
                    mvno.provider,
                    mvno.price_after
                FROM products p
                INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
                WHERE $whereClause
                ORDER BY p.id DESC
                LIMIT :limit OFFSET :offset
            ";
        } elseif ($activeTab === 'mno') {
            $sql = "
                SELECT 
                    p.id,
                    p.seller_id,
                    p.status,
                    p.created_at,
                    p.application_count,
                    mno.device_name as product_name,
                    mno.device_capacity,
                    mno.price_main
                FROM products p
                INNER JOIN product_mno_details mno ON p.id = mno.product_id
                WHERE $whereClause
                ORDER BY p.id DESC
                LIMIT :limit OFFSET :offset
            ";
        } elseif ($activeTab === 'mno-sim') {
            $sql = "
                SELECT 
                    p.id,
                    p.seller_id,
                    p.status,
                    p.created_at,
                    p.application_count,
                    mno_sim.plan_name as product_name,
                    mno_sim.provider,
                    mno_sim.price_after
                FROM products p
                INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
                WHERE $whereClause
                ORDER BY p.id DESC
                LIMIT :limit OFFSET :offset
            ";
        } elseif ($activeTab === 'internet') {
            $sql = "
                SELECT 
                    p.id,
                    p.seller_id,
                    p.status,
                    p.created_at,
                    p.application_count,
                    CONCAT(COALESCE(inet.registration_place, ''), ' ', COALESCE(inet.speed_option, '')) as product_name,
                    inet.monthly_fee
                FROM products p
                INNER JOIN product_internet_details inet ON p.id = inet.product_id
                WHERE $whereClause
                ORDER BY p.id DESC
                LIMIT :limit OFFSET :offset
            ";
        }
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = '상품 목록을 가져오는 중 오류가 발생했습니다: ' . $e->getMessage();
        error_log('Error fetching products: ' . $e->getMessage());
    }
}

// 현재 선택된 상품 ID 배열
$selectedIds = [];
if ($activeTab === 'mvno') {
    $selectedIds = $selected_mvno_plans;
} elseif ($activeTab === 'mno') {
    $selectedIds = $selected_mno_phones;
} elseif ($activeTab === 'mno-sim') {
    $selectedIds = $selected_mno_sim_plans;
} elseif ($activeTab === 'internet') {
    $selectedIds = $selected_internet_products;
}
?>

<style>
.product-list-container {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e5e7eb;
}

.page-header h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #111827;
}

.product-nav-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 0;
}

.product-nav-tab {
    padding: 12px 24px;
    font-size: 15px;
    font-weight: 600;
    color: #6b7280;
    text-decoration: none;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
    background: transparent;
    border-top: none;
    border-left: none;
    border-right: none;
    cursor: pointer;
}

.product-nav-tab:hover {
    color: #3b82f6;
    background: #f9fafb;
}

.product-nav-tab.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.filter-bar {
    background: #f9fafb;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
    border: 1px solid #e5e7eb;
}

.filter-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.filter-row {
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
    font-weight: 500;
    color: #374151;
    white-space: nowrap;
}

.filter-select,
.filter-input {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    background: white;
    color: #374151;
}

.filter-input {
    min-width: 200px;
}

.filter-buttons {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.search-button {
    padding: 10px 20px;
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.search-button:hover {
    background: #4f46e5;
}

.search-button.secondary {
    background: #6b7280;
}

.search-button.secondary:hover {
    background: #4b5563;
}

.product-table-wrapper {
    overflow-x: auto;
    margin-bottom: 24px;
}

.product-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    font-size: 14px;
}

.product-table thead {
    background: #f9fafb;
}

.product-table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
    white-space: nowrap;
}

.product-table td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    color: #1f2937;
}

.product-table tbody tr:hover {
    background: #f9fafb;
}

.checkbox-column {
    width: 50px;
    text-align: center;
}

.product-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.bulk-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #eef2ff;
    border-radius: 8px;
    margin-bottom: 16px;
    border: 1px solid #c7d2fe;
}

.bulk-actions-info {
    font-size: 14px;
    font-weight: 600;
    color: #6366f1;
}

.bulk-actions-select {
    padding: 8px 12px;
    border: 1px solid #c7d2fe;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

.bulk-actions-btn {
    padding: 8px 16px;
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.bulk-actions-btn:hover:not(:disabled) {
    background: #4f46e5;
}

.bulk-actions-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.badge-active {
    background: #d1fae5;
    color: #065f46;
}

.badge-inactive {
    background: #fee2e2;
    color: #991b1b;
}

.btn {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.btn-sm {
    padding: 4px 10px;
    font-size: 12px;
}

.btn-primary {
    background: #6366f1;
    color: white;
}

.btn-primary:hover {
    background: #4f46e5;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.btn-add-main {
    background: #10b981;
    color: white;
    font-weight: 600;
    padding: 10px 20px;
    font-size: 14px;
}

.btn-add-main:hover {
    background: #059669;
}

.btn-remove-main {
    background: #ef4444;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
    width: 100%;
}

.btn-remove-main:hover {
    background: #dc2626;
}

.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 24px;
}

.pagination-btn {
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 500;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #f9fafb;
    color: #374151;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
    min-width: 40px;
    text-align: center;
}

.pagination-btn:hover:not(.disabled):not(.active) {
    background: #e5e7eb;
    border-color: #9ca3af;
}

.pagination-btn.active {
    background: #10b981;
    color: white;
    border-color: #10b981;
    font-weight: 600;
}

.pagination-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: #f3f4f6;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.empty-state-title {
    font-size: 18px;
    font-weight: 600;
    margin-top: 16px;
    color: #374151;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 14px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.main-add-section {
    position: sticky;
    top: 20px;
    background: white;
    border: 2px solid #6366f1;
    border-radius: 12px;
    padding: 20px;
    margin-left: 24px;
    min-width: 250px;
    height: fit-content;
}

.main-add-section h3 {
    margin: 0 0 16px 0;
    font-size: 16px;
    font-weight: 600;
    color: #111827;
}

.selected-count {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 12px;
}

.selected-count strong {
    color: #6366f1;
    font-size: 18px;
}

.content-wrapper {
    display: flex;
    gap: 24px;
    align-items: flex-start;
}

.main-content {
    flex: 1;
    min-width: 0;
}
</style>

<div class="admin-content">
    <div class="product-list-container">
        <div class="page-header">
            <div>
                <h1>메인상품선택</h1>
                <p style="margin: 8px 0 0 0; font-size: 14px; color: #6b7280;">메인페이지에 노출될 상품을 선택하세요.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <label style="font-size: 14px; color: #374151; font-weight: 600;">페이지 수:</label>
                <select class="filter-select" id="per_page_select" onchange="changePerPage()" style="width: 80px;">
                    <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10개</option>
                    <option value="20" <?= $perPage === 20 ? 'selected' : '' ?>>20개</option>
                    <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50개</option>
                    <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100개</option>
                </select>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- 카테고리 탭 -->
        <div class="product-nav-tabs">
            <?php
            $tabParams = [];
            $tabParams['per_page'] = $perPage;
            if ($status) $tabParams['status'] = $status;
            if ($main_added) $tabParams['main_added'] = $main_added;
            if ($search_query) $tabParams['search_query'] = $search_query;
            ?>
            <a href="?tab=mno-sim&page=1&<?= http_build_query($tabParams) ?>" 
               class="product-nav-tab <?= $activeTab === 'mno-sim' ? 'active' : '' ?>">
                통신사단독유심
            </a>
            <a href="?tab=mvno&page=1&<?= http_build_query($tabParams) ?>" 
               class="product-nav-tab <?= $activeTab === 'mvno' ? 'active' : '' ?>">
                알뜰폰
            </a>
            <a href="?tab=mno&page=1&<?= http_build_query($tabParams) ?>" 
               class="product-nav-tab <?= $activeTab === 'mno' ? 'active' : '' ?>">
                통신사폰
            </a>
            <a href="?tab=internet&page=1&<?= http_build_query($tabParams) ?>" 
               class="product-nav-tab <?= $activeTab === 'internet' ? 'active' : '' ?>">
                인터넷
            </a>
        </div>
        
        <div class="content-wrapper">
            <div class="main-content">
                <!-- 필터 바 -->
                <div class="filter-bar">
                    <div class="filter-content">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label class="filter-label">상태:</label>
                                <select class="filter-select" id="filter_status">
                                    <option value="">전체</option>
                                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>판매중</option>
                                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>판매종료</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">메인 추가:</label>
                                <select class="filter-select" id="filter_main_added">
                                    <option value="">전체</option>
                                    <option value="yes" <?= $main_added === 'yes' ? 'selected' : '' ?>>추가됨</option>
                                    <option value="no" <?= $main_added === 'no' ? 'selected' : '' ?>>미추가</option>
                                </select>
                            </div>
                            
                            <div class="filter-group" style="flex: 1; display: flex; align-items: center; gap: 8px;">
                                <label class="filter-label">검색:</label>
                                <input type="text" class="filter-input" id="filter_search_query" 
                                       placeholder="상품명, 상품 ID, 판매자 ID 검색" 
                                       value="<?= htmlspecialchars($search_query) ?>" 
                                       style="flex: 1;"
                                       onkeypress="if(event.key === 'Enter') { event.preventDefault(); applyFilters(); }">
                                <button type="button" class="search-button secondary" onclick="resetFilters()" style="white-space: nowrap;">초기화</button>
                                <button type="button" class="search-button" onclick="applyFilters()" style="white-space: nowrap;">검색</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 일괄 작업 UI -->
                <div class="bulk-actions" id="bulkActions" style="display: none;">
                    <span class="bulk-actions-info">
                        <span id="selectedCount">0</span>개 선택됨
                    </span>
                </div>
                
                <!-- 상품 테이블 -->
                <div class="product-table-wrapper">
                    <?php if (empty($products)): ?>
                        <div class="empty-state">
                            <div class="empty-state-title">등록된 상품이 없습니다</div>
                            <div style="font-size: 14px; color: #9ca3af; margin-top: 8px;">검색 조건을 변경해보세요</div>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" id="productSelectForm">
                            <input type="hidden" name="action" value="save_products">
                            <input type="hidden" name="current_tab" value="<?= htmlspecialchars($activeTab) ?>">
                            
                            <table class="product-table">
                                <thead>
                                    <tr>
                                        <th class="checkbox-column">
                                            <input type="checkbox" id="selectAll" class="product-checkbox" onchange="toggleSelectAll(this)">
                                        </th>
                                        <th style="text-align: center;">상품ID</th>
                                        <th style="text-align: center;">판매자ID</th>
                                        <th style="text-align: left;">상품명</th>
                                        <?php if ($activeTab === 'mvno' || $activeTab === 'mno-sim'): ?>
                                            <th>통신사</th>
                                            <th style="text-align: right;">할인 후 요금</th>
                                        <?php elseif ($activeTab === 'mno'): ?>
                                            <th>용량</th>
                                            <th style="text-align: right;">월 요금</th>
                                        <?php elseif ($activeTab === 'internet'): ?>
                                            <th style="text-align: right;">월 요금</th>
                                        <?php endif; ?>
                                        <th style="text-align: right;">신청수</th>
                                        <th style="text-align: center;">상태</th>
                                        <th style="text-align: center;">등록일</th>
                                        <th style="text-align: center;">관리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $rowNum = $totalProducts - ($page - 1) * $perPage;
                                    foreach ($products as $product): 
                                        $is_selected = in_array((int)$product['id'], $selectedIds);
                                        $checkboxName = '';
                                        if ($activeTab === 'mvno') {
                                            $checkboxName = 'mvno_plans[]';
                                        } elseif ($activeTab === 'mno') {
                                            $checkboxName = 'mno_phones[]';
                                        } elseif ($activeTab === 'mno-sim') {
                                            $checkboxName = 'mno_sim_plans[]';
                                        } elseif ($activeTab === 'internet') {
                                            $checkboxName = 'internet_products[]';
                                        }
                                    ?>
                                        <tr>
                                            <td class="checkbox-column">
                                                <input type="checkbox" 
                                                       class="product-checkbox product-checkbox-item" 
                                                       name="<?= $checkboxName ?>"
                                                       value="<?= $product['id'] ?>"
                                                       <?= ($is_selected && ($product['status'] ?? 'active') === 'active') ? 'checked' : '' ?>
                                                       <?= ($product['status'] ?? 'active') === 'inactive' ? 'disabled' : '' ?>
                                                       onchange="updateSelectedCount()">
                                            </td>
                                            <td style="text-align: center;"><?= $product['id'] ?></td>
                                            <td style="text-align: center;">
                                                <?php 
                                                $sellerId = $product['seller_id'] ?? '-';
                                                if ($sellerId && $sellerId !== '-') {
                                                    echo '<a href="/MVNO/admin/users/seller-detail.php?user_id=' . urlencode($sellerId) . '" style="color: #3b82f6; text-decoration: none; font-weight: 600;">' . htmlspecialchars($sellerId) . '</a>';
                                                } else {
                                                    echo htmlspecialchars($sellerId);
                                                }
                                                ?>
                                            </td>
                                            <td style="text-align: left; font-weight: 500;"><?= htmlspecialchars($product['product_name'] ?? '-') ?></td>
                                            <?php if ($activeTab === 'mvno' || $activeTab === 'mno-sim'): ?>
                                                <td><?= htmlspecialchars($product['provider'] ?? '-') ?></td>
                                                <td style="text-align: right;">
                                                    <?php 
                                                    $price = $activeTab === 'mvno' ? ($product['price_after'] ?? 0) : ($product['price_after'] ?? 0);
                                                    if ($price && is_numeric($price)) {
                                                        $priceNum = floatval($price);
                                                        if ($priceNum == 0) {
                                                            echo '<span style="color: #10b981; font-weight: 600;">공짜</span>';
                                                        } else {
                                                            echo number_format($priceNum, 0) . '원';
                                                        }
                                                    } else {
                                                        echo '<span style="color: #10b981; font-weight: 600;">공짜</span>';
                                                    }
                                                    ?>
                                                </td>
                                            <?php elseif ($activeTab === 'mno'): ?>
                                                <td><?= htmlspecialchars($product['device_capacity'] ?? '-') ?></td>
                                                <td style="text-align: right;">
                                                    <?php 
                                                    $priceMain = $product['price_main'] ?? 0;
                                                    if ($priceMain && is_numeric($priceMain)) {
                                                        echo number_format(floatval($priceMain), 0) . '원';
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                            <?php elseif ($activeTab === 'internet'): ?>
                                                <td style="text-align: right;">
                                                    <?php 
                                                    $monthlyFee = $product['monthly_fee'] ?? 0;
                                                    if ($monthlyFee && is_numeric($monthlyFee)) {
                                                        echo number_format(floatval($monthlyFee), 0) . '원';
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                            <?php endif; ?>
                                            <td style="text-align: right;"><?= number_format($product['application_count'] ?? 0) ?></td>
                                            <td style="text-align: center;">
                                                <span class="badge <?= ($product['status'] ?? 'active') === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                                    <?= ($product['status'] ?? 'active') === 'active' ? '판매중' : '판매종료' ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center;"><?= isset($product['created_at']) ? date('Y-m-d', strtotime($product['created_at'])) : '-' ?></td>
                                            <td style="text-align: center;">
                                                <div class="action-buttons">
                                                    <?php if ($activeTab === 'mvno'): ?>
                                                        <a href="/MVNO/mvno/mvno-plan-detail.php?id=<?= $product['id'] ?>" target="_blank" class="btn btn-sm btn-primary">보기</a>
                                                    <?php elseif ($activeTab === 'mno'): ?>
                                                        <a href="/MVNO/mno/mno-phone-detail.php?id=<?= $product['id'] ?>" target="_blank" class="btn btn-sm btn-primary">보기</a>
                                                    <?php elseif ($activeTab === 'mno-sim'): ?>
                                                        <a href="/MVNO/mno-sim/mno-sim-detail.php?id=<?= $product['id'] ?>" target="_blank" class="btn btn-sm btn-primary">보기</a>
                                                    <?php elseif ($activeTab === 'internet'): ?>
                                                        <a href="/MVNO/internets/internet-detail.php?id=<?= $product['id'] ?>" target="_blank" class="btn btn-sm btn-primary">보기</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </form>
                    <?php endif; ?>
                    
                    <!-- 페이지네이션 -->
                    <?php if ($totalPages > 1): ?>
                        <?php
                        $pageGroupSize = 10;
                        $currentGroup = ceil($page / $pageGroupSize);
                        $startPage = ($currentGroup - 1) * $pageGroupSize + 1;
                        $endPage = min($currentGroup * $pageGroupSize, $totalPages);
                        $prevGroupLastPage = ($currentGroup - 1) * $pageGroupSize;
                        $nextGroupFirstPage = $currentGroup * $pageGroupSize + 1;
                        
                        $paginationParams = [];
                        $paginationParams['tab'] = $activeTab;
                        if ($perPage != 20) $paginationParams['per_page'] = $perPage;
                        if ($status) $paginationParams['status'] = $status;
                        if ($main_added) $paginationParams['main_added'] = $main_added;
                        if ($search_query) $paginationParams['search_query'] = $search_query;
                        ?>
                        <div class="pagination">
                            <?php if ($currentGroup > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $prevGroupLastPage])); ?>" class="pagination-btn">이전</a>
                            <?php else: ?>
                                <span class="pagination-btn disabled">이전</span>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="pagination-btn active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $i])); ?>" class="pagination-btn"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($nextGroupFirstPage <= $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $nextGroupFirstPage])); ?>" class="pagination-btn">다음</a>
                            <?php else: ?>
                                <span class="pagination-btn disabled">다음</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 오른쪽 메인 추가 섹션 -->
            <div class="main-add-section">
                <h3>메인 추가</h3>
                <div class="selected-count">
                    선택됨: <strong id="selectedCountDisplay">0</strong>개
                </div>
                <div style="margin-bottom: 12px; font-size: 12px; color: #6b7280;">
                    현재 메인: <strong style="color: #6366f1;"><?= count($selectedIds) ?></strong>개
                </div>
                <button type="button" class="btn btn-add-main" onclick="addToMain()">
                    메인에 추가
                </button>
                <button type="button" class="btn btn-remove-main" onclick="removeFromMain()" style="margin-top: 8px; width: 100%;">
                    메인에서 제거
                </button>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                    <a href="/MVNO/" target="_blank" class="btn btn-secondary" style="width: 100%; text-align: center; display: block;">
                        메인페이지 보기
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentTab = '<?= $activeTab ?>';

function changePerPage() {
    const perPage = document.getElementById('per_page_select').value;
    const params = new URLSearchParams(window.location.search);
    params.set('per_page', perPage);
    params.set('page', '1');
    window.location.href = '?' + params.toString();
}

function applyFilters() {
    const status = document.getElementById('filter_status').value;
    const mainAdded = document.getElementById('filter_main_added').value;
    const search = document.getElementById('filter_search_query').value.trim();
    const params = new URLSearchParams(window.location.search);
    
    params.set('page', '1');
    if (status) {
        params.set('status', status);
    } else {
        params.delete('status');
    }
    if (mainAdded) {
        params.set('main_added', mainAdded);
    } else {
        params.delete('main_added');
    }
    if (search) {
        params.set('search_query', search);
    } else {
        params.delete('search_query');
    }
    
    window.location.href = '?' + params.toString();
}

function resetFilters() {
    document.getElementById('filter_status').value = '';
    document.getElementById('filter_main_added').value = '';
    document.getElementById('filter_search_query').value = '';
    applyFilters();
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.product-checkbox-item:not(:disabled)');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    // 판매중인 상품만 카운트 (disabled된 체크박스 제외)
    const checkboxes = document.querySelectorAll('.product-checkbox-item:checked:not(:disabled)');
    const count = checkboxes.length;
    
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('selectedCountDisplay').textContent = count;
    
    const bulkActions = document.getElementById('bulkActions');
    if (count > 0) {
        bulkActions.style.display = 'flex';
    } else {
        bulkActions.style.display = 'none';
    }
    
    // selectAll 체크박스 상태 업데이트 (판매중인 상품만)
    const selectAll = document.getElementById('selectAll');
    const allActiveCheckboxes = document.querySelectorAll('.product-checkbox-item:not(:disabled)');
    selectAll.checked = allActiveCheckboxes.length > 0 && checkboxes.length === allActiveCheckboxes.length;
}

function addToMain() {
    // 판매중인 상품만 선택
    const checkboxes = document.querySelectorAll('.product-checkbox-item:checked:not(:disabled)');
    if (checkboxes.length === 0) {
        alert('선택된 상품이 없습니다.');
        return;
    }
    
    // 판매종료된 상품의 체크박스 해제
    const disabledCheckboxes = document.querySelectorAll('.product-checkbox-item:checked:disabled');
    disabledCheckboxes.forEach(cb => {
        cb.checked = false;
    });
    
    if (confirm(`선택한 ${checkboxes.length}개의 상품을 메인에 추가하시겠습니까?`)) {
        document.getElementById('productSelectForm').submit();
    }
}

function removeFromMain() {
    // 현재 메인에 추가된 상품들의 체크박스 찾기 (checked 상태인 것들)
    const checkedBoxes = document.querySelectorAll('.product-checkbox-item:checked:not(:disabled)');
    
    if (checkedBoxes.length === 0) {
        alert('메인에서 제거할 상품이 없습니다.');
        return;
    }
    
    if (confirm(`선택한 ${checkedBoxes.length}개의 상품을 메인에서 제거하시겠습니까?`)) {
        // 모든 체크박스 해제
        checkedBoxes.forEach(cb => {
            cb.checked = false;
        });
        
        // selectAll 체크박스도 해제
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.checked = false;
        }
        
        // 선택 개수 업데이트
        updateSelectedCount();
        
        // 폼 제출하여 저장
        document.getElementById('productSelectForm').submit();
    }
}

// 초기 선택 개수 업데이트
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
