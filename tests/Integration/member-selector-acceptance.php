<?php
declare(strict_types=1);
if (!defined('_JEXEC') || $db->getPrefix() !== 'r1_') throw new RuntimeException('Disposable fixture required');

$selectorChecks = [];
$checkSelector = static function(bool $ok, string $label) use (&$selectorChecks): void {
	if (!$ok) throw new RuntimeException($label);
	$selectorChecks[] = $label;
};
$lookup = new Joomleague\Component\Joomleague\Administrator\Service\EntryMemberPersonOptions($db);
$user = $app->getIdentity();
$entryId = (int) $fixture['projects']['a']['entries'][0];
$membershipId = (int) $fixture['projects']['a']['records']['Entrymember'];
$context = $lookup->context($entryId, $membershipId, $user);
$checkSelector((int) $context->id === $entryId, 'stored-parent-context');
$forged = $lookup->context((int) $fixture['projects']['b']['entries'][0], $membershipId, $user);
$checkSelector((int) $forged->id === $entryId, 'forged-url-does-not-change-owner');
try {
	$lookup->context((int) $fixture['projects']['b']['entries'][0], 0, $user);
	throw new RuntimeException('foreign project accepted');
} catch (RuntimeException $e) { $checkSelector($e->getMessage() !== 'foreign project accepted', 'foreign-project-denied'); }
$db->transactionStart();
try {
	$insertSelector = static function(string $table, array $values) use ($db): int {
		$row = (object) ($values + ['uuid' => Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(), 'published' => 1]);
		$db->insertObject('#__joomleague_' . $table, $row, 'id'); return (int) $row->id;
	};
	$club = $insertSelector('club', ['name' => 'S98 club']);
	$team = (object) ['id' => (int) $context->team_id, 'club_id' => $club]; $db->updateObject('#__joomleague_team', $team, 'id');
	$context = $lookup->context($entryId, $membershipId, $user);
	$local = $insertSelector('person', ['first_name' => 'S98', 'last_name' => 'Aardvark', 'club_id' => $club]);
	$external = $insertSelector('person', ['first_name' => 'S98', 'last_name' => 'External']);
	$private = $insertSelector('person', ['first_name' => 'S98', 'last_name' => 'Private', 'access' => 999]);
	$unpublished = $insertSelector('person', ['first_name' => 'S98', 'last_name' => 'Unpublished', 'published' => 0]);
	$ids = static fn($rows) => array_column($rows, 'value');
	$checkSelector($ids($lookup->search($context, $user, 'S98')) === [$local], 'club-filter-no-global-person-type');
	$checkSelector($ids($lookup->search($context, $user, 's98')) === [$local], 'case-insensitive-on-both-drivers');
	$checkSelector($ids($lookup->search($context, $user, 'S98', true)) === [$local, $external], 'explicit-outside-club-and-private-exclusion');
	$checkSelector($lookup->search($context, $user, '%', true) === [], 'literal-wildcard-search');
	$checkSelector($lookup->search($context, $user, "' OR 1=1 --", true) === [], 'bound-hostile-search');
	for ($i=0; $i<35; $i++) $insertSelector('person', ['first_name' => 'Bounded98', 'last_name' => sprintf('Person%02d', $i)]);
	$checkSelector(count($lookup->search($context, $user, 'Bounded98', true)) === 30, 'thirty-record-response-cap');
	$current = (object) ['id' => $personId, 'published' => 0]; $db->updateObject('#__joomleague_person', $current, 'id');
	$checkSelector($ids($lookup->search($context, $user, '', true, $personId)) === [$personId], 'current-unpublished-preserved');
	$checkSelector($lookup->search($context, $user, '', true, $unpublished) === [], 'other-unpublished-not-selectable');
	$model = $factory->createModel('Entrymember', 'Administrator', ['ignore_request' => true]);
	$data = (array) $model->getItem($membershipId);
	$checkSelector(!$model->save(array_replace($data, ['person_id' => $private])), 'forged-private-person-save-rejected');
	$checkSelector(!$model->save(array_replace($data, ['person_id' => $unpublished])), 'forged-unpublished-person-save-rejected');
	$checkSelector($model->save($data), 'unchanged-historical-person-save-retained');
} finally { $db->transactionRollback(); }
$browserClub = $insertSelector('club', ['name' => 'S98 <img src=x onerror=alert(1)>']);
$browserTeam = (object) ['id' => (int) $context->team_id, 'club_id' => $browserClub]; $db->updateObject('#__joomleague_team', $browserTeam, 'id');
$browserLocal = $insertSelector('person', ['first_name' => 'Selector', 'last_name' => 'Alpha', 'club_id' => $browserClub]);
$browserOutside = $insertSelector('person', ['first_name' => 'Selector', 'last_name' => 'External <img src=x onerror=alert(1)>']);
file_put_contents('/tmp/jl-member-selector-fixture.json', json_encode(['local'=>$browserLocal,'outside'=>$browserOutside,'club'=>$browserClub], JSON_THROW_ON_ERROR));
file_put_contents('/tmp/jl-member-selector-native.json', json_encode(['passed'=>true,'checks'=>$selectorChecks], JSON_THROW_ON_ERROR));
echo 'PASS: S98 member selectors; ' . count($selectorChecks) . " native checks.\n";
