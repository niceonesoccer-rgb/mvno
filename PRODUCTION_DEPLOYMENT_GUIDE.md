# 프로덕션 서버 배포 가이드
## ganadamobile.co.kr 루트에 직접 배포

## 📋 배포 전 확인 사항

### 1. 경로 설정 확인
- `includes/data/path-config.php` 파일이 자동으로 경로를 감지합니다
- 로컬: `/MVNO/` 사용
- 프로덕션(`ganadamobile.co.kr`): `/` (루트) 사용

### 2. 주요 파일 수정 완료
- ✅ `includes/header.php` - CSS, JS, 네비게이션 링크
- ✅ `includes/footer.php` - 푸터 링크
- ✅ `includes/components/point-usage-modal.php` - API 경로
- ✅ `includes/data/notice-functions.php` - 업로드 경로
- ✅ `includes/data/seller-inquiry-functions.php` - 업로드 경로

---

## 🚀 배포 절차

### 1단계: 로컬에서 테스트

1. **로컬에서 정상 동작 확인:**
   - `http://localhost/MVNO/` 접속
   - 모든 페이지, 이미지, CSS, JS가 정상 로드되는지 확인

2. **일괄 경로 변경 스크립트 실행 (선택사항):**
   - `fix-all-paths.php` 파일을 브라우저에서 실행
   - 또는 명령줄에서: `php fix-all-paths.php`
   - **주의:** 백업을 먼저 받으세요!

---

### 2단계: 프로덕션 서버에 업로드

1. **FTP 클라이언트 연결:**
   - `ganadamobile.co.kr` 연결
   - `/www_root` 또는 루트 디렉토리로 이동

2. **파일 업로드:**
   - 로컬 `mvno` 폴더의 **모든 내용**을 선택
   - 프로덕션 서버 **루트**에 직접 업로드
   - **주의:** `mvno` 폴더 자체를 업로드하지 말고, 폴더 **내부의 모든 파일**을 업로드

3. **폴더 구조 확인:**
   ```
   프로덕션 서버 루트/
   ├── index.php
   ├── includes/
   ├── assets/
   ├── api/
   ├── admin/
   ├── mvno/
   ├── mno/
   ├── mno-sim/
   ├── internets/
   └── ... (기타 폴더들)
   ```

---

### 3단계: 데이터베이스 연결 확인

1. **DB 설정 확인:**
   - `includes/data/db-config.php` 파일 확인
   - 프로덕션 DB 정보가 올바른지 확인

2. **DB 연결 테스트:**
   - 관리자 페이지 접속: `ganadamobile.co.kr/admin/`
   - 로그인 후 정상 동작 확인

---

### 4단계: 파일 권한 설정

1. **업로드 폴더 권한:**
   - `uploads/` 폴더: 755 또는 777
   - 하위 폴더들도 동일하게 설정

2. **쓰기 권한이 필요한 폴더:**
   - `uploads/notices/`
   - `uploads/seller-inquiries/`
   - `uploads/events/`
   - `cache/` (있는 경우)
   - `logs/` (있는 경우)

---

## ✅ 배포 후 확인 사항

### 1. 웹사이트 접속 확인
- [ ] `ganadamobile.co.kr` 접속 정상
- [ ] 메인 페이지 로드 정상
- [ ] CSS/JS 파일 로드 정상
- [ ] 이미지 표시 정상

### 2. 페이지별 확인
- [ ] 알뜰폰 페이지 (`/mvno/mvno.php`)
- [ ] 통신사폰 페이지 (`/mno/mno.php`)
- [ ] 통신사단독유심 페이지 (`/mno-sim/mno-sim.php`)
- [ ] 인터넷 페이지 (`/internets/internets.php`)
- [ ] 마이페이지 (`/mypage/mypage.php`)

### 3. 기능 확인
- [ ] 로그인/회원가입
- [ ] 상품 신청
- [ ] 포인트 사용
- [ ] 이미지 업로드

---

## 🔧 문제 해결

### 이미지가 깨지는 경우
1. 개발자 도구(F12) → Network 탭 확인
2. 실패한 이미지 파일의 경로 확인
3. 실제 파일이 서버에 존재하는지 확인
4. 파일 권한 확인

### CSS/JS가 로드되지 않는 경우
1. 개발자 도구(F12) → Console 탭 확인
2. 오류 메시지 확인
3. 파일 경로가 올바른지 확인
4. `includes/data/path-config.php` 파일 확인

### 404 오류가 발생하는 경우
1. 파일이 실제로 업로드되었는지 확인
2. 파일명 대소문자 확인 (Linux는 대소문자 구분)
3. `.htaccess` 파일 확인

---

## 📝 참고사항

- **로컬 환경:** `/MVNO/` 경로 사용
- **프로덕션 환경:** `/` (루트) 경로 사용
- 경로는 `includes/data/path-config.php`에서 자동 감지됩니다
- 모든 하드코딩된 경로를 수정하지 않아도, 주요 파일들은 이미 수정되었습니다

---

## 🗑️ 배포 후 삭제할 파일

배포 완료 후 다음 파일들을 삭제하세요:
- `fix-all-paths.php` (일괄 변경 스크립트)
- `PRODUCTION_DEPLOYMENT_GUIDE.md` (이 파일)
- `PRODUCTION_PATH_FIX.md` (가이드 파일)
- 기타 테스트/임시 파일들
