<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Standings;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Site\Service\ProjectTemplateProvider;
use Joomleague\Component\Joomleague\Site\Service\RankingColumnFilter;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $standings = [];
	/** @var array<string,mixed> */
	public array $templateConfig = [];

	public function display($tpl = null): void
	{
		$this->standings = $this->getModel()->getStandings();
		if (isset($this->standings['project'])) {
			$provider = new ProjectTemplateProvider(Factory::getContainer()->get(DatabaseInterface::class));
			$projectId = (int) $this->standings['project']->id;
			$code = $provider->supports($projectId, 'ranking') ? 'ranking' : ($provider->supports($projectId, 'race_results') ? 'race_results' : null);
			$this->templateConfig = $code === null ? [] : $provider->resolve($projectId, $code);
			if ($code === 'ranking') {
				$this->standings['columns'] = (new RankingColumnFilter())->apply($this->standings['columns'], $this->templateConfig);
			} elseif ($code === 'race_results') {
				$scopes = $this->standings['available_scopes'];
				if (!($this->templateConfig['show_categories'] ?? true)) {
					$scopes = array_values(array_diff($scopes, ['category']));
				}
				if (!($this->templateConfig['show_team'] ?? true)) {
					$scopes = array_values(array_diff($scopes, ['team']));
				}
				$this->standings['available_scopes'] = $scopes;
			}
		}

		$title = isset($this->standings['project'])
			? (string) $this->standings['project']->name
			: Text::_('COM_JOOMLEAGUE_STANDINGS_VIEW_TITLE');

		$this->getDocument()->setTitle($title);

		parent::display($tpl);
	}

}
