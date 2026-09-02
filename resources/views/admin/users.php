<div>
    <div style="margin-bottom: 2rem;">
        <span class="eyebrow" style="color: var(--amber);"><?= e(__('admin.title')) ?></span>
        <h1 style="font-size: 2.2rem;"><?= e(__('admin.manage_users')) ?></h1>
    </div>

    <!-- Search Form -->
    <div class="card" style="margin-bottom: 2rem; padding: 1rem;">
        <form method="GET" action="/admin/users" style="display: flex; gap: 1rem;">
            <input type="text" name="q" class="form-control" value="<?= e($search) ?>" placeholder="Search callsign, email, or display name...">
            <button type="submit" class="btn btn--outline">Search</button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="table-wrap">
        <table class="table" aria-label="Pilot Accounts Registry">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pilot</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Level / XP</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><span class="mono" style="color: var(--text-dim);"><?= (int) $u['id'] ?></span></td>
                        <td>
                            <div>
                                <strong style="color: #fff;"><?= e($u['display_name']) ?></strong>
                                <div class="mono" style="font-size: 0.75rem; color: var(--text-dim);">@<?= e($u['username']) ?></div>
                            </div>
                        </td>
                        <td><span class="mono" style="font-size: 0.85rem;"><?= e($u['email']) ?></span></td>
                        <td>
                            <span class="mono" style="color: <?= $u['role'] === 'admin' ? 'var(--amber)' : 'var(--cyan)' ?>; font-weight: bold;">
                                <?= strtoupper(e($u['role'])) ?>
                            </span>
                        </td>
                        <td><span class="mono">LVL <?= (int) $u['level'] ?> (<?= number_format((int) $u['xp']) ?> XP)</span></td>
                        <td>
                            <span style="color: <?= $u['status'] === 'active' ? 'var(--green)' : 'var(--crimson)' ?>; font-weight: bold; font-family: var(--font-mono); font-size: 0.85rem;">
                                <?= strtoupper(e($u['status'])) ?>
                            </span>
                        </td>
                        <td><span class="mono" style="color: var(--text-dim); font-size: 0.8rem;"><?= date('Y-m-d', strtotime($u['created_at'])) ?></span></td>
                        <td>
                            <?php if ($u['role'] !== 'admin'): ?>
                                <?php if ($u['status'] === 'active'): ?>
                                    <form method="POST" action="/admin/users/ban" style="display: inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit" class="btn btn--danger btn--sm" onclick="return confirm('Confirm pilot suspension?');"><?= e(__('admin.ban_user')) ?></button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="/admin/users/unban" style="display: inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit" class="btn btn--outline btn--sm"><?= e(__('admin.unban_user')) ?></button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
