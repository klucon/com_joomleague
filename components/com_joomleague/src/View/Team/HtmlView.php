<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Team;

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
	public array $team = [];

	public function display($tpl = null): void
	{
		$this->team = $this->getModel()->getTeam();
		PublicViewGuard::requireItem($this->team, 'team');
		$title = isset($this->team['team'])
			? (string) $this->team['team']->name
			: Text::_('COM_JOOMLEAGUE_TEAM_VIEW_TITLE');
		if (isset($this->team['team'])) {
			$item = $this->team['team'];
			$url = Route::_('index.php?option=com_joomleague&view=team&team_id=' . (int) $item->id, false, Route::TLS_IGNORE, true);
			$image = (string) ($item->logo ?: $item->picture);
			$seo = new SeoMetadata();
			$description = trim((string) ($item->description ?? '')) ?: Text::sprintf('COM_JOOMLEAGUE_SEO_ENTITY_DESCRIPTION', Text::_('COM_JOOMLEAGUE_TEAM_VIEW_TITLE'), $title);
			$seo->apply($this->getDocument(), $title, $url, $description, $image);
			$seo->addStructuredData($this->getDocument(), (new EntitySchemaBuilder())->build('team', $item, $url));
		} else {
			$this->getDocument()->setTitle($title);
		}
		parent::display($tpl);
	}
}
