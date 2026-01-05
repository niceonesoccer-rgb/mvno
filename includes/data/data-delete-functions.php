<?php
/**
 * 데이터 삭제 관련 파일 삭제 함수
 */

/**
 * 판매자 관련 파일 삭제
 */
function deleteSellerFiles($pdo) {
    $baseDir = __DIR__ . '/../..';
    $deletedFiles = [];
    $deletedDirs = [];
    $totalSize = 0;
    
    // 1. 판매자 프로필 이미지 삭제 (business_license_image)
    try {
        $stmt = $pdo->query("SELECT business_license_image FROM seller_profiles WHERE business_license_image IS NOT NULL AND business_license_image != ''");
        $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($images as $imagePath) {
            if ($imagePath) {
                // DB 경로를 실제 파일 경로로 변환
                $filePath = str_replace('/MVNO', '', $imagePath);
                $filePath = ltrim($filePath, '/');
                $actualPath = $baseDir . '/' . $filePath;
                
                if (file_exists($actualPath) && is_file($actualPath)) {
                    $size = filesize($actualPath);
                    if (@unlink($actualPath)) {
                        $deletedFiles[] = basename($actualPath);
                        $totalSize += $size;
                    }
                }
            }
        }
    } catch (PDOException $e) {
        error_log('Error deleting seller profile images: ' . $e->getMessage());
    }
    
    // 2. 판매자 문의 첨부파일 삭제 (문의 첨부 + 답변 첨부 모두 포함)
    try {
        // 모든 첨부파일 조회 (reply_id가 NULL이면 문의 첨부, 있으면 답변 첨부)
        $stmt = $pdo->query("SELECT file_path, reply_id FROM seller_inquiry_attachments");
        $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $inquiryAttachments = 0;
        $replyAttachments = 0;
        
        foreach ($attachments as $attachment) {
            $filePath = $attachment['file_path'] ?? null;
            $replyId = $attachment['reply_id'] ?? null;
            
            if ($filePath) {
                // DB 경로를 실제 파일 경로로 변환
                $filePath = str_replace('/MVNO', '', $filePath);
                $filePath = ltrim($filePath, '/');
                $actualPath = $baseDir . '/' . $filePath;
                
                if (file_exists($actualPath) && is_file($actualPath)) {
                    $size = filesize($actualPath);
                    if (@unlink($actualPath)) {
                        $deletedFiles[] = basename($actualPath);
                        $totalSize += $size;
                        
                        // 통계
                        if ($replyId) {
                            $replyAttachments++;
                        } else {
                            $inquiryAttachments++;
                        }
                    }
                }
            }
        }
        
        // 판매자 문의 디렉토리 삭제 (문의 첨부파일 + 답변 첨부파일 모두 포함)
        // 경로: /uploads/seller-inquiries/{inquiryId}/ 또는 /uploads/seller-inquiries/{inquiryId}/replies/{replyId}/
        $inquiryDir = $baseDir . '/uploads/seller-inquiries';
        if (is_dir($inquiryDir)) {
            deleteDirectory($inquiryDir);
            $deletedDirs[] = 'uploads/seller-inquiries';
        }
        
        // 삭제 통계 로깅
        if ($inquiryAttachments > 0 || $replyAttachments > 0) {
            error_log("Deleted seller inquiry files: {$inquiryAttachments} inquiry attachments, {$replyAttachments} reply attachments");
        }
    } catch (PDOException $e) {
        error_log('Error deleting seller inquiry attachments: ' . $e->getMessage());
    }
    
    // 3. 판매자 업로드 디렉토리 삭제
    $sellerUploadDir = $baseDir . '/uploads/sellers';
    if (is_dir($sellerUploadDir)) {
        deleteDirectory($sellerUploadDir);
        $deletedDirs[] = 'uploads/sellers';
    }
    
    return [
        'files' => $deletedFiles,
        'dirs' => $deletedDirs,
        'total_size' => $totalSize
    ];
}

/**
 * 상품 관련 파일 삭제
 */
function deleteProductFiles($pdo) {
    $baseDir = __DIR__ . '/../..';
    $deletedFiles = [];
    $deletedDirs = [];
    $totalSize = 0;
    
    // 상품 이미지 경로 확인 (products 테이블의 image 필드)
    try {
        $tables = [
            'products' => ['image', 'thumbnail'],
            'product_mvno_details' => ['image'],
            'product_mno_details' => ['image'],
            'product_internet_details' => ['image']
        ];
        
        foreach ($tables as $table => $columns) {
            foreach ($columns as $column) {
                try {
                    $stmt = $pdo->query("SELECT DISTINCT `{$column}` FROM `{$table}` WHERE `{$column}` IS NOT NULL AND `{$column}` != ''");
                    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($images as $imagePath) {
                        if ($imagePath) {
                            // 경로 정규화
                            $filePath = str_replace('/MVNO', '', $imagePath);
                            $filePath = ltrim($filePath, '/');
                            
                            // images/upload/ 또는 uploads/ 경로 처리
                            if (strpos($filePath, 'images/') === 0) {
                                $actualPath = $baseDir . '/' . $filePath;
                            } elseif (strpos($filePath, 'uploads/') === 0) {
                                $actualPath = $baseDir . '/' . $filePath;
                            } else {
                                $actualPath = $baseDir . '/' . $filePath;
                            }
                            
                            if (file_exists($actualPath) && is_file($actualPath)) {
                                $size = filesize($actualPath);
                                if (@unlink($actualPath)) {
                                    $deletedFiles[] = basename($actualPath);
                                    $totalSize += $size;
                                }
                            }
                        }
                    }
                } catch (PDOException $e) {
                    // 컬럼이 없을 수 있음
                    continue;
                }
            }
        }
    } catch (PDOException $e) {
        error_log('Error deleting product images: ' . $e->getMessage());
    }
    
    return [
        'files' => $deletedFiles,
        'dirs' => $deletedDirs,
        'total_size' => $totalSize
    ];
}

/**
 * 디렉토리 재귀 삭제
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            @unlink($path);
        }
    }
    
    return @rmdir($dir);
}
