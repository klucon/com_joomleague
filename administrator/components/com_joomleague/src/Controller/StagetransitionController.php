<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

final class StagetransitionController extends FormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_STAGE_TRANSITION'; protected $view_list = 'stagetransitions';
	protected function allowAdd($data = []): bool { return $this->app->getIdentity()->authorise('joomleague.project.run.transitions', $this->transitionAsset($data)); }
	protected function allowEdit($data = [], $key = 'id'): bool { return $this->app->getIdentity()->authorise('joomleague.project.run.transitions', $this->transitionAsset($data)); }
	private function transitionAsset($data): string { $projectId = (int) ($data['project_id'] ?? $this->input->getInt('project_id')); return $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague'; }
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id'): string { return parent::getRedirectToItemAppend($recordId, $urlVar) . $this->projectAppend(); }
	protected function getRedirectToListAppend(): string { return parent::getRedirectToListAppend() . $this->projectAppend(); }
	private function projectAppend(): string { $form = $this->input->get('jform', [], 'array'); $id = $this->input->getInt('project_id') ?: (int) ($form['project_id'] ?? 0); return $id > 0 ? '&project_id=' . $id : ''; }
}
