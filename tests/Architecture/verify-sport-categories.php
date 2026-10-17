<?php

declare(strict_types=1);
$root = dirname(__DIR__, 2);
$admin = $root . '/administrator/components/com_joomleague';
$service = file_get_contents($admin . '/src/Service/SportCategoryService.php');
foreach (['transactionStart()', 'transactionCommit()', 'transactionRollback()', 'FOR UPDATE', 'SportCategoryTree', 'canChangeState', 'UuidFactory::v4()'] as $required) {
	if (!str_contains($service, $required)) throw new RuntimeException('Missing category safety boundary: ' . $required);
}
foreach (['SportcategoriesController', 'SportprofilesController'] as $controller) {
	$source = file_get_contents($admin . '/src/Controller/' . $controller . '.php');
	if (!str_contains($source, 'Session::checkToken()') || !str_contains($source, "'core.edit'")) throw new RuntimeException('Missing CSRF or category edit ACL.');
}
$categoryController = file_get_contents($admin . '/src/Controller/SportcategoriesController.php');
foreach (['core.create', 'core.edit.state', 'core.delete'] as $action) {
	if (!str_contains($categoryController, $action)) throw new RuntimeException('Missing category permission: ' . $action);
}
foreach (['mysql.utf8' => 'mysql', 'postgresql' => 'postgresql'] as $install => $driver) {
	$schema = file_get_contents($admin . '/sql/install.' . $install . '.sql');
	$update = file_get_contents($admin . '/sql/updates/' . $driver . '/6.2.0-2026100302.sql');
	foreach (['sport_category', 'sport_category_lock', 'sport_profile_category', 'fk_jl_sport_category_parent', 'fk_jl_profile_category_category', 'uq_jl_sport_category_uuid', 'uq_jl_sport_category_code'] as $required) {
		if (!str_contains($schema, $required) || !str_contains($update, $required)) throw new RuntimeException('Missing classification schema: ' . $required);
	}
}
foreach (['en-GB', 'cs-CZ'] as $language) {
	$lines = file($admin . '/language/' . $language . '/com_joomleague.ini');
	$keys = [];
	foreach ($lines as $line) {
		if (preg_match('/^(COM_JOOMLEAGUE_SPORTCATEGOR[A-Z_]+)=/', $line, $match)) {
			if (isset($keys[$match[1]])) throw new RuntimeException('Duplicate category language key.');
			$keys[$match[1]] = true;
		}
	}
	if (count($keys) !== 40) throw new RuntimeException('Missing category translations for ' . $language . ': ' . count($keys));
}
foreach (['src/Service/SportCategoryService.php', 'tmpl/sportcategories/default.php', 'src/Controller/SportcategoriesController.php'] as $path) {
	$source = file_get_contents($admin . '/' . $path);
	if (str_contains($source, '#__categories') || str_contains($source, 'com_categories')) throw new RuntimeException('Sport categories must not depend on Joomla content categories.');
}
echo "Sport category architecture: owned schema, ACL, CSRF, serialized writes and unique English/Czech labels OK\n";
