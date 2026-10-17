<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class MatchController extends FormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_MATCH'; protected $view_list = 'matches';

	protected function allowAdd($data = []): bool
	{
		$form = $this->input->get('jform', [], 'array');
		$roundId = (int) ($data['round_id'] ?? $form['round_id'] ?? $this->input->getInt('round_id', 0));
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromRound($roundId);
		if ($projectId < 1) return false;
		$asset = 'com_joomleague.project.' . $projectId;
		return $this->app->getIdentity()->authorise('joomleague.project.edit.schedule', $asset);
	}

	protected function allowEdit($data = [], $key = 'id'): bool
	{
		$matchId = (int) ($data[$key] ?? 0);
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectId($matchId);
		if ($projectId < 1) return false;
		$asset = 'com_joomleague.project.' . $projectId;
		return $this->app->getIdentity()->authorise('joomleague.project.edit.schedule', $asset);
	}
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id'): string { return parent::getRedirectToItemAppend($recordId, $urlVar) . $this->roundAppend(); }
	protected function getRedirectToListAppend(): string { return parent::getRedirectToListAppend() . $this->roundAppend(); }
	private function roundAppend(): string { $form = $this->input->get('jform', [], 'array'); $roundId = $this->input->getInt('round_id') ?: (int) ($form['round_id'] ?? 0); return $roundId > 0 ? '&round_id=' . $roundId : ''; }
}
