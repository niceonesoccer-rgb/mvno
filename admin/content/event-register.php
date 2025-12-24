<?php
/**
 * 이벤트 등록 페이지
 * 경로: /MVNO/admin/content/event-register.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    header('Location: /MVNO/admin/login.php');
    exit;
}

$error = '';
$success = '';
$pdo = getDBConnection();

// 이벤트 등록 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_event') {
    $title = trim($_POST['title'] ?? '');
    $event_type = $_POST['event_type'] ?? 'promotion';
    $start_at = !empty($_POST['start_at']) ? $_POST['start_at'] : null;
    $end_at = !empty($_POST['end_at']) ? $_POST['end_at'] : null;
    $description = trim($_POST['description'] ?? '');
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    // 유효성 검사
    if (empty($title)) {
        $error = '이벤트 제목을 입력해주세요.';
    } elseif (!in_array($event_type, ['plan', 'promotion', 'card'])) {
        $error = '올바른 이벤트 타입을 선택해주세요.';
    } elseif ($start_at && $end_at && strtotime($start_at) > strtotime($end_at)) {
        $error = '시작일은 종료일보다 이전이어야 합니다.';
    }
    
    if (!$error && $pdo) {
        try {
            $pdo->beginTransaction();
            
            // 이벤트 ID 생성 (타임스탬프 기반)
            $eventId = 'evt_' . time() . '_' . bin2hex(random_bytes(4));
            
            // 메인 이미지 업로드 처리
            $main_image = null;
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $main_image = uploadEventImage($_FILES['main_image'], $eventId, 'main', true); // true = 16:9 비율 강제
                if (!$main_image) {
                    throw new Exception('메인 이미지 업로드에 실패했습니다.');
                }
            }
            
            // 이벤트 기본 정보 저장
            $stmt = $pdo->prepare("
                INSERT INTO events (id, title, event_type, main_image, description, start_at, end_at, is_published, created_at, updated_at)
                VALUES (:id, :title, :event_type, :main_image, :description, :start_at, :end_at, :is_published, NOW(), NOW())
            ");
            $stmt->execute([
                ':id' => $eventId,
                ':title' => $title,
                ':event_type' => $event_type,
                ':main_image' => $main_image,
                ':description' => $description,
                ':start_at' => $start_at ?: null,
                ':end_at' => $end_at ?: null,
                ':is_published' => $is_published
            ]);
            
            // 상세 이미지 업로드 처리
            if (isset($_FILES['detail_images']) && is_array($_FILES['detail_images']['name'])) {
                $detailImages = $_FILES['detail_images'];
                
                // 순서 정보 가져오기 (사용자가 드래그로 변경한 순서)
                $orderData = [];
                if (!empty($_POST['detail_images_data'])) {
                    $orderDataJson = json_decode($_POST['detail_images_data'], true);
                    if (is_array($orderDataJson)) {
                        // 파일명과 크기로 매칭하여 순서 정보 생성
                        foreach ($orderDataJson as $orderItem) {
                            $orderData[$orderItem['name'] . '_' . $orderItem['size']] = $orderItem['order'];
                        }
                    }
                }
                
                // 파일들을 순서대로 정렬
                $filesWithOrder = [];
                for ($i = 0; $i < count($detailImages['name']); $i++) {
                    if ($detailImages['error'][$i] === UPLOAD_ERR_OK) {
                        $fileKey = $detailImages['name'][$i] . '_' . $detailImages['size'][$i];
                        $order = isset($orderData[$fileKey]) ? $orderData[$fileKey] : $i;
                        
                        $filesWithOrder[] = [
                            'order' => $order,
                            'index' => $i,
                            'file' => [
                                'name' => $detailImages['name'][$i],
                                'type' => $detailImages['type'][$i],
                                'tmp_name' => $detailImages['tmp_name'][$i],
                                'error' => $detailImages['error'][$i],
                                'size' => $detailImages['size'][$i]
                            ]
                        ];
                    }
                }
                
                // 순서대로 정렬
                usort($filesWithOrder, function($a, $b) {
                    return $a['order'] - $b['order'];
                });
                
                // 순서대로 저장
                $displayOrder = 0;
                foreach ($filesWithOrder as $fileData) {
                    $imagePath = uploadEventImage($fileData['file'], $eventId, 'detail', false);
                    if ($imagePath) {
                        $stmt = $pdo->prepare("
                            INSERT INTO event_detail_images (event_id, image_path, display_order, created_at)
                            VALUES (:event_id, :image_path, :display_order, NOW())
                        ");
                        $stmt->execute([
                            ':event_id' => $eventId,
                            ':image_path' => $imagePath,
                            ':display_order' => $displayOrder++
                        ]);
                    }
                }
            }
            
            // 연결된 상품 저장
            if (isset($_POST['product_ids']) && is_array($_POST['product_ids'])) {
                $productIds = array_filter($_POST['product_ids'], function($id) {
                    return is_numeric($id) && $id > 0;
                });
                
                $displayOrder = 0;
                foreach ($productIds as $productId) {
                    $stmt = $pdo->prepare("
                        INSERT INTO event_products (event_id, product_id, display_order, created_at)
                        VALUES (:event_id, :product_id, :display_order, NOW())
                        ON DUPLICATE KEY UPDATE display_order = :display_order
                    ");
                    $stmt->execute([
                        ':event_id' => $eventId,
                        ':product_id' => (int)$productId,
                        ':display_order' => $displayOrder++
                    ]);
                }
            }
            
            $pdo->commit();
            $success = '이벤트가 등록되었습니다.';
            header('Location: /MVNO/admin/content/event-manage.php?success=created');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = '이벤트 등록 중 오류가 발생했습니다: ' . $e->getMessage();
            error_log('Event registration error: ' . $e->getMessage());
        }
    }
}

/**
 * 이벤트 이미지 업로드 함수
 */
function uploadEventImage($file, $eventId, $type = 'main', $force16to9 = false) {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    $uploadDir = __DIR__ . '/../../uploads/events/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $eventId . '_' . $type . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // 16:9 비율 강제 리사이징 (메인 이미지인 경우)
        if ($force16to9) {
            resizeImageTo16to9($filepath);
        }
        
        return '/MVNO/uploads/events/' . $filename;
    }
    
    return false;
}

/**
 * 이미지를 16:9 비율로 리사이징
 */
function resizeImageTo16to9($filepath) {
    $imageInfo = getimagesize($filepath);
    if ($imageInfo === false) {
        return false;
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // 목표 비율: 16:9
    $targetRatio = 16 / 9;
    $currentRatio = $width / $height;
    
    // 이미 16:9 비율이면 리사이징 불필요
    if (abs($currentRatio - $targetRatio) < 0.01) {
        return true;
    }
    
    // 이미지 리소스 생성
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($filepath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($filepath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($filepath);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($filepath);
            break;
        default:
            return false;
    }
    
    if ($sourceImage === false) {
        return false;
    }
    
    // 16:9 비율로 크롭/리사이징
    if ($currentRatio > $targetRatio) {
        // 너비가 더 넓음 - 높이 기준으로 크롭
        $newHeight = $height;
        $newWidth = (int)($height * $targetRatio);
        $x = (int)(($width - $newWidth) / 2);
        $y = 0;
    } else {
        // 높이가 더 높음 - 너비 기준으로 크롭
        $newWidth = $width;
        $newHeight = (int)($width / $targetRatio);
        $x = 0;
        $y = (int)(($height - $newHeight) / 2);
    }
    
    // 새 이미지 생성
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // PNG 투명도 유지
    if ($mimeType === 'image/png') {
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // 이미지 크롭 및 리사이징
    imagecopyresampled($newImage, $sourceImage, 0, 0, $x, $y, $newWidth, $newHeight, $newWidth, $newHeight);
    
    // 저장
    switch ($mimeType) {
        case 'image/jpeg':
            imagejpeg($newImage, $filepath, 90);
            break;
        case 'image/png':
            imagepng($newImage, $filepath, 9);
            break;
        case 'image/gif':
            imagegif($newImage, $filepath);
            break;
        case 'image/webp':
            imagewebp($newImage, $filepath, 90);
            break;
    }
    
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    
    return true;
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page-header">
    <h1>이벤트 등록</h1>
    <a href="/MVNO/admin/content/event-manage.php" class="btn-back">목록으로</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="eventForm" class="event-form">
    <input type="hidden" name="action" value="create_event">
    <input type="hidden" id="detail_images_data" name="detail_images_data" value="">
    
    <div class="form-section">
        <h2 class="section-title">기본 정보</h2>
        
        <div class="form-group">
            <label for="title">이벤트 제목 <span class="required">*</span></label>
            <input type="text" id="title" name="title" required class="form-control" placeholder="이벤트 제목을 입력하세요" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="event_type">이벤트 타입 <span class="required">*</span></label>
            <select id="event_type" name="event_type" required class="form-control">
                <option value="promotion" <?php echo (($_POST['event_type'] ?? 'promotion') === 'promotion') ? 'selected' : ''; ?>>프로모션</option>
                <option value="plan" <?php echo (($_POST['event_type'] ?? '') === 'plan') ? 'selected' : ''; ?>>요금제</option>
                <option value="card" <?php echo (($_POST['event_type'] ?? '') === 'card') ? 'selected' : ''; ?>>제휴카드</option>
            </select>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="start_at">시작일</label>
                <input type="date" id="start_at" name="start_at" class="form-control" value="<?php echo htmlspecialchars($_POST['start_at'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="end_at">종료일</label>
                <input type="date" id="end_at" name="end_at" class="form-control" value="<?php echo htmlspecialchars($_POST['end_at'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="description">이벤트 설명</label>
            <textarea id="description" name="description" class="form-control" rows="4" placeholder="이벤트 설명을 입력하세요"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_published" value="1" <?php echo isset($_POST['is_published']) ? 'checked' : 'checked'; ?>>
                <span>즉시 공개</span>
            </label>
        </div>
    </div>
    
    <div class="form-section">
        <h2 class="section-title">메인 이미지 (16:9 비율)</h2>
        <div class="form-group">
            <label for="main_image">메인 이미지 <span class="required">*</span></label>
            <input type="file" id="main_image" name="main_image" accept="image/*" required class="form-control">
            <small class="form-help">16:9 비율로 자동 리사이징됩니다. (JPG, PNG, GIF, WEBP, 최대 10MB)</small>
            <div id="main_image_preview" class="image-preview"></div>
        </div>
    </div>
    
    <div class="form-section">
        <h2 class="section-title">상세 이미지</h2>
        <div class="form-group">
            <label for="detail_images">상세 이미지 (여러 장 선택 가능)</label>
            <input type="file" id="detail_images" name="detail_images[]" accept="image/*" multiple class="form-control">
            <small class="form-help">여러 장의 이미지를 업로드할 수 있습니다. 드래그하여 순서를 변경할 수 있습니다. (JPG, PNG, GIF, WEBP, 최대 10MB)</small>
            <div id="detail_images_preview" class="images-preview sortable-images"></div>
            <div id="detail_images_order" style="display: none;"></div>
        </div>
    </div>
    
    <div class="form-section">
        <h2 class="section-title">연결 상품</h2>
        <div class="form-group">
            <label for="product_search">상품 검색</label>
            <div class="product-search-box">
                <input type="text" id="product_search" class="form-control" placeholder="상품명으로 검색...">
                <button type="button" id="product_search_btn" class="btn-search">검색</button>
            </div>
            <div id="product_search_results" class="product-search-results"></div>
        </div>
        
        <div class="form-group">
            <label>추가된 상품 (드래그로 순서 변경 가능)</label>
            <div id="selected_products" class="selected-products-list" data-sortable="true">
                <p class="empty-message">추가된 상품이 없습니다.</p>
            </div>
            <input type="hidden" id="product_ids" name="product_ids[]" value="">
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">이벤트 등록</button>
        <a href="/MVNO/admin/content/event-manage.php" class="btn btn-secondary">취소</a>
    </div>
</form>

<style>
.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e5e7eb;
}

.admin-page-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.btn-back {
    padding: 8px 16px;
    background: #f3f4f6;
    color: #374151;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-back:hover {
    background: #e5e7eb;
}

.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
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

.event-form {
    background: #ffffff;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    max-width: 50%;
    margin: 0 auto;
}

.form-section {
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e5e7eb;
}

.form-section:last-child {
    border-bottom: none;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.required {
    color: #ef4444;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-help {
    display: block;
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 8px;
}

.image-preview, .images-preview {
    margin-top: 12px;
}

.image-preview img, .images-preview img {
    max-width: 100%;
    max-height: 300px;
    border-radius: 6px;
    margin-right: 12px;
    margin-bottom: 12px;
    border: 1px solid #e5e7eb;
}

/* 상세 이미지 드래그 앤 드롭 스타일 */
.images-preview.sortable-images {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.detail-image-item {
    position: relative;
    background: #ffffff;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px;
    cursor: move;
    transition: all 0.2s;
}

.detail-image-item:hover {
    border-color: #6366f1;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.detail-image-item.sortable-ghost {
    opacity: 0.4;
    background: #f3f4f6;
}

.detail-image-item .drag-handle {
    position: absolute;
    top: 8px;
    left: 8px;
    background: rgba(99, 102, 241, 0.9);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 14px;
    cursor: move;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
}

.detail-image-item .image-wrapper {
    width: 100%;
    padding-top: 75%; /* 4:3 비율 */
    position: relative;
    overflow: hidden;
    border-radius: 6px;
    background: #f9fafb;
    margin-bottom: 8px;
}

.detail-image-item .image-wrapper img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    margin: 0;
    border: none;
    border-radius: 6px;
}

.detail-image-item .remove-image-btn {
    width: 100%;
    padding: 8px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.detail-image-item .remove-image-btn:hover {
    background: #dc2626;
}

.product-search-box {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.product-search-box input {
    flex: 1;
}

.btn-search {
    padding: 10px 20px;
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-search:hover {
    background: #4f46e5;
}

.product-search-results {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 8px;
    background: #f9fafb;
    display: none;
}

.product-search-results.show {
    display: block;
}

.product-item {
    padding: 12px;
    background: white;
    border-radius: 4px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: background 0.2s;
    border: 1px solid #e5e7eb;
}

.product-item:hover {
    background: #f3f4f6;
}

.product-item.selected {
    background: #eef2ff;
    border-color: #6366f1;
}

.selected-products-list {
    min-height: 100px;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #f9fafb;
}

.selected-products-list .empty-message {
    color: #9ca3af;
    text-align: center;
    padding: 20px;
    margin: 0;
}

.selected-product-item {
    display: flex;
    align-items: center;
    padding: 12px;
    background: white;
    border-radius: 4px;
    margin-bottom: 8px;
    border: 1px solid #e5e7eb;
    cursor: move;
}

.selected-product-item:hover {
    background: #f3f4f6;
}

.selected-product-item .drag-handle {
    margin-right: 12px;
    color: #9ca3af;
    cursor: move;
}

.selected-product-item .product-info {
    flex: 1;
}

.selected-product-item .product-name {
    font-weight: 500;
    color: #1f2937;
}

.selected-product-item .product-type {
    font-size: 12px;
    color: #6b7280;
}

.selected-product-item .remove-btn {
    padding: 4px 8px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
}

.btn {
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
    border: none;
}

.btn-primary {
    background: #6366f1;
    color: white;
}

.btn-primary:hover {
    background: #4f46e5;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

@media (max-width: 768px) {
    .event-form {
        max-width: 100%;
        padding: 16px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .product-search-box {
        flex-direction: column;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 메인 이미지 미리보기
    const mainImageInput = document.getElementById('main_image');
    const mainImagePreview = document.getElementById('main_image_preview');
    
    mainImageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                mainImagePreview.innerHTML = '<img src="' + e.target.result + '" alt="메인 이미지 미리보기">';
            };
            reader.readAsDataURL(file);
        } else {
            mainImagePreview.innerHTML = '';
        }
    });
    
    // 상세 이미지 미리보기
    const detailImagesInput = document.getElementById('detail_images');
    const detailImagesPreview = document.getElementById('detail_images_preview');
    
    let detailImageFiles = []; // 업로드된 파일들을 순서대로 저장
    
    detailImagesInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        
        files.forEach(file => {
            // 중복 체크
            const isDuplicate = detailImageFiles.some(f => f.name === file.name && f.size === file.size);
            if (isDuplicate) {
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const fileId = 'img_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                
                // 파일 정보 저장
                const fileData = {
                    id: fileId,
                    file: file,
                    preview: e.target.result
                };
                detailImageFiles.push(fileData);
                
                // 이미지 아이템 생성
                const imageItem = document.createElement('div');
                imageItem.className = 'detail-image-item';
                imageItem.dataset.fileId = fileId;
                imageItem.innerHTML = `
                    <span class="drag-handle">☰</span>
                    <div class="image-wrapper">
                        <img src="${e.target.result}" alt="상세 이미지 미리보기">
                    </div>
                    <button type="button" class="remove-image-btn" onclick="removeDetailImage('${fileId}')">삭제</button>
                `;
                detailImagesPreview.appendChild(imageItem);
                
                // Sortable 초기화/업데이트
                initDetailImagesSortable();
            };
            reader.readAsDataURL(file);
        });
    });
    
    // 상세 이미지 삭제 함수
    window.removeDetailImage = function(fileId) {
        // 파일 목록에서 제거
        detailImageFiles = detailImageFiles.filter(f => f.id !== fileId);
        
        // DOM에서 제거
        const imageItem = detailImagesPreview.querySelector(`[data-file-id="${fileId}"]`);
        if (imageItem) {
            imageItem.remove();
        }
        
        // FileList 업데이트
        updateDetailImagesFileList();
        
        // Sortable 재초기화
        if (detailImageFiles.length > 0) {
            initDetailImagesSortable();
        }
    };
    
    // FileList 업데이트 함수 및 순서 정보 저장
    function updateDetailImagesFileList() {
        // 순서 정보를 JSON으로 저장 (서버에서 참조용)
        const orderData = detailImageFiles.map((fileData, index) => ({
            id: fileData.id,
            name: fileData.file.name,
            size: fileData.file.size,
            type: fileData.file.type,
            order: index
        }));
        document.getElementById('detail_images_data').value = JSON.stringify(orderData);
    }
    
    // 폼 제출 시 순서 정보 저장
    document.getElementById('eventForm').addEventListener('submit', function(e) {
        // 순서 정보를 JSON으로 저장
        if (detailImageFiles.length > 0) {
            const orderData = detailImageFiles.map((fileData, index) => ({
                name: fileData.file.name,
                size: fileData.file.size,
                type: fileData.file.type,
                order: index
            }));
            document.getElementById('detail_images_data').value = JSON.stringify(orderData);
        }
    });
    
    // 상세 이미지 Sortable 초기화
    let detailImagesSortable = null;
    function initDetailImagesSortable() {
        if (detailImagesSortable) {
            detailImagesSortable.destroy();
        }
        
        if (detailImageFiles.length > 0) {
            detailImagesSortable = new Sortable(detailImagesPreview, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function(evt) {
                    const oldIndex = evt.oldIndex;
                    const newIndex = evt.newIndex;
                    
                    // 배열 재정렬
                    const movedFile = detailImageFiles.splice(oldIndex, 1)[0];
                    detailImageFiles.splice(newIndex, 0, movedFile);
                    
                    // FileList 업데이트
                    updateDetailImagesFileList();
                }
            });
        }
    }
    
    // 상품 검색
    const productSearchInput = document.getElementById('product_search');
    const productSearchBtn = document.getElementById('product_search_btn');
    const productSearchResults = document.getElementById('product_search_results');
    const selectedProductsList = document.getElementById('selected_products');
    const productIdsInput = document.getElementById('product_ids');
    
    let selectedProducts = [];
    
    function searchProducts(query) {
        if (!query || query.trim().length < 2) {
            productSearchResults.classList.remove('show');
            return;
        }
        
        fetch(`/MVNO/api/search-products.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.products) {
                    displaySearchResults(data.products);
                } else {
                    productSearchResults.innerHTML = '<p style="padding: 12px; color: #6b7280;">검색 결과가 없습니다.</p>';
                    productSearchResults.classList.add('show');
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                productSearchResults.innerHTML = '<p style="padding: 12px; color: #ef4444;">검색 중 오류가 발생했습니다.</p>';
                productSearchResults.classList.add('show');
            });
    }
    
    function displaySearchResults(products) {
        productSearchResults.innerHTML = '';
        
        products.forEach(product => {
            const isSelected = selectedProducts.some(p => p.id === product.id);
            
            const item = document.createElement('div');
            item.className = 'product-item' + (isSelected ? ' selected' : '');
            item.innerHTML = `
                <div class="product-name">${escapeHtml(product.name)}</div>
                <div class="product-type">${escapeHtml(product.type)}</div>
            `;
            
            if (!isSelected) {
                item.addEventListener('click', function() {
                    addProduct(product);
                });
            }
            
            productSearchResults.appendChild(item);
        });
        
        productSearchResults.classList.add('show');
    }
    
    function addProduct(product) {
        if (selectedProducts.some(p => p.id === product.id)) {
            return;
        }
        
        selectedProducts.push(product);
        updateSelectedProductsList();
        updateProductIdsInput();
        
        // 검색 결과에서 선택된 항목 표시
        const items = productSearchResults.querySelectorAll('.product-item');
        items.forEach(item => {
            if (item.textContent.includes(product.name)) {
                item.classList.add('selected');
            }
        });
    }
    
    function removeProduct(productId) {
        selectedProducts = selectedProducts.filter(p => p.id !== productId);
        updateSelectedProductsList();
        updateProductIdsInput();
        
        // 검색 결과 업데이트
        const items = productSearchResults.querySelectorAll('.product-item');
        items.forEach(item => {
            item.classList.remove('selected');
        });
    }
    
    function updateSelectedProductsList() {
        if (selectedProducts.length === 0) {
            selectedProductsList.innerHTML = '<p class="empty-message">추가된 상품이 없습니다.</p>';
            return;
        }
        
        selectedProductsList.innerHTML = '';
        
        selectedProducts.forEach((product, index) => {
            const item = document.createElement('div');
            item.className = 'selected-product-item';
            item.dataset.productId = product.id;
            item.innerHTML = `
                <span class="drag-handle">☰</span>
                <div class="product-info">
                    <div class="product-name">${escapeHtml(product.name)}</div>
                    <div class="product-type">${escapeHtml(product.type)}</div>
                </div>
                <button type="button" class="remove-btn" onclick="removeProductById(${product.id})">삭제</button>
            `;
            selectedProductsList.appendChild(item);
        });
        
        // Sortable 초기화
        if (selectedProducts.length > 0) {
            initSortable();
        }
    }
    
    function updateProductIdsInput() {
        const ids = selectedProducts.map(p => p.id);
        productIdsInput.value = ids.join(',');
        
        // hidden input 업데이트 (폼 제출용)
        const hiddenInputs = document.querySelectorAll('input[name="product_ids[]"]');
        hiddenInputs.forEach(input => {
            if (input !== productIdsInput) {
                input.remove();
            }
        });
        
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'product_ids[]';
            input.value = id;
            productIdsInput.parentNode.appendChild(input);
        });
    }
    
    function initSortable() {
        if (selectedProductsList.sortableInstance) {
            selectedProductsList.sortableInstance.destroy();
        }
        
        selectedProductsList.sortableInstance = new Sortable(selectedProductsList, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                const oldIndex = evt.oldIndex;
                const newIndex = evt.newIndex;
                
                // 배열 재정렬
                const movedProduct = selectedProducts.splice(oldIndex, 1)[0];
                selectedProducts.splice(newIndex, 0, movedProduct);
                
                updateProductIdsInput();
            }
        });
    }
    
    window.removeProductById = function(productId) {
        removeProduct(productId);
    };
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // 검색 이벤트
    productSearchBtn.addEventListener('click', function() {
        searchProducts(productSearchInput.value);
    });
    
    productSearchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchProducts(productSearchInput.value);
        }
    });
    
    // 검색 결과 외부 클릭 시 닫기
    document.addEventListener('click', function(e) {
        if (!productSearchResults.contains(e.target) && 
            !productSearchInput.contains(e.target) && 
            !productSearchBtn.contains(e.target)) {
            productSearchResults.classList.remove('show');
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>

