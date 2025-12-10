<?php
/**
 * MNO 상품 테이블 생성 스크립트
 * 
 * 사용법: 브라우저에서 http://localhost/MVNO/database/install_mno_tables.php 접속
 */

require_once __DIR__ . '/../includes/data/db-config.php';

// 데이터베이스 연결
$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MNO 상품 테이블 생성</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1e293b;
            margin-bottom: 20px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px 16px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #10b981;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #ef4444;
        }
        .info {
            background: #dbeafe;
            color: #1e40af;
            padding: 12px 16px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #3b82f6;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .btn:hover {
            background: #2563eb;
        }
        pre {
            background: #f3f4f6;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
        }
        .step {
            margin: 20px 0;
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 MNO 상품 테이블 생성</h1>
        
        <?php
        $action = $_GET['action'] ?? 'check';
        
        if ($action === 'install') {
            try {
                // 1. products 테이블 확인 및 생성
                $checkProducts = $pdo->query("SHOW TABLES LIKE 'products'");
                if (!$checkProducts->fetch()) {
                    $createProductsSQL = "
                    CREATE TABLE IF NOT EXISTS `products` (
                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `seller_id` INT(11) UNSIGNED NOT NULL COMMENT '판매자 ID',
                        `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입',
                        `status` ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active' COMMENT '상품 상태',
                        `view_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '조회수',
                        `favorite_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '찜 수',
                        `review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '리뷰 수',
                        `share_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '공유 수',
                        `application_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '신청 수',
                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
                        PRIMARY KEY (`id`),
                        KEY `idx_seller_id` (`seller_id`),
                        KEY `idx_product_type` (`product_type`),
                        KEY `idx_status` (`status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 기본 정보';
                    ";
                    $pdo->exec($createProductsSQL);
                    echo '<div class="success">✅ products 테이블이 생성되었습니다.</div>';
                } else {
                    echo '<div class="info">ℹ️ products 테이블이 이미 존재합니다.</div>';
                }
                
                // 2. product_mno_details 테이블 생성
                $checkMno = $pdo->query("SHOW TABLES LIKE 'product_mno_details'");
                if (!$checkMno->fetch()) {
                    $createMnoSQL = "
                    CREATE TABLE IF NOT EXISTS `product_mno_details` (
                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
                        `device_name` VARCHAR(100) NOT NULL COMMENT '단말기명',
                        `device_price` DECIMAL(12,2) DEFAULT NULL COMMENT '단말기 출고가',
                        `device_capacity` VARCHAR(20) DEFAULT NULL COMMENT '용량',
                        `device_colors` TEXT DEFAULT NULL COMMENT '단말기 색상 목록 (JSON)',
                        `common_provider` TEXT DEFAULT NULL COMMENT '공통지원할인 통신사 (JSON)',
                        `common_discount_new` TEXT DEFAULT NULL COMMENT '공통지원할인 신규가입 (JSON)',
                        `common_discount_port` TEXT DEFAULT NULL COMMENT '공통지원할인 번호이동 (JSON)',
                        `common_discount_change` TEXT DEFAULT NULL COMMENT '공통지원할인 기기변경 (JSON)',
                        `contract_provider` TEXT DEFAULT NULL COMMENT '선택약정할인 통신사 (JSON)',
                        `contract_discount_new` TEXT DEFAULT NULL COMMENT '선택약정할인 신규가입 (JSON)',
                        `contract_discount_port` TEXT DEFAULT NULL COMMENT '선택약정할인 번호이동 (JSON)',
                        `contract_discount_change` TEXT DEFAULT NULL COMMENT '선택약정할인 기기변경 (JSON)',
                        `service_type` VARCHAR(50) DEFAULT NULL COMMENT '서비스 타입',
                        `contract_period` VARCHAR(50) DEFAULT NULL COMMENT '약정기간',
                        `contract_period_value` VARCHAR(20) DEFAULT NULL COMMENT '약정기간 값',
                        `price_main` DECIMAL(10,2) DEFAULT NULL COMMENT '기본 요금',
                        `data_amount` VARCHAR(50) DEFAULT NULL COMMENT '데이터량',
                        `data_amount_value` VARCHAR(20) DEFAULT NULL COMMENT '데이터량 값',
                        `data_unit` VARCHAR(10) DEFAULT NULL COMMENT '데이터 단위',
                        `data_exhausted` VARCHAR(50) DEFAULT NULL COMMENT '데이터 소진 시',
                        `data_exhausted_value` VARCHAR(50) DEFAULT NULL COMMENT '데이터 소진 시 값',
                        `call_type` VARCHAR(50) DEFAULT NULL COMMENT '통화 타입',
                        `call_amount` VARCHAR(20) DEFAULT NULL COMMENT '통화량',
                        `additional_call_type` VARCHAR(50) DEFAULT NULL COMMENT '추가 통화 타입',
                        `additional_call` VARCHAR(20) DEFAULT NULL COMMENT '추가 통화량',
                        `sms_type` VARCHAR(50) DEFAULT NULL COMMENT 'SMS 타입',
                        `sms_amount` VARCHAR(20) DEFAULT NULL COMMENT 'SMS량',
                        `mobile_hotspot` VARCHAR(50) DEFAULT NULL COMMENT '모바일 핫스팟',
                        `mobile_hotspot_value` VARCHAR(20) DEFAULT NULL COMMENT '모바일 핫스팟 값',
                        `regular_sim_available` VARCHAR(10) DEFAULT NULL COMMENT '일반 SIM 가능 여부',
                        `regular_sim_price` VARCHAR(20) DEFAULT NULL COMMENT '일반 SIM 가격',
                        `nfc_sim_available` VARCHAR(10) DEFAULT NULL COMMENT 'NFC SIM 가능 여부',
                        `nfc_sim_price` VARCHAR(20) DEFAULT NULL COMMENT 'NFC SIM 가격',
                        `esim_available` VARCHAR(10) DEFAULT NULL COMMENT 'eSIM 가능 여부',
                        `esim_price` VARCHAR(20) DEFAULT NULL COMMENT 'eSIM 가격',
                        `over_data_price` VARCHAR(20) DEFAULT NULL COMMENT '데이터 초과 시 가격',
                        `over_voice_price` VARCHAR(20) DEFAULT NULL COMMENT '음성 초과 시 가격',
                        `over_video_price` VARCHAR(20) DEFAULT NULL COMMENT '영상통화 초과 시 가격',
                        `over_sms_price` VARCHAR(20) DEFAULT NULL COMMENT 'SMS 초과 시 가격',
                        `over_lms_price` VARCHAR(20) DEFAULT NULL COMMENT 'LMS 초과 시 가격',
                        `over_mms_price` VARCHAR(20) DEFAULT NULL COMMENT 'MMS 초과 시 가격',
                        `promotion_title` VARCHAR(200) DEFAULT NULL COMMENT '프로모션 제목',
                        `promotions` TEXT DEFAULT NULL COMMENT '프로모션 목록 (JSON)',
                        `benefits` TEXT DEFAULT NULL COMMENT '혜택 목록 (JSON)',
                        `delivery_method` VARCHAR(20) DEFAULT 'delivery' COMMENT '배송 방법',
                        `visit_region` VARCHAR(50) DEFAULT NULL COMMENT '방문 지역',
                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `uk_product_id` (`product_id`),
                        KEY `idx_device_name` (`device_name`),
                        CONSTRAINT `fk_mno_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MNO 상품 상세 정보';
                    ";
                    $pdo->exec($createMnoSQL);
                    echo '<div class="success">✅ product_mno_details 테이블이 생성되었습니다!</div>';
                } else {
                    echo '<div class="info">ℹ️ product_mno_details 테이블이 이미 존재합니다.</div>';
                }
                
                echo '<div class="success">✅ 모든 테이블이 준비되었습니다!</div>';
                echo '<div class="info">💡 이제 <a href="/MVNO/seller/products/mno.php" style="color: #1e40af; font-weight: 600;">통신사폰 등록 페이지</a>에서 상품을 등록할 수 있습니다.</div>';
                
            } catch (PDOException $e) {
                echo '<div class="error">❌ 오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            }
        } else {
            // 테이블 존재 여부 확인
            try {
                $productsExists = false;
                $mnoExists = false;
                
                $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
                $productsExists = $stmt->fetch() !== false;
                
                $stmt = $pdo->query("SHOW TABLES LIKE 'product_mno_details'");
                $mnoExists = $stmt->fetch() !== false;
                
                if ($productsExists && $mnoExists) {
                    echo '<div class="success">✅ 모든 테이블이 이미 존재합니다.</div>';
                    echo '<a href="/MVNO/seller/products/mno.php" class="btn">통신사폰 등록 페이지로 이동</a>';
                } else {
                    echo '<div class="info">📋 다음 테이블이 필요합니다:</div>';
                    echo '<div class="step">';
                    echo '<strong>필요한 테이블:</strong><br>';
                    echo ($productsExists ? '✅' : '❌') . ' products 테이블<br>';
                    echo ($mnoExists ? '✅' : '❌') . ' product_mno_details 테이블';
                    echo '</div>';
                    echo '<div class="info">아래 버튼을 클릭하여 테이블을 생성하세요.</div>';
                    echo '<a href="?action=install" class="btn">테이블 생성하기</a>';
                }
            } catch (PDOException $e) {
                echo '<div class="error">❌ 오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '<div class="info">테이블을 생성하려면 아래 버튼을 클릭하세요.</div>';
                echo '<a href="?action=install" class="btn">테이블 생성하기</a>';
            }
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <h3 style="font-size: 16px; color: #374151; margin-bottom: 12px;">생성될 테이블:</h3>
            <ul style="font-size: 14px; color: #6b7280; line-height: 1.8;">
                <li><strong>products</strong> - 상품 기본 정보</li>
                <li><strong>product_mno_details</strong> - 통신사폰 상품 상세 정보</li>
            </ul>
        </div>
    </div>
</body>
</html>

