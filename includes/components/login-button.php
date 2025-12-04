<?php
/**
 * 로그인 버튼 컴포넌트
 */

require_once __DIR__ . '/../data/auth-functions.php';

$currentUser = getCurrentUser();
?>

<?php if (isLoggedIn() && $currentUser): ?>
    <!-- 로그인된 사용자 메뉴 -->
    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 14px; color: #374151;">
            <?php echo htmlspecialchars($currentUser['name'] ?? $currentUser['user_id']); ?>님
        </span>
        <a href="/MVNO/mypage/mypage.php" style="padding: 8px 16px; background: #6366f1; color: white; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500;">
            마이페이지
        </a>
        <a href="/MVNO/api/logout.php" style="padding: 8px 16px; background: #f3f4f6; color: #374151; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500;">
            로그아웃
        </a>
    </div>
<?php endif; ?>

