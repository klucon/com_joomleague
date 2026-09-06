<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$viewsRoot = $root . '/administrator/components/com_joomleague/src/View';
$templatesRoot = $root . '/administrator/components/com_joomleague/tmpl';
$checked = 0;

foreach (glob($viewsRoot . '/*/HtmlView.php') ?: [] as $viewFile) {
	$source = (string) file_get_contents($viewFile);
	if (!preg_match('/ToolbarHelper::(?:editList|publish|unpublish|checkin|deleteList)\s*\(/', $source)) {
		continue;
	}

	$view = strtolower(basename(dirname($viewFile)));
	$template = $templatesRoot . '/' . $view . '/default.php';
	if (!is_file($template)) {
		throw new RuntimeException(sprintf('List-selection toolbar view %s has no default form template.', $view));
	}

	$templateSource = (string) file_get_contents($template);
	if (!str_contains($templateSource, 'id="adminForm"')
		|| !preg_match('/<input\b[^>]*\bname=["\']boxchecked["\'][^>]*\bvalue=["\']0["\']/i', $templateSource)) {
		throw new RuntimeException(sprintf('List-selection toolbar view %s requires adminForm and boxchecked.', $view));
	}
	$checked++;
}

printf("Toolbar contract OK: %d list-selection views have Joomla adminForm state\n", $checked);
