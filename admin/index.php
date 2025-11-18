<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/database.php';

$stats = [
    'plans' => 0,
    'applications' => 0,
];

try {
    $pdo = get_db_connection();
    $stats['plans'] = (int)$pdo->query('SELECT COUNT(*) FROM plans')->fetchColumn();
    $stats['applications'] = (int)$pdo->query('SELECT COUNT(*) FROM applications')->fetchColumn();
} catch (Throwable $e) {
    $statsError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 · MVNO 요금제</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
</head>
<body>
<main class="container">
    <header>
        <h1>관리자 대시보드</h1>
        <p>요금제 등록/관리 및 신청 내역을 확인하세요.</p>
    </header>

    <?php if (isset($statsError)): ?>
        <article>
            <strong>DB 통계 조회 실패:</strong>
            <p><?= h($statsError) ?></p>
        </article>
    <?php else: ?>
        <section class="grid">
            <article>
                <header>
                    <h2>등록된 요금제</h2>
                </header>
                <p><?= $stats['plans'] ?> 개</p>
            </article>
            <article>
                <header>
                    <h2>신청 건수</h2>
                </header>
                <p><?= $stats['applications'] ?> 건</p>
            </article>
        </section>
    <?php endif; ?>

    <section>
        <h2>다음 단계</h2>
        <ul>
            <li>`admin/plans.php`에서 요금제 CRUD 구현</li>
            <li>`admin/applications.php`에서 신청 내역 확인</li>
            <li>`submit_application.php`에서 고객 신청 처리</li>
        </ul>
    </section>
</main>
</body>
</html>

