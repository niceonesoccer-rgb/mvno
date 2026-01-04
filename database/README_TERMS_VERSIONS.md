# 약관 버전 관리 시스템 설치 가이드

## 개요
약관/개인정보처리방침을 시행일자별로 버전 관리하고, 5년 경과 시 자동으로 삭제하는 시스템입니다.

## 설치 순서

### 1. 데이터베이스 테이블 생성
```bash
mysql -u root -p mvno_db < database/create_terms_versions_table.sql
```
또는 phpMyAdmin에서 `database/create_terms_versions_table.sql` 파일을 실행합니다.

### 2. 기존 데이터 마이그레이션
기존 `app_settings` 테이블에 저장된 약관 데이터를 새 버전 관리 시스템으로 마이그레이션합니다.

```bash
php database/migrate_terms_to_versions.php
```

이 스크립트는:
- 기존 이용약관과 개인정보처리방침 내용을 `terms_versions` 테이블로 복사합니다
- 초기 버전을 v1.0으로 설정합니다
- 시행일자를 현재 날짜로 설정합니다 (내용에서 추출 가능하면 추출)
- 활성 버전으로 설정합니다

### 3. Cron Job 설정 (5년 경과 버전 자동 삭제)

**Linux/Unix:**
```bash
crontab -e

# 다음 라인 추가 (매일 자정 실행)
0 0 * * * /usr/bin/php /path/to/mvno/api/auto-delete-old-terms-versions.php
```

**Windows 작업 스케줄러:**
- 작업 스케줄러 열기
- 기본 작업 만들기
- 트리거: 매일, 자정
- 작업: 프로그램 시작
- 프로그램: `C:\xampp\php\php.exe`
- 인수: `C:\xampp\htdocs\mvno\api\auto-delete-old-terms-versions.php`

**수동 실행 (테스트용):**
```bash
php api/auto-delete-old-terms-versions.php
```

**웹 브라우저에서 실행 (보안 키 필요):**
```
http://localhost/MVNO/api/auto-delete-old-terms-versions.php?key=auto-delete-terms-2025
```

## 기능 설명

### 관리자 페이지
- URL: `/MVNO/admin/settings/terms-versions.php`
- 기능:
  - 이용약관/개인정보처리방침 탭 전환
  - 버전 추가/수정/삭제
  - 활성 버전 설정
  - 버전 목록 조회

### 사용자 페이지
- URL: `/MVNO/terms/view.php?type=privacy_policy`
- 기능:
  - 현재 활성 버전 자동 표시
  - 시행일자 드롭다운으로 이전 버전 선택 가능
  - URL 파라미터로 특정 버전 선택:
    - `?type=privacy_policy&version=v3.8`
    - `?type=privacy_policy&date=2025-01-01`

### 자동 삭제
- 5년 경과한 비활성 버전 자동 삭제
- 활성 버전은 삭제되지 않음
- 매일 자정에 실행 (cron job 설정 필요)

## 데이터베이스 구조

### terms_versions 테이블
- `id`: 기본 키
- `type`: 약관 타입 (terms_of_service, privacy_policy)
- `version`: 버전 번호 (예: v3.8)
- `effective_date`: 시행일자
- `announcement_date`: 공고일자 (선택)
- `title`: 제목
- `content`: HTML 내용
- `is_active`: 활성 버전 여부 (1: 활성, 0: 비활성)
- `created_by`: 생성자
- `created_at`: 생성일시
- `updated_at`: 수정일시

## 주요 함수

### includes/data/terms-functions.php
- `getActiveTermsVersion($type)`: 현재 활성 버전 가져오기
- `getTermsVersionByVersion($type, $version)`: 특정 버전 가져오기
- `getTermsVersionByDate($type, $date)`: 특정 시행일자 버전 가져오기
- `getTermsVersionList($type, $includeInactive)`: 버전 목록 가져오기
- `saveTermsVersion(...)`: 새 버전 저장
- `updateTermsVersion($id, $data)`: 버전 수정
- `deleteTermsVersion($id)`: 버전 삭제 (활성 버전 제외)
- `deleteOldTermsVersions()`: 5년 경과 버전 자동 삭제

## 주의사항

1. **활성 버전 보호**
   - 활성 버전(`is_active=1`)은 삭제할 수 없습니다
   - 5년 경과해도 활성 버전은 자동 삭제되지 않습니다

2. **하위 호환성**
   - `information_security` 타입은 기존 방식(`app_settings`)을 계속 사용합니다
   - `terms_of_service`, `privacy_policy`만 새 버전 관리 시스템을 사용합니다
   - 버전 관리 시스템에 데이터가 없으면 기존 방식으로 폴백합니다

3. **데이터 백업**
   - 법적 요구사항에 따라 필요시 삭제 전 백업을 고려하세요

4. **버전 번호 규칙**
   - 버전 번호는 중복될 수 없습니다 (타입별로 유일)
   - 예: v1.0, v2.0, v3.8 등

## 문제 해결

### 버전이 표시되지 않을 때
1. `terms_versions` 테이블이 생성되었는지 확인
2. 마이그레이션 스크립트가 실행되었는지 확인
3. 관리자 페이지에서 버전이 추가되었는지 확인

### 자동 삭제가 작동하지 않을 때
1. Cron job이 설정되었는지 확인
2. PHP 경로가 올바른지 확인
3. 스크립트 실행 권한 확인
4. 로그 파일 확인 (`error_log`)
