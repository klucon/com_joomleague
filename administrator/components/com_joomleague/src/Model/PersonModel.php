<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Model;
\defined('_JEXEC') or die;
final class PersonModel extends EntityAdminModel
{
    protected string $entityName = 'person';
    protected function prepareTable($table): void { foreach (['firstname','lastname','nickname','knvbnr','phone','mobile','email','website','address','zipcode','location','state','picture','info','notes'] as $f) { $table->$f=trim((string)$table->$f); } foreach(['birthday','deathday'] as $f){$table->$f=trim((string)$table->$f)?:null;} $table->country=trim((string)$table->country)?:null; $table->address_country=trim((string)$table->address_country)?:null; $table->contact_id=(int)$table->contact_id?:null; $table->position_id=(int)$table->position_id?:null; parent::prepareTable($table); }
}
