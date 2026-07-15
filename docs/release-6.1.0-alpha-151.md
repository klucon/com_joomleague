# JoomLeague 6.1.0-alpha-151 Release Notes

JoomLeague 6.1.0-alpha-151 is a Joomla 6.1 and PHP 8.3 alpha release focused on project-aware SEF routing, administrator form stability, frontend template polish, map support, prediction views, SQL tools and package metadata.

## Highlights

- Project-aware SEF URL generation now selects a better canonical menu item for project pages.
- Project context can be derived from project teams, matches and persons when a route does not include an explicit project parameter.
- The JoomLeague Navigation Menu module now links projects through `project_id`.
- Administrator menu and form selectors received additional project-scoped dependent loading.
- New frontend and administrator helpers reduce duplicated rendering logic in ranking, person, player statistics and map-related views.
- Leaflet assets are bundled for map rendering.
- SQL truncate tooling was added for administrator database maintenance workflows.
- Package, update and changelog metadata are aligned for the public `6.1.0-alpha-151` release.

## URLs, SEO and routing

- Added project-aware canonical menu item selection for generated SEF URLs.
- Added project detection for routes that start from a project team, match or person instead of an explicit project parameter.
- Added match alias lookup based on home team, away team and match date for readable match report routes.
- Improved SEF route generation for project, team, roster, rivals, team statistics, match report and person pages.
- Improved club detail routing so club pages can reuse the clubs menu item as their route base.
- Improved active menu parsing for empty route segments.
- Fixed generated project URLs that could use the wrong menu item when multiple project pages were available.
- Fixed project-scoped URLs that lost their project context when only a team, match or person identifier was present.
- Fixed match report route parsing for readable match aliases.

## Administrator

- Added administrator filter forms for prediction scores, prediction tips and tournament tree nodes.
- Added frontend template configuration forms for prediction, project heading, projects, race results and results-and-standings pages.
- Added the SQL truncate administrator tool with controller, model, view and template support.
- Added geocoding support for administrator forms, including a geocode field, helper and JavaScript button.
- Reworked several administrator list, form and template screens for Joomla 6.1 compatibility and more consistent filtering.
- Extended project-scoped dynamic menu item selectors so dependent values are loaded from the selected project.
- Updated administrator forms for clubs, matches, persons, positions, projects, rounds, statistics, templates, teams, team players, team staff, tournament trees and running-race records.
- Fixed administrator dependent selectors that could show global teams, clubs, matches or statistics instead of project-scoped values.

## Frontend

- Added map URL and map embed helpers for frontend and administrator views.
- Added shared frontend helpers for person names, player statistics and ranking columns.
- Added a shared ranking form layout for frontend ranking-related pages.
- Updated frontend templates for clubs, club detail, projects, project detail, results, results and standings, result matrix, schedule, standings, teams, team detail, roster, rivals, statistics, statistics ranking, events ranking, ranking curve, referees, match reports, next match, persons, playgrounds, predictions, tournament trees and race results.
- Updated dashboard and frontend CSS for the revised administrator and frontend layouts.
- Fixed map and stadium related rendering paths that previously lacked shared helper support.
- Fixed ranking-related frontend rendering by moving repeated column and form logic into shared helpers.

## Assets and data

- Added SVG placeholder assets for clubs, teams, projects, persons, stadiums, divisions, trophies, referees, players and team staff.
- Added bundled Leaflet assets for map rendering.
- Updated sport bootstrap JSON resources for football, basketball, handball, ice hockey and volleyball.

## Package and update metadata

- Updated package, component, module and plugin manifests to `6.1.0-alpha-151`.
- Consolidated historical SQL update files into the current alpha baseline marker.
- Updated release metadata generation so the Joomla update changelog rolls up the public alpha changes through `6.1.0-alpha-151`.

## Upgrade notes

- This is an alpha release for Joomla 6.1 and PHP 8.3.
- Existing route parameters remain supported where practical while generated links move toward cleaner project-aware SEF URLs.
- Sites upgrading from earlier alpha builds should test menu items, project pages, SQL tools, maps and prediction-related views on a staging copy before updating a live site.

## Release assets

- `pkg_joomleague-6.1.0-alpha-151.zip`
- `joomleague-update.xml`
- `joomleague-changelog.xml`
- `joomleague-changelog-6.1.0-alpha-151.xml`
