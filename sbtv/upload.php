<?php
/* =========================================================================
   Søgeindeks · Online — læg filer op
   Hver fil kontrolleres (skal have metadata fra en af Byggerne), lægges i
   filer/, og søgesiden opdateres med det samme. En fil med samme navn som
   en eksisterende erstatter den gamle.
   ========================================================================= */

require __DIR__ . '/faelles.php';

$erAjax = isset($_GET['ajax']);
$bruger = aktuel_bruger();

function svar_json(array $d): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($bruger === null) {
    if ($erAjax) svar_json(['ok' => false, 'fejl' => 'Du er ikke logget ind mere. Genindlæs siden og log ind igen.']);
    kraev_login();
}

/* ---- Én fil ---- */
function haandter_fil(string $origNavn, string $tmp, int $fejlkode, string $bruger): array
{
    $vist = $origNavn !== '' ? $origNavn : '(uden navn)';

    if ($fejlkode === UPLOAD_ERR_INI_SIZE || $fejlkode === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'navn' => $vist,
                'fejl' => 'filen er større end serveren tillader (' . menneske_str(maks_upload()) . ')'];
    }
    if ($fejlkode === UPLOAD_ERR_NO_FILE) return ['ok' => false, 'navn' => $vist, 'fejl' => 'ingen fil valgt'];
    if ($fejlkode !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'navn' => $vist, 'fejl' => 'filen kom ikke helt frem. Prøv igen'];
    }

    $navn = rens_filnavn($origNavn);
    if ($navn === null) {
        return ['ok' => false, 'navn' => $vist,
                'fejl' => 'filnavnet kan ikke bruges. Det skal ende på .html og må ikke indeholde # % ? eller /'];
    }
    if (er_reserveret($navn)) {
        return ['ok' => false, 'navn' => $navn,
                'fejl' => 'dette navn bruges af systemet selv. Giv filen et andet navn'];
    }

    $r = laes_post($tmp, $navn);
    if (!empty($r['spring'])) {
        return ['ok' => false, 'navn' => $navn,
                'fejl' => 'det er en søgeside. Søgesiden laves automatisk her, så den skal ikke lægges op'];
    }
    if (!isset($r['post'])) {
        return ['ok' => false, 'navn' => $navn,
                'fejl' => $r['fejl'] . '. Filen skal være lavet med en af Byggerne'];
    }
    $post = $r['post'];

    return med_laas(function () use ($navn, $tmp, $post, $bruger) {
        $gammel = find_eksisterende($navn);
        $mal = FIL_MAPPE . '/' . $navn;
        $mellem = FIL_MAPPE . '/.upload-' . bin2hex(random_bytes(6));

        if (!move_uploaded_file($tmp, $mellem)) {
            return ['ok' => false, 'navn' => $navn, 'fejl' => 'filen kunne ikke gemmes på serveren'];
        }
        @chmod($mellem, 0644);

        /* Hed den gamle fil det samme med andre store/små bogstaver, fjernes den. */
        if ($gammel !== null && $gammel !== $navn) {
            @unlink(FIL_MAPPE . '/' . $gammel);
            slet_thumb($gammel);
            $idx = hent_indeks();
            unset($idx['poster'][$gammel], $idx['sprunget'][$gammel]);
            skriv_json(INDEKS_FIL, $idx);
        }
        if (!rename($mellem, $mal)) {
            @unlink($mellem);
            return ['ok' => false, 'navn' => $navn, 'fejl' => 'filen kunne ikke gemmes på serveren'];
        }

        indeks_saet($navn, $post);
        $log = laes_json(UPLOAD_LOG, []);
        if ($gammel !== null && $gammel !== $navn) unset($log[$gammel]);
        $log[$navn] = ['af' => $bruger, 'tid' => time()];
        skriv_json(UPLOAD_LOG, $log);

        $status = $gammel !== null ? 'skiftet ud' : 'ny';
        log_haendelse($bruger, ($gammel !== null ? 'skiftede ud: ' : 'lagde op: ') . $navn);
        return ['ok' => true, 'navn' => $navn, 'titel' => $post['title'], 'status' => $status];
    });
}

/* ---- Modtag ---- */
$resultater = [];
$generelFejl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $laengde = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if (empty($_POST) && empty($_FILES) && $laengde > 0) {
        $generelFejl = 'Det du sendte var større end serveren tillader ('
                     . menneske_str(maks_upload()) . '). Læg færre eller mindre filer op ad gangen.';
    } elseif (!csrf_ok()) {
        $generelFejl = 'Siden var udløbet. Genindlæs siden og prøv igen.';
    } else {
        @set_time_limit(300);
        $f = $_FILES['filer'] ?? null;
        if ($f && is_array($f['name'])) {
            foreach ($f['name'] as $i => $n) {
                $resultater[] = haandter_fil((string)$n, (string)$f['tmp_name'][$i], (int)$f['error'][$i], $bruger);
            }
        } elseif ($f) {
            $resultater[] = haandter_fil((string)$f['name'], (string)$f['tmp_name'], (int)$f['error'], $bruger);
        } else {
            $generelFejl = 'Der var ingen filer med.';
        }
    }
    if ($erAjax) {
        if ($generelFejl !== '') svar_json(['ok' => false, 'fejl' => $generelFejl]);
        svar_json($resultater[0] ?? ['ok' => false, 'fejl' => 'Der var ingen filer med.']);
    }
}

/* ---- Siden ---- */
$indeks = hent_indeks();
$log = laes_json(UPLOAD_LOG, []);
uasort($log, fn($a, $b) => ($b['tid'] ?? 0) <=> ($a['tid'] ?? 0));
$seneste = array_slice($log, 0, 10, true);

side_top('Læg filer op', 'Filerne kommer med på søgesiden med det samme.', $bruger, 'upload');
?>

<section class="panel" aria-labelledby="h-upload">
  <h2 id="h-upload">Vælg filer</h2>
  <p>Du kan lægge HTML-filer op, som er lavet med en af Byggerne, fx albums, lydfortællinger,
  e-bøger, henvisninger og videokort. Har en fil samme navn som en, der allerede ligger her,
  bliver den gamle skiftet ud.</p>

  <form id="uploadForm" method="post" action="upload.php" enctype="multipart/form-data">
    <?= csrf_felt() ?>
    <label for="filer" class="felt">Vælg HTML-filer</label>
    <input id="filer" name="filer[]" type="file" accept=".html,.htm,text/html" multiple>
    <button class="rolig" type="button" id="vaelgKnap" hidden>Vælg filer</button>
    <div id="dropzone" hidden>eller træk filerne herned</div>
    <p class="hint" id="valgtTekst"></p>
    <div class="filvalg">
      <button class="stor" type="submit" id="sendKnap">Læg filerne op</button>
    </div>
    <p class="hint">Højst <?= h(menneske_str(maks_upload())) ?> pr. fil.</p>
  </form>

  <div class="status" id="status" role="status" aria-live="polite">
<?php if ($generelFejl !== ''): ?>
    <div class="besked fejl-b"><p><?= h($generelFejl) ?></p></div>
<?php endif; ?>
<?php if ($resultater): ?>
    <ul class="liste" style="margin-top:1rem">
<?php foreach ($resultater as $r): ?>
      <li><span class="hoved">
<?php if ($r['ok']): ?>
        <span class="ok"><?= $r['status'] === 'ny' ? 'Lagt op' : 'Skiftet ud' ?>:</span>
        <span class="t"><?= h($r['titel']) ?></span>
        <span class="m"><?= h($r['navn']) ?></span>
<?php else: ?>
        <span class="fejl">Ikke lagt op:</span> <span class="t"><?= h($r['navn']) ?></span>
        <span class="m"><?= h(ucfirst($r['fejl'])) ?>.</span>
<?php endif; ?>
      </span></li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
  </div>
</section>

<section class="panel" aria-labelledby="h-seneste" id="senesteSektion">
  <h2 id="h-seneste">Senest lagt op</h2>
<?php if (!$seneste): ?>
  <p class="tomt">Der er ikke lagt noget op endnu.</p>
<?php else: ?>
  <ul class="liste">
<?php foreach ($seneste as $navn => $l):
        $p = $indeks['poster'][$navn] ?? null; ?>
    <li><span class="hoved">
      <a class="t" href="filer/<?= h(rawurlencode($navn)) ?>" target="_blank" rel="noopener"><?= h($p['title'] ?? $navn) ?></a>
      <span class="m"><?= h($navn) ?> · <?= h($l['af'] ?? '?') ?>, <?= h(dansk_tid((int)($l['tid'] ?? 0))) ?></span>
    </span></li>
<?php endforeach; ?>
  </ul>
<?php endif; ?>
  <p class="hint">Søgesiden har nu <?= count($indeks['poster']) ?> poster. <a href="index.php">Se søgesiden</a></p>
</section>

<script>
/* Filerne sendes én ad gangen, så store filer ikke tilsammen bliver for
   meget for serveren, og så man kan se, hvordan det går med hver enkelt. */
(function(){
  const form = document.getElementById("uploadForm");
  const input = document.getElementById("filer");
  const zone = document.getElementById("dropzone");
  const knap = document.getElementById("sendKnap");
  const valgt = document.getElementById("valgtTekst");
  const status = document.getElementById("status");
  const csrf = form.querySelector('input[name="csrf"]').value;
  const MAKS = <?= (int)maks_upload() ?>;
  let filer = [];

  /* Med JavaScript skjules det lille standardfelt bag en stor knap. */
  const vaelg = document.getElementById("vaelgKnap");
  input.classList.add("visuelt-skjult");
  form.querySelector('label[for="filer"]').classList.add("visuelt-skjult");
  input.tabIndex = -1;
  vaelg.hidden = false; zone.hidden = false;
  vaelg.addEventListener("click", () => input.click());

  function saetFiler(liste){
    filer = Array.from(liste).filter(f => /\.html?$/i.test(f.name));
    if (!filer.length){ valgt.textContent = ""; return; }
    const navne = filer.slice(0, 5).map(f => f.name).join(", ") + (filer.length > 5 ? " …" : "");
    valgt.textContent = (filer.length === 1 ? "1 fil valgt: " : filer.length + " filer valgt: ") + navne;
  }
  input.addEventListener("change", () => saetFiler(input.files));

  ["dragenter","dragover"].forEach(t => zone.addEventListener(t, e => { e.preventDefault(); zone.classList.add("over"); }));
  ["dragleave","drop"].forEach(t => zone.addEventListener(t, e => { e.preventDefault(); zone.classList.remove("over"); }));
  zone.addEventListener("drop", e => {
    saetFiler(e.dataTransfer.files);
    if (!filer.length) valgt.textContent = "Der var ingen HTML-filer i det, du trak herned.";
  });

  function el(tag, cls, tekst){
    const e = document.createElement(tag);
    if (cls) e.className = cls;
    if (tekst !== undefined) e.textContent = tekst;
    return e;
  }

  function send(fil, li){
    return new Promise(resolve => {
      const hoved = li.querySelector(".hoved");
      const bar = el("progress"); bar.max = 100; bar.value = 0;
      bar.setAttribute("aria-label", "Upload af " + fil.name);
      li.appendChild(bar);

      function slut(r){
        bar.remove();
        hoved.innerHTML = "";
        if (r.ok){
          hoved.append(el("span","ok", r.status === "ny" ? "Lagt op: " : "Skiftet ud: "),
                       el("span","t", r.titel), el("span","m", r.navn));
        } else {
          hoved.append(el("span","fejl","Ikke lagt op: "), el("span","t", fil.name),
                       el("span","m", (r.fejl||"ukendt fejl").replace(/^./, c => c.toUpperCase()) + "."));
        }
        resolve(r);
      }

      if (fil.size > MAKS){
        slut({ok:false, fejl:"filen er større end serveren tillader"}); return;
      }
      const fd = new FormData();
      fd.append("csrf", csrf);
      fd.append("filer[]", fil, fil.name);
      const x = new XMLHttpRequest();
      x.open("POST", "upload.php?ajax=1");
      x.upload.onprogress = e => { if (e.lengthComputable) bar.value = Math.round(e.loaded / e.total * 100); };
      x.onload = () => { let r; try { r = JSON.parse(x.responseText); } catch(e){ r = {ok:false, fejl:"serveren svarede ikke som ventet"}; } slut(r); };
      x.onerror = () => slut({ok:false, fejl:"forbindelsen blev afbrudt"});
      x.send(fd);
    });
  }

  form.addEventListener("submit", async e => {
    e.preventDefault();
    if (!filer.length){ valgt.textContent = "Vælg først en eller flere filer."; return; }
    knap.disabled = true; input.disabled = true;
    status.innerHTML = "";
    const ul = el("ul","liste"); ul.style.marginTop = "1rem";
    status.appendChild(ul);
    const linjer = filer.map(f => {
      const li = el("li"); const h = el("span","hoved");
      h.append(el("span","t", f.name), el("span","m","venter …"));
      li.appendChild(h); ul.appendChild(li); return li;
    });
    let ok = 0;
    for (let i = 0; i < filer.length; i++){
      linjer[i].querySelector(".m").textContent = "sendes …";
      const r = await send(filer[i], linjer[i]);
      if (r.ok) ok++;
    }
    const opsum = el("p", ok === filer.length ? "ok" : "advarsel",
      ok + " af " + filer.length + (filer.length === 1 ? " fil" : " filer") + " er lagt op. ");
    const a = el("a", "", "Se søgesiden"); a.href = "index.php";
    opsum.appendChild(a);
    status.insertBefore(opsum, ul);
    knap.disabled = false; input.disabled = false; input.value = ""; filer = []; valgt.textContent = "";

    /* Hent den nye liste over senest lagt op. */
    try{
      const svar = await fetch("upload.php", {credentials: "same-origin"});
      const ny = new DOMParser().parseFromString(await svar.text(), "text/html").getElementById("senesteSektion");
      if (ny) document.getElementById("senesteSektion").replaceWith(ny);
    }catch(e){}
  });
})();
</script>
<?php side_bund();
