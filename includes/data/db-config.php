<?php
/**
 * 데이터베이스 연결 설정
 */

// 데이터베이스 설정
define('DB_HOST', 'localhost');
define('DB_NAME', 'mvno_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * 데이터베이스 연결
 * @return PDO|null
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

/**
 * 데이터베이스 연결 테스트
 * @return bool
 */
function testDBConnection() {
    try {
        $pdo = getDBConnection();
        if ($pdo === null) {
            return false;
        }
        $pdo->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        error_log("Database connection test failed: " . $e->getMessage());
        return false;
    }
}

