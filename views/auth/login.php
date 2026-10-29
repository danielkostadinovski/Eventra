<div class="card auth-card mx-auto">
    <div class="card-body p-4">
        <h1 class="h3 mb-4 text-center">Log in</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php?page=login">
            <?= csrfField() ?>
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control mb-3" value="<?= e($email) ?>">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control mb-4">
            <button class="btn btn-eventra w-100">Log in</button>
        </form>
    </div>
</div>