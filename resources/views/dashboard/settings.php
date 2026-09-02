<div class="container" style="max-width: 640px; padding-top: 3.5rem; padding-bottom: 5rem;">
    <div class="card card--glow-cyan">
        <div style="margin-bottom: 2rem;">
            <span class="eyebrow"><?= e(__('dashboard.settings_title')) ?></span>
            <h2 style="font-size: 1.8rem;"><?= e(__('dashboard.settings_title')) ?></h2>
        </div>

        <form method="POST" action="/settings" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <!-- Callsign & Display Name -->
            <div class="form-group">
                <label for="username" class="form-label"><?= e(__('auth.username')) ?></label>
                <input type="text" id="username" class="form-control" value="<?= e($user->username) ?>" disabled style="opacity: 0.6;">
            </div>

            <div class="form-group">
                <label for="display_name" class="form-label"><?= e(__('auth.display_name')) ?></label>
                <input type="text" id="display_name" name="display_name" class="form-control" required value="<?= e($user->display_name) ?>">
            </div>

            <!-- Chassis Preference -->
            <div class="form-group">
                <label for="selected_vehicle" class="form-label"><?= e(__('dashboard.chassis_preference')) ?></label>
                <select id="selected_vehicle" name="selected_vehicle" class="form-control" style="background:#090d18; color:#fff;">
                    <option value="striker" <?= $user->selected_vehicle === 'striker' ? 'selected' : '' ?>>Striker // Interceptor (High Velocity)</option>
                    <option value="titan" <?= $user->selected_vehicle === 'titan' ? 'selected' : '' ?>>Titan // Juggernaut (Heavy Kinetic Armor)</option>
                    <option value="phantom" <?= $user->selected_vehicle === 'phantom' ? 'selected' : '' ?>>Phantom // Infiltrator (Phase Blink)</option>
                </select>
            </div>

            <!-- Language Preference -->
            <div class="form-group">
                <label for="preferred_locale" class="form-label"><?= e(__('dashboard.language_preference')) ?></label>
                <select id="preferred_locale" name="preferred_locale" class="form-control" style="background:#090d18; color:#fff;">
                    <option value="en" <?= $user->preferred_locale === 'en' ? 'selected' : '' ?>>English (Standard Interface)</option>
                    <option value="fr" <?= $user->preferred_locale === 'fr' ? 'selected' : '' ?>>Français (Interface Francophone)</option>
                </select>
            </div>

            <!-- Avatar Upload -->
            <div class="form-group">
                <label for="avatar" class="form-label"><?= e(__('dashboard.avatar_upload')) ?></label>
                <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 0.75rem;">
                    <img src="<?= e($user->getAvatarUrl()) ?>" alt="Avatar Preview" style="width: 64px; height: 64px; border-radius: var(--radius-md); border: 1px solid var(--cyan); object-fit: cover;">
                    <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp" class="form-control" style="padding: 0.5rem;">
                </div>
            </div>

            <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 1rem;">
                <?= e(__('dashboard.update_profile')) ?>
            </button>
        </form>

        <!-- Passcode Change Section -->
        <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border-subtle);">
            <div style="margin-bottom: 1.5rem;">
                <span class="eyebrow"><?= e(__('auth.reset_title')) ?></span>
                <h3 style="font-size: 1.4rem;"><?= e(__('auth.new_password')) ?></h3>
            </div>

            <form method="POST" action="/settings/password">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="current_password" class="form-label"><?= e(__('auth.password')) ?> (Current)</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="new_password" class="form-label"><?= e(__('auth.new_password')) ?></label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                </div>

                <div class="form-group">
                    <label for="new_password_confirmation" class="form-label"><?= e(__('auth.password_confirm')) ?></label>
                    <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="form-control" required minlength="8">
                </div>

                <button type="submit" class="btn btn--secondary" style="width: 100%; margin-top: 0.5rem;">
                    <?= e(__('auth.reset_password_btn')) ?>
                </button>
            </form>
        </div>
    </div>
</div>
