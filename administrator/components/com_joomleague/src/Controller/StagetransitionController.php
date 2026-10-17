<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class StagetransitionController extends FormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_STAGE_TRANSITION'; protected $view_list = 'stagetransitions';
	protected function allowAdd($data = []): bool
	{
		$projectId = (int) ($data['project_id'] ?? $this->input->getInt('project_id', 0));
		return $projectId > 0 && $this->app->getIdentity()->authorise('joomleague.project.run.transitions', 'com_joomleague.project.' . $projectId);
	}
	protected function allowEdit($data = [], $key = 'id'): bool
	{
		$id = (int) ($data[$key] ?? 0);
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromTransition($id);
		return $projectId > 0 && $this->app->getIdentity()->authorise('joomleague.project.run.transitions', 'com_joomleague.project.' . $projectId);
	}
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id'): string { return parent::getRedirectToItemAppend($recordId, $urlVar) . $this->projectAppend(); }
	protected function getRedirectToListAppend(): string { return parent::getRedirectToListAppend() . $this->projectAppend(); }
	private function projectAppend(): string { $form = $this->input->get('jform', [], 'array'); $id = $this->input->getInt('project_id') ?: (int) ($form['project_id'] ?? 0); return $id > 0 ? '&project_id=' . $id : ''; }
}
