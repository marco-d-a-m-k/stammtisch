<?php
$st_date      = st_next_date();
$st_user_id   = get_current_user_id();
$st_answer    = st_get_rsvp($st_user_id, $st_date);
$st_attendees = st_attendees($st_date);
$st_venue_id  = st_get_venue($st_date);
$st_has_loc   = (bool) st_get_location('user', $st_user_id);

// Before 16:00: rough preview by straight-line distance (no API calls on page views)
$st_preview_id = $st_venue_id ? 0 : st_pick_venue($st_attendees);
$st_bar_id     = $st_venue_id ?: $st_preview_id;
$st_bar        = $st_bar_id ? st_get_location('post', $st_bar_id) : null;
?>

<section class="sp-6">

    <header class="sp-2">
        <p class="fs-2 up">Nächster Stammtisch</p>
        <h1 class="fs-10"><?= esc_html(st_format_date($st_date)) ?></h1>
    </header>

    <div class="card sp-4">
        <h2><?= $st_answer === 'yes' ? 'Du bist dabei' : 'Bist du dabei?' ?></h2>

        <form class="row sp-2 wrap center" method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
            <input type="hidden" name="action" value="st_rsvp">
            <?php wp_nonce_field('st_rsvp') ?>
            <?php if ($st_answer === 'yes') : ?>
                <button class="cta" type="submit" name="answer" value="no">Absagen</button>
            <?php else : ?>
                <button class="cta cta--choice" type="submit" name="answer" value="yes">Ja</button>
                <button class="cta cta--choice" type="submit" name="answer" value="no" aria-pressed="<?= $st_answer === 'no' ? 'true' : 'false' ?>">Nein</button>
            <?php endif; ?>
        </form>

        <?php if (!$st_has_loc) : ?>
            <p class="notice notice--error">Du hast noch keine Adresse hinterlegt. <a href="<?= esc_url(add_query_arg('view', 'profile', home_url('/'))) ?>">Jetzt eintragen</a>, damit du bei der Berechnung zählst.</p>
        <?php endif; ?>
    </div>

    <div class="card sp-4">
        <h2><?= count($st_attendees) ?> dabei</h2>
        <?php if ($st_attendees) : ?>
            <p class="fs-2 center">
                <?= esc_html(implode(', ', wp_list_pluck($st_attendees, 'display_name'))) ?>
            </p>
        <?php else : ?>
            <p>Noch hat niemand zugesagt.</p>
        <?php endif; ?>
    </div>

    <div class="card sp-4">
        <?php if ($st_venue_id && $st_bar) : ?>
            <h2 class="up">Wir treffen uns hier</h2>
        <?php elseif ($st_bar) : ?>
            <h2 class="up">Aktuelle Kneipe</h2>
        <?php else : ?>
            <h2 class="up">Ort</h2>
        <?php endif; ?>
        <div class="card sp-4">
            <?php if ($st_bar) : ?>
                <h2 class="fs-8"><?= esc_html(get_the_title($st_bar_id)) ?></h2>
            <?php endif; ?>

            <?php if ($st_bar || !$st_venue_id) : ?>
                <ul class="facts sp-4 row wrap center">
                    <?php if ($st_bar) : ?>
                        <li class="tag"><?= esc_html($st_bar['address']) ?></li>
                        <li class="tag"><?= get_post_meta($st_bar_id, 'st_smoking', true) === 'yes' ? 'Raucherbar' : 'Nichtraucher' ?></li>
                    <?php endif; ?>
                    <?php if (!$st_venue_id) : ?>
                        <li>
                            <button class="tag tag--info" type="button" data-dialog="venue-info" aria-haspopup="dialog">
                                <span aria-hidden="true">i</span><span class="visually-hidden">Info zum Ort</span>
                            </button>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>

            <?php if ($st_bar) : ?>
                <a class="cta" href="<?= esc_url(st_map_url($st_bar_id)) ?>" target="_blank" rel="noopener">Google Maps</a>
            <?php endif; ?>

            <?php if (!$st_venue_id) : ?>
                <dialog id="venue-info" class="modal" aria-label="Info zum Ort">
                    <p>
                        <?= $st_bar ? 'Grob nach Luftlinie, kann sich noch ändern. ' : 'Sobald jemand zusagt und Bars eingetragen sind, siehst du hier den Treffpunkt. ' ?>
                        <?= esc_html(ucfirst(st_day_label($st_date))) ?> um <?= esc_html(st_setting('announce_time')) ?> Uhr wird die beste Kneipe festgelegt.
                    </p>
                    <p class="fs-2">Fahrzeiten: <a href="https://transitous.org/sources/" target="_blank" rel="noopener">Transitous</a>, Kartendaten © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a></p>
                    <button class="cta" type="button" data-close>Schließen</button>
                </dialog>
            <?php endif; ?>
        </div>
    </div>

</section>