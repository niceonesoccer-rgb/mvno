#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
mypage 디렉토리 파일들의 업데이트 이력을 HTML로 생성하는 스크립트
"""

import subprocess
import os
from collections import defaultdict
from datetime import datetime

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
        return "", 1

def main():
    import sys
    # 출력을 파일로도 저장
    log_file = open('mypage_history_generation.log', 'w', encoding='utf-8')
    
    def log_print(msg):
        print(msg)
        log_file.write(msg + '\n')
        log_file.flush()
    
    try:
        log_print("mypage 업데이트 이력 HTML 생성 중...")
        log_print("")
        
        # mypage 디렉토리의 모든 PHP 파일 찾기
        mypage_dir = "mypage"
        if not os.path.exists(mypage_dir):
            log_print(f"오류: {mypage_dir} 디렉토리를 찾을 수 없습니다.")
            log_file.close()
            return
        
        php_files = [f for f in os.listdir(mypage_dir) if f.endswith('.php')]
        php_files.sort()
        
        if not php_files:
            log_print(f"{mypage_dir} 디렉토리에 PHP 파일이 없습니다.")
            log_file.close()
            return
        
        log_print(f"총 {len(php_files)}개의 파일을 확인합니다.")
        log_print("")
        
        # 각 파일의 커밋 이력 확인
        file_histories = []
        all_commits = []
        
        for filename in php_files:
            filepath = f"{mypage_dir}/{filename}"
            log_print(f"확인 중: {filename}...", end=" ")
        
            # 해당 파일의 모든 커밋 이력 가져오기
            cmd = f'git log --all --pretty=format:"%H|%ai|%an|%s" --date=iso -- "{filepath}"'
            output, returncode = run_git_command(cmd)
            
            commits = []
            if output:
                for line in output.split('\n'):
                    if '|' in line:
                        parts = line.split('|', 3)
                        if len(parts) >= 4:
                            commit = {
                                'hash': parts[0],
                                'date': parts[1],
                                'author': parts[2],
                                'message': parts[3],
                                'file': filename
                            }
                            commits.append(commit)
                            all_commits.append(commit)
            
            if commits:
                log_print(f"✓ {len(commits)}개 커밋 발견")
            else:
                log_print("커밋 이력 없음")
            
            file_histories.append({
                'file': filename,
                'path': filepath,
                'commits': commits,
                'latest': commits[0] if commits else None
            })
        
        log_print("")
        
        # 날짜별로 그룹화
        commits_by_date = defaultdict(list)
        for commit in all_commits:
            date_key = commit['date'][:10]  # YYYY-MM-DD
            commits_by_date[date_key].append(commit)
        
        # HTML 생성
        html_content = f"""<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mypage 업데이트 이력</title>
    <style>
        * {{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }}
        
        body {{
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }}
        
        .container {{
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }}
        
        .header {{
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }}
        
        .header h1 {{
            font-size: 2.5em;
            margin-bottom: 10px;
        }}
        
        .header p {{
            font-size: 1.1em;
            opacity: 0.9;
        }}
        
        .content {{
            padding: 30px;
        }}
        
        .section {{
            margin-bottom: 40px;
        }}
        
        .section-title {{
            font-size: 1.8em;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }}
        
        .file-grid {{
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }}
        
        .file-card {{
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }}
        
        .file-card:hover {{
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }}
        
        .file-card h3 {{
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.2em;
        }}
        
        .file-card .info {{
            color: #666;
            font-size: 0.9em;
            line-height: 1.6;
        }}
        
        .file-card .info strong {{
            color: #333;
        }}
        
        .file-card.no-commits {{
            background: #fff3cd;
            border-color: #ffc107;
        }}
        
        .timeline {{
            position: relative;
            padding-left: 30px;
        }}
        
        .timeline::before {{
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #667eea;
        }}
        
        .timeline-item {{
            position: relative;
            margin-bottom: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-left: 20px;
        }}
        
        .timeline-item::before {{
            content: '';
            position: absolute;
            left: -30px;
            top: 25px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #667eea;
        }}
        
        .timeline-date {{
            font-size: 1.3em;
            color: #667eea;
            font-weight: bold;
            margin-bottom: 15px;
        }}
        
        .commit-item {{
            background: white;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }}
        
        .commit-item:last-child {{
            margin-bottom: 0;
        }}
        
        .commit-time {{
            color: #999;
            font-size: 0.9em;
            margin-bottom: 5px;
        }}
        
        .commit-file {{
            color: #667eea;
            font-weight: bold;
            margin-bottom: 5px;
        }}
        
        .commit-message {{
            color: #333;
            margin-bottom: 5px;
        }}
        
        .commit-meta {{
            color: #999;
            font-size: 0.85em;
        }}
        
        .stats {{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }}
        
        .stat-card {{
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }}
        
        .stat-card .number {{
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }}
        
        .stat-card .label {{
            font-size: 0.9em;
            opacity: 0.9;
        }}
        
        .empty-state {{
            text-align: center;
            padding: 40px;
            color: #999;
        }}
        
        .empty-state::before {{
            content: '📝';
            font-size: 3em;
            display: block;
            margin-bottom: 10px;
        }}
        
        @media (max-width: 768px) {{
            .file-grid {{
                grid-template-columns: 1fr;
            }}
            
            .header h1 {{
                font-size: 1.8em;
            }}
        }}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 mypage 디렉토리 업데이트 이력</h1>
            <p>생성 시간: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}</p>
        </div>
        
        <div class="content">
            <!-- 통계 -->
            <div class="section">
                <div class="stats">
                    <div class="stat-card">
                        <div class="number">{len(php_files)}</div>
                        <div class="label">총 파일 수</div>
                    </div>
                    <div class="stat-card">
                        <div class="number">{len(all_commits)}</div>
                        <div class="label">총 커밋 수</div>
                    </div>
                    <div class="stat-card">
                        <div class="number">{len(commits_by_date)}</div>
                        <div class="label">업데이트된 날짜</div>
                    </div>
                    <div class="stat-card">
                        <div class="number">{len([f for f in file_histories if f['latest']])}</div>
                        <div class="label">커밋 이력이 있는 파일</div>
                    </div>
                </div>
            </div>
            
            <!-- 파일별 요약 -->
            <div class="section">
                <h2 class="section-title">📋 파일별 최근 업데이트</h2>
                <div class="file-grid">
"""
        
        # 파일별 카드 추가
        files_with_updates = [f for f in file_histories if f['latest']]
        files_with_updates.sort(key=lambda x: x['latest']['date'], reverse=True)
        
        for file_info in files_with_updates:
            latest = file_info['latest']
            html_content += f"""
                    <div class="file-card">
                        <h3>{file_info['file']}</h3>
                        <div class="info">
                            <div><strong>최근 업데이트:</strong> {latest['date']}</div>
                            <div><strong>작성자:</strong> {latest['author']}</div>
                            <div><strong>커밋 메시지:</strong> {latest['message']}</div>
                            <div><strong>커밋 해시:</strong> <code>{latest['hash'][:8]}</code></div>
                            <div><strong>총 커밋 수:</strong> {len(file_info['commits'])}개</div>
                        </div>
                    </div>
"""
        
        # 커밋 이력이 없는 파일
        files_without_commits = [f for f in file_histories if not f['latest']]
        if files_without_commits:
            html_content += """
                </div>
                <h3 style="margin-top: 30px; color: #999;">커밋 이력이 없는 파일</h3>
                <div class="file-grid">
"""
            for file_info in files_without_commits:
                html_content += f"""
                    <div class="file-card no-commits">
                        <h3>{file_info['file']}</h3>
                        <div class="info">
                            <div>아직 커밋되지 않은 파일입니다.</div>
                        </div>
                    </div>
"""
        
        html_content += """
                </div>
            </div>
            
            <!-- 타임라인 -->
            <div class="section">
                <h2 class="section-title">📅 업데이트 타임라인</h2>
"""
        
        if commits_by_date:
            html_content += '<div class="timeline">'
            for date in sorted(commits_by_date.keys(), reverse=True):
                commits = commits_by_date[date]
                html_content += f"""
                    <div class="timeline-item">
                        <div class="timeline-date">📅 {date} ({len(commits)}개 업데이트)</div>
"""
                for commit in commits:
                    time = commit['date'][11:19]
                    html_content += f"""
                        <div class="commit-item">
                            <div class="commit-time">🕐 {time}</div>
                            <div class="commit-file">📄 {commit['file']}</div>
                            <div class="commit-message">{commit['message']}</div>
                            <div class="commit-meta">
                                작성자: {commit['author']} | 해시: <code>{commit['hash'][:8]}</code>
                            </div>
                        </div>
"""
                html_content += """
                    </div>
"""
            html_content += '</div>'
        else:
            html_content += '<div class="empty-state">커밋 이력을 찾을 수 없습니다.</div>'
        
        html_content += """
            </div>
        </div>
    </div>
</body>
</html>
"""
        
        # HTML 파일 저장
        output_file = 'mypage_history.html'
        with open(output_file, 'w', encoding='utf-8') as f:
            f.write(html_content)
        
        abs_path = os.path.abspath(output_file)
        log_print("")
        log_print("=" * 70)
        log_print("✓ HTML 파일이 성공적으로 생성되었습니다!")
        log_print("=" * 70)
        log_print(f"파일 위치: {abs_path}")
        log_print(f"브라우저에서 열기: file:///{abs_path.replace(chr(92), '/')}")
        log_print(f"웹 서버에서: http://localhost/mvno/{output_file}")
        log_print("")
        log_print(f"통계:")
        log_print(f"  - 총 파일 수: {len(php_files)}")
        log_print(f"  - 총 커밋 수: {len(all_commits)}")
        log_print(f"  - 업데이트된 날짜: {len(commits_by_date)}")
        log_print(f"  - 커밋 이력이 있는 파일: {len([f for f in file_histories if f['latest']])}")
        log_print("")
    except Exception as e:
        log_print(f"오류 발생: {e}")
        import traceback
        log_print(traceback.format_exc())
    finally:
        log_file.close()

if __name__ == "__main__":
    main()



