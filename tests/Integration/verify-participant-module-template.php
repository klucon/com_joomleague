<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Extension/JoomleagueComponent.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Module\Participant\Site\Helper\ParticipantHelper;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
Factory::getApplication()->bootComponent('com_joomleague');

require_once JPATH_ROOT . '/components/com_joomleague/src/Service/ProjectTemplateProvider.php';
require_once JPATH_ROOT . '/modules/mod_joomleague_participant/src/Helper/ParticipantHelper.php';

$database = $container->get(DatabaseInterface::class);
$rows = $database->setQuery(
	$database->getQuery(true)
		->select(['entry.id AS entry_id', 'entry.project_id', 'version.payload_json'])
		->from($database->quoteName('#__joomleague_project_entry', 'entry'))
		->innerJoin($database->quoteName('#__joomleague_project', 'project') . ' ON project.id = entry.project_id AND project.published = 1')
		->innerJoin($database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
		->where('entry.published = 1')
		->order('entry.id ASC')
)->loadObjectList();

$fixture = null;
foreach ($rows as $row) {
	$payload = json_decode((string) $row->payload_json, true);
	if (is_array($payload['template_defaults']['participant'] ?? null)) {
		$fixture = $row;
		break;
	}
}
if ($fixture === null) {
	printf("Participant module template SKIP: no participant-profile fixture\n");
	exit(0);
}

$summary = (new ParticipantHelper())->getSummary(new Registry([
	'project_id' => (int) $fixture->project_id,
	'entry_id' => (int) $fixture->entry_id,
	'template_show_personal_data' => '0',
	'template_show_results' => '0',
]));
if (isset($summary['error'])) {
	throw new RuntimeException('Participant module could not render the selected published fixture.');
}
if (($summary['template_config']['show_personal_data'] ?? true) !== false
	|| ($summary['template_config']['show_results'] ?? true) !== false) {
	throw new RuntimeException('Participant module presentation overrides were not applied.');
}

printf("Participant module template OK on %s: project %d entry %d overrides validated\n", $database->getServerType(), $fixture->project_id, $fixture->entry_id);
