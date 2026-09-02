<div class="container" style="max-width: 900px; padding-top: 3.5rem; padding-bottom: 5rem;">
    <div class="card card--glow-cyan" style="padding: 2.5rem; margin-bottom: 3rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 2rem;">
            <div>
                <span class="eyebrow">DUEL PROTOCOL // <?= e($challenge['id']) ?></span>
                <h1 style="font-size: 2.8rem; margin: 0.2rem 0;"><?= number_format((int) $challenge['target_score']) ?> <span style="font-size: 1.2rem; color: var(--cyan);">TARGET PTS</span></h1>
                <div style="color: var(--text-muted); font-size: 0.95rem;">
                    Created by <strong style="color: #fff;"><?= e($challenge['creator_name']) ?> (@<?= e($challenge['creator_username']) ?>)</strong>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.75rem; align-items: flex-end;">
                <a href="/play?challenge=<?= e($challenge['id']) ?>" class="btn btn--primary btn--lg">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><polygon points="5,3 19,12 5,21" /></svg>
                    <?= e(__('challenges.accept_challenge')) ?>
                </a>
                <button type="button" class="btn btn--outline btn--sm" data-copy="<?= url("/challenge/{$challenge['id']}") ?>">
                    <?= e(__('challenges.copy_link')) ?>
                </button>
            </div>
        </div>

        <!-- Specifications Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; border-top: 1px solid var(--border-subtle); padding-top: 1.5rem;">
            <div>
                <div class="metric-card__label">Mandatory Chassis</div>
                <div class="font-display font-bold" style="font-size: 1.2rem; text-transform: capitalize; color: #fff;"><?= e($challenge['vehicle_class']) ?></div>
            </div>
            <div>
                <div class="metric-card__label">Combat Sector</div>
                <div class="font-display font-bold" style="font-size: 1.2rem; text-transform: capitalize; color: #fff;"><?= str_replace('_', ' ', e($challenge['arena_id'])) ?></div>
            </div>
            <div>
                <div class="metric-card__label">Combat Tier</div>
                <div class="font-display font-bold" style="font-size: 1.2rem; text-transform: capitalize; color: var(--cyan);"><?= e($challenge['difficulty']) ?></div>
            </div>
            <div>
                <div class="metric-card__label">Total Attempts</div>
                <div class="font-display font-bold" style="font-size: 1.2rem; color: #fff;"><?= count($attempts) ?> Rivals</div>
            </div>
        </div>
    </div>

    <!-- Attempts Table -->
    <div class="section__header" style="text-align: left; margin-bottom: 1.5rem;">
        <span class="eyebrow">CHALLENGER TELEMETRY</span>
        <h2 style="font-size: 1.8rem;"><?= e(__('challenges.attempts_count', ['count' => count($attempts)])) ?></h2>
    </div>

    <div class="table-wrap">
        <table class="table" aria-label="Challenge Attempt Standings">
            <thead>
                <tr>
                    <th style="width: 70px;">Rank</th>
                    <th>Challenger Pilot</th>
                    <th>Achieved Score</th>
                    <th>Objective Status</th>
                    <th>Waves Cleared</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attempts)): ?>
                    <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">No pilots have tested this challenge yet. Be the first!</td></tr>
                <?php else: ?>
                    <?php foreach ($attempts as $idx => $att): ?>
                        <tr>
                            <td>
                                <span class="rank-pill <?= $idx === 0 ? 'rank-1' : ($idx === 1 ? 'rank-2' : '') ?>">
                                    <?= $idx + 1 ?>
                                </span>
                            </td>
                            <td>
                                <span class="font-display font-bold" style="color: #fff;"><?= e($att['display_name']) ?></span>
                            </td>
                            <td>
                                <span class="font-display font-bold" style="color: <?= $att['achieved_target'] ? 'var(--green)' : 'var(--cyan)' ?>; font-size: 1.15rem;">
                                    <?= number_format((int) $att['score']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($att['achieved_target']): ?>
                                    <span style="color: var(--green); font-family: var(--font-mono); font-weight: bold;">VICTORY (PASSED)</span>
                                <?php else: ?>
                                    <span style="color: var(--crimson); font-family: var(--font-mono);">DEFICIT</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="mono"><?= (int) $att['waves'] ?></span></td>
                            <td><span class="mono" style="color: var(--text-dim); font-size: 0.8rem;"><?= date('Y-m-d H:i', strtotime($att['created_at'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
