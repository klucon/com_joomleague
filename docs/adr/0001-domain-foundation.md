# ADR 0001: Domain foundation for JoomLeague 6.2

- Status: Accepted as implementation direction
- Date: 2026-08-02
- Target: JoomLeague 6.2.0
- Planned Joomla 6.2.0 release: 2026-10-13 (tentative)

## Context

JoomLeague 6 must preserve all meaningful historical data and migration capability while becoming a genuinely sport-independent system. It does not have to preserve the JoomLeague 3 database schema. Two development lines currently contain different parts of that target:

- `com_joomleague_v6` contains the complete product ecosystem, legacy compatibility, frontend, modules, plugins, races, predictions and templates.
- `com_kluconsports` contains the stronger sport-profile model, profile validation, project-profile binding, membership history and a more accurately reconstructed administration workflow.
- JoomLeague 3 remains the compatibility baseline for existing installations and historical migration scripts.

The current JoomLeague V6 profile bootstrap is lossy. It reads the bundled KSM-compatible JSON files but discards most match, scoring, standings, lineup, statistics and migration semantics when creating legacy records.

## Decision

`com_joomleague_v6` is the only target product. KSM becomes a read-only reference and source of reviewed concepts. The JoomLeague 6.2 architecture will have four explicit layers.

### 1. New canonical persistence layer

JoomLeague 6.2 receives a new canonical schema designed from first principles around universal sport profiles. Historical JoomLeague schemas are input formats, not the target architecture.

- The target schema may rename, normalize, split or replace every historical table.
- Runtime code does not depend on a JoomLeague 0.93, 1.5, 2.5 or 3 table layout.
- Historical source IDs are preserved as migration identities, not necessarily as target primary keys.
- Fresh installations and migrations from every supported source converge to the same canonical model.
- Every source row is accounted for by a target entity, an archived source payload, or an explicit blocking migration issue.
- No source value may disappear silently.

The migration contract preserves data and meaning rather than table names, column names, URLs or internal class APIs.

### 2. Versioned sport-profile layer

A sport profile is an immutable, versioned rules contract, not only a data seeder.

It defines at least:

- contest and project capabilities,
- match structure and score model,
- result and forfeit rules,
- standings and points rules,
- lineup capabilities,
- positions and person types,
- events and their score/statistic effects,
- statistics and their sources/value types,
- template defaults,
- migration aliases.

Every bundled profile has a schema version, profile code and profile version. Installed profile JSON is persisted without lossy conversion. Validation happens before any database write.

### 3. Configurable sport type and project layer

The concepts remain distinct:

- **Sport profile:** bundled or imported versioned defaults and capabilities.
- **Sport type:** local administrator-managed sport definition derived from a profile.
- **Project:** a concrete competition bound to a profile version, with allowed overrides.

The effective rules are resolved in this order:

1. profile defaults,
2. sport-type overrides,
3. project overrides.

Overrides are allowed only for fields declared overridable by the profile schema. Existing projects do not silently change when a newer bundled profile is installed.

### 4. Application layer

Administration, frontend views, templates, modules, plugins, imports and exports consume one resolved project-rules API. They must not branch on hardcoded sport codes such as `football` or `basketball` when the profile can express the behavior.

The planned central service is conceptually named `SportRulesResolver`. Its stable output drives:

- project forms and defaults,
- match score editors,
- lineup and substitution screens,
- event and statistic availability,
- standings calculations,
- frontend rendering capabilities,
- import validation and migration mapping.

## Database principles

The final migration design must satisfy these invariants:

1. JoomLeague 0.93, 1.5, 2.5 and 3 databases can be migrated without silent data loss.
2. An existing JoomLeague V6 alpha database can be migrated to the canonical schema.
3. A clean installation and migrations from all supported versions produce equivalent canonical schemas.
4. Profile installation is idempotent.
5. A project references an installed profile version explicitly.
6. Membership and availability support repeated time intervals.
7. Historical source identity and payload provenance remain traceable after migration.
8. Every source row receives a deterministic migration outcome.
9. Migration can be resumed and repeated without creating duplicates.
10. No bundled profile update rewrites project-specific historical rules implicitly.

The expected canonical entities include:

- sport profile and profile version metadata,
- profile-to-sport-type linkage,
- project-to-profile-version linkage,
- player/staff membership intervals,
- player/staff availability intervals,
- migration batch and source mapping where required for repeatable imports.

Exact entities, columns, constraints and transformation rules require a separate reviewed database design before implementation. The design is not constrained to additive changes against JL3.

## Profile-schema policy

The current unversioned JSON format is transitional. A public schema is accepted only after passing representative stress tests:

- football: timed team score with draws,
- basketball or ice hockey: no final draw and overtime,
- volleyball: nested set scoring and win-by-margin,
- tennis and darts: different nested score depths,
- running race: individual lower-is-better time result,
- motorsport: classification and points by position.

Schema evolution rules:

- schema versions are explicit,
- profile versions follow semantic versioning,
- validators are schema-version aware,
- migrations between schema versions are deterministic,
- unknown required schema versions fail before writes,
- extension fields use documented namespaces or open profile blocks rather than hardcoded sport branches.

## Compatibility policy

JoomLeague 6.2 guarantees migration compatibility, not historical schema compatibility.

- Existing data and its domain meaning are mandatory compatibility surfaces.
- JoomLeague 0.93, 1.5, 2.5, 3 and supported V6 alpha schemas are versioned migration adapters.
- Public frontend routes remain compatible where practical.
- Obsolete internal controllers and legacy parameter-array URL syntax may gain canonical replacements.
- Compatibility adapters must have documented removal conditions.
- KSM routes, namespaces and table names are not public compatibility targets.

## UI policy

- Joomla core administration styles and controls are the default.
- Custom CSS is introduced only for behavior that Joomla cannot provide and after explicit design approval.
- Entity workflows reconstructed and verified in KSM are candidates for selective porting.
- Forms are merged field by field; complete KSM XML files are not copied blindly over newer JL functionality.
- Sport-specific controls are generated from resolved capabilities where feasible.

## Security and permissions

- Profile and SQL imports are privileged, CSRF-protected and validated before writes.
- Fine-grained project workspace ACL will be introduced without removing Joomla core ACL semantics.
- Dynamic table names, ordering and import mappings use allowlists.
- Values use Joomla Query API bindings wherever possible.
- Upload and archive handling receives dedicated tests before release.

## Delivery gates

### Gate 1: Foundation

- accepted database extension design,
- accepted profile schema,
- validator tests,
- fresh/upgrade schema equivalence test,
- no production deployment.

### Gate 2: Profile runtime

- lossless profile persistence,
- project-profile binding,
- rules resolver,
- idempotent bootstrap of all profiles,
- representative sport tests.

### Gate 3: Administration consolidation

- canonical field matrix,
- project workspace and assignment workflows,
- membership/availability history,
- fine-grained ACL,
- Joomla-style visual regression review.

### Gate 4: Ecosystem integration

- frontend and template capability integration,
- module/plugin compatibility,
- import/export compatibility,
- JL 1.5/JL 3 migration fixtures.

### Gate 5: Release readiness

- Joomla 6.2 compatibility test,
- clean install and multi-hop upgrade test,
- package install/uninstall test,
- language completeness checks,
- security review,
- release candidate deployed to a non-production demo first.

## Consequences

### Positive

- Existing JoomLeague data remains the solid foundation.
- Sport behavior becomes declarative and testable.
- KSM work is reused without replacing the complete JL ecosystem.
- Future sports can be added without duplicating controllers and standings engines.
- Projects retain historically correct rules after profile updates.

### Negative

- The 6.2 work is a domain migration, not a quick UI port.
- Legacy and normalized data must coexist temporarily.
- A validator, resolver and migration test harness are prerequisites before visible feature work.
- Some current alpha behavior will need compatibility adapters.

## Rejected alternatives

### Replace JL V6 with KSM

Rejected because KSM lacks the complete frontend, modules, plugins and several JL V6 subsystems.

### Keep profiles as one-time seed files

Rejected because match and standings behavior would remain hardcoded and most profile data would stay unused.

### Keep JL3 tables as the canonical runtime schema

Rejected because historical storage decisions would constrain universal profiles and future sports. JL3 remains a fully supported migration source, not the runtime architecture.

### Copy all KSM forms and templates into JL V6

Rejected because it would overwrite newer JL V6 fields and preserve two inconsistent domain models.

## Follow-up decisions required

1. Exact canonical database schema and migration order.
2. Final profile schema and override policy.
3. Effective-rules representation returned by the resolver.
4. Transitional behavior for legacy project settings.
5. Supported database engines for JoomLeague 6.2.
6. Minimum supported Joomla and PHP versions.
