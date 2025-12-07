<?php
/**
 * 판매자 상품 등록 폼 컴포넌트
 * 권한 체크 예제
 */

require_once __DIR__ . '/../data/auth-functions.php';

// 현재 사용자 확인
$currentUser = getCurrentUser();

// 판매자 권한 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    die('판매자만 접근할 수 있습니다.');
}

if (!isSellerApproved()) {
    die('판매자 승인이 필요합니다. 관리자에게 문의하세요.');
}

// 게시판 타입 (mvno, mno, internet)
$boardType = $_GET['type'] ?? '';

if (empty($boardType)) {
    die('게시판 타입을 선택해주세요.');
}

// 권한 체크
if (!hasSellerPermission($currentUser['user_id'], $boardType)) {
    die('해당 게시판에 등록할 권한이 없습니다. 관리자에게 권한을 요청하세요.');
}

// 권한이 있으면 상품 등록 폼 표시
?>

<div class="seller-product-form">
    <h2>상품 등록</h2>
    <p>게시판: 
        <?php 
        $boardNames = [
            'mvno' => '알뜰폰',
            'mno' => '통신사폰',
            'internet' => '인터넷'
        ];
        echo htmlspecialchars($boardNames[$boardType] ?? $boardType);
        ?>
    </p>
    
    <!-- 상품 등록 폼 -->
    <form method="POST" action="/MVNO/api/product-register.php">
        <input type="hidden" name="board_type" value="<?php echo htmlspecialchars($boardType); ?>">
        
        <!-- 알뜰폰/통신사폰 공통 필드 -->
        <?php if ($boardType === 'mvno' || $boardType === 'mno'): ?>
            <div class="form-group">
                <label>통신사</label>
                <select name="provider" required>
                    <option value="">선택하세요</option>
                    <option value="kt">KT</option>
                    <option value="skt">SKT</option>
                    <option value="lg">LG U+</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>요금제명</label>
                <input type="text" name="plan_name" required>
            </div>
            
            <div class="form-group">
                <label>월 요금</label>
                <input type="number" name="monthly_fee" required>
            </div>
        <?php endif; ?>
        
        <!-- 인터넷 필드 -->
        <?php if ($boardType === 'internet'): ?>
            <div class="form-group">
                <label>인터넷 업체</label>
                <select name="provider" required>
                    <option value="">선택하세요</option>
                    <option value="kt">KT</option>
                    <option value="skt">SKT</option>
                    <option value="lg">LG U+</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>속도</label>
                <input type="text" name="speed" required>
            </div>
            
            <div class="form-group">
                <label>월 요금</label>
                <input type="number" name="monthly_fee" required>
            </div>
        <?php endif; ?>
        
        <button type="submit">상품 등록</button>
    </form>
</div>











