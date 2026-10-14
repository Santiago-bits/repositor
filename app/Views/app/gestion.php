<div class="page-head">
    <h1>Gestión</h1>
</div>

<div class="item-list">
    <?php foreach (nav_admin() as [$path, $icon, $label]): ?>
        <a class="item-card" href="<?= url($path) ?>">
            <div class="item-icon"><i class="bi <?= $icon ?>"></i></div>
            <div class="item-body"><div class="item-title"><?= $label ?></div></div>
            <i class="bi bi-chevron-right text-body-secondary"></i>
        </a>
    <?php endforeach; ?>
</div>
