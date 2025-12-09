# 단말기 설정 데이터베이스 설치 가이드

## 설치 순서

### 1. 데이터베이스 테이블 생성

먼저 `device_tables.sql` 파일을 실행하여 테이블을 생성합니다.

```sql
-- phpMyAdmin 또는 MySQL 클라이언트에서 실행
SOURCE database/device_tables.sql;
```

또는 직접 SQL 파일 내용을 실행하세요.

### 2. 색상 필드 업데이트 (기존 테이블이 있는 경우)

만약 이미 `devices` 테이블이 생성되어 있다면, `update_device_color_field.sql` 파일을 실행하여 색상 필드를 업데이트하세요.

```sql
SOURCE database/update_device_color_field.sql;
```

### 3. 단말기 데이터 추가

제공된 단말기 데이터를 추가하려면 `insert_devices.sql` 파일을 실행하세요.

```sql
SOURCE database/insert_devices.sql;
```

## 추가되는 데이터

### 삼성 (Samsung)
- Galaxy S23 (256GB, 512GB)
- Galaxy S23+ (256GB, 512GB)
- Galaxy S23 Ultra (256GB, 512GB, 1TB)

### 애플 (Apple)
- iPhone 16 (128GB, 256GB, 512GB)
- iPhone 16 Plus (128GB, 256GB, 512GB)
- iPhone 16 Pro (128GB, 256GB, 512GB, 1TB)
- iPhone 16 Pro Max (256GB, 512GB, 1TB)
- iPhone 15 (128GB, 256GB, 512GB)

### 샤오미 (Xiaomi)
- Xiaomi 13 (256GB)
- Xiaomi 13 Pro (256GB)

## 색상값 필드 사용법

`color_values` 필드는 JSON 형태로 색상값을 저장할 수 있습니다.

예시:
```json
[
  {"name": "블랙", "value": "#000000"},
  {"name": "화이트", "value": "#FFFFFF"},
  {"name": "블루", "value": "#0066CC"}
]
```

관리 페이지에서 단말기를 수정할 때 "색상값 (JSON)" 필드에 위와 같은 형태로 입력할 수 있습니다.

## 관리 페이지 접속

설정이 완료되면 다음 URL에서 단말기 설정 페이지에 접속할 수 있습니다:

```
http://localhost/MVNO/admin/settings/device-settings.php
```

