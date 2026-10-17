<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$db = Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
if (!str_contains((string) JPATH_BASE, '/var/www/html')) exit(1);
$query = $db->getQuery(true)->select(['m.id AS event','m.project_id AS project','m.stage_id AS stage','p.project_entry_id AS entry','e.team_id AS team','t.club_id AS club','m.venue_id AS venue'])
    ->from('#__joomleague_project_match AS m')->innerJoin('#__joomleague_match_participant AS p ON p.match_id=m.id')
    ->innerJoin('#__joomleague_project_entry AS e ON e.id=p.project_entry_id')->innerJoin('#__joomleague_team AS t ON t.id=e.team_id')
    ->where('m.published=1')->where('e.published=1')->where('t.published=1')->order('m.id DESC');
$context=$db->setQuery($query,0,1)->loadAssoc();
$context['round']=$db->setQuery('SELECT round_id FROM #__joomleague_project_match WHERE id='.(int)$context['event'])->loadResult();
$context['formIds']=[];
foreach (['competition'=>'competition','season'=>'season','sporttype'=>'sport_type','position'=>'sport_position','event'=>'event_type','statistic'=>'statistic','entrymember'=>'project_entry_member','stagetransition'=>'stage_transition','standingadjustment'=>'standing_adjustment'] as $view=>$table) {
    $context['formIds'][$view]=(int)$db->setQuery('SELECT id FROM #__joomleague_'.$table.' ORDER BY id',0,1)->loadResult();
}
$context['person']=$db->setQuery('SELECT id FROM #__joomleague_person WHERE published=1 ORDER BY id',0,1)->loadResult();
if (!$context['venue']) $context['venue']=$db->setQuery('SELECT id FROM #__joomleague_venue WHERE published=1 ORDER BY id',0,1)->loadResult();
echo json_encode($context, JSON_THROW_ON_ERROR);
