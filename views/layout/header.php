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
<nav class="navbar border-bottom">
    <div class="container">
        <a class="navbar-brand" href="index.php">Eventra</a>
        <div class="d-flex align-items-center gap-3">
            <?php if ($navUser = currentUser()): ?>
                <?php if ($navUser['Role'] === 'Client'): ?>                                 
                    <a class="btn btn-eventra" href="index.php?page=venues">Venues</a>     
                    <a class="btn btn-eventra" href="index.php?page=client">My bookings</a>
                <?php endif; ?>      
                <?php if ($navUser['Role'] === 'Server'): ?>
                    <a class="btn btn-eventra" href="index.php?page=server">My events</a>
                <?php endif; ?>
                <?php if ($navUser['Role'] === 'Manager'): ?>
                    <a class="btn btn-eventra" href="index.php?page=manager">Bookings</a>
                    <a class="btn btn-eventra" href="index.php?page=manage-venues">Venues</a>
                <?php endif; ?>
                <span><?= e($navUser['FirstName']) ?>, <?= e(roleName($navUser['Role'])) ?></span>
                <form method="post" action="index.php?page=logout">
                    <?= csrfField() ?>
                    <button class="btn btn-eventra">Log out</button>
                </form>
            <?php else: ?>
                <a class="btn btn-eventra" href="index.php?page=login">Log in</a>
                <a class="btn btn-eventra" href="index.php?page=register">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="<?= $mainClass ?? 'container py-5' ?>">
    <?php if ($flash = takeFlash()): ?>
        <div class="alert alert-<?= $flash['type'] ?> <?= ($mainClass ?? null) === '' ? 'flash-bar' : '' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>


    