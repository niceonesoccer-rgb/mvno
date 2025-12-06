<?php
/**
 * 판매자 가입 페이지
 * 경로: /MVNO/seller/register.php
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
    
    // 품질 조정하여 저장 (반복적으로 크기 확인)
    $quality = 85; // 초기 품질
    $attempts = 0;
    $maxAttempts = 10;
    
    do {
        // 임시 파일에 저장
        $tempPath = $targetPath . '.tmp';
        
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($newImage, $tempPath, $quality);
                break;
            case 'image/png':
                // PNG는 품질 대신 압축 레벨 사용 (0-9, 9가 최대 압축)
                $compression = 9 - (int)(($quality - 50) / 5); // 85 -> 3, 50 -> 9
                $compression = max(0, min(9, $compression));
                imagepng($newImage, $tempPath, $compression);
                break;
            case 'image/gif':
                imagegif($newImage, $tempPath);
                break;
        }
        
        $newFileSize = filesize($tempPath);
        
        if ($newFileSize <= $maxSizeBytes) {
            // 목표 크기 이하이면 성공
            rename($tempPath, $targetPath);
            imagedestroy($sourceImage);
            imagedestroy($newImage);
            return true;
        }
        
        // 품질 낮추기
        $quality -= 10;
        $attempts++;
        
        // 품질이 너무 낮아지면 리사이징 크기 줄이기
        if ($quality < 50 && $attempts < $maxAttempts) {
            $scale *= 0.9; // 10% 더 축소
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
            $quality = 85; // 품질 다시 초기화
        }
        
        // 임시 파일 삭제
        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
        
    } while ($quality >= 30 && $attempts < $maxAttempts);
    
    // 최종 시도 (최소 품질로)
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
    
    return file_exists($targetPath);
}

// 이미 로그인한 경우 리다이렉트
if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    if ($currentUser && $currentUser['role'] === 'seller') {
        header('Location: /MVNO/seller/');
        exit;
    } else {
        header('Location: /MVNO/');
        exit;
    }
}

$error = '';
$registerSuccess = false;
$registeredData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = strtolower(trim($_POST['user_id'] ?? '')); // 소문자로 변환
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
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
    
    $name = trim($_POST['name'] ?? '');
    
    // 기본 필드 검증
    if (empty($userId) || empty($password) || empty($email) || empty($name)) {
        $error = '모든 필드를 입력해주세요.';
    } elseif (!preg_match('/^[a-z0-9]{5,20}$/', $userId)) {
        $error = '아이디는 소문자 영문자와 숫자 조합 5-20자로 입력해주세요.';
    } elseif (strlen($emailLocal) > 20) {
        $error = '이메일 아이디는 20자 이내로 입력해주세요.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '올바른 이메일 형식이 아닙니다.';
    } elseif ($password !== $passwordConfirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } elseif (strlen($password) < 8) {
        $error = '비밀번호는 최소 8자 이상이어야 합니다.';
    } else {
        // 판매자 추가 정보 수집
        $additionalData = [];
        $additionalData['phone'] = trim($_POST['phone'] ?? '');
        $additionalData['mobile'] = trim($_POST['mobile'] ?? '');
        $additionalData['postal_code'] = trim($_POST['postal_code'] ?? '');
        $additionalData['address'] = trim($_POST['address'] ?? '');
        $additionalData['address_detail'] = trim($_POST['address_detail'] ?? '');
        $additionalData['business_number'] = trim($_POST['business_number'] ?? '');
        $additionalData['company_name'] = trim($_POST['company_name'] ?? '');
        $additionalData['company_representative'] = trim($_POST['company_representative'] ?? '');
        $additionalData['business_type'] = trim($_POST['business_type'] ?? '');
        $additionalData['business_item'] = trim($_POST['business_item'] ?? '');
        
        // 판매자 필수 필드 확인
        if (empty($additionalData['business_number']) || empty($additionalData['company_name'])) {
            $error = '사업자등록번호와 회사명은 필수 입력 항목입니다.';
        } elseif (mb_strlen($additionalData['company_name']) > 20) {
            $error = '회사명은 20자 이내로 입력해주세요.';
        } elseif (!empty($additionalData['company_representative']) && mb_strlen($additionalData['company_representative']) > 20) {
            $error = '대표자명은 20자 이내로 입력해주세요.';
        } elseif (!empty($additionalData['business_type']) && mb_strlen($additionalData['business_type']) > 20) {
            $error = '업종은 20자 이내로 입력해주세요.';
        } elseif (!empty($additionalData['business_item']) && mb_strlen($additionalData['business_item']) > 20) {
            $error = '업태는 20자 이내로 입력해주세요.';
        } else {
            // 휴대폰 번호 검증 (입력된 경우에만)
            if (!empty($additionalData['mobile'])) {
                $mobileNumbers = preg_replace('/[^\d]/', '', $additionalData['mobile']);
                if (!preg_match('/^010\d{8}$/', $mobileNumbers)) {
                    $error = '휴대폰 번호는 010으로 시작하는 11자리 숫자여야 합니다.';
                }
            }
            
            // 전화번호 형식 검증 (입력된 경우에만)
            if (empty($error) && !empty($additionalData['phone'])) {
                $phoneNumbers = preg_replace('/[^\d]/', '', $additionalData['phone']);
                $phoneLength = strlen($phoneNumbers);
                
                // 숫자 길이 검증
                if ($phoneLength < 8 || $phoneLength > 11) {
                    $error = '전화번호 형식이 올바르지 않습니다.';
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
                    // 1588/1544/1577/1600 등 (전국대표번호, 8자리)
                    elseif ($phoneLength === 8 && preg_match('/^(1588|1544|1577|1600|1800|1566|1599|1644)\d{4}$/', $phoneNumbers)) {
                        $isValidFormat = true;
                    }
                    
                    if (!$isValidFormat) {
                        $error = '전화번호 형식이 올바르지 않습니다. (예: 02-1234-5678, 031-123-4567, 010-1234-5678, 1644-1234)';
                    }
                }
            }
            
            if (empty($error)) {
                // 사업자등록증 이미지 업로드 처리
                if (isset($_FILES['business_license_image']) && $_FILES['business_license_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../uploads/sellers/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileExtension = pathinfo($_FILES['business_license_image']['name'], PATHINFO_EXTENSION);
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
                        $error = '허용되지 않은 파일 형식입니다. (JPG, PNG, GIF만 가능)';
                    } else {
                        $fileName = $userId . '_license_' . time() . '.' . $fileExtension;
                        $uploadPath = $uploadDir . $fileName;
                        $tmpPath = $_FILES['business_license_image']['tmp_name'];
                        
                        // 이미지 리사이징 및 압축 (500MB 이하로)
                        if (compressImage($tmpPath, $uploadPath, 500)) {
                            $additionalData['business_license_image'] = '/MVNO/uploads/sellers/' . $fileName;
                        } else {
                            $error = '이미지 처리에 실패했습니다.';
                        }
                    }
                } else {
                    $error = '사업자등록증 이미지를 업로드해주세요.';
                }
            }
        }
        
        if (empty($error)) {
            $result = registerDirectUser($userId, $password, $email, $name, 'seller', $additionalData);
            if ($result['success']) {
                // 가입 성공 - 리다이렉트하지 않고 정보 표시
                $registerSuccess = true;
                $registeredData = [
                    'user_id' => $userId,
                    'email' => $email,
                    'name' => $name,
                    'phone' => $additionalData['phone'] ?? '',
                    'mobile' => $additionalData['mobile'] ?? '',
                    'address' => $additionalData['address'] ?? '',
                    'address_detail' => $additionalData['address_detail'] ?? '',
                    'business_number' => $additionalData['business_number'] ?? '',
                    'company_name' => $additionalData['company_name'] ?? '',
                    'company_representative' => $additionalData['company_representative'] ?? '',
                    'business_type' => $additionalData['business_type'] ?? '',
                    'business_item' => $additionalData['business_item'] ?? '',
                ];
            } else {
                $error = $result['message'];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>판매자 가입 - 모요</title>
    <link rel="stylesheet" href="/MVNO/assets/css/style.css">
    <script src="/MVNO/assets/js/modal.js" defer></script>

<style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px 0;
    }
    
    .seller-register-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 40px 24px;
    }
    
    .register-header {
        text-align: center;
        margin-bottom: 48px;
        animation: fadeInDown 0.6s ease-out;
    }
    
    .register-header h1 {
        font-size: 42px;
        font-weight: 800;
        color: #ffffff;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        margin-bottom: 16px;
        letter-spacing: -0.5px;
    }
    
    .register-header p {
        font-size: 18px;
        color: #ffffff;
        font-weight: 500;
        text-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
    }
    
    .error-message {
        padding: 18px 20px;
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #991b1b;
        border-radius: 12px;
        margin-bottom: 28px;
        font-size: 14px;
        border: 1px solid #fca5a5;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
        animation: shake 0.5s ease-in-out;
    }
    
    .success-message {
        padding: 18px 20px;
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #065f46;
        border-radius: 12px;
        margin-bottom: 28px;
        font-size: 14px;
        border: 1px solid #6ee7b7;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        text-align: center;
        animation: fadeInUp 0.5s ease-out;
    }
    
    .register-form {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        border-radius: 24px;
        padding: 48px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.2);
        animation: fadeInUp 0.6s ease-out;
    }
    
    .form-section {
        margin-bottom: 40px;
    }
    
    .form-section-title {
        font-size: 22px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 28px;
        padding-bottom: 16px;
        border-bottom: 3px solid;
        border-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%) 1;
        position: relative;
    }
    
    .form-section-title::after {
        content: '';
        position: absolute;
        bottom: -3px;
        left: 0;
        width: 60px;
        height: 3px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-group label {
        display: block;
        font-size: 15px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 10px;
        letter-spacing: -0.2px;
    }
    
    .form-group label .required {
        color: #ef4444;
        margin-left: 4px;
        font-weight: 700;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 15px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-sizing: border-box;
        background: #ffffff;
    }
    
    .form-group input:hover,
    .form-group select:hover,
    .form-group textarea:hover {
        border-color: #c7d2fe;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        transform: translateY(-1px);
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
    
    .form-help {
        font-size: 13px;
        color: #6b7280;
        margin-top: 8px;
        font-weight: 400;
    }
    
    .file-upload-area {
        border: 3px dashed #d1d5db;
        border-radius: 16px;
        padding: 40px 24px;
        text-align: center;
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        position: relative;
    }
    
    .file-upload-area:hover {
        border-color: #667eea;
        background: linear-gradient(135deg, #f0f4ff 0%, #e8edff 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
    }
    
    .file-upload-area.drag-over {
        border-color: #667eea;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        transform: scale(1.02);
        box-shadow: 0 12px 32px rgba(102, 126, 234, 0.25);
    }
    
    .file-upload-area.has-file {
        border-color: #10b981;
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.15);
    }
    
    .file-upload-input {
        display: none;
    }
    
    .file-upload-label {
        cursor: pointer;
        display: block;
    }
    
    .file-upload-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        color: #667eea;
        transition: all 0.3s ease;
    }
    
    .file-upload-area:hover .file-upload-icon {
        transform: scale(1.1);
        color: #764ba2;
    }
    
    .file-upload-text {
        font-size: 16px;
        color: #374151;
        margin-bottom: 10px;
        font-weight: 600;
    }
    
    .file-upload-hint {
        font-size: 13px;
        color: #6b7280;
    }
    
    .file-preview {
        margin-top: 20px;
        padding: 20px;
        background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        border-radius: 16px;
        border: 2px solid #e5e7eb;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .file-preview img {
        max-width: 100%;
        max-height: 300px;
        border-radius: 12px;
        display: block;
        margin: 0 auto;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }
    
    .file-delete-btn {
        padding: 8px 18px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
        letter-spacing: 0.2px;
    }
    
    .file-delete-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.35);
    }
    
    .file-delete-btn:active {
        transform: translateY(0);
    }
    
    .register-button {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 17px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        margin-top: 32px;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        letter-spacing: 0.3px;
    }
    
    .register-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
    }
    
    .register-button:active {
        transform: translateY(0);
    }
    
    .register-button:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }
    
    .login-link {
        text-align: center;
        margin-top: 32px;
        font-size: 15px;
        color: #ffffff;
        font-weight: 500;
        text-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
    }
    
    .login-link a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 700;
        padding: 8px 16px;
        border-radius: 8px;
        transition: all 0.3s ease;
        display: inline-block;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    
    .login-link a:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
    }
    
    /* 단계 표시 */
    .step-indicator {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 48px;
        padding: 0 20px;
        position: relative;
    }
    
    .step-item {
        flex: 1;
        text-align: center;
        position: relative;
        z-index: 2;
    }
    
    .step-number {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        font-weight: 700;
        font-size: 18px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border: 3px solid #ffffff;
    }
    
    .step-item.active .step-number {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: scale(1.1);
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    }
    
    .step-item.completed .step-number {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
    }
    
    .step-label {
        font-size: 15px;
        font-weight: 600;
        color: #6b7280;
        transition: all 0.3s ease;
    }
    
    .step-item.active .step-label {
        color: #667eea;
        font-weight: 700;
    }
    
    .step-item.completed .step-label {
        color: #10b981;
    }
    
    .step-line {
        flex: 1;
        height: 4px;
        background: linear-gradient(90deg, #e5e7eb 0%, #d1d5db 100%);
        margin: 0 10px;
        margin-top: -28px;
        transition: all 0.4s ease;
        border-radius: 2px;
    }
    
    .step-line.completed {
        background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    
    .step-content {
        display: none;
    }
    
    .step-content.active {
        display: block;
    }
    
    .step-buttons {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-top: 32px;
    }
    
    .btn-step {
        flex: 1;
        padding: 16px 28px;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: 0.3px;
    }
    
    .btn-prev {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        color: #374151;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .btn-prev:hover {
        background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    
    .btn-next,
    .btn-submit {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    }
    
    .btn-next:hover,
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
    }
    
    .btn-step:active {
        transform: translateY(0);
    }
    
    .btn-step:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    /* 중복확인 버튼 */
    .check-duplicate-btn {
        padding: 14px 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
        letter-spacing: 0.2px;
    }
    
    .check-duplicate-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.35);
    }
    
    .check-duplicate-btn:active {
        transform: translateY(0);
    }
    
    .check-duplicate-btn:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }
    
    .check-result {
        margin-top: 8px;
        font-size: 13px;
        font-weight: 500;
    }
    
    .check-result.success {
        color: #10b981;
    }
    
    .check-result.error {
        color: #ef4444;
    }
    
    .check-result.checking {
        color: #6b7280;
    }
    
    input.checked-valid {
        border-color: #10b981;
        background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
    }
    
    input.checked-invalid {
        border-color: #ef4444;
        background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%);
    }
    
    /* 가입 성공 화면 스타일 */
    .register-success-container {
        max-width: 900px;
        margin: 0 auto;
        animation: fadeInUp 0.6s ease-out;
    }
    
    .register-info-card {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        border-radius: 24px;
        padding: 48px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 32px;
    }
    
    .info-card-title {
        font-size: 28px;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 32px;
        padding-bottom: 16px;
        border-bottom: 3px solid;
        border-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%) 1;
        position: relative;
    }
    
    .info-card-title::after {
        content: '';
        position: absolute;
        bottom: -3px;
        left: 0;
        width: 80px;
        height: 3px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .info-item.full-width {
        grid-column: 1 / -1;
    }
    
    .info-label {
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-value {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        word-break: break-word;
    }
    
    .home-button {
        display: inline-block;
        padding: 14px 32px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #ffffff;
        text-decoration: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 700;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        letter-spacing: 0.3px;
    }
    
    .home-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
        color: #ffffff;
    }
    
    .home-button:active {
        transform: translateY(0);
    }
    
    /* 애니메이션 */
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    @media (max-width: 768px) {
        .seller-register-container {
            padding: 24px 16px;
        }
        
        .register-form {
            padding: 32px 24px;
            border-radius: 20px;
        }
        
        .register-header h1 {
            font-size: 32px;
        }
        
        .register-header p {
            font-size: 16px;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .step-indicator {
            padding: 0 10px;
        }
        
        .step-number {
            width: 48px;
            height: 48px;
            font-size: 16px;
        }
        
        .step-label {
            font-size: 12px;
        }
    }
</style>
</head>
<body>
<main class="main-content">
    <div class="seller-register-container">
        <div class="register-header">
            <h1>판매자 가입</h1>
            <p>모요 판매자로 가입하여 상품을 등록하고 판매하세요</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($registerSuccess && $registeredData): ?>
            <div class="register-success-container">
                <div class="register-info-card">
                    <h3 class="info-card-title">가입 정보</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">아이디</span>
                            <span class="info-value"><?php echo htmlspecialchars($registeredData['user_id']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">이메일</span>
                            <span class="info-value"><?php echo htmlspecialchars($registeredData['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">이름</span>
                            <span class="info-value"><?php echo htmlspecialchars($registeredData['name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">회사명</span>
                            <span class="info-value"><?php echo htmlspecialchars($registeredData['company_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">사업자등록번호</span>
                            <span class="info-value"><?php echo htmlspecialchars($registeredData['business_number']); ?></span>
                        </div>
                        <?php if (!empty($registeredData['company_representative'])): ?>
                        <div class="info-item">
                            <span class="info-label">대표자명</span>
                            <span class="info-value"><?php echo htmlspecialchars($registeredData['company_representative']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($registeredData['mobile'])): ?>
                        <div class="info-item">
                            <span class="info-label">휴대폰</span>
                            <span class="info-value"><?php echo htmlspecialchars($registeredData['mobile']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($registeredData['phone'])): ?>
                        <div class="info-item">
                            <span class="info-label">전화번호</span>
                            <span class="info-value"><?php echo htmlspecialchars($registeredData['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($registeredData['address'])): ?>
                        <div class="info-item full-width">
                            <span class="info-label">주소</span>
                            <span class="info-value"><?php echo htmlspecialchars($registeredData['address']); ?> <?php echo htmlspecialchars($registeredData['address_detail'] ?? ''); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-item full-width" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb; text-align: center;">
                            <span class="info-value" style="color: #f59e0b; font-weight: 700; font-size: 18px;">승인대기중</span>
                            <div style="margin-top: 24px;">
                                <a href="/MVNO/" class="home-button">홈으로 이동</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif (!$registerSuccess): ?>
            <div class="register-form">
                <!-- 단계 표시 -->
                <div class="step-indicator" id="stepIndicator">
                    <div class="step-item active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-label">기본 정보</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-label">서류 업로드</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-label">사업자 정보</div>
                    </div>
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="registerForm">
                    <!-- 기본 정보 -->
                    <div class="form-section step-content active" id="step1">
                        <h2 class="form-section-title">1단계: 기본 정보</h2>
                        
                        <div class="form-group">
                            <label for="user_id">아이디 <span class="required">*</span></label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="user_id" name="user_id" required value="<?php echo htmlspecialchars($_POST['user_id'] ?? ''); ?>" style="flex: 1;" pattern="[a-z0-9]{5,20}" title="소문자 영문자와 숫자 조합 5-20자로 입력해주세요." minlength="5" maxlength="20">
                                <button type="button" id="checkUserIdBtn" class="check-duplicate-btn" onclick="checkDuplicate('user_id')">중복확인</button>
                            </div>
                            <div class="form-help">소문자 영문자와 숫자 조합 5-20자</div>
                            <div id="userIdCheckResult" class="check-result"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email_local">이메일 <span class="required">*</span></label>
                            <div class="email-input-group">
                                <input type="text" id="email_local" name="email_local" required maxlength="20"
                                       value="<?php echo htmlspecialchars(explode('@', $_POST['email'] ?? '')[0] ?? ''); ?>" 
                                       placeholder="이메일 아이디">
                                <span class="email-at">@</span>
                                <select id="email_domain" name="email_domain" onchange="handleEmailDomainChange()">
                                    <option value="">선택하세요</option>
                                    <option value="naver.com" <?php echo (isset($_POST['email_domain']) && $_POST['email_domain'] === 'naver.com') || (isset($_POST['email']) && strpos($_POST['email'], '@naver.com') !== false) ? 'selected' : ''; ?>>naver.com</option>
                                    <option value="gmail.com" <?php echo (isset($_POST['email_domain']) && $_POST['email_domain'] === 'gmail.com') || (isset($_POST['email']) && strpos($_POST['email'], '@gmail.com') !== false) ? 'selected' : ''; ?>>gmail.com</option>
                                    <option value="hanmail.net" <?php echo (isset($_POST['email_domain']) && $_POST['email_domain'] === 'hanmail.net') || (isset($_POST['email']) && strpos($_POST['email'], '@hanmail.net') !== false) ? 'selected' : ''; ?>>hanmail.net</option>
                                    <option value="nate.com" <?php echo (isset($_POST['email_domain']) && $_POST['email_domain'] === 'nate.com') || (isset($_POST['email']) && strpos($_POST['email'], '@nate.com') !== false) ? 'selected' : ''; ?>>nate.com</option>
                                    <option value="custom">직접 입력</option>
                                </select>
                                <input type="text" id="email_custom" name="email_custom" 
                                       value="<?php 
                                       $emailParts = explode('@', $_POST['email'] ?? '');
                                       if (count($emailParts) === 2) {
                                           $domain = $emailParts[1];
                                           $commonDomains = ['naver.com', 'gmail.com', 'hanmail.net', 'nate.com'];
                                           if (!in_array($domain, $commonDomains)) {
                                               echo htmlspecialchars($domain);
                                           }
                                       }
                                       ?>" 
                                       placeholder="도메인 입력" 
                                       style="display: none;">
                                <button type="button" id="checkEmailBtn" class="check-duplicate-btn" onclick="checkDuplicate('email')">중복확인</button>
                            </div>
                            <input type="hidden" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            <div id="emailCheckResult" class="check-result"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">이름 <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">비밀번호 <span class="required">*</span></label>
                            <input type="password" id="password" name="password" required minlength="8">
                            <div class="form-help">최소 8자 이상 입력해주세요.</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirm">비밀번호 확인 <span class="required">*</span></label>
                            <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                        </div>
                    </div>
                    
                    <!-- 사업자등록증 -->
                    <div class="form-section step-content" id="step2" style="display: none;">
                        <h2 class="form-section-title">2단계: 사업자등록증 업로드 <span class="required">*</span></h2>
                        
                        <div class="form-group">
                            <label>사업자등록증 이미지</label>
                            <div class="file-upload-area" id="fileUploadArea">
                                <input type="file" id="business_license_image" name="business_license_image" accept="image/*" class="file-upload-input" required>
                                <label for="business_license_image" class="file-upload-label">
                                    <svg class="file-upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="17 8 12 3 7 8"/>
                                        <line x1="12" y1="3" x2="12" y2="15"/>
                                    </svg>
                                    <div class="file-upload-text">클릭하거나 파일을 드래그하여 업로드</div>
                                    <div class="file-upload-hint">JPG, PNG, GIF (이미지 파일만 가능)</div>
                                </label>
                            </div>
                            <div id="filePreview" class="file-preview" style="display: none;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">
                                    <strong style="font-size: 16px; color: #1f2937; font-weight: 700;">업로드된 파일</strong>
                                    <button type="button" onclick="removeFile()" class="file-delete-btn">삭제</button>
                                </div>
                                <div id="previewContent"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 사업자 정보 -->
                    <div class="form-section step-content" id="step3" style="display: none;">
                        <h2 class="form-section-title">3단계: 사업자 정보</h2>
                        
                        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 24px 0 16px 0;">사업자 등록 정보</h3>
                        
                        <div class="form-group">
                            <label for="business_number">사업자등록번호 <span class="required">*</span></label>
                            <input type="text" id="business_number" name="business_number" placeholder="123-45-67890" required value="<?php echo htmlspecialchars($_POST['business_number'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="company_name">회사명 <span class="required">*</span></label>
                            <input type="text" id="company_name" name="company_name" placeholder="(주)회사명" required maxlength="20" value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>">
                            <div class="form-help">20자 이내로 입력해주세요.</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="company_representative">대표자명</label>
                            <input type="text" id="company_representative" name="company_representative" placeholder="홍길동" maxlength="20" value="<?php echo htmlspecialchars($_POST['company_representative'] ?? ''); ?>">
                            <div class="form-help">20자 이내로 입력해주세요.</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="business_type">업종</label>
                            <input type="text" id="business_type" name="business_type" placeholder="도매 및 소매업" maxlength="20" value="<?php echo htmlspecialchars($_POST['business_type'] ?? ''); ?>">
                            <div class="form-help">20자 이내로 입력해주세요.</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="business_item">업태</label>
                            <input type="text" id="business_item" name="business_item" placeholder="통신판매업" maxlength="20" value="<?php echo htmlspecialchars($_POST['business_item'] ?? ''); ?>">
                            <div class="form-help">20자 이내로 입력해주세요.</div>
                        </div>
                        
                        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 24px 0 16px 0;">주소 정보</h3>
                        
                        <div class="form-group">
                            <label for="address">주소</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="address" name="address" placeholder="서울시 강남구" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" style="flex: 1;" readonly>
                                <button type="button" id="searchAddressBtn" class="check-duplicate-btn" onclick="searchAddress()">주소 검색</button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address_detail">상세주소</label>
                            <input type="text" id="address_detail" name="address_detail" placeholder="상세주소를 입력하세요" value="<?php echo htmlspecialchars($_POST['address_detail'] ?? ''); ?>">
                        </div>
                        
                        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 24px 0 16px 0;">연락처 정보</h3>
                        
                        <div class="form-group">
                            <label for="mobile">휴대폰</label>
                            <input type="tel" id="mobile" name="mobile" placeholder="010-1234-5678" value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">전화번호</label>
                            <input type="tel" id="phone" name="phone" placeholder="02-1234-5678, 031-123-4567, 010-1234-5678" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            <div class="form-help">예: 02-1234-5678, 031-123-4567, 010-1234-5678, 070-1234-5678</div>
                        </div>
                    </div>
                    
                    <!-- 단계별 버튼 -->
                    <div class="step-buttons">
                        <button type="button" class="btn-step btn-prev" id="prevBtn" style="display: none;" onclick="prevStep()">이전</button>
                        <button type="button" class="btn-step btn-next" id="nextBtn" onclick="nextStep()">다음</button>
                        <button type="submit" class="btn-step btn-submit" id="submitBtn" style="display: none;">판매자 가입 신청</button>
                    </div>
                </form>
                
                <div class="login-link">
                    이미 계정이 있으신가요? <a href="/MVNO/seller/login.php">로그인</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    let currentStep = 1;
    const totalSteps = 3;
    
    // 단계 이동 함수
    function showStep(step) {
        // 모든 단계 숨기기
        document.querySelectorAll('.step-content').forEach(content => {
            content.classList.remove('active');
            content.style.display = 'none';
        });
        
        // 현재 단계 표시
        const stepContent = document.getElementById('step' + step);
        if (stepContent) {
            stepContent.classList.add('active');
            stepContent.style.display = 'block';
        }
        
        // 단계 표시기 업데이트
        document.querySelectorAll('.step-item').forEach((item, index) => {
            const stepNum = index + 1;
            item.classList.remove('active', 'completed');
            
            if (stepNum < step) {
                item.classList.add('completed');
            } else if (stepNum === step) {
                item.classList.add('active');
            }
        });
        
        // 단계 라인 업데이트
        document.querySelectorAll('.step-line').forEach((line, index) => {
            if (index + 1 < step) {
                line.classList.add('completed');
            } else {
                line.classList.remove('completed');
            }
        });
        
        // 버튼 표시/숨김
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        
        if (step === 1) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'block';
            submitBtn.style.display = 'none';
        } else if (step === totalSteps) {
            prevBtn.style.display = 'block';
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'block';
        } else {
            prevBtn.style.display = 'block';
            nextBtn.style.display = 'block';
            submitBtn.style.display = 'none';
        }
        
        currentStep = step;
    }
    
    // 다음 단계
    function nextStep() {
        if (validateStep(currentStep)) {
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        }
    }
    
    // 이전 단계
    function prevStep() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
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
        
        // 중복확인 상태 초기화
        emailChecked = false;
        emailValid = false;
        document.getElementById('email_local').classList.remove('checked-valid', 'checked-invalid');
        document.getElementById('emailCheckResult').innerHTML = '';
        document.getElementById('emailCheckResult').className = 'check-result';
    }
    
    // 이메일 필드 업데이트
    function updateEmailField() {
        const combinedEmail = getCombinedEmail();
        document.getElementById('email').value = combinedEmail;
    }
    
    // 중복확인
    let userIdChecked = false;
    let emailChecked = false;
    let userIdValid = false;
    let emailValid = false;
    
    function checkDuplicate(type) {
        let value = '';
        let input = null;
        
        if (type === 'email') {
            value = getCombinedEmail();
            input = document.getElementById('email_local');
        } else {
            input = document.getElementById(type);
            value = input.value.trim();
        }
        
        const checkBtn = document.getElementById('check' + (type === 'user_id' ? 'UserId' : 'Email') + 'Btn');
        const resultDiv = document.getElementById((type === 'user_id' ? 'userId' : 'email') + 'CheckResult');
        
        if (!value) {
            showAlert(type === 'user_id' ? '아이디를 입력해주세요.' : '이메일을 입력해주세요.').then(() => {
                if (type === 'email') {
                    document.getElementById('email_local').focus();
                } else {
                    input.focus();
                }
            });
            return;
        }
        
        // 이메일 형식 검증
        if (type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                resultDiv.innerHTML = '<span class="error">올바른 이메일 형식이 아닙니다.</span>';
                resultDiv.className = 'check-result error';
                document.getElementById('email_local').classList.remove('checked-valid');
                document.getElementById('email_local').classList.add('checked-invalid');
                emailChecked = true;
                emailValid = false;
                return;
            }
        }
        
        // 아이디 형식 검증 (소문자 영문자와 숫자 조합 5-20자)
        if (type === 'user_id') {
            const userIdRegex = /^[a-z0-9]{5,20}$/;
            if (!userIdRegex.test(value)) {
                resultDiv.innerHTML = '<span class="error">소문자 영문자와 숫자 조합 5-20자로 입력해주세요.</span>';
                resultDiv.className = 'check-result error';
                input.classList.remove('checked-valid');
                input.classList.add('checked-invalid');
                userIdChecked = true;
                userIdValid = false;
                return;
            }
            
            // 금지된 아이디는 서버에서 체크하므로 클라이언트 측 사전 검증은 생략
        }
        
        // 중복확인 중
        checkBtn.disabled = true;
        checkBtn.textContent = '확인 중...';
        resultDiv.innerHTML = '<span class="checking">확인 중...</span>';
        resultDiv.className = 'check-result checking';
        
        fetch(`/MVNO/api/check-seller-duplicate.php?type=${type}&value=${encodeURIComponent(value)}`)
            .then(response => response.json())
            .then(data => {
                checkBtn.disabled = false;
                checkBtn.textContent = '중복확인';
                
                if (data.success && !data.duplicate) {
                    resultDiv.innerHTML = '<span class="success">✓ ' + data.message + '</span>';
                    resultDiv.className = 'check-result success';
                    if (type === 'email') {
                        document.getElementById('email_local').classList.remove('checked-invalid');
                        document.getElementById('email_local').classList.add('checked-valid');
                    } else {
                        input.classList.remove('checked-invalid');
                        input.classList.add('checked-valid');
                    }
                    
                    if (type === 'user_id') {
                        userIdChecked = true;
                        userIdValid = true;
                    } else {
                        emailChecked = true;
                        emailValid = true;
                    }
                } else {
                    resultDiv.innerHTML = '<span class="error">✗ ' + data.message + '</span>';
                    resultDiv.className = 'check-result error';
                    if (type === 'email') {
                        document.getElementById('email_local').classList.remove('checked-valid');
                        document.getElementById('email_local').classList.add('checked-invalid');
                    } else {
                        input.classList.remove('checked-valid');
                        input.classList.add('checked-invalid');
                    }
                    
                    if (type === 'user_id') {
                        userIdChecked = true;
                        userIdValid = false;
                    } else {
                        emailChecked = true;
                        emailValid = false;
                    }
                }
            })
            .catch(error => {
                checkBtn.disabled = false;
                checkBtn.textContent = '중복확인';
                resultDiv.innerHTML = '<span class="error">확인 중 오류가 발생했습니다.</span>';
                resultDiv.className = 'check-result error';
                console.error('Error:', error);
            });
    }
    
    // 아이디/이메일 입력 시 중복확인 상태 초기화 및 소문자 영문자와 숫자만 입력 제한
    document.getElementById('user_id').addEventListener('input', function(e) {
        // 소문자 영문자와 숫자만 허용 (대문자는 소문자로 변환)
        this.value = this.value.replace(/[^a-z0-9]/gi, '').toLowerCase();
        
        userIdChecked = false;
        userIdValid = false;
        this.classList.remove('checked-valid', 'checked-invalid');
        document.getElementById('userIdCheckResult').innerHTML = '';
        document.getElementById('userIdCheckResult').className = 'check-result';
    });
    
    // 이메일 입력 필드 변경 시 처리 (20자 제한)
    document.getElementById('email_local').addEventListener('input', function() {
        // 20자 제한
        if (this.value.length > 20) {
            this.value = this.value.slice(0, 20);
        }
        updateEmailField();
        emailChecked = false;
        emailValid = false;
        this.classList.remove('checked-valid', 'checked-invalid');
        document.getElementById('emailCheckResult').innerHTML = '';
        document.getElementById('emailCheckResult').className = 'check-result';
    });
    
    document.getElementById('email_custom').addEventListener('input', function() {
        updateEmailField();
        emailChecked = false;
        emailValid = false;
        document.getElementById('email_local').classList.remove('checked-valid', 'checked-invalid');
        document.getElementById('emailCheckResult').innerHTML = '';
        document.getElementById('emailCheckResult').className = 'check-result';
    });
    
    // 단계별 유효성 검사
    function validateStep(step) {
        let isValid = true;
        
        if (step === 1) {
            const userId = document.getElementById('user_id').value.trim();
            const emailLocal = document.getElementById('email_local').value.trim();
            const emailDomain = document.getElementById('email_domain').value;
            const emailCustom = document.getElementById('email_custom').value.trim();
            const email = getCombinedEmail();
            const name = document.getElementById('name').value.trim();
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            if (!userId) {
                showAlert('아이디를 입력해주세요.').then(() => {
                    document.getElementById('user_id').focus();
                });
                isValid = false;
            } else if (!userIdChecked) {
                showAlert('아이디 중복확인을 해주세요.').then(() => {
                    document.getElementById('user_id').focus();
                });
                isValid = false;
            } else if (!userIdValid) {
                showAlert('사용 가능한 아이디를 입력해주세요.').then(() => {
                    document.getElementById('user_id').focus();
                });
                isValid = false;
            } else if (!emailLocal) {
                showAlert('이메일 아이디를 입력해주세요.').then(() => {
                    document.getElementById('email_local').focus();
                });
                isValid = false;
            } else if (emailLocal.length > 20) {
                showAlert('이메일 아이디는 20자 이내로 입력해주세요.').then(() => {
                    document.getElementById('email_local').focus();
                });
                isValid = false;
            } else if (!emailDomain) {
                showAlert('이메일 도메인을 선택해주세요.').then(() => {
                    document.getElementById('email_domain').focus();
                });
                isValid = false;
            } else if (emailDomain === 'custom' && !emailCustom) {
                showAlert('이메일 도메인을 입력해주세요.').then(() => {
                    document.getElementById('email_custom').focus();
                });
                isValid = false;
            } else if (!emailChecked) {
                showAlert('이메일 중복확인을 해주세요.').then(() => {
                    document.getElementById('email_local').focus();
                });
                isValid = false;
            } else if (!emailValid) {
                showAlert('사용 가능한 이메일을 입력해주세요.').then(() => {
                    document.getElementById('email_local').focus();
                });
                isValid = false;
            } else if (!name) {
                showAlert('이름을 입력해주세요.').then(() => {
                    document.getElementById('name').focus();
                });
                isValid = false;
            } else if (password.length < 8) {
                showAlert('비밀번호는 최소 8자 이상이어야 합니다.').then(() => {
                    document.getElementById('password').focus();
                });
                isValid = false;
            } else if (password !== passwordConfirm) {
                showAlert('비밀번호가 일치하지 않습니다.').then(() => {
                    document.getElementById('password_confirm').focus();
                });
                isValid = false;
            }
        } else if (step === 2) {
            const fileInput = document.getElementById('business_license_image');
            if (!fileInput.files || fileInput.files.length === 0) {
                showAlert('사업자등록증 이미지를 업로드해주세요.');
                isValid = false;
            } else {
                const file = fileInput.files[0];
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showAlert('이미지 파일만 업로드 가능합니다. (JPG, PNG, GIF)');
                    isValid = false;
                }
            }
        } else if (step === 3) {
            const businessNumber = document.getElementById('business_number').value.trim();
            const companyName = document.getElementById('company_name').value.trim();
            const companyRepresentative = document.getElementById('company_representative').value.trim();
            const businessType = document.getElementById('business_type').value.trim();
            const businessItem = document.getElementById('business_item').value.trim();
            
            if (!businessNumber) {
                showAlert('사업자등록번호를 입력해주세요.').then(() => {
                    document.getElementById('business_number').focus();
                });
                isValid = false;
            } else if (!companyName) {
                showAlert('회사명을 입력해주세요.').then(() => {
                    document.getElementById('company_name').focus();
                });
                isValid = false;
            } else if (companyName.length > 20) {
                showAlert('회사명은 20자 이내로 입력해주세요.').then(() => {
                    document.getElementById('company_name').focus();
                });
                isValid = false;
            } else if (companyRepresentative.length > 20) {
                showAlert('대표자명은 20자 이내로 입력해주세요.').then(() => {
                    document.getElementById('company_representative').focus();
                });
                isValid = false;
            } else if (businessType.length > 20) {
                showAlert('업종은 20자 이내로 입력해주세요.').then(() => {
                    document.getElementById('business_type').focus();
                });
                isValid = false;
            } else if (businessItem.length > 20) {
                showAlert('업태는 20자 이내로 입력해주세요.').then(() => {
                    document.getElementById('business_item').focus();
                });
                isValid = false;
            } else {
                // 휴대폰 검증 (입력된 경우에만)
                const mobile = document.getElementById('mobile').value.trim();
                if (mobile) {
                    const mobileNumbers = mobile.replace(/[^\d]/g, '');
                    if (!mobileNumbers.startsWith('010')) {
                        showAlert('휴대폰 번호는 010으로 시작해야 합니다.').then(() => {
                            document.getElementById('mobile').focus();
                        });
                        isValid = false;
                    } else if (mobileNumbers.length !== 11) {
                        showAlert('휴대폰 번호는 11자리여야 합니다.').then(() => {
                            document.getElementById('mobile').focus();
                        });
                        isValid = false;
                    }
                }
                
                // 전화번호 검증 (입력된 경우에만)
                const phone = document.getElementById('phone').value.trim();
                if (phone) {
                    const phoneNumbers = phone.replace(/[^\d]/g, '');
                    const phoneLength = phoneNumbers.length;
                    
                    // 숫자 길이 검증
                    if (phoneLength < 8 || phoneLength > 11) {
                        showAlert('전화번호 형식이 올바르지 않습니다.').then(() => {
                            document.getElementById('phone').focus();
                        });
                        isValid = false;
                    } else {
                        // 한국 전화번호 형식 검증
                        let isValidFormat = false;
                        
                        // 휴대폰 (010, 011, 016, 017, 018, 019) - 11자리
                        if (phoneLength === 11 && /^01[0-9]\d{8}$/.test(phoneNumbers)) {
                            isValidFormat = true;
                        }
                        // 02-XXXX-XXXX (서울, 10자리)
                        else if (phoneLength === 10 && /^02\d{8}$/.test(phoneNumbers)) {
                            isValidFormat = true;
                        }
                        // 0XX-XXX-XXXX (지역번호 3자리, 10자리)
                        else if (phoneLength === 10 && /^0[3-6]\d{8}$/.test(phoneNumbers)) {
                            isValidFormat = true;
                        }
                        // 0XX-XXXX-XXXX (일부 지역번호, 11자리)
                        else if (phoneLength === 11 && /^0[3-6]\d{9}$/.test(phoneNumbers)) {
                            isValidFormat = true;
                        }
                        // 070/080-XXXX-XXXX (인터넷전화, 11자리)
                        else if (phoneLength === 11 && /^0[78]0\d{8}$/.test(phoneNumbers)) {
                            isValidFormat = true;
                        }
                        // 1588/1544/1577/1600 등 (전국대표번호, 8자리)
                        else if (phoneLength === 8 && /^(1588|1544|1577|1600|1800|1566|1599|1644)\d{4}$/.test(phoneNumbers)) {
                            isValidFormat = true;
                        }
                        
                        if (!isValidFormat) {
                            showAlert('전화번호 형식이 올바르지 않습니다. (예: 02-1234-5678, 031-123-4567, 010-1234-5678)').then(() => {
                                document.getElementById('phone').focus();
                            });
                            isValid = false;
                        }
                    }
                }
            }
        }
        
        return isValid;
    }
    
    // 초기화
    showStep(1);
    
    // 페이지 로드 시 이메일 도메인 선택 상태 확인
    const emailDomain = document.getElementById('email_domain');
    if (emailDomain.value === 'custom') {
        document.getElementById('email_custom').style.display = 'block';
        document.getElementById('email_custom').required = true;
    }
    
    // 이메일 필드 초기 업데이트
    updateEmailField();
    
    // 파일 처리 함수 (공통)
    function handleFile(file) {
        const uploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('business_license_image');
        const preview = document.getElementById('filePreview');
        const previewContent = document.getElementById('previewContent');
        
        if (!file) return;
        
        // 이미지 파일만 허용
        if (!file.type.startsWith('image/')) {
            showAlert('이미지 파일만 업로드 가능합니다. (JPG, PNG, GIF)');
            fileInput.value = ''; // 파일 선택 초기화
            uploadArea.classList.remove('has-file', 'drag-over');
            preview.style.display = 'none';
            previewContent.innerHTML = '';
            return;
        }
        
        // FileList 객체 생성하여 input에 할당
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;
        
        uploadArea.classList.add('has-file');
        uploadArea.classList.remove('drag-over');
        preview.style.display = 'block';
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewContent.innerHTML = '<img src="' + e.target.result + '" alt="사업자등록증 미리보기">';
        };
        reader.readAsDataURL(file);
        
        // OCR 처리하여 정보 자동 입력
        extractBusinessInfo(file);
    }
    
    // 파일 업로드 미리보기 및 OCR 처리 (클릭)
    document.getElementById('business_license_image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        handleFile(file);
    });
    
    // 드래그 앤 드롭 기능
    const uploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('business_license_image');
    
    // 드래그 오버 이벤트
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.add('drag-over');
    });
    
    // 드래그 리브 이벤트
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.remove('drag-over');
    });
    
    // 드롭 이벤트
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    });
    
    // 전체 페이지에서 드래그 오버 방지
    document.addEventListener('dragover', function(e) {
        e.preventDefault();
    });
    
    document.addEventListener('drop', function(e) {
        e.preventDefault();
    });
    
    // 사업자등록증에서 정보 추출
    function extractBusinessInfo(file) {
        // 로딩 표시
        const previewContent = document.getElementById('previewContent');
        const loadingMsg = '<div style="padding: 20px; text-align: center; color: #6366f1;"><strong>정보 추출 중...</strong><br><span style="font-size: 12px;">잠시만 기다려주세요.</span></div>';
        previewContent.innerHTML = loadingMsg;
        
        const formData = new FormData();
        formData.append('image', file);
        
        fetch('/MVNO/api/extract-business-info.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('서버 응답 오류: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // 미리보기 이미지 다시 표시
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContent.innerHTML = '<img src="' + e.target.result + '" alt="사업자등록증 미리보기">';
            };
            reader.readAsDataURL(file);
            
            if (data.success && data.data) {
                const info = data.data;
                let filledCount = 0;
                
                // 추출된 정보를 입력 필드에 자동 입력
                if (info.business_number && info.business_number.trim()) {
                    document.getElementById('business_number').value = formatBusinessNumber(info.business_number.trim());
                    filledCount++;
                }
                if (info.company_name && info.company_name.trim()) {
                    document.getElementById('company_name').value = info.company_name.trim();
                    filledCount++;
                }
                if (info.representative && info.representative.trim()) {
                    document.getElementById('company_representative').value = info.representative.trim();
                    filledCount++;
                }
                if (info.business_type && info.business_type.trim()) {
                    document.getElementById('business_type').value = info.business_type.trim();
                    filledCount++;
                }
                if (info.business_item && info.business_item.trim()) {
                    document.getElementById('business_item').value = info.business_item.trim();
                    filledCount++;
                }
                if (info.address && info.address.trim()) {
                    document.getElementById('address').value = info.address.trim();
                    filledCount++;
                }
                
                if (filledCount > 0) {
                    showAlert('정보가 자동으로 입력되었습니다. (' + filledCount + '개 필드) 확인 후 수정해주세요.');
                } else {
                    console.log('OCR 응답:', data);
                    // 정보가 추출되지 않았지만 성공 응답인 경우
                    if (data.message) {
                        console.log('OCR 메시지:', data.message);
                    }
                }
            } else {
                console.error('OCR 처리 실패:', data);
                // 미리보기 이미지는 이미 표시됨
            }
        })
        .catch(error => {
            console.error('OCR 처리 오류:', error);
            // 미리보기 이미지 다시 표시
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContent.innerHTML = '<img src="' + e.target.result + '" alt="사업자등록증 미리보기">';
            };
            reader.readAsDataURL(file);
        });
    }
    
    function removeFile() {
        document.getElementById('business_license_image').value = '';
        document.getElementById('fileUploadArea').classList.remove('has-file');
        document.getElementById('filePreview').style.display = 'none';
        document.getElementById('previewContent').innerHTML = '';
    }
    
    // 비밀번호 확인
    document.getElementById('password_confirm').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const passwordConfirm = this.value;
        
        if (password !== passwordConfirm && passwordConfirm.length > 0) {
            this.setCustomValidity('비밀번호가 일치하지 않습니다.');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // 폼 제출 시 최종 검증
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        if (!validateStep(3)) {
            e.preventDefault();
            showStep(3);
        }
    });
    
    // 주소 검색 (다음 우편번호 API)
    function searchAddress() {
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
                // 커서를 상세주소 필드로 이동
                document.getElementById('address_detail').focus();
            }
        }).open();
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
            // 전국대표번호 4자리 (1588, 1544, 1577, 1600 등) - 8자리: 1588-1234
            else if (numbers.startsWith('1588') || numbers.startsWith('1544') || 
                numbers.startsWith('1577') || numbers.startsWith('1600') ||
                numbers.startsWith('1800') || numbers.startsWith('1566') ||
                numbers.startsWith('1599') || numbers.startsWith('1644')) {
                const limited = numbers.slice(0, 8);
                if (limited.length <= 4) {
                    return limited;
                } else {
                    // 4-4 형식: 1588-1234
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
    
    // 사업자등록번호 입력 필드에 이벤트 리스너 추가
    document.getElementById('business_number').addEventListener('input', function(e) {
        const cursorPosition = e.target.selectionStart;
        const oldValue = e.target.value;
        const newValue = formatBusinessNumber(e.target.value);
        
        e.target.value = newValue;
        
        // 커서 위치 조정
        const diff = newValue.length - oldValue.length;
        const newCursorPosition = cursorPosition + diff;
        e.target.setSelectionRange(newCursorPosition, newCursorPosition);
    });
    
    // 휴대폰 입력 필드에 이벤트 리스너 추가
    document.getElementById('mobile').addEventListener('input', function(e) {
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
                e.target.value = numbers.slice(0, 3);
                return;
            }
        }
        
        const cursorPosition = e.target.selectionStart;
        const oldValue = e.target.value;
        const newValue = formatPhoneNumber(e.target.value, true);
        
        e.target.value = newValue;
        
        // 커서 위치 조정
        const diff = newValue.length - oldValue.length;
        const newCursorPosition = cursorPosition + diff;
        e.target.setSelectionRange(newCursorPosition, newCursorPosition);
    });
    
    // 전화번호 입력 필드에 이벤트 리스너 추가
    document.getElementById('phone').addEventListener('input', function(e) {
        const cursorPosition = e.target.selectionStart;
        const oldValue = e.target.value;
        const newValue = formatPhoneNumber(e.target.value, false);
        
        e.target.value = newValue;
        
        // 커서 위치 조정
        const diff = newValue.length - oldValue.length;
        const newCursorPosition = cursorPosition + diff;
        e.target.setSelectionRange(newCursorPosition, newCursorPosition);
    });
</script>
<!-- 다음 우편번호 API 스크립트 -->
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>

</body>
</html>

