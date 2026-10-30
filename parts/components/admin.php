<section class="sp-6">

    <h1 class="fs-10">Admin</h1>

    <?php
    $st_date      = st_next_date();
    $st_attendees = st_attendees($st_date);
    $st_manual    = st_is_manual_venue($st_date);
    $st_venue_id  = st_get_venue($st_date);
    $st_suggest   = st_pick_venue($st_attendees);
    $st_all_bars  = get_posts(['post_type' => 'bar', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    ?>

    <form class="card card--admin sp-4" method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
        <h2 class="fs-4">Einladung</h2>
        <input type="hidden" name="action" value="st_send_invites">
        <?php wp_nonce_field('st_send_invites') ?>
        <p>Schickt allen jetzt die Ja/Nein-Mail für <?= esc_html(st_format_date($st_date)) ?>.</p>
        <button class="cta" type="submit" data-confirm="Einladung jetzt an alle senden?">Einladung senden</button>
        <p class="hint">Automatisch: am Termin um <?= esc_html(st_setting('invite_time')) ?> Uhr. Am Termin selbst ersetzt der Knopf die automatische Mail.</p>
    </form>

    <form class="card card--admin sp-4" method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
        <h2 class="fs-4">Ort festlegen</h2>
        <input type="hidden" name="action" value="st_set_venue">
        <?php wp_nonce_field('st_set_venue') ?>

        <p>
            <?php if ($st_manual && $st_venue_id) : ?>
                Festgelegt: <strong><?= esc_html(get_the_title($st_venue_id)) ?></strong>
            <?php elseif ($st_venue_id) : ?>
                Berechnet und verschickt: <strong><?= esc_html(get_the_title($st_venue_id)) ?></strong>
            <?php else : ?>
                Automatisch um <?= esc_html(st_setting('announce_time')) ?> Uhr<?= $st_suggest ? ', Stand jetzt ungefähr: <strong>' . esc_html(get_the_title($st_suggest)) . '</strong>' : '' ?>
            <?php endif; ?>
            · <?= count($st_attendees) ?> Zusagen
        </p>

        <label class="field">
            <span class="visually-hidden">Bar</span>
            <select name="bar_id">
                <option value="0" <?php selected(!$st_manual) ?>>Automatisch berechnen</option>
                <?php foreach ($st_all_bars as $st_option) : ?>
                    <option value="<?= (int) $st_option->ID ?>" <?php selected($st_manual && $st_venue_id === $st_option->ID) ?>><?= esc_html($st_option->post_title) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="row sp-2 wrap center">
            <button class="cta" type="submit" name="send_now" value="">Festlegen</button>
            <button class="cta" type="submit" name="send_now" value="1" data-confirm="Ort festlegen und jetzt an alle Zusagen senden?">Festlegen + jetzt senden</button>
        </div>
        <p class="hint">„Festlegen“: Die Mail geht um <?= esc_html(st_setting('announce_time')) ?> Uhr mit dieser Bar raus. „Jetzt senden“: Wer später noch zusagt, bekommt die Mail um <?= esc_html(st_setting('announce_time')) ?> Uhr nachgeschickt.</p>
    </form>

    <?php if ($st_manual) : ?>
        <form class="card card--admin sp-4" method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
            <h2 class="fs-4">Zurücksetzen</h2>
            <input type="hidden" name="action" value="st_set_venue">
            <input type="hidden" name="bar_id" value="0">
            <?php wp_nonce_field('st_set_venue') ?>
            <p>Hebt die Festlegung auf <strong><?= esc_html(get_the_title($st_venue_id)) ?></strong> auf. Der Ort wird dann wieder um <?= esc_html(st_setting('announce_time')) ?> Uhr automatisch berechnet.</p>
            <button class="cta" type="submit" data-confirm="Festgelegten Ort zurücksetzen?">Zurücksetzen</button>
        </form>
    <?php endif; ?>

    <form class="card card--admin sp-4" method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
        <h2 class="fs-4">Einstellungen</h2>
        <input type="hidden" name="action" value="st_save_settings">
        <?php wp_nonce_field('st_save_settings') ?>

        <?php foreach (st_setting_fields() as $st_key => $st_field) : ?>
            <?php if (isset($st_field['group'])) : ?>
                <h3 class="fs-3"><?= esc_html($st_field['group']) ?></h3>
            <?php endif; ?>
            <label class="field">
                <span><?= esc_html($st_field['label']) ?><?= isset($st_field['unit']) ? ' (' . esc_html($st_field['unit']) . ')' : '' ?></span>
                <?php st_setting_input($st_key, $st_field) ?>
                <span class="hint"><?= esc_html($st_field['hint']) ?></span>
            </label>
        <?php endforeach; ?>

        <p class="hint">Nächste Termine: <?= esc_html(implode(' · ', array_map('st_format_date', st_upcoming_dates(4)))) ?></p>

        <button class="cta cta--primary" type="submit">Speichern</button>
    </form>

</section>
