<?php
/* =========================================================================
   Søgeindeks · Online — fælles funktioner
   Bruges af index.php, upload.php, admin.php og login.php.
   Du skal normalt ikke rette i denne fil. Indstillinger står i config.php.
   ========================================================================= */

declare(strict_types=1);
require __DIR__ . '/config.php';

const MARKER        = 'lokalt-soegeindeks-genereret';
const STATISK_NAVN  = 'soegeside.html';   // offline-udgaven, der ligger i filer/
const RESERVEREDE   = ['soegeside.html', 'index.html', 'index.htm'];
const HOVED_BYTES   = 500 * 1024;
const HALE_BYTES    = 500 * 1024;
const DC_FELTER     = [
    'title','creator','subject','description','publisher','contributor',
    'date','type','format','identifier','source','language','relation',
    'coverage','rights'
];
const MAKS_FORSOEG  = 5;        // forkerte logins før pause
const PAUSE_SEK     = 15 * 60;  // pausens længde

define('FIL_MAPPE',     __DIR__ . '/filer');
define('DATA_MAPPE',    __DIR__ . '/data');
define('PAPIRKURV',     DATA_MAPPE . '/papirkurv');
define('INDEKS_FIL',    DATA_MAPPE . '/indeks.json');
define('UPLOAD_LOG',    DATA_MAPPE . '/uploads.json');
define('HAENDELSER',    DATA_MAPPE . '/haendelser.log');
define('INDSTILLINGER', DATA_MAPPE . '/indstillinger.json');
define('LOGIN_FORSOEG', DATA_MAPPE . '/login-forsoeg.json');
define('FORENINGER_FIL', DATA_MAPPE . '/foreninger.json');   // registret over deltagende foreninger

sikr_mapper();


/* =========================================================================
   Del 1 · Mapper og beskyttelse
   Opretter mapperne og deres beskyttelsesfiler første gang, så det ikke
   gør noget, hvis .htaccess-filerne ikke kom med over.
   ========================================================================= */

function sikr_mapper(): void
{
    foreach ([FIL_MAPPE, DATA_MAPPE, PAPIRKURV] as $m) {
        if (!is_dir($m)) @mkdir($m, 0755, true);
    }
    $spaerret = "# Ingen adgang udefra til denne mappe\n"
        . "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n"
        . "<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n";
    if (!is_file(DATA_MAPPE . '/.htaccess')) @file_put_contents(DATA_MAPPE . '/.htaccess', $spaerret);

    if (!is_file(FIL_MAPPE . '/.htaccess')) {
        @file_put_contents(FIL_MAPPE . '/.htaccess',
            "# Kun HTML-filer hører til her. Programfiler må aldrig kunne køre.\n"
            . "<FilesMatch \"\\.(php[0-9]?|phtml|phar|pl|py|cgi|sh|shtml)$\">\n"
            . "  <IfModule mod_authz_core.c>\n    Require all denied\n  </IfModule>\n"
            . "  <IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n  </IfModule>\n"
            . "</FilesMatch>\n");
    }
    /* Den, der åbner /filer/ direkte, sendes videre til søgesiden. */
    if (!is_file(FIL_MAPPE . '/index.html')) {
        @file_put_contents(FIL_MAPPE . '/index.html',
            "<!DOCTYPE html><meta charset=\"utf-8\"><meta http-equiv=\"refresh\" content=\"0;url=../\">"
            . "<title>Søgeside</title><a href=\"../\">Gå til søgesiden</a>\n");
    }
}


/* =========================================================================
   Del 2 · Små hjælpere
   ========================================================================= */

function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function laes_json(string $fil, $standard)
{
    if (!is_file($fil)) return $standard;
    $d = json_decode((string)file_get_contents($fil), true);
    return is_array($d) ? $d : $standard;
}

/* Skriver først til en midlertidig fil og bytter den så ind, så en halvt
   skrevet fil aldrig kan blive læst. */
function skriv_json(string $fil, $data): bool
{
    $tmp = $fil . '.tmp' . bin2hex(random_bytes(4));
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false || file_put_contents($tmp, $json) === false) return false;
    return rename($tmp, $fil);
}

/* Sørger for, at to personer der lægger op samtidig ikke overskriver
   hinandens ændringer i indekset. */
function med_laas(callable $fn)
{
    $fh = fopen(DATA_MAPPE . '/.laas', 'c');
    flock($fh, LOCK_EX);
    try { return $fn(); }
    finally { flock($fh, LOCK_UN); fclose($fh); }
}

function dansk_tid(int $ts, bool $medKlokken = true): string
{
    static $mdr = ['januar','februar','marts','april','maj','juni','juli',
                   'august','september','oktober','november','december'];
    $s = date('j', $ts) . '. ' . $mdr[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
    return $medKlokken ? $s . ', ' . date('H.i', $ts) : $s;
}

function menneske_str(int $b): string
{
    if ($b >= 1048576) return str_replace('.', ',', (string)round($b / 1048576, 1)) . ' MB';
    if ($b >= 1024)    return (string)round($b / 1024) . ' kB';
    return $b . ' byte';
}

function ini_bytes(string $v): int
{
    $v = trim($v);
    $n = (int)$v;
    switch (strtolower(substr($v, -1))) {
        case 'g': $n *= 1024;
        case 'm': $n *= 1024;
        case 'k': $n *= 1024;
    }
    return $n;
}

function maks_upload(): int
{
    $a = ini_bytes((string)ini_get('upload_max_filesize'));
    $b = ini_bytes((string)ini_get('post_max_size'));
    return ($a && $b) ? min($a, $b) : max($a, $b);
}

function log_haendelse(string $bruger, string $tekst): void
{
    @file_put_contents(HAENDELSER, date('Y-m-d H:i') . "\t" . $bruger . "\t" . $tekst . "\n",
        FILE_APPEND | LOCK_EX);
}

function seneste_haendelser(int $antal): array
{
    if (!is_file(HAENDELSER)) return [];
    $linjer = file(HAENDELSER, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $ud = [];
    foreach (array_reverse(array_slice($linjer, -$antal)) as $l) {
        $d = explode("\t", $l, 3);
        if (count($d) === 3) $ud[] = ['tid' => $d[0], 'bruger' => $d[1], 'tekst' => $d[2]];
    }
    return $ud;
}

function indstillinger(): array
{
    global $STANDARD_TITEL, $STANDARD_UNDERTITEL;
    $i = laes_json(INDSTILLINGER, []);
    return [
        'titel'      => (string)($i['titel'] ?? $STANDARD_TITEL),
        'undertitel' => (string)($i['undertitel'] ?? $STANDARD_UNDERTITEL),
    ];
}


/* =========================================================================
   Del 2b · Registret over foreninger
   data/foreninger.json fortæller, hvilke foreninger der er med i portalen,
   og hvilken udgiver (dc.publisher) der hører til hver af dem. Forsiden
   bruger det til felterne, søgesiden til adresser som ?forening=rtv.
   ========================================================================= */

function hent_foreninger(): array
{
    $d = laes_json(FORENINGER_FIL, []);
    $ud = [];
    foreach ((is_array($d['foreninger'] ?? null) ? $d['foreninger'] : []) as $f) {
        if (!is_array($f)) continue;
        $noegle  = strtolower(trim((string)($f['noegle'] ?? '')));
        $udgiver = trim((string)($f['udgiver'] ?? ''));
        if (!preg_match('/^[a-z0-9-]{1,30}$/', $noegle) || $udgiver === '' || isset($ud[$noegle])) continue;

        $hjemmeside = trim((string)($f['hjemmeside'] ?? ''));
        if (!preg_match('#^https?://#i', $hjemmeside)) $hjemmeside = '';

        /* Logoet skal ligge på serveren, fx grafik/rtv.png. */
        $logo = trim((string)($f['logo'] ?? ''));
        if (!preg_match('#^[A-Za-z0-9_\-/.]+\.(png|jpe?g|gif|webp|svg)$#i', $logo)
            || strpos($logo, '..') !== false || $logo[0] === '/' || !is_file(__DIR__ . '/' . $logo)) {
            $logo = '';
        }

        $navn = trim((string)($f['navn'] ?? ''));
        $ud[$noegle] = [
            'noegle'      => $noegle,
            'udgiver'     => $udgiver,
            'navn'        => $navn !== '' ? $navn : $udgiver,
            'slags'       => trim((string)($f['slags'] ?? '')),
            'forkortelse' => mb_substr(trim((string)($f['forkortelse'] ?? '')), 0, 4, 'UTF-8'),
            'beskrivelse' => trim((string)($f['beskrivelse'] ?? '')),
            'hjemmeside'  => $hjemmeside,
            'logo'        => $logo,
        ];
    }
    return $ud;
}

function portal_indstillinger(): array
{
    $d = laes_json(FORENINGER_FIL, []);
    $p = is_array($d['portal'] ?? null) ? $d['portal'] : [];
    $mail = trim((string)($p['kontakt_email'] ?? ''));
    $pakke = trim((string)($p['startpakke'] ?? ''));
    return [
        'titel'         => trim((string)($p['titel'] ?? '')) ?: 'Lokalhistorisk Søgeportal',
        'undertitel'    => trim((string)($p['undertitel'] ?? '')),
        'kontakt_navn'  => trim((string)($p['kontakt_navn'] ?? '')),
        'kontakt_email' => filter_var($mail, FILTER_VALIDATE_EMAIL) ? $mail : '',
        'startpakke'    => preg_match('#^https?://#i', $pakke) ? $pakke : '',
    ];
}


/* =========================================================================
   Del 3 · Filnavne
   Et filnavn skal kunne bruges direkte i et link, og det samme navn skal
   altid give den samme fil, så en ny upload overskriver den gamle.
   ========================================================================= */

function rens_filnavn(string $navn): ?string
{
    $navn = basename(str_replace('\\', '/', $navn));
    if (!preg_match('//u', $navn)) return null;                       // ikke gyldig UTF-8
    if (class_exists('Normalizer')) {                                  // Mac skriver æøå anderledes
        $n = Normalizer::normalize($navn, Normalizer::FORM_C);
        if (is_string($n)) $navn = $n;
    }
    $navn = trim($navn);
    if ($navn === '' || $navn[0] === '.' || strlen($navn) > 200) return null;
    if (preg_match('/[\x00-\x1F\x7F<>:"|?*#%\/\\\\]/u', $navn)) return null;
    if (!preg_match('/\.html?$/i', $navn)) return null;
    return $navn;
}

function er_reserveret(string $navn): bool
{
    return in_array(mb_strtolower($navn, 'UTF-8'), RESERVEREDE, true);
}

/* Finder en eksisterende fil med samme navn, også hvis store og små
   bogstaver er forskellige (Mac og Windows skelner ikke, serveren gør). */
function find_eksisterende(string $navn): ?string
{
    if (is_file(FIL_MAPPE . '/' . $navn)) return $navn;
    $lille = mb_strtolower($navn, 'UTF-8');
    foreach (scandir(FIL_MAPPE) ?: [] as $f) {
        if (mb_strtolower($f, 'UTF-8') === $lille && is_file(FIL_MAPPE . '/' . $f)) return $f;
    }
    return null;
}


/* =========================================================================
   Del 4 · Læsning af metadata
   Samme regler som Søgeside · Bygger: kig i de første og sidste 500 kB,
   og læs kun hele filen, hvis metadatablokken ligger midt i den.
   ========================================================================= */

function udtraek_kildedata(string $s): ?string
{
    $fra = 0;
    while (($p = stripos($s, 'builder-source-data', $fra)) !== false) {
        $fra = $p + 19;
        $vindue = max(0, $p - 1000);
        $lt = strrpos(substr($s, $vindue, $p - $vindue), '<');
        $gt = strpos($s, '>', $p);
        if ($lt === false || $gt === false) continue;
        $tagStart = $vindue + $lt;
        $tag = substr($s, $tagStart, $gt - $tagStart + 1);
        if (!preg_match('/^<script\b[^>]*\bid\s*=\s*["\']builder-source-data["\'][^>]*>$/i', $tag)) continue;
        $slut = stripos($s, '</script', $gt);
        if ($slut === false) return null;          // blokken er klippet over
        return substr($s, $gt + 1, $slut - $gt - 1);
    }
    return null;
}

function som_tekst($v): string
{
    if (is_array($v)) {
        return implode(', ', array_map(fn($x) => is_scalar($x) ? (string)$x : '', $v));
    }
    if (is_bool($v)) return $v ? 'true' : '';
    return is_scalar($v) ? trim((string)$v) : '';
}

/* Returnerer ['post' => [...]]  eller  ['spring' => true]  eller  ['fejl' => '...'] */
function laes_post(string $sti, string $navn): array
{
    $str = @filesize($sti);
    $fh = @fopen($sti, 'rb');
    if ($fh === false || $str === false) return ['fejl' => 'filen kunne ikke læses'];

    if ($str <= HOVED_BYTES + HALE_BYTES) {
        $stykker = [(string)stream_get_contents($fh)];
    } else {
        $hoved = (string)fread($fh, HOVED_BYTES);
        fseek($fh, -HALE_BYTES, SEEK_END);
        $stykker = [$hoved, (string)stream_get_contents($fh)];
    }
    fclose($fh);

    if (strpos(substr($stykker[0], 0, 4000), MARKER) !== false) return ['spring' => true];

    $json = null;
    foreach ($stykker as $s) {
        $json = udtraek_kildedata($s);
        if ($json !== null) break;
    }
    if ($json === null && count($stykker) > 1) {
        $hele = @file_get_contents($sti);
        if ($hele === false) return ['fejl' => 'filen er for stor til at blive læst'];
        $json = udtraek_kildedata($hele);
        unset($hele);
    }
    if ($json === null) return ['fejl' => 'der er ingen metadata i filen'];

    $data = json_decode($json, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
    unset($json);
    if (!is_array($data)) return ['fejl' => 'metadata i filen kunne ikke læses'];

    /* Byggerne skriver enten "dublinCore" eller "dc". Begge dele virker. */
    $dc = $data['dublinCore'] ?? $data['dc'] ?? [];
    if (!is_array($dc)) $dc = [];

    /* Kun almindelige webadresser må blive til links på søgesiden. */
    $link = som_tekst($data['link'] ?? '');
    if ($link !== '' && !preg_match('#^https?://#i', $link)) $link = '';

    $post = [
        'filnavn'   => $navn,
        'link'      => $link,
        'appformat' => som_tekst($data['format'] ?? $data['builder'] ?? ''),
    ];
    foreach (DC_FELTER as $f) $post[$f] = som_tekst($dc[$f] ?? '');
    if ($post['title'] === '') $post['title'] = preg_replace('/\.html?$/i', '', $navn);
    return ['post' => $post];
}


/* =========================================================================
   Del 5 · Indekset
   data/indeks.json indeholder én post pr. fil. Hver gang det ændres,
   skrives filer/soegeside.html også på ny, så mappen filer/ altid er et
   færdigt offline-arkiv, der kan kopieres til et USB-stik.
   ========================================================================= */

function hent_indeks(): array
{
    $i = laes_json(INDEKS_FIL, []);
    return [
        'poster'    => is_array($i['poster'] ?? null) ? $i['poster'] : [],
        'sprunget'  => is_array($i['sprunget'] ?? null) ? $i['sprunget'] : [],
        'opdateret' => (int)($i['opdateret'] ?? 0),
    ];
}

function gem_indeks(array $indeks): void
{
    ksort($indeks['poster'], SORT_NATURAL | SORT_FLAG_CASE);
    ksort($indeks['sprunget'], SORT_NATURAL | SORT_FLAG_CASE);
    $indeks['opdateret'] = time();
    skriv_json(INDEKS_FIL, $indeks);
    skriv_statisk_soegeside($indeks);
}

/* Kaldes inde fra med_laas(). */
function indeks_saet(string $navn, array $post): void
{
    $indeks = hent_indeks();
    $post['storrelse'] = (int)@filesize(FIL_MAPPE . '/' . $navn);
    $indeks['poster'][$navn] = $post;
    unset($indeks['sprunget'][$navn]);
    gem_indeks($indeks);
}

/* Kaldes inde fra med_laas(). */
function indeks_fjern(string $navn): void
{
    $indeks = hent_indeks();
    unset($indeks['poster'][$navn], $indeks['sprunget'][$navn]);
    gem_indeks($indeks);
    $log = laes_json(UPLOAD_LOG, []);
    unset($log[$navn]);
    skriv_json(UPLOAD_LOG, $log);
}

/* Læser alle filer i filer/ igen. Bruges fra admin-siden, fx når der er
   lagt filer op med FTP. Kaldes inde fra med_laas(). */
function indeks_genopbyg(): array
{
    $indeks = ['poster' => [], 'sprunget' => [], 'opdateret' => 0];
    foreach (scandir(FIL_MAPPE) ?: [] as $navn) {
        if ($navn[0] === '.' || !preg_match('/\.html?$/i', $navn) || er_reserveret($navn)) continue;
        $sti = FIL_MAPPE . '/' . $navn;
        if (!is_file($sti)) continue;
        $r = laes_post($sti, $navn);
        if (isset($r['post'])) {
            $r['post']['storrelse'] = (int)filesize($sti);
            $indeks['poster'][$navn] = $r['post'];
        } elseif (empty($r['spring'])) {
            $indeks['sprunget'][$navn] = $r['fejl'];
        }
    }
    gem_indeks($indeks);

    /* Glem uploadoplysninger for filer, der ikke findes mere. */
    $log = laes_json(UPLOAD_LOG, []);
    foreach (array_keys($log) as $navn) {
        if (!is_file(FIL_MAPPE . '/' . $navn)) unset($log[$navn]);
    }
    skriv_json(UPLOAD_LOG, $log);
    return $indeks;
}

function skriv_statisk_soegeside(array $indeks): void
{
    $ind = indstillinger();
    $html = byg_soegeside(
        array_values($indeks['poster']), $ind['titel'], $ind['undertitel'], '',
        (int)$indeks['opdateret'],
        'Denne side er selvstændig og indeholder alt indhold direkte i filen. Den skal '
        . 'ligge i samme mappe som de filer, den henviser til, og virker også uden internet.'
    );
    $tmp = FIL_MAPPE . '/.' . STATISK_NAVN . '.tmp';
    if (@file_put_contents($tmp, $html) !== false) @rename($tmp, FIL_MAPPE . '/' . STATISK_NAVN);
}


/* =========================================================================
   Del 6 · Skabelonen for søgesiden
   1:1 med Søgeside · Bygger. Nye pladsholdere:
   __STI__ er mappen foran filnavnet ("filer/" online, "" i offline-udgaven),
   __FODNOTE__ er teksten nederst.
   ========================================================================= */

function byg_soegeside(array $poster, string $titel, string $under, string $sti,
                       int $tid, string $fodnoteHtml, string $topHtml = ''): string
{
    /* Offentlige data: uden filstørrelse og uden oplysninger om hvem der lagde op. */
    $rene = array_map(function ($p) {
        unset($p['storrelse']);
        return $p;
    }, $poster);
    usort($rene, fn($a, $b) => strnatcasecmp($a['filnavn'], $b['filnavn']));

    $jsonFlag = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP;
    return strtr(SKABELON, [
        '__MARKER__'     => MARKER,
        '__TITEL__'      => h($titel !== '' ? $titel : 'Lokalhistorisk søgeindeks'),
        '__UNDERTITEL__' => $under !== '' ? h($under) . ' · ' : '',
        '__ANTAL__'      => (string)count($rene),
        '__GENERERET__'  => $tid ? dansk_tid($tid) : 'endnu ikke',
        '__FODNOTE__'    => $fodnoteHtml,
        '__TOP__'        => $topHtml,
        '__STI_JSON__'   => json_encode($sti, $jsonFlag),
        '__DATA_JSON__'  => json_encode($rene, $jsonFlag | JSON_INVALID_UTF8_SUBSTITUTE),
        '__FORENINGER_JSON__' => json_encode(
            (object)array_map(fn($f) => $f['udgiver'], hent_foreninger()), $jsonFlag),
    ]);
}

const SKABELON = <<<'SKABELON'
<!-- __MARKER__ -->
<!DOCTYPE html>
<html lang="da">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>__TITEL__</title>
<style>
/* Alle størrelser er i rem, så A/A+/A++ kan skalere hele siden på én gang. */
html{font-size:100%}
html[data-tekst="stor"]{font-size:112.5%}
html[data-tekst="storst"]{font-size:125%}

:root{
  --accent:#8E1F49;        /* 8,3:1 mod papir */
  --paper:#FAF9F5;
  --paper-dim:#F1E7EC;
  --ink:#241520;           /* 15,6:1 */
  --line:#D9C7CF;
  --muted:#58424D;         /* 8,0:1 mod papir, 7,5:1 mod zebra */
  --footer-strong:#33202B;
}
@media (prefers-contrast:more){
  :root{--accent:#6E1638;--ink:#000;--muted:#33202B;--line:#9C8A93}
}
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{
  background:var(--paper);color:var(--ink);
  font-family:Georgia,"Times New Roman",serif;
  font-size:1.25rem;line-height:1.6;
  -webkit-text-size-adjust:100%;
}
a{color:var(--accent)}
a:focus-visible,button:focus-visible,input:focus-visible,select:focus-visible{
  outline:3px solid var(--accent);outline-offset:2px;
}

header{max-width:1100px;margin:0 auto;padding:2rem 1.25rem .5rem}
.topbar{display:flex;gap:1rem;align-items:flex-start;justify-content:space-between;flex-wrap:wrap}
h1{font-size:2.1rem;line-height:1.2;margin:0 0 .25rem}
.sub{color:var(--muted);font-size:1.05rem;margin:0}

/* ---- knappen tilbage til forsiden (kun i onlineudgaven) ---- */
.tilbage{margin:0 0 1.2rem}
.tilbage a{display:inline-flex;align-items:center;gap:.5rem;min-height:48px;padding:0 1rem;
  border:2px solid var(--line);border-radius:8px;background:#fff;color:var(--ink);
  text-decoration:none;font-size:1.05rem}
.tilbage a:hover{border-color:var(--accent);color:var(--accent)}
.tilbage svg{width:1.2rem;height:1.2rem;flex:0 0 auto}

/* ---- tekststørrelse ---- */
.tekststr{display:flex;align-items:center;gap:.5rem;flex:0 0 auto}
.tekststr .lbl{font-size:.95rem;color:var(--muted)}
.tekststr button{
  font-family:inherit;background:#fff;color:var(--ink);
  border:2px solid var(--line);border-radius:8px;
  min-width:48px;min-height:48px;padding:0 .6rem;
  cursor:pointer;line-height:1;
}
.tekststr button:nth-of-type(1){font-size:1rem}
.tekststr button:nth-of-type(2){font-size:1.25rem}
.tekststr button:nth-of-type(3){font-size:1.5rem}
.tekststr button:hover{border-color:var(--accent)}
.tekststr button[aria-pressed="true"]{background:var(--accent);border-color:var(--accent);color:#fff}

.controls{max-width:1100px;margin:1.2rem auto .3rem;padding:0 1.25rem;display:flex;gap:.75rem;flex-wrap:wrap}
.controls input,.controls select{
  font-family:inherit;font-size:1.05rem;padding:.65rem .75rem;
  border:2px solid var(--line);border-radius:8px;background:#fff;color:var(--ink);
  min-height:48px;
}
.controls input{flex:1 1 16rem}
.controls select{flex:0 0 auto;max-width:100%}
.filterbesked{max-width:1100px;margin:.9rem auto 0;padding:0 1.25rem}
.filterbesked[hidden]{display:none}
.filterbesked > div{display:flex;gap:.6rem 1.2rem;align-items:center;justify-content:space-between;flex-wrap:wrap;
  background:var(--paper-dim);border-radius:8px;padding:.7rem 1rem}
.filterbesked p{margin:0}
.filterbesked button{font-family:inherit;font-size:1rem;background:#fff;color:var(--accent);
  border:2px solid var(--accent);border-radius:8px;min-height:44px;padding:.3rem 1rem;cursor:pointer}
.filterbesked button:hover{background:var(--accent);color:#fff}
.count{max-width:1100px;margin:.9rem auto 0;padding:0 1.25rem;color:var(--muted);font-size:1rem}
.list{max-width:1100px;margin:.6rem auto 3rem;padding:0 1.25rem}
a.row{
  display:flex;gap:1rem;align-items:flex-start;text-decoration:none;color:var(--ink);
  padding:1rem .9rem;border-radius:8px;
}
a.row:hover{outline:2px solid var(--accent)}
a.row:focus-visible{outline:3px solid var(--accent)}
.row--a{background:var(--paper)}
.row--b{background:var(--paper-dim)}
.icon{font-size:1.4rem;flex:0 0 1.9rem;line-height:1.4}
.col-main{flex:1 1 auto;min-width:0}
.col-main .t{font-size:1.25rem;font-weight:bold;color:var(--footer-strong);line-height:1.35}
.col-main .m{font-size:1rem;color:var(--muted);margin-top:.2rem;display:block}
.col-main .d{font-size:1.05rem;color:var(--ink);margin-top:.4rem;
  display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.col-type{flex:0 0 auto;font-size:.95rem;color:var(--muted);align-self:center;white-space:nowrap}
.empty{padding:2rem 0;color:var(--muted)}
footer{max-width:1100px;margin:0 auto;padding:.6rem 1.25rem 3rem;color:var(--muted);font-size:.95rem;line-height:1.7}
@media(max-width:640px){
  .col-type{display:none}
  header{padding:1.5rem 1rem .5rem}
  .controls{padding:0 1rem;gap:.6rem}
  .controls input,.controls select{flex:1 1 100%;width:100%}
  .count,.list,footer{padding-left:1rem;padding-right:1rem}
  a.row{padding:1rem .6rem;gap:.75rem}
  .icon{flex:0 0 1.6rem}
}
</style>
</head>
<body>
<header>
__TOP__
<div class="topbar">
  <div>
    <h1>__TITEL__</h1>
    <p class="sub">__UNDERTITEL____ANTAL__ poster · opdateret __GENERERET__</p>
  </div>
  <div class="tekststr" role="group" aria-label="Tekststørrelse">
    <span class="lbl" aria-hidden="true">Tekst</span>
    <button type="button" data-str="normal" aria-pressed="true" aria-label="Normal tekststørrelse">A</button>
    <button type="button" data-str="stor" aria-pressed="false" aria-label="Større tekst">A+</button>
    <button type="button" data-str="storst" aria-pressed="false" aria-label="Størst tekst">A++</button>
  </div>
</div>
</header>

<div class="controls">
<input id="q" type="search" placeholder="Søg i titel, ophav, emne, udgiver, sted og beskrivelse …" aria-label="Søg">
<select id="omraadeFilter" aria-label="Filtrér på område"><option value="">Alle områder</option></select>
<select id="typeFilter" aria-label="Filtrér på type"><option value="">Alle typer</option></select>
<select id="sortBy" aria-label="Sortér">
<option value="title">Titel A–Å</option>
<option value="date-new">Dato, nyeste først</option>
<option value="date-old">Dato, ældste først</option>
<option value="type">Type</option>
<option value="omraade">Område</option>
</select>
</div>

<div class="filterbesked" id="filterbesked" hidden>
<div><p>Du ser kun materiale fra <strong id="filternavn"></strong>.</p>
<button type="button" id="visAlle">Vis alle</button></div>
</div>

<p class="count" id="count" role="status" aria-live="polite"></p>
<div class="list" id="list"></div>

<footer>
__FODNOTE__
</footer>

<script>
const DATA = __DATA_JSON__;
const STI = __STI_JSON__;
const FORENINGER = __FORENINGER_JSON__;
const ICONS = {Sound:"🎧",Image:"🖼️",Text:"📖",MovingImage:"🎬",Collection:"🗂️",InteractiveResource:"🧩",Event:"📌",Henvisning:"🔗"};

/* ---- tekststørrelse: A / A+ / A++ ---- */
(function(){
  const knapper = document.querySelectorAll(".tekststr button");
  function saet(v, gem){
    if(v && v!=="normal"){ document.documentElement.setAttribute("data-tekst", v); }
    else { document.documentElement.removeAttribute("data-tekst"); v="normal"; }
    knapper.forEach(k=>k.setAttribute("aria-pressed", String(k.dataset.str===v)));
    if(gem){ try{ localStorage.setItem("lokalhist-tekst", v); }catch(e){} }
  }
  let gemt = null;
  try{ gemt = localStorage.getItem("lokalhist-tekst"); }catch(e){}
  if(gemt) saet(gemt, false);
  knapper.forEach(k=>k.addEventListener("click", ()=>saet(k.dataset.str, true)));
})();

function yearOf(s){var m=(s||"").match(/\d{4}/); return m?m[0]:"0000";}

const typeSel = document.getElementById("typeFilter");
const seen = new Set();
DATA.forEach(r=>{ if(r.type) seen.add(r.type); });
Array.from(seen).sort().forEach(t=>{
  const o=document.createElement("option"); o.value=t; o.textContent=t;
  typeSel.appendChild(o);
});

/* Område = dc.publisher. Listen bygges af de udgivere der faktisk findes. */
const omrSel = document.getElementById("omraadeFilter");
const omrSeen = new Set();
let udenOmraade = 0;
DATA.forEach(r=>{ if(r.publisher) omrSeen.add(r.publisher); else udenOmraade++; });
Array.from(omrSeen).sort((a,b)=>a.localeCompare(b,"da")).forEach(p=>{
  const o=document.createElement("option"); o.value=p; o.textContent=p;
  omrSel.appendChild(o);
});
if(udenOmraade){
  const o=document.createElement("option");
  o.value="__uden__"; o.textContent="(uden udgiver — " + udenOmraade + ")";
  omrSel.appendChild(o);
}

/* ---- Adressen kan vælge filtre på forhånd ----
   ?forening=rtv   (nøglen fra data/foreninger.json)
   ?omraade=...    (udgiveren skrevet helt ud)
   ?type=Sound     ?q=søgeord                                     */
(function(){
  let p;
  try{ p = new URLSearchParams(location.search); }catch(e){ return; }
  const q = p.get("q");
  if(q) document.getElementById("q").value = q;
  let omr = p.get("omraade") || "";
  const f = (p.get("forening") || "").toLowerCase();
  if(f && Object.prototype.hasOwnProperty.call(FORENINGER, f)) omr = FORENINGER[f];
  if(omr){
    /* En forening uden poster endnu skal give "ingen poster", ikke alt. */
    if(!Array.from(omrSel.options).some(o=>o.value===omr)){
      const o=document.createElement("option"); o.value=omr; o.textContent=omr;
      omrSel.appendChild(o);
    }
    omrSel.value = omr;
  }
  const t = p.get("type");
  if(t && Array.from(typeSel.options).some(o=>o.value===t)) typeSel.value = t;
})();

const besked = document.getElementById("filterbesked");
document.getElementById("visAlle").addEventListener("click", ()=>{
  omrSel.value = ""; render(); omrSel.focus();
});

/* Holder adressen i takt med filtrene, så den kan kopieres og deles. */
function opdaterAdresse(){
  try{
    const p = new URLSearchParams();
    const q = document.getElementById("q").value.trim();
    if(q) p.set("q", q);
    const omr = omrSel.value;
    const n = Object.keys(FORENINGER).find(k=>FORENINGER[k]===omr);
    if(n) p.set("forening", n); else if(omr) p.set("omraade", omr);
    if(typeSel.value) p.set("type", typeSel.value);
    const s = p.toString();
    history.replaceState(null, "", location.pathname + (s ? "?" + s : ""));
  }catch(e){}
}

function render(){
  const omrValgt = omrSel.value;
  besked.hidden = !omrValgt || omrValgt === "__uden__";
  document.getElementById("filternavn").textContent = omrValgt;
  opdaterAdresse();

  const q = document.getElementById("q").value.trim().toLowerCase();
  const type = typeSel.value;
  const omr = omrSel.value;
  const sortBy = document.getElementById("sortBy").value;

  let rows = DATA.filter(r=>{
    if(type && r.type!==type) return false;
    if(omr === "__uden__"){ if(r.publisher) return false; }
    else if(omr && r.publisher!==omr) return false;
    if(!q) return true;
    const hay = (r.title+" "+r.creator+" "+r.subject+" "+r.publisher+" "+
                 r.coverage+" "+r.description).toLowerCase();
    return hay.indexOf(q)!==-1;
  });

  rows = rows.slice().sort((a,b)=>{
    if(sortBy==="title") return a.title.localeCompare(b.title,"da");
    if(sortBy==="type") return (a.type||"").localeCompare(b.type||"","da");
    if(sortBy==="omraade"){
      const c=(a.publisher||"ÅÅÅ").localeCompare(b.publisher||"ÅÅÅ","da");
      return c!==0 ? c : a.title.localeCompare(b.title,"da");
    }
    const ya=yearOf(a.date), yb=yearOf(b.date);
    return sortBy==="date-new" ? yb.localeCompare(ya) : ya.localeCompare(yb);
  });

  const list = document.getElementById("list");
  list.innerHTML = "";
  document.getElementById("count").textContent =
    "Viser " + rows.length + " af " + DATA.length + " poster";

  if(rows.length===0){
    const p=document.createElement("p");
    p.className="empty";
    p.textContent = DATA.length ? "Ingen poster matcher søgningen." : "Der er ikke lagt noget op endnu.";
    list.appendChild(p); return;
  }

  rows.forEach((r,i)=>{
    const a=document.createElement("a");
    a.className="row " + (i%2===0?"row--a":"row--b");
    const erEkstern = !!r.link;
    a.href = erEkstern ? r.link : STI + encodeURI(r.filnavn);
    a.target="_blank"; a.rel = erEkstern ? "noopener noreferrer" : "noopener";

    const icon=document.createElement("span");
    icon.className="icon"; icon.textContent = ICONS[r.type]||"📄";
    icon.setAttribute("aria-hidden","true");

    const main=document.createElement("span");
    main.className="col-main";
    const t=document.createElement("span"); t.className="t"; t.textContent=r.title;
    const m=document.createElement("span"); m.className="m";
    m.textContent=[r.creator,r.date,r.publisher].filter(Boolean).join(" · ");
    const d=document.createElement("span"); d.className="d"; d.textContent=r.description||"";
    main.appendChild(t); main.appendChild(document.createElement("br"));
    main.appendChild(m); main.appendChild(d);

    const typ=document.createElement("span");
    typ.className="col-type";
    typ.textContent=(r.type||"") + (r.link ? " ↗" : "");

    a.appendChild(icon); a.appendChild(main); a.appendChild(typ);
    list.appendChild(a);
  });
}

document.getElementById("q").addEventListener("input", render);
omrSel.addEventListener("change", render);
typeSel.addEventListener("change", render);
document.getElementById("sortBy").addEventListener("change", render);
render();
</script>
</body>
</html>
SKABELON;


/* =========================================================================
   Del 7 · Login
   ========================================================================= */

function start_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) return;
    /* Eget cookienavn pr. installation, så flere søgeindeks på samme
       domæne ikke deler login. */
    session_name('si_' . substr(md5(__DIR__), 0, 10));
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function brugerliste(): array
{
    global $BRUGERE;
    $ud = [];
    foreach (($BRUGERE ?? []) as $navn => $hash) {
        $ud[mb_strtolower(trim((string)$navn), 'UTF-8')] = (string)$hash;
    }
    return $ud;
}

function tjek_kode(string $navn, string $kode): bool
{
    $liste = brugerliste();
    $navn = mb_strtolower(trim($navn), 'UTF-8');
    if (!isset($liste[$navn])) {
        /* Brug samme tid som et rigtigt forsøg, så man ikke kan gætte brugernavne. */
        password_verify($kode, '$2y$12$zYysRgL.KbVNxVsR90uv/OSHL/VbfN0xW915ioFjlsYhj4b/kNkBu');
        return false;
    }
    $hash = $liste[$navn];
    if (strncmp($hash, '$2b$', 4) === 0) $hash = '$2y$' . substr($hash, 4);   // samme bcrypt
    return password_verify($kode, $hash);
}

function er_admin(string $bruger): bool
{
    global $ADMINS;
    $admins = array_map(fn($a) => mb_strtolower((string)$a, 'UTF-8'), $ADMINS ?? []);
    return !$admins || in_array($bruger, $admins, true);
}

function aktuel_bruger(): ?string
{
    start_session();
    $b = $_SESSION['bruger'] ?? null;
    return ($b !== null && isset(brugerliste()[$b])) ? $b : null;
}

function kraev_login(bool $admin = false): string
{
    $b = aktuel_bruger();
    if ($b === null) {
        header('Location: login.php?til=' . rawurlencode(basename($_SERVER['SCRIPT_NAME'])));
        exit;
    }
    if ($admin && !er_admin($b)) {
        http_response_code(403);
        side_top('Ingen adgang', 'Admin-siden', $b);
        echo '<section class="panel"><p>Du er logget ind som <strong>' . h($b) . '</strong>, '
           . 'men har ikke adgang til admin-siden. Spørg den, der har oprettet din bruger.</p>'
           . '<p><a href="upload.php">Gå til upload</a></p></section>';
        side_bund();
        exit;
    }
    return $b;
}

function csrf(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_felt(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf()) . '">';
}

function csrf_ok(): bool
{
    start_session();
    return !empty($_SESSION['csrf']) && is_string($_POST['csrf'] ?? null)
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/* ---- Bremse på gættede adgangskoder, pr. IP-adresse ---- */
function forsoeg_ip(): string
{
    return hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? '?'));
}

function laast_ude(): int
{
    $f = laes_json(LOGIN_FORSOEG, []);
    $r = $f[forsoeg_ip()] ?? null;
    if (!$r || $r['antal'] < MAKS_FORSOEG) return 0;
    $rest = ($r['sidst'] + PAUSE_SEK) - time();
    return max(0, $rest);
}

function registrer_forsoeg(bool $lykkedes): void
{
    med_laas(function () use ($lykkedes) {
        $f = laes_json(LOGIN_FORSOEG, []);
        $ip = forsoeg_ip();
        foreach ($f as $k => $r) {                          // ryd gamle poster
            if (($r['sidst'] ?? 0) < time() - PAUSE_SEK) unset($f[$k]);
        }
        if ($lykkedes) unset($f[$ip]);
        else $f[$ip] = ['antal' => (($f[$ip]['antal'] ?? 0) + 1), 'sidst' => time()];
        skriv_json(LOGIN_FORSOEG, $f);
    });
}


/* =========================================================================
   Del 8 · Sidelayout for upload, admin og login
   Samme farver og tekststørrelser som Søgeside · Bygger.
   ========================================================================= */

function side_top(string $titel, string $sub, ?string $bruger, string $aktiv = ''): void
{
    header('Content-Type: text/html; charset=utf-8');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    $nav = '';
    if ($bruger !== null) {
        $pkt = [['index.php', 'Søgesiden', ''], ['upload.php', 'Læg filer op', 'upload']];
        if (er_admin($bruger)) $pkt[] = ['admin.php', 'Admin', 'admin'];
        $nav = '<nav aria-label="Menu"><ul>';
        foreach ($pkt as [$url, $txt, $id]) {
            $nav .= '<li><a href="' . $url . '"' . ($id !== '' && $id === $aktiv ? ' aria-current="page"' : '')
                  . '>' . $txt . '</a></li>';
        }
        $nav .= '<li class="ud"><a href="logout.php">Log ud</a> <span>(' . h($bruger) . ')</span></li></ul></nav>';
    }
    ?><!DOCTYPE html>
<html lang="da">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= h($titel) ?></title>
<style>
html{font-size:100%}
html[data-tekst="stor"]{font-size:112.5%}
html[data-tekst="storst"]{font-size:125%}
:root{
  --accent:#8E1F49;--paper:#FAF9F5;--paper-dim:#F1E7EC;--ink:#241520;
  --line:#D9C7CF;--muted:#58424D;--footer-strong:#33202B;--ok:#1F5B3A;--advarsel:#8A4B00;
}
@media (prefers-contrast:more){:root{--accent:#6E1638;--ink:#000;--muted:#33202B;--line:#9C8A93}}
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{background:var(--paper);color:var(--ink);font-family:Georgia,"Times New Roman",serif;
  font-size:1.2rem;line-height:1.6;-webkit-text-size-adjust:100%}
a{color:var(--accent)}
a:focus-visible,button:focus-visible,input:focus-visible,select:focus-visible,summary:focus-visible{
  outline:3px solid var(--accent);outline-offset:2px}
header{max-width:960px;margin:0 auto;padding:2rem 1.25rem .5rem}
.topbar{display:flex;gap:1rem;align-items:flex-start;justify-content:space-between;flex-wrap:wrap}
h1{font-size:2rem;line-height:1.2;margin:0 0 .25rem}
.sub{color:var(--muted);font-size:1.05rem;margin:0;max-width:62ch}
.tekststr{display:flex;align-items:center;gap:.5rem;flex:0 0 auto}
.tekststr .lbl{font-size:.95rem;color:var(--muted)}
.tekststr button{font-family:inherit;background:#fff;color:var(--ink);border:2px solid var(--line);
  border-radius:8px;min-width:48px;min-height:48px;padding:0 .6rem;cursor:pointer;line-height:1}
.tekststr button:nth-of-type(1){font-size:1rem}
.tekststr button:nth-of-type(2){font-size:1.25rem}
.tekststr button:nth-of-type(3){font-size:1.5rem}
.tekststr button:hover{border-color:var(--accent)}
.tekststr button[aria-pressed="true"]{background:var(--accent);border-color:var(--accent);color:#fff}

nav{max-width:960px;margin:0 auto;padding:.4rem 1.25rem 0}
nav ul{list-style:none;margin:0;padding:0 0 .6rem;display:flex;flex-wrap:wrap;gap:.3rem 1.4rem;
  border-bottom:2px solid var(--line);font-size:1.05rem}
nav a{display:inline-flex;align-items:center;min-height:44px}
nav a[aria-current="page"]{color:var(--ink);font-weight:bold;text-decoration:none}
nav .ud{margin-left:auto;color:var(--muted)}

main{max-width:960px;margin:0 auto;padding:1.25rem 1.25rem 3rem;display:grid;gap:1.5rem}
.panel{background:#fff;border:2px solid var(--line);border-radius:10px;padding:1.25rem}
.panel h2{font-size:1.3rem;margin:0 0 .6rem;color:var(--footer-strong)}
.panel > p:first-of-type{margin-top:0}
.hint{font-size:.98rem;color:var(--muted);margin:.4rem 0 0}

button.stor{font-family:inherit;font-size:1.15rem;background:var(--accent);color:#fff;
  border:2px solid var(--accent);border-radius:8px;min-height:52px;padding:.6rem 1.1rem;cursor:pointer}
button.stor:hover{background:#7A1A3E;border-color:#7A1A3E}
button.stor[disabled]{background:#B8A3AC;border-color:#B8A3AC;cursor:not-allowed}
button.rolig{font-family:inherit;font-size:1.05rem;background:#fff;color:var(--ink);
  border:2px solid var(--line);border-radius:8px;min-height:48px;padding:.5rem 1rem;cursor:pointer}
button.rolig:hover{border-color:var(--accent)}
button.slet{font-family:inherit;font-size:1rem;background:#fff;color:var(--accent);
  border:2px solid var(--accent);border-radius:8px;min-height:44px;padding:.35rem .9rem;cursor:pointer}
button.slet:hover{background:var(--accent);color:#fff}

label.felt{display:block;font-size:1rem;color:var(--muted);margin:.8rem 0 .25rem}
input.felt,select.felt{font-family:inherit;font-size:1.05rem;width:100%;padding:.6rem .7rem;
  border:2px solid var(--line);border-radius:8px;background:#fff;color:var(--ink);min-height:48px}

.status{font-size:1.05rem}
.ok{color:var(--ok);font-weight:bold}
.advarsel{color:var(--advarsel)}
.fejl{color:var(--accent);font-weight:bold}
.besked{border-left:5px solid var(--ok);background:#EEF5F0;padding:.8rem 1rem;border-radius:0 8px 8px 0}
.besked.fejl-b{border-left-color:var(--accent);background:var(--paper-dim)}
.besked p{margin:.2rem 0}

.liste{list-style:none;margin:0;padding:0}
.liste > li{display:flex;gap:1rem;align-items:center;padding:.8rem .7rem;border-radius:8px}
.liste > li:nth-child(odd){background:var(--paper)}
.liste > li:nth-child(even){background:var(--paper-dim)}
.liste .hoved{flex:1 1 auto;min-width:0}
.liste .t{font-weight:bold;color:var(--footer-strong);word-break:break-word}
.liste .m{display:block;font-size:.98rem;color:var(--muted);word-break:break-word}
.liste form{margin:0;flex:0 0 auto}
.tomt{color:var(--muted)}

#dropzone{border:3px dashed var(--line);border-radius:10px;padding:1.4rem 1rem;text-align:center;
  color:var(--muted);font-size:1rem;margin-top:.7rem}
#dropzone.over{border-color:var(--accent);background:var(--paper-dim);color:var(--ink)}
.filvalg{display:flex;gap:.8rem;flex-wrap:wrap;align-items:center;margin-top:.9rem}
progress{width:8rem;height:1rem;accent-color:var(--accent)}
.visuelt-skjult{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;
  clip:rect(0 0 0 0);white-space:nowrap;border:0}
.raekke{display:flex;gap:.8rem;flex-wrap:wrap;align-items:flex-end}
.raekke > *{flex:1 1 14rem}
.raekke > button{flex:0 0 auto}
table.log{width:100%;border-collapse:collapse;font-size:1rem}
table.log td,table.log th{text-align:left;padding:.45rem .5rem;border-bottom:1px solid var(--line);vertical-align:top}
table.log th{color:var(--muted);font-weight:normal}
.logramme{overflow-x:auto}
@media(max-width:640px){
  header,nav,main{padding-left:1rem;padding-right:1rem}
  .liste > li{flex-wrap:wrap}
  nav .ud{margin-left:0}
}
</style>
</head>
<body>
<header>
<div class="topbar">
  <div>
    <h1><?= h($titel) ?></h1>
    <p class="sub"><?= h($sub) ?></p>
  </div>
  <div class="tekststr" role="group" aria-label="Tekststørrelse">
    <span class="lbl" aria-hidden="true">Tekst</span>
    <button type="button" data-str="normal" aria-pressed="true" aria-label="Normal tekststørrelse">A</button>
    <button type="button" data-str="stor" aria-pressed="false" aria-label="Større tekst">A+</button>
    <button type="button" data-str="storst" aria-pressed="false" aria-label="Størst tekst">A++</button>
  </div>
</div>
</header>
<?= $nav ?>
<main>
<?php
}

function side_bund(): void
{
    ?>
</main>
<script>
(function(){
  const knapper = document.querySelectorAll(".tekststr button");
  function saet(v, gem){
    if (v && v !== "normal"){ document.documentElement.setAttribute("data-tekst", v); }
    else { document.documentElement.removeAttribute("data-tekst"); v = "normal"; }
    knapper.forEach(k => k.setAttribute("aria-pressed", String(k.dataset.str === v)));
    if (gem){ try{ localStorage.setItem("lokalhist-tekst", v); }catch(e){} }
  }
  let gemt = null;
  try{ gemt = localStorage.getItem("lokalhist-tekst"); }catch(e){}
  if (gemt) saet(gemt, false);
  knapper.forEach(k => k.addEventListener("click", () => saet(k.dataset.str, true)));
})();
</script>
</body>
</html>
<?php
}
