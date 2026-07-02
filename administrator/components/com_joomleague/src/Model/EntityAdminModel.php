<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Model;
\defined('_JEXEC') or die;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
abstract class EntityAdminModel extends AdminModel
{
    protected string $entityName;
    public function getTable($name = '', $prefix = 'Administrator', $options = []): Table { return parent::getTable($name ?: ucfirst($this->entityName), $prefix, $options); }
    public function getForm($data = [], $loadData = true): Form|false { return $this->loadForm('com_joomleague.' . $this->entityName, $this->entityName, ['control' => 'jform', 'load_data' => $loadData]); }
    protected function loadFormData(): object { $item = $this->getItem(); $this->preprocessData('com_joomleague.' . $this->entityName, $item); return $item; }
    protected function canDelete($record): bool { return $this->getCurrentUser()->authorise('core.delete', $this->assetContext($record)); }
    protected function canEditState($record): bool { return $this->getCurrentUser()->authorise('core.edit.state', $this->assetContext($record)); }
    protected function prepareTable($table): void
    {
        $table->modified = (new Date())->toSql();
        $table->modified_by = (int) $this->getCurrentUser()->id ?: null;
        if ((int) $table->id === 0) { $table->ordering = $table->getNextOrder(); }
    }
    private function assetContext($record): string
    {
        $id = is_object($record) && isset($record->id) ? (int) $record->id : 0;

        return $id > 0 ? 'com_joomleague.' . $this->entityName . '.' . $id : 'com_joomleague';
    }
}
