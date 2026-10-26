<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Service;
defined('_JEXEC') or die;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal;

/** Revision-guarded repair using verified original migration metadata. */
final class LegacyFootballResultRepair
{
    private array $splitModes = [];
    public function __construct(private readonly DatabaseInterface $db) {}

    public function repairImportedResults(User $actor): array
    {
        $report = ['repaired' => [], 'skipped' => [], 'quarantined' => []];
        $lastId = 0;
        do {
            $rows = $this->db->setQuery($this->db->getQuery(true)
                ->select(['id', 'metadata_json'])->from($this->db->quoteName('#__joomleague_project_match'))
                ->where('id > ' . $lastId)->order('id ASC'), 0, 200)->loadAssocList();
            foreach ($rows as $row) {
                $lastId = (int) $row['id'];
                $source = json_decode((string) ($row['metadata_json'] ?? ''), true)['legacy'] ?? null;
                if (!is_array($source)) continue;
                if (!LegacyFootballResultMapping::affected($source)) continue;
                $plan = $this->preview($lastId, $actor, true);
                if ($plan['status'] === 'already_repaired') continue;
                if ($plan['status'] !== 'candidate') {
                    $report['skipped'][$lastId] = $plan['reason'] ?? $plan['status'];
                    continue;
                }
                $this->apply($lastId, $plan['revision'], $actor, true);
                if (isset($plan['review_reason'])) {
                    $report['quarantined'][] = $lastId;
                    $report['skipped'][$lastId] = 'legacy_result_requires_review: ' . $plan['review_reason'];
                } else $report['repaired'][] = $lastId;
            }
        } while (count($rows) === 200);
        return $report;
    }

    public function preview(int $matchId, User $actor, bool $overwriteResult = false): array
    {
        if ((int) $actor->id < 1 || $actor->block || !$actor->authorise('core.admin', 'com_joomleague')) throw new \RuntimeException('Active component administrator required.');
        if ($matchId < 1) throw new \InvalidArgumentException('Invalid match ID.');
        $row = $this->db->setQuery($this->db->getQuery(true)->select(['m.*', 'p.profile_version_id', 'p.metadata_json AS project_metadata_json', 'v.payload_json', 'v.payload_checksum'])
            ->from($this->db->quoteName('#__joomleague_project_match', 'm'))
            ->innerJoin($this->db->quoteName('#__joomleague_project', 'p') . ' ON p.id=m.project_id')
            ->innerJoin($this->db->quoteName('#__joomleague_sport_profile_version', 'v') . ' ON v.id=p.profile_version_id')
            ->where('m.id=' . $matchId))->loadAssoc();
        if (!$row) throw new \RuntimeException('Match not found.');
        $current = (new MatchResultRepository($this->db))->get($matchId);
        $resultRow = $this->db->setQuery('SELECT * FROM ' . $this->db->quoteName('#__joomleague_match_result') . ' WHERE match_id=' . $matchId)->loadAssoc();
        $participants = $this->db->setQuery('SELECT * FROM ' . $this->db->quoteName('#__joomleague_match_participant') . ' WHERE match_id=' . $matchId . ' ORDER BY slot_number,id')->loadAssocList();
        $profile = json_decode((string) $row['payload_json'], true, 512, JSON_THROW_ON_ERROR);
        $profile = (new ProjectRuleConfigRepository($this->db))->resolve((int) $row['project_id'], $profile);
        $profile = (new StageEncounterRules($this->db))->resolve($profile, (int) $row['project_id'], (int) $row['stage_id']);
        $revision = CanonicalJson::checksum(['match'=>$row, 'result'=>$resultRow, 'payload'=>$current, 'participants'=>$participants, 'profile'=>$profile]);
        $report = ['match_id' => $matchId, 'revision' => $revision, 'status' => 'conflict'];
        $source = $current['metadata']['legacy'] ?? null;
        $matchSource = json_decode((string) ($row['metadata_json'] ?? ''), true)['legacy'] ?? null;
        if (!is_array($source) || !is_array($matchSource) || CanonicalJson::checksum($source) !== CanonicalJson::checksum($matchSource) || (int) ($source['id'] ?? 0) !== $matchId) return $report + ['reason' => 'unverified_source'];
        if (!LegacyFootballResultMapping::affected($source)) return array_replace($report, ['status' => 'not_affected']);
        if (isset($current['metadata']['legacy_extended_repair'])) return array_replace($report, ['status' => 'already_repaired']);
        if (($profile['code'] ?? '') !== 'football') return $report + ['reason' => 'unsupported_profile'];
        if ($row['status_code'] !== 'completed' || count($participants) !== 2 || (!$overwriteResult && ((int) $row['modified_by'] !== 0 || (int) ($resultRow['created_by'] ?? 0) !== 0 || (int) ($resultRow['modified_by'] ?? 0) !== 0 || ($resultRow['modified'] ?? null) !== null || ($current['notes'] ?? null) !== null || ($current['finalized_at'] ?? null) !== null || ($current['metadata']['legacy_aggregate_only'] ?? false) !== true || ($current['status_code'] ?? '') !== 'final'))) return $report + ['reason' => 'edited_or_nonfinal'];
        $root = $current['segments'][0] ?? [];
        if (!$overwriteResult && (($root['children'] ?? []) !== [] || count($root['values'] ?? []) !== 2 || (int) $root['sequence_number'] !== 1)) return $report + ['reason' => 'existing_breakdown'];
        foreach (['match_event', 'match_statistic_value'] as $table) {
            $references = $this->db->setQuery('SELECT COUNT(*) FROM ' . $this->db->quoteName('#__joomleague_' . $table) . ' r INNER JOIN ' . $this->db->quoteName('#__joomleague_match_score_segment') . ' s ON s.id=r.score_segment_id WHERE s.match_id=' . $matchId)->loadResult();
            if ((int) $references !== 0) return $report + ['reason' => 'referenced_score_segments'];
        }
        $values = array_column($root['values'], null, 'participant_id');
        foreach ($participants as $offset => $participant) {
            $slot = $offset + 1; $value = $values[(int) $participant['id']] ?? [];
            if ((int) $participant['published'] !== 1 || $participant['role_code'] !== ($slot === 1 ? 'home' : 'away')) return $report + ['reason' => 'changed_participant_state'];
            if ((int) $participant['slot_number'] !== $slot || (int) $participant['project_entry_id'] !== (int) ($source['projectteam' . $slot . '_id'] ?? 0)) return $report + ['reason' => 'changed_participants'];
            if ($overwriteResult) continue;
            if ((int) $participant['slot_number'] !== $slot || (int) $participant['project_entry_id'] !== (int) ($source['projectteam' . $slot . '_id'] ?? 0) || !isset($value['numeric_value']) || ($value['text_value'] ?? null) !== null || ($value['status_code'] ?? null) !== null || ($value['result_rank'] ?? null) !== null || ($value['metadata'] ?? []) !== [] || StandingsDecimal::compare((string) $value['numeric_value'], $this->score($source['team' . $slot . '_result'] ?? null)) !== 0) return $report + ['reason' => 'changed_score_or_participants'];
        }
        $context = $this->splitContext($row, $source);
        $report['revision'] = CanonicalJson::checksum(['state'=>$revision, 'split_context'=>$context]);
        try { $payload = $this->payload($current, $participants, $source, $context); }
        catch (\InvalidArgumentException $error) {
            if (!$overwriteResult) return $report + ['reason' => $error->getMessage()];
            // Preserve the original payload for an administrator, but never let
            // an unverified imported score remain a published final result.
            $payload = $current;
            $payload['status_code'] = 'draft';
            $payload['outcome_code'] = null;
            $payload['finalized_at'] = null;
            $payload['metadata']['legacy_result_issue'] = ['code'=>'legacy_result_requires_review', 'reason'=>$error->getMessage()];
            $payload['metadata']['legacy_extended_repair'] = ['version'=>2,'source_checksum'=>CanonicalJson::checksum($source),'previous_result_checksum'=>CanonicalJson::checksum($current),'requires_review'=>true];
            $report['review_reason'] = $error->getMessage();
        }
        (new MatchResultPayloadValidator())->validate($profile, array_map(static fn(array $p): int => (int) $p['id'], $participants), $payload);
        return array_replace($report, ['status' => 'candidate', 'payload' => $payload]);
    }

    public function apply(int $matchId, string $expectedRevision, User $actor, bool $overwriteResult = false): int
    {
        if (preg_match('/^[a-f0-9]{64}$/D', $expectedRevision) !== 1) throw new \InvalidArgumentException('Expected preview revision required.');
        $plan = $this->preview($matchId, $actor, $overwriteResult);
        if ($plan['status'] !== 'candidate' || !hash_equals($plan['revision'], $expectedRevision)) throw new \RuntimeException('Repair conflict; obtain a new preview.');
        $resultId = (new MatchResultRepository($this->db))->replace($matchId, $plan['payload'], (int) $actor->id, function (array $match) use ($matchId, $expectedRevision, $actor, $overwriteResult): void {
            // Executed after the native repository locks the project and match.
            $locked = $this->preview($matchId, $actor, $overwriteResult);
            if ($locked['status'] !== 'candidate' || !hash_equals($expectedRevision, $locked['revision'])) throw new \RuntimeException('Result changed after preview.');
            if ((int) $match['checked_out'] !== 0 && (int) $match['checked_out'] !== (int) $actor->id) throw new \RuntimeException('Match is checked out by another user.');
            $projectId = (int) $match['project_id'];
            $this->db->setQuery('UPDATE ' . $this->db->quoteName('#__joomleague_standing_freshness') . ' SET is_dirty=1 WHERE project_id=' . $projectId)->execute();
            $this->db->setQuery('DELETE FROM ' . $this->db->quoteName('#__joomleague_standing_current') . ' WHERE project_id=' . $projectId)->execute();
        });
        $context = (new MatchProjectResolver($this->db))->resolveMatchContext($matchId);
        (new StandingsCascadeTrigger($this->db))->trigger($context['project_id'], $context['stage_id'], (int) $actor->id);
        return $resultId;
    }

    private function score(mixed $value): string
    {
        return LegacyFootballResultMapping::score($value);
    }

    private function splitContext(array $match, array $source): array
    {
        $project = json_decode((string) ($match['project_metadata_json'] ?? ''), true)['legacy'] ?? [];
        if (!is_array($project)) return [];
        $records = $this->db->setQuery($this->db->getQuery(true)->select(['r.batch_id', 'r.source_payload_json'])
            ->from($this->db->quoteName('#__joomleague_migration_record', 'r'))
            ->innerJoin($this->db->quoteName('#__joomleague_migration_batch', 'b') . ' ON b.id=r.batch_id')
            ->where('r.source_table=' . $this->db->quote('joomleague_match'))->where('r.target_entity=' . $this->db->quote('project_match'))
            ->where('r.target_identity=' . $this->db->quote((string) $match['id']))
            ->where('b.state=' . $this->db->quote('completed'))->where('b.source_product=' . $this->db->quote('joomleague')))->loadAssocList();
        if (count($records) !== 1) return $project;
        $original = json_decode((string) $records[0]['source_payload_json'], true);
        if (!is_array($original) || CanonicalJson::checksum($original) !== CanonicalJson::checksum($source)) return $project;
        $batch = (int) $records[0]['batch_id'];
        if (!isset($this->splitModes[$batch])) {
            $projects = [];
            $definitions = $this->db->setQuery('SELECT p.id,p.metadata_json,v.payload_json FROM ' . $this->db->quoteName('#__joomleague_project') . ' p INNER JOIN ' . $this->db->quoteName('#__joomleague_sport_profile_version') . ' v ON v.id=p.profile_version_id')->loadAssocList();
            foreach ($definitions as $definition) {
                $legacy = json_decode((string) $definition['metadata_json'], true)['legacy'] ?? [];
                $profile = json_decode((string) $definition['payload_json'], true);
                if (($profile['code'] ?? '') === 'football' && (string) ($legacy['game_parts'] ?? '') === '2') $projects[(int) $definition['id']] = [];
            }
            $rounds = $this->db->setQuery('SELECT id,project_id FROM ' . $this->db->quoteName('#__joomleague_project_round'))->loadAssocList('id');
            $rows = $this->db->setQuery('SELECT source_payload_json FROM ' . $this->db->quoteName('#__joomleague_migration_record') . ' WHERE batch_id=' . $batch . ' AND source_table=' . $this->db->quote('joomleague_match'))->loadColumn();
            $all = [];
            foreach ($rows as $raw) {
                $legacy = json_decode((string) $raw, true);
                $projectId = (int) ($rounds[(int) ($legacy['round_id'] ?? 0)]['project_id'] ?? 0);
                if (!is_array($legacy) || !array_key_exists($projectId, $projects)) continue;
                $projects[$projectId][] = $legacy; $all[] = $legacy;
            }
            $fallback = LegacyFootballResultMapping::splitMode($all);
            $this->splitModes[$batch] = [];
            foreach ($projects as $id=>$rows) $this->splitModes[$batch][$id] = LegacyFootballResultMapping::splitMode($rows) ?? $fallback;
        }
        $project['split_mode'] = $this->splitModes[$batch][(int) $match['project_id']] ?? null;
        return $project;
    }

    private function payload(array $current, array $participants, array $source, array $project): array
    {
        $plan = LegacyFootballResultMapping::map($source, 'football', $project);
        if ($plan === null) throw new \InvalidArgumentException('No legacy repair candidate.');
        $make = static function (string $code, array $scores, int $ordinal) use ($participants): array {
            return ['level_code'=>$code,'sequence_number'=>1,'segment_type_ordinal'=>$ordinal,'status_code'=>null,'metadata'=>$code === 'result' ? [] : ['legacy_phase_aggregate'=>true,'decider'=>$code === 'shootout'],'children'=>[], 'values'=>array_map(static fn(int $i): array => ['participant_id'=>(int)$participants[$i]['id'],'numeric_value'=>$scores[$i],'text_value'=>null,'status_code'=>null,'result_rank'=>null,'metadata'=>[]], [0,1])];
        };
        $root = $make('result', $plan['final'], 0);
        foreach ($plan['children'] as [$code, $ordinal, $scores]) $root['children'][] = $make($code, $scores, $ordinal);
        if ($plan['outcome'] === 'forfeit') $root['metadata']['legacy_awarded_result'] = $plan['resolution'];
        $previousChecksum = CanonicalJson::checksum($current);
        $current['segments'] = [$root];
        $current['status_code'] = 'final';
        $current['outcome_code'] = $plan['outcome'];
        $current['metadata']['legacy_result_resolution'] = $plan['resolution'];
        $current['metadata']['legacy_extended_repair'] = ['version'=>2,'source_checksum'=>CanonicalJson::checksum($source),'previous_result_checksum'=>$previousChecksum];
        return $current;
    }
}
