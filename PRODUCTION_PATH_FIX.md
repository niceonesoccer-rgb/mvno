# 프로덕션 서버 경로 수정 가이드

## 문제
로컬에서는 `/MVNO/` 경로를 사용하지만, 프로덕션 서버(`ganadamobile.co.kr`)에서는 실제 경로가 다를 수 있습니다.

## 해결 방법

### 방법 1: 경로 설정 파일 사용 (권장)

`includes/data/path-config.php` 파일이 자동으로 경로를 감지합니다.

**확인 사항:**
1. 프로덕션 서버의 실제 URL 확인:
   - `ganadamobile.co.kr/mvno/` 또는
   - `ganadamobile.co.kr/MVNO/` 또는
   - `ganadamobile.co.kr/` (루트)

2. 경로 설정 파일 수정:
   - `includes/data/path-config.php` 파일 열기
   - `ganadamobile.co.kr` 부분의 `$basePath` 값을 실제 경로에 맞게 수정

### 방법 2: 일괄 검색/바꾸기

프로덕션 서버의 실제 경로를 확인한 후:

1. **로컬에서 일괄 변경:**
   - 모든 파일에서 `/MVNO/`를 실제 경로로 변경
   - 예: `/mvno/` 또는 `/` (루트인 경우)

2. **변경 후 다시 업로드**

### 방법 3: .htaccess 리다이렉트

프로덕션 서버 루트에 `.htaccess` 파일 생성:

```apache
# /MVNO/ 경로를 실제 경로로 리다이렉트
RewriteEngine On
RewriteBase /

# /MVNO/로 시작하는 요청을 /mvno/로 리다이렉트 (또는 실제 경로)
RewriteRule ^MVNO/(.*)$ /mvno/$1 [R=301,L]
```

## 확인 방법

1. **프로덕션 서버에서 확인:**
   - `ganadamobile.co.kr` 접속
   - 개발자 도구(F12) → Network 탭
   - 이미지/CSS/JS 파일 로드 실패 확인
   - 실패한 파일의 경로 확인

2. **실제 경로 확인:**
   - FTP 클라이언트에서 프로덕션 서버 구조 확인
   - `www_root` 또는 `public_html` 내부 구조 확인

## 빠른 수정

프로덕션 서버의 실제 경로가 `/mvno/`인 경우:

1. `includes/data/path-config.php` 파일 수정:
   ```php
   if (strpos($_SERVER['HTTP_HOST'] ?? '', 'ganadamobile.co.kr') !== false) {
       $basePath = '/mvno'; // 실제 경로로 변경
   }
   ```

2. 또는 모든 파일에서 `/MVNO/`를 `/mvno/`로 일괄 변경
