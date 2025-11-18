<?php
declare(strict_types=1);

/**
 * HTML 이스케이프 헬퍼
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * 기본 리다이렉트 처리
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

