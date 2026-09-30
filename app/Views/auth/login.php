<div class="auth-card">
    <div class="text-center mb-4">
        <img src="<?= asset('assets/img/logo.svg') ?>" alt="" width="64" height="64" class="mb-3">
        <h1 class="h3 fw-bold mb-0 brand-title"><?= e(APP_NAME) ?></h1>
        <p class="text-body-secondary mb-0"><?= e(APP_TAGLINE) ?></p>
    </div>

    <?= partial('flash') ?>

    <form method="post" action="<?= url('/login') ?>" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control<?= invalid('email') ?>" type="email" id="email" name="email"
                   value="<?= e(old('email')) ?>" autocomplete="username" inputmode="email" autocapitalize="off" required autofocus>
            <?= field_error('email') ?>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Contraseña</label>
            <div class="input-group">
                <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
                <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="Mostrar contraseña">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <div class="form-check form-switch mb-4">
            <input class="form-check-input" type="checkbox" role="switch" id="remember" name="remember" value="1"
                <?= !has_old() || old('remember') ? 'checked' : '' ?>>
            <label class="form-check-label" for="remember">Mantener sesión iniciada (<?= REMEMBER_DAYS ?> días)</label>
        </div>

        <button class="btn btn-primary btn-xl w-100" type="submit">
            Ingresar <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </form>
</div>
