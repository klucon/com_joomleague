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
		$loaded = false;

		if ($source !== '') {
			$loaded = $language->load('pkg_joomleague', $source, null, true);
		}

		if (!$loaded) {
			$loaded = $language->load('pkg_joomleague', JPATH_SITE, null, true);
		}

		if (!$loaded) {
			throw new RuntimeException('Unable to load the JoomLeague package language.');
		}
	}

	/** @param list<array{label: string, passed: bool}> $checks */
	private function renderPreflight(array $checks): void
	{
		$items = '';

		foreach ($checks as $check) {
			$statusClass = $check['passed'] ? 'text-success' : 'text-danger';
			$statusIcon = $check['passed'] ? 'icon-check' : 'icon-times';
			$statusText = Text::_($check['passed'] ? 'PKG_JOOMLEAGUE_INSTALL_STATUS_READY' : 'PKG_JOOMLEAGUE_INSTALL_STATUS_FAILED');
			$items .= '<div class="col-12 col-sm-6 col-xl-3"><div class="border rounded p-3 h-100 bg-body">'
				. '<div class="d-flex align-items-center gap-2 ' . $statusClass . ' fw-semibold mb-2">'
				. '<span class="' . $statusIcon . '" aria-hidden="true"></span><span>' . $this->escape($statusText) . '</span></div>'
				. '<div>' . $this->escape(Text::_($check['label'])) . '</div></div></div>';
		}

		echo '<section class="mb-4">'
			. '<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3"><div>'
			. '<div class="text-uppercase text-muted small fw-bold mb-1">' . $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_REPORT_LABEL')) . '</div>'
			. '<h2 class="h4 mb-1"><span class="icon-shield" aria-hidden="true"></span> '
			. $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_PREFLIGHT_TITLE')) . '</h2>'
			. '<p class="text-muted mb-0">' . $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_PREFLIGHT_DESC')) . '</p></div>'
			. '<span class="badge text-bg-dark">' . $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_VERSION')) . '</span></div>'
			. '<div class="row g-2">' . $items . '</div></section>';
	}

	private function renderPostflight(string $type): void
	{
		$isUpdate = $type === 'update';
		$title = Text::_($isUpdate ? 'PKG_JOOMLEAGUE_INSTALL_UPDATED_TITLE' : 'PKG_JOOMLEAGUE_INSTALL_COMPLETE_TITLE');
		$description = Text::_($isUpdate ? 'PKG_JOOMLEAGUE_INSTALL_UPDATED_DESC' : 'PKG_JOOMLEAGUE_INSTALL_COMPLETE_DESC');
		$areas = [
			['icon' => 'icon-grid', 'title' => 'PKG_JOOMLEAGUE_INSTALL_PLATFORM_TITLE', 'description' => 'PKG_JOOMLEAGUE_INSTALL_PLATFORM_DESC'],
			['icon' => 'icon-list', 'title' => 'PKG_JOOMLEAGUE_INSTALL_CONTENT_TITLE', 'description' => 'PKG_JOOMLEAGUE_INSTALL_CONTENT_DESC'],
			['icon' => 'icon-plug', 'title' => 'PKG_JOOMLEAGUE_INSTALL_INTEGRATIONS_TITLE', 'description' => 'PKG_JOOMLEAGUE_INSTALL_INTEGRATIONS_DESC'],
		];
		$areaItems = '';

		foreach ($areas as $area) {
			$areaItems .= '<div class="col-12 col-lg-4"><article class="card h-100 shadow-sm border-0"><div class="card-body">'
				. '<div class="text-success mb-3"><span class="' . $area['icon'] . ' fs-2" aria-hidden="true"></span></div>'
				. '<h3 class="h5">' . $this->escape(Text::_($area['title'])) . '</h3>'
				. '<p class="text-muted mb-0">' . $this->escape(Text::_($area['description'])) . '</p>'
				. '</div></article></div>';
		}

		$steps = [
			['number' => '1', 'title' => 'PKG_JOOMLEAGUE_INSTALL_STEP_PROFILE_TITLE', 'description' => 'PKG_JOOMLEAGUE_INSTALL_STEP_PROFILE_DESC'],
			['number' => '2', 'title' => 'PKG_JOOMLEAGUE_INSTALL_STEP_PROJECT_TITLE', 'description' => 'PKG_JOOMLEAGUE_INSTALL_STEP_PROJECT_DESC'],
			['number' => '3', 'title' => 'PKG_JOOMLEAGUE_INSTALL_STEP_PUBLISH_TITLE', 'description' => 'PKG_JOOMLEAGUE_INSTALL_STEP_PUBLISH_DESC'],
		];
		$stepItems = '';

		foreach ($steps as $step) {
			$stepItems .= '<div class="col-12 col-lg-4"><div class="d-flex gap-3 h-100">'
				. '<span class="badge rounded-pill text-bg-primary align-self-start">' . $step['number'] . '</span><div>'
				. '<h3 class="h6 mb-1">' . $this->escape(Text::_($step['title'])) . '</h3>'
				. '<p class="text-muted small mb-0">' . $this->escape(Text::_($step['description'])) . '</p>'
				. '</div></div></div>';
		}

		$dashboardUrl = 'index.php?option=com_joomleague&view=dashboard';
		$profilesUrl = 'index.php?option=com_joomleague&view=sportprofiles';
		$settingsUrl = 'index.php?option=com_config&view=component&component=com_joomleague';
		$modulesUrl = 'index.php?option=com_modules&view=modules&client_id=0';

		echo '<section class="mb-4">'
			. '<div class="alert alert-success d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 p-4 mb-4" role="status">'
			. '<div><div class="text-uppercase small fw-bold mb-1"><span class="icon-check-circle" aria-hidden="true"></span> '
			. $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_REPORT_LABEL')) . '</div>'
			. '<h2 class="h3 mb-1">' . $this->escape($title) . '</h2><p class="mb-0">' . $this->escape($description) . '</p></div>'
			. '<a class="btn btn-success btn-lg flex-shrink-0" href="' . $this->escape($dashboardUrl) . '"><span class="text-white"><span class="icon-home" aria-hidden="true"></span> '
			. $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_OPEN_DASHBOARD')) . '</span></a></div>'
			. '<div class="row g-3 mb-4">' . $areaItems . '</div>'
			. '<div class="border-top border-bottom py-4 mb-4"><h2 class="h5 mb-3"><span class="icon-lightbulb" aria-hidden="true"></span> '
			. $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_NEXT_TITLE')) . '</h2><div class="row g-4">' . $stepItems . '</div></div>'
			. '<div class="d-flex flex-wrap gap-2">'
			. '<a class="btn btn-primary" href="' . $this->escape($profilesUrl) . '"><span class="text-white"><span class="icon-list" aria-hidden="true"></span> '
			. $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_OPEN_PROFILES')) . '</span></a>'
			. '<a class="btn btn-outline-primary" href="' . $this->escape($settingsUrl) . '"><span class="icon-options" aria-hidden="true"></span> '
			. $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_OPEN_SETTINGS')) . '</a>'
			. '<a class="btn btn-outline-secondary" href="' . $this->escape($modulesUrl) . '"><span class="icon-cube" aria-hidden="true"></span> '
			. $this->escape(Text::_('PKG_JOOMLEAGUE_INSTALL_OPEN_MODULES')) . '</a></div></section>';
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
