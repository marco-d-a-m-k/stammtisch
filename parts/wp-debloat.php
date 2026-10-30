<?php
/**
 * WP Bloat Remover – include in functions.php
 */

// Remove emoji scripts/styles
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_filter('the_content_feed', 'wp_staticize_emoji');
remove_filter('comment_text_rss', 'wp_staticize_emoji');
remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
add_filter('tiny_mce_plugins', fn($plugins) => is_array($plugins) ? array_diff($plugins, ['wpemoji']) : []);

// Remove block/Gutenberg CSS
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('wc-blocks-style');
    wp_dequeue_style('classic-theme-styles');
    wp_deregister_style('classic-theme-styles');
}, 100);

// Disable block editor
remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
add_filter('use_block_editor_for_post', '__return_false', 10);
add_filter('use_block_editor_for_post_type', '__return_false', 10);

// Remove scripts (early, before dependency chains form)
add_action('wp_enqueue_scripts', function () {
    wp_deregister_script('jquery');
    wp_deregister_script('jquery-migrate');
    wp_deregister_script('wp-polyfill');
    wp_deregister_script('wp-deprecated');
}, 1);

// Remove meta head junk
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head');

// Remove dashicons for non-logged-in users
add_action('wp_enqueue_scripts', function () {
    if (!is_user_logged_in()) {
        wp_deregister_style('dashicons');
    }
});