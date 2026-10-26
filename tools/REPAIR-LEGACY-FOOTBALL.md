# Repair of legacy football extra-time/shootout imports

RC2 now runs a one-time-per-result repair during a standard component update.
It restores verified original extra-time/shootout scores, including scores edited
after import. Notes are retained. Each repaired result receives a marker, so later
updates do not overwrite it again. The installer reports skipped ambiguous cases
and logs match IDs/reasons to the configured Joomla log directory. An active
component administrator is required when affected imported matches exist.
No global completion marker hides skipped matches from a later retry.

Back up and verify the complete installation before updating. Referenced score
segments, changed participants, unsupported profiles and ambiguous original scores
remain protected: the installer does not guess missing data or detach events.

The separate manual CLI below retains its conservative no-overwrite behaviour.
It is not a public endpoint. Do not run it on a production installation without
approval and a verified restorable backup.

Prerequisites:
- Install the tested candidate containing LegacyFootballResultRepair.
- Copy repair-legacy-football-result.php outside the public webroot.
- Verify an independent database backup from the same installation and point
  in time. The CLI verifies file existence, non-public location and SHA-256;
  it cannot itself prove that a dump belongs to the correct database or is
  restorable. That verification remains mandatory operational work.
- Use `umask 077`; previews contain private original match metadata.

Preview one explicitly selected match (no result writes):

```sh
php /private/repair-legacy-football-result.php --root=/var/www/html \
  --actor=ADMIN_USER_ID --match=MATCH_ID > /private/match-preview.json
```

Review status and proposed payload. Only `candidate` is applicable. A conflict
must be investigated manually, not bypassed. Source match/result metadata must
agree, current scores must still equal the original regulation scores, there
must be no signs of subsequent editing or existing detailed breakdowns, and
no event/statistic may reference the score segments that would be replaced.

Apply the exact reviewed revision:

```sh
php /private/repair-legacy-football-result.php --root=/var/www/html \
  --actor=ADMIN_USER_ID --match=MATCH_ID --apply \
  --revision=REVIEWED_REVISION_SHA256 \
  --backup=/private/verified-database.dump --backup-sha256=BACKUP_SHA256
```

The native result repository locks the project/match, rechecks the full preview
revision and validates against the current pinned/project/stage rules before
writing. It replaces result records through the normal RC2 service. Current
standings pointers are invalidated transactionally, then the normal standings
cascade runs. A failed cascade cannot leave an old table advertised as current.
Original source metadata is retained and the repaired result records the actor
and repair marker. Repeating the repair reports `already_repaired`.

Do NOT re-import the original migration ZIP afterward. Native result replacement
changes internal result/segment IDs. The importer currently rejects that replay
with an integrity error; isolated tests verify transaction rollback and
preservation of repaired results. A more user-friendly replay error is separate
follow-up work. Re-import is not this repair's rollback mechanism.

After applying, inspect the actual match report, programme, winner/progression,
and relevant standings. Back up the repaired state separately. For rollback,
restore the verified consistent pre-repair backup; do not reconstruct rows by
guessing old numeric IDs.

No production deployment or repair has been performed as part of development.
