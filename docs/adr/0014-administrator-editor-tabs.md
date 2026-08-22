# ADR 0014: Administrator editor tabs

Date: 2026-08-08

## Status

Accepted

## Context

Administrator edit forms had inconsistent layouts. Some editors used Joomla tabs while others displayed unrelated details, publishing controls and rich-text editors together on one long page. This made forms harder to scan and encouraged future fields to be added without a clear ownership group.

## Decision

All entity editors with more than one thematic fieldset use Joomla `uitab` components.

- Details, history, scheduling, presentation and publishing are separate tabs when present.
- Description is a dedicated tab rather than part of Details or Presentation.
- Inside the Description tab, its label occupies one row and the editor is rendered below it at the full available width.
- Publishing is always a separate tab.
- Tabs use Joomla's native responsive behavior and remember the last selected tab.
- No custom CSS is introduced for editor navigation or layout.

The current tab contracts are:

- Club: Details, Media, Club history, Description, Publishing.
- Competition: Details, Description, Publishing.
- Season: Details, Description, Publishing.
- Project: Project, Schedule, Presentation, Description, Publishing.
- Sport Type: Details, Publishing.
- Team: Details, Media, Description, Publishing.
- Person: Details, Biography, Media, Description, Publishing.
- Venue: Details, Location, Facilities, Media, Description, Publishing.

## Consequences

New editor fields must be assigned to an existing thematic fieldset or a deliberately introduced tab. One-page editor layouts are not accepted for entities with multiple fieldsets. Architecture and Playwright tests enforce the current contracts.

## Verification

The editor layouts are tested at desktop and mobile sizes on Joomla 6.2 with MariaDB and PostgreSQL. The browser test checks the number of top-level Joomla tab elements and ensures exactly one starts active.