<!DOCTYPE html>
<html lang="<?= e(\App\Localization\Translator::getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'Admin Nexus') ?> // VOIDSTRIKE</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .admin-layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            min-height: 100vh;
        }
        .admin-sidebar {
            background: #04060a;
            border-right: 1px solid var(--border-subtle);
            padding: 2rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .admin-nav {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .admin-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-sm);
            color: var(--text-muted);
            font-family: var(--font-display);
            font-size: 0.9rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .admin-nav a:hover, .admin-nav a.active {
            background: rgba(0, 240, 255, 0.1);
            color: var(--cyan);
            border-left: 3px solid var(--cyan);
        }
        .admin-main {
            padding: 2.5rem;
            overflow-y: auto;
        }
        @media (max-width: 768px) {
            .admin-layout { grid-template-columns: 1fr; }
            .admin-sidebar { padding: 1rem; }
            .admin-nav { flex-direction: row; flex-wrap: wrap; }
            .admin-main { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="cyber-overlay" aria-hidden="true"></div>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div>
                <a href="/admin" class="brand" style="font-size: 1.15rem;">
                    <span style="color: var(--amber);">⚡ NEXUS</span>
                </a>
                <div class="eyebrow" style="font-size: 0.65rem; color: var(--text-dim); margin-top: 0.25rem;">ADMINISTRATION // SEC-0</div>
            </div>

            <nav>
                <ul class="admin-nav">
                    <li><a href="/admin"><?= e(__('admin.overview')) ?></a></li>
                    <li><a href="/admin/users"><?= e(__('admin.manage_users')) ?></a></li>
                    <li><a href="/admin/matches"><?= e(__('admin.manage_matches')) ?></a></li>
                    <li><a href="/admin/challenges"><?= e(__('nav.challenges')) ?></a></li>
                    <li><a href="/admin/audit"><?= e(__('admin.audit_logs')) ?></a></li>
                    <li><a href="/admin/settings"><?= e(__('admin.settings_title')) ?></a></li>
                    <li style="margin-top: 2rem;"><a href="/" style="color: var(--text-dim);">← <?= e(__('admin.exit_platform')) ?></a></li>
                </ul>
            </nav>

            <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.05);">
                <div style="font-size: 0.75rem; color: var(--text-muted);">Logged in as:</div>
                <div class="font-display font-bold" style="color: var(--amber);"><?= e(auth()?->username) ?></div>
            </div>
        </aside>

        <main class="admin-main">
            <!-- Flash Messages -->
            <?php if (session()->hasFlash('success')): ?>
                <div class="alert alert--success" style="margin-bottom: 1.5rem;" role="alert">
                    <span><?= e(session()->getFlash('success')) ?></span>
                </div>
            <?php endif; ?>
            <?php if (session()->hasFlash('error')): ?>
                <div class="alert alert--error" style="margin-bottom: 1.5rem;" role="alert">
                    <span><?= e(session()->getFlash('error')) ?></span>
                </div>
            <?php endif; ?>

            <?= $slot ?>
        </main>
    </div>

    <script src="/assets/js/app.js"></script>
</body>
</html>
