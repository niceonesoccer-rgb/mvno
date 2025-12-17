<?php
/**
 * 회원가입 페이지
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 이미 로그인한 경우 리다이렉트
if (isLoggedIn()) {
    // 세션에 저장된 리다이렉트 URL 확인
    $redirectUrl = $_SESSION['redirect_url'] ?? '/MVNO/';
    if (isset($_SESSION['redirect_url'])) {
        unset($_SESSION['redirect_url']);
    }
    header('Location: ' . $redirectUrl);
    exit;
}

// 회원가입 페이지 접근 시 현재 URL을 리다이렉트 URL로 저장 (GET 파라미터로 전달된 경우)
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_url'] = $_GET['redirect'];
} elseif (!isset($_SESSION['redirect_url'])) {
    // 리다이렉트 URL이 없으면 이전 페이지 URL 저장
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (!empty($referer) && strpos($referer, '/auth/login.php') === false && strpos($referer, '/auth/register.php') === false) {
        $_SESSION['redirect_url'] = $referer;
    }
}

$error = '';
$success = false;
$registeredUser = null;
$redirectUrl = $_SESSION['redirect_url'] ?? '/MVNO/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'user';
    $userId = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // 직접 가입 (일반 회원, 관리자, 서브관리자, 판매자)
    if (true) {
        // 필수 필드 검증: 아이디, 전화번호, 이름, 이메일
        if (empty($userId) || empty($phone) || empty($name) || empty($email)) {
            $error = '아이디, 휴대폰번호, 이름, 이메일은 필수 입력 항목입니다.';
        } elseif (!preg_match('/^[A-Za-z0-9]{5,20}$/', $userId)) {
            $error = '아이디는 영문과 숫자만 사용할 수 있으며 5자 이상 20자 이내여야 합니다.';
        } elseif (mb_strlen($name) > 15) {
            $error = '이름은 15자 이내로 입력해주세요.';
        } elseif (!preg_match('/^010-\d{4}-\d{4}$/', $phone) || !str_starts_with($phone, '010-')) {
            $error = '휴대폰번호는 010으로 시작하는 번호만 가능합니다. (010-XXXX-XXXX 형식)';
        } elseif (strlen($email) > 20) {
            $error = '이메일 주소는 20자 이내로 입력해주세요.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '올바른 이메일 형식이 아닙니다.';
        } else {
            // 이메일 로컬 부분 검증 (영문 소문자, 숫자만)
            $emailParts = explode('@', $email);
            if (count($emailParts) !== 2) {
                $error = '올바른 이메일 형식이 아닙니다.';
            } else {
                $emailLocal = $emailParts[0];
                if (!preg_match('/^[a-z0-9]+$/', $emailLocal)) {
                    $error = '이메일 아이디는 영문 소문자와 숫자만 사용할 수 있습니다.';
                } else {
                    // 도메인 형식 검증 (직접입력 허용을 위해 도메인 제한 제거)
                    $emailDomain = strtolower($emailParts[1]);
                    // 도메인 형식 검증: 영문 소문자, 숫자, 점, 하이픈만 허용
                    if (!preg_match('/^[a-z0-9.-]+$/', $emailDomain)) {
                        $error = '올바른 이메일 도메인 형식이 아닙니다.';
                    } elseif (strpos($emailDomain, '.') === false) {
                        $error = '올바른 이메일 도메인 형식이 아닙니다.';
                    } elseif (strpos($emailDomain, '.') === 0 || substr($emailDomain, -1) === '.') {
                        $error = '올바른 이메일 도메인 형식이 아닙니다.';
                    }
                }
            }
        }
        
        if (empty($error)) {
            if ($password !== $passwordConfirm) {
                $error = '비밀번호가 일치하지 않습니다.';
            } elseif (strlen($password) < 8 || strlen($password) > 20) {
                $error = '비밀번호는 8자 이상 20자 이내로 입력해주세요.';
            } else {
                // 영문자(대소문자 구분 없이), 숫자, 특수문자 중 2가지 이상 조합 확인
                $hasLetter = preg_match('/[A-Za-z]/', $password);
                $hasNumber = preg_match('/[0-9]/', $password);
                $hasSpecialChar = preg_match('/[@#$%^&*!?_\-=]/', $password);
                
                $combinationCount = ($hasLetter ? 1 : 0) + ($hasNumber ? 1 : 0) + ($hasSpecialChar ? 1 : 0);
                
                if ($combinationCount < 2) {
                    $error = '비밀번호는 영문자, 숫자, 특수문자(@#$%^&*!?_-=) 중 2가지 이상 조합해야 합니다.';
                }
            }
        }
        
        if (empty($error)) {
            // 판매자 추가 정보 수집
            $additionalData = [];
            if ($role === 'seller') {
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
                } else {
                    // 사업자등록증 이미지 업로드 처리
                    if (isset($_FILES['business_license_image']) && $_FILES['business_license_image']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = __DIR__ . '/../uploads/sellers/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileExtension = strtolower(pathinfo($_FILES['business_license_image']['name'], PATHINFO_EXTENSION));
                        // 문서 파일 확장자 차단
                        $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'hwp', 'gif'];
                        if (in_array($fileExtension, $documentExtensions)) {
                            $error = 'GIF 파일과 문서 파일은 업로드할 수 없습니다. (JPG, PNG만 가능)';
                        } else {
                            $allowedExtensions = ['jpg', 'jpeg', 'png'];
                            if (!in_array($fileExtension, $allowedExtensions)) {
                                $error = '허용되지 않은 파일 형식입니다. (JPG, PNG만 가능)';
                            } else {
                                $fileName = $userId . '_license_' . time() . '.' . $fileExtension;
                                $uploadPath = $uploadDir . $fileName;
                                
                                // 파일 크기 확인 (5MB)
                                if ($_FILES['business_license_image']['size'] > 5 * 1024 * 1024) {
                                    $error = '파일 크기는 5MB 이하여야 합니다.';
                                } elseif (move_uploaded_file($_FILES['business_license_image']['tmp_name'], $uploadPath)) {
                                    $additionalData['business_license_image'] = '/MVNO/uploads/sellers/' . $fileName;
                                } else {
                                    $error = '파일 업로드에 실패했습니다.';
                                }
                            }
                        }
                    } else {
                        $error = '사업자등록증 이미지를 업로드해주세요.';
                    }
                }
            }
            
            if (empty($error)) {
                // 일반 회원의 경우 전화번호를 additionalData에 추가
                if ($role === 'user') {
                    $additionalData['phone'] = $phone;
                }
                
                $result = registerDirectUser($userId, $password, $email, $name, $role, $additionalData);
                if ($result['success']) {
                    $success = true;
                    $registeredUser = $result['user'];
                    // 세션의 redirect_url 가져오기
                    $redirectUrl = $_SESSION['redirect_url'] ?? '/MVNO/';
                    // 사용 후 세션에서 제거
                    if (isset($_SESSION['redirect_url'])) {
                        unset($_SESSION['redirect_url']);
                    }
                } else {
                    $error = $result['message'];
                }
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
    <title>회원가입 - 유심킹</title>
    <link rel="stylesheet" href="/MVNO/assets/css/style.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 60px auto;
            padding: 40px 24px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .register-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .register-header p {
            font-size: 14px;
            color: #6b7280;
        }
        
        .error-message {
            padding: 12px 16px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }
        
        .success-message {
            padding: 12px 16px;
            background: #d1fae5;
            color: #065f46;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }
        
        .sns-register-section {
            margin-bottom: 32px;
            padding: 24px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .sns-register-title {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .sns-register-desc {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 16px;
        }
        
        .sns-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .sns-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 14px 20px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            font-size: 15px;
            font-weight: 500;
            color: #1f2937;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .sns-button:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }
        
        .sns-button.naver {
            background: #03c75a;
            color: white;
            border-color: #03c75a;
        }
        
        .sns-button.naver:hover {
            background: #02b350;
        }
        
        .sns-button.kakao {
            background: #fee500;
            color: #000000;
            border-color: #fee500;
        }
        
        .sns-button.kakao:hover {
            background: #fdd835;
        }
        
        .sns-button.google {
            background: white;
            color: #1f2937;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 32px 0;
            color: #9ca3af;
            font-size: 14px;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }
        
        .divider span {
            padding: 0 16px;
        }
        
        .direct-register-section {
            margin-top: 32px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #6366f1;
        }
        
        .form-help {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .register-button {
            width: 100%;
            padding: 14px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .register-button:hover {
            background: #4f46e5;
        }
        
        .login-link {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: #6b7280;
        }
        
        .login-link a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <!-- 판매자/관리자 가입 완료 (기존 방식) -->
            <div class="success-message">
                회원가입이 완료되었습니다. <a href="/MVNO/auth/login.php" style="color: #065f46; font-weight: 600;">로그인</a>해주세요.
            </div>
        <?php endif; ?>
        
        <?php if (!$success || ($registeredUser && ($registeredUser['role'] ?? 'user') !== 'user')): ?>
        <!-- 일반 회원 SNS 가입 -->
        <div class="sns-register-section">
            <div class="sns-register-title">일반 회원 가입</div>
            <div class="sns-register-desc">일반 회원은 SNS 로그인을 통해 가입해주세요.</div>
            <div class="sns-buttons">
                <button type="button" class="sns-button naver" onclick="snsLogin('naver')">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 0C4.477 0 0 4.477 0 10s4.477 10 10 10 10-4.477 10-10S15.523 0 10 0zm4.5 5.5h-2v9h-5v-9h-2v11h9v-11z"/>
                    </svg>
                    네이버로 가입
                </button>
                <button type="button" class="sns-button kakao" onclick="snsLogin('kakao')">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 0C4.477 0 0 4.477 0 10c0 3.55 2.186 6.59 5.3 7.93L3.5 20l3.2-1.77C7.5 18.33 8.71 18.5 10 18.5c5.523 0 10-4.477 10-10S15.523 0 10 0z"/>
                    </svg>
                    카카오로 가입
                </button>
                <button type="button" class="sns-button google" onclick="snsLogin('google')">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M19.6 10.227c0-.709-.064-1.39-.182-2.045H10v3.868h5.382a4.6 4.6 0 01-1.996 3.018v2.51h3.232c1.891-1.742 2.982-4.305 2.982-7.35z" fill="#4285F4"/>
                        <path d="M10 20c2.7 0 4.964-.895 6.618-2.423l-3.232-2.509c-.895.6-2.04.955-3.386.955-2.605 0-4.81-1.76-5.595-4.123H1.064v2.59A9.996 9.996 0 0010 20z" fill="#34A853"/>
                        <path d="M4.405 11.914c-.2-.6-.314-1.24-.314-1.914 0-.673.114-1.314.314-1.914V5.496H1.064A9.996 9.996 0 000 10c0 1.614.386 3.14 1.064 4.504l3.34-2.59z" fill="#FBBC05"/>
                        <path d="M10 3.977c1.468 0 2.786.505 3.823 1.496l2.868-2.868C14.959.99 12.695 0 10 0 6.09 0 2.71 2.24 1.064 5.496l3.34 2.59C5.19 5.732 7.395 3.977 10 3.977z" fill="#EA4335"/>
                    </svg>
                    구글로 가입
                </button>
            </div>
        </div>
        
        <div class="divider">
            <span>또는</span>
        </div>
        
        <!-- 직접 가입 (일반 회원, 관리자, 서브관리자, 판매자용) -->
        <div class="direct-register-section">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="role">회원 유형</label>
                    <select id="role" name="role" required>
                        <option value="user" selected>일반 회원</option>
                        <option value="seller">판매자</option>
                        <option value="sub_admin">서브관리자</option>
                        <option value="admin">관리자</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="user_id">아이디</label>
                    <input type="text" id="user_id" name="user_id" required>
                </div>
                <div class="form-group">
                    <label for="email">이메일</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="name">이름</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" required minlength="8">
                    <div class="form-help">8자 이상 20자 이내, 영문자/숫자/특수문자(@#$%^&*!?_-=) 중 2가지 이상 조합</div>
                </div>
                <div class="form-group">
                    <label for="password_confirm">비밀번호 확인</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                    <div class="form-help">비밀번호는 영문자, 숫자, 특수문자(@#$%^&*!?_-=) 중 2가지 이상의 조합으로 가입해야 합니다.</div>
                </div>
                
                <!-- 판매자 추가 정보 -->
                <div id="sellerInfo" style="display: none;">
                    <div class="form-group">
                        <label for="phone">전화번호</label>
                        <input type="tel" id="phone" name="phone" placeholder="010-1234-5678">
                    </div>
                    <div class="form-group">
                        <label for="mobile">휴대폰</label>
                        <input type="tel" id="mobile" name="mobile" placeholder="010-1234-5678">
                    </div>
                    <div class="form-group">
                        <label for="postal_code">우편번호</label>
                        <input type="text" id="postal_code" name="postal_code" placeholder="12345">
                    </div>
                    <div class="form-group">
                        <label for="address">주소</label>
                        <input type="text" id="address" name="address" placeholder="서울시 강남구">
                    </div>
                    <div class="form-group">
                        <label for="address_detail">상세주소</label>
                        <input type="text" id="address_detail" name="address_detail" placeholder="상세주소를 입력하세요">
                    </div>
                    <div class="form-group">
                        <label for="business_number">사업자등록번호</label>
                        <input type="text" id="business_number" name="business_number" placeholder="123-45-67890">
                    </div>
                    <div class="form-group">
                        <label for="company_name">회사명</label>
                        <input type="text" id="company_name" name="company_name" placeholder="(주)회사명">
                    </div>
                    <div class="form-group">
                        <label for="company_representative">대표자명</label>
                        <input type="text" id="company_representative" name="company_representative" placeholder="홍길동">
                    </div>
                    <div class="form-group">
                        <label for="business_type">업종</label>
                        <input type="text" id="business_type" name="business_type" placeholder="도매 및 소매업">
                    </div>
                    <div class="form-group">
                        <label for="business_item">업태</label>
                        <input type="text" id="business_item" name="business_item" placeholder="통신판매업">
                    </div>
                    <div class="form-group">
                        <label for="business_license_image">사업자등록증 이미지</label>
                        <input type="file" id="business_license_image" name="business_license_image" accept="image/jpeg,image/jpg,image/png">
                        <div class="form-help">사업자등록증 이미지를 업로드해주세요. (JPG, PNG, 최대 5MB)</div>
                        <div id="licensePreview" style="margin-top: 12px; display: none;">
                            <img id="licensePreviewImg" src="" alt="사업자등록증 미리보기" style="max-width: 300px; border-radius: 8px; border: 1px solid #e5e7eb;">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="register-button">회원가입</button>
            </form>
            
            <div class="login-link">
                이미 계정이 있으신가요? <a href="/MVNO/auth/login.php">로그인</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function snsLogin(provider) {
            fetch(`/MVNO/api/sns-login.php?action=${provider}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.auth_url;
                    } else {
                        showAlert(data.message || '가입에 실패했습니다.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('가입 중 오류가 발생했습니다.');
                });
        }
        
        // 회원 유형 변경 시 판매자 추가 정보 표시/숨김
        document.getElementById('role').addEventListener('change', function() {
            const sellerInfo = document.getElementById('sellerInfo');
            if (this.value === 'seller') {
                sellerInfo.style.display = 'block';
                // 판매자 필수 필드 설정
                document.getElementById('business_number').required = true;
                document.getElementById('company_name').required = true;
                document.getElementById('business_license_image').required = true;
            } else {
                sellerInfo.style.display = 'none';
                // 필수 해제
                document.getElementById('business_number').required = false;
                document.getElementById('company_name').required = false;
                document.getElementById('business_license_image').required = false;
            }
        });
        
        // 사업자등록증 이미지 미리보기
        document.getElementById('business_license_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('licensePreviewImg').src = e.target.result;
                    document.getElementById('licensePreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>



