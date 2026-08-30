<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class StandingadjustmentController extends FormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_STANDING_ADJUSTMENT';
	protected $view_list = 'standingadjustments';

	protected function allowAdd($data = []): bool
	{
		$projectId = (int) ($data['project_id'] ?? $this->input->getInt('project_id', 0));
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		return $this->app->getIdentity()->authorise('joomleague.project.edit.results', $asset);
	}

	protected function allowEdit($data = [], $key = 'id'): bool
	{
		$projectId = (int) ($data['project_id'] ?? 0);
		if ($projectId < 1) {
			$id = (int) ($data[$key] ?? 0);
			$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromStandingAdjustment($id);
		}
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		return $this->app->getIdentity()->authorise('joomleague.project.edit.results', $asset);
	}
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id'): string { return parent::getRedirectToItemAppend($recordId, $urlVar) . $this->contextAppend(); }
	protected function getRedirectToListAppend(): string { return parent::getRedirectToListAppend() . $this->contextAppend(); }
	private function contextAppend(): string
	{
		$form = $this->input->get('jform', [], 'array'); $projectId = $this->input->getInt('project_id') ?: (int) ($form['project_id'] ?? 0); $stageId = $this->input->getInt('stage_id') ?: (int) ($form['stage_id'] ?? 0);
		return ($projectId > 0 ? '&project_id=' . $projectId : '') . ($stageId > 0 ? '&stage_id=' . $stageId : '');
	}
}
