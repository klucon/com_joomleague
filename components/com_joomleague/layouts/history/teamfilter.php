<?php
declare(strict_types=1);
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
$data = $displayData['data'];
$id = (string) $displayData['id'];
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="col-12 col-md-4"><label class="form-label" for="<?php echo $escape($id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_HISTORY_TEAM_LABEL'); ?></label><select class="form-select" id="<?php echo $escape($id); ?>" name="filter_team_id"><option value="0"><?php echo $escape($displayData['all_label'] ?? Text::_('JALL')); ?></option><?php foreach ($data['options']['teams'] ?? [] as $teamId => $name): ?><option value="<?php echo (int) $teamId; ?>"<?php echo (int) ($data['filters']['team_id'] ?? 0) === (int) $teamId ? ' selected' : ''; ?>><?php echo $escape($name); ?></option><?php endforeach; ?></select></div>
