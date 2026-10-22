<?php
/* =========================================================================
   Søgeindeks · Online — søgesiden
   Viser alt, der er lagt op i mappen filer/. Siden bygges ud fra
   data/indeks.json, som opdateres hver gang en fil lægges op eller slettes.
   ========================================================================= */

require __DIR__ . '/faelles.php';

$indeks = hent_indeks();
$ind = indstillinger();

header('Content-Type: text/html; charset=utf-8');
header('Referrer-Policy: same-origin');
echo byg_soegeside(
    array_values($indeks['poster']),
    $ind['titel'],
    $ind['undertitel'],
    'filer/',
    (int)$indeks['opdateret'],
    'Søgesiden opdateres af sig selv, når der lægges nye filer op. '
    . 'Alle filer virker også uden internet, hvis du gemmer dem på din egen computer. '
    . '<br><a href="upload.php">Log ind og læg filer op</a>',
    /* Knappen øverst. Findes kun online; offline-udgaven har ingen forside. */
    '<p class="tilbage"><a href="forside.php">'
    . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" '
    . 'stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 11l9-7 9 7"/>'
    . '<path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg>Til forsiden</a></p>'
);
