<div class="container" style="max-width: 480px; padding-top: 4rem; padding-bottom: 4rem;">
    <div class="card card--glow-cyan">
        <div style="text-align: center; margin-bottom: 2rem;">
            <span class="eyebrow"><?= e(__('auth.reset_title')) ?></span>
            <h2 style="font-size: 1.8rem;"><?= e(__('auth.reset_subtitle')) ?></h2>
        </div>

        <form method="POST" action="/reset-password">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

            <div class="form-group">
                <label for="email" class="form-label"><?= e(__('auth.email')) ?></label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="pilot@net.io">
            </div>

            <div class="form-group">
                <label for="password" class="form-label"><?= e(__('auth.new_password')) ?></label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Min 8 chars">
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label"><?= e(__('auth.password_confirm')) ?></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required placeholder="Confirm">
            </div>

            <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 1rem;">
                <?= e(__('auth.reset_password_btn')) ?>
            </button>
        </form>
    </div>
</div>
