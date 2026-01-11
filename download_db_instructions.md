# 서버에서 데이터베이스 다운로드 방법

## 서버 정보
- **호스트**: db.ganadamobile.co.kr
- **데이터베이스명**: dbdanora
- **사용자명**: danora
- **비밀번호**: 2leosim@*ly

## 방법 1: phpMyAdmin 사용 (추천)

1. 웹브라우저에서 서버의 phpMyAdmin 접속
   - 예: `http://서버주소/phpmyadmin` 또는 `https://서버주소/phpmyadmin`

2. 로그인 정보 입력:
   - 사용자명: `danora`
   - 비밀번호: `2leosim@*ly`

3. 왼쪽 목록에서 `dbdanora` 데이터베이스 선택

4. 상단의 **"내보내기(Export)"** 탭 클릭

5. 설정 선택:
   - **방법**: "빠른" 또는 "사용자 정의" (사용자 정의 권장)
   - **형식**: SQL
   - (사용자 정의 선택 시) 옵션:
     - ✓ 구조와 데이터 모두 포함
     - ✓ 외래 키 체크 비활성화 체크
     - ✓ 추가 옵션: "IF NOT EXISTS" 추가

6. **"실행"** 버튼 클릭하여 SQL 파일 다운로드

7. 다운로드된 파일을 `C:\xampp\htdocs\mvno\` 디렉토리에 저장

---

## 방법 2: SSH 접속 (고급 사용자용)

SSH로 서버에 접속할 수 있는 경우:

```bash
# SSH로 서버 접속
ssh 사용자명@서버주소

# 데이터베이스 덤프 생성
mysqldump -h db.ganadamobile.co.kr -u danora -p dbdanora > dbdanora_backup.sql

# 비밀번호 입력: 2leosim@*ly

# SCP로 파일 다운로드 (다른 터미널에서)
scp 사용자명@서버주소:~/dbdanora_backup.sql ./
```

---

## 방법 3: 호스팅 패널 사용

cPanel, Plesk 등 호스팅 패널이 있는 경우:

1. 호스팅 패널에 로그인
2. 데이터베이스 관리 섹션으로 이동
3. `dbdanora` 데이터베이스 선택
4. "백업" 또는 "Export" 기능 사용
5. SQL 파일 다운로드

---

## 다운로드 후 다음 단계

1. SQL 파일을 `C:\xampp\htdocs\mvno\` 디렉토리에 저장
2. 로컬 MySQL에 데이터베이스 복원
3. 설정 파일 확인 (이미 로컬 설정으로 되어 있음)
