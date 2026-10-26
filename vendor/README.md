# Pinned Countries Build Dependencies

These source snapshots supply the Klucon Countries dependency and its connector
for the RC2 full package. They are build inputs, not code copied into JoomLeague's
runtime namespace. Each dependency retains its own manifest and version.

The Countries third-party notices and ISO-data licence files are included.
Original upstream working trees are maintained separately; do not treat the
snapshot as a second independent implementation.

The package builder prefers these snapshots, making a clean checkout buildable.
Explicit KLUCONCOUNTRIES_ROOT and KLUCONCOUNTRIES_CONNECTOR_ROOT paths can select
alternative reviewed sources. Existing sibling working trees are a fallback for
older checkouts without the snapshots. Release checks still validate all nested
manifests and English-only language payloads in the assembled ZIP.
