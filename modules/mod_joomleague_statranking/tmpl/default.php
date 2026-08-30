<?php
declare(strict_types=1);
use Joomla\CMS\Language\Text;use Joomla\CMS\Router\Route;
defined('_JEXEC') or die;
$moduleclassSfx=htmlspecialchars((string)$params->get('moduleclass_sfx',''),ENT_QUOTES,'UTF-8');$format=static function(mixed$value):string{$v=rtrim(rtrim((string)$value,'0'),'.');return$v===''||$v==='-'?'0':$v;};
?>
<div class="mod-joomleague-statranking<?php echo $moduleclassSfx; ?>">
<?php if(isset($ranking['error'])):?><div class="alert alert-info mb-0"><?php echo Text::_($ranking['error']);?></div>
<?php elseif($ranking['definitions']===[]||$ranking['rows']===[]):?><div class="alert alert-info mb-0"><?php echo Text::_('MOD_JOOMLEAGUE_STATRANKING_EMPTY');?></div>
<?php else:?>
<?php if((int)$params->get('show_project_name',1)===1):?><div class="fw-bold mb-2"><?php echo htmlspecialchars((string)$ranking['project']->name,ENT_QUOTES,'UTF-8');?></div><?php endif;?>
<div class="list-group list-group-flush"><?php foreach($ranking['rows']as$row):$url=(string)$row->target_kind==='person'?Route::_('index.php?option=com_joomleague&view=person&person_id='.(int)$row->target_id):Route::_('index.php?option=com_joomleague&view=participant&project_id='.(int)$ranking['project']->id.'&entry_id='.(int)$row->target_id);?><a class="list-group-item list-group-item-action px-0 d-flex align-items-center gap-2" href="<?php echo$url;?>"><span class="badge text-bg-secondary rounded-pill"><?php echo(int)$row->rank;?></span><span class="flex-grow-1"><?php echo htmlspecialchars((string)$row->display_name,ENT_QUOTES,'UTF-8');?></span><strong><?php echo htmlspecialchars($format($row->total_value),ENT_QUOTES,'UTF-8');?></strong></a><?php endforeach;?></div>
<a class="btn btn-sm btn-outline-secondary mt-2" href="<?php echo Route::_('index.php?option=com_joomleague&view=statranking&project_id='.(int)$ranking['project']->id.'&statistic_code='.rawurlencode((string)$ranking['selected_code']));?>"><?php echo Text::_('MOD_JOOMLEAGUE_STATRANKING_SHOW_ALL');?></a>
<?php endif;?></div>
