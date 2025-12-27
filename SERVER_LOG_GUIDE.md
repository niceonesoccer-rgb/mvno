# 서버 로그 확인 가이드

## PHP 에러 로그 위치

### XAMPP (Windows)
- **에러 로그 파일**: `C:\xampp\apache\logs\error.log`
- 또는: `C:\xampp\php\logs\php_error_log`

### 일반적인 위치
- Linux: `/var/log/apache2/error.log` 또는 `/var/log/php_errors.log`
- Mac (MAMP): `/Applications/MAMP/logs/php_error.log`

## 로그 확인 방법

### 방법 1: 파일 직접 열기
1. 위의 경로로 이동
2. `error.log` 파일을 텍스트 에디터로 열기
3. 파일 맨 아래쪽(최신 로그) 확인

### 방법 2: 실시간 로그 확인 (Windows)
```powershell
# PowerShell에서 실행
Get-Content C:\xampp\apache\logs\error.log -Wait -Tail 50
```

### 방법 3: 실시간 로그 확인 (Linux/Mac)
```bash
tail -f /var/log/apache2/error.log
```

## 찾아야 할 로그

다음과 같은 로그를 찾으세요:

```
Internet Application Debug - Step 1: Starting database connection
Internet Application Debug - Step 2: Database connected, querying product ID: 29
Internet Application Debug - Step 3: Product found, seller_id: X
Internet Application Debug - Step 4: Current user - user_id: X
Internet Application Debug - Step 5: Calling addProductApplication
Internet Application Debug - Step 6: addProductApplication returned: false
Internet Application Save Failed - Last DB Error: ...
```

또는:

```
addProductApplication - PDOException occurred:
  Message: ...
  Code: ...
  SQL State: ...
```

## 브라우저 콘솔에서 확인

개발 환경(localhost)에서는 브라우저 콘솔에 더 자세한 에러 정보가 표시됩니다:

1. F12로 개발자 도구 열기
2. Console 탭 확인
3. `Internet Application Debug - Response data` 객체를 펼쳐서 `debug` 속성 확인

## 일반적인 에러 원인

1. **데이터베이스 연결 실패**
   - `db-config.php` 설정 확인
   - MySQL 서비스가 실행 중인지 확인

2. **테이블이 존재하지 않음**
   - `product_applications` 테이블 확인
   - `application_customers` 테이블 확인

3. **외래 키 제약조건 위반**
   - `user_id`가 `users` 테이블에 존재하는지 확인
   - `product_id`가 `products` 테이블에 존재하는지 확인

4. **데이터 타입 불일치**
   - 전화번호 형식 문제
   - JSON 인코딩 문제

## 로그 파일이 너무 클 때

최신 로그만 보려면:
```bash
# 마지막 100줄만 보기
tail -n 100 error.log
```

또는 특정 키워드로 필터링:
```bash
# "Internet Application"이 포함된 로그만 보기
grep "Internet Application" error.log | tail -n 50
```








