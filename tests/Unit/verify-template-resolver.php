<?php

declare(strict_types=1);

define('_JEXEC', 1);

$root = dirname(__DIR__, 2);
require_once $root . '/administrator/components/com_joomleague/src/Service/TemplateDefinitionRegistry.php';
require_once $root . '/administrator/components/com_joomleague/src/Service/TemplateConfigResolver.php';

use Joomleague\Component\Joomleague\Domain\Service\TemplateConfigResolver;
use Joomleague\Component\Joomleague\Domain\Service\TemplateDefinitionRegistry;

$registry = new TemplateDefinitionRegistry($root . '/administrator/components/com_joomleague/resources/template-definitions/templates.json');
$resolver = new TemplateConfigResolver($registry);
$profile = ['template_defaults' => ['results' => ['group_by_round' => false, 'show_period_scores' => true]]];
$result = $resolver->resolveProfileTemplate(
	'results',
	$profile,
	['group_by_round' => true],
	['show_match_detail_button' => false],
	['sort_rounds_by_date' => false]
);

$expected = [
	'group_by_round' => true,
	'sort_rounds_by_date' => false,
	'show_match_detail_button' => false,
	'show_period_scores' => true,
	'show_set_scores' => false,
];

if ($result !== $expected) {
	throw new RuntimeException('Template layers were resolved in an incorrect order.');
}

try {
	$resolver->resolve('results', ['unknown_setting' => true]);
	throw new RuntimeException('Unknown template fields must be rejected.');
} catch (InvalidArgumentException) {
}

try {
	$resolver->resolve('results', ['group_by_round' => 'yes']);
	throw new RuntimeException('Invalid template field types must be rejected.');
} catch (InvalidArgumentException) {
}

printf("Template resolver OK: %d definitions, five ordered layers validated\n", count($registry->all()));
