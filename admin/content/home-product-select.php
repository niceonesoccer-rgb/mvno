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
        
        // 각 상품 타입별로 선택된 ID 저장
        if (isset($_POST['mvno_plans']) && is_array($_POST['mvno_plans'])) {
            $home_settings['mvno_plans'] = array_map('intval', $_POST['mvno_plans']);
        } else {
            $home_settings['mvno_plans'] = [];
        }
        
        if (isset($_POST['mno_phones']) && is_array($_POST['mno_phones'])) {
            $home_settings['mno_phones'] = array_map('intval', $_POST['mno_phones']);
        } else {
            $home_settings['mno_phones'] = [];
        }
        
        if (isset($_POST['mno_sim_plans']) && is_array($_POST['mno_sim_plans'])) {
            $home_settings['mno_sim_plans'] = array_map('intval', $_POST['mno_sim_plans']);
        } else {
            $home_settings['mno_sim_plans'] = [];
        }
        
        if (isset($_POST['internet_products']) && is_array($_POST['internet_products'])) {
            $home_settings['internet_products'] = array_map('intval', $_POST['internet_products']);
        } else {
            $home_settings['internet_products'] = [];
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

// 각 상품 타입별 목록 가져오기
$mvno_products = [];
$mno_products = [];
$mno_sim_products = [];
$internet_products = [];

if ($pdo) {
    try {
        // 알뜰폰 요금제 목록
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.status,
                mvno.plan_name,
                mvno.provider,
                mvno.price_after,
                p.application_count
            FROM products p
            INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
            WHERE p.product_type = 'mvno' 
            AND p.status != 'deleted'
            ORDER BY p.id DESC
            LIMIT 500
        ");
        $stmt->execute();
        $mvno_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 통신사폰 목록
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.status,
                mno.device_name,
                mno.device_capacity,
                mno.price_main,
                p.application_count
            FROM products p
            INNER JOIN product_mno_details mno ON p.id = mno.product_id
            WHERE p.product_type = 'mno' 
            AND p.status != 'deleted'
            ORDER BY p.id DESC
            LIMIT 500
        ");
        $stmt->execute();
        $mno_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 통신사단독유심 목록
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.status,
                mno_sim.plan_name,
                mno_sim.provider,
                mno_sim.price_after,
                p.application_count
            FROM products p
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
            WHERE p.product_type = 'mno-sim' 
            AND p.status != 'deleted'
            ORDER BY p.id DESC
            LIMIT 500
        ");
        $stmt->execute();
        $mno_sim_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 인터넷 상품 목록
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.status,
                inet.registration_place,
                inet.speed_option,
                inet.monthly_fee,
                p.application_count
            FROM products p
            INNER JOIN product_internet_details inet ON p.id = inet.product_id
            WHERE p.product_type = 'internet' 
            AND p.status != 'deleted'
            ORDER BY p.id DESC
            LIMIT 500
        ");
        $stmt->execute();
        $internet_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = '상품 목록을 가져오는 중 오류가 발생했습니다: ' . $e->getMessage();
        error_log('Error fetching products: ' . $e->getMessage());
    }
}
?>

<div class="admin-content">
    <div class="admin-content-header">
        <h1>메인상품선택</h1>
        <p class="admin-content-description">메인페이지에 노출될 상품을 선택하세요. 선택한 상품은 판매 상태와 관계없이 메인페이지에 표시됩니다.</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom: 1.5rem;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" id="productSelectForm">
        <input type="hidden" name="action" value="save_products">
        
        <!-- 알뜰폰 요금제 -->
        <div class="product-section">
            <div class="product-section-header">
                <h2>추천 알뜰폰 요금제</h2>
                <span class="product-count">선택됨: <strong id="mvno-count"><?php echo count($selected_mvno_plans); ?></strong>개</span>
            </div>
            <div class="product-list-container">
                <?php if (!empty($mvno_products)): ?>
                    <div class="product-list">
                        <?php foreach ($mvno_products as $product): 
                            $is_selected = in_array((int)$product['id'], $selected_mvno_plans);
                            $status_class = $product['status'] === 'active' ? 'status-active' : 'status-inactive';
                        ?>
                            <label class="product-item <?php echo $is_selected ? 'selected' : ''; ?>">
                                <input type="checkbox" 
                                       name="mvno_plans[]" 
                                       value="<?php echo $product['id']; ?>"
                                       <?php echo $is_selected ? 'checked' : ''; ?>
                                       onchange="updateCount('mvno')">
                                <div class="product-item-content">
                                    <div class="product-item-header">
                                        <span class="product-id">ID: <?php echo $product['id']; ?></span>
                                        <span class="product-status <?php echo $status_class; ?>">
                                            <?php echo $product['status'] === 'active' ? '판매중' : '판매종료'; ?>
                                        </span>
                                    </div>
                                    <div class="product-item-title"><?php echo htmlspecialchars($product['plan_name'] ?? '요금제명 없음'); ?></div>
                                    <div class="product-item-info">
                                        <span>통신사: <?php echo htmlspecialchars($product['provider'] ?? '-'); ?></span>
                                        <span>할인 후: <?php echo $product['price_after'] ? number_format($product['price_after']) . '원' : '-'; ?></span>
                                        <span>신청수: <?php echo number_format($product['application_count'] ?? 0); ?>건</span>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-message">등록된 알뜰폰 요금제가 없습니다.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 통신사폰 -->
        <div class="product-section">
            <div class="product-section-header">
                <h2>인기 통신사폰</h2>
                <span class="product-count">선택됨: <strong id="mno-count"><?php echo count($selected_mno_phones); ?></strong>개</span>
            </div>
            <div class="product-list-container">
                <?php if (!empty($mno_products)): ?>
                    <div class="product-list">
                        <?php foreach ($mno_products as $product): 
                            $is_selected = in_array((int)$product['id'], $selected_mno_phones);
                            $status_class = $product['status'] === 'active' ? 'status-active' : 'status-inactive';
                        ?>
                            <label class="product-item <?php echo $is_selected ? 'selected' : ''; ?>">
                                <input type="checkbox" 
                                       name="mno_phones[]" 
                                       value="<?php echo $product['id']; ?>"
                                       <?php echo $is_selected ? 'checked' : ''; ?>
                                       onchange="updateCount('mno')">
                                <div class="product-item-content">
                                    <div class="product-item-header">
                                        <span class="product-id">ID: <?php echo $product['id']; ?></span>
                                        <span class="product-status <?php echo $status_class; ?>">
                                            <?php echo $product['status'] === 'active' ? '판매중' : '판매종료'; ?>
                                        </span>
                                    </div>
                                    <div class="product-item-title"><?php echo htmlspecialchars($product['device_name'] ?? '기기명 없음'); ?></div>
                                    <div class="product-item-info">
                                        <span>용량: <?php echo htmlspecialchars($product['device_capacity'] ?? '-'); ?></span>
                                        <span>월 요금: <?php echo $product['price_main'] ? number_format($product['price_main']) . '원' : '-'; ?></span>
                                        <span>신청수: <?php echo number_format($product['application_count'] ?? 0); ?>건</span>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-message">등록된 통신사폰이 없습니다.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 통신사단독유심 -->
        <div class="product-section">
            <div class="product-section-header">
                <h2>알짜 통신사단독유심</h2>
                <span class="product-count">선택됨: <strong id="mno-sim-count"><?php echo count($selected_mno_sim_plans); ?></strong>개</span>
            </div>
            <div class="product-list-container">
                <?php if (!empty($mno_sim_products)): ?>
                    <div class="product-list">
                        <?php foreach ($mno_sim_products as $product): 
                            $is_selected = in_array((int)$product['id'], $selected_mno_sim_plans);
                            $status_class = $product['status'] === 'active' ? 'status-active' : 'status-inactive';
                        ?>
                            <label class="product-item <?php echo $is_selected ? 'selected' : ''; ?>">
                                <input type="checkbox" 
                                       name="mno_sim_plans[]" 
                                       value="<?php echo $product['id']; ?>"
                                       <?php echo $is_selected ? 'checked' : ''; ?>
                                       onchange="updateCount('mno-sim')">
                                <div class="product-item-content">
                                    <div class="product-item-header">
                                        <span class="product-id">ID: <?php echo $product['id']; ?></span>
                                        <span class="product-status <?php echo $status_class; ?>">
                                            <?php echo $product['status'] === 'active' ? '판매중' : '판매종료'; ?>
                                        </span>
                                    </div>
                                    <div class="product-item-title"><?php echo htmlspecialchars($product['plan_name'] ?? '요금제명 없음'); ?></div>
                                    <div class="product-item-info">
                                        <span>통신사: <?php echo htmlspecialchars($product['provider'] ?? '-'); ?></span>
                                        <span>할인 후: <?php echo $product['price_after'] ? number_format($product['price_after']) . '원' : '-'; ?></span>
                                        <span>신청수: <?php echo number_format($product['application_count'] ?? 0); ?>건</span>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-message">등록된 통신사단독유심이 없습니다.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 인터넷 상품 -->
        <div class="product-section">
            <div class="product-section-header">
                <h2>최대할인 인터넷 상품</h2>
                <span class="product-count">선택됨: <strong id="internet-count"><?php echo count($selected_internet_products); ?></strong>개</span>
            </div>
            <div class="product-list-container">
                <?php if (!empty($internet_products)): ?>
                    <div class="product-list">
                        <?php foreach ($internet_products as $product): 
                            $is_selected = in_array((int)$product['id'], $selected_internet_products);
                            $status_class = $product['status'] === 'active' ? 'status-active' : 'status-inactive';
                        ?>
                            <label class="product-item <?php echo $is_selected ? 'selected' : ''; ?>">
                                <input type="checkbox" 
                                       name="internet_products[]" 
                                       value="<?php echo $product['id']; ?>"
                                       <?php echo $is_selected ? 'checked' : ''; ?>
                                       onchange="updateCount('internet')">
                                <div class="product-item-content">
                                    <div class="product-item-header">
                                        <span class="product-id">ID: <?php echo $product['id']; ?></span>
                                        <span class="product-status <?php echo $status_class; ?>">
                                            <?php echo $product['status'] === 'active' ? '판매중' : '판매종료'; ?>
                                        </span>
                                    </div>
                                    <div class="product-item-title">
                                        <?php echo htmlspecialchars($product['registration_place'] ?? ''); ?> 
                                        <?php echo htmlspecialchars($product['speed_option'] ?? ''); ?>
                                    </div>
                                    <div class="product-item-info">
                                        <span>월 요금: <?php echo $product['monthly_fee'] ? number_format($product['monthly_fee']) . '원' : '-'; ?></span>
                                        <span>신청수: <?php echo number_format($product['application_count'] ?? 0); ?>건</span>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-message">등록된 인터넷 상품이 없습니다.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 저장 버튼 -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">저장하기</button>
            <a href="/MVNO/" target="_blank" class="btn btn-secondary">메인페이지 보기</a>
        </div>
    </form>
</div>

<style>
.product-section {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.product-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e5e7eb;
}

.product-section-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.product-count {
    font-size: 0.875rem;
    color: #6b7280;
}

.product-count strong {
    color: #6366f1;
    font-weight: 600;
}

.product-list-container {
    max-height: 600px;
    overflow-y: auto;
}

.product-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.product-item {
    display: block;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
}

.product-item:hover {
    border-color: #6366f1;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.1);
}

.product-item.selected {
    border-color: #6366f1;
    background: #eef2ff;
}

.product-item input[type="checkbox"] {
    margin-right: 0.75rem;
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.product-item-content {
    display: inline-block;
    width: calc(100% - 30px);
    vertical-align: top;
}

.product-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.product-id {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
}

.product-status {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-weight: 500;
}

.product-status.status-active {
    background: #d1fae5;
    color: #065f46;
}

.product-status.status-inactive {
    background: #fee2e2;
    color: #991b1b;
}

.product-item-title {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.5rem;
}

.product-item-info {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.product-item-info span {
    white-space: nowrap;
}

.empty-message {
    text-align: center;
    padding: 3rem 1rem;
    color: #9ca3af;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding: 1.5rem 0;
    border-top: 1px solid #e5e7eb;
    margin-top: 2rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
    border: none;
    font-size: 0.875rem;
}

.btn-primary {
    background: #6366f1;
    color: white;
}

.btn-primary:hover {
    background: #4f46e5;
}

.btn-secondary {
    background: #e5e7eb;
    color: #374151;
}

.btn-secondary:hover {
    background: #d1d5db;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
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
</style>

<script>
function updateCount(type) {
    const checkboxes = document.querySelectorAll(`input[name="${type}_plans[]"], input[name="${type}_phones[]"], input[name="${type}_products[]"]`);
    let count = 0;
    checkboxes.forEach(cb => {
        if (cb.checked) count++;
    });
    
    const countElement = document.getElementById(`${type}-count`);
    if (countElement) {
        countElement.textContent = count;
    }
}

// 전체 선택/해제 기능 (선택사항)
document.addEventListener('DOMContentLoaded', function() {
    // 각 섹션에 전체 선택 버튼 추가 가능
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
