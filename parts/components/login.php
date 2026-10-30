<main id="main" class="main main--login sp-8">

    <header class="sp-2">
        <h1 class="fs-10"><?= esc_html(get_bloginfo('name')) ?></h1>
        <p class="fs-4"><?= esc_html(st_rhythm_label()) ?>, in der Bar mit Bestlage.</p>
    </header>

    <?php include THEME_DIR . "parts/components/notice.php" ?>

    <div class="section--split">

        <form class="card sp-4" method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
            <h2>Anmelden</h2>
            <input type="hidden" name="action" value="st_login">
            <?php wp_nonce_field('st_login') ?>

            <label class="field">
                <span class="visually-hidden">Benutzername oder E-Mail</span>
                <input type="text" name="login" autocomplete="username" required placeholder="Benutzername oder E-Mail">
            </label>
            <label class="field">
                <span class="visually-hidden">Passwort</span>
                <input type="password" name="password" autocomplete="current-password" required placeholder="Passwort">
            </label>

            <button class="cta cta--primary" type="submit">Anmelden</button>
            <a class="fs-2" href="<?= esc_url(wp_lostpassword_url(home_url('/'))) ?>">Passwort vergessen?</a>
        </form>

        <form class="card sp-4" method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
            <h2>Registrieren</h2>
            <input type="hidden" name="action" value="st_signup">
            <?php wp_nonce_field('st_signup') ?>

            <label class="field">
                <span class="visually-hidden">Benutzername</span>
                <input type="text" name="username" autocomplete="username" required placeholder="Benutzername">
            </label>
            <label class="field">
                <span class="visually-hidden">E-Mail</span>
                <input type="email" name="email" autocomplete="email" required placeholder="E-Mail">
            </label>
            <label class="field">
                <span class="visually-hidden">Passwort</span>
                <input type="password" name="password" autocomplete="new-password" minlength="8" required placeholder="Passwort (mind. 8 Zeichen)">
            </label>
            <label class="field">
                <span class="visually-hidden">Deine Adresse</span>
                <input type="text" name="address" autocomplete="street-address" required placeholder="Deine Adresse (Straße, PLZ, Ort)">
            </label>
            <p class="hint">Deine Adresse wird nur genutzt, um den Treffpunkt zu berechnen. Andere sehen sie nicht.</p>

            <button class="cta cta--primary" type="submit">Registrieren</button>
        </form>

    </div>

    <p class="nav__meta"><?php foreach (st_legal_views() as $st_legal => $st_label) : ?>
        <a class="cta--logout" href="<?= esc_url(add_query_arg('view', $st_legal, home_url('/'))) ?>"><?= esc_html($st_label) ?></a>
    <?php endforeach; ?></p>
</main>