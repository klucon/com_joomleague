<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView $this */

$view = $this;

$webAssetManager = $this->getDocument()->getWebAssetManager();
$webAssetManager->getRegistry()->addExtensionRegistryFile('com_joomleague');
$webAssetManager
	->useScript('keepalive')
	->useScript('form.validate')
	->useScript('showon')
	->useStyle('webcomponent.joomla-tab')
	->useScript('webcomponent.joomla-tab')
	->useScript('com_joomleague.form');

$tabs = [
	[
		'id' => 'details',
		'label' => $this->entity['details'],
		'content' => static function () use ($view): void {
			foreach ($view->entity['main'] as $field) {
				echo $view->form->renderField($field);
			}
		},
	],
];

foreach ($this->entity['side'] as $fieldset => $legend) {
	if ($this->form->getFieldset($fieldset)) {
		$tabs[] = [
			'id' => $fieldset,
			'label' => $legend,
			'content' => static function () use ($view, $fieldset): void {
				echo $view->form->renderFieldset($fieldset);
			},
		];
	}
}

if ((int) $this->item->id === 0) {
	foreach (($this->entity['side_new'] ?? []) as $fieldset => $legend) {
		if ($this->form->getFieldset($fieldset)) {
			$tabs[] = [
				'id' => $fieldset,
				'label' => $legend,
				'content' => static function () use ($view, $fieldset): void {
					echo $view->form->renderFieldset($fieldset);
				},
			];
		}
	}
}

// Joomla Custom Fields (com_fields) – plugin je vloží do skupiny com_fields jako fieldsety "fields-<groupId>".
$customFieldsets = [];

foreach (array_keys($this->form->getFieldsets()) as $fieldset) {
	if (str_starts_with((string) $fieldset, 'fields-') && $this->form->getFieldset($fieldset)) {
		$customFieldsets[] = $fieldset;
	}
}

if ($customFieldsets) {
	$tabs[] = [
		'id' => 'com_fields',
		'label' => 'COM_JOOMLEAGUE_CUSTOM_FIELDS',
		'content' => static function () use ($view, $customFieldsets): void {
			foreach ($customFieldsets as $fieldset) {
				echo $view->form->renderFieldset($fieldset);
			}
		},
	];
}

if (!empty($this->entity['publishing'])) {
	$tabs[] = [
		'id' => 'publishing',
		'label' => 'JGLOBAL_FIELDSET_PUBLISHING',
		'content' => static function () use ($view): void {
			foreach ($view->entity['publishing'] as $field) {
				echo $view->form->renderField($field);
			}
		},
	];
}

if ($this->form->getFieldset('permissions')) {
	$tabs[] = [
		'id' => 'permissions',
		'label' => 'JCONFIG_PERMISSIONS_LABEL',
		'content' => static function () use ($view): void {
			echo $view->form->renderFieldset('permissions');
		},
	];
}

?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="<?php echo $this->escape($this->entity['singular']); ?>-form" class="form-validate">
	<div class="main-card">
		<joomla-tab id="<?php echo $this->escape($this->entity['singular']); ?>-edit-tabs" orientation="horizontal" recall breakpoint="768">
			<?php foreach ($tabs as $index => $tab) : ?>
				<joomla-tab-element id="<?php echo $this->escape($this->entity['singular'] . '-' . $tab['id']); ?>" name="<?php echo Text::_($tab['label']); ?>"<?php echo $index === 0 ? ' active' : ''; ?>>
					<fieldset class="options-form">
						<legend><?php echo Text::_($tab['label']); ?></legend>
						<?php $tab['content'](); ?>
					</fieldset>
				</joomla-tab-element>
			<?php endforeach; ?>
		</joomla-tab>
	</div>
	<?php echo $this->form->renderControlFields(); ?>
</form>
