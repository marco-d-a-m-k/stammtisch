# Stammtisch Finder

A WordPress theme for a small, private *Stammtisch*: a regular get-together at a bar. It asks everyone by mail whether they're coming, then picks the bar that's easiest to reach for the people who said yes, and mails them where to go.

The interface is in German. Code and comments are in English.

## How it works

1. **Invite.** On meeting day at the invite time (default 10:00), every member gets a mail: *„Bist du heute dabei?“* with one-click **Ja / Nein** buttons. Members can also answer on the site.
2. **Pick the bar.** At the announce time (default 16:00), the bar is chosen:
   - Bars used in the last *X* meetings are skipped (rotation, default 3).
   - The *N* bars closest to everyone (straight-line distance) are shortlisted.
   - For each shortlisted bar, real travel times (public transport or walking, arriving at the meeting time) are fetched from [Transitous](https://transitous.org) and summed over all attendees. The lowest total wins.
3. **Announce.** Everyone who said yes gets a mail with the bar, a map link and their own travel time. Anyone who says yes later still gets the mail on the next run.

An admin can also pick the bar by hand, or send the invite early.

## Features

- Sign-up with username, e-mail, password and home address
- Members add bars (with smoking / non-smoking)
- Shared site password in front of everything (except the Ja/Nein mail links and the legal pages)
- Admin view on the site plus a settings page in WP-Admin (*Einstellungen → Stammtisch*)
- Meeting rhythm (every 1–8 weeks), times, walking limits, rotation and shortlist size are all configurable
- Impressum and Datenschutz pages, filled from the settings
- HTML mails that follow the mail app's light or dark mode
- Members can delete their own account

## Privacy

- **Home addresses are sent without the house number.** Only street, postcode and city are geocoded, so stored coordinates point to the middle of the street.
- **Only coordinates go out.** Transitous is the only outside service. It receives addresses (street level) for geocoding, and coordinates and meeting times for routing. Names and e-mail addresses are never sent. All requests come from the server, not the visitor's browser.
- **Requests are kept to a minimum.** Geocoding is limited to 1 request per second and cached for 30 days. Routes are cached for 6 days. Normal page views make no outside requests.
- **No tracking, no analytics, no external fonts or scripts.**

## Requirements

- WordPress 6.x
- PHP 7.4+ (8.x recommended)
- MySQL / MariaDB
- Working `wp_mail()`. On a real server, use an SMTP plugin so mails don't land in spam.
- Node 18+ (only to rebuild CSS/JS)

## Setup

1. **Add the font.** The theme uses *ABC Areal* by Dinamo, which isn't included in this repo because of its licence. Download it for free at [abcdinamo.com/free/areal](https://abcdinamo.com/free/areal) and put the variable font at `load/fonts/ABCArealSuperfamilyVariable.woff2`. Without it, the site falls back to a system font.
2. Copy the theme into `wp-content/themes/` and activate it. The app always runs on the front page, whatever is set under *Einstellungen → Lesen*.
3. Go to *Einstellungen → Stammtisch* and set:
   - **Neues Seitenpasswort**. Until one is set, nobody gets past the password screen. Admins can always log in via `/wp-admin`.
   - **Impressum** (name, address, e-mail)
   - the next meeting date and the times
4. **Set up a real cron job.** WP-Cron only runs on page visits, so the mails may arrive late or not at all. Call `wp-cron.php` every few minutes:

   ```cron
   */5 * * * * curl -s https://example.com/wp-cron.php > /dev/null
   ```

   Then disable visit-based cron in `wp-config.php`:

   ```php
   define( 'DISABLE_WP_CRON', true );
   ```

## Development

```bash
npm install
npm run watch   # rebuild on change
npm run build   # production build into build/
```

| Path | What's in it |
|---|---|
| `m-k-code/stammtisch.php` | Dates, geocoding, routing, choosing the bar, rotation, messages |
| `m-k-code/cronjob.php` | Scheduled invite and announce mails, mail helpers |
| `m-k-code/forms.php` | Form handlers (sign-up, profile, bars, RSVP, admin actions) |
| `m-k-code/settings.php` | Settings fields, WP-Admin page, address field on the user screen |
| `m-k-code/gate.php` | Site password |
| `m-k-code/e-mail-template.html` | Mail layout |
| `parts/components/` | Page parts: login, info, profile, bars, admin, legal pages |
| `src/` | CSS and JS sources (built into `build/`) |

## Credits

Travel times and geocoding: [Transitous](https://transitous.org/sources/). Map data: © [OpenStreetMap](https://www.openstreetmap.org/copyright) contributors.

## License

[GPL-2.0-or-later](LICENSE)
