# 이메일 발송 설정 가이드 (로컬 + 호스팅 모두 작동)

## 🎯 목표
**로컬 PC(XAMPP)에서도 작동하고, 웹 호스팅에서도 작동하도록 설정**

---

## ✅ 가장 간단한 방법 (권장)

### 현재 설정 상태
- `MAIL_METHOD`가 `'auto'`로 설정되어 있습니다
- 환경을 자동으로 감지하여 적절한 방법을 선택합니다

### 작동 방식
1. **로컬 PC (XAMPP)**: 
   - PHPMailer가 설치되어 있으면 → SMTP 사용
   - PHPMailer가 없으면 → mail() 함수 사용 (실패해도 DB에는 저장됨)

2. **웹 호스팅**:
   - 자동으로 `mail()` 함수 사용
   - 대부분의 호스팅에서 바로 작동합니다!

---

## 🚀 설정 방법

### 방법 1: 호스팅만 사용 (가장 간단) ✅

**아무것도 할 필요 없습니다!**
- 호스팅에 파일만 업로드하면 자동으로 작동합니다
- `MAIL_METHOD = 'auto'`가 호스팅 환경을 감지하여 `mail()` 함수 사용

---

### 방법 2: 로컬 PC에서도 실제 이메일 발송하기

#### 2-1. PHPMailer 설치

프로젝트 루트 디렉토리에서:
```bash
composer require phpmailer/phpmailer
```

또는 수동 설치:
1. https://github.com/PHPMailer/PHPMailer 에서 다운로드
2. `vendor/phpmailer/phpmailer` 폴더에 압축 해제

#### 2-2. Gmail SMTP 설정

1. **Gmail 앱 비밀번호 생성**:
   - Google 계정 설정 → 보안 → 2단계 인증 활성화
   - https://myaccount.google.com/apppasswords 접속
   - 앱 선택: "메일"
   - 기기 선택: "기타(맞춤 이름)" → "MVNO" 입력
   - 생성된 16자리 비밀번호 복사

2. **mail-config.php 파일 수정**:
```php
// SMTP 설정 (MAIL_METHOD가 'smtp'일 때 사용)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'your-email@gmail.com'); // 본인 Gmail 주소
define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');   // 생성한 앱 비밀번호
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'MVNO');
```

3. **로컬에서만 SMTP 사용하도록 설정** (선택사항):
   - `MAIL_METHOD`를 `'auto'`로 두면 자동 감지
   - 또는 `'smtp'`로 설정하면 항상 SMTP 사용

---

## 📋 설정 옵션 설명

### MAIL_METHOD 옵션

#### `'auto'` (기본값) ⭐ 권장
- 환경을 자동으로 감지
- 로컬 + PHPMailer 있음 → SMTP 사용
- 그 외 → mail() 함수 사용
- **로컬과 호스팅 모두에서 자동으로 적절한 방법 선택**

#### `'mail'`
- 항상 기본 `mail()` 함수 사용
- 호스팅에서 대부분 작동
- 로컬 PC에서는 작동하지 않을 수 있음

#### `'smtp'`
- 항상 SMTP 사용 (PHPMailer 필요)
- 로컬과 호스팅 모두에서 작동
- SMTP 설정 필요

---

## 🔍 현재 환경 확인

### 로컬 PC인지 확인
- URL이 `localhost` 또는 `127.0.0.1`로 시작하면 로컬로 인식
- 자동으로 감지되므로 별도 설정 불필요

### 호스팅인지 확인
- 실제 도메인으로 접속하면 호스팅으로 인식
- 자동으로 `mail()` 함수 사용

---

## ✅ 테스트 방법

### 로컬 PC (XAMPP)
1. **PHPMailer 없이**:
   - 이메일 발송 실패해도 DB에는 저장됨
   - 화면에 인증번호 표시됨
   - `/MVNO/admin/test-email-verification.php`에서 확인 가능

2. **PHPMailer + SMTP 설정 후**:
   - 실제 이메일로 인증번호 발송됨
   - Gmail 등에서 확인 가능

### 웹 호스팅
- 파일 업로드 후 바로 작동
- 실제 이메일로 인증번호 발송됨

---

## 📝 요약

### 현재 설정 (`MAIL_METHOD = 'auto'`)
✅ **로컬 PC**: PHPMailer 있으면 SMTP, 없으면 mail() (개발용 DB 확인)
✅ **웹 호스팅**: 자동으로 mail() 함수 사용 → 바로 작동!

### 추가 설정 없이
- 호스팅에 업로드하면 바로 작동합니다
- 로컬에서도 DB에서 인증번호 확인 가능

### 로컬에서 실제 이메일 발송하려면
- PHPMailer 설치 + SMTP 설정만 하면 됩니다

---

## 🎉 결론

**현재 설정으로 이미 양쪽 모두 작동합니다!**
- 호스팅: 자동으로 `mail()` 함수 사용 → 바로 작동 ✅
- 로컬: PHPMailer 있으면 SMTP, 없으면 개발용으로 DB 확인 ✅

추가 설정 없이 호스팅에 업로드하면 바로 사용 가능합니다!
