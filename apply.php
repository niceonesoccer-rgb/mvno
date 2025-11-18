<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';

$planId = isset($_GET['plan_id']) ? (int)$_GET['plan_id'] : 0;

if ($planId < 1) {
    http_response_code(400);
    echo '<h1>요금제를 찾을 수 없습니다.</h1>';
    echo '<p>올바른 상품을 선택하고 다시 시도해주세요.</p>';
    exit;
}

try {
    $pdo = db_connect();
    $stmt = $pdo->prepare(
        'SELECT id, name, data_allowance, price, discount_price 
         FROM plans WHERE id = :id LIMIT 1'
    );
    $stmt->execute([':id' => $planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>상품 조회 중 오류가 발생했습니다.</h1>';
    echo '<p>' . h($e->getMessage()) . '</p>';
    exit;
}

if (!$plan) {
    http_response_code(404);
    echo '<h1>요금제를 찾을 수 없습니다.</h1>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?= h($plan['name']) ?> · 신청하기</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <style>
        body {
            background: #f3f4f6;
            padding: 2rem 1rem;
        }
        .card {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(15,23,42,.1);
        }
        .plan-summary {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            background: #f8fafc;
        }
        .plan-summary h1 {
            margin-bottom: .5rem;
        }
        .plan-summary .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4c48ff;
        }
        .plan-summary .original {
            text-decoration: line-through;
            color: #9ca3af;
            margin-right: .5rem;
            font-size: 1rem;
        }
        form label {
            margin-bottom: 1rem;
            display: block;
        }
        .actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
<section class="card">
    <div class="plan-summary">
        <h1><?= h($plan['name']) ?></h1>
        <p><?= h($plan['data_allowance'] ?? '데이터 정보 없음') ?></p>
        <p class="price">
            <?php if (!empty($plan['price'])): ?>
                <span class="original"><?= number_format((float)$plan['price']) ?>원</span>
            <?php endif; ?>
            <?php if (!empty($plan['discount_price'])): ?>
                월 <?= number_format((float)$plan['discount_price']) ?>원
            <?php endif; ?>
        </p>
    </div>

    <form method="post" action="/apply_submit.php">
        <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">

        <label>
            이름
            <input type="text" name="customer_name" required>
        </label>

        <label>
            연락처
            <input type="tel" name="customer_phone" placeholder="010-0000-0000" required>
        </label>

        <label>
            이메일
            <input type="email" name="customer_email" placeholder="example@domain.com" required>
        </label>

        <label>
            대리점 (선택)
            <input type="text" name="agency_name" placeholder="선택 입력">
        </label>

        <label>
            <input type="checkbox" name="agree_notifications" value="1">
            알림 수신에 동의합니다.
        </label>

        <div class="actions">
            <button type="submit">신청 제출</button>
            <a href="/" class="secondary" role="button">목록으로</a>
        </div>
    </form>
</section>
</body>
</html>

