# Builder-standard

**Fælles specifikation for værktøjskassen**
Version 1.3 · oktober 2026

Referenceimplementering: **Sidebygger** (`sidebygger-hs.html`, version 1.4).
Når noget er i tvivl, er det referencefilen der gælder.

---

## 1. Hvad standarden dækker

Værktøjskassen består af flere små, uafhængige builder-apps:

| App | Genererer | dc.type |
|---|---|---|
| Sidebygger | artikelside om gader, steder og mennesker | Text |
| Lydfortælling · Bygger | lydafspiller med billede og tekst | Sound |
| Album · Bygger | billedalbum med grid og lightbox | Image |
| Bogbygger | e-bog / lydbog | Text |
| Enkeltbillede · Bygger | ét billede i høj kvalitet med tekst og Exif | Image |
| Før og Nu · Bygger | to billeder fra samme sted med slider | Image |
| Mediekort · Bygger | kort til en video (YouTube) eller en podcast, med kapitler og evt. transskription | MovingImage (video), Sound (podcast) |
| Henvisning · Bygger | "skilt" der sender videre til en ekstern side | afklares |
| Vejledningsbygger | trin-for-trin vejledning | afklares |
| PWA · Bygger | samler færdige filer til en app til hjemmeskærmen | afklares |
| Metadata-hjælper | ingen fil; laver en AI-prompt til Dublin Core og kontrollerer svaret (se afsnit 11) | - |

Bragt i overensstemmelse med standarden: Sidebygger (v1.4) og Album · Bygger (v3.0, `albumbygger-dc-v3.html`, følger 1.2).

De løser hver sin opgave, men skal se ens ud, opføre sig ens og producere filer med samme metadata-struktur. Det er kun de dele, dette dokument beskriver. Alt andet må gerne være appspecifikt.

Vejledningsbygger og PWA · Bygger afviger bevidst på enkelte punkter, fordi de ikke laver indhold på samme måde som de andre. Afvigelserne skal stå i appens egen beskrivelse.

Metadata-hjælper laver ingen outputfil og har derfor hverken temaer, forhåndsvisning af en side, indlæsning eller afsnit 5. Den følger standarden for builderens udseende (afsnit 2 og 3), `PUBLISHERS`, dc.type-listen og de 15 felter i afsnit 4.

Ældre filer, der er lavet før en bygger blev bragt i overensstemmelse med standarden, laves om i den nye bygger. Byggerne skal ikke bære rundt på kode til at forstå gamle formater.

### Grundprincipper

1. **Én HTML-fil.** Builderen kører lokalt i browseren uden server. Outputfilen indeholder alt (billeder og lyd som base64) og kan åbnes uden internet.
2. **Målgruppen er ældre og ikke nødvendigvis teknisk øvede.** Stor skrift, høj kontrast, tydelige knapper.
3. **Filerne skal kunne læses om 20 år.** Ren HTML, ingen eksterne afhængigheder, metadata efter Dublin Core.

### 1.1 Bevaringsstrategi og backup (3-2-1-reglen)

Selv den mest holdbare HTML-fil har ingen værdi, hvis filen forsvinder fysisk. Alle guider, workshops og brugerflader skal fremhæve 3-2-1-princippet:

- **3 kopier:** den originale fil plus mindst 2 kopier.
- **2 medietyper:** mindst to forskellige lagringsmedier (fx computerens harddisk og et USB-stik).
- **1 off-site:** mindst én kopi opbevaret et andet sted (fx hos et familiemedlem eller i et foreningsarkiv).

### 1.2 Hvor tingene ligger

Standarden og byggerne ligger i ét GitHub-repo:

```
builder-standard.md      ← den officielle udgave
byggere/                 ← én fil pr. bygger, kun nyeste version
arkiv/                   ← gamle versioner
eksempler/               ← en færdig testfil fra hver bygger
```

---

## 2. Layout i builderen

```
┌──────────────────────────────────────────────┐
│ Topbar: Appnavn + linje om hvad den gør,     │
│         "kører lokalt, ingen server", version │
├───────────────────────────┬──────────────────┤
│ Formular-rude (max 660px) │ Forhåndsvisning  │
│                           │ (420px, sticky)  │
│ felter …                  │ iframe med       │
│ Dublin Core (foldet ind)  │ live output      │
│ [ Generér … (.html) ]     │                  │
│ besked-boks               │                  │
└───────────────────────────┴──────────────────┘
```

Grid: `1fr 420px`, falder til én kolonne under 900 px.

Forhåndsvisningen opdateres ved hvert tastetryk (`renderPreview()`) og bygges med samme funktion som den endelige fil, ellers driver de fra hinanden. Tællerkoden udelades bevidst i forhåndsvisningen.

Apps med mange eller store billeder må tegne selve rammen med en kort forsinkelse (højst 0,3 sekund), så skrivningen ikke bliver tung. Felter, Dublin Core og størrelsesvisning opdateres stadig med det samme. Forhåndsvisningen må også vise et begrænset antal billeder (Album · Bygger viser de første 24) med en kort note om, at alle kommer med i den færdige fil.

Topbarens overskrift er appens navn i Georgia 25 px. Versionsnummeret står i underlinjen, fx "… · kører lokalt, ingen server · version 1.4". Browserfanens `<title>` er appens navn.

### Rækkefølge af felter

1. Indlæs eksisterende fil (valgfri), altid øverst
2. Tema
3. Appens egne indholdsfelter (mærkat, titel, sted, billede, lyd, tekst …)
4. Knapper i bunden (valgfri CTA: knaptekst + URL)
5. Hjem-knap (se 5.3)
6. "Nederst på den færdige side": afkrydsning for **Gem filen** og **Om denne fil**
7. Dublin Core-metadata, sammenfoldet `<details>`-panel
8. Generér-knap + beskedboks

---

## 3. Design-tokens

### Farver i builderens egen brugerflade

```
--paper:     #EDE7D8  /* baggrund */
--paper-dim: #E2DBC8  /* forhåndsvisningsrude */
--ink:       #211E19  /* brødtekst */
--rust:      #9A4630  /* accent, knapper, fokus */
--moss:      #47543C  /* feltlabels */
--hint:      #5F5A4B  /* hjælpetekster */
--line:      #cbc2a8  /* streger og rammer */
--serif: Georgia, 'Iowan Old Style', 'Palatino Linotype', Palatino, serif;
--sans:  -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
```

Builderens egen brugerflade har altid disse farver, uanset hvilket tema der er valgt til outputfilen.

### Skriftstørrelser i builderen, minimum

| Element | Størrelse |
|---|---|
| Topbar-overskrift | 25 px, Georgia |
| Feltlabel | 13,5 px, halvfed, versaler, spatiering 0,09em |
| Hjælpetekst under felt | 13,5 px, linjehøjde 1,55 |
| Inputfelt og textarea | 15,5 px |
| Dublin Core-feltlabel | 14 px |
| Dublin Core-hjælpetekst | 13 px |
| Filboks og status | 14,5 / 13,5 px |
| Generér-knap | 17 px, fed |
| Beskedboks | 14,5 px |

Kontrastkrav: al tekst mindst 4,5:1 mod sin baggrund.

### Temaerne til outputfilen

Fem temaer, samme nøgler og samme værdier i alle apps. Vælges med store farvede radioknapper med farveprikker, i denne rækkefølge. Hvert tema har både lys og mørk udgave (`d`-præfiks = mørk tilstand).

| Nøgle | Navn i vælgeren | Accent |
|---|---|---|
| `original` | Oprindeligt | #9A4630 |
| `historisk` | Historisk Samfund | #385261 |
| `lokalhistorisk2` | Lokalhistorisk | #9E2453 |
| `roedekors` | Røde Kors | #E30A0B |
| `roskildetv` | Roskilde TV | #B03035 |

`label` i `THEMES` er temaets fulde beskrivelse. Vælgeren viser det korte navn fra tabellen, så knapperne kan stå på én linje.

Den fulde definition kopieres uændret ind i hver bygger:

```js
const THEMES = {
  original: {
    label:'Oprindeligt',
    accent:'#9A4630', accentDark:'#82392A', onAccent:'#FDF8EE',
    paper:'#EDE7D8', surface:'#FBF8EF', ink:'#211E19', body:'#3A362E',
    muted:'#6B6455', line:'#D8D0BA', quote:'#5A5646',
    dPaper:'#171613', dSurface:'#201E1A', dInk:'#EDE7D8', dBody:'#DDD5C4',
    dMuted:'#A79E8B', dLine:'#3A3630', dQuote:'#BDB5A3'
  },
  historisk: {
    label:'Historisk Samfund',
    accent:'#385261', accentDark:'#2A3D49', onAccent:'#FFFFFF',
    paper:'#F2F5F6', surface:'#FFFFFF', ink:'#16232A', body:'#2A3A42',
    muted:'#5F707A', line:'#DDE3E6', quote:'#4A5C66',
    dPaper:'#151D22', dSurface:'#1D272D', dInk:'#E9EEF1', dBody:'#DBE3E8',
    dMuted:'#A5B5BE', dLine:'#32414B', dQuote:'#B8C6CD'
  },
  lokalhistorisk2: {
    label:'Lokalhistorisk (bordeaux)',
    accent:'#9E2453', accentDark:'#7A1B40', onAccent:'#FAF9F5',
    paper:'#F3EFEF', surface:'#FFFFFF', ink:'#2A1620', body:'#332028',
    muted:'#6B5560', line:'#E4DADE', quote:'#5C3B48',
    dPaper:'#1A1116', dSurface:'#241820', dInk:'#F7EFF2', dBody:'#E9DDE2',
    dMuted:'#B9A4AD', dLine:'#3D2C34', dQuote:'#C7AEB8'
  },
  roedekors: {
    label:'Røde Kors',
    accent:'#E30A0B', accentDark:'#B80809', onAccent:'#FFFFFF',
    paper:'#F5F5F5', surface:'#FFFFFF', ink:'#1A1A1A', body:'#2B2B2B',
    muted:'#5A5A5A', line:'#E2E2E2', quote:'#333333',
    dPaper:'#161616', dSurface:'#1F1F1F', dInk:'#F2F2F2', dBody:'#E0E0E0',
    dMuted:'#AAAAAA', dLine:'#383838', dQuote:'#C4C4C4'
  },
  roskildetv: {
    label:'Roskilde TV',
    accent:'#B03035', accentDark:'#8A2429', onAccent:'#FFFFFF',
    paper:'#F4F4F4', surface:'#FFFFFF', ink:'#141414', body:'#222222',
    muted:'#555555', line:'#E2E2E2', quote:'#333333',
    dPaper:'#141414', dSurface:'#1E1E1E', dInk:'#F2F2F2', dBody:'#DEDEDE',
    dMuted:'#A8A8A8', dLine:'#363636', dQuote:'#C2C2C2'
  }
};
```

Roskilde TV's farveprik i vælgeren er flerfarvet (logoets seks prikfarver). Mediekort · Bygger må desuden vise prikrækken i selve kortet.

Temaet skrives ud som CSS-variabler i `:root` i den genererede fil, aldrig som faste farver inde i reglerne. Kommer et nyt tema til, tilføjes det her først og kopieres derefter til alle byggere.

---

## 4. Dublin Core

Samme 15 felter, samme rækkefølge og samme labels i alle apps. Hjælpeteksten starter altid med feltnavnet (fx `dc.creator —`) og må derefter tilpasses appen (fx "den der har skrevet siden" i Sidebyggeren).

| # | Felt | Label | Standard-hjælpetekst | Standard |
|---|---|---|---|---|
| 1 | dc.title | Titel | udfyldes automatisk fra titlen | (auto) |
| 2 | dc.creator | Ophav / skaber | den der har skabt materialet | |
| 3 | dc.subject | Emne / nøgleord | adskil med semikolon | |
| 4 | dc.description | Beskrivelse | udfyldes automatisk | (auto) |
| 5 | dc.publisher | Udgiver / område | vælg område, så filen kan filtreres i søgeindekset | tom |
| 6 | dc.contributor | Bidragyder | den der har redigeret, fotograferet eller registreret | |
| 7 | dc.date | Dato | helst ÅÅÅÅ-MM-DD, men fx "ca. 1955" er også i orden | |
| 8 | dc.type | Type | DCMI-typeordforråd | appens egen |
| 9 | dc.format | Format | filens tekniske format | text/html |
| 10 | dc.identifier | Identifikator | foreslås automatisk ud fra område og titel | (auto) |
| 11 | dc.source | Kilde | hvor oplysningerne stammer fra | |
| 12 | dc.language | Sprog | sprogkode, fx da | da |
| 13 | dc.relation | Relation | beslægtet materiale | |
| 14 | dc.coverage | Dækning | sted og/eller periode, udfyldes automatisk fra Sted | (auto) |
| 15 | dc.rights | Rettigheder | ophavsret og vilkår for brug | |

Dropdown til dc.type, i denne rækkefølge: Text, Image, Sound, MovingImage, InteractiveResource, Collection, Event. Hver app forvælger sin egen.

### Regler

- Alle felter er valgfrie. Tomme felter udelades helt af outputfilen.
- Fire felter udfyldes automatisk, indtil man selv skriver i dem (`dcTouched`-flag): title, description, coverage og identifier. description afstribes for Markdown og forkortes til 300 tegn.
- subject deles ved semikolon og skrives som ét `<meta>`-tag pr. nøgleord.
- Panelet er sammenfoldet som standard.
- Knappen **Gem metadata som separat JSON-fil** giver `<slug>-metadata.json`:
  `{ "@context": "http://purl.org/dc/elements/1.1/", "dublinCore": { … }, "generated": "…Z" }`

I den genererede fil skrives metadata to steder:

1. I `<head>`: `<link rel="schema.DC" href="http://purl.org/dc/elements/1.1/">` efterfulgt af `<meta name="DC.xxx" content="…">` i feltrækkefølgen. Dette sker altid.
2. Nederst på siden: et udfoldeligt panel **Om denne fil** med label, teknisk feltnavn i småt og værdien, skrevet som færdig HTML. Kan slås fra (se 5.4).

Sidens `<html lang="…">` sættes fra dc.language, ellers `da`.

### 4.1 Udgiver og område

Hver fil skal kunne henføres til præcis én udgiver, så en samling kan filtreres, pakkes og videregives uden manuel gennemgang.

dc.publisher vises som en rulleliste, aldrig som frit tekstfelt, med standardvalget "(ikke valgt)". Listen ligger som ét `PUBLISHERS`-array øverst i scriptet og kopieres uændret til alle byggere:

```js
const PUBLISHERS = [
  { name:'Historisk Samfund for Roskilde Amt',      prefix:'HSR'  },
  { name:'Privat',                                  prefix:'PRIV' },
  { name:'Roskilde TV',                             prefix:'RTV'  },
  { name:'Røde Kors',                               prefix:'RKR'  },
  { name:'Syd for Banen - Lokalhistorisk Forening', prefix:'SFB'  }
];
```

Værdierne gengives tegn for tegn, med **almindelig bindestreg**. Afvigende stavemåder (tankestreg, "Røde Kors Roskilde", "SFB", "privat" med lille p) skaber nye grupper i søgesiden og regnes som fejl.

Indlæses en fil med en gammel værdi, rettes den til den gældende: tankestreg bliver til bindestreg, og "Røde Kors Roskilde" bliver til "Røde Kors". Status ved indlæsning nævner rettelsen. Står der et helt ukendt navn, vises det som ekstra valg markeret "ikke på listen", så det ikke går tabt, men kan rettes.

**dc.identifier** foreslås automatisk som `PRÆFIKS-type-titelslug-år`, fx `SFB-side-astersvej-2026` eller `SFB-album-skomagervaerkstedet-paa-algade-2026`. Titelslug laves efter reglen i 5.6. `type` er et kort, fast ord for appen (side, lyd, album, bog, billede, foernu, video). Forslaget kan overskrives.

**dc.subject:** udgiveren står som første emneord. En app med faste serier (fx Sidebyggerens "Gader og veje" og "Steder, institutioner og mennesker") indsætter serien som andet emneord.

`Syd for Banen - Lokalhistorisk Forening; Gader og veje; Astersvej; velfærdsbyggeri`

**dc.rights** beskriver, hvad andre må med filen, og udfyldes uafhængigt af udgiveren. Typiske værdier:

| Udgiver | Typisk dc.rights |
|---|---|
| Privat | Privat materiale. Må ikke videregives. |
| Syd for Banen | © Syd for Banen - Lokalhistorisk Forening. Må gengives med kildeangivelse. |
| Røde Kors | © Røde Kors. Må anvendes i foreningens formidling. |

**Adskillelse på disken:** arbejdsfilerne ligger i én mappe pr. udgiver, og en distributionspakke bygges fra én mappe ad gangen.

**Søgesiden** bygger sit udgiverfilter ud fra de værdier, der faktisk findes i samlingen. Filer uden udgiver samles under **Uden område** og rettes ved kilden.

**Nye udgivere** tilføjes i `PUBLISHERS` ovenfor, før de tages i brug, derefter i alle byggere og i prompten i afsnit 11.

---

## 5. Outputfilens kontrakt

### 5.1 Kildedata

Hver genereret fil indeholder en JSON-blok, der gør filen redigerbar igen:

```html
<script type="application/json" id="builder-source-data">
{ "format": "sidebygger-side",
  "builderVersion": 1,
  "theme": "roedekors",
  "title": "…",
  "dublinCore": { … } }
</script>
```

- `format` er appens eget navn (sidebygger-side, lydfortaelling-player, billedalbum, ebog, foer-og-nu …).
- `builderVersion` er et heltal, der tælles op ved formatændringer.
- `theme` er en af nøglerne fra afsnit 3.
- Blokken indeholder den rå Markdown, ikke færdig HTML.
- Billeder og lyd hører ikke til i JSON-blokken. De læses tilbage fra dokumentet.

### 5.2 Øvrigt indhold i `<head>`

- og:type, og:title, og:description altid. og:image + twitter:card kun hvis **Webadresse til delingsbillede** er udfyldt.
- `<link rel="icon">` sat til det indlejrede billede.

### 5.3 Hjem-knap

Øverst i outputfilen kan der stå en hjem-knap. Builderen har tre valg:

- **Automatisk** (standard): knappen vises kun, når filen er del af noget større. Filen viser den, hvis den er åbnet som installeret app, hvis man kom fra en side i samme mappe, eller hvis der ligger en `index.html` ved siden af (online). Åbnes filen alene, bliver knappen væk.
- **Altid vist**
- **Aldrig vist**

Knaptekst (standard "Hjem") og adresse (standard `index.html`) kan ændres. En adresse i URL'en (`?hjem=…`) går forud for den indbyggede. I forhåndsvisningen vises knappen altid.

### 5.4 Nederst i filen

- **Gem filen**-knap: pilikon i accentfarven + teksten **Gem filen** (fed), pilleform (`border-radius:999px`) med 2 px ramme, mindst 48 px høj, fyldes ved hover, 3 px fokus-outline. Under knappen en kort kursiveret linje om hvad filen er, fx "Et billedalbum. Gemmer hele albummet som én fil på din egen computer." Knappen gemmer en ren kopi: mørk tilstand, skriftstørrelse og op-pil nulstilles før gem.
- **Om denne fil**-panelet (se afsnit 4).
- Begge kan slås fra i builderen, fx når siden vises inde på et website. Dublin Core i `<head>` følger altid med.
- Manuelt indsat tællerkode mellem `TÆLLER` og `TÆLLER SLUT` bevares uændret. Builderen skriver aldrig selv tællerkode.

### 5.5 Læsehjælp i outputfilen

- **Mørk tilstand**: knap i toppen, bruger temaets `d`-farver.
- **Skriftstørrelse**: knapper til mindre og større skrift, skala 0,85-1,7 i trin af 0,1 via `--fs`-variablen. Al typografi i outputfilen er i `em` eller ganges med `--fs`.
- **Op-pil**: rund knap nederst til højre i accentfarven, 48 × 48 px, vises efter 400 px scroll.
- Ved udskrift skjules værktøjslinje, op-pil og gem-knap.

Korte outputfiler (fx et enkelt billede) må udelade skriftstørrelse og op-pil.

### 5.6 Filnavn

Slug af titlen: små bogstaver, **æ→ae, ø→oe, å→aa**, derefter fjernes accenter (é→e, ü→u), og alt andet end a-z0-9 bliver til bindestreg. Samme funktion bruges til filnavn og til titelslug i dc.identifier, så de altid passer sammen.

```js
function slugify(s, fallback){
  const out = (s || '').toLowerCase()
    .replace(/æ/g,'ae').replace(/ø/g,'oe').replace(/å/g,'aa')
    .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
    .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
  return out || fallback || '';
}
```

Eksempel: "Skomagerværkstedet på Algade" → `skomagervaerkstedet-paa-algade.html`.

Filer, der er lavet før version 1.2, beholder deres filnavn og identifikator. Reglen gælder kun nye filer, og en gammel identifikator overskrives ikke, når filen indlæses igen.

---

## 6. Markdown i tekstfelter

Alle apps understøtter den samme lille delmængde.

| Skrives | Bliver til |
|---|---|
| `**fed**` | fed |
| `*kursiv*` | kursiv |
| `## Overskrift` | `<h2>` |
| `### Overskrift` | `<h3>` |
| `- punkt` | punktliste |
| `1. punkt` | nummereret liste |
| `> citat` | citat med streg i accentfarven |
| `[tekst](https://…)` | link, åbner i nyt faneblad |
| `---` | vandret streg |
| `` `kode` `` | kode med baggrund |

Tekst escapes før oversættelsen. Derfor skal reglerne matche den escapede tekst: et citat genkendes som `&gt;` i starten af linjen, ikke som `>`. Sidens titel er `<h1>`, så et enkelt `#` giver også `<h2>`. Hjælpeteksten viser syntaksen med eksempler.

---

## 7. Indlæsning af eksisterende fil

- Feltet står øverst og hedder **Indlæs eksisterende … (valgfri)**.
- Filen parses med DOMParser, og `builder-source-data` læses. Mangler blokken, afvises filen med: "Filen kan ikke indlæses — den er ikke lavet med denne udgave af værktøjet."
- Har filen et andet `format`, afvises den med: "Filen er lavet med et andet værktøj i serien og kan ikke indlæses her."
- Er `builderVersion` lavere end byggerens egen, afvises filen med samme besked som ved manglende blok. Byggeren indeholder ikke kode til at omsætte gamle formater; filen laves om.
- Billeder og lyd hentes tilbage fra data:-URL'erne.
- Tællerkoden følger med over.
- Udgiverværdier rettes til gældende streng (se 4.1).
- Status kvitterer med filnavnet og nævner, hvis tællerkoden blev fundet.

---

## 8. Billeder

Skaleres til maks. **1600 px** på den længste led og gemmes som JPEG, kvalitet **0,82**. Faste indstillinger, ingen knapper at skrue på. Statuslinjen viser omtrentlig størrelse efter komprimering.

Undtagelse: Enkeltbillede · Bygger gemmer i høj opløsning (3000-4000 px eller originalen) og bevarer Exif, fordi det er appens formål.

Bemærk: canvas-komprimering fanger kun første billede i en animeret GIF.

Billederne behandles ét ad gangen, så computeren ikke går i stå, når man vælger mange store billeder på én gang. Rækkefølgen følger det valgte.

### 8.1 Oplysninger fra sidecar-filer

Byggere, der tager imod billeder, kan læse oplysninger fra de små filer, som andre systemer lægger ved siden af billederne. Man markerer billeder og sidecar-filer på én gang i samme filvælger.

| Kilde | Filtype | Hentes | Til |
|---|---|---|---|
| Google Takeout, pr. billede | `.json` (også `.supplemental-metadata.json`) | `description`, `photoTakenTime` | billedets beskrivelse, optagelsesdato |
| Google Takeout, albummet | `metadata.json` | `title`, første `narrativeEnrichment.text` | albummets titel og beskrivelse |
| Fotosafari | `.txt`, linjer: fotograf, beskrivelse, bredde, længde, gruppe, kode | fotograf, beskrivelse | dc.creator (flere adskilt af semikolon), billedets beskrivelse |

Regler:

- Sidecar og billede kobles på filnavnet uden endelse, uden forskel på store og små bogstaver.
- Et felt udfyldes kun, hvis det er tomt. Det, man selv har skrevet, overskrives aldrig.
- Et usynligt BOM-tegn forrest i en tekstfil fjernes.
- Koordinater, gruppe og kode fra Fotosafari bruges ikke og kommer ikke med i outputfilen.
- Er der optagelsesdatoer, vises knappen **Sortér efter optagelsesdato**, og datoen gemmes i kildedata, så sorteringen også virker efter genindlæsning.
- Beskedboksen fortæller, hvor mange billeder der fik oplysninger, eller at ingen filer passede.

---

## 9. Tilgængelighed

- Kontrast mindst 4,5:1 for al tekst, i både lys og mørk tilstand.
- Synligt fokus: 2 px outline i builderen, 3 px på gem-knappen.
- Klikflader mindst 44 × 44 px.
- Ingen information formidlet med farve alene.
- Hvert billede har et felt til alt-tekst. Er det tomt, bruges billedets titel.
- Layoutet virker ned til mobilbredde.
- Sproget er dansk, i hele sætninger, uden fagudtryk. En knap siger hvad der sker.

---

## 10. Tjekliste

**Builderen**
- [ ] Topbar med appnavn, en linje om hvad den gør, og versionsnummer
- [ ] To-rudet layout (1fr 420px) med live forhåndsvisning i iframe
- [ ] Feltrækkefølgen fra afsnit 2
- [ ] Skriftstørrelser og kontrast fra afsnit 3
- [ ] De fem temaer med `THEMES` kopieret uændret fra afsnit 3
- [ ] Indlæsning af egne filer med pæn afvisning af fremmede filer, andre formater og ældre builderVersion
- [ ] Billeder 1600 px / 0,82 (undtagen Enkeltbillede)
- [ ] Alt-tekstfelt pr. billede
- [ ] Sidecar-filer læses efter 8.1 (hvor appen tager imod billeder)

**Dublin Core**
- [ ] Alle 15 felter, i rækkefølge, med samme labels
- [ ] Hjælpetekster starter med feltnavnet
- [ ] dc.type-listen fra afsnit 4 med appens egen type forvalgt
- [ ] Automatisk udfyldning af title, description, coverage og identifier med dcTouched-flag
- [ ] `PUBLISHERS` kopieret uændret fra 4.1, vist som rulleliste
- [ ] Gamle udgiverværdier rettes ved indlæsning
- [ ] Udgiveren står som første emneord
- [ ] Identifikator foreslås som `PRÆFIKS-type-titelslug-år`
- [ ] Knap til JSON-eksport af metadata

**Outputfilen**
- [ ] DC-meta i `<head>` og "Om denne fil"-panel
- [ ] `builder-source-data` med format, builderVersion og theme
- [ ] Temaet som CSS-variabler i `:root`, med mørk tilstand
- [ ] Hjem-knap med Automatisk / Altid / Aldrig
- [ ] "Gem filen" gemmer en ren kopi
- [ ] "Gem filen" og "Om denne fil" kan slås fra
- [ ] Skriftstørrelse og op-pil (på lange sider)
- [ ] Tællerkode bevares ved genindlæsning
- [ ] og:-tags og felt til delingsbillede
- [ ] Markdown-delmængden fra afsnit 6, også citat
- [ ] Filnavn som slug af titlen med æ→ae, ø→oe, å→aa (5.6)
- [ ] "Gem filen" i pilleform med kursiv linje under

---

## 11. AI-assisteret udfyldning af Dublin Core

### 11.1 Formål

Når flere foreninger og enkeltpersoner leverer materiale, skal metadata udfyldes ens. En AI (fx et Claude-projekt med prompten nedenfor som instruktion) læser teksten og foreslår de 15 felter. Et menneske godkender altid resultatet, før det sættes ind i en bygger.

### 11.2 Arbejdsgang

Arbejdsgangen foregår i **Metadata-hjælper**, som laver INDLEDNINGEN ud fra en formular og kontrollerer AI'ens svar. Den kan også gøres i hånden.

1. Bidragyderen udfylder INDLEDNING (i Metadata-hjælperens formular eller øverst i sin tekst).
2. Prompten med INDLEDNING og tekst indsættes i AI'en, som svarer med de 15 felter.
3. Svaret kontrolleres. Metadata-hjælper retter kendte stavemåder af udgiveren og markerer felter med [?], forkert dato, forkert type, udfyldt identifikator, udgiver der ikke står som første emneord, navne der ikke findes i teksten, og felter der afviger fra INDLEDNINGEN. Felterne kan ikke kopieres videre, før fejl og [?] er rettet.
4. Identifikatoren overlades til byggeren, der foreslår den automatisk.
5. Felterne kopieres ind i byggeren.

Metadata-hjælper kan læse svaret, uanset om AI'en skriver ét felt pr. linje, alle felter i ét afsnit, en tabel eller med fed skrift. Afprøvet med Claude, Gemini og Copilot 365.

### 11.3 Indledning (udfyldes af bidragyder)

```
INDLEDNING
Navn (ophav):
Øvrige bidragydere:
Dato for materialet:
Udgiver:
Rettigheder:
Kilde (hvor stammer materialet fra):
Arbejdstitel:
Type:
Sted og periode:

TEKST
...
```

De tre sidste linjer er valgfrie. Type skrives som dc.type-værdien (fx Sound). I Metadata-hjælper udfyldes den ud fra den valgte bygger.

### 11.4 Prompt

```
Du udfylder Dublin Core-metadata ud fra en tekst med en INDLEDNING.
Svar KUN med de 15 felter nedenfor, i denne rækkefølge, én linje
pr. felt. Ingen indledning, ingen kommentarer, ingen forklaringer.

REGLER
- Brug oplysninger fra INDLEDNING præcis som de står.
- Gæt aldrig på navne, datoer eller rettigheder. Mangler de,
  lad feltet stå tomt.
- Er du i tvivl om et forslag, skriv [?] efter det.
- Dato: helst ÅÅÅÅ-MM-DD. ÅÅÅÅ-MM, ÅÅÅÅ eller "ca. ÅÅÅÅ" er også i orden.
- Udgiver: KUN én af disse, stavet præcis sådan:
  Historisk Samfund for Roskilde Amt
  Privat
  Roskilde TV
  Røde Kors
  Syd for Banen - Lokalhistorisk Forening
  Passer ingen, lad feltet stå tomt.
- Emne: udgiveren som første emneord, derefter 3-6 emneord
  adskilt af semikolon.
- Beskrivelse: 2-4 saglige sætninger uden vurderinger.
- Type: Text, Image, Sound, MovingImage, InteractiveResource,
  Collection eller Event.
- Format: text/html.
- Identifikator: lad altid stå tom.
- Sprog: da, medmindre teksten er på et andet sprog.
- Dækning: sted og/eller periode som teksten handler om.

FELTER
Titel (dc.title):
Ophav / skaber (dc.creator):
Emne / nøgleord (dc.subject):
Beskrivelse (dc.description):
Udgiver / område (dc.publisher):
Bidragyder (dc.contributor):
Dato (dc.date):
Type (dc.type):
Format (dc.format):
Identifikator (dc.identifier):
Kilde (dc.source):
Sprog (dc.language):
Relation (dc.relation):
Dækning (dc.coverage):
Rettigheder (dc.rights):
```

### 11.5 Vedligeholdelse

Udgiverlisten i prompten skal altid svare til `PUBLISHERS` i 4.1. Kommer der en ny udgiver, rettes den tre steder: i `PUBLISHERS` i byggerne, i prompten her og i Metadata-hjælper (som bygger prompten ud fra sin egen kopi af `PUBLISHERS`).

Ændres prompten her, rettes `PROMPT` i Metadata-hjælper tilsvarende.

---

## 12. Ændringslog

**Version 1.3 — oktober 2026.**
- Videokort · Bygger er afløst af Mediekort · Bygger, som laver kort til både video (MovingImage) og podcast (Sound).
- Nyt værktøj: Metadata-hjælper (v1.1), der laver INDLEDNING og prompt efter afsnit 11 og kontrollerer AI'ens svar. Afviger bevidst fra standarden, fordi den ikke laver en fil.
- INDLEDNING i 11.3 udvidet med Arbejdstitel, Type og Sted og periode. Prompten i 11.4 er uændret.
- 11.2 beskriver arbejdsgangen med Metadata-hjælper.
- 11.5: udgiverlisten rettes nu også i Metadata-hjælper.

**Version 1.2 — september 2026.**
- Slug: æ→ae, ø→oe, å→aa, før accenterne fjernes. Gælder filnavn og titelslug i dc.identifier. Gamle filer beholder deres navn.
- "Gem filen": pilleform (999px) og kursiv linje under knappen er nu fastlagt. Rettet i Sidebygger 1.4.
- Temavælgeren viser "Lokalhistorisk", som i referencen. `THEMES` er uændret.
- Markdown: reglerne skal matche den escapede tekst. Citat-fejlen i Sidebyggeren er rettet i 1.4.
- Indlæsning: filer med andet format eller lavere builderVersion afvises.
- Forhåndsvisning: kort forsinkelse og loft over billeder er tilladt i billedtunge apps.
- Nyt afsnit 8.1 om sidecar-filer fra Google Takeout og Fotosafari.
- Alt-tekstfelt pr. billede (afsnit 9).
- Album · Bygger v3.0 er bragt i overensstemmelse med standarden.
- Sidebygger v1.4 følger 1.2 og er fortsat referenceimplementering. Den har desuden fået felt til delingsbillede og `<link rel="icon">`, som manglede efter 5.2.

**Version 1.1 — september 2026.**
- Ny referenceimplementering: Sidebygger (`sidebygger-hs.html`, v1.3) i stedet for Lydfortælling · Bygger.
- Layout 1fr 420px / 900 px / 660 px, som i referencen.
- Fem temaer med lys og mørk udgave, defineret i ét `THEMES`-objekt. Nyt: Historisk Samfund og Roskilde TV.
- Fem faste udgivere i ét `PUBLISHERS`-array med præfikser. "Røde Kors Roskilde" er erstattet af "Røde Kors". Almindelig bindestreg.
- Områdetillægget fra v1.0 er indarbejdet i afsnit 4.1.
- Identifikator foreslås automatisk (`PRÆFIKS-type-titelslug-år`) i stedet for løbenummer.
- dc.type-listen udvidet med MovingImage.
- Hjælpetekster til DC må tilpasses appen, når de starter med feltnavnet.
- Hjem-knap med Automatisk / Altid / Aldrig.
- "Gem filen" og "Om denne fil" kan slås fra. "Gem filen" gemmer en ren kopi.
- Mørk tilstand, skriftstørrelse og op-pil i outputfilen.
- Billeder 1600 px / 0,82 i stedet for 1100 px / 0,78.
- Filer lavet før ensretningen laves om. Ingen kode til gamle formater.
- Nyt afsnit 11 om AI-assisteret udfyldning af Dublin Core.

**Version 1.0 — august 2026.** Første udgave, skrevet ud fra Lydfortælling · Bygger version 2.

---

## 13. Åbne punkter

**Afklares, når byggeren tages op:**
- dc.type for Henvisning, Vejledning og PWA.
- Kort typeord i dc.identifier for Mediekort · Bygger: "video" for video, og hvilket ord for podcast (fx "podcast" eller "lyd").
- Hvilke af byggerne der skal have skriftstørrelse og op-pil (korte outputfiler kan undvære dem). Afgjort: Sidebygger og Album · Bygger har begge dele.
