<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomleague\Component\Joomleague\Administrator\Helper\TelemetryHelper;

final class AjaxController extends BaseController
{
	/**
	 * Uloží rozhodnutí uživatele o anonymní telemetrii a (při souhlasu) jednou odešle.
	 * Volá se z výzvy zobrazené po instalaci balíčku.
	 */
	public function telemetryconsent(): void
	{
		$result = ['ok' => false, 'sent' => false];

		if (Session::checkToken('request')) {
			$mode = $this->input->getWord('mode', '');

			if (\in_array($mode, ['once', 'monthly', 'never'], true)) {
				TelemetryHelper::setConsent($mode);

				if ($mode !== 'never') {
					$result['sent'] = TelemetryHelper::send('install');
				}

				$result['ok'] = true;
			}
		}

		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$this->app->sendHeaders();
		echo json_encode($result);
		$this->app->close();
	}

	public function projectteamsoptions(): void
	{
		$this->json($this->getModel('Ajax')->getProjectTeams($this->input->getInt('p'), $this->input->getInt('division', 0)));
	}

	public function projectteamsbaseoptions(): void
	{
		$this->json($this->getModel('Ajax')->getProjectBaseTeams($this->input->getInt('p')));
	}

	public function projectdivisionsoptions(): void
	{
		$this->json($this->getModel('Ajax')->getProjectDivisions($this->input->getInt('p')));
	}

	public function projectclubsoptions(): void
	{
		$this->json($this->getModel('Ajax')->getProjectClubs($this->input->getInt('p')));
	}

	public function projecteventtypesoptions(): void
	{
		$this->json($this->getModel('Ajax')->getProjectEventTypes($this->input->getInt('p')));
	}

	public function projectstatisticsoptions(): void
	{
		$this->json($this->getModel('Ajax')->getProjectStatistics($this->input->getInt('p')));
	}

	public function projecttreesoptions(): void
	{
		$this->json($this->getModel('Ajax')->getProjectTrees($this->input->getInt('p')));
	}

	public function projectpredictiongamesoptions(): void
	{
		$this->json($this->getModel('Ajax')->getProjectPredictionGames($this->input->getInt('p')));
	}

	public function roundsoptions(): void
	{
		$this->json($this->getModel('Ajax')->getProjectRounds($this->input->getInt('p')));
	}

	public function matchesoptions(): void
	{
		$this->json($this->getModel('Ajax')->getMatches($this->input->getInt('p'), $this->input->getInt('pt')));
	}

	public function persons(): void
	{
		$this->json($this->getModel('Ajax')->searchPersons($this->input->getString('q', $this->input->getString('query', ''))));
	}

	public function clubs(): void
	{
		$this->json($this->getModel('Ajax')->searchClubs($this->input->getString('q', $this->input->getString('query', ''))));
	}

	public function playground(): void
	{
		$pgId = (int) $this->getModel('Match')->resolvePlayground($this->input->getInt('pt'));
		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$this->app->sendHeaders();
		echo json_encode(['id' => $pgId], JSON_UNESCAPED_UNICODE);
		$this->app->close();
	}

	private function json(array $data): void
	{
		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$this->app->sendHeaders();
		echo json_encode(['items' => $data, 'total' => count($data)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$this->app->close();
	}
}
