<div class="container" style="padding-top: 3.5rem; padding-bottom: 5rem;">
    <div class="section__header" style="text-align: left; margin-bottom: 2.5rem;">
        <span class="eyebrow"><?= e(__('leaderboard.weekly_tab')) ?></span>
        <h1 style="font-size: 2.5rem;"><?= e(__('leaderboard.weekly_tab')) ?></h1>
        <p class="section__subtitle">Rankings logged within the current 7-day tactical cycle.</p>
    </div>

    <!-- Tab Bar -->
    <div style="display: flex; gap: 1rem; border-bottom: 1px solid var(--border-subtle); margin-bottom: 2rem;">
        <a href="/leaderboard" class="btn <?= $tab === 'global' ? 'btn--primary' : 'btn--outline' ?>" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
            <?= e(__('leaderboard.global_tab')) ?>
        </a>
        <a href="/leaderboard/weekly" class="btn <?= $tab === 'weekly' ? 'btn--primary' : 'btn--outline' ?>" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
            <?= e(__('leaderboard.weekly_tab')) ?>
        </a>
    </div>

    <!-- Filter Form -->
    <div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
        <form method="GET" action="/leaderboard/weekly" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 120px; gap: 1rem; align-items: flex-end;">
            <div>
                <label for="vehicle" class="form-label" style="font-size: 0.75rem;"><?= e(__('vehicles.select_chassis')) ?></label>
                <select id="vehicle" name="vehicle" class="form-control" style="background:#060810;">
                    <option value="">All Chassis</option>
                    <option value="striker" <?= $current_vehicle === 'striker' ? 'selected' : '' ?>>Striker</option>
                    <option value="titan" <?= $current_vehicle === 'titan' ? 'selected' : '' ?>>Titan</option>
                    <option value="phantom" <?= $current_vehicle === 'phantom' ? 'selected' : '' ?>>Phantom</option>
                </select>
            </div>

            <div>
                <label for="arena" class="form-label" style="font-size: 0.75rem;"><?= e(__('arenas.title')) ?></label>
                <select id="arena" name="arena" class="form-control" style="background:#060810;">
                    <option value="">All Sectors</option>
                    <option value="neon_core" <?= $current_arena === 'neon_core' ? 'selected' : '' ?>>Neon Core</option>
                    <option value="orbital_station" <?= $current_arena === 'orbital_station' ? 'selected' : '' ?>>Orbital Station</option>
                    <option value="magma_foundry" <?= $current_arena === 'magma_foundry' ? 'selected' : '' ?>>Magma Foundry</option>
                </select>
            </div>

            <div>
                <label for="q" class="form-label" style="font-size: 0.75rem;"><?= e(__('leaderboard.pilot')) ?></label>
                <input type="text" id="q" name="q" class="form-control" value="<?= e($search ?? '') ?>" placeholder="<?= e(__('leaderboard.search_placeholder')) ?>">
            </div>

            <div>
                <button type="submit" class="btn btn--outline" style="width: 100%;">Filter</button>
            </div>
        </form>
    </div>

    <!-- Leaderboard Table -->
    <div class="table-wrap">
        <table class="table" aria-label="Weekly Ranked Leaderboard">
            <thead>
                <tr>
                    <th style="width: 70px;"><?= e(__('leaderboard.rank')) ?></th>
                    <th><?= e(__('leaderboard.pilot')) ?></th>
                    <th><?= e(__('leaderboard.score')) ?></th>
                    <th><?= e(__('leaderboard.vehicle')) ?></th>
                    <th><?= e(__('leaderboard.arena')) ?></th>
                    <th><?= e(__('leaderboard.waves')) ?></th>
                    <th><?= e(__('leaderboard.kills')) ?></th>
                    <th><?= e(__('leaderboard.date')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaderboard)): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 3rem;"><?= e(__('leaderboard.no_entries')) ?></td></tr>
                <?php else: ?>
                    <?php foreach ($leaderboard as $idx => $row): ?>
                        <tr>
                            <td>
                                <span class="rank-pill <?= $idx === 0 ? 'rank-1' : ($idx === 1 ? 'rank-2' : ($idx === 2 ? 'rank-3' : '')) ?>">
                                    <?= $idx + 1 ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <span class="font-display font-bold" style="color: #fff;"><?= e($row['display_name']) ?></span>
                                    <span class="eyebrow font-mono" style="margin: 0; font-size: 0.65rem;">LVL <?= (int) $row['user_level'] ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="font-display font-bold" style="color: var(--cyan); font-size: 1.25rem;">
                                    <?= number_format((int) $row['score']) ?>
                                </span>
                            </td>
                            <td><span class="mono" style="text-transform: capitalize;"><?= e($row['vehicle_class']) ?></span></td>
                            <td><span class="mono" style="text-transform: capitalize;"><?= str_replace('_', ' ', e($row['arena_id'])) ?></span></td>
                            <td><span class="mono"><?= (int) $row['waves_cleared'] ?></span></td>
                            <td><span class="mono"><?= (int) $row['kills'] ?></span></td>
                            <td><span class="mono" style="color: var(--text-dim); font-size: 0.8rem;"><?= date('Y-m-d', strtotime($row['finished_at'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
