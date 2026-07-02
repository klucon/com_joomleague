<?php

/**
 * @package     Klucon.Plugin
 * @subpackage  Content.joomleaguematch
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Plugin\Content\Joomleaguematch\Extension;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Nahrazuje {jlmatch id=X ...} v obsahu živým detailem zápasu vykresleným
 * přes sdílený (přepisovatelný) layout komponenty. Data se čtou z jednoho
 * zdroje (site model), takže jsou vždy aktuální.
 */
final class Joomleaguematch extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return ['onContentPrepare' => 'onContentPrepare'];
    }

    public function onContentPrepare(ContentPrepareEvent $event): void
    {
        if ($this->getApplication()->isClient('api') || $event->getContext() === 'com_finder.indexer') {
            return;
        }

        $item = $event->getItem();

        if (!isset($item->text) || strpos($item->text, 'jlmatch') === false) {
            return;
        }

        $item->text = preg_replace_callback(
            '#\{jlmatch\s*([^}]*)\}#s',
            fn ($matches) => $this->renderMatch($matches[1]),
            $item->text
        );
    }

    /**
     * Vykreslí detail zápasu pro jeden tag {jlmatch ...}.
     */
    private function renderMatch(string $attrString): string
    {
        $attrs = $this->parseAttributes($attrString);
        $id    = (int) ($attrs['id'] ?? 0);

        if ($id < 1) {
            return '';
        }

        try {
            $model  = Factory::getApplication()->bootComponent('com_joomleague')->getMVCFactory()
                ->createModel('Matchreport', 'Site', ['ignore_request' => true]);
            $match  = $model->getMatch($id);

            if (!$match) {
                return '';
            }

            $events = $model->getMatchEvents($id);
        } catch (\Throwable $exception) {
            return '';
        }

        // Jazyk komponenty (klíče v layoutu).
        $this->getApplication()->getLanguage()->load('com_joomleague', JPATH_SITE);

        $options = [
            'summary' => !isset($attrs['summary']) || $attrs['summary'] !== '0',
            'events'  => !isset($attrs['events']) || $attrs['events'] !== '0',
            'link'    => isset($attrs['link']) && $attrs['link'] === '1',
        ];

        return LayoutHelper::render(
            'joomleague.match.detail',
            ['match' => $match, 'events' => $events, 'options' => $options],
            JPATH_SITE . '/components/com_joomleague/layouts'
        );
    }

    /**
     * Rozparsuje atributy shortcode: id=5 show="..." link="1" (uvozovky volitelné).
     *
     * @return array<string,string>
     */
    private function parseAttributes(string $string): array
    {
        $attrs = [];

        if (preg_match_all('/(\w+)\s*=\s*"([^"]*)"|(\w+)\s*=\s*(\S+)/', $string, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (($match[1] ?? '') !== '') {
                    $attrs[strtolower($match[1])] = $match[2];
                } elseif (($match[3] ?? '') !== '') {
                    $attrs[strtolower($match[3])] = $match[4];
                }
            }
        }

        return $attrs;
    }
}
