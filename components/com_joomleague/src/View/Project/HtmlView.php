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
use Joomleague\Component\Joomleague\Site\Service\ProjectSchemaBuilder;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $project = [];

	public function display($tpl = null): void
	{
		$this->project = $this->getModel()->getProject();
		$title = isset($this->project['project'])
			? (string) $this->project['project']->name
			: Text::_('COM_JOOMLEAGUE_PROJECT_VIEW_TITLE');
		$this->getDocument()->setTitle($title);
		if (isset($this->project['project'])) {
			$description = trim(strip_tags((string) $this->project['project']->description));
			if ($description !== '') {
				$this->getDocument()->setDescription($description);
			}
			$schema = (new ProjectSchemaBuilder())->build(
				$this->project,
				Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $this->project['project']->id, true, Route::TLS_IGNORE, true)
			);
			if ($schema !== []) {
				$this->getDocument()->addCustomTag('<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>');
			}
		}
		parent::display($tpl);
	}
}
