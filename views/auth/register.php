<div class="card auth-card mx-auto">
    <div class="card-body p-4">
        <h1 class="h3 mb-4 text-center">Create an account</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php?page=register">
            <?= csrfField() ?>
            <label class="form-label">First name</label>
            <input type="text" name="first_name" class="form-control mb-3" value="<?= e($firstName) ?>">
            <label class="form-label">Last name</label>
            <input type="text" name="last_name" class="form-control mb-3" value="<?= e($lastName) ?>">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control mb-3" value="<?= e($email) ?>">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control mb-3">
            <label class="form-label">Confirm password</label>
            <input type="password" name="password_confirm" class="form-control mb-4">
            <button class="btn btn-eventra w-100">Create account</button>
        </form>
    </div>
</div>