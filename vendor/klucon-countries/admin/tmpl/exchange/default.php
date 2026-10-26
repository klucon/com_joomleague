<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
?>
<?php if($this->preview): ?>
 <div class="alert alert-warning"><?php echo Text::sprintf('COM_KLUCONCOUNTRIES_PREVIEW_COUNTS',$this->preview['new'],$this->preview['updated']); ?></div>
 <div class="table-responsive"><table class="table"><thead><tr><th><?php echo Text::_('COM_KLUCONCOUNTRIES_NAME'); ?></th><th><?php echo Text::_('COM_KLUCONCOUNTRIES_TYPE'); ?></th><th>UUID</th><th><?php echo Text::_('JSTATUS'); ?></th></tr></thead><tbody>
 <?php foreach($this->preview['records'] as $row): ?><tr><th scope="row"><?php echo $this->escape($row['names']['en-GB']); ?></th><td><?php echo Text::_('COM_KLUCONCOUNTRIES_TYPE_'.strtoupper($row['kind'])); ?></td><td><code><?php echo $this->escape($row['uuid']); ?></code></td><td><?php echo Text::_(match($row['state']){1=>'JPUBLISHED',-2=>'JTRASHED',default=>'JUNPUBLISHED'}); ?></td></tr><?php endforeach; ?>
 </tbody></table></div>
 <form action="index.php?option=com_kluconcountries&amp;task=exchange.apply" method="post"><input type="hidden" name="nonce" value="<?php echo $this->escape($this->preview['nonce']); ?>"><?php echo HTMLHelper::_('form.token'); ?><button type="submit" class="btn btn-warning"><span class="icon-upload" aria-hidden="true"></span> <?php echo Text::_('COM_KLUCONCOUNTRIES_CONFIRM_IMPORT'); ?></button></form>
 <hr>
<?php endif; ?>
<form action="index.php?option=com_kluconcountries&amp;task=exchange.preview" method="post" enctype="multipart/form-data">
 <div class="row"><div class="col-12 col-lg-9">
 <p><?php echo Text::_('COM_KLUCONCOUNTRIES_IMPORT_DESC'); ?></p>
 <div class="mb-3"><label class="form-label" for="countries-import-file"><?php echo Text::_('COM_KLUCONCOUNTRIES_IMPORT_FILE'); ?></label><input class="form-control" type="file" accept=".json,application/json" id="countries-import-file" name="import_file"></div>
 <div class="mb-3"><label class="form-label" for="countries-import-json"><?php echo Text::_('COM_KLUCONCOUNTRIES_IMPORT_JSON'); ?></label><textarea class="form-control" id="countries-import-json" name="json" rows="10"></textarea></div>
 <?php echo HTMLHelper::_('form.token'); ?><button type="submit" class="btn btn-primary"><span class="icon-search" aria-hidden="true"></span> <?php echo Text::_('COM_KLUCONCOUNTRIES_PREVIEW'); ?></button>
 </div></div>
</form>
