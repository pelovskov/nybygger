<?php
/* =========================================================================
   Søgeindeks · Online — log ind
   ========================================================================= */

require __DIR__ . '/faelles.php';
start_session();

/* Kun vores egne sider må man sendes videre til efter login. */
$til = (string)($_GET['til'] ?? $_POST['til'] ?? 'upload.php');
if (!in_array($til, ['upload.php', 'admin.php'], true)) $til = 'upload.php';

if (aktuel_bruger() !== null) {
    header('Location: ' . $til);
    exit;
}

$fejl = '';
$navn = '';
$ingenBrugere = !brugerliste();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$ingenBrugere) {
    $navn = trim((string)($_POST['navn'] ?? ''));
    $kode = (string)($_POST['kode'] ?? '');
    $vent = laast_ude();

    if ($vent > 0) {
        $fejl = 'Der er prøvet for mange forkerte adgangskoder. Prøv igen om '
              . max(1, (int)ceil($vent / 60)) . ' minutter.';
    } elseif (!csrf_ok()) {
        $fejl = 'Siden var udløbet. Prøv at logge ind igen.';
    } elseif ($navn !== '' && $kode !== '' && tjek_kode($navn, $kode)) {
        registrer_forsoeg(true);
        session_regenerate_id(true);
        $_SESSION['bruger'] = mb_strtolower($navn, 'UTF-8');
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
        log_haendelse($_SESSION['bruger'], 'loggede ind');
        header('Location: ' . $til);
        exit;
    } else {
        registrer_forsoeg(false);
        sleep(1);
        $fejl = 'Brugernavn eller adgangskode passer ikke.';
    }
}

$ind = indstillinger();
side_top('Log ind', $ind['titel'], null);
?>
<section class="panel" aria-labelledby="h-login" style="max-width:32rem">
  <h2 id="h-login">Log ind for at lægge filer op</h2>

<?php if ($ingenBrugere): ?>
  <div class="besked fejl-b">
    <p>Der er ikke oprettet nogen brugere endnu.</p>
    <p>Lav en adgangskode med <a href="lav-hash.php">lav-hash.php</a>, og skriv
    linjen ind i <strong>config.php</strong> under <code>$BRUGERE</code>.</p>
  </div>
<?php else: ?>
  <?php if ($fejl !== ''): ?>
    <div class="besked fejl-b" role="alert"><p><?= h($fejl) ?></p></div>
  <?php endif; ?>
  <form method="post" action="login.php">
    <?= csrf_felt() ?>
    <input type="hidden" name="til" value="<?= h($til) ?>">
    <label class="felt" for="navn">Brugernavn</label>
    <input class="felt" id="navn" name="navn" type="text" autocomplete="username"
           autocapitalize="none" spellcheck="false" required value="<?= h($navn) ?>">
    <label class="felt" for="kode">Adgangskode</label>
    <input class="felt" id="kode" name="kode" type="password" autocomplete="current-password" required>
    <p style="margin:1.1rem 0 0"><button class="stor" type="submit">Log ind</button></p>
  </form>
<?php endif; ?>
  <p class="hint" style="margin-top:1.2rem"><a href="index.php">Tilbage til søgesiden</a></p>
</section>
<?php side_bund();
