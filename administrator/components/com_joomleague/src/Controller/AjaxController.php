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
use Joomleague\Component\Joomleague\Administrator\Helper\GeocodingHelper;
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

	/**
	 * Manuální dohledání souřadnic podle adresy (tlačítko "Najít souřadnice" u klubu/hřiště).
	 */
	public function geocode(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}

		if (!Session::checkToken('request')) {
			$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
			$this->app->sendHeaders();
			http_response_code(403);
			echo json_encode(['error' => 'Invalid token'], JSON_UNESCAPED_UNICODE);
			$this->app->close();

			return;
		}

		$result = GeocodingHelper::lookup($this->input->getString('q', ''));

		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$this->app->sendHeaders();
		echo json_encode($result ?? ['error' => 'Not found'], JSON_UNESCAPED_UNICODE);
		$this->app->close();
	}

	public function projectteamsoptions(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}
		$this->json($this->getModel('Ajax')->getProjectTeams($this->input->getInt('p', 0), $this->input->getInt('division', 0)));
	}

	public function projectteamsbaseoptions(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}
		$this->json($this->getModel('Ajax')->getProjectBaseTeams($this->input->getInt('p', 0)));
	}

	public function projectdivisionsoptions(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}
		$this->json($this->getModel('Ajax')->getProjectDivisions($this->input->getInt('p', 0)));
	}

	public function projectclubsoptions(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}
		$this->json($this->getModel('Ajax')->getProjectClubs($this->input->getInt('p', 0)));
	}

	public function projecteventtypesoptions(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}
		$this->json($this->getModel('Ajax')->getProjectEventTypes($this->input->getInt('p', 0)));
	}

	public function projectstatisticsoptions(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}
		$this->json($this->getModel('Ajax')->getProjectStatistics($this->input->getInt('p', 0)));
	}

	public function projecttreesoptions(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}
		$this->json($this->getModel('Ajax')->getProjectTrees($this->input->getInt('p', 0)));
	}

	public function projectpredictiongamesoptions(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}
		$this->json($this->getModel('Ajax')->getProjectPredictionGames($this->input->getInt('p', 0)));
	}

	public function teampositionsoptions(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}
		$this->json($this->getModel('Ajax')->getTeamStaffPositions($this->input->getInt('pt', 0)));
	}

	public function roundsoptions(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}
		$this->json($this->getModel('Ajax')->getProjectRounds($this->input->getInt('p', 0)));
	}

	public function matchesoptions(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}
		$this->json($this->getModel('Ajax')->getMatches($this->input->getInt('p', 0), $this->input->getInt('pt', 0)));
	}

	public function persons(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}
		$this->json($this->getModel('Ajax')->searchPersons($this->input->getString('q', $this->input->getString('query', ''))));
	}

	public function clubs(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}
		$this->json($this->getModel('Ajax')->searchClubs($this->input->getString('q', $this->input->getString('query', ''))));
	}

	public function playground(): void
	{
		if (!$this->requireManageAccess()) {
			return;
		}
		$pgId = (int) $this->getModel('Match')->resolvePlayground($this->input->getInt('pt', 0));
		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$this->app->sendHeaders();
		echo json_encode(['id' => $pgId], JSON_UNESCAPED_UNICODE);
		$this->app->close();
	}

	/**
	 * Tyto AJAX endpointy jen čtou interní data komponenty pro naplnění dropdownů
	 * ve formulářích administrace. Nejsou svázané s konkrétní entitou/assetem
	 * (core.edit by tu nedávalo smysl), ale pořád vyžadují alespoň přihlášeného
	 * backend uživatele s právem do komponenty spravovat obsah.
	 */
	private function requireManageAccess(): bool
	{
		if ($this->app->getIdentity()->authorise('core.manage', 'com_joomleague')) {
			return true;
		}

		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$this->app->sendHeaders();
		http_response_code(403);
		echo json_encode(['error' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
		$this->app->close();

		return false;
	}

	private function json(array $data): void
	{
		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$this->app->sendHeaders();
		echo json_encode(['items' => $data, 'total' => count($data)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$this->app->close();
	}
}
