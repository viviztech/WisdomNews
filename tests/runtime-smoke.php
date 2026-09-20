<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/config.php';

$failures = [];

$tamilSlug = slugify('தமிழ் செய்திகள் 2026');
if ($tamilSlug !== 'தமிழ்-செய்திகள்-2026') {
    $failures[] = 'Tamil slug generation failed: ' . $tamilSlug;
}

$clean = sanitize_html('<p onclick="x()">ok</p><script>alert(1)</script><a href="javascript:x">bad</a>');
foreach (['onclick', '<script', 'javascript:'] as $unsafe) {
    if (str_contains($clean, $unsafe)) {
        $failures[] = 'HTML sanitizer retained unsafe content: ' . $unsafe;
    }
}
if (!str_contains($clean, '<p>ok</p>')) {
    $failures[] = 'HTML sanitizer removed safe paragraph content: ' . $clean;
}

if (excerpt('<p>தமிழ் செய்தி உள்ளடக்கம்</p>', 100) !== 'தமிழ் செய்தி உள்ளடக்கம்') {
    $failures[] = 'Tamil excerpt generation failed.';
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Runtime smoke checks passed.\n");
