<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Model;
\defined('_JEXEC') or die;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
final class ProjectsModel extends EntityListModel
{
    protected array $searchColumns = ['a.name', 'l.name', 's.name'];
    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        $config['filter_fields'] = ['id','a.id','name','a.name','published','a.published','league','l.name','season','s.name','sport','st.name','ordering','a.ordering'];
        parent::__construct($config, $factory);
    }
    protected function buildQuery(): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()->select([
            'a.*', $db->quoteName('l.name', 'league'), $db->quoteName('s.name', 'season'),
            $db->quoteName('st.name', 'sport'), $db->quoteName('u.name', 'editor'),
            '(SELECT COUNT(*) FROM ' . $db->quoteName('#__joomleague_round', 'rc') . ' WHERE ' . $db->quoteName('rc.project_id') . ' = ' . $db->quoteName('a.id') . ') AS ' . $db->quoteName('round_count'),
            '(SELECT MAX(' . $db->quoteName('rl.id') . ') FROM ' . $db->quoteName('#__joomleague_round', 'rl') . ' WHERE ' . $db->quoteName('rl.project_id') . ' = ' . $db->quoteName('a.id') . ') AS ' . $db->quoteName('latest_round_id'),
        ])->from($db->quoteName('#__joomleague_project', 'a'))
            ->join('LEFT', $db->quoteName('#__joomleague_league', 'l'), $db->quoteName('l.id') . '=' . $db->quoteName('a.league_id'))
            ->join('LEFT', $db->quoteName('#__joomleague_season', 's'), $db->quoteName('s.id') . '=' . $db->quoteName('a.season_id'))
            ->join('LEFT', $db->quoteName('#__joomleague_sports_type', 'st'), $db->quoteName('st.id') . '=' . $db->quoteName('a.sports_type_id'))
            ->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . '=' . $db->quoteName('a.checked_out'));
        foreach (['league_id','season_id','sports_type_id'] as $field) {
            $value = (int) $this->getState('filter.' . $field);
            if ($value) { $parameter = ':' . $field; $query->where($db->quoteName('a.' . $field) . '=' . $parameter)->bind($parameter, $value, ParameterType::INTEGER); }
        }
        return $query;
    }
}
