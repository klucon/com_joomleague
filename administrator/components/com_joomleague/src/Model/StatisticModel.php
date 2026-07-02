<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Model;
\defined('_JEXEC') or die;
final class StatisticModel extends EntityAdminModel
{
    protected string $entityName = 'statistic';
    public function getItem($pk = null): object|false
    {
        $item = parent::getItem($pk);

        if ($item === false) {
            return false;
        }

        foreach (['params', 'baseparams'] as $field) {
            if (is_array($item->$field) || is_object($item->$field)) {
                $item->$field = json_encode($item->$field, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
            } elseif (trim((string) $item->$field) === '') {
                $item->$field = '{}';
            }
        }

        return $item;
    }

    protected function prepareTable($table): void
    {
        foreach (['name', 'short', 'icon', 'class', 'note'] as $field) {
            $table->$field = trim((string) $table->$field);
        }

        foreach (['params', 'baseparams'] as $field) {
            if (is_array($table->$field) || is_object($table->$field)) {
                $table->$field = json_encode($table->$field, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
            } else {
                $table->$field = trim((string) $table->$field) ?: '{}';
            }
        }

        $table->sports_type_id = (int) $table->sports_type_id;
        parent::prepareTable($table);
    }
}
