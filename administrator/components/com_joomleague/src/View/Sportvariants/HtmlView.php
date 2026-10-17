<?php

declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\View\Sportvariants;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomleague\Component\Joomleague\Administrator\Service\SportVariantRules;

final class HtmlView extends BaseHtmlView
{
	public array $items = [];
	public array $profiles = [];
	public ?array $selected = null;
	public array $bases = [];
	public array $revisions = [];
	public array $fields = [];
	public array $draft = [];
	public array $createDraft = [];
	public int $baseId = 0;

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		if (!$app->getIdentity()->authorise('core.manage', 'com_joomleague')) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		$service = $this->getModel()->service();
		$this->items = $service->variants();
		foreach ($service->bases() as $base) $this->profiles[(int) $base['profile_id']] = $base['name_key'];
		$this->createDraft = $app->getUserState('com_joomleague.sportvariant.create', []);
		$this->draft = $app->getUserState('com_joomleague.sportvariant.revision', []);
		$app->setUserState('com_joomleague.sportvariant.create', null);
		$app->setUserState('com_joomleague.sportvariant.revision', null);
		$id = $app->getInput()->getInt('id');
		if ($id > 0) {
			foreach ($this->items as $item) if ((int) $item['id'] === $id) $this->selected = $item;
			if ($this->selected === null) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_SPORTVARIANT_MISSING'), 404);
			$this->bases = $service->bases($id);
			$this->revisions = $service->revisions($id);
			$this->baseId = $app->getInput()->getInt('base_id', (int) ($this->bases[0]['id'] ?? 0));
			if ($this->draft !== [] && (int) ($this->draft['variant_id'] ?? 0) === $id) $this->baseId = (int) ($this->draft['base_id'] ?? 0);
			else $this->draft = [];
			if (!in_array($this->baseId, array_map(static fn (array $base): int => (int) $base['id'], $this->bases), true)) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_SPORTVARIANT_MISSING'), 404);
			$this->fields = (new SportVariantRules())->fields($service->base($id, $this->baseId));
		}
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_SPORTVARIANTS_TITLE'), 'options');
		parent::display($tpl);
	}
}
