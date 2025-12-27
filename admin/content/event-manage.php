<?php
/**
 * 이벤트 관리 페이지
 * 경로: /MVNO/admin/content/event-manage.php
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

// 성공 메시지 처리
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'created') {
        $success = '이벤트가 등록되었습니다.';
    } elseif ($_GET['success'] === 'updated') {
        $success = '이벤트가 수정되었습니다.';
    } elseif ($_GET['success'] === 'deleted') {
        $success = '이벤트가 삭제되었습니다.';
    }
}

// 이벤트 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_event') {
    $eventId = trim($_POST['event_id'] ?? '');
    
    if (!empty($eventId) && $pdo) {
        try {
            $pdo->beginTransaction();
            
            // 이벤트 삭제 (CASCADE로 관련 데이터도 삭제됨)
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
            $stmt->execute([':id' => $eventId]);
            
            $pdo->commit();
            header('Location: /MVNO/admin/content/event-manage.php?success=deleted');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = '이벤트 삭제 중 오류가 발생했습니다.';
            error_log('Event delete error: ' . $e->getMessage());
        }
    }
}

// 페이지네이션 설정
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 필터 설정
$eventType = $_GET['type'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');

// 이벤트 목록 조회
$events = [];
$totalEvents = 0;
$totalPages = 1;

if ($pdo) {
    try {
        $whereConditions = [];
        $params = [];
        
        // 이벤트 타입 필터
        if ($eventType && in_array($eventType, ['plan', 'promotion', 'card'])) {
            $whereConditions[] = 'event_type = :event_type';
            $params[':event_type'] = $eventType;
        }
        
        // 검색 필터
        if ($searchQuery) {
            $whereConditions[] = 'title LIKE :search';
            $params[':search'] = '%' . $searchQuery . '%';
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // 전체 개수 조회
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM events {$whereClause}");
        $countStmt->execute($params);
        $totalEvents = $countStmt->fetch()['total'];
        $totalPages = ceil($totalEvents / $perPage);
        
        // 이벤트 목록 조회
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;
        
        $stmt = $pdo->prepare("
            SELECT * FROM events 
            {$whereClause}
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log('Event list error: ' . $e->getMessage());
        $error = '이벤트 목록을 불러오는 중 오류가 발생했습니다.';
    }
}

// 이벤트 타입 한글 변환
function getEventTypeName($type) {
    $types = [
        'plan' => '요금제',
        'promotion' => '프로모션',
        'card' => '제휴카드'
    ];
    return $types[$type] ?? $type;
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page-header">
    <h1>이벤트 관리</h1>
    <a href="/MVNO/admin/content/event-register.php" class="btn-primary">이벤트 등록</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- 필터 및 검색 -->
<div class="filter-section">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label for="type">이벤트 타입</label>
            <select id="type" name="type" class="form-control">
                <option value="">전체</option>
                <option value="plan" <?php echo $eventType === 'plan' ? 'selected' : ''; ?>>요금제</option>
                <option value="promotion" <?php echo $eventType === 'promotion' ? 'selected' : ''; ?>>프로모션</option>
                <option value="card" <?php echo $eventType === 'card' ? 'selected' : ''; ?>>제휴카드</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="search">검색</label>
            <input type="text" id="search" name="search" class="form-control" 
                   placeholder="이벤트 제목으로 검색..." value="<?php echo htmlspecialchars($searchQuery); ?>">
        </div>
        
        <div class="filter-group">
            <button type="submit" class="btn-search">검색</button>
            <a href="/MVNO/admin/content/event-manage.php" class="btn-reset">초기화</a>
        </div>
    </form>
</div>

<!-- 이벤트 목록 -->
<div class="events-table-container">
    <table class="events-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>제목</th>
                <th>타입</th>
                <th>메인 이미지</th>
                <th>기간</th>
                <th>공개 상태</th>
                <th>등록일</th>
                <th>관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($events)): ?>
                <tr>
                    <td colspan="8" class="empty-message">등록된 이벤트가 없습니다.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(substr($event['id'], 0, 20)); ?>...</td>
                        <td>
                            <a href="/MVNO/event/event-detail.php?id=<?php echo htmlspecialchars($event['id']); ?>" 
                               target="_blank" class="event-title-link">
                                <?php echo htmlspecialchars($event['title']); ?>
                            </a>
                        </td>
                        <td><?php echo getEventTypeName($event['event_type']); ?></td>
                        <td>
                            <?php if ($event['main_image']): ?>
                                <img src="<?php echo htmlspecialchars($event['main_image']); ?>" 
                                     alt="메인 이미지" class="thumbnail-image">
                            <?php else: ?>
                                <span class="no-image">이미지 없음</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($event['start_at'] || $event['end_at']): ?>
                                <?php echo $event['start_at'] ? date('Y-m-d', strtotime($event['start_at'])) : '-'; ?>
                                ~
                                <?php echo $event['end_at'] ? date('Y-m-d', strtotime($event['end_at'])) : '-'; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            // start_at과 end_at을 기준으로 공개/비공개 판단
                            $now = time();
                            $startAt = $event['start_at'] ? strtotime($event['start_at']) : null;
                            $endAt = $event['end_at'] ? strtotime($event['end_at']) : null;
                            
                            $isPublished = false;
                            if ($startAt && $endAt) {
                                // 시작일과 종료일이 모두 있으면 현재 날짜가 그 사이에 있는지 확인
                                $isPublished = ($now >= $startAt && $now <= $endAt);
                            } elseif ($startAt) {
                                // 시작일만 있으면 시작일 이후인지 확인
                                $isPublished = ($now >= $startAt);
                            } elseif ($endAt) {
                                // 종료일만 있으면 종료일 이전인지 확인
                                $isPublished = ($now <= $endAt);
                            } else {
                                // 시작일과 종료일이 모두 없으면 비공개
                                $isPublished = false;
                            }
                            ?>
                            <span class="status-badge <?php echo $isPublished ? 'published' : 'unpublished'; ?>">
                                <?php echo $isPublished ? '공개' : '비공개'; ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($event['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="/MVNO/event/event-detail.php?id=<?php echo htmlspecialchars($event['id']); ?>" 
                                   target="_blank" class="btn-view">보기</a>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                    <input type="hidden" name="action" value="delete_event">
                                    <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event['id']); ?>">
                                    <button type="submit" class="btn-delete">삭제</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- 페이지네이션 -->
<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&type=<?php echo htmlspecialchars($eventType); ?>&search=<?php echo htmlspecialchars($searchQuery); ?>" 
               class="page-link">이전</a>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="?page=<?php echo $i; ?>&type=<?php echo htmlspecialchars($eventType); ?>&search=<?php echo htmlspecialchars($searchQuery); ?>" 
               class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>&type=<?php echo htmlspecialchars($eventType); ?>&search=<?php echo htmlspecialchars($searchQuery); ?>" 
               class="page-link">다음</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

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
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
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

.filter-section {
    background: #ffffff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filter-form {
    display: flex;
    gap: 16px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-group label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.form-control {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.btn-search, .btn-reset {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    border: none;
}

.btn-search {
    background: #6366f1;
    color: white;
}

.btn-search:hover {
    background: #4f46e5;
}

.btn-reset {
    background: #f3f4f6;
    color: #374151;
}

.btn-reset:hover {
    background: #e5e7eb;
}

.events-table-container {
    background: #ffffff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.events-table {
    width: 100%;
    border-collapse: collapse;
}

.events-table thead {
    background: #f9fafb;
}

.events-table th {
    padding: 12px;
    text-align: left;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}

.events-table td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

.events-table tbody tr:hover {
    background: #f9fafb;
}

.event-title-link {
    color: #6366f1;
    text-decoration: none;
    font-weight: 500;
}

.event-title-link:hover {
    text-decoration: underline;
}

.thumbnail-image {
    width: 80px;
    height: 45px;
    object-fit: cover;
    border-radius: 4px;
}

.no-image {
    color: #9ca3af;
    font-size: 12px;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.published {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.unpublished {
    background: #fee2e2;
    color: #991b1b;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-view, .btn-delete {
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-block;
}

.btn-view {
    background: #e0e7ff;
    color: #4338ca;
}

.btn-view:hover {
    background: #c7d2fe;
}

.btn-delete {
    background: #fee2e2;
    color: #991b1b;
}

.btn-delete:hover {
    background: #fecaca;
}

.empty-message {
    text-align: center;
    padding: 40px;
    color: #9ca3af;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
}

.page-link {
    padding: 8px 12px;
    background: #ffffff;
    color: #374151;
    text-decoration: none;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.page-link:hover {
    background: #f3f4f6;
    border-color: #6366f1;
}

.page-link.active {
    background: #6366f1;
    color: white;
    border-color: #6366f1;
}

@media (max-width: 768px) {
    .filter-form {
        flex-direction: column;
    }
    
    .events-table {
        font-size: 12px;
    }
    
    .events-table th,
    .events-table td {
        padding: 8px;
    }
}
</style>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>

