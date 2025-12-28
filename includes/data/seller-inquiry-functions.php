<?php
/**
 * 판매자 1:1 문의 관련 함수
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/db-config.php';

/**
 * 테이블 자동 생성 함수
 */
function ensureSellerInquiryTables() {
    static $checked = false;
    if ($checked) return true;
    
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        // seller_inquiries 테이블 생성
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `seller_inquiries` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 user_id',
                `title` VARCHAR(255) NOT NULL COMMENT '제목',
                `content` TEXT NOT NULL COMMENT '내용',
                `status` ENUM('pending', 'answered', 'closed') NOT NULL DEFAULT 'pending' COMMENT '상태: pending=답변대기, answered=답변완료, closed=확인완료',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '작성일시',
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
                `answered_at` DATETIME DEFAULT NULL COMMENT '답변 작성 시간',
                `answered_by` VARCHAR(50) DEFAULT NULL COMMENT '답변한 관리자 user_id',
                `admin_viewed_at` DATETIME DEFAULT NULL COMMENT '관리자 확인 시간',
                `admin_viewed_by` VARCHAR(50) DEFAULT NULL COMMENT '확인한 관리자 user_id',
                PRIMARY KEY (`id`),
                KEY `idx_seller_id` (`seller_id`),
                KEY `idx_status` (`status`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='판매자 1:1 문의'
        ");
        
        // admin_viewed_at, admin_viewed_by 컬럼 추가 (기존 테이블에 있을 경우)
        $stmt = $pdo->query("SHOW COLUMNS FROM seller_inquiries LIKE 'admin_viewed_at'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `seller_inquiries` ADD COLUMN `admin_viewed_at` DATETIME DEFAULT NULL COMMENT '관리자 확인 시간' AFTER `answered_by`");
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM seller_inquiries LIKE 'admin_viewed_by'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `seller_inquiries` ADD COLUMN `admin_viewed_by` VARCHAR(50) DEFAULT NULL COMMENT '확인한 관리자 user_id' AFTER `admin_viewed_at`");
        }
        
        // seller_inquiry_replies 테이블 생성
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `seller_inquiry_replies` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `inquiry_id` INT(11) UNSIGNED NOT NULL COMMENT '문의 ID',
                `reply_type` ENUM('seller', 'admin') NOT NULL COMMENT '작성자 구분: seller=판매자, admin=관리자',
                `content` TEXT NOT NULL COMMENT '답변 내용',
                `created_by` VARCHAR(50) NOT NULL COMMENT '작성자 user_id',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '작성일시',
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
                PRIMARY KEY (`id`),
                KEY `idx_inquiry_id` (`inquiry_id`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='판매자 1:1 문의 답변'
        ");
        
        // seller_inquiry_attachments 테이블 생성
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `seller_inquiry_attachments` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `inquiry_id` INT(11) UNSIGNED NOT NULL COMMENT '문의 ID',
                `reply_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '답변 ID (NULL이면 문의에 첨부, 있으면 답변에 첨부)',
                `file_name` VARCHAR(255) NOT NULL COMMENT '원본 파일명',
                `file_path` VARCHAR(500) NOT NULL COMMENT '저장 경로',
                `file_size` INT(11) UNSIGNED NOT NULL COMMENT '파일 크기 (bytes)',
                `file_type` VARCHAR(100) NOT NULL COMMENT 'MIME 타입',
                `uploaded_by` VARCHAR(50) NOT NULL COMMENT '업로드한 사용자 user_id',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '업로드일시',
                PRIMARY KEY (`id`),
                KEY `idx_inquiry_id` (`inquiry_id`),
                KEY `idx_reply_id` (`reply_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='판매자 1:1 문의 첨부파일'
        ");
        
        $checked = true;
        return true;
    } catch (PDOException $e) {
        error_log('ensureSellerInquiryTables error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 판매자 문의 작성
 */
function createSellerInquiry($sellerId, $title, $content, $attachments = []) {
    ensureSellerInquiryTables();
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        $pdo->beginTransaction();
        
        // 문의 등록
        $stmt = $pdo->prepare("
            INSERT INTO seller_inquiries (seller_id, title, content, status, created_at, updated_at)
            VALUES (:seller_id, :title, :content, 'pending', NOW(), NOW())
        ");
        $stmt->execute([
            ':seller_id' => $sellerId,
            ':title' => $title,
            ':content' => $content
        ]);
        
        $inquiryId = $pdo->lastInsertId();
        
        // 첨부파일 등록
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $stmt = $pdo->prepare("
                    INSERT INTO seller_inquiry_attachments 
                    (inquiry_id, file_name, file_path, file_size, file_type, uploaded_by, created_at)
                    VALUES (:inquiry_id, :file_name, :file_path, :file_size, :file_type, :uploaded_by, NOW())
                ");
                $stmt->execute([
                    ':inquiry_id' => $inquiryId,
                    ':file_name' => $attachment['file_name'],
                    ':file_path' => $attachment['file_path'],
                    ':file_size' => $attachment['file_size'],
                    ':file_type' => $attachment['file_type'],
                    ':uploaded_by' => $sellerId
                ]);
            }
        }
        
        $pdo->commit();
        return $inquiryId;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('createSellerInquiry error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 판매자 문의 수정 (답변 전이고 관리자가 확인하지 않은 경우만 가능)
 */
function updateSellerInquiry($inquiryId, $sellerId, $title, $content, $attachments = []) {
    ensureSellerInquiryTables();
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        // 문의 존재 및 권한 확인
        $inquiry = getSellerInquiryById($inquiryId);
        if (!$inquiry || $inquiry['seller_id'] !== $sellerId) {
            return false;
        }
        
        // 답변 전인지 확인 (status가 'pending'이어야 함)
        if ($inquiry['status'] !== 'pending') {
            return false; // 답변 후에는 수정 불가
        }
        
        // 관리자가 확인했는지 확인
        if (!empty($inquiry['admin_viewed_at'])) {
            return false; // 관리자가 확인하면 수정 불가
        }
        
        $pdo->beginTransaction();
        
        // 문의 수정
        $stmt = $pdo->prepare("
            UPDATE seller_inquiries 
            SET title = :title, content = :content, updated_at = NOW()
            WHERE id = :id AND seller_id = :seller_id AND status = 'pending'
        ");
        $stmt->execute([
            ':id' => $inquiryId,
            ':seller_id' => $sellerId,
            ':title' => $title,
            ':content' => $content
        ]);
        
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            return false;
        }
        
        // 기존 첨부파일 삭제
        $stmt = $pdo->prepare("
            SELECT file_path FROM seller_inquiry_attachments 
            WHERE inquiry_id = :inquiry_id AND reply_id IS NULL
        ");
        $stmt->execute([':inquiry_id' => $inquiryId]);
        $oldAttachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($oldAttachments as $old) {
            $filePath = __DIR__ . '/../..' . $old['file_path'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        
        $stmt = $pdo->prepare("
            DELETE FROM seller_inquiry_attachments 
            WHERE inquiry_id = :inquiry_id AND reply_id IS NULL
        ");
        $stmt->execute([':inquiry_id' => $inquiryId]);
        
        // 새 첨부파일 등록
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $stmt = $pdo->prepare("
                    INSERT INTO seller_inquiry_attachments 
                    (inquiry_id, file_name, file_path, file_size, file_type, uploaded_by, created_at)
                    VALUES (:inquiry_id, :file_name, :file_path, :file_size, :file_type, :uploaded_by, NOW())
                ");
                $stmt->execute([
                    ':inquiry_id' => $inquiryId,
                    ':file_name' => $attachment['file_name'],
                    ':file_path' => $attachment['file_path'],
                    ':file_size' => $attachment['file_size'],
                    ':file_type' => $attachment['file_type'],
                    ':uploaded_by' => $sellerId
                ]);
            }
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('updateSellerInquiry error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 판매자 문의 삭제 (답변 전이고 관리자가 확인하지 않은 경우만 가능)
 */
function deleteSellerInquiry($inquiryId, $sellerId) {
    ensureSellerInquiryTables();
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        // 문의 존재 및 권한 확인
        $inquiry = getSellerInquiryById($inquiryId);
        if (!$inquiry || $inquiry['seller_id'] !== $sellerId) {
            return false;
        }
        
        // 답변 전인지 확인
        if ($inquiry['status'] !== 'pending') {
            return false; // 답변 후에는 삭제 불가
        }
        
        // 관리자가 확인했는지 확인
        if (!empty($inquiry['admin_viewed_at'])) {
            return false; // 관리자가 확인하면 삭제 불가
        }
        
        $pdo->beginTransaction();
        
        // 첨부파일 삭제
        $stmt = $pdo->prepare("
            SELECT file_path FROM seller_inquiry_attachments 
            WHERE inquiry_id = :inquiry_id
        ");
        $stmt->execute([':inquiry_id' => $inquiryId]);
        $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($attachments as $attachment) {
            $filePath = __DIR__ . '/../..' . $attachment['file_path'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        
        // 문의 삭제 (CASCADE로 답변과 첨부파일도 자동 삭제)
        $stmt = $pdo->prepare("
            DELETE FROM seller_inquiries 
            WHERE id = :id AND seller_id = :seller_id AND status = 'pending'
        ");
        $stmt->execute([
            ':id' => $inquiryId,
            ':seller_id' => $sellerId
        ]);
        
        $pdo->commit();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('deleteSellerInquiry error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 판매자 문의 조회 (ID로)
 */
function getSellerInquiryById($inquiryId) {
    ensureSellerInquiryTables();
    $pdo = getDBConnection();
    if (!$pdo) return null;
    
    $stmt = $pdo->prepare("
        SELECT i.*, u.name as seller_name
        FROM seller_inquiries i
        LEFT JOIN users u ON i.seller_id = u.user_id
        WHERE i.id = :id
    ");
    $stmt->execute([':id' => $inquiryId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * 관리자가 문의 확인 처리
 */
function markSellerInquiryAsViewedByAdmin($inquiryId, $adminId) {
    ensureSellerInquiryTables();
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        // 이미 확인했는지 체크
        $inquiry = getSellerInquiryById($inquiryId);
        if (!$inquiry || !empty($inquiry['admin_viewed_at'])) {
            return true; // 이미 확인했거나 문의가 없으면 true 반환
        }
        
        $stmt = $pdo->prepare("
            UPDATE seller_inquiries 
            SET admin_viewed_at = NOW(), admin_viewed_by = :admin_id
            WHERE id = :inquiry_id AND admin_viewed_at IS NULL
        ");
        $stmt->execute([
            ':inquiry_id' => $inquiryId,
            ':admin_id' => $adminId
        ]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('markSellerInquiryAsViewedByAdmin error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 판매자 문의 목록 조회 (판매자용)
 */
function getSellerInquiriesBySeller($sellerId, $limit = null, $offset = 0) {
    ensureSellerInquiryTables();
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    $sql = "
        SELECT i.*, 
               (SELECT COUNT(*) FROM seller_inquiry_replies WHERE inquiry_id = i.id) as reply_count,
               (SELECT COUNT(*) FROM seller_inquiry_attachments WHERE inquiry_id = i.id AND reply_id IS NULL) as attachment_count
        FROM seller_inquiries i
        WHERE i.seller_id = :seller_id
        ORDER BY i.created_at DESC
    ";
    
    if ($limit !== null) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':seller_id', $sellerId, PDO::PARAM_STR);
    if ($limit !== null) {
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * 모든 판매자 문의 목록 조회 (관리자용)
 */
function getAllSellerInquiries($status = null, $sellerId = null, $limit = null, $offset = 0) {
    ensureSellerInquiryTables();
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    $sql = "
        SELECT i.*, u.name as seller_name,
               (SELECT COUNT(*) FROM seller_inquiry_replies WHERE inquiry_id = i.id) as reply_count,
               (SELECT COUNT(*) FROM seller_inquiry_attachments WHERE inquiry_id = i.id AND reply_id IS NULL) as attachment_count
        FROM seller_inquiries i
        LEFT JOIN users u ON i.seller_id = u.user_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($status) {
        $sql .= " AND i.status = :status";
        $params[':status'] = $status;
    }
    
    if ($sellerId) {
        $sql .= " AND i.seller_id = :seller_id";
        $params[':seller_id'] = $sellerId;
    }
    
    $sql .= " ORDER BY i.created_at DESC";
    
    if ($limit !== null) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    if ($limit !== null) {
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * 문의 답변 목록 조회
 */
function getSellerInquiryReplies($inquiryId) {
    ensureSellerInquiryTables();
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as author_name, u.role as author_role
        FROM seller_inquiry_replies r
        LEFT JOIN users u ON r.created_by = u.user_id
        WHERE r.inquiry_id = :inquiry_id
        ORDER BY r.created_at ASC
    ");
    $stmt->execute([':inquiry_id' => $inquiryId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * 관리자 답변 작성
 */
function createSellerInquiryReply($inquiryId, $adminId, $content, $attachments = []) {
    ensureSellerInquiryTables();
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        $pdo->beginTransaction();
        
        // 답변 등록
        $stmt = $pdo->prepare("
            INSERT INTO seller_inquiry_replies (inquiry_id, reply_type, content, created_by, created_at, updated_at)
            VALUES (:inquiry_id, 'admin', :content, :created_by, NOW(), NOW())
        ");
        $stmt->execute([
            ':inquiry_id' => $inquiryId,
            ':content' => $content,
            ':created_by' => $adminId
        ]);
        
        $replyId = $pdo->lastInsertId();
        
        // 첨부파일 등록
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $stmt = $pdo->prepare("
                    INSERT INTO seller_inquiry_attachments 
                    (inquiry_id, reply_id, file_name, file_path, file_size, file_type, uploaded_by, created_at)
                    VALUES (:inquiry_id, :reply_id, :file_name, :file_path, :file_size, :file_type, :uploaded_by, NOW())
                ");
                $stmt->execute([
                    ':inquiry_id' => $inquiryId,
                    ':reply_id' => $replyId,
                    ':file_name' => $attachment['file_name'],
                    ':file_path' => $attachment['file_path'],
                    ':file_size' => $attachment['file_size'],
                    ':file_type' => $attachment['file_type'],
                    ':uploaded_by' => $adminId
                ]);
            }
        }
        
        // 문의 상태를 'answered'로 변경 (관리자가 답변하면 수정 불가)
        $stmt = $pdo->prepare("
            UPDATE seller_inquiries 
            SET status = 'answered', answered_at = NOW(), answered_by = :answered_by, updated_at = NOW()
            WHERE id = :inquiry_id
        ");
        $stmt->execute([
            ':inquiry_id' => $inquiryId,
            ':answered_by' => $adminId
        ]);
        
        $pdo->commit();
        return $replyId;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('createSellerInquiryReply error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 판매자 확인 처리 (answered -> closed)
 */
function markSellerInquiryAsClosed($inquiryId, $sellerId) {
    ensureSellerInquiryTables();
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    // 권한 확인
    $inquiry = getSellerInquiryById($inquiryId);
    if (!$inquiry || $inquiry['seller_id'] !== $sellerId) {
        return false;
    }
    
    // answered 상태만 closed로 변경 가능
    if ($inquiry['status'] !== 'answered') {
        return false;
    }
    
    $stmt = $pdo->prepare("
        UPDATE seller_inquiries 
        SET status = 'closed', updated_at = NOW()
        WHERE id = :id AND seller_id = :seller_id AND status = 'answered'
    ");
    $stmt->execute([
        ':id' => $inquiryId,
        ':seller_id' => $sellerId
    ]);
    
    return $stmt->rowCount() > 0;
}

/**
 * 문의 첨부파일 목록 조회
 */
function getSellerInquiryAttachments($inquiryId, $replyId = null) {
    ensureSellerInquiryTables();
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    if ($replyId) {
        $stmt = $pdo->prepare("
            SELECT * FROM seller_inquiry_attachments 
            WHERE inquiry_id = :inquiry_id AND reply_id = :reply_id
            ORDER BY created_at ASC
        ");
        $stmt->execute([
            ':inquiry_id' => $inquiryId,
            ':reply_id' => $replyId
        ]);
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM seller_inquiry_attachments 
            WHERE inquiry_id = :inquiry_id AND reply_id IS NULL
            ORDER BY created_at ASC
        ");
        $stmt->execute([':inquiry_id' => $inquiryId]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * 첨부파일 업로드 처리
 */
function uploadSellerInquiryAttachment($file, $inquiryId, $userId) {
    error_log("uploadSellerInquiryAttachment called - inquiryId: $inquiryId, userId: $userId");
    
    if (!isset($file)) {
        error_log("uploadSellerInquiryAttachment: file not set");
        return null;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("uploadSellerInquiryAttachment: upload error - " . $file['error']);
        return null;
    }
    
    error_log("uploadSellerInquiryAttachment: file name - " . $file['name'] . ", size: " . $file['size']);
    
    // 파일 타입 확인
    $allowedTypes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/x-hwp', 'application/haansofthwp', 'application/x-tika-msoffice'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    error_log("uploadSellerInquiryAttachment: detected mime type - $mimeType");
    
    if (!in_array($mimeType, $allowedTypes)) {
        error_log("uploadSellerInquiryAttachment: mime type not allowed - $mimeType");
        return null;
    }
    
    // 파일 크기 확인 (10MB)
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        error_log("uploadSellerInquiryAttachment: file too large - " . $file['size']);
        return null;
    }
    
    // 업로드 디렉토리 생성
    $uploadDir = __DIR__ . '/../../uploads/seller-inquiries/' . $inquiryId . '/';
    error_log("uploadSellerInquiryAttachment: upload directory - $uploadDir");
    
    if (!is_dir($uploadDir)) {
        $mkdirResult = mkdir($uploadDir, 0755, true);
        error_log("uploadSellerInquiryAttachment: mkdir result - " . ($mkdirResult ? 'success' : 'failed'));
        if (!$mkdirResult) {
            error_log("uploadSellerInquiryAttachment: failed to create directory - $uploadDir");
            return null;
        }
    }
    
    // 파일명 생성
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = date('YmdHis') . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    error_log("uploadSellerInquiryAttachment: target file path - $filePath");
    error_log("uploadSellerInquiryAttachment: temp file path - " . $file['tmp_name']);
    error_log("uploadSellerInquiryAttachment: temp file exists - " . (file_exists($file['tmp_name']) ? 'yes' : 'no'));
    
    // 파일 이동
    $moveResult = move_uploaded_file($file['tmp_name'], $filePath);
    error_log("uploadSellerInquiryAttachment: move_uploaded_file result - " . ($moveResult ? 'success' : 'failed'));
    
    if ($moveResult) {
        $fileExists = file_exists($filePath);
        $fileSize = $fileExists ? filesize($filePath) : 0;
        error_log("uploadSellerInquiryAttachment: file exists after move - " . ($fileExists ? 'yes' : 'no') . ", size: $fileSize");
        
        $result = [
            'file_name' => $file['name'],
            'file_path' => '/MVNO/uploads/seller-inquiries/' . $inquiryId . '/' . $fileName,
            'file_size' => $file['size'],
            'file_type' => $mimeType
        ];
        error_log("uploadSellerInquiryAttachment: returning result - " . json_encode($result));
        return $result;
    } else {
        error_log("uploadSellerInquiryAttachment: move_uploaded_file failed");
        if (file_exists($file['tmp_name'])) {
            error_log("uploadSellerInquiryAttachment: temp file still exists");
        }
    }
    
    return null;
}

/**
 * 답변 첨부파일 업로드 처리
 */
function uploadSellerInquiryReplyAttachment($file, $inquiryId, $replyId, $userId) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // 파일 타입 확인
    $allowedTypes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/x-hwp', 'application/haansofthwp', 'application/x-tika-msoffice'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return null;
    }
    
    // 파일 크기 확인 (10MB)
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return null;
    }
    
    // 업로드 디렉토리 생성
    $uploadDir = __DIR__ . '/../../uploads/seller-inquiries/' . $inquiryId . '/replies/' . $replyId . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // 파일명 생성
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = date('YmdHis') . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    // 파일 이동
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return [
            'file_name' => $file['name'],
            'file_path' => '/MVNO/uploads/seller-inquiries/' . $inquiryId . '/replies/' . $replyId . '/' . $fileName,
            'file_size' => $file['size'],
            'file_type' => $mimeType
        ];
    }
    
    return null;
}

