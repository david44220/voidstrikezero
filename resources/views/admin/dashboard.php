<div>
    <div style="margin-bottom: 2.5rem;">
        <span class="eyebrow" style="color: var(--amber);"><?= e(__('admin.title')) ?></span>
        <h1 style="font-size: 2.4rem;"><?= e(__('admin.title')) ?></h1>
        <p style="color: var(--text-muted);">Real-time surveillance of pilot accounts, match integrity, and security audit logs.</p>
    </div>

    <!-- Statistics Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
        <div class="card">
            <span class="metric-card__label"><?= e(__('admin.total_users')) ?></span>
            <span class="metric-card__value"><?= (int) $stats['users_count'] ?></span>
        </div>
        <div class="card">
            <span class="metric-card__label"><?= e(__('admin.total_matches')) ?></span>
            <span class="metric-card__value"><?= (int) $stats['matches_count'] ?></span>
        </div>
        <div class="card card--glow-crimson">
            <span class="metric-card__label" style="color: var(--crimson);"><?= e(__('admin.flagged_matches')) ?></span>
            <span class="metric-card__value" style="color: var(--crimson);"><?= (int) $stats['flagged_count'] ?></span>
        </div>
        <div class="card">
            <span class="metric-card__label">Active Challenges</span>
            <span class="metric-card__value"><?= (int) $stats['challenges_count'] ?></span>
        </div>
    </div>

    <!-- Flagged Matches Surveillance -->
    <div style="margin-bottom: 3rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 style="font-size: 1.5rem; color: var(--crimson);">Flagged Anti-Cheat Anomalies</h2>
            <a href="/admin/matches?status=flagged" class="btn btn--outline btn--sm">View All Flagged</a>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Match ID</th>
                        <th>Pilot</th>
                        <th>Score</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($flagged)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No suspicious anomalies currently flagged.</td></tr>
                    <?php else: ?>
                        <?php foreach ($flagged as $fm): ?>
                            <tr>
                                <td><span class="mono" style="font-size: 0.8rem;"><?= e($fm['id']) ?></span></td>
                                <td><strong><?= e($fm['username']) ?></strong></td>
                                <td><span class="mono" style="color: var(--cyan);"><?= number_format((int) $fm['score']) ?></span></td>
                                <td><span class="mono"><?= (int) $fm['duration_seconds'] ?>s</span></td>
                                <td><span style="color: var(--crimson); font-weight: bold; font-family: var(--font-mono);"><?= strtoupper(e($fm['status'])) ?></span></td>
                                <td>
                                    <?php if ($fm['status'] !== 'invalidated'): ?>
                                        <form method="POST" action="/admin/matches/invalidate" style="display: inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="match_id" value="<?= e($fm['id']) ?>">
                                            <button type="submit" class="btn btn--danger btn--sm"><?= e(__('admin.invalidate')) ?></button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--text-dim); font-size: 0.8rem;">Purged</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Audit Logs -->
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 style="font-size: 1.5rem;"><?= e(__('admin.audit_logs')) ?></h2>
            <a href="/admin/audit" class="btn btn--outline btn--sm">View Full Log</a>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Administrator</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($audit_logs)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No audit logs recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($audit_logs as $al): ?>
                            <tr>
                                <td><span class="mono" style="color: var(--text-dim); font-size: 0.8rem;"><?= date('Y-m-d H:i:s', strtotime($al['created_at'])) ?></span></td>
                                <td><strong><?= e($al['admin_username'] ?? 'SYSTEM') ?></strong></td>
                                <td><span class="font-mono" style="color: var(--amber);"><?= e($al['action']) ?></span></td>
                                <td><span class="mono"><?= e($al['target_type']) ?>:<?= e($al['target_id']) ?></span></td>
                                <td><span class="mono" style="font-size: 0.8rem;"><?= e($al['ip_address']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
