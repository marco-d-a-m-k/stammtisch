<?php

// ========================
//  Stammtisch Finder: Form Handlers (admin-post.php)
// ========================

// ---- Sign up ----
add_action('admin_post_nopriv_st_signup', function () {
    check_admin_referer('st_signup');

    $username = sanitize_user($_POST['username'] ?? '', true);
    $email    = sanitize_email($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $address  = sanitize_text_field($_POST['address'] ?? '');

    if (!$username || !$email || !$password || !$address) st_back('missing_fields');
    if (!is_email($email)) st_back('invalid_email');
    if (username_exists($username) || email_exists($email)) st_back('user_exists');
    if (strlen($password) < 8) st_back('weak_password');

    $geo = st_geocode($address, true);
    if (!$geo) st_back('address_unknown');

    $user_id = wp_insert_user([
        'user_login' => $username,
        'user_email' => $email,
        'user_pass'  => $password,
        'role'       => 'subscriber',
    ]);
    if (is_wp_error($user_id)) st_back('user_exists');

    st_set_location('user', $user_id, $address, $geo);
    wp_set_auth_cookie($user_id, true);
    st_back('signed_up');
});


// ---- Log in (username or e-mail) ----
add_action('admin_post_nopriv_st_login', function () {
    check_admin_referer('st_login');

    $user = wp_signon([
        'user_login'    => sanitize_text_field($_POST['login'] ?? ''),
        'user_password' => $_POST['password'] ?? '',
        'remember'      => true,
    ], is_ssl());

    st_back(is_wp_error($user) ? 'login_failed' : '');
});


// ---- Edit profile ----
add_action('admin_post_st_profile', function () {
    check_admin_referer('st_profile');

    $user_id  = get_current_user_id();
    $email    = sanitize_email($_POST['email'] ?? '');
    $address  = sanitize_text_field($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$address) st_back('missing_fields', 'profile');
    if (!is_email($email)) st_back('invalid_email', 'profile');

    $owner = email_exists($email);
    if ($owner && $owner !== $user_id) st_back('user_exists', 'profile');
    if ($password && strlen($password) < 8) st_back('weak_password', 'profile');

    $current = st_get_location('user', $user_id);
    if (!$current || $current['address'] !== $address) {
        $geo = st_geocode($address, true);
        if (!$geo) st_back('address_unknown', 'profile');
        st_set_location('user', $user_id, $address, $geo);
    }

    $data = ['ID' => $user_id, 'user_email' => $email];
    if ($password) $data['user_pass'] = $password;
    wp_update_user($data);

    // Changing the password logs the user out, so log them back in
    if ($password) wp_set_auth_cookie($user_id, true);

    st_back('profile_saved', 'profile');
});


// ---- Delete account ----
add_action('admin_post_st_delete_account', function () {
    check_admin_referer('st_delete_account');

    if (current_user_can('manage_options')) st_back('not_allowed', 'profile');

    require_once ABSPATH . 'wp-admin/includes/user.php';

    // Keep the bars this user added: hand them to an admin
    $admin = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
    wp_delete_user(get_current_user_id(), $admin[0] ?? null);
    wp_logout();

    st_back('account_deleted');
});


// ---- Add bar ----
add_action('admin_post_st_add_bar', function () {
    check_admin_referer('st_add_bar');

    $name    = sanitize_text_field($_POST['name'] ?? '');
    $address = sanitize_text_field($_POST['address'] ?? '');
    $smoking = ($_POST['smoking'] ?? '') === 'yes' ? 'yes' : 'no';

    if (!$name || !$address) st_back('missing_fields', 'bars');

    $geo = st_geocode($address);
    if (!$geo) st_back('address_unknown', 'bars');

    $bar_id = wp_insert_post([
        'post_type'   => 'bar',
        'post_title'  => $name,
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ]);

    st_set_location('post', $bar_id, $address, $geo);
    update_post_meta($bar_id, 'st_smoking', $smoking);

    st_back('bar_added', 'bars');
});


// ---- Delete bar (own bars, admins: all) ----
add_action('admin_post_st_delete_bar', function () {
    $bar_id = (int) ($_POST['bar_id'] ?? 0);
    check_admin_referer('st_delete_bar_' . $bar_id);

    if (!st_can_delete_bar($bar_id)) st_back('not_allowed', 'bars');

    wp_delete_post($bar_id, true);
    st_back('bar_deleted', 'bars');
});

function st_can_delete_bar($bar_id)
{
    $bar = get_post($bar_id);
    if (!$bar || $bar->post_type !== 'bar') return false;
    return current_user_can('manage_options') || (int) $bar->post_author === get_current_user_id();
}


// ---- Yes / No on the website ----
add_action('admin_post_st_rsvp', function () {
    check_admin_referer('st_rsvp');

    $answer = ($_POST['answer'] ?? '') === 'yes' ? 'yes' : 'no';
    st_set_rsvp(get_current_user_id(), st_next_date(), $answer);
    st_back('rsvp_' . $answer);
});


// ---- Admin: settings from the website ----
add_action('admin_post_st_save_settings', function () {
    check_admin_referer('st_save_settings');
    if (!current_user_can('manage_options')) st_back('not_allowed');

    $input = wp_unslash((array) ($_POST['st_settings'] ?? []));
    if (($input['announce_time'] ?? '') <= ($input['invite_time'] ?? '')) st_back('settings_order', 'admin');

    update_option('st_settings', st_sanitize_settings($input));
    st_back('settings_saved', 'admin');
});


// ---- Admin: send the invite now ----
add_action('admin_post_st_send_invites', function () {
    check_admin_referer('st_send_invites');
    if (!current_user_can('manage_options')) st_back('not_allowed');

    st_back('invites_sent', 'admin', st_send_invites(st_next_date(), true));
});


// ---- Admin: pick the bar by hand ----
add_action('admin_post_st_set_venue', function () {
    check_admin_referer('st_set_venue');
    if (!current_user_can('manage_options')) st_back('not_allowed');

    $date   = st_next_date();
    $bar_id = (int) ($_POST['bar_id'] ?? 0);
    $bar    = get_post($bar_id);
    if ($bar_id && (!$bar || $bar->post_type !== 'bar')) st_back('not_allowed', 'admin');

    st_set_manual_venue($date, $bar_id);
    if (!$bar_id) st_back('venue_auto', 'admin');

    if (empty($_POST['send_now'])) st_back('venue_saved', 'admin');
    if (!st_attendees($date)) st_back('venue_nobody', 'admin');

    st_back('venue_sent', 'admin', st_announce($date, true));
});


// ========================
//  One-click Yes / No from the invite mail
// ========================

// Mail scanners open links on their own, so a GET only shows a page that
// re-submits itself as POST. Only the POST stores the answer.
add_action('template_redirect', function () {
    if (!isset($_GET['st_rsvp'])) return;

    $answer  = $_GET['st_rsvp'] === 'yes' ? 'yes' : 'no';
    $user_id = (int) ($_GET['u'] ?? 0);
    $date    = sanitize_text_field($_GET['d'] ?? '');
    $token   = sanitize_text_field($_GET['t'] ?? '');

    $valid = $user_id
        && get_userdata($user_id)
        && $date >= st_now()->format('Y-m-d')
        && hash_equals(st_rsvp_token($user_id, $date, $answer), $token);

    if (!$valid) st_back('rsvp_invalid');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        st_set_rsvp($user_id, $date, $answer);
        st_back('rsvp_' . $answer);
    }

    nocache_headers();
    $label = $answer === 'yes' ? 'Ja, ich bin dabei' : 'Nein, heute nicht';
?>
    <!DOCTYPE html>
    <html lang="de">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>Stammtisch</title>
    </head>

    <body style="font-family: system-ui, sans-serif; padding: 2rem;">
        <form method="post" id="st-rsvp">
            <button type="submit"><?= esc_html($label) ?></button>
        </form>
        <script>
            document.getElementById('st-rsvp').submit();
        </script>
    </body>

    </html>
<?php
    exit;
});
