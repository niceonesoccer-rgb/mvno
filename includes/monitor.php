<?php
/**
 * 간단한 접속 모니터링 시스템
 * 동시 접속 수 추적 및 로깅
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

class ConnectionMonitor {
    private $logDir = 'logs';
    private $logFile;
    private $sessionFile;
    
    public function __construct() {
        $this->logDir = __DIR__ . '/../logs';
        $this->logFile = $this->logDir . '/connections.log';
        $this->sessionFile = $this->logDir . '/sessions.log';
        
        // 로그 디렉토리 생성
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    /**
     * 현재 접속 기록
     */
    public function logConnection() {
        $sessionId = session_id();
        if (empty($sessionId)) {
            session_start();
            $sessionId = session_id();
        }
        
        $data = [
            'time' => time(),
            'datetime' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'page' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'session_id' => $sessionId
        ];
        
        // 세션별 마지막 접속 시간 기록
        $this->updateSession($sessionId, $data);
        
        // 전체 접속 로그 기록
        file_put_contents(
            $this->logFile, 
            json_encode($data) . "\n", 
            FILE_APPEND | LOCK_EX
        );
    }
    
    /**
     * 세션별 마지막 접속 시간 업데이트
     */
    private function updateSession($sessionId, $data) {
        $sessions = $this->getSessions();
        $sessions[$sessionId] = $data;
        
        // 5분 이상 비활성 세션 제거
        $now = time();
        foreach ($sessions as $sid => $session) {
            if ($now - $session['time'] > 300) {
                unset($sessions[$sid]);
            }
        }
        
        file_put_contents(
            $this->sessionFile,
            json_encode($sessions),
            LOCK_EX
        );
    }
    
    /**
     * 현재 활성 세션 가져오기
     */
    private function getSessions() {
        if (!file_exists($this->sessionFile)) {
            return [];
        }
        
        $content = file_get_contents($this->sessionFile);
        $sessions = json_decode($content, true);
        
        return $sessions ?: [];
    }
    
    /**
     * 현재 동시 접속 수 가져오기
     */
    public function getCurrentConnections() {
        $sessions = $this->getSessions();
        $now = time();
        $active = 0;
        
        foreach ($sessions as $session) {
            // 최근 5분 내 접속만 활성으로 간주
            if ($now - $session['time'] < 300) {
                $active++;
            }
        }
        
        return $active;
    }
    
    /**
     * 최근 접속 통계
     */
    public function getRecentStats($minutes = 5) {
        if (!file_exists($this->logFile)) {
            return [
                'total' => 0,
                'unique_ips' => 0,
                'pages' => []
            ];
        }
        
        $lines = file($this->logFile);
        $cutoff = time() - ($minutes * 60);
        $stats = [
            'total' => 0,
            'unique_ips' => [],
            'pages' => []
        ];
        
        foreach (array_reverse($lines) as $line) {
            $data = json_decode(trim($line), true);
            if (!$data) continue;
            
            if ($data['time'] < $cutoff) {
                break;
            }
            
            $stats['total']++;
            $stats['unique_ips'][$data['ip']] = true;
            $stats['pages'][$data['page']] = ($stats['pages'][$data['page']] ?? 0) + 1;
        }
        
        $stats['unique_ips'] = count($stats['unique_ips']);
        arsort($stats['pages']);
        
        return $stats;
    }
    
    /**
     * 로그 정리 (오래된 로그 삭제)
     */
    public function cleanup($days = 7) {
        if (!file_exists($this->logFile)) {
            return 0;
        }
        
        $cutoff = time() - ($days * 24 * 60 * 60);
        $lines = file($this->logFile);
        $kept = [];
        $deleted = 0;
        
        foreach ($lines as $line) {
            $data = json_decode(trim($line), true);
            if ($data && $data['time'] >= $cutoff) {
                $kept[] = $line;
            } else {
                $deleted++;
            }
        }
        
        file_put_contents($this->logFile, implode('', $kept), LOCK_EX);
        
        return $deleted;
    }
}






















