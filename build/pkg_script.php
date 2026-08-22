<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Plugin\PluginHelper;

final class Pkg_JoomleagueInstallerScript
{
	public function postflight(string $type, InstallerAdapter $adapter): void
	{
		if (!in_array($type, ['install', 'update'], true)) {
			return;
		}

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('enabled') . ' = 1')
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->where($db->quoteName('folder') . ' IN (' . implode(', ', [$db->quote('quickicon'), $db->quote('console'), $db->quote('task')]) . ')')
			->where($db->quoteName('element') . ' = ' . $db->quote('joomleague'));

		$db->setQuery($query)->execute();
		$this->ensureTelemetryTask();
	}

	private function ensureTelemetryTask(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__scheduler_tasks'))
			->where($db->quoteName('type') . ' = ' . $db->quote('joomleague.telemetry'));

		if ((int) $db->setQuery($query)->loadResult() > 0) {
			return;
		}

		PluginHelper::importPlugin('task', 'joomleague');
		$component = Factory::getApplication()->bootComponent('com_scheduler');
		$model = $component->getMVCFactory()->createModel('Task', 'Administrator', ['ignore_request' => true]);
		$model->save([
			'title' => 'JoomLeague anonymous statistics',
			'type' => 'joomleague.telemetry',
			'execution_rules' => [
				'rule-type' => 'interval-hours',
				'interval-hours' => 24,
				'exec-day' => '01',
				'exec-time' => '03:15',
			],
			'state' => 1,
			'params' => [
				'individual_log' => false,
				'log_file' => '',
				'notifications' => [
					'success_mail' => '0',
					'failure_mail' => '0',
					'fatal_failure_mail' => '1',
					'orphan_mail' => '1',
				],
			],
		]);
	}
}
