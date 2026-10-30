<?php

// ========================
//  Load Theme Assets
// ========================

function sound_and_vision()
{
    $script_path = get_theme_file_path('/build/script.js');
    $style_path  = get_theme_file_path('/build/style.css');

    $script_version = file_exists($script_path) ? filemtime($script_path) : '1.0.0';
    $style_version  = file_exists($style_path) ? filemtime($style_path) : '1.0.0';

    wp_enqueue_script('main-js', get_theme_file_uri('/build/script.js'), NULL, $script_version, true);
    wp_enqueue_style('style', get_theme_file_uri('/build/style.css'), NULL, $style_version);
}

add_action('wp_enqueue_scripts', 'sound_and_vision');


// Filter to add defer attribute to specific scripts
function add_defer_attribute($tag, $handle)
{
    if ('main-js' !== $handle) {
        return $tag;
    }
    return str_replace(' src', ' defer src', $tag);
}
add_filter('script_loader_tag', 'add_defer_attribute', 10, 2);

// ========================
//  Constants
// ========================
define('THEME_DIR', get_template_directory() . '/');
define('SITE_URL', site_url() . '/');


// ========================
//  WordPress Cleanup (No Bloat)
// ========================
require_once get_template_directory() . '/parts/wp-debloat.php';


// ========================
//  Stammtisch Finder
// ========================
require_once get_template_directory() . '/m-k-code/settings.php';
require_once get_template_directory() . '/m-k-code/stammtisch.php';
require_once get_template_directory() . '/m-k-code/forms.php';
require_once get_template_directory() . '/m-k-code/cronjob.php';
require_once get_template_directory() . '/m-k-code/gate.php';


// ========================
//  Disable All Content Editors
// ========================
add_action('init', function () {
    $types = get_post_types(['public' => true], 'names');
    foreach ($types as $post_type) {
        remove_post_type_support($post_type, 'editor');
    }
}, 100);


// ========================
//  Remove Autop from Contact Form 7
// ========================
add_filter('wpcf7_autop_or_not', '__return_false');


// ========================
//  Admin UI Cleanup
// ========================
function post_remove()
{
    remove_menu_page('jetpack');
    remove_menu_page('edit.php');             // Posts
    remove_menu_page('edit-comments.php');    // Comments
    remove_menu_page('themes.php');           // Appearance
}
add_action('admin_menu', 'post_remove');

// Load admin styles
function backend_style()
{
    $screen = get_current_screen();
    if ($screen) {
        wp_enqueue_style('admin-styles', get_template_directory_uri() . '/load/backend/wordpress.css');
    }
}
add_action('admin_enqueue_scripts', 'backend_style');

// Add TinyMCE styles
function add_acf_wysiwyg_styles($mce_css)
{
    $editor_style_url = get_template_directory_uri() . '/load/backend/editor-style.css';
    if (!empty($mce_css)) {
        $mce_css .= ',';
    }
    $mce_css .= $editor_style_url;
    return $mce_css;
}
add_filter('mce_css', 'add_acf_wysiwyg_styles');

// Remove admin bar margin CSS
function remove_admin_bar_bump()
{
    remove_action('wp_head', '_admin_bar_bump_cb');
}
add_action('get_header', 'remove_admin_bar_bump');

function custom_login_styles()
{
    wp_enqueue_style('custom-login', get_stylesheet_directory_uri() . '/load/backend/custom-login.css');
}
add_action('login_enqueue_scripts', 'custom_login_styles');

function custom_login_logo_url()
{
    return "https://www.d-a-m-k.de";
}
add_filter('login_headerurl', 'custom_login_logo_url');

function custom_login_logo_title()
{
    return "d-a-m-k Büro für Gestaltung";
}
add_filter('login_headertext', 'custom_login_logo_title');

// ========================
//  Auto-Set Featured Image from First Upload
// ========================
add_action('add_attachment', function ($attachment_id) {
    $attachment = get_post($attachment_id);
    if (!$attachment || $attachment->post_type !== 'attachment') return;

    $parent_id = $attachment->post_parent;
    if (!$parent_id || has_post_thumbnail($parent_id)) return;

    $mime = get_post_mime_type($attachment_id);
    if (strpos($mime, 'image/') === 0) {
        set_post_thumbnail($parent_id, $attachment_id);
    }
});

// ========================
//  Global Image Settings
// ========================
add_filter('wp_editor_set_quality', fn($arg) => 100); // Max JPEG quality
add_filter('big_image_size_threshold', '__return_false'); // Disable large image scaling
add_action('after_setup_theme', function () {
    set_post_thumbnail_size(150, 9999); // 150px wide, unlimited height
});

// ========================
//  Responsive Image Output
// ========================
function responsive_images($image_id, $img_sizes)
{
    if (!$image_id) return;

    $meta     = wp_get_attachment_metadata($image_id, 'full');
    $src      = wp_get_attachment_image_url($image_id, 'full');
    $thumb    = wp_get_attachment_image_url($image_id, 'post-thumbnail');
    $srcset   = wp_get_attachment_image_srcset($image_id, 'full');
    $alt      = get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: "Kein Alt-Text hinterlegt.";
    $mime     = get_post_mime_type($image_id);
    $w        = $meta['width'];
    $h        = $meta['height'];

    if ($mime === 'image/gif') {
        echo "src=\"" . esc_url($src) . "\" width=\"$w\" height=\"$h\" alt=\"" . esc_attr($alt) . "\"";
    } else {
        echo "src=\"" . esc_url($thumb) . "\" " .
            "style=\"background-image: url(" . esc_url($thumb) . ");\" " .
            "srcset=\"" . esc_attr($srcset) . "\" " .
            "sizes=\"" . esc_attr($img_sizes) . "\" " .
            "width=\"$w\" height=\"$h\" alt=\"" . esc_attr($alt) . "\"";
    }
}

// ========================
//  Body Class: Add Page Slug
// ========================
add_filter('body_class', function ($classes) {
    $post = get_queried_object();
    if (is_a($post, 'WP_Post') && $post->post_type === 'page') {
        $classes[] = $post->post_name;
    }
    return $classes;
});


// ========================
//  ACF: Minimal Toolbar
// ========================
add_filter('acf/fields/wysiwyg/toolbars', function ($toolbars) {
    $toolbars['d-a-m-k-way'][1] = ['bold', 'italic', 'bullist', 'numlist', 'link', 'hr', 'formatselect'];
    return $toolbars;
});

add_filter('tiny_mce_before_init', 'limit_editor_formats');

function limit_editor_formats($settings)
{
    $settings['block_formats'] = 'Fließtext=p;Überschrift=h3';
    return $settings;
}

//  Allow MIME Type Uploads 
// ========================
add_filter('upload_mimes', function ($allowed) {
    if (current_user_can('manage_options')) {
        $allowed['svg'] = 'image/svg+xml';
        $allowed['json'] = 'application/json';
    }
    return $allowed;
});

// ========================
//  Section Marker
// ========================

function marker($block)
{
    if (!empty($block['label_onpage-nav'])) {
        $label = $block['label_onpage-nav'];
        $slug = strtolower(str_replace(' ', '-', $label));
        echo 'id="' . esc_attr($slug) . '" data-section-marker';
    }
}

// ========================
//  2FA
// ========================

/**
 * Force administrators to enable Two-Factor Authentication.
 * Requires the "Two-Factor" plugin: https://wordpress.org/plugins/two-factor/
 */

add_action('admin_init', function () {

    // Bail if the Two-Factor plugin isn't active.
    if (! class_exists('Two_Factor_Core')) {
        return;
    }

    // Only target administrators (use the capability, not the role name,
    // so it also covers custom roles with admin-level access).
    if (! current_user_can('manage_options')) {
        return;
    }

    // Never block AJAX or cron requests.
    if (wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    // Already set up 2FA? Nothing to do.
    if (Two_Factor_Core::is_user_using_two_factor(get_current_user_id())) {
        return;
    }

    // Let them reach their profile page (where 2FA is configured) and log out.
    global $pagenow;
    if (in_array($pagenow, array('profile.php', 'admin-ajax.php'), true)) {
        return;
    }

    // Redirect everywhere else in wp-admin straight to the 2FA section.
    wp_safe_redirect(admin_url('profile.php?force_2fa=1#two-factor-options'));
    exit;
});

/**
 * Explain why they got redirected — text follows the site's admin language,
 * and links straight to the settings section.
 */
add_action('admin_notices', function () {
    if (empty($_GET['force_2fa'])) {
        return;
    }

    $is_german  = (0 === strpos(get_locale(), 'de'));
    $settings_url = esc_url(admin_url('profile.php#two-factor-options'));

    $message = $is_german
        ? 'Für Administrator-Konten ist die Zwei-Faktor-Authentifizierung erforderlich. Bitte aktiviere <a href="' . $settings_url . '">hier</a> eine Methode, bevor du fortfährst.'
        : 'Two-Factor Authentication is required for administrator accounts. Please enable a method <a href="' . $settings_url . '">here</a> before continuing.';

    echo '<div class="notice notice-error"><p><strong>' . wp_kses($message, array('a' => array('href' => array()))) . '</strong></p></div>';
});
