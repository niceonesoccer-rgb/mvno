<?php
/**
 * 고객 포인트 적립 내역 관리자 페이지
 * 관리자가 모든 고객의 포인트 적립/사용 내역을 조회할 수 있는 페이지
 */

require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/path-config.php';

// 관리자 권한 체크
if (!isAdmin()) {
    header('Location: ' . getAssetPath('/admin/login.php'));
    exit;
}

$pdo = getDBConnection();
$error = '';
$success = '';

// 타입 라벨 매핑
$type_labels = [
    'mvno' => '알뜰폰 신청',
    'mno' => '통신사폰 신청',
    'mno_sim' => '통신사단독유심 신청',
    'internet' => '인터넷 신청',
    'add' => '회원가입 포인트',
    'view_product' => '상품 조회 포인트'
];

// description 기반 타입 라벨 결정 함수
function getPointTypeLabel($type, $description) {
    global $type_labels;
    
    // view_product 타입이고 description에 신청 포인트가 포함된 경우
    if ($type === 'view_product' && !empty($description)) {
        if (strpos($description, '통신사폰 신청 포인트') !== false) {
            return '통신사폰 신청';
        } elseif (strpos($description, '알뜰폰 신청 포인트') !== false) {
            return '알뜰폰 신청';
        } elseif (strpos($description, '통신사단독유심 신청 포인트') !== false) {
            return '통신사단독유심 신청';
        } elseif (strpos($description, '인터넷 신청 포인트') !== false) {
            return '인터넷 신청';
        }
    }
    
    return $type_labels[$type] ?? $type;
}

// CSV 다운로드 처리
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    // 검색 필터
    $search_user_id = isset($_GET['search_user_id']) ? trim($_GET['search_user_id']) : '';
    $search_name = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
    $search_type = isset($_GET['search_type']) ? trim($_GET['search_type']) : '';
    
    // WHERE 조건 구성
    $where_conditions = [];
    $params = [];
    
    if ($search_user_id) {
        $where_conditions[] = "l.user_id LIKE :search_user_id";
        $params[':search_user_id'] = '%' . $search_user_id . '%';
    }
    
    if ($search_name) {
        $where_conditions[] = "u.name LIKE :search_name";
        $params[':search_name'] = '%' . $search_name . '%';
    }
    
    if ($search_type) {
        // 특수 타입 처리: 신청 포인트 타입들
        if (in_array($search_type, ['mno_application', 'mvno_application', 'mno_sim_application', 'internet_application'])) {
            // view_product 타입이면서 description에 해당 신청 포인트가 포함된 경우
            $where_conditions[] = "l.type = 'view_product'";
            $description_map = [
                'mno_application' => '통신사폰 신청 포인트',
                'mvno_application' => '알뜰폰 신청 포인트',
                'mno_sim_application' => '통신사단독유심 신청 포인트',
                'internet_application' => '인터넷 신청 포인트'
            ];
            $where_conditions[] = "l.description LIKE :search_description";
            $params[':search_description'] = '%' . $description_map[$search_type] . '%';
        } else {
            // 일반 타입 검색
            $where_conditions[] = "l.type = :search_type";
            $params[':search_type'] = $search_type;
        }
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // 전체 데이터 조회 (페이지네이션 없이)
    $sql = "
        SELECT 
            l.id,
            l.user_id,
            u.name as user_name,
            u.email,
            l.delta,
            l.type,
            l.description,
            l.balance_after,
            l.created_at
        FROM user_point_ledger l
        LEFT JOIN users u ON u.user_id = l.user_id
        {$where_clause}
        ORDER BY l.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $all_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 전체 개수 조회 (순서 번호 역순을 위해)
    $count_sql = "
        SELECT COUNT(*) 
        FROM user_point_ledger l
        LEFT JOIN users u ON u.user_id = l.user_id
        {$where_clause}
    ";
    $count_stmt = $pdo->prepare($count_sql);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_count_csv = (int)$count_stmt->fetchColumn();
    
    // CSV 형식으로 다운로드
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="point_history_' . date('YmdHis') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // BOM 추가 (엑셀에서 한글 깨짐 방지)
    echo "\xEF\xBB\xBF";
    
    // 헤더
    echo "순번,일시,사용자 ID,이름,이메일,카테고리명,금액,잔액,설명\n";
    
    // 데이터 (순서 번호 역순: 최근 등록된 것이 가장 큰 숫자)
    $row_number = $total_count_csv;
    foreach ($all_data as $item) {
        $is_deduction = !in_array($item['type'], ['add', 'view_product']);
        $type_label = getPointTypeLabel($item['type'], $item['description'] ?? '');
        $amount = abs((int)$item['delta']);
        $amount_str = ($is_deduction ? '-' : '+') . number_format($amount);
        
        echo $row_number-- . ',';
        echo '"' . str_replace('"', '""', $item['created_at']) . '",';
        echo '"' . str_replace('"', '""', $item['user_id']) . '",';
        echo '"' . str_replace('"', '""', $item['user_name'] ?? '') . '",';
        echo '"' . str_replace('"', '""', $item['email'] ?? '') . '",';
        echo '"' . str_replace('"', '""', $type_label) . '",';
        echo '"' . str_replace('"', '""', $amount_str) . '",';
        echo '"' . str_replace('"', '""', number_format((int)$item['balance_after'])) . '",';
        echo '"' . str_replace('"', '""', $item['description'] ?? '') . '"';
        echo "\n";
    }
    
    exit;
}

// 페이지네이션 설정
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPageOptions = [10, 50, 100, 500];
$perPage = isset($_GET['per_page']) && in_array(intval($_GET['per_page']), $perPageOptions) 
    ? intval($_GET['per_page']) 
    : 50;
$offset = ($page - 1) * $perPage;

// 검색 필터
$search_user_id = isset($_GET['search_user_id']) ? trim($_GET['search_user_id']) : '';
$search_name = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
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
    
    if ($search_name) {
        $where_conditions[] = "u.name LIKE :search_name";
        $params[':search_name'] = '%' . $search_name . '%';
    }
    
    if ($search_type) {
        // 특수 타입 처리: 신청 포인트 타입들
        if (in_array($search_type, ['mno_application', 'mvno_application', 'mno_sim_application', 'internet_application'])) {
            // view_product 타입이면서 description에 해당 신청 포인트가 포함된 경우
            $where_conditions[] = "l.type = 'view_product'";
            $description_map = [
                'mno_application' => '통신사폰 신청 포인트',
                'mvno_application' => '알뜰폰 신청 포인트',
                'mno_sim_application' => '통신사단독유심 신청 포인트',
                'internet_application' => '인터넷 신청 포인트'
            ];
            $where_conditions[] = "l.description LIKE :search_description";
            $params[':search_description'] = '%' . $description_map[$search_type] . '%';
        } else {
            // 일반 타입 검색
            $where_conditions[] = "l.type = :search_type";
            $params[':search_type'] = $search_type;
        }
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
    
    // 통계 정보 계산
    $stats_sql = "
        SELECT 
            SUM(CASE WHEN l.delta > 0 THEN l.delta ELSE 0 END) as total_earned,
            SUM(CASE WHEN l.delta < 0 THEN ABS(l.delta) ELSE 0 END) as total_used,
            COUNT(DISTINCT l.user_id) as unique_users
        FROM user_point_ledger l
        LEFT JOIN users u ON u.user_id = l.user_id
        {$where_clause}
    ";
    $stats_stmt = $pdo->prepare($stats_sql);
    foreach ($params as $key => $value) {
        $stats_stmt->bindValue($key, $value);
    }
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_earned = (int)($stats['total_earned'] ?? 0);
    $total_used = (int)($stats['total_used'] ?? 0);
    $unique_users = (int)($stats['unique_users'] ?? 0);
    
} catch (PDOException $e) {
    error_log('포인트 내역 조회 오류: ' . $e->getMessage());
    $error = '포인트 내역을 불러오는 중 오류가 발생했습니다.';
    $total_earned = 0;
    $total_used = 0;
    $unique_users = 0;
}

// 총 페이지 수
$total_pages = $total_count > 0 ? ceil($total_count / $perPage) : 1;

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
    
    <!-- 통계 정보 -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;">
            <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;">총 적립 포인트</div>
            <div style="font-size: 24px; font-weight: 700; color: #3b82f6;">
                <?= number_format($total_earned) ?>원
            </div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;">
            <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;">총 사용 포인트</div>
            <div style="font-size: 24px; font-weight: 700; color: #ef4444;">
                <?= number_format($total_used) ?>원
            </div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;">
            <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;">고유 사용자 수</div>
            <div style="font-size: 24px; font-weight: 700; color: #10b981;">
                <?= number_format($unique_users) ?>명
            </div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;">
            <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;">순 적립 포인트</div>
            <div style="font-size: 24px; font-weight: 700; color: #6366f1;">
                <?= number_format($total_earned - $total_used) ?>원
            </div>
        </div>
    </div>
    
    <!-- 검색 필터 -->
    <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; margin-bottom: 24px;">
        <form method="GET" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px;">
                <label for="search_user_id" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    사용자 ID
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
            <div style="flex: 1; min-width: 180px;">
                <label for="search_name" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    이름
                </label>
                <input 
                    type="text" 
                    id="search_name" 
                    name="search_name" 
                    value="<?= htmlspecialchars($search_name) ?>"
                    placeholder="이름 입력"
                    style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
                >
            </div>
            <div style="min-width: 180px;">
                <label for="search_type" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    카테고리
                </label>
                <select 
                    id="search_type" 
                    name="search_type" 
                    style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
                >
                    <option value="">전체</option>
                    <optgroup label="신청 포인트">
                        <option value="mno_application" <?= $search_type === 'mno_application' ? 'selected' : '' ?>>통신사폰 신청</option>
                        <option value="mvno_application" <?= $search_type === 'mvno_application' ? 'selected' : '' ?>>알뜰폰 신청</option>
                        <option value="mno_sim_application" <?= $search_type === 'mno_sim_application' ? 'selected' : '' ?>>통신사단독유심 신청</option>
                        <option value="internet_application" <?= $search_type === 'internet_application' ? 'selected' : '' ?>>인터넷 신청</option>
                    </optgroup>
                </select>
            </div>
            <div>
                <button type="submit" style="padding: 10px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; white-space: nowrap;">
                    검색
                </button>
                <?php if ($search_user_id || $search_name || $search_type): ?>
                    <a href="?" style="padding: 10px 24px; background: #9ca3af; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; margin-left: 8px;">
                        초기화
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- 포인트 내역 테이블 -->
    <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
            <h2 style="font-size: 20px; font-weight: 700; color: #1f2937; margin: 0;">
                포인트 내역 (총 <?= number_format($total_count) ?>건)
            </h2>
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <a 
                    href="?<?= http_build_query(array_merge($_GET, ['action' => 'export_csv'])) ?>" 
                    style="padding: 8px 16px; background: #10b981; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;"
                    onmouseover="this.style.background='#059669'" 
                    onmouseout="this.style.background='#10b981'"
                >
                    📥 CSV 다운로드
                </a>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="per_page" style="font-size: 14px; color: #374151; font-weight: 500;">보기:</label>
                    <select 
                        id="per_page" 
                        name="per_page"
                        onchange="changePerPage(this.value)"
                        style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; cursor: pointer;"
                    >
                        <?php foreach ($perPageOptions as $option): ?>
                            <option value="<?= $option ?>" <?= $perPage == $option ? 'selected' : '' ?>>
                                <?= $option ?>개
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
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
                            <th style="padding: 12px; text-align: center; font-size: 13px; font-weight: 600; color: #374151; width: 60px;">순서</th>
                            <th style="padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: #374151;">일시</th>
                            <th style="padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: #374151;">사용자 ID</th>
                            <th style="padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: #374151;">이름</th>
                            <th style="padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: #374151;">카테고리명</th>
                            <th style="padding: 12px; text-align: right; font-size: 13px; font-weight: 600; color: #374151;">금액</th>
                            <th style="padding: 12px; text-align: right; font-size: 13px; font-weight: 600; color: #374151;">잔액</th>
                            <th style="padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: #374151;">설명</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // 순서 번호 역순: 최근 등록된 것이 가장 큰 숫자
                        $row_number = $total_count - $offset;
                        foreach ($point_history as $item): 
                            $is_deduction = !in_array($item['type'], ['add', 'view_product']);
                            $type_label = getPointTypeLabel($item['type'], $item['description'] ?? '');
                            $amount = abs((int)$item['delta']);
                        ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 12px; font-size: 14px; color: #6b7280; text-align: center;">
                                    <?= number_format($row_number--) ?>
                                </td>
                                <td style="padding: 12px; font-size: 14px; color: #374151;">
                                    <?= htmlspecialchars($item['created_at']) ?>
                                </td>
                                <td style="padding: 12px; font-size: 14px;">
                                    <a 
                                        href="javascript:void(0)" 
                                        onclick="showUserModal('<?= htmlspecialchars($item['user_id'], ENT_QUOTES) ?>')"
                                        style="color: #6366f1; text-decoration: none; font-weight: 500; cursor: pointer;"
                                        onmouseover="this.style.textDecoration='underline'"
                                        onmouseout="this.style.textDecoration='none'"
                                    >
                                        <?= htmlspecialchars($item['user_id']) ?>
                                    </a>
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
                    if (empty($query_string)) {
                        $base_url = '?';
                    }
                    
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

<!-- 사용자 정보 모달 -->
<div id="userModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 0; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 20px; font-weight: 700; color: #1f2937; margin: 0;">회원 정보</h2>
            <button onclick="closeUserModal()" style="background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                ×
            </button>
        </div>
        <div id="userModalContent" style="padding: 24px;">
            <div style="text-align: center; padding: 40px;">
                <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #e5e7eb; border-top-color: #6366f1; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <p style="margin-top: 16px; color: #6b7280;">로딩 중...</p>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
.detail-row {
    display: flex; 
    padding: 12px 0; 
    border-bottom: 1px solid #f3f4f6;
}
.detail-row:last-child {
    border-bottom: none;
}
.detail-label {
    width: 140px; 
    font-weight: 600; 
    color: #374151; 
    font-size: 14px;
}
.detail-value {
    flex: 1; 
    color: #6b7280; 
    font-size: 14px;
}
</style>

<script>
function changePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function showUserModal(userId) {
    const modal = document.getElementById('userModal');
    const content = document.getElementById('userModalContent');
    
    // 로딩 표시
    content.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #e5e7eb; border-top-color: #6366f1; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 16px; color: #6b7280;">로딩 중...</p>
        </div>
    `;
    
    modal.style.display = 'flex';
    
    // 사용자 정보 가져오기
    fetch(`<?php echo getApiPath('/api/admin/get-user-info.php'); ?>?user_id=${encodeURIComponent(userId)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', text);
                throw new Error('Invalid JSON response');
            }
        })
        .then(data => {
            if (data.success && data.user) {
                const user = data.user;
                let html = '<div class="detail-info">';
                
                // 기본 정보
                html += '<div class="detail-row"><div class="detail-label">아이디</div><div class="detail-value">' + escapeHtml(user.user_id || '-') + '</div></div>';
                html += '<div class="detail-row"><div class="detail-label">이름</div><div class="detail-value">' + escapeHtml(user.name || '-') + '</div></div>';
                html += '<div class="detail-row"><div class="detail-label">이메일</div><div class="detail-value">' + escapeHtml(user.email || '-') + '</div></div>';
                
                if (user.phone) {
                    html += '<div class="detail-row"><div class="detail-label">전화번호</div><div class="detail-value">' + escapeHtml(user.phone) + '</div></div>';
                }
                
                // 역할
                const roleNames = {
                    'user': '일반 회원',
                    'seller': '판매자',
                    'admin': '관리자',
                    'sub_admin': '부관리자'
                };
                if (user.role) {
                    html += '<div class="detail-row"><div class="detail-label">역할</div><div class="detail-value">' + escapeHtml(roleNames[user.role] || user.role) + '</div></div>';
                }
                
                // 주소 정보
                if (user.address) {
                    html += '<div class="detail-row"><div class="detail-label">주소</div><div class="detail-value">' + escapeHtml(user.address);
                    if (user.address_detail) {
                        html += ' ' + escapeHtml(user.address_detail);
                    }
                    html += '</div></div>';
                }
                
                // 생년월일
                if (user.birth_date) {
                    html += '<div class="detail-row"><div class="detail-label">생년월일</div><div class="detail-value">' + escapeHtml(user.birth_date) + '</div></div>';
                }
                
                // 성별
                if (user.gender) {
                    let genderText = user.gender;
                    if (genderText === 'male') genderText = '남성';
                    else if (genderText === 'female') genderText = '여성';
                    else if (genderText === 'other') genderText = '기타';
                    html += '<div class="detail-row"><div class="detail-label">성별</div><div class="detail-value">' + escapeHtml(genderText) + '</div></div>';
                }
                
                // 가입일
                if (user.created_at) {
                    html += '<div class="detail-row"><div class="detail-label">가입일</div><div class="detail-value">' + escapeHtml(user.created_at) + '</div></div>';
                }
                
                html += '</div>';
                content.innerHTML = html;
            } else {
                content.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #ef4444;">
                        <p>사용자 정보를 불러올 수 없습니다.</p>
                        <p style="font-size: 13px; color: #6b7280; margin-top: 8px;">${data.message || '알 수 없는 오류가 발생했습니다.'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #ef4444;">
                    <p>사용자 정보를 불러오는 중 오류가 발생했습니다.</p>
                </div>
            `;
        });
}

function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 모달 외부 클릭 시 닫기
document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeUserModal();
    }
});
</script>

<?php
// 푸터 포함
include __DIR__ . '/../includes/admin-footer.php';
?>
