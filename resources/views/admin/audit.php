<div>
    <div style="margin-bottom: 2rem;">
        <span class="eyebrow" style="color: var(--amber);"><?= e(__('admin.title')) ?></span>
        <h1 style="font-size: 2.2rem;"><?= e(__('admin.audit_logs')) ?></h1>
        <p style="color: var(--text-muted);">Immutable chronological ledger of all administrative interventions and anti-cheat enforcements.</p>
    </div>

    <div class="table-wrap">
        <table class="table" aria-label="Security Audit Log Trail">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Timestamp</th>
                    <th>Administrator</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem;">No security actions recorded.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td><span class="mono" style="font-size: 0.75rem; color: var(--text-dim);"><?= e($l['id']) ?></span></td>
                            <td><span class="mono" style="font-size: 0.8rem;"><?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?></span></td>
                            <td><strong><?= e($l['admin_username'] ?? 'SYSTEM') ?></strong></td>
                            <td><span class="mono" style="color: var(--amber); font-weight: bold;"><?= e($l['action']) ?></span></td>
                            <td><span class="mono"><?= e($l['target_type']) ?>:<?= e($l['target_id']) ?></span></td>
                            <td><span class="mono" style="font-size: 0.75rem; color: var(--text-muted);"><?= e($l['details']) ?></span></td>
                            <td><span class="mono" style="font-size: 0.8rem;"><?= e($l['ip_address']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
