<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Club;

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
	public array $club = [];

	public function display($tpl = null): void
	{
		$this->club = $this->getModel()->getClub();
		PublicViewGuard::requireItem($this->club, 'club');
		$title = isset($this->club['club'])
			? (string) $this->club['club']->name
			: Text::_('COM_JOOMLEAGUE_CLUB_VIEW_TITLE');
		if (isset($this->club['club'])) {
			$item = $this->club['club'];
			$clubLogo = $item->logo;
			$item->logo = \Joomleague\Component\Joomleague\Site\Service\MediaResolver::image('club', $clubLogo);
			foreach ($this->club['teams'] as $team) {
				$team->logo = \Joomleague\Component\Joomleague\Site\Service\MediaResolver::image('team', $team->logo, $clubLogo, $team->picture);
				$team->picture = \Joomleague\Component\Joomleague\Site\Service\MediaResolver::image('', $team->picture);
			}
			$url = Route::_('index.php?option=com_joomleague&view=club&club_id=' . (int) $item->id, false, Route::TLS_IGNORE, true);
			$seo = new SeoMetadata();
			$description = trim((string) ($item->description ?? '')) ?: Text::sprintf('COM_JOOMLEAGUE_SEO_ENTITY_DESCRIPTION', Text::_('COM_JOOMLEAGUE_CLUB_VIEW_TITLE'), $title);
			$seo->apply($this->getDocument(), $title, $url, $description, (string) ($item->logo ?? ''));
			$seo->addStructuredData($this->getDocument(), (new EntitySchemaBuilder())->build('club', $item, $url));
		} else {
			$this->getDocument()->setTitle($title);
		}
		parent::display($tpl);
	}
}
