<!-- Hero Section -->
<section class="hero" id="hero">
    <!-- Interactive Three.js 3D Hero Background Canvas -->
    <div class="hero__canvas-container">
        <canvas id="hero-canvas" class="hero__canvas" aria-hidden="true"></canvas>
    </div>

    <div class="container hero__content">
        <span class="eyebrow"><?= e(__('hero.eyebrow')) ?></span>
        <h1 class="hero__title"><?= e(__('hero.title')) ?></h1>
        <p class="hero__subtitle"><?= e(__('hero.subtitle')) ?></p>
        
        <div class="hero__actions">
            <a href="/play" class="btn btn--primary btn--lg">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><polygon points="5,3 19,12 5,21" /></svg>
                <?= e(__('hero.cta_primary')) ?>
            </a>
            <a href="#vehicles" class="btn btn--outline btn--lg"><?= e(__('hero.cta_secondary')) ?></a>
        </div>

        <div class="hero__metrics">
            <div>
                <span class="metric-card__value"><?= number_format($stats['total_matches'] ?? 0) ?></span>
                <span class="metric-card__label"><?= e(__('hero.live_stats_matches')) ?></span>
            </div>
            <div>
                <span class="metric-card__value"><?= number_format($stats['total_pilots'] ?? 0) ?></span>
                <span class="metric-card__label"><?= e(__('hero.live_stats_pilots')) ?></span>
            </div>
            <div>
                <span class="metric-card__value"><?= number_format($stats['high_score'] ?? 0) ?></span>
                <span class="metric-card__label"><?= e(__('hero.live_stats_score')) ?></span>
            </div>
            <div>
                <span class="metric-card__value" style="color: var(--cyan);"><?= e($stats['tick_rate'] ?? '60 Hz') ?></span>
                <span class="metric-card__label"><?= e(__('hero.live_stats_latency')) ?></span>
            </div>
        </div>
    </div>
</section>

<!-- Season 1 Banner -->
<section style="background: linear-gradient(90deg, #0b1120 0%, #15223e 50%, #0b1120 100%); border-top: 1px solid var(--border-subtle); border-bottom: 1px solid var(--border-subtle); padding: 2rem 0;">
    <div class="container" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
        <div>
            <span class="eyebrow" style="margin-bottom: 0.25rem;"><?= e(__('app.season')) ?></span>
            <h3 style="font-size: 1.5rem;"><?= e(__('app.season_desc')) ?></h3>
        </div>
        <a href="/play" class="btn btn--primary"><?= e(__('game.start_match')) ?></a>
    </div>
</section>

<!-- Game Modes -->
<section class="section" id="modes">
    <div class="container">
        <div class="section__header">
            <span class="eyebrow">TACTICAL OPERATIONS</span>
            <h2 class="section__title"><?= e(__('modes.title')) ?></h2>
            <p class="section__subtitle"><?= e(__('modes.subtitle')) ?></p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
            <div class="card card--glow-cyan">
                <span class="eyebrow font-mono" style="color: var(--cyan);">01 // IMMEDIATE DEPLOYMENT</span>
                <h3 style="font-size: 1.4rem; margin: 0.5rem 0 1rem;"><?= e(__('modes.quick_title')) ?></h3>
                <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 1.5rem;"><?= e(__('modes.quick_desc')) ?></p>
                <a href="/play?mode=quick" class="btn btn--outline btn--sm"><?= e(__('hero.cta_primary')) ?> →</a>
            </div>

            <div class="card card--glow-crimson">
                <span class="eyebrow font-mono" style="color: var(--crimson);">02 // RIVAL TRANSMISSION</span>
                <h3 style="font-size: 1.4rem; margin: 0.5rem 0 1rem;"><?= e(__('modes.challenge_title')) ?></h3>
                <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 1.5rem;"><?= e(__('modes.challenge_desc')) ?></p>
                <a href="/challenges" class="btn btn--outline btn--sm"><?= e(__('nav.challenges')) ?> →</a>
            </div>

            <div class="card">
                <span class="eyebrow font-mono" style="color: var(--purple);">03 // GHOST TELEMETRY</span>
                <h3 style="font-size: 1.4rem; margin: 0.5rem 0 1rem;"><?= e(__('modes.rival_title')) ?></h3>
                <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 1.5rem;"><?= e(__('modes.rival_desc')) ?></p>
                <a href="/leaderboard" class="btn btn--outline btn--sm"><?= e(__('nav.leaderboard')) ?> →</a>
            </div>
        </div>
    </div>
</section>

<!-- Vehicle Arsenal -->
<section class="section" id="vehicles" style="background: rgba(6, 9, 18, 0.6);">
    <div class="container">
        <div class="section__header">
            <span class="eyebrow">CHASSIS SPECIFICATIONS</span>
            <h2 class="section__title"><?= e(__('vehicles.title')) ?></h2>
            <p class="section__subtitle"><?= e(__('vehicles.subtitle')) ?></p>
        </div>

        <div class="vehicle-grid">
            <!-- Striker -->
            <div class="vehicle-card card--glow-cyan">
                <div class="vehicle-card__preview">
                    <span class="vehicle-card__badge" style="color: var(--cyan);"><?= e(__('vehicles.striker_role')) ?></span>
                    <svg width="120" height="120" viewBox="0 0 100 100" style="filter: drop-shadow(0 0 15px var(--cyan));">
                        <polygon points="50,15 85,80 50,65 15,80" fill="#141a29" stroke="var(--cyan)" stroke-width="2" />
                        <circle cx="50" cy="55" r="8" fill="var(--cyan)" />
                    </svg>
                </div>
                <div class="vehicle-card__body">
                    <h3 style="font-size: 1.5rem;"><?= e(__('vehicles.striker_name')) ?></h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.5rem 0 1.25rem;"><?= e(__('vehicles.striker_desc')) ?></p>
                    
                    <div class="stat-bars">
                        <div class="stat-row">
                            <div class="stat-row__header"><span><?= e(__('vehicles.speed')) ?></span><span>1.35x</span></div>
                            <div class="progress-track"><div class="progress-fill" style="width: 90%; background: var(--cyan);"></div></div>
                        </div>
                        <div class="stat-row">
                            <div class="stat-row__header"><span><?= e(__('vehicles.armor')) ?></span><span>80 HP</span></div>
                            <div class="progress-track"><div class="progress-fill" style="width: 50%; background: var(--cyan);"></div></div>
                        </div>
                        <div class="stat-row">
                            <div class="stat-row__header"><span><?= e(__('vehicles.dash')) ?></span><span>1.2s</span></div>
                            <div class="progress-track"><div class="progress-fill" style="width: 85%; background: var(--cyan);"></div></div>
                        </div>
                    </div>

                    <div style="margin-top: auto; padding-top: 1rem;">
                        <span class="eyebrow" style="font-size: 0.7rem; color: var(--text-muted);"><?= e(__('vehicles.special')) ?>: Overdrive</span>
                        <a href="/play?vehicle=striker" class="btn btn--primary" style="width: 100%; margin-top: 0.5rem;"><?= e(__('vehicles.select_chassis')) ?></a>
                    </div>
                </div>
            </div>

            <!-- Titan -->
            <div class="vehicle-card card--glow-crimson">
                <div class="vehicle-card__preview">
                    <span class="vehicle-card__badge" style="color: var(--crimson);"><?= e(__('vehicles.titan_role')) ?></span>
                    <svg width="120" height="120" viewBox="0 0 100 100" style="filter: drop-shadow(0 0 15px var(--crimson));">
                        <rect x="25" y="20" width="50" height="60" rx="6" fill="#1a1e28" stroke="var(--crimson)" stroke-width="2" />
                        <rect x="15" y="30" width="10" height="40" fill="var(--crimson)" />
                        <rect x="75" y="30" width="10" height="40" fill="var(--crimson)" />
                    </svg>
                </div>
                <div class="vehicle-card__body">
                    <h3 style="font-size: 1.5rem;"><?= e(__('vehicles.titan_name')) ?></h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.5rem 0 1.25rem;"><?= e(__('vehicles.titan_desc')) ?></p>
                    
                    <div class="stat-bars">
                        <div class="stat-row">
                            <div class="stat-row__header"><span><?= e(__('vehicles.speed')) ?></span><span>0.85x</span></div>
                            <div class="progress-track"><div class="progress-fill" style="width: 55%; background: var(--crimson);"></div></div>
                        </div>
                        <div class="stat-row">
                            <div class="stat-row__header"><span><?= e(__('vehicles.armor')) ?></span><span>160 HP</span></div>
                            <div class="progress-track"><div class="progress-fill" style="width: 100%; background: var(--crimson);"></div></div>
                        </div>
                        <div class="stat-row">
                            <div class="stat-row__header"><span><?= e(__('vehicles.dash')) ?></span><span>2.2s</span></div>
                            <div class="progress-track"><div class="progress-fill" style="width: 45%; background: var(--crimson);"></div></div>
                        </div>
                    </div>

                    <div style="margin-top: auto; padding-top: 1rem;">
                        <span class="eyebrow" style="font-size: 0.7rem; color: var(--text-muted);"><?= e(__('vehicles.special')) ?>: Kinetic Dome</span>
                        <a href="/play?vehicle=titan" class="btn btn--danger" style="width: 100%; margin-top: 0.5rem;"><?= e(__('vehicles.select_chassis')) ?></a>
                    </div>
                </div>
            </div>

            <!-- Phantom -->
            <div class="vehicle-card">
                <div class="vehicle-card__preview">
                    <span class="vehicle-card__badge" style="color: var(--purple);"><?= e(__('vehicles.phantom_role')) ?></span>
                    <svg width="120" height="120" viewBox="0 0 100 100" style="filter: drop-shadow(0 0 15px var(--purple));">
                        <polygon points="35,15 45,75 30,85" fill="#121420" stroke="var(--purple)" stroke-width="2" />
                        <polygon points="65,15 55,75 70,85" fill="#121420" stroke="var(--purple)" stroke-width="2" />
                        <polygon points="50,40 58,55 50,70 42,55" fill="var(--purple)" />
                    </svg>
                </div>
                <div class="vehicle-card__body">
                    <h3 style="font-size: 1.5rem;"><?= e(__('vehicles.phantom_name')) ?></h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.5rem 0 1.25rem;"><?= e(__('vehicles.phantom_desc')) ?></p>
                    
                    <div class="stat-bars">
                        <div class="stat-row">
                            <div class="stat-row__header"><span><?= e(__('vehicles.speed')) ?></span><span>1.15x</span></div>
                            <div class="progress-track"><div class="progress-fill" style="width: 75%; background: var(--purple);"></div></div>
                        </div>
                        <div class="stat-row">
                            <div class="stat-row__header"><span><?= e(__('vehicles.armor')) ?></span><span>100 HP</span></div>
                            <div class="progress-track"><div class="progress-fill" style="width: 65%; background: var(--purple);"></div></div>
                        </div>
                        <div class="stat-row">
                            <div class="stat-row__header"><span><?= e(__('vehicles.dash')) ?></span><span>0.9s</span></div>
                            <div class="progress-track"><div class="progress-fill" style="width: 95%; background: var(--purple);"></div></div>
                        </div>
                    </div>

                    <div style="margin-top: auto; padding-top: 1rem;">
                        <span class="eyebrow" style="font-size: 0.7rem; color: var(--text-muted);"><?= e(__('vehicles.special')) ?>: Phase Shift</span>
                        <a href="/play?vehicle=phantom" class="btn btn--outline" style="width: 100%; margin-top: 0.5rem; border-color: var(--purple); color: #fff;"><?= e(__('vehicles.select_chassis')) ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Combat Sectors / Arenas -->
<section class="section" id="arenas">
    <div class="container">
        <div class="section__header">
            <span class="eyebrow">ENVIRONMENTAL SURVEILLANCE</span>
            <h2 class="section__title"><?= e(__('arenas.title')) ?></h2>
            <p class="section__subtitle"><?= e(__('arenas.subtitle')) ?></p>
        </div>

        <div class="arena-grid">
            <div class="arena-card card--glow-cyan">
                <div class="arena-card__sector"><?= e(__('arenas.neon_core_sub')) ?></div>
                <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;"><?= e(__('arenas.neon_core')) ?></h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;"><?= e(__('arenas.neon_core_desc')) ?></p>
                <div>
                    <span class="hazard-badge">Laser Barriers</span>
                    <span class="hazard-badge">Energy Pillars</span>
                </div>
            </div>

            <div class="arena-card" style="border-color: rgba(191, 0, 255, 0.3);">
                <div class="arena-card__sector" style="color: var(--purple);"><?= e(__('arenas.orbital_station_sub')) ?></div>
                <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;"><?= e(__('arenas.orbital_station')) ?></h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;"><?= e(__('arenas.orbital_station_desc')) ?></p>
                <div>
                    <span class="hazard-badge">Repulsion Wells</span>
                    <span class="hazard-badge">Asteroids</span>
                </div>
            </div>

            <div class="arena-card card--glow-crimson">
                <div class="arena-card__sector" style="color: var(--crimson);"><?= e(__('arenas.magma_foundry_sub')) ?></div>
                <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;"><?= e(__('arenas.magma_foundry')) ?></h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;"><?= e(__('arenas.magma_foundry_desc')) ?></p>
                <div>
                    <span class="hazard-badge">Lava Trenches</span>
                    <span class="hazard-badge">Coolant Columns</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Global Leaderboard Preview -->
<section class="section" id="leaderboard" style="background: rgba(6, 9, 18, 0.6);">
    <div class="container">
        <div class="section__header">
            <span class="eyebrow">AUDITED COMBAT STANDINGS</span>
            <h2 class="section__title"><?= e(__('leaderboard.title')) ?></h2>
            <p class="section__subtitle"><?= e(__('leaderboard.subtitle')) ?></p>
        </div>

        <div class="table-wrap">
            <table class="table" aria-label="Top Pilots Leaderboard">
                <thead>
                    <tr>
                        <th style="width: 80px;"><?= e(__('leaderboard.rank')) ?></th>
                        <th><?= e(__('leaderboard.pilot')) ?></th>
                        <th><?= e(__('leaderboard.score')) ?></th>
                        <th><?= e(__('leaderboard.vehicle')) ?></th>
                        <th><?= e(__('leaderboard.arena')) ?></th>
                        <th><?= e(__('leaderboard.waves')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_players)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;"><?= e(__('leaderboard.no_entries')) ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($top_players as $i => $row): ?>
                            <tr>
                                <td>
                                    <span class="rank-pill <?= $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : '')) ?>">
                                        <?= $i + 1 ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                                        <span class="font-display font-bold" style="color: #fff;"><?= e($row['display_name']) ?></span>
                                        <span class="eyebrow font-mono" style="margin: 0; font-size: 0.65rem;">LVL <?= (int) $row['user_level'] ?></span>
                                    </div>
                                </td>
                                <td><span class="font-display font-bold" style="color: var(--cyan); font-size: 1.15rem;"><?= number_format((int) $row['score']) ?></span></td>
                                <td><span class="mono" style="text-transform: capitalize;"><?= e($row['vehicle_class']) ?></span></td>
                                <td><span class="mono" style="text-transform: capitalize;"><?= str_replace('_', ' ', e($row['arena_id'])) ?></span></td>
                                <td><span class="font-mono"><?= (int) $row['waves_cleared'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="/leaderboard" class="btn btn--outline"><?= e(__('leaderboard.global_tab')) ?> Full Matrix →</a>
        </div>
    </div>
</section>

<!-- Active Challenges Feed -->
<section class="section" id="challenges">
    <div class="container">
        <div class="section__header">
            <span class="eyebrow">BROADCAST TRANSMISSIONS</span>
            <h2 class="section__title"><?= e(__('challenges.title')) ?></h2>
            <p class="section__subtitle"><?= e(__('challenges.subtitle')) ?></p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($recent_challenges as $chal): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span class="eyebrow font-mono" style="margin: 0;"><?= e($chal['creator_username']) ?></span>
                        <span class="mono" style="font-size: 0.75rem; color: var(--text-dim);"><?= (int) $chal['challenger_count'] ?> rivals</span>
                    </div>
                    <div class="font-display" style="font-size: 1.8rem; font-weight: 800; color: var(--cyan); margin-bottom: 0.25rem;">
                        <?= number_format((int) $chal['target_score']) ?> <span style="font-size: 0.9rem; color: var(--text-muted);">PTS</span>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                        Sector: <?= str_replace('_', ' ', e($chal['arena_id'])) ?> // Chassis: <?= e($chal['vehicle_class']) ?>
                    </div>
                    <a href="/challenge/<?= e($chal['id']) ?>" class="btn btn--outline btn--sm" style="width: 100%;"><?= e(__('challenges.accept_challenge')) ?></a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="section" id="faq" style="background: rgba(6, 9, 18, 0.6);">
    <div class="container" style="max-width: 800px;">
        <div class="section__header">
            <span class="eyebrow">SYSTEM ARCHITECTURE</span>
            <h2 class="section__title">Frequently Asked Questions</h2>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div class="card">
                <h4 style="color: var(--cyan); margin-bottom: 0.5rem;">Is VOIDSTRIKE really built without Laravel or Symfony?</h4>
                <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;">
                    Yes. The backend is 100% pure PHP 8.5+ utilizing a custom modular-monolith router, custom middleware pipeline, native PDO with prepared statements, and zero heavy third-party web frameworks.
                </p>
            </div>
            <div class="card">
                <h4 style="color: var(--cyan); margin-bottom: 0.5rem;">How does server-side Anti-Cheat prevent score spoofing?</h4>
                <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;">
                    Matches require pre-authorized cryptographic run tokens generated with server nonces. Submissions are audited against theoretical score rate ceilings, wave limits, monotonic telemetry sequences, and clock drift tolerances.
                </p>
            </div>
            <div class="card">
                <h4 style="color: var(--cyan); margin-bottom: 0.5rem;">Can I play on mobile devices?</h4>
                <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;">
                    Absolutely. The Three.js 3D engine features dedicated virtual on-screen analog sticks, multi-touch action triggers, viewport safe-area padding, and touch-action constraints preventing accidental scrolling.
                </p>
            </div>
        </div>
    </div>
</section>

<?php \App\Core\View::startSection('scripts'); ?>
<script type="module">
    import * as THREE from '/assets/vendor/three/three.module.js';

    // Interactive 3D Hero Background Showroom
    const canvas = document.getElementById('hero-canvas');
    if (canvas) {
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(45, canvas.clientWidth / canvas.clientHeight, 0.1, 100);
        camera.position.set(0, 4, 14);

        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
        renderer.setSize(canvas.clientWidth, canvas.clientHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

        // Lighting
        const light = new THREE.DirectionalLight(0x00f0ff, 2.5);
        light.position.set(10, 20, 10);
        scene.add(light);

        const fillLight = new THREE.DirectionalLight(0xff2a5f, 1.5);
        fillLight.position.set(-10, -10, -10);
        scene.add(fillLight);

        // Showcase Vehicle Group
        const group = new THREE.Group();

        // Sleek Arrowhead Showroom Vehicle
        const bodyMat = new THREE.MeshStandardMaterial({ color: 0x0e1422, metalness: 0.9, roughness: 0.2 });
        const glowMat = new THREE.MeshBasicMaterial({ color: 0x00f0ff });

        const fuse = new THREE.Mesh(new THREE.ConeGeometry(1.2, 5.5, 4), bodyMat);
        fuse.rotateX(Math.PI / 2);
        fuse.scale.set(1.1, 0.5, 1);
        group.add(fuse);

        const wing = new THREE.Mesh(new THREE.BoxGeometry(5.2, 0.1, 2.2), bodyMat);
        wing.position.set(0, 0.1, -0.6);
        group.add(wing);

        const thruster = new THREE.Mesh(new THREE.CylinderGeometry(0.3, 0.5, 1.0, 8), glowMat);
        thruster.rotateX(Math.PI / 2);
        thruster.position.set(0, 0, -2.5);
        group.add(thruster);

        // Surrounding Cyber Rings
        const ring1 = new THREE.Mesh(new THREE.TorusGeometry(5.2, 0.04, 8, 64), glowMat);
        ring1.rotateX(Math.PI / 3);
        group.add(ring1);

        scene.add(group);
        group.position.set(3.5, 0, 0); // Position to right side on desktop

        let mouseX = 0;
        let mouseY = 0;
        window.addEventListener('mousemove', (e) => {
            mouseX = (e.clientX / window.innerWidth) * 2 - 1;
            mouseY = -(e.clientY / window.innerHeight) * 2 + 1;
        });

        const animate = () => {
            requestAnimationFrame(animate);
            group.rotation.y += 0.008;
            group.rotation.x = mouseY * 0.2;
            group.position.y = Math.sin(performance.now() * 0.002) * 0.3;
            renderer.render(scene, camera);
        };
        animate();

        window.addEventListener('resize', () => {
            if (!canvas.parentElement) return;
            const w = canvas.parentElement.clientWidth;
            const h = canvas.parentElement.clientHeight;
            camera.aspect = w / h;
            camera.updateProjectionMatrix();
            renderer.setSize(w, h);
        });
    }
</script>
<?php \App\Core\View::endSection(); ?>
