# SEO Check – Handover

## Projekt
- **Lokaler Pfad:** `/Users/lakestudio/localhost/claude/apps/seo-tool/`
- **Stack:** Single `index.html` – Vanilla JS, kein Build-Step, keine Dependencies, kein Backend

---

## Zweck

SEO-relevante Daten einer beliebigen Domain clientseitig auslesen. HTML wird über einen
**öffentlichen CORS-Proxy** geladen und per `DOMParser` ausgewertet. Kein eigener Server.

---

## Features

- **Score** (0–100) aus den bestandenen Checks + Ampel-Ring (grün/accent/rot)
- **Vorschau:** Google-SERP-Snippet + Open-Graph-Social-Card (echtes og:image)
- **Bewertung:** Checkliste mit ok/warn/err (Title-/Description-Länge, genau eine H1,
  Canonical, noindex, Viewport, lang, OG vollständig, Schema vorhanden, Alt-Texte)
- **Meta:** Title, Description (mit Längen-Badge), Robots, Canonical, lang, Viewport,
  Charset, Keywords, Author, Generator, Theme-Color
- **Open Graph** + **Twitter Card** (alle Tags, Bild-/URL-Werte verlinkt)
- **Schema.org (JSON-LD):** erkennt alle `@type` (inkl. `@graph`), formatiertes JSON ausklappbar
- **Überschriften-Struktur:** H1–H6 als eingerückte Outline
- **Hreflang**, **Icons/Favicons**, **Bilder** (Anzahl + ohne Alt)
- **Kopieren** der Kerndaten (Clipboard API)
- **Prefill per `?url=`** → als Bookmarklet/Lesezeichen nutzbar (`…/index.html?url=DOMAIN`)
- Domain-Normalisierung (ergänzt `https://`, lowercased Host)

### Proxys (parallel geracet, `Promise.any`)
```
allorigins-json   https://api.allorigins.win/get?url=   (JSON { contents })
allorigins        https://api.allorigins.win/raw?url=
corsproxy.io      https://corsproxy.io/?url=            (aktuell oft 401 → Key nötig)
codetabs          https://api.codetabs.com/v1/proxy/?quest=
```
- Alle öffentlichen Proxys werden **gleichzeitig** abgefragt – die **erste gültige Antwort gewinnt**
- **Per-Request-Timeout 10s** (AbortController)
- **Wichtig – warum geracet statt Kette:** allorigins `/raw` *hängt* oft bis zum Timeout,
  während `/get` in Millisekunden antwortet. Seriell würde man jedes Mal 12s warten
  (Symptom „lädt und lädt"). Parallel egal, welcher tot ist.
- Die Konsole loggt dabei die *verlierenden* Racer (CORS/401) – harmlos, `Promise.any`
  ignoriert sie, solange einer liefert.

### Eigener Proxy – AKTIV
```
const CUSTOM_PROXY = "https://lake-studio.de/tools/proxy.php?url=";
```
`proxy.php` (im Repo) liegt live unter `lake-studio.de/tools/proxy.php` (cURL, Redirects,
SSRF-Schutz). Wird **allein zuerst** versucht (nicht
geracet, damit die URL nicht doch an die öffentlichen Dienste geht); erst bei Fehler
fallen die öffentlichen ein. Vorteil: zuverlässig + die abgefragte URL verlässt nicht
den fremden Proxy (Datenschutz).
Minimal-PHP auf eigenem Hosting:
```php
<?php
// proxy.php?url=https://example.com/
header( 'Access-Control-Allow-Origin: *' );
$url = isset( $_GET['url'] ) ? $_GET['url'] : '';

if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) { http_response_code( 400 ); exit; }

$ctx = stream_context_create([ 'http' => [ 'timeout' => 12, 'user_agent' => 'Mozilla/5.0' ] ]);
echo file_get_contents( $url, false, $ctx );
```
Dann: `const CUSTOM_PROXY = "https://deine-domain.de/proxy.php?url=";`

---

## Grenzen
- **SPAs/JS-gerendert:** Proxy liefert rohes HTML ohne JS-Ausführung → clientseitig
  nachgeladene Meta/Inhalte fehlen
- **Datenschutz:** Bei öffentlichem Proxy sieht der Dienst die angefragte URL –
  nicht für interne/geschützte Seiten (dann eigenen Proxy nutzen)

---

## Design System (LS-CI, identisch zu den anderen Apps)
Dark Theme, Accent `#d4a55a`, DM Serif Display (h1) + DM Sans + Mono für Werte.
CSS Custom Properties & Komponenten wie bei dns-lookup / den übrigen Tools.

---

## Code-Style (LS-Standard)
- **Tabs**, Spaces inside Parens `foo( 'x' )`, Space nach `!` (`if ( ! el ) return;`)
- Callbacks `});` (kein Space vor `}`), Kommentare Englisch, Vanilla JS
- Alles in einer `index.html` (inline `<style>` + `<script>`)

---

## Lokal testen
```bash
cd /Users/lakestudio/localhost/claude/apps/seo-tool
python3 -m http.server 8791
# → http://localhost:8791/index.html
```
Direkt per `file://` funktioniert der Proxy-Fetch u. U. nicht (Origin) – über http testen.

---

## Deployment
```bash
# GitHub Pages: main branch, root /
git add index.html
git commit -m "..."
git push
```
