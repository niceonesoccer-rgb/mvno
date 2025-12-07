@echo off
chcp 65001 >nul
echo ========================================
echo mypage 디렉토리 파일 업데이트 이력
echo ========================================
echo.

cd /d "%~dp0"

echo [각 파일별 최근 업데이트]
echo.

git log --all --pretty=format:"%%ai|%%s" --date=iso --name-only -- mypage/ > temp_mypage.txt 2>nul

if exist temp_mypage.txt (
    type temp_mypage.txt
    del temp_mypage.txt
) else (
    echo 커밋 이력을 찾을 수 없습니다.
)

echo.
echo ========================================
echo [파일별 상세 이력]
echo ========================================
echo.

for %%f in (mypage\*.php) do (
    echo.
    echo ==== %%~nxf ====
    git log --all --pretty=format:"  %%ai - %%s" --date=iso -10 -- "%%f"
    echo.
)

pause

