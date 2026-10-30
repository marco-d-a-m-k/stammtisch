<?php
$nav_items = [
    'info'    => 'Stammtisch',
    'profile' => 'Profil',
    'bars'    => 'Kneipen',
];
if (current_user_can('manage_options')) $nav_items['admin'] = 'Admin';
?>
<button class="cta cta--menu mobile" type="button" aria-haspopup="dialog" aria-controls="nav" aria-expanded="false">Menu</button>

<?php // Desktop: shown inline. Mobile: modal from the left (src/js/base/base.js) 
?>
<dialog id="nav" class="nav-dialog" aria-label="Hauptmenü">
    <button class="cta nav-dialog__close mobile" type="button">Schließen</button>

    <nav class="nav sp-8" aria-label="Hauptmenü">
        <ul class="nav__list sp-2 center">
            <?php foreach ($nav_items as $nav_view => $nav_label) : ?>
                <li class="cta">
                    <a href="<?= esc_url($nav_view === 'info' ? home_url('/') : add_query_arg('view', $nav_view, home_url('/'))) ?>"
                        <?= $view === $nav_view ? 'aria-current="page"' : '' ?>><?= esc_html($nav_label) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="nav__meta">
        <?php foreach (st_legal_views() as $nav_view => $nav_label) : ?>
            <a class="cta--logout" href="<?= esc_url(add_query_arg('view', $nav_view, home_url('/'))) ?>"
                <?= $view === $nav_view ? 'aria-current="page"' : '' ?>><?= esc_html($nav_label) ?></a>
        <?php endforeach; ?>
        <a class="cta--logout" href="<?= esc_url(wp_logout_url(home_url('/'))) ?>">Abmelden</a>
        <p class="fs-2">Daten: <a href="https://transitous.org/sources/" target="_blank" rel="noopener">Transitous</a>, © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a></p>
    </div>
</dialog>