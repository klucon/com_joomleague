<?php

declare(strict_types=1);

define('_JEXEC', 1);
require_once dirname(__DIR__, 2) . '/components/com_joomleague/src/Service/RouteAliasRegistry.php';

use Joomleague\Component\Joomleague\Site\Service\RouteAliasRegistry;

$en = new RouteAliasRegistry('en-GB', false);
$cs = new RouteAliasRegistry('cs-CZ', false);
$csUnicode = new RouteAliasRegistry('cs-CZ', true);

if ($en->aliasFor('results') !== 'results') throw new RuntimeException('English results alias is invalid.');
if ($cs->aliasFor('results') !== 'vysledky') throw new RuntimeException('Czech ASCII results alias is invalid.');
if ($csUnicode->aliasFor('results') !== 'výsledky') throw new RuntimeException('Czech Unicode results alias is invalid.');
if ($cs->viewFor('pořadí') !== 'standings') throw new RuntimeException('Czech Unicode standings alias cannot be parsed.');
if ($cs->viewFor('standings') !== 'standings') throw new RuntimeException('Internal legacy view alias cannot be parsed.');
if ($cs->viewFor('unknown-view') !== null) throw new RuntimeException('Unknown aliases must not resolve.');

$ref = new ReflectionClass(RouteAliasRegistry::class);
$routes = $ref->getConstant('ROUTES');
foreach (['en', 'cs', 'cs_unicode'] as $variant) {
	$seen = [];
	foreach ($routes as $view => $route) {
		$alias = $route[$variant];
		if (isset($seen[$alias])) throw new RuntimeException(sprintf('Duplicate %s alias %s for %s and %s.', $variant, $alias, $seen[$alias], $view));
		$seen[$alias] = $view;
	}
}

echo "Route alias registry OK\n";
