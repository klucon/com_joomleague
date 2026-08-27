<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\ProjectTemplateResolver;

final class EventreportModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$this->setState('event_id', Factory::getApplication()->getInput()->getInt('event_id', 0));
	}

	/** @return array<string,mixed> */
	public function getItem(): array
	{
		$matchId = (int) $this->getState('event_id');

		if ($matchId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_EVENTREPORT_NOT_CONFIGURED'];
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$item = $db->setQuery(
			$db->getQuery(true)
				->select([
					'match.id', 'match.match_number', 'match.contest_type', 'match.scheduled_start',
					'match.timezone', 'match.duration_minutes', 'match.attendance', 'match.status_code',
					'match.description', 'project.id AS project_id', 'project.name AS project_name',
					'stage.name AS stage_name', 'round.name AS round_name',
					'venue.id AS venue_id', 'venue.name AS venue_name',
					'sporttype.name AS sport_name', 'competition.name AS competition_name', 'season.name AS season_name',
					'result.result_type', 'result.status_code AS result_status', 'result.outcome_code',
					'result.finalized_at', 'result.notes AS result_notes',
				])
				->from($db->quoteName('#__joomleague_project_match', 'match'))
				->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id AND project.published = 1')
				->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
				->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
				->innerJoin($db->quoteName('#__joomleague_sport_type', 'sporttype') . ' ON sporttype.id = project.sport_type_id AND sporttype.published = 1')
				->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id = match.stage_id AND stage.published = 1')
				->innerJoin($db->quoteName('#__joomleague_project_round', 'round') . ' ON round.id = match.round_id AND round.published = 1')
				->leftJoin($db->quoteName('#__joomleague_venue', 'venue') . ' ON venue.id = match.venue_id AND venue.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'venue'))
				->leftJoin($db->quoteName('#__joomleague_match_result', 'result') . ' ON result.match_id = match.id')
				->where('match.id = :matchId')
				->where('match.published = 1')
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'project'))
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'competition'))
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'season'))
				->bind(':matchId', $matchId, ParameterType::INTEGER)
		)->loadObject();

		if (!$item) {
			return ['error' => 'COM_JOOMLEAGUE_EVENTREPORT_UNAVAILABLE'];
		}

		$participants = $db->setQuery(
			$db->getQuery(true)
				->select([
					'participant.id', 'participant.slot_number', 'participant.role_code',
					'participant.participation_status', 'participant.result_status', 'participant.result_rank',
					'entry.id AS entry_id', 'entry.entry_kind', 'entry.display_name', 'entry.team_id', 'entry.person_id',
					'team.name AS team_name', 'person.first_name', 'person.last_name',
				])
				->from($db->quoteName('#__joomleague_match_participant', 'participant'))
				->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = participant.project_entry_id AND entry.published = 1')
				->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'team'))
				->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id AND person.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'person'))
				->where('participant.match_id = :matchId')
				->where('participant.published = 1')
				->where("((entry.entry_kind = 'team' AND team.id IS NOT NULL) OR (entry.entry_kind = 'person' AND person.id IS NOT NULL) OR entry.entry_kind = 'group')")
				->bind(':matchId', $matchId, ParameterType::INTEGER)
				->order('participant.slot_number ASC, participant.id ASC')
		)->loadObjectList();

		foreach ($participants as $participant) {
			$personName = trim((string) $participant->first_name . ' ' . (string) $participant->last_name);
			$participant->name = (string) ($participant->display_name ?: $participant->team_name ?: $personName ?: ('ID ' . $participant->entry_id));
		}

		$segments = [];
		$valuesBySegment = [];
		if ($item->result_status === 'final') {
			$segments = $db->setQuery(
			$db->getQuery(true)
				->select(['segment.id', 'segment.parent_id', 'segment.level_code', 'segment.sequence_number', 'segment.status_code'])
				->from($db->quoteName('#__joomleague_match_score_segment', 'segment'))
				->where('segment.match_id = :matchId')
				->bind(':matchId', $matchId, ParameterType::INTEGER)
				->order('segment.parent_id ASC, segment.sequence_number ASC, segment.id ASC')
			)->loadObjectList();

			$values = $db->setQuery(
			$db->getQuery(true)
				->select(['value.segment_id', 'value.participant_id', 'value.numeric_value', 'value.text_value', 'value.status_code', 'value.result_rank'])
				->from($db->quoteName('#__joomleague_match_score_value', 'value'))
				->where('value.match_id = :matchId')
				->bind(':matchId', $matchId, ParameterType::INTEGER)
			)->loadObjectList();

			foreach ($values as $value) {
				$valuesBySegment[(int) $value->segment_id][(int) $value->participant_id] = $value;
			}
		}

		$events = $db->setQuery(
			$db->getQuery(true)
				->select(['event.match_participant_id', 'event.event_name_key', 'event.primary_name_snapshot', 'event.secondary_name_snapshot', 'event.actor_name_snapshot', 'event.phase_code', 'event.phase_sequence', 'event.clock_value', 'event.clock_unit', 'event.occurred_at', 'event.numeric_value', 'event.text_value', 'event.notes'])
				->from($db->quoteName('#__joomleague_match_event', 'event'))
				->where('event.match_id = :matchId')->where('event.published = 1')
				->bind(':matchId', $matchId, ParameterType::INTEGER)
				->order('event.sequence_number ASC, event.id ASC')
		)->loadObjectList();

		$officials = $db->setQuery(
			$db->getQuery(true)
				->select(['role.role_code', 'role.display_name_snapshot', 'role.notes'])
				->from($db->quoteName('#__joomleague_match_actor_role', 'role'))
				->where('role.match_id = :matchId')->where('role.published = 1')
				->bind(':matchId', $matchId, ParameterType::INTEGER)
				->order('role.ordering ASC, role.id ASC')
		)->loadObjectList();

		$lineup = $db->setQuery(
			$db->getQuery(true)
				->select(['member.match_participant_id', 'member.member_person_type', 'member.role_code', 'member.shirt_number', 'member.lineup_status', 'member.is_captain', 'person.first_name', 'person.last_name'])
				->from($db->quoteName('#__joomleague_match_lineup_member', 'member'))
				->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = member.person_id AND person.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'person'))
				->where('member.match_id = :matchId')->where('member.published = 1')
				->bind(':matchId', $matchId, ParameterType::INTEGER)
				->order('member.match_participant_id ASC, member.ordering ASC, member.id ASC')
		)->loadObjectList();

		foreach ($lineup as $member) {
			$member->name = trim((string) $member->first_name . ' ' . (string) $member->last_name);
		}

		$substitutions = $db->setQuery(
			$db->getQuery(true)
				->select([
					'change.match_participant_id', 'change.change_type', 'change.phase_code',
					'change.phase_sequence', 'change.clock_value', 'change.clock_unit', 'change.notes',
					'out_person.first_name AS outgoing_first_name', 'out_person.last_name AS outgoing_last_name',
					'in_person.first_name AS incoming_first_name', 'in_person.last_name AS incoming_last_name',
				])
				->from($db->quoteName('#__joomleague_match_lineup_change', 'change'))
				->innerJoin($db->quoteName('#__joomleague_match_lineup_member', 'out_member') . ' ON out_member.id = change.outgoing_lineup_member_id')
				->innerJoin($db->quoteName('#__joomleague_person', 'out_person') . ' ON out_person.id = out_member.person_id AND out_person.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'out_person'))
				->innerJoin($db->quoteName('#__joomleague_match_lineup_member', 'in_member') . ' ON in_member.id = change.incoming_lineup_member_id')
				->innerJoin($db->quoteName('#__joomleague_person', 'in_person') . ' ON in_person.id = in_member.person_id AND in_person.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'in_person'))
				->where('change.match_id = :matchId')
				->bind(':matchId', $matchId, ParameterType::INTEGER)
				->order('change.sequence_number ASC, change.id ASC')
		)->loadObjectList();

		foreach ($substitutions as $substitution) {
			$substitution->outgoing_name = trim((string) $substitution->outgoing_first_name . ' ' . (string) $substitution->outgoing_last_name);
			$substitution->incoming_name = trim((string) $substitution->incoming_first_name . ' ' . (string) $substitution->incoming_last_name);
		}

		$statistics = $db->setQuery(
			$db->getQuery(true)
				->select(['statistic.statistic_name_key', 'statistic.abbreviation_key', 'statistic.scope_code', 'statistic.target_name_snapshot', 'statistic.numeric_value', 'statistic.text_value', 'statistic.notes'])
				->from($db->quoteName('#__joomleague_match_statistic_value', 'statistic'))
				->where('statistic.match_id = :matchId')->where('statistic.published = 1')
				->bind(':matchId', $matchId, ParameterType::INTEGER)
				->order('statistic.ordering ASC, statistic.id ASC')
		)->loadObjectList();
		$template = (new ProjectTemplateResolver($db))->resolve((int) $item->project_id, 'eventreport');

		return compact('item', 'participants', 'segments', 'valuesBySegment', 'events', 'officials', 'lineup', 'substitutions', 'statistics', 'template');
	}
}
