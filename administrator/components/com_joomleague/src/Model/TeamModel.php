<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Model;
\defined('_JEXEC') or die;
final class TeamModel extends EntityAdminModel
{
    protected string $entityName = 'team';
    protected function prepareTable($table): void { foreach (['name','short_name','middle_name','website','picture','info','notes'] as $f) { $table->$f = trim((string) $table->$f); } $table->club_id=(int)$table->club_id; parent::prepareTable($table); }
}
