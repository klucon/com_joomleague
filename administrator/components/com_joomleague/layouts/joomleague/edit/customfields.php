<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$form = $displayData['form'];
$tabSet = (string) $displayData['tabSet'];

foreach ($form->getFieldsets('com_fields') as $fieldset) :
	$label = trim((string) ($fieldset->label ?? ''));
	$label = $label !== '' ? Text::_($label) : Text::_('COM_JOOMLEAGUE_FIELDS_DEFAULT_GROUP');
	$tabId = 'custom-fields-' . preg_replace('/[^a-z0-9_-]+/i', '-', (string) $fieldset->name);
	?>
	<?php echo HTMLHelper::_('uitab.addTab', $tabSet, $tabId, $label); ?>
	<div class="row"><div class="col-12">
		<fieldset class="options-form">
			<legend><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></legend>
			<?php if (!empty($fieldset->description)) : ?>
				<p class="text-body-secondary"><?php echo htmlspecialchars(Text::_((string) $fieldset->description), ENT_QUOTES, 'UTF-8'); ?></p>
			<?php endif; ?>
			<?php echo $form->renderFieldset((string) $fieldset->name); ?>
		</fieldset>
	</div></div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endforeach; ?>
