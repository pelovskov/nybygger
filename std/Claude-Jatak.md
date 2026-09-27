# you asked

message time: 2026-09-27 08:08:24

Ja tak

---

# claude response

Her er afsnittet, klar til at indsætte i Builder-standarden. Jeg har sat feltnavnet `dc.xxx` i parentes efter hver label. Så er output entydigt, også hvis en label afviger en smule mellem byggerne.

~~~markdown
## 11. AI-assisteret udfyldning af Dublin Core

### 11.1 Formål
Når flere foreninger og enkeltpersoner leverer materiale, skal
metadata udfyldes ens. En AI (fx et Claude-projekt med prompten
nedenfor som instruktion) læser teksten og foreslår de 15 felter.
Et menneske godkender altid resultatet, før det sættes ind i en bygger.

### 11.2 Arbejdsgang
1. Bidragyderen udfylder INDLEDNING øverst i sin tekst.
2. Teksten indsættes i AI'en, som svarer med de 15 felter.
3. Felter markeret [?] kontrolleres.
4. Identifikator tildeles manuelt (løbenummer med id-præfiks).
5. Felterne kopieres ind i byggeren.

### 11.3 Indledning (udfyldes af bidragyder)
```
INDLEDNING
Navn (ophav):
Øvrige bidragydere:
Dato for materialet:
Udgiver (forening):
Rettigheder:
Kilde (hvor stammer materialet fra):

TEKST
...
```

### 11.4 Faste regler
- Oplysninger fra INDLEDNING bruges som de står.
- Der gættes aldrig på navne, datoer eller rettigheder.
  Mangler de, står feltet tomt.
- Usikre forslag markeres med [?].
- Dato skrives ÅÅÅÅ-MM-DD, ÅÅÅÅ-MM eller ÅÅÅÅ.
- Udgiver er altid én af de faste strenge (se 11.6),
  med almindelig bindestreg.
- Emne: udgiveren som første emneord, derefter 3-6 emneord
  adskilt af semikolon.
- Beskrivelse: 2-4 saglige sætninger uden vurderinger.
- Type bruger DCMI-værdierne: Text, Sound, Image, MovingImage.
- Format for færdige filer: text/html.
- Identifikator står altid tom (tildeles manuelt).
- Sprog: da, medmindre materialet er på et andet sprog.

### 11.5 Prompt
```
Du udfylder Dublin Core-metadata ud fra en tekst med en INDLEDNING.
Svar KUN med de 15 felter nedenfor, i denne rækkefølge, én linje
pr. felt. Ingen indledning, ingen kommentarer, ingen forklaringer.

REGLER
- Brug oplysninger fra INDLEDNING præcis som de står.
- Gæt aldrig på navne, datoer eller rettigheder. Mangler de,
  lad feltet stå tomt.
- Er du i tvivl om et forslag, skriv [?] efter det.
- Dato: ÅÅÅÅ-MM-DD, ÅÅÅÅ-MM eller ÅÅÅÅ.
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
- Type: Text, Sound, Image eller MovingImage.
- Format: text/html.
- Identifikator: lad altid stå tom.
- Sprog: da, medmindre teksten er på et andet sprog.
- Dækning: sted og/eller periode som teksten handler om.

FELTER
Titel (dc.title):
Ophav (dc.creator):
Emne (dc.subject):
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

### 11.6 Vedligeholdelse
Udgiverlisten i 11.5 skal altid svare til udgiverlisten i
byggernes dropdown. Kommer der en ny udgiver, rettes begge steder.
~~~

To ting skal du lige tage stilling til. Hvis dine labels i byggerne er anderledes (fx "Ophavsperson" i stedet for "Ophav"), retter du dem både i 11.5 og i felt-listen. Nummeret 11 har jeg valgt, fordi tjeklisten er afsnit 10. Passer det ikke i dokumentet, retter du bare nummereringen.