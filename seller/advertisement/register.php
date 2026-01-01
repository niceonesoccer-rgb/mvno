<?php
/**
 * 광고 신청 페이지 (판매자)
 * 경로: /seller/advertisement/register.php
 */

require_once __DIR__ . '/../includes/seller-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/product-functions.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

$currentUser = getCurrentUser();
$sellerId = $currentUser['user_id'] ?? '';

if (empty($sellerId)) {
    header('Location: /MVNO/seller/login.php');
    exit;
}

$error = '';
$success = '';

// 광고 신청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = intval($_POST['product_id'] ?? 0);
    $advertisementDays = intval($_POST['advertisement_days'] ?? 0);
    
    // system_settings에서 현재 로테이션 시간 가져오기
    $rotationDuration = 30; // 기본값
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'advertisement_rotation_duration'");
        $stmt->execute();
        $durationValue = $stmt->fetchColumn();
        if ($durationValue) {
            $rotationDuration = intval($durationValue);
        }
    } catch (PDOException $e) {
        error_log('Rotation duration 조회 오류: ' . $e->getMessage());
    }
    
    if ($productId <= 0 || $advertisementDays <= 0) {
        $error = '모든 필드를 올바르게 선택해주세요.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // 상품 정보 조회
            $stmt = $pdo->prepare("SELECT id, seller_id, product_type, status FROM products WHERE id = :id AND seller_id = :seller_id");
            $stmt->execute([':id' => $productId, ':seller_id' => $sellerId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception('상품을 찾을 수 없습니다.');
            }
            
            if ($product['status'] !== 'active') {
                throw new Exception('판매중인 상품만 광고할 수 있습니다.');
            }
            
            // 같은 상품의 활성화된 광고 중복 체크
            $stmt = $pdo->prepare("
                SELECT id FROM rotation_advertisements 
                WHERE product_id = :product_id 
                AND status = 'active' 
                AND end_datetime > NOW()
            ");
            $stmt->execute([':product_id' => $productId]);
            if ($stmt->fetch()) {
                throw new Exception('이미 광고 중인 상품입니다. 광고가 종료된 후 다시 신청해주세요.');
            }
            
            // 가격 조회
            // rotation_advertisement_prices 테이블은 mno_sim (언더스코어)를 사용하므로 변환
            $priceProductType = $product['product_type'];
            if ($priceProductType === 'mno-sim') {
                $priceProductType = 'mno_sim';
            }
            
            $stmt = $pdo->prepare("
                SELECT price FROM rotation_advertisement_prices 
                WHERE product_type = :product_type 
                AND advertisement_days = :advertisement_days 
                AND is_active = 1
            ");
            $stmt->execute([
                ':product_type' => $priceProductType,
                ':advertisement_days' => $advertisementDays
            ]);
            $priceData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$priceData) {
                throw new Exception('선택한 조건의 가격 정보를 찾을 수 없습니다.');
            }
            
            $supplyAmount = floatval($priceData['price']); // 공급가액
            $taxAmount = $supplyAmount * 0.1; // 부가세 (10%)
            $totalAmount = $supplyAmount + $taxAmount; // 부가세 포함 총액
            
            // 예치금 잔액 확인 (부가세 포함 금액으로 확인)
            $stmt = $pdo->prepare("SELECT balance FROM seller_deposit_accounts WHERE seller_id = :seller_id FOR UPDATE");
            $stmt->execute([':seller_id' => $sellerId]);
            $balanceData = $stmt->fetch(PDO::FETCH_ASSOC);
            $currentBalance = floatval($balanceData['balance'] ?? 0);
            
            if ($currentBalance < $totalAmount) {
                throw new Exception('예치금 잔액이 부족합니다. 예치금을 충전해주세요.');
            }
            
            // 광고 등록
            // rotation_advertisements 테이블은 mno_sim (언더스코어)를 사용하므로 변환
            $adProductType = $product['product_type'];
            if ($adProductType === 'mno-sim') {
                $adProductType = 'mno_sim';
            }
            
            $startDatetime = date('Y-m-d H:i:s');
            $endDatetime = date('Y-m-d H:i:s', strtotime($startDatetime) + ($advertisementDays * 86400));
            
            $stmt = $pdo->prepare("
                INSERT INTO rotation_advertisements 
                (product_id, seller_id, product_type, rotation_duration, advertisement_days, price, start_datetime, end_datetime, status)
                VALUES (:product_id, :seller_id, :product_type, :rotation_duration, :advertisement_days, :price, :start_datetime, :end_datetime, 'active')
            ");
            $stmt->execute([
                ':product_id' => $productId,
                ':seller_id' => $sellerId,
                ':product_type' => $adProductType,
                ':rotation_duration' => $rotationDuration,
                ':advertisement_days' => $advertisementDays,
                ':price' => $supplyAmount, // 광고 테이블에는 공급가액 저장
                ':start_datetime' => $startDatetime,
                ':end_datetime' => $endDatetime
            ]);
            
            $adId = $pdo->lastInsertId();
            
            // 예치금 차감 (부가세 포함 총액 차감)
            $newBalance = $currentBalance - $totalAmount;
            $pdo->prepare("UPDATE seller_deposit_accounts SET balance = :balance, updated_at = NOW() WHERE seller_id = :seller_id")
                ->execute([':balance' => $newBalance, ':seller_id' => $sellerId]);
            
            // 예치금 내역 기록 (부가세 포함 총액 차감)
            $pdo->prepare("
                INSERT INTO seller_deposit_ledger 
                (seller_id, transaction_type, amount, balance_before, balance_after, advertisement_id, description, created_at)
                VALUES (:seller_id, 'withdraw', :amount, :balance_before, :balance_after, :advertisement_id, :description, NOW())
            ")->execute([
                ':seller_id' => $sellerId,
                ':amount' => -$totalAmount,
                ':balance_before' => $currentBalance,
                ':balance_after' => $newBalance,
                ':advertisement_id' => $adId,
                ':description' => '광고 신청 차감'
            ]);
            
            $pdo->commit();
            $success = '광고 신청이 완료되었습니다. 광고가 즉시 시작됩니다.';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Advertisement register error: ' . $e->getMessage());
            $error = $e->getMessage();
        }
    }
}

// product_id 파라미터 확인
$selectedProductId = intval($_GET['product_id'] ?? 0);

// 탭 파라미터 (기본값: 통신사단독유심)
$activeTab = $_GET['tab'] ?? 'mno-sim';
$validTabs = ['mno-sim', 'mvno', 'mno', 'internet'];
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'mno-sim';
}

// product_id가 전달된 경우 해당 상품의 타입으로 탭 설정
// DB의 product_type을 탭 이름으로 변환 (mno-sim -> mno_sim)
$tabToDbTypeMap = [
    'mno-sim' => 'mno-sim',
    'mno_sim' => 'mno-sim', // 하위 호환성
    'mvno' => 'mvno',
    'mno' => 'mno',
    'internet' => 'internet'
];
$dbToTabTypeMap = array_flip($tabToDbTypeMap);

if ($selectedProductId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT product_type FROM products WHERE id = :id AND seller_id = :seller_id");
        $stmt->execute([':id' => $selectedProductId, ':seller_id' => $sellerId]);
        $dbProductType = $stmt->fetchColumn();
        if ($dbProductType && isset($dbToTabTypeMap[$dbProductType])) {
            $activeTab = $dbToTabTypeMap[$dbProductType];
        }
    } catch (PDOException $e) {
        error_log("Error fetching product type: " . $e->getMessage());
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// 예치금 잔액 조회
$stmt = $pdo->prepare("SELECT balance FROM seller_deposit_accounts WHERE seller_id = :seller_id");
$stmt->execute([':seller_id' => $sellerId]);
$balanceResult = $stmt->fetch(PDO::FETCH_ASSOC);
$balance = floatval($balanceResult['balance'] ?? 0);

$productTypeLabels = [
    'mno-sim' => '통신사단독유심',
    'mvno' => '알뜰폰',
    'mno' => '통신사폰',
    'internet' => '인터넷'
];

// 탭별 상품 개수 조회
$tabCounts = ['mno-sim' => 0, 'mvno' => 0, 'mno' => 0, 'internet' => 0];
try {
    $countStmt = $pdo->prepare("
        SELECT product_type, COUNT(*) as count
        FROM products
        WHERE seller_id = :seller_id AND status = 'active'
        GROUP BY product_type
    ");
    $countStmt->execute([':seller_id' => $sellerId]);
    $typeCounts = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $tabCounts['mno-sim'] = $typeCounts['mno-sim'] ?? 0;
    $tabCounts['mvno'] = $typeCounts['mvno'] ?? 0;
    $tabCounts['mno'] = $typeCounts['mno'] ?? 0;
    $tabCounts['internet'] = $typeCounts['internet'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching tab counts: " . $e->getMessage());
}

// 탭 이름을 DB의 product_type으로 변환 (mno_sim -> mno-sim)
$productTypeMap = [
    'mno_sim' => 'mno-sim',
    'mvno' => 'mvno',
    'mno' => 'mno',
    'internet' => 'internet'
];
$dbProductType = $productTypeMap[$activeTab] ?? $activeTab;

// WHERE 조건 구성
$whereConditions = ["p.seller_id = :seller_id", "p.status = 'active'", "p.product_type = :product_type"];
$params = [':seller_id' => $sellerId, ':product_type' => $dbProductType];

$whereClause = implode(' AND ', $whereConditions);

// 전체 개수 조회
$countStmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM products p
    WHERE $whereClause
");
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// 상품 목록 조회
$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        CASE p.product_type
            WHEN 'mvno' THEN mvno.plan_name
            WHEN 'mno' THEN mno.device_name
            WHEN 'mno-sim' THEN mno_sim.plan_name
            WHEN 'internet' THEN CONCAT(inet.registration_place, ' ', inet.speed_option)
        END AS product_name,
        CASE p.product_type
            WHEN 'mvno' THEN mvno.provider
            WHEN 'mno' THEN 'SKT/KT/LG U+'
            WHEN 'mno-sim' THEN mno_sim.provider
            WHEN 'internet' THEN inet.registration_place
        END AS provider,
        CASE p.product_type
            WHEN 'mvno' THEN mvno.service_type
            WHEN 'mno' THEN mno.data_amount
            WHEN 'mno-sim' THEN mno_sim.service_type
            WHEN 'internet' THEN inet.speed_option
        END AS data_speed,
        CASE p.product_type
            WHEN 'mvno' THEN mvno.registration_types
            WHEN 'mno' THEN mno.contract_period
            WHEN 'mno-sim' THEN mno_sim.registration_types
            WHEN 'internet' THEN NULL
        END AS registration_type,
        CASE p.product_type
            WHEN 'mvno' THEN mvno.price_main
            WHEN 'mno' THEN mno.price_main
            WHEN 'mno-sim' THEN mno_sim.price_main
            WHEN 'internet' THEN inet.monthly_fee
        END AS monthly_fee,
        CASE p.product_type
            WHEN 'mvno' THEN mvno.discount_period
            WHEN 'mno' THEN NULL
            WHEN 'mno-sim' THEN mno_sim.discount_period
            WHEN 'internet' THEN NULL
        END AS discount_period,
        CASE p.product_type
            WHEN 'mvno' THEN mvno.price_after
            WHEN 'mno' THEN NULL
            WHEN 'mno-sim' THEN mno_sim.price_after
            WHEN 'internet' THEN NULL
        END AS discount_price,
        COALESCE(prs.total_review_count, 0) AS review_count,
        CASE WHEN EXISTS (
            SELECT 1 FROM rotation_advertisements ra 
            WHERE ra.product_id = p.id 
            AND ra.status = 'active' 
            AND ra.end_datetime > NOW()
        ) THEN 1 ELSE 0 END AS has_active_ad
    FROM products p
    LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND p.product_type = 'mvno'
    LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
    LEFT JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id AND p.product_type = 'mno-sim'
    LEFT JOIN product_internet_details inet ON p.id = inet.product_id AND p.product_type = 'internet'
    LEFT JOIN product_review_statistics prs ON p.id = prs.product_id
    WHERE $whereClause
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
");

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$advertisementDaysOptions = [1, 2, 3, 5, 7, 10, 14, 30];
?>

<style>
.product-tabs {
    background: white;
    border-radius: 12px;
    padding: 8px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    display: flex;
    gap: 8px;
    overflow-x: auto;
}

.product-tab {
    flex: 1;
    min-width: 120px;
    padding: 12px 20px;
    text-align: center;
    font-size: 15px;
    font-weight: 600;
    color: #6b7280;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.product-tab:hover {
    background: #f9fafb;
    color: #374151;
}

.product-tab.active {
    background: #6366f1;
    color: white;
}

.product-tab.active:hover {
    background: #4f46e5;
}

.product-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    font-size: 14px;
}

.product-table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    background: #f1f5f9;
    border-bottom: 2px solid #e2e8f0;
}

.product-table td {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
}

.product-table tr:hover {
    background: #f8fafc;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
}

.pagination-btn {
    padding: 8px 16px;
    font-size: 14px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    color: #374151;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.pagination-btn:hover {
    background: #f9fafb;
    border-color: #6366f1;
}

.pagination-btn.active {
    background: #6366f1;
    color: white;
    border-color: #6366f1;
}

.btn {
    padding: 6px 12px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-ad {
    background: #6366f1;
    color: white;
}

.btn-ad:hover {
    background: #4f46e5;
}

.btn-ad:disabled {
    background: #cbd5e1;
    color: #64748b;
    cursor: not-allowed;
}
</style>

<?php
$pageHeaders = [
    'mno-sim' => '통신사단독유심 광고 신청',
    'mvno' => '알뜰폰 광고 신청',
    'mno' => '통신사폰 광고 신청',
    'internet' => '인터넷 광고 신청'
];
$currentPageHeader = $pageHeaders[$activeTab] ?? '광고 신청';
?>

<div class="seller-center-container">
    <div class="page-header" style="margin-bottom: 32px;">
        <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 8px;"><?= $currentPageHeader ?></h1>
        <p style="font-size: 16px; color: #64748b;">상품 광고를 신청하여 더 많은 고객에게 노출시키세요.</p>
    </div>
    
    <?php if ($error): ?>
        <div style="padding: 12px; background: #fee2e2; color: #991b1b; border-radius: 6px; margin-bottom: 20px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div style="padding: 12px; background: #d1fae5; color: #065f46; border-radius: 6px; margin-bottom: 20px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <div style="margin-bottom: 24px; padding: 20px; background: #f8fafc; border-radius: 8px;">
        <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">현재 예치금 잔액</div>
        <div style="font-size: 32px; font-weight: 700; color: #6366f1;"><?= number_format($balance, 0) ?>원</div>
        <?php if ($balance < 10000): ?>
            <div style="margin-top: 8px; color: #f59e0b; font-size: 14px;">
                예치금이 부족합니다. <a href="/MVNO/seller/deposit/charge.php" style="color: #6366f1; text-decoration: underline;">예치금 충전하기</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 탭 메뉴 -->
    <div class="product-tabs">
        <button class="product-tab <?= $activeTab === 'mno-sim' ? 'active' : '' ?>" onclick="switchTab('mno-sim')">
            통신사단독유심 (<?= $tabCounts['mno-sim'] ?>)
        </button>
        <button class="product-tab <?= $activeTab === 'mvno' ? 'active' : '' ?>" onclick="switchTab('mvno')">
            알뜰폰 (<?= $tabCounts['mvno'] ?>)
        </button>
        <button class="product-tab <?= $activeTab === 'mno' ? 'active' : '' ?>" onclick="switchTab('mno')">
            통신사폰 (<?= $tabCounts['mno'] ?>)
        </button>
        <button class="product-tab <?= $activeTab === 'internet' ? 'active' : '' ?>" onclick="switchTab('internet')">
            인터넷 (<?= $tabCounts['internet'] ?>)
        </button>
    </div>
    
    <div class="content-box" style="background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <?php if (empty($products)): ?>
            <div style="padding: 40px; text-align: center; color: #64748b;">
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">광고할 상품이 없습니다</div>
                <div>판매중인 상품을 먼저 등록해주세요.</div>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">번호</th>
                            <th>상품명</th>
                            <th style="text-align: center;">통신사</th>
                            <th style="text-align: center;">데이터 속도</th>
                            <th style="text-align: center;">가입 형태</th>
                            <th style="text-align: right;">월 요금</th>
                            <th style="text-align: center;">할인기간</th>
                            <th style="text-align: right;">할인기간요금</th>
                            <th style="text-align: right;">조회수</th>
                            <th style="text-align: right;">찜</th>
                            <th style="text-align: right;">리뷰</th>
                            <th style="text-align: right;">신청</th>
                            <th style="text-align: center;">상태</th>
                            <th style="text-align: center;">등록일</th>
                            <th style="text-align: center;">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $index => $product): 
                            // registration_type 처리
                            $registrationType = $product['registration_type'] ?? '';
                            if (!empty($registrationType) && (substr($registrationType, 0, 1) === '[' || substr($registrationType, 0, 1) === '{')) {
                                $registrationTypeArr = json_decode($registrationType, true);
                                if (is_array($registrationTypeArr)) {
                                    $registrationType = implode(', ', $registrationTypeArr);
                                }
                            }
                            
                            // discount_period 처리
                            $discountPeriod = $product['discount_period'] ?? '';
                            if ($discountPeriod === '프로모션 없음' || empty($discountPeriod)) {
                                $discountPeriod = '-';
                            }
                            
                            // discount_price 처리
                            $discountPrice = $product['discount_price'] ?? null;
                            $discountPriceDisplay = '-';
                            if ($discountPrice !== null && $discountPrice !== '') {
                                if ($discountPrice == 0) {
                                    $discountPriceDisplay = '공짜';
                                } else {
                                    $discountPriceDisplay = number_format(floatval($discountPrice), 0) . '원';
                                }
                            }
                            
                            $hasActiveAd = intval($product['has_active_ad'] ?? 0);
                        ?>
                            <tr>
                                <td style="text-align: center;"><?= $totalProducts - (($page - 1) * $perPage + $index) ?></td>
                                <td style="font-weight: 500;"><?= htmlspecialchars($product['product_name'] ?? '-') ?></td>
                                <td style="text-align: center;"><?= htmlspecialchars($product['provider'] ?? '-') ?></td>
                                <td style="text-align: center;"><?= htmlspecialchars($product['data_speed'] ?? '-') ?></td>
                                <td style="text-align: center; font-size: 13px;"><?= htmlspecialchars($registrationType ?: '-') ?></td>
                                <td style="text-align: right;"><?= $product['monthly_fee'] ? number_format(floatval($product['monthly_fee']), 0) . '원' : '-' ?></td>
                                <td style="text-align: center; font-size: 13px;"><?= htmlspecialchars($discountPeriod) ?></td>
                                <td style="text-align: right; font-size: 13px;"><?= $discountPriceDisplay ?></td>
                                <td style="text-align: right;"><?= number_format(intval($product['view_count'] ?? 0), 0) ?></td>
                                <td style="text-align: right;"><?= number_format(intval($product['favorite_count'] ?? 0), 0) ?></td>
                                <td style="text-align: right;"><?= number_format(intval($product['review_count'] ?? 0), 0) ?></td>
                                <td style="text-align: right;"><?= number_format(intval($product['application_count'] ?? 0), 0) ?></td>
                                <td style="text-align: center;">
                                    <span style="padding: 4px 12px; background: #10b98120; color: #10b981; border-radius: 4px; font-size: 14px; font-weight: 500;">
                                        판매중
                                    </span>
                                </td>
                                <td style="text-align: center; font-size: 13px; color: #64748b;">
                                    <?= date('Y-m-d', strtotime($product['created_at'])) ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($hasActiveAd): ?>
                                        <span style="color: #64748b; font-size: 13px;">광고중</span>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-ad" onclick="openAdModal(<?= $product['id'] ?>, '<?= $product['product_type'] ?>', '<?= htmlspecialchars($product['product_name'] ?? '', ENT_QUOTES) ?>')">
                                            광고
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1): ?>
                <?php
                // 페이지 그룹 계산 (10개씩 그룹화)
                $pageGroupSize = 10;
                $currentGroup = ceil($page / $pageGroupSize);
                $startPage = ($currentGroup - 1) * $pageGroupSize + 1;
                $endPage = min($currentGroup * $pageGroupSize, $totalPages);
                $prevGroupLastPage = ($currentGroup - 1) * $pageGroupSize;
                $nextGroupFirstPage = $currentGroup * $pageGroupSize + 1;
                ?>
                <div class="pagination">
                    <?php if ($currentGroup > 1): ?>
                        <a href="?tab=<?= $activeTab ?>&page=<?= $prevGroupLastPage ?>" class="pagination-btn">이전</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">이전</span>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?tab=<?= $activeTab ?>&page=<?= $i ?>" class="pagination-btn <?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($nextGroupFirstPage <= $totalPages): ?>
                        <a href="?tab=<?= $activeTab ?>&page=<?= $nextGroupFirstPage ?>" class="pagination-btn">다음</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">다음</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- 광고 신청 모달 -->
<div id="adModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 32px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 600;">광고 신청</h2>
            <button type="button" onclick="closeAdModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        
        <form method="POST" id="adForm">
            <input type="hidden" name="product_id" id="modalProductId">
            
            <div style="margin-bottom: 20px;">
                <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 16px;">
                    <div style="font-size: 14px; color: #64748b; margin-bottom: 4px;">상품</div>
                    <div style="font-size: 16px; font-weight: 600;" id="modalProductName"></div>
                </div>
                
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    광고 기간 <span style="color: #ef4444;">*</span>
                </label>
                <select name="advertisement_days" id="modalAdvertisementDays" required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;">
                    <option value="">광고 기간을 선택하세요</option>
                    <?php foreach ($advertisementDaysOptions as $days): ?>
                        <option value="<?= $days ?>"><?= $days ?>일</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="modalPricePreview" style="margin-bottom: 24px; padding: 20px; background: #f8fafc; border-radius: 8px; display: none;">
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">광고 금액</div>
                <div id="modalPriceAmount"></div>
                <div id="modalBalanceCheck" style="margin-top: 12px; font-size: 14px;"></div>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="submit" id="modalSubmitBtn" disabled
                        style="flex: 1; padding: 12px 24px; background: #cbd5e1; color: #64748b; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: not-allowed;">
                    광고 신청
                </button>
                <button type="button" onclick="closeAdModal()" style="flex: 1; padding: 12px 24px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                    취소
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const currentBalance = <?= $balance ?>;
let currentProductType = '';

function switchTab(tab) {
    window.location.href = '?tab=' + tab;
}

function openAdModal(productId, productType, productName) {
    document.getElementById('modalProductId').value = productId;
    document.getElementById('modalProductName').textContent = productName;
    document.getElementById('modalAdvertisementDays').value = '';
    document.getElementById('modalPricePreview').style.display = 'none';
    document.getElementById('modalSubmitBtn').disabled = true;
    document.getElementById('modalSubmitBtn').style.background = '#cbd5e1';
    document.getElementById('modalSubmitBtn').style.color = '#64748b';
    document.getElementById('modalSubmitBtn').style.cursor = 'not-allowed';
    
    currentProductType = productType;
    document.getElementById('adModal').style.display = 'flex';
}

function closeAdModal() {
    document.getElementById('adModal').style.display = 'none';
}

// 모달 배경 클릭 시 닫기
document.getElementById('adModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAdModal();
    }
});

async function updateModalPrice() {
    const productId = document.getElementById('modalProductId').value;
    const days = document.getElementById('modalAdvertisementDays').value;
    
    if (!productId || !days || !currentProductType) {
        document.getElementById('modalPricePreview').style.display = 'none';
        document.getElementById('modalSubmitBtn').disabled = true;
        document.getElementById('modalSubmitBtn').style.background = '#cbd5e1';
        document.getElementById('modalSubmitBtn').style.color = '#64748b';
        document.getElementById('modalSubmitBtn').style.cursor = 'not-allowed';
        return;
    }
    
    try {
        const url = `/MVNO/api/advertisement-price.php?product_type=${encodeURIComponent(currentProductType)}&advertisement_days=${days}`;
        console.log('Fetching price from:', url);
        const response = await fetch(url);
        const data = await response.json();
        
        console.log('Price API response:', data);
        
        if (data.success && data.price) {
            const supplyAmount = parseFloat(data.price);
            const taxAmount = supplyAmount * 0.1;
            const totalAmount = supplyAmount + taxAmount;
            
            document.getElementById('modalPriceAmount').innerHTML = `
                <div style="font-size: 24px; margin-bottom: 4px;">공급가액: ${new Intl.NumberFormat('ko-KR').format(Math.round(supplyAmount))}원</div>
                <div style="font-size: 14px; color: #64748b; margin-bottom: 4px;">부가세 (10%): ${new Intl.NumberFormat('ko-KR').format(Math.round(taxAmount))}원</div>
                <div style="font-size: 32px; font-weight: 700; color: #6366f1; margin-top: 8px;">입금금액 (부가세 포함): ${new Intl.NumberFormat('ko-KR').format(Math.round(totalAmount))}원</div>
            `;
            document.getElementById('modalPricePreview').style.display = 'block';
            
            if (currentBalance >= totalAmount) {
                document.getElementById('modalBalanceCheck').innerHTML = '<span style="color: #10b981;">✓ 예치금 잔액이 충분합니다.</span>';
                document.getElementById('modalSubmitBtn').disabled = false;
                document.getElementById('modalSubmitBtn').style.background = '#6366f1';
                document.getElementById('modalSubmitBtn').style.color = '#fff';
                document.getElementById('modalSubmitBtn').style.cursor = 'pointer';
            } else {
                document.getElementById('modalBalanceCheck').innerHTML = '<span style="color: #ef4444;">✗ 예치금 잔액이 부족합니다. 예치금을 충전해주세요.</span>';
                document.getElementById('modalSubmitBtn').disabled = true;
                document.getElementById('modalSubmitBtn').style.background = '#cbd5e1';
                document.getElementById('modalSubmitBtn').style.color = '#64748b';
                document.getElementById('modalSubmitBtn').style.cursor = 'not-allowed';
            }
        } else {
            console.error('Price API failed:', data.message || 'Unknown error');
            document.getElementById('modalPricePreview').style.display = 'block';
            document.getElementById('modalPriceAmount').innerHTML = `
                <div style="color: #ef4444; font-size: 14px;">
                    ⚠️ 가격 정보를 가져올 수 없습니다: ${data.message || '알 수 없는 오류'}
                </div>
            `;
            document.getElementById('modalBalanceCheck').innerHTML = '';
            document.getElementById('modalSubmitBtn').disabled = true;
            document.getElementById('modalSubmitBtn').style.background = '#cbd5e1';
            document.getElementById('modalSubmitBtn').style.color = '#64748b';
            document.getElementById('modalSubmitBtn').style.cursor = 'not-allowed';
        }
    } catch (error) {
        console.error('Price fetch error:', error);
        document.getElementById('modalPricePreview').style.display = 'block';
        document.getElementById('modalPriceAmount').innerHTML = `
            <div style="color: #ef4444; font-size: 14px;">
                ⚠️ 오류가 발생했습니다. 다시 시도해주세요.
            </div>
        `;
        document.getElementById('modalBalanceCheck').innerHTML = '';
        document.getElementById('modalSubmitBtn').disabled = true;
        document.getElementById('modalSubmitBtn').style.background = '#cbd5e1';
        document.getElementById('modalSubmitBtn').style.color = '#64748b';
        document.getElementById('modalSubmitBtn').style.cursor = 'not-allowed';
    }
}

document.getElementById('modalAdvertisementDays')?.addEventListener('change', updateModalPrice);

<?php if ($selectedProductId > 0): ?>
// product_id가 전달된 경우 페이지 상단으로 스크롤 및 모달 자동 열기
document.addEventListener('DOMContentLoaded', function() {
    // 페이지 상단으로 스크롤
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    const productId = <?= $selectedProductId ?>;
    // 탭 이름을 DB 타입으로 변환 (mno-sim은 그대로, 하위 호환성을 위해 mno_sim도 지원)
    const tabToDbType = {
        'mno-sim': 'mno-sim',
        'mno_sim': 'mno-sim', // 하위 호환성
        'mvno': 'mvno',
        'mno': 'mno',
        'internet': 'internet'
    };
    const productType = tabToDbType['<?= $activeTab ?>'] || '<?= $activeTab ?>';
    
    // 상품명 조회
    fetch(`/MVNO/api/get-product-name.php?product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.product_name) {
                openAdModal(productId, productType, data.product_name);
            }
        })
        .catch(error => {
            console.error('Error fetching product name:', error);
        });
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>
