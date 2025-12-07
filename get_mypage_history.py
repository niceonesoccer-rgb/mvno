#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import subprocess
import os
import sys

def run_cmd(cmd):
    try:
        result = subprocess.run(cmd, shell=True, capture_output=True, text=True, encoding='utf-8')
        return result.stdout.strip()
    except:
        return ""

print("mypage 디렉토리 파일 업데이트 이력 확인")
print("=" * 70)
print()

# 파일 목록
files = [
    "mypage/mypage.php",
    "mypage/account-management.php",
    "mypage/alarm-setting.php",
    "mypage/internet-order.php",
    "mypage/mno-order.php",
    "mypage/mvno-order.php",
    "mypage/point-history.php",
    "mypage/wishlist.php",
    "mypage/withdraw.php"
]

all_commits = []

for filepath in files:
    if not os.path.exists(filepath):
        continue
    
    filename = os.path.basename(filepath)
    print(f"확인 중: {filename}")
    
    # Git 로그 가져오기
    cmd = f'git log --all --pretty=format:"%ai|%s|%H" --date=iso -- "{filepath}"'
    output = run_cmd(cmd)
    
    commits = []
    if output:
        for line in output.split('\n'):
            if '|' in line:
                parts = line.split('|', 2)
                if len(parts) >= 3:
                    commits.append({
                        'date': parts[0],
                        'message': parts[1],
                        'hash': parts[2],
                        'file': filename
                    })
    
    if commits:
        all_commits.extend(commits)
        print(f"  ✓ {len(commits)}개 커밋 발견")
    else:
        print(f"  - 커밋 이력 없음")

print()
print("=" * 70)
print("전체 업데이트 이력 (날짜순)")
print("=" * 70)
print()

if all_commits:
    # 날짜순 정렬
    all_commits.sort(key=lambda x: x['date'], reverse=True)
    
    # 날짜별 그룹화
    by_date = {}
    for commit in all_commits:
        date_key = commit['date'][:10]
        if date_key not in by_date:
            by_date[date_key] = []
        by_date[date_key].append(commit)
    
    # 출력
    for date in sorted(by_date.keys(), reverse=True):
        commits = by_date[date]
        print(f"\n📅 {date} ({len(commits)}개 업데이트)")
        print("-" * 70)
        for commit in commits:
            time = commit['date'][11:19]
            print(f"  {time} - {commit['file']}")
            print(f"    {commit['message']}")
            print(f"    해시: {commit['hash'][:8]}")
            print()
else:
    print("커밋 이력을 찾을 수 없습니다.")
    print()
    print("파일 수정 시간 확인:")
    print("-" * 70)
    for filepath in files:
        if os.path.exists(filepath):
            stat = os.stat(filepath)
            import datetime
            mtime = datetime.datetime.fromtimestamp(stat.st_mtime)
            print(f"  {os.path.basename(filepath)}: {mtime.strftime('%Y-%m-%d %H:%M:%S')}")

# 결과를 파일로 저장
with open('mypage_update_history.txt', 'w', encoding='utf-8') as f:
    f.write("mypage 디렉토리 파일 업데이트 이력\n")
    f.write("=" * 70 + "\n\n")
    
    if all_commits:
        for date in sorted(by_date.keys(), reverse=True):
            commits = by_date[date]
            f.write(f"\n📅 {date} ({len(commits)}개 업데이트)\n")
            f.write("-" * 70 + "\n")
            for commit in commits:
                time = commit['date'][11:19]
                f.write(f"  {time} - {commit['file']}\n")
                f.write(f"    {commit['message']}\n")
                f.write(f"    해시: {commit['hash'][:8]}\n\n")
    else:
        f.write("커밋 이력을 찾을 수 없습니다.\n")

print("\n결과가 'mypage_update_history.txt' 파일에 저장되었습니다.")



