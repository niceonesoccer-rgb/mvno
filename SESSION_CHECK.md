# 세션 공유 확인 리포트

## 현재 세션 설정 (auth-functions.php)

1. **세션 이름**: `MVNO_SESSION` (일관성 유지)
2. **쿠키 경로**: `/` (전체 사이트에서 공유)
3. **도메인**: 빈 문자열 (현재 도메인 사용)
4. **Lifetime**: 0 (브라우저 종료 시까지 유지)

## 일반 회원 페이지 구조

### 1. 메인 페이지 (index.php)
- `include 'includes/header.php'` 사용
- header.php에서 `require_once __DIR__ . '/data/auth-functions.php'` 포함

### 2. 알뜰폰 상세 페이지 (mvno-plan-detail.php)
- `include '../includes/header.php'` 사용
- header.php에서 auth-functions.php 포함

### 3. 통신사폰 상세 페이지 (mno-phone-detail.php)
- `require_once '../includes/data/auth-functions.php'` 직접 포함
- 그 다음 `include '../includes/header.php'` (header.php에서도 auth-functions.php 포함하지만 require_once로 중복 방지)

### 4. 인터넷 페이지 (internets.php)
- `require_once '../includes/data/auth-functions.php'` 직접 포함
- 그 다음 `include '../includes/header.php'`

### 5. API 파일들 (로그인 관련)
- `api/direct-login.php`: auth-functions.php만 포함 (수정 완료)
- `api/sns-login.php`: auth-functions.php만 포함 (수정 완료)
- `api/sns-callback.php`: auth-functions.php만 포함 (수정 완료)

## 세션 보호 메커니즘

auth-functions.php의 세션 시작 코드:
```php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_name('MVNO_SESSION');
    session_set_cookie_params([...]);
    session_start();
}
```

이 코드는:
- ✅ 세션이 이미 시작되지 않은 경우에만 실행
- ✅ 헤더가 전송되지 않은 경우에만 실행
- ✅ 중복 include 시 안전하게 작동

## 결론

**모든 일반 회원 페이지에서 동일한 세션 설정을 사용하므로, 한 곳에서 로그인하면 모든 곳에서 로그인이 유지되어야 합니다.**

세션 쿠키가:
- 경로: `/` - 전체 사이트에서 접근 가능
- 이름: `MVNO_SESSION` - 일관된 세션 이름 사용
- 도메인: 현재 도메인 - 모든 하위 경로에서 공유

**테스트 방법:**
1. 한 페이지에서 로그인
2. 다른 페이지로 이동하여 로그인 상태 확인
3. 세션 쿠키 `MVNO_SESSION`이 모든 페이지에서 동일한지 확인

























