<?php
/**
 * 이벤트 상세 페이지
 * 경로: /MVNO/event/event-detail.php?id=evt_xxx
 */

$current_page = 'event';
$is_main_page = false;

require_once '../includes/data/auth-functions.php';
require_once '../includes/data/db-config.php';
include '../includes/header.php';

$eventId = $_GET['id'] ?? '';
$event = null;
$detailImages = [];
$eventProducts = [];

if (empty($eventId)) {
    header('Location: /MVNO/event/event.php');
    exit;
}

$pdo = getDBConnection();
if ($pdo) {
    try {
        // 이벤트 기본 정보 조회
        $stmt = $pdo->prepare("
            SELECT * FROM events 
            WHERE id = :id AND is_published = 1
        ");
        $stmt->execute([':id' => $eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            header('Location: /MVNO/event/event.php');
            exit;
        }
        
        // 상세 이미지 조회
        $stmt = $pdo->prepare("
            SELECT * FROM event_detail_images 
            WHERE event_id = :event_id 
            ORDER BY display_order ASC
        ");
        $stmt->execute([':event_id' => $eventId]);
        $detailImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 연결된 상품 조회
        $stmt = $pdo->prepare("
            SELECT 
                ep.*,
                p.product_type,
                CASE 
                    WHEN p.product_type = 'mvno' THEN mvno.plan_name
                    WHEN p.product_type = 'mno' THEN mno.device_name
                    WHEN p.product_type = 'internet' THEN CONCAT(inet.registration_place, ' ', inet.speed_option)
                    ELSE '알 수 없음'
                END AS product_name,
                CASE 
                    WHEN p.product_type = 'mvno' THEN mvno.price_after
                    WHEN p.product_type = 'mno' THEN mno.price_main
                    WHEN p.product_type = 'internet' THEN inet.monthly_fee
                    ELSE 0
                END AS product_price
            FROM event_products ep
            INNER JOIN products p ON ep.product_id = p.id
            LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND p.product_type = 'mvno'
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
            LEFT JOIN product_internet_details inet ON p.id = inet.product_id AND p.product_type = 'internet'
            WHERE ep.event_id = :event_id AND p.status = 'active'
            ORDER BY ep.display_order ASC
        ");
        $stmt->execute([':event_id' => $eventId]);
        $eventProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log('Event detail error: ' . $e->getMessage());
    }
}

if (!$event) {
    header('Location: /MVNO/event/event.php');
    exit;
}

// 관리자 여부 확인 (드래그 기능 사용 가능 여부)
$isAdmin = false;
$currentUser = getCurrentUser();
if ($currentUser && function_exists('isAdmin')) {
    $isAdmin = isAdmin($currentUser['user_id']);
}
?>

<main class="main-content">
    <div class="event-detail-container">
        <!-- 이벤트 헤더 -->
        <div class="event-detail-header">
            <div class="event-breadcrumb">
                <a href="/MVNO/event/event.php">이벤트</a>
                <span class="separator">/</span>
                <span><?php echo htmlspecialchars($event['title']); ?></span>
            </div>
            
            <h1 class="event-detail-title"><?php echo htmlspecialchars($event['title']); ?></h1>
            
            <?php if ($event['start_at'] || $event['end_at']): ?>
                <div class="event-detail-date">
                    <?php if ($event['start_at']): ?>
                        <span>시작일: <?php echo date('Y-m-d', strtotime($event['start_at'])); ?></span>
                    <?php endif; ?>
                    <?php if ($event['end_at']): ?>
                        <span>종료일: <?php echo date('Y-m-d', strtotime($event['end_at'])); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 메인 이미지 -->
        <?php if ($event['main_image']): ?>
            <div class="event-main-image">
                <img src="<?php echo htmlspecialchars($event['main_image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
            </div>
        <?php endif; ?>
        
        <!-- 이벤트 설명 -->
        <?php if ($event['description']): ?>
            <div class="event-description">
                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            </div>
        <?php endif; ?>
        
        <!-- 상세 이미지 -->
        <?php if (!empty($detailImages)): ?>
            <div class="event-detail-images">
                <h2 class="section-title">상세 이미지</h2>
                <div class="detail-images-grid">
                    <?php foreach ($detailImages as $image): ?>
                        <div class="detail-image-item">
                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="상세 이미지">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- 연결된 상품 -->
        <?php if (!empty($eventProducts)): ?>
            <div class="event-products-section">
                <h2 class="section-title">이벤트 상품</h2>
                <div class="event-products-list <?php echo $isAdmin ? 'sortable-enabled' : ''; ?>" 
                     data-event-id="<?php echo htmlspecialchars($eventId); ?>">
                    <?php foreach ($eventProducts as $item): ?>
                        <div class="event-product-item" data-product-id="<?php echo $item['product_id']; ?>">
                            <?php if ($isAdmin): ?>
                                <span class="drag-handle">☰</span>
                            <?php endif; ?>
                            
                            <div class="product-info">
                                <h3 class="product-name">
                                    <a href="/MVNO/<?php 
                                        echo $item['product_type'] === 'mvno' ? 'mvno' : 
                                            ($item['product_type'] === 'mno' ? 'mno' : 'internets'); 
                                    ?>/<?php echo $item['product_type'] === 'mvno' ? 'mvno-phone-detail' : 
                                        ($item['product_type'] === 'mno' ? 'mno-phone-detail' : 'internet-detail'); 
                                    ?>.php?id=<?php echo $item['product_id']; ?>">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </a>
                                </h3>
                                <div class="product-type"><?php 
                                    echo $item['product_type'] === 'mvno' ? '알뜰폰' : 
                                        ($item['product_type'] === 'mno' ? '통신사폰' : '인터넷'); 
                                ?></div>
                                <?php if ($item['product_price']): ?>
                                    <div class="product-price">월 <?php echo number_format($item['product_price']); ?>원</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- 목록으로 버튼 -->
        <div class="event-detail-actions">
            <a href="/MVNO/event/event.php" class="btn-back">목록으로</a>
        </div>
    </div>
</main>

<style>
.event-detail-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.event-detail-header {
    margin-bottom: 2rem;
}

.event-breadcrumb {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 1rem;
}

.event-breadcrumb a {
    color: #6366f1;
    text-decoration: none;
}

.event-breadcrumb a:hover {
    text-decoration: underline;
}

.event-breadcrumb .separator {
    margin: 0 0.5rem;
}

.event-detail-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 1rem;
}

.event-detail-date {
    font-size: 0.875rem;
    color: #6b7280;
    display: flex;
    gap: 1rem;
}

.event-main-image {
    width: 100%;
    margin-bottom: 2rem;
    border-radius: 0.75rem;
    overflow: hidden;
    background: #f3f4f6;
}

.event-main-image img {
    width: 100%;
    height: auto;
    display: block;
    aspect-ratio: 16 / 9;
    object-fit: cover;
}

.event-description {
    margin-bottom: 3rem;
    padding: 1.5rem;
    background: #f9fafb;
    border-radius: 0.75rem;
    line-height: 1.8;
    color: #374151;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 1.5rem;
}

.event-detail-images {
    margin-bottom: 3rem;
}

.detail-images-grid {
    display: grid;
    gap: 1rem;
}

.detail-image-item {
    width: 100%;
    border-radius: 0.75rem;
    overflow: hidden;
    background: #f3f4f6;
}

.detail-image-item img {
    width: 100%;
    height: auto;
    display: block;
}

.event-products-section {
    margin-bottom: 3rem;
}

.event-products-list {
    display: grid;
    gap: 1rem;
}

.event-products-list.sortable-enabled .event-product-item {
    cursor: move;
}

.event-product-item {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    transition: all 0.2s;
}

.event-product-item:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border-color: #6366f1;
}

.event-product-item .drag-handle {
    margin-right: 1rem;
    color: #9ca3af;
    font-size: 1.25rem;
    cursor: move;
}

.event-product-item .product-info {
    flex: 1;
}

.event-product-item .product-name {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.event-product-item .product-name a {
    color: #1a1a1a;
    text-decoration: none;
}

.event-product-item .product-name a:hover {
    color: #6366f1;
    text-decoration: underline;
}

.event-product-item .product-type {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.event-product-item .product-price {
    font-size: 1rem;
    font-weight: 600;
    color: #6366f1;
}

.event-detail-actions {
    text-align: center;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
}

.btn-back {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: #6366f1;
    color: white;
    text-decoration: none;
    border-radius: 0.5rem;
    font-weight: 500;
    transition: background 0.2s;
}

.btn-back:hover {
    background: #4f46e5;
}

@media (max-width: 768px) {
    .event-detail-container {
        padding: 1rem 0.75rem;
    }
    
    .event-detail-title {
        font-size: 1.5rem;
    }
    
    .event-detail-date {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<?php if ($isAdmin): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const productsList = document.querySelector('.event-products-list.sortable-enabled');
    if (!productsList) return;
    
    const eventId = productsList.dataset.eventId;
    
    const sortable = new Sortable(productsList, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function(evt) {
            const productItems = productsList.querySelectorAll('.event-product-item');
            const productIds = Array.from(productItems).map(item => item.dataset.productId);
            
            // 순서 업데이트 API 호출
            fetch('/MVNO/api/update-event-product-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    event_id: eventId,
                    product_ids: JSON.stringify(productIds)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 성공 메시지 표시 (선택사항)
                    console.log('순서가 업데이트되었습니다.');
                } else {
                    console.error('순서 업데이트 실패:', data.message);
                    alert('순서 업데이트에 실패했습니다.');
                    location.reload(); // 실패 시 페이지 새로고침
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('순서 업데이트 중 오류가 발생했습니다.');
                location.reload();
            });
        }
    });
});
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

