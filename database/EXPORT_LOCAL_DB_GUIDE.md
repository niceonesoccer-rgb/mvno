# 로컬 DB를 프로덕션 서버에 업로드하는 방법

## 📋 개요
로컬 `mvno_db` 데이터베이스를 프로덕션 서버 `dbdanora`에 업로드합니다.

---

## 방법 1: phpMyAdmin 사용 (권장)

### 1단계: 로컬 DB 내보내기

1. **로컬 phpMyAdmin 접속**
   - `http://localhost/phpmyadmin` 접속

2. **데이터베이스 선택**
   - 왼쪽에서 `mvno_db` 선택

3. **내보내기**
   - 상단 "내보내기" 탭 클릭
   - **형식**: SQL
   - **방법**: 사용자 정의
   - **옵션**:
     - ✅ 구조와 데이터 모두 선택
     - ✅ "DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT / TRIGGER 구문 추가" 체크
     - ✅ "CREATE DATABASE / USE 구문 추가" 체크 해제 (프로덕션 DB 이름이 다르므로)
   - "실행" 클릭하여 SQL 파일 다운로드

### 2단계: 프로덕션 서버에 업로드

1. **프로덕션 phpMyAdmin 접속**
   - `ganadamobile.co.kr/phpmyadmin` 또는 호스팅 제공 업체의 phpMyAdmin 접속

2. **데이터베이스 선택**
   - 왼쪽에서 `dbdanora` 선택

3. **가져오기**
   - 상단 "가져오기" 탭 클릭
   - 다운로드한 SQL 파일 선택
   - "실행" 클릭

---

## 방법 2: DBeaver 사용

### 1단계: 로컬 DB 내보내기

1. **DBeaver에서 로컬 DB 연결**
   - `mvno_db` 데이터베이스 선택

2. **데이터베이스 내보내기**
   - `mvno_db` 우클릭 → "도구" → "데이터베이스 내보내기"
   - 또는 상단 메뉴: "데이터베이스" → "도구" → "데이터베이스 내보내기"

3. **설정**
   - **대상**: 파일
   - **형식**: SQL
   - **옵션**:
     - ✅ "DROP 문 포함" 체크
     - ✅ "CREATE 문 포함" 체크
     - ✅ "데이터 포함" 체크
     - ❌ "CREATE DATABASE" 체크 해제 (프로덕션 DB 이름이 다르므로)
   - "시작" 클릭하여 SQL 파일 생성

### 2단계: SQL 파일 수정

생성된 SQL 파일을 열어서:

1. **USE 문 제거**
   - 파일 상단의 `USE mvno_db;` 또는 `USE \`mvno_db\`;` 제거

2. **데이터베이스 이름 변경 (선택사항)**
   - `CREATE DATABASE` 문이 있다면 제거하거나 주석 처리

### 3단계: 프로덕션 서버에 업로드

1. **DBeaver에서 프로덕션 DB 연결**
   - `dbdanora` 데이터베이스 연결

2. **SQL 스크립트 실행**
   - SQL 편집기 열기 (Alt+X 또는 상단 메뉴)
   - 내보낸 SQL 파일 내용 복사하여 붙여넣기
   - **중요**: 파일 맨 앞에 `USE dbdanora;` 추가
   - 실행 (Ctrl+Enter 또는 실행 버튼)

---

## 방법 3: mysqldump 명령줄 사용

### 1단계: 로컬 DB 내보내기

```bash
# Windows (XAMPP)
cd C:\xampp\mysql\bin
mysqldump.exe -u root --no-create-db mvno_db > C:\xampp\htdocs\mvno\database\mvno_db_export.sql
```

또는 스키마와 데이터 모두:

```bash
mysqldump.exe -u root --no-create-db --routines --triggers mvno_db > C:\xampp\htdocs\mvno\database\mvno_db_full_export.sql
```

### 2단계: SQL 파일 수정

생성된 SQL 파일을 열어서:
- 파일 맨 앞에 `USE dbdanora;` 추가

### 3단계: 프로덕션 서버에 업로드

**FTP/SFTP로 파일 업로드 후:**
- phpMyAdmin에서 가져오기
- 또는 SSH 접속 후:
  ```bash
  mysql -u danora -p dbdanora < mvno_db_full_export.sql
  ```

---

## ⚠️ 주의사항

1. **백업 필수**
   - 프로덕션 서버의 기존 데이터를 먼저 백업하세요
   - 현재 테이블이 없으므로 백업은 선택사항이지만, 혹시 모를 데이터가 있을 수 있으니 확인하세요

2. **데이터베이스 이름**
   - 로컬: `mvno_db`
   - 프로덕션: `dbdanora`
   - SQL 파일에서 `USE mvno_db;` 문을 제거하거나 `USE dbdanora;`로 변경

3. **문자셋**
   - 내보내기 시 `utf8mb4` 문자셋 사용 확인

4. **파일 크기**
   - 파일이 크면 phpMyAdmin의 `upload_max_filesize` 제한에 걸릴 수 있습니다
   - 이 경우 DBeaver나 명령줄 사용 권장

---

## ✅ 업로드 후 확인

1. **테이블 확인**
   ```sql
   USE dbdanora;
   SHOW TABLES;
   ```

2. **데이터 확인**
   ```sql
   SELECT COUNT(*) FROM products;
   SELECT COUNT(*) FROM app_settings;
   ```

3. **웹사이트 확인**
   - `ganadamobile.co.kr/?debug=1` 접속하여 디버깅 정보 확인
   - 메인 페이지에 상품이 표시되는지 확인

---

## 🚨 문제 발생 시

### 에러: "Table already exists"
- SQL 파일에서 `DROP TABLE IF EXISTS` 문이 포함되어 있는지 확인
- 또는 프로덕션 DB의 기존 테이블을 먼저 삭제

### 에러: "Unknown database 'mvno_db'"
- SQL 파일에서 `USE mvno_db;` 문을 제거하거나 `USE dbdanora;`로 변경

### 에러: "Access denied"
- 프로덕션 DB 사용자 권한 확인
- `danora` 사용자에게 `CREATE`, `DROP`, `INSERT`, `UPDATE` 권한이 있는지 확인
