<?php
/**
 * 판매자 센터용 판매자 정보 수정 페이지
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

/**
 * 이미지 리사이징 및 압축 함수 (500MB 이하로 자동 축소)
 */
function compressImage($sourcePath, $targetPath, $maxSizeMB = 500) {
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
    
    // 최종 시도
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
        rename($tempPath, $targetPath);
    }
    
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    return true;
}

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 판매자 인증 체크
$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: /MVNO/seller/login.php');
    exit;
}

// 판매자 승인 상태 확인
$approvalStatus = $currentUser['approval_status'] ?? 'pending';
if ($approvalStatus !== 'approved') {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

// 판매자 정보 가져오기 (본인만)
$seller = getUserById($currentUser['user_id']);

if (!$seller || $seller['role'] !== 'seller') {
    header('Location: /MVNO/seller/profile.php');
    exit;
}

// 판매자 정보 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_seller'])) {
    $userId = $currentUser['user_id'];
    $seller = getUserById($userId);
    
    if (!$seller || $seller['role'] !== 'seller') {
        $error_message = '판매자를 찾을 수 없습니다.';
    } else {
        // 입력값 검증
        // 이메일 처리 (@ 앞부분과 뒷부분 분리 또는 전체 이메일)
        $emailLocal = trim($_POST['email_local'] ?? '');
        $emailDomain = trim($_POST['email_domain'] ?? '');
        $emailCustom = trim($_POST['email_custom'] ?? '');
        
        // 이메일 조합
        if (!empty($emailCustom)) {
            $email = $emailLocal . '@' . $emailCustom;
        } else {
            $email = $emailLocal . '@' . $emailDomain;
        }
        $email = trim($email);
        
        $phone = trim($_POST['phone'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        
        // 이메일 검증
        if (empty($emailLocal)) {
            $error_message = '이메일 아이디를 입력해주세요.';
        } elseif (strlen($emailLocal) > 20) {
            $error_message = '이메일 아이디는 20자 이내로 입력해주세요.';
        } elseif (empty($emailDomain)) {
            $error_message = '이메일 도메인을 선택해주세요.';
        } elseif ($emailDomain === 'custom' && empty($emailCustom)) {
            $error_message = '이메일 도메인을 입력해주세요.';
        } elseif (empty($email)) {
            $error_message = '이메일을 입력해주세요.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = '올바른 이메일 형식을 입력해주세요.';
        }
        
        // 전화번호 검증 (입력된 경우에만)
        if (empty($error_message) && !empty($phone)) {
            // 하이픈 제거 후 숫자만 추출
            $phoneNumbers = preg_replace('/[^0-9]/', '', $phone);
            $phoneLength = strlen($phoneNumbers);
            
            // 숫자 길이 검증
            if ($phoneLength < 8 || $phoneLength > 11) {
                $error_message = '전화번호 형식이 올바르지 않습니다. (예: 02-1234-5678, 031-123-4567, 010-1234-5678, 1644-1234)';
            } else {
                // 한국 전화번호 형식 검증
                $isValidFormat = false;
                
                // 휴대폰 (010, 011, 016, 017, 018, 019) - 11자리
                if ($phoneLength === 11 && preg_match('/^01[0-9]\d{8}$/', $phoneNumbers)) {
                    $isValidFormat = true;
                }
                // 02-XXXX-XXXX (서울, 10자리)
                elseif ($phoneLength === 10 && preg_match('/^02\d{8}$/', $phoneNumbers)) {
                    $isValidFormat = true;
                }
                // 0XX-XXX-XXXX (지역번호 3자리, 10자리)
                elseif ($phoneLength === 10 && preg_match('/^0[3-6]\d{8}$/', $phoneNumbers)) {
                    $isValidFormat = true;
                }
                // 0XX-XXXX-XXXX (일부 지역번호, 11자리)
                elseif ($phoneLength === 11 && preg_match('/^0[3-6]\d{9}$/', $phoneNumbers)) {
                    $isValidFormat = true;
                }
                // 070/080-XXXX-XXXX (인터넷전화, 11자리)
                elseif ($phoneLength === 11 && preg_match('/^0[78]0\d{8}$/', $phoneNumbers)) {
                    $isValidFormat = true;
                }
                // 전국대표번호 (8자리) - 15XX, 16XX, 18XX 등으로 시작하는 8자리 번호
                elseif ($phoneLength === 8 && preg_match('/^(15|16|18)\d{6}$/', $phoneNumbers)) {
                    $isValidFormat = true;
                }
                
                if (!$isValidFormat) {
                    $error_message = '전화번호 형식이 올바르지 않습니다. (예: 02-1234-5678, 031-123-4567, 010-1234-5678, 1644-1234)';
                }
            }
        }
        
        // 휴대폰번호 검증 (입력된 경우에만)
        if (empty($error_message) && !empty($mobile)) {
            // 하이픈 제거 후 숫자만 추출
            $mobileNumbers = preg_replace('/[^0-9]/', '', $mobile);
            
            // 휴대폰번호 형식 검증 (010으로 시작하는 11자리)
            if (strlen($mobileNumbers) !== 11) {
                $error_message = '휴대폰번호는 11자리 숫자여야 합니다. (예: 010-1234-5678)';
            } elseif (!preg_match('/^010/', $mobileNumbers)) {
                $error_message = '휴대폰번호는 010으로 시작해야 합니다. (예: 010-1234-5678)';
            }
        }
        
        // 수정할 정보 수집 (사업자등록번호는 변경 불가)
        if (empty($error_message)) {
            $updateData = [
                'name' => $_POST['name'] ?? $seller['name'],
                'email' => $email,
                'phone' => $phone,
                'mobile' => $mobile,
                'address' => $_POST['address'] ?? ($seller['address'] ?? ''),
                'address_detail' => $_POST['address_detail'] ?? ($seller['address_detail'] ?? ''),
                // 'business_number'는 변경 불가이므로 제외
                'company_name' => $_POST['company_name'] ?? ($seller['company_name'] ?? ''),
                'company_representative' => $_POST['company_representative'] ?? ($seller['company_representative'] ?? ''),
                'business_type' => $_POST['business_type'] ?? ($seller['business_type'] ?? ''),
                'business_item' => $_POST['business_item'] ?? ($seller['business_item'] ?? ''),
            ];
            
            // 비밀번호 변경이 있는 경우
            if (!empty($_POST['password'])) {
                if (strlen($_POST['password']) < 8) {
                    $error_message = '비밀번호는 최소 8자 이상이어야 합니다.';
                } else {
                    $updateData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
            }
            
            // 사업자등록증 이미지 업로드 처리
            if (empty($error_message) && isset($_FILES['business_license_image']) && $_FILES['business_license_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/sellers/';
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
                                if (!empty($seller['business_license_image']) && file_exists(__DIR__ . '/..' . $seller['business_license_image'])) {
                                    @unlink(__DIR__ . '/..' . $seller['business_license_image']);
                                }
                                
                                // 새 파일명 생성
                                $fileName = $userId . '_license_' . time() . '.' . $fileExtension;
                                $targetPath = $uploadDir . $fileName;
                                
                                // 이미지 압축 및 저장 (500MB 이하로 자동 압축)
                                if (compressImage($tempPath, $targetPath)) {
                                    // 상대 경로 저장
                                    $updateData['business_license_image'] = '/MVNO/uploads/sellers/' . $fileName;
                                } else {
                                    $error_message = '이미지 업로드에 실패했습니다.';
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // 에러가 없을 때만 업데이트 진행
        if (empty($error_message)) {
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
                // 판매자 정보 업데이트
                $file = getSellersFilePath();
                if (file_exists($file)) {
                    $data = json_decode(file_get_contents($file), true) ?: ['sellers' => []];
                    $updated = false;
                    
                    foreach ($data['sellers'] as &$u) {
                        if ($u['user_id'] === $userId) {
                            // 기존 데이터에 업데이트 데이터 병합
                            foreach ($updateData as $key => $value) {
                                $u[$key] = $value;
                            }
                            // 우편번호 필드 제거 (더 이상 사용하지 않음)
                            if (isset($u['postal_code'])) {
                                unset($u['postal_code']);
                            }
                            $u['updated_at'] = date('Y-m-d H:i:s');
                            // 정보 업데이트 플래그 설정 (관리자 확인 전까지 유지)
                            $u['info_updated'] = true;
                            $u['info_updated_at'] = date('Y-m-d H:i:s');
                            $u['info_checked_by_admin'] = false; // 관리자 확인 전
                            $updated = true;
                            break;
                        }
                    }
                    
                    if ($updated) {
                        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        // 저장 성공 플래그 설정
                        $success_saved = true;
                        // 판매자 정보 다시 로드 (업데이트된 정보 반영)
                        $seller = getUserById($userId);
                    } else {
                        $error_message = '판매자 정보 업데이트에 실패했습니다.';
                    }
                } else {
                    $error_message = '판매자 데이터 파일을 찾을 수 없습니다.';
                }
            } else {
                // 변경 사항이 없으면 메시지 표시
                $error_message = '변경된 사항이 없습니다.';
            }
        }
    }
}

// 판매자 헤더 포함
require_once __DIR__ . '/includes/seller-header.php';
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
        gap: 12px;
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

<div class="seller-edit-container">
    <div class="edit-header">
        <h1>회원정보 수정</h1>
        <a href="/MVNO/seller/profile.php" class="back-button">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            내정보로
        </a>
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
            <h2 class="form-section-title">기본 정보</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">아이디</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($seller['user_id']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">이름 <span class="required">*</span></label>
                    <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($seller['name'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">이메일 <span class="required">*</span></label>
                    <div class="email-input-group" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                        <?php
                        $currentEmail = $seller['email'] ?? '';
                        $emailParts = explode('@', $currentEmail);
                        $emailLocalValue = count($emailParts) === 2 ? $emailParts[0] : '';
                        $emailDomainValue = count($emailParts) === 2 ? $emailParts[1] : '';
                        $commonDomains = ['naver.com', 'gmail.com', 'hanmail.net', 'nate.com'];
                        $isCustomDomain = !empty($emailDomainValue) && !in_array($emailDomainValue, $commonDomains);
                        ?>
                        <input type="text" id="email_local" name="email_local" class="form-input" value="<?php echo htmlspecialchars($emailLocalValue); ?>" required maxlength="20" style="flex: 1; min-width: 120px;" placeholder="이메일 아이디">
                        <span class="email-at" style="font-size: 16px; color: #6b7280; white-space: nowrap; padding: 0 4px;">@</span>
                        <select id="email_domain" name="email_domain" class="form-input" onchange="handleEmailDomainChange()" style="flex: 1; min-width: 150px;">
                            <option value="">선택하세요</option>
                            <option value="naver.com" <?php echo ($emailDomainValue === 'naver.com') ? 'selected' : ''; ?>>naver.com</option>
                            <option value="gmail.com" <?php echo ($emailDomainValue === 'gmail.com') ? 'selected' : ''; ?>>gmail.com</option>
                            <option value="hanmail.net" <?php echo ($emailDomainValue === 'hanmail.net') ? 'selected' : ''; ?>>hanmail.net</option>
                            <option value="nate.com" <?php echo ($emailDomainValue === 'nate.com') ? 'selected' : ''; ?>>nate.com</option>
                            <option value="custom" <?php echo $isCustomDomain ? 'selected' : ''; ?>>직접 입력</option>
                        </select>
                        <input type="text" id="email_custom" name="email_custom" class="form-input" value="<?php echo $isCustomDomain ? htmlspecialchars($emailDomainValue) : ''; ?>" placeholder="도메인 입력" style="flex: 1; min-width: 150px; display: <?php echo $isCustomDomain ? 'block' : 'none'; ?>;">
                    </div>
                    <input type="hidden" id="email" name="email" value="<?php echo htmlspecialchars($currentEmail); ?>">
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
                    <input type="tel" name="phone" id="phone" class="form-input" value="<?php echo htmlspecialchars($seller['phone'] ?? ''); ?>" placeholder="1588-1588, 02-1234-1234">
                </div>
                <div class="form-group">
                    <label class="form-label">휴대폰</label>
                    <input type="tel" name="mobile" id="mobile" class="form-input" value="<?php echo htmlspecialchars($seller['mobile'] ?? ''); ?>" placeholder="010-1234-5678">
                </div>
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
                    <input type="text" name="business_number" class="form-input" value="<?php echo htmlspecialchars($seller['business_number'] ?? ''); ?>" placeholder="123-45-67890" disabled>
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
            <a href="/MVNO/seller/profile.php" class="btn btn-secondary">취소</a>
            <button type="submit" class="btn btn-primary">저장</button>
        </div>
    </form>
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
    
    // 전화번호 하이픈 자동 입력
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
            // 전국대표번호 (15XX, 16XX, 18XX로 시작하는 8자리) - 8자리: 1655-4444
            else if (numbers.length >= 2 && (numbers.startsWith('15') || numbers.startsWith('16') || numbers.startsWith('18'))) {
                const limited = numbers.slice(0, 8);
                if (limited.length <= 4) {
                    return limited;
                } else {
                    // 4-4 형식: 1655-4444
                    return limited.slice(0, 4) + '-' + limited.slice(4, 8);
                }
            }
            // 인터넷전화 (070, 080) - 11자리
            else if (numbers.startsWith('070') || numbers.startsWith('080')) {
                const limited = numbers.slice(0, 11);
                if (limited.length <= 3) {
                    return limited;
                } else if (limited.length <= 7) {
                    return limited.slice(0, 3) + '-' + limited.slice(3);
                } else {
                    return limited.slice(0, 3) + '-' + limited.slice(3, 7) + '-' + limited.slice(7);
                }
            }
            // 서울 지역번호 (02) - 10자리: 02-XXXX-XXXX
            else if (numbers.startsWith('02')) {
                const limited = numbers.slice(0, 10);
                if (limited.length <= 2) {
                    return limited;
                } else if (limited.length <= 6) {
                    return limited.slice(0, 2) + '-' + limited.slice(2);
                } else {
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
        
        // 전화번호 필드
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                const cursorPosition = e.target.selectionStart;
                const oldValue = e.target.value;
                const newValue = formatPhoneNumber(e.target.value, false);
                
                e.target.value = newValue;
                
                // 커서 위치 조정
                const diff = newValue.length - oldValue.length;
                const newCursorPosition = Math.max(0, Math.min(newValue.length, cursorPosition + diff));
                e.target.setSelectionRange(newCursorPosition, newCursorPosition);
            });
        }
        
        // 휴대폰 필드 (010으로 시작하는 번호만 허용)
        const mobileInput = document.querySelector('input[name="mobile"]');
        if (mobileInput) {
            mobileInput.addEventListener('input', function(e) {
                // 숫자만 추출
                const numbers = e.target.value.replace(/[^\d]/g, '');
                
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
        }
        
        // 이메일 도메인 선택 변경 처리
        function handleEmailDomainChange() {
            const emailDomain = document.getElementById('email_domain');
            const emailCustom = document.getElementById('email_custom');
            
            if (emailDomain && emailCustom) {
                if (emailDomain.value === 'custom') {
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
        }
        
        // 이메일 조합 함수
        function getCombinedEmail() {
            const emailLocal = document.getElementById('email_local');
            const emailDomain = document.getElementById('email_domain');
            const emailCustom = document.getElementById('email_custom');
            
            if (!emailLocal || !emailDomain) {
                return '';
            }
            
            const emailLocalValue = emailLocal.value.trim();
            if (!emailLocalValue) {
                return '';
            }
            
            let domain = '';
            if (emailDomain.value === 'custom') {
                domain = emailCustom ? emailCustom.value.trim() : '';
            } else {
                domain = emailDomain.value;
            }
            
            if (!domain) {
                return '';
            }
            
            return emailLocalValue + '@' + domain;
        }
        
        // 이메일 필드 업데이트
        function updateEmailField() {
            const emailHidden = document.getElementById('email');
            if (emailHidden) {
                const combinedEmail = getCombinedEmail();
                emailHidden.value = combinedEmail;
            }
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
        
        // 이메일 도메인 선택 변경 시
        const emailDomainSelect = document.getElementById('email_domain');
        if (emailDomainSelect) {
            emailDomainSelect.addEventListener('change', function() {
                handleEmailDomainChange();
            });
        }
        
        // 초기 이메일 필드 업데이트
        updateEmailField();
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
    
    function goToProfilePage() {
        window.location.href = '/MVNO/seller/profile.php';
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
            회원정보가 성공적으로 저장되었습니다.
        </div>
        <div class="save-success-modal-actions">
            <button type="button" class="modal-btn modal-btn-primary" onclick="goToProfilePage()">확인</button>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/includes/seller-footer.php';
?>

