<?php
/**
 * 단말기 설정 페이지
 * 제조사 및 단말기 관리
 */

require_once __DIR__ . '/../../includes/data/path-config.php';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

// 데이터베이스 연결
$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

// 제조사 목록 가져오기
$manufacturers = [];
try {
    $stmt = $pdo->query("SELECT * FROM device_manufacturers ORDER BY display_order ASC, name ASC");
    $manufacturers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching manufacturers: " . $e->getMessage());
}


// 단말기 관리 탭용 필터 파라미터
$deviceFilterManufacturer = $_GET['device_filter_manufacturer'] ?? '';
$deviceFilterStorage = $_GET['device_filter_storage'] ?? '';
$deviceFilterDateFrom = $_GET['device_filter_date_from'] ?? '';
$deviceFilterDateTo = $_GET['device_filter_date_to'] ?? '';
$devicePerPage = isset($_GET['device_per_page']) ? intval($_GET['device_per_page']) : 30;
$devicePage = isset($_GET['device_page']) ? max(1, intval($_GET['device_page'])) : 1;

// 허용된 per_page 값만 사용 (10, 30, 50, 100, 300)
if (!in_array($devicePerPage, [10, 30, 50, 100, 300])) {
    $devicePerPage = 30;
}

// 단말기 목록 가져오기 (제조사 정보 포함)
$devices = [];
$dbError = null;
try {
    // devices 테이블 존재 확인
    $stmt = $pdo->query("SHOW TABLES LIKE 'devices'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        // WHERE 조건 구성
        $whereConditions = [];
        $params = [];
        
        // 제조사 필터
        if ($deviceFilterManufacturer && $deviceFilterManufacturer !== '') {
            $whereConditions[] = 'd.manufacturer_id = :manufacturer_id';
            $params[':manufacturer_id'] = $deviceFilterManufacturer;
        }
        
        // 용량 필터
        if ($deviceFilterStorage && $deviceFilterStorage !== '') {
            $whereConditions[] = 'd.storage LIKE :storage';
            $params[':storage'] = '%' . $deviceFilterStorage . '%';
        }
        
        // 출시일 필터 (시작일)
        if ($deviceFilterDateFrom && $deviceFilterDateFrom !== '') {
            $whereConditions[] = 'DATE(d.release_date) >= :date_from';
            $params[':date_from'] = $deviceFilterDateFrom;
        }
        
        // 출시일 필터 (종료일)
        if ($deviceFilterDateTo && $deviceFilterDateTo !== '') {
            $whereConditions[] = 'DATE(d.release_date) <= :date_to';
            $params[':date_to'] = $deviceFilterDateTo;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // 전체 개수 조회
        $countSql = "
            SELECT COUNT(*) as total
            FROM devices d 
            LEFT JOIN device_manufacturers m ON d.manufacturer_id = m.id 
            {$whereClause}
        ";
        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalDevices = $countStmt->fetch()['total'];
        $totalPages = ceil($totalDevices / $devicePerPage);
        
        // 페이지네이션을 위한 LIMIT과 OFFSET 계산
        $offset = ($devicePage - 1) * $devicePerPage;
        
        $sql = "
            SELECT d.*, m.name as manufacturer_name 
            FROM devices d 
            LEFT JOIN device_manufacturers m ON d.manufacturer_id = m.id 
            {$whereClause}
            ORDER BY d.release_date DESC, m.display_order ASC, m.name ASC, d.name ASC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $devicePerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $devices = $stmt->fetchAll();
    } else {
        $dbError = "devices 테이블이 존재하지 않습니다. database/device_tables.sql 파일을 실행하세요.";
        $totalDevices = 0;
        $totalPages = 0;
        $offset = 0;
    }
} catch (PDOException $e) {
    $dbError = "데이터베이스 오류: " . $e->getMessage();
    error_log("Error fetching devices: " . $e->getMessage());
    $totalDevices = 0;
    $totalPages = 0;
    $offset = 0;
}

// 성공/에러 메시지
$successMsg = $_GET['success'] ?? '';
$errorMsg = $_GET['error'] ?? '';
?>

<style>
    .admin-content {
        padding: 32px;
    }
    
    .page-header {
        margin-bottom: 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }
    
    .tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 24px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .tab {
        padding: 12px 24px;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        font-size: 15px;
        font-weight: 600;
        color: #6b7280;
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
    
    .card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        margin-bottom: 24px;
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .card-title {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: #3b82f6;
        color: white;
    }
    
    .btn-primary:hover {
        background: #2563eb;
    }
    
    .btn-danger {
        background: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background: #dc2626;
    }
    
    .btn-secondary {
        background: #6b7280;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #4b5563;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    table th {
        background: #f9fafb;
        padding: 12px 16px;
        text-align: left;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
    }
    
    table td {
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
        color: #1f2937;
    }
    
    table tr:hover {
        background: #f9fafb;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-group label .required {
        color: #ef4444;
        margin-left: 4px;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }
    
    .alert {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
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
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-badge.active {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-badge.inactive {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }
    
    .modal.active {
        display: flex;
    }
    
    .modal-content {
        background: white;
        border-radius: 12px;
        padding: 24px;
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .modal-title {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }
    
    .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        color: #6b7280;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .close-btn:hover {
        color: #374151;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #6b7280;
    }
    
    /* 드래그 앤 드롭 스타일 */
    .draggable-row {
        cursor: move;
        transition: background-color 0.2s;
    }
    
    .draggable-row:hover {
        background: #f3f4f6;
    }
    
    .draggable-row.dragging {
        opacity: 0.5;
        background: #e5e7eb;
    }
    
    .draggable-row.drag-over {
        border-top: 3px solid #3b82f6;
        background: #eff6ff;
    }
    
    /* 삭제 확인 모달 */
    .delete-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 3000;
        align-items: center;
        justify-content: center;
    }
    
    .delete-modal.active {
        display: flex;
    }
    
    .delete-modal-content {
        background: white;
        border-radius: 12px;
        padding: 24px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }
    
    .delete-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .delete-modal-title {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }
    
    .delete-modal-body {
        margin-bottom: 24px;
    }
    
    .delete-modal-body p {
        font-size: 15px;
        color: #374151;
        line-height: 1.6;
        margin-bottom: 12px;
    }
    
    .delete-modal-body .warning-text {
        color: #ef4444;
        font-weight: 600;
    }
    
    .delete-modal-footer {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
    
    /* 성공/에러 메시지 모달 */
    .message-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 3001;
        align-items: center;
        justify-content: center;
    }
    
    .message-modal.active {
        display: flex;
    }
    
    .message-modal-content {
        background: white;
        border-radius: 12px;
        padding: 32px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        text-align: center;
    }
    
    .message-modal-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
    }
    
    .message-modal-icon.success {
        background: #dcfce7;
        color: #16a34a;
    }
    
    .message-modal-icon.error {
        background: #fee2e2;
        color: #dc2626;
    }
    
    .message-modal-title {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 12px 0;
    }
    
    .message-modal-body {
        font-size: 15px;
        color: #6b7280;
        line-height: 1.6;
        margin-bottom: 24px;
    }
    
    .message-modal-footer {
        display: flex;
        gap: 12px;
        justify-content: center;
    }
</style>

<div class="admin-content">
    <div class="page-header">
        <h1>단말기 설정</h1>
    </div>
    
    <?php if ($successMsg): ?>
        <div class="alert alert-success">
            <?php 
            $successMessages = [
                'manufacturer_added' => '제조사가 추가되었습니다.',
                'manufacturer_updated' => '제조사 정보가 수정되었습니다.',
                'manufacturer_deleted' => '제조사가 삭제되었습니다.',
                'device_added' => '단말기가 추가되었습니다.',
                'device_updated' => '단말기 정보가 수정되었습니다.',
                'device_deleted' => '단말기가 삭제되었습니다.'
            ];
            echo htmlspecialchars($successMessages[$successMsg] ?? '작업이 완료되었습니다.');
            ?>
        </div>
    <?php endif; ?>
    
    <?php if ($errorMsg): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($errorMsg); ?>
        </div>
    <?php endif; ?>
    
    <!-- 탭 메뉴 -->
    <?php 
    $currentTab = $_GET['tab'] ?? 'devices';
    ?>
    <div class="tabs">
        <button class="tab <?php echo $currentTab === 'devices' ? 'active' : ''; ?>" onclick="switchTab('devices')">단말기 관리</button>
        <button class="tab <?php echo $currentTab === 'manufacturers' ? 'active' : ''; ?>" onclick="switchTab('manufacturers')">제조사 관리</button>
    </div>
    
    <!-- 단말기 관리 탭 -->
    <div id="tab-devices" class="tab-content <?php echo $currentTab === 'devices' ? 'active' : ''; ?>">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">단말기 목록</h2>
                <button class="btn btn-primary" onclick="openDeviceModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    단말기 추가
                </button>
            </div>
            
            <!-- 검색 필터 -->
            <div style="padding: 20px; background: #f9fafb; border-radius: 8px; margin-bottom: 20px;">
                <form method="GET" id="deviceFilterForm" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;" onsubmit="document.getElementById('deviceFilterForm').querySelector('input[name=device_page]').value = '1';">
                    <input type="hidden" name="tab" value="devices">
                    <input type="hidden" name="device_page" value="1">
                    <input type="hidden" name="device_per_page" value="<?php echo $devicePerPage; ?>">
                    <div style="min-width: 180px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">제조사</label>
                        <select name="device_filter_manufacturer" style="width: 100%; padding: 10px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                            <option value="">전체</option>
                            <?php foreach ($manufacturers as $m): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo $deviceFilterManufacturer == $m['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="min-width: 150px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">용량</label>
                        <input type="text" name="device_filter_storage" value="<?php echo htmlspecialchars($deviceFilterStorage); ?>" placeholder="예: 128GB, 256GB" style="width: 100%; padding: 10px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                    </div>
                    <div style="min-width: 200px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">출시일</label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="date" name="device_filter_date_from" value="<?php echo htmlspecialchars($deviceFilterDateFrom); ?>" style="flex: 1; padding: 10px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                            <span style="font-size: 14px; color: #6b7280; white-space: nowrap;">~</span>
                            <input type="date" name="device_filter_date_to" value="<?php echo htmlspecialchars($deviceFilterDateTo); ?>" style="flex: 1; padding: 10px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                            <span style="font-size: 14px; color: #6b7280; white-space: nowrap;">이전</span>
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: end;">
                        <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">검색</button>
                        <a href="<?php echo getAssetPath('/admin/settings/device-settings.php?tab=devices'); ?>" class="btn btn-secondary" style="padding: 10px 20px; text-decoration: none;">초기화</a>
                    </div>
                </form>
            </div>
            
            <div class="table-container">
                <?php 
                $hasDeviceFilter = !empty($deviceFilterManufacturer) || !empty($deviceFilterStorage) || !empty($deviceFilterDateFrom) || !empty($deviceFilterDateTo);
                ?>
                <?php if (empty($devices)): ?>
                    <div class="empty-state">
                        <?php if ($hasDeviceFilter): ?>
                            <p style="font-size: 16px; margin-bottom: 16px;">검색 결과가 없습니다.</p>
                            <p style="font-size: 14px; color: #6b7280; margin-bottom: 20px;">
                                검색 조건을 변경하거나 필터를 초기화해보세요.
                            </p>
                            <a href="<?php echo getAssetPath('/admin/settings/device-settings.php?tab=devices'); ?>" class="btn btn-secondary" style="text-decoration: none;">필터 초기화</a>
                        <?php else: ?>
                            <p style="font-size: 16px; margin-bottom: 16px; color: #ef4444;">⚠️ 등록된 단말기가 없습니다.</p>
                            <p style="font-size: 14px; color: #6b7280; margin-bottom: 20px;">
                                데이터를 추가하려면 <strong>database/insert_devices.sql</strong> 파일을 실행하거나<br>
                                위의 "단말기 추가" 버튼을 클릭하여 직접 추가할 수 있습니다.
                            </p>
                            <a href="<?php echo getAssetPath('/database/check_devices.php'); ?>" target="_blank" class="btn btn-secondary" style="text-decoration: none;">
                                데이터베이스 상태 확인
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="margin-bottom: 16px; padding: 12px; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #3b82f6; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <?php if ($hasDeviceFilter): ?>
                                <strong>검색 결과: <?php echo number_format($totalDevices); ?>개</strong>
                            <?php else: ?>
                                <strong>총 <?php echo number_format($totalDevices); ?>개의 단말기가 등록되어 있습니다.</strong>
                            <?php endif; ?>
                            <?php if ($totalPages > 1): ?>
                                <span style="color: #6b7280; font-size: 14px; margin-left: 8px;">
                                    (<?php echo number_format($offset + 1); ?>-<?php echo number_format(min($offset + $devicePerPage, $totalDevices)); ?> / <?php echo number_format($totalDevices); ?>)
                                </span>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label style="font-size: 14px; color: #374151; font-weight: 600;">페이지당 표시:</label>
                            <select id="devicePerPageSelect" onchange="changeDevicePerPage(this.value)" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; cursor: pointer;">
                                <option value="10" <?php echo $devicePerPage === 10 ? 'selected' : ''; ?>>10개</option>
                                <option value="30" <?php echo $devicePerPage === 30 ? 'selected' : ''; ?>>30개</option>
                                <option value="50" <?php echo $devicePerPage === 50 ? 'selected' : ''; ?>>50개</option>
                                <option value="100" <?php echo $devicePerPage === 100 ? 'selected' : ''; ?>>100개</option>
                                <option value="300" <?php echo $devicePerPage === 300 ? 'selected' : ''; ?>>300개</option>
                            </select>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>번호</th>
                                <th>제조사</th>
                                <th>단말기명</th>
                                <th>용량</th>
                                <th>출고가</th>
                                <th>색상</th>
                                <th>출시일</th>
                                <th>상태</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($devices as $index => $device): ?>
                                <tr>
                                    <td><?php echo $totalDevices - ($offset + $index); ?></td>
                                    <td><strong><?php echo htmlspecialchars($device['manufacturer_name'] ?? '-'); ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($device['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($device['storage'] ?? '-'); ?></td>
                                    <td><?php echo $device['release_price'] ? number_format($device['release_price']) . '원' : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($device['color'] ?? '-'); ?></td>
                                    <td><?php echo $device['release_date'] ? date('Y-m-d', strtotime($device['release_date'])) : '-'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $device['status']; ?>">
                                            <?php echo $device['status'] === 'active' ? '활성' : '비활성'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons" style="display: flex; gap: 8px;">
                                            <button class="btn btn-secondary" onclick="editDevice(<?php echo htmlspecialchars(json_encode($device)); ?>)">수정</button>
                                            <button class="btn btn-danger" onclick="deleteDevice(<?php echo $device['id']; ?>, '<?php echo htmlspecialchars(addslashes($device['name'])); ?>')">삭제</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- 페이지네이션 -->
                    <?php if ($totalPages > 1): ?>
                        <?php
                        // 쿼리 파라미터 구성
                        $queryParams = ['tab' => 'devices'];
                        if ($deviceFilterManufacturer) $queryParams['device_filter_manufacturer'] = $deviceFilterManufacturer;
                        if ($deviceFilterStorage) $queryParams['device_filter_storage'] = $deviceFilterStorage;
                        if ($deviceFilterDateFrom) $queryParams['device_filter_date_from'] = $deviceFilterDateFrom;
                        if ($deviceFilterDateTo) $queryParams['device_filter_date_to'] = $deviceFilterDateTo;
                        $queryParams['device_per_page'] = $devicePerPage;
                        $baseQueryString = http_build_query($queryParams);
                        ?>
                        <div style="display: flex; flex-direction: row; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; padding: 20px; flex-wrap: nowrap;">
                            <!-- 이전 버튼 -->
                            <?php if ($devicePage > 1): ?>
                                <a href="?<?php echo $baseQueryString; ?>&device_page=<?php echo $devicePage - 1; ?>" 
                                   style="padding: 8px 16px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 6px; background: white; color: #374151; cursor: pointer; text-decoration: none; transition: all 0.2s; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; line-height: 1;">
                                    이전
                                </a>
                            <?php else: ?>
                                <span style="padding: 8px 16px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 6px; background: #f3f4f6; color: #9ca3af; cursor: not-allowed; opacity: 0.5; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; line-height: 1;">
                                    이전
                                </span>
                            <?php endif; ?>
                            
                            <!-- 페이지 번호 -->
                            <?php
                            $startPage = max(1, $devicePage - 2);
                            $endPage = min($totalPages, $devicePage + 2);
                            
                            if ($startPage > 1): ?>
                                <a href="?<?php echo $baseQueryString; ?>&device_page=1" 
                                   style="padding: 8px 16px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 6px; background: white; color: #374151; cursor: pointer; text-decoration: none; transition: all 0.2s; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; line-height: 1; min-width: 40px;">
                                    1
                                </a>
                                <?php if ($startPage > 2): ?>
                                    <span style="padding: 8px 4px; color: #6b7280; white-space: nowrap; display: inline-flex; align-items: center; line-height: 1;">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?<?php echo $baseQueryString; ?>&device_page=<?php echo $i; ?>" 
                                   style="padding: 8px 16px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 6px; background: <?php echo $i === $devicePage ? '#10b981' : 'white'; ?>; color: <?php echo $i === $devicePage ? 'white' : '#374151'; ?>; cursor: pointer; text-decoration: none; transition: all 0.2s; font-weight: <?php echo $i === $devicePage ? '600' : 'normal'; ?>; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; line-height: 1; min-width: 40px;">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span style="padding: 8px 4px; color: #6b7280; white-space: nowrap; display: inline-flex; align-items: center; line-height: 1;">...</span>
                                <?php endif; ?>
                                <a href="?<?php echo $baseQueryString; ?>&device_page=<?php echo $totalPages; ?>" 
                                   style="padding: 8px 16px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 6px; background: white; color: #374151; cursor: pointer; text-decoration: none; transition: all 0.2s; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; line-height: 1; min-width: 40px;">
                                    <?php echo $totalPages; ?>
                                </a>
                            <?php endif; ?>
                            
                            <!-- 다음 버튼 -->
                            <?php if ($devicePage < $totalPages): ?>
                                <a href="?<?php echo $baseQueryString; ?>&device_page=<?php echo $devicePage + 1; ?>" 
                                   style="padding: 8px 16px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 6px; background: white; color: #374151; cursor: pointer; text-decoration: none; transition: all 0.2s; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; line-height: 1;">
                                    다음
                                </a>
                            <?php else: ?>
                                <span style="padding: 8px 16px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 6px; background: #f3f4f6; color: #9ca3af; cursor: not-allowed; opacity: 0.5; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; line-height: 1;">
                                    다음
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 제조사 관리 탭 -->
    <div id="tab-manufacturers" class="tab-content <?php echo $currentTab === 'manufacturers' ? 'active' : ''; ?>">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">제조사 목록</h2>
                <button class="btn btn-primary" onclick="openManufacturerModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    제조사 추가
                </button>
            </div>
            
            <div class="table-container">
                <?php if (empty($manufacturers)): ?>
                    <div class="empty-state">등록된 제조사가 없습니다.</div>
                <?php else: ?>
                    <table id="manufacturerTable">
                        <thead>
                            <tr>
                                <th style="width: 50px;">순서</th>
                                <th>제조사명</th>
                                <th>영문명</th>
                                <th>표시순서</th>
                                <th>상태</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody id="manufacturerTableBody">
                            <?php foreach ($manufacturers as $index => $manufacturer): ?>
                                <tr data-id="<?php echo $manufacturer['id']; ?>" data-order="<?php echo $manufacturer['display_order']; ?>" draggable="true" class="draggable-row">
                                    <td style="text-align: center; cursor: move;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #9ca3af;">
                                            <circle cx="9" cy="12" r="1"></circle>
                                            <circle cx="9" cy="5" r="1"></circle>
                                            <circle cx="9" cy="19" r="1"></circle>
                                            <circle cx="15" cy="12" r="1"></circle>
                                            <circle cx="15" cy="5" r="1"></circle>
                                            <circle cx="15" cy="19" r="1"></circle>
                                        </svg>
                                    </td>
                                    <td><?php echo htmlspecialchars($manufacturer['name']); ?></td>
                                    <td><?php echo htmlspecialchars($manufacturer['name_en'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($manufacturer['display_order']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $manufacturer['status']; ?>">
                                            <?php echo $manufacturer['status'] === 'active' ? '활성' : '비활성'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-secondary" onclick="editManufacturer(<?php echo htmlspecialchars(json_encode($manufacturer)); ?>)">수정 / 삭제</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
</div>

<!-- 단말기 삭제 확인 모달 -->
<div id="deleteDeviceModal" class="delete-modal">
    <div class="delete-modal-content">
        <div class="delete-modal-header">
            <h2 class="delete-modal-title">단말기 삭제 확인</h2>
            <button class="close-btn" onclick="closeDeleteDeviceModal()">&times;</button>
        </div>
        <div class="delete-modal-body">
            <p><strong id="deleteDeviceName"></strong> 단말기를 삭제하시겠습니까?</p>
            <p class="warning-text">⚠️ 주의: 이 작업은 되돌릴 수 없습니다.</p>
            <p style="font-size: 14px; color: #6b7280;">연결된 상품이나 주문이 있는 경우 삭제할 수 없습니다.</p>
        </div>
        <div class="delete-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteDeviceModal()">취소</button>
            <button type="button" class="btn btn-danger" onclick="deleteDeviceConfirm()">삭제</button>
        </div>
        <input type="hidden" id="deleteDeviceId" value="">
    </div>
</div>

<!-- 성공/에러 메시지 모달 -->
<div id="messageModal" class="message-modal">
    <div class="message-modal-content">
        <div class="message-modal-icon" id="messageModalIcon">
            <span id="messageModalIconText">✓</span>
        </div>
        <h2 class="message-modal-title" id="messageModalTitle">알림</h2>
        <div class="message-modal-body" id="messageModalBody"></div>
        <div class="message-modal-footer">
            <button type="button" class="btn btn-primary" onclick="closeMessageModal()">확인</button>
        </div>
    </div>
</div>

<!-- 제조사 삭제 확인 모달 -->
<div id="deleteManufacturerModal" class="delete-modal">
    <div class="delete-modal-content">
        <div class="delete-modal-header">
            <h2 class="delete-modal-title">제조사 삭제 확인</h2>
            <button class="close-btn" onclick="closeDeleteManufacturerModal()">&times;</button>
        </div>
        <div class="delete-modal-body">
            <p><strong id="deleteManufacturerName"></strong> 제조사를 삭제하시겠습니까?</p>
            <p class="warning-text">⚠️ 주의: 연결된 단말기가 있는 경우 삭제할 수 없습니다.</p>
            <p style="font-size: 14px; color: #6b7280;">이 작업은 되돌릴 수 없습니다.</p>
        </div>
        <div class="delete-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteManufacturerModal()">취소</button>
            <button type="button" class="btn btn-danger" onclick="deleteManufacturer()">삭제</button>
        </div>
        <input type="hidden" id="deleteManufacturerId" value="">
    </div>
</div>

<!-- 제조사 추가/수정 모달 -->
<div id="manufacturerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="manufacturerModalTitle">제조사 추가</h2>
            <button class="close-btn" onclick="closeManufacturerModal()">&times;</button>
        </div>
        <form id="manufacturerForm" method="POST">
            <input type="hidden" name="action" id="manufacturerAction" value="add_manufacturer">
            <input type="hidden" name="manufacturer_id" id="manufacturerId">
            
            <div class="form-group">
                <label for="manufacturerName">제조사명 <span class="required">*</span></label>
                <input type="text" id="manufacturerName" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="manufacturerNameEn">영문명</label>
                <input type="text" id="manufacturerNameEn" name="name_en">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="manufacturerDisplayOrder">표시순서</label>
                    <input type="number" id="manufacturerDisplayOrder" name="display_order" value="0">
                </div>
                <div class="form-group">
                    <label for="manufacturerStatus">상태</label>
                    <select id="manufacturerStatus" name="status">
                        <option value="active">활성</option>
                        <option value="inactive">비활성</option>
                    </select>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary" style="flex: 1; min-width: 120px;">저장</button>
                <button type="button" class="btn btn-secondary" onclick="closeManufacturerModal()" style="flex: 1; min-width: 120px;">취소</button>
                <button type="button" id="deleteManufacturerInModalBtn" class="btn btn-danger" onclick="showDeleteManufacturerFromModal()" style="display: none; min-width: 120px;">삭제</button>
            </div>
        </form>
    </div>
</div>

<!-- 단말기 추가/수정 모달 -->
<div id="deviceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="deviceModalTitle">단말기 추가</h2>
            <button class="close-btn" onclick="closeDeviceModal()">&times;</button>
        </div>
        <form id="deviceForm" method="POST">
            <input type="hidden" name="action" id="deviceAction" value="add_device">
            <input type="hidden" name="device_id" id="deviceId">
            
            <div class="form-group">
                <label for="deviceManufacturer">제조사 <span class="required">*</span></label>
                <select id="deviceManufacturer" name="manufacturer_id" required>
                    <option value="">선택하세요</option>
                    <?php foreach ($manufacturers as $m): ?>
                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="deviceName">단말기명 <span class="required">*</span></label>
                <input type="text" id="deviceName" name="name" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="deviceStorage">용량</label>
                    <input type="text" id="deviceStorage" name="storage" placeholder="예: 128GB, 256GB">
                </div>
                <div class="form-group">
                    <label for="deviceReleasePrice">출고가 (원)</label>
                    <input type="number" id="deviceReleasePrice" name="release_price" placeholder="예: 1200000">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="deviceModelCode">모델 코드</label>
                    <input type="text" id="deviceModelCode" name="model_code">
                </div>
            </div>
            
            <div class="form-group">
                <label for="deviceColors">색상 <span class="required">*</span></label>
                <div id="colorList" style="margin-bottom: 12px;"></div>
                <button type="button" id="addColorBtn" class="btn btn-secondary" style="padding: 8px 16px; font-size: 14px; margin-bottom: 12px;">
                    + 색상 추가
                </button>
                <input type="hidden" id="deviceColorValues" name="color_values" value="">
                <div class="form-help" style="font-size: 12px; color: #6b7280; margin-top: 4px;">색상명과 색상값을 함께 입력하세요</div>
            </div>
            
            <div class="form-group">
                <label for="deviceReleaseDate">출시일</label>
                <input type="date" id="deviceReleaseDate" name="release_date">
            </div>
            
            <div class="form-group">
                <label for="deviceStatus">상태</label>
                <select id="deviceStatus" name="status">
                    <option value="active">활성</option>
                    <option value="inactive">비활성</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary" style="flex: 1; min-width: 120px;">저장</button>
                <button type="button" class="btn btn-secondary" onclick="closeDeviceModal()" style="flex: 1; min-width: 120px;">취소</button>
                <button type="button" id="deleteDeviceInModalBtn" class="btn btn-danger" onclick="showDeleteDeviceFromModal()" style="display: none; min-width: 120px;">삭제</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tabName) {
    // URL 파라미터 유지하면서 탭 전환
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tabName);
    window.location.href = url.toString();
}

// 페이지당 표시 개수 변경
function changeDevicePerPage(perPage) {
    const url = new URL(window.location.href);
    url.searchParams.set('device_per_page', perPage);
    url.searchParams.set('device_page', '1'); // 첫 페이지로 이동
    window.location.href = url.toString();
}

// 제조사 모달
function openManufacturerModal(manufacturer = null) {
    const modal = document.getElementById('manufacturerModal');
    const form = document.getElementById('manufacturerForm');
    const title = document.getElementById('manufacturerModalTitle');
    const actionInput = document.getElementById('manufacturerAction');
    const idInput = document.getElementById('manufacturerId');
    const deleteBtn = document.getElementById('deleteManufacturerInModalBtn');
    
    if (manufacturer) {
        title.textContent = '제조사 수정';
        actionInput.value = 'update_manufacturer';
        idInput.value = manufacturer.id;
        document.getElementById('manufacturerName').value = manufacturer.name || '';
        document.getElementById('manufacturerNameEn').value = manufacturer.name_en || '';
        document.getElementById('manufacturerDisplayOrder').value = manufacturer.display_order || 0;
        document.getElementById('manufacturerStatus').value = manufacturer.status || 'active';
        // 삭제 버튼 표시
        deleteBtn.style.display = 'block';
        deleteBtn.setAttribute('data-manufacturer-id', manufacturer.id);
        deleteBtn.setAttribute('data-manufacturer-name', manufacturer.name);
    } else {
        title.textContent = '제조사 추가';
        actionInput.value = 'add_manufacturer';
        idInput.value = '';
        form.reset();
        // 삭제 버튼 숨김
        deleteBtn.style.display = 'none';
    }
    
    modal.classList.add('active');
    // 모달이 열릴 때 body 스크롤 잠금
    document.body.style.overflow = 'hidden';
}

function closeManufacturerModal() {
    document.getElementById('manufacturerModal').classList.remove('active');
    document.getElementById('manufacturerForm').reset();
    // 모달이 닫힐 때 body 스크롤 복원
    document.body.style.overflow = '';
}

// 제조사 폼 제출 처리 (AJAX)
document.getElementById('manufacturerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = document.getElementById('manufacturerForm');
    const formData = new FormData(form);
    const action = document.getElementById('manufacturerAction').value;
    
    // 버튼 비활성화
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = '처리 중...';
    
    fetch('<?php echo getApiPath('/api/device-manage.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        } else {
            return response.text().then(text => {
                throw new Error(text || '요청 처리에 실패했습니다.');
            });
        }
    })
    .then(data => {
        if (data.success) {
            // 모달 닫기
            closeManufacturerModal();
            
            // 즉시 페이지 새로고침
            location.reload();
        } else {
            showMessageModal('error', '오류', data.message || '처리 중 오류가 발생했습니다.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessageModal('error', '오류', '오류가 발생했습니다: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
});

function editManufacturer(manufacturer) {
    openManufacturerModal(manufacturer);
}

// 삭제 확인 모달 표시 (목록에서)
function showDeleteManufacturerModal(id, name) {
    const modal = document.getElementById('deleteManufacturerModal');
    const modalId = document.getElementById('deleteManufacturerId');
    const modalName = document.getElementById('deleteManufacturerName');
    
    modalId.value = id;
    modalName.textContent = name;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// 삭제 확인 모달 표시 (수정 모달에서)
function showDeleteManufacturerFromModal() {
    const deleteBtn = document.getElementById('deleteManufacturerInModalBtn');
    const id = deleteBtn.getAttribute('data-manufacturer-id');
    const name = deleteBtn.getAttribute('data-manufacturer-name');
    
    if (id && name) {
        // 수정 모달 닫기
        closeManufacturerModal();
        
        // 삭제 확인 모달 열기
        showDeleteManufacturerModal(id, name);
    }
}

// 삭제 확인 모달 닫기
function closeDeleteManufacturerModal() {
    const modal = document.getElementById('deleteManufacturerModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// 제조사 삭제 실행
function deleteManufacturer() {
    const id = document.getElementById('deleteManufacturerId').value;
    const name = document.getElementById('deleteManufacturerName').textContent;
    
    if (!id) {
        showMessageModal('error', '오류', '삭제할 제조사 ID가 없습니다.');
        return;
    }
    
    // 삭제 버튼 비활성화
    const deleteBtn = document.querySelector('#deleteManufacturerModal .btn-danger');
    const originalText = deleteBtn.textContent;
    deleteBtn.disabled = true;
    deleteBtn.textContent = '삭제 중...';
    
    const formData = new FormData();
    formData.append('action', 'delete_manufacturer');
    formData.append('manufacturer_id', id);
    
    fetch('<?php echo getApiPath('/api/device-manage.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        } else {
            return response.text().then(text => {
                throw new Error(text || '삭제에 실패했습니다.');
            });
        }
    })
    .then(data => {
        if (data.success) {
            // 삭제 모달 닫기
            closeDeleteManufacturerModal();
            
            // 즉시 페이지 새로고침
            location.reload();
        } else {
            showMessageModal('error', '오류', data.message || '삭제 중 오류가 발생했습니다.');
            deleteBtn.disabled = false;
            deleteBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessageModal('error', '오류', '삭제 중 오류가 발생했습니다: ' + error.message);
        deleteBtn.disabled = false;
        deleteBtn.textContent = originalText;
    });
}

// 드래그 앤 드롭 기능
let draggedRow = null;

document.addEventListener('DOMContentLoaded', function() {
    // 제조사 테이블 드래그 앤 드롭
    const manufacturerTableBody = document.getElementById('manufacturerTableBody');
    if (manufacturerTableBody) {
        const rows = manufacturerTableBody.querySelectorAll('.draggable-row');
        
        rows.forEach(row => {
            row.addEventListener('dragstart', function(e) {
                draggedRow = this;
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.innerHTML);
            });
            
            row.addEventListener('dragend', function(e) {
                this.classList.remove('dragging');
                rows.forEach(r => r.classList.remove('drag-over'));
            });
            
            row.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                
                if (this !== draggedRow) {
                    this.classList.add('drag-over');
                }
            });
            
            row.addEventListener('dragleave', function(e) {
                this.classList.remove('drag-over');
            });
            
            row.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                
                if (this !== draggedRow && draggedRow) {
                    const allRows = Array.from(manufacturerTableBody.querySelectorAll('.draggable-row'));
                    const draggedIndex = allRows.indexOf(draggedRow);
                    const targetIndex = allRows.indexOf(this);
                    
                    if (draggedIndex < targetIndex) {
                        manufacturerTableBody.insertBefore(draggedRow, this.nextSibling);
                    } else {
                        manufacturerTableBody.insertBefore(draggedRow, this);
                    }
                    
                    // 순서 업데이트
                    updateManufacturerOrder();
                }
            });
        });
    }
});

// 제조사 순서 업데이트
function updateManufacturerOrder() {
    const rows = document.querySelectorAll('#manufacturerTableBody .draggable-row');
    const orders = [];
    
    rows.forEach((row, index) => {
        const id = row.getAttribute('data-id');
        const newOrder = index + 1;
        orders.push({ id: id, order: newOrder });
    });
    
    // 서버에 순서 업데이트 요청
    fetch('<?php echo getApiPath('/api/device-manage.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=update_manufacturer_order&orders=' + encodeURIComponent(JSON.stringify(orders))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 성공 메시지 표시 (선택사항)
            console.log('순서가 업데이트되었습니다.');
            // 페이지 새로고침 없이 순서만 업데이트
            rows.forEach((row, index) => {
                const orderCell = row.querySelector('td:nth-child(4)');
                if (orderCell) {
                    orderCell.textContent = index + 1;
                }
                row.setAttribute('data-order', index + 1);
            });
        } else {
            showMessageModal('error', '오류', '순서 업데이트에 실패했습니다: ' + (data.message || '알 수 없는 오류'), function() {
                location.reload();
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessageModal('error', '오류', '순서 업데이트 중 오류가 발생했습니다.', function() {
            location.reload();
        });
    });
}

// 용량 순서 업데이트
function updateStorageOrder() {
    const rows = document.querySelectorAll('#storageTableBody .draggable-row');
    const orders = [];
    
    rows.forEach((row, index) => {
        const id = row.getAttribute('data-id');
        const newOrder = index + 1;
        orders.push({ id: id, order: newOrder });
    });
    
    // 서버에 순서 업데이트 요청
    fetch('<?php echo getApiPath('/api/device-manage.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=update_storage_order&orders=' + encodeURIComponent(JSON.stringify(orders))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 성공 메시지 표시 (선택사항)
            console.log('순서가 업데이트되었습니다.');
            // 페이지 새로고침 없이 순서만 업데이트
            rows.forEach((row, index) => {
                const orderCell = row.querySelector('td:nth-child(3)'); // 표시순서 컬럼 (순서, 용량명, 표시순서)
                if (orderCell) {
                    orderCell.textContent = index + 1;
                }
                row.setAttribute('data-order', index + 1);
            });
        } else {
            showMessageModal('error', '오류', '순서 업데이트에 실패했습니다: ' + (data.message || '알 수 없는 오류'), function() {
                location.reload();
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessageModal('error', '오류', '순서 업데이트 중 오류가 발생했습니다.', function() {
            location.reload();
        });
    });
}

// 단말기 모달
function openDeviceModal(device = null) {
    const modal = document.getElementById('deviceModal');
    const form = document.getElementById('deviceForm');
    const title = document.getElementById('deviceModalTitle');
    const actionInput = document.getElementById('deviceAction');
    const idInput = document.getElementById('deviceId');
    const deleteBtn = document.getElementById('deleteDeviceInModalBtn');
    
    if (device) {
        title.textContent = '단말기 수정';
        actionInput.value = 'update_device';
        idInput.value = device.id;
        document.getElementById('deviceManufacturer').value = device.manufacturer_id || '';
        document.getElementById('deviceName').value = device.name || '';
        document.getElementById('deviceStorage').value = device.storage || '';
        document.getElementById('deviceReleasePrice').value = device.release_price || '';
        document.getElementById('deviceModelCode').value = device.model_code || '';
        
        // 색상 데이터 로드
        let colorData = [];
        if (device.color_values) {
            try {
                colorData = JSON.parse(device.color_values);
            } catch (e) {
                // JSON 파싱 실패 시 color 필드에서 파싱 시도
                if (device.color) {
                    const colorNames = device.color.split(',').map(c => c.trim());
                    colorData = colorNames.map(name => ({ name: name, value: '' }));
                }
            }
        } else if (device.color) {
            const colorNames = device.color.split(',').map(c => c.trim());
            colorData = colorNames.map(name => ({ name: name, value: '' }));
        }
        
        // 색상 목록 초기화
        document.getElementById('colorList').innerHTML = '';
        colorData.forEach((color, index) => {
            addColorInput(color.name, color.value, index);
        });
        
        updateColorValues();
        document.getElementById('deviceModelCode').value = device.model_code || '';
        document.getElementById('deviceReleaseDate').value = device.release_date || '';
        document.getElementById('deviceStatus').value = device.status || 'active';
        
        // 삭제 버튼 표시
        deleteBtn.style.display = 'block';
        deleteBtn.setAttribute('data-device-id', device.id);
        deleteBtn.setAttribute('data-device-name', device.name);
    } else {
        title.textContent = '단말기 추가';
        actionInput.value = 'add_device';
        idInput.value = '';
        form.reset();
        // 색상 목록 초기화
        document.getElementById('colorList').innerHTML = '';
        updateColorValues();
        
        // 삭제 버튼 숨김
        deleteBtn.style.display = 'none';
    }
    
    modal.classList.add('active');
    // 모달이 열릴 때 body 스크롤 잠금
    document.body.style.overflow = 'hidden';
}

function closeDeviceModal() {
    document.getElementById('deviceModal').classList.remove('active');
    document.getElementById('deviceForm').reset();
    // 모달이 닫힐 때 body 스크롤 복원
    document.body.style.overflow = '';
}

// 단말기 폼 제출 처리 (AJAX)
document.getElementById('deviceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = document.getElementById('deviceForm');
    const formData = new FormData(form);
    const action = document.getElementById('deviceAction').value;
    
    // 색상 값 업데이트
    updateColorValues();
    formData.set('color_values', document.getElementById('deviceColorValues').value);
    
    // 버튼 비활성화
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = '처리 중...';
    
    fetch('<?php echo getApiPath('/api/device-manage.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        } else {
            return response.text().then(text => {
                throw new Error(text || '요청 처리에 실패했습니다.');
            });
        }
    })
    .then(data => {
        if (data.success) {
            // 모달 닫기
            closeDeviceModal();
            
            // 즉시 페이지 새로고침
            location.reload();
        } else {
            showMessageModal('error', '오류', data.message || '처리 중 오류가 발생했습니다.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessageModal('error', '오류', '오류가 발생했습니다: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
});

function editDevice(device) {
    openDeviceModal(device);
}

// 삭제 확인 모달 표시 (목록에서)
function deleteDevice(id, name) {
    const modal = document.getElementById('deleteDeviceModal');
    const modalId = document.getElementById('deleteDeviceId');
    const modalName = document.getElementById('deleteDeviceName');
    
    modalId.value = id;
    modalName.textContent = name;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// 삭제 확인 모달 표시 (수정 모달에서)
function showDeleteDeviceFromModal() {
    const deleteBtn = document.getElementById('deleteDeviceInModalBtn');
    const id = deleteBtn.getAttribute('data-device-id');
    const name = deleteBtn.getAttribute('data-device-name');
    
    if (id && name) {
        // 수정 모달 닫기
        closeDeviceModal();
        
        // 삭제 확인 모달 열기
        deleteDevice(id, name);
    }
}

// 삭제 확인 모달 닫기
function closeDeleteDeviceModal() {
    const modal = document.getElementById('deleteDeviceModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// 단말기 삭제 실행
function deleteDeviceConfirm() {
    const id = document.getElementById('deleteDeviceId').value;
    const name = document.getElementById('deleteDeviceName').textContent;
    
    if (!id) {
        showMessageModal('error', '오류', '삭제할 단말기 ID가 없습니다.');
        return;
    }
    
    // 삭제 버튼 비활성화
    const deleteBtn = document.querySelector('#deleteDeviceModal .btn-danger');
    const originalText = deleteBtn.textContent;
    deleteBtn.disabled = true;
    deleteBtn.textContent = '삭제 중...';
    
    const formData = new FormData();
    formData.append('action', 'delete_device');
    formData.append('device_id', id);
    
    fetch('<?php echo getApiPath('/api/device-manage.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        } else {
            return response.text().then(text => {
                throw new Error(text || '삭제에 실패했습니다.');
            });
        }
    })
    .then(data => {
        if (data.success) {
            // 삭제 모달 닫기
            closeDeleteDeviceModal();
            
            // 즉시 페이지 새로고침
            location.reload();
        } else {
            showMessageModal('error', '오류', data.message || '삭제 중 오류가 발생했습니다.');
            deleteBtn.disabled = false;
            deleteBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessageModal('error', '오류', '삭제 중 오류가 발생했습니다: ' + error.message);
        deleteBtn.disabled = false;
        deleteBtn.textContent = originalText;
    });
}

// 색상 입력 필드 추가 함수
let colorIndex = 0;
function addColorInput(name = '', value = '', index = null) {
    const colorList = document.getElementById('colorList');
    const idx = index !== null ? index : colorIndex++;
    
    const colorItem = document.createElement('div');
    colorItem.className = 'color-item';
    colorItem.style.cssText = 'display: flex; gap: 8px; margin-bottom: 8px; align-items: center;';
    colorItem.innerHTML = `
        <input type="text" class="color-name-input" placeholder="색상명 (예: 블랙)" value="${name}" 
               style="flex: 1; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
               onchange="updateColorValues()">
        <div style="display: flex; align-items: center; gap: 8px;">
            <input type="color" class="color-picker-input" value="${value || '#000000'}" 
                   style="width: 50px; height: 40px; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer;"
                   onchange="updateColorValues()">
            <input type="text" class="color-value-input" placeholder="#000000" value="${value}" 
                   pattern="^#[0-9A-Fa-f]{6}$"
                   style="width: 100px; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; font-family: monospace;"
                   onchange="updateColorValueFromText(this)" oninput="updateColorValues()">
        </div>
        <button type="button" class="remove-color-btn" onclick="removeColorItem(this)" 
                style="padding: 10px 16px; background: #ef4444; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px;">
            삭제
        </button>
    `;
    
    colorList.appendChild(colorItem);
    updateColorValues();
}

// 색상값 텍스트 입력 시 컬러 피커 업데이트
function updateColorValueFromText(input) {
    const colorItem = input.closest('.color-item');
    const colorPicker = colorItem.querySelector('.color-picker-input');
    const value = input.value.trim();
    
    if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
        colorPicker.value = value;
    }
    updateColorValues();
}

// 컬러 피커 변경 시 텍스트 입력 업데이트
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('color-picker-input')) {
        const colorItem = e.target.closest('.color-item');
        const colorValueInput = colorItem.querySelector('.color-value-input');
        colorValueInput.value = e.target.value;
        updateColorValues();
    }
});

// 색상 항목 삭제
function removeColorItem(btn) {
    btn.closest('.color-item').remove();
    updateColorValues();
}

// 색상값 JSON 업데이트
function updateColorValues() {
    const colorItems = document.querySelectorAll('.color-item');
    const colors = [];
    
    colorItems.forEach(item => {
        const nameInput = item.querySelector('.color-name-input');
        const valueInput = item.querySelector('.color-value-input');
        const name = nameInput.value.trim();
        const value = valueInput.value.trim();
        
        if (name) {
            colors.push({
                name: name,
                value: value || '#000000'
            });
        }
    });
    
    document.getElementById('deviceColorValues').value = JSON.stringify(colors);
}

// 색상 추가 버튼 이벤트
document.getElementById('addColorBtn').addEventListener('click', function() {
    addColorInput();
});

// 모달 외부 클릭 시 닫기 (삭제 모달은 제외)
window.addEventListener('click', function(event) {
    const manufacturerModal = document.getElementById('manufacturerModal');
    const deviceModal = document.getElementById('deviceModal');
    const deleteManufacturerModal = document.getElementById('deleteManufacturerModal');
    const deleteDeviceModal = document.getElementById('deleteDeviceModal');
    
    // 삭제 모달은 외부 클릭으로 닫히지 않음
    if (event.target === manufacturerModal) {
        closeManufacturerModal();
    }
    if (event.target === deviceModal) {
        closeDeviceModal();
    }
    // deleteManufacturerModal과 deleteDeviceModal은 외부 클릭으로 닫히지 않음 (취소 버튼으로만 닫힘)
});

// 삭제 모달 배경 클릭 방지
const deleteManufacturerModal = document.getElementById('deleteManufacturerModal');
if (deleteManufacturerModal) {
    deleteManufacturerModal.addEventListener('click', function(e) {
        // 모달 배경을 직접 클릭한 경우에도 닫히지 않음
        if (e.target === this) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
    
    // 모달 내부 컨텐츠 클릭 시 이벤트 전파 방지
    const deleteModalContent = deleteManufacturerModal.querySelector('.delete-modal-content');
    if (deleteModalContent) {
        deleteModalContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
}

// 단말기 삭제 모달 배경 클릭 방지
const deleteDeviceModal = document.getElementById('deleteDeviceModal');
if (deleteDeviceModal) {
    deleteDeviceModal.addEventListener('click', function(e) {
        // 모달 배경을 직접 클릭한 경우에도 닫히지 않음
        if (e.target === this) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
    
    // 모달 내부 컨텐츠 클릭 시 이벤트 전파 방지
    const deleteModalContent = deleteDeviceModal.querySelector('.delete-modal-content');
    if (deleteModalContent) {
        deleteModalContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
}

// 메시지 모달 표시 함수
function showMessageModal(type, title, message, callback) {
    const modal = document.getElementById('messageModal');
    const icon = document.getElementById('messageModalIcon');
    const iconText = document.getElementById('messageModalIconText');
    const modalTitle = document.getElementById('messageModalTitle');
    const modalBody = document.getElementById('messageModalBody');
    
    // 아이콘 설정
    icon.className = 'message-modal-icon ' + type;
    if (type === 'success') {
        iconText.textContent = '✓';
    } else {
        iconText.textContent = '✕';
    }
    
    modalTitle.textContent = title;
    modalBody.textContent = message;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // 확인 버튼 클릭 시 콜백 실행 후 모달 닫기
    const confirmBtn = modal.querySelector('.btn-primary');
    const originalOnclick = confirmBtn.onclick;
    confirmBtn.onclick = function() {
        closeMessageModal();
        if (callback && typeof callback === 'function') {
            callback();
        }
    };
}

// 메시지 모달 닫기
function closeMessageModal() {
    const modal = document.getElementById('messageModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

