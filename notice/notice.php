<?php
/**
 * 공지사항 목록 페이지
 */
session_start();

// 현재 페이지 설정
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 헤더 포함
include '../includes/header.php';

// 공지사항 함수 포함
require_once '../includes/data/notice-functions.php';
require_once '../includes/data/auth-functions.php';

// 관리자 여부 확인
$isAdmin = isAdmin();

// 페이지네이션
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($per_page, [10, 20, 50, 100])) {
    $per_page = 10;
}
$offset = ($page - 1) * $per_page;

// 공지사항 목록 가져오기
$all_notices = getNotices();
$total = count($all_notices);
$total_pages = ceil($total / $per_page);
$notices = array_slice($all_notices, $offset, $per_page);
?>

<main class="main-content">
    <div style="width: 100%; max-width: 980px; margin: 0 auto; padding: 20px;" class="notice-container">
        <!-- 페이지 헤더 -->
        <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-size: 28px; font-weight: bold; margin: 0 0 8px 0;">공지사항</h1>
                <p style="color: #6b7280; font-size: 14px; margin: 0;">유심킹의 새로운 소식과 중요한 안내사항을 확인하세요.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="color: #6b7280; font-size: 14px;">총 <?php echo number_format($total); ?>개</span>
                <select id="per_page_select" onchange="changePerPage()" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; cursor: pointer; color: #374151;">
                    <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10개씩 보기</option>
                    <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20개씩 보기</option>
                    <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50개씩 보기</option>
                    <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100개씩 보기</option>
                </select>
            </div>
        </div>

        <!-- 공지사항 목록 -->
        <?php if (empty($notices)): ?>
            <div style="text-align: center; padding: 60px 20px;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin: 0 auto 16px; opacity: 0.5;">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p style="color: #9ca3af; font-size: 16px;">등록된 공지사항이 없습니다.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <?php foreach ($notices as $notice): 
                    $has_image = !empty($notice['image_url']);
                    $has_content = !empty($notice['content']);
                ?>
                    <a href="/MVNO/notice/notice-detail.php?id=<?php echo htmlspecialchars($notice['id']); ?>" 
                       style="display: block; background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; text-decoration: none; color: inherit; transition: all 0.2s; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);"
                       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.15)';" 
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0, 0, 0, 0.1)';">
                        <?php if ($has_image): ?>
                            <!-- 케이스 1: 이미지만 있을 때 / 케이스 3: 이미지와 텍스트 둘 다 있을 때 -->
                            <!-- 이미지 영역 -->
                            <div style="width: 100%; overflow: hidden; position: relative; background: #f3f4f6;">
                                <img src="<?php echo htmlspecialchars($notice['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($notice['title']); ?>" 
                                     class="notice-image"
                                     style="width: 100%; height: auto; display: block; object-fit: contain; object-position: top;"
                                     onload="adjustImageDisplay(this)">
                            </div>
                            
                            <!-- 텍스트 영역 -->
                            <div style="padding: 16px;">
                                <?php if ($has_content): ?>
                                    <!-- 본문 텍스트 (이미지와 텍스트 둘 다 있을 때만 표시) -->
                                    <p style="font-size: 14px; color: #6b7280; margin: 0 0 12px 0; line-height: 1.5;">
                                        <?php echo htmlspecialchars(mb_substr(strip_tags($notice['content']), 0, 150)); ?>
                                        <?php echo mb_strlen(strip_tags($notice['content'])) > 150 ? '...' : ''; ?>
                                    </p>
                                <?php endif; ?>
                                <!-- 제목과 날짜 -->
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                                    <h3 style="font-size: 16px; font-weight: 600; margin: 0; color: #111827; line-height: 1.4; flex: 1;">
                                        <?php echo htmlspecialchars($notice['title']); ?>
                                    </h3>
                                    <span style="font-size: 13px; color: #9ca3af; white-space: nowrap; flex-shrink: 0;">
                                        <?php echo date('Y.m.d', strtotime($notice['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- 케이스 2: 텍스트만 있을 때 -->
                            <?php if ($has_content): ?>
                                <!-- 본문 텍스트 영역 (그라데이션 배경) -->
                                <div style="width: 100%; padding: 40px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 200px;">
                                    <p style="font-size: 15px; margin: 0; color: rgba(255, 255, 255, 0.9); line-height: 1.6; word-wrap: break-word; word-break: break-word; overflow-wrap: break-word; white-space: normal;">
                                        <?php echo nl2br(htmlspecialchars(strip_tags($notice['content']))); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            <!-- 제목과 날짜 영역 (흰색 배경) -->
                            <div style="padding: 16px; background: white;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                                    <h3 style="font-size: 16px; font-weight: 600; margin: 0; color: #111827; line-height: 1.4; flex: 1;">
                                        <?php echo htmlspecialchars($notice['title']); ?>
                                    </h3>
                                    <span style="font-size: 13px; color: #9ca3af; white-space: nowrap; flex-shrink: 0;">
                                        <?php echo date('Y.m.d', strtotime($notice['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- 페이지네이션 -->
            <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 32px; flex-wrap: wrap;">
                    <!-- 이전 버튼 -->
                    <a href="?page=<?php echo max(1, $page - 1); ?>&per_page=<?php echo $per_page; ?>" 
                       style="display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s; font-size: 14px; font-weight: 500; <?php echo $page <= 1 ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : ''; ?>"
                       onmouseover="<?php echo $page > 1 ? "this.style.borderColor='#6366f1'; this.style.color='#6366f1'" : ''; ?>" 
                       onmouseout="<?php echo $page > 1 ? "this.style.borderColor='#e5e7eb'; this.style.color='#374151'" : ''; ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 4px;">
                            <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        이전
                    </a>
                    
                    <!-- 페이지 번호 -->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($total_pages, $page + 2);
                    
                    // 첫 페이지 표시
                    if ($startPage > 1) {
                        echo '<a href="?page=1&per_page=' . $per_page . '" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s; font-size: 14px;" onmouseover="this.style.borderColor=\'#6366f1\'; this.style.color=\'#6366f1\'" onmouseout="this.style.borderColor=\'#e5e7eb\'; this.style.color=\'#374151\'">1</a>';
                        if ($startPage > 2) {
                            echo '<span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; color: #9ca3af; font-size: 14px;">...</span>';
                        }
                    }
                    
                    // 페이지 번호 표시
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <?php if ($i == $page): ?>
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #6366f1; border-radius: 8px; color: white; background: #6366f1; font-weight: 600; font-size: 14px;">
                                <?php echo $i; ?>
                            </span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&per_page=<?php echo $per_page; ?>" 
                               style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s; font-size: 14px;"
                               onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1'" 
                               onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#374151'">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php
                    endfor;
                    
                    // 마지막 페이지 표시
                    if ($endPage < $total_pages) {
                        if ($endPage < $total_pages - 1) {
                            echo '<span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; color: #9ca3af; font-size: 14px;">...</span>';
                        }
                        echo '<a href="?page=' . $total_pages . '&per_page=' . $per_page . '" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s; font-size: 14px;" onmouseover="this.style.borderColor=\'#6366f1\'; this.style.color=\'#6366f1\'" onmouseout="this.style.borderColor=\'#e5e7eb\'; this.style.color=\'#374151\'">' . $total_pages . '</a>';
                    }
                    ?>
                    
                    <!-- 다음 버튼 -->
                    <a href="?page=<?php echo min($total_pages, $page + 1); ?>&per_page=<?php echo $per_page; ?>" 
                       style="display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s; font-size: 14px; font-weight: 500; <?php echo $page >= $total_pages ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : ''; ?>"
                       onmouseover="<?php echo $page < $total_pages ? "this.style.borderColor='#6366f1'; this.style.color='#6366f1'" : ''; ?>" 
                       onmouseout="<?php echo $page < $total_pages ? "this.style.borderColor='#e5e7eb'; this.style.color='#374151'" : ''; ?>">
                        다음
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-left: 4px;">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<script>
function changePerPage() {
    const perPage = document.getElementById('per_page_select').value;
    const params = new URLSearchParams(window.location.search);
    
    params.set('per_page', perPage);
    params.set('page', '1'); // 첫 페이지로 이동
    
    window.location.href = '?' + params.toString();
}

function adjustImageDisplay(img) {
    // 가로만 맞추고 원본 비율 유지
    // 세로가 짧으면 가로에 맞춰서 자연스럽게 표시
    img.style.width = '100%';
    img.style.height = 'auto';
    img.style.objectFit = 'contain';
    img.style.objectPosition = 'top';
}

// 페이지 로드 시 모든 이미지에 대해 적용
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('.notice-image');
    images.forEach(function(img) {
        if (img.complete) {
            adjustImageDisplay(img);
        } else {
            img.addEventListener('load', function() {
                adjustImageDisplay(this);
            });
        }
    });
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>


















