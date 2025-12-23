<?php
/**
 * 관리자용 판매자 정보 수정 페이지
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';

/**
 * 이미지 리사이징 및 압축 함수 (지정 용량 이하로 자동 축소)
 * - 기본값: 5MB
 * - JPEG/PNG/GIF 지원 (업로드 허용 확장자는 호출부에서 제한)
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
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourcePath);
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
    
    // PNG 투명도 유지
    if ($mimeType === 'image/png') {
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
        
        if ($quality < 50 && $attempts < $maxAttempts) {
            $scale *= 0.9;
            $newWidth = (int)($width * $scale);
            $newHeight = (int)($height * $scale);
            
            imagedestroy($newImage);
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            if ($mimeType === 'image/png') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $quality = 85;
        }
        
        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
        
    } while ($quality >= 30 && $attempts < $maxAttempts);
    
    // 최종 시도(최저 품질/압축)
    $tempPath = $targetPath . '.tmp';
    switch ($mimeType) {
        case 'image/jpeg':
            imagejpeg($newImage, $tempPath, 30);
            break;
        case 'image/png':
            imagepng($newImage, $tempPath, 9);
            break;
        case 'image/gif':
            imagegif($newImage, $tempPath);
            break;
    }
    
    if (file_exists($tempPath)) {
        $finalSize = @filesize($tempPath);
        if ($finalSize !== false && $finalSize <= $maxSizeBytes) {
            rename($tempPath, $targetPath);
            imagedestroy($sourceImage);
            imagedestroy($newImage);
            return true;
        }
        // 목표 용량 이하로 줄이지 못한 경우
        @unlink($tempPath);
    }
    
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    return false;
}

// 관리자 권한 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    header('Location: /MVNO/auth/login.php');
    exit;
}

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 판매자 ID 가져오기
$sellerId = $_GET['user_id'] ?? '';

if (empty($sellerId)) {
    header('Location: /MVNO/admin/seller-approval.php');
    exit;
}

// 판매자 정보 가져오기
$seller = getUserById($sellerId);

if (!$seller || $seller['role'] !== 'seller') {
    header('Location: /MVNO/admin/seller-approval.php');
    exit;
}

$isAdmin = true; // 관리자 전용 페이지

// 판매자 정보 수정 처리 (인증 체크 후)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_seller'])) {
    $userId = $_POST['user_id'] ?? '';
    $seller = getUserById($userId);
    
    if (!$seller || $seller['role'] !== 'seller') {
        $error_message = '판매자를 찾을 수 없습니다.';
    } else {
        // 이메일 처리 (@ 앞부분과 뒷부분 분리 또는 전체 이메일)
        $emailLocal = trim($_POST['email_local'] ?? '');
        $emailDomain = trim($_POST['email_domain'] ?? '');
        $emailCustom = trim($_POST['email_custom'] ?? '');
        
        // 이메일 조합
        if (!empty($emailLocal)) {
            if (!empty($emailCustom)) {
                $email = $emailLocal . '@' . $emailCustom;
            } else if (!empty($emailDomain)) {
                $email = $emailLocal . '@' . $emailDomain;
            } else {
                $email = $seller['email'] ?? '';
            }
        } else {
            // 기존 방식으로 이메일 입력한 경우
            $email = trim($_POST['email'] ?? $seller['email'] ?? '');
        }
        $email = trim($email);
        
        // 수정할 정보 수집
        $postedSellerName = trim($_POST['seller_name'] ?? '');
        $updateData = [
            'name' => $_POST['name'] ?? $seller['name'],
            'seller_name' => $postedSellerName !== '' ? $postedSellerName : ($seller['seller_name'] ?? ''),
            'email' => $email,
            'phone' => $_POST['phone'] ?? ($seller['phone'] ?? ''),
            'mobile' => $_POST['mobile'] ?? ($seller['mobile'] ?? ''),
            'address' => $_POST['address'] ?? ($seller['address'] ?? ''),
            'address_detail' => $_POST['address_detail'] ?? ($seller['address_detail'] ?? ''),
            'business_number' => $_POST['business_number'] ?? ($seller['business_number'] ?? ''),
            'company_name' => $_POST['company_name'] ?? ($seller['company_name'] ?? ''),
            'company_representative' => $_POST['company_representative'] ?? ($seller['company_representative'] ?? ''),
            'business_type' => $_POST['business_type'] ?? ($seller['business_type'] ?? ''),
            'business_item' => $_POST['business_item'] ?? ($seller['business_item'] ?? ''),
            'chat_consultation_url' => trim($_POST['chat_consultation_url'] ?? ''),
        ];
        
        // 비밀번호 변경이 있는 경우
        if (!empty($_POST['password'])) {
            $updateData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        // 이메일 검증
        if (empty($email)) {
            $error_message = '이메일을 입력해주세요.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = '올바른 이메일 형식이 아닙니다.';
        } elseif (strlen($emailLocal) > 20) {
            $error_message = '이메일 아이디는 20자 이내로 입력해주세요.';
        }
        
        // 휴대폰/전화번호 검증: seller/register.php(4단계) 규칙과 동일하게 공용 함수 사용
        if (empty($error_message)) {
            $formattedMobile = validateAndFormatSellerMobile($updateData['mobile'] ?? '', $error_message);
            if (empty($error_message) && $formattedMobile !== null) {
                $updateData['mobile'] = $formattedMobile;
            }
        }

        if (empty($error_message)) {
            $formattedPhone = null;
            if (validateSellerPhoneList($updateData['phone'] ?? '', $error_message, $formattedPhone)) {
                if (!empty($formattedPhone)) {
                    $updateData['phone'] = $formattedPhone;
                }
            }
        }
        
        // 사업자등록증 이미지 업로드 처리
        if (isset($_FILES['business_license_image']) && $_FILES['business_license_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/sellers/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $tempPath = $_FILES['business_license_image']['tmp_name'];
            $originalFileName = $_FILES['business_license_image']['name'];
            $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
            // 문서 파일 확장자 차단
            $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'hwp', 'gif'];
            if (in_array($fileExtension, $documentExtensions)) {
                $error_message = 'GIF 파일과 문서 파일은 업로드할 수 없습니다. (JPG, PNG만 가능)';
            } else {
                $allowedExtensions = ['jpg', 'jpeg', 'png'];
                
                // 확장자 체크
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $error_message = '이미지 파일만 업로드 가능합니다. (jpg, jpeg, png)';
                } else {
                    // 실제 이미지 파일인지 MIME 타입으로 체크
                    $imageInfo = @getimagesize($tempPath);
                    if ($imageInfo === false) {
                        $error_message = '이미지 파일이 아닙니다. 올바른 이미지 파일을 업로드해주세요.';
                    } else {
                        $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                        $detectedMimeType = $imageInfo['mime'];
                        
                        if (!in_array($detectedMimeType, $allowedMimeTypes)) {
                            $error_message = '이미지 파일만 업로드 가능합니다. (jpg, jpeg, png)';
                        } else {
                            // 기존 이미지 삭제 (있는 경우)
                            // DB에는 "/MVNO/uploads/..." 같은 웹경로로 저장되어 있으므로 파일시스템 경로로 안전하게 변환한다.
                            $projectRoot = realpath(__DIR__ . '/../../');
                            $uploadRoot = $projectRoot ? ($projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sellers') : null;
                            if (!empty($seller['business_license_image']) && $projectRoot && $uploadRoot) {
                                $webPath = (string)$seller['business_license_image'];
                                // "/MVNO" 접두 제거 후 실제 파일 경로 생성
                                if (strpos($webPath, '/MVNO/') === 0) {
                                    $webPath = substr($webPath, 5); // "/MVNO" 길이
                                }
                                $candidate = $projectRoot . str_replace('/', DIRECTORY_SEPARATOR, $webPath);
                                $candidateReal = realpath($candidate);
                                if ($candidateReal && strpos($candidateReal, $uploadRoot) === 0 && is_file($candidateReal)) {
                                    @unlink($candidateReal);
                                }
                            }
                            
                            // 새 파일명 생성
                            $fileName = $userId . '_license_' . time() . '.' . $fileExtension;
                            $targetPath = $uploadDir . $fileName;
                            
                            // 이미지 압축 및 저장 (5MB 이하로 자동 압축)
                            if (compressImage($tempPath, $targetPath, 5)) {
                                // 상대 경로 저장
                                $updateData['business_license_image'] = '/MVNO/uploads/sellers/' . $fileName;

                                // 같은 사용자 예전 파일 정리(폴더에 고아 파일이 남지 않게)
                                // - 방금 저장된 파일은 유지
                                $keep = realpath($targetPath);
                                foreach (glob($uploadDir . $userId . '_license_*.*') ?: [] as $p) {
                                    $real = realpath($p);
                                    if ($real && $keep && $real !== $keep && is_file($real)) {
                                        @unlink($real);
                                    }
                                }
                            } else {
                                $error_message = '이미지 업로드에 실패했습니다. (5MB 이하로 압축할 수 없습니다)';
                            }
                        }
                    }
                }
            }
        }
        
        // 에러가 없을 때만 업데이트 진행
        if (!isset($error_message)) {
            // 변경 사항 확인
            $hasChanges = false;
            
            // 각 필드 비교
            foreach ($updateData as $key => $newValue) {
                $oldValue = $seller[$key] ?? '';
                // 문자열로 변환하여 비교 (null과 빈 문자열도 비교)
                $oldValueStr = (string)($oldValue ?? '');
                $newValueStr = (string)($newValue ?? '');
                
                if ($oldValueStr !== $newValueStr) {
                    $hasChanges = true;
                    break;
                }
            }
            
            // 비밀번호 변경 확인
            if (!empty($_POST['password'])) {
                $hasChanges = true;
            }
            
            // 이미지 업로드 확인
            if (isset($_FILES['business_license_image']) && $_FILES['business_license_image']['error'] === UPLOAD_ERR_OK) {
                $hasChanges = true;
            }
            
            // 변경 사항이 있을 때만 업데이트 진행
            if ($hasChanges) {
                // DB-only: users + seller_profiles 업데이트
                $pdo = getDBConnection();
                if (!$pdo) {
                    $error_message = 'DB 연결에 실패했습니다.';
                } else {
                    try {
                        $pdo->beginTransaction();

                        // chat_consultation_url 필드 존재 여부 확인
                        $checkColumn = $pdo->query("
                            SELECT COUNT(*) 
                            FROM INFORMATION_SCHEMA.COLUMNS 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = 'users' 
                            AND COLUMN_NAME = 'chat_consultation_url'
                        ");
                        $columnExists = $checkColumn->fetchColumn() > 0;

                        // 필드가 있으면 포함, 없으면 제외
                        $chatConsultationSql = $columnExists ? "chat_consultation_url = :chat_consultation_url," : "";

                        // users(표시/로그인 호환용) 업데이트
                        $u = $pdo->prepare("
                            UPDATE users
                            SET name = :name,
                                seller_name = :seller_name,
                                email = :email,
                                phone = :phone,
                                mobile = :mobile,
                                address = :address,
                                address_detail = :address_detail,
                                business_number = :business_number,
                                company_name = :company_name,
                                company_representative = :company_representative,
                                business_type = :business_type,
                                business_item = :business_item,
                                business_license_image = :business_license_image,
                                " . $chatConsultationSql . "
                                password = COALESCE(:password, password),
                                updated_at = NOW()
                            WHERE user_id = :user_id
                              AND role = 'seller'
                            LIMIT 1
                        ");

                        $passwordHashed = null;
                        if (!empty($_POST['password'])) {
                            $passwordHashed = $updateData['password'] ?? password_hash($_POST['password'], PASSWORD_DEFAULT);
                        }

                        $executeParams = [
                            ':name' => $updateData['name'] ?? ($seller['name'] ?? ''),
                            ':seller_name' => $updateData['seller_name'] ?? ($seller['seller_name'] ?? null),
                            ':email' => $updateData['email'] ?? ($seller['email'] ?? null),
                            ':phone' => $updateData['phone'] ?? ($seller['phone'] ?? null),
                            ':mobile' => $updateData['mobile'] ?? ($seller['mobile'] ?? null),
                            ':address' => $updateData['address'] ?? ($seller['address'] ?? null),
                            ':address_detail' => $updateData['address_detail'] ?? ($seller['address_detail'] ?? null),
                            ':business_number' => $updateData['business_number'] ?? ($seller['business_number'] ?? null),
                            ':company_name' => $updateData['company_name'] ?? ($seller['company_name'] ?? null),
                            ':company_representative' => $updateData['company_representative'] ?? ($seller['company_representative'] ?? null),
                            ':business_type' => $updateData['business_type'] ?? ($seller['business_type'] ?? null),
                            ':business_item' => $updateData['business_item'] ?? ($seller['business_item'] ?? null),
                            ':business_license_image' => $updateData['business_license_image'] ?? ($seller['business_license_image'] ?? null),
                            ':password' => $passwordHashed,
                            ':user_id' => $userId
                        ];
                        
                        if ($columnExists) {
                            $executeParams[':chat_consultation_url'] = $updateData['chat_consultation_url'] ?? ($seller['chat_consultation_url'] ?? null);
                        }
                        
                        $u->execute($executeParams);

                        // seller_profiles 업데이트 (존재하면)
                        $sp = $pdo->prepare("
                            UPDATE seller_profiles
                            SET postal_code = NULL,
                                address = :address,
                                address_detail = :address_detail,
                                business_number = :business_number,
                                company_name = :company_name,
                                company_representative = :company_representative,
                                business_type = :business_type,
                                business_item = :business_item,
                                business_license_image = :business_license_image,
                                info_checked_by_admin = info_checked_by_admin,
                                updated_at = NOW()
                            WHERE user_id = :user_id
                            LIMIT 1
                        ");
                        $sp->execute([
                            ':address' => $updateData['address'] ?? ($seller['address'] ?? null),
                            ':address_detail' => $updateData['address_detail'] ?? ($seller['address_detail'] ?? null),
                            ':business_number' => $updateData['business_number'] ?? ($seller['business_number'] ?? null),
                            ':company_name' => $updateData['company_name'] ?? ($seller['company_name'] ?? null),
                            ':company_representative' => $updateData['company_representative'] ?? ($seller['company_representative'] ?? null),
                            ':business_type' => $updateData['business_type'] ?? ($seller['business_type'] ?? null),
                            ':business_item' => $updateData['business_item'] ?? ($seller['business_item'] ?? null),
                            ':business_license_image' => $updateData['business_license_image'] ?? ($seller['business_license_image'] ?? null),
                            ':user_id' => $userId
                        ]);

                        $pdo->commit();

                        // 저장 성공 플래그 설정 (리다이렉트하지 않고 같은 페이지에 머물러서 모달 표시)
                        $success_saved = true;
                        // 판매자 정보 다시 로드 (업데이트된 정보 반영)
                        $seller = getUserById($userId);
                    } catch (PDOException $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        error_log('seller-edit DB error: ' . $e->getMessage());
                        $error_message = '판매자 정보 업데이트에 실패했습니다.';
                    }
                }
            } else {
                // 변경 사항이 없으면 메시지 표시
                $error_message = '변경된 사항이 없습니다.';
            }
        }
    }
}

// POST 처리 후 판매자 정보 다시 로드 (업데이트된 정보 반영)
if (isset($success_saved) && $success_saved && isset($userId)) {
    $seller = getUserById($userId);
}

// 기존 이메일 값을 파싱하여 email_local과 email_domain으로 분리
$emailLocal = '';
$emailDomain = '';
$emailCustom = '';
$currentEmail = $seller['email'] ?? '';
if (!empty($currentEmail) && strpos($currentEmail, '@') !== false) {
    $emailParts = explode('@', $currentEmail);
    $emailLocal = $emailParts[0] ?? '';
    $domain = $emailParts[1] ?? '';
    
    $commonDomains = ['naver.com', 'gmail.com', 'hanmail.net', 'nate.com'];
    if (in_array($domain, $commonDomains)) {
        $emailDomain = $domain;
    } else {
        $emailDomain = 'custom';
        $emailCustom = $domain;
    }
}

// 관리자 헤더 사용
require_once __DIR__ . '/../includes/admin-header.php';
?>

<style>
    .seller-edit-container {
        max-width: 900px;
        margin: 0 auto;
    }
    
    .edit-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    
    .edit-header h1 {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .back-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #f3f4f6;
        color: #374151;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .back-button:hover {
        background: #e5e7eb;
    }
    
    .edit-form-card {
        background: white;
        border-radius: 12px;
        padding: 32px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .form-section {
        margin-bottom: 32px;
    }
    
    .form-section-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .form-section-title-right {
        font-size: 14px;
        font-weight: 500;
        color: #6366f1;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-label .required {
        color: #ef4444;
        margin-left: 4px;
    }
    
    .form-input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .form-input.error {
        border-color: #ef4444;
        background-color: #fef2f2;
    }
    
    .form-input.error:focus {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
    
    .form-error-message {
        font-size: 12px;
        color: #ef4444;
        margin-top: 4px;
        display: none;
    }
    
    .form-group.has-error .form-error-message {
        display: block;
    }
    
    .form-textarea {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        resize: vertical;
        min-height: 100px;
        transition: all 0.2s;
    }
    
    .form-textarea:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .form-actions {
        display: flex;
        gap: 80px;
        justify-content: flex-end;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
    }
    
    .btn {
        padding: 10px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    
    .btn-primary {
        background: #6366f1;
        color: white;
    }
    
    .btn-primary:hover {
        background: #4f46e5;
    }
    
    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    
    .btn-secondary:hover {
        background: #e5e7eb;
    }
    
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #10b981;
    }
    
    .password-note {
        font-size: 12px;
        color: #6b7280;
        margin-top: 4px;
    }
    
    .seller-name-check-result {
        font-size: 12px;
        min-height: 18px;
    }
    
    .seller-name-check-result.checking {
        color: #6b7280;
    }
    
    .seller-name-check-result.success {
        color: #10b981;
    }
    
    .seller-name-check-result.error {
        color: #ef4444;
    }
    
    .form-input.checked-valid {
        border-color: #10b981;
    }
    
    .form-input.checked-invalid {
        border-color: #ef4444;
        background-color: #fef2f2;
    }
    
    /* 이메일 입력 필드 스타일 */
    .email-input-group {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .email-input-group input[type="text"] {
        flex: 1;
        min-width: 120px;
    }
    
    .email-input-group select {
        flex: 1;
        min-width: 150px;
    }
    
    .email-input-group .email-at {
        font-size: 16px;
        color: #6b7280;
        white-space: nowrap;
        padding: 0 4px;
    }
    
    .form-input[type="file"] {
        padding: 8px;
        cursor: pointer;
    }
    
    .form-input[type="file"]::-webkit-file-upload-button {
        padding: 8px 16px;
        background: #6366f1;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        margin-right: 12px;
    }
    
    .form-input[type="file"]::-webkit-file-upload-button:hover {
        background: #4f46e5;
    }
    
    /* 저장 성공 모달 스타일 */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10000;
        align-items: center;
        justify-content: center;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .save-success-modal {
        background: white;
        border-radius: 12px;
        padding: 32px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        text-align: center;
    }
    
    .save-success-modal-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 20px;
        background: #10b981;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .save-success-modal-icon svg {
        width: 36px;
        height: 36px;
        stroke: white;
        stroke-width: 3;
        fill: none;
    }
    
    .save-success-modal-title {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 12px;
    }
    
    .save-success-modal-message {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 24px;
    }
    
    .save-success-modal-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
    }
    
    .modal-btn {
        padding: 10px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    
    .modal-btn-primary {
        background: #6366f1;
        color: white;
    }
    
    .modal-btn-primary:hover {
        background: #4f46e5;
    }
</style>

<div class="admin-content">
    <div class="seller-edit-container">
        <div class="edit-header">
            <h1>판매자 정보 수정</h1>
            <div style="display: flex; gap: 12px; align-items: center;">
                <a href="/MVNO/admin/seller-approval.php?tab=approved" class="back-button">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    목록으로
                </a>
                <a href="/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>" class="back-button">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    상세보기로
                </a>
            </div>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="edit-form-card" enctype="multipart/form-data">
            <input type="hidden" name="update_seller" value="1">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($seller['user_id']); ?>">
            
            <!-- 기본 정보 -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <span>기본 정보</span>
                    <span class="form-section-title-right">정보수정</span>
                </h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">아이디</label>
                        <input type="text" class="form-input" value="<?php echo htmlspecialchars($seller['user_id']); ?>" disabled>
                        <div class="password-note">아이디는 변경할 수 없습니다.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">이름 <span class="required">*</span></label>
                        <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($seller['name'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">판매자명</label>
                        <input type="text" name="seller_name" id="seller_name" class="form-input" value="<?php echo htmlspecialchars($seller['seller_name'] ?? ''); ?>" placeholder="판매자명을 입력하세요" maxlength="50">
                        <div id="seller_name_check_result" class="seller-name-check-result" style="margin-top: 4px; font-size: 12px; min-height: 18px;"></div>
                        <div class="password-note">판매자명이 설정되면 상품 목록 등에서 표시됩니다.</div>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">이메일 <span class="required">*</span></label>
                        <div class="email-input-group">
                            <input type="text" id="email_local" name="email_local" class="form-input" value="<?php echo htmlspecialchars($emailLocal); ?>" placeholder="이메일 아이디" maxlength="20" required>
                            <span class="email-at">@</span>
                            <select id="email_domain" name="email_domain" class="form-input" onchange="handleEmailDomainChange()" required>
                                <option value="">선택하세요</option>
                                <option value="naver.com" <?php echo ($emailDomain === 'naver.com') ? 'selected' : ''; ?>>naver.com</option>
                                <option value="gmail.com" <?php echo ($emailDomain === 'gmail.com') ? 'selected' : ''; ?>>gmail.com</option>
                                <option value="hanmail.net" <?php echo ($emailDomain === 'hanmail.net') ? 'selected' : ''; ?>>hanmail.net</option>
                                <option value="nate.com" <?php echo ($emailDomain === 'nate.com') ? 'selected' : ''; ?>>nate.com</option>
                                <option value="custom" <?php echo ($emailDomain === 'custom') ? 'selected' : ''; ?>>직접 입력</option>
                            </select>
                            <input type="text" id="email_custom" name="email_custom" class="form-input" value="<?php echo htmlspecialchars($emailCustom); ?>" placeholder="도메인 입력" style="display: <?php echo ($emailDomain === 'custom') ? 'block' : 'none'; ?>;">
                        </div>
                        <input type="hidden" id="email" name="email" value="<?php echo htmlspecialchars($currentEmail); ?>">
                        <div class="password-note">이메일 아이디는 20자 이내로 입력해주세요.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">비밀번호</label>
                        <input type="password" name="password" class="form-input" placeholder="변경하지 않으려면 비워두세요">
                        <div class="password-note">비밀번호를 변경하려면 새 비밀번호를 입력하세요.</div>
                    </div>
                </div>
            </div>
            
            <!-- 연락처 정보 -->
            <div class="form-section">
                <h2 class="form-section-title">연락처 정보</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">전화번호</label>
                        <input type="tel" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($seller['phone'] ?? ''); ?>" placeholder="1588-1234, 02-1234-5678, 070-1234-5678">
                        <div class="password-note">(예: 1588-1234, 02-1234-5678)</div>
                        <div class="phone-error-message" style="display: none; font-size: 12px; color: #ef4444; margin-top: 4px;"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">휴대폰 <span class="required">*</span></label>
                        <input type="tel" id="mobile" name="mobile" class="form-input" value="<?php echo htmlspecialchars($seller['mobile'] ?? ''); ?>" placeholder="010-1234-5678" required>
                        <div class="password-note">010으로 시작하는 11자리 숫자를 입력해주세요.</div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">채팅상담 URL</label>
                    <input type="url" id="chat_consultation_url" name="chat_consultation_url" class="form-input" value="<?php echo htmlspecialchars($seller['chat_consultation_url'] ?? ''); ?>" placeholder="https://pf.kakao.com/_abc123 또는 네이버톡톡 URL">
                    <div class="password-note">카카오톡 채널 또는 네이버톡톡 등 채팅상담 URL을 입력해주세요.</div>
                </div>
            </div>
            
            <!-- 주소 정보 -->
            <div class="form-section">
                <h2 class="form-section-title">주소 정보</h2>
                <div class="form-group">
                    <label class="form-label">주소</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="address" name="address" class="form-input" value="<?php echo htmlspecialchars($seller['address'] ?? ''); ?>" placeholder="서울시 강남구 테헤란로 123" readonly style="flex: 1;">
                        <button type="button" id="searchAddressBtn" class="btn btn-primary" onclick="searchAddress()" style="white-space: nowrap; padding: 10px 20px;">주소 검색</button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">상세주소</label>
                    <input type="text" id="address_detail" name="address_detail" class="form-input" value="<?php echo htmlspecialchars($seller['address_detail'] ?? ''); ?>" placeholder="101호">
                </div>
            </div>
            
            <!-- 사업자 정보 -->
            <div class="form-section">
                <h2 class="form-section-title">사업자 정보</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">사업자등록번호</label>
                        <input type="text" name="business_number" class="form-input" value="<?php echo htmlspecialchars($seller['business_number'] ?? ''); ?>" placeholder="123-45-67890">
                    </div>
                    <div class="form-group">
                        <label class="form-label">회사명</label>
                        <input type="text" name="company_name" class="form-input" value="<?php echo htmlspecialchars($seller['company_name'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">대표자명</label>
                        <input type="text" name="company_representative" class="form-input" value="<?php echo htmlspecialchars($seller['company_representative'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">업태</label>
                        <input type="text" name="business_type" class="form-input" value="<?php echo htmlspecialchars($seller['business_type'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">종목</label>
                    <input type="text" name="business_item" class="form-input" value="<?php echo htmlspecialchars($seller['business_item'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">사업자등록증</label>
                    <?php if (!empty($seller['business_license_image'])): ?>
                        <div style="margin-bottom: 12px;">
                            <img src="<?php echo htmlspecialchars($seller['business_license_image']); ?>" alt="사업자등록증" style="max-width: 400px; max-height: 300px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 8px;">
                            <div class="password-note">현재 등록된 사업자등록증입니다. 새로 업로드하면 기존 이미지가 교체됩니다.</div>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="business_license_image" accept="image/jpeg,image/jpg,image/png" class="form-input">
                    <div class="password-note">이미지 파일만 업로드 가능합니다. (jpg, jpeg, png) - GIF 및 문서 파일은 업로드 불가</div>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>" class="btn btn-secondary">취소</a>
                <button type="submit" class="btn btn-primary">저장</button>
            </div>
        </form>
    </div>
</div>

<!-- 다음 우편번호 API 스크립트 -->
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>

<script>
    // 주소 검색 (다음 우편번호 API)
    function searchAddress() {
        // 모달 오버레이 생성
        let overlay = document.getElementById('address-search-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'address-search-overlay';
            overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;';
            overlay.onclick = function(e) {
                if (e.target === overlay) {
                    closeAddressSearch();
                }
            };
            document.body.appendChild(overlay);
        }
        
        // 컨테이너 생성
        let container = document.getElementById('address-search-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'address-search-container';
            container.style.cssText = 'position: relative; width: 500px; max-width: 90vw; height: 600px; max-height: 90vh; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); overflow: hidden; z-index: 10001;';
            overlay.appendChild(container);
            
            // 닫기 버튼 추가
            let closeBtn = document.createElement('button');
            closeBtn.innerHTML = '×';
            closeBtn.style.cssText = 'position: absolute; top: 10px; right: 10px; width: 36px; height: 36px; border: none; background: #f3f4f6; border-radius: 50%; cursor: pointer; font-size: 24px; line-height: 1; z-index: 10002; color: #374151; display: flex; align-items: center; justify-content: center; transition: all 0.2s;';
            closeBtn.onmouseover = function() {
                this.style.background = '#e5e7eb';
            };
            closeBtn.onmouseout = function() {
                this.style.background = '#f3f4f6';
            };
            closeBtn.onclick = closeAddressSearch;
            container.appendChild(closeBtn);
        }
        
        // 오버레이 표시
        overlay.style.display = 'flex';
        
        // 다음 우편번호 API 초기화
        new daum.Postcode({
            oncomplete: function(data) {
                // 주소 선택 시 실행되는 함수
                let addr = ''; // 주소 변수
                
                // 사용자가 선택한 주소 타입에 따라 해당 주소 값을 가져온다.
                if (data.userSelectedType === 'R') { // 사용자가 도로명 주소를 선택했을 경우
                    addr = data.roadAddress;
                } else { // 사용자가 지번 주소를 선택했을 경우(J)
                    addr = data.jibunAddress;
                }
                
                // 주소 필드에 값 설정
                document.getElementById('address').value = addr;
                // 팝업 닫기
                closeAddressSearch();
                // 커서를 상세주소 필드로 이동
                document.getElementById('address_detail').focus();
            },
            width: '100%',
            height: '100%',
            maxSuggestItems: 5
        }).embed(container);
    }
    
    // 주소 검색 팝업 닫기
    function closeAddressSearch() {
        let overlay = document.getElementById('address-search-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
    
    // 전화번호 하이픈 자동 입력 및 길이 제한
    function formatPhoneNumber(value, isMobile = false) {
        // 숫자만 추출
        const numbers = value.replace(/[^\d]/g, '');
        
        if (isMobile) {
            // 휴대폰: 010-1234-5678 (3-4-4, 11자리)
            const limited = numbers.slice(0, 11);
            if (limited.length <= 3) {
                return limited;
            } else if (limited.length <= 7) {
                return limited.slice(0, 3) + '-' + limited.slice(3);
            } else {
                return limited.slice(0, 3) + '-' + limited.slice(3, 7) + '-' + limited.slice(7);
            }
        } else {
            // 일반 전화번호 형식 처리 (휴대폰 포함)
            // 휴대폰 (010, 011, 016, 017, 018, 019) - 11자리: 010-1234-5678
            if (numbers.startsWith('010') || numbers.startsWith('011') || 
                numbers.startsWith('016') || numbers.startsWith('017') || 
                numbers.startsWith('018') || numbers.startsWith('019')) {
                const limited = numbers.slice(0, 11);
                if (limited.length <= 3) {
                    return limited;
                } else if (limited.length <= 7) {
                    return limited.slice(0, 3) + '-' + limited.slice(3);
                } else {
                    return limited.slice(0, 3) + '-' + limited.slice(3, 7) + '-' + limited.slice(7);
                }
            }
            // 전국대표번호 (1XXX-XXXX) - 8자리: 1588-1234, 1688-6547, 1111-1111 등
            // 서버 검증과 동일하게 "1로 시작하는 4자리 + 4자리" 체계를 폭넓게 허용
            else if (numbers.length >= 1 && numbers.startsWith('1')) {
                const limited = numbers.slice(0, 8);
                if (limited.length <= 4) {
                    return limited;
                } else {
                    return limited.slice(0, 4) + '-' + limited.slice(4);
                }
            }
            // 인터넷전화/수신자부담 (070, 080) - 10자리(3-3-4) 또는 11자리(3-4-4)
            else if (numbers.startsWith('070') || numbers.startsWith('080')) {
                const limited = numbers.slice(0, 11);
                if (limited.length <= 3) {
                    return limited;
                } else if (limited.length <= 6) {
                    return limited.slice(0, 3) + '-' + limited.slice(3);
                } else if (limited.length <= 10) {
                    return limited.slice(0, 3) + '-' + limited.slice(3, 6) + '-' + limited.slice(6);
                } else {
                    return limited.slice(0, 3) + '-' + limited.slice(3, 7) + '-' + limited.slice(7);
                }
            }
            // 서울 지역번호 (02) - 9자리(02-XXX-XXXX) 또는 10자리(02-XXXX-XXXX)
            else if (numbers.startsWith('02')) {
                // 02로 시작하는 경우 최대 10자리만 허용
                const limited = numbers.slice(0, 10);
                if (limited.length <= 2) {
                    return limited;
                } else if (limited.length <= 5) {
                    // 02-XXX (9자리 케이스의 중간 3자리)
                    return limited.slice(0, 2) + '-' + limited.slice(2);
                } else if (limited.length <= 9) {
                    // 02-XXX-XXXX (9자리)
                    return limited.slice(0, 2) + '-' + limited.slice(2, 5) + '-' + limited.slice(5);
                } else {
                    // 02-XXXX-XXXX (10자리)
                    return limited.slice(0, 2) + '-' + limited.slice(2, 6) + '-' + limited.slice(6);
                }
            }
            // 지역번호 3자리 (031, 032, 033, 041, 042, 043, 044, 051, 052, 053, 054, 055, 061, 062, 063, 064)
            else if (numbers.length >= 3 && numbers.startsWith('0')) {
                // 10자리 형식: 031-123-4567 (3-3-4)
                // 11자리 형식: 031-1234-5678 (3-4-4)
                const limited = numbers.slice(0, 11);
                if (limited.length <= 3) {
                    return limited;
                } else if (limited.length <= 6) {
                    // 3-3 형식
                    return limited.slice(0, 3) + '-' + limited.slice(3);
                } else if (limited.length <= 10) {
                    // 3-3-4 형식 (10자리)
                    return limited.slice(0, 3) + '-' + limited.slice(3, 6) + '-' + limited.slice(6);
                } else {
                    // 3-4-4 형식 (11자리)
                    return limited.slice(0, 3) + '-' + limited.slice(3, 7) + '-' + limited.slice(7);
                }
            } else {
                // 기타: 숫자만 반환 (최대 11자리)
                return numbers.slice(0, 11);
            }
        }
    }
    
    // 전화번호 유효성 검증 함수
    function validatePhoneNumber(phoneValue) {
        const numbers = phoneValue.replace(/[^\d]/g, '');
        const length = numbers.length;
        
        if (length < 8 || length > 11) {
            return { valid: false, message: '전화번호는 8-11자리여야 합니다.' };
        }
        
        // 휴대폰 (010, 011, 016, 017, 018, 019) - 11자리
        if (length === 11 && /^01[0-9]\d{8}$/.test(numbers)) {
            return { valid: true };
        }
        // 02-XXX-XXXX (서울, 9자리) 또는 02-XXXX-XXXX (서울, 10자리)
        if (length === 9 && /^02\d{7}$/.test(numbers)) {
            return { valid: true };
        }
        if (length === 10 && /^02\d{8}$/.test(numbers)) {
            return { valid: true };
        }
        // 0XX-XXX-XXXX (지역번호 3자리, 10자리)
        if (length === 10 && /^0[3-6]\d{8}$/.test(numbers)) {
            return { valid: true };
        }
        // 0XX-XXXX-XXXX (일부 지역번호, 11자리)
        if (length === 11 && /^0[3-6]\d{9}$/.test(numbers)) {
            return { valid: true };
        }
        // 070/080-XXX-XXXX (10자리) 또는 070/080-XXXX-XXXX (11자리)
        if (length === 10 && /^0[78]0\d{7}$/.test(numbers)) {
            return { valid: true };
        }
        if (length === 11 && /^0[78]0\d{8}$/.test(numbers)) {
            return { valid: true };
        }
        // 전국대표번호 (1XXX로 시작하는 4자리 번호 + 4자리, 총 8자리)
        if (length === 8 && /^1\d{3}\d{4}$/.test(numbers)) {
            return { valid: true };
        }
        
        return { valid: false, message: '전화번호 형식이 올바르지 않습니다. (예: 02-1234-5678, 031-123-4567, 010-1234-5678, 1588-1234, 070-1234-5678, 080-1234-5678)' };
    }
    
    // 사업자등록번호 하이픈 자동 입력 (123-45-67890)
    function formatBusinessNumber(value) {
        // 숫자만 추출
        const numbers = value.replace(/[^\d]/g, '');
        
        // 최대 10자리까지만 허용
        const limited = numbers.slice(0, 10);
        
        // 형식: 123-45-67890 (3-2-5)
        if (limited.length <= 3) {
            return limited;
        } else if (limited.length <= 5) {
            return limited.slice(0, 3) + '-' + limited.slice(3);
        } else {
            return limited.slice(0, 3) + '-' + limited.slice(3, 5) + '-' + limited.slice(5);
        }
    }
    
    // 이메일 조합 함수
    function getCombinedEmail() {
        const emailLocal = document.getElementById('email_local').value.trim();
        const emailDomain = document.getElementById('email_domain').value;
        const emailCustom = document.getElementById('email_custom').value.trim();
        
        if (!emailLocal) {
            return '';
        }
        
        let domain = '';
        if (emailDomain === 'custom') {
            domain = emailCustom;
        } else {
            domain = emailDomain;
        }
        
        if (!domain) {
            return '';
        }
        
        return emailLocal + '@' + domain;
    }
    
    // 이메일 필드 업데이트
    function updateEmailField() {
        const combinedEmail = getCombinedEmail();
        document.getElementById('email').value = combinedEmail;
    }
    
    // 이메일 도메인 선택 변경 처리
    function handleEmailDomainChange() {
        const emailDomain = document.getElementById('email_domain').value;
        const emailCustom = document.getElementById('email_custom');
        
        if (emailDomain === 'custom') {
            emailCustom.style.display = 'block';
            emailCustom.required = true;
            emailCustom.focus();
        } else {
            emailCustom.style.display = 'none';
            emailCustom.required = false;
            emailCustom.value = '';
        }
        
        // 이메일 조합하여 hidden 필드 업데이트
        updateEmailField();
    }
    
    // 페이지 로드 시 전화번호 필드에 이벤트 리스너 추가
    document.addEventListener('DOMContentLoaded', function() {
        // 사업자등록번호 필드
        const businessNumberInput = document.querySelector('input[name="business_number"]');
        if (businessNumberInput) {
            businessNumberInput.addEventListener('input', function(e) {
                const cursorPosition = e.target.selectionStart;
                const oldValue = e.target.value;
                const newValue = formatBusinessNumber(e.target.value);
                
                e.target.value = newValue;
                
                // 커서 위치 조정
                const diff = newValue.length - oldValue.length;
                const newCursorPosition = Math.max(0, Math.min(newValue.length, cursorPosition + diff));
                e.target.setSelectionRange(newCursorPosition, newCursorPosition);
            });
        }
        
        // 전화번호 필드 (쉼표로 구분된 여러 번호 지원)
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
            let isFormatting = false; // 포맷팅 중 중복 실행 방지
            // "형식이 맞지 않으면 더 이상 입력 안되게" 처리:
            // 접두(prefix) 규칙이 깨지는 순간 마지막 정상값으로 롤백한다.
            let lastValidPhoneValue = phoneInput.value || '';

            function isValidPhonePrefixProgress(digits) {
                if (!digits) return true;
                // 첫자리는 0(일반) 또는 1(대표번호)만 허용
                if (!(digits.startsWith('0') || digits.startsWith('1'))) return false;

                // 1로 시작: 대표번호(1XXX-XXXX)로 진행 가능
                if (digits.startsWith('1')) return true;

                // 0으로 시작: 두 자리 접두 제한
                if (digits.length === 1) return true;
                const p2 = digits.slice(0, 2);
                const ok2 =
                    (p2 === '01') || // 휴대폰(01X)
                    (p2 === '02') || // 서울
                    (p2 === '07') || // 070(3자리에서 확정)
                    (p2 === '08') || // 080(3자리에서 확정)
                    (p2 >= '03' && p2 <= '06'); // 지역번호 대역
                if (!ok2) return false;

                // 07/08은 3자리에서 070/080으로 확정되어야 함
                if (digits.length >= 3) {
                    const p3 = digits.slice(0, 3);
                    if (p2 === '07' && p3 !== '070') return false;
                    if (p2 === '08' && p3 !== '080') return false;
                }

                return true;
            }
            
            phoneInput.addEventListener('input', function(e) {
                if (isFormatting) return;
                isFormatting = true;
                
                let value = e.target.value;
                const cursorPosition = e.target.selectionStart;
                
                try {
                    // 쉼표로 구분된 전화번호들을 각각 포맷팅
                    if (value.includes(',')) {
                        // 쉼표로 분리
                        const phoneList = value.split(',');
                        const formattedList = [];
                        
                        for (let i = 0; i < phoneList.length; i++) {
                            const phone = phoneList[i].trim();
                            if (phone) {
                                // 숫자만 추출하여 길이 확인
                                let numbers = phone.replace(/[^\d]/g, '');

                                // prefix 규칙 위반이면 전체 입력을 마지막 정상값으로 롤백
                                if (!isValidPhonePrefixProgress(numbers)) {
                                    e.target.value = lastValidPhoneValue;
                                    isFormatting = false;
                                    return;
                                }
                                
                                // 각 전화번호 유형별 최대 길이 결정 및 제한 (우선순위: 전국대표번호 > 02 > 기타)
                                let maxLength = 11;
                                
                                // 전국대표번호(1XXX-XXXX)는 8자리로 제한 (서버 규칙과 동일)
                                if (numbers.startsWith('1')) {
                                    maxLength = 8; // 전국대표번호는 8자리
                                }
                                // 서울 지역번호는 10자리
                                else if (numbers.startsWith('02')) {
                                    maxLength = 10; // 서울 지역번호는 10자리
                                }
                                // 휴대폰은 11자리
                                else if (numbers.startsWith('010') || numbers.startsWith('011') || 
                                         numbers.startsWith('016') || numbers.startsWith('017') || 
                                         numbers.startsWith('018') || numbers.startsWith('019')) {
                                    maxLength = 11; // 휴대폰은 11자리
                                }
                                // 인터넷전화는 11자리
                                else if (numbers.startsWith('070') || numbers.startsWith('080')) {
                                    maxLength = 11; // 인터넷전화는 11자리
                                }
                                // 지역번호 3자리로 시작하는 경우
                                else if (numbers.startsWith('0') && numbers.length >= 3) {
                                    maxLength = 11; // 지역번호는 최대 11자리
                                }
                                
                                // 최대 길이 초과 시 자르기
                                if (numbers.length > maxLength) {
                                    numbers = numbers.slice(0, maxLength);
                                }
                                
                                if (numbers.length > 0) {
                                    formattedList.push(formatPhoneNumber(numbers, false));
                                }
                            } else if (i === phoneList.length - 1) {
                                // 마지막 항목이 빈 값인 경우 (입력 중)
                                formattedList.push('');
                            }
                        }
                        
                        // 쉼표와 공백으로 조인 (빈 값 제거)
                        const validPhones = formattedList.filter(p => p.trim());
                        const result = validPhones.join(', ');
                        
                        e.target.value = result;
                    } else {
                        // 단일 전화번호 포맷팅 - 길이 제한 적용
                        let numbers = value.replace(/[^\d]/g, '');

                        // prefix 규칙 위반이면 마지막 정상값으로 롤백
                        if (!isValidPhonePrefixProgress(numbers)) {
                            e.target.value = lastValidPhoneValue;
                            isFormatting = false;
                            return;
                        }
                        
                        // 각 전화번호 유형별 최대 길이 결정 (우선순위: 전국대표번호 > 02 > 기타)
                        let maxLength = 11;
                        
                        // 전국대표번호(1XXX-XXXX)는 8자리로 제한 (서버 규칙과 동일)
                        if (numbers.startsWith('1')) {
                            maxLength = 8; // 전국대표번호는 8자리
                        }
                        // 서울 지역번호는 10자리
                        else if (numbers.startsWith('02')) {
                            maxLength = 10; // 서울 지역번호는 10자리
                        }
                        // 휴대폰은 11자리
                        else if (numbers.startsWith('010') || numbers.startsWith('011') || 
                                 numbers.startsWith('016') || numbers.startsWith('017') || 
                                 numbers.startsWith('018') || numbers.startsWith('019')) {
                            maxLength = 11; // 휴대폰은 11자리
                        }
                        // 인터넷전화는 11자리
                        else if (numbers.startsWith('070') || numbers.startsWith('080')) {
                            maxLength = 11; // 인터넷전화는 11자리
                        }
                        // 지역번호 3자리로 시작하는 경우
                        else if (numbers.startsWith('0') && numbers.length >= 3) {
                            maxLength = 11; // 최대 11자리
                        }
                        
                        // 최대 길이 초과 시 자르기
                        if (numbers.length > maxLength) {
                            numbers = numbers.slice(0, maxLength);
                        }
                        
                        const oldValue = e.target.value;
                        const newValue = formatPhoneNumber(numbers, false);
                        
                        // 포맷팅 후에도 검증하여 올바른 형식으로 재포맷팅
                        const formattedNumbers = newValue.replace(/[^\d]/g, '');
                        
                        // 전국대표번호(1XXX-XXXX)는 8자리로 제한 (서버 규칙과 동일)
                        if (formattedNumbers.startsWith('1')) {
                            if (formattedNumbers.length > 8) {
                                const correctedNumbers = formattedNumbers.slice(0, 8);
                                e.target.value = formatPhoneNumber(correctedNumbers, false);
                            } else {
                                e.target.value = newValue;
                            }
                        }
                        // 02로 시작하는 경우 10자리로 제한
                        else if (formattedNumbers.startsWith('02')) {
                            if (formattedNumbers.length > 10) {
                                const correctedNumbers = formattedNumbers.slice(0, 10);
                                e.target.value = formatPhoneNumber(correctedNumbers, false);
                            } else {
                                e.target.value = newValue;
                            }
                        }
                        // 지역번호 3자리로 시작하는 경우 11자리로 제한
                        else if (formattedNumbers.startsWith('0') && formattedNumbers.length >= 3) {
                            if (formattedNumbers.length > 11) {
                                const correctedNumbers = formattedNumbers.slice(0, 11);
                                e.target.value = formatPhoneNumber(correctedNumbers, false);
                            } else {
                                e.target.value = newValue;
                            }
                        }
                        else {
                            e.target.value = newValue;
                        }
                        
                        // 커서 위치 조정
                        const finalValue = e.target.value;
                        const diff = finalValue.length - oldValue.length;
                        const newCursorPosition = Math.max(0, Math.min(finalValue.length, cursorPosition + diff));
                        setTimeout(() => {
                            e.target.setSelectionRange(newCursorPosition, newCursorPosition);
                        }, 0);
                    }
                } catch (error) {
                    console.error('전화번호 포맷팅 오류:', error);
                }
                
                // 정상 포맷팅이 끝난 경우 마지막 정상값 갱신
                lastValidPhoneValue = e.target.value;
                isFormatting = false;
            });
            
            // keypress 기반 차단은 브라우저/키반복에서 일관성이 떨어져서 제거
            // (input 이벤트에서 길이 제한 + 포맷팅으로 강제 고정)
            
            // 블러 시 검증 및 오류 표시
            phoneInput.addEventListener('blur', function(e) {
                const value = e.target.value.trim();
                const formGroup = e.target.closest('.form-group');
                let errorMessage = formGroup ? formGroup.querySelector('.phone-error-message') : null;
                
                if (!value) {
                    e.target.classList.remove('error');
                    if (errorMessage) errorMessage.style.display = 'none';
                    return;
                }
                
                // 쉼표로 구분된 전화번호 검증
                const phoneList = value.split(',').map(p => p.trim()).filter(p => p.length > 0);
                let hasError = false;
                let errorMsg = '';
                
                if (phoneList.length === 0) {
                    hasError = true;
                    errorMsg = '전화번호를 입력해주세요.';
                } else {
                    for (let i = 0; i < phoneList.length; i++) {
                        const phoneItem = phoneList[i];
                        const validation = validatePhoneNumber(phoneItem);
                        
                        if (!validation.valid) {
                            hasError = true;
                            errorMsg = validation.message || '전화번호 형식이 올바르지 않습니다.';
                            break;
                        }
                    }
                }
                
                if (hasError) {
                    e.target.classList.add('error');
                    if (!errorMessage) {
                        errorMessage = document.createElement('div');
                        errorMessage.className = 'phone-error-message';
                        errorMessage.style.cssText = 'font-size: 12px; color: #ef4444; margin-top: 4px;';
                        formGroup.appendChild(errorMessage);
                    }
                    errorMessage.textContent = errorMsg || '전화번호 형식이 올바르지 않습니다.';
                    errorMessage.style.display = 'block';
                } else {
                    e.target.classList.remove('error');
                    if (errorMessage) errorMessage.style.display = 'none';
                }
            });
            
            // 입력 중 실시간 검증 (일정 시간 후)
            let validationTimeout = null;
            phoneInput.addEventListener('input', function(e) {
                // 기존 타이머 취소
                if (validationTimeout) {
                    clearTimeout(validationTimeout);
                }
                
                // 1초 후 검증 실행
                validationTimeout = setTimeout(function() {
                    const value = e.target.value.trim();
                    if (!value) {
                        e.target.classList.remove('error');
                        const formGroup = e.target.closest('.form-group');
                        if (formGroup) {
                            const errorMessage = formGroup.querySelector('.phone-error-message');
                            if (errorMessage) errorMessage.style.display = 'none';
                        }
                        return;
                    }
                    
                    // 쉼표로 구분된 전화번호 실시간 검증
                    const phoneList = value.split(',').map(p => p.trim()).filter(p => p.length > 0);
                    let hasError = false;
                    
                    for (let i = 0; i < phoneList.length; i++) {
                        const phoneItem = phoneList[i];
                        // 입력 중인 마지막 번호는 제외 (아직 입력 중일 수 있음)
                        if (i === phoneList.length - 1 && value.endsWith(',')) {
                            continue;
                        }
                        const validation = validatePhoneNumber(phoneItem);
                        if (!validation.valid) {
                            hasError = true;
                            break;
                        }
                    }
                    
                    if (hasError) {
                        e.target.classList.add('error');
                    } else {
                        e.target.classList.remove('error');
                    }
                }, 1000);
            });
            
            // 포커스 시 오류 표시 제거
            phoneInput.addEventListener('focus', function(e) {
                e.target.classList.remove('error');
                const formGroup = e.target.closest('.form-group');
                if (formGroup) {
                    const errorMessage = formGroup.querySelector('.phone-error-message');
                    if (errorMessage) errorMessage.style.display = 'none';
                }
            });
            
            // 붙여넣기 이벤트 처리 (과도한 입력 방지)
            phoneInput.addEventListener('paste', function(e) {
                e.preventDefault();
                
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                const currentValue = e.target.value;
                const cursorPosition = e.target.selectionStart;
                
                // 현재 위치 기준으로 붙여넣을 위치 결정
                const beforeText = currentValue.substring(0, cursorPosition);
                const afterText = currentValue.substring(e.target.selectionEnd);
                
                // 붙여넣은 텍스트를 현재 값에 삽입
                const newValue = beforeText + pastedText + afterText;
                
                // 포맷팅 적용
                setTimeout(() => {
                    if (isFormatting) return;
                    isFormatting = true;
                    
                    try {
                        if (newValue.includes(',')) {
                            const phoneList = newValue.split(',');
                            const formattedList = [];
                            
                            for (let i = 0; i < phoneList.length; i++) {
                                const phone = phoneList[i].trim();
                                if (phone) {
                                    formattedList.push(formatPhoneNumber(phone, false));
                                }
                            }
                            
                            e.target.value = formattedList.filter(p => p.trim()).join(', ');
                        } else {
                            e.target.value = formatPhoneNumber(newValue, false);
                        }
                    } catch (error) {
                        console.error('전화번호 붙여넣기 오류:', error);
                    }
                    
                    isFormatting = false;
                }, 0);
            });
        }
        
        // 휴대폰 필드 (010으로 시작하는 번호만 허용)
        const mobileInput = document.querySelector('input[name="mobile"]');
        if (mobileInput) {
            mobileInput.addEventListener('input', function(e) {
                // 숫자만 추출
                let numbers = e.target.value.replace(/[^\d]/g, '');
                
                // 010으로 시작하지 않으면 입력 제한
                if (numbers.length > 0) {
                    // 첫 번째 숫자가 0이 아니면 무시
                    if (numbers.length === 1 && numbers[0] !== '0') {
                        e.target.value = '';
                        return;
                    }
                    // 두 번째 숫자가 1이 아니면 무시
                    if (numbers.length === 2 && numbers[1] !== '1') {
                        e.target.value = numbers[0];
                        return;
                    }
                    // 세 번째 숫자가 0이 아니면 무시
                    if (numbers.length === 3 && numbers[2] !== '0') {
                        e.target.value = numbers.slice(0, 2);
                        return;
                    }
                    // 010으로 시작하지 않는 경우 (4자리 이상에서 체크)
                    if (numbers.length >= 4 && !numbers.startsWith('010')) {
                        // 010으로 시작하지 않으면 010까지만 유지
                        numbers = '010';
                    }
                }
                
                const cursorPosition = e.target.selectionStart;
                const oldValue = e.target.value;
                // 010으로 시작하는 번호만 포맷팅
                const newValue = formatPhoneNumber(numbers, true);
                
                e.target.value = newValue;
                
                // 커서 위치 조정
                const diff = newValue.length - oldValue.length;
                const newCursorPosition = Math.max(0, Math.min(newValue.length, cursorPosition + diff));
                e.target.setSelectionRange(newCursorPosition, newCursorPosition);
            });
            
            // 블러 시 형식 검증
            mobileInput.addEventListener('blur', function(e) {
                const phoneNumbers = e.target.value.replace(/[^\d]/g, '');
                if (phoneNumbers.length > 0 && (!/^010\d{8}$/.test(phoneNumbers))) {
                    e.target.style.borderColor = '#ef4444';
                } else {
                    e.target.style.borderColor = '';
                }
            });
        }
        
        // 이메일 입력 필드 변경 시 처리
        const emailLocalInput = document.getElementById('email_local');
        if (emailLocalInput) {
            emailLocalInput.addEventListener('input', function() {
                // 20자 제한
                if (this.value.length > 20) {
                    this.value = this.value.slice(0, 20);
                }
                updateEmailField();
            });
        }
        
        const emailCustomInput = document.getElementById('email_custom');
        if (emailCustomInput) {
            emailCustomInput.addEventListener('input', function() {
                updateEmailField();
            });
        }
        
        // 페이지 로드 시 이메일 도메인 선택 상태 확인
        const emailDomainSelect = document.getElementById('email_domain');
        if (emailDomainSelect && emailDomainSelect.value === 'custom') {
            const emailCustomInput = document.getElementById('email_custom');
            if (emailCustomInput) {
                emailCustomInput.style.display = 'block';
                emailCustomInput.required = true;
            }
        }
        
        // 초기 이메일 필드 업데이트
        updateEmailField();
        
        // 판매자명 중복 검사
        let sellerNameCheckTimeout = null;
        let sellerNameValid = true; // 초기값은 true (기존 값이 있는 경우)
        const sellerNameInput = document.getElementById('seller_name');
        const sellerNameResult = document.getElementById('seller_name_check_result');
        const initialSellerName = sellerNameInput ? sellerNameInput.value.trim() : '';
        
        if (sellerNameInput && sellerNameResult) {
            function checkSellerNameDuplicate(value) {
                // 기존 값과 동일하면 검사 스킵
                if (value === initialSellerName) {
                    sellerNameResult.innerHTML = '';
                    sellerNameResult.className = 'seller-name-check-result';
                    sellerNameInput.classList.remove('checked-valid', 'checked-invalid');
                    sellerNameValid = true;
                    return;
                }
                
                // 빈 값이면 검사 스킵
                if (value === '') {
                    sellerNameResult.innerHTML = '';
                    sellerNameResult.className = 'seller-name-check-result';
                    sellerNameInput.classList.remove('checked-valid', 'checked-invalid');
                    sellerNameValid = true; // 빈 값은 허용
                    return;
                }
                
                sellerNameResult.innerHTML = '<span>확인 중...</span>';
                sellerNameResult.className = 'seller-name-check-result checking';
                
                const currentUserId = '<?php echo htmlspecialchars($seller['user_id'] ?? ''); ?>';
                fetch(`/MVNO/api/check-seller-duplicate.php?type=seller_name&value=${encodeURIComponent(value)}&current_user_id=${encodeURIComponent(currentUserId)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && !data.duplicate) {
                            sellerNameResult.innerHTML = '<span>✓ ' + data.message + '</span>';
                            sellerNameResult.className = 'seller-name-check-result success';
                            sellerNameInput.classList.remove('checked-invalid');
                            sellerNameInput.classList.add('checked-valid');
                            sellerNameValid = true;
                        } else {
                            sellerNameResult.innerHTML = '<span>✗ ' + (data.message || '이미 사용 중인 판매자명입니다.') + '</span>';
                            sellerNameResult.className = 'seller-name-check-result error';
                            sellerNameInput.classList.remove('checked-valid');
                            sellerNameInput.classList.add('checked-invalid');
                            sellerNameValid = false;
                        }
                    })
                    .catch(error => {
                        console.error('판매자명 중복 검사 오류:', error);
                        sellerNameResult.innerHTML = '<span style="color: #ef4444;">검사 중 오류가 발생했습니다.</span>';
                        sellerNameResult.className = 'seller-name-check-result error';
                        sellerNameValid = false;
                    });
            }
            
            sellerNameInput.addEventListener('input', function() {
                const value = this.value.trim();
                
                // 기존 타이머 취소
                if (sellerNameCheckTimeout) {
                    clearTimeout(sellerNameCheckTimeout);
                }
                
                // 500ms 후 중복 검사 실행 (디바운싱)
                sellerNameCheckTimeout = setTimeout(() => {
                    checkSellerNameDuplicate(value);
                }, 500);
            });
            
            // 포커스 아웃 시 즉시 검사
            sellerNameInput.addEventListener('blur', function() {
                const value = this.value.trim();
                
                // 타이머가 있으면 취소하고 즉시 검사
                if (sellerNameCheckTimeout) {
                    clearTimeout(sellerNameCheckTimeout);
                    sellerNameCheckTimeout = null;
                }
                
                checkSellerNameDuplicate(value);
            });
        }
        
        // 폼 제출 시 검증
        const editForm = document.querySelector('.edit-form-card');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                // 이메일 검증
                const emailLocal = document.getElementById('email_local').value.trim();
                const emailDomain = document.getElementById('email_domain').value;
                const emailCustom = document.getElementById('email_custom').value.trim();
                const email = getCombinedEmail();
                
                if (!emailLocal) {
                    e.preventDefault();
                    alert('이메일 아이디를 입력해주세요.');
                    document.getElementById('email_local').focus();
                    return false;
                }
                
                if (!emailDomain) {
                    e.preventDefault();
                    alert('이메일 도메인을 선택해주세요.');
                    document.getElementById('email_domain').focus();
                    return false;
                }
                
                if (emailDomain === 'custom' && !emailCustom) {
                    e.preventDefault();
                    alert('이메일 도메인을 입력해주세요.');
                    document.getElementById('email_custom').focus();
                    return false;
                }
                
                if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    e.preventDefault();
                    alert('올바른 이메일 형식이 아닙니다.');
                    document.getElementById('email_local').focus();
                    return false;
                }
                
                // 휴대폰 검증
                const mobile = document.querySelector('input[name="mobile"]').value.trim();
                const mobileNumbers = mobile.replace(/[^\d]/g, '');
                if (!/^010\d{8}$/.test(mobileNumbers)) {
                    e.preventDefault();
                    alert('휴대폰 번호는 010으로 시작하는 11자리 숫자여야 합니다.');
                    document.querySelector('input[name="mobile"]').focus();
                    return false;
                }
                
                // 판매자명 중복 검사 확인
                const sellerNameInputCheck = document.getElementById('seller_name');
                if (sellerNameInputCheck) {
                    const currentSellerName = sellerNameInputCheck.value.trim();
                    // 판매자명이 변경되었고, 중복 검사를 통과하지 못한 경우
                    if (currentSellerName !== '' && currentSellerName !== initialSellerName) {
                        // 중복 검사 결과 확인 (input 필드의 클래스로 확인)
                        if (sellerNameInputCheck.classList.contains('checked-invalid')) {
                            e.preventDefault();
                            alert('판매자명 중복 검사를 통과하지 못했습니다. 다른 판매자명을 입력해주세요.');
                            sellerNameInputCheck.focus();
                            return false;
                        }
                    }
                }
                
                // 전화번호 검증 (입력된 경우에만)
                const phone = document.querySelector('input[name="phone"]').value.trim();
                if (phone) {
                    const phoneList = phone.split(',').map(p => p.trim()).filter(p => p.length > 0);
                    for (let i = 0; i < phoneList.length; i++) {
                        const phoneItem = phoneList[i];
                        const phoneNumbers = phoneItem.replace(/[^\d]/g, '');
                        const phoneLength = phoneNumbers.length;
                        
                        if (phoneLength < 8 || phoneLength > 11) {
                            e.preventDefault();
                            alert('전화번호 형식이 올바르지 않습니다. (각 번호는 8-11자리여야 합니다)');
                            document.querySelector('input[name="phone"]').focus();
                            return false;
                        }
                        
                        let isValidFormat = false;
                        if (phoneLength === 11 && /^01[0-9]\d{8}$/.test(phoneNumbers)) {
                            isValidFormat = true;
                        } else if (phoneLength === 10 && /^02\d{8}$/.test(phoneNumbers)) {
                            isValidFormat = true;
                        } else if (phoneLength === 10 && /^0[3-6]\d{8}$/.test(phoneNumbers)) {
                            isValidFormat = true;
                        } else if (phoneLength === 11 && /^0[3-6]\d{9}$/.test(phoneNumbers)) {
                            isValidFormat = true;
                        } else if (phoneLength === 11 && /^0[78]0\d{8}$/.test(phoneNumbers)) {
                            isValidFormat = true;
                        } else if (phoneLength === 8 && /^1\d{3}\d{4}$/.test(phoneNumbers)) {
                            isValidFormat = true;
                        }
                        
                        if (!isValidFormat) {
                            e.preventDefault();
                            alert('전화번호 형식이 올바르지 않습니다. (예: 02-1234-5678, 031-123-4567, 010-1234-5678, 1588-1234, 070-1234-5678)');
                            document.querySelector('input[name="phone"]').focus();
                            return false;
                        }
                    }
                }
            });
        }
    });
    
    // 저장 성공 모달 표시
    <?php if (isset($success_saved) && $success_saved): ?>
    document.addEventListener('DOMContentLoaded', function() {
        showSaveSuccessModal();
    });
    <?php endif; ?>
    
    function showSaveSuccessModal() {
        document.getElementById('saveSuccessModal').classList.add('active');
    }
    
    function closeSaveSuccessModal() {
        document.getElementById('saveSuccessModal').classList.remove('active');
    }
    
    function goToDetailPage() {
        window.location.href = '/MVNO/admin/users/seller-detail.php?user_id=<?php echo urlencode($seller['user_id']); ?>';
    }
</script>

<!-- 저장 성공 모달 -->
<div class="modal-overlay" id="saveSuccessModal">
    <div class="save-success-modal">
        <div class="save-success-modal-icon">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <div class="save-success-modal-title">저장 완료</div>
        <div class="save-success-modal-message">
            판매자 정보가 성공적으로 저장되었습니다.
        </div>
        <div class="save-success-modal-actions">
            <button type="button" class="modal-btn modal-btn-primary" onclick="goToDetailPage()">확인</button>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/admin-footer.php';
?>

