# Profile-driven match result editor

## Scope

The first writable administrator result editor is generated from the immutable sport profile assigned to the project. It does not branch on a sport code and supports all four result contracts: `numeric_score`, `nested_score`, `time_result` and `decision_result`.

## Implementation

- `MatchResultEditorContext` loads the match, project profile, published participants and stored result.
- `MatchResultFormStateBuilder` creates the implicit root and the profile-defined segment tree. `expected_count` creates the declared number of slots even for conditional groups.
- Existing data is merged with the profile template, so unused optional segments remain available after reload without discarding stored repeatable segments.
- `MatchResultFormPayloadBuilder` converts the nested Joomla form payload to the canonical repository payload. Empty optional segments are omitted and client-provided `required` flags are not trusted.
- `MatchresultController` provides Apply, Save and Cancel with Joomla CSRF and ACL checks.
- The editor uses Joomla tabs, form controls, switches and tables. It contains no custom CSS or JavaScript.
- A stored `finalized_at` value survives Apply. A newly finalized result receives the current UTC timestamp.
- Technical exceptions are logged under `com_joomleague`; the UI displays en-GB language constants rather than service or database messages.

## Final-result guarantees

A final result requires:

- an outcome allowed by the assigned profile;
- at least one published match participant;
- one root value, rank or participant-result status for every published participant;
- every unconditional `expected_count` segment group;
- the complete expected count when a conditional segment group is present.

The repository remains the authoritative validation and transaction boundary. Hidden form values cannot bypass the profile result type, segment graph, participant set, status vocabularies or count limits.

## Verification

- Form-state and form-payload round trip for football, including optional extra time and shootout slots.
- Form-state construction for all 15 bundled profiles.
- Editor-schema coverage: 8 numeric, 4 nested, 2 time and 1 decision profile.
- Result-contract stress payloads for football, tennis, running race and MMA.
- Architecture checks require CSRF, edit ACL, error logging, no custom editor CSS/JavaScript and en-GB labels for every profile-defined status/outcome code.
- Repository integration runs on both MariaDB and PostgreSQL and cleans up only its named fixture.

## Development deployment

## Deferred second iteration

- Runtime evaluation for `condition_code` from calculated score state.
- Structured decision controls for judges and rounds instead of a generic text value.
- Profile aggregation `derive` mode and field-level translated validation messages.

## Repeatable segment controls

The second implementation pass adds server-backed Add/Remove controls for profile segments that are repeatable and do not declare a fixed `expected_count`.

- No custom JavaScript is required. The clicked Joomla button posts the complete form and uses a PRG redirect back to the editor.
- A canonical locator such as `result:1/set:2/game:1` identifies the parent or selected segment.
- `MatchResultFormStateMutator` accepts only locators and segment codes allowed by the assigned profile, rejects fixed-count segments and enforces `maximum_count`.
- Submitted form data is normalized and validated as a draft before mutation. A client cannot inject an unknown segment, invalid hierarchy, participant or vocabulary code.
- The transient form is stored per user and match through Joomla user state. Apply/Save persists it and clears the transient state; Cancel discards only that transient state.
- Failed saves and failed structure changes preserve all valid submitted fields instead of returning to stale database values.
- Unit coverage includes nested tennis set/game/point mutation, fixed football periods and the five-round MMA maximum.

## Conditional segment groups

- Every distinct profile `condition_code` is rendered once in the Joomla "Match phases" section.
- Enabling extra time includes its complete profile-defined `expected_count` group; football therefore persists both extra-time halves atomically.
- Disabling a condition removes the complete group from the submitted payload even when stale values remain in its visible inputs.
- Active condition switches are derived from stored segments after reload and are not persisted as parallel metadata.
- Condition labels are en-GB language constants verified against every bundled profile.

## Latest verification

- Deployment backup: `/mnt/disk-b/server-backups/joomla62/20260811-172159`
- Repeatable controls and conditional groups are installed on both Joomla62 development instances.
- Repeated suite installation, Database Checker, stack verification and repository/context/mutation integration all pass on MariaDB and PostgreSQL.

## Duration values

- `time_result` editors accept `SS.mmm`, `MM:SS.mmm` and `H:MM:SS.mmm` instead of raw milliseconds.
- `MatchResultDuration` performs strict integer conversion without floating-point arithmetic.
- Canonical repository and database values remain numeric milliseconds, preserving the public result contract.
- Stored millisecond values are formatted back to the same editable representation after reload.
- Invalid, negative, incomplete and overflowing duration strings are rejected before repository persistence.

## Score aggregation

- Final results using `aggregation.mode = validate` must equal the sum of the profile-declared root child segment types in `aggregation.from`.
- Every aggregated participant requires numeric values at the root and in every included source segment.
- Decimal aggregation uses string arithmetic at the database precision; chess half-points do not pass through PHP floating-point values.
- Draft results remain partially editable and are not blocked by final aggregation rules.
- `aggregation.mode = derive` creates missing or replaces submitted root numeric values from the declared source segments while preserving participant status, rank and metadata.

## Match-list summary

- The round match list includes a dedicated Result column linked to the result editor.
- Result lifecycle is displayed with Joomla/Bootstrap badges and translated profile vocabulary.
- `MatchResultSummaryProvider` loads root score values for the current page in one batched query ordered by participant slot; no per-match query is executed.
- Numeric, duration, structured text, participant status and rank-only roots all have profile-neutral fallbacks.
- The provider is exercised with a stored nested tennis result on both MariaDB and PostgreSQL.

## Verification update

- Deployment backup before the match-list release: `/mnt/disk-b/server-backups/joomla62/20260811-173325`.
- Repeated suite installation, Database Checker and stack verification passed on both development database drivers.
- Repository, editor context, repeatable mutation and batched list summary integration passed on both drivers.

## Translated validation feedback

- Expected administrator-correctable errors use `MatchResultValidationException` with an en-GB language key.
- The controller translates only this typed exception. Unexpected service, database and programming exceptions remain in the Joomla log and expose only the generic save message.
- Specific feedback covers final outcome and participants, root values, segment counts, profile aggregation, numeric values, ranks and duration syntax.
- Submitted valid fields remain in Joomla user state after validation failure.

## Structured decision controls

- Profiles may select `number`, `duration`, `text`, `status_rank` or `none` controls independently for the root and each segment type.
- Control/value compatibility is validated by `SportProfileSchemaValidator`; conventional controls remain derived when the declaration is omitted.
- The MMA editor records winner/loser/draw/no-contest state and rank at the result root, treats each judge as a repeatable structural container and records decimal cards per round.
- No runtime branch refers to `mma_boxing` or any other sport code.
