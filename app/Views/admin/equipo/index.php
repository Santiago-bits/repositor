<div class="page-head">
    <h1>Equipo</h1>
</div>

<?= partial('equipo-periodo', ['dias' => $dias, 'periodos' => $periodos, 'base' => '/admin/equipo']) ?>

<?php
$grupos = ['repositor' => 'Repositores', 'vendedor' => 'Vendedores', 'admin' => 'Administradores'];
foreach ($grupos as $slug => $titulo):
    $lista = array_filter($miembros, fn ($m) => $m['role_slug'] === $slug);
    if ($lista === []) {
        continue;
    }
?>
    <h2 class="section-title"><?= $titulo ?></h2>
    <div class="item-list">
        <?php foreach ($lista as $m): ?>
            <?php $activoHoy = $m['last_seen_at'] && date('Y-m-d', strtotime($m['last_seen_at'])) === date('Y-m-d'); ?>
            <a class="item-card" href="<?= url("/admin/equipo/{$m['id']}?dias={$dias}") ?>">
                <div class="item-avatar"><?= e(initials($m)) ?></div>
                <div class="item-body">
                    <div class="item-title"><?= e($m['nombre'] . ' ' . $m['apellido']) ?></div>
                    <div class="item-sub">
                        <span class="equipo-punto<?= $activoHoy ? ' is-on' : '' ?>"></span>
                        Activo <?= e(hace($m['last_seen_at'])) ?>
                    </div>
                    <?php if ($slug !== 'vendedor'): ?>
                        <div class="item-meta">
                            <span><i class="bi bi-geo-alt"></i> <?= (int) $m['visitas'] ?> visita<?= (int) $m['visitas'] === 1 ? '' : 's' ?></span>
                            <span><i class="bi bi-cart-x"></i> <?= (int) $m['faltantes'] ?></span>
                            <span><i class="bi bi-calendar-x"></i> <?= (int) $m['vencimientos'] ?></span>
                            <span><i class="bi bi-camera"></i> <?= (int) $m['fotos'] ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <i class="bi bi-chevron-right text-body-secondary"></i>
            </a>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<p class="small text-body-secondary mt-3 mb-0">
    <i class="bi bi-geo-alt"></i> visitas · <i class="bi bi-cart-x"></i> faltantes anotados · <i class="bi bi-calendar-x"></i> vencimientos cargados · <i class="bi bi-camera"></i> fotos.
    «Activo» es la última vez que abrió la app.
</p>
