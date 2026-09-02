<!DOCTYPE html>
<html lang="<?= e(\App\Localization\Translator::getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="description" content="<?= e(__('app.tagline')) ?>">
    <meta name="theme-color" content="#00f0ff">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/assets/icons/icon-192.svg">
    <title><?= e($title ?? config('app.name')) ?></title>
    
    <link rel="stylesheet" href="/assets/css/style.css">
    <?= \App\Core\View::yieldSection('head') ?>
</head>
<body>
    <div class="cyber-overlay" aria-hidden="true"></div>

    <!-- Navigation Header -->
    <header class="header">
        <div class="container header__inner">
            <a href="/" class="brand" aria-label="VOIDSTRIKE ARENA">
                <svg class="brand__logo-icon" viewBox="0 0 100 100">
                    <polygon points="50,10 90,85 50,70 10,85" />
                </svg>
                <span>VOIDSTRIKE</span>
            </a>

            <nav>
                <ul class="nav__links" id="nav-menu">
                    <li><a href="/"><?= e(__('nav.home')) ?></a></li>
                    <li><a href="/play" class="text-cyan"><?= e(__('nav.play')) ?></a></li>
                    <li><a href="/leaderboard"><?= e(__('nav.leaderboard')) ?></a></li>
                    <li><a href="/challenges"><?= e(__('nav.challenges')) ?></a></li>
                    <li><a href="/achievements"><?= e(__('nav.achievements')) ?></a></li>
                    <?php if (auth()): ?>
                        <li><a href="/dashboard"><?= e(__('nav.dashboard')) ?></a></li>
                        <?php if (auth()->isAdmin()): ?>
                            <li><a href="/admin" style="color: var(--amber);"><?= e(__('nav.admin')) ?></a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="header__actions">
                <!-- Language Selector -->
                <div class="lang-switch" role="group" aria-label="Language Selector">
                    <a href="?lang=en" class="lang-btn <?= \App\Localization\Translator::getLocale() === 'en' ? 'active' : '' ?>">EN</a>
                    <a href="?lang=fr" class="lang-btn <?= \App\Localization\Translator::getLocale() === 'fr' ? 'active' : '' ?>">FR</a>
                </div>

                <?php if (auth()): ?>
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <a href="/dashboard" style="display:flex; align-items:center; gap:0.5rem;">
                            <img src="<?= e(auth()->getAvatarUrl()) ?>" alt="Avatar" style="width:32px; height:32px; border-radius:50%; border:1px solid var(--cyan);">
                            <span class="font-display font-bold" style="font-size:0.9rem;"><?= e(auth()->display_name) ?></span>
                        </a>
                        <form method="POST" action="/logout" style="display:inline;">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn--outline btn--sm"><?= e(__('nav.logout')) ?></button>
                        </form>
                    </div>
                <?php else: ?>
                    <a href="/login" class="btn btn--outline btn--sm"><?= e(__('nav.login')) ?></a>
                    <a href="/register" class="btn btn--primary btn--sm"><?= e(__('nav.register')) ?></a>
                <?php endif; ?>

                <button class="menu-toggle" aria-expanded="false" aria-controls="nav-menu" aria-label="Toggle menu">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <div class="flash-container">
        <?php if (session()->hasFlash('success')): ?>
            <div class="alert alert--success" role="alert">
                <span><?= e(session()->getFlash('success')) ?></span>
                <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;">✕</button>
            </div>
        <?php endif; ?>
        <?php if (session()->hasFlash('error')): ?>
            <div class="alert alert--error" role="alert">
                <span><?= e(session()->getFlash('error')) ?></span>
                <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;">✕</button>
            </div>
        <?php endif; ?>
        <?php if (session()->hasFlash('info')): ?>
            <div class="alert alert--info" role="alert">
                <span><?= e(session()->getFlash('info')) ?></span>
                <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;">✕</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <main id="main-content">
        <?= $slot ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer__grid">
                <div class="footer__brand">
                    <div class="brand">
                        <svg class="brand__logo-icon" viewBox="0 0 100 100"><polygon points="50,10 90,85 50,70 10,85" /></svg>
                        <span>VOIDSTRIKE ARENA</span>
                    </div>
                    <p><?= e(__('app.tagline')) ?></p>
                    <div style="margin-top:1rem;">
                        <span class="eyebrow" style="font-size:0.7rem; color:var(--text-dim);"><?= e(__('app.season')) ?></span>
                    </div>
                </div>

                <div>
                    <h4 class="footer__title"><?= e(__('nav.modes')) ?></h4>
                    <ul class="footer__links">
                        <li><a href="/play"><?= e(__('modes.quick_title')) ?></a></li>
                        <li><a href="/challenges"><?= e(__('modes.challenge_title')) ?></a></li>
                        <li><a href="/leaderboard"><?= e(__('modes.rival_title')) ?></a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="footer__title"><?= e(__('nav.leaderboard')) ?></h4>
                    <ul class="footer__links">
                        <li><a href="/leaderboard"><?= e(__('leaderboard.global_tab')) ?></a></li>
                        <li><a href="/leaderboard/weekly"><?= e(__('leaderboard.weekly_tab')) ?></a></li>
                        <li><a href="/achievements"><?= e(__('nav.achievements')) ?></a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="footer__title"><?= e(__('nav.dashboard')) ?></h4>
                    <ul class="footer__links">
                        <?php if (auth()): ?>
                            <li><a href="/dashboard"><?= e(__('dashboard.title')) ?></a></li>
                            <li><a href="/settings"><?= e(__('dashboard.settings_title')) ?></a></li>
                            <li><a href="/notifications"><?= e(__('nav.dashboard')) ?> Alerts</a></li>
                        <?php else: ?>
                            <li><a href="/login"><?= e(__('nav.login')) ?></a></li>
                            <li><a href="/register"><?= e(__('nav.register')) ?></a></li>
                            <li><a href="/forgot-password"><?= e(__('auth.forgot_password')) ?></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="footer__bottom">
                <span>© <?= date('Y') ?> VOIDSTRIKE ARENA. Pure PHP 8.5 • PostgreSQL 17 • Three.js</span>
                <span>Deterministic Anti-Cheat Verified</span>
            </div>
        </div>
    </footer>

    <script src="/assets/js/app.js"></script>
    <?= \App\Core\View::yieldSection('scripts') ?>
</body>
</html>
