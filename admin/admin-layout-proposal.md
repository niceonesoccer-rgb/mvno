# 관리자 페이지 구성 제안

## 📋 현재 관리자 페이지 목록

### 설정 관련
- ✅ `api-settings.php` - API 설정 (SNS 로그인 키)
- ✅ `point-settings.php` - 포인트 설정
- ✅ `filter-settings.php` - 필터 설정
- ✅ `home-manage.php` - 홈 관리

### 콘텐츠 관리
- ✅ `event-manage.php` - 이벤트 관리
- ✅ `notice-manage.php` - 공지사항 관리
- ✅ `qna-manage.php` - Q&A 관리

### 사용자 관리
- ✅ `seller-approval.php` - 판매자 승인
- ✅ `seller-permissions.php` - 판매자 권한 관리

### 모니터링
- ✅ `monitor.php` - 모니터링

### 유틸리티
- ✅ `image-selector.php` - 이미지 선택기

---

## 🎨 제안하는 관리자 페이지 구조

### 1. 관리자 대시보드 (admin/index.php)
**메인 대시보드 - 전체 현황 한눈에 보기**

```
┌─────────────────────────────────────────────────┐
│  관리자 대시보드                                  │
├─────────────────────────────────────────────────┤
│                                                  │
│  📊 통계 카드 (4개)                              │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐            │
│  │ 총   │ │ 판매자│ │ 상품 │ │ 문의 │            │
│  │ 회원 │ │ 승인  │ │ 등록 │ │ 건수 │            │
│  └──────┘ └──────┘ └──────┘ └──────┘            │
│                                                  │
│  📈 최근 활동                                     │
│  - 최근 가입 회원                                 │
│  - 승인 대기 판매자                               │
│  - 최근 등록 상품                                 │
│                                                  │
│  ⚠️ 알림                                         │
│  - 승인 대기 판매자 (3명)                        │
│  - 미답변 Q&A (5건)                              │
│                                                  │
└─────────────────────────────────────────────────┘
```

### 2. 사이드바 네비게이션 구조

```
┌─────────────────┐
│  관리자 메뉴     │
├─────────────────┤
│ 🏠 대시보드      │
│                 │
│ 👥 사용자 관리   │
│   ├ 판매자 승인  │
│   ├ 판매자 권한  │
│   └ 회원 관리    │
│                 │
│ 📦 상품 관리     │
│   ├ 알뜰폰 관리  │
│   ├ 통신사폰 관리│
│   └ 인터넷 관리  │
│                 │
│ 📝 콘텐츠 관리   │
│   ├ 이벤트 관리  │
│   ├ 공지사항 관리│
│   └ Q&A 관리    │
│                 │
│ ⚙️ 설정         │
│   ├ API 설정    │
│   ├ 포인트 설정  │
│   ├ 필터 설정    │
│   └ 홈 관리     │
│                 │
│ 📊 모니터링     │
│                 │
│ 🖼️ 유틸리티     │
│   └ 이미지 선택기│
└─────────────────┘
```

### 3. 페이지 그룹화

#### 그룹 1: 대시보드
- `admin/index.php` (신규 생성 필요)

#### 그룹 2: 사용자 관리
- `admin/users/seller-approval.php` (이동)
- `admin/users/seller-permissions.php` (이동)
- `admin/users/member-list.php` (신규 생성 필요)

#### 그룹 3: 상품 관리
- `admin/products/mvno-list.php` (신규 생성 필요)
- `admin/products/mno-list.php` (신규 생성 필요)
- `admin/products/internet-list.php` (신규 생성 필요)

#### 그룹 4: 콘텐츠 관리
- `admin/content/event-manage.php` (이동)
- `admin/content/notice-manage.php` (이동)
- `admin/content/qna-manage.php` (이동)

#### 그룹 5: 설정
- `admin/settings/api-settings.php` (이동)
- `admin/settings/point-settings.php` (이동)
- `admin/settings/filter-settings.php` (이동)
- `admin/settings/home-manage.php` (이동)

#### 그룹 6: 모니터링
- `admin/monitor.php` (유지)

#### 그룹 7: 유틸리티
- `admin/utils/image-selector.php` (이동)

---

## 🎯 제안하는 레이아웃 구조

### 옵션 A: 사이드바 + 메인 콘텐츠 (추천)
```
┌──────────┬──────────────────────────┐
│          │                          │
│ 사이드바  │   메인 콘텐츠 영역        │
│ (고정)   │   (스크롤 가능)           │
│          │                          │
│          │                          │
│          │                          │
└──────────┴──────────────────────────┘
```

### 옵션 B: 상단 네비게이션
```
┌────────────────────────────────────┐
│  상단 네비게이션 바 (드롭다운 메뉴) │
├────────────────────────────────────┤
│                                    │
│      메인 콘텐츠 영역               │
│                                    │
│                                    │
└────────────────────────────────────┘
```

---

## 💡 구현 제안

### 1. 공통 관리자 레이아웃 파일 생성
- `admin/includes/admin-header.php` - 관리자 헤더 (사이드바 포함)
- `admin/includes/admin-footer.php` - 관리자 푸터
- `admin/includes/admin-sidebar.php` - 사이드바 네비게이션

### 2. 관리자 대시보드 생성
- `admin/index.php` - 메인 대시보드

### 3. 디렉토리 구조 재구성 (선택사항)
```
admin/
├── index.php (대시보드)
├── includes/
│   ├── admin-header.php
│   ├── admin-sidebar.php
│   └── admin-footer.php
├── users/
│   ├── seller-approval.php
│   ├── seller-permissions.php
│   └── member-list.php
├── products/
│   ├── mvno-list.php
│   ├── mno-list.php
│   └── internet-list.php
├── content/
│   ├── event-manage.php
│   ├── notice-manage.php
│   └── qna-manage.php
├── settings/
│   ├── api-settings.php
│   ├── point-settings.php
│   ├── filter-settings.php
│   └── home-manage.php
├── monitor.php
└── utils/
    └── image-selector.php
```

---

## 🤔 토의할 사항

1. **레이아웃 스타일**
   - 사이드바 방식 vs 상단 네비게이션 방식
   - 어떤 방식이 더 편리할까요?

2. **디렉토리 구조**
   - 현재처럼 평면 구조 유지?
   - 카테고리별로 폴더 분리?

3. **대시보드 기능**
   - 어떤 통계/정보를 보여줄까요?
   - 실시간 알림이 필요할까요?

4. **권한 관리**
   - 관리자와 서브관리자 권한 차이?
   - 서브관리자는 어떤 기능만 볼 수 있을까요?

5. **추가 필요한 페이지**
   - 상품 등록/수정 페이지?
   - 회원 상세 정보 페이지?
   - 통계/리포트 페이지?

---

## 📝 다음 단계

어떤 방향으로 진행할지 결정해주시면:
1. 공통 관리자 레이아웃 구현
2. 사이드바 네비게이션 구현
3. 관리자 대시보드 구현
4. 기존 페이지들을 새 레이아웃에 통합

진행하겠습니다!







