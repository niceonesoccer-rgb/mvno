# XAMPP 로그 파일 위치 및 확인 방법

## 로그 파일 위치

### Apache 로그
- **에러 로그**: `C:\xampp\apache\logs\error.log`
- **액세스 로그**: `C:\xampp\apache\logs\access.log`

### PHP 로그
- **에러 로그**: `C:\xampp\php\logs\php_error_log`
- **PHP 설정**: `C:\xampp\php\php.ini`에서 `error_log` 설정 확인

### MySQL 로그
- **에러 로그**: `C:\xampp\mysql\data\*.err` (데이터베이스별로 다를 수 있음)
- **일반 로그**: MySQL 설정 파일에서 확인

## 로그 확인 방법

### 1. 수동 확인
- Windows 탐색기에서 위 경로로 이동하여 로그 파일 열기
- 메모장이나 텍스트 에디터로 열어서 확인

### 2. PowerShell로 확인 (권장)
```powershell
# Apache 에러 로그 최근 20줄
Get-Content C:\xampp\apache\logs\error.log -Tail 20 -Encoding UTF8

# PHP 에러 로그 최근 20줄
Get-Content C:\xampp\php\logs\php_error_log -Tail 20 -Encoding UTF8

# MySQL 에러 로그 찾기
Get-ChildItem C:\xampp\mysql\data -Filter "*.err" -Recurse
```

### 3. 배치 파일 사용
프로젝트 루트에 있는 `check-logs.bat` 파일을 더블클릭하면 모든 로그를 한 번에 확인할 수 있습니다.

### 4. PowerShell 스크립트 사용
```powershell
.\check-logs.ps1
```

## 실시간 로그 모니터링

### PowerShell에서 실시간 모니터링
```powershell
# Apache 로그 실시간 모니터링
Get-Content C:\xampp\apache\logs\error.log -Wait -Tail 10 -Encoding UTF8

# PHP 로그 실시간 모니터링
Get-Content C:\xampp\php\logs\php_error_log -Wait -Tail 10 -Encoding UTF8
```

## 주요 에러 패턴

### PHP 에러
- `Parse error`: 구문 오류 (보통 `;` 누락, 따옴표 불일치 등)
- `Fatal error`: 치명적 오류 (함수 미정의, 클래스 미찾음 등)
- `Warning`: 경고 (파일 없음, 함수 사용법 오류 등)
- `Notice`: 알림 (변수 미정의 등)

### Apache 에러
- `404 Not Found`: 파일을 찾을 수 없음
- `500 Internal Server Error`: 서버 내부 오류
- `403 Forbidden`: 접근 권한 없음

### MySQL 에러
- `Access denied`: 접근 권한 오류
- `Table doesn't exist`: 테이블 없음
- `Connection refused`: 연결 거부

## 문제 해결 팁

1. **에러 발생 시 즉시 로그 확인**
   - 브라우저에서 에러가 발생하면 즉시 로그 파일 확인
   - 최근 변경한 코드 부분 확인

2. **로그 레벨 설정 확인**
   - PHP: `php.ini`에서 `error_reporting` 설정
   - Apache: `httpd.conf`에서 `LogLevel` 설정

3. **로그 파일 크기 관리**
   - 로그 파일이 너무 커지면 주기적으로 백업 후 삭제
   - 로테이션 설정 고려

## 참고
- 로그 파일은 UTF-8 인코딩으로 저장되므로 텍스트 에디터에서 UTF-8로 열어야 한글이 제대로 표시됩니다.
- 로그 파일은 계속 쌓이므로 주기적으로 확인하고 정리하는 것이 좋습니다.






