<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\Rules\NomenuRules;

final class LocalizedNomenuRules extends NomenuRules
{
	public function __construct(RouterView $router, private readonly RouteAliasRegistry $aliases)
	{
		parent::__construct($router);
	}

	public function parse(&$segments, &$vars): void
	{
		if (isset($segments[0])) {
			$view = $this->aliases->viewFor((string) $segments[0]);
			if ($view !== null) $segments[0] = $view;
		}
		parent::parse($segments, $vars);
	}

	public function build(&$query, &$segments): void
	{
		$start = count($segments);
		parent::build($query, $segments);
		if (isset($segments[$start])) $segments[$start] = $this->aliases->aliasFor((string) $segments[$start]);
	}
}
