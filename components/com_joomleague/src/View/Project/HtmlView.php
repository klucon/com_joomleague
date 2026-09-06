<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Project;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Site\Service\ProjectSchemaBuilder;
use Joomleague\Component\Joomleague\Site\Service\ProjectTemplateProvider;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $project = [];
	/** @var array<string,mixed> */
	public array $templateConfig = [];

	public function display($tpl = null): void
	{
		$this->project = $this->getModel()->getProject();
		if (isset($this->project['project'])) {
			$this->templateConfig = (new ProjectTemplateProvider(\Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class)))->resolve((int) $this->project['project']->id, 'project');
		}
		$title = isset($this->project['project'])
			? (string) $this->project['project']->name
			: Text::_('COM_JOOMLEAGUE_PROJECT_VIEW_TITLE');
		if (isset($this->project['project'])) {
			$url = Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $this->project['project']->id, true, Route::TLS_IGNORE, true);
			$seo = new SeoMetadata();
			$seo->apply($this->getDocument(), $title, $url, (string) $this->project['project']->description, (string) ($this->project['project']->picture ?? ''));
			$schema = (new ProjectSchemaBuilder())->build(
				$this->project,
				$url
			);
			$seo->addStructuredData($this->getDocument(), $schema);
		} else {
			$this->getDocument()->setTitle($title);
		}
		parent::display($tpl);
	}
}
