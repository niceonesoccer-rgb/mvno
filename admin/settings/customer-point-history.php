<?php
/**
 * 고객 포인트 적립 내역 관리자 페이지
 * 관리자가 모든 고객의 포인트 적립/사용 내역을 조회할 수 있는 페이지
 */

require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

// 관리자 권한 체크
if (!isAdmin()) {
    header('Location: /MVNO/admin/');
    exit;
}

$pdo = getDBConnection();
$error = '';
$success = '';

// 페이지네이션 설정
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// 검색 필터
$search_user_id = isset($_GET['search_user_id']) ? trim($_GET['search_user_id']) : '';
$search_type = isset($_GET['search_type']) ? trim($_GET['search_type']) : '';

// 포인트 내역 조회
$point_history = [];
$total_count = 0;

try {
    // WHERE 조건 구성
    $where_conditions = [];
    $params = [];
    
    if ($search_user_id) {
        $where_conditions[] = "l.user_id LIKE :search_user_id";
        $params[':search_user_id'] = '%' . $search_user_id . '%';
    }
    
    if ($search_type) {
        $where_conditions[] = "l.type = :search_type";
        $params[':search_type'] = $search_type;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // 전체 개수 조회
    $count_sql = "
        SELECT COUNT(*) 
        FROM user_point_ledger l
        LEFT JOIN users u ON u.user_id = l.user_id
        {$where_clause}
    ";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_count = (int)$count_stmt->fetchColumn();
    
    // 포인트 내역 조회
    $sql = "
        SELECT 
            l.id,
            l.user_id,
            u.name as user_name,
            u.email,
            l.delta,
            l.type,
            l.item_id,
            l.description,
            l.balance_after,
            l.created_at
        FROM user_point_ledger l
        LEFT JOIN users u ON u.user_id = l.user_id
        {$where_clause}
        ORDER BY l.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $point_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('포인트 내역 조회 오류: ' . $e->getMessage());
    $error = '포인트 내역을 불러오는 중 오류가 발생했습니다.';
}

// 총 페이지 수
$total_pages = $total_count > 0 ? ceil($total_count / $perPage) : 1;

// 타입 라벨 매핑
$type_labels = [
    'mvno' => '알뜰폰 신청',
    'mno' => '통신사폰 신청',
    'internet' => '인터넷 신청',
    'add' => '포인트 충전',
    'view_product' => '상품 조회 포인트'
];

// 현재 페이지 설정
$currentPage = 'customer-point-history.php';

// 헤더 포함
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="page-header" style="margin-bottom: 32px;">
        <h1 style="font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">고객 적립포인트 내역</h1>
        <p style="font-size: 16px; color: #6b7280;">모든 고객의 포인트 적립 및 사용 내역을 조회할 수 있습니다.</p>
    </div>
    
    <?php if ($error): ?>
        <div style="padding: 16px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 24px; border: 1px solid #ef4444;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div style="padding: 16px; background: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 24px; border: 1px solid #10b981;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <!-- 검색 필터 -->
    <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; margin-bottom: 24px;">
        <form method="GET" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label for="search_user_id" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    사용자 ID 검색
                </label>
                <input 
                    type="text" 
                    id="search_user_id" 
                    name="search_user_id" 
                    value="<?= htmlspecialchars($search_user_id) ?>"
                    placeholder="사용자 ID 입력"
                    style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
                >
            </div>
            <div style="min-width: 180px;">
                <label for="search_type" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    타입 필터
                </label>
                <select 
                    id="search_type" 
                    name="search_type" 
                    style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
                >
                    <option value="">전체</option>
                    <?php foreach ($type_labels as $type_key => $type_label): ?>
                        <option value="<?= htmlspecialchars($type_key) ?>" <?= $search_type === $type_key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type_label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" style="padding: 10px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; white-space: nowrap;">
                    검색
                </button>
                <?php if ($search_user_id || $search_type): ?>
                    <a href="?" style="padding: 10px 24px; background: #9ca3af; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; margin-left: 8px;">
                        초기화
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- 포인트 내역 테이블 -->
    <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="font-size: 20px; font-weight: 700; color: #1f2937; margin: 0;">
                포인트 내역 (총 <?= number_format($total_count) ?>건)
            </h2>
        </div>
        
        <?php if (empty($point_history)): ?>
            <div style="text-align: center; padding: 60px 20px; color: #9ca3af;">
                <p style="font-size: 16px; margin: 0;">포인트 내역이 없습니다.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: #374151;">일시</th>
                            <th style="padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: #374151;">사용자 ID</th>
                            <th style="padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: #374151;">이름</th>
                            <th style="padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: #374151;">타입</th>
                            <th style="padding: 12px; text-align: right; font-size: 13px; font-weight: 600; color: #374151;">금액</th>
                            <th style="padding: 12px; text-align: right; font-size: 13px; font-weight: 600; color: #374151;">잔액</th>
                            <th style="padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: #374151;">설명</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($point_history as $item): 
                            $is_deduction = !in_array($item['type'], ['add', 'view_product']);
                            $type_label = $type_labels[$item['type']] ?? $item['type'];
                            $amount = abs((int)$item['delta']);
                        ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 12px; font-size: 14px; color: #374151;">
                                    <?= htmlspecialchars($item['created_at']) ?>
                                </td>
                                <td style="padding: 12px; font-size: 14px; color: #374151;">
                                    <?= htmlspecialchars($item['user_id']) ?>
                                </td>
                                <td style="padding: 12px; font-size: 14px; color: #374151;">
                                    <?= htmlspecialchars($item['user_name'] ?? '-') ?>
                                </td>
                                <td style="padding: 12px; font-size: 14px; color: #374151;">
                                    <?= htmlspecialchars($type_label) ?>
                                </td>
                                <td style="padding: 12px; font-size: 14px; text-align: right; font-weight: 600; color: <?= $is_deduction ? '#ef4444' : '#3b82f6' ?>;">
                                    <?= $is_deduction ? '-' : '+' ?><?= number_format($amount) ?>원
                                </td>
                                <td style="padding: 12px; font-size: 14px; text-align: right; color: #6b7280;">
                                    <?= number_format((int)$item['balance_after']) ?>원
                                </td>
                                <td style="padding: 12px; font-size: 14px; color: #6b7280;">
                                    <?= htmlspecialchars($item['description'] ?? '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
                    <?php
                    $query_params = $_GET;
                    unset($query_params['page']);
                    $query_string = http_build_query($query_params);
                    $base_url = '?' . ($query_string ? $query_string . '&' : '');
                    
                    // 이전 페이지
                    if ($page > 1):
                        $prev_page = $page - 1;
                    ?>
                        <a href="<?= $base_url ?>page=<?= $prev_page ?>" style="padding: 8px 16px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500;">
                            이전
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    // 페이지 번호 표시
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="<?= $base_url ?>page=<?= $i ?>" style="padding: 8px 12px; background: <?= $i === $page ? '#6366f1' : 'white' ?>; color: <?= $i === $page ? 'white' : '#374151' ?>; border: 1px solid #d1d5db; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: <?= $i === $page ? '600' : '500' ?>;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php
                    // 다음 페이지
                    if ($page < $total_pages):
                        $next_page = $page + 1;
                    ?>
                        <a href="<?= $base_url ?>page=<?= $next_page ?>" style="padding: 8px 16px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500;">
                            다음
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// 푸터 포함
include __DIR__ . '/../includes/admin-footer.php';
?>
