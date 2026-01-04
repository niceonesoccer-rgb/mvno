<?php
/**
 * 카테고리별 태그라인 관리 페이지 (관리자)
 * 경로: /admin/advertisement/tagline.php
 */

// POST 처리는 출력 전에 수행 (헤더 리다이렉트를 위해)
require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/site-settings.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    header('Location: /MVNO/admin/login.php');
    exit;
}

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_taglines'])) {
    $taglines = [
        'home' => [
            'tagline' => trim($_POST['tagline_home'] ?? ''), 
            'link' => trim($_POST['link_home'] ?? ''),
            'effect' => trim($_POST['effect_home'] ?? 'none')
        ],
        'mno-sim' => [
            'tagline' => trim($_POST['tagline_mno-sim'] ?? ''), 
            'link' => trim($_POST['link_mno-sim'] ?? ''),
            'effect' => trim($_POST['effect_mno-sim'] ?? 'none')
        ],
        'mvno' => [
            'tagline' => trim($_POST['tagline_mvno'] ?? ''), 
            'link' => trim($_POST['link_mvno'] ?? ''),
            'effect' => trim($_POST['effect_mvno'] ?? 'none')
        ],
        'mno' => [
            'tagline' => trim($_POST['tagline_mno'] ?? ''), 
            'link' => trim($_POST['link_mno'] ?? ''),
            'effect' => trim($_POST['effect_mno'] ?? 'none')
        ],
        'internets' => [
            'tagline' => trim($_POST['tagline_internets'] ?? ''), 
            'link' => trim($_POST['link_internets'] ?? ''),
            'effect' => trim($_POST['effect_internets'] ?? 'none')
        ],
    ];
    
    if (saveCategoryTaglines($taglines)) {
        $_SESSION['tagline_save_success'] = true;
    } else {
        $_SESSION['tagline_save_error'] = true;
    }
    
    // POST-Redirect-Get 패턴: 저장 후 GET 요청으로 리다이렉트
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 헤더 포함 (출력 시작)
require_once __DIR__ . '/../includes/admin-header.php';

// 세션에서 성공/에러 메시지 확인
$saveSuccess = false;
$saveError = false;

if (isset($_SESSION['tagline_save_success'])) {
    $saveSuccess = true;
    unset($_SESSION['tagline_save_success']);
}

if (isset($_SESSION['tagline_save_error'])) {
    $saveError = true;
    unset($_SESSION['tagline_save_error']);
}

// 태그라인 로드
$categoryTaglines = getCategoryTaglines();
?>

<style>
.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

.form-group input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
}

.form-group input:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.category-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 24px;
}

.category-title {
    font-weight: 600;
    margin-bottom: 16px;
    color: #1f2937;
    font-size: 16px;
}

.input-grid {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr;
    gap: 16px;
}

@media (max-width: 768px) {
    .input-grid {
        grid-template-columns: 1fr;
    }
}

.btn-save {
    padding: 12px 24px;
    background: #6366f1;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: background 0.2s;
}

.btn-save:hover {
    background: #4f46e5;
}

.help-text {
    font-size: 13px;
    color: #6b7280;
    margin-top: 8px;
}

/* 성공 모달 스타일 */
.success-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.success-modal-overlay.show {
    display: flex;
}

.success-modal {
    background: #fff;
    border-radius: 12px;
    padding: 32px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.success-modal-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    background: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: #fff;
}

.success-modal-title {
    font-size: 20px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
}

.success-modal-message {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 24px;
}

.success-modal-btn {
    padding: 10px 24px;
    background: #6366f1;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: background 0.2s;
}

.success-modal-btn:hover {
    background: #4f46e5;
}
</style>

<div class="admin-content-wrapper">
    <div class="admin-content">
        <div class="page-header">
            <h1>카테고리별 태그라인 관리</h1>
            <p>각 카테고리 메뉴에 표시될 태그라인과 링크를 설정할 수 있습니다.</p>
        </div>
        
        <div class="content-box">
            <div style="padding: 24px;">
                <form method="POST">
                    <?php
                    $categories = [
                        'home' => '홈',
                        'mno-sim' => '통신사단독유심',
                        'mvno' => '알뜰폰',
                        'mno' => '통신사폰',
                        'internets' => '인터넷'
                    ];
                    
                    $effectOptions = [
                        'none' => '없음',
                        'blink' => '깜빡거림',
                        'fade' => '페이드',
                        'pulse' => '펄스',
                        'gradient' => '그라데이션',
                        'slide' => '슬라이드',
                        'underline' => '언더라인',
                        'bounce' => '바운스',
                        'slide-right-bounce' => '오른쪽에서 날아와서 바운스',
                        'slide-left-bounce' => '왼쪽에서 날아와서 바운스',
                        'slide-right-char' => '한글자씩 오른쪽에서',
                        'slide-left-char' => '한글자씩 왼쪽에서',
                        'typing' => '타이핑'
                    ];
                    
                    foreach ($categories as $key => $label):
                        $tagline = $categoryTaglines[$key]['tagline'] ?? '';
                        $link = $categoryTaglines[$key]['link'] ?? '';
                        $effect = $categoryTaglines[$key]['effect'] ?? 'none';
                    ?>
                        <div class="category-section">
                            <div class="category-title"><?= htmlspecialchars($label) ?></div>
                            <div class="input-grid">
                                <div class="form-group">
                                    <label for="tagline_<?= htmlspecialchars($key) ?>">태그라인</label>
                                    <input type="text" 
                                           id="tagline_<?= htmlspecialchars($key) ?>" 
                                           name="tagline_<?= htmlspecialchars($key) ?>" 
                                           value="<?= htmlspecialchars($tagline) ?>" 
                                           placeholder="예: 알뜰요금의 리더">
                                    <div class="help-text">헤더 중앙에 표시될 태그라인 텍스트입니다.</div>
                                </div>
                                <div class="form-group">
                                    <label for="link_<?= htmlspecialchars($key) ?>">링크 (선택사항)</label>
                                    <input type="text" 
                                           id="link_<?= htmlspecialchars($key) ?>" 
                                           name="link_<?= htmlspecialchars($key) ?>" 
                                           value="<?= htmlspecialchars($link) ?>" 
                                           placeholder="예: /MVNO/event/event.php">
                                    <div class="help-text">태그라인 클릭 시 이동할 링크입니다. 비워두면 텍스트만 표시됩니다.</div>
                                </div>
                                <div class="form-group">
                                    <label for="effect_<?= htmlspecialchars($key) ?>">효과</label>
                                    <select id="effect_<?= htmlspecialchars($key) ?>" 
                                            name="effect_<?= htmlspecialchars($key) ?>" 
                                            style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                                        <?php foreach ($effectOptions as $effectKey => $effectLabel): ?>
                                            <option value="<?= htmlspecialchars($effectKey) ?>" <?= $effect === $effectKey ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($effectLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="help-text">태그라인에 적용할 시각적 효과를 선택하세요.</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
                        <button type="submit" name="save_taglines" class="btn-save">
                            태그라인 저장
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 성공 모달 -->
<div id="successModal" class="success-modal-overlay">
    <div class="success-modal">
        <div class="success-modal-icon">✓</div>
        <div class="success-modal-title">저장 완료</div>
        <div class="success-modal-message" id="successModalMessage">태그라인이 저장되었습니다.</div>
        <button type="button" class="success-modal-btn" onclick="closeSuccessModal()">확인</button>
    </div>
</div>

<script>
// 저장 결과 확인하여 모달 표시
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($saveSuccess): ?>
        showSuccessModal('태그라인이 저장되었습니다.');
    <?php elseif ($saveError): ?>
        showSuccessModal('태그라인 저장에 실패했습니다.');
    <?php endif; ?>
});

function showSuccessModal(message) {
    const modal = document.getElementById('successModal');
    const messageEl = document.getElementById('successModalMessage');
    
    if (messageEl) {
        messageEl.textContent = message;
    }
    
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// 모달 배경 클릭 시 닫기
document.getElementById('successModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSuccessModal();
    }
});

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSuccessModal();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

