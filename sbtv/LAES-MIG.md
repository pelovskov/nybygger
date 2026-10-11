# Søgeindeks · Online

Onlineudgaven af *Søgeside · Bygger*. Søgesiden ligger på et webhotel med PHP og
opdaterer sig selv, hver gang nogen lægger en fil op. Filerne er de samme som før:
singlefile-HTML fra Byggerne, henvisninger og videokort.

## Filerne

| Fil | Hvad den gør |
|---|---|
| `index.php` | Søgesiden. Den er offentlig. |
| `upload.php` | Læg filer op (kræver login). |
| `admin.php` | Slet filer, byg søgesiden forfra, ret overskrift, se hvem der har gjort hvad. |
| `login.php`, `logout.php` | Log ind og ud. |
| `lav-hash.php` | Laver adgangskode-linjer til `config.php`. Gemmer intet. |
| `config.php` | Brugere og indstillinger. Den eneste fil, du normalt retter i. |
| `faelles.php` | Fælles kode, herunder skabelonen til søgesiden. |
| `filer/` | Alle de filer, der er lagt op, plus en færdig `soegeside.html`. |
| `filer/thumbs/` | Små billeder (240 × 180 px) til søgesiden og forsiden. Laves af sig selv. |
| `data/` | Indeks, log og papirkurv. Spærret for adgang udefra. |

## Sådan sætter du det op

1. Læg hele mappen op på webhotellet, fx i `/arkiv/`.
2. Åbn `https://ditdomæne.dk/arkiv/lav-hash.php`, skriv brugernavn og adgangskode,
   og kopiér den linje, du får.
3. Sæt linjen ind i `config.php` under `$BRUGERE`. Gentag for hver bruger.
   Dine eksisterende bcrypt-hashes (`$2b$` eller `$2y$`, cost 12) kan bruges direkte.
4. Skal kun nogle af brugerne kunne slette, så skriv dem i `$ADMINS`, fx `['kim']`.
5. Sæt `$TILLAD_HASH_SIDE = false;`, når alle er oprettet.
6. Log ind via `upload.php`, og ret overskriften på admin-siden.

## Tjek at data-mappen er spærret

Åbn `https://ditdomæne.dk/arkiv/data/indeks.json` i browseren. Du skal få
**Forbidden** (403). Får du filens indhold at se, læser serveren ikke `.htaccess`
(det sker på nginx-servere). Så skal webhotellets support spærre mappen `data/`.

Får du **500 Internal Server Error** på filerne i `filer/`, tillader webhotellet ikke
`.htaccess` der. Slet så `filer/.htaccess`. Systemet tager kun imod `.html`-filer
alligevel, så det er en ekstra sikring, ikke den eneste.

## Hvordan det virker

- Kun `.html`/`.htm`-filer med metadata fra Byggerne tages imod.
  Filer uden metadata afvises med en forklaring.
- En fil med samme navn som en eksisterende erstatter den gamle, også hvis store og
  små bogstaver er forskellige.
- Filerne sendes op én ad gangen med en statuslinje, så mange store filer ikke
  tilsammen sprænger serverens grænse. Grænsen pr. fil vises på upload-siden.
- Slettede filer lægges i `data/papirkurv/` og kan hentes tilbage med FTP,
  indtil papirkurven tømmes på admin-siden.
- Lægger du filer op eller sletter du dem med FTP, så tryk
  **Byg søgesiden forfra** på admin-siden.
- Henvisninger med andet end en `http(s)`-adresse bliver ikke til links.
- **Små billeder:** Når en fil lægges op, gemmes det første billede i den som en lille
  JPEG i `filer/thumbs/`. Det kræver GD på webhotellet. Filer uden billede får et ikon.
  Filer, der lå der før denne funktion kom til, får billeder, når du trykker
  **Byg søgesiden forfra** på admin-siden. Billederne slettes sammen med filen.
- Søgesiden og forsiden åbner filerne i samme faneblad med `?hjem=` i adressen, så
  Hjem-knappen i filerne fører tilbage til søgningen – med de samme filtre.
- Fem forkerte adgangskoder fra samme sted giver 15 minutters pause.

## Offline-kopi

Mappen `filer/` indeholder altid en opdateret `soegeside.html`. Hent hele mappen
med FTP – også undermappen `thumbs/` – så har du et færdigt arkiv til USB-stik eller
tidskapsel, der virker uden internet, præcis som med den lokale Søgeside · Bygger.

## Flere foreninger

- **Ét fælles arkiv:** alle får en bruger i samme installation. Filteret *Område*
  på søgesiden sorterer efter `publisher`, så hver forening kan findes for sig.
- **Hver sit arkiv:** kopiér hele mappen til en ny undermappe med sin egen
  `config.php`. Installationerne deler hverken filer eller login.

## Én ting at vide om sikkerhed

De filer, der lægges op, er HTML med JavaScript og kører på samme domæne som
upload- og admin-siden. Giv derfor kun login til folk, du stoler på.
