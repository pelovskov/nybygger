# you asked

message time: 2026-09-30 17:55:52

Rigtig godt. Hver af de fire foreninger har deres egen hjemmeside. Vil det være muligt at lave en stump html kode, som kan sættes ind. f.eks. på en side i Wordpress (tilpasset html) som viser en søgeformular man kan skrive i og så søgningen sendes videre til faelles.php - altså tilpasset til en enkelte forening.

---

# claude response

Ja, det kan sagtens lade sig gøre, og det kræver ingen ændringer på serveren. Søgeboksen er en almindelig formular, der sender søgningen videre til søgesiden, fx `…/index.php?q=tummelumsen&forening=rtv`. Det er i øvrigt `index.php` og ikke `faelles.php`, man sendes til. `faelles.php` er den fil, der bygger siden bag kulisserne.

Jeg har lavet søgeboksen til Roskilde TV og afprøvet den. Den sender præcis den adresse, søgesiden forventer.

**Sådan tilpasser du den til hver forening**

Der er tre steder at rette:

- **Nøglen** i linjen med afkrydsningsfeltet: `value="rtv"`. De fire nøgler er `sfb`, `rtv`, `hsra` og `rkr`, de samme som i `foreninger.json`.
- **Teksten** ved afkrydsningsfeltet, fx "Søg kun i Røde Kors Roskildes materiale".
- **Id-navnet** `lsp-q-rtv`, som står to steder (i `for=` og `id=`). Skift `rtv` til foreningens nøgle. Det betyder kun noget, hvis der står to søgebokse på samme side, men det er en god vane.

Overskriften og den lille forklaring kan hver forening skrive, som de vil. Foreningens egen farve sættes øverst i `--lsp-farve:#8E1F49`. Boksen arver ellers skrifttypen fra hjemmesiden, så den falder naturligt ind.

**Afkrydsningsfeltet** er sat til "kun vores materiale" fra start. Fjerner man fluebenet, søger man i hele portalen. Det viser samtidig besøgende, at der findes mere end foreningens eget.

**Tjek adressen:** Jeg har skrevet `https://sfbbilleder.eu/over/sbtv/index.php` ud fra mappen i FileZilla. Åbn den i browseren og se, at søgesiden kommer frem. Den dag forsiden bliver `index.php` og søgesiden fx `soeg.php`, skal adressen i boksene rettes tilsvarende.

**I WordPress** bruger du blokken "Tilpasset HTML" og indsætter hele stumpen. Undgå tomme linjer i koden, for i den klassiske editor kan WordPress finde på at lave dem om til afsnit.

**Pas på i et WordPress Multisite**, fx Røde Kors' net af lokalafdelinger. Dér må almindelige administratorer normalt ikke indsætte formularer og `<style>`. WordPress fjerner dem stille og roligt, når siden gemmes, og kun netværksadministratoren har lov. Forsvinder boksen, er den nemme udvej et almindeligt link eller en knap til `…/index.php?forening=rkr`. Det virker overalt og fører direkte til foreningens materiale i portalen.

Søgningen åbner i samme vindue, så browserens tilbage-knap fører tilbage til foreningens hjemmeside. Vil man hellere åbne portalen i en ny fane, tilføjer man `target="_blank"` i `<form …>`-linjen.