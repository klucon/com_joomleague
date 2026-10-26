<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
?>
<form action="index.php?option=com_kluconcountries" method="post" enctype="multipart/form-data" name="adminForm" id="adminForm">
 <?php echo HTMLHelper::_('uitab.startTabSet','territoryTabs',['active'=>'details']); ?>
 <?php foreach(['details','identifiers','artwork'] as $fieldset): ?>
 <?php echo HTMLHelper::_('uitab.addTab','territoryTabs',$fieldset,Text::_('COM_KLUCONCOUNTRIES_TAB_'.strtoupper($fieldset))); ?>
 <div class="row"><div class="col-12 col-lg-9"><?php echo $this->form->renderFieldset($fieldset); ?></div></div>
 <?php if($fieldset==='artwork'&&$this->item&&($url=$this->registry->flagUrl($this->item->flag))): ?><img src="<?php echo $this->escape(\Joomla\CMS\Uri\Uri::root().$url); ?>" alt="<?php echo $this->escape($this->item->labels['en-GB']??''); ?>" width="160" height="100" class="object-fit-contain border p-2"><?php endif; ?>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php endforeach; ?>
 <?php if($this->item&&$this->item->kind==='continent'): ?>
 <?php echo HTMLHelper::_('uitab.addTab','territoryTabs','facts',Text::_('COM_KLUCONCOUNTRIES_TAB_FACTS')); ?>
 <div class="row"><div class="col-12 col-lg-10">
 <?php $facts=\Klucon\Component\Kluconcountries\Administrator\Service\ContinentFacts::get($this->item);$density=\Klucon\Component\Kluconcountries\Administrator\Service\ContinentFacts::density($facts); ?>
 <dl class="row"><dt class="col-sm-4"><?php echo Text::_('COM_KLUCONCOUNTRIES_FACT_DENSITY'); ?></dt><dd class="col-sm-8" id="continent-density"><?php echo $density===null?Text::_('COM_KLUCONCOUNTRIES_FACT_NOT_APPLICABLE'):$this->escape(number_format($density,3,'.',',')); ?></dd></dl>
 <p class="text-body-secondary"><?php echo Text::_('COM_KLUCONCOUNTRIES_FACT_DENSITY_DESC'); ?></p>
 <?php foreach(\Klucon\Component\Kluconcountries\Administrator\Service\ContinentFacts::GROUPS as $group=>$fields): ?>
 <h2 class="h4 mt-4"><?php echo Text::_('COM_KLUCONCOUNTRIES_FACT_GROUP_'.strtoupper($group)); ?></h2>
 <?php echo $this->form->renderFieldset('facts_'.$group); ?>
 <?php if(!empty($facts[$group.'_source'])): ?><p><a href="<?php echo $this->escape($facts[$group.'_source']); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_KLUCONCOUNTRIES_FACT_OPEN_SOURCE'); ?></a></p><?php endif; ?>
 <?php endforeach; ?>
 </div></div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php endif; ?>
 <?php if($this->item&&$this->item->kind==='continent'): ?>
 <?php echo HTMLHelper::_('uitab.addTab','territoryTabs','information',Text::_('COM_KLUCONCOUNTRIES_TAB_INFORMATION')); ?>
 <div class="row"><div class="col-12 col-lg-9">
 <?php $reference=\Klucon\Component\Kluconcountries\Administrator\Service\ContinentInformation::reference($this->item);$memberCount=0;foreach($this->registry->all() as $unit)if((int)$unit->state!==-2&&in_array($this->item->uuid,$unit->continents,true))$memberCount++; ?>
 <dl class="row"><dt class="col-sm-4"><?php echo Text::_('COM_KLUCONCOUNTRIES_CHILD_COUNT'); ?></dt><dd class="col-sm-8"><?php echo $memberCount; ?></dd>
 <?php if($reference): ?><dt class="col-sm-4"><?php echo Text::_('COM_KLUCONCOUNTRIES_INFO_GEONAMES'); ?></dt><dd class="col-sm-8"><?php echo $this->escape($reference['geonames_code']); ?></dd><dt class="col-sm-4"><?php echo Text::_('COM_KLUCONCOUNTRIES_INFO_REVIEWED'); ?></dt><dd class="col-sm-8"><?php echo $this->escape($reference['reviewed_on']); ?></dd><?php endif; ?></dl>
 <?php echo $this->form->renderFieldset('information'); ?>
 <?php if($reference): ?><h2 class="h4"><?php echo Text::_('COM_KLUCONCOUNTRIES_INFO_SOURCES'); ?></h2><ul><?php foreach($reference['sources'] as $source): ?><li><a href="<?php echo $this->escape($source); ?>" target="_blank" rel="noopener noreferrer"><?php echo $this->escape($source); ?></a></li><?php endforeach; ?></ul><?php endif; ?>
 </div></div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php endif; ?>
 <?php if($this->item): ?>
 <?php echo HTMLHelper::_('uitab.addTab','territoryTabs','source',Text::_('COM_KLUCONCOUNTRIES_TAB_SOURCE')); ?>
 <?php
 $source=$this->item->source;$sourceIdentifiers=$source['identifiers']??[];
 $origin=!$source?'LOCAL':($this->item->kind==='continent'?'CLASSIFICATION':((isset($sourceIdentifiers['ruian-vusc'])||isset($sourceIdentifiers['ruian-okres']))?'RUIAN':'CATALOGUE'));
 ?>
 <dl class="row" id="territory-source-metadata">
 <dt class="col-sm-4"><?php echo Text::_('COM_KLUCONCOUNTRIES_SOURCE_UUID'); ?></dt><dd class="col-sm-8"><code><?php echo $this->escape($this->item->uuid); ?></code></dd>
 <dt class="col-sm-4"><?php echo Text::_('COM_KLUCONCOUNTRIES_VERSION'); ?></dt><dd class="col-sm-8"><?php echo (int)$this->item->version; ?></dd>
 <dt class="col-sm-4"><?php echo Text::_('COM_KLUCONCOUNTRIES_SOURCE_ORIGIN'); ?></dt><dd class="col-sm-8"><?php echo Text::_('COM_KLUCONCOUNTRIES_SOURCE_ORIGIN_'.$origin); ?></dd>
 <?php if(!empty($source['names']['en-GB'])): ?><dt class="col-sm-4"><?php echo Text::_('COM_KLUCONCOUNTRIES_SOURCE_NAME'); ?></dt><dd class="col-sm-8"><?php echo $this->escape($source['names']['en-GB']); ?></dd><?php endif; ?>
 <?php if(!empty($source['code'])): ?><dt class="col-sm-4"><?php echo Text::_('COM_KLUCONCOUNTRIES_SOURCE_CODE'); ?></dt><dd class="col-sm-8"><code><?php echo $this->escape($source['code']); ?></code></dd><?php endif; ?>
 <?php if(isset($source['ordering'])): ?><dt class="col-sm-4"><?php echo Text::_('COM_KLUCONCOUNTRIES_SOURCE_ORDERING'); ?></dt><dd class="col-sm-8"><?php echo (int)$source['ordering']; ?></dd><?php endif; ?>
 <?php foreach(['valid_from','valid_to','source_updated_at'] as $field): ?>
 <?php if(!empty($source[$field])): ?><dt class="col-sm-4"><?php echo Text::_('COM_KLUCONCOUNTRIES_SOURCE_'.strtoupper($field)); ?></dt><dd class="col-sm-8"><?php echo $this->escape($source[$field]); ?></dd><?php endif; ?>
 <?php endforeach; ?>
 <?php if($sourceIdentifiers): ?><dt class="col-sm-4"><?php echo Text::_('COM_KLUCONCOUNTRIES_CODES'); ?></dt><dd class="col-sm-8"><ul class="list-unstyled mb-0"><?php foreach($sourceIdentifiers as $scheme=>$code): ?><li><code><?php echo $this->escape($scheme); ?></code>: <?php echo $this->escape($code); ?></li><?php endforeach; ?></ul></dd><?php endif; ?>
 </dl>
 <p><?php echo Text::_('COM_KLUCONCOUNTRIES_SOURCE_DESC'); ?></p>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php endif; ?>
 <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
 <input type="hidden" name="uuid" value="<?php echo $this->escape($this->item?->uuid??''); ?>"><input type="hidden" name="version" value="<?php echo (int)($this->item?->version??0); ?>"><input type="hidden" name="task" value="">
 <?php echo HTMLHelper::_('form.token'); ?>
 <input type="hidden" name="return_branch" value="<?php echo $this->escape($this->returnBranch); ?>">
</form>
