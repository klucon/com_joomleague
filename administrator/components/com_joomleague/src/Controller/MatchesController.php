<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Utilities\ArrayHelper;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class MatchesController extends AdminController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_MATCHES';
	public function getModel($name = 'Match', $prefix = 'Administrator', $config = ['ignore_request' => true]) { return parent::getModel($name, $prefix, $config); }
	public function delete(): void { $roundId = $this->input->getInt('round_id'); parent::delete(); if ($roundId > 0) $this->setRedirect('index.php?option=com_joomleague&view=matches&round_id=' . $roundId); }

	public function saveInline(): void
	{
		$app = $this->app;
		try {
			if (!Session::checkToken('post')) throw new \RuntimeException(Text::_('JINVALID_TOKEN'));
			$roundId = $this->input->post->getInt('round_id', 0);
			$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromRound($roundId);
			$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
			if (!$app->getIdentity()->authorise('joomleague.project.edit.schedule', $asset)) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'));
			$model = $this->getModel('Matches', 'Administrator', ['ignore_request' => true]);
			$model->saveInline(
				$this->input->post->getInt('match_id'),
				$this->input->post->getInt('round_id'),
				$this->input->post->get('schedule', [], 'array'),
				(int) $app->getIdentity()->id
			);
			echo new JsonResponse(null, Text::_('COM_JOOMLEAGUE_MATCH_AUTOSAVE_SAVED'));
		} catch (\Throwable $error) {
			echo new JsonResponse(null, $error->getMessage() ?: Text::_('COM_JOOMLEAGUE_MATCH_AUTOSAVE_FAILED'), true);
		}
		$app->close();
	}

	public function batchApply(): void
	{
		$app = $this->app;
		$roundId = $this->input->post->getInt('round_id', 0);
		$redirect = 'index.php?option=com_joomleague&view=matches&round_id=' . $roundId;

		if (!Session::checkToken('post')) {
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			$this->setRedirect(Route::_($redirect, false));
			return;
		}
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromRound($roundId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		if (!$app->getIdentity()->authorise('joomleague.project.edit.schedule', $asset)) {
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$this->setRedirect(Route::_($redirect, false));
			return;
		}

		$ids = ArrayHelper::toInteger($this->input->post->get('cid', [], 'array'));
		if ($ids === []) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHES_BATCH_NONE_SELECTED'), 'warning');
			$this->setRedirect(Route::_($redirect, false));
			return;
		}

		$venueRaw = trim((string) $this->input->post->getString('batch_venue_id', ''));
		$venueId = $venueRaw === '' ? null : (int) $venueRaw;
		$shiftRaw = trim((string) $this->input->post->getString('batch_shift_days', ''));
		$shiftDays = ($shiftRaw !== '' && preg_match('/^-?\d+$/', $shiftRaw) === 1) ? (int) $shiftRaw : null;

		if ($venueId === null && $shiftDays === null) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHES_BATCH_NO_CHANGES'), 'warning');
			$this->setRedirect(Route::_($redirect, false));
			return;
		}

		try {
			$model = $this->getModel('Matches', 'Administrator', ['ignore_request' => true]);
			$result = $model->batchApply($ids, $roundId, $venueId, $shiftDays, (int) $app->getIdentity()->id);
		} catch (\Throwable $error) {
			$app->enqueueMessage($error->getMessage() ?: Text::_('COM_JOOMLEAGUE_MATCHES_BATCH_FAILED'), 'error');
			$this->setRedirect(Route::_($redirect, false));
			return;
		}

		if ($result['skipped'] > 0) {
			$app->enqueueMessage(Text::sprintf('COM_JOOMLEAGUE_MATCHES_BATCH_RESULT_WITH_SKIPPED', $result['applied'], $result['skipped']), 'warning');
		} else {
			$app->enqueueMessage(Text::sprintf('COM_JOOMLEAGUE_MATCHES_BATCH_RESULT', $result['applied']), 'success');
		}
		$this->setRedirect(Route::_($redirect, false));
	}
}
