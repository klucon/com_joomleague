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

final class QuickaddController extends BaseController
{
	public function person(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		if (!$this->app->getIdentity()->authorise('core.create', 'com_joomleague')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$name = trim($this->input->post->getString('name', ''));
		$parts = preg_split('/\s+/', $name) ?: [];
		$db = $this->app->getContainer()->get(\Joomla\Database\DatabaseInterface::class);
		$row = (object) [
			'firstname' => array_shift($parts) ?: '',
			'lastname' => trim(implode(' ', $parts)) ?: '.',
			'published' => 1,
		];
		$db->insertObject('#__joomleague_person', $row, 'id');
		$this->json(['id' => (int) $row->id, 'name' => trim($row->firstname . ' ' . $row->lastname)]);
	}

	public function team(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		if (!$this->app->getIdentity()->authorise('core.create', 'com_joomleague')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$name = trim($this->input->post->getString('name', ''));
		$db = $this->app->getContainer()->get(\Joomla\Database\DatabaseInterface::class);
		$row = (object) ['name' => $name, 'middle_name' => $name, 'short_name' => $name, 'published' => 1];
		$db->insertObject('#__joomleague_team', $row, 'id');
		$this->json(['id' => (int) $row->id, 'name' => $name]);
	}

	private function json(array $data): void
	{
		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$this->app->sendHeaders();
		echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$this->app->close();
	}
}
