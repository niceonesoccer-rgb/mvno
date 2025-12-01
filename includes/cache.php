<?php
/**
 * 파일 기반 캐싱 시스템
 * Redis가 없어도 작동하는 간단한 캐시
 */

class SimpleCache {
    private $cacheDir;
    private $defaultTTL = 300; // 5분 기본 캐시 시간
    
    public function __construct($cacheDir = 'cache') {
        $this->cacheDir = $cacheDir;
        
        // 캐시 디렉토리 생성
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * 캐시에서 데이터 가져오기
     */
    public function get($key) {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return false;
        }
        
        $data = file_get_contents($file);
        $cache = unserialize($data);
        
        // 만료 시간 확인
        if (time() > $cache['expires']) {
            $this->delete($key);
            return false;
        }
        
        return $cache['data'];
    }
    
    /**
     * 캐시에 데이터 저장
     */
    public function set($key, $data, $ttl = null) {
        if ($ttl === null) {
            $ttl = $this->defaultTTL;
        }
        
        $file = $this->getCacheFile($key);
        $cache = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        file_put_contents($file, serialize($cache), LOCK_EX);
    }
    
    /**
     * 캐시 삭제
     */
    public function delete($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    /**
     * 캐시 키로 파일 경로 생성
     */
    private function getCacheFile($key) {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
    
    /**
     * 모든 캐시 삭제
     */
    public function clear() {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    /**
     * 만료된 캐시 정리
     */
    public function cleanup() {
        $files = glob($this->cacheDir . '/*.cache');
        $now = time();
        $deleted = 0;
        
        foreach ($files as $file) {
            $data = file_get_contents($file);
            $cache = unserialize($data);
            
            if ($now > $cache['expires']) {
                unlink($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }
}







