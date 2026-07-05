# com_joomleague_v6 checklist - 2026-07-05

## Rozsah zkontrolovanych zmen

- [x] Porovnana zdrojova slozka `com_joomleague_v6` proti Git checkoutu.
- [x] Prevzaty frontend zmeny pro result matrix, schedule, team detail a person detail.
- [x] Prevzat novy sdileny vystup `components/com_joomleague/tmpl/results/matches_grouped.php`.
- [x] Prevzaty doplnene jazykove klice pro `cs-CZ`, `en-GB` a `de-DE`.
- [x] Prevzaty CSS styly pro seskupeny rozpis zapasu.
- [x] Zachovana opravena SQL update cesta pro verzi `0.40.4`.

## Technicke overeni

- [x] PHP lint pro zmenene modely, view a templaty.
- [x] Kontrola novych jazykovych klicu ve vsech trech frontend jazycich.
- [x] SQL test predikcnich FK na docasne databazi.
- [x] Verzni metadata sjednocena na `0.40.4`.
- [ ] Rucni kontrola v Joomla administraci po aktualizaci balicku.
- [ ] Rucni kontrola frontend stranek: result matrix, schedule, team detail, person detail.

## Funkcni oblasti k rucni kontrole

- [ ] Aktualizace z `0.40.1` nebo `0.40.3` na `0.40.4` probehne bez SQL chyby.
- [ ] Tipovaci soutez zustane po aktualizaci dostupna.
- [ ] Result matrix zobrazi odehrane, neodehrane, zrusene a kontumovane zapasy.
- [ ] Result matrix korektne rozdeli tymy podle divizi, pokud projekt divize pouziva.
- [ ] Rozpis zapasu prepina zobrazeni podle kola a podle data.
- [ ] Rozpis tymu filtruje vse / doma / venku.
- [ ] Detail tymu zobrazi logo a pouzije seskupeny vypis zapasu.
- [ ] Detail osoby zobrazi fotografii, kontaktni udaje a historii zapasu hrace.
- [ ] CSS nerozbije tabulky na mobilu ani desktopu.

## Poznamky

- `0.40.3.sql` v balicku `0.40.4` uz nepouziva `PREPARE`, protoze to v Joomla instalacnim toku selhalo.
- Cerstva instalace stale bere schema z `install.mysql.utf8.sql`; update skripty resi hlavne prechod existujicich instalaci.
