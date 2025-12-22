<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';

// 로그인 체크 - 로그인하지 않은 경우 회원가입 모달로 리다이렉트
if (!isLoggedIn()) {
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    // 로그인 모달이 있는 홈으로 리다이렉트 (모달 자동 열기)
    header('Location: /MVNO/?show_login=1');
    exit;
}

// 현재 사용자 정보 가져오기
$currentUser = getCurrentUser();
if (!$currentUser) {
    // 세션 정리 후 로그인 페이지로 리다이렉트
    if (isset($_SESSION['logged_in'])) {
        unset($_SESSION['logged_in']);
    }
    if (isset($_SESSION['user_id'])) {
        unset($_SESSION['user_id']);
    }
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: /MVNO/?show_login=1');
    exit;
}

$user_id = $currentUser['user_id'];

// 포인트 설정 로드
require_once '../includes/data/point-settings.php';

// 현재 사용자 포인트 조회
$user_point = getUserPoint($user_id);
$current_balance = $user_point['balance'] ?? 0;
$history = $user_point['history'] ?? [];

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div style="width: 460px; margin: 0 auto; padding: 20px;" class="mypage-container">
        <!-- 페이지 헤더 -->
        <div style="margin-bottom: 24px; padding: 20px 0;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <a href="/MVNO/mypage/mypage.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <h2 style="font-size: 24px; font-weight: bold; margin: 0;">포인트 내역</h2>
            </div>
        </div>

        <!-- 포인트 잔액 카드 -->
        <div style="margin-bottom: 24px; padding: 20px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border-radius: 12px; color: white;">
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">보유 포인트</div>
            <div style="font-size: 32px; font-weight: 700;">
                <?php echo number_format($current_balance); ?>원
            </div>
        </div>

        <!-- 포인트 내역 리스트 -->
        <div style="margin-bottom: 32px;">
            <?php if (empty($history)): ?>
                <div style="text-align: center; padding: 60px 20px; color: #9ca3af;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin: 0 auto 16px; opacity: 0.5;">
                        <path d="M3 10C3 6.22876 3 4.34315 4.17157 3.17157C5.34315 2.34315 7.22876 2.34315 11 2.34315H13C16.7712 2.34315 18.6569 2.34315 19.8284 3.17157C21 4.34315 21 6.22876 21 10V14C21 17.7712 21 19.6569 19.8284 20.8284C18.6569 22 16.7712 22 13 22H11C7.22876 22 5.34315 22 4.17157 20.8284C3 19.6569 3 17.7712 3 14V10Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M7 12H17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M7 16H12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <p style="font-size: 16px; margin: 0;">포인트 사용 내역이 없습니다.</p>
                </div>
            <?php else: 
                // 최신순으로 정렬
                $sorted_history = array_reverse($history);
                $display_count = 10;
                $total_count = count($sorted_history);
                $display_items = array_slice($sorted_history, 0, $display_count);
                $remaining_count = $total_count - $display_count;
            ?>
                <ul style="list-style: none; padding: 0; margin: 0;" id="pointHistoryList">
                    <?php foreach ($display_items as $item): 
                        $is_deduction = $item['type'] !== 'add';
                        $type_labels = [
                            'mvno' => '알뜰폰 신청',
                            'mno' => '통신사폰 신청',
                            'internet' => '인터넷 신청',
                            'add' => '포인트 충전'
                        ];
                        $type_label = $type_labels[$item['type']] ?? '포인트 사용';
                    ?>
                        <li style="border-bottom: 1px solid #e5e7eb;">
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                        <?php if ($is_deduction): ?>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M20 12H4" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        <?php else: ?>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 4V20M4 12H20" stroke="#10b981" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        <?php endif; ?>
                                        <span style="font-size: 15px; font-weight: 600; color: #1f2937;">
                                            <?php echo htmlspecialchars($type_label); ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 13px; color: #6b7280;">
                                        <?php echo htmlspecialchars($item['date']); ?>
                                    </div>
                                    <?php if (!empty($item['description'])): ?>
                                        <div style="font-size: 13px; color: #9ca3af; margin-top: 4px;">
                                            <?php echo htmlspecialchars($item['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 16px; font-weight: 700; color: <?php echo $is_deduction ? '#ef4444' : '#10b981'; ?>; margin-bottom: 4px;">
                                        <?php echo $is_deduction ? '-' : '+'; ?><?php echo number_format($item['amount']); ?>원
                                    </div>
                                    <div style="font-size: 12px; color: #9ca3af;">
                                        잔액: <?php echo number_format($item['balance_after']); ?>원
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if ($remaining_count > 0): ?>
                    <div style="margin-top: 16px; text-align: center;">
                        <button type="button" id="showMorePointHistory" style="padding: 12px 24px; background-color: #6366f1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                            더보기 (<?php echo $remaining_count; ?>개)
                        </button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- 포인트 내역 모달 -->
<div id="pointHistoryModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: white; z-index: 1000; overflow: hidden; width: 100%; height: 100%;">
    <div style="position: relative; width: 100%; height: 100%; display: flex; flex-direction: column; background: white; margin: 0; padding: 0;">
        <!-- 모달 헤더 -->
        <div style="flex-shrink: 0; background: white; padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
            <h3 style="font-size: 20px; font-weight: bold; margin: 0;">포인트 내역 전체</h3>
            <button type="button" id="closePointHistoryModal" style="background: none; border: none; cursor: pointer; padding: 4px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
        <!-- 모달 내용 (스크롤 가능 영역) -->
        <div style="flex: 1; overflow-y: auto; padding: 20px;">
            <ul style="list-style: none; padding: 0; margin: 0;" id="pointHistoryModalList">
                <?php if (!empty($sorted_history)): 
                    // 모달에서도 처음 10개만 표시
                    $modal_display_count = 10;
                    $modal_display_items = array_slice($sorted_history, 0, $modal_display_count);
                    $modal_remaining_count = count($sorted_history) - $modal_display_count;
                ?>
                    <?php foreach ($modal_display_items as $item): 
                        $is_deduction = $item['type'] !== 'add';
                        $type_labels = [
                            'mvno' => '알뜰폰 신청',
                            'mno' => '통신사폰 신청',
                            'internet' => '인터넷 신청',
                            'add' => '포인트 충전'
                        ];
                        $type_label = $type_labels[$item['type']] ?? '포인트 사용';
                    ?>
                        <li style="border-bottom: 1px solid #e5e7eb;" class="modal-history-item">
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                        <?php if ($is_deduction): ?>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M20 12H4" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        <?php else: ?>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 4V20M4 12H20" stroke="#10b981" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        <?php endif; ?>
                                        <span style="font-size: 15px; font-weight: 600; color: #1f2937;">
                                            <?php echo htmlspecialchars($type_label); ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 13px; color: #6b7280;">
                                        <?php echo htmlspecialchars($item['date']); ?>
                                    </div>
                                    <?php if (!empty($item['description'])): ?>
                                        <div style="font-size: 13px; color: #9ca3af; margin-top: 4px;">
                                            <?php echo htmlspecialchars($item['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 16px; font-weight: 700; color: <?php echo $is_deduction ? '#ef4444' : '#10b981'; ?>; margin-bottom: 4px;">
                                        <?php echo $is_deduction ? '-' : '+'; ?><?php echo number_format($item['amount']); ?>원
                                    </div>
                                    <div style="font-size: 12px; color: #9ca3af;">
                                        잔액: <?php echo number_format($item['balance_after']); ?>원
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
            
            <!-- 모달 내 더보기 버튼 -->
            <?php if (!empty($sorted_history) && $modal_remaining_count > 0): ?>
                <div style="margin-top: 16px; text-align: center;">
                    <button type="button" id="showMoreModalHistory" class="plan-review-more-btn" data-total-count="<?php echo count($sorted_history); ?>" data-current-count="<?php echo $modal_display_count; ?>">
                        더보기 (<?php echo $modal_remaining_count > 10 ? 10 : $modal_remaining_count; ?>개)
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// 포인트 내역 데이터를 JavaScript로 전달
const pointHistoryData = <?php echo json_encode($sorted_history ?? []); ?>;

<script>
document.addEventListener('DOMContentLoaded', function() {
    const showMoreBtn = document.getElementById('showMorePointHistory');
    const modal = document.getElementById('pointHistoryModal');
    const closeBtn = document.getElementById('closePointHistoryModal');
    const showMoreModalBtn = document.getElementById('showMoreModalHistory');
    const modalList = document.getElementById('pointHistoryModalList');
    
    let currentDisplayCount = 10;
    const itemsPerPage = 10;
    
    // 타입 라벨 매핑
    const typeLabels = {
        'mvno': '알뜰폰 신청',
        'mno': '통신사폰 신청',
        'internet': '인터넷 신청',
        'add': '포인트 충전'
    };
    
    // 포인트 내역 아이템 생성 함수
    function createHistoryItem(item) {
        const isDeduction = item.type !== 'add';
        const typeLabel = typeLabels[item.type] || '포인트 사용';
        
        return `
            <li style="border-bottom: 1px solid #e5e7eb;" class="modal-history-item">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0;">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            ${isDeduction ? 
                                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 12H4" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/></svg>' :
                                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 4V20M4 12H20" stroke="#10b981" stroke-width="2" stroke-linecap="round"/></svg>'
                            }
                            <span style="font-size: 15px; font-weight: 600; color: #1f2937;">
                                ${typeLabel}
                            </span>
                        </div>
                        <div style="font-size: 13px; color: #6b7280;">
                            ${item.date}
                        </div>
                        ${item.description ? `
                            <div style="font-size: 13px; color: #9ca3af; margin-top: 4px;">
                                ${item.description}
                            </div>
                        ` : ''}
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 16px; font-weight: 700; color: ${isDeduction ? '#ef4444' : '#10b981'}; margin-bottom: 4px;">
                            ${isDeduction ? '-' : '+'}${parseInt(item.amount).toLocaleString()}원
                        </div>
                        <div style="font-size: 12px; color: #9ca3af;">
                            잔액: ${parseInt(item.balance_after).toLocaleString()}원
                        </div>
                    </div>
                </div>
            </li>
        `;
    }
    
    // 모달 내 더보기 버튼 클릭
    if (showMoreModalBtn && modalList) {
        showMoreModalBtn.addEventListener('click', function() {
            const totalCount = parseInt(this.getAttribute('data-total-count'));
            const nextItems = pointHistoryData.slice(currentDisplayCount, currentDisplayCount + itemsPerPage);
            
            // 새로운 아이템 추가
            nextItems.forEach(item => {
                const li = document.createElement('li');
                li.innerHTML = createHistoryItem(item);
                modalList.appendChild(li);
            });
            
            currentDisplayCount += nextItems.length;
            const remaining = totalCount - currentDisplayCount;
            
            // 더보기 버튼 업데이트 또는 제거
            if (remaining > 0) {
                const displayCount = remaining > itemsPerPage ? itemsPerPage : remaining;
                this.textContent = `더보기 (${displayCount}개)`;
            } else {
                this.remove();
            }
        });
    }
    
    // 모달 열기
    if (showMoreBtn) {
        showMoreBtn.addEventListener('click', function() {
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                // 모달 열 때 초기화
                currentDisplayCount = 10;
            }
        });
    }
    
    // 모달 닫기
    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
            closeModal();
        }
    });
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

