# No Bloat Popups

Leichtgewichtiges WordPress Popup-Plugin mit JS-basierter Cookie-Logik für volle Cache-Kompatibilität.

## Features

- **Mehrere Popups** – Jedes Popup ist ein eigener Custom Post Type Eintrag
- **Aktivieren / Deaktivieren** – Globaler An/Aus-Schalter pro Popup
- **Zeitsteuerung** – Automatisch aktivieren und deaktivieren per Datum (JS-seitig, cache-safe)
- **Seitenregeln** – Nur auf bestimmten Seiten zeigen / auf bestimmten Seiten ausblenden (Wildcards möglich)
- **Timer-Trigger** – Popup nach X Sekunden anzeigen
- **Click-Trigger** – Popup nach Klick auf beliebiges Element (CSS-Selektor)
- **Borlabs Cookie Integration** – Wartet auf Borlabs-Consent bevor das Popup erscheint
- **Cookie-Logik komplett in JS** – Kein PHP-Cookie-Lesen, funktioniert mit jedem Server-Cache
- **Inhalt-Felder** – Bild, Headline, Subheadline, Text (WYSIWYG), Button, Custom HTML
- **Custom CSS** – Pro Popup individuelles CSS möglich
- **Debug-Modus** – Detaillierte Konsolen-Ausgaben zur Fehlersuche
- **Accessibility** – `inert`-Attribut, `aria-modal`, ESC-Taste zum Schließen

## Architektur

**Warum JS-Cookies?**  
PHP wird häufig serverseitig gecached. Dabei wird immer eine bestimmte HTML-Version der Seite ausgeliefert. Sind alternative Varianten durch PHP-Funktionen vorhanden (z.B. abhängig von Cookies), wird die gecachte Version ausgeliefert, nicht die aktuelle. JavaScript wird zwar auch gecached, aber die Ausführung erfolgt immer im Browser – daher funktioniert die Cookie-Logik zuverlässig.

## Dateistruktur

```
popup.php              → Plugin-Einstiegspunkt
includes/
  cpt.php              → Custom Post Type Registration
  metaboxes.php        → Admin Meta Boxes (Einstellungen)
  frontend.php         → Frontend HTML-Ausgabe + Script-Enqueue
assets/
  pop.js               → Frontend JS (Cookie, Trigger, Borlabs, Display)
  pop-style.css        → Frontend CSS
  admin-style.css      → Admin CSS
uninstall.php          → Cleanup bei Plugin-Deinstallation
```

## Cookie-Verhalten

- **Timer-Trigger**: Cookie wird gesetzt → Popup wird für X Tage nicht mehr angezeigt
- **Click-Trigger**: Kein Cookie → Popup öffnet sich bei jedem Klick (User-Intent)
