<?php
/**
 * 관리자 대시보드
 */

require_once __DIR__ . '/includes/admin-header.php';
?>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        margin-bottom: 24px;
    }
    
    .dashboard-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border: 1px solid #e2e8f0;
        transition: all 0.3s;
    }
    
    .dashboard-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    }
    
    .dashboard-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }
    
    .dashboard-card-title {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .dashboard-card-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    
    .dashboard-card-value {
        font-size: 32px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }
    
    .dashboard-card-description {
        font-size: 13px;
        color: #94a3b8;
    }
    
    .dashboard-card-link {
        display: inline-flex;
        align-items: center;
        margin-top: 12px;
        font-size: 13px;
        color: #3b82f6;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }
    
    .dashboard-card-link:hover {
        color: #2563eb;
    }
    
    .dashboard-section {
        margin-bottom: 32px;
    }
    
    .dashboard-section-title {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }
    
    .quick-action-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }
    
    .quick-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .quick-action-btn svg {
        width: 20px;
        height: 20px;
        stroke: currentColor;
        fill: none;
        stroke-width: 2.5;
    }
</style>

<div class="dashboard-section">
    <h1 class="dashboard-section-title">대시보드</h1>
    
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <span class="dashboard-card-title">판매자 승인 대기</span>
                <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
            </div>
            <div class="dashboard-card-value">0</div>
            <div class="dashboard-card-description">승인 대기 중인 판매자 수</div>
        </div>
        
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <span class="dashboard-card-title">이벤트</span>
                <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                </div>
            </div>
            <div class="dashboard-card-value">0</div>
            <div class="dashboard-card-description">진행 중인 이벤트 수</div>
        </div>
        
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <span class="dashboard-card-title">공지사항</span>
                <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #f472b6 0%, #ec4899 100%);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </div>
            </div>
            <div class="dashboard-card-value">0</div>
            <div class="dashboard-card-description">등록된 공지사항 수</div>
        </div>
        
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <span class="dashboard-card-title">Q&A</span>
                <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #34d399 0%, #10b981 100%);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
            </div>
            <div class="dashboard-card-value">0</div>
            <div class="dashboard-card-description">답변 대기 중인 질문 수</div>
        </div>
        
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <span class="dashboard-card-title">회원 정보</span>
                <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
            </div>
            <div class="dashboard-card-value"><?php 
                require_once __DIR__ . '/../includes/data/auth-functions.php';
                $usersData = getUsersData();
                $totalUsers = count($usersData['users'] ?? []);
                echo number_format($totalUsers);
            ?></div>
            <div class="dashboard-card-description">전체 회원 수</div>
        </div>
    </div>
</div>

<div class="dashboard-section">
    <h2 class="dashboard-section-title">빠른 작업</h2>
    <div class="quick-actions">
        <a href="/MVNO/admin/seller-approval.php" class="quick-action-btn">
            <svg viewBox="0 0 24 24">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            판매자 승인
        </a>
        <a href="/MVNO/admin/event-manage.php" class="quick-action-btn">
            <svg viewBox="0 0 24 24">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
            이벤트 관리
        </a>
        <a href="/MVNO/admin/notice-manage.php" class="quick-action-btn">
            <svg viewBox="0 0 24 24">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            공지사항 관리
        </a>
        <a href="/MVNO/admin/qna-manage.php" class="quick-action-btn">
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            Q&A 관리
        </a>
        <a href="/MVNO/admin/monitor.php" class="quick-action-btn">
            <svg viewBox="0 0 24 24">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                <line x1="8" y1="21" x2="16" y2="21"/>
                <line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
            모니터링
        </a>
        <a href="/MVNO/admin/analytics/dashboard.php" class="quick-action-btn">
            <svg viewBox="0 0 24 24">
                <line x1="18" y1="20" x2="18" y2="10"/>
                <line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            통계 대시보드
        </a>
        <a href="/MVNO/admin/users/member-list.php" class="quick-action-btn">
            <svg viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            회원 관리
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
