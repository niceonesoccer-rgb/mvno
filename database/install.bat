@echo off
echo MVNO 데이터베이스 설치 중...
echo.

REM MySQL 경로 설정 (XAMPP 기본 경로)
set MYSQL_PATH=C:\xampp\mysql\bin\mysql.exe

REM 데이터베이스 생성 및 스키마 실행
"%MYSQL_PATH%" -u root -e "CREATE DATABASE IF NOT EXISTS mvno_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
"%MYSQL_PATH%" -u root mvno_db < products_schema.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================
    echo 데이터베이스 설치 완료!
    echo ============================================
    echo 데이터베이스명: mvno_db
    echo.
) else (
    echo.
    echo ============================================
    echo 오류 발생!
    echo ============================================
    echo MySQL 경로를 확인하거나 phpMyAdmin을 사용하세요.
    echo.
)

pause


