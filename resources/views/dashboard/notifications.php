<div class="container" style="max-width: 760px; padding-top: 3.5rem; padding-bottom: 5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <span class="eyebrow">PILOT COMMUNICATIONS</span>
            <h1 style="font-size: 2rem;">Notifications & Alerts</h1>
        </div>

        <?php if (!empty($notifications)): ?>
            <form method="POST" action="/notifications/read-all">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--outline btn--sm">Mark All As Read</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <p style="color: var(--text-muted);">No unread communications on your frequency.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($notifications as $n): ?>
                <div class="card <?= $n['is_read'] ? '' : 'card--glow-cyan' ?>">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <strong style="color: <?= $n['is_read'] ? 'var(--text-main)' : 'var(--cyan)' ?>;">
                            <?= e(\App\Localization\Translator::getLocale() === 'fr' ? $n['title_fr'] : $n['title_en']) ?>
                        </strong>
                        <span class="mono" style="font-size: 0.75rem; color: var(--text-dim);">
                            <?= date('M d, H:i', strtotime($n['created_at'])) ?>
                        </span>
                    </div>
                    <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                        <?= e(\App\Localization\Translator::getLocale() === 'fr' ? $n['message_fr'] : $n['message_en']) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
