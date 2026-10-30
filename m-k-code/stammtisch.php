<?php

// ========================
//  Stammtisch Finder: Core
// ========================

// Times, walking & routing options: Einstellungen → Stammtisch (m-k-code/settings.php)


// ========================
//  Post Type: Bar
// ========================

add_action('init', function () {
    register_post_type('bar', [
        'labels'    => ['name' => 'Bars', 'singular_name' => 'Bar', 'add_new_item' => 'Bar hinzufügen'],
        'public'    => false,
        'show_ui'   => true,
        'menu_icon' => 'dashicons-location',
        'supports'  => ['title', 'author'],
    ]);
});

add_action('add_meta_boxes_bar', function () {
    add_meta_box('st_bar', 'Ort', 'st_bar_metabox', 'bar', 'normal', 'high');
});

function st_bar_metabox($post)
{
    $loc     = st_get_location('post', $post->ID);
    $smoking = get_post_meta($post->ID, 'st_smoking', true);
    wp_nonce_field('st_bar_metabox', 'st_bar_nonce');
?>
    <p><label>Adresse<br><input type="text" name="st_address" class="widefat" value="<?= esc_attr($loc['address'] ?? '') ?>"></label></p>
    <p><label>Raucher<br>
            <select name="st_smoking">
                <option value="no" <?php selected($smoking, 'no') ?>>Nichtraucher</option>
                <option value="yes" <?php selected($smoking, 'yes') ?>>Raucher</option>
            </select></label></p>
    <p class="description"><?= $loc ? esc_html("Koordinaten: {$loc['lat']}, {$loc['lng']}") : 'Noch keine Koordinaten.' ?></p>
<?php
}

add_action('save_post_bar', function ($post_id) {
    if (!isset($_POST['st_bar_nonce']) || !wp_verify_nonce($_POST['st_bar_nonce'], 'st_bar_metabox')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    update_post_meta($post_id, 'st_smoking', ($_POST['st_smoking'] ?? '') === 'yes' ? 'yes' : 'no');

    $address = sanitize_text_field($_POST['st_address'] ?? '');
    $current = st_get_location('post', $post_id);
    if ($address && (!$current || $current['address'] !== $address)) {
        $geo = st_geocode($address);
        if ($geo) st_set_location('post', $post_id, $address, $geo);
    }
});


// ========================
//  Dates
// ========================

function st_now()
{
    return new DateTimeImmutable('now', wp_timezone());
}

// Next meeting (Y-m-d): "Nächster Termin" + every "Turnus" weeks. Today counts all day.
function st_next_date()
{
    $today  = st_now()->setTime(0, 0);
    $anchor = new DateTimeImmutable(st_setting('anchor_date'), wp_timezone());
    if ($anchor >= $today) return $anchor->format('Y-m-d');

    $period = 7 * st_setting('interval');
    $steps  = (int) ceil($anchor->diff($today)->days / $period);
    return $anchor->modify('+' . ($steps * $period) . ' days')->format('Y-m-d');
}

function st_upcoming_dates($count)
{
    $first  = new DateTimeImmutable(st_next_date(), wp_timezone());
    $period = 7 * st_setting('interval');
    return array_map(fn($i) => $first->modify('+' . ($i * $period) . ' days')->format('Y-m-d'), range(0, $count - 1));
}

function st_weekday($date)
{
    $days = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];
    return $days[(new DateTimeImmutable($date, wp_timezone()))->format('N') - 1];
}

// "Immer donnerstags" / "Alle 2 Wochen donnerstags"
function st_rhythm_label()
{
    $weeks = (int) st_setting('interval');
    $day   = strtolower(st_weekday(st_next_date())) . 's';
    return $weeks === 1 ? "Immer $day" : "Alle $weeks Wochen $day";
}

// "heute" on the day itself, otherwise "am Donnerstag, 22. Oktober"
function st_day_label($date)
{
    return $date === st_now()->format('Y-m-d') ? 'heute' : 'am ' . st_format_date($date);
}

// German, independent of the site language: "Donnerstag, 22. Oktober"
function st_format_date($date)
{
    $months = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
    $d      = new DateTimeImmutable($date, wp_timezone());
    return st_weekday($date) . ', ' . $d->format('j') . '. ' . $months[$d->format('n') - 1];
}


// ========================
//  Locations
// ========================

// Address → ['lat' => float, 'lng' => float] via Transitous (same service as the routing)
// $private: a member's home address. Sent without house number (no personal
// data leaves the server), so the result is the middle of the street.
function st_geocode($address, $private = false)
{
    $address = trim($private ? st_strip_house_number($address) : $address);
    if ($address === '') return null;

    $cache_key = 'st_geo_' . md5(strtolower($address));
    $cached = get_transient($cache_key);
    if ($cached !== false) return $cached ?: null;

    $url = 'https://api.transitous.org/api/v1/geocode?' . http_build_query([
        'text'     => preg_replace('/(str)\.(?=\s|,|$)/iu', '$1aße', $address), // "Hauptstr." isn't matched
        'language' => 'de',
    ]);
    st_geocode_wait();
    $response = wp_remote_get($url, [
        'timeout' => 10,
        'headers' => ['User-Agent' => st_user_agent()],
    ]);

    // Don't cache network errors, only real answers
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return null;

    // Transitous also returns stops and fuzzy place matches: only take real
    // addresses, and if a postcode was typed, it has to match
    preg_match('/\b\d{5}\b/', $address, $zip);
    $hits = array_filter(
        (array) json_decode(wp_remote_retrieve_body($response), true),
        fn($hit) => ($hit['type'] ?? '') === 'ADDRESS' && (!$zip || ($hit['zip'] ?? '') === $zip[0])
    );
    $hit = reset($hits) ?: null;
    $geo = $hit ? ['lat' => (float) $hit['lat'], 'lng' => (float) $hit['lon']] : [];

    set_transient($cache_key, $geo, MONTH_IN_SECONDS);
    return $geo ?: null;
}

// "Musterstraße 12a, 12345 Musterstadt" → "Musterstraße, 12345 Musterstadt".
// Keeps postcodes (5 digits) and numbers in names ("Straße des 17. Juni").
function st_strip_house_number($address)
{
    $letter  = '\s*[a-zA-Z]?(?![a-zA-ZäöüßÄÖÜ])';
    $address = preg_replace('/(?<![\d.])\d{1,4}(?!\d|\.)' . $letter . '(\s*[-–\/]\s*\d{1,4}' . $letter . ')?/u', '', $address);
    return trim(preg_replace(['/\s+,/', '/\s{2,}/', '/^,\s*/'], [',', ' ', ''], $address));
}

// Be gentle with the free service: max. 1 lookup per second. Each request reserves
// the next free second (shared across PHP processes via the options table) and waits for it.
function st_geocode_wait()
{
    global $wpdb;

    add_option('st_geocode_slot', 0, '', false);

    // Milliseconds. LAST_INSERT_ID(x) hands this connection its own reserved value.
    $now = (int) (microtime(true) * 1000);
    $wpdb->query($wpdb->prepare(
        "UPDATE $wpdb->options SET option_value = LAST_INSERT_ID(GREATEST(option_value + 1000, %d))
         WHERE option_name = 'st_geocode_slot'",
        $now
    ));
    $slot = (int) $wpdb->get_var('SELECT LAST_INSERT_ID()');

    if ($slot > $now) usleep(min(10000, $slot - $now) * 1000);
}

function st_user_agent()
{
    return 'Stammtisch-Finder/1.0 (' . home_url() . ')';
}

// $type: 'user' or 'post'
function st_get_location($type, $id)
{
    $lat = get_metadata($type, $id, 'st_lat', true);
    $lng = get_metadata($type, $id, 'st_lng', true);
    if ($lat === '' || $lng === '') return null;

    return [
        'lat'     => (float) $lat,
        'lng'     => (float) $lng,
        'address' => get_metadata($type, $id, 'st_address', true),
    ];
}

function st_set_location($type, $id, $address, $geo)
{
    update_metadata($type, $id, 'st_address', $address);
    update_metadata($type, $id, 'st_lat', $geo['lat']);
    update_metadata($type, $id, 'st_lng', $geo['lng']);
}


// ---- Home address: street (no house number), postcode, city ----
// Members' addresses are never stored with a house number: street level is
// enough to pick a bar, and what isn't stored can't leak.

// From the form fields. Returns null if incomplete or the postcode is invalid;
// 'stripped' says whether a typed house number was removed.
function st_home_address($street, $zip, $city)
{
    $typed  = sanitize_text_field($street);
    $street = st_strip_house_number($typed);
    $zip    = preg_replace('/\s+/', '', sanitize_text_field($zip));
    $city   = sanitize_text_field($city);
    if ($street === '' || $city === '' || !preg_match('/^\d{5}$/', $zip)) return null;

    return [
        'street'   => $street,
        'zip'      => $zip,
        'city'     => $city,
        'address'  => "$street, $zip $city",
        'stripped' => $street !== trim($typed),
    ];
}

// Same, read from $_POST (fields street / zip / city)
function st_home_address_from_post()
{
    return st_home_address(
        wp_unslash($_POST['street'] ?? ''),
        wp_unslash($_POST['zip'] ?? ''),
        wp_unslash($_POST['city'] ?? '')
    );
}

function st_set_home_address($user_id, $home, $geo)
{
    st_set_location('user', $user_id, $home['address'], $geo);
    foreach (['street', 'zip', 'city'] as $part) update_user_meta($user_id, "st_$part", $home[$part]);
}

// For the forms: ['street', 'zip', 'city']. Older free-text addresses are parsed.
function st_home_address_parts($user_id)
{
    $parts = array_map(fn($part) => (string) get_user_meta($user_id, "st_$part", true), ['street' => 'street', 'zip' => 'zip', 'city' => 'city']);
    if ($parts['street'] !== '') return $parts;

    $address = (string) get_user_meta($user_id, 'st_address', true);
    if (preg_match('/^(.*?),?\s*(\d{5})\s+(.+)$/u', $address, $m)) {
        return ['street' => st_strip_house_number($m[1]), 'zip' => $m[2], 'city' => trim($m[3])];
    }
    return ['street' => st_strip_house_number($address), 'zip' => '', 'city' => ''];
}

// One-time cleanup: drop house numbers from addresses saved before this
// existed, and look them up again (street level). Runs once in WP-Admin.
add_action('admin_init', function () {
    if (!current_user_can('manage_options') || !add_option('st_home_address_v2', time(), '', false)) return;

    foreach (get_users(['meta_key' => 'st_address', 'fields' => 'ID']) as $user_id) {
        if (get_user_meta($user_id, 'st_street', true) !== '') continue;

        $parts = st_home_address_parts($user_id);
        $home  = st_home_address($parts['street'], $parts['zip'], $parts['city']);
        $geo   = $home ? st_geocode($home['address']) : null;

        if ($geo) {
            st_set_home_address($user_id, $home, $geo);
        } else {
            // Not parseable or not found: keep the coordinates, but store the address without number
            update_user_meta($user_id, 'st_address', st_strip_house_number((string) get_user_meta($user_id, 'st_address', true)));
        }
    }
});

// Great-circle distance in km
function st_distance($a, $b)
{
    $dLat = deg2rad($b['lat'] - $a['lat']);
    $dLng = deg2rad($b['lng'] - $a['lng']);
    $h = sin($dLat / 2) ** 2 + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($dLng / 2) ** 2;
    return 6371 * 2 * asin(min(1, sqrt($h)));
}

// Fastest way there: Öffis or walking (walking gets a head start of
// "Gehen bevorzugen" minutes). Returns ['minutes' => int, 'mode' => 'walk'|'transit'] or null.
function st_route($from, $to, $date)
{
    $times = st_route_times($from, $to, $date);
    if (!$times) return null;

    $walk    = $times['walk'];
    $transit = $times['transit'];

    if ($walk !== null && ($transit === null || $walk <= $transit + st_setting('walk_bonus') * 60)) {
        return ['minutes' => (int) ceil($walk / 60), 'mode' => 'walk'];
    }
    if ($transit !== null) {
        return ['minutes' => (int) ceil($transit / 60), 'mode' => 'transit'];
    }
    return null;
}

// Raw durations in seconds ['walk' => ?int, 'transit' => ?int], arriving by "Treffen um".
// Transitous (transitous.org): routing on OpenStreetMap + open timetable data.
// Usage policy: https://transitous.org/api/
function st_route_times($from, $to, $date)
{
    $max_walk = st_setting('max_walk') * MINUTE_IN_SECONDS;
    $meet     = st_setting('meet_time');

    // Settings are part of the key, so changing them recalculates
    $coords    = array_map(fn($n) => round($n, 4), [$from['lat'], $from['lng'], $to['lat'], $to['lng']]);
    $cache_key = 'st_route_' . md5(implode(',', [...$coords, $meet, $max_walk]));
    $cached    = get_transient($cache_key);
    if ($cached !== false) return $cached;

    $arrive = new DateTimeImmutable($date . ' ' . $meet, wp_timezone());
    $query  = [
        'fromPlace'      => "{$from['lat']},{$from['lng']}",
        'toPlace'        => "{$to['lat']},{$to['lng']}",
        'time'           => $arrive->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
        'arriveBy'       => 'true',
        'numItineraries' => 3,
    ];
    if ($max_walk) {
        $query['directModes']   = 'WALK';
        $query['maxDirectTime'] = $max_walk; // otherwise longer walks are left out
    }

    $response = wp_remote_get('https://api.transitous.org/api/v5/plan?' . http_build_query($query), [
        'timeout' => 15,
        'headers' => ['User-Agent' => st_user_agent()],
    ]);

    // Don't cache network errors, only real answers
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return null;

    $data    = json_decode(wp_remote_retrieve_body($response), true);
    $transit = array_column($data['itineraries'] ?? [], 'duration');
    $walk    = $max_walk ? array_column($data['direct'] ?? [], 'duration') : [];
    $times   = [
        'walk'    => $walk ? (int) min($walk) : null,
        'transit' => $transit ? (int) min($transit) : null,
    ];

    // Timetables repeat weekly, so a few days of cache are fine
    set_transient($cache_key, $times, 6 * DAY_IN_SECONDS);
    return $times;
}

// Fallback if Transitous is down or finds no route: ~20 km/h plus 10 min to the stop
function st_estimate_minutes($from, $to)
{
    return (int) round(st_distance($from, $to) * 3 + 10);
}

function st_travel_minutes($from, $to, $date)
{
    return st_route($from, $to, $date)['minutes'] ?? st_estimate_minutes($from, $to);
}

// Shows just the bar on Google Maps (no route)
function st_map_url($bar_id)
{
    $loc = st_get_location('post', $bar_id);
    return 'https://www.google.com/maps/search/?' . http_build_query([
        'api'   => 1,
        'query' => get_the_title($bar_id) . ', ' . ($loc['address'] ?? ''),
    ]);
}


// ========================
//  RSVP
// ========================

// Only the answer for the next meeting is kept per user
function st_get_rsvp($user_id, $date)
{
    if (get_user_meta($user_id, 'st_rsvp_date', true) !== $date) return '';
    return get_user_meta($user_id, 'st_rsvp_answer', true);
}

function st_set_rsvp($user_id, $date, $answer)
{
    update_user_meta($user_id, 'st_rsvp_date', $date);
    update_user_meta($user_id, 'st_rsvp_answer', $answer === 'yes' ? 'yes' : 'no');
}

function st_attendees($date)
{
    return get_users([
        'meta_query' => [
            ['key' => 'st_rsvp_date', 'value' => $date],
            ['key' => 'st_rsvp_answer', 'value' => 'yes'],
        ],
        'orderby' => 'display_name',
    ]);
}

// Signed one-click links for the invite mail (no login needed)
function st_rsvp_token($user_id, $date, $answer)
{
    return substr(hash_hmac('sha256', "$user_id|$date|$answer", wp_salt('auth')), 0, 32);
}

function st_rsvp_url($user_id, $date, $answer)
{
    return home_url('/?' . http_build_query([
        'st_rsvp' => $answer,
        'u'       => $user_id,
        'd'       => $date,
        't'       => st_rsvp_token($user_id, $date, $answer),
    ]));
}


// ========================
//  Venue
// ========================

// The bar with the smallest total travel time for everyone joining.
// 1. Rank all bars by total straight-line distance (cheap)
// 2. With $date: check the closest bars (setting "Bars prüfen") with real travel times (Öffis or walking)
// Without $date (website preview) only step 1 runs, so page views cause no API calls.
function st_pick_venue($attendees, $date = '')
{
    $people = array_filter(array_map(fn($user) => st_get_location('user', $user->ID), $attendees));
    if (!$people) return 0;

    $bars = [];
    foreach (get_posts(['post_type' => 'bar', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids']) as $bar_id) {
        $loc = st_get_location('post', $bar_id);
        if ($loc) $bars[$bar_id] = $loc;
    }
    if (!$bars) return 0;
    $bars = st_rotate_bars($bars, $date ?: st_next_date());

    $km =array_map(fn($bar) => array_sum(array_map(fn($person) => st_distance($person, $bar), $people)), $bars);
    asort($km);
    if (!$date) return array_key_first($km);

    $best_id    = 0;
    $best_score = INF;

    foreach (array_slice(array_keys($km), 0, st_setting('shortlist')) as $bar_id) {
        $score = array_sum(array_map(fn($person) => st_travel_minutes($person, $bars[$bar_id], $date), $people));
        if ($score < $best_score) {
            $best_score = $score;
            $best_id    = $bar_id;
        }
    }

    return $best_id;
}

// Rotation: drop the bars of the last X meetings. Too few bars left:
// only the last one is dropped, with a single bar it stays.
function st_rotate_bars($bars, $date)
{
    $recent = st_recent_venues($date, st_setting('rotation'));

    $left = array_diff_key($bars, array_flip($recent));
    if ($left) return $left;

    $left = array_diff_key($bars, array_flip(array_slice($recent, 0, 1)));
    return $left ?: $bars;
}

// Bars of the last $count meetings before $date, newest first
function st_recent_venues($date, $count)
{
    global $wpdb;

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT option_name, option_value FROM $wpdb->options
         WHERE option_name LIKE %s AND option_name < %s AND option_value > 0
         ORDER BY option_name DESC LIMIT %d",
        $wpdb->esc_like('st_venue_') . '____-__-__',
        'st_venue_' . $date,
        $count
    ));

    return array_map(fn($row) => (int) $row->option_value, $rows);
}

// Public pages: reachable without login and without the site password
function st_legal_views()
{
    return ['impressum' => 'Impressum', 'datenschutz' => 'Datenschutz'];
}

function st_get_venue($date)
{
    return (int) get_option("st_venue_$date", 0);
}

function st_is_manual_venue($date)
{
    return (bool) get_option("st_venue_manual_$date", false);
}

// Admin picks the bar ($bar_id = 0: back to automatic)
function st_set_manual_venue($date, $bar_id)
{
    if ($bar_id) {
        update_option("st_venue_$date", $bar_id, false);
        update_option("st_venue_manual_$date", 1, false);
    } else {
        delete_option("st_venue_$date");
        delete_option("st_venue_manual_$date");
    }
}


// ========================
//  Messages (?st_msg=)
// ========================

function st_messages()
{
    return [
        'signed_up'       => ['Willkommen! Du bist registriert.', 'success'],
        'signed_up_nonr'  => ['Willkommen! Du bist registriert. Die Hausnummer haben wir weggelassen, die brauchen wir nicht.', 'success'],
        'invalid_zip'     => ['Bitte eine gültige Postleitzahl (5 Ziffern) angeben.', 'error'],
        'login_failed'    => ['Benutzername oder Passwort falsch.', 'error'],
        'missing_fields'  => ['Bitte alle Felder ausfüllen.', 'error'],
        'invalid_email'   => ['Diese E-Mail-Adresse ist ungültig.', 'error'],
        'user_exists'     => ['Benutzername oder E-Mail ist bereits vergeben.', 'error'],
        'weak_password'   => ['Das Passwort muss mindestens 8 Zeichen haben.', 'error'],
        'address_unknown' => ['Diese Straße konnten wir nicht finden. Bitte Straße, PLZ und Ort prüfen.', 'error'],
        'profile_saved'   => ['Profil gespeichert.', 'success'],
        'profile_saved_nonr' => ['Profil gespeichert. Die Hausnummer haben wir weggelassen, die brauchen wir nicht.', 'success'],
        'account_deleted' => ['Dein Konto wurde gelöscht.', 'success'],
        'bar_added'       => ['Bar hinzugefügt.', 'success'],
        'bar_deleted'     => ['Bar gelöscht.', 'success'],
        'rsvp_yes'        => ['Super, du bist dabei!', 'success'],
        'rsvp_no'         => ['Schade, vielleicht nächste Woche.', 'success'],
        'rsvp_invalid'    => ['Dieser Link ist ungültig oder abgelaufen.', 'error'],
        'not_allowed'     => ['Das darfst du leider nicht.', 'error'],
        'invites_sent'    => ['%d Einladungen verschickt.', 'success'],
        'venue_saved'     => ['Ort festgelegt. Die Mail geht automatisch raus.', 'success'],
        'venue_sent'      => ['Ort festgelegt, %d Mails verschickt.', 'success'],
        'venue_nobody'    => ['Ort festgelegt. Noch hat niemand zugesagt, daher keine Mail.', 'success'],
        'venue_auto'      => ['Der Ort wird wieder automatisch berechnet.', 'success'],
        'settings_saved'  => ['Einstellungen gespeichert.', 'success'],
        'settings_order'  => ['„Ort festlegen“ muss nach der Einladung liegen.', 'error'],
    ];
}

// $n fills a %d in the message
function st_back($msg, $view = '', $n = null)
{
    wp_safe_redirect(add_query_arg(array_filter(['st_msg' => $msg, 'view' => $view, 'st_n' => $n], fn($v) => $v !== '' && $v !== null), home_url('/')));
    exit;
}


// ========================
//  Keep Members Out of wp-admin
// ========================

add_filter('show_admin_bar', fn($show) => $show && current_user_can('manage_options'));

add_action('admin_init', function () {
    if (wp_doing_ajax() || current_user_can('manage_options')) return;
    global $pagenow;
    if ($pagenow === 'admin-post.php') return;
    wp_safe_redirect(home_url('/'));
    exit;
});
