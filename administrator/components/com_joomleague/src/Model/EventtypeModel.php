<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Model;
\defined('_JEXEC') or die;
final class EventtypeModel extends EntityAdminModel
{
    protected string $entityName = 'eventtype';
    protected function prepareTable($table): void { foreach(['name','icon'] as $f){$table->$f=trim((string)$table->$f);} $table->parent=(int)$table->parent?:null; $table->sports_type_id=(int)$table->sports_type_id; $table->direction=strtoupper((string)$table->direction)==='ASC'?'ASC':'DESC'; parent::prepareTable($table); }
}
