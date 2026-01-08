# 프로덕션 DB 재구성 가이드
## ganadamobile.co.kr (dbdanora) 배포용

### ⚠️ 주의사항
**이 작업은 모든 데이터를 삭제하고 테이블을 재생성합니다!**
- **반드시 백업을 먼저 받으세요!**
- 프로덕션 서버에서 실행하기 전에 테스트 서버에서 먼저 테스트하세요.

---

## 📋 배포 절차

### 1단계: 백업 받기
프로덕션 DB의 모든 데이터를 백업합니다.

**phpMyAdmin 사용:**
1. ganadamobile.co.kr/phpmyadmin 접속
2. `dbdanora` 데이터베이스 선택
3. "내보내기" 탭 클릭
4. "빠른" 또는 "사용자 정의" 선택
5. "실행" 클릭하여 SQL 파일 다운로드

**명령줄 사용:**
```bash
mysqldump -u [사용자명] -p dbdanora > backup_$(date +%Y%m%d_%H%M%S).sql
```

---

### 2단계: SQL 스크립트 실행

**방법 1: phpMyAdmin 사용 (권장)**
1. ganadamobile.co.kr/phpmyadmin 접속
2. `dbdanora` 데이터베이스 선택
3. "가져오기" 탭 클릭
4. `full_production_deployment.sql` 파일 선택
5. "실행" 클릭

**방법 2: 명령줄 사용**
```bash
mysql -u [사용자명] -p dbdanora < full_production_deployment.sql
```

**방법 3: DBeaver 사용**
1. DBeaver에서 `dbdanora` 연결 선택
2. SQL 편집기 열기
3. `full_production_deployment.sql` 파일 내용 복사
4. 실행 (Ctrl+Enter 또는 실행 버튼)

---

### 3단계: 확인

다음 쿼리로 테이블이 올바르게 생성되었는지 확인:

```sql
-- 테이블 목록 확인
SHOW TABLES;

-- products 테이블 구조 확인 (point_setting, point_benefit_description 포함)
SHOW COLUMNS FROM products;

-- product_applications 테이블 구조 확인 (order_number, mno-sim 포함)
SHOW COLUMNS FROM product_applications;
DESCRIBE product_applications;

-- product_type ENUM 확인
SHOW COLUMNS FROM products WHERE Field = 'product_type';
SHOW COLUMNS FROM product_applications WHERE Field = 'product_type';
```

---

## 🔍 주요 변경사항

### 1. products 테이블
- ✅ `point_setting` 컬럼 추가 (INT(11) UNSIGNED, 기본값 0)
- ✅ `point_benefit_description` 컬럼 추가 (TEXT, NULL 허용)
- ✅ `product_type` ENUM에 'mno-sim' 추가

### 2. product_applications 테이블
- ✅ `order_number` 컬럼 추가 (VARCHAR(20), UNIQUE)
- ✅ `user_id` 컬럼 추가 (VARCHAR(50))
- ✅ `product_type` ENUM에 'mno-sim' 추가
- ✅ `status_changed_at` 컬럼 포함

### 3. application_customers 테이블
- ✅ `user_id` 컬럼 추가 (VARCHAR(50))

### 4. 기타
- ✅ 모든 외래키 제약조건 포함
- ✅ 인덱스 최적화
- ✅ 포인트 관련 테이블 (user_point_accounts, user_point_ledger)

---

## 🚨 문제 발생 시

### 백업 복원
```bash
mysql -u [사용자명] -p dbdanora < backup_YYYYMMDD_HHMMSS.sql
```

### 특정 테이블만 복원
```sql
-- 예: products 테이블만 복원
USE mvno_db;
SOURCE backup_products_table.sql;
```

---

## 📝 추가 작업 (필요시)

### 기본 관리자 계정 생성
```sql
INSERT INTO users (user_id, password, name, email, role, status) 
VALUES ('admin', '[암호화된 비밀번호]', '관리자', 'admin@example.com', 'admin', 'active');
```

### 기본 판매자 계정 생성
```sql
INSERT INTO sellers (user_id, company_name, status) 
VALUES ('seller1', '판매자명', 'active');
```

---

## ✅ 배포 완료 체크리스트

- [ ] 백업 완료
- [ ] SQL 스크립트 실행 완료
- [ ] 테이블 구조 확인 완료
- [ ] 포인트 관련 컬럼 확인 완료
- [ ] product_type ENUM에 'mno-sim' 포함 확인
- [ ] 외래키 제약조건 확인 완료
- [ ] 웹사이트 정상 동작 확인

---

## 📞 문의
문제가 발생하면 즉시 백업을 복원하고 개발팀에 문의하세요.
