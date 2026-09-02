<div class="container" style="padding-top: 3.5rem; padding-bottom: 5rem;">
    <div class="section__header" style="text-align: left; margin-bottom: 2.5rem;">
        <span class="eyebrow"><?= e(__('achievements.title')) ?></span>
        <h1 style="font-size: 2.5rem;"><?= e(__('achievements.title')) ?></h1>
        <p class="section__subtitle"><?= e(__('achievements.subtitle')) ?></p>
    </div>

    <!-- Achievements Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.75rem;">
        <?php foreach ($achievements as $ach): ?>
            <?php 
                $isUnlocked = isset($unlocked_map[$ach['id']]);
                $locale = \App\Localization\Translator::getLocale();
                $name = $locale === 'fr' ? $ach['name_fr'] : $ach['name_en'];
                $desc = $locale === 'fr' ? $ach['description_fr'] : $ach['description_en'];
            ?>
            <div class="card <?= $isUnlocked ? 'card--glow-cyan' : '' ?>" style="display: flex; gap: 1.25rem; align-items: flex-start; opacity: <?= $isUnlocked ? '1' : '0.65' ?>;">
                <div style="width: 52px; height: 52px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; background: <?= $isUnlocked ? 'rgba(0, 240, 255, 0.2)' : 'rgba(255, 255, 255, 0.05)' ?>; border: 1px solid <?= $isUnlocked ? 'var(--cyan)' : 'var(--border-subtle)' ?>;">
                    <?= $isUnlocked ? '✦' : '✧' ?>
                </div>

                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.35rem;">
                        <h3 style="font-size: 1.15rem; color: <?= $isUnlocked ? '#fff' : 'var(--text-muted)' ?>;"><?= e($name) ?></h3>
                        <span class="font-mono" style="font-size: 0.8rem; font-weight: bold; color: var(--green);">+<?= (int) $ach['xp_reward'] ?> XP</span>
                    </div>
                    <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5; margin-bottom: 0.75rem;"><?= e($desc) ?></p>

                    <div>
                        <?php if ($isUnlocked): ?>
                            <span class="eyebrow font-mono" style="margin: 0; font-size: 0.65rem; color: var(--cyan);">
                                <?= e(__('achievements.unlocked')) ?> (<?= date('Y-m-d', strtotime($unlocked_map[$ach['id']])) ?>)
                            </span>
                        <?php else: ?>
                            <span class="font-mono" style="font-size: 0.75rem; color: var(--text-dim);"><?= e(__('achievements.locked')) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
