<div class="section__header" style="text-align: left; margin-bottom: 2rem;">
    <span class="eyebrow"><?= e(__('admin.title')) ?></span>
    <h1 style="font-size: 2.2rem;"><?= e(__('admin.season_settings')) ?></h1>
    <p class="section__subtitle"><?= e(__('admin.season_desc')) ?></p>
</div>

<div class="card" style="max-width: 700px;">
    <form method="POST" action="/admin/settings">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="season_title" class="form-label"><?= e(__('admin.season_title')) ?></label>
            <input type="text" id="season_title" name="season_title" class="form-control" value="<?= e($settings['season_title'] ?? 'Season 1 // Void Genesis') ?>" required>
        </div>

        <div class="form-group">
            <label for="season_end_date" class="form-label"><?= e(__('admin.season_end_date')) ?></label>
            <input type="date" id="season_end_date" name="season_end_date" class="form-control" value="<?= e($settings['season_end_date'] ?? '2026-12-31') ?>" required>
        </div>

        <div class="form-group">
            <label for="max_score_per_sec" class="form-label"><?= e(__('admin.anticheat_score_ceiling')) ?> (pts/sec)</label>
            <input type="number" id="max_score_per_sec" name="max_score_per_sec" class="form-control" value="<?= e($settings['max_score_per_sec'] ?? '280') ?>" required min="50" max="1000">
        </div>

        <div class="form-group">
            <label for="clock_drift_tol" class="form-label"><?= e(__('admin.clock_drift_tol')) ?> (seconds)</label>
            <input type="number" id="clock_drift_tol" name="clock_drift_tol" class="form-control" value="<?= e($settings['clock_drift_tol'] ?? '15') ?>" required min="2" max="60">
        </div>

        <div class="form-group">
            <label for="maintenance_mode" class="form-label"><?= e(__('admin.maintenance_toggle')) ?></label>
            <select id="maintenance_mode" name="maintenance_mode" class="form-control" style="background:#090d18; color:#fff;">
                <option value="0" <?= ($settings['maintenance_mode'] ?? '0') === '0' ? 'selected' : '' ?>><?= e(__('admin.status_active')) ?></option>
                <option value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'selected' : '' ?>><?= e(__('admin.status_maintenance')) ?></option>
            </select>
        </div>

        <button type="submit" class="btn btn--primary" style="margin-top: 1rem;">
            <?= e(__('admin.save_settings')) ?>
        </button>
    </form>
</div>
