<div class="container" style="max-width: 480px; padding-top: 4rem; padding-bottom: 4rem;">
    <div class="card card--glow-cyan">
        <div style="text-align: center; margin-bottom: 2rem;">
            <span class="eyebrow"><?= e(__('auth.login_title')) ?></span>
            <h2 style="font-size: 1.8rem;"><?= e(__('auth.login_subtitle')) ?></h2>
        </div>

        <form method="POST" action="/login">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="login" class="form-label"><?= e(__('auth.username')) ?></label>
                <input type="text" id="login" name="login" class="form-control" required autofocus value="<?= e(old('login')) ?>">
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label for="password" class="form-label" style="margin-bottom: 0;"><?= e(__('auth.password')) ?></label>
                    <a href="/forgot-password" style="font-size: 0.8rem; color: var(--text-muted);"><?= e(__('auth.forgot_password')) ?></a>
                </div>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 1rem;">
                <?= e(__('auth.submit_login')) ?>
            </button>
        </form>

        <div style="text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-subtle); font-size: 0.9rem; color: var(--text-muted);">
            <?= e(__('auth.no_account')) ?> <a href="/register" style="color: var(--cyan); font-weight: bold;"><?= e(__('nav.register')) ?></a>
        </div>
    </div>
</div>
