<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;

final class SqlimportController extends BaseController
{
	/** Upload + analyse a dump; returns the per-table manifest as JSON. */
	public function analyze(): void
	{
		$this->guard();

		try {
			$file = $this->input->files->get('sql_file', [], 'array');
			$tmp = (string) ($file['tmp_name'] ?? '');

			if ($tmp === '' || !is_uploaded_file($tmp)) {
				throw new \RuntimeException('COM_JOOMLEAGUE_SQLIMPORT_ERROR_FILE');
			}

			$result = $this->getModel('Sqlimport')->analyze($tmp);
			$this->json(['ok' => true, 'token' => $result->token, 'prefix' => $result->prefix, 'tables' => $result->tables]);
		} catch (\Throwable $e) {
			$this->json(['ok' => false, 'error' => $this->msg($e)]);
		}
	}

	/** Import a single table chunk. */
	public function importtable(): void
	{
		$this->guard();

		$token = $this->input->getString('token', '');
		$table = $this->input->getCmd('table', '');

		try {
			$this->getModel('Sqlimport')->importTable($token, $table);
			$this->json(['ok' => true, 'table' => $table]);
		} catch (\Throwable $e) {
			$this->json(['ok' => false, 'table' => $table, 'error' => $this->msg($e)]);
		}
	}

	/** Remove a finished job's temporary files. */
	public function cleanupjob(): void
	{
		$this->guard();

		try {
			$this->getModel('Sqlimport')->cleanup($this->input->getString('token', ''));
		} catch (\Throwable $e) {
			// ignore
		}

		$this->json(['ok' => true]);
	}

	private function guard(): void
	{
		if (!Session::checkToken('request')) {
			$this->json(['ok' => false, 'error' => Text::_('JINVALID_TOKEN')]);
			$this->app->close();
		}

		if (!$this->app->getIdentity()->authorise('core.admin', 'com_joomleague')) {
			$this->json(['ok' => false, 'error' => Text::_('JERROR_ALERTNOAUTHOR')]);
			$this->app->close();
		}
	}

	private function msg(\Throwable $e): string
	{
		$key = $e->getMessage();

		return str_starts_with($key, 'COM_JOOMLEAGUE_') ? Text::_($key) : $key;
	}

	private function json(array $data): void
	{
		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$this->app->sendHeaders();
		echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$this->app->close();
	}
}
