<?php
/**
 * 1:1 Q&A 목록 페이지
 */
session_start();

// 현재 페이지 설정
$current_page = 'mypage';
$is_main_page = false;

// 헤더 포함
include '../includes/header.php';

// Q&A 함수 포함
require_once '../includes/data/qna-functions.php';

// 사용자 ID 가져오기
$user_id = getCurrentUserId();

// 필터 파라미터 가져오기
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // all, pending, answered

// 페이지네이션 설정
$itemsPerPage = 10; // 한 페이지당 표시할 항목 수
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // 현재 페이지 번호

// Q&A 목록 가져오기 (모든 문의 가져오기 - 답변 완료 포함)
$allQnas = getQnaList($user_id);

// 필터 적용
if ($filter === 'pending') {
    $qnas = array_filter($allQnas, function($qna) {
        $status = isset($qna['status']) ? trim($qna['status']) : '';
        return ($status !== 'answered');
    });
} elseif ($filter === 'answered') {
    $qnas = array_filter($allQnas, function($qna) {
        $status = isset($qna['status']) ? trim($qna['status']) : '';
        return ($status === 'answered');
    });
} else {
    $qnas = $allQnas;
}

// 배열 인덱스 재정렬
$qnas = array_values($qnas);

// 페이지네이션 계산
$totalItems = count($qnas); // 필터링된 전체 항목 수
$totalPages = ceil($totalItems / $itemsPerPage); // 전체 페이지 수
$currentPage = min($currentPage, max(1, $totalPages)); // 유효한 페이지 범위로 제한

// 현재 페이지에 표시할 항목만 추출
$offset = ($currentPage - 1) * $itemsPerPage;
$pagedQnas = array_slice($qnas, $offset, $itemsPerPage);

// 통계 계산
$totalCount = count($allQnas);
$pendingCount = count(array_filter($allQnas, function($qna) {
    $status = isset($qna['status']) ? trim($qna['status']) : '';
    return ($status !== 'answered');
}));
$answeredCount = count(array_filter($allQnas, function($qna) {
    $status = isset($qna['status']) ? trim($qna['status']) : '';
    return ($status === 'answered');
}));
?>

<main class="main-content">
    <div style="width: 100%; max-width: 980px; margin: 0 auto; padding: 20px;" class="qna-container">
        <!-- 페이지 헤더 -->
        <div style="margin-bottom: 32px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h1 style="font-size: 28px; font-weight: bold; margin: 0 0 8px 0;">질문과 답변</h1>
                    <p style="color: #6b7280; font-size: 14px; margin: 0;">궁금한 사항을 질문해주시면 관리자가 답변해드립니다.</p>
                </div>
                <a href="/MVNO/qna/qna-write.php" 
                   style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #6366f1; color: white; border-radius: 8px; text-decoration: none; font-weight: 500; transition: background-color 0.2s;"
                   onmouseover="this.style.background='#4f46e5'" 
                   onmouseout="this.style.background='#6366f1'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    질문하기
                </a>
            </div>
            
            <!-- 필터 탭 -->
            <div style="display: flex; gap: 8px; border-bottom: 2px solid #e5e7eb;">
                <a href="/MVNO/qna/qna.php?filter=all" 
                   style="padding: 12px 20px; text-decoration: none; font-weight: 500; font-size: 14px; color: <?php echo $filter === 'all' ? '#6366f1' : '#6b7280'; ?>; border-bottom: 2px solid <?php echo $filter === 'all' ? '#6366f1' : 'transparent'; ?>; margin-bottom: -2px; transition: all 0.2s;">
                    전체 (<?php echo $totalCount; ?>)
                </a>
                <a href="/MVNO/qna/qna.php?filter=pending" 
                   style="padding: 12px 20px; text-decoration: none; font-weight: 500; font-size: 14px; color: <?php echo $filter === 'pending' ? '#6366f1' : '#6b7280'; ?>; border-bottom: 2px solid <?php echo $filter === 'pending' ? '#6366f1' : 'transparent'; ?>; margin-bottom: -2px; transition: all 0.2s;">
                    답변대기 (<?php echo $pendingCount; ?>)
                </a>
                <a href="/MVNO/qna/qna.php?filter=answered" 
                   style="padding: 12px 20px; text-decoration: none; font-weight: 500; font-size: 14px; color: <?php echo $filter === 'answered' ? '#6366f1' : '#6b7280'; ?>; border-bottom: 2px solid <?php echo $filter === 'answered' ? '#6366f1' : 'transparent'; ?>; margin-bottom: -2px; transition: all 0.2s;">
                    답변완료 (<?php echo $answeredCount; ?>)
                </a>
            </div>
            
            <!-- 페이지네이션 정보 -->
            <?php if ($totalItems > 0): ?>
                <div style="margin-top: 16px; font-size: 14px; color: #6b7280;">
                    전체 <?php echo number_format($totalItems); ?>건 중 <?php echo number_format($offset + 1); ?>-<?php echo number_format(min($offset + $itemsPerPage, $totalItems)); ?>건 표시
                </div>
            <?php endif; ?>
        </div>

        <!-- 에러 메시지 -->
        <?php if (isset($_SESSION['qna_error'])): ?>
            <div style="padding: 16px; background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; margin-bottom: 24px; color: #dc2626;">
                <?php 
                echo htmlspecialchars($_SESSION['qna_error']); 
                unset($_SESSION['qna_error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Q&A 목록 -->
        <?php if (empty($pagedQnas)): ?>
            <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; border: 1px solid #e5e7eb;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin: 0 auto 16px; opacity: 0.5;">
                    <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                    <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p style="color: #9ca3af; font-size: 16px; margin-bottom: 24px;">등록된 질문이 없습니다.</p>
                <a href="/MVNO/qna/qna-write.php" 
                   style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #6366f1; color: white; border-radius: 8px; text-decoration: none; font-weight: 500;">
                    첫 질문 작성하기
                </a>
            </div>
        <?php else: ?>
            <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;">
                <?php foreach ($pagedQnas as $qna): ?>
                    <a href="/MVNO/qna/qna-detail.php?id=<?php echo htmlspecialchars($qna['id']); ?>" 
                       style="display: block; padding: 20px; border-bottom: 1px solid #e5e7eb; text-decoration: none; color: inherit; transition: background-color 0.2s;"
                       onmouseover="this.style.backgroundColor='#f9fafb'" 
                       onmouseout="this.style.backgroundColor='white'">
                        <div style="display: flex; align-items: flex-start; gap: 16px;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                    <?php if (isset($qna['status']) && $qna['status'] == 'answered'): ?>
                                        <span style="display: inline-flex; align-items: center; padding: 4px 8px; background: #d1fae5; color: #059669; border-radius: 4px; font-size: 12px; font-weight: 600;">답변완료</span>
                                    <?php elseif (isset($qna['admin_viewed_at']) && !empty($qna['admin_viewed_at'])): ?>
                                        <span style="display: inline-flex; align-items: center; padding: 4px 8px; background: #dbeafe; color: #1e40af; border-radius: 4px; font-size: 12px; font-weight: 600;">답변대기중</span>
                                    <?php else: ?>
                                        <span style="display: inline-flex; align-items: center; padding: 4px 8px; background: #fef3c7; color: #d97706; border-radius: 4px; font-size: 12px; font-weight: 600;">답변대기</span>
                                    <?php endif; ?>
                                    <h3 style="font-size: 16px; font-weight: 600; margin: 0; color: #111827;">
                                        <?php echo htmlspecialchars($qna['title']); ?>
                                    </h3>
                                </div>
                                <p style="font-size: 14px; color: #6b7280; margin: 0 0 8px 0; line-height: 1.5;">
                                    <?php echo htmlspecialchars(mb_substr($qna['content'], 0, 100)); ?>
                                    <?php echo mb_strlen($qna['content']) > 100 ? '...' : ''; ?>
                                </p>
                                <div style="display: flex; align-items: center; gap: 16px; font-size: 13px; color: #9ca3af;">
                                    <span><?php echo date('Y.m.d', strtotime($qna['created_at'])); ?></span>
                                    <?php if (isset($qna['answered_at'])): ?>
                                        <span>답변일: <?php echo date('Y.m.d', strtotime($qna['answered_at'])); ?></span>
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
            <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 32px;">
                    <!-- 이전 페이지 -->
                    <?php if ($currentPage > 1): ?>
                        <a href="/MVNO/qna/qna.php?filter=<?php echo urlencode($filter); ?>&page=<?php echo $currentPage - 1; ?>" 
                           style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s;"
                           onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1';"
                           onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#374151';">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    <?php else: ?>
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; color: #d1d5db; background: #f9fafb; cursor: not-allowed;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    <?php endif; ?>
                    
                    <!-- 페이지 번호 -->
                    <?php
                    // 페이지 번호 범위 계산 (현재 페이지 기준 앞뒤 2페이지씩 표시)
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    
                    // 첫 페이지 표시
                    if ($startPage > 1): ?>
                        <a href="/MVNO/qna/qna.php?filter=<?php echo urlencode($filter); ?>&page=1" 
                           style="display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s;"
                           onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1';"
                           onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#374151';">
                            1
                        </a>
                        <?php if ($startPage > 2): ?>
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; color: #9ca3af;">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <?php if ($i == $currentPage): ?>
                            <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border: 1px solid #6366f1; border-radius: 8px; color: white; background: #6366f1; font-weight: 600;">
                                <?php echo $i; ?>
                            </span>
                        <?php else: ?>
                            <a href="/MVNO/qna/qna.php?filter=<?php echo urlencode($filter); ?>&page=<?php echo $i; ?>" 
                               style="display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s;"
                               onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1';"
                               onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#374151';">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <!-- 마지막 페이지 표시 -->
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; color: #9ca3af;">...</span>
                        <?php endif; ?>
                        <a href="/MVNO/qna/qna.php?filter=<?php echo urlencode($filter); ?>&page=<?php echo $totalPages; ?>" 
                           style="display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s;"
                           onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1';"
                           onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#374151';">
                            <?php echo $totalPages; ?>
                        </a>
                    <?php endif; ?>
                    
                    <!-- 다음 페이지 -->
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="/MVNO/qna/qna.php?filter=<?php echo urlencode($filter); ?>&page=<?php echo $currentPage + 1; ?>" 
                           style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; color: #374151; background: white; transition: all 0.2s;"
                           onmouseover="this.style.borderColor='#6366f1'; this.style.color='#6366f1';"
                           onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#374151';">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    <?php else: ?>
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 8px; color: #d1d5db; background: #f9fafb; cursor: not-allowed;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
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


















