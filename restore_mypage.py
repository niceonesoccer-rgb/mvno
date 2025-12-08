#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
마이페이지 관련 파일 복구 스크립트
어제 10시 이전 커밋에서 mypage 관련 파일만 복구
"""

import subprocess
import sys
from datetime import datetime, timedelta
import re

def run_git_command(cmd):
    """Git 명령어 실행"""
    try:
        result = subprocess.run(
            cmd,
            shell=True,
            capture_output=True,
            text=True,
            encoding='utf-8'
        )
        return result.stdout.strip(), result.returncode
    except Exception as e:
        print(f"오류 발생: {e}")
        return "", 1

def main():
    print("=" * 50)
    print("마이페이지 파일 복구 스크립트")
    print("=" * 50)
    print()
    
    # mypage 관련 파일 목록
    mypage_files = [
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
    
    # 어제 10시 계산
    yesterday = datetime.now() - timedelta(days=1)
    target_time = yesterday.replace(hour=10, minute=0, second=0, microsecond=0)
    target_time_str = target_time.strftime("%Y-%m-%d %H:%M:%S")
    
    print(f"대상 시간: {target_time_str}")
    print()
    
    # 커밋 목록 가져오기
    print("1단계: 커밋 목록 검색 중...")
    cmd = f'git log --all --pretty=format:"%H|%ai|%s" --date=iso --until="{target_time_str}"'
    output, returncode = run_git_command(cmd)
    
    if returncode != 0 or not output:
        print("오류: 커밋을 찾을 수 없습니다.")
        print("\n최근 커밋 목록:")
        output, _ = run_git_command('git log --all --pretty=format:"%H|%ai|%s" --date=iso -20')
        if output:
            commits = output.split('\n')
            for i, commit in enumerate(commits[:10], 1):
                parts = commit.split('|', 2)
                if len(parts) >= 3:
                    hash_part = parts[0][:8]
                    date = parts[1]
                    message = parts[2]
                    print(f"  {i}. {hash_part} | {date} | {message}")
        sys.exit(1)
    
    # 커밋 파싱
    commits = []
    for line in output.split('\n'):
        if '|' in line:
            parts = line.split('|', 2)
            if len(parts) >= 3:
                commits.append({
                    'hash': parts[0],
                    'date': parts[1],
                    'message': parts[2]
                })
    
    if not commits:
        print("해당 시간 이전의 커밋을 찾을 수 없습니다.")
        sys.exit(1)
    
    print(f"\n찾은 커밋: {len(commits)}개")
    print("\n최근 커밋 목록 (최대 10개):")
    for i, commit in enumerate(commits[:10], 1):
        hash_short = commit['hash'][:8]
        print(f"  {i}. {hash_short} | {commit['date']} | {commit['message']}")
    print()
    
    # mypage 관련 파일이 변경된 커밋 찾기
    print("2단계: mypage 관련 파일이 변경된 커밋 검색 중...")
    mypage_commits = []
    
    for commit in commits:
        cmd = f'git diff-tree --no-commit-id --name-only -r {commit["hash"]}'
        output, _ = run_git_command(cmd)
        if output:
            changed_files = output.split('\n')
            has_mypage = any('mypage' in f for f in changed_files)
            if has_mypage:
                mypage_commits.append(commit)
    
    if mypage_commits:
        print(f"\n찾은 mypage 관련 커밋: {len(mypage_commits)}개")
        print("\nmypage 관련 커밋 목록:")
        for i, commit in enumerate(mypage_commits[:5], 1):
            hash_short = commit['hash'][:8]
            print(f"  {i}. {hash_short} | {commit['date']} | {commit['message']}")
        target_commit = mypage_commits[0]['hash']
        print(f"\n사용할 커밋: {target_commit[:8]} (가장 최근)")
    else:
        print("\nmypage 관련 파일이 변경된 커밋을 찾을 수 없습니다.")
        print("수동으로 커밋을 선택하세요.")
        print("\n사용 가능한 커밋 목록:")
        for i, commit in enumerate(commits[:10], 1):
            hash_short = commit['hash'][:8]
            print(f"  {i}. {hash_short} | {commit['date']} | {commit['message']}")
        
        choice = input("\n커밋 번호를 입력하거나 해시를 직접 입력하세요: ").strip()
        
        if choice.isdigit():
            idx = int(choice) - 1
            if 0 <= idx < len(commits):
                target_commit = commits[idx]['hash']
            else:
                print("잘못된 번호입니다.")
                sys.exit(1)
        else:
            target_commit = choice
    
    print()
    print("=" * 50)
    print("3단계: 파일 복구")
    print("=" * 50)
    print()
    print("다음 파일들을 복구합니다:")
    for file in mypage_files:
        print(f"  - {file}")
    print()
    
    confirm = input("계속하시겠습니까? (Y/N): ").strip().upper()
    if confirm != 'Y':
        print("복구가 취소되었습니다.")
        sys.exit(0)
    
    print()
    print("파일 복구 중...")
    success_count = 0
    fail_count = 0
    
    for file in mypage_files:
        cmd = f'git checkout {target_commit} -- {file}'
        output, returncode = run_git_command(cmd)
        if returncode == 0:
            print(f"  ✓ {file}")
            success_count += 1
        else:
            print(f"  ✗ {file} (복구 실패 - 파일이 해당 커밋에 없을 수 있음)")
            fail_count += 1
    
    print()
    print("=" * 50)
    print("복구 완료!")
    print("=" * 50)
    print(f"성공: {success_count}개, 실패: {fail_count}개")
    print()
    print("변경사항을 확인하려면 'git status'를 실행하세요.")
    print("변경사항을 되돌리려면 'git restore mypage/*'를 실행하세요.")

if __name__ == "__main__":
    main()







