<div class="container" style="max-width: 600px; padding-top: 3.5rem; padding-bottom: 5rem;">
    <div class="card card--glow-cyan">
        <div style="margin-bottom: 2rem;">
            <span class="eyebrow"><?= e(__('challenges.create_title')) ?></span>
            <h1 style="font-size: 2rem; margin-top: 0.25rem;"><?= e(__('challenges.create_title')) ?></h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Broadcast a tactical gauntlet to all pilots across the galactic network.</p>
        </div>

        <form method="POST" action="/challenges">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="target_score" class="form-label"><?= e(__('challenges.target_score')) ?></label>
                <input type="number" id="target_score" name="target_score" class="form-control" required min="5000" max="500000" step="500" value="<?= e(old('target_score', '25000')) ?>" placeholder="e.g. 25000">
                <small style="color: var(--text-dim); font-size: 0.75rem; display: block; margin-top: 0.35rem;">Score benchmark between 5,000 and 500,000 points.</small>
            </div>

            <div class="form-group">
                <label for="vehicle_class" class="form-label"><?= e(__('vehicles.select_chassis')) ?></label>
                <select id="vehicle_class" name="vehicle_class" class="form-control" style="background:#090d18; color:#fff;">
                    <option value="striker" <?= old('vehicle_class') === 'striker' ? 'selected' : '' ?>>Striker // Interceptor</option>
                    <option value="titan" <?= old('vehicle_class') === 'titan' ? 'selected' : '' ?>>Titan // Heavy Juggernaut</option>
                    <option value="phantom" <?= old('vehicle_class') === 'phantom' ? 'selected' : '' ?>>Phantom // Infiltrator</option>
                </select>
            </div>

            <div class="form-group">
                <label for="arena_id" class="form-label"><?= e(__('arenas.title')) ?></label>
                <select id="arena_id" name="arena_id" class="form-control" style="background:#090d18; color:#fff;">
                    <option value="neon_core" <?= old('arena_id') === 'neon_core' ? 'selected' : '' ?>>Sector 01 // Neon Core</option>
                    <option value="orbital_station" <?= old('arena_id') === 'orbital_station' ? 'selected' : '' ?>>Sector 02 // Orbital Station</option>
                    <option value="magma_foundry" <?= old('arena_id') === 'magma_foundry' ? 'selected' : '' ?>>Sector 03 // Magma Foundry</option>
                </select>
            </div>

            <div class="form-group">
                <label for="difficulty" class="form-label"><?= e(__('challenges.difficulty')) ?></label>
                <select id="difficulty" name="difficulty" class="form-control" style="background:#090d18; color:#fff;">
                    <option value="easy" <?= old('difficulty') === 'easy' ? 'selected' : '' ?>>Recruit // 1.0x Multiplier</option>
                    <option value="normal" <?= old('difficulty', 'normal') === 'normal' ? 'selected' : '' ?>>Veteran // 1.5x Multiplier</option>
                    <option value="hard" <?= old('difficulty') === 'hard' ? 'selected' : '' ?>>Void Master // 2.5x Multiplier</option>
                </select>
            </div>

            <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 1rem;">
                <?= e(__('challenges.create_btn')) ?>
            </button>
        </form>
    </div>
</div>
