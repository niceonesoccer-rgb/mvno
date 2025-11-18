<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';

$plans = [];
$dbError = null;

try {
    $pdo = db_connect();
    $sql = <<<SQL
        SELECT
            id,
            carrier,
            data_type,
            name,
            data_allowance,
            voice_allowance,
            video_allowance,
            sms_allowance,
            price,
            discount_price,
            min_period_days,
            benefit1,
            benefit2,
            benefit3,
            benefit4,
            benefit5,
            description_title1,
            description_body1,
            description_title2,
            description_body2
        FROM plans
        WHERE is_active = 1
        ORDER BY display_order ASC, id DESC
    SQL;
    $stmt = $pdo->query($sql);
    if ($stmt !== false) {
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MVNO 요금제 목록</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <style>
        :root {
            --card-border: #dcdcdc;
            --accent: #4c48ff;
            --pill-bg: #f3f4f6;
            --pill-color: #333;
            --muted: #6b7280;
        }
        body {
            background: #f9fafb;
            padding: 2rem 1rem;
            font-family: 'SUIT', 'Pretendard', 'Noto Sans KR', sans-serif;
        }
        .plans-wrap {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .plan-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 32px;
            border: 1px solid var(--card-border);
            box-shadow: 0 10px 40px rgba(15, 23, 42, 0.08);
            position: relative;
        }
        .pill-row, .meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }
        .pill {
            border: 1px solid #d1d5db;
            border-radius: 999px;
            padding: .35rem .85rem;
            font-size: .85rem;
            background: var(--pill-bg);
            color: var(--pill-color);
        }
        .plan-main {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: center;
            margin: 1rem 0;
        }
        .plan-main h2 {
            margin: 0;
            font-size: 1.6rem;
        }
        .plan-main .sub {
            color: var(--muted);
            margin-bottom: 0;
        }
        .price-box {
            text-align: right;
        }
        .price-box .original {
            text-decoration: line-through;
            color: var(--muted);
            font-size: .9rem;
        }
        .price-box .discount {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--accent);
        }
        .price-box .period {
            font-size: .9rem;
            color: var(--muted);
        }
        .accordion {
            margin-top: 1.25rem;
            border-radius: 18px;
            border: 1px solid #d1d5db;
            background: #f8fafc;
            overflow: hidden;
        }
        .accordion button {
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            padding: 1rem 1.25rem;
            font-weight: 600;
            cursor: pointer;
        }
        .accordion button:focus {
            outline: none;
        }
        .accordion-content {
            display: none;
            padding: 0 1.25rem 1.25rem;
        }
        .accordion-content.active {
            display: block;
        }
        .accordion-content h4 {
            margin-bottom: .35rem;
        }
        .cta {
            display: flex;
            justify-content: flex-end;
            margin-top: 1rem;
        }
        .cta a {
            border-radius: 999px;
            padding: .75rem 1.75rem;
            background: var(--accent);
            color: #fff;
            font-weight: 600;
            text-decoration: none;
        }
        @media (max-width: 768px) {
            .plan-main {
                grid-template-columns: 1fr;
                text-align: left;
            }
            .price-box {
                text-align: left;
            }
        }
    </style>
</head>
<body>
<main class="plans-wrap">
    <header>
        <h1>판매중인 알뜰폰 요금제</h1>
        <p class="pill sub">혜택부터 요금까지 한눈에 비교하고 바로 신청하세요.</p>
    </header>

    <?php if ($dbError !== null): ?>
        <article class="plan-card">
            <strong>데이터베이스 연결에 실패했습니다.</strong>
            <p><?= h($dbError) ?></p>
            <p><small>`includes/db.php` 설정을 확인하세요.</small></p>
        </article>
    <?php elseif (empty($plans)): ?>
        <article class="plan-card">
            <p>현재 판매중인 요금제가 없습니다. 관리자 화면에서 상품을 등록해 주세요.</p>
        </article>
    <?php else: ?>
        <?php foreach ($plans as $plan): ?>
            <?php
            $benefits = array_filter([
                $plan['benefit1'] ?? null,
                $plan['benefit2'] ?? null,
                $plan['benefit3'] ?? null,
                $plan['benefit4'] ?? null,
                $plan['benefit5'] ?? null,
            ]);

            $metaPills = array_filter([
                $plan['carrier'] ?? null,
                $plan['data_type'] ?? null,
                !empty($plan['voice_allowance']) ? '통화 ' . $plan['voice_allowance'] : null,
                !empty($plan['sms_allowance']) ? '문자 ' . $plan['sms_allowance'] : null,
                !empty($plan['video_allowance']) ? '영상/부가 ' . $plan['video_allowance'] : null,
            ]);

            $accordionId = 'desc-' . (int)$plan['id'];
            ?>
            <section class="plan-card">
                <?php if (!empty($benefits)): ?>
                    <div class="pill-row">
                        <?php foreach ($benefits as $benefit): ?>
                            <span class="pill"><?= h($benefit) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="plan-main">
                    <div>
                        <h2><?= h($plan['name']) ?></h2>
                        <p class="sub"><?= h($plan['data_allowance'] ?? '') ?></p>
                    </div>
                    <div class="price-box">
                        <?php if (!empty($plan['price'])): ?>
                            <div class="original"><?= number_format((float)$plan['price']) ?>원</div>
                        <?php endif; ?>
                        <?php if (!empty($plan['discount_price'])): ?>
                            <div class="discount">월 <?= number_format((float)$plan['discount_price']) ?>원</div>
                        <?php endif; ?>
                        <?php if (!empty($plan['min_period_days'])): ?>
                            <div class="period"><?= (int)$plan['min_period_days'] ?>일 유지 시 혜택 적용</div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($metaPills)): ?>
                    <div class="meta-row">
                        <?php foreach ($metaPills as $pill): ?>
                            <span class="pill"><?= h($pill) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($plan['description_title1']) || !empty($plan['description_title2'])): ?>
                    <div class="accordion">
                        <button type="button" data-target="#<?= h($accordionId) ?>">혜택 자세히 보기</button>
                        <div id="<?= h($accordionId) ?>" class="accordion-content">
                            <?php if (!empty($plan['description_title1'])): ?>
                                <h4><?= h($plan['description_title1']) ?></h4>
                                <p><?= nl2br(h($plan['description_body1'] ?? '')) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($plan['description_title2'])): ?>
                                <h4><?= h($plan['description_title2']) ?></h4>
                                <p><?= nl2br(h($plan['description_body2'] ?? '')) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="cta">
                    <a href="apply.php?plan_id=<?= (int)$plan['id'] ?>">신청하기</a>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<script>
    document.querySelectorAll('.accordion button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.querySelector(btn.getAttribute('data-target'));
            if (!target) {
                return;
            }
            target.classList.toggle('active');
        });
    });
</script>
</body>
</html>

