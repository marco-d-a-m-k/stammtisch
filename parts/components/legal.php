<?php // Impressum + Datenschutz. $view is 'impressum' or 'datenschutz'. Data: admin settings (Impressum).
$legal_contact = trim(st_setting('imprint'));
$legal_email   = st_setting('imprint_email');
?>

<section class="sp-6">

    <?php if ($view === 'impressum') : ?>

        <h1 class="fs-10">Impressum</h1>

        <div class="card sp-4">
            <?php if ($legal_contact) : ?>
                <p><?= nl2br(esc_html($legal_contact)) ?></p>
            <?php else : ?>
                <p>Noch nicht eingetragen.</p>
            <?php endif; ?>
            <?php if ($legal_email) : ?>
                <p><a href="mailto:<?= esc_attr($legal_email) ?>"><?= esc_html($legal_email) ?></a></p>
            <?php endif; ?>
        </div>

    <?php else : ?>

        <h1 class="fs-10">Datenschutz</h1>

        <div class="card sp-4">
            <h2>Wer ist verantwortlich?</h2>
            <p><?= $legal_contact ? esc_html(preg_replace('/\s*\n\s*/', ', ', $legal_contact)) : 'Siehe Impressum.' ?><?= $legal_email ? ' · <a href="mailto:' . esc_attr($legal_email) . '">' . esc_html($legal_email) . '</a>' : '' ?></p>
        </div>

        <div class="card sp-4">
            <h2>Was wird gespeichert?</h2>
            <p>Wenn du dich registrierst: Benutzername, E-Mail-Adresse, Passwort (verschlüsselt) und deine Adresse. Dazu kommen deine Zu- und Absagen und die Bars, die du einträgst.</p>
            <p>Andere Mitglieder sehen deinen Namen und ob du dabei bist. Deine Adresse und E-Mail sieht niemand außer dem Admin.</p>
        </div>

        <div class="card sp-4">
            <h2>Wofür?</h2>
            <p>Nur für den Stammtisch: Du bekommst Einladungen und den Treffpunkt per Mail. Aus den Adressen aller Zusagen wird die Bar berechnet, die für alle am besten liegt.</p>
        </div>

        <div class="card sp-4">
            <h2>Wer bekommt Daten?</h2>
            <p>Um Adressen in Koordinaten umzuwandeln und Fahrzeiten zu berechnen, nutzt der Server den freien Dienst Transitous. Von deiner Adresse gehen nur Straße, PLZ und Ort raus, ohne Hausnummer. Transitous bekommt weder deinen Namen noch deine E-Mail-Adresse, und die Anfrage kommt vom Server, nicht von deinem Gerät.</p>
            <p>Der Link „Auf der Karte zeigen“ öffnet Google Maps. Dabei gilt der Datenschutz von Google, aber übergeben wird nur die Adresse der Bar.</p>
            <p>Sonst werden keine Daten weitergegeben.</p>
        </div>

        <div class="card sp-4">
            <h2>Cookies</h2>
            <p>Nur zwei, beide technisch nötig: eins, damit du eingeloggt bleibst, und eins, damit du das Seitenpasswort nicht jedes Mal eingeben musst (90 Tage).</p>
        </div>

        <div class="card sp-4">
            <h2>Löschen und deine Rechte</h2>
            <p>Unter Profil kannst du dein Konto jederzeit selbst löschen. Dann sind deine Daten, Adresse und Zusagen weg. Eingetragene Bars bleiben für die anderen erhalten.</p>
            <p>Du kannst Auskunft über deine Daten verlangen und sie berichtigen oder löschen lassen. Beschweren kannst du dich bei der Berliner Beauftragten für Datenschutz und Informationsfreiheit.</p>
        </div>

    <?php endif; ?>

</section>