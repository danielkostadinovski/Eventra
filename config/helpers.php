<?php

function e(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function view(string $name, array $data = []): void
{
    extract($data);
    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/' . $name . '.php';
    require __DIR__ . '/../views/layout/footer.php';
}