@echo off
echo ========================================
echo 서버에서 데이터베이스 다운로드 스크립트
echo ========================================
echo.
echo 이 스크립트는 SSH로 서버에 접속하여
echo 데이터베이스를 덤프하는 예제입니다.
echo.
echo 실제 사용 시에는 SSH 접속 정보를
echo 수정한 후 사용하세요.
echo.
echo 예시 명령어:
echo mysqldump -h db.ganadamobile.co.kr -u danora -p dbdanora ^> dbdanora_backup.sql
echo.
echo 또는 SSH를 통해:
echo ssh 사용자명@서버주소
echo mysqldump -h db.ganadamobile.co.kr -u danora -p dbdanora ^> dbdanora_backup.sql
echo.
pause
