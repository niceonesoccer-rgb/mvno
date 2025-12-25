# 이메일 발송 설정 가이드

## 📧 이메일 발송이 작동하는 환경

### 1. 웹 호스팅 (카페24, 가비아, 닷홈 등)
✅ **대부분의 호스팅에서는 기본 `mail()` 함수가 작동합니다!**
- 호스팅 업체가 SMTP 서버를 제공하므로 추가 설정 없이 사용 가능
- `mail-config.php` 파일에서 `MAIL_METHOD`를 `'mail'`로 설정하면 됩니다

### 2. XAMPP (로컬 PC)
❌ **기본적으로 작동하지 않습니다**
- 추가 설정 필요 (SMTP 또는 PHPMailer)

---

## 🚀 설정 방법

### 방법 1: 호스팅 업체 사용 (가장 간단)

1. `includes/data/mail-config.php` 파일 열기
2. 다음 설정 변경:
```php
define('MAIL_METHOD', 'mail'); // 'mail'로 설정
```
3. 호스팅에 업로드하면 바로 작동합니다!

**참고:** 대부분의 호스팅 업체는 자체 SMTP 서버를 제공하므로 추가 설정 없이 `mail()` 함수가 작동합니다.

---

### 방법 2: Gmail SMTP 사용 (PHPMailer 필요)

#### 2-1. PHPMailer 설치

프로젝트 루트 디렉토리에서 실행:
```bash
composer require phpmailer/phpmailer
```

또는 수동 설치:
1. https://github.com/PHPMailer/PHPMailer 에서 다운로드
2. `vendor/phpmailer/phpmailer` 폴더에 압축 해제

#### 2-2. Gmail 설정

1. Google 계정 설정 → 보안 → **2단계 인증 활성화**
2. **앱 비밀번호 생성**: https://myaccount.google.com/apppasswords
   - 앱 선택: "메일"
   - 기기 선택: "기타(맞춤 이름)" → "MVNO" 입력
   - 생성된 16자리 비밀번호 복사

#### 2-3. mail-config.php 설정

```php
define('MAIL_METHOD', 'smtp');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'your-email@gmail.com'); // 본인 Gmail 주소
define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');   // 생성한 앱 비밀번호
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'MVNO');
```

---

### 방법 3: 네이버 메일 SMTP 사용

```php
define('MAIL_METHOD', 'smtp');
define('SMTP_HOST', 'smtp.naver.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'your-email@naver.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_FROM_EMAIL', 'your-email@naver.com');
define('SMTP_FROM_NAME', 'MVNO');
```

**참고:** 네이버는 보안 설정에서 "POP3/SMTP 사용"을 활성화해야 합니다.

---

### 방법 4: 호스팅 업체 SMTP 사용

각 호스팅 업체마다 SMTP 설정이 다릅니다. 호스팅 업체의 메일 설정 페이지에서 확인하세요.

**카페24 예시:**
```php
define('MAIL_METHOD', 'smtp');
define('SMTP_HOST', 'smtp.cafe24.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'your-email@yourdomain.com');
define('SMTP_PASSWORD', 'your-password');
```

---

## ✅ 테스트 방법

### 개발 환경 (XAMPP)
- 이메일 발송 실패해도 DB에는 인증번호가 저장됩니다
- `/MVNO/admin/test-email-verification.php` 페이지에서 인증번호 확인 가능
- 화면에 인증번호가 자동으로 표시됩니다

### 운영 환경 (호스팅)
- 실제 이메일로 인증번호가 발송됩니다
- 이메일 수신함을 확인하세요

---

## 🔧 문제 해결

### 이메일이 발송되지 않는 경우

1. **호스팅 사용 시:**
   - `MAIL_METHOD`를 `'mail'`로 설정했는지 확인
   - 호스팅 업체의 메일 서버 상태 확인

2. **SMTP 사용 시:**
   - SMTP 설정 정보가 정확한지 확인
   - 방화벽에서 포트(587, 465)가 차단되지 않았는지 확인
   - Gmail 사용 시 앱 비밀번호가 올바른지 확인

3. **PHPMailer 오류 시:**
   - `vendor/autoload.php` 파일이 존재하는지 확인
   - Composer로 설치했는지 확인

---

## 📝 요약

- **웹 호스팅**: `MAIL_METHOD = 'mail'` 설정만 하면 대부분 작동 ✅
- **로컬 PC (XAMPP)**: PHPMailer + SMTP 설정 필요 또는 개발용으로 DB에서 확인
- **실제 서비스**: 호스팅에 업로드하면 바로 사용 가능!



