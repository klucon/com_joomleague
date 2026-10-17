<?php

declare(strict_types=1);

$directory = dirname(__DIR__) . '/administrator/components/com_joomleague/resources/sport-profiles';

$adjudicators = [
	'arbiter', 'chief_arbiter', 'chair_umpire', 'course_referee', 'finish_referee',
	'first_referee', 'fourth_official', 'judge', 'lane_official', 'line_judge',
	'line_umpire', 'linesman_1', 'linesman_2', 'referee', 'second_referee',
	'start_referee', 'steward', 'touch_judge', 'var_referee',
	'assistant_referee_1', 'assistant_referee_2',
];
$competitionOfficials = [
	'admin', 'caller', 'delegate', 'marker', 'observer', 'scorer', 'table_official',
	'timekeeper', 'video_goal_judge',
];
$eventStaffByProfile = [
	'mma_boxing' => ['doctor'],
	'motorsport' => ['marshal', 'race_director'],
	'running_race' => ['course_manager', 'medical_staff', 'organizer', 'race_director'],
];

foreach (glob($directory . '/*.json') ?: [] as $file) {
	$profile = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
	$profileCode = (string) ($profile['code'] ?? '');

	foreach ($profile['positions'] as &$position) {
		$code = (string) ($position['code'] ?? '');
		$personType = (string) ($position['person_type'] ?? '');

		$position['role_domain'] = match (true) {
			in_array($code, $adjudicators, true) => 'adjudicator',
			in_array($code, $competitionOfficials, true) => 'competition_official',
			in_array($code, $eventStaffByProfile[$profileCode] ?? [], true) => 'event_staff',
			$personType === 'staff' => 'team_staff',
			$personType === 'player', $personType === 'participant' => 'participant',
			default => throw new RuntimeException(sprintf('Position %s in %s has no role-domain mapping.', $code, basename($file))),
		};

		if (in_array($position['role_domain'], ['adjudicator', 'competition_official', 'event_staff'], true)) {
			$position['person_type'] = 'official';
			$position['actor_kinds'] = ['person'];
		} else {
			unset($position['actor_kinds']);
		}
	}
	unset($position);
	$availablePersonTypes = array_values(array_unique(array_column($profile['positions'], 'person_type')));
	$profile['entry_model']['member_person_types'] = array_values(array_filter(
		$profile['entry_model']['member_person_types'] ?? [],
		static fn (string $personType): bool => in_array($personType, $availablePersonTypes, true)
	));

	$profile['schema_version'] = '1.5.0';
	$profile['version'] = '1.6.0';
	file_put_contents($file, json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
}
