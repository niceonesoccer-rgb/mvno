@echo off
chcp 65001 >nul
echo ========================================
echo XAMPP 로그 확인 스크립트
echo ========================================
echo.

echo [Apache 에러 로그 - 최근 20줄]
echo ----------------------------------------
powershell -Command "Get-Content C:\xampp\apache\logs\error.log -Tail 20 -Encoding UTF8"
echo.
echo.

echo [PHP 에러 로그 - 최근 20줄]
echo ----------------------------------------
powershell -Command "Get-Content C:\xampp\php\logs\php_error_log -Tail 20 -Encoding UTF8"
echo.
echo.

echo [MySQL 에러 로그 찾기]
echo ----------------------------------------
for /r C:\xampp\mysql\data %%f in (*.err) do (
    echo 파일: %%f
    powershell -Command "Get-Content '%%f' -Tail 10 -Encoding UTF8"
    echo.
)
echo.
echo ========================================
echo 로그 확인 완료
echo ========================================
pause









