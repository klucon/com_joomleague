<?php

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

$context = (string) ($displayData['context'] ?? '');
$item = $displayData['item'] ?? null;

if ($context === '' || !is_object($item)) {
	return;
}

$fields = FieldsHelper::getFields($context, $item, true);

if ($fields === []) {
	return;
}

echo LayoutHelper::render(
	'fields.render',
	['context' => $context, 'item' => $item, 'fields' => $fields],
	JPATH_ROOT . '/components/com_fields/layouts'
);
