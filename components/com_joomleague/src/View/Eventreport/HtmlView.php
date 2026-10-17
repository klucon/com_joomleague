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
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;

class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $programItem = [];
	/** @var array<string,mixed> */
	public array $templateConfig = [];

	public function display($tpl = null): void
	{
		$this->programItem = $this->getModel()->getItem();
		PublicViewGuard::requireItem($this->programItem, 'item');
		$title = Text::_('COM_JOOMLEAGUE_EVENTREPORT_VIEW_TITLE');
		if (isset($this->programItem['item'], $this->programItem['participants'])) {
			$provider = new ProjectTemplateProvider(Factory::getContainer()->get(DatabaseInterface::class));
			$projectId = (int) $this->programItem['item']->project_id;
			$this->templateConfig = $provider->supports($projectId, 'eventreport') ? $provider->resolve($projectId, 'eventreport') : [];
			$names = array_values(array_filter(array_map(static fn (object $participant): string => trim((string) ($participant->name ?? '')), $this->programItem['participants'])));
			$eventName = count($names) >= 2 && count($names) <= 4 ? implode(' - ', $names) : (string) $this->programItem['item']->round_name;
			$context = (string) ($this->programItem['item']->round_name ?: $this->programItem['item']->scheduled_start ?: $this->programItem['item']->id);
			if ($this->programItem['item']->scheduled_start) {
				$context .= ' - ' . Factory::getDate($this->programItem['item']->scheduled_start, 'UTC')->format('Y-m-d');
			}
			$title = Text::sprintf('COM_JOOMLEAGUE_EVENTREPORT_PAGE_TITLE', $eventName, $context, $this->programItem['item']->project_name);
			$eventUrl = Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $this->programItem['item']->id, false, Route::TLS_IGNORE, true);
			foreach ($this->programItem['participants'] as $participant) {
				if ((int) ($participant->team_id ?? 0) > 0) {
					$participant->schema_url = Route::_('index.php?option=com_joomleague&view=team&team_id=' . (int) $participant->team_id, false, Route::TLS_IGNORE, true);
				} elseif ((int) ($participant->person_id ?? 0) > 0) {
					$participant->schema_url = Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $participant->person_id, false, Route::TLS_IGNORE, true);
				}
			}
			if ((int) ($this->programItem['item']->venue_id ?? 0) > 0) {
				$this->programItem['item']->venue_url = Route::_('index.php?option=com_joomleague&view=venue&venue_id=' . (int) $this->programItem['item']->venue_id, false, Route::TLS_IGNORE, true);
			}
			$seo = new SeoMetadata();
			$description = trim((string) ($this->programItem['item']->description ?? ''));
			if ($description === '') {
				$description = Text::sprintf('COM_JOOMLEAGUE_EVENTREPORT_META_DESCRIPTION', $eventName, $this->programItem['item']->project_name, $context);
			}
			$seo->apply($this->getDocument(), $title, $eventUrl, $description);
			$schema = ($this->templateConfig['show_schema_org'] ?? true) ? (new EventSchemaBuilder())->build($this->programItem, $eventUrl) : [];
			$seo->addStructuredData($this->getDocument(), $schema);
		} else {
			$this->getDocument()->setTitle($title);
		}
		parent::display($tpl);
	}
}
