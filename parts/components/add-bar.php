<?php
$st_bars = get_posts([
    'post_type'   => 'bar',
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby'     => 'title',
    'order'       => 'ASC',
]);
?>

<section class="sp-6">

    <h1 class="fs-10">Bars</h1>

    <form class="card sp-4" method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
        <h2>Bar hinzufügen</h2>
        <input type="hidden" name="action" value="st_add_bar">
        <?php wp_nonce_field('st_add_bar') ?>

        <label class="field">
            <span class="visually-hidden">Name</span>
            <input type="text" name="name" required placeholder="Name">
        </label>
        <label class="field">
            <span class="visually-hidden">Adresse</span>
            <input type="text" name="address" required placeholder="Adresse (Straße, PLZ, Ort)">
        </label>

        <fieldset class="sp-4 sb row wrap">
            <legend class="field visually-hidden">Rauchen</legend>
            <label class="cta cta--radio"><input class="visually-hidden" type="radio" name="smoking" value="no" checked> Nichtraucher</label>
            <label class="cta cta--radio"><input class="visually-hidden" type="radio" name="smoking" value="yes"> Raucher</label>
        </fieldset>

        <button class="cta cta--primary" type="submit">Hinzufügen</button>
    </form>

    <div class="card sp-4">
        <h2><?= count($st_bars) ?> Bars</h2>

        <?php if ($st_bars) : ?>
            <ul class="bar-list sp-4">
                <?php foreach ($st_bars as $st_bar_post) :
                    $st_bar_loc = st_get_location('post', $st_bar_post->ID);
                ?>
                    <li class="bar-list__item sp-4 card center row wrap sb">
                        <div class="sp-2">
                            <strong class="sp-4"><?= esc_html($st_bar_post->post_title) ?></strong>
                            <span class="hint">
                                <?= esc_html($st_bar_loc['address'] ?? 'Keine Adresse') ?> ·
                                <?= get_post_meta($st_bar_post->ID, 'st_smoking', true) === 'yes' ? 'Raucher' : 'Nichtraucher' ?>
                            </span>
                        </div>

                        <?php if (st_can_delete_bar($st_bar_post->ID)) : ?>
                            <form method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
                                <input type="hidden" name="action" value="st_delete_bar">
                                <input type="hidden" name="bar_id" value="<?= (int) $st_bar_post->ID ?>">
                                <?php wp_nonce_field('st_delete_bar_' . $st_bar_post->ID) ?>
                                <button class="cta cta--small" type="submit" data-confirm="Bar wirklich löschen?">Löschen</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p>Noch keine Bars eingetragen.</p>
        <?php endif; ?>
    </div>

</section>