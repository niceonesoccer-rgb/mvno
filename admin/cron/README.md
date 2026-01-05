# 로그 파일 자동 정리 스크립트

## 개요

시스템이 자동으로 생성하는 로그 파일들을 정기적으로 정리하여 디스크 공간을 절약합니다.

## 정리 대상

- 커스텀 로그 파일 (logs/connections.log, logs/sessions.log 등)
- PHP 에러 로그
- Apache 로그 (error.log, access.log)
- MySQL 로그 (*.err)
- 캐시 파일 (cache/*.cache)
- 만료된 세션 파일
- MySQL 바이너리 로그 (expire_logs_days 설정)

## 사용 방법

### 1. 수동 실행

```bash
# 기본 설정 (7일 보관)
php admin/cron/cleanup-logs.php

# 보관 기간 지정
php admin/cron/cleanup-logs.php --days=14
```

### 2. Windows 작업 스케줄러

1. 작업 스케줄러 열기
2. 기본 작업 만들기
3. 트리거: 매일 새벽 2시
4. 작업: 프로그램 시작
   - 프로그램: `C:\xampp\php\php.exe`
   - 인수: `C:\xampp\htdocs\mvno\admin\cron\cleanup-logs.php`
   - 시작 위치: `C:\xampp\htdocs\mvno`

또는 배치 파일 사용:
- 프로그램: `C:\xampp\htdocs\mvno\admin\cron\cleanup-logs.bat`

### 3. Linux Cron

```bash
# crontab 편집
crontab -e

# 매일 새벽 2시 실행 (7일 보관)
0 2 * * * /usr/bin/php /path/to/mvno/admin/cron/cleanup-logs.php

# 매일 새벽 2시 실행 (14일 보관)
0 2 * * * /usr/bin/php /path/to/mvno/admin/cron/cleanup-logs.php --days=14
```

### 4. 웹에서 실행 (보안 주의)

```
http://yourdomain.com/MVNO/admin/cron/cleanup-logs.php?cron_key=CHANGE_THIS_TO_RANDOM_STRING_2024&days=7
```

**주의**: `cleanup-logs.php` 파일의 `$cronKey` 변수를 변경하세요!

## 보관 기간 설정

- **3일**: 개발/테스트 환경
- **7일**: 소규모 사이트 (권장)
- **14일**: 중규모 사이트
- **30일**: 대규모 사이트 (법적 요구사항이 있는 경우)
- **60-90일**: 장기 보관이 필요한 경우

## MySQL 바이너리 로그 설정

스크립트는 자동으로 MySQL의 `expire_logs_days` 설정을 업데이트합니다.
MySQL 서버 재시작 후에도 유지하려면 `my.ini` (Windows) 또는 `my.cnf` (Linux)에 추가:

```ini
[mysqld]
expire_logs_days = 7
```

## 로그 확인

스크립트 실행 결과는:
- CLI: 콘솔에 출력
- 웹: JSON 형식으로 반환
- 배치 파일: `logs/cleanup-logs.log`에 저장 가능

## 문제 해결

### 권한 오류
- 로그 파일 삭제 권한 확인
- PHP 실행 사용자에게 쓰기 권한 부여

### MySQL 설정 실패
- MySQL 사용자 권한 확인 (SUPER 권한 필요)
- `SET GLOBAL expire_logs_days` 실행 권한 확인

### 스크립트가 실행되지 않음
- PHP 경로 확인
- 파일 경로 확인
- cron_key 확인 (웹 실행 시)
