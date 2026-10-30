<?php
$st_user = wp_get_current_user();
$st_home = st_home_address_parts($st_user->ID);
?>

<section class="sp-6">

    <h1 class="fs-10">Profil</h1>

    <form class="card sp-4" method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
        <input type="hidden" name="action" value="st_profile">
        <?php wp_nonce_field('st_profile') ?>

        <label class="field">
            <span class="visually-hidden">Benutzername</span>
            <input type="text" value="<?= esc_attr($st_user->user_login) ?>" placeholder="Benutzername" disabled>
        </label>
        <label class="field">
            <span class="visually-hidden">E-Mail</span>
            <input type="email" name="email" value="<?= esc_attr($st_user->user_email) ?>" autocomplete="email" required placeholder="E-Mail">
        </label>
        <label class="field">
            <span class="visually-hidden">Straße</span>
            <input type="text" name="street" value="<?= esc_attr($st_home['street']) ?>" autocomplete="off" required placeholder="Straße (ohne Hausnummer)">
        </label>
        <div class="field-row">
            <label class="field">
                <span class="visually-hidden">PLZ</span>
                <input type="text" name="zip" value="<?= esc_attr($st_home['zip']) ?>" autocomplete="postal-code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" required placeholder="PLZ">
            </label>
            <label class="field">
                <span class="visually-hidden">Ort</span>
                <input type="text" name="city" value="<?= esc_attr($st_home['city']) ?>" autocomplete="address-level2" required placeholder="Ort">
            </label>
        </div>
        <p class="hint">Ohne Hausnummer, die brauchen wir nicht.</p>
        <label class="field">
            <span class="visually-hidden">Neues Passwort</span>
            <input type="password" name="password" autocomplete="new-password" minlength="8" placeholder="Neues Passwort (leer lassen, um es zu behalten)">
        </label>

        <button class="cta cta--primary" type="submit">Speichern</button>
    </form>

    <?php if (!current_user_can('manage_options')) : ?>
        <form class="card card--danger sp-4" method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
            <h2>Konto löschen</h2>
            <p>Du bekommst danach keine Stammtisch-Mails mehr. Deine eingetragenen Bars bleiben erhalten.</p>
            <input type="hidden" name="action" value="st_delete_account">
            <?php wp_nonce_field('st_delete_account') ?>
            <button class="cta cta--danger" type="submit" data-confirm="Konto wirklich löschen?">Konto löschen</button>
        </form>
    <?php endif; ?>

</section>