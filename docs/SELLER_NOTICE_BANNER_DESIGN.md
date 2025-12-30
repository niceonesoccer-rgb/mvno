# 판매자 전용 공지사항 메인배너 설계 문서

## 📋 개요

판매자에게만 공지되는 공지사항 메인배너 시스템을 설계합니다. 관리자가 공지사항을 작성하고 메인배너로 선택하면, 판매자 메인 페이지에서 모달로 배너가 표시됩니다.

## 🎯 요구사항

1. **관리자 페이지**
   - 공지사항 작성/수정/삭제
   - 메인배너 선택 기능
   - 이미지 업로드 (직관적인 UI)
   - 이미지 수정 시 기존 이미지 삭제 후 새 이미지 업로드
   - 텍스트, 이미지, 또는 둘 다 작성 가능
   - 링크 URL 설정
   - 페이지네이션

2. **판매자 페이지**
   - 메인배너로 선택된 공지사항을 모달로 표시
   - 링크 클릭 시 해당 URL로 이동
   - 카테고리 메뉴 링크 연결

## 🗄️ 데이터베이스 설계

### 1. notices 테이블 확장

현재 `notices` 테이블에 다음 컬럼들이 이미 존재합니다:
- `show_on_main` (TINYINT) - 메인페이지 표시 여부
- `image_url` (VARCHAR(500)) - 이미지 URL
- `link_url` (VARCHAR(500)) - 링크 URL
- `start_at` (DATE) - 시작일
- `end_at` (DATE) - 종료일

**추가 필요 컬럼:**
```sql
ALTER TABLE notices 
ADD COLUMN target_audience ENUM('all', 'seller', 'user') DEFAULT 'all' 
COMMENT '대상 사용자 (all: 전체, seller: 판매자만, user: 일반 사용자만)' 
AFTER show_on_main;

ADD COLUMN banner_type ENUM('text', 'image', 'both') DEFAULT 'text' 
COMMENT '배너 타입 (text: 텍스트만, image: 이미지만, both: 둘 다)' 
AFTER image_url;
```

### 2. 테이블 구조 (최종)

```
notices
├── id (VARCHAR) - 공지사항 ID
├── title (VARCHAR) - 제목
├── content (TEXT) - 내용
├── image_url (VARCHAR(500)) - 이미지 URL
├── banner_type (ENUM) - 배너 타입
├── link_url (VARCHAR(500)) - 링크 URL
├── show_on_main (TINYINT) - 메인배너 표시 여부
├── target_audience (ENUM) - 대상 사용자
├── start_at (DATE) - 시작일
├── end_at (DATE) - 종료일
├── views (INT) - 조회수
├── created_at (DATETIME) - 생성일시
└── updated_at (DATETIME) - 수정일시
```

## 📁 파일 구조

### 관리자 페이지
```
admin/
├── content/
│   └── seller-notice-manage.php  (신규 생성)
└── api/
    └── seller-notice-api.php     (신규 생성)
```

### 판매자 페이지
```
seller/
├── index.php                      (수정 - 배너 모달 추가)
└── includes/
    └── seller-notice-banner.php   (신규 생성)
```

### 공통 함수
```
includes/
└── data/
    └── notice-functions.php       (수정 - 판매자 전용 함수 추가)
```

### 업로드 디렉토리
```
uploads/
└── notices/
    └── seller/                    (신규 - 판매자 전용 공지사항 이미지)
        └── YYYY/
            └── MM/
```

## 🔧 기능 설계

### 1. 관리자 페이지: 공지사항 관리 (`admin/content/seller-notice-manage.php`)

#### 1.1 페이지 레이아웃

```
┌─────────────────────────────────────────────────────────┐
│  판매자 공지사항 관리                                     │
├─────────────────────────────────────────────────────────┤
│  [새 공지사항 작성] 버튼                                  │
├─────────────────────────────────────────────────────────┤
│  공지사항 목록 (테이블)                                    │
│  ┌──────┬──────────┬──────────┬──────────┬──────────┐  │
│  │ 번호 │ 제목      │ 배너타입 │ 메인배너 │ 작성일   │  │
│  ├──────┼──────────┼──────────┼──────────┼──────────┤  │
│  │  1   │ 공지제목  │ 이미지   │ ✅       │ 2025-01-01│ │
│  └──────┴──────────┴──────────┴──────────┴──────────┘  │
│                                                          │
│  [이전] [1] [2] [3] [다음]  (페이지네이션)                │
└─────────────────────────────────────────────────────────┘
```

#### 1.2 공지사항 작성/수정 모달

```
┌─────────────────────────────────────────────────────────┐
│  공지사항 작성/수정                              [X]      │
├─────────────────────────────────────────────────────────┤
│  제목 *                                                  │
│  [________________________________]                      │
│                                                          │
│  내용                                                    │
│  [________________________________]                      │
│  [________________________________]                      │
│                                                          │
│  배너 타입 *                                             │
│  ○ 텍스트만  ○ 이미지만  ○ 텍스트+이미지                 │
│                                                          │
│  이미지 업로드 (배너 타입: 이미지만, 텍스트+이미지)        │
│  ┌──────────────────────────────────────┐               │
│  │  [이미지 선택] 또는 드래그 앤 드롭      │               │
│  │                                       │               │
│  │  [기존 이미지 미리보기]                │               │
│  │  [🗑️ 삭제] 버튼                       │               │
│  └──────────────────────────────────────┘               │
│                                                          │
│  링크 URL (선택사항)                                      │
│  [________________________________]                      │
│                                                          │
│  메인배너로 표시                                          │
│  ☑ 판매자 메인 페이지에 배너로 표시                      │
│                                                          │
│  표시 기간                                                │
│  시작일: [YYYY-MM-DD]  종료일: [YYYY-MM-DD]             │
│                                                          │
│  [취소]  [저장]                                          │
└─────────────────────────────────────────────────────────┘
```

#### 1.3 이미지 업로드 UI 설계

**이미지 업로드 영역:**
- 드래그 앤 드롭 영역 표시
- "이미지 선택" 버튼 클릭으로 파일 선택
- 선택된 이미지 미리보기
- 이미지 위에 "삭제" 버튼 오버레이
- 이미지 삭제 시 기존 이미지 파일도 서버에서 삭제

**이미지 수정 시:**
1. 기존 이미지가 있는 경우 미리보기 표시
2. 새 이미지 선택 시 기존 이미지 자동 삭제
3. "삭제" 버튼으로 이미지 제거 가능
4. 이미지 제거 후 다시 선택 가능

#### 1.4 페이지네이션

- 하단에 페이지네이션 표시
- 한 페이지당 10개 또는 20개 공지사항 표시
- 페이지 번호, 이전/다음 버튼
- 현재 페이지 하이라이트

### 2. 판매자 페이지: 메인배너 모달 (`seller/index.php`)

#### 2.1 배너 표시 로직

```php
// 판매자 메인 페이지 로드 시
1. getSellerMainBanner() 함수 호출
2. target_audience = 'seller' AND show_on_main = 1 인 공지사항 조회
3. start_at, end_at 기간 체크
4. 조건 만족 시 모달로 표시
```

#### 2.2 모달 디자인

```
┌─────────────────────────────────────────────────────────┐
│                                                          │
│  [배너 이미지 또는 텍스트]                                │
│                                                          │
│  (링크가 있는 경우 클릭 가능)                             │
│                                                          │
│                                    [닫기] [링크 이동]     │
└─────────────────────────────────────────────────────────┘
```

**모달 타입별 표시:**
- **텍스트만**: 제목과 내용을 큰 텍스트로 표시
- **이미지만**: 이미지를 크게 표시
- **텍스트+이미지**: 이미지 상단에 제목/내용 표시

#### 2.3 모달 동작

1. 페이지 로드 시 자동으로 모달 표시
2. 링크가 있는 경우 "링크 이동" 버튼 표시
3. 링크 클릭 시 새 창 또는 현재 창에서 이동
4. "닫기" 버튼으로 모달 닫기
5. 모달 닫기 후 오늘 하루 동안 다시 표시하지 않기 (선택사항)

### 3. API 설계 (`admin/api/seller-notice-api.php`)

#### 3.1 엔드포인트

```
POST /admin/api/seller-notice-api.php?action=create
POST /admin/api/seller-notice-api.php?action=update
POST /admin/api/seller-notice-api.php?action=delete
POST /admin/api/seller-notice-api.php?action=delete_image
GET  /admin/api/seller-notice-api.php?action=list&page=1&limit=10
GET  /admin/api/seller-notice-api.php?action=get&id=notice_id
```

#### 3.2 요청/응답 형식

**생성 (create):**
```json
// Request
{
  "title": "공지사항 제목",
  "content": "공지사항 내용",
  "banner_type": "image",
  "link_url": "https://example.com",
  "show_on_main": true,
  "start_at": "2025-01-01",
  "end_at": "2025-12-31"
}

// Response
{
  "success": true,
  "message": "공지사항이 생성되었습니다.",
  "data": {
    "id": "notice_xxxxx",
    "image_url": "/MVNO/uploads/notices/seller/2025/01/xxx.jpg"
  }
}
```

**수정 (update):**
```json
// Request
{
  "id": "notice_xxxxx",
  "title": "수정된 제목",
  "image_url": "new_image_url" // 새 이미지 업로드 시
}

// Response
{
  "success": true,
  "message": "공지사항이 수정되었습니다."
}
```

**이미지 삭제 (delete_image):**
```json
// Request
{
  "id": "notice_xxxxx"
}

// Response
{
  "success": true,
  "message": "이미지가 삭제되었습니다."
}
```

### 4. 함수 설계 (`includes/data/notice-functions.php`)

#### 4.1 추가할 함수들

```php
// 판매자 전용 메인배너 가져오기
function getSellerMainBanner() {
    // target_audience = 'seller'
    // show_on_main = 1
    // start_at <= 현재날짜 <= end_at
    // 가장 최근 것 1개 반환
}

// 판매자 전용 공지사항 목록 가져오기 (관리자용)
function getSellerNoticesForAdmin($limit = null, $offset = 0) {
    // target_audience = 'seller' 인 모든 공지사항
    // 페이지네이션 지원
}

// 이미지 삭제 함수
function deleteNoticeImage($image_url) {
    // 서버에서 이미지 파일 삭제
    // DB에서 image_url NULL로 업데이트
}

// 공지사항 이미지 업로드 (판매자 전용 경로)
function uploadSellerNoticeImage($file) {
    // /uploads/notices/seller/YYYY/MM/ 경로에 저장
}
```

## 🎨 UI/UX 설계

### 1. 이미지 업로드 UI

**드래그 앤 드롭 영역:**
```html
<div class="image-upload-area">
  <input type="file" id="imageInput" accept="image/*" style="display: none;">
  <div class="drop-zone" id="dropZone">
    <svg>...</svg>
    <p>이미지를 드래그하거나 클릭하여 선택</p>
    <button>이미지 선택</button>
  </div>
  <div class="image-preview" id="imagePreview" style="display: none;">
    <img src="..." alt="미리보기">
    <button class="delete-image-btn">🗑️ 삭제</button>
  </div>
</div>
```

**상태별 표시:**
- 이미지 없음: 드래그 앤 드롭 영역 표시
- 이미지 있음: 미리보기 + 삭제 버튼
- 새 이미지 선택: 기존 이미지 삭제 후 새 이미지 표시

### 2. 배너 타입 선택 UI

**라디오 버튼:**
```html
<div class="banner-type-selector">
  <label>
    <input type="radio" name="banner_type" value="text">
    텍스트만
  </label>
  <label>
    <input type="radio" name="banner_type" value="image">
    이미지만
  </label>
  <label>
    <input type="radio" name="banner_type" value="both">
    텍스트+이미지
  </label>
</div>
```

**배너 타입에 따른 필드 표시/숨김:**
- 텍스트만: 이미지 업로드 영역 숨김
- 이미지만: 내용 입력 영역 숨김 (제목은 필수)
- 텍스트+이미지: 모든 필드 표시

### 3. 페이지네이션 UI

```html
<div class="pagination">
  <button class="prev-btn" <?= $page <= 1 ? 'disabled' : '' ?>>이전</button>
  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>">
      <?= $i ?>
    </a>
  <?php endfor; ?>
  <button class="next-btn" <?= $page >= $totalPages ? 'disabled' : '' ?>>다음</button>
</div>
```

### 4. 판매자 메인배너 모달 UI

```html
<div class="seller-banner-modal" id="sellerBannerModal">
  <div class="modal-content">
    <button class="close-btn">×</button>
    
    <!-- 배너 타입: 텍스트만 -->
    <div class="banner-text-only">
      <h2>공지사항 제목</h2>
      <p>공지사항 내용</p>
    </div>
    
    <!-- 배너 타입: 이미지만 -->
    <div class="banner-image-only">
      <img src="..." alt="배너 이미지">
    </div>
    
    <!-- 배너 타입: 텍스트+이미지 -->
    <div class="banner-both">
      <img src="..." alt="배너 이미지">
      <div class="banner-text-overlay">
        <h2>공지사항 제목</h2>
        <p>공지사항 내용</p>
      </div>
    </div>
    
    <div class="modal-actions">
      <button class="close-btn">닫기</button>
      <?php if ($banner['link_url']): ?>
        <a href="<?= $banner['link_url'] ?>" class="link-btn" target="_blank">
          링크 이동
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>
```

## 🔗 카테고리 메뉴 링크

### 판매자 헤더 메뉴에 추가

```php
// seller/includes/seller-header.php
<nav class="seller-nav">
  <a href="/MVNO/seller/">대시보드</a>
  <a href="/MVNO/seller/products/">상품 관리</a>
  <a href="/MVNO/seller/orders/">주문 관리</a>
  <a href="/MVNO/notice/?target=seller">공지사항</a>  <!-- 추가 -->
  ...
</nav>
```

### 공지사항 목록 페이지 수정

```php
// notice/notice.php
// target=seller 파라미터가 있으면 판매자 전용 공지사항만 표시
$target = $_GET['target'] ?? 'all';
if ($target === 'seller') {
    $notices = getSellerNotices($limit, $offset);
}
```

## 📝 구현 순서

1. **데이터베이스 스키마 수정**
   - `target_audience` 컬럼 추가
   - `banner_type` 컬럼 추가

2. **함수 추가/수정**
   - `notice-functions.php`에 판매자 전용 함수 추가
   - 이미지 삭제 함수 추가

3. **관리자 페이지 구현**
   - `seller-notice-manage.php` 생성
   - 공지사항 목록 표시
   - 작성/수정 모달
   - 이미지 업로드 UI
   - 페이지네이션

4. **API 구현**
   - `seller-notice-api.php` 생성
   - CRUD 엔드포인트 구현
   - 이미지 업로드/삭제 처리

5. **판매자 페이지 구현**
   - `seller/index.php`에 배너 모달 추가
   - 배너 표시 로직 구현
   - 모달 UI 구현

6. **카테고리 메뉴 연결**
   - 판매자 헤더에 공지사항 링크 추가
   - 공지사항 목록 페이지에 판매자 필터 추가

## 🎯 주요 고려사항

1. **이미지 관리**
   - 업로드 시 기존 이미지 자동 삭제
   - 이미지 삭제 시 서버 파일도 삭제
   - 이미지 경로는 `/MVNO/uploads/notices/seller/YYYY/MM/` 형식

2. **권한 관리**
   - 관리자만 공지사항 작성/수정/삭제 가능
   - 판매자만 배너 모달 표시

3. **성능 최적화**
   - 이미지 미리보기 시 썸네일 생성 (선택사항)
   - 페이지네이션으로 목록 로딩 최적화

4. **사용자 경험**
   - 드래그 앤 드롭으로 직관적인 이미지 업로드
   - 이미지 수정 시 기존 이미지 삭제 확인
   - 모달 닫기 후 하루 동안 다시 표시하지 않기 (선택사항)

5. **보안**
   - 이미지 파일 타입 검증
   - 파일 크기 제한
   - XSS 방지를 위한 출력 이스케이프

## 📊 데이터 흐름도

```
[관리자]
  ↓
[공지사항 작성]
  ↓
[이미지 업로드] → /uploads/notices/seller/YYYY/MM/
  ↓
[DB 저장] → notices 테이블
  ↓
[메인배너 선택] → show_on_main = 1
  ↓
[판매자 페이지 접속]
  ↓
[배너 조회] → getSellerMainBanner()
  ↓
[모달 표시] → seller/index.php
```

## ✅ 체크리스트

- [ ] 데이터베이스 스키마 수정
- [ ] notice-functions.php 함수 추가
- [ ] 관리자 페이지 UI 구현
- [ ] 이미지 업로드 UI 구현
- [ ] 이미지 삭제 기능 구현
- [ ] API 엔드포인트 구현
- [ ] 페이지네이션 구현
- [ ] 판매자 배너 모달 구현
- [ ] 카테고리 메뉴 링크 추가
- [ ] 테스트 및 디버깅


