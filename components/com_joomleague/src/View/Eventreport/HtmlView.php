<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Eventreport;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Site\Service\EventSchemaBuilder;
use Joomleague\Component\Joomleague\Site\Service\ProjectTemplateProvider;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $programItem = [];
	/** @var array<string,mixed> */
	public array $templateConfig = [];

	public function display($tpl = null): void
	{
		$this->programItem = $this->getModel()->getItem();
		$title = Text::_('COM_JOOMLEAGUE_EVENTREPORT_VIEW_TITLE');
		if (isset($this->programItem['item'], $this->programItem['participants'])) {
			$provider = new ProjectTemplateProvider(Factory::getContainer()->get(DatabaseInterface::class));
			$projectId = (int) $this->programItem['item']->project_id;
			$this->templateConfig = $provider->supports($projectId, 'eventreport') ? $provider->resolve($projectId, 'eventreport') : [];
			$names = array_values(array_filter(array_map(static fn (object $participant): string => trim((string) ($participant->name ?? '')), $this->programItem['participants'])));
			$title = count($names) >= 2 && count($names) <= 4 ? implode(' – ', $names) : (string) $this->programItem['item']->round_name;
			$eventUrl = Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $this->programItem['item']->id, true, Route::TLS_IGNORE, true);
			$seo = new SeoMetadata();
			$seo->apply($this->getDocument(), $title, $eventUrl, (string) ($this->programItem['item']->description ?? ''));
			$schema = ($this->templateConfig['show_schema_org'] ?? true) ? (new EventSchemaBuilder())->build($this->programItem, $eventUrl) : [];
			$seo->addStructuredData($this->getDocument(), $schema);
		} else {
			$this->getDocument()->setTitle($title);
		}
		parent::display($tpl);
	}
}
