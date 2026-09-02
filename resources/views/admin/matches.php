<div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
        <div>
            <span class="eyebrow" style="color: var(--amber);"><?= e(__('admin.title')) ?></span>
            <h1 style="font-size: 2.2rem;"><?= e(__('admin.manage_matches')) ?></h1>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <a href="/admin/matches?status=flagged" class="btn <?= $status === 'flagged' ? 'btn--primary' : 'btn--outline' ?> btn--sm">Flagged / Anomalies</a>
            <a href="/admin/matches?status=completed" class="btn <?= $status === 'completed' ? 'btn--primary' : 'btn--outline' ?> btn--sm">Clean Matches</a>
            <a href="/admin/matches?status=all" class="btn <?= $status === 'all' ? 'btn--primary' : 'btn--outline' ?> btn--sm">All Telemetry</a>
        </div>
    </div>

    <!-- Matches Table -->
    <div class="table-wrap">
        <table class="table" aria-label="Matches Telemetry Audit">
            <thead>
                <tr>
                    <th>Match ID</th>
                    <th>Pilot</th>
                    <th>Chassis</th>
                    <th>Sector</th>
                    <th>Score</th>
                    <th>Waves / Kills</th>
                    <th>Duration</th>
                    <th>Accuracy</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($matches)): ?>
                    <tr><td colspan="10" style="text-align: center; color: var(--text-muted); padding: 3rem;">No matches matching criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($matches as $m): ?>
                        <tr>
                            <td><span class="mono" style="font-size: 0.75rem;"><?= e($m['id']) ?></span></td>
                            <td><strong><?= e($m['username'] ?? 'Anonymous') ?></strong></td>
                            <td><span class="mono" style="text-transform: capitalize;"><?= e($m['vehicle_class']) ?></span></td>
                            <td><span class="mono" style="text-transform: capitalize;"><?= str_replace('_', ' ', e($m['arena_id'])) ?></span></td>
                            <td><span class="font-display font-bold" style="color: var(--cyan);"><?= number_format((int) $m['score']) ?></span></td>
                            <td><span class="mono"><?= (int) $m['waves_cleared'] ?> W / <?= (int) $m['kills'] ?> K</span></td>
                            <td><span class="mono"><?= (int) $m['duration_seconds'] ?>s</span></td>
                            <td><span class="mono"><?= (float) $m['accuracy'] ?>%</span></td>
                            <td>
                                <span style="color: <?= $m['status'] === 'completed' ? 'var(--green)' : 'var(--crimson)' ?>; font-weight: bold; font-family: var(--font-mono); font-size: 0.8rem;">
                                    <?= strtoupper(e($m['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($m['status'] !== 'invalidated'): ?>
                                    <form method="POST" action="/admin/matches/invalidate" style="display: inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="match_id" value="<?= e($m['id']) ?>">
                                        <button type="submit" class="btn btn--danger btn--sm" onclick="return confirm('Invalidate and purge score?');">Purge</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: var(--text-dim); font-size: 0.8rem;">Invalidated</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
