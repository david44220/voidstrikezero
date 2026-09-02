<div class="container" style="max-width: 480px; padding-top: 4rem; padding-bottom: 4rem;">
    <div class="card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <span class="eyebrow"><?= e(__('auth.reset_title')) ?></span>
            <h2 style="font-size: 1.8rem;"><?= e(__('auth.reset_subtitle')) ?></h2>
        </div>

        <form method="POST" action="/forgot-password">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email" class="form-label"><?= e(__('auth.email')) ?></label>
                <input type="email" id="email" name="email" class="form-control" required autofocus placeholder="pilot@net.io">
            </div>

            <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 1rem;">
                <?= e(__('auth.send_reset_link')) ?>
            </button>
        </form>

        <div style="text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-subtle); font-size: 0.9rem; color: var(--text-muted);">
            <a href="/login" style="color: var(--cyan);">← <?= e(__('auth.have_account')) ?></a>
        </div>
    </div>
</div>
