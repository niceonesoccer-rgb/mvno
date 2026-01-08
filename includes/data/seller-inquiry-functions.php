<?php
/**
 * 판매자 1:1 문의 관련 함수
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/db-config.php';

/**
 * 이미지 리사이징 및 압축 함수 (5MB 이하로 자동 축소)
 */
function compressImage($sourcePath, $targetPath, $maxSizeMB = 5) {
    $maxSizeBytes = $maxSizeMB * 1024 * 1024;
    
    // 파일 크기 확인
    $fileSize = filesize($sourcePath);
    if ($fileSize <= $maxSizeBytes) {
        // 이미 목표 크기 이하이면 그대로 복사
        return copy($sourcePath, $targetPath);
    }
    
    // 이미지 정보 가져오기
    $imageInfo = getimagesize($sourcePath);
    if ($imageInfo === false) {
        return false;
    }
    
    $mimeType = $imageInfo['mime'];
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    
    // 이미지 리소스 생성
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $sourceImage = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = @imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = @imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $sourceImage = @imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    if ($sourceImage === false) {
        return false;
    }
    
    // 최대 너비/높이 설정 (큰 이미지 리사이징)
    $maxDimension = 3000; // 최대 3000px
    $scale = 1.0;
    
    if ($width > $maxDimension || $height > $maxDimension) {
        $scale = min($maxDimension / $width, $maxDimension / $height);
    }
    
    $newWidth = (int)($width * $scale);
    $newHeight = (int)($height * $scale);
    
    // 새 이미지 생성
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // PNG/WebP 투명도 유지
    if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // 이미지 리사이징
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // 품질 조정하여 저장
    $quality = 85;
    $attempts = 0;
    $maxAttempts = 10;
    
    do {
        $tempPath = $targetPath . '.tmp';
        
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($newImage, $tempPath, $quality);
                break;
            case 'image/png':
                $compression = 9 - (int)(($quality - 50) / 5);
                $compression = max(0, min(9, $compression));
                imagepng($newImage, $tempPath, $compression);
                break;
            case 'image/gif':
                imagegif($newImage, $tempPath);
                break;
            case 'image/webp':
                imagewebp($newImage, $tempPath, $quality);
                break;
        }
        
        $newFileSize = filesize($tempPath);
        
        if ($newFileSize <= $maxSizeBytes) {
            rename($tempPath, $targetPath);
            imagedestroy($sourceImage);
            imagedestroy($newImage);
            return true;
        }
        
        $quality -= 10;
        $attempts++;
        
        // 품질이 너무 낮아지면 리사이징 크기 줄이기
        if ($quality < 50 && $attempts < $maxAttempts) {
            $scale *= 0.9;
            $newWidth = (int)($width * $scale);
            $newHeight = (int)($height * $scale);
            imagedestroy($newImage);
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $quality = 85; // 품질 다시 초기화
        }
    } while ($attempts < $maxAttempts);
    
    // 최대 시도 횟수 초과 시 마지막 파일 사용
    if (file_exists($tempPath)) {
        rename($tempPath, $targetPath);
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        return true;
    }
    
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    return false;
}

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
                `status` ENUM('pending', 'answered') NOT NULL DEFAULT 'pending' COMMENT '상태: pending=답변대기, answered=답변완료',
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
function updateSellerInquiry($inquiryId, $sellerId, $title, $content, $attachments = [], $keepFileIds = []) {
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
        
        // 삭제할 파일 처리
        // 새 파일을 업로드한 경우에만 keepFileIds에 없는 파일 삭제
        // 새 파일을 업로드하지 않은 경우: 기존 파일은 모두 유지 (변경 없음)
        
        if (!empty($attachments)) {
            // 새 파일을 업로드한 경우에만 기존 파일 삭제 처리
            error_log("updateSellerInquiry: New files uploaded, processing file deletion");
            
            // 기존 파일 목록 조회
            $stmt = $pdo->prepare("
                SELECT id, file_path FROM seller_inquiry_attachments 
                WHERE inquiry_id = :inquiry_id AND reply_id IS NULL
            ");
            $stmt->execute([':inquiry_id' => $inquiryId]);
            $allExistingFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 삭제할 파일 결정
            $filesToDelete = [];
            if (!empty($keepFileIds)) {
                // keepFileIds에 없는 파일 삭제
                foreach ($allExistingFiles as $existing) {
                    if (!in_array($existing['id'], $keepFileIds)) {
                        $filesToDelete[] = $existing;
                    }
                }
                error_log("updateSellerInquiry: Keeping " . count($keepFileIds) . " files, deleting " . count($filesToDelete) . " files");
            } else {
                // keepFileIds가 비어있으면 모든 기존 파일 삭제
                $filesToDelete = $allExistingFiles;
                error_log("updateSellerInquiry: No keepFileIds, deleting all " . count($filesToDelete) . " existing files");
            }
            
            // 파일 삭제 실행
            if (!empty($filesToDelete)) {
                foreach ($filesToDelete as $old) {
                    // DB 경로를 실제 파일 시스템 경로로 변환
                    $dbPath = $old['file_path'];
                    $actualPath = str_replace('/MVNO', '', $dbPath);
                    $filePath = __DIR__ . '/../..' . $actualPath;
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                        error_log("updateSellerInquiry: Deleted file: $filePath");
                    }
                }
                
                // DB에서 삭제
                $deleteIds = array_column($filesToDelete, 'id');
                if (!empty($deleteIds)) {
                    $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
                    $stmt = $pdo->prepare("
                        DELETE FROM seller_inquiry_attachments 
                        WHERE id IN ($placeholders) AND inquiry_id = ?
                    ");
                    $stmt->execute(array_merge($deleteIds, [$inquiryId]));
                    error_log("updateSellerInquiry: Deleted " . count($deleteIds) . " files from DB");
                }
            }
        } else {
            // 새 파일을 업로드하지 않은 경우: 기존 파일 유지 (변경 없음)
            error_log("updateSellerInquiry: No new files uploaded, keeping all existing files");
        }
        
        // 새 첨부파일 등록
        if (!empty($attachments)) {
            error_log("updateSellerInquiry: Saving " . count($attachments) . " attachments to DB");
            foreach ($attachments as $attachment) {
                error_log("updateSellerInquiry: Inserting attachment - " . json_encode($attachment));
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO seller_inquiry_attachments 
                        (inquiry_id, file_name, file_path, file_size, file_type, uploaded_by, created_at)
                        VALUES (:inquiry_id, :file_name, :file_path, :file_size, :file_type, :uploaded_by, NOW())
                    ");
                    $result = $stmt->execute([
                        ':inquiry_id' => $inquiryId,
                        ':file_name' => $attachment['file_name'],
                        ':file_path' => $attachment['file_path'],
                        ':file_size' => $attachment['file_size'],
                        ':file_type' => $attachment['file_type'],
                        ':uploaded_by' => $sellerId
                    ]);
                    if ($result) {
                        error_log("updateSellerInquiry: Attachment inserted successfully - ID: " . $pdo->lastInsertId());
                    } else {
                        error_log("updateSellerInquiry: Failed to insert attachment");
                    }
                } catch (PDOException $e) {
                    error_log("updateSellerInquiry: DB error inserting attachment - " . $e->getMessage());
                    throw $e;
                }
            }
        } else {
            error_log("updateSellerInquiry: No attachments to save");
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
        
        // 첨부파일 삭제 (문의 첨부파일 + 답변 첨부파일 모두)
        $stmt = $pdo->prepare("
            SELECT file_path FROM seller_inquiry_attachments 
            WHERE inquiry_id = :inquiry_id
        ");
        $stmt->execute([':inquiry_id' => $inquiryId]);
        $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $deletedDirs = [];
        foreach ($attachments as $attachment) {
            // DB 경로를 실제 파일 시스템 경로로 변환
            // DB 경로를 실제 파일 시스템 경로로 변환
            require_once __DIR__ . '/path-config.php';
            $basePath = getBasePath();
            $dbPath = $attachment['file_path'];
            if ($basePath && strpos($dbPath, $basePath) === 0) {
                $actualPath = str_replace($basePath, '', $dbPath);
            } elseif (strpos($dbPath, '/MVNO') === 0) {
                $actualPath = str_replace('/MVNO', '', $dbPath);
            } else {
                $actualPath = $dbPath;
            }
            // __DIR__은 includes/data이므로 ../../로 루트로 이동
            $filePath = __DIR__ . '/../..' . $actualPath;
            if (file_exists($filePath)) {
                @unlink($filePath);
                
                // 답변 첨부파일인 경우 디렉토리도 삭제 대상에 추가
                $fileDir = dirname($filePath);
                if (strpos($actualPath, '/replies/') !== false && !in_array($fileDir, $deletedDirs)) {
                    $deletedDirs[] = $fileDir;
                }
            }
        }
        
        // 답변 첨부파일 디렉토리 삭제
        foreach ($deletedDirs as $dir) {
            if (is_dir($dir)) {
                @rmdir($dir); // 디렉토리가 비어있으면 삭제
            }
        }
        
        // 문의 첨부파일 디렉토리 삭제 (비어있으면)
        $inquiryDir = __DIR__ . '/../../uploads/seller-inquiries/' . $inquiryId;
        if (is_dir($inquiryDir)) {
            // 디렉토리가 비어있는지 확인
            $files = array_diff(scandir($inquiryDir), ['.', '..']);
            if (empty($files)) {
                @rmdir($inquiryDir);
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
 * 판매자 확인 처리 함수 제거됨 - closed 상태 사용 안 함
 */

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
    
    // 이미지 파일이고 5MB 이상이면 압축
    $isImage = in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
    $fileSize = $file['size'];
    $maxSizeForCompression = 5 * 1024 * 1024; // 5MB
    
    if ($isImage && $fileSize > $maxSizeForCompression) {
        error_log("uploadSellerInquiryAttachment: Image file is larger than 5MB, compressing...");
        $compressed = compressImage($file['tmp_name'], $filePath, 5);
        if ($compressed) {
            $finalFileSize = filesize($filePath);
            error_log("uploadSellerInquiryAttachment: Image compressed successfully. Original: {$fileSize} bytes, Compressed: {$finalFileSize} bytes");
            $fileSize = $finalFileSize;
        } else {
            error_log("uploadSellerInquiryAttachment: Image compression failed, using original file");
            $moveResult = move_uploaded_file($file['tmp_name'], $filePath);
            if (!$moveResult) {
                error_log("uploadSellerInquiryAttachment: move_uploaded_file failed");
                return null;
            }
        }
    } else {
        // 이미지가 아니거나 5MB 이하면 그대로 이동
        $moveResult = move_uploaded_file($file['tmp_name'], $filePath);
        error_log("uploadSellerInquiryAttachment: move_uploaded_file result - " . ($moveResult ? 'success' : 'failed'));
        
        if (!$moveResult) {
            error_log("uploadSellerInquiryAttachment: move_uploaded_file failed");
            if (file_exists($file['tmp_name'])) {
                error_log("uploadSellerInquiryAttachment: temp file still exists");
            }
            return null;
        }
    }
    
    $fileExists = file_exists($filePath);
    $finalSize = $fileExists ? filesize($filePath) : 0;
    error_log("uploadSellerInquiryAttachment: file exists after processing - " . ($fileExists ? 'yes' : 'no') . ", size: $finalSize");
    
    require_once __DIR__ . '/path-config.php';
    $result = [
        'file_name' => $file['name'],
        'file_path' => getUploadPath('/uploads/seller-inquiries/' . $inquiryId . '/' . $fileName),
        'file_size' => $finalSize,
        'file_type' => $mimeType
    ];
    error_log("uploadSellerInquiryAttachment: returning result - " . json_encode($result));
    return $result;
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
    
    // 이미지 파일이고 5MB 이상이면 압축
    $isImage = in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
    $fileSize = $file['size'];
    $maxSizeForCompression = 5 * 1024 * 1024; // 5MB
    
    $moveResult = false;
    if ($isImage && $fileSize > $maxSizeForCompression) {
        error_log("uploadSellerInquiryReplyAttachment: Image file is larger than 5MB, compressing...");
        $compressed = compressImage($file['tmp_name'], $filePath, 5);
        if ($compressed) {
            $finalFileSize = filesize($filePath);
            error_log("uploadSellerInquiryReplyAttachment: Image compressed successfully. Original: {$fileSize} bytes, Compressed: {$finalFileSize} bytes");
            $fileSize = $finalFileSize;
            $moveResult = true;
        } else {
            error_log("uploadSellerInquiryReplyAttachment: Image compression failed, using original file");
            $moveResult = move_uploaded_file($file['tmp_name'], $filePath);
        }
    } else {
        // 이미지가 아니거나 5MB 이하면 그대로 이동
        $moveResult = move_uploaded_file($file['tmp_name'], $filePath);
    }
    
    // 파일 이동
    if ($moveResult) {
        $finalSize = filesize($filePath);
        require_once __DIR__ . '/path-config.php';
        return [
            'file_name' => $file['name'],
            'file_path' => getUploadPath('/uploads/seller-inquiries/' . $inquiryId . '/replies/' . $replyId . '/' . $fileName),
            'file_size' => $finalSize,
            'file_type' => $mimeType
        ];
    }
    
    return null;
}

