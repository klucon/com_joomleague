<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_playgroundplan
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\Playgroundplan\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_playgroundplan.
 *
 * @since  1.0.0
 */
class PlaygroundplanHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Get the playgrounds used in the configured project's matches.
     *
     * @param   Registry         $params  Module parameters.
     * @param   SiteApplication  $app     Application.
     *
     * @return  array
     */
    public function getPlaygrounds(Registry $params, SiteApplication $app): array
    {
        $projectId = $this->resolveProjectId($params, $app);

        if ($projectId === 0) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query->select('DISTINCT ' . $db->quoteName('pg') . '.*')
            ->from($db->quoteName('#__joomleague_playground', 'pg'))
            ->join('INNER', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.playground_id') . ' = ' . $db->quoteName('pg.id'))
            ->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
            ->where($db->quoteName('r.project_id') . ' = :pid')
            ->bind(':pid', $projectId, ParameterType::INTEGER)
            ->order($db->quoteName('pg.name') . ' ASC');

        $count = $this->firstPositiveInt($params->get('count') ?: $params->get('limit') ?: $params->get('maxmatches'));

        return $db->setQuery($query, 0, $count > 0 ? $count : 0)->loadObjectList();
    }

    private function resolveProjectId(Registry $params, SiteApplication $app): int
    {
        return $this->firstPositiveInt(
            $params->get('project_id')
            ?: $params->get('p')
            ?: $params->get('project')
            ?: $params->get('projects')
            ?: $params->get('project_ids')
            ?: $app->getInput()->getInt('project_id', 0)
            ?: $app->getInput()->getInt('p', 0)
        );
    }

    private function firstPositiveInt(mixed $value): int
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $number = $this->firstPositiveInt($item);
                if ($number > 0) {
                    return $number;
                }
            }
            return 0;
        }

        if (is_string($value) && str_contains($value, ',')) {
            foreach (explode(',', $value) as $item) {
                $number = $this->firstPositiveInt($item);
                if ($number > 0) {
                    return $number;
                }
            }
            return 0;
        }

        $number = (int) $value;
        return $number > 0 ? $number : 0;
    }
}
