<h1>Welcome, <?= e($user['FirstName']) ?></h1>
<p class="text-muted">You are logged in as <?= e(roleName($user['Role'])) ?>. Your dashboard is coming soon.</p>