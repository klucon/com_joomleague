<?php

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
}
