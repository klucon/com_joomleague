<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Eventreport;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomleague\Component\Joomleague\Site\Service\EventSchemaBuilder;

class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $programItem = [];

	public function display($tpl = null): void
	{
		$this->programItem = $this->getModel()->getItem();
		$title = Text::_('COM_JOOMLEAGUE_EVENTREPORT_VIEW_TITLE');
		if (isset($this->programItem['item'], $this->programItem['participants'])) {
			$names = array_values(array_filter(array_map(static fn (object $participant): string => trim((string) ($participant->name ?? '')), $this->programItem['participants'])));
			$title = count($names) >= 2 && count($names) <= 4 ? implode(' – ', $names) : (string) $this->programItem['item']->round_name;
			$eventUrl = rtrim(Uri::root(), '/') . '/' . ltrim(Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $this->programItem['item']->id, false), '/');
			$schema = (new EventSchemaBuilder())->build($this->programItem, $eventUrl);
			if ($schema !== []) {
				$this->getDocument()->addCustomTag('<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR) . '</script>');
			}
		}
		$this->getDocument()->setTitle($title);
		parent::display($tpl);
	}
}
