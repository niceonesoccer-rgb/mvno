<?php
require_once __DIR__ . '/../../includes/data/auth-functions.php';

$isLoggedIn = isLoggedIn();
$currentUser = $isLoggedIn ? getCurrentUser() : null;
?>

<?php if ($isLoggedIn && $currentUser): ?>
    <!-- 로그인된 경우: 사용자 메뉴 -->
    <div class="user-menu">
        <a href="/MVNO/mypage/mypage.php" class="user-menu-link">
            <span><?php echo htmlspecialchars($currentUser['name'] ?? '사용자'); ?>님</span>
        </a>
    </div>
<?php else: ?>
    <!-- 로그인되지 않은 경우: 로그인 버튼 -->
    <button class="login-btn" onclick="openLoginModal(false)">
        로그인
    </button>
<?php endif; ?>

<style>
.login-btn {
    padding: 8px 16px;
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.login-btn:hover {
    background: #4f46e5;
}

.user-menu {
    display: flex;
    align-items: center;
}

.user-menu-link {
    padding: 8px 16px;
    color: #1f2937;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: color 0.2s;
}

.user-menu-link:hover {
    color: #6366f1;
}
</style>
