# Builder-standard

**Fælles specifikation for værktøjskassen**
Version 1.1 · september 2026

Referenceimplementering: Lydfortælling · Bygger (`lyd-fort-gen-dc-v3.html`).
Når dette dokument og en referencefil er uenige om **udgivere, id-præfikser og temaer**, er det dokumentet der gælder. På alle andre punkter gælder referencefilen, når noget er i tvivl.

---

## 1. Hvad standarden dækker

Værktøjskassen består af flere små, uafhængige builder-apps:

| App | Genererer | dc.type |
|---|---|---|
| Lydfortælling · Bygger | lydafspiller med billede og tekst | Sound |
| Album · Bygger | billedalbum med grid og lightbox | Image |
| Bogbygger | e-bog / lydbog | Text |
| Enkeltbillede · Bygger | ét billede i høj kvalitet med tekst og Exif | Image |
| Før og Nu · Bygger | to billeder fra samme sted med slider | Image |
| Sidebygger | artikelside om gader, steder og mennesker | Text |
| Videokort · Bygger | kort til en YouTube-udsendelse med kapitler | afklares (se afsnit 13) |
| Henvisning · Bygger | "skilt" der sender videre til en ekstern side | afklares |
| Vejledningsbygger | trin-for-trin vejledning | afklares |
| PWA · Bygger | samler færdige filer til en app til hjemmeskærmen | afklares |

De løser hver sin opgave, men skal se ens ud, opføre sig ens og producere filer med samme metadata-struktur. Det er kun de dele, dette dokument beskriver. Alt andet må gerne være appspecifikt.

Vejledningsbygger og PWA · Bygger afviger bevidst på enkelte punkter, fordi de ikke laver indhold på samme måde som de andre. Afvigelserne skal stå i appens egen beskrivelse.

### Grundprincipper

1. **Én HTML-fil.** Builderen kører lokalt i browseren uden server. Outputfilen indeholder alt (billeder og lyd som base64) og kan åbnes uden internet.
2. **Målgruppen er ældre og ikke nødvendigvis teknisk øvede.** Stor skrift, høj kontrast, tydelige knapper.
3. **Filerne skal kunne læses om 20 år.** Ren HTML, ingen eksterne afhængigheder, metadata efter Dublin Core.

### 1.1 Bevaringsstrategi og backup (3-2-1-reglen)

Selv den mest holdbare HTML-fil med komplet Dublin Core-metadata har ingen værdi, hvis filen forsvinder fysisk. Alle guider, workshops og brugerflader skal fremhæve 3-2-1-princippet:

- **3 kopier:** den originale fil plus mindst 2 kopier.
- **2 medietyper:** mindst to forskellige lagringsmedier (fx computerens harddisk og et USB-stik).
- **1 off-site:** mindst én kopi opbevaret et andet sted (fx hos et familiemedlem eller i et foreningsarkiv).

Da alt ligger i én offline HTML-fil, er det let for uøvede brugere at følge reglen uden at holde styr på løse bilag.

---

## 2. Layout i builderen

```
┌──────────────────────────────────────────────┐
│ Topbar: <Appnavn> · Bygger + kort forklaring │
├───────────────────────────┬──────────────────┤
│ Formular-rude (max 640px) │ Forhåndsvisning  │
│                           │ (380px, sticky)  │
│ felter …                  │ iframe med       │
│ Dublin Core (foldet ind)  │ live output      │
│ [ Generér … (.html) ]     │                  │
│ besked-boks               │                  │
└───────────────────────────┴──────────────────┘
```

Grid: `1fr 380px`, falder til én kolonne under 860 px.

Forhåndsvisningen opdateres ved hvert tastetryk (`renderPreview()`) og bygges med samme funktion som den endelige fil, ellers driver de fra hinanden. Tællerkoden udelades bevidst i forhåndsvisningen.

### Rækkefølge af felter

1. Indlæs eksisterende fil (valgfri), altid øverst
2. Tema
3. Appens egne indholdsfelter (mærkat, titel, sted, billede, lyd, tekst …)
4. Knap i bunden (valgfri CTA: knaptekst + URL)
5. Dublin Core-metadata, sammenfoldet `<details>`-panel
6. Generér-knap + beskedboks

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
```

Skrift: systemets sans-serif til brugerfladen, Georgia/serif kun til overskriften i topbaren.

### Skriftstørrelser i builderen, minimum

| Element | Størrelse |
|---|---|
| Feltlabel | 13,5 px, halvfed, versaler |
| Hjælpetekst under felt | 13,5 px, linjehøjde 1,55 |
| Inputfelt og textarea | 15,5 px |
| Dublin Core-feltlabel | 14 px |
| Dublin Core-hjælpetekst | 13 px |
| Filboks og status | 14,5 / 13,5 px |
| Generér-knap | 17 px, fed |
| Beskedboks | 14,5 px |

Kontrastkrav: al tekst mindst 4,5:1 mod sin baggrund. Lysegrå toner (#a39c88, #7c7563) bruges ikke til småtekst.

### Temaerne

Samme temaer i alle apps, samme hex-værdier, valgt med store farvede radioknapper med farveprikker.

| Tema | Accent | Papir | Kort | Korttekst |
|---|---|---|---|---|
| Oprindeligt | #9A4630 | #EDE7D8 | #1B1A16 | #F2ECDD |
| Røde Kors | #E30A0B | #FFFFFF | #E30A0B | #FFFFFF |
| Lokalhistorisk (bordeaux) | #9E2453 | #FAF9F5 | #9E2453 | #FAF9F5 |
| Roskilde TV | #B03035 | #FFFFFF | afklares | afklares |

**Roskilde TV:** hvid baggrund, næsten sort tekst, rød accent fra logoet. Farveprikken i temavælgeren er flerfarvet (logoets seks prikfarver). De øvrige tokens hentes fra `sidebygger-hs.html`, hvor temaet er implementeret, og skrives ind her ved næste revision.

Hvert tema definerer desuden: `cardBg, ink, moss, line, timesColor, footerColor, muted, footerStrong, bodyText, quoteText, codeBg`.

| Tema | muted | footerStrong |
|---|---|---|
| Oprindeligt | #6B6455 | #4A463C |
| Røde Kors | #5A5A5A | #333333 |
| Bordeaux | #6B5560 | #3A2630 |
| Roskilde TV | afklares | afklares |

Temaet skrives ud som CSS-variabler i `:root` i den genererede fil, aldrig som faste farver inde i reglerne.

---

## 4. Dublin Core

Denne del skal være fuldstændig identisk i alle apps: samme 15 felter, samme rækkefølge, samme danske labels, samme hjælpetekster.

| # | Felt | Label | Hjælpetekst | Standard |
|---|---|---|---|---|
| 1 | dc.title | Titel | udfyldes automatisk fra titlen | (auto) |
| 2 | dc.creator | Ophav / skaber | fortæller, optager eller den der har skabt materialet | |
| 3 | dc.subject | Emne / nøgleord | adskil med semikolon | |
| 4 | dc.description | Beskrivelse | udfyldes automatisk fra beskrivelsen | (auto) |
| 5 | dc.publisher | Udgiver | vælg fra listen (se 4.1) | tom |
| 6 | dc.contributor | Bidragyder | den der har interviewet, redigeret eller registreret | |
| 7 | dc.date | Dato | helst ÅÅÅÅ-MM-DD, men fx "ca. 1955" er også i orden | |
| 8 | dc.type | Type | DCMI-typeordforråd (dropdown) | appens egen |
| 9 | dc.format | Format | filens tekniske format | text/html |
| 10 | dc.identifier | Identifikator | arkivnummer, URL eller lignende | |
| 11 | dc.source | Kilde | hvor materialet stammer fra | |
| 12 | dc.language | Sprog | sprogkode, fx da | da |
| 13 | dc.relation | Relation | beslægtet materiale, fx et billedalbum | |
| 14 | dc.coverage | Dækning | sted og/eller periode, udfyldes automatisk fra Sted | (auto) |
| 15 | dc.rights | Rettigheder | ophavsret og vilkår for brug | |

Dropdown til dc.type: Sound, InteractiveResource, Text, Image, Collection, Event, (ingen). Hver app forvælger sin egen.

### Regler

- Alle felter er valgfrie. Tomme felter udelades helt af outputfilen.
- title, description og coverage spejler formularen automatisk, indtil man selv skriver i feltet (`dcTouched`-flag). description afstribes for Markdown og forkortes til 300 tegn.
- subject deles ved semikolon og skrives som ét `<meta>`-tag pr. nøgleord.
- Panelet er sammenfoldet som standard med underteksten: "Til arkivering. Alle felter er valgfrie — udfyld dem der giver mening."
- Knappen **Gem metadata som separat JSON-fil** giver `<slug>-metadata.json`:
  `{ "@context": "http://purl.org/dc/elements/1.1/", "dublinCore": { … }, "generated": "…Z" }`

I den genererede fil skrives metadata to steder:

1. I `<head>`: `<link rel="schema.DC" href="http://purl.org/dc/elements/1.1/">` efterfulgt af `<meta name="DC.xxx" content="…">` i feltrækkefølgen.
2. Nederst på siden: et udfoldeligt panel **Om denne fil** med dansk label, teknisk feltnavn i småt og værdien, skrevet som færdig HTML.

Sidens `<html lang="…">` sættes fra dc.language, ellers `da`.

### 4.1 Udgiver og område

Hver fil skal kunne henføres til præcis én udgiver, så en samling kan filtreres, pakkes og videregives uden manuel gennemgang.

**Bærende felt: dc.publisher.** Feltet bruges kun til dette og vises som en rulleliste, aldrig som frit tekstfelt. Standardvalg er tomt, så udfyldelsen er et bevidst valg. Listen ligger i builderen som ét `PUBLISHERS`-array øverst i scriptet, så en ny udgiver er en ændring på én linje.

Værdien er én af disse strenge, gengivet tegn for tegn, med **almindelig bindestreg**:

| Udgiver (værdi i dc.publisher) | Præfiks | Eksempel på id |
|---|---|---|
| Historisk Samfund for Roskilde Amt | HSR | HSR-side-0001 |
| Privat | PRIV | PRIV-album-0003 |
| Roskilde TV | RTV | RTV-video-0012 |
| Røde Kors | RKR | RKR-bog-0007 |
| Syd for Banen - Lokalhistorisk Forening | SFB | SFB-lyd-0142 |

Afvigende stavemåder (tankestreg, "Røde Kors Roskilde", "SFB", "privat" med lille p) skaber nye grupper i søgesiden og regnes som fejl.

**Ældre filer:** Når en builder indlæser en fil med en gammel værdi, rettes den automatisk til den gældende streng: "Røde Kors Roskilde" bliver til "Røde Kors", og tankestreg bliver til bindestreg. Så rettes ældre filer, efterhånden som de åbnes og gemmes igen.

**dc.identifier:** formatet er `PRÆFIKS-type-løbenummer` med fire cifre. Præfikset gentages i filnavnet, så udgiveren kan ses i en mappevisning. Enkelte apps kan bruge et navn i stedet for løbenummer (fx `SFB-side-astersvej-2026`).

**dc.subject:** udgiveren står som første emneord. En app med faste serier (fx Sidebyggerens "Gader og veje") må indsætte serien som andet emneord.

`Syd for Banen - Lokalhistorisk Forening; Gader og veje; Musicon; 1970erne`

**Afgrænsning mod dc.rights:** dc.rights beskriver, hvad andre må med filen, og udfyldes uafhængigt af udgiveren. Typiske værdier:

| Udgiver | Typisk dc.rights |
|---|---|
| Privat | Privat materiale. Må ikke videregives. |
| Syd for Banen | © Syd for Banen - Lokalhistorisk Forening. Må gengives med kildeangivelse. |
| Røde Kors | © Røde Kors. Må anvendes i foreningens formidling. |

**Adskillelse på disken:** arbejdsfilerne ligger i én mappe pr. udgiver på øverste niveau, og en distributionspakke bygges altid fra én mappe ad gangen.

**Søgesiden** læser dc.publisher og bygger et udgiverfilter ud fra de værdier, der faktisk findes i samlingen. Filer uden udgiver samles under **Uden område** og rettes ved kilden.

**Nye udgivere** tilføjes i tabellen ovenfor med fast streng og præfiks, før de tages i brug, og derefter i `PUBLISHERS` i alle byggere samt i prompten i afsnit 11.

---

## 5. Outputfilens kontrakt

Hver genereret fil indeholder en JSON-blok, der gør filen redigerbar igen:

```html
<script type="application/json" id="builder-source-data">
{ "format": "lydfortaelling-player",
  "builderVersion": 2,
  "title": "…", "description": "…", "theme": "rodekors",
  "dublinCore": { … } }
</script>
```

- `format` er appens eget navn (lydfortaelling-player, billedalbum, ebog, foer-og-nu …).
- `builderVersion` er et heltal, der tælles op ved formatændringer.
- Blokken indeholder den rå Markdown, ikke færdig HTML.
- Billeder og lyd hører ikke til i JSON-blokken. De læses tilbage fra dokumentet.

### Øvrigt indhold i `<head>`

- og:type, og:title, og:description altid. og:image + twitter:card kun hvis **Webadresse til delingsbillede** er udfyldt.
- `<link rel="icon">` sat til det indlejrede billede.

### Nederst i filen

Manuelt indsat tællerkode mellem markørerne `TÆLLER` og `TÆLLER SLUT` bevares uændret. Builderen skriver aldrig selv tællerkode.

### Filnavn

Slug af titlen: små bogstaver, æ→ae, ø→oe, å→aa, alt andet end a-z0-9 bliver til bindestreg.

### Gem-knap i den færdige fil

- Pilikon (19-20 px, accentfarve, stregtykkelse 2,4) + teksten **Gem filen** (16,5 px, fed).
- Pilleform med 2 px ramme i accentfarven, fyldes ved hover.
- Synlig tastaturfokus: 3 px outline.
- Under knappen en kort kursiveret linje om hvad filen er ("En lydfortælling" …), i footerStrong.
- **Nyt i 1.1:** Knappen gemmer en ren kopi. Mørk tilstand og valgt skriftstørrelse nulstilles, før siden gemmes, så filen altid åbner i standardudseendet.

### Valgfrie hjælpefunktioner (nyt i 1.1)

Til lange outputfiler må appen tilbyde, som i Vejledningsbyggeren:

- **Skriftstørrelse A / A+ / A++** i toppen, med typografi i `em` ud fra en `--fs`-variabel.
- **Op-pil** nederst til højre, der vises efter ca. en skærmhøjdes scroll.

Begge kan slås fra i builderen og vises ikke ved udskrift.

---

## 6. Markdown i tekstfelter

Alle apps understøtter den samme lille delmængde. Hverken mere eller mindre.

| Skrives | Bliver til |
|---|---|
| `**fed**` | fed |
| `*kursiv*` | kursiv |
| `## Overskrift` | `<h2>`, 21 px |
| `### Overskrift` | `<h3>`, 18 px |
| `- punkt` | punktliste |
| `1. punkt` | nummereret liste |
| `> citat` | citat med streg i accentfarven |
| `[tekst](https://…)` | link, åbner i nyt faneblad |
| `---` | vandret streg |
| `` `kode` `` | kode med baggrund |

Tekst escapes før oversættelsen. Sidens titel er `<h1>`, så et enkelt `#` giver også `<h2>`. Hjælpeteksten viser syntaksen med eksempler.

---

## 7. Indlæsning af eksisterende fil

- Feltet står øverst og hedder **Indlæs eksisterende … (valgfri)**.
- Filen parses med DOMParser, og `builder-source-data` læses. Mangler blokken, afvises filen med: "Filen kan ikke indlæses — den er ikke lavet med denne udgave af værktøjet."
- Billeder og lyd hentes tilbage fra data:-URL'erne.
- Tællerkoden følger med over.
- Udgiverværdier rettes til gældende streng (se 4.1).
- Status kvitterer med filnavnet og nævner, hvis tællerkoden blev fundet.

---

## 8. Billeder

Skaleres til maks. 1100 px på den længste led, JPEG kvalitet 0,78. Statuslinjen viser omtrentlig størrelse efter komprimering.

Undtagelse: Enkeltbillede · Bygger gemmer i høj opløsning (3000-4000 px eller originalen) og bevarer Exif, fordi det er appens formål.

Bemærk: canvas-komprimering fanger kun første billede i en animeret GIF.

---

## 9. Tilgængelighed

- Kontrast mindst 4,5:1 for al tekst, også i den færdige fil.
- Synligt fokus: 2 px outline i builderen, 3 px på gem-knappen.
- Klikflader mindst 44 × 44 px.
- Ingen information formidlet med farve alene.
- Layoutet virker ned til mobilbredde.
- Sproget er dansk, i hele sætninger, uden fagudtryk. En knap siger hvad der sker.

---

## 10. Tjekliste ved en app i serien

- [ ] Topbar med `<Appnavn> · Bygger` og en linje om hvad den gør
- [ ] To-rudet layout med live forhåndsvisning i iframe
- [ ] Alle temaer fra afsnit 3, samme hex-værdier, farveprikker
- [ ] Feltrækkefølgen fra afsnit 2
- [ ] Skriftstørrelser og kontrast fra afsnit 3
- [ ] Alle 15 Dublin Core-felter, i rækkefølge, med samme labels og hjælpetekster
- [ ] Automatisk spejling af title, description, coverage med dcTouched-flag
- [ ] Knap til JSON-eksport af metadata
- [ ] DC-meta i `<head>` og "Om denne fil"-panel i outputfilen
- [ ] `builder-source-data` med format og builderVersion
- [ ] Indlæsning af egne filer med pæn afvisning af fremmede filer
- [ ] Tællerkode bevares ved genindlæsning
- [ ] og:-tags og felt til delingsbillede
- [ ] "Gem filen"-knap, der gemmer en ren kopi
- [ ] Markdown-delmængden fra afsnit 6
- [ ] Filnavn som slug af titlen
- [ ] dc.publisher som rulleliste med de fem faste udgivere fra 4.1
- [ ] Gamle udgiverværdier rettes ved indlæsning
- [ ] dc.identifier har det præfiks, der svarer til udgiveren
- [ ] Udgiveren står som første emneord i dc.subject
- [ ] dc.rights beskriver anvendelse, ikke udgiver

---

## 11. AI-assisteret udfyldning af Dublin Core

### 11.1 Formål

Når flere foreninger og enkeltpersoner leverer materiale, skal metadata udfyldes ens. En AI (fx et Claude-projekt med prompten nedenfor som instruktion) læser teksten og foreslår de 15 felter. Et menneske godkender altid resultatet, før det sættes ind i en bygger.

### 11.2 Arbejdsgang

1. Bidragyderen udfylder INDLEDNING øverst i sin tekst.
2. Teksten indsættes i AI'en, som svarer med de 15 felter.
3. Felter markeret [?] kontrolleres.
4. Identifikator tildeles manuelt (løbenummer med præfiks fra 4.1).
5. Felterne kopieres ind i byggeren.

### 11.3 Indledning (udfyldes af bidragyder)

```
INDLEDNING
Navn (ophav):
Øvrige bidragydere:
Dato for materialet:
Udgiver:
Rettigheder:
Kilde (hvor stammer materialet fra):

TEKST
...
```

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
- Type: Sound, Text, Image, InteractiveResource, Collection eller Event.
- Format: text/html.
- Identifikator: lad altid stå tom.
- Sprog: da, medmindre teksten er på et andet sprog.
- Dækning: sted og/eller periode som teksten handler om.

FELTER
Titel (dc.title):
Ophav / skaber (dc.creator):
Emne / nøgleord (dc.subject):
Beskrivelse (dc.description):
Udgiver (dc.publisher):
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

Udgiverlisten i prompten skal altid svare til tabellen i 4.1 og byggernes `PUBLISHERS`. Kommer der en ny udgiver, rettes alle tre steder.

---

## 12. Ændringslog

**Version 1.1 — september 2026.**
- Fem faste udgivere med id-præfikser (HSR, PRIV, RTV, RKR, SFB). "Røde Kors Roskilde" er erstattet af "Røde Kors".
- Almindelig bindestreg i udgivernavne i stedet for tankestreg.
- Områdetillægget er indarbejdet i afsnit 4.1.
- Gamle udgiverværdier rettes automatisk ved indlæsning.
- Temaet Roskilde TV tilføjet.
- App-oversigten opdateret med Enkeltbillede, Før og Nu, Sidebygger, Videokort, Henvisning, Vejledning og PWA.
- "Gem filen" gemmer en ren kopi. Valgfri A/A+/A++ og op-pil.
- Undtagelse for billedstørrelse i Enkeltbillede · Bygger.
- Nyt afsnit 11 om AI-assisteret udfyldning af Dublin Core.
- Tjeklisten udvidet.

**Version 1.0 — august 2026.** Første udgave, skrevet ud fra Lydfortælling · Bygger version 2.

---

## 13. Åbne punkter til næste revision

- De fulde tokens for Roskilde TV-temaet hentes fra `sidebygger-hs.html`. Sidebyggeren har fem temaer. Hvis det femte er et selvstændigt tema (fx Historisk Samfund), skal det skrives ind i afsnit 3.
- dc.type for Videokort, Henvisning, Vejledning og PWA. Videokort er video, men dropdownen mangler MovingImage.
- Skal referenceimplementeringen fortsat være Lydfortælling · Bygger, eller skal det være Sidebyggeren?
- Skal alle outputfiler have en hjem-knap, og hvordan skal den se ud?
