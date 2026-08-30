<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class RoundController extends FormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_ROUND'; protected $view_list = 'rounds';

	protected function allowAdd($data = []): bool
	{
		$projectId = (int) ($data['project_id'] ?? $this->input->getInt('project_id', 0));
		if ($projectId < 1) {
			$stageId = (int) ($data['stage_id'] ?? $this->input->getInt('stage_id', 0));
			$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromStage($stageId);
		}
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		return $this->app->getIdentity()->authorise('joomleague.project.edit.schedule', $asset);
	}

	protected function allowEdit($data = [], $key = 'id'): bool
	{
		$projectId = (int) ($data['project_id'] ?? 0);
		if ($projectId < 1) {
			$id = (int) ($data[$key] ?? 0);
			$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromRound($id);
		}
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		return $this->app->getIdentity()->authorise('joomleague.project.edit.schedule', $asset);
	}
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id'): string { return parent::getRedirectToItemAppend($recordId, $urlVar) . $this->stageAppend(); }
	protected function getRedirectToListAppend(): string { return parent::getRedirectToListAppend() . $this->stageAppend(); }
	private function stageAppend(): string { $form = $this->input->get('jform', [], 'array'); $stageId = $this->input->getInt('stage_id') ?: (int) ($form['stage_id'] ?? 0); return $stageId > 0 ? '&stage_id=' . $stageId : ''; }
}
