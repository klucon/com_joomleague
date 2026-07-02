<?php

/**
 * @package     Klucon.Plugin
 * @subpackage  Finder.joomleague
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Plugin\Finder\Joomleague\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Indexer;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\QueryInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Smart Search (Finder) adapter for JoomLeague.
 *
 * Indexes the main public JoomLeague entities (clubs, projects/competitions,
 * persons and playgrounds) in a single content type, distinguished by the
 * "Type" taxonomy.
 *
 * Note: the JoomLeague component core does not dispatch finder save/delete
 * events, so this adapter is updated through batch indexing (the Smart Search
 * "Index" button or the indexing scheduled task), not live on each record save.
 *
 * @since  1.0.0
 */
final class Joomleague extends Adapter implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * The plugin identifier.
     *
     * @var  string
     */
    protected $context = 'Joomleague';

    /**
     * The extension name.
     *
     * @var  string
     */
    protected $extension = 'com_joomleague';

    /**
     * The sublayout to use when rendering the results.
     *
     * @var  string
     */
    protected $layout = 'project';

    /**
     * The type of content the adapter indexes.
     *
     * @var  string
     */
    protected $type_title = 'JoomLeague';

    /**
     * The table name (used by base helpers only).
     *
     * @var  string
     */
    protected $table = '#__joomleague_project';

    /**
     * The field the published state is stored in.
     *
     * @var  string
     */
    protected $state_field = 'state';

    /**
     * Load the language file on instantiation.
     *
     * @var  boolean
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     */
    public static function getSubscribedEvents(): array
    {
        return parent::getSubscribedEvents();
    }

    /**
     * Method to setup the indexer to be run.
     *
     * @return  boolean  True on success.
     */
    protected function setup()
    {
        return true;
    }

    /**
     * Method to index an item.
     *
     * @param   Result  $item  The item to index as a Result object.
     *
     * @return  void
     */
    protected function index(Result $item)
    {
        // Check if the extension is enabled.
        if (ComponentHelper::isEnabled($this->extension) === false) {
            return;
        }

        $type      = $item->jl_type ?? 'project';
        $projectId = (int) ($item->jl_project_id ?? 0);

        // Skip persons that are not attached to any project (no valid route).
        if ($type === 'person' && $projectId === 0) {
            return;
        }

        // Build the route/identifier for this entity type.
        switch ($type) {
            case 'club':
                $url = 'index.php?option=com_joomleague&view=club&id=' . (int) $item->id;
                $taxType = 'Club';
                break;

            case 'person':
                $url = 'index.php?option=com_joomleague&view=person&id=' . (int) $item->id
                    . '&project_id=' . $projectId;
                $taxType = 'Person';
                break;

            case 'playground':
                $url = 'index.php?option=com_joomleague&view=playground&id=' . (int) $item->id;
                $taxType = 'Playground';
                break;

            case 'project':
            default:
                $url = 'index.php?option=com_joomleague&view=project&project_id=' . (int) $item->id;
                $taxType = 'Competition';
                break;
        }

        $item->setLanguage();
        $item->params = new Registry();
        $item->url    = $url;
        $item->route  = $url;
        $item->access = 1;

        if (empty($item->language)) {
            $item->language = '*';
        }

        // Index the description text.
        if (!empty($item->summary)) {
            $item->addInstruction(Indexer::META_CONTEXT, 'summary');
        }

        // Type taxonomy lets users filter by JoomLeague entity kind.
        $item->addTaxonomy('Type', $taxType);
        $item->addTaxonomy('Language', $item->language);

        $this->indexer->index($item);
    }

    /**
     * Method to get the SQL query used to retrieve the list of content items.
     *
     * Returns a single derived-table query that unions the indexable
     * JoomLeague entities so the base indexer can count and page through them.
     *
     * @param   mixed  $query  An object implementing QueryInterface or null.
     *
     * @return  QueryInterface  A database query object.
     */
    protected function getListQuery($query = null)
    {
        $db = $this->getDatabase();

        if ($query instanceof QueryInterface) {
            return $query;
        }

        $union = '('
            . "SELECT c.id AS id, c.name AS title,"
            . " TRIM(CONCAT_WS(' ', c.location, c.notes)) AS summary,"
            . " 1 AS state, 'club' AS jl_type, 0 AS jl_project_id"
            . ' FROM #__joomleague_club AS c'
            . ' UNION ALL '
            . "SELECT p.id, p.name, p.name, p.published, 'project', p.id"
            . ' FROM #__joomleague_project AS p'
            . ' UNION ALL '
            . "SELECT pe.id, TRIM(CONCAT_WS(' ', pe.firstname, pe.lastname)),"
            . " TRIM(CONCAT_WS(' ', pe.nickname, pe.notes)), pe.published, 'person',"
            . ' COALESCE((SELECT pt.project_id FROM #__joomleague_project_team AS pt'
            . ' INNER JOIN #__joomleague_team_player AS tp ON tp.projectteam_id = pt.id'
            . ' WHERE tp.person_id = pe.id ORDER BY pt.project_id DESC LIMIT 1), 0)'
            . ' FROM #__joomleague_person AS pe'
            . ' UNION ALL '
            . "SELECT pl.id, pl.name, TRIM(CONCAT_WS(' ', pl.city, pl.notes)), 1, 'playground', 0"
            . ' FROM #__joomleague_playground AS pl'
            . ') AS a';

        return $db->createQuery()
            ->select('a.*')
            ->from($union);
    }
}
