<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Service;
defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

/** Called only inside the parent result write transaction and project ACL. */
final class TeamEncounterRepository
{
    public function __construct(private readonly DatabaseInterface $db) {}

    public function roster(int $matchId, array $contract, bool $lock = false): array
    {
        $q = $this->db->getQuery(true)->select(['member.id', 'member.match_id', 'member.match_participant_id', 'member.person_id', 'member.published', 'person.first_name', 'person.last_name', 'member.member_person_type'])
            ->from($this->db->quoteName('#__joomleague_match_lineup_member', 'member'))
            ->innerJoin($this->db->quoteName('#__joomleague_person', 'person') . ' ON person.id = member.person_id')
            ->where('member.match_id = :match')->where('member.published = 1')->where('person.published = 1')
            ->order('member.match_participant_id, person.last_name, person.first_name, member.id')->bind(':match', $matchId, ParameterType::INTEGER);
        if ($lock) $this->db->setQuery('SELECT id FROM ' . $this->db->quoteName('#__joomleague_match_lineup_member') . ' WHERE match_id = ' . (int) $matchId . ' FOR UPDATE')->loadColumn();
        $rows = $this->db->setQuery($q)->loadAssocList();
        return array_values(array_filter($rows, static fn(array $r): bool => in_array($r['member_person_type'], $contract['member_person_types'], true)));
    }

    public function get(int $matchId): array
    {
        $q = $this->db->getQuery(true)->select(['duel.id', 'duel.sequence_number', 'duel.format_code', 'member.match_participant_id', 'member.lineup_member_id'])
            ->from($this->db->quoteName('#__joomleague_match_duel', 'duel'))
            ->leftJoin($this->db->quoteName('#__joomleague_match_duel_member', 'member') . ' ON member.duel_id = duel.id')
            ->where('duel.match_id = :match')->order('duel.sequence_number, member.match_participant_id, member.ordering')->bind(':match', $matchId, ParameterType::INTEGER);
        $duels = [];
        foreach ($this->db->setQuery($q)->loadAssocList() as $row) {
            $seq = (int) $row['sequence_number'];
            $duels[$seq] ??= ['sequence_number' => $seq, 'format_code' => $row['format_code'], 'sides' => []];
            if ($row['lineup_member_id'] !== null) {
                $team = (int) $row['match_participant_id'];
                $duels[$seq]['sides'][$team] ??= ['participant_id' => $team, 'lineup_member_ids' => []];
                $duels[$seq]['sides'][$team]['lineup_member_ids'][] = (int) $row['lineup_member_id'];
            }
        }
        foreach ($duels as &$duel) $duel['sides'] = array_values($duel['sides']);
        return array_values($duels);
    }

    public function revision(int $matchId): string
    {
        $q = $this->db->getQuery(true)->select('uuid')->from($this->db->quoteName('#__joomleague_match_encounter_revision'))->where('match_id = :match')->order('id DESC')->bind(':match', $matchId, ParameterType::INTEGER);
        return (string) ($this->db->setQuery($q, 0, 1)->loadResult() ?? 'new');
    }

    public function replace(int $matchId, array $participants, array $contract, array $payload, array &$result, int $actorId): void
    {
        if (!is_string($payload['encounter_revision'] ?? null) || !hash_equals($this->revision($matchId), $payload['encounter_revision'])) throw new MatchResultValidationException('COM_JOOMLEAGUE_ENCOUNTER_ERROR_STALE');
        $roster = $this->roster($matchId, $contract, true);
        try { $duels = (new TeamEncounterRosterValidator())->validate($matchId, $participants, $roster, $contract['formats'], $payload['duels'] ?? []); }
        catch (\InvalidArgumentException $e) { throw new MatchResultValidationException('COM_JOOMLEAGUE_ENCOUNTER_ERROR_ROSTER'); }
        (new EncounterPenaltyNominations())->validate($matchId, $participants, $roster, $contract, $result['segments'][0], $duels);
        $expected = array_column(array_filter($result['segments'][0]['children'], static fn (array $child): bool => $child['level_code'] === 'duel'), 'sequence_number');
        $actual = array_column($duels, 'sequence_number');
        sort($expected); sort($actual);
        if ($expected !== $actual) throw new MatchResultValidationException('COM_JOOMLEAGUE_ENCOUNTER_ERROR_ROSTER');
        $q = $this->db->getQuery(true)->select(['id', 'sequence_number'])->from($this->db->quoteName('#__joomleague_match_duel'))->where('match_id = :match')->bind(':match', $matchId, ParameterType::INTEGER);
        $old = $this->db->setQuery($q)->loadAssocList('sequence_number');
        foreach ($duels as $duel) {
            $seq = $duel['sequence_number'];
            $id = isset($old[$seq]) ? (int) $old[$seq]['id'] : 0;
            if (!$id) $id = $this->insert('#__joomleague_match_duel', ['uuid' => UuidFactory::v4(), 'match_id' => $matchId, 'sequence_number' => $seq, 'format_code' => $duel['format_code']]);
            else {
                $format = $duel['format_code'];
                $q = $this->db->getQuery(true)->update($this->db->quoteName('#__joomleague_match_duel'))->set('format_code = :format')->where('id = :id')->bind(':format', $format)->bind(':id', $id, ParameterType::INTEGER);
                $this->db->setQuery($q)->execute();
            }
            $q = $this->db->getQuery(true)->delete($this->db->quoteName('#__joomleague_match_duel_member'))->where('duel_id = :id')->bind(':id', $id, ParameterType::INTEGER);
            $this->db->setQuery($q)->execute();
            foreach ($duel['sides'] as $side) foreach ($side['lineup_member_ids'] as $i => $member) $this->insert('#__joomleague_match_duel_member', ['duel_id' => $id, 'match_id' => $matchId, 'match_participant_id' => $side['participant_id'], 'lineup_member_id' => $member, 'ordering' => $i + 1], false);
            unset($old[$seq]);
        }
        foreach ($old as $row) {
            $id = (int) $row['id'];
            $q = $this->db->getQuery(true)->delete($this->db->quoteName('#__joomleague_match_duel'))->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
            $this->db->setQuery($q)->execute();
        }
        $snapshot = ['result' => $result, 'duels' => $duels];
        $this->insert('#__joomleague_match_encounter_revision', ['uuid' => UuidFactory::v4(), 'match_id' => $matchId, 'payload_json' => CanonicalJson::encodeObject($snapshot), 'checksum' => CanonicalJson::checksum($snapshot), 'created_by' => $actorId]);
    }

    private function insert(string $table, array $data, bool $identity = true): int
    {
        $q = $this->db->getQuery(true)->insert($this->db->quoteName($table))->columns($this->db->quoteName(array_keys($data)));
        $holders = [];
        foreach ($data as $key => &$value) { $holders[] = ':' . $key; $q->bind(':' . $key, $value); }
        unset($value);
        $q->values(implode(',', $holders));
        $this->db->setQuery($q)->execute();
        return $identity ? (int) $this->db->insertid() : 0;
    }
}
