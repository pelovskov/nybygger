<?php
/* =========================================================================
   Lokalhistorisk Søgeportal — forsiden
   Præsenterer de deltagende foreninger og sender videre til søgesiden.
   Alt hentes fra de samme filer som søgesiden:
     data/indeks.json      poster (opdateres ved upload)
     data/uploads.json     hvornår hver fil blev lagt op
     data/foreninger.json  registret over foreninger + kontaktoplysninger
   Du skal normalt ikke rette i denne fil. Ret i data/foreninger.json.
   ========================================================================= */

require __DIR__ . '/faelles.php';

/* Søgesidens filnavn. Skift til fx 'soeg.php', den dag forsiden
   overtager navnet index.php. */
const SOEGESIDE = 'index.php';
const ANTAL_NYE = 4;

const TYPE_NAVNE = [
    'Text'                => ['Tekst', 'Tekst'],
    'Image'               => ['Billede', 'Billeder'],
    'Sound'               => ['Lyd', 'Lyd'],
    'MovingImage'         => ['Video', 'Video'],
    'Collection'          => ['Samling', 'Samlinger'],
    'InteractiveResource' => ['Interaktiv', 'Interaktivt'],
    'Event'               => ['Begivenhed', 'Begivenheder'],
    'Henvisning'          => ['Henvisning', 'Henvisninger'],
];
const TYPE_RAEKKEFOELGE = ['Text', 'Image', 'Sound', 'MovingImage'];

function typenavn(string $t, bool $flertal = false): string
{
    return TYPE_NAVNE[$t][$flertal ? 1 : 0] ?? $t;
}

/* Enkle stregtegninger pr. type. Bruges, når en fil ikke har et lille billede. */
function type_ikon(string $t): string
{
    $s = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" '
       . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">';
    switch ($t) {
        case 'Image':
            return $s . '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/>'
                 . '<path d="M4 18l5-5 4 4 3-3 4 4"/></svg>';
        case 'Sound':
            return $s . '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="4" height="6" rx="1.5"/>'
                 . '<rect x="17" y="14" width="4" height="6" rx="1.5"/></svg>';
        case 'MovingImage':
            return $s . '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M10 9.5v5l4.5-2.5z"/></svg>';
        default:
            return $s . '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4"/><path d="M9 12h6M9 15h6M9 18h4"/></svg>';
    }
}

/* ---- Data ---------------------------------------------------------------- */

$indeks     = hent_indeks();
$poster     = $indeks['poster'];
$foreninger = hent_foreninger();
$portal     = portal_indstillinger();
$uploads    = laes_json(UPLOAD_LOG, []);

$navnFraUdgiver = [];
foreach ($foreninger as $f) $navnFraUdgiver[$f['udgiver']] = $f['navn'];

/* Antal og typer pr. udgiver, og hvilke typer der findes i alt. */
$prUdgiver = [];
$typerIAlt = [];
foreach ($poster as $p) {
    $u = (string)($p['publisher'] ?? '');
    $t = (string)($p['type'] ?? '');
    $prUdgiver[$u]['antal'] = ($prUdgiver[$u]['antal'] ?? 0) + 1;
    if ($t !== '') {
        $prUdgiver[$u]['typer'][$t] = true;
        $typerIAlt[$t] = true;
    }
}
$typeOrden = array_values(array_unique(array_merge(
    array_values(array_filter(TYPE_RAEKKEFOELGE, fn($t) => isset($typerIAlt[$t]))),
    array_keys($typerIAlt)
)));

/* De nyeste poster efter uploadtidspunkt (ellers filens dato). */
$nye = [];
foreach ($poster as $navn => $p) {
    $tid = (int)($uploads[$navn]['tid'] ?? 0);
    if ($tid === 0) $tid = (int)@filemtime(FIL_MAPPE . '/' . $navn);
    $nye[] = ['tid' => $tid, 'p' => $p, 'navn' => (string)$navn];
}
usort($nye, fn($a, $b) => $b['tid'] <=> $a['tid']);
$nye = array_slice($nye, 0, ANTAL_NYE);

/* Egne filer får ?hjem= med, så deres Hjem-knap fører tilbage til forsiden. */
function post_link(array $p, string $navn): string
{
    $link = (string)($p['link'] ?? '');
    if ($link !== '') return $link;
    return 'filer/' . rawurlencode($navn) . '?hjem=' . rawurlencode((string)($_SERVER['SCRIPT_NAME'] ?? 'forside.php'));
}

function kort_tekst(string $s, int $max = 220): string
{
    $s = trim(preg_replace('/\s+/u', ' ', $s));
    return mb_strlen($s, 'UTF-8') > $max ? rtrim(mb_substr($s, 0, $max, 'UTF-8')) . ' …' : $s;
}

/* Rækken af foreningsnavne til fodteksten: "A, B, C og D". */
$navne = array_values(array_map(fn($f) => $f['navn'], $foreninger));
$samarbejde = count($navne) > 1
    ? implode(', ', array_slice($navne, 0, -1)) . ' og ' . end($navne)
    : ($navne[0] ?? '');

header('Content-Type: text/html; charset=utf-8');
header('Referrer-Policy: same-origin');
?><!DOCTYPE html>
<html lang="da">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($portal['titel']) ?></title>
<meta name="description" content="Søg i lokalhistorisk materiale fra foreninger i Roskilde og omegn.">
<style>
/* Samme farver og skrift som søgesiden. Alle størrelser i rem, så A/A+/A++ virker. */
html{font-size:100%}
html[data-tekst="stor"]{font-size:112.5%}
html[data-tekst="storst"]{font-size:125%}
:root{
  --accent:#8E1F49;--accent-moerk:#7A1A3E;
  --paper:#FAF9F5;--paper-dim:#F1E7EC;--ink:#241520;
  --line:#D9C7CF;--muted:#58424D;--footer-strong:#33202B;--kant-felt:#8A7680;
}
@media (prefers-contrast:more){:root{--accent:#6E1638;--ink:#000;--muted:#33202B;--line:#9C8A93;--kant-felt:#33202B}}
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{background:var(--paper);color:var(--ink);font-family:Georgia,"Times New Roman",serif;
  font-size:1.2rem;line-height:1.6;-webkit-text-size-adjust:100%}
a{color:var(--accent)}
a:focus-visible,button:focus-visible,input:focus-visible{outline:3px solid var(--accent);outline-offset:2px}
.bredde{max-width:1100px;margin:0 auto;padding-left:1.25rem;padding-right:1.25rem}
.visuelt-skjult{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;
  clip:rect(0 0 0 0);white-space:nowrap;border:0}
.spring{position:absolute;left:1rem;top:-10rem;background:#fff;padding:.6rem 1rem;border-radius:8px;z-index:10}
.spring:focus{top:1rem}

/* ---- Top ---- */
.top{background:#fff;border-bottom:2px solid var(--line)}
.top .bredde{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;
  padding-top:1rem;padding-bottom:1rem}
.maerke{display:flex;align-items:center;gap:.8rem}
.maerke .ls{width:3rem;height:3rem;flex:0 0 3rem;border-radius:8px;background:var(--accent);color:#fff;
  display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:1.15rem}
.maerke .navn{display:block;font-size:1.35rem;font-weight:bold;line-height:1.2;color:var(--footer-strong)}
.maerke .sted{display:block;font-size:1rem;color:var(--muted)}
.tekststr{display:flex;align-items:center;gap:.5rem}
.tekststr .lbl{font-size:.95rem;color:var(--muted)}
.tekststr button{font-family:inherit;background:#fff;color:var(--ink);border:2px solid var(--line);
  border-radius:8px;min-width:48px;min-height:48px;padding:0 .6rem;cursor:pointer;line-height:1}
.tekststr button:nth-of-type(1){font-size:1rem}
.tekststr button:nth-of-type(2){font-size:1.25rem}
.tekststr button:nth-of-type(3){font-size:1.5rem}
.tekststr button:hover{border-color:var(--accent)}
.tekststr button[aria-pressed="true"]{background:var(--accent);border-color:var(--accent);color:#fff}

/* ---- Indgangen: overskrift og søgning ---- */
.indgang{text-align:center;padding:4rem 0 3.5rem}
.indgang h1{font-size:clamp(2.2rem,5.2vw,3.6rem);line-height:1.12;margin:0 auto 1rem;max-width:22ch;
  color:var(--footer-strong);font-weight:bold}
.indgang .intro{font-size:1.3rem;color:var(--muted);max-width:40ch;margin:0 auto 2.2rem}
.soeg{max-width:46rem;margin:0 auto;text-align:left}
.soeg label{display:block;font-weight:bold;margin-bottom:.4rem;font-size:1.1rem}
.soeg .felt{display:flex;gap:.6rem}
.soeg input{flex:1 1 auto;min-width:0;font-family:inherit;font-size:1.25rem;min-height:3.8rem;
  padding:.6rem 1rem;border:2px solid var(--kant-felt);border-radius:10px;background:#fff;color:var(--ink)}
.soeg button{font-family:inherit;font-size:1.2rem;font-weight:bold;min-height:3.8rem;padding:0 1.6rem;
  border:0;border-radius:10px;background:var(--accent);color:#fff;cursor:pointer;
  display:inline-flex;align-items:center;justify-content:center;gap:.5rem}
.soeg button:hover{background:var(--accent-moerk)}
.soeg button svg{width:1.4rem;height:1.4rem}
.typer{display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:.6rem;margin-top:1.6rem}
.typer .lbl{color:var(--muted);margin-right:.2rem}
.typer a{display:inline-flex;align-items:center;min-height:3rem;padding:0 1.2rem;border:2px solid var(--line);
  border-radius:999px;background:#fff;color:var(--ink);text-decoration:none;font-size:1.05rem}
.typer a:hover{border-color:var(--accent)}
.status{margin:1.6rem 0 0;color:var(--muted);font-size:1rem}

/* ---- Sektioner ---- */
.sektion{padding:3.5rem 0}
.sektion.hvid{background:#fff;border-top:2px solid var(--line);border-bottom:2px solid var(--line)}
.sektion h2{font-size:2.1rem;line-height:1.2;margin:0 0 .5rem;color:var(--footer-strong)}
.sektion .forklaring{color:var(--muted);margin:0 0 2rem;max-width:60ch}
.sektion .hoved{display:flex;justify-content:space-between;align-items:flex-end;gap:1rem;flex-wrap:wrap}

/* ---- Foreningerne ---- */
.deltagere{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.25rem}
.kort{position:relative;display:flex;gap:1.4rem;padding:1.5rem;border:2px solid var(--line);
  border-radius:12px;background:var(--paper)}
.kort:hover{border-color:var(--accent)}
.kort:focus-within{outline:3px solid var(--accent);outline-offset:2px}
.logo{flex:0 0 6rem;height:6rem;border-radius:10px;background:var(--paper-dim);display:flex;
  align-items:center;justify-content:center;font-size:1.6rem;font-weight:bold;color:var(--accent);overflow:hidden}
.logo img{max-width:100%;max-height:100%;object-fit:contain;padding:.4rem}
.kort .indhold{display:flex;flex-direction:column;gap:.35rem;min-width:0;flex:1 1 auto}
.kort .slags{font-size:1rem;color:var(--muted)}
.kort h3{margin:0;font-size:1.5rem;line-height:1.25}
.kort h3 a{color:var(--ink);text-decoration:none}
.kort h3 a::after{content:"";position:absolute;inset:0;border-radius:12px}
.kort h3 a:focus-visible{outline:none}
.kort p{margin:0}
.maerker{display:flex;flex-wrap:wrap;gap:.4rem;margin:.3rem 0 0;padding:0;list-style:none}
.maerker li{font-size:.95rem;padding:.1rem .7rem;border-radius:999px;background:var(--paper-dim);color:var(--footer-strong)}
.kort .bund{display:flex;justify-content:space-between;align-items:center;gap:.4rem 1rem;flex-wrap:wrap;
  border-top:1px solid var(--line);padding-top:.7rem;margin-top:auto;font-size:1rem}
.kort .antal{color:var(--muted)}
.kort .handling{font-weight:bold;color:var(--accent)}
.kort .hjemmeside{position:relative;z-index:1}
.ny-deltager{grid-column:1/-1;display:flex;justify-content:space-between;align-items:center;gap:1rem 1.5rem;
  flex-wrap:wrap;padding:1.5rem;border:2px dashed #A8949E;border-radius:12px}
.ny-deltager h3{margin:0 0 .2rem;font-size:1.4rem}
.ny-deltager p{margin:0;color:var(--muted)}
.knap-kant{display:inline-flex;align-items:center;min-height:3.2rem;padding:0 1.4rem;border:2px solid var(--accent);
  border-radius:10px;color:var(--accent);font-weight:bold;text-decoration:none}
.knap-kant:hover{background:var(--accent);color:#fff}

/* ---- Nyt i arkivet ---- */
.nye{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1.25rem}
.ny{display:flex;flex-direction:column;border:2px solid var(--line);border-radius:12px;background:#fff;
  overflow:hidden;text-decoration:none;color:var(--ink)}
.ny:hover{border-color:var(--accent)}
.ny .flade{height:6.5rem;background:var(--paper-dim);display:flex;align-items:center;justify-content:center;color:var(--accent)}
.ny .flade svg{width:2.8rem;height:2.8rem}
.ny .flade.billede{height:9rem}
.ny .flade img{width:100%;height:100%;object-fit:cover;display:block}
.ny .tekst{padding:1rem 1.1rem 1.2rem;display:flex;flex-direction:column;gap:.3rem}
.ny .fra{font-size:.95rem;color:var(--muted)}
.ny .titel{font-size:1.2rem;font-weight:bold;line-height:1.3;color:var(--footer-strong)}
.ny .beskr{font-size:1rem;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}

/* ---- Om portalen ---- */
.om{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:2rem}
.om h3{font-size:1.35rem;margin:0 0 .4rem;color:var(--footer-strong)}
.om p{margin:0}

/* ---- Bliv deltager ---- */
.bliv{background:var(--accent);color:#fff}
.bliv .bredde{display:flex;justify-content:space-between;align-items:center;gap:1.5rem 2rem;flex-wrap:wrap}
.bliv h2{color:#fff}
.bliv p{max-width:52ch;margin:0}
.bliv .knapper{display:flex;flex-direction:column;gap:.8rem}
.bliv a{display:inline-flex;align-items:center;justify-content:center;min-height:3.4rem;padding:0 1.6rem;
  border-radius:10px;font-weight:bold;text-decoration:none}
.bliv .lys{background:#fff;color:var(--accent)}
.bliv .kant{border:2px solid #fff;color:#fff}
.bliv a:focus-visible{outline-color:#fff}

/* ---- Fod ---- */
.fod{background:var(--footer-strong);color:#F1E7EC;padding:2.5rem 0 3rem;font-size:1.05rem}
.fod .bredde{display:flex;justify-content:space-between;gap:1.5rem 3rem;flex-wrap:wrap}
.fod strong{color:#fff;font-size:1.2rem}
.fod p{margin:.3rem 0 0;max-width:60ch}
.fod a{color:#fff}
.fod a:focus-visible{outline-color:#fff}

@media (max-width:900px){
  .nye{grid-template-columns:repeat(2,minmax(0,1fr))}
  .om{grid-template-columns:1fr;gap:1.4rem}
}
@media (max-width:700px){
  .indgang{padding:2.5rem 0}
  .deltagere{grid-template-columns:1fr}
  .soeg .felt{flex-direction:column}
  .kort{flex-direction:column;gap:1rem}
  .logo{flex:0 0 auto;width:4.5rem;height:4.5rem;font-size:1.3rem}
  .sektion{padding:2.5rem 0}
  .sektion h2{font-size:1.8rem}
}
@media (max-width:480px){
  .nye{grid-template-columns:1fr}
  .bredde{padding-left:1rem;padding-right:1rem}
}
</style>
</head>
<body>
<a class="spring" href="#indhold">Spring til indholdet</a>

<header class="top">
<div class="bredde">
  <div class="maerke">
    <span class="ls" aria-hidden="true">LS</span>
    <span>
      <span class="navn"><?= h($portal['titel']) ?></span>
      <?php if ($portal['undertitel'] !== ''): ?><span class="sted"><?= h($portal['undertitel']) ?></span><?php endif; ?>
    </span>
  </div>
  <div class="tekststr" role="group" aria-label="Tekststørrelse">
    <span class="lbl" aria-hidden="true">Tekst</span>
    <button type="button" data-str="normal" aria-pressed="true" aria-label="Normal tekststørrelse">A</button>
    <button type="button" data-str="stor" aria-pressed="false" aria-label="Større tekst">A+</button>
    <button type="button" data-str="storst" aria-pressed="false" aria-label="Størst tekst">A++</button>
  </div>
</div>
</header>

<main id="indhold">

<section class="indgang">
<div class="bredde">
  <h1>Find lokalhistorien fra Roskilde og omegn</h1>
  <p class="intro">Billeder, lydfortællinger, videoer og artikler fra lokale foreninger, samlet i én søgning.</p>

  <form class="soeg" action="<?= h(SOEGESIDE) ?>" method="get" role="search">
    <label for="q">Hvad leder du efter?</label>
    <div class="felt">
      <input id="q" name="q" type="search" placeholder="Fx en gade, et sted eller et årstal" autocomplete="off">
      <button type="submit">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"/><path d="M16.5 16.5L21 21"/></svg>
        Søg
      </button>
    </div>
  </form>

  <?php if ($typeOrden): ?>
  <nav class="typer" aria-label="Se alt af én slags">
    <span class="lbl">Eller se alt af én slags:</span>
    <?php foreach ($typeOrden as $t): ?>
      <a href="<?= h(SOEGESIDE . '?type=' . rawurlencode($t)) ?>"><?= h(typenavn($t, true)) ?></a>
    <?php endforeach; ?>
  </nav>
  <?php endif; ?>

  <p class="status">Arkivet rummer <?= count($poster) ?> poster<?php if ($indeks['opdateret']): ?> og blev sidst opdateret <?= h(dansk_tid((int)$indeks['opdateret'], false)) ?><?php endif; ?>.</p>
</div>
</section>

<?php if ($foreninger): ?>
<section class="sektion hvid" aria-labelledby="deltagere-titel">
<div class="bredde">
  <h2 id="deltagere-titel">Søg i én forenings materiale</h2>
  <p class="forklaring">Vælg en forening, så søger du kun i deres arkiv. På søgesiden kan du altid vælge alle foreninger igen.</p>

  <div class="deltagere">
  <?php foreach ($foreninger as $f):
      $stat  = $prUdgiver[$f['udgiver']] ?? ['antal' => 0, 'typer' => []];
      $antal = (int)($stat['antal'] ?? 0);
      $typer = array_values(array_filter($typeOrden, fn($t) => isset($stat['typer'][$t])));
      $url   = SOEGESIDE . '?forening=' . rawurlencode($f['noegle']);
      $forkort = $f['forkortelse'] !== '' ? $f['forkortelse'] : mb_substr($f['navn'], 0, 2, 'UTF-8');
  ?>
    <article class="kort">
      <div class="logo">
        <?php if ($f['logo'] !== ''): ?>
          <img src="<?= h($f['logo']) ?>" alt="">
        <?php else: ?>
          <span aria-hidden="true"><?= h($forkort) ?></span>
        <?php endif; ?>
      </div>
      <div class="indhold">
        <?php if ($f['slags'] !== ''): ?><span class="slags"><?= h($f['slags']) ?></span><?php endif; ?>
        <h3><a href="<?= h($url) ?>"><?= h($f['navn']) ?><span class="visuelt-skjult">: søg i deres materiale</span></a></h3>
        <?php if ($f['beskrivelse'] !== ''): ?><p><?= h($f['beskrivelse']) ?></p><?php endif; ?>
        <?php if ($typer): ?>
          <ul class="maerker" aria-label="Materialetyper">
            <?php foreach ($typer as $t): ?><li><?= h(typenavn($t, true)) ?></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <div class="bund">
          <span class="antal"><?= $antal === 0 ? 'Ingen poster endnu' : ($antal === 1 ? '1 post' : $antal . ' poster') ?></span>
          <?php if ($f['hjemmeside'] !== ''): ?>
            <a class="hjemmeside" href="<?= h($f['hjemmeside']) ?>">Foreningens hjemmeside<span class="visuelt-skjult"> (<?= h($f['navn']) ?>)</span></a>
          <?php endif; ?>
          <span class="handling" aria-hidden="true">Søg i arkivet</span>
        </div>
      </div>
    </article>
  <?php endforeach; ?>

    <div class="ny-deltager">
      <div>
        <h3>Jeres forening her?</h3>
        <p>Portalen er åben for flere lokalhistoriske foreninger, arkiver og grupper i området.</p>
      </div>
      <a class="knap-kant" href="#bliv">Sådan kommer I med</a>
    </div>
  </div>
</div>
</section>
<?php endif; ?>

<?php if ($nye): ?>
<section class="sektion" aria-labelledby="nyt-titel">
<div class="bredde">
  <div class="hoved">
    <div>
      <h2 id="nyt-titel">Nyt i arkivet</h2>
      <p class="forklaring">De seneste tilføjelser fra alle foreninger.</p>
    </div>
  </div>
  <div class="nye">
  <?php foreach ($nye as $n):
      $p = $n['p'];
      $t = (string)($p['type'] ?? '');
      $udg = (string)($p['publisher'] ?? '');
      $fra = $navnFraUdgiver[$udg] ?? $udg;
      $ekstern = (string)($p['link'] ?? '') !== '';
      $thumb = (string)($p['thumb'] ?? '');
      if ($thumb !== '' && !is_file(THUMB_MAPPE . '/' . $thumb)) $thumb = '';
  ?>
    <a class="ny" href="<?= h(post_link($p, $n['navn'])) ?>"<?= $ekstern ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
      <?php if ($thumb !== ''): ?>
      <span class="flade billede"><img src="<?= h('filer/thumbs/' . rawurlencode($thumb)) ?>" alt="" loading="lazy"></span>
      <?php else: ?>
      <span class="flade"><?= type_ikon($t) ?></span>
      <?php endif; ?>
      <span class="tekst">
        <span class="fra"><?= h(trim(($t !== '' ? typenavn($t) : '') . ($fra !== '' ? ', ' . $fra : ''), ', ')) ?></span>
        <span class="titel"><?= h((string)($p['title'] ?? $n['navn'])) ?></span>
        <?php if (!empty($p['description'])): ?><span class="beskr"><?= h(kort_tekst((string)$p['description'])) ?></span><?php endif; ?>
      </span>
    </a>
  <?php endforeach; ?>
  </div>
</div>
</section>
<?php endif; ?>

<section class="sektion hvid" aria-labelledby="om-titel">
<div class="bredde">
  <h2 id="om-titel">Om portalen</h2>
  <p class="forklaring">Portalen samler lokalhistorisk materiale fra flere foreninger ét sted.</p>
  <div class="om">
    <div>
      <h3>Søg på tværs</h3>
      <p>Én søgning går gennem materiale fra alle deltagende foreninger. Du kan afgrænse til en forening eller en slags materiale.</p>
    </div>
    <div>
      <h3>Bygget til at holde</h3>
      <p>Hver post er én selvstændig fil med faste, beskrivende oplysninger. Samlingen kan også bruges uden internet, fx fra en USB-nøgle.</p>
    </div>
    <div>
      <h3>Fælles, men jeres eget</h3>
      <p>Hver forening lægger selv sit materiale op og bestemmer over det. Videoer og artikler andre steder på nettet kan tages med som videokort eller henvisninger.</p>
    </div>
  </div>
</div>
</section>

<section class="sektion bliv" id="bliv" aria-labelledby="bliv-titel">
<div class="bredde">
  <div>
    <h2 id="bliv-titel">Vil jeres forening være med?</h2>
    <p>Det kræver ingen teknisk viden. I får et login, en kort vejledning og de samme byggeværktøjer, som de andre foreninger bruger.</p>
  </div>
  <?php if ($portal['kontakt_email'] !== '' || $portal['startpakke'] !== ''): ?>
  <div class="knapper">
    <?php if ($portal['kontakt_email'] !== ''): ?>
      <a class="lys" href="mailto:<?= h($portal['kontakt_email']) ?>?subject=<?= rawurlencode('Vi vil gerne være med i søgeportalen') ?>">Skriv til os</a>
    <?php endif; ?>
    <?php if ($portal['startpakke'] !== ''): ?>
      <a class="kant" href="<?= h($portal['startpakke']) ?>">Se startpakken</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
</section>

</main>

<footer class="fod">
<div class="bredde">
  <div>
    <strong><?= h($portal['titel']) ?></strong>
    <?php if ($samarbejde !== ''): ?><p>Et samarbejde mellem <?= h($samarbejde) ?>.</p><?php endif; ?>
    <p>Hele samlingen findes også i en udgave, der virker uden internet.</p>
  </div>
  <div>
    <?php if ($portal['kontakt_navn'] !== '' || $portal['kontakt_email'] !== ''): ?>
      <strong>Kontakt</strong>
      <?php if ($portal['kontakt_navn'] !== ''): ?><p><?= h($portal['kontakt_navn']) ?></p><?php endif; ?>
      <?php if ($portal['kontakt_email'] !== ''): ?><p><a href="mailto:<?= h($portal['kontakt_email']) ?>"><?= h($portal['kontakt_email']) ?></a></p><?php endif; ?>
    <?php endif; ?>
    <p><a href="upload.php">Log ind og læg filer op</a></p>
  </div>
</div>
</footer>

<script>
/* Tekststørrelse A / A+ / A++ — huskes på tværs af forside og søgeside. */
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
