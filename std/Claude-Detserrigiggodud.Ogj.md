# you asked

message time: 2026-09-27 08:21:37

Det ser rigig god ud. Og ja - Builder Standarden. Den er blevet opdateret af flere omgange. Jeg har arbejdet på flere forskellige computere undervejs. Kan du ikke finde den nyeste, som jeg så må sørge for at gemme et sted hvor jeg kan finde den igen. Byggerne er blevet ændret i flere omgange og nye er kommet til. Jeg håber du kan finde den vi så skal arbejde med fremadrettet.

---

# claude response

Jeg har gennemsøgt dine tidligere samtaler, og konklusionen er lidt overraskende: Der findes kun én skrevet udgave af selve dokumentet, nemlig **version 1.0 fra 5. august**. Den blev lavet i samtalen [Builders spec documentation](https://claude.ai/chat/615f51f0-d840-4d68-b791-f9e1b40a7312) sammen med Lydfortælling · Bygger v2.

Siden da er der truffet en række beslutninger, men de er aldrig blevet skrevet ind i dokumentet. De ligger spredt i de enkelte bygger-samtaler:

- **Udgiver som områdefelt** (august): `dc.publisher` er det bærende felt, der skrives med almindelig bindestreg, og udgiveren er første emneord.
- **Fem udgivere med id-præfiks** (26. september, i sidebyggeren): Historisk Samfund for Roskilde Amt (HSR), Privat (PRIV), Roskilde TV (RTV), Røde Kors (RKR) og Syd for Banen - Lokalhistorisk Forening (SFB).
- **Fem farvetemaer** i stedet for tre: Røde Kors og Roskilde TV er kommet til.
- **Referencefilen** er flyttet fra `lyd-fort-gen-dc-v2` til v3, og i den seneste samtale blev sidebyggeren foreslået som ny reference.
- **Nye byggere** er kommet til, som standarden ikke nævner: Før og Nu, Videokort, Henvisning, Vejledning, PWA og Sidebygger.
- **Konkrete løsninger** fra enkelte byggere, som kunne gøres fælles: A/A+/A++ og op-pil fra Vejledningsbyggeren, og "Gem filen", der nulstiller mørk tilstand.

I samme samtale den 26. september blev vi faktisk enige om, at første skridt i ensretningen skulle være en **version 1.1**, hvor alt dette låses fast. Den er bare aldrig blevet skrevet.

To forbehold. Samtaler inde i dine Claude-projekter (fx "langtidsholdbare medieprojekter") kan jeg ikke søge i herfra. Og du kan have rettet i filen selv på en af computerne. Derfor er det sikreste at finde den `builder-standard.md`, du har liggende, og uploade den her. Så samler jeg den med beslutningerne ovenfor og det nye afsnit 11 til én samlet version 1.1.

Et godt fast sted til den fremover er dit GitHub-repo ved siden af byggerne. Så er der én officiel udgave, uanset hvilken computer du sidder ved.

Der er én ting, jeg har brug for, at du afgør inden: Skal Røde Kors hedde "Røde Kors" eller "Røde Kors Roskilde" i udgiverfeltet? Ældre filer bruger den lange form, og det påvirker filtreringen i søgesiden.