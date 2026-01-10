<?php
/**
 * 배너 관리 페이지
 * 경로: /MVNO/admin/content/banner-manage.php
 */

require_once __DIR__ . '/../../includes/data/path-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/home-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';
$pdo = getDBConnection();

// 성공 메시지 처리
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'banner_saved') {
        $success = '배너 설정이 저장되었습니다.';
    }
}

// 배너 설정 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_site_banners') {
    $large_banners = isset($_POST['large_banners']) && is_array($_POST['large_banners']) ? $_POST['large_banners'] : [];
    $small_banners = isset($_POST['small_banners']) && is_array($_POST['small_banners']) ? $_POST['small_banners'] : [];
    
    // ID를 문자열로 변환하여 일관성 유지
    $large_banners = array_map('strval', $large_banners);
    $small_banners = array_map('strval', array_slice($small_banners, 0, 2)); // 서브배너는 최대 2개
    
    try {
        // 기존 설정을 한 번에 가져와서 두 배너를 동시에 저장
        $home_settings = getHomeSettings();
        
        $home_settings['site_large_banners'] = $large_banners;
        $home_settings['site_small_banners'] = $small_banners;
        
        $saveResult = saveHomeSettings($home_settings);
        
        if ($saveResult) {
            header('Location: banner-manage.php?success=banner_saved');
            exit;
        } else {
            $error = '배너 설정 저장에 실패했습니다.';
        }
    } catch (Exception $e) {
        $error = '배너 설정 저장 중 오류가 발생했습니다: ' . $e->getMessage();
        error_log('Banner save error: ' . $e->getMessage());
    }
}

// 현재 배너 설정 가져오기
$home_settings = getHomeSettings();
$current_large_banners = $home_settings['site_large_banners'] ?? [];
$current_small_banners = $home_settings['site_small_banners'] ?? [];

// ID를 문자열로 변환하여 비교 일관성 유지
$current_large_banners = array_map('strval', $current_large_banners);
$current_small_banners = array_map('strval', $current_small_banners);

// 이미지 경로 정규화 함수 (getAssetPath 사용)
function normalizeImagePath($path) {
    if (empty($path)) {
        return '';
    }
    
    $imagePath = trim($path);
    
    // 이미 전체 URL이면 그대로 사용
    if (preg_match('/^https?:\/\//', $imagePath)) {
        return $imagePath;
    }
    
    // 이미 /로 시작하는 절대 경로면 getAssetPath 사용
    if (strpos($imagePath, '/') === 0) {
        return getAssetPath($imagePath);
    }
    
    // 파일명만 있는 경우 (확장자가 있고 슬래시가 없음)
    if (strpos($imagePath, '/') === false && preg_match('/\.(webp|jpg|jpeg|png|gif)$/i', $imagePath)) {
        return getAssetPath('/uploads/events/' . $imagePath);
    }
    
    // 상대 경로인 경우
    return getAssetPath('/' . $imagePath);
}

// 모든 이벤트 가져오기 (배너 선택용) - 공개된 이벤트만
$all_events = [];
if ($pdo) {
    try {
        // is_published 컬럼 존재 여부 확인
        $stmt = $pdo->query("
            SELECT COUNT(*) as cnt 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'events' 
            AND COLUMN_NAME = 'is_published'
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $hasIsPublished = $result['cnt'] > 0;
        
        // 공개 상태 및 기간 조건 추가
        $whereConditions = [];
        if ($hasIsPublished) {
            // is_published가 0이면 기간과 상관없이 비공개
            $whereConditions[] = "(is_published IS NULL OR is_published != 0)";
        }
        
        // 공개 기간 확인
        $whereConditions[] = "(start_at IS NULL OR start_at <= CURDATE())";
        $whereConditions[] = "(end_at IS NULL OR end_at >= CURDATE())";
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // SELECT *를 사용하여 모든 컬럼 가져오기 (안전한 방법)
        $stmt = $pdo->query("SELECT * FROM events {$whereClause} ORDER BY created_at DESC");
        $all_events_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // image 필드 추가 (main_image 또는 image_url 중 사용 가능한 것 사용)
        foreach ($all_events_raw as &$event) {
            $imagePath = '';
            if (!empty($event['main_image'])) {
                $imagePath = $event['main_image'];
            } elseif (!empty($event['image_url'])) {
                $imagePath = $event['image_url'];
            }
            
            // 이미지 경로 정규화
            $event['image'] = normalizeImagePath($imagePath);
            
            // link 필드 추가
            if (isset($event['link_url'])) {
                $event['link'] = $event['link_url'];
            } else {
                $event['link'] = '';
            }
        }
        unset($event);
        
        $all_events = $all_events_raw;
    } catch (PDOException $e) {
        error_log('Events query error: ' . $e->getMessage());
        error_log('Events query error trace: ' . $e->getTraceAsString());
        $error = '이벤트 목록을 불러오는 중 오류가 발생했습니다: ' . $e->getMessage();
    }
}

// 이벤트를 ID로 매핑 (빠른 검색을 위해)
$events_by_id = [];
foreach ($all_events as $event) {
    $events_by_id[(string)$event['id']] = $event;
}

// 현재 배너로 설정된 이벤트 제외하고 이벤트 목록 구성
$available_events = [];
$main_banner_events = [];
$sub_banner_events = [];

// 유효하지 않은 이벤트 ID 수집 (비공개 또는 공개기간 지난 것)
$invalid_large_banner_ids = [];
$invalid_small_banner_ids = [];

// 저장된 순서대로 메인배너 이벤트 구성 (유효한 것만)
foreach ($current_large_banners as $banner_id) {
    if (isset($events_by_id[$banner_id])) {
        $main_banner_events[] = $events_by_id[$banner_id];
    } else {
        // 유효하지 않은 이벤트 ID 수집
        $invalid_large_banner_ids[] = $banner_id;
    }
}

// 저장된 순서대로 서브배너 이벤트 구성 (유효한 것만)
foreach ($current_small_banners as $banner_id) {
    if (isset($events_by_id[$banner_id])) {
        $sub_banner_events[] = $events_by_id[$banner_id];
    } else {
        // 유효하지 않은 이벤트 ID 수집
        $invalid_small_banner_ids[] = $banner_id;
    }
}

// 유효하지 않은 이벤트가 있으면 배너 설정에서 자동 제거
if (!empty($invalid_large_banner_ids) || !empty($invalid_small_banner_ids)) {
    $needs_save = false;
    
    // 메인배너에서 유효하지 않은 ID 제거
    if (!empty($invalid_large_banner_ids)) {
        $home_settings['site_large_banners'] = array_values(array_filter(
            $home_settings['site_large_banners'],
            function($id) use ($invalid_large_banner_ids) {
                return !in_array((string)$id, array_map('strval', $invalid_large_banner_ids), true);
            }
        ));
        $needs_save = true;
    }
    
    // 서브배너에서 유효하지 않은 ID 제거
    if (!empty($invalid_small_banner_ids)) {
        $home_settings['site_small_banners'] = array_values(array_filter(
            $home_settings['site_small_banners'],
            function($id) use ($invalid_small_banner_ids) {
                return !in_array((string)$id, array_map('strval', $invalid_small_banner_ids), true);
            }
        ));
        $needs_save = true;
    }
    
    // 설정 저장
    if ($needs_save) {
        saveHomeSettings($home_settings);
        // 현재 설정 다시 가져오기
        $home_settings = getHomeSettings();
        $current_large_banners = $home_settings['site_large_banners'] ?? [];
        $current_small_banners = $home_settings['site_small_banners'] ?? [];
        $current_large_banners = array_map('strval', $current_large_banners);
        $current_small_banners = array_map('strval', $current_small_banners);
    }
}

// 배너에 포함되지 않은 이벤트만 available_events에 추가
$banner_event_ids = array_merge($current_large_banners, $current_small_banners);
foreach ($all_events as $event) {
    $event_id_str = (string)$event['id'];
    if (!in_array($event_id_str, $banner_event_ids, true)) {
        $available_events[] = $event;
    }
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page-header">
    <h1>배너 관리</h1>
    <button type="button" id="save-banners-btn" class="btn-primary">저장</button>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- 배너 관리 레이아웃 -->
<div class="banner-management-layout">
    <!-- 왼쪽: 이벤트 목록 -->
    <div class="events-list-panel">
        <div class="panel-header">
            <h2>이벤트 목록</h2>
        </div>
        <div class="events-list-container" id="events-list">
            <?php if (empty($available_events)): ?>
                <div class="empty-state">등록된 이벤트가 없습니다.</div>
            <?php else: ?>
                <?php foreach ($available_events as $event): 
                    $event_image = $event['image'] ?? '';
                ?>
                    <div class="event-item" 
                         draggable="true" 
                         data-event-id="<?php echo htmlspecialchars($event['id']); ?>"
                         data-event-title="<?php echo htmlspecialchars($event['title']); ?>"
                         data-event-image="<?php echo htmlspecialchars($event_image); ?>">
                        <?php if (!empty($event_image)): ?>
                            <img src="<?php echo htmlspecialchars($event_image); ?>" 
                                 alt="이벤트 이미지" class="event-item-image">
                        <?php else: ?>
                            <div class="event-item-image-placeholder">이미지 없음</div>
                        <?php endif; ?>
                        <div class="event-item-title"><?php echo htmlspecialchars($event['title']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 오른쪽: 배너 설정 영역 -->
    <div class="banners-panel">
        <!-- 메인배너 영역 -->
        <div class="banner-section">
            <div class="panel-header">
                <h2>메인배너</h2>
            </div>
            <div class="banner-drop-zone" 
                 id="main-banner-zone"
                 data-banner-type="large">
                <div class="banner-list" id="main-banner-list">
                    <?php if (empty($main_banner_events)): ?>
                        <div class="drop-zone-placeholder">이벤트를 드래그하여 추가하세요</div>
                    <?php else: ?>
                        <?php foreach ($main_banner_events as $event): 
                            $event_image = $event['image'] ?? '';
                        ?>
                            <div class="banner-item" 
                                 draggable="true" 
                                 data-event-id="<?php echo htmlspecialchars($event['id']); ?>"
                                 data-banner-type="large">
                                <?php if (!empty($event_image)): ?>
                                    <img src="<?php echo htmlspecialchars($event_image); ?>" 
                                         alt="배너 이미지" class="banner-item-image">
                                <?php else: ?>
                                    <div class="banner-item-image-placeholder">이미지 없음</div>
                                <?php endif; ?>
                                <div class="banner-item-title"><?php echo htmlspecialchars($event['title']); ?></div>
                                <button type="button" class="banner-item-remove" onclick="removeBannerItem(this, 'large')">×</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 서브배너 영역 -->
        <div class="banner-section">
            <div class="panel-header">
                <h2>서브배너 <span class="banner-limit">(최대 2개)</span></h2>
            </div>
            <div class="banner-drop-zone" 
                 id="sub-banner-zone"
                 data-banner-type="small"
                 data-max-items="2">
                <div class="banner-list" id="sub-banner-list">
                    <?php if (empty($sub_banner_events)): ?>
                        <div class="drop-zone-placeholder">이벤트를 드래그하여 추가하세요</div>
                    <?php else: ?>
                        <?php foreach ($sub_banner_events as $event): 
                            $event_image = $event['image'] ?? '';
                        ?>
                            <div class="banner-item" 
                                 draggable="true" 
                                 data-event-id="<?php echo htmlspecialchars($event['id']); ?>"
                                 data-banner-type="small">
                                <?php if (!empty($event_image)): ?>
                                    <img src="<?php echo htmlspecialchars($event_image); ?>" 
                                         alt="배너 이미지" class="banner-item-image">
                                <?php else: ?>
                                    <div class="banner-item-image-placeholder">이미지 없음</div>
                                <?php endif; ?>
                                <div class="banner-item-title"><?php echo htmlspecialchars($event['title']); ?></div>
                                <button type="button" class="banner-item-remove" onclick="removeBannerItem(this, 'small')">×</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 배너 저장 폼 (히든) -->
<form method="POST" id="banner-form" style="display: none;">
    <input type="hidden" name="action" value="save_site_banners">
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

.btn-primary {
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

.btn-primary:hover {
    background: #4f46e5;
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

/* 배너 관리 레이아웃 */
.banner-management-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

/* 패널 공통 스타일 */
.events-list-panel,
.banners-panel {
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.panel-header {
    padding: 16px 20px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.panel-header h2 {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.banner-limit {
    font-size: 14px;
    font-weight: 400;
    color: #6b7280;
}

/* 이벤트 목록 패널 */
.events-list-container {
    padding: 16px;
    overflow-y: auto;
    max-height: calc(100vh - 300px);
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.event-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    cursor: move;
    transition: all 0.2s;
}

.event-item:hover {
    background: #f3f4f6;
    border-color: #6366f1;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.event-item.dragging {
    opacity: 0.5;
    background: #e5e7eb;
}

.event-item-image {
    width: 60px;
    height: 34px;
    object-fit: cover;
    border-radius: 4px;
    flex-shrink: 0;
}

.event-item-image-placeholder {
    width: 60px;
    height: 34px;
    background: #e5e7eb;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #9ca3af;
    flex-shrink: 0;
}

.event-item-title {
    flex: 1;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
    font-size: 14px;
}

/* 배너 섹션 */
.banner-section {
    border-bottom: 1px solid #e5e7eb;
}

.banner-section:last-child {
    border-bottom: none;
}

.banner-drop-zone {
    min-height: 200px;
    padding: 16px;
}

.banner-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-height: 150px;
}

.banner-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f0f9ff;
    border: 2px solid #93c5fd;
    border-radius: 8px;
    cursor: move;
    transition: all 0.2s;
}

.banner-item:hover {
    background: #e0f2fe;
    border-color: #3b82f6;
}

.banner-item.dragging {
    opacity: 0.5;
    background: #e5e7eb;
}

.banner-item-image {
    width: 80px;
    height: 45px;
    object-fit: cover;
    border-radius: 4px;
    flex-shrink: 0;
}

.banner-item-image-placeholder {
    width: 80px;
    height: 45px;
    background: #e5e7eb;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #9ca3af;
    flex-shrink: 0;
}

.banner-item-title {
    flex: 1;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.banner-item-remove {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 50%;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
}

.banner-item:hover .banner-item-remove {
    opacity: 1;
}

.banner-item-remove:hover {
    background: #dc2626;
}

.drop-zone-placeholder {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
    font-size: 14px;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    background: #f9fafb;
}

.banner-drop-zone.drag-over {
    background: #eff6ff;
}

.banner-drop-zone.drag-over .drop-zone-placeholder {
    border-color: #3b82f6;
    background: #dbeafe;
    color: #1e40af;
}

@media (max-width: 1024px) {
    .banner-management-layout {
        grid-template-columns: 1fr;
    }
    
    .events-list-container {
        max-height: 400px;
    }
}
</style>

<script>
let draggedElement = null;
let draggedEventData = null;

// 이벤트 아이템 드래그 시작
document.addEventListener('DOMContentLoaded', function() {
    // 이벤트 목록의 드래그 이벤트
    const eventItems = document.querySelectorAll('#events-list .event-item');
    eventItems.forEach(item => {
        item.addEventListener('dragstart', function(e) {
            draggedElement = this;
            draggedEventData = {
                id: this.getAttribute('data-event-id'),
                title: this.getAttribute('data-event-title'),
                image: this.getAttribute('data-event-image')
            };
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        item.addEventListener('dragend', function(e) {
            this.classList.remove('dragging');
        });
    });

    // 초기 배너 아이템에 드래그 앤 드롭 설정
    setupBannerItemDragDrop();
    
    // 배너 목록에 dragover 이벤트 추가 (빈 공간 드롭 지원)
    document.querySelectorAll('.banner-list').forEach(bannerList => {
        bannerList.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            // 배너 아이템을 드래그하는 경우, 배너 목록 내에서 순서 변경
            if (draggedElement && draggedElement.classList.contains('banner-item')) {
                const afterElement = getDragAfterElement(bannerList, e.clientY);
                const dragging = bannerList.querySelector('.dragging');
                
                if (dragging && afterElement == null) {
                    bannerList.appendChild(dragging);
                } else if (dragging && afterElement) {
                    bannerList.insertBefore(dragging, afterElement);
                }
            }
        });
        
        bannerList.addEventListener('drop', function(e) {
            e.preventDefault();
            // 배너 아이템 간 드롭은 각 아이템의 drop 이벤트에서 처리됨
        });
    });

    // 드롭 존 설정
    const dropZones = document.querySelectorAll('.banner-drop-zone');
    dropZones.forEach(zone => {
        const bannerList = zone.querySelector('.banner-list');
        
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            // 배너 아이템을 드래그하는 경우는 banner-list의 dragover에서 처리
            if (!draggedElement || !draggedElement.classList.contains('banner-item')) {
                this.classList.add('drag-over');
            }
        });

        zone.addEventListener('dragleave', function(e) {
            // 배너 아이템을 드래그하는 경우가 아니면 drag-over 제거
            if (!draggedElement || !draggedElement.classList.contains('banner-item')) {
                this.classList.remove('drag-over');
            }
        });

        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');

            if (!draggedEventData) return;

            const bannerType = this.getAttribute('data-banner-type');
            const maxItems = parseInt(this.getAttribute('data-max-items') || '999');

            // 배너 아이템을 같은 목록 내에서 이동하는 경우
            if (draggedElement.classList.contains('banner-item')) {
                // 이미 같은 목록에 있으면 순서만 변경됨 (dragover에서 처리됨)
                // 다른 목록으로 이동하는 경우는 여기서 처리
                const sourceList = draggedElement.closest('.banner-list');
                const targetList = bannerList;
                
                if (sourceList !== targetList) {
                    // 서브배너 최대 개수 체크
                    if (bannerType === 'small') {
                        const currentItems = targetList.querySelectorAll('.banner-item');
                        if (currentItems.length >= maxItems) {
                            alert('서브배너는 최대 ' + maxItems + '개까지만 추가할 수 있습니다.');
                            return;
                        }
                    }
                    
                    // 다른 목록으로 이동
                    const afterElement = getDragAfterElement(targetList, e.clientY);
                    if (afterElement == null) {
                        targetList.appendChild(draggedElement);
                    } else {
                        targetList.insertBefore(draggedElement, afterElement);
                    }
                    
                    // 원래 목록이 비어있으면 placeholder 추가
                    if (sourceList && sourceList.querySelectorAll('.banner-item').length === 0) {
                        const placeholder = document.createElement('div');
                        placeholder.className = 'drop-zone-placeholder';
                        placeholder.textContent = '이벤트를 드래그하여 추가하세요';
                        sourceList.appendChild(placeholder);
                    }
                }
                // 같은 목록 내에서 순서 변경은 dragover에서 이미 처리됨
            } else {
                // 이벤트 목록에서 배너 목록으로 추가
                // 서브배너 최대 개수 체크
                if (bannerType === 'small') {
                    const currentItems = bannerList.querySelectorAll('.banner-item');
                    if (currentItems.length >= maxItems) {
                        alert('서브배너는 최대 ' + maxItems + '개까지만 추가할 수 있습니다.');
                        return;
                    }
                }

                // 이벤트 목록에서 제거
                draggedElement.remove();

                // 배너 목록에 추가
                const afterElement = getDragAfterElement(bannerList, e.clientY);
                const newItem = addBannerItem(this, draggedEventData, bannerType, afterElement);
                
                // 새로 추가된 아이템에 드래그 앤 드롭 설정
                if (newItem) {
                    setupBannerItemDragDrop();
                }

                // placeholder 제거
                const placeholder = bannerList.querySelector('.drop-zone-placeholder');
                if (placeholder) {
                    placeholder.remove();
                }
            }
        });
    });

    // 저장 버튼
    const saveBtn = document.getElementById('save-banners-btn');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            saveBanners();
        });
    }
});

// 배너 아이템 추가
function addBannerItem(zone, eventData, bannerType, insertBefore = null) {
    const bannerList = zone.querySelector('.banner-list');
    if (!bannerList) return null;

    const bannerItem = document.createElement('div');
    bannerItem.className = 'banner-item';
    bannerItem.setAttribute('draggable', 'true');
    bannerItem.setAttribute('data-event-id', eventData.id);
    bannerItem.setAttribute('data-banner-type', bannerType);

    let imageHtml = '';
    if (eventData.image && eventData.image.trim() !== '') {
        imageHtml = `<img src="${eventData.image}" alt="배너 이미지" class="banner-item-image">`;
    } else {
        imageHtml = '<div class="banner-item-image-placeholder">이미지 없음</div>';
    }

    bannerItem.innerHTML = `
        ${imageHtml}
        <div class="banner-item-title">${eventData.title}</div>
        <button type="button" class="banner-item-remove" onclick="removeBannerItem(this, '${bannerType}')">×</button>
    `;

    // 삽입 위치 지정
    if (insertBefore) {
        bannerList.insertBefore(bannerItem, insertBefore);
    } else {
        bannerList.appendChild(bannerItem);
    }
    
    // 드래그 앤 드롭 이벤트는 setupBannerItemDragDrop에서 설정됨
    
    return bannerItem;
}

// 드래그 위치 계산 함수 (전역으로 사용)
function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.banner-item:not(.dragging)')];
    
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

// 배너 아이템 드래그 앤 드롭 설정 함수 (전역으로 사용)
function setupBannerItemDragDrop() {
    const bannerItems = document.querySelectorAll('.banner-item');
    bannerItems.forEach(item => {
        // 기존 이벤트 리스너 제거 (중복 방지)
        const newItem = item.cloneNode(true);
        item.parentNode.replaceChild(newItem, item);
        
        newItem.addEventListener('dragstart', function(e) {
            draggedElement = this;
            draggedEventData = {
                id: this.getAttribute('data-event-id'),
                title: this.querySelector('.banner-item-title').textContent,
                image: this.querySelector('.banner-item-image')?.src || ''
            };
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        newItem.addEventListener('dragend', function(e) {
            this.classList.remove('dragging');
            document.querySelectorAll('.banner-item').forEach(i => i.classList.remove('drag-over'));
        });
        
        newItem.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            if (this !== draggedElement && draggedElement) {
                this.classList.add('drag-over');
            }
        });
        
        newItem.addEventListener('dragleave', function(e) {
            this.classList.remove('drag-over');
        });
        
        newItem.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            
            if (draggedElement && this !== draggedElement) {
                const bannerList = this.closest('.banner-list');
                if (!bannerList) return;
                
                const allItems = Array.from(bannerList.querySelectorAll('.banner-item'));
                const draggedIndex = allItems.indexOf(draggedElement);
                const targetIndex = allItems.indexOf(this);
                
                if (draggedIndex !== -1 && targetIndex !== -1) {
                    if (draggedIndex < targetIndex) {
                        bannerList.insertBefore(draggedElement, this.nextSibling);
                    } else {
                        bannerList.insertBefore(draggedElement, this);
                    }
                }
            }
        });
    });
}

// 배너 아이템 제거
function removeBannerItem(button, bannerType) {
    const bannerItem = button.closest('.banner-item');
    if (!bannerItem) return;

    const eventData = {
        id: bannerItem.getAttribute('data-event-id'),
        title: bannerItem.querySelector('.banner-item-title').textContent,
        image: bannerItem.querySelector('.banner-item-image')?.src || bannerItem.querySelector('.banner-item-image-placeholder') ? '' : ''
    };

    bannerItem.remove();

    // 이벤트 목록에 다시 추가
    const eventsList = document.getElementById('events-list');
    const eventItem = document.createElement('div');
    eventItem.className = 'event-item';
    eventItem.setAttribute('draggable', 'true');
    eventItem.setAttribute('data-event-id', eventData.id);
    eventItem.setAttribute('data-event-title', eventData.title);
    eventItem.setAttribute('data-event-image', eventData.image);

    let imageHtml = '';
    if (eventData.image && eventData.image.trim() !== '') {
        imageHtml = `<img src="${eventData.image}" alt="이벤트 이미지" class="event-item-image">`;
    } else {
        imageHtml = '<div class="event-item-image-placeholder">이미지 없음</div>';
    }

    eventItem.innerHTML = `
        ${imageHtml}
        <div class="event-item-title">${eventData.title}</div>
    `;

    // 드래그 이벤트 추가
    eventItem.addEventListener('dragstart', function(e) {
        draggedElement = this;
        draggedEventData = {
            id: this.getAttribute('data-event-id'),
            title: this.getAttribute('data-event-title'),
            image: this.getAttribute('data-event-image')
        };
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });

    eventItem.addEventListener('dragend', function(e) {
        this.classList.remove('dragging');
    });

    eventsList.appendChild(eventItem);

    // 빈 배너 목록에 placeholder 추가
    const bannerList = button.closest('.banner-list');
    if (bannerList && bannerList.querySelectorAll('.banner-item').length === 0) {
        const placeholder = document.createElement('div');
        placeholder.className = 'drop-zone-placeholder';
        placeholder.textContent = '이벤트를 드래그하여 추가하세요';
        bannerList.appendChild(placeholder);
    }
}

// 배너 저장
function saveBanners() {
    const mainBannerList = document.getElementById('main-banner-list');
    const subBannerList = document.getElementById('sub-banner-list');

    const largeBanners = [];
    const smallBanners = [];

    // 메인배너 ID 수집
    mainBannerList.querySelectorAll('.banner-item').forEach(item => {
        largeBanners.push(item.getAttribute('data-event-id'));
    });

    // 서브배너 ID 수집
    subBannerList.querySelectorAll('.banner-item').forEach(item => {
        smallBanners.push(item.getAttribute('data-event-id'));
    });

    // 폼 생성 및 제출
    const form = document.getElementById('banner-form');
    const existingInputs = form.querySelectorAll('input[type="hidden"][name^="large_banners"], input[type="hidden"][name^="small_banners"]');
    existingInputs.forEach(input => input.remove());

    largeBanners.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'large_banners[]';
        input.value = id;
        form.appendChild(input);
    });

    smallBanners.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'small_banners[]';
        input.value = id;
        form.appendChild(input);
    });

    form.submit();
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
