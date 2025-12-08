#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
mypage 디렉토리 파일들의 업데이트 이력 확인 스크립트
"""

import subprocess
import os
from collections import defaultdict

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
    print("=" * 70)
    print("mypage 디렉토리 파일 업데이트 이력 확인")
    print("=" * 70)
    print()
    
    # mypage 디렉토리의 모든 PHP 파일 찾기
    mypage_dir = "mypage"
    if not os.path.exists(mypage_dir):
        print(f"오류: {mypage_dir} 디렉토리를 찾을 수 없습니다.")
        return
    
    php_files = [f for f in os.listdir(mypage_dir) if f.endswith('.php')]
    php_files.sort()
    
    if not php_files:
        print(f"{mypage_dir} 디렉토리에 PHP 파일이 없습니다.")
        return
    
    print(f"총 {len(php_files)}개의 파일을 확인합니다.\n")
    
    # 각 파일의 커밋 이력 확인
    file_histories = []
    
    for filename in php_files:
        filepath = f"{mypage_dir}/{filename}"
        print(f"확인 중: {filepath}")
        
        # 해당 파일의 모든 커밋 이력 가져오기
        cmd = f'git log --all --pretty=format:"%H|%ai|%an|%s" --date=iso -- "{filepath}"'
        output, returncode = run_git_command(cmd)
        
        commits = []
        if output:
            for line in output.split('\n'):
                if '|' in line:
                    parts = line.split('|', 3)
                    if len(parts) >= 4:
                        commits.append({
                            'hash': parts[0],
                            'date': parts[1],
                            'author': parts[2],
                            'message': parts[3]
                        })
        
        if commits:
            file_histories.append({
                'file': filename,
                'path': filepath,
                'commits': commits,
                'latest': commits[0] if commits else None
            })
        else:
            file_histories.append({
                'file': filename,
                'path': filepath,
                'commits': [],
                'latest': None
            })
    
    print()
    print("=" * 70)
    print("요약: 각 파일의 최근 업데이트")
    print("=" * 70)
    print()
    
    # 최근 업데이트 순으로 정렬
    files_with_updates = [f for f in file_histories if f['latest']]
    files_with_updates.sort(key=lambda x: x['latest']['date'], reverse=True)
    
    for file_info in files_with_updates:
        latest = file_info['latest']
        print(f"📄 {file_info['file']}")
        print(f"   최근 업데이트: {latest['date']}")
        print(f"   작성자: {latest['author']}")
        print(f"   커밋 메시지: {latest['message']}")
        print(f"   커밋 해시: {latest['hash'][:8]}")
        print(f"   총 커밋 수: {len(file_info['commits'])}개")
        print()
    
    # 커밋이 없는 파일
    files_without_commits = [f for f in file_histories if not f['latest']]
    if files_without_commits:
        print("=" * 70)
        print("커밋 이력이 없는 파일 (아직 커밋되지 않음)")
        print("=" * 70)
        print()
        for file_info in files_without_commits:
            print(f"  - {file_info['file']}")
        print()
    
    # 전체 커밋 타임라인
    print("=" * 70)
    print("전체 업데이트 타임라인 (최신순)")
    print("=" * 70)
    print()
    
    all_commits = []
    for file_info in file_histories:
        for commit in file_info['commits']:
            commit['file'] = file_info['file']
            all_commits.append(commit)
    
    # 날짜순 정렬 (최신순)
    all_commits.sort(key=lambda x: x['date'], reverse=True)
    
    # 날짜별로 그룹화
    commits_by_date = defaultdict(list)
    for commit in all_commits:
        date_key = commit['date'][:10]  # YYYY-MM-DD
        commits_by_date[date_key].append(commit)
    
    # 날짜별로 출력
    for date in sorted(commits_by_date.keys(), reverse=True):
        commits = commits_by_date[date]
        print(f"\n📅 {date} ({len(commits)}개 업데이트)")
        print("-" * 70)
        for commit in commits:
            time = commit['date'][11:19]  # HH:MM:SS
            print(f"  {time} - {commit['file']}")
            print(f"    {commit['message']}")
            print(f"    작성자: {commit['author']} | 해시: {commit['hash'][:8]}")
            print()
    
    # 상세 이력 보기 옵션
    print("=" * 70)
    print("상세 이력 보기")
    print("=" * 70)
    print()
    
    for file_info in files_with_updates:
        if len(file_info['commits']) > 1:
            print(f"\n📄 {file_info['file']} - 전체 이력 ({len(file_info['commits'])}개 커밋)")
            print("-" * 70)
            for i, commit in enumerate(file_info['commits'][:10], 1):  # 최대 10개만
                print(f"  {i}. {commit['date']} - {commit['message']}")
                print(f"     작성자: {commit['author']} | 해시: {commit['hash'][:8]}")
            if len(file_info['commits']) > 10:
                print(f"  ... 외 {len(file_info['commits']) - 10}개 커밋 더 있음")
            print()

if __name__ == "__main__":
    main()





