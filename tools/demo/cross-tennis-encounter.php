<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Joomla\CMS\Factory::$application = $container->get(Joomla\Console\Application::class);
foreach (['UuidFactory','CanonicalJson','EntryModelValidator','MatchResultValidationException','MatchResultDecimal','MatchResultAggregationValidator','TeamEncounterScoreNormalizer','TeamEncounterRosterValidator','TeamEncounterRepository','MatchResultPayloadValidator','MatchResultRepository'] as $service) require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service . '.php';
$db = $container->get(Joomla\Database\DatabaseInterface::class);
$name = 'Tennis team encounter demo 2026';
$q = $db->getQuery(true)->select('m.id')->from('#__joomleague_project_match AS m')->join('INNER', '#__joomleague_project AS p ON p.id=m.project_id')->where('p.name = :name')->bind(':name',$name);
$ids = $db->setQuery($q)->loadColumn();
if (count($ids)!==1) throw new RuntimeException('Expected one owned demo match.');
$match = (int)$ids[0];
$repo = new Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository($db);
$encounter = new Joomleague\Component\Joomleague\Administrator\Service\TeamEncounterRepository($db);
$payload = $repo->get($match);
$payload['duels'] = $encounter->get($match);
$payload['encounter_revision'] = $encounter->revision($match);
$roster = $db->setQuery($db->getQuery(true)->select(['m.id','p.first_name','p.last_name'])->from('#__joomleague_match_lineup_member AS m')->join('INNER', '#__joomleague_person AS p ON p.id=m.person_id')->where('m.match_id = ' . $match))->loadObjectList();
$players = [];
foreach ($roster as $player) $players[$player->first_name . ' ' . $player->last_name] = (int)$player->id;
foreach (['Martin Hill','David Stone'] as $player) if (!isset($players[$player])) throw new RuntimeException('Expected demo players missing.');
foreach ($payload['duels'] as &$duel) {
    if (!in_array($duel['sequence_number'],[3,4],true)) continue;
    foreach ($duel['sides'] as &$side) {
        if (array_intersect($side['lineup_member_ids'],[$players['Martin Hill'],$players['David Stone']])) {
            $side['lineup_member_ids'] = [$players[$duel['sequence_number']===3 ? 'David Stone':'Martin Hill']];
        }
    }
    unset($side);
}
unset($duel);
$before = $repo->get($match);
$repo->replace($match,$payload,0);
if ($repo->get($match)!==$before) throw new RuntimeException('Scores unexpectedly changed.');
echo "Crossed singles saved with audit; scores unchanged.\n";
