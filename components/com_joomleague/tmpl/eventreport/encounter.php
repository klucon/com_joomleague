<?php
declare(strict_types=1);
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$duels = []; $sets = []; $setColumns = []; $penaltyRounds = [];
foreach ($data['segments'] as $segment) {
    if ($segment->level_code === 'duel') { $duels[] = $segment; }
    elseif ($segment->level_code === 'penalty_round') { $penaltyRounds[] = $segment; }
    elseif ($segment->level_code === 'set') {
        $sequence = (int) $segment->sequence_number;
        $sets[(int) $segment->parent_id][$sequence] = $segment;
        $setColumns[$sequence] = $sequence;
    }
}
ksort($setColumns, SORT_NUMERIC);
$pairScore = static function (object $segment) use ($participants, $data, $formatValue): string {
    $scores = [];
    foreach ($participants as $participant) {
        $value = $data['valuesBySegment'][(int)$segment->id][(int)$participant->id] ?? null;
        $scores[] = $value ? $formatValue($value) : '-';
    }
    return implode(':', $scores);
};
?>
<div class="table-responsive">
<table class="table table-striped align-middle">
    <thead><tr>
        <th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_SEGMENT'); ?></th>
        <?php foreach ($participants as $participant) : ?><th scope="col"><?php echo htmlspecialchars((string)$participant->name, ENT_QUOTES, 'UTF-8'); ?></th><?php endforeach; ?>
        <?php foreach ($setColumns as $sequence) : ?><th scope="col" class="text-center text-nowrap"><?php echo Text::sprintf('COM_JOOMLEAGUE_EVENTREPORT_ENCOUNTER_SET_COLUMN', $sequence); ?></th><?php endforeach; ?>
        <th scope="col" class="text-center text-nowrap"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_RESULT'); ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($duels as $duel) : ?>
        <tr>
            <th scope="row" class="text-nowrap"><?php echo htmlspecialchars($segmentLabel($duel), ENT_QUOTES, 'UTF-8'); ?> <?php echo (int)$duel->sequence_number; ?></th>
            <?php foreach ($participants as $participant) : ?>
                <td><?php foreach ($data['encounterMembers'][(int)$duel->sequence_number][(int)$participant->id] ?? [] as $member) : ?>
                    <div><?php if ($member->person_id) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int)$member->person_id); ?>"><?php echo htmlspecialchars(trim($member->first_name . ' ' . $member->last_name), ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><?php echo Text::_('COM_JOOMLEAGUE_ENCOUNTER_PRIVATE_PLAYER'); ?><?php endif; ?></div>
                <?php endforeach; ?>
                <?php $gameValue = $data['valuesBySegment'][(int) $duel->id][(int) $participant->id] ?? null; $gameMetadata = $gameValue->metadata ?? []; ?>
                <?php if (isset($gameMetadata['innings']) && is_int($gameMetadata['innings']) && $gameMetadata['innings'] > 0) : ?>
                    <div class="small text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_ENCOUNTER_INNINGS'); ?>: <?php echo $gameMetadata['innings']; ?>
                    <?php if ($gameValue->numeric_value !== null) : ?> · <?php echo Text::_('COM_JOOMLEAGUE_ENCOUNTER_AVERAGE'); ?>: <?php echo number_format((float) $gameValue->numeric_value / $gameMetadata['innings'], 3, '.', ''); ?><?php endif; ?></div>
                <?php endif; ?>
                <?php if (isset($gameMetadata['high_run']) && is_int($gameMetadata['high_run'])) : ?><div class="small text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_ENCOUNTER_HIGH_RUN'); ?>: <?php echo $gameMetadata['high_run']; ?></div><?php endif; ?>
                </td>
            <?php endforeach; ?>
            <?php foreach ($setColumns as $sequence) : $set = $sets[(int)$duel->id][$sequence] ?? null; ?>
                <td class="text-center text-nowrap"><?php echo $set ? htmlspecialchars($pairScore($set), ENT_QUOTES, 'UTF-8') : '&mdash;'; ?></td>
            <?php endforeach; ?>
            <td class="text-center text-nowrap fw-semibold"><?php echo htmlspecialchars($pairScore($duel), ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php if ($penaltyRounds !== []) : ?>
<?php $penaltyLineup = []; foreach ($data['lineup'] as $member) $penaltyLineup[(int) $member->id] = $member; ?>
<div class="table-responsive"><table class="table table-striped align-middle">
    <caption><?php echo Text::_('COM_JOOMLEAGUE_CAROM_PENALTY_CAPTION'); ?></caption>
    <thead><tr><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_CAROM_PENALTY_ROUND'); ?></th><?php foreach ($participants as $participant) : ?><th scope="col" class="text-center"><?php echo htmlspecialchars((string) $participant->name, ENT_QUOTES, 'UTF-8'); ?></th><?php endforeach; ?></tr></thead>
    <tbody><?php foreach ($penaltyRounds as $round) : ?><tr><th scope="row"><?php echo (int) $round->sequence_number; ?></th><?php foreach ($participants as $participant) : $value = $data['valuesBySegment'][(int) $round->id][(int) $participant->id] ?? null; ?><td class="text-center"><?php echo $value ? htmlspecialchars($formatValue($value), ENT_QUOTES, 'UTF-8') : '&mdash;'; ?>
    <?php if (isset($value->metadata['lineup_member_id'])) : $member = $penaltyLineup[(int) $value->metadata['lineup_member_id']] ?? null; ?>
    <div class="fw-normal"><?php if ($member && (int) $member->match_participant_id === (int) $participant->id) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $member->person_id); ?>"><?php echo htmlspecialchars((string) $member->name, ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><?php echo Text::_('COM_JOOMLEAGUE_ENCOUNTER_PRIVATE_PLAYER'); ?><?php endif; ?></div>
    <?php endif; ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
<?php $cycleLength = (int) ($rootSegment->metadata['penalty_cycle_length'] ?? 0); if (($rootSegment->metadata['penalty_resolution'] ?? '') === 'team_cycle' && $cycleLength > 0 && $cycleLength <= 64) : ?>
<tfoot><?php foreach (array_chunk($penaltyRounds, $cycleLength) as $cycle => $rounds) : if (count($rounds) !== $cycleLength) continue; ?>
<tr><th scope="row"><?php echo Text::sprintf('COM_JOOMLEAGUE_CAROM_PENALTY_CYCLE_TOTAL', $cycle + 1); ?></th>
<?php foreach ($participants as $participant) : $total = 0; foreach ($rounds as $round) $total += (int) ($data['valuesBySegment'][(int) $round->id][(int) $participant->id]->numeric_value ?? 0); ?><td class="text-center fw-semibold"><?php echo $total; ?></td><?php endforeach; ?></tr>
<?php endforeach; ?></tfoot><?php endif; ?>
</table></div>
<?php foreach ($participants as $participant) : $decision = isset($rootSegment) && $rootSegment ? ($data['valuesBySegment'][(int) $rootSegment->id][(int) $participant->id] ?? null) : null; if (($decision->status_code ?? '') !== 'winner') continue; ?>
<p class="fw-semibold"><?php echo Text::_('COM_JOOMLEAGUE_CAROM_PENALTY_WINNER'); ?>: <?php echo htmlspecialchars((string) $participant->name, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endforeach; ?>
<?php endif; ?>
<?php if (!empty($data['caromSummary'])) : ?>
<div class="table-responsive">
<table class="table align-middle">
    <caption><?php echo Text::_('COM_JOOMLEAGUE_CAROM_SUMMARY_CAPTION'); ?></caption>
    <thead><tr><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_CAROM_SUMMARY_TEAM'); ?></th><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_CAROM_SUMMARY_CAROMS'); ?></th><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_ENCOUNTER_INNINGS'); ?></th><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_ENCOUNTER_AVERAGE'); ?></th><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_ENCOUNTER_HIGH_RUN'); ?></th></tr></thead>
    <tbody><?php foreach ($participants as $participant) : $summary = $data['caromSummary'][(int) $participant->id] ?? null; if (!$summary) continue; ?>
    <tr><th scope="row"><?php echo htmlspecialchars((string) $participant->name, ENT_QUOTES, 'UTF-8'); ?></th><td><?php echo (int) $summary['caroms']; ?></td><td><?php echo $summary['complete'] ? (int) $summary['innings'] : '&mdash;'; ?></td><td><?php echo $summary['average'] !== null ? number_format($summary['average'], 3, '.', '') : '&mdash;'; ?></td><td><?php echo $summary['high_run'] !== null ? (int) $summary['high_run'] : '&mdash;'; ?></td></tr>
    <?php endforeach; ?></tbody>
</table>
</div>
<?php endif; ?>
<?php if (!empty($data['caromPlayers'])) : ?>
<div class="table-responsive"><table class="table align-middle">
    <caption><?php echo Text::_('COM_JOOMLEAGUE_CAROM_PLAYERS_CAPTION'); ?></caption>
    <thead><tr><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_CAROM_PLAYERS_NAME'); ?></th><?php foreach (['GAMES', 'CAROMS', 'INNINGS', 'AVERAGE', 'HIGH_RUN'] as $metric) : ?><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_CAROM_SEASON_' . $metric); ?></th><?php endforeach; ?></tr></thead>
    <tbody><?php foreach ($data['caromPlayers'] as $personId => $player) : $summary = $player['summary']; $person = $player['person']; ?>
    <tr><th scope="row"><a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $personId); ?>"><?php echo htmlspecialchars(trim($person->first_name . ' ' . $person->last_name), ENT_QUOTES, 'UTF-8'); ?></a></th><td><?php echo (int) $summary['games']; ?></td><td><?php echo (int) $summary['caroms']; ?></td><td><?php echo $summary['complete'] ? (int) $summary['innings'] : '&mdash;'; ?></td><td><?php echo $summary['average'] !== null ? number_format($summary['average'], 3, '.', '') : '&mdash;'; ?></td><td><?php echo $summary['high_run'] !== null ? (int) $summary['high_run'] : '&mdash;'; ?></td></tr>
    <?php endforeach; ?></tbody>
</table></div>
<?php endif; ?>
