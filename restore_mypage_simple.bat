@echo off
chcp 65001 >nul
echo ========================================
echo 마이페이지 파일 복구 스크립트
echo ========================================
echo.

echo 1단계: 어제 10시 이전 커밋 검색 중...
echo.

REM 어제 날짜 계산 (PowerShell 사용)
for /f "tokens=*" %%i in ('powershell -Command "(Get-Date).AddDays(-1).ToString('yyyy-MM-dd')"') do set YESTERDAY=%%i
set TARGET_DATE=%YESTERDAY% 10:00:00

echo 대상 시간: %TARGET_DATE%
echo.

REM 커밋 목록 표시
echo 최근 커밋 목록 (어제 10시 이전):
git log --all --oneline --until="%TARGET_DATE%" -10
echo.

echo ========================================
echo 2단계: mypage 관련 파일 복구
echo ========================================
echo.

echo 다음 중 하나를 선택하세요:
echo 1. 자동으로 가장 최근 커밋에서 복구
echo 2. 커밋 해시를 직접 입력하여 복구
echo.
set /p choice="선택 (1 또는 2): "

if "%choice%"=="1" (
    echo.
    echo 가장 최근 커밋에서 복구 중...
    for /f "tokens=1" %%c in ('git log --all --format="%%H" --until="%TARGET_DATE%" -1') do set COMMIT_HASH=%%c
    if "!COMMIT_HASH!"=="" (
        echo 오류: 해당 시간 이전의 커밋을 찾을 수 없습니다.
        pause
        exit /b 1
    )
    echo 사용할 커밋: !COMMIT_HASH!
) else if "%choice%"=="2" (
    echo.
    set /p COMMIT_HASH="커밋 해시를 입력하세요: "
) else (
    echo 잘못된 선택입니다.
    pause
    exit /b 1
)

echo.
echo 다음 파일들을 복구합니다:
echo   - mypage/mypage.php
echo   - mypage/account-management.php
echo   - mypage/alarm-setting.php
echo   - mypage/internet-order.php
echo   - mypage/mno-order.php
echo   - mypage/mvno-order.php
echo   - mypage/point-history.php
echo   - mypage/wishlist.php
echo   - mypage/withdraw.php
echo.
set /p confirm="계속하시겠습니까? (Y/N): "

if /i not "%confirm%"=="Y" (
    echo 복구가 취소되었습니다.
    pause
    exit /b 0
)

echo.
echo 파일 복구 중...
git checkout %COMMIT_HASH% -- mypage/mypage.php
git checkout %COMMIT_HASH% -- mypage/account-management.php
git checkout %COMMIT_HASH% -- mypage/alarm-setting.php
git checkout %COMMIT_HASH% -- mypage/internet-order.php
git checkout %COMMIT_HASH% -- mypage/mno-order.php
git checkout %COMMIT_HASH% -- mypage/mvno-order.php
git checkout %COMMIT_HASH% -- mypage/point-history.php
git checkout %COMMIT_HASH% -- mypage/wishlist.php
git checkout %COMMIT_HASH% -- mypage/withdraw.php

echo.
echo ========================================
echo 복구 완료!
echo ========================================
echo.
echo 변경사항을 확인하려면 'git status'를 실행하세요.
echo 변경사항을 되돌리려면 'git restore mypage/*'를 실행하세요.
echo.
pause






