<div class="container" style="max-width: 520px; padding-top: 4rem; padding-bottom: 4rem;">
    <div class="card card--glow-cyan">
        <div style="text-align: center; margin-bottom: 2rem;">
            <span class="eyebrow"><?= e(__('auth.register_title')) ?></span>
            <h2 style="font-size: 1.8rem;"><?= e(__('auth.register_subtitle')) ?></h2>
        </div>

        <form method="POST" action="/register">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="username" class="form-label"><?= e(__('auth.username')) ?></label>
                <input type="text" id="username" name="username" class="form-control" required autofocus value="<?= e(old('username')) ?>" placeholder="e.g. ApexViper">
            </div>

            <div class="form-group">
                <label for="display_name" class="form-label"><?= e(__('auth.display_name')) ?></label>
                <input type="text" id="display_name" name="display_name" class="form-control" value="<?= e(old('display_name')) ?>" placeholder="e.g. Commander Thorne">
            </div>

            <div class="form-group">
                <label for="email" class="form-label"><?= e(__('auth.email')) ?></label>
                <input type="email" id="email" name="email" class="form-control" required value="<?= e(old('email')) ?>" placeholder="pilot@net.io">
            </div>

            <div class="form-group">
                <label for="selected_vehicle" class="form-label"><?= e(__('dashboard.chassis_preference')) ?></label>
                <select id="selected_vehicle" name="selected_vehicle" class="form-control" style="background:#090d18; color:#fff;">
                    <option value="striker" <?= old('selected_vehicle') === 'striker' ? 'selected' : '' ?>>Striker // Interceptor (High Speed)</option>
                    <option value="titan" <?= old('selected_vehicle') === 'titan' ? 'selected' : '' ?>>Titan // Juggernaut (Heavy Armor)</option>
                    <option value="phantom" <?= old('selected_vehicle') === 'phantom' ? 'selected' : '' ?>>Phantom // Infiltrator (Phase Dash)</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="password" class="form-label"><?= e(__('auth.password')) ?></label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Min 8 chars">
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label"><?= e(__('auth.password_confirm')) ?></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required placeholder="Confirm">
                </div>
            </div>

            <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 1rem;">
                <?= e(__('auth.submit_register')) ?>
            </button>
        </form>

        <div style="text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-subtle); font-size: 0.9rem; color: var(--text-muted);">
            <?= e(__('auth.have_account')) ?> <a href="/login" style="color: var(--cyan); font-weight: bold;"><?= e(__('nav.login')) ?></a>
        </div>
    </div>
</div>
