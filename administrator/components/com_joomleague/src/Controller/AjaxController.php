<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

final class AjaxController extends BaseController
{
	public function projectteamsoptions(): void
	{
		$this->json($this->getModel('Ajax')->getProjectTeams($this->input->getInt('p'), $this->input->getInt('division')));
	}

	public function projectdivisionsoptions(): void
	{
		$this->json($this->getModel('Ajax')->getProjectDivisions($this->input->getInt('p')));
	}

	public function roundsoptions(): void
	{
		$this->json($this->getModel('Ajax')->getProjectRounds($this->input->getInt('p')));
	}

	public function matchesoptions(): void
	{
		$this->json($this->getModel('Ajax')->getMatches($this->input->getInt('p')));
	}

	public function persons(): void
	{
		$this->json($this->getModel('Ajax')->searchPersons($this->input->getString('q', $this->input->getString('query', ''))));
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
