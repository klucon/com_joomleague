<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Results;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Site\Service\ProjectTemplateProvider;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;

final class HtmlView extends BaseHtmlView
{
    /** @var array<string,mixed> */
    public array $results = [];
	/** @var array<string,mixed> */
	public array $templateConfig = [];
	public bool $raceResults = false;

    public function display($tpl = null): void
    {
        $this->results = $this->getModel()->getResults();
		PublicViewGuard::requireItem($this->results, 'project');
		if (isset($this->results['project'])) {
			$provider = new ProjectTemplateProvider(Factory::getContainer()->get(DatabaseInterface::class));
			$projectId = (int) $this->results['project']->id;
			$code = $provider->supports($projectId, 'race_results') ? 'race_results' : 'results';
			$this->raceResults = $code === 'race_results';
			$this->templateConfig = $provider->resolve($projectId, $code);
			if ($code === 'results' && ($this->templateConfig['sort_rounds_by_date'] ?? false)) {
				usort($this->results['rounds'], static function (array $first, array $second): int {
					$firstDate = (string) ($first['items'][0]->scheduled_start ?? '9999-12-31');
					$secondDate = (string) ($second['items'][0]->scheduled_start ?? '9999-12-31');
					return $firstDate <=> $secondDate;
				});
			}
		}

        $title = isset($this->results['project'])
            ? Text::sprintf('COM_JOOMLEAGUE_RESULTS_PAGE_TITLE', $this->results['project']->name)
            : Text::_('COM_JOOMLEAGUE_RESULTS_VIEW_TITLE');

		if (isset($this->results['project'])) {
			$project = $this->results['project'];
			$stageId = Factory::getApplication()->getInput()->getInt('stage_id', 0);
			$route = 'index.php?option=com_joomleague&view=results&project_id=' . (int) $project->id;
			if ($stageId > 0) $route .= '&stage_id=' . $stageId;
			(new SeoMetadata())->apply($this->getDocument(), $title, Route::_($route, false, Route::TLS_IGNORE, true), Text::sprintf('COM_JOOMLEAGUE_RESULTS_META_DESCRIPTION', $project->name));
		} else {
			$this->getDocument()->setTitle($title);
		}

        parent::display($tpl);
    }
}
