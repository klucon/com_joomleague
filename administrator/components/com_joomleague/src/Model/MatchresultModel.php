<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultEditorContext;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultFormPayloadBuilder;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultFormStateBuilder;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultFormStateMutator;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultPayloadValidator;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository;
use Joomleague\Component\Joomleague\Administrator\Service\StandingsCascadeTrigger;

final class MatchresultModel extends BaseDatabaseModel
{
	/** @return array<string,mixed> */
	public function getContext(int $matchId): array
	{
		$repository = new MatchResultRepository($this->getDatabase());
		$context = (new MatchResultEditorContext($this->getDatabase(), $repository))->get($matchId);
		$transient = Factory::getApplication()->getUserState($this->stateKey($matchId));

		if (is_array($transient)) {
			$context['form_state'] = (new MatchResultFormStateBuilder())->build($context['editor_schema'], $context['participants'], $transient);
		}

		return $context;
	}

	/** @param array<string,mixed> $data */
	public function saveResult(int $matchId, array $data): int
	{
		$repository = new MatchResultRepository($this->getDatabase());
		$context = (new MatchResultEditorContext($this->getDatabase(), $repository))->get($matchId);
		$payload = (new MatchResultFormPayloadBuilder())->build($data, $context['editor_schema']);
		if (($payload['status_code'] ?? null) === 'final' && $payload['finalized_at'] === null) $payload['finalized_at'] = Factory::getDate()->toSql();
		$actorId = (int) Factory::getApplication()->getIdentity()->id;
		$resultId = $repository->replace($matchId, $payload, $actorId);
		$this->clearTransient($matchId);
		$this->cascadeStandings($matchId, $actorId);
		return $resultId;
	}

	private function cascadeStandings(int $matchId, int $actorId): void
	{
		try {
			$matchContext = (new MatchProjectResolver($this->getDatabase()))->resolveMatchContext($matchId);
			(new StandingsCascadeTrigger($this->getDatabase()))->trigger($matchContext['project_id'], $matchContext['stage_id'], $actorId);
		} catch (\Throwable $exception) {
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague.standings');
		}
	}

	/** @param array<string,mixed> $data */
	public function mutateResult(int $matchId, array $data, string $operation, string $locator, string $segmentCode = ''): void
	{
		[$context, $payload] = $this->normalizeDraft($matchId, $data);
		$mutator = new MatchResultFormStateMutator();
		$payload = $operation === 'add'
			? $mutator->add($payload, $context['editor_schema'], $locator, $segmentCode)
			: $mutator->remove($payload, $context['editor_schema'], $locator);
		Factory::getApplication()->setUserState($this->stateKey($matchId), $payload);
	}

	/** @param array<string,mixed> $data */
	public function preserveResultForm(int $matchId, array $data): void
	{
		[, $payload] = $this->normalizeDraft($matchId, $data);
		Factory::getApplication()->setUserState($this->stateKey($matchId), $payload);
	}

	public function clearTransient(int $matchId): void
	{
		Factory::getApplication()->setUserState($this->stateKey($matchId), null);
	}

	private function stateKey(int $matchId): string
	{
		return 'com_joomleague.edit.matchresult.' . $matchId . '.data';
	}

	/** @param array<string,mixed> $data @return array{0:array<string,mixed>,1:array<string,mixed>} */
	private function normalizeDraft(int $matchId, array $data): array
	{
		$context = (new MatchResultEditorContext($this->getDatabase(), new MatchResultRepository($this->getDatabase())))->get($matchId);
		$payload = (new MatchResultFormPayloadBuilder())->build($data, $context['editor_schema']);
		$status = (string) ($payload['status_code'] ?? 'draft');
		$payload['status_code'] = 'draft';
		$participantIds = array_map(static fn (array $participant): int => (int) $participant['id'], $context['participants']);
		$payload = (new MatchResultPayloadValidator())->validate($context['profile_payload'], $participantIds, $payload);

		if (!in_array($status, $context['editor_schema']['statuses'], true)) throw new \InvalidArgumentException('Result status is not supported by the sport profile.');

		$payload['status_code'] = $status;
		return [$context, $payload];
	}
}
