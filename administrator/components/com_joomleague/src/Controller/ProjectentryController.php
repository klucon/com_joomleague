<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class ProjectentryController extends FormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_PROJECTENTRY';
	protected $view_list = 'projectentries';

	protected function allowAdd($data = []): bool
	{
		$projectId = (int) ($data['project_id'] ?? $this->input->getInt('project_id', 0));
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		return $this->app->getIdentity()->authorise('core.create', $asset);
	}

	protected function allowEdit($data = [], $key = 'id'): bool
	{
		$projectId = (int) ($data['project_id'] ?? 0);
		if ($projectId < 1) {
			$id = (int) ($data[$key] ?? 0);
			$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromEntry($id);
		}
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		return $this->app->getIdentity()->authorise('core.edit', $asset);
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id'): string
	{
		return parent::getRedirectToItemAppend($recordId, $urlVar) . $this->projectAppend();
	}

	protected function getRedirectToListAppend(): string
	{
		return parent::getRedirectToListAppend() . $this->projectAppend();
	}

	private function projectAppend(): string
	{
		$projectId = $this->input->getInt('project_id');

		return $projectId > 0 ? '&project_id=' . $projectId : '';
	}
}
