<main id="main" class="main main--login sp-8">

    <header class="sp-2">
        <h1 class="fs-10"><?= esc_html(get_bloginfo('name')) ?></h1>
        <p class="fs-4"><?= esc_html(st_rhythm_label()) ?>, in der Bar mit Bestlage.</p>
    </header>

    <?php if ($error) : ?>
        <p class="notice notice--toast notice--error" data-toast role="alert">Falsches Passwort.</p>
    <?php endif; ?>

    <?php // e.g. "Super, du bist dabei!" after a Ja/Nein link from the mail
    include THEME_DIR . "parts/components/notice.php" ?>

    <form class="card sp-4" method="post">
        <h2>Passwort</h2>

        <label class="field">
            <span class="visually-hidden">Passwort</span>
            <input type="password" name="st_gate_password" autocomplete="current-password" required autofocus placeholder="Passwort">
        </label>

        <button class="cta cta--primary" type="submit">Weiter</button>
    </form>

    <p class="nav__meta"><?php foreach (st_legal_views() as $st_legal => $st_label) : ?>
        <a class="cta--logout" href="<?= esc_url(add_query_arg('view', $st_legal, home_url('/'))) ?>"><?= esc_html($st_label) ?></a>
    <?php endforeach; ?></p>
</main>