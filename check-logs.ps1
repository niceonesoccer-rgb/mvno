# XAMPP 로그 확인 PowerShell 스크립트
# 사용법: .\check-logs.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "XAMPP 로그 확인 스크립트" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Apache 에러 로그
Write-Host "[Apache 에러 로그 - 최근 20줄]" -ForegroundColor Yellow
Write-Host "----------------------------------------" -ForegroundColor Gray
if (Test-Path "C:\xampp\apache\logs\error.log") {
    Get-Content "C:\xampp\apache\logs\error.log" -Tail 20 -Encoding UTF8
} else {
    Write-Host "Apache 로그 파일을 찾을 수 없습니다." -ForegroundColor Red
}
Write-Host ""

# PHP 에러 로그
Write-Host "[PHP 에러 로그 - 최근 20줄]" -ForegroundColor Yellow
Write-Host "----------------------------------------" -ForegroundColor Gray
if (Test-Path "C:\xampp\php\logs\php_error_log") {
    Get-Content "C:\xampp\php\logs\php_error_log" -Tail 20 -Encoding UTF8
} else {
    Write-Host "PHP 로그 파일을 찾을 수 없습니다." -ForegroundColor Red
}
Write-Host ""

# MySQL 에러 로그
Write-Host "[MySQL 에러 로그 찾기]" -ForegroundColor Yellow
Write-Host "----------------------------------------" -ForegroundColor Gray
$mysqlLogs = Get-ChildItem -Path "C:\xampp\mysql\data" -Filter "*.err" -Recurse -ErrorAction SilentlyContinue
if ($mysqlLogs) {
    foreach ($log in $mysqlLogs) {
        Write-Host "파일: $($log.FullName)" -ForegroundColor Green
        Get-Content $log.FullName -Tail 10 -Encoding UTF8
        Write-Host ""
    }
} else {
    Write-Host "MySQL 로그 파일을 찾을 수 없습니다." -ForegroundColor Red
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "로그 확인 완료" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

