<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Eventra') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-eventra">
    <div class="container">
        <a class="navbar-brand" href="index.php">Eventra</a>
        <div class="d-flex align-items-center gap-3">
            <?php if ($navUser = currentUser()): ?>
                <span class="navbar-text"><?= e($navUser['FirstName']) ?>, <?= e(roleName($navUser['Role'])) ?></span>
                <form method="post" action="index.php?page=logout">
                    <?= csrfField() ?>
                    <button class="btn btn-outline-eventra btn-sm">Log out</button>
                </form>
            <?php else: ?>
                <a class="nav-link" href="index.php?page=login">Log in</a>
                <a class="btn btn-outline-eventra btn-sm" href="index.php?page=register">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="<?= $mainClass ?? 'container py-5' ?>">
    <?php if ($flash = takeFlash()): ?>
        <div class="alert alert-success"><?= e($flash) ?></div>
    <?php endif; ?>