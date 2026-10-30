<?php

// ========================
//  Stammtisch Finder: Site Password
// ========================
//
// Everyone needs the shared password before seeing anything. Logged-in users
// and the one-click Ja/Nein links from the mail skip it.
//
// The password is set by an admin (Einstellungen → Stammtisch, or the admin view);
// only its hash is stored, in the option st_gate_hash. As long as none is set,
// nobody gets past the password screen. Admins log in via /wp-admin.

define('ST_GATE_COOKIE', 'st_gate');

function st_gate_hash()
{
    return (string) get_option('st_gate_hash', '');
}

// Cookie value changes with the password, so a new password locks everyone out again
function st_gate_token()
{
    return hash_hmac('sha256', st_gate_hash(), wp_salt('auth'));
}

function st_gate_open()
{
    if (is_user_logged_in()) return true;
    return hash_equals(st_gate_token(), (string) ($_COOKIE[ST_GATE_COOKIE] ?? ''));
}

add_action('template_redirect', function () {
    if (st_gate_open() || isset($_GET['st_rsvp']) || isset(st_legal_views()[$_GET['view'] ?? ''])) return;

    $error = false;
    if (isset($_POST['st_gate_password'])) {
        if (password_verify(wp_unslash($_POST['st_gate_password']), st_gate_hash())) {
            setcookie(ST_GATE_COOKIE, st_gate_token(), [
                'expires'  => time() + 90 * DAY_IN_SECONDS,
                'path'     => COOKIEPATH ?: '/',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            wp_safe_redirect(add_query_arg([])); // back to the page that was asked for
            exit;
        }
        $error = true;
        sleep(1); // slows down guessing
    }

    nocache_headers();
    status_header(401);
    get_header();
    include THEME_DIR . 'parts/components/gate.php';
    get_footer();
    exit;
}, 0);

// Sign up / log in only after the password
foreach (['st_signup', 'st_login'] as $st_action) {
    add_action("admin_post_nopriv_$st_action", function () {
        if (!st_gate_open()) {
            wp_safe_redirect(home_url('/'));
            exit;
        }
    }, 0);
}

// REST API would list usernames to anyone
add_filter('rest_authentication_errors', function ($result) {
    if ($result || is_user_logged_in()) return $result;
    return new WP_Error('st_gate', 'Nicht erlaubt.', ['status' => 401]);
});
