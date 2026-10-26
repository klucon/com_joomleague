<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Klucon\Component\Kluconcountries\Administrator\Service\Registry;
$app=Factory::getApplication();$app->getDocument()->getWebAssetManager()->useScript('multiselect');$user=$app->getIdentity();
$language=$app->getLanguage()->getTag();$tree=$this->state->get('filter.mode')==='tree';$ordering=$this->state->get('list.ordering');$direction=$this->state->get('list.direction');
$matched=array_fill_keys(array_column($this->rows,'uuid'),true);$nodes=$tree?$this->all:array_column($this->rows,null,'uuid');$included=$matched;
if($tree)foreach($this->rows as $row){$cursor=$row;$seen=[];while($cursor&&!isset($seen[$cursor->uuid])){$seen[$cursor->uuid]=true;$included[$cursor->uuid]=true;foreach($cursor->continents as $continent)$included[$continent]=true;$cursor=$cursor->parent_uuid?($nodes[$cursor->parent_uuid]??null):null;}}
$children=[];$roots=[];
if($tree)foreach($nodes as $row){if(!isset($included[$row->uuid]))continue;if($row->kind==='continent')$roots[]=$row;
    elseif($row->parent_uuid)$children[$row->parent_uuid][]=$row;
    elseif($row->continents)foreach($row->continents as $continent)$children[$continent][]=$row;
    else $roots[]=$row;}
if($tree){$rank=array_flip(array_column($this->rows,'uuid'));$sort=static function($a,$b)use($rank){return ($rank[$a->uuid]??-1)<=>($rank[$b->uuid]??-1)?:((int)$a->ordering<=>(int)$b->ordering)?:strcmp($a->name_en,$b->name_en);};usort($roots,$sort);foreach($children as &$siblings)usort($siblings,$sort);unset($siblings);}
$counts=[];foreach($this->all as $unit)if((int)$unit->state!==-2){if($unit->parent_uuid)$counts[$unit->parent_uuid]=($counts[$unit->parent_uuid]??0)+1;foreach($unit->continents as $continent)$counts[$continent]=($counts[$continent]??0)+1;}
$maxDepth=1;
$index=0;$render=function(object $row,int $depth=0,array $seen=[])use(&$render,&$index,$tree,$children,$matched,$user,$language,$maxDepth,$counts):void{
    if(isset($seen[$row->uuid])||$depth>32)return;$seen[$row->uuid]=true;$i=$index++;$context=!isset($matched[$row->uuid]);$label=Registry::label($row,$language);
    $branch=$row->kind==='continent'?$row->uuid:(string)$this->state->get('filter.branch','');
    ?>
    <tr data-uuid="<?php echo $this->escape($row->uuid); ?>"<?php echo $context?' class="text-body-secondary"':''; ?>>
     <td><?php echo HTMLHelper::_('grid.id',$i,$row->uuid,$context,'cid','cb'); ?></td>
     <td><?php echo HTMLHelper::_('jgrid.state',[1=>['unpublish','JPUBLISHED','JLIB_HTML_UNPUBLISH_ITEM','JPUBLISHED',true,'publish text-success','publish text-success'],0=>['publish','JUNPUBLISHED','JLIB_HTML_PUBLISH_ITEM','JUNPUBLISHED',true,'unpublish text-danger','unpublish text-danger'],-2=>['publish','JTRASHED','JLIB_HTML_PUBLISH_ITEM','JTRASHED',true,'trash text-danger','trash text-danger']],(int)$row->state,$i,'territory.',!$context&&$user->authorise('core.edit.state','com_kluconcountries')); ?></td>
     <th scope="row"><span aria-hidden="true" class="text-body-secondary"><?php echo str_repeat('— ',$depth); ?></span>
      <?php if($user->authorise('core.edit','com_kluconcountries')): ?><a href="index.php?option=com_kluconcountries&amp;view=territory&amp;uuid=<?php echo $this->escape($row->uuid); ?>&amp;return_branch=<?php echo $this->escape($branch); ?>"><?php echo $this->escape($label); ?></a><?php else: echo $this->escape($label); endif; ?>
      <?php if($row->valid_to&&$row->valid_to<=gmdate('Y-m-d')): ?><span class="badge bg-secondary ms-1"><?php echo Text::_('COM_KLUCONCOUNTRIES_EXPIRED'); ?></span><?php endif; ?>
     </th>
     <td class="text-center"><?php if($url=$this->registry->flagUrl($row->flag)): ?><img src="<?php echo $this->escape(Uri::root().$url); ?>" alt="<?php echo $this->escape($label); ?>" width="36" height="24" class="object-fit-contain" loading="lazy"><?php endif; ?></td>
     <td class="text-center"><?php if(isset($counts[$row->uuid])): ?><a class="btn btn-sm btn-outline-secondary" href="index.php?option=com_kluconcountries&amp;view=territories&amp;filter[branch]=<?php echo $this->escape($branch); ?>&amp;filter[mode]=list&amp;filter[parent]=<?php echo $this->escape($row->uuid); ?>&amp;filter[search]=&amp;filter[kind]=&amp;filter[state]=&amp;filter[continent]=&amp;filter[validity]=&amp;limitstart=0&amp;list[fullordering]=a.name_en%20ASC" title="<?php echo $this->escape(Text::_('COM_KLUCONCOUNTRIES_BROWSE_CHILDREN')); ?>"><span class="visually-hidden"><?php echo Text::_('COM_KLUCONCOUNTRIES_BROWSE_CHILDREN'); ?>: </span><?php echo (int)$counts[$row->uuid]; ?></a><?php else: ?>0<?php endif; ?></td>
     <td class="text-center"><?php if($user->authorise('countries.history','com_kluconcountries')): ?><a class="btn btn-sm btn-outline-secondary" href="index.php?option=com_kluconcountries&amp;view=history&amp;uuid=<?php echo $this->escape($row->uuid); ?>" title="<?php echo $this->escape(Text::_('COM_KLUCONCOUNTRIES_HISTORY')); ?>"><span class="icon-clock" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_KLUCONCOUNTRIES_HISTORY'); ?></span></a><?php else: ?><span aria-hidden="true">-</span><?php endif; ?></td>
     <td><?php echo Text::_('COM_KLUCONCOUNTRIES_TYPE_'.strtoupper($row->kind)); ?></td>
     <td><?php foreach($row->identifiers as $scheme=>$code): ?><div><abbr title="<?php echo $this->escape($scheme); ?>"><?php echo $this->escape($code); ?></abbr></div><?php endforeach; ?></td>
     <td><?php echo $this->escape($row->valid_from??''); ?></td><td><?php echo $this->escape($row->valid_to??''); ?></td>
    </tr>
    <?php if($tree&&(!$maxDepth||$depth+1<$maxDepth))foreach($children[$row->uuid]??[] as $child)$render($child,$depth+1,$seen);
};
?>
<form action="index.php?option=com_kluconcountries&amp;view=territories" method="post" name="adminForm" id="adminForm">
 <?php if($this->parent): ?><nav class="mb-3" aria-label="<?php echo $this->escape(Text::_('COM_KLUCONCOUNTRIES_HIERARCHY_NAVIGATION')); ?>"><a href="index.php?option=com_kluconcountries&amp;view=territories&amp;filter[mode]=tree&amp;filter[depth]=1&amp;filter[parent]=&amp;filter[search]=&amp;filter[kind]=&amp;filter[state]=&amp;filter[continent]=&amp;filter[validity]="><?php echo Text::_('COM_KLUCONCOUNTRIES'); ?></a><?php
 $ancestors=[];$cursor=$this->parent;$visited=[];
 while($cursor&&!isset($visited[$cursor->uuid])){$visited[$cursor->uuid]=true;$next=$cursor->parent_uuid?:($cursor->continents[0]??null);$cursor=$next?($this->all[$next]??null):null;if($cursor)array_unshift($ancestors,$cursor);}
 foreach($ancestors as $ancestor): ?> / <a href="index.php?option=com_kluconcountries&amp;view=territories&amp;filter[mode]=list&amp;filter[parent]=<?php echo $this->escape($ancestor->uuid); ?>&amp;filter[search]=&amp;filter[kind]=&amp;filter[state]=&amp;filter[continent]=&amp;filter[validity]=&amp;limitstart=0"><?php echo $this->escape(Registry::label($ancestor,$language)); ?></a><?php endforeach; ?> / <strong><?php echo $this->escape(Registry::label($this->parent,$language)); ?></strong></nav><?php endif; ?>
 <?php echo LayoutHelper::render('joomla.searchtools.default',['view'=>$this]); ?>
 <div class="table-responsive"><table class="table table-striped" id="countriesList"><thead><tr>
  <th class="w-1"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
  <th scope="col"><?php echo HTMLHelper::_('searchtools.sort','JSTATUS','a.state',$direction,$ordering); ?></th>
  <th scope="col"><?php echo HTMLHelper::_('searchtools.sort','COM_KLUCONCOUNTRIES_NAME','a.name_en',$direction,$ordering); ?></th>
  <th scope="col" class="text-center"><?php echo Text::_('COM_KLUCONCOUNTRIES_FLAG'); ?></th>
  <th scope="col" class="text-center"><?php echo Text::_('COM_KLUCONCOUNTRIES_CHILD_COUNT'); ?></th>
  <th scope="col" class="text-center"><?php echo Text::_('COM_KLUCONCOUNTRIES_HISTORY'); ?></th>
  <th scope="col"><?php echo HTMLHelper::_('searchtools.sort','COM_KLUCONCOUNTRIES_TYPE','a.kind',$direction,$ordering); ?></th>
  <th scope="col"><?php echo Text::_('COM_KLUCONCOUNTRIES_CODES'); ?></th>
  <th scope="col"><?php echo HTMLHelper::_('searchtools.sort','COM_KLUCONCOUNTRIES_VALID_FROM','a.valid_from',$direction,$ordering); ?></th>
  <th scope="col"><?php echo HTMLHelper::_('searchtools.sort','COM_KLUCONCOUNTRIES_VALID_TO','a.valid_to',$direction,$ordering); ?></th>
 </tr></thead><tbody><?php foreach($tree?$roots:$this->rows as $row)$render($row); ?></tbody></table></div>
 <?php if(!$this->rows): ?><p><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></p><?php endif; ?>
 <?php if(!$tree)echo $this->pagination->getListFooter(); ?>
 <input type="hidden" name="task" value=""><input type="hidden" name="boxchecked" value="0"><?php echo HTMLHelper::_('form.token'); ?>
</form>
