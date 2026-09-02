<div class="container" style="padding-top: 3.5rem; padding-bottom: 5rem;">
    <?php if (!$user->isEmailVerified()): ?>
        <div class="card" style="margin-bottom: 1.5rem; background: rgba(255, 170, 0, 0.1); border: 1px solid #ffaa00; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="eyebrow" style="color: #ffaa00; margin: 0;">CLEARANCE PENDING</span>
                <div style="font-size: 0.95rem; color: #fff;">Neural-link email (<?= e($user->email) ?>) is unverified. Verify to protect your pilot records.</div>
            </div>
            <form method="POST" action="/verify-email/resend" style="margin: 0;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--outline btn--sm" style="border-color: #ffaa00; color: #ffaa00;">
                    Resend Transmission
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Pilot Header Card -->
    <div class="card card--glow-cyan" style="margin-bottom: 2.5rem; padding: 2rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <img src="<?= e($user->getAvatarUrl()) ?>" alt="Pilot Avatar" style="width: 84px; height: 84px; border-radius: var(--radius-md); border: 2px solid var(--cyan); object-fit: cover;">
                <div>
                    <span class="eyebrow" style="margin-bottom: 0.2rem;">PILOT CALLSIGN // <?= e($user->role) ?></span>
                    <h1 style="font-size: 2.2rem;"><?= e($user->display_name) ?> <span style="font-size: 1.1rem; color: var(--text-muted); font-weight: normal;">(@<?= e($user->username) ?>)</span></h1>
                    <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.5rem;">
                        <span class="rank-pill rank-1" style="font-size: 0.85rem; width: auto; height: auto; padding: 0.2rem 0.6rem; border-radius: var(--radius-sm);">
                            RANK <?= $rank ? "#{$rank}" : 'UNRANKED' ?>
                        </span>
                        <span class="mono" style="font-size: 0.85rem; color: var(--cyan); text-transform: uppercase;">Chassis: <?= e($user->selected_vehicle) ?></span>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 1rem;">
                <a href="/play?vehicle=<?= e($user->selected_vehicle) ?>" class="btn btn--primary">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><polygon points="5,3 19,12 5,21" /></svg>
                    <?= e(__('game.start_match')) ?>
                </a>
                <a href="/settings" class="btn btn--outline"><?= e(__('dashboard.settings_title')) ?></a>
            </div>
        </div>

        <!-- Level & XP Bar -->
        <div style="margin-top: 2rem; border-top: 1px solid var(--border-subtle); padding-top: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <span class="font-display font-bold" style="font-size: 1.1rem; color: #fff;">
                    <?= e(__('dashboard.level', ['level' => $user->level])) ?>
                </span>
                <span class="font-mono" style="font-size: 0.85rem; color: var(--text-muted);">
                    <?= e(__('dashboard.xp', [
                        'current' => number_format($user->xp),
                        'next' => number_format($user->getNextLevelXp()),
                        'percent' => $user->getLevelProgressPercentage()
                    ])) ?>
                </span>
            </div>
            <div class="progress-track" style="height: 10px;">
                <div class="progress-fill" style="width: <?= $user->getLevelProgressPercentage() ?>%; background: linear-gradient(90deg, #00a2ff, var(--cyan)); box-shadow: 0 0 10px var(--cyan);"></div>
            </div>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
        <div class="card">
            <span class="metric-card__label"><?= e(__('dashboard.matches_played')) ?></span>
            <span class="metric-card__value"><?= (int) $stats['total_matches'] ?></span>
        </div>
        <div class="card">
            <span class="metric-card__label"><?= e(__('dashboard.wins')) ?> / <?= e(__('dashboard.losses')) ?></span>
            <span class="metric-card__value"><?= (int) $stats['wins'] ?> <span style="font-size: 1rem; color: var(--text-dim);">/ <?= (int) $stats['losses'] ?></span></span>
        </div>
        <div class="card">
            <span class="metric-card__label"><?= e(__('dashboard.win_rate')) ?></span>
            <span class="metric-card__value" style="color: var(--green);"><?= $stats['win_rate'] ?>%</span>
        </div>
        <div class="card">
            <span class="metric-card__label"><?= e(__('dashboard.personal_best')) ?></span>
            <span class="metric-card__value" style="color: var(--cyan);"><?= number_format((int) $stats['high_score']) ?></span>
        </div>
        <div class="card">
            <span class="metric-card__label"><?= e(__('dashboard.total_kills')) ?></span>
            <span class="metric-card__value"><?= number_format((int) $stats['total_kills']) ?></span>
        </div>
        <div class="card">
            <span class="metric-card__label">Accuracy Average</span>
            <span class="metric-card__value"><?= $stats['avg_accuracy'] ?>%</span>
        </div>
    </div>

    <!-- Match History Table -->
    <div class="section__header" style="text-align: left; margin-bottom: 1.5rem;">
        <span class="eyebrow"><?= e(__('dashboard.match_history')) ?></span>
        <h2 style="font-size: 1.8rem;"><?= e(__('dashboard.match_history')) ?></h2>
    </div>

    <div class="table-wrap">
        <table class="table" aria-label="Pilot Match History">
            <thead>
                <tr>
                    <th>Sector / Arena</th>
                    <th>Chassis</th>
                    <th>Score</th>
                    <th>Waves</th>
                    <th>Kills</th>
                    <th>Accuracy</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2.5rem;"><?= e(__('dashboard.no_matches')) ?></td></tr>
                <?php else: ?>
                    <?php foreach ($history as $m): ?>
                        <tr>
                            <td><span class="font-display font-bold" style="text-transform: capitalize; color: #fff;"><?= str_replace('_', ' ', e($m['arena_id'])) ?></span></td>
                            <td><span class="mono" style="text-transform: capitalize;"><?= e($m['vehicle_class']) ?></span></td>
                            <td><span class="font-display font-bold" style="color: var(--cyan); font-size: 1.1rem;"><?= number_format((int) $m['score']) ?></span></td>
                            <td><span class="mono"><?= (int) $m['waves_cleared'] ?></span></td>
                            <td><span class="mono"><?= (int) $m['kills'] ?></span></td>
                            <td><span class="mono"><?= (float) $m['accuracy'] ?>%</span></td>
                            <td><span class="mono"><?= (int) $m['duration_seconds'] ?>s</span></td>
                            <td>
                                <?php if ($m['status'] === 'completed'): ?>
                                    <span style="color: var(--green); font-size: 0.8rem; font-family: var(--font-mono);">CLEAN</span>
                                <?php else: ?>
                                    <span style="color: var(--crimson); font-size: 0.8rem; font-family: var(--font-mono); font-weight: bold;"><?= strtoupper(e($m['status'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="mono" style="color: var(--text-dim); font-size: 0.8rem;"><?= date('Y-m-d H:i', strtotime($m['finished_at'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
