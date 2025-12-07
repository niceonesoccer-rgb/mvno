# 마이페이지 관련 파일 복구 스크립트
# 어제 10시 이전 커밋에서 mypage 관련 파일만 복구

Write-Host "=== 마이페이지 파일 복구 스크립트 ===" -ForegroundColor Green
Write-Host ""

# mypage 관련 파일 목록
$mypageFiles = @(
    "mypage/mypage.php",
    "mypage/account-management.php",
    "mypage/alarm-setting.php",
    "mypage/internet-order.php",
    "mypage/mno-order.php",
    "mypage/mvno-order.php",
    "mypage/point-history.php",
    "mypage/wishlist.php",
    "mypage/withdraw.php"
)

# 어제 10시 이전 커밋 찾기
Write-Host "어제 10시 이전 커밋 검색 중..." -ForegroundColor Yellow

# 현재 날짜 기준으로 어제 10시 계산
$yesterday = (Get-Date).AddDays(-1)
$targetTime = Get-Date -Year $yesterday.Year -Month $yesterday.Month -Day $yesterday.Day -Hour 10 -Minute 0 -Second 0
$targetTimeStr = $targetTime.ToString("yyyy-MM-dd HH:mm:ss")

Write-Host "대상 시간: $targetTimeStr" -ForegroundColor Cyan
Write-Host ""

# 커밋 목록 가져오기
$commits = git log --all --pretty=format:"%H|%ai|%s" --date=iso --until="$targetTimeStr" | ConvertFrom-Csv -Delimiter "|" -Header "Hash","Date","Message"

if ($commits.Count -eq 0) {
    Write-Host "해당 시간 이전의 커밋을 찾을 수 없습니다." -ForegroundColor Red
    Write-Host ""
    Write-Host "사용 가능한 커밋 목록:" -ForegroundColor Yellow
    git log --all --pretty=format:"%H|%ai|%s" --date=iso -20
    exit
}

Write-Host "찾은 커밋 목록 (최대 10개):" -ForegroundColor Yellow
$commits | Select-Object -First 10 | ForEach-Object {
    Write-Host "  $($_.Hash.Substring(0,8)) | $($_.Date) | $($_.Message)" -ForegroundColor White
}
Write-Host ""

# mypage 관련 파일이 변경된 커밋 찾기
Write-Host "mypage 관련 파일이 변경된 커밋 검색 중..." -ForegroundColor Yellow
$mypageCommits = @()

foreach ($commit in $commits) {
    $changedFiles = git diff-tree --no-commit-id --name-only -r $commit.Hash
    $hasMypageFiles = $false
    foreach ($file in $mypageFiles) {
        if ($changedFiles -match [regex]::Escape($file)) {
            $hasMypageFiles = $true
            break
        }
    }
    if ($hasMypageFiles) {
        $mypageCommits += $commit
    }
}

if ($mypageCommits.Count -eq 0) {
    Write-Host "mypage 관련 파일이 변경된 커밋을 찾을 수 없습니다." -ForegroundColor Red
    Write-Host ""
    Write-Host "수동으로 커밋 해시를 입력하시겠습니까? (Y/N)" -ForegroundColor Yellow
    $response = Read-Host
    if ($response -eq "Y" -or $response -eq "y") {
        Write-Host "커밋 해시를 입력하세요:" -ForegroundColor Yellow
        $commitHash = Read-Host
        $targetCommit = $commitHash
    } else {
        exit
    }
} else {
    Write-Host "찾은 mypage 관련 커밋 (최신순):" -ForegroundColor Green
    $mypageCommits | Select-Object -First 5 | ForEach-Object {
        Write-Host "  $($_.Hash.Substring(0,8)) | $($_.Date) | $($_.Message)" -ForegroundColor White
    }
    Write-Host ""
    
    # 가장 최근 커밋 사용
    $targetCommit = $mypageCommits[0].Hash
    Write-Host "가장 최근 커밋 사용: $($targetCommit.Substring(0,8))" -ForegroundColor Cyan
    Write-Host ""
}

# 복구 확인
Write-Host "다음 파일들을 복구하시겠습니까? (Y/N)" -ForegroundColor Yellow
$mypageFiles | ForEach-Object { Write-Host "  - $_" -ForegroundColor White }
Write-Host ""
$confirm = Read-Host

if ($confirm -ne "Y" -and $confirm -ne "y") {
    Write-Host "복구가 취소되었습니다." -ForegroundColor Red
    exit
}

# 파일 복구
Write-Host ""
Write-Host "파일 복구 중..." -ForegroundColor Yellow

foreach ($file in $mypageFiles) {
    if (Test-Path $file) {
        Write-Host "복구 중: $file" -ForegroundColor Cyan
        git checkout $targetCommit -- $file
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  ✓ 복구 완료" -ForegroundColor Green
        } else {
            Write-Host "  ✗ 복구 실패 (파일이 해당 커밋에 없을 수 있음)" -ForegroundColor Red
        }
    } else {
        Write-Host "파일 없음: $file (스킵)" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "=== 복구 완료 ===" -ForegroundColor Green
Write-Host ""
Write-Host "변경사항을 확인하려면 'git status'를 실행하세요." -ForegroundColor Cyan
Write-Host "변경사항을 되돌리려면 'git restore mypage/*'를 실행하세요." -ForegroundColor Cyan

