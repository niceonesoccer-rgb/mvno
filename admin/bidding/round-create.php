<?php
/**
 * 입찰 라운드 생성 페이지 (관리자)
 * 경로: /admin/bidding/round-create.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    header('Location: /MVNO/admin/login.php');
    exit;
}

$error = '';
$success = '';
$pdo = getDBConnection();

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_round') {
    $category = $_POST['category'] ?? '';
    $biddingStartAt = $_POST['bidding_start_at'] ?? '';
    $biddingEndAt = $_POST['bidding_end_at'] ?? '';
    $displayStartAt = $_POST['display_start_at'] ?? '';
    $displayEndAt = $_POST['display_end_at'] ?? '';
    $maxDisplayCount = intval($_POST['max_display_count'] ?? 20);
    $minBidAmount = floatval($_POST['min_bid_amount'] ?? 0);
    $maxBidAmount = floatval($_POST['max_bid_amount'] ?? 100000);
    $rotationType = $_POST['rotation_type'] ?? 'fixed';
    $rotationIntervalMinutes = $rotationType === 'rotating' ? intval($_POST['rotation_interval_minutes'] ?? 60) : null;
    
    // 유효성 검사
    if (empty($category) || !in_array($category, ['mno', 'mvno', 'mno_sim'])) {
        $error = '카테고리를 선택해주세요.';
    } elseif (empty($biddingStartAt) || empty($biddingEndAt)) {
        $error = '입찰 기간을 입력해주세요.';
    } elseif (empty($displayStartAt) || empty($displayEndAt)) {
        $error = '게시 기간을 입력해주세요.';
    } elseif (strtotime($biddingEndAt) <= strtotime($biddingStartAt)) {
        $error = '입찰 종료일시는 시작일시 이후여야 합니다.';
    } elseif (strtotime($displayEndAt) <= strtotime($displayStartAt)) {
        $error = '게시 종료일시는 시작일시 이후여야 합니다.';
    } elseif ($minBidAmount < 0) {
        $error = '최소 입찰금액은 0원 이상이어야 합니다.';
    } elseif ($maxBidAmount <= $minBidAmount) {
        $error = '최대 입찰금액은 최소 입찰금액보다 커야 합니다.';
    } elseif ($maxDisplayCount <= 0 || $maxDisplayCount > 100) {
        $error = '최대 노출 개수는 1~100 사이여야 합니다.';
    } elseif ($rotationType === 'rotating' && (!$rotationIntervalMinutes || $rotationIntervalMinutes < 1)) {
        $error = '순환 간격을 입력해주세요.';
    } else {
        // 상태 결정: 현재 시간 기준으로 초기 상태 설정
        $now = date('Y-m-d H:i:s');
        if (strtotime($biddingStartAt) > strtotime($now)) {
            $status = 'upcoming';
        } elseif (strtotime($biddingStartAt) <= strtotime($now) && strtotime($biddingEndAt) >= strtotime($now)) {
            $status = 'bidding';
        } else {
            $status = 'closed';
        }
        
        try {
            if (!$pdo) {
                throw new Exception('DB 연결에 실패했습니다.');
            }
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO bidding_rounds (
                    category, bidding_start_at, bidding_end_at,
                    display_start_at, display_end_at,
                    max_display_count, min_bid_amount, max_bid_amount,
                    rotation_type, rotation_interval_minutes,
                    status, created_by
                ) VALUES (
                    :category, :bidding_start_at, :bidding_end_at,
                    :display_start_at, :display_end_at,
                    :max_display_count, :min_bid_amount, :max_bid_amount,
                    :rotation_type, :rotation_interval_minutes,
                    :status, :created_by
                )
            ");
            
            $stmt->execute([
                ':category' => $category,
                ':bidding_start_at' => $biddingStartAt,
                ':bidding_end_at' => $biddingEndAt,
                ':display_start_at' => $displayStartAt,
                ':display_end_at' => $displayEndAt,
                ':max_display_count' => $maxDisplayCount,
                ':min_bid_amount' => $minBidAmount,
                ':max_bid_amount' => $maxBidAmount,
                ':rotation_type' => $rotationType,
                ':rotation_interval_minutes' => $rotationIntervalMinutes,
                ':status' => $status,
                ':created_by' => $currentUser['user_id']
            ]);
            
            $pdo->commit();
            $success = '입찰 라운드가 생성되었습니다.';
            
            // 성공 시 목록으로 리다이렉트
            header('Location: /MVNO/admin/bidding/rounds.php?success=created');
            exit;
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = '입찰 라운드 생성 중 오류가 발생했습니다: ' . $e->getMessage();
            error_log("Bidding round create error: " . $e->getMessage());
        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/admin-header.php';

// 카테고리 라벨
$categoryLabels = [
    'mno' => '통신사폰',
    'mvno' => '알뜰폰',
    'mno_sim' => '통신사단독유심'
];
?>

<style>
    .page-header {
        margin-bottom: 32px;
    }
    
    .page-title {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }
    
    .page-description {
        color: #64748b;
        font-size: 15px;
    }
    
    .form-container {
        background: white;
        border-radius: 12px;
        padding: 32px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        max-width: 800px;
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
    }
    
    .form-label .required {
        color: #ef4444;
        margin-left: 4px;
    }
    
    .form-input,
    .form-select {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s;
    }
    
    .form-input:focus,
    .form-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    
    .form-help {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        color: #6b7280;
    }
    
    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
    }
    
    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    
    .btn-secondary:hover {
        background: #e5e7eb;
    }
    
    .error-message {
        background: #fee2e2;
        color: #991b1b;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        border-left: 4px solid #dc2626;
    }
    
    .success-message {
        background: #d1fae5;
        color: #065f46;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        border-left: 4px solid #10b981;
    }
    
    .rotation-settings {
        background: #f8fafc;
        padding: 16px;
        border-radius: 8px;
        margin-top: 12px;
        display: none;
    }
    
    .rotation-settings.active {
        display: block;
    }
</style>

<div class="page-header">
    <h1 class="page-title">입찰 라운드 생성</h1>
    <p class="page-description">새로운 입찰 라운드를 생성합니다.</p>
</div>

<?php if ($error): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success-message">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<div class="form-container">
    <form method="POST" action="" id="roundForm">
        <input type="hidden" name="action" value="create_round">
        
        <div class="form-group">
            <label class="form-label">
                카테고리 <span class="required">*</span>
            </label>
            <select name="category" class="form-select" required>
                <option value="">선택하세요</option>
                <option value="mno">통신사폰</option>
                <option value="mvno">알뜰폰</option>
                <option value="mno_sim">통신사단독유심</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                입찰 기간 <span class="required">*</span>
            </label>
            <div class="form-row">
                <div>
                    <input type="datetime-local" name="bidding_start_at" class="form-input" required>
                    <small class="form-help">입찰 시작일시</small>
                </div>
                <div>
                    <input type="datetime-local" name="bidding_end_at" class="form-input" required>
                    <small class="form-help">입찰 종료일시</small>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                게시 기간 <span class="required">*</span>
            </label>
            <div class="form-row">
                <div>
                    <input type="date" name="display_start_at" class="form-input" required>
                    <small class="form-help">게시 시작일</small>
                </div>
                <div>
                    <input type="date" name="display_end_at" class="form-input" required>
                    <small class="form-help">게시 종료일</small>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                최대 노출 개수 <span class="required">*</span>
            </label>
            <input type="number" name="max_display_count" class="form-input" value="20" min="1" max="100" required>
            <small class="form-help">상단에 노출될 최대 게시물 개수 (기본: 20개)</small>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                입찰 금액 범위 <span class="required">*</span>
            </label>
            <div class="form-row">
                <div>
                    <input type="number" name="min_bid_amount" class="form-input" value="0" min="0" step="1000" required>
                    <small class="form-help">최소 입찰금액 (원)</small>
                </div>
                <div>
                    <input type="number" name="max_bid_amount" class="form-input" value="100000" min="0" step="1000" required>
                    <small class="form-help">최대 입찰금액 (원)</small>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                운용 방식 <span class="required">*</span>
            </label>
            <select name="rotation_type" class="form-select" id="rotationType" required>
                <option value="fixed">고정</option>
                <option value="rotating">순환</option>
            </select>
            <small class="form-help">고정: 입찰금액 순으로 고정 배치, 순환: 일정 간격으로 순서 변경</small>
            
            <div class="rotation-settings" id="rotationSettings">
                <label class="form-label" style="margin-top: 16px;">
                    순환 간격 (분) <span class="required">*</span>
                </label>
                <input type="number" name="rotation_interval_minutes" class="form-input" value="60" min="1" step="1">
                <small class="form-help">예: 60분 입력 시 1시간마다 순서 변경</small>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="/MVNO/admin/bidding/rounds.php" class="btn btn-secondary">취소</a>
            <button type="submit" class="btn btn-primary">생성</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rotationType = document.getElementById('rotationType');
    const rotationSettings = document.getElementById('rotationSettings');
    
    function updateRotationSettings() {
        if (rotationType.value === 'rotating') {
            rotationSettings.classList.add('active');
            rotationSettings.querySelector('input[name="rotation_interval_minutes"]').required = true;
        } else {
            rotationSettings.classList.remove('active');
            rotationSettings.querySelector('input[name="rotation_interval_minutes"]').required = false;
        }
    }
    
    rotationType.addEventListener('change', updateRotationSettings);
    updateRotationSettings();
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>


