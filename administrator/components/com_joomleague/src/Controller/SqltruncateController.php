<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;

final class SqltruncateController extends BaseController
{
	private const LOG_CATEGORY = 'com_joomleague.sqltruncate';

	/** Live row counts for the confirmation screen. */
	public function counts(): void
	{
		$this->guard();

		$this->json(['ok' => true, 'counts' => $this->getModel('Sqltruncate')->getCounts()]);
	}

	/** The actual, irreversible wipe. Requires the site name typed back as confirmation. */
	public function truncate(): void
	{
		$this->guard();

		$confirm = trim($this->input->getString('confirm', ''));
		$expected = trim((string) Factory::getApplication()->get('sitename', ''));
		$user = $this->app->getIdentity();

		if ($expected === '' || $confirm !== $expected) {
			$this->log(Log::WARNING, sprintf(
				'Rejected: user #%d (%s) attempted a data wipe with a non-matching confirmation phrase.',
				$user->id,
				$user->username
			));
			$this->json(['ok' => false, 'error' => Text::_('COM_JOOMLEAGUE_SQLTRUNCATE_ERROR_MISMATCH')]);

			return;
		}

		$includeReference = (bool) $this->input->getInt('include_reference', 0);

		try {
			$truncated = $this->getModel('Sqltruncate')->truncate($includeReference);
			$this->log(Log::INFO, sprintf(
				'Executed: user #%d (%s) wiped %d table(s) (reference data included: %s): %s',
				$user->id,
				$user->username,
				count($truncated),
				$includeReference ? 'yes' : 'no',
				implode(', ', $truncated)
			));
			$this->json(['ok' => true, 'truncated' => $truncated]);
		} catch (\Throwable $e) {
			$this->log(Log::ERROR, sprintf(
				'Failed: user #%d (%s) attempted a data wipe, error: %s',
				$user->id,
				$user->username,
				$e->getMessage()
			));
			$this->json(['ok' => false, 'error' => $e->getMessage()]);
		}
	}

	private function log(int $priority, string $message): void
	{
		Log::addLogger(['text_file' => 'com_joomleague_sqltruncate.php'], Log::ALL, [self::LOG_CATEGORY]);
		Log::add($message, $priority, self::LOG_CATEGORY);
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

	private function json(array $data): void
	{
		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$this->app->sendHeaders();
		echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$this->app->close();
	}
}
