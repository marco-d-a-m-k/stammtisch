<?php
get_header();

$views = ['info', 'profile', 'bars', ...array_keys(st_legal_views())];
if (current_user_can('manage_options')) $views[] = 'admin';
$view  = in_array($_GET['view'] ?? '', $views, true) ? $_GET['view'] : 'info';
$legal = isset(st_legal_views()[$view]);
?>

<?php if (!is_user_logged_in() && $legal) : ?>

    <main id="main" class="main main--login sp-8">
        <?php include THEME_DIR . "parts/components/legal.php" ?>
        <a class="cta" href="<?= esc_url(home_url('/')) ?>">Zurück</a>
    </main>

<?php elseif (!is_user_logged_in()) : ?>

    <?php include THEME_DIR . "parts/components/login.php" ?>

<?php else : ?>

    <?php // Toast is position: fixed; outside .right so the pop-in scale can't shift it
    include THEME_DIR . "parts/components/notice.php" ?>

    <main id="main" class="main">

        <div class="left">
            <?php include THEME_DIR . "parts/components/nav.php" ?>
        </div>

        <div class="right sp-6">

            <?php
            if ($view === 'profile') {
                include THEME_DIR . "parts/components/edit-user.php";
            } elseif ($view === 'bars') {
                include THEME_DIR . "parts/components/add-bar.php";
            } elseif ($legal) {
                include THEME_DIR . "parts/components/legal.php";
            } elseif ($view === 'admin') {
                include THEME_DIR . "parts/components/admin.php";
            } else {
                include THEME_DIR . "parts/components/main-info.php";
            }
            ?>

        </div>
    </main>

<?php endif; ?>

<?php
get_footer();
?>