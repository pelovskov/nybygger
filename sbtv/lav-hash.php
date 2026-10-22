<?php
/* =========================================================================
   Søgeindeks · Online — lav en adgangskode til config.php
   Siden gemmer intet. Den regner kun en hash ud og viser den linje,
   der skal skrives ind i config.php. Slå den fra i config.php, når
   alle brugere er oprettet.
   ========================================================================= */

require __DIR__ . '/faelles.php';

if (empty($TILLAD_HASH_SIDE)) {
    http_response_code(404);
    exit('Siden er slået fra.');
}

$linje = '';
$fejl = '';
$navn = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $navn = mb_strtolower(trim((string)($_POST['navn'] ?? '')), 'UTF-8');
    $k1 = (string)($_POST['kode1'] ?? '');
    $k2 = (string)($_POST['kode2'] ?? '');
    if (!preg_match('/^[a-z0-9æøå._-]{2,40}$/u', $navn)) {
        $fejl = 'Brugernavnet skal være 2-40 tegn: små bogstaver, tal, punktum, bindestreg eller understreg.';
    } elseif (mb_strlen($k1) < 10) {
        $fejl = 'Adgangskoden skal være mindst 10 tegn. Tre-fire almindelige ord i træk er både nemt og sikkert.';
    } elseif ($k1 !== $k2) {
        $fejl = 'De to adgangskoder er ikke ens.';
    } else {
        $hash = password_hash($k1, PASSWORD_BCRYPT, ['cost' => 12]);
        $linje = "    '" . $navn . "' => '" . $hash . "',";
    }
}

side_top('Lav adgangskode', 'Til en ny bruger i config.php', null);
?>
<section class="panel" aria-labelledby="h-hash" style="max-width:40rem">
  <h2 id="h-hash">Ny bruger</h2>
<?php if ($linje !== ''): ?>
  <div class="besked">
    <p>Kopiér linjen herunder, og sæt den ind i <strong>config.php</strong> mellem
    <code>$BRUGERE = [</code> og <code>];</code></p>
  </div>
  <label class="felt" for="linje">Linje til config.php</label>
  <input class="felt" id="linje" type="text" readonly value="<?= h($linje) ?>" onfocus="this.select()"
         style="font-family:Menlo,Consolas,monospace;font-size:.95rem">
  <p style="margin:.8rem 0 0"><button class="rolig" type="button" id="kopier">Kopiér linjen</button>
  <span class="hint" id="kopieret" role="status" aria-live="polite"></span></p>
  <p class="hint">Giv adgangskoden til personen på en sikker måde, fx mundtligt.</p>
  <hr style="border:0;border-top:1px solid var(--line);margin:1.4rem 0">
<?php endif; ?>
<?php if ($fejl !== ''): ?>
  <div class="besked fejl-b" role="alert"><p><?= h($fejl) ?></p></div>
<?php endif; ?>
  <form method="post" action="lav-hash.php" autocomplete="off">
    <label class="felt" for="navn">Brugernavn</label>
    <input class="felt" id="navn" name="navn" type="text" autocapitalize="none" spellcheck="false"
           required value="<?= h($navn) ?>">
    <label class="felt" for="kode1">Adgangskode</label>
    <input class="felt" id="kode1" name="kode1" type="password" autocomplete="new-password" required minlength="10">
    <label class="felt" for="kode2">Adgangskode igen</label>
    <input class="felt" id="kode2" name="kode2" type="password" autocomplete="new-password" required minlength="10">
    <p style="margin:1rem 0 0"><button class="stor" type="submit">Lav linjen</button></p>
  </form>
  <p class="hint" style="margin-top:1.2rem">Siden gemmer intet. Slå den fra i config.php
  (<code>$TILLAD_HASH_SIDE = false;</code>), når alle er oprettet.</p>
</section>
<script>
const k = document.getElementById("kopier");
if (k) k.addEventListener("click", async () => {
  const f = document.getElementById("linje");
  try { await navigator.clipboard.writeText(f.value); }
  catch(e){ f.select(); document.execCommand("copy"); }
  document.getElementById("kopieret").textContent = " Kopieret.";
});
</script>
<?php side_bund();
