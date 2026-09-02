<div class="container" style="padding-top: 3.5rem; padding-bottom: 5rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 2.5rem;">
        <div>
            <span class="eyebrow"><?= e(__('challenges.title')) ?></span>
            <h1 style="font-size: 2.5rem;"><?= e(__('challenges.title')) ?></h1>
            <p class="section__subtitle"><?= e(__('challenges.subtitle')) ?></p>
        </div>

        <?php if ($user): ?>
            <a href="/challenges/create" class="btn btn--primary"><?= e(__('challenges.create_btn')) ?></a>
        <?php else: ?>
            <a href="/login" class="btn btn--primary">Log In To Broadcast Challenge</a>
        <?php endif; ?>
    </div>

    <!-- Active Community Challenges Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 3.5rem;">
        <?php if (empty($public_challenges)): ?>
            <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                <p style="color: var(--text-muted);"><?= e(__('challenges.no_challenges')) ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($public_challenges as $chal): ?>
                <div class="card card--glow-cyan">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <span class="eyebrow font-mono" style="margin: 0; font-size: 0.75rem;">HOSTED BY <?= e($chal['creator_username']) ?></span>
                            <div class="font-display font-bold" style="font-size: 2rem; color: var(--cyan); margin-top: 0.25rem;">
                                <?= number_format((int) $chal['target_score']) ?> <span style="font-size: 0.9rem; color: var(--text-muted);">PTS</span>
                            </div>
                        </div>
                        <span class="rank-pill" style="border-radius: var(--radius-sm); font-size: 0.75rem; width: auto; height: auto; padding: 0.2rem 0.6rem; text-transform: uppercase;">
                            <?= e($chal['difficulty']) ?>
                        </span>
                    </div>

                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem; display: flex; gap: 1.25rem;">
                        <span>Sector: <strong style="color: #fff; text-transform: capitalize;"><?= str_replace('_', ' ', e($chal['arena_id'])) ?></strong></span>
                        <span>Chassis: <strong style="color: #fff; text-transform: capitalize;"><?= e($chal['vehicle_class']) ?></strong></span>
                    </div>

                    <?php if (!empty($chal['best_score'])): ?>
                        <div style="background: rgba(0, 0, 0, 0.4); padding: 0.6rem 0.8rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem; font-size: 0.85rem; display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);"><?= e(__('challenges.best_attempt')) ?>:</span>
                            <strong style="color: var(--green);"><?= number_format((int) $chal['best_score']) ?> by <?= e($chal['best_username']) ?></strong>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 0.75rem;">
                        <a href="/challenge/<?= e($chal['id']) ?>" class="btn btn--outline btn--sm" style="flex: 1;">Details</a>
                        <a href="/play?challenge=<?= e($chal['id']) ?>" class="btn btn--primary btn--sm" style="flex: 1;"><?= e(__('challenges.accept_challenge')) ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
