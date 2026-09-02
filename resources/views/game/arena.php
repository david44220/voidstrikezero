<div class="game-viewport">
    <!-- WebGL Canvas -->
    <canvas id="game-canvas"></canvas>

    <!-- In-Game HUD Overlay -->
    <div class="hud-overlay">
        <!-- Top Row -->
        <div class="hud-top">
            <div class="hud-status-bars">
                <div class="hud-bar">
                    <div class="hud-bar__header">
                        <span style="color: var(--green);"><?= e(__('game.health')) ?></span>
                        <span class="hud-hull-text font-mono" style="color: #fff;">100 / 100</span>
                    </div>
                    <div class="hud-bar__track">
                        <div class="hud-bar__fill-hull" style="width: 100%;"></div>
                    </div>
                </div>

                <div class="hud-bar">
                    <div class="hud-bar__header">
                        <span style="color: var(--cyan);"><?= e(__('game.energy')) ?></span>
                        <span class="hud-energy-text font-mono" style="color: #fff;">100 / 100</span>
                    </div>
                    <div class="hud-bar__track">
                        <div class="hud-bar__fill-energy" style="width: 100%;"></div>
                    </div>
                </div>
            </div>

            <!-- Score & Combo -->
            <div class="hud-score-box">
                <div class="hud-score">0</div>
                <div class="hud-combo">1.0x COMBO</div>
            </div>
        </div>

        <!-- Center Notice -->
        <div class="hud-center-notice">
            SYSTEM READY
        </div>

        <!-- Bottom Row -->
        <div class="hud-bottom">
            <div class="hud-cooldowns">
                <div class="hud-cd-slot hud-cd-slot--dash">
                    <span class="hud-cd-key">SPACE</span>
                    <span>DASH</span>
                </div>
                <div class="hud-cd-slot hud-cd-slot--special">
                    <span class="hud-cd-key">E</span>
                    <span>SPECIAL</span>
                </div>
            </div>

            <div style="text-align: right; font-family: var(--font-display);">
                <div class="hud-wave-text" style="font-size: 1.5rem; font-weight: 800; color: var(--cyan);">WAVE 1</div>
                <div style="font-size: 0.9rem; color: var(--text-muted);">
                    KILLS: <span class="hud-kills-text font-bold" style="color: #fff;">0</span>
                </div>
            </div>
        </div>

        <!-- Mobile Touch Controls -->
        <div class="touch-zone touch-joystick-zone" aria-label="Virtual Joystick">
            <div class="touch-stick"></div>
        </div>

        <div class="touch-zone touch-actions-zone" aria-label="Virtual Action Triggers">
            <div class="touch-btn touch-btn--fire">FIRE</div>
            <div style="display: flex; gap: 0.75rem;">
                <div class="touch-btn touch-btn--dash">DASH</div>
                <div class="touch-btn touch-btn--special">SPEC</div>
            </div>
        </div>
    </div>

    <!-- Pause Modal -->
    <div id="pause-modal" class="modal-backdrop" style="display: none;">
        <div class="modal" style="text-align: center;">
            <span class="eyebrow"><?= e(__('game.paused')) ?></span>
            <h2 style="font-size: 2rem; margin: 0.5rem 0 1.5rem;"><?= e(__('game.paused')) ?></h2>
            <div style="margin: 1.5rem 0; padding: 1rem; background: rgba(0,0,0,0.4); border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <span class="eyebrow" style="font-size: 0.75rem; margin-bottom: 0.5rem; display: block;">GRAPHICS QUALITY PRESET</span>
                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                    <button type="button" class="btn btn--sm btn--outline btn-gfx" data-preset="auto">Auto</button>
                    <button type="button" class="btn btn--sm btn--outline btn-gfx" data-preset="low">Low</button>
                    <button type="button" class="btn btn--sm btn--outline btn-gfx" data-preset="medium">Med</button>
                    <button type="button" class="btn btn--sm btn--outline btn-gfx" data-preset="high">High</button>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button type="button" class="btn btn--primary" id="btn-resume"><?= e(__('game.resume')) ?></button>
                <a href="/" class="btn btn--outline"><?= e(__('game.abort')) ?></a>
            </div>
        </div>
    </div>

    <!-- Match Conclusion Results Modal -->
    <div id="results-modal" class="modal-backdrop" style="display: none;">
        <div class="modal" style="text-align: center;">
            <span id="res-status" class="eyebrow"><?= e(__('game.validated')) ?></span>
            <h2 style="font-size: 2.2rem; margin: 0.5rem 0 1.5rem;"><?= e(__('game.score_breakdown')) ?></h2>

            <div style="background: rgba(0, 0, 0, 0.4); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1.5rem;">
                <div class="font-display" style="font-size: 3rem; font-weight: 800; color: var(--cyan);" id="res-score">0</div>
                <div class="mono" style="color: var(--green); font-size: 1.1rem; font-weight: bold; margin-bottom: 1rem;" id="res-xp">+0 XP</div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; border-top: 1px solid var(--border-subtle); padding-top: 1rem;">
                    <div>
                        <div class="metric-card__label">Waves</div>
                        <div class="font-display font-bold" style="font-size: 1.3rem; color: #fff;" id="res-waves">0</div>
                    </div>
                    <div>
                        <div class="metric-card__label">Kills</div>
                        <div class="font-display font-bold" style="font-size: 1.3rem; color: #fff;" id="res-kills">0</div>
                    </div>
                    <div>
                        <div class="metric-card__label">Duration</div>
                        <div class="font-display font-bold" style="font-size: 1.3rem; color: #fff;" id="res-duration">0s</div>
                    </div>
                </div>
            </div>

            <div id="res-achievements"></div>

            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
                <button type="button" class="btn btn--primary" onclick="window.location.reload();"><?= e(__('game.play_again')) ?></button>
                <a href="/leaderboard" class="btn btn--outline"><?= e(__('game.view_leaderboard')) ?></a>
                <a href="/dashboard" class="btn btn--outline"><?= e(__('nav.dashboard')) ?></a>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::startSection('scripts'); ?>
<script type="module">
    import { GameEngine } from '/assets/js/game/Engine.js';

    const config = {
        vehicle: '<?= e($selected_vehicle) ?>',
        arena: '<?= e($selected_arena) ?>',
        difficulty: '<?= e($selected_diff) ?>',
        mode: '<?= e($mode) ?>',
        challengeId: '<?= e($challenge['id'] ?? '') ?>',
    };

    const engine = new GameEngine(config);
    window.gameEngine = engine;

    // Start match
    engine.start();

    // Pause Resume Button
    document.getElementById('btn-resume')?.addEventListener('click', () => {
        engine.input.pause = false;
        engine.hud.setPause(false);
    });
</script>
<?php \App\Core\View::endSection(); ?>
