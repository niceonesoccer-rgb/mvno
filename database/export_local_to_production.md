# 로컬 DB → 프로덕션 DB 배포 가이드

## 📋 배포 절차

### 1단계: 로컬 DB 스키마 내보내기

#### 방법 1: phpMyAdmin 사용 (권장)

1. **로컬 phpMyAdmin 접속**
   - http://localhost/phpmyadmin

2. **데이터베이스 선택**
   - 왼쪽에서 `mvno_db` 선택

3. **내보내기 설정**
   - 상단 "내보내기" 탭 클릭
   - **"사용자 정의"** 선택
   - **"구조"** 섹션:
     - ✅ "CREATE DATABASE / USE 문 추가" 체크 해제
     - ✅ "IF NOT EXISTS 추가" 체크
   - **"데이터"** 섹션:
     - ❌ "데이터 삽입" 체크 해제 (구조만 필요)
   - **"개체 생성 옵션"**:
     - ✅ "DROP 문 추가" 체크
     - ✅ "IF NOT EXISTS 추가" 체크

4. **파일 저장**
   - "실행" 클릭
   - 파일명: `local_mvno_db_schema.sql` 저장

#### 방법 2: 명령줄 사용 (구조만)

```bash
# XAMPP MySQL 경로로 이동
cd C:\xampp\mysql\bin

# 구조만 내보내기 (데이터 제외)
mysqldump.exe -u root --no-data mvno_db > C:\xampp\htdocs\mvno\database\local_schema_structure_only.sql

# 또는 데이터 포함
mysqldump.exe -u root mvno_db > C:\xampp\htdocs\mvno\database\local_schema_with_data.sql
```

#### 방법 3: DBeaver 사용

1. **로컬 DB 연결**
   - DBeaver에서 `mvno_db` 연결 선택

2. **내보내기 실행**
   - 우클릭 → "도구" → "데이터베이스 내보내기"
   - **"구조만"** 또는 **"구조와 데이터"** 선택
   - 파일 경로 지정: `database/local_mvno_db_schema.sql`
   - "시작" 클릭

---

### 2단계: 프로덕션 DB 백업

**⚠️ 필수: 프로덕션 DB 백업**

#### DBeaver 사용:
1. `dbdanora` 연결 선택
2. 우클릭 → "도구" → "데이터베이스 내보내기"
3. 모든 테이블 선택
4. 파일 저장: `backup_dbdanora_YYYYMMDD.sql`

---

### 3단계: 프로덕션 DB에 적용

#### 방법 1: DBeaver 사용 (권장)

1. **프로덕션 DB 연결**
   - DBeaver에서 `dbdanora` 연결 선택

2. **SQL 스크립트 실행**
   - SQL 편집기 열기 (새 SQL 스크립트)
   - `local_mvno_db_schema.sql` 파일 열기
   - 전체 선택 (Ctrl+A)
   - 실행 (Ctrl+Enter)

#### 방법 2: phpMyAdmin 사용

1. **프로덕션 phpMyAdmin 접속**
   - ganadamobile.co.kr/phpmyadmin

2. **데이터베이스 선택**
   - `dbdanora` 선택

3. **가져오기**
   - "가져오기" 탭 클릭
   - `local_mvno_db_schema.sql` 파일 선택
   - "실행" 클릭

---

## 🔍 확인 사항

### 스키마 확인 쿼리

```sql
-- 프로덕션 DB에서 실행
USE dbdanora;

-- products 테이블에 point_setting, point_benefit_description 확인
SHOW COLUMNS FROM products LIKE 'point_setting';
SHOW COLUMNS FROM products LIKE 'point_benefit_description';

-- product_type ENUM에 'mno-sim' 확인
SHOW COLUMNS FROM products WHERE Field = 'product_type';
SHOW COLUMNS FROM product_applications WHERE Field = 'product_type';

-- product_applications 테이블에 order_number, user_id 확인
SHOW COLUMNS FROM product_applications LIKE 'order_number';
SHOW COLUMNS FROM product_applications LIKE 'user_id';
```

---

## ⚠️ 주의사항

1. **백업 필수**: 프로덕션 DB 백업 없이 진행하지 마세요
2. **테스트 먼저**: 가능하면 테스트 서버에서 먼저 테스트
3. **다운타임**: 배포 중에는 사이트 접속을 차단하는 것을 권장
4. **롤백 준비**: 문제 발생 시 즉시 백업 복원 가능하도록 준비

---

## 🚨 문제 발생 시

### 백업 복원
```sql
-- DBeaver에서
-- 1. backup_dbdanora_YYYYMMDD.sql 파일 열기
-- 2. 전체 선택 후 실행
```

---

## ✅ 배포 완료 체크리스트

- [ ] 로컬 DB 스키마 내보내기 완료
- [ ] 프로덕션 DB 백업 완료
- [ ] 프로덕션 DB에 스키마 적용 완료
- [ ] point_setting, point_benefit_description 컬럼 확인
- [ ] product_type ENUM에 'mno-sim' 확인
- [ ] order_number, user_id 컬럼 확인
- [ ] 웹사이트 정상 동작 확인
