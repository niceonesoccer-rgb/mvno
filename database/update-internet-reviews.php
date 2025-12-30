<?php
/**
 * 인터넷 리뷰 데이터베이스 업데이트 스크립트
 * 
 * 이 스크립트는 다음을 수행합니다:
 * 1. product_reviews 테이블의 product_type ENUM에 'internet' 추가
 * 2. 이미 작성된 인터넷 리뷰를 'approved' 상태로 업데이트
 * 
 * 실행 방법: 브라우저에서 http://localhost/MVNO/database/update-internet-reviews.php 접속
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>인터넷 리뷰 데이터베이스 업데이트</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid #6366f1;
        }
        .step-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .success {
            color: #10b981;
            font-weight: 600;
        }
        .error {
            color: #ef4444;
            font-weight: 600;
        }
        .info {
            color: #6366f1;
        }
        pre {
            background: #1f2937;
            color: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 13px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 600;
        }
        .button:hover {
            background: #4f46e5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>인터넷 리뷰 데이터베이스 업데이트</h1>
        
        <?php
        $pdo = getDBConnection();
        
        if (!$pdo) {
            echo '<div class="step"><div class="step-title error">❌ 데이터베이스 연결 실패</div>';
            echo '<p>데이터베이스에 연결할 수 없습니다. db-config.php 파일을 확인해주세요.</p></div>';
            exit;
        }
        
        echo '<div class="step"><div class="step-title info">✓ 데이터베이스 연결 성공</div></div>';
        
        $errors = [];
        $success = [];
        
        // 1. product_type ENUM에 'internet' 추가
        echo '<div class="step">';
        echo '<div class="step-title">1. product_type ENUM에 \'internet\' 추가</div>';
        
        try {
            $stmt = $pdo->prepare("
                ALTER TABLE `product_reviews` 
                MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입'
            ");
            $stmt->execute();
            $success[] = "product_type ENUM에 'internet' 추가 완료";
            echo '<p class="success">✓ 성공: product_type ENUM에 \'internet\' 추가되었습니다.</p>';
        } catch (PDOException $e) {
            // 이미 'internet'이 포함되어 있거나 다른 오류인 경우
            if (strpos($e->getMessage(), 'Duplicate') !== false || 
                strpos($e->getMessage(), 'already') !== false ||
                strpos($e->getMessage(), 'enum') !== false) {
                $success[] = "product_type ENUM에 이미 'internet'이 포함되어 있습니다.";
                echo '<p class="info">ℹ 이미 \'internet\' 타입이 포함되어 있습니다.</p>';
            } else {
                $errors[] = "ENUM 업데이트 실패: " . $e->getMessage();
                echo '<p class="error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        }
        echo '</div>';
        
        // 2. 현재 인터넷 리뷰 상태 확인
        echo '<div class="step">';
        echo '<div class="step-title">2. 현재 인터넷 리뷰 상태 확인</div>';
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
                FROM product_reviews 
                WHERE product_type = 'internet'
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($stats) {
                echo '<p>총 인터넷 리뷰: <strong>' . $stats['total'] . '개</strong></p>';
                echo '<p>- 승인 대기 (pending): <strong>' . ($stats['pending_count'] ?? 0) . '개</strong></p>';
                echo '<p>- 승인됨 (approved): <strong>' . ($stats['approved_count'] ?? 0) . '개</strong></p>';
                echo '<p>- 거부됨 (rejected): <strong>' . ($stats['rejected_count'] ?? 0) . '개</strong></p>';
            } else {
                echo '<p>인터넷 리뷰가 없습니다.</p>';
            }
        } catch (PDOException $e) {
            // product_type에 'internet'이 아직 없는 경우
            if (strpos($e->getMessage(), 'Unknown column') !== false || 
                strpos($e->getMessage(), 'product_type') !== false) {
                echo '<p class="error">❌ 오류: product_type에 \'internet\'이 아직 추가되지 않았습니다. 위의 ENUM 업데이트를 먼저 실행해주세요.</p>';
            } else {
                echo '<p class="error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        }
        echo '</div>';
        
        // 3. pending 상태의 인터넷 리뷰를 approved로 업데이트
        echo '<div class="step">';
        echo '<div class="step-title">3. pending 상태의 인터넷 리뷰를 approved로 업데이트</div>';
        
        try {
            $stmt = $pdo->prepare("
                UPDATE `product_reviews` 
                SET `status` = 'approved' 
                WHERE `product_type` = 'internet' 
                AND `status` = 'pending'
            ");
            $stmt->execute();
            $updatedCount = $stmt->rowCount();
            
            if ($updatedCount > 0) {
                $success[] = "{$updatedCount}개의 인터넷 리뷰가 승인 상태로 업데이트되었습니다.";
                echo '<p class="success">✓ 성공: <strong>' . $updatedCount . '개</strong>의 인터넷 리뷰가 승인 상태로 업데이트되었습니다.</p>';
            } else {
                echo '<p class="info">ℹ 업데이트할 pending 상태의 인터넷 리뷰가 없습니다.</p>';
            }
        } catch (PDOException $e) {
            $errors[] = "리뷰 업데이트 실패: " . $e->getMessage();
            echo '<p class="error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        echo '</div>';
        
        // 최종 결과
        echo '<div class="step">';
        echo '<div class="step-title">최종 결과</div>';
        
        if (empty($errors)) {
            echo '<p class="success">✓ 모든 작업이 성공적으로 완료되었습니다!</p>';
            echo '<ul>';
            foreach ($success as $msg) {
                echo '<li class="success">' . htmlspecialchars($msg) . '</li>';
            }
            echo '</ul>';
            echo '<p style="margin-top: 20px;"><strong>이제 인터넷 리뷰가 고객 페이지에 표시됩니다!</strong></p>';
        } else {
            echo '<p class="error">❌ 일부 작업에서 오류가 발생했습니다:</p>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li class="error">' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        
        // 최종 확인: 인터넷 리뷰 목록
        echo '<div class="step">';
        echo '<div class="step-title">4. 최종 확인: 승인된 인터넷 리뷰 목록</div>';
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    r.id,
                    r.product_id,
                    r.user_id,
                    r.rating,
                    LEFT(r.content, 50) as content_preview,
                    r.status,
                    r.created_at
                FROM product_reviews r
                WHERE r.product_type = 'internet'
                AND r.status = 'approved'
                ORDER BY r.created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($reviews) > 0) {
                echo '<p>승인된 인터넷 리뷰 목록 (최근 10개):</p>';
                echo '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
                echo '<thead><tr style="background: #f3f4f6;">';
                echo '<th style="padding: 8px; text-align: left; border: 1px solid #e5e7eb;">ID</th>';
                echo '<th style="padding: 8px; text-align: left; border: 1px solid #e5e7eb;">상품ID</th>';
                echo '<th style="padding: 8px; text-align: left; border: 1px solid #e5e7eb;">별점</th>';
                echo '<th style="padding: 8px; text-align: left; border: 1px solid #e5e7eb;">내용 미리보기</th>';
                echo '<th style="padding: 8px; text-align: left; border: 1px solid #e5e7eb;">작성일</th>';
                echo '</tr></thead><tbody>';
                
                foreach ($reviews as $review) {
                    echo '<tr>';
                    echo '<td style="padding: 8px; border: 1px solid #e5e7eb;">' . htmlspecialchars($review['id']) . '</td>';
                    echo '<td style="padding: 8px; border: 1px solid #e5e7eb;">' . htmlspecialchars($review['product_id']) . '</td>';
                    echo '<td style="padding: 8px; border: 1px solid #e5e7eb;">' . str_repeat('★', $review['rating']) . '</td>';
                    echo '<td style="padding: 8px; border: 1px solid #e5e7eb;">' . htmlspecialchars($review['content_preview']) . '...</td>';
                    echo '<td style="padding: 8px; border: 1px solid #e5e7eb;">' . htmlspecialchars($review['created_at']) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
            } else {
                echo '<p class="info">ℹ 승인된 인터넷 리뷰가 아직 없습니다. 리뷰를 작성하면 자동으로 승인되어 표시됩니다.</p>';
            }
        } catch (PDOException $e) {
            echo '<p class="error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        echo '</div>';
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <a href="/MVNO/internets/internet-detail.php?id=9" class="button">인터넷 상세 페이지로 이동</a>
            <a href="/MVNO/mypage/internet-order.php" class="button" style="margin-left: 10px;">인터넷 주문 페이지로 이동</a>
        </div>
    </div>
</body>
</html>











