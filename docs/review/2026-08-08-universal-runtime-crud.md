# Universal runtime catalog CRUD

Date: 2026-08-08

Positions, Event Types and Statistics now expose standard Joomla New, Edit, Publish, Unpublish, Check-in and Delete actions plus two-tab editors. Every definition belongs to a Sport Type. Stable codes are unique within that Sport Type.

Sport semantics remain data-driven. Person types, lineup groups, score targets, statistic types, scopes, value types and calculation sources are extensible lowercase codes rather than closed PHP enums. Editing a profile-derived runtime row changes it to `source=local`; immutable profile JSON is never modified.

Position parent relationships are restricted to the same Sport Type, reject cycles and cannot be deleted while assigned to project members. Event and statistic editors preserve source metadata not exposed by their basic forms.