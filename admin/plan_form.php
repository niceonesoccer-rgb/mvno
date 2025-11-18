<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

$carriers = [
    '' => '통신사 선택',
    'SKT' => 'SKT',
    'KT' => 'KT',
    'LG U+' => 'LG U+',
    '알뜰_SK' => '알뜰(SK망)',
    '알뜰_KT' => '알뜰(KT망)',
    '알뜰_LG' => '알뜰(LG망)',
];

$planTypes = ['일반', '데이터중심', '통화중심', '요금제추천'];
$customerTypes = ['개인', '법인', '청소년', '시니어'];
$joinTypes = ['번호이동', '신규가입', '기기변경'];
$simTypes = ['USIM', 'eSIM', 'USIM+eSIM'];
$dataTypes = ['5G', 'LTE', '3G', 'IoT'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>요금제 등록</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
</head>
<body>
<main class="container">
    <header>
        <h1>요금제 등록</h1>
        <p>plans 테이블에 저장될 모든 필드를 입력하세요.</p>
    </header>

    <form method="post" action="plan_save.php" class="grid">
        <section>
            <label>
                통신사
                <select name="carrier" required>
                    <?php foreach ($carriers as $value => $label): ?>
                        <option value="<?= h($value) ?>"><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </section>

        <section class="grid">
            <label>
                가입 유형
                <select name="join_type" required>
                    <?php foreach ($joinTypes as $type): ?>
                        <option value="<?= h($type) ?>"><?= h($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                고객 유형
                <select name="customer_type" required>
                    <?php foreach ($customerTypes as $type): ?>
                        <option value="<?= h($type) ?>"><?= h($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                요금제 유형
                <select name="plan_type" required>
                    <?php foreach ($planTypes as $type): ?>
                        <option value="<?= h($type) ?>"><?= h($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                유심 유형
                <select name="sim_type" required>
                    <?php foreach ($simTypes as $type): ?>
                        <option value="<?= h($type) ?>"><?= h($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </section>

        <section class="grid">
            <label>
                데이터 종류
                <select name="data_type" required>
                    <?php foreach ($dataTypes as $type): ?>
                        <option value="<?= h($type) ?>"><?= h($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                요금제명
                <input type="text" name="plan_name" required>
            </label>
            <label>
                데이터 용량
                <input type="text" name="data_allowance" placeholder="예: 15GB + 무제한">
            </label>
            <label>
                통화
                <input type="text" name="voice_allowance" placeholder="예: 무제한 / 300분 등">
            </label>
            <label>
                영상/부가통화
                <input type="text" name="video_allowance">
            </label>
            <label>
                문자
                <input type="text" name="sms_allowance">
            </label>
        </section>

        <section class="grid">
            <label>
                판매가 (원)
                <input type="number" name="price" min="0" step="100">
            </label>
            <label>
                할인가 (원)
                <input type="number" name="discount_price" min="0" step="100">
            </label>
            <label>
                요금제 유지기간 (일)
                <input type="number" name="min_period_days" min="0" step="1">
            </label>
        </section>

        <section>
            <h3>혜택</h3>
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <label>
                    혜택 <?= $i ?>
                    <input type="text" name="benefit<?= $i ?>">
                </label>
            <?php endfor; ?>
        </section>

        <section>
            <h3>상품 설명</h3>
            <?php for ($i = 1; $i <= 2; $i++): ?>
                <label>
                    설명 제목 <?= $i ?>
                    <input type="text" name="description_title<?= $i ?>">
                </label>
                <label>
                    설명 내용 <?= $i ?>
                    <textarea name="description_body<?= $i ?>" rows="4"></textarea>
                </label>
            <?php endfor; ?>
        </section>

        <section>
            <label>
                연결 URL
                <input type="url" name="join_url" placeholder="https://">
            </label>
        </section>

        <section>
            <button type="submit">저장하기</button>
            <a href="index.php" role="button" class="secondary">목록으로</a>
        </section>
    </form>
</main>
</body>
</html>

