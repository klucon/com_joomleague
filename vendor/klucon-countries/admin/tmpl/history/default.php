<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
$user=Factory::getApplication()->getIdentity();$canRestore=$user->authorise('countries.restore','com_kluconcountries')&&$user->authorise('core.edit','com_kluconcountries')&&$user->authorise('core.edit.state','com_kluconcountries');
?>
<h2 class="h4"><?php echo $this->escape($this->item->labels['en-GB']); ?></h2>
<p><code><?php echo $this->escape($this->item->uuid); ?></code></p>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th><?php echo Text::_('COM_KLUCONCOUNTRIES_VERSION'); ?></th><th><?php echo Text::_('JDATE'); ?></th><th><?php echo Text::_('COM_KLUCONCOUNTRIES_HISTORY_ACTION'); ?></th><th><?php echo Text::_('COM_KLUCONCOUNTRIES_HISTORY_ACTOR'); ?></th><th><?php echo Text::_('COM_KLUCONCOUNTRIES_HISTORY_VALUES'); ?></th><th><?php echo Text::_('JACTION_EDIT'); ?></th></tr></thead><tbody>
<?php foreach($this->revisions as $revision): $data=json_decode($revision['snapshot'],true,64,JSON_THROW_ON_ERROR); ?>
 <tr><th scope="row"><?php echo (int)$revision['version']; ?></th><td><?php echo HTMLHelper::_('date',$revision['created_at'],'Y-m-d H:i:s'); ?></td><td><?php echo Text::_('COM_KLUCONCOUNTRIES_ACTION_'.strtoupper($revision['action'])); ?></td><td><?php echo (int)$revision['actor_id']?:Text::_('COM_KLUCONCOUNTRIES_SYSTEM'); ?></td>
 <td><details><summary><?php echo $this->escape($data['names']['en-GB']??''); ?></summary><dl>
  <dt><?php echo Text::_('COM_KLUCONCOUNTRIES_TYPE'); ?></dt><dd><?php echo Text::_('COM_KLUCONCOUNTRIES_TYPE_'.strtoupper($data['kind'])); ?></dd>
  <dt><?php echo Text::_('JSTATUS'); ?></dt><dd><?php echo Text::_(match((int)$data['state']){1=>'JPUBLISHED',-2=>'JTRASHED',default=>'JUNPUBLISHED'}); ?></dd>
  <dt><?php echo Text::_('COM_KLUCONCOUNTRIES_PARENT'); ?></dt><dd><code><?php echo $this->escape($data['parent_uuid']??''); ?></code></dd>
  <dt><?php echo Text::_('COM_KLUCONCOUNTRIES_CODES'); ?></dt><dd><?php foreach($data['identifiers'] as $scheme=>$code): ?><div><?php echo $this->escape($scheme.': '.$code); ?></div><?php endforeach; ?></dd>
  <dt><?php echo Text::_('COM_KLUCONCOUNTRIES_VALIDITY'); ?></dt><dd><?php echo $this->escape(($data['valid_from']??'').' / '.($data['valid_to']??'')); ?></dd>
 </dl></details></td>
 <td><?php if($canRestore&&(int)$revision['version']!==(int)$this->item->version): ?><form action="index.php?option=com_kluconcountries&amp;task=territory.restore" method="post"><input type="hidden" name="uuid" value="<?php echo $this->escape($this->item->uuid); ?>"><input type="hidden" name="version" value="<?php echo (int)$this->item->version; ?>"><input type="hidden" name="revision" value="<?php echo (int)$revision['version']; ?>"><?php echo HTMLHelper::_('form.token'); ?><button type="submit" class="btn btn-sm btn-outline-secondary"><span class="icon-history" aria-hidden="true"></span> <?php echo Text::_('COM_KLUCONCOUNTRIES_RESTORE'); ?></button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php if(!$this->revisions): ?><p><?php echo Text::_('COM_KLUCONCOUNTRIES_NO_HISTORY'); ?></p><?php endif; ?>
<form action="index.php?option=com_kluconcountries&amp;view=history&amp;uuid=<?php echo $this->escape($this->item->uuid); ?>" id="adminForm" name="adminForm" method="post"><?php echo $this->pagination->getListFooter(); ?><input type="hidden" name="task" value=""><?php echo HTMLHelper::_('form.token'); ?></form>
<p class="text-body-secondary"><?php echo Text::_('COM_KLUCONCOUNTRIES_HISTORY_DESC'); ?></p>
