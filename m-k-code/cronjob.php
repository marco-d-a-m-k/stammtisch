<?php

// ========================
//  Stammtisch Finder: Weekly Mails
// ========================
//
// WP-Cron ticks every 15 minutes and checks the time itself. Each step runs
// once per meeting day (guarded by an option), so late or double ticks are safe.
// WP-Cron only fires on page visits, so on the live server call
// wp-cron.php from a real cron job every few minutes.

add_filter('cron_schedules', function ($schedules) {
    $schedules['st_quarter_hour'] = ['interval' => 15 * MINUTE_IN_SECONDS, 'display' => 'Alle 15 Minuten'];
    return $schedules;
});

add_action('init', function () {
    if (!wp_next_scheduled('st_tick')) {
        wp_schedule_event(time(), 'st_quarter_hour', 'st_tick');
    }
});

add_action('switch_theme', fn() => wp_clear_scheduled_hook('st_tick'));

add_action('st_tick', function () {
    $now  = st_now();
    $date = $now->format('Y-m-d');
    if ($date !== st_next_date()) return; // no meeting today
    $time = $now->format('H:i');

    // No point asking once the venue is already decided
    if ($time >= st_setting('invite_time') && $time < st_setting('announce_time')) st_send_invites($date);
    if ($time >= st_setting('announce_time')) st_announce($date);
});


// ---- Meeting day morning: "Bist du heute dabei?" ----
function st_send_invites($date, $force = false)
{
    // add_option fails if the key exists → once-per-meeting lock.
    // Manual admin runs ($force) skip the check; on the day itself they still
    // set the lock, so the automatic invite doesn't go out a second time.
    $locked = !add_option("st_invited_$date", time(), '', false);
    if ($locked && !$force) return 0;

    $day  = st_day_label($date);
    $sent = 0;
    foreach (get_users() as $user) {
        $yes = esc_url(st_rsvp_url($user->ID, $date, 'yes'));
        $no  = esc_url(st_rsvp_url($user->ID, $date, 'no'));

        $html = '<p>Hallo ' . esc_html($user->display_name) . ',</p>'
            . '<p>' . esc_html(ucfirst($day)) . ' Abend ist Stammtisch. Bist du dabei?</p>'
            . '<p style="margin:28px 0;">'
            . st_mail_button($yes, 'Ja')
            . st_mail_button($no, 'Nein')
            . '</p>'
            . '<p class="st-muted" style="' . ST_MAIL_MUTED . '">' . esc_html(ucfirst($day)) . ' um ' . st_setting('announce_time') . ' Uhr bekommst du eine Mail, wo es hingeht.</p>';

        $sent += (int) st_mail($user, 'Stammtisch ' . $day . '? Ja oder Nein', $html);
    }

    // Sent early for a later date: keep that day's automatic invite
    if (!$locked && $date !== st_now()->format('Y-m-d')) delete_option("st_invited_$date");

    return $sent;
}


// ---- Meeting day 16:00: tell everyone joining where to go ----
// Uses the bar the admin picked, otherwise calculates the best one.
// Each person gets the mail once per bar, so a later run only reaches
// people who said yes after the last mail.
function st_announce($date, $force = false)
{
    if (!$force && !add_option("st_announced_$date", time(), '', false)) return 0;

    $attendees = st_attendees($date);
    if (!$attendees) return 0;

    $bar_id = st_is_manual_venue($date) ? st_get_venue($date) : 0;
    if (!$bar_id || !st_get_location('post', $bar_id)) $bar_id = st_pick_venue($attendees, $date);
    update_option("st_venue_$date", $bar_id, false);

    // Who already got the mail for this bar (other bar → everyone again)
    $notified = (array) get_option("st_notified_$date", []);
    $notified = ($notified['bar'] ?? 0) === $bar_id ? $notified['users'] : [];

    $day   = st_day_label($date);
    $names = implode(', ', array_map(fn($user) => esc_html($user->display_name), $attendees));

    if ($bar_id) {
        $bar     = st_get_location('post', $bar_id);
        $smoking = get_post_meta($bar_id, 'st_smoking', true) === 'yes' ? 'Raucherbar' : 'Nichtraucher';
        $subject = 'Stammtisch ' . $day . ': ' . get_the_title($bar_id);
        $body    = '<p>' . esc_html($day) . ' Abend treffen wir uns hier:</p>'
            . '<p style="margin:24px 0 8px;font-size:32px;line-height:1.05;font-weight:700;font-style:italic;letter-spacing:-0.02em;">' . esc_html(get_the_title($bar_id)) . '</p>'
            . '<p style="margin:0 0 24px;">' . esc_html($bar['address']) . '<br>' . $smoking . '</p>'
            . '<p style="margin:0 0 24px;">' . st_mail_button(esc_url(st_map_url($bar_id)), 'Auf der Karte zeigen') . '</p>'
            . '{{travel}}';
    } else {
        $subject = 'Stammtisch ' . $day . ': Ort offen';
        $body    = '<p>leider konnten wir keinen Ort berechnen: Es sind noch keine Bars eingetragen.</p>'
            . '<p>' . st_mail_button(esc_url(add_query_arg('view', 'bars', home_url('/'))), 'Bar eintragen') . '</p>';
    }

    $sent = 0;
    foreach ($attendees as $user) {
        if (in_array($user->ID, $notified, true)) continue;

        // Personal travel time (cached from st_pick_venue, or fetched now)
        $home   = $bar_id ? st_get_location('user', $user->ID) : null;
        $route  = $home ? st_route($home, $bar, $date) : null;
        $travel = $route
            ? '<p>Dein Weg: ca. ' . $route['minutes'] . ' Min. ' . ($route['mode'] === 'walk' ? 'zu Fuß' : 'mit Öffis') . '</p>'
            : '';

        $html = '<p>Hallo ' . esc_html($user->display_name) . ',</p>'
            . str_replace('{{travel}}', $travel, $body)
            . '<p class="st-muted" style="' . ST_MAIL_MUTED . '">Dabei: ' . $names . '</p>'
            . '<p class="st-muted" style="' . ST_MAIL_MUTED . 'font-size:12px;">Fahrzeiten: <a href="https://transitous.org/sources/" style="color:inherit;">Transitous</a>, Kartendaten © <a href="https://www.openstreetmap.org/copyright" style="color:inherit;">OpenStreetMap</a></p>';

        if (st_mail($user, $subject, $html)) {
            $sent++;
            if ($bar_id) $notified[] = $user->ID;
        }
    }

    update_option("st_notified_$date", ['bar' => $bar_id, 'users' => $notified], false);
    return $sent;
}


// ========================
//  Mail Helpers
// ========================

define('ST_MAIL_MUTED', 'color:#6f6c70;');

function st_mail($user, $subject, $html)
{
    static $template = null;
    $template ??= file_get_contents(THEME_DIR . 'm-k-code/e-mail-template.html');

    $body = strtr($template, [
        '{{title}}'   => esc_html($subject),
        '{{content}}' => $html,
        '{{site}}'    => esc_html(get_bloginfo('name')),
        '{{url}}'     => esc_url(home_url('/')),
    ]);

    $from_name = fn() => 'Stammtischinfo';
    add_filter('wp_mail_from_name', $from_name);
    $ok = wp_mail($user->user_email, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    remove_filter('wp_mail_from_name', $from_name);

    return $ok;
}

// Like .cta on the site: uppercase, round, inset shadow (border = fallback for Gmail)
function st_mail_button($url, $label)
{
    return '<a href="' . $url . '" class="st-btn" style="display:inline-block;margin:6px;padding:18px 32px;border-radius:100px;'
        . 'border:1px solid rgba(25,25,25,0.15);box-shadow:inset 0 0 17px 0 rgba(0,0,0,0.25);'
        . 'color:#191919;text-decoration:none;text-transform:uppercase;font-weight:700;font-size:16px;letter-spacing:0.02em;">'
        . esc_html($label) . '</a>';
}
