<!DOCTYPE html>
<html lang="<?= e(\App\Localization\Translator::getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="theme-color" content="#000000">
    <title><?= e($title ?? 'VOIDSTRIKE ARENA') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body, html {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #000;
            user-select: none;
            -webkit-user-select: none;
            touch-action: none;
        }
    </style>
</head>
<body>
    <?= $slot ?>

    <script src="/assets/js/app.js"></script>
    <?= \App\Core\View::yieldSection('scripts') ?>
</body>
</html>
