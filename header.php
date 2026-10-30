<!DOCTYPE html>
<?php include THEME_DIR . "parts/greetings.php" ?>

<html lang="de">

<head>
    <?php
    $has_acf = function_exists('get_field');
    if (is_front_page()) {
        $meta_title = get_bloginfo('name');
    } else {
        $meta_title = (($has_acf ? get_field('seo_meta_title') : '') ?: get_the_title()) . ' | ' . get_bloginfo('name');
    }
    $meta_description = ($has_acf ? get_field('seo_meta_description') : '') ?: get_bloginfo('description');
    ?>


    <title><?php echo esc_html($meta_title); ?></title>
    <meta name="description" content="<?php echo esc_attr($meta_description); ?>">



    <meta charset="<?php bloginfo('charset') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">


    <link rel="icon" type="image/png" href="<?= get_theme_file_uri('load/favicon/favicon-96x96.png') ?>" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="<?= get_theme_file_uri('load/favicon/favicon.svg') ?>" />
    <link rel="shortcut icon" href="<?= get_theme_file_uri('load/favicon/favicon.ico') ?>" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?= get_theme_file_uri('load/favicon/apple-touch-icon.png') ?>" />
    <link rel="manifest" href="<?= get_theme_file_uri('load/favicon/site.webmanifest') ?>" />

    <!-- Safari bars in the page colour; from the home screen it runs as an app without them -->
    <meta name="theme-color" content="#e4e1e5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Stammtisch">

    <?php wp_head(); ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var firstImage = document.querySelector('img');
            if (firstImage) {
                firstImage.loading = "eager";
                firstImage.setAttribute('fetchpriority', 'high');
            }
        });
    </script>
</head>

<body <?php body_class(); ?>>