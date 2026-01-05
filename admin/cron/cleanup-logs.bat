@echo off
REM Windows용 로그 정리 스크립트
REM 작업 스케줄러에서 이 파일을 실행하도록 설정

chcp 65001 >nul
cd /d "%~dp0\..\.."

php admin\cron\cleanup-logs.php

REM 결과를 로그 파일에 저장하려면:
REM php admin\cron\cleanup-logs.php >> logs\cleanup-logs.log 2>&1
