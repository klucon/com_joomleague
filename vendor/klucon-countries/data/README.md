# Czech Data Foundation

`cz.json` contains seven separately classified continents (Europe first), one
country, 14 Czech regions, 76 current districts, the special Prague territory
and the historical Prague district with its end date. Other countries are not seeded.

Official Czech names and hierarchy come from CUZK RUIAN code lists UI_VUSC and
UI_OKRES, retrieved on 2026-10-18. Original Windows-1250 CSV files are preserved
under `sources/cz`; their SHA-256 hashes and source URLs are embedded in the seed.
Source Czech names remain in the immutable provenance record. Display labels
in the base seed are English; Czech display names are language-pack constants.
Proper place names retain their spelling, with translated administrative labels
such as "District". Region terminology follows Czech Statistical Office sources.

`PLATI_OD` is the source record update date, not its foundation date.
`DATUM_VZNIKU` supplies `valid_from`; `PLATI_DO` supplies `valid_to`.
RUIAN district 3100 ended on 2022-07-01. Record 9999 is a special capital territory,
not an additional current district. Identifiers from the source are namespaced,
not claimed to be current ISO subdivision codes.

UUIDv5 identities use the record's stable namespaced source code, not its name.
Continents are classification references, never administrative parents.
The JSON is an installer seed, not an instruction to overwrite user edits.

Regenerate and validate:

```sh
php tools/build-czech-seed.php
php tools/validate-czech-seed.php
```

The installer seeds missing UUIDs and preserves existing customisations. Source
identities remain immutable; effective hierarchy, English names, identifiers and
flag choices can be edited with version checks and revision history.
