<?php
/**
 * 공지사항 디렉토리 인덱스
 * notice.php로 리다이렉트
 */

// target 파라미터가 있으면 유지
$target = isset($_GET['target']) ? '?target=' . urlencode($_GET['target']) : '';
$page = isset($_GET['page']) ? ($target ? '&' : '?') . 'page=' . (int)$_GET['page'] : '';
$per_page = isset($_GET['per_page']) ? ($target || $page ? '&' : '?') . 'per_page=' . (int)$_GET['per_page'] : '';

// notice.php로 리다이렉트
header('Location: /MVNO/notice/notice.php' . $target . $page . $per_page);
exit;

