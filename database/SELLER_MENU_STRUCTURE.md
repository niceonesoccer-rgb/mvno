# 판매자 페이지 메뉴 구조 분석 및 광고 시스템 추가 제안

## 📋 현재 판매자 페이지 구조

### 디렉토리 구조

```
seller/
├── index.php              (대시보드)
├── profile.php            (프로필 관리)
├── products/              (상품 관리)
│   ├── index.php
│   ├── list.php           (전체 상품 목록)
│   ├── mvno-list.php
│   ├── mvno.php           (알뜰폰 등록/수정)
│   ├── mno-list.php
│   ├── mno.php            (통신사폰 등록/수정)
│   ├── internet-list.php
│   ├── internet.php       (인터넷 등록/수정)
│   ├── mno-sim-list.php
│   └── mno-sim.php        (통신사단독유심 등록/수정)
├── orders/                (주문 관리)
│   ├── mvno.php
│   ├── mno.php
│   ├── mno-sim.php
│   └── internet.php
├── bidding/               (입찰 시스템)
│   ├── list.php
│   ├── detail.php
│   ├── participate.php
│   └── deposits.php
├── inquiry/               (1:1 문의)
│   ├── inquiry-list.php
│   ├── inquiry-write.php
│   ├── inquiry-detail.php
│   └── ...
├── notice/                (공지사항)
│   ├── index.php
│   └── detail.php
└── includes/
    ├── seller-header.php  (헤더/사이드바)
    └── seller-footer.php
```

---

## 💡 현재 메뉴 구조 (seller-header.php 확인 필요)

seller-header.php 파일을 확인한 결과, 메뉴 구조가 코드에 포함되어 있을 것으로 예상됩니다.

예상되는 현재 메뉴 구조:

```
판매자 센터
├── 대시보드
├── 상품 관리
│   ├── 등록 상품 (전체 목록)
│   ├── 알뜰폰 등록/관리
│   ├── 통신사폰 등록/관리
│   ├── 인터넷 등록/관리
│   └── 통신사단독유심 등록/관리
├── 주문 관리
│   ├── 알뜰폰 주문
│   ├── 통신사폰 주문
│   ├── 통신사단독유심 주문
│   └── 인터넷 주문
├── 입찰 관리
│   ├── 입찰 목록
│   ├── 입찰 참여
│   └── 입찰 보증금
├── 1:1 문의
├── 공지사항
└── 프로필
```

---

## 🎯 광고 시스템 추가 제안

### 제안: 광고 관리 섹션 추가

기존 구조에 "광고 관리" 및 "예치금 관리" 섹션을 추가하는 방식입니다.

```
판매자 센터
├── 대시보드
├── 상품 관리
│   ├── 등록 상품
│   ├── 알뜰폰 등록/관리
│   ├── 통신사폰 등록/관리
│   ├── 인터넷 등록/관리
│   └── 통신사단독유심 등록/관리
├── 광고 관리 ⬅️ 신규
│   ├── 광고 신청
│   └── 광고 내역
├── 예치금 관리 ⬅️ 신규
│   ├── 예치금 충전
│   └── 예치금 내역
├── 주문 관리
├── 입찰 관리
├── 1:1 문의
├── 공지사항
└── 프로필
```

---

## 📁 신규 디렉토리 및 파일 구조

```
seller/
├── advertisement/         ⬅️ 신규
│   ├── register.php       (광고 신청)
│   └── list.php           (광고 내역)
└── deposit/               ⬅️ 신규
    ├── charge.php         (예치금 충전)
    └── history.php        (예치금 내역)
```

---

## 📄 페이지 상세 설계

### 1. 광고 신청 페이지 (/seller/advertisement/register.php)

**기능:**
- 상품 선택 (본인이 등록한 상품 중에서 선택)
- 카테고리 자동 설정 (선택한 상품의 product_type)
- 로테이션 시간 선택 (10초, 30초, 60초, 300초)
- 광고 기간 선택 (1일, 2일, 3일, 5일, 7일, 10일 등)
- 가격 표시 (카테고리별, 시간별, 기간별 가격)
- 입금금액 표시 (부가세 포함: 가격 × 1.1)
- 예치금 잔액 확인
- 광고 신청 처리

**UI 구조:**
```
[상품 선택 드롭다운]
[카테고리: 자동 설정 (읽기 전용)]
[로테이션 시간: 10초 / 30초 / 60초 / 300초]
[광고 기간: 1일 / 2일 / 3일 / 5일 / 7일 / 10일]
[광고 금액: XXX원]
[입금금액 (부가세 포함): XXX원]
[예치금 잔액: XXX원]
[광고 신청] 버튼
```

---

### 2. 광고 내역 페이지 (/seller/advertisement/list.php)

**기능:**
- 광고 목록 조회 (본인이 신청한 광고만)
- 필터링: 전체, 광고중, 광고중지, 광고종료
- 카테고리별 필터링 (선택사항)

**표시 정보:**
- 상품명
- 카테고리
- 로테이션 시간
- 광고 기간
- 광고 금액
- 광고 상태 (광고중, 광고중지, 광고종료)
- 시작일/종료일
- 남은 시간 (광고중인 경우)

**상태별 버튼:**
- 광고중: 상태 표시만
- 광고중지: 상태 표시만 (상품이 판매종료 상태)
- 광고종료: "다시 광고신청" 버튼

---

### 3. 예치금 충전 페이지 (/seller/deposit/charge.php)

**기능:**
- 입금 금액 입력 (부가세 포함 금액)
- 공급가액, 부가세 자동 계산 표시
- 무통장 계좌 선택 (관리자가 등록한 계좌 중에서)
- 입금자명 입력
- 예치금 충전 신청

**UI 구조:**
```
[입금 금액 입력] (부가세 포함)
- 공급가액: XXX원
- 부가세 (10%): XXX원
- 입금금액: XXX원

[무통장 계좌 선택]
- 은행명: XXX
- 계좌번호: XXX-XXX-XXXXXX
- 예금주: XXX

[입금자명 입력]
[예치금 충전 신청] 버튼
```

---

### 4. 예치금 내역 페이지 (/seller/deposit/history.php)

**기능:**
- 예치금 잔액 표시
- 예치금 내역 조회 (충전, 차감)
- 필터링: 전체, 충전, 차감

**표시 정보:**
- 거래 일시
- 거래 유형 (충전, 광고 차감)
- 금액 (충전: +, 차감: -)
- 잔액
- 설명 (충전: 입금 신청 ID, 차감: 광고 ID)

---

## 🔧 seller-header.php 메뉴 추가 코드

```php
<!-- 광고 관리 -->
<div class="menu-section">
    <div class="menu-section-title">광고 관리</div>
    <a href="/MVNO/seller/advertisement/register.php" class="menu-item <?php echo ($currentPage === 'register.php' && strpos($_SERVER['REQUEST_URI'], '/advertisement/') !== false) ? 'active' : ''; ?>">
        <span class="menu-item-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        </span>
        광고 신청
    </a>
    <a href="/MVNO/seller/advertisement/list.php" class="menu-item <?php echo ($currentPage === 'list.php' && strpos($_SERVER['REQUEST_URI'], '/advertisement/') !== false) ? 'active' : ''; ?>">
        <span class="menu-item-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </span>
        광고 내역
    </a>
</div>

<!-- 예치금 관리 -->
<div class="menu-section">
    <div class="menu-section-title">예치금 관리</div>
    <a href="/MVNO/seller/deposit/charge.php" class="menu-item <?php echo ($currentPage === 'charge.php' && strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>">
        <span class="menu-item-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
            </svg>
        </span>
        예치금 충전
    </a>
    <a href="/MVNO/seller/deposit/history.php" class="menu-item <?php echo ($currentPage === 'history.php' && strpos($_SERVER['REQUEST_URI'], '/deposit/') !== false) ? 'active' : ''; ?>">
        <span class="menu-item-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </span>
        예치금 내역
    </a>
</div>
```

---

## 📝 구현 순서

1. **seller-header.php 수정**
   - 광고 관리 섹션 추가
   - 예치금 관리 섹션 추가

2. **디렉토리 생성**
   - `seller/advertisement/` 디렉토리 생성
   - `seller/deposit/` 디렉토리 생성

3. **페이지 파일 생성**
   - 광고 신청 페이지
   - 광고 내역 페이지
   - 예치금 충전 페이지
   - 예치금 내역 페이지

4. **API 구현**
   - 광고 신청 API
   - 광고 목록 조회 API
   - 예치금 충전 신청 API
   - 예치금 내역 조회 API

---

위 구조로 진행하시겠습니까? 다른 방식이 필요하시면 알려주세요.