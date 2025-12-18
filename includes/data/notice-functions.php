<?php
/**
 * 공지사항 관련 함수
 * DB 기반 데이터 저장
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/db-config.php';

// 공지사항 목록 가져오기
function getNotices($limit = null, $offset = 0) {
    $pdo = getDBConnection();
    if (!$pdo) return [];

    $sql = "SELECT * FROM notices WHERE is_published = 1 ORDER BY created_at DESC";
    if ($limit !== null) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }
    $stmt = $pdo->prepare($sql);
    if ($limit !== null) {
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// 공지사항 상세 가져오기
function getNoticeById($id) {
    $pdo = getDBConnection();
    if (!$pdo) return null;
    $stmt = $pdo->prepare("SELECT * FROM notices WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => (string)$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

// 공지사항 생성 (관리자용)
function createNotice($title, $content, $is_important = false) {
    $notice = [
        'id' => uniqid('notice_'),
        'title' => $title,
        'content' => $content,
        'is_important' => $is_important,
        'is_published' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'views' => 0
    ];

    $pdo = getDBConnection();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("
        INSERT INTO notices (id, title, content, is_important, is_published, views, created_at, updated_at)
        VALUES (:id,:title,:content,:imp,1,0,:ca,:ua)
    ");
    $stmt->execute([
        ':id' => $notice['id'],
        ':title' => $notice['title'],
        ':content' => $notice['content'],
        ':imp' => $notice['is_important'] ? 1 : 0,
        ':ca' => $notice['created_at'],
        ':ua' => $notice['updated_at'],
    ]);
    return $notice;
}

// 공지사항 수정 (관리자용)
function updateNotice($id, $title, $content, $is_important = false, $is_published = true) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("
        UPDATE notices
        SET title = :title,
            content = :content,
            is_important = :imp,
            is_published = :pub,
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':id' => (string)$id,
        ':title' => (string)$title,
        ':content' => (string)$content,
        ':imp' => $is_important ? 1 : 0,
        ':pub' => $is_published ? 1 : 0,
    ]);
    return $stmt->rowCount() > 0;
}

// 공지사항 삭제 (관리자용)
function deleteNotice($id) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("DELETE FROM notices WHERE id = :id");
    $stmt->execute([':id' => (string)$id]);
    return $stmt->rowCount() > 0;
}

// 조회수 증가
function incrementNoticeViews($id) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("UPDATE notices SET views = views + 1 WHERE id = :id");
    $stmt->execute([':id' => (string)$id]);
    return $stmt->rowCount() > 0;
}

// 모든 공지사항 가져오기 (관리자용, 비공개 포함)
function getAllNoticesForAdmin() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    $stmt = $pdo->query("SELECT * FROM notices ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}






