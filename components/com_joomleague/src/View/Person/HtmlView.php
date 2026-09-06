<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Person;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\EntitySchemaBuilder;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $person = [];

	public function display($tpl = null): void
	{
		$this->person = $this->getModel()->getPerson();
		$title = isset($this->person['person'])
			? trim((string) $this->person['person']->first_name . ' ' . (string) $this->person['person']->last_name)
			: Text::_('COM_JOOMLEAGUE_PERSON_VIEW_TITLE');
		if (isset($this->person['person'])) {
			$item = $this->person['person'];
			$url = Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $item->id, true, Route::TLS_IGNORE, true);
			$seo = new SeoMetadata();
			$seo->apply($this->getDocument(), $title, $url, (string) ($item->description ?? ''), (string) ($item->picture ?? ''), 'profile');
			$seo->addStructuredData($this->getDocument(), (new EntitySchemaBuilder())->build('person', $item, $url));
		} else {
			$this->getDocument()->setTitle($title);
		}
		parent::display($tpl);
	}
}
