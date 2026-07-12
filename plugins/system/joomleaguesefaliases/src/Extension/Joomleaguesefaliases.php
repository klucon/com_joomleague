<?php

/**
 * @package     Klucon.Plugin
 * @subpackage  System.joomleaguesefaliases
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Plugin\System\Joomleaguesefaliases\Extension;

use Joomla\CMS\Event\Application\AfterInitialiseEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Router;
use Joomla\CMS\Router\SiteRouterAwareTrait;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Lets translated JoomLeague base route aliases resolve through the canonical
 * Joomla menu aliases without creating duplicate menu items or redirects.
 *
 * @since  6.1.0
 */
final class Joomleaguesefaliases extends CMSPlugin implements SubscriberInterface
{
	use SiteRouterAwareTrait;

	protected $autoloadLanguage = true;

	private const BASE_ALIASES = [
		'competitions' => 'souteze',
		'wettbewerbe' => 'souteze',
		'clubs' => 'kluby',
		'vereine' => 'kluby',
		'club' => 'klub',
		'verein' => 'klub',
		'teams' => 'tymy',
		'matches' => 'zapasy',
		'spiele' => 'zapasy',
		'people' => 'osoby',
		'persons' => 'osoby',
		'personen' => 'osoby',
		'venues' => 'stadiony',
		'playgrounds' => 'stadiony',
		'spielstaetten' => 'stadiony',
	];

	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterInitialise' => 'onAfterInitialise',
		];
	}

	public function onAfterInitialise(AfterInitialiseEvent $event): void
	{
		$app = $event->getApplication();

		if (!$app->isClient('site')) {
			return;
		}

		$this->getSiteRouter()->attachParseRule([$this, 'rewriteBaseAlias'], Router::PROCESS_BEFORE);
	}

	public function rewriteBaseAlias(&$router, &$uri): void
	{
		if (!$uri instanceof Uri) {
			return;
		}

		$path = trim((string) $uri->getPath(), '/');

		if ($path === '') {
			return;
		}

		$segments = explode('/', $path);
		$index = 0;
		$rewritten = $this->rewriteSegment($segments, $index);

		if (!$rewritten && isset($segments[1]) && $this->looksLikeLanguagePrefix($segments[0])) {
			$rewritten = $this->rewriteSegment($segments, 1);
		}

		if ($rewritten) {
			$uri->setPath(implode('/', $segments));
		}
	}

	private function rewriteSegment(array &$segments, int $index): bool
	{
		$segment = $this->normalise((string) ($segments[$index] ?? ''));

		if (!isset(self::BASE_ALIASES[$segment])) {
			return false;
		}

		if ($this->menuHasAlias($segment)) {
			return false;
		}

		$segments[$index] = self::BASE_ALIASES[$segment];

		return true;
	}

	private function menuHasAlias(string $alias): bool
	{
		try {
			$items = (array) $this->getApplication()->getMenu()->getItems(['alias'], [$alias]);
		} catch (\Throwable $exception) {
			return true;
		}

		foreach ($items as $item) {
			if ((int) ($item->published ?? 0) === 1) {
				return true;
			}
		}

		return false;
	}

	private function looksLikeLanguagePrefix(string $segment): bool
	{
		return (bool) preg_match('/^[a-z]{2}(?:-[a-z]{2})?$/i', $segment);
	}

	private function normalise(string $segment): string
	{
		return strtolower(trim(rawurldecode($segment)));
	}
}
