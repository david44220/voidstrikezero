<div class="container" style="max-width: 600px; text-align: center; padding-top: 6rem; padding-bottom: 6rem;">
    <div class="card card--glow-crimson" style="padding: 3rem 2rem;">
        <?php $errCode = (int) ($status ?? $code ?? 500); ?>
        <span class="eyebrow" style="color: var(--crimson);">ANOMALY DETECTED // HTTP <?= $errCode ?></span>
        <h1 style="font-size: 3.5rem; margin: 0.5rem 0 1rem; color: #fff;"><?= $errCode ?></h1>
        <p style="color: var(--text-muted); font-size: 1.1rem; line-height: 1.6; margin-bottom: 2rem;">
            <?= e($message ?? 'An unexpected system anomaly has disrupted defense communication.') ?>
        </p>
        <div>
            <a href="/" class="btn btn--primary">Return To Defense Grid</a>
        </div>
    </div>
</div>
