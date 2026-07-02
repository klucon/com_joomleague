<?php

/**
 * @package     Klucon.Plugin
 * @subpackage  Content.joomleagueperson
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Plugin\Content\Joomleagueperson\Extension;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Replaces {jl_player}First Last{/jl_player} markup in content with a link to
 * the JoomLeague person (player) frontend detail page.
 *
 * @since  1.0.0
 */
final class Joomleagueperson extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

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
        return ['onContentPrepare' => 'onContentPrepare'];
    }

    /**
     * Replace player tags with links.
     *
     * @param   ContentPrepareEvent  $event  The event instance.
     *
     * @return  void
     */
    public function onContentPrepare(ContentPrepareEvent $event): void
    {
        // Do not run during indexing or in the API application.
        if ($this->getApplication()->isClient('api') || $event->getContext() === 'com_finder.indexer') {
            return;
        }

        $item = $event->getItem();

        if (!isset($item->text) || strpos($item->text, 'jl_player') === false) {
            return;
        }

        $item->text = preg_replace_callback(
            '#\{jl_player\}(.*?)\{/jl_player\}#s',
            function ($matches) {
                return $this->renderPlayer($matches[1]);
            },
            $item->text
        );
    }

    /**
     * Build the replacement HTML for a single player tag.
     *
     * @param   string  $name  The raw player name from the tag.
     *
     * @return  string
     */
    private function renderPlayer(string $name): string
    {
        $clean = trim(html_entity_decode($name, ENT_QUOTES, 'UTF-8'));
        $parts = preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (\count($parts) > 1) {
            $firstname = array_shift($parts);
            $lastname  = implode(' ', $parts);
        } else {
            $firstname = '';
            $lastname  = $clean;
        }

        $row = $this->findPerson($firstname, $lastname);

        // No published match: output the plain name without markup.
        if ($row === null) {
            return htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        }

        $url = Route::_(
            'index.php?option=com_joomleague&view=person&id=' . (int) $row->pid
            . '&project_id=' . (int) $row->project_id
        );

        return '<a class="jl-player" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a>';
    }

    /**
     * Look up a published person by name, returning the most recent project.
     *
     * @param   string  $firstname  Person first name.
     * @param   string  $lastname   Person last name.
     *
     * @return  object|null
     */
    private function findPerson(string $firstname, string $lastname)
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query->select($db->quoteName(['pr.id'], ['pid']))
            ->select($db->quoteName('pt.project_id'))
            ->from($db->quoteName('#__joomleague_person', 'pr'))
            ->join('INNER', $db->quoteName('#__joomleague_team_player', 'tp'), $db->quoteName('tp.person_id') . ' = ' . $db->quoteName('pr.id'))
            ->join('INNER', $db->quoteName('#__joomleague_project_team', 'pt'), $db->quoteName('pt.id') . ' = ' . $db->quoteName('tp.projectteam_id'))
            ->join('INNER', $db->quoteName('#__joomleague_project', 'p'), $db->quoteName('p.id') . ' = ' . $db->quoteName('pt.project_id'))
            ->where($db->quoteName('pr.lastname') . ' = :lastname')
            ->where($db->quoteName('pr.published') . ' = 1')
            ->where($db->quoteName('tp.published') . ' = 1')
            ->where($db->quoteName('p.published') . ' = 1')
            ->bind(':lastname', $lastname)
            ->order($db->quoteName('p.id') . ' DESC')
            ->setLimit(1);

        if ($firstname !== '') {
            $query->where($db->quoteName('pr.firstname') . ' = :firstname')
                ->bind(':firstname', $firstname);
        }

        $db->setQuery($query);

        return $db->loadObject() ?: null;
    }
}
