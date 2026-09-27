# you asked

message time: 2026-09-27 08:48:16

Ja tak - med sidebyggeren rettet og den opdaterede standard, kan jeg starte stille og roligt med en builder af gangen.

---

# claude response

Sidebyggeren er nu version 1.3, og den stemmer helt overens med standarden. Begge filer ligger nedenfor.

**Rettelserne i Sidebyggeren:**

- **Typelisten** har nu syv typer i standardens rækkefølge: Text, Image, Sound, MovingImage, InteractiveResource, Collection og Event.
- **Gamle udgivernavne** rettes automatisk, når en fil indlæses: "Røde Kors Roskilde" bliver til "Røde Kors", og tankestreg bliver til bindestreg. Statuslinjen fortæller, når det er sket, fx *udgiveren "Røde Kors Roskilde" er rettet til "Røde Kors"*. Emneordet og identifikatoren følger med, så de også bliver rigtige (`RKR-side-…`), når du gemmer.
- Står der et helt ukendt udgivernavn i en fil, går det ikke tabt. Det vises som et ekstra valg markeret "ikke på listen", så du kan se det og vælge det rigtige.

Jeg har testet indlæsningen med en fil med det gamle Røde Kors-navn, og den bliver rettet som den skal. Resten af Sidebyggeren er ikke rørt.

I standarden er referencen opdateret til version 1.3, og listen over rettelser i referencen er fjernet, da den nu er tom. Tilbage under åbne punkter står kun det, der hører til de enkelte byggere.

**Når du starter på næste bygger** i en ny samtale, så upload tre filer: `builder-standard.md`, `sidebygger-hs.html` som reference, og den bygger du vil have rettet. Skriv så: *"Bring denne bygger i overensstemmelse med Builder-standard 1.1. Brug tjeklisten i afsnit 10 og Sidebyggeren som reference."*

God fornøjelse med det. I dit tempo bliver det både grundigt og overskueligt.