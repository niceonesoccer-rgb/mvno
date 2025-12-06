<?php
/**
 * 공지사항 목록 페이지
 */
session_start();

// 현재 페이지 설정
$current_page = 'mypage';
$is_main_page = false;

// 헤더 포함
include '../includes/header.php';

// 공지사항 함수 포함
require_once '../includes/data/notice-functions.php';

// 페이지네이션
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
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
        <div style="margin-bottom: 32px;">
            <h1 style="font-size: 28px; font-weight: bold; margin: 0 0 8px 0;">공지사항</h1>
            <p style="color: #6b7280; font-size: 14px; margin: 0;">모요의 새로운 소식과 중요한 안내사항을 확인하세요.</p>
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
            <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;">
                <?php foreach ($notices as $notice): ?>
                    <a href="/MVNO/notice/notice-detail.php?id=<?php echo htmlspecialchars($notice['id']); ?>" 
                       style="display: block; padding: 20px; border-bottom: 1px solid #e5e7eb; text-decoration: none; color: inherit; transition: background-color 0.2s;"
                       onmouseover="this.style.backgroundColor='#f9fafb'" 
                       onmouseout="this.style.backgroundColor='white'">
                        <div style="display: flex; align-items: flex-start; gap: 16px;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                    <?php if (isset($notice['is_important']) && $notice['is_important']): ?>
                                        <span style="display: inline-flex; align-items: center; padding: 4px 8px; background: #fee2e2; color: #dc2626; border-radius: 4px; font-size: 12px; font-weight: 600;">중요</span>
                                    <?php endif; ?>
                                    <h3 style="font-size: 16px; font-weight: 600; margin: 0; color: #111827;">
                                        <?php echo htmlspecialchars($notice['title']); ?>
                                    </h3>
                                </div>
                                <p style="font-size: 14px; color: #6b7280; margin: 0 0 8px 0; line-height: 1.5;">
                                    <?php echo htmlspecialchars(mb_substr(strip_tags($notice['content']), 0, 100)); ?>
                                    <?php echo mb_strlen(strip_tags($notice['content'])) > 100 ? '...' : ''; ?>
                                </p>
                                <div style="display: flex; align-items: center; gap: 16px; font-size: 13px; color: #9ca3af;">
                                    <span><?php echo date('Y.m.d', strtotime($notice['created_at'])); ?></span>
                                    <?php if (isset($notice['views'])): ?>
                                        <span>조회 <?php echo number_format($notice['views']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0; color: #9ca3af;">
                                <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- 페이지네이션 -->
            <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 32px;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" 
                           style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s;"
                           onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1'" 
                           onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#374151'">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #6366f1; border-radius: 8px; color: white; background: #6366f1; font-weight: 600;">
                                <?php echo $i; ?>
                            </span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>" 
                               style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s;"
                               onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1'" 
                               onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#374151'">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" 
                           style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s;"
                           onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1'" 
                           onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#374151'">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php
// 푸터 포함
include '../includes/footer.php';
?>



