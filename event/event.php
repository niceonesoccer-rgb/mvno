<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'event';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

require_once '../includes/data/db-config.php';

// 이미지 경로 정규화 함수
function normalizeImagePathForDisplay($path) {
    if (empty($path)) {
        return '';
    }
    
    $imagePath = trim($path);
    
    // 이미 /MVNO/로 시작하면 그대로 사용
    if (strpos($imagePath, '/MVNO/') === 0) {
        return $imagePath;
    }
    // /uploads/events/ 또는 /uploads/events/로 시작하는 경우
    elseif (preg_match('#^/uploads/events/#', $imagePath)) {
        return '/MVNO' . $imagePath;
    }
    // /uploads/ 또는 /images/로 시작하면 /MVNO/ 추가
    elseif (strpos($imagePath, '/uploads/') === 0 || strpos($imagePath, '/images/') === 0) {
        return '/MVNO' . $imagePath;
    }
    // 파일명만 있는 경우 (확장자가 있고 슬래시가 없음)
    elseif (strpos($imagePath, '/') === false && preg_match('/\.(webp|jpg|jpeg|png|gif)$/i', $imagePath)) {
        return '/MVNO/uploads/events/' . $imagePath;
    }
    // 상대 경로인데 파일명이 아닌 경우
    elseif (strpos($imagePath, '/') !== 0) {
        return '/MVNO/' . $imagePath;
    }
    
    return $imagePath;
}

// 이벤트 타입 필터 (전체, 요금제, 프로모션, 제휴카드)
$eventTypeFilter = $_GET['type'] ?? 'all';
$validTypes = ['all', 'plan', 'promotion', 'card'];
if (!in_array($eventTypeFilter, $validTypes)) {
    $eventTypeFilter = 'all';
}

// 데이터베이스에서 이벤트 가져오기
$events = [];
$pdo = getDBConnection();
if ($pdo) {
    try {
        $now = date('Y-m-d');
        
        // WHERE 조건 구성
        $whereConditions = [];
        $params = [];
        
        // is_published 컬럼 존재 여부 확인
        $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE 'is_published'");
        $hasIsPublished = $stmt->rowCount() > 0;
        
        // is_published가 0이면 기간과 상관없이 비공개
        if ($hasIsPublished) {
            $whereConditions[] = "(is_published IS NULL OR is_published != 0)";
        }
        
        // 공개 기간 확인 (조건 완화)
        // start_at이 NULL이거나 현재 날짜 이전/같으면 시작됨
        // end_at이 NULL이거나 현재 날짜 이후/같으면 아직 종료 안됨
        // is_published가 0이 아닌 경우에만 기간 체크
        $whereConditions[] = "(
            (start_at IS NULL OR start_at <= :now_datetime) 
            AND (end_at IS NULL OR end_at >= :now_date)
        )";
        $params[':now_datetime'] = date('Y-m-d H:i:s');
        $params[':now_date'] = $now;
        
        // 이벤트 타입 필터
        if ($eventTypeFilter !== 'all') {
            // 타입 매핑: plan -> plan, promotion -> promotion, card -> card
            $typeMap = [
                'plan' => 'plan',
                'promotion' => 'promotion',
                'card' => 'card'
            ];
            if (isset($typeMap[$eventTypeFilter])) {
                $whereConditions[] = "event_type = :event_type";
                $params[':event_type'] = $typeMap[$eventTypeFilter];
            }
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // main_image 또는 image_url 컬럼 확인
        $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE 'main_image'");
        $hasMainImage = $stmt->rowCount() > 0;
        $imageColumn = $hasMainImage ? 'main_image' : 'image_url';
        
        $sql = "
            SELECT 
                id,
                title,
                event_type,
                category,
                {$imageColumn} as main_image,
                start_at,
                end_at,
                created_at
            FROM events
            WHERE {$whereClause}
            ORDER BY created_at DESC
        ";
        
        // 디버깅 로그
        error_log('Event list SQL: ' . $sql);
        error_log('Event list Params: ' . json_encode($params, JSON_UNESCAPED_UNICODE));
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 디버깅: 결과 개수 로그
        error_log('Event list result count: ' . count($events));
        
        // 만약 결과가 없으면 조건 없이 모든 이벤트 확인
        if (empty($events)) {
            $stmtAll = $pdo->query("SELECT COUNT(*) as cnt FROM events");
            $allCount = $stmtAll->fetch(PDO::FETCH_ASSOC)['cnt'];
            error_log('Total events in DB: ' . $allCount);
            
            // 조건 없이 샘플 데이터 확인
            $stmtSample = $pdo->query("SELECT id, title, event_type, start_at, end_at, created_at FROM events ORDER BY created_at DESC LIMIT 5");
            $samples = $stmtSample->fetchAll(PDO::FETCH_ASSOC);
            error_log('Sample events: ' . json_encode($samples, JSON_UNESCAPED_UNICODE));
        }
        
    } catch (PDOException $e) {
        error_log('Event list error: ' . $e->getMessage());
        error_log('Event list SQL: ' . ($sql ?? 'N/A'));
        $events = [];
    }
}

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div class="event-container">
        <h2 class="event-main-title">진행 중 이벤트</h2>
        
        <!-- 탭 메뉴 및 전체 이벤트 -->
        <section class="event-section event-tab-section">
            <div class="c-tabmenu-wrap">
                <div class="c-tabmenu-link-wrap">
                    <div class="c-tabmenu-list">
                        <ul class="tab-list">
                            <li class="tab-item <?php echo $eventTypeFilter === 'all' ? 'is-active' : ''; ?>">
                                <button role="tab" aria-selected="<?php echo $eventTypeFilter === 'all' ? 'true' : 'false'; ?>" tabindex="<?php echo $eventTypeFilter === 'all' ? '0' : '-1'; ?>" class="tab-button" data-type="all">전체</button>
                            </li>
                            <li class="tab-item <?php echo $eventTypeFilter === 'plan' ? 'is-active' : ''; ?>">
                                <button role="tab" aria-selected="<?php echo $eventTypeFilter === 'plan' ? 'true' : 'false'; ?>" tabindex="<?php echo $eventTypeFilter === 'plan' ? '0' : '-1'; ?>" class="tab-button" data-type="plan">요금제</button>
                            </li>
                            <li class="tab-item <?php echo $eventTypeFilter === 'promotion' ? 'is-active' : ''; ?>">
                                <button role="tab" aria-selected="<?php echo $eventTypeFilter === 'promotion' ? 'true' : 'false'; ?>" tabindex="<?php echo $eventTypeFilter === 'promotion' ? '0' : '-1'; ?>" class="tab-button" data-type="promotion">프로모션</button>
                            </li>
                            <li class="tab-item <?php echo $eventTypeFilter === 'card' ? 'is-active' : ''; ?>">
                                <button role="tab" aria-selected="<?php echo $eventTypeFilter === 'card' ? 'true' : 'false'; ?>" tabindex="<?php echo $eventTypeFilter === 'card' ? '0' : '-1'; ?>" class="tab-button" data-type="card">제휴카드</button>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="c-tabcontent-box">
                    <?php if (empty($events)): ?>
                        <div class="empty-events">
                            <p>등록된 이벤트가 없습니다.</p>
                        </div>
                    <?php else: ?>
                        <ul class="event-grid all-events">
                            <?php 
                            $now = time();
                            foreach ($events as $event): 
                                // 이벤트 상세 페이지 링크
                                $eventUrl = '/MVNO/event/event-detail.php?id=' . htmlspecialchars($event['id'], ENT_QUOTES, 'UTF-8');
                                
                                // 이미지 경로 처리
                                $imageUrl = '/MVNO/assets/images/no-image.png';
                                if (!empty($event['main_image'])) {
                                    $imagePath = normalizeImagePathForDisplay($event['main_image']);
                                    if (!empty($imagePath)) {
                                        $imageUrl = htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8');
                                    }
                                }
                                
                                // 이벤트 제목
                                $title = htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8');
                                
                                // 날짜 포맷팅
                                $startDate = $event['start_at'] ? date('Y-m-d', strtotime($event['start_at'])) : '';
                                $endDate = $event['end_at'] ? date('Y-m-d', strtotime($event['end_at'])) : '';
                                $dateRange = $startDate && $endDate ? $startDate . ' ~ ' . $endDate : ($startDate ? $startDate . ' ~' : '');
                                
                                // 배지 표시 로직
                                $badgeHtml = '';
                                $createdAt = strtotime($event['created_at']);
                                $daysSinceCreated = ($now - $createdAt) / (60 * 60 * 24);
                                
                                // 최신 이벤트 (7일 이내)
                                if ($daysSinceCreated <= 7) {
                                    $badgeHtml = '<small class="c-flag new-flag">최신</small>';
                                }
                                
                                // 마감 임박 (종료일이 7일 이내)
                                if ($event['end_at']) {
                                    $endAt = strtotime($event['end_at']);
                                    $daysUntilEnd = ($endAt - $now) / (60 * 60 * 24);
                                    if ($daysUntilEnd > 0 && $daysUntilEnd <= 7) {
                                        $badgeHtml = '<small class="c-flag dDay-flag">마감임박</small>';
                                    }
                                }
                            ?>
                            <li class="event-card">
                                <a href="<?php echo $eventUrl; ?>" class="event-link">
                                    <?php if ($badgeHtml): ?>
                                    <span class="option-flag">
                                        <?php echo $badgeHtml; ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="option-flag"></span>
                                    <?php endif; ?>
                                    <div class="img-area gradient">
                                        <img src="<?php echo $imageUrl; ?>" alt="<?php echo $title; ?>" loading="lazy" class="event-image">
                                    </div>
                                    <div class="text-area">
                                        <p class="tit"><?php echo $title; ?></p>
                                        <?php if ($dateRange): ?>
                                        <p class="date"><?php echo htmlspecialchars($dateRange, ENT_QUOTES, 'UTF-8'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</main>

<style>
/* 이벤트 페이지 컨테이너 */
.event-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

/* 메인 제목 */
.event-main-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 2rem;
    text-align: center;
}

/* 섹션 제목 */
.event-section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 1.5rem;
}

.event-section {
    margin-bottom: 3rem;
}

.event-tab-section {
    padding-top: 0;
}

/* 이벤트 그리드 */
.event-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
}

/* 이벤트 카드 */
.event-card {
    position: relative;
    background: #ffffff;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.event-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
}

.event-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

/* 옵션 플래그 */
.option-flag {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    z-index: 10;
}

.c-flag {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1.2;
}

.dDay-flag {
    background-color: #ef4444;
    color: #ffffff;
}

.new-flag {
    background-color: #6366f1;
    color: #ffffff;
}

/* 이미지 영역 */
.img-area {
    position: relative;
    width: 100%;
    padding-top: 56.25%; /* 16:9 비율 */
    overflow: hidden;
    background: #f3f4f6;
}

.img-area.gradient::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 40%;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.3), transparent);
    pointer-events: none;
}

.event-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.event-card:hover .event-image {
    transform: scale(1.05);
}

/* 텍스트 영역 */
.text-area {
    padding: 1rem;
}

.flag-area {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.blue-flag {
    background-color: #dbeafe;
    color: #1e40af;
}

.gray-flag {
    background-color: #f3f4f6;
    color: #6b7280;
}

.tit {
    font-size: 1rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0.5rem 0;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.flag-n-tag {
    margin: 0.5rem 0;
}

.flag-n-tag em {
    font-size: 0.875rem;
    color: #6b7280;
    font-style: normal;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.date {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0.5rem 0 0 0;
}

/* 빈 이벤트 메시지 */
.empty-events {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.empty-events p {
    font-size: 1.125rem;
    margin: 0;
}

/* 탭 메뉴 */
.c-tabmenu-wrap {
    margin-bottom: 2rem;
}

.c-tabmenu-link-wrap {
    margin-bottom: 2rem;
}

.tab-list {
    display: flex;
    gap: 0.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
    flex-wrap: wrap;
}

.tab-item {
    margin: 0;
}

.tab-button {
    padding: 0.5rem 1.25rem;
    background: transparent;
    border: 1px solid #e5e7eb;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    outline: none;
}

.tab-button:focus {
    outline: none;
    box-shadow: none;
}

.tab-button:active {
    outline: none;
    box-shadow: none;
}

.tab-button:hover {
    background-color: #f3f4f6;
    color: #374151;
}

.tab-item.is-active .tab-button {
    color: #ec4899;
    font-weight: 600;
    border: 1px solid #ec4899;
}

/* 모바일 반응형 */
@media (max-width: 767px) {
    .event-container {
        padding: 1rem 0.75rem;
    }
    
    .event-main-title {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .event-section-title {
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }
    
    .event-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .tab-list {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    
    .tab-list::-webkit-scrollbar {
        display: none;
    }
    
    .tab-item {
        flex-shrink: 0;
    }
    
    .tab-button {
        padding: 0.625rem 1rem;
        font-size: 0.8125rem;
    }
    
    .text-area {
        padding: 0.875rem;
    }
    
    .tit {
        font-size: 0.9375rem;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    .event-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .event-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabItems = document.querySelectorAll('.tab-item');
    
    tabButtons.forEach((button, index) => {
        button.addEventListener('click', function() {
            const eventType = this.getAttribute('data-type');
            
            // URL 파라미터 업데이트
            const url = new URL(window.location.href);
            if (eventType === 'all') {
                url.searchParams.delete('type');
            } else {
                url.searchParams.set('type', eventType);
            }
            
            // 페이지 리로드
            window.location.href = url.toString();
        });
    });
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

