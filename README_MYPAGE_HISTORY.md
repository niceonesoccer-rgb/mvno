# mypage 디렉토리 업데이트 이력 확인 방법

## 방법 1: 전체 mypage 디렉토리 커밋 이력 확인

```bash
git log --all --pretty=format:"%ai|%s|%H" --date=iso --name-only -- mypage/
```

## 방법 2: 각 파일별 최근 커밋 확인

```bash
# mypage.php
git log --all --pretty=format:"%ai - %s" --date=iso -10 -- mypage/mypage.php

# account-management.php
git log --all --pretty=format:"%ai - %s" --date=iso -10 -- mypage/account-management.php

# alarm-setting.php
git log --all --pretty=format:"%ai - %s" --date=iso -10 -- mypage/alarm-setting.php

# internet-order.php
git log --all --pretty=format:"%ai - %s" --date=iso -10 -- mypage/internet-order.php

# mno-order.php
git log --all --pretty=format:"%ai - %s" --date=iso -10 -- mypage/mno-order.php

# mvno-order.php
git log --all --pretty=format:"%ai - %s" --date=iso -10 -- mypage/mvno-order.php

# point-history.php
git log --all --pretty=format:"%ai - %s" --date=iso -10 -- mypage/point-history.php

# wishlist.php
git log --all --pretty=format:"%ai - %s" --date=iso -10 -- mypage/wishlist.php

# withdraw.php
git log --all --pretty=format:"%ai - %s" --date=iso -10 -- mypage/withdraw.php
```

## 방법 3: 날짜별로 필터링하여 확인

```bash
# 어제 10시 이전 커밋만 확인
git log --all --pretty=format:"%ai - %s" --date=iso --until="어제날짜 10:00:00" -- mypage/

# 예: 2024-12-01 10:00 이전
git log --all --pretty=format:"%ai - %s" --date=iso --until="2024-12-01 10:00:00" -- mypage/
```

## 방법 4: Python 스크립트 사용

```bash
python get_mypage_history.py
```

또는

```bash
python check_mypage_history.py
```

## 방법 5: 배치 파일 사용

```bash
check_mypage.bat
```

## 방법 6: 특정 파일의 모든 변경 이력 보기

```bash
git log --all --pretty=format:"%ai|%an|%s" --date=iso -- mypage/mypage.php
```

## 방법 7: 파일별 최근 수정 시간 확인 (Git 이력이 없는 경우)

```powershell
Get-ChildItem -Path mypage\*.php | ForEach-Object { 
    Write-Host $_.Name
    Write-Host ("  수정 시간: " + $_.LastWriteTime.ToString('yyyy-MM-dd HH:mm:ss'))
    Write-Host ""
}
```

## 복구 방법

어제 10시 이전 버전으로 복구하려면:

```bash
# 1. 먼저 커밋 해시 확인
git log --all --pretty=format:"%H|%ai|%s" --date=iso --until="어제날짜 10:00:00" -- mypage/

# 2. 커밋 해시를 확인한 후 복구
git checkout [커밋해시] -- mypage/mypage.php
git checkout [커밋해시] -- mypage/account-management.php
# ... 등등

# 또는 한 번에
git checkout [커밋해시] -- mypage/
```

## 자동 복구 스크립트

```bash
# Python 스크립트
python restore_mypage.py

# 배치 파일
restore_mypage_simple.bat

# PowerShell 스크립트
powershell -ExecutionPolicy Bypass -File restore_mypage.ps1
```









