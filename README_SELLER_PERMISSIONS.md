# 판매자 권한 관리 시스템

## 개요
판매자들이 알뜰폰, 통신사폰, 인터넷 상품을 등록할 수 있도록 권한을 관리하는 시스템입니다.

## 주요 기능

### 1. 판매자 승인
- 관리자가 판매자 가입을 승인
- `/MVNO/admin/seller-approval.php`에서 승인 처리

### 2. 판매자 권한 설정
- 관리자가 승인된 판매자에게 게시판별 권한 부여
- `/MVNO/admin/seller-permissions.php`에서 권한 설정
- 체크박스로 알뜰폰, 통신사폰, 인터넷 권한 부여

### 3. 권한 체크 함수
```php
// 판매자 권한 확인
hasSellerPermission($userId, $permission); // 'mvno', 'mno', 'internet'

// 현재 판매자 권한 확인
canSellerPost($permission);
```

## 사용 방법

### 관리자
1. 판매자 승인: `/MVNO/admin/seller-approval.php`
2. 권한 설정: `/MVNO/admin/seller-permissions.php`
   - 승인된 판매자 목록에서 체크박스로 권한 부여
   - 알뜰폰, 통신사폰, 인터넷 중 선택 가능

### 판매자
1. 상품 등록 시 권한 자동 체크
2. 권한이 없으면 등록 불가 메시지 표시

## 파일 구조
- `includes/data/auth-functions.php` - 권한 관리 함수
- `admin/seller-approval.php` - 판매자 승인 페이지
- `admin/seller-permissions.php` - 권한 설정 페이지
- `api/product-register.php` - 상품 등록 API (권한 체크 포함)
- `includes/components/seller-product-form.php` - 상품 등록 폼 예제







