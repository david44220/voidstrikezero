<div>
    <div style="margin-bottom: 2rem;">
        <span class="eyebrow" style="color: var(--amber);"><?= e(__('admin.title')) ?></span>
        <h1 style="font-size: 2.2rem;">Challenges Surveillance</h1>
    </div>

    <div class="table-wrap">
        <table class="table" aria-label="Challenges Moderation Registry">
            <thead>
                <tr>
                    <th>Challenge ID</th>
                    <th>Host Pilot</th>
                    <th>Target Score</th>
                    <th>Chassis</th>
                    <th>Sector</th>
                    <th>Difficulty</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($challenges)): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 3rem;">No challenges currently recorded.</td></tr>
                <?php else: ?>
                    <?php foreach ($challenges as $c): ?>
                        <tr>
                            <td><span class="mono" style="font-size: 0.8rem;"><?= e($c['id']) ?></span></td>
                            <td><strong><?= e($c['creator_name']) ?> (@<?= e($c['creator_username']) ?>)</strong></td>
                            <td><span class="font-display font-bold" style="color: var(--cyan);"><?= number_format((int) $c['target_score']) ?></span></td>
                            <td><span class="mono" style="text-transform: capitalize;"><?= e($c['vehicle_class']) ?></span></td>
                            <td><span class="mono" style="text-transform: capitalize;"><?= str_replace('_', ' ', e($c['arena_id'])) ?></span></td>
                            <td><span class="mono"><?= e($c['difficulty']) ?></span></td>
                            <td><span class="mono" style="color: <?= $c['status'] === 'active' ? 'var(--green)' : 'var(--crimson)' ?>;"><?= strtoupper(e($c['status'])) ?></span></td>
                            <td><span class="mono" style="color: var(--text-dim); font-size: 0.8rem;"><?= date('Y-m-d', strtotime($c['created_at'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
