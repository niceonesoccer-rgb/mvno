<?php
/**
 * 메인 페이지 관리 (관리자용)
 */
session_start();

// 관리자 인증 확인 (실제로는 세션 체크 필요)
// $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
// if (!$is_admin) {
//     header('Location: /MVNO/mypage/mypage.php');
//     exit;
// }

// 현재 페이지 설정
$current_page = 'mypage';
$is_main_page = false;

// 헤더 포함
include '../includes/header.php';

// 함수 포함
require_once '../includes/data/home-functions.php';
require_once '../includes/data/plan-data.php';
require_once '../includes/data/phone-data.php';

// 액션 처리
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'set_main_banners') {
        $event_ids = isset($_POST['main_banners']) && is_array($_POST['main_banners']) 
            ? array_filter($_POST['main_banners']) 
            : [];
        if (setMainBanners($event_ids)) {
            $message = '메인 배너가 설정되었습니다.';
            $message_type = 'success';
        }
    } elseif ($action === 'set_ranking_banners') {
        $event_ids = isset($_POST['ranking_banners']) && is_array($_POST['ranking_banners']) 
            ? array_filter($_POST['ranking_banners']) 
            : [];
        if (setRankingBanners($event_ids)) {
            $message = '랭킹 배너가 설정되었습니다.';
            $message_type = 'success';
        }
    } elseif ($action === 'set_mvno_plans') {
        $plan_ids = isset($_POST['mvno_plans']) && is_array($_POST['mvno_plans']) 
            ? array_filter($_POST['mvno_plans']) 
            : [];
        if (setMvnoPlans($plan_ids)) {
            $message = '알뜰폰 요금제가 설정되었습니다.';
            $message_type = 'success';
        }
    } elseif ($action === 'set_mno_phones') {
        $phone_ids = isset($_POST['mno_phones']) && is_array($_POST['mno_phones']) 
            ? array_filter($_POST['mno_phones']) 
            : [];
        if (setMnoPhones($phone_ids)) {
            $message = '통신사폰이 설정되었습니다.';
            $message_type = 'success';
        }
    } elseif ($action === 'set_internet_products') {
        $product_ids = [];
        if (isset($_POST['internet_products']) && is_array($_POST['internet_products'])) {
            $product_ids = array_filter($_POST['internet_products']);
        } elseif (isset($_POST['internet_products_str'])) {
            // 문자열로 받은 경우 쉼표로 분리
            $product_ids = array_filter(array_map('trim', explode(',', $_POST['internet_products_str'])));
        }
        if (setInternetProducts($product_ids)) {
            $message = '인터넷 상품이 설정되었습니다.';
            $message_type = 'success';
        }
    }
}

// 현재 설정 가져오기
$home_settings = getHomeSettings();
$all_events = getAllEvents();
$all_plans = getPlansData(100);
$all_phones = getPhonesData(100);
?>

<main class="main-content">
    <div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 20px;" class="home-manage-container">
        <!-- 페이지 헤더 -->
        <div style="margin-bottom: 32px;">
            <h1 style="font-size: 28px; font-weight: bold; margin: 0 0 8px 0;">메인 페이지 관리</h1>
            <p style="color: #6b7280; font-size: 14px; margin: 0;">메인 페이지에 표시될 내용을 설정할 수 있습니다.</p>
        </div>

        <!-- 메시지 -->
        <?php if ($message): ?>
            <div style="padding: 16px; background: <?php echo $message_type === 'success' ? '#d1fae5' : '#fee2e2'; ?>; border: 1px solid <?php echo $message_type === 'success' ? '#a7f3d0' : '#fecaca'; ?>; border-radius: 8px; margin-bottom: 24px; color: <?php echo $message_type === 'success' ? '#059669' : '#dc2626'; ?>;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- 메인 배너 설정 (3개) -->
        <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 32px; margin-bottom: 24px;">
            <h2 style="font-size: 20px; font-weight: bold; margin: 0 0 24px 0;">메인 배너 설정 (최대 3개)</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="set_main_banners">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        이벤트 선택 (순서대로 표시됩니다)
                    </label>
                    <?php 
                    $selected_main = $home_settings['main_banners'] ?? [];
                    // 기존 main_banner가 있으면 마이그레이션
                    if (empty($selected_main) && !empty($home_settings['main_banner'])) {
                        $selected_main = [$home_settings['main_banner']];
                    }
                    for ($i = 0; $i < 3; $i++): 
                    ?>
                        <div style="margin-bottom: 12px;">
                            <select name="main_banners[]" style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px;">
                                <option value="">선택 안함</option>
                                <?php foreach ($all_events as $event): ?>
                                    <option value="<?php echo htmlspecialchars($event['id']); ?>" 
                                            <?php echo (isset($selected_main[$i]) && $selected_main[$i] == $event['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endfor; ?>
                </div>
                <button type="submit" style="padding: 12px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer;">
                    저장하기
                </button>
            </form>
        </div>

        <!-- 랭킹 배너 설정 -->
        <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 32px; margin-bottom: 24px;">
            <h2 style="font-size: 20px; font-weight: bold; margin: 0 0 24px 0;">랭킹 배너 설정 (최대 2개)</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="set_ranking_banners">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        이벤트 선택
                    </label>
                    <?php 
                    $selected_rankings = $home_settings['ranking_banners'] ?? [];
                    for ($i = 0; $i < 2; $i++): 
                    ?>
                        <div style="margin-bottom: 12px;">
                            <select name="ranking_banners[]" style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px;">
                                <option value="">선택 안함</option>
                                <?php foreach ($all_events as $event): ?>
                                    <option value="<?php echo htmlspecialchars($event['id']); ?>" 
                                            <?php echo (isset($selected_rankings[$i]) && $selected_rankings[$i] == $event['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endfor; ?>
                </div>
                <button type="submit" style="padding: 12px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer;">
                    저장하기
                </button>
            </form>
        </div>

        <!-- 알뜰폰 요금제 설정 -->
        <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 32px; margin-bottom: 24px;">
            <h2 style="font-size: 20px; font-weight: bold; margin: 0 0 24px 0;">알뜰폰 요금제 설정</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="set_mvno_plans">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        요금제 선택 (여러 개 선택 가능)
                    </label>
                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #d1d5db; border-radius: 8px; padding: 12px;">
                        <?php 
                        $selected_mvno = $home_settings['mvno_plans'] ?? [];
                        foreach ($all_plans as $plan): 
                            $plan_id = $plan['id'] ?? '';
                            $plan_name = ($plan['name'] ?? '') . ' - ' . ($plan['price'] ?? '');
                        ?>
                            <label style="display: flex; align-items: center; gap: 8px; padding: 8px; cursor: pointer; border-radius: 4px; transition: background 0.2s;" 
                                   onmouseover="this.style.background='#f9fafb'" 
                                   onmouseout="this.style.background='transparent'">
                                <input type="checkbox" 
                                       name="mvno_plans[]" 
                                       value="<?php echo htmlspecialchars($plan_id); ?>"
                                       <?php echo in_array($plan_id, $selected_mvno) ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars($plan_name); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" style="padding: 12px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer;">
                    저장하기
                </button>
            </form>
        </div>

        <!-- 통신사폰 설정 -->
        <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 32px; margin-bottom: 24px;">
            <h2 style="font-size: 20px; font-weight: bold; margin: 0 0 24px 0;">통신사폰 설정</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="set_mno_phones">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        통신사폰 선택 (여러 개 선택 가능)
                    </label>
                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #d1d5db; border-radius: 8px; padding: 12px;">
                        <?php 
                        $selected_mno = $home_settings['mno_phones'] ?? [];
                        foreach ($all_phones as $phone): 
                            $phone_id = $phone['id'] ?? '';
                            $phone_name = ($phone['name'] ?? '') . ' - ' . ($phone['price'] ?? '');
                        ?>
                            <label style="display: flex; align-items: center; gap: 8px; padding: 8px; cursor: pointer; border-radius: 4px; transition: background 0.2s;" 
                                   onmouseover="this.style.background='#f9fafb'" 
                                   onmouseout="this.style.background='transparent'">
                                <input type="checkbox" 
                                       name="mno_phones[]" 
                                       value="<?php echo htmlspecialchars($phone_id); ?>"
                                       <?php echo in_array($phone_id, $selected_mno) ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars($phone_name); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" style="padding: 12px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer;">
                    저장하기
                </button>
            </form>
        </div>

        <!-- 인터넷 상품 설정 -->
        <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 32px; margin-bottom: 24px;">
            <h2 style="font-size: 20px; font-weight: bold; margin: 0 0 24px 0;">인터넷 상품 설정</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="set_internet_products">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        인터넷 상품 ID 입력 (쉼표로 구분)
                    </label>
                    <input type="text" 
                           name="internet_products_str" 
                           value="<?php echo htmlspecialchars(implode(', ', $home_settings['internet_products'] ?? [])); ?>"
                           placeholder="예: 26257, 26258, 26259"
                           style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px;">
                    <p style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                        인터넷 상품 ID를 쉼표로 구분하여 입력하세요.
                    </p>
                </div>
                <button type="submit" style="padding: 12px 24px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer;">
                    저장하기
                </button>
            </form>
        </div>
    </div>
</main>

<script>
// 인터넷 상품 폼 처리
document.addEventListener('DOMContentLoaded', function() {
    const internetForm = document.querySelector('form[action=""][method="POST"]:last-of-type');
    if (internetForm && internetForm.querySelector('input[name="internet_products_str"]')) {
        internetForm.addEventListener('submit', function(e) {
            const input = this.querySelector('input[name="internet_products_str"]');
            const values = input.value.split(',').map(v => v.trim()).filter(v => v);
            
            // 숨겨진 input 생성
            values.forEach(function(value) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'internet_products[]';
                hiddenInput.value = value;
                this.appendChild(hiddenInput);
            }.bind(this));
        });
    }
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

