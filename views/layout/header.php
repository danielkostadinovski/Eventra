<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Eventra') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-md navbar-eventra">
    <div class="container">
        <a class="navbar-brand" href="index.php">Eventra</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <div class="navbar-nav ms-auto align-items-md-center gap-2">
                <a class="nav-link" href="index.php?page=login">Log in</a>
                <a class="btn btn-outline-eventra btn-sm" href="index.php?page=register">Register</a>
            </div>
        </div>
    </div>
</nav>
<main class="container py-5">