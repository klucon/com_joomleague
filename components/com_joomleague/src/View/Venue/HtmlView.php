<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Venue;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\EntitySchemaBuilder;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $venue = [];

	public function display($tpl = null): void
	{
		$this->venue = $this->getModel()->getVenue();
		PublicViewGuard::requireItem($this->venue, 'venue');
		$title = isset($this->venue['venue']) ? (string) $this->venue['venue']->name : Text::_('COM_JOOMLEAGUE_VENUE_VIEW_TITLE');
		if (isset($this->venue['venue'])) {
			$item = $this->venue['venue'];
			$item->picture = \Joomleague\Component\Joomleague\Site\Service\MediaResolver::image('venue', $item->picture);
			$url = Route::_('index.php?option=com_joomleague&view=venue&venue_id=' . (int) $item->id, false, Route::TLS_IGNORE, true);
			$seo = new SeoMetadata();
			$description = trim((string) ($item->description ?? '')) ?: Text::sprintf('COM_JOOMLEAGUE_SEO_ENTITY_DESCRIPTION', Text::_('COM_JOOMLEAGUE_VENUE_VIEW_TITLE'), $title);
			$seo->apply($this->getDocument(), $title, $url, $description, (string) ($item->picture ?? ''));
			$seo->addStructuredData($this->getDocument(), (new EntitySchemaBuilder())->build('venue', $item, $url));
		} else {
			$this->getDocument()->setTitle($title);
		}
		parent::display($tpl);
	}
}
