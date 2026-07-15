<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Person;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Site\View\Common\SiteHtmlView;

final class HtmlView extends SiteHtmlView
{
	public array $playerHistory = [];
	public array $staffHistory = [];
	public array $refereeHistory = [];
	public array $personStats = [];
	public array $playerMatches = [];
	public array $playerMatchStats = [];
	public array $playerCareerStats = [];
	public array $playerTemplateParams = [];
	public array $staffTemplateParams = [];
	public array $refereeTemplateParams = [];
}
