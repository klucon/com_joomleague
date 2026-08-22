<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultValidationException;

final class MatchresultController extends BaseController
{
	public function apply(): void { $this->saveResult(true); }
	public function save(): void { $this->saveResult(false); }
	public function addSegment(): void { $this->mutateResult('add'); }
	public function removeSegment(): void { $this->mutateResult('remove'); }

	public function cancel(): void
	{
		if (!Session::checkToken()) throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		$matchId = $this->input->getInt('match_id');
		$this->getModel('Matchresult')->clearTransient($matchId);
		$roundId = $this->input->getInt('round_id');
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=matches&round_id=' . $roundId, false));
	}

	private function mutateResult(string $operation): void
	{
		if (!Session::checkToken()) throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		$matchId = $this->input->getInt('match_id');
		$this->assertEditPermission($matchId);
		$data = $this->input->get('jform', [], 'array');
		$locator = $this->input->getString('segment_locator');
		$segmentCode = $this->input->getCmd('segment_code');

		try {
			$this->getModel('Matchresult')->mutateResult($matchId, is_array($data) ? $data : [], $operation, $locator, $segmentCode);
		} catch (MatchResultValidationException $exception) {
			$this->preserveSubmittedResult($matchId, is_array($data) ? $data : []);
			$this->app->enqueueMessage(Text::_($exception->getLanguageKey()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_joomleague&view=matchresult&match_id=' . $matchId, false));
			return;
		} catch (\Throwable $exception) {
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague');
			$this->preserveSubmittedResult($matchId, is_array($data) ? $data : []);
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHRESULT_STRUCTURE_CHANGE_FAILED'), 'error');
		}

		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=matchresult&match_id=' . $matchId, false));
	}

	private function saveResult(bool $apply): void
	{
		if (!Session::checkToken()) throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		$matchId = $this->input->getInt('match_id');
		$this->assertEditPermission($matchId);
		$roundId = $this->input->getInt('round_id');
		$data = $this->input->get('jform', [], 'array');

		try {
			$this->getModel('Matchresult')->saveResult($matchId, is_array($data) ? $data : []);
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHRESULT_SAVE_SUCCESS'), 'success');
		} catch (\Throwable $exception) {
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague');
			try {
				$this->getModel('Matchresult')->preserveResultForm($matchId, is_array($data) ? $data : []);
			} catch (\Throwable $preserveException) {
				Log::add($preserveException->getMessage(), Log::ERROR, 'com_joomleague');
			}
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHRESULT_SAVE_FAILED'), 'error');
			$this->setRedirect(Route::_('index.php?option=com_joomleague&view=matchresult&match_id=' . $matchId, false));
			return;
		}

		$url = $apply
			? 'index.php?option=com_joomleague&view=matchresult&match_id=' . $matchId
			: 'index.php?option=com_joomleague&view=matches&round_id=' . $roundId;
		$this->setRedirect(Route::_($url, false));
	}

	private function assertEditPermission(int $matchId): void
	{
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectId($matchId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		if (!$this->app->getIdentity()->authorise('joomleague.project.edit.results', $asset)) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
	}

	/** @param array<string,mixed> $data */
	private function preserveSubmittedResult(int $matchId, array $data): void
	{
		try {
			$this->getModel('Matchresult')->preserveResultForm($matchId, $data);
		} catch (\Throwable $preserveException) {
			Log::add($preserveException->getMessage(), Log::ERROR, 'com_joomleague');
		}
	}
}
