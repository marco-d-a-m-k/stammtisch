<?php

// ========================
//  Stammtisch Finder: Settings (Einstellungen → Stammtisch)
// ========================

function st_setting_fields()
{
    return [
        'interval'      => ['label' => 'Turnus', 'type' => 'number', 'default' => 1, 'min' => 1, 'max' => 8, 'unit' => 'Wochen', 'hint' => '1 = jede Woche, 2 = alle zwei Wochen usw.'],
        'anchor_date'   => ['label' => 'Nächster Termin', 'type' => 'date', 'default' => '2026-01-01', 'hint' => 'Ab diesem Datum geht es im Turnus weiter. Der Wochentag ergibt sich aus dem Datum.'],
        'invite_time'   => ['label' => 'Einladung (am Termin)', 'type' => 'time', 'default' => '10:00', 'hint' => 'Mail „Bist du heute dabei?“ an alle.'],
        'announce_time' => ['label' => 'Ort festlegen (am Termin)', 'type' => 'time', 'default' => '16:00', 'hint' => 'Ort wird berechnet und an alle Zusagen verschickt.'],
        'meet_time'     => ['label' => 'Treffen um', 'type' => 'time', 'default' => '19:00', 'hint' => 'Fahrzeiten werden mit Ankunft zu dieser Uhrzeit berechnet.'],
        'max_walk'      => ['label' => 'Max. Gehzeit', 'type' => 'number', 'default' => 60, 'min' => 0, 'max' => 180, 'unit' => 'Min.', 'hint' => 'Längere Fußwege werden nicht berücksichtigt. 0 = nur Öffis.'],
        'walk_bonus'    => ['label' => 'Gehen bevorzugen', 'type' => 'number', 'default' => 0, 'min' => 0, 'max' => 60, 'unit' => 'Min.', 'hint' => 'Zu Fuß gewinnt auch, wenn es bis zu so viele Minuten langsamer ist als Öffis.'],
        'rotation'      => ['label' => 'Rotation', 'type' => 'number', 'default' => 3, 'min' => 1, 'max' => 20, 'unit' => 'Termine', 'hint' => 'Eine Bar setzt nach einem Treffen so viele Termine aus. Gibt es zu wenige Bars, wird nur die letzte ausgeschlossen.'],
        'shortlist'     => ['label' => 'Bars prüfen', 'type' => 'number', 'default' => 5, 'min' => 1, 'max' => 20, 'unit' => 'Bars', 'hint' => 'Nur die X nächsten Bars (Luftlinie) werden per Routing geprüft. Mehr = genauer, aber mehr Anfragen.'],
        'gate_password' => ['label' => 'Neues Seitenpasswort', 'type' => 'password', 'default' => '', 'group' => 'Zugang', 'hint' => 'Leer lassen, um das aktuelle zu behalten. Gespeichert wird nur ein Hash. Danach müssen alle (nicht Eingeloggten) es einmal neu eingeben.'],
        'imprint'       => ['label' => 'Name und Anschrift', 'type' => 'textarea', 'default' => '', 'group' => 'Impressum', 'hint' => 'Erscheint im Impressum und im Datenschutz als verantwortliche Person.'],
        'imprint_email' => ['label' => 'E-Mail', 'type' => 'email', 'default' => '', 'hint' => 'Kontakt für Impressum und Datenschutz.'],
    ];
}

// Value shown in the forms: "Nächster Termin" shows the actual next date, not the old start date
function st_setting_value($key)
{
    return $key === 'anchor_date' ? st_next_date() : st_setting($key);
}

function st_setting($key)
{
    static $saved = null;
    $saved ??= (array) get_option('st_settings', []);
    return $saved[$key] ?? st_setting_fields()[$key]['default'];
}

// One form control, used by the WP-Admin page and the admin view on the site
// $attrs: extra HTML attributes (already escaped), e.g. id and class
function st_setting_input($key, $field, $attrs = '')
{
    $attrs = 'name="st_settings[' . esc_attr($key) . ']" ' . $attrs;

    if ($field['type'] === 'textarea') {
        echo '<textarea ' . $attrs . ' rows="4">' . esc_textarea(st_setting_value($key)) . '</textarea>';
    } elseif ($field['type'] === 'password') {
        echo '<input type="password" ' . $attrs . ' autocomplete="new-password" minlength="4">';
    } else {
        $extra = $field['type'] === 'number' ? ' min="' . (int) $field['min'] . '" max="' . (int) $field['max'] . '" required' : '';
        $extra .= in_array($field['type'], ['time', 'date'], true) ? ' required' : '';
        echo '<input type="' . esc_attr($field['type']) . '" ' . $attrs . ' value="' . esc_attr(st_setting_value($key)) . '"' . $extra . '>';
    }
}

add_action('admin_menu', function () {
    add_options_page('Stammtisch', 'Stammtisch', 'manage_options', 'stammtisch', 'st_settings_page');
});

add_action('admin_init', function () {
    register_setting('stammtisch', 'st_settings', [
        'type'              => 'array',
        'sanitize_callback' => 'st_sanitize_settings',
    ]);
});

function st_sanitize_settings($input)
{
    $clean = [];
    foreach (st_setting_fields() as $key => $field) {
        $value = $input[$key] ?? $field['default'];

        if ($field['type'] === 'time') {
            $clean[$key] = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : $field['default'];
        } elseif ($field['type'] === 'date') {
            $parts = array_map('intval', explode('-', (string) $value));
            $clean[$key] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && checkdate($parts[1], $parts[2], $parts[0]) ? $value : st_setting($key);
        } elseif ($field['type'] === 'textarea') {
            $clean[$key] = sanitize_textarea_field($value);
        } elseif ($field['type'] === 'email') {
            $clean[$key] = sanitize_email($value);
        } elseif ($field['type'] === 'number') {
            $clean[$key] = max($field['min'], min($field['max'], (int) $value));
        }
    }

    if ($clean['announce_time'] <= $clean['invite_time']) {
        add_settings_error('st_settings', 'order', '„Ort festlegen“ muss nach der Einladung liegen.');
        return get_option('st_settings', []);
    }

    // Never stored in plain text: only the hash, in its own option (gate.php)
    $password = (string) ($input['gate_password'] ?? '');
    if ($password !== '') update_option('st_gate_hash', password_hash($password, PASSWORD_DEFAULT), false);

    return $clean;
}

function st_settings_page()
{
?>
    <div class="wrap">
        <h1>Stammtisch</h1>
        <?php settings_errors('st_settings') ?>

        <form method="post" action="options.php">
            <?php settings_fields('stammtisch') ?>

            <table class="form-table" role="presentation">
                <?php foreach (st_setting_fields() as $key => $field) : ?>
                    <?php if (isset($field['group'])) : ?>
                        <tr>
                            <th colspan="2"><h2><?= esc_html($field['group']) ?></h2></th>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th scope="row"><label for="st_<?= esc_attr($key) ?>"><?= esc_html($field['label']) ?></label></th>
                        <td>
                            <?php st_setting_input($key, $field, 'id="st_' . esc_attr($key) . '" class="' . ($field['type'] === 'number' ? 'small-text' : 'regular-text') . '"') ?>
                            <?= esc_html($field['unit'] ?? '') ?>
                            <p class="description"><?= esc_html($field['hint']) ?></p>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <?php submit_button() ?>
        </form>
    </div>
<?php
}


// ========================
//  Address on the user screen (Benutzer → Bearbeiten)
// ========================

foreach (['show_user_profile', 'edit_user_profile'] as $st_hook) {
    add_action($st_hook, function ($user) {
        if (!current_user_can('edit_user', $user->ID)) return;
        $loc = st_get_location('user', $user->ID);
?>
        <h2>Stammtisch</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="st_address">Adresse</label></th>
                <td>
                    <input type="text" id="st_address" name="st_address" value="<?= esc_attr($loc['address'] ?? '') ?>" class="regular-text">
                    <p class="description">
                        Wird beim Speichern neu in Koordinaten umgewandelt (ohne Hausnummer).
                        <?php if ($loc) : ?>Aktuell: <?= esc_html(round($loc['lat'], 5) . ', ' . round($loc['lng'], 5)) ?><?php endif; ?>
                    </p>
                </td>
            </tr>
        </table>
<?php
    });
}

foreach (['personal_options_update', 'edit_user_profile_update'] as $st_hook) {
    add_action($st_hook, function ($user_id) {
        if (!current_user_can('edit_user', $user_id) || !isset($_POST['st_address'])) return;

        $address = sanitize_text_field(wp_unslash($_POST['st_address']));
        $current = st_get_location('user', $user_id);
        if (!$address || ($current && $current['address'] === $address)) return;

        $geo = st_geocode($address, true);
        if ($geo) {
            st_set_location('user', $user_id, $address, $geo);
        } else {
            set_transient('st_address_error_' . get_current_user_id(), $address, 60);
        }
    });
}

// Address not found: say so on the next page load
add_action('admin_notices', function () {
    $address = get_transient('st_address_error_' . get_current_user_id());
    if ($address === false) return;
    delete_transient('st_address_error_' . get_current_user_id());
    echo '<div class="notice notice-error"><p>Adresse nicht gefunden, nicht gespeichert: ' . esc_html($address) . '</p></div>';
});
