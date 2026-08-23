<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;

final class Pkg_JoomleagueInstallerScript
{
	public function preflight(string $type, InstallerAdapter $adapter): bool
	{
		if (!in_array($type, ['install', 'update'], true)) {
			return true;
		}

		$this->loadLanguage($adapter);
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		$temporaryDirectory = (string) Factory::getApplication()->get('tmp_path');
		$checks = [
			['label' => 'PKG_JOOMLEAGUE_INSTALL_CHECK_PHP', 'passed' => version_compare(PHP_VERSION, '8.3.0', '>=')],
			['label' => 'PKG_JOOMLEAGUE_INSTALL_CHECK_DATABASE', 'passed' => in_array($database->getName(), ['mysql', 'mysqli', 'pgsql'], true)],
			['label' => 'PKG_JOOMLEAGUE_INSTALL_CHECK_JSON', 'passed' => extension_loaded('json')],
			['label' => 'PKG_JOOMLEAGUE_INSTALL_CHECK_TMP', 'passed' => is_dir($temporaryDirectory) && is_writable($temporaryDirectory)],
		];

		$this->renderPreflight($checks);
		$passed = !in_array(false, array_column($checks, 'passed'), true);

		if (!$passed) {
			Factory::getApplication()->enqueueMessage(Text::_('PKG_JOOMLEAGUE_INSTALL_PREFLIGHT_FAILED'), 'error');
		}

		return $passed;
	}

	public function postflight(string $type, InstallerAdapter $adapter): bool
	{
		if (!in_array($type, ['install', 'update'], true)) {
			return true;
		}

		$this->loadLanguage($adapter);
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('enabled') . ' = 1')
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->where($db->quoteName('folder') . ' IN (' . implode(', ', [$db->quote('quickicon'), $db->quote('console'), $db->quote('task')]) . ')')
			->where($db->quoteName('element') . ' = ' . $db->quote('joomleague'));

		$db->setQuery($query)->execute();
		$this->ensureTelemetryTask();
		$this->renderPostflight($type);

		return true;
	}

	private function loadLanguage(InstallerAdapter $adapter): void
	{
		$language = Factory::getApplication()->getLanguage();
		$source = (string) $adapter->getParent()->getPath('source');
		$language->load('pkg_joomleague', JPATH_ADMINISTRATOR, null, true);

		if ($source !== '') {
			$language->load('pkg_joomleague', $source . '/language', null, true);
		}
	}

	/** @param list<array{label: string, passed: bool}> $checks */
	private function renderPreflight(array $checks): void
	{
		$items = '';

		foreach ($checks as $check) {
			$statusClass = $check['passed'] ? 'bg-success' : 'bg-danger';
			$statusIcon = $check['passed'] ? 'icon-check' : 'icon-times';
			$statusText = Text::_($check['passed'] ? 'PKG_JOOMLEAGUE_INSTALL_STATUS_READY' : 'PKG_JOOMLEAGUE_INSTALL_STATUS_FAILED');
			$items .= '<li class="list-group-item d-flex justify-content-between align-items-center gap-3">'
				. '<span>' . $this->escape(Text::_($check['label'])) . '</span>'
				. '<span class="badge ' . $statusClass . '"><span class="' . $statusIcon . '" aria-hidden="true"></span> '
				. $this->escape($statusText) . '</span></li>';
		}

		echo '<section class="card mb-4">'
			. '<div class="card-header bg-info text-white"><h2 class="h4 mb-0"><span class="icon-search" aria-hidden="true"></span> '
			. $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_PREFLIGHT_TITLE')) . '</h2></div>'
			. '<div class="card-body"><p class="card-text">' . $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_PREFLIGHT_DESC')) . '</p>'
			. '<ul class="list-group list-group-flush">' . $items . '</ul></div></section>';
	}

	private function renderPostflight(string $type): void
	{
		$isUpdate = $type === 'update';
		$title = Text::_($isUpdate ? 'PKG_JOOMLEAGUE_INSTALL_UPDATED_TITLE' : 'PKG_JOOMLEAGUE_INSTALL_COMPLETE_TITLE');
		$description = Text::_($isUpdate ? 'PKG_JOOMLEAGUE_INSTALL_UPDATED_DESC' : 'PKG_JOOMLEAGUE_INSTALL_COMPLETE_DESC');
		$features = [
			['icon' => 'icon-grid', 'text' => 'PKG_JOOMLEAGUE_INSTALL_COMPONENT'],
			['icon' => 'icon-list', 'text' => 'PKG_JOOMLEAGUE_INSTALL_PROFILES'],
			['icon' => 'icon-bars', 'text' => 'PKG_JOOMLEAGUE_INSTALL_MODULE'],
			['icon' => 'icon-plug', 'text' => 'PKG_JOOMLEAGUE_INSTALL_PLUGINS'],
			['icon' => 'icon-database', 'text' => 'PKG_JOOMLEAGUE_INSTALL_DATABASE'],
		];
		$featureItems = '';

		foreach ($features as $feature) {
			$featureItems .= '<li class="list-group-item"><span class="' . $feature['icon'] . ' text-success" aria-hidden="true"></span> '
				. $this->escape(Text::_($feature['text'])) . '</li>';
		}

		$dashboardUrl = 'index.php?option=com_joomleague&view=dashboard';
		$settingsUrl = 'index.php?option=com_config&view=component&component=com_joomleague';
		$modulesUrl = 'index.php?option=com_modules&view=modules&client_id=0';

		echo '<section class="card border-success mb-4">'
			. '<div class="card-header bg-success text-white"><h2 class="h4 mb-0"><span class="icon-check-circle" aria-hidden="true"></span> '
			. $this->escape($title) . '</h2></div>'
			. '<div class="card-body"><p class="lead">' . $this->escape($description) . '</p>'
			. '<div class="row g-3"><div class="col-12 col-lg-7"><ul class="list-group">' . $featureItems . '</ul></div>'
			. '<div class="col-12 col-lg-5"><div class="alert alert-info mb-0"><h3 class="h5"><span class="icon-lightbulb" aria-hidden="true"></span> '
			. $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_NEXT_TITLE')) . '</h3><p class="mb-0">'
			. $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_NEXT_DESC')) . '</p></div></div></div>'
			. '<div class="d-flex flex-wrap gap-2 mt-4">'
			. '<a class="btn btn-success" href="' . $this->escape($dashboardUrl) . '"><span class="icon-home" aria-hidden="true"></span> '
			. $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_OPEN_DASHBOARD')) . '</a>'
			. '<a class="btn btn-primary" href="' . $this->escape($settingsUrl) . '"><span class="icon-options" aria-hidden="true"></span> '
			. $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_OPEN_SETTINGS')) . '</a>'
			. '<a class="btn btn-secondary" href="' . $this->escape($modulesUrl) . '"><span class="icon-cube" aria-hidden="true"></span> '
			. $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_OPEN_MODULES')) . '</a>'
			. '</div></div></section>';
	}

	private function escape(string $value): string
	{
		return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
